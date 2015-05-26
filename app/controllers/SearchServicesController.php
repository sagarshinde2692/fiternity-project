<?php

class SearchServicesController extends \BaseController {

	protected $indice = "fitternity";

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



// {
//   "from": 0,
//   "size": 25,
//   "city":"mumbai",
//   "city_id":1,
//   "category":"gyms,crossfit",
//   "location" :"navi mumbai,kandivali east"
// }


// 	{ "from": 0, 
//  "size": 10, 
//  "city":"mumbai",
//  "category":"gyms"
// }
	public function getWorkoutsession(){

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

		//filters 
		$city_filter 				= 	($city != '') ? '{"terms" : {  "city": ["'.str_ireplace(',', '","', $city ).'"] }},'  : '';
		$category_filter 			= 	($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"] }},'  : '';		
		$subcategory_filter 		= 	($subcategory != '') ? '{"terms" : {  "subcategory": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('subcategory'))).'"] }},'  : '';		
		$location_filter 			= 	($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('location'))).'"] }},'  : '';	
		$workout_intensity_filter 	= 	($workout_intensity != '') ? '{"terms" : {  "workout_intensity": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workout_intensity'))).'"] }},'  : '';	
		$workout_tags_filter 		= 	($workout_tags != '') ? '{"terms" : {  "workout_tags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workout_tags'))).'"] }},'  : '';

		$shouldfilter = $mustfilter = '';
		
		// //used for location , category, 	
		// if($location_filter != ''){			
		// 	$should_filtervalue = trim($location_filter.$locationtags_filter,',');	
		// 	$shouldfilter = '"should": ['.$should_filtervalue.'],';	
		// }
		

		//used for category, subcategory, location, offering, facilities and workout_intensity
		if($city_filter != '' || $category_filter != '' || $subcategory_filter != '' || $location_filter != '' || $workout_intensity_filter != '' || $workout_tags_filter != ''){
			$must_filtervalue = trim($city_filter.$category_filter.$subcategory_filter.$location_filter.$workout_intensity_filter.$workout_tags_filter,',');	
			$mustfilter = '"must": ['.$must_filtervalue.']';		
		}

		if($shouldfilter != '' || $mustfilter != ''){
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
			"query": {
				"filtered": {
					"query": {'
					.$query.
					'}'.$filters.'
				}
			}
		}';

		// echo $body; exit;
		$serachbody = $body;
		$request = array(
			'url' => $this->elasticsearch_default_url."_search",
			'port' => 9200,
			'method' => 'POST',
			'postfields' => $serachbody
			);
		
		$search_results 	=	es_curl_request($request);

		
		return Response::json($search_results); exit;
	}



}
