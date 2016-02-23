<?PHP namespace App\Sms;

use Config;

Class FinderSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on ".Config::get('app.customer_care_number')." for any queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on ".Config::get('app.customer_care_number')." for any queries. Regards - Team Fitternity.";
		}

		$label = 'BookTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}


	public function rescheduledBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on ".Config::get('app.customer_care_number')." for queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on ".Config::get('app.customer_care_number')." for queries. Regards - Team Fitternity.";
		}

		$label = 'RescheduledTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}

	//currently not using reminder
	public function bookTrialReminder ($datshow_location_flaga, $delay){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on ".Config::get('app.customer_care_number')." for queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." for ".ucwords($data['finder_name']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on ".Config::get('app.customer_care_number')." for queries. Regards - Team Fitternity.";
		}

		$label = 'BookTrialReminder-F';

		return $this->sendToWorker($to, $message, $label);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

        $cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		if($data['show_location_flag']){
			$message 	=	"This is a reminder for session scheduled for ".ucwords($data['customer_name'])." at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." on ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have confirmed the session. Incase there is a no-show we will followup and get back to you. Regards - Team Fitternity.";
		}else{
			$message 	=	"This is a reminder for session scheduled for ".ucwords($data['customer_name'])." at ".ucwords($data['finder_name'])." on ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have confirmed the session. Incase there is a no-show we will followup and get back to you. Regards - Team Fitternity.";
		}

		$label = 'TrialRmdBefore1Hr-F';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}



	public function cancelBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		if($data['show_location_flag']){
			$message 	=	"We have received a cancellation request for session booked for ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).".Please call us on ".Config::get('app.customer_care_number')." for queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a cancellation request for session booked for ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).". Please call us on ".Config::get('app.customer_care_number')." for queries. Regards - Team Fitternity.";
		}

		$label = 'CancelBookTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}

	public function sendPgOrderSms ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$message 	=	"Hi. Greetings from Fitternity! We have processed a membership sale for ".ucwords($data['finder_name']).". Customer name: ".ucwords($data['customer_name'])." Membership purchased: ".ucwords($data['service_name']).". The details of the transaction have been shared with you on email. If you have any questions or need assistance call us on ".Config::get('app.customer_care_number')." or email us on info@fitternity.com.";

		$label = 'PgOrder-V';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}


}