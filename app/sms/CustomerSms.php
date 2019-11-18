<?PHP namespace App\Sms;

use Config, Log;
use App\Services\Utilities as Utilities;

Class CustomerSms extends VersionNextSms{

	
	protected function bookTrial ($data){
		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && ($data['type'] == "vip_booktrials" || $data['type'] == "vip_booktrials_rewarded" || $data['type'] == "vip_booktrials_invited" )){

			$label = 'VipTrial-Instant-Customer';
		}

		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])) {
			$label = 'AutoTrial-Instant-Customer-abg';
		}

		$header = $this->multifitKioskOrder($data);
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'AutoTrial-Instant-Multifit-Customer';
		}

		if(isset($data['corporate_id']) && $data['corporate_id'] != ''){
			$label = 'AutoTrial-Instant-Customer-Reliance';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function bookTrialFreeSpecial ($data){

		$label = 'AutoTrial-Instant-FreeSpecial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function onepassDynamic ($data){

		$label = 'Onepass-Dynamic-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Customer';
		
		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])) {
			$label = 'RescheduleTrial-Instant-Customer-abg';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function bookTrialReminderBefore12Hour ($data, $delay){

		$label = 'AutoTrial-ReminderBefore12Hour-Customer';

		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])) {
			$label = 'AutoTrial-ReminderBefore12Hour-Customer-abg';
		}

		$header = $this->multifitKioskOrder($data);
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'AutoTrial-ReminderBefore12Hour-Multifit-Customer';
		}

		if(isset($data['corporate_id']) && $data['corporate_id'] != ''){
			$label = 'AutoTrial-ReminderBefore12Hour-Customer-Reliance';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
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
		
		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])) {
			$label = 'AutoTrial-ReminderBefore3Hour-Customer-abg';
		}

		$header = $this->multifitKioskOrder($data);
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'AutoTrial-ReminderBefore3Hour-Multifit-Customer';
		}

		if(isset($data['corporate_id']) && $data['corporate_id'] != ''){
			$label = 'AutoTrial-ReminderBefore3Hour-Customer-Reliance';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}


	protected function bookTrialReminderAfter2Hour ($data, $delay){

		$label = 'AutoTrial-ReminderAfter2Hour-Customer';
		\Log::info('inside auto trial remainder after 2 hours for customerss:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::');
		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])) {
			$label = 'AutoTrial-ReminderAfter2Hour-Customer-abg';
		}

		if(isset($data['corporate_id']) && $data['corporate_id'] != ''){
			$label = 'AutoTrial-ReminderAfter2Hour-Customer-Reliance';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}

	protected function bookTrialReminderAfter30Mins ($data, $delay){

		$label = 'AutoTrial-ReminderAfter30Mins-Customer';
		
		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])) {
			$label = 'AutoTrial-ReminderAfter30Mins-Customer-abg';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
	}

	protected function cancelBookTrial ($data){

		Log::info('cancelBookTrial sms: ', [$data]);

		$label = 'Cancel-Trial-Customer';
		
		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])) {
			$label = 'Cancel-Trial-Customer-abg';
		}

		$header = $this->multifitUserHeader();
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'Cancel-Trial-Multifit-Customer';
		}

		if(isset($data['corporate_id']) && $data['corporate_id'] != ''){
			$label = 'Cancel-Trial-Customer-Reliance';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function cancelBookTrialByVendor ($data){

		Log::info('cancelBookTrialByVendor sms: ', [$data]);

		$label = 'CancelTrialByVendor-Customer';

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

		// $header = $this->multifitUserHeader();
		// if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
		// 	$label = 'Order-COD-Multifit-Customer';
		// }
		
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

		$header = $this->multifitKioskOrder($data);

        if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'Order-PG-Multifit-Customer';
		}

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

		if(isset($data['event_type']) && $data['event_type']=='TOI'){
			$label = 'Order-PG-Event-TOI';
			$data['sender'] = 'TOIMFP';
		}
		
		if($data['type'] == "diet_plan"){
			$label = 'Diet-PG-Customer';
		}
		
		if(!empty($data['sub_type']) && $data['sub_type'] ==  "music-run"){
			$label = 'MusicRun-Customer';
		}
		
        if(!empty($data['extended_validity'])){
			$label = 'ExtendedValidityInstant-Customer';

			$header = $this->multifitKioskOrder($data);
			
			if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
				$label = 'ExtendedValidityInstant-Multifit-Customer';
			}
		}
		
		if(!empty($data['type']) && $data['type'] ==  "pass"){
			Log::info('sending pass purchase sms::::::::::::::::::::');
			$label = 'Pass-Purchase-Customer';
			
			if(($data['pass']['pass_type'] =='hybrid') && (empty($data['customer_source']) || $data['customer_source']!='sodexo')){
				$data['pass']['pass_type'] = $data['pass']['branding'];
				if(empty($data['onepass_attachment_type']) || in_array($data['onepass_attachment_type'], ['complementary', 'membership_plus'])){
					return;
				}
			}
		}

		if(!empty($data['combo_pass_id'])){

			$data['pass'] = \Pass::where('pass_id', (int)$data['combo_pass_id'])->first();
			
			if(empty($data['ratecard_flags']['onepass_attachment_type']) || in_array($data['ratecard_flags']['onepass_attachment_type'], ['complementary', 'membership_plus']))
				$label = "Membership-Plus-Hybrid-Pass-Purchase";
			else {
				return;
			}
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

		if($data['type'] != 'events'){
			return "no sms";
		}

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

		$utilities = new Utilities();
		$multifitWebsiteHeader = $utilities->getMultifitWebsiteHeader();
		
		$headreKiosk = $this->multifitUserHeader();
		if($multifitWebsiteHeader == 'multifit' || $headreKiosk == true){
			$label = 'Generic-Otp-Multifit-Customer';
			$data['multifit'] = true;
		}
		
		$to = $data['customer_phone'];

		$data['otp_route'] = true;

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

        $to = $data['customer_phone'];

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

	public function alertmsg($data){

        $label = 'alertmsg';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data);

    }

	protected function orderUpdateCOD($data){

		$label = 'OrderUpdateCOD-Customer';

		$to = $data['customer_phone'];

        return $this->common($label,$to,$data);
	}

	protected function orderUpdatePartPayment($data){

		$label = 'OrderUpdatePartPayment-Customer';

		$to = $data['customer_phone'];

        return $this->common($label,$to,$data);
	}
	
	protected function orderUpdatePartPaymentBefore2Days($data, $delay){
		
		$label = 'OrderUpdatePartPaymentBefore2Days-Customer';
		
		$to = $data['customer_phone'];
		
        return $this->common($label,$to,$data, $delay);
	}
	
	protected function invitePreRegister($data){
		
		$label = "Pre-Regster-Invite-Customer";
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);

	}

	protected function salePreregister($data){
		
		$label = "Pre-Regster-Customer";
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);
	}

	protected function fitcashPreRegister($data){

		$label = "Fitcash-Pre-Regster-Customer";
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);
	}

	public function custom($data){

		$label = 'CustomSms-Customer';

		$to = $data['customer_phone'];

		// $to = '7506026203';

        return $this->common($label,$to,$data);
	}

	public function pledge($data){
		
		$label = 'Pledge-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function sendVendorNumber($data){
		
		$label = 'SendVendorNumber-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function locateTrialReminderAfter1Hour($data){

		$label = 'LocateTrialReminderAfter1Hour-Customer';

		$to = $data['customer_phone'];

        return $this->common($label,$to,$data);
	}

	protected function bookTrialReminderAfter24Hour($data,$delay){

        $label = 'AutoTrial-ReminderAfter24Hour-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function purchaseAfter1Days($data,$delay){

        $label = 'PurchaseAfter1Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function purchaseAfter7Days($data,$delay){

        $label = 'PurchaseAfter7Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function purchaseAfter15Days($data,$delay){

        $label = 'PurchaseAfter15Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function sendRenewalPaymentLinkAfter7Days($data,$delay){

        $label = 'MembershipRenewalLinkSentAfter7Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function sendRenewalPaymentLinkAfter15Days($data,$delay){

        $label = 'MembershipRenewalLinkSentAfter15Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

    }

    protected function sendRenewalPaymentLinkAfter30Days($data,$delay){

        $label = 'MembershipRenewalLinkSentAfter30Days-Customer';

        $to = $data['customer_phone'];

        return $this->common($label,$to,$data,$delay);

	}
	
	public function sendGroupInvite($data){
		
		return "no sms sent";
		
		$label = 'GroupInvite-Customer';
		
		$to = $data['phone'];

		return $this->common($label,$to,$data);
	
	}

	public function addGroupNewMember($data){

		return "no sms sent";
		
		$label = 'AddGroupNewMember-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function addGroupOldMembers($data){
		
		return "no sms sent";
		
		$label = 'AddGroupOldMembers-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
		
	}
	
	
	public function workoutSmsOnFitCodeEnter($data){
		
		
		$label = 'PayPerSession-OnFitCodeEnter';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);
		
	}
	
	public function workoutSmsOnFitCodeEnterPayLater($data){
		
		
		$label = 'PayPerSession-OnFitCodeEnterPayLater';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);
		
	}
	
	protected function bookTrialReminderBefore10Min($data,$delay){
		
		
		$label = 'BookTrialReminderBefore10Min-Customer';
		
		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])) {
			$label = 'BookTrialReminderBefore10Min-Customer-abg';
		}

		$header = $this->multifitKioskOrder($data);
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'BookTrialReminderBefore10Min-Multifit-Customer';
		}

		if(isset($data['corporate_id']) && $data['corporate_id'] != ''){
			$label = 'BookTrialReminderBefore10Min-Customer-Reliance';
		}

		$to = $data['customer_phone'];

		return $this->common($label,$to,$data,$delay);
		
	}

	public function atVendorOrderCaputure($data){
		
		$label = 'AtVendorOrderCaputure-Customer';

		$header = $this->multifitUserHeader();
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'AtVendorOrderCaputure-Multifit-Customer';
		}
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);	
	}
	
	public function ppsReferral($data){
		
		$label = 'PPSReferral-Customer';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);	
	}
	
	protected function offhoursConfirmation($data,$delay){

		if(isset($data['pre_trial_vendor_confirmation']) && !in_array($data['pre_trial_vendor_confirmation'], ['yet_to_connect', ''])){
			return null;
		}
		
		$label = 'OffhoursConfirmation-Customer';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data,$delay);
		
	}

	public function sendPgProductOrderSms($data){
		
		$label = 'SendPgProductOrderMail-Customer';
		
		$to = $data['customer']['customer_phone'];
		
		return $this->common($label,$to,$data);	
	}
	
	public function giftCoupon($data){
		
		$label = 'GiftCoupon-Customer';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);	
	}
	
    public function walletRecharge($data){
		
		$label = 'WalletRecharge-Customer';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);	
	}
	public function inviteEvent($data){
		
		$label = 'InviteEvent-Customer';
		
		$to = $data['invitee']['phone'];
		
		return $this->common($label,$to,$data);	
	}
	
	public function upgradeMembershipInstant($data){
		
		$label = 'UpgradeMembershipInstant-Customer';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);	
	}
    
    public function spinWheelAfterTransaction($data){
		
		$label = 'SpinWheelAfterTransaction-Customer';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);	
	}

	protected function membership100PerCashback($data){

		$label = 'Membership100PerCashback-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	protected function onePass100PerCashback($data){

		$label = 'OnePass100PerCashback-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function multifitUserHeader(){
		$vendor_token = \Request::header('Authorization-Vendor');
		\Log::info('register auth             :: ', [$vendor_token]);
		if($vendor_token){

            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = $decodeKioskVendorToken->vendor;

			$finder_id = $vendor->_id;

			$utilities = new Utilities();

			$allMultifitFinderId = $utilities->multifitFinder(); 
			// $allMultifitFinderId = [9932, 1935, 9304, 9423, 9481, 9954, 10674, 10970, 11021, 11223, 12208, 12209, 13094, 13898, 14102, 14107, 16062, 13968, 15431, 15980, 15775, 16251, 9600, 14622, 14626, 14627];
			\Log::info('register     :: ', [$finder_id]);
			if(in_array($finder_id, $allMultifitFinderId)){
				return true;
			}
		}
		
		return false;
    }
    
    public function multifitKioskOrder($data){
        if(!empty($data['source'])){
            $data["customer_source"] = $data['source'];
        }
        $utilities = new Utilities();
        $allMultifitFinderId = $utilities->multifitFinder(); 
        if(!empty($data['finder_id']) && in_array($data['finder_id'], $allMultifitFinderId) && !empty($data["customer_source"]) && $data["customer_source"] == "kiosk"){
            return true;
        }
	}
	
	public function goldFitcash($data){
		
		
		$label = 'Golds-Fitcash-Customer';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);
		
	}

	public function diwaliMixedReward($data){
		$label = 'DiwaliMixedReward-Customer';
		
		if(!empty($data['customer_source']) && $data['customer_source']=='sodexo'){
			return;
		}

		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);
	}

	public function occasionDaySms($data){
		$label = 'OccasionDaySms-Customer';
		
		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);
	}
	
	public function fitboxMixedReward($data){
		$label = 'FitboxMixedReward-Customer';
		
		if(!empty($data['customer_source']) && $data['customer_source']=='sodexo'){
			return;
		}

		$to = $data['customer_phone'];
		
		return $this->common($label,$to,$data);
	}

	public function externalVoucher($data){

		$label = 'ExternalVoucher-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}
	
	public function common($label,$to,$data,$delay = 0){

		try{
			$ekincareCust = !empty($data['third_party_details']['ekn']);
			if((!empty($data['ratecard_flags']['onepass_attachment_type']) && $data['ratecard_flags']['onepass_attachment_type']=='upgrade') || ($ekincareCust)){
				return;
			}
		} catch(\Exception $e) { }

		if(isset($data['source']) && $data['source'] == 'cleartrip'){
			return "";
		}

		// Log::info('orderDetails: ', $data);

		$template = \Template::where('label',$label)->first();

        $to = array($to);
        
        $header = $this->multifitKioskOrder($data);
        if(!empty($header)){
            $data['multifit'] = true;
        }

		$sender = null;
		if(isset($data['third_party_details']) && isset($data['third_party_details']['abg'])){
			$sender = 'ABCPRO';
			if(Config::get('app.env') == 'stage'){
				$to = ['9920150108','7506262489','9619240452']; //9619240452
			}
		}

		if(Config::get('app.env') != 'stage'){
			if(!empty($data['multifit']) && $label != 'Generic-Otp-Customer'){
				$sender = 'MULTIF';
			}
		}

		if(!empty($data['event_type']) && $data['event_type']=='TOI' && !empty($data['sender'])){
			$sender = $data['sender'];
		}

		$message = $this->bladeCompile($template->sms_text,$data);

		$otp = false;

		if(isset($data['otp_route']) && $data['otp_route']){
			$otp = true;
		}
		return $this->sendToWorker($to, $message, $label, $delay, $otp, $sender);
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
