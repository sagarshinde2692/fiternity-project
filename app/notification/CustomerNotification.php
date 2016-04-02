<?PHP namespace App\Notification;

use Config;

Class CustomerNotification extends Notification{

	public function bookTrial ($data){

		$to 		=  	array($data['reg_id']);

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$text 	=	"Hi ".ucwords($data['customer_name']).". Thank you for using Fitternity. Your ".$session_type." session for ".ucwords($data['service_name'])." at ".ucwords($data['finder_name'])." - ".ucwords($data['finder_location'])." is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on ".Config::get('app.customer_care_number')." for any queries. Regards - Team Fitternity.";
		}else{
			$text 	=	"Hi ".ucwords($data['customer_name']).". Thank you for using Fitternity. Your ".$session_type." session for ".ucwords($data['service_name'])." at ".ucwords($data['finder_name'])."  is confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on ".Config::get('app.customer_care_number')." for any queries. Regards - Team Fitternity.";
		}
		
		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);

		$label = 'BookTrial-C';
		$priority = 1;

		return $this->sendToWorker($to, $text, $notif_id, $notif_type, $notif_object, $label, $priority);
	}



	public function rescheduledBookTrial ($data){

		$to 		=  	array($data['reg_id']);

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$text 	=	"Hey ".ucwords($data['customer_name']).". Your ".$session_type." session is re-scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Thank you for using Fitternity. For any queries call us on ".Config::get('app.customer_care_number')." or mail us on ".Config::get('app.contact_us_customer_email').".";
		}else{
			$text 	=	"Hey ".ucwords($data['customer_name']).". Your ".$session_type." session is re-scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." at for ".ucwords($data['service_name'])." ".ucwords($data['finder_name']).". Thank you for using Fitternity. For any queries call us on ".Config::get('app.customer_care_number')." or mail us on ".Config::get('app.contact_us_customer_email').".";
		}

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);
		
		$label = 'RescheduledTrial-C';
		$priority = 1;


		return $this->sendToWorker($to, $text, $notif_id, $notif_type, $notif_object, $label, $priority);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array($data['reg_id']);
		$bity_url 	= 	$google_pin = "";
		
		if($data['show_location_flag']){
			$text 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". For address please refer to http://www.fitternity.com/".$data['finder_slug']." Contact person: ".ucwords($data['finder_poc_for_customer_name']).$google_pin." have a great workout!";
		}else{
			$text 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". For address please refer to http://www.fitternity.com/".$data['finder_slug']." ".$google_pin." have a great workout!";
		}

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);

		$label = 'TrialRmdBefore1Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $text, $notif_id, $notif_type, $notif_object, $label, $priority, $delay);
	}


	public function bookTrialReminderAfter2Hour ($data, $delay){

		$to 		=  	array($data['reg_id']);

		if($data['show_location_flag']){
			$text 	=	"Hope you had a chance to attend the session ".ucwords($data['finder_name']).". If you attended- rate your experience and win awesome merchandise and unlock Rs. 250 off. Click here to post a review: http://www.fitternity.com/".$data['finder_slug'];
		}else{
			$text 	=	"Hope you had a chance to attend the session ".ucwords($data['finder_name']).". If you attended- rate your experience and win awesome merchandise and unlock Rs. 250 off. Click here to post a review: http://www.fitternity.com/".$data['finder_slug'];
		}

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);

		$label = 'TrialRmdAfter2Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $text, $notif_id, $notif_type, $notif_object, $label, $priority, $delay);
	}


}