<?PHP namespace App\Notification;

use Config;

Class CustomerNotification extends Notification{

	public function bookTrial ($data){

		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && $data['type'] === "vip_booktrials"){

			$label = 'VipTrial-Instant-Customer';
		}

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");

		return $this->common($label,$data,$notif_id,$notif_type,$notif_object);
	}

	public function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Customer';

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");

		return $this->common($label,$data,$notif_id,$notif_type,$notif_object);
	}

	public function bookTrialReminderBefore12Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore12Hour-Customer';

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-12");
		
		return $this->common($label,$data,$notif_id,$notif_type,$notif_object,$delay);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore1Hour-Customer';

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n-1");
		
		return $this->common($label,$data,$notif_id,$notif_type,$notif_object,$delay);
	}


	public function bookTrialReminderAfter2Hour ($data, $delay){

		$label = 'AutoTrial-ReminderAfter2Hour-Customer';

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"n+2");
		
		return $this->common($label,$data,$notif_id,$notif_type,$notif_object,$delay);
	}

	public function cancelBookTrial ($data){

		$label = 'Cancel-Trial-Customer';

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id'],"time"=>"instant");
		
		return $this->common($label,$data,$notif_id,$notif_type,$notif_object);
	}

	public function common($label,$data,$notif_id,$notif_type,$notif_object,$delay = 0){

		$template = \Template::where('label',$label)->first();

		$device_type = $data['device_type'];
		$to =  array($data['reg_id']);
		$text = $this->bladeCompile($template->notification_text,$data);

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


}
