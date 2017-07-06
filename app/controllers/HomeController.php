<?php

//use Moa\API\Provider\ProviderInterface;


use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;

class HomeController extends BaseController {


    protected $api_url = "http://a1.fitternity.com/";
    // protected $api_url = "http://fitapi.com/";
    protected $debug = false;
    protected $client;


    public function __construct() {
        $this->initClient();
    }


    public function initClient($debug = false, $api_url = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $api_url = ($api_url) ? $api_url : $this->api_url;
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $api_url] );

    }


    public function saveUtmData(){


        $data   =   Input::json()->all();

        if(empty($data['entity_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - entity_id");
            return  Response::json($resp, 400);
        }

        if(empty($data['entity_type'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - entity_type");
            return  Response::json($resp, 400);
        }

        if(empty($data['utm'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - utm");
            return  Response::json($resp, 400);
        }

        $entity_id      =   trim($data['entity_id']);
        $entity_type    =   $data['entity_type'];
        $utm            =   $data['utm'];

        if($entity_type == 'booktrials'){
            $item 		= 	Booktrial::findOrFail(intval($entity_id));
        }

        if($entity_type == 'order'){
            $item 		= 	Order::findOrFail(intval($entity_id));
        }

        if($entity_type == 'captures') {
            $item = Capture::findOrFail($entity_id);
        }

        if($entity_type != "" && $entity_id != "" && isset($item) && !isset($item['utm'])){
            if($entity_type == 'booktrials'){
                $item       =   Booktrial::where('_id', intval($entity_id))->update(['utm' => $utm]);
            }elseif($entity_type == 'captures'){
                $item       =   Capture::where('_id', $entity_id)->update(['utm' => $utm]);
            }elseif($entity_type == 'order'){
                $item       =   Order::where('_id', intval($entity_id))->update(['utm' => $utm]);
            }
            $resp = array('status' => 200,'message' => "Added utm data");
            return Response::json($resp);
        }

    }


    public function getHomePageDatav2($city = 'mumbai',$cache = true){

        $home_by_city = $cache ? Cache::tags('home_by_city')->has($city) : false;

        if(!$home_by_city){
            $categorytags = $locations = $popular_finders =	$recent_blogs =	array();
            $citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));

            if(!$citydata){
                return $this->responseNotFound('City does not exist');
            }

            $city_name 		= 	$citydata['name'];
            $city_id		= 	(int) $citydata['_id'];

            $categorytags			= 		Findercategorytag::active()->whereIn('cities',array($city_id))->whereNotIn('_id', [41,37,39,43])->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
            $locations				= 		Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));

            $homepage 				= 		Homepage::where('city_id', '=', $city_id)->get()->first();
            if(!$homepage){
                return $this->responseNotFound('homepage does not exist');
            }

            $str_finder_ids 		= 		$homepage['gym_finders'].",".$homepage['yoga_finders'].",".$homepage['zumba_finders'];
            $finder_ids 			= 		array_map('intval', explode(",",$str_finder_ids));


            $footer_block1_ids 		= 		array_map('intval', explode(",", $homepage['footer_block1_ids'] ));
            $footer_block2_ids 		= 		array_map('intval', explode(",", $homepage['footer_block2_ids'] ));
            $footer_block3_ids 		= 		array_map('intval', explode(",", $homepage['footer_block3_ids'] ));
            $footer_block4_ids 		= 		array_map('intval', explode(",", $homepage['footer_block4_ids'] ));


            $footer_block1_finders 		=		Finder::active()->whereIn('_id', $footer_block1_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block2_finders 		=		Finder::active()->whereIn('_id', $footer_block2_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block3_finders 		=		Finder::active()->whereIn('_id', $footer_block3_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block4_finders 		=		Finder::active()->whereIn('_id', $footer_block4_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();

            // array_set($footer_finders,  'footer_block1_finders', $footer_block1_finders);
            // array_set($footer_finders,  'footer_block2_finders', $footer_block2_finders);
            // array_set($footer_finders,  'footer_block3_finders', $footer_block3_finders);
            // array_set($footer_finders,  'footer_block4_finders', $footer_block4_finders);

            // array_set($footer_finders,  'footer_block1_title', (isset($homepage['footer_block1_title']) && $homepage['footer_block1_title'] != '') ? $homepage['footer_block1_title'] : '');
            // array_set($footer_finders,  'footer_block2_title', (isset($homepage['footer_block2_title']) && $homepage['footer_block2_title'] != '') ? $homepage['footer_block2_title'] : '');
            // array_set($footer_finders,  'footer_block3_title', (isset($homepage['footer_block3_title']) && $homepage['footer_block3_title'] != '') ? $homepage['footer_block3_title'] : '');
            // array_set($footer_finders,  'footer_block4_title', (isset($homepage['footer_block4_title']) && $homepage['footer_block4_title'] != '') ? $homepage['footer_block4_title'] : '');

            //return Response::json($finder_ids);
            // $category_finders 		=		Finder::whereIn('_id', $finder_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            // ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            // ->remember(Config::get('app.cachetime'))
            // ->get(array('_id','average_rating','category_id','coverimage','finder_coverimage','slug','title','category','location_id','location','total_rating_count'))
            // ->groupBy('category.name')
            // ->toArray();

            // array_set($popular_finders,  'gyms', array_get($category_finders, 'gyms'));
            // array_set($popular_finders,  'yoga', array_get($category_finders, 'yoga'));
            // array_set($popular_finders,  'dance', array_get($category_finders, 'dance'));

            // $recent_blogs	 		= 		Blog::where('status', '=', '1')
            // ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            // ->with('categorytags')
            // ->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
            // ->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
            // ->orderBy('_id', 'desc')
            // ->remember(Config::get('app.cachetime'))
            // ->get(array('_id','author_id','category_id','categorytags','coverimage','finder_coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
            // ->take(4)->toArray();

            $collections 			= 	Findercollection::active()->where('city_id', '=', intval($city_id))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));

            $feature_service_ids 	= 		array_map('intval', explode(",", $homepage['service_ids'] ));
            $services 		=		Service::active()->whereIn('_id', $feature_service_ids)
                ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage','contact','average_rating');}))
                ->get();

            $feature_services = [];
            foreach ($services as $key => $value) {
                $item  	   	=  	(!is_array($value)) ? $value->toArray() : $value;
                $data = [
                    '_id' => $item['_id'],
                    'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
                    'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
                    'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
                    'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
                    'show_in_offers' => (isset($item['show_in_offers']) && $item['show_in_offers'] != '') ? strtolower($item['show_in_offers']) : "",
                    'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
                    'service_coverimage_thumb' => (isset($item['service_coverimage_thumb']) && $item['service_coverimage_thumb'] != '') ? strtolower($item['service_coverimage_thumb']) : "",
                    'location' => (isset($item['location']) && !empty($item['location'])) ? array_only( $item['location'] , array('_id', 'name', 'slug') ) : "",
                    'category' => (isset($item['category']) && !empty($item['category'])) ? array_only( $item['category'] , array('_id', 'name', 'slug') ) : "",
                    'subcategory' => (isset($item['subcategory']) && !empty($item['subcategory'])) ? array_only( $item['subcategory'] , array('_id', 'name', 'slug') ) : "",
                    'finder' => (isset($item['finder']) && !empty($item['finder'])) ? array_only( $item['finder'] , array('_id','title','slug','finder_coverimage','coverimage','contact','average_rating') ) : "",
                ];

                if(isset($item['show_in_offers']) && $item['show_in_offers'] == '1' && isset($item['service_ratecards']) && !empty($item['service_ratecards'])){
                    $ratecards = [];
                    foreach ($item['service_ratecards'] as $key => $v) {
                        if($v['featured_offer'] == '1'){
                            array_push($ratecards, $v);
                        }
                    }
                    $data['service_ratecards'] =  $ratecards;
                }else{
                    $data['service_ratecards'] =  (isset($item['service_ratecards']) && !empty($item['service_ratecards'])) ? $item['service_ratecards']  : [];
                }

                $data['service_trialschedules'] =  (isset($item['trialschedules']) && !empty($item['trialschedules'])) ? $item['trialschedules']  : [];

                array_push($feature_services, $data);
            }

            $homedata 				= 	array('categorytags' => $categorytags,
                'locations' => $locations,
                // 'popular_finders' => $popular_finders,
                // 'footer_finders' => $footer_finders,
                // 'recent_blogs' => $recent_blogs,
                'city_name' => $city_name,
                'city_id' => $city_id,
                'collections' => $collections,
                'feature_services' => $feature_services,
                'banner' => 'http://b.fitn.in/c/welcome/1.jpg'
            );

            Cache::tags('home_by_city')->put($city,$homedata,Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('home_by_city')->get($city));
    }


    public function getHomePageDatav3($city = 'mumbai',$cache = true){

        $home_by_city = $cache ? Cache::tags('home_by_city_v3')->has($city) : false;

        if(!$home_by_city){
            $categorytags = $locations = $popular_finders = $footer_finders = $recent_blogs =	array();
            $citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
            if(!$citydata){
                return $this->responseNotFound('City does not exist');
            }

            $city_name 		= 	$citydata['name'];
            $city_id		= 	(int) $citydata['_id'];

            $homepage 				= 		Homepage::where('city_id', '=', $city_id)->get()->first();
            if(!$homepage){
                return $this->responseNotFound('homepage does not exist');
            }

            $str_finder_ids 		= 		$homepage['gym_finders'].",".$homepage['yoga_finders'].",".$homepage['zumba_finders'];
            $finder_ids 			= 		array_map('intval', explode(",",$str_finder_ids));

            $footer_block1_ids 		= 		array_map('intval', explode(",", $homepage['footer_block1_ids'] ));
            $footer_block2_ids 		= 		array_map('intval', explode(",", $homepage['footer_block2_ids'] ));
            $footer_block3_ids 		= 		array_map('intval', explode(",", $homepage['footer_block3_ids'] ));
            $footer_block4_ids 		= 		array_map('intval', explode(",", $homepage['footer_block4_ids'] ));
            $footer_block5_ids 		= 		array_map('intval', explode(",", $homepage['footer_block5_ids'] ));
            $footer_block6_ids 		= 		array_map('intval', explode(",", $homepage['footer_block6_ids'] ));

            //return Response::json($finder_ids);
            $category_finders 		=		Finder::whereIn('_id', $finder_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
                ->groupBy('category.name')
                ->toArray();

            $footer_block1_finders 		=		Finder::active()->whereIn('_id', $footer_block1_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block2_finders 		=		Finder::active()->whereIn('_id', $footer_block2_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block3_finders 		=		Finder::active()->whereIn('_id', $footer_block3_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block4_finders 		=		Finder::active()->whereIn('_id', $footer_block4_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block5_finders 		=		Finder::active()->whereIn('_id', $footer_block5_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block6_finders 		=		Finder::active()->whereIn('_id', $footer_block6_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();

            array_set($popular_finders,  'gyms', array_get($category_finders, 'gyms'));
            array_set($popular_finders,  'yoga', array_get($category_finders, 'yoga'));
            array_set($popular_finders,  'dance', array_get($category_finders, 'dance'));

            array_set($footer_finders,  'footer_block1_finders', $footer_block1_finders);
            array_set($footer_finders,  'footer_block2_finders', $footer_block2_finders);
            array_set($footer_finders,  'footer_block3_finders', $footer_block3_finders);
            array_set($footer_finders,  'footer_block4_finders', $footer_block4_finders);
            array_set($footer_finders,  'footer_block5_finders', $footer_block5_finders);
            array_set($footer_finders,  'footer_block6_finders', $footer_block6_finders);

            array_set($footer_finders,  'footer_block1_title', (isset($homepage['footer_block1_title']) && $homepage['footer_block1_title'] != '') ? $homepage['footer_block1_title'] : '');
            array_set($footer_finders,  'footer_block2_title', (isset($homepage['footer_block2_title']) && $homepage['footer_block2_title'] != '') ? $homepage['footer_block2_title'] : '');
            array_set($footer_finders,  'footer_block3_title', (isset($homepage['footer_block3_title']) && $homepage['footer_block3_title'] != '') ? $homepage['footer_block3_title'] : '');
            array_set($footer_finders,  'footer_block4_title', (isset($homepage['footer_block4_title']) && $homepage['footer_block4_title'] != '') ? $homepage['footer_block4_title'] : '');
            array_set($footer_finders,  'footer_block5_title', (isset($homepage['footer_block5_title']) && $homepage['footer_block5_title'] != '') ? $homepage['footer_block5_title'] : '');
            array_set($footer_finders,  'footer_block6_title', (isset($homepage['footer_block6_title']) && $homepage['footer_block6_title'] != '') ? $homepage['footer_block6_title'] : '');


            $recent_blogs	 		= 		Blog::where('status', '=', '1')
                ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with('categorytags')
                ->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
                ->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
                ->orderBy('_id', 'desc')
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
                ->take(4)->toArray();

            $collections 			= 	Findercollection::active()->where('city_id', '=', intval($citydata['_id']))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));

            $homedata 	= 	array(
                'popular_finders' => $popular_finders,
                'footer_finders' => $footer_finders,
                'recent_blogs' => $recent_blogs,
                'city_name' => $city_name,
                'city_id' => $city_id,
                'collections' => $collections

            );

            Cache::tags('home_by_city_v3')->put($city, $homedata, Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('home_by_city_v3')->get($city));
    }




    public function getHomePageDatav4($city = 'mumbai',$cache = true){

        $home_by_city = $cache ? Cache::tags('home_by_city_v4')->has($city) : false;

        if(!$home_by_city){
            $categorytags = $locations = $popular_finders = $footer_finders = $recent_blogs =	array();
            $citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
            if(!$citydata){
                return $this->responseNotFound('City does not exist');
            }

            $city_name 		= 	$citydata['name'];
            $city_id		= 	(int) $citydata['_id'];

            $homepage 				= 		Homepage::where('city_id', '=', $city_id)->get()->first();
            if(!$homepage){
                return $this->responseNotFound('homepage does not exist');
            }

            $str_finder_ids 		= 		$homepage['gym_finders'].",".$homepage['yoga_finders'].",".$homepage['zumba_finders'];
            $finder_ids 			= 		array_map('intval', explode(",",$str_finder_ids));


            //return Response::json($finder_ids);
            $category_finders 		=		Finder::whereIn('_id', $finder_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
                ->groupBy('category.name')
                ->toArray();


            $gym_finders_label 			= 		(isset($homepage['gym_finders_label'])) ? $homepage['gym_finders_label'] : "";
            $yoga_finders_label 		= 		(isset($homepage['yoga_finders_label'])) ? $homepage['yoga_finders_label'] : "";
            $dance_finders_label 		= 		(isset($homepage['dance_finders_label'])) ? $homepage['dance_finders_label'] : "";

            array_set($popular_finders,  'gym_finders_label', $gym_finders_label);
            array_set($popular_finders,  'yoga_finders_label', $yoga_finders_label);
            array_set($popular_finders,  'dance_finders_label', $dance_finders_label);

            $gym_finders_ids 		= 		array_map('intval', explode(",", $homepage['gym_finders'] ));
            $yoga_finders_ids 		= 		array_map('intval', explode(",", $homepage['yoga_finders'] ));
            $zumba_finders_ids 		= 		array_map('intval', explode(",", $homepage['zumba_finders'] ));


            $gyms_finders 		=		Finder::active()->whereIn('_id', $gym_finders_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->remember(Config::get('app.cachetime'))->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
                ->toArray();
            $yoga_finders 		=		Finder::active()->whereIn('_id', $yoga_finders_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->remember(Config::get('app.cachetime'))->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
                ->toArray();
            $dance_finders 		=		Finder::active()->whereIn('_id', $zumba_finders_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->remember(Config::get('app.cachetime'))->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
                ->toArray();

            array_set($popular_finders,  'gyms', $gyms_finders);
            array_set($popular_finders,  'yoga', $yoga_finders);
            array_set($popular_finders,  'dance', $dance_finders);


            $footer_block1_ids 		= 		array_map('intval', explode(",", $homepage['footer_block1_ids'] ));
            $footer_block2_ids 		= 		array_map('intval', explode(",", $homepage['footer_block2_ids'] ));
            $footer_block3_ids 		= 		array_map('intval', explode(",", $homepage['footer_block3_ids'] ));
            $footer_block4_ids 		= 		array_map('intval', explode(",", $homepage['footer_block4_ids'] ));
            $footer_block5_ids 		= 		array_map('intval', explode(",", $homepage['footer_block5_ids'] ));
            $footer_block6_ids 		= 		array_map('intval', explode(",", $homepage['footer_block6_ids'] ));

            $footer_block1_finders 		=		Finder::active()->whereIn('_id', $footer_block1_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block2_finders 		=		Finder::active()->whereIn('_id', $footer_block2_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block3_finders 		=		Finder::active()->whereIn('_id', $footer_block3_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block4_finders 		=		Finder::active()->whereIn('_id', $footer_block4_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block5_finders 		=		Finder::active()->whereIn('_id', $footer_block5_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block6_finders 		=		Finder::active()->whereIn('_id', $footer_block6_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            array_set($footer_finders,  'footer_block1_finders', $footer_block1_finders);
            array_set($footer_finders,  'footer_block2_finders', $footer_block2_finders);
            array_set($footer_finders,  'footer_block3_finders', $footer_block3_finders);
            array_set($footer_finders,  'footer_block4_finders', $footer_block4_finders);
            array_set($footer_finders,  'footer_block5_finders', $footer_block5_finders);
            array_set($footer_finders,  'footer_block6_finders', $footer_block6_finders);

            array_set($footer_finders,  'footer_block1_title', (isset($homepage['footer_block1_title']) && $homepage['footer_block1_title'] != '') ? $homepage['footer_block1_title'] : '');
            array_set($footer_finders,  'footer_block2_title', (isset($homepage['footer_block2_title']) && $homepage['footer_block2_title'] != '') ? $homepage['footer_block2_title'] : '');
            array_set($footer_finders,  'footer_block3_title', (isset($homepage['footer_block3_title']) && $homepage['footer_block3_title'] != '') ? $homepage['footer_block3_title'] : '');
            array_set($footer_finders,  'footer_block4_title', (isset($homepage['footer_block4_title']) && $homepage['footer_block4_title'] != '') ? $homepage['footer_block4_title'] : '');
            array_set($footer_finders,  'footer_block5_title', (isset($homepage['footer_block5_title']) && $homepage['footer_block5_title'] != '') ? $homepage['footer_block5_title'] : '');
            array_set($footer_finders,  'footer_block6_title', (isset($homepage['footer_block6_title']) && $homepage['footer_block6_title'] != '') ? $homepage['footer_block6_title'] : '');


            $recent_blogs	 		= 		Blog::where('status', '=', '1')
                ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with('categorytags')
                ->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
                ->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
                ->orderBy('_id', 'desc')
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
                ->take(4)->toArray();

            $collections 			= 	Findercollection::active()->where('city_id', '=', intval($citydata['_id']))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));

            $homedata 	= 	array(
                'popular_finders' => $popular_finders,
                'footer_finders' => $footer_finders,
                'recent_blogs' => $recent_blogs,
                'city_name' => $city_name,
                'city_id' => $city_id,
                'collections' => $collections

            );

            Cache::tags('home_by_city_v4')->put($city, $homedata, Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('home_by_city_v4')->get($city));
    }


    public function getSuccessMsg($type, $id){

        $customer_id = "";
        $jwt_token = Request::header('Authorization');

        Log::info("getSuccessMsg".$jwt_token);

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }

        $type       =   strtolower(trim($type));
        $order_type    = "";

        if($type != "" && $id != ""){

            $booktrialItemArr   =   ["personaltrainertrial","manualtrial","manualautotrial","booktrialfree"];
            $orderItemArr       =   ["healthytiffintrial","membershipwithpg","membershipwithoutpg","healthytiffinmembership","personaltrainermembership","booktrial","workoutsession"];
            $captureItemArr     =   ["manualmembership"];

            $itemData           =   [];
            
            if (in_array($type, $booktrialItemArr)){

                $itemData       =   Booktrial::with('finder')->find(intval($id));

                $dates = array('start_date', 'start_date_starttime', 'schedule_date', 'schedule_date_time', 'followup_date', 'followup_date_time','missedcall_date','customofferorder_expiry_date','auto_followup_date');

                foreach ($dates as $key => $value){
                    if(isset($itemData[$value]) && $itemData[$value]==''){
                        $itemData->unset($value);
                    }
                }

                $itemData       =   $itemData->toArray();

                if($type == 'manualtrial'){
                    if(isset($itemData['finder']['manual_trial_auto']) && $itemData['finder']['manual_trial_auto'] == "1"){
                        $type = "manualautotrial";
                    }
                }

                $order_type = "booktrial_id";
            }

            if (in_array($type, $orderItemArr)) {

                $itemData = Order::with('finder')->find(intval($id));

                $dates = array('followup_date','last_called_date','preferred_starting_date', 'called_at','subscription_start','start_date','start_date_starttime','end_date', 'order_confirmation_customer');

                foreach ($dates as $key => $value){
                    if(isset($itemData[$value]) && $itemData[$value]==''){
                        $itemData->unset($value);
                    }
                }

                $itemData       =   $itemData->toArray();

                $order_type = "order_id";

            }

            if (in_array($type, $captureItemArr)) {
                $itemData = Capture::with('finder')->find($id)->toArray();

                $order_type = "capture_id";
            }

            $item           =   array_except($itemData, ['finder']);
            $finder_name    =   (isset($itemData) && isset($itemData['finder']) && isset($itemData['finder']['title'])) ? ucwords($itemData['finder']['title']) : "";
            $service_name    =   (isset($itemData) && isset($itemData['service_name'])) ? ucwords($itemData['service_name']) : "";
            $schedule_date  =   (isset($itemData['schedule_date']) && $itemData['schedule_date'] != "") ? date(' jS F\, Y \(l\) ', strtotime($itemData['schedule_date'])) : "-";
            $schedule_slot  =   (isset($itemData['schedule_slot']) && $itemData['schedule_slot'] != "") ? $itemData['schedule_slot'] : "-";
            $service_duration = (isset($itemData['service_duration_purchase']) && $itemData['service_duration_purchase'] != "") ? $itemData['service_duration_purchase'] : "-";
            $preferred_starting_date = (isset($itemData['preferred_starting_date'])) ? $itemData['preferred_starting_date'] : "";

            $header     =   "Congratulations!";
            $note       =   "Note: If you face any issues or need assistance for the  session - please call us on 022-61222222 and we will resolve it immediately";
            $icon_path  =   "https://b.fitn.in/iconsv1/success-pages/";
            $show_invite = false;
            $id_for_invite = (int) $id;
            $end_point = "";
            $item_description = "";

            switch ($type) {

                case 'booktrialfree':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider.Make sure you buy memberships through us for lowest price guarantee.Below are the details of your transaction.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Manage this booking through your User Profile'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Flash the code at the studio to access your session'],
                        ['icon'=>$icon_path.'low-price.png','text'=>'Get lowest price guarantee to buy membership'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Choose exciting rewards when you buy'],
                    ];
                    $show_invite = true;
                    $end_point = "invitefortrial";
                    $item_description = "$finder_name, $finder_location Trial Session <b>(FREE VIA FITTERNITY)</b>";
                    $header = "Booking Confirmed";
                    break;

                case 'booktrial':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider.Make sure you buy memberships through us for lowest price guarantee.Below are the details of your transaction.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Manage this booking through your User Profile'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Flash the code at the studio to access your session'],
                        ['icon'=>$icon_path.'low-price.png','text'=>'Get lowest price guarantee to buy membership'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Choose exciting rewards when you buy'],
                    ];
                    $show_invite = true;
                    $id_for_invite = (int) $item['booktrial_id'];
                    $end_point = "invitefortrial";
                    $item_description = "$finder_name, $finder_location Trial Session";
                    $header = "Booking Confirmed";
                    break;
                case 'workoutsession':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider. Make sure you buy a membership through Fitternity to enjoy lowest price guarantee!";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Manage this booking through your User Profile'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Flash the code at the studio to access your session'],
                        ['icon'=>$icon_path.'attend-workout.png','text'=>'Attend your workout'],
                    ];
                    $show_invite = true;
                    $id_for_invite = (int) $item['booktrial_id'];
                    $end_point = "invitefortrial";
                    $item_description = "Single Workout Session";
                    $header = "Booking Confirmed";
                    break;
                case 'personaltrainertrial':
                    $subline = "Your Session is booked. Hope you and your buddy have great workout.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'book-appointment.png','text'=>'Fitternity will get in touch with you to book the appointment'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Manage this booking through your User Profile'],
                        ['icon'=>$icon_path.'attend-workout.png','text'=>'You attend the trial with the trainer basis the appointment'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Get lowest price guarantee & Rewards on purchase'],
                    ];
                    $end_point = "";
                    $item_description = "Personal Trialner Trial Session";
                    $header = "Booking Confirmed";
                    break;
                case 'manualtrial':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider.We’ll get back to you shortly with your appointment details.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'book-appointment.png','text'=>'Fitternity will get in touch with you to book the appointment'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Manage this booking through your User Profile'],
                        ['icon'=>$icon_path.'attend-workout.png','text'=>'You attend the trial basis the appointment'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Get lowest price guarantee & Rewards on purchase'],
                    ];
                    $end_point = "";
                    $item_description = "Trial Session";
                    $header = "Request Confirmed";
                    break;
                case 'manualautotrial':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider.We’ll get back to you shortly with your appointment details.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'book-appointment.png','text'=>"$finder_name will get in touch with you to book the appointment"],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Manage this booking through your User Profile'],
                        ['icon'=>$icon_path.'attend-workout.png','text'=>'You attend the trial basis the appointment'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Get lowest price guarantee & Rewards on purchase'],
                    ];
                    $end_point = "";
                    $item_description = "Trial Session";
                    $header = "Request Confirmed";
                    break;
                case 'healthytiffintrial':
                    $subline = "Your Trial request at $finder_name has been received. Please expect a revert shortly.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'Subscription details are shared on an email to you'],
                        ['icon'=>$icon_path.'get-details.png','text'=> $finder_name.' will get in touch with you'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'Your meal will be delivered basis the specifications'],
                    ];
                    $end_point = "";
                    $item_description = "Healthy Tiffin Trial";
                    $header = "Booking Confirmed";
                    break;
                case 'membershipwithpg':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider. Make sure you upgrade / renew your membership through Fitternity to enjoy lowest price guarantee!";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'Subscription code & membership details shared on email'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Claim your selected reward through your User Profile'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Flash the code at the studio & kickstart your fitness journey.'],
                    ];
                    $show_invite = true;
                    $end_point = "inviteformembership";
                    $item_description = "$service_name $service_duration";
                    $header = "Booking Confirmed";
                    break;
                case 'membershipwithoutpg':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider. Make sure you upgrade / renew your membership through Fitternity to enjoy lowest price guarantee!";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'Subscription code & membership details shared on email'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Access your Profile on Fitternity to keep track of your membership'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Flash the code at the studio & kickstart your fitness journey.'],
                    ];
                    $show_invite = true;
                    $end_point = "inviteformembership";
                    $item_description = "$service_name $service_duration";
                    $header = "Booking Confirmed";
                    break;
                case 'manualmembership':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider.We’ll get back to you shortly with your appointment details.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Fitternity will get in touch with you to facilitate the membership purchase'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Choose exciting rewards on purchasing the membership'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'On purchase - Subscription code & membership details shared'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Flash the code at the studio & kickstart your fitness journey.'],
                    ];
                    $end_point = "";
                    $item_description = (isset($item['membership']) && $item['membership'] != "") ? $item['membership'] : "";
                    $header = "Request Confirmed";
                    break;
                case 'healthytiffinmembership':
                    $subline = "Your Membership request at $finder_name for $service_name has been received. Please expect a revert shortly.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'Subscription details are shared on an email to you'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Claim your selected reward through your User Profile'],
                        ['icon'=>$icon_path.'get-details.png','text'=> $finder_name.' will get in touch with you'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'Your meal will be delivered basis the specifications'],
                    ];
                    $end_point = "";
                    $item_description = "$service_name";
                    $header = "Booking Confirmed";
                    break;
                case 'personaltrainermembership':
                    $subline = "Your Membership request with $finder_name is captured. ";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'book-appointment.png','text'=>'Fitternity will get in touch with you to facilitate the purchase'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'When you buy the membership details will be shared'],
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'On starting date the trainer will reach your location'],
                    ];
                    $end_point = "";
                    $item_description = "Personal Trainer Membership";
                    $header = "Booking Confirmed";
                    break;
                default :
                    $subline = "Your Session has been scheduled";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Manage this booking through your User Profile'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Flash the code at the studio to access your session'],
                        ['icon'=>$icon_path.'low-price.png','text'=>'Get lowest price guarantee to buy membership'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Choose exciting rewards when you buy'],
                    ];
                    $end_point = "";
                    $item_description = "Trial Session";
                    break;
            }

            if(count($item) < 0){
                $item = null;
            }

            $popup_message = "";
            if(($type == "booktrial" || $type == "healthytiffintrial") && isset($itemData['amount_customer']) && $itemData['amount_customer'] > 0){

                $amount_20_percent = (int)($itemData['amount_customer']*20/100);
                $popup_message = "Rs ".$amount_20_percent." FitCash has been added to your wallet";
            }

            if(isset($item['myreward_id']) && $item['myreward_id'] != "" && $item['myreward_id'] != 0){
                $show_invite = false;
            }

            $fitcash_plus = 0;

            if($customer_id != ""){

                $customer = Customer::find($customer_id);

                if(isset($customer)){

                    if(isset($customer->demonetisation)){

                        $fitcash_plus = \Wallet::active()->where('customer_id',$customer->_id)->where('balance','>',0)->sum('balance');

                    }else{

                        $customer_wallet = Customerwallet::where('customer_id',$customer->_id)
                        ->where('amount','!=',0)
                        ->orderBy('_id', 'DESC')
                        ->first();

                        if($customer_wallet){
                            $fitcash_plus = (isset($customer_wallet['balance_fitcash_plus']) && $customer_wallet['balance_fitcash_plus'] != "") ? (int) $customer_wallet['balance_fitcash_plus'] : 0 ;
                        }
                    }

                }
            }

            $near_by_vendor = [];

            if(isset($itemData['finder']) && isset($itemData['finder']['lat']) && isset($itemData['finder']['lon'])){

                $lat = $itemData['finder']['lat'];
                $lon = $itemData['finder']['lon'];

                $near_by_vendor_request = [
                    "offset" => 0,
                    "limit" => 6,
                    "radius" => "3km",
                    "category"=>"",
                    "lat"=>$lat,
                    "lon"=>$lon,
                    "keys"=>[
                      "average_rating",
                      // "business_type",
                      // "categorytags",
                      // "commercial_type",
                      "contact",
                      "coverimage",
                      // "distance",
                      // "facilities",
                      // "geolocation",
                      "location",
                      // "locationtags",
                      "multiaddress",
                      // "offer_available",
                      "offerings",
                      // "photos",
                      // "servicelist",
                      "slug",
                      "title",
                      "id",
                      // "total_rating_count",
                      // "vendor_type"
                    ]
                ];

                $near_by_vendor = $this->geoLocationFinder($near_by_vendor_request);

                $near_by_vendor_message = "People with similar prefrences";

                $swimming_finder_request = [
                    "offset" => 0,
                    "limit" => 6,
                    "radius" => "3km",
                    "category"=>"swimming",
                    "lat"=>$lat,
                    "lon"=>$lon,
                    "keys"=>[
                      // "average_rating",
                      // "business_type",
                      // "categorytags",
                      // "commercial_type",
                      // "contact",
                      "coverimage",
                      // "distance",
                      // "facilities",
                      // "geolocation",
                      "location",
                      // "locationtags",
                      // "multiaddress",
                      // "offer_available",
                      // "offerings",
                      // "photos",
                      // "servicelist",
                      "slug",
                      "title",
                      "id",
                      // "total_rating_count",
                      // "vendor_type"
                    ]
                ];

                $swimming_finder = $this->geoLocationFinder($swimming_finder_request);

                $healthy_tiffins_finder_request = [
                    "offset" => 0,
                    "limit" => 6,
                    "radius" => "3km",
                    "category"=>"healthy-tiffins",
                    "lat"=>$lat,
                    "lon"=>$lon,
                    "keys"=>[
                      // "average_rating",
                      // "business_type",
                      // "categorytags",
                      // "commercial_type",
                      // "contact",
                      "coverimage",
                      // "distance",
                      // "facilities",
                      // "geolocation",
                      "location",
                      // "locationtags",
                      // "multiaddress",
                      // "offer_available",
                      // "offerings",
                      // "photos",
                      // "servicelist",
                      "slug",
                      "title",
                      "id",
                      // "total_rating_count",
                      // "vendor_type"
                    ]
                ];

                $healthy_tiffins_finder = $this->geoLocationFinder($healthy_tiffins_finder_request);

            }

            $booking_details = [];

            $booking_details_data = [
                "booking_id" => ['field'=>'SUBSCRIPTION CODE','value'=>(string)$item['_id'],'position'=>0],
                "description" => ['field'=>'DESCRIPTION','value'=>$item_description,'position'=>1],
                "start_date" => ['field'=>'START DATE','value'=>'','position'=>2],
                "start_time" => ['field'=>'START TIME','value'=>'','position'=>3],
                "location" => ['field'=>'ADDRESS','value'=>'','position'=>4],
                "price" => ['field'=>'PRICE','value'=>'Free Via Fitternity','position'=>5],
            ];

            if(isset($item['start_date']) && $item['start_date'] != ""){
                $booking_details_data['start_date']['value'] = date('d-m-Y',strtotime($item['start_date']));
            }

            if(isset($item['schedule_date']) && $item['schedule_date'] != ""){
                $booking_details_data['start_date']['value'] = date('d-m-Y',strtotime($item['schedule_date']));
            }

            if(isset($item['preferred_starting_date']) && $item['preferred_starting_date'] != ""){
                $booking_details_data['start_date']['value'] = date('d-m-Y',strtotime($item['preferred_starting_date']));
            }

            if(isset($item['start_time']) && $item['start_time'] != ""){
                $booking_details_data['start_time']['value'] = strtoupper($item['start_time']);
            }

            if(isset($item['schedule_slot_start_time']) && $item['schedule_slot_start_time'] != ""){
                $booking_details_data['start_time']['value'] = strtoupper($item['schedule_slot_start_time']);
            }

            if(isset($item['amount']) && $item['amount'] != ""){
                $booking_details_data['price']['value'] = "Rs. ".(string)$item['amount'];
            }

            if(isset($item['amount_finder']) && $item['amount_finder'] != ""){
                $booking_details_data['price']['value']= "Rs. ".(string)$item['amount_finder'];
            }

            if(isset($item['finder_address']) && $item['finder_address'] != ""){
                $booking_details_data['location']['value'] = (string)$item['finder_address'];
            }

            if(in_array($type, ['manualtrial','manualautotrial','manualmembership'])){
                $booking_details_data["start_date"]["field"] = "PREFERRED DATE";
                $booking_details_data["start_time"]["field"] = "PREFERRED TIME";
                $booking_details_data["price"]["value"] = "-";
            }

            if(in_array($type, ['booktrialfree','booktrial','workoutsession'])){
                $booking_details_data["start_date"]["field"] = "DATE";
                $booking_details_data["start_time"]["field"] = "TIME";
            }

            foreach ($booking_details_data as $key => $value) {

                $booking_details[$value['position']] = ['field'=>$value['field'],'value'=>$value['value']];
            }

            $poc = null;

            if(isset($item['finder_poc_for_customer_name']) && $item['finder_poc_for_customer_name'] != ""){
                $poc['name'] = $item['finder_poc_for_customer_name'];
            }

            if(isset($item['finder_poc_for_customer_no']) && $item['finder_poc_for_customer_no'] != ""){
                $poc['number'] = $item['finder_poc_for_customer_no'];
            }

            $city_name = "";

            if(isset($item['city_id']) && $item['city_id'] != ""){

                $city = City::find((int)$item['city_id']);

                if($city){
                    $city_name  = ucwords($city->name);
                }
            }

            $fitcash_vendor = [
                "title"=>"title",
                "description"=>"description",
                "image"=>"image",
                "vendor"=>[
                    [ 
                        "image"=>"image",
                        "title"=>"Book Swiming Sessions",
                        "details"=>[
                            ['field'=>'Avg. Calorie Burn','value'=>'750 KCAL'],
                            ['field'=>'Avg. Price Per Session','value'=>'Rs 200'],
                            ['field'=>'Current Providers in area','value'=>'2 Providers']
                        ],
                        "category"=>"swimming",
                        "city"=>$city_name,
                        "region"=>(isset($item['finder_location']) && $item['finder_location'] != "") ? [$item['finder_location']] : []
                    ],
                    [ 
                        "image"=>"image",
                        "title"=>"Book Healthy Tiffin",
                        "details"=>[
                            ['field'=>'Avg. Calorie Intake','value'=>'750 KCAL'],
                            ['field'=>'Avg. Price Per Tiffin','value'=>'Rs 200'],
                            ['field'=>'Current Providers in area','value'=>'2 Providers']
                        ],
                        "category"=>"healthy tiffins",
                        "city"=>$city_name,
                        "region"=>(isset($item['finder_location']) && $item['finder_location'] != "") ? [$item['finder_location']] : []
                    ],
                    [ 
                        "image"=>"image",
                        "title"=>"Buy Online Diet Plans",
                        "details"=>[
                            ['field'=>'Avg. Plan Duration','value'=>'1 Month'],
                            ['field'=>'Avg. Price Per Plan','value'=>'Rs 200'],
                            ['field'=>'Primary Function','value'=>'Weight Loss']
                        ],
                        "category"=>"diet plan",
                        "city"=>$city_name,
                        "region"=>(isset($item['finder_location']) && $item['finder_location'] != "") ? [$item['finder_location']] : []
                    ],
                ]
            ];

            $invite = [
                "description"=>"<b>Did you know?</b><br/>Working out with a friend can improve your performance by 87%",
                "message"=>"Awesome! Lets invite your buddies to join you!",
                "confirm"=>"Now you have invited your workout buddies,you are sure to have a lot of fun",
                'show_invite' => $show_invite,
                'id_for_invite' => $id_for_invite,
                'end_point'=> $end_point,
                'type' => $type
            ];

            $conclusion = [
                "title"=>"Done",
                "description"=>"Hope you had a great experience. \n If you need any help, you can reach out to us on on below details",
                "email"=>"info@fitternity.com",
                "phone"=>"022-61222222"
            ];

            $feedback = [
                'reason'=>[
                    'Choice of fitness options available',
                    'Information provided about the gym/studio',
                    'Transaction & Booking process',
                    'Payment/Cashback/Offer related',
                    'Any other issue'
                ],
                'threshold_value'=>6
            ];

            $reward_details = null;

            if(isset($item['reward_ids']) && !empty($item['reward_ids'])){

                $reward_id = (int)$item['reward_ids'][0];

                $reward = Reward::select('_id','title','reward_type')->find($reward_id);

                $reward_details = [
                    'reward_id' => $reward->_id,
                    'reward_type' => $reward->reward_type,
                    'finder_name'=> (isset($item['finder_name']) && $item['finder_name'] != "") ? $item['finder_name'] : ""
                ];
            }

            $resp = [
                'status'    =>  200,
                'item'      =>  null,
                'message'   =>   [
                    'header'    =>  $header,
                    'subline'   =>  $subline,
                    'steps'     =>  $steps,
                    'note'      =>  $note
                ],
                'popup_message' => $popup_message,
                'show_invite' => $show_invite,
                'id_for_invite' => $id_for_invite,
                'end_point'=> $end_point,
                'type' => $type,
                'current_balance'=> $fitcash_plus,
                'near_by_vendor'=>$near_by_vendor,
                'booking_details'=>$booking_details,
                'fitcash_vendor'=>$fitcash_vendor,
                'poc'=>$poc,
                'invite'=>$invite,
                'conclusion'=>$conclusion,
                'feedback'=>$feedback,
                'order_type'=>$order_type,
                'id'=>$id,
                'reward_details'=>$reward_details
            ];

            return Response::json($resp);
        }
    }

    public function geoLocationFinder($request){

        $offset  = $request['offset'];
        $limit   = $request['limit'];
        $radius  = $request['radius'];
        $lat    =  $request['lat'];
        $lon    =  $request['lon'];
        $category = $request['category'];
        $keys = $request['keys'];

        $payload = [
           "category"=>$category,
           "sort"=>[
              "sortfield"=>"popularity",
              "order"=>"desc"
           ],
           "offset"=>[
              "from"=>$offset,
              "number_of_records"=>$limit
           ],
           "other_filters"=>[

           ],
           "location"=>[
              "city"=>"mumbai",
              "lat"=>$lat,
              "lon"=>$lon,
              "radius"=>$radius
           ],
           "with_locationtags"=>"1",
           "keys"=>$keys
        ];

        //echo"<pre>";print_r($payload);exit;

        $url = $this->api_url."search/getfinderresultsv4";
        $finder = [];

        try {
           
            $response  =   json_decode($this->client->post($url,['json'=>$payload])->getBody()->getContents(),true);

            if(isset($response['results']['resultlist'])){

                $vendor = $response['results']['resultlist'];

                foreach ($vendor as $key => $value) {

                    $address = false;

                    $finder_data = $value['object'];

                    if(in_array('coverimage',$request['keys'])){
                        $finder_data['coverimage'] = Config::get('app.s3_finderurl.cover_thumb').$finder_data['coverimage'];
                    }

                    if(in_array('offerings',$request['keys'])){
                        $finder_data['remarks'] = implode(",",$finder_data['offerings']);
                        unset($finder_data['offerings']);
                    }

                    if(in_array('multiaddress',$request['keys'])){

                        $finder_data['address'] = "";

                        if(!empty($finder_data['multiaddress']) && isset( $finder_data['multiaddress'][0])){

                            $multi_address = $finder_data['multiaddress'][0];

                            $finder_data['address'] = $multi_address['line1'].$multi_address['line2'].$multi_address['line3'].$multi_address['landmark'].$multi_address['pincode'];

                            $address = true;                            
                        }
                     
                        unset($finder_data['multiaddress']);
                    }

                    if(in_array('contact',$request['keys'])){

                        if(!$address && !empty($finder_data['contact']) && isset($finder_data['contact']['address']) && $finder_data['contact']['address'] != ""){

                            $finder_data['address'] = $finder_data['contact']['address'];
                        }

                        unset($finder_data['contact']);
                    }

                    $finder[] = $finder_data;
                }
            }

            return $finder;

        }catch (RequestException $e) {

            return $finder;

        }catch (Exception $e) {

            return $finder;
        }

    }



    public function getFinderCountLocationwise($city = 'mumbai', $cache = true){


        $findercount_locationwise_city  =   $cache ? Cache::tags('findercount_locationwise_city')->has($city) : false;

        if(!$findercount_locationwise_city){

            $citydata                       =   City::where('slug', '=', $city)->first(array('name','slug'));

            if(!$citydata){
                return $this->responseNotFound('City does not exist');
            }

            $city_name     =       trim(strtolower($citydata['name']));
            $jsonData      = '{
                "category":"",
                "budget":[],
                "offerings":[],
                "facilities":[],
                "regions":[],
                "location":{"city":"'.$city_name.'"},
                "offset":{"from":0,
                "number_of_records":0},
                "sort":{"sortfield":"popularity",
                "order":"desc"},
                "trialdays":[],
                "with_locationtags": 1,
                "keys":["name"]
            }';


            $payload            =   json_decode($jsonData, true);
            $url                =   $this->api_url."search/getfinderresultsv4";
            $response           =   json_decode($this->client->post($url,['json'=>$payload])->getBody()->getContents(), true);
            $aggregationlist    =   (isset($response['results']['aggregationlist']) && $response['results']['aggregationlist']['locationtags']) ? $response['results']['aggregationlist']['locationtags'] : [];


            $locationsArr       =   [];

            if(count($aggregationlist) > 0){
                foreach ($aggregationlist as $key => $location) {
                    if(intval($location['count']) > 0){
                        $location = ['count' => $location['count'], 'name' => $location['key'], 'slug' => url_slug([$location['slug']]) ];
                        array_push($locationsArr, $location);
                    }
                }
            }

            $data               =   ['locations' => $locationsArr, 'message' => 'locations aggregationlist :)'];
            Cache::tags('findercount_locationwise_city')->put($city, $data, Config::get('cache.cache_time'));

        }

        return Response::json(Cache::tags('findercount_locationwise_city')->get($city));

    }




    public function getFooterByCity($city = 'mumbai',$cache = true){

        $footer_by_city = $cache ? Cache::tags('footer_by_city')->has($city) : false;

        if(!$footer_by_city){
            $footer_finders 			=		array();
            $citydata 					=		City::where('slug', '=', $city)->first(array('name','slug'));
            if(!$citydata){
                return $this->responseNotFound('City does not exist');
            }

            $city_name 					= 		$citydata['name'];
            $city_id					= 		(int) $citydata['_id'];
            $homepage 					= 		Homepage::where('city_id', '=', $city_id)->get()->first();
            if(!$homepage){
                return $this->responseNotFound('footer blocks does not exist');
            }

            $footer_block1_ids 			= 		array_map('intval', explode(",", $homepage['footer_block1_ids'] ));
            $footer_block2_ids 			= 		array_map('intval', explode(",", $homepage['footer_block2_ids'] ));
            $footer_block3_ids 			= 		array_map('intval', explode(",", $homepage['footer_block3_ids'] ));
            $footer_block4_ids 			= 		array_map('intval', explode(",", $homepage['footer_block4_ids'] ));
            $footer_block5_ids 			= 		array_map('intval', explode(",", $homepage['footer_block5_ids'] ));
            $footer_block6_ids 			= 		array_map('intval', explode(",", $homepage['footer_block6_ids'] ));

            $footer_block1_finders 		=		Finder::active()->whereIn('_id', $footer_block1_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block2_finders 		=		Finder::active()->whereIn('_id', $footer_block2_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block3_finders 		=		Finder::active()->whereIn('_id', $footer_block3_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block4_finders 		=		Finder::active()->whereIn('_id', $footer_block4_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block5_finders 		=		Finder::active()->whereIn('_id', $footer_block5_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();
            $footer_block6_finders 		=		Finder::active()->whereIn('_id', $footer_block6_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title'))->toArray();

            array_set($footer_finders,  'footer_block1_finders', $footer_block1_finders);
            array_set($footer_finders,  'footer_block2_finders', $footer_block2_finders);
            array_set($footer_finders,  'footer_block3_finders', $footer_block3_finders);
            array_set($footer_finders,  'footer_block4_finders', $footer_block4_finders);
            array_set($footer_finders,  'footer_block5_finders', $footer_block5_finders);
            array_set($footer_finders,  'footer_block6_finders', $footer_block6_finders);

            array_set($footer_finders,  'footer_block1_title', (isset($homepage['footer_block1_title']) && $homepage['footer_block1_title'] != '') ? $homepage['footer_block1_title'] : '');
            array_set($footer_finders,  'footer_block2_title', (isset($homepage['footer_block2_title']) && $homepage['footer_block2_title'] != '') ? $homepage['footer_block2_title'] : '');
            array_set($footer_finders,  'footer_block3_title', (isset($homepage['footer_block3_title']) && $homepage['footer_block3_title'] != '') ? $homepage['footer_block3_title'] : '');
            array_set($footer_finders,  'footer_block4_title', (isset($homepage['footer_block4_title']) && $homepage['footer_block4_title'] != '') ? $homepage['footer_block4_title'] : '');
            array_set($footer_finders,  'footer_block5_title', (isset($homepage['footer_block5_title']) && $homepage['footer_block5_title'] != '') ? $homepage['footer_block5_title'] : '');
            array_set($footer_finders,  'footer_block6_title', (isset($homepage['footer_block6_title']) && $homepage['footer_block6_title'] != '') ? $homepage['footer_block6_title'] : '');

            // Default City vendors
            $defaultfinders = Finder::where('city_id',10000)->active()->get(array('title','slug','custom_city','custom_location'))->groupBy('custom_city');
            $footerdata 	= 	array('footer_finders' => $footer_finders, 'city_name' => $city_name, 'city_id' => $city_id,'default_vendors'=>$defaultfinders);
            // $footerdata 	= 	array('footer_finders' => $footer_finders, 'city_name' => $city_name, 'city_id' => $city_id);
            Cache::tags('footer_by_city')->put($city, $footerdata, Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('footer_by_city')->get($city));
    }



    public function getFooterByCityV1($city = 'mumbai', $category = 'all', $location = "", $cache = false){

        $footer_by_city = $cache ? Cache::tags('category_wise_footer_finders')->has($city) : false;

        if(!$footer_by_city){

            $categoryArr            =   ["gyms", "fitness studios" , "zumba" , "yoga" , "dance" , "mma and kick boxing" , "pilates" , "crossfit"];
            $categoryArrWithId      =   ["all" => "all", "gyms" => "gyms", "fitness-studios" => "fitness studios", "zumba" => "zumba", "yoga" => "yoga" , "dance" => "dance", "mma-and-kick-boxing" => "mma and kick boxing" , "pilates" => "pilates", "crossfit" => "crossfit"];

            if(array_key_exists($category ,$categoryArrWithId)){
                unset($categoryArrWithId[strtolower($category)]);
            }

//            return $categoryArrWithId; exit;

            $footer_finders  = [];
            $key = 1;
            foreach ($categoryArrWithId as $category => $categoryslug){

                $categoryParm      = ($categoryslug == 'all') ? "" : $categoryslug;

                if($location == ""){
                    $jsonData      = '{
                        "category":"'.$categoryParm.'",
                        "budget":[],
                        "offerings":[],
                        "facilities":[],
                        "regions":[],
                        "location":{"city":"'.$city.'"},
                        "offset":{"from":0,
                        "number_of_records":5},
                        "sort":{"sortfield":"popularity",
                        "order":"desc"},
                        "trialdays":[]
                    }';
                }else{
                    $jsonData      = '{
                        "category":"'.$categoryParm.'",
                        "budget":[],
                        "offerings":[],
                        "facilities":[],
                        "regions":["'.str_ireplace(',', '","',$location).'"],
                        "location":{"city":"'.$city.'"},
                        "offset":{"from":0,
                        "number_of_records":5},
                        "sort":{"sortfield":"popularity",
                        "order":"desc"},
                        "trialdays":[]
                    }';
                }

                $payload    =   json_decode($jsonData, true);
                $url        =   $this->api_url."search/getfinderresultsv2";
                $response   =  json_decode($this->client->post($url,['json'=>$payload])->getBody()->getContents(), true);


                $finders    = (isset($response['results']['resultlist'])) ? $response['results']['resultlist'] : [];
                $finderArr  = [];
                if(count($finders) > 0){
                    foreach ($finders as $finder){
                        $finder = array_only($finder['object'], array('id', 'title','slug','category','location','categorytags'));
                        array_push( $finderArr, $finder);
                    }

                }
                $footer_finder_block_key                     =   "footer_block".$key."_finders";
                $footer_finders[$footer_finder_block_key]    =   $finderArr;
                $footer_title_block_key                      =   "footer_block".$key."_title";
                $footer_finders[$footer_title_block_key]     =   ucwords($category)." in ".ucwords($city);
                $key = $key + 1;
            }

//            return $footer_finders;

            $footerdata 	= 	array('footer_finders' => $footer_finders, 'city_name' => $city);
            Cache::tags('category_wise_footer_finders')->put($city, $footerdata, Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('category_wise_footer_finders')->get($city));
    }


    public function getCities(){

        $array = array();

        $cites		= 	City::active()->orderBy('name')->whereNotIn('_id',$array)->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));

        return Response::json($cites,200);
    }

    public function getCityLocation($city = 'mumbai',$cache = true){

        $location_by_city = $cache ? Cache::tags('location_by_city')->has($city) : false;
        if(!$location_by_city){
            $categorytags = $locations  =	array();
            if($city != "all"){
                $citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
                if(!$citydata){
                    return $this->responseNotFound('City does not exist');
                }

                $city_name 		= 	$citydata['name'];
                $city_id		= 	(int) $citydata['_id'];

                $locations				= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug','location_group','lat','lon'));
            }else{
                $locations				= 	Location::active()->orderBy('name')->get(array('name','_id','slug','location_group','lat','lon'));
            }

            $homedata 				= 	array('locations' => $locations );

            Cache::tags('location_by_city')->put($city,$homedata,Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('location_by_city')->get($city));
    }



    public function getCityCategorys($city = 'mumbai',$cache = true){

        $category_by_city = $cache ? Cache::tags('category_by_city')->has($city) : false;
        if(!$category_by_city){
            $categorytags = $locations  =	array();
            $citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));

            if(!$citydata){
                return $this->responseNotFound('City does not exist');
            }

            $city_name 				= 	$citydata['name'];
            $city_id				= 	(int) $citydata['_id'];
            // $categorytags			= 	Findercategorytag::active()->whereIn('cities',array($city_id))->where('_id', '!=', 42)->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
            $categorytags			= 	Findercategorytag::active()->whereIn('cities',array($city_id))->whereNotIn('_id', [41,37,39,43,44])->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
            $homedata 				= 	array('categorytags' => $categorytags );

            Cache::tags('category_by_city')->put($city,$homedata,Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('category_by_city')->get($city));
    }


    public function landingcrushLocationClusterWise($location_cluster){

        switch ($location_cluster) {
            case 'all':
                $finder_ids		=		array(6988,6991,6992,6995,6999,7006,7017,7360,7418,7439,7440,7441);
                break;

            case 'north':
                $finder_ids		=		array(6995,6999);
                break;

            case 'south':
                $finder_ids		=		array(6988,6991,6993,7006, 7439, 7441);
                break;

            case 'east':
                $finder_ids		=		array(7017);
                break;

            case 'west':
                $finder_ids		=		array(7360);
                break;

            case 'gurgaon':
                $finder_ids		=		array(6992,7440);
                break;
        }

        // $finder_ids		=		array(6988,6991,6992,6995,6999,7006,7017,7360,7418,7439,7440,7441);
        $finders = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with('categorytags')
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->whereIn('_id', $finder_ids)
            ->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','contact','budget','price_range'))->toArray();

        $finderArr = [];
        foreach ($finders as $key => $value) {
            $finderobj 	= 	array_except($value, array('categorytags'));
            array_set($finderobj, 'categorytags', pluck( $value['categorytags'] , array('_id', 'name', 'slug', 'offering_header') ));
            array_push($finderArr, $finderobj);
        }
        $responseArr = ['finders' => $finderArr, 'count' => count($finder_ids), 'location_cluster' => $location_cluster];
        return Response::json($responseArr);
    }


    public function landingcrushFinders(){

        $finder_ids			=		array(6988,6991,6992,6995,6999,7006,7017,7360,7418,7439,7440,7441,7870,7872,8646,8647,8648,8666,8729,8731,8741);
        $gallery 			= 		Finder::whereIn('_id', $finder_ids)->with(array('location'=>function($query){$query->select('_id','name','slug');}))->pluck('photos');
        $finders 			= 		Finder::whereIn('_id', $finder_ids)
            ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
            ->get(array('_id','slug','title','category_id','category','location_id','location','city_id','city','contact','services'))->toArray();;

        $finderArr = [];
        foreach ($finders as $key => $value) {
            $finderobj 	= 	array_except($value, array('services'));
            array_set($finderobj, 'services', pluck( $value['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category','subcategory']  ));
            array_push($finderArr, $finderobj);
        }

        $responseArr 		= 		['finders' => $finderArr, 'gallery' => $gallery, 'count' => count($finder_ids)];
        return Response::json($responseArr);
    }

    public function landingFinders($typeoflandingpage){
        switch($typeoflandingpage){
            case "yfc" : $finder_ids			=		array(1029,1030,1033,1034,1035,1554,1705,1706,7407,1870,4585,5045);
                break;
            case "snap" : $finder_ids			=		array(608,2890,3175,3178,3179,3183,3192,3201,3204,3233,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,7081,7106,7111,7114,7116,8872,5566,5735,5736,5737,5738,5739,5964,6254,6594,8878);
                break;
            default: $responseArr 		= 		['finders' => [], 'gallery' => [], 'count' => 0];
                return Response::json($responseArr);

        }
        $gallery 			= 		Finder::whereIn('_id', $finder_ids)->with(array('location'=>function($query){$query->select('_id','name','slug');}))->pluck('photos');
        $finders 			= 		Finder::whereIn('_id', $finder_ids)
            ->with('categorytags')
            ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
            ->get(array('_id','slug','title','categorytags','category_id','category','location_id','location','city_id','city','contact','services','lat','lon','price_range','average_rating','custom_city','coverimage'))->toArray();;

        $finderArr = [];
        foreach ($finders as $key => $value) {
            $finderobj 	= 	array_except($value, array('services'));
            array_set($finderobj, 'services', pluck( $value['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category','subcategory']  ));
            array_push($finderArr, $finderobj);
        }

        $responseArr 		= 		['finders' => $finderArr, 'gallery' => $gallery, 'count' => count($finders)];
        return Response::json($responseArr);
    }
    public function landingFindersTitle($typeoflandingpage, $city_id = ''){
        switch($typeoflandingpage){
            case "yfc" :
                $finder_ids			=	[1029,1030,1033,1034,1035,1554,1705,1706,7407,1870,4585,5045];
                break;
            case "snap" :
                $finder_ids			=	[608,2890,3175,3178,3179,3183,3192,3201,3204,3233,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,
                    7081,7106,7111,7114,7116,8872,5566,5735,5736,5737,5738,5739,5964,6254,6594,8878];
                break;
            default:
                $responseArr 		= 	['finders' => []];
                return Response::json($responseArr);
        }

        if($city_id == ''){
            $finders 			= 		Finder::whereIn('_id', $finder_ids)->get(array('_id','slug','title'))->toArray();;
        }else{
            $finders 			= 		Finder::whereIn('_id', $finder_ids)->where('city_id', intval($city_id))->get(array('_id','slug','title'))->toArray();;
        }

        $responseArr 		    = 		['finders' => $finders];
        return Response::json($responseArr);
    }
    public function landingAnytimeFitnessFinders(){

        $finder_ids			=		array(1484,5728,5745,5746,5747,5748,6250,7335,5728,7900,7901,7902,7903,7905,7906,7907,7909,8821,8823,8871);
        $gallery 			= 		Finder::whereIn('_id', $finder_ids)->with(array('location'=>function($query){$query->select('_id','name','slug');}))->pluck('photos');
        $finders 			= 		Finder::whereIn('_id', $finder_ids)
            ->with('categorytags')
            ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
            ->get(array('_id','slug','title','categorytags','category_id','category','location_id','location','city_id','city','contact','services','lat','lon','price_range','average_rating','custom_city','coverimage'))->toArray();;

        $finderArr = [];
        foreach ($finders as $key => $value) {
            $finderobj 	= 	array_except($value, array('services'));
            array_set($finderobj, 'services', pluck( $value['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category','subcategory']  ));
            array_push($finderArr, $finderobj);
        }

        $responseArr 		= 		['finders' => $finderArr, 'gallery' => $gallery, 'count' => count($finder_ids)];
        return Response::json($responseArr);
    }


    public function landingPowerhouseFinders(){

        $finder_ids			=		array(1392,1393,1579,1580,1581,1582,1583,1584,1602,1604,1605,1607,2235,2236,2244,6890,6891,6893);
        $gallery 			= 		Finder::whereIn('_id', $finder_ids)->with(array('location'=>function($query){$query->select('_id','name','slug');}))->pluck('photos');
        $finders 			= 		Finder::whereIn('_id', $finder_ids)
            ->with('categorytags')
            ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
            ->get(array('_id','slug','title','categorytags','category_id','category','location_id','location','city_id','city','contact','services','lat','lon','price_range','average_rating','custom_city','coverimage'))->toArray();;

        $finderArr = [];
        foreach ($finders as $key => $value) {
            $finderobj 	= 	array_except($value, array('services'));
            array_set($finderobj, 'services', pluck( $value['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category','subcategory']  ));
            array_push($finderArr, $finderobj);
        }

        $responseArr 		= 		['finders' => $finderArr, 'gallery' => $gallery, 'count' => count($finders)];
        return Response::json($responseArr);
    }


    public function landingAnytimeFitnessFindersCityWise($cityid)
    {
        $finder_ids     = array(1484, 5728, 5745, 5746, 5747, 5748, 6250, 7335, 7439, 7900, 7901, 7902, 7903, 7905, 7906, 7907, 7909);
        $finders 		= 		Finder::whereIn('_id', $finder_ids)->where('city_id', intval($cityid))->get(array('_id','slug','title'))->toArray();;
        $finder_html     =       "";
        foreach ($finders as $key => $finder) {
            $finder_html .= "<option value='".$finder['_id']."' data-findername='".$finder['title']."'>".ucwords($finder['title'])."</option>";
        }
        return $finder_html;
    }

    public function landingzumba(){
        $finder_slugs 		=		array(1493,2701,1771,1623,4742,5373,1646,731,6140,6134,3382,1783);
        $zumba = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->whereIn('_id', $finder_slugs)
            ->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','contact'));

        return Response::json($zumba);

    }

    public function zumbadiscover(){
        $finder_slugs 		=		array('mint-v-s-fitness-khar-west',
            'zumba-with-illumination',
            'reebok-fitness-studio-khar-west',
            'frequencee-hughes-road',
            'the-soul-studio',
            'studio-balance-hughes-road',
            'house-of-wow-bandra-west',
            'nritya-studio-navi-mumbai',
            'dance-beat-mumbai-hughes-road',
            'zumba-with-yogesh-kushalkar',
            'jgs-fitness-centre-santacruz-west',
            'rudra-shala-malad-west');
        $zumba = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))->whereIn('slug', $finder_slugs)->get(array('_id','title','average_rating','category_id','category','slug','contact','coverimage'));
        return Response::json($zumba);

    }



    public function fitcardpagefinders(){
        $finders							= 		array();

        $bandra_vileparle_finder_ids 		=		array(579,878,590,1606,1580,752,566,131,1747,1813,1021,424,1451,905,1388,1630,728,1031,1495,816,902,1650,1424,1587,1080,224,984,1563,1242,223,1887,1751,1493,1783,1691,1516,1781,1784,827,147,417,1676,1885,825,569,61,1431,123,968,1664,571,881,1673,1704,900,1623,1766,1765,596,400,1452,1604,1690,987,1431,1607,1656,1666,1621,1673,613,1473,1242,616);
        $bandra_vileparle_finders 			= 		Finder::whereIn('_id', $bandra_vileparle_finder_ids)
            ->remember(Config::get('app.cachetime'))
            ->get(array('_id','title','slug','coverimage'));
        array_set($finders,  'bandra_vileparle_finders', array('title'=>'Bandra to Vile Parle', 'finders' => $bandra_vileparle_finders));

        $andheri_borivalii_finder_ids 		=		array(1579,1261,1705,401,561,341,1655,1513,1510,739,1514,570,1260,1261,40,1465,523,576,1332,166,1447,602,1428,224,1887,1786,604,1771,1257,1751,1523,1554,1235,1209,439,1421,625,1020,1522,1392,1667,1484,1041,1435,1694,1259,1413,45,449,1330,227,1697,1395,1511,1873,1698,1691,1389,412,1642,1480,1676,417,1682,1069,1677,1445,1424,223,1214,1688,1080,1490);
        $andheri_borivalii_finders 			= 		Finder::whereIn('_id', $andheri_borivalii_finder_ids)
            ->remember(Config::get('app.cachetime'))
            ->get(array('_id','title','slug','coverimage'));
        array_set($finders,  'andheri_borivalii_finders', array('title'=>'Andheri to Borivali', 'finders' => $andheri_borivalii_finders));

        $south_mumbai_finder_ids 			=		array(718,329,26,25,1603,1605,1449,328,171,1296,1327,1422,1710,1441,1293,1295,903,1835,1639,983,1851,569,1764,1823,1493,1646,1242,1563,1783,1887,984,1612,827,417,1888,1782,138,731,1,422,1122,1029,1706,1331,1333,1233);
        $south_mumbai_finders 				= 		Finder::whereIn('_id', $south_mumbai_finder_ids)
            ->remember(Config::get('app.cachetime'))
            ->get(array('_id','title','slug','coverimage'));
        array_set($finders,  'south_mumbai_finders', array('title'=>'South Mumbai', 'finders' => $south_mumbai_finders));


        $central_suburbs_finder_ids 		=		array(1450,1602,413,1609,437,1501,927,1494,700,1264,1269,1357,256,1030,170,417,1454,1581,1266);
        $central_suburbs_finders 			= 		Finder::whereIn('_id', $central_suburbs_finder_ids)
            ->remember(Config::get('app.cachetime'))
            ->get(array('_id','title','slug','coverimage'));
        array_set($finders,  'central_suburbs_finders', array('title'=>'Central Suburbs', 'finders' => $central_suburbs_finders));



        return Response::json($finders);

    }

    public function specialoffers_finder(){
        $finders		= 		array();
        $findersrs 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->where('finder_type', '=', 1)
            ->where('status', '=', '1')
            ->get(array('_id','title','slug','category_id','category','special_offer_title'))
            ->toArray();

        foreach ($findersrs as $key => $value) {
            $postdata = array(
                'finderid' => $value['_id'],
                'category' => strtolower($value['category']['name']),
                'slug' => $value['slug'],
                'title' => strtolower($value['title']),
                'special_offer_title' => (isset($value['special_offer_title']) && $value['special_offer_title'] != '') ? $value['special_offer_title'] : ""
            );
            array_push($finders, $postdata);
        }
        return Response::json($finders);
    }

    public function yfc_finders(){

        $finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
            ->whereIn('_id', array(1029,1030,1032,1034,1035,1554,1705,1706,1870,4585))
            ->remember(Config::get('app.cachetime'))
            ->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','contact'))
            ->toArray();
        return Response::json($finders);
    }



    public function getcollecitonnames($city = 'mumbai', $cache = true){

        $collection_by_city_list = $cache ? Cache::tags('collection_by_city_list')->has($city) : false;
        if(!$collection_by_city_list){
            $citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
            if(!$citydata){
                return $this->responseNotFound('City does not exist');
            }

            $city_id		= 	(int) $citydata['_id'];
            $collections 	= 	Findercollection::active()->where('city_id', '=', $city_id)->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name', 'slug', 'coverimage', 'ordering' ));

            if(count($collections) < 1){
                $resp 	= 	array('status' => 200,'collections' => $collections,'message' => 'No collections yet :)');
                Cache::tags('collection_by_city_list')->put($city,$resp,Config::get('cache.cache_time'));
                return Response::json(Cache::tags('collection_by_city_list')->get($city));
            }

            $resp 	= 	array('status' => 200,'collections' => $collections,'message' => 'List of collections names');
            Cache::tags('collection_by_city_list')->put($city,$resp,Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('collection_by_city_list')->get($city));
    }


    public function getcollecitonfinders($city, $slug, $cache = true){

        $city = strtolower($city);
        $slug = strtolower($slug);

        $finder_by_collection_list = $cache ? Cache::tags('finder_by_collection_list')->has($city."_".$slug) : false;
        if(!$finder_by_collection_list){
            $citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));

            if(!$citydata){
                return $this->responseNotFound('City does not exist');
            }

            $city_id		= 	(int) $citydata['_id'];
            $collection 	= 	Findercollection::where('slug', '=', trim($slug))->where('city_id', '=', $city_id)->first(array());
            $finder_ids 	= 	array_map('intval', explode(",", $collection['finder_ids']));

            $collection_finders =	Finder::whereIn('_id', $finder_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count','info','city_id','photos'))
                ->toArray();

            $finders = array();

            // return $finder_ids;
            // echo $collection['finder_ids']."<br>";1395,881,1490,968,1765,613,1682,424,1493,1,1704,1928
            foreach ($finder_ids as $key => $finderid) {
                $array = head(array_where($collection_finders, function($key, $value) use ($finderid){
                    if($value['_id'] == $finderid){ return $value; }
                }));
                array_push($finders, $array);
            }

            // return $finders; exit;
            $data 	= 	array('status' => 200,'collection' => $collection,'collection_finders' => $finders);
            Cache::tags('finder_by_collection_list')->put($city."_".$slug,$data,Config::get('cache.cache_time'));
        }
        return Response::json(Cache::tags('finder_by_collection_list')->get($city."_".$slug));
    }


    public function fitcardfinders(){

        $fitcardids = array_unique(array(3305,1664,1214,1587,147,217,417,579,596,752,825,827,878,905,1021,1031,1376,1388,1451,1493,1495,1516,1630,1650,1765,1783,1965,2211,2736,1473,741,984,1496,2810,1752,2818,1346,
            1668,2755,2833,3109,1751,624,1692,2865,1770,677,902,1243,1623,1766,224,2808,232,566,881,900,1563,968,1704,1885,351,424,599,571,816,1588,1080,131,1606,1813,1673,1732,
            590,1242,1431,1607,1452,1747,400,613,616,633,975,987,1309,1604,1676,1750,2207,341,1784,1656,1642,1621,575,877));

        $finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->whereIn('_id', $fitcardids)
            ->remember(Config::get('app.cachetime'))
            ->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','contact'))
            ->toArray();

        return Response::json($finders);
    }


    public function fitcardfindersV1(){

        $data				=	Input::json()->all();
        $fitcardids = array_unique(array(3305,1664,1214,1587,147,217,417,579,596,752,825,827,878,905,1021,1031,1376,1388,1451,1493,1495,1516,1630,1650,1765,1783,1965,2211,2736,1473,741,984,1496,2810,1752,2818,1346,
            1668,2755,2833,3109,1751,624,1692,2865,1770,677,902,1243,1623,1766,224,2808,232,566,881,900,1563,968,1704,1885,351,424,599,571,816,1588,1080,131,1606,1813,1673,1732,
            590,1242,1431,1607,1452,1747,400,613,616,633,975,987,1309,1604,1676,1750,2207,341,1784,1656,1642,1621,575,877));

        if(!isset($data['category_id']) && !isset($data['location_id']) ){
            $finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->whereIn('_id', $fitcardids)
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','contact'))
                ->toArray();
        }

        if(isset($data['location_id']) && $data['location_id'] != ""){
            $finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->whereIn('_id', $fitcardids)
                ->where('location_id', intval($data['location_id']))
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','contact'))
                ->toArray();
        }

        if(isset($data['category_id']) &&  $data['category_id'] != ""){
            $finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->whereIn('_id', $fitcardids)
                ->where('category_id', intval($data['category_id']))
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','contact'))
                ->toArray();
        }


        if(isset($data['category_id']) && $data['category_id'] != "" && isset($data['location_id']) &&  $data['location_id'] != ""){
            $finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->whereIn('_id', $fitcardids)
                ->where('category_id', intval($data['category_id']))
                ->where('location_id', intval($data['location_id']))
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','contact'))
                ->toArray();
        }

        return Response::json($finders);
    }



    //Get all landing page finder base on location cluster
    public function getLandingPageFinders($cityid , $landingpageid, $locationclusterid = ''){

        $locationclusters 	= 	Locationcluster::active()->where('city_id', intval($cityid))->lists('name','_id');
        $collection 		= 	Landingpage::active()->find( intval($landingpageid) )->first(array());
        $finder_ids 		= 	array_map('intval', explode(",", $collection['finder_ids']));

        $query 		=	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('offerings')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('location'=>function($query){$query->select('_id','name','slug');}))->whereIn('_id', $finder_ids);

        if($locationclusterid != ''){
            $locations 		= 	Location::active()->where('locationcluster_id', intval($locationclusterid))->lists('name','_id');
            $locaitonids   	= 	array_map('intval', array_keys($locations));
            // return $locaitonids;
            $query->whereIn('location_id', $locaitonids);
        }

        $collection_finders = $query->remember(Config::get('app.cachetime'))->get();
        $landingfinders = [];
        foreach ($collection_finders as $key => $value) {
            $landingdata = $this->transformLandingpageFinder($value);
            array_push($landingfinders, $landingdata);
        }

        $responsedata = ['locationclusters' => $locationclusters, 'finders' => $landingfinders];
        return Response::json($responsedata, 200);
    }


    private function transformLandingpageFinder($finder){

        $item  	   	=  	(!is_array($finder)) ? $finder->toArray() : $finder;

        $data = [
            '_id' => $item['_id'],
            'title' => (isset($item['title']) && $item['title'] != '') ? strtolower($item['title']) : "",
            'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
            'lat' => (isset($item['lat']) && $item['lat'] != '') ? strtolower($item['lat']) : "",
            'lon' => (isset($item['lon']) && $item['lon'] != '') ? strtolower($item['lon']) : "",
            'commercial_type' => (isset($item['commercial_type']) && $item['commercial_type'] != '') ? strtolower($item['commercial_type']) : "",
            'finder_coverimage' => (isset($item['finder_coverimage']) && $item['finder_coverimage'] != '') ? strtolower($item['finder_coverimage']) : "",
            'average_rating' => (isset($item['average_rating']) && $item['average_rating'] != '') ? $item['average_rating'] : "",
            'total_rating_count' => (isset($item['total_rating_count']) && $item['total_rating_count'] != '') ? $item['total_rating_count'] : "",
            'offerings' => (isset($item['offerings']) && !empty($item['offerings'])) ? pluck( $item['offerings'] , array('_id', 'name', 'slug') ) : "",
            'location' => (isset($item['location']) && !empty($item['location'])) ? array_only( $item['location'] , array('_id', 'name', 'slug') ) : "",
            'category' => (isset($item['category']) && !empty($item['category'])) ? array_only( $item['category'] , array('_id', 'name', 'slug') ) : "",
            'info' => (isset($item['info']) && !empty($item['info'])) ? $item['info']  : "",
            'contact' => (isset($item['contact']) && !empty($item['contact'])) ? $item['contact']  : "",
            'city' => (isset($item['city']) && !empty($item['city'])) ? $item['city']  : "",
            'photos' => (isset($item['photos']) && !empty($item['photos'])) ? $item['photos']  : "",
        ];

        // echo "<pre>";print_r($data);exit();
        return $data;


    }


    public function getOffersTabsOffers($city, $captionslug, $slug){

        $citydata 		=	City::where('slug', '=', strtolower($city))->first(array('name','slug'));
        if(!$citydata){
            return $this->responseNotFound('City does not exist');
        }
        $city_name 				= 	$citydata['name'];
        $city_id				= 	(int) $citydata['_id'];

        $slugname 				= 	strtolower(trim($slug));
        $captionslug 			= 	strtolower(trim($captionslug));
        $offerobj 				=	Offer::where('city_id', '=', $city_id)->where('slug', '=', strtolower($captionslug))->first();

        if(count($offerobj) < 1){
            $responsedata 	= ['services' => [],  'message' => 'No Service Exist'];
            return Response::json($responsedata, 200);
        }

        $offerdata = $offerobj->toArray();

        // return $offerobj 				=	Offer::where('city_id', '=', $city_id)->where('1_url',$slugname)->orWhere('2_url',$slugname)->orWhere('3_url',$slugname)->orWhere('4_url',$slugname)->get()->toArray();
        // return $offerdata 				=	Offer::where('city_id', '=', $city_id)->where('1_url', '=', $slugname)->orWhere('2_url', '=', $slugname)->orWhere('3_url', '=', $slugname)->orWhere('4_url', '=', $slugname)->first()->toArray();

        // echo "<pre>";print_r($offerobj);exit();
        // $offerdata 				=	Offer::where('city_id', '=', $city_id)->first()->toArray();

        $slug_array 			=  	array_map('strtolower', array_only($offerdata, array('1_title', '2_title','3_title','4_title')));
        $slug_index 			= 	array_search($slugname,$offerdata);
        $ratecardids_index 		=  	str_replace('url', 'ratecardids', $slug_index);
        $ratecardids 			=   array_map('intval', explode(',', $offerdata[$ratecardids_index]));
        $ratecards_array        =   Ratecard::with('serviceoffers')->whereIn('_id', $ratecardids  )->get()->toArray();
        $servicesids     		=  	array_flatten(pluck($ratecards_array, ['service_id']));
        $serivce_array 			= 	Service::active()->whereIn('_id', $servicesids  )
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('finder'=>function($query){$query->select('_id','title','slug','finder_coverimage','coverimage','average_rating', 'contact');}))
            ->get()
            ->toArray();

        if(count($serivce_array) < 1){
            $responsedata 	= ['services' => [],  'message' => 'No Service Exist'];
            return Response::json($responsedata, 200);
        }

        $services = $service_ratecards = [];
        foreach ($serivce_array as $key => $value) {
            $service_id  			=	$value['_id'];
            $serviceobj 			=	array_only($value, ['name','_id','finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'service_coverimage', 'category',  'subcategory', 'location', 'finder','trialschedules']);
            $service_ratecards 		=	array_where($ratecards_array, function($key, $value) use ($service_id){
                if($value['service_id'] == $service_id){
                    return $value;
                }
            });
            $serviceobj['service_ratecards'] = array_values($service_ratecards);
            array_push($services, $serviceobj);
        }

        $data['services'] = $services;
        return Response::json($data, 200);
    }



    public function getOffersTabsOffersV1($city, $captionslug, $slug){

        $citydata 		=	City::where('slug', '=', strtolower($city))->first(array('name','slug'));
        if(!$citydata){
            return $this->responseNotFound('City does not exist');
        }
        $city_name 				= 	$citydata['name'];
        $city_id				= 	(int) $citydata['_id'];

        $slugname 				= 	strtolower(trim($slug));
        $captionslug 			= 	strtolower(trim($captionslug));
        $offertabobj 			=	Offer::where('city_id', '=', $city_id)->where('slug', '=', $captionslug)->first();

        if(count($offertabobj) < 1){
            $responsedata 	= ['offers' => [],  'message' => 'No Offers Exist'];
            return Response::json($responsedata, 200);
        }

        $offertabdata 			= 	$offertabobj->toArray();
        $slug_array 			=  	array_map('strtolower', array_only($offertabdata, array('1_url', '2_url','3_url','4_url')));
        $slug_index 			= 	array_search($slugname,$slug_array);
        $ratecardids_index 		=  	str_replace('url', 'ratecardids', $slug_index);
        $offersids 				=   array_map('intval', explode(',', $offertabdata[$ratecardids_index]));

        $offers 				= 	[];
        $offers_records 		= 	Serviceoffer::with('finder')->with('service')->whereIn('_id', $offersids)->get();
        foreach ($offers_records as $key => $record) {
            array_push($offers, $record);
        }

        if(count($offers) > 0){
            $responsedata 	= ['offers' => $offers,  'message' => 'Offers Lists'];
        }else{
            $responsedata 	= ['offers' => [],  'message' => 'No Offers Exist'];
        }
        return Response::json($responsedata, 200);

    }



    public function getOffers($city = 'mumbai', $from = '', $size = ''){

        $citydata 		=	City::where('slug', '=', strtolower($city))->first(array('name','slug'));
        if(!$citydata){
            return $this->responseNotFound('City does not exist');
        }
        $city_name 		= 	$citydata['name'];
        $city_id		= 	(int) $citydata['_id'];

        $from 					=	($from != '') ? intval($from) : 0;
        $size 					=	($size != '') ? intval($size) : 10;
        $offers_colleciton 		= 	Service::active()->where('finder_id', 'exists', true)->where('city_id', $city_id)->where('show_in_offers','1')->take($size)->with('finder')->with('category')->with('location')->with('subcategory')->skip($from)->orderBy('_id', 'desc')->get();

        $offers = [];
        foreach ($offers_colleciton as $key => $value) {
            $offerdata = $this->transformOffer($value);
            array_push($offers, $offerdata);
        }
        $responsedata 	= ['offers' => $offers,  'message' => 'List for offers'];

        return Response::json($responsedata, 200);

    }



    private function transformOffer($service){

        $item  	   	=  	(!is_array($service)) ? $service->toArray() : $service;

        $data = [
            '_id' => $item['_id'],
            'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
            'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
            'lat' => (isset($item['lat']) && $item['lat'] != '') ? strtolower($item['lat']) : "",
            'lon' => (isset($item['lon']) && $item['lon'] != '') ? strtolower($item['lon']) : "",
            'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
            'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
            'show_in_offers' => (isset($item['show_in_offers']) && $item['show_in_offers'] != '') ? strtolower($item['show_in_offers']) : "",
            'service_coverimage' => (isset($item['service_coverimage']) && $item['service_coverimage'] != '') ? strtolower($item['service_coverimage']) : "",
            'service_coverimage_thumb' => (isset($item['service_coverimage_thumb']) && $item['service_coverimage_thumb'] != '') ? strtolower($item['service_coverimage_thumb']) : "",
            'service_ratecards' => (isset($item['service_ratecards']) && !empty($item['service_ratecards'])) ? $item['service_ratecards']  : "",
            'location' => (isset($item['location']) && !empty($item['location'])) ? array_only( $item['location'] , array('_id', 'name', 'slug') ) : "",
            'category' => (isset($item['category']) && !empty($item['category'])) ? array_only( $item['category'] , array('_id', 'name', 'slug') ) : "",
            'subcategory' => (isset($item['subcategory']) && !empty($item['subcategory'])) ? array_only( $item['subcategory'] , array('_id', 'name', 'slug') ) : "",
        ];

        // echo "<pre>";print_r($data);exit();
        return $data;
    }




    public function getOffersTabs($city = 'mumbai'){

        $citydata 		=	City::where('slug', '=', strtolower($city))->first(array('name','slug'));
        if(!$citydata){
            return $this->responseNotFound('City does not exist');
        }
        $city_name 		= 	$citydata['name'];
        $city_id		= 	(int) $citydata['_id'];
        $offertabsrs 		= 	Offer::where('city_id', '=', $city_id)->get();

        $offertabs  = [];
        foreach ($offertabsrs as $key => $value) {

            $item = $value->toArray();

            $data = [
                '_id' => $item['_id'],
                'caption' => (isset($item['caption']) && $item['caption'] != '') ? strtolower($item['caption']) : "",
                'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
                'banner_link' => (isset($item['banner_link']) && $item['banner_link'] != '') ? strtolower($item['banner_link']) : "",
                'ordering' => (isset($item['ordering']) && $item['ordering'] != '') ? intval($item['ordering']) : "",
                'status' => (isset($item['status']) && $item['status'] != '') ? strtolower($item['status']) : "",
                'banner_image' => (isset($item['banner_image']) && $item['banner_image'] != '') ? strtolower($item['banner_image']) : "",
                'banner_icon' => (isset($item['banner_icon']) && $item['banner_icon'] != '') ? strtolower($item['banner_icon']) : "",
                'block1_title' => (isset($item['1_title']) && $item['1_title'] != '') ? strtolower($item['1_title']) : "",
                'block2_title' => (isset($item['2_title']) && $item['2_title'] != '') ? strtolower($item['2_title']) : "",
                'block3_title' => (isset($item['3_title']) && $item['3_title'] != '') ? strtolower($item['3_title']) : "",
                'block4_title' => (isset($item['4_title']) && $item['4_title'] != '') ? strtolower($item['4_title']) : "",
                'block1_url' => (isset($item['1_url']) && $item['1_url'] != '') ? strtolower($item['1_url']) : "",
                'block2_url' => (isset($item['2_url']) && $item['2_url'] != '') ? strtolower($item['2_url']) : "",
                'block3_url' => (isset($item['3_url']) && $item['3_url'] != '') ? strtolower($item['3_url']) : "",
                'block4_url' => (isset($item['4_url']) && $item['4_url'] != '') ? strtolower($item['4_url']) : "",
            ];
            array_push($offertabs, $data);
        }


        if(count($offertabs) < 0){
            $responsedata 	= ['offertabs' => [],  'message' => 'List for offertabs'];
            return Response::json($responsedata, 200);
        }

        $responsedata 	= ['offertabs' => $offertabs,  'message' => 'List for offertabs'];
        return Response::json($responsedata, 200);

    }



    public function getCategorytagsOfferings($city = 'mumbai'){

        $citydata 		=	City::where('slug', '=', strtolower($city))->first(array('name','slug'));
        if(!$citydata){
            return $this->responseNotFound('City does not exist');
        }
        $city_name 		= 	$citydata['name'];
        $city_id		= 	(int) $citydata['_id'];

        $categorytag_offerings = array();

        $categorytag_offerings = Findercategorytag::active()->with('offerings')->whereIn('cities', [$city_id])->orderBy('ordering')->get(array('_id','name','offering_header','slug','status','offerings'));

        if(count($categorytag_offerings) > 0){

            $categorytag_offerings = $categorytag_offerings->toArray();

            foreach ($categorytag_offerings as $key => $value) {

                $offerings = array();

                foreach ($value['offerings'] as $offerings_key => $offerings_value){

                    $offerings_value['key'] = $offerings_value['name'];

                    $offerings[] = $offerings_value;

                }

                $categorytag_offerings[$key]['key'] = $value['name'];
                $categorytag_offerings[$key]['offerings'] = $offerings;

            }
        }

        $responsedata 	= ['categorytag_offerings' => $categorytag_offerings,  'message' => 'List for Finder categorytags'];
        return Response::json($responsedata, 200);


    }



    // FOR MONSOON SALE
    public function getMonsoonSaleHomepage($city = 'mumbai', $cache = false){

        $citydata       =   City::where('slug', '=', $city)->first(array('name','slug'));

        if(!$citydata){
            return $this->responseNotFound('City does not exist');
        }


        $city_name      =   $citydata['name'];
        $city_id        =   (int) $citydata['_id'];

        $monsoon_sale_homepage = $cache ? Cache::tags('monsoon_sale_homepage')->has($city) : false;


        if(!$monsoon_sale_homepage){

            $fitmaniahomepageobj    =   Fitmaniahomepage::where('city_id', '=', $city_id)->first();
            if($fitmaniahomepageobj){

                $serviceids     =   (isset($fitmaniahomepageobj['serviceids']) && $fitmaniahomepageobj['serviceids'] != "") ? array_map('intval', explode(",", $fitmaniahomepageobj['serviceids']) ) : [];

                if(count($serviceids)> 0) {
                    $resp   =   array('status' => 400, 'ratecards' => [], 'message' => 'No Services Exist :)');
                }


                $unOrderServiceArr      =   [];
                $serviceArr             =   [];
                $services               =   Service::whereIn('_id', $serviceids )
                    ->with(
                        array('finder'=>function($query){
                            $query->select('_id', 'title', 'slug', 'coverimage', 'category_id','finder_coverimage', 'city_id', 'photos', 'contact', 'commercial_type', 'finder_type', 'what_i_should_carry', 'what_i_should_expect',
                                'total_rating_count', 'average_rating', 'detail_rating_summary_count', 'detail_rating_summary_average', 'reviews','info');
                        })
                    )
                    ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                    ->with(array('serviceratecards'=>function($query){$query->select('*')->where('hot_deals',"1");}))
                    ->get()
                    ->toArray();

                foreach ($services as $service){
                    $item    =      array_only($service, ['serviceratecards', 'finder', 'name', 'slug', '_id', 'what_i_should_carry', 'what_i_should_expect', 'workout_intensity', 'workout_tags', 'finder_id','location_id','servicecategory_id','servicesubcategory_id','workout_tags', 'address', 'body', 'timing','location']);
                    array_push($unOrderServiceArr,$item);
                }

                $serviceArr  = sorting_array($unOrderServiceArr, "_id", $serviceids, true);
//            return $serviceArr;

                // $allserviceids              =   array_unique(Ratecard::where("direct_payment_enable", "1")->lists("service_id"));
                // $allservices                =   Service::whereIn('_id', $allserviceids )
                // ->active()
                // ->where('city_id', $city_id)
                // ->with(array('serviceratecards'=>function($query){$query->select('*')->where('direct_payment_enable',"1");}))
                // ->get(['serviceratecards','_id','name','location_id'])->toArray();

                $locationclusters           =   Locationcluster::where('city_id', '=', $city_id)
                    ->active()
                    ->with(array('locations'=>function($query){$query->select('*');}))
                    ->get()->toArray();

                $locationclustersArr        =   [];

                foreach ($locationclusters as $key => $locationcluster) {

                    $locationids                =   array_unique(array_pluck($locationcluster['locations'],'_id'));
                    $cluster_ratecard_count     =   0;

                    // foreach ($allservices as $key => $allservice) {
                    //     $location_id = intval($allservice['location_id']);
                    //     if(in_array($location_id, $locationids) && isset($allservice['serviceratecards'])){
                    //         $service_ratecard_count     =   count($allservice['serviceratecards']);
                    //         $cluster_ratecard_count     =   $cluster_ratecard_count + $service_ratecard_count;
                    //     }

                    //     }// foreach


                    $item                       =   array_only($locationcluster, ['name', 'slug', '_id','cluster_ratecard_count']);
                    $locationids                =   array_unique(array_pluck($locationcluster['locations'],'_id'));

                    $item['locations']          =   pluck($locationcluster['locations'], ['name', 'slug', '_id']);
                    $item['ratecard_count']     =   $item['cluster_ratecard_count'];
                    array_push($locationclustersArr,$item);

                }


                // $categorys          =       Findercategory::active()->whereIn('_id', [5,6,7,8,11,12,32,35,43])->get(array('name','_id','slug'));
                $categorys          =       Servicecategory::active()->whereIn('_id', [3,19,65,1,2,4,5,111])->get(array('name','_id','slug'));

                $responsedata       =   [ 'city' => $citydata, 'services' => $serviceArr, 'locationclusters' => $locationclustersArr, 'categorys' => $categorys,  'message' => 'Monsoon Sale Services :)'];
                // return Response::json($responsedata, 200);
                Cache::tags('monsoon_sale_homepage')->put($city,$responsedata,Config::get('cache.cache_time'));

            }

        }

        return Response::json(Cache::tags('monsoon_sale_homepage')->get($city));

    }




    public function getHashes(){

//        $data   =   Input::json()->all();

        $env                    =       (Input::json()->get('env')) ? intval(Input::json()->get('env')) : 1;
        $txnid                  =       (Input::json()->get('txnid')) ? Input::json()->get('txnid') : "";
        $amount                 =       (Input::json()->get('amount')) ? Input::json()->get('amount') : "";
        $productinfo            =       (Input::json()->get('productinfo')) ? Input::json()->get('productinfo') : "";
        $firstname              =       (Input::json()->get('firstname')) ? Input::json()->get('firstname') : "";
        $email                  =       (Input::json()->get('email')) ? Input::json()->get('email') : "";
        $user_credentials       =       (Input::json()->get('user_credentials')) ? Input::json()->get('user_credentials') : "";
        $udf1                   =       (Input::json()->get('udf1')) ? Input::json()->get('udf1') : "";
        $udf2                   =       (Input::json()->get('udf2')) ? Input::json()->get('udf2') : "";
        $udf3                   =       (Input::json()->get('udf3')) ? Input::json()->get('udf3') : "";
        $udf4                   =       (Input::json()->get('udf4')) ? Input::json()->get('udf4') : "";
        $udf5                   =       (Input::json()->get('udf5')) ? Input::json()->get('udf5') : "";
        $offerKey               =       (Input::json()->get('offerKey')) ? Input::json()->get('offerKey') : "";
        $cardBin                =       (Input::json()->get('cardBin')) ? Input::json()->get('cardBin') : "";


        // For Production
//        if($env == 2){
//            $key = '0MQaQP';//'gtKFFx';
//            $salt = '13p0PXZk';//'eCwWELxi';
//        }else{
//            $key = '0MQaQP';
//            $salt = '13p0PXZk';
//        }

        // $firstname, $email can be "", i.e empty string if needed. Same should be sent to PayU server (in request params) also.
        $key = 'l80gyM';//'gtKFFx';
        $salt = 'QBl78dtK';//'eCwWELxi';

        $payhash_str            =   $key . '|' . checkNull($txnid) . '|' .checkNull($amount)  . '|' .checkNull($productinfo)  . '|' . checkNull($firstname) . '|' . checkNull($email) . '|' . checkNull($udf1) . '|' . checkNull($udf2) . '|' . checkNull($udf3) . '|' . checkNull($udf4) . '|' . checkNull($udf5) . '||||||' . $salt;
        $paymentHash            =   strtolower(hash('sha512', $payhash_str));
        $arr['payment_hash']    =   $paymentHash;

        $cmnNameMerchantCodes                   =   'get_merchant_ibibo_codes';
        $merchantCodesHash_str                  =   $key . '|' . $cmnNameMerchantCodes . '|default|' . $salt ;
        $merchantCodesHash                      =   strtolower(hash('sha512', $merchantCodesHash_str));
        $arr['get_merchant_ibibo_codes_hash']   =   $merchantCodesHash;

        $cmnMobileSdk                           =   'vas_for_mobile_sdk';
        $mobileSdk_str                          =   $key . '|' . $cmnMobileSdk . '|default|' . $salt;
        $mobileSdk                              =   strtolower(hash('sha512', $mobileSdk_str));
        $arr['vas_for_mobile_sdk_hash']         =   $mobileSdk;

        $cmnPaymentRelatedDetailsForMobileSdk1              =   'payment_related_details_for_mobile_sdk';
        $detailsForMobileSdk_str1                           =   $key  . '|' . $cmnPaymentRelatedDetailsForMobileSdk1 . '|default|' . $salt ;
        $detailsForMobileSdk1                               =   strtolower(hash('sha512', $detailsForMobileSdk_str1));
        $arr['payment_related_details_for_mobile_sdk_hash'] =   $detailsForMobileSdk1;

        //used for verifying payment(optional)
        $cmnVerifyPayment               =   'verify_payment';
        $verifyPayment_str              =   $key . '|' . $cmnVerifyPayment . '|'.$txnid .'|' . $salt;
        $verifyPayment                  =   strtolower(hash('sha512', $verifyPayment_str));
        $arr['verify_payment_hash']     =   $verifyPayment;

        if($user_credentials != NULL && $user_credentials != '')
        {
            $cmnNameDeleteCard              =   'delete_user_card';
            $deleteHash_str                 =   $key  . '|' . $cmnNameDeleteCard . '|' . $user_credentials . '|' . $salt ;
            $deleteHash                     =   strtolower(hash('sha512', $deleteHash_str));
            $arr['delete_user_card_hash']   =   $deleteHash;

            $cmnNameGetUserCard             =   'get_user_cards';
            $getUserCardHash_str            =   $key  . '|' . $cmnNameGetUserCard . '|' . $user_credentials . '|' . $salt ;
            $getUserCardHash                =   strtolower(hash('sha512', $getUserCardHash_str));
            $arr['get_user_cards_hash']     =   $getUserCardHash;

            $cmnNameEditUserCard = 'edit_user_card';
            $editUserCardHash_str = $key  . '|' . $cmnNameEditUserCard . '|' . $user_credentials . '|' . $salt ;
            $editUserCardHash = strtolower(hash('sha512', $editUserCardHash_str));
            $arr['edit_user_card_hash'] = $editUserCardHash;

            $cmnNameSaveUserCard = 'save_user_card';
            $saveUserCardHash_str = $key  . '|' . $cmnNameSaveUserCard . '|' . $user_credentials . '|' . $salt ;
            $saveUserCardHash = strtolower(hash('sha512', $saveUserCardHash_str));
            $arr['save_user_card_hash'] = $saveUserCardHash;

            $cmnPaymentRelatedDetailsForMobileSdk = 'payment_related_details_for_mobile_sdk';
            $detailsForMobileSdk_str = $key  . '|' . $cmnPaymentRelatedDetailsForMobileSdk . '|' . $user_credentials . '|' . $salt ;
            $detailsForMobileSdk = strtolower(hash('sha512', $detailsForMobileSdk_str));
            $arr['payment_related_details_for_mobile_sdk_hash'] = $detailsForMobileSdk;
        }


        if ($offerKey!=NULL && !empty($offerKey)) {
            $cmnCheckOfferStatus = 'check_offer_status';
            $checkOfferStatus_str = $key  . '|' . $cmnCheckOfferStatus . '|' . $offerKey . '|' . $salt ;
            $checkOfferStatus = strtolower(hash('sha512', $checkOfferStatus_str));
            $arr['check_offer_status_hash']=$checkOfferStatus;
        }


        if ($cardBin!=NULL && !empty($cardBin)) {
            $cmnCheckIsDomestic = 'check_isDomestic';
            $checkIsDomestic_str = $key  . '|' . $cmnCheckIsDomestic . '|' . $cardBin . '|' . $salt ;
            $checkIsDomestic = strtolower(hash('sha512', $checkIsDomestic_str));
            $arr['check_isDomestic_hash']=$checkIsDomestic;
        }

        $responsedata =  array('result'=>$arr);

        return Response::json($responsedata, 200);
    }



}