<?PHP namespace Acme\Mailers;
use Mail;

Class CustomerMailer extends Mailer {

	public function welcome(){
		//$email_template, $template_data = [], $message_data = [] ;

	}

	public function bookTrial ($data){

		$email_template = 'emails.testemail';
		$template_data 	= $data;
		$message_data 	= array(
			'string' => 'Hello World from array with time -- '.time(),
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => array('sanjay.fitternity@gmail.com'),
			'email_subject' => 'send email instant'
			);
		return $this->sendTo($email_template, $template_data, $message_data);
	}

	public function bookTrialReminder ($data, $delay){

		$email_template = 'emails.testemail';
		$template_data 	= $data;
		$message_data 	= array(
			'string' => 'Hello World from array with time -- '.time(),
			'user_email' => $data['customer_email'],
			'user_name' => $data['customer_name'],
			'bcc_emailids' => array('sanjay.fitternity@gmail.com'),
			'email_subject' => 'send email delay by 1 min'
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