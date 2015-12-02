<?php


Route::get('/migrateratecards', function() { 

	$items = Service::active()->orderBy('_id')->lists('_id');

	$Servicedata = array();

	foreach ($items as $key => $item) {

		$service_id = intval($item);
		$Service 	=	Service::find($service_id);

		if($Service){
			$data 		=	$Service->toArray();

			if(count($data['ratecards']) > 0 && isset($data['ratecards'])){

				foreach ($data['ratecards'] as $key => $value) {

					if(isset($value['featured_offer']) && $value['featured_offer'] == '1'){
						$show_in_offers = '1';
					}

					$ratecard = [
					'service_id'=> $service_id,
					'offers'=> [],
					'order'=> (isset($value['order'])) ? $value['order'] : '0',
					'type'=> (isset($value['type'])) ? $value['type'] : '',
					'price'=> (isset($value['price'])) ? $value['price'] : '',
					'special_price'=> (isset($value['special_price'])) ? $value['special_price'] : '',
					'remarks'=> (isset($value['remarks'])) ? $value['remarks'] : '',

					

					
					'duration'=> (isset($value['duration'])) ? $value['duration'] : '',
					'days'=> intval($days),
					'sessions'=> intval($sessions),
					'show_on_fitmania'=> (isset($value['show_on_fitmania'])) ? $value['show_on_fitmania'] : '',
					'direct_payment_enable'=> (isset($value['direct_payment_enable'])) ? $value['direct_payment_enable'] : '0',
					'featured_offer'=> (isset($value['featured_offer'])) ? $value['featured_offer'] : '0'
					];
					array_push($finderratecards, $ratecard);

				}

				$ratecard = [
				'order'=> (isset($value['order'])) ? $value['order'] : '0',
				'type'=> (isset($value['type'])) ? $value['type'] : '',
				'duration'=> (isset($value['duration'])) ? $value['duration'] : '',
				'days'=> intval($days),
				'sessions'=> intval($sessions),
				'price'=> (isset($value['price'])) ? $value['price'] : '',
				'special_price'=> (isset($value['special_price'])) ? $value['special_price'] : '',
				'remarks'=> (isset($value['remarks'])) ? $value['remarks'] : '',
				'show_on_fitmania'=> (isset($value['show_on_fitmania'])) ? $value['show_on_fitmania'] : '',
				'direct_payment_enable'=> (isset($value['direct_payment_enable'])) ? $value['direct_payment_enable'] : '0',
				'featured_offer'=> (isset($value['featured_offer'])) ? $value['featured_offer'] : '0'
				];

			}
		}
	}
});



Route::get('/updateservices', function() { 

	$items = Service::active()->orderBy('_id')->lists('_id');
	// return $items;
	// return 	$Service 	=	Service::find(intval(4));

	$Servicedata = array();

	foreach ($items as $key => $item) {
		// $service_trialschedules = $service_workoutsessionschedules = array();
		echo "<br>id - $item";
		$Service 	=	Service::find(intval($item));
		if($Service){
			// return $Service;
			$data 		=	$Service->toArray();

			$service_trialschedules = [];
			if(count($data['trialschedules']) > 0 && isset($data['trialschedules'])){
				foreach ($data['trialschedules'] as $key => $trials) {
					$weekwiseslot = [];
					$weekwiseslot['weekday'] 	=	$trials['weekday'];
					$weekwiseslot['slots']		=	[];
					foreach ($trials['slots'] as $k => $val) {
						$newslot = ['start_time' => $val['start_time'], 
						'start_time_24_hour_format' => (string)$val['start_time_24_hour_format'], 
						'end_time' => $val['end_time'], 
						'end_time_24_hour_format' => (string) $val['end_time_24_hour_format'], 
						'slot_time' => $val['slot_time'], 
						'limit' => (intval($val['limit'])) ? intval($val['limit']) : 0,
						'price' => (intval($val['price']) == 100) ? 0 : intval($val['price']) 
						];
						array_push($weekwiseslot['slots'], $newslot);
					}
					array_push($service_trialschedules, $weekwiseslot);
				}
			}

			$service_workoutsessionschedules = [];
			if(count($data['workoutsessionschedules']) > 0 && isset($data['workoutsessionschedules'])){
				foreach ($data['workoutsessionschedules'] as $key => $trials) {
					$weekwiseslot = [];
					$weekwiseslot['weekday'] 	=	$trials['weekday'];
					$weekwiseslot['slots']		=	[];
					foreach ($trials['slots'] as $k => $val) {
						$newslot = ['start_time' => $val['start_time'], 
						'start_time_24_hour_format' => $val['start_time_24_hour_format'], 
						'end_time' => $val['end_time'], 
						'end_time_24_hour_format' => $val['end_time_24_hour_format'], 
						'slot_time' => $val['slot_time'], 
						'limit' => (intval($val['limit'])) ? intval($val['limit']) : 0,
						'price' => (intval($val['price']) == 100) ? 0 : intval($val['price']) 
						];
						array_push($weekwiseslot['slots'], $newslot);
					}
					array_push($service_workoutsessionschedules, $weekwiseslot);
				}
			}

			array_set($Servicedata, 'workoutsessionschedules', $service_workoutsessionschedules);
			array_set($Servicedata, 'trialschedules', $service_trialschedules);
			$response = $Service->update($Servicedata);
			echo "<br>$response";
			// if($val == 4){ exit(); }
			// exit();
		}
	}

});


Route::get('/jwt/create', function() { 
	$password_claim = array(
		"iat" => Config::get('jwt.web.iat'),
		"exp" => Config::get('jwt.web.exp'),
		"data" => 'data'
		);
	$password_key = Config::get('jwt.web.key');
	$password_alg = Config::get('jwt.web.alg');
	$token = JWT::encode($password_claim,$password_key,$password_alg);
	return $token;
});

Route::group(array('before' => 'jwt'), function() {
	Route::get('/jwt/check', function() { 
		return "security is working";
	});
	
});


Route::get('/hesh', function() { 
	/* Queue:push(function($job) use ($data){ $data['string']; $job->delete();  }); */
	Queue::connection('redis')->push('LogFile', array( 'string' => 'new testpushqueue instantly -- '.time()));
	//Queue::later(Carbon::now()->addMinutes(1),'WriteFile', array( 'string' => 'new testpushqueue delay by 1 min time -- '.time()));
	//Queue::later(Carbon::now()->addMinutes(2),'WriteFile', array( 'string' => 'new testpushqueue delay by 2 min time -- '.time()));
	return "successfully test push queue with dealy job as well....";
});

class LogFile {

	public function fire($job, $data){
		/*$job_id = $job->getJobId(); 
		File::append(app_path().'/queue.txt', $data['string']." ------ $job_id".PHP_EOL); */
		$job->delete();  
	}

}


Route::get('/', function() {  return "laravel 4.2 goes here...."; });



Route::get('/testfinder', function() { 

	return $items = Finder::where('status', '1')->take(10000)->skip(0)->groupBy('slug')->get(array('slug'));

	$slugArr = [];
	$duplicateSlugArr = [];
	foreach ($items as $item) {  

		Finder::destroy(intval($item->_id));
		// if (!in_array($item->slug, $slugArr)){
		// 	array_push($slugArr, $item->slug);
		// }else{
		// 	array_push($duplicateSlugArr,  $item->slug);
		// }

	}

	return $duplicateSlugArr;

	exit;

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

/*Route::get('/testsms', function() { 

	$number = '9773348762';
	$msg 	= 'test msg';
	$sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);
	$ci = curl_init();
	curl_setopt($ci, CURLOPT_URL, $sms_url);
	curl_setopt($ci, CURLOPT_HEADER, 0);
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ci);
	curl_close($ci);
	return $response;

});*/


Route::get('export', function() { 

	$headers = [
	'Content-type'        => 'application/csv'
	,   'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
	,   'Content-type'        => 'text/csv'
	,   'Content-Disposition' => 'attachment; filename=export_finder.csv'
	,   'Expires'             => '0'
	,   'Pragma'              => 'public'
	];

	$output = "ID,  NAME, COMMERCIALTYPE Type, EMAIL, No of TICKETS BOOKED, TICKET RATE, ORDER TOTAL\n";
	$items = Finder::where('status', '1')->where('status', '1')->take(10000)->skip(0)->get();

	foreach ($orders as $key => $value) {

		// $output .= "$value[id], $value[first_name] $value[last_name], $value[contact], $value[email], $value[quantity], $value[price], $value[total]\n";
		$output .= "$value[id], $value[first_name] $value[last_name], $value[contact], $value[email], $value[quantity], $value[price], $value[total]\n";
	}

	return Response::make(rtrim($output, "\n"), 200, $headers);
});



Route::get('updateduration', function() { 

	$items = Order::where('order_action', 'bought')->get();
	foreach ($items as $value) {  
		$order 		=	Order::findOrFail(intval($value->_id));
		$response 	= 	$order->update(['status' => '1']);
	}
	exit();

	$orderids = [3811,3813,3815,3816,3819,3821,3830,3836,3837,3839,3841,3847,3848,3860,3861,3866,3867,3868,3870,3871,3874,3886,3891,3903,3906,3908,3911,3919,3923,3928,3932,3940,3941,3945,3948,3952,3964,3965,3967,3968,3972,3974,3980,3983,3991,3995,3997,4004,4006,4008,4013,4015,4024,4028,4042,4055,4067,4069,4073,4077,4081,4082,4083,4084,4106,4107,4108,4112,4173,4181,4194,4202,4233,4283,4301,4590,4705,4710,4757,4802,4844,4862,4868,4871,4872,4873,4878,4884,4896,4913,4924,4937,4981,4987,4991,4992,4997,5003,5014,5017,5019,5022,5024,5035,5044,5053,5058,5102,5109,5112,5113,5172,5188,5196,5281,5283,5288,5289,5290,5292,5293,5295,5296,5297,5298,5299,5300,5302,5303,5306,5308,5309,5310,5314,5315,5328,5330,5335,5337,5340,5343,5344,5345,5346,5347,5352,5354,5355,5359,5362,5363,5364,5365,5370,5372,5373,5374,5375,5377,5379,5381,5382,5383,5390,5392,5393,5394,5396,5397,5400,5401,5403,5410,5412,5419,5420,5421,5429,5434,5436,5437,5439,5440,5445,5450,5454,5458,5459,5460,5461,5463,5464,5465,5468,5469,5470,5471,5472,5473,5476,5486,5487,5488,5491,5496,5499,5500,5501,5502,5503,5504,5506,5512,5513,5516,5517,5522,5532,5559,5569,5570,5571,5572,5574,5578,5581,5582,5585,5587,5588,5589,5590,5591,5592,5594,5597,5598,5608,5609,5610,5611,5612,5613,5615,5616,5617,5619,5620,5622,5623,5625,5627,5628,5630,5632,5633,5634,5644,5645,5646,5647,5648,5652,5653,5655,5659,5660,5661,5669,5670,5675,5678,5679,5681,5682,5683,5685,5687,5688,5689,5693,5695,5696,5697,5700,5702,5703,5708,5710,5713,5718,5721,5724,5727,5728,5736,5740,5741];
	
	$items = Order::whereIn('_id', $orderids)->get();

	$fp = fopen('orderlatest.csv', 'w');
	$header = ["ID", "NAME", "EMAIL", "NUMBER", "TYPE" , "ADDRESS"  ];
	fputcsv($fp, $header);
	
	foreach ($items as $value) {  
		$fields = [$value->_id, $value->customer_name, $value->customer_email, $value->customer_phone,  $value->payment_mode, $value->customer_location];
		fputcsv($fp, $fields);
	}
	fclose($fp);
	return 'done';
	

	$items = Duration::active()->get();
	$fp = fopen('updateduration.csv', 'w');
	$header = ["ID", "NAME", "SLUG", "DAYS", "SESSIONS"  ];
	
	fputcsv($fp, $header);
	
	foreach ($items as $value) {  
		$fields = [$value->_id, $value->name, $value->slug, $value->days, $value->sessions];
		// return $fields;
		fputcsv($fp, $fields);
	}
	fclose($fp);
	return 'done';
	return Response::make(rtrim($output, "\n"), 200, $headers);

	
	// foreach ($items as $value) {  
	// 	$duration 		=	Duration::findOrFail(intval($value->_id));
	// 	$durationData 	=	[];
	// 	$itemArr 		= 	explode('-', $value->slug);

	// 	if(str_contains($value->slug , 'day')){
	// 		$days 					=  head($itemArr);
	// 		$durationData['days'] 	=  intval($days);
	// 	}

	// 	if(str_contains($value->slug , 'week')){
	// 		$days 					=  head($itemArr) * 7;
	// 		$durationData['days'] 	=  intval($days);
	// 	}

	// 	if(str_contains($value->slug , 'month')){
	// 		$days			 		=  head($itemArr) * 30;
	// 		$durationData['days'] 	=  intval($days);
	// 	}

	// 	if(str_contains($value->slug , 'year')){
	// 		$days 					=  head($itemArr) * 30 * 12;
	// 		$durationData['days'] 	=  intval($days);
	// 	}

	// 	if(str_contains($value->slug , 'session')){
	// 		if(count($itemArr) > 3){
	// 			// echo $value->_id;
	// 			$sessions 					=  $itemArr[3];
	// 			$durationData['sessions'] 	=  intval($sessions);
	// 		}
	// 	}

	// 	if($key = array_search('sessions', $itemArr)){
	// 		if($key == 1){
	// 			echo "<br>"; print_r($key);
	// 			$sessions 					=  $itemArr[0];
	// 			$durationData['sessions'] 	=  intval($sessions);
	// 			$durationData['days'] 		=  0;
	// 		}

	// 		if($key == 3){
	// 			echo "<br>"; print_r($key);
	// 			$sessions 					=  $itemArr[2];
	// 			$durationData['sessions'] 	=  intval($sessions);
	// 			// $durationData['days'] 		=  0;
	// 		}

	// 	}

	// 	// $durationData['days'] 	=  0;
	// 	// $durationData['sessions'] 	=  0;

	// 	$response = $duration->update($durationData);
	// }

});


Route::get('capturedata', function() { 


	// $items = Service::active()->where('trialschedules', 'size', 0)->get();
	// $fp = fopen('serviceslive1.csv', 'w');
	// $header = ["ID", "SERVICENAME", "FINDERID", "FINDERNAME", "COMMERCIALTYPE" ];
	// fputcsv($fp, $header);

	// foreach ($items as $value) {  
	// 	$finder = Finder::findOrFail(intval($value->finder_id));

	// 	$commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'free special', 3 => 'commission on sales');
	// 	$commercial_type 	= $commercial_type_arr[intval($finder->commercial_type)];

	// 	$fields = [$value->_id,
	// 	$value->name,
	// 	$value->finder_id,
	// 	$finder->slug,
	// 	$commercial_type
	// 	];
	// 	// return $fields;
	// 	fputcsv($fp, $fields);
	// 	// exit();
	// }

	// fclose($fp);
	// return "done";
	// return Response::make(rtrim($output, "\n"), 200, $headers);

	// $items = Booktrial::take(5)->skip(0)->get();
	// $items = Finder::active()->get();
	// $items = Finder::active()->orderBy('_id')->whereIn('city_id',array(1,2))->get()->count();
	$items = Finder::active()->with('city')->with('location')->with('category')
	->whereIn('category_id',array(41))
	->orderBy('_id')->take(3000)->skip(0)
	->get(array('_id','finder_type','slug','city_id','commercial_type','city','category','category_id','location_id','contact','locationtags'));

	$data = array();

	$fp = fopen('newfinder.csv', 'w');
	$header = ["ID", "SLUG", "CITY", "CATEGORY",  "LOCAITONTAG", "FINDERTYPE", "COMMERCIALTYPE", "Contact-address", "Contact-email", "Contact-phone", "finder_vcc_email", "finder_vcc_mobile"  ];
	fputcsv($fp, $header);

	foreach ($items as $value) {  
		$commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'free special', 3 => 'commission on sales');
		$FINDERTYPE 		= ($value->finder_type == 1) ? 'paid' : 'free';
		$commercial_type 	= $commercial_type_arr[intval($value->commercial_type)];
		$cityname 			= $value->city->name;
		$category 			= $value->category->name;
		$location 			= $value->location->name;
		// $output .= "$value->_id, $value->slug, $cityname, $category, $FINDERTYPE, $commercial_type"."\n";

		$fields = [
		$value->_id,
		$value->slug,
		$cityname,
		$category,
		$location,
		$FINDERTYPE,
		$commercial_type,
		$value->contact['address'],
		$value->contact['email'],
		$value->contact['phone'],
		$value->finder_vcc_email,
		$value->finder_vcc_mobile
		];
		// return $fields;
		fputcsv($fp, $fields);
		// exit();
	}

	fclose($fp);
	
	return "newfinder";
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
				'direct_payment_enable'=> (isset($value['direct_payment_enable']) && $value['direct_payment_enable'] != '') ? $value['direct_payment_enable'] : '0'
				];
				array_push($finderratecards, $ratecard);
			}
		}

		array_set($finderdata, 'ratecards', array_values($finderratecards));
		$response = $finder->update($finderdata);

		print_pretty($response);
	}

	
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

	if(filter_var(trim('ut.mehrotra@gmail.com'), FILTER_VALIDATE_EMAIL) === false){
		echo 'not vaild';
	}else{
		echo ' vaild';
	}

	exit();
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

Route::get('exportorders/', array('as' => 'orders.exportorders','uses' => 'OrderController@exportorders'));

Route::get('/debug/invalidfinderstats',  array('as' => 'debug.invalidfinderstats','uses' => 'DebugController@invalidFinderStats'));
Route::get('/debug/sendbooktrialdaliysummary',  array('as' => 'debug.sendbooktrialdaliysummary','uses' => 'DebugController@sendbooktrialdaliysummary'));
Route::get('/debug/sendbooktrialdaliysummaryv1',  array('as' => 'debug.sendbooktrialdaliysummaryv1','uses' => 'DebugController@sendbooktrialdaliysummaryv1'));
Route::get('/debug/vendorstats',  array('as' => 'debug.vendorstats','uses' => 'DebugController@vendorStats'));
Route::get('/debug/getvendors',  array('as' => 'debug.getvendors','uses' => 'DebugController@getVendors'));
Route::get('/debug/vendorsbymonth',  array('as' => 'debug.vendorsByMonth','uses' => 'DebugController@vendorsByMonth'));
Route::get('/debug/gurgaonmigration',  array('as' => 'debug.gurgaonmigration','uses' => 'DebugController@gurgaonmigration'));
Route::get('/debug/movekickboxing',  array('as' => 'debug.movekickboxing','uses' => 'DebugController@movekickboxing'));
Route::get('/debug/updateorderamount',  array('as' => 'debug.updateOrderamount','uses' => 'DebugController@updateOrderAmount'));
Route::get('/debug/vendorstatsmeta',  array('as' => 'debug.vendorStatsMeta','uses' => 'DebugController@vendorStatsMeta'));




Route::get('/cleandata', function() { 
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 161)
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Altitude Training is 151 as sub
		//Cross Functional Training as root 5
		$servicedata = array();
		array_set($servicedata,'servicecategory_id', 5);
		array_set($servicedata,'servicesubcategory_id', 151);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/cleandata1', function() { 
	//Danzo Fit clean up
	//delete servicecategory 120
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 120)
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();
		array_set($servicedata,'servicecategory_id', 2);
		array_set($servicedata,'servicesubcategory_id', 122);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/cleandata2', function() { 
	//Aerobics in dance
	//delete servicecategory 152
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 152)
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();
		array_set($servicedata,'servicecategory_id', 2);
		array_set($servicedata,'servicesubcategory_id', 85);       
		$resp = $item->update($servicedata);
	}
	
});

Route::get('/cleandata3', function() {
	//Zumba classes, 
	//delete servicesubcategory 141
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 19)
	->where('servicesubcategory_id',141 )
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();        
		array_set($servicedata,'servicesubcategory_id', 20);       
		$resp = $item->update($servicedata);
	}	
});
//dont his this route ,not sure about the categories
Route::get('/cleandata4', function() {
	//kids gym
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 65)
	->where('servicesubcategory_id',66 )
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();        
		array_set($servicedata,'servicesubcategory_id', 67);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/cleandata5', function() { 
	//functional training (64, 75)
	//delete servicesubcategory _id 75
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 5)
	->where('servicesubcategory_id',75 )
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();        
		array_set($servicedata,'servicesubcategory_id', 64);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/cleandata6', function() {
	//matt pilates (89, 99)
	// delete servicesubcategory 89, 99
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 4)
	->whereIn('servicesubcategory_id',array(89,99) )
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();        
		array_set($servicedata,'servicesubcategory_id', 13);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/customercleanup', function() {
	//matt pilates (89, 99)
	// delete servicesubcategory 89, 99
	$customer = Customer::where('picture','like' ,'%http:%')
	->where('identity','facebook')					
	->get();
	
	foreach ($customer as $item) {

		$picture = $item['picture'];		
		$newpic = str_replace("http:", "https:", $picture);
		$newpic2 = str_replace("http%", "https%", $newpic);	
		array_set($customerdata,'picture', $newpic2);

		echo $resp = $item->update($customerdata);
	}				
	
	
});