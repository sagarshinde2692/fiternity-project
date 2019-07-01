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
        ->select('pass_id', 'amount', 'duaration', 'duration_type', 'type', 'credits')
        ->get();

        return array("status" => 200, "data"=> $passList, "msg" => "success");
    }

    public function passCapture($data){

        $pass = Pass::where('pass_id', $data['pass_id'])->first();

        $data['amount'] = $data['razorpay_subscription_amount'] = $pass['amount'];

        $data['type'] = 'pass';
        $data['pass_type'] = $pass['type'];

        $razorpay_service = new RazorpayService();

        $id = Order::maxId()+1;
        $order = new Order($data);
        $order['_id'] = $id;
        $order->save();

        $create_subscription_response = $razorpay_service->createSubscription($id);

        $order['subscription_id'] = $create_subscription_response['subscription_id'];
        $order['payment_type'] = 'razorpay';
        $order['order_id'] = $order['_id'];

        return ['status'=>200, 'data'=>$order];


    }




}