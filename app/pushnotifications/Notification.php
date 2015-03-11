<?PHP namespace App\Notifications;

use Mail;

abstract Class Mailer {

	public function sendTo($email_template, $template_data = [], $message_data = [], $delay = null ){

		if($delay == null){

			Mail::queue($email_template, $template_data, function($message) use ($message_data){

				$message->to($message_data['user_email'], $message_data['user_name'])
				->bcc($message_data['bcc_emailids'])
				->subject($message_data['email_subject']);

			});
			
		}else{

			Mail::later($delay, $email_template, $template_data, function($message) use ($message_data){

				$message->to($message_data['user_email'], $message_data['user_name'])
				->bcc($message_data['bcc_emailids'])
				->subject($message_data['email_subject']);

			});
			
		}

	}


}