<?PHP namespace App\Sms;

use Config;

Class FinderSms extends VersionNextSms{

	public function bookTrial ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'AutoTrial-Instant-Vendor';

		return $this->common($label,$to,$data);
	}


	public function rescheduledBookTrial ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'RescheduleTrial-Instant-Vendor';

		return $this->common($label,$to,$data);
	}


	public function bookTrialReminderBefore1Hour ($data, $delay){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'AutoTrial-ReminderBefore1Hour-Vendor';

		return $this->common($label,$to,$data,$delay);
	}


	public function cancelBookTrial ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'Cancel-Trial-Vendor';

		return $this->common($label,$to,$data);
	}

	public function sendPgOrderSms ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'Order-PG-Vendor';

		return $this->common($label,$to,$data);
	}

	public function buyLandingpagePurchaseEefashrof ($data, $count){

		$to 		=  	['9730401839','9773348762'];

		$label = 'Purchase-LandingPage-Eefashrof';

		return $this->common($label,$to,$data);
	}

	public function confirmTrial($data){

		$label = 'Missedcall-Reply-N-3-ConfirmTrial-Vendor';
		
		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

	}

	public function cancelTrial($data){

		$label = 'Missedcall-Reply-N-3-CancelTrial-Vendor';
		
		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

	}

	public function rescheduleTrial($data){

		$label = 'Missedcall-Reply-N-3-RescheduleTrial-Vendor';
		
		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

	}

	public function common($label,$to,$data,$delay = 0){

		$template = \Template::where('label',$label)->first();

		$message = $this->bladeCompile($template->sms_text,$data);

		$to = array('9920864894','9920093394');

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