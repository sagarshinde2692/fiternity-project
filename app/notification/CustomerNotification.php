<?PHP namespace App\Notification;

use Config;

Class CustomerNotification extends Notification{

	public function bookTrial ($data){

		$to 		=  	array($data['reg_id']);

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		$text = "Your ".$session_type." session with ".ucwords($data['finder_name'])." has been confirmed for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Know the details of your trial.";
		
		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);

		$label = 'BookTrial-C';
		$priority = 1;
		$device_type = $data['device_type'];

		return $this->sendToWorker($device_type, $to, $text, $notif_id, $notif_type, $notif_object, $label, $priority);
	}



	public function rescheduledBookTrial ($data){

		$to 		=  	array($data['reg_id']);

		$session_type = (isset($data['type']) && $data['type'] != '' && $data['type'] == 'memberships') ? 'workout' : 'trial';

		$text = "Your ".$session_type." session with ".ucwords($data['finder_name'])." has been rescheduled for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". We hope you have a good session.";
		

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);
		
		$label = 'RescheduledTrial-C';
		$priority = 1;
		$device_type = $data['device_type'];

		return $this->sendToWorker($device_type, $to, $text, $notif_id, $notif_type, $notif_object, $label, $priority);
	}

	


	public function bookTrialReminderBefore12Hour ($data, $delay){

		$to 		=  	array($data['reg_id']);
		$bity_url 	= 	$google_pin = "";
		
		$text = "Get ready for your trial at ".ucwords($data['finder_name'])."! Prep yourself with what to expect and more…";

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);

		$label = 'TrialRmdBefore12Hr-C';
		$priority = 0;
		$device_type = $data['device_type'];

		return $this->sendToWorker($device_type, $to, $text, $notif_id, $notif_type, $notif_object, $label, $priority, $delay);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to 		=  	array($data['reg_id']);
		$bity_url 	= 	$google_pin = "";
		
		$text = ucwords($data['finder_name'])." are waiting for you! Here is the best route to make it to your session";

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);

		$label = 'TrialRmdBefore1Hr-C';
		$priority = 0;
		$device_type = $data['device_type'];

		return $this->sendToWorker($device_type, $to, $text, $notif_id, $notif_type, $notif_object, $label, $priority, $delay);
	}


	public function bookTrialReminderAfter2Hour ($data, $delay){

		$to 		=  	array($data['reg_id']);

		$text = "We hope you had a good session at ".ucwords($data['finder_name']).". Share your feedback and get Rs. 250/- off on your purchase!";

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);

		$label = 'TrialRmdAfter2Hr-C';
		$priority = 0;
		$device_type = $data['device_type'];

		return $this->sendToWorker($device_type, $to, $text, $notif_id, $notif_type, $notif_object, $label, $priority, $delay);
	}

	public function cancelBookTrial ($data){

		$to 		=  	array($data['reg_id']);

		$text = "Your session at ".ucwords($data['finder_name'])." for ".date(' jS F\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) ." has been cancelled. Keep your fitness going, explore over 1500+ fitness options here!";

		$notif_id = (int)$data['_id'];
		$notif_type = 'open_trial';
		$notif_object = array('trial_id'=>(int)$data['_id']);

		$label = 'CancelTrial-C';
		$priority = 0;
		$device_type = $data['device_type'];

		return $this->sendToWorker($device_type, $to, $text, $notif_id, $notif_type, $notif_object, $label, $priority);
	}


}