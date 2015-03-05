<?PHP namespace Acme\Mailers;
use Mail;

abstract Class Mailer {

	public function sendTo($email_template, $template_data = [], $message_data = [] ){

		Mail::queue($email_template, $template_data, function($message) use ($message_data){

			$message->to($message_data['user_email'], $message_data['user_name'])
					->bcc($message_data['bcc_emailids'])
					->subject($message_data['email_subject']);

		});

	}

}