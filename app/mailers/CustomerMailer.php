<?PHP namespace App\Mailers;

use Config,Mail;

Class CustomerMailer extends Mailer {

	public function welcome(){
		//$email_template, $template_data = [], $message_data = [] ;

	}

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
		
		return $this->sendTo($email_template_mailus, $template_data, $message_data);
	}


	public function fitcardPaymentGateWelcomeMail ($data){

		// return $data; exit;
		$email_template = 	'emails.customer.fitcardpaymentgatewaywelcomemail';
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

		return $this->sendTo($email_template_mailus, $template_data, $message_data);
	}






}