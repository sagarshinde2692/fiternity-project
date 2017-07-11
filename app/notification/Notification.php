<?PHP namespace App\Notification;

use App\Services\Sidekiq as Sidekiq;

abstract Class Notification {

    protected $sidekiq;

    public function __construct(Sidekiq $sidekiq) {

        $this->sidekiq = $sidekiq;
    }

    public function __call($method, $arguments){

        $qualified_class_name = get_class($this);
        
        $reflect = new \ReflectionClass($this);
        $class_name = $reflect->getShortName();

        $reflect = new \ReflectionClass($qualified_class_name);
        $objInstance = $reflect->newInstanceArgs();
        
		if(count($arguments) < 2 || (is_int($arguments[1]) && $arguments[1] == 0) || !in_array($method, Config::get('app.delay_methods'))){
			return $objInstance->$method($arguments[0], 0);
		}else{
			$utilities = new Utilities();
			return $utilities->scheduleCommunication($arguments, $method, $class_name);
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


    public function sendToWorker($device_type, $to, $text,  $notif_id, $notif_type, $notif_object, $label = 'label', $delay = 0){

        if(is_array($delay))
        {
            \Log::info('notification - '.$label.' -- '. $delay['date']);
        }else{
            \Log::info('notification - '.$label.' -- '. $delay);
        }

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }
        

        if($device_type == 'ios'){

            $notif_object['notif_id'] = $notif_id;
            $notif_object['notif_type'] = $notif_type;

            $app_payload = [
                "aps"=>[
                    "alert"=>[
                        "body"=>$text,
                        "title"=>$notif_object['title']
                    ],
                    "sound"=>"default",
                    "badge"=> 1
                ],
                'notif_object'=>$notif_object
            ];

        }else{

            $app_payload = [
                'text'=>$text,
                'notif_id'=>$notif_id,
                'notif_type'=>$notif_type,
                'notif_object'=>$notif_object
            ];
            
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