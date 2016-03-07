<?PHP namespace App\Mailers;

use Config,Mail;

Class FinderMailer extends Mailer {

	public function welcome(){
		//$email_template, $template_data = [], $message_data = [] ;

	}

	public function bookTrial ($data){

		// $email_template = 'emails.test';
		$email_template = 'emails.finder.autobooktrial';
		$template_data 	= $data;

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$bcc_emailids = array(Config::get('mail.to_mailus'));

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Session request from customer '.ucwords($data['customer_name']).' at '.ucwords($data['finder_name']).' has been confirmed | Fitternity'
		);

		$label = 'BookTrial-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);
	}

	public function rescheduledBookTrial ($data){

		$email_template = 'emails.finder.rescheduledautobooktrial';
		$template_data 	= $data;

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		} 

		$user_name = ucwords($data['finder_name']);

		$bcc_emailids = array(Config::get('mail.to_mailus'));

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Reschedule request from customer '.ucwords($data['customer_name']).' for a session | Fitternity'
		);

		$label = 'RescheduledTrial-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);
	}

	public function sendBookTrialDaliySummary ($data){

		$email_template = 'emails.finder.booktrialdailysummary';
		$template_data 	= $data;

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		} 

		$user_name = ucwords($data['finder_name_base_locationtags']);

		$bcc_emailids = Config::get('mail.bcc_emailds_finderdailsummary');

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Daily report on customers who have booked sessions for tomorrow with '.ucwords($data['finder_name_base_locationtags'])
		);
		// echo "<pre>";print_r($data);exit;

		$label = 'TrialDaliySummary-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);

	}


	public function buyServiceThroughFitmania ($data){

		$email_template 	= 	'emails.order.fitmania_offer_vendor';
		$template_data 		= 	$data;

		if(isset($data['finder_vcc_email'] ) && $data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 

		$subject  			=   'FitMania Offer availed on Fitternity - '.ucwords($data['customer_name']).' purchased membership for '.ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => ucwords($data['finder_name']),
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuyServiceFitmania-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);

	}


	public function buyServiceThroughFitmaniaWorngCustomer ($data){

		$email_template 	= 	'emails.order.fitmania_offer_vendor_wrong_customer';
		$template_data 		= 	$data;

		if(isset($data['finder_vcc_email'] ) && $data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 

		$subject  			=   'Please ignore previous mail - Regarding sale of membership on FitMania';

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => ucwords($data['finder_name']),
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuySrvFitmaniaWrongCustomer-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);

	}



	public function resendFinderGroupBy ($to, $name, $location, $data){

		if($to != ''){		
			$bcc_emailids  =  	(!is_array($to)) ? array_merge( explode(',', $to), Config::get('mail.bcc_emailds_fitmaniasale') ) : array_merge($to, Config::get('mail.bcc_emailds_fitmaniasale'));
			// $bcc_emailids 	=  	array_merge(explode(',', $to),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 

		$email_template_customer 	= 	'emails.order.fitmania_offer_vendor_groupby1';
		$template_data 				= 	$data;
		// $bcc_emailids 				= 	Config::get('mail.bcc_emailds_fitmaniasale');
		$subject  					=   'Summary of sales on FitMania 2016 by Fitternity';

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			// 'user_email' => 'sanjaysahu@fitternity.com',
			'user_name' => ucwords($name),
			'bcc_emailids' => $bcc_emailids,
			// 'bcc_emailids' => ['sanjaysahu@fitternity.com'],
			'email_subject' => $subject
			);

		$label = 'BuySrvFitmaniaGroupbyFinder-F';
		$priority = 1;
		
		return $this->sendToWorker('vendor',$email_template_customer, $template_data, $message_data, $label, $priority);

	}


	public function emailToFitmaniaVendors ($to, $name, $data){

		if($to != ''){		
			$bcc_emailids  =  	(!is_array($to)) ? array_merge( explode(',', $to), Config::get('mail.bcc_emailds_fitmaniasale') ) : array_merge($to, Config::get('mail.bcc_emailds_fitmaniasale'));
			// $bcc_emailids 	=  	array_merge(explode(',', $to),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 

		$email_template_customer 	= 	'emails.order.fitmania_offer_vendor_groupby2';
		$template_data 				= 	$data;
		// $bcc_emailids 				= 	Config::get('mail.bcc_emailds_fitmaniasale');
		$subject  					=   'FitMania 2016 and other important updates from Fitternity I A note from CEO';

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			// 'user_email' => 'sanjaysahu@fitternity.com',
			'user_name' => ucwords($name),
			'bcc_emailids' => $bcc_emailids,
			// 'bcc_emailids' => ['sanjaysahu@fitternity.com'],
			'email_subject' => $subject
			);

		$label = 'BuySrvFitmaniaGroupbyFinder-F';
		$priority = 1;
		
		return $this->sendToWorker('vendor',$email_template_customer, $template_data, $message_data, $label, $priority);

	}

	public function buyServiceMembershipThroughFitmania ($data){

		$email_template 	= 	'emails.order.fitmania_membership_vendor_v1';
		$template_data 				= 	$data;

		if(isset($data['finder_vcc_email'] ) && $data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 
		
		// $bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');

		$subject  					=   'FitMania Sale by Fitternity - Membership purchase request for '.ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => ucwords($data['finder_name']),
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuySrvMbrFitM-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);

	}

	public function buyServiceHealthyTiffinThroughFitmania ($data){

		$email_template 	= 	'emails.order.fitmania_healthytiffin_vendor_v1';
		$template_data 				= 	$data;

		if(isset($data['finder_vcc_email'] ) && $data['finder_vcc_email'] != ''){
			$bcc_emailids 	=  	array_merge(explode(',', $data['finder_vcc_email']),Config::get('mail.bcc_emailds_fitmaniasale'));
		}else{
			$bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');
		} 
		
		// $bcc_emailids 	= 	Config::get('mail.bcc_emailds_fitmaniasale');

		$subject  					=   'FitMania Sale by Fitternity - Order confirmation for '.ucwords($data['finder_name']);

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => ucwords($data['finder_name']),
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'BuySrvHltTifFitM-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);

	}


	public function resendFitmaniaFinderEmail ($data){

		$email_template 	= 	'emails.finder.fitmaniafinder_consolidated';
		$template_data 				= 	$data;
		// $bcc_emailids 				= 	Config::get('mail.bcc_emailds_fitmaniasale');
		// $bcc_emailids 				= 	[$data['finder_vcc_email']];
		$bcc_emailids 				=  	array_merge(explode(',', $data['finder_vcc_email']));
		$subject  					=   'Confirmation of orders received through FitMania Sale on Fitternity.com (till August 3, 2015)';

		$message_data 	= array(
			'user_email' => Config::get('mail.to_mailus'),
			'user_name' => 'Fitternity',
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
			);

		$label = 'ResendFitmania-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);

	}

	public function cancelBookTrial ($data){
		
		$email_template = 'emails.finder.cancelbooktrial';
		$template_data 	= $data;

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$bcc_emailids = array(Config::get('mail.to_mailus')); 

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => 'Cancellation of session booked by customer '.ucwords($data['customer_name']).' | Fitternity'
		);

		$label = 'CancelTrial-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);
	}

	public function sendPgOrderMail ($data){

		$email_template 	= 	'emails.order.pg_'.strtolower($data['type']).'_vendor';
		$template_data 				= 	$data;
		$subject = "";

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$bcc_emailids = Config::get('mail.bcc_emailds_purchase');
		if($data['type'] == 'memberships'){
			$subject  = 'Confirmation of purchase '. ucwords($data['service_name'])." ". ucwords($data['service_duration']). ' for '.ucwords($data['finder_name']).' '.ucwords($data['finder_location']).' on Fitternity: '.ucwords($data['customer_name']);
		}
		if($data['type'] == 'womens-day'){
			$subject  = "Women's Day Offer availed on Fitternity - ".ucwords($data['customer_name'])." purchased membership for ".ucwords($data['finder_name']);
		}
		
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
		);

		$label = 'SendPgOrder-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);
	}

	public function sendCodOrderMail ($data){

		$email_template 	= 	'emails.order.cod_memberships_vendor';
		$template_data 				= 	$data;

		if($data['finder_vcc_email'] != ''){
			$user_email 	=  	explode(',', $data['finder_vcc_email']);
		}else{
			$user_email 	= 	array(Config::get('mail.to_mailus'));
		}

		$user_name = ucwords($data['finder_name']);

		$bcc_emailids = Config::get('mail.bcc_emailds_purchase');

		$subject  = 'Confirmation of purchase '. ucwords($data['service_name'])." ". ucwords($data['service_duration']). ' for '.ucwords($data['finder_name']).' '.ucwords($data['finder_location']).' on Fitternity: '.ucwords($data['customer_name']);
		
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $subject
		);

		$label = 'SendPgOrder-F';

		return $this->sendToWorker('vendor',$email_template, $template_data, $message_data, $label);
	}

}