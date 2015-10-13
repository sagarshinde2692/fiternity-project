<?PHP namespace App\Mailers;

use Mail, Queue, IronWorker, Config;

abstract Class Mailer {

	public function sendTo($email_template, $template_data = [], $message_data = [], $delay = null ){

		if($delay == null){

			$messageid = Queue::push(function($job) use ($email_template, $template_data, $message_data){ 

				$job_id = $job->getJobId(); 

				try {

					Mail::send($email_template, $template_data, function($message) use ($message_data){
						$message->to($message_data['user_email'], $message_data['user_name'])
						->cc($message_data['bcc_emailids'])
						->bcc(array('sanjay.id7@gmail.com'))
						->subject($message_data['email_subject']);
					});

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

					Mail::send($email_template, $template_data, function($message) use ($message_data){
						$message->to($message_data['user_email'], $message_data['user_name'])
						->cc($message_data['bcc_emailids'])
						->bcc(array('sanjay.id7@gmail.com'))
						->subject($message_data['email_subject']);
					});

				}catch(Swift_RfcComplianceException $exception){
					
					Log::error($exception);
				}

				$job->delete();

			}, array(),'pullapp');

			return $messageid;
			
		}

	}

	public function  sendEmail($email_template, $template_data = [], $message_data = []){

			return Mail::send($email_template, $template_data, function($message) use ($message_data){
				$message->to($message_data['user_email'], $message_data['user_name'])
				->bcc(array_merge( ['sanjay.id7@gmail.com'], $message_data['bcc_emailids']))
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


	public function sendToWorkerbk($email_template, $template_data = [], $message_data = [], $label = 'label', $priority = 0, $delay = 0){

		$worker = new IronWorker(array(
		    'token' => Config::get('queue.connections.ironworker.token'),
    		'project_id' => Config::get('queue.connections.ironworker.project')
		));
		
		if($delay !== 0){
			$delay = $this->getSeconds($delay);
		}
	
		$payload = array('email_template'=>$email_template,'template_data'=>$template_data,'message_data'=>$message_data);
		$options = array('delay'=>$delay,'priority'=>$priority,'label' => $label, 'cluster' => 'dedicated');
		$queue_name = 'MailerApi';

		$messageid = $worker->postTask($queue_name,$payload,$options);

		return $messageid;

	}


	public function sendToWorker($email_template, $template_data = [], $message_data = [], $label = 'label', $priority = 0, $delay = 0){

		// fsa

	}



	public function sendToWorkerTest($email_template, $etmplate_data = [], $message_data = [], $label = 'label', $priority = 0, $delay = 0){

		$worker = new IronWorker(array(
		    'token' => Config::get('queue.connections.ironworker.token'),
    		'project_id' => Config::get('queue.connections.ironworker.project')
		));
		
		if($delay !== 0){
			$delay = $this->getSeconds($delay);
		}
	
		$payload = array('email_template'=>$email_template,'template_data'=>$template_data,'message_data'=>$message_data);
		$options = array('delay'=>$delay,'priority'=>$priority,'label' => $label, 'cluster' => 'dedicated');
		$queue_name = 'TestMailerApi';

		$messageid = $worker->postTask($queue_name,$payload,$options);

		return $messageid;

	}

}