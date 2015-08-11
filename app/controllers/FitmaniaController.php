<?PHP

/** 
 * ControllerName : FitmaniaController.
 * Maintains a list of functions used for FitmaniaController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


use App\Mailers\CustomerMailer as CustomerMailer;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\CustomerSms as CustomerSms;


class FitmaniaController extends \BaseController {

	protected $customermailer;
	protected $customersms;
	protected $findermailer;
	
	public function __construct(CustomerMailer $customermailer, CustomerSms $customersms, FinderMailer $findermailer) {

		$this->customermailer	=	$customermailer;
		$this->customersms		=	$customersms;
		$this->findermailer 	=	$findermailer;
	}


	public function getDealOfDay($city = 'mumbai', $from = '', $size = '', $location_cluster = ''){

		// $date 				=  	($date == null) ? Carbon::now() : $date;
		$date 					=  	Carbon::now();
		$timestamp 				= 	strtotime($date);
		$citydata 				=	City::where('slug', '=', $city)->first(array('name','slug'));
		$city_name 				= 	$citydata['name'];
		$city_id				= 	(int) $citydata['_id'];	
		$dealsofdays 			=	[];

		$query 	=	Fitmaniadod::with('location')->with('city')->active()->where('city_id', '=', $city_id)
		->where('offer_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
		->where('offer_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ));

		if($location_cluster != ''){ 
			$query->where('location_cluster', $location_cluster); 
		}	

		// $dealsofdaycolleciton 	= $query->orderBy('ordering', 'desc')->get()->toArray();
		$dealsofdaycolleciton 	= $query->take($size)->skip($from)->orderBy('ordering', 'desc')->get()->toArray();
		
		
		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transform($value);
			array_push($dealsofdays, $dealdata);
		}

		if($city == 'mumbai'){
			$location_cluster	=	['all','central-mumbai','south-mumbai','western-mumbai','navi-mumbai','thane'];

		}else{
			$location_cluster	=	['all','pune-city', 'pimpri-chinchwad' ];
		}	

		$responseData = [ 'dealsofday' => $dealsofdays, 'location_cluster' => $location_cluster ];

		return Response::json($responseData, 200);
	}

	public function getDealOfDayHealthyTiffin($city = 'mumbai', $from = '', $size = '', $category_cluster = ''){

		// $date 					=  	Carbon::now();
		$date 					=  	'31-07-2015';
		$timestamp 				= 	strtotime($date);
		$citydata 				=	City::where('slug', '=', $city)->first(array('name','slug'));
		$city_name 				= 	$citydata['name'];
		$city_id				= 	(int) $citydata['_id'];	
		$from 					=	($from != '') ? intval($from) : 0;
		$size 					=	($size != '') ? intval($size) : 10;

		$dealsofdays 			=	[];

		$query 	=	Fitmaniadod::with('location')->with('city')->active()->where('city_id', '=', $city_id)
		->where('offer_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
		->where('offer_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ));

		if($category_cluster != ''){ 
			$query->where('category_cluster', $category_cluster); 
		}	

		$dealsofdaycolleciton 	= $query->take($size)->skip($from)->orderBy('ordering', 'desc')->get()->toArray();
		
		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transform($value);
			array_push($dealsofdays, $dealdata);
		}

		if($city == 'mumbai'){
			$location_cluster	=	['all','central-mumbai','south-mumbai','western-mumbai','navi-mumbai','thane'];
		}else{
			$location_cluster	=	['all','pune-city', 'pimpri-chinchwad' ];
		}	

		$category_cluster	=	['all', 'beverages', 'desserts', 'snacks', 'packages'];


		$responseData = [ 'dealsofday' => $dealsofdays,  'location_cluster' => $category_cluster ];

		return Response::json($responseData, 200);
	}


	public function getDealOfDayZumba($city = 'mumbai', $location_cluster = ''){

		$date 					=  	'27-07-2015';
		$citydata 				=	City::where('slug', '=', $city)->first(array('name','slug'));
		$city_name 				= 	$citydata['name'];
		$city_id				= 	(int) $citydata['_id'];	
		$dealsofdays 			=	[];

		$query 	=	Fitmaniadod::with('location')->with('city')->active()->where('city_id', '=', $city_id)
		->where('offer_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
		->where('offer_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ));

		if($location_cluster != ''){ 
			$query->where('location_cluster', $location_cluster); 
		}	

		$dealsofdaycolleciton 	= $query->orderBy('ordering', 'desc')->get()->toArray();
		
		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transform($value);
			array_push($dealsofdays, $dealdata);
		}

		if($city == 'mumbai'){
			$location_cluster	=	['all','central-mumbai','south-mumbai','western-mumbai','navi-mumbai','thane'];
		}else{
			$location_cluster	=	['all','pune-city', 'pimpri-chinchwad' ];
		}	

		$responseData = [ 'dealsofday' => $dealsofdays, 'location_cluster' => $location_cluster ];

		return Response::json($responseData, 200);
	}

	public function getDealOfDayBetweenDate($startdate = null, $enddate = null, $city = 'mumbai', $location_cluster = ''){

		// $date 					=  	'27-07-2015';
		$startdate 				=  	($startdate == null) ? Carbon::now() : $startdate;
		$enddate 				=  	($enddate == null) ? Carbon::now() : $enddate;

		$citydata 				=	City::where('slug', '=', $city)->first(array('name','slug'));
		$city_name 				= 	$citydata['name'];
		$city_id				= 	(int) $citydata['_id'];	
		$dealsofdays 			=	[];

		$query 	=	Fitmaniadod::with('location')->with('city')->active()->where('city_id', '=', $city_id)
		->where('offer_date', '>=', new DateTime( date("d-m-Y", strtotime( $startdate )) ))
		->where('offer_date', '<=', new DateTime( date("d-m-Y", strtotime( $enddate )) ));

		if($location_cluster != ''){ 
			$query->where('location_cluster', $location_cluster); 
		}	

		$dealsofdaycolleciton 	= $query->orderBy('ordering', 'desc')->get()->toArray();
		
		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transform($value);
			array_push($dealsofdays, $dealdata);
		}

		if($city == 'mumbai'){
			$location_cluster	=	['all','central-mumbai','south-mumbai','western-mumbai','navi-mumbai','thane'];
		}else{
			$location_cluster	=	['all','pune-city', 'pimpri-chinchwad' ];
		}	

		$responseData = [ 'dealsofday' => $dealsofdays, 'location_cluster' => $location_cluster ];

		return Response::json($responseData, 200);
	}


	private function transform($deal){

		$item  	   	=  	(!is_array($deal)) ? $deal->toArray() : $deal;
		$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->where('_id', (int) $item['finder_id'])->first();
		// return $finderarr;

		if(isset($item['slabs']) && !empty($item['slabs'])){
			$current_going_slab = head(array_where($item['slabs'], function($key, $value){
				if($value['can_sold'] == 1 && $value['price'] > 0){
					return $value;
				}
			}));
		}

		$data = [
		'_id' => $item['_id'],
		'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
		'location' => (isset($item['location']) && !empty($item['location']) ) ? array_only($item['location'], array('_id', 'name', 'slug')) : "",
		'city' => (isset($item['city']) && !empty($item['city']) ) ? array_only($item['city'], array('_id', 'name', 'slug')) : "",
		'finder_name' => (isset($item['finder_name']) && $item['finder_name'] != '') ? strtolower($item['finder_name']) : "",
		'price' => (isset($item['price']) && $item['price'] != '') ? strtolower($item['price']) : "",
		'location_cluster' => (isset($item['location_cluster']) && $item['location_cluster'] != '') ? strtolower($item['location_cluster']) : "",
		'category_cluster' => (isset($item['category_cluster']) && $item['category_cluster'] != '') ? strtolower($item['category_cluster']) : "",
		'finder_id' => (isset($item['finder_id']) && $item['finder_id'] != '') ? strtolower($item['finder_id']) : "",
		'offer_pic' => (isset($item['offer_pic']) && $item['offer_pic'] != '') ? $item['offer_pic'] : "",
		'description' => (isset($item['description']) && $item['description'] != '') ? $item['description'] : "",
		'timing' => (isset($item['timing']) && $item['timing'] != '') ? $item['timing'] : "",
		'address' => (isset($item['address']) && $item['address'] != '') ? $item['address'] : "",
		'ordering' => (isset($item['ordering']) && $item['ordering'] != '') ? (int)$item['ordering'] : "",
		'offer_date' => (isset($item['offer_date']) && $item['offer_date'] != '') ? strtolower($item['offer_date']) : "",
		'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
		'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type','commercial_type','coverimage','info','category','location','contact','finder_poc_for_customer_name','finder_poc_for_customer_mobile','finder_vcc_email')),
		'slabs' => (isset($item['slabs']) && !empty($item['slabs']) ) ? $item['slabs'] : "",
		'current_going_slab' => (isset($current_going_slab) && !empty($current_going_slab) ) ? $current_going_slab : "",
		'august_available_dates' => "",
		'available_dates' => (isset($item['available_dates']) && !empty($item['available_dates']) ) ? $item['available_dates'] : "",
		'finder_poc_for_customer_name' => (isset($item['finder_poc_for_customer_name']) && !empty($item['finder_poc_for_customer_name']) ) ? $item['finder_poc_for_customer_name'] : "",
		'finder_poc_for_customer_mobile' => (isset($item['finder_poc_for_customer_mobile']) && !empty($item['finder_poc_for_customer_mobile']) ) ? $item['finder_poc_for_customer_mobile'] : "",
		'finder_vcc_email' => (isset($item['finder_vcc_email']) && !empty($item['finder_vcc_email']) ) ? $item['finder_vcc_email'] : "",
		'delivery_area' => (isset($item['delivery_area']) && !empty($item['delivery_area']) ) ? $item['delivery_area'] : "",
		'delivery_time' => (isset($item['delivery_time']) && !empty($item['delivery_time']) ) ? $item['delivery_time'] : "",
		];
		return $data;
	}


	public function fitmaniaServices(){

		// return $data = Input::json()->all();
		$from 				=	(Input::json()->get('from')) ? intval(Input::json()->get('from')) : 0;
		$size 				=	(Input::json()->get('size')) ? intval(Input::json()->get('size')) : 10;
		$city 				=	(Input::json()->get('city')) ? intval(Input::json()->get('city')) : 1;
		$category 			=	(Input::json()->get('category')) ? intval(Input::json()->get('category')) : '';		
		$location 			=	(Input::json()->get('location')) ? intval(Input::json()->get('location')) : '';	
		$fitmaniaServices 	=	[];

		$query	 			= 	Service::active()->orderBy('_id')->whereIn('show_on', array('2','3'))->where('city_id', $city);	

		if($category != ''){ 
			$query->where('servicecategory_id', $category); 
		}		
		
		if($location != ''){ 
			$query->where('location_id', $location); 
		}	

		$serviceColleciton 		= 	$query->take($size)->skip($from)->get();
		foreach ($serviceColleciton as $key => $value) {
			$servicedata = $this->transformFitmaniaService($value);
			array_push($fitmaniaServices, $servicedata);
		}
		// if(!$services){  $services =  'No Service Exist :)'; }
		$all = [array('_id'=>0,'slug'=>'all','name'=>'all',)];
		$categories = Servicecategory::active()->where('parent_id', 0)->orderBy('name')->get(array('name','_id','slug'))->toArray();
		$locations = Location::active()->whereIn('cities', array($city))->orderBy('name')->get(array('name','_id','slug'))->toArray();
		$responseData = [
		'categories' => array_merge($all,$categories),
		'locations' => array_merge($all,$locations),
		'services' => $fitmaniaServices
		];
		// return $responseData;

		return Response::json($responseData, 200);
	}



	private function transformFitmaniaService($serivce){

		$item  	   	=  	(!is_array($serivce)) ? $serivce->toArray() : $serivce;
		$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->where('_id', (int) $item['finder_id'])->first();
		// return $item; exit;

		$data = [
		'_id' => $item['_id'],
		'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
		'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
		'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
		'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
		'workout_tags' => (isset($item['workout_tags']) && $item['workout_tags'] != '') ? $item['workout_tags'] : [],
		'servicecategory_id' => (isset($item['servicecategory_id']) && $item['servicecategory_id'] != '') ? intval($item['servicecategory_id']) : "",
		'servicesubcategory_id' => (isset($item['servicesubcategory_id']) && $item['servicesubcategory_id'] != '') ? intval($item['servicesubcategory_id']) : "",
		'location_id' => (isset($item['location_id']) && $item['location_id'] != '') ? intval($item['location_id']) : "",
		'ratecards' => (isset($item['ratecards']) && $item['ratecards'] != '') ? $item['ratecards'] : [],
		'finder_id' => (isset($item['finder_id']) && $item['finder_id'] != '') ? strtolower($item['finder_id']) : "",
		'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
		'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type','commercial_type','coverimage','info','category','location','contact','finder_poc_for_customer_name','finder_poc_for_customer_mobile','finder_vcc_email')),
		];

		return $data;
	}


	public function buyService(){

		$data			=	Input::json()->all();		
		if(empty($data['order_id'])){
			return Response::json(array('status' => 404,'message' => "Data Missing Order Id - order_id"),404);			
		}
		// return Input::json()->all();
		$orderid 	=	(int) Input::json()->get('order_id');
		$order 		= 	Order::findOrFail($orderid);
		$orderData 	= 	$order->toArray();

		//Maintain Slab for deals of day
		if($orderData['type'] == 'fitmaniadealsofday'){
			if(empty($orderData['service_id']) ){
				return Response::json(array('status' => 404,'message' => "Data Missing - service_id"),404);				
			}
		}

		if($orderData['status'] == 0){
			$buydealofday 	=	$order->update(['status' => '1']);

			if($buydealofday){
				if($orderData['type'] == 'fitmaniadealsofday'){
					$dealofday = Fitmaniadod::findOrFail(intval($orderData['service_id']));
					$dealslabsarr = $dealofday->toArray();
					$slab_arr = $dealslabsarr['slabs'];
					foreach ($dealslabsarr['slabs'] as $key => $item) {
						if(intval($item['can_sold']) == 1){
							$item['total_purchase'] =  intval($item['total_purchase']) + 1;
							if(intval($item['limit']) == intval($item['total_purchase'])){
								$item['can_sold'] = 0;
							}
							$slab_arr[$key] = $item;
							break;
						}
					}
					// return $slab_arr;
					$slabdata = [];
					array_set($slabdata, 'slabs', $slab_arr);
					$dealofday->update($slabdata);
				}
			}

			$sndsSmsCustomer		= 	$this->customersms->buyServiceThroughFitmania($orderData);
			$sndsEmailCustomer		= 	$this->customermailer->buyServiceThroughFitmania($orderData);
			$sndsEmailFinder		= 	$this->findermailer->buyServiceThroughFitmania($orderData);
			Log::info('Customer Purchase : '.json_encode(array('purchase_details' => $order)));
			$resp 	= 	array('status' => 200,'message' => "Successfully buy Serivce through Fitmania :)");
			return Response::json($resp,200);		
		}

		$resp 	= 	array('status' => 401,'message' => "Serivce already purchase :)");
		return Response::json($resp,401);		


	}


	public function buyServiceMembership(){

		$data			=	Input::json()->all();		
		if(empty($data['order_id'])){
			return Response::json(array('status' => 404,'message' => "Data Missing Order Id - order_id"),404);			
		}
		
		// return Input::json()->all();
		$orderid 	=	(int) Input::json()->get('order_id');
		$order 		= 	Order::findOrFail($orderid);
		$orderData 	= 	$order->toArray();

		if($orderData['status'] == 0){
			$buydealofday 	=	$order->update(['status' => '1']);
			$resp 			= 	array('status' => 404,'message' => "Order Update Fail :)");

			if($buydealofday){
				$sndsSmsCustomer		= 	$this->customersms->buyServiceMembershipThroughFitmania($orderData);
				$sndsEmailCustomer		= 	$this->customermailer->buyServiceMembershipThroughFitmania($orderData);
				$sndsEmailFinder		= 	$this->findermailer->buyServiceMembershipThroughFitmania($orderData);
				$resp 					= 	array('status' => 200,'message' => "Successfully buy Serivce Membership through Fitmania :)");
				return Response::json($resp,200);		
			}
		}

		$resp 	= 	array('status' => 401,'message' => "Serivce already purchase :)");
		return Response::json($resp,401);		
	}


	public function buyServiceHealthyTiffin(){

		$data			=	Input::json()->all();		
		if(empty($data['order_id'])){
			return Response::json(array('status' => 404,'message' => "Data Missing Order Id - order_id"),404);			
		}
		// return Input::json()->all();
		$orderid 	=	(int) Input::json()->get('order_id');
		$order 		= 	Order::findOrFail($orderid);
		$orderData 	= 	$order->toArray();

		if($orderData['status'] == 0){
			$buydealofday 	=	$order->update(['status' => '1']);
			$sndsSmsCustomer		= 	$this->customersms->buyServiceHealthyTiffinThroughFitmania($orderData);
			$sndsEmailCustomer		= 	$this->customermailer->buyServiceHealthyTiffinThroughFitmania($orderData);
			$sndsEmailFinder		= 	$this->findermailer->buyServiceHealthyTiffinThroughFitmania($orderData);

			$resp 	= 	array('status' => 200,'message' => "Successfully buy Serivce Healthy through Fitmania :)");
			return Response::json($resp,200);	
		}

		$resp 	= 	array('status' => 401,'message' => "Serivce already purchase :)");
		return Response::json($resp,401);		
	}



	//resend email to customer and finder for successfull orders
	public function resendEmails(){

		// $order_ids = [3338,3341,3345,3342,3352,3351,3356,3355,3364,3428,3430,3392];
		$order_ids = [4402];

		// $order_ids = [2310];		//  sanjay.fitternity@gmail.com

		// updates city name  first
		$items = Order::whereIn('_id`', $order_ids)->get();
		$finderdata = array();

		foreach ($items as $item) {  
			$data 	= $item->toArray();
			$finder = Order::findOrFail($data['_id']);
			$city_name = ($data['city_id'] == 1) ? 'mumbai' : 'pune';
			array_set($finderdata, 'status', '1');
			array_set($finderdata, 'city_name', $city_name);
			$response = $finder->update($finderdata);
			// print_pretty($finderdata); 
		}

		$orders = Order::whereIn('_id', $order_ids)->get();
		$finderdata = array();
		foreach ($orders as $order) {  
			$orderData 				= 	$order->toArray();
			// $sndsSmsCustomer		= 	$this->customersms->buyServiceThroughFitmania($orderData);
			$sndsEmailCustomer		= 	$this->customermailer->buyServiceThroughFitmania($orderData);
			$sndsEmailFinder		= 	$this->findermailer->buyServiceThroughFitmania($orderData);
			echo "$sndsEmailCustomer === $sndsEmailFinder<br><br>";
			// echo "$sndsSmsCustomer === $sndsEmailCustomer === $sndsEmailFinder<br><br>";
		}

	}

	public function resendFinderEmail(){

		// $order_ids = [2284];		//  sanjay.fitternity@gmail.com

		$order_ids = [2329,2334,2333,2331,2345,2348,2344,2347,2350,2365,2375,2374,2379,2376,2381,2383,2390,2395,2372,2393,2394,2396,2406,2408,2412,2413,2420,2426,2428,2430,2432,2431,2437,2435,2448,2446,2453,2456,2457,2455,2454,2463,2458,2468,2469,
		2475,2474,2377,2491,2495,2497,2482,2500,2498,2496,2503,2508,2508,2505,2507,2510,2509,2512,2511,2398,2515,2516,2520,2526,2527,2525,2531,2506,2394,2536,2537,2539,2541,2545,2548,2549,2552,2553,2562,2564,2561,2570,2574,2575,2576,2582,
		2585,2587,2586,2589,2591,2592,2594,2596,2597,2599,2598,2600,2603,2604,2607,2602,2609,2619,2613,2612,2625,2622,2627,2630,2629,2631,2636,2638,2639,2640,2641,2642,2651,2653,2741,2659,2661,2662,2666,2669,2668,2667,2671,2673,2672,2676,
		2677,2679,2682,2684,2685,2689,2692,2703,2705,2706,2709,2720,2721,2722,2734,2736,2733,2756,2759,2762,2761,2760,2764,2770,2774,2777,2778,2779,2781,2783,2784,2786,2787,2790,2788,2792,2793,2791,2797,2800,2803,2804,2808,2811,2812,2814,
		2815,2828,2832,2833,2837,2852,2856,2862,2864,2867,2869,2871,2872,2875,2878,2879,2880,2882,2890,2894,2917,2919,2920,2921,2922,2924,2930,2931,2933,2934,2940,2841,2943,2947,2950,2953,2961,2962,2967,13000,2968,2968,2972,2973,2977,2982,
		2985,2990,2989,2990,2992,3005,3017,3021,3022,3025,3031,3039,3041,3042,3044,3048,3050,3051,3053,3052,3054,3057,3064,3069,3074,3075,3076,3084,3084,3096,3098,3101,3105,3103,3111,3113,3118,3117,3119,3120,3123,3125,3131,3132,3133,
		3136,3144,3147,3153,3150,3155,3159,3166,3172,3178,3175,3176,3178,3183,3186,3179,3200,3198,3202,3205,3208,3210,3212,3216,3217,3220,3221,3222,3236,3238,3246,3248,3250,3254,3255,3258,3259,3261,3264,3263,3270,3272,3273,3283,3284,3287,
		3291,3293,3294,3299,3300,3302,3304,3305,3307,3309,3321,3330,3338,3341,3345,3342,3342,3352,3351,3356,3355,3364,3374,3376,3386,3394,3397,3399,3403,3405,3407,3410,3412,3411,3413,3415,3424,3427,3426,3428,3429,3430,3431,3432,3445,3446,
		3452,3456,3458,3459,3464,3465,3468,3478,3482,3484,3485,3486,3487,3488,3492,3497,3500,3507,3392,3509,3518,3523,3527,3528,3549,3552,3558,3560,3559,3562,3565,3570,3575,3581,3588,3590,3593,3598,3600,3603,3604,3613,3617,3629,3636,3638,
		3639,3640,3641,3644,3647,3649,3650,3655,3660,3663,3665,3667,3668,3672,3677,3678,3682,3681,3684,3687,3688,3695,3696,3698,3701,3705,3710,3711,3714,3717,3725,3726,3728,3697,3730,3733,3734,3735,3732,3741,3744,3745,3746,3747,3749,3753,
		3756,3760,3764,3765,3768,3770,3774,3779,3780,3783,3785,3789,3790,3792,3795,3798,3799,3800,3805,2999,3814,3812,3818,3820,3853,3855,3856,3869,3878,3883,3885,3887,3892,3894,3912,3918,3915,3935,3939,3949,3959,3956,3961,3997,3979,3984,
		3985,3992,3994,3996,4002,4010,4016,4021,4034,4033,4037,4041,4046,4049,4048,4053,4058,4064,4066,4071,4072,4075,4092,4130,4121,4124,4126,4129];		


		$finders 			=	Order::whereIn('_id', $order_ids)->get()->groupBy('finder_id')->toArray();
		$i = 0;

		foreach ($finders as $finderid => $orders) {
			$orderdata = array();
			foreach ($orders as $key => $value) {
				$order = [
				'customer_name' => (isset($value->customer_name) && $value->customer_name != '') ? $value->customer_name : "", 
				'service_name' => (isset($value->service_name) && $value->service_name != '') ? $value->service_name : "", 
				'brand' => (isset($value->finder_name) && $value->finder_name != '') ? $value->finder_name : "", 
				'order_id' => (isset($value->_id) && $value->_id != '') ? $value->_id : "", 
				'amount' => (isset($value->amount) && $value->amount != '') ? $value->amount : "", 
				'customer_phone' => (isset($value->customer_phone) && $value->customer_phone != '') ? $value->customer_phone : ""
				];
				array_push($orderdata, $order);
			}


			$finder = 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
			$finderarr = $finder->toArray();

			if(isset($finder->finder_vcc_email) && $finder->finder_vcc_email != ""){
				echo "<br><br> finder_vcc_email =  ---- $finder->finder_vcc_email  finderid  ---- $finderid ";
			// if($i > 20 ){

				$emaildata = [
				'finder_vcc_email'	=> $finder->finder_vcc_email,	
				'orders' 			=> $orderdata
				];
					// return $this->findermailer->resendFitmaniaFinderEmail($emaildata);					
				if(!$this->findermailer->resendFitmaniaFinderEmail($emaildata)){
					echo "<br><br> finderid =  finderid  ---- $finderid <br>finder_vcc_email  ---- ". $orders[0]['finder_vcc_email'];
				}
			// }
			// $i++;
			}else{
				echo "<br><br> finder_vcc_email not exist finderid  ---- $finderid ";
			}

		}

		// return $finders;

	}


	public function resendCustomerEmail(){

		$order_ids = [2284];		//  sanjay.fitternity@gmail.com

		$order_ids = [2329,2334,2333,2331,2345,2348,2344,2347,2350,2365,2375,2374,2379,2376,2381,2383,2390,2395,2372,2393,2394,2396,2406,2408,2412,2413,2420,2426,2428,2430,2432,2431,2437,2435,2448,2446,2453,2456,2457,2455,2454,2463,2458,2468,2469,
		2475,2474,2377,2491,2495,2497,2482,2500,2498,2496,2503,2508,2508,2505,2507,2510,2509,2512,2511,2398,2515,2516,2520,2526,2527,2525,2531,2506,2394,2536,2537,2539,2541,2545,2548,2549,2552,2553,2562,2564,2561,2570,2574,2575,2576,2582,
		2585,2587,2586,2589,2591,2592,2594,2596,2597,2599,2598,2600,2603,2604,2607,2602,2609,2619,2613,2612,2625,2622,2627,2630,2629,2631,2636,2638,2639,2640,2641,2642,2651,2653,2741,2659,2661,2662,2666,2669,2668,2667,2671,2673,2672,2676,
		2677,2679,2682,2684,2685,2689,2692,2703,2705,2706,2709,2720,2721,2722,2734,2736,2733,2756,2759,2762,2761,2760,2764,2770,2774,2777,2778,2779,2781,2783,2784,2786,2787,2790,2788,2792,2793,2791,2797,2800,2803,2804,2808,2811,2812,2814,
		2815,2828,2832,2833,2837,2852,2856,2862,2864,2867,2869,2871,2872,2875,2878,2879,2880,2882,2890,2894,2917,2919,2920,2921,2922,2924,2930,2931,2933,2934,2940,2841,2943,2947,2950,2953,2961,2962,2967,13000,2968,2968,2972,2973,2977,2982,
		2985,2990,2989,2990,2992,3005,3017,3021,3022,3025,3031,3039,3041,3042,3044,3048,3050,3051,3053,3052,3054,3057,3064,3069,3074,3075,3076,3084,3084,3096,3098,3101,3105,3103,3111,3113,3118,3117,3119,3120,3123,3125,3131,3132,3133,
		3136,3144,3147,3153,3150,3155,3159,3166,3172,3178,3175,3176,3178,3183,3186,3179,3200,3198,3202,3205,3208,3210,3212,3216,3217,3220,3221,3222,3236,3238,3246,3248,3250,3254,3255,3258,3259,3261,3264,3263,3270,3272,3273,3283,3284,3287,
		3291,3293,3294,3299,3300,3302,3304,3305,3307,3309,3321,3330,3338,3341,3345,3342,3342,3352,3351,3356,3355,3364,3374,3376,3386,3394,3397,3399,3403,3405,3407,3410,3412,3411,3413,3415,3424,3427,3426,3428,3429,3430,3431,3432,3445,3446,
		3452,3456,3458,3459,3464,3465,3468,3478,3482,3484,3485,3486,3487,3488,3492,3497,3500,3507,3392,3509,3518,3523,3527,3528,3549,3552,3558,3560,3559,3562,3565,3570,3575,3581,3588,3590,3593,3598,3600,3603,3604,3613,3617,3629,3636,3638,
		3639,3640,3641,3644,3647,3649,3650,3655,3660,3663,3665,3667,3668,3672,3677,3678,3682,3681,3684,3687,3688,3695,3696,3698,3701,3705,3710,3711,3714,3717,3725,3726,3728,3697,3730,3733,3734,3735,3732,3741,3744,3745,3746,3747,3749,3753,
		3756,3760,3764,3765,3768,3770,3774,3779,3780,3783,3785,3789,3790,3792,3795,3798,3799,3800,3805,2999,3814,3812,3818,3820,3853,3855,3856,3869,3878,3883,3885,3887,3892,3894,3912,3918,3915,3935,3939,3949,3959,3956,3961,3997,3979,3984,
		3985,3992,3994,3996,4002,4010,4016,4021,4034,4033,4037,4041,4046,4049,4048,4053,4058,4064,4066,4071,4072,4075,4092,4130,4121,4124,4126,4129];		


		// updates city name  first
		$items = Order::whereIn('_id', $order_ids)->get();
		$finderdata = array();
		$i = 1;
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			$finder = Order::findOrFail($data['_id']);
			$city_name = ($data['city_id'] == 1) ? 'mumbai' : 'pune';
			array_set($finderdata, 'status', '1');
			array_set($finderdata, 'city_name', $city_name);
			$response = $finder->update($finderdata);
			print_pretty($response); 
		}


		$orders = Order::whereIn('_id', $order_ids)->get();
		$finderdata = array();

		foreach ($orders as $order) {  
			$orderData 				= 	$order->toArray();
			if($i > 20 ){

				$sndsEmailCustomer		= 	$this->customermailer->resendFitmaniaCustomerEmail($orderData);
			}
			$i++;
			// echo "$sndsEmailCustomer $sndsEmailCustomer <br><br>";

			if($sndsEmailCustomer =! 1){
				echo "order id : $order[_id]  <br><br>";
			}

		}

	}



}
