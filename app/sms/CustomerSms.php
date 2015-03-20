<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Thank you for using Fitternity. For any queries call us on +91 92222 21131 or reply to this message.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).". Thank you for using Fitternity. For any queries call us on +91 92222 21131 or reply to this message.";
		}
		
		return $this->sendTo($to, $message);
	}



	public function rescheduledBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Thank you for using Fitternity. For any queries call us on +91 92222 21131 or reply to this message.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).". Thank you for using Fitternity. For any queries call us on +91 92222 21131 or reply to this message.";
		}
		
		return $this->sendTo($to, $message);
	}

	public function bookTrialReminderBefore1Min ($data, $delay){

		//testing for berfore12hour template
		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your workout session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on +91 92222 21131 or reply to this message.";
		
		//return $this->sendTo($to, $message, $delay);
		$this->sendTo($to, $message);

		//testing for berfore1hour template
		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Please note the address: ".ucwords($data['finder_name']).", ".ucwords($data['finder_address']).", ".ucwords($data['finder_location']).". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		
		// return $this->sendTo($to, $message, $delay);
		$this->sendTo($to, $message);

		//testing for bookTrialReminderAfter2Hour template
		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hope you had a good session. We will call you later to hear about it and share discounts in case you wish to subscribe. In the meantime you can rate your experience at ".ucwords($data['finder_name'])." here http://www.fitternity.com/".$data['finder_slug'];
		
		//return $this->sendTo($to, $message, $delay);
		return $this->sendTo($to, $message);
	}

	public function bookTrialReminderBefore12Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your workout session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." scheduled for ".ucwords($data['service_name'])." for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on +91 92222 21131 or reply to this message.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your workout session at ".ucwords($data['finder_name'])." scheduled for ".ucwords($data['service_name'])." for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on +91 92222 21131 or reply to this message.";
		}
		return $this->sendTo($to, $message, $delay);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		//$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Please note the address: ".ucwords($data['finder_name']).", ".ucwords($data['finder_address']).", ".ucwords($data['finder_location']).". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		
		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". For address please refer to http://www.fitternity.com/".$data['finder_slug'].". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).". For address please refer to http://www.fitternity.com/".$data['finder_slug'].". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		}
		return $this->sendTo($to, $message, $delay);
	}



	public function bookTrialReminderAfter2Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hope you had a good session. We will call you later to hear about it and share discounts in case you wish to subscribe. In the meantime you can rate your experience at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." here http://www.fitternity.com/".$data['finder_slug'];
		}else{
			$message 	=	"Hope you had a good session. We will call you later to hear about it and share discounts in case you wish to subscribe. In the meantime you can rate your experience at ".ucwords($data['finder_name'])." here http://www.fitternity.com/".$data['finder_slug'];
		}
		
		return $this->sendTo($to, $message, $delay);
	}


	public function cancelBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". has been cancelled basis your request. Thank you for using Fitternity.com. For any queries call us on +91 92222 21131 or reply to this message";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session at ".ucwords($data['finder_name'])." has been cancelled basis your request. Thank you for using Fitternity.com. For any queries call us on +91 92222 21131 or reply to this message.";
		}
		
		return $this->sendTo($to, $message);
	}




	public function manualBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey ".ucwords($data['customer_name']).". Thank you for the request to book a trial at ".ucwords($data['finder_name']).". We will call you shortly to arrange a time. Regards - Team Fitternity.";

		return $this->sendTo($to, $message);
	}


	public function manual2ndBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey ".ucwords($data['customer_name']).". Thank you for the request to book a trial at ".ucwords($data['finder_names']).". We will call you shortly to arrange a time. Regards - Team Fitternity.";

		return $this->sendTo($to, $message);
	}






	

}