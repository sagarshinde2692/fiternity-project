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

        if(!Config::get('app.debug')) {
            $trialPurchased =$this->checkTrialPassUsedByCustomer($customerId);
        }
        else {
            $trialPurchased = false;
        }

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
                $passDetails['offer'] = 'Get 100% instant cash back';
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

        if($pass['type']=='trial' && !Config::get('app.debug')) {
            $trialExists = $this->checkTrialPassUsedByCustomer($customer_detail['data']['customer_id']);
            if(!empty($trialExists['status']) && $trialExists['status']) {
                return [
                    'status' =>400,
                    'data' => null,
                    'msg' => 'Not eligible to book a trial pass.'
                ];
            }
        }

        $data['pass'] = $pass;
        if(empty($data['rp_subscription_id'])){
            $data['amount'] = $data['rp_subscription_amount'] = $pass['price'];
        }

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
        
        $data['order_id'] = $data['_id'];
        $data['orderid'] = $data['_id'];
        
        if(!empty($data['pass']['payment_gateway']) && $data['pass']['payment_gateway'] == 'payu'){
            
            $txnid = "";
            $successurl = "";
            $mobilehash = "";
            if($data['customer_source'] == "android" || $data['customer_source'] == "ios"){
                $txnid = "MFIT".$data['_id'];
                $successurl = $data['customer_source'] == "android" ? Config::get('app.website')."/paymentsuccessandroid" : Config::get('app.website')."/paymentsuccessios";
            }else{
                $txnid = "FIT".$data['_id'];
                $successurl = Config::get('app.website')."/paymentsuccessproduct";
            }

            $data['txnid'] = $txnid;
            $data['finder_name'] = 'Fitternity';
            $data['finder_slug'] = 'fitternity';
            
            
            $data['service_name'] = 'Pass';
            
            $data['service_id'] = 100002;
            
            if(Config::get('app.env') != 'production'){
                $data['env'] = 1;
            }
            
            $hash = getHash($data);
            
            $data = array_merge($data,$hash);
            $order = new Order($data);
            $order['_id'] = $data['_id'];
            $order->save();
            
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
            
            
            $data['amount_customer'] = $data['amount'];
            $data['rp_subscription_amount'] = $data['amount_customer'];
            $wallet = Wallet::active()->where('customer_id', $data['customer_id'])->where('balance', '>', 0)->where('order_type', 'pass')->first();
            if(!empty($wallet)){
                $data['fitcash'] = $wallet['balance'];
                $data['amount'] = $data['amount'] - $data['fitcash'];
                // $data['amount'] = 1;
                $data['wallet_id'] = $wallet['_id'];
            }
            $order = new Order($data);
            $order['_id'] = $data['_id'];

            if(!empty($data['rp_subscription_id'])){
                $order['rp_subscription_id'] = $data['rp_subscription_id'];
                $order['rp_plan_id'] = $data['plan_id'];
                $order['rp_orignal_pass_order_id'] = $data['rp_orignal_pass_order_id'];
            }

            $order->save();

            if(empty($data['rp_subscription_id'])){
                $razorpay_service = new RazorpayService();
                $create_subscription_response = $razorpay_service->createSubscription($id);
                $order['subscription_id'] = $create_subscription_response['subscription_id'];
                $order['rp_subscription_id'] = $create_subscription_response['rp_subscription_id'];
            }


        }
  
        return  [
            'status' => 200,
            'data' => !empty($result) ? $result : $order,
            'message' => "Tmp Order Generated Sucessfully"
        ];

    }

    public function passSuccess($data){
        
        if(empty($data['order_id'])){
            return;
        }
    
        if(!empty($data['razorpay'])){
            $data['payment_id'] = $data['razorpay']['razorpay_payment_id'];
            $verify_status = $this->verifyOrderSignature(["body"=>$data['razorpay']['rp_body'], "key"=> $data['razorpay']['key'], "signature"=>$data['razorpay']['razorpay_signature']])['status'];
            if(!$verify_status){
                return ['status'=>400, 'message'=>'Invalid Request.'];
            }
        }

        Log::info('pass success:: ', [$data]);

        $data['order_id'] = intval($data['order_id']);

        $order = Order::where('_id', $data['order_id'])->first();

        $wallet_update = $this->updateWallet($order);

        if(empty($wallet_update['status']) || $wallet_update['status'] != 200){
            return $wallet_update;
        }
        
        $this->passSuccessRazorpay($order, $data);
        
        if(empty($order['status'])){
            return ['status'=>400, 'message'=>'Something went wrong. Please contact customer support. (2)'];
        }
        
        $success_data = $this->getSuccessData($order);

        return ['status'=>200, 'data'=>$success_data];

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

    public function getPassOrderDetails($customerId, $credits) {
        $passOrder = Order::raw(function($collection) use ($customerId, $credits) {
            $aggregate = [
                ['$match' => [
                    'status' => '1', 'type' => 'pass', 'customer_id' => $customerId,
                    'end_date' => ['$gte' => new \MongoDate(strtotime('midnight'))],
                    'total_credits' => ['$exists' => true]
                ]],
                ['$project' => [
                    'pass_type'=>1, 'total_premium_sessions'=>1, 'premium_sessions_used'=>1, 'total_credits' => 1, 'total_credits_used' => 1,
                    'credits_diff' => ['$subtract' => ['$total_credits', '$total_credits_used']],
                    'credits_available' => ['$gte' => ['$credits_diff', $credits]]
                ]],
                ['$match' => [
                    'credits_available' => false
                ]],
                ['$sort' => ['_id' => -1]]
            ];
            return $collection->aggregate($aggregate);
        });
        if(!empty($passOrder['result'][0])) {
            $passOrder = $passOrder['result'][0];
        }
        return $passOrder;
    }

    public function getCreditsApplicable($amount, $customerId) {

        // credits: 0=>pass not applicable, -1=>unlimited access, >0=>monthly access credit points for the session

        if(empty($amount) && empty(!$customerId)) {
            return;
        }

        $customer = Customer::where('_id', $customerId)->first();

        $credits = null;
        $creditMap = Config::get('app.creditMap');
        foreach($creditMap as $rec) {
            if($amount<=$rec['max_price']){
                $credits = $rec['credits'];
                break;
            }
        }

        if(!empty($customer) && !empty($credits)) {
            $passOrder = $this->getPassOrderDetails($customerId, $credits);
        }

        if(!empty($passOrder)) {
            $passType = $passOrder['pass_type'];
        }
        if(!empty($passType) && $passType=='unlimited') {

            if($amount>=750) {
                if(!isset($passOrder['total_premium_sessions']) || !isset($passOrder['premium_sessions_used']) || !($passOrder['premium_sessions_used']<$passOrder['total_premium_sessions'])) {
                    return [ 'credits' => 0, 'order_id' => $passOrder['_id']];        
                }
            }

            return [ 'credits' => -1, 'order_id' => $passOrder['_id'], 'pass_type' => $passType ];
        }
        else if(empty($passType)) {
            return [ 'credits' => 0, 'order_id' => $passOrder['_id'] ];
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

        if(!empty($data['order_id'])) {
            $data['order_id'] = intval($data['order_id']);
        }
        $order = Order::where('status', '0')->where('pass.payment_gateway', 'payu')->where('_id', $data['order_id'])->first();
        
        if(empty($order)){
            return ['status'=>400, 'message'=>'Something went wrong. Please try later'];
        }
        
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
        $trialPass = Order::where('status', "1")->where('customer_id', $customerId)->select('_id')->first();
        // ->where("pass_type", 'trial')
        if(isset($trialPass['_id'])) {
            $response["status"]= true;
        }
        return $response;
    }

    public function getSuccessData($order){
        
        $success = Config::get('pass');
        $success_template = $success['success'];
        
        $success_template['subline'] = strtr(
            $success_template['subline'], 
            [
                '__customer_name'=>$order['customer_name'], 
                '__pass_name'=>$order['pass']['name'],
                '__pass_duration'=> $order['pass']['duration_text']
            ]
        );

        $success_template['pass']['header'] = strtr(
            $success_template['pass']['header'],
            [
                '__credit_point'=> $order['pass']['credits']
            ]
        );

        $success_template['pass']['subheader'] = strtr(
            $success_template['pass']['subheader'],
            [
                '__pass_count'=> $order['pass']['classes']
            ]
        );

        $success_template['pass']['text'] = strtr(
            $success_template['pass']['text'],
            [
                '__end_date'=> date_format($order['end_date'],'d-M-Y')
            ]
        );

        if($order['pass']['type']=='unlimited'){
            $success_template['pass']['subheader'] = "Unlimitd Access";
            $success_template['pass_image'] = $success['pass_image_gold'];
        }
        else{
            $success_template['pass_image'] = $success['pass_image_silver'];
        }
       
        if(!in_array(Request::header('Device-Type'), ["android", "ios"])){
            $success_template['web_message'] = $success['web_message'];
        }

        return $success_template;

    }

    public function passSuccessRazorpay($order, $data){

        if(!empty($order['pass']['payment_gateway']) && $order['pass']['payment_gateway'] == 'razorpay' && empty($order['status']) && !empty($data['payment_id'])){
            
            $razorpay_service = new RazorpayService();
            $storePaymentDetails = $razorpay_service->storePaymentDetails($order['_id'], $data['payment_id']);
            if(!empty($storePaymentDetails)){
                $order->update(['status'=>'1']);
            }

        }
       
    }

    public function updateWallet($order){
        
        if(!empty($order['wallet_id']) && empty($order['status'])){
            
            $wallet_update = Wallet::where('_id', $order['wallet_id'])->update(['status'=>'0']);
            
            if(empty($wallet_update)){
             
                return ['status'=>400, 'message'=>'Something went wrong. Please contact customer support. (1)'];    
            
            }

        }

        return ['status'=>200];

    }

    public function verifyOrderSignature($data){
        $expected_signature = hash_hmac('sha256', $data['body'], $data['key']);
        $response= ["status"=>false];
        Log::info("in verify signature:::::::::::::", [$data['signature'], $expected_signature]);
        if($data['signature'] == $expected_signature){
            $response['status'] = true;
        }
        return $response;
    }

}