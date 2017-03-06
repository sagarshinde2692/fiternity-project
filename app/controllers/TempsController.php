<?PHP

/**
 * ControllerName : TempsController.
 * Maintains a list of functions used for TempsController.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */
use App\Sms\CustomerSms as CustomerSms;
use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;

class TempsController extends \BaseController {

    protected $customersms;

    public function __construct(CustomerSms $customersms) {
        //parent::__construct();
        $this->customersms              =   $customersms;
        $this->contact_us_customer_number = Config::get('app.contact_us_customer_number');
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

                if(isset($data['service_id']) && $data['service_id'] != ""){
                    $temp->service_id = (int) $data['service_id'];
                }

                $temp->save();

                $data['otp'] = $temp->otp;

                $this->customersms->genericOtp($data);

                $response =  array('status' => 200,'message'=>'OTP Created Successfull','temp_id'=>$temp->_id,'sender_id'=>'FTRNTY');
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

    public function addV1(){

        try{

            $data = Input::json()->all();

            $rules = array(
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

                if(isset($data['service_id']) && $data['service_id'] != ""){
                    $temp->service_id = (int) $data['service_id'];
                }

                if(isset($data['ratecard_id']) && $data['ratecard_id'] != ""){
                    $temp->ratecard_id = (int) $data['ratecard_id'];
                }

                $temp->save();

                $data['otp'] = $temp->otp;

                $this->customersms->genericOtp($data);

                $response =  array('status' => 200,'message'=>'OTP Created Successfull','temp_id'=>$temp->_id,'sender_id'=>'FTRNTY');
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
            /*if($temp->verified == "Y"){
                return Response::json(array('status' => 400,'message' => 'Already Verified'),400);
            }else{*/

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

                if(isset($temp->service_id) && $temp->service_id != ""){

                    $service = Service::active()->find($temp->service_id);
                    $finder_id = (int)$service->finder_id;
                    
                    $booktrial_count = Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->orWhere('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
                            ->where('finder_id', '=',$finder_id)
                            ->where('type','booktrials')
                            ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                            ->count();

                    if($booktrial_count > 0){

                        $ratecard = Ratecard::where('service_id',$temp->service_id)->where('type','workout session')->first();

                        if($ratecard && count($service->workoutsessionschedules) > 0){

                            if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                                $amount = $ratecard->special_price;
                            }else{
                                $amount = $ratecard->price;
                            }

                            return Response::json(array('status' => 200,'message' => 'Already Booked Trial. Book a Workout Session starting from Rs '.$amount.'.','verified' => $verified,'token'=>$customerToken,'ratecard_id'=>(int)$ratecard->_id,'amount'=>$amount),200);
                        }

                        return Response::json(array('status' => 200,'message' => 'Already Booked Trial. Book a Workout Session starting from Rs 300.','verified' => $verified,'token'=>$customerToken,'ratecard_id'=>0,'amount'=>0),200);
                    }
                }

                return Response::json(array('status' => 200,'verified' => $verified,'token'=>$customerToken),200);
            //}
        }else{
            return Response::json(array('status' => 400,'message' => 'Not Found'),400);
        }
    }

    function verifyOtpV1($temp_id,$otp,$email="",$name=""){

        $customerToken = "";
        $jwt_token = Request::header('Authorization');

        if($jwt_token){
            $decoded = decode_customer_token();
            $customerToken = $jwt_token;
        }

        $otp = (int)$otp;
        $temp = Temp::find($temp_id);
        $fitternity_no = $this->contact_us_customer_number;

        if($temp){

            $verified = false;
            
            $customer_data = null;

            $ratecard_id = "";
            $finder_id = "";
            $amount = "";
            $cashback = new \stdClass();

            if(isset($temp->ratecard_id) && $temp->ratecard_id != ""){

                $ratecard_id = (int)$temp->ratecard_id;

                $ratecard = Ratecard::find($ratecard_id);

                if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                    $amount = $ratecard->special_price;
                }else{
                    $amount = $ratecard->price;
                }

            }

            if(isset($temp->finder_id) && $temp->finder_id != ""){
                $finder_id = (int)$temp->finder_id;
            }

            $return =  array('status' => 200,'verified' => $verified,'token'=>$customerToken,'trial_booked'=>false,'customer_data'=>$customer_data,'fitternity_no'=>$fitternity_no);

            if($temp->otp == $otp){

                $temp->verified = "Y";

                if($email != "" && $name != ""){

                    $temp->customer_name = $name;
                    $temp->customer_email = $email;

                    $data['customer_name'] = $temp['customer_name'];
                    $data['customer_email'] = $temp['customer_email'];
                    $data['customer_phone'] = $temp['customer_phone'];
                    $data['customer_id'] = autoRegisterCustomer($data);

                    $temp->customer_id = $data['customer_id'];
                }

                $temp->save();
                $verified = true;

                Customer::$withoutAppends = true;
                $customer = Customer::select('name','email','contact_no','dob','gender')->active()->where('contact_no',$temp['customer_phone'])->orderBy('_id','desc')->first();

                if($customer) {

                    if($customerToken == ""){

                        $customerToken = createCustomerToken((int)$customer->_id);
                    }

                    $customer_data = $customer->toArray();

                    $customer_data['dob'] = isset($customer_data['dob']) && $customer_data['dob'] != "" ? $customer_data['dob'] : "";
                    $customer_data['gender'] = isset($customer_data['gender']) && $customer_data['gender'] != "" ? $customer_data['gender'] : "";

                }

                $return = array('status' => 200,'verified' => $verified,'token'=>$customerToken,'trial_booked'=>false,'customer_data'=>$customer_data,'fitternity_no'=>$fitternity_no);

                if(isset($temp->service_id) && $temp->service_id != "" && $temp->action == "booktrials"){

                    $customer_phone = $temp->customer_phone;
                    $service = Service::active()->find($temp->service_id);
                    $finder_id = (int)$service->finder_id;

                    $booktrial_count = Booktrial::where('customer_phone', $customer_phone)
                        ->where('finder_id', '=',$finder_id)
                        ->where('type','booktrials')
                        ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                        ->count();

                    Log::info("booktrial_count : ".$booktrial_count);

                    if($booktrial_count > 0){

                        if($customer_data == null){

                            $booktrial = Booktrial::where('customer_phone', $customer_phone)
                                ->where('finder_id', '=',$finder_id)
                                ->where('type','booktrials')
                                ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                                ->orderBy('_id','desc')
                                ->first();

                            Customer::$withoutAppends = true;
                            $customer = Customer::select('name','email','contact_no','dob','gender')->find((int)$booktrial->customer_id);

                            if($customer) {

                                if($customerToken == ""){

                                    $customerToken = createCustomerToken((int)$customer->_id);
                                }

                                $customer_data = $customer->toArray();

                                $customer_data['dob'] = isset($customer_data['dob']) && $customer_data['dob'] != "" ? $customer_data['dob'] : "";
                                $customer_data['gender'] = isset($customer_data['gender']) && $customer_data['gender'] != "" ? $customer_data['gender'] : "";
                                $customer_data['contact_no'] = $customer_phone;

                            }
                        }

                        $return = array('workout_session_available'=>false,'customer_data'=>$customer_data,'trial_booked'=>true,'status' => 200,'message' => 'Already Booked Trial,Please Explore Other Options','verified' => $verified,'token'=>$customerToken,'ratecard_id'=>0,'amount'=>0,'fitternity_no'=>$fitternity_no);

                        $ratecard = Ratecard::where('service_id',$temp->service_id)->where('type','workout session')->first();

                        if($ratecard && count($service->workoutsessionschedules) > 0){

                            $ratecard_id = $ratecard->_id;

                            if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                                $amount = $ratecard->special_price;
                            }else{
                                $amount = $ratecard->price;
                            }

                            $return = array('workout_session_available'=>true,'customer_data'=>$customer_data,'trial_booked'=>true,'status' => 200,'message' => 'Already Booked Trial. Book a Workout Session starting from Rs '.$amount.'.','verified' => $verified,'token'=>$customerToken,'ratecard_id'=>(int)$ratecard->_id,'amount'=>(int)$amount,'fitternity_no'=>$fitternity_no);
                        }
                    }
                }

            }

            if($finder_id != "" && $amount != ""){

                $customerReward     =   new CustomerReward();
                $calculation        =   $customerReward->purchaseGame($amount,$finder_id);
                $cashback  = array(
                    'title'=>$calculation['algo']['cashback'].'% Instant Cashback on Purchase',
                    'percentage'=>$calculation['algo']['cashback'].'%',
                    'commision'=>$calculation['algo']['cashback'],
                    'calculation'=>$calculation,
                    'info'          =>  "You can only pay upto 10% of the booking amount through FitCash. \nIt is calculated basis the amount, type and duration of the purchase.  \nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet'],
                    'description'=>$calculation['description']
                );

                unset($cashback['calculation']['description']);
            }

            $return["cashback"] = $cashback;

            return Response::json($return,200);

        }else{

            return Response::json(array('status' => 400,'message' => 'Not Found'),400);
        }
    }

    function proceedWithoutOtp($temp_id){

        $temp = Temp::find($temp_id);

        if($temp){
            /*if($temp->proceed_without_otp == "Y"){
                return Response::json(array('status' => 400,'message' => 'Already Done'),400);
            }else{*/

                $temp->proceed_without_otp = "Y";
                $temp->save();

                $customer_email = $temp->customer_email;
                $customer_phone = $temp->customer_phone;

                if(isset($temp->service_id) && $temp->service_id != ""){

                    $service = Service::active()->find($temp->service_id);
                    $finder_id = (int)$service->finder_id;
                    
                    $booktrial_count = Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->orWhere('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
                            ->where('finder_id', '=',$finder_id)
                            ->where('type','booktrials')
                            ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                            ->count();

                    if($booktrial_count > 0){

                        $ratecard = Ratecard::where('service_id',$temp->service_id)->where('type','workout session')->first();

                        if($ratecard){

                            if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                                $amount = $ratecard->special_price;
                            }else{
                                $amount = $ratecard->price;
                            }

                            return Response::json(array('status' => 200,'message' => 'Already Booked Trial. Book a Workout Session starting from Rs '.$amount.'.','ratecard_id'=>(int)$ratecard->_id,'amount'=>$amount),200);
                        }

                        return Response::json(array('status' => 200,'message' => 'Already Booked Trial. Book a Workout Session starting from Rs 300.','ratecard_id'=>"",'amount'=>""),200);
                    }
                }

                return Response::json(array('status' => 200,'message' => 'Sucessfull'),200);
            //}
        }else{
            return Response::json(array('status' => 400,'message' => 'Not Found'),400);
        }
    }

    function proceedWithoutOtpV1($temp_id){

        $fitternity_no = $this->contact_us_customer_number;

        $temp = Temp::find($temp_id);

        if($temp){

            $temp->proceed_without_otp = "Y";
            $temp->save();

            $customer_data = null;
            
            Customer::$withoutAppends = true;
            $customer = Customer::select('name','email','contact_no','dob','gender')->active()->where('contact_no',$temp['customer_phone'])->orderBy('_id','desc')->first();

            if($customer) {

                $customer_data = $customer->toArray();

                $customer_data['dob'] = isset($customer_data['dob']) && $customer_data['dob'] != "" ? $customer_data['dob'] : "";
                $customer_data['gender'] = isset($customer_data['gender']) && $customer_data['gender'] != "" ? $customer_data['gender'] : "";

            }
            
            if(isset($temp->service_id) && $temp->service_id != "" && $temp->action == "booktrials"){

                $customer_phone = $temp->customer_phone;
                $service = Service::active()->find($temp->service_id);
                $finder_id = (int)$service->finder_id;

                $booktrial_count = Booktrial::where('customer_phone', $customer_phone)
                    ->where('finder_id', '=',$finder_id)
                    ->where('type','booktrials')
                    ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                    ->count();

                if($booktrial_count > 0){

                    if($customer_data == null){

                        $booktrial = Booktrial::where('customer_phone', $customer_phone)
                            ->where('finder_id', '=',$finder_id)
                            ->where('type','booktrials')
                            ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                            ->orderBy('_id','desc')
                            ->first();

                        Customer::$withoutAppends = true;
                        $customer = Customer::select('name','email','contact_no','dob','gender')->find((int)$booktrial->customer_id);

                        if($customer) {

                            $customer_data = $customer->toArray();

                            $customer_data['dob'] = isset($customer_data['dob']) && $customer_data['dob'] != "" ? $customer_data['dob'] : "";
                            $customer_data['gender'] = isset($customer_data['gender']) && $customer_data['gender'] != "" ? $customer_data['gender'] : "";
                            $customer_data['contact_no'] = $customer_phone;
                        }
                    }

                    $ratecard = Ratecard::where('service_id',$temp->service_id)->where('type','workout session')->first();

                    if($ratecard && count($service->workoutsessionschedules) > 0){

                        if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                            $amount = $ratecard->special_price;
                        }else{
                            $amount = $ratecard->price;
                        }

                        return Response::json(array('workout_session_available'=>true,'customer_data'=>$customer_data,'trial_booked'=>true,'status' => 200,'message' => 'Already Booked Trial. Book a Workout Session starting from Rs '.$amount.'.','ratecard_id'=>(int)$ratecard->_id,'amount'=>(int)$amount,'fitternity_no'=>$fitternity_no),200);
                    }

                    return Response::json(array('workout_session_available'=>false,'customer_data'=>$customer_data,'trial_booked'=>true,'status' => 200,'message' => 'Already Booked Trial,Please Explore Other Options','ratecard_id'=>0,'amount'=>0,'fitternity_no'=>$fitternity_no),200);
                }
            }

            return Response::json(array('customer_data'=>$customer_data,'trial_booked'=>false,'status' => 200,'message' => 'Sucessfull','fitternity_no'=>$fitternity_no),200);

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

            return Response::json(array('status' => 200,'attempt' => $temp->attempt,'sender_id'=>'FTRNTY'),200);
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
