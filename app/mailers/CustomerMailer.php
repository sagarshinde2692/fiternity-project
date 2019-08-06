<?PHP namespace App\Mailers;

use Config,Mail;
use App\Services\Utilities as Utilities;

Class CustomerMailer extends Mailer {


	protected function bookTrial ($data){

		\Log::info('bookTrial');

		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && ($data['type'] == "vip_booktrials" || $data['type'] == "vip_booktrials_rewarded" || $data['type'] == "vip_booktrials_invited" )){

			$label = 'VipTrial-Instant-Customer';
		}

		$header = $this->multifitKioskOrder($data);
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'AutoTrial-Instant-Multifit-Customer';
		}

		// return $data;

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);
		Log::info('instant bbooktrial data of customer mailer:::::::::::', [$data]);
		return $this->common($label,$data,$message_data);

	}

	protected function bookYogaDayTrial ($data){

		$label = 'YogaDay-AutoTrial-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}

	protected function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}


	protected function bookTrialReminderBefore12Hour ($data, $delay){
		\Log::info("inside bookTrialReminderBefore12Hour");
		$label = 'AutoTrial-ReminderBefore12Hour-Customer';

		$header = $this->multifitKioskOrder($data);
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'AutoTrial-ReminderBefore12Hour-Multifit-Customer';
		}

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$delay);
	}


	protected function bookTrialReminderAfter2Hour ($data, $delay){

		$label = 'AutoTrial-ReminderAfter2Hour-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$delay);
	}

	protected function manualBookTrial ($data){

		$label = 'ManualTrial-Customer';

		$message_data 	= array(
			'user_email' => array(Config::get('mail.to_mailus')),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}

	protected function manualTrialAuto ($data){

		$label = 'ManualTrialAuto-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}


	protected function sendCodOrderMail ($data){

		$label = 'Order-COD-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function sendPgOrderMail ($data){

		\Log::info('CustomerMailer Order-PG-Customer');

		$label = 'Order-PG-Customer';
		if(!empty($data['type']) && !in_array($data['type'], ['pass'])){
			$utilities = new Utilities();
			$data['loyalty_success_msg'] = $utilities->getLoyaltyAppropriationConsentMsg($data['customer_id'], $data['order_id'], true);	
			\Log::info('loyalty_success_msg :: ', [$data['loyalty_success_msg']]);
		}

		switch ($data['payment_mode']) {
			case 'cod': $label = 'Order-COD-Customer'; break;
			case 'paymentgateway': $label = 'Order-PG-Customer'; break;
			case 'at the studio': $label = 'Order-At-Finder-Customer'; break;
			default: break;
		}

		switch ($data['type']) {
			case 'crossfit-week' : $label = 'Order-PG-Crossfit-Week-Customer';break;
			case 'wonderise' :  $label = 'Order-PG-Wonderise-Customer';break;
			case 'combat-fitness' :  $label = 'Order-PG-Combat-fitness-Customer';break;
			case 'lyfe' :  $label = 'Order-PG-Lyfe-Customer';break;
			case 'mickeymehtaevent' :  $label = 'Order-PG-Mickeymehtaevent-Customer';break;
			case 'events' :  $label = 'Order-PG-Event';break;
			case 'diet_plan' :  $label = 'Diet-PG-Customer';break;
			default: break;
		}

		if(isset($data['event_type']) && $data['event_type']=='TOI'){
			$label = 'Order-PG-Event-TOI';
            $data['via'] = 'mfp';
        }
        
        if(!empty($data['ratecard_flags']['free_sp'])){
            $label = "Free-SP-Customer";
		}
		if(!empty($data['type']) && !in_array($data['type'], ['pass'])){
			$header = $this->multifitKioskOrder($data);
			if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
				switch ($data['payment_mode']) {
					case 'cod': $label = 'Order-COD-Multifit-Customer'; break;
					case 'paymentgateway': $label = 'Order-PG-Multifit-Customer'; break;
					case 'at the studio': $label = 'Order-At-Finder-Multifit-Customer'; break;
					default: break;
				}
			}
		}
		
		if(!empty($data['type']) && ($data['type']=='pass')){
            $label = "Pass-Purchase-Customer";
		}

		$message_data 	= array(
			'user_email' =>explode(",",$data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function forgotPassword ($data){

		$label = 'ForgotPassword-Customer';

		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function forgotPasswordApp ($data){

		$label = 'ForgotPassword-App-Customer';

		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function register($data){
		$label = 'Register-Customer';

		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		$multifitFlag = false;
		$allData = \Input::json()->all();

		if(empty($allData)){
			$allData = \Input::all();
		}
		
		if(!empty($allData['multifit'])){
			$multifitFlag = $allData['multifit'];
		}
		
		$header = $this->multifitUserHeader();
		if($multifitFlag == true || $header == true){
			return;
		}

		return $this->common($label,$data,$message_data);
	}


	protected function buyArsenalMembership ($data){

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

	protected function buyLandingpagePurchase ($data){

		$label = 'Purchase-LandingPage-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}


	protected function cancelBookTrial($data){
		
		$label = 'Cancel-Trial-Customer';

		$header = $this->multifitUserHeader();
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'Cancel-Trial-Multifit-Customer';
		}

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);
		//if(empty($data["communications"]['customer']['mails']) ||(!empty($data["communications"]['customer']['mails']) &&  (in_array($label,$data["communications"]['customer']['mails'])))){
			\Log::info('Cancel-Trial-Customer - data:: ', [$data]);
			if(!empty($data['studio_extended_validity_order_id'])){
				if(!empty($data['studio_sessions'])){
					$avail = $data['studio_sessions']['total_cancel_allowed'] - $data['studio_sessions']['cancelled'];
					$avail = ($avail<0)?0:$avail;
					$data['studio_extended_details'] = [
						'can_cancel' => $avail,
						'total_cancel' => $data['studio_sessions']['total_cancel_allowed']
					];
					$data['app_onelink'] = "https://go.onelink.me/I0CO?pid=studioextcancelmail";
				}	
			}
			return $this->common($label, $data, $message_data);
		// }
		// else if(!empty($data["communications"]['customer']['mails']) &&  !(in_array($label,$data["communications"]['customer']['mails']))){
		// 	return null;
		// }
		//return $this->common($label,$data,$message_data);
	}
	
	protected function cancelBookTrialByVendor($data){

		// return 'no email';

		$label = 'CancelTrialByVendor-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function reviewReplyByVendor($data){

//		print_r($data);
//		return;

		$label = 'review-reply-to-customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function orderAfter10Days($data, $delay){

		$label = 'S+10-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$delay);
	}

	protected function orderRenewalMissedcall($data, $delay){

		$label = 'MembershipRenewal-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$delay);
	}

	protected function inviteEmail($type, $data){

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

		$message_data 	= array(
			'user_email' => array($data['invitee_email']),
			'user_name' => $data['invitee_name']
		);
		return $this->common($label,$data,$message_data);

	}

	protected function respondToInviteEmail($data){

		$label = 'respond-to-invite-for-trial';

		$message_data 	= array(
			'user_email' => array($data['host_email']),
			'user_name' => $data['host_name']
		);

		return $this->common($label,$data,$message_data);
	}


	protected function healthyTiffinTrial($data){

		$label = 'HealthyTiffinTrial-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function healthyTiffinMembership($data){

		$label = 'HealthyTiffinMembership-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function vipReward($data){

		$label = 'VipReward-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function yogaDayPass($data){

		$label = 'YogaDayPass-Instant-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function nutritionStore($data){

		$label = 'NutritionStore-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function landingPageCallback($data){

		$label = 'FitnessCanvas-Customer';

		switch ($data['capture_type']) {
			case 'fitness_canvas': $label = 'FitnessCanvas-Customer';break;
			default:return "no email sms";break;
		}

		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function rewardClaim($data){

		$label = $data['label'];

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function campaignRegisterCustomer($data){

		$label = 'Campaign-Register-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function orderUpdatePaymentAtVendor($data){

		$label = 'OrderUpdatePaymentAtVendor-Customer';

		$header = $this->multifitUserHeader();
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'OrderUpdatePaymentAtVendor-Multifit-Customer';
		}

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function orderUpdateCOD($data){

		$label = 'OrderUpdateCOD-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function orderUpdatePartPayment($data){

		$label = 'OrderUpdatePartPayment-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}
	


	protected function referFriend($data){

		$label = "Refer-friend";

		$message_data 	= array(
			'user_email' => array($data['invitee_email']),
			'user_name' => $data['invitee_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function instantSlotBooking($data){

		$label = 'DietPlan-InstantSlotBooking-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}

	protected function before3HourSlotBooking($data,$delay){

		$label = 'DietPlan-Before3HourSlotBooking-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$dealy);

	}

	protected function dietPlanAfter15DaysReviewSlotConfirm($data){

        $label = 'DietPlan-After15DaysReview-SlotConfirm-Customer';

        $message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

    }

    protected function dietPlanAfter15DaysFollowupSlotConfirm($data){

        $label = 'DietPlan-After15DaysFollowup-SlotConfirm-Customer';

        $message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

    }

	protected function sendDietPgCustomer($data){

        $label = 'Diet-PG-Customer';

        $message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

    }

	protected function orderUpdatePartPaymentBefore2Days($data, $delay){

        $label = 'OrderUpdatePartPaymentBefore2Days-Customer';

        $message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data, $delay);

    }

    protected function linkSentNotSuccess($data){

        $label = 'LinkSentNotSuccess-Customer';

        $message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

    }

    protected function locateTrialReminderAfter1Hour($data){

		$label = 'LocateTrialReminderAfter1Hour-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function registerNoFitcash($data){
		$label = 'RegisterNoFitcash-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		$multifitFlag = false;
		$allData = \Input::json()->all();

		if(empty($allData)){
			$allData = \Input::all();
		}

		if(!empty($allData['multifit'])){
			$multifitFlag = $allData['multifit'];
		}

		if(isset($_GET['multifit'])){
			\Log::info("inside get $$$$$$$$$$");
			$multifitFlag = true;
		}

		$utilities = new Utilities();
		$multifitWebsiteHeader = $utilities->getMultifitWebsiteHeader();
		if($multifitWebsiteHeader == 'multifit'){
			$multifitFlag = true;
		}

		\Log::info(" ++++++++ multifitflag",[$multifitFlag]);
		\Log::info(" ++++++++ multifitflag2",[\Input::get('multifit')]);
		\Log::info(" ++++++++ all data",[\Input::json()->all()]);
		$header = $this->multifitUserHeader();
		if($multifitFlag == true || $header == true){
			return;
		}

		return $this->common($label,$data,$message_data);
	}

	public function registerFitcash($data){
		$label = 'RegisterFitcash-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		$multifitFlag = false;
		$allData = \Input::json()->all();

		if(empty($allData)){
			$allData = \Input::all();
		}

		if(!empty($allData['multifit'])){
			$multifitFlag = $allData['multifit'];
		}

		$header = $this->multifitUserHeader();
		if($multifitFlag == true || $header == true){
			return;
		}

		return $this->common($label,$data,$message_data);
	}

	public function saavn($data){

		$label = 'Saavn-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}
	protected function bookTrialReminderBefore10Min($data,$delay){
		
		\Log::info("workout sessoin before 10 min sms");
		// return "sent";
		$label = 'BookTrialReminderBefore10Min-Customer';

		$header = $this->multifitKioskOrder($data);
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'BookTrialReminderBefore10Min-Multifit-Customer';
		}
		
		$message_data 	= array(
				'user_email' => array($data['customer_email']),
				'user_name' => $data['customer_name']
		);
		
		return $this->common($label,$data,$message_data);
		
	}
	
	protected function workoutSessionInstantWorkoutLevelStart($data){
		
		\Log::info("workout sessoin before 10 min sms");
		// return "sent";
		$label = 'Workout-session_Instant_WorkoutLevelStart';

		$header = $this->multifitKioskOrder($data);
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			return;
		}
		
		$message_data 	= array(
				'user_email' => array($data['customer_email']),
				'user_name' => $data['customer_name']
		);
		
		return $this->common($label,$data,$message_data);
		 
	}

	public function payPerSessionFree($data){

		$label = 'PayPerSessionFree-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function atVendorOrderCaputure($data){

		$label = 'AtVendorOrderCaputure-Customer';
		
		$header = $this->multifitUserHeader();
		if((!empty($data['multifit']) && $data['multifit'] == true) || $header == true){
			$label = 'AtVendorOrderCaputure-Multifit-Customer';
		}

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function routedOrder($data){

		$label = 'RoutedOrder-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	protected function captureCustomerWalkthrough($data){

		$label = 'Walkthrough-Customer';

		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name']
		);

		return $this->common($label,$data,$message_data);
	}
	
	public function externalVoucher($data){

		$label = 'ExternalVoucher-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}
	
	public function sendPgProductOrderMail($data){

		if(empty($data['customer']['customer_email'])){
			Log::info("sendPgProductOrderMail not sent. Email not present");
		}

		$label = 'SendPgProductOrderMail-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['customer']['customer_email']),
			'user_name' => !empty($data['customer']['customer_name']) ? $data['customer']['customer_name'] : '',
            );

		return $this->common($label,$data,$message_data);
	}
    
    public function registerOngoingLoyalty($data){

		$label = 'RegisterOngoingLoyalty-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['email']),
			'user_name' => $data['name'],
            );
		return $this->common($label,$data,$message_data);
	}

    public function exitIntent($data){
		if(empty($data['customer_email'])){
			Log::info("exitIntent not sent. Email not present");
		}
		$label = 'ExitIntent-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => !empty($data['customer_name']) ? $data['customer_name'] : '',
		);
		return $this->common($label,$data,$message_data);
	}
    public function spinTheWheel($data, $delay=0){

		$label = 'SpinTheWheel-Customer';
		
		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => '',
		);
		return $this->common($label,$data,$message_data);
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
        if(in_array($data['finder_id'], $allMultifitFinderId) && !empty($data["customer_source"]) && $data["customer_source"] == "kiosk"){
            return true;
        }
    }
    
    protected function common($label,$data,$message_data,$delay = 0){

		if(isset($data['source']) && $data['source'] == 'cleartrip'){
			return "";
		}

		if(!empty($data['multifit'])){
			$message_data['fromemail'] = 'info@multifit.co.in';
		}

		$template = \Template::where('label',$label)->first();

		$email_template = 	$this->bladeCompile($template->email_text,$data);
		$email_subject = 	$this->bladeCompile($template->email_subject,$data);

		$message_data['bcc_emailids'] = ($template->email_bcc != "") ? array_merge(explode(',', $template->email_bcc),array(Config::get('mail.to_mailus'))) : array(Config::get('mail.to_mailus'));

		$message_data['email_subject'] = $email_subject;
		if(!empty($data['via']) && $data['via']== 'mfp'){
    		return $this->sendDbToWorker('mfp',$email_template, $message_data, $label, $delay);
        }
		return $this->sendDbToWorker('customer',$email_template, $message_data, $label, $delay);

	}

	protected function bladeCompile($value, array $args = array())
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

	protected function directWorker($emaildata){

		\Log::info("inside direct worker");
		// return;

        $email_template_data    =   $emaildata['email_template_data'];

		$template = \Template::where('label',$email_template_data['label'])->first();

		$email_template = 	$this->bladeCompile($template->email_text,$email_template_data);

		$message_data 	= [
			'user_email' => is_array($emaildata['to']) ? $emaildata['to'] : [$emaildata['to']],
			'user_name' => (isset($email_template_data['name'])) ? ucwords($email_template_data['name']) : 'Team Fitternity',
			'bcc_emailids'=> $emaildata['bcc_emailds'],
			'email_subject'=>ucfirst($emaildata['email_subject'])
		];
		$label = "Direct Worker";
		$delay = 0;

		return $this->sendDbToWorker('customer',$email_template, $message_data, $label, $delay);
	}

}
