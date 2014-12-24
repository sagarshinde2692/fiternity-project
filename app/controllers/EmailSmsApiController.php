<?php

use Hugofirth\Mailchimp\Facades\MailchimpWrapper;

class EmailSmsApiController extends \BaseController {

	protected $reciver_email = "mailus@fitternity.com";
	protected $reciver_name = "Leads From Website";

	// public function __construct()
	// {
	// 	$this->afterFilter(function($response)
	// 	{
	// 		header("Access-Control-Allow-Origin: *");
	// 		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
	// 		return $response;
	// 	});
	// }

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
		$email_lists			= 	Config::get('mail.cc_emailids');
		$email_template 		= 	$emaildata['email_template'];
		$email_template_data 	= 	$emaildata['email_template_data'];
		$reciver_email 			= 	$emaildata['reciver_email'];
		$reciver_name 			= 	$emaildata['reciver_name'];
		$reciver_subject 		= 	$emaildata['reciver_subject'];
		
		array_push($email_lists,$reciver_email);

		foreach ($email_lists as $email){			
			Mail::send($email_template, $email_template_data, function($message) use ($email,$reciver_name,$reciver_subject){

				$message->to($email, $reciver_name)->subject($reciver_subject);

			});
		}

	}


	public function testemail(){

		$email_template = 'emails.testemail';
		$email_template_data = array();

		Mail::send($email_template, $email_template_data, function($message){
				$to = 'sanjay.id7@gmail.com';
				$reciver_name = 'sanjay sahu';
				$cc_emailids = array('sanjay.fitternity@gmail.com','info@fitternity.com');
				$reciver_subject = 'subject of test email';
				//$message->to($to, $reciver_name)->cc($cc_emailids)->bcc($cc_emailids)->subject($reciver_subject);
				$message->to($to, $reciver_name)->bcc($cc_emailids)->subject($reciver_subject);

			});

	}

	public function RequestCallback() {
		date_default_timezone_set("Asia/Kolkata");
		$emaildata = array(
			'email_template' => 'emails.callback', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'date' => date("h:i:sa")        
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'Request A Callback' 
			);

		$this->sendEmail($emaildata);
		$smsdata = array(
			'send_to' => Input::json()->get('phone'),
			'message_body'=>Input::json()->get('name').', Thanks for your request for a call back. We\'ll call you within 24 hours. Team Fitternity',
			);
		$this->sendSMS($smsdata);
		$resp = array(
			'status' => 200,
			'message' => "Recieved the Request"
			);
		return Response::json($resp);
	}

	public function BookTrail() {
		date_default_timezone_set("Asia/Kolkata");
		$emaildata = array(
			'email_template' => 'emails.finder.booktrial', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'finder' => Input::json()->get('finder'),
				'location' => Input::json()->get('location'),
				'service'	=> Input::json()->get('service'),
				'date' => date("h:i:sa")
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'Request For Book a Trail' 
			);
		$this->sendEmail($emaildata);

		$smsdata = array(
			'send_to' => Input::json()->get('phone'),
			'message_body'=>Input::json()->get('name').', Thanks for your request to book a trial at '. Input::json()->get('finder') .'. We will call you within 24 hours to arrange a time. Team Fitternity',
			);

		$this->sendSMS($smsdata);
		$resp = array(
			'status' => 200,
			'message' => "Book a Trial"
			);
		return Response::json($resp);		
	}

	public function FinderLead(){
		$emaildata = array(
			'email_template' => 'emails.finder.finderlead', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'location' => Input::json()->get('location'),
				'date' => Input::json()->get('date'),
				'findertitle' => Input::json()->get('findertitle'),
				'finderaddress' => Input::json()->get('finderaddress')
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'lead generator popup' 
			);
		$this->sendEmail($emaildata);

		$smsdata = array(
			// 'send_to' => Input::json()->get('phone'), // Number of customer 
			'send_to' => '9870747016',
			'message_body'=>Input::json()->get('name').', Thanks for your enquiry about '.Input::json()->get('findertitle').'. We will call you within 24 hours. Team Fitternity',
			);
		$this->sendSMS($smsdata);
	}


	public function SubscribeNewsletter(){
		$list_id = 'd2a433c826';
		//$list_id = 'cd8d82a9d0';
		$email_address = Input::json()->get('email');
		$response =  MailchimpWrapper::lists()->subscribe($list_id, array('email'=>$email_address));
		return $response;
	}

	public function fivefitnesscustomer(){
		$reciver_email = "mailus@fitternity.com";
		$reciver_name = "Leads From 5-fitness page";
		date_default_timezone_set("Asia/Kolkata");
		$emaildata = array(
			'email_template' => 'emails.finder.fivefitness', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'vendor' => implode(",",Input::json()->get('vendor')),
				'location' => Input::json()->get('location'),
				'date' => date("h:i:sa")        
				), 
			'reciver_email' => $reciver_email, 
			'reciver_name' => $reciver_name, 
			'reciver_subject' => '5 Fitness requests alternative' 
			);
		$this->sendEmail($emaildata);
		$data = array(
				'capture_type' => 'fivefitness_alternative',
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'vendor' => implode(",",Input::json()->get('vendor')),
				'location' => Input::json()->get('location'),
			);
		$storecapture = Capture::create($data);
		// $smsdata = array(
		// 	'send_to' => Input::json()->get('phone'),
		// 	'message_body'=>Input::json()->get('name').', Thanks for your request for a call back. We\'ll call you within 24 hours. Team Fitternity',
		// 	);
		// $this->sendSMS($smsdata);
		$resp = array(
			'status' => 200,
			'message' => "Recieved the Request"
			);
		return Response::json($resp);
	}

	public function refundfivefitnesscustomer(){
		$reciver_email = "mailus@fitternity.com";
		$reciver_name = "Leads From 5-fitness page";
		date_default_timezone_set("Asia/Kolkata");
		$emaildata = array(
			'email_template' => 'emails.finder.refund', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'date' => date("h:i:sa")        
				), 
			'reciver_email' => $reciver_email, 
			'reciver_name' => $reciver_name, 
			'reciver_subject' => '5 Fitness requests refund' 
			);
		$this->sendEmail($emaildata);
		$data = array(
				'capture_type' => 'fivefitness_refund',
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'refund' => 1
			);
		$storecapture = Capture::create($data);
		// $smsdata = array(
		// 	'send_to' => Input::json()->get('phone'),
		// 	'message_body'=>Input::json()->get('name').', Thanks for your request for a call back. We\'ll call you within 24 hours. Team Fitternity',
		// 	);
		// $this->sendSMS($smsdata);
		$resp = array(
			'status' => 200,
			'message' => "Recieved the Request"
			);
		return Response::json($resp);
	}

	public function landingpagecallback(){
		$reciver_email = "mailus@fitternity.com";
		$reciver_name = "Leads From Fitternity";
		date_default_timezone_set("Asia/Kolkata");
		$emaildata = array(
			'email_template' => 'emails.finder.landingcallbacks', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'findertitle' => Input::json()->get('title'),
				'location' => Input::json()->get('location'),
				'date' => date("h:i:sa")        
				), 
			'reciver_email' => 'ut.mehrotra@gmail.com', 
			'reciver_name' => $reciver_name, 
			'reciver_subject' => Input::json()->get('subject') 
			);
		$this->sendEmail($emaildata);

		// $data = array(
		// 		'capture_type' => Input::json()->get('capture_type'),
		// 		'name' => Input::json()->get('name'), 
		// 		'phone' => Input::json()->get('phone')
		// 	);

		$data = Input::json()->all();
		$storecapture = Capture::create($data);
		$resp = array(
			'status' => 200,
			'message' => "Recieved the Request"
			);
		return Response::json($resp);
	}

	public function landingconversion(){
		$reciver_email = "mailus@fitternity.com";
		$reciver_name = "Leads From Fitternity";
		date_default_timezone_set("Asia/Kolkata");
		$emaildata = array(
			'email_template' => 'emails.finder.fivefitness', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'vendor' => implode(",",Input::json()->get('vendor')),
				'title' => Input::json()->get('title'),
				'location' => Input::json()->get('location'),
				'date' => date("h:i:sa")        
				), 
			'reciver_email' => 'ut.mehrotra@gmail.com', 
			'reciver_name' => $reciver_name, 
			'reciver_subject' => Input::json()->get('subject')
			);
		$this->sendEmail($emaildata);
		$data = array(
				'capture_type' => Input::json()->get('capture_type'),
				'name' => Input::json()->get('name'), 
				'phone' => Input::json()->get('phone'),
				'vendor' => implode(",",Input::json()->get('vendor')),
				'location' => Input::json()->get('location'),
			);

		$storecapture = Capture::create($data);
		// $smsdata = array(
		// 	'send_to' => Input::json()->get('phone'),
		// 	'message_body'=>Input::json()->get('name').', Thanks for your request for a call back. We\'ll call you within 24 hours. Team Fitternity',
		// 	);
		// $this->sendSMS($smsdata);
		$resp = array(
			'status' => 200,
			'message' => "Recieved the Request"
			);
		return Response::json($resp);
	}

	public function registerme(){
		$emaildata = array(
						'email_template' => 'emails.register.register',
						'email_template_data' => $data = array(
								'name' => Input::json()->get('name'),
								'email' => Input::json()->get('email'), 		       
								'pass' => Input::json()->get('password')
								), 
						'reciver_email' => Input::json()->get('email'), 
						'reciver_name' => Input::json()->get('name'),
						'reciver_subject' => 'Welcome mail from Fitternity'
					);

		$this->sendEmail($emaildata);

	}




}
