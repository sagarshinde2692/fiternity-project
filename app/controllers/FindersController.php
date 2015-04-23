<?PHP

/** 
 * ControllerName : FindersController.
 * Maintains a list of functions used for FindersController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\FinderMailer as FinderMailer;

class FindersController extends \BaseController {


	protected $facetssize 					=	10000;
	protected $limit 						= 	10000;
	protected $elasticsearch_host           =   "";
	protected $elasticsearch_port           =   "";
	protected $elasticsearch_default_index  =   "";
	protected $elasticsearch_url            =   "";
	protected $elasticsearch_default_url    =   "";

	protected $findermailer;

	public function __construct(FinderMailer $findermailer) {
		parent::__construct();	
		$this->elasticsearch_default_url 		=	"http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/'.Config::get('app.elasticsearch_default_index').'/';
		$this->elasticsearch_url 				=	"http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/';
		$this->elasticsearch_host 				=	Config::get('app.elasticsearch_host');
		$this->elasticsearch_port 				=	Config::get('app.elasticsearch_port');
		$this->elasticsearch_default_index 		=	Config::get('app.elasticsearch_default_index');
		$this->findermailer						=	$findermailer;
	}

	public function finderdetail($slug){
		$data 	= array();
		$tslug 	= (string) $slug;
		$finder = Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
						->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
						->with(array('location'=>function($query){$query->select('_id','name','slug');}))
						->with('categorytags')
						->with('locationtags')
						->with('offerings')
						->with('facilities')
						->with('servicerates')
						->where('slug','=',$tslug)
						->first();
		if($finder){
			$finderdata 		=	$finder->toArray();
			$finderid 			= (int) $finderdata['_id'];
			$findercategoryid 	= (int) $finderdata['category_id'];
			$finderlocationid 	= (int) $finderdata['location_id'];	

			//if category is helath tifins or ditesion


			if($findercategoryid == 25 || $findercategoryid == 42){

				// $nearby_same_categoryfinders 	= 	Finder::active()->where('_id','!=',$finderid)->where('category_id','=',$findercategoryid)->orWhere(function($query) use($findercategoryid){
				// 								        		$query->WhereIn('categorytags', array($findercategoryid));
				// 								    		})->get(array('_id' ))->toArray();

				// return Finder::active()->where('_id','!=',$finderid)->whereIn('categorytags', array(4))
				// 					->orWhere( function($query){
				// 						$query->where('category_id','=', 25);
				// 					})
				// 					->get(array('_id' ))->toArray();


				// return Finder::active()->where('category_id','=', 42)->get(array('_id' ))->toArray();

				// $nearby_same_categoryfinders 	= 	Finder::active()->where('category_id','=', 8)
				// 						            	->orWhere(function($query){
				// 						                	// $query->where('category_id','=', 8);
				// 						                	$query->whereIn('categorytags', array( 8 ));
				// 						            })
				// 						            ->get(array('_id' ))->toArray();


				// return $nearby_same_categoryfinder_randomids 	= 	array_pluck($nearby_same_categoryfinders, '_id');
				// return $nearby_same_categoryfinder_randomids; 


				$nearby_same_category 		= 	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
														->with(array('location'=>function($query){$query->select('_id','name','slug');}))
														->where('_id','!=',$finderid)
														->where('category_id','=',$findercategoryid)
														->where('status', '=', '1')
														->orderBy('popularity', 'DESC')
														->remember(Config::get('app.cachetime'))
														->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','logo','coverimage'))
														->take(5)->toArray();

				if($findercategoryid == 25){ $other_categoryid = 42; }else{ $other_categoryid = 25; } 

				$nearby_other_category 		= 	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
														->with(array('location'=>function($query){$query->select('_id','name','slug');}))
														->where('_id','!=',$finderid)
														->where('category_id','=',$other_categoryid)
														->where('status', '=', '1')
														->orderBy('popularity', 'DESC')
														->remember(Config::get('app.cachetime'))
														->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','logo','coverimage'))
														->take(5)->toArray();														


			}else{

				$nearby_same_category 		= 	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
														->with(array('location'=>function($query){$query->select('_id','name','slug');}))
														->where('category_id','=',$findercategoryid)
														->where('location_id','=',$finderlocationid)
														->where('_id','!=',$finderid)
														->where('status', '=', '1')
														->orderBy('finder_type', 'DESC')
														->remember(Config::get('app.cachetime'))
														->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','logo','coverimage'))
														->take(5)->toArray();	

				$nearby_other_category 		= 	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
														->with(array('location'=>function($query){$query->select('_id','name','slug');}))
														->where('category_id','!=',$findercategoryid)
														->where('location_id','=',$finderlocationid)
														->where('_id','!=',$finderid)
														->where('status', '=', '1')
														->orderBy('finder_type', 'DESC')
														->remember(Config::get('app.cachetime'))
														->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count','logo','coverimage'))
														->take(5)->toArray();					
			}

			



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

	// public function ratecards($finderid){

	// 	$finderid 	=  	(int) $finderid;
	// 	$ratecard 	= 	Ratecard::where('finder_id', '=', $finderid)->where('going_status', '=', 1)->orderBy('_id', 'desc')->get($selectfields)->toArray();
	// 	$resp 		= 	array('status' => 200,'ratecard' => $ratecard);
	// 	return Response::json($resp);
	// }

	public function ratecarddetail($id){
		$id 		=  	(int) $id;
		$ratecard 	= 	Ratecard::find($id);

		if($ratecard){
			$resp 	= 	array('status' => 200,'ratecard' => $ratecard);
		}else{
			$resp 	= 	array('status' => 200,'message' => 'No Ratecard exist :)');
		}
		return Response::json($resp);
	}


	public function getfinderleftside(){
		$data = array('categorytag_offerings' => Findercategorytag::active()->with('offerings')->orderBy('ordering')->get(array('_id','name','offering_header','slug','status','offerings')),
			'locations' => Location::active()->whereIn('cities',array(1))->orderBy('name')->get(array('name','_id','slug','location_group')),
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
		$postdata 	= 	get_elastic_finder_document($data);

		$request = array(
			'url' => $this->elasticsearch_url."fitternity/finder/".$data['_id'],
			'port' => Config::get('app.elasticsearch_port'),
			'method' => 'PUT',
			'postfields' => json_encode($postdata)
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

			$rating  = 	array('average_rating' => $finder->average_rating, 'total_rating_count' => $finder->total_rating_count);
			$resp 	 = 	array('status' => 200, 'rating' => $rating, "message" => "Rating Updated Successful :)");
			return Response::json($resp);
		}

	}


	public function updatefinderlocaiton (){

		$items = Finder::active()->orderBy('_id')->whereIn('location_id',array(14))->get(array('_id','location_id'));
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


	public function getallfinder(){
		
		//->take(2)
		$items = Finder::active()->orderBy('_id')->get(array('_id','slug','title'));
		return Response::json($items);				
	}


	public function sendbooktrialdaliysummary(){

		$tommorowDateTime 	=	date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
		$finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();

		foreach ($finders as $finderid => $trials) {
			$finder = 	Finder::where('_id','=',intval($finderid))->first();
			if($finder->finder_vcc_email != ""){
				// echo "<br>finderid  ---- $finder->_id <br>finder_vcc_email  ---- $finder->finder_vcc_email";
				// echo "<pre>";print_r($trials); 

				$trialdata = array();
				foreach ($trials as $key => $value) {
					$trial = array('customer_name' => $value->customer_name, 
						'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ), 
						'schedule_slot' => $value->schedule_slot, 
						'code' => $value->code, 
						'service_name' => $value->service_name,
						'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name
						);
					array_push($trialdata, $trial);
				}
				$scheduledata = array('user_name'	=> 'sanjay sahu',
					'user_email'					=> 'sanjay.id7@gmail',
					'finder_name'					=> $finder->name,
					'finder_poc_for_customer_name'	=> $finder->finder_poc_for_customer_name,
					'finder_vcc_email'				=> $finder->finder_vcc_email,	
					'scheduletrials' 				=> $trialdata
					);
				// echo "<pre>";print_r($scheduledata); 
				$this->findermailer->sendBookTrialDaliySummary($scheduledata);
			}	  
		}

		return $resp 	= 	array('status' => 200,'message' => "Email Send");
		return Response::json($resp);	

	}


	public function migrateratecards(){

		//Ratecard::truncate();
		Ratecard::truncate();
		//exit;
		// $items = Finder::with('category')->with('location')->active()->orderBy('_id')->take(2)->get();
		$items = Finder::with('category')->with('location')->active()->orderBy('_id')->get();
		$finderdata = array();

		foreach ($items as $item) {  
			$finderdata = $item->toArray();

			$finderratecards  	=	$finderdata['ratecards'];


			if(count($finderratecards) > 1){
				
				$finderid  			=	(int) $finderdata['_id'];
				$findercategory_id  =	$finderdata['category']['_id'];
				$location_id  		=	$finderdata['location']['_id'];
				$interest  			=	$finderdata['category']['name'];
				$area  				=	$finderdata['location']['name'];

				foreach ($finderratecards as $key => $value) {

					$ratedata 		= array();
					array_set($ratedata, 'finder_id', $finderid );
					array_set($ratedata, 'name', $value['service_name']);
					array_set($ratedata, 'slug', url_slug(array($value['service_name'])));
					array_set($ratedata, 'duration', $value['duration']);
					array_set($ratedata, 'price', intval($value['price']));
					array_set($ratedata, 'special_price', intval($value['special_price']));
					array_set($ratedata, 'product_url', $value['product_url']);
					array_set($ratedata, 'order',  (isset($value['order']) && $value['order'] != '') ? intval($value['order']) : 0);

					array_set($ratedata, 'findercategory_id', $findercategory_id );
					array_set($ratedata, 'location_id', $location_id );
					array_set($ratedata, 'interest', $interest );
					array_set($ratedata, 'area', $area );

					array_set($ratedata, 'short_description', '' );
					array_set($ratedata, 'body', '' );

					echo "<br><br>finderid  --- $finderid"; 
					//echo "<br>finderratecards <pre> "; print_r($ratedata);
					$insertedid	= Ratecard::max('_id') + 1;
					$ratecard 	= new Ratecard($ratedata);
					$ratecard->_id = $insertedid;
					$ratecard->save();
				}
			}

		}

	}


	public function updatepopularity (){

		return "true";

		//marathon training
		$items = Finder::active()->where('category_id',36)->get();
		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			array_set($finderdata, 'popularity', 3000);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

		//personal trainers
		$items = Finder::active()->where('category_id',41)->get();
		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			array_set($finderdata, 'popularity', 2500);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

		//dietitians and nutritionists
		$items = Finder::active()->where('category_id',25)->get();
		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			array_set($finderdata, 'popularity', 2000);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

		//physiotherapists
		$items = Finder::active()->where('category_id',26)->get();
		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			array_set($finderdata, 'popularity', 1500);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}		

		//sports
		$items = Finder::active()->where('category_id',40)->get();
		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			array_set($finderdata, 'popularity', 1000);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}


	}


}
