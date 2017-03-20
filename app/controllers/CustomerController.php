<?PHP

/** 
 * ControllerName : CustomerController.
 * Maintains a list of functions used for CustomerController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Services\Utilities as Utilities;
use App\Services\CustomerInfo as CustomerInfo;
use App\Services\CustomerReward as CustomerReward;
use App\Services\ShortenUrl as ShortenUrl;
use App\Services\Emi as Emi;

class CustomerController extends \BaseController {

	protected $customermailer;
	protected $customersms;
	protected $utilities;


	public function __construct(CustomerMailer $customermailer,CustomerSms $customersms,Utilities $utilities,CustomerReward $customerreward) {

		$this->customermailer	=	$customermailer;
		$this->customersms	=	$customersms;
		$this->utilities	=	$utilities;
		$this->customerreward = $customerreward;

	}

    // Listing Schedule Tirals for Normal Customer
	public function getAutoBookTrials($customeremail){
		$selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt','service_id','what_i_should_carry','what_i_should_expect','origin','trial_attended_finder', 'type','amount');
		$trials 		=	Booktrial::where('customer_email', '=', $customeremail)
		->whereIn('booktrial_type', array('auto'))
		->with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
		->with(array('invite'=>function($query){$query->get(array('invitee_name', 'invitee_email','invitee_phone','referrer_booktrial_id'));}))
		//->with('invite')
		->orderBy('_id', 'desc')->take(8)
		->get($selectfields)->toArray();


		if(count($trials) < 0){
			$resp 	= 	array('status' => 200,'trials' => [],'message' => 'No trials scheduled yet :)');
			return Response::json($resp,200);
		}

		$customertrials  = 	$trial = array();
		$currentDateTime =	\Carbon\Carbon::now();
		$upcomingtrials = [];
		$passedtrials = [];
		$healthytiffintrail = [];
		
		foreach ($trials as $trial){

			array_set($trial, 'message', '');
			array_set($trial, 'finder_offerings', []);

			if(isset($trial['finder_id']) && $trial['finder_id'] != ""){
				$finderarr = Finder::active()->with('offerings')->with('location')->where('_id','=', intval($trial['finder_id']))->first();
				if ($finderarr) {
					$finderarr = $finderarr->toArray();
					$avg_rating  = isset($finderarr['average_rating']) ? $finderarr['average_rating'] : 0;
					array_set($trial, 'finder_offerings', pluck( $finderarr['offerings'] , array('_id', 'name', 'slug') ));
					array_set($trial, 'finder_location', ucwords($finderarr['location']['name']));
					array_set($trial, 'average_rating', $avg_rating);
				}
			}



			$scheduleDateTime 				=	Carbon::parse($trial['schedule_date_time']);
			$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 0) ? false : true;

			
			array_set($trial, 'passed', $slot_datetime_pass_status);

			if($slot_datetime_pass_status){

				$time_diff = strtotime($currentDateTime) - strtotime($scheduleDateTime);
				$hour2 = 60*60*2;

				$trial_message = '';

				if($time_diff >= $hour2){

					$trial_message = nl2br("Hope you had a chance to attend the session.\nIf you attended, rate your experience and win awesome merchandise and unlock Rs. 250 off");
				}

				array_set($trial, 'message', $trial_message);

				array_push($passedtrials, $trial);
				
			}else{

				$time_diff = strtotime($scheduleDateTime) - strtotime($currentDateTime);

				$hour = 60*60;
				$hour12 = 60*60*12;
				$hour2 = 60*60*2;

				$trial_message = '';

				if($time_diff <= $hour12 && isset($trial['what_i_should_carry']) && $trial['what_i_should_carry'] != ''){
					$trial_message = nl2br('What to carry : '.str_replace("Optional","\nOptional ",strip_tags($trial['what_i_should_carry'])));
				}

				if($time_diff <= $hour && isset($trial['finder']['finder_poc_for_customer_name']) && $trial['finder']['finder_poc_for_customer_name'] != ''){
					$trial_message = nl2br("Hope you are ready for your session.\nContact person : ".ucwords($trial['finder']['finder_poc_for_customer_name'])."\nHave a great workout!");
				}

				array_set($trial, 'message', $trial_message);

				$going_status_txt = ['rescheduled','cancel'];

				if(!isset($trial['going_status_txt'])){
					$trial['going_status_txt'] = "-";
				}

				if(!isset($trial['amount'])){
					$trial['amount'] = 0;
				}

				if(isset($trial['amount']) && $trial['amount'] == "-"){
					$trial['amount'] = 0;
				}

				if($time_diff <= $hour2){
					$reschedule_enable = false;
				}elseif(in_array($trial['going_status_txt'], $going_status_txt) || $trial['amount'] > 0  || $trial['type'] == 'workout-session'){
					$reschedule_enable = false;
				}else{
					$reschedule_enable = true;
				}

				if(!isset($trial['going_status_txt'])){
					$reschedule_enable = false;
				}
			
				array_set($trial, 'reschedule_enable', $reschedule_enable);

				array_push($upcomingtrials, $trial);	
			}

			$healthytiffintrail = array();

			$ht_selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt','service_id','what_i_should_carry','what_i_should_expect','origin','preferred_starting_date','amount','status','order_action');

			$healthytiffintrail = Order::where('customer_email',$customeremail)
			->where('type','healthytiffintrail')
			->orWhere(function($query){$query->where('status',"1")->where('order_action','bought')->where('amount','!=',0);})
			->orWhere(function($query){$query->where('status',"0")->where('amount','exist',false);})
			->with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
			->orderBy('_id', 'desc')->take(8)
			->get($ht_selectfields);

			if(count($healthytiffintrail) > 0){
				$healthytiffintrail = $healthytiffintrail->toArray();

				foreach ($healthytiffintrail as $key => $value) {

					foreach ($selectfields as $field) {

						if(!isset($value[$field])){
							$healthytiffintrail[$key][$field] = "";
						}

						if(isset($value['preferred_starting_date'])){

							$healthytiffintrail[$key]['schedule_date_time'] = $value['preferred_starting_date'];
							$healthytiffintrail[$key]['schedule_date'] = $value['preferred_starting_date'];

							unset($healthytiffintrail[$key]['preferred_starting_date']);
						}

						if(isset($value['amount'])){

							unset($healthytiffintrail[$key]['amount']);
						}

						if(isset($value['status'])){

							unset($healthytiffintrail[$key]['status']);
						}

						if(isset($value['order_action'])){

							unset($healthytiffintrail[$key]['order_action']);
						}

					}

				}
			}

		}

		// array_push($customertrials, $trial);
		$resp 	= 	array('status' => 200,'passedtrials' => $passedtrials,'upcomingtrials' => $upcomingtrials,'healthytiffintrail'=>$healthytiffintrail,'message' => 'List of scheduled trials');
		return Response::json($resp,200);
	}



	// Listing Schedule Tirals for Fitcard Customer
	public function getFitcardAutoBookTrials($customeremail){

		$selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt');
		$trials 		=	Booktrial::where('customer_email', '=', $customeremail)
		->where('fitcard_user', 1)
		->whereIn('booktrial_type', array('auto'))
		->with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
		->orderBy('_id', 'desc')
		->get($selectfields)->toArray();

		if(!$trials){
			return $this->responseNotFound('Customer does not exist');
		}

		if(count($trials) < 1){
			$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
			return Response::json($resp,200);
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

		return Response::json($resp,200);
	}


	// Get Particular Tiral of Customer
	public function getAutoBookTrial($trialid){

		$selectfields 	=	array('finder', 'finder_id','finder_name','finder_slug','service_name','schedule_date','schedule_slot_start_time','schedule_slot_end_time','code');
		$trial 			=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))->where('_id', '=', intval($trialid) )->where('going_status', '=', 1)->first($selectfields);

		if(!$trials){
			return $this->responseNotFound('Customer does not exist');
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
			return Response::json($resp,200);
		}

		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		return Response::json($resp,200);

	}


	//create cod order for fitcard
	public function generateFitCardCodOrder(){

		$data				=	Input::json()->all();

		if(empty($data['customer_name'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_name");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_email'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
			return  Response::json($resp, 400);
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			$resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
			return  Response::json($resp, 400);
		} 
		
		if(empty($data['customer_identity'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_identity");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_phone'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_location'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_location");
			return  Response::json($resp, 400);
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

		return Response::json($resp,200);

	}

	//generate fitcard temp order
	public function generateFitCardTmpOrder(){

		$data			=	Input::json()->all();

		if(empty($data['customer_name'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_name");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_email'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
			return  Response::json($resp, 400);
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			$resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_phone'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_identity'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_identity");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_location'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_location");
			return  Response::json($resp, 400);
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
		return Response::json($resp,200);

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
			if (filter_var(trim($order->customer_email), FILTER_VALIDATE_EMAIL) === false){
				$orderdata 	=	$order->update(['email_not_sent'=>'captureOrderPayment']);
			}else{
				$sndWelcomeMail	= 	$this->customermailer->fitcardPaymentGateWelcomeMail($order->toArray());
			}
			
			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
			Log::info('Customer Purchase : '.json_encode(array('purchase_details' => $order)));
			return Response::json($resp,200);
		}

		$orderdata 		=	$order->update($data);
		$resp 	= 	array('status' => 400, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		
		return Response::json($resp,400);

	}

	public function register(){

		$data = Input::json()->all();
		$inserted_id = Customer::max('_id') + 1;
		$rules = [
		'name' => 'required|max:255',
		'email' => 'required|email|max:255',
		'password' => 'required|min:6|max:20|confirmed',
		'password_confirmation' => 'required|min:6|max:20',
		'contact_no' => 'max:15',
		'identity' => 'required'
		];
		$validator = Validator::make($data,$rules);
		$data['email'] = strtolower($data['email']);
		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);
		}else{

			$customer = Customer::where('email','=',$data['email'])->where('identity','!=','email')->first();
			
			if(empty($customer)){
				$ishullcustomer = Customer::where('email','=',$data['email'])->where('ishulluser',1)->first();
				if(empty($ishullcustomer)){
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
						isset($data['dob']) ? $customer->dob = $data['dob'] : null;
						isset($data['gender']) ? $customer->gender = $data['gender'] : null;
						isset($data['fitness_goal']) ? $customer->fitness_goal = $data['fitness_goal'] : null;
						$customer->picture = "https://www.gravatar.com/avatar/".md5($data['email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
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

						Log::info('Customer Register : '.json_encode(array('customer_details' => $customer)));
						$response = $this->createToken($customer);
						$resp = $this->checkIfpopPup($customer);
						if($resp["show_popup"] == "true"){
							$response["extra"] = $resp;
						}
						return Response::json($response,200);
					}
				}else{
					$ishullcustomer->name = ucwords($data['name']);
					$ishullcustomer->password = md5($data['password']);
					$ishullcustomer->ishulluser = 0;
					$ishullcustomer->update();
					$customer_data = array('name'=>ucwords($ishullcustomer['name']),'email'=>$ishullcustomer['email'],'password'=>$ishullcustomer['password']);
					// $this->customermailer->register($ishullcustomer);

					Log::info('Customer Register : '.json_encode(array('customer_details' => $ishullcustomer)));
					$response = $this->createToken($ishullcustomer);
					$resp = $this->checkIfpopPup($ishullcustomer);
					if($resp["show_popup"] == "true"){
						$response["extra"] = $resp;
					}
					return Response::json($response,200);
				}	
			}else{

				$account_link= $customer['account_link'];
				$account_link[$data['identity']] = 1;
				$customer->name = ucwords($data['name']) ;
				$customer->email = $data['email'];

				isset($data['dob']) ? $customer->dob = $data['dob'] : null;
				isset($data['gender']) ? $customer->gender = $data['gender'] : null;
				isset($data['fitness_goal']) ? $customer->fitness_goal = $data['fitness_goal'] : null;
				$customer->picture = "https://www.gravatar.com/avatar/".md5($data['email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
				$customer->password = md5($data['password']);
				if(isset($data['contact_no'])){
					$customer->contact_no = $data['contact_no'];
				}
				$customer->account_link = $account_link;
				$customer->status = "1";
				$customer->update();

				$customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email'],'password'=>$data['password']);
				$this->customermailer->register($customer_data);

				Log::info('Customer Register : '.json_encode(array('customer_details' => $customer)));
				$response = $this->createToken($customer);
				$resp = $this->checkIfpopPup($customer);
				if($resp["show_popup"] == "true"){
					$response["extra"] = $resp;
				}
				return Response::json($response,200);
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
				$resp = $this->emailLogin($data);
				if(isset($resp["token"])){
					$response = $resp["token"];
					if($resp["popup"]["show_popup"] == "true"){
						$response["extra"] = $resp["popup"];
					}
				}else{
					$response = $resp;
				}
				return Response::json($response,$response['status']);
			}elseif($data['identity'] == 'google' || $data['identity'] == 'facebook' || $data['identity'] == 'twitter'){
				$resp = $this->socialLogin($data);
				if(isset($resp["token"])){
					$response = $resp["token"];
					if($resp["popup"]["show_popup"] == "true"){
						$response["extra"] = $resp["popup"];
					}
				}else{
					$response = $resp;
				}
				return Response::json($response,$response['status']);
			}else{
				return Response::json(array('status' => 400,'message' => 'The identity is incorrect'),400);
			}

		}else{

			return Response::json(array('status' => 400,'message' => 'The identity field is required'),400);
		}
	}

	public function emailLogin($data){

		$rules = [
		'email' => 'required|email',
		'password' => 'required'
		];
		Log::info($data);
		$validator = Validator::make($data = Input::json()->all(),$rules);

		if($validator->fails()) {
			return array('status' => 400,'message' =>$this->errorMessage($validator->errors()));  
		}

		$customer = Customer::where('email','=',$data['email'])->first();

		if(empty($customer)){
			return array('status' => 400,'message' => 'Customer does not exists');
		}

		$customer = Customer::where('email','=',$data['email'])->where('status','=','1')->first();

		if(empty($customer)){
			return array('status' => 400,'message' => 'Customer is inactive');
		}else{
			if($customer['ishulluser'] == 1){
				$customer->password = md5($data['password']);
				$customer->ishulluser = 0;
			}else{
				if($customer['password'] != md5($data['password'])){
					return array('status' => 400,'message' => 'Incorrect email or password');
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
		$resp = $this->checkIfpopPup($customer);
		
		return array("token" => $this->createToken($customer), "popup" => $resp);
	}

	public function checkIfpopPup($customer){
		$resp = array();
		if($customer["balance"] > 0 || $customer["balance_fitcash_plus"] > 0){
			$resp["show_popup"] = true;
			$resp["popup"]["header_image"] = "http://b.fitn.in/iconsv1/global/fitcash.jpg";
			$resp["popup"]["header_text"] = "Congratulations";
			if($customer["balance_fitcash_plus"] > 0){
				$resp["popup"]["text"] = "You have Rs. ".$customer["balance_fitcash_plus"]." in your wallet as FitCash+. You can use this across session and membership bookings at gyms in studios in Mumbai, Bangalore, Pune & Delhi";
			}else{
				$resp["popup"]["text"] = "You have Rs. ".$customer["balance"]." in your wallet as FitCash. You can use this across session and membership bookings at gyms in studios in Mumbai, Bangalore, Pune & Delhi";
			}
			$resp["popup"]["button"] = "Ok";
		}else{
			$resp["show_popup"] = false;
			$resp["popup"] = array();
		}
		return $resp;
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

				$customer = Customer::where('facebook_id','=',$data['facebook_id'])->first();

				if(empty($customer)){
					$customer = Customer::where('email','=',$data['email'])->first();
				}

				if(!empty($customer)){
					if(!isset($customer->email) || $customer->email == ''){
						$customer->email = $data['email'];
						$customer->update();
					}
				}

			}else{

				$rules = [
				'facebook_id' => 'required'
				];

				$validator = Validator::make($data = Input::json()->all(),$rules);

				if($validator->fails()) {
					return array('status' => 400,'message' =>$this->errorMessage($validator->errors()));  
				}else{

					$customer = Customer::where('facebook_id','=',$data['facebook_id'])->first();

					if(empty($customer) || !isset($customer->email) || $customer->email == ''){
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
			$customer->picture = 'https://graph.facebook.com/'.$data['facebook_id'].'/picture?type=large';
		}

		$customer->last_visited = Carbon::now();
		$customer->update();
		$resp = $this->checkIfpopPup($customer);
		return array("token" => $this->createToken($customer), "popup" => $resp);
		// return $this->createToken($customer);
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
			isset($data['dob']) ? $customer->dob = $data['dob'] : null;
			isset($data['gender']) ? $customer->gender = $data['gender'] : null;
			isset($data['fitness_goal']) ? $customer->fitness_goal = $data['fitness_goal'] : null;
			$customer->picture = (isset($data['picture'])) ? $data['picture'] : "";
			$customer->identity = $data['identity'];
			$customer->account_link = $account_link;

			if($data['identity'] == 'facebook' && isset($data['facebook_id'])){
				$customer->facebook_id = $data['facebook_id'];
				$customer->picture = 'https://graph.facebook.com/'.$data['facebook_id'].'/picture?type=large';
			}

			$customer->status = "1";
			$customer->save();

			$response = array('status' => 200,'customer'=>$customer);
		}

		return $response;
	}

	public function createToken($customer){

		$customer = array_except($customer->toArray(), array('password'));

		$customer['name'] = (isset($customer['name'])) ? $customer['name'] : "";
		$customer['email'] = (isset($customer['email'])) ? $customer['email'] : "";
		$customer['picture'] = (isset($customer['picture'])) ? $customer['picture'] : "";
		$customer['facebook_id'] = (isset($customer['facebook_id'])) ? $customer['facebook_id'] : "";
		$customer['address'] = (isset($customer['address'])) ? $customer['address'] : "";
		$customer['contact_no'] = (isset($customer['contact_no'])) ? $customer['contact_no'] : "";
		$customer['location'] = (isset($customer['location'])) ? $customer['location'] : "";
		$customer['extra']['mob'] = (isset($customer['contact_no'])) ? $customer['contact_no'] : "";
		$customer['extra']['location'] = (isset($customer['location'])) ? $customer['location'] : "";
		$customer['gender'] = (isset($customer['gender'])) ? $customer['gender'] : "";

		$data = array(
					'_id'=>$customer['_id'],
					'name'=>$customer['name'],
					"email"=>$customer['email'],
					"picture"=>$customer['picture'],
					'facebook_id'=>$customer['facebook_id'],
					"identity"=>$customer['identity'],
					"address"=>$customer['address'],
					"contact_no"=>$customer['contact_no'],
					"location"=>$customer['location'],
					'gender'=>$customer['gender'],
					'extra'=>array(
						'mob'=>$customer['extra']['mob'],
						'location'=>$customer['extra']['location']
					)
				);	

		$jwt_claim = array(
			"iat" => Config::get('app.jwt.iat'),
			"nbf" => Config::get('app.jwt.nbf'),
			"exp" => Config::get('app.jwt.exp'),
			"customer" => $data
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

		return Response::json($response,$response['status']);
	}

	public function createPasswordToken($customer){
		$password_claim = array(
			"iat" => Config::get('app.forgot_password.iat'),
			"exp" => Config::get('app.forgot_password.exp'),
			// "customer" => array('_id'=>$customer['_id'],'name'=>$customer['name'],"email"=>$customer['email'],"picture"=>$customer['picture'],'facebook_id'=>$customer['facebook_id'],"identity"=>$customer['identity'],'extra'=>array('mob'=>$mob,'location'=>$location))
			"customer" => array('_id'=>$customer['_id'],'name'=>$customer['name'],"email"=>$customer['email'],"picture"=>$customer['picture'],'facebook_id'=>$customer['facebook_id'],"identity"=>$customer['identity'])
			// "customer" => array('name'=>$customer['name'],"email"=>$customer['email'])
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

		$message = implode(',', array_values($message));

		return $message;
	}

	public function customerUpdate(){

		$jwt_token = Request::header('Authorization');
		$decodedToken = $this->customerTokenDecode($jwt_token);
		$variable = ['name',
		'email',
		'contact_no',
		'picture',
		'location',
		'gender',
		'shipping_address',
		'billing_address',
		'address',
		'interest',
		'dob',
		'ideal_workout_time',
		'preferred_workout_location',
		'recieve_update',
		'notification',
		'fitness_goal',
		'city',
		'place'];

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
			$token = $this->createToken($customer);
			return Response::json(array('status' => 200,'message' => $message.' updated successfull','token'=>$token),200);
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

	public function reviewListingByEmail($customer_email, $from = '', $size = ''){

		$customer = Customer::where('email',$customer_email)->first();

		if($customer){

			return $this->reviewListing($customer->_id,$from,$size);

		}else{

			return Response::json(array('status' => 400,'message' => 'customer not present'),400);
		}

	}

	public function reviewListing($customer_id, $from = '', $size = ''){
		
		$customer_id			= 	(int) $customer_id;	
		$from 				=	($from != '') ? intval($from) : 0;
		$size 				=	($size != '') ? intval($size) : 10;

		$reviews 			= 	Review::with(array('finder'=>function($query){$query->select('_id','title','slug','coverimage');}))->active()->where('customer_id','=',$customer_id)->take($size)->skip($from)->orderBy('_id', 'desc')->get();
		$responseData 		= 	['reviews' => $reviews,  'message' => 'List for reviews'];

		return Response::json($responseData, 200);
	}


	public function orderHistory($customer_email,$offset = 0, $limit = 10){
		
		$customer_email		= 	$customer_email;	
		$offset 			=	intval($offset);
		$limit 				=	intval($limit);

		$orders 			=  	[];
		$membership_types 		= Config::get('app.membership_types');

		$orderData 			= 	Order::active()->where('customer_email','=',$customer_email)->whereIn('type',$membership_types)->where('schedule_date','exists',false)->where(function($query){$query->orWhere('preferred_starting_date','exists',true)->orWhere('start_date','exists',true);})->skip($offset)->take($limit)->orderBy('_id', 'desc')->get();

		$action = [
			"button_text"=>"Renew Membership",
			"activity"=>"renew_membership",
			"color"=> "#EF1C26",
			"info" => "Renew Membership"
		];

		if(count($orderData) > 0){

			foreach ($orderData as $key => $value) {

				if(isset($value['finder_id']) && $value['finder_id'] != ''){
					$finderarr = Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
					->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->find(intval($value['finder_id']),['_id','title','slug','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile','finder_poc_for_customer_name','info','category_id','location_id','city_id','category','location','city','average_rating','total_rating_count']);
					if($finderarr){
						$value['finder'] = $finderarr;
					}
				}

				$value['renewal_flag'] = $this->checkRenewal($value);

				if(!isset($value['preferred_starting_date']) && isset($value['start_date'])){
					$value['preferred_starting_date'] = $value['start_date']; 
				}

				if(isset($value['amount_customer']) && $value['amount_customer'] != 0){
					$value['amount'] = $value['amount_customer'];
				}

				if(isset($value['end_date']) && strtotime($value['end_date']) >= time() && isset($value['duration_day']) && $value['duration_day'] < 360){
					$action = [
						"button_text"=>"Upgrade Membership",
						"activity"=>"upgrade_membership",
						"color"=>"#26ADE5",
						"info" => "Upgrade Membership"
					];
				}

				if(time() <= strtotime($value['created_at'].'+15 days')){
					$action = [
						"button_text"=>"Update Start Date",
						"activity"=>"update_starting_date",
						"color"=>"#7AB317",
						"info" => "Update Start Date"
					];
				}

				if(isset($value['duration_day']) && isset($value['start_date'])){

					$renewal_date = array();
					$validity = (int) $value['duration_day'];
					$start_date = $value['start_date'];

					if($validity >= 30 && $validity < 90){

						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(7)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(1)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->addDays(7)));

					}elseif($validity >= 90 && $validity < 180){

						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(30)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(15)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(7)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(1)));

					}elseif($validity >= 180){

						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(45)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(30)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(15)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(7)));
						$renewal_date[] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(1)));
					}

					$current_date = date('Y-m-d');

					if(in_array($current_date,$renewal_date)){

						$action = [
							"button_text"=>"Renew Membership",
							"activity"=>"renew_membership",
							"color"=>"#EF1C26",
							"info" => "Renew Membership"
						];

					}
				}

				/*if(isset($value['end_date']) && strtotime($value['end_date']) <= time()){
					$action = [
						"button_text"=>"Renew Membership",
						"activity"=>"renew_membership",
					];
				}*/


				$value["action"] = $action;

				array_push($orders, $value);

			}
		}

		$responseData 		= 	['orders' => $orders,  'message' => 'List for orders'];
		return Response::json($responseData, 200);
	}

	public function checkRenewal($order){

		$validity = 0;

		if(isset($order->ratecard_id) && $order->ratecard_id != ""){

			$ratecard = Ratecard::find($order->ratecard_id);

			if(isset($ratecard->validity) && $ratecard->validity != ""){
				$validity = (int)$ratecard->validity;
			}	
		}

		if(isset($order->duration_day) && $order->duration_day != ""){
			
			$validity = (int)$order->duration_day;
		}

		if(isset($order->preferred_starting_date) && $order->preferred_starting_date != ""){
						
			$start_date = $order->preferred_starting_date;
		}

		if(isset($order->start_date) && $order->start_date != ""){
			
			$start_date = $order->start_date;
		}

		$start_date = date('Y-m-d', strtotime($start_date));

		$renewal_date = "";

		$renewal_flag = false;

		if($validity >= 30 && $validity < 90){

			$renewal_date = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(7)));
		}

		if($validity >= 90 && $validity < 180){

			$renewal_date = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(15)));
		}

		if($validity >= 180){

			$renewal_date = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('Y-m-d', $start_date)->addDays($validity)->subDays(30)));
		}

		$current_date = date('Y-m-d');

		if($current_date >= $renewal_date){
			$renewal_flag = true;
		}

		return $renewal_flag;

	}

	public function getBookmarksByEmail($customer_email){

		$customer = Customer::where('email',$customer_email)->first();

		if($customer){

			return $this->getBookmarks($customer->_id);

		}else{

			return Response::json(array('status' => 400,'message' => 'customer not present'),400);
		}

	}


	public function getBookmarks($customer_id){
		
		$customer 			= 	Customer::where('_id', intval($customer_id))->first();
		$finderids 			= 	(isset($customer->bookmarks) && !empty($customer->bookmarks)) ? $customer->bookmarks : [];

		if(empty($finderids)){
			$responseData 		= 	['bookmarks' => [],  'message' => 'No bookmarks yet :)'];
			return Response::json($responseData, 200);
		}

		$bookmarksfinders = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->with('offerings')
		->whereIn('_id', $finderids)
		->with(array('city'=>function($query){$query->select('_id','name','slug');}))
		->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','offerings','photos','info'));

		$responseData 		= 	['bookmarksfinders' => $bookmarksfinders,  'message' => 'List for bookmarks'];
		return Response::json($responseData, 200);
	}

	public function updateBookmarksByEmail($customer_id, $finder_id, $remove = ''){

		$customer = Customer::where('email',$customer_email)->first();

		if($customer){

			return $this->getBookmarks($customer->_id,$finder_id,$remove);

		}else{

			return Response::json(array('status' => 400,'message' => 'customer not present'),400);
		}

	}

	public function updateBookmarks($customer_id, $finder_id, $remove = ''){

		$customer 			= 	Customer::where('_id', intval($customer_id))->first();
		$finderids 			= 	(isset($customer->bookmarks) && !empty($customer->bookmarks)) ? array_map('intval',$customer->bookmarks) : [];

		if($remove == ""){
			array_push($finderids, intval($finder_id));
			$message = 'bookmark added successfully';
		}else{
			if (in_array(intval($finder_id), $finderids)){
				unset($finderids[array_search(intval($finder_id),$finderids)]);
			}
			$message = 'bookmark revomed successfully';
		}

		$customer = Customer::find((int) $customer_id);
		$bookmarksdata = ['bookmarks' => array_unique($finderids)];
		$customer->update($bookmarksdata);

		$bookmarksfinders = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->whereIn('_id', array_unique($finderids))
		->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count'));
		$responseData 		= 	['bookmarksfinders' => $bookmarksfinders,  'message' => $message];

		return Response::json($responseData, 200);
	}

	public function getAllOrders($offset = 0, $limit = 10){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		return $this->orderHistory($decoded->customer->_id,$offset,$limit);
	}

	public function getAllBookmarks(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		$customer 			= 	Customer::where('_id', intval($decoded->customer->_id))->first();
		$finderids 			= 	(isset($customer->bookmarks) && !empty($customer->bookmarks)) ? $customer->bookmarks : [];

		if(empty($finderids)){
			$response 		= 	['status' => 200, 'bookmarks' => [],  'message' => 'No bookmarks yet :)'];
			return Response::json($response, 200);
		}

		$bookmarksfinders = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->with('offerings')
		->whereIn('_id', $finderids)
		->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','offerings'));

		$response 		= 	['status' => 200, 'bookmarksfinders' => $bookmarksfinders,  'message' => 'List for bookmarks'];
		return Response::json($response, 200);
	}

	public function getAllReviews($offset = 0, $limit = 10){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		$reviews 			= 	Review::with(array('finder'=>function($query){$query->select('_id','title','slug','coverimage');}))->active()->where('customer_id',$decoded->customer->_id)->skip($offset)->take($limit)->orderBy('_id', 'desc')->get();

		$response 		= 	['status' => 200,'reviews' => $reviews,  'message' => 'List for reviews'];

		return Response::json($response, 200);
	}

	public function getAllTrials(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		return $this->getAutoBookTrials($decoded->customer->email);

	// $selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt');

	// $trials 		=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
	// ->where('customer_email', '=', $decoded->customer->email)
	// ->whereIn('booktrial_type', array('auto'))
	// ->orderBy('_id', 'desc')
	// ->get($selectfields)->toArray();

	// if(!$trials){
	// 	return $this->responseNotFound('Customer does not exist');
	// }

	// if(count($trials) < 1){
	// 	$response 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
	// 	return Response::json($response,200);
	// }

	// $customertrials  = 	$trial = array();
	// $currentDateTime =	\Carbon\Carbon::now();

	// foreach ($trials as $trial){
	// 	$scheduleDateTime 				=	Carbon::parse($trial['schedule_date_time']);
	// 	$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 0) ? false : true;
	// 	array_set($trial, 'passed', $slot_datetime_pass_status);
	// 	array_push($customertrials, $trial);
	// }

	// $response 	= 	array('status' => 200,'trials' => $customertrials,'message' => 'List of scheduled trials');
	// return Response::json($response,200);
	}

	public function getUpcomingTrials(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		$customeremail = $decoded->customer->email;

		$data = array();

		$trials 		=	Booktrial::where('customer_email', '=', $customeremail)->where('going_status_txt','!=','cancel')->where('booktrial_type','auto')->where('schedule_date_time','>=',new DateTime())->orderBy('schedule_date_time', 'asc')->select('finder','finder_name','service_name', 'schedule_date', 'schedule_slot_start_time','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon','finder_id','schedule_date_time','what_i_should_carry','what_i_should_expect','code')->first();

		$resp 	= 	array('status' => 400,'data' => $data);

		if($trials){

			$data = $trials->toArray();

			$data['finder_average_rating'] = 0;

			$finder = Finder::find((int) $data['finder_id']);

			if($finder){

				$finder = $finder->toArray();

				if(isset($finder['average_rating'])){

					$data['finder_average_rating'] = $finder['average_rating'];
				}
			}

			foreach ($data as $key => $value) {

				$data[$key] = ucwords(strip_tags($value));
			}

			if(isset($data['schedule_slot_start_time'])){
				$data['schedule_slot_start_time'] = strtoupper($data['schedule_slot_start_time']);
			}

			$resp 	= 	array('status' => 200,'data' => $data);
		}

		return Response::json($resp,$resp['status']);

	}

	public function editBookmarks($finder_id, $remove = ''){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		$customer_id = $decoded->customer->_id;

		$customer 			= 	Customer::where('_id', intval($customer_id))->first();
		$finderids 			= 	(isset($customer->bookmarks) && !empty($customer->bookmarks)) ? array_map('intval',$customer->bookmarks) : [];

		if($remove == ""){
			array_push($finderids, intval($finder_id));
			$message = 'bookmark added successfully';
		}else{
			if (in_array(intval($finder_id), $finderids)){
				unset($finderids[array_search(intval($finder_id),$finderids)]);
			}
			$message = 'bookmark revomed successfully';
		}

		$customer = Customer::find((int) $customer_id);
		$bookmarksdata = ['bookmarks' => array_unique($finderids)];
		$customer->update($bookmarksdata);

		$bookmarksfinders = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->whereIn('_id', array_unique($finderids))
		->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count'));
		$responseData 		= 	['bookmarksfinders' => $bookmarksfinders,  'message' => $message];

		return Response::json($responseData, 200);
	}

	public function customerDetailByEmail($customer_email){

		$customer = Customer::where('email',$customer_email)->first();	

		if($customer){

			return $this->customerDetail($customer->_id);

		}else{

			return Response::json(array('status' => 400,'message' => 'customer not present'),400);
		}

	}

	public function customerDetail($customer_id){

		$array = array('name'=>NULL,
			'email'=>NULL,
			'contact_no'=>NULL,
			'picture'=>NULL,
			'location'=>NULL,
			'gender'=>NULL,
			'shipping_address'=>NULL,
			'billing_address'=>NULL,
			'address'=>NULL,
			'interest'=>[],
			'dob'=>NULL,
			'ideal_workout_time'=>NULL,
			'preferred_workout_location'=>NULL,
			'recieve_update'=>NULL,
			'notification'=>NULL,
			'fitness_goal'=>NULL,
			'city'=>NULL,
			'place'=>NULL
			);

		$customer = Customer::where('_id',(int) $customer_id)->get(array('name',
			'email',
			'contact_no',
			'picture',
			'location',
			'gender',
			'shipping_address',
			'billing_address',
			'address',
			'interest',
			'dob',
			'ideal_workout_time',
			'identity',
			'preferred_workout_location',
			'recieve_update',
			'notification',
			'fitness_goal',
			'city',
			'place'))->toArray();


		if($customer){

			foreach ($array as $key => $value) {

				if(array_key_exists($key, $customer[0]))
				{
					continue;
				}else{
					$customer[0][$key] = $value;
				}

			}

			$response 	= 	array('status' => 200,'customer' => $customer[0],'message' => 'Customer Details');

		}else{

			$response 	= 	array('status' => 400,'message' => 'Customer not found');
		}

		return Response::json($response, $response['status']);

	}

	public function getCustomerDetail(){

		$jwt_token = Request::header('Authorization');
		//Log::info($jwt_token);
		$decoded = $this->customerTokenDecode($jwt_token);

		$customer_id = $decoded->customer->_id;

		return $this->customerDetail($customer_id);

	}

	public function getCustomerTransactions(){

		$jwt_token = Request::header('Authorization');
		//Log::info($jwt_token);
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;
		$customer_email = $decoded->customer->email;

		$free_vip_trials_count = $trials_count = $memberships_count = $workout_sessions_count = 0;

		$all_trials = $this->utilities->getCustomerTrials($customer_email)['result'];

		$vip_trial_types 		= Config::get('app.vip_trial_types');
		$trial_types 			= Config::get('app.trial_types');
		$membership_types 		= Config::get('app.membership_types');
		$workout_session_types 	= Config::get('app.workout_session_types');


		foreach($all_trials as $key=>$value){
			$trials_count += $value['count'];
			in_array((string)$value['_id'], $vip_trial_types) ? $free_vip_trials_count += $value['count'] : 0;
			in_array((string)$value['_id'], $workout_session_types) ? $workout_sessions_count += $value['count'] : 0;
		}

		$memberships_count = Order::active()->where('customer_email',$customer_email)->where('schedule_date','exists',false)->whereIn('type',$membership_types)->count();

		$responseData =  array(
			'free_vip_trials_count'=>$free_vip_trials_count,
			'trials_count'=>$trials_count,
			'memberships_count'=>$memberships_count,
			'workout_sessions_count'=>$workout_sessions_count,
			);

		return	array('status' => 200,'data' => $responseData);

	}
	
	
	public function forYou($customer_email,$city_id,$lat = false,$lon = false){

		//blogs catgories
		$cardio = 1;
		$strength = 2;
		$nutrition = 3;
		$flexibility = 4;
		$recipes = 5;
		$lifestyle = 6;
		$eating_out = 7;
		$relationships = 8;
		$mind = 9;
		$general = 10;

		//finder categories
		$gyms = array('id'=>5,'name'=>'gyms','blog_category'=>array($cardio,$strength,$general));
		$yoga = array('id'=>6,'name'=>'yoga','blog_category'=>array($flexibility,$general));
		$zumba = array('id'=>12,'name'=>'zumba','blog_category'=>array($cardio,$general));
		$cross_functional_training = array('id'=>35,'name'=>'cross_functional_training','blog_category'=>array($strength,$general));
		$dance = array('id'=>7,'name'=>'dance','blog_category'=>array($cardio,$general));
		$fitness_studios = array('id'=>43,'name'=>'fitness_studios','blog_category'=>array($cardio,$strength,$general,$flexibility));
		$crossfit = array('id'=>32,'name'=>'crossfit','blog_category'=>array($flexibility,$strength,$general));
		$pilates = array('id'=>11,'name'=>'pilates','blog_category'=>array($flexibility,$strength,$general));
		$mma_and_kick_boxing = array('id'=>8,'name'=>'mma_and_kick_boxing','blog_category'=>array($cardio,$strength,$general,$flexibility));
		$spinning = array('id'=>14,'name'=>'spinning','blog_category'=>array($cardio,$general));
		$healthy_tiffins = array('id'=>42,'name'=>'healthy_tiffins','blog_category'=>array($nutrition,$recipes,$eating_out,$general));
		$marathon_training = array('id'=>36,'name'=>'marathon_training','blog_category'=>array($cardio,$general));
		$healthy_snacks_and_beverages = array('id'=>45,'name'=>'healthy_snacks_and_beverages','blog_category'=>array($nutrition,$recipes,$eating_out,$general));
		$swimming = array('id'=>10,'name'=>'swimming','blog_category'=>array($cardio,$general));
		$dietitians_and_nutritionists = array('id'=>25,'name'=>'dietitians_and_nutritionists','blog_category'=>array($nutrition,$recipes,$eating_out,$general));
		$aerobics = array('id'=>9,'name'=>'aerobics','blog_category'=>array($cardio));


		$categories = array($gyms,$yoga,$zumba,$cross_functional_training,$dance,$fitness_studios,$crossfit,$pilates,$mma_and_kick_boxing,$spinning,$healthy_tiffins,$marathon_training,$healthy_snacks_and_beverages,$swimming,$dietitians_and_nutritionists,$aerobics);

		//$categories = array('5'=>$gyms,'6'=>$yoga,'12'=>$zumba,'35'=>$cross_functional_training,'7'=>$dance,'43'=>$fitness_studios,'32'=>$crossfit,'11'=>$pilates,'8'=>$mma_and_kick_boxing,'14'=>$spinning,'42'=>$healthy_tiffins,'36'=>$marathon_training,'45'=>$healthy_snacks_and_beverages,'10'=>$swimming,'25'=>$dietitians_and_nutritionists,'9'=>$aerobics);

		//echo"<pre>";print_r($categories);


		$interest = Customer::where('email',$customer_email)->where('interest', 'exists', true)->lists('interest');

		//echo"<pre>";print_r($interest);exit;

		$finder = $offer = $article = $blog_category_id = $location_id = $category_id = array();

		$limit = 5;

		if($lat == false && $lon == false){

			$city = City::find((int)$city_id);

			if(isset($city->lat) && $city->lat != "" && isset($city->lon)  && $city->lon != ""){


				$lat = (float)$city->lat;
				$lon = (float)$city->lon;

			}else{

				$lat = 19.1154900;
				$lon = 72.8726951;

			}
		}

		$lonlat = [(float)$lon,(float)$lat];

		//echo "<pre>";print_r($lonlat);exit;

		$location_id = Location::where('lonlat','near',$lonlat)->take($limit)->lists('_id');

				//echo "<pre>";print_r($location_id);exit;


		if(!empty($interest)){

			$interest = $interest[0];

			foreach ($categories as $key => $value) {

				if(in_array($value['id'], $interest)){
					
					$blog_category_id = array_merge($blog_category_id,$value['blog_category']);
				}
				
			}

			$blog_category_id = array_unique($blog_category_id);

			$category_id  = Findercategory::active()->whereIn('name',$interest)->lists('_id');


			//blogs
			$article_query = Blog::with(array('author'=>function($query){$query->select('_id','name');}))->active();

			if(!empty($blog_category_id)){

				$article_query->whereIn('category_id',$blog_category_id);
			}

			$article = $article_query->take($limit)->get()->toArray();


			//finder id
			$finder_id_query = Finder::active();

			if(!empty($category_id)){
				$finder_id_query->whereIn('category_id',$category_id);
			}

			if(!empty($location_id)){

				$finder_id_query->whereIn('location_id',$location_id);
			}

			$finder_id = $finder_id_query->lists('_id');


			//offers and finder
			$offer_query = Serviceoffer::where('type',"mobile-only")->with(array('service'=>function($query){$query->select('_id','finder_id','name','lat','lon','address','show_on','status')->where('status','=','1')->orderBy('ordering', 'ASC');}));

			$finder_query = Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
			->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
			->with(array('location'=>function($query){$query->select('_id','name','slug');}))
			->with('categorytags')
			->with('locationtags')
			->with(array('services'=>function($query){$query->select('_id','finder_id','name','lat','lon','address','show_on','status','trialschedules')->where('status','=','1')->orderBy('ordering', 'ASC');}));

			if(!empty($finder_id)){

				$finder_query->whereIn('_id',$finder_id);

				$offer_query->whereIn('finder_id',$finder_id);
			}

			$offer = $offer_query->take($limit)->get()->toArray();

			$finder = $finder_query->take($limit)->get(array('_id','location_id','category_id','categorytags','locationtags','title','city_id','total_rating_count','average_rating','slug'))->toArray();

			foreach ($finder as $finder_key => $finder_value) {

				foreach ($finder_value['categorytags'] as $key => $value) {

					unset($finder[$finder_key]['categorytags'][$key]['finders']);
					unset($finder[$finder_key]['categorytags'][$key]['cities']);
				}

				foreach ($finder_value['locationtags'] as $key => $value) {

					unset($finder[$finder_key]['locationtags'][$key]['finders']);
					unset($finder[$finder_key]['locationtags'][$key]['cities']);
				}

				foreach ($finder_value['services'] as $key => $value) {

					unset($finder[$finder_key]['services'][$key]['serviceratecard']);
				}

			}

			foreach ($offer as $offer_key => $offer_value) {

				// echo "<pre>";print_r($offer_value);exit();

				if(isset($offer_value['service']) && !empty($offer_value['service'])){

					foreach ($offer_value['service'] as $key => $value) {

						unset($offer[$offer_key]['service']['serviceratecard']);
					}

					$offer_finder = Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
					->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->with('categorytags')
					->with('locationtags')
					->find((int) $offer_value['finder_id'],array('_id','location_id','category_id','categorytags','locationtags','title','city_id','total_rating_count','average_rating','slug'))
					->toArray();

					foreach ($offer_finder['categorytags'] as $key => $value) {

						unset($offer_finder['categorytags'][$key]['finders']);
						unset($offer_finder['categorytags'][$key]['cities']);
					}

					foreach ($offer_finder['locationtags'] as $key => $value) {

						unset($offer_finder['locationtags'][$key]['finders']);
						unset($offer_finder['locationtags'][$key]['cities']);
					}

					$offer[$offer_key]['finder'] = $offer_finder;
				}

			}

		}else{

			//finder id
			$finder_id_query = Finder::active();

			if(!empty($location_id)){

				$finder_id_query->whereIn('location_id',$location_id);
			}

					//echo "<pre>";print_r($location_id);exit;

			$finder_id = $finder_id_query->lists('_id');

					//echo "<pre>";print_r($finder_id);exit;

			$offer_query = Serviceoffer::where('type',"mobile-only")->with(array('service'=>function($query){$query->select('_id','finder_id','name','lat','lon','address','show_on','status')->where('status','=','1')->orderBy('ordering', 'ASC');}));

			$finder_query = Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
			->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
			->with(array('location'=>function($query){$query->select('_id','name','slug');}))
			->with('categorytags')
			->with('locationtags')
			->with(array('services'=>function($query){$query->select('_id','finder_id','name','lat','lon','address','show_on','status','trialschedules')->where('status','=','1')->orderBy('ordering', 'ASC');}));

			if(!empty($finder_id)){

				$finder_query->whereIn('_id',$finder_id);

				$offer_query->whereIn('finder_id',$finder_id);
			}

			$finder = $finder_query->take($limit)->get(array('_id','location_id','category_id','categorytags','locationtags','title','slug','city_id'))->toArray();

			$offer = $offer_query->take($limit)->get()->toArray();

			$article = Blog::with(array('author'=>function($query){$query->select('_id','name');}))->active()->take($limit)->get()->toArray();

			foreach ($finder as $finder_key => $finder_value) {

				foreach ($finder_value['categorytags'] as $key => $value) {

					unset($finder[$finder_key]['categorytags'][$key]['finders']);
					unset($finder[$finder_key]['categorytags'][$key]['cities']);
				}

				foreach ($finder_value['locationtags'] as $key => $value) {

					unset($finder[$finder_key]['locationtags'][$key]['finders']);
					unset($finder[$finder_key]['locationtags'][$key]['cities']);
				}

				foreach ($finder_value['services'] as $key => $value) {

					unset($finder[$finder_key]['services'][$key]['serviceratecard']);
				}

			}

			foreach ($offer as $offer_key => $offer_value) {

				if(isset($offer_value['service'])){

					foreach ($offer_value['service'] as $key => $value) {

						unset($offer[$offer_key]['service']['serviceratecard']);
					}
				}

				$offer_finder = Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with('categorytags')
				->with('locationtags')
				->find((int) $offer_value['finder_id'],array('_id','location_id','category_id','categorytags','locationtags','title','city_id','total_rating_count','average_rating'))
				->toArray();

				foreach ($offer_finder['categorytags'] as $key => $value) {

					unset($offer_finder['categorytags'][$key]['finders']);
					unset($offer_finder['categorytags'][$key]['cities']);
				}

				foreach ($offer_finder['locationtags'] as $key => $value) {

					unset($offer_finder['locationtags'][$key]['finders']);
					unset($offer_finder['locationtags'][$key]['cities']);
				}

				$offer[$offer_key]['finder'] = $offer_finder;

			}

		}

		$return = array('finder'=>$finder,'offer'=>$offer,'article'=>$article, 'meta' => ['finder_url' => Config::get('app.s3_finderurl'),'article_url' => Config::get('app.s3_articleurl')]);

		return $return;

	}


	public function isRegistered($email,$id = false,$collection = false){

		$customer = Customer::where('email',$email)->first();	

		if($customer){

			return Response::json(array('status' => 200,'message' => 'registered'),200);

		}else{

			$data = array();

			if($id){

				if($collection){

					if($collection == 'trial'){

						$trial = Booktrial::find((int) $id);

						if($trial){

							$data['name'] = $trial->customer_name;
							$data['email'] = $trial->customer_email;
							$data['contact_no'] = $trial->customer_phone;

						}

					}elseif($collection == 'order'){

						$order = Order::find((int) $id);

						if($order){

							$data['name'] = $order->customer_name;
							$data['email'] = $order->customer_email;
							$data['contact_no'] = $order->customer_phone;

						}

					}

				}else{

					$trial = Booktrial::find((int) $id);

					if($trial){

						$data['name'] = $trial->customer_name;
						$data['email'] = $trial->customer_email;
						$data['contact_no'] = $trial->customer_phone;

					}

				}

			}

			return Response::json(array('status' => 201,'message' => 'not registered','data'=>$data),201);
		}

	}

	public function addRegId(){

		$data = Input::json()->all();

		$response = add_reg_id($data);

		return Response::json($response,$response['status']);
	}

	public function autoRegisterCustomer($data){

		$customer 		= 	Customer::active()->where('email', $data['customer_email'])->first();

		if(!$customer) {

			$inserted_id = Customer::max('_id') + 1;
			$customer = new Customer();
			$customer->_id = $inserted_id;
			$customer->name = ucwords($data['customer_name']) ;
			$customer->email = $data['customer_email'];
			$customer->picture = "https://www.gravatar.com/avatar/".md5($data['customer_email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
			$customer->password = md5(time());
			$customer->gender = $data['gender'];

			if(isset($data['customer_phone'])  && $data['customer_phone'] != ''){
				$customer->contact_no = $data['customer_phone'];
			}

			if(isset($data['customer_address'])){

				if(is_array($data['customer_address']) && !empty($data['customer_address'])){

					$customer->address = implode(",", array_values($data['customer_address']));
					$customer->address_array = $data['customer_address'];

				}elseif(!is_array($data['customer_address']) && $data['customer_address'] != ''){

					$customer->address = $data['customer_address'];
				}

			}

			$customer->identity = 'email';
			$customer->account_link = array('email'=>1,'google'=>0,'facebook'=>0,'twitter'=>0);
			$customer->status = "1";
			$customer->ishulluser = 1;
			$customer->save();

			return $inserted_id;

		}else{

			$customerData = [];

			try{

				if(isset($data['customer_phone']) && $data['customer_phone'] != ""){
					$customerData['contact_no'] = trim($data['customer_phone']);
				}

				if(isset($data['otp']) &&  $data['otp'] != ""){
					$customerData['contact_no_verify_status'] = "yes";
				}

				if(isset($data['gender']) && $data['gender'] != ""){
					$customerData['gender'] = $data['gender'];
				}

				if(isset($data['customer_address'])){

					if(is_array($data['customer_address']) && !empty($data['customer_address'])){

						$customerData['address'] = implode(",", array_values($data['customer_address']));
						$customerData['address_array'] = $data['customer_address'];

					}elseif(!is_array($data['customer_address']) && $data['customer_address'] != ''){

						$customerData['address'] = $data['customer_address'];
					}

				}

				if(count($customerData) > 0){
					$customer->update($customerData);
				}

			} catch(ValidationException $e){

				Log::error($e);

			}

			return $customer->_id;
		}

	}

	public function addHealthInfo(){

		$data = Input::json()->all();

		$customer_info = new CustomerInfo();

		$customer_id = (isset($data['customer_id']) && $data['customer_id'] != "") ? $data['customer_id'] : $this->autoRegisterCustomer($data);

		$data['customer_id'] = (int)$customer_id;

		$response = $customer_info->addHealthInfo($data);

		return Response::json($response,$response['status']);

	}


	public function listWalletSummary($limit=0,$offset=10){

		$request = Input::json()->all();

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;
		$request['customer_id'] = $customer_id;
		//Log::info($jwt_token);
		$wallet = array();

		$wallet = Customerwallet::where('customer_id',$request['customer_id'])
		->where('amount','!=',0)
		->orderBy('_id', 'DESC')
		->skip($limit)
		->take($offset)
		->get();

		$wallet_balance = 0;

		if(count($wallet) > 0){

			$wallet = $wallet->toArray();

			foreach ($wallet as $key => $value) {

				if(!isset($value['order_id'])){
					$wallet[$key]['order_id'] = 0;
				}
			}

			$balance = (isset($wallet[0]['balance']) && $wallet[0]['balance'] != "") ? (int) $wallet[0]['balance'] : 0 ;
			$balance_fitcash_plus = (isset($wallet[0]['balance_fitcash_plus']) && $wallet[0]['balance_fitcash_plus'] != "") ? (int) $wallet[0]['balance_fitcash_plus'] : 0 ;

			$wallet_balance = $balance + $balance_fitcash_plus;
		}

		return Response::json(
			array(
				'status' => 200,
				'data' => $wallet,
				'wallet_balance'=>$wallet_balance,
				),200

			);
	}

	public function getWalletBalance(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = intval($decoded->customer->_id);

		$customer = Customer::find($customer_id);
		$balance = (isset($customer['balance']) && $customer['balance'] != "") ? (int) $customer['balance'] : 0 ;
		$balance_fitcash_plus = (isset($customer['balance_fitcash_plus']) && $customer['balance_fitcash_plus'] != "") ? (int) $customer['balance_fitcash_plus'] : 0 ;

		$customer_balance = $balance + $balance_fitcash_plus;

		// balance and transaction_allowed are same at this time........
		return 	Response::json(
					array(
						'status' => 200,
						'balance' => $customer_balance,
						'transaction_allowed' => $customer_balance
					),200
				);
	}
	
	public function getExistingTrialWithFinder(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_email = $decoded->customer->email;
		$customer_phone = $decoded->customer->contact_no;

		$rules = [
		'finder_id' => 'required|integer|numeric'
		];

		$data = Input::all();

		$validator = Validator::make($data,$rules);
		if ($validator->fails()) {
			return Response::json(
				array(
					'status' => 400,
					'message' => $this->errorMessage($validator->errors()
						)),400
				);
		}
		$result =  $this->utilities->checkExistingTrialWithFinder($customer_email, $customer_phone, $data['finder_id']);

		return Response::json(
			array(
				'status' => 200,
				'data' => $result
				),200

			);
	}

	public function getInteractedFinder(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_email = $decoded->customer->email;

		$orders = array();
		$orders = Order::where('customer_email',$customer_email)->orderBy('_id', 'desc')->get(array('finder_id','created_at'));

		$interaction_data = array();
		$date_array = array();

		if(count($orders) > 0){
			$orders = $orders;
			
			foreach ($orders as $key => $item) {

				$data['finder_id'] = (int)$item->finder_id;
				$data['interaction_time'] = strtotime($item->created_at);

				array_push($interaction_data, $data);
				array_push($date_array, strtotime($item->created_at));
			}
		}

		$booktrials = array();
		$booktrials = Booktrial::where('customer_email',$customer_email)->orderBy('_id', 'desc')->get(array('finder_id','created_at'));

		if(count($booktrials) > 0){
			$booktrials = $booktrials;

			foreach ($booktrials as $key => $item) {

				$data['finder_id'] = (int)$item->finder_id;
				$data['interaction_time'] = strtotime($item->created_at);
				
				array_push($interaction_data, $data);
				array_push($date_array, strtotime($item->created_at));
			}
		}

		array_multisort($date_array, SORT_DESC, $interaction_data);

		$finder_id = array();

		foreach ($interaction_data as $value) {

			if(!in_array($value['finder_id'], $finder_id)){
				$finder_id[] = $value['finder_id'];
			}
		}

		$data = array();

		foreach ($finder_id as $id) {

			$finder = Finder::select('title','average_rating','slug','city_id','city','coverimage')->find((int)$id);//->select('title','average_rating','slug','city_id','city','coverimage');

			if($finder){
				$data[] = $finder;
			}
		}

		return $data;

	}


	function array_sort_by_column($arr, $col, $dir = SORT_DESC) {
		
		$sort_col = array();
		foreach ($arr as $key=> $row) {
			$sort_col[$key] = $row[$col];
		}

		echo "<pre>";print_r($sort_col);exit;

		return array_multisort($sort_col, $dir, $arr);
	}

	public function home($city = 'mumbai',$cache = true){

		$jwt_token = Request::header('Authorization');
		$upcoming = array();
		
		$city = strtolower($city);

		if($jwt_token != ""){

			try {

				$decoded = $this->customerTokenDecode($jwt_token);
				$customeremail = $decoded->customer->email;

				$trials = Booktrial::where('customer_email', '=', $customeremail)->where('going_status_txt','!=','cancel')->where('booktrial_type','auto')->where('schedule_date_time','>=',new DateTime())->orderBy('schedule_date_time', 'asc')->select('finder','finder_name','service_name', 'schedule_date', 'schedule_slot_start_time','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon','finder_id','schedule_date_time','what_i_should_carry','what_i_should_expect','code')->get();

				if(count($trials) > 0){

					foreach ($trials as $trial) {

						$data = array();

						$data = $trial->toArray();

						$data['finder_average_rating'] = 0;

						$finder = Finder::find((int) $data['finder_id']);

						if($finder){

							$finder = $finder->toArray();

							if(isset($finder['average_rating'])){

								$data['finder_average_rating'] = $finder['average_rating'];
							}
						}

						foreach ($data as $key => $value) {

							$data[$key] = ucwords(strip_tags($value));
						}

						if(isset($data['schedule_slot_start_time'])){
							$data['schedule_slot_start_time'] = strtoupper($data['schedule_slot_start_time']);
						}

						$upcoming[] = $data;

					}
				}

			} catch (Exception $e) {
				Log::error($e);
			}
			
		}

		if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "android") && isset($_GET['app_version']) && ((float)$_GET['app_version'] >= 2.5)){

			$category_slug = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"marathon-training","dance","cross-functional-training","mma-and-kick-boxing","swimming","pilates",/*"personal-trainers",*/"luxury-hotels","healthy-snacks-and-beverages","spinning-and-indoor-cycling","healthy-tiffins","dietitians-and-nutritionists","sport-nutrition-supliment-stores","aerobics");

			$cache_tag = 'customer_home_by_city_2_5';

		}else{

			$category_slug = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"marathon-training","dance","cross-functional-training","mma-and-kick-boxing","swimming","pilates"/*,"personal-trainers","luxury-hotels"*/,"healthy-snacks-and-beverages","spinning-and-indoor-cycling","healthy-tiffins","dietitians-and-nutritionists"/*,"sport-nutrition-supliment-stores"*/);

			$cache_tag = 'customer_home_by_city';

		}


		if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "ios")){

			$category_slug = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"marathon-training","dance","cross-functional-training","mma-and-kick-boxing","swimming","pilates","luxury-hotels","healthy-snacks-and-beverages","spinning-and-indoor-cycling","healthy-tiffins","dietitians-and-nutritionists","sport-nutrition-supliment-stores");

			$cat = array();

			$cat['mumbai'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"marathon-training","dance","cross-functional-training","mma-and-kick-boxing","swimming","pilates","luxury-hotels","healthy-snacks-and-beverages","spinning-and-indoor-cycling","healthy-tiffins","dietitians-and-nutritionists","sport-nutrition-supliment-stores");

			$cat['pune'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"pilates","healthy-tiffins","cross-functional-training","mma-and-kick-boxing","dance","spinning-and-indoor-cycling","sport-nutrition-supliment-stores","aerobics");

			$cat['bangalore'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"pilates","healthy-tiffins","cross-functional-training","mma-and-kick-boxing","dance","spinning-and-indoor-cycling","sport-nutrition-supliment-stores");

			$cat['delhi'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"pilates","healthy-tiffins","cross-functional-training","mma-and-kick-boxing","dance","spinning-and-indoor-cycling","sport-nutrition-supliment-stores");

			$cat['gurgaon'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"pilates","healthy-tiffins","cross-functional-training","mma-and-kick-boxing","dance","spinning-and-indoor-cycling","sport-nutrition-supliment-stores");

			$cat['noida'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"mma-and-kick-boxing","dance");

			if(isset($cat[$city])){
				$category_slug = $cat[$city];
			}

			$cache_tag = 'customer_home_by_city_ios';

		}

		$customer_home_by_city = $cache ? Cache::tags($cache_tag)->has($city) : false;

		if(!$customer_home_by_city){

			$category = $locations = $popular_finders =	$recent_blogs =	array();
			$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));

			if(!$citydata){
				if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "ios")){
					$citydata['name'] 		= 	"mumbai";
					$citydata['_id']		= 	1;
				}else{
					return $this->responseNotFound('City does not exist');
				}
				
			}

			$city_name 		= 	$citydata['name'];
			$city_id		= 	(int) $citydata['_id'];

			$category			= 		Findercategory::active()->where('cities',$city_id)->whereIn('slug',$category_slug)->get(array('name','_id','slug'))->toArray();
			
			$ordered_category = array();

			foreach ($category_slug as $category_slug_key => $category_slug_value){

				foreach ($category as $category_key => $category_value){

					if($category_value['slug'] == $category_slug_value){

						$category_value['name'] = ucwords($category_value['name']);

						$ordered_category[] = $category_value;
						break;
					}
				}
			}

			$locations				= 		Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug','location_group'));

			$collections 			= 	Findercollection::active()->where('city_id', '=', intval($city_id))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));	
			
			$homedata 				= 	array('categorytags' => $ordered_category,
				'locations' => $locations,
				'city_name' => $city_name,
				'city_id' => $city_id,
				'collections' => $collections,
				'banner' => 'http://b.fitn.in/c/welcome/1.jpg'
				);

			Cache::tags($cache_tag)->put($city,$homedata,Config::get('cache.cache_time'));
		}

		$result             = Cache::tags($cache_tag)->get($city);
		$result['upcoming'] = $upcoming;

		// $result['campaign'] =  new \stdClass();

		// $result['campaign'] = array(
		// 	'image'=>'http://b.fitn.in/iconsv1/womens-day/women_banner_app_50.png',
		// 	'link'=>'fitternity://www.fitternity.com/search/offer_available/true',
		// 	'title'=>'FitStart 2017',
		// 	'height'=>1,
		// 	'width'=>6,
		// 	'ratio'=>1/6
		// );

		if(isset($_REQUEST['device_type']) && $_REQUEST['device_type'] == "ios" ){
			$result['campaign'] =  new \stdClass();
			$result['campaign'] = array(
				'image'=>'http://b.fitn.in/iconsv1/fitmania/sale_banner.png',
				'link'=>'',
				'title'=>'FitStart 2017',
				'height'=>1,
				'width'=>6,
				'ratio'=>1/6
			);
		}

		return Response::json($result);
	}


	public function captureMyReward(){

		$data = Input::all();

        if(!isset($data['customer_id'])){

            $jwt_token = Request::header('Authorization');
            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
            $data['customer_id'] = $customer_id;

        }else{
            $customer_id = $data['customer_id'] ;
        }

		$customer = Customer::find((int)$customer_id);

		if(isset($data['customer_address']) && is_array($data['customer_address']) && !empty($data['customer_address'])){

			$customerData['address'] = $data['customer_address'];
			$customer->update($customerData);

			$customer_address = "";
            $customer_address .= (isset($data['customer_address']['line1']) && $data['customer_address']['line1'] != "") ? $data['customer_address']['line1'] : "";
            $customer_address .= (isset($data['customer_address']['line2']) && $data['customer_address']['line2'] != "") ? ", ".$data['customer_address']['line2'] : "";
            $customer_address .= (isset($data['customer_address']['line3']) && $data['customer_address']['line3'] != "") ? ", ".$data['customer_address']['line3'] : "";
            $customer_address .= (isset($data['customer_address']['landmark']) && $data['customer_address']['landmark'] != "") ? ", ".$data['customer_address']['landmark'] : "";
            $customer_address .= (isset($data['customer_address']['pincode']) && $data['customer_address']['pincode'] != "") ? ", ".$data['customer_address']['pincode'] : "";

            $data['customer_address'] = $customer_address;

		}

		if(isset($data['gender']) && $data['gender'] != ""){

			$customerData['gender'] = $data['gender'];
			$customer->update($customerData);
		}

		$token = $this->createToken($customer);

		$response  = $this->customerreward->createMyRewardCapture($data);
		$response['token'] = $token;

		return Response::json($response,$response['status']);

	}

	public function transformation(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;

		$rules = [
		'customer_id' => 'required',
			//'weight' => 'required',
			//'energy_level' => 'required',
			//'record'=>'required',
		'image'=>'image'
		];

		$data = Input::all();

		$data['customer_id'] = $customer_id;

		unset($data['image']);

		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);
		}else{

			$transformationid 		=	Transformation::max('_id') + 1;

			if (Input::hasFile('image')) {

				$image_detail = array(
					array('type'=>'cover','path'=>'customer/'.$customer_id.'/transformation/cover/','width'=>720),
					array('type'=>'thumb','path'=>'customer/'.$customer_id.'/transformation/thumb/','width'=>256),
					);

				$image = array('input' => Input::file('image'),'detail' => $image_detail,'id' => $transformationid);

				$image_response = upload_magic($image);

				foreach ($image_response['response'] as $key => $value){

					if(isset($value['success']) && $value['success']){

						$image_success['width'] = $value['kraked_width'];
						$image_success['height'] = $value['kraked_height'];
						$image_success['s3_url'] = $value['kraked_url'];
						$image_success['s3_folder_path'] = $value['folder_path'];
						$image_success['s3_file_path'] = $value['folder_path'].$image_response['image_name'];
						$image_success['name'] = $image_response['image_name'];

						$data[$value['type']] = $image_success;
					}

				}

			}

			$duration = (isset($data['record']['duration']) && $data['record']['duration'] != "") ? (int)$data['record']['duration'] : "";
			$duration_type = (isset($data['record']['duration_type']) && $data['record']['duration_type'] != "") ? $data['record']['duration_type'] : "";
			$duration_day = 0;

			if($duration != "" && $duration_type != ""){

				switch ($dutarion_type){

					case 'day': $duration_day = $duration; break;
					case 'week': $duration_day = ($duration*7); break;
					case 'month': $duration_day = ($duration*30); break;
					default : break;

				}

			}

			if($duration_day != 0){

				$data['reminder_schedule_date'] = date('Y-m-d 00:00:00', strtotime('+'.$duration_day.' days'));

			}

			$data['duration'] = $duration;
			$data['duration_type'] = $duration_type;

			Transformation::where('status','1')->where('customer_id',$customer_id)->update(array('status'=>'0'));

			$transformation 		= 	new Transformation($data);
			$transformation->_id 	= 	$transformationid;
			$transformation->status = 	"1";
			$transformation->save();

			return Response::json(array('status' => 200,'message' => "Transformation added Successfull",'data'=>$transformation),200);
		}
		
	}

	
	public function stayOnTrack(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;

		$rules = [
		'customer_id' => 'required',
			//'weekdays' => 'required',
			//'session_time' => 'required',
			//'wake_up_by'=>'required',
			//'wake_up_time'=>'required'
		];

		$data = Input::json()->all();

		$data['customer_id'] = $customer_id;

		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);
		}else{

			Stayontrack::where('status','1')->where('customer_id',$customer_id)->update(array('status'=>'0'));

			$stayontrackid 		=	Stayontrack::max('_id') + 1;
			$stayontrack 		= 	new Stayontrack($data);
			$stayontrack->_id 	= 	$stayontrackid;
			$stayontrack->status = 	"1";
			$stayontrack->save();

			return Response::json(array('status' => 200,'message' => "StayOnTrack added Successfull"),200);
		}

	}


	public function getTransformation(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;

		$transformation = array();

		$transformation = Transformation::where('customer_id',$customer_id)->orderBy('_id','desc')->get();

		return Response::json(array('status' => 200,'message' => "Customer transformation List",'data'=>$transformation),200);

	}

	public function getStayOnTrack(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;

		$stayontrack = array();

		$stayontrack = Stayontrack::where('customer_id',$customer_id)->orderBy('_id','desc')->get();

		return Response::json(array('status' => 200,'message' => "Customer StayOnTrack List",'data'=>$stayontrack),200);

	}

	public function downloadApp(){

		$rules = [
		'phone' => 'required',
		'device_type' => 'required'
		];

		$data = Input::json()->all();

		$data['url'] = "https://www.fitternity.com/downloadapp";

		$shorten_url = new ShortenUrl();

		$url = $shorten_url->getShortenUrl($data['url']);

		if(isset($url['status']) &&  $url['status'] == 200){
			$data['url'] = $url['url'];
		}

		$validator = Validator::make($data,$rules);

		if($validator->fails()) {
			return array('status' => 401,'message' =>$this->errorMessage($validator->errors()));  
		}

		$this->customersms->downloadApp($data);

		return array('status' => 200,'message' =>"SMS Sent");

	}

	public function forceUpdate(){

		$rules = [
		'app_version' => 'numeric|required',
		'device_type' => 'required'
		];

		$data = $_REQUEST;

		$validator = Validator::make($data,$rules);

		if($validator->fails()) {

			return Response::json(array('status' => 401,'message' =>$this->errorMessage($validator->errors())),401);
		}

		$current_version_android = 3.5;
		$current_version_ios = 2.3;

		if($data["device_type"] == "android"){

			$result_android = array(
				//"message" => "Version ".$current_version_android." is available on Play Store",
				"message" => "Update is available on Play Store",
				"force_update" => false
			);

			if(floatval($data["app_version"]) < $current_version_android){

				$result_android['force_update'] = true;
			}

			return Response::json($result_android,200);
		}

		$result_ios = array(
			"title" => "Version ".$current_version_ios." is available on App Store",
			"description" => "Version ".$current_version_ios." is available on App Store",
			"force_update" => false,
			"available_version" => $current_version_ios,
		);

		if(floatval($data["app_version"]) < $current_version_ios){

			$result_ios['force_update'] = true;
		}

		return Response::json(array('status' => 200,'data' => $result_ios),200);

	}

	public function appConfig(){

		$app_version = Request::header('App-Version');
		$device_type = Request::header('Device-Type');

		$current_version_android = 3.5;
		$current_version_ios = 2.3;
		$force_update_android = false;
		$force_update_ios = false;
		
		$api['city'] = 1476350961;
		$api['home'] = 1476350961;

		if($device_type == "android"){

			if($app_version < $current_version_android){

				$force_update_android = true;
			}
		}

		$result_android = array(
			"title" => "Version ".$current_version_android." is available on Play Store",
			"description" => "Version ".$current_version_android." is available on Play Store",
			"force_update" => $force_update_android,
			"available_version" => $current_version_android,
			);

		if($device_type == "ios"){

			if($app_version < $current_version_ios){

				$force_update_ios = true;
			}
		}

		$result_ios = array(
			"title" => "Version ".$current_version_ios." is available on App Store",
			"description" => "Version ".$current_version_ios." is available on App Store",
			"force_update" => $force_update_ios,
			"available_version" => $current_version_ios,
			"share_message" => "Share With Friends"
			);

		$data = ($device_type == 'ios') ? $result_ios : $result_android;

		$data['api'] = $api;

		return Response::json(array('status' => 200,'data' => $data),200);

	}

	public function applyPromotionCode(){
		// return time();
		// $valid_promotion_codes		=		['fitgift','in2017','befit'];
		$data 						= 		Input::json()->all();
		
		if(empty(Request::header('Authorization'))){
			$resp 	= 	array('status' => 400,'message' => "Customer Token Missing");
			return  Response::json($resp, 400);
		}

		if(empty($data['code'])){
			$resp 	= 	array('status' => 400,'message' => "Promotion Code Missing - code");
			return  Response::json($resp, 400);
		}

		$code 			= 	trim(strtolower($data['code']));

		$fitcashcode  = Fitcashcoupon::where('code',$code)->where("expiry",">",time())->first();


		if (!isset($fitcashcode) || $fitcashcode == "") {
			$resp 	= 	array('status' => 404,'message' => "Invalid Promotion Code");
			return Response::json($resp,404);
		}

		if(Request::header('Authorization')){
			$decoded          				=       decode_customer_token();
			$customer_id 					= 		intval($decoded->customer->_id);

			$already_applied_promotion 		= 		Customer::where('_id',$customer_id)->whereIn('applied_promotion_codes',[$code])->count();

			if($already_applied_promotion > 0){
				$resp 	= 	array('status' => 400,'message' => "You have already applied promotion code");
				return  Response::json($resp, 400);
			}

			$customer_update 	=	Customer::where('_id', $customer_id)->push('applied_promotion_codes', $code, true);
			$amounttobeadded = 0;
			if($customer_update){
				// switch($code){
				// 	case "fitgift" :  $amounttobeadded = 2000;
				// 	break;
				// 	case "in2017" :  $amounttobeadded = 2000;
                //     break;
				// }
				$amounttobeadded = $fitcashcode['amount'];
				$customer 	=	Customer::find($customer_id);				


				$customerwallet 		= 		Customerwallet::where('customer_id',$customer_id)->orderBy('_id', 'desc')->first();
				if($customerwallet){
					$customer_balance 	=	$customerwallet['balance'] + $amounttobeadded;				
					$customer_balance_fitcashplus = $customerwallet['balance_fitcash_plus'];
				}else{
					$customer_balance 	=	 $amounttobeadded;
					$customer_balance_fitcashplus = 0;
				}
				
				$cashback_amount 	=	$amounttobeadded;
				$walletData = array(
					"customer_id"=> $customer_id,
					"amount"=> $cashback_amount,
					"type"=>'CASHBACK',
					"code"=>	$code,
					"balance"=>	$customer_balance,
					"balance_fitcash_plus"=>$customer_balance_fitcashplus,
					"description"=>'CASHBACK ON Promotion amount - '.$cashback_amount
					);
				if($fitcashcode['type'] == "restricted"){
					$walletData["vendor_id"] = $fitcashcode['vendor_id'];
					$vb = array("vendor_id"=>$fitcashcode['vendor_id'],"balance"=>$amounttobeadded);
					$customer_update = Customer::where('_id', $customer_id)->push('vendor_balance', $vb, true);
				}
				if($fitcashcode['type'] == "fitcashplus"){
					$walletData["type"] = "FITCASHPLUS";
					if($customerwallet){
						$walletData["balance"] = $customerwallet['balance'];
						$walletData["balance_fitcash_plus"] = $customerwallet['balance_fitcash_plus'] + $amounttobeadded;				
					}else{
						$walletData["balance"] = 0;
						$walletData["balance_fitcash_plus"] = $amounttobeadded;
					}
					$walletData["description"] = "Added Fitcash Plus on PROMOTION Rs - ".$cashback_amount;
				}
				$customer_balance = $walletData["balance"];
				$customer_balance_fitcashplus = $walletData["balance_fitcash_plus"];
				// return $walletData;

				$wallet               	=   new CustomerWallet($walletData);
				$last_insertion_id      =   CustomerWallet::max('_id');
				$last_insertion_id      =   isset($last_insertion_id) ? $last_insertion_id :0;
				$wallet->_id          	=   ++ $last_insertion_id;
				$wallet->save();
				$customer_update 	=	Customer::where('_id', $customer_id)->update(['balance' => intval($customer_balance),'balance_fitcash_plus' => intval($customer_balance_fitcashplus)]);

				$resp 	= 	array('status' => 200,'message' => "Thank you. Rs ".$amounttobeadded." has been successfully added to your fitcash wallet", 'walletdata' => $wallet);
				return  Response::json($resp, 200);	
			}
		}
	}

	public function emailOpened(){

		$data = $_REQUEST;

		Log::info('Customer Email Open : '.json_encode($data));

		$emailTracking = false;

		if(isset($data['booktrial_id']) && $data['booktrial_id'] != ""){

			$emailTracking = Emailtracking::where('customer_id',$data['customer_id'])->where('label',$data['label'])->where('booktrial_id',$data['booktrial_id'])->first();
		}

		if(isset($data['order_id']) && $data['order_id'] != ""){

			$emailTracking = Emailtracking::where('customer_id',$data['customer_id'])->where('label',$data['label'])->where('order_id',$data['order_id'])->first();
		}


		if($emailTracking){

			$emailTracking->count = $emailTracking->count + 1;
			$emailTracking->update();

		}else{

			$emailTracking = new Emailtracking($data);
			$emailTracking->count = 1;
			$emailTracking->save();
		}

		return Response::json(array('status' => 200,'message' => 'Email Opened'),200);

	}


	public function customerstatus(){
		$data = Input::json()->all();
		if(!isset($data['email']) || $data['email'] == ""){
			return Response::json(array('status' => 400,'message' => 'Bad Request. Email Missing'),400);
		}
		$customer = Customer::where('email',$data['email'])->first();
		if(isset($customer)){
			if($customer['ishulluser'] != 0){
				$resp = array('registered'=>false,'name'=>$customer['name']);
				return Response::json(array('status' => 200,'data' => $resp),200);		
			}else{
				if($customer['account_link']['facebook'] == 1){
					$resp = array('registered'=>true,'facebook'=>true,'name'=>$customer['name']);
				}else{
					$resp = array('registered'=>true,'name'=>$customer['name']);
				}
				return Response::json(array('status' => 200,'data' => $resp),200);
			}
		}else{
			$resp = array('registered'=>false);
			return Response::json(array('status' => 200,'data' => $resp),200);
		}
		return $customer;
	}

	public function customerLoginOtp(){
		$data = Input::json()->all();
		$customer = Customer::where("contact_no",$data['phone'])->first();
		if(empty($customer)){
			$otp = genericGenerateOtp();
			$customerdata = array(
                'customer_name' => "",
                'customer_email' => "newregister@fitternity.com",
                'customer_phone' => $data['phone'],
				'action' => 'login',
				'otp' => $otp
            );
			$temp = new Temp($customerdata);
			$temp->verified = "N";
			$temp->save();
			$this->customersms->genericOtp($customerdata);
			return Response::json(array('status' => 400,'message' => 'This number is not registered with us','temp_id'=>$temp->_id),200);
		}else{
			$otp = genericGenerateOtp();
			$customer->lastOtp = $otp;
			$customer->save();
			$customerdata = array(
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->contact_no,
				'action' => 'login',
				'otp' => $otp
            );
			$temp = new Temp($customerdata);
			$temp->verified = "N";
			$temp->save();
			$this->customersms->genericOtp($customerdata);
			return $response =  array('status' => 200,'message'=>'OTP Created Successfull','temp_id'=>$temp->_id);
		}
	}

	public function displayEmi(){
		$bankNames=array();
		$bankList= array();
	 	$emiStruct = Config::get('app.emi_struct');
		$data = Input::json()->all();
		$response = array(
			"bankList"=>array(),
			"emiData"=>array(),
			"higerMinVal" => array()
			);
		foreach ($emiStruct as $emi) {
			if(isset($data['bankName']) && !isset($data['amount'])){
				if($emi['bankName'] == $data['bankName']){
					if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
					Log::info("inside1");
					$emiData = array();
						$emiData['total_amount'] =  "";
						$emiData['emi'] ="";
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
					array_push($response['emiData'], $emiData);
				}
			
			}elseif(isset($data['bankName'])&&isset($data['amount'])){
					if($emi['bankName'] == $data['bankName'] && $data['amount']>=$emi['minval']){
						Log::info("inside2");
						$emiData = array();
						if(!in_array($emi['bankName'], $bankList)){
							array_push($bankList, $emi['bankName']);
						}
						$emiData['total_amount'] =  (string)round($data['amount']*(100+$emi['rate'])/100, 2);
						$emiData['emi'] =(string)round($emiData['total_amount']/$emi['bankTitle'], 2);
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
						array_push($response['emiData'], $emiData);
					}elseif($emi['bankName'] == $data['bankName']){
						$emiData = array();
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['minval'] = (string)$emi['minval'];
						array_push($response['higerMinVal'], $emiData);
						break;
					}
			}elseif(isset($data['amount']) && !(isset($data['bankName']))){
				if($data['amount']>=$emi['minval']){
					if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
					Log::info("inside3");
					$emiData = array();
					$emiData['total_amount'] =  (string)round($data['amount']*(100+$emi['rate'])/100, 2);
					$emiData['emi'] =(string)round($emiData['total_amount']/$emi['bankTitle'], 2);
					$emiData['months'] = (string)$emi['bankTitle'];
					$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
					$emiData['rate'] = (string)$emi['rate'];
					$emiData['minval'] = (string)$emi['minval'];
					array_push($response['emiData'], $emiData);
				}else{
					$key = array_search($emi['bankName'], $bankNames);
					if(!is_int($key)){
						array_push($bankNames, $emi['bankName']);
						$emiData = array();
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['minval'] = (string)$emi['minval'];
						array_push($response['higerMinVal'], $emiData);
					}
				}
			}else{
				if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
				Log::info("inside4");
				$emiData = array();
						$emiData['total_amount'] =  "";
						$emiData['emi'] ="";
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)(string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
				array_push($response['emiData'], $emiData);
			}
		}
		$response['bankList'] = $bankList;
	    return $response;
	}


}