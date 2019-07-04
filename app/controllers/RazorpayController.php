<?PHP

use App\Services\RazorpayService as RazorpayService;

class RazorpayController extends \BaseController {
    protected $razorpayService;

	public function __construct(RazorpayService $razorpayService) {
		parent::__construct();
        $this->razorpayService = $razorpayService;
    }
    
    public function createSubscription () {
        $data = Input::json()->all();
        Log::info('$data: ', [$data]);
        $response = ['status' => 400, 'data' => null, 'msg' => 'Failed'];
        if(!empty($data['pass_id'])) {
            $response = $this->razorpayService->createSubscription($data['pass_id']);
        }
        if(!empty($response)) {
            $response = ['status' => 200, 'data' => $response, 'msg' => 'Success'];
        }
        return $response;
    }

    public function storePaymentDetails() {
        $data = Input::json()->all();
        $response = ['status' => 400, 'data' => null, 'msg' => 'Failed'];
        if(!empty($data['payment_id']) && $data['order_id']) {
            $response = $this->razorpayService->storePaymentDetails($data['order_id'], $data['payment_id']);
        }
        if(!empty($response)) {
            $response = ['status' => 200, 'data' => $response, 'msg' => 'Success'];
        }
        return $response;
    }

    public function razorpayWebhooks(){
        $data = Input::json()->all();
        Log::info("webhooks data:::::::::::::::::::::::::::::::::::::::::::::::::::::::", [$data]);
        switch($data['event']){
            case "subscription.activated": $this->activated($data);break;
            case "subscription.charged": ;
            case "subscription.completed": ;
            case "subscription.pending": ;
            case "subscription.halted": ;
            case "subscription.cancelled": ;
        }
    }

    public function activated($data){
        $subs_id = $data['payload']['subscription']['entity']['id'];

        $order = Order::find('rp_payment_id', $subs_id);
        $order->rp_actived = true;

        $razorpaySubs = RazorpaySubscription::find('rp_subscription_id', $subs_id);
        $razorpaySubs->rp_activate = true;

        $order->update();
        $razorpaySubs->updated();
    }
}