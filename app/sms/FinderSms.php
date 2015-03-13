<?PHP namespace App\Sms;

use Config;

Class FinderSms extends VersionNextSms{

	public function bookTrial ($data){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));
		$message 	=	"We have received a workout / trial session request from ".ucwords($data['customer_name'])." for ".ucwords($data['finder_name']).", ".ucwords($data['finder_location']).". The slot has been confirmed for ".date(' jS\, Y \(l\) ', strtotime($data['schedule_date_time']) ) .", ".date(' g\.i A', strtotime($data['schedule_date_time']) ) .". Please call us on +91 92222 21131 for queries. Regards - Team Fitternity.";
		
		return $this->sendTo($to, $message);
	}

	//currently not using reminder
	public function bookTrialReminder ($data, $delay){

		$to 		=  	array_merge(explode(',', $data['finder_vcc_mobile']));
		$message 	=	"reminder finder test sms";
		
		return $this->sendTo($to, $message, $delay);
	}

	

}