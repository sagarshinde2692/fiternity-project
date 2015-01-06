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
		$resp 				= 	array('deals_of_day' => $deals_of_day, 'search_results' => json_decode($search_results,true));
		return Response::json($resp);
		//echo $body; exit;
	}


	public function get_deals_of_day(){
		date_default_timezone_set("Asia/Kolkata");
		$deals_of_day 	= 	array();
		$weekday 		= 	strtolower(date('l'));
		if($weekday == ""){ $weekday = "sunday";}
		
		//echo $weekday;
		switch ($weekday) {
			case "sunday":
			$deals_of_day   = array(
								  array(
								  		'name'=>'Jiwa Diabetic care Atta - 1 KG',
								  		'image'=>'http://b.fitn.in/featured/10/p/1.jpg',					  		
								  		'discount'=>50,'price'=>99,'special_price'=>99,'type'=>"product"
								  	),
									array(
								  		'name'=>'High Voltage Whey 6000 1 KG - Chocolate',
								  		'image'=>'http://a.fitn.in/featured/10/p/2.jpg',					  		
								  		'discount'=>50,'price'=>1850,'special_price'=>1850,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'Harbinger Classic WristWrap Gloves',
								  		'image'=>'http_date()://a.fitn.in/featured/10/p/3.jpg',					  		
								  		'discount'=>50,'price'=>1949,'special_price'=>1949,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'TEGO Antimicrobial Towel',
								  		'image'=>'http://a.fitn.in/featured/10/p/4.jpg',
								  		'discount'=>50,'price'=>499,'special_price'=>499,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'Accu-Check Active Meter Only Kit',
								  		'image'=>'http://a.fitn.in/featured/10/p/5.jpg',					  		
								  		'discount'=>50,'price'=>1480,'special_price'=>1480,'type'=>"product"									  		
								  	),
								  	array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_1.jpg',					  		
								  		'discount'=>97,'price'=>3500,'special_price'=>99,'type'=>"service"
								  	),
									array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_2.jpg',					  		
								  		'discount'=>72,'price'=>3500,'special_price'=>999,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'Harbinger Classic WristWrap Gloves',
								  		'image'=>'http://a.fitn.in/featured/10/p/3.jpg',					  		
								  		'discount'=>50,'price'=>1949,'special_price'=>1949,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'TEGO Antimicrobial Towel',
								  		'image'=>'http://a.fitn.in/featured/10/p/4.jpg',
								  		'discount'=>50,'price'=>499,'special_price'=>499,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'Accu-Check Active Meter Only Kit',
								  		'image'=>'http://a.fitn.in/featured/10/p/5.jpg',					  		
								  		'discount'=>50,'price'=>1480,'special_price'=>1480,'type'=>"service"									  		
								  	)

						);
			break;

			case "monday":
			$deals_of_day   = array(								  
								  	array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_1.jpg',	
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-sucheta-pal',
										'location'=>'Bandra',
								  		'discount'=>'97%','price'=>3500,'special_price'=>99,'type'=>"service",'sold_out'=>0
								  	),
									array(
								  		'name'=>'Zumba with Sukoon Rajani - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_2.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-sukoon-rajani',
								  		'location'=>'Bandra , Marine Drive',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	),	
								  	array(
								  		'name'=>'Zumba with Yogesh Kushalkar - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_3.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-yogesh-kushalkar',
								  		'location'=>'Lokhandwala,Khar,Ghatkopar',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	),	
								  	array(
								  		'name'=>'Zumba with illumination - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_4.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-illumination',
								  		'location'=>'Andheri to Dahisar',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	)
						);
			break;

			case "tuesday":
			$deals_of_day   = array(
								  	array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_1.jpg',	
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-sucheta-pal',
										'location'=>'Bandra',
								  		'discount'=>'97%','price'=>3500,'special_price'=>99,'type'=>"service",'sold_out'=>0
								  	),
									array(
								  		'name'=>'Zumba with Sukoon Rajani - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_2.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-sukoon-rajani',
								  		'location'=>'Bandra , Marine Drive',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	),	
								  	array(
								  		'name'=>'Zumba with Yogesh Kushalkar - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_3.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-yogesh-kushalkar',
								  		'location'=>'Lokhandwala,Khar,Ghatkopar',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	),	
								  	array(
								  		'name'=>'Zumba with illumination - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_4.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-illumination',
								  		'location'=>'Andheri to Dahisar',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	),
								  	array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_1.jpg',	
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-sucheta-pal',
										'location'=>'Bandra',
								  		'discount'=>'42%','price'=>3500,'special_price'=>1999,'type'=>"service",'sold_out'=>0
								  	),								  	
						);
			break;

			case "wednesday":
			$deals_of_day   = array(
								  	array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_1.jpg',	
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-sucheta-pal',
										'location'=>'Bandra , Chowpatty',
								  		'discount'=>'97%','price'=>3500,'special_price'=>99,'type'=>"service",'sold_out'=>0
								  	),
									array(
								  		'name'=>'Zumba with Sukoon Rajani - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_2.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-sukoon-rajani',
								  		'location'=>'Bandra , Marine Drive',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	),	
								  	array(
								  		'name'=>'Zumba with Yogesh Kushalkar - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_3.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-yogesh-kushalkar',
								  		'location'=>'Lokhandwala,Khar,Ghatkopar',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	),	
								  	array(
								  		'name'=>'Zumba with illumination - 12 Sessions',
								  		'image'=>'http://b.fitn.in/global/fitmania/5_4.jpg',
								  		'finder_url' => 'http://www.fitternity.com/zumba-with-illumination',
								  		'location'=>'Andheri to Dahisar',
								  		'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
								  	)
						);
			break;

			case "thursday":
			$deals_of_day   = array(
								  array(
								  		'name'=>'Jiwa Diabetic care Atta - 1 KG',
								  		'image'=>'http://a.fitn.in/featured/10/p/1.jpg',					  		
								  		'discount'=>50,'price'=>99,'special_price'=>99,'type'=>"product"
								  	),
									array(
								  		'name'=>'High Voltage Whey 6000 1 KG - Chocolate',
								  		'image'=>'http://a.fitn.in/featured/10/p/2.jpg',					  		
								  		'discount'=>50,'price'=>1850,'special_price'=>1850,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'Harbinger Classic WristWrap Gloves',
								  		'image'=>'http://a.fitn.in/featured/10/p/3.jpg',					  		
								  		'discount'=>50,'price'=>1949,'special_price'=>1949,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'TEGO Antimicrobial Towel',
								  		'image'=>'http://a.fitn.in/featured/10/p/4.jpg',
								  		'discount'=>50,'price'=>499,'special_price'=>499,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'Accu-Check Active Meter Only Kit',
								  		'image'=>'http://a.fitn.in/featured/10/p/5.jpg',					  		
								  		'discount'=>50,'price'=>1480,'special_price'=>1480,'type'=>"product"									  		
								  	),
								  	array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://a.fitn.in/featured/10/p/1.jpg',					  		
								  		'discount'=>97,'price'=>3500,'special_price'=>99,'type'=>"service"
								  	),
									array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://a.fitn.in/featured/10/p/2.jpg',					  		
								  		'discount'=>72,'price'=>3500,'special_price'=>999,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'Harbinger Classic WristWrap Gloves',
								  		'image'=>'http://a.fitn.in/featured/10/p/3.jpg',					  		
								  		'discount'=>50,'price'=>1949,'special_price'=>1949,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'TEGO Antimicrobial Towel',
								  		'image'=>'http://a.fitn.in/featured/10/p/4.jpg',
								  		'discount'=>50,'price'=>499,'special_price'=>499,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'Accu-Check Active Meter Only Kit',
								  		'image'=>'http://a.fitn.in/featured/10/p/5.jpg',					  		
								  		'discount'=>50,'price'=>1480,'special_price'=>1480,'type'=>"service"									  		
								  	)

						);
			break;

			case "friday":
			$deals_of_day   = array(
								  array(
								  		'name'=>'Jiwa Diabetic care Atta - 1 KG',
								  		'image'=>'http://a.fitn.in/featured/10/p/1.jpg',					  		
								  		'discount'=>50,'price'=>99,'special_price'=>99,'type'=>"product"
								  	),
									array(
								  		'name'=>'High Voltage Whey 6000 1 KG - Chocolate',
								  		'image'=>'http://a.fitn.in/featured/10/p/2.jpg',					  		
								  		'discount'=>50,'price'=>1850,'special_price'=>1850,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'Harbinger Classic WristWrap Gloves',
								  		'image'=>'http://a.fitn.in/featured/10/p/3.jpg',					  		
								  		'discount'=>50,'price'=>1949,'special_price'=>1949,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'TEGO Antimicrobial Towel',
								  		'image'=>'http://a.fitn.in/featured/10/p/4.jpg',
								  		'discount'=>50,'price'=>499,'special_price'=>499,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'Accu-Check Active Meter Only Kit',
								  		'image'=>'http://a.fitn.in/featured/10/p/5.jpg',					  		
								  		'discount'=>50,'price'=>1480,'special_price'=>1480,'type'=>"product"									  		
								  	),
								  	array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://a.fitn.in/featured/10/p/1.jpg',					  		
								  		'discount'=>97,'price'=>3500,'special_price'=>99,'type'=>"service"
								  	),
									array(
								  		'name'=>'Zumba with Sucheta Pal - 12 Sessions',
								  		'image'=>'http://a.fitn.in/featured/10/p/2.jpg',					  		
								  		'discount'=>72,'price'=>3500,'special_price'=>999,'type'=>"service"
								  	),		
								  	array(
								  		'name'=>'Harbinger Classic WristWrap Gloves',
								  		'image'=>'http://a.fitn.in/featured/10/p/3.jpg',					  		
								  		'discount'=>50,'price'=>1949,'special_price'=>1949,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'TEGO Antimicrobial Towel',
								  		'image'=>'http://a.fitn.in/featured/10/p/4.jpg',
								  		'discount'=>50,'price'=>499,'special_price'=>499,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'Accu-Check Active Meter Only Kit',
								  		'image'=>'http://a.fitn.in/featured/10/p/5.jpg',					  		
								  		'discount'=>50,'price'=>1480,'special_price'=>1480,'type'=>"service"									  		
								  	)
						);
			break;

			case "saturday":
			$deals_of_day   = array(
								  array(
								  		'name'=>'Jiwa Diabetic care Atta - 1 KG',
								  		'image'=>'http://a.fitn.in/featured/10/p/1.jpg',					  		
								  		'discount'=>50,'price'=>99,'special_price'=>99,'type'=>"product"
								  	),
									array(
								  		'name'=>'High Voltage Whey 6000 1 KG - Chocolate',
								  		'image'=>'http://a.fitn.in/featured/10/p/2.jpg',					  		
								  		'discount'=>50,'price'=>1850,'special_price'=>1850,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'Harbinger Classic WristWrap Gloves',
								  		'image'=>'http://a.fitn.in/featured/10/p/3.jpg',					  		
								  		'discount'=>50,'price'=>1949,'special_price'=>1949,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'TEGO Antimicrobial Towel',
								  		'image'=>'http://a.fitn.in/featured/10/p/4.jpg',
								  		'discount'=>50,'price'=>499,'special_price'=>499,'type'=>"product"
								  	),	
								  	array(
								  		'name'=>'Accu-Check Active Meter Only Kit',
								  		'image'=>'http://a.fitn.in/featured/10/p/5.jpg',					  		
								  		'discount'=>50,'price'=>1480,'special_price'=>1480,'type'=>"product"									  		
								  	),array(
								  		'name'=>'Jiwa Diabetic care Atta - 1 KG',
								  		'image'=>'http://a.fitn.in/featured/10/p/1.jpg',					  		
								  		'discount'=>50,'price'=>99,'special_price'=>99,'type'=>"service"
								  	),
									array(
								  		'name'=>'High Voltage Whey 6000 1 KG - Chocolate',
								  		'image'=>'http://a.fitn.in/featured/10/p/2.jpg',					  		
								  		'discount'=>50,'price'=>1850,'special_price'=>1850,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'Harbinger Classic WristWrap Gloves',
								  		'image'=>'http://a.fitn.in/featured/10/p/3.jpg',					  		
								  		'discount'=>50,'price'=>1949,'special_price'=>1949,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'TEGO Antimicrobial Towel',
								  		'image'=>'http://a.fitn.in/featured/10/p/4.jpg',
								  		'discount'=>50,'price'=>499,'special_price'=>499,'type'=>"service"
								  	),	
								  	array(
								  		'name'=>'Accu-Check Active Meter Only Kit',
								  		'image'=>'http://a.fitn.in/featured/10/p/5.jpg',					  		
								  		'discount'=>50,'price'=>1480,'special_price'=>1480,'type'=>"service"									  		
								  	)
						);
			break;
	    }//switch

        return $deals_of_day;

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

			//echo $body; exit;
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


}