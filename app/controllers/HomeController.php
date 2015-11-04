<?php

//use Moa\API\Provider\ProviderInterface;

class HomeController extends BaseController {

	public function __construct() {
		parent::__construct();	
	}

	public function getHomePageDatav2($city = 'mumbai',$cache = true){   

		$home_by_city = $cache ? Cache::tags('home_by_city')->has($city) : false;

		if(!$home_by_city){
			$categorytags = $locations = $popular_finders =	$recent_blogs =	array();
			$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
			$city_name 		= 	$citydata['name'];
			$city_id		= 	(int) $citydata['_id'];	

			$categorytags			= 		Findercategorytag::active()->whereIn('cities',array($city_id))->where('_id', '!=', 42)->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
			$locations				= 		Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));

			$homepage 				= 		Homepage::where('city_id', '=', $city_id)->get()->first();						
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

			array_set($footer_finders,  'footer_block1_finders', $footer_block1_finders);									
			array_set($footer_finders,  'footer_block2_finders', $footer_block2_finders);									
			array_set($footer_finders,  'footer_block3_finders', $footer_block3_finders);									
			array_set($footer_finders,  'footer_block4_finders', $footer_block4_finders);	

			array_set($footer_finders,  'footer_block1_title', (isset($homepage['footer_block1_title']) && $homepage['footer_block1_title'] != '') ? $homepage['footer_block1_title'] : '');									
			array_set($footer_finders,  'footer_block2_title', (isset($homepage['footer_block2_title']) && $homepage['footer_block2_title'] != '') ? $homepage['footer_block2_title'] : '');									
			array_set($footer_finders,  'footer_block3_title', (isset($homepage['footer_block3_title']) && $homepage['footer_block3_title'] != '') ? $homepage['footer_block3_title'] : '');									
			array_set($footer_finders,  'footer_block4_title', (isset($homepage['footer_block4_title']) && $homepage['footer_block4_title'] != '') ? $homepage['footer_block4_title'] : '');	

			//return Response::json($finder_ids);
			$category_finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
			->with(array('location'=>function($query){$query->select('_id','name','slug');}))
			->whereIn('_id', $finder_ids)
			->remember(Config::get('app.cachetime'))
			->get(array('_id','average_rating','category_id','coverimage','finder_coverimage','slug','title','category','location_id','location','total_rating_count'))
			->groupBy('category.name')
			->toArray();

			array_set($popular_finders,  'gyms', array_get($category_finders, 'gyms'));		
			array_set($popular_finders,  'yoga', array_get($category_finders, 'yoga'));		
			array_set($popular_finders,  'dance', array_get($category_finders, 'dance'));									

			$recent_blogs	 		= 		Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
			->with('categorytags')
			->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
			->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
			->where('status', '=', '1')
			->orderBy('_id', 'desc')
			->remember(Config::get('app.cachetime'))
			->get(array('_id','author_id','category_id','categorytags','coverimage','finder_coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
			->take(4)->toArray();		

			$collections 			= 	Findercollection::active()->where('city_id', '=', intval($city_id))->orderBy('ordering')->get(array('name', 'slug', 'coverimage', 'ordering' ));	
			
			$homedata 				= 	array('categorytags' => $categorytags,
				'locations' => $locations,
				'popular_finders' => $popular_finders,       
				'footer_finders' => $footer_finders,    
				'recent_blogs' => $recent_blogs,
				'city_name' => $city_name,
				'city_id' => $city_id,
				'collections' => $collections
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
			$city_name 		= 	$citydata['name'];
			$city_id		= 	(int) $citydata['_id'];	

			$homepage 				= 		Homepage::where('city_id', '=', $city_id)->get()->first();						
			$str_finder_ids 		= 		$homepage['gym_finders'].",".$homepage['yoga_finders'].",".$homepage['zumba_finders'];
			$finder_ids 			= 		array_map('intval', explode(",",$str_finder_ids));

			$footer_block1_ids 		= 		array_map('intval', explode(",", $homepage['footer_block1_ids'] ));
			$footer_block2_ids 		= 		array_map('intval', explode(",", $homepage['footer_block2_ids'] ));
			$footer_block3_ids 		= 		array_map('intval', explode(",", $homepage['footer_block3_ids'] ));
			$footer_block4_ids 		= 		array_map('intval', explode(",", $homepage['footer_block4_ids'] ));
			$footer_block5_ids 		= 		array_map('intval', explode(",", $homepage['footer_block5_ids'] ));
			$footer_block6_ids 		= 		array_map('intval', explode(",", $homepage['footer_block6_ids'] ));

			//return Response::json($finder_ids);
			$category_finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
			->with(array('location'=>function($query){$query->select('_id','name','slug');}))
			->whereIn('_id', $finder_ids)
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


			$recent_blogs	 		= 		Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
			->with('categorytags')
			->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
			->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
			->where('status', '=', '1')
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


	public function getFooterByCity($city = 'mumbai',$cache = true){   

		$footer_by_city = $cache ? Cache::tags('footer_by_city')->has($city) : false;

		if(!$footer_by_city){
			$footer_finders 			=		array();
			$citydata 					=		City::where('slug', '=', $city)->first(array('name','slug'));
			$city_name 					= 		$citydata['name'];
			$city_id					= 		(int) $citydata['_id'];	
			$homepage 					= 		Homepage::where('city_id', '=', $city_id)->get()->first();						

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

			$footerdata 	= 	array('footer_finders' => $footer_finders, 'city_name' => $city_name, 'city_id' => $city_id);
			Cache::tags('footer_by_city')->put($city, $footerdata, Config::get('cache.cache_time'));
		}

		return Response::json(Cache::tags('footer_by_city')->get($city));
	}


	public function getCityLocation($city = 'mumbai',$cache = true){   

		$location_by_city = $cache ? Cache::tags('location_by_city')->has($city) : false;
		if(!$location_by_city){
			$categorytags = $locations  =	array();
			$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
			$city_name 		= 	$citydata['name'];
			$city_id		= 	(int) $citydata['_id'];	

			$locations				= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));
			$homedata 				= 	array('locations' => $locations );

			Cache::tags('location_by_city')->put($city,$homedata,Config::get('cache.cache_time'));
		}

		return Response::json(Cache::tags('location_by_city')->get($city));
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
														//->whereIn('_id', array(570))
		->get(array('_id','title','slug','category_id','category','special_offer_title'))
														//->take(1)
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
		->whereIn('_id', array(1029,1030,1032,1033,1034,1035,1554,1705,1706,1870,4585))
		->remember(Config::get('app.cachetime'))
		->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','contact'))
		->toArray();

		return Response::json($finders);										
	}



	public function getcollecitonnames($city = 'mumbai', $cache = true){

		$collection_by_city_list = $cache ? Cache::tags('collection_by_city_list')->has($city) : false;
		if(!$collection_by_city_list){
			$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
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

		$finder_by_collection_list = $cache ? Cache::tags('finder_by_collection_list')->has($city."_".$slug) : false;
		if(!$finder_by_collection_list){
			$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
			$city_id		= 	(int) $citydata['_id'];	
			$collection 	= 	Findercollection::where('slug', '=', trim($slug))->where('city_id', '=', $city_id)->first(array());
			$finder_ids 	= 	array_map('intval', explode(",", $collection['finder_ids']));

			$collection_finders =	Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
			->with(array('location'=>function($query){$query->select('_id','name','slug');}))
			->whereIn('_id', $finder_ids)
			->remember(Config::get('app.cachetime'))
			->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','total_rating_count','info'))
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


}																																																																																																																																																																																																																																																																										