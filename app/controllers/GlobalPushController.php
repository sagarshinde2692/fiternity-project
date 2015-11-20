<?php



class GlobalPushController extends \BaseController
{
	protected $indice = "autosuggest_index_alllocations1";
	protected $type   = "autosuggestor";	
	protected $elasticsearch_port = "";
	protected $elasticsearch_default_index = "";
	protected $elasticsearch_url = "";
	protected $elasticsearch_default_url = "";
	protected $elasticsearch_host = "";
	protected $citylist = array(1,2,3,4,8);
	protected $citynames = array('1' => 'mumbai','2' => 'pune', '3' => 'bangalore', '4' => 'delhi', '8' => 'gurgaon');
	public function __construct()
	{
		parent::__construct();		
		$this->elasticsearch_host = Config::get('app.elasticsearch_host_new');
		$this->elasticsearch_port = Config::get('app.elasticsearch_port_new');
		$this->elasticsearch_url  = 'http://'.$this->elasticsearch_host.':'.$this->elasticsearch_port.'/autosuggest_index_alllocations1/autosuggestor/';
		$this->build_elasticsearch_url = 'http://'.$this->elasticsearch_host.':'.$this->elasticsearch_port.'/autosuggest_index_alllocations1';
		//'http://localhost:9200/autosuggest_index_alllocations1';
	}

	public function pushfinders(){
		
		$indexdocs = Finder::with(array('country'=>function($query){$query->select('name');}))
		->with(array('city'=>function($query){$query->select('name');}))
		->with(array('category'=>function($query){$query->select('name','meta');}))
		->with(array('location'=>function($query){$query->select('name','locationcluster_id' );}))
		->with('categorytags')
		->with('locationtags')
		->with('offerings')
		->with('facilities')
		->with('services')
		->active()
		->orderBy('_id')         
        ->whereIn('_id', array(1623))
		->whereIn('city_id', array(1,2,3,4,8))
		->take(10000)->skip(0)
		->timeout(400000000)
        // ->take(3000)->skip(0)                          
		->get()->toArray();  
		//return $indexdocs;exit;
		foreach ($indexdocs as $data) {					
			$clusterid = '';
			if(!isset($data['location']['locationcluster_id']))
			{
				continue;
			}
			else
			{
				$clusterid  = $data['location']['locationcluster_id'];
			}
			// dd($data['categorytags'][0]['name']);
			// return $data->categorytags;exit;
			$locationcluster = Locationcluster::active()->where('_id',$clusterid)->get();
			$locationcluster->toArray();                            
			$postdata = get_elastic_autosuggest_doc($data, $locationcluster[0]['name']);	
			$postfields_data = json_encode($postdata);	 			
			$request = array('url' => $this->elasticsearch_url.$data['_id'], 'port' => $this->elasticsearch_port, 'method' => 'PUT', 'postfields' => $postfields_data);
			echo es_curl_request($request);
		}		
	}

	public function pushcategorylocations(){

		foreach ($this->citylist as $city) {
			$categorytags = Findercategorytag::active()
			->with('cities')
			->where('cities', $city)			
			->get();

			$locationtags = Locationtag::where('cities', $city)
			->get();

			foreach ($categorytags as $cat) {
				foreach ($locationtags as $loc) {	
					$cluster = '';										
					$string = ucwords($cat['name']).' in '.ucwords($loc['name']);					
					$postdata =  get_elastic_autosuggest_catloc_doc($cat, $loc, $string, $loc['city'], $cluster);
					$postfields_data = json_encode($postdata);						
					$request = array('url' => $this->elasticsearch_url."C".$cat['_id'].$loc['_id'], 'port' => $this->elasticsearch_port, 'method' => 'PUT', 'postfields' => $postfields_data);						
					echo "<br>    ---  ".es_curl_request($request);					
				}
			}						   

		}					
	}
	

	public function pushcategorywithfacilities(){
		$facilitieslist = array('Free Trial', 'Group Classes', 'Locker and Shower Facility', 'Parking', 'Personal Training', 'Sunday Open');

		foreach ($this->citylist as $key => $city) {			
			$cityname = $this->citynames[strval($city)];

			$categorytags = Findercategorytag::active()
			->with('cities')
			->where('cities', $city)
			->get();
			
			foreach ($categorytags as $cat) {
				foreach ($facilitieslist as $key1 => $fal) {									
					$string = ucwords($cat['name']).' with '.ucwords($fal);									
					$postdata =  get_elastic_autosuggest_catfac_doc($cat, $fal, $string, $cityname);
					$postfields_data = json_encode($postdata);					
					$request = array('url' => $this->elasticsearch_url."CF".$cat['_id'].$key1, 'port' => $this->elasticsearch_port, 'method' => 'PUT', 'postfields' => $postfields_data);		
					echo "<br> ---  ".es_curl_request($request);	
				}
			}			
		}
	}

	public function pushcategoryoffering(){
		foreach ($this->citylist as $city) {
			
			$cityname = $this->citynames[strval($city)];

			$categorytag_offerings = Findercategorytag::active()				
			->whereIn('cities',array($city))
			->with('offerings')
			->orderBy('ordering')
			->get(array('_id','name','offering_header','slug','status','offerings'));

			foreach ($categorytag_offerings as $cat) {
				$offerings = $cat['offerings'];
				foreach ($offerings as $off) {					
					$string = ucwords($cat['name']).' with '.ucwords($off['name']);
					$postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname);					
					$postfields_data = json_encode($postdata);					
					$request = array('url' => $this->elasticsearch_url."CF".$cat['_id'].$off['_id'], 'port' => $this->elasticsearch_port, 'method' => 'PUT', 'postfields' => $postfields_data);		
					echo "<br> ---  ".es_curl_request($request);					
				}								
			}
		}
	}

	public function pushcategoryofferinglocation(){
		foreach ($this->citylist as $city) {
			
			$cityname = $this->citynames[strval($city)];

			$locationtags = Locationtag::where('cities', $city)
			->get();

			$categorytag_offerings = Findercategorytag::active()				
			->whereIn('cities',array($city))
			->with('offerings')
			->orderBy('ordering')
			->get(array('_id','name','offering_header','slug','status','offerings'));

			foreach ($categorytag_offerings as $cat) {
				$offerings = $cat['offerings'];
				foreach ($offerings as $off) {	
					foreach ($locationtags as $loc) {
						$cluster = '';
						$string = ucwords($cat['name']).' in '.ucwords($loc['name']).' with '.ucwords($off['name']);						
						$postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster);			
						$postfields_data = json_encode($postdata);											
						$request = array('url' => $this->elasticsearch_url."CLF".$cat['_id'].$off['_id'].$loc['_id'], 'port' => $this->elasticsearch_port, 'method' => 'PUT', 'postfields' => $postfields_data);	
						echo "<br> ---  ".es_curl_request($request);					
					}			
				}					
			}
		}
	}

	public function pushcategoryfacilitieslocation(){
		$facilitieslist = array('Free Trial', 'Group Classes', 'Locker and Shower Facility', 'Parking', 'Personal Training', 'Sunday Open');

		foreach ($this->citylist as $key => $city) {			
			$cityname = $this->citynames[strval($city)];

			$locationtags = Locationtag::where('cities', $city)
			->get();

			$categorytags = Findercategorytag::active()
			->with('cities')
			->where('cities', $city)
			->get();
			
			foreach ($categorytags as $cat) {
				foreach ($facilitieslist as $key1 => $fal) {
					foreach ($locationtags as $loc) {
						$cluster ='';
						$string = ucwords($cat['name']).' in '.ucwords($loc['name']).' with '.ucwords($fal);														
						$postdata =  get_elastic_autosuggest_catlocfac_doc($cat, $fal, $loc, $string, $cityname, $cluster);		
						$postfields_data = json_encode($postdata);					
						$request = array('url' => $this->elasticsearch_url."CFL".$cat['_id'].$loc['_id'].$key1, 'port' => $this->elasticsearch_port, 'method' => 'PUT', 'postfields' => $postfields_data);		
						echo "<br> ---  ".es_curl_request($request);	
					}
				}
			}			
		}
	}

	public function buildglobalindex(){

		$url 		= $this->build_elasticsearch_url."/_close";
		$request = array(
			'url' =>  $url,
			'port' => $this->elasticsearch_port,
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
					 "locationanalyzer":{
					 	"type": "custom",
					 	"tokenizer": "standard",
					 	"filter": ["standard", "locationsynfilter", "lowercase"],
					 	"tokenizer": "my_ngram_tokenizer" 					 
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
						"type": "edgeNGram",
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
						"mazgaon,byculla",
						"navi mumbai,vashi",
						"navi mumbai,sanpada",
						"navi mumbai,juinagar",
						"navi mumbai,nerul",
						"navi mumbai,seawoods",
						"navi mumbai,cbd belapur",
						"navi mumbai,kharghar",
						"navi mumbai,airoli",
						"navi mumbai,kamothe",
						"navi mumbai,kopar khairan",
						"gamdevi,hughes road",
						"mazgaon,byculla"
						]
					}
				}
			}
		}';

		$index 				= 'autosuggestor';;
		$url 			 	= $this->build_elasticsearch_url."/_settings";              
		$postfields_data 	= json_encode(json_decode($body,true));
        
		$request = array(
			'url' => $url,
			'port' => $this->elasticsearch_port,
			'postfields' => $postfields_data,
			'method' => 'PUT',
			);       

		echo es_curl_request($request);

		$url = $this->build_elasticsearch_url."/_open";
		$request = array(
			'url' =>  $url,
			'port' => $this->elasticsearch_port,
			'method' => 'POST',
			);

		echo es_curl_request($request);

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
				"autosuggestvalue": {
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
				"type": {
					"type": "string",
					"index": "not_analyzed",
					"store": "yes"
				},
				"inputloc1":{
					"type": "string",
					"index_analyzer": "locationanalyzer"
				},
				"inputv3":{
					"type": "string",
					"index_analyzer": "index_analyzerV2"
				},
				"inputv4":{
					"type": "string",
					"index_analyzer": "index_analyzerV2"
				},
				"inputcat1":{
					"type": "string",
					"index_analyzer": "index_analyzerV2"
				}
			}
		}';

		$typeurl 		=	$this->build_elasticsearch_url."/autosuggestor/_mapping";
					
		$postfields_data 	= 	json_encode(json_decode($autosuggest_mappings,true));
		
		$request = array(
			'url' => $typeurl,
			'port' => $this->elasticsearch_port,
			'method' => 'PUT',
			'postfields' => $postfields_data
			);		
		return es_curl_request($request);

	}
}