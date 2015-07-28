<?PHP namespace App\Mailers;

use Config,Mail;

Class CustomerMailer extends Mailer {


	public function bookTrial ($data){

		// $email_template = 'emails.test';
		$email_template = 	'emails.customer.autobooktrial';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_emailds_autobook_trial');

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Your session at '.ucwords($data['finder_name']).' has been confirmed | Fitternity'
			);

		return $this->sendTo($email_template, $template_data, $message_data);
	}


	//used for testing purpose
	public function bookTrialReminderBefore1Min ($data, $delay){

		$email_template = 	'emails.customer.booktrialreminderbefore12hour';
		$template_data 	= 	$data;
		$emails 		= 	Config::get('mail.bcc_emailds_autobook_trial');
		$bcc_emailids 	= 	array_flatten($emails);
		$message_data 	= 	array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'bookTrialReminderBefore12Hour Reminder Book a Trial'
			);
		// return $this->sendTo($email_template, $template_data, $message_data, $delay);
		$this->sendTo($email_template, $template_data, $message_data);

		$email_template = 	'emails.customer.booktrialreminderafter2hour';
		$template_data 	= 	$data;
		$emails 		= 	Config::get('mail.bcc_emailds_autobook_trial');
		$bcc_emailids 	= 	array_flatten($emails);
		$message_data 	= 	array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'bookTrialReminderAfter2Hour Reminder Book a Trial'
			);
		return $this->sendTo($email_template, $template_data, $message_data);
	}


	public function bookTrialReminderBefore12Hour ($data, $delay){

		$email_template = 	'emails.customer.booktrialreminderbefore12hour';
		$template_data 	= 	$data;
		$emails 		= 	Config::get('mail.bcc_emailds_autobook_trial');
		$bcc_emailids 	= 	array_flatten($emails);
		$message_data 	= 	array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Regarding your session at '.ucwords($data['finder_name']).' | Fitternity'
			);
		return $this->sendTo($email_template, $template_data, $message_data, $delay);
	}


	public function bookTrialReminderAfter2Hour ($data, $delay){

		$email_template = 'emails.customer.booktrialreminderafter2hour';
		$template_data 	= $data;
		$emails 		= 	Config::get('mail.bcc_emailds_autobook_trial');
		$bcc_emailids 	= 	array_flatten($emails);
		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Feedback and subscription at '.ucwords($data['finder_name']).' | Fitternity'
			);
		return $this->sendTo($email_template, $template_data, $message_data, $delay);
	}

	public function manualBookTrial ($data){

		// $email_template = 'emails.test';
		$email_template = 	'emails.customer.manualbooktrial';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_emailds_autobook_trial');

		$message_data 	= array(
			'user_email' => Config::get('mail.to_neha'),
			// 'user_email' => 'ut.mehrotra@gmail.com',
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Request For Manual Book a Trial'
			);

		return $this->sendTo($email_template, $template_data, $message_data);
	}

	public function manual2ndBookTrial ($data){

		// $email_template = 'emails.test';
		$email_template = 	'emails.customer.manual2ndbooktrial';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_emailds_autobook_trial');

		$message_data 	= array(
			'user_email' => Config::get('mail.to_neha'),
			// 'user_email' => 'ut.mehrotra@gmail.com',
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Request For Manual Second Book a Trial'
			);

		return $this->sendTo($email_template, $template_data, $message_data);
	}


	public function cancelBookTrial(){

	}


	public function fitcardCodWelcomeMail ($data){

		// $email_template = 'emails.test';
		$email_template_customer = 	'emails.customer.fitcardcodwelcomemail';
		$email_template_mailus = 	'emails.customer.fitcardcodwelcomemail_mailus';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_emailds_sanjay');
		
		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Acknowledgement email - Regarding your purchase request for FitCard'
			);


		$this->sendTo($email_template_customer, $template_data, $message_data);
		
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');
		
		return $this->sendTo($email_template_mailus, $template_data, $message_data);
	}


	public function fitcardPaymentGateWelcomeMail ($data){

		// return $data; exit;
		$email_template_customer = 	'emails.customer.fitcardpaymentgatewaywelcomemail';
		$email_template_mailus = 	'emails.customer.fitcardpaymentgatewaywelcomemail_mailus';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_emailds_sanjay');

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Welcome mail - Welcome to the FitCard clan!'
			);

		$this->sendTo($email_template_customer, $template_data, $message_data);

		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		return $this->sendTo($email_template_mailus, $template_data, $message_data);
	}


	public function sendCodOrderMail ($data){

		$email_template_customer 	= 	'emails.order.cod_'.strtolower($data['type']);
		$email_template_mailus 		= 	'emails.order.cod_'.strtolower($data['type']).'_mailus';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_sanjay');
		$subject 					= 	'';

		if($data['type'] == 'memberships'){
			$subject  = 'Fitternity - Acknowledgement of request to purchase '. ucwords($data['service_name'])." ". ucwords($data['service_duration']). " at ". ucwords($data['finder_name']);
		}

		if($data['type'] == 'fitmaniaservice'){
			$subject  = 'FitMania Sale - Acknowledgement of request to purchase '. ucwords($data['service_name'])." ". ucwords($data['service_duration']). " at ". ucwords($data['finder_name']);
		}

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);


		$this->sendTo($email_template_customer, $template_data, $message_data);
		
		// array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_name', 'Fitternity');
		
		return $this->sendTo($email_template_mailus, $template_data, $message_data);
	}


	public function sendPgOrderMail ($data){

		$email_template_customer 	= 	'emails.order.pg_'.strtolower($data['type']);
		$email_template_mailus 		= 	'emails.order.pg_'.strtolower($data['type']).'_mailus';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_sanjay');
		$subject 					= 	'';
		
		if($data['type'] == 'memberships'){
			$subject  = 'Fitternity - Confirmation of purchase '. ucwords($data['service_name'])." ". ucwords($data['service_duration']). " at ". ucwords($data['finder_name']);
		}

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$this->sendTo($email_template_customer, $template_data, $message_data);

		// array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_name', 'Fitternity');

		return $this->sendTo($email_template_mailus, $template_data, $message_data);
	}


	public function buyServiceThroughFitmania ($data){

		$email_template_customer 	= 	'emails.order.fitmanianew_offer_template_v1';
		$email_template_mailus 		= 	'emails.order.fitmania_offer_mailus';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_mailus');
		$subject  					=   'Regarding your purchase on FitMania Sale by Fitternity';

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$this->sendTo($email_template_customer, $template_data, $message_data);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		return $this->sendTo($email_template_mailus, $template_data, $message_data);
	}

	public function buyServiceMembershipThroughFitmania ($data){

		$email_template_customer 	= 	'emails.order.fitmania_membership_template';
		$email_template_mailus 		= 	'emails.order.fitmania_membership_mailus';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_mailus');
		$subject  					=   'Regarding your purchase on FitMania Sale by Fitternity';

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$this->sendTo($email_template_customer, $template_data, $message_data);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		return $this->sendTo($email_template_mailus, $template_data, $message_data);
	}


	public function forgotPassword ($data){

		$email_template = 	'emails.customer.forgot_password';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_forgot_password');

		$message_data 	= array(
			'user_email' => $data['email'],
			'user_name' => $data['name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Your Password Reset Request for Fitternity'
		);

		return $this->sendTo($email_template, $template_data, $message_data);
	}

	public function forgotPasswordApp ($data){

		$email_template = 	'emails.customer.forgot_password_app';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_forgot_password_app');

		$message_data 	= array(
			'user_email' => $data['email'],
			'user_name' => $data['name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Your Password Reset Request for Fitternity'
		);

		return $this->sendTo($email_template, $template_data, $message_data);
	}

	public function register($data){

		$email_template = 	'emails.customer.register';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_register');

		$message_data 	= array(
			'user_email' => $data['email'],
			'user_name' => $data['name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Welcome to Fitternity'
		);

		return $this->sendTo($email_template, $template_data, $message_data);
	}

}