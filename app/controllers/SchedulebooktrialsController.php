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
		$finderid 	= 	(int) $finderid;
		$date 		=  	($date == null) ? Carbon\Carbon::now() : $date;
		$timestamp 	= 	strtotime($date);
		$weekday 	= 	strtolower(date( "l", $timestamp));

		// echo "$date  --- $timestamp -- $weekday";
		//finder sechedule trials
		$items = Schedulebooktrial::where('finder_id', '=', $finderid)
		->where('weekday', '=', $weekday)
		->get(array('finder_id','weekday','name','slots'))->toArray();
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
				$slot_status = ($slot['limit'] > $booktrialslotcnt) ? "available" : "full";
				array_set($slot, 'booked', $booktrialslotcnt);
				array_set($slot, 'status', $slot_status);
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
		$slot_times 			=	explode('-',Input::json()->get('sechedule_slot'));
		$schedule_date_time 	=	strtoupper(Input::json()->get('schedule_date')." ".head($slot_times));

		// $date 					=	Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time);
		// $reminder_date1 		=	Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->subMinutes(60);
		// $reminder_date2 		=	Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->subMinutes(60 * 12);
		// return "$date  --   $reminder_date1   --  $reminder_date2";
		
		$booktrialdata = array(
			'customer_id' 			=>		Input::json()->get('customer_id'), 
			'customer_name' 		=>		Input::json()->get('customer_name'), 
			'customer_email' 		=>		Input::json()->get('customer_email'), 
			'customer_phone' 		=>		Input::json()->get('customer_phone'),
			'finder_name' 			=>		'Send Instant Notiication To Customer'.time(),
			'finder_id' 			=>		Input::json()->get('finder_id'),
			'service_name'			=>		Input::json()->get('service_name'),
			'schedule_date'			=>		date('Y-m-d 00:00:00', strtotime(Input::json()->get('schedule_date'))),
			'schedule_date_time'	=>		Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time),
			'sechedule_slot'		=>		Input::json()->get('sechedule_slot'),
			'going_status'			=>		1
			);

		//return $booktrialdata;
		$booktrial = new Booktrial($booktrialdata);
		$booktrial->_id = Booktrial::max('_id') + 1;
		$booktrial->save();

		//Send Instant Notiication To Customer
		$sndNotificaiton  = 	$this->mailer->bookTrial($booktrialdata);
		
		//Send Reminder Notiication Before 12 Hour To Customer
		$booktrialdata2 = array(
			'customer_id' 			=>		Input::json()->get('customer_id'), 
			'customer_name' 		=>		Input::json()->get('customer_name'), 
			'customer_email' 		=>		Input::json()->get('customer_email'), 
			'customer_phone' 		=>		Input::json()->get('customer_phone'),
			'finder_name' 			=>		'test finder name Send Reminder Notiication Before 12 Hour To Customer'.time(),
			'finder_id' 			=>		Input::json()->get('finder_id'),
			'service_name'			=>		Input::json()->get('service_name'),
			'schedule_date'			=>		date('Y-m-d 00:00:00', strtotime(Input::json()->get('schedule_date'))),
			'schedule_date_time'	=>		Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time),
			'sechedule_slot'		=>		Input::json()->get('sechedule_slot'),
			'going_status'			=>		1
			);
		$date = Carbon::now()->addMinutes(1);
		Queue::later($date, 'CustomerMailer@bookTrial', $booktrialdata2);

		//Send Reminder Notiication Before 1 Hour To Customer
		$booktrialdata1 = array(
			'customer_id' 			=>		Input::json()->get('customer_id'), 
			'customer_name' 		=>		Input::json()->get('customer_name'), 
			'customer_email' 		=>		Input::json()->get('customer_email'), 
			'customer_phone' 		=>		Input::json()->get('customer_phone'),
			'finder_name' 			=>		'test finder name Send Reminder Notiication Before 1 Hour To Customer'.time(),
			'finder_id' 			=>		Input::json()->get('finder_id'),
			'service_name'			=>		Input::json()->get('service_name'),
			'schedule_date'			=>		date('Y-m-d 00:00:00', strtotime(Input::json()->get('schedule_date'))),
			'schedule_date_time'	=>		Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time),
			'sechedule_slot'		=>		Input::json()->get('sechedule_slot'),
			'going_status'			=>		1
			);
		$date = Carbon::now()->addMinutes(2);
		Queue::later($date, 'CustomerMailer@bookTrial', $booktrialdata1);

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
