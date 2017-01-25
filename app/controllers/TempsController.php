<?PHP

/**
 * ControllerName : TempsController.
 * Maintains a list of functions used for TempsController.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */
use App\Sms\CustomerSms as CustomerSms;

class TempsController extends \BaseController {

    protected $customersms;

    public function __construct(CustomerSms $customersms) {
        //parent::__construct();
        $this->customersms              =   $customersms;
    }

    public function errorMessage($errors){

        $errors = json_decode(json_encode($errors));
        $message = array();
        foreach ($errors as $key => $value) {
            $message[$key] = $value[0];
    }

        $message = implode(',', array_values($message));

        return $message;
    }

    public function addWeb(){
        try{
            $data = Input::json()->all();
            $temp = new Temp($data);
            $temp->save();
            $response =  array('status' => 200,'message'=>'Added Successfull');
        }catch (Exception $e) {
            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );
            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            
            Log::error($e);       
        }
        return Response::json($response,$response['status']); 
        
    }

    public function add(){

        try{

            $data = Input::json()->all();

            $rules = array(
                'customer_name' => 'required|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'required|max:15',
                'action'   =>   'required'
            );

            $validator = Validator::make($data,$rules);

            if ($validator->fails()) {

                return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);

            }else{

                $temp = new Temp($data);
                $temp->otp = $this->generateOtp();
                $temp->attempt = 1;
                $temp->verified = "N";
                $temp->proceed_without_otp = "N";

                if(isset($data['finder_id']) && $data['finder_id'] != ""){
                   $temp->finder_id = (int) $data['finder_id'];
                }

                $temp->save();

                $data['otp'] = $temp->otp;

                $this->customersms->genericOtp($data);

                $response =  array('status' => 200,'message'=>'OTP Created Successfull','temp_id'=>$temp->_id);
            }

        }catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            
            Log::error($e);       
        }

        return Response::json($response,$response['status']); 
        
    }


    function verifyNumber($number){

        $verified = false;

        $temp = Temp::where('customer_phone','LIKE','%'.substr($number, -8).'%')->where('verified','Y')->first();

        if($temp){
            $verified = true;
        }

        return Response::json(array('status' => 200,'verified' => $verified),200);

    }

    function verifyOtp($temp_id,$otp,$email="",$name=""){

        $otp = (int)$otp;
        $temp = Temp::find($temp_id);

        if($temp){
            if($temp->verified == "Y"){
                return Response::json(array('status' => 400,'message' => 'Already Verified'),400);
            }else{

                $verified = false;
                $customerToken = "";
                if($temp->otp == $otp){
                    $temp->verified = "Y";
                    if($email != "" && $name != ""){
                        $temp->customer_name = $name;
                        $temp->customer_email = $email;
                    }
                    $temp->save();
                    $verified = true;

                    $data['customer_name'] = $temp['customer_name'];
                    $data['customer_email'] = $temp['customer_email'];
                    $data['customer_phone'] = $temp['customer_phone'];
                    $data['customer_id'] = autoRegisterCustomer($data);
                    $customerToken = createCustomerToken($data['customer_id']);
                }

                $customer_email = $temp->customer_email;
                $customer_phone = $temp->customer_phone;

                if(isset($temp->finder_id) && $temp->finder_id != ""){

                    $booktrial_count = Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->orWhere('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
                            ->where('finder_id', '=', (int)$temp->finder_id)
                            ->where('type','booktrials')
                            ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                            ->count();

                    if($booktrial_count > 0){
                        return Response::json(array('status' => 400,'message' => 'Already Booked Trial'),400);
                    }
                }

                return Response::json(array('status' => 200,'verified' => $verified,'token'=>$customerToken),200);
            }
        }else{
            return Response::json(array('status' => 400,'message' => 'Not Found'),400);
        }
    }

    function proceedWithoutOtp($temp_id){

        $temp = Temp::find($temp_id);

        if($temp){
            if($temp->proceed_without_otp == "Y"){
                return Response::json(array('status' => 400,'message' => 'Already Done'),400);
            }else{

                $temp->proceed_without_otp = "Y";
                $temp->save();

                return Response::json(array('status' => 200,'message' => 'Sucessfull'),200);
            }
        }else{
            return Response::json(array('status' => 400,'message' => 'Not Found'),400);
        }
    }

    function regenerateOtp($temp_id){

        $temp = Temp::find($temp_id);

        if($temp){

            $temp->attempt = $temp->attempt + 1;
            $temp->save();
        
            if($temp->attempt >= 1 && $temp->attempt <= 3){

                $data = $temp->toArray();
                $this->customersms->genericOtp($data);
            }

            return Response::json(array('status' => 200,'attempt' => $temp->attempt),200);
        }else{
            return Response::json(array('status' => 400,'message' => 'Not Found'),400);
        }
    }

    function generateOtp($length = 4)
    {      
        $characters = '0123456789';
        $result = '';
        $charactersLength = strlen($characters);

        for ($p = 0; $p < $length; $p++)
        {
            $result .= $characters[rand(0, $charactersLength - 1)];
        }

        return $result;
    }

    public function delete($mobile){

        if(isset($mobile) && $mobile != '')
        {

            $temp = Temp::where('mobile',$mobile)->delete();

            $response  =   array('status' => 200,'message' => "Deleted Successfull");

        }else{

            $response  =   array('status' => 400,'message' => "mobile is required or empty");
        }

        
        return Response::json($response,$response['status']); 

    }

}
