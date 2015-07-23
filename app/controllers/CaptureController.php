<?php

use App\Sms\CustomerSms as CustomerSms;


class CaptureController extends \BaseController {


	public function __construct(CustomerSms $customersms){

		$this->findersms 				=	$findersms;

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


	protected $customersms;

	public function postCapture(){
		
		$data = array(
				'capture_type' => Input::json()->get('capture_type'),
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'),
				'mobile' => Input::json()->get('mobile'),
				'created_at' => date('Y-m-d H:i:s')
			);

		$yet_to_connect_arr = array('FakeBuy', 'request_callback','FakeBuy','FakeBuy','FakeBuy','FakeBuy','FakeBuy');
		// if(in_array(Input::json()->get('capture_type'), $yet_to_connect_arr)){
		// }

		$storecapture = Capture::create($data);
		if($storecapture){
			if(Input::json()->get('capture_type') == 'pre-register-fitmania'){
				$sndInstantSmsFinder	=	$this->findersms->bookTrial($data);
			}
		}

		return Response::json($storecapture);
	}	

}
