<?php


App::error(function(Illuminate\Database\Eloquent\ModelNotFoundException $e){
	return Response::json('not found',404);
});


##############################################################################
/******************** DEBUG SECTION START HERE /********************/
Route::get('/', function() { return "laravel 4.2 goes here....";});

Route::get('/testcountrysms', function() { 

	$user 			=	"chaitu87"; //your username
	$password 		=	"564789123"; //your password
	// $mobilenumbers 	=	"919004483103"; //enter Mobile numbers comma seperated
	$mobilenumbers 	=	"919773348762"; //enter Mobile numbers comma seperated
	$message  		=	"Hey Primi. Your workout session is confirmed for December 2, 2015 (Tuesday), 5.00 PM for Zumba at Fitness First, Andheri (West). Thank you for using Fitternity.com. For any queries call us on +91 92222 21131 or reply to this message."; //enter Your Message
	$senderid 		=	"FTRNTY"; //Your senderid
	$messagetype 	=	"N"; //Type Of Your Message
	$DReports 		=	"Y"; //Delivery Reports
	$url 			=	"http://www.smscountry.com/SMSCwebservice_Bulk.aspx";
	$message 		=	urlencode($message);
	$ch 			=	curl_init();
	$ret 			=	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt ($ch, CURLOPT_POSTFIELDS,"User=$user&passwd=$password&mobilenumber=$mobilenumbers&message=$message&sid=$senderid&mtype=$messagetype&DR=$DReports");
	$ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//If you are behind proxy then please uncomment below line and provide your proxy ip with port.
	// $ret = curl_setopt($ch, CURLOPT_PROXY, "PROXY IP ADDRESS:PORT");
	return $curlresponse = curl_exec($ch); 

});


Route::get('/testdate', function() { 

	return Carbon::now();

	echo strip_tags('<h1>Title example</h1>');
	exit;
	$isodate = '2015-03-10T13:00:00.000Z';
	$actualdate =  \Carbon\Carbon::now();
	return \Carbon\Carbon::now();
	return Finder::findOrFail(1)->toArray();
	return  date( "Y-m-d H:i:s", strtotime("2015-03-10T13:00:00.000Z"));
	//convert iso date to php datetime
	return "laravel 4.2 goes here ....";

});

Route::get('/testpushnotification', function() { 

	PushNotification::app('appNameAndroid')->to('APA91bG_gkVGxr6atdmGbMGGHWLP82U2o91HjU-UKu27gtEFy1a-9TVXYg7gVr0Q_DLEPEtpE-0z6K5f2nuL9i_SPeRySLy0Typtt7ZjQRi4yHc49R5EQg44gAGuovNpP76UbC8wuIL8VCjgNVXD2UEXmwnVFvQJDw')->send('Hello World, i`m a push message');

});

Route::get('/testtwilio', function() { 

	return Twilio::message('+919773348762', 'Pink Customer Elephants and Happy Rainbows');
});


Route::get('/testemail', function() { 
	// return "email send succuess";
	$m1 = Queue::push('WriteClass', array( 'string' => 'new delete function form local -- '.time()),'pullapp');
	$m2 = Queue::later(Carbon::now()->addMinutes(3),'WriteClass', array( 'string' => 'new delete function 3 min time -- '.time()),'pullapp');
	$m3 = Queue::later(Carbon::now()->addMinutes(5),'WriteClass', array( 'string' => 'new delete function 5 min time -- '.time()),'pullapp');
	echo "$m1 -- $m2 -- $m3";
	// 	$url ='https://mq-aws-us-east-1.iron.io/1/projects/549a5af560c8e60009000030/queues/pullapp/messages/'.$m2.'?oauth=tsFrArQmL8VS8Cx-5PDg3gij19Y';
	//    $ch = curl_init();
	//    curl_setopt($ch, CURLOPT_URL,$url);
	//    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	//    $result = curl_exec($ch);
	//    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	//    curl_close($ch);
	$deleteid = Queue::deleteMessage('pullapp',$m2);
});

class WriteClass {

	public function fire($job, $data){

		$job_id = $job->getJobId(); 

		// File::append(app_path().'/queue.txt', $data['string']." ------ $job_id".PHP_EOL); 
		$email_template = 'emails.test';
		$email_template_data = array();
		$email_message_data = array(
			'string' => 'Hello World from array with time -- '.time(),
			'to' => 'sanjay.id7@gmail.com',
			'reciver_name' => 'sanjay sahu',
			'bcc_emailids' => array('sanjay.fitternity@gmail.com'),
			'email_subject' => $data['string'].' -- Testemail with queue using ngrok from local ' .time()
			);

		Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
			$message->to($email_message_data['to'], $email_message_data['reciver_name'])
			->bcc($email_message_data['bcc_emailids'])
			->subject($email_message_data['email_subject'].' send email from instant -- '.date( "Y-m-d H:i:s", time()));
		});
		$job->delete();  
		return $job_id;	
	}

}


Route::get('/testpushemail', function() { 

	$email_template = 'emails.testemail1';
	$email_template_data = array();
	$email_message_data = array(
		'string' => 'Hello World from array with time -- '.time(),
		'to' => 'sanjay.id7@gmail.com',
		'reciver_name' => 'sanjay sahu',
		'bcc_emailids' => array('chaithanyapadi@fitternity.com'),
		'bcc_emailids' => array(),
		'email_subject' => 'Testemail using loop ' .time()
		);

	// $messageid1 =  Mail::queue($email_template, $email_template_data, function($message) use ($email_message_data){
	// 		$message->to($email_message_data['to'], $email_message_data['reciver_name'])
	// 		->bcc($email_message_data['bcc_emailids'])
	// 		->subject($email_message_data['email_subject'].' from instant -- '.date( "Y-m-d H:i:s", time()));
	// 	});
	// return var_dump($messageid1);

	// echo $deleteid = Queue::deleteReserved('default',$messageid1);

});


Route::get('/testhipchat', function() { 
	HipChat::setRoom('Teamfitternity');
	HipChat::sendMessage('My Message to room Teamfitternity', 'green');
	// HipChat::sendMessage('My Message', 'red', true);
	return "successfully test hipchat ....";
});

Route::get('/testpushqueue', function() { 
	/* Queue:push(function($job) use ($data){ $data['string']; $job->delete();  }); */
	Queue::push('WriteFile', array( 'string' => 'new testpushqueue instantly -- '.time()));
	Queue::later(Carbon::now()->addMinutes(1),'WriteFile', array( 'string' => 'new testpushqueue delay by 1 min time -- '.time()));
	Queue::later(Carbon::now()->addMinutes(2),'WriteFile', array( 'string' => 'new testpushqueue delay by 2 min time -- '.time()));
	return "successfully test push queue with dealy job as well....";
});

class WriteFile {

	public function fire($job, $data){

		$job_id = $job->getJobId(); 

		File::append(app_path().'/queue.txt', $data['string']." ------ $job_id".PHP_EOL); 
		
		$job->delete();  
		
	}

}

Route::get('migrateratecards/', array('as' => 'finders.migrateratecards','uses' => 'FindersController@migrateratecards'));
Route::get('updatepopularity/', array('as' => 'finders.updatepopularity','uses' => 'FindersController@updatepopularity'));




/******************** DEBUG SECTION END HERE ********************/
##############################################################################


Route::get('/home', 'HomeController@getHomePageData');
Route::get('/homev2/{city?}', 'HomeController@getHomePageDatav2');
Route::get('/zumbadiscover', 'HomeController@zumbadiscover');
Route::get('/fitcardpage1finders', 'HomeController@fitcardpagefinders');
Route::get('/specialoffers_finder', 'HomeController@specialoffers_finder');
Route::get('/yfc_finders', 'HomeController@yfc_finders');
Route::get('/getcollecitonnames/{city?}', 'HomeController@getcollecitonnames');
Route::get('/getcollecitonfinders/{slug}', 'HomeController@getcollecitonfinders');




##############################################################################
/******************** CUSTOMERS SECTION START HERE ***********************/
Route::get('/autobooktrials/{customeremail}',  array('as' => 'customer.autobooktrials','uses' => 'CustomerController@getAutoBookTrials'));
Route::get('/autobooktrial/{trialid}',  array('as' => 'customer.autobooktrial','uses' => 'CustomerController@getAutoBookTrial'));
Route::post('capturepayment',  array('as' => 'customer.capturepayment','uses' => 'CustomerController@capturePayment'));


/******************** CUSTOMERS SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** USERS SECTION START HERE ***********************/
Route::get('/experts', 'UsersController@getExperts');
Route::get('/expert/{username}', 'UsersController@getExpert');
Route::get('/authors', 'UsersController@getAuthors');
Route::get('/author/{username}', 'UsersController@getAuthor');

/******************** USERS SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** FINDERS SECTION START HERE ***********************/
//Route::get('getallfinder/', array('as' => 'finders.getallfinder','uses' => 'FindersController@getallfinder'));
Route::get('finderdetail/{slug}', array('as' => 'finders.finderdetail','uses' => 'FindersController@finderdetail'));
// Route::get('ratecards/{finderid}', array('as' => 'finders.ratecards','uses' => 'FindersController@ratecards'));
Route::get('ratecarddetail/{id}', array('as' => 'finders.ratecarddetail','uses' => 'FindersController@ratecarddetail'));
Route::post('updatefinderrating/', array('as' => 'finders.updatefinderrating','uses' => 'FindersController@updatefinderrating'));
Route::get('getfinderleftside/', array('as' => 'finders.getfinderleftside','uses' => 'FindersController@getfinderleftside'));
//Route::get('getallfinders/', array('as' => 'finders.getallfinders','uses' => 'FindersController@getallfinders'));
Route::get('updatefinderlocaiton/', array('as' => 'finders.updatefinderlocaiton','uses' => 'FindersController@updatefinderlocaiton'));

Route::get('finder/sendbooktrialdaliysummary/', array('as' => 'finders.sendbooktrialdaliysummary','uses' => 'FindersController@sendbooktrialdaliysummary'));

/******************** FINDERS SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** SCHEDULE BOOK TRIAL SECTION START HERE ***********************/
Route::get('getschedulebooktrial/{finderid?}/{date?}', array('as' => 'finders.getschedulebooktrial','uses' => 'SchedulebooktrialsController@getScheduleBookTrial'));
Route::get('booktrial/{finderid?}/{date?}', array('as' => 'finders.getbooktrial','uses' => 'SchedulebooktrialsController@getBookTrial'));
Route::post('booktrial', array('as' => 'finders.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrial'));
Route::post('manualbooktrial', array('as' => 'finders.storemanualbooktrial','uses' => 'SchedulebooktrialsController@manualBookTrial'));
Route::post('manual2ndbooktrial', array('as' => 'finders.storemanual2ndbooktrial','uses' => 'SchedulebooktrialsController@manual2ndBookTrial'));



/******************** SCHEDULE BOOK TRIAL SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** BLOGS SECTION START HERE ********************/
Route::get('/blogs/{offset}/{limit}', 'BlogsController@getBlogs');
Route::get('blogdetail/{slug}', array('as' => 'blogs.blogdetail','uses' => 'BlogsController@blogdetail'));
Route::get('/blogs/{cat}', 'BlogsController@getCategoryBLogs');
Route::get('/updateblogdate', 'BlogsController@updateblogdate');
Route::post('/getblogrelatedfinder', 'BlogsController@getblogRelatedFinder');

/******************** BLOGS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** ELASTICSEARH SECTION START HERE  *******************/
Route::get('createindex/{index?}', array('as' => 'elasticsearch.createindex','uses' => 'ElasticsearchController@createIndex'));
Route::get('deleteindex/{index?}', array('as' => 'elasticsearch.deleteindex','uses' => 'ElasticsearchController@deleteIndex'));
Route::get('managesetttings/{index?}', array('as' => 'elasticsearch.managesetttings','uses' => 'ElasticsearchController@manageSetttings'));
Route::get('createtype/{type}', array('as' => 'elasticsearch.createtype','uses' => 'ElasticsearchController@createType'));
Route::get('checkmapping/{type}', array('as' => 'elasticsearch.checkmapping','uses' => 'ElasticsearchController@checkMapping'));
Route::get('deletetype/{type}', array('as' => 'elasticsearch.deletetype','uses' => 'ElasticsearchController@deleteType'));		
Route::get('mongo2elastic/{type?}', array('as' => 'elasticsearch.mongo2elastic','uses' => 'ElasticsearchController@mongo2Elastic'));

/******************** ELASTICSEARH SECTION END HERE  ********************/
##############################################################################



##############################################################################
/******************** SEARCH SECTION START HERE ********************/
Route::post('/search', 'SearchController@getGlobal');
Route::post('/search/finders', 'SearchController@getFinders');
Route::post('/findersearchv3', 'SearchController@getFindersv3');
Route::post('/globalsearch', 'SearchController@getGlobalv2');
Route::post('/findersearch', 'SearchController@getFindersv4');
Route::post('/geolocationfindersearch', 'SearchController@geoLocationFinder');

Route::get('/categoryfinders', 'SearchController@categoryfinders');
Route::post('/fitmaniafinders', 'SearchController@getFitmaniaFinders');
Route::post('/fitcardfinders', 'SearchController@getFitcardFinders');
/******************** SEARCH SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** SENDING EMAIL STUFFS SECTION START HERE ********************/
Route::post('/notify/{notifytype}','EmailSmsApiController@triggerNotify');
Route::post('/email/requestcallback','EmailSmsApiController@RequestCallback');
Route::post('/email/booktrial','EmailSmsApiController@BookTrial');
Route::post('/email/extrabooktrial','EmailSmsApiController@extraBookTrial');
Route::post('/email/finderlead','EmailSmsApiController@FinderLead');
Route::post('/email/emailfinder','EmailSmsApiController@EmailSmsFinder');
Route::post('/email/newfinder','EmailSmsApiController@findercreated');
Route::post('/email/finderreview','EmailSmsApiController@ReviewOnfinder');
Route::post('/email/createcommunity','EmailSmsApiController@CreateCommunity');
Route::post('/email/joincommuntiy','EmailSmsApiController@JoinCommunity');
Route::post('/email/interestcommunity','EmailSmsApiController@InterestCommunity');
Route::post('/email/commentonblog','EmailSmsApiController@CommentOnBlog');
Route::post('/subscribenewsletter','EmailSmsApiController@SubscribeNewsletter');
Route::post('/email/joinevent','EmailSmsApiController@JoinEvent');
Route::post('/email/createevent','EmailSmsApiController@CreateEvent');
Route::post('/landing', 'CaptureController@postCapture');
Route::post('/fivefitness/customer', 'EmailSmsApiController@fivefitnesscustomer');
Route::post('/fivefitness/refundcustomer', 'EmailSmsApiController@refundfivefitnesscustomer');
Route::post('/registerme', 'EmailSmsApiController@registerme');
Route::post('/landingpage/conversion', 'EmailSmsApiController@landingconversion');
Route::post('/landingpage/callback', 'EmailSmsApiController@landingpagecallback');
Route::post('/landingpage/register', 'EmailSmsApiController@landingpageregister');
Route::post('/offeravailed', 'EmailSmsApiController@offeravailed');
Route::post('/fitcardbuy', 'EmailSmsApiController@fitcardbuy');
Route::post('/not_able_to_find', 'EmailSmsApiController@not_able_to_find');



Route::get('/email/testemail','EmailSmsApiController@testemail');


Route::post('/queue/push', function(){
	return Queue::marshal();
});
##############################################################################
/******************** SENDING EMAIL STUFFS SECTION START HERE ********************/