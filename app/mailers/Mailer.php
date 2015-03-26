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
			}, array(),'pullapp');

			return $messageid;
			
		}else{

			$seconds  	= 	$this->getSeconds($delay);
			$messageid 	= 	Queue::push(function($job) use ($email_template, $template_data, $message_data, $seconds){ 

				$job_id 	= 	$job->getJobId(); 

				Mail::later($seconds, $email_template, $template_data, function($message) use ($message_data){
					$message->to($message_data['user_email'], $message_data['user_name'])
					->bcc($message_data['bcc_emailids'])
					->subject($message_data['email_subject']);
				});

				$job->delete();

			}, array(),'pullapp');

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

		if ($delay instanceof \Carbon\Carbon){
			return max(0, $delay->timestamp - $this->getTime());
		}
		// echo (int) $delay; exit;
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