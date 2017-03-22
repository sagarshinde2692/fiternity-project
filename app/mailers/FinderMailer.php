<?PHP namespace App\Mailers;

use Config,Mail;

Class FinderMailer extends Mailer {


	public function bookTrial ($data){

		$label = 'AutoTrial-Instant-Vendor';

		if(isset($data['type']) && ($data['type'] == "vip_booktrials" || $data['type'] == "vip_booktrials_rewarded" || $data['type'] == "vip_booktrials_invited" )){

			$label = 'VipTrial-Instant-Vendor';
		}

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);
		
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function bookYogaDayTrial ($data){

		$label = 'YogaDay-AutoTrial-Instant-Vendor';

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function rescheduledBookTrial ($data){

		$label = 'RescheduleTrial-Instant-Vendor';
		
		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function sendBookTrialDaliySummary ($data){

		$label = 'BookTrialDaliySummary-Vendor';
		
		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);

	}

	public function cancelBookTrial ($data){
		
		$label = 'Cancel-Trial-Vendor';
		
		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function cancelBookTrialByVendor ($data){

		$label = 'Vendor-trial-cancellation-email-to-vendor';

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function VendorEmailOnProfileEditRequest ($data){

		$label = 'Vendor-email-on-profile-edit-request';

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

//		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
//			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function RMEmailOnProfileEditRequest ($data){

		$label = 'RM-email-on-profile-edit-request';

		$user_email = array('pranjalisalvi@fitternity.com','sailismart@fitternity.com','harshitagupta@fitternity.com');

		$message_data 	= array(
			'user_email' => $user_email
		);

		return $this->common($label,$data,$message_data);
	}

	public function sendPgOrderMail ($data){

		$label = 'Order-PG-Vendor';

		switch ($data['payment_mode']) {
			case 'cod': $label = 'Order-COD-Vendor'; break;
			case 'paymentgateway': $label = 'Order-PG-Vendor'; break;
			case 'at the studio': $label = 'Order-At-Finder-Vendor'; break;
			default: break;
		}

		switch ($data['type']) {
			case 'crossfit-week' : $label = 'Order-PG-Crossfit-Week-Vendor';
			default: break;
		}

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function sendCodOrderMail ($data){

		$label = 'Order-COD-Vendor';
		
		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}



	public function healthyTiffinTrial($data){

		$label = 'HealthyTiffinTrial-Instant-Vendor';

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

    public function healthyTiffinTrialReminder($data){

        $label = 'HealthyTiffinTrial-Reminder-Vendor';

        if($data['finder_vcc_email'] != ''){
            $user_email 	=  	explode(',', $data['finder_vcc_email']);
        }else{
            $user_email 	= 	array(Config::get('mail.to_mailus'));
        }

        $user_name = ucwords($data['finder_name']);

        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );

        return $this->common($label,$data,$message_data);
    }



    public function healthyTiffinMembership($data){

		$label = 'HealthyTiffinMembership-Instant-Vendor';

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}


    public function sendDaliySummaryHealthyTiffin ($data){

        $label = 'BookTriaMembershiplDaliySummary-HealthyTiffinVendor';

        if($data['finder_vcc_email'] != ''){
            $user_email 	=  	explode(',', $data['finder_vcc_email']);
        }else{
            $user_email 	= 	array(Config::get('mail.to_mailus'));
        }

        $user_name = ucwords($data['finder_name']);

        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );

        return $this->common($label,$data,$message_data);

    }

    public function cleartrip ($data){

        $label = 'Cleartrip-Vendor';

        if($data['finder_vcc_email'] != ''){
            $user_email 	=  	explode(',', $data['finder_vcc_email']);
        }else{
            $user_email 	= 	array(Config::get('mail.to_mailus'));
        }

        $user_name = ucwords($data['title']);

        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );

        return $this->common($label,$data,$message_data);

    }

    public function monsoonSale($data){

        $label = 'MonsoonSale-Vendor';

        if($data['finder_vcc_email'] != ''){
            $user_email 	=  	explode(',', $data['finder_vcc_email']);
        }else{
            $user_email 	= 	array(Config::get('mail.to_mailus'));
        }

        $user_name = ucwords($data['title']);

        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );

        return $this->common($label,$data,$message_data);

    }

    public function firstTrial($data){

        $label = 'First-Autotrial-Fitternity';

        $user_email = array('pranjalisalvi@fitternity.com','vinichellani@fitternity.com','fitternity.suraj@gmail.com');

        $user_name = 'Fitternity Team';

        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );

        return $this->common($label,$data,$message_data);

    }

    public function nutritionStore($data){

        $label = 'NutritionStore-Vendor';

        if($data['finder_vcc_email'] != ''){
            $user_email 	=  	explode(',', $data['finder_vcc_email']);
        }else{
            $user_email 	= 	array(Config::get('mail.to_mailus'));
        }

        $user_name = ucwords($data['finder_name']);

        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );

        return $this->common($label,$data,$message_data);

    }


    public function acceptVendorMou ($data){
        $label = 'AcceptVendorMou-Paid-Cash-Cheque-Vendor';
        if($data['contract_type'] == 'premium'){
            $label = 'AcceptVendorMou-Cos-Vendor';
        }elseif(($data['contract_type'] == 'platinum' || $data['contract_type'] == 'launch plan' ) && $data['payment_mode'] == 'online'){
            $label = 'AcceptVendorMou-Paid-Online-Vendor';
        }
        // var_dump($label);exit();

        if($data['rm_email'] != ''){
            $user_email 	=  	[$data['rm_email']];
        }else{
            $user_email 	= 	array(Config::get('mail.to_mailus'));
        }
        $user_name = ucwords($data['rm_name']);
        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );
        return $this->common($label,$data,$message_data);
    }

    public function cancelVendorMou ($data){
        $label = 'CancelVendorMou-Vendor';
        if($data['rm_email'] != ''){
            $user_email 	=  	[$data['rm_email']];
        }else{
            $user_email 	= 	array(Config::get('mail.to_mailus'));
        }
        $user_name = ucwords($data['rm_name']);
        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );
        return $this->common($label,$data,$message_data);
    }

	public function rewardClaim($data){

        $label = $data['label'];
        
        if($data['finder_vcc_email'] != ''){
            $user_email 	=  	explode(',', $data['finder_vcc_email']);
        }else{
            $user_email 	= 	array(Config::get('mail.to_mailus'));
        }

        $user_name = ucwords($data['title']);

        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );

        return $this->common($label,$data,$message_data);

    }

	public function manualTrialAuto ($data){

		$label = 'ManualTrialAuto-Finder';

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);

	}

	public function orderUpdatePaymentAtVendor($data){

		$label = 'OrderUpdatePaymentAtVendor-Vendor';

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function orderFailureNotificationToLmd($data){

		$label = 'OrderFailureNotification-LMD';

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}
	
	public function sendNoPrevSalesMail($data){

		$label = 'NoPrevSalesNotification';

		$user_email = array('pranjalisalvi@fitternity.com','vinichellani@fitternity.com','fitternity.suraj@gmail.com');
		$user_name = "Fitternity Team";

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function common($label,$data,$message_data,$delay = 0){
		// return($message_data['user_email']);
		if(in_array(Config::get('mail.to_mailus'),$message_data['user_email'])){
			$delay = 0;
			$data['label'] = $label;
			$data['user_name'] = $message_data['user_name'];
			$label = 'EmailFailureNotification-LMD';
			$message_data['user_email'] = array('vinichellani@fitternity.com');
		}

		$template = \Template::where('label',$label)->first();

		$email_template = 	$this->bladeCompile($template->email_text,$data);
		$email_subject = 	$this->bladeCompile($template->email_subject,$data);

		$message_data['user_email'] = array('utkarshmehrotra@fitternity.com','pranjalisalvi@fitternity.com','sailismart@fitternity.com');


		// $message_data['bcc_emailids'] = ($template->email_bcc != "") ? array_merge(explode(',', $template->email_bcc),array(Config::get('mail.to_mailus'))) : array(Config::get('mail.to_mailus'));

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
