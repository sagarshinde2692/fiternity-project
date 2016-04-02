<?PHP namespace App\Notification;

use App\Services\Sidekiq as Sidekiq;

abstract Class Notification {

    protected $sidekiq;

    public function __construct(Sidekiq $sidekiq) {

        $this->sidekiq = $sidekiq;
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


    public function sendToWorker($device_type, $to, $text,  $notif_id, $notif_type, $notif_object, $label = 'label', $priority = 0, $delay = 0){

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }
    
        $payload = array('device_type'=>$device_type,'to'=>$to,'text'=>$text,'notif_id'=>$notif_id,'notif_type'=>$notif_type,'notif_object'=>$notif_object,'delay'=>$delay,'priority'=>$priority,'label' => $label);
        
        $route  = 'notify';
        $result  = $this->sidekiq->sendToQueue($payload,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
        }

    }



}