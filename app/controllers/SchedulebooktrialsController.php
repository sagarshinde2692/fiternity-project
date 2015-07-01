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


class SchedulebooktrialsController extends \BaseController {

	protected $customermailer;
	protected $findermailer;
	protected $customersms;
	protected $findersms;
	protected $customernotification;

	public function __construct(CustomerMailer $customermailer, FinderMailer $findermailer, CustomerSms $customersms, FinderSms $findersms, CustomerNotification $customernotification) {
		//parent::__construct();	
		date_default_timezone_set("Asia/Kolkata");
		$this->customermailer			=	$customermailer;
		$this->findermailer				=	$findermailer;
		$this->customersms 				=	$customersms;
		$this->findersms 				=	$findersms;
		$this->customernotification 	=	$customernotification;
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

				array_push($scheduleservices, $service);

			}

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

	public function getServiceSchedule($serviceid, $date = null, $noofdays = null){

		// $dobj = new DateTime;print_r($dobj);exit;
		$currentDateTime 	=	\Carbon\Carbon::now();
		
		$item 				=	Service::where('_id', (int) $serviceid)->first(array('name', 'finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();

		$finderid 			= 	intval($item['finder_id']);

		$noofdays 			=  	($noofdays == null) ? 1 : $noofdays;

		$serviceschedules 	= 	array();

		for ($j = 0; $j < $noofdays; $j++) {

			$dt 			=	Carbon::createFromFormat('Y-m-d', date("Y-m-d", strtotime($date)) )->addDays(intval($j))->format('d-m-Y'); 

			$timestamp 		= 	strtotime($dt);

			$weekday 		= 	strtolower(date( "l", $timestamp));
			// echo "$dt -- $weekday <br>";

			$weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){

				if($value['weekday'] == $weekday){
					return $value;
				}

			}));

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
	 * Book Scheduled Book A Trial.
	 *
	 */

	public function bookTrial(){

		// send error message if any thing is missing	

		$data = Input::json()->all();

		if(empty($data['customer_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_id");
		}

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_name");
		}

		if(empty($data['customer_email'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_email");
		}

		if(empty($data['customer_phone'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_phone");
		}

		if(empty($data['finder_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_id");
		}

		if(empty($data['service_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - service_name");
		}

		if(empty($data['schedule_date'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - schedule_date");
		}

		if(empty($data['schedule_slot'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - schedule_slot");
		}

		try {

			//return $data	= Input::json()->all();
			//its helpful to send any kind for dateformat date time as srting or iso formate timezond
			$slot_times 						=	explode('-',Input::json()->get('schedule_slot'));
			$schedule_slot_start_time 			=	$slot_times[0];
			$schedule_slot_end_time 			=	$slot_times[1];
			$schedule_slot 						=	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

			$slot_date 							=	date('d-m-Y', strtotime(Input::json()->get('schedule_date')));
			$schedule_date_starttime 			=	strtoupper($slot_date ." ".$schedule_slot_start_time);
			$currentDateTime 					=	\Carbon\Carbon::now();
			$scheduleDateTime 					=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime);
			$delayReminderTimeBefore1Min 		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
			$delayReminderTimeBefore1Hour 		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
			$delayReminderTimeBefore5Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 5);
			$delayReminderTimeBefore12Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
			$delayReminderTimeAfter2Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 2);
			$oneHourDiff 						= 	$currentDateTime->diffInHours($delayReminderTimeBefore1Hour, false);  
			$twelveHourDiff 					= 	$currentDateTime->diffInHours($delayReminderTimeBefore12Hour, false); 
			$oneHourDiffInMin 					= 	$currentDateTime->diffInMinutes($delayReminderTimeBefore1Hour, false);  
			$fiveHourDiffInMin 					= 	$currentDateTime->diffInMinutes($delayReminderTimeBefore5Hour, false);  
			$twelveHourDiffInMin 				= 	$currentDateTime->diffInMinutes($delayReminderTimeBefore12Hour, false);  

			// echo "<br>currentDateTime : $currentDateTime, 
			// 		<br>scheduleDateTime : $scheduleDateTime, 
			// 		<br>Before1Min : $delayReminderTimeBefore1Min, 
			// 		<br>Before1Hour : $delayReminderTimeBefore1Hour, 
			// 		<br>Before12Hour : $delayReminderTimeBefore12Hour,
			// 		<br><br>oneHourDiff  -- $oneHourDiff   ,  
			// 		<br>twelveHourDiff  -- $twelveHourDiff 
			// 		<br>oneHourDiffInMin  -- $oneHourDiffInMin
			// 		<br>twelveHourDiffInMin  -- $twelveHourDiffInMin";
			// exit;
			
			$booktrialid 						=	Booktrial::max('_id') + 1;
			$finderid 							= 	(int) Input::json()->get('finder_id');
			$finder 							= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();
			
			// return $finder['locationtags'];		
			// echo  count($finder['locationtags']);

			$customer_id 						=	Input::json()->get('customer_id'); 
			$customer_name 						=	Input::json()->get('customer_name'); 
			$customer_email 					=	Input::json()->get('customer_email'); 
			$customer_phone 					=	Input::json()->get('customer_phone');
			$fitcard_user						= 	(Input::json()->get('fitcard_user')) ? intval(Input::json()->get('fitcard_user')) : 0;

			$finder_name						= 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
			$finder_slug						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
			$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
			$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
			$finder_lat 						= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon 						= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$city_id 							=	(int) $finder['city_id'];
			$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;

			$finder_vcc_email					= 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
			$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
			$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
			$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_no']) && $finder['finder_poc_for_customer_no'] != '') ? $finder['finder_poc_for_customer_no'] : "";

			$service_name						=	strtolower(Input::json()->get('service_name'));
			$schedule_date						=	date('Y-m-d 00:00:00', strtotime($slot_date));
			$schedule_date_time					=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

			$code								=	$booktrialid.str_random(8);
			$device_id							= 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
			$premium_session 					=	(Input::json()->get('premium_session')) ? (boolean) Input::json()->get('premium_session') : false;
			$additional_info					= 	(Input::has('additional_info') && Input::json()->get('additional_info') != '') ? Input::json()->get('additional_info') : "";

			$booktrialdata = array(
				'booktrialid'					=>		$booktrialid,
				'premium_session' 				=>		$premium_session, 

				'customer_id' 					=>		$customer_id, 
				'customer_name' 				=>		$customer_name, 
				'customer_email' 				=>		$customer_email, 
				'customer_phone' 				=>		$customer_phone,
				'fitcard_user'					=>		$fitcard_user,

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
				'additional_info'				=>		$additional_info	
				);

			// return $this->customersms->bookTrial($booktrialdata);
			// return $booktrialdata;
$booktrial = new Booktrial($booktrialdata);
$booktrial->_id = $booktrialid;
$trialbooked = $booktrial->save();

} catch(ValidationException $e){

	return array('status' => 500,'message' => $e->getMessage());
}

if($trialbooked = true){

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

			// return "$sndInstantEmailCustomer --- $sndInstantSmsCustomer   ----  $sndInstantEmailFinder   --- $sndInstantSmsFinder ";
			//#############  TESTING FOR 1 MIN START ##############
			//Send Reminder Notiication (Email) Before 1 Min To Customer used for testing
			// $sndBefore1MinEmailCustomer			= 	$this->customermailer->bookTrialReminderBefore1Min($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndBefore1MinSmsCustomer			=	$this->customersms->bookTrialReminderBefore1Min($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndBefore1MinNotificationCustomer		=	$this->customernotification->bookTrialReminderBefore1Min($booktrialdata, $delayReminderTimeBefore1Min);


			//#############  TESTING FOR 1 MIN END ##############

			//Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
	if($twelveHourDiffInMin >= (12 * 60)){
		$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
		$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
				// $sndBefore12HourSmsCustomer			=	$this->customersms->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
				// $sms_messageids['before12hour'] 	= 	$sndBefore12HourSmsCustomer;
	}

	if($device_id != ''){
		if($fiveHourDiffInMin >= (5 * 60)){
			// $sndBefore5HourNotificationCustomer					=	$this->customernotification->bookTrialReminderBefore5Hour($booktrialdata, $delayReminderTimeBefore5Hour);
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
		'finder_smsqueuedids' => $finer_sms_messageids
		);
	$trialbooked 	= 	$booktrial->update($queueddata);
}

$resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'message' => "Book a Trial");
return Response::json($resp,200);	
}




	/**
	 * Book Scheduled Book A Trial with payment.
	 *
	 */

	public function bookTrialV2(){

		// send error message if any thing is missing	

		$data = Input::json()->all();

		if(!isset($data['customer_id']) || $data['customer_id'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_id");
		}

		if(!isset($data['customer_name']) || $data['customer_name'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_name");
		}

		if(!isset($data['customer_email']) || $data['customer_email'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_email");
		}

		if(!isset($data['customer_phone']) || $data['customer_phone'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_phone");
		}

		if(!isset($data['finder_id']) || $data['finder_id'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_id");
		}

		if(!isset($data['service_name']) || $data['service_name'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - service_name");
		}

		if(!isset($data['schedule_date']) || $data['schedule_date'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - schedule_date");
		}

		if(!isset($data['schedule_slot']) || $data['schedule_slot'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - schedule_slot");
		}

		if(!isset($data['order_id']) || $data['order_id'] == ''){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - order_id");
		}

		try {

			//return $data	= Input::json()->all();
			//its helpful to send any kind for dateformat date time as srting or iso formate timezond
			$slot_times 						=	explode('-',Input::json()->get('schedule_slot'));
			$schedule_slot_start_time 			=	$slot_times[0];
			$schedule_slot_end_time 			=	$slot_times[1];
			$schedule_slot 						=	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

			$slot_date 							=	date('d-m-Y', strtotime(Input::json()->get('schedule_date')));
			$schedule_date_starttime 			=	strtoupper($slot_date ." ".$schedule_slot_start_time);
			$currentDateTime 					=	\Carbon\Carbon::now();
			$scheduleDateTime 					=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime);
			$delayReminderTimeBefore1Min 		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
			$delayReminderTimeBefore1Hour 		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
			$delayReminderTimeBefore5Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 5);
			$delayReminderTimeBefore12Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
			$delayReminderTimeAfter2Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 2);
			$oneHourDiff 						= 	$currentDateTime->diffInHours($delayReminderTimeBefore1Hour, false);  
			$twelveHourDiff 					= 	$currentDateTime->diffInHours($delayReminderTimeBefore12Hour, false); 
			$oneHourDiffInMin 					= 	$currentDateTime->diffInMinutes($delayReminderTimeBefore1Hour, false);  
			$fiveHourDiffInMin 					= 	$currentDateTime->diffInMinutes($delayReminderTimeBefore5Hour, false);  
			$twelveHourDiffInMin 				= 	$currentDateTime->diffInMinutes($delayReminderTimeBefore12Hour, false);  

			// echo "<br>currentDateTime : $currentDateTime, 
			// 		<br>scheduleDateTime : $scheduleDateTime, 
			// 		<br>Before1Min : $delayReminderTimeBefore1Min, 
			// 		<br>Before1Hour : $delayReminderTimeBefore1Hour, 
			// 		<br>Before12Hour : $delayReminderTimeBefore12Hour,
			// 		<br><br>oneHourDiff  -- $oneHourDiff   ,  
			// 		<br>twelveHourDiff  -- $twelveHourDiff 
			// 		<br>oneHourDiffInMin  -- $oneHourDiffInMin
			// 		<br>twelveHourDiffInMin  -- $twelveHourDiffInMin";
			// exit;
			
			$booktrialid 						=	Booktrial::max('_id') + 1;
			$finderid 							= 	(int) Input::json()->get('finder_id');
			$finder 							= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();
			
			// return $finder['locationtags'];		
			// echo  count($finder['locationtags']);

			$customer_id 						=	Input::json()->get('customer_id'); 
			$customer_name 						=	Input::json()->get('customer_name'); 
			$customer_email 					=	Input::json()->get('customer_email'); 
			$customer_phone 					=	Input::json()->get('customer_phone');
			$fitcard_user						= 	(Input::json()->get('fitcard_user')) ? intval(Input::json()->get('fitcard_user')) : 0;

			$finder_name						= 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
			$finder_slug						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
			$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
			$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
			$finder_lat 						= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
			$finder_lon 						= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$city_id 							=	(int) $finder['city_id'];
			$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;

			$finder_vcc_email					= 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
			$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
			$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
			$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_no']) && $finder['finder_poc_for_customer_no'] != '') ? $finder['finder_poc_for_customer_no'] : "";

			$service_name						=	strtolower(Input::json()->get('service_name'));
			$schedule_date						=	date('Y-m-d 00:00:00', strtotime($slot_date));
			$schedule_date_time					=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

			$code								=	$booktrialid.str_random(8);
			$device_id							= 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
			$premium_session 					=	(Input::json()->get('premium_session')) ? (boolean) Input::json()->get('premium_session') : false;
			$additional_info					= 	(Input::has('additional_info') && Input::json()->get('additional_info') != '') ? Input::json()->get('additional_info') : "";


			$booktrialdata = array(
				'booktrialid'					=>		intval($booktrialid),
				'premium_session' 				=>		$premium_session, 

				'customer_id' 					=>		$customer_id, 
				'customer_name' 				=>		$customer_name, 
				'customer_email' 				=>		$customer_email, 
				'customer_phone' 				=>		$customer_phone,
				'fitcard_user'					=>		$fitcard_user,

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
				'additional_info'				=>		$additional_info	
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

if($trialbooked = true){

	$orderid 	=	(int) Input::json()->get('order_id');

	$order 		= 	Order::findOrFail($orderid);

	array_set($data, 'status', '1');

	array_set($data, 'booktrial_id', intval($booktrialid));

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

			// return "$sndInstantEmailCustomer --- $sndInstantSmsCustomer   ----  $sndInstantEmailFinder   --- $sndInstantSmsFinder ";
			//#############  TESTING FOR 1 MIN START ##############
			//Send Reminder Notiication (Email) Before 1 Min To Customer used for testing
			// $sndBefore1MinEmailCustomer			= 	$this->customermailer->bookTrialReminderBefore1Min($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndBefore1MinSmsCustomer			=	$this->customersms->bookTrialReminderBefore1Min($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndBefore1MinNotificationCustomer		=	$this->customernotification->bookTrialReminderBefore1Min($booktrialdata, $delayReminderTimeBefore1Min);


			//#############  TESTING FOR 1 MIN END ##############

			//Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
	if($twelveHourDiffInMin >= (12 * 60)){
		$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
		$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
				// $sndBefore12HourSmsCustomer			=	$this->customersms->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
				// $sms_messageids['before12hour'] 	= 	$sndBefore12HourSmsCustomer;
	}

	if($device_id != ''){
		if($fiveHourDiffInMin >= (5 * 60)){
			// $sndBefore5HourNotificationCustomer					=	$this->customernotification->bookTrialReminderBefore5Hour($booktrialdata, $delayReminderTimeBefore5Hour);
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
		'finder_smsqueuedids' => $finer_sms_messageids
		);
	$trialbooked 	= 	$booktrial->update($queueddata);
}

$resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'message' => "Book a Trial");
return Response::json($resp,200);	
}


	/**
	 * Booked Manual Book A Trial.
	 *
	 */

	public function manualBookTrial() {


		$data = Input::json()->all();

		if(empty($data['customer_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_id");
		}

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_name");
		}

		if(empty($data['customer_email'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_email");
		}

		if(empty($data['customer_phone'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_phone");
		}

		if(empty($data['finder_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_id");
		}

		if(empty($data['finder_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_name");
		}

		if(empty($data['city_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - city_id");
		}

		// if(empty($data['preferred_location'])){
		// 	return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_location");
		// }

		// if(empty($data['preferred_day'])){
		// 	return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_day");
		// }

		// if(empty($data['preferred_time'])){
		// 	return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_time");
		// }

		// exit;

		// return $data	= Input::json()->all();
		$booktrialid 				=	Booktrial::max('_id') + 1;
		$finder_id 					= 	(int) Input::json()->get('finder_id');
		$city_id 					=	(int) Input::json()->get('city_id');
		$finder_name 				=	Input::json()->get('finder_name');

		$customer_id				= 	(Input::has('customer_id') && Input::json()->get('customer_id') != '') ? Input::json()->get('customer_id') : "";
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

			'finder_id' 			=>		$finder_id,
			'city_id'				=>		$city_id, 
			'finder_name' 			=>		$finder_name,

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
			'additional_info'		=>		$additional_info
			);

		// return $booktrialdata;
		$booktrial = new Booktrial($booktrialdata);
		$booktrial->_id = $booktrialid;
		$trialbooked = $booktrial->save();

		if($trialbooked){
			$sndInstantEmailCustomer		= 	$this->customermailer->manualBookTrial($booktrialdata);
			$sndInstantSmsCustomer			=	$this->customersms->manualBookTrial($booktrialdata);
		}

		$resp 	= 	array('status' => 200,'message' => "Book a Trial");
		return Response::json($resp,200);		
	}


	public function manual2ndBookTrial() {


		$data = Input::json()->all();

		if(empty($data['customer_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_id");
		}

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_name");
		}

		if(empty($data['customer_email'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_email");
		}

		if(empty($data['customer_phone'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_phone");
		}

		if(empty($data['finder_ids'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_ids");
		}

		if(empty($data['finder_names'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_names");
		}

		if(empty($data['city_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - city_id");
		}

		// if(empty($data['preferred_location'])){
		// 	return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_location");
		// }

		// if(empty($data['preferred_day'])){
		// 	return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_day");
		// }

		// if(empty($data['preferred_time'])){
		// 	return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_time");
		// }


		// return $data	= Input::json()->all();
		$finder_ids 				= 	Input::json()->get('finder_ids');
		$finder_names 				=	Input::json()->get('finder_names');
		$city_id 					=	(int) Input::json()->get('city_id');

		$customer_id				= 	(Input::has('customer_id') && Input::json()->get('customer_id') != '') ? Input::json()->get('customer_id') : "";
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


}
