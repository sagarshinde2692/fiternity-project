<?PHP
use App\Services\PassService as PassService;
class PassController extends \BaseController {

    public function __construct(PassService $passService) {
        parent::__construct();
        $this->passService = $passService;
    }

    public function listPasses(){
        $passes = $this->passService->listPasses();

        $passConfig = \Config::get('pass');
        $trial_header = $passConfig['trial_header'];
        $main_header = $passConfig['main_header'];
        $subscription_header = $passConfig['subscription_header'];
        $unlimited_header = $passConfig['unlimited_header'];


        $response =[
            "header" => $main_header,
            "data" => [
            ]
        ];

        foreach($passes['data'] as $key=>$value){
            if($value['type'] == 'trial'){
                array_push($response['data'],[
                    "header" => $trial_header, 
                    "data" => $value
                ]);
            }
            else if($value['type'] == 'subscription'){
                array_push($response['data'],[
                    "header" => $subscription_header, 
                    "data" => $value
                ]);
            }
            else if($value['type'] == 'unlimited'){
                array_push($response['data'],[
                    "header" => $unlimited_header, 
                    "data" => $value
                ]);
            }
        }
        return $response;
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

    public function passSuccess(){
        
        $data = Input::json()->all();
        return $order_success_response = $this->passService->passSuccess($data);
        return $order_success_response;

    }

}