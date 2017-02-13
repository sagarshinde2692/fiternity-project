<?PHP namespace App\Sms;

use Config;

Class FinderSms extends VersionNextSms{

	public function instantSlotBooking ($data){

		$to = explode(',', $data['trainer_mobile']);

		$label = 'DietPlan-InstantSlotBooking-Trainer';

		return $this->common($label,$to,$data);
	}

	public function before3HourSlotBooking ($data){

		$to = explode(',', $data['trainer_mobile']);

		$label = 'DietPlan-Before3HourSlotBooking-Trainer';

		return $this->common($label,$to,$data);
	}

	public function common($label,$to,$data,$delay = 0){

		$template = \Template::where('label',$label)->first();

		$message = $this->bladeCompile($template->sms_text,$data);

		//$to = array('8976167917');
		
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
