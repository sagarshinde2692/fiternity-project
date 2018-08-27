<?PHP

/** 
 * ControllerName : ServiceController.
 * Maintains a list of functions used for ServiceController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */
use App\Services\Metropolis as Metropolis;
use App\Services\Utilities as Utilities;

class ServiceController extends \BaseController {
	protected $utilities;
	public function __construct(Utilities $utilities) {

		parent::__construct();

		$this->utilities = $utilities;

		$this->vendor_token = false;
        
        $vendor_token = Request::header('Authorization-Vendor');

        if($vendor_token){

            $this->vendor_token = true;
		}
		
		$this->error_status = ($this->vendor_token) ? 200 : 400;
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
		$servicedata['locationtags'] = $servicedata['finder']['locationtags'];
		if($servicecategoryid==180){
			if($servicedata['meal_type']=='lunch' && isset($servicedata['finder']['lunchlocationtags'])){
				$servicedata['locationtags'] = $servicedata['finder']['lunchlocationtags'];
			}else if($servicedata['meal_type']=='dinner' && isset($servicedata['finder']['dinnerlocationtags'])){
				$servicedata['locationtags'] = $servicedata['finder']['dinnerlocationtags'];
			}
		}

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
			'service_gallery' => (isset($item['service_gallery']) && !empty($item['service_gallery'])) ? $item['service_gallery'] : "",
			'meal_type' => (isset($item['meal_type'])) ? $item['meal_type'] : "",


			// 'workoutsessionschedules' => (isset($item['workoutsessionschedules']) && !empty($item['workoutsessionschedules'])) ? $item['workoutsessionschedules'] : "",
		);
		
		// return $data;
						
		if(isset($item['finder']) && $item['finder'] != ''){
			$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
							->with(array('location'=>function($query){$query->select('_id','name','slug');}))
							->where('_id', (int) $service['finder_id'])
							->first();
			// return $finderarr;
			$data['finder'] = array_only($item['finder'], array('_id', 'title', 'slug', 'coverimage', 'city_id', 'photos', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect', 'total_rating_count', 'cal', 'detail_rating_summary_count', 'detail_rating_summary_average', 'locationtags', 'lunchlocationtags', 'dinnerlocationtags'));
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




	public function getServiceHomePageDataV1($city = 'mumbai',$cache = true){   

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


	public function getFooterByCityV1($city = 'mumbai',$cache = true){   

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

        if($this->vendor_token){

        	$currentDateTime = time() - 7200;
        }

        $date = date('d-m-Y',strtotime($date));

        $item = Service::active()->where('_id', '=', $service_id)->first(array('_id','name','finder_id', 'workoutsessionschedules','servicecategory_id'));

        $time_in_seconds = time_passed_check($item['servicecategory_id']);

        $data = array();

		if(count($item) > 0){

			$ratecard_id = Ratecard::where('finder_id',$item['finder_id'])->where('service_id',$item['_id'])->where('type', 'workout session')->get(['id'])->first();
			$item = $item->toArray();
			$slots = array();

			if($ratecard_id){

				foreach ($item['workoutsessionschedules'] as $key => $value) {

					if($value['weekday'] == $weekday){

						if(!empty($value['slots'])){
							
							foreach ($value['slots'] as $key => $slot) {

								try{

									/*$scheduleDateTime     =   Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".strtoupper($slot['start_time'])));
									$slot_datetime_pass_status      =   ($currentDateTime->diffInMinutes($scheduleDateTime, false) > $time_in_seconds) ? false : true;*/

									$scheduleDateTimeUnix               =  strtotime(strtoupper($date." ".$slot['start_time']));
                        			$slot_datetime_pass_status      =   (($scheduleDateTimeUnix - time()) > $time_in_seconds) ? false : true;

									array_set($slot, 'passed', $slot_datetime_pass_status);
									array_set($slot, 'service_id', $item['_id']);
									array_set($slot, 'finder_id', $item['finder_id']);
									array_set($slot, 'ratecard_id', $ratecard_id['id']);
									array_push($slots, $slot);

								}catch(Exception $e){

									Log::info("getWorkoutSessionScheduleByService Error : ".$date." ".$slot['start_time']);
								}
								
							}
						}
						break;
					}
					
				}
			}
			
			$data['_id'] = (int)$service_id;
			$data['name'] = $item['name'];
			$data['finder_id'] = $item['finder_id'];
			$data['slots'] = $slots;
			$data['weekday'] = $weekday;
			
		}

		return Response::json($data,200);
        
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
    		$request['requested_date'] = (isset($request['date']) && $request['date'] != "") ? date('Y-m-d',strtotime($request['date'])) : date("Y-m-d");
    		$request['date'] = $request['requested_date'];
    	}
		Log::info($request);
    	if(!isset($request['finder_id']) && !isset($request['service_id'])){
    		return Response::json(array('status'=>401,'message'=>'finder or service is required'),401);
    	}

        $currentDateTime        =   time();

        if($this->vendor_token){

        	$decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = $decodeKioskVendorToken->vendor;

            $finder_id = (int)$vendor->_id;

        	$currentDateTime = time() - 7200;

        	$jwt_token = Request::header('Authorization');

			if($jwt_token == true && $jwt_token != 'null' && $jwt_token != null){

	            $decoded = decode_customer_token();

	            $customer_id = intval($decoded->customer->_id);

	            $booktrial_count = Booktrial::where('customer_id', $customer_id)
                        ->where('finder_id', '=',$finder_id)
                        ->where('type','booktrials')
                        ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                        ->count();

 				if($booktrial_count > 0){

 					$request['type'] = 'workout_session';
	        	}
	        }
        }

        $date         			=   (isset($request['date']) && $request['date'] != "") ? date('Y-m-d',strtotime($request['date'])) : date("Y-m-d");
        $timestamp    			=   strtotime($date);
        $weekday     			=   strtolower(date( "l", $timestamp));
        $type 					= 	(isset($request['type']) && $request['type'] != "") ? $request['type'] : "trial" ;
        $recursive 				= 	(isset($request['recursive']) && $request['recursive'] != "" && $request['recursive'] == "true") ? true : false ;

		$selectedFieldsForService = array('_id','name','finder_id','servicecategory_id','vip_trial','three_day_trial','address','trial', 'city_id');
		Service::$withoutAppends=true;
		Service::$setAppends=['trial_active_weekdays', 'workoutsession_active_weekdays'];
		
        $query = Service::active()->where('trial','!=','disable');



        (isset($request['finder_id']) && $request['finder_id'] != "") ? $query->where('finder_id',(int)$request['finder_id']) : null;

        (isset($request['service_id']) && $request['service_id'] != "") ? $query->where('_id',(int)$request['service_id']) : null;

		switch ($type) {
			case 'workout-session':
        	case 'workout_session': $ratecard_type = 'workout session'; array_push($selectedFieldsForService,'workoutsessionschedules'); break;
        	case 'trial': $ratecard_type = 'trial'; array_push($selectedFieldsForService,'trialschedules');break;
        	default: $ratecard_type = 'trial'; array_push($selectedFieldsForService,'trialschedules');break;
        }
     	 $items = $query->with(array('serviceratecards'=> function($query) use ($ratecard_type){
			 $query->where('type',$ratecard_type);
		 }))->get($selectedFieldsForService)->toArray();

		//  $items = $query->get()->toArray();


		

        if(count($items) == 0){
        	return Response::json(array('status'=>401,'message'=>'data is empty'),401);
        }

		Finder::$withoutAppends = true;
		Finder::$setAppends = ['inoperational_dates_array'];

		$finder_id = $items[0]['finder_id'];
		// $finder = Finder::find($finder_id, array('inoperational_dates'));
		$finder = Finder::find($finder_id);

		$findercategory_id = isset($finder->category_id) ? $finder->category_id : null;
		
		$city_id = isset($items[0]['city_id'])?$items[0]['city_id']:0;

        $schedules = array();

        switch ($type) {
			case 'workout-session':
        	case 'workout_session': $type = 'workoutsessionschedules'; break;
        	case 'trial': $type = 'trialschedules'; break;
        	default: $type = 'trialschedules'; break;
        }

		// $all_trials_booked = true;

		

        foreach ($items as $k => $item) {

        	$item['three_day_trial'] = isset($item['three_day_trial']) ? $item['three_day_trial'] : "";
            $item['vip_trial'] = "";//isset($item['vip_trial']) ? $item['vip_trial'] : "";
			$item['address'] = isset($item['address']) ? $item['address'] : "";
			$trial_status = isset($item['trial']) ? $item['trial'] : "";

			// return $item;

			$weekdayslots = false;


			if(!in_array($timestamp, $finder['inoperational_dates_array'])){
				$weekdayslots = head(array_where($item[$type], function($key, $value) use ($weekday){
					if($value['weekday'] == $weekday){
						return $value;
					}
				}));
			}
			
			$time_in_seconds = time_passed_check($item['servicecategory_id']);
			
			if(isset($request['time_interval']) && $request['time_interval']){
				$time_in_seconds = $request['time_interval'];
			}

            $service = array(
            	'service_id' => $item['_id'],
            	'finder_id' => $item['finder_id'],
            	'service_name' => $item['name'],
            	'weekday' => $weekday,
            	'three_day_trial' => $item['three_day_trial'],
            	'vip_trial' => $item['vip_trial'],
            	'address' => $item['address'],
            	'available_date'=>"",
            	'trial_status'=>$trial_status,
            	'current_available_date_diff'=>0,
            	'popup_message'=>'Slot available is beyond booking range. Select an earlier slot or get in touch with us at '.Config::get('app.contact_us_customer_number'),
            	'available_message' => "No Slots Available",
            	'workout_session' => [
	    			"available" => false,
	    			"amount" => 0
	    		],
				'workoutsession_active_weekdays' => $item["workoutsession_active_weekdays"],
				'trial_active_weekdays' => $item["trial_active_weekdays"],
				'inoperational_dates_array' => $finder['inoperational_dates_array'],
				'cost'=>'Free Via Fitternity'
			);
			
			$slots = array();

            $slots_timewise = array(
				'morning'=>[],
				'afternoon'=>[],
				'evening'=>[],
			);
			$total_slots_count = 0;
			$total_slots_available_count = 0;
            // switch ($type) {
	        // 	case 'workoutsessionschedules': $ratecard = Ratecard::where('service_id',(int)$item['_id'])->where('type','workout session')->first(); break;
	        // 	case 'trialschedules': $ratecard = Ratecard::where('service_id',(int)$item['_id'])->where('type','trial')->first(); break;
	        // 	default: $ratecard = Ratecard::where('service_id',(int)$item['_id'])->where('type','trial')->first(); break;
	        // }
			// return array("name" => $item["name"],"rate"=>$item["serviceratecards"], "item"=>$item);
			if(isset($item["serviceratecards"]) && isset($item["serviceratecards"][0]) > 0 )
			{
				$ratecard = $item["serviceratecards"][0];
			}else{
				continue;
			}
			

			// return $item;
			// if($ratecard){
			// 	$ratecard = $ratecard->toArray();
			// }

	        $slot_passed_flag = true;
			
            if(count($weekdayslots['slots']) > 0 && isset($ratecard['_id'])){
				if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
					$ratecard_price = $ratecard['special_price'];
                }else{
					$ratecard_price = $ratecard['price'];
                }
//******************************************************************************************************DYNAMIC PRICING START*****************************************************************************************
                /* if($type == "workoutsessionschedules"){
                	$temp=$this->utilities->getPeakAndNonPeakPrice($weekdayslots['slots'],$this->utilities->getPrimaryCategory(null,$service['service_id']));

                	$service["workout_session"] = [];
                	$service['cost']=[];
                	if(!empty($temp))
                	{
                		if(isset($temp['peak']))
                		{
                				$service["workout_session"]['available']=true;
                				$service["workout_session"]["peak_amount"]=$temp['peak'];
                				$service['cost']['peak'] = "₹ ".$temp['peak'];
                		}
                		if(isset($temp['non_peak']))
                		{
	                			$service["workout_session"]["non_peak_amount"]=$temp['non_peak'];
	                			$service['cost']['non_peak'] = "₹ ".$temp['non_peak'];
                		}
                	}
		    	} */
//******************************************************************************************************DYNAMIC PRICING END*****************************************************************************************
		    	if($ratecard_price > 0&&$type !== "workoutsessionschedules"){
		    		$service['cost'] = "₹. ".$ratecard_price;
		    	}
		    	if(!empty($weekdayslots)&&!empty($weekdayslots['slots'])&&count($weekdayslots['slots'])>0&&(isset($_GET['source']) && $_GET['source'] == 'pps'))
		    	{
		    		$rsh=["title"=>"RUSH HOUR","price"=>"","data"=>[], 'image'=>'https://b.fitn.in/paypersession/rush_hour_icon@3x1.png', 'slot_type'=>1];$nrsh=["title"=>"NON RUSH HOUR","price"=>"","data"=>[], 'image'=>'https://b.fitn.in/paypersession/non_rush_hour@3x1.png', 'slot_type'=>0];
		    		
		    		$p_np=$this->utilities->getPeakAndNonPeakPrice($weekdayslots['slots'],$this->utilities->getPrimaryCategory(null,$service['service_id']));
		    		if(!empty($p_np))
		    		{
		    			$rsh['price']=(isset($p_np['peak']))?$this->utilities->getRupeeForm($p_np['peak']):"";
		    			$nrsh['price']=(isset($p_np['non_peak']))?$this->utilities->getRupeeForm($p_np['non_peak']):"";
		    		}
		    		array_push($slots,$rsh);array_push($slots,$nrsh);
		    	}
		    	
				foreach ($weekdayslots['slots'] as $slot) {

					if(!empty($finder)&&!empty($finder['flags'])&&!empty($finder['flags']['newly_launched_date']))
					{
						if($finder['flags']['newly_launched_date']->sec>$timestamp)
							$dontShow=true;
					}
					if(!isNotInoperationalDate($date, $city_id, $slot, $findercategory_id)||(isset($dontShow)&&$dontShow)){
						continue;
					}

                    $slot_status 		= 	"available";
                    
                    array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
                    array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
                    array_set($slot, 'status', $slot_status);

                    $vip_trial_amount = 0;

                    /*if($item['vip_trial'] == "1"){

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

                    }*/

                    array_set($slot, 'vip_trial_amount', $vip_trial_amount);

                    try{
						
                    	$scheduleDateTimeUnix               =  strtotime(strtoupper($date." ".$slot['start_time']));
						$slot_datetime_pass_status      =   (($scheduleDateTimeUnix - $currentDateTime) > $time_in_seconds) ? false : true;
						
						if(isset($request['within_time']) && $request['within_time'] && !$slot_datetime_pass_status){
							$slot_datetime_pass_status = (($scheduleDateTimeUnix - $currentDateTime) < $request['within_time']) ? false : true;
						}
						
						if(isset($request['show_all']) && $request['show_all']){
							$slot_datetime_pass_status = false;
						}

						($slot_datetime_pass_status == false) ? $slot_passed_flag = false : null;

                        array_set($slot, 'price', $ratecard_price);
                        array_set($slot, 'passed', $slot_datetime_pass_status);
                        array_set($slot, 'service_id', $item['_id']);
                        array_set($slot, 'finder_id', $item['finder_id']);
                        array_set($slot, 'ratecard_id', $ratecard['_id']);
                        array_set($slot,'epoch_start_time',strtotime(strtoupper($date." ".$slot['start_time'])));
						array_set($slot,'epoch_end_time',strtotime(strtoupper($date." ".$slot['end_time'])));
						$total_slots_count +=1;
						
						if(isset($_GET['source']) && $_GET['source'] == 'pps')
						$ck=$this->utilities->getWSNonPeakPrice($slot['start_time_24_hour_format'],$slot['end_time_24_hour_format'],null,$this->utilities->getPrimaryCategory(null,$service['service_id'],true));
						
						if(!$slot['passed']){
							$total_slots_available_count +=1;
// 							return $ck;
							if(intval($slot['start_time_24_hour_format']) < 12){
								array_push($slots_timewise['morning'], $slot);
							}elseif(intval($slot['start_time_24_hour_format']) < 16){
								array_push($slots_timewise['afternoon'], $slot);
							}else{
								array_push($slots_timewise['evening'], $slot);
							}
							if(isset($_GET['source']) && $_GET['source'] == 'pps')
								(!empty($ck)&&!empty($ck['peak']))?array_push($slots[0]['data'], $slot):array_push($slots[1]['data'], $slot);
							else array_push($slots,$slot);
						}
                    }catch(Exception $e){
						
						Log::info("getTrialSchedule Error : ".$date." ".$slot['start_time']);
                    }
					
                }
			}
			if((isset($_GET['source']) && $_GET['source'] == 'pps')){
				
				if(isset($_GET['slot_type']) && $_GET['slot_type'] == '0'){
					$slots = array_reverse($slots);
				}

				foreach($slots as $key => $slot){
					if(empty($slot['data'])){
						unset($slots[$key]);
					}
				}

				$slots = array_values($slots);

				if(count($slots) == 1){
					$slots[0]['title'] = 'BOOK A SLOT';
				}
			}
            
            $service['slot_passed_flag'] = $slot_passed_flag;
            if(!empty($slots)&&count($slots)>0&&!empty($slots[0])&&!empty($slots[0])&&!empty($slots[0]['data'])&&count($slots[0]['data'])==0)
            	unset($slots[0]);
            if(!empty($slots)&&count($slots)>0&&!empty($slots[1])&&!empty($slots[1])&&!empty($slots[1]['data'])&&count($slots[1]['data'])==0)
            	unset($slots[1]);
            $service['slots'] = array_values($slots);
			$service['slots_timewise'] = $slots_timewise;
			$service['total_slots_count'] = $total_slots_count;
			$service['total_slots_available_count'] = $total_slots_available_count;

// 			return $service;
            if(count($slots) <= 0){

            	$avaliable_request = [
            		'service_id' => $item['_id'],
            		'type' => $type,
            		'date' => $date
            	];

            //  $service['available_date'] = $this->getAvailableDateByService($avaliable_request);
            	$service['available_date'] = $this->getAvailableDateByServiceV1($service,$request, $type);
            	if($service['available_date'] != ""){
            		$service['current_available_date_diff'] = $this->getDateDiff($service['available_date']);
            		$service['available_message'] = "Next Slot is available on ".date("jS F, Y",strtotime($service['available_date']));
            	}
            }
			

            // $service['trials_booked'] = $this->checkTrialAlreadyBooked($item['finder_id'],$item['_id']);
            // $all_trials_booked = $all_trials_booked && $service['trials_booked'];

            if($this->vendor_token){

            	if(!empty($slots)){

            		array_push($schedules, $service);
            	}

            }else array_push($schedules, $service);
        }
        
        
//         return $service;

		// return $schedules;

		// return $trial_booked?'true':'false';

        $request['date'] = date("Y-m-d",strtotime($date." +1 days"));

        // $flag = false;

        // foreach ($schedules as $key => $value) {

        // 	// (count($value['slots']) > 0) ? $flag = true : null;
		// 	(!$value['slot_passed_flag']) ? $flag = true : null;
        // }

        $schedules_sort = array();
        $schedules_slots_empty = array();

        foreach ($schedules as $key => $value) 
        {
        	if(count($value['slots']) >0)
        	{
        		$schedules_sort[] = $value;
        	}
        	else
        	{
        		$schedules_slots_empty[] = $value;
        	}
        }

        $schedules_sort_passed_true = array();
        $schedules_sort_passed_false = array();

        foreach ($schedules_sort as $key => $value) {

        	if($value['slot_passed_flag']){
				$value['available_date'] = $this->getAvailableDateByServiceV1($value,$request, $type);
        		$schedules_sort_passed_true[] = $value;
        	}else{
        		$schedules_sort_passed_false[] = $value;
        	}
			$schedules_sort[$key] = $value;
        }
		
		$flag = count($schedules_sort_passed_false)>0?true:false;
		
        $schedules = array();

        $schedules = array_merge($schedules_sort_passed_false,$schedules_sort_passed_true,$schedules_slots_empty);
        
        if(!$flag && $count < 7 && $recursive){

        	$count += 1;

        	return $this->getScheduleByFinderService($request,$count);

        }else{

        	$data['status'] = 200;
        	$data['finder_id'] = $item['finder_id'];
	        $data['schedules'] = $schedules;
	        $data['weekday'] = $weekday;
	        $data['available_date'] = $date;
	        $data['count'] = $count;
        	$data['todays_date'] = date("Y-m-d");
        	$data['requested_date'] = $request['requested_date'];
			$data['trial_booked'] = $this->checkTrialAlreadyBooked($item['finder_id']);
			// $device_type = ['ios','android'];

			// if(isset($_GET['device_type']) && in_array($_GET['device_type'], $device_type)){
        	// 	$data['trial_booked'] = $this->checkTrialAlreadyBooked($item['finder_id']);
			// }else{
        	// 	$data['trial_booked'] = $all_trials_booked;
			// }

        	if($type == "trialschedules" &&  !empty($schedules)){
        		$data['schedules'] = $this->checkWorkoutSessionAvailable($schedules);
			}
			
			if(isset($_GET['source']) && $_GET['source'] == 'pps'){
				
				$slots = [];
				
				if(isset($data['schedules']) && count($data['schedules']) > 0 && !(isset($finder['trial']) && $finder['trial'] == 'disable'))
				{
					$schedule = $data['schedules'][0];
					if(isset($schedule['slots'])&&count($schedule['slots'])>0)
					{
						$slots =$schedule['slots'];
						//$slots = pluck($schedule['slots'], ['slot_time', 'price', 'service_id', 'finder_id', 'ratecard_id', 'epoch_start_time', 'epoch_end_time']);
					}
					$slots=$schedule['slots']; 
				}

				$message = "";
				
				if(count($slots) == 0){
					$message = "No slots available";
				}

				$data = [
					'status'=>200,
					'slots'=>$slots,
					'message'=>$message
				];


			} 


	        return Response::json($data,200);
        }

    }


    public function checkWorkoutSessionAvailable($schedules){

    	foreach ($schedules as $key => $value) {

    		$schedules[$key]["workout_session"] = [
    			"available" => false,
    			"amount" => 0
    		];

    		$ratecard = Ratecard::where("service_id",(int)$value["service_id"])->where('type','workout session')->orderBy("_id","desc")->first();

    		if($ratecard && !empty($value['slots'])){

    			if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                    $amount = $ratecard->special_price;
                }else{
                    $amount = $ratecard->price;
                }

    			$schedules[$key]["workout_session"] = [
	    			"available" => true,
	    			"amount" => $amount
	    		];
    		}

    	}

    	return $schedules;

    }

    public function checkTrialAlreadyBooked($finder_id,$service_id = false){

    	$return = false;

    	if($finder_id == ""){
        	return false;
        }

    	$customer_id = "";
        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token : '.$jwt_token);

        if($jwt_token == true && $jwt_token != 'null' && $jwt_token != null){
            $decoded = decode_customer_token();
            $customer_id = intval($decoded->customer->_id);
            $customer_email = intval($decoded->customer->email);

            $customer_phone = "";

            if(isset($decoded->customer->contact_no)){
				$customer_phone = $decoded->customer->contact_no;
			}
        }

        $booktrial_count = 0;

        if($customer_id != ""){

        	if($customer_phone != ""){

        		$query = Booktrial::where(function ($query) use($customer_email, $customer_phone) {
								$query->orWhere('customer_email', $customer_email)
									->orWhere('customer_phone','LIKE','%'.substr($customer_phone, -9).'%');
							})
                        ->where('finder_id',(int)$finder_id)
                        ->where('type','booktrials')
                        ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"]);
            }else{

            	$query = Booktrial::where('customer_email', $customer_email)
                        ->where('finder_id',(int)$finder_id)
                        ->where('type','booktrials')
                        ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"]);

            }

            // if($service_id){
            // 	$query->where('service_id',(int)$service_id);
            // }

            $booktrial_count = $query->count();
        }

        if($booktrial_count > 0){

        	$return = true;
        }

        return $return;

    }

    public function checkTrial($finder_id,$service_id = false){

    	$return =  array('trial_booked'=>false);

    	if($finder_id == ""){
        	return array('trial_booked'=>false);
        }

    	$customer_id = "";
        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token : '.$jwt_token);

        if($jwt_token == true && $jwt_token != 'null' && $jwt_token != null){
            $decoded = decode_customer_token();
            $customer_id = intval($decoded->customer->_id);
        }

        $booktrial_count = 0;

        if($customer_id != ""){

        	$booktrial_count = Booktrial::where('customer_id', $customer_id)
                        ->where('finder_id', '=',$finder_id)
                        ->where('type','booktrials')
                        ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                        ->count();
        }

        if($booktrial_count > 0){

        	$return =  array('trial_booked'=>true);
        }

        if($service_id){

        	$return = array('workout_session_available'=>false,'amount'=>0);

            if($booktrial_count > 0){

	        	$return = array('workout_session_available'=>false,'trial_booked'=>true,'amount'=>0);
	        }

            $ratecard = Ratecard::where('service_id',$service_id)->where('type','workout session')->first();

            $service = Service::find((int)$service_id);

            if($ratecard && $service && isset($service->workoutsessionschedules) && count($service->workoutsessionschedules) > 0){

                if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                    $amount = $ratecard->special_price;
                }else{
                    $amount = $ratecard->price;
                }

                $return = array('workout_session_available'=>true,'amount'=>(int)$amount);

                if($booktrial_count > 0){

		        	$return = array('workout_session_available'=>true,'trial_booked'=>true,'amount'=>(int)$amount);
		        }

            }
            
        }

        return $return;

    }

	public function getAvailableDateByServiceV1($service,$request, $type){
		$timestamp    			=   strtotime($request['date']);
		$weekday     			=   strtolower(date( "l", $timestamp));
		$active_weekdays 		=  $type = 'trialschedules' ? $service['trial_active_weekdays'] : $service['workoutsession_active_weekdays'];
		for($i = 1; $i < 7; $i++){
			$nextweekday     			=   strtolower(date( "l", strtotime("+".$i." days",$timestamp)));
			// Log::info($nextweekday." ++ ".$service["service_name"]);
			// Log::info($active_weekdays);
			if(in_array($nextweekday,$active_weekdays) && !in_array(strtotime("+".$i." days",$timestamp), $service['inoperational_dates_array'])){
				// Log::info("available timestamp:".strtotime("+".$i." days",$timestamp));
				$v = date("Y-m-d",strtotime("+".$i." days",$timestamp));
				Log::info("Yahan".$v);
				return  $v;
			}
		}
		return "";

		// $scheduleDateTimeUnix               =  strtotime(strtoupper($date." ".$slot['start_time']));
        // $slot_datetime_pass_status      =   (($scheduleDateTimeUnix - time()) > $time_in_seconds) ? false : true;
		
    }


    public function getAvailableDateByService($request,$count = 1){

    	$date 					= $request['date'];
        $currentDateTime        =   time();
        $timestamp    			=   strtotime($request['date']);
        $weekday     			=   strtolower(date( "l", $timestamp));
        $type 					= 	$request['type'];

        if($this->vendor_token){

        	$currentDateTime = time() - 7200; 
        }

        $service = Service::find((int)$request['service_id'],array('workoutsessionschedules','trialschedules'));

        switch ($type) {
        	case 'workoutsessionschedules': $ratecard = Ratecard::where('service_id',(int)$service['_id'])->where('type','workout session')->first(); break;
        	case 'trialschedules': $ratecard = Ratecard::where('service_id',(int)$service['_id'])->where('type','trial')->first(); break;
        	default: $ratecard = Ratecard::where('service_id',(int)$service['_id'])->where('type','trial')->first(); break;
        }

        $weekdayslots = head(array_where($service[$type], function($key, $value) use ($weekday){
            if($value['weekday'] == $weekday){
                return $value;
            }
        }));

        if(count($weekdayslots['slots']) > 0 && isset($ratecard['_id'])){

        	return $request['date'];

        }else{

        	$request['date'] = date("Y-m-d",strtotime($date." +1 days"));

        	if($count < 7){

	        	$count += 1;

	        	return $this->getAvailableDateByService($request,$count);

	        }else{

	        	return "";
	        }
        }

    }


    public function getDateDiff($requested_date){

    	$diff = 0;

    	$current_datetime = time();

    	if($this->vendor_token){

        	$current_datetime = time() - 7200; 
        }

    	$requested_datetime = strtotime($requested_date);

    	$diff = ceil(($requested_datetime - $current_datetime) / (60 * 60 * 24));

    	return $diff;
    }

    public function getMealDetailsById(){

    	$request = $_REQUEST;

    	$finder_id = "";

    	if(!isset($request['finder_id']) && !isset($request['service_id'])){
    		return Response::json(array('status'=>401,'message'=>'finder or service is required'),401);
    	}

        $query = Service::active()->where('trial','!=','disable')->select('_id','name','short_description','finder_id','trial','meal_type');

        if(isset($request['finder_id']) && $request['finder_id'] != ""){
        	$query->where('finder_id',(int)$request['finder_id']);
        	$finder_id = (int)$request['finder_id'];
        }

        if(isset($request['service_id']) && $request['service_id'] != ""){
        	$query->where('_id',(int)$request['service_id']);
        }

        $items = $query->get();

        if(count($items) == 0){
        	return Response::json(array('status'=>401,'message'=>'data is empty'),401);
        }

        $services = [];
		
        foreach ($items as $k => $item) {

        	$finder_id = (int)$item['finder_id'];

        	$service_data = [
        		'service_id'=>$item['_id'],
        		'name'=>$item['name'],
        		'short_description'=>$item['short_description'],
        		'finder_id'=>$item['finder_id'],
        		'meal_type'=>$item['meal_type'],
        		'ratecard'=>[],
        		'image'=>''
        	];

 
        	$ratecard = Ratecard::where('service_id',(int)$service_data['service_id'])->where('finder_id',(int)$service_data['finder_id'])->where('type','trial')->get();

        	if($ratecard && count($ratecard) > 0){

        		$service_data['ratecard'] = $ratecard->toArray();
        	}

        	$services[] = $service_data;

        }

        $data = [];

        $data['service'] = $services;

	    return Response::json($data,200);

    }

    public function getLocationByRatecard($ratecard_id){

    	$data = [];
    	$data['location'] = [];

    	$ratecard = Ratecard::find((int)$ratecard_id);

		if($ratecard){

			$finder_id = (int)$ratecard['finder_id'];
			$service_id = (int)$ratecard['service_id'];

			$service = Service::select('_id','meal_type')->active()->find($service_id);

			if($service){

				$meal_type = $service['meal_type'];
 
				$finder = Finder::find((int)$finder_id)->toArray();

		        $all_locations = [];
		        $dinner_locations = [];
		        $lunch_locations = [];

		        if(isset($finder['locationtags']) && !empty($finder['locationtags']) && $finder['locationtags'] != "" && $finder['locationtags'] != null){

		        	$all_locations = Locationtag::whereIn('_id',$finder['locationtags'])->orderBy('name','asc')->lists('name');
		        }

		        if(isset($finder['lunchlocationtags']) && !empty($finder['lunchlocationtags']) && $finder['lunchlocationtags'] != "" && $finder['lunchlocationtags'] != null){

		        	$lunch_locations = Locationtag::whereIn('_id',$finder['lunchlocationtags'])->orderBy('name','asc')->lists('name');
		        }

		        if(isset($finder['dinnerlocationtags']) && !empty($finder['dinnerlocationtags']) && $finder['dinnerlocationtags'] != "" && $finder['dinnerlocationtags'] != null){

		        	$dinner_locations = Locationtag::whereIn('_id',$finder['dinnerlocationtags'])->orderBy('name','asc')->lists('name');
		        }

		        switch ($meal_type) {
		        	case 'lunch': $location = (!empty($lunch_locations)) ? $lunch_locations : $all_locations; break;
		        	case 'dinner': $location = (!empty($dinner_locations)) ? $dinner_locations : $all_locations; break;		        	
		        	default: $location = $all_locations; break;
		        }

		        $data['location'] = $location;
			}
		}

		return Response::json($data,200);
	}
	
	public function serviceDetailv1($finder_slug, $service_slug, $cache=true){

		// return date('Y-m-d', strtotime('day after tomorrow'));

		Log::info($_SERVER['REQUEST_URI']);
		$cache_key = "$finder_slug-$service_slug";

		$service_details = $cache ? Cache::tags('service_detail')->has($cache_key) : false;

		if(!$service_details){
			Log::info("Not cached");
			Finder::$withoutAppends = true;
			Service::$withoutAppends = true;
			
			$finder = Finder::active()->where('slug','=',$finder_slug)->whereNotIn('flags.state', ['closed', 'temporarily_shut'])
				->with(array('facilities'=>function($query){$query->select( 'name', 'finders');}))
				->with(array('reviews'=>function($query){$query->select('finder_id', 'customer', 'customer_id', 'rating', 'updated_at', 'description')->where('status','=','1')->orderBy('updated_at', 'DESC')->limit(3);}))
				->first(['title', 'contact', 'average_rating', 'total_rating_count', 'photos', 'coverimage', 'slug', 'trial','videos','playOverVideo']);

			if(!$finder){
				return Response::json(array('status'=>400, 'error_message'=>'Facility not active'), $this->error_status);
			}
	
			// $metropolis = new Metropolis();
	
			// $service_details_response =  $metropolis->vendorserviceDetail($finder['_id'], $service_slug);
	
			// if($service_details_response['status'] == 200){
			// 	$service_details = json_decode(json_encode($service_details_response['data']), true);
			// }
			
			$service_details = Service::active()->where('finder_id', $finder['_id'])->where('slug', $service_slug)->with('location')->with(array('ratecards'))->first(['name', 'contact', 'photos', 'lat', 'lon', 'calorie_burn', 'address', 'servicecategory_id', 'finder_id', 'location_id','trial','workoutsessionschedules']);
			// return $service_details;
			if(!$service_details){
				
				
// 				workoutsessionschedules
				return Response::json(array('status'=>400, 'error_message'=>'Service not active'), $this->error_status);
			
			};
			$service_details['lat'] = (string)$service_details['lat'];
			$service_details['lon'] = (string)$service_details['lon'];
			
			

			// return $service_details;
			$service_details = $service_details->toArray();

			/* $service_details['dynamic_pricing'] = ["title"=>"RUSH HOUR","sub_title"=>"RUSH HOUR","rush"=>["title"=>"RUSH HOUR","sub_title"=>"RUSH HOUR"],"non_rush"=>["title"=>"NON RUSH HOUR","sub_title"=>"NON RUSH HOUR"]];
			
			$this->utilities->getDayWs()
			array_values(array_filter([],function ($e) use()))
			$this->utilities->getPeakAndNonPeakPrice($service_details,$this->utilities->getPrimaryCategory(null,$service_details['_id'])); */

			// $service_details['title'] = $service_details['name'].' at '.$finder['title'];
			$service_details['title'] = preg_replace('/membership/i', 'Workout', $service_details['name']).' at '.$finder['title'];
			
			$service_details['finder_name'] = $finder['title'];
			

			if($service_details['servicecategory_id'] == 65){

				if($this->app_version < 5){
					$service_details['type'] = 'gym';
				}
				$service_details['pass_title'] = 'All Day Pass';
				$service_details['pass_description'] = 'Choose to workout at a suitable time between 6 am to 11 pm';


			}else{

				$service_details['type'] = 'studio';
				$service_details['pass_title'] = 'Quick Book';

			}
			
			$workout_session_ratecard = head(array_where($service_details['ratecards'], function($key, $value){
				if($value['type'] == 'workout session'){
					return $value;
				}
			}));
			unset($service_details['ratecards']);
			
			if(!$workout_session_ratecard){
				
				return Response::json(array('status'=>400, 'error_message'=>'Workout session ratecard not active'), $this->error_status);
			
			};
			
			$service_details['amount'] = (($workout_session_ratecard['special_price']!=0) ? $workout_session_ratecard['special_price'] : $workout_session_ratecard['price']);

			$service_details['price'] = "₹".$service_details['amount']." PER SESSION";

			$service_details['contact'] = [
				'address'=>''
			];
			$service_details['contact']['address'] = $service_details['address'];
	
			$service_details['facilities'] = $this->getFacilityImages(array_pluck($finder['facilities'], 'name'));
			
			$service_details['average_rating'] = isset($finder['average_rating']) ? round($finder['average_rating'], 1) : 0;
			
			$sericecategorysCalorieArr              =   Config::get('app.calorie_burn_categorywise');

			$category_id = $service_details['servicecategory_id'];
			
			if(!isset($service_details['calorie_burn']) || !isset($service_details['calorie_burn']['avg']) || $service_details['calorie_burn']['avg'] == 0){
				if(isset($sericecategorysCalorieArr[$category_id])){
					$service_details['calorie_burn'] = [
						'avg'=>$sericecategorysCalorieArr[$category_id]
					];
				}else{
					$service_details['calorie_burn'] = [
						'avg'=>500
					];
				}
				
			}
			
			$service_details['calorie_burn'] = "BURN ".$service_details['calorie_burn']['avg']." ".((isset($service_details['calorie_burn']['type']) && $service_details['calorie_burn']['type'] != "") ? strtoupper($service_details['calorie_burn']['type']) : "KCAL");

			$reviews = [];

			foreach($finder['reviews'] as $review){

				$review['posted_on'] = "Posted on ".date("jS M Y", strtotime($review['updated_at']));

				if(isset($review['customer']) && isset($review['customer']['name']) && isset($review['customer']['name'])!= ""){

					$review['reviewer'] = ucwords($review['customer']['name']);

				}else{
					
					$review['reviewer'] = "Fitternity User";
					
				}

				$review['rating'] = round($review['rating'], 1);		

				$review_data = array_only($review->toArray(), ['rating', 'description', 'posted_on', 'reviewer']);

				array_push($reviews, $review_data);

			}
	
			$service_details['reviews'] = [
				'count'=>isset($finder['total_rating_count']) ? $finder['total_rating_count'] : 0,
				'reviews'=>$reviews
			];

			function appendServiceImageDomain($url){
				return Config::get('app.service_gallery_path').$url;
			}

			$service_details['photos'] = [];
			
			foreach($finder['photos'] as $photo){
				if(isset($photo['servicetags']) && in_array($service_details['_id'], $photo['servicetags'])){
					array_unshift($service_details['photos'], 'https://b.fitn.in/f/g/full/'.$photo['url']);
				}else{
					array_push($service_details['photos'], 'https://b.fitn.in/f/g/full/'.$photo['url']);
				}
			}
			
			array_push($service_details['photos'], 'https://b.fitn.in/f/c/'.$finder['coverimage']);
			
			// $service_details['photos'] = isset($service_details['photos']) ? $service_details['photos'] : [];
			
			// $service_details['photos'] = array_map("appendServiceImageDomain",$service_details['photos']);
			
			$photos = $service_details['photos'];

			$service_details['photos'] = [
				'count'=>(count($service_details['photos']) > 1) ? (count($service_details['photos']) - 1) : 0,
				'urls'=>$photos
			];

			if(count($service_details['photos']['urls']) == 0){
				$service_details['photos']['urls'] = ['https://www.w3schools.com/howto/img_fjords.jpg'];
			}

			// $service_details['photos'] = array_pluck($service_details, 'url');
			
			$service_details['coordinates'] = [$service_details['lat'], $service_details['lon']];

			$service_details['finder'] = $finder;

			$service_details['workout_session_ratecard'] = $workout_session_ratecard;
			
			
			$findAr=$finder->toArray();
			
						if(isset($findAr)&&isset($findAr['playOverVideo'])&&$findAr['playOverVideo']!=-1&&isset($findAr['videos']) && is_array($findAr['videos']))
							{
									try {
											$povInd=$findAr['videos'][(int)$findAr['playOverVideo']];
											if(!isset($povInd['url']) || trim($povInd['url']) == ""){
													$povInd=null;
												}
												if(!empty($povInd))
														$service_details['playOverVideo']=$povInd;
								
												} catch (Exception $e) {
														$message = array(
																		'type'    => get_class($e),
																		'message' => $e->getMessage(),
																		'file'    => $e->getFile(),
																		'line'    => $e->getLine(),
																);
														Log::info(" playOverVideoError ".print_r($message,true));
													}
												}
						else unset($service_details['playOverVideo']);
			
			
			
			
			
			
			
			
			
			// return $service_details;
			// $service_details = array_except($service_details, array('gallery','videos','vendor_id','location_id','city_id','service','schedules','updated_at','created_at','traction','timings','trainers','offer_available','showOnFront','flags','remarks','trial_discount','rockbottom_price','threedays_trial','vip_trial','seo','batches','workout_tags','category', 'geometry', 'info', 'what_i_should_expect', 'what_i_should_carry', 'custom_location', 'name', 'workout_intensity', 'session_type', 'latlon_change', 'membership_end_date', 'membership_start_date', 'workout_results', 'vendor_name', 'location_name'));
			
			Cache::tags('service_detail')->put($cache_key,$service_details,Config::get('cache.cache_time'));
			
		}
		
		$service_details = Cache::tags('service_detail')->get($cache_key);

		$time = isset($_GET['time']) ? $_GET['time'] : null;
		$time_interval = null;
		$within_time = null;
		$requested_date = date('Y-m-d', time());
		$gym_start_time = [
			'hour'=>6,
			'min'=>0
		];

		$gym_end_time = [
			'hour'=>23,
			'min'=>0
		];
		$requested_date = date('Y-m-d', time());
		
		if(isset($_GET['date']) && $_GET['date'] != ''){

			$requested_date = $_GET['date'];

		}else{

			switch($time){
				case "within-4-hours":
					$within_time = 4*60*60;
					break;
				case "later-today":
					$time_interval = 4*60*60;
					break;
				case "tomorrow":
					$requested_date = date('Y-m-d', strtotime('+1 day', time()));
					break;
				case "day-after":
					$requested_date = date('Y-m-d', strtotime('+2 days', time()));
					break;
			}
		}
		// if((isset($_GET['source']) && $_GET['source'] == 'pps'))
		// {
		// 		$service_details['dynamic_pricing'] = ["title"=>"RUSH HOUR","sub_title"=>"RUSH HOUR","rush"=>["data"=>[],"title"=>"RUSH HOUR","sub_title"=>"RUSH HOUR"],"non_rush"=>["data"=>[],"title"=>"NON RUSH HOUR","sub_title"=>"NON RUSH HOUR"]];
				
		// 		 $p_np=$this->utilities->getAnySlotAvailablePNp($requested_date,$service_details);
		// 		 $service_cat=$this->utilities->getPrimaryCategory(null,$service_details['_id']);
		// 		if(!empty($p_np))
		// 		{
		// 			if(isset($p_np['peak']))
		// 				$service_details['dynamic_pricing']['rush']['sub_title']=$this->utilities->getRupeeForm($p_np['peak']);
		// 			else $service_details['dynamic_pricing']['rush']['sub_title']="";
		// 			if(isset($p_np['non_peak'])&&!empty($service_cat))
		// 			{
		// 				if($service_cat=='gym')
		// 					$service_details['dynamic_pricing']['non_rush']['sub_title']=$this->utilities->getRupeeForm($p_np['non_peak'])." ".((1-Config::get('app.non_peak_hours.studios.off'))*100)."% Off";
		// 				else
		// 					$service_details['dynamic_pricing']['non_rush']['sub_title']=$this->utilities->getRupeeForm($p_np['non_peak'])." ".((1-Config::get('app.non_peak_hours.studios.off'))*100)."% Off";
						
		// 			}
		// 			else $service_details['dynamic_pricing']['non_rush']['sub_title']="";
		// 		}
				
		// 		array_push($service_details['dynamic_pricing']['rush']['data'], ["name"=>"Moring","value"=>"6am -10am"]);
		// 		array_push($service_details['dynamic_pricing']['rush']['data'], ["name"=>"Evening","value"=>"6pm -10pm"]);
		// 		array_push($service_details['dynamic_pricing']['non_rush']['data'], ["name"=>"Moring","value"=>"10am -6pm"]);
		// 		array_push($service_details['dynamic_pricing']['non_rush']['data'], ["name"=>"Evening","value"=>"10pm -12am"]);			
		// }
		
						
		$schedule_data = [
			'service_id'=>$service_details['_id'],
			'requested_date'=>$requested_date,
			'time_interval'=>$time_interval,
			'date'=>$requested_date,
			'type'=>'workout_session',
			'within_time'=>$within_time
		];
		
		if($service_details['servicecategory_id'] == 65){
			$schedule_data['show_all'] = true;
		}
		
		if(isset($_GET['keyword']) && $_GET['keyword']){
			$schedule_data['recursive'] = true;
			// $service_details['gym_date_data'] = $this->getPPSAvailableDateTime($service_details, 3);
		}
		$service_details['gym_date_data'] = $this->getPPSAvailableDateTime($service_details, 3);
		unset($service_details['workoutsessionschedules']);
		$schedule = json_decode(json_encode($this->getScheduleByFinderService($schedule_data)->getData()));
		
		if($schedule->status != 200){
			return Response::json(array('status'=>400, 'error_message'=>'Booking not available'), $this->error_status);
		}
		
		$service_details['single_slot'] = false;
		if(isset($schedule->schedules) && count($schedule->schedules) > 0 && count(head($schedule->schedules)->slots)>0 && !(isset($service_details['finder']['trial']) && $service_details['finder']['trial'] == 'disable') && !(isset($service_details['trial']) && $service_details['trial'] == 'disable') && !(isset($service_details['workout_session_ratecard']['direct_payment_enable']) && $service_details['workout_session_ratecard']['direct_payment_enable'] == '0')){

			
			$service_details['next_session'] = "Next session at ".strtoupper(head($schedule->schedules)->slots[0]->start_time);
			$service_details['slots'] = (head($schedule->schedules)->slots);
			$service_details['total_sessions'] = count($service_details['slots'])." sessions";
			$service_details['schedule_date'] = date('d-m-Y', strtotime($schedule->available_date));

			if(isset($_GET['keyword']) && $_GET['keyword']){
				$service_details['page_index'] = $schedule->count -1;
				$service_details['schedule_date'] = date('d-m-Y', strtotime($schedule->available_date));
				$service_details['pass_title'] = $service_details['pass_title'].' ('.date('jS M', strtotime($schedule->available_date)).')';
				if($schedule->count > 3){
					$service_details['single_slot'] = false;
					$service_details['session_unavailable'] = true;
					$service_details['next_session'] = "Booking opens on".date('d-m-Y', strtotime($schedule->available_date));
					$service_details['slots'] = [];
					$service_details['total_sessions'] = "No sessions availabe";
					unset($service_details['pass_title']);
					unset($service_details['pass_description']);
					$service_details['page_index'] = 0;
				}
			}
			
			if(count($service_details['slots']) == 1){

				$service_details['single_slot'] = true;
				$service_details['slot_text'] = "Session Time ".strtoupper(head($schedule->schedules)->slots[0]->start_time);

			}

			$service_details['ratecard_id'] = head($schedule->schedules)->slots[0]->ratecard_id;

			$gym_start_time = [
				'hour'=>intval(date('G', strtotime(head($schedule->schedules)->slots[0]->start_time))),
				'min'=>intval(date('i', strtotime(head($schedule->schedules)->slots[0]->start_time))),
			];
	
			$gym_end_time = [
				'hour'=>intval(date('G', strtotime(head($schedule->schedules)->slots[count(head($schedule->schedules)->slots)-1]->start_time))),
				'min'=>intval(date('i', strtotime(head($schedule->schedules)->slots[count(head($schedule->schedules)->slots)-1]->start_time))),
			];

			if($service_details['servicecategory_id'] == 65){
				$service_details['pass_description'] = "Choose to workout at a suitable time between ".date('h:i a', strtotime($gym_start_time['hour'].':'.$gym_start_time['min'])).' to '.date('h:i a', strtotime($gym_end_time['hour'].':'.$gym_end_time['min']));
		
				if(date('z', time()) == date('z', strtotime($service_details['schedule_date'])) && intval(date('G', time())) >= $gym_start_time['hour']){
					$gym_start_time['hour'] = intval(date('G', strtotime('+30 minutes', time())));
					$gym_start_time['min'] = $gym_start_time['hour'] == (date('G', time())) ? 30 : 0;
				}

				if(date('z', time()) == date('z', strtotime($service_details['schedule_date'])) && intval(date('G', time())) >= $gym_end_time['hour']){
					$gym_end_time['hour'] = intval(date('G', strtotime('+30 minutes', time())));
					$gym_end_time['min'] = $gym_end_time['hour'] == (date('G', time())) ? 30 : 0;
				}

				$service_details['gym_display_time'] = "Select between ".date('h:i a', strtotime($gym_start_time['hour'].':'.$gym_start_time['min'])).' to '.date('h:i a', strtotime($gym_end_time['hour'].':'.$gym_end_time['min']));
				$service_details['next_session'] = "Next session at ".date('h:i a', strtotime($gym_start_time['hour'].':'.$gym_start_time['min']));
			}
		}else{

			$pps_slots=json_decode(json_encode($schedule), true);
			if(!empty($pps_slots)&&!empty($pps_slots['slots']))
			{
				$service_details['slots']=$pps_slots['slots'];
				$service_details['single_slot'] = (count($pps_slots['slots']) == 1) && count($pps_slots['slots'][0]['data']) == 1;
				$next_session_epoch = $pps_slots['slots'][0]['data'][0]['epoch_start_time'];
				$next_session_slot = $pps_slots['slots'][0]['data'][0]['start_time'];
				foreach($pps_slots['slots'] as $slot){
					foreach($slot['data'] as $x){
						if($x['epoch_start_time'] < $next_session_epoch){
							$next_session_epoch = $x['epoch_start_time'];
							$next_session_slot = $x['start_time'];
						}else{
							break;
						}
					}
				}
				$service_details['session_unavailable'] = false;
				$service_details['next_session'] = "Next session at $next_session_slot";
			}
			else {				
				$service_details['single_slot'] = false;
				$service_details['session_unavailable'] = true;
				$service_details['next_session'] = "OOPs sessions are currently unavailable. ";
				$service_details['slots'] = [];
				$service_details['total_sessions'] = "No sessions availabe";
				unset($service_details['pass_title']);
				unset($service_details['pass_description']);
				$service_details['page_index'] = 0;
			}
			// $service_details['next_session'] = "No sessions available";
		}
		unset($service_details['finder']);
		unset($service_details['workout_session_ratecard']);
		if(isset($service_details['session_unavailable']) && $service_details['session_unavailable']){
			$session_unavailable = new Sessionsunavailable();
			$session_unavailable->data = $schedule_data;
			$session_unavailable->url = $_SERVER['REQUEST_URI'];

			$session_unavailable->save();
		}
		
		$service_details['trial_active_weekdays']= null;
		$service_details['workoutsession_active_weekdays']= null;
		unset($service_details['trial_active_weekdays']);
		unset($service_details['workoutsession_active_weekdays']);
		$service_details['gym_start_time'] = $gym_start_time;
		$service_details['gym_end_time'] = $gym_end_time;

		$service_details['time_description'] = "Select between ".date('h:i a', strtotime($gym_start_time['hour'].':'.$gym_start_time['min']))." and ".date('h:i a', strtotime($gym_end_time['hour'].':'.$gym_end_time['min']));
		
		$data['service'] = $service_details;

		$data['bookmark'] = false;
		if($service_details['servicecategory_id'] != 65){
			$data['share_message_email'] = $data['share_message_text'] = "Check-out ".$service_details['title']." in ".$service_details['location']['name']." on Fitternity, India's biggest fitness discovery and booking platform. Pay-per-session available here - https://www.fitternity.com/".$finder_slug . " Download app to book -". Config::get('app.download_app_link');
		}else{
			$data['share_message_email'] = $data['share_message_text'] = "Check-out ".$service_details['finder_name']." in ".$service_details['location']['name']." on Fitternity, India's biggest fitness discovery and booking platform. Pay-per-session available here - https://www.fitternity.com/".$finder_slug . " Download app to book -". Config::get('app.download_app_link');
		}
		
		$data['pending_payment'] = $this->utilities->hasPendingPayments();
		if(!$data['pending_payment']){
			unset($data['pending_payment']);	
		}

		return Response::json(array('status'=>200, 'data'=> $data));

	}

	function getFacilityImages($available_facilities){
		
		$facility_images = [];

		$pay_per_session_facilities = ["parking","group-classes","sunday-open","locker-and-shower-facility"];

		$all_facilities = Facility::active()->whereIn('slug', $pay_per_session_facilities)->get(['name', 'images']);
		Log::info($all_facilities);
		// return $all_facilities;

		function append_base_url($x){
			return Config::get('app.facility_image_base_url').$x;

		}
		foreach($all_facilities as $facility){
			if(in_array($facility->name, $available_facilities)){
				$facility_images = array_merge($facility_images, array_map('append_base_url', $facility->images['yes']));
			}else{
				$facility_images = array_merge($facility_images, array_map('append_base_url', $facility->images['yes']));
			}	
		}

		return $facility_images;
	}

	public function workoutServiceCategorys($city='mumbai'){

		$not_included_ids = [161, 120, 170, 163, 168, 180, 184];

		$order = [65, 5, 19, 1, 123, 3, 4, 2, 114, 86];

		$included_ids = citywiseServiceCategoryIds(strtolower($city));

		$ordered_categories = [];
		$servicecategories	 = 	Servicecategory::active()->whereIn('_id', $included_ids)->where('parent_id', 0)->whereNotIn('slug', [null, ''])->whereNotIn('_id', $not_included_ids)->orderBy('name')->get(array('_id','name','slug'));
		if(count($servicecategories) > 0){

			foreach($servicecategories as &$category){
				$category['image'] = $category['slug'];
				if($category['slug'] == 'martial-arts'){
					$category['name'] = 'MMA & Kick-boxing';
				}
			}

			foreach($order as $_id){
				foreach($servicecategories as $x){
					if($x['_id'] == $_id){
						array_push($ordered_categories, $x);
					}
				}
			}

			$servicecategories = $servicecategories->toArray();
			array_unshift($ordered_categories, ['_id'=>0, 'name'=>'I want to explore all options', 'slug'=>'', 'image'=>'select-all-icon']);
		}
		
		$data  = [
			'status'=>200,
			'header'=>'Which fitness form do you want to try?',
			// 'all_message'=> "I want to explore all options",
			'category'=>$ordered_categories,
			'message'=>"",
			'base_url'=>"http://b.fitn.in/iconsv1/",
			'rebook_trials'=>[]
		];

		try{

			if($this->authorization){
				Log::info($this->authorization);
				$decoded = decode_customer_token();
				
				$customer_email = $decoded->customer->email;
				Service::$withoutAppends = true;
				Finder::$withoutAppends = true;
				Booktrial::$withoutAppends = true;
				Ratecard::$withoutAppends = true;

				$trials		=	Booktrial::where('customer_email', '=', $customer_email)
					->whereIn('booktrial_type', array('auto'))
					->where('type', 'workout-session')
					->with(array('service'=>function($query){ $query->where('status','1')->where('trial', '!=', 'disable')->with(array('ratecards'=>function($query){ $query->where('type', 'workout session')->select('service_id', 'price','special_price');}))->select('_id', 'slug');}))
					->with(array('finder'=>function($query){$query->where('status', '1')->whereNotIn('flags.state', ['closed', 'temporarily_shut'])->where('trial', '!=', 'disable')->select('_id', 'slug');}))
					// ->where('going_status_txt','!=','cancel')
					->orderBy('_id', 'desc')
					->get(['finder_id', 'service_id', 'finder_name', 'service_name']);
				
				$rebook_trials = [];
				$rebook_service_ids = [];
				foreach($trials as $trial){

					if(count($rebook_trials) < 3){
							if($trial['finder'] && $trial['service'] && count($trial['service']['ratecards']) && !in_array($trial['service_id'], $rebook_service_ids)){
								
								$trial['title'] = ucwords(preg_replace('/membership/i', 'Workout', $trial['service_name'])).' at '.$trial['finder_name'];

								$trial['amount'] = 'â‚¹'.($trial['service']['ratecards'][0]['special_price'] != 0 ? $trial['service']['ratecards'][0]['special_price'] : $trial['service']['ratecards'][0]['price']);
								$trial['service_slug'] = $trial['service']['slug'];
								$trial['finder_slug'] = $trial['finder']['slug'];
								
								array_push($rebook_trials, array_only($trial->toArray(), ['_id', 'title', 'amount', 'service_slug', 'finder_slug']));
								array_push($rebook_service_ids, $trial['service_id']);
							}
							
					}else{

						break;

					}

				}

				$data['rebook_trials'] = $rebook_trials;

			}			


		}catch(Exception $e){
			Log::info($e);
		}
		// return DB::getQueryLog();

		return $data;

	}

	public function timepreference(){
		$data = Input::json()->all();
		Log::info($data);
		$pay_persession_request = [
			"category"=>$data["category"],
			"location"=>isset($data["location"]) ? $data["location"] : array(),
			"keys"=>[
			  "name",
			  "id"
			]
		];
		$pay_per_session = payPerSession($pay_persession_request);
		$subheader = $pay_per_session["request"]["category_name"] . " sessions in " . $pay_per_session["request"]["location_name"];
		$timings = $pay_per_session["aggregations"]["time_range"];
		$tomorrow = date('l', strtotime(' +1 day'));
		$tomorrow_date = date('d-m-Y', strtotime(' +1 day'));
		$day_after = date('l', strtotime(' +2 day'));
		$day_after_date = date('d-m-Y', strtotime(' +1 day'));
		$days = array_fetch($pay_per_session["aggregations"]["days"],"name");
		foreach($timings as $key =>$timing){
			$timings[$key]["index"] = 0;
		}
		$indexofTomorrow = array_search($tomorrow,$days);
		$pay_per_session["aggregations"]["days"][$indexofTomorrow]["name"] = "Tomorrow";
		$pay_per_session["aggregations"]["days"][$indexofTomorrow]["slug"] = "tomorrow";
		$pay_per_session["aggregations"]["days"][$indexofTomorrow]["date"] = $tomorrow_date;
		$pay_per_session["aggregations"]["days"][$indexofTomorrow]["index"] = 1;
		$pay_per_session["aggregations"]["days"][$indexofTomorrow]["count"] = isset($pay_per_session["aggregations"]["days"][$indexofTomorrow]["count"]) ? $pay_per_session["aggregations"]["days"][$indexofTomorrow]["count"] : 0;
		$indexofday_after = array_search($day_after,$days);
		$pay_per_session["aggregations"]["days"][$indexofday_after]["name"] = "Day after";
		$pay_per_session["aggregations"]["days"][$indexofday_after]["slug"] = "day-after";
		$pay_per_session["aggregations"]["days"][$indexofday_after]["date"] = $day_after_date;
		$pay_per_session["aggregations"]["days"][$indexofday_after]["index"] = 2;
		$pay_per_session["aggregations"]["days"][$indexofday_after]["count"] = isset($pay_per_session["aggregations"]["days"][$indexofday_after]["count"]) ? $pay_per_session["aggregations"]["days"][$indexofday_after]["count"] : 0;
		array_push($timings, $pay_per_session["aggregations"]["days"][$indexofTomorrow]);
		array_push($timings, $pay_per_session["aggregations"]["days"][$indexofday_after]);
		$session_count = 0;
		foreach($timings as $timing){
			$session_count += $timing["count"];
		}
		return $data = array("header"=> "When would you like to workout?","subheader"=>$subheader, "categories" => $timings, "session_count"=> $session_count);
	}

	public function getPPSAvailableDateTime($service, $days){
		
		 $workoutsessionschedules = $service['workoutsessionschedules'];

		$available_dates = [];

		$weekdays_available = array_pluck($workoutsessionschedules, 'weekday');

		for($i = 0; $i < $days; $i++){
			
			$date = date('Y-m-d', strtotime("+$i days"));
			$weekday = strtolower(date( "l", strtotime($date)));
			if(in_array($weekday, $weekdays_available)){
				$weekdayslots = head(array_where($workoutsessionschedules, function($key, $value) use ($weekday){
					if($value['weekday'] == $weekday){
						return $value;
					}
				}));
				if(empty($weekdayslots) || empty($weekdayslots['slots'])){
					continue;
				}
				$first_slot = $weekdayslots['slots'][0];
				$last_slot = $weekdayslots['slots'][count($weekdayslots['slots'])-1];
				
				if(strtotime($date.''.$last_slot['start_time']) > time()){

					$data = ['date'=>date('d-m-Y', strtotime($date)), 'weekday'=>$weekday];
		
					$data['gym_start_time'] = [
						'hour'=>intval(date('G', strtotime($first_slot['start_time']))),
						'min'=>intval(date('i', strtotime($first_slot['start_time']))),
					];
			
					$data['gym_end_time'] = [
						'hour'=>intval(date('G', strtotime($last_slot['start_time']))),
						'min'=>intval(date('i', strtotime($last_slot['start_time']))),
					];
					
					if($i == 0 && intval(date('G', time())) >= $data['gym_start_time']['hour']){
						Log::info("asdas");
						$data['gym_start_time']['hour'] = intval(date('G', strtotime('+30 minutes', time())));
						$data['gym_start_time']['min'] = $data['gym_start_time']['hour'] == (date('G', time())) ? 30 : 0;
					}
	
					if($i == 0 && intval(date('G', time())) >= $data['gym_end_time']['hour']){
						$data['gym_end_time']['hour'] = intval(date('G', strtotime('+30 minutes', time())));
						$data['gym_end_time']['min'] = $data['gym_end_time']['hour'] == (date('G', time())) ? 30 : 0;
					}
					$data['time_description'] = "Select between ".date('h:i a', strtotime($data['gym_start_time']['hour'].':'.$data['gym_start_time']['min']))." and ".date('h:i a', strtotime($data['gym_end_time']['hour'].':'.$data['gym_end_time']['min']));
					array_push($available_dates, $data);
				}else if(!$i){
					$days++;
				}
			}


		
		}

		return $available_dates;
		
	}


}
