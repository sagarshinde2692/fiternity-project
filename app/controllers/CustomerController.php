<?PHP

/** 
 * ControllerName : CustomerController.
 * Maintains a list of functions used for CustomerController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;


class CustomerController extends \BaseController {

	protected $customermailer;

	public function __construct(CustomerMailer $customermailer) {

		$this->customermailer	=	$customermailer;

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

			return Response::json($resp);
		}

		$orderdata 		=	$order->update($data);

		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		
		return Response::json($resp);

	}

	public function register(){

		$inserted_id = Customer::max('_id') + 1;
        $validator = Validator::make($data = Input::json()->all(), Customer::$rules);

     	if ($validator->fails()) {
            $response = array('status' => 404,'error_message' =>$validator->errors());
        }else{
        	
        	$account_link = array('email'=>0,'google'=>0,'facebook'=>0,'twitter'=>0);
        	$account_link[$data['identity']] = 1;

	        $customer = new Customer();
	        $customer->_id = $inserted_id;
	        $customer->name = ucwords($data['name']) ;
	        $customer->email = $data['email'];
	        $customer->password = md5($data['password']);
	        $customer->contact_no = $data['contact_no'];
	        $customer->identity = $data['identity'];
	        $customer->account_link = $account_link;
	        $customer->status = "1";
	        $customer->save();

	        $response = array('status' => 200);
        } 

        return Response::json($response);  
	}


	public function customerLogin(){

		$data = Input::json()->all();

		if(isset($data['identity']) && !empty($data['identity'])){

			if($data['identity'] == 'email')
			{
				return Response::json($this->emailLogin($data));
			}elseif($data['identity'] == 'google' || $data['identity'] == 'facebook' || $data['identity'] == 'twitter'){
				return Response::json($this->socialLogin($data));
			}else{
				return Response::json(array('status' => 404,'error_message' => array('identity' => 'The identity is incorrect')));
			}

	    }else{

	    	return Response::json(array('status' => 404,'error_message' => array('identity' => 'The identity field is required')));
	    }
	}

	public function emailLogin($data){

		$rules = [
				    'email' => 'required|email',
				    'password' => 'required'
				];

		$validator = Validator::make($data = Input::json()->all(),$rules);

		if($validator->fails()) {
			return array('status' => 404,'error_message' =>$validator->errors());  
        }

		$customer = Customer::where('email','=',$data['email'])->where('password','=',md5($data['password']))->first();

		if(empty($customer)){
			return array('status' => 404,'error_message' => array('email' => 'Incorrect email and password','password' => 'incorrect email and password'));
		}

		if($customer['account_link'][$data['identity']] != 1)
		{
			$customer = $this->updateAccountLink($customer,$data['identity']);
		}

		return $this->createToken($customer);
	}

	public function socialLogin($data){
		$rules = [
			    'email' => 'required|email'
			];

		$validator = Validator::make($data = Input::json()->all(),$rules);

		if($validator->fails()) {
			return array('status' => 404,'error_message' =>$validator->errors());  
        }

        $customer = Customer::where('email','=',$data['email'])->first();

		if(empty($customer)){
			$socialRegister = $this->socialRegister($data);
			if($socialRegister['status'] == 404)
			{
				return $socialRegister;
			}else{
				$customer = $socialRegister['customer'];
			}
		}

		if($customer['account_link'][$data['identity']] != 1)
		{
			$customer = $this->updateAccountLink($customer,$data['identity']);
		} 

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
            $response = array('status' => 404,'error_message' =>$validator->errors());
        }else{
        	
        	$account_link = array('email'=>0,'google'=>0,'facebook'=>0,'twitter'=>0);
        	$account_link[$data['identity']] = 1;

	        $customer = new Customer();
	        $customer->_id = $inserted_id;
	        $customer->name = ucwords($data['name']) ;
	        $customer->email = $data['email'];
	        $customer->identity = $data['identity'];
	        $customer->account_link = $account_link;
	        $customer->status = "1";
	        $customer->save();

	        $response = array('status' => 200,'customer'=>$customer);
        }

        return $response;
	}

	public function updateAccountLink($customer,$identity){

		$account_link['account_link'] = $customer['account_link'];
		$account_link['account_link'][$identity] = 1;
		$customer->update($account_link);

		return $customer;
	}

	public function createToken($customer){
		$jwt_claim = array(
				"jti" => Config::get('app.jwt.jti'),
			    "iat" => Config::get('app.jwt.iat'),
			    "nbf" => Config::get('app.jwt.nbf'),
			    "exp" => Config::get('app.jwt.exp'),
			    "customer" => array('name'=>$customer['name'],"email"=>$customer['email'])
			);
		$jwt_key = Config::get('app.jwt.key');
		$jwt_alg = Config::get('app.jwt.alg');

		$token = JWT::encode($jwt_claim,$jwt_key,$jwt_alg);

        return array('status' => 200,'message' => 'successfull login', 'token' => $token);
	}

	public function validateToken(){

		return Response::json(array('status' => 200,'message' => 'token is correct'));
	}

	public function resetPassword(){

		$data = Input::json()->all();

		$rules = [
			    'email' => 'required|email|max:255',
	    		'password' => 'required|min:8|max:20|confirmed',
	    		'password_confirmation' => 'required|min:8|max:20',
			];

        $validator = Validator::make($data, $rules);

		if ($validator->fails()) {
            $response = array('status' => 404,'error_message' =>$validator->errors());
        }else{
        	
        	$customer = Customer::where('email','=',$data['email'])->first();
        	
        	if(empty($customer)){
				return array('status' => 404,'error_message' => array('email' => 'Incorrect email'));
			}

			$password['password'] = md5($data['password']);
			$customer->update($password);

	        $response = array('status' => 200,'message' => 'password reset successfull');
        }

        return Response::json($response);
	}

	public function customerLogout(){

		$jwt_token = Request::header('Authorization');
		$jwt_key = Config::get('app.jwt.key');
        $jwt_alg = Config::get('app.jwt.alg');
		$decoded = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
		$expiry_time_minutes = (int)round(($decoded->exp - time())/60);

		Cache::tags('blacklist_customer_token')->put($jwt_token,$decoded->customer->email,$expiry_time_minutes);

		return Response::json(array('status' => 200,'message' => 'logged out'));
	}
}
