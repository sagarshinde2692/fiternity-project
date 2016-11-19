<?php

use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Services\CustomerReward as CustomerReward;
use App\Services\Utilities as Utilities;


class CaptureController extends \BaseController {

	protected $customersms;

	public function __construct(CustomerSms $customersms,CustomerMailer $customermailer){

		$this->customersms 				=	$customersms;
		$this->customermailer 			=	$customermailer;

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
	
	
	/*$live_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode($to) . "&source=fitter&message=" . urlencode($message);*/
    // $sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);

	/*$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $live_url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);
	curl_close($ch);*/
}

public function postCapture(){

	$data 					= 	Input::json()->all();
	$yet_to_connect_arr 	= 	array('FakeBuy', 'request_callback','FakeBuy','FakeBuy','FakeBuy','FakeBuy','FakeBuy');
	// if(in_array(Input::json()->get('capture_type'), $yet_to_connect_arr)){
	// }

	//set default status
	array_set($data, 'capture_status', 'yet to connect');
	$uuid 	= random_numbers(6);
	array_set($data, 'uuid', $uuid);


    if(isset($data['batches']) && $data['batches'] != ""){
        if(is_array($data['batches'])){
            $data['batches'] = $data['batches'];
        }else{
            $data['batches'] = json_decode($data['batches'],true);
        }

        foreach ($data['batches'] as $key => $value) {

            if(isset($value['slots'][0]['start_time']) && $value['slots'][0]['start_time'] != ""){
                $data['batch_time'] = strtoupper($value['slots'][0]['start_time']);
                break;
            }
        }
    }

    if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '') {
        if(trim(Input::json()->get('preferred_starting_date')) != '-'){
            $date_arr = explode('-', Input::json()->get('preferred_starting_date'));
            $preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
            array_set($data, 'start_date', $preferred_starting_date);
            array_set($data, 'preferred_starting_date', $preferred_starting_date);
        }
    }

    if(isset($data['finder_id']) && $data['finder_id'] != ""){
    	
    	$utilities = new Utilities();

        $finderData = $utilities->getFinderData($data['finder_id']);
        $data  = array_merge($data,$finderData);
    }

    
    $data['customer_name'] = $data['name'];
	$data['customer_email'] = $data['email'];
	$data['customer_phone'] = $data['phone'];
	$data['customer_address'] = $data['address'];

	$data['customer_id'] = autoRegisterCustomer($data);

	$customer = Customer::find((int)$data['customer_id']);

    if(isset($data['customer_address']) && is_array($data['customer_address']) && !empty($data['customer_address'])){
    	
        $customerData['address'] = $data['customer_address'];
        $customer->update($customerData);

        $data['customer_address'] = $data['address'] = implode(",", array_values($data['customer_address']));
    }

    if(isset($data['myreward_id']) && $data['myreward_id'] != ""){

    	$customerreward = new CustomerReward();

        $createMyRewardCapture = $customerreward->createMyRewardCapture($data);

        if($createMyRewardCapture['status'] !== 200){

            return Response::json($createMyRewardCapture,$createMyRewardCapture['status']);
        }

        $my_reward = Myreward::find((int)$data['myreward_id'])->toArray();

        $data['my_reward'] = $my_reward;
    }

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

		if(Input::json()->get('capture_type') == 'personal-trainer-page' && Input::json()->get('phone') != ''){

			$smsdata = [
				'send_to' => Input::json()->get('phone'),
				'message_body'=> "We have received your request for Personal trainer. Our Fitness Concierge Manager will contact you within the next 48 hours to assist you. For any further queries you can call us on 022-61222222."
			];
			$this->sendSMS($smsdata);
		}

		if(isset($data['capture_type']) && $data['capture_type'] == 'personal-trainer-page' && isset($data['myreward_id']) && $data['myreward_id'] != ""){
			
			$data['label'] = "Reward-PersonalTrainer-AtHome-Customer";

			$this->customermailer->rewardClaim($data);
			
			return Response::json($createMyRewardCapture, $createMyRewardCapture['status']);
		}


	}

	return Response::json($storecapture, 200);
}



public function getCaptureDetail($captureid){

		$orderdata 		=	Capture::find($captureid);
		if(isset($orderdata->preferred_starting_date) && $orderdata->preferred_starting_date == ""){
			unset($orderdata->preferred_starting_date);
		}

		if(!$orderdata){
			return $this->responseNotFound('Request not found');
		}

		$responsedata 	= ['capture' => $orderdata,  'message' => 'Request Detail'];
		return Response::json($responsedata, 200);

	}

}
