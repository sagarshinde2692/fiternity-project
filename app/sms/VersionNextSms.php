<?PHP namespace App\Sms;

use Queue, IronWorker, Config;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Utilities as Utilities;


abstract Class VersionNextSms {

    public function __call($method, $arguments){

        $qualified_class_name = get_class($this);
        
        $reflect = new \ReflectionClass($this);
        $class_name = $reflect->getShortName();

        $reflect = new \ReflectionClass($qualified_class_name);
        $objInstance = $reflect->newInstanceArgs();
        $comm_type = 'sms';
        if($class_name == 'CustomerSms'){
            $class_name_comm = 'customer';
		}
		else{
			$class_name_comm = 'finder';
		}
		\Log::info('inside customer sms checking for after 2 hour for normal bookingssss:::::::::::::::::::::',[]);
		if(!(!isset($arguments[0]['communications']) || !isset($arguments[0]['communications'][$class_name_comm]) || (!isset($arguments[0]['communications'][$class_name_comm][$comm_type])) || (in_array($method, $arguments[0]['communications'][$class_name_comm][$comm_type])))){
            return null;
        }
        
		if(count($arguments) < 2 || (is_int($arguments[1]) && $arguments[1] == 0) || !in_array($method, Config::get('app.delay_methods'))){
			// return $objInstance->$method($arguments[0], 0);
            \Log::info("Inside VersionNextSms");
            return call_user_func_array( array($objInstance, $method), $arguments);

		}else{
			$utilities = new Utilities();
			return $utilities->scheduleCommunication($arguments, $method, $class_name);
		}
    }


	public function sendTo($to, $message, $delay = null ){

        // return $to;exit;

		if($delay == null){

            $messageid = Queue::push(function($job) use ($to, $message){ 

                $job_id = $job->getJobId(); 

                $msg = strip_tags($message);

                foreach ($to as $number) {
                
                    $url = 'http://www.kookoo.in/outbound/outbound_sms_ftrnty.php';

                    $param = array(
                        'api_key' => 'KK33e21df516ab75130faef25c151130c1', 
                        'phone_no' => trim($number), 
                        'message' => $message,
                        'senderid'=> 'FTRNTY' 
                    );
                                              
                    $url = $url . "?" . http_build_query($param, '&');

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                    $result = curl_exec($ch);
                    curl_close($ch);
                    
                    echo $result;

                    /*$sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);
                    $ci = curl_init();
                    curl_setopt($ci, CURLOPT_URL, $sms_url);
                    curl_setopt($ci, CURLOPT_HEADER, 0);
                    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
                    $response = curl_exec($ci);
                    curl_close($ci);*/
                }

                $job->delete();  
            }, array(), 'pullapp');

            return $messageid;

        }else{

            $seconds    =   $this->getSeconds($delay);
            $messageid  =   Queue::later($seconds, function($job) use ($to, $message){ 

                $job_id = $job->getJobId();
                
                $msg = strip_tags($message); 

                foreach ($to as $number) {

                    $url = 'http://www.kookoo.in/outbound/outbound_sms_ftrnty.php';

                    $param = array(
                        'api_key' => 'KK33e21df516ab75130faef25c151130c1', 
                        'phone_no' => trim($number), 
                        'message' => $message,
                        'senderid'=> 'FTRNTY' 
                    );
                                              
                    $url = $url . "?" . http_build_query($param, '&');

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                    $result = curl_exec($ch);
                    curl_close($ch);
                    
                    echo $result;

                   /* $sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);
                    $ci = curl_init();
                    curl_setopt($ci, CURLOPT_URL, $sms_url);
                    curl_setopt($ci, CURLOPT_HEADER, 0);
                    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
                    $response = curl_exec($ci);
                    curl_close($ci);*/

                }

                $job->delete();  

            }, array(), 'pullapp');
            return $messageid;
        }

    }




    public function sendSms($to, $message){

       $msg = strip_tags($message); 

       foreach ($to as $number) {

            $url = 'http://www.kookoo.in/outbound/outbound_sms_ftrnty.php';

            $param = array(
                'api_key' => 'KK33e21df516ab75130faef25c151130c1', 
                'phone_no' => trim($number), 
                'message' => $message,
                'senderid'=> 'FTRNTY' 
            );
                                      
            $url = $url . "?" . http_build_query($param, '&');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);
            curl_close($ch);
            
            echo $result;

            /*$sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);
            $ci = curl_init();
            curl_setopt($ci, CURLOPT_URL, $sms_url);
            curl_setopt($ci, CURLOPT_HEADER, 0);
            curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ci);
            curl_close($ci);*/

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

/* public function sendToWorkerBk($to, $message, $label = 'label', $priority = 0, $delay = 0){

        if($delay !== 0){
            $delay = strtotime($delay);
        }

        $scheduler  = new \Schedulerjob();
        $scheduler->_id = \Schedulerjob::max('_id') + 1;       
        $scheduler->to = $to;
        $scheduler->message = $message;
        $scheduler->delay = $delay;
        $scheduler->priority = $priority;
        $scheduler->label = $label;
        $scheduler->type = 'sms';
        $scheduler->status = 'scheduled';

        $scheduler->save();

        return $scheduler->_id;

    }

    public function sendToWorkerBk($to, $message, $label = 'label', $priority = 0, $delay = 0){

        $worker = new IronWorker(array(
            'token' => Config::get('queue.connections.ironworker.token'),
            'project_id' => Config::get('queue.connections.ironworker.project')
        ));
        
        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }
    
        $payload = array('to'=>$to,'message'=>$message);
        $options = array('delay'=>$delay,'priority'=>$priority,'label' => $label, 'cluster' => 'dedicated');
        $queue_name = 'SmsApi';

        $messageid = $worker->postTask($queue_name,$payload,$options);

        return $messageid;

    }*/

    public function sendToWorker($to, $message, $label = 'label', $delay = 0, $otp = false, $sender = null){

        $arrayLabel = [
            'PurchaseAfter10Days-Customer',
            'PurchaseAfter30Days-Customer',
            'SendPaymentLinkAfter3Days-Customer',
            'SendPaymentLinkAfter7Days-Customer',
            'SendPaymentLinkAfter45Days-Customer',
            'PurchaseAfter7Days-Customer',
            'PurchaseAfter15Days-Customer'
        ];
        
        $sidekiq = new Sidekiq();

        if(is_array($delay))
        {
            \Log::info('sms - '.$label.' -- '. $delay['date']);
        }else{
            \Log::info('sms - '.$label.' -- '. $delay);
        }

        if($delay !== 0){

            if(in_array($label,$arrayLabel)){

                $delay = $this->getDelayTime($delay);   
            }

            $delay = $this->getSeconds($delay);
        }

        $payload = array('to'=>$to,'message'=>$message,'delay'=>$delay,'label' => $label);

        $route  = 'sms';

        if($otp){
            $route = 'otp';
        }

        if(!empty($sender)){
            $payload['sender'] = $sender;
            $route  = 'smstp';

            if($otp){
                $route = 'otptp';
            }
        }
        
        $result  = $sidekiq->sendToQueue($payload,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
        }

    }


    public function getDelayTime($delay){

        $hour = (int) date("G", strtotime($delay));

        if($hour >= 7 && $hour <= 22 ){

            return $delay;
            
        }else{

            if($hour > 22 && $hour <= 24){
                $delay = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 20:00:00',strtotime($delay)));
            }else{
                $delay = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 20:00:00',strtotime($delay)))->subDay();
            }

            return $delay;
        }

    }


    /*public function sendToWorkerTest($to, $message, $label = 'label', $priority = 0, $delay = 0){

        $worker = new IronWorker(array(
            'token' => Config::get('queue.connections.ironworker.token'),
            'project_id' => Config::get('queue.connections.ironworker.project')
        ));
        
        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }
    
        $payload = array('to'=>$to,'message'=>$message);
        $options = array('delay'=>$delay,'priority'=>$priority,'label' => $label, 'cluster' => 'dedicated');
        $queue_name = 'TestSmsApi';

        $messageid = $worker->postTask($queue_name,$payload,$options);

        return $messageid;

    }*/
    
}