<?PHP namespace App\Mailers;

use Config,Mail;

Class CustomerMailer extends Mailer {

	public function bookTrial ($data){

		$label = 'AutoTrial-Instant-Customer';

		if(isset($data['type']) && ($data['type'] == "vip_booktrials" || $data['type'] == "vip_booktrials_rewarded" || $data['type'] == "vip_booktrials_invited" )){

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

	public function manualTrialAuto ($data){

		$label = 'ManualTrialAuto-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
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

		switch ($data['payment_mode']) {
			case 'cod': $label = 'Order-COD-Customer'; break;
			case 'paymentgateway': $label = 'Order-PG-Customer'; break;
			case 'at the studio': $label = 'Order-At-Finder-Customer'; break;
			default: break;
		}

		switch ($data['type']) {
			case 'crossfit-week' : $label = 'Order-PG-Crossfit-Week-Customer';
			case 'wonderise' :  $label = 'Order-PG-Wonderise-Customer';
			case 'combat-fitness' :  $label = 'Order-PG-Combat-fitness-Customer';
			case 'lyfe' :  $label = 'Order-PG-Lyfe-Customer';
			case 'mickeymehtaevent' :  $label = 'Order-PG-Mickeymehtaevent-Customer';
			case 'events' :  $label = 'Order-PG-Event';
			default: break;
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
	
	public function cancelBookTrialByVendor($data){

		$label = 'Vendor-trial-cancellation-email-to-customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function reviewReplyByVendor($data){

//		print_r($data);
//		return;

		$label = 'review-reply-to-customer';

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

	public function inviteEmail($type, $data){

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

	public function respondToInviteEmail($data){

		$label = 'respond-to-invite-for-trial';

		$message_data 	= array(
			'user_email' => array($data['host_email']),
			'user_name' => $data['host_name']
		);

		return $this->common($label,$data,$message_data);
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

	public function vipReward($data){

		$label = 'VipReward-Instant-Customer';

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

	public function nutritionStore($data){

		$label = 'NutritionStore-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function landingPageCallback($data){

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

	public function rewardClaim($data){

		$label = $data['label'];

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function campaignRegisterCustomer($data){

		$label = 'Campaign-Register-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function orderUpdatePaymentAtVendor($data){

		$label = 'OrderUpdatePaymentAtVendor-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}

	public function instantSlotBooking($data){

		$label = 'DietPlan-InstantSlotBooking-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}

	public function before3HourSlotBooking($data,$delay){

		$label = 'DietPlan-Before3HourSlotBooking-Customer';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data,$dealy);

	}

	public function dietPlanAfter15DaysReviewSlotConfirm($data){

        $label = 'DietPlan-After15DaysReview-SlotConfirm-Customer';

        $message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

    }

    public function dietPlanAfter15DaysFollowupSlotConfirm($data){

        $label = 'DietPlan-After15DaysFollowup-SlotConfirm-Customer';

        $message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

    }



	public function common($label,$data,$message_data,$delay = 0){

		if(isset($data['source']) && $data['source'] == 'cleartrip'){
			return "";
		}
		
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
