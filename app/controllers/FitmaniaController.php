<?PHP

/** 
 * ControllerName : FitmaniaController.
 * Maintains a list of functions used for FitmaniaController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class FitmaniaController extends \BaseController {

	public function __construct() {
		parent::__construct();	
	}


	public function getMockData($date = null){

		$date 					=  	($date == null) ? Carbon::now() : $date;
		$timestamp 				= 	strtotime($date);
		$dealsofdays 			=	[];

		// $dealsofdaycolleciton 	=	Fitmaniadod::active()->where('offer_date', '=', new DateTime($date) )->orderBy('ordering','desc')->get()->toArray();
		$dealsofdaycolleciton 	=	Fitmaniadod::active()
												->where('offer_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
												->where('offer_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ))
												->orderBy('ordering','desc')->get()->toArray();


		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transform($value);
			array_push($dealsofdays, $dealdata);
		}
		$responseData = [ 'dealsofday' => $dealsofdays ];
		return Response::json($responseData, 200);
	}



	private function transform($deal){

		$item  	   	=  	(!is_array($deal)) ? $deal->toArray() : $deal;
		$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
								->with(array('location'=>function($query){$query->select('_id','name','slug');}))
								->where('_id', (int) $item['finder_id'])->first();
		// return $finderarr;

		$data = array(
			'_id' => $item['_id'],
			'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
			'price' => (isset($item['price']) && $item['price'] != '') ? strtolower($item['price']) : "",
			'special_price' => (isset($item['special_price']) && $item['special_price'] != '') ? strtolower($item['special_price']) : "",
			'finder_id' => (isset($item['finder_id']) && $item['finder_id'] != '') ? strtolower($item['finder_id']) : "",
			'offer_pic' => (isset($item['offer_pic']) && $item['offer_pic'] != '') ? strtolower($item['offer_pic']) : "",
			'description' => (isset($item['description']) && $item['description'] != '') ? $item['description'] : "",
			'ordering' => (isset($item['ordering']) && $item['ordering'] != '') ? (int)$item['ordering'] : "",
			'offer_date' => (isset($item['offer_date']) && $item['offer_date'] != '') ? strtolower($item['offer_date']) : "",
			'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
			'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type')),
			// 'city' => (isset($finderarr->city->name) && $finderarr->city->name != '') ? strtolower($finderarr->city->name) : "",
			// 'location' => (isset($finderarr->location->name) && $finderarr->location->name != '') ? strtolower($finderarr->location->name) : ""
			);
		return $data;

	}


	public function buyDealOfDay(){

		return "buyDealOfDay";
	}


	public function fitmaniaServices(){

		$category 					=	(Input::json()->get('category')) ? strtolower(Input::json()->get('category')) : '';		
		$subcategory 				=	(Input::json()->get('subcategory')) ? strtolower(Input::json()->get('subcategory')) : '';		
		$location 					=	(Input::json()->get('location')) ? strtolower(Input::json()->get('location')) : '';	
		// $workout_intensity 			=	(Input::json()->get('workout_intensity')) ? strtolower(Input::json()->get('workout_intensity')) : '';			
		// $workout_tags 				=	(Input::json()->get('workout_tags')) ? strtolower(Input::json()->get('workout_tags')) : '';	
		// $min_time 					=	(Input::json()->get('min_time')) ? trim(strtolower(Input::json()->get('min_time'))) : intval(date("H")) + 1;		
		// $max_time 					=	(Input::json()->get('max_time')) ? trim(strtolower(Input::json()->get('max_time'))) : 24;		
		// $min_price 					=	(Input::json()->get('min_price')) ? trim(strtolower(Input::json()->get('min_price'))) : '';		
		// $max_price 					=	(Input::json()->get('max_price')) ? trim(strtolower(Input::json()->get('max_price'))) : '';	

		$services = Service::with('category')->with('subcategory')->with('finder')->where('_id', (int) $serviceid)->get();
		// return $service;
		if(!$service){
			$resp 	= 	array('status' => 400, 'service' => [], 'message' => 'No Service Exist :)');
			return Response::json($resp, 400);
		}
		$servicedata = $this->transform($service);
		$resp 	= 	array('status' => 200, 'service' => $servicedata, 'message' => 'Particular Service Info');
		return Response::json($resp, 200);

		return 'finders';
	}






	

}
