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

	public function __construct() {
		parent::__construct();	
		$this->elasticsearch_default_url 		=	"http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/'.Config::get('app.elasticsearch_default_index').'/';
		$this->elasticsearch_url 				=	"http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/';
		$this->elasticsearch_host 				=	Config::get('app.elasticsearch_host');
		$this->elasticsearch_port 				=	Config::get('app.elasticsearch_port');
		$this->elasticsearch_default_index 		=	Config::get('app.elasticsearch_default_index');
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
						"filter": ["standard","lowercase","asciifolding","filter_stop","filter_worddelimiter","filter_snowball"]
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
					"geolocation" : {"type" : "geo_point"}
				}
			}
		}';

		switch (strtolower($type)) {
			case "fitternityfinder":
			$typemapping 	=	$common_findermapping;
			$typeurl 		=	$this->elasticsearch_default_index."finder/_mapping";	
			break;

			case "fitmaniafinder":
			$typemapping 	=	$common_findermapping;
			$typeurl 		=	$this->elasticsearch_url."fitmania/finder/_mapping";
			break;

			case "fitcardfinder":
			$typemapping 	=	$common_findermapping;
			$typeurl 		=	$this->elasticsearch_url."fitcard/finder/_mapping";	
			break;
		}

		$postfields_data 	= 	json_encode(json_decode($typemapping,true));
		$url 				=  	$this->elasticsearch_default_url;
		$request = array(
			'url' => $typeurl,
			'port' => Config::get('elasticsearch.elasticsearch_port'),
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
                                //->take(10)
			->get();
			break;
		}

        //return Response::json($items);

        //manipulating or adding custom fields based on type
		foreach ($items as $item) {  
			$data = $item->toArray();
            //return Response::json($data);
			switch ($doctype) {
				case "fitternityfinder":
				$posturl 						=	$this->elasticsearch_url."fittternity/finder/".$data['_id'];	
				$postdata 						= 	get_elastic_finder_document($data);

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

            //return Response::json($postdata);exit;
            //$cityname = strtolower($postdata['city']);
            //if($cityname == 'mumbai'){
            $this->pushdocument($posturl, json_encode($postdata));
            //}
            //$response = $this->pushdocument($doctype, $data['_id'], json_encode($postdata));
            //echo $response

        }//foreach
    }

    // push mongo document to elastic
    public function pushdocument($posturl, $postfields_data){
        //echo $postfields_data->_id;exit;
        //echo var_dump($postfields_data);exit;

    	$request = array(
    		'url' => $posturl,
    		'port' => Config::get('elasticsearch.elasticsearch_port'),
    		'method' => 'PUT',
    		'postfields' => $postfields_data
    		);
    	echo "<br> $documentid    ---  ".es_curl_request($request);
    }




}