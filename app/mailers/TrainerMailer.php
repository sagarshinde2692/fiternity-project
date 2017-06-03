<?PHP namespace App\Mailers;

use Config,Mail;

Class TrainerMailer extends Mailer {

	public function instantSlotBooking ($data){

		$label = 'DietPlan-InstantSlotBooking-Trainer';

		if($data['trainer_email'] != ''){
			$user_email 	=  	explode(',', $data['trainer_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['trainer_name']);
		
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function before3HourSlotBooking ($data,$delay){

		$label = 'DietPlan-Before3HourSlotBooking-Trainer';

		if($data['trainer_email'] != ''){
			$user_email 	=  	explode(',', $data['trainer_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['trainer_name']);
		
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data,$delay);
	}

	public function dietPlanAfter15DaysReviewSlotConfirm ($data){

		$label = 'DietPlan-After15DaysReview-SlotConfirm-Trainer';

		if($data['trainer_email'] != ''){
			$user_email 	=  	explode(',', $data['trainer_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['trainer_name']);
		
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function dietPlanAfter15DaysFollowupSlotConfirm ($data){

		$label = 'DietPlan-After15DaysFollowup-SlotConfirm-Trainer';

		if($data['trainer_email'] != ''){
			$user_email 	=  	explode(',', $data['trainer_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['trainer_name']);
		
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	

	public function common($label,$data,$message_data,$delay = 0){

		/*if(in_array(Config::get('mail.to_mailus'),$message_data['user_email'])){
			$delay = 0;
			$data['label'] = $label;
			$data['user_name'] = $message_data['user_name'];
			$label = 'EmailFailureNotification-LMD';
			$message_data['user_email'] = array('vinichellani@fitternity.com');
		}*/

		$template = \Template::where('label',$label)->first();

		$email_template = 	$this->bladeCompile($template->email_text,$data);
		$email_subject = 	$this->bladeCompile($template->email_subject,$data);

		//$message_data['user_email'] = array('utkarshmehrotra@fitternity.com','pranjalisalvi@fitternity.com','sailismart@fitternity.com');

		$message_data['bcc_emailids'] = ($template->email_bcc != "") ? array_merge(explode(',', $template->email_bcc),array(Config::get('mail.to_mailus'))) : array(Config::get('mail.to_mailus'));

		$message_data['email_subject'] = $email_subject;

		return $this->sendDbToWorker('vendor',$email_template, $message_data, $label, $delay);

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
