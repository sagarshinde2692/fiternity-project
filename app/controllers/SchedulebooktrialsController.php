<?PHP

/**
 * ControllerName : SchedulebooktrialsController.
 * Maintains a list of functions used for SchedulebooktrialsController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Sms\FinderSms as FinderSms;
use App\Notification\CustomerNotification as CustomerNotification;
use App\Services\Fitnessforce as Fitnessforce;
use Carbon\Carbon;
use App\Services\Sidekiq as Sidekiq;
use App\Services\OzontelOutboundCall as OzontelOutboundCall;


class SchedulebooktrialsController extends \BaseController {

	protected $customermailer;
	protected $findermailer;
	protected $customersms;
	protected $findersms;
	protected $customernotification;
	protected $fitnessforce;
	protected $worker;
	protected $sidekiq;
	protected $ozontelOutboundCall;

	public function __construct(CustomerMailer $customermailer, FinderMailer $findermailer, CustomerSms $customersms, FinderSms $findersms, CustomerNotification $customernotification, Fitnessforce $fitnessforce,Sidekiq $sidekiq,OzontelOutboundCall $ozontelOutboundCall) {
		//parent::__construct();	
		date_default_timezone_set("Asia/Kolkata");
		$this->customermailer			=	$customermailer;
		$this->findermailer				=	$findermailer;
		$this->customersms 				=	$customersms;
		$this->findersms 				=	$findersms;
		$this->customernotification 	=	$customernotification;
		$this->fitnessforce 			=	$fitnessforce;
		$this->sidekiq 	=	$sidekiq;
		$this->ozontelOutboundCall 	=	$ozontelOutboundCall;
	}

	/**
	 * Display the ScheduleBookTrial.
	 *
	 * @param  int  $finderid
	 * @param  date  $date(dd-mm-yyyy)
	 * @return Response
	 */

	public function getScheduleBookTrial($finderid,$date = null){

		//$dobj = new DateTime;print_r($dobj);
		$currentDateTime 		=	\Carbon\Carbon::now();
		$finderid 				= 	(int) $finderid;
		$date 					=  	($date == null) ? Carbon::now() : $date;
		$timestamp 				= 	strtotime($date);
		$weekday 				= 	strtolower(date( "l", $timestamp));

		// echo "$date  --- $timestamp -- $weekday";exit;
		//finder schedule trials
		$items = Schedulebooktrial::where('finder_id', '=', $finderid)->where('weekday', '=', $weekday)->get()->toArray();
		if(!$items){
			return $this->responseNotFound('Schedule does not exist');
		}

		$scheduletrials = array();
		foreach ($items as $item) {
			$price = (isset($item['price'])) ? $item['price'] : 0;
			$trial = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'price' => $price, 'weekday' =>  $item['weekday']);
			$slots = array();
			foreach ($item['slots'] as $slot) {
				$booktrialslotcnt = Booktrial::where('finder_id', '=', $finderid)
					->where('service_name', '=', $item['name'])
					->where('schedule_date', '=', new DateTime($date) )
					->where('schedule_slot', '=', $slot['slot_time'])
					->count();
				// var_dump($booktrialslotcnt);

				$slot_status 		= 	($slot['limit'] > $booktrialslotcnt) ? "available" : "full";
				array_set($slot, 'booked', $booktrialslotcnt);
				array_set($slot, 'status', $slot_status);

				$scheduleDateTime 				=	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']))->subMinutes(1);
				// $oneHourDiffInMin 			= 	$currentDateTime->diffInMinutes($delayReminderTimeBefore1Hour, false);  
				$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
				array_set($slot, 'passed', $slot_datetime_pass_status);

				//echo "<br>finderid : $finderid  --- schedule_date : $date servicename : $item[name] -- slot_time : $slot[slot_time] --  booktrialslotcnt : $booktrialslotcnt";
				array_push($slots, $slot);
			}
			$trial['slots'] = $slots;
			array_push($scheduletrials, $trial);
		}
		return $scheduletrials;
	}


	/**
	 * Display the TrialSchedule.
	 *
	 * @param  int  $finderid
	 * @param  date  $date(dd-mm-yyyy)
	 * @return Response
	 */

	public function getTrialSchedule($finderid,$date = null){
		// $dobj = new DateTime;print_r($dobj);
		$currentDateTime 		=	\Carbon\Carbon::now();
		$finderid 				= 	(int) $finderid;
		$date 					=  	($date == null) ? Carbon::now() : $date;
		$timestamp 				= 	strtotime($date);
		$weekday 				= 	strtolower(date( "l", $timestamp));
		$items = Service::where('finder_id', '=', $finderid)->get(array('_id','name','finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();
		if(!$items){
			return $this->responseNotFound('TrialSchedule does not exist');
		}
		$scheduleservices = array();
		foreach ($items as $k => $item) {
			$weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
				if($value['weekday'] == $weekday){
					return $value;
				}
			}));
			//slots exists
			if(count($weekdayslots['slots']) > 0){
				// echo "<br> count -- ".count($weekdayslots['slots']);
				$service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday);
				$slots = array();
				foreach ($weekdayslots['slots'] as $slot) {
					$totalbookcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
					$goingcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
					$cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
					$slot_status 		= 	($slot['limit'] > $goingcnt) ? "available" : "full";
					array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
					array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
					array_set($slot, 'totalbookcnt', $totalbookcnt);
					array_set($slot, 'goingcnt', $goingcnt);
					array_set($slot, 'cancelcnt', $cancelcnt);
					array_set($slot, 'status', $slot_status);
					$scheduleDateTime 				=	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']));
					$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
					array_set($slot, 'passed', $slot_datetime_pass_status);
					array_push($slots, $slot);
				}
				$service['slots'] = $slots;
				$service['trialschedules']['slots'] = $slots;
				array_push($scheduleservices, $service);
			}
		}
		return $scheduleservices;
	}

	
	public function getTrialScheduleIfDontSoltsAlso($finderid,$date = null){

		// $dobj = new DateTime;print_r($dobj);

		$currentDateTime 		=	\Carbon\Carbon::now();
		$finderid 				= 	(int) $finderid;
		$date 					=  	($date == null) ? Carbon::now() : $date;
		$timestamp 				= 	strtotime($date);
		$weekday 				= 	strtolower(date( "l", $timestamp));

		$items 					= 	Service::where('finder_id', '=', $finderid)->get(array('_id','name','finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();
		if(!$items){
			return $this->responseNotFound('TrialSchedule does not exist');
		}

		$scheduleservices = array();
		foreach ($items as $k => $item) {
			$weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
				if($value['weekday'] == $weekday){
					return $value;
				}
			}));


			// echo "<br> count -- ".count($weekdayslots['slots']);
			$service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday);

			$slots = array();
			//slots exists
			if(count($weekdayslots['slots']) > 0){
				foreach ($weekdayslots['slots'] as $slot) {
					$totalbookcnt 		= 	Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
					$goingcnt 			= 	Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
					$cancelcnt 			= 	Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
					$slot_status 		= 	($slot['limit'] > $goingcnt) ? "available" : "full";
					array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
					array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
					array_set($slot, 'totalbookcnt', $totalbookcnt);
					array_set($slot, 'goingcnt', $goingcnt);
					array_set($slot, 'cancelcnt', $cancelcnt);
					array_set($slot, 'status', $slot_status);
					$scheduleDateTime 				=	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']));
					$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
					array_set($slot, 'passed', $slot_datetime_pass_status);
					array_push($slots, $slot);
				}
			}

			$service['slots'] 					=	$slots;
			$service['trialschedules']['slots'] =	$slots;
			array_push($scheduleservices, $service);

		}

		return $scheduleservices;
	}

	/**
	 * Display the WorkoutSession Schedule.
	 *
	 * @param  int  $finderid
	 * @param  date  $date(dd-mm-yyyy)
	 * @return Response
	 */

	public function getWorkoutSessionSchedule($finderid,$date = null){
		// $dobj = new DateTime;print_r($dobj);

		$currentDateTime 		=	\Carbon\Carbon::now();
		$finderid 				= 	(int) $finderid;
		$date 					=  	($date == null) ? Carbon::now() : $date;
		$timestamp 				= 	strtotime($date);
		$weekday 				= 	strtolower(date( "l", $timestamp));

		$items = Service::where('finder_id', '=', $finderid)->get(array('_id','name','finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();
		if(!$items){
			return $this->responseNotFound('WorkoutSession Schedule does not exist');
		}

		$scheduleservices = array();
		foreach ($items as $k => $item) {
			$weekdayslots = head(array_where($item['workoutsessionschedules'], function($key, $value) use ($weekday){
				if($value['weekday'] == $weekday){
					return $value;
				}
			}));

			//slots exists
			if(count($weekdayslots['slots']) > 0){
				// echo "<br> count -- ".count($weekdayslots['slots']);
				$service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday);
				$slots = array();

				foreach ($weekdayslots['slots'] as $slot) {
					$totalbookcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
					$goingcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
					$cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
					$slot_status 		= 	($slot['limit'] > $goingcnt) ? "available" : "full";

					array_set($slot, 'totalbookcnt', $totalbookcnt);
					array_set($slot, 'goingcnt', $goingcnt);
					array_set($slot, 'cancelcnt', $cancelcnt);
					array_set($slot, 'status', $slot_status);

					$scheduleDateTime 				=	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']));
					$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
					array_set($slot, 'passed', $slot_datetime_pass_status);
					array_push($slots, $slot);
				}

				$service['slots'] = $slots;
				$service['workoutsessionschedules']['slots'] = $slots;
				array_push($scheduleservices, $service);
			}
		}
		return $scheduleservices;
	}



	/**
	 * Display the ServiceSchedule.
	 *
	 * @param  int  $serviceid
	 * @param  date $date(dd-mm-yyyy)
	 * @param  int  $noofdays
	 * @return Response
	 */

	public function getServiceSchedule($serviceid, $date = null, $noofdays = null, $schedulesof = null){


		// $dobj = new DateTime;print_r($dobj);exit;
		$currentDateTime 	=	\Carbon\Carbon::now();
		$item 				=	Service::where('_id', (int) $serviceid)->first(array('name', 'finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();
		if(!$item){
			return $this->responseNotFound('Service Schedule does not exist');
		}

		$finderid 			= 	intval($item['finder_id']);
		$noofdays 			=  	($noofdays == null) ? 1 : $noofdays;
		$schedulesof 		=  	($schedulesof == null) ? 'trialschedules' : $schedulesof;
		$serviceschedules 	= 	array();

		for ($j = 0; $j < $noofdays; $j++) {

			$dt 			=	Carbon::createFromFormat('Y-m-d', date("Y-m-d", strtotime($date)) )->addDays(intval($j))->format('d-m-Y');
			$timestamp 		= 	strtotime($dt);
			$weekday 		= 	strtolower(date( "l", $timestamp));
			// echo "$dt -- $weekday <br>";

			if($schedulesof == 'trialschedules'){

				$weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
					if($value['weekday'] == $weekday){
						return $value;
					}
				}));

			}else{

				$weekdayslots = head(array_where($item['workoutsessionschedules'], function($key, $value) use ($weekday){
					if($value['weekday'] == $weekday){
						return $value;
					}
				}));
			}

			// print_pretty($weekdayslots);

			// sslots exists
			$service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'date' => $dt, 'weekday' => $weekday, 'month' => date( "M", $timestamp), 'day' => date( "d", $timestamp));
			$slots = array();
			if(count($weekdayslots['slots']) > 0){
				foreach ($weekdayslots['slots'] as $slot) {
					$totalbookcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->count();
					$goingcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
					// $cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();								
					$slot_status 		= 	($slot['limit'] > $goingcnt) ? "available" : "full";

					array_set($slot, 'totalbookcnt', $totalbookcnt);
					array_set($slot, 'goingcnt', $goingcnt);
					// array_set($slot, 'cancelcnt', $cancelcnt);
					array_set($slot, 'status', $slot_status);

					$scheduleDateTime 				=	Carbon::createFromFormat('d-m-Y g:i A', date("d-m-Y g:i A", strtotime(strtoupper($dt." ".$slot['start_time']))) );
					// $scheduleDateTime 				=	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($dt." ".$slot['start_time']));
					// $scheduleDateTime 				=	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($dt." ".$slot['start_time']));
					$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
					array_set($slot, 'passed', $slot_datetime_pass_status);
					array_push($slots, $slot);
				}
			}

			$service['slots'] = $slots;
			array_push($serviceschedules, $service);

		}

		return $serviceschedules;
	}


	public function getBookTrial($finderid,$date = null){
		$finderid 	= 	(int) $finderid;
		$items 		= 	Booktrial::where('finder_id', '=', $finderid)
			->where('service_name', '=', 'gyms' )
			->where('schedule_date', '=', new DateTime($date) )
			->get(array('customer_name','service_name','finder_id','schedule_date','schedule_slot'));
		return $items;
	}


	/**
	 * Update Book A Trial.
	 *
	 */

	public function updateBookTrial() {

		$data = Input::json()->all();

		if(empty($data['booktrial_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - booktrial_id");
			return  Response::json($resp, 400);
		}

		$booktrial_id = intval(Input::json()->get('booktrial_id'));
		$customer_reminder_need_status = Input::json()->get('customer_reminder_need_status');
		$booktrialdata = array(
			'customer_reminder_need_status' 		=>		$customer_reminder_need_status
		);
		$booktiral 				= 	Booktrial::findOrFail($booktrial_id);
		$booktiral_response 	=	$booktiral->update($booktrialdata);

		$resp 	= 	array('status' => 200,'message' => "Book Trial Update Sucessfully");
		return Response::json($resp,200);

	}

	/**
	 * Booked Manual Book A Trial.
	 *
	 */

	public function manualBookTrial() {


		$data = Input::json()->all();

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

		if(empty($data['finder_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
			return  Response::json($resp, 400);
		}

		if(empty($data['finder_name'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_name");
			return  Response::json($resp, 400);
		}

		if(empty($data['city_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - city_id");
			return  Response::json($resp, 400);
		}

		// return $data	= Input::json()->all();
		$booktrialid 				=	Booktrial::max('_id') + 1;
		$finder_id 					= 	(int) Input::json()->get('finder_id');
		$city_id 					=	(int) Input::json()->get('city_id');
		$finder_name 				=	Input::json()->get('finder_name');
		$finder						=	Finder::active()->where('_id','=',intval($finder_id))->first();
		$customer_id				= 	$this->autoRegisterCustomer($data);
		$customer_name				= 	$data['customer_name'];
		$customer_email				= 	$data['customer_email'];
		$customer_phone				= 	$data['customer_phone'];

		$preferred_location			= 	(isset($data['preferred_location']) && $data['preferred_location'] != '') ? $data['preferred_location'] : "";
		$preferred_service			= 	(isset($data['preferred_service']) && $data['preferred_service'] != '') ? $data['preferred_service'] : "";
		$preferred_day				= 	(isset($data['preferred_day']) && $data['preferred_day'] != '') ? $data['preferred_day'] : "";
		$preferred_time				= 	(isset($data['preferred_time']) && $data['preferred_time'] != '') ? $data['preferred_time'] : "";
		$device_id					= 	(isset($data['device_id']) && $data['device_id'] != '') ? $data['device_id'] : "";
		$premium_session 			=	(isset($data['premium_session'])) ? (boolean) $data['premium_session'] : false;
		$additional_info			= 	(isset($data['additional_info']) && $data['additional_info'] != '') ? $data['additional_info'] : "";
		$otp	 					=	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";
		$customer_address	 		=	(isset($data['customer_address']) && $data['customer_address'] != '') ? implode(',', array_values($data['customer_address'])) : "";
		$customer_note	 			=	(isset($data['customer_note']) && $data['customer_note'] != '') ? $data['customer_note'] : "";

		$social_referrer					= 	(isset($data['social_referrer']) && $data['social_referrer'] != '') ? $data['social_referrer'] : "";
		$referrer_object					= 	(isset($data['referrer_object']) && $data['referrer_object'] != '') ? $data['referrer_object'] : "";
		$transacted_after			= 	(isset($data['transacted_after']) && $data['transacted_after'] != '') ? $data['transacted_after'] : "";


		$booktrialdata = array(
			'premium_session' 		=>		$premium_session,

			'finder_id' 			=>		$finder_id,
			'city_id'				=>		$city_id,
			'finder_name' 			=>		$finder_name,
			'finder_category_id' 	=>		intval($finder->category_id),

			'customer_id' 			=>		$customer_id,
			'customer_name' 		=>		$customer_name,
			'customer_email' 		=>		$customer_email,
			'customer_phone' 		=>		$customer_phone,

			'preferred_location'	=>		Input::json()->get('preferred_location'),
			'preferred_service'		=>		Input::json()->get('preferred_service'),
			'preferred_day'			=>		Input::json()->get('preferred_day'),
			'preferred_time'		=>		Input::json()->get('preferred_time'),
			'device_id'				=>		$device_id,
			'going_status'			=>		0,
			'going_status_txt'		=>		'not fixed',
			'booktrial_type'		=>		'manual',
			'booktrial_actions'		=>		'call to set up trial',
			'source'				=>		'website',
			'origin'				=>		'manual',
			'additional_info'		=>		$additional_info,
			'otp'					=>		$otp,
			'source_flag'			=> 		'customer',
			'final_lead_stage'			=>		'booking_stage',
			'final_lead_status'			=>		'slot_not_fixed',
			'customer_address'		=> 		$customer_address,
			'customer_note'		=>		$customer_note,

			'social_referrer'				=>		$social_referrer,
			'transacted_after'				=>		$transacted_after,
			'referrer_object'				=>		$referrer_object
		);


		if(isset($data['customer_address']) && $data['customer_address'] != ''){
			$booktrialdata['customer_address_array'] = $data['customer_address'];
		}

		$device_type						= 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
		$gcm_reg_id							= 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

		if($device_type != '' && $gcm_reg_id != ''){

			$reg_data = array();

			$reg_data['customer_id'] = $customer_id;
			$reg_data['reg_id'] = $gcm_reg_id;
			$reg_data['type'] = $device_type;

			$this->addRegId($reg_data);
		}


		// return $booktrialdata;
		$booktrial = new Booktrial($booktrialdata);
		$booktrial->_id = $booktrialid;
		$trialbooked = $booktrial->save();

		if($trialbooked){
			$sndInstantEmailCustomer		= 	$this->customermailer->manualBookTrial($booktrialdata);
			$sndInstantSmsCustomer			=	$this->customersms->manualBookTrial($booktrialdata);
		}

		$resp 	= 	array('status' => 200,'booktrialid',$booktrialid->_id, 'message' => "Book a Trial");
		return Response::json($resp,200);
	}


	public function manual2ndBookTrial() {

		$data = Input::json()->all();

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

		if(empty($data['finder_ids'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_ids");
			return  Response::json($resp, 400);
		}

		if(empty($data['finder_names'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_names");
			return  Response::json($resp, 400);
		}

		if(empty($data['city_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - city_id");
			return  Response::json($resp, 400);
		}

		// if(empty($data['preferred_location'])){
		// $resp 	= 	array('status' => 400,'message' => "Data Missing - preferred_location");
		// return  Response::json($resp, 400);
		// }

		// if(empty($data['preferred_day'])){
		//    $resp 	= 	array('status' => 400,'message' => "Data Missing - preferred_day");
		// return  Response::json($resp, 400);
		// }

		// if(empty($data['preferred_time'])){
		// $resp 	= 	array('status' => 400,'message' => "Data Missing - preferred_time");
		// return  Response::json($resp, 400);
		// }


		// return $data	= Input::json()->all();
		$finder_ids 				= 	Input::json()->get('finder_ids');
		$finder_names 				=	Input::json()->get('finder_names');
		$city_id 					=	(int) Input::json()->get('city_id');

		$customer_id				= 	$this->autoRegisterCustomer($data);
		$customer_name				= 	(Input::has('customer_name') && Input::json()->get('customer_name') != '') ? Input::json()->get('customer_name') : "";
		$customer_email				= 	(Input::has('customer_email') && Input::json()->get('customer_email') != '') ? Input::json()->get('customer_email') : "";
		$customer_phone				= 	(Input::has('customer_phone') && Input::json()->get('customer_phone') != '') ? Input::json()->get('customer_phone') : "";

		$preferred_location			= 	(Input::has('preferred_location') && Input::json()->get('preferred_location') != '') ? Input::json()->get('preferred_location') : "";
		$preferred_service			= 	(Input::has('preferred_service') && Input::json()->get('preferred_service') != '') ? Input::json()->get('preferred_service') : "";
		$preferred_day				= 	(Input::has('preferred_day') && Input::json()->get('preferred_day') != '') ? Input::json()->get('preferred_day') : "";
		$preferred_time				= 	(Input::has('preferred_time') && Input::json()->get('preferred_time') != '') ? Input::json()->get('preferred_time') : "";
		$device_id					= 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
		$premium_session 			=	(Input::json()->get('premium_session')) ? (boolean) Input::json()->get('premium_session') : false;
		$additional_info			= 	(Input::has('additional_info') && Input::json()->get('additional_info') != '') ? Input::json()->get('additional_info') : "";


		$booktrialdata = array(
			'premium_session' 		=>		$premium_session,
			'finder_ids' 			=>		implode(", ",$finder_ids),
			'city_id'				=>		$city_id,
			'finder_names' 			=>		implode(", ",$finder_names),

			'customer_id' 			=>		$customer_id,
			'customer_name' 		=>		$customer_name,
			'customer_email' 		=>		$customer_email,
			'customer_phone' 		=>		$customer_phone,

			'preferred_location'	=>		Input::json()->get('preferred_location'),
			'preferred_service'		=>		Input::json()->get('preferred_service'),
			'preferred_day'			=>		Input::json()->get('preferred_day'),
			'preferred_time'		=>		Input::json()->get('preferred_time'),
			'device_id'				=>		$device_id,
			'going_status'			=>		0,
			'going_status_txt'		=>		'not fixed',
			'booktrial_type'		=>		'2ndmanual',
			'booktrial_actions'		=>		'call to set up trial',
			'source'				=>		'website',
			'additional_info'		=>		$additional_info

		);

		foreach ($finder_ids as $key => $finder_id) {

			$insertdata		= 	array_except($booktrialdata, array('finder_ids','finder_names')); ;
			array_set($insertdata, 'finder_id', intval($finder_id));
			array_set($insertdata, 'finder_name', $finder_names[$key]);
			// return $insertdata;

			$booktrialid	=	Booktrial::max('_id') + 1;
			$booktrial 		= new Booktrial($insertdata);
			$booktrial->_id = $booktrialid;
			$trialbooked = $booktrial->save();
		}

		$sndInstantEmailCustomer	= 	$this->customermailer->manual2ndBookTrial($booktrialdata);
		$sndInstantSmsCustomer		=	$this->customersms->manual2ndBookTrial($booktrialdata);
		$resp 						= 	array('status' => 200,'message' => "Second Book a Trial");
		return Response::json($resp,200);

	}

	// public function bookTrialFintnessForce($booktrial,$finder){


	// 	if($finder){
	// 		$data = [];
	// 		$data['authenticationkey'] = "F862975730294C0F82E24DD224A26890";
	// 		$data['trialowner'] = "AUTO";
	// 		$data['name'] = $booktrial->customer_name;
	// 		$data['mobileno'] = $booktrial->customer_phone; 
	// 		$data['emailaddress'] = $booktrial->customer_email;
	// 		$data['startdate'] = date('d-M-Y',strtotime($booktrial->schedule_date_time));
	// 		$data['enddate'] = date('d-M-Y',strtotime($booktrial->schedule_date_time));
	// 		$data['starttime'] = $booktrial->schedule_slot_start_time;
	// 		$data['endtime'] = $booktrial->schedule_slot_end_time;

	// 		return $this->fitnessforce->createAppointment($data);
	// 	}
	// 	return false;
	// }

	public function updateAppointmentStatus(){

		$date = date("d-m-Y");
		$booktrail = Booktrial::with('finder')->where('schedule_date', '=', new DateTime($date))->get();
		$response = [];

		foreach ($booktrail as $key => $value) {
			$fitness_force = $this->fitnessforce->getAppointmentStatus($value);

			if($fitness_force['status'] == 200){

				$queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
				$queueddata['fitness_force_appointment']['status'] = 200;
				$queueddata['fitness_force_appointment'] = $fitness_force['data'];

				try{
					$value->update($queueddata);
					$response[$key] = [  	'status'=>200,
						'message'=>'Sucessfull',
						'id'=>$value->_id
					];
				}catch(Exception $e){
					$response[$key] = [  	'status'=>400,
						'message'=>'Update error',
						'id'=>$value->_id
					];
				}
			}else{
				$response[$key] = $fitness_force ;
				$response[$key]['id'] = $value->_id;
			}

		}

		return Response::json($response,200);
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

				if(isset($data['customer_address']) && !empty($data['customer_address']) ){
					$customerData['address'] = implode(",", array_values($data['customer_address']));
					$customerData['address_array'] = $data['customer_address'];
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


	public function bookTrialPaid(){

		$data = Input::json()->all();

		if(!isset($data['customer_name']) || $data['customer_name'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_name");
			return  Response::json($resp, 400);
		}

		if(!isset($data['customer_email']) || $data['customer_email'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
			return  Response::json($resp, 400);
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			$resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
			return  Response::json($resp, 400);
		}

		if(!isset($data['customer_phone']) || $data['customer_phone'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
			return  Response::json($resp, 400);
		}

		if(!isset($data['finder_id']) || $data['finder_id'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
			return  Response::json($resp, 400);
		}

		if(!isset($data['service_name']) || $data['service_name'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
			return  Response::json($resp, 400);
		}

		if(!isset($data['schedule_date']) || $data['schedule_date'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_date");
			return  Response::json($resp, 400);
		}

		if(!isset($data['schedule_slot']) || $data['schedule_slot'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
			return  Response::json($resp, 400);
		}

		if(!isset($data['order_id']) || $data['order_id'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - order_id");
			return  Response::json($resp, 400);
		}

		try {

			$service_id	 						=	(isset($data['service_id']) && $data['service_id'] != '') ? intval($data['service_id']) : "";
			$campaign	 						=	(isset($data['campaign']) && $data['campaign'] != '') ? $data['campaign'] : "";
			$otp	 							=	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";
			$slot_times 						=	explode('-',$data['schedule_slot']);
			$schedule_slot_start_time 			=	$slot_times[0];
			$schedule_slot_end_time 			=	$slot_times[1];
			$schedule_slot 						=	$schedule_slot_start_time.'-'.$schedule_slot_end_time;
			$slot_date 							=	date('d-m-Y', strtotime(Input::json()->get('schedule_date')));
			$schedule_date_starttime 			=	strtoupper($slot_date ." ".$schedule_slot_start_time);

			$booktrialid 						=	Booktrial::max('_id') + 1;
			$finderid 							= 	(int) Input::json()->get('finder_id');
			$finder 							= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->find($finderid);

			$customer_id 						=	$this->autoRegisterCustomer($data);
			$customer_name 						=	Input::json()->get('customer_name');
			$customer_email 					=	Input::json()->get('customer_email');
			$customer_phone 					=	Input::json()->get('customer_phone');
			$fitcard_user						= 	(Input::json()->get('fitcard_user')) ? intval(Input::json()->get('fitcard_user')) : 0;
			$type								= 	(Input::json()->get('type')) ? intval(Input::json()->get('type')) : '';

			$finder_name						= 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
			$finder_slug						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
			$finder_lat 						= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon 						= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$city_id 							=	(int) $finder['city_id'];

			$finder_commercial_type				= 	(isset($finder['commercial_type']) && $finder['commercial_type'] != '') ? (int)$finder['commercial_type'] : "";

			$social_referrer					= 	(isset($data['social_referrer']) && $data['social_referrer'] != '') ? $data['social_referrer'] : "";
			$referrer_object					= 	(isset($data['referrer_object']) && $data['referrer_object'] != '') ? $data['referrer_object'] : "";
			$transacted_after			= 	(isset($data['transacted_after']) && $data['transacted_after'] != '') ? $data['transacted_after'] : "";

			$final_lead_stage = '';
			$final_lead_status = '';

			$confirmed = array(1,3);

			if(in_array($finder_commercial_type, $confirmed)){

				$final_lead_stage = 'trial_stage';
				$final_lead_status = 'confirmed';

			}else{

				$final_lead_stage = 'booking_stage';
				$final_lead_status = 'call_to_confirm';
			}

			// $finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
			// $finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
			// $show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;

			$description =  $what_i_should_carry = $what_i_should_expect = '';
			if($service_id != ''){
				$serviceArr 						= 	Service::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('category')->with('subcategory')->find($service_id);

				if((isset($serviceArr['category']['description']) && $serviceArr['category']['description'] != '')){
					$description = $serviceArr['category']['description'];
				}else{
					if((isset($serviceArr['subcategory']['description']) && $serviceArr['subcategory']['description'] != '')){
						$description = $serviceArr['subcategory']['description'];
					}
				}

				if((isset($serviceArr['category']['what_i_should_carry']) && $serviceArr['category']['what_i_should_carry'] != '')){
					$what_i_should_carry = $serviceArr['category']['what_i_should_carry'];
				}else{
					if((isset($serviceArr['subcategory']['what_i_should_carry']) && $serviceArr['subcategory']['what_i_should_carry'] != '')){
						$what_i_should_carry = $serviceArr['subcategory']['what_i_should_carry'];
					}
				}



				if((isset($serviceArr['location']['name']) && $serviceArr['location']['name'] != '')){
					$finder_location					=	$serviceArr['location']['name'];
					$show_location_flag 				=   true;
				}else{
					$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
					$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;
				}
				if((isset($serviceArr['address']) && $serviceArr['address'] != '')){
					$finder_address						= 	$serviceArr['address'];
				}else{
					$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
				}
			}else{
				$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
				$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
				$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;
			}

			$finder_lat				=	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon				=	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$finder_photos			= 	[];
			if(isset($finder['photos']) && count($finder['photos']) > 0){
				foreach ($finder['photos'] as $key => $value) {
					if($key > 2){ continue; }
					array_push($finder_photos, Config::get('app.s3_finder_url').'g/full/'.$value['url']);
				}
			}

			$finder_vcc_email = "";
			if(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != ''){
				$explode = explode(',', $finder['finder_vcc_email']);
				$valid_finder_email = [];
				foreach ($explode as $email) {
					if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
						$valid_finder_email[] = $email;
					}
				}
				if(!empty($valid_finder_email)){
					$finder_vcc_email = implode(",", $valid_finder_email);
				}
			}

			$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
			$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
			$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
			$share_customer_no					= 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;


			$service_name						=	strtolower(Input::json()->get('service_name'));
			$schedule_date						=	date('Y-m-d 00:00:00', strtotime($slot_date));
			$schedule_date_time					=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

			$code								=	random_numbers(5);
			$device_id							= 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
			$premium_session 					=	(Input::json()->get('premium_session')) ? (boolean) Input::json()->get('premium_session') : false;
			$reminder_need_status 				=	(Input::json()->get('reminder_need_status')) ? Input::json()->get('reminder_need_status') : '';
			$additional_info					= 	(Input::has('additional_info') && Input::json()->get('additional_info') != '') ? Input::json()->get('additional_info') : "";


			$orderid 	=	(int) Input::json()->get('order_id');
			$order 		= 	Order::findOrFail($orderid);
			$type 		= 	$order->type;

			$booktrialdata = array(
				'booktrialid'					=>		intval($booktrialid),
				'campaign'						=>		$campaign,
				'premium_session' 				=>		$premium_session,
				'reminder_need_status' 			=>		$reminder_need_status,

				'customer_id' 					=>		$customer_id,
				'customer_name' 				=>		$customer_name,
				'customer_email' 				=>		$customer_email,
				'customer_phone' 				=>		$customer_phone,
				'fitcard_user'					=>		$fitcard_user,
				'type'							=>		$type,

				'finder_id' 					=>		$finderid,
				'finder_name' 					=>		$finder_name,
				'finder_slug' 					=>		$finder_slug,
				'finder_location' 				=>		$finder_location,
				'finder_address' 				=>		$finder_address,
				'finder_lat'		 			=>		$finder_lat,
				'finder_lon'		 			=>		$finder_lon,
				'finder_photos'		 			=>		$finder_photos,
				'description'		 			=>		$description,
				'what_i_should_carry'		 	=>		$what_i_should_carry,
				'what_i_should_expect'		 	=>		$what_i_should_expect,

				'city_id'						=>		$city_id,
				'finder_vcc_email' 				=>		$finder_vcc_email,
				'finder_vcc_mobile' 			=>		$finder_vcc_mobile,
				'finder_poc_for_customer_name'	=>		$finder_poc_for_customer_name,
				'finder_poc_for_customer_no'	=>		$finder_poc_for_customer_no,
				'show_location_flag'			=> 		$show_location_flag,
				'share_customer_no'				=> 		$share_customer_no,

				'service_id'					=>		$service_id,
				'service_name'					=>		$service_name,
				'schedule_slot_start_time'		=>		$schedule_slot_start_time,
				'schedule_slot_end_time'		=>		$schedule_slot_end_time,
				'schedule_date'					=>		$schedule_date,
				'schedule_date_time'			=>		$schedule_date_time,
				'schedule_slot'					=>		$schedule_slot,
				'going_status'					=>		1,
				'going_status_txt'				=>		'going',
				'code'							=>		$code,
				'device_id'						=>		$device_id,
				'booktrial_type'				=>		'auto',
				'booktrial_actions'				=>		'call to confirm trial',
				'source'						=>		'website',
				'origin'						=>		'auto',
				'additional_info'				=>		$additional_info,
				'amount'						=>		$order->amount,
				'otp'							=> 		$otp,
				'source_flag'					=> 		'customer',

				'final_lead_stage'				=>		$final_lead_stage,
				'final_lead_status'				=>		$final_lead_status,

				'social_referrer'				=>		$social_referrer,
				'transacted_after'				=>		$transacted_after,
				'referrer_object'				=>		$referrer_object
			);

			// return $this->customersms->bookTrial($booktrialdata);
			// return $booktrialdata;
			$booktrial = new Booktrial($booktrialdata);
			$booktrial->_id = $booktrialid;
			$trialbooked = $booktrial->save();

		} catch(ValidationException $e){

			// If booktrial query fail updates error message
			$orderid 	=	(int) Input::json()->get('order_id');
			$order 		= 	Order::findOrFail($orderid);
			array_set($data, 'message', $e->getMessage());
			$orderdata 	=	$order->update($data);
			return array('status' => 500,'message' => $e->getMessage());
		}

		if($trialbooked == true){

			$orderid = (int) Input::json()->get('order_id');
			$redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueBookTrialPaid', array('data'=>$data,'orderid'=>$orderid,'booktrialid'=>$booktrialid),'booktrial');
			$booktrial->update(array('redis_id'=>$redisid));

		}

		if($trialbooked == true && $campaign != ''){
			$this->attachTrialCampaignToCustomer($customer_id,$campaign,$booktrialid);
		}
		Log::info('Customer Book Trial : '.json_encode(array('book_trial_details' => Booktrial::findOrFail($booktrialid))));

		$resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'message' => "Book a Trial");
		return Response::json($resp,200);
	}

	public function toQueueBookTrialPaid($job,$data){


		try{
			$orderid = $data['orderid'];
			$booktrialid = $data['booktrialid'];
			$data = $data['data'];

			$slot_times 						=	explode('-',$data['schedule_slot']);
			$schedule_slot_start_time 			=	$slot_times[0];
			$schedule_slot_end_time 			=	$slot_times[1];
			$schedule_slot 						=	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

			$slot_date 							=	date('d-m-Y', strtotime($data['schedule_date']));
			$schedule_date_starttime 			=	strtoupper($slot_date ." ".$schedule_slot_start_time);
			$currentDateTime 					=	\Carbon\Carbon::now();
			$scheduleDateTime 					=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime);
			$delayReminderTimeBefore1Min 		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
			$delayReminderTimeBefore1Hour 		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
			$delayReminderTimeBefore5Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 5);
			$delayReminderTimeBefore12Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
			$delayReminderTimeAfter2Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 2);
			$reminderTimeAfter1Hour 			=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(60);
			$oneHourDiff 						= 	$currentDateTime->diffInHours($scheduleDateTime, false);
			$twelveHourDiff 					= 	$currentDateTime->diffInHours($scheduleDateTime, false);
			$oneHourDiffInMin 					= 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
			$fiveHourDiffInMin 					= 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
			$twelveHourDiffInMin 				= 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
			$finderid 							= 	(int) $data['finder_id'];

			$booktrialdata = Booktrial::findOrFail($booktrialid)->toArray();
			$order = Order::findOrFail($orderid);
			$finder = Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();

			$finder_category_id 				= (isset($booktrialdata['finder_category_id']) && $booktrialdata['finder_category_id'] != '') ? $booktrialdata['finder_category_id'] : "";

			array_set($data, 'status', '1');
			array_set($data, 'order_action', 'bought');
			array_set($data, 'booktrial_id', (int)$booktrialid);
			$orderdata 	=	$order->update($data);

			$customer_email_messageids 	=  $finder_email_messageids  =	$customer_sms_messageids  =  $finer_sms_messageids  =  $customer_notification_messageids  =  array();

			//Send Instant (Email) To Customer & Finder
			$sndInstantEmailCustomer				= 	$this->customermailer->bookTrial($booktrialdata);
			$sndInstantSmsCustomer					=	$this->customersms->bookTrial($booktrialdata);
			$sndInstantEmailFinder					= 	$this->findermailer->bookTrial($booktrialdata);
			$sndInstantSmsFinder					=	$this->findersms->bookTrial($booktrialdata);

			$customer_email_messageids['instant'] 	= 	$sndInstantEmailCustomer;
			$customer_sms_messageids['instant'] 	= 	$sndInstantSmsCustomer;
			$finder_email_messageids['instant'] 	= 	$sndInstantEmailFinder;
			$finer_sms_messageids['instant'] 		= 	$sndInstantSmsFinder;


			$customer_auto_sms = $this->autoSms($booktrialdata,$schedule_date_starttime);

			//Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
			if($twelveHourDiffInMin >= (12 * 60)){
				if($finder_category_id != 41){
					$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
					$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
				}
			}else{
				if($finder_category_id != 41){
					$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
					$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
				}
			}

			if(isset($data['device_id']) && $data['device_id'] != ''){
				if($fiveHourDiffInMin >= (5 * 60)){
					$sndBefore5HourNotificationCustomer					=	'';
					$customer_notification_messageids['before5hour'] 	= 	$sndBefore5HourNotificationCustomer;
				}
			}

			//Send Reminder Notiication (Sms) Before 1 Hour To Customer
			if($oneHourDiffInMin >= 60){
				$sndBefore1HourSmsCustomer					=	$this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
				$sndBefore1HourSmsFinder					=	$this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
				$customer_sms_messageids['before1hour'] 	= 	$sndBefore1HourSmsCustomer;
				$finer_sms_messageids['before1hour'] 		= 	$sndBefore1HourSmsFinder;
			}

			//Send Post Trial Notificaiton After 2 Hours Need to Write
			$sndAfter2HourEmailCustomer							= 	$this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
			$sndAfter2HourSmsCustomer							= 	$this->customersms->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
			$sndAfter2HourNotificationCustomer					= 	$this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
			$customer_email_messageids['after2hour'] 			= 	$sndAfter2HourEmailCustomer;
			$customer_sms_messageids['after2hour'] 				= 	$sndAfter2HourSmsCustomer;
			$customer_notification_messageids['after2hour'] 	= 	$sndAfter2HourNotificationCustomer;


			//update queue ids for booktiral
			$booktrial 		= 	Booktrial::findOrFail($booktrialid);

			$queueddata 	= 	array('customer_emailqueuedids' => $customer_email_messageids,
				'customer_smsqueuedids' => $customer_sms_messageids,
				'customer_notificationqueuedids' => $customer_notification_messageids,
				'finder_emailqueuedids' => $finder_email_messageids,
				'finder_smsqueuedids' => $finer_sms_messageids,
				'customer_auto_sms' => $customer_auto_sms
			);

			$fitness_force  = 	$this->fitnessforce->createAppointment(['booktrial'=>$booktrial,'finder'=>$finder]);

			if($fitness_force){
				if($fitness_force['status'] == 200){
					$queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
					$queueddata['fitness_force_appointment']['status'] = 200;
					$queueddata['fitness_force_appointment'] = $fitness_force['data'];
				}else{
					$queueddata['fitness_force_appointment'] = $fitness_force;
				}
			}

			$trialbooked 	= 	$booktrial->update($queueddata);

		}catch(\Exception $exception){
			Log::error($exception);
		}

		$job->delete();

	}


	public function bookTrialFree(){

		// send error message if any thing is missing
		$data = Input::json()->all();

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

		if(empty($data['finder_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
			return  Response::json($resp, 400);
		}

		if(empty($data['service_name'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
			return  Response::json($resp, 400);
		}

		if(empty($data['schedule_date'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_date");
			return  Response::json($resp, 400);
		}

		if(empty($data['schedule_slot'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
			return  Response::json($resp, 400);
		}

		try {

			$service_id	 						=	(isset($data['service_id']) && $data['service_id'] != '') ? intval($data['service_id']) : "";
			$campaign	 						=	(isset($data['campaign']) && $data['campaign'] != '') ? $data['campaign'] : "";
			$otp	 							=	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";
			$slot_times 						=	explode('-',$data['schedule_slot']);
			$schedule_slot_start_time 			=	$slot_times[0];
			$schedule_slot_end_time 			=	$slot_times[1];
			$schedule_slot 						=	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

			$slot_date 							=	date('d-m-Y', strtotime(Input::json()->get('schedule_date')));
			$schedule_date_starttime 			=	strtoupper($slot_date ." ".$schedule_slot_start_time);
			$currentDateTime 					=	\Carbon\Carbon::now();

			$booktrialid 						=	Booktrial::max('_id') + 1;
			$finderid 							= 	(int) Input::json()->get('finder_id');
			$finder 							= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();

			$customer_id 						=	$this->autoRegisterCustomer($data);
			$customer_name 						=	Input::json()->get('customer_name');
			$customer_email 					=	Input::json()->get('customer_email');
			$customer_phone 					=	Input::json()->get('customer_phone');
			$fitcard_user						= 	(Input::json()->get('fitcard_user')) ? intval(Input::json()->get('fitcard_user')) : 0;
			$type								= 	(Input::json()->get('type')) ? intval(Input::json()->get('type')) : '';


			$finder_name						= 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
			$finder_slug						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
			$finder_lat 						= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon 						= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$city_id 							=	(int) $finder['city_id'];
			$finder_commercial_type				= 	(isset($finder['commercial_type']) && $finder['commercial_type'] != '') ? (int)$finder['commercial_type'] : "";
			$finder_category_id						= 	(isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";

			$final_lead_stage = '';
			$final_lead_status = '';

			$confirmed = array(1,3);

			if(in_array($finder_commercial_type, $confirmed)){

				$final_lead_stage = 'trial_stage';
				$final_lead_status = 'confirmed';

			}else{

				$final_lead_stage = 'booking_stage';
				$final_lead_status = 'call_to_confirm';
			}

			$device_type						= 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
			$gcm_reg_id							= 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

			$social_referrer					= 	(isset($data['social_referrer']) && $data['social_referrer'] != '') ? $data['social_referrer'] : "";
			$referrer_object					= 	(isset($data['referrer_object']) && $data['referrer_object'] != '') ? $data['referrer_object'] : "";
			$transacted_after			= 	(isset($data['transacted_after']) && $data['transacted_after'] != '') ? $data['transacted_after'] : "";

			if($device_type != '' && $gcm_reg_id != ''){

				$reg_data = array();

				$reg_data['customer_id'] = $customer_id;
				$reg_data['reg_id'] = $gcm_reg_id;
				$reg_data['type'] = $device_type;

				$this->addRegId($reg_data);
			}

			// $finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
			// $finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
			// $show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;

			$description =  $what_i_should_carry = $what_i_should_expect = '';
			if($service_id != ''){
				$serviceArr 						= 	Service::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('category')->with('subcategory')->where('_id','=', intval($service_id))->first()->toArray();

				if((isset($serviceArr['category']['description']) && $serviceArr['category']['description'] != '')){
					$description = $serviceArr['category']['description'];
				}else{
					if((isset($serviceArr['subcategory']['description']) && $serviceArr['subcategory']['description'] != '')){
						$description = $serviceArr['subcategory']['description'];
					}
				}

				if((isset($serviceArr['category']['what_i_should_carry']) && $serviceArr['category']['what_i_should_carry'] != '')){
					$what_i_should_carry = $serviceArr['category']['what_i_should_carry'];
				}else{
					if((isset($serviceArr['subcategory']['what_i_should_carry']) && $serviceArr['subcategory']['what_i_should_carry'] != '')){
						$what_i_should_carry = $serviceArr['subcategory']['what_i_should_carry'];
					}
				}

				if((isset($serviceArr['category']['what_i_should_expect']) && $serviceArr['category']['what_i_should_expect'] != '')){
					$what_i_should_expect = $serviceArr['category']['what_i_should_expect'];
				}else{
					if((isset($serviceArr['subcategory']['what_i_should_expect']) && $serviceArr['subcategory']['what_i_should_expect'] != '')){
						$what_i_should_expect = $serviceArr['subcategory']['what_i_should_expect'];
					}
				}


				if((isset($serviceArr['location']['name']) && $serviceArr['location']['name'] != '')){
					$finder_location					=	$serviceArr['location']['name'];
					$show_location_flag 				=   true;
				}else{
					$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
					$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;
				}
				if((isset($serviceArr['address']) && $serviceArr['address'] != '')){
					$finder_address						= 	$serviceArr['address'];
				}else{
					$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
				}
			}else{
				$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
				$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
				$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;
			}

			$finder_lat				=	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon				=	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$finder_photos			= 	[];
			if(isset($finder['photos']) && count($finder['photos']) > 0){
				foreach ($finder['photos'] as $key => $value) {
					if($key > 2){ continue; }
					array_push($finder_photos, Config::get('app.s3_finder_url').'g/full/'.$value['url']);
				}
			}

			$finder_vcc_email = "";
			if(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != ''){
				$explode = explode(',', $finder['finder_vcc_email']);
				$valid_finder_email = [];
				foreach ($explode as $email) {
					if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
						$valid_finder_email[] = $email;
					}
				}
				if(!empty($valid_finder_email)){
					$finder_vcc_email = implode(",", $valid_finder_email);
				}
			}

			$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
			$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
			$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
			$share_customer_no					= 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;

			$service_name						=	strtolower(Input::json()->get('service_name'));
			$schedule_date						=	date('Y-m-d 00:00:00', strtotime($slot_date));
			$schedule_date_time					=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

			$code								=	random_numbers(5);
			$device_id							= 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
			$premium_session 					=	(Input::json()->get('premium_session')) ? (boolean) Input::json()->get('premium_session') : false;
			$reminder_need_status 				=	(Input::json()->get('reminder_need_status')) ? Input::json()->get('reminder_need_status') : '';
			$additional_info					= 	(Input::has('additional_info') && Input::json()->get('additional_info') != '') ? Input::json()->get('additional_info') : "";

			$booktrialdata = array(
				'booktrialid'					=>		$booktrialid,
				'campaign'						=>		$campaign,

				'premium_session' 				=>		$premium_session,
				'reminder_need_status' 			=>		$reminder_need_status,

				'customer_id' 					=>		$customer_id,
				'customer_name' 				=>		$customer_name,
				'customer_email' 				=>		$customer_email,
				'customer_phone' 				=>		$customer_phone,
				'fitcard_user'					=>		$fitcard_user,
				'type'							=>		$type,

				'finder_id' 					=>		$finderid,
				'finder_name' 					=>		$finder_name,
				'finder_slug' 					=>		$finder_slug,
				'finder_location' 				=>		$finder_location,
				'finder_address' 				=>		$finder_address,
				'finder_lat'		 			=>		$finder_lat,
				'finder_lon'		 			=>		$finder_lon,
				'finder_photos'		 			=>		$finder_photos,
				'description'		 			=>		$description,
				'what_i_should_carry'		 	=>		$what_i_should_carry,
				'what_i_should_expect'		 	=>		$what_i_should_expect,

				'city_id'						=>		$city_id,
				'finder_vcc_email' 				=>		$finder_vcc_email,
				'finder_vcc_mobile' 			=>		$finder_vcc_mobile,
				'finder_poc_for_customer_name'	=>		$finder_poc_for_customer_name,
				'finder_poc_for_customer_no'	=>		$finder_poc_for_customer_no,
				'show_location_flag'			=> 		$show_location_flag,
				'share_customer_no'				=> 		$share_customer_no,

				'service_id'					=>		$service_id,
				'service_name'					=>		$service_name,
				'schedule_slot_start_time'		=>		$schedule_slot_start_time,
				'schedule_slot_end_time'		=>		$schedule_slot_end_time,
				'schedule_date'					=>		$schedule_date,
				'schedule_date_time'			=>		$schedule_date_time,
				'schedule_slot'					=>		$schedule_slot,
				'going_status'					=>		1,
				'going_status_txt'				=>		'going',
				'code'							=>		$code,
				'device_id'						=>		$device_id,
				'booktrial_type'				=>		'auto',
				'booktrial_actions'				=>		'call to confirm trial',
				'source'						=>		'website',
				'origin'						=>		'auto',
				'additional_info'				=>		$additional_info,
				'otp'							=>		$otp,
				'source_flag'					=> 		'customer',
				'final_lead_stage'				=>		$final_lead_stage,
				'final_lead_status'				=>		$final_lead_status,

				'social_referrer'				=>		$social_referrer,
				'transacted_after'				=>		$transacted_after,
				'finder_category_id'				=>		$finder_category_id,
				'referrer_object'				=>		$referrer_object

			);

			// return $this->customersms->bookTrial($booktrialdata);
			// return $booktrialdata;
			$booktrial = new Booktrial($booktrialdata);
			$booktrial->_id = $booktrialid;
			$trialbooked = $booktrial->save();

		} catch(ValidationException $e){

			return array('status' => 500,'message' => $e->getMessage());
		}

		if($trialbooked == true){

			//if vendor type is free special dont send communication
			Log::info('finder commercial_type  -- '. $finder['commercial_type']);
			if($finder['commercial_type'] != '2'){
				$redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueBookTrialFree', array('data'=>$data,'booktrialid'=>$booktrialid), 'booktrial');
				$booktrial->update(array('redis_id'=>$redisid));
			}else{

				$customer_sms_free_special = $this->customersms->bookTrialFreeSpecial($booktrialdata);
				$booktrial->customer_sms_free_special = $customer_sms_free_special;
				$booktrial->update();
			}
		}

		if($trialbooked == true && $campaign != ''){
			$this->attachTrialCampaignToCustomer($customer_id,$campaign,$booktrialid);
		}

		Log::info('Customer Book Trial : '.json_encode(array('book_trial_details' => Booktrial::findOrFail($booktrialid))));

		$resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'message' => "Book a Trial");
		return Response::json($resp,200);
	}

	public function toQueueBookTrialFree($job,$data){

		try{

			$booktrialid = $data['booktrialid'];
			$data = $data['data'];

			$slot_times 						=	explode('-',$data['schedule_slot']);
			$schedule_slot_start_time 			=	$slot_times[0];
			$schedule_slot_end_time 			=	$slot_times[1];
			$schedule_slot 						=	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

			$slot_date 							=	date('d-m-Y', strtotime($data['schedule_date']));
			$schedule_date_starttime 			=	strtoupper($slot_date ." ".$schedule_slot_start_time);
			$currentDateTime 					=	\Carbon\Carbon::now();
			$scheduleDateTime 					=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime);
			$delayReminderTimeBefore1Min 		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
			$delayReminderTimeBefore1Hour 		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
			$delayReminderTimeBefore5Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 5);
			$delayReminderTimeBefore12Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
			$delayReminderTimeAfter2Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 2);
			$reminderTimeAfter1Hour 			=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(60);
			$oneHourDiff 						= 	$currentDateTime->diffInHours($scheduleDateTime, false);
			$twelveHourDiff 					= 	$currentDateTime->diffInHours($scheduleDateTime, false);
			$oneHourDiffInMin 					= 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
			$fiveHourDiffInMin 					= 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
			$twelveHourDiffInMin 				= 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
			$finderid 							= 	(int) $data['finder_id'];


			$booktrialdata = Booktrial::findOrFail($booktrialid)->toArray();
			$finder = Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();

			$finder_category_id 				= (isset($booktrialdata['finder_category_id']) && $booktrialdata['finder_category_id'] != '') ? $booktrialdata['finder_category_id'] : "";

			$customer_email_messageids 	=  $finder_email_messageids  =	$customer_sms_messageids  =  $finer_sms_messageids  =  $customer_notification_messageids  =  array();

			//Send Instant (Email) To Customer & Finder
			$sndInstantEmailCustomer				= 	$this->customermailer->bookTrial($booktrialdata);
			$sndInstantSmsCustomer					=	$this->customersms->bookTrial($booktrialdata);
			$sndInstantEmailFinder					= 	$this->findermailer->bookTrial($booktrialdata);
			$sndInstantSmsFinder					=	$this->findersms->bookTrial($booktrialdata);

			$customer_email_messageids['instant'] 	= 	$sndInstantEmailCustomer;
			$customer_sms_messageids['instant'] 	= 	$sndInstantSmsCustomer;
			$finder_email_messageids['instant'] 	= 	$sndInstantEmailFinder;
			$finer_sms_messageids['instant'] 		= 	$sndInstantSmsFinder;

			//ozonetel outbound calls
			$customer_auto_sms = $this->autoSms($booktrialdata,$schedule_date_starttime);

			//Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
			if($twelveHourDiffInMin >= (12 * 60)){
				if($finder_category_id != 41){
					$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
					$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
				}
			}else{
				if($finder_category_id != 41){
					$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
					$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
				}
			}

			if(isset($data['device_id']) && $data['device_id'] != ''){
				if($fiveHourDiffInMin >= (5 * 60)){
					$sndBefore5HourNotificationCustomer					=	'';
					$customer_notification_messageids['before5hour'] 	= 	$sndBefore5HourNotificationCustomer;
				}
			}

			//Send Reminder Notiication (Sms) Before 1 Hour To Customer
			if($oneHourDiffInMin >= 60){
				$sndBefore1HourSmsCustomer					=	$this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
				$sndBefore1HourSmsFinder					=	$this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
				$customer_sms_messageids['before1hour'] 	= 	$sndBefore1HourSmsCustomer;
				$finer_sms_messageids['before1hour'] 		= 	$sndBefore1HourSmsFinder;
			}

			//Send Post Trial Notificaiton After 2 Hours Need to Write
			$sndAfter2HourEmailCustomer							= 	$this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
			$sndAfter2HourSmsCustomer							= 	$this->customersms->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
			$sndAfter2HourNotificationCustomer					= 	$this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
			$customer_email_messageids['after2hour'] 			= 	$sndAfter2HourEmailCustomer;
			$customer_sms_messageids['after2hour'] 				= 	$sndAfter2HourSmsCustomer;
			$customer_notification_messageids['after2hour'] 	= 	$sndAfter2HourNotificationCustomer;

			//update queue ids for booktiral
			$queueddata 	= 	array('customer_emailqueuedids' => $customer_email_messageids,
				'customer_smsqueuedids' => $customer_sms_messageids,
				'customer_notificationqueuedids' => $customer_notification_messageids,
				'finder_emailqueuedids' => $finder_email_messageids,
				'finder_smsqueuedids' => $finer_sms_messageids,
				'customer_auto_sms' => $customer_auto_sms
			);

			$booktrial 		= 	Booktrial::findOrFail($booktrialid);

			$fitness_force  = 	$this->fitnessforce->createAppointment(['booktrial'=>$booktrial,'finder'=>$finder]);

			if($fitness_force){
				if($fitness_force['status'] == 200){
					$queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
					$queueddata['fitness_force_appointment']['status'] = 200;
					$queueddata['fitness_force_appointment'] = $fitness_force['data'];
				}else{
					$queueddata['fitness_force_appointment'] = $fitness_force;
				}
			}

			$trialbooked = $booktrial->update($queueddata);

		}catch(\Exception $exception){
			Log::error($exception);
		}

		$job->delete();

	}


	public function rescheduledBookTrial(){

		$data = Input::json()->all();

		if(!isset($data['booktrial_id']) || $data['booktrial_id'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - booktrial_id");
			return  Response::json($resp, 400);
		}

		if(!isset($data['customer_name']) || $data['customer_name'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_name");
			return  Response::json($resp, 400);
		}

		if(!isset($data['customer_email']) || $data['customer_email'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
			return  Response::json($resp, 400);
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			$resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
			return  Response::json($resp, 400);
		}

		if(!isset($data['customer_phone']) || $data['customer_phone'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
			return  Response::json($resp, 400);
		}

		if(!isset($data['finder_id']) || $data['finder_id'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
			return  Response::json($resp, 400);
		}

		if(!isset($data['service_name']) || $data['service_name'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
			return  Response::json($resp, 400);
		}

		if(!isset($data['schedule_date']) || $data['schedule_date'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_date");
			return  Response::json($resp, 400);
		}

		if(!isset($data['schedule_slot']) || $data['schedule_slot'] == ''){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
			return  Response::json($resp, 400);
		}


		try {

			$id 		= 	(int) $data['booktrial_id'];
			$booktrial 	= 	Booktrial::findOrFail($id);
			$old_going_status	 						=	(isset($booktrial->going_status) && $booktrial->going_status != '') ? $booktrial->going_status : "";
			$old_schedule_date	 						=	(isset($booktrial->schedule_date) && $booktrial->schedule_date != '') ? $booktrial->schedule_date : "";
			$old_schedule_slot_start_time	 			=	(isset($booktrial->schedule_slot_start_time) && $booktrial->schedule_slot_start_time != '') ? $booktrial->schedule_slot_start_time : "";
			$old_schedule_slot_end_time	 				=	(isset($booktrial->schedule_slot_end_time) && $booktrial->schedule_slot_end_time != '') ? $booktrial->schedule_slot_end_time : "";


			$service_id	 						=	(isset($data['service_id']) && $data['service_id'] != '') ? intval($data['service_id']) : "";
			$campaign	 						=	(isset($data['campaign']) && $data['campaign'] != '') ? $data['campaign'] : "";
			$send_alert	 						=	true;

			$update_only_info	 				=	'';
			$send_post_reminder_communication	=	(isset($data['send_post_reminder_communication']) && $data['send_post_reminder_communication'] != '') ? $data['send_post_reminder_communication'] : "";
			$send_purchase_communication		=	(isset($data['send_purchase_communication']) && $data['send_purchase_communication'] != '') ? $data['send_purchase_communication'] : "";
			$deadbooktrial						=	(isset($data['deadbooktrial']) && $data['deadbooktrial'] != '') ? $data['deadbooktrial'] : "";

			//its helpful to send any kind for dateformat date time as srting or iso formate timezond
			$slot_times 						=	explode('-',$data['schedule_slot']);
			$schedule_slot_start_time 			=	$slot_times[0];
			$schedule_slot_end_time 			=	$slot_times[1];
			$schedule_slot 						=	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

			$slot_date 							=	date('d-m-Y', strtotime($data['schedule_date']));
			$schedule_date_starttime 			=	strtoupper($slot_date ." ".$schedule_slot_start_time);
			$currentDateTime 					=	Carbon::now();
			$scheduleDateTime 					=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
			$delayReminderTimeBefore1Min 		=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
			$delayReminderTimeBefore1Hour 		=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
			$delayReminderTimeBefore12Hour		=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
			$delayReminderTimeAfter2Hour		=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 2);
			$reminderTimeAfter1Hour 			=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(60);
			$oneHourDiff 						= 	$currentDateTime->diffInHours($scheduleDateTime, false);
			$twelveHourDiff 					= 	$currentDateTime->diffInHours($scheduleDateTime, false);
			$oneHourDiffInMin 					= 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
			$twelveHourDiffInMin 				= 	$currentDateTime->diffInMinutes($scheduleDateTime, false);

			$booktrialid 						=	(int) $data['booktrial_id'];
			$finderid 							= 	(int) $data['finder_id'];
			$finder 							= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();

			$customer_id 						=	$data['customer_id'];
			$customer_name 						=	$data['customer_name'];
			$customer_email 					=	$data['customer_email'];
			$customer_phone 					=	$data['customer_phone'];

			$finder_name						= 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
			$finder_slug						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
			$finder_lat 						= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon 						= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$city_id 							=	(int) $finder['city_id'];

			$otp	 							=	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";

			$finder_category_id						= 	(isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";

			$description =  $what_i_should_carry = $what_i_should_expect = '';
			if($service_id != ''){
				$serviceArr 						= 	Service::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('category')->with('subcategory')->where('_id','=', intval($service_id))->first()->toArray();

				if((isset($serviceArr['category']['description']) && $serviceArr['category']['description'] != '')){
					$description = $serviceArr['category']['description'];
				}else{
					if((isset($serviceArr['subcategory']['description']) && $serviceArr['subcategory']['description'] != '')){
						$description = $serviceArr['subcategory']['description'];
					}
				}

				if((isset($serviceArr['category']['what_i_should_carry']) && $serviceArr['category']['what_i_should_carry'] != '')){
					$what_i_should_carry = $serviceArr['category']['what_i_should_carry'];
				}else{
					if((isset($serviceArr['subcategory']['what_i_should_carry']) && $serviceArr['subcategory']['what_i_should_carry'] != '')){
						$what_i_should_carry = $serviceArr['subcategory']['what_i_should_carry'];
					}
				}

				if((isset($serviceArr['category']['what_i_should_expect']) && $serviceArr['category']['what_i_should_expect'] != '')){
					$what_i_should_expect = $serviceArr['category']['what_i_should_expect'];
				}else{
					if((isset($serviceArr['subcategory']['what_i_should_expect']) && $serviceArr['subcategory']['what_i_should_expect'] != '')){
						$what_i_should_expect = $serviceArr['subcategory']['what_i_should_expect'];
					}
				}


				if((isset($serviceArr['location']['name']) && $serviceArr['location']['name'] != '')){
					$finder_location					=	$serviceArr['location']['name'];
					$show_location_flag 				=   true;
				}else{
					$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
					$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;
				}
				if((isset($serviceArr['address']) && $serviceArr['address'] != '')){
					$finder_address						= 	$serviceArr['address'];
				}else{
					$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
				}
			}else{
				$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
				$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
				$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;
			}

			$finder_lat				=	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon				=	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$finder_photos			= 	[];
			if(isset($finder['photos']) && count($finder['photos']) > 0){
				foreach ($finder['photos'] as $key => $value) {
					if($key > 2){ continue; }
					array_push($finder_photos, Config::get('app.s3_finder_url').'g/full/'.$value['url']);
				}
			}

			$finder_vcc_email = "";
			if(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != ''){
				$explode = explode(',', $finder['finder_vcc_email']);
				$valid_finder_email = [];
				foreach ($explode as $email) {
					if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
						$valid_finder_email[] = $email;
					}
				}
				if(!empty($valid_finder_email)){
					$finder_vcc_email = implode(",", $valid_finder_email);
				}
			}

			$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
			$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
			$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
			$share_customer_no					= 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;

			$service_name						=	(isset($data['service_name']) && $data['service_name'] != '') ? strtolower($data['service_name']) : "";
			$service_name_purchase				=	(isset($data['service_name_purchase']) && $data['service_name_purchase'] != '') ? strtolower($data['service_name_purchase']) : "";
			$service_duration_purchase			=	(isset($data['service_duration_purchase']) && $data['service_duration_purchase'] != '') ? strtolower($data['service_duration_purchase']) : "";
			$finder_branch						=	(isset($data['finder_branch']) && $data['finder_branch'] != '') ? strtolower($data['finder_branch']) : "";

			if($update_only_info == ''){
				$schedule_date						=	date('Y-m-d 00:00:00', strtotime($slot_date));
				$schedule_date_time					=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();
				$code								=	random_numbers(5);
			}
			$device_id							= 	(Input::has('device_id') && $data['device_id'] != '') ? $data['device_id'] : "";
			$followup_date 						=	(isset($data['followup_date']) && $data['followup_date'] != '') ? date('Y-m-d 00:00:00', strtotime($data['followup_date'])) : '';
			$followup_time 						=	(isset($data['followup_time']) && $data['followup_time'] != '') ? $data['followup_time'] : '';
			$followup_date_time					=	'';

			$menmbership_bought					=	(isset($data['menmbership_bought']) && $data['menmbership_bought'] != '') ? strtolower($data['menmbership_bought']) : "";
			$amount								=	(isset($data['amount']) && $data['amount'] != '') ? intval($data['amount']) : "";
			$amount_finder						=	(isset($data['amount_finder']) && $data['amount_finder'] != '') ? intval($data['amount_finder']) : "";
			$paid_trial_amount					=	(isset($data['paid_trial_amount']) && $data['paid_trial_amount'] != '') ? intval($data['paid_trial_amount']) : "";
			$premium_session 					=	(boolean) $data['premium_session'];
			$booktrial_actions 					=	(isset($data['booktrial_actions']) && $data['booktrial_actions'] != '') ? $data['booktrial_actions'] : "";
			$person_followingup 				=	(isset($data['person_followingup']) && $data['person_followingup'] != '') ? $data['person_followingup'] : "";
			$remarks 							=	(isset($data['remarks']) && $data['remarks'] != '') ? $data['remarks'] : "";
			$feedback_about_trial 				=	(isset($data['feedback_about_trial']) && $data['feedback_about_trial'] != '') ? $data['feedback_about_trial'] : "";

			$post_trial_status 					=	(isset($data['post_trial_status']) && $data['post_trial_status'] != '') ? $data['post_trial_status'] : "";
			$post_reminder_status 				=	(isset($data['post_reminder_status']) && $data['post_reminder_status'] != '') ? $data['post_reminder_status'] : "";
			$reminder_need_status 				=	(isset($data['reminder_need_status']) && $data['reminder_need_status'] != '') ? $data['reminder_need_status'] : "";
			$membership_bought_at 				=	(isset($data['membership_bought_at']) && $data['membership_bought_at'] != '') ? $data['membership_bought_at'] : "";

			$booktrialdata = array(
				'booktrialid' 					=>		$booktrialid,
				'menmbership_bought' 			=>		$menmbership_bought,

				'campaign'						=>		$campaign,
				'service_id'					=>		$service_id,
				'service_name' 					=>		$service_name,
				'service_name_purchase' 		=>		$service_name_purchase,
				'service_duration_purchase' 	=>		$service_duration_purchase,
				'finder_branch' 				=>		$finder_branch,

				'amount' 						=>		$amount,
				'amount_finder' 				=>		$amount_finder,
				'paid_trial_amount' 			=>		$paid_trial_amount,
				'premium_session' 				=>		$premium_session,
				'booktrial_actions' 			=>		$booktrial_actions,
				'person_followingup' 			=>		$person_followingup,
				'remarks' 						=>		$remarks,
				'feedback_about_trial' 			=>		$feedback_about_trial,
				'post_trial_status' 			=>		$post_trial_status,
				'post_reminder_status' 			=>		$post_reminder_status,
				'reminder_need_status' 			=>		$reminder_need_status,
				'membership_bought_at' 			=>		$membership_bought_at,

				'followup_date' 				=>		$followup_date,
				'followup_time' 				=>		$followup_time,
				'followup_date_time' 			=>		$followup_date_time,

				'customer_id' 					=>		$customer_id,
				'customer_name' 				=>		$customer_name,
				'customer_email' 				=>		$customer_email,
				'customer_phone' 				=>		$customer_phone,

				'finder_id' 					=>		$finderid,
				'finder_name' 					=>		$finder_name,
				'finder_slug' 					=>		$finder_slug,
				'finder_location' 				=>		$finder_location,
				'finder_address' 				=>		$finder_address,
				'finder_lat'		 			=>		$finder_lat,
				'finder_lon'		 			=>		$finder_lon,
				'finder_photos'		 			=>		$finder_photos,
				'what_i_should_carry'		 	=>		$what_i_should_carry,
				'what_i_should_expect'		 	=>		$what_i_should_expect,

				'city_id'						=>		$city_id,
				'finder_vcc_email' 				=>		$finder_vcc_email,
				'finder_vcc_mobile' 			=>		$finder_vcc_mobile,
				'finder_poc_for_customer_name'	=>		$finder_poc_for_customer_name,
				'finder_poc_for_customer_no'	=>		$finder_poc_for_customer_no,
				'show_location_flag'			=> 		$show_location_flag,
				'share_customer_no'				=> 		$share_customer_no,
				'device_id'						=>		$device_id,
				'otp'							=> 		$otp,
				'source_flag'					=> 		'customer',
				'finder_category_id'			=>		$finder_category_id
			);

			if($update_only_info == ''){
				array_set($booktrialdata, 'schedule_slot_start_time', $schedule_slot_start_time);
				array_set($booktrialdata, 'schedule_slot_end_time', $schedule_slot_end_time);
				array_set($booktrialdata, 'schedule_date', $schedule_date);
				array_set($booktrialdata, 'schedule_date_time', $schedule_date_time);
				array_set($booktrialdata, 'schedule_slot', $schedule_slot);
				array_set($booktrialdata, 'code', $code);
				array_set($booktrialdata, 'going_status', 1);
				array_set($booktrialdata, 'going_status_txt', 'rescheduled');
				array_set($booktrialdata, 'booktrial_type', 'auto');
			}

			$trialbooked = $booktrial->update($booktrialdata);

			$payload = array(
				'booktrialid'=>$booktrialid,
				'send_alert'=>$send_alert,
				'update_only_info'=>$update_only_info,
				'send_post_reminder_communication'=>$send_post_reminder_communication,
				'booktrialdata'=>$booktrialdata,
				'delayReminderTimeBefore12Hour'=>$delayReminderTimeBefore12Hour,
				'twelveHourDiffInMin'=>$twelveHourDiffInMin,
				'oneHourDiffInMin'=>$oneHourDiffInMin,
				'delayReminderTimeBefore1Hour'=>$delayReminderTimeBefore1Hour,
				'delayReminderTimeAfter2Hour'=>$delayReminderTimeAfter2Hour,
				'reminderTimeAfter1Hour'=> $reminderTimeAfter1Hour,
				'finder'=>$finder,
				'old_going_status'=>$old_going_status,
				'old_schedule_date'=>$old_schedule_date,
				'old_schedule_slot_start_time'=>$old_schedule_slot_start_time,
				'old_schedule_slot_end_time'=>$old_schedule_slot_end_time
			);

			$redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueRescheduledBookTrial',$payload, 'booktrial');
			$booktrial->update(array('reschedule_redis_id'=>$redisid));

			$resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'message' => "Rescheduled Trial");
			return Response::json($resp,200);

		} catch(ValidationException $e){

			return array('status' => 500,'message' => $e->getMessage());
		}

	}

	public function toQueueRescheduledBookTrial($job,$data){

		try{

			$booktrialid = (int) $data['booktrialid'];
			$send_alert = $data['send_alert'];
			$update_only_info = $data['update_only_info'];
			$send_post_reminder_communication = $data['send_post_reminder_communication'];
			$booktrialdata = Booktrial::find($booktrialid)->toArray();//$data['booktrialdata'];
			$delayReminderTimeBefore12Hour = $data['delayReminderTimeBefore12Hour'];
			$twelveHourDiffInMin = $data['twelveHourDiffInMin'];
			$oneHourDiffInMin = $data['oneHourDiffInMin'];
			$delayReminderTimeBefore1Hour = $data['delayReminderTimeBefore1Hour'];
			$delayReminderTimeAfter2Hour = $data['delayReminderTimeAfter2Hour'];
			$reminderTimeAfter1Hour = $data['reminderTimeAfter1Hour'];
			$finder = $data['finder'];
			$old_going_status = $data['old_going_status'];
			$old_schedule_date = $data['old_schedule_date'];
			$old_schedule_slot_start_time = $data['old_schedule_slot_start_time'];
			$old_schedule_slot_end_time = $data['old_schedule_slot_end_time'];




			$booktrial = Booktrial::find($booktrialid);

			$booktrialdata = $booktrial->toArray();

			$finder_category_id = (isset($booktrialdata['finder_category_id']) && $booktrialdata['finder_category_id'] != '') ? $booktrialdata['finder_category_id'] : "";

			//hit fitness force api start here
			if(isset($finder['fitnessforce_key']) && $finder['fitnessforce_key'] != ''){
				if($old_going_status == 6){
					$this->bookTrialFintnessForce ($booktrial,$finder);
				}elseif($old_schedule_date != $booktrial->schedule_date || $old_schedule_slot_start_time != $booktrial->schedule_slot_start_time || $old_schedule_slot_start_time != $booktrial->schedule_slot_end_time && isset($booktrial->fitness_force_appointment['appointmentbooktrialid']) && $booktrial->fitness_force_appointment['appointmentid'] != ''){
					$this->updateBookTrialFintnessForce($id);
				}
			}

			if($send_alert != '' && $update_only_info == ''){
				if((isset($booktrial->customer_emailqueuedids['before12hour']) && $booktrial->customer_emailqueuedids['before12hour'] != '')){

					try {
						$this->sidekiq->delete($booktrial->customer_emailqueuedids['before12hour']);
					}catch(\Exception $exception){
						Log::error($exception);
					}
				}

				if((isset($booktrial->customer_emailqueuedids['after2hour']) && $booktrial->customer_emailqueuedids['after2hour'] != '')){

					try {
						$this->sidekiq->delete($booktrial->customer_emailqueuedids['after2hour']);
					}catch(\Exception $exception){
						Log::error($exception);
					}

				}

				if((isset($booktrial->customer_smsqueuedids['before1hour']) && $booktrial->customer_smsqueuedids['before1hour'] != '')){

					try {
						$this->sidekiq->delete($booktrial->customer_smsqueuedids['before1hour']);
					}catch(\Exception $exception){
						Log::error($exception);
					}
				}

				if((isset($booktrial->customer_smsqueuedids['after2hour']) && $booktrial->customer_smsqueuedids['after2hour'] != '')){

					try {
						$this->sidekiq->delete($booktrial->customer_smsqueuedids['after2hour']);
					}catch(\Exception $exception){
						Log::error($exception);
					}
				}

				if((isset($booktrial->finder_smsqueuedids['before1hour']) && $booktrial->finder_smsqueuedids['before1hour'] != '')){

					try {
						$this->sidekiq->delete($booktrial->finder_smsqueuedids['before1hour']);
					}catch(\Exception $exception){
						Log::error($exception);
					}
				}

				if(isset($booktrial->customer_auto_sms) && $booktrial->customer_auto_sms != '' && $booktrial->customer_auto_sms != 'no_auto_sms'){

					try {
						$this->sidekiq->delete($booktrial->customer_auto_sms);
					}catch(\Exception $exception){
						Log::error($exception);
					}
				}
			}


			if($send_post_reminder_communication != '' && $update_only_info == ''){
				$sndInstantPostReminderStatusSmsFinder	=	$this->findersms->postReminderStatusSmsFinder($booktrialdata);
			}


			if($send_alert != '' && $update_only_info == ''){

				$customer_email_messageids 	=  $finder_email_messageids  =	$customer_sms_messageids  =  $finer_sms_messageids  = array();

				//Send Instant (Email) To Customer & Finder
				$sndInstantEmailCustomer				= 	$this->customermailer->rescheduledBookTrial($booktrialdata);
				$sndInstantSmsCustomer					=	$this->customersms->rescheduledBookTrial($booktrialdata);
				$sndInstantEmailFinder					= 	$this->findermailer->rescheduledBookTrial($booktrialdata);
				$sndInstantSmsFinder					=	$this->findersms->rescheduledBookTrial($booktrialdata);

				$customer_email_messageids['instant'] 	= 	$sndInstantEmailCustomer;
				$customer_sms_messageids['instant'] 	= 	$sndInstantSmsCustomer;
				$finder_email_messageids['instant'] 	= 	$sndInstantEmailFinder;
				$finer_sms_messageids['instant'] 		= 	$sndInstantSmsFinder;

				//Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
				if($twelveHourDiffInMin >= (12 * 60)){
					if($finder_category_id != 41){
						$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
						$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
					}
				}else{
					if($finder_category_id != 41){
						$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
						$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
					}
				}

				//Send Reminder Notiication (Sms) Before 1 Hour To Customer
				if($oneHourDiffInMin >= 60){
					$sndBefore1HourSmsCustomer					=	$this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
					$sndBefore1HourSmsFinder					=	$this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
					$customer_sms_messageids['before1hour'] 	= 	$sndBefore1HourSmsCustomer;
					$finer_sms_messageids['before1hour'] 		= 	$sndBefore1HourSmsFinder;
				}

				//Send Post Trial Notificaiton After 2 Hours Need to Write
				$sndAfter2HourEmailCustomer					= 	$this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
				$sndAfter2HourSmsCustomer					= 	$this->customersms->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
				$customer_email_messageids['after2hour'] 	= 	$sndAfter2HourEmailCustomer;
				$customer_sms_messageids['after2hour'] 		= 	$sndAfter2HourSmsCustomer;

				//update queue ids for booktiral
				$booktrial 		= 	Booktrial::findOrFail($booktrialid);
				$queueddata 	= 	array('customer_emailqueuedids' => $customer_email_messageids,
					'customer_smsqueuedids' => $customer_sms_messageids,
					'finder_emailqueuedids' => $finder_email_messageids,
					'finder_smsqueuedids' => $finer_sms_messageids);

				$booktrial->update($queueddata);

			}

		}catch(\Exception $exception){
			Log::error($exception);
		}

		$job->delete();

	}


	public function cancel($id){

		$id 				= 	(int) $id;
		$bookdata 			= 	array();
		$booktrial 			= 	Booktrial::findOrFail($id);
		array_set($bookdata, 'going_status', 2);
		array_set($bookdata, 'going_status_txt', 'cancel');
		array_set($bookdata, 'booktrial_actions', '');
		array_set($bookdata, 'followup_date', '');
		array_set($bookdata, 'followup_date_time', '');
		array_set($bookdata, 'source_flag', 'customer');
		array_set($bookdata, 'final_lead_stage', 'cancel_stage');
		$trialbooked 		= 	$booktrial->update($bookdata);

		if($trialbooked == true ){

			$redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueBookTrialCancel', array('id'=>$id), 'booktrial');
			$booktrial->update(array('cancel_redis_id'=>$redisid));

			$resp 	= 	array('status' => 200, 'message' => "Trial Canceled");
			return Response::json($resp,200);

		}else{

			$resp 	= 	array('status' => 400, 'message' => "Error");
			return Response::json($resp,400);

		}

	}

	public function toQueueBookTrialCancel($job,$data){

		try{

			$id = $data['id'];
			$booktrial = Booktrial::find($id);

			//hit fitness force api to cancel trial
			if(isset($booktrial->fitness_force_appointment['appointmentid']) && $booktrial->fitness_force_appointment['appointmentid'] != ''){
				$trialbooked = $this->cancelBookTrialFintnessForce($id);
			}

			if((isset($booktrial->customer_emailqueuedids['before12hour']) && $booktrial->customer_emailqueuedids['before12hour'] != '')){

				try {
					$this->sidekiq->delete($booktrial->customer_emailqueuedids['before12hour']);
				}catch(\Exception $exception){
					Log::error($exception);
				}
			}

			if((isset($booktrial->customer_emailqueuedids['after2hour']) && $booktrial->customer_emailqueuedids['after2hour'] != '')){

				try {
					$this->sidekiq->delete($booktrial->customer_emailqueuedids['after2hour']);
				}catch(\Exception $exception){
					Log::error($exception);
				}

			}

			if((isset($booktrial->customer_smsqueuedids['before1hour']) && $booktrial->customer_smsqueuedids['before1hour'] != '')){

				try {
					$this->sidekiq->delete($booktrial->customer_smsqueuedids['before1hour']);
				}catch(\Exception $exception){
					Log::error($exception);
				}
			}

			if((isset($booktrial->customer_smsqueuedids['after2hour']) && $booktrial->customer_smsqueuedids['after2hour'] != '')){

				try {
					$this->sidekiq->delete($booktrial->customer_smsqueuedids['after2hour']);
				}catch(\Exception $exception){
					Log::error($exception);
				}
			}

			if((isset($booktrial->finder_smsqueuedids['before1hour']) && $booktrial->finder_smsqueuedids['before1hour'] != '')){

				try {
					$this->sidekiq->delete($booktrial->finder_smsqueuedids['before1hour']);
				}catch(\Exception $exception){
					Log::error($exception);
				}
			}

			if(isset($booktrial->customer_auto_sms) && $booktrial->customer_auto_sms != '' && $booktrial->customer_auto_sms != 'no_auto_sms'){

				try {
					$this->sidekiq->delete($booktrial->customer_auto_sms);
				}catch(\Exception $exception){
					Log::error($exception);
				}
			}

			$booktrialdata      =	$booktrial;

			$finderid 							= 	(int) $booktrialdata['finder_id'];
			$finder 							= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();

			$finder_name						= 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
			$finder_slug						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
			$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
			$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
			$finder_lat 						= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon 						= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$city_id 							=	(int) $finder['city_id'];

			$finder_vcc_email = "";
			if(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != ''){
				$explode = explode(',', $finder['finder_vcc_email']);
				$valid_finder_email = [];
				foreach ($explode as $email) {
					if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
						$valid_finder_email[] = $email;
					}
				}
				if(!empty($valid_finder_email)){
					$finder_vcc_email = implode(",", $valid_finder_email);
				}
			}

			$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
			$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
			$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
			$share_customer_no					= 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;
			$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;

			$emaildata = array(
				'customer_name' 				=>		$booktrialdata->customer_name,
				'customer_email' 				=>		$booktrialdata->customer_email,
				'customer_phone' 				=>		$booktrialdata->customer_phone,

				'finder_id' 					=>		$finderid,
				'finder_name' 					=>		$finder_name,
				'finder_slug' 					=>		$finder_slug,
				'finder_location' 				=>		$finder_location,
				'finder_address' 				=>		$finder_address,
				'finder_lat'		 			=>		$finder_lat,
				'finder_lon'		 			=>		$finder_lon,
				'city_id'						=>		$city_id,
				'finder_vcc_email' 				=>		$finder_vcc_email,
				'finder_vcc_mobile' 			=>		$finder_vcc_mobile,
				'finder_poc_for_customer_name'	=>		$finder_poc_for_customer_name,
				'finder_poc_for_customer_no'	=>		$finder_poc_for_customer_no,
				'show_location_flag'			=> 		$show_location_flag,
				'share_customer_no'				=> 		$share_customer_no,

				'service_name'					=>		$booktrialdata->service_name,
				'schedule_slot_start_time'		=>		$booktrialdata->schedule_slot_start_time,
				'schedule_slot_end_time'		=>		$booktrialdata->schedule_slot_end_time,
				'schedule_date'					=>		$booktrialdata->schedule_date,
				'schedule_date_time'			=>		$booktrialdata->schedule_date_time,
				'schedule_slot'					=>		$booktrialdata->schedule_slot,

				'code'							=>		$booktrialdata->code,
				'booktrial_actions'				=>		"",
				'followup_date'					=>		"",
				'followup_date_time'			=>		""
			);

			$this->customermailer->cancelBookTrial($emaildata);
			$this->findermailer->cancelBookTrial($emaildata);
			$this->customersms->cancelBookTrial($emaildata);
			$this->findersms->cancelBookTrial($emaildata);

		}catch(\Exception $exception){

			Log::error($exception);
		}

		$job->delete();

	}

	public function bookTrialFintnessForce($booktrial,$finder){

		$fitness_force  = 	$this->fitnessforce->createAppointment(['booktrial'=>$booktrial,'finder'=>$finder]);

		if($fitness_force){
			if($fitness_force['status'] == 200){
				$queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
				$queueddata['fitness_force_appointment'] = $fitness_force['data'];
			}else{
				$queueddata['fitness_force_appointment'] = $fitness_force;
			}
		}

		return $booktrial->update($queueddata);
	}

	public function cancelBookTrialFintnessForce($id){

		$booktrial = Booktrial::with('finder')->where('_id','=',$id)->first();
		$fitness_force  = 	$this->fitnessforce->cancelAppointment($booktrial);

		if($fitness_force){
			if($fitness_force['status'] == 200){
				$queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
				$queueddata['fitness_force_appointment'] = $booktrial->fitness_force_appointment;
				$queueddata['fitness_force_appointment']['appointmentstatus'] = $fitness_force['data']['appointmentstatus'];
			}else{
				$queueddata['fitness_force_appointment_cancel'] = $fitness_force;
			}
		}

		return $booktrial->update($queueddata);
	}



	public function updateBookTrialFintnessForce($id){

		$booktrial = Booktrial::with('finder')->where('_id','=',$id)->first();
		$fitness_force  = 	$this->fitnessforce->updateAppointment($booktrial);

		if($fitness_force){
			if($fitness_force['status'] == 200){
				$queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
				$queueddata['fitness_force_appointment'] = $booktrial->fitness_force_appointment;
				$queueddata['fitness_force_appointment']['appointmentid'] = $fitness_force['data']['appointmentid'];
				$queueddata['fitness_force_appointment']['status'] = $fitness_force['data']['status'];
				$queueddata['fitness_force_appointment']['appointmentstatus'] = $fitness_force['data']['appointmentstatus'];
				$queueddata['fitness_force_appointment']['appointmentwith'] = $fitness_force['data']['appointmentwith'];
				$queueddata['fitness_force_appointment']['startdate'] = $fitness_force['data']['startdate'];
				$queueddata['fitness_force_appointment']['enddate'] = $fitness_force['data']['enddate'];
				$queueddata['fitness_force_appointment']['starttime'] = $fitness_force['data']['starttime'];
				$queueddata['fitness_force_appointment']['endtime'] = $fitness_force['data']['endtime'];
			}else{
				$queueddata['fitness_force_appointment_update'] = $fitness_force;
			}
		}

		return $booktrial->update($queueddata);
	}



	public function attachTrialCampaignToCustomer($cid, $campaign, $trialid = ''){

		$data 		= [];
		$customer 	= Customer::find(intval($cid));
		if($campaign == 'uber' && $trialid != ''){
			$data['uber_trials'] = $customer->uber_trials.','.$trialid;
		}

		return $customer->update($data);
	}

	public function deleteTask($id){

		$cancel = Schedulerjob::where('_id',(int)$id)->update(array('status'=>'cancel'));

		return $cancel;

	}

	public function autoSms($booktrialdata,$schedule_date_starttime){

		$created_date =  strtotime($booktrialdata['created_at']);
		$schedule_date = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

		$created_sec = $created_date;
		$scheduled_sec = strtotime($schedule_date);
		$diff_sec = (int) ($scheduled_sec - $created_sec) ;
		$hour = (int) date("G", strtotime($schedule_date));
		$min = (int) date("i", strtotime($schedule_date));
		$hour2 = 60*60*2;
		$hour4 = 60*60*4;

		if($hour >= 11 && $hour <= 22){

			if($diff_sec >= $hour4){

				$booktrial = Booktrial::find((int) $booktrialdata['_id']);
				$booktrial->update(array('outbound_sms_status'=>'1'));

				$ozonetel_date = date("Y-m-d H:i:s", strtotime($schedule_date . "-4 hours"));

				Log::info('ozonetel_date  -- '. $ozonetel_date);

				return $this->customersms->missedCallDelay($booktrialdata,$ozonetel_date);

			}

		}

		if($hour >= 6 && $hour <= 10){

			$booktrial = Booktrial::find((int) $booktrialdata['_id']);
			$booktrial->update(array('outbound_sms_status'=>'1'));

			$ozonetel_date = date("Y-m-d 21:00:00", strtotime($schedule_date . "-1 days"));
			$ozonetel_date_sec = strtotime($ozonetel_date);

			if($ozonetel_date_sec > $created_sec){

				Log::info('ozonetel_date  -- '. $ozonetel_date);

				return $this->customersms->missedCallDelay($booktrialdata,$ozonetel_date);
			}

		}

		return 'no_auto_sms';
	}

	public function addRegId($data){

		$response = add_reg_id($data);

		return Response::json($response,$response['status']);
	}
	public function booktrialdetail($captureid){

		$booktrial 		=	Booktrial::find(intval($captureid));

		if(!$booktrial){
			return $this->responseNotFound('Request not found');
		}

		$responsedata 	= ['booktrial' => $booktrial,  'message' => 'Booktrial Detail'];
		return Response::json($responsedata, 200);

	}

}