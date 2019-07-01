<?PHP namespace App\Services;

use Log;
use Pass;
use App\Services\RazorpayService as RazorpayService;
use App\Services\Utilities as Utilities;
use Order;
use Booktrial;

use Config;
use Request;
use Wallet;
class PassService {

    public function __construct() {

    }

    public function listPasses(){

        $passList = Pass:: active()
        ->select('pass_id', 'amount', 'duaration', 'duration_type', 'type', 'credits', 'price', 'selling_price', 'premium_sessions')
        ->get();

        return array("data"=> $passList);
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
        $data['pass_type'] = $pass['type'];
        
        $id = Order::maxId()+1;
        $data['_id'] = $id;
        $order = new Order($data);
        $order['_id'] = $data['_id'];
        $order['order_id'] = $order['_id'];
        $order['orderid'] = $order['_id'];
        $order->save();
        
        if(!empty($data['pass_type']) && $data['pass_type'] == 'trial'){

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
            
            if(in_array($data['customer_source'],['android','ios','kiosk'])){
                $mobilehash = $data['payment_related_details_for_mobile_sdk_hash'];
            }
            $result['firstname'] = trim(strtolower($data['customer_name']));
            $result['lastname'] = "";
            $result['phone'] = $data['customer_phone'];
            $result['email'] = strtolower($data['customer_email']);
            $result['orderid'] = $data['_id'];
            $result['txnid'] = $txnid;
            $result['amount'] = $data['amount'];
            $result['productinfo'] = strtolower($data['productinfo']);
            $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($data['service_name']));
            $result['successurl'] = $successurl;
            $result['hash'] = $data['payment_hash'];
            $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
            $result['finder_name'] = strtolower($data['finder_name']);
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
            
            $order->update($data);
            $razorpay_service = new RazorpayService();
            $create_subscription_response = $razorpay_service->createSubscription($id);
            $data['subscription_id'] = $create_subscription_response['subscription_id'];
            $data['rp_subscription_id'] = $create_subscription_response['rp_subscription_id'];
            

        }

        $order->update($data);

        return  [
            'status' => 200,
            'data' => !empty($result) ? $result : $order,
            'message' => "Tmp Order Generated Sucessfully"
        ];
        


    }

    public function passSuccess($data){
        
        $order = Order::where('_id', $data['order_id'])->first();

        $utilities = new Utilities();

        if(!empty($order['pass_type']) && $order['pass_type'] == 'trial'){
            
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


        $order->update(['status'=>'1']);
        return ['status'=>200, 'data'=>$order, "message"=>"Subscription successful"];

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
            $temp['finder_slug'] = $booking['finder_slug'];
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
            'options'=>[
                    [
                            'title' => 'Paypal',
                            'subtitle' => '100% off upto 350 INR on first PayPal transaction.',
                            'value' => 'paypal'
                    ],
                    [
                            'title' => 'Paytm',
                            // 'subtitle' => 'Paytm',
                            'value' => 'paytm'
                    ],
                    [
                            'title' => 'AmazonPay',
                            // 'subtitle' => 'AmazonPay',
                            'value' => 'amazonpay'
                    ],
                    [
                            'title' => 'Mobikwik',
                            // 'subtitle' => 'Mobikwik',
                            'value' => 'mobikwik'
                    ],
                    [
                            'title' => 'PayU',
                            // 'subtitle' => 'PayU',
                            'value' => 'payu'
                    ]
            ]
        ];

        $payment_modes[] = array(
            'title' => 'Pay now',
            'subtitle' => 'Pay online through wallet,credit/debit card',
            'value' => 'paymentgateway',
            'payment_options'=>$payment_options
        );

        return $payment_modes;
    }

}