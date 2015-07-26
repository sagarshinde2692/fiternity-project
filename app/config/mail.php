<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Mail Driver
	|--------------------------------------------------------------------------
	|
	| Laravel supports both SMTP and PHP's "mail" function as drivers for the
	| sending of e-mail. You may specify which one you're using throughout
	| your application here. By default, Laravel is setup for SMTP mail.
	|
	| Supported: "smtp", "mail", "sendmail"
	|
	*/

	'driver' => 'smtp',

	/*
	|--------------------------------------------------------------------------
	| SMTP Host Address
	|--------------------------------------------------------------------------
	|
	| Here you may provide the host address of the SMTP server used by your
	| applications. A default option is provided that is compatible with
	| the Postmark mail service, which will provide reliable delivery.
	|
	*/


	// 'host' => 'smtp.mailgun.org',
	'host' => 'email-smtp.us-east-1.amazonaws.com',

	/*
	|--------------------------------------------------------------------------
	| SMTP Host Port
	|--------------------------------------------------------------------------
	|
	| This is the SMTP port used by your application to delivery e-mails to
	| users of your application. Like the host we have set this value to
	| stay compatible with the Postmark e-mail application by default.
	|
	*/

	'port' => 587,
	// 'port' => 465,

	/*
	|--------------------------------------------------------------------------
	| Global "From" Address
	|--------------------------------------------------------------------------
	|
	| You may wish for all e-mails sent by your application to be sent from
	| the same address. Here, you may specify a name and address that is
	| used globally for all e-mails that are sent by your application.
	|
	*/

	'from' => array('address' => 'mailus@fitternity.com', 'name' => 'Fitternity'),

	/*
	|--------------------------------------------------------------------------
	| E-Mail Encryption Protocol
	|--------------------------------------------------------------------------
	|
	| Here you may specify the encryption protocol that should be used when
	| the application send e-mail messages. A sensible default using the
	| transport layer security protocol should provide great security.
	|
	*/

	'encryption' => 'tls',

	/*
	|--------------------------------------------------------------------------
	| SMTP Server Username
	|--------------------------------------------------------------------------
	|
	| If your SMTP server requires a username for authentication, you should
	| set it here. This will get used to authenticate with your server on
	| connection. You may also set the "password" value below this one.
	|
	*/

	'username' => 'AKIAIXLA3VSY4HJ4TAKQ',

	/*
	|--------------------------------------------------------------------------
	| SMTP Server Password
	|--------------------------------------------------------------------------
	|
	| Here you may set the password required by your SMTP server to send out
	| messages from your application. This will be given to the server on
	| connection so that the application will be able to send messages.
	|
	*/

	'password' => 'AsIHtNFlhfep4qutkJqMcQcxrb6sYBkCoTlINuZE13U6',

	/*
	|--------------------------------------------------------------------------
	| Sendmail System Path
	|--------------------------------------------------------------------------
	|
	| When using the "sendmail" driver to send e-mails, we will need to know
	| the path to where Sendmail lives on this server. A default path has
	| been provided here, which will work well on most of your systems.
	|
	*/

	'sendmail' => '/usr/sbin/sendmail -bs',

	/*
	|--------------------------------------------------------------------------
	| Mail "Pretend"
	|--------------------------------------------------------------------------
	|
	| When this option is enabled, e-mail will not actually be sent over the
	| web and will instead be written to your application's logs files so
	| you may inspect the message. This is great for local development.
	|
	*/

	'pretend' => false,

	//'cc_emailids' => array('sanjay.id7@gmail.com','neha@fitternity.com','jayamvora@fitternity.com','info@fitternity.com'),
	//'cc_emailids' => array('sanjay.id7@gmail.com','info@fitternity.com'),
	//'cc_emailids' => array('sanjay.id7@gmail.com','info@fitternity.com','mailus@fitternity.com'),
	'cc_emailids' 									=> 	array('sanjay.id7@gmail.com','mailus@fitternity.com'),


	'to_neha'										=> 	'neha@fitternity.com',
	'to_jay'										=> 	'sanjay.id7@gmail.com',
	'to_mailus' 									=> 	'mailus@fitternity.com',
	'bcc_emailds_sanjay' 							=> 	array('sanjay.id7@gmail.com'),
	'bcc_emailds_mailus' 							=> 	array('mailus@fitternity.com'),

	
	'bcc_emailds_review' 							=> 	array('pranjalisalvi@fitternity.com','sailismart@fitternity.com'),

	'bcc_emailds_request_callback' 					=> 	array('info@fitternity.com','mailus@fitternity.com'),
	'bcc_emailds_book_trial' 						=> 	array('info@fitternity.com','mailus@fitternity.com'),
	'bcc_emailds_fitcardbuy' 						=> 	array('info@fitternity.com','mailus@fitternity.com'),
	'bcc_emailds_register_me' 						=> 	array('mailus@fitternity.com'),



	'bcc_emailds_request_callback_landing_page' 	=> 	array('info@fitternity.com','mailus@fitternity.com'),
	'bcc_emailds_book_trial_landing_page' 			=> 	array('info@fitternity.com','mailus@fitternity.com'),
	
	'bcc_emailds_finder_lead_pop'					=> 	array('info@fitternity.com','mailus@fitternity.com'),
	'bcc_emailds_finder_offer_pop'					=> 	array('info@fitternity.com','mailus@fitternity.com'),

	'bcc_emailds_fivefitness_alternative' 			=> 	array('mailus@fitternity.com'),
	'bcc_emailds_fivefitness_refund'	 			=> 	array('mailus@fitternity.com'),


	'bcc_emailds_not_able_to_find' 					=> 	array('info@fitternity.com','mailus@fitternity.com','dharatanna@fitternity.com','jayamvora@fitternity.com'),


	// 'bcc_emailds_autobook_trial' 					=> 	array('sanjay.fitternity@gmail.com'),

	'bcc_emailds_autobook_trial' 					=> 	array('mailus@fitternity.com'),
	'bcc_emailds_finderdailsummary' 				=> 	array('mailus@fitternity.com','pranjalisalvi@fitternity.com','sailismart@fitternity.com'),
	'bcc_forgot_password' 							=> 	array('mailus@fitternity.com','mjmjadhav@gmail.com'),
	'bcc_forgot_password_app' 						=> 	array('ut.mehrotra@gmail.com','mailus@fitternity.com'),
	'bcc_register' 									=> 	array('mailus@fitternity.com'),
	'bcc_emailds_fitmaniasale' 						=> 	array('mailus@fitternity.com','harshitagupta@fitternity.com','pranjalisalvi@fitternity.com','sailismart@fitternity.com','apoorvasharma@fitternity.com','vishwaskharote@fitternity.com'),

);