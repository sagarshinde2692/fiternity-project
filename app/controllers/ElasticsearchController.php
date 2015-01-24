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


	// manage settings in elastic
	public function manageSetttings(){

		$url = $this->elasticsearch_url."fitternitytest/_close";
		$request = array(
			'url' =>  $url,
			'port' => $this->elasticsearch_port,
			'method' => 'POST',
			);

		echo es_curl_request($request);  

		
		// "edgengram_analyzer": {
		// 	"type": "custom",
		// 	"tokenizer": "standard",
		// 	"filter": ["haystack_edgengram"]
		// }	

		$body =	'{
			"analysis" : {
				"analyzer":{
					"snowball_analyzer": {
						"type": "custom",
						"tokenizer": "standard",
						"filter": ["standard","lowercase","asciifolding","filter_stop","filter_snowball"]
					},
					"shingle_analyzer": {
						"type": "custom",
						"tokenizer": "standard",
						"filter": ["standard","lowercase","asciifolding","filter_stop","filter_shingle","filter_snowball"]
					}
				},
				"filter": {
					"filter_stop": {
						"type":       "stop",
						"stopwords":  "_english_"
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
					"haystack_ngram": {
						"type": "nGram",
						"min_gram": 3,
						"max_gram": 15
					},
					"haystack_edgengram": {
						"type": "edgeNGram",
						"min_gram": 2,
						"max_gram": 15
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

		$index 				= 'fitternitytest';
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

		$url = $this->elasticsearch_url."fitternitytest/_open";
		$request = array(
			'url' =>  $url,
			'port' => $this->elasticsearch_port,
			'method' => 'POST',
			);

		echo es_curl_request($request);    
	}

	// create mapping
	public function createtype($type){

		switch (strtolower($type)) {
			case "finder":
			$typemapping = '{
				"finder" :{
					"_source" : {"enabled" : true },
					"properties":{
						"category" : {
							"type" : "string", 
							"index" : "not_analyzed"
						},
						"location" : {
							"type" : "string", 
							"index" : "not_analyzed"
						},
						"categorytags" : {
							"type" : "string", 
							"index" : "not_analyzed"
						},
						"locationtags" : {
							"type" : "string", 
							"index" : "not_analyzed"
						},
						"offerings" : {
							"type" : "string", 
							"index" : "not_analyzed"
						},
						"facilities" : {
							"type" : "string", 
							"index" : "not_analyzed"
						},
						"geolocation" : {
							"type" : "geo_point"
						}

					}
				}
			}';
			break;
		}

		$postfields_data = json_encode(json_decode($typemapping,true));
		$request = array(
			'url' => $this->elasticsearch_url."$type/_mapping",
			'port' => Config::get('elasticsearch.elasticsearch_port'),
			'method' => 'PUT',
			'postfields' => $postfields_data
			);
		return es_curl_request($request);
	}

	



}