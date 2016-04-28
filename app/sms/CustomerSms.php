<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for using Fitternity. Your ".$session_type." session for ".ucwords($data['service_name'])." at ".ucwords($data['finder_name'])." - ".ucwords($data['finder_location'])." is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on ".Config::get('app.contact_us_customer_number')." for any queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for using Fitternity. Your ".$session_type." session for ".ucwords($data['service_name'])." at ".ucwords($data['finder_name'])."  is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on ".Config::get('app.contact_us_customer_number')." for any queries. Regards - Team Fitternity.";
		}
		
		$label = 'BookTrial-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}

	public function bookTrialFreeSpecial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		$message 	=	"Hey ".ucwords($data['customer_name']).". Thank you for requesting a ".$session_type." session at ".ucwords($data['finder_name'])." through fitternity. Our team will get in touch with you shortly and help you arrange your ".$session_type." session. Thanks - Team Fitternity.";

		$label = 'BookTrialFreeSpecial-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}
	
	public function rescheduledBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your ".$session_type." session is re-scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Thank you for using Fitternity. For any queries call us on ".Config::get('app.contact_us_customer_number')." or mail us on ".Config::get('app.contact_us_customer_email').".";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your ".$session_type." session is re-scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).". Thank you for using Fitternity. For any queries call us on ".Config::get('app.contact_us_customer_number')." or mail us on ".Config::get('app.contact_us_customer_email').".";
		}
		
		$label = 'RescheduledTrial-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}

	public function bookTrialReminderBefore1Min ($data, $delay){

		//testing for berfore12hour template
		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your ".$session_type." session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on ".Config::get('app.contact_us_customer_number')." or mail us on ".Config::get('app.contact_us_customer_email').".";
		
		$label = 'TrialRmdBefore12Hr-C';

		$this->sendToWorker($to, $message, $label);

		//testing for berfore1hour template
		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		/*$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Please note the address: ".ucwords($data['finder_name']).", ".ucwords(strip_tags($data['finder_address'])).", ".ucwords($data['finder_location']).". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";*/

		$message = "Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". For address please refer to http://www.fitternity.com/".$data['finder_slug']." Contact person: ".ucwords($data['finder_poc_for_customer_name'])." have a great ".$session_type."!";
		
		$label = 'TrialRmdBefore1Hr-C';

		$this->sendToWorker($to, $message, $label);

		//testing for bookTrialReminderAfter2Hour template
		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hope you had a good session at ".ucwords($data['finder_name']).". We will call you later to hear about it and share discounts in case you wish to subscribe. In the meantime you can rate your experience at ".ucwords($data['finder_name'])." here http://www.fitternity.com/".$data['finder_slug'];
		
		$label = 'TrialRmdAfter2Hr-C';

		return $this->sendToWorker($to, $message, $label);
	}


	public function bookTrialReminderBefore12Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your ".$session_type." session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." scheduled for ".ucwords($data['service_name'])." for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the ".$session_type." session. If you wish to reschedule or cancel your ".$session_type." session call us on ".Config::get('app.contact_us_customer_number')." or mail us on ".Config::get('app.contact_us_customer_email').". Regards - Team Fitternity";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your ".$session_type." session at ".ucwords($data['finder_name'])." scheduled for ".ucwords($data['service_name'])." for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the ".$session_type." session. If you wish to reschedule or cancel your ".$session_type." session call us on ".Config::get('app.contact_us_customer_number')." or mail us on ".Config::get('app.contact_us_customer_email').". Regards - Team Fitternity";
		}

		$label = 'TrialRmdBefore12Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}




	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$bity_url 	= 	$google_pin = "";

		// if(isset($finder_lat) && $finder_lat != "" && isset($finder_lon) && $finder_lon != ""){
		// 	$bity_url 	= 	bitly_url("https://maps.google.com/maps?q=$finder_lat,$finder_lon&ll=$finder_lat,$finder_lon");
		// 	$google_pin = "Google pin for directions: ".$bity_url;
		// }else{
		// 	$google_pin = "test ".$finder_lat.$finder_lon;
		// }


		// $google_pin = "test ".$finder_lat.$finder_lon;
		
		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". For address please refer to http://www.fitternity.com/".$data['finder_slug']." Contact person: ".ucwords($data['finder_poc_for_customer_name']).$google_pin." have a great workout!";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". For address please refer to http://www.fitternity.com/".$data['finder_slug']." ".$google_pin." have a great workout!";
		}

		$label = 'TrialRmdBefore1Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}


	public function bookTrialReminderAfter2Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hope you had a chance to attend the session ".ucwords($data['finder_name']).". If you attended- rate your experience and win awesome merchandise and unlock Rs. 250 off. Click here to post a review: http://www.fitternity.com/".$data['finder_slug'];
		}else{
			$message 	=	"Hope you had a chance to attend the session ".ucwords($data['finder_name']).". If you attended- rate your experience and win awesome merchandise and unlock Rs. 250 off. Click here to post a review: http://www.fitternity.com/".$data['finder_slug'];
		}

		$label = 'TrialRmdAfter2Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}


	public function cancelBookTrial ($data){

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your ".$session_type." session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". has been cancelled basis your request. Thank you for using Fitternity. For any queries call us on ".Config::get('app.contact_us_customer_number')." or mail us on ".Config::get('app.contact_us_customer_email').".";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your ".$session_type." session at ".ucwords($data['finder_name'])." has been cancelled basis your request. Thank you for using Fitternity. For any queries call us on ".Config::get('app.contact_us_customer_number')." or mail us on ".Config::get('app.contact_us_customer_email').".";
		}

		$label = 'CancelTrial-C';
		return $this->sendToWorker($to, $message, $label);
	}


	public function manualBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		switch ($data['finder_category_id']) {

			case 41 : $message 	=	"Hi ".ucwords($data['customer_name']).", Thank you for using Fitternity. We will get in touch with you shortly regarding your personal training trial with ".ucwords($data['finder_name'])." . In case of any other queries call us on ".Config::get('app.contact_us_customer_number').". Regards - Team Fitternity"; break;

			case 45 : $message 	=	"Hey ".ucwords($data['customer_name']).". Thank you for your request to avail the healthy food trial pack. We will get in touch with you shortly. Team Fitternity."; break;

			default: $message 	=	"Hey ".ucwords($data['customer_name']).". Thank you for requesting a session at ".ucwords($data['finder_name'])." through fitternity. Our team will get in touch with you shortly and help you arrange your session. Thanks - Team Fitternity."; break;
		}

		$label = 'ManualBookTrial-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}


	public function manual2ndBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey ".ucwords($data['customer_name']).". Thank you for the request to book a trial at ".ucwords($data['finder_names']).". We will call you shortly to arrange a time. Regards - Team Fitternity.";

		$label = 'Manual2ndBookTrial-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}


	public function sendCodOrderSms ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for requesting purchase of membership at ".ucwords($data['finder_name']).". We will get in touch with you shortly to help you get started. In the meantime you can reach us on ".Config::get('app.contact_us_customer_number')." for any queries. Regards - Team Fitternity";

		if($data['type'] == 'arsenalmembership'){
			if($data['service_duration'] == 'Renew'){
				$message 	=	"Hi ".ucwords($data['customer_name']).". Thank You for requesting purchase of AMSC membership  Renewal. We will get in touch with you shortly to help you get started. In the meantime you can reach us on ".Config::get('app.contact_us_customer_number')." for any queries. Team Fitternity.";
			}else{
				$message 	=	"Hi ".ucwords($data['customer_name']).". Thank You for requesting purchase of AMSC membership. We will get in touch with you shortly to help you get started. In the meantime you can reach us on ".Config::get('app.contact_us_customer_number')." for any queries. Team Fitternity.";
			}
		}

		if($data['type'] == 'crossfit-week'){

			$batch_text = "";
            $batch_array = array();

            if(isset($data['batches']) && $data['batches'] != ""){

	            foreach ($data['batches'] as $key => $value) {

	                $batch_array[] = ucwords($value['weekday']);
	            }

	            $batch_text = implode("-", $batch_array);
	        }

			$message 	=	"Hi ".ucwords($data['customer_name']).", Thank you for using Fitternity. Your 3 day Crossfit induction at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." is confirmed for the ".$batch_text." batch, starting ".date(' jS F\, Y \(l\) ', strtotime($data['preferred_starting_date']) ) .".  Please refer to the mail for more details. Call us on ".Config::get('app.contact_us_customer_number')." for any queries. Regards - Team Fitternity";
		}

		$label = 'CodOrder-C';

		return $this->sendToWorker($to, $message, $label);
	}


	public function sendPgOrderSms ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if(isset($data['finder_category_id']) && $data['finder_category_id'] == 41){

			$message = "Hey ".ucwords($data['customer_name']).", Thank you for purchasing membership for personal training with ".ucwords($data['finder_name']).". Your subscription code is: ".$data['_id']." . For any queries call us on ".Config::get('app.contact_us_customer_number').". Regards - Team Fitternity";

		}else{

			if($data['type'] == 'womens-day'){

				$message = "Hi ".ucwords($data['customer_name']).". Thank you for purchasing of membership at ". ucwords($data['finder_name']).". Your subscription ID is ".$data['_id'].".  We will be sending you an email with the all details you need to start the membership. Call us on ".Config::get('app.contact_us_customer_number')." for any queries.";
			}else{

				$message = "Hi ".ucwords($data['customer_name']).". Thank you for purchasing of membership at ". ucwords($data['finder_name']).". Your subscription ID is ".$data['_id'].". We will be sending you the purchase invoice and details on email. In the meantime you can reach us on ".Config::get('app.contact_us_customer_number')." for any queries. Regards - Team Fitternity";
			}

		}

		if($data['type'] == 'crossfit-week'){

			$batch_text = "";
            $batch_array = array();

            if(isset($data['batches']) && $data['batches'] != ""){

	            foreach ($data['batches'] as $key => $value) {

	                $batch_array[] = ucwords($value['weekday']);
	            }

	            $batch_text = implode("-", $batch_array);
	        }

			$message 	=	"Hi ".ucwords($data['customer_name']).", Thank you for using Fitternity. Your 3 day Crossfit induction at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." is confirmed for the ".$batch_text." batch, starting ".date(' jS F\, Y \(l\) ', strtotime($data['preferred_starting_date']) ) .".  Please refer to the mail for more details. Call us on ".Config::get('app.contact_us_customer_number')." for any queries. Regards - Team Fitternity";
		}

		$label = 'PgOrder-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}


	public function buyServiceThroughFitmania ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for purchasing your membership at ". ucwords($data['finder_name']).". Your subscription ID is ".$data['_id'].". We will be sending you an email with the all details you need to start the membership. Call us on ".Config::get('app.contact_us_customer_number')." for any queries.";
		$label = 'BuySrvFitmania-C';
		$priority = 1;
		return $this->sendToWorker($to, $message, $label, $priority);
	}


	public function buyServiceMembershipThroughFitmania ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for purchasing your membership at ". ucwords($data['finder_name']).". Your subscription ID is ".$data['_id'].". We will be sending you an email with the all details you need to start the membership. Call us on ".Config::get('app.contact_us_customer_number')." for any queries.";

		$label = 'BuySrvMbrFitM-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}

	public function buyServiceHealthyTiffinThroughFitmania ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for purchasing healthy food from ". ucwords($data['finder_name']).". Your Order ID is ".$data['_id'].". We will be sending you an email with the all details. Call us on ".Config::get('app.contact_us_customer_number')." for any queries.";

		$label = 'BuySrvHltTifFitM-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}

	public function forgotPasswordApp ($data){

		$to 		=  	array_merge(explode(',', $data['contact_no']));

		$message 	=	"Hello ".ucwords($data['name']).", The authorisation code required for resetting your password on Fitternity is ".$data['otp'] ;

		$label = 'ForgotPwdApp-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}

	public function fitmaniaPreRegister ($data){

		$to 		=  	array_merge(explode(',', $data['mobile']));

		$message 	=	"Thanks for pre-registering on FitMania Sale by Fitternity.com. We will be getting in touch with you to share more details. Spread the word http://on.fb.me/1JgBYIU .";

		$label = 'FitMPreRegister-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}


	public function buyArsenalMembership ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		// $message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for your payment of Fitternity.com towards Arsenal Mumbai Supporters Club, we acknowledge the receipt of the same. You will soon receive an email with the details. Regards, Team Fitternity.";
		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank You for requesting purchase of AMSC membership  Renewal. We will get in touch with you shortly to help you get started. In the meantime you can reach us on ".Config::get('app.contact_us_customer_number')." for any queries. Team Fitternity.";

		$label = 'BuyArsenalMbrShip-C';

		return $this->sendToWorker($to, $message, $label);
	}


	public function buyLandingpagePurchase ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	ucwords($data['sms_body']);

		$label = 'buyLandingpagePurchase-C';

		return $this->sendToWorker($to, $message, $label);
	}

	public function generalSms ($data){

		$to 		=  	array($data['to']);
		$message 	= 	$data['message'];
		$label 		= 	$data['label'];

		return $this->sendToWorker($to, $message, $label);
	}

	public function missedCallDelay ($data,$delay){

		$current_date = date('Y-m-d 00:00:00');

        $from_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date))));
        $to_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date." + 1 days"))));

		$booktrial  = \Booktrial::where('_id','!=',(int) $data['_id'])->where('customer_phone','LIKE','%'.substr($data['customer_phone'], -8).'%')->where('missedcall_batch','exists',true)->where('created_at','>',$from_date)->where('created_at','<',$to_date)->orderBy('_id','desc')->first();
		if(!empty($booktrial) && isset($booktrial->missedcall_batch) && $booktrial->missedcall_batch != ''){
			$batch = $booktrial->missedcall_batch + 1;
		}else{
			$batch = 1;
		}

		$missedcall_no = \Ozonetelmissedcallno::where('batch',$batch)->get()->toArray();

		if(empty($missedcall_no)){

			$missedcall_no = \Ozonetelmissedcallno::where('batch',1)->get()->toArray();
		}

		foreach ($missedcall_no as $key => $value) {

			switch ($value['type']) {
				case 'yes': $yes = $value['number'];break;
				case 'no': $no = $value['number'];break;
				case 'reschedule': $reschedule = $value['number'];break;
			}

		}
	
		$slot_date 			=	date('d-m-Y', strtotime($data['schedule_date']));
		$datetime 			=	strtoupper($slot_date ." ".$data['schedule_slot_start_time']);

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message = "Hi ".$data['customer_name']." This is regarding your session with ".ucwords($data['finder_name'])." - ".ucwords($data['finder_location'])." on ".$datetime.". Do you plan to attend? Reply by missed call. Yes will go: ".$yes." , No - cancel it: ".$no." , Want to reschedule: ".$reschedule. ", Regards - Team Fitternity";

		$label = 'MissedCall-C';
		$priority = 0;

		$booktrial = \Booktrial::find((int) $data['_id']);
		$booktrial->missedcall_batch = $batch;
		$booktrial->update();

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}

	public function confirmTrial($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$message = "Thank you for confirming the session at ".ucwords($data['finder_name'])." on ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Hope you have a great workout. If you need further assistance call us on ".Config::get('app.contact_us_customer_number').". Regards - Team Fitternity";

		$label = 'ConfirmTrial-C';

		return $this->sendToWorker($to, $message, $label);

	}

	public function cancelTrial($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$message = "Your session at ".ucwords($data['finder_name'])." has been cancelled. Book a trial from over 1,000+ options across yoga, crossfit, zumba, kickboxing and more on www.fitternity.com. Call ".Config::get('app.contact_us_customer_number')." for assistance. Regards - Team Fitternity";

		$label = 'CancelTrial-C';

		return $this->sendToWorker($to, $message, $label);

	}

	public function rescheduleTrial($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		$message = "We have received your reschedule request for trial session at ".ucwords($data['finder_name']).". You will receive a call from our team shortly. Regards - Team Fitternity";

		$label = 'RescheduleTrial-C';

		return $this->sendToWorker($to, $message, $label);

	}



}