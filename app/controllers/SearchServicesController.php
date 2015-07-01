<?php

class SearchServicesController extends \BaseController {

	protected $indice 						= 	"fitternity";
	protected $facetssize 					=	10000;
	protected $limit 						= 	10000;
	protected $elasticsearch_host           =   "";
	protected $elasticsearch_port           =   "";
	protected $elasticsearch_default_index  =   "";
	protected $elasticsearch_url            =   "";
	protected $elasticsearch_default_url    =   "";

	public function __construct() {

		parent::__construct();	
		$this->elasticsearch_default_url 		=	"http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/'.Config::get('app.elasticsearch_default_index').'/'.Config::get('app.elasticsearch_default_type').'/';
		$this->elasticsearch_url 				=	"http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/';
		$this->elasticsearch_host 				=	Config::get('app.elasticsearch_host');
		$this->elasticsearch_port 				=	Config::get('app.elasticsearch_port');
		$this->elasticsearch_default_index 		=	Config::get('app.elasticsearch_default_index');
	}



// 	{ "from": 0, 
//  "size": 10, 
//  "city":"mumbai",
//  "category":"gyms",
//  "min_time": 20.3,
// "max_time":  6.15
// }
	public function getWorkoutsessions(){

		$searchParams 				= 	array();
		$type 						= 	"finder";		    	
		$filters 					=	"";	
		$selectedfields 			= 	"";		
		$from 						=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 						=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;	

		// $date 						= 	(intval(date("H")) > 20 ) ? date("H") : date("d-m-Y g:i A", strtotime('+1 day', time()) );	
		$weekday 					= 	(intval(date("H")) < 20 ) ? strtolower(date( "l", time() )) : strtolower(date( "l", strtotime('+1 day', time() ) ));	
		// return intval(date("H")). " - ".strtolower(date( "l", time() )) ." - ". $weekday;

		$city 						=	(Input::json()->get('city')) ? strtolower(Input::json()->get('city')) : 'mumbai';	
		$city_id					=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;
		$category 					=	(Input::json()->get('category')) ? strtolower(Input::json()->get('category')) : '';		
		$subcategory 				=	(Input::json()->get('subcategory')) ? strtolower(Input::json()->get('subcategory')) : '';		
		$location 					=	(Input::json()->get('location')) ? strtolower(Input::json()->get('location')) : '';	
		$workout_intensity 			=	(Input::json()->get('workout_intensity')) ? strtolower(Input::json()->get('workout_intensity')) : '';			
		$workout_tags 				=	(Input::json()->get('workout_tags')) ? strtolower(Input::json()->get('workout_tags')) : '';	

		$min_time 					=	(Input::json()->get('min_time')) ? trim(strtolower(Input::json()->get('min_time'))) : intval(date("H")) + 2;		
		$max_time 					=	(Input::json()->get('max_time')) ? trim(strtolower(Input::json()->get('max_time'))) : '';		
		$min_price 					=	(Input::json()->get('min_price')) ? trim(strtolower(Input::json()->get('min_price'))) : '';		
		$max_price 					=	(Input::json()->get('max_price')) ? trim(strtolower(Input::json()->get('max_price'))) : '';		

		//filters 
		$city_filter 				= 	($city != '') ? '{"terms" : {  "city": ["'.str_ireplace(',', '","', $city ).'"] }},'  : '';
		$category_filter 			= 	($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"] }},'  : '';		
		$subcategory_filter 		= 	($subcategory != '') ? '{"terms" : {  "subcategory": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('subcategory'))).'"] }},'  : '';		
		$location_filter 			= 	($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('location'))).'"] }},'  : '';	
		$workout_intensity_filter 	= 	($workout_intensity != '') ? '{"terms" : {  "workout_intensity": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workout_intensity'))).'"] }},'  : '';	
		$workout_tags_filter 		= 	($workout_tags != '') ? '{"terms" : {  "workout_tags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workout_tags'))).'"] }},'  : '';

		$time_range_filter			=  ($min_time != '' && $max_time != '') ? '{"range" : {"workoutsessionschedules.start_time_24_hour_format" : { "gte" : '.$min_time.',"lte": '.$max_time.'}} },'  : '';
		$price_range_filter			=  ($min_price != '' && $max_price != '') ? '{"range" : {"workoutsessionschedules.price" : { "gte" : '.$min_price.',"lte": '.$max_price.'}} },'  : '';
		$weekday_filter				=   '{ "terms": { "workoutsessionschedules.weekday": ["'.$weekday.'"] } },'; 

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
                "path": "workoutsessionschedules"
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

		if($category == ''){
			$query = '"match_all": {}';
			$basecategory_score = '';		
		}else{
			$query = '"multi_match": {
				"query": "'.$category.'",
				"fields": ["category","categorytags"]
			}';	
			$basecategory_score	= '{
				"script_score": { "script": "(doc[\'category\'].value == \''.$category.'\' ? 10 : 0)" }
			},';
		}

		$query = '"match_all": {}';
		$basecategory_score = '';		

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
			"size": '.$size.',
			"aggs": '.$aggsval.',
			"query": {
				"filtered": {
					"query": {'
					.$query.
					'}'.$filters.'
				}
			},
			"partial_fields": {
				"serviceinfo": {
					"include": [
					"_id",
					"name",
					"findername",
					"finderslug",
					"city",
					"category",
					"subcategory",
					"workoutsessionschedules",
					"location",
					"workout_intensity",
					"workout_tags"
					]
				}
			}
		}';

		// echo $body; exit;
		$serachbody = $body;
		$request = array(
			'url' => $this->elasticsearch_url."fitternity/service/_search",
			'port' => 9200,
			'method' => 'POST',
			'postfields' => $serachbody
			);
		
		$search_results 	=	es_curl_request($request);
		$response 		= 	[ 'search_results' => json_decode($search_results,true), 'weekday' => $weekday, 'hour' => date("H"), 'min' => date("i") ];

		return Response::json($response);
	}


// 	{ "from": 0, 
//  "size": 10, 
//  "city":"mumbai",
//  "category":"gyms",
//  "min_time": 20.3,
// "max_time":  6.15
// }
	public function getRatecards(){

		$searchParams 				= 	array();
		$type 						= 	"finder";		    	
		$filters 					=	"";	
		$selectedfields 			= 	"";		
		$from 						=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 						=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		

		$city 						=	(Input::json()->get('city')) ? strtolower(Input::json()->get('city')) : 'mumbai';	
		$city_id					=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;
		$category 					=	(Input::json()->get('category')) ? strtolower(Input::json()->get('category')) : '';		
		$subcategory 				=	(Input::json()->get('subcategory')) ? strtolower(Input::json()->get('subcategory')) : '';		
		$location 					=	(Input::json()->get('location')) ? strtolower(Input::json()->get('location')) : '';	
		$workout_intensity 			=	(Input::json()->get('workout_intensity')) ? strtolower(Input::json()->get('workout_intensity')) : '';			
		$workout_tags 				=	(Input::json()->get('workout_tags')) ? strtolower(Input::json()->get('workout_tags')) : '';	

		$min_time 					=	(Input::json()->get('min_time')) ? trim(strtolower(Input::json()->get('min_time'))) : '';		
		$max_time 					=	(Input::json()->get('max_time')) ? trim(strtolower(Input::json()->get('max_time'))) : '';		
		$min_price 					=	(Input::json()->get('min_price')) ? trim(strtolower(Input::json()->get('min_price'))) : '';		
		$max_price 					=	(Input::json()->get('max_price')) ? trim(strtolower(Input::json()->get('max_price'))) : '';		

		//filters 
		$city_filter 				= 	($city != '') ? '{"terms" : {  "city": ["'.str_ireplace(',', '","', $city ).'"] }},'  : '';
		$category_filter 			= 	($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"] }},'  : '';		
		$subcategory_filter 		= 	($subcategory != '') ? '{"terms" : {  "subcategory": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('subcategory'))).'"] }},'  : '';		
		$location_filter 			= 	($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('location'))).'"] }},'  : '';	
		$workout_intensity_filter 	= 	($workout_intensity != '') ? '{"terms" : {  "workout_intensity": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workout_intensity'))).'"] }},'  : '';	
		$workout_tags_filter 		= 	($workout_tags != '') ? '{"terms" : {  "workout_tags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workout_tags'))).'"] }},'  : '';


		// $time_range_filter			=  ($min_time != '' && $max_time != '') ? '{"range" : {"ratecard.start_time_24_hour_format" : { "gte" : '.$min_time.',"lte": '.$max_time.'}} },'  : '';
		$price_range_filter			=  ($min_price != '' && $max_price != '') ? '{"range" : {"ratecard.price" : { "gte" : '.$min_price.',"lte": '.$max_price.'}} },'  : '';

		$shouldfilter = $mustfilter = $ratecardfilter = '';
		
		//used for Ratecards, 	
		if($price_range_filter != ''){			
			$ratecard_filtervalue = trim($price_range_filter,',');	

			$ratecardfilter = '{
              "nested": {
                "filter": {
                  "bool": {
                    "must": ['.$ratecard_filtervalue.']
                  }
                },
                "path": "ratecard"
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

		if($category == ''){
			$query = '"match_all": {}';
			$basecategory_score = '';		
		}else{
			$query = '"multi_match": {
				"query": "'.$category.'",
				"fields": ["category","categorytags"]
			}';	
			$basecategory_score	= '{
				"script_score": { "script": "(doc[\'category\'].value == \''.$category.'\' ? 10 : 0)" }
			},';
		}

		$query = '"match_all": {}';
		$basecategory_score = '';		


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
			"size": '.$size.',
			"aggs": '.$aggsval.',
			"query": {
				"filtered": {
					"query": {'
					.$query.
					'}'.$filters.'
				}
			},
			"partial_fields": {
				"serviceinfo": {
					"include": [
					"city",
					"category",
					"subcategory",
					"ratecards",
					"location",
					"workout_intensity",
					"workout_tags"
					]
				}
			}
		}';

		// echo $body; exit;

		$serachbody = $body;
		$request = array(
			'url' => $this->elasticsearch_url."fitternity/service/_search",
			'port' => 9200,
			'method' => 'POST',
			'postfields' => $serachbody
			);
		
		$search_results 	=	es_curl_request($request);

		
		return Response::json($search_results); 
	}
	



}
