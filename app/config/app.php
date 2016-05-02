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


	'debug' => FALSE,

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

	'url' => 'http://a1.fitternity.com',
	// 'url' => 'http://apistg.fitn.in/',

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

	),

	'cachetime' 					=> 	10,
	'perpage' 						=> 	50,

	's3_finder_url'							=> 'https://d3oorwrq3wx4ad.cloudfront.net/f/',
	's3_service_url'						=> 'https://d3oorwrq3wx4ad.cloudfront.net/s/',

	'elasticsearch_port' 			=> 	9200,
	'elasticsearch_host_new' 			=> 	'ESAdmin:fitternity2020@54.169.120.141',
	'elasticsearch_port_new'        => 8050,

	//old
	'elasticsearch_host' 			=> 	'54.179.134.14',
	'elasticsearch_port' 			=> 	9200,
	//'elasticsearch_host' 			=> 	'localhost',
	//'elasticsearch_default_index' 	=> 	'fitternity'
	//'elasticsearch_host' 			=> 	'ec2-54-169-60-45.ap-southeast-1.compute.amazonaws.com',
	'elasticsearch_default_index' 	=> 	'fitternity',
	'elasticsearch_default_type' 	=> 	'finder',

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

	'customer_care_number' => '02261222233',
	'contact_us_vendor_email' => 'business@fitternity.com',
	'contact_us_customer_email' => 'support@fitternity.com',
	'contact_us_vendor_number' => '02261222233',
	'contact_us_customer_number' => '02261222222',

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



);
