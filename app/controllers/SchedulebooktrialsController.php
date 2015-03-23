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

class SchedulebooktrialsController extends \BaseController {

	protected $customermailer;
	protected $findermailer;
	protected $customersms;
	protected $findersms;

	public function __construct(CustomerMailer $customermailer, FinderMailer $findermailer, CustomerSms $customersms, FinderSms $findersms) {
		//parent::__construct();	
		$this->customermailer	=	$customermailer;
		$this->findermailer		=	$findermailer;
		$this->customersms 		=	$customersms;
		$this->findersms 		=	$findersms;
	}

	/**
	 * Display the specified blogcategorytag.
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
		$items = Schedulebooktrial::where('finder_id', '=', $finderid)->where('weekday', '=', $weekday)->get(array('finder_id','weekday','name','slots'))->toArray();
		$scheduletrials = array();
		foreach ($items as $item) {
			$trial = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' =>  $item['weekday']); 
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
				$slot_datetime_pass_status  	= 	($currentDateTime->diffInHours($scheduleDateTime, false) > 1) ? false : true;
				array_set($slot, 'passed', $slot_datetime_pass_status); 

				//echo "<br>finderid : $finderid  --- schedule_date : $date servicename : $item[name] -- slot_time : $slot[slot_time] --  booktrialslotcnt : $booktrialslotcnt";
				array_push($slots, $slot);
			}
			$trial['slots'] = $slots;
			array_push($scheduletrials, $trial);
		}						
		return $scheduletrials;
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
			$delayReminderTimeBefore12Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
			$delayReminderTimeAfter2Hour		=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 2);
			$oneHourDiff 						= 	$currentDateTime->diffInHours($delayReminderTimeBefore1Hour, false);  
			$twelveHourDiff 					= 	$currentDateTime->diffInHours($delayReminderTimeBefore12Hour, false);  

			// var_dump($delayReminderTimeBefore1Hour);
			// echo "<br>currentDateTime : $currentDateTime, <br>scheduleDateTime : $scheduleDateTime, <br>Before1Min : $delayReminderTimeBefore1Min, <br>Before1Hour : $delayReminderTimeBefore1Hour, <br>Before12Hour : $delayReminderTimeBefore12Hour <br>";
			// return  "oneHourDiff  -- $oneHourDiff   ,  twelveHourDiff  -- $twelveHourDiff";
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


			$booktrialdata = array(
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
				'booktrial_type'				=>		'auto'	
			);


			// return $booktrialdata;
			$booktrial = new Booktrial($booktrialdata);
			$booktrial->_id = $booktrialid;
			$trialbooked = $booktrial->save();

		} catch(ValidationException $e){
   
        	return array('status' => 500,'message' => $e->getMessage());
		}

		if($trialbooked = true){

			$customer_email_messageids 	=  $finder_email_messageids  =	$customer_sms_messageids  = $customer_sms_messageids  = $finer_sms_messageids  = array();

			//Send Instant (Email) To Customer & Finder
			$sndInstantEmailCustomer		= 	$this->customermailer->bookTrial($booktrialdata);
			$sndInstantSmsCustomer			=	$this->customersms->bookTrial($booktrialdata);

			$sndInstantEmailFinder			= 	$this->findermailer->bookTrial($booktrialdata);
			$sndInstantSmsFinder			=	$this->findersms->bookTrial($booktrialdata);

			$customer_email_messageids['instant'] 	= 	$sndInstantEmailCustomer;
			$customer_sms_messageids['instant'] 	= 	$sndInstantSmsCustomer;
			$finder_email_messageids['instant'] 	= 	$sndInstantEmailFinder;
			$finer_sms_messageids['instant'] 		= 	$sndInstantSmsFinder;


			//#############  TESTING FOR 1 MIN START ##############
			//Send Reminder Notiication (Email) Before 1 Min To Customer used for testing
			// $sndBefore1MinEmailCustomer		= 	$this->customermailer->bookTrialReminderBefore1Min($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndBefore1MinSmsCustomer		=	$this->customersms->bookTrialReminderBefore1Min($booktrialdata, $delayReminderTimeBefore1Min);

			//#############  TESTING FOR 1 MIN END ##############

			if($twelveHourDiff >= 12){
				//Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
				$sndBefore12HourEmailCustomer				= 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
				$customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
				// $sndBefore12HourSmsCustomer			=	$this->customersms->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
				// $sms_messageids['before12hour'] 	= 	$sndBefore12HourSmsCustomer;

			}

			if($oneHourDiff >= 1){
				//Send Reminder Notiication (Sms) Before 1 Hour To Customer
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
			$queueddata 	= 	array('customer_emailqueuedids' => $customer_email_messageids, 'customer_smsqueuedids' => $customer_sms_messageids,
									  'finder_emailqueuedids' => $finder_email_messageids, 'finder_smsqueuedids' => $finer_sms_messageids);
			$trialbooked 	= 	$booktrial->update($queueddata);
		}

		$resp 	= 	array('status' => 200,'message' => "Book a Trial");
		return Response::json($resp);	
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

		if(empty($data['preferred_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_location");
		}

		if(empty($data['preferred_day'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_day");
		}

		if(empty($data['preferred_time'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_time");
		}

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
		
		$booktrialdata = array(
			'finder_id' 			=>		$finder_id,
			'city_id'				=>		$city_id, 
			'finder_name' 			=>		$finder_name,

			'customer_id' 			=>		$customer_id, 
			'customer_name' 		=>		$customer_name, 
			'customer_email' 		=>		$customer_email, 
			'customer_phone' 		=>		$customer_phone,

			'preferred_location'	=>		$preferred_location,
			'preferred_service'		=>		$preferred_service,
			'preferred_day'			=>		$preferred_day,
			'preferred_time'		=>		$preferred_time,
			'device_id'				=>		$device_id,
			'going_status'			=>		0,
			'going_status_txt'		=>		'not fixed',
			'booktrial_type'		=>		'manual'
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
		return Response::json($resp);		
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

		if(empty($data['preferred_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_location");
		}

		if(empty($data['preferred_day'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_day");
		}

		if(empty($data['preferred_time'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - preferred_time");
		}


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
		
		$booktrialdata = array(
				'finder_ids' 			=>		implode(", ",$finder_ids),
				'city_id'				=>		$city_id, 
				'finder_names' 			=>		implode(", ",$finder_names),

				'customer_id' 			=>		$customer_id, 
				'customer_name' 		=>		$customer_name, 
				'customer_email' 		=>		$customer_email, 
				'customer_phone' 		=>		$customer_phone,

				'preferred_location'	=>		$preferred_location,
				'preferred_service'		=>		$preferred_service,
				'preferred_day'			=>		$preferred_day,
				'preferred_time'		=>		$preferred_time,
				'device_id'				=>		$device_id,
				'going_status'			=>		0,
				'going_status_txt'		=>		'not fixed',
				'booktrial_type'		=>		'2ndmanual'
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
		return Response::json($resp);          

	}


}
