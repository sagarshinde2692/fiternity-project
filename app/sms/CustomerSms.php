<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"customer test sms";
		
		return $this->sendTo($to, $message);
	}

	public function bookTrialReminder ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"reminder customer test sms";
		
		return $this->sendTo($to, $message, $delay);
	}

	

}