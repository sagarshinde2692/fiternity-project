<?PHP

/** 
 * ControllerName : CustomerController.
 * Maintains a list of functions used for CustomerController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;


class CustomerController extends \BaseController {

	protected $customermailer;
	protected $customersms;

	public function __construct(CustomerMailer $customermailer,CustomerSms $customersms) {

		$this->customermailer	=	$customermailer;
		$this->customersms	=	$customersms;

	}

    // Listing Schedule Tirals for Normal Customer
	public function getAutoBookTrials($customeremail){

		$selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt');

		$trials 		=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
										->where('customer_email', '=', $customeremail)
										->whereIn('booktrial_type', array('auto'))
										->orderBy('_id', 'desc')
										->get($selectfields)->toArray();

		if(count($trials) < 1){

			$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
			
			return Response::json($resp);
		}

		$customertrials  = 	$trial = array();

		$currentDateTime =	\Carbon\Carbon::now();

		foreach ($trials as $trial){

			$scheduleDateTime 				=	Carbon::parse($trial['schedule_date_time']);
			
			$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 0) ? false : true;
			
			array_set($trial, 'passed', $slot_datetime_pass_status);
			
			array_push($customertrials, $trial);
		}

		$resp 	= 	array('status' => 200,'trials' => $customertrials,'message' => 'List of scheduled trials');
		return Response::json($resp);
	}


	// Listing Schedule Tirals for Fitcard Customer
	public function getFitcardAutoBookTrials($customeremail){

		$selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt');
		
		$trials 		=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
							->where('customer_email', '=', $customeremail)
							->where('fitcard_user', 1)
							->whereIn('booktrial_type', array('auto'))
							->orderBy('_id', 'desc')
							->get($selectfields)->toArray();

		if(count($trials) < 1){

			$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
			
			return Response::json($resp);
		}

		$customertrials  = 	$trial = array();
		$currentDateTime =	\Carbon\Carbon::now();

		foreach ($trials as $trial){

			$scheduleDateTime 				=	Carbon::parse($trial['schedule_date_time']);
			
			$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 0) ? false : true;
			
			array_set($trial, 'passed', $slot_datetime_pass_status);
			
			// return $trial; 
			array_push($customertrials, $trial);
		}

		$resp 	= 	array('status' => 200,'trials' => $customertrials,'message' => 'List of scheduled trials');

		return Response::json($resp);
	}


	// Get Particular Tiral of Customer
	public function getAutoBookTrial($trialid){

		$selectfields 	=	array('finder', 'finder_id','finder_name','finder_slug','service_name','schedule_date','schedule_slot_start_time','schedule_slot_end_time','code');
		$trial 			=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))->where('_id', '=', intval($trialid) )->where('going_status', '=', 1)->first($selectfields);

		if(!$trial){

			$resp 	= 	array('status' => 200, 'trial' => $trial, 'message' => 'No trial Exist :)');
			
			return Response::json($resp);
		}

		$resp 	= 	array('status' => 200, 'trial' => $trial, 'message' => 'Particular Tiral of Customer');
		
		return Response::json($resp);
	}


	//capturePayment for book schedule 
	public function capturePayment(){

		$data					=	Input::all();
		$orderid 				=	Booktrialorder::max('_id') + 1;
		$booktrialorder 		= 	new Booktrialorder($data);
		$booktrialorder->_id 	= 	$orderid;
		$order   				= 	$booktrialorder->save();

		if(Input::get('status') == 'success'){
			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
			return Response::json($resp);
		}

		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		return Response::json($resp);

	}


	//create cod order for fitcard
	public function generateFitCardCodOrder(){

		$data				=	Input::json()->all();

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_name");
		}

		if(empty($data['customer_email'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_email");
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			return $resp 	= 	array('status' => 500,'message' => "Invalid Email Id");
		} 
		
		if(empty($data['customer_identity'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_identity");
		}

		if(empty($data['customer_phone'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_phone");
		}

		if(empty($data['customer_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_location");
		}

		$orderid 			=	Order::max('_id') + 1;

		$data = array(
			'customer_name'		=>		Input::json()->get('customer_name'),
			'customer_phone'	=>		Input::json()->get('customer_phone'),
			'customer_email'	=>		Input::json()->get('customer_email'),
			'customer_location'	=>		Input::json()->get('customer_location'),
			'customer_identity'	=>		Input::json()->get('customer_identity'),
			'fitcardno'			=>		intval((10000 + intval($orderid)) - 10000),
			'type'				=>		'fitcardbuy',
			'payment_mode'		=>		'cod',
			'status'			=>		'0'	
			);

		$order 				= 	new Order($data);
		$order->_id 		= 	$orderid;
		$orderstatus   		= 	$order->save();

		//send welcome email to cod customer
		$sndWelcomeMail	= 	$this->customermailer->fitcardCodWelcomeMail($order->toArray());

		$resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Order Successful :)");

		return Response::json($resp);

	}

	//generate fitcard temp order
	public function generateFitCardTmpOrder(){

		$data			=	Input::json()->all();

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_name");
		}

		if(empty($data['customer_email'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_email");
		}

		if(empty($data['customer_phone'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_phone");
		}

		if(empty($data['customer_identity'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_identity");
		}

		if(empty($data['customer_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_location");
		}

		$orderid 			=	Order::max('_id') + 1;

		$data = array(
			'customer_name'		=>		Input::json()->get('customer_name'),
			'customer_phone'	=>		Input::json()->get('customer_phone'),
			'customer_email'	=>		Input::json()->get('customer_email'),
			'customer_location'	=>		Input::json()->get('customer_location'),
			'customer_identity'	=>		Input::json()->get('customer_identity'),
			'fitcardno'			=>		intval((10000 + intval($orderid)) - 10000),
			'type'				=>		'fitcardbuy',
			'payment_mode'		=>		'paymentgateway',
			'status'			=>		'0'	
			);

		$order 			= 	new Order($data);

		$order->_id 		= 	$orderid;

		$orderstatus   		= 	$order->save();

		$resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Transaction details for tmp fitcard buy :)");
		return Response::json($resp);

	}


	//capture order status for customer
	public function captureOrderPayment(){

		$data		=	Input::json()->all();

		$orderid 	=	(int) Input::json()->get('orderid');

		$order 		= 	Order::findOrFail($orderid);

		if(Input::json()->get('status') == 'success'){

			array_set($data, 'status', '1');

			$orderdata 	=	$order->update($data);
			
			//send welcome email to payment gateway customer
			$sndWelcomeMail	= 	$this->customermailer->fitcardPaymentGateWelcomeMail($order->toArray());

			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");

			Log::info('Customer Purchase', array('purchase_details' => $order));

			return Response::json($resp);
		}

		$orderdata 		=	$order->update($data);

		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		
		return Response::json($resp);

	}

	public function register(){

		$data = Input::json()->all();
		$inserted_id = Customer::max('_id') + 1;
		$rules = [
		    'name' => 'required|max:255',
		    'email' => 'required|email|unique:customers|max:255',
		    'password' => 'required|min:6|max:20|confirmed',
		    'password_confirmation' => 'required|min:6|max:20',
		    'contact_no' => 'max:15',
		    'identity' => 'required'
		];
        $validator = Validator::make($data,$rules);

     	if ($validator->fails()) {
            return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);
        }else{

        	$customer = Customer::where('email','=',$data['email'])->where('identity','!=','email')->first();
			
			if(empty($customer)){
				$new_validator = Validator::make($data, Customer::$rules);
				if ($new_validator->fails()) {
		            return Response::json(array('status' => 400,'message' => $this->errorMessage($new_validator->errors())),400);
		        }else{
		        	$account_link = array('email'=>0,'google'=>0,'facebook'=>0,'twitter'=>0);
        			$account_link[$data['identity']] = 1;
		        	$customer = new Customer();
		        	$customer->_id = $inserted_id;
			        $customer->name = ucwords($data['name']) ;
			        $customer->email = $data['email'];
			        $customer->picture = "http://www.gravatar.com/avatar/".md5($data['email'])."?s=200&d=http%3A%2F%2Fb.fitn.in%2Favatar.png";
			        $customer->password = md5($data['password']);
			        if(isset($data['contact_no'])){
			        	$customer->contact_no = $data['contact_no'];
			        }
			        $customer->identity = $data['identity'];
			        $customer->account_link = $account_link;
			        $customer->status = "1";
			        $customer->save();

			        $customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email'],'password'=>$data['password']);
					$this->customermailer->register($customer_data);

					Log::info('Customer Register', array('customer_details' => $customer));

        			return Response::json($this->createToken($customer),200);
		        }	
	        }else{

	        	$account_link= $customer['account_link'];
				$account_link[$data['identity']] = 1;
				$customer->name = ucwords($data['name']) ;
		        $customer->email = $data['email'];
		        $customer->picture = "http://www.gravatar.com/avatar/".md5($data['email'])."?s=200&d=http%3A%2F%2Fb.fitn.in%2Favatar.png";
		        $customer->password = md5($data['password']);
		        if(isset($data['contact_no'])){
		        	$customer->contact_no = $data['contact_no'];
		        }
		        $customer->account_link = $account_link;
		        $customer->status = "1";
				$customer->update();

				$customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email'],'password'=>$data['password']);
				$this->customermailer->register($customer_data);

				Log::info('Customer Register', array('customer_details' => $customer));

				return Response::json($this->createToken($customer),200);
	        }

	        $account_link = array('email'=>0,'google'=>0,'facebook'=>0,'twitter'=>0);
    		$account_link[$data['identity']] = 1;
        }
	}




	public function customerLogin(){

		$data = Input::json()->all();

		if(isset($data['identity']) && !empty($data['identity'])){

			if($data['identity'] == 'email')
			{
				$responce = $this->emailLogin($data);
				return Response::json($responce,$responce['status']);
			}elseif($data['identity'] == 'google' || $data['identity'] == 'facebook' || $data['identity'] == 'twitter'){
				$responce = $this->socialLogin($data);
				return Response::json($responce,$responce['status']);
			}else{
				return Response::json(array('status' => 400,'message' => array('identity' => 'The identity is incorrect')),400);
			}

	    }else{

	    	return Response::json(array('status' => 400,'message' => array('identity' => 'The identity field is required')),400);
	    }
	}

	public function emailLogin($data){

		$rules = [
				    'email' => 'required|email',
				    'password' => 'required'
				];

		$validator = Validator::make($data = Input::json()->all(),$rules);

		if($validator->fails()) {
			return array('status' => 400,'message' =>$this->errorMessage($validator->errors()));  
        }

        $customer = Customer::where('email','=',$data['email'])->where('status','=','1')->first();
       	if(empty($customer)){
       		return array('status' => 400,'message' => 'Customer is inactive');
       	}else{
       		if(isset($customer['hull_id']) && $customer['ishulluser'] == 1){
       			$customer->password = md5($data['password']);
       			$customer->ishulluser = 0;
       		}else{
       			if($customer['password'] != md5($data['password'])){
       				return array('status' => 400,'message' => array('email' => 'Incorrect email and password','password' => 'incorrect email and password'));
       			}
       		}
       	}

       	if($customer['account_link'][$data['identity']] != 1)
		{
			$account_link = $customer['account_link'];
			$account_link[$data['identity']] = 1;
			$customer->account_link = $account_link;
		}

		$customer->last_visited = Carbon::now();
       	$customer->update();

		return $this->createToken($customer);
	}

	public function socialLogin($data){

		if($data['identity'] == 'facebook'){

			if(isset($data['email']) && !empty($data['email'])){

				$rules = [
				    'email' => 'email',
				    'facebook_id' => 'required'
				];

				$validator = Validator::make($data = Input::json()->all(),$rules);

				if($validator->fails()) {
					return array('status' => 400,'message' =>$this->errorMessage($validator->errors()));  
		        }

		        $customer = Customer::where('email','=',$data['email'])->first();

			}else{

				$rules = [
			    	'facebook_id' => 'required'
				];

				$validator = Validator::make($data = Input::json()->all(),$rules);

				if($validator->fails()) {
					return array('status' => 400,'message' =>$this->errorMessage($validator->errors()));  
		        }else{

					$customer = Customer::where('facebook_id','=',$data['facebook_id'])->first();

					if(empty($customer)){
						return array('status' => 401,'message' => 'email is missing');
					}
				}
			}
			
	    }else{

	    	$rules = [
				    'email' => 'required|email'
				];

			$validator = Validator::make($data = Input::json()->all(),$rules);

			if($validator->fails()) {
				return array('status' => 400,'message' =>$this->errorMessage($validator->errors()));  
	        }

	        $customer = Customer::where('email','=',$data['email'])->first();
	    }

		if(empty($customer)){
			$socialRegister = $this->socialRegister($data);
			if($socialRegister['status'] == 400)
			{
				return $socialRegister;
			}else{
				$customer = $socialRegister['customer'];

				if(isset($customer['password'])){
					$password = $customer['password'];
				}else{
					$password = "No Password";
				}

				$customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email'],'password'=>$password);
				
				$this->customermailer->register($customer_data);
			}
		}
		

		if($customer['account_link'][$data['identity']] != 1)
		{
			$account_link = $customer['account_link'];
			$account_link[$data['identity']] = 1;
			$customer->account_link = $account_link;
		}

		if($data['identity'] == 'facebook' && isset($data['facebook_id'])){
        	$customer->facebook_id = $data['facebook_id'];
        }

		$customer->last_visited = Carbon::now();
       	$customer->update();

		return $this->createToken($customer);
	}

	public function socialRegister($data){

		$rules = [
			    'email' => 'required|email',
			    'name' => 'required',
			    'identity' => 'required',
			];

		$inserted_id = Customer::max('_id') + 1;
        $validator = Validator::make($data, $rules);

		if ($validator->fails()) {
            $response = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));
        }else{
        	
        	$account_link = array('email'=>0,'google'=>0,'facebook'=>0,'twitter'=>0);
        	$account_link[$data['identity']] = 1;

	        $customer = new Customer();
	        $customer->_id = $inserted_id;
	        $customer->name = ucwords($data['name']) ;
	        $customer->email = $data['email'];
	        $customer->picture = (isset($data['picture'])) ? $data['picture'] : "";
	        $customer->identity = $data['identity'];
	        $customer->account_link = $account_link;

	        if($data['identity'] == 'facebook' && isset($data['facebook_id'])){
	        	$customer->facebook_id = $data['facebook_id'];
	        }

	        $customer->status = "1";
	        $customer->save();

	        $response = array('status' => 200,'customer'=>$customer);
        }

        return $response;
	}

	public function createToken($customer){

		$mob = (isset($customer['contact_no'])) ? $customer['contact_no'] : "";
		$location = (isset($customer['location'])) ? $customer['location'] : "";

		$jwt_claim = array(
			    "iat" => Config::get('app.jwt.iat'),
			    "nbf" => Config::get('app.jwt.nbf'),
			    "exp" => Config::get('app.jwt.exp'),
			    "customer" => array('_id'=>$customer['_id'],'name'=>$customer['name'],"email"=>$customer['email'],"picture"=>$customer['picture'],'facebook_id'=>$customer['facebook_id'],"identity"=>$customer['identity'],'extra'=>array('mob'=>$mob,'location'=>$location))
			);
		$jwt_key = Config::get('app.jwt.key');
		$jwt_alg = Config::get('app.jwt.alg');

		$token = JWT::encode($jwt_claim,$jwt_key,$jwt_alg);

        return array('status' => 200,'message' => 'successfull login', 'token' => $token);
	}

	public function validateToken(){

		return Response::json(array('status' => 200,'message' => 'token is correct'),200);
	}

	public function resetPassword(){

		$data = Input::json()->all();

		$jwt_token  = Request::header('Authorization');
        $jwt_key = Config::get('app.jwt.key');
        $jwt_alg = Config::get('app.jwt.alg');
		$decoded = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

		$data['email'] = $decoded->customer->email;

		$rules = [
			    'email' => 'required|email|max:255',
	    		'password' => 'required|min:6|confirmed',
	    		'password_confirmation' => 'required|min:6',
			];

        $validator = Validator::make($data, $rules);

		if ($validator->fails()) {
            $response = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));
        }else{
        	
        	$customer = Customer::where('email','=',$data['email'])->first();
        	
        	if(empty($customer)){
				return array('status' => 400,'message' => array('email' => 'Incorrect email'));
			}

			$password['password'] = md5($data['password']);
			$customer->update($password);

	        $response = array('status' => 200,'message' => 'password reset successfull');
        }

        return Response::json($response,$responce['status']);
	}

	public function createPasswordToken($customer){
		$password_claim = array(
			    "iat" => Config::get('app.forgot_password.iat'),
			    "exp" => Config::get('app.forgot_password.exp'),
			    "customer" => array('name'=>$customer['name'],"email"=>$customer['email'])
			);
		$password_key = Config::get('app.forgot_password.key');
		$password_alg = Config::get('app.forgot_password.alg');

		$token = JWT::encode($password_claim,$password_key,$password_alg);

        return $token;
	}

	public function forgotPasswordEmail(){

		$data = Input::json()->all();

		if(isset($data['email']) && !empty($data['email'])){
			$customer = Customer::where('email','=',$data['email'])->first();
			if(!empty($customer)){

				$token = $this->createPasswordToken($customer);

				if(isset($customer['email']) && !empty($customer['email'])){
					$customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email'],'token'=>$token);
					$this->customermailer->forgotPassword($customer_data);
					return Response::json(array('status' => 200,'message' => 'token successfull created and mail send', 'token' => $token),200);
				}else{
					return Response::json(array('status' => 400,'message' => 'Customer email not present'),400);
				}

			}else{
				return Response::json(array('status' => 400,'message' => 'Customer not found'),400);
			}
		}else{
			return Response::json(array('status' => 400,'message' => 'Empty email'),400);
		}

	}

	public function forgotPassword(){

		$data = Input::json()->all();

		if(isset($data['password_token']) && !empty($data['password_token'])){

			$password_token = $data['password_token'];
	        $password_key = Config::get('app.forgot_password.key');
	        $password_alg = Config::get('app.forgot_password.alg');
	    
	        try{
	        	if(Cache::tags('blacklist_forgot_password_token')->has($password_token)){
	        		return Response::json(array('status' => 400,'message' => 'Token expired'),400);
	        	}

	            $decoded = JWT::decode($password_token, $password_key,array($password_alg));

	            if(!empty($decoded)){
	            	$rules = [
					    'email' => 'required|email|max:255',
			    		'password' => 'required|min:6|confirmed',
			    		'password_confirmation' => 'required|min:6',
					];

	            	$data['email'] = $decoded->customer->email;

	            	$validator = Validator::make($data, $rules);

	            	if ($validator->fails()) {
			           return Response::json(array('status' => 400,'message' =>$this->errorMessage($validator->errors())),400);
			        }else{
			        	$password['password'] = md5($data['password']);

			        	$customer = Customer::where('email','=',$data['email'])->first();
						$customer->update($password);

						$expiry_time_minutes = (int)round(($decoded->exp - time())/60);

						Cache::tags('blacklist_forgot_password_token')->put($password_token,$decoded->customer->email,$expiry_time_minutes);

				        return $this->createToken($customer);
			        }
	            }else{
	            	return Response::json(array('status' => 400,'message' => 'Token incorrect'),400);
	            }

	        }catch(DomainException $e){
	            return Response::json(array('status' => 400,'message' => 'Token incorrect'),400);
	        }catch(ExpiredException $e){
	            return Response::json(array('status' => 400,'message' => 'Token expired'),400);
	        }catch(SignatureInvalidException $e){
	            return Response::json(array('status' => 400,'message' => 'Signature verification failed'),400);
	        }catch(Exception $e){
	            return Response::json(array('status' => 400,'message' => 'Token incorrect'),400);
	        }

	    }else{
	        return Response::json(array('status' => 400,'message' => 'Empty token or token should be string'),400);
	    }
	}

	public function forgotPasswordEmailApp(){

		$data = Input::json()->all();

		if(isset($data['email']) && !empty($data['email'])){
			$customer = Customer::where('email','=',$data['email'])->first();
			if(!empty($customer)){

				$otp = $this->createOtp($customer['email']);

				$email = 0;
				if(isset($customer['email']) && !empty($customer['email'])){
					$customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email'],'otp'=>$otp);
					$this->customermailer->forgotPasswordApp($customer_data);
					$email = 1;
				}

				$sms = 0;
				if(isset($customer['contact_no']) && !empty($customer['contact_no'])){
					$customer_data = array('name'=>ucwords($customer['name']),'contact_no'=>$customer['contact_no'],'otp'=>$otp);
					$this->customersms->forgotPasswordApp($customer_data);
					$sms = 1;
				}

				if($email == 0 && $sms == 0){
					return Response::json(array('status' => 400,'message' => 'email and contact no not present'),400);
				}else if ($email == 0){	
					return Response::json(array('status' => 200,'message' => 'sms sent and email not sent','otp'=> $otp),200);
				}else if ($sms == 0){	
					return Response::json(array('status' => 200,'message' => 'email sent and sms not sent','otp'=> $otp),200);
				}else{
					return Response::json(array('status' => 200,'message' => 'email and sms sent','otp'=> $otp),200);
				}

			}else{
				return Response::json(array('status' => 400,'message' => 'Customer not found'),400);
			}
		}else{
			return Response::json(array('status' => 400,'message' => 'Empty email'),400);
		}

	}

	public function createOtp($email){
		$length = 4;
		$characters = '0123456789';
		$expiry_time_minutes = 60*24;
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }

	    if(Cache::tags('app_customer_otp')->has($randomString)){
	    	$randomString = $this->createOtp($customer['email']);
	    }
	    else{
	    	Cache::tags('app_customer_otp')->put($randomString,$email,$expiry_time_minutes);
	    }

	    return $randomString;
	}

	public function validateOtp(){

		$data = Input::json()->all();

		$rules = [
		    'email' => 'required|email|max:255',
    		'otp' => 'required'
		];

		$validator = Validator::make($data, $rules);

		if ($validator->fails()) {
            $response = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));
        }else{
        	if(Cache::tags('app_customer_otp')->has($data['otp'])){
        		if(Cache::tags('app_customer_otp')->get($data['otp']) == $data['email']){
        			$customer = Customer::where('email','=',$data['email'])->first();
        			$token = $this->createPasswordToken($customer);
        			$response = array('status' => 200,'token'=>$token,'message' =>'OTP verified successfull');
        		}else{
        			$response = array('status' => 400,'message' =>'OTP expired');
        		}
        	}else{
        		$response = array('status' => 400,'message' =>'OTP expired');
        	}
        }

	    return Response::json($response,$response['status']);
	}

	public function customerLogout(){

		$jwt_token = Request::header('Authorization');
		$decodedToken = $this->customerTokenDecode($jwt_token);
		$expiry_time_minutes = (int)round(($decodedToken->exp - time())/60);

		Cache::tags('blacklist_customer_token')->put($jwt_token,$decodedToken->customer->email,$expiry_time_minutes);

		return Response::json(array('status' => 200,'message' => 'logged out successfull'),200);
	}

	public function errorMessage($errors){

		$errors = json_decode(json_encode($errors));
		$message = array();
		foreach ($errors as $key => $value) {
			$message[$key] = $value[0];
		}
		return $message;
	}

	public function customerUpdate(){

		$jwt_token = Request::header('Authorization');
		$decodedToken = $this->customerTokenDecode($jwt_token);
		$variable = ['name','email','contact_no','location'];

		$data = Input::json()->all();
		$validator = Validator::make($data, Customer::$update_rules);

		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' =>$this->errorMessage($validator->errors())),400);
		}

		if(isset($data['email']) && !empty($data['email'])){
			$customer = Customer::where('email','=',$data['email'])->where('_id','!=',(int) $decodedToken->customer->_id)->first();

			if(!empty($customer)){
				return Response::json(array('status' => 400,'message' =>array(''=>'Email already exists')),400);
			}
		}

		$customer_data = [];

		foreach ($variable as $value) {
			if(array_key_exists($value, $data)){
				$customer_data[$value] = $data[$value];
			}
		}

		$customer = Customer::find((int) $decodedToken->customer->_id);
		if(!empty($customer_data)){
			$customer->update($customer_data);
			$message = implode(', ', array_keys($customer_data)) ;
			return Response::json(array('status' => 200,'message' => $message.' updated successfull'),200);
		}
		
		return Response::json(array('status' => 400,'message' => 'customer data empty'),400);
	}

	public function customerTokenDecode($token){

		$jwt_token = $token;
		$jwt_key = Config::get('app.jwt.key');
        $jwt_alg = Config::get('app.jwt.alg');
		$decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

		return $decodedToken;
	}

}