<?php


return array(

	//local
	'new_search_url' =>'http://apistage.fitn.in:5000/',
	'url' => 'http://fitapi.com',
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

	//stage
	// 'new_search_url' =>'http://apistage.fitn.in:5000/',
	// 'url' => 'http://apistage.fitn.in',
	// 'admin_url' => 'http://adminstage.fitn.in',
	// 'website' => 'http://apistage.fitn.in:8903',
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

	//beta
	// 'new_search_url' =>'http://apistage.fitn.in:5000/',
	// 'url' => 'http://apistage.fitn.in',
	// 'admin_url' => 'http://adminstage.fitn.in',
	// 'website' => 'http://apistage.fitn.in:1122',
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

	//live
	// 'new_search_url' =>'http://c1.fitternity.com/',
	// 'url' => 'https://a1.fitternity.com',
	// 'admin_url' => 'https://fitn.in',
	// 'website' => 'https://www.fitternity.com',
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

	"core_key"=> "FITITRNTY",
	'non_peak_hours' => ["off"=>0.8,"gym"=>["off"=>0.8,"start"=>10,"end"=>18],"studios"=>["start"=>11,"end"=>17,"off"=>0.8]],
	'pubnub_publish' => 'pub-c-d9aafff8-bb9e-42a0-a24b-17ab5500036f',
	'pubnub_sub' => 'sub-c-05ef3130-d0e6-11e6-bbe2-02ee2ddab7fe',
	
	'download_app_link' => 'https://go.onelink.me/I0CO?pid=techfitsms',//https://www.fitternity.com/downloadapp?source=fittech',
	
    'business' => 'http://business.fitternity.com',
	'static_coupon' => array(
		array("code" => "fivefit", "text" => "Get Flat 5% off | Limited Memberships Available | Hurry! Code: FIVEFIT","discount_max" => 10000,"discount_amount" => 0,"discount_min" => 0, "discount_percent"=> 5, "once_per_user"=> true, "ratecard_type"=> ["membership","healthytiffinmembership"]),
		array("code" => "lucky500", "text" => "3 Lucky Members Get Flat Rs.500 off | Hurry! Use Code: LUCKY500","discount_max" => 500,"discount_amount" => 500,"discount_min" => 500, "once_per_user"=> true, "ratecard_type"=> ["membership","healthytiffinmembership"]),
		array("code" => "lucky300", "text" => "3 Lucky Members Get Flat Rs.300 off | Hurry! Use Code: LUCKY300","discount_max" => 300,"discount_amount" => 300,"discount_min" => 300, "once_per_user"=> true, "ratecard_type"=> ["membership","healthytiffinmembership"]),
		array("code" => "starfit", "text" => "Experience Luxury with A Flat 5% off Membership Purchase | NO T&C | Code: STARFIT","discount_max" => 10000,"discount_amount" => 0,"discount_min" => 0, "discount_percent"=> 5, "once_per_user"=> true, "ratecard_type"=> ["membership","healthytiffinmembership"]),
	),
    'app' =>array(
		'discount'		=> 			0,
		'discount_excluded_vendors' => [9459,1484,1458,576,1647,1486,9883,1451,1452,401,1487,1457,2522,1488,1460,4830,4829,4831,4827,4821,4818],
	),
	'fitternity_vendors' => [11907,9869,9891,11128,12064],
	'vendors_without_convenience_fee' => [7116,9149,12008,1860,6593,14085,14081,13761,13765,14079],
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
	//Production
	// 'es' =>array(
	// 	'url'		=> 			'ESAdmin:fitternity2020@54.169.120.141:8050',
	// 	'host'		=> 			'ESAdmin:fitternity2020@54.169.120.141',
	// 	'port'		=>			8050,
	// 	'default_index' => 	'fitternity',
	// 	'default_type' 	=> 	'finder',
	// ),
	//stage
	'es' =>array(
	 	'url'		=> 			'139.59.16.74:1243',
	 	'host'		=> 			'139.59.16.74',
	 	'port'		=>			1243,
	 	'default_index' => 	'fitternity',
	 	'default_type' 	=> 	'finder',
	),
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
		'nbf' => time()+10, // time when token can be used from
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
		'key' 								=> 'AKIAIRQP65VEX5N23QRQ',
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
	),

	'customer_care_number' => '+919699998838',
	'contact_us_vendor_email' => 'business@fitternity.com',
	'contact_us_customer_email' => 'support@fitternity.com',
	'contact_us_vendor_number' => '+919699998838',
	'contact_us_customer_number' => '+912261094444',
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
                "bankCode"=> "EMIICP6",
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
                "bankCode"=> "SBI03",
                "bankName"=> "SBI",
                "bankTitle"=>9,
                "pgId"=> "8",
                "minval"=> 2500,
                "rate"=>14
            ),
            array(
                "bankCode"=> "SBI06",
                "bankName"=> "SBI",
                "bankTitle"=>12,
                "pgId"=> "8",
                "minval"=> 2500,
                "rate"=>14
            )
        ),
    'test_page_users' => ['dhruvsarawagi@fitternity.com', 'utkarshmehrotra@fitternity.com', 'sailismart@fitternity.com', 'neha@fitternity.com', 'pranjalisalvi@fitternity.com', 'maheshjadhav@fitternity.com', 'gauravravi@fitternity.com', 'nishankjain@fitternity.com', 'laxanshadesara@fitternity.com','mjmjadhav@gmail.com','gauravraviji@gmail.com','kushagra@webbutterjam.com','beltezzarthong@fitternity.com'],
	
	'test_vendors' => ['fitternity-test-page-bandra-west', 'test-healthy-vendor', 'fitternity-test-dharminder', 'gaurav-test-page-gym'],
	'hide_from_search' => [11128, 6332, 6865, 7146, 9309, 9329, 9379, 9381, 9403, 9623, 9863, 9869, 9891, 10037, 11128, 12110, 576, 1451, 1460, 1647, 9883, 2522, 401, 1486, 1488, 1458, 1487, 1452, 1878, 4830, 4827, 4831, 4829, 13138, 13135, 13137, 13136, 11836, 11828, 11829, 11838, 13680, 11830, 11451, 1457],

	// 'delay_methods' =>["bookTrialReminderAfter2Hour","bookTrialReminderAfter2HourRegular","bookTrialReminderBefore12Hour","bookTrialReminderBefore1Hour","bookTrialReminderBefore20Min","bookTrialReminderBefore3Hour","bookTrialReminderBefore6Hour", "manualBookTrial", "reminderToConfirmManualTrial", "manual2ndBookTrial", "before3HourSlotBooking", "orderRenewalMissedcall", "sendPaymentLinkAfter3Days", "sendPaymentLinkAfter7Days", "sendPaymentLinkAfter45Days", "purchaseAfter10Days", "purchaseAfter30Days"]

	'fitternity_personal_trainers' => 'Personal Training at Home by Fitternity',

	'delay_methods' =>["bookTrialReminderAfter2HourRegular","bookTrialReminderBefore12Hour","bookTrialReminderBefore1Hour","bookTrialReminderBefore20Min","bookTrialReminderBefore6Hour", "manualBookTrial", "reminderToConfirmManualTrial", "manual2ndBookTrial", "orderRenewalMissedcall", "sendPaymentLinkAfter3Days", "sendPaymentLinkAfter7Days", "sendPaymentLinkAfter45Days", "purchaseAfter10Days", "purchaseAfter30Days", "postTrialStatusUpdate", "bookTrialReminderAfter2Hour", "bookTrialReminderBefore10Min", "bookTrialReminderBefore3Hour", 'bookTrialReminderBefore20Min', 'offhoursConfirmation'],


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
			'number'=>3,
			'cashback'=>10,
			'level'=>1,
			'unlock_icon'=>'https://b.fitn.in/paypersession/level-1.png',
			'unlock_color'=>'#d34b4b'
		],
		[
			'number'=>5,
			'cashback'=>15,
			'level'=>2,
			'unlock_icon'=>'https://b.fitn.in/paypersession/level-2.png',
			'unlock_color'=>'#f7a81e'
		],
		[
			'number'=>10,
			'cashback'=>20,
			'level'=>3,
			'unlock_icon'=>'https://b.fitn.in/paypersession/level-3.png',
			'unlock_color'=>'#4b67d3'
		],
		[
			'number'=>20,
			'cashback'=>30,
			'level'=>4,
			'unlock_icon'=>'https://b.fitn.in/paypersession/level-4.png',
			'unlock_color'=>'#16b765'
		],
	],
	'paypersession_level_icon_base_url'=>'https://b.fitn.in/paypersession/level-',
	'paypersession_lock_icon'=>'https://b.fitn.in/paypersession/lock-icon.png',
	'remove_patti_from_brands' => [
									9427,10965,12164,9419,9365,12046,8546,14518,11230,1013,1429,2421,9877,11810,941,718,9432,10466,12157,1020,1484,9459,4824,3175,3178,3179,3183,3201,3204,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,5962,5964,7081,7106,7111,7114,7116,8872,9446,13670,13671,579,1233,1257,1261,1260,1262,1266,2545,1259,1263,1874,1860,1875,12569,2105,1876,5967,6593,2194,13965,7335,5745,5728,8821,8871,5747,12221,10953,5748,5746,6250,9480,8823,10570,10568,7909,13124,11363,7907,11103,12516,10756,11037,11129,11742,7902,7136,12008,12181,5681,6411
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
		'full_day_weekend'=>['Sunday'],
		'begin_weekend'=>['Saturday'],
		'end_weekend'=>['Monday'],
	],

	'mixed_reward_finders'=>[579,1233,1257,1259,1260,1261,1262,1263,1266,1860,1874,1875,1876,2105,2194,2545,4817,4818,4819,4821,4822,4823,4824,4825,4826,5502,5681,5741,5742,5743,5744,5750,5967,6029,6525,6530,6593,7355,7651,9171,9178,9198,9216,10675,11381,12077,12198,12226,12565,12566,12569,13396,13549,13965,1581,1582,1583,1584,1602,1604,1606,1607,2235,2236,6893,7064,1029,1030,1034,1705,1706,9872,12768,3239,10624,10964,12223,14141,14142,3175,3178,3179,3183,3201,3204,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,5964,7081,7106,7111,7114,8872,9446,13670,13671,7116],
	
	'no_patti_brands_slugs'=>['anytime-fitness-gurgaon'],


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
	'add_wallet_extra'=>10

);