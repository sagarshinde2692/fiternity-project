<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && ($data['type'] == "vip_booktrials" || $data['type'] == "vip_booktrials_rewarded" || $data['type'] == "vip_booktrials_invited" )){

			$label = 'VipTrial-Instant-Customer';
		}

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

	public function cancelBookTrialByVendor ($data){

		$label = 'Vendor-trial-cancellation-email-to-customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}


	public function manualBookTrial ($data){

		$label = 'ManualTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function manualTrialAuto ($data){

		$label = 'ManualTrialAuto-Customer';
	
		$to = $data['customer_phone'];
	
		return $this->common($label,$to,$data);
	}

	public function reminderToConfirmManualTrial ($data,$delay){
	
		$label = 'Reminder-To-Confirm-ManualTrial-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}
	
	public function sendCodOrderSms ($data){

		$label = 'Order-COD-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function requestCodOrderSms ($data){

		$label = 'Order-COD-Request-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}


	public function sendPgOrderSms ($data){

		$label = 'Order-PG-Customer';

		if($data['type'] == 'crossfit-week'){

			$label = 'Order-PG-Crossfit-Week-Customer';
		}
		
		if($data['type'] == 'wonderise'){

			$label = 'Order-PG-Wonderise-Customer';
		}

		if($data['type'] == 'combat-fitness'){

			$label = 'Order-PG-Combat-fitness-Customer';
		}

		if($data['type'] == 'mickeymehtaevent'){

			$label = 'Order-PG-Mickeymehtaevent-Customer';
		}

		if($data['type'] == 'events'){
			$label = 'Order-PG-Event';
		}
		
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

	public function bookTrialReminderAfter2HourRegular($data,$delay){

		$label = 'AutoTrial-ReminderAfter2HourRegular-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

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

    public function giveCashbackOnTrialOrderSuccessAndInvite($data){

        $label = 'Give-Cashback-On-Trial-OrderSuccessAndInvite-Instant-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data);

    }

	public function healthyTiffinTrial($data){

		$label = 'HealthyTiffinTrial-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function healthyTiffinMembership($data){

		$label = 'HealthyTiffinMembership-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function reminderAfter2Hour3DaysTrial($data,$delay){

		$label = 'Missedcall-GymTrial-N+2-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}


	public function inviteSMS($type, $data){

		switch ($type){
			case 'vip_booktrials':
				$label = 'Invite-friend-for-vip-trial';
				break;
			case 'vip_booktrials_invited':
				$label = 'Invite-friend-for-vip-trial';
				break;
			case 'vip_3days_booktrials':
				$label = 'Invite-friend-for-vip-trial';
				break;
			case 'memberships':
				$label = 'Invite-friend-for-membership';
				break;
			default:
				$label = 'Invite-friend-for-trial';
				break;
		}

		$to = $data['invitee_phone'];

		return $this->common($label,$to,$data);

	}

	public function respondToInviteSMS($data){

		$label = 'respond-to-invite-for-trial';

		$to = $data['host_phone'];

		return $this->common($label,$to,$data);
	}

	public function vipReward($data){

		$label = 'VipReward-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function genericOtp ($data){

		$label = 'Generic-Otp-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function yogaDayPass($data){

		$label = 'YogaDayPass-Instant-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function bookYogaDayTrial ($data){

		$label = 'YogaDay-AutoTrial-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function nutritionStore ($data){

		$label = 'NutritionStore-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function landingPageCallback ($data){

		$label = 'FitnessCanvas-Customer';

		switch ($data['capture_type']) {
			case 'fitness_canvas': $label = 'FitnessCanvas-Customer';break;
			default:return "no email sms";break;
		}

		$to = $data['phone'];

		return $this->common($label,$to,$data);

	}

	public function reminderRescheduleAfter4Days($data,$delay){

		$label = 'RescheduleAfter4Days-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}

	public function ozonetelCapture($data){

       $label = 'OzonetelCapture-Customer';
       
       $to = $data['customer_contact_no'];

       return $this->common($label,$to,$data);

   	}

  	public function downloadApp($data){

       $label = 'DownloadApp-Customer';
       
       $to = $data['phone'];

       return $this->common($label,$to,$data);

   	}

   	public function rewardClaim($data){

		$label = $data['label'];

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function sendPaymentLinkAfter3Days($data,$delay){

        $label = 'SendPaymentLinkAfter3Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    public function sendPaymentLinkAfter7Days($data,$delay){

        $label = 'SendPaymentLinkAfter7Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    public function sendPaymentLinkAfter15Days($data,$delay){

        $label = 'SendPaymentLinkAfter15Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    public function sendPaymentLinkAfter30Days($data,$delay){

        $label = 'SendPaymentLinkAfter30Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    public function sendPaymentLinkAfter45Days($data,$delay){

        $label = 'SendPaymentLinkAfter45Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    public function purchaseInstant($data){

        $label = 'PurchaseInstant-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data);

    }

    public function purchaseAfter10Days($data,$delay){

        $label = 'PurchaseAfter10Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    public function purchaseAfter30Days($data,$delay){

        $label = 'PurchaseAfter30Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }
	public function referFriend($data){

		$label = 'Refer-friend';

		$to = $data['invitee_phone'];

		return $this->common($label,$to,$data);
	}

	public function referralFitcash($data){

		$label = 'Referral-fitcashplus';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function common($label,$to,$data,$delay = 0){

		if(isset($data['source']) && $data['source'] == 'cleartrip'){
			return "";
		}

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
