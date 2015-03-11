<?PHP namespace App\Sms;

use Config;

Class CustomerSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"Hey $data[customer_name]. Your workout session is confirmed for December 2, 2015 (Tuesday), 5.00 PM at $data[finder_name], Andheri (West). 
						Thank you for using Fitternity.com. For any queries call us on +91 92222 21131 or reply to this message.";
		
		return $this->sendTo($to, $message);
	}

	public function bookTrialReminder ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['customer_phone']));
		$message 	=	"reminder customer test sms";
		
		return $this->sendTo($to, $message, $delay);
	}

	

}