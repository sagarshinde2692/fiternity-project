<?PHP

/**
 * ControllerName : FindersController.
 * Maintains a list of functions used for FindersController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\FinderMailer as FinderMailer;
use App\Services\Cacheapi as Cacheapi;
use App\Services\Cron as Cron;
use App\Services\Utilities as Utilities;


class FindersController extends \BaseController {


	protected $facetssize                   =   10000;
	protected $limit                        =   10000;
	protected $elasticsearch_host           =   "";
	protected $elasticsearch_port           =   "";
	protected $elasticsearch_default_index  =   "";
	protected $elasticsearch_url            =   "";
	protected $elasticsearch_default_url    =   "";

	protected $findermailer;
	protected $cacheapi;

	public function __construct(FinderMailer $findermailer, Cacheapi $cacheapi, Utilities $utilities) {

		parent::__construct();
		$this->elasticsearch_default_url        =   "http://".Config::get('app.es.host').":".Config::get('app.es.port').'/'.Config::get('app.es.default_index').'/';
		$this->elasticsearch_url                =   "http://".Config::get('app.es.host').":".Config::get('app.es.port').'/';
		$this->elasticsearch_host               =   Config::get('app.es.host');
		$this->elasticsearch_port               =   Config::get('app.es.port');
		$this->elasticsearch_default_index      =   Config::get('app.es.default_index');
		$this->findermailer                     =   $findermailer;
		$this->cacheapi                     =   $cacheapi;
		$this->appOfferDiscount 				= Config::get('app.app.discount');
		$this->appOfferExcludedVendors 				= Config::get('app.app.discount_excluded_vendors');
		$this->utilities 						= $utilities;

		$this->vendor_token = false;

        $vendor_token = Request::header('Authorization-Vendor');

        $this->kiosk_app_version = false;

        if($vendor_token){

            $this->vendor_token = true;

            $this->kiosk_app_version = (float)Request::header('App-Version');
		}

		$this->error_status = ($this->vendor_token) ? 200 : 400;
		
		
	}



	public function acceptVendorMou($mouid){


		$vendormou = Vendormou::with(array('finder'=>function($query){$query->select('_id','title','slug');}))->find(intval($mouid));

		if($vendormou){

			$vendormouData =    $vendormou->toArray();

			return $this->findermailer->acceptVendorMou($vendormouData);

		}


	}


	public function cancelVendorMou($mouid){


		$vendormou = Vendormou::with(array('finder'=>function($query){$query->select('_id','title','slug');}))->find(intval($mouid));

		if($vendormou){

			$vendormouData =    $vendormou->toArray();

			return $this->findermailer->cancelVendorMou($vendormouData);
		}

	}

	public function finderdetail($slug, $cache = true){

		
		$data   =  array();
		$tslug  = (string) strtolower($slug);

		if($tslug == "default" && isset($_GET['vendor_id']) && $_GET['vendor_id'] != ""){

			$vendor = Finder::find((int)$_GET['vendor_id'],["slug"]);

			if($vendor){
				$tslug = $vendor->slug;
			}else{
				return Response::json(array("status"=>404), 404);
			}
		}
		
		$cache_key = $tslug;
		
		$category_slug = null;
		if(isset($_GET['category_slug']) && $_GET['category_slug'] != ''){
			// Log::info("Category exists");
			$category_slug = $_GET['category_slug'];
			$cache_key  = $tslug.'-'.$category_slug;
		}

		$customer_email = null;
		
		$jwt_token = Request::header('Authorization');

		if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
			
			$decoded = $this->customerTokenDecode($jwt_token);
			
			if($decoded){
				$customer_email = $decoded->customer->email;
			}

		}

		$cache_key = $this->updateCacheKey($cache_key);

		if(in_array($tslug, Config::get('app.test_vendors'))){
			if($customer_email){
				
				if(!in_array($customer_email, Config::get('app.test_page_users'))){

					return Response::json("not found", 404);
				}

			}else{

				return Response::json("not found", 404);
			}
		}

		$finder_detail = $cache ? Cache::tags('finder_detail')->has($cache_key) : false;
		if(!$finder_detail){
			$campaign_offer = false;
			//Log::info("Not cached in detail");
			Finder::$withoutAppends=true;
			Service::$withoutAppends=true;
			Service::$setAppends=['active_weekdays','serviceratecard'];
			$finderarr = Finder::active()->where('slug','=',$tslug)
				->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				// ->with('findercollections')
				// ->with('blogs')
				// ->with('categorytags')
				->with('locationtags')
				->with('offerings')
				->with('facilities')
				->with(array('ozonetelno'=>function($query){$query->select('*')->where('status','=','1');}))
				->with(array('knowlarityno'=>function($query){$query->select('*')->where('status',true);}))
				->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->where('status','=','1')->orderBy('ordering', 'ASC');}))
				->with(array('reviews'=>function($query){$query->select('*')->where('status','=','1')->orderBy('updated_at', 'DESC')->limit(5);}))
				// ->with(array('reviews'=>function($query){$query->select('*')->where('status','=','1')->orderBy('_id', 'DESC');}))
				->first();

			unset($finderarr['ratecards']);

			$finder = null;	
			
			if($finderarr){

				// $ratecards           =   Ratecard::with('serviceoffers')->where('finder_id', intval($finder_id))->orderBy('_id', 'desc')->get();
				$finderarr = $finderarr->toArray();

				if(isset($finderarr['commercial_type']) && $finderarr['commercial_type']==0){
					if(isset($finderarr['budget'])){
						if($finderarr['budget'] < 1000){
							$finderarr['budget'] = "Less than Rs. 1000";
						}else if($finderarr['budget'] >= 1000 && $finderarr['budget'] < 2500){
							$finderarr['budget'] = "Rs. 1000- Rs. 2500";
						}else if($finderarr['budget'] >= 2500 && $finderarr['budget'] < 5000){
							$finderarr['budget'] = "Rs. 2500- Rs. 5000";
						}else if($finderarr['budget'] >= 5000 && $finderarr['budget'] < 7500){
							$finderarr['budget'] = "Rs. 5000- Rs. 7500";
						}else if($finderarr['budget'] >= 7500 && $finderarr['budget'] < 15000){
							$finderarr['budget'] = "Rs. 7500- Rs. 15000";
						}else {
							$finderarr['budget'] = "Above Rs. 15000";
						}
					}else{
							$finderarr['budget'] = "";
					}
				}

				if(!empty($finderarr['reviews'])){

					foreach ($finderarr['reviews'] as $rev_key => $rev_value) {

						if($rev_value['customer'] == null){

							$finderarr['reviews'][$rev_key]['customer'] = array("id"=>0,"name"=>"A Fitternity User","picture"=>"https://www.gravatar.com/avatar/0573c7399ef3cf8e1c215cdd730f02ec?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png");
						}
					}
				}

				// Check if there are any events running on this vendor
				$finderevent = DbEvent::where('vendors',$finderarr['_id'])
					->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
					->get(array('name','slug','venue','start_date','end_date'));
				$finderarr['events'] = $finderevent;
				// End of event check

				// return  pluck( $finderarr['categorytags'] , array('name', '_id') );
				$finder         =   array_except($finderarr, array('coverimage','findercollections','categorytags','locationtags','offerings','facilities','services','blogs'));

				$coverimage     =   ($finderarr['coverimage'] != '') ? $finderarr['coverimage'] : 'default/'.$finderarr['category_id'].'-'.rand(1, 4).'.jpg';
				array_set($finder, 'coverimage', $coverimage);

				$finder['today_opening_hour'] =  null;
				$finder['today_closing_hour'] = null;


				if(isset($finder['flags'])){
					if(!isset($finder['flags']['state'])){
						$finder['flags']['state'] = "open";
					}
				}else{
					$finder['flags'] = array('state'=>"open");
				}
				$detail_rating_array = array('detail_rating_summary_average','detail_rating_summary_count');

				foreach ($detail_rating_array as $value){

					$finder[$value] =  [0,0,0,0,0];

					if(isset($finderarr[$value]) && $finderarr[$value] != "" && is_array($finderarr[$value])){

						$detail_rating_summary = array();

						for ($i=0; $i < 5; $i++) {

							$detail_rating_summary[$i] = 0;

							if(isset($finderarr[$value][$i])){

								$detail_rating_summary[$i] = $finderarr[$value][$i];

								if($finderarr[$value][$i] == null){
									$detail_rating_summary[$i] = 0;
								}
							}
						}

						$finder[$value] = $detail_rating_summary;

					}
				}
				if(isset($finderarr['category']['name'])){
					$newcat = newcategorymapping(strtolower($finderarr['category']['name']));
					$finder["breadcrumb"] = array("link"=>"/".$finderarr['city']['slug']."/".$finderarr['location']['slug']."/".str_replace(" ","-",$newcat));
				}
				if(isset($finderarr['category_id']) && $finderarr['category_id'] == 5){
					if(isset($finderarr['services']) && count($finderarr['services']) > 0){
						//for servcie category gym
						$finder_gym_service  = [];
						$finder_gym_service = head(array_where($finderarr['services'], function($key, $value){
							if($value['category']['_id'] == 65){ return $value; }
						}));



                       // return $finder_gym_service; exit;

						if(isset($finder_gym_service['trialschedules']) && count($finder_gym_service['trialschedules']) > 0){
							$all_weekdays                       =   sort_weekdays($finder_gym_service['active_weekdays']);
							
							$today_weekday                      =   strtolower(date( "l", time()));

							foreach ($all_weekdays as $weekday){
								$whole_week_open_close_hour_Arr             =   [];
								$slots_start_time_24_hour_format_Arr        =   [];
								$slots_end_time_24_hour_format_Arr          =   [];

								$weekdayslots       =   head(array_where($finder_gym_service['trialschedules'], function($key, $value) use ($weekday){
									if($value['weekday'] == $weekday){
										return $value;
									}
								}));// weekdayslots

								if(isset($weekdayslots['slots']) && count($weekdayslots['slots']) > 0){
									foreach ($weekdayslots['slots'] as $key => $slot) {
//                                        return $slot;
										$find       =   ["am","pm"];
										$replace    =   [""];
										$start_time_surfix_arr  =   explode(":", trim(str_replace($find, $replace, $slot['start_time'])) );
										$start_time_surfix      =   (isset($start_time_surfix_arr[1])) ? $start_time_surfix_arr[1] : "";
										$strart_time            =   floatval($slot['start_time_24_hour_format'].".".$start_time_surfix);

										$end_time_surfix_arr  =   explode(":", trim(str_replace($find, $replace, $slot['end_time'])) );
										$end_time_surfix      =   (isset($end_time_surfix_arr[1])) ? $end_time_surfix_arr[1] : "";
										$end_time            =   floatval($slot['end_time_24_hour_format'].".".$end_time_surfix);

										array_push($slots_start_time_24_hour_format_Arr, $strart_time);
										array_push($slots_end_time_24_hour_format_Arr, $end_time);
									}

//                                    return $slots_start_time_24_hour_format_Arr;


									if(!empty($slots_start_time_24_hour_format_Arr) && !empty($slots_end_time_24_hour_format_Arr)){
										$opening_hour_arr       = explode(".",min($slots_start_time_24_hour_format_Arr));
										$opening_hour_surfix    = "";
										if(isset($opening_hour_arr[1])){
											$opening_hour_surfix = (strlen($opening_hour_arr[1]) == 1) ? $opening_hour_arr[1]."0" : $opening_hour_arr[1];
										}else{
											$opening_hour_surfix =  "00";
										}

										$opening_hour     = $opening_hour_arr[0].":".$opening_hour_surfix;

										$closing_hour_arr = explode(".",max($slots_end_time_24_hour_format_Arr));
										$closing_hour_surfix    = "";

										if(isset($closing_hour_arr[1])){
											$closing_hour_surfix = (strlen($closing_hour_arr[1]) == 0) ? "00" : "00";
											$closing_hour_surfix = (strlen($closing_hour_arr[1]) == 1) ? $closing_hour_arr[1]."0" : $closing_hour_arr[1];
										}else{
											$closing_hour_surfix =  "00";
										}

										$closing_hour     = $closing_hour_arr[0].":".$closing_hour_surfix;

//                                        return "$opening_hour  -- $closing_hour";
										//   $finder['opening_hour'] = min($slots_start_time_24_hour_format_Arr);
										//   $finder['closing_hour'] = max($slots_end_time_24_hour_format_Arr)
										if($today_weekday == $weekday){
											$finder['today_opening_hour'] =  date("g:i A", strtotime(str_replace(".",":",$opening_hour)));
											$finder['today_closing_hour'] = date("g:i A", strtotime(str_replace(".",":",$closing_hour)));
										}
										$whole_week_open_close_hour[$weekday]['opening_hour'] = date("g:i A", strtotime(str_replace(".",":",$opening_hour)));
										$whole_week_open_close_hour[$weekday]['closing_hour'] = date("g:i A", strtotime(str_replace(".",":",$closing_hour)));
										array_push($whole_week_open_close_hour_Arr, $whole_week_open_close_hour);
									}
								}
							}

							$finder['open_close_hour_for_week'] = (!empty($whole_week_open_close_hour_Arr) && count($whole_week_open_close_hour_Arr) > 0) ? head($whole_week_open_close_hour_Arr) : [];

						}// trialschedules

					}
				}

//                return  $finder;

				if(isset($finderarr['ozonetelno']) && $finderarr['ozonetelno'] != ''){
					$finderarr['ozonetelno']['phone_number'] = '+'.$finderarr['ozonetelno']['phone_number'];
					$finder['ozonetelno'] = $finderarr['ozonetelno'];
				}

				if(isset($finderarr['knowlarityno']) && $finderarr['knowlarityno'] != ''){
					$finderarr['knowlarityno']['phone_number'] = '+91'.$finderarr['knowlarityno']['phone_number'];
					$finderarr['knowlarityno']['extension'] = strlen($finderarr['knowlarityno']['extension']) < 2 && $finderarr['knowlarityno']['extension'] >= 1  ?  "0".$finderarr['knowlarityno']['extension'] : $finderarr['knowlarityno']['extension'];
					$finder['knowlarityno'] = $finderarr['knowlarityno'];
					$finder['ozonetelno'] = $finder['knowlarityno'];
				}
				// if($finderarr['city_id'] == 4 || $finderarr['city_id'] == 8 || $finderarr['city_id'] == 9){
				// 	$direct_Fitternity_delhi_vendors = [4929,4968,5027,5066,5145,5355,5603,5609,5617,5709,6047,6411,6412,6499,6534,6876,6895,6979,7136,7448,7657,7907,7909,8289,8837,8878,9125,9171,9178,9201,9337,9397,9415,9417,9600,9624,9726,9728,9876,9878,9888,9913,10245,10568,10570,10624,10847,10957,10962,10993,11034,11040,11134,11176,11274,11374,6993,10987,8470,8823,6446,9855,11028,11030,11031,9854];
				// 	if(in_array($finderarr["_id"],$direct_Fitternity_delhi_vendors)){
				// 		$finder['contact']['phone'] = Config::get('app.contact_us_customer_number');
				// 	}else{
				// 		$finder['contact']['phone'] = $finderarr['contact']['phone'];
				// 	}
				// 	unset($finder['ozonetelno']);
				// }

				$finder['review_count']     =   isset($finderarr["total_rating_count"]) ? $finderarr["total_rating_count"] : 0;

				$finder['offer_icon'] = "";
				$finder['offer_icon_mob'] = "";

				$finder['associate_finder'] = null;

				// Check
				if(isset($finderarr['associate_finder']) && $finderarr['associate_finder'] != ''){

					$associate_finder = array_map('intval',$finderarr['associate_finder']);
					$associate_finder = Finder::active()->whereIn('_id',$associate_finder)->get(array('_id','title','slug'))->toArray();
					$finder['associate_finder'] = $associate_finder;
				}
				// End Check
				$traction_exists = false;
				foreach($finderarr['services'] as &$service){
					if(!isset($service['traction']) || !isset($service['traction']['sales']) || !isset($service['traction']['trials'])){
						$service['traction'] = array('trials'=>0, 'sales'=>0);
					}else{
						$traction_exists = true;
					}
				}

				function cmp($a, $b)
				{
					return $a['traction']['sales']+$a['traction']['trials']*0.8 <= $b['traction']['sales']+$b['traction']['trials']*0.8;
				}
				if($traction_exists){
					usort($finderarr['services'], "cmp");
				}
				
				$category_slug_services = array();
				$category_slug_services = array_where($finderarr['services'], function($key, $value) use ($category_slug){
							if($value['category']['slug'] == $category_slug)
								{
								 return $value; 
								}
						});

				$non_category_slug_services = array();
				$non_category_slug_services = array_where($finderarr['services'], function($key, $value) use ($category_slug){
							if($value['category']['slug'] != $category_slug)
								{
								 return $value; 
								}
						});


	        	//usort($category_slug_services, "cmp");
	        	//usort($non_category_slug_services, "cmp");
	        	
	        	$finderarr['services'] = array_merge($category_slug_services, $non_category_slug_services);


				$finderarr['services'] = $this->sortNoMembershipServices($finderarr['services'], 'finderdetail');
				


				
				array_set($finder, 'services', pluck( $finderarr['services'] , ['_id', 'name', 'lat', 'lon', 'serviceratecard', 'session_type', 'workout_tags', 'calorie_burn', 'workout_results', 'short_description','service_trainer','timing','category','subcategory','batches','vip_trial','meal_type','trial','membership', 'offer_available', 'showOnFront', 'traction', 'timings', 'flags']  ));
				array_set($finder, 'categorytags', pluck( $finderarr['categorytags'] , array('_id', 'name', 'slug', 'offering_header') ));
				// array_set($finder, 'findercollections', pluck( $finderarr['findercollections'] , array('_id', 'name', 'slug') ));
				// array_set($finder, 'blogs', pluck( $finderarr['blogs'] , array('_id', 'title', 'slug', 'coverimage') ));
				array_set($finder, 'locationtags', pluck( $finderarr['locationtags'] , array('_id', 'name', 'slug') ));
				array_set($finder, 'offerings', pluck( $finderarr['offerings'] , array('_id', 'name', 'slug') ));
				array_set($finder, 'facilities', pluck( $finderarr['facilities'] , array('_id', 'name', 'slug') ));
				
			   //return $finderarr['services'];

				if(count($finder['photos']) > 0 ){
					$photoArr        =   [];
					usort($finder['photos'], "sort_by_order");
					foreach ($finder['photos'] as $photo) {
						$servicetags                =   (isset($photo['servicetags']) && count($photo['servicetags']) > 0) ? Service::whereIn('_id',$photo['servicetags'])->lists('name') : [];
						$photoObj                   =   array_except($photo,['servicetags']);
						$photoObj['servicetags']    =   $servicetags;
						$photoObj['tags']              =  (isset($photo['tags']) && count($photo['tags']) > 0) ? $photo['tags'] : []; 
						array_push($photoArr, $photoObj);
					}
					array_set($finder, 'photos', $photoArr);
// //                    print_pretty($photoArr);exit;

// 					$service_tags_photo_arr             =   [];
// 					$info_tags_photo_arr                =   [];

// 					if(count($photoArr) > 0 ) {
// 						$unique_service_tags_arr    =   array_unique(array_flatten(array_pluck($photoArr, 'servicetags')));
// 						$unique_info_tags_arr       =   array_unique(array_flatten(array_pluck($photoArr, 'tags')));

// 						foreach ($unique_service_tags_arr as $unique_service_tags) {
// 							$service_tags_photoObj = [];
// 							$service_tags_photoObj['name'] = $unique_service_tags;
// 							$service_tags_photos = array_where($photoArr, function ($key, $value) use ($unique_service_tags) {
// 								if (in_array($unique_service_tags, $value['servicetags'])) {
// 									return $value;
// 								}
// 							});
// 							$service_tags_photoObj['photo'] = array_values($service_tags_photos);
// 							array_push($service_tags_photo_arr, $service_tags_photoObj);
// 						}

// 						foreach ($unique_info_tags_arr as $unique_info_tags) {
// 							$info_tags_photoObj = [];
// 							$info_tags_photoObj['name'] = $unique_info_tags;
// 							$info_tags_photos = array_where($photoArr, function ($key, $value) use ($unique_info_tags) {
// 								if (in_array($unique_info_tags, $value['tags'])) {
// 									return $value;
// 								}
// 							});
// 							$info_tags_photoObj['photo'] = array_values($info_tags_photos);
// 							array_push($info_tags_photo_arr, $info_tags_photoObj);
// 						}
// 					}

// 					array_set($finder, 'photo_service_tags', array_values($service_tags_photo_arr));
// 					array_set($finder, 'photo_info_tags', array_values($info_tags_photo_arr));

				}
				// $finder['offer_icon'] = "https://b.fitn.in/iconsv1/womens-day/womens-day-mobile-banner.svg";
				$finder['pay_per_session'] = true;
				if(isset($finder['trial']) && $finder['trial']=='disable' || $finder['commercial_type']== 0){
					$finder['pay_per_session'] = false;
				}
				$pay_per_session = false;

				$info_timing = $this->getInfoTiming($finder['services']);

					if(isset($finder['info']) && $info_timing != ""){
						$finder['info']['timing'] = $info_timing;
				}

				$payment_options_data = [
	               	'emi'=>false,
	               	'cash_pickup'=>false,
	               	'part_payment'=>false
               	];
				
				if(isset($finder['flags']) && isset($finder['flags']['campaign_offer']) && $finder['flags']['campaign_offer']){
					$campaign_offer = true;
					$finder['campaign_text'] = "Womens day";
				}
				
				
				// start top selling and newly launched logic 
				
				
				if(isset($finderarr['flags']) && isset($finderarr['flags']['top_selling']))
					if($finderarr['flags']['top_selling'])		
						 $finder['flags']['top_selling']=true;
				    else unset($finder['flags']['top_selling']);
				
				if(isset($finderarr['flags']) && isset($finderarr['flags']['newly_launched']) && $finderarr['flags']['newly_launched'] != null && $finderarr['flags']['newly_launched'] != "" && isset($finderarr['flags']['newly_launched_date']) && $finderarr['flags']['newly_launched_date'] != null && $finderarr['flags']['newly_launched_date'] != ""){

					if($finderarr['flags']['newly_launched']&&$finderarr['flags']['newly_launched_date']){

						$launchedTime=strtotime($finderarr['flags']['newly_launched_date']);	
						$date1=date_create(date("Y/m/d"));
						$date2=date_create(date('Y/m/d',$finderarr['flags']['newly_launched_date']->sec));
						$diff=date_diff($date1,$date2);
						Log::info(" info diff ".print_r($diff,true));
						if($diff->invert>0)
						{
							if($diff->days<=30)
								$finder['flags']['newly_launched']='newly launched';
						}
						else $finder['flags']['newly_launched']='coming soon';	
						unset($finder['flags']['newly_launched_date']);
					}else{

						unset($finder['flags']['newly_launched_date']);
						unset($finder['flags']['newly_launched']);
					}

				}else if(isset($finderarr['flags'])){

					if(isset($finderarr['flags']['newly_launched']) || $finderarr['flags']['newly_launched'] == null){
						unset($finder['flags']['newly_launched']);
					}

					if(isset($finderarr['flags']['newly_launched_date']) || $finderarr['flags']['newly_launched_date'] == null){

						unset($finder['flags']['newly_launched_date']);
					}
				}
				
				// end top selling and newly launched logic 
				
				
				// return $info_timing;
				if(count($finder['services']) > 0 ){

					$serviceArr                             =   [];
					$sericecategorysCalorieArr              =   Config::get('app.calorie_burn_categorywise');
					$sericecategorysWorkoutResultArr        =   Config::get('app.workout_results_categorywise');

					foreach ($finder['services'] as $key => $service){

						// if(!isset($service['showOnFront']) || ((isset($service['showOnFront']) && $service['showOnFront']))){
						if((!isset($service['showOnFront']) || ((isset($service['showOnFront']) && in_array('web', $service['showOnFront'])))) && count($service['serviceratecard'])){ 



							$service = $service;

							$service['offer_icon'] = "";
							
							// if(isset($service['offer_available']) && $service['offer_available'] == true && !in_array($finder['_id'], Config::get('app.hot_offer_excluded_vendors'))){
								
							// 	$service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/mob_offer_ratecard.png";
							// }
							
							
							if(!isset($finder['campaign_text']) && isset($service['flags']) && isset($service['flags']['campaign_offer']) && $service['flags']['campaign_offer']){
								$campaign_offer = true;
								$service['campaign_text'] = "<strong>Additional Flat 30%</strong> for Women";
							}
							

							if(isset($service['category']) && isset($service['category']['_id'])){
								$category_id                =   intval($service['category']['_id']);

								//calorie_burn
								$category_calorie_burn      =   300;
								if(isset($service['calorie_burn']) && $service['calorie_burn']['avg'] != 0){
									$category_calorie_burn = $service['calorie_burn']['avg'];
								}else{
									if(isset($sericecategorysCalorieArr[$category_id])){
										$category_calorie_burn = $sericecategorysCalorieArr[$category_id];
									}
								}
								$service['calorie_burn']    = $category_calorie_burn;

								//workout_results
								$category_workout_result    =   [];
								if(isset($sericecategorysWorkoutResultArr[$category_id])){
									$category_workout_result = $sericecategorysWorkoutResultArr[$category_id];
								}
								$service['workout_results']     =   $category_workout_result;
								$service['membership']          =   (isset($service['membership'])) ? $service['membership'] : "";
								$service['trial']               =   (isset($service['trial'])) ? $service['trial'] : "";

								if(in_array($category_id, [26, 25, 46, 41])){
									unset($service['calorie_burn']);
									unset($service['short_description']);
									unset($service['workout_results']);
								}

							}


							if(count($service['serviceratecard']) > 0){

								foreach ($service['serviceratecard'] as $ratekey => $rateval){

									if((isset($rateval['expiry_date']) && $rateval['expiry_date'] != "" && strtotime("+ 1 days", strtotime($rateval['expiry_date'])) < time()) || (isset($rateval['start_date']) && $rateval['start_date'] != "" && strtotime($rateval['start_date']) > time())){
										unset($service['serviceratecard'][$ratekey]);
										continue;
									}

									// if(in_array($rateval['type'], ['membership', 'packages']) && !isset($finder['campaign_text']) && !isset($service['campaign_text']) && isset($rateval['flags']) && isset($rateval['flags']['campaign_offer']) && $rateval['flags']['campaign_offer']){
									if(in_array($rateval['type'], ['membership', 'packages']) && (isset($finder['campaign_text'])  || isset($service['campaign_text']) || (isset($rateval['flags']) && isset($rateval['flags']['campaign_offer']) && $rateval['flags']['campaign_offer']))){
										$campaign_offer = true;
										// $service['serviceratecard'][$ratekey]['campaign_text'] = "(Women - Get additional 30% off)";
									}
									// if(isset($rateval['flags']) && isset($rateval['flags']["offerFor"]) && $rateval['flags']["offerFor"] == "women"){
									// 	$service['serviceratecard'][$ratekey]['campaign_text'] = "Women only offer";
									// 	if(!isset($rateval['offers'])){
									// 		$service['serviceratecard'][$ratekey]['offers'] = [
									// 			[
									// 				"offer_icon"=>"https://b.fitn.in/global/finder/women-offer2.png",
									// 			]
									// 			];
									// 	}else{
									// 		$service['serviceratecard'][$ratekey]['offers'][0]['offer_icon'] = "https://b.fitn.in/global/finder/women-offer2.png";
									// 	}
									// }
									
									if(isset($service['membership']) && $service['membership']=='manual'){
										$service['serviceratecard'][$ratekey]['direct_payment_enable'] = "0";
									}
									
									$customerDiscount = $this->utilities->getCustomerDiscount();

									$final_price = 0;
							
									$discount = $customerDiscount;
									if($rateval['special_price'] > 0){
										$discount_amount = intval($rateval['special_price'] * ($discount/100));
										$final_price = $service['serviceratecard'][$ratekey]['special_price'] = $rateval['special_price'] - $discount_amount;
									}else{
										$discount_amount = intval($rateval['price'] * ($discount/100));
										$final_price = $service['serviceratecard'][$ratekey]['price'] = $rateval['price'] - $discount_amount;
									}


									if($final_price >= 3000){
										$payment_options_data['emi'] = true;
										$payment_options_data['cash_pickup'] = true;
 									}

									// Removing womens offer ratecards if present
									// if(isset($rateval['flags'])){

									// 	if(isset($rateval['flags']['discother']) && $rateval['flags']['discother'] == true){
									// 		if(isset($service['serviceratecard'][$ratekey]['offers']) && count($service['serviceratecard'][$ratekey]['offers']) == 0){
									// 			unset($service['serviceratecard'][$ratekey]);
									// 			continue;
									// 		}
									// 	}

									// 	if(isset($rateval['flags']['disc25or50']) && $rateval['flags']['disc25or50'] == true){
									// 		if(isset($service['serviceratecard'][$ratekey]['offers']) && count($service['serviceratecard'][$ratekey]['offers']) == 0){
									// 			unset($service['serviceratecard'][$ratekey]);
									// 			continue;
									// 		}
									// 	}
									// }
									// if(isset($rateval['flags']) && ((isset($rateval['flags']['disc25or50']) && $rateval['flags']['disc25or50']) || (isset($rateval['flags']['discother']) && $rateval['flags']['discother']))){
									// 	$finder['offer_icon'] = "https://b.fitn.in/iconsv1/womens-day/women-day-banner.svg";
									// 	$finder['offer_icon_mob'] = "https://b.fitn.in/iconsv1/womens-day/exclusive.svg";
									// }
									// else{
									// 	$finder['offer_icon'] = "https://b.fitn.in/iconsv1/womens-day/womens-day-mobile-banner.svg";
									// }
									// if(!empty($rateval['_id']) && isset($rateval['_id'])){

									// 	$ratecardoffersRecardsCount  =   Offer::where('ratecard_id', intval($rateval['_id']))->where('hidden', false)->orderBy('order', 'asc')
									// 		->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
									// 		->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
									// 		->count();

									// 	if($ratecardoffersRecardsCount > 0){  

									// 		$service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/offer_available_vendor.png";
									// 	}
									// }
								}
							}

							if((isset($finderarr['membership']) && $finderarr['membership'] == 'disable') || isset($service['membership']) && $service['membership'] == 'disable'){
								$service['offer_available'] = false;
							}
							$service['pay_per_session'] = false;

							if(isset($finder['pay_per_session']) && $finder['pay_per_session'] && isset($finder['trial']) && $finder['trial'] != 'disable' && isset($service['trial']) && $service['trial'] != 'disable'){
								foreach($service['serviceratecard'] as $ratecard){
									if($ratecard['type']=='workout session'){

										$service['pay_per_session'] = true;
										$pay_per_session = true;
									
									}
								}
							}

							if(!$pay_per_session){
								$finder['pay_per_session'] = false;
							}
							array_push($serviceArr, $service);
						}
					}



					array_set($finder, 'services', $serviceArr);

					// $info_timing = $this->getInfoTiming($finder['services']);

					// if(isset($finder['info']) && $info_timing != ""){
					// 	$finder['info']['timing'] = $info_timing;
					// }

					
				}

				if(count($finder['offerings']) > 0 ){
					$tempoffering = [];
					$tempofferingname = [];
					foreach ($finder['offerings'] as $offering) {
						if(in_array(strtolower($offering["name"]),$tempofferingname)){

						}else{
							array_push($tempoffering,$offering);
							array_push($tempofferingname,strtolower($offering["name"]));
						}
					}
					$finder['offerings'] = $tempoffering;
				}

				// $fitmania_offer_cnt     =   Serviceoffer::where('finder_id', '=', intval($finderarr['_id']))->where("active" , "=" , 1)->whereIn("type" ,["fitmania-dod", "fitmania-dow","fitmania-membership-giveaways"])->count();
				// if($fitmania_offer_cnt > 0){
				// 	array_set($finder, 'fitmania_offer_exist', true);
				// }else{
				// 	array_set($finder, 'fitmania_offer_exist', false);
				// }

				if(isset($finderarr['brand_id']) && $finderarr['city_id'] != 10000){

					$brand = Brand::find((int)$finderarr['brand_id']);

					$brandFinder = array();

					$brandFinder = Finder::where("brand_id",(int)$finderarr['brand_id'])
					->active()
					->where("_id","!=",(int)$finderarr['_id'])
					->where('city_id',(int)$finderarr['city_id'])
					// ->with('offerings')
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->with(array('city'=>function($query){$query->select('_id','name','slug');}))
					->with(array('category'=>function($query){$query->select('_id','name','slug');}))
					->take(5)
					->get(['_id','title','slug','brand_id','location_id','city_id','average_rating','finder_coverimage','coverimage', 'category_id']);

					// if(count($brandFinder) > 0){

					// 	$brandFinder = $brandFinder->toArray();
					
					// 	foreach($brandFinder as $key => $finder1){
					// 		array_set($brandFinder[$key], 'offerings', pluck( $finder1['offerings'] , array('_id', 'name', 'slug') ));
					// 	}

					// }

					$finderarr['brand']['brand_detail'] = $brand;
					$finderarr['brand']['finder_detail'] = $brandFinder;
					$finder['brand'] = $finderarr['brand'];

				}


				if(isset($finderarr['category_id']) && $finderarr['category_id'] == 41){
					$finder['trial'] = 'disable';
					$finder['membership'] = 'disable';
				}

			}

			if($finder){

				$categoryTagDefinationArr     =   [];
				
				// $Findercategory         =   Findercategory::find(intval($finderarr['category_id']));                

				$findercategorytag_ids      =   array_pluck(pluck( $finderarr['categorytags'] , array('_id')), '_id');
				$Findercategorytags         =   Findercategorytag::whereIn('_id', $findercategorytag_ids)->get()->toArray();

				foreach ($Findercategorytags as $key => $Findercategorytag) {
					if(isset($Findercategorytag['defination']) && isset($Findercategorytag['defination']) && count($Findercategorytag['defination']) > 0){
						$maxCnt                                 =   count($Findercategorytag['defination']) - 1;
						$categoryTagDefination['slug']          =   $Findercategorytag['slug'];
						$categoryTagDefination['defination']    =   $Findercategorytag['defination'][rand(0,$maxCnt)];
						array_push($categoryTagDefinationArr, $categoryTagDefination);
					}
				}

				if(!isset($finder['callout']) || trim($finder['callout']) == ''){
					Log::info("inside");
					$callout_offer = Offer::where('vendor_id', $finder['_id'])->where('hidden', false)->orderBy('order', 'asc')
									->where('offer_type', 'newyears')
									->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
									->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
									->first();
					Log::info($callout_offer);
					if($callout_offer){

						$device = $this->vendor_token ? 'kiosk' : 'web';
						$callout_service = Service::active()->where('_id', $callout_offer['vendorservice_id'])->where(function($query) use ($device){return $query->orWhere('showOnFront', 'exists', false)->orWhere('showOnFront', $device);})->first();
						$callout_ratecard = Ratecard::find($callout_offer['ratecard_id']);
						Log::info($callout_ratecard);
						if($callout_service && $callout_ratecard){
							$finder['callout'] = $callout_service['name']." - ".$this->getServiceDuration($callout_ratecard)." @ Rs. ".$callout_offer['price'];
						}
					}
				}

				if(isset($finder['services']) && count($finder['services'])>0){

					$cheapest_price = $this->getCheapestWorkoutSession($finder['services']);

					if($cheapest_price > 0){
						$finder['cheapest_workout_session'] = $cheapest_price;
					}
					
				}

				$finderdata         =   $finder;
				$finderid           = (int) $finderdata['_id'];
				$finder_cityid      = (int) $finderdata['city_id'];
				$findercategoryid   = (int) $finderdata['category_id'];
				$finderlocationid   = (int) $finderdata['location_id'];

				$skip_categoryid_finders    = [41,42,45,25,46,10,26,40];

				


				$nearby_same_category_request = [
                    "offset" => 0,
                    "limit" => 4,
                    "radius" => "3km",
                    "category"=>newcategorymapping($finderdata["category"]["name"]),
                    "lat"=>$finderdata["lat"],
                    "lon"=>$finderdata["lon"],
                    "city"=>strtolower($finderdata["city"]["name"]),
                    "keys"=>[
                      "average_rating",
                      "business_type",
                      "commercial_type",
                      "coverimage",
                      "location",
					  "subcategories",
					  "categorytags",
                      "slug",
                      "name",
                      "id",
                      "city",
                      "category"
                    ],
                    "not"=>[
                    	"vendor"=>[(int)$finderdata["_id"]]
                    ]
                ];

                $nearby_same_category = geoLocationFinder($nearby_same_category_request);

				$nearby_other_category_request = [
                    "offset" => 0,
                    "limit" => 4,
                    "radius" => "3km",
                    "category"=>"",
                    "lat"=>$finderdata["lat"],
                    "lon"=>$finderdata["lon"],
                    "city"=>strtolower($finderdata["city"]["name"]),
                    "keys"=>[
                      "average_rating",
                      "business_type",
                      "commercial_type",
                      "coverimage",
                      "location",
					  "subcategories",
					  "categorytags",
                      "slug",
                      "name",
                      "id",
                      "city",
                      "category"
                    ],
                    "not"=>[
                    	"vendor"=>[(int)$finderdata["_id"]],
                    	"category"=>[newcategorymapping($finderdata["category"]["name"])]
                    ]
                ];

                $nearby_other_category = geoLocationFinder($nearby_other_category_request);

                // $nearby_same_category = array();

				// $nearby_same_category       =   Finder::where('category_id','=',$findercategoryid)
				// ->where('commercial_type','!=', 0)
				// ->where('location_id','=',$finderlocationid)
				// ->where('_id','!=',$finderid)
				// ->where('status', '=', '1')
				// ->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
				// ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				// ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				// // ->with('offerings')
				// ->orderBy('finder_type', 'DESC')
				// ->remember(Config::get('app.cachetime'))
				// ->take(5)
				// ->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','finder_coverimage','offerings'));

				// $nearby_other_category = array();    

				// $nearby_other_category      =   Finder::where('category_id','!=',$findercategoryid)
				// ->whereNotIn('category_id', $skip_categoryid_finders)
				// ->where('commercial_type','!=', 0)
				// ->where('location_id','=',$finderlocationid)
				// ->where('_id','!=',$finderid)
				// ->where('status', '=', '1')
				// ->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
				// ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				// ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				// // ->with('offerings')
				// ->orderBy('finder_type', 'DESC')
				// ->remember(Config::get('app.cachetime'))
				// ->take(5)
				// ->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','finder_coverimage','offerings'));


				if($finder['city_id'] == 10000){
					$finder['city']['name'] = $finder['custom_city'];
					$finder['location']['name'] = $finder['custom_location'];
					$nearby_same_category = [];
					$nearby_other_category = [];
				}

				$finder_footer = $cache ? Cache::tags('finder_footer')->has($finderdata["location"]["slug"]) : false;

				if(!$finder_footer){

					$finder_footer = $this->vendorFooter($finderdata);

					Cache::tags('finder_footer')->put($finderdata["location"]["slug"],$finder_footer,1440);

				}else{

					$finder_footer = Cache::tags('finder_footer')->get($finderdata["location"]["slug"]);
				}

				$finder['photo_facility_tags'] = ['All'];
				
				$finder['photo_service_tags'] = ['All'];

				$photo_facility_tags_others_count = 0;
				$photo_service_tags_others_count = 0;
				
				foreach($finder['photos'] as $photo){
				
					$finder['photo_facility_tags'] = array_merge($finder['photo_facility_tags'], $photo['tags']);

					// if(count($photo['tags']) == 0){
					// 	$photo_facility_tags_others_count += 1;
					// }
				
					$finder['photo_service_tags'] = array_merge($finder['photo_service_tags'], $photo['servicetags']);

					// if(count($photo['servicetags']) == 0){
					// 	$photo_service_tags_others_count += 1;
					// }
				
				}

				$finder['photo_facility_tags'] =  array_count_values($finder['photo_facility_tags']);

				$finder['photo_service_tags'] =  array_count_values($finder['photo_service_tags']);


				
				$finder['photo_facility_tags']['All'] = count($finder['photos']);
				$finder['photo_service_tags']['All'] = count($finder['photos']);
				
				// if(count($finder['photo_facility_tags'])>1 && $photo_facility_tags_others_count>0){
				// 	$finder['photo_facility_tags']['Others'] = $photo_facility_tags_others_count;
				// }

				// if(count($finder['photo_service_tags'])>1 && $photo_service_tags_others_count>0){
				// 	$finder['photo_service_tags']['Others'] = $photo_service_tags_others_count;
				// }

				$video_service_tags = ['All'];
				$video_service_tags_others_count = 0;
				
				foreach($finder['videos'] as $key => $video){
					$service_names = Service::whereIn('_id', $video['servicetags'])->lists('name');
					$video_service_tags = array_merge($video_service_tags, $service_names);
					$finder['videos'][$key]['servicetags'] = $service_names;

					if(count($service_names)){
						$video_service_tags_others_count += 1;
					}
				}
				
				$finder['video_service_tags'] = array_count_values($video_service_tags);
				
				$finder['video_service_tags']['All'] = count($finder['videos']);

				// if(count($finder['video_service_tags'])>1 && $video_service_tags_others_count>0){
				// 	$finder['video_service_tags']['Others'] = $video_service_tags_others_count;
					
				// }
				
				$finder['title'] = str_replace('crossfit', 'CrossFit', $finder['title']);
				$response['statusfinder']                   =       200;
				$response['finder']                         =       $finder;
				$response['defination']                     =       ['categorytags' => $categoryTagDefinationArr];
				$response['nearby_same_category']           =       $nearby_same_category;
				$response['nearby_other_category']          =       $nearby_other_category;
				$response['show_reward_banner'] = true;
				$response['finder_footer']				= 		$finder_footer;
				$response['finder']['payment_options']				=		$this->getPaymentModes($payment_options_data);
				// if($campaign_offer){
				// 	$response['vendor_stripe_data']	=	[
				// 		'text'=> "#STRONGGETSSTRONGER | <strong>FLAT 30% OFF FOR WOMEN</strong>",
				// 		'text_color'=> '#ffffff',
				// 		'background'=> '-webkit-linear-gradient(left, #FE7E87 0%, #FA5295 100%)',
				// 		'background-color'=> ''
				// 	];
				// }else if($finder['commercial_type']!=0 && !(isset($finder['flags']) && in_array($finder['flags'], ['closed', 'temporarily_shut'])) && !(isset($finder['membership']) && $finder['membership']=='disable' && isset($finder['trial']) && $finder['trial']=='disable') ){
				// 	$response['vendor_stripe_data']	=	[
				// 		'text'=> "#STRONGGETSSTRONGER | <strong>Special Surprise Discount</strong> For Women | <span class=\"code\">CODE: WFIT</span>",
				// 		'text_color'=> '#ffffff',
				// 		'background'=> '-webkit-linear-gradient(left, #FE7E87 0%, #FA5295 100%)',
				// 		'background-color'=> ''
				// 	];
                // }
                
                // if(in_array($response['finder']['_id'], [14,10315,10861,10863,10868,10870,10872,10875,10876,10877,10880,10883,10886,10887,10888,10890,10891,10892,10894,10895,10897,10900,12246,12247,12250,12252,12254])){
                //     $response['vendor_stripe_data']	=	[
                //         'text'=> "Get additional 25% cashback as Fitcash on 1 year membership",
                //         'text_color'=> '#ffffff',
                //         'background'=> '-webkit-linear-gradient(left, #71b2c7 0%, #71b2c7 100%)',
                //         'background-color'=> ''
                //     ];
                // }
				
				// if(in_array($response['finder']['_id'], [4823,4819,4817,4824,4826])){
                //     $response['vendor_stripe_data']	=	[
                //         'text'=> "5% Discount + 5% Cashback as FitCash on 1-year Membership",
                //         'text_color'=> '#ffffff',
                //         'background'=> '-webkit-linear-gradient(left, #FE7E87 0%, #FA5295 100%)',
                //         'background-color'=> ''
                //     ];
				// }
				
				if(isset($finder['commercial_type']) && $finder['commercial_type'] == 0){

					unset($response['finder']['payment_options']);
				}

				if(isset($finder['flags']) && isset($finder['flags']['state']) && in_array($finder['flags']['state'],['closed','temporarily_shut'])){

					$response['finder']['membership'] = "disable";
					$response['finder']['trial'] = "disable";

					unset($response['finder']['payment_options']);
				}

				

				Cache::tags('finder_detail')->put($cache_key,$response,Config::get('cache.cache_time'));

			}else{

				$updatefindersulg       = Urlredirect::whereIn('oldslug',array($tslug))->firstOrFail();
				$data['finder']         = $updatefindersulg->newslug;
				$data['statusfinder']   = 404;

				return Response::json($data);
			}

		}else{

			$response = Cache::tags('finder_detail')->get($cache_key);
		}

		if(Request::header('Authorization')){
			$decoded                            =       decode_customer_token();
			$customer_email                     =       $decoded->customer->email;
			$customer_phone                     =       $decoded->customer->contact_no;
			$customer_trials_with_vendors       =       Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->where('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
			->where('finder_id', '=', (int) $response['finder']['_id'])
			->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
			->get(array('id'));
			$response['trials_detials']              =      $customer_trials_with_vendors;
			$response['trials_booked_status']        =      (count($customer_trials_with_vendors) > 0) ? true : false;
		}else{
			$response['trials_detials']              =      [];
			$response['trials_booked_status']        =      false;
		}
		// if($response['finder']['offer_icon'] == ""){
		// 	$response['finder']['offer_icon']        =        "https://b.fitn.in/iconsv1/womens-day/womens-day-mobile-banner.svg";
		// }
		// if($response['finder']['offer_icon_mob'] == "" && (int)$response['finder']['commercial_type'] != 0){
		// 	$response['finder']['offer_icon_mob']        =        "https://a.fitn.in/fitimages/fitmania/offer_available_sale.svg";
		// }
		$response['finder']['offer_icon']        =        "https://a.fitn.in/fitimages/vendor-app-download-badge1.svg";

		// if(time() >= strtotime(date('2016-12-24 00:00:00')) && (int)$response['finder']['commercial_type'] != 0){

		// 	$response['finder']['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/offer_available_search.png";
		// }
		
		return Response::json($response);

	}


	public function getPaymentModes($data){

        $payment_modes = [];

        $payment_modes[] = array(
            'title' => 'Online Payment',
            'subtitle' => 'Buy Memberships with Debit/Credit card , Paytm & other Mobile Wallets',
            'value' => 'paymentgateway',
        );

        if(isset($data['emi']) && $data['emi']){
 
            $payment_modes[] = array(
                'title' => 'EMI',
                'subtitle' => 'Buy Memberships via Monthly Instalments with Interest Rate as low as 2%',
                'value' => 'emi',
            );
        }

        if(!empty($data['cash_pickup']) && $data['cash_pickup']){
            $payment_modes[] = array(
                'title' => 'Cash Pickup',
                'subtitle' => 'Get Cash Picked up from your Preferd Location',
                'value' => 'cod',
            );
        }

        if(!empty($data['part_payment']) && $data['part_payment']){
            $payment_modes[] = array(
                'title' => 'Reserve Payment',
                'subtitle' => 'Pay 20% to reserve membership and pay rest on joining',
                'value' => 'part_payment',
            );
        }

        return $payment_modes;
    }



	public function finderServices($finderid){
		$finderid   =   (int) $finderid;
		$finder = Finder::active()->with(array('services'=>function($query){$query->select('*')->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))->where('_id','=',$finderid)->first();
		if($finder){
			$finderarr = $finder->toArray();
			$data['message']        = "Finder Detail With services";
			$data['status']         = 200;
			$data['finder']         = array_only($finderarr, array('services'));
		}else{
			$data['message']        = "Finder Does Not Exist";
			$data['status']         = 404;
		}

		return Response::json($data,200);
	}




	// public function ratecards($finderid){

	//  $finderid   =   (int) $finderid;
	//  $ratecard   =   Ratecard::where('finder_id', '=', $finderid)->where('going_status', '=', 1)->orderBy('_id', 'desc')->get($selectfields)->toArray();
	//  $resp       =   array('status' => 200,'ratecard' => $ratecard);
	//  return Response::json($resp);
	// }

	public function ratecarddetail($id){
		$id         =   (int) $id;
		$ratecard   =   Ratecard::find($id);

		if($ratecard){
			$resp   =   array('status' => 200,'ratecard' => $ratecard);
		}else{
			$resp   =   array('status' => 200,'message' => 'No Ratecard exist :)');
		}
		return Response::json($resp,200);
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
		return Response::json($data,200);
	}



	public function pushfinder2elastic ($slug){

		$tslug      =   (string) $slug;
		$finderarr  =   Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
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
		$data       =   $finderarr->toArray();
		// print_pretty($data);exit;
		$postdata   =   get_elastic_finder_document($data);

		$request = array(
			'url' => $this->elasticsearch_url."fitternity/finder/".$data['_id'],
			'port' => Config::get('app.es.port'),
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
			$email_template_data = array( 'vendor'  =>  ucwords($finderslug) ,  'date'  =>  date("h:i:sa") );

			// print_pretty($email_template_data);  exit;


			$email_message_data = array(
				'to' => Config::get('mail.to_mailus'),
				'reciver_name' => 'Fitternity',
				'bcc_emailids' => Config::get('mail.bcc_emailds_review'),
				'email_subject' => 'Review given for - ' .ucwords($finderslug)
				);
			$email = Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
				$message->to($email_message_data['to'], $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
				// $message->to('sanjay.id7@gmail.com', $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
			});

			//sending response
			$rating  =  array('average_rating' => $finder->average_rating, 'total_rating_count' => $finder->total_rating_count);
			$resp    =  array('status' => 200, 'rating' => $rating, "message" => "Rating Updated Successful :)");

			return Response::json($resp);
		}
	}

	public function updatefinderlocaiton (){

		$items = Finder::active()->orderBy('_id')->whereIn('location_id',array(14))->get(array('_id','location_id'));
		//exit;
		$finderdata = array();
		foreach ($items as $item) {
			$data   = $item->toArray();
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

		$start_time = time();
		$cron = new Cron;
		$flag = true;
		$message = '';

		try{

			// $tommorowDateTime    =   date('d-m-Y', strtotime('02-09-2015'));
			$tommorowDateTime   =   date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
			//$finders          =   Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();

			$final_lead_status = array('rescheduled','confirmed');

			$finders            =   Booktrial::where('final_lead_stage', 'trial_stage')->whereIn('final_lead_status',$final_lead_status)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();
			// $finders             =   Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->where('finder_id', '=',3305)->get()->groupBy('finder_id')->toArray();

			// echo $todayDateTime      =   date('d-m-Y', strtotime(Carbon::now()) );
			// return $todaytrialarr        =   Booktrial::where('going_status', 1)
			// ->where('schedule_date', '>=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
			// ->where('schedule_date', '<=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
			// ->where('finder_id', 3305 )->get();

			foreach ($finders as $finderid => $trials) {
				$finder     =   Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
				$finderarr  =   $finder->toArray();

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

					$finder_name_new                    =   (isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
					$finder_location_new                =   (isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
					$finder_name_base_locationtags      =   (count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

					$trialdata = array();
					foreach ($trials as $key => $value) {
						$trial = array('customer_name' => $value->customer_name,
							'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
							'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ),
							'schedule_slot' => $value->schedule_slot,
							'code' => $value->code,
							'service_name' => $value->service_name,
							'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name,
							'type' => $value->type,
							);
						array_push($trialdata, $trial);
					}

					$todayDateTime      =   date('d-m-Y', strtotime(Carbon::now()) );

					//$todaytrialarr        =   Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($todayDateTime))->where('finder_id', intval($finder->_id) )->get();

					$todaytrialarr         =   Booktrial::where('final_lead_stage', 'trial_stage')->whereIn('final_lead_status',$final_lead_status)->where('schedule_date', '=', new DateTime($todayDateTime))->where('finder_id', intval($finder->_id) )->get();

					$todaytrialdata = array();
					if($todaytrialarr){
						foreach ($todaytrialarr as $key => $value) {
							$trial = array('customer_name' => $value->customer_name,
								'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
								'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ),
								'schedule_slot' => $value->schedule_slot,
								'code' => $value->code,
								'service_name' => $value->service_name,
								'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name,
								'type' => $value->type,
								);
							array_push($todaytrialdata, $trial);
						}
					}


					$scheduledata = array('user_name'   => 'sanjay sahu',
						'user_email'                    => 'sanjay.id7@gmail',
						'finder_name'                   => $finder->title,
						'finder_name_base_locationtags' => $finder_name_base_locationtags,
						'finder_poc_for_customer_name'  => $finder->finder_poc_for_customer_name,
						'finder_vcc_email'              => $finder_vcc_email,
						'scheduletrials'                => $trialdata,
						'todaytrials'                   => $todaytrialdata
						);

//                     echo "<pre>";print_r($scheduledata);exit();

					$this->findermailer->sendBookTrialDaliySummary($scheduledata);
				}
			}

			Log::info('Trial Daily Summary Cron : success');
			$message = 'Email Send';
			$resp   =   array('status' => 200,'message' => "Email Send");


		}catch(Exception $e){


			$message = array(
				'type'    => get_class($e),
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
			);

			$resp   =   array('status' => 400,'message' => $message);
			Log::info('Trial Daily Summary Cron : fial',$message);
			$flag = false;
		}

		$end_time = time();
		$data = [];
		$data['label'] = 'TrialDailySummary';
		$data['start_time'] = $start_time;
		$data['end_time'] = $end_time;
		$data['status'] = ($flag) ? '1' : '0';
		$data['message'] = $message;

		$cron = $cron->cronLog($data);

		return Response::json($resp);


	}

	public function checkbooktrialdaliysummary($date){
		//give one date before
		$tommorowDateTime   =   date('d-m-Y', strtotime($date));
		$finders            =   Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();
		// $finders             =   Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->where('finder_id', '=',3305)->get()->groupBy('finder_id')->toArray();

		// echo $todayDateTime      =   date('d-m-Y', strtotime(Carbon::now()) );
		// return $todaytrialarr        =   Booktrial::where('going_status', 1)
		// ->where('schedule_date', '>=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
		// ->where('schedule_date', '<=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
		// ->where('finder_id', 3305 )->get();

		foreach ($finders as $finderid => $trials) {
			$finder     =   Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
			$finderarr  =   $finder->toArray();

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

				$finder_name_new                    =   (isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
				$finder_location_new                =   (isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
				$finder_name_base_locationtags      =   (count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

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

				$todayDateTime      =   date('d-m-Y', strtotime(Carbon::now()) );
				$todaytrialarr      =   Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($todayDateTime))->where('finder_id', intval($finder->_id) )->get();
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


				$scheduledata = array('user_name'   => 'sanjay sahu',
					'user_email'                    => 'sanjay.id7@gmail',
					'finder_name'                   => $finder->title,
					'finder_name_base_locationtags' => $finder_name_base_locationtags,
					'finder_poc_for_customer_name'  => $finder->finder_poc_for_customer_name,
					'finder_vcc_email'              => $finder_vcc_email,
					'scheduletrials'                => $trialdata,
					'todaytrials'                   => $todaytrialdata
				);
//                echo "<pre>";print_r($scheduledata);

				$this->findermailer->sendBookTrialDaliySummary($scheduledata);
			}
		}

		$resp   =   array('status' => 200,'message' => "Email Send");
		return Response::json($resp);

	}


	public function sendDaliySummaryHealthyTiffin()
	{

//        $todayDate    =   date('d-m-Y', strtotime('06-05-2015'));
		$todayDate          =   date('d-m-Y', time());
		$tommorowDateTime   =   date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
		$startDateTime      =   $todayDate." 00:00:00";
		$endDateTime        =   $tommorowDateTime." 00:00:00";

//        return "$startDateTime   $endDateTime";

		try{
			$finders   =   Order::whereIn('type',['healthytiffinmembership','healthytiffintrail'])->where('status', '=', '1')
				->where('created_at', '>=', new DateTime($startDateTime))
				->where('created_at', '<=', new DateTime($endDateTime))
				->get()
				->groupBy('finder_id')->toArray();


			foreach ($finders as $finderid => $trials) {
				$finder     =   Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
				$finderarr  =   $finder->toArray();

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

					$finder_name_new                    =   (isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
					$finder_location_new                =   (isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
					$finder_name_base_locationtags      =   (count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

					$trialsData = $purchasesData = array();
					foreach ($trials as $key => $value) {
						$trial = ['customer_name' => $value->customer_name,
							'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
							'customer_email' => $value->customer_email,
							'preferred_starting_date' => date('d-m-Y', strtotime($value->preferred_starting_date) ),
							'code' => $value->code,
							'code' => $value->code,
							'service_name' => $value->service_name,
							'service_duration' => $value->service_duration,
							'meal_contents' => $value->meal_contents,
							'amount' => $value->amount
						];

						if($value->type == "healthytiffintrail"){
							array_push($trialsData, $trial);
						}

						if($value->type == "healthytiffinmembership"){
							array_push($purchasesData, $trial);
						}
					}

					$scheduledata = array('user_name'   => 'sanjay sahu',
						'user_email'                    => 'sanjay.id7@gmail',
						'finder_name'                   => $finder->title,
						'finder_name_base_locationtags' => $finder_name_base_locationtags,
						'finder_poc_for_customer_name'  => $finder->finder_poc_for_customer_name,
						'finder_vcc_email'              => $finder_vcc_email,
						'trials'                        => $trialsData,
						'purchases'                     => $purchasesData
					);
//                echo "<pre>";print_r($scheduledata);

					$this->findermailer->sendDaliySummaryHealthyTiffin($scheduledata);
				}
			}

			$message = 'Email Send';
			$resp   =   array('status' => 200,'message' => "Email Send");
			Log::info('Trial Daily Summary Cron For Healthy Tiffin : success');

		}catch(Exception $e){
			$message = 'Email Send Fail';
			$resp   =   array('status' => 400,'message' => $message);
			Log::info('Trial Daily Summary Cron  For Healthy Tiffin : fail');
		}

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

			$finderratecards    =   $finderdata['ratecards'];


			if(count($finderratecards) > 1){

				$finderid           =   (int) $finderdata['_id'];
				$findercategory_id  =   $finderdata['category']['_id'];
				$location_id        =   $finderdata['location']['_id'];
				$interest           =   $finderdata['category']['name'];
				$area               =   $finderdata['location']['name'];

				foreach ($finderratecards as $key => $value) {

					$ratedata       = array();
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
					$insertedid = Ratecard::max('_id') + 1;
					$ratecard   = new Ratecard($ratedata);
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
			$data   = $item->toArray();
			array_set($finderdata, 'popularity', 10000);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

		// set popularity 4000 for following category
		$items = Finder::active()->where('finder_type', 0)->whereIn('city_id', array(2,3,4))->whereIn('category_id', array(36,41,25,42,26,40))->get();
		$finderdata = array();
		foreach ($items as $item) {
			$data   = $item->toArray();
			array_set($finderdata, 'popularity', 4000);
			$finder = Finder::findOrFail($data['_id']);
			$response = $finder->update($finderdata);
			print_pretty($response);
		}

	}

	public function addReviewCustomer(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		$rules = [
		    'finder_id' => 'required|integer|numeric',
		    'rating' => 'required|numeric',
		    //'description' => 'required'
		];

		$data = Input::json()->all();

		$validator = Validator::make($data,$rules);
		if ($validator->fails()) {
			$response = array('status' => 400, 'message' => 'Could not create a review.', 'errors' => $validator->errors());
			return Response::json($response, 400);
		}

		$rating = $data['rating'];
		$data["customer_id"] = $decoded->customer->_id;
		$data['description'] = (isset($data['description'])) ? $data['description'] : '';

		if(!isset($data['detail_rating'])){
			$data['detail_rating'] = [$rating,$rating,$rating,$rating,$rating];
		}

		if(isset($data['notification_id'])){

			$notificationTracking = NotificationTracking::find($data['notification_id']);

			if(isset($notificationTracking["order_id"])){
				$data["order_id"] = (int)$notificationTracking["order_id"];
			}

			if(isset($notificationTracking["booktrial_id"])){
				$data["booktrial_id"] = (int)$notificationTracking["booktrial_id"];
				Booktrial::where('_id', $data["booktrial_id"])->update(['post_trial_status'=> 'attended']);
			}

			unset($data['"notification_id']);
		}

		if(isset($data['review_for']) && in_array($data['review_for'],['membership','session'])){

			switch ($data['review_for']) {
				case 'membership' :
					$transaction = Order::active()->where('finder_id',(int)$data["finder_id"])->where('customer_id',(int)$data["customer_id"])->orderBy('_id','desc')->first();
					if($transaction){
						$data["order_id"] = (int)$transaction['_id'];
					}
					break;
				case 'session' :
					$transaction = Booktrial::where('finder_id',(int)$data["finder_id"])->where('customer_id',(int)$data["customer_id"])->orderBy('_id','desc')->first();
					if($transaction){
						$data["booktrial_id"] = (int)$transaction['_id'];
					}
					break;
				default : break;
			}
		}

		if($this->vendor_token){
			$data['source'] = 'kiosk';
		}

		Log::info('review data',$data);

		return $this->addReview($data);
	}

	public function addReview($data = false){

		if(!$data){
			$data = Input::json()->all();
		}

		$jwt_token = Request::header('Authorization');

	    if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

	        $decoded = customerTokenDecode($jwt_token);
	        $data['customer_id'] = (int)$decoded->customer->_id;
	    }

		// return Input::json()->all();
		$validator = Validator::make($data, Review::$rules);
		Log::info("Review".$jwt_token);
		if ($validator->fails()) {
			$response = array('status' => 400, 'message' => 'Could not create a review.', 'errors' => $validator->errors());
			return Response::json($response, $this->error_status);
		}

		if(isset($data['review_for']) && in_array($data['review_for'],['membership','session'])){

			switch ($data['review_for']) {
				case 'membership' :
					$transaction = Order::active()->where('finder_id',(int)$data["finder_id"])->where('customer_id',(int)$data["customer_id"])->orderBy('_id','desc')->first();
					if($transaction){
						$data["order_id"] = (int)$transaction['_id'];
					}
					break;
				case 'session' :
					$transaction = Booktrial::where('finder_id',(int)$data["finder_id"])->where('customer_id',(int)$data["customer_id"])->orderBy('_id','desc')->first();
					if($transaction){
						$data["booktrial_id"] = (int)$transaction['_id'];
					}
					break;
				default : break;
			}
		}

		if($this->vendor_token){
			$data['source'] = 'kiosk';
		}

		$reviewdata = [
			'finder_id' => intval($data['finder_id']),
			'customer_id' => intval($data['customer_id']),
			'rating' => floatval($data['rating']),
			'detail_rating' => array_map('floatval',$data['detail_rating']),
			'description' => (isset($data['description'])) ? $data['description'] : '',
			'uploads' => (isset($data['uploads'])) ? $data['uploads'] : [],
			'booktrial_id' => (isset($data['booktrialid'])) ? intval($data['booktrialid']) : '',
			'source' => (isset($data['source'])) ? $data['source'] : 'customer',
			'status' => '1',
			'order_id' => (isset($data['order_id']) && $data['order_id'] != "") ? intval($data['order_id']) : '',
			'assisted_by' => (isset($data['assisted_by'])) ? intval($data['assisted_by']) : null
		];

		(isset($_GET['device_type']) && $_GET['device_type'] != "") ? $reviewdata['source'] = strtolower($_GET['device_type']) : null ;

		if(isset($data['booktrialid']) && $data['booktrialid'] != '' && (!isset($data['source']) || $data['source'] != 'admin')){
			$booktrial = Booktrial::find(intval($data['booktrialid']));
			$booktrial->post_trial_status = 'attended';
			$booktrial->update();
		}
		
		$reviewdata['booktrial_id'] = ($reviewdata['booktrial_id'] == "" && isset($data['booktrial_id']) && $data['booktrial_id'] != "") ? intval($data['booktrial_id']) : '';

		if(isset($data['agent_name'])){
			$reviewdata['agent_name'] = $data['agent_name'];
		}

		if(isset($data['agent_email'])){
			$reviewdata['agent_email'] = $data['agent_email'];
		}

		if(isset($data['booktrial_id']) && $data['booktrial_id'] != ""){
			$reviewdata['booktrial_id'] = (int)$data['booktrial_id'];
		}

		$finder = Finder::find(intval($data['finder_id']));

		$review = Review::where('finder_id', intval($data['finder_id']))->where('customer_id', intval($data['customer_id']))->first();

		if(isset($data['order_id']) && $data['order_id'] != ""){
			$order = Order::find((int) $data['order_id']);

			if($order){
				$order->update(["review_added"=>true]);
			}
		}

		$fresh_review = true;

		if($review){

			$fresh_review = false;

			$review->update($reviewdata);
			$message = 'Review Updated Successfully';
			$review_id = $review->_id;

		}else{

			$inserted_id = Review::max('_id') + 1;
			$review = new Review($reviewdata);
			$review_id = $review->_id = $inserted_id;
			$review->save();

			$message = 'Review Created Successfully';
		}

		$this->updateFinderRatingV2($finder);

		$review_detail = $this->updateFinderRatingV1($reviewdata);
		
		$review_detail['reviews'] = Review::active()->where('finder_id',intval($data['finder_id']))->orderBy('_id', 'DESC')->limit(5)->get();

		$response = array('status' => 200, 'message' => $message,'id'=>$review_id,'review_detail'=>$review_detail);

		if(isset($data['booktrialid']) &&  $data['booktrialid'] != '' && isset($review_id) &&  $review_id != ''){
			$booktrial_id   =   (int) $data['booktrialid'];
			$trial          =   Booktrial::find($booktrial_id);
			$trial->update(['review_id'=> intval($review_id), 'has_reviewed' => '1']);
		}

		$this->cacheapi->flushTagKey('finder_detail',$finder->slug);
		$this->cacheapi->flushTagKey('review_by_finder_list',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_android',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_3_2',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_3_2',$finder->slug);


		$order_count = Order::active()->where('type','memberships')->where('finder_id',(int)$data["finder_id"])->where('customer_id',(int)$data["customer_id"])->count();

		$booktrial_count = Booktrial::where('type','booktrials')->where('finder_id',(int)$data["finder_id"])->where('customer_id',(int)$data["customer_id"])->count();

		if($this->vendor_token && $fresh_review && $booktrial_count > 0 && $order_count == 0){

			$fitcash_amount = 150;

			$req = array(
                "customer_id"=>$data['customer_id'],
                "review_id"=>$review_id,
                "finder_id"=>$data['finder_id'],
                "amount"=>$fitcash_amount,
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $fitcash_amount,
                "type"=>'CREDIT',
                'entry'=>'credit',
                'description'=>"Fitcash+ Added for reviewing ".ucwords($finder['title']),
            );

			$this->utilities->walletTransaction($req);

			$response['fitcash'] = [
				'image'=>'https://b.fitn.in/gamification/reward/cashback.jpg',
				'amount'=>(string)$fitcash_amount,
				'title1'=>strtoupper('<b>₹'.$fitcash_amount.'</b> FITCASH+'),
				'title2'=>strtoupper('Has  been  added'),
				'description'=>'Find  this  on  <b>Fitternity  Wallet</b>  &  use  it  to  purchase  your  membership',
			];

			$response['membership'] = [
				'image'=>'https://b.fitn.in/gamification/reward/cashback.jpg',
				'amount'=>(string)$fitcash_amount,
				'title1'=>strtoupper('Membership  On'),
				'title2'=>strtoupper('Lowest  prices'),
				'description'=>'Use  this  <b>₹'.$fitcash_amount.'  off</b>  before  it  gets  expired  to  buy  membership  on  this  tab  at  lowest  price  with  complimentary  rewards'
			];		

			$response['message'] = "Thanks for your valuable feedback!";
			$response['message_title'] = "Done!";

			$response['review_detail'] = null;
		}

		return Response::json($response, 200);
	}

	public function updateFinderRatingV2($finder){

		$review = Review::where('finder_id',$finder->_id)->get();

		$detail_rating = array(array('count'=>0,'rating'=>0),array('count'=>0,'rating'=>0),array('count'=>0,'rating'=>0),array('count'=>0,'rating'=>0),array('count'=>0,'rating'=>0));
		$rating = 0;
		$rating_count = 0;
		$average_rating = 0;

		foreach ($review as $key => $value) {

			if($value->rating >= 0){
				$rating += (int) $value->rating;
				$rating_count += 1;
			}

			foreach ($value->detail_rating as $dr_key => $dr_value) {

				if($dr_value >= 1){

					$detail_rating[$dr_key]['rating'] += (int) $dr_value;
					$detail_rating[$dr_key]['count'] += 1;
				}
			}
		}

		foreach ($detail_rating as $dr_key => $dr_value) {

			if($dr_value['count'] < 1 ){
				$detail_rating[$dr_key]['rating'] = round($dr_value['rating'],2);
			}else{
				$detail_rating[$dr_key]['rating'] = round($dr_value['rating']/$dr_value['count'],2);
			}

			$detail_rating_summary_average[$dr_key] = $detail_rating[$dr_key]['rating'];
			$detail_rating_summary_count[$dr_key] = $detail_rating[$dr_key]['count'];
		}

		if($rating_count == 0 || $rating == 0){
			$average_rating = $rating;
		}else{
			$average_rating = round($rating/$rating_count,2);
		}

		$total_rating_count = $rating_count;

		$finder->average_rating = $average_rating;
		$finder->total_rating_count = $total_rating_count;
		$finder->detail_rating_summary_count = $detail_rating_summary_count;
		$finder->detail_rating_summary_average = $detail_rating_summary_average;
		$finder->update();

		$rating  =  array('average_rating' => $finder->average_rating, 'total_rating_count' => $finder->total_rating_count, 'detail_rating_summary_average' => $finder->detail_rating_summary_average, 'detail_rating_summary_count' => $finder->detail_rating_summary_count);

		return array('rating' => $rating);

	}

	public function updateFinderRatingV1 ($review, $oldreview = NULL ){

		$data                   =   $review;
		$total_rating_count     =   round(floatval(Input::json()->get('total_rating_count')),1);
		$average_rating         =   round(floatval(Input::json()->get('average_rating')),1);
		$finderdata             =   array();
		$finderid               =   (int) $data['finder_id'];
		$finder                 =   Finder::findOrFail($finderid);
		$finderslug             =   $finder->slug;
		$total_rating_count     =   Review::where('finder_id', $finderid)->count();
		$sum_rating         =   Review::where('finder_id', $finderid)->sum('rating');

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
		// return $finder;

		if($finder->update($finderdata)){
			//updating elastic search
			// $this->pushfinder2elastic($finderslug);
			//sending email
			$email_template = 'emails.review';
			$email_template_data = array( 'vendor'  =>  ucwords($finderslug) , 'review' => $data['description'] ,  'date'   =>  date("h:i:sa") );
			$email_message_data = array(
				'to' => Config::get('mail.to_mailus'),
				'reciver_name' => 'Fitternity',
				'bcc_emailids' => Config::get('mail.bcc_emailds_review'),
				'email_subject' => 'Review given for - ' .ucwords($finderslug)
				);
			$email = Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
				// $message->to($email_message_data['to'], $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
				$message->to('sailismart@fitternity.com', $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
			});

			//sending response
			$rating  =  array('average_rating' => $finder->average_rating, 'total_rating_count' => $finder->total_rating_count, 'detail_rating_summary_average' => $finder->detail_rating_summary_average, 'detail_rating_summary_count' => $finder->detail_rating_summary_count);
			//$resp    =  array('status' => 200, 'rating' => $rating, "message" => "Rating Updated Successful :)");

			$resp    =  array('rating' => $rating);
			return $resp ; //Response::json($resp);
		}
	}

	public function getFinderReview($slug,$cache = true){
		$data = array();
		$tslug = (string) $slug;

		$review_by_finder_list = $cache ? Cache::tags('review_by_finder_list')->has($tslug) : false;

		if(!$review_by_finder_list){

			$finder_by_slug= Finder::where('slug','=',$tslug)->firstOrFail();

			if(!empty($finder_by_slug)){

				$finder_id  = (int) $finder_by_slug['_id'];
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
	 * @param  int      $reivewid
	 * @param  string   $slug
	 * @return Response
	 */

	public function detailReview($reivewid){

		$review = Review::with('finder')->where('_id', (int) $reivewid)->first();

		if(!$review){
			$resp   =   array('status' => 400, 'review' => [], 'message' => 'No review Exist :)');
			return Response::json($resp, 400);
		}

		$reviewdata = $this->transform($review);
		$resp   =   array('status' => 200, 'review' => $reviewdata, 'message' => 'Particular Review Info');
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

	public function finderTopReview($slug, $limit = '', $cache=false){

		$limit  =   ($limit != '') ? intval($limit) : 10;
		$finder_detail_with_top_review = $cache ? Cache::tags('finder_detail_with_top_review')->has($slug) : false;

		if(!$finder_detail_with_top_review){
			$finder = array();
			$review = array();

			try {
				$finder = Finder::where('slug','=',(string)$slug)
					->with(array('city'=>function($query){$query->select('_id','name','slug');}))
					->with(array('location'=>function($query){$query->select('_id','name','slug');}))
					->first(array('title','photos','city_id','location_id','info','contact','total_rating_count','detail_rating_summary_average','detail_rating_summary_count'));
			} catch (Exception $error) {
				return $errorMessage = $this->errorMessage($error);
			}


			if(!is_null($finder) || !empty($finder) && isset($finder->_id)){
				try {
					$review = Review::where('finder_id','=',$finder->_id)->orderBy('created_at', 'desc')->orderBy('rating', 'desc')->take($limit)->get();
				} catch (Exception $error) {
					return $errorMessage = $this->errorMessage($error);
				}

				if(is_null($review)){
					$review = array();
				}

			}else{
				$finder = array();
			}

			$data = [ 'finder' => $finder, 'review' => $review ];
			$response = array('status' => 200,'data'=>$data);

			if(!empty($finder) && !empty($review)){
				Cache::tags('finder_detail_with_top_review')->put($slug,$response,Config::get('app.cachetime'));
			}

		}else{
			$response = Cache::tags('finder_detail_with_top_review')->get($slug);
		}

		return Response::json($response,200);
	}


	public function errorMessage($error){

		$message = $error->getMessage().' in '.$error->getFile().' : '.$error->getLine();
		$status = 400;

		$response = array('status'=>$status,'message'=>$message);

		return Response::json($response,$status);
	}

	public function reviewListing($finder_id, $from = '', $size = ''){

		$finder_id          =   (int) $finder_id;
		$from               =   ($from != '') ? intval($from) : 0;
		$size               =   ($size != '') ? intval($size) : 10;

		$reviews            =   Review::with(array('finder'=>function($query){$query->select('_id','title','slug','coverimage');}))->active()->where('finder_id','=',$finder_id)->take($size)->skip($from)->orderBy('updated_at', 'desc')->get();

		$remaining_count =  Review::active()->where('finder_id','=',$finder_id)->count() - ($from+$size);

		$remaining_count    =   ($remaining_count > 0) ? $remaining_count : 0;

		$responseData       =   ['reviews' => $reviews,'message' => 'List for reviews','remaining_count'=>$remaining_count];

		return Response::json($responseData, 200);
	}

	public function updateBudgetFromRatecardsToFinder(){

		$city_list = array(1,2,3,4,8);

		foreach ($city_list as $city) {


			$finder_documents = Finder::with(array('country'=>function($query){$query->select('name');}))
				->with(array('city'=>function($query){$query->select('name');}))
				->active()
				->orderBy('_id')
				->where('city_id', intval($city))
				//->where('status', '=', '1')
				->take(50000)->skip(0)
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

				$finderData = [];
				//Logo
				$finderData['price_range']  = $average_monthly_tag;
				$finderData['budget']  = round($average_monthly);

				$response = $finder->update($finderData);
			}
		}
	}


	public function getInfoTiming($services){

		$service_batch = array();

		foreach ($services as $service_key => $service_value){

			if(isset($service_value['batches']) && !empty($service_value['batches'])){
				
				$service_batch[$service_value['name']] = $this->getAllBatches($service_value['batches']);
			}else if(isset($service_value['timings']) && $service_value['timings'] != ""){
				$service_batch[$service_value['name']] = $service_value['timings'];
			}
		}

		$info_timing = "";

		if(count($service_batch) > 0){

			foreach ($service_batch as $ser => $btch){

				$info_timing .= "<p><strong>".$ser."</strong></p>";

				if(gettype($btch)=='array'){
					foreach ($btch as $btch_value){

						foreach ($btch_value as $key => $value) {
							$info_timing .= "<p><i>".$this->matchAndReturn($value)." : </i>". $key ."</p>";
						}

					}
				}else{

					$time = substr($btch, strpos($btch, ":")+1);
					$days = substr($btch, 0, strpos($btch, ":")-1);
					$info_timing .= "<p><i>".$days." : </i>$time</p>";
				}
			}
		}

		return $info_timing;

	}

	public function getAllBatches($batches){

		$result = array();

		foreach ($batches as $key => $batch) {

			$result_weekday = array();

			foreach ($batch as $data) {

				$count = 0;

				if(isset($data['slots'])){
					foreach ($data['slots'] as $slot) {
						if($count == 0){

							if(isset($slot['weekday']) && isset($slot['slot_time'])){
								$result_weekday[ucwords($slot['weekday'])] = strtoupper($slot['slot_time']);
							}

						}else{
							break;
						}

						$count++;
					}
				}
			}

			$result[] = $this->getDupKeys($result_weekday);

		}

		return $result;

	}

	public function getDupKeys($array) {

		$dups = array();

		foreach ($array as $k => $v) {
			$dups[$v][] = $k;
		}

		foreach($dups as $k => $v){

			$dups[$k] = implode(", ", $v);

		}

		return $dups;
	}

	public function matchAndReturn($key){

		$match = array(
			"Monday, Tuesday, Wednesday"=>"Monday - Wednesday",
			"Monday, Tuesday, Wednesday, Thursday"=>"Monday - Thursday",
			"Monday, Tuesday, Wednesday, Thursday, Friday"=>"Monday - Friday",
			"Monday, Tuesday, Wednesday, Thursday, Friday, Saturday"=>"Monday - Saturday",
			"Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday"=>"Monday - Sunday",
		);

		if(array_key_exists($key,$match)){
			return $match[$key];
		}else{
			return $key;
		}
	}

	public function getMembershipRatecardByServiceId($service_id){

		$service_id = (int) $service_id;

		$service = Service::find($service_id);

		$response = [
			'status'=>200,
			'message'=>'Success'
		];

		if($service){

			$finder_id = (int)$service->finder_id;

			$getTrialSchedule = $this->getTrialSchedule($finder_id);

			$wallet_balance = 0;

			$jwt_token = Request::header('Authorization');

	        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

	            $decoded = customerTokenDecode($jwt_token);

	            $customer_id = (int)$decoded->customer->_id;

	            $getWalletBalanceData = [
	                'finder_id'=>$finder_id,
	                'order_type'=>'memberships'
	            ];

            	$wallet_balance = $this->utilities->getWalletBalance($customer_id,$getWalletBalanceData);
	        }

			foreach ($getTrialSchedule as $key => $value) {

				if($value['_id'] == $service_id){

					$service = $value;

					$ratecards = $value['ratecard'];

					foreach ($ratecards as $ratecard_key => $ratecard_value) {

						if(!in_array($ratecard_value['type'],['membership','packages'])){

							unset($ratecards[$ratecard_key]); continue;
						}

						if($ratecard_value['direct_payment_enable'] == '0'){

							unset($ratecards[$ratecard_key]); continue;
						}

						$price = $this->utilities->getRatecardAmount($ratecard_value);

						$ratecards[$ratecard_key]['fitcash_applicable'] = $wallet_balance;

						if($wallet_balance > $price){
							$ratecards[$ratecard_key]['fitcash_applicable'] = $price;
						}

					}

					$ratecards = array_values($ratecards);

					$service['ratecard'] = $ratecards;

					$finder = Finder::find((int) $service['finder_id']);

					if($finder){

						$service['finder_name'] = ucwords($finder->title);
						$service['finder_slug'] = $finder->slug;
						$service['finder_category_id'] = $finder->category_id;
					}

					$service['city'] = null;
					$service['location'] = null;
					$service['category'] = null;

					$serviceData = Service::active()
						->with(array('category'=>function($query){$query->select('name','slug','_id');}))
						->with(array('location'=>function($query){$query->select('name','slug','_id');}))
						->with(array('city'=>function($query){$query->select('name','slug','_id');}))
						->find((int)$service['_id']);

					if($serviceData){

						$serviceDataArray = $serviceData->toArray();

						$service['city'] = $serviceDataArray['city'];
						$service['location'] = $serviceDataArray['location'];
						$service['category'] = $serviceDataArray['category'];

						$traction = [
							'trials' => 0,
							'requests' => 0,
							'sales' => 0,
							'six_months' => [
							    'requests' => 0,
							    'sales' => 0,
							    'trials' => 0,
						  	]
						];

						if(isset($serviceDataArray['traction']) && $serviceDataArray['traction'] != ""){

							$traction = $serviceDataArray['traction'];
						}

						if($traction['sales'] > 0){

							$traction['sales'] = $traction['sales'] * 10 + 181;

						}else{

							if(isset($serviceDataArray['fake_sales'])){

								$traction['sales'] = $serviceDataArray['fake_sales'];

							}else{

								$fake_sales = rand(140,200);

								$serviceData->fake_sales = $fake_sales;
								$serviceData->update();

								$traction['sales'] = $fake_sales;
							}
						}
						
						$service['traction'] = $traction;
						
					}

					break;
				}

			}

			$response['service'] = $service;
		}

		return Response::json($response,200);

	}

	public function serviceMembership($finder_id){

		$response = [
			'status'=>200,
			'message'=>'Success'
		];
		$device_id = Request::header('Device-Id');
		Log::info($device_id);
		$getTrialSchedule = $this->getTrialSchedule($finder_id);

		if(empty($getTrialSchedule)){

			$response = [
				'status'=>400,
				'message'=>'No results found',
				'memberships'=>[]
			];

			return Response::json($response,200);
		}

		foreach ($getTrialSchedule as $key => $value) {

			if(isset($getTrialSchedule[$key]['showOnFront']) && !in_array('kiosk',$getTrialSchedule[$key]['showOnFront'])){

				unset($getTrialSchedule[$key]); continue;
			}

			/*if(isset($getTrialSchedule[$key]['showOnFront']) && !$getTrialSchedule[$key]['showOnFront']){

				unset($getTrialSchedule[$key]); continue;
			}*/

			if(empty($value['ratecard'])){

				unset($getTrialSchedule[$key]);

			}else{

				$ratecards = $value['ratecard'];

				foreach ($ratecards as $ratecard_key => $ratecard_value) {

					if($ratecard_value['direct_payment_enable'] == '0'){

						unset($ratecards[$ratecard_key]);
					}

				}

				$ratecards = array_values($ratecards);

				if(!empty($ratecards)){

					$getTrialSchedule[$key]['ratecard'] = $ratecards;

				}else{

					unset($getTrialSchedule[$key]);
				}

			}

		}
		
		$getTrialSchedule = array_values($getTrialSchedule);

		$response['memberships'] = $getTrialSchedule;
		$response['perks'] = [
			[
				"image"=>"https://b.fitn.in/global/toi/mfp/mfpmum-26th/lowest-price.png",
				"title"=>"Lowest price guarantee"
			],
			[
				"image"=>"https://b.fitn.in/global/toi/mfp/mfpmum-26th/rewards.png",
				"title"=>"Complementary rewards"
			],
			[
				"image"=>"https://b.fitn.in/global/toi/mfp/mfpmum-26th/flexible.png",
				"title"=>"Flexible EMI & payment options"
			],
			[
				"image"=>"https://b.fitn.in/global/toi/mfp/mfpmum-26th/fitcash.png",
				"title"=>"Earn Fitcash+ & cashback"
			]
		];

		$this->kioskTabLastLoggedIn();

		return Response::json($response,200);
	}


	public function getTrialSchedule($finder_id,$category = false){

		$currentDateTime        =   date('Y-m-d');
		$finder_id               =   (int) $finder_id;
		$date                   =   date('Y-m-d');
		$timestamp              =   strtotime($date);
		$weekday                =   strtolower(date( "l", $timestamp));
		//$offer_icon_vendor      =   "";


		/*if($category->_id == 42){
			$membership_services = Ratecard::where('finder_id', $finder_id)->lists('service_id');
		}else{
			$membership_services = Ratecard::where('finder_id', $finder_id)->orWhere('type','membership')->orWhere('type','packages')->lists('service_id');
		}

		$membership_services = array_map('intval',$membership_services);*/
		Service::$withoutAppends=true;
		Service::$setAppends=['active_weekdays','serviceratecard'];
		if(isset($_GET['device_type']) && $_GET['device_type'] == 'android'){

			$items = Service::active()->where('finder_id', $finder_id)->get(array('_id','name','finder_id', 'serviceratecard','trialschedules','servicecategory_id','batches','short_description','photos','trial','membership', 'traction', 'location_id', 'offer_available', 'ad', 'showOnFront'))->toArray();

		}else{

			$membership_services = Ratecard::where('finder_id', $finder_id)->orWhere('type','membership')->orWhere('type','packages')->lists('service_id');
			$membership_services = array_map('intval',$membership_services);

			$items = Service::active()->whereIn('_id',$membership_services)->where('finder_id', $finder_id)->get(array('_id','name','finder_id', 'serviceratecard','trialschedules','servicecategory_id','batches','short_description','photos','trial','membership', 'traction', 'location_id','offer_available', 'showOnFront'))->toArray();

		}

		if(!$items){
			return array();
		}

		$scheduleservices = array();

		foreach ($items as $k => $item) {

			$device = $this->vendor_token ? 'kiosk' : 'web';
			
			if(!isset($item['showOnFront']) || ((isset($item['showOnFront']) && in_array($device, $item['showOnFront'])))){
			// if(!isset($item['showOnFront']) || ((isset($item['showOnFront']) && $item['showOnFront']))){

				$extra_info = array();

			/*$extra_info[0] = array(
				'title'=>'Description',
				'icon'=>'https://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
				'description'=> (isset($item['short_description']) && count($item['short_description']) > 0) ? strip_tags($item['short_description']) : ""
			);*/

			// unset($items[$k]['short_description']);
			$items[$k]['short_description_icon'] = "https://b.fitn.in/iconsv1/vendor-page/description.png";

			$sericecategorysCalorieArr = Config::get('app.calorie_burn_categorywise');
			$category_calorie_burn = 300;
			$service_category_id = (isset($item['servicecategory_id']) && $item['servicecategory_id'] != "") ? $item['servicecategory_id'] : 0;


			if(isset($service['calorie_burn']) && $service['calorie_burn']['avg'] != 0){
				$category_calorie_burn = $service['calorie_burn']['avg'];
			}else{
				if(isset($sericecategorysCalorieArr[$service_category_id])){
					$category_calorie_burn = $sericecategorysCalorieArr[$service_category_id];
				}
			}

			$extra_info[0] = array(
				'title'=>'Avg. Calorie Burn',
				'icon'=>'https://b.fitn.in/iconsv1/vendor-page/calorie.png',
				'description'=>$category_calorie_burn.' Kcal'
			);

			$extra_info[1] = array(
				'title'=>'Results',
				'icon'=>'https://b.fitn.in/iconsv1/vendor-page/form.png',
				'description'=>'Burn Fat | Super Cardio'
			);

			if($category && ($category->_id == 42 || $category->_id == 45)){

				$extra_info = [];

				if(isset($item['short_description']) && $item['short_description'] != ""){
					$extra_info[] = array(
						'title'=>'Meal Contents',
						'icon'=>'https://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
						'description'=> str_replace("&nbsp;", "", strip_tags($item['short_description'])) 
					);
				}
			}

			$batches = array();

			if(isset($item['batches']) && count($item['batches']) > 0){

				$batches = $item['batches'];

				foreach ($batches as $batches_key => $batches_value) {

					foreach ($batches_value as $batches_value_key => $value) {

						$batches[$batches_key][$batches_value_key]['slots'] = $value['slots'][0];
					}
				}
			}

			$photo = null;
			if(isset($item['photos']) && count($item['photos']) > 0){

				$photo1 = $item['photos'][0];
				if(isset($photo1['url'])){
					$photo = "https://b.fitn.in/s/g/thumbs/".$photo1['url'];
				}
			}

			$service = array(
				'_id' => $item['_id'],
				'finder_id' => $item['finder_id'],
				'service_name' => $item['name'],
				'weekday' => $weekday,
				'ratecard'=>[],
				'slots'=>null,
				'extra_info'=>$extra_info,
				'batches'=>$batches,
				'image'=>$photo,
				'membership' => (isset($item['membership'])) ? $item['membership'] : "",
				'trial' => (isset($item['trial'])) ? $item['trial'] : "",
				'offer_icon' => "",
				'servicecategory_id' => $item['servicecategory_id'],
				'traction' => isset($item['traction']) && isset($item['traction']['sales']) && isset($item['traction']['trials']) ? $item['traction'] : array("trials"=>0,"sales"=>0),
				'location_id' => $item['location_id'],
				'offer_available' => isset($item['offer_available']) ? $item['offer_available'] : false,
				'short_description' => isset($item['short_description']) ? $item['short_description'] : "",
				// 'showOnFront'=>(isset($item['showOnFront'])) ? $item['showOnFront'] : []
			);

			// if(isset($item['offer_available']) && $item['offer_available'] == true && !in_array($finder_id, Config::get('app.hot_offer_excluded_vendors'))){

			// 	$service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/women_offer_ratecard.png";
			// }


			if(count($item['serviceratecard']) > 0){

				$ratecardArr = [];

				foreach ($item['serviceratecard'] as $ratekey => $rateval){

					//for ratecards offers
					$ratecardoffers     =   [];

					if((isset($rateval['expiry_date']) && $rateval['expiry_date'] != "" && strtotime("+ 1 days", strtotime($rateval['expiry_date'])) < time()) || (isset($rateval['start_date']) && $rateval['start_date'] != "" && strtotime($rateval['start_date']) > time())){
						continue;
					}
					

					if(!isset($rateval['offers']) || (isset($rateval['offers']) && count($rateval['offers'])==0)){
						if(!empty($rateval['_id']) && isset($rateval['_id'])){
							$ratecardoffersRecards  =   Offer::where('ratecard_id', intval($rateval['_id']))->where('hidden', false)
								->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
								->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
								->orderBy('order', 'asc')
								->get(['start_date','end_date','price','type','allowed_qty','remarks'])
								->toArray();


							if(count($ratecardoffersRecards) > 0){ 

								// $service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/mob_offer_ratecard.png";
								//$offer_icon_vendor = "https://b.fitn.in/iconsv1/fitmania/offer_available_search.png";
								
								foreach ($ratecardoffersRecards as $ratecardoffersRecard){
									$ratecardoffer                  =   $ratecardoffersRecard;
									$ratecardoffer['offer_text']    =   "";
									$ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/fitmania/hot_offer_vendor.png";

									if(isset($rateval['flags'])){

										if(isset($rateval['flags']['discother']) && $rateval['flags']['discother'] == true){
											$ratecardoffer['offer_text']    =   "";
											// $ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/womens-day/women-only.png";
											$ratecardoffer['offer_icon']    =   "";
										}

										if(isset($rateval['flags']['disc25or50']) && $rateval['flags']['disc25or50'] == true){
											$ratecardoffer['offer_text']    =   "";
											// $ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/womens-day/women-only.png";
											$ratecardoffer['offer_icon']    =   "";
										}
									}

									$today_date     =   new DateTime( date("d-m-Y 00:00:00", time()) );
									$end_date       =   new DateTime( date("d-m-Y 00:00:00", strtotime("+ 1 days", strtotime($ratecardoffer['end_date']))));
									$difference     =   $today_date->diff($end_date);

									// if($difference->days <= 5){
									// 	$ratecardoffer['offer_text']    =   ($difference->d == 1) ? "Expires Today" : "Expires in ".$difference->days." days";

									// }
									array_push($ratecardoffers,$ratecardoffer);
								}
							}
						}
					}
					

					count($ratecardoffers)>0 ? $rateval['offers']  = $ratecardoffers: null;

					if(count($ratecardoffers) > 0 && isset($ratecardoffers[0]['price'])){

						/*if($ratecardoffers[0]['price'] == $rateval['price']){
							$rateval['price'] = $ratecardoffers[0]['price'];
						}else{
							$rateval['special_price'] = $ratecardoffers[0]['price'];
						}*/

						$rateval['special_price'] = $ratecardoffers[0]['price'];

                    	($rateval['price'] == $ratecardoffers[0]['price']) ? $rateval['special_price'] = 0 : null;

						if(isset($ratecardoffers[0]['remarks']) && $ratecardoffers[0]['remarks'] != ""){
							$rateval['remarks'] = $ratecardoffers[0]['remarks'];
						}
					}

					/*if($category->_id == 42){
						array_push($ratecardArr, $rateval);
					}else{*/
						if($rateval['type'] == 'membership' || $rateval['type'] == 'packages'){
							
							$appOfferDiscount = in_array($finder_id, $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;

							$customerDiscount = $this->utilities->getCustomerDiscount();
							
							Log::info("getCustomerDiscount");
							$discount = $appOfferDiscount + $customerDiscount;
							// Log::info($discount);
							if($rateval['special_price'] > 0){
								$discount_amount = intval($rateval['special_price'] * ($discount/100));
								$rateval['special_price'] = $rateval['special_price'] - $discount_amount;
							}else{
								$discount_amount = intval($rateval['price'] * ($discount/100));
								$rateval['price'] = $rateval['price'] - $discount_amount;
							}

							if(isset($rateval['special_price']) && $rateval['special_price'] != 0){
					            $rateval_price = $rateval['special_price'];
					        }else{
					            $rateval_price = $rateval['price'];
					        }

					        if($rateval_price >= 20000){

					        	$rateval['campaign_offer'] = "(EMI options available)";
					        	$rateval['campaign_color'] = "#43a047";
					        }
					        
							array_push($ratecardArr, $rateval);
						}
						// else{
						// 	array_push($ratecardArr, $rateval);
						// }
					//}
				}

				$service['ratecard'] = $ratecardArr;
				
			}

			$time_in_seconds = time_passed_check($item['servicecategory_id']);

			if(isset($item['trialschedules']) && count($item['trialschedules']) > 0){

				$weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
					if($value['weekday'] == $weekday){
						return $value;
					}
				}));

				$slots = array();

				if(count($weekdayslots['slots']) > 0){
					foreach ($weekdayslots['slots'] as $slot) {
						array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
						array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
						try{
							$scheduleDateTimeUnix               =  strtotime(strtoupper($date." ".$slot['start_time']));
							if(($scheduleDateTimeUnix - time()) > $time_in_seconds){
								array_push($slots, $slot);
							}
						}catch(Exception $e){
							Log::info("getTrialSchedule Error : ".$date." ".$slot['start_time']);
						}
					}

					if(count($slots) > 0){
						$service['slots'] = $slots[0];
					}
				}
			}

			if(empty($service['slots']) && empty($service['ratecard'])){
				continue ;
			}



			array_push($scheduleservices, $service);
			}
		}

		//$scheduleservices['offer_icon_vendor'] = $offer_icon_vendor;

		return $scheduleservices;
	}

	public function finderDetailApp($slug, $cache = true){

		$data   =  array();	
		$tslug  = (string) strtolower($slug);


		if($tslug == "default" && isset($_GET['vendor_id']) && $_GET['vendor_id'] != ""){

			$vendor = Finder::find((int)$_GET['vendor_id'],["slug"]);

			if($vendor){
				$tslug = $vendor->slug;
			}else{
				return Response::json(array("status"=>404), 404);
			}
		}

		$cache_key = $tslug;

		$category_slug = null;
		if(isset($_GET['category_slug']) && $_GET['category_slug'] != ''){
			// Log::info("Category exists");
			$category_slug = $_GET['category_slug'];
			$cache_key  = $tslug.'-'.$category_slug;
		}

		$location_id = null;
		if(isset($_GET['location_id']) && $_GET['location_id'] != ''){
			// Log::info("location exists");
			$location_id = $_GET['location_id'];
			$cache_key  = $cache_key.'-'.$location_id;
		}

		// Log::info($cache_key);
		// $cache_key = $this->updateCacheKey($cache_key);

		Log::info("cache key");
		Log::info($cache_key);


		$customer_email = null;
		if(in_array($tslug, Config::get('app.test_vendors'))){
			$jwt_token = Request::header('Authorization');
			if($jwt_token){
				$decoded = $this->customerTokenDecode($jwt_token);
				if($decoded){
					$customer_email = $decoded->customer->email;
				}
				if(!in_array($customer_email, Config::get('app.test_page_users'))){

					return Response::json(array("status"=>404), 404);
				}
			}else{

				return Response::json(array("status"=>404), 404);
			}
		}

		$cache_name = "finder_detail_app";

		if(isset($_GET['device_type']) && $_GET['device_type'] == 'android'){
			$cache_name = "finder_detail_android";
		}

		if(isset($_GET['device_type']) && $_GET['device_type'] == 'android' && isset($_GET['app_version']) && (float)$_GET['app_version'] >= 3.2){
			$cache_name = "finder_detail_android_3_2";
		}

		if(isset($_GET['device_type']) && $_GET['device_type'] == 'ios'){
			$cache_name = "finder_detail_ios";
		}

		if(isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && isset($_GET['app_version']) && (float)$_GET['app_version'] >= 3.2){
			$cache_name = "finder_detail_ios_3_2";
		}

		if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios','android']) && isset($_GET['app_version']) && (float)$_GET['app_version'] >= 4.4){
			$cache_name = "finder_detail_4_4";
		}

		if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios']) && isset($_GET['app_version']) && $_GET['app_version'] > '4.4.2'){
			$cache_name = "finder_detail_ios_4_4_3";
		}

		if(isset($_GET['device_type']) && in_array($_GET['device_type'],['android']) && isset($_GET['app_version']) && $_GET['app_version'] > '4.42'){
			$cache_name = "finder_detail_android_4_4_3";
		}
		Log::info($cache_name);
		$finder_detail = $cache ? Cache::tags($cache_name)->has($cache_key) : false;

		if(!$finder_detail){
			//Log::info("Not Cached in app");
			Finder::$withoutAppends=true;
			Service::$withoutAppends=true;
			Service::$setAppends=['active_weekdays','serviceratecard'];
			Finder::$setAppends=['finder_coverimage'];
			$finderarr = Finder::active()->where('slug','=',$tslug)
				->with(array('category'=>function($query){$query->select('_id','name','slug','detail_rating');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with('categorytags')
				->with('locationtags')
				->with('offerings')
				->with('facilities')
				->with(array('ozonetelno'=>function($query){$query->select('*')->where('status','=','1');}))
				->with(array('knowlarityno'=>function($query){$query->select('*')->where('status',true);}))
				->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
				->with(array('reviews'=>function($query){$query->select('_id','finder_id','customer_id','rating','description','updated_at')->where('status','=','1')->with(array('customer'=>function($query){$query->select('_id','name','picture')->where('status','=','1');}))->orderBy('updated_at', 'DESC')->limit(1);}))
				->first(array('_id','slug','title','lat','lon','category_id','category','location_id','location','city_id','city','categorytags','locationtags','offerings','facilities','coverimage','finder_coverimage','contact','average_rating','photos','info','manual_trial_enable','manual_trial_auto','trial','commercial_type','multiaddress','membership','flags','custom_link','videos'));

			$finder = false;

			if($finderarr){
				$finderarr = $finderarr->toArray();

				if(isset($finderarr['trial']) && $finderarr['trial']=='manual'){
					$finderarr['manual_trial_enable'] = '1';
				}

				if(!empty($finderarr['reviews'])){

					foreach ($finderarr['reviews'] as $rev_key => $rev_value) {

						if($rev_value['customer'] == null){
							
							$finderarr['reviews'][$rev_key]['customer'] = array("id"=>0,"name"=>"A Fitternity User","picture"=>"https://www.gravatar.com/avatar/0573c7399ef3cf8e1c215cdd730f02ec?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png");
						}
					}
				}

				if(isset($finderarr['videos'])){
					foreach($finderarr['videos'] as $key => $video){
						if(!isset($video['url']) || trim($video['url']) == ""){
							unset($finderarr['videos'][$key]);
						}
					}
				}

				$finder         =   array_except($finderarr, array('info','finder_coverimage','location_id','category_id','city_id','coverimage','findercollections','categorytags','locationtags','offerings','facilities','blogs'));
				$coverimage     =   ($finderarr['finder_coverimage'] != '') ? $finderarr['finder_coverimage'] : 'default/'.$finderarr['category_id'].'-'.rand(1, 19).'.jpg';
				array_set($finder, 'coverimage', $coverimage);

				$finder['info']              =   array_only($finderarr['info'], ['timing','delivery_timing','service']);

				$finder['today_opening_hour']           =   null;
				$finder['today_closing_hour']           =   null;
				$finder['open_now']                     =   false;
				$finder['open_close_hour_for_week']     =   [];

				if(isset($finderarr['category_id']) && $finderarr['category_id'] != ""){
					$finder['category_id'] = $finderarr['category_id'];
				}

				if(isset($finderarr['category_id']) && $finderarr['category_id'] == 5){




					if(isset($finderarr['services']) && count($finderarr['services']) > 0){

						//for servcie category gym
						$finder_gym_service  = [];
						$finder_gym_service = head(array_where($finderarr['services'], function($key, $value){
							if($value['category']['_id'] == 65){ return $value; }
						}));

						if(isset($finder_gym_service['trialschedules']) && count($finder_gym_service['trialschedules']) > 0){



							$all_weekdays                       =   $finder_gym_service['active_weekdays'];
							$today_weekday                      =   strtolower(date( "l", time()));

							foreach ($all_weekdays as $weekday){
								$whole_week_open_close_hour_Arr             =   [];
								$slots_start_time_24_hour_format_Arr        =   [];
								$slots_end_time_24_hour_format_Arr          =   [];

								$weekdayslots       =   head(array_where($finder_gym_service['trialschedules'], function($key, $value) use ($weekday){
									if($value['weekday'] == $weekday){
										return $value;
									}
								}));

								if(isset($weekdayslots['slots']) && count($weekdayslots['slots']) > 0){
									foreach ($weekdayslots['slots'] as $key => $slot) {

										$find       =   ["am","pm"];
										$replace    =   [""];
										$start_time_surfix_arr  =   explode(":", trim(str_replace($find, $replace, $slot['start_time'])) );
										$start_time_surfix      =   (isset($start_time_surfix_arr[1])) ? $start_time_surfix_arr[1] : "";
										$strart_time            =   floatval($slot['start_time_24_hour_format'].".".$start_time_surfix);
										$end_time_surfix_arr  =   explode(":", trim(str_replace($find, $replace, $slot['end_time'])) );
										$end_time_surfix      =   (isset($end_time_surfix_arr[1])) ? $end_time_surfix_arr[1] : "";
										$end_time            =   floatval($slot['end_time_24_hour_format'].".".$end_time_surfix);
										array_push($slots_start_time_24_hour_format_Arr, $strart_time);
										array_push($slots_end_time_24_hour_format_Arr, $end_time);

									}

									if(!empty($slots_start_time_24_hour_format_Arr) && !empty($slots_end_time_24_hour_format_Arr)){
										$opening_hour_arr       = explode(".",min($slots_start_time_24_hour_format_Arr));
										$opening_hour_surfix    = "";
										if(isset($opening_hour_arr[1])){
											$opening_hour_surfix = (strlen($opening_hour_arr[1]) == 1) ? $opening_hour_arr[1]."0" : $opening_hour_arr[1];
										}
										$opening_hour     = $opening_hour_arr[0].":".$opening_hour_surfix;
										$closing_hour_arr = explode(".",max($slots_end_time_24_hour_format_Arr));
										$closing_hour_surfix    = "";
										if(isset($closing_hour_arr[1])){
											$closing_hour_surfix = (strlen($closing_hour_arr[1]) == 0) ? "00" : "00";
											$closing_hour_surfix = (strlen($closing_hour_arr[1]) == 1) ? $closing_hour_arr[1]."0" : $closing_hour_arr[1];
										}else{
											$closing_hour_surfix =  "00";
										}
										$closing_hour     = $closing_hour_arr[0].":".$closing_hour_surfix;
										//   $finder['opening_hour'] = min($slots_start_time_24_hour_format_Arr);
										//   $finder['closing_hour'] = max($slots_end_time_24_hour_format_Arr)
										if($today_weekday == $weekday){
											$finder['today_opening_hour'] =  date("g:i A", strtotime(str_replace(".",":",$opening_hour)));
											$finder['today_closing_hour'] = date("g:i A", strtotime(str_replace(".",":",$closing_hour)));
										}
										$whole_week_open_close_hour[$weekday]['opening_hour'] = date("g:i A", strtotime(str_replace(".",":",$opening_hour)));
										$whole_week_open_close_hour[$weekday]['closing_hour'] = date("g:i A", strtotime(str_replace(".",":",$closing_hour)));
										array_push($whole_week_open_close_hour_Arr, $whole_week_open_close_hour);
									}

								}
							}

							//  $finder['open_close_hour_for_week'] = (!empty($whole_week_open_close_hour_Arr) && count($whole_week_open_close_hour_Arr) > 0) ? head($whole_week_open_close_hour_Arr) : null;

							if(!empty($whole_week_open_close_hour_Arr) && count($whole_week_open_close_hour_Arr) > 0){

								$weekWiseArr                    =   [];
								$whole_week_open_close_hour_Arr =   head($whole_week_open_close_hour_Arr);
								$weekdayDays                    =   ["monday","tuesday","wednesday","thursday","friday","saturday","sunday"];
								foreach ($weekdayDays as $day){
									if (array_key_exists($day, $whole_week_open_close_hour_Arr)) {
										$obj = ["day" => $day, "opening_hour" => $whole_week_open_close_hour_Arr[$day]["opening_hour"],  "closing_hour" => $whole_week_open_close_hour_Arr[$day]["closing_hour"]];
										array_push($weekWiseArr, $obj);
									}
								}
								$finder['open_close_hour_for_week'] = $weekWiseArr;
							}else{
								$finder['open_close_hour_for_week'] = [];
							}

						}// trialschedules

					}
				}
				$cult_Ids = array(12140,12141,12142,12143,12144,12145,12146,11110,4307,10514,9882,5986,9412,9881,9589);
				if((isset($finderarr['category_id']) && $finderarr['category_id'] == 41) || in_array($finderarr['_id'], $cult_Ids)){
					$finder['trial'] = 'disable';
					$finder['membership'] = 'disable';
				}

				if(isset($finderarr['custom_link'])){
					$finder['trial'] = 'disable';
					$finder['membership'] = 'disable';
					$finder["custom_link"] = "";
				}

				if($finder['today_opening_hour'] != NULL && $finder['today_closing_hour'] != NULL){

					$status = false;
					$startTime = DateTime::createFromFormat('h:i A', $finder['today_opening_hour'])->format('Y-m-d H:i:s');
					$endTime   = DateTime::createFromFormat('h:i A', $finder['today_closing_hour'])->format('Y-m-d H:i:s');

					if($finder['today_closing_hour'] == "12:00 AM"){
						$endTime = date('Y-m-d H:i:s',strtotime('+1 days',strtotime($endTime)));
					}

					if (time() >= strtotime($startTime) && time() <= strtotime($endTime)) {
						$status = true;
					}

					array_set($finder, 'open_now', $status);
				}

				

				array_set($finder, 'services', pluck( $finderarr['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category','subcategory','batches','vip_trial','meal_type','trial','membership', 'timings']  ));
				array_set($finder, 'categorytags', array_map('ucwords',array_values(array_unique(array_flatten(pluck( $finderarr['categorytags'] , array('name') ))))));
				array_set($finder, 'locationtags', array_map('ucwords',array_values(array_unique(array_flatten(pluck( $finderarr['locationtags'] , array('name') ))))));
				array_set($finder, 'offerings', array_map('ucwords',array_values(array_unique(array_flatten(pluck( $finderarr['offerings'] , array('name') ))))));
				array_set($finder, 'facilities', array_map('ucwords',array_values(array_unique(array_flatten(pluck( $finderarr['facilities'] , array('name') ))))));


				try {
					if(isset($finder['info']['service']) && $finder['info']['service'] != ""){

						$info_service = str_replace("<ul><li>","",$finder['info']['service']);
						$info_service = str_replace("</li></ul>","",$info_service);
						$finder['offerings'] = explode("</li><li>", $info_service);
						
					}
				} catch (Exception $e) {
					Log::info("info service Error");
				}

				if(count($finder['services']) > 0 ){
					$info_timing = $this->getInfoTiming($finder['services']);
					if(isset($finder['info']) && $info_timing != ""){
						$finder['info']['timing'] = $info_timing;
					}
					unset($finder['services']);
				}
				

				if(count($finder['photos']) > 0 ){
					$photoArr        =   [];
					usort($finder['photos'], "sort_by_order");
					foreach ($finder['photos'] as $photo) {
						$servicetags                =   (isset($photo['servicetags']) && count($photo['servicetags']) > 0) ? Service::whereIn('_id',$photo['servicetags'])->lists('name') : [];
						$photoObj                   =   array_except($photo,['servicetags']);
						$photoObj['servicetags']    =   $servicetags;
						$photoObj['tags']              =  (isset($photo['tags']) && count($photo['tags']) > 0) ? $photo['tags'] : []; 
						array_push($photoArr, $photoObj);
					}
					array_set($finder, 'photos', $photoArr);


					$service_tags_photo_arr             =   [];
					$info_tags_photo_arr                =   [];

					if(count($photoArr) > 0 ) {
						$unique_service_tags_arr    =   array_unique(array_flatten(array_pluck($photoArr, 'servicetags')));
						$unique_info_tags_arr       =   array_unique(array_flatten(array_pluck($photoArr, 'tags')));

						foreach ($unique_service_tags_arr as $unique_service_tags) {
							$service_tags_photoObj = [];
							$service_tags_photoObj['name'] = $unique_service_tags;
							$service_tags_photos = array_where($photoArr, function ($key, $value) use ($unique_service_tags) {
								if (in_array($unique_service_tags, $value['servicetags'])) {
									return $value;
								}
							});
							$service_tags_photoObj['photo'] = array_values($service_tags_photos);
							array_push($service_tags_photo_arr, $service_tags_photoObj);
						}

						foreach ($unique_info_tags_arr as $unique_info_tags) {
							$info_tags_photoObj = [];
							$info_tags_photoObj['name'] = $unique_info_tags;
							$info_tags_photos = array_where($photoArr, function ($key, $value) use ($unique_info_tags) {
								if (in_array($unique_info_tags, $value['tags'])) {
									return $value;
								}
							});
							$info_tags_photoObj['photo'] = array_values($info_tags_photos);
							array_push($info_tags_photo_arr, $info_tags_photoObj);
						}
					}

					array_set($finder, 'photo_service_tags', array_values($service_tags_photo_arr));
					array_set($finder, 'photo_info_tags', array_values($info_tags_photo_arr));

				}

				if($finderarr['category_id'] == 5){
					$finder['type'] = "gyms";
				}elseif($finderarr['category_id'] == 42 || $finderarr['category_id'] == 45){
					$finder['type'] = "healthytiffins";
				}elseif($finderarr['category_id'] == 41){
					$finder['type'] = "personaltrainers";
				}elseif($finderarr['category_id'] == 25){
					$finder['type'] = "dietitians and nutritionists";
				}elseif($finderarr['category_id'] == 46){
					$finder['type'] = "sport nutrition supliment stores";
				}else{
					$finder['type'] = "fitnessstudios";
				}
				$finder['type'] = getFinderType($finderarr['category_id']);

				$finder['assured']  =   array();
				$not_assured        =   [41,42,45,25,46,10,26,40];


				if(!in_array($finderarr['category_id'], $not_assured) && $finderarr['commercial_type'] != 0 ){

					$finder['assured'] = [
						["icon" => "https://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png", "name" =>"Real-Time Booking"],
						["icon" => "https://b.fitn.in/iconsv1/fitternity-assured/service-fullfillment.png", "name" =>"100% Service Fulfillment"],
						["icon" => "https://b.fitn.in/iconsv1/fitternity-assured/lowest-price.png", "name" =>"Lowest Price"]
					];
				}

				if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios','android']) && isset($_GET['app_version']) && (float)$_GET['app_version'] >= 4.4){

					$finder['assured'] = null;
					$assured_flag = false;

					if(isset($finder['flags']) && isset($finder['flags']['exclusive_partner']) && $finder['flags']['exclusive_partner']){
						$assured_flag = true;
						$finder['assured']['icon'] = 'https://a.fitn.in/fitimages/vendor/exclusive-selling.png';

					}

					if(isset($finder['flags']) && isset($finder['flags']['official_partner']) && $finder['flags']['official_partner']){
						$assured_flag = true;
						$finder['assured']['icon'] = 'https://a.fitn.in/fitimages/vendor/official-selling.png';
					}

					if($assured_flag){

						$finder['assured']['data'] = [
							[
								"icon" => "https://b.fitn.in/global/fitternityassured-app/assuredicon-alarm.png", 
								"name" =>"Real-Time Booking"
							],
							[
								"icon" => "https://b.fitn.in/global/fitternityassured-app/assuredicon-card.png",
								"name" =>"Secured Payment"
							],
							[
								"icon" => "https://b.fitn.in/global/fitternityassured-app/assuredicon-allthebest.png",
								"name" =>"100% Service Fulfillment"
							],
							[
								"icon" => "https://b.fitn.in/global/fitternityassured-app/assuredicon-lowestprice.png",
								"name" =>"Lowest Price"
							]
						];
					}

				}

				$finder['review_count']     =   Review::active()->where('finder_id',$finderarr['_id'])->count();
				$finder['average_rating']   =   (isset($finder['average_rating']) && $finder['average_rating'] != "") ? round($finder['average_rating'],1) : 0;
				
				if(isset($finderarr['ozonetelno']) && $finderarr['ozonetelno'] != '' && isset($finder['contact']['phone']) && $finder['contact']['phone'] != ""){

					$extension = (isset($finder['ozonetelno']['extension']) && $finder['ozonetelno']['extension'] != "") ? ",".$finder['ozonetelno']['extension'] : "";
					$finder['ozonetelno']['phone_number'] = '+'.$finder['ozonetelno']['phone_number'].$extension;
					$finder['contact']['phone'] = $finder['ozonetelno']['phone_number'];
					unset($finder['ozonetelno']);
					unset($finder['contact']['website']);
				}
				if(isset($finderarr['knowlarityno']) && $finderarr['knowlarityno'] != ''){
					$extension = (isset($finder['knowlarityno']['extension']) && $finder['knowlarityno']['extension'] != "") ? ",,".$finder['knowlarityno']['extension'] : "";
					$finder['knowlarityno']['phone_number'] = '+91'.$finder['knowlarityno']['phone_number'].$extension;
					$finder['contact']['phone'] = $finder['knowlarityno']['phone_number'];
					unset($finder['knowlarityno']);
					unset($finder['contact']['website']);
				}
				// if($finderarr['city_id'] == 4 || $finderarr['city_id'] == 8 || $finderarr['city_id'] == 9){
				// 	$direct_Fitternity_delhi_vendors = [4929,4968,5027,5066,5145,5355,5603,5609,5617,5709,6047,6411,6412,6499,6534,6876,6895,6979,7136,7448,7657,7907,7909,8289,8837,8878,9125,9171,9178,9201,9337,9397,9415,9417,9600,9624,9726,9728,9876,9878,9888,9913,10245,10568,10570,10624,10847,10957,10962,10993,11034,11040,11134,11176,11274,11374,6993,10987,8470,8823,6446,9855,11028,11030,11031,9854];
				// 	if(in_array($finderarr["_id"],$direct_Fitternity_delhi_vendors)){
				// 		$finder['contact']['phone'] = Config::get('app.contact_us_customer_number');
				// 	}else{
				// 		$finder['contact']['phone'] = $finderarr['contact']['phone'];
				// 	}
				// }
				if(isset($finderarr['multiaddress']) && count($finderarr['multiaddress']) > 0){
					$finder['multiaddress'] = $finderarr['multiaddress'];
				}else{
					$finder['multiaddress'] = array();
				}

				if(isset($finder['flags']) && isset($finder['flags']['state']) && in_array($finder['flags']['state'],['closed','temporarily_shut'])){

					$finder['membership'] = "disable";
					$finder['trial'] = "disable";
				}

				$data['status']                         =       200;
				$data['finder']                         =       $finder;


				$finder = Finder::active()->where('slug','=',$tslug)->first();
				if($finder){

					$data['finder']['services']          =        $this->getTrialSchedule($finder->_id,$finder->category);
					$data['finder']['bookmark']          =        false;
					$data['trials_detials']              =        [];
					$data['trials_booked_status']        =        false;
					$data['call_for_action_button']      =        "";

					$data['finder']['offer_icon']        =        "";
					$data['finder']['multiaddress']	     =		  $finder->multiaddress;

					if(isset($data['finder']['multiaddress']	) && count($data['finder']['multiaddress'])>0 && isset($data['finder']['multiaddress'][0]['location'])){
						$data['finder']['multiaddress']	[0]['location'] = [$finder['location']['name']];
					}
					
					$campaign_offer = false;
					
					// foreach($data['finder']['services'] as $serviceKey =>$service){
					// 	foreach($service['ratecard'] as $ratekey => $ratecard){
					// 		if(isset($ratecard['flags']) && isset($ratecard['flags']['campaign_offer']) && $ratecard['flags']['campaign_offer']){
					// 			$campaign_offer = true;
					// 			// break;
					// 		}

					// 		if(isset($ratecard['flags']) && isset($ratecard['flags']["offerFor"]) && $ratecard['flags']["offerFor"] == "women"){
					// 			if(!isset($ratecard['offers']) || count($ratecard['offers']) == 0){
					// 				$data['finder']['services'][$serviceKey]['ratecard'][$ratekey]['offers'] = [
					// 					[
					// 						"offer_icon"=>"https://b.fitn.in/global/finder/women-offer2.png",
					// 					]
					// 					];
					// 			}else{
					// 				$data['finder']['services'][$serviceKey]['ratecard'][$ratekey]['offers'][0]['offer_icon'] = "https://b.fitn.in/global/finder/women-offer2.png";
					// 			}
					// 		}
								
					// 	}
					// }
					
					// if($campaign_offer){
					// 	$data['finder']['offer_icon'] = "https://b.fitn.in/global/women-day/flat-30tag-app.png";
					// }else if($data['finder']['commercial_type']!=0 && !(isset($data['finder']['flags']) && in_array($data['finder']['flags'], ['closed', 'temporarily_shut'])) && !(isset($data['finder']['membership']) && $data['finder']['membership']=='disable' && isset($data['finder']['trial']) && $data['finder']['trial']=='disable') ){
					// 	$data['finder']['offer_icon'] = "https://b.fitn.in/global/women-day/surprise-tag.png";
					// }
					/*if(time() >= strtotime(date('2016-12-24 00:00:00')) && (int)$finder['commercial_type'] != 0){
						
						$data['finder']['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/offer_avail_red.png";
					}*/
					
					
					$category_id = Servicecategory::where('slug', $category_slug)->where('parent_id', 0)->first(['_id']);
					;

					$traction_exists = false;
					
					foreach($data['finder']['services'] as $service){
						if($service['traction']['sales'] > 0 || $service['traction']['trials'] > 0){
							$traction_exists = true;
						}
					}
					function cmp($a, $b)
		            {
		            	return $a['traction']['sales']+$a['traction']['trials']*0.8 <= $b['traction']['sales']+$b['traction']['trials']*0.8;
		            }
					if($traction_exists){
						usort($data['finder']['services'], "cmp");
					}

					if($location_id){
						$location_id = intval($location_id);
						$location_id_services =array_where($data['finder']['services'] , function($key, $value) use ($location_id){
							if($value['location_id'] == $location_id)
								{
								 return $value; 
								}
						});
						$non_location_id_services = array_where($data['finder']['services'] , function($key, $value) use ($location_id){
							if($value['location_id'] != $location_id)
								{
								 return $value; 
								}
						});

						$data['finder']['services'] = array_merge($location_id_services, $non_location_id_services);

						$location_id_address =array_where($data['finder']['multiaddress'] , function($key, $value) use ($location_id){
							if($value['location_id'][0] == $location_id)
								{
								 return $value; 
								}
						});

						$non_location_id_address =array_where($data['finder']['multiaddress'] , function($key, $value) use ($location_id){
							if($value['location_id'][0] != $location_id)
								{
								 return $value; 
								}
						});

						$data['finder']['multiaddress'] = array_merge($location_id_address, $non_location_id_address);

					}

					

					
					$category_slug_services = array();
					$category_slug_services = array_where($data['finder']['services'] , function($key, $value) use ($category_id){
							if($value['servicecategory_id'] == $category_id['_id'])
								{
								 return $value; 
								}
						});

					$non_category_slug_services = array();
					$non_category_slug_services = array_where($data['finder']['services'] , function($key, $value) use ($category_id){
							if($value['servicecategory_id'] != $category_id['_id'])
								{
								 return $value; 
								}
						});

				

		        	$data['finder']['services']  = array_merge($category_slug_services, $non_category_slug_services);


					$data['finder']['services'] = $this->sortNoMembershipServices($data['finder']['services'], 'finderDetailApp');

					foreach($data['finder']['services'] as &$serviceObj){
						if((isset($finder['membership']) && $finder['membership']=='disable') || (isset($serviceObj['membership']) && $serviceObj['membership']=='disable')){
							$serviceObj['offer_available'] = false;
						}
					}

					/*if(isset($data['finder']['services']['offer_icon_vendor'])){

						$data['finder']['offer_icon'] = $data['finder']['services']['offer_icon_vendor'];

						unset($data['finder']['services']['offer_icon_vendor']);
					}*/

					$category_id                                =   intval($finder['category']['_id']);
					$commercial_type                            =   intval($finder['commercial_type']);
					$bookTrialArr                               =   [5,6,12,42,43,32,36,7,35,13,10,11,47,14,25,9,8];



					if(in_array($category_id, $bookTrialArr)){
						$data['call_for_action_button']      =      "Book a Trial";

						if(in_array( 27 , $finder['facilities'])){
							$data['call_for_action_button']      =      "Book a Free Trial";
						}

						if($category_id == 42 ){
							$data['call_for_action_button']      =      "Book a Meal";
						}
					}

					if($commercial_type == 0 || in_array($finder['_id'], $cult_Ids)){
						$data['call_for_action_button']       =      "";
					}

					$data['finder']['pay_per_session']        =   false;
					$pay_per_session_abandunt_catyegory             =   [41,42,45,25,46,10,26,40];
					$service_count                                  =   Service::active()->where('finder_id',$finder['_id'])->count();

					if($finder['manual_trial_enable'] == "1" || $service_count == 0 || $finder['commercial_type'] == 0 || in_array($finder['category_id'],$pay_per_session_abandunt_catyegory)){
						$data['finder']['pay_per_session'] = false;
					}

					$data['finder']['dispaly_map']        =   true;
					$dispaly_map_abandunt_catyegory             =   [41,42,45,25];
					if(in_array($finder['category_id'],$dispaly_map_abandunt_catyegory)){
						$data['finder']['dispaly_map'] = false;
					}

					$device_type = ['ios','android'];

					if(isset($_GET['device_type']) && in_array($_GET['device_type'], $device_type) && isset($_GET['app_version']) && (float)$_GET['app_version'] >= 3.2 && isset($data['finder']['services']) && count($data['finder']['services']) > 0){

						$data['finder']['services_trial'] = $this->getTrialWorkoutRatecard($data['finder']['services'],$finder['type'],'trial', $data['finder']['trial']);
						$data['finder']['services_workout'] = $this->getTrialWorkoutRatecard($data['finder']['services'],$finder['type'],'workout session');
						
					}

				}


				$data = Cache::tags($cache_name)->put($cache_key, $data, Config::get('cache.cache_time'));

			}

		}

		$finderData = Cache::tags($cache_name)->get($cache_key);
		
		if(count($finderData) > 0 && isset($finderData['status']) && $finderData['status'] == 200){

			$finder = Finder::active()->where('slug','=',$tslug)->first();

			if($finder){

				$finderData['finder']['title'] = str_replace('crossfit', 'CrossFit', $finder['title']);
				$finderData['finder']['title'] = str_replace('Crossfit', 'CrossFit', $finder['title']);
				if(Request::header('Authorization')){

					$decoded                            =       decode_customer_token();
					$customer_email                     =       $decoded->customer->email;
					$customer_phone 					= 		"";

					if(isset($decoded->customer->contact_no)){
						$customer_phone                     =       $decoded->customer->contact_no;
					}

					$customer_id                        =       $decoded->customer->_id;

					$customer                           =       Customer::find((int)$customer_id);

					if($customer){
						$customer   = $customer->toArray();
					}

					if(isset($customer['bookmarks']) && is_array($customer['bookmarks']) && in_array($finder['_id'],$customer['bookmarks'])){
						$finderData['finder']['bookmark'] = true;
					}
					
					if($customer_phone != ""){

						$customer_trials_with_vendors       =       Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->orWhere('customer_email', $customer_email)->orWhere('customer_phone','LIKE','%'.substr($customer_phone, -9).'%');})
						->where('finder_id', '=', (int) $finder->_id)
						->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
						->get(array('id'));

					}else{

						$customer_trials_with_vendors       =       Booktrial::where('customer_email', $customer_email)
						->where('finder_id', '=', (int) $finder->_id)
						->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
						->get(array('id'));
					}

					$finderData['trials_detials']              =      $customer_trials_with_vendors;
					$finderData['trials_booked_status']        =      (count($customer_trials_with_vendors) > 0) ? true : false;

				}
			
			}

			if(isset($finder['flags']) && isset($finder['flags']['state']) && in_array($finder['flags']['state'],['closed','temporarily_shut'])){

				if($finder['flags']['state'] == 'temporarily_shut'){

					$finderData['finder']['offer_icon'] = "https://b.fitn.in/global/finder/temporarily_shut.png";

				}else{

					$finderData['finder']['offer_icon'] = "https://b.fitn.in/global/finder/closed.png";
					$finderData['finder']['services'] = [];
				}
			}	

			if(isset($finderData['finder']['trial']) && $finderData['finder']['trial'] == "disable" ){
				$finderData['call_for_action_button'] = "";
				$finderData['finder']['pay_per_session'] = false;
			}

			$device_type = ['ios','android'];

			if(isset($_GET['device_type']) && in_array($_GET['device_type'], $device_type) && isset($_GET['app_version']) && (float)$_GET['app_version'] >= 3.2 && isset($finderData['finder']['services']) && count($finderData['finder']['services']) > 0){

				if(isset($finderData['trials_booked_status']) && $finderData['trials_booked_status'] == true){

					$finderData['finder']['services'] = [];
					if(isset($finderData['finder']['services_workout'])){

						$finderData['finder']['services'] = $finderData['finder']['services_workout'];
					}

				}else{

					$finderData['finder']['services'] = [];
					if(isset($finderData['finder']['services_trial'])){

						$finderData['finder']['services'] = $finderData['finder']['services_trial'];
					}
				}

				
				if(!empty($finderData['finder']['services'])){
					$disable_button = [];
					$pay_per_session = false;
					foreach ($finderData['finder']['services'] as $key => $value) {
						if(isset($finderData['finder']['trial']) && $finderData['finder']['trial'] == "disable" ){
							$finderData['finder']['services'][$key]['trial'] = "disable";
							$value["trial"] == "disable";
						}

						if(isset($finderData['finder']['membership']) && $finderData['finder']['membership'] == "disable" ){

							$finderData['finder']['services'][$key]['membership'] = "disable";
							$value["membership"] == "disable";
						}

						if($finderData['finder']['commercial_type'] == 0){
							$finderData['finder']['services'][$key]['membership'] = "disable";
							$finderData['finder']['services'][$key]['trial'] = "disable";
						}

						//remove book and buy button frompersonal trainer
						if(isset($finderData['finder']['category_id']) && $finderData['finder']['category_id'] == 41){

							$finderData['finder']['services'][$key]['trial'] = "disable";
							$value["trial"] == "disable";

							$finderData['finder']['services'][$key]['membership'] = "disable";
							$value["membership"] == "disable";
						}


						if(isset($value["trial"]) && $value["trial"] == "disable"){
							$disable_button[] = "true";
						}else{
							$disable_button[] = "false";
						}
						$finderData['finder']['services'][$key]['pay_per_session'] = false;

						if(isset($finderData['finder']['pay_per_session']) && $finderData['finder']['pay_per_session'] && isset($finderData['finder']['trial']) && $finderData['finder']['trial'] != 'disable' && isset($finderData['finder']['services'][$key]['trial']) && $finderData['finder']['services'][$key]['trial'] != 'disable'){
							foreach($value['ratecard'] as $ratecard){
								if($ratecard['type']=='workout session'){
									$finderData['finder']['services'][$key]['pay_per_session'] = true;
									$pay_per_session = true;
								}
							}
						}
					}

					if(!$pay_per_session){
						$finderData['finder']['pay_per_session'] = false;
					}

					if(!in_array("false", $disable_button)){
						$finderData['call_for_action_button'] = "";
						$finderData['finder']['pay_per_session'] = false;
					}

				}
				if($finderData['finder']['commercial_type'] == 0){
					$finderData['finder']['trial'] = "disable";
					$finderData['finder']['membership'] = "disable";
				}

				if(isset($_GET['notification_id']) && $_GET['notification_id'] != ''){
					$finderData['finder']['contact']['phone'] = Config::get('app.followup_customer_number');
				}

				if(isset($_GET['service_id']) && $_GET['service_id'] != ''){
					$service_id = intval($_GET['service_id']);
					$id_service = array();
					$id_service = array_where($finderData['finder']['services'], function($key, $value) use ($service_id){
								if($value['_id'] == $service_id)
									{
									return $value; 
									}
							});

					$non_id_services = array();
					$non_id_services = array_where($finderData['finder']['services'], function($key, $value) use ($service_id){
								if($value['_id'] != $service_id)
									{
									return $value; 
									}
							});
					$finderData['finder']['services'] = array_merge($id_service, $non_id_services);

				}

				unset($finderData['finder']['services_workout']);
				unset($finderData['finder']['services_trial']);
			}

			// $finderData['show_reward_banner'] = true;

			$finderData['finder']['call_interrupt'] = null;

			if($finderData['finder']['commercial_type'] != 0 || $finderData['call_for_action_button'] != ""){

				$call_interrupt = [
					'title'=>'Calling to book a trial at '.$finderData['finder']['title'],
					'description'=>'Book online for faster experience when it comes to your fitness choices!',
					'button_text'=>'Book Trial Online',
					'chat_enable'=>true,
					'call_enable'=>true
				];

				if(isset($finderData['trials_booked_status']) && $finderData['trials_booked_status'] == true){
					$call_interrupt['title'] = 'Calling to book a session at '.$finderData['finder']['title'];
					$call_interrupt['button_text'] = 'Book Session Online';
				}

				if($finderData['finder']['type'] == "healthytiffins"){
					$call_interrupt['title'] = 'Calling to book a trial meal at '.$finderData['finder']['title'];
					$call_interrupt['button_text'] = 'Book Trial Meal Online';
				}

				$finderData['finder']['call_interrupt'] = $call_interrupt;
			}

			if(isset($finderData['finder']['category_id']) && $finderData['finder']['category_id'] == 42){
				unset($finderData['finder']['lat']);
				unset($finderData['finder']['lon']);
			}

					

		}else{

			$finderData['status'] = 404;
		}

		return Response::json($finderData,$finderData['status']);

	}




	public function getTrialWorkoutRatecard($finderservices,$findertype,$type, $finder_trial = null){

		$finderservicesArr  =   [];

		foreach ($finderservices as $finderservice){

			$finderserviceObj   =   array_except($finderservice,['ratecard']);
			$ratecardArr        =   [];

			if(isset($finderservice['ratecard']) && count($finderservice['ratecard']) > 0){

				$ratecard = Ratecard::where('type',$type)->where('service_id', intval($finderserviceObj['_id']))->first();

				if($ratecard){
					$ratecard = $ratecard->toArray();
					$ratecard['offers'] = [];

					if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
	                    $ratecard_price = $ratecard['special_price'];
	                }else{
	                    $ratecard_price = $ratecard['price'];
	                }

	                $ratecard['cashback_on_trial'] = "";

					if($ratecard_price > 0 && $type == 'trial'){
						$ratecard['cashback_on_trial'] = "100% Cashback";
					}

					if((isset($finderservice['trial']) && $finderservice['trial']=='manual' || $finder_trial=='manual') && $ratecard['type'] == 'trial'){
						if(isset($_GET['app_version']) && isset($_GET['device_type']) && (($_GET['device_type'] == 'android' && $_GET['app_version'] > 4.42) || ($_GET['device_type'] == 'ios' && version_compare($_GET['app_version'], '4.4.2') > 0))){
							Log::info($ratecard['_id']);
							$ratecard['manual_trial_enable'] = "1";
							unset($ratecard['direct_payment_enable']);
							Log::info("manual_trial_enable");
							
						}else{
							$ratecard['direct_payment_enable'] = "0";
							Log::info("direct_payment_enable");
							
						}
					}
					array_push($ratecardArr, $ratecard);
				}
				// return $finderservice['ratecard'];
				// exit;
				foreach ($finderservice['ratecard'] as $ratecard){

					if(isset($ratecard['flags'])){

						if(isset($ratecard['flags']['discother']) && $ratecard['flags']['discother'] == true){
							if(isset($ratecard['offers']) && count($ratecard['offers']) == 0){
								continue;
							}
						}

						if(isset($ratecard['flags']['disc25or50']) && $ratecard['flags']['disc25or50'] == true){
							if(isset($ratecard['offers']) && count($ratecard['offers']) == 0){
								continue;
							}
						}
					}


					array_push($ratecardArr, $ratecard);
				}

			}else{
				$finderserviceObj['ratecard'] = [];
			}

			$finderserviceObj['ratecard'] = $ratecardArr;
			
			array_push($finderservicesArr, $finderserviceObj);
		}

		return $finderservicesArr;

	}

	public function customerTokenDecode($token){

        $jwt_token = $token;

        $jwt_key = Config::get('app.jwt.key');
        $jwt_alg = Config::get('app.jwt.alg');
        try {
            $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

        }catch (Exception $e) {
            // Log::info($e);
            return null;
        }
        
        return $decodedToken;
    }

	public function sortNoMembershipServices($serviceArray, $from){
		if($from == 'finderdetail'){
			$ratecard_key = 'serviceratecard';
		}else{
			$ratecard_key = 'ratecard';
		}
		$membership_services = array_where($serviceArray, function($key, $value) use ($ratecard_key){
			$ratecard_array = $value[$ratecard_key];
			$membership_exists = false;
			foreach($ratecard_array as $ratecard){
				if(isset($ratecard['type']) && $ratecard['type']=='membership'){
					$membership_exists = true;
				}
			}
			if($membership_exists)
				{
					return $value; 
				}
		});

		$no_membership_services = array_where($serviceArray, function($key, $value) use ($ratecard_key){
			$ratecard_array = $value[$ratecard_key];
			$membership_exists = false;
			foreach($ratecard_array as $ratecard){
				if(isset($ratecard['type']) && $ratecard['type']=='membership'){
					$membership_exists = true;
				}
			}
			if(!$membership_exists)
				{
					return $value; 
				}
		});

		return array_merge($membership_services, $no_membership_services);

	}


    public function getDetailRating(){

    	$request = $_REQUEST;

    	if(!isset($request['finder_id']) && !isset($request['category_id'])){
    		return Response::json(array('status'=>401,'message'=>'finder or category is required'),401);
    	}

    	$category_id = "";

    	if(isset($request["finder_id"]) && $request["finder_id"] != ""){

	    	$finder_id = (int) $request["finder_id"];

	    	$finder = Finder::find($finder_id,array('_id','category_id'));

	    	if(!$finder){
	    		return Response::json(["message"=>"Vendor not found","status"=>404], 404);
	    	}

	    	$category_id = (int)$finder->category_id;
	    }

	    if(isset($request["category_id"]) && $request["category_id"] != ""){

	    	$category_id = (int) $request["category_id"];
	    }

	    if($category_id == ""){
	    	return Response::json(["message"=>"Category ID Missing","status"=>404], 404);
	    }

	    $category = Findercategory::find($category_id,array('_id','name','slug','detail_rating'));

    	if(!$category){
    		return Response::json(["message"=>"Category not found","status"=>404], 404);
    	}

    	$category = $category->toArray();

    	$category["status"] = 200;

    	return Response::json($category, 200);
    }

	public function fitternityDietVedorDetail(){
		try{
			Service::$withoutAppends=true;
			Service::$setAppends=['active_weekdays','serviceratecard'];
			$finder = Finder::where('title', 'Fitternity Diet Vendor')
			->with(array('services'=>function($query){
				$query->active()->select(array('id', 'name','finder_id', 'short_description','body','what_i_should_expect', 'workout_intensity','ordering'))->orderBy('ordering');
				}))
			->first();
			return array('finder'=>$finder, 'status'=>200);
		}catch(Exception $error){
			return $errorMessage = $this->errorMessage($error);
		}
	}

	public function getbrands($city,$brand_id){
		$city = getmy_city($city);
		$city_obj = City::active()->where('slug',$city)->get(array('_id'));
		$brand_id = (int) $brand_id;
		if(count($city_obj) > 0){
			$finders = Finder::active()->where("brand_id",$brand_id)->where('city_id',$city_obj[0]->_id)->with('location')->get(array('title','location_id','slug'));
		}else{
			$finders = Finder::active()->where("brand_id",$brand_id)->where('city_id',10000)->where('custom_city',new MongoRegex('^'.$city.'$/i'))->get(array('title','custom_location','slug'));
		}
		return $finders;
	}

	public function fitternityPersonalTrainersDetail(){
		try{
			$finder = Finder::where('title', Config::get('app.fitternity_personal_trainers'))
			->with(array('services'=>function($query){
				$query->active()->select(array('id', 'name','finder_id', 'short_description','body','what_i_should_expect', 'workout_intensity','ordering'))->orderBy('ordering');
				}))
			->first();
			return array('finder'=>$finder, 'status'=>200);
		}catch(Exception $error){
			return $errorMessage = $this->errorMessage($error);
		}
	}

	public function updateCacheKey($key){
		$jwt_token = Request::header('Authorization');
		$customer_email = "";
		if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
			
			$decoded = $this->customerTokenDecode($jwt_token);
			
			if($decoded){
				$customer_email = $decoded->customer->email;
				Log::info("customer_email");
				Log::info("$customer_email");
				
			}

		}

		if($this->utilities->checkCorporateLogin($customer_email)){
			$key = $key.'-corporate';
			Log::info("key");
			Log::info($key);
		}

		return $key;
	}


	public function vendorFooter($finderdata){

		$location_slug = $finderdata["location"]["slug"];
		$location_name = ucwords($finderdata["location"]["name"]);
		$city_slug = $finderdata["city"]["slug"];
		$city_name = ucwords($finderdata["city"]["name"]);

		$data = [
			[
				'title'=>'Explore Fitness in '.$location_name,
				'row'=>[
					[
						'name'=>'Gyms in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/gyms'
					],
					[
						'name'=>'Zumba Classes in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/zumba-classes'
					],
					[
						'name'=>'Cross Functional Fitness in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/functional-training'
					],
					[
						'name'=>'Yoga Classes in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/yoga-classes'
					],
					[
						'name'=>'Pilates Classes in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/pilates-classes'
					]

				]
			],
			[
				'title'=>'Explore Fitness in '.$location_name,
				'row'=>[
					[
						'name'=>'MMA & Kickboxing Classes in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/mma-and-kick-boxing-classes'
					],
					[
						'name'=>'Fitness Studios in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/fitness-studios'
					],
					[
						'name'=>'Dance Classes in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/dance-classes'
					],
					[
						'name'=>'Marathon Training in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/marathon-training'
					],
					[
						'name'=>'Swimming in '.$location_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/'.$location_slug.'/swimming-pools'
					]

				]
			],
			[
				'title'=>'Find classes for beginners in '.$city_name,
				'row'=>[
					[
						'name'=>' Yoga classes for beginners',
						'link'=> Config::get('app.website').'/'.$city_slug.'/yoga-classes'
					],
					[
						'name'=>'Zumba classes for beginners',
						'link'=> Config::get('app.website').'/'.$city_slug.'/zumba-classes'
					],
					[
						'name'=>'CrossFit classes for beginners',
						'link'=> Config::get('app.website').'/'.$city_slug.'/functional-training'
					],
					[
						'name'=>'Gyms for beginners',
						'link'=> Config::get('app.website').'/'.$city_slug.'/gyms'
					],
					[
						'name'=>'Dance classes for beginners',
						'link'=> Config::get('app.website').'/'.$city_slug.'/dance-classes'
					],
					[
						'name'=>'Pilates for beginners',
						'link'=> Config::get('app.website').'/'.$city_slug.'/pilates-classes'
					]

				]
			],
			[
				'title'=>'24 hours open gyms in '.$city_name,
				'row'=>[
					[
						'name'=>'24 hours open gyms near me in '.$city_name,
						'link'=> Config::get('app.website').'/'.$city_slug.'/gyms/24-hour-facility'
					]

				]
			]
		];

		$request = [
            "offset" => 0,
            "limit" => 15,
            "radius" =>"5km",
            "category"=>"",
            "lat"=>"",
            "lon"=>"",
            "city"=>strtolower($finderdata["city"]["name"]),
            "region"=>[strtolower($finderdata["location"]["name"])],
            "keys"=>[
              "slug",
              "name"
            ],
            "not"=>[
            	"vendor"=>[(int)$finderdata["_id"]]
            ]
        ];

	    $geoLocationFinder = geoLocationFinder($request);

	    $finders = [];

	    if(count($geoLocationFinder)){

		    foreach ($geoLocationFinder as $value) {

		    	$finders[] = [
		    		'name'=>$value['title'],
		    		'link'=> Config::get('app.website').'/'.$value['slug']
		    	];
		    }

		    $finders = array_chunk($finders,5);
		}

		if(isset($finders[0])){
		    $data[] = [
		    	'title'=>'Recommended in '.$location_name,
		    	'row'=> $finders[0]
		    ];
		}

		if(isset($finders[1])){
		    $data[] = [
		    	'title'=>'Top Fitness Options in '.$location_name,
		    	'row'=> $finders[1]
		    ];
		}

		if(isset($finders[2])){
		    $data[] = [
		    	'title'=>'Trending Places in '.$location_name,
		    	'row'=> $finders[2]
		    ];
		}

		$data[] = [
			'title'=>'Fitness Options for Ladies',
			'row'=>[
				[
					'name'=>'Gyms for Ladies',
					'link'=> Config::get('app.website').'/ladies-gym-'.strtolower($city_name)
				],
				[
					'name'=>'Yoga Classes for Ladies',
					'link'=> Config::get('app.website').'/ladies-yoga-'.strtolower($city_name)
				],
				[
					'name'=>'Fitness Studios for Ladies',
					'link'=> Config::get('app.website').'/ladies-fitness-studios-'.strtolower($city_name)
				]

			]
		];

		return $data;

	}

	public function reportReview(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);

		$rules = [
		    'review_id' => 'required|integer|numeric',
		    'description' => 'required'
		];

		$data = Input::json()->all();

		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' => error_message($validator->errors())),400);
		}

		$review = Review::find($data['review_id']);

		if($review){

			$review_data = $review->toArray();

			$reports = [];

			if(!empty($review_data['reports'])){

				$reports = $review_data['reports'];
			}

			$data = [
				'customer_id' => (int)$decoded->customer->_id,
				'customer_name' => $decoded->customer->name,
				'customer_email' => $decoded->customer->email,
				'customer_phone' => $decoded->customer->contact_no,
				'description'=>$data['description']
			];

			$reports[] = $data;

			$review->update(['reports'=>$reports]);

			$data['fitternity_email'] = [
				'pranjalisalvi@fitternity.com',
				'sailismart@fitternity.com'
			];
			
			$data['review'] = $review_data['description'];

			Finder::$withoutAppends=true;

			$finder = Finder::with(array('location'=>function($query){$query->select('name');}))->with(array('city'=>function($query){$query->select('name');}))->find($review_data['finder_id'],['_id','title','location_id','city_id']);

			$data['finder_name'] = ucwords($finder['title']);
			$data['finder_location'] = ucwords($finder['location']['name']);
			$data['finder_city'] = ucwords($finder['city']['name']);
			$data['city_id'] = (int)$finder['city']['_id'];

			$this->findermailer->reportReview($data);

			$response = ['status' => 200, 'message' => 'Reported a review Successfully'];

			return Response::json($response, 200);

		}

		$response = ['status' => 400, 'message' => 'Review Not Found'];

		return Response::json($response, 400);
	}

	public function kisokDashboard($finder_id){

		Finder::$withoutAppends=true;

		$finder = Finder::find((int)$finder_id);

		if(!$finder){

			return Response::json(["message"=>"Vendor not found","status"=>404], 200);
		}

		$response = [
			"status"=>200,
			"message"=>"Successfully retrieved.",
			"response"=>[
				"about"=>[
					"description"=>"Fitternity is the one stop shop for everything fitness. Discover, compare, try & buy through fitternity.",
					"details"=>[
						[
							"title"=>"Book unlimited free trials to explore fitness forms around you.",
							"image"=>"https://b.fitn.in/global/toi/mfp/mfpmum-26th/point1.png"
						],
						[
							"title"=>"Buy Gym / group class memberships across fitness centres in your city.",
							"image"=>"https://b.fitn.in/global/toi/mfp/mfpmum-26th/point2.png"
						],
						[
							"title"=>"Get personalised online diet consultation for workout performance enhancement.",
							"image"=>"https://b.fitn.in/global/toi/mfp/mfpmum-26th/point3.png"
						],
						[
							"title"=>"Book healthy, calorie counted yet tasty tiffin subscription.",
							"image"=>"https://b.fitn.in/global/toi/mfp/mfpmum-26th/point4.png"
						]
					]
				],
				"options"=>[
					[
						"title"=>"Access Fitternity Booking",
						"description"=>"Quick step to activate your trial/session",
						"image"=>"https://b.fitn.in/global/tabapp-homescreen/access-trials-small.png",
						"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/accesstrial-big-1.png",
						"id"=>1,
						'type'=>'access_booktrial'
					],
					[
						"title"=>"Buy Membership",
						"description"=>"Quick buy with free rewards & flexible payment options.",
						"image"=>"https://b.fitn.in/global/tabapp-homescreen/explorememberships-small.png",
						"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/explorememberships-big-1.png",
						"id"=>2,
						"type"=>'memberships'
					],
					[
						"title"=>"Post a Review",
						"description"=>"Rate your experience & help fellow fitness enthusiasts.",
						"image"=>"https://b.fitn.in/global/tabapp-homescreen/post-review-small.png",
						"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/postreview-big.png",
						"id"=>3,
						'type'=>'post_review'
					]
				],
				"title"=>"Welcome to ".ucwords($finder['title']),
				"powered"=>"Powered by Fitternity"
			]
		];


		if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.08){

			$response["response"]["options"]= [
				[
					"title"=>"Access Fitternity Booking",
					"description"=>"Quick step to activate your trial/session",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/access-trials-small.png",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/accesstrial-big-1.png",
					"id"=>1,
					'type'=>'access_booktrial'
				],
				[
					"title"=>"Buy Membership",
					"description"=>"Quick buy with free rewards & flexible payment options.",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/explorememberships-small.png",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/explorememberships-big-1.png",
					"id"=>2,
					"type"=>'memberships'
				],
				[
					"title"=>"Schedule New Bookings",
					"description"=>"Pick a day & slot that works for you and get started",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/book-instant-trial-small.jpg",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/book-instant-trial-big-1.jpg",
					"id"=>6,
					'type'=>'booktrials'
				],
				[
					"title"=>"Activate Fitternity Membership",
					"description"=>"Quick step to activate your membership",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/membership-small.jpg",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/membership1-big1.jpg",
					"id"=>5,
					"type"=>'activate_membership'
				],
				[
					"title"=>"Post a Review",
					"description"=>"Rate your experience & help fellow fitness enthusiasts.",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/post-review-small.png",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/postreview-big.png",
					"id"=>3,
					'type'=>'post_review'
				],				
				[
					"title"=>"Fitternity Advantage",
					"description"=>"Buy through Fitterntiy & get access to these amazing rewards",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/reward-small.jpg",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/rewards-big-picture-1.jpg",
					"id"=>7,
					'type'=>'rewards'
				],
			];
		}


		return Response::json($response,$response['status']);
	}

	public function getServiceDuration($ratecard){

        $duration_day = 1;

        if(isset($ratecard['validity']) && $ratecard['validity'] != '' && $ratecard['validity'] != 0){

            $duration_day = $ratecard['validity'];
        }

        if(isset($ratecard['validity']) && $ratecard['validity'] != '' && $ratecard['validity_type'] == "days"){

            $ratecard['validity_type'] = 'Days';

            if(($ratecard['validity'] % 30) == 0){

                $month = ($ratecard['validity']/30);

                if($month == 1){
                    $ratecard['validity_type'] = 'Month';
                    $ratecard['validity'] = $month;
                }

                if($month > 1 && $month < 12){
                    $ratecard['validity_type'] = 'Months';
                    $ratecard['validity'] = $month;
                }

                if($month == 12){
                    $ratecard['validity_type'] = 'Year';
                    $ratecard['validity'] = 1;
                }

            }
              
        }

        if(isset($ratecard['validity']) && $ratecard['validity'] != '' && $ratecard['validity_type'] == "months"){

            $ratecard['validity_type'] = 'Months';

            if($ratecard['validity'] == 1){
                $ratecard['validity_type'] = 'Month';
            }

            if(($ratecard['validity'] % 12) == 0){

                $year = ($ratecard['validity']/12);

                if($year == 1){
                    $ratecard['validity_type'] = 'Year';
                    $ratecard['validity'] = $year;
                }

                if($year > 1){
                    $ratecard['validity_type'] = 'Years';
                    $ratecard['validity'] = $year;
                }
            }
              
        }

        if(isset($ratecard['validity']) && $ratecard['validity'] != '' && $ratecard['validity_type'] == "year"){

            $year = $ratecard['validity'];

            if($year == 1){
                $ratecard['validity_type'] = 'Year';
            }

            if($year > 1){
                $ratecard['validity_type'] = 'Years';
            }
              
        }

        $service_duration = "";

        if($ratecard['duration'] > 0){
            $service_duration .= $ratecard['duration'] ." ".ucwords($ratecard['duration_type']);
        }
        if($ratecard['duration'] > 0 && $ratecard['validity'] > 0){
            $service_duration .= " - ";
        }
        if($ratecard['validity'] > 0){
            $service_duration .=  $ratecard['validity'] ." ".ucwords($ratecard['validity_type']);
        }

        ($service_duration == "") ? $service_duration = "-" : null;

        return $service_duration;
    }

	public function ratecardMembership($service_id){

		$response = [
			'status'=>200,
			'message'=>'Success'
		];

		$service_id = (int) $service_id;

		$ratecards = Ratecard::where('service_id',$service_id)->whereIn('type',['membership','packages'])->get();

		$ratecard_data = [];

		foreach ($ratecards as $ratecard_key => $ratecard_value) {

			if($ratecard_value['direct_payment_enable'] == '0'){

				unset($ratecards[$ratecard_key]);
				continue;
			}

			if(isset($ratecard_value['special_price']) && $ratecard_value['special_price'] != 0){
                $ratecard_price = $ratecard_value['special_price'];
            }else{
                $ratecard_price = $ratecard_value['price'];
            }

            $data = [
            	'finder_id'=>$ratecard_value['finder_id'],
            	'service_id'=>$ratecard_value['service_id']
            ];

            $data['amount'] = $ratecard_price;
            $data['ratecard_id'] = $ratecard_value['_id'];
            $data['service_duration'] = $this->getServiceDuration($ratecard_value);

            $ratecard_data[] = $data; 

		}

		$response['ratecards'] = $ratecard_data;

		return Response::json($response,200);
	}


	public function getVendorTrainer($finder_id = false){

		if($this->vendor_token){

			$decodeKioskVendorToken = decodeKioskVendorToken();

	        $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

	        $finder_id = (int)$vendor['_id'];
		}

		$getVendorTrainer = [];

		if($finder_id){
			$getVendorTrainer = $this->utilities->getVendorTrainer($finder_id);
		}

		$response['assisted_by'] = $getVendorTrainer;
		$response['assisted_by_image'] = "https://b.fitn.in/global/tabapp-homescreen/freetrail-summary/trainer.png";
		$response['status'] = 200;

		return Response::json($response,200);	
	}

	public function kioskTabLastLoggedIn(){

        $serialNumber = Request::header('Device-Serial');

        if($serialNumber != "" && $serialNumber != null && $serialNumber != 'null'){

        	$app_version = Request::header('App-Version');

            $kiosk_tab = KioskTab::where('serialNumber',$serialNumber)->first();

            if($kiosk_tab){

                $kiosk_tab->last_logged_in = time();

                if($app_version != "" && $app_version != null && $app_version != 'null'){
                	$kiosk_tab->app_version = $app_version;
                }
                
                $kiosk_tab->update();
            }
        }

        return "success";

	}
	
	function getCheapestWorkoutSession($services){

		$price = 0;

		foreach($services as $service){
			if(isset($service['serviceratecard'])){
				foreach($service['serviceratecard'] as $ratecard){
					$ratecard_price = isset($ratecard['special_price']) &&  $ratecard['special_price'] != 0 ? $ratecard['special_price'] : $ratecard['price'];
					if($ratecard['type'] == 'workout session' && ($price == 0 || $ratecard_price < $price)){
						$price = $ratecard_price;
					}
				}
			}
		}

		return $price;
	}

	function getTermsAndCondition(){

		$tnc = [
			"title"=>"Terms & Conditions",
			"description"=>""
		];

		$finder_id = "";

		if(isset($_REQUEST['ratecard_id']) && $_REQUEST['ratecard_id'] != ""){

			$ratecard = Ratecard::find((int)$_REQUEST['ratecard_id']);

			if($ratecard){

				$finder_id = (int) $ratecard['finder_id'];
			}

		}

		if(isset($_REQUEST['service_id']) && $_REQUEST['service_id'] != ""){

			$service = Service::find((int)$_REQUEST['service_id']);

			if($service){

				$finder_id = (int) $service['finder_id'];
			}

		}

		if(isset($_REQUEST['finder_id']) && $_REQUEST['finder_id'] != ""){

			$finder_id = (int)$_REQUEST['finder_id'];
		}

		if($finder_id != ""){

			$finder = Finder::find($finder_id);

			if($finder){

				$finder = $finder->toArray();

				$location = Location::find((int) $finder['location_id']);

				$location_name = "";
				if($location && isset($location['name'])){
					$location_name = ", ".ucwords($location['name']);
				}

				if(isset($ratecard) && isset($ratecard['type']) && in_array($ratecard['type'],['membership','packages'])){

					$tnc['description'] .= "<b> - </b>  Discount varies across different outlets depending on slot availability.";
					$tnc['description'] .= "<br/><br/><b> - </b>  For memberships reserved by part payment and not fully paid for on date of joining, 5% of total membership value will be deducted as convenience fees & the remaining will be transferred in the wallet as Fitcash+ . The membership will also be terminated.";
					$tnc['description'] .= "<br/><br/><b> - </b>  Memberships once purchased are not transferrable or resalable.";
					$tnc['description'] .= "<br/><br/><b> - </b>  For any Refund/cancellation queries refer to <a href='https://www.fitternity.com/refund-cancellation'> https://www.fitternity.com/refund-cancellation</a>";
					$tnc['description'] .= "<br/><br/><b> - </b>  For any Offer related queries refer to <a href='https://www.fitternity.com/offer-usage'>https://www.fitternity.com/offer-usage</a>";

				}else{

					$tnc['description'] .= "<b> - </b>  I recognise that staff at ".ucwords($finder['title']).$location_name.", their associates and staff at Fitternity Health E Solutions are not able to provide medical advice or assess whether it is suitable for me to participate in programs.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I participate at my own risk. I acknowledge that as with any exercise program, there are risks, including increased heart stress and the chance of musculoskeletal injuries.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I warrant that I am physically and mentally well enough to proceed with the classes and have furnished true details in the form above.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I hereby waive, release and forever discharge ".ucwords($finder['title']).", Fitternity Health E Solutions and all their associates from all liabilities for injures or damages resulting from my participation in fitness activities and classes.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I understand no refunds or transfer / extension of services will be issued for unused classes, sessions and services.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I have read and understand the advice given above.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I assume the risk of and responsibility of personal property loss or damage.";

				}

				if(isset($finder['info']['terms_and_conditions']) && $finder['info']['terms_and_conditions'] != ""){

					$terms_and_conditions = $finder['info']['terms_and_conditions'];

					$terms_and_conditions = str_replace('<ol>','',$terms_and_conditions);
					$terms_and_conditions = str_replace('</ol>','',$terms_and_conditions);
					$terms_and_conditions = str_replace('</ul>','',$terms_and_conditions);
					$terms_and_conditions = str_replace('</ul>','',$terms_and_conditions);
					$terms_and_conditions = str_replace('</li>','',$terms_and_conditions);
					$terms_and_conditions = str_replace('<li>','<br/><br/><b> - </b>  ',$terms_and_conditions);

					$tnc['description'] .= $terms_and_conditions;
				}
			}

		}

		$response = [
			"tnc"=>$tnc,
			"status"=>200,
			"message"=>"Success"
		];

		return $response;

	}
	

}
