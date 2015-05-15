<?php

class SearchController extends \BaseController {

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
	public function getFindersv4(){


		$searchParams 		= 	array();
		$type 				= 	"finder";		    	
		$filters 			=	"";	
		$selectedfields 	= 	"";		
		$from 				=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 				=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		

		$city 				=	(Input::json()->get('city')) ? strtolower(Input::json()->get('city')) : 'mumbai';	
		$city_id			=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;

		$category 				=	(Input::json()->get('category')) ? strtolower(Input::json()->get('category')) : '';		
		$location 				=	(Input::json()->get('location')) ? strtolower(Input::json()->get('location')) : '';		
		$offerings 				=	(Input::json()->get('offerings')) ? strtolower(Input::json()->get('offerings')) : '';		
		$facilities 			=	(Input::json()->get('facilities')) ? strtolower(Input::json()->get('facilities')) : '';		
		$price_range 			=	(Input::json()->get('price_range')) ? strtolower(Input::json()->get('price_range')) : '';			

		//filters 
		$city_filter 			= ($city != '') ? '{"terms" : {  "city": ["'.str_ireplace(',', '","', $city ).'"] }},'  : '';
		$category_filter 		= ($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"] }},'  : '';		
		$categorytags_filter 	= ($category != '') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"] }},'  : '';
		$location_filter 		= ($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('location'))).'"] }},'  : '';	
		$locationtags_filter 	= ($location != '') ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('location'))).'"] }},'  : '';	
		$offerings_filter 		= ($offerings != '') ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('offerings'))).'"] }},'  : '';
		$facilities_filter 		= ($facilities != '') ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('facilities'))).'"] }},'  : '';	
		$price_range_filter 	= ($price_range != '') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('price_range'))).'"] }},'  : '';	

		$shouldfilter = $mustfilter = '';
		
		//used for location , category, 	
		if($location_filter != ''){			
			$should_filtervalue = trim($location_filter.$locationtags_filter,',');	
			$shouldfilter = '"should": ['.$should_filtervalue.'],';	
		}
		

		//used for offering, facilities and price range
		if($city_filter != '' || $offerings_filter != '' || $facilities_filter != '' || $price_range_filter != ''){
			$must_filtervalue = trim($city_filter.$offerings_filter.$facilities_filter.$price_range_filter,',');	
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
			"aggs": '.$aggsval.',
			"query": {
				"function_score": {
					"functions": ['.$basecategory_score.'
					{
						"script_score": { "script": "log(doc[\'popularity\'].value)" }
					},
					{
						"script_score": { "script": "(doc[\'finder_type\'].value > 0 ? 20 : 0)" }
					}
					],
					"query": {
						"filtered": {
							"query": {'
							.$query.
							'}'.$filters.'
						}
					},
					"score_mode": "sum",
					"boost_mode": "replace"
				}
			}
		}';

		//echo $body; exit;
		$serachbody = $body;
		$request = array(
			'url' => $this->elasticsearch_default_url."_search",
			'port' => 9200,
			'method' => 'POST',
			'postfields' => $serachbody
			);
		
		$search_results 	=	es_curl_request($request);

		$finder_leftside = array('categorytag_offerings' => Findercategorytag::active()->whereIn('cities',array($city_id))->with('offerings')->orderBy('ordering')->get(array('_id','name','offering_header','slug','status','offerings')),
			'locations' => Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug')),
			'price_range' => array(
				array("slug" =>"one","name" => "less than 1000"),
				array("slug"=>"two","name" => "1000-2500"),	
				array("slug" =>"three","name" => "2500-5000"),
				array("slug"=>"four","name" => "5000-7500"),
				array("slug"=>"five" ,"name"=> "7500-15000"),
				array("slug"=>"six","name"=> "15000 & above")
				),
			'facilities' => Facility::active()->orderBy('name')->get(array('name','_id','slug'))	
			);

		$meta_title	= $meta_description = $meta_keywords = '';
		if($category != ''){
			$findercategory 	=  	Findercategory::where('slug', '=', url_slug(array($category)))->first(array('meta'));
			$meta_title			= $findercategory['meta']['title'];
			$meta_description	= $findercategory['meta']['description'];
			$meta_keywords		= $findercategory['meta']['keywords'];
		} 

		$resp  = 	array(
			'meta_title' => $meta_title,
			'meta_description' => $meta_description,
			'meta_keywords' => $meta_keywords,
			'finder_leftside' => $finder_leftside,									
			'search_results' => json_decode($search_results,true),
			);

		//return Response::json($search_results); exit;
		return Response::json($resp);
		//echo $body; exit;
	}



}
