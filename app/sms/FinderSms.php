<?PHP namespace App\Sms;

use Config;

Class FinderSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));
		$message 	=	"finder test sms";
		
		return $this->sendTo($to, $message);
	}

	public function bookTrialReminder ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));
		$message 	=	"reminder finder test sms";
		
		return $this->sendTo($to, $message, $delay);
	}

	

}