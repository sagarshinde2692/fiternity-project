<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).".Thank you for using Fitternity.com. For any queries call us on +91 92222 21131 or reply to this message.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Your workout session is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at ".ucwords($data['finder_name']).".Thank you for using Fitternity.com. For any queries call us on +91 92222 21131 or reply to this message.";
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
		return $this->sendTo($to, $message);
	}

	public function bookTrialReminderBefore12Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));

		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your workout session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on +91 92222 21131 or reply to this message.";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Here is a reminder for your workout session at ".ucwords($data['finder_name'])." scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have sent you a mail on essentials you need to carry for the session. Incase if you would like to reschedule or cancel your session call us on +91 92222 21131 or reply to this message.";
		}
		return $this->sendTo($to, $message, $delay);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		//$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Please note the address: ".ucwords($data['finder_name']).", ".ucwords($data['finder_address']).", ".ucwords($data['finder_location']).". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		
		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". For address please refer to http://www.fitternity.com/".$finder_slug.". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).". For address please refer to http://www.fitternity.com/".$finder_slug.". Contact person: ".ucwords($data['finder_poc_for_customer_name']).". Have a great workout!";
		}
		return $this->sendTo($to, $message, $delay);
	}



	public function bookTrialReminderAfter2Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"bookTrialReminderAfter2Hour customer test sms";
		
		return $this->sendTo($to, $message, $delay);
	}

	

}