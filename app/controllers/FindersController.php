<?PHP

/** 
 * ControllerName : FindersController.
 * Maintains a list of functions used for FindersController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\FinderMailer as FinderMailer;
use App\Services\Cacheapi as Cacheapi;


class FindersController extends \BaseController {


	protected $facetssize 					=	10000;
	protected $limit 						= 	10000;
	protected $elasticsearch_host           =   "";
	protected $elasticsearch_port           =   "";
	protected $elasticsearch_default_index  =   "";
	protected $elasticsearch_url            =   "";
	protected $elasticsearch_default_url    =   "";

	protected $findermailer;
	protected $cacheapi;

	public function __construct(FinderMailer $findermailer, Cacheapi $cacheapi) {

		parent::__construct();	
		$this->elasticsearch_default_url 		=	"http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/'.Config::get('app.elasticsearch_default_index').'/';
		$this->elasticsearch_url 				=	"http://".Config::get('app.elasticsearch_host').":".Config::get('app.elasticsearch_port').'/';
		$this->elasticsearch_host 				=	Config::get('app.elasticsearch_host');
		$this->elasticsearch_port 				=	Config::get('app.elasticsearch_port');
		$this->elasticsearch_default_index 		=	Config::get('app.elasticsearch_default_index');
		$this->findermailer						=	$findermailer;
		$this->cacheapi						=	$cacheapi;
	}


	public function finderdetail($slug, $cache = true){

		$data 	=  array();
		$tslug 	= (string) strtolower($slug);

		$finder_detail = $cache ? Cache::tags('finder_detail')->has($tslug) : false;

		if(!$finder_detail){
			
			$finderarr = Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
			->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
			->with(array('location'=>function($query){$query->select('_id','name','slug');}))
			->with('categorytags')
			->with('locationtags')
			->with('offerings')
			->with('facilities')
			->with('servicerates')
			// ->with('services')
			->with(array('services'=>function($query){$query->select('*')->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
			->with(array('reviews'=>function($query){$query->select('*')->where('status','=','1')->orderBy('_id', 'DESC');}))
			->where('slug','=',$tslug)
			->first();


			if($finderarr){
				
				$finderarr = $finderarr->toArray();
				// return  pluck( $finderarr['categorytags'] , array('name', '_id') );
				$finder 		= 	array_except($finderarr, array('coverimage','categorytags','locationtags','offerings','facilities')); 
				$coverimage  	=	($finderarr['coverimage'] != '') ? $finderarr['coverimage'] : 'default/'.$finderarr['category_id'].'-'.rand(1, 4).'.jpg';
				array_set($finder, 'coverimage', $coverimage);
				array_set($finder, 'categorytags', pluck( $finderarr['categorytags'] , array('_id', 'name', 'slug', 'offering_header') ));
				array_set($finder, 'locationtags', pluck( $finderarr['locationtags'] , array('_id', 'name', 'slug') ));
				array_set($finder, 'offerings', pluck( $finderarr['offerings'] , array('_id', 'name', 'slug') ));
				array_set($finder, 'facilities', pluck( $finderarr['facilities'] , array('_id', 'name', 'slug') ));

			}else{
				
				$finder = null;
			}

			if($finder){

				$finderdata 		=	$finder;
				$finderid 			= (int) $finderdata['_id'];
				$findercategoryid 	= (int) $finderdata['category_id'];
				$finderlocationid 	= (int) $finderdata['location_id'];	

				//if category is helath tifins or ditesion

				if($findercategoryid == 25 || $findercategoryid == 42){ 

					$nearby_same_category 		= 	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
					->where('_id','!=',$finderid)
					->where('category_id','=',$findercategoryid)
					->where('status', '=', '1')
					->orderBy('popularity', 'DESC')
					->remember(Config::get('app.cachetime'))
					->get(array('_id','average_rating','category_id','coverimage', 'finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','coverimage'))
					->take(5)->toArray();

					if($findercategoryid == 25){ $other_categoryid = 42; }else{ $other_categoryid = 25; } 

					$nearby_other_category 		= 	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
					->where('_id','!=',$finderid)
					->where('category_id','=',$other_categoryid)
					->where('status', '=', '1')
					->orderBy('popularity', 'DESC')
					->remember(Config::get('app.cachetime'))
					->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','coverimage'))
					->take(5)->toArray();

				}else{

					$nearby_same_category 		= 	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
					->where('category_id','=',$findercategoryid)
					->where('location_id','=',$finderlocationid)
					->where('_id','!=',$finderid)
					->where('status', '=', '1')
					->orderBy('finder_type', 'DESC')
					->remember(Config::get('app.cachetime'))
					->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','coverimage'))
					->take(5)->toArray();

					
					$nearby_other_category 		= 	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
					->where('category_id','!=',$findercategoryid)
					->where('location_id','=',$finderlocationid)
					->where('_id','!=',$finderid)
					->where('status', '=', '1')
					->orderBy('finder_type', 'DESC')
					->remember(Config::get('app.cachetime'))
					->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','coverimage'))
					->take(5)->toArray();
				}
				
				$data['statusfinder'] 					= 		200;
				$data['finder'] 						= 		$finder;
				$data['nearby_same_category'] 			= 		$nearby_same_category;
				$data['nearby_other_category'] 			= 		$nearby_other_category;

				Cache::tags('finder_detail')->put($tslug,$data,Config::get('cache.cache_time'));

				return Response::json(Cache::tags('finder_detail')->get($tslug));

			}else{

				$updatefindersulg 		= Urlredirect::whereIn('oldslug',array($tslug))->firstOrFail();
				$data['finder'] 		= $updatefindersulg->newslug;
				$data['statusfinder'] 	= 404;			
				
				return Response::json($data);
			}	
		}else{

			return Response::json(Cache::tags('finder_detail')->get($tslug));
		}	
	}

	public function finderServices($finderid){
		$finderid 	=  	(int) $finderid;
		$finder = Finder::active()->with(array('services'=>function($query){$query->select('*')->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))->where('_id','=',$finderid)->first();
		if($finder){
			$finderarr = $finder->toArray();
			$data['message'] 		= "Finder Detail With services"; 
			$data['status'] 		= 200; 
			$data['finder'] 		= array_only($finderarr, array('services')); 
		}else{
			$data['message'] 		= "Finder Does Not Exist"; 
			$data['status'] 		= 404; 
		}

		return Response::json($data,200);
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
		$finderarr 	= 	Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
		->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->with('categorytags')
		->with('locationtags')
		->with('offerings')
		->with('facilities')
		->with('servicerates')
		->with('services')
		->where('slug','=',$tslug)
		->first();
		$data 		= 	$finderarr->toArray();
		// print_pretty($data);exit;
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

		// $data = Input::all()->json();
		// return $data;
		$finderid = (int) Input::json()->get('finderid');
		$total_rating_count = round(floatval(Input::json()->get('total_rating_count')),1);
		$average_rating =  round(floatval(Input::json()->get('average_rating')),1);

		$finder = Finder::findOrFail($finderid);
		$finderslug = $finder->slug;
		$finderdata = array();


		//cache set

		array_set($finderdata, 'average_rating', round($average_rating,1));
		array_set($finderdata, 'total_rating_count', round($total_rating_count,1));

		// return $finderdata;
		if($finder->update($finderdata)){
			//updating elastic search	
			$this->pushfinder2elastic($finderslug); 
			//sending email
			$email_template = 'emails.review';
			$email_template_data = array( 'vendor' 	=>	ucwords($finderslug) ,  'date' 	=>	date("h:i:sa") );

			// print_pretty($email_template_data);  exit;


			$email_message_data = array(
				'to' => Config::get('mail.to_neha'), 
				'reciver_name' => 'Fitternity',
				'bcc_emailids' => Config::get('mail.bcc_emailds_review'), 
				'email_subject' => 'Review given for - ' .ucwords($finderslug)
				);
			$email = Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
				$message->to($email_message_data['to'], $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
					// $message->to('sanjay.id7@gmail.com', $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
			});

			//sending response
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

		// $tommorowDateTime 	=	date('d-m-Y', strtotime('02-09-2015'));
		$tommorowDateTime 	=	date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
		$finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();
		// $finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->where('finder_id', '=',3305)->get()->groupBy('finder_id')->toArray();

		// echo $todayDateTime 		=	date('d-m-Y', strtotime(Carbon::now()) );
		// return $todaytrialarr 		=	Booktrial::where('going_status', 1)
		// ->where('schedule_date', '>=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
		// ->where('schedule_date', '<=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
		// ->where('finder_id', 3305 )->get();

		foreach ($finders as $finderid => $trials) {
			$finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
			$finderarr 	= 	$finder->toArray();

			if($finder->finder_vcc_email != ""){
				$finder_vcc_email = "";
				$explode = explode(',', $finder->finder_vcc_email);
				$valid_finder_email = [];
				foreach ($explode as $email) {
					if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
						$valid_finder_email[] = $email;
					}
				}
				if(!empty($valid_finder_email)){
					$finder_vcc_email = implode(",", $valid_finder_email);
				}

				// echo "<br>finderid  ---- $finder->_id <br>finder_vcc_email  ---- $finder->finder_vcc_email";
				// echo "<pre>";print_r($trials); 

				$finder_name_new					= 	(isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
				$finder_location_new				=	(isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
				$finder_name_base_locationtags 		= 	(count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

				$trialdata = array();
				foreach ($trials as $key => $value) {
					$trial = array('customer_name' => $value->customer_name, 
						'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
						'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ), 
						'schedule_slot' => $value->schedule_slot, 
						'code' => $value->code, 
						'service_name' => $value->service_name,
						'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name
						);
					array_push($trialdata, $trial);
				}

				$todayDateTime 		=	date('d-m-Y', strtotime(Carbon::now()) );
				$todaytrialarr 		=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($todayDateTime))->where('finder_id', intval($finder->_id) )->get();
				$todaytrialdata = array();
				if($todaytrialarr){
					foreach ($todaytrialarr as $key => $value) {
						$trial = array('customer_name' => $value->customer_name, 
							'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
							'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ), 
							'schedule_slot' => $value->schedule_slot, 
							'code' => $value->code, 
							'service_name' => $value->service_name,
							'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name
							);
						array_push($todaytrialdata, $trial);
					}
				}


				$scheduledata = array('user_name'	=> 'sanjay sahu',
					'user_email'					=> 'sanjay.id7@gmail',
					'finder_name'					=> $finder->title,
					'finder_name_base_locationtags'	=> $finder_name_base_locationtags,
					'finder_poc_for_customer_name'	=> $finder->finder_poc_for_customer_name,
					'finder_vcc_email'				=> $finder_vcc_email,	
					'scheduletrials' 				=> $trialdata,
					'todaytrials' 					=> $todaytrialdata
					);
				// echo "<pre>";print_r($scheduledata); 
				
				$this->findermailer->sendBookTrialDaliySummary($scheduledata);
			}	  
		}

		$resp 	= 	array('status' => 200,'message' => "Email Send");
		return Response::json($resp);	
	}

	public function checkbooktrialdaliysummary($date){
		//give one date before
		$tommorowDateTime 	=	date('d-m-Y', strtotime($date));
		$finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();
		// $finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->where('finder_id', '=',3305)->get()->groupBy('finder_id')->toArray();

		// echo $todayDateTime 		=	date('d-m-Y', strtotime(Carbon::now()) );
		// return $todaytrialarr 		=	Booktrial::where('going_status', 1)
		// ->where('schedule_date', '>=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
		// ->where('schedule_date', '<=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
		// ->where('finder_id', 3305 )->get();

		foreach ($finders as $finderid => $trials) {
			$finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
			$finderarr 	= 	$finder->toArray();

			if($finder->finder_vcc_email != ""){
				$finder_vcc_email = "";
				$explode = explode(',', $finder->finder_vcc_email);
				$valid_finder_email = [];
				foreach ($explode as $email) {
					if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
						$valid_finder_email[] = $email;
					}
				}
				if(!empty($valid_finder_email)){
					$finder_vcc_email = implode(",", $valid_finder_email);
				}

				// echo "<br>finderid  ---- $finder->_id <br>finder_vcc_email  ---- $finder->finder_vcc_email";
				// echo "<pre>";print_r($trials); 

				$finder_name_new					= 	(isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
				$finder_location_new				=	(isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
				$finder_name_base_locationtags 		= 	(count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

				$trialdata = array();
				foreach ($trials as $key => $value) {
					$trial = array('customer_name' => $value->customer_name, 
						'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
						'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ), 
						'schedule_slot' => $value->schedule_slot, 
						'code' => $value->code, 
						'service_name' => $value->service_name,
						'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name
						);
					array_push($trialdata, $trial);
				}

				$todayDateTime 		=	date('d-m-Y', strtotime(Carbon::now()) );
				$todaytrialarr 		=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($todayDateTime))->where('finder_id', intval($finder->_id) )->get();
				$todaytrialdata = array();
				if($todaytrialarr){
					foreach ($todaytrialarr as $key => $value) {
						$trial = array('customer_name' => $value->customer_name, 
							'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
							'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ), 
							'schedule_slot' => $value->schedule_slot, 
							'code' => $value->code, 
							'service_name' => $value->service_name,
							'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name
							);
						array_push($todaytrialdata, $trial);
					}
				}


				$scheduledata = array('user_name'	=> 'sanjay sahu',
					'user_email'					=> 'sanjay.id7@gmail',
					'finder_name'					=> $finder->title,
					'finder_name_base_locationtags'	=> $finder_name_base_locationtags,
					'finder_poc_for_customer_name'	=> $finder->finder_poc_for_customer_name,
					'finder_vcc_email'				=> $finder_vcc_email,	
					'scheduletrials' 				=> $trialdata,
					'todaytrials' 					=> $todaytrialdata
					);
				echo "<pre>";print_r($scheduledata); 
				
				// $this->findermailer->sendBookTrialDaliySummary($scheduledata);
			}	  
		}

		$resp 	= 	array('status' => 200,'message' => "Email Send");
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

		// set popularity 10000 for following category
		$items = Finder::active()->where('finder_type', 0)->whereIn('city_id', array(2,3,4))->whereIn('category_id', array(5,11,14,32,35,6,12,8,7))->get();

		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			array_set($finderdata, 'popularity', 10000);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

		// set popularity 4000 for following category
		$items = Finder::active()->where('finder_type', 0)->whereIn('city_id', array(2,3,4))->whereIn('category_id', array(36,41,25,42,26,40))->get();
		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			array_set($finderdata, 'popularity', 4000);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

	}

	
	public function addReview(){
		// return Input::json()->all();
		$validator = Validator::make($data = Input::json()->all(), Review::$rules);
		if ($validator->fails()) {
			$response = array('status' => 400, 'message' => 'Could not create a review.', 'errors' => $validator->errors());
			return Response::json($response, 400); 
		}

		$reviewdata = [
		'finder_id' => intval($data['finder_id']),
		'customer_id' => intval($data['customer_id']),
		'rating' => intval($data['rating']),
		'detail_rating' => array_map('intval',$data['detail_rating']),
		'description' => $data['description'],
		'status' => '1'
		];

		$finderobj = Finder::where('_id', intval($data['finder_id']))->first();
		//$cacheurl = 'flushtagkey/finder_detail/'.$finderobj->slug;
		//clear_cache($cacheurl);

		//if exist then update
		$oldreview = Review::where('finder_id', intval($data['finder_id']))->where('customer_id', intval($data['customer_id']))->first();

		if($oldreview){
			$updatefinder = $this->updateFinderRatingV1($reviewdata,$oldreview);
			$oldreviewobj = Review::findOrFail(intval($oldreview->_id));
			$oldreviewobj->update($reviewdata);
			$response = array('status' => 200, 'message' => 'Review Updated Successfully.');
		}else{
			$inserted_id = Review::max('_id') + 1;
			$review = new Review($reviewdata);
			$review->_id = $inserted_id;
			$reviewobject = $review->save();
			$updatefinder = $this->updateFinderRatingV1($reviewdata);

			Log::info('Customer Review : '.json_encode(array('review_details' => Review::findOrFail($inserted_id))));

			$response = array('status' => 200, 'message' => 'Review Created Successfully.');
		}

		$this->cacheapi->flushTagKey('finder_detail',$finderobj->slug);
		$this->cacheapi->flushTagKey('review_by_finder_list',$finderobj->slug);
		
		return Response::json($response, 200);  
	}

	public function updateFinderRatingV1 ($review, $oldreview = NULL ){

		$data 					=	$review;
		$total_rating_count 	=	round(floatval(Input::json()->get('total_rating_count')),1);
		$average_rating 		=	round(floatval(Input::json()->get('average_rating')),1);
		
		$finderdata 			=	array();
		$finderid 				=	(int) $data['finder_id'];
		$finder 				=	Finder::findOrFail($finderid);
		$finderslug 			=	$finder->slug;
		$total_rating_count 	=	Review::where('finder_id', $finderid)->count();
		$sum_rating 		=	Review::where('finder_id', $finderid)->sum('rating');

		array_set($finderdata, 'total_rating_count', round($total_rating_count,1));
		array_set($finderdata, 'average_rating', ($sum_rating/$total_rating_count));

		//Detail rating summary count && Detail rating summary avg
		if(isset($finder->detail_rating_summary_average) && !empty($finder->detail_rating_summary_average)){
			if(isset($finder->detail_rating_summary_count) && !empty($finder->detail_rating_summary_count)){
				$detail_rating_summary_average = $finder->detail_rating_summary_average;
				$detail_rating_summary_count = $finder->detail_rating_summary_count;
				if($oldreview == NULL){
					for($i = 0; $i < 5; $i++) {
						if($data['detail_rating'][$i] > 0){
							$sum_detail_rating = floatval(floatval($finder->detail_rating_summary_average[$i]) * floatval($finder->detail_rating_summary_count[$i]));
							$detail_rating_summary_average[$i] = ($sum_detail_rating + $data['detail_rating'][$i])/($detail_rating_summary_count[$i]+1);
							$detail_rating_summary_count[$i] = (int) $detail_rating_summary_count[$i]+1;
						}
					}

				}else{
					for($i = 0; $i < 5; $i++) {
						if($oldreview['detail_rating'][$i] == 0 && $data['detail_rating'][$i] > 0){
							$sum_detail_rating = floatval(floatval($finder->detail_rating_summary_average[$i]) * floatval($finder->detail_rating_summary_count[$i])) - $oldreview['detail_rating'][$i];
							$detail_rating_summary_average[$i] = ($sum_detail_rating + $data['detail_rating'][$i])/($detail_rating_summary_count[$i]+1);
							$detail_rating_summary_count[$i] = (int) $detail_rating_summary_count[$i]+1;
						}
						else if($data['detail_rating'][$i] == 0 && $oldreview['detail_rating'][$i] > 0){
							$sum_detail_rating = floatval(floatval($finder->detail_rating_summary_average[$i]) * floatval($finder->detail_rating_summary_count[$i])) - $oldreview['detail_rating'][$i];
							if($detail_rating_summary_count[$i] > 1){
								$detail_rating_summary_average[$i] = ($sum_detail_rating)/($detail_rating_summary_count[$i]-1);
							}
							else{
								$detail_rating_summary_average[$i] = 0;	
							}
							$detail_rating_summary_count[$i] = (int) $detail_rating_summary_count[$i]-1;
						}
						else if($data['detail_rating'][$i] == 0 && $oldreview['detail_rating'][$i] == 0){

						}
						else{
							$sum_detail_rating = floatval(floatval($finder->detail_rating_summary_average[$i]) * floatval($finder->detail_rating_summary_count[$i])) - $oldreview['detail_rating'][$i];
							$detail_rating_summary_average[$i] = ($sum_detail_rating + $data['detail_rating'][$i])/($detail_rating_summary_count[$i]);
						}
					}
				}
			}
		}else{
			$detail_rating_summary_average = [0,0,0,0,0];
			$detail_rating_summary_count = [0,0,0,0,0];
			for($i = 0; $i < 5; $i++) {
				$detail_rating_summary_average[$i] =  ($data['detail_rating'][$i] > 0) ? $data['detail_rating'][$i] : 0;
				$detail_rating_summary_count[$i] = ($data['detail_rating'][$i] > 0) ? 1 : 0;
			}
		}
		array_set($finderdata, 'detail_rating_summary_average', $detail_rating_summary_average);
		array_set($finderdata, 'detail_rating_summary_count', $detail_rating_summary_count);
		
		// return $finderdata;
		$success = $finder->update($finderdata);
		return $finder;

		if($finder->update($finderdata)){
			//updating elastic search	
			$this->pushfinder2elastic($finderslug); 
			//sending email
			$email_template = 'emails.review';
			$email_template_data = array( 'vendor' 	=>	ucwords($finderslug) ,  'date' 	=>	date("h:i:sa") );
			$email_message_data = array(
				'to' => Config::get('mail.to_neha'), 
				'reciver_name' => 'Fitternity',
				'bcc_emailids' => Config::get('mail.bcc_emailds_review'), 
				'email_subject' => 'Review given for - ' .ucwords($finderslug)
				);
			$email = Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
				$message->to($email_message_data['to'], $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
					// $message->to('sanjay.id7@gmail.com', $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
			});

			//sending response
			$rating  = 	array('average_rating' => $finder->average_rating, 'total_rating_count' => $finder->total_rating_count, 'detail_rating_summary_average' => $finder->detail_rating_summary_average, 'detail_rating_summary_count' => $finder->detail_rating_summary_count);
			$resp 	 = 	array('status' => 200, 'rating' => $rating, "message" => "Rating Updated Successful :)");
			
			return Response::json($resp);
		}
	}

	public function getFinderReview($slug,$cache = true){
		$data = array();
		$tslug = (string) $slug;

		$review_by_finder_list = $cache ? Cache::tags('review_by_finder_list')->has($tslug) : false;

		if(!$review_by_finder_list){

			$finder_by_slug= Finder::where('slug','=',$tslug)->firstOrFail();

			if(!empty($finder_by_slug)){

				$finder_id 	= (int) $finder_by_slug['_id'];

				$reviews = Review::where('status', '!=', '1')
				->where('finder_id','=',$finder_id)
				->orderBy('_id', 'desc')
				->get(array('_id','finder_id','customer_id','customer','rating','detail_rating','description','updated_at','created_at'));

				$data = array('status' => 200,'data'=>$reviews);

				Cache::tags('review_by_finder_list')->put($slug,$data,Config::get('app.cachetime'));
				$response = $data;

			}else{
				$response = array('status' => 200,'message'=>'no reviews');
			}
		}else{

			$response = Cache::tags('review_by_finder_list')->get($tslug);
		}

		return Response::json($response);
	}


	/**
	 * Return the specified reivew.
	 *
	 * @param  int  	$reivewid
	 * @param  string  	$slug
	 * @return Response
	 */

	public function detailReview($reivewid){

		$review = Review::with('finder')->where('_id', (int) $reivewid)->first();

		if(!$review){
			$resp 	= 	array('status' => 400, 'review' => [], 'message' => 'No review Exist :)');
			return Response::json($resp, 400);
		}

		$reviewdata = $this->transform($review);
		$resp 	= 	array('status' => 200, 'review' => $reviewdata, 'message' => 'Particular Review Info');
		return Response::json($resp, 200);
	}


	private function transform($review){

		$item  =  (!is_array($review)) ? $review->toArray() : $review;
		$data = [
		'finder_id' => $item['finder_id'],
		'customer_id' => $item['customer_id'],
		'rating' => $item['rating'],
		'detail_rating' => $item['detail_rating'],
		'description' => $item['description'],
		'created_at' => $item['created_at'],
		'updated_at' => $item['updated_at'],
		'customer' => $item['customer'],
		'finder' =>  array_only($item['finder'], array('_id', 'title', 'slug'))
		];
		
		return $data;
	}



	public function reviewListing($finder_id, $from = '', $size = ''){
		
		$finder_id			= 	(int) $finder_id;	
		$from 				=	($from != '') ? intval($from) : 0;
		$size 				=	($size != '') ? intval($size) : 10;

		$reviews 			= 	Review::with(array('finder'=>function($query){$query->select('_id','title','slug','coverimage');}))->active()->where('finder_id','=',$finder_id)->take($size)->skip($from)->orderBy('_id', 'desc')->get();
		$responseData 		= 	['reviews' => $reviews,  'message' => 'List for reviews'];

		return Response::json($responseData, 200);
	}


}
