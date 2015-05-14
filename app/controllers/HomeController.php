<?php

//use Moa\API\Provider\ProviderInterface;

class HomeController extends BaseController {

	public function __construct() {
     	parent::__construct();	
    }

	public function getHomePageData(){   


		$categorytags = $locations = $popular_finders =	$recent_blogs =	array();						
		
		$finder_gym_slugs 		=		array('beyond-fitness-borivali-west', 
											   'your-fitness-club-mumbai-central', 
											   'golds-gym-lower-parel', 
											   '48-fitness-lokhandwala');

		$finder_yoga_slugs 		=		array('nuage-hot-yoga-lokhandwala', 
											   'samanta-duggal', 
											   'cosmic-fusion-santacruz-west', 
											   'yoga-hut-borivali-west');

		$finder_zumba_slugs 	=		array('baile-de-salon', 
											   'rudra-shala-malad-west', 
											   'studio-balance-hughes-road', 
											   'adil-dance-academy-versova');

		$finder_slugs 			= 		array_merge($finder_gym_slugs,$finder_yoga_slugs,$finder_zumba_slugs);

		$categorytags			= 		Findercategorytag::active()->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
		$locations				= 		Location::active()->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));

		$category_finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->whereIn('slug', $finder_slugs)
												->remember(Config::get('app.cachetime'))
												->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count'))
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
											->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
											->take(4)->toArray();		

		$homedata 				= 	array(			
										'categorytags' => $categorytags,
										'locations' => $locations,
										'popular_finders' => $popular_finders,       
										'recent_blogs' => $recent_blogs
									);
		return Response::json($homedata);
	}


	public function getHomePageDatav2($city = 'mumbai'){   

		$categorytags = $locations = $popular_finders =	$recent_blogs =	array();
		$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
		$city_name 		= 	$citydata['name'];
		$city_id		= 	(int) $citydata['_id'];	

		$categorytags			= 		Findercategorytag::active()->whereIn('cities',array($city_id))->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
		$locations				= 		Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));

		$homepage 				= 		Homepage::where('city_id', '=', $city_id)->get(array('gym_finders','yoga_finders','zumba_finders'))->first();						
		$str_finder_ids 		= 		$homepage['gym_finders'].",".$homepage['yoga_finders'].",".$homepage['zumba_finders'];
		$finder_ids 			= 		array_map('intval', explode(",",$str_finder_ids));
		//return Response::json($finder_ids);
		$category_finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->whereIn('_id', $finder_ids)
												->remember(Config::get('app.cachetime'))
												->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count'))
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
											->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
											->take(4)->toArray();		

		$homedata 				= 	array('categorytags' => $categorytags,
										'locations' => $locations,
										'popular_finders' => $popular_finders,       
										'recent_blogs' => $recent_blogs,
										'city_name' => $city_name,
										'city_id' => $city_id
									);
		return Response::json($homedata);
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
		$finders							= 		array();

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
										->whereIn('_id', array(1029,1030,1032,1033,1034,1035,1554,1705,1706))
										->remember(Config::get('app.cachetime'))
										->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','contact'))
										->toArray();

		return Response::json($finders);										
	}



	public function getcollecitonnames($city = 'mumbai'){
		
		$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));
		
		$city_id		= 	(int) $citydata['_id'];	
		
		$collections 	= 	Findercollection::active()->where('city_id', '=', $city_id)->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name', 'slug', 'coverimage', 'ordering' ));	

		if(count($collections) < 1){
		
			$resp 	= 	array('status' => 200,'collections' => $collections,'message' => 'No collections yet :)');
		
			return Response::json($resp);
		
		}

		$resp 	= 	array('status' => 200,'collections' => $collections,'message' => 'List of collections names');
		
		return Response::json($resp);			

	}



	public function getcollecitonfinders($city, $slug){

		$citydata 		=	City::where('slug', '=', $city)->first(array('name','slug'));

		$city_id		= 	(int) $citydata['_id'];	

		$collection 		= 	Findercollection::where('slug', '=', trim($slug))->where('city_id', '=', $city_id)->first(array());
		
		$finder_ids 		= 	array_map('intval', explode(",", $collection['finder_ids']));

		$collection_finders =	Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
												->with(array('location'=>function($query){$query->select('_id','name','slug');}))
												->whereIn('_id', $finder_ids)
												->remember(Config::get('app.cachetime'))
												->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count'))
												->toArray();

		$data 				= 	array('status' => 200,'collection' => $collection,'collection_finders' => $collection_finders);
		
		return Response::json($data);	
	}


	public function fitcardfinders(){

		$fitcardids = array_unique(array(1664,1214,1587,147,217,417,579,596,752,825,827,878,905,1021,1031,1376,1388,1451,1493,1495,1516,1630,1650,1765,1783,1965,2211,2736,1473,741,984,1496,2810,1752,2818,1346,
										1668,2755,2833,3109,1751,624,393,1692,2865,1770,677,902,1243,1623,1766,224,2808,232,566,881,900,1563,968,1704,1885,351,424,599,571,816,1588,1080,131,1606,1813,1673,1732,
										590,1242,1431,1607,1452,1747,400,613,616,633,975,987,1309,1604,1676,1750,2207,341,1784,1656,1642,1621,575,877));
		
		$finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
										->with(array('location'=>function($query){$query->select('_id','name','slug');}))
										->whereIn('_id', $fitcardids)
										->remember(Config::get('app.cachetime'))
										->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','contact'))
										->toArray();

		return Response::json($finders);										
	}



}																																																																																																																																																																																																																																																																										