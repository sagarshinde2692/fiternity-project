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

class DebugController extends \BaseController {

	public function __construct(FinderMailer $findermailer) {

		$this->findermailer						=	$findermailer;

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
}
