<?php


App::error(function(Illuminate\Database\Eloquent\ModelNotFoundException $e){
	return Response::json('not found',404);
});


##############################################################################
/******************** DEBUG SECTION START HERE /********************/
Route::get('/', function() { return "laravel 4.2 goes here....";});
Route::get('/testdate', function() { 

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

	$email_template = 'emails.test';
	$email_template_data = array();
	$email_message_data = array(
		'string' => 'Hello World from array with time -- '.time(),
		'to' => 'sanjay.id7@gmail.com',
		'reciver_name' => 'sanjay sahu',
		'bcc_emailids' => array('sanjay.fitternity@gmail.com'),
		'email_subject' => 'Testemail with queue using ngrok from local ' .time()
		);

	Mail::queue($email_template, $email_template_data, function($message) use ($email_message_data){
		$message->to($email_message_data['to'], $email_message_data['reciver_name'])
		->bcc($email_message_data['bcc_emailids'])
		->subject($email_message_data['email_subject'].' send email from instant -- '.date( "Y-m-d H:i:s", time()));
	});

	return "email send succuess";

});


// Route::get('/testpushemail', function() { 

	// $finder = Finder::with('locationtags')->where('_id','=',1)->first();
	// return $finder;

	$email_template = 'emails.testemail1';
	$email_template_data = array();
	$email_message_data = array(
		'string' => 'Hello World from array with time -- '.time(),
		'to' => 'sanjay.id7@gmail.com',
		'reciver_name' => 'sanjay sahu',
		'bcc_emailids' => array('chaithanyapadi@fitternity.com'),
		'bcc_emailids' => array(),
		'email_subject' => 'Testemail 4m local using redis with deleteReserved' .time()
		);
	// var_dump($delaytime);
	// exit;
	
	$messageid1 =  Mail::queue($email_template, $email_template_data, function($message) use ($email_message_data){
		$message->to($email_message_data['to'], $email_message_data['reciver_name'])
		->bcc($email_message_data['bcc_emailids'])
		->subject($email_message_data['email_subject'].' from instant -- '.date( "Y-m-d H:i:s", time()));
	});

	// return var_dump($messageid1);

	$messageid2 = Mail::later(Carbon::now()->addMinutes(5), $email_template, $email_template_data, function($message) use ($email_message_data){
		$message->to($email_message_data['to'], $email_message_data['reciver_name'])
		->bcc($email_message_data['bcc_emailids'])
		->subject($email_message_data['email_subject'].' delay by 1 min -- '.date( "Y-m-d H:i:s", time()));
	});

	$messageid3 = Mail::later(Carbon::now()->addMinutes(10), $email_template, $email_template_data, function($message) use ($email_message_data){
		$message->to($email_message_data['to'], $email_message_data['reciver_name'])
		->bcc($email_message_data['bcc_emailids'])
		->subject($email_message_data['email_subject'].' delay by 2 min -- '.date( "Y-m-d H:i:s", time()));
	});

	echo "<br>messageid1 -- $messageid1 <br>messageid2 -- $messageid2 <br>messageid3 -- $messageid3";
	// // sleep(60 * 5);
	// echo "<br>messageid1 -- $messageid1 <br>messageid2 -- $messageid2 <br>messageid3 -- $messageid3";
	// echo $deleteid = Queue::deleteMessage('app',$messageid2);

	// echo $deleteid = Queue::deleteReserved('default',$messageid1);

	

	// $sqs = App::make('aws')->get('sqs');
	// try {
	// 	$parm =		array(
	// 	    // QueueUrl is required
	// 		'QueueUrl' => 'https://sqs.ap-southeast-1.amazonaws.com/246537648714/FitQ'.$messageid2,
	// 	    // ReceiptHandle is required
	// 		'ReceiptHandle' => $messageid2,
	// 		);
	// 	$response = $sqs->deleteMessage($parm);
	// 	echo "The message was deleted successfully.";
	// } catch(Exception $e){
	// 	echo "The message could not be deleted. The error message from Amazon is:";
	// 	echo "<br/><br/><i>".$e->getMessage()."</i>";
	// }

	// echo "<br>http://mq-aws-us-east-1.iron.io/projects/549a5af560c8e60009000030/queues/app/messages/$messageid2";
	// $curl = curl_init();
	// curl_setopt($curl, CURLOPT_URL, 'http://testcURL.com');

	// $url =$this->__url.$path;
	//    $ch = curl_init();
	//    curl_setopt($ch, CURLOPT_URL,$url);
	//    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	//    $result = curl_exec($ch);
	//    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	//    curl_close($ch);

	// echo "deleteid - $deleteid";

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

Route::get('sendemailtofinder', function() { 

	// return $currentDateTime = new DateTime('16-03-2015');
	// echo $currentDateTime 		=	Carbon::now()->addDays(1);

	// echo new DateTime('+1 day');

	$tommorowDateTime = date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
	$finders = Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();

	foreach ($finders as $finderid => $trials) {
		echo "<br><br> finderid  ---- ".$finderid;
		// $findertirals  = $trials->toArray();
		print_r($trials);
		echo "<br><br>************************";
	}
});



Route::get('migrateratecards/', array('as' => 'finders.migrateratecards','uses' => 'FindersController@migrateratecards'));


/******************** DEBUG SECTION END HERE ********************/
##############################################################################



Route::get('/home', 'HomeController@getHomePageData');
Route::get('/homev2/{city?}', 'HomeController@getHomePageDatav2');
Route::get('/zumbadiscover', 'HomeController@zumbadiscover');
Route::get('/fitcardpage1finders', 'HomeController@fitcardpagefinders');
Route::get('/specialoffers_finder', 'HomeController@specialoffers_finder');
Route::get('/yfc_finders', 'HomeController@yfc_finders');



##############################################################################
/******************** CUSTOMERS SECTION START HERE ***********************/
Route::get('/autobooktrials/{customerid}',  array('as' => 'customer.autobooktrials','uses' => 'CustomerController@getAutoBookTrials'));


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