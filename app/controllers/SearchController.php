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
		
		echo "calling getFindersv3";
		$searchParams 		= 	array();
		$type 				= 	"finder";		    	
		$filters 			= 	"";		
		$from 				=	(Input::json()->get('from')) ? Input::json()->get('from') : 0;
		$size 				=	(Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;		

		$category 			=	(Input::json()->get('category')) ? str_ireplace(',', '","',Input::json()->get('category')) : '';		
		$location 			=	(Input::json()->get('location')) ? str_ireplace(',', '","',Input::json()->get('location')) : '';		
		$offerings 			=	(Input::json()->get('offerings')) ? str_ireplace(',', '","',Input::json()->get('offerings')) : '';		
		$facilities 		=	(Input::json()->get('facilities')) ? str_ireplace(',', '","',Input::json()->get('facilities')) : '';		
		$price_range 		=	(Input::json()->get('price_range')) ? str_ireplace(',', '","',Input::json()->get('price_range')) : '';		


		//filters 
		$category_filter 		= ($category != '') ? '{"terms" : {  "category": ["'.$category.'"] }},'  : '';	
		$categorytags_filter 	= ($category != '') ? '{"terms" : {  "categorytags": ["'.$category.'"] }},'  : '';
		$location_filter 		= ($location != '') ? '{"terms" : {  "location": ["'.$location.'"] }},'  : '';	
		$locationtags_filter 	= ($location != '') ? '{"terms" : {  "locationtags": ["'.$location.'"] }},'  : '';	
		$offerings_filter 		= ($offerings != '') ? '{"terms" : {  "offerings": ["'.$offerings.'"] }},'  : '';
		$facilities_filter 		= ($facilities != '') ? '{"terms" : {  "facilities": ["'.$facilities.'"] }},'  : '';	
		$price_range_filter 	= ($price_range != '') ? '{"terms" : {  "price_range": ["'.$price_range.'"] }},'  : '';	
		

		$body =	'{				
			"from": '.$from.',
			"size": '.$size.',
			"query": {
				"filtered": {
					"query": {
						"match_all": {}
					}
				}
			}
		}';

		//echo $body; exit;



		//echo $body; exit;
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