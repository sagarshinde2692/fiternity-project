<?PHP namespace App\Mailers;

use Config,Mail;

Class CustomerMailer extends Mailer {

	public function bookTrial ($data){

		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && $data['type'] === "vip_booktrials"){

			$label = 'VipTrial-Instant-Customer';
		}

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}

	public function bookYogaDayTrial ($data){

		$label = 'YogaDay-AutoTrial-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}

	public function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}


	public function bookTrialReminderBefore12Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore12Hour-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$delay);
	}


	public function bookTrialReminderAfter2Hour ($data, $delay){

		$label = 'AutoTrial-ReminderAfter2Hour-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$delay);
	}

	public function manualBookTrial ($data){

		$label = 'ManualTrial-Customer';

		$message_data 	= array(
			'user_email' => array(Config::get('mail.to_mailus')),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}

	
	public function sendCodOrderMail ($data){

		$label = 'Order-COD-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}


	public function sendPgOrderMail ($data){

		$label = 'Order-PG-Customer';

		if($data['type'] == 'crossfit-week'){

			$label = 'Order-PG-Crossfit-Week-Customer';
		}

		if($data['type'] == 'wonderise'){

			$label = 'Order-PG-Wonderise-Customer';
		}
		if($data['type'] == 'lyfe'){

			$label = 'Order-PG-Lyfe-Customer';
		}

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function forgotPassword ($data){

		$label = 'ForgotPassword-Customer';

		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function forgotPasswordApp ($data){

		$label = 'ForgotPassword-App-Customer';

		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function register($data){

		$label = 'Register-Customer';

		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		return $this->common($label,$data,$message_data);
	}


	public function buyArsenalMembership ($data){

		$email_template_customer 	= 	'emails.order.pg_arsenalmembership';
		$email_template_mailus 		= 	'emails.order.pg_arsenalmembership_mailus';
		$template_data 				= 	$data;
		$bcc_emailids 				= 	Config::get('mail.bcc_emailds_mailus');
		$subject  					=   'Regarding your purchase on Arsernal Membership by Fitternity';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuyArsenalMbrShip-C';
		
		$this->sendToWorker('customer',$email_template_customer, $template_data, $message_data, $label);

		// array_set($message_data, 'user_email', 'sanjay.id7@gmail.com');
		array_set($message_data, 'user_email', 'mailus@fitternity.com');
		array_set($message_data, 'user_name', 'Fitternity');

		$label = 'BuyArsenalMbrShip-Us';
		
		return $this->sendToWorker('customer',$email_template_mailus, $template_data, $message_data, $label);
	}

	public function buyLandingpagePurchase ($data){

		$label = 'Purchase-LandingPage-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}


	public function cancelBookTrial($data){
		
		$label = 'Cancel-Trial-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function orderAfter10Days($data, $delay){

		$label = 'S+10-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$delay);
	}

	public function orderRenewalMissedcall($data, $delay){

		$label = 'MembershipRenewal-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$delay);
	}


	public function healthyTiffinTrial($data){

		$label = 'HealthyTiffinTrial-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function healthyTiffinMembership($data){

		$label = 'HealthyTiffinMembership-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function yogaDayPass($data){

		$label = 'YogaDayPass-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}


	public function common($label,$data,$message_data,$delay = 0){

		$template = \Template::where('label',$label)->first();

		$email_template = 	$this->bladeCompile($template->email_text,$data);
		$email_subject = 	$this->bladeCompile($template->email_subject,$data);

		$message_data['bcc_emailids'] = ($template->email_bcc != "") ? array_merge(explode(',', $template->email_bcc),array(Config::get('mail.to_mailus'))) : array(Config::get('mail.to_mailus'));

		$message_data['email_subject'] = $email_subject;

		return $this->sendDbToWorker('customer',$email_template, $message_data, $label, $delay);

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