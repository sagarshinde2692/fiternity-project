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

	public function getServiceCategorys(){

		$servicecategory	 = 	Servicecategory::active()->where('parent_id', 0)->orderBy('name')->get(array('name','slug'));	
		$resp 	= 	array('status' => 200, 'servicecategory' => $servicecategory, 'message' => 'Servicecategory List');
		return Response::json($resp, 200);

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

		$service = Service::with('category')->with('subcategory')->with('location')->with('city')->with('finder')->where('_id', (int) $serviceid)->first();
		// return $service;
		if(!$service){
			$resp 	= 	array('status' => 400, 'service' => [], 'message' => 'No Service Exist :)');
			return Response::json($resp, 400);
		}

		$servicedata = $this->transform($service);
		// dd($servicedata);
		// $servicedata = '';
		$servicecategoryid 	= intval($servicedata['servicecategory_id']);
		$servicelocationid 	= intval($servicedata['location_id']);
		$servicefinderid 	= intval($servicedata['finder_id']);
		$same_vendor_service = $nearby_same_category = $nearby_other_category = [];

		//same service form same location and same category
		$same_vendor_service		=		Service::active()
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->where('finder_id', '=', $servicefinderid)
												->where('_id', '!=', intval($serviceid))
												->remember(Config::get('app.cachetime'))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags'])
												->take(5)->toArray();	

		//same service form same location and same category
		$nearby_same_category 		=		Service::active()
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->where('servicecategory_id', '=', $servicecategoryid)
												->where('location_id', '=' ,$servicelocationid)
												->where('_id', '!=', intval($serviceid))
												->remember(Config::get('app.cachetime'))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags'])
												->take(5)->toArray();																								

		//different service form same location and same category
		$nearby_other_category 		=		Service::active()
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->where('servicecategory_id', '!=', $servicecategoryid)
												->where('location_id','=',$servicelocationid)
												->where('_id', '!=', intval($serviceid))
												->remember(Config::get('app.cachetime'))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags'])
												->take(5)->toArray();												

		$resp 	= 	array('status' => 200, 'service' => $servicedata, 'same_vendor_service' => $same_vendor_service, 'nearby_same_category' => $nearby_same_category, 'nearby_other_category' => $nearby_other_category, 'message' => 'Particular Service Info');
		return Response::json($resp, 200);
	}



	private function transform($service){

		$item  	   	=  	(!is_array($service)) ? $service->toArray() : $service;
	
		$data = array(
			'_id' => $item['_id'],
			'servicecategory_id' => $item['servicecategory_id'],
			'location_id' => $item['location_id'],
			'finder_id' => $item['finder_id'],
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
			'location' =>  array_only($item['location'], array('_id', 'name', 'slug')) ,
			'city' =>  array_only($item['city'], array('_id', 'name', 'slug')) ,
			'active_weekdays' => (isset($item['active_weekdays']) && $item['active_weekdays'] != '') ? array_map('strtolower',$item['active_weekdays']) : "",
			'workoutsession_active_weekdays' => (isset($item['workoutsession_active_weekdays']) && $item['workoutsession_active_weekdays'] != '') ? array_map('strtolower',$item['workoutsession_active_weekdays']) : ""

			// 'workoutsessionschedules' => (isset($item['workoutsessionschedules']) && !empty($item['workoutsessionschedules'])) ? $item['workoutsessionschedules'] : "",
			// 'trialschedules' => (isset($item['trialschedules']) && !empty($item['trialschedules'])) ? $item['trialschedules'] : "",
		);
		
		// return $data;
						
		if(isset($item['finder']) && $item['finder'] != ''){
			$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
							->with(array('location'=>function($query){$query->select('_id','name','slug');}))
							->where('_id', (int) $service['finder_id'])
							->first();
			// return $finderarr;
			$data['finder'] = array_only($item['finder'], array('_id', 'title', 'slug', 'coverimage', 'city_id', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect'));
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




	public function getServiceHomePageDataV1($city = 'mumbai',$cache = false){   

		$home_by_city = $cache ? Cache::tags('servicehome_by_city_v1')->has($city) : false;

		if(!$home_by_city){
			$categorys = $locations = $feature_services = $footer_services = [];

			$citydata 		=		City::where('slug', '=', $city)->first(array('name','slug'));
			$city_name 		= 		$citydata['name'];
			$city_id		= 		(int) $citydata['_id'];	
			$homepage 		= 		Servicehomepage::where('city_id', '=', $city_id)->get()->first();						

			$feature_services  	= 	$this->feature_services($homepage);
			$footer_services  	= 	$this->footer_services($homepage);
			$locations			= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));
			$categorys	 		= 	Servicecategory::active()->where('parent_id', 0)->orderBy('name')->get(array('name','slug'));	

			$homedata 			= 	['city_name' => $city_name, 'city_id' => $city_id, 'categorys' => $categorys, 'locations' => $locations, 'feature_services' => $feature_services, 'footer_services' => $footer_services];

			Cache::tags('servicehome_by_city_v1')->put($city, $homedata, Config::get('cache.cache_time'));
		}

		return Response::json(Cache::tags('servicehome_by_city_v1')->get($city));
	}



	private function feature_services($homepage){

		$feature_block1_ids 		= 		array_map('intval', explode(",", $homepage['feature_ids_block1'] ));
		$feature_block2_ids 		= 		array_map('intval', explode(",", $homepage['feature_ids_block2'] ));
		$feature_block3_ids 		= 		array_map('intval', explode(",", $homepage['feature_ids_block3'] ));
		$feature_block4_ids 		= 		array_map('intval', explode(",", $homepage['feature_ids_block4'] ));
		$feature_block5_ids 		= 		array_map('intval', explode(",", $homepage['feature_ids_block5'] ));
		$feature_block6_ids 		= 		array_map('intval', explode(",", $homepage['feature_ids_block6'] ));

		$feature_block1_services 		=		Service::active()
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->whereIn('_id', $feature_block1_ids)
												->get(['name','_id','finder_id','servicecategory_id','servicesubcategory_id','workout_tags']);

		$feature_block2_services 		=		Service::active()
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->whereIn('_id', $feature_block2_ids)
												->get(['name','_id','finder_id','servicecategory_id','servicesubcategory_id','workout_tags']);

		$feature_block3_services 		=		Service::active()
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->whereIn('_id', $feature_block3_ids)
												->get(['name','_id','finder_id','servicecategory_id','servicesubcategory_id','workout_tags']);

		$feature_block4_services 		=		Service::active()
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->whereIn('_id', $feature_block4_ids)
												->get(['name','_id','finder_id','servicecategory_id','servicesubcategory_id','workout_tags']);																										

		$feature_block5_services 		=		Service::active()
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->whereIn('_id', $feature_block5_ids)
												->get(['name','_id','finder_id','servicecategory_id','servicesubcategory_id','workout_tags']);																										

		$feature_block6_services 		=		Service::active()
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->whereIn('_id', $feature_block6_ids)
												->get(['name','_id','finder_id','servicecategory_id','servicesubcategory_id','workout_tags']);																										


		array_set($feature_services,  'feature_block1_services', $feature_block1_services);									
		array_set($feature_services,  'feature_block2_services', $feature_block2_services);									
		array_set($feature_services,  'feature_block3_services', $feature_block3_services);									
		array_set($feature_services,  'feature_block4_services', $feature_block4_services);	
		array_set($feature_services,  'feature_block5_services', $feature_block5_services);	
		array_set($feature_services,  'feature_block6_services', $feature_block6_services);	

		array_set($feature_services,  'footer_block1_title', (isset($homepage['footer_block1_title']) && $homepage['footer_block1_title'] != '') ? $homepage['footer_block1_title'] : '');									
		array_set($feature_services,  'footer_block2_title', (isset($homepage['footer_block2_title']) && $homepage['footer_block2_title'] != '') ? $homepage['footer_block2_title'] : '');									
		array_set($feature_services,  'footer_block3_title', (isset($homepage['footer_block3_title']) && $homepage['footer_block3_title'] != '') ? $homepage['footer_block3_title'] : '');									
		array_set($feature_services,  'footer_block4_title', (isset($homepage['footer_block4_title']) && $homepage['footer_block4_title'] != '') ? $homepage['footer_block4_title'] : '');									
		array_set($feature_services,  'footer_block5_title', (isset($homepage['footer_block5_title']) && $homepage['footer_block5_title'] != '') ? $homepage['footer_block5_title'] : '');									
		array_set($feature_services,  'footer_block6_title', (isset($homepage['footer_block6_title']) && $homepage['footer_block6_title'] != '') ? $homepage['footer_block6_title'] : '');

		return $feature_services;
	}



	private function footer_services($homepage){

		$footer_block1_ids 		= 		array_map('intval', explode(",", $homepage['footer_block1_ids'] ));
		$footer_block2_ids 		= 		array_map('intval', explode(",", $homepage['footer_block2_ids'] ));
		$footer_block3_ids 		= 		array_map('intval', explode(",", $homepage['footer_block3_ids'] ));
		$footer_block4_ids 		= 		array_map('intval', explode(",", $homepage['footer_block4_ids'] ));
		$footer_block5_ids 		= 		array_map('intval', explode(",", $homepage['footer_block5_ids'] ));
		$footer_block6_ids 		= 		array_map('intval', explode(",", $homepage['footer_block6_ids'] ));

		$footer_block1_services 		=		Service::active()->whereIn('_id', $footer_block1_ids)->lists('name','_id');
		$footer_block2_services 		=		Service::active()->whereIn('_id', $footer_block2_ids)->lists('name','_id');
		$footer_block3_services 		=		Service::active()->whereIn('_id', $footer_block3_ids)->lists('name','_id');
		$footer_block4_services 		=		Service::active()->whereIn('_id', $footer_block4_ids)->lists('name','_id');																										
		$footer_block5_services 		=		Service::active()->whereIn('_id', $footer_block5_ids)->lists('name','_id');																										
		$footer_block6_services 		=		Service::active()->whereIn('_id', $footer_block6_ids)->lists('name','_id');																										

		array_set($footer_services,  'footer_block1_services', $footer_block1_services);									
		array_set($footer_services,  'footer_block2_services', $footer_block2_services);									
		array_set($footer_services,  'footer_block3_services', $footer_block3_services);									
		array_set($footer_services,  'footer_block4_services', $footer_block4_services);	
		array_set($footer_services,  'footer_block5_services', $footer_block5_services);	
		array_set($footer_services,  'footer_block6_services', $footer_block6_services);	

		array_set($footer_services,  'footer_block1_title', (isset($homepage['footer_block1_title']) && $homepage['footer_block1_title'] != '') ? $homepage['footer_block1_title'] : '');									
		array_set($footer_services,  'footer_block2_title', (isset($homepage['footer_block2_title']) && $homepage['footer_block2_title'] != '') ? $homepage['footer_block2_title'] : '');									
		array_set($footer_services,  'footer_block3_title', (isset($homepage['footer_block3_title']) && $homepage['footer_block3_title'] != '') ? $homepage['footer_block3_title'] : '');									
		array_set($footer_services,  'footer_block4_title', (isset($homepage['footer_block4_title']) && $homepage['footer_block4_title'] != '') ? $homepage['footer_block4_title'] : '');									
		array_set($footer_services,  'footer_block5_title', (isset($homepage['footer_block5_title']) && $homepage['footer_block5_title'] != '') ? $homepage['footer_block5_title'] : '');									
		array_set($footer_services,  'footer_block6_title', (isset($homepage['footer_block6_title']) && $homepage['footer_block6_title'] != '') ? $homepage['footer_block6_title'] : '');

		return $footer_services;
	}

}
