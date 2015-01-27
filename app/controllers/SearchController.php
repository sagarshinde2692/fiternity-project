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

		/*
			today - fitness in bandra - khar
			yoga in mumbai
			dance , Martials Arts and more
		*/
			
			$resp 				= 	array("deals_of_day" => $deals_of_day, 
				"deals_today_communication" => "Fitness In Mumbai", 
				"deals_tomorrow_communication" => "Fitness In Mumbai", 
				"deals_coming_communication" => "Fitness Apparel, Health Drinks & Food & More....", 
				"search_results" => json_decode($search_results,true)
				);
			return Response::json($resp);
		//echo $body; exit;
		}

		public function get_deals_of_day(){
			
			$deals_of_day   = array(
				array(
					'name'=>"Mint - Begineers X Training - 3 Months + 3 Months FREE",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_1.jpg',
					'location'=>'Khar',
					'discount'=>'45%','price'=>15500,'special_price'=>8900,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"Bodyholics & Reebok - Cardio Boxing - 1 Session",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_19.jpg',
					'location'=>'Khar',
					'discount'=>'100%','price'=>300,'special_price'=>0,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"Your Fitness Club - 1 Year Gym Membership",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_4.jpg',
					'location'=>'Opera House',
					'discount'=>'30%','price'=>23000,'special_price'=>16100,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"The Pilates Studio - 1 Session Reformer Pilates",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_13.jpg',
					'location'=>'Santacruz , Hughes Road',
					'discount'=>'97%','price'=>1500,'special_price'=>49,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"Gold's Gym - Buy 15 days & Get 15 days FREE(Gym + Unlimited Group X Classes)",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_8.jpg',
					'location'=>'Bandra',
					'discount'=>'75%','price'=>7500,'special_price'=>3499,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"Fitness First - 1 Week Gym Membership",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_16.jpg',
					'location'=>'Oshiwara',
					'discount'=>'99%','price'=>3500,'special_price'=>49,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"Anytime Fitness - 1 Month Gym Membership",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_25.jpg',
					'location'=>'Lokhandwala',
					'discount'=>'63%','price'=>4000,'special_price'=>1499,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"Beyond Fitness - 1 Year Gym Membership",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_21.png',
					'location'=>'Malabar hill',
					'discount'=>'50%','price'=>40000,'special_price'=>20000,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"Muscle N Mind - 1 Year Gym Membership",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_12.jpg',
					'location'=>'Colaba',
					'discount'=>'40%','price'=>22000,'special_price'=>13200,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"Zumba with illumination - 12 Sessions",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_23.jpg',
					'location'=>'Andheri,Kandivali,Goregaon,Dahisar',
					'discount'=>'72%','price'=>3500,'special_price'=>999,'type'=>"service",'sold_out'=>0
					),	
				array(
					'name'=>"JG's Fitness - Group X (Zumba, Spinning, Aerobics, Boot Camp) - 6 Sessions",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_9.jpg',
					'location'=>'Santacruz',
					'discount'=>'78%','price'=>3000,'special_price'=>666,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"Activ8 - Pilates - 1 Month - 12 Sessions",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_22.jpg',
					'location'=>'Juhu',
					'discount'=>'20%','price'=>8250,'special_price'=>6600,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"4& Fitness - 1 Session Pass",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_18.jpg',
					'location'=>'Lokhandwala',
					'discount'=>'100%','price'=>1000,'special_price'=>0,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"Reebok Fitness Studio - 12 sessions",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_3.jpg',
					'location'=>'Khar',
					'discount'=>'30%','price'=>3500,'special_price'=>2499,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"The Soul Studio - 1 Month (All Dance Forms)",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_24.jpg',
					'location'=>'Andheri West',
					'discount'=>'80%','price'=>2500,'special_price'=>499,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"Endurance Fitness - 1 Month Gym Membership",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_17.jpg',
					'location'=>'Lokhandwala',
					'discount'=>'43%','price'=>1111,'special_price'=>499,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"Xtreme Fight Federation - 1 Session",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_11.jpg',
					'location'=>'Bandra , Andheri , Tardeo , Lokhandwala , Marine Drive',
					'discount'=>'66%','price'=>750,'special_price'=>250,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"House of Wow - Masala Bhangra - 1 Month - 8 Sessions",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_7.jpg',
					'location'=>'Bandra',
					'discount'=>'75%','price'=>4000,'special_price'=>999,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"Integym - 1 Year Gym Membership",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_14.jpg',
					'location'=>'Colaba',
					'discount'=>'25%','price'=>18000,'special_price'=>13500,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"Mint - Zumba - 1 Month + 1 Month FREE - 16 Sessions",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_2.jpg',
					'location'=>'Khar',
					'discount'=>'50%','price'=>5000,'special_price'=>2499,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"YFC - 1 Month Gym Membership",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_6.jpg',
					'location'=>'Kandivali East',
					'discount'=>'78%','price'=>2244,'special_price'=>499,'type'=>"service",'sold_out'=>0
					),		
				array(
					'name'=>"Zest 4 Life - Robusfit - 1 Week",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_15.jpg',
					'location'=>'Andheri West',
					'discount'=>'75%','price'=>2000,'special_price'=>499,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"Fighting Fit - 1 Month",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_20.jpg',
					'location'=>'Bandra,Khar',
					'discount'=>'96%','price'=>2500,'special_price'=>99,'type'=>"service",'sold_out'=>0
					),
				array(
					'name'=>"Yogacara - 1 Month",
					'image'=>'http://b.fitn.in/global/fitmania/onlineimageresize_com_10.jpg',
					'location'=>'Bandra',
					'discount'=>'10%','price'=>4200,'special_price'=>3780,'type'=>"service",'sold_out'=>0
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




// {
//   "from": 0,
//   "size": 25,
//   "keyword":"golds gym",
//   "category":"gyms,crossfit",
//   "location" :"navi mumbai,kandivali east"
// }


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
								"script": "(doc[\'finder.finder_type\'].value > 0 ? 100 : 0)"
							}
						}
					],
					"query": {
						"filtered": {
							"query": {
								"multi_match": {
									"query": "'.$globalkeyword.'",
									"fields": [
									"finder.title^5",
									"finder.search_category^10",
									"finder.search_categorytags^10",
									"finder.search_location^10",
									"finder.search_locationtags^10"
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
				"filtered": {
					"query": {
						"multi_match": {
							"query": "'.$globalkeyword.'",
							"fields": [
							"finder.title^2",
							"finder.slug^20",
							"finder.search_category^50",
							"finder.search_categorytags^20",
							"finder.search_location^5",
							"finder.search_locationtags^5",
							"finder.contact.address^1"
							]
						}
					}
					'.$filters.'
				}
			}
		}';


		//return $body;exit;

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

		$city 				=	(Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';	
		$city_id			=	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;
			
		$category 				=	(Input::json()->get('category')) ? Input::json()->get('category') : '';		
		$location 				=	(Input::json()->get('location')) ? Input::json()->get('location') : '';		
		$offerings 				=	(Input::json()->get('offerings')) ? Input::json()->get('offerings') : '';		
		$facilities 			=	(Input::json()->get('facilities')) ? Input::json()->get('facilities') : '';		
		$price_range 			=	(Input::json()->get('price_range')) ? Input::json()->get('price_range') : '';			

		//filters 
		$city_filter 			= ($city != '') ? '{"terms" : {  "city": ["'.str_ireplace(',', '","', $city ).'"] }},'  : '';
		$category_filter 		= ($category != '') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';		
		$categorytags_filter 	= ($category != '') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","',Input::json()->get('category')).'"] }},'  : '';
		$location_filter 		= ($location != '') ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('location')).'"] }},'  : '';	
		$locationtags_filter 	= ($location != '') ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('location')).'"] }},'  : '';	
		$offerings_filter 		= ($offerings != '') ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
		$facilities_filter 		= ($facilities != '') ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';	
		$price_range_filter 	= ($price_range != '') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","',Input::json()->get('price_range')).'"] }},'  : '';	

		$shouldfilter = $mustfilter = '';
		
		$category_filter;

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
			'url' => "http://ec2-54-169-60-45.ap-southeast-1.compute.amazonaws.com:9200/fitternitytest/finder/_search",
			'port' => 9200,
			'method' => 'POST',
			'postfields' => $serachbody
			);
		
		$search_results 	=	es_curl_request($request);

		$finder_leftside = array('categorytag_offerings' => Findercategorytag::active()->with('offerings')->orderBy('ordering')->get(array('_id','name','offering_header','slug','status','offerings')),
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

		$resp 	= 	array('search_results' => json_decode($search_results,true), 
						  'finder_leftside' => $finder_leftside);
		
		//return Response::json($search_results); exit;
		return Response::json($resp);
		//echo $body; exit;
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