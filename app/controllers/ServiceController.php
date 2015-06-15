<?PHP

/** 
 * ControllerName : ServiceController.
 * Maintains a list of functions used for ServiceController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class ServiceController extends \BaseController {


	public function __construct() {

		parent::__construct();	
	}

	

	/**
	 * Update slugs for all services.
	 *
	 */
	public function updateSlug(){
		// echo "ssssss";exit;

		$items = Service::active()->get();

		$servicedata = array();

		foreach ($items as $item) {  

			$servicedata = $item->toArray();

			echo $servicedata['_id']."<br>";

			if(isset($servicedata['slug']) && $servicedata['slug'] != '') {

				$servicecnt = Service::where('slug', url_slug(array($servicedata['name'])))->whereNotIn( '_id', array( intval($servicedata['_id']) ))->count();

				if ($servicecnt > 0) {

					array_set($servicedata, 'name', $servicedata['name']." ".$servicedata['workout_intensity'] );

					array_set($servicedata, 'slug', url_slug(array($servicedata['name'], $servicedata['workout_intensity'])));

				}else{

					array_set($servicedata, 'name', $servicedata['name']);

					array_set($servicedata, 'slug', url_slug(array($servicedata['name'])) );

				}

			}else{

				array_set($servicedata, 'name', $servicedata['name']);

				array_set($servicedata, 'slug', url_slug(array($servicedata['name'])) );
				
			}

			$service = Service::findOrFail($servicedata['_id']);

			$response = $service->update($servicedata);


		}

		// return Response::json('asfs');


	}

	/**
	 * Return the specified service.
	 *
	 * @param  int  	$serviceid
	 * @param  string  	$slug
	 * @return Response
	 */

	public function serviceDetail($serviceid){

		$service = Service::with('category')->with('subcategory')->with('finder')->where('_id', (int) $serviceid)->first();

		// return $service;

		if(!$service){

			$resp 	= 	array('status' => 400, 'service' => [], 'message' => 'No Service Exist :)');
			
			return Response::json($resp, 400);
		}

		$servicedata = $this->transform($service);

		$resp 	= 	array('status' => 200, 'service' => $servicedata, 'message' => 'Particular Service Info');
		
		return Response::json($resp, 200);
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
			'finder' =>  array_only($item['finder'], array('_id', 'title', 'slug', 'coverimage')),
			'city' => (isset($finderarr->city->name) && $finderarr->city->name != '') ? strtolower($finderarr->city->name) : "",
			'location' => (isset($finderarr->location->name) && $finderarr->location->name != '') ? strtolower($finderarr->location->name) : "",
			'active_weekdays' => (isset($item['active_weekdays']) && $item['active_weekdays'] != '') ? array_map('strtolower',$item['active_weekdays']) : "",
			'workoutsession_active_weekdays' => (isset($item['workoutsession_active_weekdays']) && $item['workoutsession_active_weekdays'] != '') ? array_map('strtolower',$item['workoutsession_active_weekdays']) : ""

			// 'workoutsessionschedules' => (isset($item['workoutsessionschedules']) && !empty($item['workoutsessionschedules'])) ? $item['workoutsessionschedules'] : "",
			// 'trialschedules' => (isset($item['trialschedules']) && !empty($item['trialschedules'])) ? $item['trialschedules'] : "",
		);

		// return $data;

		if(isset($item['trainer_id']) && $item['trainer_id'] != ''){

			$servicetrainer = Servicetrainer::remember(Config::get('app.cachetime'))->findOrFail( intval($item['trainer_id']) );

			if($servicetrainer){

				$trainerdata = $servicetrainer->toArray();

				$data['trainer'] = array_only($trainerdata, array('_id', 'name', 'bio', 'trainer_pic'));
			}

			// return $data;
			
		}else{

			$data['trainer'] = NULL;
			
		}

		return $data;

	}




}
