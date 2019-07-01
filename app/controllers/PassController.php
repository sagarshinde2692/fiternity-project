<?PHP
use App\Services\PassService as PassService;
class PassController extends \BaseController {

    public function __construct(PassService $passService) {
        parent::__construct();
        $this->passService = $passService;
    }

    public function listPasses(){
        return $this->passService->listPasses();
    }

    public function passCapture(){
        
        $data = Input::json()->all();

        $rules = [
            'pass_id'=>'required | integer',
            'customer_name'=>'required',
            'customer_email'=>'required',
            'customer_phone'=>'required',
        ];

        
        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())), 400);
        }
        return $order_creation_response = $this->passService->passCapture($data);

        if(empty($order_creation_response['status']) || $order_creation_response['status'] != 200){
            return ['status'=>400, 'message'=>'Error while creating order.'];
        }
        



    }

}