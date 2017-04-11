<?PHP namespace App\Notification;

use Config;
use App\Services\Utilities as Utilities;

Class CustomerNotification extends Notification{

	public function bookTrial ($data){

		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && $data['type'] === "vip_booktrials"){

			$label = 'VipTrial-Instant-Customer';
		}

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");

		return $this->common($label,$data,$notif_type,$notif_object);
	}

	public function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");

		return $this->common($label,$data,$notif_type,$notif_object);
	}

	public function bookTrialReminderBefore12Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore12Hour-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-12","max_time"=>strtotime($data["schedule_date_time"]));
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore1Hour-Customer';
		
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-1","max_time"=>strtotime($data["schedule_date_time"]));
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}

	public function bookTrialReminderBefore3Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore3Hour-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-3","max_time"=>strtotime($data["schedule_date_time"]));
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}


	public function bookTrialReminderAfter2Hour ($data, $delay){

		$label = 'AutoTrial-ReminderAfter2Hour-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n+2");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);
	}

	public function cancelBookTrial ($data){

		$label = 'Cancel-Trial-Customer';

		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");
		
		return $this->common($label,$data,$notif_type,$notif_object);
	}


	public function sendPaymentLinkAfter3Days($data,$delay){

        $label = 'SendPaymentLinkAfter3Days-Customer';

		$notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+3");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function sendPaymentLinkAfter7Days($data,$delay){

        $label = 'SendPaymentLinkAfter7Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+7");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function sendPaymentLinkAfter15Days($data,$delay){

        $label = 'SendPaymentLinkAfter15Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+15");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function sendPaymentLinkAfter30Days($data,$delay){

        $label = 'SendPaymentLinkAfter30Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+30");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function sendPaymentLinkAfter45Days($data,$delay){

        $label = 'SendPaymentLinkAfter45Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"pl+45");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function purchaseInstant($data){

        $label = 'PurchaseInstant-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"instance");
		
		return $this->common($label,$data,$notif_type,$notif_object);
    }

    public function purchaseAfter10Days($data,$delay){

        $label = 'PurchaseAfter10Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"p+10");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

    public function purchaseAfter30Days($data,$delay){

        $label = 'PurchaseAfter30Days-Customer';

        $notif_type = 'open_order';
		$notif_object = array('order_id'=>(int)$data['_id'],"time"=>"p+30");
		
		return $this->common($label,$data,$notif_type,$notif_object,$delay);

    }

	public function common($label,$data,$notif_type,$notif_object,$delay = 0){

		$template = \Template::where('label',$label)->first();
		$device_type = $data['device_type'];
		$to =  array($data['reg_id']);
		$text = $this->bladeCompile($template->notification_text,$data);

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

		$unique_id = $this->getUniqueId($notificationData);

		$notif_object["customer_id"] = (int)$data["customer_id"];
		$notif_object["unique_id"] = $unique_id;
		$notif_object["title"] = "Check this out!";
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
