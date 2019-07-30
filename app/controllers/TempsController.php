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
use App\Sms\FinderSms as FinderSms;
use App\Services\Sidekiq as Sidekiq;
use App\Services\RelianceService as RelianceService;
use App\Mailers\FinderMailer as FinderMailer;

class TempsController extends \BaseController {

    protected $customersms;
    protected $utilities;
    protected $findersms;

    public function __construct(
        CustomerSms $customersms,
        Utilities $utilities,
        FinderSms $findersms,
        Sidekiq $sidekiq,
        FinderMailer $findermailer,
        RelianceService $relianceService
    ) {
        //parent::__construct();
        $this->findersms            =   $findersms;
        $this->customersms              =   $customersms;
        $this->contact_us_customer_number = Config::get('app.contact_us_customer_number');
        $this->appOfferDiscount 				= Config::get('app.app.discount');
        $this->appOfferExcludedVendors 				= Config::get('app.app.discount_excluded_vendors');
        $this->utilities = $utilities;
        $this->sidekiq              =   $sidekiq;
        $this->findermailer         =   $findermailer;
        $this->relianceService         =   $relianceService;

        $this->vendor_token = false;

        $this->kiosk_app_version = false;

        $vendor_token = Request::header('Authorization-Vendor');

        if($vendor_token){

            $this->vendor_token = true;

            $this->kiosk_app_version = (float)Request::header('App-Version');
        
        }

        $this->error_status = ($this->vendor_token) ? 200 : 400;
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

                return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),$this->error_status);

            }else{
				
            	$temp = new Temp($data);
            	$temp->otp = $this->generateOtp();
            	$temp->attempt = 1;
            	$temp->verified = "N";
            	$temp->proceed_without_otp = "N";
            	$data['otp'] = $temp->otp;
            	$sendOtp=true;
            	if(!empty($data['action'])&&$data['action']=='starter_pack')
            	{
            		$cust=Customer::where("email","=",$data['customer_email'])->orWhere('contact_no', $data['customer_phone'])->first();
            		if(!empty($cust))
            		{
	            			$sendOtp=false;
		            		$response =  array('status' => 400,'message'=>'User already Present.');
            		}
            		
            	}
            	if($sendOtp)
            	{
            		$temp->save();
            		$this->customersms->genericOtp($data);
            		$response =  array('status' => 200,'message'=>'OTP Created Successfull','temp_id'=>$temp->_id);
            	}
            	
            }

        }catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);

            return Response::json($response,$this->error_status);
            
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

                return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),$this->error_status);

            }else{

                $temp = new Temp($data);
                $temp->otp = $this->generateOtp();
                $temp->attempt = 1;
                $temp->verified = "N";
                $temp->proceed_without_otp = "N";
                $temp->source = "website";
                $data['otp'] = $temp->otp;
                $sendOtp=true;

                if(isset($data['finder_id']) && $data['finder_id'] != ""){
                    $temp->finder_id = (int) $data['finder_id'];
                }

                if(isset($data['service_id']) && $data['service_id'] != ""){

                    $temp->service_id = (int) $data['service_id'];

                    $trial_type = [
                        "booktrail",
                        "booktrial",
                        "typeofsession",
                        "booktrials"
                    ];

                    if(in_array($data['action'],$trial_type) && isset($data['service_id']) && $data['service_id'] != "" && !isset($data['ratecard_id'])){

                        $ratecard = Ratecard::where('type','trial')->where('service_id',(int)$data['service_id'])->orderBy('id','desc')->first();

                        if($ratecard){

                            if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
                                $temp->price = (int)$ratecard['special_price'];
                            }else{
                                $temp->price = (int)$ratecard['price'];
                            }
                        }
                    }
                }

                if(isset($data['ratecard_id']) && $data['ratecard_id'] != ""){
                    
                    $temp->ratecard_id = (int) $data['ratecard_id'];

                    $ratecard = Ratecard::find((int) $data['ratecard_id']);

                    if($ratecard){

                        $temp->finder_id = (int) $ratecard->finder_id;
                        $temp->service_id = (int) $ratecard->service_id;

                        if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
                            $temp->price = (int)$ratecard['special_price'];
                        }else{
                            $temp->price = (int)$ratecard['price'];
                        }

                    }

                }

                if(isset($_GET['device_type']) && $_GET['device_type'] != ""){
                    $temp->source = $_GET['device_type'];
                }

                if(isset($_GET['app_version']) && $_GET['app_version'] != ""){
                    $temp->version = $_GET['app_version'];
                }

                if($this->vendor_token){

                    $decodeKioskVendorToken = decodeKioskVendorToken();

                    $vendor = $decodeKioskVendorToken->vendor;

                    $temp->finder_id = (int)$vendor->_id;

                    $temp->serial_number = Request::header('Device-Serial');

                    $temp->source = "kiosk";
                }
                
                
                if(!empty($data['action'])&&$data['action']=='starter_pack')
                {
                	
                	$rules = array(
                			'customer_phone' => 'required|max:15',
                			'action'   =>   'required',
                			'gender'   =>   'required'
                	);
                	
                	$validator = Validator::make($data,$rules);
                	
                	if ($validator->fails()) {
                		
                		return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),$this->error_status);
                	}
                	else 
                	{
	                	$cust=Customer::where("email","=",$data['customer_email'])->orWhere('contact_no', $data['customer_phone'])->first();
	                	if(!empty($cust))
	                	{
	                		$sendOtp=false;
	                		$response =  array('status' => 400,'message'=>'User already Present.');
	                	}                		
                	}
                }
                if($sendOtp)
                {
                	$temp->save();
                	$this->customersms->genericOtp($data);
                	$response =  array('status' => 200,'message'=>'OTP Created Successfully','temp_id'=>$temp->_id,'sender_id'=>'FTRNTY');
                }
            }

        }catch (Exception $e) {

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);

            return Response::json($response,$this->error_status);

            Log::error($e);
        }

        return Response::json($response,$this->vendor_token ? 200 : $response['status']);

    }


    function verifyNumber($number){

        $verified = false;

        $temp = Temp::where('customer_phone', substr($number, -10))->where('verified','Y')->first();

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
                    if(!empty($temp['gender']))
                    $data['gender'] = $temp['gender'];
                    $data['customer_id'] = autoRegisterCustomer($data);
                    
                    $customerToken = createCustomerToken($data['customer_id']);
                }

                return Response::json(array('status' => 200,'verified' => $verified,'token'=>$customerToken),200);
            }
        }else{
            return Response::json(array('status' => 400,'message' => 'Not Found'),400);
        }
    }
    function getAddWalletArray($data=array())
    {
    	
    	$req = [];
    	$req['customer_id'] = isset($data['customer_id'])&&$data['customer_id']!=""?$data['customer_id']:"";;
    	$req['amount'] = isset($data['amount'])&&$data['amount']!=""?$data['amount']:"";
    	$req['entry'] = "credit";
    	$req['type'] = "FITCASHPLUS";
    	$req['amount_fitcash_plus'] = isset($data['amount'])&&$data['amount']!=""?$data['amount']:"";
    	$req['description'] = !empty($data['description'])?$data['description']:""; 
    	$req["validity"] = time()+(86400*60);
    	$req['for'] = isset($data['for'])&&$data['for']!=""?$data['for']:"";
    	return $this->utilities->walletTransaction($req);
    }

    function verifyOtpV1($temp_id,$otp,$email="",$name=""){
        Log::info("verifyOtpV1");
        $customerToken = "";
        $jwt_token = Request::header('Authorization');

        if($jwt_token){
            $decoded = decode_customer_token();
            $customerToken = $jwt_token;
            $customer_id = (int)$decoded->customer->_id;
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
            $cashback = null;
            if(empty($customer_id)){
                $customer_id = "";
            }

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

            $return =  array('status' => 200,'verified' => $verified,'token'=>$customerToken,'trial_booked'=>false,'customer_data'=>$customer_data,'fitternity_no'=>$fitternity_no,"message"=>'Incorrect OTP');

            if($this->vendor_token){
                $return['status'] = 400;
            }

            if($temp->otp == $otp){

                $temp->verified = "Y";

                if(isset($temp['customer_email']) && $temp['customer_email'] != ""){
                    $email = $temp['customer_email'];
                }

                if(isset($temp['customer_name']) && $temp['customer_name'] != ""){
                    $name = $temp['customer_name'];
                }

                if($email != "" && $name != ""){

                    $temp->customer_name = $name;
                    $temp->customer_email = $email;

                    $data['customer_name'] = $temp['customer_name'];
                    $data['customer_email'] = $temp['customer_email'];
                    $data['customer_phone'] = $temp['customer_phone'];
                    $data['customer_id'] = autoRegisterCustomer($data);

                    setVerifiedContact($data['customer_id'], $data['customer_phone']);

                    $customer_id = $temp->customer_id = $data['customer_id'];
                }

                // $customer_from_token = $this->utilities->getCustomerFromToken();

                // if(!empty($customer_from_token['_id'])){
                    
                //     $customer_id = $customer_from_token['_id'];
                
                // }

                $temp->save();
                $verified = true;
                Customer::$withoutAppends = true;

                if($customer_id == ""){

                    $customer = Customer::select('name','email','contact_no','dob','gender','corporate_id')->active()->where('contact_no',$temp['customer_phone'])->orderBy('_id','desc')->first();
                }else{

                    $customer = Customer::find($customer_id,['name','email','contact_no','dob','gender'.'freshchat_restore_id','corporate_id']);
                }
                
                if($customer) {

                    $customer->verified = true;
                    
                    $customer->contact_no = substr($temp['customer_phone'], -10);
                    
                    $relCust = $this->relianceService->getRelianceCustomerDetails($customer->email);
                    $emailList = $this->relianceService->getRelianceCustomerEmailList();
                    if(in_array($customer->email, $emailList) || $this->relianceService->isRelianceSAPEmailId($customer['email'])) {
                        $customer->corporate_id = Config::get('health_config.reliance.corporate_id');
                        if($relCust) {
                            if(!empty($relCust['designation'])) {
                                $customer->reliance_designation = $relCust['designation'];
                            }
                            if(!empty($relCust['department'])) {
                                $customer->reliance_department = $relCust['department'];
                            }
                            if(!empty($relCust['location'])) {
                                $customer->reliance_location = strtolower($relCust['location']);
                            }
                            if(!empty($relCust['city'])) {
                                $customer->reliance_city = strtolower($relCust['city']);
                            }
                        }
                    }

                    $customer->update();

                    if($customerToken == "" && isset($customer->email)){

                        $customerToken = createCustomerToken((int)$customer->_id);
                    }

                    $customer_data = $customer->toArray();

                    $customer_data['dob'] = isset($customer_data['dob']) && $customer_data['dob'] != "" ? $customer_data['dob'] : "";
                    $customer_data['gender'] = isset($customer_data['gender']) && $customer_data['gender'] != "" ? $customer_data['gender'] : "";
                    $customer_id = (int)$customer->_id;

                    if(isset($temp->customer_email) && $temp->customer_email != ""){
                        $customer_data['email'] = $temp->customer_email;
                    }

                    if(isset($temp->customer_name) && $temp->customer_name != ""){
                        $customer_data['name'] = $temp->customer_name;
                    }

                    if(isset($temp->gender) && $temp->gender != ""){
                        $customer_data['gender'] = $temp->gender;
                    }

                    $customer_data['customerToken'] = $customerToken;

                    if($temp['source'] == 'kiosk' && $this->kiosk_app_version &&  $this->kiosk_app_version >= 1.08){

                        $customer_data = [$customer_data];
                    }

                }

                if($temp['source'] == 'kiosk' && $this->kiosk_app_version &&  $this->kiosk_app_version == 1.07){

                    $customer_data = $this->getAllCustomersByPhone($temp);

                }
                
                if ($temp['source'] != 'kiosk'){
                    
                    $all_accounts = $this->getAllCustomersByPhone($temp);
                    if(!empty($all_accounts)){
                        $customer_data = $all_accounts[0];
                        $customerToken = $customer_data['customerToken'];
                    }
                    $customer_data['all_accounts'] = $all_accounts;
                    if($all_accounts==-1){
                        $customer_data = [];
                        $customer_data['all_accounts'] = [];
                        $return = array('status' => 200,'verified' => $verified,'token'=>$customerToken,'trial_booked'=>false,'customer_data'=>$customer_data,'fitternity_no'=>$fitternity_no, 'message'=>'Successfully Verified', 'cashback' => null, 'popup' => null);        
                        return Response::json($return,200);
                    }
                }
                
                $return = array('status' => 200,'verified' => $verified,'token'=>$customerToken,'trial_booked'=>false,'customer_data'=>$customer_data,'fitternity_no'=>$fitternity_no, 'message'=>'Successfully Verified');

                if($temp->action == "booktrials"){
                    
                    $customer_phone = $temp->customer_phone;

                    $customer_email = null;

                    if(isset($temp->customer_email) && $temp->customer_email != ""){
                        $customer_email = $temp->customer_email;
                    }

                    if(isset($temp->service_id) && $temp->service_id != ""){
                        $service = Service::active()->find($temp->service_id);
                        $finder_id = (int)$service->finder_id;
                    }

                    if(isset($temp->finder_id) && $temp->finder_id != ""){
                        $finder_id = (int)$temp->finder_id;
                    }

                    $alreadyBookedTrials = $this->utilities->checkExistingTrialWithFinder($customer_email,$customer_phone,$finder_id);

                    if (count($alreadyBookedTrials) > 0){
                        
                        if($customer_data == null){

                            $booktrial = Booktrial::where('customer_phone', $customer_phone)
                                ->where('finder_id', '=',$finder_id)
                                ->where('type','booktrials')
                                ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                                ->orderBy('_id','desc')
                                ->first();

                            if(!$booktrial && $customer_email != null){

                                $booktrial = Booktrial::where('customer_email', $customer_email)
                                ->where('finder_id', '=',$finder_id)
                                ->where('type','booktrials')
                                ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                                ->orderBy('_id','desc')
                                ->first();
                            }

                            Customer::$withoutAppends = true;
                            
                            $customer = Customer::select('name','email','contact_no','dob','gender','corporate_id')->find((int)$booktrial->customer_id);

                            if($customer) {

                                if($customerToken == ""){

                                    $customerToken = createCustomerToken((int)$customer->_id);
                                }

                                $customer_data = $customer->toArray();

                                $customer_data['dob'] = isset($customer_data['dob']) && $customer_data['dob'] != "" ? $customer_data['dob'] : "";
                                $customer_data['gender'] = isset($customer_data['gender']) && $customer_data['gender'] != "" ? $customer_data['gender'] : "";
                                $customer_data['contact_no'] = $customer_phone;
                                $customer_id = (int)$customer->_id;

                                if(isset($temp->customer_email) && $temp->customer_email != ""){
                                    $customer_data['email'] = $temp->customer_email;
                                }

                                if(isset($temp->customer_name) && $temp->customer_name != ""){
                                    $customer_data['name'] = $temp->customer_name;
                                }

                                if(isset($temp->gender) && $temp->gender != ""){
                                    $customer_data['gender'] = $temp->gender;
                                }

                                $customer_data['customerToken'] = $customerToken;

                                if($temp['source'] == 'kiosk' && $this->kiosk_app_version && $this->kiosk_app_version >= 1.08){

                                    $customer_data = [$customer_data];
                                }

                            }
                        }

                        $return = array('workout_session_available'=>false,'customer_data'=>$customer_data,'trial_booked'=>true,'status' => 200,'message' => 'Already Booked Trial,Please Explore Other Options','verified' => $verified,'token'=>$customerToken,'ratecard_id'=>0,'amount'=>0,'fitternity_no'=>$fitternity_no);

                        $workout_session_available_count = Ratecard::where('finder_id',$finder_id)->where('type','workout session')->count();

                        if($workout_session_available_count > 0){

                            $return = array('workout_session_available'=>true,'customer_data'=>$customer_data,'trial_booked'=>true,'status' => 200,'message' => 'Already Booked Trial. Book a Workout Session','verified' => $verified,'token'=>$customerToken,'ratecard_id'=>0,'amount'=>0,'fitternity_no'=>$fitternity_no);
                        }

                        if(isset($service) && $service){

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

                if(in_array($temp->action,['locate_trial','prebook'])){

                    $customer_phone = $temp->customer_phone;

                    $customer_email = null;

                    if(isset($temp->customer_email) && $temp->customer_email != ""){
                        $customer_email = $temp->customer_email;
                    }

                    $message = "Sorry! We could not locate your booking. Want to book an instant session instead";

                    $return = array('customer_data'=>$customer_data,'locate_trial'=>false,'status' => 200,'message' => $message,'verified' => $verified,'token'=>$customerToken,'trial_booked'=>false);

                    $decodeKioskVendorToken = decodeKioskVendorToken();

                    $vendor = $decodeKioskVendorToken->vendor;

                    $finder_id = (int)$vendor->_id;

                    Booktrial::$withoutAppends = true;

                    $booktrial = Booktrial::where('customer_phone', substr($temp->customer_phone, -10))
                                ->where('finder_id', '=',$finder_id)
                                // ->where('type','booktrials')
                                ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                                ->where('schedule_date_time','>',new MongoDate(strtotime(date('Y-m-d 00:00:00'))))
                                ->where('schedule_date_time','<',new MongoDate(strtotime(date('Y-m-d 23:59:59'))))
                                ->orderBy('_id','desc')
                                ->first();

                    if($booktrial){

                        if(isset($booktrial['customer_sms_after24hour']) && $booktrial['customer_sms_after24hour'] != ""){
                         
                            $booktrial->unset('customer_sms_after24hour');
                         
                            $this->sidekiq->delete($booktrial['customer_sms_after24hour']);
                        
                        }

                        $message = "Hi ".ucwords($booktrial['customer_name']).", your booking at ".ucwords($booktrial['finder_name'])." for ".strtoupper($booktrial['schedule_slot_start_time'])." on ".date('D, d M Y',strtotime($booktrial['schedule_date']))." has been successfully located";

                        $kiosk_form_url = Config::get('app.website').'/kiosktrialform?booktrial_id='.$booktrial['_id'];
   
                        Customer::$withoutAppends = true;
                        $customer = Customer::select('name','email','contact_no','dob','gender','corporate_id')->find((int)$booktrial->customer_id);
                        
                        if($customer) {

                            if($customerToken == ""){

                                $customerToken = createCustomerToken((int)$customer->_id);
                            }

                            $customer_data = $customer->toArray();

                            $customer_data['dob'] = isset($customer_data['dob']) && $customer_data['dob'] != "" ? $customer_data['dob'] : "";
                            $customer_data['gender'] = isset($customer_data['gender']) && $customer_data['gender'] != "" ? $customer_data['gender'] : "";
                            $customer_data['contact_no'] = $temp->customer_phone;
                            $customer_id = (int)$customer->_id;

                            if(isset($temp->customer_email) && $temp->customer_email != ""){
                                $customer_data['email'] = $temp->customer_email;
                            }

                            if(isset($temp->customer_name) && $temp->customer_name != ""){
                                $customer_data['name'] = $temp->customer_name;
                            }

                            if(isset($temp->gender) && $temp->gender != ""){
                                $customer_data['gender'] = $temp->gender;
                            }

                            $customer_data['customerToken'] = $customerToken;

                            if($temp['source'] == 'kiosk' && $this->kiosk_app_version && $this->kiosk_app_version >= 1.08){

                                $customer_data = [$customer_data];
                            }
                        }

                        if(isset($booktrial->vendor_kiosk) && $booktrial->vendor_kiosk && $booktrial->type == "booktrials" && !isset($booktrial->post_trial_status_updated_by_kiosk)){

                            $req = array(
                                "customer_id"=>$booktrial['customer_id'],
                                "trial_id"=>$booktrial['_id'],
                                "finder_id"=>$booktrial['finder_id'],
                                "for"=>"locate_trial",
                                "amount"=> 250,
                                "amount_fitcash" => 0,
                                "amount_fitcash_plus" => 250,
                                "type"=>'CREDIT',
                                'entry'=>'credit',
                                'validity'=>time()+(86400*7),
                                'description'=>"Added FitCash+ on Trial Attendance, Expires On : ".date('d-m-Y',time()+(86400*7))
                            );

                            $this->utilities->walletTransaction($req);

                        }

                        if($temp['source'] == 'kiosk' && $this->kiosk_app_version &&  $this->kiosk_app_version == 1.07){

                            $customer_data = $this->getAllCustomersByPhone($temp);
                        }

                        $return = [
                            'customer_data'=> $customer_data,
                            'locate_trial'=>true,
                            'status' => 200,
                            'message' => $message,
                            'verified' => $verified,
                            'token'=>$customerToken,
                            'booktrial_id'=> (int)$booktrial['_id'],
                            'kiosk_form_url'=>$kiosk_form_url
                        ];

                        $booktrial->post_trial_status = 'attended';
                        $booktrial->post_trial_initail_status = 'interested';
                        $booktrial->post_trial_status_updated_by_kiosk = time();
                        $booktrial->post_trial_status_date = time();
                        $booktrial->update();

                        $data = [
                            'booked_locate'=>'locate'
                        ];

                        $return = array_merge($return,$this->utilities->trialBookedLocateScreen($data));

                        return Response::json($return,200);

                    }

                    $alreadyBookedTrials = $this->utilities->checkExistingTrialWithFinder($customer_email,$customer_phone,$finder_id);

                    if (count($alreadyBookedTrials) > 0){
                        
                        $return = array('workout_session_available'=>false,'customer_data'=>$customer_data,'trial_booked'=>true,'status' => 200,'message' => 'Sorry! We could not locate your booking, Would you like to book a session now?','verified' => $verified,'token'=>$customerToken,'ratecard_id'=>0,'amount'=>0,'fitternity_no'=>$fitternity_no);

                        $workout_session_available_count = Ratecard::where('finder_id',$finder_id)->where('type','workout session')->count();

                        if($workout_session_available_count > 0){

                            $return = array('workout_session_available'=>true,'customer_data'=>$customer_data,'trial_booked'=>true,'status' => 200,'message' => 'Sorry! We could not locate your booking, Would you like to book a session now?','verified' => $verified,'token'=>$customerToken,'ratecard_id'=>0,'amount'=>0,'fitternity_no'=>$fitternity_no);
                        }
                    }

                    return Response::json($return,200);

                }


                if(in_array($temp->action,['locate_membership'])){

                    $message = 'Sorry! Cannot locate your membership';

                    $return = array('customer_data'=>$customer_data,'locate_membership'=>false,'status' => 200,'message' => $message,'verified' => $verified,'token'=>$customerToken);

                    $decodeKioskVendorToken = decodeKioskVendorToken();

                    $vendor = $decodeKioskVendorToken->vendor;

                    $finder_id = (int)$vendor->_id;

                    Order::$withoutAppends = true;

                    $order = Order::active()
                                ->where('customer_phone', substr($temp->customer_phone, -10))
                                ->where('finder_id', '=',$finder_id)
                                ->where('type','memberships')
                                ->orderBy('_id','desc')
                                ->first();

                    if($order){

                        $data = [];

                        $preferred_starting_date = date('Y-m-d 00:00:00',time());

                        $data['start_date'] = $preferred_starting_date;
                        $data['preferred_starting_date'] = $preferred_starting_date;
                        $data['end_date'] = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']."+ ".($order->duration_day-1)." days"));
                        $data['locate_membership_date'] = time();

                        $order->update($data);

                        $message = "You are good to go your ".ucwords($order['service_duration'])." ".ucwords($order['service_name'])." membership has been activated";
  
                        Customer::$withoutAppends = true;

                        $customer = Customer::select('name','email','contact_no','dob','gender','corporate_id')->find((int)$order->customer_id);
                        
                        if($customer) {

                            if($customerToken == ""){

                                $customerToken = createCustomerToken((int)$customer->_id);
                            }

                            $customer_data = $customer->toArray();

                            $customer_data['dob'] = isset($customer_data['dob']) && $customer_data['dob'] != "" ? $customer_data['dob'] : "";
                            $customer_data['gender'] = isset($customer_data['gender']) && $customer_data['gender'] != "" ? $customer_data['gender'] : "";
                            $customer_data['contact_no'] = $temp->customer_phone;
                            $customer_id = (int)$customer->_id;

                            if(isset($temp->customer_email) && $temp->customer_email != ""){
                                $customer_data['email'] = $temp->customer_email;
                            }

                            if(isset($temp->customer_name) && $temp->customer_name != ""){
                                $customer_data['name'] = $temp->customer_name;
                            }

                            if(isset($temp->gender) && $temp->gender != ""){
                                $customer_data['gender'] = $temp->gender;
                            }

                            $customer_data['customerToken'] = $customerToken;

                            if($temp['source'] == 'kiosk' && $this->kiosk_app_version && $this->kiosk_app_version >= 1.08){

                                $customer_data = [$customer_data];
                            }
                            
                        }

                        $return = [
                            'customer_data'=>$customer_data,
                            'locate_membership'=>true,
                            'status' => 200,
                            'message' => $message,
                            'verified' => $verified,
                            'token'=>$customerToken,
                            'booktrial_id'=> (int)$order['_id']
                        ];

                        $order_data = $order->toArray();

                        $order_data['membership_locate'] = 'locate';

                        $return = array_merge($return,$this->utilities->membershipBookedLocateScreen($order_data));

                    }
                     
                    return Response::json($return,200);

                }

                if($temp->action == "routed_verification" && $customer_id != ""){

                    $addCapturedata = [
                        "customer_name"=>$temp->customer_name,
                        "customer_email"=>$temp->customer_email,
                        "customer_phone"=>$temp->customer_phone,
                        "capture_type"=>"routed_verification",
                        "gender"=>$temp->gender,
                        "customer_id"=>(int)$customer_id
                    ];

                    $this->utilities->addCapture($addCapturedata);

                    $order = Order::active()->where('customer_email',$temp->customer_email)->where('routed_order','1')->first();

                    $wallet = Wallet::where('customer_id',(int)$customer_id)->where('for','routed_verification')->first();

                    if($order && !$wallet){

                        $amount = Config::get('app.routed_order_fitcash');

                        $walletData = array(
                            "customer_id"=>(int)$customer_id,
                            "amount"=> $amount,
                            "amount_fitcash" => 0,
                            "amount_fitcash_plus" => $amount,
                            "type"=>'FITCASHPLUS',
                            "description" => "Added FitCash+ as Fitternity Bonus, Expires On : ".date('d-m-Y',time()+(86400*30)),
                            "validity" => time()+(86400*30),
                            'entry'=>'credit',
                            "order_id"=>$order->_id,
                            "for"=>"routed_verification"
                        );

                        $this->utilities->walletTransaction($walletData);

                        $customerReward     =   new CustomerReward();

                        $myRewardData = [
                            "reward_ids"=>[32],
                            "customer_id"=>(int)$customer_id,
                            "customer_name"=>$temp->customer_name,
                            "customer_email"=>$temp->customer_email,
                            "customer_phone"=>$temp->customer_phone,
                            "routed_order_id"=>(int)$order->_id,
                            "finder_id"=>(int)$order->finder_id
                        ];

                        $customerReward->createMyReward($myRewardData);
                    }

                    $return = array('status' => 200,'verified' => $verified,'token'=>$customerToken,'customer_data'=>$customer_data,'fitternity_no'=>$fitternity_no, 'message'=>'Successfully Verified');
                }

            }


            if($finder_id != "" && $amount != "" && $customer_id != ""){

                $device_type = ["android","ios"];
                
                $this->appOfferDiscount = 0;
                
                if($temp->action == "memberships" && isset($_GET['device_type']) &&  in_array($_GET['device_type'], $device_type)){
                    $this->appOfferDiscount = in_array($finder_id, $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;
                }

                $customer_discount = $this->utilities->getCustomerDiscount();

                $amount = $amount - intval($amount * (($this->appOfferDiscount + $customer_discount)/100));

                $customerReward     =   new CustomerReward();
                $calculation        =   $customerReward->purchaseGame($amount,$finder_id,"paymentgateway",false,$customer_id);

                $calculation['algo']['cashback'] = (int)$calculation['algo']['cashback'];

                $cashback  = array(
                    'title'=>$calculation['algo']['cashback'].'% Instant Cashback on Purchase',
                    'percentage'=>$calculation['algo']['cashback'].'%',
                    'commision'=>$calculation['algo']['cashback'],
                    'calculation'=>$calculation,
                    'info'          =>  "",//"You can only pay upto 10% of the booking amount through FitCash. \nIt is calculated basis the amount, type and duration of the purchase.  \nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet'],
                    'description'=>$calculation['description']
                );

                unset($cashback['calculation']['description']);
            }

            $return["cashback"] = $cashback;
            $resp = null;
            if(isset($customer)){
                $resp = $this->utilities->checkIfpopPup($customer);
            }

            $return["popup"] = $resp;

            return Response::json($return,200);

        }else{

            return Response::json(array('status' => 400,'message' => 'Not Found'),$this->error_status);
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

            $data = $temp->toArray();

            if($temp->action == 'vendor_otp'){

                $this->findersms->genericOtp($data);
                $this->findermailer->genericOtp($data);

            }else{

                if($temp->attempt >= 1 && $temp->attempt <= 3){

                    $data = $temp->toArray();
                    $this->customersms->genericOtp($data);
                }
            }
			$tempAttempt = $temp->attempt < 3 ? $temp->attempt : 2;

            return Response::json(array('status' => 200,'attempt' => $tempAttempt,'sender_id'=>'FTRNTY'),200);

        }else{
            return Response::json(array('status' => 400,'message' => 'Not Found'),$this->error_status);
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

        
        return Response::json($response, $this->vendor_token ? 200 : $response['status']); 

    }

    function getAllCustomersByPhone($data){
        
        $customer_data = [];
        Customer::$withoutAppends = true;
        
        Log::info("getAllCustomersByPhone");
        Log::info($data);
        $customer_from_token = $this->utilities->getCustomerFromToken();
        if(!empty($customer_from_token['_id'])){
            $customer_id = $customer_from_token['_id'];
        }
        if(!empty($customer_id)){
        
            $customers = Customer::active()->select('name','email','contact_no','dob','gender','corporate_id')->where('_id', $customer_id)->get();
        
        }else{
            
            $customers = Customer::active()->select('name','email','contact_no','dob','gender','corporate_id')->where('email', 'exists', true)->where('contact_no', substr($data['customer_phone'], -10))->orderBy('_id','desc')->get();

        }
        
        Log::info("Customers by primary contact no");
        Log::info($customers);

        if(!empty($customers) && count($customers)>0){
            if(count($customers) != 1){
                $defaultCustomer = Customer::active()->select('name','email','contact_no','dob','gender','corporate_id')->where('email', 'exists', true)->where('contact_no', substr($data['customer_phone'], -10))->where('default_account', true)->orderBy('_id','desc')->get();

                Log::info("Customers by primary contact no default");
                Log::info($defaultCustomer);
                
                if(count($defaultCustomer) == 0){
                    $defaultCustomer = Customer::active()->select('name','email','contact_no','dob','gender','corporate_id')->where('email', 'exists', true)->where('secondary_verified_no', substr($data['customer_phone'], -10))->orderBy('_id','desc')->get();
                }

                Log::info("Customers by primary secondary contact no");
                Log::info($defaultCustomer);
                
                if(count($defaultCustomer) == 1){
                    
                    $customers = $defaultCustomer;

                    $customers[0]['contact_no'] = substr($data['customer_phone'], -10);

                }
            }
        }
        else {
            // golds-fitcash
            Log::info('golds fitcash condition');

            $customersGold = Customer::active()->select('name','email','contact_no','dob','gender','corporate_id')->where('email', 'exists', false)->where('contact_no',substr($data['customer_phone'], -10))->orderBy('_id','desc')->get();

            Log::info('$customersGold:: ', [$customersGold]);

            if(!empty($customersGold) && count($customersGold)>0){
                return -1;
            }

        }
        
        foreach($customers as $customer) {
            
            $customer = $customer->toArray();
            $customer['customerToken'] = createCustomerToken((int)$customer['_id']);
            $customer['dob'] = isset($customer['dob']) && $customer['dob'] != "" ? $customer['dob'] : "";
            $customer['gender'] = isset($customer['gender']) && $customer['gender'] != "" ? $customer['gender'] : "";
            array_push($customer_data, $customer);
        }
        return $customer_data;
    }

}
