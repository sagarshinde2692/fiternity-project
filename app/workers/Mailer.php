<?php

require __DIR__ . '/../../bootstrap/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/start.php';
$app->setRequestForConsoleEnvironment();
$app->boot();

$payload = getPayload();

fire($payload);

function fire($payload)
{

	$email_template = $payload->email_template;
	$template_data = (array)$payload->template_data;
	$message_data = (array)$payload->message_data;

	try {

		Mail::send($email_template, $template_data, function($message) use ($message_data){
			$message->to($message_data['user_email'], $message_data['user_name'])
			->bcc(array_merge( ['sanjay.id7@gmail.com'], $message_data['bcc_emailids']))
			->subject($message_data['email_subject']);
		});

		$return = 'Mail sent to '.$message_data['user_email'];

	}catch(Swift_RfcComplianceException $exception){
		
		$return = Log::error($exception);
	}   

	echo $return; 

}