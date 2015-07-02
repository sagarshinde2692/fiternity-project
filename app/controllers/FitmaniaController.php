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

		$dealsofdaycolleciton = [

		];

		$data = array('relatedfinders' 	=> $relatedfinders,
			'categorytags' 	=> $categorytags,
			'locations' 		=> $locations
			);
		return $data;

	}



	private function transform($service){

		$item  	   	=  	(!is_array($service)) ? $service->toArray() : $service;
		$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
							->with(array('location'=>function($query){$query->select('_id','name','slug');}))
							->where('_id', (int) $service['finder_id'])
							->first();
		// return $finderarr;

		$data = array(
			'_id' => $item['_id'],
			'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
			'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
			'lat' => (isset($item['lat']) && $item['lat'] != '') ? strtolower($item['lat']) : "",
			'lon' => (isset($item['lon']) && $item['lon'] != '') ? strtolower($item['lon']) : "",
			'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
			'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
			'workout_tags' => (isset($item['workout_tags']) && !empty($item['workout_tags'])) ? array_map('strtolower',$item['workout_tags']) : "",
			'short_description' => (isset($item['short_description']) && $item['short_description'] != '') ? $item['short_description'] : "", 
			'what_i_should_carry' => (isset($item['what_i_should_carry']) && $item['what_i_should_carry'] != '') ? $item['what_i_should_carry'] : "", 
			'what_i_should_expect' => (isset($item['what_i_should_expect']) && $item['what_i_should_expect'] != '') ? $item['what_i_should_expect'] : "", 
			'ratecards' =>  (isset($item['ratecards']) && !empty($item['ratecards'])) ? $item['ratecards'] : "",
			'category' =>  array_only($item['category'], array('_id', 'name', 'slug', 'parent_name')) ,
			'subcategory' =>  array_only($item['subcategory'], array('_id', 'name', 'slug', 'parent_name')) ,
			'finder' =>  array_only($item['finder'], array('_id', 'title', 'slug', 'coverimage', 'city_id', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect')),
			'city' => (isset($finderarr->city->name) && $finderarr->city->name != '') ? strtolower($finderarr->city->name) : "",
			'location' => (isset($finderarr->location->name) && $finderarr->location->name != '') ? strtolower($finderarr->location->name) : "",
			'active_weekdays' => (isset($item['active_weekdays']) && $item['active_weekdays'] != '') ? array_map('strtolower',$item['active_weekdays']) : "",
			'workoutsession_active_weekdays' => (isset($item['workoutsession_active_weekdays']) && $item['workoutsession_active_weekdays'] != '') ? array_map('strtolower',$item['workoutsession_active_weekdays']) : ""

			// 'workoutsessionschedules' => (isset($item['workoutsessionschedules']) && !empty($item['workoutsessionschedules'])) ? $item['workoutsessionschedules'] : "",
			// 'trialschedules' => (isset($item['trialschedules']) && !empty($item['trialschedules'])) ? $item['trialschedules'] : "",
		);

		// return $data;

		

		return $data;

	}
	
}
