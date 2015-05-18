<?PHP namespace App\Notification;

use Queue;

abstract Class Notification {

	public function sendTo($to, $message, $delay = null, $type = 'generic'){

        // return $to;

		if($delay == null){

            $messageid = Queue::push(function($job) use ($to, $message, $type){ 

                $job_id = $job->getJobId(); 

                $msg = strip_tags($message);

                PushNotification::app('appNameAndroid')->to($to)->send($msg, array('title' => "Fitternity", 'type' => $type));

                $job->delete();  

            }, array(), 'pullapp');

            return $messageid;

        }else{

            $seconds    =   $this->getSeconds($delay);

            $messageid  =   Queue::later($seconds, function($job) use ($to, $message, $type){ 

                $job_id = $job->getJobId();
                
                $msg = strip_tags($message); 

                PushNotification::app('appNameAndroid')->to($to)->send($msg , array('title' => "Fitternity", 'type' => $type ));

                $job->delete();  

            }, array(), 'pullapp');
            
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