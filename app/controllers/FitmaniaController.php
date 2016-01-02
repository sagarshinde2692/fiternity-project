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



	public function categoryId($category){

		$categorydays_arr     =  array('anniversary' => 'all', 'zumba' => '19', 'gym' => '65', 'crossfit' => '111','mma' => '3', 'dance' => '2', 'yoga' => '1');
		return $categorydays_arr[$category];
	}

	public function categorydayCitywise($city, $weekday){

		$category_info  = [];
		$tommorow_date 	=	\Carbon\Carbon::tomorrow();
		$timestamp 		= 	strtotime($tommorow_date);
		$tommorow 		= 	strtolower(date( "l", $timestamp));

		switch (strtolower(trim($city))) {
			case 'mumbai':
			$categorydays_arr     =  array('sunday' => 'anniversary', 'monday' => 'zumba', 'tuesday' => 'gym', 'wednesday' => 'crossfit','thursday' => 'mma', 'friday' => 'dance', 'saturday' => 'yoga');
			break;
			
			case 'pune':
			$categorydays_arr     =  array('sunday' => 'anniversary', 'monday' => 'zumba', 'tuesday' => 'gym', 'wednesday' => 'crossfit','thursday' => 'mma', 'friday' => 'dance', 'saturday' => 'yoga');
			break;

			case 'bangalore':
			$categorydays_arr     =  array('sunday' => 'anniversary', 'monday' => 'gym', 'tuesday' => 'dance', 'wednesday' => 'yoga','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit');
			break;	

			case 'delhi':
			$categorydays_arr     =  array('sunday' => 'anniversary', 'monday' => 'gym', 'tuesday' => 'dance', 'wednesday' => 'yoga','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit');
			break;

			case 'gurgaon':
			$categorydays_arr     =  array('sunday' => 'anniversary', 'monday' => 'gym', 'tuesday' => 'dance', 'wednesday' => 'yoga','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit');
			break;		
		}

		$category_info['today']  		=  $categorydays_arr[$weekday];
		$category_info['tommorow']  	=  $categorydays_arr[$tommorow];
		$category_info['category_id']  	=  $this->categoryId($categorydays_arr[$weekday]);
		return $category_info;
	}


	public function homeData($city = 'mumbai', $from = '', $size = ''){

		$responsedata = [];
		$responsedata['dod'] = $this->getDealOfDay($city , $from, $size);
		$responsedata['membership'] = $this->getDealOfDay($city , $from, $size);
		$responsedata['message'] = "Fitmania Home Page Data :)";

return Response::json($responsedata, 200);
}


public function getFitmaniaHomepageBanners($city = 'mumbai', $type = 'fitmania-fitternity-home',  $from = '', $size = ''){

	$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
	if(!$citydata){
		return $this->responseNotFound('City does not exist');
	}

	$city_name 		= 	$citydata['name'];
	$city_id		= 	(int) $citydata['_id'];	
	$from 			=	($from != '') ? intval($from) : 0;
	$size 			=	($size != '') ? intval($size) : 10;

	$banners 		= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->where('banner_type', '=', trim($type))->take($size)->skip($from)->orderBy('ordering')->get();			
	if(!$banners){
		return $this->responseEmpty('Fitmania Home Page Banners does not exist :)');
	}

	$responsedata 	= ['banners' => $banners,  'message' => 'Fitmania Home Page Banners :)'];
return Response::json($responsedata, 200);
}


public function getDealOfDay($city = 'mumbai', $from = '', $size = ''){

	$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
	if(!$citydata){
		return $this->responseNotFound('City does not exist');
	}

	$city_name 				= 	$citydata['name'];
	$city_id				= 	(int) $citydata['_id'];	
	$from 					=	($from != '') ? intval($from) : 0;
	$size 					=	($size != '') ? intval($size) : 10;
	$date 					=  	Carbon::now();
	$timestamp 				= 	strtotime($date);
	$stringdate 			= 	$date->toFormattedDateString();
	$weekday 				= 	strtolower(date( "l", $timestamp));
	$categoryday   			=   $this->categorydayCitywise($city,$weekday);
	$location_clusters 		= 	$this->getLocationCluster($city_id);
	$banners 				= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->where('banner_type', '=', 'fitmania-dod')->take($size)->skip($from)->orderBy('ordering')->get();				

	$dealsofdaycnt 			=	Serviceoffer::with('finder')->with('ratecard')->where('city_id', '=', $city_id)
	->where("type" , "=" , "fitmania-dod")
	->where("active" , "=" , 1)
	// ->where('start_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
	// ->where('end_date', '<', new DateTime( date("d-m-Y", strtotime( $date )) ))
	// ->orWhere('buyable', 'exists', false)
	// ->where("buyable" , ">" , 0)->orWhere("buyable" , "=" , "")->orWhere('buyable', 'exists', false)
	->count();

	$fitmaniadods			=	[];
	$dealsofdaycolleciton 	=	Serviceoffer::with('finder')->with('ratecard')->where('city_id', '=', $city_id)
	->where("type" , "=" , "fitmania-dod")
	->where("active" , "=" , 1)
	// ->where('start_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
	// ->where('end_date', '<', new DateTime( date("d-m-Y", strtotime( $date )) ))
	// ->orWhere('buyable', 'exists', false)
	// ->where("buyable" , ">" , 0)->orWhere("buyable" , "=" , "")->orWhere('buyable', 'exists', false)
	->take($size)->skip($from)->orderBy('order', 'desc')->get()->toArray();

	foreach ($dealsofdaycolleciton as $key => $value) {
		$dealdata = $this->transformDod($value);
		array_push($fitmaniadods, $dealdata);
	}

	// return $fitmaniadods;
	$responsedata 		= 	['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday,  'totalcount' => $dealsofdaycnt, 'fitmaniadods' => $fitmaniadods, 'location_clusters' => $location_clusters,  'banners' => $banners, 'message' => 'Fitmania Home Page Dods :)'];
return Response::json($responsedata, 200);
}

private function transformDod($offers){

	$item  	   		=  	(!is_array($offers)) ? $offers->toArray() : $offers;
	$ratecardarr  	=  	(!is_array($item['service_offer_ratecard'])) ?  (array) $item['service_offer_ratecard'] : $item['service_offer_ratecard'];
	$finderarr   	=  	(!is_array($item['finder'])) ?  (array) $item['finder'] : $item['finder'];
	$servicearr 	= 	Service::with(array('city'=>function($query){$query->select('_id','name','slug');}))
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->with(array('category'=>function($query){$query->select('_id','name','slug');}))
	->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
	->where('_id', (int) $item['service_id'])->first();

	$data = [
	'_id' => $item['_id'],
	'type' => (isset($item['type']) && $item['type'] != '') ? strtolower($item['type']) : "",
	'finder_id' => (isset($item['finder_id']) && $item['finder_id'] != '') ? intval($item['finder_id']) : "",
	'service_id' => (isset($item['service_id']) && $item['service_id'] != '') ? intval($item['service_id']) : "",
	'ratecard_id' => (isset($item['ratecard_id']) && $item['ratecard_id'] != '') ? intval($item['ratecard_id']) : "",
	'active' => (isset($item['active']) && $item['active'] != '') ? intval($item['active']) : "",
	'city_id' => (isset($item['city_id']) && $item['city_id'] != '') ? intval($item['city_id']) : "",
	'price' => (isset($item['price']) && $item['price'] != '') ? intval($item['price']) : "",
	'limit' => (isset($item['limit']) && $item['limit'] != '') ? intval($item['limit']) : "",
	'order' => (isset($item['order']) && $item['order'] != '') ? intval($item['order']) : "",
	'start_date' => (isset($item['start_date']) && $item['start_date'] != '') ? $item['start_date'] : "",
	'end_date' => (isset($item['end_date']) && $item['end_date'] != '') ? $item['end_date'] : "",
	'ratecard' => (isset($item['ratecard']) && $item['ratecard'] != '') ? array_only( $ratecardarr , ['_id','type', 'price', 'special_price', 'duration', 'duration_type', 'validity', 'validity_type', 'remarks', 'order'] )  : "",
	'finder' => (isset($item['finder']) && $item['finder'] != '') ? array_only( $finderarr , ['_id','title','slug','finder_coverimage','coverimage','average_rating', 'contact'] )  : "",		
	'service' =>  array_only($servicearr->toArray(), array('name','_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage', 'service_coverimage_thumb', 'category',  'subcategory', 'location', 'buyable','address','timing','servicebatches' )),

	];

	return $data;
}



public function getMembership($city = 'mumbai', $from = '', $size = ''){

	$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
	if(!$citydata){
		return $this->responseNotFound('City does not exist');
	}

	$city_name 		= 	$citydata['name'];
	$city_id		= 	(int) $citydata['_id'];	
	$from 			=	($from != '') ? intval($from) : 0;
	$size 			=	($size != '') ? intval($size) : 10;
	$date 			=  	Carbon::now();
	$timestamp 		= 	strtotime($date);
	$stringdate 	= 	$date->toFormattedDateString();
	$weekday 		= 	strtolower(date( "l", $timestamp));
	$categoryday   	=   $this->categorydayCitywise($city,$weekday);

	$banners 			= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->where('banner_type', '=', 'fitmania-membership-giveaways')->take($size)->skip($from)->orderBy('ordering')->get();		
	$location_clusters 	= 	$this->getLocationCluster($city_id);			

	$fitmaniahomepageobj 		=	Fitmaniahomepage::where('city_id', '=', $city_id)->first();
	if(count($fitmaniahomepageobj) < 1){
		$responsedata 	= ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday, 'banners' => $banners, 'location_clusters' => $location_clusters,  'fitmaniamemberships' => [],  'message' => 'No Membership Giveaway Exist :)'];
return Response::json($responsedata, 200);
}

$ratecardids 				=   array_map('intval', explode(',', $fitmaniahomepageobj->ratecardids));
$serviceoffers  			= 	Serviceoffer::with('finder')->with('ratecard')->where('city_id', '=', $city_id)->where("type" , "=" , "fitmania-membership-giveaways")->whereIn('ratecard_id', $ratecardids)->get();

$serviceids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'service_id')) ; 
$ratecardids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'ratecard_id')) ; 

$query	 					= 	Service::with(array('city'=>function($query){$query->select('_id','name','slug');}))
->with(array('location'=>function($query){$query->select('_id','name','slug');}))
->with(array('category'=>function($query){$query->select('_id','name','slug');}))
->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
->active()->whereIn('_id', $serviceids_array);	
$services 					= 	$query->orderBy('ordering', 'desc')->get()->toArray();

$fitmaniamemberships 	=	[];
foreach ($services as $key => $value) {
	$item  	   				=  	(!is_array($value)) ? $value->toArray() : $value;
	$service_ratedcards 	= 	[];
	$ratecardsarr    		=   Ratecard::with('serviceoffers')->whereIn('_id', $ratecardids_array )->where('service_id', intval($item['_id']) )->get()->toArray();	
	if($ratecardsarr){
		foreach ($ratecardsarr as $key => $value) {
			if(intval($value['validity'])%360 == 0){
				$value['validity']  = intval(intval($value['validity'])/360);
				if(intval($value['validity']) > 1){
					$value['validity_type'] = "years";
				}else{
					$value['validity_type'] = "year";
				}
			}

			if(intval($value['validity'])%30 == 0){
				$value['validity']  = intval(intval($value['validity'])/30);
				if(intval($value['validity']) > 1){
					$value['validity_type'] = "months";
				}else{
					$value['validity_type'] = "month";
				}
			}
			array_push($service_ratedcards, $value);
		}
	}


	$finderarr 				= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');}))
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->with(array('category'=>function($query){$query->select('_id','name','slug');}))
	->where('_id', (int) $item['finder_id'])->first();
	$data = [
	'_id' => $item['_id'],
	'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
	'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
	'address' => (isset($item['address']) && $item['address'] != '') ? trim($item['address']) : "",
	'timing' => (isset($item['timing']) && $item['timing'] != '') ? trim($item['timing']) : "",
	'buyable' => (isset($item['buyable']) && $item['buyable'] != '') ? trim($item['buyable']) : "",
	'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
	'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
	'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
	'workout_tags' => (isset($item['workout_tags']) && $item['workout_tags'] != '') ? $item['workout_tags'] : [],
	'batches' => (isset($item['servicebatches']) && $item['servicebatches'] != '') ? $item['servicebatches'] : [],
	'service_ratedcards' => (isset($service_ratedcards) && !empty($service_ratedcards)) ? $service_ratedcards : [],
	'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type','commercial_type','coverimage','info','category','location','contact','finder_poc_for_customer_name','finder_poc_for_customer_mobile','finder_vcc_email')),
	];

	array_push($fitmaniamemberships, $data);
}

$responsedata 	=  ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday,'fitmaniamemberships' => $fitmaniamemberships,  'banners' => $banners, 'location_clusters' => $location_clusters, 'message' => 'Fitmania Home Page Memberships :)'];
return Response::json($responsedata, 200);
}


private function transformMembership($offers){

	$item  	   		=  	(!is_array($offers)) ? $offers->toArray() : $offers;
	$ratecardarr  	=  	(!is_array($item['ratecard'])) ?  (array) $item['ratecard'] : $item['ratecard'];
	$finderarr   	=  	(!is_array($item['finder'])) ?  (array) $item['finder'] : $item['finder'];
	$servicearr 	= 	Service::with(array('city'=>function($query){$query->select('_id','name','slug');}))
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->with(array('category'=>function($query){$query->select('_id','name','slug');}))
	->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
	->where('_id', (int) $item['service_id'])->first();

	$data = [
	'_id' => $item['_id'],
	'type' => (isset($item['type']) && $item['type'] != '') ? strtolower($item['type']) : "",
	'type' => (isset($item['type']) && $item['type'] != '') ? strtolower($item['type']) : "",
	'finder_id' => (isset($item['finder_id']) && $item['finder_id'] != '') ? intval($item['finder_id']) : "",
	'service_id' => (isset($item['service_id']) && $item['service_id'] != '') ? intval($item['service_id']) : "",
	'ratecard_id' => (isset($item['ratecard_id']) && $item['ratecard_id'] != '') ? intval($item['ratecard_id']) : "",
	'city_id' => (isset($item['city_id']) && $item['city_id'] != '') ? intval($item['city_id']) : "",
	'price' => (isset($item['price']) && $item['price'] != '') ? intval($item['price']) : "",
	'limit' => (isset($item['limit']) && $item['limit'] != '') ? intval($item['limit']) : "",
	'order' => (isset($item['order']) && $item['order'] != '') ? intval($item['order']) : "",
	'start_date' => (isset($item['start_date']) && $item['start_date'] != '') ? $item['start_date'] : "",
	'end_date' => (isset($item['end_date']) && $item['end_date'] != '') ? $item['end_date'] : "",
	'ratecard' => (isset($item['ratecard']) && $item['ratecard'] != '') ? array_only( $ratecardarr , ['_id','type', 'price', 'special_price', 'duration', 'duration_type', 'validity', 'validity_type', 'remarks', 'order'] )  : "",
	'finder' => (isset($item['finder']) && $item['finder'] != '') ? array_only( $finderarr , ['_id','title','slug','finder_coverimage','coverimage','average_rating', 'contact'] )  : "",		
	'service' =>  array_only($servicearr->toArray(), array('name','_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage','service_coverimage_thumb', 'category',  'subcategory', 'location' )),
	];

	return $data;
}




public function serachMembership(){

		// return Input::json()->all();
	$from 						=	(Input::json()->get('from')) ? intval(Input::json()->get('from')) : 0;
	$size 						=	(Input::json()->get('size')) ? intval(Input::json()->get('size')) : 10;
	$city 						=	(Input::json()->get('city')) ? strtolower(Input::json()->get('city')) : 'mumbai';
	$city_id					=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;
	$category 					=	(Input::json()->get('category')) ? array_map('intval', Input::json()->get('category')) : [];		
	$subcategory 				=	(Input::json()->get('subcategory')) ? array_map('intval', Input::json()->get('subcategory')) : [];		
	$location 					=	(Input::json()->get('location')) ? array_map('intval', Input::json()->get('location')) : [];	
	$finder 					=	(Input::json()->get('finder')) ? array_map('intval', Input::json()->get('finder')) : [];	
	$start_price				=	(Input::json()->get('start_price')) ? intval(Input::json()->get('start_price')) : "";
	$end_price					=	(Input::json()->get('end_price')) ? intval(Input::json()->get('end_price')) : "";
	$start_duration				=	(Input::json()->get('start_duration')) ? intval(Input::json()->get('start_duration')) : "";
	$end_duration				=	(Input::json()->get('end_duration')) ? intval(Input::json()->get('end_duration')) : "";

	$fitmaniamemberships 		=	[];

	$serviceoffersquery  			= 	Serviceoffer::where('city_id', '=', $city_id)->where("type" , "=" , "fitmania-membership-giveaways");

	if($start_duration != "" || $start_duration != 0 || $end_duration != "" || $end_duration != 0){
		$ratecardidquery 	= 	Ratecard::active();

		if($start_duration != "" || $start_duration != 0){
			$ratecardidquery->where('validity', '>=', intval($start_duration));
		}

		if($end_duration != "" || $end_duration != 0){
			$ratecardidquery->where('validity', '<=', intval($end_duration));
		}
		$ratecardids_array 		= 	$ratecardidquery->orderBy('ordering', 'desc')->lists('_id');
	}

	// return $ratecardids_array;
	if(isset($ratecardids_array) && !empty($ratecardids_array)){
		$serviceoffersquery->whereIn('ratecard_id', $ratecardids_array);
	}

	if($start_price != "" || $start_price != 0){
		$serviceoffersquery->where('price', '>=', intval($start_price));
	}
	if($end_price != "" || $end_price != 0){
		$serviceoffersquery->where('price', '<=', intval($end_price));
	}

	$serviceoffers 	=	$serviceoffersquery->get()->toArray();

	$serviceids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'service_id')) ; 
	$ratecardids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'ratecard_id')) ; 
	$finderids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'finder_id')) ; 

	$query	 					= 	Service::with(array('city'=>function($query){$query->select('_id','name','slug');}))
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->with(array('category'=>function($query){$query->select('_id','name','slug');}))
	->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
	->active()->whereIn('_id', $serviceids_array);	

	if(!empty($category)){
		$query->whereIn('servicecategory_id', $category );
	}

	if(!empty($subcategory)){
		$query->whereIn('servicesubcategory_id', $subcategory );
	}

	if(!empty($location)){
		$query->whereIn('location_id', $location );
	}

	if(!empty($finder)){
		$query->whereIn('finder_id', $finder );
	}

	$services 		= 	$query->orderBy('ordering', 'desc')->get()->toArray();

	foreach ($services as $key => $value) {
		$item  	   				=  	(!is_array($value)) ? $value->toArray() : $value;
		// $ratecardsarr    	=   Ratecard::with('serviceoffers')->whereIn('_id', $ratecardids_array )->where('service_id', intval($item['_id']) )->get()->toArray();		

		$service_ratedcards 	= 	[];
		$ratecardsarr    		=   Ratecard::with('serviceoffers')->whereIn('_id', $ratecardids_array )->where('service_id', intval($item['_id']) )->get()->toArray();	
		if($ratecardsarr){
			foreach ($ratecardsarr as $key => $value) {
				if(intval($value['validity'])%360 == 0){
					$value['validity']  = intval(intval($value['validity'])/360);
					if(intval($value['validity']) > 1){
						$value['validity_type'] = "years";
					}else{
						$value['validity_type'] = "year";
					}
				}

				if(intval($value['validity'])%30 == 0){
					$value['validity']  = intval(intval($value['validity'])/30);
					if(intval($value['validity']) > 1){
						$value['validity_type'] = "months";
					}else{
						$value['validity_type'] = "month";
					}
				}
				array_push($service_ratedcards, $value);
			}
		}


		$finderarr 				= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->where('_id', (int) $item['finder_id'])->first();

		$data = [
		'_id' => $item['_id'],
		'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
		'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
		'address' => (isset($item['address']) && $item['address'] != '') ? trim($item['address']) : "",
		'timing' => (isset($item['timing']) && $item['timing'] != '') ? trim($item['timing']) : "",
		'buyable' => (isset($item['buyable']) && $item['buyable'] != '') ? trim($item['buyable']) : "",
		'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
		'service_coverimage_thumb' => (isset($item['service_coverimage_thumb']) && $item['service_coverimage_thumb'] != '') ? strtolower($item['service_coverimage_thumb']) : "",
		'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
		'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
		'workout_tags' => (isset($item['workout_tags']) && $item['workout_tags'] != '') ? $item['workout_tags'] : [],
		'batches' => (isset($item['servicebatches']) && $item['servicebatches'] != '') ? $item['servicebatches'] : [],
		'service_ratedcards' => (isset($service_ratedcards) && !empty($service_ratedcards)) ? $service_ratedcards : [],
		'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type','commercial_type','coverimage','info','category','location','contact','finder_poc_for_customer_name','finder_poc_for_customer_mobile','finder_vcc_email')),
		];

					// return $data;
		array_push($fitmaniamemberships, $data);
	}


	$date 			=  	Carbon::now();
	$timestamp 		= 	strtotime($date);
	$stringdate 	= 	$date->toFormattedDateString();
	$weekday 		= 	strtolower(date( "l", $timestamp));
	$categoryday   	=   $this->categorydayCitywise($city,$weekday);

	$leftside 					= 	[];
	// $leftside['category'] 	 	= 	Servicecategory::active()->where('parent_id', 0)->orderBy('ordering')->get(array('_id','name','slug','parent_id'));
	// if(!empty($category)){
	// 	$leftside['subcategory'] 	= 	Servicecategory::active()->whereIn('parent_id', $category)->orderBy('ordering')->get(array('_id','name','slug','parent_id'));
	// }else{
	// 	$leftside['subcategory'] 	= 	Servicecategory::active()->where('parent_id', '!=', 0)->orderBy('ordering')->get(array('_id','name','slug','parent_id'));
	// }

	$leftside['category'] 		= 	[];
	$leftside['subcategory'] 	= 	[];
	$leftside['locations'] 		= 	$this->getLocationCluster($city_id);
	$leftside['finders'] 		= 	Finder::active()->whereIn('_id', $finderids_array)->orderBy('ordering')->get(array('_id','title','slug'));

	$responsedata 				=  ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday, 'leftside' => $leftside, 'fitmaniamemberships' => $fitmaniamemberships, 'message' => 'Fitmania Memberships :)'];
return Response::json($responsedata, 200);
}



public function serachDodAndDow(){

		// return Input::json()->all();
	$from 						=	(Input::json()->get('from')) ? intval(Input::json()->get('from')) : 0;
	$size 						=	(Input::json()->get('size')) ? intval(Input::json()->get('size')) : 10;
	$city 						=	(Input::json()->get('city')) ? strtolower(Input::json()->get('city')) : 'mumbai';
	$city_id					=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;
	$start_price				=	(Input::json()->get('start_price')) ? intval(Input::json()->get('start_price')) : "";
	$end_price					=	(Input::json()->get('end_price')) ? intval(Input::json()->get('end_price')) : "";
	$start_duration				=	(Input::json()->get('start_duration')) ? intval(Input::json()->get('start_duration')) : "";
	$end_duration				=	(Input::json()->get('end_duration')) ? intval(Input::json()->get('end_duration')) : "";
	$category 					=	(Input::json()->get('category')) ? array_map('intval', Input::json()->get('category')) : [];		
	$subcategory 				=	(Input::json()->get('subcategory')) ? array_map('intval', Input::json()->get('subcategory')) : [];		
	$location 					=	(Input::json()->get('location')) ? array_map('intval', Input::json()->get('location')) : [];	
	$finder 					=	(Input::json()->get('finder')) ? array_map('intval', Input::json()->get('finder')) : [];	


	$date 			=  	Carbon::now();
	$timestamp 		= 	strtotime($date);
	$stringdate 	= 	$date->toFormattedDateString();
	$weekday 		= 	strtolower(date( "l", $timestamp));
	$categoryday   	=   $this->categorydayCitywise($city,$weekday);



	$serviceoffers  			= 	Serviceoffer::where('city_id', '=', $city_id)->whereIn("type" ,["fitmania-dod", "fitmania-dow"])->get()->toArray();
	$serviceids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'service_id')) ; 
	$ratecardids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'ratecard_id')) ; 
	$finderids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'finder_id')) ; 
	$fitmaniadods 				=	[];

	$query	 					= 	Service::active();		
	if(!empty($category)){
		$query->whereIn('servicecategory_id', $category );
	}

	if(!empty($subcategory)){
		$query->whereIn('servicesubcategory_id', $subcategory );
	}

	if(!empty($location)){
		$query->whereIn('location_id', $location );
	}

	if(!empty($finder)){
		$query->whereIn('finder_id', $finder );
	}

	$serviceids_array 		= 	$query->orderBy('ordering', 'desc')->lists('_id');

	$dealsofdayquery 	=	Serviceoffer::with('finder')->with('ratecard')->where('city_id', '=', $city_id);
							// ->where('start_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
							// ->where('end_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ))

	if($start_duration != "" || $start_duration != 0 || $end_duration != "" || $end_duration != 0){
		$ratecardidquery 	= 	Ratecard::active();

		if($start_duration != "" || $start_duration != 0){
			$ratecardidquery->where('validity', '>=', intval($start_duration));
		}

		if($end_duration != "" || $end_duration != 0){
			$ratecardidquery->where('validity', '<=', intval($end_duration));
		}
		$ratecardids_array 		= 	$ratecardidquery->orderBy('ordering', 'desc')->lists('_id');
	}

	if(isset($ratecardids_array) && !empty($ratecardids_array)){
		$dealsofdayquery->whereIn('ratecard_id', $ratecardids_array);
	}

	if($start_price != "" || $start_price != 0){
		$dealsofdayquery->where('price', '>=', intval($start_price));
	}
	if($end_price != "" || $end_price != 0){
		$dealsofdayquery->where('price', '<=', intval($end_price));
	}
	$dealsofdaycolleciton 	=	$dealsofdayquery->where("active" , "=" , 1)->whereIn("type" ,["fitmania-dod", "fitmania-dow"])->whereIn('service_id', $serviceids_array)->take($size)->skip($from)->orderBy('order', 'desc')->get()->toArray();


	foreach ($dealsofdaycolleciton as $key => $value) {
		$dealdata = $this->transformDod($value);
		array_push($fitmaniadods, $dealdata);
	}

	$leftside 					= 	[];

	// $leftside['category'] 	 	= 	Servicecategory::active()->where('parent_id', 0)->orderBy('ordering')->get(array('_id','name','slug','parent_id'));
	// if(!empty($category)){
	// 	$leftside['subcategory'] 	= 	Servicecategory::active()->whereIn('parent_id', $category)->orderBy('ordering')->get(array('_id','name','slug','parent_id'));
	// }else{
	// 	$leftside['subcategory'] 	= 	Servicecategory::active()->where('parent_id', '!=', 0)->orderBy('ordering')->get(array('_id','name','slug','parent_id'));
	// }

	$leftside['category'] 		= 	[];
	$leftside['subcategory'] 	= 	[];
	$leftside['locations'] 		= 	$this->getLocationCluster($city_id);
	$leftside['finders'] 		= 	Finder::active()->whereIn('_id', $finderids_array)->orderBy('ordering')->get(array('_id','title','slug'));

	// Serviceoffer::get()->toArray();
	// $leftside['locations'] 		= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug'));

	$responsedata 	=  ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday, 'leftside' => $leftside, 'fitmaniadods' => $fitmaniadods, 'message' => 'Fitmania dod and dow :)'];
return Response::json($responsedata, 200);
}


	/**
	 * Return the specified service.
	 *
	 * @param  int  	$serviceid
	 * @return Response
	 */

	public function serviceDetail($serviceid, $offerid){

		// return $service_ratedcards    	=   Ratecard::with(array('serviceoffers' => function($query) use ($offerid){
		// 		$query->select('*')->whereNotIn('_id', [intval($offerid)]);
		// 	}))->where('service_id', intval($serviceid) )->get()->toArray();	
		

		$service = Service::with('category')->with('subcategory')->with('location')->with('city')->with('finder')->where('_id', (int) $serviceid)->first();
		if(!$service){
			$resp 	= 	array('status' => 400, 'service' => [], 'message' => 'No Service Exist :)');
			return Response::json($resp, 400);
		}
		$servicedata = $this->transformServiceDetail($service, $offerid);

		$servicecategoryid 	= intval($servicedata['servicecategory_id']);
		$servicefinderid 	= intval($servicedata['finder_id']);
		$same_vendor_service = $same_category_service = [];

		//same_vendor_service
		// $serviceoffers = Serviceoffer::where('finder_id', '=', $servicefinderid)->where("type" , "=" , "fitmania-membership-giveaways")->get()->toArray();
		$serviceoffers 			= 	Serviceoffer::where('finder_id', '=', $servicefinderid)->get()->toArray();
		$serviceids_array 		= 	array_map('intval', array_pluck($serviceoffers, 'service_id')) ; 
		$services_result		=	Service::with('category')->with('subcategory')->with('location')->with('city')->with('finder')->whereIn('_id', $serviceids_array)->get()->take(5)->toArray();	
		foreach ($services_result as $key => $service) {
			$data = $this->transformServiceDetailV1($service, $offerid);
			array_push($same_vendor_service, $data);
		}

		//same_category_service
		// $serviceoffers = Serviceoffer::where('finder_id', '=', $servicefinderid)->where("type" , "=" , "fitmania-membership-giveaways")->get()->toArray();
		$serviceoffers 			= 	Serviceoffer::where('finder_id', '=', $servicefinderid)->get()->toArray();
		$serviceids_array 		= 	array_map('intval', array_pluck($serviceoffers, 'service_id')); 
		$services_result		=	Service::with('category')->with('subcategory')->with('location')->with('city')->with('finder')->whereIn('_id', $serviceids_array)->where('servicecategory_id', '=', $servicecategoryid)->get()->take(5)->toArray();		
		foreach ($services_result as $key => $service) {
			$data = $this->transformServiceDetailV1($service, $offerid);
			array_push($same_category_service, $data);
		}

		$resp 	= 	array('service' => $servicedata,'same_vendor_service' => $same_vendor_service,'same_category_service' => $same_category_service,  'message' => 'Particular Service Info');
		return Response::json($resp, 200);
	}


	private function transformServiceDetail($service, $offerid = ''){

		$item  	   				=  	(!is_array($service)) ? $service->toArray() : $service;
		$service_ratedcards 	= 	[];
		if($offerid != ""){
			$ratecardsarr    	=   Ratecard::with(array('serviceoffers' => function($query) use ($offerid){
				$query->select('*')->whereIn('_id', [intval($offerid)]);
			}))->where('service_id', intval($item['_id']) )->get()->toArray();	
		}else{
			$ratecardsarr    	=   Ratecard::with('serviceoffers')->where('service_id', intval($item['_id']) )->get()->toArray();					
		}

		if($ratecardsarr){
			foreach ($ratecardsarr as $key => $value) {
				if(intval($value['validity'])%360 == 0){
					$value['validity']  = intval(intval($value['validity'])/360);
					if(intval($value['validity']) > 1){
						$value['validity_type'] = "years";
					}else{
						$value['validity_type'] = "year";
					}
				}

				if(intval($value['validity'])%30 == 0){
					$value['validity']  = intval(intval($value['validity'])/30);
					if(intval($value['validity']) > 1){
						$value['validity_type'] = "months";
					}else{
						$value['validity_type'] = "month";
					}
				}
				array_push($service_ratedcards, $value);
			}

		}


		$data = array(
			'_id' => $item['_id'],
			'servicecategory_id' => $item['servicecategory_id'],
			'location_id' => $item['location_id'],
			'finder_id' => $item['finder_id'],
			'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
			'buyable' => (isset($item['buyable']) && $item['buyable'] != '') ? trim($item['buyable']) : "",
			'timing' => (isset($item['timing']) && $item['timing'] != '') ? trim($item['timing']) : "",
			'address' => (isset($item['address']) && $item['address'] != '') ? trim($item['address']) : "",
			'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
			'service_coverimage_thumb' => (isset($item['service_coverimage_thumb']) && $item['service_coverimage_thumb'] != '') ? strtolower($item['service_coverimage_thumb']) : "",
			'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
			'lat' => (isset($item['lat']) && $item['lat'] != '') ? strtolower($item['lat']) : "",
			'lon' => (isset($item['lon']) && $item['lon'] != '') ? strtolower($item['lon']) : "",
			'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
			'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
			'workout_tags' => (isset($item['workout_tags']) && !empty($item['workout_tags'])) ? array_map('strtolower',$item['workout_tags']) : "",
			'short_description' => (isset($item['short_description']) && $item['short_description'] != '') ? $item['short_description'] : "", 
			'timing' => (isset($item['timing']) && $item['timing'] != '') ? $item['timing'] : "", 
			'address' => (isset($item['address']) && $item['address'] != '') ? $item['address'] : "", 
			'category' =>  array_only($item['category'], array('_id', 'name', 'slug', 'parent_name','what_i_should_carry','what_i_should_expect','description')) ,
			'subcategory' =>  array_only($item['subcategory'], array('_id', 'name', 'slug', 'parent_name','what_i_should_carry','what_i_should_expect','description')) ,
			'location' =>  array_only($item['location'], array('_id', 'name', 'slug')) ,
			'city' =>  array_only($item['city'], array('_id', 'name', 'slug')) ,
			'trialschedules' => (isset($item['trialschedules']) && !empty($item['trialschedules'])) ? $item['trialschedules'] : "",
			'service_gallery' => (isset($item['service_gallery']) && !empty($item['service_gallery'])) ? $item['service_gallery'] : "",
			'batches' => (isset($item['servicebatches']) && !empty($item['servicebatches'])) ? $item['servicebatches'] : "",
			'serviceratecard' => (isset($service_ratedcards) && !empty($service_ratedcards)) ? $service_ratedcards : "",
			);

if(isset($item['finder']) && $item['finder'] != ''){
	$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->where('_id', (int) $service['finder_id'])
	->first();
	$data['finder'] = array_only($item['finder'], array('_id', 'title', 'slug', 'coverimage', 'city_id', 'photos', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect', 'total_rating_count', 'average_rating', 'detail_rating_summary_count', 'detail_rating_summary_average', 'reviews','info'));
}else{
	$data['finder'] = NULL;
}

if(isset($item['trainer_id']) && $item['trainer_id'] != ''){
	$servicetrainer = Servicetrainer::remember(Config::get('app.cachetime'))->findOrFail( intval($item['trainer_id']) );
	if($servicetrainer){
		$trainerdata = $servicetrainer->toArray();
		$data['trainer'] = array_only($trainerdata, array('_id', 'name', 'bio', 'trainer_pic'));
	}
}else{
	$data['trainer'] = NULL;
}

return $data;
}


private function transformServiceDetailV1($service, $offerid = ''){

	$item  	   				=  	(!is_array($service)) ? $service->toArray() : $service;
	$service_ratedcards 	= 	[];
	$ratecardsarr    	=   Ratecard::with(array('serviceoffers' => function($query) use ($offerid){
		$query->select('*')->whereNotIn('_id', [intval($offerid)]);
	}))->where('service_id', intval($item['_id']) )->get()->toArray();	
	
	// $ratecardsarr    		=   Ratecard::with(array('serviceoffers' => function($query) use ($offerid){
	// 	$query->select('*')->whereNotIn('_id', [intval($offerid)]);
	// }))->where('service_id', intval($item['_id']) )->get()->toArray();	

	if($ratecardsarr){
		foreach ($ratecardsarr as $key => $value) {
			if(intval($value['validity'])%360 == 0){
				$value['validity']  = intval(intval($value['validity'])/360);
				if(intval($value['validity']) > 1){
					$value['validity_type'] = "years";
				}else{
					$value['validity_type'] = "year";
				}
			}

			if(intval($value['validity'])%30 == 0){
				$value['validity']  = intval(intval($value['validity'])/30);
				if(intval($value['validity']) > 1){
					$value['validity_type'] = "months";
				}else{
					$value['validity_type'] = "month";
				}
			}
			array_push($service_ratedcards, $value);
		}

	}


	$data = array(
		'_id' => $item['_id'],
		'servicecategory_id' => $item['servicecategory_id'],
		'location_id' => $item['location_id'],
		'finder_id' => $item['finder_id'],
		'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
		'buyable' => (isset($item['buyable']) && $item['buyable'] != '') ? trim($item['buyable']) : "",
		'timing' => (isset($item['timing']) && $item['timing'] != '') ? trim($item['timing']) : "",
		'address' => (isset($item['address']) && $item['address'] != '') ? trim($item['address']) : "",
		'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
		'service_coverimage_thumb' => (isset($item['service_coverimage_thumb']) && $item['service_coverimage_thumb'] != '') ? strtolower($item['service_coverimage_thumb']) : "",
		'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
		'lat' => (isset($item['lat']) && $item['lat'] != '') ? strtolower($item['lat']) : "",
		'lon' => (isset($item['lon']) && $item['lon'] != '') ? strtolower($item['lon']) : "",
		'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
		'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
		'workout_tags' => (isset($item['workout_tags']) && !empty($item['workout_tags'])) ? array_map('strtolower',$item['workout_tags']) : "",
		'short_description' => (isset($item['short_description']) && $item['short_description'] != '') ? $item['short_description'] : "", 
		'timing' => (isset($item['timing']) && $item['timing'] != '') ? $item['timing'] : "", 
		'address' => (isset($item['address']) && $item['address'] != '') ? $item['address'] : "", 
		'category' =>  array_only($item['category'], array('_id', 'name', 'slug', 'parent_name','what_i_should_carry','what_i_should_expect','description')) ,
		'subcategory' =>  array_only($item['subcategory'], array('_id', 'name', 'slug', 'parent_name','what_i_should_carry','what_i_should_expect','description')) ,
		'location' =>  array_only($item['location'], array('_id', 'name', 'slug')) ,
		'city' =>  array_only($item['city'], array('_id', 'name', 'slug')) ,
		'trialschedules' => (isset($item['trialschedules']) && !empty($item['trialschedules'])) ? $item['trialschedules'] : "",
		'service_gallery' => (isset($item['service_gallery']) && !empty($item['service_gallery'])) ? $item['service_gallery'] : "",
		'batches' => (isset($item['servicebatches']) && !empty($item['servicebatches'])) ? $item['servicebatches'] : "",
		'serviceratecard' => (isset($service_ratedcards) && !empty($service_ratedcards)) ? $service_ratedcards : "",
		);

if(isset($item['finder']) && $item['finder'] != ''){
	$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->where('_id', (int) $service['finder_id'])
	->first();
	$data['finder'] = array_only($item['finder'], array('_id', 'title', 'slug', 'coverimage', 'city_id', 'photos', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect', 'total_rating_count', 'average_rating', 'detail_rating_summary_count', 'detail_rating_summary_average', 'reviews','info'));
}else{
	$data['finder'] = NULL;
}

if(isset($item['trainer_id']) && $item['trainer_id'] != ''){
	$servicetrainer = Servicetrainer::remember(Config::get('app.cachetime'))->findOrFail( intval($item['trainer_id']) );
	if($servicetrainer){
		$trainerdata = $servicetrainer->toArray();
		$data['trainer'] = array_only($trainerdata, array('_id', 'name', 'bio', 'trainer_pic'));
	}
}else{
	$data['trainer'] = NULL;
}

return $data;
}


public function buyOffer(){

	// return Input::json()->all();
	$data			=	Input::json()->all();		
	if(empty($data['order_id'])){
		return Response::json(array('status' => 404,'message' => "Data Missing Order Id - order_id"),404);			
	}
	$orderid 	=	(int) Input::json()->get('order_id');
	$order 		= 	Order::findOrFail($orderid);
	$orderData 	= 	$order->toArray();

	//Maintain Slab for deals of day
	if($orderData['type'] == 'fitmania-dod' || $orderData['type'] == 'fitmania-dow'){
		if(empty($orderData['serviceoffer_id']) ){
			return Response::json(array('status' => 404,'message' => "Data Missing - serviceoffer_id"),404);				
		}
	}

	// if($orderData['status'] == 0){
	$buydealofday 	=	$order->update(['status' => '1']);
	if($buydealofday){
			//send email & sms
		$sndsSmsCustomer		= 	$this->customersms->buyServiceThroughFitmania($orderData);
		$sndsEmailCustomer		= 	$this->customermailer->buyServiceThroughFitmania($orderData);
		$sndsEmailFinder		= 	$this->findermailer->buyServiceThroughFitmania($orderData);

		/* limit | buyable | sold | acitve | left */
		if($orderData['type'] == 'fitmania-dod' || $orderData['type'] == 'fitmania-dow'){
			$serviceoffer 	= 	Serviceoffer::find(intval($orderData['serviceoffer_id']));
			$offer_limit 	=  	intval($serviceoffer->limit);
			$offer_sold 	=  	intval($serviceoffer->sold) + 1;
			$offer_left 	=  	$offer_limit - $offer_sold;
			$offer_active  	=  	1;
			if(intval($offer_limit) == intval($offer_sold)){
				$offer_active	=	0; 
			}
			$service_offerdata  = ['sold' => $offer_sold, 'left' => $offer_left, 'active' => $offer_active];
			$success_order = $serviceoffer->update($service_offerdata);

			if($success_order){
				$maintainActiveFlagData = $this->maintainActiveFlag($serviceoffer->service_id);
				$resp 	= 	array('status' => 200, 'message' => "Successfully buy Serivce through Fitmania :)");
				return Response::json($resp);				
			}else{
				$resp 	= 	array('status' => 200, 'message' => "fail :)");
				return Response::json($resp);	
			}
		}
		}//buydealofday
	// }

	}


	public function maintainActiveFlag($serviceid = NULL){

		$date 				=	Carbon::now();
		$date 				=  	'04-01-2016';
		$timestamp 			= 	strtotime($date);

		if($serviceid != NULL){
			$ratecardoffers 	=	Serviceoffer::whereIn("type" ,["fitmania-dod", "fitmania-dow"])->where("service_id", intval($serviceid))->orderBy('order', 'asc')->get()->groupBy('ratecard_id')->toArray();			
		}else{
			$ratecardoffers 	=	Serviceoffer::whereIn("type" ,["fitmania-dod", "fitmania-dow"])->orderBy('order', 'asc')->get()->groupBy('ratecard_id')->toArray();			
		}

		foreach ($ratecardoffers as $key => $offers) {
   		// return $offers;
			$initial_acitve_flag = 0;
			foreach ($offers as $key => $offer) {
				if($initial_acitve_flag == 1){ continue; }

				$serviceObj =	Serviceoffer::find(intval($offer->_id));
				$limit 		=	intval($serviceObj->limit);
				$sold 		=	intval($serviceObj->sold);

				if((strtotime($serviceObj->start_date) <= $timestamp) &&  (strtotime($serviceObj->end_date) > $timestamp) ){
					if(($limit - $sold) > 0){
						$serviceObj->update(['active' => 1]);
						$initial_acitve_flag = 1;
					}
				}
   			}//foreach
   		}//foreach

   		// return true;

   		return $ratecardoffers 	=	Serviceoffer::whereIn("type" ,["fitmania-dod"])->orderBy('order', 'asc')->get()->groupBy('ratecard_id')->toArray();
   	}


   	// used to reset buyable value it will hit by sidekick after 12 min
   	public function checkFitmaniaOrder($order_id){

   		$order 		= 	Order::find(intval($order_id));
   		$orderData 	= 	$order->toArray();

   		if($orderData['status'] == 0){
   			$serviceoffer 		= 	Serviceoffer::find(intval($orderData['serviceoffer_id']));
   			if(!$serviceoffer){
   				return Response::json(array('status' => 404,'message' => "serviceoffer does not exist"),404);				
   			}

   			$offer_buyable 		=  	intval($serviceoffer->buyable) + 1;
   			$service_offerdata  = 	['buyable' => intval($offer_buyable)];
   			$success_order 		= 	$serviceoffer->update($service_offerdata);

   			return Response::json(array('status' => 200,'message' => "serviceoffer buyable update scuessfull"),200);				
   		}

   	}


   	public function serachFinders (){

   		$keyword 				=	(Input::json()->get('finder')) ? trim(Input::json()->get('finder')) : "";
   		$city_id				=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;

   		$serviceoffers_array  	= 	Serviceoffer::where('city_id', $city_id)->whereIn("type" ,  ['fitmania-dod','fitmania-dow', 'fitmania-membership-giveaways'])->get(['finder_id'])->toArray();
   		$finderids_array 		= 	array_map('intval', array_pluck($serviceoffers_array, 'finder_id')) ; 

   		$query 					= 	Finder::active()->orderBy('title')->whereIn('_id', $finderids_array );
   		if ($keyword != '') {
   			$query->where('title', 'LIKE', '%' . $keyword . '%');
   		}

   		$finders = $query->get(['_id','title','slug']);
   		return Response::json(array('status' => 200,'finders' => $finders, 'message' => 'Finder list :)'),200);				
   	}


   	// used to check buyable of particular service offerid
   	public function checkBuyableValue($offerid){

   		$serviceoffer 		= 	Serviceoffer::find(intval($offerid));

   		if(isset($serviceoffer->buyable) && intval($serviceoffer->buyable) < 1){
   			$responsedata 	= ['serviceoffer' => "", 'exist' => false, 'message' => 'No serviceoffer Exist :)'];
return Response::json($responsedata, 400);
}

$responsedata 	= ['serviceoffer' => $serviceoffer, 'exist' => true, 'message' => 'serviceoffer Exist :)'];
return Response::json($responsedata, 200);
}


public function updateCityIdFromFinderCityId(){

	$serviceoffers_array  	= 	Serviceoffer::whereIn("type" ,  ['fitmania-dod','fitmania-dow', 'fitmania-membership-giveaways'])->get(['finder_id'])->toArray();

	foreach ($serviceoffers_array as $key => $value) {
		$finder 		= 		Finder::find(intval($value['finder_id']));
		$serviceoffer 	= 		Serviceoffer::find(intval($value['_id']));
		$city_id 		= 		intval($finder->city_id);
		$success_order 	= 		$serviceoffer->update(['city_id' => $city_id]);

	}
}


public function checkCouponcode($code){

	$couponcode  	= 	Couponcode::where("code" , strtolower(trim($code)))->first();

	if(count($couponcode) < 1){
		$responsedata 	= ['couponcode' => "", 'exist' => false, 'message' => 'No couponcode Exist :)'];
return Response::json($responsedata, 200);
}

$responsedata 	= ['couponcode' => $couponcode, 'exist' => true, 'message' => 'couponcode Exist :)'];
return Response::json($responsedata, 200);

}



public function getLocationCluster($city_id){

	$location_clusters  =  [];
	// $location_clusters_rs 	= 	Locationcluster::with('locations')->where('city_id', '=', intval($city_id))->where('status', '=', '1')->orderBy('name')->get()->toArray();
	$location_clusters_rs 	= 	Locationcluster::where('city_id', '=', intval($city_id))->where('status', '=', '1')->orderBy('name')->get()->toArray();
	
	foreach ($location_clusters_rs as $key => $value) {
		$locations  			=   Location::where('locationcluster_id', '=', intval($value['_id']))->where('status', '=', '1')->orderBy('name')->get(['_id', 'name', 'slug'])->toArray();
		$location_cluster 		= 	array_except($value, array('locations','updated_at','created_at')); 
		$location_cluster['locations'] 		=  pluck( $locations , array('_id', 'name', 'slug'));
		array_push($location_clusters, $location_cluster);
	}
	return $location_clusters;
}



public function exploreCategoryOffers($city_id = 1){



}



public function exploreLocationClusterOffers($city_id = 1){

	$categorys_arr     		=  	array('gyms' => '65', 'yoga' => '1',  'crossfit' => '111',  'pilates' => '4');
	$location_clusters 		= 	Locationcluster::where('city_id', '=', intval($city_id))->where('status', '=', '1')->orderBy('name')->get()->toArray();
	
	$explore_location_cluster_offers = [];

	foreach ($location_clusters as $key => $value) {
		$locations  				=   Location::where('locationcluster_id', '=', intval($value['_id']))->where('status', '=', '1')->get(['_id','name']);
		$locationids_array 			= 	array_map('intval', array_pluck($locations, '_id')) ; 

		$clusteroffers 				= 	[];
		foreach ($categorys_arr as $k => $v) {
			$servicecategory_name  	= 	$k;
			$servicecategory_id  	= 	intval($v);
			$services				=	Service::active()->whereIn('servicecategory_id', [$servicecategory_id])->whereIn('location_id', $locationids_array)->lists('_id');
			$serviceoffers_cnt  	= 	Serviceoffer::whereIn("type" ,  ['fitmania-dod','fitmania-dow', 'fitmania-membership-giveaways'])->whereIn("service_id" , $services)->count();
			$offer = ['category_name' => $servicecategory_name,'category_id' => $servicecategory_id,'cnt'=>$serviceoffers_cnt];
			array_push($clusteroffers, $offer);
		}
		$locationcluster['offers']  = 	$clusteroffers;	
		$locationcluster['name']  	= 	$value['name'];	
		$locationcluster['_id']  	= 	intval($value['_id']);	
		
		array_push($explore_location_cluster_offers, $locationcluster);

	}
	
	return $explore_location_cluster_offers;



	// $fitmaniahomepageobj 		=	Fitmaniahomepage::where('city_id', '=', $city_id)->first();

}




}
