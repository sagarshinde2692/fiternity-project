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
			'user_name' => 'Fitternity',
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Request For Book a Trial'
			);

		return $this->sendTo($email_template, $template_data, $message_data);
	}

	public function bookTrialReminder ($data, $delay){

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




	public function cancelBookTrial(){

	}

	public function updateBookTrial(){

	}


	public function requestForCallback(){

	}

	public function buyMembership($email_template, $template_data = [], $message_data = [] ){

	}






}