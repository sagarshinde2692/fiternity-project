<?php

//use Moa\API\Provider\ProviderInterface;


use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use App\Notification\CustomerNotification as CustomerNotification;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Utilities as Utilities;
use App\Services\RelianceService as RelianceService;
use App\Services\CouponService as CouponService;
class HomeController extends BaseController {


    protected $api_url = false;
    protected $debug = false;
    protected $client;
    protected $utilities;
    


    public function __construct(CustomerNotification $customernotification,Sidekiq $sidekiq, Utilities $utilities, RelianceService $relianceService,CouponService $couponService) {
        parent::__construct();
        $this->customernotification     =   $customernotification;
        $this->sidekiq = $sidekiq;
        $this->api_url = Config::get("app.url")."/";
        $this->utilities = $utilities;
        $this->initClient();
        $this->couponService = $couponService;
        $this->vendor_token = false;
        $this->relianceService = $relianceService;
        $vendor_token = Request::header('Authorization-Vendor');

        if($vendor_token){

            $this->vendor_token = true;
        }

        $this->kiosk_app_version = false;

        if($vendor_token){

            $this->vendor_token = true;

            $this->kiosk_app_version = (float)Request::header('App-Version');
        }
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
            // $category_finders 		=		Finder::whereIn('_id', $finder_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            //     ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            //     ->remember(Config::get('app.cachetime'))
            //     ->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
            //     ->groupBy('category.name')
            //     ->toArray();


            // $gym_finders_label 			= 		(isset($homepage['gym_finders_label'])) ? $homepage['gym_finders_label'] : "";
            // $yoga_finders_label 		= 		(isset($homepage['yoga_finders_label'])) ? $homepage['yoga_finders_label'] : "";
            // $dance_finders_label 		= 		(isset($homepage['dance_finders_label'])) ? $homepage['dance_finders_label'] : "";

            // array_set($popular_finders,  'gym_finders_label', $gym_finders_label);
            // array_set($popular_finders,  'yoga_finders_label', $yoga_finders_label);
            // array_set($popular_finders,  'dance_finders_label', $dance_finders_label);

            // $gym_finders_ids 		= 		array_map('intval', explode(",", $homepage['gym_finders'] ));
            // $yoga_finders_ids 		= 		array_map('intval', explode(",", $homepage['yoga_finders'] ));
            // $zumba_finders_ids 		= 		array_map('intval', explode(",", $homepage['zumba_finders'] ));


            // $gyms_finders 		=		Finder::active()->whereIn('_id', $gym_finders_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            //     ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            //     ->remember(Config::get('app.cachetime'))->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
            //     ->toArray();
            // $yoga_finders 		=		Finder::active()->whereIn('_id', $yoga_finders_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            //     ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            //     ->remember(Config::get('app.cachetime'))->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
            //     ->toArray();
            // $dance_finders 		=		Finder::active()->whereIn('_id', $zumba_finders_ids)->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            //     ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            //     ->remember(Config::get('app.cachetime'))->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count'))
            //     ->toArray();

            // array_set($popular_finders,  'gyms', $gyms_finders);
            // array_set($popular_finders,  'yoga', $yoga_finders);
            // array_set($popular_finders,  'dance', $dance_finders);


            $footer_block1_ids 		= 		array_map('intval', explode(",", $homepage['footer_block1_ids'] ));
            $footer_block2_ids 		= 		array_map('intval', explode(",", $homepage['footer_block2_ids'] ));
            $footer_block3_ids 		= 		array_map('intval', explode(",", $homepage['footer_block3_ids'] ));
            $footer_block4_ids 		= 		array_map('intval', explode(",", $homepage['footer_block4_ids'] ));
            $footer_block5_ids 		= 		array_map('intval', explode(",", $homepage['footer_block5_ids'] ));
            $footer_block6_ids 		= 		array_map('intval', explode(",", $homepage['footer_block6_ids'] ));
            Finder::$withoutAppends=true;
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

            $recent_blogs = Blog::where('status', '=', '1')->where('homepage', '1')->where('homepage_city_id', (string)$city_id)
            ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            // ->with('categorytags')
            ->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
            ->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
            ->orderBy('_id', 'desc')
            ->remember(Config::get('app.cachetime'))
            ->get(array('_id','author_id','category_id','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert', 'homepage', 'homepage_city_id'))
            ->take(4)->toArray();

            if(count($recent_blogs) < 4){

                $recent_blog_ids = array_column($recent_blogs, '_id');
                
                $common_blogs = Blog::where('status', '=', '1')->whereNotIn('_id', $recent_blog_ids)
                ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
                // ->with('categorytags')
                ->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
                ->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
                ->orderBy('_id', 'desc')
                ->remember(Config::get('app.cachetime'))
                ->get(array('_id','author_id','category_id','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
                ->take(4-count($recent_blog_ids))->toArray();

                $recent_blogs = array_merge($recent_blogs, $common_blogs);
            }


            // $recent_blogs	 		= 		Blog::where('status', '=', '1')
            //     ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
            //     // ->with('categorytags')
            //     ->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
            //     ->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
            //     ->orderBy('_id', 'desc')
            //     ->remember(Config::get('app.cachetime'))
            //     ->get(array('_id','author_id','category_id','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
            //     ->take(4)->toArray();

            // $collections 			= 	Findercollection::active()->where('city_id', '=', intval($citydata['_id']))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));
            // $campaigns=  [];
            // $campaigns[] = [
            //     'image'=> 'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/independance.png',
            //     'mob_image'=> 'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/independance_mob.png',
            //     'link'=>Config::get('app.website').'/'.$city.'/fitness?trials=1',
            //     'title'=>'Pay Per Session',
            //     'height'=>100,
            //     'width'=>375,
            //     'ratio'=>(float) number_format(100/375,2)
            // ];

            // switch($city){
            //     case "bangalore":
            //     $campaigns[] = [
            //         'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/bangalore-gold-web.png',
            //         'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/bangalore-gold-mob.png',
            //         'link'=>Config::get('app.website').'/golds-gym-bangalore',
            //         'title'=>'Pay Per Session',
            //         'height'=>100,
            //         'width'=>375,
            //         'ratio'=>(float) number_format(100/375,2)
            //     ];
            //     break;
            //     case "delhi":
            //     $campaigns[] = [
            //         'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/delhi-gold-web.png',
            //         'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/delhi-gold-mob.png',
            //         'link'=>Config::get('app.website').'/golds-gym-delhi',
            //         'title'=>'Pay Per Session',
            //         'height'=>100,
            //         'width'=>375,
            //         'ratio'=>(float) number_format(100/375,2)
            //     ];
            //     break;
            //     case "gurgaon":
            //     $campaigns[] = [
            //         'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/gurgaon-gold-web.png',
            //         'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/gurgaon-gold-mob.png',
            //         'link'=>Config::get('app.website').'/golds-gym-gurgaon',
            //         'title'=>'Pay Per Session',
            //         'height'=>100,
            //         'width'=>375,
            //         'ratio'=>(float) number_format(100/375,2)
            //     ];
            //     break;
            //     case "hyderabad":
            //     $campaigns[] = [
            //         'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/hyderabad-gold-web.png',
            //         'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/hyderabad-gold-mob.png',
            //         'link'=>Config::get('app.website').'/golds-gym-hyderabad',
            //         'title'=>'Pay Per Session',
            //         'height'=>100,
            //         'width'=>375,
            //         'ratio'=>(float) number_format(100/375,2)
            //     ];
            //     break;
            //     case "mumbai":
            //     $campaigns[] = [
            //         'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/mumbai-gold-web.png',
            //         'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/mumbai-gold-mob.png',
            //         'link'=>Config::get('app.website').'/golds-gym-mumbai',
            //         'title'=>'Pay Per Session',
            //         'height'=>100,
            //         'width'=>375,
            //         'ratio'=>(float) number_format(100/375,2)
            //     ];
            //     break;
            //     case "noida":
            //     $campaigns[] = [
            //         'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/noida-gold-web.png',
            //         'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/noida-gold-mob.png',
            //         'link'=>Config::get('app.website').'/golds-gym-noida',
            //         'title'=>'Pay Per Session',
            //         'height'=>100,
            //         'width'=>375,
            //         'ratio'=>(float) number_format(100/375,2)
            //     ];
            //     break;
            //     case "pune":
            //     $campaigns[] = [
            //         'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/pune-gold-web.png',
            //         'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/pune-gold-mob.png',
            //         'link'=>Config::get('app.website').'/golds-gym-pune',
            //         'title'=>'Pay Per Session',
            //         'height'=>100,
            //         'width'=>375,
            //         'ratio'=>(float) number_format(100/375,2)
            //     ];
            //     if(intval(date('d', time())) % 2 == 0){

            //         $campaigns[] = [
            //             'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/Multifit_Web%20banner.png',
            //             'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/Multifit_Mob%20and%20srp.png',
            //             'link'=>Config::get('app.website').'/multifit-pune',
            //             'title'=>'Pay Per Session',
            //             'height'=>100,
            //             'width'=>375,
            //             'ratio'=>(float) number_format(100/375,2)
            //         ];
            //     }else{
            //         array_splice($campaigns, count($campaigns)-1, 0, [[
            //             'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/Multifit_Web%20banner.png',
            //             'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/Multifit_Mob%20and%20srp.png',
            //             'link'=>Config::get('app.website').'/multifit-pune',
            //             'title'=>'Pay Per Session',
            //             'height'=>100,
            //             'width'=>375,
            //             'ratio'=>(float) number_format(100/375,2)
            //         ]]);
            //     }
                
            //     break;
            // }

            // if($city == 'mumbai'){

            //     $campaigns[] = [
            //         'image'=>'https://b.fitn.in/global/Homepage-branding-2018/Web-banners/yfc-mumbai-web.jpg',
            //         'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/yfc-mumbai-mob.jpg',
            //         'link'=>Config::get('app.website').'/your-fitness-club-mumbai',
            //         'title'=>'Your Fitness Club (YFC)',
            //         'height'=>100,
            //         'width'=>375,
            //         'ratio'=>(float) number_format(100/375,2)
            //     ];
            // }
            
            // $campaigns[] = [
            //     'image'=>'https://b.fitn.in/global/paypersession_branding/web_and_mobresponsive_banners/Homepage-pps.png',
            //     'mob_image'=>'https://b.fitn.in/global/paypersession_branding/web_and_mobresponsive_banners/Mob-homepage.png',
            //     'link'=>Config::get('app.website').'/pay-per-session',
            //     'title'=>'Pay Per Session',
            //     'height'=>100,
            //     'width'=>375,
            //     'ratio'=>(float) number_format(100/375,2)
            // ];

            // $campaigns[] = [
            //     'image'=>'https://b.fitn.in/global/Homepage-branding-2018/new-reward-web.png',
            //     'mob_image'=>'https://b.fitn.in/global/Homepage-branding-2018/Mob-banners/Rewards-MOB.png',
            //     'link'=>Config::get('app.website').'/rewards',
            //     'target'=>true,
            //     'title'=>'Rewards with every purchase',
            //     'height'=>100,
            //     'width'=>375,
            //     'ratio'=>(float) number_format(100/375,2)
            // ];

            $campaigns = $homepage['banners'];

            function cmp($a, $b)
            {
                return $a['order'] - $b['order'];
            }

            usort($campaigns, "cmp");

            $homedata 	= 	array(
                // 'popular_finders' => $popular_finders,
                'footer_finders' => $footer_finders,
                'recent_blogs' => $recent_blogs,
                'city_name' => $city_name,
                'city_id' => $city_id,
                "campaigns" => $campaigns,
                "spin_wheel_array"=>array_column(getSpinArray(), 'label1')
                // 'collections' => $collections

            );

            Cache::tags('home_by_city_v4')->put($city, $homedata, Config::get('cache.cache_time'));
        }

        $homedata = Cache::tags('home_by_city_v4')->get($city);

        $homedata['customer_home'] = null;

        $jwt_token = Request::header('Authorization');
        Log::info("Home Token");
        Log::info($jwt_token);
        Log::info("Home Token End");
        // if($jwt_token){
        //     $homedata['customer_home'] = $this->utilities->customerHome();
        // }

        return Response::json($homedata);
    }


    public function getSuccessMsg($type, $id){
        Log::info($_SERVER['REQUEST_URI']);
        $customer_id = "";
        $jwt_token = Request::header('Authorization');
        $device_type = Request::header('Device-Type');
        $app_version = Request::header('App-Version');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }

        $type       =   strtolower(trim($type));
        $order_type    = "";

        $loyaltySuccessMsg = null;

        if($type != "" && $id != ""){

        	if($type=='product') 
        		return $this->getProductSuccessMsg($id);
        	
            $booktrialItemArr   =   ["personaltrainertrial","manualtrial","manualautotrial","booktrialfree"];
            $orderItemArr       =   ["healthytiffintrail","healthytiffintrial","membershipwithpg","membershipwithoutpg","healthytiffinmembership","personaltrainermembership","booktrial","workoutsession","workout-session","booktrials"];
            $captureItemArr     =   ["manualmembership"];

            $itemData           =   [];
            
            if (in_array($type, $booktrialItemArr)){

                $itemData       =   Booktrial::customerValidation(customerEmailFromToken())->find(intval($id));
                
                
                //reliance section 
                
                
                if(isset($itemData['source'])&&$itemData['source']=='website'&&isset($itemData['rx_user'])&&$itemData['rx_user']==true)
                {
                	if(isset($itemData['rx_user_communication'])&&$itemData['rx_user_communication']==true)
                	{
                		Log::info(" Reliance request already processed for this trial.".print_r("",true));
                	}
                	else {
                	$relianceJson=[
                			"count"=>1,
                			"id"=>(int)$itemData['_id'],
                			"date"=>$itemData['created_at'],
                			"amount"=>(int)$itemData['amount'],
                			"phone"=>$itemData['customer_phone'],
                			"description"=>$itemData['service_name'],
                			"email"=>$itemData['customer_email'],
                			"type"=>'gym',
                			"value"=>"day",
                	];
                	
                	$sendResp=true;
                	if(isSet($itemData['finder_id'])&&$itemData['finder_id']!="")
                	{
                		$fdCat=Finder::where('_id',(int)$itemData['finder_id'])->first(['category_id']);
                		Log::info(" fdcat ".print_r($fdCat,true));
                		if(isSet($fdCat)&&$fdCat!=""&&isSet($fdCat['category_id'])&&$fdCat['category_id']!="")
                		{
                			$fcat=(int)$fdCat['category_id'];
                			Log::info(" fcat ".print_r($fcat,true));
                			$notSendCatList =  array('10', '26', '36', '40','45','46','47','52');
                			$studioCatList =  array('6', '7', '8', '9','11','12', '13', '14', '32','35','41', '43', '44', '48','49','50','51');
                			if (in_array($fcat, $notSendCatList))
                				$sendResp=false;
                				else if (in_array($fcat, $studioCatList))
                					$relianceJson['type']='studio';
                					else if($fcat==25)
                						$relianceJson['type']='dietplan';
                						else if($fcat==42)
                							$relianceJson['type']='tiffin';
                							else $relianceJson['type']='gym';
                							
                		}
                		else $sendResp=false;
                	}
                	else $sendResp=false;
                	
                	if($sendResp)
                	{
                		$relianceOutput=updateRelianceCommunication($relianceJson);
                		Log::info(" RELIANCE OUTPUT ".print_r($relianceOutput,true));
                		if($relianceOutput)
                		{Log::info(" RELIANCE OUTPUT ".print_r($relianceOutput,true));
                		$itemData->rx_user_communication= true;
                		$itemData->rx_success_url= (isset($relianceOutput)&&isset($relianceOutput['redirectURL'])&&$relianceOutput['redirectURL']!="")?$relianceOutput['redirectURL']:"";
                		}
                		else
                			$itemData->rx_user_communication= false;
                			
                	}
                	else 	$itemData->rx_user_communication= false;
                	$itemData->update();
                }
                }

                $dates = array('start_date', 'start_date_starttime', 'schedule_date', 'schedule_date_time', 'followup_date', 'followup_date_time','missedcall_date','customofferorder_expiry_date','auto_followup_date');
                $unset_keys = [];
                
                foreach ($dates as $key => $value){
                    if(isset($itemData[$value]) && $itemData[$value]==''){
                        // $itemData->unset($value);
                        array_push($unset_keys, $value);
                        
                    }
                }

                if(count($unset_keys)>0){
                    $itemData->unset($unset_keys);
                }

                $itemData       =   $itemData->toArray();

                $order_type = "booktrial_id";
            }

            if (in_array($type, $orderItemArr)) {

                $itemData = Order::customerValidation(customerEmailFromToken())->find(intval($id));

                if(empty($itemData)){
                    return ['status'=>400];
                }
                
                Log::info('book trials data',[$id]);
            
                if($itemData['studio_extended_validity']==true ){
                    Log::info('checking for studio extendrd validity order id',[$itemData['studio_extended_validity']]);
                    $extended_message = $itemData['studio_membership_duration']['num_of_days_extended'];
                    Log::info('checking for studio extendrd validity order id',[$itemData['studio_extended_validity_order_id']]);
                    if(($this->device_type=='ios' &&$this->app_version > '5.1.7') || ($this->device_type=='android' &&$this->app_version > '5.23')){
                        Log::info('checking for studio extendrd validity order id',[$itemData['studio_extended_validity']]);
                        $flexi_data = Config::get('extendedValidity.finder_banner_app');
                    }
                }
                
                if(isset($itemData['customer_source'])&&$itemData['customer_source']=='website'&&isset($itemData['rx_user'])&&$itemData['rx_user']==true)
                {
                	if(isset($itemData['rx_user_communication'])&&$itemData['rx_user_communication']==true)
                	{
                		Log::info(" Reliance request already processed for this order.".print_r("",true));
                	}
                	else {
                	$relianceJson=[
                			"count"=>1,
                			"id"=>(int)$itemData['_id'],
                			"date"=>$itemData['created_at'],
                			"amount"=>$itemData['amount_finder'],
                			"phone"=>$itemData['customer_phone'],
                			"description"=>$itemData['service_name'],
                			"email"=>$itemData['customer_email'],
                			"type"=>'gym',
                			"value"=>((isset($itemData['service_duration'])&&$itemData['service_duration']!="")?$itemData['service_duration']:"")
                	];
                	$sendResp=true;
                	if(isSet($itemData['finder_id'])&&$itemData['finder_id']!="")
                	{
                		$fdCat=Finder::where('_id',(int)$itemData['finder_id'])->first(['category_id']);
                		Log::info(" fdcat ".print_r($fdCat,true));
                		if(isSet($fdCat)&&$fdCat!=""&&isSet($fdCat['category_id'])&&$fdCat['category_id']!="")
                		{
                			$fcat=(int)$fdCat['category_id'];
                			Log::info(" fcat ".print_r($fcat,true));
                			$notSendCatList =  array('10', '26', '36', '40','45','46','47','52');
                			$studioCatList =  array('6', '7', '8', '9','11','12', '13', '14', '32','35','41', '43', '44', '48','49','50','51');
                			if (in_array($fcat, $notSendCatList))
                				$sendResp=false;
                				else if (in_array($fcat, $studioCatList))
                					$relianceJson['type']='studio';
                					else if($fcat==25)
                						$relianceJson['type']='dietplan';
                						else if($fcat==42)
                							$relianceJson['type']='tiffin';
                							else $relianceJson['type']='gym';
                							
                		}
                		else $sendResp=false;
                	}
                	else $sendResp=false;
                	
                	if($sendResp)
                	{
                		if($type=='healthytiffintrail'||$type=='healthytiffintrial')
                		{
                			$relianceJson['type']='tiffin';
                			$relianceJson['value']='trial';
                			$relianceJson['count']=1;
                		}
                		else
                		{
                			if($type=='healthytiffinmembership')
                				$relianceJson['type']='tiffin';
                				$servDur=((isset($itemData['service_duration'])&&$itemData['service_duration']!="")?$itemData['service_duration']:"");
                				$durr=(int)((isset($itemData['duration'])&&$itemData['duration']!="")?$itemData['duration']:"");
                				$durrType=((isset($itemData['duration_type'])&&$itemData['duration_type']!="")?$itemData['duration_type']:"");
                				
                				$duration_day=((isset($itemData['duration_day'])&&$itemData['duration_day']!="")?$itemData['duration_day']:"");
                				$duration=((isset($itemData['duration'])&&$itemData['duration']!="")?$itemData['duration']:"");
                				
                				
                			 if	(((preg_match_all('/\bday\b/i', $servDur, $matches))||(preg_match_all('/\bmeals\b/i', $servDur, $matches))||(preg_match_all('/\bmeal\b/i', $servDur, $matches))||(preg_match_all('/\bdays\b/i', $servDur, $matches))||(preg_match_all('/\bsessions\b/i', $servDur, $matches))||(preg_match_all('/\bsession\b/i', $servDur, $matches))||(preg_match_all('/\bMonths\b/i', $servDur, $matches))||(preg_match_all('/\bMonth\b/i', $servDur, $matches))||$servDur==''||(preg_match_all('/\byear/i', $servDur, $matches))||(preg_match_all('/\byears/i', $servDur, $matches))))
                				{
                					if(isSet($duration_day)&&$duration_day!='')
                					{
                						$duration_day=(int)$duration_day;
                						if($duration_day==360||$duration_day==365)
                						{
                							$relianceJson['count']=1;
                							$relianceJson['value']='year';
                						}
                						else if(($duration_day%30)==0)
                						{
                							$relianceJson['count']=(int)($duration_day/30);
                							if($relianceJson['count']==1)
                								$relianceJson['value']='month';
                								else $relianceJson['value']='months';
                								
                						}
                						else
                						{
                							$relianceJson['count']=$duration_day;
                							if($relianceJson['count']==1)
                								$relianceJson['value']='day';
                								else $relianceJson['value']='days';
                								
                						}
                					}
                					else if(isSet($duration)&&$duration!='')
                					{
                						//                 						$relianceJson['count']=(int)$duration;
                						
                						$duration=(int)$duration;
                						if($duration==360||$duration==365)
                						{
                							$relianceJson['count']=1;
                							$relianceJson['value']='year';
                						}
                						else if(($duration%30)==0)
                						{
                							$relianceJson['count']=(int)($duration/30);
                							if($relianceJson['count']==1)
                								$relianceJson['value']='month';
                								else $relianceJson['value']='months';
                								
                						}
                						else
                						{
                							$relianceJson['count']=$duration;
                							if($relianceJson['count']==1)
                								$relianceJson['value']='day';
                								else $relianceJson['value']='days';
                								
                						}
                						
                					}
                					else
                					{
                						if($relianceJson['count']==1)$relianceJson['value']='day';
                						else $relianceJson['value']='days';
                					}
                					
                				}
                				
                				  else
                				  {
                				  	$relianceJson['value']='day';
                				  	if(isSet($duration_day)&&$duration_day!='')
                				  	{
                				  		$relianceJson['count']=(int)$duration_day;
                				  	}
                				  	if($relianceJson['count']==1)$relianceJson['value']='day';
                				  	else $relianceJson['value']='days';
                				  	
                				  	
                				  }
                		}
                		$relianceOutput=updateRelianceCommunication($relianceJson);
                		Log::info(" RELIANCE OUTPUT ".print_r($relianceOutput,true));
                		if($relianceOutput)
                		{
                			$itemData->rx_user_communication= true;
                			$itemData->rx_success_url= (isset($relianceOutput)&&isset($relianceOutput['redirectURL'])&&$relianceOutput['redirectURL']!="")?$relianceOutput['redirectURL']:"";
                		}
                		else
                			$itemData->rx_user_communication= false;
                			
                			
                			$relianceOutput=updateRelianceCommunication($relianceJson);
                			Log::info(" RELIANCE OUTPUT ".print_r($relianceOutput,true));
                			if($relianceOutput)
                			{
                				$itemData->rx_user_communication= true;
                				$itemData->rx_success_url= (isset($relianceOutput)&&isset($relianceOutput['redirectURL'])&&$relianceOutput['redirectURL']!="")?$relianceOutput['redirectURL']:"";
                			}
                			else
                				$itemData->rx_user_communication= false;
                				
                	}
                	else $itemData->rx_user_communication= false;
                	
                	
                	$itemData->update();
                }
                }
                
                
                
                
                
                
                

                $dates = array('followup_date','last_called_date','preferred_starting_date', 'called_at','subscription_start','start_date','start_date_starttime','end_date', 'order_confirmation_customer');
                $unset_keys = [];
                
                foreach ($dates as $key => $value){
                    if(isset($itemData[$value]) && $itemData[$value]==''){
                        // $itemData->unset($value);
                        array_push($unset_keys, $value);
                    }
                }

                if(count($unset_keys)>0){
                    $itemData->unset($unset_keys);
                }

                $itemData       =   $itemData->toArray();

                $order_type = "order_id";

            }

            if (in_array($type, $captureItemArr)) {
                $itemData = Capture::find($id)->toArray();

                $order_type = "capture_id";
            }

            $finder_name = "";
            $finder_location = "";
            $finder_address = "";
            $all_options_url = "";
            //return 123455;
            if(isset($itemData['finder_id']) && $itemData['finder_id'] != ""){

                $finder = Finder::with(array('city'=>function($query){$query->select('name','slug');}))->with(array('location'=>function($query){$query->select('name','slug');}))->find((int)$itemData['finder_id'],array('_id','title','location_id','contact','lat','lon','manual_trial_auto','city_id','brand_id'));
                
                if(isset($finder['title']) && $finder['title'] != ""){
                    $finder_name = ucwords($finder['title']);
                }

                if(isset($finder['location']['name']) && $finder['location']['name'] != ""){
                    $finder_location = ucwords($finder['location']['name']);
                }

                if(isset($finder['contact']['address']) && $finder['contact']['address'] != ""){
                    $finder_address = $finder['contact']['address'];
                }

                if(isset($finder['city']['slug']) && $finder['city']['slug'] != "" && isset($finder['location']['slug']) && $finder['location']['slug'] != ""){
                    $all_options_url = "/".$finder['city']['slug']."/".$finder['location']['slug'];
                }

            }

            if(isset($itemData['finder_name']) && $itemData['finder_name'] != ""){
                $finder_name = $itemData['finder_name'];
            }

            if(isset($itemData['finder_location']) && $itemData['finder_location'] != ""){
                $finder_location = $itemData['finder_location'];
            }

            if($type == 'manualtrial'){
                if(isset($finder['manual_trial_auto']) && $finder['manual_trial_auto'] == "1"){
                    $type = "manualautotrial";
                }
            }

            $item           =   $itemData;
            $service_name    =   (isset($itemData) && isset($itemData['service_name'])) ? ucwords($itemData['service_name']) : "";
            $schedule_date  =   (isset($itemData['schedule_date']) && $itemData['schedule_date'] != "") ? date(' jS F\, Y \(l\) ', strtotime($itemData['schedule_date'])) : "";
            $schedule_slot  =   (isset($itemData['schedule_slot']) && $itemData['schedule_slot'] != "") ? $itemData['schedule_slot'] : "";
            $service_duration = (isset($itemData['service_duration_purchase']) && $itemData['service_duration_purchase'] != "") ? $itemData['service_duration_purchase'] : "";
            $preferred_starting_date = (isset($itemData['preferred_starting_date'])) ? $itemData['preferred_starting_date'] : "";
            $serviceDurArr = array_map('trim',explode("-",$service_duration));

            $header     =   "Congratulations!";
            $note       =   "Note: If you face any issues or need assistance for the  session - please call us on 022-61094444 and we will resolve it immediately";
            $icon_path  =   "https://b.fitn.in/iconsv1/success-pages/";
            $show_invite = false;
            $id_for_invite = (int) $id;
            $end_point = "";
            $start_time = (isset($itemData['start_time']) && $itemData['start_time'] != "") ? $itemData['start_time'] : "";
            
            $show_other_vendor = false;
            $why_buy = false;
            $checkin_response = !empty($itemData['checkin_response']) ? $itemData['checkin_response'] : null;

            if(isset($item['type']) && $item['type']=='workout-session' && $device_type && $app_version && in_array($device_type, ['android', 'ios']) && $app_version > '4.4.3'){

                $header = "BOOKING SUCCESSFUL!";
                
                $subline = '<p style="align:center">Your '.$service_name.' session at '.$finder_name.' is confirmed on '.$schedule_date.' at '.$start_time.' <br><br>Activate your session through FitCode provided by '.$finder_name.' or by scanning the QR code available there. FitCode helps you mark your attendance that let\'s you earn cashbacks.'."<br><br>Keep booking sessions at ".$item['finder_name']." without buying a membership and earn rewards on your every workout";

                if(!empty($item['coupon_flags']['cashback_100_per']) && ((isset($item['customer_quantity']) && $item['customer_quantity'] == 1) || empty($item['customer_quantity']) )){
                    $subline .= "<br><br> Congratulations on receiving your instant cashback. Make the most of the cashback by booking multiple workout sessions on Fitternity App for yourself as well as your friends & family without any restriction on spend value";
                }

                if(!empty($item['pass_order_id'])){
                    $subline = '<p style="align:center">Your '.$service_name.' session at '.$finder_name.' is confirmed on '.$schedule_date.' at '.$start_time;
                }

                if(!empty($item['diwali_mixed_reward'])){
                    $subline .= "<br><br> Congratulations on celebrating a Fitwali Diwali with Fitternity. Your Fitaka Diwali Hamper will reach your inbox soon.";
                }


                if(($this->device_type =='ios' && $this->app_version >= '5.2.4') || ($this->device_type =='android' && $this->app_version >= '5.31')){

                    $finder_category = !empty($item['servicecategory_id']) ? ($item['servicecategory_id'] ==5 ? 'gym' : 'studio'): 'gym / studio';

                    $steps = Config::get('paypersession.pps_booking_success_message');

                    $subline = '<p style="align:center">Your '.$service_name.' session at '.$finder_name.' is confirmed on '.$schedule_date.' at '.$start_time;

                    if(!empty($item['coupon_flags']['cashback_100_per']) && ((isset($item['customer_quantity']) && $item['customer_quantity'] == 1) || empty($item['customer_quantity']) )){
                        $subline .= "<br><br> Congratulations on receiving your instant cashback. Make the most of the cashback by booking multiple workout sessions on Fitternity App for yourself as well as your friends & family without any restriction on spend value";
                    }

                    if(!empty($item['first_session_free'])){
                        $steps = Config::get('paypersession.trial_booking_success_message');
                    }

                    if(!empty($item['pass_order_id'])){
                        $steps =  Config::get('pass.booking_using_pass_success_message');
                    }

                }

                if(isset($item['pay_later']) && $item['pay_later']){
                    $subline .='<br><br>Attend and pay later to earn Cashback!</p>';
                }else{
                    $subline .= '</p>';
                }

                if(!empty($item['finder_flags']['mfp']) && $item['finder_flags']['mfp']){
                    $subline = '<p style="align:center">Your '.$service_name.' session at '.$finder_name.' is confirmed on '.$schedule_date.' at '.$start_time.' ';  
                }

                $streak_items = [];

                foreach(Config::get('app.streak_data') as $value){

                    array_push($streak_items, ['title'=>$value['cashback'].'%', 'value'=>$value['number'].' Sessions']);

                }
                 
                $streak = [
                    'header'=>'Attend More Earn More',
                    'items'=>$streak_items
                ];

                if(!empty($item['corporate_id']) && empty($item['external_reliance'])){
                    $subline = '<p style="align:center">Your '.$service_name.' session at '.$finder_name.' is confirmed on '.$schedule_date.' at '.$start_time.' <br><br>Activate your session through FitCode provided by '.$finder_name.' or by scanning the QR code available there and earn '.$this->relianceService->getStepsByServiceCategory($item['servicecategory_id']).' steps. Session activation also helps you earn cashback into your Fitternity Wallet.';

                    if(!empty($item['pass_order_id'])){
                        $subline  = '<p style="align:center">Your '.$service_name.' session at '.$finder_name.' is confirmed on '.$schedule_date.' at '.$start_time.' <br><br>Activate your session through FitCode provided by '.$finder_name.' or by scanning the QR code available there and earn '.$this->relianceService->getStepsByServiceCategory($item['servicecategory_id']).' steps.';
                    }
                }

                $steps_count = 0;
                if((($this->device_type =='ios' && $this->app_version >= '5.2.4') || ($this->device_type =='android' && $this->app_version >= '5.31')) && !empty($item['corporate_id']) && empty($item['external_reliance'])){

                    $subline = '<p style="align:center">Your '.$service_name.' session at '.$finder_name.' is confirmed on '.$schedule_date.' at '.$start_time;

                    $steps = Config::get('paypersession.pps_booking_success_message_corporate');
                    $steps_count = $this->relianceService->getStepsByServiceCategory($item['servicecategory_id']);

                    if(!empty($item['first_session_free'])){
                        $steps = Config::get('paypersession.trial_booking_success_message_corporate');
                    }
                    
                    if(!empty($item['pass_order_id'])){
                        $steps = Config::get('pass.booking_using_pass_success_message_corporate');
                    }
                }
                
                $response = [
                    'status'=>200,
                    'image'=>'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png',
                    'header'=>$header,
                    'subline'=>$subline,
                    'streak'=>$streak,
                    'conclusion'=>$this->getConclusionData(),
                    'feedback'=>$this->getFeedbackData(),
                    'order_type'=>$order_type,
                    'id'=>$id
                ];

                if(!empty($item['finder_flags']['mfp']) && $item['finder_flags']['mfp']){
                    unset($response['streak']);
                }
                
                if(!empty($steps)){

                    foreach($steps['data'] as &$value){
                        $value = strtr($value, ['finder_category'=> $finder_category, 'steps_count'=> $steps_count]);
                    }

                    $response['steps'] = $steps;
                }

                if(!empty($item['type']) && $item['type']=='workout-session' && empty($item['pass_order_id'])){
                    
                    $profile_completed = $this->utilities->checkOnepassProfileCompleted(null, $customer_id);
                    empty($profile_completed) ? $response['personalize'] =  'Personalize your session': null;
                }
                if((isset($item['extended_validity_order_id']) || isset($item['pass_order_id'])) && (($device_type=='android' && $app_version <= '5.17') || ($device_type=='ios' && $app_version <= '5.1.4'))){
                    $response['streak']['header'] = '';
                    $response['streak']['items'] = [];
                }
                if((isset($item['extended_validity_order_id']) || isset($item['pass_order_id'])) && (($device_type=='android' && $app_version > '5.17') || ($device_type=='ios' && $app_version > '5.1.4'))){
                    unset($response['streak']);
                }
                
                if(!empty($finder) && isset($finder['brand_id'])){
                    $response['brand_id'] = !empty($finder['brand_id']);                    
                }
                
                if(!empty($flexi_data)){
                    $response['flexi_data']["popup_data"] = $flexi_data;                    
                    $response['flexi_data']["header"] = "You have purchased Flexi Membership";
                    $response['flexi_data']["button_title"] = "Know more";
                }
                
                if(!empty($customer_id)){
                    
                    $customer = Customer::find($customer_id, ['loyalty']);
                    
                    // if(!empty($customer['loyalty'])){
                        // $response['milestones'] = $this->utilities->getMilestoneSection();
                    // }
                    
                }
                
                
                // if(empty($item['pass_order_id']) && !empty($item['loyalty_registration']) && $this->utilities->sendLoyaltyCommunication($item)){
                //     $response['fitsquad'] = $this->utilities->getLoyaltyRegHeader();
                //     $cashback_type_map = Config::get('app.cashback_type_map');
                //     $response['fitsquad_type'] = !empty($item['finder_flags']['reward_type']) ?  $item['finder_flags']['reward_type'] : 2;
                //     $response['fitsquad_sub_type'] = !empty($item['finder_flags']['cashback_type']) ?  $cashback_type_map[strval($item['finder_flags']['cashback_type'])] : null;
                // }
                
                if(!empty($item['qrcodepayment'])){
                    unset($response['subline']);
                    if(!empty($checkin_response)){
                        $response['header'] .= "\n ".$checkin_response['header'];
                        $response['subline'] ='<p>'.$checkin_response['sub_header_2'].'</p>';
                    } 
                }
                
                if(isset($item['pay_later']) && $item['pay_later'] && $item['status'] == '1'){
                    if(!empty($response['fitsquad'])){
                        unset($response['fitsquad']);
                    }
                    unset($response['conclusion']);
                    unset($response['feedback']);
                    $response['header'] = 'Payment Successful';
                    // $response['subline'] = 'Your payment for '.$service_name.' session at '.$finder_name.' is successful';
                    $response['subline'] = 'Your payment for '.$service_name.' session at '.$finder_name.' for '.$schedule_date.' at '.$schedule_slot.' is successful. Keep booking, reach milestones & earn rewards';
                }
                
                if(!empty($item['pass_order_id']) && (($this->device_type =='ios' && $this->app_version >= '5.2.4') || ($this->device_type =='android' && $this->app_version >= '5.31'))){
                    unset($response['feedback']);
                    unset($response['conclusion']);
                }
            
                $response['branch_obj'] = $this->utilities->branchIOData($itemData);

                return $response;
            }

            switch ($type) {

                case 'booktrialfree':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider. Make sure you buy membership through us for lowest price guarantee. Check the sms and email shared with you for more details.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-profile.png','text'=>'Manage this booking through your User Profile'],
                        ['icon'=>$icon_path.'flash-code.png','text'=>'Flash the code at the studio to access your session'],
                        ['icon'=>$icon_path.'low-price.png','text'=>'Get lowest price guarantee to buy membership'],
                        ['icon'=>$icon_path.'choose-reward.png','text'=>'Choose exciting rewards when you buy'],
                    ];
                    $show_invite = true;
                    $end_point = "invitefortrial";
                    
                    $header = "Booking Confirmed";
                    $show_other_vendor = true;
                    $why_buy = true;
                    break;

                case 'booktrial':
                case 'booktrials':
                    $subline = "Thank you for choosing Fitternity as your preferred fitness provider. Make sure you buy membership through us for lowest price guarantee. Check the sms and email shared with you for more details.";
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
                    
                    $header = "Booking Confirmed";
                    $show_other_vendor = true;
                    $why_buy = true;
                    break;
                case 'workoutsession':
                case 'workout-session':
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
                    
                    $header = "Booking Confirmed";
                    $show_other_vendor = true;
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
                    
                    
                    $header = "Booking Confirmed";
                    $show_other_vendor = true;
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
                    
                    
                    $header = "Request Confirmed";
                    $show_other_vendor = true;
                    $why_buy = true;
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
                    
                    
                    $header = "Request Confirmed";
                    $show_other_vendor = true;
                    break;
                case 'healthytiffintrial':
                case 'healthytiffintrail':
                    $subline = "Your Trial request at $finder_name has been received. Please expect a revert shortly.";
                    $steps = [
                        ['icon'=>$icon_path.'you-are-here.png','text'=>'You are Here'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'Subscription details are shared on an email to you'],
                        ['icon'=>$icon_path.'get-details.png','text'=> $finder_name.' will get in touch with you'],
                        ['icon'=>$icon_path.'manage-booking.png','text'=>'Your meal will be delivered basis the specifications'],
                    ];
                    
                    
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
                    
                    
                    break;
            }
            
            if($this->utilities->checkCorporateLogin()){
                    $subline = "Customer will be sent an email and an sms confirmation with the subscription code. Same will be marked to vg@fitmein.in";
            }

            if(count($item) < 0){
                $item = null;
            }

            $popup_message = "";
            if(isset($itemData['cashback_amount'])){

                $popup_message = "Rs ".$itemData['cashback_amount']." FitCash has been added to your wallet";
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
            }else{

                $customer = Customer::find((int)$item['customer_id']);
            }

            $customer_auto_register = false;

            if($customer && isset($customer['ishulluser']) && $customer['ishulluser'] == 1){
                $customer_auto_register = true;
            }

            $near_by_vendor = [];

            $category_array = [
                "swimming"=>["category"=>"swimming","count"=>0],
                "healthy_tiffins"=>["category"=>"healthy tiffins","count"=>0]
            ];

            $city_name = "";

            if(isset($item['city_id']) && $item['city_id'] != ""){

                $city = City::find((int)$item['city_id']);

                if($city){
                    $city_name  = ucwords($city->name);
                }
            }

            if(isset($finder) && isset($finder['lat']) && isset($finder['lon'])){

                $lat = $finder['lat'];
                $lon = $finder['lon'];

                $near_by_type = ["membershipwithpg","membershipwithoutpg","manualmembership"];

                if(!in_array($type,$near_by_type)){

                    $near_by_vendor_request = [
                        "offset" => 0,
                        "limit" => 6,
                        "radius" => "3km",
                        "category"=>"",
                        "lat"=>$lat,
                        "lon"=>$lon,
                        "city"=>strtolower($city_name),
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
                          //"offerings",
                          // "photos",
                          // "servicelist",
                          "slug",
                          "name",
                          "id",
                          // "total_rating_count",
                          // "vendor_type"
                        ]
                    ];

                    $near_by_vendor = geoLocationFinder($near_by_vendor_request);

                    //echo"<pre>";print_r($near_by_vendor);exit;
                }

                if($fitcash_plus > 0){

                    foreach ($category_array as $key => $value) {

                         $finder_request = [
                            "offset" => 0,
                            "limit" => 100,
                            "radius" => "3km",
                            "category"=>newcategorymapping($value["category"]),
                            "lat"=>$lat,
                            "lon"=>$lon,
                            "city"=>strtolower($city_name),
                            "keys"=>[
                              // "average_rating",
                              // "business_type",
                              // "categorytags",
                              // "commercial_type",
                              // "contact",
                              //"coverimage",
                              // "distance",
                              // "facilities",
                              // "geolocation",
                              //"location",
                              // "locationtags",
                              // "multiaddress",
                              // "offer_available",
                              // "offerings",
                              // "photos",
                              // "servicelist",
                              //"slug",
                              //"title",
                              "id",
                              // "total_rating_count",
                              // "vendor_type"
                            ]
                        ];

                        $category_array[$key]['count'] = count(geoLocationFinder($finder_request));

                    }
                }

            }    

            $poc = $poc_name = $poc_number = "";

            if(isset($item['finder_poc_for_customer_name']) && $item['finder_poc_for_customer_name'] != ""){
                $poc_name = ucwords($item['finder_poc_for_customer_name']);
            }

            if(isset($item['finder_poc_for_customer_no']) && $item['finder_poc_for_customer_no'] != ""){
                $poc_number = " (".$item['finder_poc_for_customer_no'].")";
            }

            $poc = $poc_name.$poc_number;

            $booking_details = [];

            $position = 0;

            // $booking_details_data["booking_id"] = ['field'=>'SUBSCRIPTION CODE','value'=>(string)$item['_id'],'position'=>$position++];
            
            if(isset($item['extended_validity']) && $item['extended_validity']){ 
                $booking_details_data["validity"] = ['field'=>'VALIDITY','value'=>(!empty($item['ratecard_flags']['unlimited_validity']) ? "Unlimited Validity" :  $serviceDurArr[1]),'position'=>$position++];
            }

            if(in_array($type,["healthytiffintrail","healthytiffintrial","membershipwithpg","membershipwithoutpg","healthytiffinmembership","personaltrainermembership"])){
                $booking_details_data["finder_name_location"] = ['field'=>'MEMBERSHIP BOUGHT AT','value'=>$finder_name.", ".$finder_location,'position'=>$position++];
            }

            if(in_array($type,["personaltrainertrial","manualtrial","manualautotrial","booktrialfree","booktrial","workoutsession","workout-session","booktrials"])){
                $booking_details_data["finder_name_location"] = ['field'=>'BOOKING AT','value'=>$finder_name.", ".$finder_location,'position'=>$position++];
            }

            $booking_details_data["service_name"] = ['field'=>'SERVICE NAME','value'=>$service_name,'position'=>$position++];

            $booking_details_data["service_duration"] = ['field'=>'SERVICE DURATION','value'=>$service_duration,'position'=>$position++];
            
            $booking_details_data["start_date"] = ['field'=>'START DATE','value'=>'-','position'=>$position++];

            $booking_details_data["start_time"] = ['field'=>'START TIME','value'=>'-','position'=>$position++];

            if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && isset($finder['brand_id']) && (($finder['brand_id'] == 66 && $finder['city_id'] == 3) || $finder['brand_id'] == 88)){

                $booking_details_data["price"] = ['field'=>'AMOUNT','value'=>'Free','position'=>$position++];

            }else{

                $booking_details_data["price"] = ['field'=>'AMOUNT','value'=>'Free Via Fitternity','position'=>$position++];
            }

            $booking_details_data["address"] = ['field'=>'ADDRESS','value'=>'','position'=>$position++];            
            
            $booking_details_data["amount_paid"] = ['field'=>'AMOUNT PAID','value'=>'','position'=>$position++];

            if($poc != ""){ 
                $booking_details_data["poc"] = ['field'=>'POINT OF CONTACT','value'=>$poc,'position'=>$position++];
            }

            if(isset($item["reward_info"]) && $item["reward_info"] != ""){

                if($item["reward_info"] == 'Cashback'){
                    $booking_details_data["reward"] = ['field'=>'REWARD','value'=>$item["reward_info"],'position'=>$position++];
                }else{
                    $booking_details_data["reward"] = ['field'=>'REWARD','value'=>$item["reward_info"]." (Avail it from your Profile)",'position'=>$position++];
                }
            }

            /*if(isset($item["membership"]) && !empty($item["membership"])){

                if(isset($item["membership"]["cashback"]) && $item["membership"]["cashback"]){
                    $booking_details_data["reward"] = ['field'=>'PREBOOK REWARD','value'=>'Cashback','position'=>$position++];
                }

                if(isset($item["membership"]["reward"]) && isset($item["membership"]["reward"]["title"])){
                    $booking_details_data["reward"] = ['field'=>'PREBOOK REWARD','value'=>$item["membership"]["reward"]["title"],'position'=>$position++];
                }
            }*/

            if(isset($item["membership"]) && !empty($item["membership"]) && empty($customer['loyalty']['brand_loyalty'])){

                if(isset($item["membership"]['cashback']) && $item["membership"]['cashback'] === true){

                    $booking_details_data["reward"] = ['field'=>'PREBOOK REWARD','value'=>'Cashback','position'=>$position++];
                }

                if(isset($item["membership"]["reward_ids"]) && isset($item["membership"]["reward_ids"]) && !empty($item["membership"]["reward_ids"])){

                    $reward_id = $item["membership"]["reward_ids"][0];

                    $reward = Reward::find($reward_id,['title']);

                    if($reward){

                        $booking_details_data["reward"] = ['field'=>'PREBOOK REWARD','value'=>$reward['title'],'position'=>$position++];
                    }
                }

            }

            $booking_details_data["group_id"] = ['field'=>'GROUP ID','value'=>'','position'=>$position++];

            if(isset($item['start_date']) && $item['start_date'] != ""){
                $booking_details_data['start_date']['value'] = date('D, d M Y',strtotime($item['start_date']));
            }

            if(isset($item['schedule_date']) && $item['schedule_date'] != ""){
                $booking_details_data['start_date']['value'] = date('D, d M Y',strtotime($item['schedule_date']));
            }

            if(isset($item['preferred_starting_date']) && $item['preferred_starting_date'] != ""){
                $booking_details_data['start_date']['value'] = date('D, d M Y',strtotime($item['preferred_starting_date']));
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
                $booking_details_data['price']['value']= "Rs. ".(string)(isset($item['amount_customer']) ? $item['amount_customer']: $item['amount_finder']);
            }

            if(isset($item['payment_mode']) && $item['payment_mode'] == "cod"){
                $booking_details_data['price']['value']= "Rs. ".(string)$item['amount']." (Cash Pickup)";
            }

            if(isset($item['myreward_id']) && $item['myreward_id'] != ""){
                $booking_details_data['price']['value']= "Free Via Fitternity";
            }

            // if(isset($item['code']) && $item['code'] != ""){
            //     $booking_details_data['booking_id']['value'] = $item['code'];
            // }

            // if(in_array($type,["booktrialfree"])){

            //     if(isset($item['code']) && $item['code'] != ""){
            //         $booking_details_data['booking_id']['value'] = $item['code'].' (Share it at Gym/Studio to get Fitcode)';
            //     }

            // }

            // if(in_array($type, ["booktrial","workoutsession","workout-session","booktrials"])){

            //     if(isset($item['booktrial_id']) && $item['booktrial_id'] != ""){

            //         $order_booktrial = Booktrial::customerValidation(customerEmailFromToken())->find(intval($item['booktrial_id']));

            //         if(isset($order_booktrial['code'])){
                        
            //             $booking_details_data['booking_id']['value'] = $order_booktrial['code'];

            //             if(in_array($type, ["booktrial","booktrials"])){

            //                 $booking_details_data['booking_id']['value'] = $order_booktrial['code'].' (Share it at Gym/Studio to get Fitcode)';
            //             }
                    
            //         }

            //     }

            // }

            if(isset($item['type']) && $item['type'] == 'memberships'){

                if(isset($item['part_payment']) && $item['part_payment']){

                    $header= "Membership reserved";

                    if($item['amount']){

                        $booking_details_data['amount_paid']['value'] = "Rs. ".(string)$item['amount'];

                        if($item['wallet_amount']){
                            $booking_details_data['amount_paid']['value'] = "Rs. ".($item['amount']+$item['wallet_amount'])." (Rs. ".$item['remaining_amount']." to be Paid at Gym/Studio)";
                        }

                    }else{

                        $booking_details_data['amount_paid']['value'] = "Rs. ".(string)$item['wallet_amount'] . " Paid via Fitcash+";
                    }

                }/*else{

                    $booking_details_data['amount_paid']['field'] = "PAYMENT SUMMARY";

                    $booking_details_data['amount_paid']['value'] = " Rs. ".(string)$item['amount_finder']." Base Amount";

                    if(isset($item['convinience_fee']) && $item['convinience_fee'] > 0){

                        $booking_details_data['amount_paid']['value'] .= "<br/>+Rs. ".$item['convinience_fee']." Convenience Fee";
                    }

                    if(isset($item['wallet_amount']) && $item['wallet_amount'] > 0){

                        $booking_details_data['amount_paid']['value'] .= "<br/>-Rs. ".$item['wallet_amount']." Fitcash Applied";
                    }

                    if(isset($item['coupon_discount_amount']) && $item['coupon_discount_amount'] > 0){

                        $booking_details_data['amount_paid']['value'] .= "<br/>-Rs. ".$item['coupon_discount_amount']." Coupon Discount";
                    }

                    if(isset($item['customer_discount_amount']) && $item['customer_discount_amount'] > 0){

                        $booking_details_data['amount_paid']['value'] .= "<br/>-Rs. ".$item['customer_discount_amount']." Corporate Discount";
                    }

                    if(isset($item['app_discount_amount']) && $item['app_discount_amount'] > 0){

                        $booking_details_data['amount_paid']['value'] .= "<br/>-Rs. ".$item['app_discount_amount']." App Discount";
                    }

                    $booking_details_data['amount_paid']['value'] .= "<br/> Rs. ".$item['amount']." Paid";

                }*/
            }

            if($finder_address != ""){
                $booking_details_data['address']['value'] = $finder_address;
            }
            
            if(in_array($type,["healthytiffintrail","healthytiffintrial","healthytiffinmembership"])){

                if(isset($item['customer_address']) && $item['customer_address'] != ""){
                    $booking_details_data['address']['value'] = $item['customer_address'];
                }

            }else{

                if($finder_address != ""){
                    $booking_details_data['address']['value'] = $finder_address;
                }
                if(isset($item['finder_address']) && $item['finder_address'] != ""){
                    $booking_details_data['address']['value'] = $item['finder_address'];
                }
            }

            if(isset($booking_details_data['address']['value'])){

                $booking_details_data['address']['value'] = str_replace("  ", " ",$booking_details_data['address']['value']);
                $booking_details_data['address']['value'] = str_replace(", , ", "",$booking_details_data['address']['value']);
            }

            if(in_array($type, ['manualtrial','manualautotrial','manualmembership'])){
                $booking_details_data["start_date"]["field"] = "PREFERRED DATE";
                $booking_details_data["start_time"]["field"] = "PREFERRED TIME";
                $booking_details_data["price"]["value"] = "";
            }

            if(in_array($type, ['booktrialfree','booktrial','workoutsession'])){
                $booking_details_data["start_date"]["field"] = "DATE";
                $booking_details_data["start_time"]["field"] = "TIME";
                $booking_details_data["service_duration"]["value"] = "1 Session";
            }

            if(isset($item['preferred_day']) && $item['preferred_day'] != ""){
                $booking_details_data['start_date']['field'] = 'PREFERRED DAY';
                $booking_details_data['start_date']['value'] = $item['preferred_day'];
            }

            if(isset($item['preferred_time']) && $item['preferred_time'] != ""){
                $booking_details_data['start_time']['field'] = 'PREFERRED TIME';
                $booking_details_data['start_time']['value'] = $item['preferred_time'];
            }

            if(isset($item['"preferred_service']) && $item['"preferred_service'] != "" && $item['"preferred_service'] != null){
                $booking_details_data['service_name']['field'] = 'PREFERRED SERVICE';
                $booking_details_data['service_name']['value'] = $item['preferred_service'];
            }

            if(in_array($type,["healthytiffintrial","healthytiffintrail"]) && isset($item['ratecard_remarks']) && $item['ratecard_remarks'] != ""){
                $booking_details_data['service_duration']['value'] = ucwords($item['ratecard_remarks']);
            }

            if(in_array($type,["healthytiffintrail","healthytiffintrial","healthytiffinmembership"])){
                $booking_details_data['finder_name_location']['field'] = 'BOUGHT AT';
                $booking_details_data['finder_name_location']['value'] = $finder_name;
            }
            if(isset($item['group_id']) && $item['group_id'] != ""){
                $booking_details_data['group_id']['value'] = $item['group_id'];
            }

            
            Log::info('header at membership confirmed',[$type]);            
            if(in_array($type,["membershipwithpg","membershipwithoutpg","healthytiffinmembership"])){
                Log::info('header at membership confirmed');
                $header = "Membership Confirmed";
                $subline = "Hi <b>".$item['customer_name']."</b>, your <b>".$booking_details_data['service_duration']['value']."</b> Membership at <b>".$booking_details_data["finder_name_location"]['value']."</b> has been confirmed.We have also sent you a confirmation Email and SMS";
                
                // if(!empty($item['coupon_flags']['cashback_100_per'])){
                //     $subline .= "<br><br> Congratulations on receiving your instant cashback. Make the most of the cashback by using it on any transaction on Fitternity for yourself as well as friends & family. Book multiple workout sessions, buy session packs, memberships & more using this cashback without any restriction on usage.";
                    
                // }

                if(!empty($item['diwali_mixed_reward'])){
                    $subline .= "<br><br> Congratulations on celebrating a Fitwali Diwali with Fitternity. Your Fitaka Diwali Hamper will reach your inbox soon. ";
                }

                if(isset($item['extended_validity']) && $item['extended_validity']){  
                    $header = "Session Pack Confirmed";
                    $duration = "unlimited validity";
                    if(!isset($item['ratecard_flags']['unlimited_validity']) || (!$item['ratecard_flags']['unlimited_validity'])){
                        $duration = "valid for ".$serviceDurArr[1];
                    }
                    $subline = "Hi <b>".$item['customer_name']."</b>, your ".$serviceDurArr[0]." pack (".$duration.") for ".$booking_details_data['service_name']['value']." at ".$booking_details_data["finder_name_location"]['value']." has been confirmed by paying Rs. ".(string)$item['amount_customer'].". We have also sent you a confirmation Email and SMS";
                    
                    // if(!empty($item['coupon_flags']['cashback_100_per'])){
                    //     $subline .= "<br><br> Congratulations on receiving your instant cashback. Make the most of the cashback by using it on any transaction on Fitternity for yourself as well as friends & family. Book multiple workout sessions, buy session packs, memberships & more using this cashback without any restriction on usage.";
                    // }
                }

                if(isset($item['booking_for_others']) && $item['booking_for_others']){

                    $subline = "You have booked a Membership for ".ucwords($item['customer_name'])." for ".$booking_details_data['service_name']['value']." at ".$booking_details_data["finder_name_location"]['value'].". We have also sent a confirmation Email and SMS.";
                }

                if($type == "healthytiffinmembership"){
                    $subline = "Hi <b>".$item['customer_name']."</b>, your <b>".$booking_details_data['service_duration']['value']."</b> meal subscription with <b>".$booking_details_data["finder_name_location"]['value']."</b> has been confirmed.We have also sent you a confirmation Email and SMS";

                     if(isset($item['booking_for_others']) && $item['booking_for_others']){
                        $subline = "You have booked a Meal Subscription for ".ucwords($item['customer_name'])." for ".$booking_details_data['service_name']['value']." with ".$booking_details_data["finder_name_location"]['value'].". We have also sent a confirmation Email and SMS.";
                    }
                }

                if(isset($item['payment_mode']) && $item['payment_mode'] == 'cod'){
                    $subline= "Hi <b>".$item['customer_name']."</b>, your <b>".$booking_details_data['service_duration']['value']."</b> Membership at <b>".$booking_details_data["finder_name_location"]['value']."</b> has been confirmed. It will be activated once we collect your cash payment. We have also sent you a confirmation Email and SMS";
                }
                Log::info("item ---" . $item['payment_mode']);
                if(isset($item['payment_mode']) && $item['payment_mode'] == 'at the studio'){
                    $subline= "Hi <b>".$item['customer_name']."</b>, your <b>".$booking_details_data['service_duration']['value']."</b> Membership at <b>".$booking_details_data["finder_name_location"]['value']."</b> has been blocked/reserved. Activate your membership with an activation code (given by ".$booking_details_data["finder_name_location"]['value'].") on making the payment at the gym/studio.";
                }

                if(isset($_GET['device_type']) && in_array($_GET['device_type'], ['ios', 'android'])){
                    if(!isset($item['extended_validity'])){
                        $booking_details_data = array_only($booking_details_data, ['booking_id','price','address','poc', 'group_id', 'validity']);
                    }
                    else{
                        $booking_details_data = array_only($booking_details_data, ['booking_id','address','poc', 'group_id', 'validity']);
                    }
                }else{
                    if(!isset($item['extended_validity'])){
                        $booking_details_data = array_only($booking_details_data, ['booking_id','price','address','poc', 'validity']);
                    }
                    else{
                        $booking_details_data = array_only($booking_details_data, ['booking_id','address','poc', 'validity']);
                    }
                }
                // Log::info('getLoyaltyAppropriationConsentMsg', [$item['loyalty_email_content']]);
                if(isset($_GET['device_type']) && in_array($_GET['device_type'], ["ios","android"])){
                    // if(isset($item['loyalty_email_content'])){
                        // $subline = $subline."<br>".$item['loyalty_email_content'];
                        // $subline = $subline."<br>".$this->utilities->getLoyaltyAppropriationConsentMsg($customer['_id'], $id);
                         $loyaltySuccessMsg = $this->utilities->getLoyaltyAppropriationConsentMsg($customer['_id'], $id);
                    // }
                }
                else {
                    // if(isset($item['loyalty_email_content'])){
                        $loyaltySuccessMsg = $this->utilities->getLoyaltyAppropriationConsentMsg($customer['_id'], $id);
                        // $subline = $subline."<br>".$this->getLoyaltyAppropriationConsentMsg($customer['_id'], $id);
                    // }
                }

            }

            if( isset($item['type']) &&  in_array($item['type'],["booktrials","workout-session"])){

                switch ($item['type']) {
                    case 'booktrials':
                        $header = "TRIAL CONFIRMED";
                        $subline = "Hi <b>".$item['customer_name']."</b>, your Trial for <b>".$booking_details_data['service_name']['value']."</b> at <b>".$booking_details_data["finder_name_location"]['value']."</b> has been confirmed.We have also sent you a confirmation Email and SMS.";
                        break;
                    
                    default:
                        $header = "WORKOUT SESSION CONFIRMED";
                        $subline = "Hi <b>".$item['customer_name']."</b>, your Workout Session for <b>".$booking_details_data['service_name']['value']."</b> at <b>".$booking_details_data["finder_name_location"]['value']."</b> has been confirmed by paying Rs ".$item['amount'].". We have also sent you a confirmation Email & SMS.";

                        if(!empty($item['coupon_code']) && in_array($item['coupon_code'], ['FREE', 'free'])){
                            $subline = "Hi <b>".$item['customer_name']."</b>, your Workout Session for <b>".$booking_details_data['service_name']['value']."</b> at <b>".$booking_details_data["finder_name_location"]['value']."</b> has been confirmed. We have also sent you a confirmation Email & SMS.";
                        }

                        if(!empty($item['pass_order_id'])){
                            $subline = "Hi <b>".$item['customer_name']."</b>, your Workout Session for <b>".$booking_details_data['service_name']['value']."</b> at <b>".$booking_details_data["finder_name_location"]['value']."</b> has been confirmed by using unlimited access pass. We have also sent you a confirmation Email & SMS."; 
                        };

                        
                        if(!empty($item['coupon_flags']['cashback_100_per']) && ((isset($item['customer_quantity']) && $item['customer_quantity'] == 1) || empty($item['customer_quantity']) )){
                            $subline .= "<br><br> Congratulations on receiving your instant cashback. Make the most of the cashback by booking multiple workout sessions on Fitternity App for yourself as well as your friends & family without any restriction on spend value";
                        }

                        break;
                }

                if(isset($item['booking_for_others']) && $item['booking_for_others']){

                    $subline = "You have booked a session for ".ucwords($item['customer_name'])." for ".$booking_details_data['service_name']['value']." at ".$booking_details_data["finder_name_location"]['value'].". We have also sent a confirmation Email and SMS.";
                }

                $booking_details_data = array_only($booking_details_data, ['booking_id','start_date','address','poc','start_time', 'validity']);

                $booking_details_data['start_date']['value'] = $booking_details_data['start_date']['value'].", ".$booking_details_data['start_time']['value'];
                $booking_details_data['start_date']['field'] = "DATE & TIME";

                unset($booking_details_data['start_time']);

            }

            if(in_array($type,["healthytiffintrail","healthytiffintrial"])){

                if(isset($item['booking_for_others']) && $item['booking_for_others']){

                    $subline = "You have booked a Trial Meal for ".ucwords($item['customer_name'])." for ".$booking_details_data['service_name']['value']." with ".$booking_details_data["finder_name_location"]['value'].". We have also sent a confirmation Email and SMS.";
                }
            }

            if($type == "manualmembership" && isset($booking_details_data['booking_id'])){
                unset($booking_details_data['booking_id']);
            }

            $booking_details_all = [];
            foreach ($booking_details_data as $key => $value) {

                if(isset($item['type']) && in_array($item['type'],["memberships","workout-session","booktrials"]) && $key == "address" && isset($item['finder_lat']) && isset($item['finder_lon']) && !isset($_GET['device_type'])){
                    $booking_details_all[$value['position']] = ['field'=>$value['field'],'value'=>$value['value'],'lat'=>$item['finder_lat'],'lon'=>$item['finder_lon']];
                }else{
                    $booking_details_all[$value['position']] = ['field'=>$value['field'],'value'=>$value['value']];
                }
                
            }

            foreach ($booking_details_all as $key => $value) {

                if($value['value'] != "" && $value['value'] != "-"){
                    $booking_details[] = $value;
                }

            }

            $fitcash_vendor = null;

            if($fitcash_plus > 0){
                $fitcash_vendor = [
                    "title"=>"You have Rs. ".$fitcash_plus." Fitcash+ and now its time to use it!",
                    "description"=>"Fitcash+ is your fitness currency on Fitternity. You can use the entire amount in your transaction! Fitcash can be used for any booking or purchase on Fitternity ranging from workout sessions,memberships and healthy tiffin subscriptions.Fitcash+ is your companion for everything! \n Here are few options you can spend your Fitcash+ on.",
                    "image"=>"image",
                    "vendor"=>[
                        [ 
                            "image"=>"http://b.fitn.in/success-pages/swimming+session.png",
                            "title"=>"Book Swimming Sessions",
                            "details"=>[
                                ['field'=>'Avg. Calorie Burn','value'=>'750 KCAL'],
                                //['field'=>'Avg. Price Per Session','value'=>'Rs 200'],
                                ['field'=>'Current Providers in area','value'=>$category_array["swimming"]["count"].' Providers']
                            ],
                            "category"=>newcategorymapping("swimming"),
                            "city"=>$city_name,
                            "region"=>(isset($item['finder_location']) && $item['finder_location'] != "") ? [$item['finder_location']] : []
                        ],
                        [ 
                            "image"=>"http://b.fitn.in/success-pages/healthy+tiffin.png",
                            "title"=>"Book Healthy Tiffin",
                            "details"=>[
                                ['field'=>'Avg. Trial Meal Duration','value'=>'3 Days'],
                                //['field'=>'Avg. Price Per Tiffin','value'=>'Rs 200'],
                                ['field'=>'Current Providers in area','value'=>$category_array["healthy_tiffins"]["count"].' Providers']
                            ],
                            "category"=>newcategorymapping("healthy tiffins"),
                            "city"=>$city_name,
                            "region"=>(isset($item['finder_location']) && $item['finder_location'] != "") ? [$item['finder_location']] : []
                        ],
                        [ 
                            "image"=>"http://b.fitn.in/success-pages/diet+plan.png",
                            "title"=>"Buy Online Diet Plans",
                            "details"=>[
                                ['field'=>'Avg. Plan Duration','value'=>'1 Month'],
                                //['field'=>'Avg. Price Per Plan','value'=>'Rs 200'],
                                ['field'=>'Primary Function','value'=>'Weight Loss']
                            ],
                            "category"=>newcategorymapping("dietitians and nutritionists"),
                            "city"=>$city_name,
                            "region"=>(isset($item['finder_location']) && $item['finder_location'] != "") ? [$item['finder_location']] : []
                        ],
                    ]
                ];

                if(isset($_GET['device_type']) && in_array($_GET['device_type'], ["ios","android"])){
                    unset($fitcash_vendor["vendor"][2]);
                }
            }

            $invite = [
                "description"=>"Did you know that your chances of working out everyday increases by 87% with a friend?",
                "message"=>"invite your friends to join you here!",
                "confirm"=>"Now you have invited your workout buddies,you are sure to have a lot of fun",
                'show_invite' => $show_invite,
                'id_for_invite' => $id_for_invite,
                'end_point'=> $end_point,
                'type' => $type
            ];

            $conclusion = $this->getConclusionData();

            $feedback = $this->getFeedbackData();
            
            $reward_details = null;

            if(isset($item['customer_reward_id']) && $item['customer_reward_id'] != ""){

                $reward_id = (int)$item['customer_reward_id'];
                $reward = Myreward::select('_id','title','reward_type','description','validity_in_days','image')->find($reward_id);

                if($reward){

                    $reward_details = [
                        'reward_id' => $reward->_id,
                        'reward_type' => $reward->reward_type,
                        'finder_name'=> (isset($item['finder_name']) && $item['finder_name'] != "") ? $item['finder_name'] : "",
                        'title'=>$reward->title,
                        'description'=>$reward->description,
                        'validity_in_days'=>$reward->validity_in_days
                    ];

                    if(isset($reward->image) && $reward->image != ""){
                        $reward_details['image'] = $reward->image;
                    }else{
                        $reward_details['image'] = "";
                    }

                    if(in_array($reward['reward_type'],["sessions","swimming_sessions"])){

                        $session_total = "";
                        $session_amount = "";

                        $reward_details['image'] = 'https://b.fitn.in/gamification/reward/sessions.jpg';

                        $workout_session_array = Config::get('fitness_kit.workout_session');

                        if($reward['reward_type'] == "swimming_sessions"){

                            $workout_session_array = Config::get('fitness_kit.swimming_session');

                            $reward_details['image'] = 'https://b.fitn.in/gamification/reward/swimming_sessions.jpg';
                        }

                        rsort($workout_session_array);

                        foreach ($workout_session_array as $data_key => $data_value) {

                            if($item['amount_finder'] >= $data_value['min'] ){

                                $session_total = $data_value['total'];
                                $session_amount = $data_value['amount'];

                                break;
                            }
                        }

                        $reward_details['description'] = "Get access to multiple fitness sessions with instant booking at your convinience. Look out for the voucher in your profile (also sent on Email/SMS).\nGet ".$session_total." sessions for free worth Rs. ".$session_amount;

                        if($reward['reward_type'] == "swimming_sessions"){
                            $reward_details['description'] = "Get a luxury experience like never before - VIP swimming session in city's best 5-star hotels Look out for the voucher in your profile (also sent on Email/SMS).\nGet ".$session_total." swimming sessions for free worth Rs. ".$session_amount." by applying the voucher while booking your slot on Fitternity App";
                        }
                        
                    }

                    if($reward->reward_type == 'diet_plan'){
                        $reward_details['description'] = "Select convinient date and time for your first diet consultation with our expert dietitian.<ul><li>Telephonic consultation with your dietician</li><li>Personalised & customised diet plan</li><li>Regular follow-ups & progress tracking</li><li>Healthy recepies & hacks</li></ul>";
                        $reward_details['image'] = 'https://b.fitn.in/gamification/reward/diet_plan.jpg';
                    }

                    if($reward->reward_type == 'fitness_kit'){

                        if(isset($item['reward_content']) && is_array($item['reward_content']) && in_array("Breather T-Shirt",$item['reward_content'])){

                            $reward_details['tshirt_size'] = [
                                'S',
                                'M',
                                'L',
                                'XL'
                            ];
                        }

                        if(isset($item['reward_content']) && is_array($item['reward_content']) && !empty($item['reward_content'])){
                            $reward_details['description'] = "We have shaped the perfect fitness kit for you. Strike off these workout essentials from your cheat sheet & get going. <br>- ".implode(" <br>- ",$item['reward_content']);
                        }
                    }

                    if($reward->reward_type == 'mixed'){

                        $reward_details = null;

                        if(!empty($booking_details_data["reward"]) && !empty($booking_details_data["reward"]["value"])){

                            $booking_details_data["reward"]["value"] = "Snap Fitenss Hamper (We will get in touch with you shortly to assist with your reward claiming)";
                        }
                    }
                }
            }

            if(!isset($_GET['device_type']) && in_array($type,["membershipwithpg","membershipwithoutpg","healthytiffinmembership"]) && isset($item['cashback']) && $item['cashback'] && isset($item['cashback_detail']['wallet_amount'])){

                $reward_details = [
                    'reward_id' => null,
                    'reward_type' => 'cashback',
                    'finder_name'=> (isset($item['finder_name']) && $item['finder_name'] != "") ? $item['finder_name'] : "",
                    'title'=>'Instant Cashback',
                    'description'=>'Rs '.$item['cashback_detail']['wallet_amount'].'+ has been added in form of FitCash+ in your wallet. You can find it in your profile and use it to explore different workout forms and healthy tiffins.<br><br>Your currernt balance is <b>Rs '.$fitcash_plus.'</b>',
                    'validity_in_days'=>null,
                    'image'=>'https://b.fitn.in/gamification/reward/cashback2.jpg'
                ];

            }

            if(!empty($reward_details)){
                $reward_details['title'] = str_ireplace("Fitternity ","",$reward_details['title']);
                $reward_details['description'] = str_ireplace("Fitternity ","",$reward_details['description']);
            }

            if(isset($item['recommended_booktrial_id']) && $item['recommended_booktrial_id'] != ""){
                $near_by_vendor = [];
            }

            if(empty($near_by_vendor)){
                $show_other_vendor = false;
            }
            Log::info('response ::::::::::::::::::;;');
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
                'poc'=>null,
                'invite'=>$invite,
                'conclusion'=>$conclusion,
                'feedback'=>$feedback,
                'order_type'=>$order_type,
                'id'=>$id,
                'reward_details'=>$reward_details,
                'show_other_vendor' => $show_other_vendor,
                'all_options_url' => $all_options_url,
                'customer_auto_register' => $customer_auto_register,
                'why_buy'=>$why_buy,
                'loyalty_success_msg' => $loyaltySuccessMsg
            ];
            
            if(!empty($extended_message)){
                $resp['studio_extended_validity_message']= $extended_message;
            }

            if(!empty($flexi_data)){
                Log::info("inside setting flexxi data");
                $resp['flexi_data'] = $flexi_data;
                $resp['flexi_data']["popup_data"] = $flexi_data;                    
                $resp['flexi_data']["header"] = "You have purchased Flexi Membership";
                $resp['flexi_data']["button_title"] = "Know more";
                
            }
            if(empty($finder) && !empty($itemData['finder_id'])){
                $finder = Finder::find($itemData['finder_id']);
            }

            if(!empty($finder['brand_id']) && $finder['brand_id'] == 88){
                $resp['multifit_email'] = Config::get('app.multifit_email');
                $resp['multifit_helpline'] = Config::get('app.contact_us_customer_number');
            }
            
            $resp['loyalty_collaterals_delivered'] = !empty($finder) && !empty($finder['flags']['loyalty_collaterals_delivered']);

            if(!empty($item['finder_name'])){
                $resp['finder_name'] = $item['finder_name'];
            }
           
            if(!empty($item['service_name'])){
                $resp['service_name'] = $item['service_name'];
            }

            if(!empty($item['finder_poc_for_customer_name'])){
                $resp['finder_poc_for_customer_name'] = $item['finder_poc_for_customer_name'];
            }
            
            if(!empty($item['finder_poc_for_customer_no'])){
                $resp['finder_poc_for_customer_no'] = $item['finder_poc_for_customer_no'];
            }
            
            if(!empty($item['multifit'])){
                $resp['multifit'] = $item['multifit'];
            }

            $resp['payment_mode'] = !empty($item['pg_type']) ? $item['pg_type'] : 'card';

            if(!empty($resp['booking_details'])){
                foreach($resp['booking_details'] as $detail){
                    $resp['booking_details_obj'][$detail['field']] = $detail['value'];
                }
            }

            if(!empty($item['amount_customer'])){
                $resp['amount'] = $item['amount_customer'] - (!empty($item['convinience_fee']) ? $item['convinience_fee'] : 0);
            }

            if(!empty($item['type']) && $item['type'] == 'booktrials' && !empty($customer_id)){

                if(empty($customer)){
                    $customer = Customer::find($customer_id, ['loyalty']);
                }


                // if(!empty($customer['loyalty'])){
                    $resp['milestones'] = $this->utilities->getMilestoneSection();
                // }

            }

            if(!empty($item['loyalty_registration']) && $this->utilities->sendLoyaltyCommunication($item)){
                $resp['fitsquad'] = $this->utilities->getLoyaltyRegHeader();
            }
            
            if(!empty($item['free_sp_ratecard_id'])){
                
                $resp['special_offer'] = true;
            
            }
            
            if(!empty($item['reward_type']) && $item['reward_type'] == 'mixed'){
                $resp['mixed_reward_offer'] = true;
            }
            
            $cashback_type_map = Config::get('app.cashback_type_map');
            $resp['fitsquad_type'] = !empty($item['finder_flags']['reward_type']) ?  $item['finder_flags']['reward_type'] : 1;
            $resp['fitsquad_sub_type'] = !empty($item['finder_flags']['cashback_type']) ?  $cashback_type_map[strval($item['finder_flags']['cashback_type'])] : null;
            
            if(!empty($finder) && isset($finder['brand_id'])){
                $resp['brand_id'] = $finder['brand_id'];
            }

            if(!empty($finder['_id'])){
                $resp['finder_id'] = $finder['_id'];
            }
                
            if(isset($itemData['coupon_id'])){
                $resp['coupon'] = \GiftCoupon::find($itemData['coupon_id']);
            }
            if(isset($itemData['fitcash_coupon_code'])){
                $resp['fitcash_coupon_code'] = $itemData['fitcash_coupon_code'];
            }

            Log::info(" RX USER ".print_r($itemData,true));
            if(isset($itemData['rx_user'])&&$itemData['rx_user']&&isSet($itemData['rx_success_url'])&&$itemData['rx_success_url']!="")
            	$resp['rx_success_url'] =	$itemData['rx_success_url'];
            
            
            if(isset($item['group_id']) && $item['group_id'] != ''){

                $resp['group_code'] = [
                    'code'=> $item['group_id'],
                    'order_id'=> $item['_id'],
                    'end_point'=> 'sharegroupid'
                ];

            }
            
            $section3 = Config::get('nonvalidity.success_page');
            $section3['data'][0]['text'] = strtr($section3['data'][0]['text'], ['__vendor_name'=>$itemData['finder_name']]);

            if(isset($item['extended_validity']) && $item['extended_validity']){  
                $resp['session_pack'] = [
                    'header' => 'Session Pack Activated', 
                    'text' => 'All you need to do is book your slot everytime you want to workout',
                    'data' => $section3['data']
                ];
            }
            
            if(!empty($item['upgrade_fitcash'])){  
                $resp['upgrade_popup'] = getUpgradeMembershipSection($item, 'success_page');
            }

            if($this->vendor_token){

                if(in_array($item['type'],[/*"workout-session",*/"booktrials"])){

                    $item['booked_locate'] = 'booked';

                    $resp['kiosk'] = $this->utilities->trialBookedLocateScreen($item);
                }

                if($item['type'] == 'memberships'){

                    $item['membership_locate'] = 'booked';

                    $resp['kiosk_membership'] = $this->utilities->membershipBookedLocateScreen($item);
                }
            }


        
            $resp['branch_obj'] = $this->utilities->branchIOData($item);


            if(!empty($item['coupon_flags'])){
                $resp['coupon_flags'] = $item['coupon_flags'];
            }

            return Response::json($resp);
        }
    }
    
    public function getProductSuccessMsg($id,$type=null){
    	
    	try {
    		
    		$jwt_token = Request::header('Authorization');
    		if(!empty($jwt_token )&& $jwt_token != 'null'){
    			$decoded = customerTokenDecode($jwt_token);
    			$customer_id = (int)$decoded->customer->_id;
    		}
    		
    		$resp=["status"=>200,"message"=>"success"];
    		$finalData=[];
    		$order =Order::where("_id",intval($id))->where("type",'product')->first();
    		if(!empty($order))
    		{
    			$order =  $order->toArray();
    			if($order['status']!="1")
    				return ['status'=>0,"message"=>"Order not succesful."];
    		}
    		else return ['status'=>0,"message"=>"Failed to get Order."];
    		
    		
    		// KINDLY PUT ALL DEFAULTS HERE SO IT WILL BE MERGED FINALLY.
    		
    		$defaults=["showDeliveryAddress"=>false];
    		
    		if(empty($customer_id))
    		{
    			$customer_id=$order['customer']['logged_in_customer_id'];
    			if(empty($customer_id))
    				return ['status'=>0,"message"=>"Failed to get Customer."];
    		}
    		
    		
    		
    		$customer=Customer::find(intval($customer_id));
    		$customer= $customer->toArray();
    		
    		if(!empty($order['payment'])&&!empty($order['payment']['payment_mode']))
    		{
    			$payment_mode=$order['payment']['payment_mode'];
    			if($payment_mode=='paymentgateway')
    				$payment_mode="Online";

    			else if($payment_mode=='at the studio')
    					$payment_mode="At Studio";
    		}
    			else $payment_mode=null;
    			
    			
    			$header=["status_text"=>"Order Successfull","status_icon"=>"https://image.flaticon.com/teams/slug/freepik.jpg"];
    			$customer_description='Hi '.$customer['name'].', your order has been successfully placed with Fitternity.'.
      			'<br>It will be delivered to you within 7-10 working days.<br>'.
    			'You can track your order online with the information provided via SMS and E-mail';
    			
    			
    			if(!empty($order['customer_address'])&&empty($order['deliver_to_vendor']))
    			   $shipping_address=$this->utilities->formatShippingAddress($order['customer_address'],$customer['name']);
    			else if(!empty($order['finder'])&&!empty($order['finder']['finder_name'])&&!empty($order['deliver_to_vendor']))
    			   $shipping_address=$this->utilities->formatShippingAddress($order['finder'],$customer['name'],true);
    				
    				// ($payment_mode=='cod')?$finalData['customer_description']=$customer_description:"";
    				$finalData['customer_description']=$customer_description;
    				if(!empty($shipping_address))
    					$finalData['shipping_address']=$shipping_address;
    					
    					$cart_summary=$this->utilities->getCartSummary($order);
    					if($cart_summary['status'])
    					{
    						$cart_details=$cart_summary['data']['cart_details'];
    						$cart_total=$cart_summary['data']['total_cart_amount'];
    						$total_amount=$cart_summary['data']['total_amount'];
    					}
    					else
    						return $cart_summary;
    						
    						$order_summary=[];
    						array_push($order_summary, ["key"=>"Cart Amount","value"=>$cart_total]);
    						
    						if(empty($order['deliver_to_vendor']))
    							array_push($order_summary, ["key"=>"Delivery Charges","value"=>$this->utilities->getRupeeForm(intval(Config::get('app.product_delivery_charges')))]);
    							(!empty($cart_summary['data']['coupon_discount']))?
    							array_push($order_summary, ["key"=>"Coupon Discount","value"=>"-".$this->utilities->getRupeeForm($cart_summary['data']['coupon_discount']),"color"=>"#47bd55"]):"";
    							array_push($order_summary, ["key"=>"Amount Paid","value"=>$this->utilities->getRupeeForm($total_amount)]);    							

    							$orderDetail=["order_id"=>$order['_id'],"summary"=>$order_summary,"total"=>$this->utilities->getRupeeForm($total_amount)];
    							if($payment_mode)$orderDetail["payment_mode"]=$payment_mode;
    							
    							$finalData["header"]=$header;
    							
    							$finalData["cart_summary"]=$cart_details;
    							$finalData["order_detail"]=$orderDetail;
    							//     				$finalData=array_merge($finalData,$defaults);
    							
    							$resp['data']=$finalData;
    							return $resp;

    	} catch (Exception $e)
    	{
    		Log::error(" Error [getProductSuccessMsg] ".print_r($this->utilities->baseFailureStatusMessage($e),true));
    		return  ['status'=>0,"message"=>$this->utilities->baseFailureStatusMessage($e)];
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
        $city = $request['city'];

        $payload = [
           "category"=>$category,
           "sort"=>[
              "order"=>"desc",
              "sortfield"=>"popularity"
           ],
           "offset"=>[
              "from"=>$offset,
              "number_of_records"=>$limit
           ],
           "location"=>[
              "geo"=>[
                  "lat"=>$lat,
                  "lon"=>$lon,
                  "radius"=>$radius
               ],
              "city"=>$city
           ],
           "keys"=>$keys
        ];

        $url = "http://apistage.fitn.in:5000/search/vendor"; //;$this->api_url."search/getfinderresultsv4";
        $finder = [];

        try {

           
            $response  =   json_decode($this->client->post($url,['json'=>$payload])->getBody()->getContents(),true);

            if(isset($response['results'])){

                $vendor = $response['results'];

                foreach ($vendor as $key => $value) {

                    $address = false;

                    $finder_data = $value;

                    if(in_array('coverimage',$request['keys'])){
                        $finder_data['coverimage'] = $finder_data['coverimage'];
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

                    if(in_array('name',$request['keys'])){
                        $finder_data['title'] = $finder_data['name'];
                        unset($finder_data['name']);
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
            Cache::tags('findercount_locationwise_city')->put($city, $data, Config::get('cache.three_day_cache'));

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

            $footer_block1_finders 		=		Finder::active()->with(array('location'=>function($query){$query->select('name');}))->whereIn('_id', $footer_block1_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title','location_id','backend_flags'))->toArray();
            $footer_block2_finders 		=		Finder::active()->with(array('location'=>function($query){$query->select('name');}))->whereIn('_id', $footer_block2_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title','location_id','backend_flags'))->toArray();
            $footer_block3_finders 		=		Finder::active()->with(array('location'=>function($query){$query->select('name');}))->whereIn('_id', $footer_block3_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title','location_id','backend_flags'))->toArray();
            $footer_block4_finders 		=		Finder::active()->with(array('location'=>function($query){$query->select('name');}))->whereIn('_id', $footer_block4_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title','location_id','backend_flags'))->toArray();
            $footer_block5_finders 		=		Finder::active()->with(array('location'=>function($query){$query->select('name');}))->whereIn('_id', $footer_block5_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title','location_id','backend_flags'))->toArray();
            $footer_block6_finders 		=		Finder::active()->with(array('location'=>function($query){$query->select('name');}))->whereIn('_id', $footer_block6_ids)->remember(Config::get('app.cachetime'))->get(array('_id','slug','title','location_id','backend_flags'))->toArray();

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

            foreach ($footer_finders as $block => $finders) {

                if(!empty($finders) && is_array($finders)){

                    foreach ($finders as $finder_key => $finder_value) {

                        // if(strpos($finder_value['slug'],'gold') !== false){
                            // $footer_finders[$block][$finder_key]['title'] = $finder_value['title'].' '.$finder_value['location']['name'];
                        // }

                        if(!(isset($finder_value['backend_flags']) &&  isset($finder_value['backend_flags']['name_includes_location']) && $finder_value['backend_flags']['name_includes_location'] == true)){
                            $footer_finders[$block][$finder_key]['title'] = $finder_value['title'].' '.$finder_value['location']['name'];
                        }

                        
                        $array = [
                            "_id",
                            "location_id",
                            "finder_coverimage",
                            "commercial_type_status",
                            "business_type_status",
                            "location",
                            "backend_flags"
                        ];

                        foreach ($array as $value) {
                            unset($footer_finders[$block][$finder_key][$value]);
                        }
                    }
                }
            }

            $defaultfinders = [];

            // Default City vendors
            $defaultfinders = Finder::where('city_id',10000)->active()->get(array('title','slug','custom_city','custom_location'))->groupBy('custom_city');

            if(!empty($defaultfinders)){

                $defaultfinders = json_decode($defaultfinders,true);

                foreach ($defaultfinders as $city => $finders) {

                    if(!empty($finders) && is_array($finders)){

                        foreach ($finders as $finder_key => $finder_value) {

                            $array = [
                                "_id",
                                "finder_coverimage",
                                "commercial_type_status",
                                "business_type_status",
                                "custom_city",
                                "custom_location"
                            ];

                            foreach ($array as $value) {
                                unset($defaultfinders[$city][$finder_key][$value]);
                            }
                        }
                    }
                }
            }
            
            $footerdata 	= 	array(
                'footer_finders' => $footer_finders,
                'city_name' => $city_name,
                'city_id' => $city_id,
                'default_vendors'=>$defaultfinders
            );

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

        $array = array(7);
        $app_device = Request::header('Device-Type');
        if(isset($app_device) && in_array($app_device, ['ios', 'android'])){
            $cites		= 	City::active()->where('hide_on_home', '!=', true)->orderBy('order')->whereNotIn('_id',$array)->orderBy("order")->remember(Config::get('app.cachetime'), 'getcitiesapp2')->get(array('name','_id','slug'));
            // $cites		= 	City::active()->orderBy('name')->whereNotIn('_id',$array)->orderBy("order")->get(array('name','_id','slug'));
        }else{
            $cites		= 	City::orderBy('order')->whereNotIn('_id',$array)->remember(Config::get('app.cachetime'), 'getcities2')->get(array('name','_id','slug'));
            // $cites		= 	City::orderBy('name')->whereNotIn('_id',$array)->get(array('name','_id','slug'));
        }
        if(!empty($cites)){
            Log::info("getCities");
            Log::info($cites);
        }
        if($this->device_type == 'android' && $this->app_version >= '5.14'){
            return Response::json(['data'=>$cites],200);
        }
        
        return Response::json($cites,200);
    }

    public function getCityLocation($city = 'mumbai',$cache = true){
        $device_type = Input::all();
        if(!empty($device_type['source'])){
            $device_type = $device_type['source'];
        }
        else {
            $device_type = null;
        }
        $cacheKey = $city;
        if(!empty($device_type) && (in_array($device_type, ['web']))){
            $cacheKey = $city.'-web';
        }
        $location_by_city = $cache ? Cache::tags('location_by_city')->has($cacheKey) : false;
        if(!$location_by_city){
            $categorytags = $locations  =	array();
            if($city != "all"){
                $citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
                if(!$citydata){
                    return $this->responseNotFound('City does not exist');
                }

                $city_name 		= 	$citydata['name'];
                $city_id		= 	(int) $citydata['_id'];

                if(!empty($device_type) && (in_array($device_type, ['web']))){
                    $locations = Location::raw(function($collection) use ($city_id){
                        $aggregate = [];

                        $match = [
                            'cities' => [ '$in' => [$city_id]],
                            'status' => '1'
                        ];

                        $lookup = [
                            'from' => 'locations',
                            'localField' => 'parent_id',
                            'foreignField' => '_id',
                            'as' => 'parentLoc'
                        ];

                        $sort = [ 'name' => 1 ];
                        
                        $project = [
                            'name' => 1,
                            '_id' => 1,
                            'slug' => 1,
                            'location_group' => 1,
                            'lat' => 1,
                            'lon' => 1,
                            'parent_name' => [ '$arrayElemAt' => ['$parentLoc.name', 0] ]
                        ];

                        $aggregate = [
                            ['$match' => $match],
                            ['$lookup' => $lookup],
                            ['$sort' => $sort],
                            ['$project' => $project]
                        ];

                        return $collection->aggregate($aggregate);
                    });
                    if(!empty($locations)){
                        $locations = $locations['result'];
                    }
                }
                else {
                    $locations				= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug','location_group','lat','lon'));
                }

            }else{
                if(!empty($device_type) && (in_array($device_type, ['web']))){
                    $locations = Location::raw(function($collection){
                        $aggregate = [];

                        $match = [
                            'status' => '1',
                        ];

                        $lookup = [
                            'from' => 'locations',
                            'localField' => 'parent_id',
                            'foreignField' => '_id',
                            'as' => 'parentLoc'
                        ];

                        $sort = [ 'name' => 1 ];
                        
                        $project = [
                            'name' => 1,
                            '_id' => 1,
                            'slug' => 1,
                            'location_group' => 1,
                            'lat' => 1,
                            'lon' => 1,
                            'parent_name' => [ '$arrayElemAt' => ['$parentLoc.name', 0] ]
                        ];

                        $aggregate = [
                            ['$match' => $match],
                            ['$lookup' => $lookup],
                            ['$sort' => $sort],
                            ['$project' => $project]
                        ];

                        return $collection->aggregate($aggregate);
                    });
                    if(!empty($locations)){
                        $locations = $locations['result'];
                    }
                }
                else{
                    $locations				= 	Location::active()->orderBy('name')->get(array('name','_id','slug','location_group','lat','lon'))->toArray();
                }
                $locations_cluster				= 	Locationcluster::active()->orderBy('name')->get(array('name','_id','slug','lat','lon'))->toArray();
                $locations = array_merge($locations,$locations_cluster);
            }

            $homedata 				= 	array('locations' => $locations );

            Cache::tags('location_by_city')->put($cacheKey,$homedata,Config::get('cache.cache_time'));
        }

        return Response::json(Cache::tags('location_by_city')->get($cacheKey));
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
            $city = getmy_city($city);
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

        // $city = strtolower($city);
        $city = getmy_city($city);
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

            foreach ($collection_finders as $key => $finder) {

                if(!isset($finder['average_rating'])){
                    $collection_finders[$key]['average_rating'] = 0;
                }
            }

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

        $city = getmy_city($city);

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
                    // $temp_name =  strtolower($offerings_value['name']);
                    // $offerings_value['slug'] = str_replace(' ', '-', $temp_name);
                    $temp_name =  strtolower($offerings_value['slug']);
                    $temp_name1 = $value['slug'] . '-';
                    $offerings_value['slug'] = str_replace($temp_name1, '', $temp_name);
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

    public function appinstalls(){
        $data = Input::json()->all();
        $appinstall = Appinstall::create($data);
        return $appinstall;
    }

    public function promotionalNotification(){
        $data = Input::json()->all();
        $device_type = $data['device_type'];
        $to = $data['to'];
        if($device_type == "android"){
            $notification_object = array("notif_id" => 2005,"notif_type" => "promotion", "notif_object" => array("promo_id"=>739423,"promo_code"=>$data['couponcode'],"deep_link_url"=>"ftrnty://ftrnty.com".$data['deeplink'], "unique_id"=> "593a9380820095bf3e8b4568","title"=> $data["title"],"text"=> $data["body"]));
        }else{
            $notification_object = array("aps"=>array("alert"=> array("body" => $data["body"], "title" => $data["title"],), "sound" => "default", "badge" => 1), "notif_object" => array("promo_id"=>739423,"notif_type" => "promotion","promo_code"=>$data['couponcode'],"deep_link_url"=>"ftrnty://ftrnty.com".$data['deeplink'], "unique_id"=> "593a9380820095bf3e8b4568","title"=> $data["title"],"text"=> $data["body"]));
        }
        $notificationData = array("to" =>$data['to'],"delay" => 0,"label"=>$data['label'],"app_payload"=>$notification_object);
        if(!empty($data['campaign'])) {
            $notificationData['campaign'] = $data['campaign'];
        }
        $route  = $device_type;
        return $result  = $this->sidekiq->sendToQueue($notificationData,$route);
    }

    public function ifcity($city=null){
        Log::info("ifcity");
        Log::info($city);
        if(!empty($_GET['bypass'])) {
            $response = ifCityPresent($city, $_GET['bypass']);
        }
        else {
            $response = ifCityPresent($city);
        }
        
        $jwt_token = Request::header('Authorization');
        if(!$response['found']){
            if($jwt_token){
                $decoded = customerTokenDecode($jwt_token);
                $customer_id = (int)$decoded->customer->_id;
                $response["customer_id"] = $customer_id;
            }
            Log::info($response);
        }
        return $response;
    }

    public function belpSignin(){
        $data   =   Input::json()->all();
        if($data['page']=='quiz'){
            if(!isset($data["email"])){
                $resp = array("message"=> "Email field can't be blank");
                return  Response::json($resp, 400);
            }

            if(!isset($data["password"])){
                $resp = array("message"=> "Password field can't be blank");
                return  Response::json($resp, 400);
            }
            $data["email"] = strtolower($data["email"]);
            $belp_data = Belp::where("email",$data["email"])->first();

            if(isset($belp_data)){
                if($belp_data["password"] == $data["password"]){


                    $belp_data["email_id"] = $data["email_id"];
                    $belp_data["name"] = $data["name"];
                    $belp_data->save();
                    unset($belp_data["password"]);
                    
                    $resp = array("data" => $belp_data);

                    return  Response::json($resp, 200);
                }else{
                    $resp = array("message"=> "Email password don't match");
                    return  Response::json($resp, 401);     
                }
            }else{
                $resp = array("message"=> "User doesn't exists");
                return  Response::json($resp, 401);
            }
            
       }else{
            if(!isset($data["email_id"])){
                $resp = array("message"=> "Email field can't be blank");
                return  Response::json($resp, 400);
            }

            if(!isset($data["password"])){
                $resp = array("message"=> "Password field can't be blank");
                return  Response::json($resp, 400);
            }
            $data["email_id"] = strtolower($data["email_id"]);
            $belp_data = Belp::where("email_id",$data["email_id"])->first();

            if(isset($belp_data)){
                if($belp_data["password"] == $data["password"]){

                    unset($belp_data["password"]);


                    $belp_capture = Belptracking::where("belp_id",$belp_data["id"])->first();
                    $resp = array("user" => $belp_data, "capture_data"=>$belp_capture);

                    return  Response::json($resp, 200);
                }else{
                    $resp = array("message"=> "Email password don't match");
                    return  Response::json($resp, 401);     
                }
            }else{
                $resp = array("message"=> "User doesn't exists");
                return  Response::json($resp, 401);
            }
            
       }
        
    }

    public function belpUserData(){
        $data = Input::json()->all();
        
        if(!isset($data['_id'])){
            $resp = array('status'=>400, 'message'=>'No belp Id found');
            return  Response::json($resp, 400);
            
        }
        
        $belp = Belp::where('_id', $data['_id'])->first();

        if(count($belp)==0){
            $resp = array('status'=>404, 'message'=>'No belp found for this id');
            return  Response::json($resp, 400);
            
        }
        
        $keys_array = ['email', 'full_name', 'gender', 'dob','phone'];
        
        foreach($keys_array as $key){
            if(isset($data[$key])){
                $belp->$key = $data[$key];
            }
        }

        $belp->update();

        $resp = array('status'=>200, 'message'=>'Belp data saved');
        return  Response::json($resp, 200);
    }

    public function showBelpCapture(){

       $data   =   Input::json()->all();
        if(!isset($data["belp_id"])){
            $resp = array("message"=> "No belp Id found");
            return  Response::json($resp, 400);
        }else{
            $belp_data = Belp::where("_id",$data["belp_id"])->first();
            if(isset($belp_data)){
                $belp_tracking = Belptracking::where("belp_id",$data["belp_id"])->get();
                
                if(count($belp_tracking) == 0 || isset($belp_data->test)){
                    $resp = array("message"=> "No Belp Capture found");
                    return  Response::json($resp, 400);
                }else{
                    $resp = array("belp_data"=> $belp_tracking[0]);
                    return  Response::json($resp, 200);
                }
            }else{
                $resp = array("message"=> "Belp user not found");
                return  Response::json($resp, 400);
            }
        }

    }

    public function belpFitnessQuiz(){
        $data   =   Input::json()->all();
        if(!isset($data["belp_id"])){
            $resp = array("message"=> "No belp Id found");
            return  Response::json($resp, 400);
        }else{
            $belp_data = Belp::where("_id",$data["belp_id"])->first();
            if(isset($belp_data)){
                $belp_capture = Belpcapture::where("belp_id",$data["belp_id"])->get();
            Log::info($belp_capture);
                
                // return $belp_data;
                if(count($belp_capture) == 0 || isset($belp_data->test)){
                    $data["email"] = $belp_data["email"];
                    $data["capture_type"] = "belp_capture";
                    $storecapture = Belpcapture::create($data);
                    $resp = array("message"=> "Entry Saved", "capture_id"=>$storecapture->_id);
                    return  Response::json($resp, 200);
                }else{
                    $resp = array("message"=> "Your entry has already reached us");
                    return  Response::json($resp, 400);
                }
            }else{
                $resp = array("message"=> "Belp user not found");
                return  Response::json($resp, 400);
            }
        }
    }

    public function belpTracking(){
        $data   =   Input::json()->all();
        if(!isset($data["belp_id"])){
            $resp = array("message"=> "No belp Id found");
            return  Response::json($resp, 400);
        }else{
            $belp_data = Belp::where("_id",$data["belp_id"])->first();
            if(isset($belp_data)){
                // $belp_tracking = Belptracking::where("belp_id",$data["belp_id"])->get();
                // if(count($belp_tracking) == 0 || isset($belp_data->test)){
                //     // $data["email"] = $belp_data["email"];
                //     $data["capture_type"] = "belp_capture";
                //     $storecapture = Belptracking::create($data);
                //     $resp = array("message"=> "Entry Saved", "capture_id"=>$storecapture->_id);
                //     return  Response::json($resp, 200);
                // }else{
                //     $resp = array("message"=> "Your entry has already reached us");
                //     return  Response::json($resp, 400);
                // }

                $result = Belptracking::updateOrCreate(['belp_id'=>$data["belp_id"]], $data);
                $resp = array("message"=> "Entry Saved", "capture_id"=>$data["belp_id"]);
                return  Response::json($resp, 200);

            }else{
                $resp = array("message"=> "Belp user not found");
                return  Response::json($resp, 400);
            }
        }
    }

    public function crashLog(){

        $data   =   Input::json()->all();

        // $rules = CrashLog::$rules;

		// $validator = Validator::make($data,$rules);


		// if ($validator->fails()) {
		// 	return Response::json(array('status' => 400,'message' => $validator->errors()),400);   
        // }

        $crashlog = new CrashLog($data);
        $crashlog->save();

        return array('status'=>200, 'message'=>'Log saved');

    }


    public function mfp($city = false){
        
        $data = [];

        if(!$city){
            $city = 'mumbai';
        }
		$today_date = new \DateTime();
		
		$mfpEvents = DbEvent::where("mfp",true)->where("end_date", ">",$today_date)->with(array("city" => function($query){$query->select("name");}) )->get(["name", "slug","coverimage", "venue","start_date","end_date","gallery","coverimage","city_id", "city","notify"])->toArray();
		foreach($mfpEvents as $key => $mfpEvent){
			$mfpEvents[$key]["action_btn"] = "Buy Now";
			$mfpEvents[$key]["action"] = "buy";
			if(isset($mfpEvents[$key]["notify"])){
				$mfpEvents[$key]["action_btn"] = "Notify";
				$mfpEvents[$key]["action"] = "notify";
			}
		}
		$data["upcoming_party"] = $mfpEvents;
       

        
        $data["all_parties"] = [
            "drop_down"=>[
                [
                    "city"=>"All Cities",
                    "status"=>true
                ],
                [
                    "city"=>"Mumbai",
                    "status"=>false
                ],
                [
                    "city"=>"Delhi",
                    "status"=>false
                ]
            ],
            "cities"=>[
                [
                    "image"=>"https://b.fitn.in/global/toi/mfp/all_parties/mumbai_final.jpg",
                    "month"=>"DEC",
                    "date"=>"16",
                    "day"=>"SAT",
                    "city"=>"Mumbai",
                    "status"=>"current",
                    "title"=>"Sun-N-Sand, Juhu",
                    "description"=>""
                ],
                [
                    "image"=>"https://b.fitn.in/global/toi/mfp/all_parties/delhi_final.jpg",
                    "month"=>"DEC",
                    "date"=>"17",
                    "day"=>"SUN",
                    "city"=>"Delhi",
                    "status"=>"current",
                    "title"=>"The Park hotel, Connaught Place",
                    "description"=>""
                ]

            ],

        ];

        $data["buy_now"] = [
            "title"=>"BUY TICKETS NOW",
            "description"=>"Rs 300, all workshops included",
            "info"=>"Get 100% FitCash +"
        ];

        $data["madness"] = [
            "title"=>"Check Out The Madness",
			"baseurl"=>"https://b.fitn.in/mfp-2018/madness/web2/",
			"baseurl_mobile"=>"https://b.fitn.in/mfp-2018/madness/mobile1",
            "images"=>[
				"1.jpg",
				"2.jpg",
				"3.jpg",
				"4.jpg",
				"5.jpg",
				"6.jpg",
				"7.jpg",
				"8.jpg",
				"9.jpg",
				"10.jpg",
				"11.jpg",
				"12.jpg",
				"13.jpg"
            ]
        ];

        

		$data["sponsors"] = [
                [
                    "title"=>"Presented By",
                    "image"=>"https://b.fitn.in/mfp-2018/p1.png"
                ]
				// ,
                // [
                //     "title"=>"Powered By",
                //     "image"=>"https://b.fitn.in/mfp-2018/p2.png"
                // ]
            ];
        $data["what_people_say"] = [
            [
                "name"=>"Nikita Manchanda",
                "comment"=>"Ultimate Fitness party experience! it was so much fun that i dint even realise the huge amount of calories i had burnt."
            ],
            [
                "name"=>"Avani Mehta",
                "comment"=>"It was an awesome event! Amazing instructors, made the workout so much fun!"
            ],
            [
                "name"=>"Dreema H Baherwani Lala ",
                "comment"=>"The best combination of fitness and fun. Each and every session was energizing and enjoyable. The host was extremely funny and knew how to keep the crowd engaged. A colourful and vibrant way to celebrate fitness. I'm eagerly awaiting the next party."
            ],
            // [
            //     "name"=>" Nupur Banerji",
            //     "comment"=>"This was my second experience at the Morning Fitness Party and I’m blown off with the love and the energy of the people who turn up at 7 am on a Sunday! The Time of India and the team of Fitternity is doing an amazing job and going through such a big event with a smile on their faces.. You all make it easier for us to shine on the frontline.. Thank you for being such an amazing Team!"
            // ],
            [
                "name"=>" Jaspreet Bajaj",
                "comment"=>"Awesome Experience & loved the ambience... Great Instructors & Energetic folks,, too much fun"
            ],
            [
                "name"=>"Dhaval Chaurasia",
                "comment"=>"MFP THE BEST EVER I EXPERIENCED.. INSTRUCTORS ARE TOO AWESOME"
            ]
        ];

        return  Response::json($data, 200);

    }


    public function getNetBankingOptions(){

        $data = [];

        $data['options'] = [
            [
                "code"=>"BBCB",
                "bank"=>"Bank of Baroda Corporate Banking"
            ],
            [
                "code"=>"ALLB",
                "bank"=>"Allahabad Bank NetBanking"
            ],
            [
                "code"=>"ADBB",
                "bank"=>"Andhra Bank"
            ],
            [
                "code"=>"AXIB",
                "bank"=>"AXIS Bank NetBanking"
            ],
            [
                "code"=>"BBKB",
                "bank"=>"Bank of Bahrain and Kuwait"
            ],
            [
                "code"=>"BBRB",
                "bank"=>"Bank of Baroda Retail Banking"
            ],
            [
                "code"=>"BOIB",
                "bank"=>"Bank of India"
            ],
            [
                "code"=>"BOMB",
                "bank"=>"Bank of Maharashtra"
            ],
            [
                "code"=>"CABB",
                "bank"=>"Canara Bank"
            ],
            [
                "code"=>"CSBN",
                "bank"=>"Catholic Syrian Bank"
            ],
            [
                "code"=>"CBIB",
                "bank"=>"Central Bank Of India"
            ],
            [
                "code"=>"CITNB",
                "bank"=>"Citi Bank NetBanking"
            ],
            [
                "code"=>"CUBB",
                "bank"=>"CityUnion"
            ],
            [
                "code"=>"CRPB",
                "bank"=>"Corporation Bank"
            ],
            [
                "code"=>"DCBCORP",
                "bank"=>"DCB Bank - Corporate Netbanking"
            ],
            [
                "code"=>"DENN",
                "bank"=>"Dena Bank"
            ],
            [
                "code"=>"DSHB",
                "bank"=>"Deutsche Bank"
            ],
            [
                "code"=>"DCBB",
                "bank"=>"Development Credit Bank"
            ],
            [
                "code"=>"FEDB",
                "bank"=>"Federal Bank"
            ],
            [
                "code"=>"HDFB",
                "bank"=>"HDFC Bank"
            ],
            [
                "code"=>"ICIB",
                "bank"=>"ICICI Netbanking"
            ],
            [
                "code"=>"INDB",
                "bank"=>"Indian Bank"
            ],
            [
                "code"=>"INOB",
                "bank"=>"Indian Overseas Bank"
            ],
            [
                "code"=>"INIB",
                "bank"=>"IndusInd Bank"
            ],
            [
                "code"=>"IDBB",
                "bank"=>"Industrial Development Bank of India"
            ],
            [
                "code"=>"INGB",
                "bank"=>"ING Vysya Bank"
            ],
            [
                "code"=>"JAKB",
                "bank"=>"Jammu and Kashmir Bank"
            ],
            [
                "code"=>"KRKB",
                "bank"=>"Karnataka Bank"
            ],
            [
                "code"=>"KRVB",
                "bank"=>"Karur Vysya"
            ],
            [
                "code"=>"KRVB",
                "bank"=>"Karur Vysya - Corporate Netbanking"
            ],
            [
                "code"=>"162B",
                "bank"=>"Kotak Bank"
            ],
            [
                "code"=>"LVCB",
                "bank"=>"Laxmi Vilas Bank-Corporate"
            ],
            [
                "code"=>"LVRB",
                "bank"=>"Laxmi Vilas Bank-Retail"
            ],
            [
                "code"=>"OBCB",
                "bank"=>"Oriental Bank of Commerce"
            ],
            [
                "code"=>"PNBB",
                "bank"=>"Punjab National Bank - Retail Banking"
            ],
            [
                "code"=>"CPNB",
                "bank"=>"Punjab National Bank-Corporate"
            ],
            [
                "code"=>"RTN",
                "bank"=>"Ratnakar Bank "
            ],
            [
                "code"=>"SRSWT",
                "bank"=>"Saraswat Bank"
            ],
            [
                "code"=>"SVCB",
                "bank"=>"Shamrao Vitthal Co-operative Bank"
            ],
            [
                "code"=>"SOIB",
                "bank"=>"South Indian Bank"
            ],
            [
                "code"=>"SDCB",
                "bank"=>"Standard Chartered Bank"
            ],
            [
                "code"=>"SBBJB",
                "bank"=>"State Bank of Bikaner and Jaipur"
            ],
            [
                "code"=>"SBHB",
                "bank"=>"State Bank of Hyderabad"
            ],
            [
                "code"=>"SBIB",
                "bank"=>"State Bank of India"
            ],
            [
                "code"=>"SBMB",
                "bank"=>"State Bank of Mysore"
            ],
            [
                "code"=>"SBPB",
                "bank"=>"State Bank of Patiala"
            ],
            [
                "code"=>"SBTB",
                "bank"=>"State Bank of Travancore"
            ],
            [
                "code"=>"UBIBC",
                "bank"=>"Union Bank - Corporate Netbanking"
            ],
            [
                "code"=>"UBIB",
                "bank"=>"Union Bank of India"
            ],
            [
                "code"=>"UNIB",
                "bank"=>"United Bank Of India"
            ],
            [
                "code"=>"VJYB",
                "bank"=>"Vijaya Bank"
            ],
            [
                "code"=>"YESB",
                "bank"=>"Yes Bank"
            ]
        ];

        $data['status'] = 200;
        $data['message'] = 'Bank Options';

        return  Response::json($data,200);
    }

    public function careerCapture(){

        $data = Input::json()->all();

        $rules = [
            'email' => 'required|email|max:255',
            'name' => 'required',
            // 'phone'=>'required',
            // 'interest'=>'required', 
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {

            $response = array('status' => 400,'message' =>error_message($validator->errors()));

            return Response::json(
                $response,
                $response['status']
            );

        }

        if(empty($data)){

            return Response::json(
                array(
                    'status' => 400,
                    'message' => "Error!",
                    ),
                400
            );

        }

        Career::create($data);

        return Response::json(
            array(
                'status' => 200,
                'message' => "Thankyou for the Application",
                ),
            200
        );
    }
    public function getAssitanceQuestions(){
        $questions = AssistanceQuestion::all();
        return $questions;
    }

    public function postAnswers(){
        $data = Input::json()->all();
        $capture = new Capture($data);
        $capture->capture_type = "assistance_response";
        $capture->save();
        return array('status'=>200, 'message'=>'Saved successfully', 'capture'=>  $capture);

    }

    public function getCrashLog($count = 1){
        return CrashLog::orderBy('_id', 'desc')->take($count)->get();
    }

    public function getConclusionData(){
        return [
            "title"=>"Any Queries? Contact Us",
            "description"=>"Any Queries? Contact Us",
            "email"=>"support@fitternity.com",
            "phone"=>"+912261094444"
        ];;
    }

    public function getFeedbackData(){
        return [
            'reason'=>[
                'Choice of fitness options available',
                'Information provided about the gym/studio',
                'Transaction & Booking process',
                'Payment/Cashback/Offer related',
                'Any other issue'
            ],
            'threshold_value'=>6
        ];
    }
    
    public function cityFitnessOptions($cache = true){

        $data = $cache ? Cache::tags('citywise_finders')->has('citywise_finders') : false;

        if(!$data){

            Log::info("No cache citywise_finders");

            $city_id = [1, 2, 3, 4, 5, 6, 8, 9];
    
            $cities = Finder::raw(function($collection) use ($city_id){
    
                $aggregate = [];
    
                $match = ['$match' => ['status' => "1", 'city_id' => ['$in'=> $city_id]]];
    
                $aggregate[] = $match;
    
                $group = array(
                            '$group' => array(
                                '_id' => '$city_id',
                                'count' => array(
                                    '$sum' => 1
                                )
                            )
                        );
    
                $aggregate[] = $group;
    
                return $collection->aggregate($aggregate);
    
            });
    
            $cities = $cities['result'];
            $data = [];
            foreach($cities as $key => $city){

                $city_name = City::find($city['_id'], ['name']);

                if($city_name['name'] == 'gurgaon'){
                    $city_name['name'] = 'gurugram';
                }

                $data[$city_name['name']] = ['city_name'=>ucwords($city_name['name']), 'finders_count'=>$city['count']];
    
            }

            Cache::tags('citywise_finders')->put('citywise_finders',$data,Config::get('cache.three_day_cache'));
        }

        return Cache::tags('citywise_finders')->get('citywise_finders');
    }

    public function customerHome(){

        $data = [
            'status'=>200,
            'message'=>'success',
            'customer_home'=>null
        ];

        $jwt_token = Request::header('Authorization');

        if($jwt_token){
            $data['customer_home'] = $this->utilities->customerHome();
        }

        return Response::json($data);
    }
    
    public function updateRelianceCustomer(){
    	
    	$data = Input::json()->all();
    	try {
    		if(isset($data['email'])/* &&isset($data['rx_success_url']) */&&isset($data['phone'])&&isset($data['name'])&&$data['email']!=""&&$data['phone']!=""&&$data['name']!=""/* &&$data['rx_success_url']!=""&&preg_match('#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i',$data['rx_success_url'] )*/)
    		{
    			$customer_id=autoRegisterCustomer(['customer_email'=>$data['email'],/* 'rx_success_url'=>$data['rx_success_url'], */'customer_phone'=>$data['phone'],'customer_name'=>$data['name'],'rx_user'=>true]);
    			Log::info(" customer_id Data :: ".print_r($customer_id,true));
    			if(isset($customer_id))
    			{
    				$token=createCustomerToken($customer_id);
    				Log::info(" token Data :: ".print_r($token,true));
    				ini_set("memory_limit", "256M");
    				
    				Log::info(" memory_limit :: ".print_r(ini_get("memory_limit"),true));
    				$decoded = customerTokenDecode($token);
    				$customerInfo=Customer::where("_id",$customer_id)->first(array("_id", "name", "email", "picture", "facebook_id", "identity", "address", "contact_no", "location", "gender", "extra", "corporate_login"));
    				if(isset($token))
    				{
    					$customerData=array("_id"=>$customer_id,
    							"contact_no"=>((isset($customerInfo->contact_no)&&$customerInfo->contact_no!="")?$customerInfo->contact_no:""),
    							"email"=>((isset($customerInfo->email)&&$customerInfo->email!="")?$customerInfo->email:""),
    							"gender"=>((isset($customerInfo->gender)&&$customerInfo->gender!="")?$customerInfo->gender:""),
    							"name"=>((isset($customerInfo->name)&&$customerInfo->name!="")?$customerInfo->name:""),
    							"dob"=>((isset($customerInfo->dob)&&$customerInfo->dob!="")?$customerInfo->dob:"1970-01-01 00:00:00"));
    					
    					Log::info(" customerData Data :: ".print_r($customerData,true));
    					return  Response::json(array("status"=>200,"message"=>'Successful login.',"customer"=>((isset($customerInfo)&&$customerInfo!="")?$customerInfo:""),"token"=>$token,
    							"body"=>array("status"=>200,"message"=>'Successful login.',"token"=>$token,"customer_data"=>$customerData)));
    					
    				}
    				else return Response::json(['status'=>400,'message'=>'Failed to generate token.']);
    			}
    			else
    			{
    				return  Response::json(array("status"=>400,"response"=>array("rx_user"=>false,"customer_id"=>$customer_id,"message"=>"Failed to update Reliance user info.")));
    			}
    		}
    		else return Response::json(['status'=>400,'message'=>'Invalid or missing Input Data .Need [name,email,phone]']);
    		
    	} catch (Exception $e) {
    		Log::error(print_r($e,true));
    		return  Response::json(['status'=>0,message=>$e->getMessage()]);
    	}}
    	public function reliancecustomerupdate(){
    		$data = Input::json()->all();
    		return updateRelianceCommunication($data)."";
        }
        
        public function checkemail($email, $phone){

            $response = ['status'=>200, 'message'=>'Valid Email'];

            $customer_email = Customer::where('email', strtolower($email))->first();

            $customer_phone = Customer::where('contact_no', substr($phone, -10))->first();

            if($customer_email && $customer_phone){
                $response = ['status'=>400, 'message'=>'Email and mobile number already registered'];
            }else if($customer_email){
                $response = ['status'=>400, 'message'=>'Email already registered'];
            }else if($customer_phone){
                $response = ['status'=>400, 'message'=>'Mobile number already registered'];
            }

            return $response;
            
        }
        
        public function getProductsHome($cache=true)
        {
        	try {
        		
        		$home=["status"=>200,"response"=>["products"=>[]]];
        		$homeData=Homepage::active()->where("type","product_tab")->first();
        		if(!empty($homeData))$homeData=$homeData->toArray();
        		else return ['status'=>400,"message"=>"No home data found."];
        		$t=&$homeData['home'];$this->utilities->customSort('base',$t);
        		$tp=$this->utilities->groupBy($t,'base');
        		foreach($tp as &$val) $this->utilities->customSort('index',$val);
        		foreach($tp as &$val) {
        			foreach($val as &$vala) {

        				unset($vala["index"]);unset($vala["base"]);
        				(isset($vala['product_id']))?$vala['product_id']=$vala['product_id']:"";
        				$tmp_data=array_values(array_filter($homeData['rc'],function ($e) use ($vala) {return $vala['ratecard_id']== $e['ratecard']['_id'];}));
        				if(!empty($tmp_data))
        				{
        					$tmp_data=$tmp_data[0];
        					$vala['product_title']=$tmp_data['product']['title'];
        					$vala['product_slug']=$tmp_data['product']['slug'];
        					if(!empty($tmp_data['product'])&&!empty($tmp_data['product']['primarycategory'])&&!empty($tmp_data['product']['primarycategory']['slug']))
        					{
        						$vala['product_category_slug']=$tmp_data['product']['primarycategory']['slug'];
        						$vala['product_category_id']=$tmp_data['product']['primarycategory']['_id'];
        					}
        					
        					$vala['product_title']=$tmp_data['product']['title'];
        					$vala['ratecard_title']=$tmp_data['ratecard']['title'];
        					$vala['price']=$tmp_data['ratecard']['price'];
        				}
        			}
        		}
        		
        		$home["response"]['products']=array_values($tp);
        		(isset($homeData['header_image']))?array_unshift($home["response"]['products'], [['type'=>'header', 'url'=>$homeData['header_image']]]):"";
        		$this->utilities->attachCart($home["response"],false);
        		return $home;
        	} catch (Exception $e) {
        		Log::error($e);
        		return ['status'=>400,"message"=>$this->utilities->baseFailureStatusMessage($e)];
        	}
        	
        }
    
        public function addProductToCart($ratecard_id,$quantity=0)
        {
        	try {
        		$ratecard_id=intval($ratecard_id);
        		$quantity=intval($quantity);
        		$response=["status"=>200,"response"=>["message"=>"Success"]];
        		
        		$jwt_token = Request::header("Authorization");
        		if(!empty($jwt_token))
        		{
        			$token_decoded=decode_customer_token();
        			if(!empty($token_decoded)&&!empty($token_decoded->customer))
        			{
        				if(!empty($token_decoded->customer->cart_id))
        					$cart_id=$token_decoded->customer->cart_id;
        				else $cart_id=getCartOfCustomer(intval($token_decoded->customer->_id));
        			}
        			
        			if(!empty($cart_id))
        			{
        				$ratecard=ProductRatecard::active()->where("_id",$ratecard_id)->first(['price','product_id']);
        				if(!empty($ratecard)&&!empty($ratecard->product_id)&&!empty($ratecard->_id)&&isset($ratecard->price))
        				{
        					$ratecard=$ratecard->toArray();
        					$tmp=['ratecard_id'=>$ratecard_id];
        					 $alreadyQuantity=$this->utilities->attachProductQuantity($tmp,true);
        					if(!empty($alreadyQuantity))
        					  if($quantity>0&&$quantity==$alreadyQuantity)
        					    return ['status'=>0,"message"=>'Product Already Added'];
        					$cartData=["product_id"=>$ratecard['product_id'],"ratecard_id"=>$ratecard['_id'],"price"=>$ratecard['price'],"quantity"=>$quantity];
        					
        					if(empty($alreadyQuantity))
        					{
        						if($quantity>0)$addedToCart=Cart::where('_id', intval($cart_id))->push('products',$cartData);
        						else return ['status'=>0,"message"=>"Product doesn't exists. Can't remove item."];
        					}
        					else {
        						if($quantity>0)$addedToCart=Cart::raw(function($collection) use ($cart_id,$ratecard_id,$quantity){return $collection->update(['_id'=>intval($cart_id),"products.ratecard_id"=>$ratecard_id],['$set'=>['products.$.quantity'=>$quantity]]);});
        						else $removedOldFromCart=Cart::where('_id', intval($cart_id))->pull('products', ['ratecard_id' => intval($ratecard['_id']), 'product_id' => intval($ratecard['product_id'])]);
        					}
        					if(!empty($_GET['product_detail']) && filter_var($_GET['product_detail'], FILTER_VALIDATE_BOOLEAN))
        					{
        						$hc=new \HomeController(new CustomerNotification(), new Sidekiq(),$this->utilities);
        						$dataProd=$hc->getProductDetail($ratecard_id,$ratecard['product_id'],true);
        						if(!empty($dataProd)&&!empty($dataProd['status']))
        							$response["response"]['product']=$dataProd['data'];
        					}
        					if(!empty($_GET['cart_summary']) && filter_var($_GET['cart_summary'], FILTER_VALIDATE_BOOLEAN))
        					{	
        						$cart=$this->utilities->attachCart($response["response"],true);
        						$dataCart=$this->utilities->getCartFinalSummary($cart['products'], $cart['_id']);
        						if(!empty($dataCart)&&!empty($dataCart['status']) && $dataCart['status'] != 5)
        						{
        							$response["response"]['cart_summary']=$dataCart['data'];
        							$cities=$this->utilities->getProductCities();
        							if(!empty($cities))$response["response"]['cart_summary']['cities']=$cities;
        						}
        					}
        					else $this->utilities->attachCart($response["response"],false);
        					return $response;
        				}
        				return Response::json(['status'=>400,"message"=>"Not a valid ratecard or ratecard doesn't exist."]);
        			}
        		}
        		else return Response::json(['status'=>400,"message"=>"Token Not Present"]);

        		return $response;
        	} catch (Exception $e)
        	{
        		return  Response::json(['status'=>400,"message"=>$this->utilities->baseFailureStatusMessage($e)]);
        	}
        }
        
        
        public function addProductsToCart($cartDataInput=[])
        {
        	return Response::json($this->utilities->addProductsToCart($cartDataInput));
        }
        
        public function getProductDetail($ratecard_id,$product_id,$getProductInternal=false,$cache=false)
        {
    		Log::info($_SERVER['REQUEST_URI']);

        	try {
        		$ratecard_id=intval($ratecard_id);$product_id=intval($product_id);
        		$productView=Product::active()->where("_id",$product_id)->with(array('ratecard'=>function($query){$query->active()->select('_id','title','info','flags','product_id','price','slash_price','order','status','properties','extra_info','image');}))->with('primarycategory')->first();
        		if(empty($productView))return ['status'=>0,"message"=>"Not a valid product Id."];else $productView=$productView->toArray();
        		$selectedRatecard=array_values(array_filter($productView['ratecard'],function ($e) use ($ratecard_id) {return $ratecard_id== $e['_id'];}));
        		if(!empty($selectedRatecard))
        		{
        			$selectedRatecard=$selectedRatecard[0];
        			
        			$selectedRatecard['cost']=(isset($selectedRatecard['slash_price'])&&$selectedRatecard['slash_price']!=="")?$this->utilities->slashPriceFormat($selectedRatecard)." ".$this->utilities->getRupeeForm($selectedRatecard['price']):$this->utilities->getRupeeForm($selectedRatecard['price']);
                    
                    if(isset($selectedRatecard['slash_price'])&&$selectedRatecard['slash_price']!==""){
                        if(isset($selectedRatecard['price'])&&$selectedRatecard['price']!=="")
                        $selectedRatecard['discounted_price']=" (".intval(((($selectedRatecard['slash_price']-$selectedRatecard['price'])/$selectedRatecard['slash_price'])*100))."% off )";
                        $selectedRatecard['slash_price'] = $this->utilities->getRupeeForm($selectedRatecard['slash_price']);
                    }
                    if(!empty($selectedRatecard['flags'])&&!empty($selectedRatecard['flags']['tax_inclusive']))
                    		$selectedRatecard['tax_text']="Inclusive of all taxes.";
                    
                    
                    // new Code  to be implemented later 
                    $alreadyPurchased=Order::where('status',"1")->/* where('payment.success_date',">=",new DateTime(date("Y-m-d H:i:s", mktime(0,0,0))))-> */where("cart_data.ratecard._id",intval($selectedRatecard['_id']))->get();
                    
                    if(!empty($alreadyPurchased))
                    {
                    	$alreadyPurchased=$alreadyPurchased->toArray();
                    	$alreadyPurchasedCnt=0;
                    	$tt=[];
                    	foreach ($alreadyPurchased as $key=>$value) 
                    	{
                    		if(!empty($value['cart_data']))
                    		{
                    			$ra=array_filter($value['cart_data'],function ($e) use ($selectedRatecard){return !empty($e['ratecard'])&&$e['ratecard']['_id']==$selectedRatecard['_id'];});
                    			if(count($ra)>0&&!empty($ra[0]['quantity']))$alreadyPurchasedCnt=$alreadyPurchasedCnt+$ra[0]['quantity'];
                    		}
                    	}
                    	if(!empty($alreadyPurchasedCnt))
                    		$selectedRatecard['already_purchased_customers']=$alreadyPurchasedCnt;	
                    }
                    
                    // new Code  to be implemented later
                    
                    // current code

                    // category based addition
					if(!empty($productView['primarycategory'])&&!empty($productView['primarycategory']['already_purchased_count']))
					{
						if(!empty($selectedRatecard['already_purchased_customers']))
							$selectedRatecard['already_purchased_customers']=$selectedRatecard['already_purchased_customers']+$productView['primarycategory']['already_purchased_count'];
						else $selectedRatecard['already_purchased_customers']=$productView['primarycategory']['already_purchased_count'];
					}
					// category based addition
					
					
					if(!empty($selectedRatecard['already_purchased_customers']))
						$selectedRatecard['already_purchased_customers']=($selectedRatecard['already_purchased_customers']==1)?"1 person already bought this product.":$selectedRatecard['already_purchased_customers']. " people already bought this product.";
					
                    
        			(!empty($productView['specification'])&&!empty($productView['specification']['secondary']))?
        			$selectedRatecard['details']=$this->utilities->getProductDetailsCustom($productView['specification']['secondary'],'secondary'):"";
        			
        			if(!empty($selectedRatecard['info'])&&!empty($selectedRatecard['info']['long_description']))$selectedRatecard['long_description']=$selectedRatecard['info']['long_description'];
        			else if(!empty($productView['info'])&&!empty($productView['info']['long_description']))$selectedRatecard['long_description']=$productView['info']['long_description'];
        			
        			if(!empty($selectedRatecard['info'])&&!empty($selectedRatecard['info']['short_description'])&&count($selectedRatecard['info']['short_description'])>0)$selectedRatecard['short_description']=$this->utilities->getProductDetailsCustom($selectedRatecard['info']['short_description'],'secondary');
        			else if(!empty($productView['info'])&&!empty($productView['info']['short_description'])&&count($productView['info']['short_description'])>0)$selectedRatecard['short_description']=$this->utilities->getProductDetailsCustom($productView['info']['short_description'],'secondary');
        			unset($selectedRatecard['info']);
        			
        			if(!empty($selectedRatecard['image'])&&!empty($selectedRatecard['image']['secondary'])&&count($selectedRatecard['image']['secondary'])>0)$selectedRatecard['images']=$selectedRatecard['image']['secondary'];
        			else if(!empty($productView['image'])&&!empty($productView['image']['secondary'])&&count($productView['image']['secondary'])>0)$selectedRatecard['images']=$productView['image']['secondary'];
        			unset($selectedRatecard['image']);
        			(!empty($productView['specification'])&&!empty($productView['specification']['primary'])&&!empty($productView['specification']['primary']['features']))?$selectedRatecard['key_details']=$this->utilities->getProductDetailsCustom($productView['specification']['primary']['features']):"";
//         			(!empty($selectedRatecard['key_details']))?array_unshift($selectedRatecard['key_details'],["name"=>"color","value"=>$selectedRatecard['color']]):"";
        			
        			$props_arr=[];
        			if(!empty($selectedRatecard['properties']))
        			{
        				$props_arr=$this->utilities->mapProperties($selectedRatecard['properties']);
        				(!empty($props_arr))?$selectedRatecard['properties']=$props_arr:"";
        			}
        					
        			if(!empty($productView['selection_view'])&&is_array($productView['selection_view']))
        			{
        				$selectionViewFiltered=$this->utilities->getFilteredAndOrdered($productView['selection_view'],'level');$trav_idx=[];
//         					return $this->utilities->getSelectionView($selectionViewFiltered,intval($productView['_id']),$productView,intval($selectedRatecard['_id']),$trav_idx);
        				$this->utilities->getSelectionView($selectionViewFiltered,intval($productView['_id']),$productView,intval($selectedRatecard['_id']),$trav_idx);
        				(!empty($selectionViewFiltered))?$selectedRatecard=array_merge($selectedRatecard,$this->utilities->getSelectionView($selectionViewFiltered,intval($productView['_id']),$productView,intval($selectedRatecard['_id']),$trav_idx)):"";
//         				if(!empty($trav_idx))$selectedRatecard['traverse_ind']=array_pluck($trav_idx, 	'ind');
//         				if(!empty($trav_idx))$selectedRatecard['traverse_ind']=$trav_idx;
//         				return $selectedRatecard;
        				unset($selectedRatecard['extra_info']);
        				if(empty($getProductInternal))unset($selectedRatecard['properties']);
        			}
        		
        			if(!empty($productView['primarycategory'])&&!empty($productView['primarycategory']['slug'])){$selectedRatecard['product_category_slug']=$productView['primarycategory']['slug'];$selectedRatecard['product_category_id']=$productView['primarycategory']['_id'];}
        			$mainSimilar=[];
        			$selectedRatecard['ratecard_id']=$selectedRatecard['_id'];
        			
        			if($getProductInternal)
        			{
        				unset($selectedRatecard['_id']);unset($selectedRatecard['productcategory_id']);
        				unset($selectedRatecard['order']);unset($selectedRatecard['status']);
        				unset($selectedRatecard['servicecategory_id']);unset($selectedRatecard['flags']);
        				return ["status"=>1,"data"=>$selectedRatecard];
        			}
        			
        			(!empty($productView['servicecategory'])&&!empty($productView['servicecategory']['primary']))?$sameCatProducts=Product::active()->where("_id","!=",$product_id)->where("servicecategory.primary",$productView['servicecategory']['primary'])->lists('_id'):"";
        			(!empty($sameCatProducts))?$productSimilar=ProductRatecard::active()->with(array('product'=>function($query){$query->active()->with('primarycategory')->get();}))->where("product_id","!=",$product_id)->whereIn("product_id",$sameCatProducts)->take(4)->get(['_id','title','product_id','price','image']):"";
        			if(!empty($productSimilar))
        			{
        				$productSimilar=$productSimilar->toArray();
        				foreach ($productSimilar as $value) {
        					if(!empty($value['product']))
        					{
        						$url="";
        						if(!empty($value['image'])&&!empty($value['image']['primary']))
        							$url=$value['image']['primary'];
        							else if (!empty($value['product']&&!empty($value['product']['image'])&&!empty($value['product']['image']['primary'])))
        								$url=$value['product']['image']['primary'];
        								$ttp=[
        										'price'=>$value['price'],
        										'product_id'=>((!empty($value['product']['_id']))?$value['product']['_id']:""),'product_title'=>((!empty($value['product']['title']))?$value['product']['title']:""),
        										'product_slug'=>((!empty($value['product']['slug']))?$value['product']['slug']:""),'url'=>$url,'type'=>'product',
        										'product_category_slug'=>$value['product']['primarycategory']['slug'],
        										'product_category_id'=>$value['product']['primarycategory']['_id'],
        										'ratecard_title'=>$value['title'],'ratecard_id'=>$value['_id']
        								];
        								
        								if(isset($value['slash_price'])&&$value['slash_price']!==""){
                                            $ttp['cost']=$this->utilities->slashPriceFormat($value)." ".$this->utilities->getRupeeForm($value['price']);
                                            $ttp['slash_price'] = $this->utilities->getRupeeForm($value['slash_price']);
                                        }
        								else $ttp['cost']=$this->utilities->getRupeeForm($value['price']);
        								array_push($mainSimilar, $ttp);	
        					}		
        				}
        			}
        			if(!$getProductInternal)
        			{
        				unset($selectedRatecard['_id']);unset($selectedRatecard['productcategory_id']);
        				unset($selectedRatecard['order']);unset($selectedRatecard['status']);
        				unset($selectedRatecard['servicecategory_id']);unset($selectedRatecard['flags']);
        				$finalData['product']=$selectedRatecard;
        			}	
        		}
        		else return ['status'=>0,"message"=>"Not a valid Ratecard Id."];
        		$this->utilities->attachProductQuantity($finalData['product']);
        		(!empty($mainSimilar)&&count($mainSimilar)>0)?$finalData['similar_products']=["title"=>"Similar Products","sub_title"=>"Checkout other essential products for your workout","items"=>$mainSimilar]:"";
        		$this->utilities->attachCart($finalData,false);
        		return ["status"=>200,"response"=>$finalData];
        	} catch (Exception $e) {
        		return ['status'=>0,"message"=>$this->utilities->baseFailureStatusMessage($e)];
        	}
        }
 
        
        public function getCategoryBasedProducts($productcategory_id)
        {
        
        	try {
        		$productcategory_id=intval($productcategory_id);
        		$productView=Product::active()->where("productcategory.primary",$productcategory_id)->with(array('ratecard'=>function($query){$query->active()->get();}))->with('primarycategory')->get();
        		
        		if(empty($productView))return ['status'=>0,"message"=>"Not a valid Product Category Id."];
        		else $productView=$productView->toArray();
        		
        		$productIds=array_column($productView, "_id");
        	
        		/* $productView['selection_view'] */
        		$rates=ProductRatecard::active()->raw(function($collection) use($productIds)
        		 {
        		 return $collection->aggregate(
        		 [
        		 ['$match' => ['product_id' => ['$in'=>$productIds]]],
        		 ['$group' => ['_id' => ["p_id"=>'$product_id','color'=>'$color'],'details' => ['$push'=>['ratecards'=>'$_id']]]],
        		 ['$match' => ['details.0' => ['$exists'=>true]]],
        		 ['$project' => ["rcs"=>['$arrayElemAt' => ['$details',0]]]]
        		 ]);
        		 });
        		 
        		(!empty($rates)&&!empty($rates['result']))?
        		$ratecards=array_values(array_column(array_column($rates['result'], 'rcs'), 'ratecards')):"";
        		if(!empty($ratecards))
        			$ratecards=ProductRatecard::active()->whereIn("_id",$ratecards)->with(array('product'=>function($query){$query->active()->with('primarycategory')->orderBy('ordering', 'ASC');}))->get();
        		else return ['status'=>0,"message"=>"Not Ratecards found for productcategoryid : ".$productcategory_id];
        		$product_cat_title="";
        		$categories=[];
        		if(!empty($ratecards))
        		{
        			$ratecards=$ratecards->toArray();
        			foreach ($ratecards as $value) {
        				if(!empty($value['product']))
        				{
        					$url="";
        					if(!empty($value['image'])&&!empty($value['image']['primary']))
        						$url=$value['image']['primary'];
        						else if(!empty($value['product']&&!empty($value['product']['image'])&&!empty($value['product']['image']['primary'])))
        							$url=$value['product']['image']['primary'];
        							if(empty($product_cat_title))
        								$product_cat_title=(!empty($value['product']['primarycategory']['title'])?$value['product']['primarycategory']['title']:"");
        							array_push($categories, [
                                            'cost'=>(isset($value['slash_price'])&&$value['slash_price']!=="")?$this->utilities->slashPriceFormat($value)." ".$this->utilities->getRupeeForm($value['price']):$this->utilities->getRupeeForm($value['price']),
                                            'slash_price' => isset($value['slash_price']) ? $this->utilities->getRupeeForm($value['slash_price']) : "",
        									'price'=>$value['price'],
        									'product_id'=>$value['product']['_id'],
        									'product_title'=>$value['product']['title'],
        									'product_slug'=>$value['product']['slug'],
        									'url'=>$url,
        									'type'=>'product',
        									'product_category_slug'=>$value['product']['primarycategory']['slug'],
        									'product_category_id'=>$value['product']['primarycategory']['_id'],
        									'product_category_title'=>(!empty($value['product']['primarycategory']['title'])?$value['product']['primarycategory']['title']:""),
        									'ratecard_title'=>$value['title'],
        									'ratecard_id'=>$value['_id']
        							]);
        				}
        			
        			}
        			 if(empty($product_cat_title))
        			  $product_cat_title="Category Based Products";
        			$products=Product::active()->raw(function($collection) use($productcategory_id)
        			{
        				
        				return $collection->aggregate(
        						[
        								['$match' => ['status'=>'1','productcategory.primary' => ['$nin'=>[$productcategory_id]]]],
        								['$group' => ['_id' => ["p_id"=>'$productcategory.primary'],'details' => ['$push'=>['products'=>'$_id']]]],
        								['$match' => ['details.0' => ['$exists'=>true]]],
        								['$project' => ["prods"=>['$arrayElemAt' => ['$details',0]]]]
        						]);
        			});
        
        			(!empty($products)&&!empty($products['result']))?
        			$products=array_values(array_column(array_column($products['result'], 'prods'), 'products')):"";
        			$products=Product::active()->whereIn("_id",$products)->with(array('ratecard'=>function($query){$query->active()->get();}))->with('primarycategory')->take(4)->get();
        			$productSimilar=[];
        			if(!empty($products))
        			{
        				$products=$products->toArray();
        				foreach ($products as $value) {
        					if(!empty($value['ratecard']))
        					{
        						$url="";
        						$rc_url=$this->utilities->getRateCardBaseImage($value['ratecard']);
        						$rc_id=$this->utilities->getRateCardBaseID($value['ratecard']);
        						
        							if(!empty($rc_url))
        								$url=$rc_url;
									else if(!empty($value['image'])&&!empty($value['image']['primary']))
        									$url=$value['image']['primary'];
	        					array_push($productSimilar, [
	        							'product_id'=>$value['_id'],
	        							'ratecard_id'=>$rc_id,
	        							'product_title'=>$value['title'],
	        							'product_slug'=>$value['slug'],
	        							'url'=>$url,
	        							'type'=>'product',
	        							'product_category_slug'=>$value['primarycategory']['slug'],
	        							'product_category_id'=>$value['primarycategory']['_id'],
	        							'product_category_title'=>(!empty($value['primarycategory']['title'])?$value['primarycategory']['title']:""),
	        					]);
        					}
        				}
        				
        			}
        			$finalData=[];
        			if(!empty($categories)&&count($categories)>0)
        				$finalData['categories']=["title"=>$product_cat_title,/* "sub_title"=>(($productcategory_id==10)?"":"Get Fitter") ,*/"items"=>$categories];
        				(!empty($productSimilar)&&count($productSimilar)>0)?$finalData['similar_products']=["title"=>"Similar Products","sub_title"=>"Checkout other essential products for your workout","items"=>$productSimilar]:"";
        			$this->utilities->attachCart($finalData,false);
        			return ["status"=>200,"response"=>$finalData];
        		}
        		else return ['status'=>0,"message"=>"Not Ratecards found for productcategoryid : ".$productcategory_id];
        		
        			/* $productInfo=(!empty($productView['info'])?$productView['info']:"");
        			 $productFeatures=((!empty($productView['specification'])&&!empty($productView['specification']['primary'])&&!empty($productView['specification']['primary']['features']))?$productView['specification']['primary']['features']:[]);
        			 $productSpecsSecondary=((!empty($productView['specification'])&&!empty($productView['specification']['secondary']))?$productView['specification']['secondary']:[]);
        			 
        			 $this->utilities->customSort('order',$productSpecsSecondary);
        			 foreach ($productSpecsSecondary as &$value) {
        			 unset($value['order']);
        			 } */
        			
        	} catch (Exception $e) {
        		return ['status'=>0,"message"=>$this->utilities->baseFailureStatusMessage($e)];
        	}
        }
        
        public function getFinalCartSummary()
        {
        	try {
        		$t=[];
        		$tt=Request::header("Authorization");
        		Log::info(" token  ".print_r($tt,true));
        		$cart=$this->utilities->attachCart($t,true);
        		$dataCart=$this->utilities->getCartFinalSummary($cart['products'], $cart['_id']);
        		
        		if(!empty($dataCart)&&!empty($dataCart['status']) && $dataCart['status'] != 5)
        			$finalData=['status'=>200,"response"=>$dataCart['data']];
        		else return $dataCart;
        			$this->utilities->fetchCustomerAddresses($finalData['response']);
        			$cities=$this->utilities->getProductCities();
        			if(count($cities))$finalData['response']['cities']=$cities;
        		return $finalData;
        	} catch (Exception $e) {
        		return  ['status'=>0,"message"=>$this->utilities->baseFailureStatusMessage($e)];
        	}
        }

        
        
        public function getCustomerAddress()
        {
        	
        	try {
        	$t=[];
        	$tt=Request::header("Authorization");
        	Log::info(" token  ".print_r($tt,true));
        	$cart=$this->utilities->attachCart($t,true);
        	$dataCart=$this->utilities->getCartFinalSummary($cart['products'], $cart['_id']);

            $finalData=['status'=>200,"response"=>[]];
            
            if(!empty($dataCart)&&!empty($dataCart['status']) && $dataCart['status'] != 5)
        			$finalData=['status'=>200,"response"=>$dataCart['data']];
        		else return $dataCart;
        			$this->utilities->fetchCustomerAddresses($finalData['response']);
        			$cities=$this->utilities->getProductCities();
        			if(count($cities))$finalData['response']['cities']=$cities;
        		return $finalData;
        	} catch (Exception $e) {
        		return  ['status'=>0,"message"=>$this->utilities->baseFailureStatusMessage($e)];
        	}
        }
        
        
        
        public function setCustomerAddress()
        {
        	try {
        		$resp=["status"=>200,"messge"=>"Success"];
        		$data  =  Input::json()->all();
        		$rules = ['customer_address'=>'required'];
        		$validator = Validator::make($data,$rules);
        		
        		if ($validator->fails()) {
        			return ['status'=> 0,'message' => error_message($validator->errors())];
        		}
        		$added=$this->utilities->addCustomerAddress(null,$data['customer_address']);
        		return (!empty($added))?$resp:['status'=>0,"message"=>"Couldn't add address"];
        		
        	} catch (Exception $e) {
        		return  ['status'=>0,"message"=>$this->utilities->baseFailureStatusMessage($e)];
        	}
        }

        public function getCouponPackages(){
            return GiftCoupon::active()->get();
        }

        public function listValidCoupons()
        {
            $data = $_GET;
            $type = "vendor";
            $pass_id= "";
            if(isset($data['pass_id'])){
                $type = "pass";
                $pass_id = $data['pass_id'];
            }
            $order_id = (isset($data['order_id']))?$data['order_id']:"";
            if(isset($data['device_type']) && isset($data['app_version'])){
                if(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=>5.33])){
                    $coupons = $this->couponService->getlistvalidcoupons($type,$order_id,$pass_id);
                    return $resp=['status'=>200,"message"=>"Success","header"=>"Available Coupons","options"=>$coupons];
                } else {
                    return $resp=['status'=>200,"message"=>"Success","header"=>"Available Coupons","options"=>[]];
                }
            }else{
                $coupons = $this->couponService->getlistvalidcoupons($type,$order_id,$pass_id);
                return $resp=['status'=>200,"message"=>"Success","header"=>"Available Coupons","options"=>$coupons];
            }
        	try {
                $data = $_GET;
                Log::info($_GET);
        		//         		$rules= ['ratecard_id'=>'required'];	
        		// 	        	$validator = Validator::make($data,$rules);
        		// 	        	if ($validator->fails()) return ['status' => 400,'message' => error_message($validator->errors())];
        		
        		$customer_email=null;$customer_id=null;$customer_phone=null;
        		$jwt_token = Request::header('Authorization');
        		if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
        			$decoded = customerTokenDecode($jwt_token);
        			$customer_id = (int)$decoded->customer->_id;
        			$customer_email=$decoded->customer->email;
        			$customer_phone = $decoded->customer->contact_no;
        		}
        		$today_date = date("d-m-Y hh:mm:ss");
        		$coupons = Coupon::where('start_date', '<=', new \DateTime())->where('end_date', '>=', new \DateTime())->get();
        		if(empty($coupons)) return $resp;
        		$coupons=$coupons->toArray();
        		$device = Request::header('Device-Type');
        		
        		if($device &&!in_array($device, ['ios', 'android'])) $coupons=$this->utilities->removeMobileCodes($coupons);
        		
        		$finder_id =null;$service_id=null;$ratecard_type=null;$finder=null;$service=null;
        		
        		if(!empty($data['ratecard_id']))$ratecard = Ratecard::find(intval($data['ratecard_id']));
        		if(!empty($ratecard))
        		{
        			$ratecard_data = $ratecard->toArray();
        			if(!empty($ratecard_data["flags"]) && !empty($ratecard["flags"]["pay_at_vendor"]))
        				return $resp;
        				$finder_id = (int)$ratecard['finder_id'];
        				$service_id = (int)$ratecard['service_id'];
        				$ratecard_type=$ratecard_data['type'];
        				$finder = Finder::where('_id', $ratecard['finder_id'])->first(['flags']);
        				$service = Service::where('_id', $ratecard['service_id'])->first(['flags','servicecategory_id']);
        		}
        		
        		$coup=[];$all=[];$once_per_user=[];$fitternity_only=[];
        		
        		($finder_id==6168)?array_push($coupons,['code'=>"mad18"]):"";
        		$single=true;
        		
        		if($finder)
        		{
        			$couponRecieved = getDynamicCouponForTheFinder($finder);
        			if(!empty($couponRecieved)&&!empty($couponRecieved['code']))array_push($coup, $couponRecieved);
        		}
        		
        		foreach ($coupons as $coupon)
        		{
        			if(!empty($coupon)&&!empty($coupon['once_per_user']))
        				array_push($once_per_user, $coupon);
        				else array_push($all, $coupon);
        		}
        		
        		if(count($once_per_user)>0&&$customer_id)
        			$coupons=array_merge($all,$this->utilities->removeAlreadyUsedCodes($once_per_user,$customer_id));
        		else $coupons=$all;
        				
        			foreach ($coupons as $coupon)
        			{
        				
        				if(!empty($coupon['vendor_exclusive'])&&$finder_id&&$service_id )
        					if(!$this->utilities->allowSpecificvendors($coupon,$finder_id,$service_id,$single)) continue;
        					
        					if(!empty($coupon['fitternity_only']))
        						if(!($customer_id||$customer_email)||!$this->utilities->allowFitternityUsers($coupon,$customer_id,$customer_email,$single)) continue;
        						
        						if(!empty($coupon['finders'])&&$finder_id&&!in_array($finder_id."",$coupon['finders'])) continue;
        						
        						if((!empty($coupon['ratecard_type'])&&$ratecard_type&&!in_array($ratecard_type,$coupon['ratecard_type']))||!$ratecard_type)continue;
        						
        						if(!empty($coupon['campaign_only'])&&$coupon['campaign_only']=="1") continue;
        						
        						if(!empty($coupon['service_category_ids']))
        						{
        							if(!in_array($service['servicecategory_id'],$coupon['service_category_ids']))
        								continue;
        								else
        								{
        									if(!$customer_id) continue;
        									\Order::$withoutAppends = true;
        									$order_count = \Order::active()->where('customer_id',$customer_id)->where('coupon_code','like', $coupon['code'])->count();
        									if($order_count >= 4)
        										continue;
        								}
        						}
        						
        						//*****************************************************************************SYNCRON**********************************************************************
        						if(!empty($coupon['type']) && $coupon['type'] == 'syncron'){
        							if(!($customer_id||$customer_email)) continue;
        							if($coupon['total_used'] >= $coupon['total_available'])continue;
        							if(!empty($coupon['customer_emails']) && is_array($coupon['customer_emails']))
        								if(!in_array(strtolower($customer_email), $coupon['customer_emails']))continue;
        								\Booktrial::$withoutAppends = true;
        								$booktrial_count = \Booktrial::where('customer_email',$customer_email)->where('created_at','>=',new \MongoDate(strtotime(date('Y-m-d 00:00:00'))))->where('created_at','<=',new \MongoDate(strtotime(date('Y-m-d 23:59:59'))))->count();
        								if($booktrial_count>0)continue;
        						}
        						//*****************************************************************************SYNCRON**********************************************************************
        						
        						
        						//**************************************************************************CONDITIONS**********************************************************************
        						if((!empty($coupon['conditions']) && is_array($coupon['conditions']) )){
        							if(in_array('once_new_pps', $coupon['conditions']))
        							{
        								if(!($customer_phone||$customer_email))continue;
        								$prev_workout_session_count = \Booktrial::where('created_at', '>', new \DateTime('2018-04-22'))->where(function($query) use ($customer_email, $customer_phone){ return $query->orWhere('customer_email', $customer_email);})->where('type', 'workout-session')->count();
        								if($prev_workout_session_count)
        									continue;
        							}
        							else if(in_array('fitternity_employees', $coupon['conditions'])){
        								if(!in_array(strtolower($customer_email),Config::get('fitternityemails'))||!$customer_email)
        									continue;
        							}
        							else if(in_array('once_per_month', $coupon['conditions'])){
        								if(!($customer_phone||$customer_email))continue;
        								$prev_workout_session_count = \Order::active()->where('success_date', '>', new \DateTime(date('d-m-Y', strtotime('first day of this month'))))->where(function($query) use ($customer_email, $customer_phone){ return $query->orWhere('customer_phone', substr($customer_phone, -10))->orWhere('customer_email', $customer_email);})->where('coupon_code', 'Like', $coupon['code'])->where('coupon_discount_amount', '>', 0)->count();
        								if($prev_workout_session_count)
        									continue;
        							}
        						}
        						//**************************************************************************CONDITIONS**********************************************************************
        						
        						array_push($coup, $coupon);
        			}
        			$resp['options']=array_map(function ($e){
        				if(!empty($e['description']))$desc=$e['description'];
        				else if(!empty($e['text']))$desc=$e['text'];
        				else $desc="";
        				return ['code'=>strtoupper($e['code']),'description'=>$desc];
        				
        			},$coup);
        				return $resp;
        				
        	} catch (Exception $e) {
        		
        		$message="Message :: ".$e->getMessage()."  Code :: ".$e->getCode()."  File :: ".$e->getFile()."  Line :: ".$e->getLine();
        		return ['status'=>400,"message"=>$message];
        		
        	}
        }

        public function apicrashlogs(){

            try{
                
                $data = ["post_data"=>Input::all()];
                
                $data['header_data'] = apache_request_headers();
    
                $crashlog = new ApiCrashLog($data);

                
                if(empty(Config::get('app.debug')) && !empty($data["post_data"]["res_header"]) && (empty($data["post_data"]["res_header"]['Status']) || $data["post_data"]["res_header"]['Status'] != "200 OK")){
                    $crashlog->save();
                    
                    $response_400 = !empty($data['post_data']['res_status']) && $data['post_data']['res_status'] == 400;

                    if(!$response_400){

                        $message = json_encode(["text"=>strtoupper($data['header_data']['Device-Type'])."----".$crashlog['post_data']['url']]);
                        // $message = json_encode(['text'=?""]);
                        $c = curl_init();
                        curl_setopt($c, CURLOPT_URL, "https://hooks.slack.com/services/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");
                        curl_setopt($c, CURLOPT_POST, 1);
                        curl_setopt($c, CURLOPT_POSTFIELDS, $message);
                        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                        Log::info(curl_exec($c));
                    
                    }
                
                }
                // $customermailer = new CustomerMailer();
                // $mail = $customermailer->apicrashlogsSMS(['data'=>json_encode(array_only($crashlog->toArray(), ['post_data', 'created_at', '_id']))]);
                // $sms = $customersms->apicrashlogsSMS(['data'=>$crashlog['post_data']]);
    
                return ['status'=>200];
    
            }catch(Exception $e){
                Log::info($e);
                return ['status'=>500];
            }
        }
    // }
 
 	public function getLoyaltyAppropriationConsentMsg($customer_id, $order_id, $messageOnly = false) {
		return $this->utilities->getLoyaltyAppropriationConsentMsg($customer_id, $order_id, $messageOnly = false);
	}

}