<?PHP namespace App\Notification;

use Config;

Class CustomerNotification extends Notification{

	public function bookTrialReminderBefore1Min ($data, $delay){

		$to 		=  	trim($data['device_id']);

		if($data['show_location_flag']){
		
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." here http://www.fitternity.com/".$data['finder_slug'].". View the details & get directions. Have a great workout!";
		
		}else{
		
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name'])." here http://www.fitternity.com/".$data['finder_slug'].". View the details & get directions. Have a great workout!";
		}

		return $this->sendTo($to, $message, $delay, 'generic');
	}



	public function bookTrialReminderBefore5Hour ($data, $delay){

		$to 		=  	trim($data['device_id']);

		if($data['show_location_flag']){
		
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." here http://www.fitternity.com/".$data['finder_slug'].". View the details & get directions. Have a great workout!";
		
		}else{
		
			$message 	=	"Hey ".ucwords($data['customer_name']).". Hope you are ready for your session at ".ucwords($data['finder_name'])." here http://www.fitternity.com/".$data['finder_slug'].". View the details & get directions. Have a great workout!";
		}

		return $this->sendTo($to, $message, $delay, 'generic');
	}

	public function bookTrialReminderAfter2Hour ($data, $delay){

		$to 		=  	trim($data['device_id']);

		if($data['show_location_flag']){

			$message 	=	"Hope you had a good experience at your trial session with ".ucwords($data['finder_name']).", ".ucwords($data['finder_location'])." here http://www.fitternity.com/".$data['finder_slug'].". Write a review and stand a chance to win exciting goodies.";
		
		}else{
			
			$message 	=	"Hope you had a good experience at your trial session with ".ucwords($data['finder_name'])." here http://www.fitternity.com/".$data['finder_slug']." Write a review and stand a chance to win exciting goodies.";
		}
		
		return $this->sendTo($to, $message, $delay, 'generic');
	}


}