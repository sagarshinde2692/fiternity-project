<?php

use App\Sms\CustomerSms as CustomerSms;


class CaptureController extends \BaseController {

	protected $customersms;

	public function __construct(CustomerSms $customersms){

		$this->customersms 				=	$customersms;

		$this->afterFilter(function($response) {
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			return $response;
		});

	}

	/*
direct call
FakeBuy
Fitmania-submit
VIP session booked
banglore-preregister
beat-ur-best
book_trial
buy-fitcard
download-fitness-guide-15
extrabook_trial
fitcardbuy
fitmaia_notify
fitmania-dod-offer-availed
fitmania-offer-availed
fitness-guide
fitness_guide
fivefitness_alternative
fivefitness_refund
gym-pick
gyms_callbacks
gyms_trials
healthy_tiffin
list_business
marathon-guide
marathon-subscribe
marathon_finder_callback
marathon_subscribe
marathon_trials
not_able_to_find
offer-availed
personaltrainer_callbacks
pre-register-fitmania
pune-preregister
register-challenge
request_callback
sof
spinning_finder_callback
spinning_trials
yfc_offer
zumba-pune
zumba_callbacks
zumba_party
zumba_trials
*/

public function sendSMS($smsdata){

	$to = $smsdata['send_to'];
	$message = $smsdata['message_body'];
	$live_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=fitter12&type=0&dlr=1&destination=" . urlencode($to) . "&source=fitter&message=" . urlencode($message);
    // $sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=fitter12&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $live_url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);
	curl_close($ch);
}

public function postCapture(){

	$data 			= Input::json()->all();
	$yet_to_connect_arr = array('FakeBuy', 'request_callback','FakeBuy','FakeBuy','FakeBuy','FakeBuy','FakeBuy');
	// if(in_array(Input::json()->get('capture_type'), $yet_to_connect_arr)){
	// }

	//set default status
	array_set($data, 'capture_status', 'yet to connect');
	$uuid = random_numbers(6);
	array_set($data, 'uuid', $uuid);

	$storecapture = Capture::create($data);
	if($storecapture){
		if(Input::json()->get('capture_type') == 'pre-register-fitmania'){
			$sndInstantSmsFinder	=	$this->customersms->fitmaniaPreRegister($data);
		}

		if(Input::json()->get('capture_type') == 'FakeBuy' && Input::json()->get('mobile') != ''){
			$smsdata = [
			'send_to' => Input::json()->get('mobile'),
			'message_body'=>'Hi '.Input::json()->get('name').', Thank you for your request to purchase the membership at '.Input::json()->get('vendor').'. We will get in touch with you shortly. Regards - Team Fitternity.',
			];
			$this->sendSMS($smsdata);
		}

		if(Input::json()->get('capture_type') == 'kutchi-minithon' && Input::json()->get('phone') != ''){

			$smsdata = [
			'send_to' => Input::json()->get('phone'),
			'message_body'=> "Dear ".Input::json()->get('name').". You have successfully registered for Royal Diamonds Kutchi Minithon 2016 under category of ".Input::json()->get('participation_category').".Your unique registration ID is ".$uuid.". Don't delete this message. This message is important for collecting Race BIB and Goodie Bag."
			];
			$this->sendSMS($smsdata);
		}

	}
	return Response::json($storecapture, 200);
}	

}
