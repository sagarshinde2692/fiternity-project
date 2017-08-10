<?PHP namespace App\Sms;

use Config, Log;
use App\Services\Utilities as Utilities;

Class CustomerSms extends VersionNextSms{

	
	protected function bookTrial ($data){
		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && ($data['type'] == "vip_booktrials" || $data['type'] == "vip_booktrials_rewarded" || $data['type'] == "vip_booktrials_invited" )){

			$label = 'VipTrial-Instant-Customer';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function bookTrialFreeSpecial ($data){

		$label = 'AutoTrial-Instant-FreeSpecial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function bookTrialReminderBefore1Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore1Hour-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}

	protected function bookTrialReminderBefore20Min ($data, $delay){

		$label = 'AutoTrial-ReminderBefore20Min-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}

	protected function bookTrialReminderBefore3Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore3Hour-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}


	protected function bookTrialReminderAfter2Hour ($data, $delay){

		$label = 'AutoTrial-ReminderAfter2Hour-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}


	protected function cancelBookTrial ($data){

		$label = 'Cancel-Trial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function cancelBookTrialByVendor ($data){

		$label = 'Vendor-trial-cancellation-email-to-customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}


	protected function manualBookTrial ($data){

		$label = 'ManualTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function manualTrialAuto ($data){

		$label = 'ManualTrialAuto-Customer';
	
		$to = $data['customer_phone'];
	
		return $this->common($label,$to,$data);
	}

	protected function reminderToConfirmManualTrial ($data,$delay){
	
		$label = 'Reminder-To-Confirm-ManualTrial-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}
	
	protected function sendCodOrderSms ($data){

		$label = 'Order-COD-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function requestCodOrderSms ($data){

		$label = 'Order-COD-Request-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}


	protected function sendPgOrderSms ($data){

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
		
		if($data['type'] == "diet_plan"){
			$label = 'Diet-PG-Customer';
		}
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function forgotPasswordApp ($data){

		$label = 'ForgotPassword-App-Customer';
		
		$to = $data['contact_no'];

		return $this->common($label,$to,$data);
	}

	protected function buyLandingpagePurchase ($data){

		$label = 'Purchase-LandingPage-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function generalSms ($data){

		$label = 'Missedcall-Genral-Customer';
		
		$to = $data['to'];

		return $this->common($label,$to,$data);
	}

	protected function missedCallDelay ($data,$delay){

		$label = 'Missedcall-N-3-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}

	protected function confirmTrial($data){

		$label = 'Missedcall-Reply-N-3-ConfirmTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function cancelTrial($data){

		$label = 'Missedcall-Reply-N-3-CancelTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function rescheduleTrial($data){

		$label = 'Missedcall-Reply-N-3-RescheduleTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function likedTrial($data){

		$label = 'Missedcall-Reply-N+2-Liked-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function exploreTrial($data){

		$label = 'Missedcall-Reply-N+2-Explore-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function notAttendedTrial($data){

		$label = 'Missedcall-Reply-N+2-NotAttended-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function bookTrialReminderAfter2HourRegular($data,$delay){

		$label = 'AutoTrial-ReminderAfter2HourRegular-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}

	protected function orderAfter3Days($data,$delay=0){

		$label = 'S+3-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}

	protected function orderRenewalMissedcall($data,$delay){

		$label = 'Missedcall-Membership-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}

	protected function renewOrder($data){

		$label = 'Missedcall-Reply-Membership-Renew-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function alreadyExtendedOrder($data){	

		$label = 'Missedcall-Reply-Membership-AlreadyExtended-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function exploreOrder($data){

		$label = 'Missedcall-Reply-Membership-Explore-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

    protected function giveCashbackOnTrialOrderSuccessAndInvite($data){

    	return "no sms";

        $label = 'Give-Cashback-On-Trial-OrderSuccessAndInvite-Instant-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data);

    }

	protected function healthyTiffinTrial($data){

		$label = 'HealthyTiffinTrial-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function healthyTiffinMembership($data){

		$label = 'HealthyTiffinMembership-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function reminderAfter2Hour3DaysTrial($data,$delay){

		$label = 'Missedcall-GymTrial-N+2-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}


	protected function inviteSMS($type, $data){

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

	protected function respondToInviteSMS($data){

		$label = 'respond-to-invite-for-trial';

		$to = $data['host_phone'];

		return $this->common($label,$to,$data);
	}

	protected function vipReward($data){

		$label = 'VipReward-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function genericOtp ($data){

		$label = 'Generic-Otp-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function yogaDayPass($data){

		$label = 'YogaDayPass-Instant-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function bookYogaDayTrial ($data){

		$label = 'YogaDay-AutoTrial-Instant-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function nutritionStore ($data){

		$label = 'NutritionStore-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function landingPageCallback ($data){

		$label = 'FitnessCanvas-Customer';

		switch ($data['capture_type']) {
			case 'fitness_canvas': $label = 'FitnessCanvas-Customer';break;
			case 'renew-membership': $label = 'RenewMembership-Customer';break;
			case 'upgrade-membership': $label = 'UpgradeMembership-Customer';break;
			default:return "no email sms";break;
		}

		$to = $data['phone'];

		return $this->common($label,$to,$data);

	}

	protected function changeStartDate ($data){

		$label = 'ChangeStartDate-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function reminderRescheduleAfter4Days($data,$delay){

		$label = 'RescheduleAfter4Days-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}

	protected function ozonetelCapture($data){

       $label = 'OzonetelCapture-Customer';
       
       $to = $data['customer_contact_no'];

       return $this->common($label,$to,$data);

   	}

  	protected function downloadApp($data){

       $label = 'DownloadApp-Customer';
       
       $to = $data['phone'];

       return $this->common($label,$to,$data);

   	}

   	protected function rewardClaim($data){

		$label = $data['label'];

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function sendPaymentLinkAfter3Days($data,$delay){

        $label = 'SendPaymentLinkAfter3Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function sendPaymentLinkAfter7Days($data,$delay){

        $label = 'SendPaymentLinkAfter7Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function sendPaymentLinkAfter15Days($data,$delay){

        $label = 'SendPaymentLinkAfter15Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function sendPaymentLinkAfter30Days($data,$delay){

        $label = 'SendPaymentLinkAfter30Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function sendPaymentLinkAfter45Days($data,$delay){

        $label = 'SendPaymentLinkAfter45Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function purchaseInstant($data){

        $label = 'PurchaseInstant-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data);
    }

	protected function instantSlotBooking($data){

		$label = 'DietPlan-InstantSlotBooking-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	protected function before3HourSlotBooking($data,$delay){

		$label = 'DietPlan-Before3HourSlotBooking-Customer';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);

	}

	protected function dietPlanAfter15DaysReviewSlotConfirm($data){

        $label = 'DietPlan-After15DaysReview-SlotConfirm-Customer';

        $to = $data['phone'];

        return $this->common($label,$to,$data);

    }

    protected function purchaseAfter10Days($data,$delay){

        $label = 'PurchaseAfter10Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function purchaseAfter30Days($data,$delay){

        $label = 'PurchaseAfter30Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }
	protected function referFriend($data){

		$label = 'Refer-friend';

		$to = $data['invitee_phone'];

		return $this->common($label,$to,$data);
	}

	protected function referralFitcash($data){

		$label = 'Referral-fitcashplus';

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

    protected function dietPlanAfter15DaysFollowupSlotConfirm($data){

        $label = 'DietPlan-After15DaysFollowup-SlotConfirm-Customer';

        $to = $data['phone'];

        return $this->common($label,$to,$data);

    }

    protected function bookTrialCancelByVendor($data){

        $label = 'AutoTrial-CancelByVendor-Customer';

        $to = $data['phone'];

        return $this->common($label,$to,$data);

    }

    protected function sendRenewalPaymentLinkBefore7Days($data,$delay){

        $label = 'MembershipRenewalLinkSentBefore7Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function sendRenewalPaymentLinkBefore1Days($data,$delay){

        $label = 'MembershipRenewalLinkSentBefore1Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function purchaseFirst($data,$delay){

        $label = 'PurchaseFirst-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function demonetisation($data){

        $label = 'Demonetisation-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data);

    }

	protected function postTrialFollowup1After3Days($data,$delay){

        $label = 'PostTrialFollowup1After3Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function postTrialFollowup1After7Days($data,$delay){

        $label = 'PostTrialFollowup1After7Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function postTrialFollowup1After15Days($data,$delay){

        $label = 'PostTrialFollowup1After15Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function postTrialFollowup1After30Days($data,$delay){

        $label = 'PostTrialFollowup1After30Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function postTrialFollowup2After3Days($data,$delay){

        $label = 'PostTrialFollowup2After3Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function postTrialFollowup2After7Days($data,$delay){

        $label = 'PostTrialFollowup2After7Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function postTrialFollowup2After15Days($data,$delay){

        $label = 'PostTrialFollowup2After15Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function postTrialFollowup2After30Days($data,$delay){

        $label = 'PostTrialFollowup2After30Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }
	
	protected function myHomFitnessWithoutSlotInstant($data){

        $label = 'MyHomeFitnessWithoutSlotInstant-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data);

    }

	

	protected function myHomeFitnessPurchaseWithoutSlot($data){

        $label = 'MyHomeFitnessPurchaseWithoutSlot-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data);

    }


    protected function linkSentNotSuccess($data){

        $label = 'LinkSentNotSuccess-Customer';

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
