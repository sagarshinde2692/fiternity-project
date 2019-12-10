<?php


return array(

    //local
    'new_search_url' =>'http://apistage.fitn.in:5000/',
	'url' => 'http://fitapi.com/',
	'admin_url' => 'http://fitadmin.com',
	'website' => 'https://www.fitternity.com',
	'sidekiq_url' => 'http://kick.fitn.in/',
	'queue' => 'booktrial',
	'vendor_communication' => false,
	'env' => 'stage',
	'debug' => TRUE,
	'metropolis' => 'http://localhost:3030',
	'amazonpay_isSandbox' => 'true',
	'reliance_url' =>'http://rhc-portal.agileloyalty.net/fitternity/callback',
	'website_deeplink' =>'https://ftrnty.com',
	'mobikwik_sandbox'=>true,
	'paytm_sandbox'=>true,
	'paypal_sandbox'=>true,
	'ffTransactionAPI'=>'http://ffstaging.fitnessforce.com/WebPurchase/Webtransaction.aspx?source=',
	'ffEnquiryAPI'=>'http://ffstagingapi.fitnessforce.com/prospect',
	'razorpay' => [ 'key_id' => 'rzp_live_irBeGznBeqpZia', 'secret_key' => 'bMblNmPddzIIP4vbyoKYLhwR', 'currency' => 'INR', 'customer' => ['url' => 'https://api.razorpay.com/v1/customers'], 'plan' => ['interval' => 1, 'period' => 'monthly', 'url' => 'https://api.razorpay.com/v1/plans'], 'subscription' => ['total_count' => 60, 'url' => 'https://api.razorpay.com/v1/subscriptions', 'interval' => '+30 days'], "webhook_secret_key"=>"qwepoifghtyvb" ],
    "pps_url_branch_io" => "https://ftrnty.test-app.link/9g4PJcq2WW",
    'uploadStepsStage'=>'http://localhost:5000/fitness-steps-stage/asia-east2/',

	//stage
	// 'new_search_url' =>'http://apistage.fitn.in:5000/',
	// 'url' => 'http://apistage.fitn.in',
	// 'admin_url' => 'http://adminstage.fitn.in',
	// 'website' => 'http://apistage.fitn.in:1133',
	// 'sidekiq_url' => 'http://kick.fitn.in/',
	// 'queue' => 'booktrial',
	// 'vendor_communication' => false,
	// 'env' => 'stage',
	// 'debug' => TRUE,
	// 'metropolis' => 'http://apisatge.fitn.in:8989',
	// 'amazonpay_isSandbox' => 'true',
	// 'reliance_url' =>'http://rhc-portal.agileloyalty.net/fitternity/callback',
	// 'website_deeplink' =>'https://ftrnty.com',
	// 'mobikwik_sandbox'=>true,
	// 'paytm_sandbox'=>true,
	// 'paypal_sandbox'=>true,
	// 'ffTransactionAPI'=>'http://ffstaging.fitnessforce.com/WebPurchase/Webtransaction.aspx?source=',
	// 'ffEnquiryAPI'=>'http://ffstagingapi.fitnessforce.com/prospect',
	// 'razorpay' => [ 'key_id' => 'rzp_test_6TKjLnXzpuVIds', 'secret_key' => 'rK7FwR1a4coHHLCuhSSLz8P5', 'currency' => 'INR', 'customer' => ['url' => 'https://api.razorpay.com/v1/customers'], 'plan' => ['interval' => 1, 'period' => 'monthly', 'url' => 'https://api.razorpay.com/v1/plans'], 'subscription' => ['total_count' => 60, 'url' => 'https://api.razorpay.com/v1/subscriptions', 'interval' => '+30 days'], "webhook_secret_key"=>"qwepoifghtyvb" ],
	// 'razorPayKey' => 'rzp_test_6TKjLnXzpuVIds',
	// 'razorPaySecret' => 'rK7FwR1a4coHHLCuhSSLz8P5',
	// "webhook_secret_key"=>"qwepoifghtyvb",
	// "pps_url_branch_io" => "https://ftrnty.test-app.link/9g4PJcq2WW",
	// 'uploadStepsStage'=>'https://asia-east2-fitness-steps-stage.cloudfunctions.net/',
	// "corporate_mapping" => [
	// 	[ "id" => "123456789123421", "key" => "wfuefiesieuwfuururuuiwri", "name" => "Goqii", "acronym" => "goqii", "dummy_email_domain" => "@goqii.com" ],
	// 	[ "id" => "111332255567802", "key" => "wfuefiesekincareuuvfiwri", "name" => "Ekincare", "acronym" => "ekn", "dummy_email_domain" => "@ekincare.com" ],
	// 	[ "id" => "236348957967467", "key" => "wdsdfiavtruworthvuvfsdgs", "name" => "Truworth", "acronym" => "twh", "dummy_email_domain" => "@truworth.com" ]
	// ],

    //beta
	// 'new_search_url' =>'http://apistage.fitn.in:5000/',
	// 'url' => 'http://beta.fitn.in',
	// 'admin_url' => 'http://adminstage.fitn.in',
	// 'website' => 'http://apistage.fitn.in:1133',
	// 'sidekiq_url' => 'http://kick.fitn.in/',
	// 'queue' => 'booktrial',
	// 'vendor_communication' => false,
	// 'env' => 'stage',
	// 'debug' => TRUE,
	// 'metropolis' => 'http://apisatge.fitn.in:8989',
	// 'amazonpay_isSandbox' => 'true',
	// 'reliance_url' =>'http://rhc-portal.agileloyalty.net/fitternity/callback',
	// 'website_deeplink' =>'https://ftrnty.com',
	// 'mobikwik_sandbox'=>true,
	// 'paytm_sandbox'=>true,
	// 'paypal_sandbox'=>true,
	// 'ffTransactionAPI'=>'http://ffstaging.fitnessforce.com/WebPurchase/Webtransaction.aspx?source=',
	// 'ffEnquiryAPI'=>'http://ffstagingapi.fitnessforce.com/prospect',
	// 'razorpay' => [ 'key_id' => 'rzp_test_6TKjLnXzpuVIds', 'secret_key' => 'rK7FwR1a4coHHLCuhSSLz8P5', 'currency' => 'INR', 'customer' => ['url' => 'https://api.razorpay.com/v1/customers'], 'plan' => ['interval' => 1, 'period' => 'monthly', 'url' => 'https://api.razorpay.com/v1/plans'], 'subscription' => ['total_count' => 60, 'url' => 'https://api.razorpay.com/v1/subscriptions', 'interval' => '+30 days'], "webhook_secret_key"=>"qwepoifghtyvb" ],
	// 'razorPayKey' => 'rzp_test_6TKjLnXzpuVIds',
	// 'razorPaySecret' => 'rK7FwR1a4coHHLCuhSSLz8P5',
	// "webhook_secret_key"=>"qwepoifghtyvb",
	// "pps_url_branch_io" => "https://ftrnty.test-app.link/9g4PJcq2WW",
	// 'uploadStepsStage'=>'https://asia-east2-fitness-steps-stage.cloudfunctions.net/',

	//live
	// 'new_search_url' =>'http://c1.fitternity.com/',	
	// 'url' => 'https://a1.fitternity.com',
	// 'admin_url' => 'https://fitn.in',
    // 'website' => 'https://www.fitternity.com',
    // 'multifit_website' => 'http://www.multifitgym.com',
	// 'sidekiq_url' => 'http://nw.fitn.in/',
	// 'queue' => 'booktrial',
	// 'vendor_communication' => true,
	// 'env' => 'production',
	// 'debug' => false,
	// 'metropolis' => 'https://c1.fitternity.com',
	// 'amazonpay_isSandbox' => 'false',
	// 'reliance_url' =>'https://rhealthcircle.reliancegeneral.co.in/fitternity/callback',
	// 'website_deeplink' =>'https://ftrnty.com',
	// 'mobikwik_sandbox'=>false,
	// 'paytm_sandbox'=>false,
	// 'paypal_sandbox'=>false,
	// 'ffTransactionAPI'=>'https://demo.fitnessforce.com/WebPurchase/Webtransaction.aspx?source=',
	// 'ffEnquiryAPI'=>'http://api.fitnessforce.com/prospect',
	// 'uploadStepsStage'=>'https://asia-east2-fitness-steps-live.cloudfunctions.net/',
    // 'razorpay' => [ 'key_id' => 'rzp_live_irBeGznBeqpZia', 'secret_key' => 'bMblNmPddzIIP4vbyoKYLhwR', 'currency' => 'INR', 'customer' => ['url' => 'https://api.razorpay.com/v1/customers'], 'plan' => ['interval' => 1, 'period' => 'monthly', 'url' => 'https://api.razorpay.com/v1/plans'], 'subscription' => ['total_count' => 60, 'url' => 'https://api.razorpay.com/v1/subscriptions', 'interval' => '+30 days'], "webhook_secret_key"=>"qwepoifghtyvb" ],
	// "corporate_mapping" => [
	//     [ "id" => "555123098567", "key" => "sfgvdhfjekincarevdfggjfc", "name" => "Ekincare", "acronym" => "ekn", "dummy_email_domain" => "@ekincare.com" ]
	// ],

	"app_download_url_branch_io" => "https://ftrnty.app.link/8v6VWNGwi0",
    'creditMap' => [
		['max_price'=>300, 'credits'=>2],
		['max_price'=>500, 'credits'=>3],
		['max_price'=>749, 'credits'=>4]
	],

	'pass_payment_options' => [
		[
			'title' => 'Paypal',
			'subtitle' => 'Get 50% Instant Cashback Upto INR 700 (New Users Only)',
			'value' => 'paypal'
		],
		[
				'title' => 'Paytm',
				// 'subtitle' => 'Paytm',
				'value' => 'paytm'
		],
		// [
		// 		'title' => 'AmazonPay',
		// 		// 'subtitle' => 'AmazonPay',
		// 		'value' => 'amazonpay'
		// ],
		[
				'title' => 'Mobikwik',
				// 'subtitle' => 'Mobikwik',
				'value' => 'mobikwik'
		],
        [
                'title' => 'PayU',
                // 'subtitle' => 'PayU',
                'value' => 'payu'
        ]
    ],

	'pass_payment_options_wallets_test' => [
		[
			'title' => 'Paypal',
			'subtitle' => 'Get 50% Instant Cashback Upto INR 700 (New Users Only)',
			'value' => 'paypal'
		],
		[
				'title' => 'Paytm',
				// 'subtitle' => 'Paytm',
				'value' => 'paytm'
		],
		// [
		// 		'title' => 'AmazonPay',
		// 		// 'subtitle' => 'AmazonPay',
		// 		'value' => 'amazonpay'
		// ],
		[
				'title' => 'Mobikwik',
				// 'subtitle' => 'Mobikwik',
				'value' => 'mobikwik'
		],
		[
				'title' => 'PayU',
				// 'subtitle' => 'PayU',
				'value' => 'payu'
		]
	],

	'razorPayURL' =>'https://api.razorpay.com/v1/plans',
	
	'ffDetails' => [
		'source' => 'fitternity',
		'paymentmode' => 'fitternity'
    ],
	
	"reliance_coupon_code" => "RELIANCE",

    "pps_image"=> 'https://b.fitn.in/global/fit-exclusive-new-14-7-2019.png',    
    'fitsquad_upgrade_api'=> '/customer/loyaltyAppropriation',
	'fitsquad_cancel_api'=>'/customer/remaincurrentloyalty',
    "core_key"=> "FITITRNTY",
	'non_peak_hours' => ["off"=>0.6,"non_peak_title1"=>"Look for this sign to book a slot for ", "non_peak_title"=>"NON RUSH HOUR (40% OFF)", "gym"=>["off"=>0.6,"start"=>10,"end"=>18],"studios"=>["start"=>11,"end"=>17,"off"=>0.6]],
    'product_delivery_charges' => 50,
	'pubnub_publish' => 'pub-c-d9aafff8-bb9e-42a0-a24b-17ab5500036f',
	'pubnub_sub' => 'sub-c-05ef3130-d0e6-11e6-bbe2-02ee2ddab7fe',
	'download_app_link' => 'https://go.onelink.me/I0CO?pid=techfitsms',//https://www.fitternity.com/downloadapp?source=fittech',

    'business' => 'http://business.fitternity.com',
	'static_coupon' => array(
		array("code" => "fivefit", "text" => "Get Flat 5% off | Limited Memberships Available | Hurry! Code: FIVEFIT","discount_max" => 10000,"discount_amount" => 0,"discount_min" => 0, "discount_percent"=> 5, "once_per_user"=> true, "ratecard_type"=> ["membership","healthytiffinmembership"]),
		array("code" => "lucky300", "text" => "3 Lucky Members Get Flat Rs.300 off | Hurry! Use Code: LUCKY300","discount_max" => 300,"discount_amount" => 300,"discount_min" => 300, "once_per_user"=> true, "ratecard_type"=> ["membership","healthytiffinmembership"]),
		array("code" => "lucky300", "text" => "3 Lucky Members Get Flat Rs.300 off | Hurry! Use Code: LUCKY300","discount_max" => 300,"discount_amount" => 300,"discount_min" => 300, "once_per_user"=> true, "ratecard_type"=> ["membership","healthytiffinmembership"]),
		array("code" => "starfit", "text" => "Experience Luxury with A Flat 5% off Membership Purchase | NO T&C | Code: STARFIT","discount_max" => 10000,"discount_amount" => 0,"discount_min" => 0, "discount_percent"=> 5, "once_per_user"=> true, "ratecard_type"=> ["membership","healthytiffinmembership"]),
	),
    'app' =>array(
		'discount'		=> 			0,
		'discount_excluded_vendors' => [9459,1484,1458,576,1647,1486,9883,1451,1452,401,1487,1457,2522,1488,1460,4830,4829,4831,4827,4821,4818],
	),
	'fitternity_vendors' => [11907,9869,9891,11128,12064],
	'vendors_without_convenience_fee' => [15082,13965,7116,9149,12008,1860,6593,14085,14081,13761,13765,14079, 2890,3175,3178,3179,3183,3201,3204,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,5964,7081,7106,7111,7114,7116,8872],
	'payu' => array(
		'prod'=>array(
			"key" => 'l80gyM',
			"salt" => 'QBl78dtK',
			"url" => "https://info.payu.in/merchant/postservice.php?form=2"
		),
		'test'=>array(
			"key" => 'gtKFFx',
			"salt" => 'eCwWELxi',
			"url" => "https://test.payu.in/merchant/postservice.php?form=2"
			)
		),

		/*
		|--------------------------------------------------------------------------
		| Application Timezone
		|--------------------------------------------------------------------------
		|
		| Here you may specify the default timezone for your application, which
		| will be used by the PHP date and date-time functions. We have gone
		| ahead and set this to a sensible default for you out of the box.
		|
		*/
		
		//'timezone' => 'UTC',
		//'timezone' => 'America/New_York',
		'timezone' => 'Asia/Kolkata',
		
		/*
		|--------------------------------------------------------------------------
		| Application Locale Configuration
		|--------------------------------------------------------------------------
		|
		| The application locale determines the default locale that will be used
		| by the translation service provider. You are free to set this value
		| to any of the locales which will be supported by the application.
		|
		*/
		
		'locale' => 'en',
		
		/*
		|--------------------------------------------------------------------------
		| Application Fallback Locale
		|--------------------------------------------------------------------------
		|
		| The fallback locale determines the locale to use when the current one
		| is not available. You may change the value to correspond to any of
		| the language folders that are provided through your application.
		|
		*/
		
		'fallback_locale' => 'en',
		
		/*
	|--------------------------------------------------------------------------
	| Encryption Key
	|--------------------------------------------------------------------------
	|
	| This key is used by the Illuminate encrypter service and should be set
	| to a random, 32 character string, otherwise these encrypted strings
	| will not be safe. Please do this before deploying an application!
	|
	*/
	'key' => 'llUcLdw8v9nl6kEzYDW5uwGRRRJkpIOV',

	'cipher' => MCRYPT_RIJNDAEL_128,

	/*
	|--------------------------------------------------------------------------
	| Autoloaded Service Providers
	|--------------------------------------------------------------------------
	|
	| The service providers listed here will be automatically loaded on the
	| request to your application. Feel free to add your own services to
	| this array to grant expanded functionality to your applications.
	|
	*/

	'providers' => array(

		'Illuminate\Foundation\Providers\ArtisanServiceProvider',
		'Illuminate\Auth\AuthServiceProvider',
		'Illuminate\Cache\CacheServiceProvider',
		'Illuminate\Session\CommandsServiceProvider',
		'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
		'Illuminate\Routing\ControllerServiceProvider',
		'Illuminate\Cookie\CookieServiceProvider',
		'Illuminate\Database\DatabaseServiceProvider',
		'Illuminate\Encryption\EncryptionServiceProvider',
		'Illuminate\Filesystem\FilesystemServiceProvider',
		'Illuminate\Hashing\HashServiceProvider',
		'Illuminate\Html\HtmlServiceProvider',
		'Illuminate\Log\LogServiceProvider',
		'Illuminate\Mail\MailServiceProvider',
		'Illuminate\Database\MigrationServiceProvider',
		'Illuminate\Pagination\PaginationServiceProvider',
		'Illuminate\Queue\QueueServiceProvider',
		'Illuminate\Redis\RedisServiceProvider',
		'Illuminate\Remote\RemoteServiceProvider',
		'Illuminate\Auth\Reminders\ReminderServiceProvider',
		'Illuminate\Database\SeedServiceProvider',
		'Illuminate\Session\SessionServiceProvider',
		'Illuminate\Translation\TranslationServiceProvider',
		'Illuminate\Validation\ValidationServiceProvider',
		'Illuminate\View\ViewServiceProvider',
		'Illuminate\Workbench\WorkbenchServiceProvider',
		'Jenssegers\Mongodb\MongodbServiceProvider',
		'Shift31\LaravelElasticsearch\LaravelElasticsearchServiceProvider',
		'Hugofirth\Mailchimp\MailchimpServiceProvider',
		'Davibennun\LaravelPushNotification\LaravelPushNotificationServiceProvider',
		'Indatus\Dispatcher\ServiceProvider',
		//'Aloha\Twilio\TwilioServiceProvider',
		'Hernandev\HipchatLaravel\HipchatLaravelServiceProvider',
		'Aws\Laravel\AwsServiceProvider',
		'SimpleSoftwareIO\QrCode\QrCodeServiceProvider'
	),

	/*
	|--------------------------------------------------------------------------
	| Service Provider Manifest
	|--------------------------------------------------------------------------
	|
	| The service provider manifest is used by Laravel to lazy load service
	| providers which are not needed for each request, as well to keep a
	| list of all of the services. Here, you may set its storage spot.
	|
	*/

	'manifest' => storage_path().'/meta',

	/*
	|--------------------------------------------------------------------------
	| Class Aliases
	|--------------------------------------------------------------------------
	|
	| This array of class aliases will be registered when this application
	| is started. However, feel free to register as many as you wish as
	| the aliases are "lazy" loaded so they don't hinder performance.
	|
	*/

	'aliases' => array(

		'App'               => 'Illuminate\Support\Facades\App',
		'Artisan'           => 'Illuminate\Support\Facades\Artisan',
		'Auth'              => 'Illuminate\Support\Facades\Auth',
		'Blade'             => 'Illuminate\Support\Facades\Blade',
		'Cache'             => 'Illuminate\Support\Facades\Cache',
		'Carbon' 			=> 'Carbon\Carbon',
		'ClassLoader'       => 'Illuminate\Support\ClassLoader',
		'Config'            => 'Illuminate\Support\Facades\Config',
		'Controller'        => 'Illuminate\Routing\Controller',
		'Cookie'            => 'Illuminate\Support\Facades\Cookie',
		'Crypt'             => 'Illuminate\Support\Facades\Crypt',
		'DB'                => 'Illuminate\Support\Facades\DB',
		'Eloquent'          => 'Illuminate\Database\Eloquent\Model',
		'Event'             => 'Illuminate\Support\Facades\Event',
		'File'              => 'Illuminate\Support\Facades\File',
		'Form'              => 'Illuminate\Support\Facades\Form',
		'Hash'              => 'Illuminate\Support\Facades\Hash',
		'HTML'              => 'Illuminate\Support\Facades\HTML',
		'Input'             => 'Illuminate\Support\Facades\Input',
		'Lang'              => 'Illuminate\Support\Facades\Lang',
		'Log'               => 'Illuminate\Support\Facades\Log',
		'Mail'              => 'Illuminate\Support\Facades\Mail',
		'Paginator'         => 'Illuminate\Support\Facades\Paginator',
		'Password'          => 'Illuminate\Support\Facades\Password',
		'Queue'             => 'Illuminate\Support\Facades\Queue',
		'Redirect'          => 'Illuminate\Support\Facades\Redirect',
		'RedisL4'             => 'Illuminate\Support\Facades\Redis',
		'Request'           => 'Illuminate\Support\Facades\Request',
		'Response'          => 'Illuminate\Support\Facades\Response',
		'Route'             => 'Illuminate\Support\Facades\Route',
		'Schema'            => 'Illuminate\Support\Facades\Schema',
		'Seeder'            => 'Illuminate\Database\Seeder',
		'Session'           => 'Illuminate\Support\Facades\Session',
		'SoftDeletingTrait' => 'Illuminate\Database\Eloquent\SoftDeletingTrait',
		'SSH'               => 'Illuminate\Support\Facades\SSH',
		'Str'               => 'Illuminate\Support\Str',
		'URL'               => 'Illuminate\Support\Facades\URL',
		'Validator'         => 'Illuminate\Support\Facades\Validator',
		'View'              => 'Illuminate\Support\Facades\View',
		'Moloquent'       	=> 'Jenssegers\Mongodb\Model',
		'MailchimpWrapper'  => 'Hugofirth\Mailchimp\Facades\MailchimpWrapper',
		'PushNotification' 	=> 'Davibennun\LaravelPushNotification\Facades\PushNotification',
		//'Twilio' 			=> 'Aloha\Twilio\Facades\Twilio',
		'HipChat'         	=> 'Hernandev\HipchatLaravel\Facade\HipChat',
		'AWS' 				=> 'Aws\Laravel\AwsFacade',
		'DbEvent' 			=> 'App\Models\Event',
		'QrCode' 			=> 'SimpleSoftwareIO\QrCode\Facades\QrCode'
	),

	'cachetime' 					=> 	1440,
	'perpage' 						=> 	50,

	's3_finder_url'					=> 'https://d3oorwrq3wx4ad.cloudfront.net/f/',
    's3_service_url'				=> 'https://d3oorwrq3wx4ad.cloudfront.net/s/',
	's3_bane_url'				=> 'https://d3oorwrq3wx4ad.cloudfront.net/',

//
//	'elasticsearch_port' 			=> 	9200,
//	'elasticsearch_host_new' 		=> 	'ESAdmin:fitternity2020@54.169.120.141',
//	'elasticsearch_port_new'        => 8050,
//
//	// 'elasticsearch_host_new' 		=> 	'localhost',
//	// 'elasticsearch_port_new'        => 9200,
//
//	//old
//	'elasticsearch_host' 			=> 	'54.179.134.14',
//	'elasticsearch_port' 			=> 	9200,
//	'elasticsearch_default_index' 	=> 	'fitternity',
//	'elasticsearch_default_type' 	=> 	'finder',


	/******************************ElasticSearch Config****************/
	//currently used only for vip trials rolling builds and search api.
	//will be implemented everywhere in future when other api will be changed
	/*************************************************************************/
	//Production - Old
	// 'es' =>array(
	// 	'url'		=> 			'ESAdmin:fitternity2020@54.169.120.141:8050',
	// 	'host'		=> 			'ESAdmin:fitternity2020@54.169.120.141',
	// 	'port'		=>			8050,
	// 	'default_index' => 	'fitternity',
	// 	'default_type' 	=> 	'finder',
	// ),

	// Production
	'es' =>array(
		'url'		=> 			'ESAdmin:fitternity2020@15.206.111.50:8050',
		'host'		=> 			'ESAdmin:fitternity2020@15.206.111.50',
		'port'		=>			8050,
		'default_index' => 	'fitternity',
		'default_type' 	=> 	'finder',
	),

	//stage
	// 'es' =>array(
	//  	'url'		=> 			'139.59.16.74:1243',
	//  	'host'		=> 			'139.59.16.74',
	//  	'port'		=>			1243,
	//  	'default_index' => 	'fitternity',
	//  	'default_type' 	=> 	'finder',
	// ),
	//local
	// 'es' =>array(
	// 	'url'		=> 			'localhost:9200',
	// 	'host'		=> 			'localhost',
	// 	'port'		=>			9200,
	// 	'default_index' => 	'fitternity',
	// 	'default_type' 	=> 	'finder',
	// ),
//	'es' =>array(
//		'url'		=> 			'localhost:9200',
//		'host'		=> 			'localhost',
//		'port'		=>			9200,
//		'default_index' => 	'fitternity',
//		'default_type' 	=> 	'finder',
//	),


	// 'es_host'		=> 			'localhost',
	// 'es_port'		=>			9200,

	/***************************ElasticSearch Config*******************/




	'jwt' => array(
		'key' => 'fitternity', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+(86400*365), // time when token gets expired (1 year)
		'alg' => 'HS256',
	),

	'forgot_password' => array(
		'key' => 'fitternity', //secret key to encode token
		'iat' => time(), // time when token is created
		'exp' => time()+(86400*365), // time when token gets expired (1 day)
		'alg' => 'HS256',
	),

	'aws' => array(
		'key' 								=> 'AKIATSZWJ7JFA747DICW',
		'secret' 							=> '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',
		'region' 							=> 'ap-southeast-1',
		'bucket'							=> 'b.fitn.in',
		'ozonetel' =>array(
			'path'							=> 'ozonetel/',
			'url'							=> 'http://b.fitn.in/ozonetel/',
		),
		'qrcode' =>array(
			'path'							=> 'qrcode/',
			'url'							=> 'http://b.fitn.in/qrcode/',
		),
		'review_images'=>array(
			'path'							=> 'review-images/',
			'url'							=> 'https://d3oorwrq3wx4ad.cloudfront.net/review-images/',
		),
		'detail_ratings_images'=>array(
			'path'							=> 'paypersession/ratings/',
			'url'							=> 'http://d3oorwrq3wx4ad.cloudfront.net/paypersession/ratings/',
		),
        'membership_receipt'=>array(
            'path'							=> 'membership-receipts/',
            'url'							=> 'http://d3oorwrq3wx4ad.cloudfront.net/membership-receipts/',
        ),
        'customer_photo'=>array(
            'path'							=> 'customer_photo/',
            'url'							=> 'http://d3oorwrq3wx4ad.cloudfront.net/customer_photo/',
        ),
	),

	'customer_care_number' => '+919699998838',
	'contact_us_vendor_email' => 'business@fitternity.com',
	'contact_us_customer_email' => 'support@fitternity.com',
	'contact_us_customer_email_onepass' => 'onepass@fitternity.com',
	'contact_us_vendor_number' => '+919699998838',
	'contact_us_customer_number' => '+912261094444',
	'contact_us_customer_number_pps' => "+918879886083",
	'contact_us_customer_number_onepass' => '+917400062849',
	'display_contact_us_customer_number_onepass' => '+91 74000 62849',
	'followup_fitness_concierge' => 'Rachel',
	'followup_customer_number' => '+917400062843',
	'renewal_fitness_concierge' => 'David',
	'renewal_customer_number' => '+917400062844',
	'purchase_fitness_concierge' => 'David',
	'purchase_customer_number' => '+917400062844',
	'order_missed_call_no' => '+912230148070',
	'confirm_order_customer_number' => '+917400062841',
	'renewal_linksent_fitness_concierge' => 'David',
	'renewal_linksent_customer_number' => '+917400062844',
	'not_interested_fitness_concierge' => 'David',
	'not_interested_customer_number' => '+917400062844',
	'direct_customer_number' => '+917400062841',
	'cancel_trial_missed_call_vendor' => '+917400062841',
	'n-3_customer_number' => '+917400062841',
	'n-3_confirm_customer_number' => '+917400062846',
	'n+2_feedback_customer_number' => '+917400062845',
	'diet_plan_customer_email' => 'nutrition@fitternity.com',
	'diet_plan_customer_number' => '+917400062841',
	'diet_plan_trainer_email' => 'nutrition@fitternity.com',
	'diet_plan_trainer_number' => '+917400062841',
	'direct_vendor_number' => '+919699998838',
	'direct_ozonetel_vendor_number' => '+919699998838',
	'direct_ozonetel_customer_number' => '+919867592381',
	'sooraj_number'=>'+919699998838',
	
	'contact_us_customer_email_multifit' => 'info@multifit.co.in',
	'contact_us_customer_number_multifit' => '020 67473400',

	's3_finderurl'  => array(
		'cover' 			=> 'https://b.fitn.in/f/c/',
		'cover_thumb' 		=> 'https://b.fitn.in/f/ct/',
		'gallery' 			=> 'https://b.fitn.in/f/g/',
		'gallery_thumb' 	=> 'https://b.fitn.in/f/gt/',
	),

	's3_articleurl'  => array(
		'cover' 			=> 'https://b.fitn.in/articles/covers/',
		'cover_thumb' 	=> 'https://b.fitn.in/articles/thumbs/',
	),

	's3_customer_transformation_path'					=> 'c/t/',

	'vip_trial_types' => array(
		'vip_booktrials','vip_booktrials_rewarded','vip_booktrials_invited','vip_3days_booktrials'
	),
	'trial_types' => array(
		'vip_booktrials','vip_booktrials_rewarded','vip_booktrials_invited','vip_3days_booktrials',
		'booktrials','3daystrial','healthytiffintrail'
	),
	'membership_types' => array('memberships','fitmaniadealsofday','fitmaniaservice','arsenalmembership','zumbathon','booiaka','zumbaclub','fitmania-dod','fitmania-dow','fitmania-membership-giveaways','womens-day','eefashrof','crossfit-week','workout-session','wonderise','lyfe','healthytiffinmembership'),
	'workout_session_types' => array('memberships','workout-session'),

	'kraken_key'							=> '73dbf866dbe673867134dc90204ddf96',
	'kraken_secret'							=> 'd206555b6c07d8e3eba3807402a183578471251e',
	'manual_trial_auto_finderids' => [],


    'calorie_burn_categorywise'             =>   [
        65      => 600,
        1       => 250,
        2       => 450,
        4       => 350,
        5       => 450,
        19      => 700,
        86      => 450,
        111     => 800,
        114     => 400,
        123     => 750,
        152     => 450,
        154     => 300,
        3       => 450,
        161     => 650,
        184     => 400
    ],


    'workout_results_categorywise'        =>   [
        65      => ["tone up", "super cardio", "endurance", "muscle definition", "flat abs", "increase power"],
        1       => ["flexibility", "feel centered & calm", "stress buster", "control breathing", "improve postures", "tone up"],
        2       => ["catch some sexy moves", "super cardio", "stress buster", "improve co-ordination", "tone & shape legs, butt & hips", "fat burn"],
        4       => ["super strong core", "stability", "tone & rip", "flexibility", "improve postures", "strong abs"],
        5       => ["burn fat", "work on all muscle", "chiseled body", "super strong core", "agility", "musclar endurance"],
        19      => ["catch some sexy moves", "super cardio", "stress buster", "fat burn", "tone & shape legs, butt & hips", "flexibility"],
        86      => ["burn fat", "super cardio", "increase leg strength", "tone & shape legs, butt & hips", "speed", "stress buster"],
        111     => ["tone & rip", "increase sports performance", "increase power", "musclar endurance", "strong abs", "burn fat"],
        114     => ["super cardio", "speed", "agility", "burn fat", "endurance", "lean legs"],
        123     => ["super cardio", "speed", "agility", "burn fat", "tone & shape legs, butt & hips", "flexibility"],
        152     => ["catch some sexy moves", "super cardio", "stress buster", "improve co-ordination", "tone & shape legs, butt & hips", "fat burn"],
        154     => ["super cardio", "speed", "agility", "co-ordination", "stability", "flexibility"],
        3       => ["tone up", "co-ordination", "increase power", "work on all muscle", "stability", "increase sports performance"],
        161     => ["burn fat", "endurance", "control breathing", "increase sports performance", "speed", "shape up"],
        184     => ["burn fat","shape up","endurance","flat abs","flexibility","work on all muscle"]
    ],

    'emi_struct'=> array(
		 	array(
                "bankCode"=> "EMIA3",
                "bankName"=> "AXIS",
                "bankTitle"=>3,
                "pgId"=> "8",
                "minval"=> 500,
                "rate"=>12
            ),
            array(
                "bankCode"=> "EMIA6",
                "bankName"=> "AXIS",
                "bankTitle"=>6,
                "pgId"=> "8",
                "minval"=> 500,
                "rate"=>12
            ),
            array(
                "bankCode"=> "EMIA9",
                "bankName"=> "AXIS",
                "bankTitle"=>9,
                "pgId"=> "8",
                "minval"=> 500,
                "rate"=>13
            ),
            array(
                "bankCode"=> "EMIA12",
                "bankName"=> "AXIS",
                "bankTitle"=> 12,
                "pgId"=> "8",
                "minval"=> 500,
                "rate"=>13
            ),
            
          
            array(
                "bankCode"=> "EMI",
                "bankName"=> "HDFC",
                "bankTitle"=>3,
                "pgId"=> "15",
                "minval"=> 3000,
                "rate"=>12
            ),
            array(
                "bankCode"=> "EMI6",
                "bankName"=> "HDFC",
                "bankTitle"=>6,
                "pgId"=> "8",
                "minval"=> 3000,
                "rate"=>12
            ),
            array(
                "bankCode"=> "EMI9",
                "bankName"=> "HDFC",
                "bankTitle"=>9,
                "pgId"=> "15",
                "minval"=> 3000,
                "rate"=>13
            ),
          	array(
                "bankCode"=> "EMI12",
                "bankName"=> "HDFC",
                "bankTitle"=> 12,
                "pgId"=> "8",
                "minval"=> 3000,
                "rate"=>13
            ),
            array(
                "bankCode"=> "EMIHS03",
                "bankName"=> "HSBC",
                "bankTitle"=>3,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>12.50
            ),
            array(
                "bankCode"=> "EMIHS06",
                "bankName"=> "HSBC",
                "bankTitle"=>6,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>12.50
            ),
            array(
                "bankCode"=> "EMIHS09",
                "bankName"=> "HSBC",
                "bankTitle"=>9,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>13.50
            ),
            array(
                "bankCode"=> "EMIHS12",
                "bankName"=> "HSBC",
                "bankTitle"=> 12,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>13.50
            ),
            array(
                "bankCode"=> "EMIHS18",
                "bankName"=> "HSBC",
                "bankTitle"=> 18,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>13.50
            ),
            array(
                "bankCode"=> "EMIIC3",
                "bankName"=> "ICICI",
                "bankTitle"=>3,
                "pgId"=> "8",
                "minval"=> 1500,
                "rate"=>13
            ),
			array(
                "bankCode"=> "EMIICP6",
                "bankName"=> "ICICI",
                "bankTitle"=>6,
                "pgId"=> "8",
                "minval"=> 1500,
                "rate"=>13
            ),
            array(
                "bankCode"=> "EMIICP9",
                "bankName"=> "ICICI",
                "bankTitle"=>9,
                "pgId"=> "8",
                "minval"=> 1500,
                "rate"=>13
            ),
            array(
                "bankCode"=> "EMIIC12",
                "bankName"=> "ICICI",
                "bankTitle"=> 12,
                "pgId"=> "8",
                "minval"=> 1500,
                "rate"=>13
            ),
            array(
                "bankCode"=> "EMIIND3",
                "bankName"=> "INDUS",
                "bankTitle"=>3,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>13
            ),
            array(
                "bankCode"=> "EMIIND6",
                "bankName"=> "INDUS",
                "bankTitle"=>6,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>13
            ),
            array(
                "bankCode"=> "EMIIND9",
                "bankName"=> "INDUS",
                "bankTitle"=>9,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>13
            ),           
            array(
                "bankCode"=> "EMIIND12",
                "bankName"=> "INDUS",
                "bankTitle"=> 12,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>13
            ),
            array(
                "bankCode"=> "EMIIND18",
                "bankName"=> "INDUS",
                "bankTitle"=> 18,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>15
            ),
            array(
                "bankCode"=> "EMIIND24",
                "bankName"=> "INDUS",
                "bankTitle"=> 24,
                "pgId"=> "8",
                "minval"=> 2000,
                "rate"=>15
            ),
            array(
                "bankCode"=> "EMIK3",
                "bankName"=> "KOTAK",
                "bankTitle"=>3,
                "pgId"=> "8",
                "minval"=> 500,
                "rate"=>12
            ),
            array(
                "bankCode"=> "EMIK6",
                "bankName"=> "KOTAK",
                "bankTitle"=>6,
                "pgId"=> "8",
                "minval"=> 500,
                "rate"=>12
            ),
            array(
                "bankCode"=> "EMIK9",
                "bankName"=> "KOTAK",
                "bankTitle"=>9,
                "pgId"=> "8",
                "minval"=> 500,
                "rate"=>14
            ),
            array(
                "bankCode"=> "EMIK12",
                "bankName"=> "KOTAK",
                "bankTitle"=> 12,
                "pgId"=> "8",
                "minval"=> 500,
                "rate"=>14
            ),
            array(
                "bankCode"=> "SBI03",
                "bankName"=> "SBI",
                "bankTitle"=>3,
                "pgId"=> "8",
                "minval"=> 2500,
                "rate"=>14
            ),
            array(
                "bankCode"=> "SBI06",
                "bankName"=> "SBI",
                "bankTitle"=>6,
                "pgId"=> "8",
                "minval"=> 2500,
                "rate"=>14
            ),
            array(
                "bankCode"=> "SBI09",
                "bankName"=> "SBI",
                "bankTitle"=>9,
                "pgId"=> "8",
                "minval"=> 2500,
                "rate"=>14
            ),
            array(
                "bankCode"=> "SBI12",
                "bankName"=> "SBI",
                "bankTitle"=>12,
                "pgId"=> "8",
                "minval"=> 2500,
                "rate"=>14
            )
        ),
	'test_page_users' => ['dhruvsarawagi@fitternity.com', 'utkarshmehrotra@fitternity.com', 'sailismart@fitternity.com', 'neha@fitternity.com', 'pranjalisalvi@fitternity.com', 'maheshjadhav@fitternity.com', 'gauravravi@fitternity.com', 'nishankjain@fitternity.com', 'laxanshadesara@fitternity.com','mjmjadhav@gmail.com','gauravraviji@gmail.com','kushagra@webbutterjam.com','beltezzarthong@fitternity.com', 'vinichellani@fitternity.com','surajpalai@fitternity.com','kedarkhanvilkar@fitternity.com','nikitasharma@fitternity.com', 'firojmulani@fitternity.com', 'vipulchauhan@fitternity.com', 'vipul.chauhan705@gmail.com', 'ankitamamnia@fitternity.com', 'kailash.cp2419@gmail.com'],	
    
	'test_vendors' => ['fitternity-test-page', 'test-healthy-vendor', 'fitternity-test-dharminder', 'gaurav-test-page-gym'],
	'hide_from_search' => [11128, 6332, 6865, 7146, 9309, 9329, 9379, 9381, 9403, 9623, 9863, 9869, 9891, 10037, 11128, 12110],

	// 'delay_methods' =>["bookTrialReminderAfter2Hour","bookTrialReminderAfter2HourRegular","bookTrialReminderBefore12Hour","bookTrialReminderBefore1Hour","bookTrialReminderBefore20Min","bookTrialReminderBefore3Hour","bookTrialReminderBefore6Hour", "manualBookTrial", "reminderToConfirmManualTrial", "manual2ndBookTrial", "before3HourSlotBooking", "orderRenewalMissedcall", "sendPaymentLinkAfter3Days", "sendPaymentLinkAfter7Days", "sendPaymentLinkAfter45Days", "purchaseAfter10Days", "purchaseAfter30Days"]

	'fitternity_personal_trainers' => 'Personal Training at Home by Fitternity',

	'delay_methods' =>["bookTrialReminderAfter2HourRegular","bookTrialReminderBefore12Hour","bookTrialReminderBefore1Hour","bookTrialReminderBefore20Min","bookTrialReminderBefore6Hour", "manualBookTrial", "reminderToConfirmManualTrial", "manual2ndBookTrial", "orderRenewalMissedcall", "sendPaymentLinkAfter3Days", "sendPaymentLinkAfter7Days", "sendPaymentLinkAfter45Days", "purchaseAfter10Days", "purchaseAfter30Days", "postTrialStatusUpdate", "bookTrialReminderAfter2Hour", "bookTrialReminderBefore10Min", "bookTrialReminderBefore3Hour", 'bookTrialReminderBefore20Min', 'offhoursConfirmation', "bookTrialReminderAfter30Mins", 'abandonCartCustomerAfter2hoursFinder'],


	'my_fitness_party_slug' => ['mfp','mfp-mumbai','mfp-delhi'],

	'convinience_fee'=>2.5,

	'corporate_login' => array(
		'emails' => ['fitmein@fitternity.com'],
		'discount' => 2
	),

	'fitmein_email' => 'vg@fitmein.in',

	'hot_offer_excluded_vendors' => [941],

	'trial_auto_confirm_finder_ids' => [],

	'diet_reward_excluded_vendors' => [11230],

	'service_gallery_path' => 'http://b.fitn.in/s/g/full/',

	'facility_image_base_url'=> 'http://b.fitn.in/facility/',
	
	'gst_on_cos_percentage'=>18,

	// 'power_world_gym_vendor_ids' => [10315,10861,10863,10868,10870,10872,10875,10876,10877,10880,10883,10886,10887,10888,10890,10891,10892,10894,10895,10897,10900,12246,12247,12250,12252,12254],

	'power_world_gym_vendor_ids'=>[12246,12247,12250,12252,12254,12256,12258,12260,12261,13878,13879,13881,13883,13884,13886,13887,13899,13900,13902],

	'streak_data'=>[		
		[
			'number'=>10,
			'cashback'=>5,
			'level'=>1,
			'unlock_icon'=>'https://b.fitn.in/paypersession/level-1.png',
			'unlock_color'=>'#d34b4b'
		],
		[
			'number'=>20,
			'cashback'=>10,
			'level'=>2,
			'unlock_icon'=>'https://b.fitn.in/paypersession/level-2.png',
			'unlock_color'=>'#f7a81e'
		],
		[
			'number'=>35,
			'cashback'=>15,
			'level'=>3,
			'unlock_icon'=>'https://b.fitn.in/paypersession/level-3.png',
			'unlock_color'=>'#4b67d3'
		],
		[
			'number'=>50,
			'cashback'=>20,
			'level'=>4,
			'unlock_icon'=>'https://b.fitn.in/paypersession/level-4.png',
			'unlock_color'=>'#16b765'
		],
	],
	'paypersession_level_icon_base_url'=>'https://b.fitn.in/paypersession/level-',
	'paypersession_lock_icon'=>'https://b.fitn.in/paypersession/lock-icon.png',
	'remove_patti_from_brands' => [
									],

	'routed_order_fitcash'=>300,
	'fitcode_fitcash'=>300,

	'trial_comm'=>[
		'off_hours_begin_time'=>20,
		'off_hours_end_time'=>11,
		'offhours_scheduled_td_hours'=>2,
		'offhours_instant_td_mins'=>5,
		'offhours_fixed_time_1'=>22,
		'offhours_fixed_time_2'=>20,
		'full_day_weekend'=>['Sunday', 'Wednesday'],
		'begin_weekend'=>['Saturday'],
		'end_weekend'=>['Monday'],
	],


	'voucher_grid'=>[
		[
			'min'=>1,
			'max'=>2,
			'type'=>'faasos'
		],
		[
			'min'=>2,
			'max'=>3,
			'type'=>'faasos'
		],
		[
			'min'=>3,
			'max'=>4,
			'type'=>'faasos'
		],
		[
			'min'=>4,
			'type'=>'faasos'
		],
		
	],
	
	// 'mixed_reward_finders'=>[579,1233,1257,1259,1260,1261,1262,1263,1266,1860,1874,1875,1876,2105,2194,2545,4817,4818,4819,4821,4822,4823,4824,4825,4826,5502,5681,5741,5742,5743,5744,5750,5967,6029,6525,6530,6593,7355,7651,9171,9178,9198,9216,10675,11381,12077,12198,12226,12565,12566,12569,13396,13549,13965,1581,1582,1583,1584,1602,1604,1606,1607,2235,2236,6893,7064,1029,1030,1034,1705,1706,9872,12768,3239,10624,10964,12223,14141,14142,3175,3178,3179,3183,3201,3204,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,5964,7081,7106,7111,7114,8872,9446,13670,13671,7116,11230,3193,7037,9968,10120,14362,15056],
	'mixed_reward_finders'=>[3193,7037,9968,10120,14362,15056, 3175,3178,3179,3183,3201,3204,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,5964,7081,7106,7111,7114,8872,9446,13670,13671, 1029.0,1030.0,1034.0,1705,1706,7407,9872,12768,10782,579.0,1233.0,1257.0,1259.0,1260.0,1261.0,1262.0,1263.0,1266.0,1860,1874,1875,1876,2105,2194,2545,4206,4817,4818,4819,4821,4822,4823,4824,4825,4826,5502,5681,5741,5742,5743,5744,5750,5967,6029,6525,6530,6593,7355,7651,9171,9178,9198,9216,10675,11381,12077,12185,12226,12565,12566,12569,13116,13396,13549,13965],
	
	'no_patti_brands_slugs'=>['anytime-fitness-gurgaon'],

	'music_run_event_type'=>'music-run',
    
    'snap_bangalore_finder_ids'=>[],

	'rating_text'=>["Bad","OK","Average","Good","Excellent",],

	'voucher_grid'=>[
		[
			'min'=>1,
			'max'=>2,
			'type'=>'faasos'
		],
		[
			'min'=>2,
			'max'=>3,
			'type'=>'faasos'
		],
		[
			'min'=>3,
			'max'=>4,
			'type'=>'faasos'
		],
		[
			'min'=>4,
			'type'=>'faasos'
		],
		
	],
	'slotAllowance' =>['vendors'=>[1584],'services'=>[17626],'types'=>['workout-session','booktrials']],
	'add_wallet_extra'=>10,
    'renewal_ids'=>[],
	'brand_loyalty'=>[135,166,56,40],
	'brand_finder_without_loyalty'=>[579,1233,1261,1260,1262,1874,2105,5742,10675,9178,9171,13549,1259,1263,1266,2545,6525,12226,7651,9198,12077,5743,5741,7355,6530,1860,1876,2194,4818,4819,4822,4823,4824,4825,4826,5502,5681,5967,6029,6593,9216,9584,10745,11381,12565,12569,13116,13323,13396,13965,14606,14637,14691,15082,15709,15744,15745,15746,15747,15749,15750,15752,15758,15760,15772,15790,15807,15971,15992,16227,16456,16462,16623,16648,16761,16786,17019,17020,17311,17312],

	'first_free_string'=>' (First session Free)',
	'onepass_free_string'=>'Free Via Onepass',
    'eoys_excluded_vendor_ids'=>[8546,11230,11810,10466,941,12157,1020,613,9427,10965,1429,718,9432,4534,13660,9988,3184,9400,3192,13327,13328,13332,7010,3350,3351,3449,3450,11025,11352,3975,11988,12690,12101,6156,9579,11251,13271,14422,11456,5200,6411,7014,5601,5617,7136,5769,5833,5300,7013,5444,7012,10987,4929,7541,7616,6697,7649,5348,7585,8094,10537,4878,5688,9354,9341,5634,6578,9880,9878,4924,6214,9375,10974,5008,7832,6680,9395,8141,10975,4968,9417,9454,5108,5647,10983,7344,9487,9489,6213,5125,5347,9624,9905,9904,6477,9967,9763,9385,10549,9912,9483,5947,7395,10757,5633,10591,11370,10949,6912,12120,11043,5625,8598,8613,6475,11017,11012,11169,11171,11170,11137,11136,11185,11071,11134,5031,11236,5521,11405,11442,11499,11501,13693,11503,11517,11519,11521,11521,7368,5381,11884,11895,11901,10200,6331,11960,12062,12061,12065,10733,10507,10512,10517,10518,11934,11965,12760,7356,6564,6624,11036,12873,12885,13104,4997,7403,7174,12442,10849,13205,13213,13690,11818,13267,6052,13992,9215,9260,14065,14075,14135,9238,12449,14180,14199,14256,13398,12868,12869,13577,12848,13668,13084,13289,13291,13296,13596,14191,14192,14195,14196,14197,12862,14110,12872,14148,10932,12430,14123,14289,13823,13822,12968,13378,14357,12908,12909,14339,13673,14461,9185,14382,14432,9212,14779,14435,14412,14926,],
    'ratecard_button_color'=>'#53b7b7',
	'pg_charge'=>2,
	'upgrade'=>[
		'service_cat'=>[65, 111],
		'duration'=>[30]
	],
	'corporate_coupons'=>['mckinsey', 'syncron'],

	'finder_0_discount'=>[579],
	
	'finder_10_discount'=>[9932],

	'upgrade_session_finder_id'=>[13968, 15431, 15980],
	'extended_mixed_finder_id'=>[16251],
	'multiply_app_download_link' => 'http://onelink.to/abmultiply',

	'reward_type'=>[
		1 => "Instant Reward",
		2 => "Instant Reward + Fit Squad ",
		3 => "Instant Reward + 100% Cashback",
		4 => "Instant Reward + Fit Squad + 100% Cashback",
		5 => "100% Cashback"
    ],
    
    'no_instant_reward_types'=>[5,6],
    
    'no_fitsquad_reg_msg'=>[1, 3, 5],
    
    'no_fitsquad_reg'=>[1],

    'cashback_type_map'=>[
        1=>'A',
        2=>'B',
        3=>'C',
        4=>'D',
        5=>'E',
        6=>'F',
        7=>'G',
        0=>NULL,
    ],

    'routed_commission_reward_type_map'=>[
        1=>2.5,
        2=>3.5,
        3=>5.5,
        4=>7,
        5=>5,
        6=>6
    ],
    'women_mixed_finder_id'=>[142,147,596,823,1490,1771,2209,6049,6259,6468,9378,10571,11246,12164,13801,14016,14316,14392,14410,15215,15788,15605,4773,14193,166,8932,11032,11033,119,15188,9991,14706,15907,16157,13661,15977,15417,14982,15791,15789,16085,13800,11188,1837,13842,15706,15973,15929,13074,15192,783,878,1739,2828,7341,7896,9378,9436,2844,9991,3118,14066,12465,14464,10142,7669,4815,8744,11676,12166,12811,2867,1955,2050,1824,1863,1968,8800,13164,1913,14451,3196,4044,3720,14400,9112,14037,12683,7773,6686,14467,6884,11314,7321,3191,4164,14896,7429,3184,9400,12177,12806,6002,11988,12690,15078,14407,3499,4763,10098,10690,16211,7435,16190,3927,13322,6253,12160,15026,14014,4572,4032,6901,16233,3970,3985,4388,3595,9521,4484,12066,9601,3491,8877,6239,13471,3450,12521,3429,4256,9592,3476,4387,4653,3860,11297,3235,12044,3200,15930,7010,5958,4705,12101,15877,12964,3426,3205,11024,11871,6168,3919,12050,13254,4826,6256,3953,3322,13664,11042,11295,3456,3812,3210,3504,4391,8795,8797,3989,7116,14183,14534,4041,7330,11025,4291,3667,15296,10393,3221,14161,11409,13382,11397,14293,14516,13441,14529,14803,11366,14437,13341,14420,11448,12592,13251,13252,13205,7130,5629,15640,14804,14180,5947,7395,15248,15087,4834,14114,5300,7612,12947,9337,9726,15536,5241,6241,11965,11618,15247,6602,14607,4843,],
    'women_week_off'=>[1068,1986,6466,7697,9476,9518,10515,11001,11183,11475,11509,12157,13085,14235,14185,14448,14518,6289,15881,9424,1711,12079,380,6916,6939,1613,1642,14050,13231,12418,15979,927,1258,12100,13054,10752,14787,13781,1814,13842,13155,14063,14064,13273,15769,2509,15776,15502,15769,15547,13618,15560,15755,7500,11277,15940,15926,14826,1769,14459,812,11197,15008,15006,15363,15277,15942,10567,142,147,596,823,1490,1771,2209,6049,6259,6468,9378,10571,11246,12164,13801,14016,14316,14392,14410,15215,15788,15605,4773,14193,166,8932,11032,11033,119,15188,9991,14706,15907,16157,13661,15977,15417,14982,15791,15789,16085,13800,11188,1837,13842,15706,15973,15929,13074,15192,783,1801,1846,1884,1940,2119,2183,2890,6245,7458,9144,9469,11631,12123,6964,1981,2728,2135,1911,4815,8744,11676,12166,12811,2867,1955,2050,10392,12692,11347,4179,11306,3180,14194,7166,7168,11168,7323,16261,3196,4044,3720,14400,9112,14037,12683,7773,6686,14467,6884,11314,7321,3191,4164,14896,7429,3184,9400,12177,12806,6002,11988,12690,15078,14407,3499,4763,10098,10690,16211,7435,16190,3927,13322,6253,12160,15026,14014,4572,4032,6901,16233,3970,3985,4388,3595,9521,4484,12066,9601,3491,8877,6239,13471,3450,12521,3429,4256,9592,3476,4387,4653,3860,11580,12730,14226,14230,14274,14278,14439,14143,14119,13941,12803,12794,12793,11409,13382,11397,14293,14516,13441,14529,14803,5383,6979,10969,12572,12588,7143,14952,12972,12890,13673,7013,5444,7012,10987,4929,12884,12994,6083,5347,16041,15686,15685,15796,16252,15687,5133,14357,4916,12623,6047,9455,10589,11700,13939,14103,14840,14356,15868,14671,15869,14614,14443,12908,12909,14339,13205,7130,5629,15640,14804,14180,5947,7395,15248,15087,4834,14114,5300,7612,12947,9337,9726,15536,5241,6241,11965,11618,15247,6602,14607,4843,],
    'fit_10_excluded_finder_ids'=>[9988,3184,9400,3192,13327,13328,13332,7010,3350,3351,3449,3450,11025,11352,3975,11988,12690,6156,9579,7818,14387,3197,3398,12691,10030,7015,15310,11307,11306,3180,14194,11347,7116,613,9427,1667,14625,13901,15460,12164,9365,12046,11230,1013,1429,9877,11810,941,10466,12157,1020,1484,9459,579,1233,1257,1261,1260,1262,1266,2545,1259,1580,1581,1582,1583,1584,1602,1604,1605,1606,1607,2235,2236,6893,7064,1029,1030,1034,1705,1706,7407,9872,12768,401,576,1450,1451,1452,1455,1456,1457,1458,1460,1486,1487,1488,1647,2522,9883,11232,11234,11235,12782,12795,9922,9935,9942,9943,9948,1824,1860,1861,1862,1874,1875,1876,1878,1879,1880,1883,1935,2105,2194,2293,2425,5967,6593,9304,9423,9481,9954,10503,10970,11021,11223,11239,11811,11903,12073,12569,13094,13898,13965,13969,14102,14107,12208,1263,15992,15971,15807,15082,4819,4818,4821,4822,4825,4826,4824,5502,6029,12566,12565,15758,5750,5681,5741,5743,5744,7651,5742,12077,10675,6525,12226,6530,12198,7355,9178,9171,9198,9216,13396,11381,13549,13323,15709,15744,15745,15746,15747,15749,15750,15752,15760,15790,16227,15993,9932,10674,13968,15431,15980,9600,15775,14622,14626,14627,5728,5745,5746,5747,5748,6250,7335,7900,7902,7903,7905,7906,7907,7909,8821,8823,8871,9111,9418,9462,9480,10568,10570,10756,10953,11037,11103,11129,11363,11742,12221,12516,12823,13124,13980,15103,2890,3175,3178,3183,3201,3204,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,5964,7081,7106,7111,7114,8872,9446,10776,10782,10786,10787,10809,10811,13670,13671,9615,9951,9957,9959,10190,10521,10522,10524,10526,10558,10753,10759,10767,10768,10770,10771,11270,11271,11272,13256,13257,13258,13261,13262,13263,14125,14126,14129,1747,1813,4528,4530,5740,9984,10081,12709,4492,5596,5682,5729,9507,9508,9989,12480,13526,1739,2806,2824,2828,2833,2844,7341,7896,15193,3193,7037,7898,9968,10120,14361,14362,15056,4830,4827,4831,4829,13138,13135,13137,13136,11836,11828,11829,11838,13680,11830,11451,3239,9743,10624,10964,11106,11598,12126,12223,13102,14141,14142,15148,6244,6979,5383,10969,12572,12588,6047,4928,6460,7013,5444,7012,10987,4929,9149,9348,7389,5609,9140,9415,10210,4968,9453,10889,6566,9495,6480,11138,10995,9637,10689,10962,11353,11136,11141,11215,9056,4949,6083,11506,11503,13693,11501,15741,11499,11667,5418,11884,12025,11868,5621,5841,8688,5273,12119,9473,9651,12760,7174,12442,12991,13690,11818,10189,13219,12568,12783,12784,14078,13705,13921,13940,13960,13959,13116,14135,11040,12867,13146,12893,12824,13289,13291,13296,13596,14191,14192,14195,14196,14197,12834,14171,14254,13823,13822,14443,12908,12909,14339,13223,14550,15123,15122,15121,12927,12454,14571,14094,14472,14473,14818,14642,14864,14678,14894,14899,14931,6498,14423,7123,15064,15074,14613,14618,13723,7366,15096,15216,15269,15272,15332,15384,14637,14154,14691,6535,9851,9854,9855,11027,11028,11030,11031,16200,16202,16204,11029,16203,16206,9845,16209,16243,4534],

    'multifit_email'=>'info@multifit.co.in',
    
    'no_convinience_finder_ids'=>[1259, 1263, 1266, 12986, 6168],

    'discount_vendors'=>[12592,13251,13252,14526,11437,15882,12793,13941,12794,12803,13282,11397,14206,13265,13266,14168,11580,14230,14278,12730,14274,14226,14439,14803,11778,13441,14529,11622,13963,14046,11622,13342,13345,13349,13352,11409,13382,12371,11613,14292,11372,13099,14119,14438,14293],

    'discount_vendors_duration' => [180, 360],

    'powerworld_finder_ids'=>[10861,10863,10868,10870,10872,10875,10876,10877,10880,10883,10886,10887,10888,10890,10891,10892,10894,10895,10897,10900,12246,12247,12250,12252,12254,12256,12258,12260,12261,13878,13879,13881,13883,13884,13886,13887,13899,13900,13902,16607,16608,16609,16610],

    'sucheta_pal_finder_ids'=>[16452, 12986, 1493],
    
    'sucheta_pal_service_ids'=>[16452, 12986, 1493],

    'anytime_finder_ids'=> [7335,5745,5728,8821,8871,5747,12221,5748,5746,6250,9480,8823,10570,10568,7909,13124,11363,7907,11103,12516,15103,11037,11129,11742,7902,16209,13980,15103,15384,13031,],
    
	'non_flexi_service_cat'=>[111, 65, 5],
	
	// 'camp_excluded_vendor_id'=>[1935,9423,9481,9600,9932,9954,10674,10970,11021,11223,12208,13094,13898,13968,14102,14107,14622,14626,14627,15431,15775,15980,16062,16251,16449,16450,16562,16636,16644,579,1233,1260,1261,1262,1874,2105,9171,9178,5742,10675,13791],
	'camp_excluded_vendor_id'=>[579,1233,1260,1261,1262,1874,2105,9171,9178,5742,10675,13791],
	// 'fitbox_reward_vendor_id'=>[9111,12516,15103,7335,11037,11103,5728,11363,5745,8871,6250,5746,5748,12221,16569,16209,10570,10568,13124,8823,7902,16591,4819,4823,4824,4825,4826,15758],
	'fitbox_reward_vendor_id'=>[],

	'occasion_dates' => ['2019-10-26', '2019-10-29'],
    
	'tab_session_pack_vendor_ids'=>[1490,424,1935,9423,9481,9932,9954,10970,11021,11223,12208,13968,14102,15431,15775,15980,16251],
	"service_icon_base_url" => "http://b.fitn.in/iconsv1/",
	"service_icon_base_url_extention" => ".png",
	"checkin_checkout_max_distance_in_meters" => 2000
    
);