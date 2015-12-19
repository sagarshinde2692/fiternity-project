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


	public function homeData($city = 'mumbai', $from = '', $size = ''){

		$responsedata = [];
		$responsedata['dod'] = $this->getDealOfDay($city , $from, $size);
		$responsedata['membership'] = $this->getDealOfDay($city , $from, $size);
		$responsedata['message'] = "Fitmania Home Page Data :)";

		return Response::json($responsedata, 200);
	}

	public function getFitmaniaHomepageBanners($city = 'mumbai', $type = '',  $from = '', $size = ''){

		$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
		if(!$citydata){
			return $this->responseNotFound('City does not exist');
		}

		$city_name 		= 	$citydata['name'];
		$city_id		= 	(int) $citydata['_id'];	
		$from 			=	($from != '') ? intval($from) : 0;
		$size 			=	($size != '') ? intval($size) : 10;

		$banners 		= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->take($size)->skip($from)->orderBy('ordering')->get();			
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
		$city_name 		= 	$citydata['name'];
		$city_id		= 	(int) $citydata['_id'];	
		$from 			=	($from != '') ? intval($from) : 0;
		$size 			=	($size != '') ? intval($size) : 10;
		$date 			=  	Carbon::now();
		$timestamp 		= 	strtotime($date);
		// $stringdate 	= 	$date->format('l jS \\of F Y h:i:s A');
		$stringdate 	= 	$date->toFormattedDateString();
		$categoryday   	=   'zumba';

		$fitmaniahomepageobj 		=	Fitmaniahomepage::where('city_id', '=', $city_id)->first();
		if(count($fitmaniahomepageobj) < 1){
			$responsedata 	= ['services' => [],  'message' => 'No Fitmania DOD Exist :)'];
			return Response::json($responsedata, 200);
		}

		$ratecardids 			=   array_map('intval', explode(',', $fitmaniahomepageobj->ratecardids));
		$fitmaniadods			=	[];

		$dealsofdaycolleciton 	=	Serviceoffer::with('finder')->with('ratecard')->where('city_id', '=', $city_id)
												// ->where('start_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
												// ->where('end_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ))
												->where("type" , "=" , "fitmania-dod")
												->take($size)->skip($from)->orderBy('order', 'desc')->get()->toArray();

		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transformDod($value);
			array_push($fitmaniadods, $dealdata);
		}

		$banners 		= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->take($size)->skip($from)->orderBy('ordering')->get();					
		$responsedata 	= 	['stringdate' => $stringdate, 'categoryday' => $categoryday,  'fitmaniadods' => $fitmaniadods,  'banners' => $banners, 'message' => 'Fitmania Home Page Dods :)'];
		return Response::json($responsedata, 200);

	}

	private function transformDod($offers){

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
		'service' =>  array_only($servicearr->toArray(), array('name','_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage', 'category',  'subcategory', 'location' )),

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
		// $stringdate 	= 	$date->format('l jS \\of F Y h:i:s A');
		$stringdate 	= 	$date->toFormattedDateString();
		$categoryday   	=   'zumba';


		$fitmaniahomepageobj 		=	Fitmaniahomepage::where('city_id', '=', $city_id)->first();
		if(count($fitmaniahomepageobj) < 1){
			$responsedata 	= ['services' => [],  'message' => 'No Membership Giveaway Exist :)'];
			return Response::json($responsedata, 200);
		}

		$ratecardids 			=   array_map('intval', explode(',', $fitmaniahomepageobj->ratecardids));
		$fitmaniamemberships 	=	[];

		$offers  		= 	Serviceoffer::with('finder')->with('ratecard')->where('city_id', '=', $city_id)
										->where("type" , "=" , "fitmania-membership-giveaways")
										->whereIn('ratecard_id', $ratecardids)->get();
		foreach ($offers as $key => $value) {
			$membershipdata = $this->transformMembership($value);
			array_push($fitmaniamemberships, $membershipdata);
		}

		$banners 		= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->take($size)->skip($from)->orderBy('ordering')->get();			
		$responsedata 	=  ['stringdate' => $stringdate, 'categoryday' => $categoryday,'fitmaniamemberships' => $fitmaniamemberships,  'banners' => $banners, 'message' => 'Fitmania Home Page Memberships :)'];
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
		'service' =>  array_only($servicearr->toArray(), array('name','_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage', 'category',  'subcategory', 'location' )),
		];

		return $data;
	}



	public function getDealOfWeek($city = 'mumbai', $from = '', $size = ''){

		return "welcome to fitmania dow";
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

		$fitmaniamemberships 		=	[];

		$serviceoffers  			= 	Serviceoffer::where('city_id', '=', $city_id)->where("type" , "=" , "fitmania-membership-giveaways")->get()->toArray();
		$serviceids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'service_id')) ; 
		$ratecardids_array 			= 	array_map('intval', array_pluck($serviceoffers, 'ratecard_id')) ; 

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
			$service_ratedcards    	=   Ratecard::with('serviceoffers')->whereIn('_id', $ratecardids_array )->where('service_id', intval($item['_id']) )->get()->toArray();					
			$finderarr 				= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');}))
											->with(array('location'=>function($query){$query->select('_id','name','slug');}))
											->with(array('category'=>function($query){$query->select('_id','name','slug');}))
											->where('_id', (int) $item['finder_id'])->first();
			$data = [
			'_id' => $item['_id'],
			'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
			'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
			'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
			'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
			'workout_tags' => (isset($item['workout_tags']) && $item['workout_tags'] != '') ? $item['workout_tags'] : [],
			'service_ratedcards' => (isset($service_ratedcards) && !empty($service_ratedcards)) ? $service_ratedcards : [],
			'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type','commercial_type','coverimage','info','category','location','contact','finder_poc_for_customer_name','finder_poc_for_customer_mobile','finder_vcc_email')),
			];

			// return $data;
			array_push($fitmaniamemberships, $data);
		}


		$date 			=  	Carbon::now();
		$stringdate 	= 	$date->toFormattedDateString();
		$categoryday   	=   'zumba';

		$leftside 					= 	[];
		$leftside['category'] 	 	= 	Servicecategory::active()->where('parent_id', 0)->orderBy('ordering')->get(array('_id','name','slug','status'));
		$leftside['subcategory'] 	= 	Servicecategory::active()->where('parent_id', '!=', 0)->orderBy('ordering')->get(array('_id','name','slug','status'));
		$leftside['locations'] 		= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug'));

		$responsedata 				=  ['stringdate' => $stringdate, 'categoryday' => $categoryday, 'leftside' => $leftside, 'fitmaniamemberships' => $fitmaniamemberships, 'message' => 'Fitmania Memberships :)'];
		return Response::json($responsedata, 200);
	}



	public function serachDodAndDow(){

		// return Input::json()->all();
		$from 						=	(Input::json()->get('from')) ? intval(Input::json()->get('from')) : 0;
		$size 						=	(Input::json()->get('size')) ? intval(Input::json()->get('size')) : 10;
		$city 						=	(Input::json()->get('city')) ? strtolower(Input::json()->get('city')) : 'mumbai';
		$city_id					=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;
		$category 					=	(Input::json()->get('category')) ? array_map('intval', Input::json()->get('category')) : [];		
		$subcategory 				=	(Input::json()->get('subcategory')) ? array_map('intval', Input::json()->get('subcategory')) : [];		
		$location 					=	(Input::json()->get('location')) ? array_map('intval', Input::json()->get('location')) : [];	
		$finder 					=	(Input::json()->get('finder')) ? array_map('intval', Input::json()->get('finder')) : [];	

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

		$dealsofdaycolleciton 	=	Serviceoffer::with('finder')->with('ratecard')->where('city_id', '=', $city_id)
												->where('start_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
												->where('end_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ))
												// ->orWhere('end_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ))
												->Where("active" , "=" , "1")
												->where("type" , "=" , "fitmania-dod")
												->orWhere("type" , "=" , "fitmania-dow")
												->whereIn('service_id', $serviceids_array)
												->take($size)->skip($from)->orderBy('order', 'desc')->get()->toArray();

		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transformDod($value);
			array_push($fitmaniadods, $dealdata);
		}

		$date 			=  	Carbon::now();
		$stringdate 	= 	$date->toFormattedDateString();
		$categoryday   	=   'zumba';

		$leftside 					= 	[];
		$leftside['category'] 	 	= 	Servicecategory::active()->where('parent_id', 0)->orderBy('ordering')->get(array('_id','name','slug','status'));
		$leftside['subcategory'] 	= 	Servicecategory::active()->where('parent_id', '!=', 0)->orderBy('ordering')->get(array('_id','name','slug','status'));
		$leftside['locations'] 		= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug'));

		$responsedata 	=  ['stringdate' => $stringdate, 'categoryday' => $categoryday, 'leftside' => $leftside, 'fitmaniadods' => $fitmaniadods, 'message' => 'Fitmania dod and dow :)'];
		return Response::json($responsedata, 200);


	}




}
