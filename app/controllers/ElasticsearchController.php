<?php

/** 
* ControllerName : ElasticsearchController.
* Maintains a list of functions used for ElasticsearchController.
*
* @author Sanjay Sahu <sanjay.id7@gmail.com>
*/

class ElasticsearchController extends \BaseController {

	protected $facetssize 					=	10000;
	protected $limit 						= 	10000;
	protected $elasticsearch_host           =   "";
	protected $elasticsearch_port           =   "";
	protected $elasticsearch_default_index  =   "";
	protected $elasticsearch_url            =   "";
	protected $elasticsearch_default_url    =   "";
	protected $autosuggestIndex             =   "autosuggest";
	protected $elasticsearch_host_new 		=	"";
	protected $elasticsearch_url_new 		= 	"";
	protected $elasticsearch_port_new 		=	"";

	public function __construct() {
		parent::__construct();	
		$this->elasticsearch_default_url 		=	"http://".Config::get('app.es.host').":".Config::get('app.es.port').'/'.Config::get('app.es.default_index').'/';
		$this->elasticsearch_url 				=	"http://".Config::get('app.es.host').":".Config::get('app.es.port').'/';
		$this->elasticsearch_host 				=	Config::get('app.es.host');
		$this->elasticsearch_port 				=	Config::get('app.es.port');
		$this->elasticsearch_default_index 		=	Config::get('app.es.default_index');
		$this->elasticsearch_host_new			=	Config::get('app.es.host');
		$this->elasticsearch_port_new			=	Config::get('app.es.port');
		$this->elasticsearch_url_new			=	"http://".$this->elasticsearch_host_new.":".$this->elasticsearch_port_new."/";
	}


	// create Index in elastic
	public function createIndex($index = ''){
		$url = ($index != '') ? $this->elasticsearch_url."$index/" : $this->elasticsearch_default_url;
		$request = array(
			'url' => $url,
			'port' => $this->elasticsearch_port,
			'method' => 'PUT',
			);

		return es_curl_request($request);       
	}

	// Delete Index in elastic
	public function deleteIndex($index = ''){
		$url = ($index != '') ? $this->elasticsearch_url."$index/" : $this->elasticsearch_default_url;
		$request = array(
			'url' => $url,
			'port' => $this->elasticsearch_port,
			'method' => 'DELETE',
			);
		return es_curl_request($request);       
	}


	// manage settings in elastic
	public function manageSetttings($index = ''){

		$url 		= $this->elasticsearch_url."$index/_close";
		$request = array(
			'url' =>  $url,
			'port' => $this->elasticsearch_port,
			'method' => 'POST',
			);

		echo es_curl_request($request);  

		
		$body =	'{
			"analysis" : {
				"analyzer":{
					"simple_analyzer": {
						"type": "custom",
						"tokenizer": "standard",
						"filter": ["standard","lowercase","asciifolding","filter_stop","filter_worddelimiter"]
					},
					"snowball_analyzer": {
						"type": "custom",
						"tokenizer": "standard",
						"filter": ["standard","lowercase","asciifolding","filter_stop","filter_worddelimiter"]
					},
					"shingle_analyzer": {
						"type": "custom",
						"tokenizer": "standard",
						"filter": ["standard","lowercase","asciifolding","filter_stop","filter_shingle","filter_worddelimiter","filter_snowball"]
					},
					"autocomplete_analyzer": {
						"type": "custom",
						"tokenizer": "standard",
						"filter": ["standard","lowercase","asciifolding","filter_stop","filter_edgengram","filter_worddelimiter","filter_snowball"]
					},
					"title_analyzer" :{
						"type" : "custom",
						"tokenizer" : "input_ngram_tokenizer",
						"filter" : ["standard","lowercase","delimiter-filter", "titlesynfilter"]
					}
				},
				"filter": {
					"filter_stop": {
						"type":       "stop",
						"stopwords":  "_english_",
						"ignore_case" : true
					},
					"filter_shingle": {
						"type": "shingle",
						"max_shingle_size": 2,
						"min_shingle_size": 2,
						"output_unigrams": true
					},
					"filter_snowball": {
						"type": "snowball",
						"language" : "english"
					},
					"filter_stemmer": {
						"type": "porter_stem",
						"language": "English"
					},
					"filter_ngram": {
						"type": "nGram",
						"min_gram": 3,
						"max_gram": 15
					},
					"filter_edgengram": {
						"type": "edgeNGram",
						"min_gram": 2,
						"max_gram": 15
					},
					"filter_worddelimiter": {
						"type": "word_delimiter"
					},
					"delimiter-filter": {
						"type": "word_delimiter",
						"preserve_original" : true
					},
					"titlesynfilter":{
						"type": "synonym",
						"synonyms" : [
						"golds, gold",
						"talwalkars,talwalkar",
						"yfc,your fitness club"
						]
					}
				},
				"tokenizer": {
					"haystack_ngram_tokenizer": {
						"type": "nGram",
						"min_gram": 3,
						"max_gram": 15
					},
					"haystack_edgengram_tokenizer": {
						"type": "edgeNGram",
						"min_gram": 2,
						"max_gram": 15,
						"side": "front"
					},
					"input_ngram_tokenizer" : {
					"type": "edgeNGram",
					"min_gram": "2",
					"max_gram": "20"
					}
				}
			}
		}';

		$index 				= $index;
		$url 			 	= $this->elasticsearch_url."$index/_settings";
		//$url 			 	= $this->elasticsearch_url."_settings/";	
		$postfields_data 	= json_encode(json_decode($body,true));

		//var_dump($postfields_data);	exit;
		$request = array(
			'url' => $url,
			'port' => $this->elasticsearch_port,
			'postfields' => $postfields_data,
			'method' => 'PUT',
			);
		echo es_curl_request($request); 

		$url = $this->elasticsearch_url."$index/_open";
		$request = array(
			'url' =>  $url,
			'port' => $this->elasticsearch_port,
			'method' => 'POST',
			);

		echo es_curl_request($request);    
	}

	// create mapping
	public function createtype($type){

		$common_findermapping = '{
			"finder" :{
				"_source" : {"enabled" : true },
				"properties":{
					"title" : {"type" : "string", "index" : "not_analyzed"},
					"title_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"category" : {"type" : "string","index" : "not_analyzed"},
					"category_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"location" : {"type" : "string", "index" : "not_analyzed"},
					"location_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"categorytags" : {"type" : "string","index" : "not_analyzed"},
					"categorytags_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"locationtags" : {"type" : "string","index" : "not_analyzed"},
					"locationtags_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"offerings" : {"type" : "string", "index" : "not_analyzed"},
					"facilities" : {"type" : "string", "index" : "not_analyzed"},
					"geolocation" : {"type" : "geo_point","geohash": true,"geohash_prefix": true,"geohash_precision": 10},
					"average_rating" : {"type" : "float", "index" : "not_analyzed"},
					"locationcluster" : {"type" : "string", "index" : "not_analyzed"}
				}
			}
		}';


		$serivcesmapping = '{
			"service" :{
				"_source" : {"enabled" : true },
				"properties":{
					"name" : {"type" : "string", "index" : "not_analyzed"},
					"name_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"findername" : {"type" : "string", "index" : "not_analyzed"},
					"findername_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"finderslug" : {"type" : "string", "index" : "not_analyzed"},
					"finderslug_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"category" : {"type" : "string","index" : "not_analyzed"},
					"category_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"subcategory" : {"type" : "string","index" : "not_analyzed"},
					"subcategory_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"location" : {"type" : "string", "index" : "not_analyzed"},
					"location_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
					"session_type" : {"type" : "string","index" : "not_analyzed"},
					"workout_intensity" : {"type" : "string","index" : "not_analyzed"},
					"workout_tags" : {"type" : "string", "index" : "not_analyzed"},
					"city" : {"type" : "string","index" : "not_analyzed"},
					"geolocation" : {"type" : "geo_point","geohash": true,"geohash_prefix": true,"geohash_precision": 10},
					"ratecards": {
						"properties": {
							"duration" : {"type" : "string", "index" : "not_analyzed"},
							"price" : {"type" : "integer", "index" : "not_analyzed"},
							"special_price" : {"type" : "integer", "index" : "not_analyzed"}
						},
						"type": "nested"
					},
					"workoutsessionschedules": {
						"properties": {
							"weekday" : {"type" : "string", "index" : "not_analyzed"},
							"start_time" : {"type" : "string", "index" : "not_analyzed"},
							"start_time_24_hour_format" : {"type" : "float", "index" : "not_analyzed"},
							"price" : {"type" : "integer", "index" : "not_analyzed"}
						},
						"type": "nested"
					}
				}
			}
		}';

		$autosuggest_mappings = '{
			"_source": {
				"compress": "true"
			},
			"_all": {
				"enabled": "true"
			},
			"properties": {
				"input": {
					"type": "string",
					"index_analyzer": "index_analyzerV1",
					"search_analyzer": "search_analyzer"
				},
				"virgininput": {
					"type": "string",
					"index": "not_analyzed",
					"store": "yes"
				},
				"city": {
					"type": "string",
					"index": "not_analyzed",
					"store": "yes"
				},
				"location": {
					"type": "string",
					"index": "not_analyzed",
					"store": "yes"
				},
				"slug": {
					"type": "string",
					"index": "not_analyzed",
					"store": "yes"
				},
				"identifier": {
					"type": "string",
					"index": "not_analyzed",
					"store": "yes"
				},
				"inputloc1":{
					"type": "string",
					"index_analyzer": "synonymanalyzer"
				}
			}
		}';


		switch (strtolower($type)) {
			case "fitternityfinder":
			$typemapping 	=	$common_findermapping;	
			$typeurl 		=	Config::get('app.es.url')."/fitternity/finder/_mapping";
			//$typeurl 		=	$this->elasticsearch_url."fitternity/finder/_mapping"; 	
			//return $typeurl;
			break;

			case "fitternityservice":
			$typemapping 	=	$serivcesmapping;
			$typeurl 		=	Config::get('app.es.url')."/fitternity/service/_mapping";
			break;

			case "fittest":
			$typemapping 	=	$common_findermapping;
			$typeurl 		=	$this->elasticsearch_url."fittest/finder/_mapping"; 	
			break;

			case "fitmaniafinder":
			$typemapping 	=	$common_findermapping;
			$typeurl 		=	$this->elasticsearch_url."fitmania/finder/_mapping";
			break;

			case "fitcardfinder":
			$typemapping 	=	$common_findermapping;
			$typeurl 		=	$this->elasticsearch_url."fitcard/finder/_mapping";	
			break;

			case "autosuggestindex":
			$typemapping 	=	$autosuggest_mappings;
			$typeurl 		=	Config::get('app.es.url')."/autosuggest_index_alllocations/autosuggestor/_mapping";
			break;
		}
		
		$postfields_data 	= 	json_encode(json_decode($typemapping,true));
		// $url 				=  	$this->elasticsearch_default_url;

		$request = array(
			'url' => $typeurl,
			'port' => Config::get('app.es.port'),//Config::get('elasticsearch.elasticsearch_port'),
			'method' => 'PUT',
			'postfields' => $postfields_data
			);		
		return es_curl_request($request);

	}

	 // get all documents from mongodb
	public function mongo2elastic($type = 'fitternityfinder'){

		$itmes 		=	array();
		$item   	=	array();
		$postdata 	=	array();
		$doctype 	=	strtolower($type);

        //Manage the query base on type
		switch ($doctype) {
			case "fitternityfinder":
			$items = Finder::with(array('country'=>function($query){$query->select('name');}))
			->with(array('city'=>function($query){$query->select('name');}))
			->with(array('category'=>function($query){$query->select('name','meta');}))
			->with(array('location'=>function($query){$query->select('name');}))
			->with('categorytags')
			->with('locationtags')
			->with('offerings')
			->with('facilities')
			->active()
			->orderBy('_id')
			->take(1)
            // ->take(3000)->skip(0)
            //->take(3000)->skip(3000)
			->get();
			break;

			case "fitternityservice":
			$items = Service::with('category')->with('subcategory')->active()->take(5000)->skip(0)->latest()->get();
			break;

			case "fittestfinder":
			$items = Finder::with(array('country'=>function($query){$query->select('name');}))
			->with(array('city'=>function($query){$query->select('name');}))
			->with(array('category'=>function($query){$query->select('name','meta');}))
			->with(array('location'=>function($query){$query->select('name');}))
			->with('categorytags')
			->with('locationtags')
			->with('offerings')
			->with('facilities')
			->active()
			->orderBy('_id','desc')
					            //->take(2)
			->get();
			break;

			case "fitmaniafinder":

			$finderids  = array(1392,1579,1580,1581,1582,1583,1584,1602,1604,1605,1606,1607,1484,1650,718,1041,171,171,941,590,61,1490,1682,1494,900,570);
			$items      = Finder::with(array('country'=>function($query){$query->select('name');}))
			->with(array('city'=>function($query){$query->select('name');}))
			->with(array('category'=>function($query){$query->select('name','meta');}))
			->with(array('location'=>function($query){$query->select('name');}))
			->with('categorytags')
			->with('locationtags')
			->with('offerings')
			->with('facilities')
			->whereIn('_id',$finderids)
			->active()
			->orderBy('_id')
			->get();
			break;

			case "fitcardfinder":
			$bandra_vileparle_finder_ids        =       array(579,878,590,1606,1580,752,566,131,1747,1813,1021,424,1451,905,1388,1630,728,1031,1495,816,902,1650,1424,1587,1080,224,984,1563,1242,223,1887,1751,1493,1783,1691,1516,1781,1784,827,147,417,1676,1885,569);
			$andheri_borivalii_finder_ids       =       array(1579,1261,1705,401,561,1655,1513,1510,739,1514,570,1260,1261,40,1465,523,576,1332,166,1447,602,1428,1887,1786,604,1771,1257,1751,1523,1554,1209,439,625,1020,1522,1392,1667,1484,1041,1435,1694,1259,1413,45,449,1330,227,1697,1395,1511,1154,1873,1698,1691,1389,412,1642,1480,1676,417,1682,1069,1677,1445,1424,223,1214,1688,1080,1490,341);
			$south_mumbai_finder_ids            =       array(718,329,1603,1605,1449,328,171,1296,1327,1422,1710,1441,1293,1295,903,1835,1639,983,1851,1764,1823,1493,1646,1242,1563,1783,1887,984,1612,827,417,1782,138,731,1,422,1122,1781,1029,1706,1233,569,1888);
			$central_suburbs_finder_ids         =       array(1450,1602,413,1609,437,1501,927,1494,700,256,1030,170,417,1454,1581,1266);


			$finderids                          =       array_unique(array_merge($bandra_vileparle_finder_ids,$andheri_borivalii_finder_ids,$south_mumbai_finder_ids,$central_suburbs_finder_ids));
			$items                              =       Finder::with(array('country'=>function($query){$query->select('name');}))
			->with(array('city'=>function($query){$query->select('name');}))
			->with(array('category'=>function($query){$query->select('name','meta');}))
			->with(array('location'=>function($query){$query->select('name');}))
			->with('categorytags')
			->with('locationtags')
			->with('offerings')
			->with('facilities')
			->whereIn('_id',$finderids)
			->active()
			->orderBy('_id')
			->get();
			break;
		}

        // return Response::json($items);

        //manipulating or adding custom fields based on type
		foreach ($items as $item) {  
			$data = $item->toArray();
            //return Response::json($data);
			switch ($doctype) {
				case "fitternityfinder":
				$posturl 						=	$this->elasticsearch_url."fitternity/finder/".$data['_id'];	
				$postdata 						= 	get_elastic_finder_document($data);
				if(empty($postdata)) continue; 
				break;

				case "fitternityservice":
				$finder 						= 	Finder::with(array('country'=>function($query){$query->select('name');}))
				->with(array('city'=>function($query){$query->select('name');}))
				->with(array('location'=>function($query){$query->select('name');}))
				->where('_id', intval($data['finder_id']) )
				->get(array('_id', 'title', 'slug', 'city_id', 'city', 'country_id', 'country', 'slug', 'title', 'location_id', 'location', 'commercial_type'))->first();
				array_set($data, 'finder', $finder);
            	// return Response::json($data);

				$posturl 						=	Config::get('app.es.url')."/fitternity/service/".$data['_id'];
				$postdata 						= 	get_elastic_service_document($data);
				break;

				case "fittestfinder":
				$posturl 						=	$this->elasticsearch_url."fittest/finder/".$data['_id'];	
				$postdata 						= 	get_elastic_finder_document($data);
				break;

				case "fitmaniafinder":
				$posturl 						=	$this->elasticsearch_url."fitmania/finder/".$data['_id'];	
				$postdata 						= 	get_elastic_finder_document($data);
				$postdata['membership_offers'] 	= array();
				break;
				
				case "fitcardfinder":
				$posturl 						=	$this->elasticsearch_url."fitcard/finder/".$data['_id'];	
				$postdata 						= 	get_elastic_finder_document($data);
				break;
			} //switch           

            // return Response::json($postdata);exit;
            //$cityname = strtolower($postdata['city']);
            //if($cityname == 'mumbai'){
           	// echo  $response = $this->pushdocument($posturl, json_encode($postdata));
            //}
            //$response = $this->pushdocument($doctype, $data['_id'], json_encode($postdata));
            //echo $response

			$response = $this->pushdocument($posturl, json_encode($postdata));

		}//foreach
	}

    // push mongo document to elastic
	public function pushdocument($posturl, $postfields_data){
    	//echo $posturl;exit;
        //echo $postfields_data->_id;exit;
        //echo var_dump($postfields_data);exit;

		$request = array('url' => $posturl, 'port' => Config::get('elasticsearch.elasticsearch_port_new'), 'method' => 'PUT', 'postfields' => $postfields_data );

		echo "<br>$posturl    ---  ".es_curl_request($request);

	}

    //new controller functions to support Autosuggest search
	public function indexautosuggestdata($source = ''){

		$indexdocs = '';
		switch($source) {
			case 'fitternityfinders':
			$indexdocs = Finder::active()
			->with(array('category'=>function($query){$query->select('name','meta');}))
			->with(array('city'=>function($query){$query->select('name');}))                
			->with(array('location'=>function($query){$query->select('name','locationcluster_id' );}))
			->with('offerings')
			->with('facilities')
			->with('categorytags')
			->whereIn('city_id', array(1,2,3))
			->active()
			->take(3500)->skip(3500)->get();
                //->whereIn('_id',array(579))->get();               
                //->take(3000)->skip(3000)->get();
                //->take(3000)->skip(3000)->get();                                       
			break;

			case 'fitternitycategories':
			$indexdocs = Findercategory::active()->get();
			break;

			case 'fitternitybrands':
                //to do add brands as models or merge from staging
			break;
			case 'fitternitylocations' :
			$indexdocs = Location::active()->with('cities')->get(); 
            						//->whereIn('_id',array(126))
			

			break;
			
		}
		
		foreach($indexdocs as $doc)
		{
			$data = $doc->toArray();
			switch($source)
			{
				case 'fitternityfinders':

				$posturl                        =   $this->elasticsearch_url_new."autosuggest_index_alllocations/autosuggestor/".$data['_id'];
                        //$posturl 						=	$this->elasticsearch_url."autosuggest_index_alllocations/autosuggestor/".$data['_id'];                        
				$clusterid = '';
				if(!isset($data['location']['locationcluster_id']))
				{
					continue;
				}
				else
				{
					$clusterid  = $data['location']['locationcluster_id'];
				}
				
				$locationcluster = Locationcluster::active()->where('_id',$clusterid)->get();
				$locationcluster->toArray();                            
				$postdata = get_elastic_autosuggest_doc($data, $locationcluster[0]['name']);
				
				
                        //return $postdata;
				if(empty($postdata)) continue;
				break;

				case 'fitternitycategories':
				$posturl                        =   $this->elasticsearch_url_new."autosuggest_index_alllocations/autosuggestor/C".$data['_id'];
				$postdata                       =   get_elastic_category_doc($data);
				break;

				case 'fitternitybrands':
				$posturl                        =   $this->elasticsearch_url_new."autosuggest_index_alllocations/autosuggestor/";

				break;

				case 'fitternitylocations':
				$posturl                        =   $this->elasticsearch_url_new."autosuggest_index_alllocations/autosuggestor/levenshtein(str1, str2)".$data['_id'];
				$postdata                       =   get_elastic_location_doc($data);       
				
				break;

			}               
			$response = $this->pushdocument($posturl, json_encode($postdata));
		}
	}

	public function manageAutoSuggestSetttings(){

		$autosuggestindex  = "autosuggest_index_alllocations";
		$url 		= $this->elasticsearch_url_new."$autosuggestindex/_close";
		$request = array(
			'url' =>  $url,
			'port' => $this->elasticsearch_port_new,
			'method' => 'POST',
			);

		echo es_curl_request($request);


		$body =	'{
			"analysis": {
				"analyzer": {
					"synonymanalyzer":{
						"tokenizer": "standard",
						"filter": ["lowercase", "locationsynfilter"]
					},
					"search_analyzer": {
						"type": "custom",
						"filter": [
						"lowercase"
						],
						"tokenizer": "standard"
					},
					"index_analyzerV1": {
						"type": "custom",
						"filter": [
						"standard",
						"lowercase"
						],
						"tokenizer": "my_ngram_tokenizer"
					},
					"index_analyzerV2": {
						"type": "custom",
						"filter": [
						"standard",
						"lowercase",
						"ngram-filter"
						],
						"tokenizer": "standard"
					}
				},
				"tokenizer": {
					"my_ngram_tokenizer": {
						"type": "nGram",
						"min_gram": "3",
						"max_gram": "20"
					}
				},
				"filter": {
					"ngram-filter": {
						"type": "nGram",
						"min_gram": "3",
						"max_gram": "20"
					},
					"stop-filter": {
						"type": "stop",
						"stopwords": "_english_",
						"ignore_case": "true"
					},
					"snowball-filter": {
						"type": "snowball",
						"language": "english"
					},
					"delimiter-filter": {
						"type": "word_delimiter"
					},
					"locationsynfilter":{
						"type": "synonym",
						"synonyms" : [
						"lokhandwala,andheri west",
						"versova,andheri west",
						"oshiwara,andheri west",
						"chakala,andheri east",
						"jb nagar,andheri east",
						"marol,andheri east",
						"sakinaka,andheri east",
						"chandivali,powai",
						"vidyavihar,ghatkopar",
						"dharavi,sion",
						"chunabatti,sion",
						"deonar,chembur",
						"govandi,chembur",
						"anushakti nagar,chembur",
						"charkop,kandivali",
						"seven bungalows,andheri west",
						"opera house,grant road",
						"nana chowk,grant road",
						"shivaji park,dadar",
						"lalbaug,dadar",
						"walkeshwar,malabar hill",
						"tilak nagar,chembur",
						"vashi,navi mumbai",
						"sanpada,navi mumbai",
						"juinagar,navi mumbai",
						"nerul,navi mumbai",
						"seawoods,navi mumbai",
						"cbd belapur,navi mumbai",
						"kharghar,navi mumbai",
						"airoli,navi mumbai",
						"kamothe,navi mumbai",
						"kopar khairan,navi mumbai",
						"gamdevi,hughes road",
						"mazgaon,byculla"
						]
					}
				}
			}
		}';

		$index 				= $autosuggestindex;
		$url 			 	= $this->elasticsearch_url_new."$autosuggestindex/_settings";
        //$url 			 	= $this->elasticsearch_url."_settings/";      
		$postfields_data 	= json_encode(json_decode($body,true));

        //var_dump($postfields_data);	exit;
		$request = array(
			'url' => $url,
			'port' => $this->elasticsearch_port_new,
			'postfields' => $postfields_data,
			'method' => 'PUT',
			);       
		echo es_curl_request($request);

		$url = $this->elasticsearch_url_new."$autosuggestindex/_open";
		$request = array(
			'url' =>  $url,
			'port' => $this->elasticsearch_port_new,
			'method' => 'POST',
			);

		echo es_curl_request($request);
	}

}