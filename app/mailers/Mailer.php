<?PHP namespace App\Mailers;

use Mail, Queue, IronWorker, Config, View, Log;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Utilities as Utilities;


abstract Class Mailer {

	public function __call($method, $arguments){

        $qualified_class_name = get_class($this);
        
        $reflect = new \ReflectionClass($this);
        $class_name = $reflect->getShortName();

        $reflect = new \ReflectionClass($qualified_class_name);
        $objInstance = $reflect->newInstanceArgs();
		if(count($arguments) < 2 || (is_int($arguments[1]) && $arguments[1] == 0) || !in_array($method, Config::get('app.delay_methods'))){
			return call_user_func_array( array($objInstance, $method), $arguments);
		}else{
			$utilities = new Utilities();
			return $utilities->scheduleCommunication($arguments, $method, $class_name);
		}
    }

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



	/**
	 * Calculate the number of seconds with the given delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @return int
	 */
	protected function getSeconds($delay){

        if ($delay instanceof DateTime){

            return max(0, $delay->getTimestamp() - $this->getTime());

        }elseif ($delay instanceof \Carbon\Carbon){

            return max(0, $delay->timestamp - $this->getTime());

        }elseif(isset($delay['date'])){

            $time = strtotime($delay['date']) - $this->getTime();

            return $time;

        }else{

            $delay = strtotime($delay) - time();   
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


	/*public function sendToWorkerBk($email_template, $template_data = [], $message_data = [], $label = 'label', $priority = 0, $delay = 0){

		if($delay !== 0){
			$delay = strtotime($delay);
		}

        $scheduler  = new \Schedulerjob();
		$scheduler->_id = \Schedulerjob::max('_id') + 1;        
		$scheduler->email_template = $email_template;
        $scheduler->template_data = $template_data;
        $scheduler->message_data = $message_data;
        $scheduler->delay = $delay;
        $scheduler->priority = $priority;
        $scheduler->label = $label;
        $scheduler->type = 'email';
        $scheduler->status = 'scheduled';

        $scheduler->save();

        return $scheduler->_id;
	}

	public function sendToWorkerBk($email_template, $template_data = [], $message_data = [], $label = 'label', $priority = 0, $delay = 0){

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

	}*/

	public function sendToWorker($to = '',$email_template, $template_data = [], $message_data = [], $label = 'label', $priority = 0, $delay = 0){

		$sidekiq = new Sidekiq();

		if(is_array($delay))
		{
			\Log::info('email - '.$label.' -- '. $delay['date']);
		}else{
			\Log::info('email - '.$label.' -- '. $delay);
		}

		if($delay !== 0){
			$delay = $this->getSeconds($delay);
		}
	
		$email_html = View::make($email_template, $template_data)->render();

		$payload = array('to'=>$to,'email_template'=>$email_template,'template_data'=>$template_data,'email_html'=>$email_html,'user_data'=>$message_data,'delay'=>$delay,'priority'=>$priority,'label' => $label);

		$route	= 'email';
		$result  = $sidekiq->sendToQueue($payload,$route);

		if($result['status'] == 200){
			return $result['task_id'];
		}else{
			return $result['status'].':'.$result['reason'];
		}

	}

	public function sendDbToWorker($to = '',$email_template, $message_data = [], $label = 'label', $delay = 0){

		$sidekiq = new Sidekiq();

		if(is_array($delay))
		{
			\Log::info('email - '.$label.' -- '. $delay['date']);
		}else{
			\Log::info('email - '.$label.' -- '. $delay);
		}

		if($delay !== 0){
			$delay = $this->getSeconds($delay);
		}

		$message_data['user_name'] = preg_replace('/[^A-Za-z0-9 \-\']/', '', $message_data['user_name']);
		
		$payload = array('to'=>$to,'email_html'=>$email_template,'user_data'=>$message_data,'delay'=>$delay,'label' => $label);

		$route	= 'email';
		$result  = $sidekiq->sendToQueue($payload,$route);

		if($result['status'] == 200){
			return $result['task_id'];
		}else{
			return $result['status'].':'.$result['reason'];
		}

	}



	public function  sendEmail($email_template, $template_data = [], $message_data = []){

			return Mail::send($email_template, $template_data, function($message) use ($message_data){
				$message->to($message_data['user_email'], $message_data['user_name'])
				->bcc(array_merge( ['sanjay.id7@gmail.com'], $message_data['bcc_emailids']))
				->subject($message_data['email_subject']);
			});
	}


	/*public function sendToWorkerTest($email_template, $template_data = [], $message_data = [], $label = 'label', $priority = 0, $delay = 0){

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

	}*/

}