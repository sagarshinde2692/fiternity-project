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
use App\Mailers\FinderMailer as FinderMailer;

class CustomerController extends \BaseController {

	protected $customermailer;
	protected $customersms;
	protected $utilities;


	public function __construct(
		CustomerMailer $customermailer,
		CustomerSms $customersms,
		Utilities $utilities,
		CustomerReward $customerreward,
		FinderMailer $findermailer
	) {
		parent::__construct();

		$this->customermailer	=	$customermailer;
		$this->customersms	=	$customersms;
		$this->utilities	=	$utilities;
		$this->customerreward = $customerreward;
		$this->findermailer             =   $findermailer;

		$this->vendor_token = false;

        $vendor_token = Request::header('Authorization-Vendor');

        if($vendor_token){

            $this->vendor_token = true;
        }

        $this->error_status = ($this->vendor_token) ? 200 : 400;

	}

	public function getBooktrialsListingQueryRes($customeremail, $selectfields, $offset, $limit, $deviceType='website', $type='lte') {
		if($deviceType=='website'){

			// returning cancelled trials as well, for website...

			return Booktrial::where('customer_email', '=', $customeremail)
			->where('third_party_details','exists',false)
			->whereIn('booktrial_type', array('auto'))
			->with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
			->with(array('invite'=>function($query){$query->get(array('invitee_name', 'invitee_email','invitee_phone','referrer_booktrial_id'));}))
			->where('schedule_date_time',$type=='lte'?'<=':'>',new MongoDate(strtotime('-90 minutes')))
			->orderBy('schedule_date_time', $type=='lte'?'desc':'asc')->skip($offset)->take($limit)
			->get($selectfields);
		}
		return Booktrial::where('customer_email', '=', $customeremail)
		->where('third_party_details','exists',false)
			->whereIn('booktrial_type', array('auto'))
			->with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
			->with(array('invite'=>function($query){$query->get(array('invitee_name', 'invitee_email','invitee_phone','referrer_booktrial_id'));}))
			->where('going_status_txt','!=','cancel')
			->where('schedule_date_time',$type=='lte'?'<=':'>',new MongoDate(strtotime('-90 minutes')))
			->orderBy('schedule_date_time', $type=='lte'?'desc':'asc')->skip($offset)->take($limit)
			->get($selectfields);
	}

	public function getBooktrialsListTimewise($trials, $deviceType='website', $type='upcoming') {
		$bookings = [];
		$currentDateTime =	\Carbon\Carbon::now();
		$hour2 = 60*60*2;
		foreach($trials as $trial){
			array_set($trial, 'type_text', 'Trial');

			if($trial['type'] == 'workout-session'){
				array_set($trial, 'type_text', 'Workout Session');
			}
			if(!empty($trial['studio_extended_validity_order_id'])){
				Order::$withoutAppends = true;
				$order = Order::where('_id', $trial['studio_extended_validity_order_id'])->first(['_id', 'studio_extended_validity', 'studio_sessions', 'studio_membership_duration']);
				if(!empty($order['studio_sessions'])){
					$avail = $order['studio_sessions']['total_cancel_allowed'] - $order['studio_sessions']['cancelled'];
					$avail = ($avail<0)?0:$avail;
					array_set($trial, 'type_text', 'WS');
					if($avail>0){
						$trial['finder_name'] .= ' - '.$avail.'/'.$order['studio_sessions']['total_cancel_allowed'].' cancellations available';
					}
					else {
						$trial['finder_name'] .= ' - No cancellations available';
					}

				}
			}

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
			
			if(empty($trial['studio_extended_validity_order_id']) && !empty($trial['studio_extended_session'])){
				$trial['fit_code'] = $this->utilities->fitCode($trial);
			}

			$trial['interaction_date'] = strtotime($trial['created_at']);

			if($type=='upcoming') {
				$scheduleDateTime 				=	Carbon::parse($trial['schedule_date_time']);
				$time_diff = strtotime($scheduleDateTime) - strtotime($currentDateTime);

				$going_status_txt = ['rescheduled','cancel'];

				if(!isset($trial['going_status_txt'])){
					$trial['going_status_txt'] = "-";
				}

				if(!isset($trial['amount'])){
					$trial['amount'] = 0;
				}

				if(isset($trial['amount']) && ($trial['amount'] == "-" || $trial['amount'] == "")){
					$trial['amount'] = 0;
				}

				if(!isset($trial['going_status'])){
					$trial['going_status'] = 0;
				}

				if(isset($trial['going_status']) && ($trial['going_status'] == "-" || $trial['going_status'] == "")){
					$trial['going_status'] = 0;
				}

				if($time_diff <= $hour2){
					$reschedule_enable = false;
				}elseif(in_array($trial['going_status_txt'], $going_status_txt) || $trial['amount_finder'] > 0  || $trial['type'] == 'workout-session'){
					$reschedule_enable = false;
				}else{
					$reschedule_enable = true;
				}

				if(!isset($trial['going_status_txt'])){
					$reschedule_enable = false;
				}

				// $upcoming_trials_date_array[] = strtotime($trial['created_at']);
			
				array_set($trial, 'reschedule_enable', $reschedule_enable);

				if(isset($trial['payment_done']) && $trial['payment_done']){

					array_set($trial, 'payment_pending', true);

				}
			}
			$trial['fitcash_text'] = "Enter your Fitcode to get Fitcash";
			try{
				$trial['fitcash_text'] = "Enter your Fitcode to get  Rs.".$this->utilities->getFitcash($trial)." Fitcash.";
			}catch(Exception $e){
				Log::info($e);
			}
			if(!empty($trial['studio_extended_validity_order_id'])){
				$trial['fitcash_text'] = '';
			}
			array_push($bookings, $trial);
		}
		return $bookings;
	}

    // Listing Schedule Tirals for Normal Customer
	public function getAutoBookTrials($customeremail, $offset = 0, $limit = 12){

		$offset 			=	intval($offset);
		$limit 				=	intval($limit);
		$deviceType = (isset($_GET['device_type']))?$_GET['device_type']:"website";

		$selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt','service_id','what_i_should_carry','what_i_should_expect','origin','trial_attended_finder', 'type','amount','created_at', 'amount_finder','vendor_code','post_trial_status', 'payment_done','manual_order','customer_id', 'studio_extended_validity_order_id');

		// if(isset($_GET['device_type']) && $_GET['device_type'] == "website"){

			// $trials = Booktrial::where('customer_email', '=', $customeremail)
			// ->whereIn('booktrial_type', array('auto'))
			// ->with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
			// ->with(array('invite'=>function($query){$query->get(array('invitee_name', 'invitee_email','invitee_phone','referrer_booktrial_id'));}))
			// ->orderBy('_id', 'asc')->skip($offset)->take($limit)
			// ->get($selectfields);

			$upcomingTrialsQuery = $this->getBooktrialsListingQueryRes($customeremail, $selectfields, $offset, $limit, $deviceType, 'gt');
			$pastTrialsQuery = $this->getBooktrialsListingQueryRes($customeremail, $selectfields, $offset, $limit, $deviceType, 'lte');

		// }else{
		// 	$upcomingTrialsQuery = $this->getBooktrialsListingQueryRes($customeremail, $selectfields, $offset, $limit, 'gt');
		// 	$pastTrialsQuery = $this->getBooktrialsListingQueryRes($customeremail, $selectfields, $offset, $limit, 'lte');
		// }

		if(count($upcomingTrialsQuery) < 0 && count($pastTrialsQuery) < 0){
			$resp 	= 	array('status' => 200,'trials' => [],'message' => 'No trials scheduled yet :)');
			return Response::json($resp,200);
		}else{
			$upcomingTrialsQuery->toArray();
			$pastTrialsQuery->toArray();
		}

		$customertrials  = 	$trial = array();
		$currentDateTime =	\Carbon\Carbon::now();
		$upcomingtrials = [];
		$passedtrials = [];
		$healthytiffintrail = [];
		$passed_trials_date_array = [];
		$upcoming_trials_date_array = [];

		$hour = 60*60;
		$hour12 = 60*60*12;
		$hour2 = 60*60*2;
		
		$passedtrials = $this->getBooktrialsListTimewise($pastTrialsQuery, $deviceType, 'past');
		$upcomingtrials = $this->getBooktrialsListTimewise($upcomingTrialsQuery, $deviceType, 'upcoming');
		// $passed_trials_date_array[] = strtotime($trial['created_at']);

		



		

		$healthytiffintrails = array();

			$ht_selectfields 	=	array('type','finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt','service_id','what_i_should_carry','what_i_should_expect','origin','preferred_starting_date','amount','status','order_action','created_at','customer_address');

			$healthytiffintrails = Order::where('customer_email',$customeremail)
			->where('type','healthytiffintrail')
			->orWhere(function($query){$query->where('status',"1")->where('order_action','bought')->where('amount','!=',0);})
			->orWhere(function($query){$query->where('status',"0")->where('amount','exist',false);})
			->with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
			->orderBy('_id', 'asc')->skip($offset)->take($limit)
			->get($ht_selectfields);

			if(count($healthytiffintrails) > 0){

				$healthytiffintrails = $healthytiffintrails->toArray();

				foreach ($healthytiffintrails as $key => $healthytiffintrail) {

					foreach ($selectfields as $field) {

						if(!isset($healthytiffintrail[$field])){
							$healthytiffintrail[$field] = "";
						}

						if(isset($healthytiffintrail['preferred_starting_date'])){

							$healthytiffintrail['schedule_date_time'] = $healthytiffintrail['preferred_starting_date'];
							$healthytiffintrail['schedule_date'] = $healthytiffintrail['preferred_starting_date'];

							unset($healthytiffintrail['preferred_starting_date']);
						}

						if(isset($healthytiffintrail['amount'])){

							unset($healthytiffintrail['amount']);
						}

						if(isset($healthytiffintrail['status'])){

							unset($healthytiffintrail['status']);
						}

						if(isset($healthytiffintrail['order_action'])){

							unset($healthytiffintrail['order_action']);
						}

						if(!isset($healthytiffintrail['going_status'])){
							$healthytiffintrail['going_status'] = 0;
						}

						if(isset($healthytiffintrail['going_status']) && ($healthytiffintrail['going_status'] == "-" || $healthytiffintrail['going_status'] == "")){
							$healthytiffintrail['going_status'] = 0;
						}

					}

					$healthytiffintrail['interaction_date'] = strtotime($healthytiffintrail['created_at']);


					$scheduleDateTime 				=	Carbon::parse($healthytiffintrail['schedule_date_time']);
					$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 0) ? false : true;

					if($slot_datetime_pass_status){

						array_push($passedtrials, $healthytiffintrail);
						
						$passed_trials_date_array[] = strtotime($healthytiffintrail['created_at']);
						
					}else{

						$time_diff = strtotime($scheduleDateTime) - strtotime($currentDateTime);

						$going_status_txt = ['rescheduled','cancel'];

						if(!isset($healthytiffintrail['going_status_txt'])){
							$healthytiffintrail['going_status_txt'] = "-";
						}

						if(!isset($healthytiffintrail['amount'])){
							$healthytiffintrail['amount'] = 0;
						}

						if(isset($healthytiffintrail['amount']) && $healthytiffintrail['amount'] == "-"){
							$healthytiffintrail['amount'] = 0;
						}

						if($time_diff <= $hour2){
							$reschedule_enable = false;
						}elseif(in_array($healthytiffintrail['going_status_txt'], $going_status_txt) || $healthytiffintrail['amount'] > 0  || $healthytiffintrail['type'] == 'workout-session'){
							$reschedule_enable = false;
						}else{
							$reschedule_enable = true;
						}

						if(!isset($healthytiffintrail['going_status_txt'])){
							$reschedule_enable = false;
						}

						$upcoming_trials_date_array[] = strtotime($healthytiffintrail['created_at']);
					
						array_set($healthytiffintrail, 'reschedule_enable', $reschedule_enable);

						array_push($upcomingtrials, $healthytiffintrail);	
					}

				}
			}

		// if(count($upcomingtrials) > 0){// && count($upcoming_trials_date_array) > 0){

		// 	array_multisort($upcoming_trials_date_array, SORT_ASC, $upcomingtrials);
		// }

		// if(count($passedtrials) > 0){ // && count($passed_trials_date_array) > 0){
		// 	array_multisort($passed_trials_date_array, SORT_ASC, $passedtrials);
		// }

		// array_push($customertrials, $trial);
		$resp 	= 	array('status' => 200,'passedtrials' => $passedtrials,'upcomingtrials' => $upcomingtrials,'healthytiffintrail'=>[],'message' => 'List of scheduled trials');
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

		if(!$trial){
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


	public function customerExists($email){

		$response = [
			'status' => 400,
			'message'=>'Customer does not Exists'
		];

		Customer::$withoutAppends=true;

		$customer = Customer::active()->where('email',$email)->first();

		if($customer){

			$response = [
				'status' => 200,
				'message'=>'Customer Exists',
				'data'=>[
					'name'=>$customer['name'],
					'email'=>$customer['email'],
					'contact_no'=>$customer['contact_no'],
				]
			];
		}

		return Response::json($response);

	}

	public function register($data = null){

		if(empty($data)){
			$data = Input::json()->all();
		}

		Log::info('register',$data);

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

		if(!isset($data['contact_no']) && isset($data['mobile'])){
			$data['contact_no'] = $data['mobile'];
		}

		if ($validator->fails()) {

			return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),$this->error_status);

		}else{

			$customerNoEmail = Customer::active()->where('contact_no', substr($data['contact_no'], -10))
			->where(function($query) use($data) {
				$query->orWhere('email', 'exists', false)->orWhere('email','=','');
			})
			->first();

			if(!empty($customerNoEmail)){
				$checkIfEmailExists = Customer::active()
				->where('email',$data['email'])
				->first();
				if(empty($checkIfEmailExists)){
					Log::info('in no email customer register section...');
					$customerNoEmail->name = ucwords($data['name']);
					if(isset($data['password'])){
						$customerNoEmail->password = md5($data['password']);
					}
					$customerNoEmail->isnoemailreg = true;
					$customerNoEmail->referral_code = generateReferralCode($customerNoEmail->name);
					$customerNoEmail->old_customer = true;
					$customerNoEmail->email = $data['email'];
					$customerNoEmail->identity = $data['identity'];
					$customerNoEmail->demonetisation = time();
					$customerNoEmail->update();
					$customer_data = array('name'=>ucwords($customerNoEmail['name']),'email'=>$customerNoEmail['email']);
					if(isset($data['password'])){
						$customer_data['password'] = $customerNoEmail['password'];
					}

					Log::info('Customer Register : '.json_encode(array('customer_details' => $customerNoEmail)));

					$response = $this->createToken($customerNoEmail);
					$resp = $this->checkIfpopPup($customerNoEmail,$data);
					if($resp["show_popup"] == "true")
						$response["extra"] = $resp;
					

					$customer_id = $customerNoEmail->_id;

					$customer = $customerNoEmail;
				}
				else {
					return Response::json(array('status' => 400,'message' => 'The email-id is already registered'),400);
				}
			}
			else {
				Log::info('customerNoEmail: ', [$customerNoEmail]);
				$customer = Customer::active()->where('email','=',$data['email'])->where('identity','!=','email')->first();

				/*if($this->vendor_token && isset($data['contact_no']) && $data['contact_no'] != ""){

					$customer = Customer::where('email','=',$data['email'])->first();
				}*/
				
				if(empty($customer)){

					$ishullcustomer = Customer::active()->where('email','=',$data['email'])->where('ishulluser',1)->first();

					if(empty($ishullcustomer)){

						$new_validator = Validator::make($data, Customer::$rules);

						if ($new_validator->fails()) {

							return Response::json(array('status' => 401,'message' => $this->errorMessage($new_validator->errors())),$this->error_status);

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
							if(isset($data['password'])){
								$customer->password = md5($data['password']);
							}
							if(isset($data['contact_no'])){
								$customer->contact_no = $data['contact_no'];
							}
							$customer->identity = $data['identity'];
							
							$customer->account_link = $account_link;
							$customer->status = "1";
							$customer->demonetisation = time();
							$customer->referral_code = generateReferralCode($customer->name);
							$customer->old_customer = false;
							$customer->save();
							$customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email']);

							if(isset($data['password'])){
								$customer_data['password'] = $data['password'];
							}
							// $this->customermailer->register($customer_data);

							Log::info('Customer Register : '.json_encode(array('customer_details' => $customer)));

							$response = $this->createToken($customer);
								$resp = $this->checkIfpopPup($customer,$data);	
								if($resp["show_popup"] == "true")
									$response["extra"] = $resp;
						
							
							$customer_id = $customer->_id;
						}

					}else{

						$ishullcustomer->name = ucwords($data['name']);
						if(isset($data['password'])){
							$ishullcustomer->password = md5($data['password']);
						}
						$ishullcustomer->ishulluser = 0;
						$ishullcustomer->referral_code = generateReferralCode($ishullcustomer->name);
						$ishullcustomer->old_customer = true;
						$ishullcustomer->update();
						$customer_data = array('name'=>ucwords($ishullcustomer['name']),'email'=>$ishullcustomer['email']);
						if(isset($data['password'])){
							$customer_data['password'] = $ishullcustomer['password'];
						}

						Log::info('Customer Register : '.json_encode(array('customer_details' => $ishullcustomer)));

							$response = $this->createToken($ishullcustomer);
							$resp = $this->checkIfpopPup($ishullcustomer,$data);
							if($resp["show_popup"] == "true")
								$response["extra"] = $resp;
						
	// 						Log::info(" getAddWAlletArray:: ".print_r($this->getAddWAlletArray(["customer_id"=>$customer->_id,"amount"=>500,"description"=>("Added FitCash+ as Sign up Bonus for starter pack, Expires On : ".date('d-m-Y',time()+(86400*60))),"validity"=>(time()+(86400*60)),"for"=>"starter_pack"]),true));

						$customer_id = $ishullcustomer->_id;

						$customer = $ishullcustomer;
						
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
					if(isset($data['password'])){
						$customer->password = md5($data['password']);
					}
					if(isset($data['contact_no'])){
						$customer->contact_no = $data['contact_no'];
					}
					$customer->account_link = $account_link;
					$customer->status = "1";
					
						$customer->update();

					$customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email']);

					if(isset($data['password'])){
						$customer_data['password'] = $data['password'];
					}
					$this->customermailer->register($customer_data);

					Log::info('Customer Register : '.json_encode(array('customer_details' => $customer)));
					
						$response = $this->createToken($customer);
						$resp = $this->checkIfpopPup($customer);
						if($resp["show_popup"] == "true")
							$response["extra"] = $resp;
					
	// 					Log::info(" getAddWAlletArray:: ".print_r($this->getAddWAlletArray(["customer_id"=>$customer->_id,"amount"=>500,"description"=>("Added FitCash+ as Sign up Bonus for starter pack, Expires On : ".date('d-m-Y',time()+(86400*60))),"validity"=>(time()+(86400*60)),"for"=>"starter_pack"]),true));

					$customer_id = $customer->_id;

				}
			}

			$data["customer_id"] = (int)$customer_id;

			$this->addCustomerRegId($data);

			$response['customer_data'] = array_only($customer->toArray(), ['_id','name','email','contact_no','dob','gender']);
		
				$response['customer_data']["token"] = $response["token"];
				registerMail($data["customer_id"]);
		
			Log::info("Customer Register",$response);

			return Response::json($response,200);
		}
	}


	public function customerLogin(){

		$data = Input::json()->all();

		if(isset($data['vendor_login']) && $data['vendor_login']){

			return $this->vendorLogin($data);
		}

		if(isset($data['identity']) && !empty($data['identity'])){

			if($data['identity'] == 'email'){

				$resp = $this->emailLogin($data);

				if(isset($resp["token"])){
					$response = $resp["token"];
					if($resp["popup"]["show_popup"] == "true"){
						$response["extra"] = $resp["popup"];
					}else{
						$response["extra"] = array("show_popup" => false);
					}
				}else{
					$response = $resp;
				}

				if($response['status'] == 200 && isset($response['token']) && $response['token'] != ""){

					$customerTokenDecode = $this->customerTokenDecode($response['token']);
					$data["customer_id"] = (int)$customerTokenDecode->customer->_id;

					$this->addCustomerRegId($data);
				}

				if(isset($resp["customer_data"])){

					$response['customer_data'] = $resp["customer_data"];
				}

				if($response['status'] != 200){

					return Response::json($response,$this->error_status);
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

				if($response['status'] == 200 && isset($response['token']) && $response['token'] != ""){

					$customerTokenDecode = $this->customerTokenDecode($response['token']);

					$data["customer_id"] = (int)$customerTokenDecode->customer->_id;

					$this->addCustomerRegId($data);
				}
				// if(!empty($response['extra'])){
				// 	$response['extra']['popup'] = $response['extra'];
				// }
				return Response::json($response,$response['status']);
			}else{
				return Response::json(array('status' => 400,'message' => 'The identity is incorrect'),400);
			}

		}else{

			return Response::json(array('status' => 400,'message' => 'The identity field is required'),400);
		}
	}

	public function vendorLogin($data){

		$rules = [
			'email' => 'required|email',
			'password' => 'required'
		];

		$validator = Validator::make($data = Input::json()->all(),$rules);
		Log::info("Vendor login",$data);
		if($validator->fails()) {
			return array('status' => 400,'message' =>$this->errorMessage($validator->errors()));  
		}

		$kiosk_user = KioskUser::where('hidden',false)->where('type','kiosk')->where('email',$data['email'])->first();

		if($kiosk_user){

			if($kiosk_user['password'] != md5($data['password'])){

				return Response::json(array('status' => 400,'message' => 'Incorrect Password'),400);
			}

			$encodeKioskVendorToken = $this->encodeKioskVendorToken($kiosk_user);

			$header_array = [
		        "Device-Model"=>"",
		        "App-Version"=>"",
		        "Os-Version"=>"",
		        "Device-Serial"=>"",
		       "Device-Id"=>"",
		        "Mac-Address"=>"",
		    ];

		    foreach ($header_array as $header_key => $header_value) {

		        $value = Request::header($header_key);

		        if($value != "" && $value != null && $value != 'null'){
		           $header_array[$header_key] =  trim($value);
		        }
		        
		    }

		    $data = [];

		    $data['brand'] = $header_array['Device-Model'];
		    $data['os_version'] = $header_array['Os-Version'];
		    $data['app_version'] = $header_array['App-Version'];
		    $data['serialNumber'] = $header_array['Device-Serial'];
		    $data['device_id'] = $header_array['Device-Id'];
            $data['mac_address'] = $header_array['Mac-Address'];
		    $data['vendor_id'] = (int)$kiosk_user['finder_id'];
			Log::info("header_array");
			Log::info($header_array);

		    Finder::$withoutAppends=true;

		    $finder = Finder::find((int)$kiosk_user['finder_id']);

		    if($finder){
		    	$data['city_id'] = (int)$finder['city_id'];
		    }

		    $kiosk_tab = KioskTab::where('serialNumber',$data['serialNumber'])->first();

		    if($kiosk_tab){

		    	$data['old_vendor_id'] = (int)$kiosk_tab['vendor_id'];

		    	if($data['vendor_id'] != $kiosk_tab['vendor_id']){

                    $kiosk_tab->update($data);

		    		$this->findermailer->kioskTabVendorChange($data);
		    	}

		    	// $kiosk_tab->update($data);

		    }else{

		    	KioskTab::create($data);
		    }

			return Response::json($encodeKioskVendorToken,$encodeKioskVendorToken['status']);
		}

		return Response::json(array('status' => 400,'message' => 'Vendor Not Found'),400);

	}

	public function encodeKioskVendorToken($kiosk_user){

		Finder::$withoutAppends=true;

		$finder = Finder::with(array('location'=>function($query){$query->select('name','slug');}))->with(array('city'=>function($query){$query->select('name','slug');}))->find((int)$kiosk_user['finder_id']);

		$data = [
			'_id'=>$finder['_id'],
			'slug'=>$finder['slug'],
			'name'=>ucwords($finder['name']),
			'location'=>[
				'_id'=>$finder['location']['_id'],
				'name'=>ucwords($finder['location']['name']),
				'slug'=>$finder['location']['slug']
			],
			'city'=>[
				'_id'=>$finder['city']['_id'],
				'name'=>ucwords($finder['city']['name']),
				'slug'=>$finder['city']['slug']
			]
		];

		$jwt_claim = array(
			"iat" => Config::get('jwt.kiosk.iat'),
			"nbf" => Config::get('jwt.kiosk.nbf'),
			"exp" => Config::get('jwt.kiosk.exp'),
			"vendor" => $data
		);
		
		$jwt_key = Config::get('jwt.kiosk.key');
		$jwt_alg = Config::get('jwt.kiosk.alg');

        $token = JWT::encode($jwt_claim,$jwt_key,$jwt_alg);
        
		$primary_color = "#f8a81b";
		$white_lable = false;
        
        if((int)$finder['_id'] == 9932){
            
            $primary_color = "#F9CD0C";
			$white_lable = true;
        }

		return array('status' => 200,'message' => 'Successfull Login', 'token' => $token, 'finder_id'=> (int)$finder['_id'], 'primary_color'=>$primary_color, 'white_lable'=>$white_lable);
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

		$customer = Customer::where('email','=',$data['email'])->first();

		if(empty($customer)){
			return array('status' => 400,'message' => 'Customer does not exists');
		}

		$customer = Customer::where('email','=',$data['email'])->where('status','=','1')->orderBy('_id', 'DESC')->first();

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
		$cart_id=getCartOfCustomer(intval($customer->_id));
		if(!empty($cart_id))
		{
			$customer->cart_id=$cart_id;
		}
		$customer->update();

		
		$resp = $this->checkIfpopPup($customer);
		
		$customer_data = array_only($customer->toArray(), ['_id','name','email','contact_no','dob','gender']);
		
        $token = $this->createToken($customer);
		
		if($this->vendor_token && isset($data['contact_no']) && $data['contact_no'] != ""){
			
			setVerifiedContact($customer->_id, $data['contact_no']);
			$customer_data['contact_no'] = $data['contact_no'];
		
		}
		
		$customer_data['token'] = $token['token'];
		
		return array("token" => $token,"popup" => $resp,"customer_data"=>$customer_data);
	}

	public function checkIfpopPup($customer, $customdata=array()){

		$resp = array();

		$resp["show_popup"] = false;
		$resp["popup"] = array();

		if(count($customdata) == 0){
			if(isset($customer->demonetisation)){

				$current_wallet_balance = \Wallet::active()->where('customer_id',$customer->_id)->where('balance','>',0)->sum('balance');

				if($current_wallet_balance > 0){

					$resp["show_popup"] = true;
					$resp["popup"]["header_image"] = "https://b.fitn.in/iconsv1/global/fitcash.jpg";
					$resp["popup"]["header_text"] = "Congratulations";
					$resp["popup"]["text"] = "Login successful. You have Rs ".$current_wallet_balance." in your Fitcash wallet - you can use this to do membership purchase or pay-per-session bookings.";
					$resp["popup"]["button"] = "Ok";

				}

			}else{

				$fitcash = 0;
				$fitcash_plus = 0;

				$customer_wallet = Customerwallet::where('customer_id',$customer->_id)
				->where('amount','!=',0)
				->orderBy('_id', 'DESC')
				->first();

				if($customer_wallet){
					$fitcash = (isset($customer_wallet['balance']) && $customer_wallet['balance'] != "") ? (int) $customer_wallet['balance'] : 0 ;
					$fitcash_plus = (isset($customer_wallet['balance_fitcash_plus']) && $customer_wallet['balance_fitcash_plus'] != "") ? (int) $customer_wallet['balance_fitcash_plus'] : 0 ;
				}

				$current_wallet_balance = $fitcash + $fitcash_plus;

				if($fitcash > 0 || $fitcash_plus > 0){

					$resp["show_popup"] = true;
					$resp["popup"]["header_image"] = "https://b.fitn.in/iconsv1/global/fitcash.jpg";
					$resp["popup"]["header_text"] = "Congratulations";

					if($fitcash_plus > 0){
						$resp["popup"]["text"] = "Login successful. You have Rs ".$fitcash_plus." in your Fitcash wallet - you can use this to do membership purchase or pay-per-session bookings.";
					}else{
						$resp["popup"]["text"] = "Login successful. You have Rs ".$fitcash." in your Fitcash wallet - you can use this to do membership purchase or pay-per-session bookings.";
					}

					$resp["popup"]["button"] = "Ok";
				}

			}
		}else{
			if(isset($customdata['signupIncentive']) && $customdata['signupIncentive'] == true){

				$addWalletData = [
					"customer_id" => $customer["_id"],
					"amount" => 250,
					"amount_fitcash_plus"=>250,
					"description" => "Added FitCash+ Rs 250 on Sign-Up, Expires On : ".date('d-m-Y',time()+(86400*15)),
					"validity"=>time()+(86400*15),
					"entry"=>"credit",
					"type"=>"FITCASHPLUS"
				];
				$this->utilities->walletTransaction($addWalletData);
				$resp["show_popup"] = true;
				$resp["popup"]["header_image"] = "https://b.fitn.in/iconsv1/global/fitcash.jpg";
				$resp["popup"]["header_text"] = "Congratulations";
				$resp["popup"]["text"] = "You have recieved Rs.250 FitCash plus. Validity: 15 days";
				$resp["popup"]["button"] = "Ok";
			}
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
			$customer->demonetisation = time();
			$customer->referral_code = generateReferralCode($customer->name);
			$customer->old_customer = false;
			$customer->save();
            registerMail($customer->_id);
			$response = array('status' => 200,'customer'=>$customer);
		}

		return $response;
	}

	public function addRegId(){

		$data = Input::json()->all();

		$addRegIdData["device_type"] = $data["type"];
		$addRegIdData["gcm_reg_id"] = $data["reg_id"];
		$addRegIdData["customer_id"] = (isset($data["customer_id"]) && $data["customer_id"] != "") ? (int)$data["customer_id"] : "";

		$this->addCustomerRegId($addRegIdData);

		return Response::json(array('status' => 200,'message' => 'success'),200);
	}

	public function addCustomerRegId($data){

		$device_type = (isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id = (isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

        if($device_type != '' && $gcm_reg_id != ''){

            $regData = array();

            $regData['customer_id'] = $data["customer_id"];
            $regData['reg_id'] = $gcm_reg_id;
            $regData['type'] = $device_type;

            $this->utilities->addRegId($regData);
        }

        return "success";
	}

	public function createToken($customer){

		$customer = array_except($customer->toArray(), array('password'));
		Log::info("createToken");
		Log::info($customer);
		$customer['name'] = (isset($customer['name'])) ? $customer['name'] : "";
		$customer['email'] = (isset($customer['email'])) ? $customer['email'] : "";
		$customer['picture'] = (isset($customer['picture'])) ? $customer['picture'] : "";
		$customer['facebook_id'] = (isset($customer['facebook_id'])) ? $customer['facebook_id'] : "";
		$customer['address'] = (isset($customer['address'])) ? $customer['address'] : "";
		if(!empty($customer['referral_code']))
		$customer['referral_code'] = $customer['referral_code'];
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
					),
					'corporate_login'=>$this->utilities->checkCorporateEmail($customer['email'])
				);
		if(!empty($customer['cart_id']))
			$data['cart_id']=$customer['cart_id'];
		if(!empty($customer['referral_code']))
			$data['referral_code'] = $customer['referral_code'];
		if(!empty($customer['freshchat_restore_id']))
			$data['freshchat_restore_id'] = $customer['freshchat_restore_id'];
		$jwt_claim = array(
			"iat" => Config::get('app.jwt.iat'),
			"nbf" => Config::get('app.jwt.nbf'),
			"exp" => Config::get('app.jwt.exp'),
			"customer" => $data
			);
		
		$jwt_key = Config::get('app.jwt.key');
		$jwt_alg = Config::get('app.jwt.alg');
		
		JWT::$leeway = 500;
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

			$customer = Customer::active()->where('email','=',$data['email'])->first();
			
			if(!empty($customer)){

				$token = $this->createPasswordToken($customer);

				if(isset($customer['email']) && !empty($customer['email'])){
					$customer_data = array('name'=>ucwords($customer['name']),'email'=>$customer['email'],'token'=>$token);
					$this->customermailer->forgotPassword($customer_data);
					return Response::json(array('status' => 200,'message' => 'token successfull created and mail send', 'token' => $token),200);
				}else{
					return Response::json(array('status' => 400,'message' => 'Customer email not present'),$this->error_status);
				}

			}else{
				return Response::json(array('status' => 400,'message' => 'Customer not found'),$this->error_status);
			}
		}else{
			return Response::json(array('status' => 400,'message' => 'Empty email'),$this->error_status);
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
			$old_contact_no = $customer->contact_no;
			if(!empty($customer_data['contact_no']) && $old_contact_no != $customer_data['contact_no']){
				$customer_data["verified"] = false;
			}
			$customer->update($customer_data);
			$verify_phone = $old_contact_no != $customer->contact_no;
			$message = implode(', ', array_keys($customer_data)) ;
			$token = $this->createToken($customer);
			
			return Response::json(array('status' => 200,'token'=>$token, 'customer_token'=>$token['token'], 'message'=>'Profile updated successfully', 'verify_phone'=>$verify_phone, 'customer_data'=>$customer),200);
		}
		
		return Response::json(array('status' => 400,'message' => 'customer data empty'),400);
	}

	public function customerTokenDecode($token){

		// $jwt_token = $token;
		// $jwt_key = Config::get('app.jwt.key');
		// $jwt_alg = Config::get('app.jwt.alg');
		// $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

		// Log::info("Decoded token--".json_encode($decodedToken->customer));

		// return $decodedToken;
		Log::info("jwt_token customer".$token);
		return customerTokenDecode($token);
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

		$orderData 			= 	Order::where("studio_extended_validity", '!=', true)->where('extended_validity', '!=', true)->where(function($query){$query->where('status', '1')->orWhere('cod_otp', 'exists', true)->orWhere(function($q1){$q1->where("payment_mode", "at the studio")->where("customer_source","website");});})->where('customer_email','=',$customer_email)->whereIn('type',$membership_types)->where('schedule_date','exists',false)->where(function($query){$query->orWhere('preferred_starting_date','exists',true)->orWhere('start_date','exists',true);})->skip($offset)->take($limit)->orderBy('_id', 'desc')->get();


		if(count($orderData) > 0){

			foreach ($orderData as $key => $value) {

				if(isset($value['finder_id']) && $value['finder_id'] != ''){
					$finderarr = Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
					->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->find(intval($value['finder_id']),['_id','title','slug','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile','finder_poc_for_customer_name','info','category_id','location_id','city_id','category','location','city','average_rating','total_rating_count','review_added']);

					if($finderarr){

						$finderarr = $finderarr->toArray();

						if(isset($finderarr['info']['service']) && is_array($finderarr['info']['service'])){
							$finderarr['info']['service'] = "";
						}

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


				if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "android")){

					//$getAction = $this->getAction($value,"orderHistory");

				    $value["action"] = null; //$getAction["action"];
				    $value["feedback"] = null; //$getAction["feedback"];

					$value["action_new"] = $this->getActionV1($value,"orderHistory");

				}else{

					$getAction = $this->getAction($value,"orderHistory");

				    $value["action"] = $getAction["action"];
				    $value["feedback"] = $getAction["feedback"];

					$value["action_new"] = $this->getActionV1($value,"orderHistory");

				}

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
			$responseData 		= 	['bookmarksfinders' => [],  'message' => 'No bookmarks yet :)'];
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

	public function updateServiceBookmarks($customer_id, $serviceid, $remove = ''){
		
		$customer 			= 	Customer::where('_id', intval($customer_id))->first();
		$serviceids 			= 	(isset($customer->service_bookmarks) && !empty($customer->service_bookmarks)) ? array_map('intval',$customer->service_bookmarks) : [];

		if($remove == ""){
			array_push($serviceids, intval($serviceid));
			$message = 'bookmark added successfully';
		}else{
			if (in_array(intval($serviceid), $serviceids)){
				unset($serviceids[array_search(intval($serviceid),$serviceids)]);
			}
			$message = 'bookmark removed successfully';
		}

		$customer = Customer::find((int) $customer_id);
		$bookmarksdata = ['service_bookmarks' => array_unique($serviceids)];
		$customer->update($bookmarksdata);

		$bookmarks = Service::whereIn('_id', array_unique($serviceids))
		->get(array('_id','finder','category_id','coverimage','slug','name','category','location_id','location','city_id','city','total_rating_count'));
		$responseData 		= 	['bookmarks' => $bookmarks,  'message' => $message];

		return Response::json($responseData, 200);
	}

	public function getAllOrders($offset = 0, $limit = 10){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		return $this->orderHistory($decoded->customer->email,$offset,$limit);
	}

	public function getAllBookmarks(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		$customer 			= 	Customer::where('_id', intval($decoded->customer->_id))->first();
		Log::info($customer);
		$finderids 			= 	(isset($customer->bookmarks) && !empty($customer->bookmarks)) ? $customer->bookmarks : [];
		$serviceids 			= 	(isset($customer->service_bookmarks) && !empty($customer->service_bookmarks)) ? $customer->service_bookmarks : [];
		Log::info($serviceids);
		$device_type = Request::header('Device-Type');
		$app_version = Request::header('App-Version');
		Log::info($device_type);
		Log::info($app_version);
		if($device_type && $app_version && in_array($device_type, ['android', 'ios']) && version_compare($app_version, '4.4.2')>0){

			if(empty($finderids) && empty($serviceids)){
				$response 		= 	['status' => 200, 'bookmarks' => [],  'message' => 'No bookmarks yet :)'];
				return Response::json($response, 200);
			}

		}else{
			if(empty($finderids)){
				$response 		= 	['status' => 200, 'bookmarks' => [],  'message' => 'No bookmarks yet :)'];
				return Response::json($response, 200);
			}
		}


		$servicebookmarks = Service::whereIn('_id', array_unique($serviceids))
		->get(array('_id','finder','category_id','coverimage','slug','name','category','location_id','location','city_id','city','total_rating_count'));

		$bookmarksfinders = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->with('offerings')
		->whereIn('_id', $finderids)
		->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','offerings'));

		$response 		= 	['status' => 200, 'bookmarksfinders' => $bookmarksfinders, 'servicebookmarks'=>$servicebookmarks, 'message' => 'List for bookmarks'];
		return Response::json($response, 200);
	}

	public function getAllReviews($offset = 0, $limit = 10){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		$reviews 			= 	Review::with(array('finder'=>function($query){$query->select('_id','title','slug','coverimage');}))->active()->where('customer_id',$decoded->customer->_id)->skip($offset)->take($limit)->orderBy('updated_at', 'desc')->get();

		$response 		= 	['status' => 200,'reviews' => $reviews,  'message' => 'List for reviews'];

		return Response::json($response, 200);
	}

	public function getAllTrials($offset = 0, $limit = 12){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);



		return $this->getAutoBookTrials($decoded->customer->email, $offset, $limit);

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

		$trials 		=	Booktrial::where('customer_email', '=', $customeremail)->where('going_status_txt','!=','cancel')->where('booktrial_type','auto')->where('schedule_date_time','>=',new DateTime())->orderBy('schedule_date_time', 'asc')->select('finder','finder_name','service_name', 'schedule_date', 'schedule_slot_start_time','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon','finder_id','schedule_date_time','what_i_should_carry','what_i_should_expect','code', 'payment_done')->first();

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

			$data['fit_code'] = $this->utilities->fitCode($data);

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
			'place',
			'freshchat_restore_id'))->toArray();
			
			
		if($customer){
			
			foreach ($array as $key => $value) {
				
				if(array_key_exists($key, $customer[0]))
				{
					continue;
				}else{
					$customer[0][$key] = $value;
				}
				
			}
			$customer[0]['qrcode'] = false;
            
            if(!empty($customer[0]['address']) && is_string($customer[0]['address'])){
				unset($customer[0]['address']);
			}

			$response 	= 	array('status' => 200,'customer' => $customer[0],'message' => 'Customer Details');

			$customer_level_data = $this->utilities->getWorkoutSessionLevel($customer_id);                
			
			// $response['level'] = [
			// 	'header'=>'You’re on a workout streak!',
			// 	'sub_header'=>'Level '.$customer_level_data['current_level']['level'],
			// 	'image'=>Config::get('app.streak_data')[$customer_level_data['current_level']['level'] - 1]['unlock_icon']
			// ];

		}else{

			$response 	= 	array('status' => 400,'message' => 'Customer not found');
		}

		return Response::json($response, $response['status']);

	}

	public function getCustomerDetail(){

		$jwt_token = Request::header('Authorization');
		Log::info($jwt_token);
		$decoded = $this->customerTokenDecode($jwt_token);

		$customer_id = $decoded->customer->_id;
		if(isset($_GET['af_instance_id'])){
			$af_instance_id = $_GET['af_instance_id'];
			$customer = Customer::find((int) $customer_id);
			$af_instance_idData = ['af_instance_id' => $af_instance_id];
			$customer->update($af_instance_idData);
		}
		$customer_detail = $this->customerDetail($customer_id);

		

		return $customer_detail;

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
			$customer->demonetisation = time();
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

		$customer_id = (isset($data['customer_id']) && $data['customer_id'] != "") ? $data['customer_id'] : autoRegisterCustomer($data);

		$data['customer_id'] = (int)$customer_id;

		$response = $customer_info->addHealthInfo($data);

		return Response::json($response,$response['status']);

	}

	public function listWalletSummary($limit=0,$offset=10){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;

		Log::info($customer_id);
		$customer = Customer::find((int)$customer_id);

		if(isset($customer->demonetisation)){

			$wallet_summary = [];

			$wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

			$restricted_wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->where('valid_finder_id', 'exists', true)->sum('balance');
			
			$non_restricted_wallet_balance = 0;
			
			if($restricted_wallet_balance){
	
				$non_restricted_wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->where('valid_finder_id', 'exists', false)->sum('balance');

			}
			
			$restricted_wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->where('valid_finder_id', 'exists', true)->sum('balance');

			$walletTransaction = WalletTransaction::where('customer_id',$customer_id)->orderBy('updated_at','DESC')->get()->groupBy('group');

			if($walletTransaction){

				$count = 0;

				foreach ($walletTransaction as $group => $transaction) {

					$amount = 0;
					$validity = null;
					$description = "";
					$date = "";
					$entry = "";
					$created_at = "";
					$type = "";

					foreach ($transaction as $key => $value) {

						$amount += $value['amount'];

						if(isset($value['validity']) && $validity != null){
							$validity = date('d-m-Y',$value['validity']);
						}

						if($date == ""){
							$date = date('d-m-Y',strtotime($value['created_at']));
						}

						if($description == ""){
							$description = $value['description'];
						}

						if($entry == ""){
							$entry = $value['entry'];
						}

						if($created_at == ""){


							$created_at = date('Y-m-d H:i:s',strtotime($value['created_at'])); 
							$updated_at = date('Y-m-d H:i:s',strtotime($value['created_at']));

							if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "android")){
								$created_at = date('d-m-Y',strtotime($value['created_at'])); 
								$updated_at = date('d-m-Y',strtotime($value['created_at']));
							}

						}

						if($type == ""){
							$type = $value['type'];
						}
					}

					$wallet_summary[] = [
						'_id'=>$count,
						'customer_id'=>$customer_id,
						'order_id'=>0,
						'type'=>$type,
						'amount'=>$amount,
						'balance'=>0,
						'amount_fitcash'=>0,
						'amount_fitcash_plus'=>$amount,
						'balance_fitcash_plus'=>0,
						'description'=>$description,
						'updated_at'=>$updated_at,
						'created_at'=>$created_at,
						'debit_credit'=>$entry,
						'validity'=>$validity
					];

					$count++;

				}

			}

			if(count($wallet_summary) > 0){
				$wallet_summary[0]['balance'] = $wallet_balance;
			}

			$resp = [
				'status' => 200,
				'data' => $wallet_summary,
				'wallet_balance'=>$wallet_balance,
				'fitcash' => null,
				'fitcash_plus' => [
					'title' => 'FITCASH+',
					'balance'=>$wallet_balance,
					'info'=>[
						'title'=>'What is FitCash+?',
						'description' => "With FitCash+ you can redeem the wallet amount on your transaction/booking on Fitternity ranging from workout sessions, memberships, and healthy tiffin subscription basis the term of use through the amount availed. The term of use is mentioned in your Fitternity profile under 'Wallet' - transaction summary.\nIf you have Fitcash+ wallet balance & are attempting to transact - the wallet amount will be used on the primary basis on your transaction & the discount code will be applicable over & above that. For more info click here",
						'short_description' => "refer"."\n"."a friend"."\n"."and earn FitCash+"
					]
				],
				'add_fitcash_text'=> "You get 10% extra on the amount you add into your wallet."
			];

			if($restricted_wallet_balance){
				
				$resp['restricted']	= $restricted_wallet_balance;
				
				if($non_restricted_wallet_balance){
					$resp['non_restricted']	= $non_restricted_wallet_balance;
				}
			
			}

			return Response::json($resp);

		}else{

			$wallet = array();
			$wallet = Customerwallet::where('customer_id',$customer_id)
			->where('amount','!=',0)
			->orderBy('_id', 'DESC')
			//->skip($limit)
			//->take($offset)
			->get();

			$wallet_balance = 0;
			$balance = 0;
			$balance_fitcash_plus = 0;

			if(count($wallet) > 0){

				$debit_array = [
				    "CASHBACK",
				    "REFUND",
				    "FITCASHPLUS",
				    "REFERRAL",
				    "CREDIT",
				];

				$wallet = $wallet->toArray();

				foreach ($wallet as $key => $value) {

					if(!isset($value['order_id'])){
						$wallet[$key]['order_id'] = 0;
					}

					$wallet[$key]["debit_credit"] = "debit";

					if(in_array($value["type"],$debit_array)){
						$wallet[$key]["debit_credit"] = "credit";
					}

					/*if(isset($value['validity']) && $value['validity'] != "" && $value['validity'] != null){
						$wallet[$key]['description'] = $wallet[$key]['description']." Expires on : ".date('d-m-Y',$value['validity']);
					}*/

					if(isset($wallet[$key+1])){

						$wallet[$key]['amount_fitcash'] = $value['amount'];
						$wallet[$key]['amount_fitcash_plus'] = 0;

						if(isset($value['balance_fitcash_plus'])){

							$wallet[$key]['amount_fitcash'] = abs($wallet[$key]['balance'] - $wallet[$key+1]['balance']);
							$wallet[$key]['amount_fitcash_plus'] = 0;

							if(isset($wallet[$key+1]['balance_fitcash_plus'])){
								$wallet[$key]['amount_fitcash_plus'] = abs($wallet[$key]['balance_fitcash_plus'] - $wallet[$key+1]['balance_fitcash_plus']);
							}
						}


					}else{

						$wallet[$key]['amount_fitcash'] = $value['amount'];
						$wallet[$key]['amount_fitcash_plus'] = 0;

						if(isset($value['balance_fitcash_plus'])){
							$wallet[$key]['amount_fitcash_plus'] = $value['amount'];
							$wallet[$key]['amount_fitcash'] = 0;
						}
					}

					if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "android")){
						$wallet[$key]['created_at'] = date('d-m-Y',strtotime($value['created_at'])); 
						$wallet[$key]['updated_at'] = date('d-m-Y',strtotime($value['created_at']));
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
					'fitcash' => [
						'title' => 'FITCASH',
						'balance'=>$balance,
						'info'=>[
							'title'=>'What is FitCash?',
							'description' => 'Earn FitCash with every transaction you do on Fitternity. You redeem upto 10% of the booking amount in each transaction. FitCash can be used for any booking or purchase on Fitternity ranging from workout sessions, memberships and healthy tiffin subscriptions',
							'short_description' => "refer"."\n"."a friend"."\n"."and earn FitCash+"
						]
					],
					'fitcash_plus' => [
						'title' => 'FITCASH+',
						'balance'=>$balance_fitcash_plus,
						'info'=>[
							'title'=>'What is FitCash+?',
							'description' => 'With FitCash+ there is no restriction on redeeming - you can use the entire amount in your transaction! FitCash can be used for any booking or purchase on Fitternity ranging from workout sessions, memberships and healthy tiffin subscriptions.',
							'short_description' => "refer"."\n"."a friend"."\n"."and earn FitCash+"
						]
					],
					'add_fitcash_text'=> "You get 10% extra on the amount you add into your wallet."
					),
				200
			);

		}
		
	}


	public function getWalletBalance(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = intval($decoded->customer->_id);

		$customer = Customer::find((int)$customer_id);

		if(isset($customer->demonetisation)){

			$current_wallet_balance = \Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

			return 	Response::json(
				array(
					'status' => 200,
					'balance' => $current_wallet_balance,
					'transaction_allowed' => $current_wallet_balance,
					'fitcash' => 0,
					'fitcash_plus' => $current_wallet_balance
				),200
			);

		}else{

			$balance = (isset($customer['balance']) && $customer['balance'] != "") ? (int) $customer['balance'] : 0 ;
			$balance_fitcash_plus = (isset($customer['balance_fitcash_plus']) && $customer['balance_fitcash_plus'] != "") ? (int) $customer['balance_fitcash_plus'] : 0 ;

			$customer_balance = $balance + $balance_fitcash_plus;

			// balance and transaction_allowed are same at this time........
			return 	Response::json(
				array(
					'status' => 200,
					'balance' => $customer_balance,
					'transaction_allowed' => $customer_balance,
					'fitcash' => $balance,
					'fitcash_plus' => $balance_fitcash_plus
				),200
			);

		}
		
	}

	public function editfriendforbooking(){
		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;
		$rules = [
			'friend_email_old' => 'required|string',
		];
		$data = Input::json()->all();
		$validator = Validator::make($data,$rules);
		if ($validator->fails()) {
			return Response::json(
				array(
					'status' => 400,
					'message' => $this->errorMessage($validator->errors()
						)),400
				);
		}
		$customer = Customer::where("_id",(int)$customer_id)->first();
		if($customer["email"] == $data["friend_email_old"]){
			if(!empty($data["friend_name"])){
				$customer["name"] = $data["friend_name"];
			}
			if(!empty($data["friend_phone"])){
				$customer["contact_no"] = $data["friend_phone"];
			}
			if(!empty($data["friend_gender"])){
				$customer["gender"] = $data["friend_gender"];
			}
		}else{
			$friends = $customer["friends"];
			foreach($friends as $key => $friend){
				if($friend["email"] == $data["friend_email_old"]){
					if(!empty($data["friend_name"])){
						$friends[$key]["name"] = $data["friend_name"];
					}
					if(!empty($data["friend_email"])){
						$friends[$key]["email"] = $data["friend_email"];
					}
					if(!empty($data["friend_phone"])){
						$friends[$key]["phone"] = $data["friend_phone"];
					}
					if(!empty($data["friend_gender"])){
						$friends[$key]["gender"] = $data["friend_gender"];
					}
				}
			}
			$customer["friends"] = $friends;
		}
		
		$customer->update();
		return $this->getBookingFriends($customer["_id"]);
	}
	public function addafriendforbooking(){	
		$jwt_token = Request::header('Authorization');
		$data = Input::json()->all();
		if(!empty($data["signUp"]) && $data["signUp"]){
			$newData = array(
				"customer_name" => $data["friend_name"],
				"customer_email" => $data["friend_email"],
				"customer_phone" => $data["friend_phone"],
				"customer_gender" => $data["friend_gender"],
				"verified" => true,
				"thirdparty_register" => !empty($data["thirdparty_register"]) && $data["thirdparty_register"] != false ? $data["thirdparty_register"] : false
			);
			$customer_id = autoRegisterCustomer($newData);
			$customerToken = createCustomerToken($customer_id);
			Log::info("autoRegisterCustomer".$customer_id );
			$newCustomer = $this->customerTokenDecode($customerToken);
			return array("token" => $customerToken, "customer" => $newCustomer);
			
			// if(!empty($customer)){
			// 	$friend = array(
			// 		"name" => $data["friend_name"],
			// 		"email" => $data["friend_email"],
			// 		"phone" => $data["friend_phone"],
			// 		"gender" => $data["friend_gender"]
			// 	);
			// 	if(empty($customer["friends"])){
			// 		$customer["friends"] = array($friend);
			// 	}else{
			// 		$friends = $customer["friends"];
			// 		array_push($friends, $friend);
			// 		$customer["friends"] = $friends;
			// 	}
			// 	$customer->update();
			// 	return $this->getBookingFriends($customer_id);
			// }
		}else{
			$decoded = $this->customerTokenDecode($jwt_token);
			$customer_id = $decoded->customer->_id;
			$rules = [
			'friend_name' => 'required|string',
			'friend_email' => 'required|string',
			'friend_phone' => 'required|string',
			'friend_gender' => 'required|string'
			];
			
			$validator = Validator::make($data,$rules);
			if ($validator->fails()) {
				return Response::json(
					array(
						'status' => 400,
						'message' => $this->errorMessage($validator->errors()
							)),400
					);
			}
			$friend = array(
				"name" => $data["friend_name"],
				"email" => $data["friend_email"],
				"phone" => $data["friend_phone"],
				"gender" => $data["friend_gender"]
			);
			
			$customer = Customer::where("_id",(int)$customer_id)->where("friends.email","!=", $data["friend_email"])->first();
			if(!empty($customer)){
				if(empty($customer["friends"])){
					$customer["friends"] = array($friend);
				}else{
					$friends = $customer["friends"];
					array_push($friends, $friend);
					$customer["friends"] = $friends;
				}
				$customer->update();
				return $this->getBookingFriends($customer_id);
			}else{
				return Response::json(
					array(
						'status' => 400,
						'message' => "Your friend is already added"
							),400
					);
			}
		}
	}
	public function getallBookingfriends(){	
		$jwt_token = Request::header('Authorization');
		if(!empty($jwt_token)){
			$decoded = $this->customerTokenDecode($jwt_token);
			$customer_id = $decoded->customer->_id;	
			return $this->getBookingFriends($customer_id);
		}
		return [];
	}
	public function getBookingFriends($customer_id){
		$customer = Customer::find((int)$customer_id);
		$allBookingFriends = array(array("name" => $customer->name, "email" => $customer->email, "phone" => $customer->contact_no, "gender" => $customer->gender, "default"=> true));
		$customer_friends = isset($customer["friends"]) ? $customer["friends"] : [];
		$allBookingFriends  = array_merge($allBookingFriends, $customer_friends);
		return $allBookingFriends;
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


        Log::info('--------customer_home_app--------',$_GET);
        
        if(strtolower($city) == 'new'){
            $city = 'delhi';
        }

		$jwt_token = Request::header('Authorization');
		Log::info($jwt_token);
		$upcoming = array();
		
		// $city = strtolower($city);
		$city = getmy_city($city);

		if($jwt_token != ""){

			try {

				$decoded = $this->customerTokenDecode($jwt_token);

                // if(empty($decoded->customer)){
                //     return ['isSessionExpired'=>true];
                // }
				
                $customeremail = $decoded->customer->email;
				$customer_id = $decoded->customer->_id;

				Log::info("------------home------------$customeremail");

				Log::info('device_type'.$this->device_type);
				Log::info('app_version'.$this->app_version);
				$trials = [];
				if($this->app_version >= 5){

					Log::info("Asdasdasdsss=======");
					$trials = Booktrial
						::where('customer_email', '=', $customeremail)
						->where('going_status_txt','!=','cancel')
						->where('post_trial_status', '!=', 'no show')
						->where('booktrial_type','auto')
						->where(function($query){
							$query->orWhere('schedule_date_time','>=',new DateTime())
							->orWhere(function($query){
								$query->where('payment_done', false)
								->where('post_trial_verified_status', '!=', 'no')
								->where('going_status_txt','!=','cancel');
							})
							->orWhere(function($query){
									$query	->where('schedule_date_time', '>', new DateTime(date('Y-m-d H:i:s', strtotime('-3 days', time()))))
											->whereIn('post_trial_status', [null, '', 'unavailable']);	
							})
							->orWhere(function($query){
                                $query	->where('ask_review', true)
                                        ->where('schedule_date_time', '<', new DateTime(date('Y-m-d H:i:s', strtotime('-1 hour'))))
										->whereIn('post_trial_status', ['attended'])
										->where('has_reviewed', '!=', '1')
										->where('skip_review', '!=', true);	
							});
						})
						->orderBy('schedule_date_time', 'asc')
						->select('finder','finder_name','service_name', 'schedule_date', 'schedule_slot_start_time','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon','finder_id','schedule_date_time','what_i_should_carry','what_i_should_expect','code', 'payment_done', 'type', 'order_id', 'post_trial_status', 'amount_finder', 'kiosk_block_shown', 'has_reviewed', 'skip_review','amount','studio_extended_validity_order_id','studio_block_shown')
						->get();
				
				}else if($this->app_version > '4.4.3'){
					Log::info("4.4.3");
					$trials = Booktrial::where('customer_email', '=', $customeremail)->where('going_status_txt','!=','cancel')->where('post_trial_status', '!=', 'no show')->where('booktrial_type','auto')->where(function($query){return $query->where('schedule_date_time','>=',new DateTime())->orWhere('payment_done', false)->orWhere(function($query){	return 	$query->where('schedule_date_time', '>', new DateTime(date('Y-m-d H:i:s', strtotime('-3 days', time()))))->whereIn('post_trial_status', [null, '', 'unavailable']);	});})->orderBy('schedule_date_time', 'asc')->select('finder','finder_name','service_name', 'schedule_date', 'schedule_slot_start_time','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon','finder_id','schedule_date_time','what_i_should_carry','what_i_should_expect','code', 'payment_done', 'type', 'order_id', 'post_trial_status', 'amount_finder', 'kiosk_block_shown','customer_id','amount','studio_extended_validity_order_id','studio_block_shown')->get();


				}else{
					
					$trials = Booktrial::where('customer_email', '=', $customeremail)->where('going_status_txt','!=','cancel')->where('booktrial_type','auto')->where('schedule_date_time','>=',new DateTime())->orderBy('schedule_date_time', 'asc')->select('finder','finder_name','service_name', 'schedule_date', 'schedule_slot_start_time','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon','finder_id','schedule_date_time','what_i_should_carry','what_i_should_expect','code','customer_id','amount','third_party_details')->get();
				}
				
				$activate = [];
				$let_us_know = [];
				$no_block = [];
				$future = [];
				$review = [];
				//Log::info('trails count',[count($trials), $trials]);
				if(count($trials) > 0){
					$workout_session_level_data = $this->utilities->getWorkoutSessionLevel($customer_id);

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
							if(!in_array(gettype($value), ['boolean'])){
								$data[$key] = ucwords(strip_tags($value));
							}
						}

						
						if(isset($data['schedule_slot_start_time'])){
							$data['schedule_slot_start_time'] = strtoupper($data['schedule_slot_start_time']);
						}
						
						if(in_array($this->device_type, ['android', 'ios']) && $this->app_version > '4.4.3'){


							if($data['type'] == 'Workout-session'){
								if(!isset($data['extended_validity_order_id'])) {
									$data['unlock'] = [
										// 'header'=>'Unlock Level '.$workout_session_level_data['next_session']['level'].'!',
										'sub_header_2'=>'Attend this session, and get '.$workout_session_level_data['next_session']['cashback'].'% CashBack upto '.$workout_session_level_data['next_session']['number'].' sessions',
										'image'=>'https://b.fitn.in/paypersession/unlock-icon.png'
									];
									if(strtotime($data['schedule_date_time']) < time()){
										$data['unlock']['sub_header_2'] = 'Let us know if you attended this session, and get '.$workout_session_level_data['next_session']['cashback'].'% CashBack upto '.$workout_session_level_data['next_session']['number'].' sessions';
									}
								}
								$data['current_level'] = $workout_session_level_data['current_level']['level'];

								// $data['streak'] = [
								// 	'header'=>'ATTEND MORE & UNLOCK',
								// 	'data'=>$this->utilities->getStreakImages($data['current_level'])
								// ];	
								$data['subscription_text']  = "Show this subscription code at ".ucwords($data['finder_name'])." & get FitCode to activate your session";
							
							}else{

                                $data['unlock'] = [
									'header'=>'Your workout checklist',
									'sub_header_2'=>"Wondering what to carry?\nWe’ve got you covered!",
									'image'=>'https://b.fitn.in/paypersession/checklist_icon2.png'
                                ];

								$data['checklist'] = true;

								$fitcash_amount = $this->utilities->getFitcash($trial);

								$data['subscription_text']  = "Show this subscription code at ".ucwords($data['finder_name'])." & get FitCode to unlock your ".$fitcash_amount." Fitcash as discount";
							
							}
							
							$data['subscription_code']  = $data['code'];
							if(isset($data['finder_poc_for_customer_no']) && $data['finder_poc_for_customer_no']!=""){
								$data['subscription_text'] = $data['subscription_text']."\n\nPerson of contact\n".ucwords($data['finder_poc_for_customer_name'])." ";
								$data['subscription_text_number'] = $data['finder_poc_for_customer_no'];
							}

							$data['image'] = 'http://b.fitn.in/paypersession/Subscribtion_code_icon_new.png';

							$data['title']  = ucwords($data['service_name'])." at ".ucwords($data['finder_name']);
							
							$data['trial_id'] = $data['_id'];
							Log::info(strtotime($data['schedule_date_time']));
							Log::info(time());

							if(isset($data['studio_extended_validity_order_id']) && (empty($data['studio_block_shown']) || !$data['studio_block_shown'])){
								
								$time = null;
								if((time() >= (strtotime($data['schedule_date_time'])-3*60*60)) && (time() <= (strtotime($data['schedule_date_time'])))){
									Log::info('into studio extended valdity and calling block screen', [$data]);
									// it is session for grouping named as le us know
									$data['block_screen'] = [
										'type'=>'let_us_know',
										'url'=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/session_reminder',
										'time'=>'n-3'
									];
								}
									
							}

							if(!isset($data['third_party_details'])){
								if((!isset($data['post_trial_status']) || in_array($data['post_trial_status'], ['unavailable', ""]))  ){
									Log::info("inside block");

									if(time() >= strtotime('-10 minutes ', strtotime($data['schedule_date_time']))){

										if(time() < (strtotime($data['schedule_date_time'])+6*60*60) && !(isset($data['kiosk_block_shown']) && $data['kiosk_block_shown'])){
											$data['block_screen'] = [
												'type'=>'activate_session',
												'url'=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/activate_session',
												'time'=>'n-10m'
											];
										}else if(time() < (strtotime($data['schedule_date_time'])+3*24*60*60) && empty($data['studio_extended_validity_order_id'])){
											Log::info('notification by app before ',[$data]);
											$data['block_screen'] = [
												'type'=>'let_us_know',
												'url'=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/let_us_know',
												'time'=>'n+2'
											];
										}
									
									}else{
										$data['activation_url'] = Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/activate_session';
									}
									

									
								}else if(!((isset($data['has_reviewed']) && $data['has_reviewed']) || (isset($data['skip_review']) && $data['skip_review'])) && strtotime($data['schedule_date_time']) < strtotime('-1 hour') && empty($data['studio_extended_validity_order_id'])){

									if($this->app_version >= 5){

										$data['block_screen'] = [
											'type'=>'review',
											// 'review_data'=>$this->notificationDataByTrialId($data['_id'], 'review'),
											'url'=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/review'
										];	
									}else{
										if($this->device_type == 'android'){
											$data['block_screen'] = [
												'type'=>'let_us_know',
												'url'=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/let_us_know',
												'time'=>'n+2'
											];
										}
									}
								}
								
							}

							$data['current_time'] = date('Y-m-d H:i:s', time());
							
							$data['time_diff'] = strtotime($data['schedule_date_time']) - time();

							if($data['time_diff'] < 0){
								$data['schedule_date_time_text'] = "Happened on ".date('jS M, h:i a', strtotime($data['schedule_date_time']));
							}else{
								unset($data['payment_done']);
								$data['schedule_date_time_text'] = "Scheduled For ".date('jS M, h:i a', strtotime($data['schedule_date_time']));
							}
							
                            
							
                            if(isset($data['payment_done']) && !$data['payment_done']){
								$data['schedule_date_time_text'] = "Pending Amount: Rs.".$data['amount'];
								$data['amount'] = "₹".$data['amount'];
							}else if(isset($data['amount_finder'])){
								$data['amount'] = "₹".$data['amount_finder'];
							}
							
							
							$data = array_only($data, ['title', 'schedule_date_time', 'subscription_code', 'subscription_text', 'body1', 'streak', 'payment_done', 'order_id', 'trial_id', 'unlock', 'image', 'block_screen','activation_url', 'current_time' ,'time_diff', 'schedule_date_time_text', 'subscription_text_number', 'amount', 'checklist','findercategory']);

						
							
						}
						
						$upcoming[] = $data;

					}
					if($this->app_version > '4.4.3'){
						
						foreach($upcoming as $x){

							if(isset($x['block_screen'])){

								if( (isset($x['block_screen']) && $x['block_screen']['type'] == 'activate_session')){
									array_push($activate, $x);
								}else if(isset($x['block_screen']) && $x['block_screen']['type'] == 'review'){
									array_push($review, $x);
								}else{
									array_push($let_us_know, $x);
								}
							}else if(isset($x['activation_url'])){
								array_push($future, $x);
							}else{
								array_push($no_block, $x);
							}
						}

						$upcoming = array_merge($activate, $let_us_know, $review, $future, $no_block);
					}

				}

				if(isset($_GET['notif_enabled']) && $_GET['notif_enabled']){
					Customer::where('_id', $customer_id)->update(['notif_enabled'=>$_GET['notif_enabled']=='true' ? true : false]);
				}

			} catch (Exception $e) {
				Log::error($e);
			}
			
		}
        // if($this->app_version > '')
        try{
            $active_session_packs = [];
            if((!empty($_GET['device_type']) && !empty($_GET['app_version'])) && ((in_array($_GET['device_type'], ['android']) && $_GET['app_version'] >= '5.18') || ($_GET['device_type'] == 'ios' && $_GET['app_version'] >= '5.1.5'))){
                $active_session_packs = $this->getSessionPacks(null, null, true, $customer_id)['data'];
            }

        }catch(Exception $e){

            $active_session_packs = [];
        
        }

		if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "android")){
			if(isset($_GET['app_version']) && ((float)$_GET['app_version'] >= 4.2)){
				
				$category_new = citywise_categories($city);

				foreach ($category_new as $key => $value) {
					// $category_new[$key]['pps_available'] = false;
					if(isset($value["slug"]) && $value["slug"] == "fitness"){
						unset($category_new[$key]);
					}
				}

				$category_new = array_values($category_new);

				$cache_tag = 'customer_home_by_city_4_2';
			}elseif(isset($_GET['app_version']) && ((float)$_GET['app_version'] >= 2.5)){
				$category_slug = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"marathon-training","dance","cross-functional-training","mma-and-kick-boxing","swimming","pilates"/*,"personal-trainers"*/,"luxury-hotels"/*,"healthy-snacks-and-beverages"*/,"spinning-and-indoor-cycling"/*,"healthy-tiffins"*//*,"dietitians-and-nutritionists"*//*,"sport-nutrition-supliment-stores"*/,"aerobics","kids-fitness","pre-natal-classes","aerial-fitness","aqua-fitness");
				$cache_tag = 'customer_home_by_city_2_5';
			}else{
				$category_slug = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"marathon-training","dance","cross-functional-training","mma-and-kick-boxing","swimming","pilates"/*,"personal-trainers"*//*,"luxury-hotels"*//*,"healthy-snacks-and-beverages"*/,"spinning-and-indoor-cycling"/*,"healthy-tiffins"*//*,"dietitians-and-nutritionists"*//*,"sport-nutrition-supliment-stores"*/,"kids-fitness","pre-natal-classes","aerial-fitness","aqua-fitness");
				$cache_tag = 'customer_home_by_city';
			}
		}else{
			if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "ios") && isset($_GET['app_version']) && ((float)$_GET['app_version'] <= 4.1)){
				$category_slug = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"marathon-training","dance","cross-functional-training","mma-and-kick-boxing","swimming","pilates","luxury-hotels"/*,"healthy-snacks-and-beverages"*/,"spinning-and-indoor-cycling"/*,"healthy-tiffins"*//*,"dietitians-and-nutritionists"*//*,"sport-nutrition-supliment-stores"*/,"kids-fitness","pre-natal-classes","aerial-fitness","aqua-fitness"/*,"personal-trainers"*/);
				$cat = array();
				$cat['mumbai'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"marathon-training","dance","cross-functional-training","mma-and-kick-boxing","swimming","pilates","luxury-hotels"/*,"healthy-snacks-and-beverages"*/,"spinning-and-indoor-cycling"/*,"healthy-tiffins"*//*,"dietitians-and-nutritionists"*//*,"sport-nutrition-supliment-stores"*/,"kids-fitness","pre-natal-classes","aerial-fitness","aqua-fitness"/*,"personal-trainers"*/);
				$cat['pune'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"pilates"/*,"healthy-tiffins"*/,"cross-functional-training","mma-and-kick-boxing","dance","spinning-and-indoor-cycling","swimming"/*,"luxury-hotels"*//*,"sport-nutrition-supliment-stores"*/,"aerobics"/*,"kids-fitness"*/,"pre-natal-classes","aerial-fitness"/*,"personal-trainers"*/);
				$cat['bangalore'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"pilates"/*,"healthy-tiffins"*/,"cross-functional-training","mma-and-kick-boxing","dance","spinning-and-indoor-cycling","luxury-hotels","swimming"/*,"sport-nutrition-supliment-stores"*/,"kids-fitness","pre-natal-classes","aerial-fitness"/*,"personal-trainers"*/);
				$cat['delhi'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"pilates"/*,"healthy-tiffins"*/,"cross-functional-training","mma-and-kick-boxing","dance","spinning-and-indoor-cycling","luxury-hotels","swimming"/*,"sport-nutrition-supliment-stores"*/,"kids-fitness","pre-natal-classes","aerial-fitness"/*,"personal-trainers"*/);
				$cat['gurgaon'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"pilates"/*,"healthy-tiffins"*/,"cross-functional-training","mma-and-kick-boxing","dance","spinning-and-indoor-cycling"/*,"sport-nutrition-supliment-stores"*/,"kids-fitness","pre-natal-classes","aerial-fitness"/*,"personal-trainers"*/);
				$cat['noida'] = array("gyms","yoga","zumba","fitness-studios",/*"crossfit",*/"cross-functional-training","mma-and-kick-boxing","dance",/*"kids-fitness","pre-natal-classes"*/);
				if(isset($cat[$city])){
					$category_slug = $cat[$city];
				}
				$cache_tag = 'customer_home_by_city_ios';
			}else{

				$category_new = citywise_categories($city);

				foreach ($category_new as $key => $value) {
					// $category_new[$key]['pps_available'] = false;
					if(isset($value["slug"]) && $value["slug"] == "fitness"){
						unset($category_new[$key]);
					}
				}

				$category_new = array_values($category_new);

				$cache_tag = 'customer_home_by_city_ios_4.1';
			}
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
			if(isset($category_new)){
				$category			= 		$category_new;
				$ordered_category   = 		$category_new;
			}else{
				$category			= 		Findercategory::active()->whereIn('slug',$category_slug)->get(array('name','_id','slug'))->toArray();
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
			}
			// $locations				= 		Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug','location_group'));
			$collections 			= 	[]; //Findercollection::active()->where('city_id', '=', intval($city_id))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));	
			
			$homedata 				= 	array('categorytags' => $ordered_category,
				// 'locations' => $locations,
				'city_name' => $city_name,
				'city_id' => $city_id,
				'collections' => $collections,
				'banner' => 'http://b.fitn.in/c/welcome/1.jpg'
				);
			Cache::tags($cache_tag)->put($city,$homedata,Config::get('cache.cache_time'));
		}
		$result             = Cache::tags($cache_tag)->get($city);
		$result['upcoming'] = $upcoming;
        $result['session_packs'] = $active_session_packs;
		// $result['campaign'] =  new \stdClass();
		// $result['campaign'] = array(
		// 	'image'=>'http://b.fitn.in/iconsv1/womens-day/women_banner_app_50.png',
		// 	'link'=>'fitternity://www.fitternity.com/search/offer_available/true',
		// 	'title'=>'FitStart 2017',
		// 	'height'=>1,
		// 	'width'=>6,
		// 	'ratio'=>1/6
		// );

		if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "ios") && ((float)$_GET['app_version'] <= 4.1)){
			$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
			$city_id		= 	(int) $citydata['_id'];
			$result['collections'] 			= 	Findercollection::active()->where('city_id', '=', intval($city_id))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));	
		}

		if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "android") && ((float)$_GET['app_version'] <= 4.2)){
			$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
			$city_id		= 	(int) $citydata['_id'];
			$result['collections'] 			= 	Findercollection::active()->where('city_id', '=', intval($city_id))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));
		}

		// if(isset($_REQUEST['device_type']) && $_REQUEST['device_type'] == "ios" ){
			
		// }
		// $result['campaign'] =  new \stdClass();
		// $result['campaign'] = array(
		// 	// 'image'=>'http://b.fitn.in/iconsv1/offers/generic_banner.png',
		// 	'image'=>'http://b.fitn.in/global/diwali/diwali_banner.png',
		// 	'link'=>'',
		// 	'title'=>'FitStart 2017',
		// 	'height'=>1,
		// 	'width'=>6,
		// 	'ratio'=>1/6
		// );

		// if(isset($_REQUEST['device_type']) && $_REQUEST['device_type'] == "android"){
		// 	$result['campaign']['link'] = 'ftrnty://ftrnty.com/search/all';
		// }
		
		if(isset($_REQUEST['device_type']) && in_array($_REQUEST['device_type'],['ios','android']) && isset($_REQUEST['app_version']) && ((float)$_GET['app_version'] >= 4.4)){
                
			$city_id = City::where('slug', $city)->first(['_id']);
			
			// return $city_id;
			$campaigns = [];
			/***************************Banners start********************** */
			// commented below on 26 Jan - start

			if($city){

				$homepage = Homepage::where('city_id', $city_id['_id'])->first();

				$campaigns = [];

               if($homepage && !empty($homepage['app_banners']) && is_array($homepage['app_banners']) && count($homepage['app_banners']) >= 2){

                   $app_banners = $homepage['app_banners'];


                   foreach($app_banners as $banner){

                       if(isset($banner['app_version']) && (float)$_GET['app_version'] < 4.4){
                           continue;
                       }

                       if($_GET['device_type'] == 'android' && !empty($banner['link_android'])){
                           $banner['link'] = $banner['link_android'];
                       }

                       if($_REQUEST['device_type'] == 'ios' && !empty($banner['only_android'])){
                           continue;
                       }

                       array_push($campaigns, $banner);

                   }

                   function cmp($a, $b)
                   {
                       return $a['order'] - $b['order'];
                   }

                   usort($campaigns, "cmp");
               }
			}
            // commented above on 26 Jan - end
            
            /***************************Banners end********************** */

			$result['campaigns'] =  $campaigns;
			// $result['campaigns'] =  [];

			// $result['campaigns'][] = [
			// 	'image'=>'https://b.fitn.in/global/Homepage-branding-2018/app-banner/independance_app.png',
			// 	'link'=>'ftrnty://ftrnty.com/search/all',
			// 	'title'=>'Group Membership',
			// 	'height'=>100,
			// 	'width'=>375,
			// 	'ratio'=>(float) number_format(100/375,2)
			// ];

			// if($city != "ahmedabad"){

			// 	$result['campaigns'][] = [
			// 		'image'=>'https://b.fitn.in/global/Homepage-branding-2018/app-banner/mumbai-gold.jpg',
			// 		'link'=>'ftrnty://ftrnty.com/s?brand=golds-gym&city='.strtolower($city),
			// 		'title'=>'Pledge for Fitness',
			// 		'height'=>100,
			// 		'width'=>375,
			// 		'ratio'=>(float) number_format(100/375,2)
			// 	];

			// 	switch($city){
			// 		case "pune":
			// 			$result['campaigns'][1]["image"] = "https://b.fitn.in/global/Homepage-branding-2018/app-banner/pune-gold.jpg";
			// 			if(intval(date('d', time())) % 2 == 0){
			// 				$result['campaigns'][] = [
			// 					'image'=>'https://b.fitn.in/global/Homepage-branding-2018/app-banner/Multifit_App.png',
			// 					'link'=>'ftrnty://ftrnty.com/s?brand=multifit&city='.strtolower($city),
			// 					'title'=>'Pledge for Fitness',
			// 					'height'=>100,
			// 					'width'=>375,
			// 					'ratio'=>(float) number_format(100/375,2)
			// 				];
			// 			}else{
			// 				array_splice($result['campaigns'], count($result['campaigns'])-1, 0, [[
			// 					'image'=>'https://b.fitn.in/global/Homepage-branding-2018/app-banner/Multifit_App.png',
			// 					'link'=>'ftrnty://ftrnty.com/s?brand=multifit&city='.strtolower($city),
			// 					'title'=>'Pledge for Fitness',
			// 					'height'=>100,
			// 					'width'=>375,
			// 					'ratio'=>(float) number_format(100/375,2)
			// 				]]);
			// 			}
			// 		break;
			// 		case "bangalore":
			// 			$result['campaigns'][1]["image"] = "https://b.fitn.in/global/Homepage-branding-2018/app-banner/bangalore-gold.jpg";
			// 			break;
			// 		case "delhi":
			// 			$result['campaigns'][1]["image"] = "https://b.fitn.in/global/Homepage-branding-2018/app-banner/delhi-gold.jpg";
			// 			break;	
			// 		case "noida":
			// 			$result['campaigns'][1]["image"] = "https://b.fitn.in/global/Homepage-branding-2018/app-banner/noida-gold.jpg";
			// 			break;
			// 		case "hyderabad":
			// 			$result['campaigns'][1]["image"] = "https://b.fitn.in/global/Homepage-branding-2018/app-banner/hyderabad-gold.jpg";
			// 			break;					
			// 		case "gurgaon":
			// 			$result['campaigns'][1]["image"] = "https://b.fitn.in/global/Homepage-branding-2018/app-banner/gurgaon-gold.jpg";
			// 			break;										
			// 	}
			// }

			// if($city == "mumbai"){

			// 	$result['campaigns'][] = [
			// 		'image'=>'https://b.fitn.in/global/Homepage-branding-2018/app-banner/yfc-mumbai-app.jpg',
			// 		'link'=>'ftrnty://ftrnty.com/s?brand=your-fitness-club&city='.strtolower($city),
			// 		'title'=>'Your Fitness Club (YFC)',
			// 		'height'=>100,
			// 		'width'=>375,
			// 		'ratio'=>(float) number_format(100/375,2)
			// 	];

			// }


			// if(!$this->app_version || $this->app_version < '4.9'){
			// 	foreach($result['campaigns'] as &$campaign){
			// 		if(isset($campaign['title']) && $campaign['title'] == 'Pledge for Fitness'){
			// 			$campaign['link'] = '';
			// 		}
			// 	}
			// }
			
			// if($_REQUEST['device_type'] == 'ios'){

				// if($this->app_version > '4.4.3'){

					// $result['campaigns'][] = [
					// 	'image'=>'https://b.fitn.in/global/paypersession_branding/app_banners/App-pps%402x.png',
					// 	'link'=>'ftrnty://ftrnty.com/pps?',
					// 	'title'=>'Pay Per Session',
					// 	'height'=>100,
					// 	'width'=>375,
					// 	'ratio'=>(float) number_format(100/375,2)
					// ];

				// }

			// }else{

			// 	if($this->app_version > '4.4.3'){

			// 		$result['campaigns'][] = [
			// 			'image'=>'https://b.fitn.in/global/paypersession_branding/app_banners/App-pps%402x.png',
			// 			'link'=>'ftrnty://ftrnty.com/pps',
			// 			'title'=>'Pay Per Session',
			// 			'height'=>100,
			// 			'width'=>375,
			// 			'ratio'=>(float) number_format(100/375,2)
			// 		];
					
			// 	}

			// }

			// $result['campaigns'][] = [
			// 	'image'=>'https://b.fitn.in/global/ios_homescreen_banner/complimentary-rewards-appbanner.png',
			// 	'link'=>'https://www.fitternity.com/rewards?mobile_app=true',
			// 	'title'=>'Complimentary Rewards',
			// 	'height'=>100,
			// 	'width'=>375,
			// 	'ratio'=>(float) number_format(100/375,2)
			// ];

			$lat = isset($_REQUEST['lat']) && $_REQUEST['lat'] != "" ? $_REQUEST['lat'] : "";
	        $lon = isset($_REQUEST['lon']) && $_REQUEST['lon'] != "" ? $_REQUEST['lon'] : "";

			$near_by_vendor_request = [
	            "offset" => 0,
	            "limit" => 9,
	            "radius" => "2km",
	            "category"=>"",
	            "lat"=>$lat,
	            "lon"=>$lon,
	            "city"=>strtolower($city),
	            "keys"=>[
	              "average_rating",
	              "contact",
	              "coverimage",
	              "location",
	              "multiaddress",
	              "slug",
	              "name",
	              "id",
	              "categorytags",
	              "category"
	            ]
	        ];
            $geoLocationFinder = geoLocationFinder($near_by_vendor_request, 'customerhome');
	        $result['near_by_vendor'] = isset($geoLocationFinder['finder']) ? $geoLocationFinder['finder'] : $geoLocationFinder;
		}
        
		$result['categoryheader'] = "Discover | Try | Buy";
		$result['categorysubheader'] = "Fitness services in ".ucwords($city);
		$result['trendingheader'] = "Trending in ".ucwords($city);
		$result['trendingsubheader'] = "Checkout fitness services in ".ucwords($city);

		if(!empty($_REQUEST['auto_detect']) && $_REQUEST['auto_detect'] === true){

			$result['categoryheader'] = "Discover | Try | Buy";
			$result['categorysubheader'] = "Fitness services near you";
			$result['trendingheader'] = "Trending near you";
			$result['trendingsubheader'] = "Checkout fitness services near you";
		}

		if(!empty($_REQUEST['selected_region'])){

            // $result['categoryheader'] = "Discover & Book Gyms & Fitness Classes in ".ucwords($_REQUEST['selected_region']);
            $result['categoryheader'] = "Discover & Book";
			// $result['categoryheader'] = "Discover | Try | Buy";
			$result['categorysubheader'] = "Gyms and Fitness Centers in ".ucwords($_REQUEST['selected_region']);
			$result['trendingheader'] = "Trending in ".ucwords($_REQUEST['selected_region']);
			$result['trendingsubheader'] = "Checkout fitness services in ".ucwords($_REQUEST['selected_region']);
		}
		else {
            $result['categoryheader'] = "Discover & Book";
        }

        $result['fitex'] =[
            'logo' => 'https://b.fitn.in/global/pps/fexclusive1.png',
            'header' => 'EXPERIENCE FITNESS LIKE NEVER BEFORE!',
            'subheader' => 'Book sessions and only pay for days you workout',
            // 'knowmorelink' => 'know more',
            'footer' => "Available across 2500+ outlets across ".ucwords($city)." | Starting at <b>&#8377; 149</b>"
        ];

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

		if(isset($data['order_id'])){
			$order = Order::find($data['order_id']);
			$data['customer_name'] = $order['customer_name'];
			$data['customer_email']= $order['customer_email'];
			$data['customer_phone']= $order['customer_phone'];
		}

		if(isset($data['gender']) && $data['gender'] != ""){

			$customerData['gender'] = $data['gender'];
			$customer->update($customerData);
		}

		if(empty($data['customer_name'])){
			$data['customer_name'] = $customer['name'];
		}

		if(empty($data['customer_email'])){
			$data['customer_email'] = $customer['email'];
		}

		if(empty($data['customer_phone'])){

			$data['customer_phone'] = "-";

			if(empty($customer['contact_no'])){

				$data['customer_phone'] = $customer['contact_no'];
			}
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
		'app_version' => 'required',
		'device_type' => 'required'
		];

		$data = $_REQUEST;

        
        

		$validator = Validator::make($data,$rules);

		if($validator->fails()) {

			return Response::json(array('status' => 401,'message' =>$this->errorMessage($validator->errors())),401);
		}

		$current_version_android = 5.0;
		$current_version_ios = '5.1.6';

		$last_stable_version_android = 5.23;

		Log::info('forceupdate::: ', [$data["app_version"]]);
		if($data["device_type"] == "android"){
			$result_android = array(
				//"message" => "Version ".$current_version_android." is available on Play Store",
				"message" => "Update is available on Play Store",
				"dismiss" => false,
				"force_update" => false
			);

			if(floatval($data["app_version"]) < $last_stable_version_android){

				$result_android['force_update'] = true;
			}

            if(intval(Request::header('Os-Version')) >= 9 && Request::header('App-Version') < '5.13'){
                $result_android = [
                    "message" => "Update is available on Play Store",
                    "dismiss" => false,
                    "force_update" => true
                    ];
            }

			return Response::json($result_android,200);
		}

		$result_ios = array(
			"title" => "Update required",
			"description" => "Fitternity app has been updated and you need to install a newer version of the application.",
			"force_update" => false,
			"dismiss" => false,
			"available_version" => $current_version_ios,
		);

		if($data["app_version"] < $current_version_ios){

			$result_ios['force_update'] = true;
		}

		return Response::json(array('status' => 200,'data' => $result_ios),200);

	}

	public function appConfig(){

		$app_version = Request::header('App-Version');
		$device_type = Request::header('Device-Type');

		$current_version_android = 3.5;
		$current_version_ios = 4.5;
		$force_update_android = false;
		$force_update_ios = false;
		
		$api['city'] = 1524465508;
		$api['home'] = 1524465508;

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

	/*public function applyPromotionCode(){
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
			$cashback_amount = 0;
			if($customer_update){
				// switch($code){
				// 	case "fitgift" :  $cashback_amount = 2000;
				// 	break;
				// 	case "in2017" :  $cashback_amount = 2000;
                //     break;
				// }
				$cashback_amount = $fitcashcode['amount'];
				$customer 	=	Customer::find($customer_id);				


				$customerwallet 		= 		Customerwallet::where('customer_id',$customer_id)->orderBy('_id', 'desc')->first();
				if($customerwallet){
					$customer_balance 	=	$customerwallet['balance'] + $cashback_amount;				
					$customer_balance_fitcashplus = $customerwallet['balance_fitcash_plus'];
				}else{
					$customer_balance 	=	 $cashback_amount;
					$customer_balance_fitcashplus = 0;
				}
				
				$walletData = array(
					"customer_id"=> $customer_id,
					"amount"=> $cashback_amount,
					"amount_fitcash" => $cashback_amount,
                    "amount_fitcash_plus" => 0,
					"type"=>'CASHBACK',
					"code"=>	$code,
					"balance"=>	$customer_balance,
					"balance_fitcash_plus"=>$customer_balance_fitcashplus,
					"description"=>'CASHBACK ON Promotion amount - '.$cashback_amount
					);

				if($fitcashcode['type'] == "restricted"){
					$walletData["vendor_id"] = $fitcashcode['vendor_id'];
					$vb = array("vendor_id"=>$fitcashcode['vendor_id'],"balance"=>$cashback_amount);
					$customer_update = Customer::where('_id', $customer_id)->push('vendor_balance', $vb, true);
				}

				if($fitcashcode['type'] == "fitcashplus"){

					$walletData["type"] = "FITCASHPLUS";
					$walletData["amount_fitcash"] = 0;
					$walletData["amount_fitcash_plus"] = $cashback_amount;

					if($customerwallet){
						$walletData["balance"] = $customerwallet['balance'];
						$walletData["balance_fitcash_plus"] = $customerwallet['balance_fitcash_plus'] + $cashback_amount;				
					}else{
						$walletData["balance"] = 0;
						$walletData["balance_fitcash_plus"] = $cashback_amount;
					}

					$walletData["description"] = "Added FitCash+ on PROMOTION Rs - ".$cashback_amount;
				}

				
				$this->utilities->walletTransaction($walletData);

				$resp 	= 	array('status' => 200,'message' => "Thank you. Rs ".$cashback_amount." has been successfully added to your fitcash wallet", 'walletdata' => $walletData);
				return  Response::json($resp, 200);	
			}
		}
	}*/

	public function applyPromotionCode(){

		$data = Input::json()->all();
		
		if(empty(Request::header('Authorization'))){
			$resp 	= 	array('status' => 400,'message' => "Customer Token Missing");
			return  Response::json($resp, 400);
		}

		if(empty($data['code'])){
			$resp 	= 	array('status' => 400,'message' => "Promotion Code Missing - code");
			return  Response::json($resp, 400);
		}

		$code = trim(strtoupper($data['code']));

		// if(is_numeric(strpos($code, 'R-')) && strpos($code, 'R-') == 0){
		// 	return $this->setReferralData($code);
		// }

		// if(strlen($code)==9 && substr($code, -1 ) == 'R'){
		// 	Log::info("inside referral ");
		// 	$referral = $this->setReferralData($code);
		// 	Log::info($referral);
		// 	if($referral['status']==200){
		// 		return $referral;
		// 	}else{
		// 		$resp 	= 	array('status' => 401,'message' => "Referral code not valid");
		// 		return $resp;
		// 	}
		// }

		

		$code = trim(strtolower($data['code']));

		$fitcashcode = Fitcashcoupon::where('code',$code)->where("expiry",">",time())->first();


		if (!isset($fitcashcode) || $fitcashcode == "") {
			$resp 	= 	array('status' => 404,'message' => "Invalid Promotion Code");
			return Response::json($resp,404);
		}

		if(Request::header('Authorization')){

			$decoded          				=       decode_customer_token();
			$customer_id 					= 		intval($decoded->customer->_id);
			$customer_email 				= 		$decoded->customer->email;

			$customer_phone 				= 		null;

			if(!empty($decoded->customer->contact_no)){
				$customer_phone = $decoded->customer->contact_no;
			}
			
			$already_applied_promotion 		= 		Customer::where('_id',$customer_id)->whereIn('applied_promotion_codes',[$code])->count();

			if($code == 'gwdfit'){

				$customer = Customer::find($customer_id);
				$customer_tansactions_new_wallet = Wallet::where('customer_id', $customer_id)->count();
				
				if($customer_tansactions_new_wallet > 0 || (isset($customer->balance) && $customer->balance > 0) || (isset($customer->balance_fitcash_plus) && $customer->balance_fitcash_plus > 0)){

					$resp 	= 	array('status' => 400,'message' => "This promotion code is applicable only for new users");
					return  Response::json($resp, 400);
				
				}
			}	
			
			if($already_applied_promotion > 0){
				$resp 	= 	array('status' => 400,'message' => "You have already applied promotion code");
				return  Response::json($resp, 400);
			}

			if (isset($fitcashcode->quantity) && $fitcashcode->quantity != "") {
				$already_applied_promotion 		= 		Customer::whereIn('applied_promotion_codes',[$code])->count();
				if($already_applied_promotion >= $fitcashcode->quantity){
					$resp 	= 	array('status' => 404,'message' => "Promotion code already used by other customers");
					return Response::json($resp,404);
				}
			}
			
			if (is_array($fitcashcode->customer_emails)) {
				
				if(!in_array(strtolower($customer_email), $fitcashcode->customer_emails)){
					$resp 	= 	array('status' => 404,'message' => "Invalid Promotion Code");
					return Response::json($resp,404);
				}
			}
		
			if (is_array($fitcashcode->customer_phones)) {
				
				
                if(!empty($fitcashcode->customer_phones[0]['valid_till'])){
                        
                    $values = array_column($fitcashcode->customer_phones, 'value');
                    $dates = array_column($fitcashcode->customer_phones, 'valid_till');
                    
                    if(empty($customer_phone) || !in_array($customer_phone, $values) || time() > $dates[array_search($customer_phone, $values)]->sec){
                        $resp 	= 	array('status' => 404,'message' => "Invalid Promotion Code");
                        return Response::json($resp,404);
                    }
                    
                }else{
                    
                    if(empty($customer_phone) || !in_array($customer_phone, $fitcashcode->customer_phones)){
                        $resp 	= 	array('status' => 404,'message' => "Invalid Promotion Code");
                        return Response::json($resp,404);
                    }

                }
			}

			$customer_update 	=	Customer::where('_id', $customer_id)->push('applied_promotion_codes', $code, true);
			// $customer_update 	=	1;
			$cashback_amount = 0;

			if($customer_update){

				$cashback_amount = $fitcashcode['amount'];

				$customer 	=	Customer::find($customer_id);	

				// $customerwallet 		= 		Customerwallet::where('customer_id',$customer_id)->orderBy('_id', 'desc')->first();
				// if($customerwallet){
				// 	$customer_balance 	=	$customerwallet['balance'] + $cashback_amount;				
				// 	$customer_balance_fitcashplus = $customerwallet['balance_fitcash_plus'];
				// }else{
				// 	$customer_balance 	=	 $cashback_amount;
				// 	$customer_balance_fitcashplus = 0;
				// }


				$current_wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

				if (isset($fitcashcode['topup']) && $fitcashcode['topup']){

					if($cashback_amount > $current_wallet_balance){

						$cashback_amount = $cashback_amount - $current_wallet_balance;

					}else{

						$resp 	= 	array('status' => 400,'message' => "You have already enough balance");
						return  Response::json($resp, 400);
					}

				}

				
				$walletData = array(
					"customer_id"=> $customer_id,
					"amount"=> $cashback_amount,
					"amount_fitcash" => $cashback_amount,
                    "amount_fitcash_plus" => 0,
					"type"=>'CASHBACK',
					"code"=>$code,
					"coupon"=>$code,
					"entry"=>'credit',
					"description"=>'CASHBACK ON Promotion amount - '.$cashback_amount
				);

				if($fitcashcode['type'] == "restricted"){
					$walletData["vendor_id"] = $fitcashcode['vendor_id'];
					$vb = array("vendor_id"=>$fitcashcode['vendor_id'],"balance"=>$cashback_amount);
					$customer_update = Customer::where('_id', $customer_id)->push('vendor_balance', $vb, true);
				}
				if(!empty($fitcashcode['order_type'])){
					$walletData["order_type"] = $fitcashcode['order_type'];
				}
				if(!empty($fitcashcode['remove_wallet_limit'])){
					$walletData["remove_wallet_limit"] = $fitcashcode['remove_wallet_limit'];
				}

				if($fitcashcode['type'] == "fitcashplus"){

					$walletData["type"] = "FITCASHPLUS";
					$walletData["amount_fitcash"] = 0;
					$walletData["amount_fitcash_plus"] = $cashback_amount;
                    $walletData["description"] = "Added FitCash+ on PROMOTION Rs - ".$cashback_amount;
                    

                    if(isset($fitcashcode["valid_till_secs"])){
                        
                        $walletData["validity"] = strtotime('midnight', time() + $fitcashcode["valid_till_secs"]);
						$walletData["description"] = "Added FitCash+ on PROMOTION Rs - ".$cashback_amount.". Expires On : ".date('d-m-Y', strtotime('-1 day',$walletData["validity"]));

                    }else if(isset($fitcashcode["valid_till"])){
                        
                        $walletData["validity"] = $fitcashcode["valid_till"];
						$walletData["description"] = "Added FitCash+ on PROMOTION Rs - ".$cashback_amount.". Expires On : ".date('d-m-Y', strtotime('-1 day',$walletData["validity"]));
                    
                    }
				}

				if((!empty($fitcashcode['valid_finder_id']))){

					$walletData["valid_finder_id"] = intval($fitcashcode['valid_finder_id']);
					$walletData["finder_id"] = intval($fitcashcode['valid_finder_id']);
				
				}

				$this->utilities->walletTransaction($walletData);

				$resp 	= 	array('status' => 200,'message' => "Thank you. Rs ".$cashback_amount." has been successfully added to your fitcash wallet", 'walletdata' => $walletData);
				if(!empty($fitcashcode['success_message'])){
					$resp['message'] = $fitcashcode['success_message'];
				}
				if($code == "yogaday"){
					$resp["popup"] = array();
					$resp["popup"]["header_image"] = "https://b.fitn.in/iconsv1/global/fitcash.jpg";
					$resp["popup"]["header_text"] = "Congratulations";
					$resp["popup"]["text"] = "Chal chal bht hua";
					$resp["popup"]["button"] = "Khareed Le";
					$resp["popup"]["deep_link_url"] = "ftrnty://ftrnty.com/v/7146";
				}
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

		if(!isset($data['booktrial_id']) && !isset($data['order_id']) && isset($data['label']) && isset($data['customer_email'])){

			$emailTracking = Emailtracking::where('customer_email',$data['customer_email'])->where('label',$data['label'])->first();
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
			$customer->update();
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
		$bankData = array();
		foreach ($emiStruct as $emi) {
			if(isset($data['bankName']) && !isset($data['amount'])){
				if($emi['bankName'] == $data['bankName']){
					if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
					// Log::info("inside1");
					$emiData = array();
						$emiData['total_amount'] =  "";
						$emiData['emi'] ="";
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
					array_push($response['emiData'], $emiData);
					
					if(isset($bankData[$emi['bankName']])){
							array_push($bankData[$emi['bankName']], $emiData);
						}else{
							$bankData[$emi['bankName']] = [$emiData];
						}

					
				}
			
			}elseif(isset($data['bankName'])&&isset($data['amount'])){
					if($emi['bankName'] == $data['bankName'] && $data['amount']>=$emi['minval']){
						// Log::info("inside2");
						$emiData = array();
						if(!in_array($emi['bankName'], $bankList)){
							array_push($bankList, $emi['bankName']);
						}
                        $interest = $emi['rate']/1200.00;
                        $t = pow(1+$interest, $emi['bankTitle']);
                        $x = $data['amount'] * $interest * $t;
                        $y = $t - 1;
                        $emiData['emi'] = round($x / $y,0);
                        $emiData['total_amount'] =  (string)($emiData['emi'] * $emi['bankTitle']);
                        $emiData['emi'] = (string)$emiData['emi'];
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
						array_push($response['emiData'], $emiData);
						
						if(isset($bankData[$emi['bankName']])){
							array_push($bankData[$emi['bankName']], $emiData);
						}else{
							$bankData[$emi['bankName']] = [$emiData];
						}

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
					// Log::info("inside3");
					$emiData = array();
                    $interest = $emi['rate']/1200.00;
                    $t = pow(1+$interest, $emi['bankTitle']);
                    $x = $data['amount'] * $interest * $t;
                    $y = $t - 1;
                    $emiData['emi'] = round($x / $y,0);
                    $emiData['total_amount'] =  (string)($emiData['emi'] * $emi['bankTitle']);
                    $emiData['emi'] = (string)$emiData['emi'];
					$emiData['months'] = (string)$emi['bankTitle'];
					$emiData['bankName'] = $emi['bankName'];
                    $emiData['bankCode'] = $emi['bankCode'];
					$emiData['rate'] = (string)$emi['rate'];
					$emiData['minval'] = (string)$emi['minval'];
					array_push($response['emiData'], $emiData);
					
					if(isset($bankData[$emi['bankName']])){
							array_push($bankData[$emi['bankName']], $emiData);
					}else{
						$bankData[$emi['bankName']] = [$emiData];
					}

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
				// Log::info("inside4");
				$emiData = array();
						$emiData['total_amount'] =  "";
						$emiData['emi'] ="";
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)(string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
				array_push($response['emiData'], $emiData);
				
				if(isset($bankData[$emi['bankName']])){
							array_push($bankData[$emi['bankName']], $emiData);
				}else{
					$bankData[$emi['bankName']] = [$emiData];
				}

			}
		}
		/*$device_type = Request::header('Device-Type');
		$app_version = Request::header('App-Version');
		Log::info($device_type);
		Log::info($app_version);
		if($device_type && $app_version && in_array($device_type, ['android', 'ios']) && version_compare($app_version, '4.4.2')>0){
			$response = [];*/
			foreach($bankData as $key => $value){

				//echo"<pre>";print_r($value);exit;

				foreach ($value as $key_emiData => &$value_emiData) {

					$message = "Rs. ".$value_emiData['emi']." will be charged on your credit card every month for the next ".$value_emiData['months']." months";

					$value_emiData['message'] = $message;
				}

				$response['bankData'][] = [
					'bankName' => $key,
					'emiData' => $value,
				];
			}
		// }
		$response['bankList'] = $bankList;
	    return $response;
	}


	public function orderDetail($order_id){

		Log::info("----------------orderDetail : ".$order_id);

		//$decoded = decode_customer_token();

		$order_id = (int) $order_id;

		$order = Order::with(['service'=>function($query){$query->select('slug');}])->find($order_id);

	    if(!$order){

	        $resp   =   array("status" => 401,"message" => "Order Does Not Exists");
	        return Response::json($resp,$resp["status"]);
	    }

	    /*if($order->customer_email != $decoded->customer->email){
	        $resp   =   array("status" => 401,"message" => "Invalid Customer");
	        return Response::json($resp,$resp["status"]);
	    }*/

        // if(!empty($order['extended_validity'])){
        //     return $this->sessionPackDetail($order_id);
        // }

	    $finder = Finder::find((int)$order->finder_id);

	    $data = [];
        $data['finder_slug'] = $finder['slug'];
        $data['service_slug'] = $order['service']['slug'];
	    $data['order_id'] = $order_id;
	    $data['start_date'] = strtotime($order->start_date);

	    if(isset($order->end_date) && $order->end_date != ""){
	    	$data['end_date'] = strtotime($order->end_date);
	    }

	    $data['service_name'] = $order->service_name;
	    $data['duration'] = $order->service_duration;
	    $data['subscription_code'] = $order->code;
	    $data['amount'] = $order->amount;
	    if($order->amount_customer){
	    	$data['amount'] = $order->amount_customer;
	    }
	    $finderData = [];
	    $finderData['id'] = $order->finder_id;
	    $finderData['name'] = $order->finder_name;

	    if(($order->type != "healthytiffinmembership")){

	    	$finderData['address'] = strip_tags($order->finder_address);
	    	$finderData['location'] = $order->finder_location;
	    	$finderData['geo'] = ["lat"=>$order->finder_lat,"lon"=>$order->finder_lon];
	    }
	    
	    $finderData['cover_image'] = ($finder['coverimage'] != '') ? Config::get('app.s3_finderurl.cover').$finder['coverimage'] : Config::get('app.s3_finderurl.cover').'default/'.$finder['category_id'].'-'.rand(1, 4).'.jpg';
	    $data['finder'] = $finderData;

	    $extraInfoData = [];
	    $extraInfoData['contact_name'] = ($order->finder_poc_for_customer_name) ? $order->finder_poc_for_customer_name : "";
	    $extraInfoData['contact_number'] = ($order->finder_poc_for_customer_no) ? $order->finder_poc_for_customer_no : "";
	    $extraInfoData['what_to_carry'] = ($order->what_i_should_carry) ? $order->what_i_should_carry : "";
	    $extraInfoData['what_to_expect'] = ($order->what_i_should_expect) ? $order->what_i_should_expect : "";
	    $data['extra_info'] = $extraInfoData;

		$getAction = $this->getAction($order);

	    $data["action"] = $getAction["action"];
	    $data["feedback"] = $getAction["feedback"];

		$data["action_new"] = $this->getActionV1($order);

	    $reviewData = null;
	    /*$review = Review::active()->where('finder_id',(int)$order->finder_id)->where('customer_id',(int)$order->customer_id)->first();

	    if($review){
	    	$reviewData[] = [
	    		"id"=>(int) $review->_id,
	    		"rating"=>$review->rating,
	    		"detail_rating"=>$review->detail_rating,
	    		"description"=>$review->description
	    	];
	    }*/

	    $data['review'] = $reviewData;

	    return Response::json($data,200);

	}

	public function getAction($order,$method = false){

		$finder = Finder::find((int)$order['finder_id']);

		$cult_vendor_flag = false;

		if($finder && isset($finder['brand_id']) && $finder['brand_id'] == 134){

			$cult_vendor_flag = true;
		}

		$action = null;

		$change_start_date = true;
		$renew_membership = true;
		$upgrade_membership = true;

		if(isset($_GET['device_type']) && in_array($_GET['device_type'], ['ios','android'])){

			if($method == "orderHistory"){
				$change_start_date = false;
			}

		}

		if(!isset($order->upgrade_membership) && isset($order['start_date']) && time() >= strtotime($order['start_date'].'+5 days') && time() <= strtotime($order['start_date'].'+31 days') && isset($order['end_date']) && strtotime($order['end_date']) >= time() && isset($order['duration_day']) && $order['duration_day'] <= 180 && empty($order['studio_extended_validity'])){
			$action = [
				"button_text"=>"Upgrade",
				"activity"=>"upgrade_membership",
				"color"=>"#26ADE5",
				"info" => "Commit yourself for a longer duration. Upgrade your current membership with insider discounts and other benefits.",
				"popup" =>[
					"title"=>"",
					"message"=>"Commit yourself for a longer duration. Upgrade your current membership with insider discounts and other benefits."
				]
			];
		}

		if(!isset($order->preferred_starting_change_date) && isset($order['start_date']) && time() <= strtotime($order['start_date'].'+11 days') && $change_start_date && !$cult_vendor_flag && empty($order['studio_extended_validity'])){

			$min_date = strtotime('+1 days');
			$max_date = strtotime($order['created_at'].'+29 days');
			$available_days = null;


			if(isset($order->batch) && !empty($order->batch) && is_array($order->batch)){

				$minDate = [];
				$maxDate = [];

				foreach ($order->batch as $key => $value) {
					$available_days[] = $value["weekday"];
					$minDate[] = $this->closestDate($value["weekday"],time());
					$maxDate[] = $this->closestDate($value["weekday"],$max_date);
				}

				// Log::info("available_days--------",$available_days);
				// Log::info("minDate--------",$minDate);
				// Log::info("maxDate--------",$maxDate);

				foreach ($minDate as $key => $value) {

					if($value > time()){
						$min_date = $value;
						break;
					}
				}

				foreach ($maxDate as $key => $value) {

					if($value < time()){
						$max_date = $value;
					}
				}
			}

			$action = [
				"button_text"=>"Change",
				"activity"=>"update_starting_date",
				"color"=>"#7AB317",
				"info" => "Don't miss even a single day workout. Change your membership start date basis your convenience. Not applicable, if you have already started with your membership.",
				"min_date"=> $min_date,
				"max_date"=> $max_date,
				"available_days"=> $available_days,
				"popup" =>[
					"title"=>"Change Start Date",
					"message"=>"Change Start Date"
				]
			];
		}

		if(!isset($order->renew_membership) && isset($order['duration_day']) && isset($order['start_date']) && $renew_membership){

			$renewal_date = array();
			$validity = (int) $order['duration_day'];
			$start_date = $order['start_date'];

			if($validity >= 30 && $validity < 90){

				$min_date = strtotime($start_date ."+ ".($validity-7). "days");
				$max_date = strtotime($start_date ."+ ".($validity+30). "days");

			}elseif($validity >= 90 && $validity < 180){

				$min_date = strtotime($start_date ."+ ".($validity-15). "days");
				$max_date = strtotime($start_date ."+ ".($validity+30). "days");

			}elseif($validity >= 180){
				
				$min_date = strtotime($start_date ."+ ".($validity-30). "days");
				$max_date = strtotime($start_date ."+ ".($validity+30). "days");

			}

			if(isset($min_date) && isset($max_date) && time() >= $min_date  && time() <= $max_date){

				$days_to_go = ceil(($max_date - time()) / 86400);

				$action = [
					"button_text"=>"Renew",
					"activity"=>"renew_membership",
					"color"=>"#EF1C26",
					"info" => "Renew your membership with the lowest price and assured rewards",
					"popup" =>[
						"title"=>"Renew Membership",
						"message"=>"Renew Membership"
					]
				];

			}
		}

		$feedback = [];

		if(isset($order->finder_name) && $order->finder_name != ""){
			$feedback = ["info"=>"Share your experience at ".ucwords($order->finder_name)." and we will make sure they are notified with it","show"=>true];
		}else{
			$feedback = ["info"=>"Share your experience and we will make sure they are notified with it","show"=>true];
		}
		
		if(isset($order["review_added"])){
			$feedback["show"] = false;
		}

		$return = [
			'action' => $action,
			'feedback' => $feedback
		];

		return $return;

	}

	public function getActionV1($order,$method = false){

		$finder = Finder::find((int)$order['finder_id']);

		$cult_vendor_flag = false;

		if($finder && isset($finder['brand_id']) && $finder['brand_id'] == 134){

			$cult_vendor_flag = true;
		}

		$action = [
			'change_start_date'=>null,
			'change_start_date_request'=>null,
			'renew_membership'=>null,
			'upgrade_membership'=>null,
			'feedback'=>null,
		];


        if(!empty($order['extended_validity'])){
			$action['book_session'] = $this->sessionPackDetail($order['_id']);
			$action['book_session']['sessions_left'] = $action['book_session']['sessions_left'].' sessions left';
            //  if(strtotime($order['end_date']) > time() && !empty($order['sessions_left'])){
            //     $action['book_session'] = [
            //         "button_text"=>"Book a session",
            //         "activity"=>"book",
            //         "color"=>"#26ADE5",
            //         "info" => "Book a workout session",
            //     ];

            // }else{
            //     $action['renew_membership'] =[
            //         "button_text"=>"Renew",
            //         "activity"=>"renew_membership",
            //         "color"=>"#EF1C26",
            //         "info" => "Renew your membership with the lowest price and assured rewards",
            //         "popup" =>[
            //             "title"=>"",
            //             "message"=>"Renew your membership with the lowest price and assured rewards"
            //         ]
            //     ];
                
            // }   

            return $action;    
        }


		$change_start_date = true;
		$renew_membership = true;
		$upgrade_membership = true;
		$change_start_date_request = true;

		if(isset($_GET['device_type']) && in_array($_GET['device_type'], ['ios','android'])){

			if($method == "orderHistory"){
				$change_start_date = false;
			}

		}

		if(!isset($order->upgrade_membership) && isset($order['success_date']) && time() >= strtotime($order['success_date']) && $upgrade_membership && strtotime($order['end_date']) >= time() && empty($order['studio_extended_validity'])){
			$action['upgrade_membership'] = [
				"button_text"=>"Upgrade",
				"activity"=>"upgrade_membership",
				"color"=>"#26ADE5",
				"info" => "Commit yourself for a longer duration. Upgrade your current membership with insider discounts and other benefits.",
				"popup" =>[
					"title"=>"",
					"message"=>"Commit yourself for a longer duration. Upgrade your current membership with insider discounts and other benefits."
				]
			];
		}

		if(!isset($order->preferred_starting_change_date) && isset($order['success_date']) && time() <= strtotime($order['success_date'].'+10 days') && $change_start_date && !$cult_vendor_flag && empty($order['studio_extended_validity'])){

			$min_date = strtotime('+1 days');
			$max_date = strtotime($order['success_date'].'+29 days');
			$available_days = null;


			if(isset($order->batch) && !empty($order->batch) && is_array($order->batch)){

				$minDate = [];
				$maxDate = [];

				foreach ($order->batch as $key => $value) {
					$available_days[] = $value["weekday"];
					$minDate[] = $this->closestDate($value["weekday"],time());
					$maxDate[] = $this->closestDate($value["weekday"],$max_date);
				}

				foreach ($minDate as $key => $value) {

					if($value > time()){
						$min_date = $value;
						break;
					}
				}

				foreach ($maxDate as $key => $value) {

					if($value < time()){
						$max_date = $value;
					}
				}
			}

			$action['change_start_date'] = [
				"button_text"=>"Change",
				"activity"=>"update_starting_date",
				"color"=>"#7AB317",
				"info" => "Don't miss even a single day workout. Change your membership start date basis your convenience. Not applicable, if you have already started with your membership.",
				"min_date"=> $min_date,
				"max_date"=> $max_date,
				"available_days"=> $available_days,
				"popup" =>[
					"title"=>"",
					"message"=>"Don't miss even a single day workout. Change your membership start date basis your convenience. Not applicable, if you have already started with your membership."
				]
			];

		}

		if($action['change_start_date'] == null && !isset($order->requested_preferred_starting_date) && isset($order['success_date']) && time() <= strtotime($order['success_date'].'+29 days') && $change_start_date_request && !$cult_vendor_flag && empty($order['studio_extended_validity'])){

			$min_date = strtotime('+1 days');
			$max_date = strtotime($order['success_date'].'+29 days');
			$available_days = null;

			if(isset($order->batch) && !empty($order->batch) && is_array($order->batch)){

				$minDate = [];
				$maxDate = [];

				foreach ($order->batch as $key => $value) {
					$available_days[] = $value["weekday"];
					$minDate[] = $this->closestDate($value["weekday"],time());
					$maxDate[] = $this->closestDate($value["weekday"],$max_date);
				}

				foreach ($minDate as $key => $value) {

					if($value > time()){
						$min_date = $value;
						break;
					}
				}

				foreach ($maxDate as $key => $value) {

					if($value < time()){
						$max_date = $value;
					}
				}
			}

			$action['change_start_date_request'] = [
				"button_text"=>"Change",
				"activity"=>"change_start_date_request",
				"color"=>"#7AB317",
				"info" => "Don't miss even a single day workout. Change your membership start date basis your convenience. Not applicable, if you have already started with your membership.",
				"min_date"=> $min_date,
				"max_date"=> $max_date,
				"available_days"=> $available_days,
				"popup" =>[
					"title"=>"",
					"message"=>"Don't miss even a single day workout. Change your membership start date basis your convenience. Not applicable, if you have already started with your membership."
				]
			];
				
		}

		if(!isset($order->renew_membership) && isset($order['duration_day']) && isset($order['start_date']) && $renew_membership){

			$renewal_date = array();
			$validity = (int) $order['duration_day'];
			$start_date = $order['start_date'];

			if($validity >= 30 && $validity < 90){

				$min_date = strtotime($start_date ."+ ".($validity-7). "days");
				$max_date = strtotime($start_date ."+ ".($validity+30). "days");

			}elseif($validity >= 90 && $validity < 180){

				$min_date = strtotime($start_date ."+ ".($validity-15). "days");
				$max_date = strtotime($start_date ."+ ".($validity+30). "days");

			}elseif($validity >= 180){
				
				$min_date = strtotime($start_date ."+ ".($validity-30). "days");
				$max_date = strtotime($start_date ."+ ".($validity+30). "days");

			}

			if(isset($min_date) && isset($max_date) && time() >= $min_date  && time() <= $max_date){

				$days_to_go = ceil(($max_date - time()) / 86400);

				$action['renew_membership'] = [
					"button_text"=>"Renew",
					"activity"=>"renew_membership",
					"color"=>"#EF1C26",
					"info" => "Renew your membership with the lowest price and assured rewards",
					"popup" =>[
						"title"=>"",
						"message"=>"Renew your membership with the lowest price and assured rewards"
					]
				];

			}
		}

		if($action['renew_membership'] != null){
			$action['upgrade_membership'] = null;
		}

		$feedback = [];

		if(isset($order->finder_name) && $order->finder_name != ""){
			$action["feedback"] = ["info"=>"Share your experience at ".ucwords($order->finder_name)." and we will make sure they are notified with it","show"=>true];
		}else{
			$action["feedback"] = ["info"=>"Share your experience and we will make sure they are notified with it","show"=>true];
		}
		
		if(isset($order["review_added"])){
			$action["feedback"]["show"] = false;
		}

		return $action;

	}

	public function closestDate($day,$date){

		$date = ($date) ? $date : time();
	    $day = ucfirst($day);

	    if(date('l', $date) == $day)
	        return $date;
	    else if(abs($date-strtotime('next '.$day)) < abs($date-strtotime('last '.$day)))
	        return strtotime('next '.$day,$date);
	    else
	        return strtotime('last '.$day,$date);

	}


	public function notificationTracking($id){

		$notificationTracking = NotificationTracking::find($id);

		if($notificationTracking){

			if(isset($_GET['action']) && $_GET['action'] == 'received'){
				$notificationTracking->update(['received'=>time()]);
			}

			$notificationSwitch = $this->notificationSwitch($notificationTracking);

			return Response::json($notificationSwitch,200);
		}

		return Response::json(["message"=>"Data Not Found"],404);

	}

	public function notificationSwitch($notificationTracking){
		
		$notificationTracking->update(['clicked'=>time()]);

		$time = $notificationTracking->time;
		
		$data = array();

		$response = array();

		$array = [
			"time",
			"customer_id",
			"schedule_for",
			"max_time",
			"text"
		];

		$response["notification_id"] = $notificationTracking["_id"];
		$response["cancelable"] = false;
		
		foreach ($array as $value) {

			if(isset($notificationTracking[$value])){
				$response[$value] = $notificationTracking[$value];
			}
		}

		if(isset($notificationTracking["order_id"])){

			$transaction = Order::find((int)$notificationTracking["order_id"]);
			
			$dates = array('followup_date','last_called_date','preferred_starting_date', 'called_at','subscription_start','start_date','start_date_starttime','end_date', 'order_confirmation_customer');
			$unset_keys = [];

			foreach ($dates as $key => $value){
				if(isset($transaction[$value]) && $transaction[$value]==''){
					// $transaction->unset($value);
					array_push($unset_keys, $value);
			
				}
			}

			if(count($unset_keys)>0){
				$transaction->unset($unset_keys);
			}

		}else if(isset($notificationTracking["booktrial_id"])){

			$transaction = Booktrial::find((int)$notificationTracking["booktrial_id"]);
			
			if($transaction && $transaction->type != 'workout-session' && (!isset($transaction->amount))){
				$response["cancelable"] = true;
			}
			

			$dates = array('start_date', 'start_date_starttime', 'schedule_date', 'schedule_date_time', 'followup_date', 'followup_date_time','missedcall_date','customofferorder_expiry_date','auto_followup_date');

			$unset_keys = [];
			
			foreach ($dates as $key => $value){
				if(isset($transaction[$value]) && $transaction[$value]==''){
					// $transaction->unset($value);
					array_push($unset_keys, $value);
			
				}
			}

			if(count($unset_keys)>0){
				$transaction->unset($unset_keys);
			}
		}

		if($transaction){

			if(isset($notificationTracking["order_id"])){

				$response["transaction_id"] = $notificationTracking["order_id"];
				$response["transaction_type"] = "order";
	    		$response['amount'] = $transaction['amount'];
	    		$response['duration'] = $transaction['service_duration'];
	    		$response['payment_mode'] = $transaction['payment_mode'];

			}else if(isset($notificationTracking["booktrial_id"])){

				$response["transaction_id"] = $notificationTracking["booktrial_id"];
				$response["transaction_type"] = "trial";
			}

			$response["finder_name"] = $transaction["finder_name"];
			$response["finder_id"] = (int)$transaction["finder_id"];
			$response["lat"] = $transaction["finder_lat"];
			$response["lon"] = $transaction["finder_lon"];
			$response["finder_location"] = $transaction["finder_location"];
			$response["category_id"] = isset($transaction["finder_category_id"])?$transaction["finder_category_id"]:"";
			$response["finder_address"] = $transaction["finder_address"];
			$response["service_id"] = (int)$transaction->service_id;
			$response["service_name"] = isset($transaction["service_name"])?$transaction["service_name"]:"";
			
			
			if(isset($transaction->ratecard_id)){
				$response["ratecard_id"] = (int)$transaction->ratecard_id;
			}

			$data = $transaction->toArray();
		}

		$response['fitcash'] = 0;
		$response['remark'] = "";

		if(!empty($data)){

			$response["title"] = isset($notificationTracking["title"])? $notificationTracking["title"]: "Default title";
			$response["notification_msg"] = $notificationTracking["text"];
			$response["finder_slug"] = Finder::find($data['finder_id'])->slug;
			$response["type"] = $data["type"];
			

			$followup_date = null;
			
			if(isset($data["followup_date"])){

				$followup_date = strtotime($data["followup_date"]);

			}else{

				$start_date = "";
				if(isset($data["schedule_date"])){
					$start_date = date("d-m-Y",strtotime($data["schedule_date"]));
				}

				if(isset($data["start_date"])){
					$start_date = date("d-m-Y",strtotime($data["start_date"]));
				}

				if($start_date != ""){
					$followup_date = strtotime($start_date." +3 days");
				}
			}

			$response['followup_date'] = $followup_date;

			$response['callback_msg']= "Awesome. We'll get in touch with you on $followup_date \n <u>Click here to change date</u>";
			$response["phone"] = Config::get('app.direct_customer_number');
			$hour = (int) date("G", time());
			if($hour<=10 || $hour>=20){
				$response["phone"] = $data["finder_poc_for_customer_no"];
			}

			$response['fitternity_no'] = Config::get('app.direct_customer_number');
			

			switch ($time) {
				case 'n-12': 
					$response["start_time"] = strtoupper($data["schedule_slot_start_time"]);
					$response["start_date"] = date("d-m-Y",strtotime($data["schedule_date"]));
					break;
				case 'n-3': 
					if($this->app_version > '4.4.3'){
						
						$response = array_only($response, ['notification_id', 'transaction_type']);
						$response['block_screen_data'] = $this->getBlockScreenData($time, $data);
					
					}else{
						
						$response["start_time"] = strtoupper($data["schedule_slot_start_time"]);
						$response["start_date"] = date("d-m-Y",strtotime($data["schedule_date"]));

					}
					break;
				case 'n+2': 
					if($this->app_version > '4.4.3'){
						$response = array_only($response, ['notification_id', 'transaction_type']);
						$response['block_screen_data'] = $this->getBlockScreenData($time, $data);
					}else{
						$response["start_time"] = strtoupper($data["schedule_slot_start_time"]);
						$response["start_date"] = date("d-m-Y",strtotime($data["schedule_date"]));
						Booktrial::where('_id', $response["transaction_id"])->update(['final_lead_stage'=> 'post_trial_stage']);
						if(isset($_GET['device_type']) && $_GET['device_type'] == "ios"){
							unset($response["text"]);
						}
					}
					break;
				case 'n-20m':
					$response["start_time"] = strtoupper($data["schedule_slot_start_time"]);
					$response["start_date"] = date("d-m-Y",strtotime($data["schedule_date"]));
					if(isset($_GET['device_type']) && $_GET['device_type'] == "ios"){
						unset($response["text"]);
					}
					break;
				
				case 'n-10m':
				
					$response = array_only($response, ['notification_id', 'transaction_type']);
					Log::info($data);
					$response ['block_screen_data']= $this->getBlockScreenData($time, $data);
		
					break;
				default:

					if($hour>10 && $hour<20){
						$response["phone"] = Config::get('app.followup_customer_number');;
					}
					
					
					$response["start_time"] = "";
					$response["start_date"] = "";
					

					if(isset($data["schedule_slot_start_time"])){
						$response["start_time"] = strtoupper($data["schedule_slot_start_time"]);
					}

					if(isset($data["schedule_date"])){
						$response["start_date"] = date("d-m-Y",strtotime($data["schedule_date"]));
					}

					if(isset($data["start_date"])){
						$response["start_date"] = date("d-m-Y",strtotime($data["start_date"]));
					}
					break;
			}

			if($response["transaction_type"] == "order"){

				

				$batches = array();

				$service = Service::find((int)$data['service_id']);

				if($service){

					$service = $service->toArray();

					if(isset($service['batches']) && count($service['batches']) > 0){

						$batches = $service['batches'];

						foreach ($batches as $batches_key => $batches_value) {

							foreach ($batches_value as $batches_value_key => $value) {

								$batches[$batches_key][$batches_value_key]['slots'] = $value['slots'][0];
							}
						}
					}
				}

				if(isset($data['ratecard_id']) && $data['ratecard_id']){

					$ratecard = Ratecard::find((int)$data['ratecard_id']);

					if($ratecard){

						$ratecard_offers = [];

						if(!empty($ratecard['_id']) && isset($ratecard['_id'])){

							// $ratecard_offers  =   Offer::where('ratecard_id', intval($ratecard['_id']))->where('hidden', false)->orderBy('order', 'asc')
							// 	->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
							// 	->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
							// 	->get(['start_date','end_date','price','type','allowed_qty','remarks']);

							$ratecard_offers = Offer::getActiveV1('ratecard_id', intval($ratecard['_id']), intval($ratecard['finder_id']));

							if(count($ratecard_offers) > 0){

								$ratecard_offers = $ratecard_offers->toArray();

								if(isset($ratecard_offers[0]['price'])){

									$ratecard['special_price'] = $ratecard_offers[0]['price'];

			                    	if($ratecard['price'] == $ratecard_offers[0]['price']){
			                    		$ratecard['special_price'] = 0;
			                    	}
								}
							}
	
						}

						if($ratecard['type'] == 'membership' || $ratecard['type'] == 'packages'){

							if($ratecard['special_price'] > 0){

								$app_discount_amount = intval($ratecard['special_price'] * (Config::get('app.app.discount')/100));
								$ratecard['special_price'] = $ratecard['special_price'] - $app_discount_amount;

							}else{

								$app_discount_amount = intval($ratecard['price'] * (Config::get('app.app.discount')/100));
								$ratecard['price'] = $ratecard['price'] - $app_discount_amount;

							}
						}

						if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
	                    	$response['amount'] = $ratecard['special_price'];
		                }else{
		                    $response['amount'] = $ratecard['price'];
		                }

					}
				}

				$response['batches'] = $batches;

				$customerReward     =   new CustomerReward();
				$calculation        =   $customerReward->purchaseGame($response['amount'], $data["finder_id"], $data["payment_mode"], false, $data["customer_id"]);
				$response['fitcash'] = $calculation['amount_deducted_from_wallet'];
				if(isset($_GET['device_type']) && $_GET['device_type'] == "ios"){
					switch($time){
						case 'pl+3':
						case 'pl+7':
						case 'pl+15':
						case 'pl+30':
							unset($response["text"]);
							if($response['fitcash']>0){
								$response["fitcash_text"] = $response['fitcash']." Fitcash can be applied in the next step";
							}
							break;
						
					}
				}
				$response['remarks'] = "";

			}
			if(isset($response["category_id"])){
				$response["finder_type"] = getFinderType($response["category_id"]);
			}

			
			
		}

		return $response;
	}


	// Diet plan Listing
	public function listMyDietPlan(){
		$jwt_token  = Request::header('Authorization');
		$jwt_key = Config::get('app.jwt.key');
		$jwt_alg = Config::get('app.jwt.alg');
		$decoded = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

		$data['email'] = $decoded->customer->email;
		
		$rules = [
		'email' => 'required|email|max:255'
		];
		$validator = Validator::make($data, $rules);
		$current_diet_plan = Order::active()->where('customer_email',$data['email'])->where('type','diet_plan')->with(array('trainerslotbookings'=>function($query){$query->orderBy('_id','desc');}))->orderBy('_id','desc')->first();
		// $trainerslotBookings = TrainerSlotBooking::where('order_id',$current_diet_plan['_id'])->get();
		if(isset($current_diet_plan)){
			$total_sessions = $current_diet_plan['duration'];
			$sessions_booked = count($current_diet_plan['trainerslotbookings']);
			
			if(count($current_diet_plan['trainerslotbookings']) > 0){
				$can_book = false;
				$last_session_booked = strtotime($current_diet_plan['trainerslotbookings'][0]['date']);
				$sessions_lapsed_before_last_interaction = 0;
				if(isset($current_diet_plan->last_interaction_date)){
					$last_interaction_date = $current_diet_plan['last_interaction_date'];
					$sessions_lapsed_before_last_interaction = intval(floor(((strtotime($last_interaction_date)-$last_session_booked)/(60*60*24) - 3)/14));
					$sessions_lapsed_before_last_interaction = $sessions_lapsed_before_last_interaction>0?$sessions_lapsed_before_last_interaction:0;
				}
				$days_passed_last_session = intval(floor(time()-$last_session_booked)/(60*60*24));
				
				$sessions_lapsed_before_now = 0;
				if($days_passed_last_session>=1){

					$sessions_lapsed_before_now = intval(floor(($days_passed_last_session - 3)/14));
					$sessions_lapsed_before_now = $sessions_lapsed_before_now>0?$sessions_lapsed_before_now:0;
					
					$sessions_lapsed_after_last_interaction = $sessions_lapsed_before_now - $sessions_lapsed_before_last_interaction;
					
					if($sessions_lapsed_after_last_interaction>0){
						$current_diet_plan->sessions_lapsed = isset($current_diet_plan->sessions_lapsed) ? ($current_diet_plan->sessions_lapsed + $sessions_lapsed_after_last_interaction) : $sessions_lapsed_after_last_interaction;
					}

				}
				
				$last_interaction_date = date('d-m-Y', time());
				

				$current_diet_plan->last_interaction_date = $last_interaction_date;
				$current_diet_plan->update();
				$total_sessions_over = $sessions_booked + (isset($current_diet_plan->sessions_lapsed)?$current_diet_plan->sessions_lapsed:0);
			
				if($total_sessions_over < $total_sessions){
					if($days_passed_last_session>0){
						$can_book = $days_passed_last_session >= (14*($sessions_lapsed_before_now+1)-2) && $days_passed_last_session <= (14*($sessions_lapsed_before_now+1)+2);
					}
					if(!$can_book){
						$current_diet_plan->booking_opens_on = date("d-m-Y", ($last_session_booked + (60*60*24*(14*($sessions_lapsed_before_now+1)-2))));
					}
				}

			}else{
				$can_book = true;
			}
			$current_diet_plan->can_book = $can_book;
			$current_diet_plan = $current_diet_plan->toArray();

			if(isset($current_diet_plan['start_date'])){
				$current_diet_plan['start_date'] = date("F j, Y", strtotime($current_diet_plan['start_date']));
			}else{
				$current_diet_plan['start_date'] = "Not scheduled yet.";
			}

			$service = Service::find((int)$current_diet_plan['service_id']);
			
			$current_diet_plan['workout_goal'] = "";
			
			if($service && isset($service->workout_results)){
				$current_diet_plan['workout_goal'] = implode(", ", $service['workout_results']);
			}
			
		}else{
			$current_diet_plan;
		}
		$second_section = array("header" => "<p> what you <span style='color:#F7A81E'>eat</span> <br> is what makes <span style='color:#F7A81E'>you</span>", "subline"=>"<p>You've taken a great step to be fit & fine in your life!</p> <p>Here's what you can expect from the diet plan you've purchased</p>", "content"=>"<ul>    <li>Personalisations based on your requirements</li>    <li>Unlimited access to your dietician</li>    <li>Bi-weekly diet changes</li>    <li>Assorted healthy recipes & hacks</li>    <li>All diet changes synchronized with your trainer</li></ul>");
		$resp = array("current_diet_plan"=>$current_diet_plan, "content_section"=>$second_section);
		return Response::json($resp,200);
	}

	public function storeCustomerAttribution(){
		
		try{

			$data = Input::all();
		
			$rules = Customerattribution::$rules;

			$validator = Validator::make($data,$rules);

			if ($validator->fails()) {
				return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);
			}

			$attribution_array = $data['attribution'];
			$last_key = count($attribution_array) - 1;
			foreach($attribution_array as $key => $attr){
				$lineitem = array();
				$lineitem = array_merge($data, $attr);
				$customerattribution = new Customerattribution($lineitem);
				$customerattribution->_id = Customerattribution::max('_id')?(Customerattribution::max('_id')+1):1;
				$customerattribution->visit_date_epoch = $customerattribution->visit_date = ($attr['date']);
				$customerattribution->bought = $key == $last_key ? true : false;
				unset($customerattribution->attribution);
				unset($customerattribution->date);
				$customerattribution->save();
			}
			
			
			return Response::json(array('status'=>200, 'message'=>'Attribution created succesfully'));

		}catch(Exception $exception){

            $message = array(
                'type'    => get_class($exception),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            );

            Log::error($exception);

            return array('status'=>'fail','error_message'=>$message);
        }
		
	}

	public function getWalletDetails($limit=0,$offset=10){

		$request = Input::json()->all();

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;

		$wallet_summary = [];

		$wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

		$walletTransaction = WalletTransaction::where('customer_id',$customer_id)->orderBy('updated_at','DESC')->get()->groupBy('group');

		if($walletTransaction){

			foreach ($walletTransaction as $group => $transaction) {

				$amount = 0;
				$validity = "";
				$description = "";
				$date = "";
				$entry = "";

				foreach ($transaction as $key => $value) {

					$amount += $value['amount'];

					if(isset($value['validity']) && $validity != ""){
						$validity = date('d-m-Y',$value['validity']);
					}

					if($date == ""){
						$date = date('d-m-Y',strtotime($value['created_at']));
					}

					if($description == ""){
						$description = $value['description'];
					}

					if($entry == ""){
						$entry = $value['entry'];
					}
				}

				$wallet_summary[] = [
					'amount'=>$amount,
					'description'=>$description,
					'date'=>$date,
					'validity'=>$validity,
					'entry'=>$entry
				];

			}

		}

		return Response::json(
			array(
				'status' => 200,
				'wallet_summary' => $wallet_summary,
				'wallet_balance'=>$wallet_balance
				),
			200
		);

	}


	public function orderDemonetisation($order_id){

		$order_id = (int)$order_id;

		$order = Order::find($order_id);

		$message = "";

		if($order && isset($order->demonetisation)){

			$message = "Congratulations!<br/>Your FitCash has been converted to FitCash+ that enables you to do 100% redemption on any transaction on Fitternity.<br/> Any queries? Reach us on 02261222230 / support@fitternity.com";

			if(isset($order->customer_wallet_balance) && $order->customer_wallet_balance > 0){

				$message = "Congratulations!<br/>Your FitCash has been converted to FitCash+ that enables you to do 100% redemption on any transaction on Fitternity.<br/>Your balance is Rs. 1000. Any queries? Reach us on 02261222230 / support@fitternity.com";
			}
		}

		return Response::json(
			array(
				'status' => 200,
				'message' => $message,
				),
			200
		);

	}

	public function getReferralCode(){
		try{
			$jwt = Request::header('Authorization');
			$decoded = $this->customerTokenDecode($jwt);
			$id = $decoded->customer->_id;
			Customer::$withoutAppends = true;

			$customer = Customer::where('_id', $id)->first();
			// return $customer;
			if($customer){

				if(!isset($customer->referral_code)){

					$customer->referral_code = generateReferralCode($customer->name);
					$customer->update();
				}

				$referral_code = $customer['referral_code'];

				$customer_email = $customer->email;

				$url = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/$customer_email#promotion");

				/*$share_message = "Register on Fitternity and earn Rs. 250 FitCash+ which can be used for fitness classes, memberships, diet consulting & more! Use my code $referral_code and apply it in your profile after logging-in $url";*/
				$share_message = "Hi, I found this awesome fitness app - Fitternity. Use my code $referral_code to get Rs 250 Fitcash+ on sign-up. Click $url to download it.";
				$display_message = "Fitter is better together!<br>Refer a friend and both of you get Rs. 250 FitCash + which is fully redeemable on all bookings on Fitternity!<br><br>Valid till 31st December 2018. TCA.";

				if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "ios")){
					$display_message = "Fitter is better together!\nRefer a friend and both of you get Rs. 250 FitCash + which is fully redeemable on all bookings on Fitternity!\n\nValid till 31st December 2018. TCA.";
				}
				
				$email_subject = "Join me on Fitternity & get Rs. 250";
				$email_text = "Fitness on your mind?<br><br>Register on India's largest fitness platform to book fitness classes, memberships, diet plans & more!<br><br>If you use my invite code $referral_code to register yourself on the Fitternity mobile app, we both get Rs. 250 FitCash+ which is fully redeemable on all bookings!<br><br>Download the app and apply code in your profile after logging-in $url";
				
				return $response =  array('status' => 200,'referral_code' => $referral_code, 'message' 	=> $display_message, 'share_message' => $share_message, 'email_subject' => $email_subject, 'email_text' => $email_text);
			
			}else{
				
				return $response =  array('status' => 404,'message'=>"Customer not found");
			
			}
			
		}catch(Exception $e){
			Log::info($e);
			return array('status'=>500, 'message'=>'Something went wrong, Please try again later');
		}
	}
	public function referFriend(){	
			
		try{
			$jwt = Request::header('Authorization');
			$decoded = $this->customerTokenDecode($jwt);
			$id = $decoded->customer->_id;
			Customer::$withoutAppends = true;
			$customer = Customer::where('_id', $id)->first(['name', 'email', 'contact_no', 'referral_code']);
			
			if($customer){
				$referrer_name = $customer->name;
				$req = Input::json()->all();
		        Log::info('referfriend',$req);
		        $rules = [
		            'invitees' => 'required|array',
		        ];
		        $validator = Validator::make($req, $rules);
		        if ($validator->fails()) {
		            return Response::json(
		                array(
		                    'status' => 400,
		                    'message' => $this->errorMessage($validator->errors()
		                    )),400
		            );
		        }
		        // Invitee info validations...........
		        $inviteesData = [];
		        foreach ($req['invitees'] as $value){
		        	$inviteeData = [];
		            if(isset($value['name'])){
		            	Log::info("Inside name");
		            	$inviteeData = ['name'=>$value['name']];
		            }
		            $rules = [
		                'input' => 'required|string'
		            ];
		            $messages = [
		                'input' => 'invitee email or phone is required'
		            ];
		            $validator = Validator::make($value, $rules, $messages);
		            if ($validator->fails()) {
		                return Response::json(
		                    array(
		                        'status' => 400,
		                        'message' => $this->errorMessage($validator->errors()
		                        )),400
		                );
		            }
		            if(filter_var($value['input'], FILTER_VALIDATE_EMAIL) != '') {
		                // valid address
		                $inviteeData = array_add($inviteeData, 'email', $value['input']);
		            }
		            else if(filter_var($value['input'], FILTER_VALIDATE_REGEXP, array(
		                    "options" => array("regexp"=>"/^[2-9]{1}[0-9]{9}$/")
		                )) != ''){
		                // valid phone
		                $inviteeData = array_add($inviteeData, 'phone', $value['input']);
		            }
		            // return $inviteeData;
		            array_push($inviteesData, $inviteeData);
		        }	        
		// return $inviteesData;
		        foreach ($inviteesData as $value){
		            $rules = [
		                'email' => 'required_without:phone|email',
		                'phone' => 'required_without:email',
		            ];
		            $messages = [
		                'email.required_without' => 'valid invitee email or phone is required',
		                'phone.required_without' => 'valid invitee email or phone is required'
		            ];
		            $validator = Validator::make($value, $rules, $messages);
		            if ($validator->fails()) {
		                return Response::json(
		                    array(
		                        'status' => 400,
		                        'message' => $this->errorMessage($validator->errors()
		                        )),400
		                );
		            }
		        }
		        $emails = array_fetch($inviteesData, 'email');
        		$phones = array_fetch($inviteesData, 'phone');
        		// return $customer->email;
        		if(in_array($customer->email, $emails)) {
		            return Response::json(
		                array(
		                    'status' => 422,
		                    'message' => 'You cannot invite yourself email'
		                ),422
		            );
		        }
		        if(in_array($customer->contact_no, $phones)) {
		            return Response::json(
		                array(
		                    'status' => 422,
		                    'message' => 'You cannot invite yourself'
		                ),422
		            );
		        }
		        // return $inviteesData;
		        foreach ($inviteesData as $invitee){
		            $url = 'www.fitternity.com/buy/';
		            $shorten_url = new ShortenUrl();
		            $url = $shorten_url->getShortenUrl($url);
		            if(!isset($url['status']) ||  $url['status'] != 200){
		                return Response::json(
		                    array(
		                        'status' => 422,
		                        'message' => 'Unable to Generate Shortren URL'
		                    ),422
		                );
		            }
		            $url = $url['url'];
		            // Send email / SMS to invitees...
		            $templateData = array(
		                'referral_code'=>$customer['referral_code'],
		                'invitee_name' =>isset($invitee['name'])?$invitee['name']:"",
		                'invitee_email'=>isset($invitee['email'])?$invitee['email']:null,
		                'invitee_phone'=>isset($invitee['phone'])?$invitee['phone']:null,
		                'url' => $url,
		            );
		            Log::info($templateData);
		            isset($templateData['invitee_email']) ? $this->customermailer->referFriend($templateData) : null;
		            isset($templateData['invitee_phone']) ? $this->customersms->referFriend($templateData) : null;
		        }
				
				return $response =  array('status' => 200,'data'=>'Success');
			}else{
				return $response =  array('status' => 404,'message'=>"Customer not found");
			}
			
		}catch(Exception $e){
			Log::info($e);
		}
	}

	public function setReferralData($code){

		$decoded = decode_customer_token();
		$customer_id = intval($decoded->customer->_id);
		$customer = Customer::find($customer_id);
		
		$referrer = Customer::where('referral_code', $code)->where('status', '1')->first();

		$device_type = "";

		if(isset($_GET['device_type'])){
			$device_type = strtolower($_GET['device_type']);
		}

		$header_device_type = Request::header('Device-Type');

		if($header_device_type != "" && $header_device_type != null && $header_device_type != 'null'){

			$device_type = strtolower($header_device_type);
        }

        Log::info('device_type-------------------------------'.$device_type);
	
		if(in_array($device_type, ['ios', 'android']) && $referrer && isset($customer->old_customer) && $customer->old_customer == false && !isset($customer->referrer_id) && $customer_id != $referrer->_id){

			$customer->referrer_id = $referrer->_id;
			$customer->save();
			
			if(!isset($referrer->referred_to)){
				$referrer->referred_to = [];
			}
			$referred_to = $referrer->referred_to;
			array_push($referred_to, $customer->_id);
			$referrer->save();

			$wallet_data = array(
				'customer_id' => $customer->_id,
				'amount' => 250,
				'amount_fitcash' => 0,
				'amount_fitcash_plus' => 250,
				'type' => "REFERRAL",
				"entry"=>'credit',
				'description' => "Referral fitcashplus",
				'order_id' => 0
			);

			$walletTransaction = $this->utilities->walletTransaction($wallet_data);

			return array('status'=>200, 'message'=>'250 Fitcash+ has been added to your wallet');

		}else{

			return array('status'=>400, 'message'=>'Incorrect referral code or code already applied');
		}
	}

	public function notifyLocation(){

		$request = Input::json()->all();

		$jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = $this->customerTokenDecode($jwt_token);
            $request['customer_id'] = (int)$decoded->customer->_id;
        }

        $array = [
        	'lat',
        	'lon',
        	'city',
        	'region'
        ];

        foreach ($array as $value) {

        	if(isset($request[$value]) && $request[$value] != "" && $request[$value] != null){
        		$request[$value] = strtolower($request[$value]);
        	}
        }

        NotifyLocation::create($request);

        return Response::json(
			array(
				'status' => 200,
				'message' => 'success',
				),
			200
		);
    }


	public function getLink(){
		try{
			$data = Input::json()->all();
			$customer = Customer::find($data['id']);
			if(!$customer){
				return array('status'=>404, 'message'=>'Customer does not exists');
			}
			$customer_link = Config::get('app.admin_url').'/customers/'.$data['id'];
			return $customer_link;
		}catch(Exception $e){
			return array('status'=>500, 'message'=>'server error');
		}
	}

	public function termsAndConditions($type){

		if($type == 'referral'){
			$tnc = "<h3 style='text-align:center'>Terms and conditions for refer and earn</h3><ul><li>Every time a new user signs up with your referral code, they'll get Rs. 250 FitCash+</li>
				<li>As soon as they do their first transaction (free trials not applicable) on Fitternity - you will automatically get Rs. 250 FitCash+ in your wallet.</li>
				<li>FitCash+ can be used in any booking and will be auto-applied on checkout</li>
				<li>The validity of this FitCash+ is 6 months</li>
				<li>You can send unlimited referral invitations, however the maximum amount of FitCash+ you can earn is Rs. 1,500</li></ul>";
		}
		
		return $tnc;
	}


	public function addWebNotification(){
		$data = Input::json()->all();
		$rules = [
			'device' => 'required|max:255',
			'browser' => 'required',
			'subscription' => 'required',
		];
		$data["subscription"] = json_decode($data["subscription"], true);
		// return $data;
		$addWebDevice["type"] = $data["device"];
		$addWebDevice["customer_id"] = (isset($data["customer_id"]) && $data["customer_id"] != "") ? (int)$data["customer_id"] : "";
		$addWebDevice["browser"] = $data["browser"];
		$addWebDevice["endpoint"] = $data["subscription"]["endpoint"];
		$addWebDevice["keys"] = $data["subscription"]["keys"];
		$device_id = Device::max('_id') + 1;

		$device = new Device();
		$device->_id = $device_id;
		$device->customer_id = $addWebDevice['customer_id'];
		$device->type = $addWebDevice['type'];
		$device->browser = $addWebDevice['browser'];
		$device->endpoint = $addWebDevice['endpoint'];
		$device->keys = $addWebDevice['keys'];
		$device->status = "1";
		$device->city_id = isset($data["city_id"]) ? array($data["city_id"]) : array();
		// return $device;
		$device->save();
		return Response::json(array('status' => 200,'message' => 'success','device'=>$device),200);
	}

	public function updateWebNotification(){
		$data = Input::json()->all();
		$resp = Response::json(array('status' => 400,'message' => 'Customer Id not found'),400);
		if(isset($data["customer_id"])){
			$deviceFound = Device::where("endpoint",$data["endpoint"])->first();
			if($deviceFound){
				$deviceFound->customer_id = (int) $data["customer_id"];
				if(isset($deviceFound->city_id)){
					if(isset($data["city_id"]) && !in_array($data["city_id"],$deviceFound['city_id'])){
						$cityIdsFound = $deviceFound['city_id'];
						array_push($cityIdsFound,$data["city_id"]);
						$deviceFound->city_id = $cityIdsFound;
					}
				}else{
					$deviceFound['city_id'] = array($data["city_id"]);	
				}
				$deviceFound->save();
				$resp = Response::json(array('status' => 200,'message' => 'success','device'=>$deviceFound),200);
			}else{
				$resp = Response::json(array('status' => 400,'message' => 'Device not found'),400);
			}
		}
		return $resp;
	}

	public function feedback(){

		$data = Input::json()->all();

		$jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = $this->customerTokenDecode($jwt_token);
            $data['customer_id'] = (int)$decoded->customer->_id;
        }

        if(isset($data['order_id']) && $data['order_id'] != ""){
        	$data['order_id'] = (int)$data['order_id'];
        }

        if(isset($data['booktrial_id']) && $data['booktrial_id'] != ""){
        	$data['booktrial_id'] = (int)$data['booktrial_id'];
        }

        if(isset($data['finder_id']) && $data['finder_id'] != ""){
        	$data['finder_id'] = (int)$data['finder_id'];
        }

        Feedback::create($data);

        return Response::json(
			array(
				'status' => 200,
				'message' => "Thankyou for the feedback",
				),
			200
		);
	}

	public function promotionalNotificationTracking(){

		$data = Input::json()->all();

		$header_array = [
	        "Device-Type"=>"",
	        "Device-Model"=>"",
	        "App-Version"=>"",
	        "Os-Version"=>"",
	        "Device-Token"=>"",
	        "Device-Id"=>""
	    ];

	    $flag = false;

	    foreach ($header_array as $header_key => $header_value) {

	        $value = Request::header($header_key);

	        if($value != "" && $value != null && $value != 'null'){
	           $header_array[$header_key] =  $value;
	           $flag = true;
	        }
	        
	    }

	    $customer_id = "";

	    $jwt_token = Request::header('Authorization');

	    if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

	        $decoded = customerTokenDecode($jwt_token);
	        $customer_id = (int)$decoded->customer->_id;
	    }

	    $data['customer_id'] = $customer_id;
	    $data['device_id'] = $header_array['Device-Id'];
	    $data['os_version'] = $header_array['Os-Version'];
	    $data['app_version'] = $header_array['App-Version'];
	    $data['device_model'] = $header_array['Device-Model'];
	    $data['device_type'] = $header_array['Device-Type'];
	    $data['device_token'] = $header_array['Device-Token'];

	    $rules = [
			'device_token' => 'required',
			'label' => 'required',
			'action'=>'required|in:received,clicked'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()) {

			return Response::json(array('status' => 401,'message' =>$this->errorMessage($validator->errors())),401);
		}

		if($data['action'] == 'received'){
			$data['received'] = time();
		}

		if($data['action'] == 'clicked'){
			$data['clicked'] = time();
		}

		unset($data['action']);

		$promotionalNotificationTracking = false;
		
		if(isset($data['label']) && isset($data['device_token'])){

			$promotionalNotificationTracking = PromotionalNotificationTracking::where('device_token',$data['device_token'])->where('label',$data['label'])->first();
		}

		if($promotionalNotificationTracking){

			$promotionalNotificationTracking->update($data);

		}else{

			$promotionalNotificationTracking = new PromotionalNotificationTracking($data);
			$promotionalNotificationTracking->save();
		}

		return Response::json(array('status' => 200,'message' => 'Captured Successfully','promotional_notification_id'=>$promotionalNotificationTracking->_id),200);

	}

	public function customerCapture(){

		$data = Input::json()->all();

		if(empty($data)){

			return Response::json(
			array(
					'status' => 400,
					'message' => "Empty Data",
					),
				400
			);

		}

		$transaction_data = [];

        if(isset($data['customer_id']) && $data['customer_id'] != ""){
        	$data['customer_id'] = (int)$data['customer_id'];
        }

        if(isset($data['order_id']) && $data['order_id'] != ""){
        	$data['order_id'] = (int)$data['order_id'];
        }

        if(isset($data['booktrial_id']) && $data['booktrial_id'] != ""){

        	$data['booktrial_id'] = (int)$data['booktrial_id'];

        	Booktrial::$withoutAppends=true;

        	$transaction = Booktrial::find((int)$data['booktrial_id']);
        }

        if(isset($data['finder_id']) && $data['finder_id'] != ""){
        	$data['finder_id'] = (int)$data['finder_id'];
        }

        if(isset($transaction)){

        	$array_only = [
        		'finder_id',
        		'city_id',
        		'customer_id',
        		'customer_email',
        		'customer_phone',
        		'customer_name'
        	];

    		$transaction_data = array_only($transaction->toArray(),$array_only);
    	}

    	if(!empty($transaction_data)){
    		$data = array_merge($data,$transaction_data);
    	}

        CustomerCapture::create($data);

        return Response::json(
			array(
				'status' => 200,
				'message' => "Thankyou for the details",
				),
			200
		);
        
    }


    public function getFormFields(){

    	$data = [];

    	if(isset($_GET['booktrial_id']) && $_GET['booktrial_id'] != ""){

    		Booktrial::$withoutAppends=true;

        	$transaction = Booktrial::find((int)$_GET['booktrial_id']);

        	if($transaction){

        		$data = array_only($transaction->toArray(), ['finder_id','city_id','customer_id','customer_email','customer_phone','customer_name','finder_name']);

        		$data['booktrial_id'] = (int)$_GET['booktrial_id'];
        	}
        }

        $form_fields = formFields();

        return Response::json(
			array(
				'status' => 200,
				'form_fields' => $form_fields,
				'data'=>$data,
				'header'=> "Fill this form",
				'indemnity' => array("header"=>"I Agree to Terms & Conditions of ".$data['finder_name'],"description" => "I expressly agree to indemnify and hold the Gym/Studio harmless against any and all claims, demands, damages, rights of action, or causes of action, of any person or entity, that may arise from injuries or damages sustained by me or my guest. I am aware that this is a waiver and a release of liability and I voluntarily agree to its terms.
				")
				),
			200
		);

	}
	
	public function invitePreRegister(){
		$data = Input::all();

		$rules = [
			'capture_id' => 'required',
			'invitees' => 'required|array'
		];

		$invitees = $data['invitees'];
		
		$capture = Capture::find($data['capture_id']);

		$capture->invitees = $invitees;

		$customer_id = $capture->customer_id;

		$customer = Customer::find($customer_id);

		$customer_phone = $customer->contact_no;
		

		foreach($invitees as $invitee){
			
			if( substr($customer_phone, -10) == substr($invitee, -10)){
				$resp = array('status'=>400, 'message' => 'Cannot invite yourself');
				return Response::json($resp, 400);
			}
		
		}

		$capture->save();

		Log::info($customer);
		$referral_code = $customer->referral_code;
		
		$shorten_url = new ShortenUrl();
		
		$pre_register_url = $shorten_url->getShortenUrl(Config::get('app.website')."/pre-register?referral_code=$capture->_id")['url'];

		foreach($invitees as $invitee){

			$data = array(
				'customer_phone' => $invitee,
				// 'customer_phone' => $invitee['phone'],
				// 'invitee_name' => $invitee['name'],
				'pre_register_url' => $pre_register_url,
				'inviter_name' => ucwords($customer->name)
			);

			Log::info($data);

			$this->customersms->invitePreRegister($data);
		
		}

		return Response::json(array('status' => 200,'message'=> "Invites sent"), 200);

	}

	public function sendVendorNumberToCustomer(){

		$data = Input::json()->all();

		$rules = [
			'finder_id' => 'required',
			'customer_number' => 'required'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()) {
			return Response::json(['status' => 400,'message' =>$this->errorMessage($validator->errors())],400);
		}

		Finder::$withoutAppends=true;

		$finder = Finder::with(array('knowlarityno'=>function($query){$query->select('*')->where('status',true)->orderBy('extension', 'asc');}))->find((int)$data['finder_id']);

		if($finder){

			$data['finder_number'] = "";

			if(isset($finder['contact']['phone']) && $finder['contact']['phone'] != ""){

				$data['finder_number'] = $finder['contact']['phone'];
			}

			$data['finder_name'] = ucwords($finder->title);

			// $knowlarity_no = KnowlarityNo::where('status',true)->where('vendor_id',(int)$data['finder_id'])->first();
			$knowlarity_nos = $this->utilities->getContactOptions($finder);

			if(count($knowlarity_nos)){

				$intent = isset($data['intent']) && $data['intent'] != ''? intval($data['intent']) : 0;

				$knowlarity_no = $knowlarity_nos[$intent];

				$extension = (isset($knowlarity_no['extension']) && $knowlarity_no['extension'] != "") ? " (extension : ".str_pad($knowlarity_no['extension'], 2, '0', STR_PAD_LEFT).")" : "";

				$data['finder_number'] = $knowlarity_no['phone_number'].$extension;

			}

			$data['customer_phone'] = $data['customer_number'];

			$captureData = [
                "customer_phone" => $data['customer_phone'],
                "finder_id" => $data['finder_id'],
                "city_id" => $finder->city_id,
                "capture_type" => "sendVendorNumber"
            ];

            $this->utilities->addCapture($captureData);
			
			$this->customersms->sendVendorNumber($data);

			return Response::json(['status' => 200,'message'=> "SMS Sent"]);
		}

		return Response::json(['status' => 400,'message'=> "Vendor Not Found"],400);
	}

	public function getCodOrders(){
		
		$jwt_token = Request::header('Authorization');
		
		$decoded = $this->customerTokenDecode($jwt_token);

		$customer_id = $decoded->customer->_id;

		$orders = Order::where('customer_id', $customer_id)->where('payment_mode', 'cod')->where('cod_otp', 'exists', true)->where('status', '0')->get();

		return Response::json(['status' => 200,'data'=> $orders]);
	}

	public function verifyVendorKioskPin($pin){

		$decodeKioskVendorToken = decodeKioskVendorToken();

        $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

        $finder_id = (int)$vendor['_id'];

        $kiosk_user = KioskUser::where('hidden',false)->where('type','kiosk')->where('finder_id',$finder_id)->first();

        if($kiosk_user){

			/*if($kiosk_user['pin'] != $pin){

				return Response::json(array('status' => 400,'message' => 'Incorrect Pin'));
			}*/

			if((int)$pin != 1234){

				return Response::json(array('status' => 400,'message' => 'Incorrect Pin'));
			}

			return Response::json(array('status' => 200,'message' => 'Pin Verified'));
		}

		return Response::json(array('status' => 400,'message' => 'Vendor Not Found'));
	}

	public function shareGroupId(){

		$data = Input::json()->all();

		$rules = [
			'order_id' => 'required',
			'invitees' => 'required|array'
		];

		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {

			return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),$this->error_status);

		}

		$order_id = intval($data['order_id']);

		$invitees = $data['invitees'];
		
		$order = Order::find($order_id, ['customer_name', 'group_id']);

		$order->group_invites = $invitees;

		$order->update();

		foreach($invitees as $invitee){

			$data = [
				'invitor_name'=>$order['customer_name'],
				'name'=> $invitee['name'],
				'phone'=>$invitee['input'],
				'group_id'=>$order['group_id']
			];

			$this->customersms->sendGroupInvite($data);

		}

		return Response::json(['status'=>200, 'message'=>'Group invitation sent successfully']);
	}

	public function displayEmiV1(){
		
		$bankNames=array();
		$bankList= array();
		$emiStruct = Config::get('app.emi_struct');
		$data = Input::json()->all();
		$response = array(
			"bankList"=>array(),
			"bankData"=>array(),
			"higerMinVal" => array()
			);

		$bankData = array();
		
		foreach ($emiStruct as $emi) {
			if(isset($data['bankName']) && !isset($data['amount'])){
				if($emi['bankName'] == $data['bankName']){
					if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
					// Log::info("inside1");
					$emiData = array();
					$emiData['total_amount'] =  "";
					$emiData['emi'] ="";
					$emiData['months'] = (string)$emi['bankTitle']." months";
					$emiData['bankName'] = $emi['bankName'];
					$emiData['bankCode'] = $emi['bankCode'];
					$emiData['rate'] = (string)$emi['rate'];
					$emiData['minval'] = (string)$emi['minval'];
					if(isset($bankData[$emi['bankName']])){

						array_push($bankData[$emi['bankName']], $emiData);
					}else{
						$bankData[$emi['bankName']] = [$emiData];
					}
				}
			
			}elseif(isset($data['bankName'])&&isset($data['amount'])){
				if($emi['bankName'] == $data['bankName'] && $data['amount']>=$emi['minval']){
					// Log::info("inside2");
					$emiData = array();
					if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
					$interest = $emi['rate']/1200.00;
					$t = pow(1+$interest, $emi['bankTitle']);
					$x = $data['amount'] * $interest * $t;
					$y = $t - 1;
					$emiData['emi'] = round($x / $y,0);
					$emiData['total_amount'] =  "Total Payable : ₹ ".(string)($emiData['emi'] * $emi['bankTitle']);
					$emiData['emi'] = " - EMI @ ₹ ".(string)$emiData['emi'];
					$emiData['months'] = (string)$emi['bankTitle']." months";
					$emiData['bankName'] = $emi['bankName'];
					$emiData['bankCode'] = $emi['bankCode'];
					$emiData['rate'] = (string)$emi['rate'];
					$emiData['minval'] = (string)$emi['minval'];
					// array_push($bankData, $emiData);
					if(isset($bankData[$emi['bankName']])){

						array_push($bankData[$emi['bankName']], $emiData);
					}else{
						$bankData[$emi['bankName']] = [$emiData];
					}
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
					// Log::info("inside3");
					$emiData = array();
					$interest = $emi['rate']/1200.00;
					$t = pow(1+$interest, $emi['bankTitle']);
					$x = $data['amount'] * $interest * $t;
					$y = $t - 1;
					$emiData['emi'] = round($x / $y,0);
					$emiData['total_amount'] =  "Total Payable : ₹ ".(string)($emiData['emi'] * $emi['bankTitle']);
					$emiData['emi'] = " - EMI @ ₹ ".(string)$emiData['emi'];
					$emiData['months'] = (string)$emi['bankTitle']." months";
					$emiData['bankName'] = $emi['bankName'];
					$emiData['bankCode'] = $emi['bankCode'];
					$emiData['rate'] = (string)$emi['rate'];
					$emiData['minval'] = (string)$emi['minval'];
					// array_push($bankData, $emiData);
					if(isset($bankData[$emi['bankName']])){
						
						array_push($bankData[$emi['bankName']], $emiData);
					}else{
						$bankData[$emi['bankName']] = [$emiData];
					}
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
				// Log::info("inside4");
				$emiData = array();
						$emiData['total_amount'] =  "";
						$emiData['emi'] ="";
						$emiData['months'] = (string)$emi['bankTitle']." months";
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)(string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
						if(isset($bankData[$emi['bankName']])){
							
							array_push($bankData[$emi['bankName']], $emiData);
						}else{
							$bankData[$emi['bankName']] = [$emiData];
						}
			}
		}

		foreach($bankData as $key => $value){
			$response['bankData'][] = [
				'bankName' => $key,
				'emiData' => $value
			];
		}
		$response['bankList'] = $bankList;
		return $response;
	}

	public function uploadReceipt(){
        Log::info("uploadReceiptuploadReceiptuploadReceiptuploadReceiptuploadReceipt");

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = $decoded->customer->_id;

		$rules = [
			'customer_id' => 'required',
			'booktrial_id'=>'required|integer|numeric',
			'receipt'=>'image'
		];

		$data = Input::all();

		$data['customer_id'] = $customer_id;

		unset($data['receipt']);

		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {

			return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())));

		}else{

			$image_success = [];

			if (Input::hasFile('receipt')) {

				$image_detail = [
					array('type'=>'cover','path'=>'customer/'.$customer_id.'/receipt/','width'=>720),
				];

				$image = array('input' => Input::file('receipt'),'detail' => $image_detail,'id' => $data['booktrial_id']);

				$image_response = upload_magic($image);

				foreach ($image_response['response'] as $key => $value){

					if(isset($value['success']) && $value['success']){

						$image_success['width'] = $value['kraked_width'];
						$image_success['height'] = $value['kraked_height'];
						$image_success['s3_url'] = $value['kraked_url'];
						$image_success['s3_folder_path'] = $value['folder_path'];
						$image_success['s3_file_path'] = $value['folder_path'].$image_response['image_name'];
						$image_success['name'] = $image_response['image_name'];
					}

				}

			}

			if(!empty($image_success)){

				$booktrial = Booktrial::find((int) $data['booktrial_id']);
				$booktrial->update(['receipt'=>$image_success]);

				return Response::json(array('status' => 200,'message' => "Receipt Uploaded Successfully"));

			}else{

				return Response::json(array('status' => 400,'message' => "Error, Receipt Not Uploaded"));

			}
		}
		
	}

	public function notificationDataByTrialId($booktrial_id, $label){

		Log::info($_SERVER['REQUEST_URI']);

		if(isset($_GET['notif_id']) && $_GET['notif_id'] != ''){
			
			$notificationTracking = NotificationTracking::find(intval($_GET['notif_id']));
			
			if($notificationTracking){
				$notificationTracking->update(['clicked'=>time()]);
			}
			
		}

		$transaction = Booktrial::with(['category'=>function($query){$query->select('detail_rating','detail_ratings_images');}])->find(intval($booktrial_id));

		$dates = array('start_date', 'start_date_starttime', 'schedule_date', 'schedule_date_time', 'followup_date', 'followup_date_time','missedcall_date','customofferorder_expiry_date','auto_followup_date');
		$unset_keys = [];
		foreach ($dates as $key => $value){
			if(isset($transaction[$value]) && $transaction[$value]==''){
				array_push($unset_keys, $value);
			}
		}

		if(count($unset_keys) > 0){
			$transaction->unset($unset_keys);
		}

		if($transaction){

			$data = $transaction->toArray();
		}

		return $this->getBlockScreenData($label, $data);
	}

	public function getBlockScreenData($label, $data){

		$response = [];

		switch ($label) {
			case 'activate_session':
			case 'n-10m':
				$response['header'] = ucwords($data['service_name'])." at ".ucwords($data['finder_name']);
				$response['sub_header'] = "ACTIVATE SESSION";
				$response['footer'] = "FitCode will be provided by ".$data['finder_name']."  to activate your session";
				$response['schedule_date_time'] = strtotime($data['schedule_date_time']);
				$response['subscription_code'] = implode('  ',str_split($data['code']));
				$response['button_text'] = [
					'activate'=>[
						'text'=>'ACTIVATE SESSION',
						'url'=>Config::get('app.url')."/sessionstatuscapture/activate/".$data['_id']
					],
					'didnt_get'=>[
						'text'=>'Didn’t get FitCode',
						'url'=>Config::get('app.url')."/sessionstatuscapture/lost/".$data['_id']."?source=activate_session", 
						"header"=> "Didn't get Fitcode?",
						"subtitle"=> "Let us know the reason to assist you better",
						"options" => [
							[
								"text" => "Don't have/Don't remember fitcode",
								"url" => Config::get('app.url')."/sessionstatuscapture/lost/".$data['_id']."?reason=2"
							],
							[
								"text" => "Gym/studio did not provide fitcode",
								"url" => Config::get('app.url')."/sessionstatuscapture/lost/".$data['_id']."?reason=3"
							]
						]
					],
					'cant_make'=>['text'=>'CAN’T MAKE IT','url'=>Config::get('app.url')."/sessionstatuscapture/didnotattend/".$data['_id']],

					'qrcode'=>[
						'text'=> "SCAN YOUR QR CODE"
					]
				];
				$response['block'] = true;
				if(isTabActive($data['finder_id'])){
					$response['block'] = false;
					$response['activation_success'] = [
						'header'=>	ucwords($data['service_name'])." at ".ucwords($data['finder_name'])."\n\n 00hrs : 00min: 00sec",
						'image'=> 'https://b.fitn.in/paypersession/happy_face_icon-2.png',
						'sub_header_2'=>"ACTIVATE SESSION\n\n\nSubscription Code  ".$response['subscription_code']."\n\n\n\nPunch this code at on the kiosk/tab available at ".ucwords($data['finder_name'])." to activate your session"
					];
					Booktrial::where('_id', $data['_id'])->update(['kiosk_block_shown'=>true]);
				}
				break;
			case 'let_us_know':
			case 'n+2':

				$app_version = Request::header('App-Version');
				$device_type = Request::header('Device-Type');
				if(($device_type == 'ios' && $app_version > '4.9') || ($device_type == 'android' && $app_version > '4.9')){

					if(isset($_GET['getreasons']) && $_GET['getreasons'] == '1'){
						$fitcash = "";
						if(isset($data['type']) && $data['type']=='booktrials'){
							$fitcash = $this->utilities->getFitcash($data);
						}else{
							$fitcash = $this->utilities->getWorkoutSessionFitcash($data)."%";
						}
						$response['header'] = "LET US KNOW";
						$response['sub_header'] = "Did you attend your ".$data['service_name']." at ".$data['finder_name']." on ".date('jS M \a\t g:i a', strtotime($data['schedule_date_time']))."? \n\nEnter your FitCode given by ".$data['finder_name']." and earn ".$fitcash." Cashback!";
						
						$response['button_text'] = [
							'activate'=>[
								'text'=>'GET MY FITCASH',
								'url'=>Config::get('app.url')."/sessionstatuscapture/activate/".$data['_id']."?source=let_us_know"
							],
							'didnt_get'=>[
								'text'=>'Didn’t get FitCode',
								"header"=> "Didn't get Fitcode?",
								"subtitle"=> "Let us know the reason to assist you better",
								"options" => [
									[
										"text" => "Don't have/Don't remember fitcode",
										"url" => Config::get('app.url')."/sessionstatuscapture/lost/".$data['_id']."?reason=2"
									],
									[
										"text" => "Gym/studio did not provide fitcode",
										"url" => Config::get('app.url')."/sessionstatuscapture/lost/".$data['_id']."?reason=3"
									]
								]
							],
						];
						$response['block'] = true;

					}else{
						$response = $this->getFirstScreen($data);
						$response['button_text']['attended']['url'] = Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/let_us_know?getreasons=1';


						if(isTabActive($data['finder_id'])){
							$response['button_text']['attended']['type'] = 'SUCCESS';
							$response['button_text']['attended']['url'] = Config::get('app.url')."/sessionstatuscapture/lost/".$data['_id'];
						}

					}

				}else{	
					$response = $this->getFirstScreen($data);
				}
				
				$response['block'] = true;
				break;
			case 'n-3':
            case 'session_reminder':
                
            
                $one_hour_before = date('g:i a', strtotime('-1 hour',strtotime($data['schedule_date_time'])));
				$response['header'] = "SESSION REMINDER";
				
				$response['image'] = "https://b.fitn.in/paypersession/timer.png";
				
				$response['sub_header_2'] = "Your ".$data['service_name']." at ".$data['finder_name']." is scheduled for today at ".date('g:i a', strtotime($data['schedule_date_time']))."\n\nAre you ready to kill your workout?\n\nCancellation window for this session is available upto 1 hour prior to the session time (Cancel before ".$one_hour_before.")\nCancellation post the window will be chargeable " ;
				$response['button_text'] = [
					'attended'=>['text'=>'YES I’LL BE THERE','url'=>Config::get('app.url')."/sessionstatuscapture/confirm/".$data['_id'], 'type'=>"SUCCESS"],
					'did_not_attend'=>['text'=>'NO, I’M NOT GOING','url'=>Config::get('app.url')."/sessionstatuscapture/cantmake/".$data['_id']]
				];
				if(isset($data['studio_extended_validity_order_id'])){
					unset($response['button_text']);
					Booktrial::where('_id', $data['_id'])->update(['studio_block_shown'=>true]);
					Order::$withoutAppends = true;
					$order = Order::where('_id', $data['studio_extended_validity_order_id'])->first(['_id', 'studio_extended_validity', 'studio_sessions', 'studio_membership_duration']);
					if(!empty($order['studio_sessions'])){
						$avail = $order['studio_sessions']['total_cancel_allowed'] - $order['studio_sessions']['cancelled'];
						$avail = ($avail<0)?0:$avail;
						$response['sub_header_2'] = "Your ".$data['service_name']." at ".$data['finder_name']." is scheduled for today at ".date('g:i a', strtotime($data['schedule_date_time']))."\n\nAre you ready to kill your workout?\n\nCan't make it? Cancel your session 60 minutes prior from your user profile to avail the extension.";
					}
					$response['button_text'] = [
						'attended'=>['text'=>'YES I’LL BE THERE','url'=>Config::get('app.url')."/sessionstatuscapture/confirm/".$data['_id'], 'type'=>"SUCCESS"],
						'did_not_attend'=>['text'=>'Cancel','url'=>Config::get('app.url')."/sessionstatuscapture/cancel/".$data['_id']]
					];
				}
				Log::info('at the end');
				$response['block'] = false;
			break;
			case 'review':

				$app_version = Request::header('App-Version');
				if($app_version < '5'){
                    $response = $this->getFirstScreen($data);
                    $response['block'] = false;
                }else{
                    $response = array_merge($response, $this->utilities->reviewScreenData($data));
                    $response['service_id'] = $data['service_id'];
                    $response['booktrialid'] = $data['_id'];
                    $response['finder_id'] = $data['finder_id'];
                    // $response['skip'] = Config::get('app.url')."/customer/skipreview/".$data['_id'];
                    $response['optional'] = true;
                    $response['show_rtc'] = true;
                }   

		}
		$time_diff = strtotime($data['schedule_date_time']) - time();
		
		if(isset($data['schedule_date_time'])){

			if($time_diff < 0){
				$response['schedule_date_time_text'] = "Happened on ".date('jS M, h:i a', strtotime($data['schedule_date_time']));
			}else{
				$response['schedule_date_time_text'] = "Scheduled on ".date('jS M, h:i a', strtotime($data['schedule_date_time']));
			}
		
		}

		$description = "";
		
		if(isset($response['sub_header_1'])){
			$description = "<font color='#f7a81e'>".$response['sub_header_1']."</font>";
		}

		if(isset($response['sub_header_2'])){
			$description = $description.$response['sub_header_2'];
		}
		$response['description'] = $description;
							
		return $response;

	}

	public function streakScreenData(){
		
		$jwt_token = Request::header('Authorization');

		Log::info($jwt_token);

		$decoded = $this->customerTokenDecode($jwt_token);

		$customer_id = $decoded->customer->_id;
		
		$customer_level_data = $this->utilities->getWorkoutSessionLevel($customer_id); 
		
		$streak_items = [];

		$streak_constants = Config::get('app.streak_data');
		
		foreach($streak_constants as $key => $value){

			array_push($streak_items, ['title'=>$value['cashback'].'%', 'value'=>$value['number'].' Sessions', 'level'=>$key+1]);

		}

		$streak = [
			'header'=>'Attend More Earn More',
			'items'=>$streak_items
		];
		
		$response = [
			'streak'=>[
				'header'=>'You’re on a workout streak!',
				'data'=>$this->utilities->getStreakImages(count($streak_constants))
			],
			'body_2'=>[
				'header'=>'How It Works',
				'data'=>['Book a workout session', 'Attend the session', 'Collect your Fitcode from the center', 'Punch the Fitcode in your profile to track your streak', 'Attend more to earn more']
			],
			'body_3'=>[
				'header'=>'Attend more & Earn More',
				'items'=>$streak_items
			]

		];

		return $response;
	}

	public function loginOptions(){

		$response = [
			'facebook'=>true,
			'google'=>true,
			'email'=>true,
		];

        if($this->device_type == 'android'){
            $response['google'] = false;
        }

		return Response::json($response,200);
	}

	public function setDefaultAccount(){
		
		$data = Input::json()->all();
		
		$jwt_token = $data['token'];
		
		$decodedToken = $this->customerTokenDecode($jwt_token);

		setDefaultAccount(['source'=>'website', 'customer_phone'=>$decodedToken->customer->contact_no], $decodedToken->customer->_id);

		return ['status'=>200, 'token'=>$jwt_token];
	}
	
	public function autoRegister(){
		$data = Input::json()->all();

		Log::info("autoRegister metropolis");
		Log::info($data);
		if( !isset($data['key']) || $data['key'] != '1jhvv123vhjc323@(*Bb@##*yhjj2Jhasda78&*gu'){
			return array('status'=>404, 'message'=>'Not Authorized');
		}
		$customer_id = autoRegisterCustomer($data);
		return array('status'=>200, 'message'=>'Registered', 'customer_id'=>$customer_id);

	}
	
	public function inviteForSignup(){
		try {
			
			$req = Input::json()->all();
			Log::info('inviteForSignup',$req);
			$customer_id = "";
			$jwt_token = Request::header('Authorization');
			$device_type = Request::header('Device-Type');
			$app_version = Request::header('App-Version');
			
			if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
				
				$decoded = customerTokenDecode($jwt_token);
				Log::info(" decoded ".print_r($decoded ,true));
				Log::info(" sas".print_r($decoded->customer,true));
				$customer_id = (int)$decoded->customer->_id;
				Log::info(" customer_id ".print_r($customer_id ,true));
				$customer= $decoded->customer;
				Log::info(" customer ".print_r($customer,true));
			}
			if(!empty($customer))
			{
				Log::info(" customer ".print_r($customer,true));
				// Request Validations...........
				$rules = [
						'invitees' => 'required|array',
// 						'city_id' => 'required',
				];
				
				$validator = Validator::make($req, $rules);
				
				if ($validator->fails()) {
					return Response::json(
							array(
									'status' => 400,
									'message' => $this->errorMessage($validator->errors()
											)),400
							);
				}
				
				
				
				// Invitee info validations...........
				$inviteesData = [];
				
				foreach ($req['invitees'] as $value){
					
					$inviteeData = ['name'=>$value['name']];
					
					$rules = [
							'name' => 'required|string',
							'input' => 'required|string',
					];
					$messages = [
							'name' => 'invitee name is required',
							'input' => 'invitee email or phone is required'
					];
					$validator = Validator::make($value, $rules, $messages);
					
					if ($validator->fails()) {	return Response::json(array('status' => 0,'message' => $this->errorMessage($validator->errors())),200);}
					
					if(filter_var($value['input'], FILTER_VALIDATE_EMAIL) != '') {
						// valid address
						$inviteeData = array_add($inviteeData, 'email', $value['input']);
					}
					else if(filter_var($value['input'], FILTER_VALIDATE_REGEXP, array(
							"options" => array("regexp"=>"/^[2-9]{1}[0-9]{9}$/")
					)) != ''){
						// valid phone
						$inviteeData = array_add($inviteeData, 'phone', $value['input']);
						
					}
					array_push($inviteesData, $inviteeData);
					
					foreach ($inviteesData as $value){
						
						$rules = [
								'name' => 'required|string',
								'email' => 'required_without:phone|email',
								'phone' => 'required_without:email',
						];
						$messages = [
								'email.required_without' => 'invitee email or phone is required',
								'phone.required_without' => 'invitee email or phone is required'
						];
						$validator = Validator::make($value, $rules, $messages);
						
						if ($validator->fails()) {
							return Response::json(
									array(
											'status' => 400,
											'message' => $this->errorMessage($validator->errors()
													)),400
									);
						}
					}
					
					
				}
				

				// Validate customer is not inviting himself/herself......
				$emails = array_fetch($inviteesData, 'email');
				$phones = array_fetch($inviteesData, 'phone');
				
				
				if(array_where($emails, function ($key, $value) use($customer)   {
					if($value == $customer->email){
						return true;
					}
				})) {
					return Response::json(
							array(
									'status' => 400,
									'message' => 'You cannot invite yourself'
							),400
							);
				}
				
				if(array_where($phones, function ($key, $value) use($customer){
					if($value == $customer->contact_no){
						return true;
					}
				})) {
					return Response::json(
							array(
									'status' => 400,
									'message' => 'You cannot invite yourself'
							),400
							);
				}
				
				
				// Save Invite info..........
				foreach ($inviteesData as $invitee){
					$invite = new Invite();
					$invite->_id = Invite::max('_id') + 1;
					$invite->status = 'pending';
					$invite->host_id = $customer->_id;
					$invite->host_email = $customer->email;
					$invite->host_name = $customer->name;
					$invite->campaign= (!empty($req['campaign'])?$req['campaign']:'starter_pack');
					$invite->host_phone = $customer->contact_no;
					$invite->source ='web';
					$invite->city_id =(!empty($req['city_id'])?$req['city_id']:"");
					isset($invitee['name']) ? $invite->invitee_name = trim($invitee['name']): null;
					isset($invitee['email']) ? $invite->invitee_email = trim($invitee['email']): null;
					isset($invitee['phone']) ? $invite->invitee_phone = trim($invitee['phone']): null;
					$invite->save();
					
					// Generate bitly for landing page with invite_id and booktrial_id
					if(!empty($customer->referral_code))
						$url = Config::get('app.website').'/starter-pack?campaign='.$invite->campaign.'&host_id='.$invite['host_id'].'&code='.$customer->referral_code.'&invite_id='.$invite['_id'];
						$shorten_url = new ShortenUrl();
						$url1 = $shorten_url->getShortenUrl($url);
						Log::info("  url ".print_r($url,true));
						if(!isset($url['status']) ||  $url['status'] != 200){
							Log::info(" COULDN'T GENERATE SHORTEN URL");
								return Response::json(
							 array(
							 'status' => 0,
							 'message' => 'Unable to Generate Shortren URL'
							 ),200
							 );
							
						}
						else $url = $url1['url'];
						/* if(!isset($url2['status']) ||  $url2['status'] != 200){
						 return Response::json(
						 array(
						 'status' => 0,
						 'message' => 'Unable to Generate Shortren URL'
						 ),200);
						 } */
						if(!empty($invite->city_id))
						$cityName=City::where("_id",(int)$invite->city_id)->first(['name']);
						// Send email / SMS to invitees...
						$templateData = array(
								'invitee_name'=>$invite['invitee_name'],
								'invitee_email'=>$invite['invitee_email'],
								'invitee_phone'=>$invite['invitee_phone'],
								'gender'=>(!empty($customer->gender)?$customer->gender:""),
								'referral_code'=>(!empty($customer->referral_code)?$customer->referral_code:""),
								'host_name' => $invite['host_name'],
								'starter_pack' => true,
								'amount'=>"500",
								'city'=>(!empty($cityName)&&!empty($cityName->name))?$cityName->name:"",
								// 							'type'=> $BooktrialData['type'],
								// 							'finder_name'=> $BooktrialData['finder_name'],
								// 							'finder_location'=> $BooktrialData['finder_location'],
								// 							'finder_address'=> $BooktrialData['finder_address'],
								// 							'schedule_date'=> $BooktrialData['schedule_date'],
								// 							'schedule_date_time'=> $BooktrialData['schedule_date_time'],
								// 							'service_name'=> $BooktrialData['service_name'],
								// 							'schedule_slot_start_time'=> $BooktrialData['schedule_slot_start_time'],
								'url' => $url
								// 							'url2' => $url2
						);
						
						//            return $this->customermailer->inviteEmail($BooktrialData['type'], $templateData);
						
						// 					isset($templateData['invitee_email']) ? $this->customermailer->inviteEmail($BooktrialData['type'], $templateData) : null;
						Log::info("  templateData :: ".print_r($templateData,true));
						isset($templateData['invitee_phone']) ? $this->customersms->inviteSMS("", $templateData) : null;
				}
				
				return Response::json(array('status' => 200,'message' => "Successfully invited friends for signup ."),200);
			}
			else
				return Response::json(array('status' => 400,'message' => "Token Not Present or invalid."),400);
				
				
				
				
				
				
				
		} catch (Exception $e) {
			$e->getTrace();
			return Response::json(array('status' => 400,'message' => 'Something went wrong, Please try again later. '),400);
			// return Response::json(array('status' => 400,'message' => $e->getMessage()." on line :: ".$e->getLine()." in file :: ".$e->getFile()),400);
		}
		
	}

	public function getReferralScreenData(){
		
		$jwt = Request::header('Authorization');
		
		$decoded = $this->customerTokenDecode($jwt);
		
		$id = $decoded->customer->_id;
		
		Customer::$withoutAppends = true;

		$customer = Customer::where('_id', $id)->first();
		
		if($customer){

			if(!isset($customer->pps_referral_code)){

				if(!isset($customer->referral_code)){
					$customer->referral_code = generateReferralCode($customer->name);

				}

				$customer->pps_referral_code = $customer->referral_code;
				$customer->update();
			
			}

			$pps_referral_code = $customer['pps_referral_code'];
		
		}else{
			
			return Response::json(array('status' => 400,'message' => "Customer does not exist"),400);
		
		}

		$data  = ['header'=>'Refer and Earn', 'referral_code'=>$pps_referral_code, 'customer_id'=>$customer->_id]; 
		
		$data['body']['section_1'] = 'Refer a friend and both you and your friend get a free workout! Available on booking Pay-per-session, Workout Anytime Anywhere!';
		
		$data['body']['section_2'] = ['header'=>'Your unique code : '.$pps_referral_code, 'button_text'=>'INVITE & EARN', 'referral_code'=>$pps_referral_code];

		$free_sessions_remainig = $this->utilities->getRemainigPPSSessions($customer);

		$pps_referral_credits = isset($customer->pps_referral_credits) ? $customer->pps_referral_credits : 0;
		
		$pps_referral_credits_used = isset($customer->pps_referral_credits_used) ? $customer->pps_referral_credits_used : 0;

		if($pps_referral_credits){
			$data['body']['section_3'] = ['header'=>'Your Stats', 'enabled'=>true, 'data'=>[$pps_referral_credits.' friends have used your code.', $pps_referral_credits_used.' session used out of '.($pps_referral_credits <= 5 ? $pps_referral_credits : 5 ).' earned.']];
		}else{
			$data['body']['section_3'] = ['header'=>'Your Stats', 'enabled'=>true, 'data'=>['0 friends have used your code', 'Invite friends to get 5 free workouts']];
		}
		
		// $data['body']['section_3'] = ['header'=>'Your Stats', 'enabled'=>$pps_referral_credits > 0, 'data'=>[$pps_referral_credits.' friends have used your code', $free_sessions_remainig.' out of 5 Free Workout sessions remaining']];
		
		$data['body']['section_4'] = [
			'header'=>'How it works', 
			'data'=>[
				['text'=>'You share the code with your buddy. He uses it while booking his session…', 'info_text'=>'KNOW MORE', 'type'=>'type1'],
				['text'=>'5 friends using your code gets you Rs. 299 off on each session', 'type'=>'type2', 'line1'=>'₹299', 'line2'=>'OFF']
			]
		];

		$data['body']['info'] = [
			'header'=>'How it works', 
			'data'=>[
				'You share the code with your buddy I He uses it while booking his session & gets Rs. 299 off on his first workout session',
				'As soon as he books you get can get Rs. 299 off on booking your session with the same code',
				'This code is only applicable on the Pay-per-session bookings on the Fitternity app',
				'The validity of the code is 6 months'
			]
		];

		$data['info'] = [
			'header'=>'How it works', 
			'data'=>[
				'You share the code with your buddy I He uses it while booking his session & gets Rs. 299 off on his first workout session',
				'As soon as he books you get can get Rs. 299 off on booking your session with the same code',
				'This code is only applicable on the Pay-per-session bookings on the Fitternity app',
				'The validity of the code is 6 months',
				// 'You can send unlimited referral invitations, however the discount of Rs. 299 is applicable for first 5 friends I Next 10 friends using your code get you Rs. 299 I 15 friends using your code get you Rs. 299'
			]
		];

		$data['share_message'] = "Join me to workout with Fitternity's Pay-per-session. Get your first workout free (upto Rs. 299) using my code $pps_referral_code.17+ fitness forms, 7 cities & 75,000 classes every week. Download the app now - ".Config::get('app.download_app_link');

		return $data;

	}

	public function getCustomerCardDetails(){
		$jwt = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt);
		$id = $decoded->customer->_id;
		$key = Config::get("app.payu.test.key");
        $salt = Config::get("app.payu.test.salt");
		$wsUrl = Config::get("app.payu.test.url");
		$data =	Input::all();
		$env = (isset($data['env']) && $data['env'] == 1) ? "stage" : "production";
        if($env == "production"){
            $key = Config::get("app.payu.prod.key");
			$salt = Config::get("app.payu.prod.salt");;
			$wsUrl = Config::get("app.payu.prod.url");
		}
		$var1 = $key.":".$id;
		$payhash_str = $key."|get_user_cards|".$var1."|".$salt;
        Log::info("Get card details".$payhash_str);
		$hash = hash('sha512', $payhash_str);        
		

		$r = array('key' => $key , 'hash' =>$hash , 'var1' => $var1, 'command' => "get_user_cards");   
		$qs= http_build_query($r);
		$userData = curl_call($qs, $wsUrl);
		return json_decode($userData, true);
	}

	public function getFirstScreen($data){
		
		$response['header'] = "LET US KNOW";
		$response['sub_header_2'] = "Did you attend your ".$data['service_name']." at ".$data['finder_name']." on ".date('jS M \a\t g:i a', strtotime($data['schedule_date_time']))."? \n\nLet us know and earn Cashback!";
		$response['subscription_code'] = $data['code'];
		$response['button_text'] = [
			'attended'=>['text'=>'ATTENDED','url'=>Config::get('app.url')."/sessionstatuscapture/lost/".$data['_id']."?source=let_us_know"],
			'did_not_attend'=>['text'=>'DID NOT ATTEND','url'=>Config::get('app.url')."/sessionstatuscapture/didnotattend/".$data['_id']]
		];
		$response['image'] = 'https://b.fitn.in/paypersession/happy_face_icon-2.png';
		
		$response['block'] = true;
		
		return $response;
	
	}
	
	public function getCustomerUnmarkedAttendance()
	{

        // if(empty($_GET['lat']) || empty($_GET['lon'])){
        //     return ['status'=>400, 'message'=>'Checkin from invalid location'];
        // }
		Log::info($_SERVER['REQUEST_URI']);

		$resp=['status'=>200,'response'=>[]];
		try {
			
			$jwt_token = Request::header('Authorization');
			if(empty($jwt_token)) return ['status' => 400,'message' =>"Token absent"];
			$decoded = customerTokenDecode($jwt_token);
			$cust=(array)$decoded->customer;
			$customer_id = (int)$cust['_id'];
			$customer = Customer::find((int)$cust['_id']);
			$device_type= Request::header('Device-Type');
			if(empty($device_type)||!in_array($device_type, ['android','ios','web']))
				return ['status' => 400,'message' =>"Device Type not in Header or Invalid Device Type."];
// 			return (array)$cust;
			$data =	Input::json()->all();
			$rules = ['code' => 'required'];
			
			$validator = Validator::make($data, $rules);
			
			if ($validator->fails()) return ['status' => 400,'message' =>$this->errorMessage($validator->errors())];
			else
			{
				$dcd=$this->utilities->decryptQr($data['code'], Config::get('app.core_key'));
                Log::info($dcd);
				if(empty($dcd))
					return ['status' => 400,'message' =>"Invalid Qr Code"];
				$data=json_decode(preg_replace('/[\x00-\x1F\x7F]/', '', $dcd),true);
				
				if(empty($data['vendor_id'])||empty($data['owner'])||$data['owner']!='fitternity') return ['status' => 400,'message' =>"Invalid Qr Code"];
				$cur=new DateTime(date('Y-m-d H:i:s',strtotime("+2 hours")));
                $twoHours=new DateTime(date('Y-m-d H:i:s',strtotime("-2 hours")));
                $booktrial = Booktrial::where('customer_id',$customer_id)
				->whereIn('type',['booktrials','3daystrial', 'workout-session'])
				->whereIn('post_trial_status_updated_by_fitcode',[null, ''])
				->whereIn('post_trial_status_updated_by_qrcode',[null, ''])
				->whereIn('post_trial_status_updated_by_lostfitcode',[null, ''])
				->whereIn('post_trial_status',[null, ''])
				->where('schedule_date_time', '<=',$cur)
				->where('schedule_date_time', '>=',$twoHours)
				->orderBy('schedule_date_time','desc');
                
                if(in_array($data['vendor_id'], Config::get('app.sucheta_pal_finder_ids', []))){
                    $booktrial->where(function($query) use ($data){
                        $query->orWhere('finder_id',intval($data['vendor_id']))->orWhereIn('service_id', Config::get('app.sucheta_pal_service_ids', []));
                    });
                }else{
                    $booktrial->where('finder_id',intval($data['vendor_id']));
                }

				$booktrial = $booktrial->get();
				
				if(!empty($booktrial)&&count($booktrial)>0)
				{
				     
					$booktrial=$booktrial->toArray();
					$cnt=count($booktrial);
					if($cnt==1&&!empty($booktrial[0]['_id']))
					{
						$pop_up=["title"=>"Confirmation",
								"message"=>'Do you want to check-in for your '.(!empty($booktrial[0]['schedule_slot_start_time'])?$booktrial[0]['schedule_slot_start_time']:"").' '.(!empty($booktrial[0]['service_name'])?ucwords($booktrial[0]['service_name']):"").' at '.(!empty($booktrial[0]['finder_name'])?$booktrial[0]['finder_name']:"").'?',
								"positivebtn"=>"yes",
								"negativebtn"=>"No",
								"options"=>["mark"=>false,"_id"=>$booktrial[0]['_id']]	
							];
					}
					else
					{
						$header= "We have found ".$cnt." bookings for ".(!empty($booktrial[0]['finder_name'])?$booktrial[0]['finder_name']:"");
						$options=[];
						foreach ($booktrial as $value)
							(!empty($value['_id']))?array_push($options, ['name'=>(!empty($value['service_name'])?$value['service_name']:""),'subtitle'=>date_format(new DateTime($value['schedule_date_time']),"l, jS M Y H:ia"),"_id"=>$value['_id'],"mark"=>false]):"";
					
					}
				}
				else {
					
					\Finder::$withoutAppends=true; \Service::$withoutAppends=true;
					Service::$setAppends=['active_weekdays','serviceratecard'];
					$finderarr = Finder::active()->where('_id',intval($data['vendor_id']))
					->with(array('services'=>function($query){$query->active()->where('trial','!=','disable')->where('status','=','1')->select('*')->orderBy('ordering', 'ASC');}))
					->with('location')->first();
					
					$extended_validity_service_ids = [];
					
					$extended_validity_orders = $this->utilities->getExtendedValidityOrderFinder(['customer_email'=>$customer->email, 'finder_id'=>$finderarr['_id'], 'schedule_date'=>date('d-m-Y', time())]);
					if(!empty($extended_validity_orders)){

						$extended_validity_service_ids = array_column($extended_validity_orders->toArray(), 'service_id');
						
						$extended_validity_service_ids_multiple = array_flatten(array_column($extended_validity_orders->toArray(), 'all_service_id'));
						
						$extended_validity_service_ids = array_merge($extended_validity_service_ids, $extended_validity_service_ids_multiple);
					}

					$pnd_pymnt=$this->utilities->hasPendingPayments();
					
					$getWalletBalanceData = [
						'finder_id'=>$finderarr['_id'],
						'order_type'=>'workout-session'
					];
					
					$data['wallet_balance'] = $this->utilities->getWalletBalance($customer_id,$getWalletBalanceData);
					Log::info('wallet_balance');
					Log::info($data['wallet_balance']);
					
// 					if(!empty($pnd_pymnt)){$resp['response']['payment']=$pnd_pymnt;return $resp;}
					$serviceController=new ServiceController($this->utilities) ;
					if(!empty($finderarr['services'])&&count($finderarr['services'])>0)
					{
						$finderarr=$finderarr->toArray();
						$optionsBuy=[];
						foreach ($finderarr['services'] as $service)
						{	
							
							if(!empty($service['serviceratecard']))
							{
								
								
								
								$wsschedules=[];$trialsschedules=[];
								$workouts=[];$trials=[];
								foreach ($service['serviceratecard'] as $value) {
									if(!empty($value['type'])&&$value['type']=='workout session')
										array_push($workouts,$value);
										if(!empty($value['type'])&&$value['type']=='trial')
											array_push($trials,$value);
								}
								
								if(count($workouts)>0)
								{
									$workouts=$workouts[0];
									//	return $serviceController->getScheduleByFinderService(['finder_id'=>$data['vendor_id'],"show_all_price_qr"=>true,'service_id'=>$service['_id'],'type'=>'workout-session','requested_date'=>date("Y-m-d"),'date'=>date("Y-m-d")]);
									$workoutData=(array)json_decode(json_encode($serviceController->getScheduleByFinderService(['finder_id'=>$data['vendor_id'],"show_all_price_qr"=>true,'service_id'=>$service['_id'],'type'=>'workout-session','requested_date'=>date("Y-m-d"),'date'=>date("Y-m-d")])->getData()),true);
									
									if(!empty($workoutData)&&!empty($workoutData['status'])&&$workoutData['status']==200&&!empty($workoutData['schedules'])&&count($workoutData['schedules'])>0&&!empty($workoutData['schedules'][0]['slots'])&&count($workoutData['schedules'][0]['slots'])>0)
									{
										$price = !empty($workouts['special_price']) ? $workouts['special_price'] : $workouts['price'];
										Log::info('$price');
										Log::info($price);

										$paymentmode_selected=($pnd_pymnt || $data['wallet_balance'] >= $price || in_array($service['_id'], $extended_validity_service_ids))?[]:['paymentmode_selected'=>"pay_later"];
										$wallet_pass=(!empty($paymentmode_selected)&&!empty($paymentmode_selected['paymentmode_selected']))?["wallet"=>true]:[];

										if($data['wallet_balance']>= $price){
											$wallet_pass = ["wallet"=>true];
										}
										if(in_array($service['_id'], $extended_validity_service_ids)){
											$wallet_pass = ["wallet"=>true];
										}
										
										//(strpos($workoutData['schedules'][0]['cost'], "Free") === false)&&
										if(!empty($workoutData['schedules'][0]['direct_payment_enable']))
											$wsschedules=$this->utilities->getCoreSlotsView($workoutData['schedules'][0]['slots'],$service,$workouts['_id'],$workoutData['schedules'][0]['cost'],'workout-session',$service['servicecategory_id'],$cust,$device_type,$paymentmode_selected,date("Y-m-d"),$wallet_pass,!empty($workoutData['schedules'][0]['price_qr_special'])?$workoutData['schedules'][0]['price_qr_special']:null,!empty($workoutData['schedules'][0]['price_qr'])?$workoutData['schedules'][0]['price_qr']:null,!empty($workoutData['schedules'][0]['city_id'])?$workoutData['schedules'][0]['city_id']:null);
									}
								}
								
										
// 								if(count($trials)>0)
// 								{
// 									$trials=$trials[0];
// // 									$serviceController->getScheduleByFinderService(['finder_id'=>$data['vendor_id'],"show_all_price_qr"=>true,'service_id'=>$service['_id'],'type'=>'trial','requested_date'=>date("Y-m-d"),'date'=>date("Y-m-d")]);
// 									$trialsData=(array)json_decode(json_encode($serviceController->getScheduleByFinderService(['finder_id'=>$data['vendor_id'],"show_all_price_qr"=>true,'service_id'=>$service['_id'],'type'=>'trial','requested_date'=>date("Y-m-d"),'date'=>date("Y-m-d")])->getData()),true) ;
									
// 									if(!empty($trialsData)&&!empty($trialsData['status'])&&$trialsData['status']==200&&!empty($trialsData['schedules'])&&count($trialsData['schedules'])>0&&!empty($trialsData['schedules'][0]['slots'])&&count($trialsData['schedules'][0]['slots'])>0&&(strpos($trialsData['schedules'][0]['cost'], "Free") !== false))
// 									{
// 										$trialsschedules=$this->utilities->getCoreSlotsView($trialsData['schedules'][0]['slots'],$service,$trials['_id'],'0','booktrials',$service['servicecategory_id'],$cust,$device_type,$paymentmode_selected,date("Y-m-d"),$wallet_pass,null,null,!emptY($trialsData['schedules'][0]['city_id'])?$trialsData['schedules'][0]['city_id']:null);
// 									}
// 								}
								if(count($wsschedules)>0) $optionsBuy=array_merge($optionsBuy,$wsschedules);
								// if(count($trialsschedules)>0) $optionsBuy=array_merge($optionsBuy,$trialsschedules);
							}
							
							
								
							}
							if(count($optionsBuy)>0)
							{
								// if(count($optionsBuy)==1)
								// {
								// 	$pop_up=[];
								// 	$pop_up['title']="Confirmation";
								// 	$pop_up["positivebtn"]="yes";
								// 	$pop_up["negativebtn"]="No";
								// 	$pop_up["options"]=$optionsBuy[0];
								// 	Log::info($optionsBuy[0]);
								// 	$pop_up['message']="Do you want to book a ".$optionsBuy[0]['schedule_slot']." slot for ".preg_replace('/membership/i', 'Workout', $optionsBuy[0]['service_name'])." at ".$finderarr['title']."?";
								// 	// if(!empty($optionsBuy[0]['cost']) && (strpos($optionsBuy[0]['cost'], "Free") !== false))
								// 	// 	$pop_up['message']="Would you like to book a slot from ".$optionsBuy[0]['schedule_slot']."?";
								// 	// 	else $pop_up['message']="Would you like to buy a slot ".$optionsBuy[0]['schedule_slot']."?";
										
								// }
								// else 
								// {
									$resp['response']['header']="Showing all available services of ".$finderarr['title'].', '.$finderarr['location']['name']." happening today (".date('jS, M', time()).")";

                                     if(!empty($finderarr['location_id']) && $finderarr['location_id'] == 10000){
                                        $resp['response']['header']="Showing all available services of ".$finderarr['title'].', '.$finderarr['custom_location']." happening today (".date('jS, M', time()).")";
                                    }

									$resp['response']['title']="BOOK A SLOT";
									
									if(empty($customer['loyalty'])){
										$resp['response']['subtitle']="(Gets you auto-registered for FitSquad)";
									}

									$resp['response']['options']=$optionsBuy;
								// }

								$resp['response']['new_booking']=true;
							}
							else {
								unset($resp['response']);
								$resp['response']['title']="BOOK A SLOT";
								$resp['response']['subtitle']="OOPs no slots are available at ".$finderarr['title'].', '.$finderarr['location']['name']." right now.";
                                if(!empty($finderarr['location_id']) && $finderarr['location_id'] == 10000){
    								$resp['response']['subtitle']="OOPs no slots are available at ".$finderarr['title'].', '.$finderarr['custom_location']." right now.";
                                }
							}
						}
						else {
							unset($resp['response']);
							$resp['response']['title']="BOOK A SLOT";
							$resp['response']['subtitle']="OOPs no services are available at ".$finderarr['title'].', '.$finderarr['location']['name'];

                            if(!empty($finderarr['location_id']) && $finderarr['location_id'] == 10000){
                                $resp['response']['subtitle']="OOPs no services are available at ".$finderarr['title'].', '.$finderarr['custom_location'];
                            }
						}

						if(!empty($resp['response'])){
							$booking_response =$resp['response'];
							unset($resp['response']);
							$resp['response']['bookings'] = $booking_response;
						}
						if(!empty($resp['header'])){
							$resp['response']['bookings'] = $resp;
							unset($resp['header']);
						}


						// return $resp;
						// $booking_details = $
						Log::info("mobile_verfied");
						Log::info($this->mobile_verified);
						$session_not_completed = $this->checkInsList($customer['_id'], $this->device_token, true, $finderarr);
						$this->getQRLoyaltyScreen($resp, $customer, $finderarr, $session_not_completed);
						
				}
				
				if(!empty($pop_up))$resp['response']['bookings']['pop_up']=$pop_up;
				if(!empty($header))$resp['response']['bookings']['header']=$header;
				if(!empty($options))$resp['response']['bookings']['options']=$options;
				
                // if(empty($pop_up)&&empty($options)&&empty($optionsBuy))unset($resp['response']['bookings']);

                return $resp;
			}
		} 
		catch (Exception $e) {
            Log::info(['status'=>400,'message'=>$e->getMessage().' - Line :'.$e->getLine().' - Code :'.$e->getCode().' - File :'.$e->getFile()]);
			return ['status'=>400,'message'=>'Something went wrong. Please try again later'];
			// return ['status'=>400,'message'=>$e->getMessage().' - Line :'.$e->getLine().' - Code :'.$e->getCode().' - File :'.$e->getFile()];
		}
		return $resp;
	}
	
	
	public function markCustomerAttendance()
	{		$resp=['status'=>200];
    Log::info($_SERVER['REQUEST_URI']);
	try {
		$jwt_token = Request::header('Authorization');
		if(empty($jwt_token)) return ['status' => 400,'message' =>"Token absent"];
		$decoded = customerTokenDecode($jwt_token);
		$customer_id = (int)$decoded->customer->_id;
		$data =	Input::json()->all();
		$rules = ['data' => 'required'];
		Log::info($data);
		$validator = Validator::make($data, $rules);
		if ($validator->fails()) return ['status' => 400,'message' =>$this->errorMessage($validator->errors())];
		else
		{
			$invalid_data=array_filter($data['data'],function ($e){return (empty($e['_id'])||!isset($e['mark']));});
			if(count($invalid_data)>0) return ['status' => 400,'message' =>"Invalid Data"];
			$un_updated=[];$not_located=[];$already_attended=[];$attended=[];$not_attended=[];
			
			$total_fitcash=0;
			foreach ($data['data'] as $key => $value)
			{
				$booktrial = Booktrial::where('customer_id',$customer_id)->where('_id',intval($value['_id']))->first();
				$post_trial_status_updated_by_qrcode = time();
				
				
				if(!empty($booktrial))
				{
					$payment_done = !(isset($booktrial->payment_done) && !$booktrial->payment_done);
					$pending_payment = [
							'header'=>"Pending Amount ".$this->utilities->getRupeeForm($booktrial['amount_finder']),
							'sub_header'=>"Make sure you pay up, to earn Cashback & continue booking more sessions",
							'trial_id'=>$booktrial['_id']
					];
					if(!empty($booktrial['order_id']))$pending_payment['order_id']=$booktrial['order_id'];
					$customer_level_data = $this->utilities->getWorkoutSessionLevel($booktrial['customer_id']);
					if(!$key){
					    $this->utilities->autoRegisterCustomerLoyalty($booktrial);
                    }

                    if(!empty($value['mark'])){
						$this->utilities->addCheckin(['customer_id'=>$customer_id, 'finder_id'=>$booktrial['finder_id'], 'type'=>'workout-session', 'sub_type'=>$booktrial->type, 'fitternity_customer'=>true, 'tansaction_id'=>$booktrial['_id'], "checkout_status"=> false, 'device_token' => $this->device_token]);
					}

					
					
					if($booktrial->type == "booktrials" && !isset($booktrial->post_trial_status_updated_by_fitcode)&& !isset($booktrial->post_trial_status_updated_by_lostfitcode)&& !isset($booktrial->post_trial_status_updated_by_qrcode))
					{
						if(empty($booktrial->post_trial_status)||$booktrial->post_trial_status!='no show')
						{
							if(!empty($value['mark']))
								$booktrial_update = Booktrial::where('_id', intval($value['_id']))->update(['post_trial_status_updated_by_qrcode'=>$post_trial_status_updated_by_qrcode]);
								else $booktrial_update = Booktrial::where('_id', intval($value['_id']))->update(['post_trial_status'=>'no show']);
								

								if($booktrial_update&&!empty($value['mark']))
								{
									if(!isset($booktrial['extended_validity_order_id'])){
										$fitcash = $this->utilities->getFitcash($booktrial->toArray());
										$req = array(
												"customer_id"=>$booktrial['customer_id'],"trial_id"=>$booktrial['_id'],"amount"=> $fitcash,"amount_fitcash" => 0,"amount_fitcash_plus" => $fitcash,
												"type"=>'CREDIT','entry'=>'credit','validity'=>time()+(86400*21),
												'description'=>"Added FitCash+ on Trial Attendance By QrCode Scan, Applicable for buying a membership at ".ucwords($booktrial['finder_name'])." Expires On : ".date('d-m-Y',time()+(86400*21)),
												"valid_finder_id"=>intval($booktrial['finder_id']),"finder_id"=>intval($booktrial['finder_id']),"qrcodescan"=>true);
										$add_chck=$this->utilities->walletTransaction($req);
									}
									else {
										$fitcash = 0;
									}
									
									if((!empty($add_chck)&&$add_chck['status']==200) || (isset($booktrial['extended_validity_order_id'])))
									{
										$total_fitcash=$total_fitcash+$fitcash;
										if(!isset($add_chck) && (isset($booktrial['extended_validity_order_id']))){
											$add_chck = null;
										}
										$resp1=$this->utilities->getAttendedResponse('attended',$booktrial,$customer_level_data,$pending_payment,$payment_done,$fitcash,$add_chck);
										if(isset($booktrial['extended_validity_order_id'])) {
											if(isset($resp1) && isset($resp1['sub_header_1'])){
												$resp1['sub_header_1'] = '';
											}
											if(isset($resp1) && isset($resp1['sub_header_2'])){
												$resp1['sub_header_2'] = '';
											}
											if(isset($resp1) && isset($resp1['description'])){
												$resp1['description'] = '';
											}
											if(isset($resp1) && isset($resp1['image'])){
												$resp1['image'] = 'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png';
											}
										}
										array_push($attended, $resp1);
									}
									else array_push($un_updated,$value['_id']);
								}
								else  {
									$resp1=$this->utilities->getAttendedResponse('didnotattended',$booktrial,$customer_level_data,$pending_payment,$payment_done,null,null);
									if(isset($resp1) && isset($resp1['sub_header_1'])){
										$resp1['sub_header_1'] = '';
									}
									if(isset($resp1) && isset($resp1['sub_header_2'])){
										$resp1['sub_header_2'] = '';
									}
									if(isset($resp1) && isset($resp1['description'])){
										$resp1['description'] = '';
									}
									array_push($not_attended,$resp1);
								}
								
						}
						else array_push($already_attended,$value['_id']);
					}
					else if($booktrial->type == "workout-session"&&!isset($booktrial->post_trial_status_updated_by_qrcode)&&!isset($booktrial->post_trial_status_updated_by_lostfitcode)&&!isset($booktrial->post_trial_status_updated_by_fitcode))
					{
						
						if(empty($booktrial->post_trial_status)||$booktrial->post_trial_status=='no show')
						{
							if(!empty($value['mark']))
								$booktrial_update = Booktrial::where('_id', intval($value['_id']))->update(['post_trial_status_updated_by_qrcode'=>$post_trial_status_updated_by_qrcode]);
								else $booktrial_update = Booktrial::where('_id', intval($value['_id']))->update(['post_trial_status'=>'no show']);
								
								
								if($booktrial_update&&!empty($value['mark'])&& !(isset($booktrial->payment_done) && $booktrial->payment_done == false)){
									
									if(!isset($booktrial['extended_validity_order_id'])){
										$fitcash = $this->utilities->getFitcash($booktrial->toArray());
										$req = array(
												"customer_id"=>$booktrial['customer_id'],"trial_id"=>$booktrial['_id'],
												"amount"=> $fitcash,"amount_fitcash" => 0,"amount_fitcash_plus" => $fitcash,"type"=>'CREDIT',
												'entry'=>'credit','validity'=>time()+(86400*21),'description'=>"Added FitCash+ on Workout Session Attendance By QrCode Scan","qrcodescan"=>true
										);
										
										$booktrial->pps_fitcash=$fitcash;
										$booktrial->pps_cashback=$this->utilities->getWorkoutSessionLevel((int)$booktrial->customer_id)['current_level']['cashback'];
										$add_chck=$this->utilities->walletTransaction($req);
									}
									else {
										$fitcash = 0;
									}
									
									if((!empty($add_chck)&&$add_chck['status']==200) || (isset($booktrial['extended_validity_order_id'])))
									{
										$total_fitcash=$total_fitcash+$fitcash;
										if(!isset($add_chck) && (isset($booktrial['extended_validity_order_id']))){
											$add_chck = null;
										}
										$resp1=$this->utilities->getAttendedResponse('attended',$booktrial,$customer_level_data,$pending_payment,$payment_done,$fitcash,$add_chck);
										if(isset($booktrial['extended_validity_order_id'])) {
											if(isset($resp1) && isset($resp1['sub_header_1'])){
												$resp1['sub_header_1'] = '';
											}
											if(isset($resp1) && isset($resp1['sub_header_2'])){
												$resp1['sub_header_2'] = '';
											}
											if(isset($resp1) && isset($resp1['description'])){
												$resp1['description'] = '';
											}
											if(isset($resp1) && isset($resp1['image'])){
												$resp1['image'] = 'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png';
											}
										}
										array_push($attended,$resp1);
									}
									else array_push($un_updated,$value['_id']);
								}
								if($booktrial_update&&!empty($value['mark'])){
									$resp1=$this->utilities->getAttendedResponse('attended',$booktrial,$customer_level_data,$pending_payment,$payment_done,null,null);
									if(isset($booktrial['extended_validity_order_id'])) {
										if(isset($resp1) && isset($resp1['sub_header_1'])){
											$resp1['sub_header_1'] = '';
										}
										if(isset($resp1) && isset($resp1['sub_header_2'])){
											$resp1['sub_header_2'] = '';
										}
										if(isset($resp1) && isset($resp1['description'])){
											$resp1['description'] = '';
										}
										if(isset($resp1) && isset($resp1['image'])){
											$resp1['image'] = 'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png';
										}
									}
									array_push($attended,$resp1);
								}
								else  {
									
									$resp1=$this->utilities->getAttendedResponse('didnotattended',$booktrial,$customer_level_data,$pending_payment,$payment_done,null,null);
									if(isset($booktrial['extended_validity_order_id'])) {
										if(isset($resp1) && isset($resp1['sub_header_1'])){
											$resp1['sub_header_1'] = '';
										}
										if(isset($resp1) && isset($resp1['sub_header_2'])){
											$resp1['sub_header_2'] = '';
										}
										if(isset($resp1) && isset($resp1['description'])){
											$resp1['description'] = '';
										}
									}
									array_push($not_attended,$resp1);
									
								}
						}
						else array_push($already_attended,$value['_id']);
					}
					else array_push($already_attended,$value['_id']);
					
					if(!empty($value['mark']))
					{
						$booktrial->post_trial_status = 'attended';
						$booktrial->post_trial_initail_status = 'interested';
						$booktrial->post_trial_status_updated_by_qrcode= $post_trial_status_updated_by_qrcode;
						$booktrial->post_trial_status_date = time();
					}
					$booktrial->update();
				}
				else array_push($not_located,$value['_id']);
			}
			if(count($attended)>0)
			{
				$resp['response']=$attended[0];
				return $resp;
			}
			else if(count($not_attended)>0)
			{
				$resp['response']=$not_attended[0];
				return $resp;
			}
			
			else if(count($not_located)>0)
			{
				// 				$resp['not_located']=$not_located[0];
				$resp['message']="Didn't locate any bookings.";
				return $resp;
			}
			
			else if(count($already_attended)>0)
			{
				// 				$resp['already_marked']=$already_attended[0];
				$resp['message']="Already accepted your response.";
				return $resp;
			}
			else if(count($un_updated)>0)
			{
				return ['status'=>400,"message"=>"Failed to update.","ids"=>$un_updated];
			}
			
			return ['status'=>400,'message'=>"No such bookings found"];
			// 			if(!empty($un_updated))
				// 				$resp['response']['failed']=$un_updated;
				// 			if(isset($total_fitcash))
					// 				$resp['response']['total_fitcash_added']=$total_fitcash;
					// 			$resp['message']="Thank You, we have accepted your response.";
					// 			return $resp;
		}
	}
	catch (Exception $e) {
	    Log::info(['status'=>400,'message'=>$e->getMessage().' - Line :'.$e->getLine().' - Code :'.$e->getCode().' - File :'.$e->getFile()]);
		return ['status'=>400,'message'=>'Something went wrong, Please try again later'];
		// return ['status'=>400,'message'=>$e->getMessage().' - Line :'.$e->getLine().' - Code :'.$e->getCode().' - File :'.$e->getFile()];
	}
	return $resp;
	}

	public function loyaltyProfile(){
		Log::info("asdas");
		$post = false;
		$jwt_token = Request::header('Authorization');
		$customer = null;
        $filter = [];
        
        $customer = $this->utilities->getCustomerFromTokenAsObject();

        if(!empty($customer->_id)){

            $customer = Customer::active()->where('_id', $customer->_id)->where('loyalty', 'exists', true)->first();
            $filter = $this->utilities->getMilestoneFilterData($customer);

			if($customer && !empty($customer['loyalty'])){
				$post = true;
			}

			// Log::info("customer_id:   ".$customer->_id);
			// Log::info("customer_email:   ".$customer->email);
        }
        
        $voucher_categories = $this->utilities->getVoucherCategoriesAggregate($filter);
		
        $voucher_categories_map = [];
	
        foreach($voucher_categories['result'] as $vc){
            $voucher_categories_map[$vc['_id']] = $vc['vouchers'];
            if(!$post ){
                $voucher_categories_map[$vc['_id']][0]['max_amount'] = $vc['amount'];
            }
        }
		
		if($post){

            return $this->postLoyaltyRegistration($customer, $voucher_categories_map);

		}else{

            return $this->preLoyaltyRegistration($voucher_categories_map);
			
			
		}
	}


	public function registerLoyalty(){
		
		$data = Input::json()->all();

		Log::info($data);
        
        $rules = [
            'customer_name' => 'required|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'max:15',
            'customer_membership' => 'required|in:yes,no'
        ];
        $validator = Validator::make($data,$rules);
		
		if ($validator->fails()) {
            return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);
		}
		
		
		
        $customer_id = autoRegisterCustomer($data);
        $customer = Customer::active()->where('_id', $customer_id)->first();
        if($customer && !empty($customer->loyalty)){
			return Response::json(['message'=>'Already registered for Fitsquad'], 400);
        }
        $data['customer_id'] = $customer_id;
        if(!empty($data['membership_end_date'])){
			// $data['end_date'] = new Mongodate(strtotime($data['membership_end_date']));
			$data['end_date_month_year'] = $data['membership_end_date'];
        }
        if(!empty($data['finder_id'])){
			$data['finder_id'] = intval($data['finder_id']);
        }
        $resp = $this->utilities->autoRegisterCustomerLoyalty($data);
        if(empty($resp['status']) || $resp['status'] != 200){
			return $resp;
        }
		
		if(!empty($data['url'])){
			
			$parts = parse_url($data['url']);
			parse_str($parts['query'], $query);
			
			if(!empty($query['finder_id'])){
				$qr_finder_id = $query['finder_id'];

				$checkin_data = [
					'customer_id'=>$customer_id,
					'finder_id'=>intval($qr_finder_id),
					'type'=>'workout-session',
					'unverified'=>false,
					"checkout_status"=> false,
					'device_token' => $this->device_token
				];

				$addedCheckin = $this->utilities->addCheckin($checkin_data);

				Log::info('$addedCheckin');
				Log::info($addedCheckin);
			}
			

		}

        if(!empty($data['customer_phone'])){
            $customer->contact_no = substr($data['customer_phone'], -10);
        }
        $fields_to_update = ['city_id', 'gender'];
        foreach($fields_to_update as $field){
            if(!empty($data[$field])){
                $customer->$field = $data[$field];
            }
        }
        $customer->update();
        $token = $this->createToken($customer);
   	Log::info(['message'=>'Registration succesfull', 'token'=>$token['token'], 'password'=>true]);
        return Response::json(['status'=>200,'message'=>'Registration succesfull', 'token'=>$token['token'], 'password'=>true]);
        
    }



	public function listCheckins(){
		$check_ins = Config::get("loyalty_constants.checkin_limit");
		$jwt_token = Request::header('Authorization');
		
		if(!empty($jwt_token)){
			$decoded = decode_customer_token($jwt_token);
			$customer_id = $decoded->customer->_id;
		 	$checkins = Checkin::where('customer_id', $customer_id)
			->orderBy('_id', 'asc')
			->get()
			->toArray();
		}
		
		function format_date(&$value,$key){
			$value['date'] = date('d M, Y | g:i A ', strtotime($value['created_at']));
			$value['title'] = $value['finder']['title'];
			$value['position'] = $key % 2 + 1;

			$value = array_only($value, ['date', 'title', 'position']);
		}
	
		array_walk($checkins, 'format_date');
		
		$milestones = Config::get('loyalty_constants.milestones');
		
		foreach($milestones as $key => $milestone_data){
			if($milestone_data['milestone']){

				if(!empty($checkins[$milestone_data['count']+($key-2)])){
					
					array_splice($checkins, $milestone_data['count']+($key-1), 0, [['milestone'=>$milestone_data['milestone']]]);
				
				}else{

					break;

				}

			}
		}
		$checkins = array_reverse($checkins);

		$customer = Customer::find($customer_id);
		
		if(!empty($customer->loyalty['start_date'])){
			
			array_push($checkins, ['title'=>'Registered for','date'=>'FITSQUAD' , 'start'=>date('d M, Y', $customer->loyalty['start_date']->sec)]);

		}
	
		return Response::json(['data'=>$checkins]);
		
	}

	public function claimExternalCoupon($_id=null){

		$data = Input::json()->all();
		
		if(!$_id){
			return Response::json(array('status' => 400,'message' => 'Cannot claim reward. Please contact customer support (1).'));
		}
		
		$jwt_token = Request::header('Authorization');
		if(!empty($jwt_token)){

			$decoded = decode_customer_token($jwt_token);
			$customer_id = $decoded->customer->_id;
			$customer = Customer::find($customer_id);
			$milestones = $this->getCustomerMilestones($customer);


            if(!empty($_GET['milestone']) && !empty($milestones[intval($_GET['milestone'])-1]['voucher'])){
                
                if(!isset($_GET['index']) || empty($milestones[intval($_GET['milestone'])-1]['voucher'][(int)$_GET['index']])){
			        return Response::json(array('status' => 400,'message' => 'Cannot claim reward. Please contact customer support (4).'));
                }

                $voucherAttached = $milestones[intval($_GET['milestone']) - 1]['voucher'][(int)$_GET['index']];

            }else{

                $voucher_category = VoucherCategory::find($_id);

                if(!empty($milestones[$voucher_category['milestone']-1]) && !empty($milestones[$voucher_category['milestone']-1]['verified'])){

    				/* if(!empty($milestones[$voucher_category['milestone']-1]['claimed'])){
    
    					return Response::json(array('status' => 400,'message' => 'Reward already claimed for this milestone'));
    
					} */
					
					$voucherAttached = $this->utilities->assignVoucher($customer, $voucher_category);
					// Log::info('before adding fitcash-> voucher_catageory', $voucher_category);
					// Log::info('before adding fitcash-> customer_id', $customer_id);	
					try{
						if(!empty($voucher_category->fitcash)){
							$voucher_category_fitcash = array(
								"id"=>$customer_id,
								"voucher_catageory"=>$voucher_category
							);
							$this->utilities->addFitcashforVoucherCatageory($voucher_category_fitcash);
						}
					}
					catch(\Exception $err){
						return Response::json(array('status' => 400,'message' => 'Cannot Claim Fitcash. Please contact customer support (5).'));
					}
                    if(!$voucherAttached){
                        return Response::json(array('status' => 400,'message' => 'Cannot claim reward. Please contact customer support (2).'));
                    }
                    /* return
                    if(empty($milestones[$voucher_category['milestone']-1]['claimed'])){
    					return Response::json(array('status' => 400,'message' => 'Reward already claimed for this milestone'));
						$milestones[$voucher_category['milestone']-1]['claimed'] = true; */
						
                        $voucherAttached = $voucherAttached->toArray();
                        $voucherAttached['claimed_date_time'] = new \MongoDate();                  
                        $milestones[$voucher_category['milestone']-1]['voucher'] = !empty($milestones[$voucher_category['milestone']-1]['voucher']) ? $milestones[$voucher_category['milestone']-1]['voucher'] : [];
                        array_push($milestones[$voucher_category['milestone']-1]['voucher'], $voucherAttached);           
                        $loyalty = $customer->loyalty;
                        $loyalty['milestones'] = $milestones;
                        $customer->loyalty = $loyalty;             
                        $customer->update();
                    // }

                    $communication = true;

                }else{
                    
                    return Response::json(array('status' => 400,'message' => 'Cannot claim reward. Please contact customer support (3).'));
                
                }
            }

            $resp =  [
                'voucher_data'=>[
                    'header'=>"VOUCHER UNLOCKED",
                    'sub_header'=>"You have unlocked ".(!empty($voucherAttached['name']) ? strtoupper($voucherAttached['name']) : ""),
                    'coupon_title'=>(!empty($voucherAttached['description']) ? $voucherAttached['description'] : ""),
                    'coupon_text'=>"USE CODE : ".strtoupper($voucherAttached['code']),
                    'coupon_image'=>(!empty($voucherAttached['image']) ? $voucherAttached['image'] : ""),
                    'coupon_code'=>strtoupper($voucherAttached['code']),
                    'coupon_subtext'=>'(also sent via email/sms)',
                    'unlock'=>'UNLOCK VOUCHER',
                    'terms_text'=>'T & C applied.'
                ]
            ];
            if(!empty($voucherAttached['flags']['manual_redemption']) && empty($voucherAttached['flags']['swimming_session'])){
                $resp['voucher_data']['coupon_text']= $voucherAttached['name'];
                $resp['voucher_data']['header']= "REWARD UNLOCKED";
            }

            if(!empty($voucher_category['email_text'])){
                $resp['voucher_data']['email_text']= $voucher_category['email_text'];
            }
            $resp['voucher_data']['terms_detailed_text'] = $voucherAttached['terms'];
            if(!empty($communication)){
                $redisid = Queue::connection('redis')->push('CustomerController@voucherCommunication', array('resp'=>$resp['voucher_data'], 'delay'=>0,'customer_name' => $customer['name'],'customer_email' => $customer['email'],),Config::get('app.queue'));
            }

            return $resp;

		}
	}

	public function checkinInitiate($finder_id, $finder_data, $customer_id){

		Log::info($_SERVER['REQUEST_URI']);

		$finder_id = intval($finder_id);
		
		// $jwt_token = Request::header('Authorization');
		
		// $decoded = decode_customer_token($jwt_token);
		// $customer_id = $decoded->customer->_id;

        $type = !empty($_GET['type']) ? $_GET['type'] : null;
        $session_pack = !empty($_GET['session_pack']) ? $_GET['session_pack'] : null;
        //$unverified = !empty($_GET['type']) ? true : false;
        //$customer = Customer::find($customer_id);

        // if(!empty($type) && $type == 'workout-session'){
        //     $loyalty = $customer->loyalty;
        //     $finder_ws_sessions = !empty($loyalty['workout_sessions'][(string)$finder_id]) ? $loyalty['workout_sessions'][(string)$finder_id] : 0;
            
        //     if($finder_ws_sessions >= 5){
        //         $type = 'membership';
        //         $update_finder_membership = true;
        //     }else{
        //         $update_finder_ws_sessions = true;
        //     }
        // }
		
		$checkin_data = [
			'customer_id'=>$customer_id,
			'finder_id'=>intval($finder_id),
			'type'=>$type,
			'unverified'=>!empty($_GET['type']) ? true : false,
			'checkout_status' => false,
			'device_token' => $this->device_token
        ];
		Log::info('before schedule_sessions::::::::::::: device id',[$this->device_token, $checkin_data]);
        if(!empty($_GET['receipt'])){
            $checkin_data['receipt'] = true;
        }
        if(!empty($session_pack)){

            $order_id = intval($_GET['session_pack']);
            
            $schedule_session = $this->utilities->scheduleSessionFromOrder($order_id);
			//Log::info('schedule_sessions:::::::::::::',[$schedule_session, $this->device_id]);
		}
        
        if(empty($schedule_session['status']) || $schedule_session['status'] != 200){
            
			$addedCheckin = $this->utilities->addCheckin($checkin_data);
			//Log::info('adedcheckins:::::::::::::',[$addedCheckin]);
        
		}
		$finder = $finder_data;	
		if(!empty($addedCheckin['status']) && $addedCheckin['status'] == 200 || (!empty($schedule_session['status']) && $schedule_session['status'] == 200)){
			// if(!empty($update_finder_ws_sessions)){
			// 	// $loyalty['workout_sessions'][$finder_id] = $finder_ws_sessions + 1;
			// 	// $customer->update(['loyalty'=>$loyalty]);
			// 	Customer::where('_id', $customer_id)->increment('loyalty.workout_sessions.'.$finder_id);
			// }elseif(!empty($update_finder_membership)){
			// 	if(empty($loyalty['memberships']) || !in_array($finder_id, $loyalty['memberships'])){
			// 		array_push($loyalty['memberships'], $finder_id);
			// 		$customer->update(['loyalty'=>$loyalty]);
			// 	}
			// }
			// $return =  [
			// 	'header'=>'CHECK-IN SUCCESSFUL!',
			// 	'sub_header_2'=> "Enjoy your workout at ".$finder['title'].".\n Make sure you continue with your workouts and achieve the milestones quicker",
			// 	'milestones'=>$this->utilities->getMilestoneSection(),
			// 	'image'=>'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png',
			// 	// 'fitsquad'=>$this->utilities->getLoyaltyRegHeader($customer)
			// ];
			$resp = $this->checkinCheckoutSuccessMsg($finder);
			$resp['header'] = 'CHECK- IN SUCCESSFUL';
			$resp['sub_header_2'] = "Enjoy your workout at ".$finder['title']."\n Make sure you check-out post your workout by scanning the QR code again to get the successful check-in towards the goal of reaching your milestone. \n\n Please note - The check-in will not be provided if your check-out time is not mapped out. Don`t forget to scan the QR code again post your workout.";
			return $resp;
			// if(!empty($addedCheckin['already_checked_in'])){
            //     $return['header'] = 'CHECK-IN ALREADY MARKED FOR TODAY';
            // }
			// return $return;
		}else{	
			return $addedCheckin;
		}
	}

	public function uploadReceiptLoyalty(){
		
		$data = Input::all();

		if(empty($data)){
		   
			$data = Input::json()->all();

		}
	    $jwt_token = Request::header('Authorization');

		$decoded = decode_customer_token($jwt_token);
		$customer_id = $decoded->customer->_id;
		Log::info("===========================================");
		Log::info($_FILES);
		Log::info($_POST);
		Log::info($data);
		Log::info("===========================================");
		// Log::info(get_class($data['image']));

		$image = Input::file('image') ;
		
        
        Log::info("Asdsaddasdasd1111122221");

        if($image) {


            if ($image->getError()) {

                return Response::json(['status' => 400, 'message' => 'Please upload jpg/jpeg/png image formats with max. size of 10 MB']);

            }
			Log::info("Asdsaddasdasd111111");

			$data = [
				"input"=>$image,
				"upload_path"=>Config::get('app.aws.membership_receipt.path'),
				"local_directory"=>public_path().'/membershipt_receipt',
				"file_name"=>$customer_id.'-'.time().'.'.$image->getClientOriginalExtension()
				// "resize"=>["height" => 200,"strategy" => "portrait"],
			];

			$upload_resp = $this->utilities->uploadFileToS3Kraken($data);

			Log::info($upload_resp);

			if(!$upload_resp || empty($upload_resp['success'])){
				return Response::json(['status'=>200, 'message'=>'Error']);
			}


			$customer = Customer::find($customer_id, ['loyalty']);

			if(empty($customer->loyalty)){
                return Response::json(['status'=>400, 'message'=>'Not registered for FITSQUAD']);
            }

            $loyalty = !empty($customer->loyalty) ? $customer->loyalty : new stdClass();

			$receipts = !empty($loyalty['receipts']) ? $loyalty['receipts'] : [];

            array_push($receipts, ['url'=>str_replace("s3.ap-southeast-1.amazonaws.com/", "", $upload_resp['kraked_url']), 'date'=> new \MongoDate()]);

			$loyalty['receipts'] = $receipts;
			
			$loyalty['receipt_under_verfication'] = true;

            $customer->loyalty = $loyalty;

            $customer->save();

			return ['status'=>200, 'message'=>'Receipt has been upload successfully. You will receive the coupon via email / sms upon successful validations'];

	   }else{
			return Response::json(['status'=>400, 'message'=>'Image not found']);
	   }
	}

	public function getQRLoyaltyScreen(&$resp, $customer, $finderarr, $session_not_completed){
		if(empty($customer['loyalty'])){
			$resp['response']['title'] = "Register / Book Now";
			$resp['response']['fitsquad'] = [
				'logo' => Config::get('loyalty_constants.fitsquad_logo'),
				'header1' => 'REGISTER TO FITSQUAD',
				'header2' => 'INDIA\'S LARGEST FITENSS CLUB',
				'header3' => 'GET REWARDED FOR EVERY WORKOUT',
				'button_text' => 'REGISTER',
				'url' => $this->utilities->getLoyaltyRegisterUrl($finderarr['_id']),
				'type' => 'register',
			];

		}else{
			$resp['response']['title'] = "Check In / Book Now";

			$resp['response']['fitsquad'] = [
				'logo' => Config::get('loyalty_constants.fitsquad_logo'),
				'header1' => 'CHECK-IN FOR YOUR WORKOUT',
				'header3' => 'Mark Your Attendance At '.$finderarr['title'].' And Level Up To Reach Your Milestone',
				'button_text' => 'CHECK-IN',
				'url' => Config::get('app.url').'/markcheckin/'.$finderarr['_id'],
				'type' => 'checkin',
			];
			if($session_not_completed['status']){
				$resp['response']['fitsquad'] = $session_not_completed;
				unset($resp['response']['fitsquad']['status']);
			}
			$direct_checkin = false;
			
			
            $current_membership = Order::active()->where('customer_id', $customer['id'])->where('finder_id', $finderarr['_id'])->where('type', 'memberships')->where('start_date', '<', new DateTime())->where('end_date', '>=', new DateTime())->where(function($query){$query->where('extended_validity', '!=', true)->orWhere('sessions_left', '>', 0);})->first();
            
            if(!$current_membership){
                 
                if(!empty($customer['loyalty']['receipts'])){
                    
                    $receipts_verified = array_where($customer['loyalty']['receipts'], function($key, $x) use ($finderarr){
                        return !empty($x['verified']) && !empty($x['finder_id']) && !empty($x['verified_start_date']) && !empty($x['verified_end_date']) && $x['finder_id'] == $finderarr['_id'] && time() > $x['verified_start_date']->sec && time() < $x['verified_end_date']->sec;
                    });

                    if(!empty($receipts_verified)){
        				$direct_checkin = true;
                        $receipt = true;
                    }
                }
            
            }

			if($current_membership || $direct_checkin){
				
				$direct_checkin = true;
			
			}else if(!empty($customer['loyalty']['memberships'])  && in_array($finderarr['_id'], $customer['loyalty']['memberships']) || (!empty($customer['loyalty']['finder_id']) && $customer['loyalty']['finder_id'] == $finderarr['_id'])){
				
				$direct_checkin = true;
				$external_membership = true;
					
			}else if(!empty($customer['loyalty']['workout_session'][(string)$finderarr['_id']])){
				
				$direct_checkin = true;
				$external_ws_session = true;
					
			}else{

				$direct_checkin = false;

			}

			
			
			if(empty($direct_checkin) && !$session_not_completed['status']){
				
				$resp['response']['fitsquad']['url'] = "";
				$resp['response']['fitsquad']['data'] = [
					"header"=> "What are you checking-in for?",
					"subtitle"=> "Let us know the reason to assist you better",
					"options" => [
						[
							"text" => "Currently have a membership at ".$finderarr['title'],
							"url" => Config::get('app.url')."/markcheckin/".$finderarr['_id']."?type=membership",
						],
						[
							"text" => "Have booked a session at ".$finderarr['title'],
							"url" => Config::get('app.url')."/markcheckin/".$finderarr['_id']."?type=workout-session",
						]
					]
				];
			}else{

				$resp['response']['fitsquad']['url'] = Config::get('app.url').'/markcheckin/'.$finderarr['_id'];
				
				if(!empty($receipt)){
					$resp['response']['fitsquad']['url'] = Config::get('app.url')."/markcheckin/".$finderarr['_id']."?receipt=true";
				}
				if(!empty($external_membership)){
					$resp['response']['fitsquad']['url'] = Config::get('app.url')."/markcheckin/".$finderarr['_id']."?type=membership";
				}
				
                if(!empty($external_ws_session)){
					$resp['response']['fitsquad']['url'] = Config::get('app.url')."/markcheckin/".$finderarr['_id']."?type=workout-session";
                }
                
                if(!empty($current_membership['extended_validity'])){
					$resp['response']['fitsquad']['url'] = Config::get('app.url')."/markcheckin/".$finderarr['_id']."?session_pack=".$current_membership['_id'];
                }
			}
		}
	}

    public function updateFreshchatId(){

        try{
            
            $data = Input::all();

            if(empty($data)){
                $data = Input::json()->all();
            }

            $jwt = Request::header('Authorization');

            if(empty($jwt)){
                return ['status'=>400];
            }

            $decodedToken = decode_customer_token();

            $customer_id = $decodedToken->customer->_id;

            if(empty($data['freshchat_restore_id'])){
                
                return ['status'=>400, 'message'=>'Data empty'];
            
            }
            $freshchat_restore_id = $data['freshchat_restore_id'];
            
            $customer_update = Customer::where('_id', (int)$customer_id)->update(['freshchat_restore_id'=>$freshchat_restore_id]);

            $customer = Customer::find((int)$customer_id);

            $token = $this->createToken($customer);

            if($customer_update){
                return ['status'=>200, 'token'=>$token['token']];
            }

            return ['status'=>400];

        }catch(Exception $e){
            
            return ['status'=>500];
        
        }
    }

    public function postLoyaltyRegistration($customer, $voucher_categories_map){
    
        $checkins = $this->getCustomerCheckins($customer);
        $customer_milestones = $this->getCustomerMilestones($customer);
        $milestone_no = count($customer_milestones);
        $brand_milestones = Config::get('loyalty_constants');
        $post_register = Config::get('loyalty_screens.post_register');
        
        
        
        $brand_milestones = $this->utilities->getFinderMilestones($customer);
		
        $milestones = $brand_milestones['milestones'];
		$checkin_limit = $brand_milestones['checkin_limit'];
		
        $milestones_data = $this->utilities->getMilestoneSection($customer, $brand_milestones);
        $post_register['milestones']['data'] = $milestones_data['data'];
		
        $next_milestone_checkins = !empty($milestones[$milestone_no]['next_count']) ? $milestones[$milestone_no]['next_count'] : 225;
        
        $milestone_text = '';
        
        if(!empty($checkins)){
            $milestone_text = '(View)<br/><br/>';
        }else{
            $milestone_text = '<br/><br/>';
        }

        if(!empty($milestone_no)){
            $milestone_text = $milestone_text.'You are on milestone '.$milestone_no;
        }else{
            $milestone_text = $milestone_text.'Rush to your first milestone to earn rewards';
        }

        

        $milestone_next_count = $milestones_data['milestone_next_count'];
        $all_milestones_done = !empty($milestones_data['all_milestones_done']) ? true : false;
        
        
        $post_register['header']['text'] = strtr($post_register['header']['text'], ['$customer_name'=>$customer->name, '$check_ins'=>$checkins, '$milestone'=>$milestone_no, '$next_milestone_checkins'=>$next_milestone_checkins, '$milestone_text'=>$milestone_text]);
        $post_register['milestones']['subheader'] = strtr($post_register['milestones']['subheader'], ['$next_milestone_check_ins'=>$milestone_next_count-$checkins, '$next_milestone'=>$milestone_no+1]);

        if(!empty($all_milestones_done)){
            $post_register['milestones']['subheader'] = "You have completed all your milestones";
        }


        $post_register['milestones']['footer'] = strtr($post_register['milestones']['footer'], ['$last_date'=>date('d M Y', strtotime('+1 year',$customer['loyalty']['start_date']->sec))]);
        if($checkins){
            unset($post_register['past_check_in']['subheader']);
            $post_register['past_check_in']['header'] = Config::get('loyalty_screens.past_check_in_header_text');
            $post_register['past_check_in']['clickable'] = true;
        }
        $post_register['Terms']['url'] = $post_register['Terms']['url'].'?app=true&token='.Request::header('Authorization').'&otp_verified='.(!empty(Request::header('Mobile-Verified')) ? Request::header('Mobile-Verified'):'false');
        
        $post_register_rewards_data = [];
        $reward_open_index = null;
        foreach($milestones as $key => $milestone){
            if(!$milestone['milestone']){
                continue;
            }
            $post_reward_template = Config::get('loyalty_screens.post_register_rewards_data_outer_template');
            $post_reward_template['title'] = strtr($post_reward_template['title'], $milestone);
            
            $post_reward_template['_id'] = $key;
            // return $milestone_no;
            $claimed_vouchers = [];
            $milestone_claim_count = 1;
            if(!empty($voucher_categories_map[$milestone['milestone']])){
                
                $claimed_vouchers =  !empty($customer_milestones[$milestone['milestone']-1]['voucher']) ? $customer_milestones[$milestone['milestone']-1]['voucher'] : [];
                $claimed_voucher_categories = [];
                
                if(!empty($claimed_vouchers)){

                    foreach($claimed_vouchers as $key => $claimed_voucher){
                        $claimed_voucher = (array)$claimed_voucher;
                        
                        unset($claimed_voucher['flags']);
                        $post_reward_data_template = Config::get('loyalty_screens.post_register_rewards_data_inner_template');
                        $post_reward_data_template['logo'] = strtr($post_reward_data_template['logo'], $claimed_voucher);
                        $post_reward_data_template['_id'] = strtr($post_reward_data_template['_id'], $claimed_voucher);
                        $post_reward_data_template['terms'] = strtr($post_reward_data_template['terms'], $claimed_voucher);
                        $post_reward_data_template['claim_url'] = Config::get('app.url').'/claimexternalcoupon/'.$claimed_voucher['_id']."?milestone=".$milestone['milestone']."&index=".$key;
                        $post_reward_data_template['coupon_description'] = strtr($post_reward_data_template['coupon_description'], $claimed_voucher);
                        $post_reward_data_template['price'] = strtr($post_reward_data_template['price'], $claimed_voucher);
                        $post_reward_data_template['claim_enabled'] = true;
                        $post_reward_data_template['button_title'] = "View";

                        if(in_array($this->device_type, ['ios']) || in_array($this->device_type, ['android']) && $this->app_version >= 5.12){
                            unset($post_reward_data_template['claim_message']);
                        }

                        $post_reward_template['data'][] = $post_reward_data_template;

                        array_push($claimed_voucher_categories, $claimed_voucher);

                    }
                
                    $claimed_voucher_categories = array_column($claimed_voucher_categories, 'name');
                
                }

                $milestone_claim_count = !empty($milestone['vouchers_claimable']) ? $milestone['vouchers_claimable'] : 1;
                

                if(count($claimed_vouchers) < $milestone_claim_count){

                    foreach($voucher_categories_map[$milestone['milestone']] as $vc){

                        if(in_array($vc['name'], $claimed_voucher_categories)){
                            continue;
                        }
                        $vc = array_only($vc, ['image', '_id', 'terms', 'amount', 'description']);
                        $post_reward_data_template = Config::get('loyalty_screens.post_register_rewards_data_inner_template');
                        $post_reward_data_template['logo'] = strtr($post_reward_data_template['logo'], $vc);
                        $post_reward_data_template['_id'] = strtr($post_reward_data_template['_id'], $vc);
                        $post_reward_data_template['terms'] = strtr($post_reward_data_template['terms'], $vc);
                        $post_reward_data_template['claim_url'] = Config::get('app.url').'/claimexternalcoupon/'.$post_reward_data_template['_id'];
                        unset($vc['finder_ids']);
                        $post_reward_data_template['coupon_description'] = strtr($post_reward_data_template['coupon_description'], $vc);
						$post_reward_data_template['price'] = strtr($post_reward_data_template['price'], $vc);
						if($milestone_claim_count > 1){
							$post_reward_data_template['claim_message'] = "Are you sure you want to claim this reward?";
						}
                        if($milestone_no >= $milestone['milestone'] ){

                            $post_reward_data_template['claim_enabled'] = true;

                            if(empty($customer_milestones[$milestone['milestone']-1]['verified'])){

                                if(!empty($customer['loyalty']['receipt_under_verfication'])){
                                    $post_reward_data_template['block_message'] = Config::get('loyalty_screens.receipt_verification_message');
                                    $post_reward_data_template['block_msg'] = Config::get('loyalty_screens.receipt_verification_message');//for ios device v < 5.1.4
                                }else{
                                    $post_reward_data_template['receipt_message'] = Config::get('loyalty_screens.receipt_message');
                                }
                            
                            }else{

                                if(!empty($milestone['bookings']) && !empty($milestone['booking_amount'])){
                                    // return $milestone;
                                    // return $customer->_id;
                                    $orders_aggregate = Order::raw(function($collection) use ($customer){

										$aggregate = [];
                                        $match = [
                                            '$match'=>[
                                                '$and'=>[
                                                    [
														'customer_id'=>$customer->_id,
														'status'=>'1'
                                                        // 'type'=>['$in'=>['workout-session', 'membershi']],
                                                        // 'going_status_txt'=>['$nin'=>['cancel']],
                                                        // 'payment_done'=>['$ne'=>false]
                                                    ]
                                                ]
                                            ]
										];
										
										$aggregate[] = $match;

                                        // $lookup = [
                                        //     '$lookup'=>[
                                        //         'from'=>'orders',
                                        //         'localField'=>'order_id',
                                        //         'foreignField'=>'_id',
                                        //         'as'=>'order'
                                        //     ]
										// ];
										// $aggregate[] = $lookup;

                                        // $project = [
                                        //     '$project'=>[
                                        //         'order'=>['$arrayElemAt'=>['$order', 0]]
                                        //     ]
										// ];
										
										// $aggregate[] = $project;

                                        $group = [
                                            '$group'=>[
                                                '_id'=>null,
                                                // 'bookings'=>['$sum'=>1],
                                                'booking_amount'=>['$sum'=>'$amount_customer']
                                            ]
										];
										$aggregate[] = $group;

                                        return $collection->aggregate($aggregate);
                                    
                                    });  

									$orders = $orders_aggregate['result'];

                                    if(!(!empty($orders[0]) && !empty($orders[0]['booking_amount']) && $orders[0]['booking_amount'] >=$milestone['booking_amount'])){
                                        $post_reward_data_template['block_message'] = strtr(Config::get('loyalty_screens.bookings_block_message'), $milestone);
                                    }
                                
                                }

                            }

                            !isset($reward_open_index) ? $reward_open_index = $milestone['milestone'] - 1 : null;

                        }else{
                            $post_reward_data_template['claim_enabled'] = false;
                        } 
                        // return $post_reward_data_template;
                        $post_reward_template['data'][] = $post_reward_data_template;
                        // return $milestone_no;
                    }
                }

            }

            $post_reward_template['description'] = ($milestone_claim_count <= count($claimed_vouchers) ) ? "Reward(s) Claimed" : ("Select ".($milestone_claim_count - count($claimed_vouchers) )." Reward(s)");
            $post_register_rewards_data[] = $post_reward_template;
            
        }
        //return $post_register_rewards_data;
        !isset($reward_open_index) ? $reward_open_index = ($milestone_no < count($milestones) ? $milestone_no : $milestone_no-1) : null;
        $post_register['rewards']['open_index'] = $reward_open_index;

        $post_register['rewards']['data'] = $post_register_rewards_data;


		Order::$withoutAppends = true;
		$order = Order::active()->where('customer_id', $customer['_id'])->where('type', 'memberships')->orderBy('_id', 'desc')->first();
		if(!empty($order)){
			$loyaltyAppropriation = $this->utilities->getLoyaltyAppropriationConsentMsg($customer['_id'], $order['_id']);

			$post_register['loyalty_success_msg'] = $loyaltyAppropriation;
		}

        return ['post_register'=>$post_register];
    }

    public function preLoyaltyRegistration($voucher_categories_map){


        // $pre_register = Cache::tags('loyalty')->has('pre_register');

        // if($pre_register){
        //     Log::info("returning cached");
        //     return $pre_register = Cache::tags('loyalty')->get('pre_register');
        //     return ['pre_register'=>$pre_register];
        // }
        
        $pre_register = Config::get('loyalty_screens.pre_register');
	
        Log::info("preLoyaltyRegistration");
        Log::info(Request::header('Mobile-Verified'));

        


        $pre_register_check_ins_data = [];
        $milestones = Config::get('loyalty_constants.milestones');
        
        foreach($milestones as $milestone){
            if(!$milestone['milestone'] || empty($voucher_categories_map[$milestone['milestone']])){
                continue;
            }
            $pre_reward_template = Config::get('loyalty_screens.pre_register_check_ins_data_template');
            $pre_reward_template['title'] = strtr($pre_reward_template['title'], $milestone);
            $pre_reward_template['milestone'] = strtr($pre_reward_template['milestone'], $milestone);
            $pre_reward_template['amount'] = '₹'.strtr($pre_reward_template['amount'], $milestone);
            if(!empty($voucher_categories_map[$milestone['milestone']]['max_amount'])){
                $pre_reward_template['amount'] = '₹'.$voucher_categories_map[$milestone['milestone']]['amount'];
            }
            $pre_reward_template['count'] = intval(strtr($pre_reward_template['count'], $milestone));
            $pre_reward_template['images'] = array_column($voucher_categories_map[$milestone['milestone']], 'image');
            $pre_register_check_ins_data[] = $pre_reward_template;
        }

        $pre_register['check_ins']['data'] = $pre_register_check_ins_data;
        $pre_register['Terms']['url'] = $pre_register['Terms']['url'].'?app=true&token='.Request::header('Authorization').'&otp_verified='.(!empty(Request::header('Mobile-Verified')) ? Request::header('Mobile-Verified'):'false');

        if(!empty($this->device_type) && in_array($this->device_type, ['android', 'ios'])){
            $pre_register['header']['url'] = $pre_register['footer']['url'] = $this->utilities->getLoyaltyRegisterUrl();
        }
        
        // Cache::tags('loyalty')->put('pre_register', $pre_register, Config::get('cache.cache_time'));

        return ['pre_register'=>$pre_register];
    }

    public function voucherCommunication($job,$data){

        $job->delete();

        try{
            $this->customermailer->externalVoucher($data);
        }catch(Exception $e){
            Log::info(['status'=>400,'message'=>$e->getMessage().' - Line :'.$e->getLine().' - Code :'.$e->getCode().' - File :'.$e->getFile()]);            
        }
    }

    public function createtokenbycustomerid($customer_email){
        
        $customer = Customer::where('email', strtolower($customer_email))->first();
        return $this->createToken($customer);
    }

    public function getSessionPacks($offset = 0, $limit = 10, $active = false, $customer_id = null){
    
        $jwt_token = Request::header('Authorization');

		if(!empty($jwt_token)){
			
            $decoded = decode_customer_token($jwt_token);
			$customer_id = $decoded->customer->_id;
        
        }else{
            return ['status'=>400, 'message'=>'No customer found'];
        }
        $offset 			=	intval($offset);
        $limit 				=	intval($limit);

        $orders 			=  	[];
        Finder::$withoutAppends = true;
        Service::$withoutAppends = true;
        
        $orders = Order::active()
                ->where('customer_id', $customer_id)
                ->where(function($query){
                    $query
                    ->orWhere('extended_validity', true)
                    ->orWhere('studio_extended_validity', true);
                })
                
                ->with(['finder'=>function($query){
                    $query->select('slug');
                }])
                ->with(['service'=>function($query){
                    $query->select('slug');
                }])
                ->skip($offset)
                ->take($limit)
                ->orderBy('_id', 'desc');

        if(!empty($active)){

            $orders->where('start_date', '<=', new DateTime())
                    ->where('end_date', '>=', new DateTime());

        }

        $orders =  $orders->get(['service_name', 'finder_name', 'sessions_left', 'no_of_sessions','start_date', 'end_date', 'finder_address','finder_id','service_id','finder_location','customer_id', 'ratecard_flags','studio_extended_validity', 'studio_sessions', 'studio_membership_duration']);

        $orders = $this->formatSessionPackList($orders);

        return ['status'=>200, 'data'=>$orders];          
		
    }

    public function formatSessionPackList($orders){
        foreach($orders as &$order){
           
            $order = $this->formatSessionPack($order);
            
        }
        return $orders;
    }

    public function formatSessionPack($order){
        
        $order['active'] = true;
        if((!empty($order['ratecard_flags']['unlimited_validity']) || strtotime($order['end_date']) > time()) && !empty($order['sessions_left'])){
            $order['button_title'] = 'Book your next Session';
            $order['button_type'] = 'book';

        }else{
            $order['active'] = false;
            $order['button_title'] = 'Renew Pack';
            $order['button_type'] = 'renew';
        }

        if(!empty($order['studio_extended_validity'])){
        
            if(
                time() < $order['studio_membership_duration']['end_date']->sec 
                // || 
                // $order['studio_sessions']['cancelled'] >= $order['studio_sessions']['total_cancel_allowed'] 
                || 
                time() > $order['studio_membership_duration']['end_date_extended']->sec
            ){
                unset($order['button_title']);
                unset($order['button_type']);
                if(requestFtomApp()){
                    $order['active'] = false;
                    $order['button_title'] = 'Renew Pack';
                    $order['button_type'] = 'renew';
                }
            }else{
                
                if(requestFtomApp()){
                    $order['button_title'] = 'Book your next Session';
                    $order['button_type'] = 'book';
                    
                }else{
                    unset($order['button_title']);
                    unset($order['button_type']);
                }
            }
        }

        $order['start_date'] = strtotime($order['start_date']);
        $order['starting_date'] = date('d M, Y', strtotime($order['start_date']));
        $order['starting_text'] = "Starts from: ";
        $order['valid_text'] = 'Valid till: ';
        $order['valid_date'] = date('d M, Y', strtotime($order['end_date']));
        if(!empty($order['ratecard_flags']['unlimited_validity'])){
            $order['valid_date'] = "Unlimited validity";
        }
        $order['subscription_text'] = "Subscription code: ";
        $order['subscription_code'] = strval($order['_id']);
        $order['sessions_left'] = strval($order['sessions_left']);
		$order['title'] = $order['finder_name'].' - '.$order['service_name'];
        $order['detail_text'] = "VIEW DETAILS";
        $order['total_session_text'] = $order['no_of_sessions']." Session pack";
        $order['left_text'] = "left";
        if(!empty($order['studio_extended_validity'])){
            $order['left_text'] = "booked";
            $order['sessions_left'] =  $order['studio_sessions']['total'];
            $order['total_session_text'] = $order['studio_sessions']['total']." Session pack";
            $extended_count = Order::active()->where('studio_extended_validity_order_id', $order['_id'])->where('studio_extended_session', true)->count();
            $order['finder_address'] = ($order['studio_sessions']['cancelled']-$extended_count)."/".$order['studio_sessions']['total_cancel_allowed']." sessions can be extended for free after ".date('d-m-Y' ,$order['studio_membership_duration']['end_date']->sec);
        }
        $order['session_active'] = "SESSION PACK ACTIVE";
        // if(strtotime($order['start_date']) >= time()){
        //     $order['before_start_message'] = "Your session pack start from ".date('d M, Y', strtotime($order['start_date'])).". Session pack will not be applied to bookings before the start date";
        // }
        if(!empty($order['finder']['slug'])){
            $order['finder_slug'] = $order['finder']['slug'];
        }
        if(!empty($order['service']['slug'])){
            $order['service_slug'] = $order['service']['slug'];
        }



        return $order;

    }

    public function sessionPackDetail($id){
        Service::$withoutAppends = true;
		Finder::$withoutAppends = true;
        $order = Order::with(['finder'=>function($query){
                    $query->select('slug');
                }])
                ->with(['service'=>function($query){
                    $query->select('slug');
                }])
                ->find($id, ['service_name', 'finder_name', 'sessions_left', 'no_of_sessions','start_date', 'end_date', 'finder_address','finder_id','service_id','finder_location','customer_id', 'ratecard_flags']);


        return $this->formatSessionPack($order);
    }

    /**
     * @param $customer
     * @return int
     */
    public function getCustomerCheckins($customer)
    {
        return !empty($customer->loyalty['checkins']) ? $customer->loyalty['checkins'] : 0;
    }

    /**
     * @param $customer
     * @return array
     */
    public function getCustomerMilestones($customer)
    {
        return !empty($customer->loyalty['milestones']) ? $customer->loyalty['milestones'] : [];
    }

	public function prepareLoyaltyData($order){
		Log::info('----- Entered prepareLoyaltyData -----');
		if(!empty($order)){
			$finder = Finder::active()->where('_id', $order['finder_id'])->first();
			$loyalty = [
				'order_id' => $order['_id'],
				'start_date' => new MongoDate(strtotime('midnight', strtotime($order['start_date']))),
				'start_date_time' => new MongoDate(strtotime($order['start_date'])),
				'finder_id' => $order['finder_id'],
				'end_date' => new MongoDate(strtotime('+1 year', strtotime($order['start_date']))),
				'type' => $order['type'],
				'checkins' => 0,
				'created_at' => new MongoDate()
			];
			
			/*if(!empty($finder['brand_id']) && !empty($finder['city_id']) && in_array($finder['brand_id'], Config::get('app.brand_loyalty')) && !in_array($finder['_id'], Config::get('app.brand_finder_without_loyalty'))){
				$duration = !empty($order['duration_day']) ? $order['duration_day'] : (!empty($order['order_duration_day']) ? $order['order_duration_day'] : 0);
				$duration = $duration > 180 ? 360 : $duration;
				$loyalty['brand_loyalty'] = $finder['brand_id'];
				$loyalty['brand_loyalty_duration'] = $duration;
				$loyalty['brand_loyalty_city'] = $order['city_id'];

				if($loyalty['brand_loyalty'] == 135){
					if($loyalty['brand_loyalty_duration'] == 180){
						$loyalty['brand_version'] = 1;
					}else{
						$loyalty['brand_version'] = 2;
					}
				}else{
					$loyalty['brand_version'] = 1;
				}
			}*/

			$brand_loyalty_data = $this->utilities->buildBrandLoyaltyInfoFromOrder($finder, $order);
			if(!empty($brand_loyalty_data)){
				$loyalty['brand_loyalty'] = $brand_loyalty_data['brand_loyalty'];
				$loyalty['brand_loyalty_duration'] = $brand_loyalty_data['brand_loyalty_duration'];
				$loyalty['brand_loyalty_city'] = $brand_loyalty_data['brand_loyalty_city'];
				$loyalty['brand_version'] = $brand_loyalty_data['brand_version'];
			}
			else if(!empty($order['finder_flags']['reward_type'])){
				$loyalty['reward_type'] = $order['finder_flags']['reward_type'];
			}
			else{
				$loyalty['reward_type'] = 2;
			}
			if(!empty($loyalty['reward_type']) && !empty($order['finder_flags']['cashback_type'])){
				$loyalty['cashback_type'] = $order['finder_flags']['cashback_type'];
			}
			return $loyalty;
		}
		return null;
	}


	public function loyaltyAppropriation(){
		Log::info('----- Entered loyaltyAppropriation -----');
		$data = Input::all();
		Log::info('loyaltyAppropriation data: ', [$data]);
		$resp = ['status' => 500, 'messsage' => 'Something went wrong'];
		$order = null;
		try{
			if((empty($data) || empty($data['order_id'])) && !empty($data['type']) && $data['type']=='profile'){
				$order = Order::active()->where('customer_id', intval($data['customer_id']))->where('type','memberships')->orderBy('_id', 'desc')->first();
				$data['order_id'] = $order['_id'];
			}
			if(!empty($data)){
				if(!empty($data['order_id'])){
					$order_id = intval($data['order_id']);
					if(empty($order)){
						$order = Order::active()->where('_id', $order_id)->first();
					}
					if(!empty($order)){
						$customer_id = intval($order['customer_id']);
						$cust = Customer::active()->where('_id', $customer_id)->first();
							
					
						$reason = 'loyalty_appropriation';

						$oldLoyalty = $cust['loyalty'];

						Log::info('ready to prepareLoyaltyData.....');
						$newLoyalty = $this->prepareLoyaltyData($order);
						if(!empty($newLoyalty)){
							$archiveData = ['loyalty' => $oldLoyalty];
							
							$this->utilities->archiveCustomerData($cust['_id'], $archiveData, $reason);

							$cust['loyalty'] = $newLoyalty;
							$cust->update();

							$this->utilities->deactivateCheckins($cust['_id'], $reason);

							$resp = ['status'=>200, 'message'=>'Successfully appropriated the loyalty of the customer'];
						}
						else {
							$resp = ['status'=>400, 'message'=>'order details are missing'];
						}
					}
					else {
						$resp = ['status'=>400, 'message'=>'order is missing'];
					}
				}
				else {
					$resp = ['status'=>400, 'message'=>'order id is missing'];
				}
			}
			else {
				$resp = ['status'=>400, 'message'=>'order id and customer id are missing'];
			}
		} catch (Exception $ex) {
			Log::info('Exception in loyaltyAppropriation: ', [$ex]);
		}
		Log::info('returning from loyaltyAppropriation with status: ', $resp);
		return Response::json($resp, $resp['status']);
	}

	public function distanceCalculationOfCheckinsCheckouts($coordinates, $vendorCoordinates){
		$p = 0.017453292519943295;    // Math.PI / 180

		$dLat = ($vendorCoordinates['lat'] - $coordinates['lat']) * $p;
		$dLon = ($vendorCoordinates['lon'] - $coordinates['lon']) * $p;
		$a = sin($dLat/2) * sin($dLat/2) + cos($coordinates['lat'] * $p) * cos($vendorCoordinates['lat'] * $p) * sin($dLon/2) * sin($dLon/2);
		$c = 2 * atan2(sqrt($a), sqrt(1-$a)); 
		$d = 6371 * $c; // Distance in km
		
		Log::info('distance in kmsss', [$d]); 
  		return $d *1000;
	}

	public function checkForOperationalDayAndTime($finder_id){
		//Log::info('finder Service', [$finder_id]);
		Service::$withoutAppends = true;
		$finder_service = Service::where('finder_id', $finder_id)->where('status', "1")->select('trialschedules')->get();
		//Log::info('finder Service', [$finder_service['trialschedules']]);
		$todayDate= strtotime("now");
		$today = date('D', $todayDate);
		$minutes = date('i', $todayDate);
		$hour= date('H', $todayDate);
		Log::info('today date', [$todayDate, $today, $minutes, $hour]);

		$status= false;

		if(count($finder_service)>0)
		{	
			foreach($finder_service as $key0=> $value0)
			{
				foreach($value0['trialschedules'] as $key=> $value)
				{
					if(strtolower($today) == strtolower(substr($value['weekday'], 0,3)))
					{
						foreach($value['slots'] as $key1=> $value1)
						{
							if($hour >=$value1['start_time_24_hour_format'] && $hour < $value1['end_time_24_hour_format'])
							{	
								$status= true;
								break;
							}
						}
					}
					if($status)
					{
						break;
					}
				}
			}
		}
		else
		{
			return ['status'=> false, "message"=>"No Service Available."];
		}

		if($status)
		{
			return ["status"=> true];
		}
		else
		{
			return ["status"=> false, "message"=>"No Slots availabe right now. try later."];
		}
	}

	public function checkForCheckinFromDevice($finder_id, $device_token, $finder, $customer_id){

		$checkins = $this->checkInsList($customer_id, $device_token);

		$res = ["status"=> true];

		Log::info('chekcins:::::::::::;', [$checkins, $customer_id]);

		if(count($checkins)>0)
		{
			$d = strtotime($checkins['created_at']);	
			$cd = strtotime(date("Y-m-d H:i:s"));
			$difference = $cd -$d;
			Log::info('differece:::::::::::', [$difference]);

			if($checkins['device_token']!= $device_token){
				$return = $this->checkinCheckoutSuccessMsg($finder);
				$return['header'] = "Use Checkin device for Successfull Checkout.";
				return $return;
			}
			else if($checkins['customer_id'] != $customer_id){
				$return = $this->checkinCheckoutSuccessMsg($finder);
				$return['header'] = "Allready checkin done by other user using this device.";
				return $return;
			}

			if($checkins['checkout_status'])
			{
				//allreday checkdout
				//$this->checkinInitiate($finder_id, $finder, $customer_id);
				//finders
				$finder_title = Finder::where('_id', $checkins['finder_id'])->lists('title');
				$return = $this->checkinCheckoutSuccessMsg(['title'=> $finder_title[0]]);
				$return['header'] = 'CHECK-OUT ALREADY MARKED FOR TODAY';
				return $return;
				//return $res = ["status"=>false, "message"=>"You have already checked-out for the day."];
			}
			else if($difference< 45 * 60)
			{
				//session is not complitated
				$return = $this->checkinCheckoutSuccessMsg($finder);
				$return['header'] = "Session is not completed.";
				$return['sub_header_2'] = "Seems you have not completed your workout at ".$finder['title'].". The check-out time window is 45 minutes to 2 hours from your check-in time. Please make sure you check-out in the same window in order to get a successful check-in to level up on your workout milestone.";
				return $return;
				//return $res = ["status"=>false, "message"=>"session is not completed."];
			}
			else if(($difference > 45 * 60) &&($difference <= 120 * 60))
			{
				//checking out ----
				return $this->checkoutInitiate($checkins['_id'], $finder, $finder_id, $customer_id, $checkins);
				//$res = ["status"=>true, "message"=>" checking- out for the day."];
			}
			else if($difference > 120 * 60)
			{
				//times up not accaptable
				$return  = $this->checkinCheckoutSuccessMsg($finder);
				$return['header'] = "Times Up to checkout for the day.";
				$return['sub_header_2'] = "Sorry you have lapsed the check-out time window for the day. (45 minutes to 2 hours from your check-in time) . This check-in will not be marked into your profile.\n Continue with your workouts and achieve the milestones.";
				return $return;
				//return $res = ["status"=>false, "message"=>"Times Up to checkout for the day."];
			}
		}
		else
		{
			//just checkinss ->>>>>> start checkoins
			return $this->checkinInitiate($finder_id, $finder, $customer_id);
		}

		return $res;
	}

	public function markCheckin($finder_id=null){

		$input = Input::all();

		$rules = [
			'lat' => 'required',
			'lon' => 'required'
		];

		$validator = Validator::make($input,$rules);

		if ($validator->fails())
		{
			return Response::json(array('status' => 400,'message' => 'Not Able to find Your Location.'),$this->error_status);
		}

		if(empty($finder_id))
		{
			return Response::json(array('status' => 400,'message' => 'Vendor is Empty.'),'Vendor is Empty');
		}
		
		$finder_id = (int) $finder_id;
		$jwt_token = Request::header('Authorization');	
		$decoded = decode_customer_token($jwt_token);
		$customer_id = $decoded->customer->_id;
		$customer_geo = [];
		$finder_geo = [];

		//Finder::$withoutAppends = true;
		$finder = Finder::find($finder_id, ['title', 'lat', 'lon']);

		//Log::info('finder ddetails::::::::', [$finder_id,$finder]);
		if(!empty(\Input::get('lat')) && !empty(\Input::get('lon'))){
			$customer_geo['lat'] = floatval(\Input::get('lat'));
			$customer_geo['lon'] = floatval(\Input::get('lon'));
		}

		if(isset($finder['lat']) && isset($finder['lon'])){
			$finder_geo['lat'] = $finder['lat'];
			$finder_geo['lon'] = $finder['lon'];
		}

		//Log::info('geo coordinates of :::::::::::;', [$customer_geo, $finder_geo]); // need to update distance limit by 500 metere
		$distanceStatus  = $this->distanceCalculationOfCheckinsCheckouts($customer_geo, $finder_geo) <= 2000 ? true : false;
		//Log::info('distance status', [$distanceStatus]);
		if($distanceStatus){
			$oprtionalDays = $this->checkForOperationalDayAndTime($finder_id);
			if($oprtionalDays['status']){ // need to remove ! 
				//Log::info('device ids:::::::::', [$this->device_id]);
				return $this->checkForCheckinFromDevice($finder_id, $this->device_token, $finder, $customer_id);
			}
			else{
				// return for now you are checking in for non operational day or time
				$return = $this->checkinCheckoutFailureMsg('Sorry you are checking at non operational Time.');
				return $return;
				//return $oprtionalDays;
			}
		}
		else{
			// return for use high accurary
			$return  = $this->checkinCheckoutFailureMsg("Please mark your check in by visiting ".$finder['title']);
			return $return;
			//return ["status"=> false, "message"=>"Put your device in high accuracy."];
		}
		
	}

	public function checkoutInitiate($id, $finder, $finder_id, $customer_id, $checkout){
		Log::info('checkout initiate input::::::::::::::::', [Input::All()]);
		//$checkout = Checkin::where('_id', $id)->first();
			$checkout->checkout_status=true;
		try{
			$checkout->update();

			$finder_id = intval($finder_id);
			$session_pack = !empty($_GET['session_pack']) ? $_GET['session_pack'] : null;
			$finder_id = intval($finder_id);

			$customer = Customer::find($customer_id);
			$type = !empty($checkout['type'])? $checkout['type']: null;//!empty($_GET['type']) ? $_GET['type'] : null;
			$customer_update = \Customer::where('_id', $customer_id)->increment('loyalty.checkins');	
			//Log::info('customer_updates',[$customer_update]);
			if($customer_update)
			{
				if(!empty($type) && $type == 'workout-session'){
					$loyalty = $customer->loyalty;
					$finder_ws_sessions = !empty($loyalty['workout_sessions'][(string)$finder_id]) ? $loyalty['workout_sessions'][(string)$finder_id] : 0;
					
					if($finder_ws_sessions >= 5){
						$type = 'membership';
						$update_finder_membership = true;
					}else{
						$update_finder_ws_sessions = true;
					}
				}
				if(!empty($update_finder_ws_sessions)){
					// $loyalty['workout_sessions'][$finder_id] = $finder_ws_sessions + 1;
					// $customer->update(['loyalty'=>$loyalty]);
					Customer::where('_id', $customer_id)->increment('loyalty.workout_sessions.'.$finder_id);
				}elseif(!empty($update_finder_membership)){
					if(empty($loyalty['memberships']) || !in_array($finder_id, $loyalty['memberships'])){
						array_push($loyalty['memberships'], $finder_id);
						$customer->update(['loyalty'=>$loyalty]);
					}
				}
			}
			$return =$this->checkinCheckoutSuccessMsg($finder);
			$return['header'] = "CHECK-OUT SUCCESSFULL";
			$return['sub_header_2'] = "Hope you had a great workout at ".$finder['title'].". This check-in is successfully marked into your workout journey. Continue with your workouts and achieve the milestones.";
			// $return =  [
			// 	'header'=>'CHECK-OUT SUCCESSFUL!',
			// 	'sub_header_2'=> "Enjoy your workout at ".$finder['title'].".\n Make sure you continue with your workouts and achieve the milestones quicker",
			// 	'milestones'=>$this->utilities->getMilestoneSection(),
			// 	'image'=>'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png'
			// ];
			return $return;
		}catch(Exception $err){
			Log::info("error occured::::::::::::", [$err]);
			return ["status"=>false, "message"=>"Please Try again. Something went wrong."];
		}
		
	}

	public function checkinCheckoutSuccessMsg($finder){
		$return =  [
			'header'=>'CHECK-IN SUCCESSFUL!',
			'sub_header_2'=> "Enjoy your workout at ".$finder['title'].".\n Make sure you continue with your workouts and achieve the milestones quicker",
			'milestones'=>$this->utilities->getMilestoneSection(),
			'image'=>'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png',
			// 'fitsquad'=>$this->utilities->getLoyaltyRegHeader($customer)
		];
		return $return;
	}

	public function checkinCheckoutFailureMsg($reason=null) {
		$return =  [
			'header'=>'CHECK-IN FAILED!',
			'sub_header_2'=> (!empty($reason))?$reason.".":"Unable to mark your checkin.",
			'image'=>'https://b.fitn.in/paypersession/sad-face-icon.png'
		];
		return $return;
	}

	public function checkInsList($customer_id, $device_token, $get_qr_loyalty_screen=null, $finderarr=null){
		$date = date('Y-m-d', time());//return $customer_id;

		$checkins= Checkin:://where('device_id', $device_id)//->orWhere('customer_id', $customer_id)
		where(function($query) use($customer_id, $device_token){$query->where('customer_id',$customer_id)->orWhere('device_token',$device_token);})
		->where('date', '=', new MongoDate(strtotime($date)))
		->select('customer_id', 'created_at', 'status', 'device_token', 'checkout_status', 'finder_id', 'type', 'sub-type')
		->first();

		if(count($checkins) && !empty($get_qr_loyalty_screen)){
			$d = strtotime($checkins['created_at']);	
			$cd = strtotime(date("Y-m-d H:i:s"));
			$difference = $cd -$d;
			if(($difference >= 45 * 60) && $checkins['checkout_status']){
				return array(
					"status" => false
				);
			}
			else if($difference < 180 * 60){
				return  array(
					'status' => true,
					'logo' => Config::get('loyalty_constants.fitsquad_logo'),
					'header1' => 'CHECK-OUT FOR YOUR WORKOUT',
					'header3' => 'Hope you had a great workout today at '.$finderarr['title'].'. Hit the check-out button below to get the successful check-in and level up to reach your milestone.',
					'button_text' => 'CHECK-OUT',
					'url' => Config::get('app.url').'/markcheckin/'.$finderarr['_id'],
					'type' => 'checkin',
				);
			}

			return array(
				"status" => false
			);
		}
		else{
			if( !empty($get_qr_loyalty_screen)){
				return array(
					"status" => false
				);
			}
			return $checkins;
		}
	}

}
