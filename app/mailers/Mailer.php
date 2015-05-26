<?PHP namespace App\Mailers;

use Mail, Queue;

abstract Class Mailer {

	public function sendTo($email_template, $template_data = [], $message_data = [], $delay = null ){

		if($delay == null){

			$messageid = Queue::push(function($job) use ($email_template, $template_data, $message_data){ 

				$job_id = $job->getJobId(); 
				try {

					if(!empty($message_data['bcc_emailids'])){
						Mail::send($email_template, $template_data, function($message) use ($message_data){
							$message->to($message_data['user_email'], $message_data['user_name'])
							->bcc($message_data['bcc_emailids'])
							->subject($message_data['email_subject']);
						});
					}else{
						Mail::send($email_template, $template_data, function($message) use ($message_data){
							$message->to($message_data['user_email'], $message_data['user_name'])
							->subject($message_data['email_subject']);
						});
					}
				}

			}catch(Swift_RfcComplianceException $exception){

				Log::error($exception);
			}

			$job->delete();  
		}, array(),'pullapp');

			return $messageid;
			
		}else{

			$seconds  	= 	$this->getSeconds($delay);
			$messageid 	= 	Queue::later($seconds, function($job) use ($email_template, $template_data, $message_data, $seconds){ 

				$job_id =	$job->getJobId(); 

				try {
					
					if(!empty($message_data['bcc_emailids'])){
						Mail::send($email_template, $template_data, function($message) use ($message_data){
							$message->to($message_data['user_email'], $message_data['user_name'])
							->bcc($message_data['bcc_emailids'])
							->subject($message_data['email_subject']);
						});
					}else{
						Mail::send($email_template, $template_data, function($message) use ($message_data){
							$message->to($message_data['user_email'], $message_data['user_name'])
							->subject($message_data['email_subject']);
						});
					}


				}catch(Swift_RfcComplianceException $exception){
					
					Log::error($exception);
				}

				$job->delete();

			}, array(),'pullapp');

			return $messageid;
			
		}

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