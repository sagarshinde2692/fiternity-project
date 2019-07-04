<?PHP
use App\Services\PassService as PassService;
class PassController extends \BaseController {

    public function __construct(PassService $passService) {
        parent::__construct();
        $this->passService = $passService;
    }

    public function listPasses(){
        $passes = $this->passService->listPasses();

        // $passConfig = \Config::get('pass');
        // $main_header = $passConfig['main_header'];

        // foreach($passes['data'] as $key=>$value){
        //     if($value['type'] == 'trial'){
        //         array_push($passConfig['trial']['data'], $value);
        //     }
        //     else if($value['type'] == 'subscription'){
        //         array_push($passConfig['subscription']['data'], $value);
        //     }
        //     else if($value['type'] == 'unlimited'){
        //         array_push($passConfig['unlimited']['data'], $value);
        //     }
        // }

        if(empty($passes)) {
            return [
                "status" => 400,
                "data" => null,
                "msg" => "failed"
            ];
        }
        $response = [
            "status" => 200,
            "data" => $passes,
            "msg" => "success"
        ];

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

        $rules = [
            'order_id'=>'required | integer',
        ];
        
        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())), 400);
        }
    
        return $this->passService->passSuccess($data);

    }

    public function passTermsAndCondition(){
        $passTerms = \Config::get('pass.terms');
        return array("status"=> 200, "data"=> $passTerms[0], "msg"=> "success");
    }

    public function passFrequentAskedQuestion(){
        $passFaq = \Config::get('pass.question_list');
        return array("status"=> 200, "data"=> $passFaq, "msg"=> "success");
    }
}