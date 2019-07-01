<?PHP namespace App\Services;

use Log;
use Pass;
use App\Services\RazorpayService as RazorpayService;
use Order;

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

        $customer_id = autoregisterCustomer($data);
        $data['customer_id'] = $customer_id;

        $pass = Pass::where('pass_id', $data['pass_id'])->first();

        $data['pass'] = $pass;

        $data['amount'] = $data['razorpay_subscription_amount'] = $pass['price'];

        $data['type'] = 'pass';
        $data['pass_type'] = $pass['type'];

        $razorpay_service = new RazorpayService();

        $id = Order::maxId()+1;
        $order = new Order($data);
        $order['_id'] = $id;
        // return $order;
        $order->save();

        $create_subscription_response = $razorpay_service->createSubscription($id);

        $order['subscription_id'] = $create_subscription_response['subscription_id'];
        $order['rp_subscription_id'] = $create_subscription_response['rp_subscription_id'];
        $order['payment_type'] = 'razorpay';
        $order['order_id'] = $order['_id'];

        return ['status'=>200, 'data'=>$order];


    }

    public function passSuccess($data){
        
        $order = Order::where('_id', $data['order_id'])->first();
        $order->update(['status'=>'1']);
        return ['status'=>200, 'data'=>$order, "message"=>"Subscription successful"];

    }

}