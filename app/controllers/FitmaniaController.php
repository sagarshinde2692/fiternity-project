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
		$date 					=  	Carbon::now();
		$timestamp 				= 	strtotime($date);

		$fitmaniahomepageobj 		=	Fitmaniahomepage::where('city_id', '=', $city_id)->first();
		if(count($fitmaniahomepageobj) < 1){
			$responsedata 	= ['services' => [],  'message' => 'No Fitmania DOD Exist :)'];
			return Response::json($responsedata, 200);
		}

		$ratecardids 			=   array_map('intval', explode(',', $fitmaniahomepageobj->ratecardids));
		$fitmaniadods			=	[];

		$dealsofdaycolleciton 	=	Serviceoffer::with('finder')->with('service')->where('city_id', '=', $city_id)
		->where('start_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
		->where('end_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ))
		->where("type" , "=" , "fitmania-dod")
		->take($size)->skip($from)->orderBy('order', 'desc')->get()->toArray();

		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transformDod($value);
			array_push($fitmaniadods, $dealdata);
		}

		$banners 		= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->take($size)->skip($from)->orderBy('ordering')->get();			
		
		$responsedata 	= ['fitmaniadods' => $fitmaniadods,  'banners' => $banners, 'message' => 'Fitmania Home Page Dods :)'];
		return Response::json($responsedata, 200);

	}

	private function transformDod($offers){

		$item  	   		=  	(!is_array($offers)) ? $offers->toArray() : $offers;
		$servicearr  	=  	(!is_array($item['service'])) ?  (array) $item['service'] : $item['service'];
		$finderarr   	=  	(!is_array($item['finder'])) ?  (array) $item['finder'] : $item['finder'];

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
		'service' => (isset($item['service']) && $item['service'] != '') ? array_only( $servicearr , ['name','_id','workout_tags', 'service_coverimage', 'category',  'subcategory', 'location'] )  : "",
		'finder' => (isset($item['finder']) && $item['finder'] != '') ? array_only( $finderarr , ['_id','title','slug','finder_coverimage','coverimage','average_rating', 'contact'] )  : "",		
		];

		return $data;
	}


	public function getDealOfWeek($city = 'mumbai', $from = '', $size = ''){

		return "welcome to fitmania dow";
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


		$fitmaniahomepageobj 		=	Fitmaniahomepage::where('city_id', '=', $city_id)->first();
		if(count($fitmaniahomepageobj) < 1){
				$responsedata 	= ['services' => [],  'message' => 'No Membership Giveaway Exist :)'];
		return Response::json($responsedata, 200);
		}

		$ratecardids 			=   array_map('intval', explode(',', $fitmaniahomepageobj->ratecardids));
		$fitmaniamemberships 	=	[];

		$offers  		= 	Serviceoffer::with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage','average_rating', 'contact');}))
		->with(array('service'=>function($query){$query->select('name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage', 'category',  'subcategory', 'location', 'finder','trialschedules');}))
		->where("type" , "=" , "fitmania-membership-giveaways")
		->whereIn('ratecard_id', $ratecardids)->get();
		foreach ($offers as $key => $value) {
			$membershipdata = $this->transformMembership($value);
			array_push($fitmaniamemberships, $membershipdata);
		}

		$banners 		= 	Fitmaniahomepagebanner::where('city_id', '=', $city_id)->take($size)->skip($from)->orderBy('ordering')->get();			
		
		$responsedata 	= ['fitmaniamemberships' => $fitmaniamemberships,  'banners' => $banners, 'message' => 'Fitmania Home Page Memberships :)'];
		return Response::json($responsedata, 200);
	}


	private function transformMembership($offers){

		$item  	   		=  	(!is_array($offers)) ? $offers->toArray() : $offers;
		$servicearr  	=  	(!is_array($item['service'])) ?  (array) $item['service'] : $item['service'];
		$finderarr   	=  	(!is_array($item['finder'])) ?  (array) $item['finder'] : $item['finder'];

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
		'service' => (isset($item['service']) && $item['service'] != '') ? array_only( $servicearr , ['name','_id','workout_tags', 'service_coverimage', 'category',  'subcategory', 'location'] )  : "",
		'finder' => (isset($item['finder']) && $item['finder'] != '') ? array_only( $finderarr , ['_id','title','slug','finder_coverimage','coverimage','average_rating', 'contact'] )  : "",		
		];

		return $data;
	}





}
