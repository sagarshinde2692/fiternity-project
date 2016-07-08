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

	public function sendPgOrderMail ($data){

		$label = 'Order-PG-Vendor';

		if($data['type'] == 'crossfit-week'){

			$label = 'Order-PG-Crossfit-Week-Vendor';
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


	public function common($label,$data,$message_data,$delay = 0){

		$template = \Template::where('label',$label)->first();

		$email_template = 	$this->bladeCompile($template->email_text,$data);
		$email_subject = 	$this->bladeCompile($template->email_subject,$data);

		//$message_data['user_email'] = array('maheshjadhav@fitternity.com');

		$message_data['bcc_emailids'] = ($template->email_bcc != "") ? array_merge(explode(',', $template->email_bcc),array(Config::get('mail.to_mailus'))) : array(Config::get('mail.to_mailus'));

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
