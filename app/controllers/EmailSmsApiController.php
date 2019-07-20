<?php

use Hugofirth\Mailchimp\Facades\MailchimpWrapper;

use App\Services\Cloudagent as Cloudagent;
use App\Services\Sidekiq as Sidekiq;
Use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Services\Utilities as Utilities;
Use App\Mailers\FinderMailer as FinderMailer;
Use App\Sms\FinderSms as FinderSms;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class EmailSmsApiController extends \BaseController {

    protected $reciver_email    =   "mailus@fitternity.com";
    protected $reciver_name     =   "Leads From Website";
    protected $cloudagent;
    protected $sidekiq;
    protected $customermailer;
    protected $customerController;
    // protected $transactionController;
    protected $customersms;
    protected $utilities;
    protected $findermailer;
    protected $findersms;

    public function __construct(
        Cloudagent $cloudagent,
        Sidekiq $sidekiq,
        CustomerMailer $customermailer,
        CustomerSms $customersms,
    	CustomerController $customerController,
        // TransactionController $transactionController,
        Utilities $utilities,
        FinderMailer $findermailer,
        FinderSms $findersms
    ){
        $this->cloudagent       =   $cloudagent;
        $this->sidekiq          =   $sidekiq;
        $this->customermailer           =   $customermailer;
        $this->customersms              =   $customersms;
        $this->utilities            =   $utilities;
        $this->findermailer             =   $findermailer;
        $this->findersms             =   $findersms;

        $this->vendor_token = false;
        
        $vendor_token = Request::header('Authorization-Vendor');

        if($vendor_token){

            $this->vendor_token = true;
        }

        $this->customerController=   $customerController;
        // $this->transactionController=   $transactionController;
        

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

        //return $this->customermailer->directWorker($emaildata);

        $email_template         =   $emaildata['email_template'];
        $email_template_data    =   $emaildata['email_template_data'];
        $reciver_name           =   (isset($email_template_data['name'])) ? ucwords($email_template_data['name']) : 'Team Fitternity';
        $to                     =   $emaildata['to'];
        $bcc_emailids           =   $emaildata['bcc_emailds'];
        $email_subject          =   ucfirst($emaildata['email_subject']);
        $send_bcc_status        =   $emaildata['send_bcc_status'];


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

        // return $data = Input::json()->all();

        $data = array(
            'capture_type'          =>      'book_trial',
            'name'                  =>      Input::json()->get('name'),
            'email'                 =>      Input::json()->get('email'),
            'phone'                 =>      Input::json()->get('phone'),
            'finder'                =>      Input::json()->get('finder'),
            'location'              =>      Input::json()->get('location'),
            'service'               =>      Input::json()->get('service'),
            'preferred_time'        =>      Input::json()->get('preferred_time'),
            'preferred_day'         =>      Input::json()->get('preferred_day'),
            'date'                  =>      date("h:i:sa")
        );
        $emaildata = array(
            'email_template'        =>  'emails.finder.booktrial',
            'email_template_data'   =>  $data,
            'to'                    =>  Config::get('mail.to_mailus'),
            'bcc_emailds'           =>  Config::get('mail.bcc_emailds_book_trial'),
            'email_subject'         =>  'Request For Book a Trial',
            'send_bcc_status'       =>  1
        );
        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Thank you for the request to book a trial at '. Input::json()->get('finder') .'. We will call you shortly to arrange a time. Regards - Team Fitternity'
        );

        $this->sendSMS($smsdata);

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture = Capture::create($data);
        $resp   =   array('status' => 200,'message' => "Book a Trial");
        return Response::json($resp);
    }

    public function extraBookTrial() {

        $data = array(
            'capture_type'          =>      'extrabook_trial',
            'name'                  =>      Input::json()->get('name'),
            'email'                 =>      Input::json()->get('email'),
            'phone'                 =>      Input::json()->get('phone'),
            'finder'                =>      implode(",",Input::json()->get('vendor')),
            'location'              =>      Input::json()->get('location'),
            'service'               =>      Input::json()->get('service'),
            'preferred_time'        =>      Input::json()->get('preferred_time'),
            'preferred_day'         =>      Input::json()->get('preferred_day'),
            'date'                  =>      date("h:i:sa")
        );
        $emaildata = array(
            'email_template'        =>  'emails.finder.booktrial',
            'email_template_data'   =>  $data,
            'to'                    =>  Config::get('mail.to_mailus'),
            'bcc_emailds'           =>  Config::get('mail.bcc_emailds_book_trial'),
            'email_subject'         =>  'Request For 2nd Book a Trial',
            'send_bcc_status'       =>  1
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
            'to'                =>  Config::get('mail.to_mailus'),
            'bcc_emailds'       =>  Config::get('mail.bcc_emailds_finder_lead_pop'),
            'email_subject'     => 'lead generator popup',
            'send_bcc_status'   => 1
        );
        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => '9870747016',
            'message_body'=>Input::json()->get('name').', Thanks for your enquiry about '.Input::json()->get('findertitle').'. We will call you within 24 hours. Team Fitternity',
        );
        $this->sendSMS($smsdata);
    }

    public function SubscribeNewsletter(){
        // $list_id = 'd2a433c826';
        //$list_id = 'cd8d82a9d0';
        $email_address = Input::json()->get('email');
        // $response =  MailchimpWrapper::lists()->subscribe($list_id, array('email'=>$email_address));
        $data = array(
            'capture_type' => 'newsletter-subscription',
            'name' => Input::json()->get('name'),
            'email' => $email_address,
            'phone' => Input::json()->get('phone'),
            'city' => Input::json()->get('city')
        );

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture = Capture::create($data);
        $resp = array('status' => 200,'message' => "Recieved the Request");
        return Response::json($resp);
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
            'to'                =>  Config::get('mail.to_mailus'),
            'bcc_emailds'       =>  Config::get('mail.bcc_emailds_fivefitness_alternative'),
            'email_subject'     => '5 Fitness requests alternative',
            'send_bcc_status'   => 0
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
            'to'                =>  Config::get('mail.to_mailus'),
            'bcc_emailds'       =>  Config::get('mail.bcc_emailds_fivefitness_refund'),
            'email_subject'     => '5 Fitness requests refund',
            'send_bcc_status'   => 0
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
            'to'                =>  Config::get('mail.to_mailus'),
            'bcc_emailds'       =>  Config::get('mail.bcc_emailds_request_callback_landing_page'),
            'email_subject'     => Input::json()->get('subject'),
            'send_bcc_status'   => 1
        );
        $this->sendEmail($emaildata);
        $code = rand(1000,99999);
        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Your registration code is '.$code
        );

        $this->sendSMS($smsdata);
        $data           = Input::json()->all();
        array_set($data, 'capture_status', 'yet to connect');

        $storecapture   = Capture::create($data);
        $resp           = array('status' => 200,'message' => "Recieved the Request");
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
            'to'                =>  Config::get('mail.to_mailus'),
            'bcc_emailds'       =>  Config::get('mail.bcc_emailds_book_trial_landing_page'),
            'email_subject'     =>  Input::json()->get('subject'),
            'send_bcc_status'   => 1
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

        $storecapture   = Capture::create($data);
        $resp           = array('status' => 200,'message' => "Recieved the Request");
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
            'to'                =>  Input::json()->get('email'),
            'bcc_emailds'       =>  Config::get('mail.bcc_emailds_register_me'),
            'email_subject'     =>  'Welcome to Fitternity',
            'send_bcc_status'   => 1
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
            'email_template'        =>  'emails.finder.offeravailed',
            'email_template_data'   =>  $data,
            'to'                    =>  Config::get('mail.to_mailus'),
            'bcc_emailds'           =>  Config::get('mail.bcc_emailds_finder_offer_pop'),
            'email_subject'         =>  $capture_type_subject ." ".Input::json()->get('vendor'),
            'send_bcc_status'       =>  1
        );
        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Thank you for availing the offer. We will call you shortly to arrange the same. Regards - Team Fitternity'
        );

        $this->sendSMS($smsdata);

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture   = Capture::create($data);
        $resp           = array('status' => 200,'message' => "Recieved the Request");
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
            'email_template'        =>  'emails.finder.fitcardbuy',
            'email_template_data'   =>  $data,
            'to'                    =>  Config::get('mail.to_mailus'),
            'bcc_emailds'           =>  Config::get('mail.bcc_emailds_fitcardbuy'),
            'email_subject'         => 'Request for fitcard purchase',
            'send_bcc_status'       =>  1
        );
        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Thank you for purchasing FitCard. You will be receiving a call and email from us to kickstart your fitness journey.'
        );

        $this->sendSMS($smsdata);
        array_set($data, 'capture_status', 'yet to connect');

        $storecapture   = Capture::create($data);
        $resp           = array('status' => 200,'message' => "Recieved the Request");
        return Response::json($resp);

    }

    public function landingpagecallbacksave($capture_id){
        $data = Input::json()->all();
        if(isset($capture_id) && $capture_id != ""){
            $capture = Capture::find($capture_id);
            $capture['specialinstructions'] = $data["specialinstructions"];
            $capture->save();
            $resp = array('status' => 200,'message' => "Instructions saved successfully");
            return Response::json($resp,$resp['status']);
        }
        $resp = array('status' => 400,'message' => "Bad request");
        return Response::json($resp,$resp['status']);
    }
    public function landingpagecallback(){

        $data = Input::json()->all();

        Log::info("landingpagecallback");
        Log::info($data);

        if($this->vendor_token){

            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = $decodeKioskVendorToken->vendor;

            $data['finder_id'] = (int)$vendor->_id;

        }

        if(isset($data['capture_type']) && $data['capture_type'] == 'claim_listing'){

            $rules = [
                'customer_email'=>'required|email|max:255',
                'customer_name'=>'required',
                'customer_phone'=>'required',
                'finder_id'=>'required'
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {

                $response = array('status' => 400,'message' =>error_message($validator->errors()));

                return Response::json(
                    $response,
                    $response['status']
                );

            }
            
        }
        if(isset($data['capture_type']) && $data['capture_type'] == 'exit_intent'){
        	
        	$rules = [
        			'customer_name'=>'required',
        			'customer_phone'=>'required',
        			'finder_id'=>'required',
        			'city_id'=>'required',
        			'exit_type'=>'required'
        	];
        	
        	$validator = Validator::make($data, $rules);
        	
        	if ($validator->fails()) {
        		
        		$response = array('status' => 400,'message' =>error_message($validator->errors()));
        		
        		return Response::json(
        				$response,
        				$response['status']
        				);
        		
        	}
        	
            $data['coupon'] = 'exitipps';
        	
        }
        
        if(!empty($data['order_token']) && isset($data['capture_type']) && $data['capture_type'] == 'exit_intent_rtcb'){
            Log::info("exit_intent_rtcb11");
            $already_added = Capture::where("referrer_id", $data['order_token'])->count();
            if($already_added){
                return ['status'=>200];
            }
            
            $exit_intent_capture = Capture::find($data['order_token']);
            
            Log::info($exit_intent_capture);
            $data = $exit_intent_capture->toArray();
            $data['referrer_id'] = $data['_id'];
            unset($data['_id']);
            $data['capture_type'] = 'exit_intent_rtcb';
        }

        if($data['capture_type'] == 'fitness_canvas'){
            $count = Capture::where('capture_type','fitness_canvas')->where('phone', substr($data['phone'], -10))->count();

            if($count >= 2){
                $resp = array('status' => 402,'message' => "Only 2 requests are allowed");
                return Response::json($resp,$resp['status']);
            }
        }

        if(!empty($data['order_token']) && $data['capture_type']!= 'exit_intent'){

            $decodeOrderToken = decodeOrderToken($data['order_token']);

            if($decodeOrderToken['status'] != 200){
                return Response::json($decodeOrderToken,$decodeOrderToken['status']);
            }

            $data = array_merge($data,$decodeOrderToken['data']);
        }

        if($data['capture_type'] == 'sale_pre_register_2018'){

            $rules = [
                'customer_email'=>'required|email|max:255',
                'customer_name'=>'required',
                'customer_phone'=>'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {

                $response = array('status' => 400,'message' =>error_message($validator->errors()));

                return Response::json(
                    $response,
                    $response['status']
                );

            }
            
            $capture = Capture::where('capture_type', 'sale_pre_register_2018')->where(function($query) use ($data){ return $query->orWhere('customer_email', $data['customer_email'])->orWhere('customer_phone', $data['customer_phone']);})->first();
            
            if($capture){
                $resp = array('status' => 400,'message' => "You have already pre registered for the sale");
                return Response::json($resp,$resp['status']);
            }

            if(isset($data['referral_code']) && $data['referral_code'] != ''){
                
                $capture = Capture::find($data['referral_code']);

                if($capture && isset($capture->customer_id)){

                    $wallet_data = [
                        'customer_id' => $capture->customer_id,
                        'amount' => 50,
                        'amount_fitcash' => 0,
                        'amount_fitcash_plus' => 50,
                        'type' => "INVITE",
                        "entry"=>'credit',
                        'description' => "Fitcashplus for invitation",
                    ];
                    
                    Log::info($wallet_data);
                    
                    $walletTransaction = $this->utilities->walletTransactionNew($wallet_data);

                    $capture->wallet_url = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$capture->customer_email."#wallet");
                    
                    $this->customersms->fitcashPreRegister($capture->toArray());
                        
                }
                
            }
        }

        $jwt_token = Request::header('Authorization');

        if($jwt_token){

            $decoded = decode_customer_token();

            $data['customer_id'] = $decoded->customer->_id;
            $data['customer_name'] = $decoded->customer->name;
            $data['customer_email'] = $decoded->customer->email;
            if(!empty($decoded->customer)&&!empty($decoded->customer->referral_code))
            	$data['referral_code'] = $decoded->customer->referral_code;
            $data['customer_email'] = $decoded->customer->email;
            if(!empty($decoded->customer->contact_no)){
                $data['customer_phone'] = $decoded->customer->contact_no;
            }
        }

        if(isset($data['studio_name'])){
            $data['vendor'] = $data['studio_name'];
        }

        if(isset($data['order_id']) && $data['order_id'] != ""){

            $order = Order::find((int) $data['order_id']);

            if(isset($order->finder_id)){
                $data['vendor_id'] = $data['finder_id'] = $order->finder_id;
            }

            if(isset($order->finder_name)){
                $data['vendor_name'] = $data['finder_name'] = $order->finder_name;
            }

            if(isset($order->city_id)){
                $data['city_id'] = $order->city_id;
            }
            
            if(isset($order->city_id)){
                $data['phone'] = $order->customer_phone;
            }
            
            if(isset($order->customer_name)){
                $data['name'] = $order->customer_name;
            }

            if($data["capture_type"] == "renew-membership"){
                $order->update(["renew_membership"=>"requested"]);
            }

            if(isset($data['requested_preferred_starting_date']) && $data['requested_preferred_starting_date'] != "" && $data['requested_preferred_starting_date'] != "-"){
                $data['requested_preferred_starting_date'] = date('Y-m-d 00:00:00',strtotime($data['requested_preferred_starting_date']));
                $order->update(["requested_preferred_starting_date"=>$data["requested_preferred_starting_date"]]);
            }else{
                unset($data['requested_preferred_starting_date']);
            }
        }

        if(isset($data['customer_phone']) && $data['customer_phone'] != ""){
            $data['phone'] = $data['mobile'] = $data['customer_phone'];
        }

        if(isset($data['mobile']) && $data['mobile'] != ""){
            $data['customer_phone'] = $data['phone'] = $data['mobile'];
        }

        if(isset($data['customer_name']) && $data['customer_name'] != ""){
            $data['name'] = $data['customer_name'];
        }

        if(isset($data['customer_email']) && $data['customer_email'] != ""){
            $data['email'] = $data['customer_email'];
        }

        if(isset($data['phone']) && $data['phone'] != ""){
            $data['customer_phone'] = $data['mobile'] = $data['phone'];
        }
        
        if(isset($data['number']) && $data['number'] != ""){
            $data['customer_phone'] = $data['mobile'] = $data['number'];
        }

        if(isset($data['mobile']) && $data['mobile'] != ""){
            $data['customer_phone'] = $data['phone']= $data['mobile'];
        }

        if(isset($data['name']) && $data['name'] != ""){
            $data['customer_name'] = $data['name'];
        }
        
        if(isset($data['fullname']) && $data['fullname'] != ""){
            $data['customer_name'] = $data['fullname'];
        }

        if(isset($data['email']) && $data['email'] != ""){
            $data['customer_email'] = $data['email'];
        }

        if(isset($data['location']) && $data['location'] != ""){
            $data['customer_location'] = $data['location'];
        }

        array_set($data, 'capture_status', 'yet to connect');

        if(isset($data['preferred_starting_date']) && $data['preferred_starting_date'] != "" && $data['preferred_starting_date'] != "-"){
            $data['preferred_starting_date'] = date('Y-m-d 00:00:00',strtotime($data['preferred_starting_date']));
        }else{
            unset($data['preferred_starting_date']);
        }

        if(isset($data['customer_email']) && $data['customer_email'] != "" && isset($data['customer_name']) && $data['customer_name'] != ""){

            $data['customer_id'] = autoRegisterCustomer($data);

            $addUpdateDevice = $this->utilities->addUpdateDevice($data['customer_id']);

        }else{

            $addUpdateDevice = $this->utilities->addUpdateDevice();
        }

        foreach ($addUpdateDevice as $header_key => $header_value) {

            if($header_key != ""){
               $data[$header_key]  = $header_value;
            }

        }

        array_set($data, 'date',date("h:i:sa"));
        array_set($data, 'ticket_number',random_numbers(5));
        
        // starter pack
        if(!empty($data['capture_type'])&&$data['capture_type']=='starter_pack')
        {
        	
        	$start_pack_rules = ['customer_email'=>'required|email|max:255','customer_name'=>'required','customer_phone'=>'required',/* 'fitness_goal'=>'required', */'customer_location'=>'required'];
        		
        	$validator = Validator::make($data, $start_pack_rules);
        		
        		if ($validator->fails()) {
        			$response = array('status' => 400,'message' =>error_message($validator->errors()));
        			return Response::json($response,400);
        		}
        	
        		$count = Capture::where('capture_type',$data['capture_type'])->where(function($query) use ($data)
        		{$query->orWhere('customer_phone','LIKE','%'.substr($data['customer_phone'], -9).'%')->orWhere('customer_email',$data['customer_email']);
        		})->count();
        		
        		if($count >= 1){
        			$resp = array('status' => 400,'message' => "You have already signed up.");
        			return Response::json($resp,$resp['status']);
        		}   	
        }
        // starter pack end
			
        if(isset($data['booktrial_id']) && $data['booktrial_id'] != ""){

            $data['booktrial_id'] = (int) $data['booktrial_id'];

            Booktrial::$withoutAppends=true;

            $booktrial = Booktrial::find((int)$data['booktrial_id']);

            if($booktrial){

                $data['finder_id'] = (int)$booktrial['finder_id'];
                $data['service_id'] = (int)$booktrial['service_id'];
                $data['service_name'] = $booktrial['service_name'];
                $data['city_id'] = (int)$booktrial['city_id'];
            }

        }

        if(isset($data['finder_id']) && $data['finder_id'] != ""){

            $data['finder_id'] = (int)$data['finder_id'];

            Finder::$withoutAppends=true;

            $finder = Finder::with(array('location'=>function($query){$query->select('name');}))->with(array('city'=>function($query){$query->select('name');}))->find($data['finder_id']);

            $data['finder_name'] = ucwords($finder['title']);
            $data['finder_city'] = ucwords($finder['city']['name']);
            $data['finder_location'] = ucwords($finder['location']['name']);

            if($data['capture_type'] == 'walkthrough'){

                $data['google_pin'] = $this->utilities->getShortenUrl("https://maps.google.com/maps?q=".$finder['lat'].",".$finder['lon']."&ll=".$finder['lat'].",".$finder['lon']);
                $data['finder_lat'] = $finder['lat'];
                $data['finder_lon'] = $finder['lon'];
                $data['finder_poc_for_customer_name'] = $finder['finder_poc_for_customer_name'];
                $data['finder_poc_for_customer_no'] = $finder['finder_poc_for_customer_no'];
                $data['finder_address'] = $finder['contact']['address'];
            }
        }

        $storecapture   = Capture::create($data);

        if(isset($data['capture_type']) && $data['capture_type'] == 'exit_intent'){
            
            $finder = Finder::with('location')->with('city')->find($data['finder_id'], ['location_id', 'city_id']);

            $storecapture['nearby_options_link'] = Config::get('app.website').'/'.$finder['city']['slug'].'/'.$finder['location']['slug'].'/fitness';
            
            $storecapture['rtcb_link'] = Config::get('app.website').'/membership?order_token='.$storecapture['_id'].'&capture_type=exit_intent_rtcb';
            
            $storecapture['pps_link'] = Config::get('app.website').'/pay-per-session';
            
            $storecapture['emial_instant_customer'] = $this->customermailer->exitIntent($storecapture->toArray());
            
        }

        if(isset($data['capture_type']) && $data['capture_type']=='my-home-fitness-trial'){

            $this->customersms->myHomFitnessWithoutSlotInstant($data);

        }
        
        if(!empty($data['capture_type'])&&$data['capture_type']=='starter_pack'){
            
            $wallet_data = [
                "customer_id"=>$data['customer_id'],
                "amount"=>500,
                'amount_fitcash' => 0,
                'amount_fitcash_plus' => 500,
                "entry"=>'credit',
                'type'=>'FITCASHPLUS',
                "description"=>"Added FitCash+ as Sign up Bonus for starter pack, Expires On : ".date('d-m-Y',time()+(86400*60)),
                "validity"=>time()+(86400*60),
                "for"=>"starter_pack"
            ];

            $this->utilities->walletTransaction($wallet_data);

            // $this->transactionController->generateFreeDietPlanOrder($data,'starter_pack');
        	
        	if(!empty($data['code'])){
        		
        		$customerExists = Customer::where('referral_code', $data['code'])->where('status', '1')->first();

        		if(!empty($customerExists)){

                    $wallet_data = [
                        "customer_id"=>$customerExists->_id,
                        "amount"=>200,
                        'amount_fitcash' => 0,
                        'amount_fitcash_plus' => 200,
                        "entry"=>'credit',
                        'type'=>'FITCASHPLUS',
                        "description"=>"Added FitCash+ as Sign up Bonus for starter pack reference, Expires On : ".date('d-m-Y',time()+(86400*60)),
                        "validity"=>time()+(86400*60),
                        "for"=>"starter_pack_reference"
                    ];

                    $this->utilities->walletTransaction($wallet_data);
                }
        	}
        }

        if(isset($data['capture_type']) && $data['capture_type'] == 'my-home-fitness-membership')
        {
			$this->customersms->myHomeFitnessPurchaseWithoutSlot($data);
		}

        if(isset($data['capture_type']) &&  in_array($data['capture_type'],['renewalCallback'])){

            $responseData = $this->addReminderMessage($storecapture);
        }

        $emaildata = array(
            'email_template' => 'emails.finder.landingcallbacks',
            'email_template_data' => array(
                'name' => Input::json()->get('name'),
                'email' => Input::json()->get('email'),
                'phone' => Input::json()->get('phone'),
                'findertitle' => Input::json()->get('title'),
                'location' => Input::json()->get('location'),
                'date' => date("h:i:sa"),
                'label' => 'Capture-landingcallbacks'
            ),
            'to'                =>  Config::get('mail.to_mailus'),
            'bcc_emailds'       =>  Config::get('mail.bcc_emailds_request_callback_landing_page'),
            'email_subject'     => "Capture - ".ucwords(Input::json()->get('capture_type')),
            'send_bcc_status'   => 1
        );

        $capture_type = array('fitness_canvas','renew-membership','claim_listing','add_business', 'sale_pre_register_2018','walkthrough', 'multifit-franchisepage', 'multifit-contactuspage');

        if(in_array($data['capture_type'],$capture_type)){

            switch ($data['capture_type']) {
                case 'claim_listing':
                    $this->findermailer->claimListing($data);
                    break;
                case 'add_business':
                    $this->findermailer->addBusiness($data);
                    break;
                case 'multifit-franchisepage':
                case 'multifit-contactuspage':
                    $this->findermailer->multifitRequest($data);
                    break;
                case 'sale_pre_register_2018':
                    $this->customersms->salePreregister($data);
                    break;
                case 'walkthrough':

                    $sms_data = [];

                    $sms_data['customer_phone'] = $storecapture['customer_phone'];
                    
                    $sms_data['message'] = "Hi ".ucwords($storecapture['customer_name'])." your walkin request at ".ucwords($storecapture['finder_name'])." has been confirmed. Address - ".ucwords($storecapture['finder_address'])." . Google pin - ".$storecapture['google_pin'].". Contact person - ". $storecapture['finder_poc_for_customer_name']." If you wish to purchase - make sure you buy through Fitternity with lowest price and assured rewards.";

                    $header = $this->multifitUserHeader();
                    if($header == true){
                        $sms_data['message'] = "Hi ".ucwords($storecapture['customer_name'])." your walkin request at ".ucwords($storecapture['finder_name'])." has been confirmed. Address - ".ucwords($storecapture['finder_address'])." . Google pin - ".$storecapture['google_pin'].". Contact person - ". $storecapture['finder_poc_for_customer_name']." ";
                    }

                    $this->customersms->custom($sms_data);

                    
                    $finder = Finder::where('_id', $data['finder_id'])->with('location')->first();
                    $data['finder_address'] = $finder['contact']['address'];
                    $data['google_pin'] = $finder['lat'].", ".$finder['lon'];
                    // Log::info('finder_info');
                    $data['finder_vcc_mobile'] = $finder['finder_vcc_mobile'];
                    $data['finder_vcc_email'] = $finder['finder_vcc_email'];
                    $data['finder_name'] = $finder['title'].", ".$finder['location']['name'];
                    $data['finder_poc_for_customer_name'] = $finder['finder_poc_for_customer_name'];
                    $data['finder_poc_for_customer_no'] = $finder['finder_poc_for_customer_no'];
                    $data['finder_lat'] = $finder['lat'];
                    $data['finder_lon'] = $finder['lon'];
                    
                    $data['appointment'] = false;
                    
                    
                    if(!$this->vendor_token){
                
                        $this->customermailer->captureCustomerWalkthrough($data);
                        $this->findersms->captureVendorWalkthrough($data);
			            $this->findermailer->captureVendorWalkthrough($data);
                    
                    }

                    break;
                default:
                    $this->customermailer->landingPageCallback($data);
                    $this->customersms->landingPageCallback($data);
                    break;
            }

        }else{

            $this->customermailer->directWorker($emaildata);
            
        }

        $message = "";
        switch ($data['capture_type']) {
            case 'renew-membership':$message = "Thank you for your request, We will curate a renew subscription for you and get back";break;
            case 'change_start_date_request':$message = "Thank you for your request, Our team will get in touch with you within 24 hours to process the request";break;
            case 'claim_listing':$message = "Thank You! Your request has been submitted. We will get in touch with you on ".$data['customer_phone']." or ".$data['customer_email']." as soon as possible!";break;
            case 'add_business' : $message = "Thank You for sharing this information. We will verify it as soon as possible.";break;
            case 'request_callback': $message = "Thanks for requesting a callback. We'll get back to you soon";break;
            case 'walkthrough': $message = "Walkin Captured Successfully";break;
            case 'starter_pack': $message = "Thank you for signing up. Amount of Rs.500 has been credited to your wallet.";break;
            case 'exit_intent': $message = "Successfully stored exit intent of the user.";break;
            case 'membership_invoice_request': $message = "Thank you for membership invoice request, We will get back to you";break;
            case 'membership_cancel_request': $message = "Thank you for membership cancellation request, We will get back to you";break;
            default:$message = "Received the Request";break;
        }

        $resp  = array('status' => 200,'message'=>$message,'capture'=>$storecapture);

        if(!empty($data['customer_name']) && !empty($data['customer_phone']) && !empty($data['customer_email']) && !empty($data['capture_type'])){

            $captureData = [
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'capture_type' => $data['capture_type'],
                'capture_id' => $storecapture['_id'],
                'customer_id' => $storecapture['customer_id']
            ];
            if(!empty($data['gender'])) {
                $capture['gender'] = $data['gender'];
            }
            Log::info('before checking decodeKioskVendorToken');
            if(!empty($decodeKioskVendorToken->vendor) && !empty($decodeKioskVendorToken->vendor->location)) {
                Log::info('decodeKioskVendorToken vendor and location exists');
                $this->utilities->sendEnquiryToFitnessForce($captureData, $decodeKioskVendorToken->vendor, $decodeKioskVendorToken->vendor->location);
            }
            else {
                $this->utilities->sendEnquiryToFitnessForce($captureData);
            }
            
        }

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
            'capture_type'  =>      'request_callback',
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

        if($data['preferred_time'] == null){
            $data['preferred_time'] = Input::json()->get('preferredTime');
        }

        array_set($data, 'capture_status', 'yet to connect');

        $emaildata = array(
            'email_template' => 'emails.callback',
            'email_template_data' => $data,
            'to'                =>  Config::get('mail.to_mailus'),
            'bcc_emailds'       =>  Config::get('mail.bcc_emailds_request_callback'),
            'email_subject'     => $subject,
            'send_bcc_status'   => 1
        );

        $this->sendEmail($emaildata);

        $smsdata = array(
            'send_to' => Input::json()->get('phone'),
            'message_body'=>'Hi '.Input::json()->get('name').', Thank you for your request to call back. We will call you shortly to arrange a time. Regards - Team Fitternity.'
        );
        $this->sendSMS($smsdata);

        $storecapture = Capture::create($data);

        $responseData = [];
        try {

//            $responseData = $this->cloudagent->requestToCallBack($data);
            $responseData = $this->addReminderMessage($storecapture);



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

        $resp = array('status' => 200,'message' => "Recieved the Request",'capture_id'=>$storecapture->_id);

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
            'email_template'        =>  'emails.finder.customerlookingfor',
            'email_template_data'   =>  $data,
            'to'                    =>  Config::get('mail.to_mailus'),
            'bcc_emailds'           =>  Config::get('mail.bcc_emailds_not_able_to_find'),
            'email_subject'         => "Customer request not able to find what they're looking for",
            'send_bcc_status'       =>  1
        );
        $this->sendEmail($emaildata);

        array_set($data, 'capture_status', 'yet to connect');

        $storecapture   = Capture::create($data);
        $resp           = array('status' => 200,'message' => "Send Mail");

        return Response::json($resp);

    }

    public function addReminderMessage($reqquesteddata){

        $capture_id         = ($reqquesteddata['_id']) ? $reqquesteddata['_id'] : "";
        $customer_name      = ($reqquesteddata['name']) ? $reqquesteddata['name'] : "";
        $customer_phone     = ($reqquesteddata['phone']) ? $reqquesteddata['phone'] : "";
        $schedule_date      = Carbon::today()->toDateTimeString();
        $preferred_time     = ($reqquesteddata['preferred_time']) ? $reqquesteddata['preferred_time'] : "";

        if ($capture_id !="" && $customer_name != "" && $customer_phone != "" && $preferred_time != ""){

            $schedule_slot = "right_now";
            $schedule_date_time = date('Y-m-d H:i:s',time());

            switch ($preferred_time) {
                case "Before 10 AM" : $schedule_slot = "09:00 AM-10:00 AM"; break;
                case "10 AM - 2 PM" : $schedule_slot = "10:00 AM-02:00 PM"; break;
                case "2 PM - 6 PM"  : $schedule_slot = "02:00 PM-06:00 PM"; break;
                case "6 PM - 10 PM" : $schedule_slot = "06:00 PM-09:30 PM"; break;
                case "right_now"    : $schedule_slot = "right_now"; break;
            }

            if($schedule_slot != "right_now"){

                $slot_times = explode('-', $schedule_slot);
                $schedule_slot_start_time = $slot_times[0];
                $schedule_slot_end_time = $slot_times[1];
                $schedule_slot = $schedule_slot_start_time . '-' . $schedule_slot_end_time;

                $schedule_date_starttime    =   strtoupper(date('d-m-Y', strtotime($schedule_date)) . " " . $schedule_slot_start_time);
                $schedule_date_time         =   Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();


                $time       = time();
                $from_time  = strtotime(date('Y-m-d') . $schedule_slot_start_time);
                $to_time    = strtotime(date('Y-m-d') . $schedule_slot_end_time);

                if($time >= $from_time && $time <= $to_time){
                    $delay = 0;
                    // echo "today ".$delay." --- ".$from_time." -- ".$to_time;
                    $schedule_date_time = $schedule_date_time;
                }else{
                    $tommrow_schedule_date_time = date('Y-m-d H:i:s', strtotime($schedule_date_time . ' +1 day'));
                    $delay = $this->getSeconds($tommrow_schedule_date_time);
                    // echo "tommrow ".$tommrow_schedule_date_time." --- ".$from_time." -- ".$to_time;
                    $schedule_date_time = $tommrow_schedule_date_time;
                }

            }

            $data = [
                'customer_name' => trim($customer_name),
                'customer_phone' => trim($customer_phone),
                'schedule_date' => $schedule_date,
                'schedule_date_time' => $schedule_date_time,
                'schedule_slot' => trim($schedule_slot),
                'attempt' => 0,
                'call_status' => 'no',
                'status' => 'callback',
                'capture_id' => $capture_id
            ];

            $requestCallBackReminderCall = new Requestcallbackremindercall($data);
            $requestCallBackReminderCall->_id = Requestcallbackremindercall::max('_id') + 1;
            $requestCallBackReminderCall->save();


            $requestToCallBackData =   ['name' => $customer_name, 'phone' => $customer_phone];
            $responseData = $this->cloudagent->requestToCallBack($requestToCallBackData);

            if(isset($responseData) && isset($responseData['data']) && isset($responseData['data']['status']) && $responseData['data']['status'] == "SUCCESS"){

                $new_schedule_date_time     =   date("d-m-Y H:i:s", strtotime('+1 hour', strtotime($requestCallBackReminderCall['schedule_date_time'])) );
                $new_schedule_date_time     =   \Carbon::parse($new_schedule_date_time)->toDateTimeString();
                $update                     =   $requestCallBackReminderCall->update(['attempt' => 1, 'status' => 'callbackcheck', 'schedule_date_time' => $new_schedule_date_time]);
            }

        }
    }
    
    public function hitURLAfterDelay($url, $delay = 0, $label = 'label', $priority = 0){

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }

        $payload = array('url'=>$url,'delay'=>$delay,'priority'=>$priority,'label' => $label);

        $route  = 'outbound';
        $result  = $this->sidekiq->sendToQueue($payload,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
        }
    }



    /**
     * Calculate the number of seconds with the given delay.
     *
     * @param  \DateTime|int  $delay
     * @return int
     */
    public function getSeconds($delay){

        if ($delay instanceof DateTime){

            return max(0, $delay->getTimestamp() - $this->getTime());

        }elseif ($delay instanceof \Carbon\Carbon){

            return max(0, $delay->timestamp - $this->getTime());

        }elseif(isset($delay['date'])){

            $time = strtotime($delay['date']) - $this->getTime();

            return $time;

        }else{

            $delay = strtotime($delay) - time();
        }

        return (int) $delay;
    }



    public function requestCallbackCloudAgent($requestcallbackremindercall_id){

        $remindercall   =   Requestcallbackremindercall::find(intval($requestcallbackremindercall_id));
        if($remindercall){
            $data           =   ['name' => trim($remindercall['customer_name']), 'phone' => trim($remindercall['customer_phone'])];

            try {
                $responseData = $this->cloudagent->requestToCallBack($data);

                if(isset($responseData) && isset($responseData['data']) &&
                    isset($responseData['data']['status']) &&
                    $responseData['data']['status'] == "SUCCESS"){

                    $remindercallObj  = \Requestcallbackremindercall::find(intval($remindercall['_id']));
                    $remindercallObj->update(['call_status' => 'yes','status'=> "call_now"]);

                    return $responseData;

                }

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

        }

    }

    public function registerShedEat(){
        ShedEatRegistration::create(Input::json()->all());
        return ['status'=>200];
    }
    
    public function notifyShedEat(){
        ShedEatNotify::create(Input::json()->all());
        return ['status'=>200];
    }

    public function multifitUserHeader(){
		$vendor_token = \Request::header('Authorization-Vendor');
		\Log::info('register auth             :: ', [$vendor_token]);
		if($vendor_token){

            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = $decodeKioskVendorToken->vendor;

			$finder_id = $vendor->_id;

			$utilities = new Utilities();

			$allMultifitFinderId = $utilities->multifitFinder(); 
			// $allMultifitFinderId = [9932, 1935, 9304, 9423, 9481, 9954, 10674, 10970, 11021, 11223, 12208, 12209, 13094, 13898, 14102, 14107, 16062, 13968, 15431, 15980, 15775, 16251, 9600, 14622, 14626, 14627];
			\Log::info('register     :: ', [$finder_id]);
			if(in_array($finder_id, $allMultifitFinderId)){
				return true;
			}
		}
		
		return false;
    }
    
    public function spinTheWheelReg(){

        try{

            $data = Input::json()->all();
            
            $rules = [
                'customer_email'=>'required|email|max:255',
                'customer_phone'=>'required|max:10',
                // 'temp'=>'required'
            ];
    
            
            $validator = Validator::make($data, $rules);        
            
            if ($validator->fails()) {
                return Response::json(['message'=>'Invalid Input', 'error'=>1],400);
            }
            
            $data['customer_email'] = strtolower(trim($data['customer_email']));
            $data['customer_phone'] = trim($data['customer_phone']);

            $temp = Temp::where('customer_phone', $data['customer_phone'])->where('verified', 'Y')->first();
    
            if(empty($temp)){
                return Response::json(['message'=>'Invalid Input', 'error'=>3], 400);
            }
            
            list($index, $spin_array ) = $this->getSpinIndex();
    
            $data['index'] = $index;
            $data['spin_array'] = $spin_array;
            $data['label'] = $spin_array[$index]['label'];
            $data['status'] = "1";
            // return $data;
            if(!Config::get('app.debug') && !in_array($data['customer_email'], ['sailismart@fitternity.com', 'dhruvsarawagi@fitternity.com', 'gauravraviji@gmail.com', 'palaisuraj@gmail.com'])){
            

                $already_reg = CampaignReg::active()->where(function($query) use ($data){
                    $query->orWhere('customer_email', $data['customer_email'])->orWhere('customer_phone', $data['customer_phone']);
                })->update(['repeat'=>true]);
        
                if(!empty($already_reg)){
                    return Response::json(['message'=>'YOU HAVE ALREADY TRIED YOUR LUCK!!', 'error'=>2], 400); 
                }
        
            }
            $campain_reg = new CampaignReg($data);
            $campain_reg->save();
            $coupon = null;
            if(!empty($data['spin_array'][$data['index']]['spin_coupon'])){
                $coupon = $this->getSpinCampaignCoupon($data);
                $data['coupon'] = $coupon['code'];
            }
            $data['message'] = $this->getMessage($data);
            $campain_reg->update($data);
            $data['pps_link'] = Config::get('app.website').'/pay-per-session';
        
            $redisid = Queue::connection('sync')->push('EmailSmsApiController@spinTheWheelComm',['data'=>$data],Config::get('app.queue'));
            
            return [
                'status' => 200, 
                'index'=>$index, 
                'message'=>$data['message'], 'coupon'=>!empty($data['coupon']) ? $data['coupon'] : null
            ];
        
        }catch(Exception $e){
            
            print_exception($e);

            return Response::json(['message'=>'Please try after some time', 'error'=>5],500);
        }
        
    
    }

    public function getSpinIndex(){
        
        $spin_array = getSpinArray();
        
        $index = $this->getRandomWeightedElement(array_column($spin_array, 'value'));
        // $index=5;
        return [$index, $spin_array];
    }

    

    
    function getRandomWeightedElement(array $weightedValues) {
        
        $rand = mt_rand(1, (int) array_sum($weightedValues));

        foreach ($weightedValues as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
              return $key;
            }
        }
    }

    public function testSpinResult($number){

        $spin_array = getSpinArray();

        $data = [];
        for($i=1;$i<=$number;$i++){
            $r1 = $this->getRandomWeightedElement(array_column($spin_array, 'value'));
            $r = array_column($spin_array, 'label')[$r1];
            if(empty($data[$r])){
                $data[$r] = 1;
            }else{
                $data[$r]++;
            }
        }
        return $data;
    }

    public function getSpinCampaignCoupon($data){
        
        if(!empty($data['spin_array'][$data['index']]['fitcash'])){
            
            $coupon = Fitcashcoupon::where('spin_coupon', $data['spin_array'][$data['index']]['spin_coupon'])->first();
            $coupon_array = $coupon->toArray();
            
            array_push($coupon_array['customer_phones'], ['value'=>$data['customer_phone'], 'valid_till'=>new MongoDate(strtotime('+2 days'))]);
            $coupon->update($coupon_array);
            return $coupon;
            
        }else{
            
            $coupon = Coupon::where('spin_coupon', $data['spin_array'][$data['index']]['spin_coupon'])->first();
            $coupon_array = $coupon->toArray();
            
            foreach($coupon_array['and_conditions'] as &$value){
                if($value['key'] == 'logged_in_customer.contact_no'){
                    array_push($value['values'], ['value'=>$data['customer_phone'], 'valid_till'=>new MongoDate(strtotime('+2 days'))]);
                }
            }
    
            $coupon->update($coupon_array);
    
            return $coupon;

        }
        
    }

    public function getMessage($data){
               
        // return $coupon;
        $text = $data['spin_array'][$data['index']]['text'];
        if(!empty($data['coupon'])){
            $coupon = $data['coupon'];
            foreach($text as $key => &$value){
                $value =  bladeCompile($value, ['coupon' => strtoupper($coupon)]);
            }
            
        }
        return $text;

    }
    
    public function spinTheWheelComm($job,$data){

        $job->delete();

        try{
            Log::info($data['data']['message']);
            $this->customermailer->spinTheWheel($data['data']);
        
        }catch(\Exception $exception){

            Log::error($exception);
        }

    }


}
