<?PHP namespace App\Sms;

use Config;

Class FinderSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." ".$cusomterno." for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on ".Config::get('app.contact_us_vendor_number')." for any queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." ".$cusomterno." for ".ucwords($data['finder_name']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to the email sent for more details. Call us on ".Config::get('app.contact_us_vendor_number')." for any queries. Regards - Team Fitternity.";
		}

		$label = 'BookTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}


	public function rescheduledBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message = "We have received a re-schedule request for a earlier ".$session_type." booked. ".ucwords($data['service_name'])." ".$session_type." session for ".ucwords($data['customer_name'])." ".$cusomterno." at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." is now re-scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to email sent for more details & call us on ".Config::get('app.contact_us_vendor_number')." for queries";
		}else{
			$message = "We have received a re-schedule request for a earlier ".$session_type." booked. ".ucwords($data['service_name'])." ".$session_type." session for ".ucwords($data['customer_name'])." ".$cusomterno." at ".ucwords($data['finder_name'])." is now re-scheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please refer to email sent for more details & call us on ".Config::get('app.contact_us_vendor_number')." for queries";
		}

		$label = 'RescheduledTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}

	//currently not using reminder
	public function bookTrialReminder ($datshow_location_flaga, $delay){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		if($data['show_location_flag']){
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on ".Config::get('app.contact_us_vendor_number')." for queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a ".$session_type." session request from ".ucwords($data['customer_name'])." for ".ucwords($data['finder_name']).". The slot has been confirmed for ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on ".Config::get('app.contact_us_vendor_number')." for queries. Regards - Team Fitternity.";
		}

		$label = 'BookTrialReminder-F';

		return $this->sendToWorker($to, $message, $label);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

        $cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		if($data['show_location_flag']){
			$message 	=	"This is a reminder for session scheduled for ".ucwords($data['customer_name'])." ".$cusomterno." at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." on ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have confirmed the session. Incase there is a no-show we will followup and get back to you. Regards - Team Fitternity.";
		}else{
			$message 	=	"This is a reminder for session scheduled for ".ucwords($data['customer_name'])." ".$cusomterno." at ".ucwords($data['finder_name'])." on ".date(' jSF\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We have confirmed the session. Incase there is a no-show we will followup and get back to you. Regards - Team Fitternity.";
		}

		$label = 'TrialRmdBefore1Hr-F';
		$priority = 0;

		return $this->sendToWorker($to, $message, $label, $priority, $delay);
	}



	public function cancelBookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$cusomterno = ($data['share_customer_no'] == true && $data['customer_phone'] != '') ? "(".$data['customer_phone'].")" : '';

		if($data['show_location_flag']){
			$message 	=	"We have received a cancellation request for session booked for ".ucwords($data['customer_name'])." ".$cusomterno." for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).".Please call us on ".Config::get('app.contact_us_vendor_number')." for queries. Regards - Team Fitternity.";
		}else{
			$message 	=	"We have received a cancellation request for session booked for ".ucwords($data['customer_name'])." ".$cusomterno." for ".ucwords($data['finder_name']).". Please call us on ".Config::get('app.contact_us_vendor_number')." for queries. Regards - Team Fitternity.";
		}

		$label = 'CancelBookTrial-F';

		return $this->sendToWorker($to, $message, $label);
	}

	public function sendPgOrderSms ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));

		$service_duration = (isset($data['service_duration']) && $data['service_duration'] != '') ? $data['service_duration'] : '';

        if(isset($data['finder_category_id']) && $data['finder_category_id'] == 41){

			$message = "Hey ".ucwords($data['finder_name']).", Greetings from Fitternity! ".ucwords($data['customer_name'])." has purchased a ".$service_duration." personal training membership with you. The subscription code is: ".ucwords($data['_id']).". Please refer to the email sent for more details. Incase of any queries call us on ".Config::get('app.contact_us_vendor_number').". Regards - Team Fitternity";

		}else{

			$message 	=	"Hi. Greetings from Fitternity! We have processed a membership sale for ".ucwords($data['finder_name']).". Customer name: ".ucwords($data['customer_name'])." Membership purchased: ".ucwords($data['service_name']).". The details of the transaction have been shared with you on email. If you have any questions or need assistance call us on ".Config::get('app.contact_us_vendor_number')." or email us on info@fitternity.com.";

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

			$message 	=	"We have confirmed a slot for the 3 day Crossfit induction for ".ucwords($data['customer_name'])."   on ".date(' jS F\, Y \(l\) ', strtotime($data['preferred_starting_date']) ) ." for ".$batch_text." batch.  They are eligible for a fitness kit, hassle free access and personalised experience. Please refer to the email sent for more details. Call us on ".Config::get('app.contact_us_vendor_number')." for any queries. Regards, Team Fitternity";
		}

		$label = 'PgOrder-V';
		$priority = 1;

		return $this->sendToWorker($to, $message, $label, $priority);
	}



	public function buyLandingpagePurchaseEefashrof ($data, $count){

		$to 		=  	['9730401839','9773348762'];
		$message 	=	"We have received the payment for ".ucwords($data['customer_name'])."for The 21 day Dupernova Challenge with Eefa Shrof. The details of the customer are: Customer Name: ".ucwords($data['customer_name'])." Contact Number: ".$data['customer_phone']." Count: ".$count;
		$label 		= 	'buyLandingpagePurchaseEefashrof-F';

		return $this->sendToWorker($to, $message, $label);
	}


}