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


    public function sendToWorker($device_type, $to, $text,  $notif_id, $notif_type, $notif_object, $label = 'label', $delay = 0){

        if(is_array($delay))
        {
            \Log::info('email - '.$label.' -- '. $delay['date']);
        }else{
            \Log::info('email - '.$label.' -- '. $delay);
        }

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }
        

        if($device_type == 'ios'){
            $app_payload = array("aps"=>array("alert"=>array("body"=>$text)),'notif_id'=>$notif_id,'notif_type'=>$notif_type,'notif_object'=>$notif_object);
        }else{
            $app_payload = array('text'=>$text,'notif_id'=>$notif_id,'notif_type'=>$notif_type,'notif_object'=>$notif_object);
        }

        $payload = array('to'=>$to,'delay'=>$delay,'label' => $label,'app_payload'=>$app_payload);
        
        $route  = $device_type;
        $result  = $this->sidekiq->sendToQueue($payload,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
        }

    }



}