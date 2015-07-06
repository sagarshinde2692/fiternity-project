<?PHP namespace App\Notification;

use Queue;

use PushNotification;

abstract Class Notification {

	public function sendTo($to, $message, $delay = null, $title = "Fitternity", $booktrialid, $type = "generic", $slug){

        // return "$to, $message, $delay, $title , $booktrialid, $type , $slug";

		if($delay == null){

            try {
                $messageid = Queue::push(function($job) use ($to, $message, $title, $booktrialid, $type, $slug){ 

                    $job_id = $job->getJobId(); 
                    $msg = strip_tags($message);
                    try{
                        PushNotification::app('appNameAndroid')->to($to)->send($msg, array('title' => $title, 'id' => $booktrialid, 'type' => $type, 'slug' => $slug));
                    }catch(\Exception $e){
                        // do nothing... php will ignore and continue    
                    }
                    $job->delete();  

                }, array(), 'pullapp');

                return $messageid;

            } catch(\Exception $e){
                // do nothing
            }


        }else{

            $seconds    =   $this->getSeconds($delay);

            try {
                $messageid  =   Queue::later($seconds, function($job) use ($to, $message, $title, $booktrialid, $type, $slug){ 

                    $job_id = $job->getJobId();

                    $msg = strip_tags($message); 
                    try{
                        PushNotification::app('appNameAndroid')->to($to)->send($msg , array('title' => $title, 'id' => $booktrialid, 'type' => $type, 'slug' => $slug));
                    }catch (\Exception $e){
                        // do nothing... php will ignore and continue    
                    }
                    $job->delete();  

                }, array(), 'pullapp');

                return $messageid;
            } catch(\Exception $e){
                // do nothing
            }
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