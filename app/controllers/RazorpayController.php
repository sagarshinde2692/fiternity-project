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
}