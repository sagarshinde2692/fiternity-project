<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$label = 'AutoTrial-Instant-Customer';

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


	public function manualBookTrial ($data){

		$label = 'ManualTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}

	public function sendCodOrderSms ($data){

		$label = 'Order-COD-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);
	}


	public function sendPgOrderSms ($data){

		$label = 'Order-PG-Customer';
		
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

		$current_date = date('Y-m-d 00:00:00');

        $from_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date))));
        $to_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date." + 1 days"))));

		$booktrial  = \Booktrial::where('_id','!=',(int) $data['_id'])->where('customer_phone','LIKE','%'.substr($data['customer_phone'], -8).'%')->where('missedcall_batch','exists',true)->where('created_at','>',$from_date)->where('created_at','<',$to_date)->orderBy('_id','desc')->first();
		if(!empty($booktrial) && isset($booktrial->missedcall_batch) && $booktrial->missedcall_batch != ''){
			$batch = $booktrial->missedcall_batch + 1;
		}else{
			$batch = 1;
		}

		$missedcall_no = \Ozonetelmissedcallno::where('batch',$batch)->get()->toArray();

		if(empty($missedcall_no)){

			$missedcall_no = \Ozonetelmissedcallno::where('batch',1)->get()->toArray();
		}

		foreach ($missedcall_no as $key => $value) {

			switch ($value['type']) {
				case 'yes': $yes = $value['number'];break;
				case 'no': $no = $value['number'];break;
				case 'reschedule': $reschedule = $value['number'];break;
			}

		}
	
		$slot_date 			=	date('d-m-Y', strtotime($data['schedule_date']));
		$datetime 			=	strtoupper($slot_date ." ".$data['schedule_slot_start_time']);

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message = "Hi ".$data['customer_name']." This is regarding your session with ".ucwords($data['finder_name'])." - ".ucwords($data['finder_location'])." on ".$datetime.". Do you plan to attend? Reply by missed call. Yes will go: ".$yes." , No - cancel it: ".$no." , Want to reschedule: ".$reschedule. ", Regards - Team Fitternity";

		$label = 'MissedCall-C';
		$priority = 0;

		$booktrial = \Booktrial::find((int) $data['_id']);
		$booktrial->missedcall_batch = $batch;
		$booktrial->update();

		return $this->sendToWorker($to, $message, $label, $delay);
	}

	public function confirmTrial($data){

		$label = 'Missedcall-ConfirmTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function cancelTrial($data){

		$label = 'Missedcall-CancelTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function rescheduleTrial($data){

		$label = 'Missedcall-RescheduleTrial-Customer';
		
		$to = $data['customer_phone'];

		return $this->common($label,$to,$data);

	}

	public function common($label,$to,$data,$delay = 0){

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