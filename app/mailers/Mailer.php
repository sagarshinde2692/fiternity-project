<?PHP namespace Acme\Mailers;

use Mail;

abstract Class Mailer {

	public function sendTo($email_template, $template_data = [], $message_data = [], $delay = null ){

		if($delay == null){

			Mail::queue($email_template, $template_data, function($message) use ($message_data){

				$message->to($message_data['user_email'], $message_data['user_name'])
				->bcc($message_data['bcc_emailids'])
				->subject($message_data['email_subject']);

			});
			
		}else{

			Mail::later($delay, $email_template, $template_data, function($message) use ($message_data){

				$message->to($message_data['user_email'], $message_data['user_name'])
				->bcc($message_data['bcc_emailids'])
				->subject($message_data['email_subject']);

			});
			
		}

	}

	public function sendToWithDelay($delay, $email_template, $template_data = [], $message_data = [] ){

		//$delayInSeconds =  $this->getSeconds($delay);
		Mail::later($delay, $email_template, $template_data, function($message) use ($message_data){

			$message->to($message_data['user_email'], $message_data['user_name'])
			->bcc($message_data['bcc_emailids'])
			->subject($message_data['email_subject']);

		});

	}

	/**
	 * Calculate the number of seconds with the given delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @return int
	 */
	protected function getSeconds($delay){
		
		if ($delay instanceof DateTime){
			return max(0, $delay->getTimestamp() - $this->getTime());
		}
		return (int) $delay;
	}

	/**
	 * Get the current UNIX timestamp.
	 *
	 * @return int
	 */
	public function getTime(){
		return time();
	}

	

}