<?PHP namespace App\Mailers;

use Config,Mail;

Class FinderMailer extends Mailer {

	public function welcome(){
		//$email_template, $template_data = [], $message_data = [] ;

	}

	public function bookTrial ($data){

		// $email_template = 'emails.test';
		$email_template = 'emails.finder.autobooktrial';
		$template_data 	= $data;
		if($data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_autobook_trial'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_autobook_trial');
		} 

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' =>  $data['finder_poc_for_customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Session request from customer '.ucwords($data['customer_name']).' at '.ucwords($data['finder_name']).' has been confirmed | Fitternity'
			);

		$label = 'BookTrial-F';

		return $this->sendToWorker($email_template, $template_data, $message_data, $label);
	}

	public function rescheduledBookTrial ($data){

		// $email_template = 'emails.test';
		$email_template = 'emails.finder.rescheduledautobooktrial';
		$template_data 	= $data;
		if($data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_autobook_trial'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_autobook_trial');
		} 

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' =>  $data['finder_poc_for_customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Reschedule request from customer '.ucwords($data['customer_name']).' for a session | Fitternity'
			);

		$label = 'RescheduledTrial-F';

		return $this->sendToWorker($email_template, $template_data, $message_data, $label);
	}

	//currently not using reminder
	public function bookTrialReminderBefore12Hour ($data, $delay){

		$email_template = 'emails.finder.autobooktrial_reminder';
		$template_data 	= $data;
		$emails 		= 	Config::get('mail.bcc_emailds_autobook_trial');
		$bcc_emailids 	= 	array_flatten($emails);
		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Reminder Mail: Request For Book a Trial'
			);
		
		$label = 'TrialRmdBefore12Hr-F';
		$priority = 0;

		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority, $delay);
	}

	public function sendBookTrialDaliySummary ($data){

		$email_template = 'emails.finder.booktrialdailysummary';
		$template_data 	= $data;

		if($data['finder_vcc_email'] != ''){

			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_finderdailsummary'));
		}else{

			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_finderdailsummary');
		} 


		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' =>  $data['finder_poc_for_customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Daily report on customers who have booked sessions for tomorrow with '.ucwords($data['finder_name_base_locationtags'])
			);
		// echo "<pre>";print_r($data);exit;

		$label = 'TrialDaliySummary-F';

		return $this->sendToWorker($email_template, $template_data, $message_data, $label);

	}


	public function buyServiceThroughFitmania ($data){

		$email_template 	= 	'emails.order.fitmania_offer_vendor_v1';
		$template_data 				= 	$data;

		if(isset($data['finder_vcc_email'] ) && $data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 
		
		// $bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');

		$subject  					=   'FitMania Sale by Fitternity - Membership purchase request for '.ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => ucwords($data['finder_name']),
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuyServiceFitmania-F';

		return $this->sendToWorker($email_template, $template_data, $message_data, $label);

	}

	public function buyServiceMembershipThroughFitmania ($data){

		$email_template 	= 	'emails.order.fitmania_membership_vendor_v1';
		$template_data 				= 	$data;

		if(isset($data['finder_vcc_email'] ) && $data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 
		
		// $bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');

		$subject  					=   'FitMania Sale by Fitternity - Membership purchase request for '.ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => ucwords($data['finder_name']),
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuySrvMbrFitM-F';

		return $this->sendToWorker($email_template, $template_data, $message_data, $label);

	}

	public function buyServiceHealthyTiffinThroughFitmania ($data){

		$email_template 	= 	'emails.order.fitmania_healthytiffin_vendor_v1';
		$template_data 				= 	$data;

		if(isset($data['finder_vcc_email'] ) && $data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 
		
		// $bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');

		$subject  					=   'FitMania Sale by Fitternity - Order confirmation for '.ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => ucwords($data['finder_name']),
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuySrvHltTifFitM-F';

		return $this->sendToWorker($email_template, $template_data, $message_data, $label);

	}


	public function resendFitmaniaFinderEmail ($data){

		$email_template 	= 	'emails.finder.fitmaniafinder_consolidated';
		$template_data 				= 	$data;
		// $bcc_emailids 				= 	Config::get('mail.bcc_emailds_fitmaniasale');
		// $bcc_emailids 				= 	[$data['finder_vcc_email']];
		$bcc_emailids 				=  	array_merge(explode(',', $data['finder_vcc_email']));
		$subject  					=   'Confirmation of orders received through FitMania Sale on Fitternity.com (till August 3, 2015)';

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => 'Fitternity',
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'ResendFitmania-F';

		return $this->sendToWorker($email_template, $template_data, $message_data, $label);

	}

	public function cancelBookTrial ($data){
		
		$email_template = 'emails.finder.cancelbooktrial';
		$template_data 	= $data;
		if($data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_autobook_trial'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_autobook_trial');
		} 

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' =>  $data['finder_poc_for_customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Cancellation of session booked by customer '.ucwords($data['customer_name']).' | Fitternity'
			);

		$label = 'CancelTrial-F';

		return $this->sendToWorker($email_template, $template_data, $message_data, $label);
	}

}