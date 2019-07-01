<?PHP namespace App\Services;

use Log;
use Pass;
use App\Services\RazorpayService as RazorpayService;
use Order;
use Booktrial;

class PassService {

    public function __construct() {

    }

    public function listPasses(){

        $passList = Pass:: active()
        ->select('pass_id', 'amount', 'duaration', 'duration_type', 'type', 'credits', 'price', 'selling_price', 'premium_sessions')
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

}