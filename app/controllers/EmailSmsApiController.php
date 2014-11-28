<?php

use Hugofirth\Mailchimp\Facades\MailchimpWrapper;

class EmailSmsApiController extends \BaseController {

	protected $reciver_email = "info@fitternity.com";
	protected $reciver_name = "Leads From Website";

	public function __construct()
	{
		$this->afterFilter(function($response)
		{
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			return $response;
		});
	}

	public function sendEmail($emaildata){

		$email_template = $emaildata['email_template'];
		$email_template_data = $emaildata['email_template_data'];
		$reciver_email = $emaildata['reciver_email'];
		$reciver_name = $emaildata['reciver_name'];
		$reciver_subject = $emaildata['reciver_subject'];

		Mail::send($email_template, $email_template_data, function($message) use ($reciver_email,$reciver_name,$reciver_subject)
		{
			$message->to($reciver_email, $reciver_name)->subject($reciver_subject);
		});
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



	public function findercreated() {
		$emaildata = array(
			'email_template' => 'emails.finder.create', 
			'email_template_data' => $data = array(
				'finder_id'=> Input::json()->get('finder_id'),
				'finder_owner'=> Input::json()->get('finder_owner'), 
				'approval_status'=> Input::json()->get('approval_status'), 
				'finder_link'=> Input::json()->get('finder_link') 
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'New Finder' 
			);

		$this->sendEmail($emaildata);
	}


	public function ReviewOnfinder(){
		$emaildata = array(
			'email_template' => 'emails.finder.review', 
			'email_template_data' => 		$data = array(
				'finder_name'=> Input::json()->get('finder_name'),
				'finder_location'=> Input::json()->get('finder_location'),
				'name' => Input::json()->get('name'),
				'date' => Input::json()->get('date'),
				'time' => Input::json()->get('time'),
				'review_posted'=> Input::json()->get('review_posted'),
				'rating'=> Input::json()->get('rating')
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'Review on finder' 
			);
		$this->sendEmail($emaildata);
	}


	public function JoinCommunity(){
		$emaildata = array(
			'email_template' => 'emails.community.createcom', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('fullname'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('mobile'),
				'address' => Input::json()->get('address')
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'Request to Create A community' 
			);
		$this->sendEmail($emaildata);
	}


	public function CustomerJoinCommunity(){
		$emaildata = array(
			'email_template' => 'emails.community.joincom', 
			'email_template_data' => $data = array(
				'cust_name' => Input::json()->get('fullname'),
				'com_name' => Input::json()->get('com_name')
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'Join a Community' 
			);
		$this->sendEmail($emaildata);
	}
	

	public function InterestCommunity(){
		$emaildata = array(
			'email_template' => 'emails.community.interest', 
			'email_template_data' => $data = array(
				'communityname' => Input::json()->get('communityname'),
				'communityleader' => Input::json()->get('communityleader'),
				'communityleaderemail' => Input::json()->get('communityleaderemail'),
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'address' => Input::json()->get('address')
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'Interest In Community' 
			);
		$this->sendEmail($emaildata);
	}

	public function CustomerCreateCommunity(){
		$emaildata = array(
			'email_template' => 'emails.community.createcom', 
			'email_template_data' => $data = array(
				'cust_name' => Input::json()->get('cust_name')
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'Create A community' 
			);
		$this->sendEmail($emaildata);
	}


		public function CreateCommunity(){
		$emaildata = array(
			'email_template' => 'emails.community.create',
			'email_template_data' => $data = array(
				'fullname' => Input::json()->get('fullname'),
				'email' => Input::json()->get('email'),
				'mobile' => Input::json()->get('mobile'),
				'communityname' => Input::json()->get('communityname'),
				'location' => Input::json()->get('location'),
				'others' => Input::json()->get('others'),
				'date' => date("h:i:sa")
				),
				'reciver_email' => $this->reciver_email,
				'reciver_name' => $this->reciver_name,
				'reciver_subject' => 'Create an community'
		);
		$this->sendEmail($emaildata);
	}

	public function CommentOnBlog(){
		$emaildata = array(
			'email_template' => 'emails.blog.comment', 
			'email_template_data' => $data = array(
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'article' =>Input::json()->get('article'), 
				'date' =>Input::json()->get('date'), 
				'time' => Input::json()->get('time'), 
				'comment' => Input::json()->get('comment')
				), 
			'reciver_email' => $this->reciver_email, 
			'reciver_name' => $this->reciver_name, 
			'reciver_subject' => 'Comment On Blog' 
			);
		$this->sendEmail($emaildata);
	}

	public function SubscribeNewsletter(){
		$list_id = 'd2a433c826';
		//$list_id = 'cd8d82a9d0';
		$email_address = Input::json()->get('email');
		$response =  MailchimpWrapper::lists()->subscribe($list_id, array('email'=>$email_address));
		return $response;
	}


	public function JoinEvent(){
		$emaildata = array(
			'email_template' => 'emails.events.join',
			'email_template_data' => $data = array(
				'fullname' => Input::json()->get('fullname'),
				'email' => Input::json()->get('email'),
				'mobile' => Input::json()->get('mobile'),
				'eventname' => Input::json()->get('eventname'),
				'location' => Input::json()->get('location'),
				'others' => Input::json()->get('others'),
				'date' => date("h:i:sa")
				),
				'reciver_email' => $this->reciver_email,
				'reciver_name' => $this->reciver_name,
				'reciver_subject' => 'Join an event'
		);
		$this->sendEmail($emaildata);
	}


		public function CreateEvent(){
		$emaildata = array(
			'email_template' => 'emails.events.create',
			'email_template_data' => $data = array(
				'fullname' => Input::json()->get('fullname'),
				'email' => Input::json()->get('email'),
				'mobile' => Input::json()->get('mobile'),
				'eventname' => Input::json()->get('eventname'),
				'location' => Input::json()->get('location'),
				'others' => Input::json()->get('others'),
				'date' => date("h:i:sa")
				),
				'reciver_email' => $this->reciver_email,
				'reciver_name' => $this->reciver_name,
				'reciver_subject' => 'Create an event'
		);
		$this->sendEmail($emaildata);
	}

	public function fivefitnesscustomer(){
		$reciver_email = "ut.mehrotra@gmail.com";
		$reciver_name = "Leads From Website";
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
			'reciver_subject' => '5 Fitness requests' 
			);
		$this->sendEmail($emaildata);
		$data = array(
				'capture_type' => 'fivefitness',
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'), 
				'phone' => Input::json()->get('phone'),
				'vendor' => implode(",",Input::json()->get('vendor')),
				'location' => Input::json()->get('location'),
				'date' => date("h:i:sa")        
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
}
