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




	public function finderdetail($slug, $cache = true){

		$data 	=  array();
		$tslug 	= (string) $slug;

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
							->with('services')
							->where('slug','=',$tslug)
							->first();

			if($finderarr){
				
				$finderarr = $finderarr->toArray();
				// return  pluck( $finderarr['categorytags'] , array('name', '_id') );
				$finder 		= 	array_except($finderarr, array('categorytags','locationtags','offerings','facilities')); 
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
													->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','logo','coverimage'))
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
													->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','logo','coverimage'))
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
													->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','logo','coverimage'))
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
													->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','city_id','city','total_rating_count','logo','coverimage'))
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
		$result 	= 	$this->finderdetail($tslug,false);		
		$data 		= 	$result['finder'];
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

		//cache set

		
		array_set($finderdata, 'average_rating', round($average_rating,1));
		
		array_set($finderdata, 'total_rating_count', round($total_rating_count,1));

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

			// if($email){
			// 	echo "send";
			// }
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

		$tommorowDateTime 	=	date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
		$finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();

		foreach ($finders as $finderid => $trials) {
			$finder = 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();

			$finderarr = $finder->toArray();
			if($finder->finder_vcc_email != ""){
				// echo "<br>finderid  ---- $finder->_id <br>finder_vcc_email  ---- $finder->finder_vcc_email";
				// echo "<pre>";print_r($trials); 

				$finder_name_new					= 	(isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
				$finder_location_new				=	(isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
				$finder_name_base_locationtags 		= 	(count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

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
					'finder_name'					=> $finder->title,
					'finder_name_base_locationtags'	=> $finder_name_base_locationtags,
					'finder_poc_for_customer_name'	=> $finder->finder_poc_for_customer_name,
					'finder_vcc_email'				=> $finder->finder_vcc_email,	
					'scheduletrials' 				=> $trialdata
					);
				// echo "<pre>";print_r($scheduledata); exit;
				$this->findermailer->sendBookTrialDaliySummary($scheduledata);
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

		return "true";

		//healthy tiffins
		$items = Finder::active()->where('category_id', 42)->get();
		$finderdata = array();
		foreach ($items as $item) {  
			$data 	= $item->toArray();
			array_set($finderdata, 'popularity', 100);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

		// //marathon training
		// $items = Finder::active()->where('category_id',36)->get();
		// $finderdata = array();
		// foreach ($items as $item) {  
		// 	$data 	= $item->toArray();
		// 	array_set($finderdata, 'popularity', 100);
		// 	$finder = Finder::findOrFail($data['_id']);
		// 	$response = $finder->update($finderdata);
		// 	print_pretty($response);
		// }

		// //personal trainers
		// $items = Finder::active()->where('category_id',41)->get();
		// $finderdata = array();
		// foreach ($items as $item) {  
		// 	$data 	= $item->toArray();
		// 	array_set($finderdata, 'popularity', 2500);
		// 	$finder = Finder::findOrFail($data['_id']);
		// 	$response = $finder->update($finderdata);
		// 	print_pretty($response);
		// }

		// //dietitians and nutritionists
		// $items = Finder::active()->where('category_id',25)->get();
		// $finderdata = array();
		// foreach ($items as $item) {  
		// 	$data 	= $item->toArray();
		// 	array_set($finderdata, 'popularity', 2000);
		// 	$finder = Finder::findOrFail($data['_id']);
		// 	$response = $finder->update($finderdata);
		// 	print_pretty($response);
		// }

		// //physiotherapists
		// $items = Finder::active()->where('category_id',26)->get();
		// $finderdata = array();
		// foreach ($items as $item) {  
		// 	$data 	= $item->toArray();
		// 	array_set($finderdata, 'popularity', 1500);
		// 	$finder = Finder::findOrFail($data['_id']);
		// 	$response = $finder->update($finderdata);
		// 	print_pretty($response);
		// }		

		// //sports
		// $items = Finder::active()->where('category_id',40)->get();
		// $finderdata = array();
		// foreach ($items as $item) {  
		// 	$data 	= $item->toArray();
		// 	array_set($finderdata, 'popularity', 1000);
		// 	$finder = Finder::findOrFail($data['_id']);
		// 	$response = $finder->update($finderdata);
		// 	print_pretty($response);
		// }


	}


}
