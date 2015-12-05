<?PHP namespace App\Sms;

use Config;

Class FinderSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		if($data['show_location_flag']){
			$message 	=	"We have received a workout session request from ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on +91 92222 21131 for any queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a workout session request from ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on +91 92222 21131 for any queries. Regards - Team Fitternity.";
		}

		$label = 'BookTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}


	public function rescheduledBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		if($data['show_location_flag']){
			$message 	=	"We have received a workout / trial session request from ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on +91 92222 21131 for queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a workout / trial session request from ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on +91 92222 21131 for queries. Regards - Team Fitternity.";
		}

		$label = 'RescheduledTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}

	//currently not using reminder
	public function bookTrialReminder ($datshow_location_flaga, $delay){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));
		if($data['show_location_flag']){
			$message 	=	"We have received a workout / trial session request from ".ucwords($data['customer_name'])." for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on +91 92222 21131 for queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a workout / trial session request from ".ucwords($data['customer_name'])." for ".ucwords($data['finder_name']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on +91 92222 21131 for queries. Regards - Team Fitternity.";
		}

		$label = 'BookTrialReminder-F';

		return $this->sendToWorker($to, $message, $label);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		
		if($data['show_location_flag']){
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". Please note the address: ".ucwords($data['finder_address'])." Contact person: ".ucwords($data['finder_poc_for_customer_name']);
		}else{
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).". Please note the address: ".ucwords($data['finder_address'])." Contact person: ".ucwords($data['finder_poc_for_customer_name']);
		}

		$label = 'TrialRmdBefore1Hr-C';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}


	public function cancelBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		if($data['show_location_flag']){
			$message 	=	"We have received a cancellation request for session booked for ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).".Please call us on +91 92222 21131 for queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a cancellation request for session booked for ".ucwords($data['customer_name'])." $cusomterno for ".ucwords($data['finder_name']).". Please call us on +91 92222 21131 for queries. Regards - Team Fitternity.";
		}

		$label = 'CancelBookTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}



	

}