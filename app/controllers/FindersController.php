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
		$this->elasticsearch_url = "http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/'.Config::get('app.elasticsearch_default_index').'/';
	}


	public function finderdetail($slug){
		$data 	= array();
		$tslug 	= (string) $slug;
		$finder = Finder::with('category')
						->with('location')
						->with('categorytags')
						->with('locationtags')
						->with('offerings')
						->with('facilities')
						->where('slug','=',$tslug)
						//->remember(Config::get('app.cachetime'))
						->first();
		if($finder){
			$finderdata 		=	$finder->toArray();
			$finderid 			= (int) $finderdata['_id'];
			$findercategoryid 	= (int) $finderdata['category_id'];
			$finderlocationid 	= (int) $finderdata['location_id'];	
			
			$nearby_same_category 				= 			Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
																	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
																	->where('category_id','=',$findercategoryid)
																	->where('location_id','=',$finderlocationid)
																	->where('finder_type', '=', 1)
																	->where('_id','!=',$finderid)
																	->where('status', '=', '1')
																	->remember(Config::get('app.cachetime'))
																	->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','logo'))
																	->take(5)->toArray();	

			$nearby_other_category 				= 			Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
																	->with(array('location'=>function($query){$query->select('_id','name','slug');}))
																	->where('category_id','!=',$findercategoryid)
																	->where('location_id','=',$finderlocationid)
																	->where('finder_type', '=', 1)
																	->where('_id','!=',$finderid)
																	->where('status', '=', '1')
																	->remember(Config::get('app.cachetime'))
																	->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','logo'))
																	->take(5)->toArray();	

			$data['finder'] 						= 		$finder;
			$data['statusfinder'] 					= 		200;
			$data['nearby_same_category'] 			= 		$nearby_same_category;
			$data['nearby_other_category'] 			= 		$nearby_other_category;
			return $data;
		}else{
			$updatefindersulg 		= Urlredirect::whereIn('oldslug',array($tslug))->firstOrFail();
			$data['finder'] 		= $updatefindersulg->newslug;
			$data['statusfinder'] 	= 404;			
			return $data;
		}		
	}


	public function getfinderleftside(){
		$data = array('categorytag_offerings' => Findercategorytag::active()->with('offerings')->orderBy('ordering')->get(array('_id','name','offering_header','slug','status','offerings')),
					'locations' => Location::active()->orderBy('name')->get(array('name','_id','slug')),
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
			'port' => Config::get('app.elasticsearch_port'),
			'method' => 'PUT',
			'postfields' => json_encode($postfields_data)
			);
						//echo es_curl_request($request);exit;
		es_curl_request($request);
	}

	public function updatefinderrating (){

		//return Input::all()->json();
		$finderid = (int) Input::json()->get('finderid');
		$total_rating_count = round(floatval(Input::json()->get('total_rating_count')),1);
		$average_rating =  round(floatval(Input::json()->get('average_rating')),1);

		$finderdata = array();
		$finder = Finder::findOrFail($finderid);
		$finderslug = $finder->slug;
		array_set($finderdata, 'average_rating', round($average_rating,1));
		array_set($finderdata, 'total_rating_count', round($total_rating_count,1));
		if($finder->update($finderdata)){
			$this->pushfinder2elastic($finderslug); 
		}

	}


	public function updatefinderlocaiton (){

		$items = Finder::active()
						->orderBy('_id')
						->whereIn('location_id',array(14))
						->get(array('_id','location_id'));
							//exit;				
		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			//print_pretty($data);
			array_set($finderdata, 'location_id', 69);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

	}

}
