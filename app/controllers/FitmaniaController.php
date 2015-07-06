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

}
