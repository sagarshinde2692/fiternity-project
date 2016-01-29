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

		$categorydays_arr     =  array('surprise' => 'all', 'personal trainers' => 'all', 'mix bag' => 'all', 'zumba' => '19', 'gym' => '65', 'crossfit' => '111,5','mma' => '3', 'dance' => '2', 'yoga' => '1,4');
		return $categorydays_arr[$category];
	}


	public function categoryCitywiseSuccessPage($city = 'mumbai', $from = '', $size = ''){

		$category_info  = [];
		$tommorow_date 	=	\Carbon\Carbon::tomorrow();
		$timestamp 		= 	strtotime($tommorow_date);
		$tommorow 		= 	strtolower(date( "l", $timestamp));

		$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
		if(!$citydata){
			return $this->responseNotFound('City does not exist');
		}

		$city_name 		= 	$citydata['name'];
		$city_id		= 	(int) $citydata['_id'];	
		$from 			=	($from != '') ? intval($from) : 0;
		$size 			=	($size != '') ? intval($size) : 10;

		switch (strtolower(trim($city_name))) {
			case 'mumbai':
			$categorydays_arr     =  array(  'tuesday' => 'surprise', 'wednesday' => 'surprise','thursday' => 'mma', 'friday' => 'dance', 'saturday' => 'yoga', 'sunday' => 'surprise', 'monday' => 'surprise');
			break;
			
			case 'pune':
			$categorydays_arr     =  array( 'tuesday' => 'surprise', 'wednesday' => 'surprise','thursday' => 'mma', 'friday' => 'dance', 'saturday' => 'yoga', 'sunday' => 'surprise', 'monday' => 'surprise');
			break;

			case 'bangalore':
			$categorydays_arr     =  array( 'tuesday' => 'surprise', 'wednesday' => 'surprise','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit', 'sunday' => 'surprise', 'monday' => 'surprise');
			break;	

			case 'delhi':
			$categorydays_arr     =  array( 'tuesday' => 'surprise', 'wednesday' => 'surprise','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit', 'sunday' => 'surprise', 'monday' => 'surprise');
			break;

			case 'gurgaon':
			$categorydays_arr     =  array( 'tuesday' => 'surprise', 'wednesday' => 'surprise','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit', 'sunday' => 'surprise', 'monday' => 'surprise');
			break;		
		}

		$fitmaniadods			=	[];
		$dealsofdaycolleciton 	=	Serviceoffer::with('finder')->with('ratecard')->where('city_id', '=', $city_id)->where("type" , "=" , "fitmania-dod")->where("active" , "=" , 1)->take($size)->skip($from)->orderBy('order', 'desc')->get()->toArray();

		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transformDod($value);
			array_push($fitmaniadods, $dealdata);
		}


		$responsedata 	= ['categorydays_arr' => $categorydays_arr, 'deals' => $fitmaniadods,  'message' => 'Fitmania categoryCitywiseSuccessPage :)'];
return Response::json($responsedata, 200);
}

public function categorydayCitywise($city, $weekday){

	$category_info  = [];
	$tommorow_date 	=	\Carbon\Carbon::tomorrow();
	$timestamp 		= 	strtotime($tommorow_date);
	$tommorow 		= 	strtolower(date( "l", $timestamp));


	switch (strtolower(trim($city))) {
		case 'mumbai':
		$categorydays_arr     =  array('sunday' => 'surprise', 'monday' => 'surprise', 'tuesday' => 'surprise', 'wednesday' => 'crossfit','thursday' => 'mma', 'friday' => 'dance', 'saturday' => 'yoga');
		break;

		case 'pune':
		$categorydays_arr     =  array('sunday' => 'surprise', 'monday' => 'surprise', 'tuesday' => 'surprise', 'wednesday' => 'crossfit','thursday' => 'mma', 'friday' => 'dance', 'saturday' => 'yoga');
		break;

		case 'bangalore':
		$categorydays_arr     =  array('sunday' => 'surprise', 'monday' => 'surprise', 'tuesday' => 'surprise', 'wednesday' => 'yoga','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit');
		break;	

		case 'delhi':
		$categorydays_arr     =  array('sunday' => 'surprise', 'monday' => 'surprise', 'tuesday' => 'surprise', 'wednesday' => 'yoga','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit');
		break;

		case 'gurgaon':
		$categorydays_arr     =  array('sunday' => 'surprise', 'monday' => 'surprise', 'tuesday' => 'surprise', 'wednesday' => 'yoga','thursday' => 'zumba', 'friday' => 'mma', 'saturday' => 'crossfit');
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
	$size 					=	($size != '') ? intval($size) : 15;
	$date 					=  	Carbon::now();
	$timestamp 				= 	strtotime($date);
	$stringdate 			= 	$date->toFormattedDateString();
	$weekday 				= 	strtolower(date( "l", $timestamp));
	$categoryday   			=   $this->categorydayCitywise($city,$weekday);
	// $location_clusters 		= 	$this->getLocationCluster($city_id);
	$explore_locations 		= 	$this->exploreLocationClusterOffers($city_id);
	$explore_categorys 		= 	$this->exploreCategoryOffers($city_id);
	$banners 				= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->where('banner_type', '=', 'fitmania-dod')->take($size)->skip($from)->orderBy('ordering')->get();				

	if($categoryday['category_id'] != 'all'){
		$servicecategoryids  	= 	array_map('intval', explode(',', $categoryday['category_id'])) ;
	}else{
		$servicecategoryids  	=  [19,65,111,5,3,2,1,4];
	}

	$serviceids_array	 	= 	Service::active()->whereIn('servicecategory_id', $servicecategoryids )->lists('_id');
	$dealsofdaycnt 			=	Serviceoffer::where('city_id', '=', $city_id)->whereIn("type" ,["fitmania-dod", "fitmania-dow"])->whereIn('service_id', $serviceids_array)
	->where(function($query){
		$query->orWhere('active', '=', 1)->orWhere('left', '=', 0);
	})
	->count();

	$fitmaniahomepageobj 		=	Fitmaniahomepage::where('city_id', '=', $city_id)->first();
	if(count($fitmaniahomepageobj) < 1){
		$responsedata 	= ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday,  'totalcount' => $dealsofdaycnt,  'explore_locations' => $explore_locations,  'explore_categorys' => $explore_categorys, 'fitmaniadods' => [],  'banners' => $banners, 
		'fitmaniamemberships' => [],  'message' => 'No Membership Giveaway Exist :)'];
return Response::json($responsedata, 200);
}
$dodofferids 		=   array_map('intval', explode(',', $fitmaniahomepageobj->dod_serviceoffer_ids));

$fitmaniadods			=	[];
$dealsofdaycolleciton 	=	Serviceoffer::where('city_id', '=', $city_id)
->whereIn('_id', $dodofferids)
->where("type" , "=" , "fitmania-dod")
->where(function($query){
	$query->orWhere('active', '=', 1)->orWhere('left', '=', 0);
})
->with('finder')->with('ratecard')
->take($size)->skip($from)->orderBy('order', 'desc')->get()->toArray();

foreach ($dealsofdaycolleciton as $key => $value) {
	$dealdata = $this->transformDod($value);
	array_push($fitmaniadods, $dealdata);
}

$fitmaniadods_orderby = [];
foreach ($dodofferids as $key => $oid) {
	$offer = 	head(array_where($fitmaniadods, function($key, $value) use ($oid){
		if($value['_id'] == $oid){
			return $value;
		}
	}));

	// var_dump($offer);exit();
	array_push($fitmaniadods_orderby, $offer);
}




$categoryday['today'] = str_replace("mma","MMA & KICKBOXING",$categoryday['today']);
$categoryday['today'] = str_replace("yoga","yoga & pilates",$categoryday['today']);
$categoryday['today'] = str_replace("crossfit","functional / crossfit",$categoryday['today']);
$categoryday['today'] = str_replace("anniversary","surprise",$categoryday['today']);

// return $fitmaniadods_orderby;
$responsedata 		= 	['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday,  'totalcount' => $dealsofdaycnt,  'explore_locations' => $explore_locations,  'explore_categorys' => $explore_categorys, 'fitmaniadods' => $fitmaniadods_orderby, 
'banners' => $banners, 'message' => 'Fitmania Home Page Dods :)'];

return Response::json($responsedata, 200);

}

private function transformDod($offers){

	$item  	   		=  	(!is_array($offers)) ? $offers->toArray() : $offers;
	$ratecardarr  	=  	(!is_array($item['service_offer_ratecard'])) ?  (array) $item['service_offer_ratecard'] : $item['service_offer_ratecard'];
	$finderarr   	=  	(!is_array($item['finder'])) ?  (array) $item['finder'] : $item['finder'];
	$servicearr 	= 	Service::where('_id', (int) $item['service_id'])->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->with(array('category'=>function($query){$query->select('_id','name','slug');}))
	->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
	->with(array('city'=>function($query){$query->select('_id','name','slug');}))->first();

	// if($servicearr == NULL){
	// 	echo $item['service_id']; exit();
	// }
	
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
	'buyable' => (isset($item['buyable'])) ? intval($item['buyable']) : intval($item['limit']),
	'left' => (isset($item['left'])) ? intval($item['left']) : intval($item['limit']),
	'start_date' => (isset($item['start_date']) && $item['start_date'] != '') ? $item['start_date'] : "",
	'end_date' => (isset($item['end_date']) && $item['end_date'] != '') ? $item['end_date'] : "",
	'ratecard' => (isset($item['ratecard']) && $item['ratecard'] != '') ? array_only( $ratecardarr , ['_id','type', 'price', 'special_price', 'duration', 'duration_type', 'validity', 'validity_type', 'remarks', 'order'] )  : "",
	'finder' => (isset($item['finder']) && $item['finder'] != '') ? array_only( $finderarr , ['_id','title','slug','finder_coverimage','coverimage','average_rating', 'contact', 'total_rating_count', 'average_rating', 'detail_rating_summary_count', 'detail_rating_summary_average'] )  : "",		
	'service' =>  array_only($servicearr->toArray(), array('name','_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage', 'service_coverimage_thumb', 'category',  'subcategory', 'location', 'address','timing','servicebatches' )),

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
	$explore_locations 		= 	$this->exploreLocationClusterOffers($city_id);
	$explore_categorys 		= 	$this->exploreCategoryOffers($city_id);

	$fitmaniahomepageobj 		=	Fitmaniahomepage::where('city_id', '=', $city_id)->first();
	if(count($fitmaniahomepageobj) < 1){
		$responsedata 	= ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday, 'banners' => $banners, 'location_clusters' => $location_clusters,  'fitmaniamemberships' => [],  'message' => 'No Membership Giveaway Exist :)'];
return Response::json($responsedata, 200);
}

$serviceids 				=   array_map('intval', explode(',', $fitmaniahomepageobj->ratecardids));
$serviceoffers  			= 	Serviceoffer::where('city_id', '=', $city_id)->where("type" , "=" , "fitmania-membership-giveaways")->whereIn('service_id', $serviceids)->with('finder')->with('ratecard')->get();

$serviceids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'service_id')) ; 
$ratecardids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'ratecard_id')) ; 

$query	 					= 	Service::active()->whereIn('_id', $serviceids);	

$services 					= 	$query->with(array('city'=>function($query){$query->select('_id','name','slug');}))
->with(array('location'=>function($query){$query->select('_id','name','slug');}))
->with(array('category'=>function($query){$query->select('_id','name','slug');}))
->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
->orderBy('ordering', 'desc')->get()->toArray();

$fitmaniamemberships 	=	[];
foreach ($services as $key => $value) {
	$item  	   				=  	(!is_array($value)) ? $value->toArray() : $value;
	$service_ratedcards 	= 	[];
	$ratecardsarr    		=   Ratecard::whereIn('_id', $ratecardids_array )->where('service_id', intval($item['_id']) )->with('serviceoffers')->get()->toArray();	
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


	$finderarr 				= 	Finder::where('_id', (int) $item['finder_id'])->with(array('city'=>function($query){$query->select('_id','name','slug');}))
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->with(array('category'=>function($query){$query->select('_id','name','slug');}))
	->first();
	$data = [
	'_id' => $item['_id'],
	'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
	'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
	'location' => (isset($item['location']) && $item['location'] != '') ? array_only($item['location'], array('_id', 'name', 'slug'))  : [],
	'address' => (isset($item['address']) && $item['address'] != '') ? trim($item['address']) : "",
	'timing' => (isset($item['timing']) && $item['timing'] != '') ? trim($item['timing']) : "",
	'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
	'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
	'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
	'workout_tags' => (isset($item['workout_tags']) && $item['workout_tags'] != '') ? $item['workout_tags'] : [],
	'batches' => (isset($item['servicebatches']) && $item['servicebatches'] != '') ? $item['servicebatches'] : [],
	'service_ratedcards' => (isset($service_ratedcards) && !empty($service_ratedcards)) ? $service_ratedcards : [],
	'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type','commercial_type','coverimage','info','category','location','contact','finder_poc_for_customer_name','finder_poc_for_customer_mobile','finder_vcc_email', 'total_rating_count', 'average_rating', 'detail_rating_summary_count', 'detail_rating_summary_average')),
	];

	array_push($fitmaniamemberships, $data);
}

$responsedata 	=  ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday, 'explore_locations' => $explore_locations,  'explore_categorys' => $explore_categorys, 
'fitmaniamemberships' => $fitmaniamemberships,  'banners' => $banners, 'location_clusters' => $location_clusters, 'message' => 'Fitmania Home Page Memberships :)'];
return Response::json($responsedata, 200);
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
		$serviceoffersquery->where('price', '>', intval($start_price));
	}
	if($end_price != "" || $end_price != 0){
		$serviceoffersquery->where('price', '<=', intval($end_price));
	}

	$serviceoffers 	=	$serviceoffersquery->get()->toArray();

	$serviceids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'service_id')) ; 
	$ratecardids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'ratecard_id')) ; 
	$finderids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'finder_id')) ; 

	$query	 					= 	Service::active()->whereIn('_id', $serviceids_array);	

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

	$cntquery 			= 	$query;
	$services_count 	= 	$cntquery->count();
	$services 			= 	$query->with(array('city'=>function($query){$query->select('_id','name','slug');}))
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->with(array('category'=>function($query){$query->select('_id','name','slug');}))
	->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
	->take($size)->skip($from)->orderBy('ordering', 'desc')->get()->toArray();
	// echo "services_count -- $services_count size -- $size from -- $from ";exit();

	foreach ($services as $key => $value) {
		$item  	   				=  	(!is_array($value)) ? $value->toArray() : $value;

		$service_ratedcards 	= 	[];
		$ratecardsarr    		=   Ratecard::whereIn('_id', $ratecardids_array )->where('service_id', intval($item['_id']) )->with('serviceoffers')->get()->toArray();	
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


		$finderarr 				= 	Finder::where('_id', (int) $item['finder_id'])->with(array('city'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->first();

		$data = [
		'_id' => $item['_id'],
		'location' => (isset($item['location']) && $item['location'] != '') ? array_only($item['location'], array('_id', 'name', 'slug'))  : [],
		'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
		'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
		'address' => (isset($item['address']) && $item['address'] != '') ? trim($item['address']) : "",
		'timing' => (isset($item['timing']) && $item['timing'] != '') ? trim($item['timing']) : "",
		'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
		'service_coverimage_thumb' => (isset($item['service_coverimage_thumb']) && $item['service_coverimage_thumb'] != '') ? strtolower($item['service_coverimage_thumb']) : "",
		'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
		'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
		'workout_tags' => (isset($item['workout_tags']) && $item['workout_tags'] != '') ? $item['workout_tags'] : [],
		'batches' => (isset($item['servicebatches']) && $item['servicebatches'] != '') ? $item['servicebatches'] : [],
		'service_ratedcards' => (isset($service_ratedcards) && !empty($service_ratedcards)) ? $service_ratedcards : [],
		'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type','commercial_type','coverimage','info','category','location','contact','finder_poc_for_customer_name','finder_poc_for_customer_mobile','finder_vcc_email', 'total_rating_count', 'average_rating', 'detail_rating_summary_count', 'detail_rating_summary_average')),
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

	$responsedata 				=  ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday, 'leftside' => $leftside, 'fitmaniamemberships' => $fitmaniamemberships, 'total_count' => $services_count, 'message' => 'Fitmania Memberships :)'];
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

	if(empty($category)){
		if($categoryday['category_id'] == 'all'){
			$category  	=  [19,65,111,5,3,2,1,4];
		}
	}

	// return $category;
	$serviceoffers  			= 	Serviceoffer::where('city_id', '=', $city_id)->whereIn("type" ,["fitmania-dod", "fitmania-dow"])->get()->toArray();
	$serviceids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'service_id')) ; 
	$ratecardids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'ratecard_id')) ; 
	$finderids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'finder_id')) ; 
	$fitmaniadods 				=	[];

	$serviceids_array  = [];
	if(!empty($category) || !empty($subcategory) || !empty($location) || !empty($finder)){

		$query	 					= 	Service::active();		
		if(!empty($category) && count($category) > 0){
			$query->whereIn('servicecategory_id', $category );
		}

		if(!empty($subcategory) && count($subcategory) > 0){
			$query->whereIn('servicesubcategory_id', $subcategory );
		}

		if(!empty($location) && count($location) > 0){
			$query->whereIn('location_id', $location );
		}

		if(!empty($finder) && count($finder) > 0){
			$query->whereIn('finder_id', $finder );
		}
		$serviceids_array 		= 	$query->orderBy('ordering', 'desc')->lists('_id');
	}


	$dealsofdayquery 	=	Serviceoffer::where('city_id', '=', $city_id)->whereIn("type" ,["fitmania-dod", "fitmania-dow"])
	->where(function($query){
		$query->orWhere('active', '=', 1)->orWhere('left', '=', 0);
	});

	// if(isset($serviceids_array) && !empty($serviceids_array)){
	$dealsofdayquery->whereIn('service_id', $serviceids_array);
	// }
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
		$dealsofdayquery->where('price', '>', intval($start_price));
	}
	if($end_price != "" || $end_price != 0){
		$dealsofdayquery->where('price', '<=', intval($end_price));
	}

	$cntquery 				= 	$dealsofdayquery;
	$dealsofday_count 		=	$cntquery->count();
	$dealsofdaycolleciton 	=	$dealsofdayquery->with('finder')->with('ratecard')->take($size)->skip($from)->orderBy('order', 'desc')->get()->toArray();

	// echo "dealsofday_count -- $dealsofday_count size -- $size from -- $from";exit();

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

	// $responsedata 	=  ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday,  'total_count' => $dealsofday_count, 'message' => 'Fitmania dod and dow :)'];

	$responsedata 	=  ['stringdate' => $stringdate, 'categoryday' => $categoryday['today'], 'category_info' => $categoryday, 'leftside' => $leftside, 'fitmaniadods' => $fitmaniadods, 
	'total_count' => $dealsofday_count, 'message' => 'Fitmania dod and dow :)'];

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
		

		$service = Service::where('_id', (int) $serviceid)->with('category')->with('subcategory')->with('location')->with('city')->with('finder')->first();
		if(!$service){
			$resp 	= 	array('status' => 400, 'service' => [], 'message' => 'No Service Exist :)');
			return Response::json($resp, 400);
		}
		$servicedata = $this->transformServiceDetail($service, $offerid);

		$servicecategoryid 		=	intval($servicedata['servicecategory_id']);
		$servicelocationid 		=	intval($servicedata['location_id']);
		$servicefinderid 		=	intval($servicedata['finder_id']);
		$serviceoffer 			=	Serviceoffer::find(intval($offerid));
		$serviceratecardid 		=	intval($serviceoffer->ratecard_id);

		$same_vendor_service = $same_category_service = [];

		//same_vendor_service
		$serviceoffers 			= 	Serviceoffer::where('finder_id', '=', $servicefinderid)->whereNotIn('ratecard_id', [$serviceratecardid])
		->where(function($query){
			$query->orWhere('active', '=', 1)->orWhere('type', '=', "fitmania-membership-giveaways");
		})
		->with('finder')->with('ratecard')->with('service')->timeout(400000000)->get()->take(5)->toArray();	
		foreach ($serviceoffers as $key => $service) {
			$data = $this->transformDod($service);
			array_push($same_vendor_service, $data);
		}
		// return $same_vendor_service;

		//same_category_service
		$services_ids			=	Service::active()->where('servicecategory_id', '=', $servicecategoryid)->where('location_id', '=', $servicelocationid)->lists('_id');		
		$serviceoffers 			= 	Serviceoffer::whereIn('service_id', $services_ids)->whereNotIn('ratecard_id', [$serviceratecardid])
		->where(function($query){
			$query->orWhere('active', '=', 1)->orWhere('type', '=', "fitmania-membership-giveaways");
		})
		->with('finder')->with('ratecard')->with('service')->timeout(400000000)->get()->take(5)->toArray();	
		foreach ($serviceoffers as $key => $service) {
			$data = $this->transformDod($service);
			array_push($same_category_service, $data);
		}
		// return $same_category_service;


		$resp 	= 	array('service' => $servicedata,'same_vendor_service' => $same_vendor_service,'same_category_service' => $same_category_service,  'message' => 'Particular Service Info');
		return Response::json($resp, 200);
	}


	private function transformServiceDetail($service, $offerid = ''){

		$item  	   				=  	(!is_array($service)) ? $service->toArray() : $service;
		$service_ratedcards 	= 	[];
		if($offerid != ""){
			$ratecardsarr    	=   Ratecard::where('service_id', intval($item['_id']) )->with(array('serviceoffers' => function($query) use ($offerid){
				$query->select('*')->whereIn('_id', [intval($offerid)]);
			}))->get()->toArray();	
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
	$finderarr 	= 	Finder::where('_id', (int) $service['finder_id'])->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
	->first();
	$data['finder'] = array_only($item['finder'], array('_id', 'title', 'slug', 'coverimage', 'city_id', 'photos', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect', 'total_rating_count', 'average_rating', 'detail_rating_summary_count', 'detail_rating_summary_average', 'reviews','info'));
}else{
	$data['finder'] = NULL;
}

if(isset($item['trainer_id']) && $item['trainer_id'] != ''){
	$servicetrainer = Servicetrainer::remember(Config::get('app.cachetime'))->find( intval($item['trainer_id']) );
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
	$ratecardsarr    	=   Ratecard::where('service_id', intval($item['_id']) )->with(array('serviceoffers' => function($query) use ($offerid){
		$query->select('*')->whereNotIn('_id', [intval($offerid)]);
	}))->get()->toArray();	
	
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

	if($orderData['status'] == 0){
		//send email & sms
		$sndsSmsCustomer		= 	$this->customersms->buyServiceThroughFitmania($orderData);
		$sndsEmailCustomer		= 	$this->customermailer->buyServiceThroughFitmania($orderData);
		$sndsEmailFinder		= 	$this->findermailer->buyServiceThroughFitmania($orderData);

		$buydealofday 			=	$order->update(['status' => '1', 'customer_email_messageids' => $sndsEmailCustomer, 'customer_sms_messageids' => $sndsSmsCustomer, 'finder_email_messageids' => $sndsEmailFinder]);

		if($buydealofday){
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
	}else{
		return Response::json(array('status' => 200,'message' => "order already scuessfull :)"),200);				
	}

}


public function maintainActiveFlag($serviceid = NULL){

	$date 				=	Carbon::now();
	// $date 				=  	'04-01-2016';
	$timestamp 			= 	strtotime($date);

	if($serviceid != NULL){
		$ratecardoffers 	=	Serviceoffer::whereIn("type" ,["fitmania-dod", "fitmania-dow"])->where("service_id", intval($serviceid))->orderBy('order', 'asc')->get()->groupBy('ratecard_id')->toArray();			
	}else{
		$ratecardoffers 	=	Serviceoffer::whereIn("type" ,["fitmania-dod", "fitmania-dow"])->orderBy('order', 'asc')->get()->groupBy('ratecard_id')->toArray();			
	}

	foreach ($ratecardoffers as $key => $offers) {
   		// return $offers;
		$initial_acitve_flag = 0;
			//for dod
		foreach ($offers as $key => $offer) {
			if($offer->type == "fitmania-dod"){
				$serviceObj =	Serviceoffer::find(intval($offer->_id));
				$limit 		=	intval($serviceObj->limit);
				$sold 		=	intval($serviceObj->sold);

				if($initial_acitve_flag == 1){
					$serviceObj->update(['active' => 0]);
				}else{
					if((strtotime($serviceObj->start_date) <= $timestamp) &&  (strtotime($serviceObj->end_date) > $timestamp) ){
						if(($limit - $sold) > 0){
							$serviceObj->update(['active' => 1]);
							$initial_acitve_flag = 1;
						}
					}
				}					
			}

   			}//foreach

			//for dow
   			foreach ($offers as $key => $offer) {
   				if($offer->type == "fitmania-dow"){
   					$serviceObj =	Serviceoffer::find(intval($offer->_id));
   					$limit 		=	intval($serviceObj->limit);
   					$sold 		=	intval($serviceObj->sold);

   					if($initial_acitve_flag == 1){
   						$serviceObj->update(['active' => 0]);
   					}else{
   						// if((strtotime($serviceObj->start_date) <= $timestamp) &&  (strtotime($serviceObj->end_date) > $timestamp) ){
   						if((strtotime($serviceObj->start_date) <= $timestamp)){
   							if(($limit - $sold) > 0){
   								$serviceObj->update(['active' => 1]);
   								$initial_acitve_flag = 1;
   							}
   						}
   					}					
   				}

   			}//foreach



   		}//foreach

   		// return true;

   		Log::info(' Maintain Active Flag Called -- '. date("d-m-Y h:i:s", time()) );

   		return $ratecardoffers 	=	Serviceoffer::whereIn("type" ,["fitmania-dod"])->orderBy('order', 'asc')->get()->groupBy('ratecard_id')->toArray();
   	}


   	// used to reset buyable value it will hit by sidekick after 12 min check only once base on sidekick_check_status
   	public function checkFitmaniaOrder($order_id){

   		$order 		= 	Order::find(intval($order_id));
   		$orderData 	= 	$order->toArray();

   		if($orderData['status'] == 0 && !isset($orderData['sidekick_check_status'])){

   			$order_obj 		= 	$order->update(['sidekick_check_status' => 1]);

   			$serviceoffer 	= 	Serviceoffer::find(intval($orderData['serviceoffer_id']));
   			if(!$serviceoffer){
   				return Response::json(array('status' => 404,'message' => "serviceoffer does not exist"),404);				
   			}

   			$offer_buyable 		=  	intval($serviceoffer->buyable) + 1;
   			$service_offerdata  = 	['buyable' => intval($offer_buyable)];
   			$success_order 		= 	$serviceoffer->update($service_offerdata);

   			return Response::json(array('status' => 200,'message' => "serviceoffer buyable update scuessfull :)"),200);				
   		}else{
   			return Response::json(array('status' => 200,'message' => "sidekick already checked once :)"),200);				
   		}

   	}


   	public function serachFinders (){

   		$keyword 				=	(Input::json()->get('finder')) ? trim(Input::json()->get('finder')) : "";
   		$city_id				=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;

   		// $serviceoffers_array  	= 	Serviceoffer::where('city_id', $city_id)->whereIn("type" ,  ['fitmania-dod','fitmania-dow', 'fitmania-membership-giveaways'])->get(['finder_id'])->toArray();
   		$serviceoffers_array  	= 	Serviceoffer::where('city_id', $city_id)->where('active', '=', 1)->whereIn("type" ,  ['fitmania-dod','fitmania-dow'])->get(['finder_id'])->toArray();
   		$finderids_array 		= 	array_map('intval', array_pluck($serviceoffers_array, 'finder_id')) ; 

   		$query 					= 	Finder::active()->with(array('location'=>function($query){$query->select('_id','name','slug');}))->orderBy('title')->whereIn('_id', $finderids_array );
   		if ($keyword != '') {
   			$query->where('title', 'LIKE', '%' . $keyword . '%');
   		}

   		$finders = $query->get(['_id','title','slug','location_id','location']);
   		return Response::json(array('status' => 200,'finders' => $finders, 'message' => 'Finder list :)'),200);				
   	}


   	// used to check buyable of particular service offerid
   	public function checkBuyableValue($offerid){

   		$serviceoffer 		= 	Serviceoffer::find(intval($offerid));

   		if(isset($serviceoffer->buyable) && intval($serviceoffer->buyable) < 1){
   			$allservice = Serviceoffer::where('ratecard_id',intval($serviceoffer->ratecard_id))->whereNotIn('_id',[intval($serviceoffer->_id)])->orderBy('order')->get();
   			$nextoffer = [];
   			$nextoffer1 = [];
   			foreach ($allservice as $key => $value) {
   				# code...
   				if($value->type == 'fitmania-dod' && intval($serviceoffer->buyable) > 0){
   					$nextoffer = $value;
   					continue;
   				}
   				if($value->type == 'fitmania-dow' && intval($serviceoffer->buyable) > 0){
   					$nextoffer1 = $value;
   					continue;
   				}
   			}
   			$finaloffer = (!empty($nextoffer)) ? $nextoffer : $nextoffer1;

   			$responsedata 	= ['serviceoffer' => "",'nextoffer'=>$finaloffer, 'exist' => false, 'message' => 'No serviceoffer Exist :)'];
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

	$date 					=  	Carbon::now();
	$timestamp 				= 	strtotime($date);
	$date_hour 				= 	strtolower(date( "d-m-y : h", $timestamp));
	$redis_tag_id 			=   "explore_category_offers_by_city_".$city_id."_".$date_hour;

	$explore_category_offers_by_city = Cache::tags('explore_category_offers_by_city')->has($redis_tag_id);

	if(!$explore_category_offers_by_city){

		$categorys_arr     		=  	array('zumba' => '19', 'yoga & pilates' => '1,4',  'gyms' => '65', 'mma & kickboxing' => '3', 'functional / crossfit' => '5',  'dance' => '2');
		
		$explore_category_offers = [];
		foreach ($categorys_arr as $k => $v) {
			$servicecategory_name  	= 	$k;
			$servicecategory_id  	= 	array_map('intval', explode(',', $v)) ;
			$services				=	Service::active()->whereIn('servicecategory_id', $servicecategory_id)->lists('_id');
			$serviceoffers_cnt  	= 	Serviceoffer::whereIn("type" ,  ['fitmania-dod','fitmania-dow', 'fitmania-membership-giveaways'])->whereIn("service_id" , $services)->count();
			$offer = ['category_name' => $servicecategory_name,'category_id' => $servicecategory_id,'cnt'=>$serviceoffers_cnt];
			array_push($explore_category_offers, $offer);
		}

		Cache::tags('explore_category_offers_by_city')->put($redis_tag_id, $explore_category_offers, Config::get('cache.cache_time'));
	}

	return Cache::tags('explore_category_offers_by_city')->get($redis_tag_id) ;
}



public function exploreLocationClusterOffers($city_id = 1 ){

	$date 					=  	Carbon::now();
	$timestamp 				= 	strtotime($date);
	$date_hour 				= 	strtolower(date( "d-m-y : h", $timestamp));
	$redis_tag_id 			=   "explore_locationcluster_offers_by_city_".$city_id."_".$date_hour;
	
	// $explore_locationcluster_offers_by_city = $cache ? Cache::tags('explore_locationcluster_offers_by_city')->has($redis_tag_id) : false;
	$explore_locationcluster_offers_by_city = Cache::tags('explore_locationcluster_offers_by_city')->has($redis_tag_id);

	if(!$explore_locationcluster_offers_by_city){
		$categorys_arr     		=  	array('gyms' => '65', 'yoga & pilates' => '1,4',  'functional / crossfit' => '5,111',  'dance' => '2');
		$location_clusters 		= 	Locationcluster::where('city_id', '=', intval($city_id))->where('status', '=', '1')->orderBy('name')->get()->toArray();

		$explore_location_cluster_offers = [];

		foreach ($location_clusters as $key => $value) {
			$locations  				=   Location::where('locationcluster_id', '=', intval($value['_id']))->where('status', '=', '1')->get(['_id','name']);
			$locationids_array 			= 	array_map('intval', array_pluck($locations, '_id')) ; 

			$clusteroffers 				= 	[];
			foreach ($categorys_arr as $k => $v) {
				$servicecategory_name  	= 	$k;
				$servicecategory_id  	= 	array_map('intval', explode(',', $v)) ;
				$services				=	Service::active()->whereIn('servicecategory_id', $servicecategory_id)->whereIn('location_id', $locationids_array)->lists('_id');
				$serviceoffers_cnt  	= 	Serviceoffer::whereIn("type" ,  ['fitmania-dod','fitmania-dow', 'fitmania-membership-giveaways'])->whereIn("service_id" , $services)->count();
				$offer = ['category_name' => $servicecategory_name,'category_id' => $servicecategory_id,'cnt'=>$serviceoffers_cnt];
				array_push($clusteroffers, $offer);
			}
			$locationcluster['offers']  = 	$clusteroffers;	
			$locationcluster['name']  	= 	$value['name'];	
			$locationcluster['slug']  	= 	$value['slug'];	
			$locationcluster['locations']  	= 	$locationids_array;	
			$locationcluster['_id']  	= 	intval($value['_id']);	

			array_push($explore_location_cluster_offers, $locationcluster);

		}

		Cache::tags('explore_locationcluster_offers_by_city')->put($redis_tag_id, $explore_location_cluster_offers, Config::get('cache.cache_time'));
	}

	return Cache::tags('explore_locationcluster_offers_by_city')->get($redis_tag_id) ;

}


public function resendEmails(){

	//For Orders
	$match 			=	array('fitmania-dod','fitmania-dow','fitmania-membership-giveaways');
	// $orderscount 	=	Order::orderBy('_id','desc')->whereIn('type',$match)->where('resend_email', 'exists', false)->count();
	$orderscount 	=	Order::whereIn('type',$match)->orderBy('_id','desc')->count();
	$orders 		=	Order::whereIn('type',$match)->whereIn('status','1')->orderBy('_id','desc')->get()->toArray();

	echo "orderscount -- $orderscount "; exit();
	foreach ($orders as $key => $order) {
		//send email to customer and finder
		$order 		= 	Order::find(intval($order['_id']));
		$orderData 	= 	$order->toArray();

		try {
			if(!isset($orderData['resend_customer_send_status']) || $orderData['resend_customer_send_status'] == 0){
				$email_send_data['resend_customer_confirm_email'] = $this->customermailer->buyServiceThroughFitmania($orderData);
				$email_send_data['resend_customer_send_status'] = 1;
			}
		} catch (Exception $e) {
			Log::error($e);
			$message = array( 'type'    => get_class($e), 'message' => $e->getMessage(), 'file'    => $e->getFile(), 'line'    => $e->getLine(), );
			$email_send_data['resend_customer_confirm_email'] = $message;
			$email_send_data['resend_customer_send_status'] = 0;
		}

		try {
			if(!isset($orderData['resend_finder_send_status']) || $orderData['resend_finder_send_status'] == 0){
				$email_send_data['resend_finder_confirm_email'] = $this->findermailer->buyServiceThroughFitmania($orderData);
				$email_send_data['resend_finder_send_status'] = 1;
			}

		} catch (Exception $e) {
			Log::error($e);
			$message = array( 'type'    => get_class($e), 'message' => $e->getMessage(), 'file'  => $e->getFile(), 'line'  => $e->getLine(), );
			$email_send_data['resend_finder_confirm_email'] = $message;
			$email_send_data['resend_finder_send_status'] = 0;

		}
		$email_send_data['resend_email'] = 1;
		$order_obj 		= 	$order->update($email_send_data);
	}

	echo "orderscount -- $orderscount "; exit();

}


public function resendEmailsForWorngCustomer (){

	$corders = [];

	//For Orders
	$match 		=	array('fitmania-dod','fitmania-dow','fitmania-membership-giveaways');
	// $customers 		=	Order::whereIn('type',$match)->where('status','1')->where('customer_email','sanjaysahu@fitternity.com')->get()->groupBy('customer_email');
	$customers 		=	Order::whereIn('type',$match)->where('status','1')->get()->groupBy('customer_email');
	foreach ($customers as $key => $customer) {
		$orders  =  	(!is_array($customer)) ? $customer->toArray() : $customer;
		if(count($orders)>0){
			$customer_email = $orders[0]['customer_email'];
			$customer_name = $orders[0]['customer_name'];
			foreach ($orders as $key => $value) {
				array_push($corders, $value);
			}
			$data['corders'] = $orders;
			$data['customer_email'] = $orders[0]['customer_email'];
			$data['customer_name'] = $orders[0]['customer_name'];
			// return $data;
			// $this->customermailer->resendCustomerGroupBy($customer_email, $customer_name, $data);
		}
	}

	return "email send";
	

}





public function resendEmailsForWorngFinder (){

	return "email send";
	
	$corders = [];

	//For Orders
	$match 			=	array('fitmania-dod','fitmania-dow','fitmania-membership-giveaways');
	// $finders 		=	Order::whereIn('type',$match)->where('status','1')->where('finder_id',7007)->get()->groupBy('finder_id');
	$finders 		=	Order::whereIn('type',$match)->where('payment_status','<>','cancel')->where(function($query){
		$query->orWhere('status','1')->orWhere('abondon_status','bought_closed');
	})->get()->groupBy('finder_id');
	// $finders 		=	Order::whereIn('type',$match)->where('status','1')->whereIn('finder_id', [131,1026,1038,1039,1040,7319,7022])->get()->groupBy('finder_id');
	// $finders 		=	Order::whereIn('type',$match)->where('status','1')->whereIn('finder_id', [131,1026,1038,1039,1040,7319])->get()->groupBy('finder_id');
	foreach ($finders as $key => $customer) {
		$orders  =  	(!is_array($customer)) ? $customer->toArray() : $customer;
		if(count($orders) > 0){
			$finder_vcc_email		=	$orders[0]['finder_vcc_email'];
			$finder_name 			=	$orders[0]['finder_name'];
			$finder_location 		=	$orders[0]['finder_location'];
			$finder_id 				=	$orders[0]['finder_location'];
			foreach ($orders as $key => $value) {
				if(isset($value['coupon_code'])){
					$couponcode = array("uberfit","holafit","ttt","glammfit","haptik","stretchfit","vistafit","pepperfit");
					if(in_array(trim(strtolower($value['coupon_code'])), $couponcode)){
						$value['amount'] = ($value['amount'] * 10)/9;
					}
				}
				array_push($corders, $value);
			}

			if (isset($orders[0]['finder_id']) && $orders[0]['finder_id'] != "") {
				$abandonorders 		=	Order::whereIn('type',$match)->where('payment_status','<>','cancel')->where('status','0')->where('finder_id',intval($orders[0]['finder_id']))->where('abondon_status','bought_closed')->get();
				if(count($abandonorders) > 0){
					foreach ($abandonorders as $key => $abandonorder) {
						if(isset($abandonorder['coupon_code'])){
							$couponcode = array("uberfit","holafit","ttt","glammfit","haptik","stretchfit","vistafit","pepperfit");
							if(in_array(trim(strtolower($abandonorder['coupon_code'])), $couponcode)){
								$abandonorder['amount'] = ($abandonorder['amount'] * 10)/9;
							}
						}
						array_push($corders, $abandonorder);
					}
				}
			}

			$finder 				=	Finder::find(intval($orders[0]['finder_id']));
			$finder_vcc_email		=	($finder->finder_vcc_email) ? $finder->finder_vcc_email : [];

			$data['corders'] 			= $orders;
			$data['finder_id'] 			= $orders[0]['finder_id'];
			$data['finder_name'] 		= $orders[0]['finder_name'];
			$data['finder_location'] 	= $orders[0]['finder_location'];
			$data['finder_vcc_email'] 	= $finder_vcc_email;
			// return $data;
			sleep(1);

			// $$finder_vcc_email = $finder_vcc_email;
			// $queid = $this->findermailer->resendFinderGroupBy($finder_vcc_email, $finder_name, $finder_location, $data);
			echo $queid." - ".$data['finder_id']." - ".$data['finder_name']." - ".$data['finder_location']." - ". var_dump($finder_vcc_email)."<br>" ;
			// return $data['corders'];
			echo "==================================================================================================================== <br><br>";
			// exit();
		}
	}
	// return $corders;


	return "email send";
	

}




public function emailToFitmaniaVendors (){
	
	$corders = [];

	//For Orders
	$match 			=	array('fitmania-dod','fitmania-dow','fitmania-membership-giveaways');
	$finders 		=	Order::whereIn('type',$match)->where('payment_status','<>','cancel')->where(function($query){
		$query->orWhere('status','1')->orWhere('abondon_status','bought_closed');
	})->get()->groupBy('finder_id');

	// return $finders;

	foreach ($finders as $key => $customer) {
		$orders  =  	(!is_array($customer)) ? $customer->toArray() : $customer;
		if(count($orders) > 0){
			$data['finder_id'] 		=	$orders[0]['finder_id'];
			$finder_name 			=	$orders[0]['finder_name'];
			$finder 				=	Finder::find(intval($orders[0]['finder_id']));
			$finder_vcc_email		=	($finder->finder_vcc_email) ? $finder->finder_vcc_email : [];
			$peppertapobj 			= 	Peppertap::where('status','=', 0)->take(1)->get(['code','_id'])->toArray();
			$peppertap_ids 			=  	array_map('intval', array_pluck($peppertapobj, '_id'));
			$data['codes'] 			=	$peppertapobj;

			// $update = Peppertap::whereIn('_id', $peppertap_ids)->update(['status' => 1]);
			$queid = "";
			$queid = $this->findermailer->emailToFitmaniaVendors($finder_vcc_email, $finder_name, $data);
			echo $queid." - ".$data['finder_id']. var_dump($finder_vcc_email)." <br><pre> ".print_r($peppertap_ids)." - </pre><pre> ".print_r($peppertapobj)."<br>" ;
			// return $data['corders'];
			echo "==================================================================================================================== <br><br>";
			// exit();
		}
	}
	// return $corders;


	return "email send";
	

}





}
