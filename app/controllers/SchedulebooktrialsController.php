<?PHP

/** 
 * ControllerName : SchedulebooktrialsController.
 * Maintains a list of functions used for SchedulebooktrialsController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use Acme\Mailers\CustomerMailer as Mailer;

class SchedulebooktrialsController extends \BaseController {

	protected $mailer;

	public function __construct(Mailer $mailer) {
		//parent::__construct();	
		$this->mailer = $mailer;
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
		
		$booktrialdata = array(
			'customer_id' 			=>		Input::json()->get('customer_id'), 
			'customer_name' 		=>		Input::json()->get('customer_name'), 
			'customer_email' 		=>		Input::json()->get('customer_email'), 
			'customer_phone' 		=>		Input::json()->get('customer_phone'),
			'finder_id' 			=>		Input::json()->get('finder_id'),
			'finder_name' 			=>		Input::json()->get('finder_name'),
			'finder_email' 			=>		Input::json()->get('finder_email'),
			'service_name'			=>		Input::json()->get('service_name'),
			'schedule_date'			=>		date('Y-m-d 00:00:00', strtotime($slot_date)),
			'schedule_date_time'	=>		Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->toDateTimeString(),
			'sechedule_slot'		=>		Input::json()->get('sechedule_slot'),
			'going_status'			=>		1
			);

		//return $booktrialdata;
		$booktrial = new Booktrial($booktrialdata);
		$booktrial->_id = Booktrial::max('_id') + 1;
		$trialbooked = $booktrial->save();

		if($trialbooked){
			//Send Instant Notiication To Customer
			$sndInstantNotificaiton  				= 	$this->mailer->bookTrial($booktrialdata);

			//Send Reminder Notiication Before 1 Min To Customer
			$sndReminderNotificaitonBefore1Min  	= 	$this->mailer->bookTrialReminder($booktrialdata,$delayReminderTimeBefore1Min);

			//Send Reminder Notiication Before 1 Hour To Customer
			if($oneHourDiff >= 1){
				$sndReminderNotificaitonBefore1Hour  	= 	$this->mailer->bookTrialReminder($booktrialdata,$delayReminderTimeBefore1Hour);
			}

			//Send Reminder Notiication Before 12 Hour To Customer
			if($oneHourDiff >= 12){
				$sndReminderNotificaitonBefore12Hour  	= 	$this->mailer->bookTrialReminder($booktrialdata,$delayReminderTimeBefore12Hour);
			}
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
