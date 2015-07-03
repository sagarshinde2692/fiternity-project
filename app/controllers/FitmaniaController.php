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
		['_id' => 1,
		'name' => ' service1 ', 
		'description' => '20-15 Fitness Tardeo - Cross & Functional Training Classes Membership Fees, Rates, Reviews, Contact - Mumbai', 
		'ordering' => 1, 
		'finder_id' => 1
		],
		['_id' => 1,
		'name' => ' service2 ', 
		'description' => '212 Jaywant industrial estate, 63 Tardeo road ,Mumbai - 34 Landmark- Diagonally opp. Sobo Central Mall near Haji Ali', 
		'ordering' => 2, 
		'finder_id' => 1
		],
		['_id' => 1,
		'name' => ' service3 ', 
		'description' => 'Essar Fitness Andheri East,Mumbai - View address, contact number, membership fees, pictures, reviews & offers. Book free trial & buy membership  for Essar Fitness Andheri East', 
		'ordering' => 3, 
		'finder_id' => 1
		],
		['_id' => 1,'name' => ' service4 ', 
		'description' => 'Neha Chandna Khar West,Mumbai - Diet for Weight Loss, Nutrition, Sports Nutrition, hypnotherapy. View contact number, fees, qualification, s & reviews on Fitternity.com', 'ordering' => 4, 'finder_id' => 1]
		
		];

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
