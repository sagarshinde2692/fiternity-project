<?php



class GlobalPushController extends \BaseController
{
	protected $indice = "autosuggest_index_alllocations";
	protected $type   = "autosuggestor";	
	protected $elasticsearch_port = "";
	protected $elasticsearch_default_index = "";
	protected $elasticsearch_url = "";
	protected $elasticsearch_default_url = "";
	protected $elasticsearch_host = "";
	protected $citylist = array(1,2,3,4,8);

	public function __construct()
	{
		parent::__construct();		
		$this->elasticsearch_host = Config::get('app.elasticsearch_host_new');
		$this->elasticsearch_port = Config::get('app.elasticsearch_port_new');
		$this->elasticsearch_url  = 'http://'.$this->elasticsearch_host.':'.$this->elasticsearch_port.'/autosuggest_index_alllocations/autosuggestor/';
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
                            //->whereIn('category_id', array(42,45))
                            //->whereIn('_id', array(1623))
		->whereIn('city_id', array(8))
		->take(2000)->skip(0)
		->timeout(400000000)
                            // ->take(3000)->skip(0)
                            //->take(3000)->skip(3000)
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
					$string = $cat['name'].' in '.strtolower($loc['name']);					
						$postdata =  get_elastic_autosuggest_catloc_doc($cat, $loc, $string, $city, $cluster);
						$request = array('url' => $this->elasticsearch_url."C".$cat['_id'], 'port' => $this->elasticsearch_port, 'method' => 'PUT', 'postfields' => $string);
						return $request;exit;
						echo "<br>$posturl    ---  ".es_curl_request($request);					
					}
				}						   

			}					
		}
	

	public function pushcategorywithfacilities(){
		$facilitieslist = array('Free Trial', 'Group Classes', 'Locker and Shower Facility', 'Parking', 'Personal Training', 'Sunday Open');

		foreach ($this->citylist as $city) {
			$categorytags = Findercategorytag::active()
			->with('cities')
			->where('cities', $city)
			->get();
			foreach ($categorytags as $cat) {
				foreach ($facilitieslist as $fal) {									
					$string = $cat['name'].' with '.$fal;
					$postdata =  get_elastic_autosuggest_catfac_doc($cat, $string);
					$request = array('url' => $this->elasticsearch_url."CF".$cat['_id'], 'port' => $this->elasticsearch_port, 'method' => 'PUT', 'postfields' => $string);
					return $request;exit;
					echo "<br>$posturl    ---  ".es_curl_request($request);	
				}
			}			
		}
	}

	public function pushcategoryoffering(){
		$categorytags = Findercategorytag::active()
		->with('cities')
		->where('cities', $city)
		->get();

		foreach ($categorytags as $cat) {
			return $cat;exit;
		// $categorytag_offerings = Findercategorytag::active()
		//         ->where('slug', '=', url_slug(array($category)))
		//         ->whereIn('cities',array($city_id))
		//         ->with('offerings')
		//         ->orderBy('ordering')
		//         ->get(array('_id','name','offering_header','slug','status','offerings'));
		}
	}
}