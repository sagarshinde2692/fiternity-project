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

		$service = Service::where('_id', (int) $serviceid)
		->with('serviceratecards')
		->with('category')
		->with('subcategory')->with('location')->with('city')->with('finder')->first();
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
												->where('finder_id', '=', $servicefinderid)
												->where('_id', '!=', intval($serviceid))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->remember(Config::get('app.cachetime'))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage'])
												->take(5)->toArray();	

		//same service form same location and same category
		$nearby_same_category 		=		Service::active()
												->where('servicecategory_id', '=', $servicecategoryid)
												->where('location_id', '=' ,$servicelocationid)
												->where('_id', '!=', intval($serviceid))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->remember(Config::get('app.cachetime'))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage'])
												->take(5)->toArray();																								

		//different service form same location and same category
		$nearby_other_category 		=		Service::active()
												->where('servicecategory_id', '!=', $servicecategoryid)
												->where('location_id','=',$servicelocationid)
												->where('_id', '!=', intval($serviceid))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->remember(Config::get('app.cachetime'))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage'])
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
			'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
			'service_coverimage_thumb' => (isset($item['service_coverimage_thumb']) && $item['service_coverimage_thumb'] != '') ? strtolower($item['service_coverimage_thumb']) : "",
			'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
			'lat' => (isset($item['lat']) && $item['lat'] != '') ? strtolower($item['lat']) : "",
			'lon' => (isset($item['lon']) && $item['lon'] != '') ? strtolower($item['lon']) : "",
			'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
			'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
			'workout_tags' => (isset($item['workout_tags']) && !empty($item['workout_tags'])) ? array_map('strtolower',$item['workout_tags']) : "",
			'short_description' => (isset($item['short_description']) && $item['short_description'] != '') ? $item['short_description'] : "", 
			'batches' => (isset($item['batches']) && $item['batches'] != '') ? $item['batches'] : [], 
			'body' => (isset($item['body']) && $item['body'] != '') ? $item['body'] : "", 
			'timing' => (isset($item['timing']) && $item['timing'] != '') ? $item['timing'] : "", 
			'address' => (isset($item['address']) && $item['address'] != '') ? $item['address'] : "", 
			'what_i_should_carry' => (isset($item['what_i_should_carry']) && $item['what_i_should_carry'] != '') ? $item['what_i_should_carry'] : "", 
			'what_i_should_expect' => (isset($item['what_i_should_expect']) && $item['what_i_should_expect'] != '') ? $item['what_i_should_expect'] : "", 
			'serviceratecards' =>  (isset($item['serviceratecards']) && !empty($item['serviceratecards'])) ? $item['serviceratecards'] : [],
//			'service_ratecards' =>  (isset($item['service_ratecards']) && !empty($item['service_ratecards'])) ? $item['service_ratecards'] : "",
			'category' =>  array_only($item['category'], array('_id', 'name', 'slug', 'parent_name')) ,
			'subcategory' =>  array_only($item['subcategory'], array('_id', 'name', 'slug', 'parent_name')) ,
			'location' =>  array_only($item['location'], array('_id', 'name', 'slug')) ,
			'city' =>  array_only($item['city'], array('_id', 'name', 'slug')) ,
			'active_weekdays' => (isset($item['active_weekdays']) && $item['active_weekdays'] != '') ? array_map('strtolower',$item['active_weekdays']) : "",
			'workoutsession_active_weekdays' => (isset($item['workoutsession_active_weekdays']) && $item['workoutsession_active_weekdays'] != '') ? array_map('strtolower',$item['workoutsession_active_weekdays']) : "",
			'trialschedules' => (isset($item['trialschedules']) && !empty($item['trialschedules'])) ? $item['trialschedules'] : "",
			'service_gallery' => (isset($item['service_gallery']) && !empty($item['service_gallery'])) ? $item['service_gallery'] : ""

			// 'workoutsessionschedules' => (isset($item['workoutsessionschedules']) && !empty($item['workoutsessionschedules'])) ? $item['workoutsessionschedules'] : "",
		);
		
		// return $data;
						
		if(isset($item['finder']) && $item['finder'] != ''){
			$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
							->with(array('location'=>function($query){$query->select('_id','name','slug');}))
							->where('_id', (int) $service['finder_id'])
							->first();
			// return $finderarr;
			$data['finder'] = array_only($item['finder'], array('_id', 'title', 'slug', 'coverimage', 'city_id', 'photos', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect', 'total_rating_count', 'average_rating', 'detail_rating_summary_count', 'detail_rating_summary_average'));
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

		$info_timing = $this->getInfoTiming($data);

        if(isset($data['timing']) && $info_timing != ""){
            $data['timing'] = $info_timing;
        }

		return $data;
	}




	public function getServiceHomePageDataV1($city = 'mumbai',$cache = false){   

		$home_by_city = $cache ? Cache::tags('servicehome_by_city_v1')->has($city) : false;

		if(!$home_by_city){
			$banners = $categorys = $locations = $feature_services = $footer_services = [];

			$citydata 		=		City::where('slug', '=', $city)->first(array('name','slug'));
			$city_name 		= 		$citydata['name'];
			$city_id		= 		(int) $citydata['_id'];	
			$homepage 		= 		Servicehomepage::where('city_id', '=', $city_id)->get()->first();						

			$feature_services  	= 	$this->feature_services($homepage);
			// $footer_services  	= 	$this->footer_services($homepage);
			$banners			= 	Servicehomepagebanner::active()->whereIn('city_id',array($city_id))->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('_id','caption','banner_link','banner_image','ordering'));
			$locations			= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));
			$categorys	 		= 	Servicecategory::active()->where('parent_id', 0)->orderBy('name')->get(array('name','slug'));	

			$homedata 			= 	['city_name' => $city_name, 'city_id' => $city_id, 'banners' => $banners, 'categorys' => $categorys, 'locations' => $locations, 'feature_services' => $feature_services, 'footer_services' => $footer_services];

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
												->whereIn('_id', $feature_block1_ids)
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'ratecards', 'service_coverimage', 'service_coverimage']);

		$feature_block2_services 		=		Service::active()
												->whereIn('_id', $feature_block2_ids)
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'ratecards', 'service_coverimage', 'service_coverimage']);

		$feature_block3_services 		=		Service::active()
												->whereIn('_id', $feature_block3_ids)
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'ratecards', 'service_coverimage', 'service_coverimage']);

		$feature_block4_services 		=		Service::active()
												->whereIn('_id', $feature_block4_ids)
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'ratecards', 'service_coverimage', 'service_coverimage']);																										

		$feature_block5_services 		=		Service::active()
												->whereIn('_id', $feature_block5_ids)
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'ratecards', 'service_coverimage', 'service_coverimage']);																										

		$feature_block6_services 		=		Service::active()
												->whereIn('_id', $feature_block6_ids)
												->with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage');}))
												->get(['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'ratecards', 'service_coverimage', 'service_coverimage']);																										


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

		$footer_block1_services 		=		Service::active()->whereIn('_id', $footer_block1_ids)->get(['name','finder_id','_id','slug']);
		$footer_block2_services 		=		Service::active()->whereIn('_id', $footer_block2_ids)->get(['name','finder_id','_id','slug']);
		$footer_block3_services 		=		Service::active()->whereIn('_id', $footer_block3_ids)->get(['name','finder_id','_id','slug']);
		$footer_block4_services 		=		Service::active()->whereIn('_id', $footer_block4_ids)->get(['name','finder_id','_id','slug']);																										
		$footer_block5_services 		=		Service::active()->whereIn('_id', $footer_block5_ids)->get(['name','finder_id','_id','slug']);																										
		$footer_block6_services 		=		Service::active()->whereIn('_id', $footer_block6_ids)->get(['name','finder_id','_id','slug']);																										

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


	public function getFooterByCityV1($city = 'mumbai',$cache = false){   

		$home_by_city = $cache ? Cache::tags('servicehomefooter_by_city_v1')->has($city) : false;

		if(!$home_by_city){
			$categorys = $locations =  $footer_services = [];

			$citydata 		=		City::where('slug', '=', $city)->first(array('name','slug'));
			$city_name 		= 		$citydata['name'];
			$city_id		= 		(int) $citydata['_id'];	
			$homepage 		= 		Servicehomepage::where('city_id', '=', $city_id)->get()->first();						

			$footer_services  	= 	$this->footer_services($homepage);
			$locations			= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));
			$categorys	 		= 	Servicecategory::active()->where('parent_id', 0)->orderBy('name')->get(array('name','slug'));	
			$homedata 			= 	['city_name' => $city_name, 'city_id' => $city_id, 'categorys' => $categorys, 'locations' => $locations, 'footer_services' => $footer_services];

			Cache::tags('servicehomefooter_by_city_v1')->put($city, $homedata, Config::get('cache.cache_time'));
		}

		return Response::json(Cache::tags('servicehomefooter_by_city_v1')->get($city));

	}

	public function getServiceWithWorkoutSession($finder_id){

		$services = array();

		Service::$withoutAppends = true; 

		$services = Service::active()->where('finder_id', '=', (int)$finder_id)->where('workoutsessionschedules','!=',array())->orderBy('ordering','asc')->get(array('_id','name'));

		if(count($services) > 0){
			$services = $services->toArray();
		}

		return Response::json($services,200);
	}

	public function getServicesByType($finder_id,$type){

    	switch ($type) {
    		case 'workoutsession': return $this->getServiceWithWorkoutSession($finder_id); break;
    		case 'membership': $type = array('membership','packages'); break;
    		case 'package': $type = array('packages'); break;
    	}

    	$service_id = Ratecard::where('finder_id',(int) $finder_id)->whereIn('type',$type)->lists('service_id');

    	$service_id = array_map('intval',array_unique($service_id));

		$services = array();

		Service::$withoutAppends = true; 

		$services = Service::active()->whereIn('_id',$service_id)->orderBy('ordering','asc')->get(array('_id','name'));

		if(count($services) > 0){
			$services = $services->toArray();
		}

		return Response::json($services,200);
	}

	public function getWorkoutSessionScheduleByService($service_id,$date = null){

        $currentDateTime        =   \Carbon\Carbon::now();
        $service_id    =   (int) $service_id;
        $date         =   ($date == null) ? Carbon::now() : $date;
        $timestamp    =   strtotime($date);
        $weekday      =   strtolower(date( "l", $timestamp));

        $date = date('d-m-Y',strtotime($date));

        $item = Service::active()->where('_id', '=', $service_id)->first(array('_id','name','finder_id', 'workoutsessionschedules','servicecategory_id'));

        $time_in_seconds = time_passed_check($item['servicecategory_id']);
		if(count($item) > 0){
			$item = $item->toArray();
			$slots = array();

			foreach ($item['workoutsessionschedules'] as $key => $value) {

				if($value['weekday'] == $weekday){

					if(!empty($value['slots'])){
						
						foreach ($value['slots'] as $key => $slot) {

							try{

								$scheduleDateTime     =   Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".strtoupper($slot['start_time'])));
								$slot_datetime_pass_status      =   ($currentDateTime->diffInMinutes($scheduleDateTime, false) > $time_in_seconds) ? false : true;
								array_set($slot, 'passed', $slot_datetime_pass_status);
								array_set($slot, 'service_id', $item['_id']);
								array_set($slot, 'finder_id', $item['finder_id']);
								array_push($slots, $slot);

							}catch(Exception $e){

								Log::info("getWorkoutSessionScheduleByService Error : ".$date." ".$slot['start_time']);
							}
							
						}
					}
					break;
				}
				
			}

			$data['_id'] = (int)$service_id;
			$data['name'] = $item['name'];
			$data['finder_id'] = $item['finder_id'];
			$data['slots'] = $slots;
			$data['weekday'] = $weekday;
			return Response::json($data,200);
		}else{
			$data = array();
			return Response::json($data,200);
		}

        
    }

    public function getInfoTiming($services){

    	$batch = array();

        if(isset($services['batches']) && !empty($services['batches'])){

            $batch = $this->getAllBatches($services['batches']);
        }

        $info_timing = "";

        if(count($batch) > 0){

            foreach ($batch as $btch_value){

                foreach ($btch_value as $key => $value) {
                    $info_timing .= "<p><i>".$this->matchAndReturn($value)." : </i>". $key ."</p>";
                }

            }
        }

        return $info_timing;

    }

    public function getAllBatches($batches){

        $result = array();

        foreach ($batches as $key => $batch) {

            $result_weekday = array();

            foreach ($batch as $data) {

                $count = 0;

                if(isset($data['slots'])){
                    foreach ($data['slots'] as $slot) {
                        if($count == 0){

                            if(isset($slot['weekday']) && isset($slot['slot_time'])){
                                $result_weekday[ucwords($slot['weekday'])] = strtoupper($slot['slot_time']);
                            }
                            
                        }else{
                            break;
                        }

                        $count++;
                    }
                }
            }

            $result[] = $this->getDupKeys($result_weekday);

        }

        return $result;
            
    }

    public function getDupKeys($array) {

        $dups = array();

        foreach ($array as $k => $v) {
                $dups[$v][] = $k;
        }

        foreach($dups as $k => $v){

            $dups[$k] = implode(", ", $v);

        }

        return $dups;
    }

    public function matchAndReturn($key){

        $match = array(
            "Monday, Tuesday, Wednesday"=>"Monday - Wednesday",
            "Monday, Tuesday, Wednesday, Thursday"=>"Monday - Thursday",
            "Monday, Tuesday, Wednesday, Thursday, Friday"=>"Monday - Friday",
            "Monday, Tuesday, Wednesday, Thursday, Friday, Saturday"=>"Monday - Saturday",
            "Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday"=>"Monday - Sunday",
        );

        if(array_key_exists($key,$match)){
            return $match[$key];
        }else{
            return $key;
        }
    }

   
     public function getScheduleByFinderService($request = false,$count = 1){

    	if(!$request){

    		$request = $_REQUEST;
    		$request['requested_date'] = $request['date'];
    	}

    	if(!isset($request['finder_id']) && !isset($request['service_id'])){
    		return Response::json(array('status'=>401,'message'=>'finder or service is required'),401);
    	}

        $currentDateTime        =   time();
        $date         			=   (isset($request['date']) && $request['date'] != "") ? date('Y-m-d',strtotime($request['date'])) : date("Y-m-d");
        $timestamp    			=   strtotime($date);
        $weekday     			=   strtolower(date( "l", $timestamp));
        $type 					= 	(isset($request['type']) && $request['type'] != "") ? $request['type'] : "trial" ;
        $recursive 				= 	(isset($request['recursive']) && $request['recursive'] != "" && $request['recursive'] == "true") ? true : false ;

        $query = Service::active()->select('_id','name','finder_id', 'workoutsessionschedules','servicecategory_id','trialschedules','vip_trial','three_day_trial');

        (isset($request['finder_id']) && $request['finder_id'] != "") ? $query->where('finder_id',(int)$request['finder_id']) : null;

        (isset($request['service_id']) && $request['service_id'] != "") ? $query->where('_id',(int)$request['service_id']) : null;

        $items = $query->get();

        if(count($items) == 0){
        	return Response::json(array('status'=>401,'message'=>'data is empty'),401);
        }

        $schedules = array();

        switch ($type) {

        	case 'workout_session': $type = 'workoutsessionschedules'; break;
        	case 'trial': $type = 'trialschedules'; break;
        	default: $type = 'trialschedules'; break;
        }

        foreach ($items as $k => $item) {

        	switch ($type) {
	        	case 'workoutsessionschedules': $ratecard = Ratecard::where('service_id',$item['_id'])->where('type','workout session')->first(); break;
	        	case 'trialschedules': $ratecard = Ratecard::where('service_id',$item['_id'])->where('type','trial')->first(); break;
	        	default: $ratecard = Ratecard::where('service_id',$item['_id'])->where('type','trial')->first(); break;
	        }

	        $ratecard_id = $ratecard->_id;

        	$item['three_day_trial'] = isset($item['three_day_trial']) ? $item['three_day_trial'] : "";
            $item['vip_trial'] = "";//isset($item['vip_trial']) ? $item['vip_trial'] : "";

            $weekdayslots = head(array_where($item[$type], function($key, $value) use ($weekday){
                if($value['weekday'] == $weekday){
                    return $value;
                }
            }));

            $time_in_seconds = time_passed_check($item['servicecategory_id']);

            $service = array('service_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'service_name' => $item['name'], 'weekday' => $weekday,'three_day_trial' => $item['three_day_trial'],'vip_trial' => $item['vip_trial']);

            $slots = array();

            if(count($weekdayslots['slots']) > 0){

                foreach ($weekdayslots['slots'] as $slot) {

                    $slot_status 		= 	"available";
                    
                    array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
                    array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
                    array_set($slot, 'status', $slot_status);

                    $vip_trial_amount = 0;

                    if($item['vip_trial'] == "1"){

                        $price = (int) $slot['price'];

                        if($price >= 500){
                            $vip_trial_amount = $price;
                        }

                        if($price < 500){
                            $vip_trial_amount = $price+150;
                        }

                        if($price == 0){
                            $vip_trial_amount = 199;
                        }

                    }

                    array_set($slot, 'vip_trial_amount', $vip_trial_amount);

                    try{

                    	$scheduleDateTimeUnix               =  strtotime(strtoupper($date." ".$slot['start_time']));
                        $slot_datetime_pass_status      =   (($scheduleDateTimeUnix - time()) > $time_in_seconds) ? false : true;
                        array_set($slot, 'passed', $slot_datetime_pass_status);
                        array_set($slot, 'service_id', $item['_id']);
                        array_set($slot, 'finder_id', $item['finder_id']);
                        array_set($slot, 'ratecard_id', $ratecard_id);
                        array_push($slots, $slot);

                    }catch(Exception $e){

                        Log::info("getTrialSchedule Error : ".$date." ".$slot['start_time']);
                    }
                     
                }
                
            }

            $service['slots'] = $slots;
            array_push($schedules, $service);
        }

        $request['date'] = date("Y-m-d",strtotime($date." +1 days"));

        $flag = false;

        foreach ($schedules as $key => $value) {

        	(count($value['slots']) > 0) ? $flag = true : null;
        }

        if(!$flag && $count < 7 && $recursive){

        	$count += 1;

        	return $this->getScheduleByFinderService($request,$count);

        }else{

        	$data['finder_id'] = $item['finder_id'];
	        $data['schedules'] = $schedules;
	        $data['weekday'] = $weekday;
	        $data['available_date'] = $date;
	        $data['count'] = $count;
        	$data['todays_date'] = date("Y-m-d");
        	$data['requested_date'] = $request['requested_date'];

	        return Response::json($data,200);
        }

    }
}
