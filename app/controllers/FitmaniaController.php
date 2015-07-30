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


	public function getDealOfDay($city = 'mumbai', $location_cluster = ''){

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

	public function getDealOfDayHealthyTiffin($city = 'mumbai', $category_cluster = ''){

		// $date 					=  	Carbon::now();
		$date 					=  	'31-07-2015';

		$timestamp 				= 	strtotime($date);
		$citydata 				=	City::where('slug', '=', $city)->first(array('name','slug'));
		$city_name 				= 	$citydata['name'];
		$city_id				= 	(int) $citydata['_id'];	
		$dealsofdays 			=	[];

		$query 	=	Fitmaniadod::with('location')->with('city')->active()->where('city_id', '=', $city_id)
		->where('offer_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
		->where('offer_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ));

		if($category_cluster != ''){ 
			$query->where('category_cluster', $category_cluster); 
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
		'offer_pic' => (isset($item['offer_pic']) && $item['offer_pic'] != '') ? strtolower($item['offer_pic']) : "",
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
			if( empty($orderData['service_id']) ){
				return Response::json(array('status' => 404,'message' => "Data Missing - service_id"),404);				
			}
		}

		array_set($data, 'status', '1');
		$buydealofday 	=	$order->update($data);

		$resp 	= 	array('status' => 404,'message' => "Order Update Fail :)");

		if($buydealofday){

			if($orderData['type'] == 'fitmaniadealsofday'){

				$dealofday = Fitmaniadod::findOrFail(intval($orderData['service_id']));
				$dealslabsarr = $dealofday->toArray();

				$slab_arr = $dealslabsarr['slabs'];

				// return $dealslabsarr['slabs'];
				foreach ($dealslabsarr['slabs'] as $key => $item) {
					// return $item['total_purchase'];
					// $slab = [];
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

			$sndsSmsCustomer		= 	$this->customersms->buyServiceThroughFitmania($orderData);
			$sndsEmailCustomer		= 	$this->customermailer->buyServiceThroughFitmania($orderData);
			$sndsEmailFinder		= 	$this->findermailer->buyServiceThroughFitmania($orderData);

			$resp 	= 	array('status' => 200,'message' => "Successfully buy Serivce through Fitmania :)");
		}

		return Response::json($resp,200);		
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

		array_set($data, 'status', '1');
		$buydealofday 	=	$order->update($data);

		$resp 	= 	array('status' => 404,'message' => "Order Update Fail :)");

		if($buydealofday){

			$sndsSmsCustomer		= 	$this->customersms->buyServiceMembershipThroughFitmania($orderData);
			$sndsEmailCustomer		= 	$this->customermailer->buyServiceMembershipThroughFitmania($orderData);
			$sndsEmailFinder		= 	$this->findermailer->buyServiceMembershipThroughFitmania($orderData);

			$resp 	= 	array('status' => 200,'message' => "Successfully buy Serivce Membership through Fitmania :)");
		}

		return Response::json($resp,200);		
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

		array_set($data, 'status', '1');
		$buydealofday 	=	$order->update($data);

		$sndsSmsCustomer		= 	$this->customersms->buyServiceHealthyTiffinThroughFitmania($orderData);
		$sndsEmailCustomer		= 	$this->customermailer->buyServiceHealthyTiffinThroughFitmania($orderData);
		$sndsEmailFinder		= 	$this->findermailer->buyServiceHealthyTiffinThroughFitmania($orderData);

		$resp 	= 	array('status' => 200,'message' => "Successfully buy Serivce Healthy through Fitmania :)");

		return Response::json($resp,200);		
	}




	//resend email to customer and finder for successfull orders
	public function resendEmails(){
		
		// $order_ids = [2329,2334,2333,2331,2345,2348,2347,2350,2365,2375,2379,2376,2381,2383,2390,2395,2372,2393,2394,2396,2406,2408,2413,2426,2428,2430,2432,2437,2435,2448,2446,2453,2455,2454,2463,2468,2469,2475,2474,2377,2491,2495,2497,2482,2500,2498,2496,2503,2508,2508,2505,2507,2510,2509,2512,2511,2398,2516,2506,2548,2564,2576,2587,2659,2733,2921,2922,2924,2930,2931,2933,2934,2968,2972,2973,2985,2990,2989,2990];

		$order_ids = [2310];		//  sanjay.fitternity@gmail.com

		// updates city name  first
		// $items = Order::active()->whereIn('_id', $order_ids)->get();
		// $finderdata = array();

		// foreach ($items as $item) {  
		// 	$data 	= $item->toArray();
		// 	$finder = Order::findOrFail($data['_id']);
		// 	$city_name = ($data['city_id'] == 1) ? 'mumbai' : 'pune';
		// 	array_set($finderdata, 'status', '1');
		// 	array_set($finderdata, 'city_name', $city_name);
		// 	$response = $finder->update($finderdata);
		// 	print_pretty($response);
		// }

		$orders = Order::active()->whereIn('_id', $order_ids)->get();
		$finderdata = array();

		foreach ($orders as $order) {  
			$orderData 				= 	$order->toArray();
			$sndsEmailCustomer		= 	$this->customermailer->buyServiceThroughFitmaniaResend1($orderData);
			// $sndsSmsCustomer		= 	$this->customersms->buyServiceThroughFitmania($orderData);
			// $sndsEmailFinder		= 	$this->findermailer->buyServiceThroughFitmania($orderData);

			echo "$sndsEmailCustomer <br><br>";
			// echo "$sndsSmsCustomer === $sndsEmailCustomer === $sndsEmailFinder<br><br>";
		}



	}



}
