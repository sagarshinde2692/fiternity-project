<?PHP

/** 
 * ControllerName : OrderControllerDebugController.
 * Maintains a list of functions used for DebugController.
 *
 * @author Mahesh Jadhav
 */

// use Response;
use App\Mailers\FinderMailer as FinderMailer;
//use Queue;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Bulksms as Bulksms;
use App\Services\Utilities as Utilities;
use App\Sms\CustomerSms as CustomerSms;
use App\Services\Cacheapi as Cacheapi;


use \Pubnub\Pubnub as Pubnub;

class DebugController extends \BaseController {

	public $fitapi;

	public function __construct(FinderMailer $findermailer) {

		$this->findermailer						=	$findermailer;
		$this->fitapi = 'mongodb2';

	}

	public function invalidFinderStats(){

		$finder = Finder::active()->where('finder_vcc_email', 'exists', true)->where("finder_vcc_email","!=","")->orderBy('_id', 'asc')->get(array('_id','slug','finder_vcc_email'));
		$array = [];
		foreach ($finder as $key => $value) {

			$finderarray = [];
			$finderarray['id'] = $value->_id;
			$finderarray['slug'] = $value->slug;
			$finderarray['finder_vcc_email'] = $value->finder_vcc_email;
			$finderarray['finder_type'] = ($value->finder_type == 0) ? 'free' : 'paid';

			$explode = explode(',', $value->finder_vcc_email);
			$valid = [];
			$invalid = [];
			foreach ($explode as $email) {
				if (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
					array_push($invalid, $email);
				}else{
					array_push($valid, $email);
				}
			}

			$finderarray['valid'] = $valid;
			$finderarray['invalid'] = $invalid;

			$array[] = $finderarray;
		}

		foreach ($array as $key => $value) {

			if(count($value['invalid']) > 0)
			{
				echo"<pre>";print_r($value);
			}
			
		}
		
	}

	public function sendbooktrialdaliysummary(){

		$tommorowDateTime 	=	date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
		$finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime('20-08-2015'))->where('finder_id', '=',2420)->get()->groupBy('finder_id')->toArray();

		foreach ($finders as $finderid => $trials) {
			$finder = 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();

			$finderarr = $finder->toArray();
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
					$trial = array('customer_name' => $value['customer_name'], 
						'schedule_date' => date('d-m-Y', strtotime($value['schedule_date']) ), 
						'schedule_slot' => $value['schedule_slot'], 
						'code' => $value['code'], 
						'service_name' => $value['service_name'],
						'finder_poc_for_customer_name' => $value['finder_poc_for_customer_name']
						);
					array_push($trialdata, $trial);
				}
				
				$scheduledata = array('user_name'	=> 'sanjay sahu',
					'user_email'					=> 'sanjay.id7@gmail',
					'finder_name'					=> $finder->title,
					'finder_name_base_locationtags'	=> $finder_name_base_locationtags,
					'finder_poc_for_customer_name'	=> $finder->finder_poc_for_customer_name,
					'finder_vcc_email'				=> $finder_vcc_email,	
					'scheduletrials' 				=> $trialdata
					);
				// echo "<pre>";print_r($scheduledata); 
				$this->findermailer->sendBookTrialDaliySummary($scheduledata);
			}	  
		}

		$resp 	= 	array('status' => 200,'message' => "Email Send");
		return Response::json($resp);	
	}



	public function sendbooktrialdaliysummaryv1(){

		$tommorowDateTime 	=	date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
		$finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->where('finder_id', '=',3305)->get()->groupBy('finder_id')->toArray();

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
					// echo "<pre>";print_r($scheduledata); exit;
				$this->findermailer->sendBookTrialDaliySummary($scheduledata);
			}	  
		}

		$resp 	= 	array('status' => 200,'message' => "Email Send");
		return Response::json($resp);	
	}

	public function vendorStats(){


		$mumbai = array('gyms','yoga','pilates','zumba','cross-functional-training','kick-boxing','dance','martial-arts','spinning-and-indoor-cycling','crossfit','marathon-training','swimming','dietitians-and-nutritionists','healthy-tiffins','fitness-studios','healthy-snacks-and-beverages');
		$pune = array('gyms','yoga','dance','zumba','martial-arts','pilates','kick-boxing','spinning-and-indoor-cycling','crossfit','cross-functional-training','aerobics','fitness-studios');
		$banglore = array('gyms','yoga','dance','zumba','martial-arts','pilates','kick-boxing','spinning-and-indoor-cycling','crossfit','fitness-studios');
		$delhi = array('gyms','yoga','pilates','zumba','cross-functional-training','dance','martial-arts','spinning-and-indoor-cycling','crossfit');
		$commercial = array(0,1,2,3);

		$city = array('1'=>$mumbai,'2'=>$pune,'3'=>$banglore,'4'=>$delhi);
		$cities = array('1'=>'mumbai','2'=>'pune','3'=>'banglore','4'=>'delhi');
		$commercialType = array('0'=>'free','1'=>'paid','2'=>'free special','3'=>'COS');
		$result = array();
		$data = '';

		foreach ($city as $city_id => $city_value) {
			$category = Findercategory::active()->whereIn('slug',$city_value)->lists('_id','slug');

			foreach ($category as $category_value => $category_id ) {

				foreach ($commercial as $commercial_type) {

					$total = Finder::active()->where('city_id',(int)$city_id)->where('category_id',(int)$category_id)->where('commercial_type',(int)$commercial_type)->count();
					$with_contact = Finder::active()->where('contact.phone','!=','')->where('city_id',(int)$city_id)->where('category_id',(int)$category_id)->where('commercial_type',(int)$commercial_type)->count();
					$with_no_contact = Finder::active()->where('contact.phone','')->where('city_id',(int)$city_id)->where('category_id',(int)$category_id)->where('commercial_type',(int)$commercial_type)->lists('_id');
					$with_no_contact = '"('.implode(',', $with_no_contact).')"';

					$result[$cities[$city_id]][$category_value][$commercialType[$commercial_type]] = $total;
					$data .= $cities[$city_id].' : '.$category_value.' : '.$commercialType[$commercial_type].' : '.$total.' : '.$with_contact.' : '.$with_no_contact.' , ';

				}	
			}	
		}

		return $data;
	}

	public function getVendors(){

		$mumbai = array('gyms','yoga','pilates','zumba','cross-functional-training','kick-boxing','dance','martial-arts','spinning-and-indoor-cycling','crossfit','marathon-training','healthy-tiffins','fitness-studios','healthy-snacks-and-beverages');
		$pune = array('gyms','yoga','dance','zumba','martial-arts','pilates','kick-boxing','spinning-and-indoor-cycling','crossfit','cross-functional-training','aerobics','fitness-studios');
		$banglore = array('gyms','yoga','dance','zumba','martial-arts','pilates','kick-boxing','spinning-and-indoor-cycling','crossfit','fitness-studios');
		$delhi = array('gyms','yoga','pilates','zumba','cross-functional-training','dance','martial-arts','spinning-and-indoor-cycling','crossfit');
		$commercial = array(0,1,2,3);

		$city = array('1'=>$mumbai,'2'=>$pune,'3'=>$banglore,'4'=>$delhi);
		$cities = array('1'=>'MUM','2'=>'PUN','3'=>'BLR','4'=>'DEL');
		$commercialType = array('0'=>'free','1'=>'paid','2'=>'free special','3'=>'COS');
		$result = array();
		//$data = '';

		foreach ($city as $city_id => $city_value) {
			$category = Findercategory::active()->whereIn('slug',$city_value)->lists('_id','slug');

			//foreach ($category as $category_value => $category_id ) {

			foreach ($commercial as $commercial_type) {

					//$total = Finder::active()->where('city_id',(int)$city_id)->where('category_id',(int)$category_id)->where('commercial_type',(int)$commercial_type)->count();
				$data = Finder::active()->where('contact.phone','!=','')->where('city_id',(int)$city_id)->where('commercial_type',(int)$commercial_type)->orderBy('_id','ASC')->lists('_id');
					//$with_no_contact = Finder::active()->where('contact.phone','')->where('city_id',(int)$city_id)->where('category_id',(int)$category_id)->where('commercial_type',(int)$commercial_type)->lists('_id');
					//$with_no_contact = '"('.implode(',', $with_no_contact).')"';

				$result[$cities[$city_id]][$commercial_type] = $data;
					//$data .= $cities[$city_id].' : '.$category_value.' : '.$commercialType[$commercial_type].' : '.$total.' : '.$with_contact.' : '.$with_no_contact.' , ';

			}	
			//}	
		}

		return $result;

	}

	public function vendorsByMonth($from,$to){

		$cities = array('1'=>'Mumbai','2'=>'Pune','3'=>'Banglore','4'=>'Delhi');
		
		$finder = '"City","Month","Count";';



		foreach ($cities as $city_id => $city) {

			for ($i=4; $i < 10; $i++) { 
				$from_date = date( "1-".$i."-2015");
				$to_date = date( "1-".($i+1)."-2015");
				
				$count = Finder::active()->where('city_id',(int)$city_id)->where('created_at', '>',new DateTime($from_date))->where('created_at', '<',new DateTime($to_date))->orderBy('_id','ASC')->count();

				$month_year = date('M-Y', strtotime($from_date));
				$result[$cities[$city_id]][$month_year] = $count;
				$finder .= '"'.$cities[$city_id].'","'.$month_year.'","'.$count.'";';

				/*foreach ($data as $key => $value) {
					$hesh['finder'] = $value->slug;
					$hesh['month_year'] = date('M-Y', strtotime($value->created_at));
					$result[$cities[$city_id]][] = $hesh;
					$finder .= '"'.$cities[$city_id].'","'.date('M-Y', strtotime($value->created_at)).'","'.$value->slug.'";';

				}*/
			}

			/*foreach ($data as $key => $value) {
					$hesh['finder'] = $value->slug;
					$hesh['month_year'] = date('M-Y', strtotime($value->created_at));
					$result[$cities[$city_id]][] = $hesh;
					$finder .= '"'.$cities[$city_id].'","'.date('M-Y', strtotime($value->created_at)).'","'.$value->slug.'";';

				}*/

			//echo"<pre>";print_r($city_id);exit;
			//$data = Finder::active()->where('city_id',(int)$city_id)->where('created_at', '>',new DateTime($from_date))->where('created_at', '<',new DateTime($to_date))->orderBy('_id','ASC')->get();

			//echo"<pre>";print_r($data);exit;

				


			}

			return $finder;
		}

		public function gurgaonmigration(){
			$locationlist = array(411,403,393,388,389,392,394,397,396,398,412,408,409,406,402,407,400,401,399,404,391,405);
			$findertaglocation = array();
			$gurgaonfinderid = array();
			$gurgaonfinderlist = array();
			$locationtaglist = array(409,401,391,386,387,390,392,395,394,396,410,406,407,404,400,405,398,399,397,402,389,403);
			foreach ($locationtaglist as $loctag) {
				$tagfinder = Finder::where('locationtags', $loctag)->select('_id')->get();
				$localloc = array();
				foreach ($tagfinder as $x) {
					array_push($localloc, $x['_id']);
				}				
				array_push($gurgaonfinderlist, $localloc);
				array_push($findertaglocation, array('locationtag' => $loctag, 'finders' => $localloc));
			}

	//gurgaon city locationclusterid - 20
	//gurgaon city id 8
	//update Finder location update city and location cluster
			$finderlocation = Location::whereIn('_id', array(411,403,393,388,389,392,394,397,396,398,412,408,409,406,402,407,400,401,399,404,391,405))->get();
			if(isset($finderlocation)&&(!empty($finderlocation))){
				foreach ($finderlocation as $val) {
					$locdata = array();
					array_set($locdata,'cities', array(8));
					array_set($locdata,'locationcluster_id', 20);
					array_set($locdata,'city', 'gurgaon');
					$resp = $val->update($locdata);
				}
			}
	// //update Finder location tags with new city(id) and city(name)
			$finderlocationtags = Locationtag::whereIn('_id', array(409,401,391,386,387,390,392,395,394,396,410,406,407,404,400,405,398,399,397,402,389,403))->get();
			if(isset($finderlocation)&&(!empty($finderlocation))){
				foreach ($finderlocationtags as $val1) {
					$loctagdata = array();	
					$key = array();
					foreach ($findertaglocation as $t) {
						if($t['locationtag'] === $val1['_id']){
							$key = $t['finders'];
						}						
					}				
					//$key = array_search($val1['_id'], $findertaglocation);
					//$finderembed = $findertaglocation[$key];
					array_set($loctagdata, 'finders', $key);

					array_set($loctagdata,'city', 'gurgaon');
					array_set($loctagdata,'cities', array(8));
					$resp = $val1->update($loctagdata);
				}
			}

			$gurgaoncity = City::where('_id',8)->get();

			$delhicity = City::where('_id',4)->first();
			foreach ($gurgaoncity as $city) {
				$citydata = array();
				array_set($citydata, 'locations', $locationlist);
				array_set($citydata, 'locationtags', $locationtaglist);
				array_set($citydata, 'categorytags', $delhicity['categorytags']);
				array_set($citydata, 'findercategorys', $delhicity['findercategorys']);
				$resp = $city->update($citydata);				
			}

			$findercategories = FinderCategory::where('cities', 4)->get();
			foreach ($findercategories as $y) {
				$cities = $y['cities'];
				array_push($cities, 8);
				$findercatdata = array();
				array_set($findercatdata,'cities', $cities);	
				$resp = $y->update($findercatdata);				
			}

			$findercategorytags = Findercategorytag::where('cities', 4)->get();
			foreach ($findercategorytags as $z) {
				$cities1 = $z['cities'];
				array_push($cities1, 8);
				$findercattagdata = array();
				array_set($findercattagdata, 'cities', $cities1);
				$resp = $z->update($findercattagdata);
			}

			$gurgaonfinder = Finder::whereIn('location_id',$locationlist)->where('city_id', 4)->get();
			foreach ($gurgaonfinder as $val2) {
				$finddata = array();
				array_push($gurgaonfinderid, $val2['_id']);
				array_set($finddata,'city_id', 8);		
				$resp = $val2->update($finddata);
			}

			foreach ($gurgaonfinderlist as $value) {
				$service = Service::whereIn('finder_id', $value)->get();
				foreach ($service as $p) {
					$servicedata = array();
					array_set($servicedata, 'city_id', 8);
					$resp = $p->update($servicedata);
				}
			}

			$delhicity = City::where('_id',4)->first();
			$locationarray = $delhicity['locations'];
			$locationtagarray = $delhicity['locationtags'];
			$newlocationarray = array();
			$newlocationtagarray = array();	
			$newlocationarray1 = array();
			$newlocationtagarray1 = array();			
			$newlocationarray = array_diff($locationarray, $locationlist);
			$newlocationtagarray = array_diff($locationtagarray, $locationtaglist);
			foreach($newlocationarray as $r){
				array_push($newlocationarray1, $r);				
			}
			foreach($newlocationtagarray as $o){
				array_push($newlocationtagarray1, $o);				
			}

			$delhicityup = array();			
			array_set($delhicityup,'locations', $newlocationarray1);
			array_set($delhicityup,'locationtags', $newlocationtagarray1);
			$respv2 = $delhicity->update($delhicityup);
		}

		public function movekickboxing(){
			$finder = Finder::where('categorytags',8)->get();

			foreach ($finder as $value) {
				$finderdata = array();
				array_set($finderdata, 'category_id', 8);
				$value->update($finderdata);
			}
		}

		public function updateOrderAmount(){

			$order = Order::where('amount', 'exists', true)->orderBy('_id', 'desc')->get();

			$hesh = array();

			foreach ($order as $value) {

				$amout = (int) $value->amount;
				$orderdata = array();
				$hesh[$value->_id]['old'] = gettype($value->amount);
				array_set($orderdata, 'amount', '' );
				$value->update($orderdata);
				array_set($orderdata, 'amount', $amout );
				$value->update($orderdata);
				$hesh[$value->_id]['new'] = gettype($value->amount);
			}

			return $hesh;
		}

		public function vendorStatsMeta(){

			$cities = array('1'=>'Mumbai','2'=>'Pune','3'=>'Banglore','4'=>'Delhi','8'=>'Gurgaon');

			$data = '';

			foreach ($cities as $city_id => $city_name) {

				$finders = Finder::active()->where('city_id',(int)$city_id)
				->with(array('category'=>function($query){$query->select('_id','name','slug');}))	 
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->orderBy('_id', 'desc')
				->get();

				foreach ($finders as $key => $value) 
				{
					$data .= $city_name.';'.$value->title.';'.$value->location->name.';'.$value->category->name.';'.$value->meta["title"].';'.$value->meta["description"].' | ';		
				}			
				
			}

			return $data;
		}

		public function movepostnatal(){
			$finders = Finder::where('offerings', 360)->get();
			$movingfinder_id = array();
			foreach ($finders as $finder) {
				array_push($movingfinder_id, $finder['_id']);
				$finderofferings = $finder['offerings'];
				$key = array_search(360, $finderofferings);
				array_splice($finderofferings, $key);
				array_push($finderofferings, 310);
				$totalfinderofferings = array();
				array_set($totalfinderofferings,'offerings', $finderofferings);
				$finder->update($totalfinderofferings);
			}

			$liveoffering = Offering::where('_id', 310)->first();				
			$oldfinders = $liveoffering['finders'];			
			
			$newfinders = array_unique(array_merge($oldfinders, $movingfinder_id));
			$updatenewoffering = array();
			array_set($updatenewoffering, 'finders', $newfinders);			
			$liveoffering->update($updatenewoffering);

			$deadoffering = Offering::where('_id', 360)->first();
			$allfinder = array();
			$updateoldoffering = array();
			array_set($updateoldoffering, 'finders', $allfinder);
			$deadoffering->update($updateoldoffering);
		}

		public function csvBooktrialAll(){

			$date = "01_07_2015_to_30_11_2015";

			$start_date = new DateTime( date("d-m-Y", strtotime(date("01-07-2015"))));
			$end_date = new DateTime( date("d-m-Y", strtotime(date("30-11-2015")." +1 day")));

			//$array = array('_id','customer_name','customer_email','customer_phone','preferred_location','finder_id','finder_name','finder_location','created_at','service_name','premium_session','amount');

			$array = array('created_at','customer_name','customer_email','customer_phone','finder_id','finder_name','city_id','finder_location','service_name','premium_session','booktrial_type','amount','schedule_date_time');

			$trials = Booktrial::where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->orderBy('_id', 'desc')->get($array)->toArray();

			

			foreach ($trials as $key => $booktrial) {

				if(isset($booktrial['finder_id']))
				{
					$finder = Finder::find((int) $booktrial['finder_id'] );

					if(isset($finder->city_id) && $finder->city_id != ''){
						$city = City::find((int) $finder->city_id );

						$trials[$key]['city_id'] = ucwords($city->name);
					}else{
						$trials[$key]['city_id'] = '-';
					}

				}

				if(isset($booktrial['schedule_date_time']) && $booktrial['schedule_date_time'] != '')
				{

					$trials[$key]['schedule_date_time'] = date('Y-m-d',$booktrial['schedule_date_time']->sec);
				}

				if(isset($booktrial['premium_session']))
				{
					if($booktrial['premium_session'])
					{
						$trials[$key]['premium_session'] = 'Paid';

					}else{
						$trials[$key]['premium_session'] = 'Free';
					}
					
				}

				foreach ($array as $value) {

					if(!array_key_exists($value, $booktrial)){
						$trials[$key][$value] = '-';
					}else{
						$trials[$key][$value] = str_replace(","," ",$trials[$key][$value]);
					}
					
				}
				
			}


			$fp = fopen('trials_from_'.$date.'.csv', 'w');

			$header = [];

			foreach ($array as $key => $value) {

				$str = str_replace("_"," ",$value);
				$header[] = ucwords($str);

				if($value == 'created_at')
				{
					$header[$key] = 'Date';

				}

				if($value == 'premium_session')
				{
					$header[$key] = 'Trial (Free/Paid)';

				}

				if($value == 'city_id')
				{
					$header[$key] = 'City';

				}

				if($value == 'finder_location')
				{
					$header[$key] = 'Location';

				}
			}

			fputcsv($fp, $header);
			
			foreach ($trials as $value) {  
				

				$fields = array($value['created_at'],$value['customer_name'],$value['customer_email'],$value['customer_phone'],$value['finder_id'],$value['finder_name'],$value['city_id'],$value['finder_location'],$value['service_name'],$value['premium_session'],$value['booktrial_type'],$value['amount'],$value['schedule_date_time']);

				fputcsv($fp, $fields);
			}
			fclose($fp);
			return 'done';

		}


		public function csvOrderAll(){

			$date = "01_07_2015_to_30_11_2015";

			$start_date = new DateTime( date("d-m-Y", strtotime(date("01-07-2015"))));
			$end_date = new DateTime( date("d-m-Y", strtotime(date("30-11-2015")." +1 day")));

			$array = array('created_at','_id','customer_name','customer_email','customer_phone','finder_id','finder_name','service_name','service_duration','amount','payment_mode','city_id');

			$orders = Order::where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->orderBy('_id', 'desc')->get($array)->toArray();

			foreach ($orders as $key => $booktrial) {

				if(isset($booktrial['finder_id']))
				{
					$finder = Finder::find((int) $booktrial['finder_id'] );

					if(isset($finder->city_id) && $finder->city_id != ''){
						$city = City::find((int) $finder->city_id );

						$orders[$key]['city_id'] = ucwords($city->name);
					}else{
						$orders[$key]['city_id'] = '-';
					}

					if(isset($finder->location_id) && $finder->location_id != ''){
						$location = Location::find((int) $finder->location_id );

						$orders[$key]['Location'] = ucwords($location->name);
					}else{
						$orders[$key]['Location'] = '-';
					}

				}else{
					$orders[$key]['city_id'] = '-';
					$orders[$key]['Location'] = '-';
				}

				foreach ($array as $value) {

					if(!array_key_exists($value, $booktrial)){
						$orders[$key][$value] = '-';
					}else{
						$orders[$key][$value] = str_replace(","," ",$orders[$key][$value]);
					}
				}
				
			}


			$fp = fopen('orders_from_'.$date.'.csv', 'w');

			$header = [];

			foreach ($array as $key => $value) {

				$str = str_replace("_"," ",$value);
				$header[] = ucwords($str);

				if($value == 'created_at')
				{
					$header[$key] = 'Date';

				}

				if($value == '_id')
				{
					$header[$key] = 'Order ID';

				}

				if($value == 'city_id')
				{
					$header[$key] = 'City';

				}

			}

			array_push($header,'Location');


			fputcsv($fp, $header);
			
			foreach ($orders as $value) {  
				

				$fields = array($value['created_at'],$value['_id'],$value['customer_name'],$value['customer_email'],$value['customer_phone'],$value['finder_id'],$value['finder_name'],$value['service_name'],$value['service_duration'],$value['amount'],$value['payment_mode'],$value['city_id'],$value['Location']);

				fputcsv($fp, $fields);
			}

			fclose($fp);
			return 'done';
			
		}

		public function csvFakebuyAll(){

			$date = "01_07_2015_to_30_11_2015";

			$start_date = new DateTime( date("d-m-Y", strtotime(date("01-07-2015"))));
			$end_date = new DateTime( date("d-m-Y", strtotime(date("30-11-2015")." +1 day")));

			$array = array('created_at','name','email','mobile','finder_id','vendor','membership','city_id');

			$capture = Capture::where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->where('capture_type','=','FakeBuy')->orderBy('_id', 'desc')->get($array)->toArray();

			foreach ($capture as $key => $fakebuy) {

				if(isset($fakebuy['finder_id']))
				{
					$finder = Finder::find((int) $fakebuy['finder_id'] );

					if(isset($finder->city_id) && $finder->city_id != ''){
						$city = City::find((int) $finder->city_id );

						$capture[$key]['city_id'] = ucwords($city->name);
					}else{
						$capture[$key]['city_id'] = '-';
					}

					if(isset($finder->location_id) && $finder->location_id != ''){
						$location = Location::find((int) $finder->location_id );

						$capture[$key]['Location'] = ucwords($location->name);
					}else{
						$capture[$key]['Location'] = '-';
					}

				}else{
					$capture[$key]['city_id'] = '-';
					$capture[$key]['Location'] = '-';
				}

				foreach ($array as $value) {

					if(!array_key_exists($value, $fakebuy)){
						$capture[$key][$value] = '-';
					}else{
						$capture[$key][$value] = str_replace(","," ",$capture[$key][$value]);
					}
				}
				
			}


			$fp = fopen('fakebuys_from_'.$date.'.csv', 'w');

			$header = [];

			foreach ($array as $key => $value) {

				$str = str_replace("_"," ",$value);
				$header[] = ucwords($str);

				if($value == 'created_at')
				{
					$header[$key] = 'Date';

				}


				if($value == 'city_id')
				{
					$header[$key] = 'City';

				}

			}

			array_push($header,'Location');


			fputcsv($fp, $header);
			
			foreach ($capture as $value) {  
				

				$fields = array($value['created_at'],$value['name'],$value['email'],$value['mobile'],$value['finder_id'],$value['vendor'],$value['membership'],$value['city_id'],$value['Location']);

				fputcsv($fp, $fields);
			}

			fclose($fp);
			return 'done';
			
		}

		public function csvCaptureAll(){

			$date = "01_07_2015_to_30_11_2015";

			$start_date = new DateTime( date("d-m-Y", strtotime(date("01-07-2015"))));
			$end_date = new DateTime( date("d-m-Y", strtotime(date("30-11-2015")." +1 day")));

			$array = array('created_at','name','email','mobile','capture_type','vendor','location','city_id');

			$capture = Capture::where('created_at','>=',$start_date)->where('created_at','<=',$end_date)->where('capture_type','!=','FakeBuy')->orderBy('_id', 'desc')->get($array)->toArray();

			foreach ($capture as $key => $all) {

				if(isset($all['city_id']) && $all['city_id'] != ''){
					$city = City::find((int) $all['city_id'] );

					$capture[$key]['city_id'] = ucwords($city->name);
				}else{
					$capture[$key]['city_id'] = '-';
				}

				if(isset($all['location']) && $all['location'] != ''){

					$capture[$key]['location'] = ucwords($all['location']);
				}else{
					$capture[$key]['location'] = '-';
				}


				foreach ($array as $value) {

					if(!array_key_exists($value, $all)){
						$capture[$key][$value] = '-';
					}else{
						$capture[$key][$value] = str_replace(","," ",$capture[$key][$value]);
					}
				}
				
			}


			$fp = fopen('captures_from_'.$date.'.csv', 'w');

			$header = [];

			foreach ($array as $key => $value) {

				$str = str_replace("_"," ",$value);
				$header[] = ucwords($str);

				if($value == 'created_at')
				{
					$header[$key] = 'Date';

				}

				if($value == 'city_id')
				{
					$header[$key] = 'City';

				}

				if($value == 'location')
				{
					$header[$key] = 'Location';

				}

			}

			fputcsv($fp, $header);
			
			foreach ($capture as $value) {  
				

				$fields = array($value['created_at'],$value['name'],$value['email'],$value['mobile'],$value['capture_type'],$value['vendor'],$value['location'],$value['city_id']);

				fputcsv($fp, $fields);
			}

			fclose($fp);
			return 'done';
			
		}

		public function csvKatchi(){


			$array = array(9820332324,9869170441,9168554322,9821046843,9819801210,9320996070,9619592268,9930912468,9757039364,9820787817,8451811112);


			$order = Order::whereIn('customer_phone',$array)->where('finder_name','Katchi Marathon')->orderBy('_id', 'desc')->get()->toArray();

			$header = array(
				"_id",
				"uuid",
				"customer_id",
				"customer_name",
				"customer_lastname",
				"customer_father",
				"customer_mother",
				"customer_grandfather",
				"gender",
				"blood_group",
				"customer_phone",
				"customer_email",
				"birthday",
				"customer_identity",
				"customer_location",
				"customer_source",
				"city_id",
				"finder_id",
				"finder_name",
				"finder_address",
				"service_id",
				"service_name",
				"service_duration",
				"type",
				"sms_body",
				"email_body2",
				"amount",
				"emergency_number",
				"tshirt",
				"preferred_starting_date",
				"status",
				"payment_mode",
				"updated_at",
				"created_at");

			


			foreach ($order as $key => $all) {

				


				foreach ($header as $value) {

					if(!array_key_exists($value, $all)){
						$order[$key][$value] = '-';
					}else{
						$order[$key][$value] = str_replace(","," ",$order[$key][$value]);
					}
				}
				
			}
			

			$fp = fopen('katchi.csv', 'w');



			fputcsv($fp, $header);
			
			foreach ($order as $value) {  
				

				$fields = array(
					$value["_id"],
					$value["uuid"],
					$value["customer_id"],
					$value["customer_name"],
					$value["customer_lastname"],
					$value["customer_father"],
					$value["customer_mother"],
					$value["customer_grandfather"],
					$value["gender"],
					$value["blood_group"],
					$value["customer_phone"],
					$value["customer_email"],
					$value["birthday"],
					$value["customer_identity"],
					$value["customer_location"],
					$value["customer_source"],
					$value["city_id"],
					$value["finder_id"],
					$value["finder_name"],
					$value["finder_address"],
					$value["service_id"],
					$value["service_name"],
					$value["service_duration"],
					$value["type"],
					$value["sms_body"],
					$value["email_body2"],
					$value["amount"],
					$value["emergency_number"],
					$value["tshirt"],
					$value["preferred_starting_date"],
					$value["status"],
					$value["payment_mode"],
					$value["updated_at"],
					$value["created_at"]);

				fputcsv($fp, $fields);
			}

			fclose($fp);
			return 'done';
			
		}

		public function csvOzonetel(){

			/*$mumbai = array('gyms','yoga','pilates','zumba','cross-functional-training','kick-boxing','dance','martial-arts','spinning-and-indoor-cycling','crossfit','marathon-training','healthy-tiffins','fitness-studios','healthy-snacks-and-beverages');
        	$pune = array('gyms','yoga','dance','zumba','martial-arts','pilates','kick-boxing','spinning-and-indoor-cycling','crossfit','cross-functional-training','aerobics','fitness-studios');
        	$banglore = array('gyms','yoga','dance','zumba','martial-arts','pilates','kick-boxing','spinning-and-indoor-cycling','crossfit','fitness-studios');
        	$delhi = array('gyms','yoga','pilates','zumba','cross-functional-training','dance','martial-arts','spinning-and-indoor-cycling','crossfit');

        	$category = array_unique(array_merge($mumbai,$pune,$banglore,$delhi));*/

        	$catgory_slug = array('gyms','yoga','pilates','zumba','cross-functional-training','kick-boxing','dance','martial-arts','spinning-and-indoor-cycling','crossfit','marathon-training','healthy-tiffins','fitness-studios','healthy-snacks-and-beverages','aerobics');

        	$category_id = Findercategory::whereIn('slug',$catgory_slug)->lists('_id');

        	$finder = Finder::with(array('category'=>function($query){$query->select('name');}))->with(array('location'=>function($query){$query->select('name');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with(array('ozonetelno'=>function($query){$query->select('finder_id','phone_number','extension','type','_id')->where('status','=','1');}))->whereIn('category_id',$category_id)->get(array('_id','title','commercial_type','business_type','location_id','category_id','contact.phone','city_id'))->toArray();

        	$fp = fopen('finder_ozonetel.csv', 'w');

        	$header = array('Vendor ID','Vendor Name','Category','City','Location','Commercial Type','Business Type','Vendor Number','Ozonetel ID','Ozonetel Account','Ozonetel Number','Ozonetel Extension');


        	foreach ($finder as $key => $value) 
        	{
        		if(!isset($value['title'])){
        			$finder[$key]['title'] = '';
        		}

        		if(!isset($value['city']['name'])){
        			$finder[$key]['city']['name'] = '';
        		}

        		if(!isset($value['location']['name'])){
        			$finder[$key]['location']['name'] = '';
        		}

        		if(!isset($value['category']['name'])){
        			$finder[$key]['category']['name'] = '';
        		}

        		if(!isset($value['commercial_type_status'])){
        			$finder[$key]['commercial_type_status'] = '';
        		}

        		if(!isset($value['business_type_status'])){
        			$finder[$key]['business_type_status'] = '';
        		}

        		if(!isset($value['contact']['phone'])){
        			$finder[$key]['contact']['phone'] = '';
        		}

        		if(!isset($value['ozonetelno']['type'])){
        			$finder[$key]['ozonetelno']['type'] = '';
        		}

        		if(!isset($value['ozonetelno']['_id'])){
        			$finder[$key]['ozonetelno']['_id'] = '';
        		}

        		if(!isset($value['ozonetelno']['phone_number'])){
        			$finder[$key]['ozonetelno']['phone_number'] = '';
        		}

        		if(!isset($value['ozonetelno']['extension'])){
        			$finder[$key]['ozonetelno']['extension'] = '';
        		}

        		$finder[$key]['city']['name'] = ucwords($finder[$key]['city']['name']);
        		$finder[$key]['location']['name'] = ucwords($finder[$key]['location']['name']);
        		$finder[$key]['ozonetelno']['type'] = ucwords($finder[$key]['ozonetelno']['type']);
        		$finder[$key]['category']['name'] = ucwords($finder[$key]['category']['name']);

        		$validateNo = $this->validateNo($finder[$key]['contact']['phone']);

        		if($validateNo){
        			unset($finder[$key]);
        		}

        	}

        	fputcsv($fp, $header);

        	foreach ($finder as $value) {  


        		$fields = array($value['_id'],$value['title'],$value['category']['name'],$value['city']['name'],$value['location']['name'],$value['commercial_type_status'],$value['business_type_status'],$value['contact']['phone'],$value['ozonetelno']['_id'],$value['ozonetelno']['type'],$value['ozonetelno']['phone_number'],$value['ozonetelno']['extension']);

        		fputcsv($fp, $fields);
        	}

        	fclose($fp);

        	return 'done';



			/*$finder = Finder::with(array('location'=>function($query){$query->select('name');}))->with(array('ozonetelno'=>function($query){$query->select('finder_id','phone_number','extension','type','_id')->where('status','=','1');}))->get(array('_id','title','commercial_type','business_type','location_id','contact.phone'))->toArray();

			$fp = fopen('finder_ozonetel.csv', 'w');

			$header = array('Vendor ID','Vendor Name','Location','Commercial Type','Business Type','Vendor Number','Ozonetel ID','Ozonetel Account','Ozonetel Number','Ozonetel Extension');


			foreach ($finder as $key => $value) 
			{
				if(!isset($value['title'])){
					$finder[$key]['title'] = '';
				}

				if(!isset($value['location']['name'])){
					$finder[$key]['location']['name'] = '';
				}

				if(!isset($value['commercial_type_status'])){
					$finder[$key]['commercial_type_status'] = '';
				}

				if(!isset($value['business_type_status'])){
					$finder[$key]['business_type_status'] = '';
				}

				if(!isset($value['contact']['phone'])){
					$finder[$key]['contact']['phone'] = '';
				}

				if(!isset($value['ozonetelno']['type'])){
					$finder[$key]['ozonetelno']['type'] = '';
				}

				if(!isset($value['ozonetelno']['_id'])){
					$finder[$key]['ozonetelno']['_id'] = '';
				}

				if(!isset($value['ozonetelno']['phone_number'])){
					$finder[$key]['ozonetelno']['phone_number'] = '';
				}

				if(!isset($value['ozonetelno']['extension'])){
					$finder[$key]['ozonetelno']['extension'] = '';
				}

			}

			fputcsv($fp, $header);
			
			foreach ($finder as $value) {  
				

				$fields = array($value['_id'],$value['title'],$value['location']['name'],$value['commercial_type_status'],$value['business_type_status'],$value['contact']['phone'],$value['ozonetelno']['_id'],$value['ozonetelno']['type'],$value['ozonetelno']['phone_number'],$value['ozonetelno']['extension']);

				fputcsv($fp, $fields);
			}

			fclose($fp);*/
			
		}


		public function validateNo($value) {

			$value_explode = explode(',', $value);

			if(in_array("", $value_explode))
			{
				return false;
			}

			if (!preg_match('/(^[0-9,]+$)+/', $value)) {
				return false;
			}

			return true;
		}

		public function csvPeppertap(){

			$peppertap = Peppertap::where('status',0)->get(array('_id','code'))->toArray();

			$fp = fopen('peppertap_unused_code.csv', 'w');

			$header = array('ID','Code');


			foreach ($peppertap as $key => $value) 
			{
				if(!isset($value['code'])){
					$peppertap[$key]['code'] = '';
				}

			}

			fputcsv($fp, $header);
			
			foreach ($peppertap as $value) {  	

				$fields = array($value['_id'],$value['code']);

				fputcsv($fp, $fields);
			}

			fclose($fp);

			return 'done';
		}

		public function lonlat(){

			$location = Location::get();

			foreach ($location as $value) {

				if(isset($value->lon) && isset($value->lat)){

					$value->lonlat = array('lon'=>$value->lon,'lat'=>$value->lat);

					$value->update();
				}
				
			}

			return 'done';
		}

		public function orderFitmania(){

			$type = array('fitmania-dod','fitmania-dow','fitmania-membership-giveaways');

			$order = Order::with(array('serviceoffer'=>function($query){$query->select('_id','price');}))->whereIn('type',$type)->where(function ($query) { $query->orWhere('status',"1")->orWhere('abondon_status','bought_closed');})->where('coupon_code','exists',true)->get()->toArray();

			$fp = fopen('order_fitmania_coupon.csv', 'w');

			$header = array('Order ID','Coupon Code','Amount','Discounted Amount','Customer Name','Customer Email','Customer Number','Vendor ID','Vendor Name','Location');

			foreach ($order as $key => $value) 
			{
				

				if(!isset($value['coupon_code'])){
					$order[$key]['coupon_code'] = '';
				}

				if(!isset($value['serviceoffer']['price'])){
					$order[$key]['serviceoffer']['price'] = '';
				}

				if(!isset($value['amount'])){
					$order[$key]['amount'] = '';
				}

				if(!isset($value['customer_name'])){
					$order[$key]['customer_name'] = '';
				}

				if(!isset($value['customer_email'])){
					$order[$key]['customer_email'] = '';
				}

				if(!isset($value['customer_phone'])){
					$order[$key]['customer_phone'] = '';
				}

				if(!isset($value['finder_id'])){
					$order[$key]['finder_id'] = '';
				}

				if(!isset($value['finder_name'])){
					$order[$key]['finder_name'] = '';
				}

				if(!isset($value['finder_location'])){
					$order[$key]['finder_location'] = '';
				}	

			}

			fputcsv($fp, $header);
			
			foreach ($order as $value) {  
				

				$fields = array($value['_id'],$value['coupon_code'],$value['serviceoffer']['price'],$value['amount'],$value['customer_name'],$value['customer_email'],$value['customer_phone'],$value['finder_id'],$value['finder_name'],$value['finder_location']);

				fputcsv($fp, $fields);
			}

			fclose($fp);

			return 'done';


		}


		public function csvPaidTrial(){

			ini_set('memory_limit','2048M');

			$finder_id = Ratecard::where('direct_payment_enable','1')->lists('finder_id');

			$finders = Finder::active()->whereIn('_id',$finder_id)->with(array('location'=>function($query){$query->select('_id','name');}))->with(array('city'=>function($query){$query->select('_id','name');}))->with(array('category'=>function($query){$query->select('_id','name');}))->orderBy('_id', 'asc')->get()->toArray();

			$fp = fopen('finder_with_direct_payment_enable.csv', 'w');

			$header = array('Vendor ID','Vendor Name','Vendor City','Vendor Location','Category','Commercial Type');

			foreach ($finders as $key => $value) 
			{
				

				if(!isset($value['_id'])){
					$finders[$key]['_id'] = '';
				}

				if(!isset($value['title'])){
					$finders[$key]['title'] = '';
				}

				if(!isset($value['city']['name'])){
					$finders[$key]['city']['name'] = '';	
				}

				if(!isset($value['location']['name'])){
					$finders[$key]['location']['name'] = '';
				}

				if(!isset($value['category']['name'])){
					$finders[$key]['category']['name'] = '';
				}

				if(!isset($value['commercial_type_status'])){
					$finders[$key]['commercial_type_status'] = '';
				}

			}

			fputcsv($fp, $header);
			
			foreach ($finders as $value) {  

				$fields = array($value['_id'],$value['title'],$value['city']['name'],$value['location']['name'],$value['category']['name'],$value['commercial_type_status']);

				fputcsv($fp, $fields);
			}

			fclose($fp);

			return 'done';

		}


		public function freeSpecial(){


			$finder = Finder::with(array('category'=>function($query){$query->select('name');}))->with(array('location'=>function($query){$query->select('name');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->where('status',"1")->where('commercial_type',2)->get(array('_id','title','commercial_type','business_type','location_id','category_id','city_id'))->toArray();

			$fp = fopen('freeSpecialVendor.csv', 'w');

			$header = array('Vendor ID','Vendor Name','Category','City','Location');


			foreach ($finder as $key => $value) 
			{
				if(!isset($value['title'])){
					$finder[$key]['title'] = '';
				}

				if(!isset($value['city']['name'])){
					$finder[$key]['city']['name'] = '';
				}

				if(!isset($value['location']['name'])){
					$finder[$key]['location']['name'] = '';
				}

				if(!isset($value['category']['name'])){
					$finder[$key]['category']['name'] = '';
				}

				$finder[$key]['city']['name'] = ucwords($finder[$key]['city']['name']);
				$finder[$key]['location']['name'] = ucwords($finder[$key]['location']['name']);
				$finder[$key]['category']['name'] = ucwords($finder[$key]['category']['name']);

			}

			fputcsv($fp, $header);
			
			foreach ($finder as $value) {  
				

				$fields = array($value['_id'],$value['title'],$value['category']['name'],$value['city']['name'],$value['location']['name']);

				fputcsv($fp, $fields);
			}

			fclose($fp);

			return 'done';


		}

		public function membershipFitmania(){

			$array = array('elabali09@gmail.com','adv.kaushiq@gmail.com','shrutikanthakandali@gmail.com','anushri.churhat@gmail.com','shruti.gupta184@yahoo.com','shilpagoel23@gmail.com','aarushichauhan9@gmail.com','aquarian_asmita@yahoo.com','ankit.sethi236@gmail.com','sonali.david@gmail.com','sejalchoudhari@gmail.com','dhiraj14792@gmail.com','surbhivyas2907@gmail.com','nagpal129@gmail.com');

			$order = Order::whereIn('customer_email',$array)->get()->toArray();

			$fp = fopen('membership_fitmania.csv', 'w');

			$header = array('Order ID','Amount','Customer Name','Customer Email','Customer Number','Customer Address','Vendor ID','Vendor Name','Location');

			foreach ($order as $key => $value) 
			{
				
				if(!isset($value['amount'])){
					$order[$key]['amount'] = '';
				}

				if(!isset($value['address'])){
					$order[$key]['address'] = '';
				}


				if(!isset($value['customer_name'])){
					$order[$key]['customer_name'] = '';
				}

				if(!isset($value['customer_email'])){
					$order[$key]['customer_email'] = '';
				}

				if(!isset($value['customer_phone'])){
					$order[$key]['customer_phone'] = '';
				}

				if(!isset($value['finder_id'])){
					$order[$key]['finder_id'] = '';
				}

				if(!isset($value['finder_name'])){
					$order[$key]['finder_name'] = '';
				}

				if(!isset($value['finder_location'])){
					$order[$key]['finder_location'] = '';
				}	

			}

			fputcsv($fp, $header);
			
			foreach ($order as $value) {  
				

				$fields = array($value['_id'],$value['amount'],$value['customer_name'],$value['customer_email'],$value['customer_phone'],$value['address'],$value['finder_id'],$value['finder_name'],$value['finder_location']);

				fputcsv($fp, $fields);
			}

			fclose($fp);

			return 'done';


		}


		public function reviewAddress(){

			$capture = Capture::where('capture_type','reviewposted')->orderBy('_id', 'desc')->get()->toArray();

			$fp = fopen('review_to_win_address.csv', 'w');

			$header = array('Customer Name','Customer Mobile','Customer Address');

			foreach ($capture as $key => $value) 
			{
				
				if(!isset($value['name'])){
					$capture[$key]['name'] = '';
				}

				if(!isset($value['mobile'])){
					$capture[$key]['mobile'] = '';
				}

				if(!isset($value['address'])){
					$capture[$key]['address'] = '';
				}
			}

			fputcsv($fp, $header);
			
			foreach ($capture as $value) {  
				

				$fields = array($value['name'],$value['mobile'],$value['address']);

				fputcsv($fp, $fields);
			}

			fclose($fp);

			return 'done';


		}


		public function dumpNo(){

			$array = array('40','131','170','227','400','401','402','424','442','523','530','569','576','608','613','645','714','728','731','752','816','823','903','941','983','1020','1080','1140','1233','1259','1261','1263','1293','1295','1349','1376','1388','1413','1421','1422','1428','1431','1449','1450','1451','1452','1455','1456','1457','1458','1459','1460','1484','1486','1487','1488','1494','1523','1563','1579','1580','1581','1582','1584','1587','1602','1605','1606','1607','1611','1639','1646','1647','1669','1672','1739','1750','1814','1820','1824','1835','1845','1863','1867','1875','1878','1880','1887','1895','1935','1938','1939','1962','1985','2079','2157','2196','2198','2199','2200','2201','2207','2208','2216','2244','2297','2421','2425','2426','2427','2428','2628','2650','2701','2774','2777','2778','2806','2821','2824','2828','2833','2851','2852','2890','2997','3012','3105','3109','3296','3305','3344','3378','3382','3415','3416','3564','3612','3921','4029','4059','4142','4145','4168','4230','4315','4352','4417','4491','4534','4694','4767','4773','4823','4837','4929','5077','5500','5733','5895','5902','5950','5958','5965','6005','6010','6044','6126','6128','6149','6197','6210','6218','6229','6234','6268','6327','6329','6330','6332','6384','6387','6422','6426','6452','6496','6511','6531','6551','6574','6671','6686','6698','6896','6922','6923','6944','6969','6975','6976','7064','7150','5241','6241','14','927','1623','1719','1846','2025','2160','3807','4045','4048','4226','4681','5529','5560','5723','5851','5973','6019','6248','6310','6315','6348','6413','6419','6707');

			/*$destinationPath = public_path();
            $fileName = "finder_dump.csv";
            $filePath = $destinationPath.'/'.$fileName;

            $csv_to_array = $this->csv_to_array($filePath);

            if($csv_to_array){

                foreach ($csv_to_array as $key => $value) {

                    if($value['Vendor ID'] != ''){

                    	$finder = Finder::find((int) $value['Vendor ID']);

                    	if($finder){

                    		$contact = $finder->contact;

                    		$contact['phone'] = $value['Vendor Number'];

                    		$finder->contact = $contact;
                    		$finder->update();

                    		echo $finder->_id.' , ';

                    	}

                    }

                }
            }*/

        }

        public function csv_to_array($filename='', $delimiter=',')
        {
        	if(!file_exists($filename) || !is_readable($filename))
        		return FALSE;

        	$header = NULL;
        	$data = array();
        	if (($handle = fopen($filename, 'r')) !== FALSE)
        	{
        		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
        		{
        			if(!$header)
        				$header = $row;
        			else
        				$data[] = array_combine($header, $row);
        		}
        		fclose($handle);
        	}
        	return $data;
        }

        public function dumpMissedcallNo(){

			/*$missedcall_no = array(
					array('number'=>'+912233010068','type'=>'yes','batch'=>1),
					array('number'=>'+912233010069','type'=>'no','batch'=>1),
					array('number'=>'+912233010070','type'=>'reschedule','batch'=>1)
					);*/

$missedcall_no = array(

	array('number'=>'+912233010091','type'=>'like','batch'=>1,'for'=>'N+2Trial'),
	array('number'=>'+912233010092','type'=>'explore','batch'=>1,'for'=>'N+2Trial'),
	array('number'=>'+912233010093','type'=>'notattended','batch'=>1,'for'=>'N+2Trial'),

	array('number'=>'+912233010094','type'=>'like','batch'=>2,'for'=>'N+2Trial'),
	array('number'=>'+912233010095','type'=>'explore','batch'=>2,'for'=>'N+2Trial'),
	array('number'=>'+912233010096','type'=>'notattended','batch'=>2,'for'=>'N+2Trial'),

	array('number'=>'+912067083508','type'=>'like','batch'=>3,'for'=>'N+2Trial'),
	array('number'=>'+918030088462','type'=>'explore','batch'=>3,'for'=>'N+2Trial'),
	array('number'=>'+918030088463','type'=>'notattended','batch'=>3,'for'=>'N+2Trial'),

	array('number'=>'+912233010069','type'=>'renew','batch'=>1,'for'=>'OrderRenewal'),
	array('number'=>'+912233010070','type'=>'alreadyextended','batch'=>1,'for'=>'OrderRenewal'),
	array('number'=>'+912067083510','type'=>'explore','batch'=>1,'for'=>'OrderRenewal'),
	);

foreach ($missedcall_no as $key => $value) {

	$pk = Ozonetelmissedcallno::max('_id') + 1;
	$number = new Ozonetelmissedcallno($value);
	$number->_id 	= 	$pk;
	$number->save();

}	

}	

public function top10Finder(){

	ini_set('allow_url_fopen', 'On');
	ini_set('allow_url_include', 'On');


	$cities = array('1'=>'Mumbai','2'=>'Pune','3'=>'Banglore','4'=>'Delhi','8'=>'Gurgaon');

	$trialRequest = Booktrial::raw(function($collection){

		$aggregate = [];

		$match['$match']['finder_id']['$ne'] = 3305;

		$aggregate[] = $match;

		$group = array(
			'$group' => array(
				'_id' => array(
					'finder_id' => '$finder_id',
					'city_id'	=> '$city_id'
					),
				'count' => array(
					'$sum' => 1
					)
				)
			);

		$aggregate[] = $group;

		return $collection->aggregate($aggregate);

	});

	foreach ($trialRequest['result'] as $key => $value) {

		$request[$value['_id']['city_id']][$value['_id']['finder_id']] = $value['count'];
	}

	foreach ($request as $city_id => $finder) {

		arsort($request[$city_id]);

		$i = 1;

		foreach ($request[$city_id] as $finder_id => $count) {

			$vendor = Finder::find((int) $finder_id);

			$array['finder_id'] = $finder_id;
			$array['finder_name'] = ucwords($vendor->title);
			$array['city_id'] = $city_id;
			$array['city_name'] = $cities[$city_id];
			$array['count'] = $count;

			$hesh[] = $array;

			if($i == 10){
				break;
			}

			$i += 1;

		}
	}

	$fp = fopen('top10vendors.csv', 'w');

	$header = array('City Name','City ID','Vendor Name','Vendor ID','Booktrial Count');

	fputcsv($fp, $header);

	foreach ($hesh as $value) {  

		$fields = array($value['city_name'],$value['city_id'],$value['finder_name'],$value['finder_id'],$value['count']);

		fputcsv($fp, $fields);
	}

	fclose($fp);

	return 'done';


}


public function finderWithNoSchedule(){

	ini_set('memory_limit','2048M');
	ini_set('max_execution_time', 300);

	$services = Service::where('trialschedules','exists',true)->active()->get(array('trialschedules','finder_id'))->toArray();
	$finder = array();
	foreach ($services as $key => $value) {
		if(!empty($value['trialschedules'])){
			if(!isset($finder[$value['finder_id']]['ne'])){
				$finder[$value['finder_id']]['ne'] = 1;
			}else{
				$finder[$value['finder_id']]['ne'] += 1;
			}
		}else{
			if(!isset($finder[$value['finder_id']]['e'])){
				$finder[$value['finder_id']]['e'] = 1;
			}else{
				$finder[$value['finder_id']]['e'] += 1;
			}
		}
	}
	$hesh = array();
	foreach ($finder as $key => $value) {
		if(isset($value['e']) && !isset($value['ne']))
		{
			$hesh[] = $key;
		}
	}

	$category = array(42,45,40,25);

	$finders = Finder::whereIn('_id',$hesh)->whereNotIn('category_id',$category)->with(array('location'=>function($query){$query->select('_id','name');}))->with(array('city'=>function($query){$query->select('_id','name');}))->with(array('category'=>function($query){$query->select('_id','name');}))->orderBy('_id', 'asc')->get()->toArray();

	$fp = fopen('finder_with_no_schdule.csv', 'w');
	$header = array('Vendor ID','Vendor Name','Vendor City','Vendor Location','Category','Commercial Type','Status');

	foreach ($finders as $key => $value) 
	{

		if(!isset($value['_id'])){
			$finders[$key]['_id'] = '';
		}
		if(!isset($value['title'])){
			$finders[$key]['title'] = '';
		}
		if(!isset($value['city']['name'])){
			$finders[$key]['city']['name'] = '';
		}
		if(!isset($value['location']['name'])){
			$finders[$key]['location']['name'] = '';
		}
		if(!isset($value['category']['name'])){
			$finders[$key]['category']['name'] = '';
		}
		if(!isset($value['commercial_type_status'])){
			$finders[$key]['commercial_type_status'] = '';
		}

	}

	fputcsv($fp, $header);

	foreach ($finders as $value) {  

		$status = ($value['status'] == '1') ? 'Active' : 'Inactive';

		$fields = array($value['_id'],$value['title'],$value['city']['name'],$value['location']['name'],$value['category']['name'],$value['commercial_type_status'],$status);
		fputcsv($fp, $fields);
	}

	fclose($fp);

	return 'done';
}


public function finderStatus(){

	$homepage = Homepage::get()->toArray();

	$finder_status = array();
	$array = array();

	foreach ($homepage as $key => $value) {

		$array['footer_block1_ids'] = explode(',',$value['footer_block1_ids']);
		$array['footer_block2_ids'] = explode(',',$value['footer_block2_ids']);
		$array['footer_block3_ids'] = explode(',',$value['footer_block3_ids']);
		$array['footer_block4_ids'] = explode(',',$value['footer_block4_ids']);
		$array['footer_block5_ids'] = explode(',',$value['footer_block5_ids']);
		$array['gym_finders'] = explode(',',$value['gym_finders']);
		$array['yoga_finders'] = explode(',',$value['yoga_finders']);
		$array['zumba_finders'] = explode(',',$value['zumba_finders']);

		foreach ($array as $col) {
			foreach ($col as $finder_id) {

				if(!in_array($finder_id, $finder_status)){
					$finder_status[] = $finder_id;
				}
			}
		}

	}

	$array = array();

	$collection = Findercollection::get()->toArray();

	foreach ($collection as $key => $value) {

		$array['finder_ids'] = explode(',',$value['finder_ids']);

		foreach ($array['finder_ids'] as $finder_id) {

			if(!in_array($finder_id, $finder_status)){
				$finder_status[] = $finder_id;
			}
		}
	}

	$data = array();

	foreach ($finder_status as $finder_id) {

		if($finder_id != ''){

			$finder = Finder::with(array('location'=>function($query){$query->select('_id','name');}))->with(array('city'=>function($query){$query->select('_id','name');}))->with(array('category'=>function($query){$query->select('_id','name');}))->find((int) $finder_id);

			if($finder){

				$finder = $finder->toArray();

				$hesh['_id'] = $finder_id;
				$hesh['title'] = $finder['title'];
				$hesh['city_name'] = $finder['city']['name'];
				$hesh['location_name'] = $finder['location']['name'];
				$hesh['category_name'] = $finder['category']['name'];
				$hesh['commercial_type_status'] = $finder['commercial_type_status'];
				$hesh['status'] = ($finder['status'] == '1') ? 'Active' : 'Inactive';

			}else{

				$hesh['_id'] = $finder_id;
				$hesh['title'] = '';
				$hesh['city_name'] = '';
				$hesh['location_name'] = '';
				$hesh['category_name'] = '';
				$hesh['commercial_type_status'] = '';
				$hesh['status'] = 'Deleted';

			}

			$data[] = $hesh;
		}
	}

	$fp = fopen('finder_status.csv', 'w');

	$header = array('Vendor ID','Vendor Name','Vendor City','Vendor Location','Category','Commercial Type','Status');

	fputcsv($fp, $header);

	foreach ($data as $value) {  
		$fields = array($value['_id'],$value['title'],$value['city_name'],$value['location_name'],$value['category_name'],$value['commercial_type_status'],$value['status']);
		fputcsv($fp, $fields);
	}

	fclose($fp);

	return 'done';

}

public function testEmail(){

	$customermailer = new CustomerMailer();



		/*$template = Template::where('label','CustomerAutoTrail')->first();

		$data = array(
			'customer_name'=>'mahesh',
			"customer_phone"=>'9920864894',
			"customer_email" => "mjmjadhav@gmail.com",
			"booktrialid"	=> 1,
			"trial_type" => "test",
			"finder_name" => "Eight packs",
			"with_at"=>"at"
		);


		$email_template = 	$this->bladeCompile($template->email_text,$data);
		$bcc_emailids 	= 	array(Config::get('mail.to_mailus')) ;

		

		$message_data 	= array(
			'user_email' => array($data['customer_email']),
			'user_name' => $data['customer_name'],
			'bcc_emailids' => $bcc_emailids,
			'email_subject' => $this->bladeCompile($template->subject,$data)
		);

		$label = 'BookTrial-C';
		$priority = 1;*/

		return $customermailer->testEmail();




				//echo "<pre>";print_r($template);exit;

		//return View::make($template->email_text, $data)->render();


				//echo "<pre>";print_r($template->emai_text);exit;

	}

	public function bladeCompile($value, array $args = array())
	{
		$generated = \Blade::compileString($value);

		ob_start() and extract($args, EXTR_SKIP);

	    // We'll include the view contents for parsing within a catcher
	    // so we can avoid any WSOD errors. If an exception occurs we
	    // will throw it out to the exception handler.
		try
		{
			eval('?>'.$generated);
		}

	    // If we caught an exception, we'll silently flush the output
	    // buffer so that no partially rendered views get thrown out
	    // to the client and confuse the user with junk.
		catch (\Exception $e)
		{
			ob_get_clean(); throw $e;
		}

		$content = ob_get_clean();

		return $content;
	}


	public function findersHaveRatecardWithNoServices (){

		ini_set('memory_limit','2048M');
		ini_set('max_execution_time', 300);

		$headers = [
		'Content-type'        => 'application/csv',
		'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
		'Content-type'        => 'text/csv',
		'Content-Disposition' => 'attachment; filename=findersHaveRatecardWithNoServices.csv',
		'Expires'             => '0',
		'Pragma'              => 'public'
		];
		$output = "Vendor ID, Vendor Name, Vendor City , Vendor Location , Category , Commercial Type \n";
		$finders = Finder::with(array('location'=>function($query){$query->select('_id','name');}))
		->with(array('city'=>function($query){$query->select('_id','name');}))
		->with(array('category'=>function($query){$query->select('_id','name');}))
		->with(array('services'=>function($query){$query->select('_id','name');}))
		->active()
		->get(['_id','title','city_id','city','category_id','category','location_id','location','services','commercial_type','business_type','finder_type'])->toArray();
		if($finders){
			foreach ($finders as $value) {
				if(count($value['services']) < 1) {
					$commercial_type_arr    = array( 0 => 'free', 1 => 'paid', 2 => 'freespecial', 3 => 'cos');
					$business_type_arr      = array( 0 => 'noninfrastructure', 1 => 'infrastructure');
					$finder_type_arr        = array( 0 => 'free', 1 => 'paid');
					$commercial_type 	    = $commercial_type_arr[intval($value['commercial_type'])];
					$business_type 		    = $business_type_arr[intval($value['business_type'])];
					$vendor_type 		    = $finder_type_arr[intval($value['finder_type'])];
					$output .=  $value['_id'] .",". $value['title'] .",". $value['city']['name'] .",". $value['location']['name'] .",". $value['category']['name'] .",". $commercial_type ." \n";
				}
			}
		}
		return Response::make(rtrim($output, "\n"), 200, $headers);
	}


	public function paymentEnabledServices(){
		ini_set('max_execution_time', 30000);
		$city_list = array(1,2,3,4,8);
		//$city_list = array(1);
		$city_names = array('Mumbai', 'Pune',  'Banglore' ,'Delhi', 'Gurgaon');
		$fp = fopen('paymentEnabledServices.csv', 'w');
		foreach ($city_list as $city)
		{
			$finder_documents = Finder::where('status','1')->where('city_id', $city)
			->with('services')	
			->take(10000)		
			->orderBy('_id', 'asc')->get()->toArray();

			

			$header = array('FinderId','FinderName','ServiceId', 'ServiceName', 'CommercialType','City');


			fputcsv($fp, $header);

			foreach ($finder_documents as $finder) 
			{	

				$finder_commercial_type = '';

				switch ($finder['commercial_type']) {
					case 1:
					$finder_commercial_type = 'paid';
					break;
					case 2:
					$finder_commercial_type = 'free special';
					break;
					case 3:
					$finder_commercial_type = 'COS';
					break;
					case 0:
					$finder_commercial_type = 'free';
					break;

				}

				$finder_services = $finder['services'];
				foreach ($finder_services as $service) {

					
					$service_ratecards = Ratecard::where('service_id', '=',intval(
						$service['_id']))->get()->toArray();;
										//check if the finder is payment enabled type
					
					$ispaymentenabled = false;
					echo json_encode($service_ratecards).'</br>';
					foreach ($service_ratecards as $fr) {

						echo $fr['direct_payment_enable'].'</br>';						if($fr['direct_payment_enable'] === '1'){
							$ispaymentenabled = true;
							break;
						}
					}
					// echo $service['_id'].'</br>';
					if($ispaymentenabled){
						$cityname = '';
						switch ($city) {
							case 1:
							$cityname = 'mumbai';
							break;
							case 2:
							$cityname = 'pune';
							break;
							case 3:
							$cityname = 'banglore';
							break;
							case 4:
							$cityname = 'delhi';
							break;
							case 8:
							$cityname = 'gurgaon';
							break;
							
						}

						$fields = array($finder['_id'],$finder['title'],$service['_id'],$service['name'], $finder_commercial_type,$cityname);

						fputcsv($fp, $fields);

					}
				}						

			}
		}

		fclose($fp);

		return 'done';


	}

	public function BudgetAlgoFinders(){

		$fp = fopen('budgetrangeandvalue.csv', 'w');
		$header = array('FinderId','FinderName','Average_Budget', 'Budget_slab', 'CommercialType','Infrastructure_type','City');

		fputcsv($fp, $header);
		$city_list = array(1,2,3,4,8);
		foreach ($city_list as $key => $city) {

			$finder_documents = Finder::with(array('country'=>function($query){$query->select('name');}))
			->with(array('city'=>function($query){$query->select('name');}))               
			->active()
			->orderBy('_id')
			->where('city_id', intval($city))
			->where('status', '=', '1')
			->take(5000)->skip(0)
			->timeout(400000000)
			->get(); 


			foreach ($finder_documents as $finder) {
				
				$ratecards = Ratecard::where('finder_id', intval($finder['id']))->get();
				$ratecard_money = 0;
				$ratecard_count = 0;  $average_monthly = 0;

				foreach ($ratecards as $ratecard) {
					
					switch($ratecard['validity']){
						case 30:
						$ratecard_count = $ratecard_count + 1;
						$ratecard_money = $ratecard_money + intval($ratecard['price']);
						break;
						case 90:
						$ratecard_count = $ratecard_count + 1;
						$average_one_month = intval($ratecard['price'])/3;
						$ratecard_money = $ratecard_money + $average_one_month;
						break;
						case 120:
						$ratecard_count = $ratecard_count + 1;
						$average_one_month = intval($ratecard['price'])/4;
						$ratecard_money = $ratecard_money + $average_one_month;
						break;
						case 180:
						$ratecard_count = $ratecard_count + 1;
						$average_one_month = intval($ratecard['price'])/6;
						$ratecard_money = $ratecard_money + $average_one_month;
						break;
						case 360:
						$ratecard_count = $ratecard_count + 1;
						$average_one_month = intval($ratecard['price'])/12;
						$ratecard_money = $ratecard_money + $average_one_month;
						break;
					}  

				}

				if(($ratecard_count !==0)){

					$average_monthly = ($ratecard_money) / ($ratecard_count);
				}

				$average_monthly_tag = '';

				switch($average_monthly){
					case ($average_monthly < 1001):
					$average_monthly_tag = 'one';
					$rangeval = 1;
					break;

					case ($average_monthly > 1000 && $average_monthly < 2501):
					$average_monthly_tag = 'two';
					$rangeval = 2;
					break;

					case ($average_monthly > 2500 && $average_monthly < 5001):
					$average_monthly_tag = 'three';
					$rangeval = 3;
					break;

					case ($average_monthly > 5000 && $average_monthly < 7501):
					$average_monthly_tag = 'four';
					$rangeval = 4;
					break;

					case ($average_monthly > 7500 && $average_monthly < 15001):
					$average_monthly_tag = 'five';
					$rangeval = 5;
					break;

					case ($average_monthly > 15000):
					$average_monthly_tag = 'six';
					$rangeval = 6;
					break;
				}
				$finder_commercial_type = '';

				switch ($finder['commercial_type']) {
					case 1:
					$finder_commercial_type = 'paid';
					break;
					case 2:
					$finder_commercial_type = 'free special';
					break;
					case 3:
					$finder_commercial_type = 'COS';
					break;
					case 0:
					$finder_commercial_type = 'free';
					break;

				}
				$cityname = '';
				switch ($city) {
					case 1:
					$cityname = 'mumbai';
					break;
					case 2:
					$cityname = 'pune';
					break;
					case 3:
					$cityname = 'banglore';
					break;
					case 4:
					$cityname = 'delhi';
					break;
					case 8:
					$cityname = 'gurgaon';
					break;

				}
				$finder_infrastructuretype = $finder['business_type'];
				$fields = array($finder['_id'],$finder['title'],$average_monthly,$average_monthly_tag, $finder_commercial_type,$finder_infrastructuretype,$cityname);
				fputcsv($fp, $fields);
				

			}

		}
		
		

		return 'done';

	}


	public function manualtrialdisable(){

		$yes_vendors = array(
			328,569,731,752,825,987,988,1293,1295,1505,1770,1938,6299,7449,7494,7544,
			7585,
			7603,
			7616,
			7643,
			7651,
			7663,
			7716,
			7722,
			7746,
			7771,
			7792,
			7812,
			7818,
			7833,
			7840,
			7847,
			7868,
			7880,
			7897,
			7918,
			7920,
			7922,
			7938,
			7943,
			8097,
			8098,
			8102
			);

		$no_vendors = array(
			6656,
			7458,
			7459,
			7513,
			7521,
			7523,
			7524,
			7553,
			7571,
			7586,
			7612,
			7641,
			7649,
			7657,
			7668,
			7757,
			7786,
			7805,
			7855,
			7867,
			7870,
			7871,
			7872,
			7874,
			7879,
			7883,
			7890,
			7895,
			7898,
			7909,
			7913,
			7915,
			7933,
			7937,
			7940,
			7941,
			7944,
			7947,
			7954,
			7992,
			8052,
			8058,
			8077,
			8083,
			8087,
			8093,
			8107
			);


		$yes_finders = Finder::whereIn('_id', $yes_vendors)->where('status', '1')->get();

		$no_finders = Finder::whereIn('_id', $no_vendors)->where('status','1')->get();

		foreach ($yes_finders as $key => $finder) {
				$finderdata = array();
				array_set($finderdata, 'manual_trial_enable', '1');
				$finder->update($finderdata);
		}

		foreach ($no_finders as $key => $finder) {
				$finderdata = array();
				array_set($finderdata, 'manual_trial_enable', '0');
				$finder->update($finderdata);
		}

	}

	public function renewalSmsStatus(){

		$total_sms = Order::where('customer_sms_renewal','exists',true)->count();

		$match = array();

        $orderStatus = Order::raw(function($collection) use ($match){

            $aggregate = [];

            $match['$match']['missedcall_renew_status']['$exists'] = true;

            $aggregate[] = $match;

            $group = array(
                        '$group' => array(
                            '_id' => array(
                                'status' => '$missedcall_renew_status'
                            ),
                            'count' => array(
                                '$sum' => 1
                            )
                        )
                    );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

        $request = array();

        $request['total_sms'] = $total_sms;

        if(isset($orderStatus['result'])){

	        foreach ($orderStatus['result'] as $key => $value) {

	            $request[$value['_id']['missedcall_renew_status']] = $value['count'];

	        }
	    }

       	echo "<pre>";print_r($request);exit;

    }


    public function deleteId(){

    	$orders = Order::where('type','!=','memberships')->where(function($query){$query->orwhere('customer_sms_after3days','exists',true)->orwhere('customer_email_after10days','exists',true)->orWhere('customer_email_renewal','exists',true)->orWhere('customer_sms_renewal','exists',true);})->get(array('_id','customer_sms_after3days','customer_email_after10days','customer_email_renewal','customer_sms_renewal'));

    	if(!empty($orders)){

    		$orders = $orders->toArray();
    		$id = array();
    		$array = array('customer_sms_after3days','customer_email_after10days','customer_email_renewal','customer_sms_renewal');

    		foreach ($orders as $value) {

    			foreach ($array as $key) {

    				if(isset($value[$key]) && $value[$key] != "")
    				{
    					$id[] = $value[$key];
    				}
    			}
    		}
    	}

    	$sidekiq = new Sidekiq();

    	if(!empty($id)){

    		$sidekiq->delete($id);
    		echo "<pre>";print_r($id);

    	}else{

    		echo "no record found";
    	}

    }

	public function getbrandId($brand_name){

		$brand = Brand::where('name',$brand_name)->first(array('_id'));

		if($brand){
			$brand_id = $brand['_id'];
		}
		else{
			$data = array('name'=>$brand_name,'status'=>'1');
			$insertedid = Brand::max('_id') + 1;
			$brand       =   new Brand($data);
			$brand_id = $brand->_id  =   $insertedid;
			$brand->save();
		}

		return $brand_id;

	}
	
	public function updateBrandToFindersFromCSV(){

		// Schema::dropIfExists('brands');

		Finder::where('brand_id','exists',true)->unset('brand_id');

		$filePath = base_path('public/brands.csv');
		// var_dump($filePath);exit();
		$data = $this->csv_to_array($filePath);
		foreach($data as $row){

			$brand_name = trim($row['Brand']);

			if(isset($brand_name) && ($brand_name != '')){

				$brand_id =  $this->getbrandId($brand_name);
				$finder = Finder::find((int) $row['Vendor ID']);
				if(isset($finder)){
					$finder->update(array('brand_id'=> $brand_id));
				}
			}

		}

	}

	function cleartrip(){

		$finder_id = array(878,8852,1,5023,1020,6738,3196,7344,941,2443,6461,6466,6707,7434,7428,3619,6933,3618,3620,1698,2109,6668,3622,7021,5609,4808,5066,6548,5374,1445,1501,6532,5191,2421,3248,1908,6945,3775,6948,6947,3777,3774,1522,8867,6049,3378,1989,417,4777,2119,7458,1906,2743,5040,1330,7875,217,1495,5200,8821,7335,5728,5747,5745,8871,6250,5746,5748,1484,7909,8823,6137,7037,7898,3193,7035,5125,7145,228,1242,4742,2818,7056,6477,9112,2160,5586,4974,3803,7119,6949,3667,1690,6451,3380,3200,3235,8761,3987,4006,4007,3876,7498,2860,7386,7371,6216,442,2684,1934,6134,1750,6414,129,570,2785,2813,171,2881,6408,6885,5204,7001,6082,2501,2386,8847,5041,7136,119,6498,6664,4119,6543,6749,1968,667,5043,2050,1490,7262,7041,7010,6297,6005,2609,7450,4159,7448,5934,5931,6289,7451,7212,7120,3784,1140,7383,2140,575,1885,5970,7340,5629,5709,7649,5303,6796,1866,530,6462,6984,3239,4847,6125,1427,4141,862,1927,3416,4839,3074,7215,5310,1842,7433,5958,4491,5956,5950,5957,5959,3417,1801,6417,1764,7571,6453,4534,2044,4650,6686,6227,3186,6212,6081,613,6534,8546,7360,6993,6985,7006,8729,7441,6988,6991,7017,6999,7418,7870,8648,8741,8666,7020,8647,6995,8731,7872,8646,5986,3229,3228,1485,7046,813,4088,4291,2006,5717,1618,2992,8613,8615,8598,4855,1751,4853,7156,2165,1816,4705,2292,7135,6208,5148,6047,4846,7409,6909,7343,5817,1846,6419,7376,6898,5900,40,612,97,6479,5603,6503,6446,2995,6207,547,1041,296,2739,596,1873,4111,5027,7322,5892,5928,6329,4043,2432,1827,8469,6557,7147,6990,6598,2861,1946,8534,1514,4175,4021,7442,7214,2126,1883,1752,5404,1803,5029,1837,147,7324,7321,6941,7323,3172,3191,7317,75,1414,2451,6189,7036,6759,3184,7130,8467,2799,7058,6437,695,714,1413,3429,5082,6213,998,1425,4916,1518,6502,1711,7443,4029,6570,4059,4841,647,3634,7157,999,7146,1720,4878,5353,7123,2002,5727,4840,6632,7348,7351,6709,5893,5883,5882,5878,5880,5156,4115,6513,7405,7387,7401,7398,2021,6576,5979,8910,1650,6997,1388,2263,288,292,1389,6054,4520,6002,2973,4212,6144,6907,2252,6870,7028,7033,4073,6541,5347,1219,6426,8842,1257,1860,579,5742,5743,5681,1263,1874,5502,4823,5741,4822,4820,2105,4826,1260,1261,4817,1259,1233,1876,6525,5744,1266,4818,5750,6593,7355,6530,1262,1875,4819,4645,3977,6168,3176,307,736,2435,753,7014,4180,7330,4041,7325,7318,7541,7220,6506,5573,4964,3919,5111,7436,3757,7148,7696,1154,1732,4344,6422,6633,4489,4682,5444,7012,7013,3716,1664,4179,3972,8449,5597,554,7011,4173,2117,6964,6943,6950,7331,6768,7338,6756,7947,7954,7140,6475,5264,6234,7408,5968,2474,329,7372,6818,7357,6469,1258,5570,5585,5601,2257,552,6730,7213,621,1027,424,8908,5348,6603,2093,7314,6160,4767,3197,4183,7349,1473,7151,7521,1676,6167,1576,1895,1754,883,61,8861,3953,5961,5383,224,1642,6233,1516,6972,5313,6352,6349,4217,1855,3322,7054,3443,4254,3551,7121,3289,4193,1971,2723,1068,6139,7025,6656,6131,1395,143,7024,4659,7786,5070,8785,7345,6440,6472,5149,8352,4929,7003,22,1739,7341,1215,1428,6219,881,1622,3387,1984,5769,5833,7668,4875,2217,7757,1935,889,693,6319,699,4397,718,6468,3449,1905,7913,6680,590,2424,7294,4763,3499,698,3254,1613,6916,380,6914,4185,7224,4845,4607,1969,8021,449,4267,4928,7446,4587,7805,1786,1863,1865,841,6411,5635,3369,6591,7361,7366,6415,6904,7415,7417,1380,1828,3927,5084,7032,2155,5028,6209,5318,3907,3904,3905,3901,5909,4834,2159,824,6490,6259,6452,625,563,4677,2004,1026,1040,7319,1038,4749,8657,1510,2035,7064,1584,1580,6893,1602,6890,1581,1582,1604,1393,2236,1579,2235,2244,1605,6891,1583,1606,1392,1607,3843,4281,7540,7030,4680,4679,4678,303,6820,7431,739,8332,3579,6222,2013,1892,7150,2281,6589,7553,8797,8795,3464,8898,671,1424,3989,6806,6218,4772,4901,5133,6566,6574,6978,1928,3105,6895,179,3279,3929,6394,5031,5898,5373,927,1981,6397,8077,561,7523,7524,6938,1656,2864,4811,6587,4315,6670,6821,7310,5721,6220,6316,2640,5327,4082,5275,7177,6460,616,7061,5047,2145,6963,8125,3612,4211,3173,3557,7421,2707,5885,1818,6671,1523,4385,408,7874,4013,4255,6876,6489,4458,6128,4913,6908,6910,1962,7823,7869,2169,6939,3800,1824,3346,3345,3342,3233,3336,3204,3201,3330,3192,3335,7081,3341,2890,6594,7111,3347,608,5736,3332,3178,5739,7116,7106,5964,3343,8878,5566,5737,8872,3183,5735,3179,7114,6254,5738,3331,6291,6901,4032,6118,2665,1915,7158,7313,7381,7378,3614,4034,7641,351,4949,7462,3519,1939,2242,6333,7031,1437,3681,3384,3372,6126,975,1891,1820,2411,7143,3985,3970,2736,5402,7205,7211,2148,1013,2886,4035,6044,5145,5268,6874,4050,8763,3382,6230,5079,5477,1630,2663,635,4837,7047,3109,4602,3451,8859,4968,2378,877,2388,8158,4484,341,7154,1766,7266,7279,7273,4098,4099,7267,4371,7029,7308,7309,7315,7204,6501,1799,1623,3195,8120,7190,900,6064,6560,6578,4486,6141,7374,6753,8058,7883,8895,2137,2183,6058,3552,7878,6905,6412,5889,6602,5842,7356,6624,6317,6564,5884,5887,5890,6162,5014,1667,2009,6009,966,8447,8470,7009,3856,7444,7008,1677,6377,4307,7336,14,1673,5617,6241,5241,6518,1031,6140,1209,7162,1756,6499,691,5723,6223,6021,3654,2677,3680,6022,3679,2678,3678,7389,6083,5740,4784,1829,2209,3495,7447,4209,4773,7149,4876,5545,7362,7365,6694,2897,5387,6456,6504,7612,7305,7657,5655,4198,6942,6151,2867,6650,6961,3360,1853,4884,1645,3921,6644,5331,6511,6894,4581,3209,1712,7437,7359,7429,7375,3012,3980,861,926,2240,4768,5362,1671,2823,6794,3,1609,1024,3491,7307,823,827,3702,9,3508,3863,6481,7299,7172,3617,645,4836,5119,4996,7133,1069,905,4226,2865,6697,602,6932,3149,8361,3129,5216,7129,1349,6248,188,6563,7174,6185,6567,6974,8932,166,6266,1332,1554,1706,1705,1034,1035,4585,1029,5045,1030,1870,7407,1033,7301,3609,4518,6245,3804,3350,3351,3291,1682,2131,2459,8123,6784,2107,2309,6138,1771,7842,1493,7459,6136,5939,7022,6143,1986,1783,3160,8519);

		$finder = Finder::whereIn('_id',$finder_id)->get(array('_id','title','finder_vcc_email'))->toArray();

		foreach ($finder as $data){

			$response  = $this->findermailer->cleartrip($data);

			echo $data['title'].'-'.$response;

		}

	}

	function monsoonSale(){

		$finder = Finder::active()->whereIn('commercial_type',array(1,2,3))->get(array('_id','title','finder_vcc_email','finder_vcc_mobile'))->toArray();

		$numbers = array();

		foreach ($finder as $data){

			if(isset($data['finder_vcc_email']) && $data['finder_vcc_email'] != ""){

				$this->findermailer->monsoonSale($data);
			}

			if(isset($data['finder_vcc_mobile']) && $data['finder_vcc_mobile'] != ""){

				$array = explode(",", $data['finder_vcc_mobile']);

				foreach ($array as $value) {

					$numbers[] = $value;
				}
				
			}
			
		}

		$contact_nos = array_chunk($numbers,400);

		$return = array();

		foreach ($contact_nos as $contact_no) {

			$sms['sms_type'] = 'transactional';
			$sms['contact_no'] = $contact_no;
			$sms['message'] = "Hi, We are running a monsoon sale campaign starting from 15th July 2016. In order to participate and give exciting offers for this, refer to your registered email for further details or call us on - +919769361661 - Team Fitternity";

			$bulkSms = new Bulksms();

			$return[] = $bulkSms->send($sms);
		}

		echo "<pre>";print_r($return);exit;

	}

	function deactivateOzonetelDid(){

		$phone_number = array('911166765187','911166765188','911166765189','911166765190','911166765192','911166765193','911166765194','911166765195','911166765247');

		$ozonetel_no = Ozonetelno::active()->whereIn('phone_number',$phone_number)->where('city','DEL')->where('type','free')->where('finder_id','exists',false)->update(array('status'=>'0'));

		return $ozonetel_no;

	}

	public function unsetVipTrial(){

		ini_set('memory_limit','512M');
		ini_set('max_execution_time', 300);

		$finder_id = array(1,2443,941,6466,1698,987,1501,2421,6049,417,1495,1484,9111,1242,2818,4742,7498,984,442,6134,1750,6414,171,1309570,2501,2386,5043,7041,7456,1490,7728,6289,7451,1496,862,1427,4141,7215,4534,6081,613,7376,7656,1041,1873,147,1518,647,1388,292,1219,1259,1260,1262,1263,1266,1233,1257,1261,7696,6422,1664,1421,329,5570,5585,424,6603,1676,7054,1068,7024,328,2806,2821,2824,2828,2833,2844,2848,1739,7896,1215,1215,881,6468,8021,6259,6452,7319,1026,1040,7319,1038,1510,1392,1393,1579,1580,1581,1583,1584,1602,1604,1605,1606,1607,2235,2236,2244,6891,6893,6890,1582,4680,4679,4678,3579,3105,179,5898,561,7421,608,3382,1630,3451,1766,7878,1667,6009,1677,7444,6377,1756,2209,4773,5387,6151,1671,7724,827,1029,1033,1554,1705,1706,7407,1682,1493,1965,1986,6129,1771,1783,1691);

		$service = Service::whereNotIn('finder_id',$finder_id)->where('vip_trial','exists',true)->unset('vip_trial');

		echo "<pre>";print_r($service);exit;

    }

	public function addManualTrialAutoFlag($finder_ids = null){
		if(!isset($finder_ids)){
			$finder_ids = Config::get('app.manual_trial_auto_finderids');
		}
		$finder_ids = array_map('intval',$finder_ids);
		Vendor::whereIn('_id',$finder_ids)->update(['manual_trial_auto'=>true]);
		Finder::whereIn('_id',$finder_ids)->update(['manual_trial_auto'=>'1']);
		echo "done";return;
	}

	public function removePersonalTrainerStudio(){

		$reward_id = Reward::where("reward_type","healthy_snacks")->lists("_id");

		// $reward = Reward::where("reward_type","personal_trainer_at_studio")->delete();

		// $reward_category = Rewardcategory::where("reward_type","personal_trainer_at_studio")->delete();

		foreach ($reward_id as $r_id){

			$reward_offer = Rewardoffer::where("rewards",$r_id)->get();

			if(count($reward_offer) > 0){

				foreach ($reward_offer as $key => $value){

					$rewards = $value->rewards;

					foreach ($rewards as $rewards_key => $rewards_value){

						if($r_id == $rewards_value){
							unset($rewards[$rewards_key]);
						}

					}

					$rewards = array_values($rewards);

					$value->rewards = $rewards;
					$value->update();

				}
			}
		}
		
		echo "done";
	}


	public function latLonSwap(){
		
		try{

			ini_set('memory_limit', '-1');
        	ini_set('max_execution_time', 3000);

			$offset = 0;
			$limit = 10;

			$finders = $this->finderQuery($offset,$limit);

			while(count($finders) != 0){

				foreach ($finders as $key => $finder) {

					ini_set('set_time_limit', 30);

					$lat = (float)$finder->lat;
					$lon = (float)$finder->lon;

					if($lat > 60){
						$finder->lat = (string)$lon;
						$finder->lon = (string)$lat;
					}

					$finder->latlon_change = true;
					$finder->update();

				}

				$offset = $offset + 10;

				$finders = $this->finderQuery($offset,$limit);

			}

			$return = array('status'=>'done');

		}catch(Exception $exception){

			$message = array(
            	'type'    => get_class($exception),
               	'message' => $exception->getMessage(),
               	'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            );

            Log::error($exception);

			$return = array('status'=>'fail','error_message'=>$message);
		}

		//Finder::where('latlon_change','exists',true)->unset('latlon_change');

		print_r($return);

	}


	public function finderQuery($offset,$limit){

		$finders = Finder::where('lat','exists',true)->where('lat','!=',"")->where('lon','!=',"")->where('latlon_change','exists',false)->skip($offset)->take($limit)->get(array("_id","lat","lon","latlon_change"));

		//dd(DB::getQueryLog());

		return $finders;
	}

	public function latLonSwapApi(){
		
		try{

			ini_set('memory_limit', '-1');
        	ini_set('max_execution_time', 3000);

			$offset = 0;
			$limit = 10;

			$finders = $this->finderQueryApi($offset,$limit);

			while(count($finders) != 0){

				foreach ($finders as $key => $finder) {

					$lat = (float)$finder->geometry['coordinates'][0];
					$lon = (float)$finder->geometry['coordinates'][1];

					if($lat > 60){

						$lat_new = (string)$lon;
						$lon_new = (string)$lat;

						$geometry = array(
							"type" => "Point",
							"coordinates" => array($lat_new,$lon_new)
						);

						$finder->geometry = $geometry;
						
					}

					$finder->latlon_change = true;
					$finder->update();

				}

				$offset = $offset + 10;

				$finders = $this->finderQueryApi($offset,$limit);

			}

			$return = array('status'=>'done');

		}catch(Exception $exception){

			$message = array(
            	'type'    => get_class($exception),
               	'message' => $exception->getMessage(),
               	'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            );

            Log::error($exception);

			$return = array('status'=>'fail','error_message'=>$message);
		}

		//Vendor::where('latlon_change','exists',true)->unset('latlon_change');

		print_r($return);

	}


	public function finderQueryApi($offset,$limit){

		$finders = Vendor::where('geometry','exists',true)->where('latlon_change','exists',false)->skip($offset)->take($limit)->get(array("_id","geometry","latlon_change"));

		//dd(DB::getQueryLog());

		return $finders;
	}

	public function latLonSwapService(){
		
		try{

			ini_set('memory_limit', '-1');
        	ini_set('max_execution_time', 3000);

			$offset = 0;
			$limit = 10;

			$services = $this->serviceQuery($offset,$limit);

			while(count($services) != 0){

				foreach ($services as $key => $service) {

					ini_set('set_time_limit', 30);

					$lat = (float)$service->lat;
					$lon = (float)$service->lon;

					if($lat > 60){
						$service->lat = (string)$lon;
						$service->lon = (string)$lat;
					}

					$service->latlon_change = true;
					$service->update();

				}

				$offset = $offset + 10;

				$services = $this->serviceQuery($offset,$limit);

			}

			$return = array('status'=>'done');

		}catch(Exception $exception){

			$message = array(
            	'type'    => get_class($exception),
               	'message' => $exception->getMessage(),
               	'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            );

            Log::error($exception);

			$return = array('status'=>'fail','error_message'=>$message);
		}

		//Service::where('latlon_change','exists',true)->unset('latlon_change');

		print_r($return);

	}


	public function serviceQuery($offset,$limit){

		$services = Service::where('lat','exists',true)->where('lat','!=',"")->where('lon','!=',"")->where('latlon_change','exists',false)->skip($offset)->take($limit)->get(array("_id","lat","lon","latlon_change"));

		//dd(DB::getQueryLog());

		return $services;
	}

	public function latLonSwapServiceApi(){
		
		try{

			ini_set('memory_limit', '-1');
        	ini_set('max_execution_time', 3000);

			$offset = 0;
			$limit = 10;

			$services = $this->serviceQueryApi($offset,$limit);

			while(count($services) != 0){

				foreach ($services as $key => $service) {

					$lat = (double)$service->geometry['coordinates'][0];
					$lon = (double)$service->geometry['coordinates'][1];

					if($lat > 60){

						$lat_new = (double)$lon;
						$lon_new = (double)$lat;

						$geometry = array(
							"type" => "Point",
							"coordinates" => array($lat_new,$lon_new)
						);

						$service->geometry = $geometry;
						
					}

					$service->latlon_change = true;
					$service->update();

				}

				$offset = $offset + 10;

				$services = $this->serviceQueryApi($offset,$limit);

			}

			$return = array('status'=>'done');

		}catch(Exception $exception){

			$message = array(
            	'type'    => get_class($exception),
               	'message' => $exception->getMessage(),
               	'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            );

            Log::error($exception);

			$return = array('status'=>'fail','error_message'=>$message);
		}

		//Vendorservice::where('latlon_change','exists',true)->unset('latlon_change');

		print_r($return);

	}


	public function serviceQueryApi($offset,$limit){

		$services = Vendorservice::where('geometry','exists',true)->where('latlon_change','exists',false)->skip($offset)->take($limit)->get(array("_id","geometry","latlon_change"));

		//dd(DB::getQueryLog());

		return $services;
	}

	public function addExpiryDate(){

		try{

			ini_set('memory_limit', '-1');
        	ini_set('max_execution_time', 3000);

			$offset = 0;
			$limit = 50;

			/*$dates = array('customofferorder_expiry_date','customofferorder_validity','customofferorder_flag');

			foreach ($dates as $key => $value) {

				Booktrial::where('customofferorder_flag','exists',true)->unset($value);
			}

			exit;*/

			//Booktrial::where('customofferorder_flag','exists',true)->unset('customofferorder_flag');

			//exit;

			$booktrials = $this->booktrialQuery($offset,$limit);

			while(count($booktrials) != 0){

				foreach ($booktrials as $key => $booktrial) {

					$customofferorder   =   Fitapicustomofferorder::find($booktrial['customofferorder_id']);

	                if(isset($customofferorder->validity) && $customofferorder->validity != ""){

	                    $booktrial->customofferorder_expiry_date =   date("Y-m-d h:i:s", strtotime("+".$customofferorder->validity." day", strtotime($customofferorder->created_at)));
	                    $booktrial->customofferorder_validity = $customofferorder->validity;
	               
	                }	

	                $booktrial->customofferorder_new_flag = "1";

					$booktrial->update();
				}

				$offset = $offset + 50;

				$booktrials = $this->booktrialQuery($offset,$limit);

			}

			$return = array('status'=>'done');

		}catch(Exception $exception){

			$message = array(
            	'type'    => get_class($exception),
               	'message' => $exception->getMessage(),
               	'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            );

            Log::error($exception);

			$return = array('status'=>'fail','error_message'=>$message);
		}

		print_r($return);

	}

	public function booktrialQuery($offset,$limit){

		$booktrials = Booktrial::where('customofferorder_id','exists',true)->where('customofferorder_new_flag','exists',false)->orderBy('update_at','desc')->skip($offset)->take($limit)->get(array("_id","customofferorder_id","customofferorder_expiry_date","schedule_date_time","customofferorder_new_flag"));

		return $booktrials;
	}


	public function booktrialRaw($next_month,$start_month,$year,$removecustomers = [],$includeonlythese = []){

		return $trialRequest = Booktrial::raw(function($collection) use ($next_month,$start_month,$year,$removecustomers,$includeonlythese){

				$aggregate = [];
				$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("1-".$start_month."-".$year))));
				$match['$match']['created_at']['$gt'] = $from_date;

				if($next_month != -1){
					$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("30-".$next_month."-".$year))));
					$match['$match']['created_at']['$lte'] = $to_date;
				}
				if(count($includeonlythese) > 0){
					$match['$match']['customer_email']['$in'] = $includeonlythese;	
				}
				$match['$match']['customer_email']['$nin'] = $removecustomers;

				$aggregate[] = $match;

				$group = array(
					'$group' => array(
						'_id' => array(
							'customer_email' => '$customer_email',
							),
						'count' => array(
							'$sum' => 1
							)
						)
					);

				$aggregate[] = $group;
				$aggregate[] = array('$sort' => array('count'=> -1));
				return $collection->aggregate($aggregate);

			});
	}





	public function orderRaw($next_month,$start_month,$year,$removecustomers = [],$includeonlythese = []){

		return $trialRequest = Order::raw(function($collection) use ($next_month,$start_month,$year,$removecustomers,$includeonlythese){

				$aggregate = [];
				$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("1-".$start_month."-".$year))));
				$match['$match']['created_at']['$gt'] = $from_date;

				if($next_month != -1){
					$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("30-".$next_month."-".$year))));
					$match['$match']['created_at']['$lte'] = $to_date;
				}
				if(count($includeonlythese) > 0){
					$match['$match']['customer_email']['$in'] = $includeonlythese;	
				}
				$match['$match']['customer_email']['$nin'] = $removecustomers;

				$aggregate[] = $match;

				$group = array(
					'$group' => array(
						'_id' => array(
							'customer_email' => '$customer_email',
							),
						'count' => array(
							'$sum' => 1
							)
						)
					);

				$aggregate[] = $group;
				$aggregate[] = array('$sort' => array('count'=> -1));
				return $collection->aggregate($aggregate);

			});
	}


	public function customertrials($year, $division){
		$cycle = 12/$division;
		$start_month = 1;
		$monthwise = array();
		for($i = 0; $i < $division; $i++){
			$next_month = $start_month + ($cycle - 1);
			// $customers = 
			$trialRequest = $this->booktrialRaw($next_month,$start_month,$year);	
				$start_month = $next_month+1;
				array_push($monthwise,count($trialRequest['result']));
		}
		return $monthwise;
	}


	


	public function customertrialsrepeat($year, $division){
		$cycle = 12/$division;
		$monthwise = array();
		$avgwise = array();
		$removecustomers = array();
		$years = ["2015","2016"];
		foreach($years as $year){
			$start_month = 1;
			for($i = 0; $i < $division; $i++){
				$next_month = $start_month + ($cycle - 1);
				// $customers = 
				$trialRequest = $this->booktrialRaw($next_month,$start_month,$year,$removecustomers);	
				
				$customersinthissegment = array_fetch($trialRequest['result'],"_id.customer_email");
				array_push($removecustomers,$customersinthissegment);
				$thisCustomerSegment = $this->booktrialRaw(-1,$start_month,$year,array(),$customersinthissegment);
				$thisCustomerSegment["start_date"] = "1-0".$start_month."-".$year;
				$start_month = $next_month+1;
				// array_push($monthwise,array_sum(array_fetch($thisCustomerSegment['result'],"count")));
				$customersWithMoreThanOneTrial = 0;
				$totalTrialCustomerForMoreThanOneTrial = 0;
				foreach($thisCustomerSegment['result'] as $res){
					if($res["count"] > 1){
						$customersWithMoreThanOneTrial++;
						$totalTrialCustomerForMoreThanOneTrial += $res["count"];
					}
				}
				array_push($monthwise,$customersWithMoreThanOneTrial);
				array_push($avgwise,($totalTrialCustomerForMoreThanOneTrial/$customersWithMoreThanOneTrial));
			}
		}
		return array("monthwise" => $monthwise, "avgwise" => $avgwise);
	}


	public function customerorders($year, $division){
		$cycle = 12/$division;
		$monthwise = array();
		$years = ["2015","2016"];
		foreach($years as $year){
			$start_month = 1;
			
		for($i = 0; $i < $division; $i++){
				$next_month = $start_month + ($cycle - 1);
				// $customers =
				$trialRequest = $this->orderRaw($next_month,$start_month,$year); 
				if($i == 1 && $year == "2016"){
					return $trialRequest = $this->orderRaw($next_month,$start_month,$year);
				exit;
				}
					// echo $start_month. " - ".$next_month." * ".$year. " ";
					$start_month = $next_month+1;
					array_push($monthwise,count($trialRequest['result']));
			}
		}
		return $monthwise;
	}


	public function customerordersrepeat($year, $division){
		$cycle = 12/$division;
		$monthwise = array();
		$avgwise = array();
		$removecustomers = array();
		$years = ["2015","2016"];
		foreach($years as $year){
			$start_month = 1;
			for($i = 0; $i < $division; $i++){
				$next_month = $start_month + ($cycle - 1);
				// $customers = 
				$trialRequest = $this->orderRaw($next_month,$start_month,$year,$removecustomers);	
				return $trialRequest['result'];
				exit;
				$customersinthissegment = array_fetch($trialRequest['result'],"_id.customer_email");
				array_push($removecustomers,$customersinthissegment);
				$thisCustomerSegment = $this->orderRaw(-1,$start_month,$year,array(),$customersinthissegment);
				
				$thisCustomerSegment["start_date"] = "1-0".$start_month."-".$year;
				$start_month = $next_month+1;
				// array_push($monthwise,array_sum(array_fetch($thisCustomerSegment['result'],"count")));
				$customersWithMoreThanOneTrial = 0;
				$totalTrialCustomerForMoreThanOneTrial = 0;
				foreach($thisCustomerSegment['result'] as $res){
					if($res["count"] > 1){
						$customersWithMoreThanOneTrial++;
						$totalTrialCustomerForMoreThanOneTrial += $res["count"];
					}
				}
				array_push($monthwise,$customersWithMoreThanOneTrial);
				array_push($avgwise,($totalTrialCustomerForMoreThanOneTrial/$customersWithMoreThanOneTrial));
			}
		}
		return array("monthwise" => $monthwise, "avgwise" => $avgwise);
	}



	public function topBooktrial($from,$to){

		return $trialRequest = Booktrial::raw(function($collection) use ($to, $from){

				$aggregate = [];
				$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($from))));
					$match['$match']['created_at']['$gt'] = $from_date;
				$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($to))));
					$match['$match']['created_at']['$lte'] = $to_date;

				$aggregate[] = $match;

				$group = array(
					'$group' => array(
						'_id' => array(
							'finder_id' => '$finder_id',
							'finder_name'	=> '$finder_name',
							'city_id' => '$city_id'
							),
						'count' => array(
							'$sum' => 1
							)
						)
					);

				$aggregate[] = $group;
				$aggregate[] = array('$sort' => array('count'=> -1));

				return $collection->aggregate($aggregate);

			});
	}



	public function nehacustomertrials($year, $month){
		$trials = array();
		   $booktrial = Booktrial::where('schedule_slot', 'exists', true)
            ->where('schedule_slot', '!=', "")
            ->where('schedule_date_time',  '>=', new \DateTime( date("d-m-Y", strtotime( "1-".$month."-".$year )) ))
            ->where('schedule_date_time',  '<=', new \DateTime( date("d-m-Y", strtotime( "31-".$month."-".$year )) ))->get(array("customer_email"))->toArray();
		
		$ht = Order::where("type","healthytiffintrail")->where("status","1")->get(array("customer_email"))->toArray();
		$booktrial = array_fetch($booktrial,"customer_email");
		$ht = array_fetch($ht,"customer_email");
		$booktrial = array_merge($booktrial,$ht);
		$unique = array_unique($booktrial);
		return array("booktrial" => count($booktrial),"unique" =>count($unique));
		
	}

	public function unsetStartEndService(){

		Service::where('start_date',"")->unset('start_date');

		Service::where('end_date',"")->unset('end_date');

	}


public function xyz(){
	$pubnub = new Pubnub('demo', 'demo');
	$pubnub->subscribe('hello_world', function ($envelope) {
             print_r($envelope['message']);
       });
}


public function yes($msg){
	$pubnub = new Pubnub('demo', 'demo');
	$publish_result = $pubnub->publish('hello_world',$msg);
   
	print_r($publish_result);
}

	public function orderQuery($offset,$limit){

		$orders = Order::where('vertical_type','exists',false)
					->where('vertical_type','exists',false)
					->where('migration_done','exists',false)
					->orderBy('created_at','desc')
					->skip($offset)
					->take($limit)
					->get();

		return $orders;
	}

	public function newOrderMigration(){

		try{

			$customer_email = [
				"smart.saili@gmail.com",
				"ut.mehrotra@gmail.com",
				"gauravraviji@gmail.com",
				"pranjalitanya@gmail.com",
				"nishankiit@gmail.com"
			];

			$finder_id = [
				3305,
				6465,
				6323,
				6324,
				6325,
				6449,
				6332,
				6865,
				9403
			];

			Order::whereIn('customer_email',$customer_email)->delete();

			Order::where('customer_email', 'LIKE', '%fitternity.com%')->whereNotIn('customer_email',['neha@fitternity.com,jayamvora@fitternity.com'])->delete();

			Order::whereIn('finder_id',$finder_id)->delete();

			Order::where('customer_name', 'LIKE', '%test%')->delete();

			ini_set('memory_limit', '-1');
        	ini_set('max_execution_time', 3000);

			$offset = 0;
			$limit = 50;

			$orders = $this->orderQuery($offset,$limit);

			while(count($orders) != 0){

				foreach ($orders as $key => $order) {

					try{

						$set_vertical_type = array(
							'healthytiffintrail'=>'tiffin',
							'healthytiffinmembership'=>'tiffin',
							'memberships'=>'workout',
							'booktrials'=>'workout',
							'workout-session'=>'workout',
							'3daystrial'=>'workout',
							'vip_booktrials'=>'workout',
						);

						$set_membership_duration_type = array(
							'healthytiffintrail'=>'trial',
							'healthytiffinmembership'=>'short_term_membership',
							'memberships'=>'short_term_membership',
							'booktrials'=>'trial',
							'workout-session'=>'workout_session',
							'3daystrial'=>'trial',
							'vip_booktrials'=>'vip_trial',
						);

						if($order->customer_source != 'admin'){

							if(!isset($order->vertical_type)){

								(isset($set_vertical_type[$order->type])) ? $order->vertical_type = $set_vertical_type[$order->type] : null;

								(isset($order->finder_category_id) &&  $order->finder_category_id == 41) ? $order->vertical_type = 'trainer' : null;
							}

							if(!isset($order->membership_duration_type)){

								(isset($set_membership_duration_type[$order->type])) ? $order->membership_duration_type = $set_membership_duration_type[$order->type] : null;

								(isset($order->duration_day) && $order->duration_day >=30 && $order->duration_day <= 90) ? $order->membership_duration_type = 'short_term_membership' : null;

								(isset($order->duration_day) && $order->duration_day >90 ) ? $order->membership_duration_type = 'long_term_membership' : null;
							}

							if($order->status == "1"){

								if(!isset($order->secondary_payment_mode) && $order->payment_mode == 'paymentgateway'){
									$order->secondary_payment_mode = 'payment_gateway_membership';
								}		

							}

							if(!isset($order->secondary_payment_mode) && $order->payment_mode == 'paymentgateway'){
								$order->secondary_payment_mode = 'payment_gateway_tentative';
							}

						}

						if(!isset($order->secondary_payment_mode) && $order->payment_mode == 'cod'){
							$order->secondary_payment_mode = 'cod_membership';
						}

						if(!isset($order->secondary_payment_mode) && $order->payment_mode == 'at the studio'){
							$order->secondary_payment_mode = 'at_vendor_pre';
						}

						if($order->status == "1"){

							if(!isset($order->secondary_payment_mode) && $order->payment_mode == 'paymentgateway'){
								$order->secondary_payment_mode = 'payment_gateway_membership';
							}		

						}

						if(!isset($order->secondary_payment_mode) && $order->payment_mode == 'paymentgateway'){
							$order->secondary_payment_mode = 'payment_gateway_tentative';
						}

						if(!isset($order->vertical_type)){

							(isset($set_vertical_type[$order->type])) ? $order->vertical_type = $set_vertical_type[$order->type] : null;

							(isset($order->finder_category_id) &&  $order->finder_category_id == 41) ? $order->vertical_type = 'trainer' : null;
						}

						if(isset($order->schedule_slot) && is_string($order->schedule_slot) && $order->schedule_slot != "" && $order->schedule_slot != "-"){

							$schedule_slot = explode("-", $order->schedule_slot);

							if(isset($schedule_slot[0]) && isset($schedule_slot[1])){
								$order->start_time = trim($schedule_slot[0]);
								$order->end_time = trim($schedule_slot[1]);
							}
						}

						if($order->status != "1"){
							$order->status = "0";
						}

						if(isset($order->customer_phone)  && $order->customer_phone != ""){
							$order->customer_phone = str_replace(" ", "", $order->customer_phone);
						}

		                $order->migration_done = "1";

						$order->update();

					}catch(Exception $exception){

						Log::error($order);

						$message = array(
			            	'type'    => get_class($exception),
			               	'message' => $exception->getMessage(),
			               	'file'    => $exception->getFile(),
			                'line'    => $exception->getLine(),
			            );

			            Log::error($exception);

						return array('status'=>'fail','error_message'=>$message);

					}
				}

				$offset = $offset + 50;

				$orders = $this->orderQuery($offset,$limit);

			}

			return array('status'=>'done');

		}catch(Exception $exception){

			$message = array(
            	'type'    => get_class($exception),
               	'message' => $exception->getMessage(),
               	'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            );

            Log::error($exception);

			return array('status'=>'fail','error_message'=>$message);
		}

		print_r($return);
	}

	public function cacheLocations(){
		$locationTags = Locationtag::where('status', "1")->get(['_id', 'name', 'slug','location_group','lat','lon']);
		return $locationTags;

	}

	public function cacheFinderCategoryTags($city="mumbai"){
		// $finderCategoryTags = Findercategorytag::where('status', "1")->get(['_id', 'name', 'slug']);
		if($city != "all"){
			$finderCategoryTags = citywise_categories($city);
		}else{
			$finderCategoryTags = citywise_categories("all");
		}
		return $finderCategoryTags;

	}

	public function cacheOfferings(){
		$offerings = Offering::where('status', "1")->get(['_id', 'name', 'slug']);
		return $offerings;

	}

	public function serviceToVendorMigration(){

		try{

			$serviceToFinder =[
			"5" => 35,
			"2" => 7,
			"1" => 6,
			"3" => 8,
			"4" => 11,
			"19" => 12,
			"86" => 14,
			"111" => 32,
			"114" => 36,
			"123" => 10,
			"152" => 9,
			"154" => 44,
			"168" => 45,
			"180" => 42,
			"184" => 41
			];
			Service::$withoutAppends=true;
			
			// $vendorServices = Vendorservice::where('hidden',false)->get(['category','vendor_id']);
			$vendorServices = Vendorservice::where('hidden',false)->get(['category','vendor_id']);
			// return $service;
			// exit(0);
			$vendorsChanged=array();
			foreach ($vendorServices as $key => $value) {
				// return $value;
				// exit(0);

				if(isset($serviceToFinder[$value['category']['primary']]) && isset($value['vendor_id'])){
					$vendorCategoryId = $serviceToFinder[$value['category']['primary']];
					// return $vendorServiceId;
					// exit(0);
					$vendorCategory = Vendorcategory::where('_id', $vendorCategoryId)->first(['vendors']);
					// return $vendorServiceCategory;
					// exit(0);
					// $key = array_search($value['vendor_id'], $vendorCategory['vendors']);
					// return gettype($key);
					// exit(0);
					// if(!is_int($key)){
						// return $value['vendor_id'];
						// exit(0);
						$vendor = Vendor::find((int) $value['vendor_id']);
						// return $vendor;
						// exit(0);
						$key = array_search($vendorCategoryId, $vendor['vendorcategory']['secondary']);
						// return gettype($key);
						// exit(0);
						if(!is_int($key)){
							// print_r("inside");
							array_push($vendorsChanged, (int) $value['vendor_id']);
							$vendorCategory = $vendor['vendorcategory'];
							array_push($vendorCategory['secondary'], $vendorCategoryId);
							$vendor->vendorcategory = $vendorCategory;
							$vendor->update();
							// $url = Config::get('app.url').'/reverse/migration/vendor/'.$value['vendor_id'];
							// // return $url;
							// // exit(0);
							// $ch = curl_init();
							// curl_setopt($ch, CURLOPT_URL, $url);
							// $result = curl_exec($ch);
							// curl_close($ch);
							// return gettype($result);
							// exit(0);
						}
					// }
 				
				}
				Log::info("Done for ".$value);
			}
			return array('status'=>'done','Vendors'=>$vendorsChanged);
		}catch(Exception $exception){

			$message = array(
	        	'type'    => get_class($exception),
	           	'message' => $exception->getMessage(),
	           	'file'    => $exception->getFile(),
	            'line'    => $exception->getLine(),
	        );

	        Log::error($exception);

			return array('status'=>'fail','error_message'=>$message);
		}

		// print_r($return);
	}
		
	public function vendorReverseMigrate($vendorIds=[])
	{
		$ch = curl_init();
		foreach($vendorIds as $vendorId){
			Log::info("migratoin url:".Config::get('app.url'));
			$url = Config::get('app.url').'/reverse/migration/vendor/'.$vendorId;
			curl_setopt($ch, CURLOPT_URL, $url);
			$result = curl_exec($ch);
			Log::info("Done for vendor Id".$vendorId);
		}
		
		curl_close($ch);	
	}

	public function subCatToOfferings(){
		
		try{
			
			$subCatToOffering =[
				"82"=>[462, 463, 464, 465, 466, 467],
				"77"=>[251],
				"79"=>[252],
				"88"=>[253],
				"116"=>[469],
				"93"=>[250],
				"94"=>[346],
				"110"=>[12],
				"96"=>[468],
				"97"=>[456],
				"20"=>[334],
				"21"=>[65],
				"13"=>[260],
				"12"=>[259],
				"6"=>[294],
				"8"=>[293],
				"11"=>[337],
				"134"=>[476],
				"14"=>[40],
				"28"=>[35],
				"26"=>[36],
				"16"=>[32],
				"25"=>[41],
				"17"=>[39],
				"15"=>[34],
				"18"=>[31],
				"27"=>[38],
				"22"=>[33],
				"23"=>[37],
				"24"=>[274],
				"142"=>[477],
				"30"=>[326],
				"29"=>[342],
				"32"=>[322],
				"38"=>[312],
				"41"=>[333],
				"48"=>[319],
				"51"=>[324],
				"53"=>[320],
				"54"=>[315],
				"56"=>[479],
				"100"=>[480],
				"83"=>[361],
				"19"=>[362],
				"17"=>[367],
				"138"=>[363],
				"40"=>[316],
				"35"=>[321]

			];

			$subCatIds = array_keys($subCatToOffering);
			// return $subCatIds;
			// exit(0);
			Service::$withoutAppends=true;
			Log::info("inside subcat to offering");
			
			// $vendorServices = Vendorservice::where('hidden',false)->get(['category','vendor_id']);
			$vendorServices = Vendorservice::where('hidden',false)->whereIn('category.secondary',$subCatIds)->get(['category','vendor_id']);
			// return $vendorServices;
			// exit(0);
			$vendorsChanged=array();
			foreach ($vendorServices as $key => $value) {
				// return $value;
				// exit(0);

				if(isset($subCatToOffering[$value['category']['secondary']]) && isset($value['vendor_id'])){
					// return $value;
					// exit(0);
					$offeringIds = $subCatToOffering[$value['category']['secondary']];
					// return $offeringIds;
					// exit(0);
					foreach($offeringIds as $offeringId){
						$offering = Offering::on($this->fitapi)->where('_id', $offeringId)->first(['vendors']);
						// return $offering;
						// continue;
						// $key = array_search($value['vendor_id'], $offering['vendors']);
						// return gettype($key);
						// exit(0);
						// if(!is_int($key)){
							// return $value['vendor_id'];
							// exit(0);
							$vendor = Vendor::find((int) $value['vendor_id']);
							// return $vendor;
							// exit(0);
							$key = array_search($offeringId, $vendor['filter']['offerings']);
							// return gettype($key);
							// exit(0);
							if(!is_int($key)){
								// return $vendor;
								// exit(0);
								// print_r("inside");
								array_push($vendorsChanged, (int) $value['vendor_id']);
								$filter = $vendor['filter'];
								array_push($filter['offerings'], $offeringId);
								$vendor->filter = $filter;
								$vendor->update();
							}
						// }
						Log::info("Done for service Id ".$value." offering Id :".$offeringId);
					}
					// return $vendorServiceId;
					// exit(0);
					
 				
				}
			}
			return array('status'=>'done','Vendors'=>$vendorsChanged);
		}catch(Exception $exception){

			$message = array(
	        	'type'    => get_class($exception),
	           	'message' => $exception->getMessage(),
	           	'file'    => $exception->getFile(),
	            'line'    => $exception->getLine(),
	        );

	        Log::error($exception);

			return array('status'=>'fail','error_message'=>$message);
		}
	}

	public function cacheFinders(){
		Finder::$withoutAppends = true;
		$finders = Finder::where('status', "1")->get(['_id', 'slug']);
		return $finders;
	}
	public function customer_data()
	{       
		$start_date = new DateTime('01-02-2017');
		$end_date = new DateTime('31-03-2017');
			$transactions = Transaction::
			where('transaction_type', 'Order')
				->where('status', '1')
				->where('created_at', '>=', $start_date)
				->where('created_at', '<=', $end_date)
				->get();
			$after_trial = 0;
			$after_link = 0;
			$orders = count($transactions);
			$orderDetails = array();
			$trialDetails = array();
			$linkDetails = array();
			foreach($transactions as $transaction){

				$data = array();
				$fields = array('finder_name', 'finder_id', 'service_name','service_id', 'amount_finder', 'customer_email', 'customer_phone', 'customer_name');
				foreach($fields as $field){
					if(isset($transaction[$field])){
						$data[$field] = $transaction[$field];
					}
				}
				// array_push($orderDetails, $data);

				$prev_payment = Transaction::where('customer_email', $transaction['customer_email'])
					->where('created_at', '<', $transaction['created_at'])
					->where('transaction_type', 'Booktrial')
					->first();


				if($prev_payment){
					$after_trial++;
					// array_push($trialDetails, $data);
					if(isset($transaction['paymentLinkEmailCustomerTiggerCount'])){
						$after_link++;
						// array_push($linkDetails, $data);
						
					}
				}
			}
			$data = array(
				'orders' 	=> $orders,
				'trials'	=> $after_trial,
				'link'		=> $after_link,
				// 'orderDetails' 	=> $orderDetails,
				// 'trialDetails'	=> $trialDetails,
				// 'linkDetails'	=> $linkDetails
			);
			return $data;	
	}


	public function zumba_data(){
		Service::$withoutAppends = true;
		$zumba_services = Service::where('servicecategory_id', 19)
			->where('status', '1')
			->lists('_id')
			;

		$count = 0;
		$booktrials = Transaction::where('service_id','exists',true)->where('transaction_type', 'Booktrial')->get(['service_id']);

		foreach($booktrials as $booktrial){
			if(in_array($booktrial['service_id'],$zumba_services)){
					$count++;

			}
			// foreach($zumba_services as $service){
			// 	if($booktrial['finder_id']==$service['finder_id'] && strtolower($booktrial['service_name'])==strtolower($service['name'])){
			// 	}
			// }
		}
		$data = array(
			'total_booktrials'	=> count($booktrials),
			'zumba_trials'		=> $count
		);
		return $data;
	}

	public function booktrial_funnel()
	{       
		$start_date = new DateTime('01-02-2017');
		$end_date = new DateTime('31-02-2017');
			$transactions = Transaction::where('transaction_type', 'Booktrial')
				->where('created_at', '>=', $start_date)
				->where('created_at', '<=', $end_date)
				->groupBy('customer_email')->lists('customer_email');
			$orders = Transaction::where('transaction_type', 'Order')
									->where('status', '1')
									->where('created_at', '>=', $start_date)
									->where('type', "memberships")		
									->whereIn('customer_email',$transactions)
									->groupBy('customer_email')->lists('customer_email');
			$nopaymentgateway = Transaction::where('transaction_type', 'Order')
									->where('status', '1')
									->where('created_at', '>=', $start_date)
									->where('type', "memberships")		
									->whereIn('customer_email',$transactions)
									->where("payment_mode",'!=', "paymentgateway")
									->groupBy('customer_email')->lists('customer_email');
			$query = Transaction::where('transaction_type', 'Order')
									->where('status', '1')
									->where('created_at', '>=', $start_date)
									->where('type', "memberships")		
									->whereIn('customer_email',$transactions)
									->where("payment_mode", "paymentgateway");
			$paymentgateway = $query->groupBy('customer_email')->lists('customer_email'); 
			$linkSent = $query->where("paymentLinkEmailCustomerTiggerCount", "exists",true)->groupBy('customer_email')->lists('customer_email');
									
			return $data = array(
				'Trials' 	=> count($transactions),
				'orders_after_trials'	=> count($orders),
				'did_not_purchase'		=> count($transactions) - count($orders),
				'offline'				=> count($nopaymentgateway),
				'online'				=> count($paymentgateway),
				'link'					=> count($linkSent),
				'direct'				=> count($paymentgateway) - count($linkSent)

				// 'orderDetails' 	=> $orderDetails,
				// 'trialDetails'	=> $trialDetails,
				// 'linkDetails'	=> $linkDetails
			);	
	}

	public function order_funnel(){
		$start_date = new DateTime('01-02-2017');
		$end_date = new DateTime('31-02-2017');
		// $fortyfive = new DateTime('01-11-2016');
		$transactions = Transaction::where('transaction_type', 'Order')
									->where('status', '1')
									->where('type', "memberships")		
									->where('created_at', '>=', $start_date)
									->where('created_at', '<=', $end_date)
									->groupBy('customer_email')->lists('customer_email');
		$transactions2 = Transaction::where('status','!=', '1')
									// ->where('created_at', '>=', $fortyfive)
									->where('created_at', '<=', $end_date)
									->whereIn('customer_email',$transactions)
									->groupBy('customer_email')
									->lists('customer_email');
		return $data = array(
			"purchases" => count($transactions),
			"direct_purchases" => count(array_diff($transactions, $transactions2)),
			"people_who_interacted_in_last_45_days" => count($transactions2)
		);
		
	}
	public function linksent_funnel()
	{       
		$start_date = new DateTime('01-02-2017');
		$end_date = new DateTime('31-02-2017');
			$query = Order::
				where('created_at', '>=', $start_date)
				->where('created_at', '<=', $end_date)->where("type","memberships");

			$transactions = $query->where("paymentLinkEmailCustomerTiggerCount", "exists",true)->groupBy('customer_email')->lists('customer_email');
			$link_sent_purchase = Order::
				where('created_at', '>=', $start_date)
				->where('created_at', '<=', $end_date)->where("type","memberships")
										->whereIn('customer_email',$transactions)
										->where('status', '1')
										->where("paymentLinkEmailCustomerTiggerCount", "exists",true)
										// ->groupBy('customer_email')
										->lists('customer_email');
			$link_sent_direct_purchase = Order::
				where('created_at', '>=', $start_date)
				->where('created_at', '<=', $end_date)->where("type","memberships")->whereIn('customer_email',$transactions)
												->where('status', '1')
												->where("paymentLinkEmailCustomerTiggerCount", "exists",false)
												// ->where("payment_mode", "paymentgateway")
												->lists('customer_email');
												// ->groupBy('customer_email');
			$link_sent_direct_purchase_offline = Order::
				where('created_at', '>=', $start_date)
				->where('created_at', '<=', $end_date)->where("type","memberships")->whereIn('customer_email',$transactions)
														->where('status', '1')
														->where("paymentLinkEmailCustomerTiggerCount", "exists",false)
														->where("payment_mode",'!=', "paymentgateway")
														->groupBy('customer_email')->lists('customer_email');
			return $data = array(
				"link_sent" => count($transactions),
				"link_sent_purchase" => count($link_sent_purchase),
				"link_sent_direct_purchase" => count($link_sent_direct_purchase),
				"link_sent_direct_purchase_offline" => count($link_sent_direct_purchase_offline),
			);
	}

	public function syncsharecustomerno(){
		$vendors = Vendor::
		where('commercial.share_customer_no', true)
		->lists('_id');
		Finder::whereIn('_id', $vendors)->update(['share_customer_no'=> "1"]);
		Finder::whereNotIn('_id', $vendors)->update(['share_customer_no'=> "0"]);
		return "Done";

	}

	public function ozonetelCaptureBulkSms(){

		ini_set('memory_limit','512M');
		ini_set('max_execution_time', 300);

		$utilities = new Utilities();

		$finder_ids = Ozonetelcapture::where('created_at', '>=', new DateTime(date("2017-01-01 00:00:00")))
						->where('finder_id','exists',true)
						->where('customer_cid','exists',true)
						->where('bulk_sms_sent','exists',false)
						->lists('finder_id');

		$finder_ids = array_map("intval",array_unique($finder_ids));

		$allFinder = [];

		foreach ($finder_ids as $key => $finder_id) {

			$finder = Finder::select('city_id','location_id','title','slug','_id')->where('_id',$finder_id)->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with(array('location'=>function($query){$query->select('_id','name','slug');}))->first();

			$paymentEnableFinderCount = Ratecard::where('direct_payment_enable','1')->where('finder_id',$finder_id)->count();

			$contact_nos = Ozonetelcapture::where('created_at', '>=', new DateTime(date("2017-01-01 00:00:00")))
						->where('finder_id','exists',true)
						->where('finder_id',$finder_id)
						->where('customer_cid','exists',true)
						->where('bulk_sms_sent','exists',false)
						->lists('customer_cid');

			$finder_city_slug = $finder->city->slug;
			$finder_location_slug = $finder->location->slug;
			$finder_slug = $finder->slug;
			$srp_link = $utilities->getShortenUrl(Config::get('app.website')."/".$finder_city_slug."/".$finder_location_slug."/fitness");
			$vendor_link = $utilities->getShortenUrl(Config::get('app.website')."/".$finder_slug);
			$finder_name = ucwords($finder->title);

			$message = "This is regarding your enquiry on Fitternity. We have some great offers running for fitness options around you. Get lowest price guaranteed and rewards like fitness kit or diet plan on your purchase. Get Rs 300 in your wallet by applying promocode in your user profile. Code - GETFIT. Explore - ".$srp_link;

			if($paymentEnableFinderCount > 0){

				$message = "This is regarding your enquiry for ".$finder_name." on Fitternity. We have some great offers running for ".$finder_name." and 10000 other fitness providers. Get lowest price guaranteed and rewards like fitness kit or diet plan on your purchase. Get Rs 300 in your wallet by applying promocode in your user profile. Code GETFIT. Buy now - ".$vendor_link;
			}

			$contact_nos = array_unique($contact_nos);

			$numbers = array_chunk($contact_nos, 500);

			$return = [];

			foreach ($numbers as $key => $contact_no) {

				$ozonetelCapture = Ozonetelcapture::where('created_at', '>=', new DateTime(date("2017-01-01 00:00:00")))
						->where('finder_id','exists',true)
						->where('finder_id',$finder_id)
						->whereIn('customer_cid',$contact_no)
						->update(['bulk_sms_sent'=>time()]);

				$sms['sms_type'] = 'transactional';
				$sms['contact_no'] = $contact_no;
				$sms['message'] = $message;

				$bulkSms = new Bulksms();

				$return[] = $bulkSms->send($sms);
			}

			$allFinder[$finder_id] = $return;

		}

		return $allFinder;

	}


	public function durationDayStringQuery($offset,$limit){

		$orders  = Order::where('duration_day','type',2)
			->where('duration_day','!=',"")
			->skip($offset)
			->take($limit)
			->get();

		return $orders;
	}

	public function durationDayString(){

		ini_set('memory_limit','512M');
		ini_set('max_execution_time', 300);

		$offset = 0;
		$limit = 10;

		$allOrders = $this->durationDayStringQuery($offset,$limit);

		while(count($allOrders) != 0){

			echo $offset;

			foreach ($allOrders as $order) {

				$duration_day = intval($order['duration_day']);

				DB::table('orders')->where('_id', (int)$order->_id)->update(['duration_day' =>$duration_day]);

			}

			$offset = $offset + 10;

			$allOrders = $this->durationDayStringQuery($offset,$limit);
		}

		return array('status'=>'done');

	}

	public function orderFollowupQuery($offset,$limit){
		
		$orders  = Order::active()
			->whereIn('type',['memberships','healthytiffinmembership'])
			->where('added_auto_followup_date','exists',false)
			->where('start_date','exists',true)
			->where('start_date', '>=', new DateTime(date("Y-m-d H:i:s",strtotime("2016-06-01 00:00:00"))))
			->where('end_date','exists',true)
			->where('duration_day','exists',true)
			->where('duration_day','>=',30)
			->skip($offset)
			->take($limit)
			->get();

		return $orders;

	}


	public function orderFollowup(){

		ini_set('memory_limit','512M');
		ini_set('max_execution_time', 300);

		$offset = 0;
		$limit = 10;

		$allOrders = $this->orderFollowupQuery($offset,$limit);

		while(count($allOrders) != 0){

			echo $offset;

			foreach ($allOrders as $order) {

				if($order['duration_day'] >= 30 && $order['duration_day'] < 90){

					if(time() <= strtotime("+7 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 1;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+7 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("+21 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 2;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+21 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("-7 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 1;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-7 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-1 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 2;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-1 days",strtotime($order['end_date'])));

					}

				}elseif ($order['duration_day'] >= 90 && $order['duration_day'] < 180) {

					if(time() <= strtotime("+7 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 1;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+7 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("+45 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 2;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+45 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("-15 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 1;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-15 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-7 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 2;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-7 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-1 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 3;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-1 days",strtotime($order['end_date'])));

					}

				}elseif ($order['duration_day'] >= 180 && $order['duration_day'] < 360) {

					if(time() <= strtotime("+7 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 1;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+7 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("+45 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 2;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+45 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("+75 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 3;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+75 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("-30 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 1;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-30 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-15 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 2;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-15 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-7 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 3;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-7 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-1 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 4;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-1 days",strtotime($order['end_date'])));

					}

				}elseif ($order['duration_day'] >= 360) {

					if(time() <= strtotime("+7 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 1;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+7 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("+45 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 2;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+45 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("+75 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 3;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+75 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("+165 days",strtotime($order['start_date']))){

						$data['followup_status'] = 'catch_up';
						$data['followup_status_count'] = 4;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+165 days",strtotime($order['start_date'])));

					}elseif(time() <= strtotime("-30 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 1;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-30 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-15 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 2;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-15 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-7 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 3;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-7 days",strtotime($order['end_date'])));

					}elseif(time() <= strtotime("-1 days",strtotime($order['end_date']))){

						$data['followup_status'] = 'renewal';
						$data['followup_status_count'] = 4;
						$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("-1 days",strtotime($order['end_date'])));

					}
				}

				if(!isset($order['auto_followup_date']) && isset($data['auto_followup_date']) && isset($order['followup_date']) && strtotime($order['followup_date']) >= strtotime($data['auto_followup_date'])){

					$data = [];

				}

				$data['added_auto_followup_date'] = time();

				$order->update($data);
			}

			$offset = $offset + 10;

			$allOrders = $this->orderFollowupQuery($offset,$limit);
		}

		return array('status'=>'done');
	}


	public function trialFollowupQuery($offset,$limit){

		$trials  = Booktrial::whereIn('type',['booktrials','workout-session'])
			->where('final_lead_stage','exists',true)
			->where('final_lead_stage','post_trial_stage')
			->where('added_auto_followup_date','exists',false)
			->where('schedule_date_time','exists',true)
			->where('schedule_date_time', '>=', new DateTime(date("Y-m-d H:i:s",strtotime("2017-04-01 00:00:00"))))
			->skip($offset)
			->take($limit)
			->get();
			
		return $trials;

	}

	public function trialFollowup(){

		ini_set('memory_limit','512M');
		ini_set('max_execution_time', 300);

		$offset = 0;
		$limit = 10;

		$allTrials = $this->trialFollowupQuery($offset,$limit);

		while(count($allTrials) != 0){

			echo $offset;

			foreach ($allTrials as $trial) {

				$data['auto_followup_date'] = date('Y-m-d H:i:s', strtotime("+31 days",strtotime($trial['schedule_date_time'])));

				if(!isset($trial['auto_followup_date']) && isset($data['auto_followup_date']) && isset($trial['followup_date']) && strtotime($trial['followup_date']) >= strtotime($data['auto_followup_date'])){

					$data = [];

				}

				$data['added_auto_followup_date'] = time();

				$trial->update($data);

			}

			$offset = $offset + 10;

			$allTrials = $this->trialFollowupQuery($offset,$limit);
		}

		return array('status'=>'done');

	}

	public function demonetisation(){

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);

        $customer_wallet_customer_ids = Customerwallet::where('balance','exists',true)->where('balance','>',0)->lists('customer_id');

        $customer_wallet_customer_ids = array_map('intval', array_unique($customer_wallet_customer_ids));

        $customer_demonetisation_customer_ids = Customer::where('demonetisation','exists',true)->lists('_id');

        $customer_demonetisation_customer_ids = array_map('intval', array_unique($customer_demonetisation_customer_ids));

        $percentage = 25;
        $cap = 1500;
        $wallet_limit = 2500;

        $final_customer_ids = [];

        //echo"<pre>";print_r($customer_wallet_customer_ids);exit;

        foreach ($customer_wallet_customer_ids as $key => $customer_id) {

        	if(!in_array($customer_id,$customer_demonetisation_customer_ids)){
        		$final_customer_ids[] = $customer_id;
        	}

        }

        echo"<pre> final_customer_ids ---";print_r(count($final_customer_ids));

        foreach ($final_customer_ids as $key => $customer_id) {

    		$customer = Customer::find($customer_id);

    		if($customer && !isset($customer->demonetisation)){

    			//echo $customer_id;

        		$wallet = Customerwallet::where('customer_id',$customer_id)->orderBy('_id','desc')->first();

                $fitcash_balance = intval($wallet->balance * ($percentage/100));

                if($fitcash_balance >= $cap){
                	$fitcash_balance = $cap;
                }

                $fitcash_plus_balance = 0;

                if(isset($wallet->balance_fitcash_plus)){

                    $fitcash_plus_balance = $wallet->balance_fitcash_plus;

                    if($fitcash_plus_balance >= $cap){
	            		$fitcash_balance = 0;
	            	}else{
	            		if($fitcash_balance+$fitcash_plus_balance > $wallet_limit){
	            			$fitcash_balance = $wallet_limit - $fitcash_plus_balance;
	            		}
	            	}
            	}

            	//echo "<br/>".$fitcash_balance;

            	//echo "<br/>".$fitcash_plus_balance;

	            $current_wallet_balance = $fitcash_balance + $fitcash_plus_balance;

	            //echo"<pre>";print_r($current_wallet_balance);


				// What is this
	            if($current_wallet_balance > $wallet_limit){
                    $customer->update(['current_wallet_balance'=>$current_wallet_balance]);
                }

        		$wallet = new Wallet();
	            $wallet->_id = (Wallet::max('_id')) ? (int) Wallet::max('_id') + 1 : 1;
	            $wallet->amount = (int)$current_wallet_balance;
	            $wallet->used = 0;
	            $wallet->balance = (int)$current_wallet_balance;
	            $wallet->status = "1";
	            $wallet->entry = 'credit';
	            $wallet->customer_id = (int)$customer_id;
	            $wallet->validity = time()+(86400*360);
	            $wallet->type = "CREDIT";
	            $wallet->description = "Conversion of FitCash+ on Demonetization, Expires On : ".date('d-m-Y',time()+(86400*360));
	            $wallet->save();

	            $wallet_transaction_data['wallet_id'] = $wallet->_id;
	            $wallet_transaction_data['entry'] = $wallet->entry;
	            $wallet_transaction_data['type'] = $wallet->type;
	            $wallet_transaction_data['customer_id'] = $wallet->customer_id;
	            $wallet_transaction_data['amount'] = $wallet->amount;
	            $wallet_transaction_data['description'] = $wallet->description;
	            $wallet_transaction_data['validity'] = $wallet->validity;

	            $walletTransaction = WalletTransaction::create($wallet_transaction_data);

	            $walletTransaction->update(['group'=>$walletTransaction->_id]);

	            $customer->update(['demonetisation'=>time()]);

	            // if(isset($customer->contact_no) && $customer->contact_no != "" && $customer->contact_no != null){

		        //     $sms_data = [
		        //     	'customer_phone' => $customer->contact_no,
		        //     	'customer_wallet_balance' => $current_wallet_balance
		        //     ];

		        //     $customersms = new CustomerSms();

	            // 	$customersms->demonetisation($sms_data);
	            // }

	           //echo"<pre>";print_r('success');exit;
	        }

        }

        return "success";

    }

    public function addFitcash(){

    	$customer_emails = [
			['email'=>'mittravinda@gmail.com','amount'=>400],
			['email'=>'vinaya.r.kanoor@gmail.com','amount'=>400],
			['email'=>'rohita.gadepalli@gmail.com','amount'=>400],
			['email'=>'samar_krishna@yahoo.com','amount'=>800],
			['email'=>'mumyums@gmail.com','amount'=>400],
			['email'=>'reeneta05@hotmail.com','amount'=>800],
			['email'=>'gayatrirao.great@gmail.com','amount'=>400],
			['email'=>'aneeshapillai13@gmail.com','amount'=>400],
			['email'=>'samshad.mohd@gmail.com','amount'=>400],
			['email'=>'madhurgundecha@gmail.com','amount'=>400],
			['email'=>'sarikapancholi48@gmail.com','amount'=>400],
			['email'=>'brave.noopur08@gmail.com','amount'=>400],
			['email'=>'archana.k11@gmail.com','amount'=>400],
			['email'=>'asha.rattan@majesco.com','amount'=>400],
			['email'=>'mitikjagtiani@gmail.com','amount'=>400],
			['email'=>'amitnandoskar@gmail.com','amount'=>400],
			['email'=>'srk9363@gmail.com','amount'=>400],
			['email'=>'mumyums@gmail.com','amount'=>400],
			['email'=>'shweta.h.lalan@gmail.com','amount'=>400],
			['email'=>'kavitasachinnair@gmail.com','amount'=>400],
			['email'=>'aky.akshi@gmail.com','amount'=>400],
			['email'=>'tanishasadevra2609@gmail.com','amount'=>400],
		];

		$fitcashGiven = [];

		foreach ($customer_emails as $value) {

			$customer = Customer::where('email',$value['email'])->first();

			if($customer){
				if(!isset($customer->demonetisation)){
					$customer->demonetisation =	time();
					$customer->update();
				}
				$description = "Cashback on event purchase Beat the Heat";

				$walletGiven = Wallet::where('description','LIKE','%'.$description.'%')->where('customer_id',(int)$customer->_id)->first();

				if(!$walletGiven){

					$wallet = new Wallet();
		            $wallet->_id = (Wallet::max('_id')) ? (int) Wallet::max('_id') + 1 : 1;
		            $wallet->amount = (int)$value['amount'];
		            $wallet->used = 0;
		            $wallet->balance = (int)$value['amount'];
		            $wallet->status = "1";
		            $wallet->entry = 'credit';
		            $wallet->customer_id = (int)$customer->_id;
		            $wallet->validity = time()+(86400*180);
		            $wallet->type = "CASHBACK";
		            $wallet->description = "Cashback on event purchase Beat the Heat, Expires On : ".date('d-m-Y',time()+(86400*180));
		            $wallet->save();

		            $wallet_transaction_data['wallet_id'] = $wallet->_id;
		            $wallet_transaction_data['entry'] = $wallet->entry;
		            $wallet_transaction_data['type'] = $wallet->type;
		            $wallet_transaction_data['customer_id'] = $wallet->customer_id;
		            $wallet_transaction_data['amount'] = $wallet->amount;
		            $wallet_transaction_data['description'] = $wallet->description;
		            $wallet_transaction_data['validity'] = $wallet->validity;

			        $walletTransaction = WalletTransaction::create($wallet_transaction_data);

			        $fitcashGiven[] = $value['email'];
			    }
		    }
		}

		echo"<pre>customer_emails";print_r($customer_emails);

		echo"<pre>fitcashGiven";print_r($fitcashGiven);

		return "Success";

    }

    public function conditionTest(){

		$request = [];

		$request['order_id'] = 48495;
		$customer_id = 1;

		$order = Order::find((int)$request['order_id'])->toArray();

        $fitcashCoupons = Fitcashcoupon::select('_id','code','condition')->where('condition','exists',true)->get();

        $query = Wallet::active()->where('customer_id',(int)$customer_id)->where('balance','>',0);

        if(count($fitcashCoupons) > 0){

            $fitcashCoupons = $fitcashCoupons->toArray();

            foreach ($fitcashCoupons as $coupon) {

            	$code = $coupon['code'];

            	$condition_array = [];

            	foreach ($coupon['condition'] as $condition) {

            		$operator = $condition['operator'];
            		$field = $condition['field'];
            		$value = $condition['value'];

            		switch ($operator) {
            			case 'in':

            				if(isset($order[$field]) && in_array($order[$field],$value)){
			                    $condition_array[] = 'true';
			                }else{
			                	$condition_array[] = 'false';
			                }

            				break;

            			case 'not_in':

            				if(isset($order[$field]) && !in_array($order[$field],$value)){
			                    $condition_array[] = 'true';
			                }else{
			                	$condition_array[] = 'false';
			                }

            				break;
            		}

            	}

            	if(in_array('false', $condition_array)){
            		$query->where('coupon','!=',$code);
        		}

            }
        }

        $allWallets  = $query->OrderBy('_id','asc')->get();

        return $allWallets;

	}

	public function manualtractionupdate($type, $x){

		$x = intval($x);
	
		$data = Input::all();

		$service_ids = $data['service_ids'];

		$services = 0;
		$vendorservices = 0;

		if($type=='increment'){

			$services = Service::whereIn('_id', $service_ids)->increment('traction.sales', $x);

			$vendorservices = Vendorservice::whereIn('_id', $service_ids)->increment('traction.sales', $x);

		}else if($type=='decrement'){

			$services = Service::whereIn('_id', $service_ids)->where('traction.sales', '<=', $x)->update(['traction.sales'=> 0]);

			$vendorservices = Vendorservice::whereIn('_id', $service_ids)->where('traction.sales', '<=', $x)->update(['traction.sales'=> 0]);

			$services += Service::whereIn('_id', $service_ids)->where('traction.sales', '>', $x)->decrement('traction.sales', $x);

			$vendorservices += Vendorservice::whereIn('_id', $service_ids)->where('traction.sales', '>', $x)->decrement('traction.sales', $x);

		}

		return array('services updated'=>$services, 'vendorservices updated'=> $vendorservices);

	}

	public function linkSentNotSuccess(){

		ini_set('memory_limit','512M');
		ini_set('max_execution_time', 300);

		$orderSuccessCustomerId = Order::active()->where('customer_id','!=','xxxxxxxxxx')->whereIn('type',['memberships','healthytiffinmembership'])->where('created_at', '>=', new DateTime(date("Y-m-d H:i:s",strtotime("2017-07-01 00:00:00"))))->lists('customer_id');

		$orderSuccessCustomerId = array_unique(array_map("intval", $orderSuccessCustomerId));

		$orderNotSuccessOrderId = Order::whereIn('type',['memberships','healthytiffinmembership'])
			->where('created_at', '>=', new DateTime(date("Y-m-d H:i:s",strtotime("2017-07-01 00:00:00"))))
			->where('status','!=','1')
			->where("paymentLinkEmailCustomerTiggerCount","exists",true)
			->where("paymentLinkEmailCustomerTiggerCount",">=",1)
			->whereNotIn('customer_id',$orderSuccessCustomerId)
			->lists('_id');

		$orderNotSuccessOrderId = array_map("intval", $orderNotSuccessOrderId);

		$offset = 0;
		$limit = 10;

		$allOrders = $this->linkSentNotSuccessQuery($offset,$limit,$orderNotSuccessOrderId);

		$customersms = new CustomerSms();

		while(count($allOrders) != 0){

			echo $offset;

			foreach ($allOrders as $order) {

				$data = [];

				$data['payment_link'] = Config::get('app.website')."/paymentlink/".$order['order_id'];

				if(isset($order['ratecard_id']) && $order['ratecard_id'] != ""){
		            $data['payment_link'] = Config::get('app.website')."/buy/".$order['finder_slug']."/".$order['service_id']."/".$order['ratecard_id']."/".$order['order_id'];
		        }

		        if(isset($order['no_ratecard_service_duration']) && $order['no_ratecard_service_duration'] != ""){
		            $data['payment_link'] = Config::get('app.website')."/buy/".$order['finder_slug']."/".$order['service_id']."/true/".$order['order_id'];
		        }

		        $data['customer_name'] = ucwords($order['customer_name']);
		        $data['customer_phone'] = $order['customer_phone'];
		        $data['finder_name'] = ucwords($order['finder_name']);

		       	$customersms->linkSentNotSuccess($data);

				$order->update(['linkSentNotSuccess'=>time()]);

			}

			$offset = $offset + 10;

			$allOrders = $this->linkSentNotSuccessQuery($offset,$limit,$orderNotSuccessOrderId);
		}

		return array('status'=>'done');

	}

	public function linkSentNotSuccessQuery($offset,$limit,$orderNotSuccessOrderId){
		
		$orders  = Order::whereIn('type',['memberships','healthytiffinmembership'])
			->where('created_at', '>=', new DateTime(date("Y-m-d H:i:s",strtotime("2017-07-01 00:00:00"))))
			->where('status','!=','1')
			->where("paymentLinkEmailCustomerTiggerCount","exists",true)
			->where("paymentLinkEmailCustomerTiggerCount",">=",1)
			->whereIn('_id',$orderNotSuccessOrderId)
			->skip($offset)
			->take($limit)
			->where('linkSentNotSuccess','exists',false)
			->get();

		return $orders;

	}

	public function servicefilterreversemigration(){

		$updated_vendors = [];

		$vendors = Vendor::where('filter.servicesfilter', 'exists', true)->take(7)->get(['_id', 'filter.servicesfilter']);

		$i = 1;
		
		Log::info("Vendors to update:".count($vendors));
		foreach($vendors as $vendor){

			$result = Finder::where('_id', $vendor['id'])->update(['servicesfilter'=>$vendor['filter']['servicesfilter']]);
			array_push($updated_vendors, $vendor['_id']);
			Log::info("Updated:".$i++);
		}
		return $updated_vendors;
	}





    
}
