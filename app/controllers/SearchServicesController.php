<?php

class SearchServicesController extends \BaseController {

	protected $indice 						= 	"fitternity";
	protected $facetssize 					=	10000;
	protected $limit 						= 	10000;
	protected $max_price 					= 	10000000;
	protected $elasticsearch_host           =   "";
	protected $elasticsearch_port           =   "";
	protected $elasticsearch_default_index  =   "";
	protected $elasticsearch_url            =   "";
	protected $elasticsearch_default_url    =   "";


	public function __construct() {

		parent::__construct();	
		$this->elasticsearch_default_url 		=	"http://".Config::get('app.elasticsearch_host_new').":".Config::get('app.elasticsearch_port_new').'/'.Config::get('app.elasticsearch_default_index').'/'.Config::get('app.elasticsearch_default_type').'/';
		$this->elasticsearch_url 				=	"http://".Config::get('app.elasticsearch_host_new').":".Config::get('app.elasticsearch_port_new').'/';
		$this->elasticsearch_host 				=	Config::get('app.elasticsearch_host_new');
		$this->elasticsearch_port 				=	Config::get('app.elasticsearch_port_new');
		$this->elasticsearch_default_index 		=	Config::get('app.elasticsearch_default_index');
	}

/*	
{ "from": 0, 
 "size": 10, 
 "city":"mumbai",
 "category":"gyms",
 "min_time": 20.3,
"max_time":  6.15
}
*/
public function getWorkoutsessions(){

	$searchParams 				= 	array();
	$type 						= 	"finder";		    	
	$filters 					=	"";	
	$selectedfields 			= 	"";		
	$from 						=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
	$size 						=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		
	$date 						=	(Input::json()->get('date')) ? Input::json()->get('date') : null;


	if($date == null){
		$weekday 				= 	(intval(date("H")) < 20 ) ? strtolower(date( "l", time() )) : strtolower(date( "l", strtotime('+1 day', time() ) ));
		$date 					= 	(intval(date("H")) < 20 ) ? date('d-m-Y',strtotime( time() )) : date('d-m-Y',strtotime( '+1 day', time() )) ;

	}else{
		$weekday 				= 	(intval(date("H")) < 20 ) ? strtolower(date( "l", strtotime($date) )) : strtolower(date( "l", strtotime('+1 day', strtotime($date) ) ));
		$date 					= 	(intval(date("H")) < 20 ) ? date('d-m-Y',strtotime($date)) : date('d-m-Y',strtotime( '+1 day', strtotime($date) )) ;
	}

	$city 						=	(Input::json()->get('city')) ? strtolower(Input::json()->get('city')) : 'mumbai';
	$city_id					=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;
	$category 					=	(Input::json()->get('category')) ? strtolower(Input::json()->get('category')) : '';		
	$subcategory 				=	(Input::json()->get('subcategory')) ? strtolower(Input::json()->get('subcategory')) : '';		
	$location 					=	(Input::json()->get('location')) ? strtolower(Input::json()->get('location')) : '';	
	$workout_intensity 			=	(Input::json()->get('workout_intensity')) ? strtolower(Input::json()->get('workout_intensity')) : '';			
	$workout_tags 				=	(Input::json()->get('workout_tags')) ? strtolower(Input::json()->get('workout_tags')) : '';	
	$weekdaytoday				= 	'';
	$weekdaytom					=	'';
	$weekday_filter				=   '';
	$time_range_filter			=	'';

	if( !(Input::json()->get('min_time')))
	{
		$weekdaytoday 				= 	strtolower(date( "l", time() ));    	
		$min_time					= 	intval(date("H")) == 23 ? 0 : intval(date("H")) + 1;
		if($min_time + 12 >= 24)
		{
			$weekdaytom 		= 	strtolower(date( "l", strtotime('+1 day', time() ) ));    	
			$max_time			=	$min_time - 12 ;
			$weekday_filter				=   '{ "terms": { "workoutsessionschedules.weekday": ["'.$weekdaytoday.'","'.$weekdaytom.'"] } },'; 
			$time_range_filter 	=	'{
				"bool": {
					"should": [
					{
						"range": {
							"workoutsessionschedules.start_time_24_hour_format": {
								"gte": '.$min_time.',
								"lte": 24
							}
						}
					},
					{
						"range": {
							"workoutsessionschedules.start_time_24_hour_format": {
								"gte": 0,
								"lte": '.$max_time.'
							}
						}
					}
					]
				}
			},';
		}
		else
		{
			$max_time				=	$min_time + 12 ;
			$weekday_filter				=   '{ "terms": { "workoutsessionschedules.weekday": ["'.$weekdaytoday.'"] } },'; 
			$time_range_filter			=  ($min_time != '' && $max_time != '') ? '{"range" : {"workoutsessionschedules.start_time_24_hour_format" : { "gte" : '.$min_time.',"lte": '.$max_time.'}} },'  : '';
		}     	
	}
	else
	{
		$weekdaytoday 			= 	strtolower(date( "l", time()));    	
		$min_time				= 	trim(strtolower(Input::json()->get('min_time'))) == 23 ? 0 : trim(strtolower(Input::json()->get('min_time'))) + 1;
		if($min_time + 12 >= 24)
		{
			$weekdaytom 		= 	strtolower(date( "l", strtotime('+1 day', time() ) ));    	
			$max_time			=	$min_time - 12 ;
			$weekday_filter				=   '{ "terms": { "workoutsessionschedules.weekday": ["'.$weekdaytoday.'","'.$weekdaytom.'"] } },'; 
			$time_range_filter 	=	'{
				"bool": {
					"should": [
					{
						"range": {
							"workoutsessionschedules.start_time_24_hour_format": {
								"gte": '.$min_time.',
								"lte": 24
							}
						}
					},
					{
						"range": {
							"workoutsessionschedules.start_time_24_hour_format": {
								"gte": 0,
								"lte": '.$max_time.'
							}
						}
					}
					]
				}
			},';
		}
		else
		{
			$max_time				=	$min_time + 12 ;
			$weekday_filter				=   '{ "terms": { "workoutsessionschedules.weekday": ["'.$weekdaytoday.'"] } },'; 
			$time_range_filter			=  ($min_time != '' && $max_time != '') ? '{"range" : {"workoutsessionschedules.start_time_24_hour_format" : { "gte" : '.$min_time.',"lte": '.$max_time.'}} },'  : '';
		}    
	}
	//$min_time 					=	(Input::json()->get('min_time')) ? trim(strtolower(Input::json()->get('min_time'))) : intval(date("H")) + 2;
	//$max_time 					=	(Input::json()->get('max_time')) ? trim(strtolower(Input::json()->get('max_time'))) : 24;
	$min_price 					=	(Input::json()->get('min_price')) ? trim(strtolower(Input::json()->get('min_price'))) : '';		
	$max_price 					=	(Input::json()->get('max_price')) ? trim(strtolower(Input::json()->get('max_price'))) : '';
	
	//filters 
	$city_filter 				= 	($city != '') ? '{"terms" : {  "city": ["'.str_ireplace(',', '","', $city ).'"] }},'  : '';
	$category_filter 			= 	($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"] }},'  : '';
    //$category_filter 			= 	($category != '') ? '{"terms" : {  "category": ["'.strtolower(Input::json()->get('category')).'"] }},' : '';
	$subcategory_filter 		= 	($subcategory != '') ? '{"terms" : {  "subcategory": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('subcategory'))).'"] }},'  : '';		
	$location_filter 			= 	($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('location'))).'"] }},'  : '';	
	$workout_intensity_filter 	= 	($workout_intensity != '') ? '{"terms" : {  "workout_intensity": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workout_intensity'))).'"] }},'  : '';	
	$workout_tags_filter 		= 	($workout_tags != '') ? '{"terms" : {  "workout_tags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workout_tags'))).'"] }},'  : '';

	//$time_range_filter			=  ($min_time != '' && $max_time != '') ? '{"range" : {"workoutsessionschedules.start_time_24_hour_format" : { "gte" : '.$min_time.',"lte": '.$max_time.'}} },'  : '';	
	$price_range_filter			=  ($min_price != '' && $max_price != '') ? '{"range" : {"workoutsessionschedules.price" : { "gte" : '.$min_price.',"lte": '.$max_price.'}} },'  : '';
	//$weekday_filter				=   '{ "terms": { "workoutsessionschedules.weekday": ["'.$weekday.'"] } },'; 

	$shouldfilter = $mustfilter = $workoutsesionfilter = '';
	
	//used for workout sesion, 	
	if($weekday_filter != "" || $time_range_filter != '' || $price_range_filter){			
		$workoutsesion_filtervalue = trim($weekday_filter.$time_range_filter.$price_range_filter,',');	

		$workoutsesionfilter = '{
			"nested": {
				"filter": {
					"bool": {
						"must": ['.$workoutsesion_filtervalue.']
					}
				},
				"path": "workoutsessionschedules",
				"inner_hits": {"sort": [ { "start_time_24_hour_format" : { "order":"asc" } } ] }
			}
		},';	
	}

	//used for category, subcategory, location, offering, facilities and workout_intensity
	if($city_filter != '' || $category_filter != '' || $subcategory_filter != '' || $location_filter != '' || $workout_intensity_filter != '' || $workout_tags_filter != '' || $workoutsesionfilter != ''){
		$must_filtervalue = trim($city_filter.$category_filter.$subcategory_filter.$location_filter.$workout_intensity_filter.$workout_tags_filter.$workoutsesionfilter,',');	
		$mustfilter = '"must": ['.$must_filtervalue.']';		
	}

	if($shouldfilter != '' || $mustfilter != '' ){
		$filtervalue = trim($shouldfilter.$mustfilter,',');	
		$filters = ',"filter": { 
			"bool" : {'.$filtervalue.'}
		},"_cache" : true';
	}

	$query = '"match_all": {}';
	$basecategory_score = '';

	$aggsval	= '{
		"all_categories": { "terms": {"field": "category","size": 10000 } },
		"all_subcategories": { "terms": {"field": "locationtags","size": 10000 } },
		"resultset_offerings": { "terms": {"field": "offerings","size": 10000 } },
		"resultset_facilities": { "terms": {"field": "facilities","size": 10000 } }			
	}';

	$aggsval	= '{
		"all_categories": {
			"global" : {}, 
			"aggs": {
				"city_filter": {
					"filter": { "terms": { "city": [ "'.$city.'" ] } },
					"aggs": {
						"city_categories": {
							"terms": { "field": "category", "size": 10000 }
						}
					}
				}
			}
		},
		"all_subcategories": {
			"global" : {}, 
			"aggs": {
				"category_filter": {
					"filter": { "terms": { "category": [ "'.$category.'" ] } },
					"aggs": {
						"city_subcategories": {
							"terms": { "field": "subcategory", "size": 10000  }
						}
					}
				}
			}
		}
	}';

	$body =	'{				
		"from": '.$from.',
		"size": '.$size.',
		"aggs": '.$aggsval.',
		"query": {
			"filtered": {
				"query": {'
				.$query.
				'}'.$filters.'
			}
		},
		"fields" : [ "_id", "finder_id", "name", "findername", "finderslug", "city", "category", "subcategory", "location", "workout_intensity", "workout_tags", "commercial_type"]
	}';

	// echo $body; exit;
	$serachbody = $body;
	
    //return $serachbody;
	$request = array(
		'url' => $this->elasticsearch_url."fitternity/service/_search",
		'port' => $this->elasticsearch_port,
		'method' => 'POST',
		'postfields' => $serachbody
		);

	$search_results 	=	es_curl_request($request);
	$response 			= 	['search_results' => json_decode($search_results,true),  'weekday' => $weekday,  'hour' => date("H"), 'min' => date("i"), 'date' => date("d-n-Y") ];

	return Response::json($response);
}

/*
{ 
"from": 0, 
"size": 10, 
"city":"mumbai",
"category":["gym"],
"location":["goregaon west","andheri west"],
"workout_intensity": ["all"],
"workout_tags":["cardio"]
}
*/

public function getRatecards(){

	// return Input::json()->all();
	$searchParams 				= 	array();
	$type 						= 	"finder";		    	
	$filters 					=	"";	
	$selectedfields 			= 	"";		
	$from 						=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
	$size 						=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		

	$city 						=	(Input::json()->get('city')) ? strtolower(Input::json()->get('city')) : 'mumbai';	
	$category 					=	(Input::json()->get('category')) ? strtolower(implode(',',Input::json()->get('category'))) : '';		
	$subcategory 				=	(Input::json()->get('subcategory')) ? strtolower(implode(',',Input::json()->get('subcategory'))) : '';		
	$location 					=	(Input::json()->get('location')) ? strtolower(implode(',',Input::json()->get('location'))) : '';	
	$workout_intensity 			=	(Input::json()->get('workout_intensity')) ? strtolower(implode(',',Input::json()->get('workout_intensity'))) : '';			
	$workout_tags 				=	(Input::json()->get('workout_tags')) ? strtolower(implode(',',Input::json()->get('workout_tags'))) : '';	

	$min_price 					=	(Input::json()->get('min_price')) ? trim(strtolower(Input::json()->get('min_price'))) : '';		
	$max_price 					=	(Input::json()->get('max_price')) ? trim(strtolower(Input::json()->get('max_price'))) : $this->max_price;	

	//filters 
	$city_filter 				= 	($city != '') ? '{"terms" : {  "city": ["'.str_ireplace(',', '","', $city ).'"] }},'  : '';
	$category_filter 			= 	($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower($category)).'"] }},'  : '';		
	$subcategory_filter 		= 	($subcategory != '') ? '{"terms" : {  "subcategory": ["'.str_ireplace(',', '","', strtolower($subcategory)).'"] }},'  : '';		
	$location_filter 			= 	($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","', strtolower($location)).'"] }},'  : '';	
	$workout_intensity_filter 	= 	($workout_intensity != '') ? '{"terms" : {  "workout_intensity": ["'.str_ireplace(',', '","', strtolower($workout_intensity)).'"] }},'  : '';	
	$workout_tags_filter 		= 	($workout_tags != '') ? '{"terms" : {  "workout_tags": ["'.str_ireplace(',', '","', strtolower($workout_tags)).'"] }},'  : '';
	$price_range_filter			=  	($min_price != '' && $max_price != '') ? '{"range" : {"ratecards.price" : { "gte" : '.$min_price.',"lte": '.$max_price.'}} },'  : '';

	$shouldfilter = $mustfilter = $ratecardfilter = '';

	//used for Ratecards, 	
	if($price_range_filter != ''){			
		$ratecard_filtervalue = trim($price_range_filter,',');	
		$ratecardfilter = '{
			"nested": {
				"query": {
					"bool": {
						"must": ['.$ratecard_filtervalue.']
					}
				},
				"path": "ratecards",
				"inner_hits":{}
			}
		},';	
	}

	//used for category, subcategory, location, offering, facilities and workout_intensity
	if($city_filter != '' || $category_filter != '' || $subcategory_filter != '' || $location_filter != '' || $workout_intensity_filter != '' || $workout_tags_filter != '' || $ratecardfilter != ''){
		$must_filtervalue = trim($city_filter.$category_filter.$subcategory_filter.$location_filter.$workout_intensity_filter.$workout_tags_filter.$ratecardfilter,',');	
		$mustfilter = '"must": ['.$must_filtervalue.']';		
	}


	if($shouldfilter != '' || $mustfilter != '' ){
		$filtervalue = trim($shouldfilter.$mustfilter,',');	
		$filters = ',"filter": { 
			"bool" : {'.$filtervalue.'}
		},"_cache" : true';
	}

	// if($category == ''){
	// 	$query = '"match_all": {}';
	// 	$basecategory_score = '';		
	// }else{
	// 	$query = '"multi_match": {
	// 		"query": "'.$category.'",
	// 		"fields": ["category","categorytags"]
	// 	}';	
	// 	$basecategory_score	= '{
	// 		"script_score": { "script": "(doc[\'category\'].value == \''.$category.'\' ? 10 : 0)" }
	// 	},';
	// }

	$query = '"match_all": {}';
	$basecategory_score = '';		

	// $aggsval	= '{
	// 	"all_categories" : {
	// 		"global" : {}, 
	// 		"aggs" : { 
	// 			"city_filter": {
	// 				"filter": { 
	// 					"terms": { "city": [ "'.$city.'" ] } 
	// 				},
	// 				"aggs": {
	// 					"city_categories": { "terms": { "field": "category", "size": 10000 } }
	// 				}
	// 			}
	// 		}
	// 	},
	// 	"all_subcategories" : {
	// 		"global" : {}, 
	// 		"aggs" : { 
	// 			"city_filter": {
	// 				"filter": { 
	// 					"terms": { "city": [ "'.$city.'" ] } 
	// 				},
	// 				"aggs": {
	// 					"city_subcategories": { "terms": { "field": "subcategory", "size": 10000 } }
	// 				}
	// 			}
	// 		}
	// 	},
	// 	"all_locations" : {
	// 		"global" : {}, 
	// 		"aggs" : { 
	// 			"city_filter": {
	// 				"filter": { 
	// 					"terms": { "city": [ "'.$city.'" ] } 
	// 				},
	// 				"aggs": {
	// 					"city_locations": { "terms": { "field": "location", "size": 10000 } }
	// 				}
	// 			}
	// 		}
	// 	},
	// 	"all_workout_intensity" : {
	// 		"global" : {}, 
	// 		"aggs" : { 
	// 			"city_filter": {
	// 				"filter": { 
	// 					"terms": { "city": [ "'.$city.'" ] } 
	// 				},
	// 				"aggs": {
	// 					"city_workout_intensity": { "terms": { "field": "workout_intensity", "size": 10000 } }
	// 				}
	// 			}
	// 		}
	// 	},
	// 	"all_workout_tags" : {
	// 		"global" : {}, 
	// 		"aggs" : { 
	// 			"city_filter": {
	// 				"filter": { 
	// 					"terms": { "city": [ "'.$city.'" ] } 
	// 				},
	// 				"aggs": {
	// 					"city_workout_tags" : { "terms": { "field": "workout_tags", "size": 10000 } }
	// 				}
	// 			}
	// 		}
	// 	}
	// }';

	// $aggsval	.=',
	// "categorised_subcategories": {
	// 	"global": {},
	// 	"aggs": {
	// 		"category_filter": {
	// 			"filter": {
	// 				"terms": {
	// 					"category": [
	// 					"'.$category.'"
	// 					]
	// 				}
	// 			},
	// 			"aggs": {
	// 				"category_subcategories": {
	// 					"terms": {
	// 						"field": "subcategory",
	// 						"size": 10000
	// 					}
	// 				}
	// 			}
	// 		}
	// 	}
	// }';

	$aggsval	= '{
		"all_categories" : {
			"terms": { "field": "category", "size": 10000 } 
		},
		"all_subcategories" : {
			"terms": { "field": "subcategory", "size": 10000 } 
		},
		"all_locations" : {
			"terms": { "field": "location", "size": 10000 } 
		},
		"all_workout_intensity" : {
			"terms": { "field": "workout_intensity", "size": 10000 }
		},
		"all_workout_tags" : {
			"terms": { "field": "workout_tags", "size": 10000 }
		}
	}';

	$body =	'{				
		"from": '.$from.',
		"size": '.$size.',
		"aggs": '.$aggsval.',
		"_source": {
    		"exclude": ["*_snow", "workoutsessionschedules"]
  		},
		"query": {
			"filtered": {
				"query": {'
				.$query.
				'}'.$filters.'
			}
		},
		"fields" : ["_id",
		"name",
		"finder_id",
		"findername", 
		"finderslug",
		"commercial_type",
		"city",
		"category",
		"subcategory",
		"geolocation",
		"location",
		"workout_intensity",
		"workout_tags",
		"commercial_type"]
	}';

	

	echo $body; exit;

	$serachbody = $body;
	$request = array(
		'url' => $this->elasticsearch_url."fitternity/service/_search",
		'port' => $this->elasticsearch_port,
		'method' => 'POST',
		'postfields' => $serachbody
		);

	$search_results 	=	es_curl_request($request);


	return Response::json($search_results); 
}



public function geoLocationService(){

	$page_no 			=	(Input::json()->get('page')) ? Input::json()->get('page') : 1;
	$page_size 			=	(Input::json()->get('size')) ? Input::json()->get('size') : 10;
	$from_range 		=	(Input::json()->get('from_range')) ? Input::json()->get('from_range') : 0;
	$to_range 			=	(Input::json()->get('to_range')) ? Input::json()->get('to_range') : 10;
	$category 			=	(Input::json()->get('category')) ? Input::json()->get('category') : '';
	$lat 				=	(Input::json()->get('lat')) ? Input::json()->get('lat') : '';
	$lon 				=	(Input::json()->get('lon')) ? Input::json()->get('lon') : '';
	$city               =   (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';

	if($lat == '' || $lon == '' ){
		$response 		= 	[ 'search_results' => []];
		return Response::json($response); 
	}

	$date = getdate();

    //$date 					= 	date('d-m-Y',strtotime($date));
	$weekday 				= 	(intval(date("H")) < 22 ) ? strtolower(date( "l", time() )) : strtolower(date( "l", strtotime('+1 day', time() ) ));
	$min_time = (intval(date("H")) < 21 ) ? intval(date("H")) + 2 : 0;
	$max_time = 24;

	$category_filter 			= 	($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"] }},'  : '';
	$geo_location_filter 			= 	($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';
	$time_range_filter			=  ($min_time != '' && $max_time != '') ? '{"range" : {"workoutsessionschedules.start_time_24_hour_format" : { "gte" : '.$min_time.',"lte": '.$max_time.'}} },'  : '';
	$weekday_filter				=   '{ "terms": { "workoutsessionschedules.weekday": ["'.$weekday.'"] } },';

	$shouldfilter = $mustfilter = $workoutsesionfilter = '';

	if($weekday_filter != "" || $time_range_filter != '' ) {
		$workoutsesion_filtervalue = trim($weekday_filter . $time_range_filter, ',');

		$workoutsesionfilter = '{
			"nested": {
				"filter": {
					"bool": {
						"must": [' . $workoutsesion_filtervalue . ']
					}
				},
				"path": "workoutsessionschedules",
				"inner_hits" : {"sort": [ { "start_time_24_hour_format" : { "order":"asc" } } ] }
			}
		},';
	}

	if($geo_location_filter != '' || $workoutsesionfilter != '' ){
		$must_filtervalue = trim($geo_location_filter.$workoutsesionfilter,',');
		$mustfilter = '"must": ['.$must_filtervalue.']';
	}

	if($category_filter != ''){
		$must_not_filtervalue=trim($category_filter,',');
	}

	if($shouldfilter != '' || $mustfilter != ''  ){
		$filtervalue = trim($shouldfilter.$mustfilter,',');
		$filters = ',"filter": {
			"bool" : {'.$filtervalue.'}
		},"_cache" : true';
	}

	$query = '"match_all": {}';
	$from = ($page_no-1)*$page_size;

	$aggsval	= '{
		"all_categories" : {
			"global" : {},
			"aggs" : {
				"city_filter": {
					"filter": {
						"terms": { "city": [ "'.$city.'" ] }
					},
					"aggs": {
						"city_categories": { "terms": { "field": "category", "size": 10000 } }
					}
				}
			}
		},
		"all_subcategories" : {
			"global" : {},
			"aggs" : {
				"city_filter": {
					"filter": {
						"terms": { "city": [ "'.$city.'" ] }
					},
					"aggs": {
						"city_subcategories": { "terms": { "field": "subcategory", "size": 10000 } }
					}
				}
			}
		},
		"all_workout_intensity" : {
			"global" : {},
			"aggs" : {
				"city_filter": {
					"filter": {
						"terms": { "city": [ "'.$city.'" ] }
					},
					"aggs": {
						"city_workout_intensity": { "terms": { "field": "workout_intensity", "size": 10000 } }
					}
				}
			}
		},
		"all_workout_tags" : {
			"global" : {},
			"aggs" : {
				"city_filter": {
					"filter": {
						"terms": { "city": [ "'.$city.'" ] }
					},
					"aggs": {
						"city_workout_tags": { "terms": { "field": "workout_tags", "size": 10000 } }
					}
				}
			}
		},
		"all_locations" : {
			"global" : {},
			"aggs" : {
				"city_filter": {
					"filter": {
						"terms": { "city": [ "'.$city.'" ] }
					},
					"aggs": {
						"city_locations": { "terms": { "field": "location", "size": 10000 } }
					}
				}
			}
		}
	}';

	$body =	'{
		"from": '.$from.',
		"size": '.$page_size.',
		"aggs": '.$aggsval.',
		"query": {
			"filtered": {
				"query": {'
				.$query.
				'}'.$filters.'
			}
		},
		"fields" : ["_id",
		"name",
		"finder_id",
		"findername",
		"finderslug",
		"city",
		"category",
		"subcategory",
		"geolocation",
		"location",
		"workout_intensity",
		"workout_tags",
		"commercial_type"],
		"sort": [
		{
			"_geo_distance": {
				"geolocation": {
					"lat": '.$lat.',
					"lon":  '.$lon.'},
					"order": "asc",
					"unit": "km",
					"distance_type": "plane"
				}
			}
			]
		}';
		$serachbody = $body;
        //return $serachbody;
		$request = array(
			'url' => $this->elasticsearch_url."fitternity/service/_search",
			'port' => $this->elasticsearch_port,
			'method' => 'POST',
			'postfields' => $serachbody
			);

		$search_results 	=	es_curl_request($request);
		$response 		= 	[
		'search_results' => json_decode($search_results,true),
		'weekday' => $weekday,
		'hour' => date("H"), 'min' => date("i"),
		'date' => date("d-n-Y") ];

		return Response::json($response);
	}


}
