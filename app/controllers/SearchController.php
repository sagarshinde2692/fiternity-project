<?php

class SearchController extends \BaseController {
	protected $indice = "fitternity";
	protected $facetssize = 10000;
	protected $limit = 10000;

	public function __construct() {
     	parent::__construct();	
    }


	public function getGlobal() {
		$searchParams = array();
		$facetssize =  $this->facetssize;	
		$type = "blog,finder,product";		 		
		$filters = "";		 		
		$globalkeyword =  Input::json()->get('keyword');
		$from =  (Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size =  (Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		

		//filters 
		$type_filter = ((Input::json()->get('type'))) ? '{"terms" : {  "_type": ["'.Input::json()->get('type').'"] }},'  : '';	
		$category_filter = ((Input::json()->get('category'))) ? '{"terms" : {  "category": ["'.Input::json()->get('category').'"] }},'  : '';
		$category_tags_filter = ((Input::json()->get('category_tags'))) ? '{"terms" : {  "category_tags": ["'.Input::json()->get('category_tags').'"] }},'  : '';

		//can able apply filter having product only
		if(Input::json()->get('type') == 'product'){			
			$filtervalue = trim($type_filter.$category_tags_filter,',');	
		}else{			
			$filtervalue =trim($type_filter.$category_filter.$category_tags_filter,',');	
		}

		if($type_filter != '' || $category_filter != '' || $category_tags_filter != '' ){
			$filters = ',"filter": { 
							"and" : ['.$filtervalue.']
						}';
		}

		//factes
		$type_facets = '"type": {"terms": {"field": "_type","all_terms": true,"size": '.$facetssize.',"order": "term"}},';	
		$category_facets = '"category": {"terms": {"field": "category","all_terms": true,"size": '.$facetssize.',"order": "term"}},';	
		$category_tags_facets = '"category_tags": {"terms": {"field": "category_tags","all_terms": true,"size": '.$facetssize.',"order": "term"}},';	

		//can able apply facets having product only		
		if(Input::json()->get('type') == 'product'){
			$facetsvalue = trim($type_facets.$category_tags_facets,',');				
		}else{
			$facetsvalue = trim($type_facets.$category_facets.$category_tags_facets,',');				
		}

		$body = '
		{
			"from": '.$from.',
			"size": '.$size.',
			"facets": {'.$facetsvalue.'},
			"query": {
				"function_score": {
					"functions": [
					{
						"script_score": {
							"script": "(doc[\'_type\'].value == \'finder\' ? 1500 : 0)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'_type\'].value == \'product\' ? 1000 : 0)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'_type\'].value == \'blog\' ? 500 : 0)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'finder.finder_type\'].value > 0 ? 50 : 0)"
						}
					}
					],
					"query": {
						"filtered": {
							"query": {
								"multi_match": {
									"query": "'.$globalkeyword.'",
									"fields": [
									"finder.title^10",
									"finder.location.region^10",
									"finder.location.region_tags^5",
									"finder.category^20",
									"finder.category_tags^5",
									"finder.info.about^2",
									"product.name^10",
									"product.category_tags^5",
									"product.description^2",
									"blog.title^10",
									"blog.category^10",
									"blog.body^2"
									]
								}
							}
							'.$filters.'
						}
					},
					"score_mode": "sum",
					"boost_mode": "sum"
				}
			}
		}';

		//return $body;exit;
		//$serachbody = Input::json()->all();
		//$searchParams['size'] = $this->limit;
		$serachbody = json_decode($body,true);		
		$searchParams['index'] = $this->indice;
		$searchParams['type']  = $type;		
		$searchParams['body'] = $serachbody;
		//print"<pre>";print_r($searchParams);exit;
		$results =  Es::search($searchParams);
		//printPretty($results);
		return $results;		
	}

	/*
		{
		  "from": 0,
		  "size": 10,
		  "category": "gyms",
		  "regions": "grant road,churchgate",
		  "offerings":"spinning"
	  	}
  	*/



	public function getFinders() {			
		$searchParams = array();
		$facetssize =  $this->facetssize;	
		$type = "finder";		    	
		$filters = "";		
		$from =  (Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size =  (Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;						

		//filters 
		$filters = "";		 
		$category_filter =  Input::json()->get('category');
		$regions_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "region": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$region_tags_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "region_tags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$offerings_filter = ((Input::json()->get('offerings'))) ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
		$facilities_filter = ((Input::json()->get('facilities'))) ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';		
		

		$should_filtervalue = trim($regions_filter.$region_tags_filter,',');	
		$must_filtervalue = trim($offerings_filter.$facilities_filter,',');	
		$shouldfilter = '"should": ['.$should_filtervalue.'],';	//used for location 
		$mustfilter = '"must": ['.$must_filtervalue.']';		//used for offering and facilities
		$filtervalue = trim($shouldfilter.$mustfilter,',');	

		if($shouldfilter != '' || $mustfilter != ''){
			$filters = ',"filter": { 
							"bool" : {'.$filtervalue.'}
						},"_cache" : true';
		}

		//factes
		$category_facets = '"category": {"terms": {"field": "category","all_terms": true,"size": '.$facetssize.',"order": "term"}},';	
		$regions_facets = '"regions": {"terms": {"field": "regions","all_terms": true,"size": '.$facetssize.',"order": "term"}},';	
		$offerings_facets = '"offerings": {"terms": {"field": "offerings","all_terms": true,"size": '.$facetssize.',"order": "term"}},';			
		$facilities_facets = '"facilities": {"terms": {"field": "facilities","all_terms": true,"size": '.$facetssize.',"order": "term"}},';			
		$facetsvalue = trim($category_facets.$regions_facets.$offerings_facets.$facilities_facets,',');		

		$body =	'{				
			"from": '.$from.',
			"size": '.$size.',
			"facets": {'.$facetsvalue.'},
			"query": {
				"function_score": {
					"functions": [
					{
						"script_score": {
							"script": "(doc[\'category\'].value == \''.$category_filter.'\' ? 10 : 0)"
						}
					},
					{
						"script_score": {
							"script": "log(doc[\'popularity\'].value)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'finder_type\'].value > 0 ? 20 : 0)"
						}
					}
					],
					"query": {
						"filtered": {
							"query": {
								"multi_match": {
									"query": "'.$category_filter.'",
									"fields": [
									"category",
									"category_tags"
									]
								}
							}'.$filters.'
						}
					},
					"score_mode": "sum",
					"boost_mode": "replace"
				}
			}
		}';

		//echo $body; exit;
		$serachbody = json_decode($body,true);
		$searchParams['index'] = $this->indice;
		$searchParams['type']  = $type;
		//$searchParams['size'] = $this->limit;
		$searchParams['body'] = $serachbody;
		//printPretty($searchParams);exit;
		$results =  Es::search($searchParams);
		//printPretty($results);
		return $results;
		
	}

	/*
		{
		  "from": 0,
		  "size": 10,
		  "category": "gyms",
		  "regions": "grant road,churchgate",
		  "offerings":"spinning"
	  	}
  	*/


	public function getFindersv2() {			
		//echo "asdfsdf";exit;
		$searchParams 	= 	array();
		$facetssize 	=  	$this->facetssize;	
		$type 			= 	"finder";		    	
		$filters 		= 	"";		
		$from 			=  (Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 			=  (Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;						

		//filters 
		$filters = "";		 
		$category_filter =  Input::json()->get('category');
		$regions_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$region_tags_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$offerings_filter = ((Input::json()->get('offerings'))) ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
		$facilities_filter = ((Input::json()->get('facilities'))) ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';		
		

		$should_filtervalue = trim($regions_filter.$region_tags_filter,',');	
		$must_filtervalue = trim($offerings_filter.$facilities_filter,',');	
		$shouldfilter = '"should": ['.$should_filtervalue.'],';	//used for location 
		$mustfilter = '"must": ['.$must_filtervalue.']';		//used for offering and facilities
		$filtervalue = trim($shouldfilter.$mustfilter,',');	

		if($shouldfilter != '' || $mustfilter != ''){
			$filters = ',"filter": { 
							"bool" : {'.$filtervalue.'}
						},"_cache" : true';
		}

		//factes
		$category_facets = '"category": {"terms": {"field": "category","all_terms": true,"size": '.$facetssize.',"order": "term"}},';	
		$regions_facets = '"regions": {"terms": {"field": "locations","all_terms": true,"size": '.$facetssize.',"order": "term"}},';	
		$offerings_facets = '"offerings": {"terms": {"field": "offerings","all_terms": true,"size": '.$facetssize.',"order": "term"}},';			
		$facilities_facets = '"facilities": {"terms": {"field": "facilities","all_terms": true,"size": '.$facetssize.',"order": "term"}},';			
		$facetsvalue = trim($category_facets.$regions_facets.$offerings_facets.$facilities_facets,',');		

		$body =	'{				
			"from": '.$from.',
			"size": '.$size.',
			"facets": {'.$facetsvalue.'},
			"query": {
				"function_score": {
					"functions": [
					{
						"script_score": {
							"script": "(doc[\'category\'].value == \''.$category_filter.'\' ? 10 : 0)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'popularity\'].value > 0 ? log(doc[\'popularity\'].value) : 0)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'finder_type\'].value > 0 ? 20 : 0)"
						}
					}
					],
					"query": {
						"filtered": {
							"query": {
								"multi_match": {
									"query": "'.$category_filter.'",
									"fields": [
									"category",
									"categorytags"
									]
								}
							}'.$filters.'
						}
					},
					"score_mode": "sum",
					"boost_mode": "replace"
				}
			}
		}';

		//echo $body; exit;
		$serachbody = json_decode($body,true);
		$searchParams['index'] = 'fitadmin';
		$searchParams['type']  = $type;
		//$searchParams['size'] = $this->limit;
		$searchParams['body'] = $serachbody;
		//print_pretty($searchParams);exit;
		$results =  Es::search($searchParams);
		//printPretty($results);
		return $results;
		
	}

	public function getFindersv3(){
		
		//echo "calling getFindersv3";
		$searchParams 		= 	array();
		$type 				= 	"finder";		    	
		$filters 			=	"";	
		$selectedfields 	= 	"";		
		$from 				=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 				=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		

		$category 			=	(Input::json()->get('category')) ? Input::json()->get('category') : '';		
		$location 			=	(Input::json()->get('regions')) ? Input::json()->get('regions') : '';		
		$offerings 			=	(Input::json()->get('offerings')) ? Input::json()->get('offerings') : '';		
		$facilities 		=	(Input::json()->get('facilities')) ? Input::json()->get('facilities') : '';		
		$price_range 		=	(Input::json()->get('price_range')) ? Input::json()->get('price_range') : '';		

		//filters 
		$category_filter 		= ($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';	
		$categorytags_filter 	= ($category != '') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';
		$location_filter 		= ($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$locationtags_filter 	= ($location != '') ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$offerings_filter 		= ($offerings != '') ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
		$facilities_filter 		= ($facilities != '') ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';	
		$price_range_filter 	= ($price_range != '') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","',Input::json()->get('price_range')).'"] }},'  : '';	

		$shouldfilter = $mustfilter = '';
		
		//used for location , category, 	
		if($location_filter != ''){			
			//$should_filtervalue = trim($category_filter.$categorytags_filter.$location_filter.$locationtags_filter,',');	
			$should_filtervalue = trim($location_filter.$locationtags_filter,',');	
			$shouldfilter = '"should": ['.$should_filtervalue.'],';	
		}
		
		//used for offering, facilities and price range
		if($offerings_filter != '' || $facilities_filter != '' || $price_range_filter != ''){
			$must_filtervalue = trim($offerings_filter.$facilities_filter.$price_range_filter,',');	
			$mustfilter = '"must": ['.$must_filtervalue.']';		
		}

		if($shouldfilter != '' || $mustfilter != ''){
			$filtervalue = trim($shouldfilter.$mustfilter,',');	
			$filters = ',"filter": { 
							"bool" : {'.$filtervalue.'}
						},"_cache" : true';
		}

		$selectedfields = '"fields": ["title","average_rating","category","categorytags","location","locationtags","finder_type","popularity"],';

		if($category == ''){
			$query = '"match_all": {}';
			$basecategory_score = '';		
		}else{
			$query = '"multi_match": {
						"query": "'.$category.'",
						"fields": [
						"category",
						"categorytags"
						]
					}';	
			$basecategory_score	= '{
										"script_score": {
											"script": "(doc[\'category\'].value == \''.$category.'\' ? 10 : 0)"
										}
									},';
		}



		$body =	'{				
			"from": '.$from.',
			"size": '.$size.',
			"query": {
				"function_score": {
					"functions": ['.$basecategory_score.'
					{
						"script_score": {
							"script": "log(doc[\'popularity\'].value)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'finder_type\'].value > 0 ? 20 : 0)"
						}
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
		$serachbody = json_decode($body,true);
		$searchParams['index'] = 'fitadmin';
		$searchParams['type']  = $type;
		//$searchParams['size'] = $this->limit;
		$searchParams['body'] = $serachbody;
		//print_pretty($searchParams);exit;
		$results =  Es::search($searchParams);
		//printPretty($results);
		return $results;


		//echo $body; exit;
	}


	public function getFitmaniaFinders(){
		
		$searchParams 		= 	array();
		$type 				= 	"findermembership";		    	
		$filters 			=	"";	
		$selectedfields 	= 	"";		
		$from 				=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 				=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		

		$category 			=	(Input::json()->get('category')) ? Input::json()->get('category') : '';		
		$location 			=	(Input::json()->get('regions')) ? Input::json()->get('regions') : '';		
		$offerings 			=	(Input::json()->get('offerings')) ? Input::json()->get('offerings') : '';		
		$facilities 		=	(Input::json()->get('facilities')) ? Input::json()->get('facilities') : '';		
		$price_range 		=	(Input::json()->get('price_range')) ? Input::json()->get('price_range') : '';		

		//filters 
		$category_filter 		= ($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';	
		$categorytags_filter 	= ($category != '') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';
		$location_filter 		= ($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$locationtags_filter 	= ($location != '') ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$offerings_filter 		= ($offerings != '') ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
		$facilities_filter 		= ($facilities != '') ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';	
		$price_range_filter 	= ($price_range != '') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","',Input::json()->get('price_range')).'"] }},'  : '';	

		$shouldfilter = $mustfilter = '';
		
		//used for location , category, 	
		if($location_filter != ''){			
			//$should_filtervalue = trim($category_filter.$categorytags_filter.$location_filter.$locationtags_filter,',');	
			$should_filtervalue = trim($location_filter.$locationtags_filter,',');	
			$shouldfilter = '"should": ['.$should_filtervalue.'],';	
		}
		
		//used for offering, facilities and price range
		if($offerings_filter != '' || $facilities_filter != '' || $price_range_filter != ''){
			$must_filtervalue = trim($offerings_filter.$facilities_filter.$price_range_filter,',');	
			$mustfilter = '"must": ['.$must_filtervalue.']';		
		}

		if($shouldfilter != '' || $mustfilter != ''){
			$filtervalue = trim($shouldfilter.$mustfilter,',');	
			$filters = ',"filter": { 
							"bool" : {'.$filtervalue.'}
						},"_cache" : true';
		}

		$selectedfields = '"fields": ["title","average_rating","category","categorytags","location","locationtags","finder_type","popularity"],';

		if($category == ''){
			$query = '"match_all": {}';
			$basecategory_score = '';		
		}else{
			$query = '"multi_match": {
						"query": "'.$category.'",
						"fields": [
						"category",
						"categorytags"
						]
					}';	
			$basecategory_score	= '{
										"script_score": {
											"script": "(doc[\'category\'].value == \''.$category.'\' ? 10 : 0)"
										}
									},';
		}



		$body =	'{				
			"from": '.$from.',
			"size": '.$size.',
			"aggs" : {
				"all_categorys" : {
		            "global" : {}, 
		            "aggs" : { 
		                "category" : { "terms" : { "field" : "category", "size": 10000} }
		            }
		        },
		        "all_locations" : {
		            "global" : {}, 
		            "aggs" : { 
		                "location" : { "terms" : { "field" : "location", "size": 10000} }
		            }
		        }
		    },
			"query": {
				"function_score": {
					"functions": ['.$basecategory_score.'
					{
						"script_score": {
							"script": "log(doc[\'popularity\'].value)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'finder_type\'].value > 0 ? 20 : 0)"
						}
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


		$serachbody = $body;
		$request = array(
			'url' => "http://54.179.134.14:9200/fitadmin/findermembership/_search",
			'port' => 9200,
			'method' => 'POST',
			'postfields' => $serachbody
			);
		
		$search_results 	=	es_curl_request($request);
		$deals_of_day   	=	$this->get_deals_of_day();
		$resp 				= 	array("deals_of_day" => $deals_of_day, 
										"deals_today_communication" => "Power House Gyms", 
										"deals_tomorrow_communication" => "Fitness in bandra", 
										"deals_coming_communication" => "Martial Arts, Yoga & More....", 
										"search_results" => json_decode($search_results,true)
									);
		return Response::json($resp);
		//echo $body; exit;
	}

	public function get_deals_of_day(){

		$deals_of_day   = array(
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_1.jpg',
							  		'location'=>'Versova',
							  		'discount'=>'50%','price'=>6000,'special_price'=>3000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_2.jpg',
							  		'location'=>'Ghatkopar East',
							  		'discount'=>'50%','price'=>6000,'special_price'=>3000,'type'=>"service",'sold_out'=>0
							  	),								  								  								  	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_3.jpg',
							  		'location'=>'Juhu',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_4.jpg',
							  		'location'=>'Mumbai Central',
							  		'discount'=>'50%','price'=>6000,'special_price'=>3000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_5.jpg',
							  		'location'=>'Santacruz West',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_6s.jpg',
							  		'location'=>'Chowpatty',
							  		'discount'=>'98%','price'=>8000,'special_price'=>99,'type'=>"service",'sold_out'=>1
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_7.jpg',
							  		'location'=>'Bandra',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_8.jpg',
							  		'location'=>'Malad East',
							  		'discount'=>'50%','price'=>6000,'special_price'=>3000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_9.jpg',
							  		'location'=>'Prabhadevi',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_10.jpg',
							  		'location'=>'Andheri East',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_11.jpg',
							  		'location'=>'Chembur East',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),	

							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_12.jpg',
							  		'location'=>'Vile Parle East',
							  		'discount'=>'50%','price'=>6000,'special_price'=>3000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_13.jpg',
							  		'location'=>'Kandivali East',
							  		'discount'=>'50%','price'=>6000,'special_price'=>3000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_14.jpg',
							  		'location'=>'Mulund',
							  		'discount'=>'50%','price'=>6000,'special_price'=>3000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_6.jpg',
							  		'location'=>'Chowpatty',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_15.jpg',
							  		'location'=>'Versova',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),	
							  	array(
							  		'name'=>"Powerhouse - 3 Months Gym Membership",
							  		'image'=>'http://b.fitn.in/global/fitmania/18_16.jpg',
							  		'location'=>'Versova',
							  		'discount'=>'50%','price'=>8000,'special_price'=>4000,'type'=>"service",'sold_out'=>0
							  	),							  								  								  								  								  								  								  								  								  								  								  								  								  	
							);

        return $deals_of_day;

	}


	public function getFitcardFinders(){


		$searchParams 		= 	array();
		$type 				= 	"fitcardfinder";		    	
		$filters 			=	"";	
		$selectedfields 	= 	"";		
		$from 				=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 				=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		

		$category 			=	(Input::json()->get('category')) ? Input::json()->get('category') : '';		
		$location 			=	(Input::json()->get('regions')) ? Input::json()->get('regions') : '';		
		$offerings 			=	(Input::json()->get('offerings')) ? Input::json()->get('offerings') : '';		
		$facilities 		=	(Input::json()->get('facilities')) ? Input::json()->get('facilities') : '';		
		$price_range 		=	(Input::json()->get('price_range')) ? Input::json()->get('price_range') : '';		

		//filters 
		$category_filter 		= ($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';	
		$categorytags_filter 	= ($category != '') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';
		$location_filter 		= ($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$locationtags_filter 	= ($location != '') ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
		$offerings_filter 		= ($offerings != '') ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
		$facilities_filter 		= ($facilities != '') ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';	
		$price_range_filter 	= ($price_range != '') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","',Input::json()->get('price_range')).'"] }},'  : '';	

		$shouldfilter = $mustfilter = '';
		
		$category_filter;


		//used for location , category, 	
		if($location_filter != '' || $category_filter != ''){			
			//$should_filtervalue = trim($category_filter.$categorytags_filter.$location_filter.$locationtags_filter,',');	
			$should_filtervalue = trim($category_filter.$categorytags_filter.$location_filter.$locationtags_filter,',');	
			$shouldfilter = '"should": ['.$should_filtervalue.'],';	
		}
		
		//used for offering, facilities and price range
		if($offerings_filter != '' || $facilities_filter != '' || $price_range_filter != ''){
			$must_filtervalue = trim($offerings_filter.$facilities_filter.$price_range_filter,',');	
			$mustfilter = '"must": ['.$must_filtervalue.']';		
		}

		if($shouldfilter != '' || $mustfilter != ''){
			$filtervalue = trim($shouldfilter.$mustfilter,',');	
			$filters = ',"filter": { 
							"bool" : {'.$filtervalue.'}
						},"_cache" : true';
		}

		$selectedfields = '"fields": ["title","average_rating","category","categorytags","location","locationtags","finder_type","popularity"],';

		if($category == ''){
			$query = '"match_all": {}';
			$basecategory_score = '';		
		}else{
			$query = '"multi_match": {
						"query": "'.$category.'",
						"fields": [
						"category",
						"categorytags"
						]
					}';	
			$basecategory_score	= '{
										"script_score": {
											"script": "(doc[\'category\'].value == \''.$category.'\' ? 10 : 0)"
										}
									},';
		}



		$body =	'{				
			"from": '.$from.',
			"size": '.$size.',
			"aggs" : {
				"all_categorys" : {
		            "global" : {}, 
		            "aggs" : { 
		                "category" : { "terms" : { "field" : "category", "size": 10000} }
		            }
		        },
		        "all_locations" : {
		            "global" : {}, 
		            "aggs" : { 
		                "location" : { "terms" : { "field" : "location", "size": 10000} }
		            }
		        }
		    },
			"query": {
				"function_score": {
					"functions": ['.$basecategory_score.'
					{
						"script_score": {
							"script": "log(doc[\'popularity\'].value)"
						}
					},
					{
						"script_score": {
							"script": "(doc[\'finder_type\'].value > 0 ? 20 : 0)"
						}
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


		$serachbody = $body;
		$request = array(
			'url' => "http://54.179.134.14:9200/fitadmin/fitcardfinder/_search",
			'port' => 9200,
			'method' => 'POST',
			'postfields' => $serachbody
			);
		
		$search_results 	=	es_curl_request($request);
		$resp 				= 	array('search_results' => json_decode($search_results,true));
		return Response::json($resp);
		//echo $body; exit;
	}


	public function getGlobalv2() {
		//var_dump(Input::json()->all());
		//var_dump(Input::json()->all());
		//var_dump(Input::json()->get('category'));

		$searchParams 			=	array();
		$type 					= 	"finder";		 		
		$filters 				= 	"";		 		
		$globalkeyword 			=  	(Input::json()->get('keyword')) ? refine_keyword(Input::json()->get('keyword')) : "";
		$from 					=  	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 					=  	(Input::json()->get('size')) ? Input::json()->get('size') : 5;		

		$category 				=	(Input::json()->get('category')) ? Input::json()->get('category') : '';		
		$location 				=	(Input::json()->get('location')) ? Input::json()->get('location') : '';		
		$offerings 				=	(Input::json()->get('offerings')) ? Input::json()->get('offerings') : '';		
		$facilities 			=	(Input::json()->get('facilities')) ? Input::json()->get('facilities') : '';		
		$price_range 			=	(Input::json()->get('price_range')) ? Input::json()->get('price_range') : '';		


		//return $globalkeyword;exit;

		//filters 
		$category_filter 		=	($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';	
		$categorytags_filter 	=	($category != '') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';
		$location_filter 		=	($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('location')).'"] }},'  : '';	
		$locationtags_filter 	=	($location != '') ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('location')).'"] }},'  : '';	
		$offerings_filter 		=	($offerings != '') ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
		$facilities_filter 		=	($facilities != '') ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';	
		$price_range_filter 	=	($price_range != '') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","',Input::json()->get('price_range')).'"] }},'  : '';	

		$shouldfilter = $mustfilter = '';
		
		//return $category_filter;exit;

		// //used for location , category, 	
		// if($category_filter != '' || $location_filter != ''){			
		// 	$should_filtervalue = trim($category_filter.$location_filter,',');	
		// 	//$should_filtervalue = trim($category_filter.$categorytags_filter.$location_filter.$locationtags_filter,',');	
		// 	$shouldfilter = '"should": ['.$should_filtervalue.'],';	
		// }
		
		// //used for offering, facilities and price range
		// if($offerings_filter != '' || $facilities_filter != '' || $price_range_filter != ''){
		// 	$must_filtervalue = trim($offerings_filter.$facilities_filter.$price_range_filter,',');	
		// 	$mustfilter = '"must": ['.$must_filtervalue.']';		
		// }


		if($category_filter != '' || $location_filter != '' || $offerings_filter != '' || $facilities_filter != '' || $price_range_filter != ''){
			$must_filtervalue = trim($category_filter.$location_filter.$offerings_filter.$facilities_filter.$price_range_filter,',');	
			$mustfilter = '"must": ['.$must_filtervalue.']';		
		}


		if($shouldfilter != '' || $mustfilter != ''){
			$filtervalue = trim($shouldfilter.$mustfilter,',');	
			$filters = ',"filter": { 
							"bool" : {'.$filtervalue.'}
						},"_cache" : true';
		}

		//return $filters;exit;

		/*
		"all_categorys" : {
		            "global" : {}, 
		            "aggs" : { 
		                "category" : { "terms" : { "field" : "category", "size": 10000} }
		            }
		        },
		*/		        
		$body = '
		{
			"from": '.$from.',
			"size": '.$size.',
			"aggs" : {						        		        
	            "resultset_categories": { "terms": {"field": "category","size": 10000 } },
	            "resultset_locations": { "terms": {"field": "location","size": 10000 } },
	            "resultset_offerings": { "terms": {"field": "offerings","size": 10000 } },
	            "resultset_facilities": { "terms": {"field": "facilities","size": 10000 } }
		    },
			"query": {
				"function_score": {
					"functions": [
						{
							"script_score": {
								"script": "(doc[\'finder.finder_type\'].value > 0 ? 100 : 20)"
							}
						},
						{
							"script_score": {
								"script": "(doc[\'finder.category\'].value == \'swimming\' ? -10 : 0)"
							}
						},
						{
							"script_score": {
								"script": "(doc[\'finder.category\'].value == \'sports\' ? -10 : 0)"
							}
						}
					],
					"query": {
						"filtered": {
							"query": {
								"multi_match": {
									"query": "'.$globalkeyword.'",
									"fields": [
									"finder.title^50",
									"finder.category^20",
									"finder.categorytags^5",
									"finder.location^20",
									"finder.locationtags^5"
									]
								}
							}
							'.$filters.'
						}
					},
					"score_mode": "sum",
					"boost_mode": "sum"
				}
			}
		}';


		return $body;exit;

		$serachbody = $body;
		$request = array(
			'url' => "http://54.179.134.14:9200/fitadmin/finder/_search",
			'port' => 9200,
			'method' => 'POST',
			'postfields' => $serachbody
			);
		
		$search_results 	=	es_curl_request($request);
		
		if($category_filter != '' || $location_filter != '' || $offerings_filter != '' || $facilities_filter != '' || $price_range_filter != ''){
			$firsttime_query_execution = 0;
		}else{
			$firsttime_query_execution = 1;
		}
		
		$resp 				= 	array('search_results' => json_decode($search_results,true),'firsttime_query_execution' =>$firsttime_query_execution);

		return Response::json($resp);
				
	}


	public function categoryfinders(){
		//echo "calling categoryfinders";exit;
		
		$finders = array();	
		$categoryarr = array('gyms','yoga','pilates','dance','zumba','martial arts','kick boxing','cross functional training');
		foreach ($categoryarr as $catitem) {  		
			$searchParams 		= 	array();
			$type 				= 	"finder";		    	
			$filters 			=	"";	
			$selectedfields 	= 	"";		
			$from 				=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
			$size 				=	(Input::json()->get('size')) ? Input::json()->get('size') : 20;		

			$category 			=	(Input::json()->get('category')) ? Input::json()->get('category') : $catitem;		
			$location 			=	(Input::json()->get('regions')) ? Input::json()->get('regions') : '';		
			$offerings 			=	(Input::json()->get('offerings')) ? Input::json()->get('offerings') : '';		
			$facilities 		=	(Input::json()->get('facilities')) ? Input::json()->get('facilities') : '';		
			$price_range 		=	(Input::json()->get('price_range')) ? Input::json()->get('price_range') : '';		

			//filters 
			$category_filter 		= ($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';	
			$categorytags_filter 	= ($category != '') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';
			$location_filter 		= ($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
			$locationtags_filter 	= ($location != '') ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';	
			$offerings_filter 		= ($offerings != '') ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
			$facilities_filter 		= ($facilities != '') ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';	
			$price_range_filter 	= ($price_range != '') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","',Input::json()->get('price_range')).'"] }},'  : '';	

			$shouldfilter = $mustfilter = '';
			
			//used for location , category, 	
			if($location_filter != ''){			
				//$should_filtervalue = trim($category_filter.$categorytags_filter.$location_filter.$locationtags_filter,',');	
				$should_filtervalue = trim($location_filter.$locationtags_filter,',');	
				$shouldfilter = '"should": ['.$should_filtervalue.'],';	
			}
			
			//used for offering, facilities and price range
			if($offerings_filter != '' || $facilities_filter != '' || $price_range_filter != ''){
				$must_filtervalue = trim($offerings_filter.$facilities_filter.$price_range_filter,',');	
				$mustfilter = '"must": ['.$must_filtervalue.']';		
			}

			if($shouldfilter != '' || $mustfilter != ''){
				$filtervalue = trim($shouldfilter.$mustfilter,',');	
				$filters = ',"filter": { 
								"bool" : {'.$filtervalue.'}
							},"_cache" : true';
			}

			$selectedfields = '"fields": ["title","average_rating","category","categorytags","location","locationtags","finder_type","popularity"],';

			if($category == ''){
				$query = '"match_all": {}';
				$basecategory_score = '';		
			}else{
				$query = '"multi_match": {
							"query": "'.$category.'",
							"fields": [
							"category",
							"categorytags"
							]
						}';	
				$basecategory_score	= '{
											"script_score": {
												"script": "(doc[\'category\'].value == \''.$category.'\' ? 10 : 0)"
											}
										},';
			}

			$body =	'{				
				"from": '.$from.',
				"size": '.$size.',
				"query": {
					"function_score": {
						"functions": ['.$basecategory_score.'
						{
							"script_score": {
								"script": "log(doc[\'popularity\'].value)"
							}
						},
						{
							"script_score": {
								"script": "(doc[\'finder_type\'].value > 0 ? 20 : 0)"
							}
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

			echo $body; exit;
			$serachbody = json_decode($body,true);
			$searchParams['index'] = 'fitadmin';
			$searchParams['type']  = $type;
			$searchParams['body'] = $serachbody;
			$rs = Es::search($searchParams);
			$category_finder = array();
			foreach ($rs['hits']['hits'] as $item) {  
				array_push($category_finder,array_only($item['_source'], array('title', 'slug','coverimage')));
			}
			$finders[$catitem] = $category_finder;
		}	

		return $finders;

	}

	public function getFindersJsonData() {				
		$searchParams = array();
		$type = "finder";		       
		$body = Input::json()->all();		
		$searchParams['index'] = $this->indice;
		$searchParams['type']  = $type;
		$searchParams['size'] = $this->limit;
		$searchParams['body'] = $body;
		//printPretty($searchParams);exit;
		$results =  Es::search($searchParams);
		//printPretty($results);
		return $results;
		
	}



}