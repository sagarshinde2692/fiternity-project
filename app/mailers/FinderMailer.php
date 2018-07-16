<?PHP namespace App\Mailers;

use Config,Mail, Log;
use App\Services\Utilities as Utilities;


Class FinderMailer extends Mailer {


	protected function bookTrial ($data){

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

		if(!empty($data['type']) && $data['type'] == 'workout-session'){

			$fitternity_email = [];

			if(isset($data['city_id']) && $data['city_id'] != ""){
				
				switch ($data['city_id']) {
					case 1 : 
						$fitternity_email = [
							'rahulsachdev@fitternity.com',
						 	'kevalshah@fitternity.com',
						 	'mitmehta@fitternity.com',
							'dharatanna@fitternity.com',
							'pranjalisalvi@fitternity.com',
							"rajivharichandani@fitternity.com",
							"allendpenha@fitternity.com",
							"aaqibbora@fitternity.com",
						];
						break;
					case 2 : 
						$fitternity_email = [
							'mitmehta@fitternity.com',
							'vishankkapoor@fitternity.com',
							'dharatanna@fitternity.com',
							'pranjalisalvi@fitternity.com',
							'kevalshah@fitternity.com',
							"rajivharichandani@fitternity.com",
							"allendpenha@fitternity.com",
							"aaqibbora@fitternity.com",
						];
						break;
					case 3 : 
						$fitternity_email = [
							'virenmehta@fitternity.com',
							'silkeshakadam@fitternity.com',
							'priyankamohnish@fitternity.com',
							'dharatanna@fitternity.com',
							'pranjalisalvi@fitternity.com',
							'hardikkhamkar@fitternity.com',
							'ismailbaig@fitternity.com'
						];
						break;
					case 4 : 
					case 8 : 
					case 9 :
						$fitternity_email = [
							'vikramkhanna@fitternity.com',
							'niveditasomani@fitternity.com',
							'arvindraj@fitternity.com',
							'bhavinjani@fitternity.com',
							'priyankapatel@fitternity.com',
							'dharatanna@fitternity.com',
							'pranjalisalvi@fitternity.com',
							'dharmindersingh@fitternity.com'
						];
						break;
					case 6 : 
						$fitternity_email = [
							"priyankapatel@fitternity.com",
							'vikramkhanna@fitternity.com',
							'bhavinjani@fitternity.com',
						];
						break;
					case 5 : 
						$fitternity_email = [
							'silkeshakadam@fitternity.com',
							'priyankamohnish@fitternity.com',
							'hardikkhamkar@fitternity.com',
							'virenmehta@fitternity.com',
							'nishantullal@fitternity.com',
							'zeeshanshaikh@fitternity.com'
						];
						break;
					default:
						break;
				}
			}

			$user_email = array_merge($user_email,$fitternity_email);

			$common = [
				'kinnarijariwala@fitternity.com',
				'siddharthshah@fitternity.com'
			];

			$user_email = array_merge($user_email,$common);
		}
		
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	protected function bookYogaDayTrial ($data){

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

	protected function rescheduledBookTrial ($data){

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

	protected function sendBookTrialDaliySummary ($data){

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

	protected function cancelBookTrial ($data){
		
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

	protected function cancelBookTrialByVendor ($data){

		// return 'no email';

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

	protected function VendorEmailOnProfileEditRequest ($data){

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

	protected function RMEmailOnProfileEditRequest ($data){

		$label = 'RM-email-on-profile-edit-request';

		$user_email = array('pranjalisalvi@fitternity.com','sailismart@fitternity.com','harshitagupta@fitternity.com');

		$message_data 	= array(
			'user_email' => $user_email
		);

		return $this->common($label,$data,$message_data);
	}

	protected function sendPgOrderMail ($data){

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

		$fitternity_email = [];

		if(isset($data['city_id']) && $data['city_id'] != ""){
			
			switch ($data['city_id']) {
				case 1 : 
					$fitternity_email = [
						'rahulsachdev@fitternity.com',
					 	'kevalshah@fitternity.com',
					 	'mitmehta@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com',
						"rajivharichandani@fitternity.com",
						"allendpenha@fitternity.com",
						"aaqibbora@fitternity.com",
					];
					break;
				case 2 : 
					$fitternity_email = [
						'mitmehta@fitternity.com',
						'vishankkapoor@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com',
						'kevalshah@fitternity.com',
						"rajivharichandani@fitternity.com",
						"allendpenha@fitternity.com",
						"aaqibbora@fitternity.com",
					];
					break;
				case 3 : 
					$fitternity_email = [
						'virenmehta@fitternity.com',
						'silkeshakadam@fitternity.com',
						'priyankamohnish@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com',
						'hardikkhamkar@fitternity.com',
						'ismailbaig@fitternity.com'
					];
					break;
				case 4 : 
				case 8 : 
				case 9 :
					$fitternity_email = [
						'vikramkhanna@fitternity.com',
						'niveditasomani@fitternity.com',
						'arvindraj@fitternity.com',
						'bhavinjani@fitternity.com',
						'priyankapatel@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com',
						'dharmindersingh@fitternity.com'
					];
					break;
				case 6 : 
					$fitternity_email = [
						"priyankapatel@fitternity.com",
						'vikramkhanna@fitternity.com',
						'bhavinjani@fitternity.com',
					];
					break;
				case 5 : 
					$fitternity_email = [
						'silkeshakadam@fitternity.com',
						'priyankamohnish@fitternity.com',
						'hardikkhamkar@fitternity.com',
						'virenmehta@fitternity.com',
						'nishantullal@fitternity.com',
						'zeeshanshaikh@fitternity.com'
					];
					break;
				default:
					break;
			}
		}



		$user_email = array_merge($user_email,$fitternity_email);

		$common = [
			'kinnarijariwala@fitternity.com',
			'siddharthshah@fitternity.com'
		];

		$user_email = array_merge($user_email,$common);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	protected function sendCodOrderMail ($data){

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



	protected function healthyTiffinTrial($data){

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

    protected function healthyTiffinTrialReminder($data){

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



    protected function healthyTiffinMembership($data){

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


    protected function sendDaliySummaryHealthyTiffin ($data){

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

    protected function cleartrip ($data){

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

    protected function monsoonSale($data){

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

    protected function firstTrial($data){

        $label = 'First-Autotrial-Fitternity';

        $user_email = array('pranjalisalvi@fitternity.com','vinichellani@fitternity.com','fitternity.suraj@gmail.com');

        $user_name = 'Fitternity Team';

        $message_data 	= array(
            'user_email' => $user_email,
            'user_name' =>  $user_name,
        );

        return $this->common($label,$data,$message_data);

    }

    protected function nutritionStore($data){

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


    protected function acceptVendorMou ($data){
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

    protected function cancelVendorMou ($data){
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

	protected function rewardClaim($data){

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

	protected function manualTrialAuto ($data){

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

	protected function orderUpdatePaymentAtVendor($data){

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

	protected function orderUpdatePartPayment($data){

		$label = 'OrderUpdatePartPayment-Vendor';

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

	protected function orderFailureNotificationToLmd($data){

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
	
	protected function sendNoPrevSalesMail($data){

		$label = 'NoPrevSalesNotification';

		$user_email = array('pranjalisalvi@fitternity.com','vinichellani@fitternity.com','surajshetty@fitternity.com');
		$user_name = "Fitternity Team";

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function sendOrderCorporateMail($data){
		Log::info("OrderCorporateMail-Vendor");
		return;
		$label = 'OrderCorporateMail-Vendor';

		$user_email = array($data['corporate_email']);
		$user_name = $data['corporate_name'];

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);	
	}

	

	protected function orderUpdatePartPaymentBefore2Days($data, $delay){

		$label = 'OrderUpdatePartPaymentBefore2Days-Vendor';

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

		return $this->common($label,$data,$message_data, $delay);
	}

	protected function partPaymentFitternity($data){
		$label = 'PartPaymentPurchase-Fitternity';
		$user_email 	=  	['sailismart@fitternity.com', 'neha@fitternity.com', 'pranjalisalvi@fitternity.com'];
		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  'Fitterntiy',
		);
		return $this->common($label,$data,$message_data);
	}

	protected function claimListing($data){

		$label = 'ClaimListing-Fitternity';

		$data['fitternity_email'] = [
			'dharatanna@fitternity.com',
			'pranjalisalvi@fitternity.com',
			'rahulsachdev@fitternity.com',
			'vikramkhanna@fitternity.com',
			'mitmehta@fitternity.com',
			'kevalshah@fitternity.com',
			'priyankapatel@fitternity.com',
			'silkeshakadam@fitternity.com',
			'ismailbaig@fitternity.com',
			'arvindraj@fitternity.com'
		];

		/*if(isset($data['city_id']) && $data['city_id'] != ""){
			
			switch ($data['city_id']) {
				case 1 : 
					$data['fitternity_email'] = [
						'rahulsachdev@fitternity.com',
					 	'kevalshah@fitternity.com',
					 	'mitmehta@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com'
					];
					break;
				case 2 : 
					$data['fitternity_email'] = [
						'mitmehta@fitternity.com',
						'vishankkapoor@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 3 : 
					$data['fitternity_email'] = [
						'silkeshakadam@fitternity.com',
						'ismailbaig@fitternity.com',
						'prashanthreddy@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 4 : 
				case 8 : 
					$data['fitternity_email'] = [
						'vikramkhanna@fitternity.com',
						'niveditasomani@fitternity.com',
						'arvindraj@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				default:
					break;
			}
		}*/

		$user_email = $data['fitternity_email'];
		$user_name = 'Fitternity Team';

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	protected function addBusiness($data){

		$label = 'AddBusiness-Fitternity';

		$data['fitternity_email'] = [
			'silkeshakadam@fitternity.com',
			'rahulsachdev@fitternity.com',
			'niveditasomani@fitternity.com',
			'dharatanna@fitternity.com',
			'pranjalisalvi@fitternity.com',
			'sailismart@fitternity.com'
		];

		if(isset($data['city_id']) && $data['city_id'] != ""){
			
			switch ($data['city_id']) {
				case 1 : 
					$data['fitternity_email'] = [
						'kevalshah@fitternity.com',
						'mitmehta@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 2 : 
					$data['fitternity_email'] = [
						'mitmehta@fitternity.com',
						'vishankkapoor@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 3 : 
					$data['fitternity_email'] = [
						'silkeshakadam@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 4 : 
				case 8 : 
					$data['fitternity_email'] = [
						'vikramkhanna@fitternity.com',
						'niveditasomani@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				default:
					break;
			}
		}

		$user_email = $data['fitternity_email'];
		$user_name = 'Fitternity Team';

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	public function reportReview ($data){

		$label = 'ReportReview-Fitternity';
		
		$data['fitternity_email'] = [
			'pranjalisalvi@fitternity.com',
			'dharatanna@fitternity.com'
		];

		if(isset($data['city_id']) && $data['city_id'] != ""){

			switch ($data['city_id']) {
				case 1 : 
					$data['fitternity_email'] = [
						'kevalshah@fitternity.com',
						'mitmehta@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 2 : 
					$data['fitternity_email'] = [
						'mitmehta@fitternity.com',
						'vishankkapoor@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 3 : 
					$data['fitternity_email'] = [
						'silkeshakadam@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 4 : 
				case 8 : 
					$data['fitternity_email'] = [
						'vikramkhanna@fitternity.com',
						'niveditasomani@fitternity.com',
						'dharatanna@fitternity.com',
						'pranjalisalvi@fitternity.com' 
					];
					break;
				case 6 : 
					$data['fitternity_email'] = [
						"priyankapatel@fitternity.com",
						'vikramkhanna@fitternity.com',
						'bhavinjani@fitternity.com',
					];
					break;	
				default:
					break;
			}
		}

		$user_email = $data['fitternity_email'];
		$user_name = 'Fitternity Team';

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data);
	}

	protected function kioskTabVendorChange($data){

		$label = 'KioskTabVendorChange-Fitternity';

		$user_email 	=  	['gauravravi@fitternity.com','sailismart@fitternity.com','utkarshmehrotra@fitternity.com','maheshjadhav@fitternity.com','dhruvsarawagi@fitternity.com'];

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  'Fitterntiy Team',
		);

		return $this->common($label,$data,$message_data);
	}

	protected function genericOtp($data){

		$label = 'Generic-Otp-Vendor';

		if(!empty($data['customer_source']) && $data['customer_source'] == 'website'){
			$label = 'Generic-Otp-AtStudio-Vendor';
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

	protected function postTrialStatusUpdate($data, $delay){

		if(isset($data['post_trial_status']) && $data['post_trial_status'] != '' && $data['post_trial_status'] != 'unavailable') {
			return "post_trial_status_set";
		}

		$label = 'PostTrialStatusUpdate-Fitternity';

		$data['fitternity_email'] = [
			"vinichellani@fitternity.com",
			"rohityadav@fitternity.com",
			"karishahuja@fitternity.com",
			"mukeshraheja@fitternity.com",
			"prachimayekar@fitternity.com",
			"pranjalisalvi@fitternity.com",
			"siddharthshah@fitternity.com",
		];

		if(isset($data['city_id']) && $data['city_id'] != ""){

			$cities = ["", "Mumbai", "Pune", "Bangalore", "Delhi", "Hyderabad", "Ahmedabad", "Chennai", "Gurgaon", "Noida", "Faridabad"];

			$data['finder_city'] = isset($cities[$data['city_id']]) ? $cities[$data['city_id']] : "Default City";

			switch ($data['city_id']) {
				case 1 : 
				case 2 : 
					$data['fitternity_email'] = [
						"vinichellani@fitternity.com",
						"rohityadav@fitternity.com",
						"karishahuja@fitternity.com",
						"mukeshraheja@fitternity.com",
						"prachimayekar@fitternity.com",
						"kevalshah@fitternity.com",
						"rajivharichandani@fitternity.com",
						"allendpenha@fitternity.com",
					];
					break;
				case 3 : 
					$data['fitternity_email'] = [
						"silkeshakadam@fitternity.com",
						"priyankamohnish@fitternity.com",
						"hardikkhamkar@fitternity.com",
						"virenmehta@fitternity.com",
						"nishantullal@fitternity.com",
						"vinichellani@fitternity.com",
						"pranjalisalvi@fitternity.com",
						"vinichellani@fitternity.com",
						"rohityadav@fitternity.com",
						"karishahuja@fitternity.com",
						"mukeshraheja@fitternity.com",
						"prachimayekar@fitternity.com",
					];
					break;
				case 4 : 
				case 8 : 
				case 9 : 
				case 10 : 
					$data['fitternity_email'] = [
						"vikramkhanna@fitternity.com",
						"dharmindersingh@fitternity.com",
						"bhavinjani@fitternity.com",
						"pranjalisalvi@fitternity.com ",
						"vinichellani@fitternity.com",
						"rohityadav@fitternity.com",
						"karishahuja@fitternity.com",
						"mukeshraheja@fitternity.com",
						"prachimayekar@fitternity.com",
					];
					break;
				case 5 : 
					$data['fitternity_email'] = [
						"silkeshakadam@fitternity.com",
						"priyankamohnish@fitternity.com",
						"nishantullal@fitternity.com",
						"vinichellani@fitternity.com",
						"rohityadav@fitternity.com",
						"karishahuja@fitternity.com",
						"mukeshraheja@fitternity.com",
						"prachimayekar@fitternity.com",
					];
					break;
				case 6 : 
					$data['fitternity_email'] = [
						"vinichellani@fitternity.com",
						"rohityadav@fitternity.com",
						"karishahuja@fitternity.com",
						"mukeshraheja@fitternity.com",
						"prachimayekar@fitternity.com",
						"pranjalisalvi@fitternity.com",
						"priyankapatel@fitternity.com",
						'vikramkhanna@fitternity.com',
						'bhavinjani@fitternity.com',
					];
					break;
				default:
					break;
			}
		}
		
		$all_city_ids = ['vinichellani@fitternity.com', 'pranjalisalvi@fitternity.com', 'siddharthshah@fitternity.com'];
		
		$data['fitternity_email'] = array_merge($data['fitternity_email'], $all_city_ids);

		$user_email = $data['fitternity_email'];
		$user_name = 'Fitternity Team';

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $user_name,
		);

		return $this->common($label,$data,$message_data, $delay);
	}

	public function trialAlert($data){

		$label = 'TrialAlert-Fitternity';

		$user_email = ["vinichellani@fitternity.com","rohityadav@fitternity.com","karishahuja@fitternity.com","mukeshraheja@fitternity.com","prachimayekar@fitternity.com"];
		
		// $user_email = ["dhruvsarawagi@fitternity.com"];
		$user_name = 'Fitternity Team';

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name']
		);

		return $this->common($label,$data,$message_data);
	}
	
	protected function captureVendorWalkthrough ($data){

		$label = 'Walkthrough-Vendor';

		$user_email 	=  	explode(',', $data['finder_vcc_email']);

		$message_data 	= array(
			'user_email' => $user_email,
			'user_name' =>  $data['finder_name']
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
		
		if(!Config::get('app.vendor_communication')){

			$message_data['user_email'] = array('sailismart@fitternity.com');
		}

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
