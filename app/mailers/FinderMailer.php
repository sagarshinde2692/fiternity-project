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
			'email_subject' => 'Session request from customer '.ucwords($data['customer_name']).' has been confirmed | Fitternity'
			);

		return $this->sendTo($email_template, $template_data, $message_data);
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
		return $this->sendTo($email_template, $template_data, $message_data, $delay);
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

		return $this->sendTo($email_template, $template_data, $message_data);

	}


	public function buyServiceThroughFitmania ($data){

		$email_template_customer 	= 	'emails.order.fitmania_offer_vendor';
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

		return $this->sendTo($email_template_customer, $template_data, $message_data);

	}

	public function buyServiceMembershipThroughFitmania ($data){

		$email_template_customer 	= 	'emails.order.fitmania_membership_vendor';
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

		return $this->sendTo($email_template_customer, $template_data, $message_data);

	}


	public function cancelBookTrial(){

	}






}