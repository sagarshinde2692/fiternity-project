<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." for ".ucwords($data['service_name'])." at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Please flash this subscription code for the session: ".$data['code'].". For address refer to http://www.fitternity.com/".$data['finder_slug'] .". Thank you for using Fitternity.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." for ".ucwords($data['service_name'])." at ".ucwords($data['finder_name']).". Please flash this subscription code for the session: ".$data['code'].". For address refer to http://www.fitternity.com/".$data['finder_slug'] .". Thank you for using Fitternity.";
		}
		
		$label = 'BookTrial-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}



	public function rescheduledBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Thank you for using Fitternity. For any queries call us on +91 92222 21131 or reply to this message.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).". Thank you for using Fitternity. For any queries call us on +91 92222 21131 or reply to this message.";
		}
		
		$label = 'RescheduledTrial-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}

	public function bookTrialReminderBefore1Min ($data, $delay){

		//testing for berfore12hour template
		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your workout session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on +91 92222 21131 or reply to this message.";
		
		$label = 'TrialRmdBefore12Hr-C';

		$this->sendToWorker($to, $message, $label);

		//testing for berfore1hour template
		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Please note the address: ".ucwords($data['finder_name']).", ".ucwords($data['finder_address']).", ".ucwords($data['finder_location']).". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		
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

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your workout session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." scheduled for ".ucwords($data['service_name'])." for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on +91 92222 21131 or reply to this message.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your workout session at ".ucwords($data['finder_name'])." scheduled for ".ucwords($data['service_name'])." for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on +91 92222 21131 or reply to this message.";
		}

		$label = 'TrialRmdBefore12Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);

	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		//$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Please note the address: ".ucwords($data['finder_name']).", ".ucwords($data['finder_address']).", ".ucwords($data['finder_location']).". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		
		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". For address please refer to http://www.fitternity.com/".$data['finder_slug'].". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).". For address please refer to http://www.fitternity.com/".$data['finder_slug'].". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		}
		
		$label = 'TrialRmdBefore1Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}



	public function bookTrialReminderAfter2Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hope you had a good session at ".ucwords($data['finder_name']).". We will call you later to hear about it and share discounts in case you wish to subscribe. In the meantime you can rate your experience at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." here http://www.fitternity.com/".$data['finder_slug'];
		}else{
			$message 	=	"Hope you had a good session at ".ucwords($data['finder_name']).". We will call you later to hear about it and share discounts in case you wish to subscribe. In the meantime you can rate your experience at ".ucwords($data['finder_name'])." here http://www.fitternity.com/".$data['finder_slug'];
		}
		
		$label = 'TrialRmdAfter2Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);

	}


	public function cancelBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". has been cancelled basis your request. Thank you for using Fitternity.com. For any queries call us on +91 92222 21131 or reply to this message";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session at ".ucwords($data['finder_name'])." has been cancelled basis your request. Thank you for using Fitternity.com. For any queries call us on +91 92222 21131 or reply to this message.";
		}
		
		$label = 'CancelBookTrial-C';

		return $this->sendToWorker($to, $message, $label);
	}



	public function manualBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['finder_category_id'] == 45){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Thank you for your request to avail the healthy food trial pack. We will get in touch with you shortly. Team Fitternity.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Thank you for requesting a session at ".ucwords($data['finder_name'])." through fitternity. Our team will get in touch with you shortly and help you arrange your session. Thanks - Team Fitternity.";
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

		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for requesting purchase of ". ucwords($data['service_name'])." ". ucwords($data['service_duration']). " at ". ucwords($data['finder_name']).". We will get in touch with you shortly to help you get started. In the meantime you can reach us on 09222221131 for any queries. Team Fitternity";

		if($data['type'] == 'arsenalmembership'){
			if($data['service_duration'] == 'Renew'){
				$message 	=	"Hi ".ucwords($data['customer_name']).". Thank You for requesting purchase of AMSC membership  Renewal. We will get in touch with you shortly to help you get started. In the meantime you can reach us on 09222221131 for any queries. Team Fitternity.";
			}else{
				$message 	=	"Hi ".ucwords($data['customer_name']).". Thank You for requesting purchase of AMSC membership. We will get in touch with you shortly to help you get started. In the meantime you can reach us on 09222221131 for any queries. Team Fitternity.";
			}
		}

		$label = 'CodOrder-C';

		return $this->sendToWorker($to, $message, $label);
	}


	public function sendPgOrderSms ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		
		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for requesting purchase of ". ucwords($data['service_name'])." ". ucwords($data['service_duration']). " at ". ucwords($data['finder_name']).". Your subscription ID is ".$data['_id'].". We will be sending you the purchase invoice and details on email. In the meantime you can reach us on 09222221131 for any queries. Team Fitternity";

		$label = 'PgOrder-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}


	public function buyServiceThroughFitmania ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		
		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for purchasing your membership at ". ucwords($data['finder_name']).". Your subscription ID is ".$data['_id'].". We will be sending you an email with the all details you need to start the membership. Call us on +91922221131 for any queries.";

		$label = 'BuySrvFitmania-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}

	public function buyServiceMembershipThroughFitmania ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		
		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for purchasing your membership at ". ucwords($data['finder_name']).". Your subscription ID is ".$data['_id'].". We will be sending you an email with the all details you need to start the membership. Call us on +91922221131 for any queries.";

		$label = 'BuySrvMbrFitM-C';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}

	public function buyServiceHealthyTiffinThroughFitmania ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		
		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank you for purchasing healthy food from ". ucwords($data['finder_name']).". Your Order ID is ".$data['_id'].". We will be sending you an email with the all details. Call us on +91922221131 for any queries.";

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
		$message 	=	"Hi ".ucwords($data['customer_name']).". Thank You for requesting purchase of AMSC membership  Renewal. We will get in touch with you shortly to help you get started. In the meantime you can reach us on 09222221131 for any queries. Team Fitternity.";

		$label = 'BuyArsenalMbrShip-C';

		return $this->sendToWorker($to, $message, $label);
	}


	public function buyLandingpagePurchase ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	ucwords($data['sms_body']);

		$label = 'buyLandingpagePurchase-C';

		return $this->sendToWorker($to, $message, $label);
	}





}