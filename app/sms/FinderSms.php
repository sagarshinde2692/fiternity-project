<?PHP namespace App\Sms;

use Config;

Class FinderSms extends VersionNextSms{

	public function bookTrial ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'AutoTrial-Instant-Vendor';

		if(isset($data['type']) && ($data['type'] == "vip_booktrials" || $data['type'] == "vip_booktrials_rewarded" || $data['type'] == "vip_booktrials_invited" )){

			$label = 'VipTrial-Instant-Vendor';
		}

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

	public function bookTrialReminderBefore6Hour ($data, $delay){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'AutoTrial-ReminderBefore6Hour-Vendor';

		return $this->common($label,$to,$data,$delay);
	}


	public function cancelBookTrial ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'Cancel-Trial-Vendor';

		return $this->common($label,$to,$data);
	}

	public function cancelBookTrialByVendor ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'Vendor-trial-cancellation-email-to-vendor';

		return $this->common($label,$to,$data);
	}

	public function sendPgOrderSms ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'Order-PG-Vendor';

		if($data['type'] == 'crossfit-week'){

			$label = 'Order-PG-Crossfit-Week-Vendor';
		}

		return $this->common($label,$to,$data);
	}

	public function buyLandingpagePurchaseEefashrof ($data, $count){

		$to 		=  	['9730401839','9773348762'];

		$label = 'Purchase-LandingPage-Eefashrof';

		return $this->common($label,$to,$data);
	}

	public function confirmTrial($data){

		$label = 'AutoTrial-ReminderBefore20Min-Confirm-Vendor';
		
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

	public function healthyTiffinTrial($data){

		$label = 'HealthyTiffinTrial-Instant-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

	}

    public function healthyTiffinMembership($data){

        $label = 'HealthyTiffinMembership-Instant-Vendor';

        $to = explode(',', $data['finder_vcc_mobile']);

        return $this->common($label,$to,$data);

    }

    public function bookYogaDayTrial ($data){

		$label = 'YogaDay-AutoTrial-Instant-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

        return $this->common($label,$to,$data);
	}

	public function firstTrial($data){

		$label = 'First-Autotrial-Fitternity';

		$to = array('9930206022','8976167917','9867812126');

        return $this->common($label,$to,$data);
	}

	public function nutritionStore ($data){

		$label = 'NutritionStore-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

        return $this->common($label,$to,$data);
	}

	public function manualTrialAuto ($data){

		$label = 'ManualTrialAuto-Finder';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}

	public function reminderToConfirmManualTrial ($data,$delay){
	
		$label = 'Reminder-To-Confirm-ManualTrial-Finder';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data,$delay);
	}

	public function changeStartDate ($data){
	
		$label = 'ChangeStartDate-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}

	public function bookTrialReminderBefore20Min ($data){
	
		$label = 'AutoTrial-ReminderBefore20Min-Confirm-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}

	public function ozonetelCapture($data){

       	$label = 'OzonetelCapture-Vendor';
       
       	$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

   	}
	
	public function common($label,$to,$data,$delay = 0){

		$template = \Template::where('label',$label)->first();

		$message = $this->bladeCompile($template->sms_text,$data);

		// $to = array('7506262489');
		
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
