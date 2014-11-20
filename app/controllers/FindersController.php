<?PHP

/** 
 * ControllerName : FindersController.
 * Maintains a list of functions used for FindersController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class FindersController extends \BaseController {

	protected $elasticsearch_url	=   "";

	public function __construct() {
		parent::__construct();	
		$this->elasticsearch_url = "http://".Config::get('elasticsearch.elasticsearch_host').":".Config::get('elasticsearch.elasticsearch_port').'/'.Config::get('elasticsearch.elasticsearch_default_index').'/';
	}

	
	public function getcategorysofferings(){

		return Response::json(Findercategorytag::active()->with('offerings')->orderBy('name')->get());
	}


	public function finderdetail($slug){

		$tslug = (string) $slug;
		$finder = Finder::with('category')
		->with('location')
		->with('categorytags')
		->with('locationtags')
		->with('offerings')
		->with('facilities')
		->where('slug','=',$tslug)
		->first();
		if($finder){
			return array("finder"=>$finder,"statusfinder"=>200);
		}else{
			$updatefindersulg = Urlredirect::whereIn('oldslug',array($tslug))->firstOrFail();
			return array("finder"=>$updatefindersulg->newslug,"statusfinder"=>404);
		}		

	}

	public function getfinderleftside(){
		$data = array('categorytag_offerings' => Findercategorytag::active()->with('offerings')->orderBy('ordering')->get(),
			'locations' => Location::active()->orderBy('name')->get(array('name','_id','slug')),	
			'facilities' => Facility::active()->orderBy('name')->get(array('name','_id','slug'))	
			);
		return Response::json($data);
	}

	public function pushfinder2elastic ($slug){

		$tslug 		= 	(string) $slug;
		$result 	= 	$this->finderdetail($tslug);		
		$data 		= 	$result['finder']->toArray();
		//print "<pre>"; print_r($data); exit;
		$documentid = 	$data['_id'];

		$postfields_data = array(
			'_id' => $data['_id'],
			'alias' => (isset($data['alias'])) ? $data['alias'] : '',
			'average_rating' => (isset($data['average_rating']) && $data['average_rating'] != '') ? round($data['average_rating'],1) : 0,
			'category' => strtolower($data['category']['name']),
			'category_metatitle' => $data['category']['meta']['title'],
			'category_metadescription' => $data['category']['meta']['description'],
			'categorytags' => array_map('strtolower',array_pluck($data['categorytags'],'name')),
			'contact' => $data['contact'],
			'coverimage' => $data['coverimage'],
			'finder_type' => $data['finder_type'],
			'fitternityno' => $data['fitternityno'],
			'facilities' => array_map('strtolower',array_pluck($data['facilities'],'name')),
			'logo' => $data['logo'],
			'location' => strtolower($data['location']['name']),
			'locationtags' => array_map('strtolower',array_pluck($data['locationtags'],'name')),
			'geolocation' => array('lat' => $data['lat'],'lon' => $data['lon']),
			'offerings' => array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))),
			'price_range' => (isset($data['price_range']) && $data['price_range'] != '') ? $data['price_range'] : "",
			'popularity' => (isset($data['popularity']) && $data['popularity'] != '' ) ? $data['popularity'] : 0,
			'slug' => $data['slug'],
			'status' => $data['status'],
			'title' => strtolower($data['title']),
			'total_rating_count' => (isset($data['total_rating_count']) && $data['total_rating_count'] != '') ? $data['total_rating_count'] : 0,
			'views' => (isset($data['views']) && $data['views'] != '') ? $data['views'] : 0

			);
		//print_r($postfields_data);

		$request = array(
			'url' => $this->elasticsearch_url."finder/$documentid",
			'port' => Config::get('elasticsearch.elasticsearch_port'),
			'method' => 'PUT',
			'postfields' => json_encode($postfields_data)
			);
		//echo es_curl_request($request);exit;
		es_curl_request($request);

	}


	public function deletefinder2elastic ($finderid){

		$request = array(
			"url" => $this->elasticsearch_url."finder/$finderid",
			"port" => Config::get("elasticsearch.elasticsearch_port"),
			"method" => "DELETE",
			);
			//echo es_curl_request($request);exit;
		es_curl_request($request);

	}

}
