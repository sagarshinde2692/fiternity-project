<?PHP

/** 
 * ControllerName : SchedulebooktrialsController.
 * Maintains a list of functions used for SchedulebooktrialsController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as Mailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Sms\FinderSms as FinderSms;

class SchedulebooktrialsController extends \BaseController {

	protected $mailer;
	protected $customersms;
	protected $findersms;

	public function __construct(Mailer $mailer, CustomerSms $customersms, FinderSms $findersms) {
		//parent::__construct();	
		$this->mailer			=	$mailer;
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
		$currentDateTime 		=	Carbon::now();
		$finderid 				= 	(int) $finderid;
		$date 					=  	($date == null) ? Carbon::now() : $date;
		$timestamp 				= 	strtotime($date);
		$weekday 				= 	strtolower(date( "l", $timestamp));

		// echo "$date  --- $timestamp -- $weekday";exit;
		//finder sechedule trials
		$items = Schedulebooktrial::where('finder_id', '=', $finderid)->where('weekday', '=', $weekday)->get(array('finder_id','weekday','name','slots'))->toArray();
		$secheduletrials = array();
		foreach ($items as $item) {
			$trial = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' =>  $item['weekday']); 
			$slots = array();
			foreach ($item['slots'] as $slot) {
				$booktrialslotcnt = Booktrial::where('finder_id', '=', $finderid)
											->where('service_name', '=', $item['name'])
											->where('schedule_date', '=', new DateTime($date) )
											->where('sechedule_slot', '=', $slot['slot_time'])
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
			array_push($secheduletrials, $trial);
		}						
		return $secheduletrials;
	}



	public function bookTrial(){

		//return $data	= Input::json()->all();
		//its helpful to send any kind for dateformat date time as srting or iso formate timezond
		$slot_times 						=	explode('-',Input::json()->get('sechedule_slot'));
		$slot_date 							=	date('d-m-Y', strtotime(Input::json()->get('schedule_date'))); 
		$schedule_date_time 				=	strtoupper($slot_date ." ".head($slot_times));

		$currentDateTime 					=	Carbon::now();
		$scheduleDateTime 					=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->subMinutes(1);
		$delayReminderTimeBefore1Min 		=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->subMinutes(1);
		$delayReminderTimeBefore1Hour 		=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->subMinutes(60);
		$delayReminderTimeBefore12Hour		=	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->subMinutes(60 * 12);
		$oneHourDiff 						= 	$currentDateTime->diffInHours($delayReminderTimeBefore1Hour, false);  
		$twelveHourDiff 					= 	$currentDateTime->diffInHours($delayReminderTimeBefore12Hour, false);  

		//echo "<br>currentDateTime : $currentDateTime, <br>scheduleDateTime : $scheduleDateTime, <br>Before1Min : $delayReminderTimeBefore1Min, <br>Before1Hour : $delayReminderTimeBefore1Hour, <br>Before12Hour : $delayReminderTimeBefore12Hour <br>";
		//return  "oneHourDiff  -- $oneHourDiff   ,  twelveHourDiff  -- $twelveHourDiff";
		
		$booktrialid 						=	Booktrial::max('_id') + 1;
		// $customer_id 						=	Input::json()->get('customer_id'); 
		// $customer_name 						=	Input::json()->get('customer_name'); 
		// $customer_email 					=	Input::json()->get('customer_email'); 
		// $customer_phone 					=	Input::json()->get('customer_phone');
		
		$finderid 							= 	(int) Input::json()->get('finder_id');
		$finder 							= 	Finder::findOrFail(1)->toArray();
		$finder_name						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
		$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
		$finder_lat 						= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
		$finder_lon 						= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";

		$finder_vcc_email					= 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
		$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
		$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
		$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_no']) && $finder['finder_poc_for_customer_no'] != '') ? $finder['finder_poc_for_customer_no'] : "";


		$booktrialdata = array(
			'customer_id' 					=>		Input::json()->get('customer_id'), 
			'customer_name' 				=>		Input::json()->get('customer_name'), 
			'customer_email' 				=>		Input::json()->get('customer_email'), 
			'customer_phone' 				=>		Input::json()->get('customer_phone'),

			'finder_id' 					=>		$finderid,
			'finder_name' 					=>		$finder_name,
			'finder_address' 				=>		$finder_address,
			'finder_lat'		 			=>		$finder_lat,
			'finder_lon'		 			=>		$finder_lon,
			'finder_vcc_email' 				=>		$finder_vcc_email,
			'finder_vcc_mobile' 			=>		$finder_vcc_mobile,
			'finder_poc_for_customer_name'	=>		$finder_poc_for_customer_name,
			'finder_poc_for_customer_no'	=>		$finder_poc_for_customer_no,

			'service_name'					=>		Input::json()->get('service_name'),
			'schedule_date'					=>		date('Y-m-d 00:00:00', strtotime($slot_date)),
			'schedule_date_time'			=>		Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->toDateTimeString(),
			'sechedule_slot'				=>		Input::json()->get('sechedule_slot'),
			'going_status'					=>		1,
			'code'							=>		$booktrialid.str_random(8)
			);

		//return $booktrialdata;
		$booktrial = new Booktrial($booktrialdata);
		$booktrial->_id = $booktrialid;
		$trialbooked = $booktrial->save();

		if($trialbooked){

			//Send Instant (Email) To Customer & Finder
			$sndInstantEmailCustomerFinder	= 	$this->mailer->bookTrial($booktrialdata);

			// $sndInstantSmsNotificaitonCustomer			=	$this->customersms->bookTrial($booktrialdata);
			// $sndInstantSmsNotificaitonFinder			=	$this->findersms->bookTrial($booktrialdata);



			//#############  TESTING FOR 1 MIN START ##############
			//Send Reminder Notiication (Email) Before 1 Min To Customer used for testing
			// $sndReminderEmailNotificaitonBefore1MinCustomer  	= 	$this->mailer->bookTrialReminder($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndReminderSmsNotificaitonCustomer					=	$this->customersms->bookTrialReminder($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndReminderSmsNotificaitonFinder					=	$this->findersms->bookTrialReminder($booktrialdata, $delayReminderTimeBefore1Min);

			//#############  TESTING FOR 1 MIN END ##############


			if($oneHourDiff >= 12){
				//Send Reminder Notiication (Email) Before 12 Hour To To Customer & Finder
				//$sndReminderEmailNotificaitonBefore12HourCustomer  	= 	$this->mailer->bookTrialReminder($booktrialdata,$delayReminderTimeBefore12Hour);

				//Send Reminder Notiication (SMS) To Customer & Finder need to write 
				//send sms to customer Viva twilio
				//send sms to finder Viva Curl APi

				//Queue::later(Carbon::now()->addMinutes(2),'WriteFile', array( 'string' => 'new testpushqueue delay by 2 min time -- '.time()));
				
			}

			if($oneHourDiff >= 1){

				//Send Reminder Notiication (Email) Before 1 Hour To Customer & Finder
				//$sndReminderEmailNotificaitonBefore1HourCustomer  	= 	$this->mailer->bookTrialReminder($booktrialdata,$delayReminderTimeBefore1Hour);

				//Send Reminder Notiication (SMS) To Customer & Finder need to write 
				//send sms to customer
				//send sms to finder

			}


			//Send Post Trial Notificaiton After 2 Hours Need to Write



		}

		$resp 	= 	array('status' => 200,'message' => "Book a Trial");
		return Response::json($resp);	
	}

	public function getBookTrial($finderid,$date = null){
		$finderid 	= 	(int) $finderid;
		$items 		= 	Booktrial::where('finder_id', '=', $finderid)
		->where('service_name', '=', 'gyms' )
		->where('schedule_date', '=', new DateTime($date) )
		->get(array('customer_name','service_name','finder_id','schedule_date','sechedule_slot'));
		return $items;
	}


}
