<?PHP namespace App\Mailers;

use Mail, Queue;

abstract Class Mailer {

	public function sendTo($email_template, $template_data = [], $message_data = [], $delay = null ){

		if($delay == null){

			$messageid = Queue::push(function($job) use ($email_template, $template_data, $message_data){ 

				$job_id = $job->getJobId(); 

				Mail::send($email_template, $template_data, function($message) use ($message_data){
					$message->to($message_data['user_email'], $message_data['user_name'])
					->bcc($message_data['bcc_emailids'])
					->subject($message_data['email_subject']);
				});

				$job->delete();  
			}, '','pullapp');

			return $messageid;
			
		}else{

			$messageid = Queue::push(function($job) use ($email_template, $template_data, $message_data, $delay){ 

				$job_id = $job->getJobId(); 

				Mail::later($delay, $email_template, $template_data, function($message) use ($message_data){
					$message->to($message_data['user_email'], $message_data['user_name'])
					->bcc($message_data['bcc_emailids'])
					->subject($message_data['email_subject']);
				});

				$job->delete();

			}, '','pullapp');

			return $messageid;
			
		}

	}


	/*


	public function sendTo($email_template, $template_data = [], $message_data = [], $delay = null ){

		if($delay == null){

			$messageid = Mail::queue($email_template, $template_data, function($message) use ($message_data){

							$message->to($message_data['user_email'], $message_data['user_name'])
							->bcc($message_data['bcc_emailids'])
							->subject($message_data['email_subject']);

						});

			return $messageid;
			
		}else{

			$messageid = Mail::later($delay, $email_template, $template_data, function($message) use ($message_data){

				$message->to($message_data['user_email'], $message_data['user_name'])
				->bcc($message_data['bcc_emailids'])
				->subject($message_data['email_subject']);

			});

			return $messageid;
			
		}

	}

	*/

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