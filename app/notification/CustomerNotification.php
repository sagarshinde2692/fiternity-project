<?PHP namespace App\Notification;

use Config;
use App\Services\Utilities as Utilities;

Class CustomerNotification extends Notification{

	protected function bookTrial ($data){

		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && $data['type'] === "vip_booktrials"){

			$label = 'VipTrial-Instant-Customer';
		}

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");

		return $this->common($label,$data,$notif_type,$notif_object);
	}

	protected function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");

		return $this->common($label,$data,$notif_type,$notif_object);
	}

	protected function bookTrialReminderBefore12Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore12Hour-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-12","max_time"=>strtotime($data["schedule_date_time"]));
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}

	protected function bookTrialReminderBefore20Min ($data, $delay){

		$label = 'AutoTrial-ReminderBefore20Min-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-20m","max_time"=>strtotime($data["schedule_date_time"])+(30*60));
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}

	protected function bookTrialReminderBefore1Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore1Hour-Customer';
		
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-1","max_time"=>strtotime($data["schedule_date_time"]));
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}

	protected function bookTrialReminderBefore3Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore3Hour-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-3","max_time"=>strtotime($data["schedule_date_time"]), "url"=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/session_reminder?notif_id=');
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}


	protected function bookTrialReminderAfter2Hour ($data, $delay){

		$label = 'AutoTrial-ReminderAfter2Hour-Customer';

		if(isset($data['corporate_id']) && $data['corporate_id'] != ''){
			$label = 'AutoTrial-ReminderAfter2Hour-Customer-Reliance';
		}

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n+2", "url"=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/let_us_know?notif_id=');
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}

	protected function cancelBookTrial ($data){

		\Log::info("cancelBookTrial notification:: ", [$data]);

		$label = 'Cancel-Trial-Customer';
        
        if(!empty($data['studio_extended_validity_order_id'])){
            if(!empty($data['studio_sessions'])){
                $avail = $data['studio_sessions']['total_cancel_allowed'] - $data['studio_sessions']['cancelled'];
                $avail = ($avail<0)?0:$avail;
                $data['studio_extended_details'] = [
                    'can_cancel' => $avail,
                    'total_cancel' => $data['studio_sessions']['total_cancel_allowed']
                ];
                $data['app_onelink'] = "https://go.onelink.me/I0CO?pid=studioextcancelmail";
            }	
        }
        
        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");
		
		return $this->common($label,$data,$notif_type,$notif_object);
	}


	protected function sendPaymentLinkAfter3Days($data,$delay){

        $label = 'SendPaymentLinkAfter3Days-Customer';

		$notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+3");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function sendPaymentLinkAfter7Days($data,$delay){

        $label = 'SendPaymentLinkAfter7Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+7");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function sendPaymentLinkAfter15Days($data,$delay){

        $label = 'SendPaymentLinkAfter15Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+15");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function sendPaymentLinkAfter30Days($data,$delay){

        $label = 'SendPaymentLinkAfter30Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+30");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function sendPaymentLinkAfter45Days($data,$delay){

        $label = 'SendPaymentLinkAfter45Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+45");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function purchaseInstant($data){

        $label = 'PurchaseInstant-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"instance");
		
		return $this->common($label,$data,$notif_type,$notif_object);
    }

    protected function purchaseAfter10Days($data,$delay){

        $label = 'PurchaseAfter10Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"p+10");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function purchaseAfter30Days($data,$delay){

        $label = 'PurchaseAfter30Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"p+30");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function sendRenewalPaymentLinkBefore7Days($data,$delay){

        $label = 'MembershipRenewalLinkSentBefore7Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"rl-7");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function sendRenewalPaymentLinkBefore1Days($data,$delay){

        $label = 'MembershipRenewalLinkSentBefore1Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"rl-1");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    protected function purchaseFirst($data,$delay){

        $label = 'PurchaseFirst-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"purchase_first");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function postTrialFollowup1After15Days($data,$delay){

        $label = 'PostTrialFollowup1After15Days-Customer';

        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"f1+15");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }
	public function postTrialFollowup1After3Days($data,$delay){

        $label = 'PostTrialFollowup1After3Days-Customer';

        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"f1+3");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function postTrialFollowup1After7Days($data,$delay){

        $label = 'PostTrialFollowup1After7Days-Customer';

        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"f1+7");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }
    

    public function postTrialFollowup1After30Days($data,$delay){

        $label = 'PostTrialFollowup1After30Days-Customer';

        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"f1+30");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function postTrialFollowup2After3Days($data,$delay){

        $label = 'PostTrialFollowup2After3Days-Customer';

        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"f2+3");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function postTrialFollowup2After7Days($data,$delay){

        $label = 'PostTrialFollowup2After7Days-Customer';

        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"f2+7");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function postTrialFollowup2After15Days($data,$delay){

        $label = 'PostTrialFollowup2After15Days-Customer';

        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"f2+15");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function postTrialFollowup2After30Days($data,$delay){

        $label = 'PostTrialFollowup2After30Days-Customer';

        $notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"f2+30");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

	}

	protected function bookTrialReminderBefore10Min($data,$delay){

		\Log::info("workout sessoin instant notification");
		// return "sent";
		$label = 'BookTrialReminderBefore10Min-Customer';

		$notif_type = 'open_trial';
		
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-10m", "url"=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/activate_session?notif_id=');
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

	}
	
	protected function reviewReminder($data,$delay){

		$label = 'ReviewReminder-Customer';

		$notif_type = 'open_trial';
		
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"review", "url"=>Config::get('app.url').'/notificationdatabytrialid/'.$data['_id'].'/review?notif_id=');
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

	}
	
	

	public function common($label,$data,$notif_type,$notif_object,$delay = 0){

		$template = \Template::where('label',$label)->first();
		// $device_type = $data['device_type'];

		$device = \Device::where('customer_id', $data['customer_id'])
			->where('reg_id','exists',true)
			->whereIn('type', ["android", "ios"])
			->orderBy('updated_at', 'desc')
			->first();

		if($device){

			$to = array($device['reg_id']);
			$device_type = $device['type'];

		}else{
			
			\Log::info("no device id");
			return "no device id";
		}

		$text = $this->bladeCompile($template->notification_text,$data);

		$notification_title = isset($template->notification_title)?$template->notification_title:"Fitternity";
		$title = $this->bladeCompile($notification_title,$data);
		

		$notificationData = [
			"label"=> $label,
			"time" => $notif_object["time"],
			"customer_id" => $data["customer_id"],
			"schedule_for" => 0,
			"max_time"=> null
		];

		if(isset($notif_object["order_id"])){
			$notificationData["order_id"] = (int)$notif_object["order_id"];
		}

		if(isset($notif_object["trial_id"])){
			$notificationData["booktrial_id"] = (int)$notif_object["trial_id"];
		}

		if($delay != ""){
			$notificationData["schedule_for"] = strtotime($delay);
		}

		if(isset($notif_object["max_time"])){
			$notificationData["max_time"] = $notif_object["max_time"];
		}

		
		$notificationData["text"]  = $text;
		$notificationData["title"]  = $title;
		
		$unique_id = $this->getUniqueId($notificationData);
		
		if(isset($notif_object["url"])){
			$notif_object["url"] = $notif_object["url"].$unique_id;
		}


		$notif_object["customer_id"] = (int)$data["customer_id"];
		$notif_object["unique_id"] = $unique_id;
		$notif_object["title"] = $title;

		$text = strip_tags($text);
		$notif_object["text"] = $text;

		$notif_id = (int)$data["_id"];

		return $this->sendToWorker($device_type, $to, $text, $notif_id, $notif_type, $notif_object, $label, $delay);
	}

	public function bladeCompile($value, array $args = array())
	{
	    $generated = \Blade::compileString($value);

	    ob_start() and extract($args, EXTR_SKIP);

	    // We'll include the view contents for parsing within a catcher
	    // so we can avoid any WSOD errors. If an exception occurs we
	    // will throw it out to the exception handler.
	    try
	    {
	        eval('?>'.$generated);
	    }

	    // If we caught an exception, we'll silently flush the output
	    // buffer so that no partially rendered views get thrown out
	    // to the client and confuse the user with junk.
	    catch (\Exception $e)
	    {
	        ob_get_clean(); throw $e;
	    }

	    $content = ob_get_clean();

	    return $content;
	}

	public function getUniqueId($data){

		$utilities = new Utilities();

		$notificationTracking = $utilities->addNotificationTracking($data);

		return $notificationTracking->_id;
	}


}
