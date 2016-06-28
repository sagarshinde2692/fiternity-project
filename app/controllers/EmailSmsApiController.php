<?php

use Hugofirth\Mailchimp\Facades\MailchimpWrapper;

use App\Services\Cloudagent as Cloudagent;


class EmailSmsApiController extends \BaseController {

    protected $reciver_email    =   "mailus@fitternity.com";
    protected $reciver_name     =   "Leads From Website";
    protected $cloudagent;

    public function __construct(Cloudagent $cloudagent)
    {
        $this->cloudagent = $cloudagent;
    }

    public function sendSMS($smsdata){

        $to = $smsdata['send_to'];
        $message = $smsdata['message_body'];

        $url = 'http://www.kookoo.in/outbound/outbound_sms_ftrnty.php';

        $param = array(
            'api_key' => 'KK33e21df516ab75130faef25c151130c1',
            'phone_no' => trim($to),
            'message' => $message,
            'senderid'=> 'FTRNTY'
        );

        $url = $url . "?" . http_build_query($param, '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        curl_close($ch);

        // $live_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=india123&type=0&dlr=1&destination=" . urlencode($to) . "&source=fitter&message=" . urlencode($message);
        /*$live_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode($to) . "&source=fitter&message=" . urlencode($message);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $live_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);*/
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



    public function testemail(){

        $email_template = 'emails.testemail';
        $email_template_data = array();

        Mail::queue($email_template, $email_template_data, function($message){
            $to = 'sanjay.id7@gmail.com';
            $reciver_name = 'sanjay sahu';
            $cc_emailids = array('sanjay.fitternity@gmail.com','info@fitternity.com');
            $email_subject = 'subject of test email';
            $message->to($to, $reciver_name)->bcc($cc_emailids)->subject($email_subject);

        });

        /* Queue:push(function($job) use ($data){ $data['string']; $job->delete();  }); */
    }

    public function BookTrial() {

        // return $data	= Input::json()->all();

        $data = array(
            'capture_type' 			=>		'book_trial',
            'name' 					=>		Input::json()->get('name'),
            'email' 				=>		Input::json()->get('email'),
            'phone' 				=>		Input::json()->get('phone'),
            'finder' 				=>		Input::json()->get('finder'),
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
            'email_subject' 		=> 	'Request For Book a Trial',
            'send_bcc_status' 		=> 	1
        );
        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Thank you for the request to book a trial at '. Input::json()->get('finder') .'. We will call you shortly to arrange a time. Regards - Team Fitternity'
        );

        $this->sendSMS($smsdata);

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture = Capture::create($data);
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

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture = Capture::create($data);
        $resp = array('status' => 200,'message' => "Book a Trial");
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
            'to'				=> 	Config::get('mail.to_neha'),
            'bcc_emailds' 		=> 	Config::get('mail.bcc_emailds_finder_lead_pop'),
            'email_subject' 	=> 'lead generator popup',
            'send_bcc_status' 	=> 1
        );
        $this->sendEmail($emaildata);

        $smsdata = array(
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
            'to'				=> 	Config::get('mail.to_mailus'),
            'bcc_emailds' 		=> 	Config::get('mail.bcc_emailds_fivefitness_alternative'),
            'email_subject' 	=> '5 Fitness requests alternative',
            'send_bcc_status' 	=> 0
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

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture = Capture::create($data);
        $resp = array('status' => 200,'message' => "Recieved the Request");
        return Response::json($resp);
    }

    public function refundfivefitnesscustomer(){

        if (filter_var(trim(Input::json()->get('email')), FILTER_VALIDATE_EMAIL) === false){
            return Response::json(array('status' => 400,'message' => "Invalid Email Id"),400);
        }

        $emaildata = array(
            'email_template' => 'emails.finder.refund',
            'email_template_data' => $data = array(
                'name' => Input::json()->get('name'),
                'email' => Input::json()->get('email'),
                'phone' => Input::json()->get('phone'),
                'date' => date("h:i:sa")
            ),
            'to'				=> 	Config::get('mail.to_mailus'),
            'bcc_emailds' 		=> 	Config::get('mail.bcc_emailds_fivefitness_refund'),
            'email_subject' 	=> '5 Fitness requests refund',
            'send_bcc_status' 	=> 0
        );
        $this->sendEmail($emaildata);

        $data = array(
            'capture_type' => 'fivefitness_refund',
            'name' => Input::json()->get('name'),
            'email' => Input::json()->get('email'),
            'phone' => Input::json()->get('phone'),
            'refund' => 1
        );
        array_set($data, 'capture_status', 'yet to connect');

        $storecapture = Capture::create($data);
        $resp = array('status' => 200,'message' => "Recieved the Request");
        return Response::json($resp);
    }

    public function landingpageregister(){

        if (filter_var(trim(Input::json()->get('email')), FILTER_VALIDATE_EMAIL) === false){
            return Response::json(array('status' => 400,'message' => "Invalid Email Id"),400);
        }

        $emaildata = array(
            'email_template' => strpos(Input::json()->get('title'), 'marathon-') ? 'emails.finder.marathon' : 'emails.finder.landingcallbacks',
            'email_template_data' => $data = array(
                'name' => Input::json()->get('name'),
                'email' => Input::json()->get('email'),
                'phone' => Input::json()->get('phone'),
                'findertitle' => Input::json()->get('title'),
                'location' => Input::json()->get('location'),
                'date' => date("h:i:sa")
            ),
            'to'				=> 	Config::get('mail.to_neha'),
            'bcc_emailds' 		=> 	Config::get('mail.bcc_emailds_request_callback_landing_page'),
            'email_subject' 	=> Input::json()->get('subject'),
            'send_bcc_status' 	=> 1
        );
        $this->sendEmail($emaildata);
        $code = rand(1000,99999);
        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Your registration code is '.$code
        );

        $this->sendSMS($smsdata);
        $data 			= Input::json()->all();
        array_set($data, 'capture_status', 'yet to connect');

        $storecapture 	= Capture::create($data);
        $resp 			= array('status' => 200,'message' => "Recieved the Request");
        return Response::json($resp);
    }

    public function landingconversion(){

        if (filter_var(trim(Input::json()->get('email')), FILTER_VALIDATE_EMAIL) === false){
            return Response::json(array('status' => 400,'message' => "Invalid Email Id"),400);
        }

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
            'to'				=> 	Config::get('mail.to_neha'),
            'bcc_emailds' 		=> 	Config::get('mail.bcc_emailds_book_trial_landing_page'),
            'email_subject' 	=> 	Input::json()->get('subject'),
            'send_bcc_status' 	=> 1
        );
        $this->sendEmail($emaildata);
        $data = array(
            'capture_type' => Input::json()->get('capture_type'),
            'name' => Input::json()->get('name'),
            'phone' => Input::json()->get('phone'),
            'vendor' => implode(",",Input::json()->get('vendor')),
            'location' => Input::json()->get('location'),
        );
        array_set($data, 'capture_status', 'yet to connect');

        $storecapture 	= Capture::create($data);
        $resp 			= array('status' => 200,'message' => "Recieved the Request");
        return Response::json($resp);
    }

    public function registerme(){

        if (filter_var(trim(Input::json()->get('email')), FILTER_VALIDATE_EMAIL) === false){
            return Response::json(array('status' => 400,'message' => "Invalid Email Id"),400);
        }

        $emaildata = array(
            'email_template' => 'emails.register.register',
            'email_template_data' => $data = array(
                'name' => Input::json()->get('name'),
                'email' => Input::json()->get('email'),
                'pass' => Input::json()->get('password')
            ),
            'to'				=> 	Input::json()->get('email'),
            'bcc_emailds' 		=> 	Config::get('mail.bcc_emailds_register_me'),
            'email_subject' 	=>  'Welcome to Fitternity',
            'send_bcc_status' 	=> 1
        );

        $this->sendEmail($emaildata);

    }

    public function offeravailed(){

        if (filter_var(trim(Input::json()->get('email')), FILTER_VALIDATE_EMAIL) === false){
            return Response::json(array('status' => 400,'message' => "Invalid Email Id"),400);
        }

        $data = array(
            'name' => Input::json()->get('name'),
            'email' => Input::json()->get('email'),
            'phone' => Input::json()->get('phone'),
            'location' => Input::json()->get('location'),
            'vendor' => Input::json()->get('vendor'),
            'finder_offer' => Input::json()->get('finder_offer'),
            'capture_type' => Input::json()->get('capture_type')
        );

        $capture_type_subject =  ucfirst(str_replace("-"," ",Input::json()->get('capture_type')));


        $emaildata = array(
            'email_template' 		=> 	'emails.finder.offeravailed',
            'email_template_data' 	=> 	$data,
            'to'					=> 	Config::get('mail.to_neha'),
            'bcc_emailds' 			=> 	Config::get('mail.bcc_emailds_finder_offer_pop'),
            'email_subject' 		=> 	$capture_type_subject ." ".Input::json()->get('vendor'),
            'send_bcc_status' 		=> 	1
        );
        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Thank you for availing the offer. We will call you shortly to arrange the same. Regards - Team Fitternity'
        );

        $this->sendSMS($smsdata);

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture 	= Capture::create($data);
        $resp 			= array('status' => 200,'message' => "Recieved the Request");
        return Response::json($resp);
    }


    public function fitcardbuy(){

        $data = array(
            'name' => Input::json()->get('name'),
            'email' => Input::json()->get('email'),
            'phone' => Input::json()->get('phone'),
            'location' => Input::json()->get('location'),
            'capture_type' => 'fitcardbuy',
            'date' => date("h:i:sa")
        );

        $emaildata = array(
            'email_template' 		=> 	'emails.finder.fitcardbuy',
            'email_template_data' 	=> 	$data,
            'to'					=> 	Config::get('mail.to_neha'),
            'bcc_emailds' 			=> 	Config::get('mail.bcc_emailds_fitcardbuy'),
            'email_subject' 		=> 'Request for fitcard purchase',
            'send_bcc_status' 		=> 	1
        );
        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Thank you for purchasing FitCard. You will be receiving a call and email from us to kickstart your fitness journey.'
        );

        $this->sendSMS($smsdata);
        array_set($data, 'capture_status', 'yet to connect');

        $storecapture 	= Capture::create($data);
        $resp 			= array('status' => 200,'message' => "Recieved the Request");
        return Response::json($resp);

    }


    public function landingpagecallback(){
        $emaildata = array(
            'email_template' => strpos(Input::json()->get('title'), 'marathon-') ? 'emails.finder.marathon' : 'emails.finder.landingcallbacks',
            'email_template_data' => $data = array(
                'name' => Input::json()->get('name'),
                'email' => Input::json()->get('email'),
                'phone' => Input::json()->get('phone'),
                'findertitle' => Input::json()->get('title'),
                'location' => Input::json()->get('location'),
                'date' => date("h:i:sa")
            ),
            'to'				=> 	Config::get('mail.to_neha'),
            'bcc_emailds' 		=> 	Config::get('mail.bcc_emailds_request_callback_landing_page'),
            'email_subject' 	=> Input::json()->get('subject'),
            'send_bcc_status' 	=> 1
        );
        $this->sendEmail($emaildata);

        $data 			= Input::json()->all();
        array_set($data, 'capture_status', 'yet to connect');

        $storecapture 	= Capture::create($data);

        if(Input::json()->get('capture_type') == 'FakeBuy'){

            // $smsdata = array(
            // 'send_to' => Input::json()->get('phone'),
            // 	'message_body'=>'Hi '.Input::json()->get('name').', Your registration code is '.$code
            // 	);

            // $this->sendSMS($smsdata);
        }


        $resp 			= array('status' => 200,'capture' =>$storecapture, 'message' => "Recieved the Request");
        return Response::json($resp);
    }


    public function RequestCallback() {

        $vendor = Input::json()->get('vendor');

        if($vendor != ''){
            $subject = 'Request A Callback '.$vendor;
        }else{
            $subject = 'Request A Callback';
        }

        $data = array(
            'capture_type' 	=>		'request_callback',
            'vendor' => Input::json()->get('vendor'),
            'city_id' => (Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : '',
            'finder_id' => (Input::json()->get('finder_id')) ? intval(Input::json()->get('finder_id')) : '',
            'name' => Input::json()->get('name'),
            'email' => Input::json()->get('email'),
            'phone' => Input::json()->get('phone'),
            'preferred_time' => Input::json()->get('preferred_time'),
            'date' => date("h:i:sa"),
            'referrer' =>  (Input::json()->get('referrer')) ? Input::json()->get('referrer') : 'fitternity',
            'social_referrer' =>  (Input::json()->get('social_referrer')) ? Input::json()->get('social_referrer') : '',

            'transacted_after' =>  (Input::json()->get('transacted_after')) ? Input::json()->get('transacted_after') : '',
            'referrer_object' =>  (Input::json()->get('referrer_object')) ? Input::json()->get('referrer_object') : ''
        );

        array_set($data, 'capture_status', 'yet to connect');

        $emaildata = array(
            'email_template' => 'emails.callback',
            'email_template_data' => $data,
            'to'				=> 	Config::get('mail.to_mailus'),
            'bcc_emailds' 		=> 	Config::get('mail.bcc_emailds_request_callback'),
            'email_subject' 	=> $subject,
            'send_bcc_status' 	=> 1
        );

        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Thank you for your request to call back. We will call you shortly to arrange a time. Regards - Team Fitternity.',
        );
        $this->sendSMS($smsdata);

        $storecapture = Capture::create($data);

        $responseData = [];
        try {

//            $responseData = $this->cloudagent->requestToCallBack($data);
            $responseData = $this->addReminderMessage($data);

        }catch (Exception $e) {

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status'=>400,'reason'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            Log::info('Requestcallbackremindercall Error : '.json_encode($response));
        }


//        var_dump($responseData);exit;

        $resp = array('status' => 200,'message' => "Recieved the Request" );

        if(isset($responseData) && isset($responseData['data'])){
            $resp['data'] = $responseData['data'];
        }

        return Response::json($resp);
    }



    public function not_able_to_find(){

        if (filter_var(trim(Input::json()->get('email')), FILTER_VALIDATE_EMAIL) === false){
            return Response::json(array('status' => 400,'message' => "Invalid Email Id"),400);
        }

        $data = array(
            'name' => Input::json()->get('name'),
            'email' => Input::json()->get('email'),
            'phone' => Input::json()->get('phone'),
            'msg' => Input::json()->get('msg'),
            'capture_type' => 'not_able_to_find',
            'date' => date("h:i:sa")
        );

        $emaildata = array(
            'email_template' 		=> 	'emails.finder.customerlookingfor',
            'email_template_data' 	=> 	$data,
            'to'					=> 	Config::get('mail.to_mailus'),
            'bcc_emailds' 			=> 	Config::get('mail.bcc_emailds_not_able_to_find'),
            'email_subject' 		=> "Customer request not able to find what they're looking for",
            'send_bcc_status' 		=> 	1
        );
        $this->sendEmail($emaildata);

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture 	= Capture::create($data);
        $resp 			= array('status' => 200,'message' => "Send Mail");

        return Response::json($resp);

    }



    public function addReminderMessage($reqquesteddata)
    {

        $customer_name = ($reqquesteddata['name']) ? $reqquesteddata['name'] : "";
        $customer_phone = ($reqquesteddata['phone']) ? $reqquesteddata['phone'] : "";
        $schedule_date = Carbon::today()->toDateTimeString();
        $preferred_time = ($reqquesteddata['preferred_time']) ? $reqquesteddata['preferred_time'] : "";

        if ($customer_name != "" && $customer_phone != "" && $preferred_time != ""){

            if ($preferred_time == "Before 10 AM") {
                $schedule_slot = "09:00 AM-10:00 PM";
            } elseif ($preferred_time == "10 AM - 2 PM") {
                $schedule_slot = "10:00 AM-02:00 PM";
            } elseif ($preferred_time == "2 PM - 6 PM") {
                $schedule_slot = "02:00 PM-06:00 PM";
            } elseif ($preferred_time == "6 PM - 10 PM") {
                $schedule_slot = "06:00 PM-09:00 PM";
            }

            $slot_times = explode('-', $schedule_slot);
            $schedule_slot_start_time = $slot_times[0];
            $schedule_slot_end_time = $slot_times[1];
            $schedule_slot = $schedule_slot_start_time . '-' . $schedule_slot_end_time;

            $schedule_date_starttime = strtoupper(date('d-m-Y', strtotime($schedule_date)) . " " . $schedule_slot_start_time);
            $schedule_date_time = Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

            $data = [
                'customer_name' => trim($customer_name),
                'customer_phone' => trim($customer_phone),
                'schedule_date' => $schedule_date,
                'schedule_date_time' => $schedule_date_time,
                'schedule_slot' => trim($schedule_slot),
                'call_status' => 'no'
            ];

//        return $data;

            $insertedid = Requestcallbackremindercall::max('_id') + 1;
            $obj = new Requestcallbackremindercall($data);
            $obj->_id = $insertedid;
            $obj->save();

        }
    }


}
