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

	/**
	 * Display a listing of finders
	 *
	 * @return Response
	 */
	public function index(){

		$q 				= (Input::get('q') != '') ? Input::get('q') : '';
		$category_id 	= (Input::get('category_id') != '') ? Input::get('category_id') : '';
		$location_id 	= (Input::get('location_id') != '') ? Input::get('location_id') : '';
		$finder_type 	= (Input::get('finder_type') != '') ? Input::get('finder_type') : '';
		$status 		= (Input::get('status') != '') ? Input::get('status') : '';
		$appends_array = array(
			'q' => $q,
			'category_id' => $category_id,
			'location_id' => $location_id,
			'finder_type' => $finder_type,
			'status' => $status
			);
		//echo "<br>";print_r($appends_array);

		$perpage = Config::get('app.perpage');

		$query = Finder::with('category')->with('location')->orderBy('_id');					
		if($q != ''){
			$query->where('title', 'LIKE', '%'. $q .'%');
		}
		if($category_id != ''){
			$query->where('category_id', intval($category_id));
		}
		if($location_id != ''){
			$query->where('location_id', intval($location_id));
		}
		if($finder_type != ''){
			$query->where('finder_type', intval($finder_type));
		}
		if($status != ''){
			$query->where('status', $status);
		}		
		$finders = $query->paginate($perpage);
		//print_r($finders);exit;	
		#$categories = array_add(Findercategory::active()->orderBy('name')->where('parent_id','!=',0)->lists('name','_id'),'','select category');
		$categories = array_add(Findercategory::orderBy('name')->lists('name','_id'),'','select category');
		$locations = array_add(Location::orderBy('name')->lists('name','_id'),'','select location');
		$finder_type = array('' => 'select type', 1 => 'paid', 0 => 'free');
		$status = array('' => 'select status', 1 => 'active', 0 => 'inactive');

		//return Finder::with('category')->groupBy('category_id')->get();
		return View::make('finders.index', compact('finders', 'categories', 'locations', 'finder_type', 'status', 'appends_array'));
	}

	/**
	 * Show the form for creating a new finder
	 *
	 * @return Response
	 */
	public function create(){

		//$users = User::lists('name','_id');
		$facilities = Facility::active()->orderBy('name')->get();
		$offerings = Offering::active()->orderBy('name')->get();
		$categories = Findercategory::active()->orderBy('name')->lists('name','_id');
		$categorytags = Findercategorytag::active()->orderBy('name')->get();
		$locations = Location::active()->orderBy('name')->lists('name','_id');
		$locationtags = Locationtag::active()->orderBy('name')->get();
		return View::make('finders.create', compact('users','facilities','offerings','categories','categorytags','locations','locationtags'));
	}

	/**
	 * Store a newly created finder in storage.
	 *
	 * @return Response
	 */
	public function store(){

		$insertedid = Finder::max('_id') + 1;
		$validator = Validator::make($data = Input::all(), Finder::$rules);
		if ($validator->fails()){
			return Redirect::back()->withErrors($validator)->withInput();
		}
		
		$finderdata = $data;
		//finder has multiple location tags
		if(Input::has('locationtags.1')){
			array_set($finderdata, 'slug', url_slug(array($finderdata['title'])));
		}else{
			$selectedlocation = Location::where('_id', (int) $finderdata['location_id'])->pluck('name');
			array_set($finderdata, 'slug', url_slug(array($finderdata['title'],$selectedlocation)));
		}
		array_set($finderdata, 'alias', url_slug(array($finderdata['title'])));
		array_set($finderdata, 'views', 0);
		array_set($finderdata, 'average_rating', 0);
		array_set($finderdata, 'total_rating_count', 0);
		array_set($finderdata, 'popularity', intval($finderdata['popularity']));

		//used keep the relastionship cloumn atleast if not selected
		array_set($finderdata, 'categorytags', array());
		array_set($finderdata, 'locationtags', array());
		array_set($finderdata, 'offerings', array());
		array_set($finderdata, 'facilities', array());
		if(!Input::has('extratab_contents')){
			array_set($finderdata, 'extratab_contents', array());
		}else{
			uasort($finderdata['extratab_contents'],'sort_by_order');
			array_set($finderdata, 'extratab_contents', array_values($finderdata['extratab_contents']));
		}
		if(!Input::has('ratecards')){
			array_set($finderdata, 'ratecards', array());
		}else{
			uasort($finderdata['ratecards'],'sort_by_order');
			array_set($finderdata, 'ratecards', array_values($finderdata['ratecards']));
		}

		// print  "<br>";print_pretty($finderdata['extratab_contents']);
		// // print  "<br>";print_pretty($finderdata['ratecards']);
		// exit;


		array_set($finderdata, 'category_id', intval($finderdata['category_id']));
		array_set($finderdata, 'location_id', intval($finderdata['location_id']));
		array_set($finderdata, 'finder_type', intval($finderdata['finder_type']));
		//array_set($finderdata, 'user_id', intval($finderdata['user_id']));

		//manages gallery, logos, converimages
		$photosarr = array();
		for ($i=1; $i <= intval(Input::get('total_photos')); $i++){ 
			$photo['url'] = $insertedid."/".$i.".jpg";
			$photo['alt'] = $finderdata['title'];
			$photo['caption'] = $finderdata['title'];
			array_push($photosarr, $photo);
		}

		// print  "<br>";print_pretty($photoarr);
		// print  "<br>";print_pretty(Input::all());exit;
		array_set($finderdata, 'logo', $insertedid.".jpg");
		array_set($finderdata, 'coverimage', $insertedid.".jpg");
		array_set($finderdata, 'photos', $photosarr);


		$finder = new Finder($finderdata);		
		$finder->_id = $insertedid;
		$finder->save();

		//manage categorytags
		if(!empty(Input::get('categorytags'))){
			$findercategorytags = array_map('intval', Input::get('categorytags'));
			$finder = Finder::find($insertedid);
			$finder->categorytags()->sync(array());
			foreach ($findercategorytags as $key => $value) {
				$finder->categorytags()->attach($value);
			}
		}

		//manage locationtags
		if(!empty(Input::get('locationtags'))){
			$finderlocationtags = array_map('intval', Input::get('locationtags'));
			$finder = Finder::find($insertedid);
			$finder->locationtags()->sync(array());
			foreach ($finderlocationtags as $key => $value) {
				$finder->locationtags()->attach($value);
			}
		}

		//manage offerings
		if(!empty(Input::get('offerings'))){
			$finderofferings = array_map('intval', Input::get('offerings'));
			$finder = Finder::find($insertedid);
			$finder->offerings()->sync(array());
			foreach ($finderofferings as $key => $value) {
				$finder->offerings()->attach($value);
			}
		}

		//manage facilities
		if(!empty(Input::get('facilities'))){
			$finderfacilities = array_map('intval', Input::get('facilities'));
			$finder = Finder::find($insertedid);
			$finder->facilities()->sync(array());
			foreach ($finderfacilities as $key => $value) {
				$finder->facilities()->attach($value);
			}
		}

		$this->pushfinder2elastic($finderdata['slug']);

		Session::flash('message', 'Successfully created finders!');
		return Redirect::route('finders.index');
	}

	/**
	 * Display the specified finder.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id){

		$id = (int) $id;
		$finder = Finder::findOrFail($id);
		return View::make('finders.show', compact('finder'));
	}

	/**
	 * Show the form for editing the specified finder.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id){

		$id = (int) $id;
		$finder = Finder::with('offerings')->find($id);
		//$users = User::lists('name','_id');
		$facilities = Facility::active()->orderBy('name')->get();
		$offerings = Offering::active()->orderBy('name')->get();
		$categories = Findercategory::active()->orderBy('name')->lists('name','_id');
		$categorytags = Findercategorytag::active()->orderBy('name')->get();
		$locations = Location::active()->orderBy('name')->lists('name','_id');
		$locationtags = Locationtag::active()->orderBy('name')->get();						
		$finderofferings = array_pluck($finder->Offerings,'_id');
		return View::make('finders.edit', compact('finder','users','facilities','offerings','categories','categorytags','locations','locationtags','finderofferings'));
	}

	/**
	 * Update the specified finder in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id){

		//print  "<br>";print_pretty(Input::all());exit;
		$id = (int) $id;
		$finder = Finder::findOrFail($id);
		$oldfinderslug = $finder->slug; 

		$validator = Validator::make($data = Input::all(), Finder::$rules);
		if ($validator->fails()){

			return Redirect::back()->withErrors($validator)->withInput();
		}

		$finderdata = array_except($data, array('categorytags','locationtags','offerings','facilities'));
		//var_dump($finderdata['ratecards']);exit;
		
		//finder has multiple location tags
		if(Input::has('locationtags.1')){
			array_set($finderdata, 'slug', url_slug(array($finderdata['title'])));
		}else{
			$selectedlocation = Location::where('_id', (int) $finderdata['location_id'])->pluck('name');
			array_set($finderdata, 'slug', url_slug(array($finderdata['title'],$selectedlocation)));
		}
		array_set($finderdata, 'alias', url_slug(array($finderdata['title'])));
		array_set($finderdata, 'popularity', intval($finderdata['popularity']));

		if(!Input::has('extratab_contents')){
			array_set($finderdata, 'extratab_contents', array());
		}else{
			uasort($finderdata['extratab_contents'],'sort_by_order');
			array_set($finderdata, 'extratab_contents', array_values($finderdata['extratab_contents']));
		}
		if(!Input::has('ratecards')){
			array_set($finderdata, 'ratecards', array());
		}else{
			uasort($finderdata['ratecards'],'sort_by_order');
			array_set($finderdata, 'ratecards', array_values($finderdata['ratecards']));
		}	
		
		//print  "<br>";print_pretty($finderdata['extratab_contents']);
		// print  "<br>";print_pretty($finderdata['ratecards']);
		//exit;

		//manages gallery, logos, converimages
		$photosarr = array();
		for ($i=1; $i <= intval(Input::get('total_photos')); $i++){ 
			$photo['url'] = $id."/".$i.".jpg";
			$photo['alt'] = $finderdata['title'];
			$photo['caption'] = $finderdata['title'];
			array_push($photosarr, $photo);
		}

		// print  "<br>";print_pretty($photoarr);
		// print  "<br>";print_pretty(Input::all());exit;
		array_set($finderdata, 'logo', $id.".jpg");
		array_set($finderdata, 'coverimage', $id.".jpg");
		array_set($finderdata, 'photos', $photosarr);
		

		array_set($finderdata, 'category_id', intval($finderdata['category_id']));
		array_set($finderdata, 'location_id', intval($finderdata['location_id']));
		array_set($finderdata, 'finder_type', intval($finderdata['finder_type']));	
		//array_set($finderdata, 'user_id', intval($finderdata['user_id']));
		//print_pretty($finderdata);exit;
		$finder->update($finderdata);

		//manages categorytags
		if(!empty(Input::get('categorytags'))){
			$findercategorytags = array_map('intval', Input::get('categorytags'));
			$finder->categorytags()->attach($findercategorytags[0]);
			$finder->categorytags()->sync(array());
			foreach ($findercategorytags as $key => $value) {
				$finder->categorytags()->attach($value);
			}
		}else{
			$finder->categorytags()->sync(array());
		}

		//manages locationtags
		if(!empty(Input::get('locationtags'))){
			$finderlocationtags = array_map('intval', Input::get('locationtags'));
			$finder->locationtags()->attach($finderlocationtags[0]);
			$finder->locationtags()->sync(array());
			foreach ($finderlocationtags as $key => $value) {
				$finder->locationtags()->attach($value);
			}
		}else{
			$finder->locationtags()->sync(array());
		}

		//manages offerings
		if(!empty(Input::get('offerings'))){
			$finderofferings = array_map('intval', Input::get('offerings'));
			$finder->offerings()->attach($finderofferings[0]);
			$finder->offerings()->sync(array());
			foreach ($finderofferings as $key => $value) {
				$finder->offerings()->attach($value);
			}
		}else{
			$finder->offerings()->sync(array());
		}


		//manages facilities
		if(!empty(Input::get('facilities'))){
			$finderfacilities = array_map('intval', Input::get('facilities'));
			$finder->facilities()->attach($finderfacilities[0]);
			$finder->facilities()->sync(array());
			foreach ($finderfacilities as $key => $value) {
				$finder->facilities()->attach($value);
			}
		}else{
			$finder->facilities()->sync(array());
		}

		//Manages Redirects

		if($finderdata['slug'] != $oldfinderslug){
			$redirects = Urlredirect::where('finder_id','=',$id)->first();
			if(sizeof($redirects)){

				foreach ($redirects['oldslug'] as $key => $value) {
					if($value == $finderdata['slug'])
						$redirects->pull('oldslug',$finderdata['slug']);		
				}
				$redirects->push('oldslug',$oldfinderslug);
				array_set($redirectdata, 'newslug', $finderdata['slug']);
				$redirects->update($redirectdata);	
				//return $redirects;
			}else{	
				$redirectid = Urlredirect::max('_id') + 1;
				array_set($redirectdata, 'finder_id', $id);
				array_set($redirectdata, 'oldslug', array($oldfinderslug));
				array_set($redirectdata, 'newslug', $finderdata['slug']);
				array_set($redirectdata, 'type', 'finder');
				$redirects = new Urlredirect($redirectdata);		
				$redirects->_id = (int) $redirectid;
				$redirects->save();
				//return $redirects;
			}

		}

		//Push or Delete Records into elasticsearch base on status
		($finderdata['status'] == '1') ? $this->pushfinder2elastic($finderdata['slug']) : $this->deletefinder2elastic($id);

		Session::flash('message', 'Successfully updated finders!');
		return Redirect::route('finders.index');
	}

	/**
	 * Remove the specified finder from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id){

		$id = (int) $id;
		$finder = Finder::findOrFail($id);
		$finder->categorytags()->sync(array());
		$finder->locationtags()->sync(array());
		$finder->offerings()->sync(array());
		$finder->facilities()->sync(array());

		$this->deletefinder2elastic($id);
		Finder::destroy($id);

		Session::flash('message', 'Successfully deleted finders!');
		return Redirect::route('finders.index');
	}


	public function getcategorysofferings(){

		return Response::json(Findercategorytag::active()->with('offerings')->orderBy('name')->get());
	}


	public function finderdetail($slug){

		$tslug = (string) $slug;
		// $data = array();
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
			// $newfinderurl = '/finderdetail/'.$updatefindersulg->newslug;
			// return Redirect::to($newfinderurl);
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



public function generatemeta (){

	$items = Finder::with(array('category'=>function($query){$query->select('name');}))
	->with(array('location'=>function($query){$query->select('name');}))
	->active()
	->orderBy('_id')
    //->take(1)
	->get();

	$finderdata = array();
	foreach ($items as $item) {  
		$data 					= $item->toArray();
		$findertitle 			= ucwords($data['title']);
		$finderlocation 		= ucwords($data['location']['name']);
		$finder_metatitle 		= 	"$findertitle - Gym in $finderlocation , Mumbai I Best gyms & fitness studios in $finderlocation - Mumbai | Fitternity";
		$finder_metadescription = 	"$findertitle $finderlocation Mumbai. gym addresses, contact details, membership fees, reviews & ratings, images, offerings and facilities. Book free trials and buy memberships online for $findertitle on Fitternity";
		array_set($finderdata, 'meta.title', $finder_metatitle);
		array_set($finderdata, 'meta.description', $finder_metadescription);
		array_set($finderdata, 'meta.keywords', '');

		$finder = Finder::findOrFail($data['_id']);
		$finder->update($finderdata);
		//print "<pre>";print_r($finderdata);exit;
		//$finder->meta['keywords'] = "";

	}

}


public function generatefitternityno (){

	$items = Finder::orderBy('_id') ->get();

	$finderdata = array();
	foreach ($items as $item) {  
		$data 					= $item->toArray();
		$fitternityno 			= "022-612222".rand(31,61);
		array_set($finderdata, 'fitternityno', $fitternityno);
		array_set($finderdata, 'views', 0);
		//print $fitternityno."<br>";
		$finder = Finder::findOrFail($data['_id']);
		echo $finder->update($finderdata);
	}

}


public function getallfinders (){

	return Finder::active()->orderBy('_id')->get(array('title','slug'));

}


}
