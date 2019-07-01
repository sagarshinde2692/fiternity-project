<?PHP namespace App\Services;

use Log;
use Pass;
use App\Services\RazorpayService as RazorpayService;
use App\Services\Utilities as Utilities;
use Order;
use Config;
use Request;
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

        $utilities = new Utilities();
        $customer_detail = $utilities->getCustomerDetail($data);

        if(empty($customer_detail['status']) || $customer_detail['status'] != 200){
            return $customer_detail;
        }

        $data = array_merge($data, $customer_detail['data']);

        $pass = Pass::where('pass_id', $data['pass_id'])->first()->toArray();

        $data['pass'] = $pass;

        $data['amount'] = $data['razorpay_subscription_amount'] = $pass['price'];

        $data['type'] = 'pass';
        $data['pass_type'] = $pass['type'];
        
        $id = Order::maxId()+1;
        $data['_id'] = $id;
        
        
        if(!empty($order['pass_type']) && $order['pass'] == 'trial'){

            $razorpay_service = new RazorpayService();
            $create_subscription_response = $razorpay_service->createSubscription($id);
            $order['subscription_id'] = $create_subscription_response['subscription_id'];
            $order['rp_subscription_id'] = $create_subscription_response['rp_subscription_id'];
            
        }else{

            
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
            
            $hash = getHash($data);
            
            $data = array_merge($data,$hash);

        }
        
        $order = new Order($data);
        $order['_id'] = $data['_id'];
        $order['order_id'] = $order['_id'];
        $order->save();


        return ['status'=>200, 'data'=>$order];


    }

    public function passSuccess($data){
        
        $order = Order::where('_id', $data['order_id'])->first();

        $utilities = new Utilities();

        if(!empty($order['pass_type']) && $order['pass'] == 'trial'){

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

}