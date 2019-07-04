<?PHP namespace App\Services;

use Log;
use Pass;
use App\Services\RazorpayService as RazorpayService;
use App\Services\Utilities as Utilities;
use Validator;
use Order;
use Booktrial;

use Config;
use Request;
use Wallet;
use Customer;
use stdClass;

class PassService {

    public function __construct() {

    }

    public function listPasses($customerId){
        
        $passList = Pass::where('status', '1');

        $trialPurchased =$this->checkTrialPassUsedByCustomer($customerId);

        if(!empty($trialPurchased['status']) && $trialPurchased['status']) {
            $passList = $passList->where('type','!=', 'trial');
        }

        $passList = $passList->orderBy('pass_id')->get();
        
        $response = Config::get('pass.list');
        foreach($passList as &$pass) {
            $passDetails = [
                'pass_id' => $pass['pass_id'],
                'header' => $pass['duration_text'],
                'text' => 'All Access',
                'remarks' => ucwords($pass['type'])
            ];
            if($pass['unlimited_access']) {
                $passDetails['price'] = 'Rs. '.$pass['price'];
                $passDetails['old_price'] = 'Rs. '.$pass['max_retail_price'];
                $response['passes'][1]['offerings']['ratecards'][] = $passDetails;
            } else {
                $passDetails['header'] = $pass['credits'].' Sweat Points';
                $passDetails['text'] = 'for 1 month';
                $passDetails['price'] = 'Rs. '.$pass['price'];
                $passDetails['old_price'] = 'Rs. '.$pass['max_retail_price'];
                $response['passes'][0]['offerings']['ratecards'][] = $passDetails;
            }
        }
        return $response;
    }

    public function passCapture($data){


        $data['customer_source'] = !empty(Request::header('Device-Type')) ? Request::header('Device-Type') : "website" ;
        
        $data['type'] = "pass";
        $data['status'] = "0";

        $utilities = new Utilities();
        $customer_detail = $utilities->getCustomerDetail($data);

        if(empty($customer_detail['status']) || $customer_detail['status'] != 200){
            return $customer_detail;
        }

        $data = array_merge($data, $customer_detail['data']);

        $pass = Pass::where('pass_id', $data['pass_id'])->first()->toArray();

        $data['pass'] = $pass;

        $data['amount'] = $data['rp_subscription_amount'] = $pass['price'];

        $data['type'] = 'pass';
        $data['payment_mode'] =  'paymentgateway';
        $data['pass_type'] = $pass['type'];
        
        $data['start_date'] = new \MongoDate(strtotime('midnight', time()));
        $data['end_date'] = new \MongoDate(strtotime('midnight', strtotime('+'.$pass['duration'].' days', time())));
        
        if(!empty($pass['credits'])){
            
            $data['total_credits'] = $pass['credits'];
        
        }
        
        
        $id = Order::maxId()+1;
        $data['_id'] = $id;
        $order = new Order($data);
        $order['_id'] = $data['_id'];
        $order['order_id'] = $order['_id'];
        $order['orderid'] = $order['_id'];
        
        if(!empty($order['pass_type']) && $order['pass_type'] == 'trial'){
            
            $order['payment_gateway'] = 'payu';
            $txnid = "";
            $successurl = "";
            $mobilehash = "";
            if($order['customer_source'] == "android" || $order['customer_source'] == "ios"){
                $txnid = "MFIT".$order['_id'];
                $successurl = $order['customer_source'] == "android" ? Config::get('app.website')."/paymentsuccessandroid" : Config::get('app.website')."/paymentsuccessios";
            }else{
                $txnid = "FIT".$order['_id'];
                $successurl = Config::get('app.website')."/paymentsuccessproduct";
            }

            $order['txnid'] = $txnid;
            $order['finder_name'] = 'Fitternity';
            $order['finder_slug'] = 'fitternity';
            
            
            $order['service_name'] = 'Pass';
            
            $order['service_id'] = 100002;
            
            if(Config::get('app.env') != 'production'){
                $order['env'] = 1;
            }
            
            $hash = getHash($order);
            
            $order = array_merge($order,$hash);

            $order->save($order);
            
            if(in_array($order['customer_source'],['android','ios','kiosk'])){
                $mobilehash = $order['payment_related_details_for_mobile_sdk_hash'];
            }
            $result['firstname'] = trim(strtolower($order['customer_name']));
            $result['lastname'] = "";
            $result['phone'] = $order['customer_phone'];
            $result['email'] = strtolower($order['customer_email']);
            $result['orderid'] = $order['_id'];
            $result['txnid'] = $txnid;
            $result['amount'] = $order['amount'];
            $result['productinfo'] = strtolower($order['productinfo']);
            $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($order['service_name']));
            $result['successurl'] = $successurl;
            $result['hash'] = $order['payment_hash'];
            $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
            $result['finder_name'] = strtolower($order['finder_name']);
            $resp = [
                'status' => 200,
                'data' => $result,
                'message' => "Tmp Order Generated Sucessfully"
            ];
            $result['payment_modes'] = $this->getPaymentModes($resp);
            
        }else{
            
            $order['payment_gateway'] = 'razorpay';
            $order['amount_customer'] = $order['amount'];
            $order['rp_subscription_amount'] = $order['amount_customer'];
            $wallet = Wallet::active()->where('customer_id', $order['customer_id'])->where('balance', '>', 0)->where('order_type', 'pass')->first();
            if(!empty($wallet)){
                $order['fitcash'] = $wallet['balance'];
                $order['amount'] = $order['amount'] - $order['fitcash'];
                // $order['amount'] = 1;
                $order['wallet_id'] = $wallet['_id'];
            }
            
            $order->save();
            $razorpay_service = new RazorpayService();
            $create_subscription_response = $razorpay_service->createSubscription($id);
            $order['subscription_id'] = $create_subscription_response['subscription_id'];
            $order['rp_subscription_id'] = $create_subscription_response['rp_subscription_id'];

        }

        

        return  [
            'status' => 200,
            'data' => !empty($result) ? $result : $order,
            'message' => "Tmp Order Generated Sucessfully"
        ];
        


    }

    public function passSuccess($data){
        
        $order = Order::where('_id', $data['order_id'])->first();

        $this->passSuccessRazorpay($order, $data);
        
        if(empty($order['status'])){
            return ['status'=>400, 'message'=>'Invalid request'];
        }
        
        $success_data = $this->getSuccessData($order);

        return ['status'=>200, 'data'=>$order, 'message'=>'Subscription successful', 'success_data'=>!empty($success_data) ? $success_data : new stdClass()];

    }

    public function getPassBookings($orderId) {
        if(empty($orderId)) {
            return;
        }
        $booktrials = Booktrial::where('pass_order_id', $orderId)->where('going_status',1)->where('schedule_date_time','>=',new \MongoDate(strtotime('midnight')))->get();
        if(empty($booktrials)) {
            return;
        }
        $booktrials = $booktrials->toArray();
        $finalList = [];
        foreach($booktrials as $booking) {
            $temp = [];
            $temp['_id'] = $booking['_id'];
            $temp['order_id'] = $booking['order_id'];
            $temp['pass_order_id'] = $booking['pass_order_id'];
            $temp['customer_id'] = $booking['customer_id'];
            $temp['customer_name'] = $booking['customer_name'];
            $temp['customer_email'] = $booking['customer_email'];
            $temp['customer_phone'] = $booking['customer_phone'];
            $temp['type'] = $booking['type'];
            $temp['finder_id'] = $booking['finder_id'];
            $temp['amount'] = $booking['amount'];
            $temp['finder_slug'] = $booking['finder_slug'];
            $temp['finder_location'] = $booking['finder_location'];
            $temp['schedule_date'] = $booking['schedule_date'];
            $temp['schedule_date_time'] = $booking['schedule_date_time'];
            $temp['schedule_date'] = $booking['schedule_date'];
            $temp['schedule_slot_start_time'] = $booking['schedule_slot_start_time'];
            $temp['schedule_slot_end_time'] = $booking['schedule_slot_end_time'];
            array_push($finalList, $temp);
        }
        return $finalList;
    }

    function getPaymentModes($data){
        
        $utilities = new Utilities();

        $payment_modes = [];

        $payment_options['payment_options_order'] = ["cards", "upi", "wallet", "netbanking", "emi"];
        

        $payment_options['upi'] = [
            'title' => 'UPI',
            'notes' => "Note: In the next step you will be redirected to the bank's website to verify yourself"
        ];

        $payment_options['wallet'] = [
            'title' => 'Wallet',
            'subtitle' => 'Transact online with Wallets',
            'value'=>'wallet',
            'options'=>Config::get('app.pass_payment_options')
        ];

        $payment_modes[] = array(
            'title' => 'Pay now',
            'subtitle' => 'Pay online through wallet,credit/debit card',
            'value' => 'paymentgateway',
            'payment_options'=>$payment_options
        );

        return $payment_modes;
    }

    public function getCreditsApplicable($amount, $customerId) {
        if(empty($amount) && empty(!$customerId)) {
            return;
        }

        $customer = Customer::where('_id', $customerId)->first();
        $passOrder = Order::active()->where('type', 'pass')->where('customer_id', $customer['_id'])->where('end_date','>',new \MongoDate(strtotime('midnight')))->first();

        if(!empty($passOrder)) {
            $passType = $passOrder['pass_type'];
        }
        $credits = null;
        if(!empty($passType) && $passType=='unlimited') {
            return [ 'credits' => -1, 'order_id' => $passOrder['_id'], 'pass_type' => $passType ];
        }
        else if(empty($passType)) {
            return [ 'credits' => 0, 'order_id' => $passOrder['_id'] ];
        }
        $creditMap = Config::get('app.creditMap');
        foreach($creditMap as $rec) {
            if($amount<=$rec['max_price']){
                $credits = $rec['credits'];
                break;
            }
        }
        if(!empty($passOrder['total_credits']) && empty($passOrder['total_credits_used'])) {
            $passOrder['total_credits_used'] = 0;
        }
        if(isset($passOrder['total_credits']) && ($credits+$passOrder['total_credits_used'])<=$passOrder['total_credits']) {
            return [ 'credits' => $credits, 'order_id' => $passOrder['_id'], 'pass_type' => $passType ];
        }
        return [ 'credits' => 0, 'order_id' => $passOrder['_id'], 'pass_type' => $passType ];
        
    }
    
    public function passSuccessPayU($data){
    
        $rules = [
            'order_id'=>'required | integer',
            'verify_hash'=>'required'
        ];

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return ['status' => 404,'message' => error_message($validator->errors())];
        }

        $order = Order::find(intval($data['order_id']));

        $utilities = new Utilities();    
        $hash_verified = $utilities->verifyOrder($data, $order);

        if(empty($hash_verified)){
            return ['status'=>400, 'message'=>'Something went wrong. Please try later'];
        }

        if(!empty($order['pass']['cashback'])){
            
            $walletData = array(
                "order_id"=>$order['_id'],
                "customer_id"=> intval($order['customer_id']),
                "amount"=> $order['amount'],
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $order['amount'],
                "type"=>'CASHBACK',
                'entry'=>'credit',
                'order_type'=>['pass'],
                "description"=> "100% Cashback on workout-session booking at ".ucwords($order['finder_name']).", Expires On : ".date('d-m-Y',time()+(86400*14)),
                "validity"=>time()+(86400*14),
            );
    
            $utilities->walletTransaction($walletData);
        }


        $order->status = '1';
        $order->update();

        return ['status'=>200, 'message'=>'Transaction successful'];

    
    }

    public function getCreditsForAmount($amount) {
        $creditMap = Config::get('app.creditMap');
        $credits = 0;
        foreach($creditMap as $rec) {
            if($amount<$rec['max_price']) {
                $credits = $rec['credits'];
                break;
            }
        }
        return $credits;
    }
    
    public function checkTrialPassUsedByCustomer($customerId) {
        if(empty($customerId)) {
            return;
        }
        $response = ["status" => false];
        $trialPass = Order::where("pass_type", 'trial')
                    ->where('status', "1")
                    ->where('customer_id', $customerId)
                    ->select('_id')
                    ->first();
        if(isset($trialPass['_id'])) {
            $response["status"]= true;
        }
        return $response;
    }

    public function getSuccessData($order){
        
        $success_template = Config::get('pass.success');
        
        $success_template['section1'] = strtr(
            $success_template['section1'], 
            [
                '__customer_name'=>$order['customer_name'], 
                '__pass_name'=>$order['pass']['name'],
                '__pass_duration'=> $order['pass']['duration_text']
            ]
        );

        return $success_template;

    }

    public function passSuccessRazorpay($order, $data){

        if(!empty($order['payment_gateway']) && $order['payment_gateway'] == 'razorpay' && empty($order['status']) && !empty($data['payment_id'])){
            
            $order->update(['status'=>'1']);
            $razorpay_service = new RazorpayService();
            $razorpay_service->storePaymentDetails($order['_id'], $data['payment_id']);

        }
       
    }

    

}