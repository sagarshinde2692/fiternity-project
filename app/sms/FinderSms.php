<?PHP namespace App\Sms;

use Config;
use App\Services\Utilities as Utilities;

Class FinderSms extends VersionNextSms{

	protected function bookTrial ($data){

		\Log::info('FinderSms bookTrial');

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'AutoTrial-Instant-Vendor';

		if(isset($data['type']) && ($data['type'] == "vip_booktrials" || $data['type'] == "vip_booktrials_rewarded" || $data['type'] == "vip_booktrials_invited" )){

			$label = 'VipTrial-Instant-Vendor';
		}

		return $this->common($label,$to,$data);
	}


	protected function rescheduledBookTrial ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'RescheduleTrial-Instant-Vendor';

		return $this->common($label,$to,$data);
	}


	protected function bookTrialReminderBefore1Hour ($data, $delay){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'AutoTrial-ReminderBefore1Hour-Vendor';

		return $this->common($label,$to,$data,$delay);
	}

	protected function bookTrialReminderBefore6Hour ($data, $delay){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'AutoTrial-ReminderBefore6Hour-Vendor';

		return $this->common($label,$to,$data,$delay);
	}


	protected function cancelBookTrial ($data){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'Cancel-Trial-Vendor';

		return $this->common($label,$to,$data);
	}

	protected function cancelBookTrialByVendor ($data){

		// return 'no sms';

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'Cancel-Trial-Vendor';

		return $this->common($label,$to,$data);
	}

	protected function sendPgOrderSms ($data){

		\Log::info('FinderSms Order-PG-Vendor');

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'Order-PG-Vendor';

		if($data['type'] == 'crossfit-week'){

			$label = 'Order-PG-Crossfit-Week-Vendor';
		}

		return $this->common($label,$to,$data);
	}

	protected function buyLandingpagePurchaseEefashrof ($data, $count){

		$to 		=  	['9730401839','9773348762'];

		$label = 'Purchase-LandingPage-Eefashrof';

		return $this->common($label,$to,$data);
	}

	protected function confirmTrial($data){

		$label = 'AutoTrial-ReminderBefore20Min-Confirm-Vendor';
		
		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

	}

	protected function cancelTrial($data){

		$label = 'Missedcall-Reply-N-3-CancelTrial-Vendor';
		
		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

	}

	protected function rescheduleTrial($data){

		$label = 'Missedcall-Reply-N-3-RescheduleTrial-Vendor';
		
		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

	}

	protected function healthyTiffinTrial($data){

		$label = 'HealthyTiffinTrial-Instant-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);

	}

    protected function healthyTiffinMembership($data){

        $label = 'HealthyTiffinMembership-Instant-Vendor';

        $to = explode(',', $data['finder_vcc_mobile']);

        return $this->common($label,$to,$data);

    }

    protected function bookYogaDayTrial ($data){

		$label = 'YogaDay-AutoTrial-Instant-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

        return $this->common($label,$to,$data);
	}

	protected function firstTrial($data){

		$label = 'First-Autotrial-Fitternity';

		$to = array('9930206022','8976167917','9867812126');

        return $this->common($label,$to,$data);
	}

	protected function nutritionStore ($data){

		$label = 'NutritionStore-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

        return $this->common($label,$to,$data);
	}

	protected function manualTrialAuto ($data){

		$label = 'ManualTrialAuto-Finder';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}

	protected function reminderToConfirmManualTrial ($data,$delay){
	
		$label = 'Reminder-To-Confirm-ManualTrial-Finder';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data,$delay);
	}

	protected function changeStartDate ($data){
	
		$label = 'ChangeStartDate-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}

	protected function bookTrialReminderBefore20Min ($data){
	
		$label = 'AutoTrial-ReminderBefore20Min-Confirm-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}

	protected function ozonetelCapture($data){

       	$label = 'OzonetelCapture-Vendor';
       
       	$to = explode(',', $data['finder_vcc_mobile']);

       	//$to = array_merge($to,['7506262489']);

		return $this->common($label,$to,$data);

   	}

	protected function bookTrialReminderAfter2Hour ($data, $delay){

		$to = explode(',', $data['finder_vcc_mobile']);

		$label = 'AutoTrial-ReminderAfter2Hour-Vendor';

		return $this->common($label,$to,$data,$delay);
	}

	

	protected function orderUpdatePartPaymentBefore2Days ($data, $delay){

		$label = 'OrderUpdatePartPaymentBefore2Days-Vendor';
		
		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data,$delay);
	}

	protected function orderUpdatePartPayment ($data){

		$label = 'OrderUpdatePartPayment-Vendor';
		
		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}

	protected function genericOtp ($data){

		$label = 'Generic-Otp-Vendor';
		if(!empty($data['customer_source']) && $data['customer_source'] == 'website'){
			$label = 'Generic-Otp-AtStudio-Vendor';
		}

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}

	protected function trialAlert($data){

        return null;

		$label = 'TrialAlert-Fitternity';

		$to = array('9004556289','9833201020','8976457756','9833023772');

        return $this->common($label,$to,$data);
	}

	protected function captureVendorWalkthrough ($data){
	
		$label = 'Walkthrough-Vendor';

		$to = explode(',', $data['finder_vcc_mobile']);

		return $this->common($label,$to,$data);
	}
	
	protected function brandVendorEmpty ($data){
	
		$label = 'BrandVendorEmpty-FItternity';

		$to = ['7506026203','9730401839'];

		return $this->common($label,$to,$data);
	}
	
    protected function apicrashlogsSMS ($data){
	
		$label = 'ApicrashlogsSMS-Customer';

		$to = [
            '7506026203',
            '9730401839',
            '9824313243'
         ];

		return $this->common($label,$to,$data);
	}

	public function common($label,$to,$data,$delay = 0){

		try{
			if(!empty($data['ratecard_flags']['onepass_attachment_type']) && $data['ratecard_flags']['onepass_attachment_type']=='upgrade'){
				return;
			}
		} catch(\Exception $e) { }

		$template = \Template::where('label',$label)->first();

		$message = $this->bladeCompile($template->sms_text,$data);

		// if($label=="ClockDayVendor"){
		// 	$to = array('9619240452', '9920150108'); //Nilesh's number ('9096794779') added, later changed (Hemant called) - Aditya Birla
		// }
		// else if(!empty($data['third_party_details'])){

		if(!Config::get('app.vendor_communication') && !empty($data['third_party_details']['ekn']) && $label=='ClockDayVendor') {
			$to = ['9755979216', '9619240452'];
        }
		else if(!Config::get('app.vendor_communication') && !empty($data['third_party_details'])){
			$to = array('9619240452');
		}
		else if(!Config::get('app.vendor_communication')){

			//$to = array('7506026203','9619240452');
			$to = array('9022594823','9619240452');
		}
		
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
