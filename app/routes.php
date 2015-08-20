<?php

$monolog = Log::getMonolog();
$syslog = new \Monolog\Handler\SyslogHandler('fitapi');
$formatter = new \Monolog\Formatter\LineFormatter('%channel%.%level_name%: %message% %extra%');
$syslog->setFormatter($formatter);
$monolog->pushHandler($syslog);

App::error(function(Illuminate\Database\Eloquent\ModelNotFoundException $e){
	return Response::json('not found',404);
});


##############################################################################
/******************** DEBUG SECTION START HERE /********************/



Route::get('/', function() { return "laravel 4.2 goes here....";});


Route::get('/testfinder', function() { 

	for ($i=0; $i < 7 ; $i++) { 
		$skip = $i * 1000;
		$items = Finder::active()->take(1000)->skip(0)->get(array('slug'));
		foreach ($items as $item) {  
			$data = $item->toArray();
			$fid = $data['_id'];
			$url =  "http://a1.fitternity.com/finderdetail/".$data['slug'];
			// $fid = 579;
			// $url =  "http://a1.fitternity.com/finderdetail/golds-gym-bandra-west";
			$handlerr = curl_init($url);
			curl_setopt($handlerr,  CURLOPT_RETURNTRANSFER, TRUE);
			$resp = curl_exec($handlerr);
			$ht = curl_getinfo($handlerr, CURLINFO_HTTP_CODE);
			if ($ht == '404'){ echo "\n\n isssue in : fid - $fid url -$url";}
		}
		exit;
	}

});

Route::get('/testsms', function() { 

	$number = '9773348762';
	$msg 	= 'test msg';
	$sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=india123&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);
	$ci = curl_init();
	curl_setopt($ci, CURLOPT_URL, $sms_url);
	curl_setopt($ci, CURLOPT_HEADER, 0);
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ci);
	curl_close($ci);
	return $response;

});

Route::get('/capturedata', function() { 

	$headers = [
	'Content-type'        => 'application/csv',   
	'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',   
	'Content-type'        => 'text/csv',   
	'Content-Disposition' => 'attachment; filename=finderlist2.csv',   
	'Expires'             => '0',   
	'Pragma'              => 'public'
	];

	// $items = Booktrial::take(5)->skip(0)->get();
	// $items = Finder::active()->get();
	// $items = Finder::active()->orderBy('_id')->whereIn('city_id',array(1,2))->get()->count();
	$items = Finder::active()->with('city')->with('category')->orderBy('_id')->take(3000)->skip(3000)->get(array('_id','finder_type','slug','city_id','commercial_type','city','category','category_id','contact'));

	$data = array();
	$output = "ID, SLUG, CITY, CATEGORY, FINDERTYPE, COMMERCIALTYPE \n";

	$fp = fopen('finder2.csv', 'w');

	$header = ["ID", "SLUG", "CITY", "CATEGORY", "FINDERTYPE", "COMMERCIALTYPE", "Contact-address", "Contact-email", "Contact-phone", "finder_vcc_email", "finder_vcc_mobile"  ];
	
	fputcsv($fp, $header);

	foreach ($items as $value) {  

		// $data = $item->toArray();
		// $finderobj = Finder::with('city')->findOrFail((int)$value->finder_id);
		// $finder = $finderobj->toArray();
		// echo $response = $capture->update($data);
		$commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'free special', 3 => 'commission on sales');
		$FINDERTYPE 		= ($value->finder_type == 1) ? 'paid' : 'free';
		$commercial_type 	= $commercial_type_arr[intval($value->commercial_type)];
		$cityname 			= $value->city->name;
		$category 			= $value->category->name;
		// $output .= "$value->_id, $value->slug, $cityname, $category, $FINDERTYPE, $commercial_type"."\n";

		$fields = [$value->_id,
				$value->slug,
				$cityname,
				$category,
				$FINDERTYPE,
				$commercial_type,
				$value->contact['address'],
				$value->contact['email'],
				$value->contact['phone'],
				$value->finder_vcc_email,
				$value->finder_vcc_mobile
		];
		return $fields;
		fputcsv($fp, $fields);
		// exit();
	}

	fclose($fp);
	
	return "done";
	return Response::make(rtrim($output, "\n"), 200, $headers);

});



Route::get('/updatefinder', function() { 

	


	// $items = Finder::active()->take(3000)->skip(0)->get();
	$items = Service::active()->take(3000)->skip(0)->get();

	$finderdata = array();
	foreach ($items as $item) {  
		$data 	= $item->toArray();

		// $august_available_dates = $data['august_available_dates'];
		// $august_available_dates_new = [];

		// foreach ($august_available_dates as $day){
		// 	$date = explode('-', $day);
		// 	// return ucfirst( date("l", strtotime("$date[0]-08-2015") )) ;
		// 	array_push($august_available_dates_new, $date[0].'-'.ucfirst( date("l", strtotime("$date[0]-08-2015") )) );

		// }
		// // return $august_available_dates_new;
		// array_set($finderdata, 'august_available_dates', $august_available_dates_new);

		$finder = Service::findOrFail($data['_id']);
		$finderratecards = [];
        foreach ($data['ratecards'] as $key => $value) {
        	if((isset($value['price']) && $value['price'] != '0')){
	            $ratecard = [
	            'order'=> (isset($value['order']) && $value['order'] != '') ? $value['order'] : '0',
	            'type'=> (isset($value['type']) && $value['type'] != '') ? $value['type'] : '',
	            'duration'=> (isset($value['duration']) && $value['duration'] != '') ? $value['duration'] : '',
	            'price'=> (isset($value['price']) && $value['price'] != '') ? $value['price'] : '',
	            'special_price'=> (isset($value['special_price']) && $value['special_price'] != '') ? $value['special_price'] : '',
	            'remarks'=> (isset($value['remarks']) && $value['remarks'] != '') ? $value['remarks'] : '',
	            'show_on_fitmania'=> (isset($value['show_on_fitmania']) && $value['show_on_fitmania'] != '') ? $value['show_on_fitmania'] : 'no',
	            'direct_payment_enable'=> '0'
	            ];
	            array_push($finderratecards, $ratecard);
        	}
        }

        array_set($finderdata, 'ratecards', array_values($finderratecards));
		$response = $finder->update($finderdata);

		print_pretty($response);
	}


	// $items = Finder::active()->orderBy('_id')->whereIn('category_id',array(22))->get();
	// 	//exit;				
	// $finderdata = array();
	// foreach ($items as $item) {  
	// 	$data 	= $item->toArray();
	// 		//print_pretty($data);
	// 	array_set($finderdata, 'status', '0');
	// 	$finder = Finder::findOrFail($data['_id']);
	// 	$response = $finder->update($finderdata);
	// 	print_pretty($response);
	// }
});


Route::get('/testdate', function() { 

	return Carbon::now();
	$isodate = '2015-03-10T13:00:00.000Z';
	$actualdate =  \Carbon\Carbon::now();
	return \Carbon\Carbon::now();
	return Finder::findOrFail(1)->toArray();
	return  date( "Y-m-d H:i:s", strtotime("2015-03-10T13:00:00.000Z"));
	//convert iso date to php datetime
	return "laravel 4.2 goes here ....";

});

Route::get('/testpushnotification', function() { 

	// PushNotification::app('appNameAndroid')
	// 				->to('APA91bG_gkVGxr6atdmGbMGGHWLP82U2o91HjU-UKu27gtEFy1a-9TVXYg7gVr0Q_DLEPEtpE-0z6K5f2nuL9i_SPeRySLy0Typtt7ZjQRi4yHc49R5EQg44gAGuovNpP76UbC8wuIL8VCjgNVXD2UEXmwnVFvQJDw')
	// 				->send('Hello World, i`m a push message');

	$response = PushNotification::app('appNameAndroid')
	->to('APA91bF5pPDQbftrS4SppKxrgZWsBUhHrtCkjdfwZXXrazVD9c-qvGvo8MejFGnZ3iHrhOoKyMQKeX3yHrtY_N4xC0ZHVYfHFmgHdaxw_WWOKP5YTdUdDv0Enr-1CBO2q411M33YKiHYl6PJB5z12W3WNbu2Pphz8A')
	->send('This is a simple message, takes use to homepage',array( 
		'title' => "Fitternity",
		'type' => "generic"
		));	
	return Response::json($response,200);	


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




Route::get('/trialcsv', function() { 

	$headers = [
	'Content-type'        => 'application/csv',   
	'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',   
	'Content-type'        => 'text/csv',   
	'Content-Disposition' => 'attachment; filename=trialsdiff.csv',   
	'Expires'             => '0',   
	'Pragma'              => 'public'
	];

	$booktrialslotcnt = Booktrial::where('booktrial_type', 'auto')->where('source', 'website')->skip(0)->take(3000)->get();

	// return $booktrialslotcnt;
	// return $finders;sourceja
	$output = "ID, customer name,customer email, Created At, Updated At, Schedule Date, Diff date \n";
	$emails = ['chaithanya.padi@gmail.com','chaithanyapadi@fitternity.com','sanjay.id7@gmail.com','sanjay.fitternity@gmail.com','utkarsh2arsh@gmail.com','ut.mehrotra@gmail.com','neha@fitternity.com','jayamvora@fitternity.com'];
	foreach ($booktrialslotcnt as $key => $value) {
		$dStart = strtotime($value->created_at);
		$dEnd  = strtotime($value->schedule_date);
		$dDiff = $dEnd - $dStart;
		// $dDiff = $dStart->diff($dEnd);
		if(floor($dDiff/(60*60*24)) > 0 && floor($dDiff/(60*60*24)) < 50){
			if(!in_array($value->customer_email, $emails)){
				$output .= "$value->_id,$value->customer_name,$value->customer_email, $value->created_at, $value->updated_at, ".$value->schedule_date.", ".floor($dDiff/(60*60*24))."\n";
			}
		}
	}
	
	return Response::make(rtrim($output, "\n"), 200, $headers);

});

Route::get('/findercsv', function() { 

	$headers = [
	'Content-type'        => 'application/csv',   
	'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',   
	'Content-type'        => 'text/csv',   
	'Content-Disposition' => 'attachment; filename=freefinders.csv',   
	'Expires'             => '0',   
	'Pragma'              => 'public'
	];

	$finders 		= 	Blog::active()->get();

	// return $finders;
	$output = "ID, URL, \n";

	foreach ($finders as $key => $value) {
		$output .= "$value->_id, http://www.fitternity.com/article/$value->slug, "."\n";
	}
	
	return Response::make(rtrim($output, "\n"), 200, $headers);

	$finders 		= 	Finder::active()
						// ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
						// ->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
						// ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
						// ->skip(0)
						// ->take(3000)
						->where('finder_type', 1)
						->get();

	// return $finders;
	$output = "ID, SLUG, CITY, TYPE, EMAIL, TYPE \n";

	foreach ($finders as $key => $value) {
		$type = ($value->finder_type == '0') ? 'Free' : 'Paid';
		$output .= "$value->_id, $value->slug, ".$value->city->name.", ".$type.", ".$value->finder_vcc_email ."\n";
	}

	
	return Response::make(rtrim($output, "\n"), 200, $headers);

});

/******************** DEBUG SECTION END HERE ********************/
##############################################################################


Route::get('/home', 'HomeController@getHomePageData');
Route::get('/homev2/{city?}', 'HomeController@getHomePageDatav2');
Route::get('/homev3/{city?}', 'HomeController@getHomePageDatav3');
Route::get('/zumbadiscover', 'HomeController@zumbadiscover');
Route::get('/fitcardpage1finders', 'HomeController@fitcardpagefinders');

Route::get('/specialoffers_finder', 'HomeController@specialoffers_finder');
Route::get('/yfc_finders', 'HomeController@yfc_finders');
Route::get('landingzumba', 'HomeController@landingzumba');

Route::get('/fitcardfinders', 'HomeController@fitcardfinders');
Route::post('/fitcardfindersv1', 'HomeController@fitcardfindersV1');

Route::get('/getcollecitonnames/{city?}', 'HomeController@getcollecitonnames');
Route::get('/getcollecitonfinders/{city}/{slug}', 'HomeController@getcollecitonfinders');
Route::get('/getlocations/{city?}', 'HomeController@getCityLocation');

Route::post('getlandingpagefinders/', 'HomeController@getLandingPageFinders');




##############################################################################
/******************** CUSTOMERS SECTION START HERE ***********************/
Route::get('/autobooktrials/{customeremail}',  array('as' => 'customer.autobooktrials','uses' => 'CustomerController@getAutoBookTrials'));
Route::get('/fitcardautobooktrials/{customeremail}',  array('as' => 'customer.fitcardautobooktrials','uses' => 'CustomerController@getFitcardAutoBookTrials'));
Route::get('/autobooktrial/{trialid}',  array('as' => 'customer.autobooktrial','uses' => 'CustomerController@getAutoBookTrial'));
Route::post('capturepayment',  array('as' => 'customer.capturepayment','uses' => 'CustomerController@capturePayment'));

Route::post('generatefitcardcodorder',  array('as' => 'customer.generatefitcardcodorder','uses' => 'CustomerController@generateFitCardCodOrder'));
Route::post('generatefitcardtmporder',  array('as' => 'customer.generatefitcardtmporder','uses' => 'CustomerController@generateFitCardTmpOrder'));
Route::post('captureorderpayment',  array('as' => 'customer.captureorderpayment','uses' => 'CustomerController@captureOrderPayment'));


Route::post('customerregister', array('as' => 'customer.customerregister','uses' => 'CustomerController@register'));
Route::post('customerlogin', array('as' => 'customer.customerlogin','uses' => 'CustomerController@customerLogin'));
Route::post('customerforgotpasswordemail', array('as' => 'customer.customerforgotpasswordemail','uses' => 'CustomerController@forgotPasswordEmail'));
Route::post('customerforgotpassword', array('as' => 'customer.customerforgotpassword','uses' => 'CustomerController@forgotPassword'));
Route::post('customerforgotpasswordemailapp', array('as' => 'customer.customerforgotpasswordemailapp','uses' => 'CustomerController@forgotPasswordEmailApp'));
Route::post('customervalidateotp', array('as' => 'customer.customervalidateotp','uses' => 'CustomerController@validateOtp'));

Route::group(array('before' => 'validatetoken'), function() {

	Route::get('validatetoken', array('as' => 'customer.validatetoken','uses' => 'CustomerController@validateToken'));
	Route::post('customerresetpassword', array('as' => 'customer.customerresetpassword','uses' => 'CustomerController@resetPassword'));
	Route::get('customerlogout', array('as' => 'customer.validatetokencustomerlogout','uses' => 'CustomerController@customerLogout'));
	Route::post('customerupdate', array('as' => 'customer.customerupdate','uses' => 'CustomerController@customerUpdate'));

});

/******************** CUSTOMERS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** ORDERS SECTION START HERE ***********************/

Route::post('generatecodorder',  array('as' => 'orders.generatecodorder','uses' => 'OrderController@generateCodOrder'));
Route::post('generatetmporder',  array('as' => 'orders.generatetmporder','uses' => 'OrderController@generateTmpOrder'));
Route::post('captureorderstatus',  array('as' => 'orders.captureorderstatus','uses' => 'OrderController@captureOrderStatus'));
Route::post('capturefailsorders',  array('as' => 'orders.capturefailsorders','uses' => 'OrderController@captureFailOrders'));


Route::post('buyarsenalmembership',  array('as' => 'orders.buyarsenalmembership','uses' => 'OrderController@buyArsenalMembership'));





/******************** ORDERS SECTION END HERE ********************/
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
Route::get('checkbooktrialdaliysummary/{date}', array('as' => 'finders.checkbooktrialdaliysummary','uses' => 'FindersController@checkbooktrialdaliysummary'));

Route::get('reviewlisting/{finderid}/{from?}/{size?}', array('as' => 'finders.reviewlisting','uses' => 'FindersController@reviewListing'));
Route::post('addreview', array('as' => 'finders.addreview','uses' => 'FindersController@addReview'));
Route::get('reviewdetail/{id}', array('as' => 'review.reviewdetail','uses' => 'FindersController@detailReview'));
Route::get('getfinderreview/{slug}', array('as' => 'finders.getfinderreview','uses' => 'FindersController@getFinderReview'));

/******************** FINDERS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** BLOGS SECTION START HERE ********************/
Route::get('/blogs/{offset}/{limit}', 'BlogsController@getBlogs');
Route::get('blogdetail/{slug}', array('as' => 'blogs.blogdetail','uses' => 'BlogsController@blogdetail'));
Route::get('/blogs/{cat}', 'BlogsController@getCategoryBLogs');
Route::get('/updateblogdate', 'BlogsController@updateblogdate');
Route::post('/getblogrelatedfinder', 'BlogsController@getblogRelatedFinder');
Route::post('addcomment', array('as' => 'blogs.addcomment','uses' => 'BlogsController@addComment'));
Route::get('getblogcomment/{slug}', array('as' => 'blogs.getblogcomment','uses' => 'BlogsController@getBlogComment'));

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

Route::post('/workoutsessionsearch', 'SearchServicesController@getWorkoutsessions');
Route::post('/ratcardsearch', 'SearchServicesController@getRatecards');
Route::post('/getnearbytrials', 'SearchServicesController@geoLocationService');
/******************** SEARCH SECTION END HERE ********************/
##############################################################################




##############################################################################
/******************** SERVICE SECTION START HERE ********************/

Route::get('updateserviceslug/', array('as' => 'service.updateserviceslug','uses' => 'ServiceController@updateSlug'));
Route::get('servicedetail/{id}', array('as' => 'service.servicedetail','uses' => 'ServiceController@serviceDetail'));
Route::get('servicecategorys', array('as' => 'service.servicecategorys','uses' => 'ServiceController@getServiceCategorys'));



/******************** SERVICE SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** SCHEDULE BOOK TRIAL SECTION START HERE ***********************/
Route::get('getschedulebooktrial/{finderid?}/{date?}', array('as' => 'finders.getschedulebooktrial','uses' => 'SchedulebooktrialsController@getScheduleBookTrial'));
Route::get('booktrial/{finderid?}/{date?}', array('as' => 'finders.getbooktrial','uses' => 'SchedulebooktrialsController@getBookTrial'));
Route::post('booktrial', array('as' => 'finders.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrial'));
Route::post('manualbooktrial', array('as' => 'finders.storemanualbooktrial','uses' => 'SchedulebooktrialsController@manualBookTrial'));
Route::post('manual2ndbooktrial', array('as' => 'finders.storemanual2ndbooktrial','uses' => 'SchedulebooktrialsController@manual2ndBookTrial'));

Route::post('storebooktrial', array('as' => 'customer.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialV2'));

Route::get('gettrialschedule/{finderid}/{date}', array('as' => 'services.gettrialschedule', 'uses' => 'SchedulebooktrialsController@getTrialSchedule'));
Route::get('getworkoutsessionschedule/{finderid}/{date}', array('as' => 'services.getworkoutsessionschedule', 'uses' => 'SchedulebooktrialsController@getWorkoutSessionSchedule'));
Route::get('getserviceschedule/{serviceid}/{date?}/{noofdays?}', array('as' => 'services.getserviceschedule','uses' => 'SchedulebooktrialsController@getServiceSchedule'));
/*Route::get('booktrialff', array('as' => 'schedulebooktrials.booktrialff','uses' => 'SchedulebooktrialsController@bookTrialFintnessForce'));*/
Route::get('updateappointmentstatus', array('as' => 'customer.updateappointmentstatus','uses' => 'SchedulebooktrialsController@updateAppointmentStatus'));

/******************** SCHEDULE BOOK TRIAL SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** SENDING EMAIL STUFFS SECTION START HERE ********************/
Route::post('/notify/{notifytype}','EmailSmsApiController@triggerNotify');
Route::post('/email/requestcallback','EmailSmsApiController@RequestCallback');
// Route::post('/email/booktrial','EmailSmsApiController@BookTrial');
// Route::post('/email/extrabooktrial','EmailSmsApiController@extraBookTrial');
Route::post('/email/finderlead','EmailSmsApiController@FinderLead');
Route::post('/email/emailfinder','EmailSmsApiController@EmailSmsFinder');
// Route::post('/email/newfinder','EmailSmsApiController@findercreated');
// Route::post('/email/finderreview','EmailSmsApiController@ReviewOnfinder');
// Route::post('/email/createcommunity','EmailSmsApiController@CreateCommunity');
// Route::post('/email/joincommuntiy','EmailSmsApiController@JoinCommunity');
// Route::post('/email/interestcommunity','EmailSmsApiController@InterestCommunity');
Route::post('/email/commentonblog','EmailSmsApiController@CommentOnBlog');
Route::post('/subscribenewsletter','EmailSmsApiController@SubscribeNewsletter');
// Route::post('/email/joinevent','EmailSmsApiController@JoinEvent');
// Route::post('/email/createevent','EmailSmsApiController@CreateEvent');
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




##############################################################################
/******************** CACHE SECTION START HERE *******************************/

Route::get('/flushtag/{tag}', 'CacheApiController@flushTag');
Route::get('/flushtagkey/{tag}/{key}', 'CacheApiController@flushTagKey');
Route::get('/flushall', 'CacheApiController@flushAll');

##############################################################################
/******************** CACHE SECTION END HERE *******************************/



##############################################################################
/******************** FITMANIA SECTION START HERE *******************************/

Route::get('fitmania/{city?}/{from?}/{size?}/{location_cluster?}', 'FitmaniaController@getDealOfDay');
Route::get('fitmaniahealthytiffin/{city?}/{from?}/{size?}/{category_cluster?}', 'FitmaniaController@getDealOfDayHealthyTiffin');

Route::get('fitmaniazumba/{city?}/{location_cluster?}', 'FitmaniaController@getDealOfDayZumba');
Route::get('fitmaniadeals/{startdate?}/{enddate?}/{city?}/{location_cluster?}', 'FitmaniaController@getDealOfDayBetweenDate');

Route::post('fitmania', 'FitmaniaController@fitmaniaServices');

Route::post('buyfitmaniaservice', 'FitmaniaController@buyService');
Route::post('buyfitmaniaservicemembership', 'FitmaniaController@buyServiceMembership');
Route::post('buyfitmaniahealthytiffin', 'FitmaniaController@buyServiceHealthyTiffin');


Route::get('resendemails', 'FitmaniaController@resendEmails');
Route::get('resendfinderemail', 'FitmaniaController@resendFinderEmail');
Route::get('resendcustomeremail', 'FitmaniaController@resendCustomerEmail');


##############################################################################
/******************** FITMANIA SECTION END HERE *******************************/

