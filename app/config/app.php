<?php


return array(

	/*
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	|
	| When your application is in debug mode, detailed error messages with
	| stack traces will be shown on every error that occurs within your
	| application. If disabled, a simple generic error page is shown.
	|
	*/


	'debug' => TRUE,

	/*
	|--------------------------------------------------------------------------
	| Application URL
	|--------------------------------------------------------------------------
	|
	| This URL is used by the console to properly generate URLs when using
	| the Artisan command line tool. You should set this to the root of
	| your application so that it is used when running Artisan tasks.
	|
	*/

	// 'url' => 'https://a1.fitternity.com',
	// 'url' => 'http://apistage.fitn.in',
	'url' => 'http://fitapi.com',



    // 'website' => 'https://www.fitternity.com',
    'website' => 'http://apistage.fitn.in:1122',


	'app' =>array(
		'discount'		=> 			2,
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
		'DbEvent' 					=> 'App\Models\Event',
	),

	'cachetime' 					=> 	10,
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
	'es' =>array(
		'url'		=> 			'ESAdmin:fitternity2020@54.169.120.141:8050',
		'host'		=> 			'ESAdmin:fitternity2020@54.169.120.141',
		'port'		=>			8050,
		'default_index' => 	'fitternity',
		'default_type' 	=> 	'finder',
	),
	//stage
	/*'es' =>array(
	 	'url'		=> 			'139.59.16.74:1243',
	 	'host'		=> 			'139.59.16.74',
	 	'port'		=>			1243,
	 	'default_index' => 	'fitternity',
	 	'default_type' 	=> 	'finder',

	),*/
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
		'nbf' => time()+10, // time when token can be used from
		'exp' => time()+(86400*365), // time when token gets expired (1 year)
		'alg' => 'HS256',
	),

	'forgot_password' => array(
		'key' => 'fitternity', //secret key to encode token
		'iat' => time(), // time when token is created
		'exp' => time()+86400, // time when token gets expired (1 day)
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
	),

	'customer_care_number' => '+912261222233',
	'contact_us_vendor_email' => 'business@fitternity.com',
	'contact_us_customer_email' => 'support@fitternity.com',
	'contact_us_vendor_number' => '+912261222233',
	'contact_us_customer_number' => '+912261222222',

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
    'test_page_users' => ['dhruvsarawagi@fitternity.com', 'utkarshmehrotra@fitternity.com', 'sailismart@fitternity.com', 'neha@fitternity.com', 'pranjalisalvi@fitternity.com', 'maheshjadhav@fitternity.com', 'gauravravi@fitternity.com', 'nishankjain@fitternity.com', 'laxanshadesara@fitternity.com','mjmjadhav@gmail.com','gauravraviji@gmail.com','kushagra@webbutterjam.com'],
    'test_vendors' => ['fitternity-test-page-bandra-west', 'test-healthy-vendor']



);
