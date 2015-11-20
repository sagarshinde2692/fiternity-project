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

		$label = 'BookTrial-C';
		$priority = 1;

		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority);
	}

	public function rescheduledBookTrial ($data){

		// $email_template = 'emails.test';
		$email_template = 	'emails.customer.rescheduledautobooktrial';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_emailds_autobook_trial');

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Your session at '.ucwords($data['finder_name']).' has been re-scheduled | Fitternity'
			);

		$label = 'RescheduledTrial-C';
		$priority = 1;

		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority);
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

		$label = 'TrialRmdBefore1Min-C';
		
		$this->sendToWorker($email_template, $template_data, $message_data, $label);

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

		$label = 'TrialRmdAfter2Hr-C';
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label);
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

		$label = 'TrialRmdBefore12Hr-C';
		$priority = 0;
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority, $delay);
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

		$label = 'TrialRmdAfter2Hr-C';
		$priority = 0;
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority, $delay);
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

		$label = 'ManualBookTrial-C';
		$priority = 1;
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority);
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

		$label = 'Manual2ndBookTrial-C';
		$priority = 1;
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority);
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


		$label = 'FitcardCod-C';
		$priority = 1;
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label, $priority);
		
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'FitcardCod-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
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

		$label = 'FitcardPaymentGate-C';
		$priority = 1;
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label, $priority);

		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'FitcardPaymentGate-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
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

		if($data['type'] == 'arsenalmembership'){
			$subject  = 'Fitternity - Acknowledgement of request to purchase Arsenal Mumbai Membership';
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


		$label = 'SendCodOrder-C';
		$priority = 1;
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label, $priority);
		
		// array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_name', 'Fitternity');
		
		$label = 'SendCodOrder-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
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

		$label = 'SendPgOrder-C';
		$priority = 1;
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label, $priority);

		// array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'SendPgOrder-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
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

		$label = 'BuySrvFitmania-C';
		$priority = 1;
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label, $priority);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'BuySrvFitmania-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
	}

	public function buyServiceThroughFitmaniaResend1 ($data){

		$email_template_customer 	= 	'emails.order.fitmanianew_offer_template';
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

		$label = 'BuySrvFitMResend1-C';
		
		return $this->sendToWorker($email_template_customer, $template_data, $message_data, $label);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		// array_set($message_data, 'user_email', 'mailus@fitternity.com');
		// array_set($message_data, 'user_name', 'Fitternity');

		/*$label = 'BuyArsenalMembership-Us';
		$priority = 0;
		$delay = 0;
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label, $priority, $delay);*/
	}

	public function buyServiceMembershipThroughFitmania ($data){

		$email_template_customer 	= 	'emails.order.fitmania_membership_template_v1';
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

		$label = 'BuySrvMbrFitM-C';
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'BuySrvMbrFitM-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
	}

	public function buyServiceHealthyTiffinThroughFitmania ($data){

		$email_template_customer 	= 	'emails.order.fitmania_healthytiffin_v1';
		$email_template_mailus 		= 	'emails.order.fitmania_healthytiffin_mailus';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_mailus');
		$subject  					=   'Regarding your purchase on FitMania Sale by Fitternity';

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuySrvHltTifFitM-C';
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'BuySrvHltTifFitM-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
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

		$label = 'ForgotPwd-C';
		$priority = 1;
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority);
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

		$label = 'ForgotPwdApp-C';
		$priority = 1;
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label, $priority);
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

		$label = 'Register-C';
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label);
	}


	public function buyArsenalMembership ($data){

		$email_template_customer 	= 	'emails.order.pg_arsenalmembership';
		$email_template_mailus 		= 	'emails.order.pg_arsenalmembership_mailus';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_mailus');
		$subject  					=   'Regarding your purchase on Arsernal Membership by Fitternity';

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuyArsenalMbrShip-C';
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'BuyArsenalMbrShip-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
	}


	public function buyLandingpagePurchase ($data){

		$email_template_customer 	= 	'emails.order.pg_landingpage';
		$email_template_mailus 		= 	'emails.order.pg_landingpage_mailus';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_mailus');
		$subject  					=   'Regarding your purchase on  Membership by Fitternity';

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'buyLandingpagePurchase-C';
		
		$this->sendToWorker($email_template_customer, $template_data, $message_data, $label);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'buyLandingpagePurchase-Us';
		
		return $this->sendToWorker($email_template_mailus, $template_data, $message_data, $label);
	}


	public function resendFitmaniaCustomerEmail ($data){

		$email_template_customer 	= 	'emails.order.fitmania_customer_resend';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_mailus');
		$subject  					=   'Regarding your purchase on FitMania Sale by Fitternity';

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'ResendFitmania-C';
		
		return  $this->sendToWorker($email_template_customer, $template_data, $message_data, $label);

	}

	public function cancelBookTrial($data){
		
		$email_template = 	'emails.customer.cancelbooktrial';
		$template_data 	= 	$data;
		$bcc_emailids 	= 	Config::get('mail.bcc_emailds_autobook_trial');

		$message_data 	= array(
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Your session at '.ucwords($data['finder_name']).' has been cancelled | Fitternity'
			);

		$label = 'CancelTrial-C';
		
		return $this->sendToWorker($email_template, $template_data, $message_data, $label);
	}

}