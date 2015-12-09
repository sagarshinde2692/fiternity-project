<?PHP namespace App\Sms;

use Queue, IronWorker, Config;
use App\Services\Sidekiq as Sidekiq;

abstract Class VersionNextSms {

    protected $sidekiq;

    public function __construct(Sidekiq $sidekiq) {

        $this->sidekiq = $sidekiq;
    }

	public function sendTo($to, $message, $delay = null ){

        // return $to;exit;

		if($delay == null){

            $messageid = Queue::push(function($job) use ($to, $message){ 

                $job_id = $job->getJobId(); 

                $msg = strip_tags($message);

                foreach ($to as $number) {
                
                    $url = 'http://www.kookoo.in/outbound/outbound_sms.php';

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

                    $url = 'http://www.kookoo.in/outbound/outbound_sms.php';

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

            $url = 'http://www.kookoo.in/outbound/outbound_sms.php';

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

    public function sendToWorker($to, $message, $label = 'label', $priority = 0, $delay = 0){

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }
    
        $payload = array('to'=>$to,'message'=>$message,'delay'=>$delay,'priority'=>$priority,'label' => $label);
        
        $route  = 'sms';
        $result  = $this->sidekiq->sendToQueue($payload,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
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