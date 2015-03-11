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


	public function getBookTrial($finderid,$date = null){
		$finderid 	= 	(int) $finderid;
		$items 		= 	Booktrial::where('finder_id', '=', $finderid)
		->where('service_name', '=', 'gyms' )
		->where('schedule_date', '=', new DateTime($date) )
		->get(array('customer_name','service_name','finder_id','schedule_date','sechedule_slot'));
		return $items;
	}

	/**
	 * Book Scheduled Book A Trial.
	 *
	 */

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
		// $booktrialid 						=	1;
		// $customer_id 						=	Input::json()->get('customer_id'); 
		// $customer_name 						=	Input::json()->get('customer_name'); 
		// $customer_email 					=	Input::json()->get('customer_email'); 
		// $customer_phone 					=	Input::json()->get('customer_phone');
		
		$finderid 							= 	(int) Input::json()->get('finder_id');
		$finder 							= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))
														->where('_id','=',$finderid)
														->first()->toArray();
		//return var_dump($finder)	;									
		$finder_name						= 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
		$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
		$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
		$finder_lat 						= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
		$finder_lon 						= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
		$city_id 							=	(int) $finder['city_id'];

		$finder_vcc_email					= 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
		$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
		$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
		$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_no']) && $finder['finder_poc_for_customer_no'] != '') ? $finder['finder_poc_for_customer_no'] : "";

		$device_id							= 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";

		$booktrialdata = array(
			'customer_id' 					=>		Input::json()->get('customer_id'), 
			'customer_name' 				=>		Input::json()->get('customer_name'), 
			'customer_email' 				=>		Input::json()->get('customer_email'), 
			'customer_phone' 				=>		Input::json()->get('customer_phone'),

			'finder_id' 					=>		$finderid,
			'finder_name' 					=>		$finder_name,
			'finder_location' 				=>		$finder_location,
			'finder_address' 				=>		$finder_address,
			'finder_lat'		 			=>		$finder_lat,
			'finder_lon'		 			=>		$finder_lon,
			'city_id'						=>		$city_id,
			'finder_vcc_email' 				=>		$finder_vcc_email,
			'finder_vcc_mobile' 			=>		$finder_vcc_mobile,
			'finder_poc_for_customer_name'	=>		$finder_poc_for_customer_name,
			'finder_poc_for_customer_no'	=>		$finder_poc_for_customer_no,

			'service_name'					=>		Input::json()->get('service_name'),
			'schedule_date'					=>		date('Y-m-d 00:00:00', strtotime($slot_date)),
			'schedule_date_time'			=>		Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_time)->toDateTimeString(),
			'sechedule_slot'				=>		Input::json()->get('sechedule_slot'),
			'going_status'					=>		1,
			'code'							=>		$booktrialid.str_random(8),
			'device_id'						=>		$device_id,
			'booktrial_type'				=>		'auto'	
			);

		//return $booktrialdata;
		$booktrial = new Booktrial($booktrialdata);
		$booktrial->_id = $booktrialid;
		$trialbooked = $booktrial->save();

		if($trialbooked = true){

			//Send Instant (Email) To Customer & Finder
			$sndInstantEmailCustomer	= 	$this->customermailer->bookTrial($booktrialdata);
			$sndInstantEmailFinder		= 	$this->findermailer->bookTrial($booktrialdata);
			//$sndInstantSmsCustomer		=	$this->customersms->bookTrial($booktrialdata);
			//$sndInstantSmsFinder		=	$this->findersms->bookTrial($booktrialdata);



			//#############  TESTING FOR 1 MIN START ##############
			//Send Reminder Notiication (Email) Before 1 Min To Customer used for testing
			// $sndReminderEmailNotificaitonBefore1MinCustomer  	= 	$this->customermailer->bookTrialReminder($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndReminderSmsNotificaitonCustomer					=	$this->customersms->bookTrialReminder($booktrialdata, $delayReminderTimeBefore1Min);
			// $sndReminderSmsNotificaitonFinder					=	$this->findersms->bookTrialReminder($booktrialdata, $delayReminderTimeBefore1Min);

			//#############  TESTING FOR 1 MIN END ##############


			if($oneHourDiff >= 12){
				//Send Reminder Notiication (Email) Before 12 Hour To To Customer & Finder
				//$sndReminderEmailNotificaitonBefore12HourCustomer  	= 	$this->customermailer->bookTrialReminder($booktrialdata,$delayReminderTimeBefore12Hour);

				//Send Reminder Notiication (SMS) To Customer & Finder need to write 
				//send sms to customer Viva twilio
				//send sms to finder Viva Curl APi

				//Queue::later(Carbon::now()->addMinutes(2),'WriteFile', array( 'string' => 'new testpushqueue delay by 2 min time -- '.time()));
				
			}

			if($oneHourDiff >= 1){

				//Send Reminder Notiication (Email) Before 1 Hour To Customer & Finder
				//$sndReminderEmailNotificaitonBefore1HourCustomer  	= 	$this->customermailer->bookTrialReminder($booktrialdata,$delayReminderTimeBefore1Hour);

				//Send Reminder Notiication (SMS) To Customer & Finder need to write 
				//send sms to customer
				//send sms to finder

			}


			//Send Post Trial Notificaiton After 2 Hours Need to Write



		}

		$resp 	= 	array('status' => 200,'message' => "Book a Trial");
		return Response::json($resp);	
	}


	public function sendSMS($smsdata){

		$to = $smsdata['send_to'];
		$message = $smsdata['message_body'];
		$live_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=india123&type=0&dlr=1&destination=" . urlencode($to) . "&source=fitter&message=" . urlencode($message);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $live_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);
	}

	public function sendEmail($emaildata){


		$email_template 		= 	$emaildata['email_template'];
		$email_template_data 	= 	$emaildata['email_template_data'];
		$reciver_name 			= 	(isset($email_template_data['name'])) ? ucwords($email_template_data['name']) : 'Team Fitternity';
		$to 					= 	$emaildata['to'];
		$bcc_emailids 			= 	$emaildata['bcc_emailds'];
		$email_subject 			= 	ucfirst($emaildata['email_subject']);		
		$send_bcc_status 		= 	$emaildata['send_bcc_status'];
		
		if($send_bcc_status == 1){
			Mail::send($email_template, $email_template_data, function($message) use ($to,$reciver_name,$bcc_emailids,$email_subject){
				$message->to($to, $reciver_name)->bcc($bcc_emailids)->subject($email_subject);
			});			
		}else{
			Mail::send($email_template, $email_template_data, function($message) use ($to,$reciver_name,$bcc_emailids,$email_subject){
				$message->to($to, $reciver_name)->subject($email_subject);
			});			
		}
	}

	/**
	 * Booked Manual Book A Trial.
	 *
	 */

	public function manualBookTrial() {

		// return $data	= Input::json()->all();

		$booktrialid 				=	Booktrial::max('_id') + 1;
		$finder_id 					= 	(int) Input::json()->get('finder_id');
		$customer_id				= 	(Input::has('customer_id') && Input::json()->get('customer_id') != '') ? (int) Input::json()->get('customer_id') : "";
		$device_id					= 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
		$city_id 					=	(int) Input::json()->get('city_id');
		$booktrialdata = array(
			'finder_id' 			=>		$finder_id,
			'finder_name' 			=>		Input::json()->get('finder'),
			'city_id'				=>		$city_id, 

			'customer_id' 			=>		$customer_id, 
			'customer_name' 		=>		Input::json()->get('name'), 
			'customer_email' 		=>		Input::json()->get('email'), 
			'customer_phone' 		=>		Input::json()->get('phone'),
			'preferred_location'	=>		Input::json()->get('location'),
			'preferred_service'		=>		Input::json()->get('service'),
			'preferred_day'			=>		Input::json()->get('preferred_day'),
			'preferred_time'		=>		Input::json()->get('preferred_time'),
			'device_id'				=>		$device_id,
			'booktrial_type'		=>		'manual'
			);



		$emaildata = array(
			'email_template' 		=> 	'emails.customer.manualbooktrial', 
			'email_template_data' 	=> 	$booktrialdata, 
			'to'					=> 	Config::get('mail.to_neha'), 
			'bcc_emailds' 			=> 	Config::get('mail.bcc_emailds_book_trial'), 
			'email_subject' 		=> 	'Request For Manual Book a Trial',
			'send_bcc_status' 		=> 	1 
			);
		$this->sendEmail($emaildata);

		$smsdata = array(
			'send_to' => Input::json()->get('phone'),
			'message_body'=>'Hi '.Input::json()->get('name').', Thank you for the request to manual book a trial at '. Input::json()->get('finder') .'. We will call you shortly to arrange a time. Regards - Team Fitternity'
			);

		$this->sendSMS($smsdata);
		
		$booktrial = new Booktrial($booktrialdata);
		$booktrial->_id = $booktrialid;
		$trialbooked = $booktrial->save();
		$resp 	= 	array('status' => 200,'message' => "Book a Trial");
		return Response::json($resp);		
	}

	public function extraBookTrial() {
		$data = array(
			'capture_type' 			=>		'extrabook_trial',
			'name' 					=>		Input::json()->get('name'), 
			'email' 				=>		Input::json()->get('email'), 
			'phone' 				=>		Input::json()->get('phone'),
			'finder' 				=>		implode(",",Input::json()->get('vendor')),
			'location' 				=>		Input::json()->get('location'),
			'service'				=>		Input::json()->get('service'),
			'preferred_time'		=>		Input::json()->get('preferred_time'),
			'preferred_day'			=>		Input::json()->get('preferred_day'),
			'date' 					=>		date("h:i:sa")
			);
		$emaildata = array(
			'email_template' 		=> 	'emails.finder.booktrial', 
			'email_template_data' 	=> 	$data, 
			'to'					=> 	Config::get('mail.to_neha'), 
			'bcc_emailds' 			=> 	Config::get('mail.bcc_emailds_book_trial'), 
			'email_subject' 		=> 	'Request For 2nd Book a Trial',
			'send_bcc_status' 		=> 	1 
			);
		$this->sendEmail($emaildata);

		$smsdata = array(
			'send_to' => Input::json()->get('phone'),
			'message_body'=>'Hi '.Input::json()->get('name').', Thank you for the request to book a trial at '. implode(",",Input::json()->get('vendor')) .'. We will call you shortly to arrange a time. Regards - Team Fitternity'
			);
		$this->sendSMS($smsdata);


		$storecapture = Capture::create($data);
		$resp = array('status' => 200,'message' => "Book a Trial");
		return Response::json($resp);          

		// return $data	= Input::json()->all();

		$booktrialid 				=	Booktrial::max('_id') + 1;
		$finder_id 					= 	(int) Input::json()->get('finder_id');
		$customer_id				= 	(Input::has('customer_id') && Input::json()->get('customer_id') != '') ? (int) Input::json()->get('customer_id') : "";
		$device_id					= 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
		$city_id 					=	(int) Input::json()->get('city_id');
		$booktrialdata = array(
			'finder_id' 			=>		$finder_id,
			'finder_name' 			=>		Input::json()->get('finder'),
			'city_id'				=>		$city_id, 

			'customer_id' 			=>		$customer_id, 
			'customer_name' 		=>		Input::json()->get('name'), 
			'customer_email' 		=>		Input::json()->get('email'), 
			'customer_phone' 		=>		Input::json()->get('phone'),
			'preferred_location'	=>		Input::json()->get('location'),
			'preferred_service'		=>		Input::json()->get('service'),
			'preferred_day'			=>		Input::json()->get('preferred_day'),
			'preferred_time'		=>		Input::json()->get('preferred_time'),
			'device_id'				=>		$device_id,
			'booktrial_type'		=>		'manual'
			);



		$emaildata = array(
			'email_template' 		=> 	'emails.customer.manualbooktrial', 
			'email_template_data' 	=> 	$booktrialdata, 
			'to'					=> 	Config::get('mail.to_neha'), 
			'bcc_emailds' 			=> 	Config::get('mail.bcc_emailds_book_trial'), 
			'email_subject' 		=> 	'Request For Manual Book a Trial',
			'send_bcc_status' 		=> 	1 
			);
		$this->sendEmail($emaildata);

		$smsdata = array(
			'send_to' => Input::json()->get('phone'),
			'message_body'=>'Hi '.Input::json()->get('name').', Thank you for the request to manual book a trial at '. Input::json()->get('finder') .'. We will call you shortly to arrange a time. Regards - Team Fitternity'
			);

		$this->sendSMS($smsdata);
		
		$booktrial = new Booktrial($booktrialdata);
		$booktrial->_id = $booktrialid;
		$trialbooked = $booktrial->save();
		$resp 	= 	array('status' => 200,'message' => "Book a Trial");
		return Response::json($resp);	      
	}


}
