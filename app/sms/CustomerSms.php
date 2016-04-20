<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$label = 'AutoTrial-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function bookTrialFreeSpecial ($data){

		$label = 'AutoTrial-Instant-FreeSpecial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function bookTrialReminderBefore1Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore1Hour-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}


	public function bookTrialReminderAfter2Hour ($data, $delay){

		$label = 'AutoTrial-ReminderAfter2Hour-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}


	public function cancelBookTrial ($data){

		$label = 'Cancel-Trial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}


	public function manualBookTrial ($data){

		$label = 'ManualTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function sendCodOrderSms ($data){

		$label = 'Order-COD-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}


	public function sendPgOrderSms ($data){

		$label = 'Order-PG-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function forgotPasswordApp ($data){

		$label = 'ForgotPassword-App-Customer';
		
		$to = $data['contact_no'];

		return $this->common($label,$to,$data);
	}

	public function buyLandingpagePurchase ($data){

		$label = 'Purchase-LandingPage-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function generalSms ($data){

		$label = 'Missedcall-Genral-Customer';
		
		$to = $data['to'];

		return $this->common($label,$to,$data);
	}

	public function missedCallDelay ($data,$delay){

		$label = 'Missedcall-N-3-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}

	public function confirmTrial($data){

		$label = 'Missedcall-Reply-N-3-ConfirmTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function cancelTrial($data){

		$label = 'Missedcall-Reply-N-3-CancelTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function rescheduleTrial($data){

		$label = 'Missedcall-Reply-N-3-RescheduleTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function likedTrial($data){

		$label = 'Missedcall-Reply-N+2-Liked-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function exploreTrial($data){

		$label = 'Missedcall-Reply-N+2-Explore-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function notAttendedTrial($data){

		$label = 'Missedcall-Reply-N+2-NotAttended-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function bookTrialReminderAfter2HourRegular($data){

		$label = 'AutoTrial-ReminderAfter2HourRegular-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function orderAfter3Days($data,$delay){

		$label = 'S+3-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}

	public function orderRenewalMissedcall($data,$delay){

		$label = 'Missedcall-Membership-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}

	public function renewOrder($data){

		$label = 'Missedcall-Reply-Membership-Renew-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function alreadyExtendedOrder($data){	

		$label = 'Missedcall-Reply-Membership-AlreadyExtended-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function exploreOrder($data){

		$label = 'Missedcall-Reply-Membership-Explore-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function common($label,$to,$data,$delay = 0){

		$template = \Template::where('label',$label)->first();

		$to = array($to);

		$message = $this->bladeCompile($template->sms_text,$data);

		return $this->sendToWorker($to, $message, $label, $delay);
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