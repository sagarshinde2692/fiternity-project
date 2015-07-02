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


	public function getMockData(){

		$dealsofdays = [];
		$dealsofdaycolleciton = [
		['_id' => 1,'name' => 'offer service1', 'description' => 'description1', 'ordering' => 1, 'finder_id' => 1],
		['_id' => 1,'name' => 'offer service2', 'description' => 'description2', 'ordering' => 2, 'finder_id' => 1],
		['_id' => 1,'name' => 'offer service3', 'description' => 'description3', 'ordering' => 3, 'finder_id' => 1],
		['_id' => 1,'name' => 'offer service4', 'description' => 'description4', 'ordering' => 4, 'finder_id' => 1],
		['_id' => 1,'name' => 'offer service5', 'description' => 'description5', 'ordering' => 5, 'finder_id' => 1]
		];

		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transform($value);
			array_push($dealsofdays, $dealdata);
		}

		return Response::json($dealsofdays, 200);
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
			'description' => (isset($item['description']) && $item['description'] != '') ? $item['description'] : "",
			'ordering' => (isset($item['ordering']) && $item['ordering'] != '') ? (int)$item['ordering'] : "",
			'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
			'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'coverimage', 'city_id', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect')),
			// 'city' => (isset($finderarr->city->name) && $finderarr->city->name != '') ? strtolower($finderarr->city->name) : "",
			'location' => (isset($finderarr->location->name) && $finderarr->location->name != '') ? strtolower($finderarr->location->name) : ""
			);
		return $data;

	}

}
