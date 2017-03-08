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

	public function __construct(FinderMailer $findermailer, Cacheapi $cacheapi) {

		parent::__construct();
		$this->elasticsearch_default_url        =   "http://".Config::get('app.es.host').":".Config::get('app.es.port').'/'.Config::get('app.es.default_index').'/';
		$this->elasticsearch_url                =   "http://".Config::get('app.es.host').":".Config::get('app.es.port').'/';
		$this->elasticsearch_host               =   Config::get('app.es.host');
		$this->elasticsearch_port               =   Config::get('app.es.port');
		$this->elasticsearch_default_index      =   Config::get('app.es.default_index');
		$this->findermailer                     =   $findermailer;
		$this->cacheapi                     =   $cacheapi;
		$this->appOfferDiscount 				= Config::get('app.app.discount');;
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
		$cache_key = $tslug;
		
		$category_slug = null;
		if(isset($_GET['category_slug']) && $_GET['category_slug'] != ''){
			Log::info("Category exists");
			$category_slug = $_GET['category_slug'];
			$cache_key  = $tslug.'-'.$category_slug;
		}

		$customer_email = null;
		if(in_array($tslug, Config::get('app.test_vendors'))){
			$jwt_token = Request::header('Authorization');
			if($jwt_token){
				$decoded = $this->customerTokenDecode($jwt_token);
				if($decoded){
					$customer_email = $decoded->customer->email;
				}
				if(!in_array($customer_email, Config::get('app.test_page_users'))){

					return Response::json("not found", 404);
				}
			}else{

				return Response::json("not found", 404);
			}
		}

		$finder_detail = $cache ? Cache::tags('finder_detail')->has($tslug) : false;

		if(!$finder_detail){
			Log::info("Not cached in detail");

			$finderarr = Finder::active()->where('slug','=',$tslug)
				->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with('findercollections')
				->with('blogs')
				->with('categorytags')
				->with('locationtags')
				->with('offerings')
				->with('facilities')
				->with(array('ozonetelno'=>function($query){$query->select('*')->where('status','=','1');}))
				->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->where('status','=','1')->orderBy('ordering', 'ASC');}))
				->with(array('reviews'=>function($query){$query->select('*')->where('status','=','1')->orderBy('_id', 'DESC')->limit(5);}))
				// ->with(array('reviews'=>function($query){$query->select('*')->where('status','=','1')->orderBy('_id', 'DESC');}))
				->first();
		

			unset($finderarr['ratecards']);

			$finder = null;	

			if($finderarr){

				// $ratecards           =   Ratecard::with('serviceoffers')->where('finder_id', intval($finder_id))->orderBy('_id', 'desc')->get();
				$finderarr = $finderarr->toArray();


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

				if(isset($finderarr['category_id']) && $finderarr['category_id'] == 5){

//                    echo "ad";exit;
					if(isset($finderarr['services']) && count($finderarr['services']) > 0){
						//for servcie category gym
						$finder_gym_service  = [];
						$finder_gym_service = head(array_where($finderarr['services'], function($key, $value){
							if($value['category']['_id'] == 65){ return $value; }
						}));



                       // return $finder_gym_service; exit;

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

				$finder['review_count']     =   Review::active()->where('finder_id',$finderarr['_id'])->count();

				$finder['offer_icon'] = "";
				$finder['offer_icon_mob'] = "";

				$finder['associate_finder'] = null;
				if(isset($finderarr['associate_finder']) && $finderarr['associate_finder'] != ''){

					$associate_finder = array_map('intval',$finderarr['associate_finder']);
					$associate_finder = Finder::active()->whereIn('_id',$associate_finder)->get(array('_id','title','slug'))->toArray();
					$finder['associate_finder'] = $associate_finder;
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

				function cmp($a, $b)
	            {
	            	return $a['traction']['sales'] < $b['traction']['sales'];
	            }

	        	usort($category_slug_services, "cmp");
	        	usort($non_category_slug_services, "cmp");
	        	
	        	$finderarr['services'] = array_merge($category_slug_services, $non_category_slug_services);


				
				array_set($finder, 'services', pluck( $finderarr['services'] , ['_id', 'name', 'lat', 'lon', 'serviceratecard', 'session_type', 'workout_tags', 'calorie_burn', 'workout_results', 'short_description','service_trainer','timing','category','subcategory','batches','vip_trial','meal_type','trial','membership', 'offer_available', 'showOnFront']  ));
				array_set($finder, 'categorytags', pluck( $finderarr['categorytags'] , array('_id', 'name', 'slug', 'offering_header') ));
				array_set($finder, 'findercollections', pluck( $finderarr['findercollections'] , array('_id', 'name', 'slug') ));
				array_set($finder, 'blogs', pluck( $finderarr['blogs'] , array('_id', 'title', 'slug', 'coverimage') ));
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

				if(count($finder['services']) > 0 ){

					$serviceArr                             =   [];
					$sericecategorysCalorieArr              =   Config::get('app.calorie_burn_categorywise');
					$sericecategorysWorkoutResultArr        =   Config::get('app.workout_results_categorywise');

					foreach ($finder['services'] as $service){

						if(!isset($service['showOnFront']) || (isset($service['showOnFront']) && $service['showOnFront'])){



								$service = $service;

							$service['offer_icon'] = "";

							if(isset($service['offer_available']) && $service['offer_available'] == true){

								$service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/mob_offer_ratecard.png";
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

									// if(isset($rateval['flags'])){

									// 	if(isset($rateval['flags']['discother']) && $rateval['flags']['discother'] == true){
									// 		unset($service['serviceratecard'][$ratekey]);
									// 		continue;
									// 	}

									// 	if(isset($rateval['flags']['disc25or50']) && $rateval['flags']['disc25or50'] == true){
									// 		unset($service['serviceratecard'][$ratekey]);
									// 		continue;
									// 	}
									// }
									if(isset($rateval['flags']) && ((isset($rateval['flags']['disc25or50']) && $rateval['flags']['disc25or50']) || (isset($rateval['flags']['discother']) && $rateval['flags']['discother']))){
										$finder['offer_icon'] = "https://b.fitn.in/iconsv1/womens-day/women-day-banner.svg";
										$finder['offer_icon_mob'] = "https://b.fitn.in/iconsv1/womens-day/exclusive.svg";
									}
									// else{
									// 	$finder['offer_icon'] = "https://b.fitn.in/iconsv1/womens-day/womens-day-mobile-banner.svg";
									// }
									if(!empty($rateval['_id']) && isset($rateval['_id'])){

										$ratecardoffersRecardsCount  =   Offer::where('ratecard_id', intval($rateval['_id']))->where('hidden', false)->orderBy('order', 'asc')
											->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
											->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
											->count();

										if($ratecardoffersRecardsCount > 0){  

											$service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/offer_available_vendor.png";
										}
									}
								}
							}


							array_push($serviceArr, $service);
						}
					}

					array_set($finder, 'services', $serviceArr);

					$info_timing = $this->getInfoTiming($finder['services']);

					if(isset($finder['info']) && $info_timing != ""){
						$finder['info']['timing'] = $info_timing;
					}

					
					
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

				$fitmania_offer_cnt     =   Serviceoffer::where('finder_id', '=', intval($finderarr['_id']))->where("active" , "=" , 1)->whereIn("type" ,["fitmania-dod", "fitmania-dow","fitmania-membership-giveaways"])->count();
				if($fitmania_offer_cnt > 0){
					array_set($finder, 'fitmania_offer_exist', true);
				}else{
					array_set($finder, 'fitmania_offer_exist', false);
				}

				if(isset($finderarr['brand_id'])){

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





				$finderdata         =   $finder;
				$finderid           = (int) $finderdata['_id'];
				$finder_cityid      = (int) $finderdata['city_id'];
				$findercategoryid   = (int) $finderdata['category_id'];
				$finderlocationid   = (int) $finderdata['location_id'];

				$skip_categoryid_finders    = [41,42,45,25,46,10,26,40];

				$nearby_same_category = array();

				$nearby_same_category       =   Finder::where('category_id','=',$findercategoryid)
				->where('location_id','=',$finderlocationid)
				->where('_id','!=',$finderid)
				->where('status', '=', '1')
				->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				// ->with('offerings')
				->orderBy('finder_type', 'DESC')
				->remember(Config::get('app.cachetime'))
				->take(5)
				->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','finder_coverimage','offerings'));

				/*if(count($nearby_same_category) > 0){

					$nearby_same_category->toArray();

					foreach($nearby_same_category as $key => $finder1){
						array_set($nearby_same_category[$key], 'offerings', pluck( $finder1['offerings'] , array('_id', 'name', 'slug') ));
					}
				}*/

				$nearby_other_category = array();    

				$nearby_other_category      =   Finder::where('category_id','!=',$findercategoryid)
				->whereNotIn('category_id', $skip_categoryid_finders)
				->where('location_id','=',$finderlocationid)
				->where('_id','!=',$finderid)
				->where('status', '=', '1')
				->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				// ->with('offerings')
				->orderBy('finder_type', 'DESC')
				->remember(Config::get('app.cachetime'))
				->take(5)
				->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','finder_coverimage','offerings'));

				/*if(count($nearby_other_category) > 0){

					$nearby_other_category->toArray();

					foreach($nearby_other_category as $key => $finder1){
						array_set($nearby_other_category[$key], 'offerings', pluck( $finder1['offerings'] , array('_id', 'name', 'slug') ));
					}
				}*/

				if($finder['city_id'] == 10000){
					$finder['city']['name'] = $finder['custom_city'];
					$finder['location']['name'] = $finder['custom_location'];
					$nearby_same_category = [];
					$nearby_other_category = [];
				}

				$finder['title'] = str_replace('crossfit', 'CrossFit', $finder['title']);
				$response['statusfinder']                   =       200;
				$response['finder']                         =       $finder;
				$response['defination']                     =       ['categorytags' => $categoryTagDefinationArr];
				$response['nearby_same_category']           =       $nearby_same_category;
				$response['nearby_other_category']          =       $nearby_other_category;
				$response['show_reward_banner'] = true;

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
		if($response['finder']['offer_icon'] == ""){
			$response['finder']['offer_icon']        =        "https://b.fitn.in/iconsv1/womens-day/womens-day-mobile-banner.svg";
		}
		if($response['finder']['offer_icon_mob'] == "" && (int)$response['finder']['commercial_type'] != 0){
			$response['finder']['offer_icon_mob']        =        "https://a.fitn.in/fitimages/fitmania/offer_available_sale.svg";
		}

		// if(time() >= strtotime(date('2016-12-24 00:00:00')) && (int)$response['finder']['commercial_type'] != 0){

		// 	$response['finder']['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/offer_available_search.png";
		// }
		
		return Response::json($response);

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
			'rating' => floatval($data['rating']),
			'detail_rating' => array_map('floatval',$data['detail_rating']),
			'description' => $data['description'],
			'uploads' => (isset($data['uploads'])) ? $data['uploads'] : [],
			'booktrial_id' => (isset($data['booktrialid'])) ? intval($data['booktrialid']) : '',
			'source' => (isset($data['source'])) ? $data['source'] : 'customer',
			'status' => '1'
		];

		(isset($_GET['device_type']) && $_GET['device_type'] != "") ? $reviewdata['source'] = strtolower($_GET['device_type']) : null ;


		$reviewdata['booktrial_id'] = ($reviewdata['booktrial_id'] == "" && isset($data['booktrial_id']) && $data['booktrial_id'] != "") ? intval($data['booktrial_id']) : '';

		if(isset($data['agent_name'])){
			$reviewdata['agent_name'] = $data['agent_name'];
		}

		if(isset($data['agent_email'])){
			$reviewdata['agent_email'] = $data['agent_email'];
		}

		$finder = Finder::find(intval($data['finder_id']));

		$review = Review::where('finder_id', intval($data['finder_id']))->where('customer_id', intval($data['customer_id']))->first();

		if($review){

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

		$review_detail = $this->updateFinderRatingV1($reviewdata);$review_detail['reviews'] = Review::active()->where('finder_id',intval($data['finder_id']))->orderBy('_id', 'DESC')->limit(5)->get();

		$response = array('status' => 200, 'message' => $message,'id'=>$review_id,'review_detail'=>$review_detail);

		if(isset($data['booktrialid']) &&  $data['booktrialid'] != '' && isset($review_id) &&  $review_id != ''){
			$booktrial_id   =   (int) $data['booktrialid'];
			$trial          =   Booktrial::find($booktrial_id);
			$trial->update(['review_id'=> intval($review_id), 'has_reviewed' => '1']);
		}

		$this->cacheapi->flushTagKey('finder_detail',$finder->slug);
		$this->cacheapi->flushTagKey('review_by_finder_list',$finder->slug);

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
			/*$email_template = 'emails.review';
			$email_template_data = array( 'vendor'  =>  ucwords($finderslug) , 'review' => $data['description'] ,  'date'   =>  date("h:i:sa") );
			$email_message_data = array(
				'to' => Config::get('mail.to_neha'),
				'reciver_name' => 'Fitternity',
				'bcc_emailids' => Config::get('mail.bcc_emailds_review'),
				'email_subject' => 'Review given for - ' .ucwords($finderslug)
				);
			$email = Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
				// $message->to($email_message_data['to'], $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
				$message->to('sanjay.id7@gmail.com', $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
			});*/

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

		$reviews            =   Review::with(array('finder'=>function($query){$query->select('_id','title','slug','coverimage');}))->active()->where('finder_id','=',$finder_id)->take($size)->skip($from)->orderBy('_id', 'desc')->get();

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
			}
		}

		$info_timing = "";

		if(count($service_batch) > 0){

			foreach ($service_batch as $ser => $btch){

				$info_timing .= "<p><strong>".$ser."</strong></p>";
				foreach ($btch as $btch_value){

					foreach ($btch_value as $key => $value) {
						$info_timing .= "<p><i>".$this->matchAndReturn($value)." : </i>". $key ."</p>";
					}

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


	public function getTrialSchedule($finder_id,$category){

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

		if(isset($_GET['device_type']) && $_GET['device_type'] == 'android'){

			$items = Service::active()->where('finder_id', $finder_id)->get(array('_id','name','finder_id', 'serviceratecard','trialschedules','servicecategory_id','batches','short_description','photos','trial','membership', 'traction', 'location_id'))->toArray();

		}else{

			$membership_services = Ratecard::where('finder_id', $finder_id)->orWhere('type','membership')->orWhere('type','packages')->lists('service_id');
			$membership_services = array_map('intval',$membership_services);

			$items = Service::active()->whereIn('_id',$membership_services)->where('finder_id', $finder_id)->get(array('_id','name','finder_id', 'serviceratecard','trialschedules','servicecategory_id','batches','short_description','photos','trial','membership', 'traction', 'location_id'))->toArray();

		}

		if(!$items){
			return array();
		}

		$scheduleservices = array();

		foreach ($items as $k => $item) {

			if(!isset($item['showOnFront']) || (isset($item['showOnFront']) && $item['showOnFront'])){
				$extra_info = array();

			/*$extra_info[0] = array(
				'title'=>'Description',
				'icon'=>'https://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
				'description'=> (isset($item['short_description']) && count($item['short_description']) > 0) ? strip_tags($item['short_description']) : ""
			);*/

			unset($items[$k]['short_description']);

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
				'icon'=>'https://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
				'description'=>$category_calorie_burn.' Kcal'
			);

			$extra_info[1] = array(
				'title'=>'Results',
				'icon'=>'https://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
				'description'=>'Burn Fat | Super Cardio'
			);

			if($category->_id == 42 || $category->_id == 45){

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
				'traction' => $item['traction'],
				'location_id' => $item['location_id']
			);

			if(isset($item['offer_available']) && $item['offer_available'] == true){

				$service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/women_offer_ratecard.png";
			}


			if(count($item['serviceratecard']) > 0){

				$ratecardArr = [];

				foreach ($item['serviceratecard'] as $ratekey => $rateval){

					


					//for ratecards offers
					$ratecardoffers     =   [];



					if(!empty($rateval['_id']) && isset($rateval['_id'])){
						$ratecardoffersRecards  =   Offer::where('ratecard_id', intval($rateval['_id']))->where('hidden', false)->orderBy('order', 'asc')
							->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
							->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
							->get(['start_date','end_date','price','type','allowed_qty','remarks'])
							->toArray();


						if(count($ratecardoffersRecards) > 0){ 

							$service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/mob_offer_ratecard.png";
							//$offer_icon_vendor = "https://b.fitn.in/iconsv1/fitmania/offer_available_search.png";
							
							foreach ($ratecardoffersRecards as $ratecardoffersRecard){
								$ratecardoffer                  =   $ratecardoffersRecard;
								$ratecardoffer['offer_text']    =   "";
								$ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/fitmania/hot_offer_vendor.png";

								if(isset($rateval['flags'])){

									if(isset($rateval['flags']['discother']) && $rateval['flags']['discother'] == true){
										$ratecardoffer['offer_text']    =   "";
										$ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/womens-day/women-only.png";
									}

									if(isset($rateval['flags']['disc25or50']) && $rateval['flags']['disc25or50'] == true){
										$ratecardoffer['offer_text']    =   "";
										$ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/womens-day/women-only.png";
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

					$rateval['offers']  = $ratecardoffers;

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
							if($rateval['special_price'] > 0){
								$app_discount_amount = intval($rateval['special_price'] * ($this->appOfferDiscount/100));
								$rateval['special_price'] = $rateval['special_price'] - $app_discount_amount;
							}else{
								$app_discount_amount = intval($rateval['price'] * ($this->appOfferDiscount/100));
								$rateval['price'] = $rateval['price'] - $app_discount_amount;
							}
							array_push($ratecardArr, $rateval);
						}
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
		$cache_key = $tslug;

		$category_slug = null;
		if(isset($_GET['category_slug']) && $_GET['category_slug'] != ''){
			Log::info("Category exists");
			$category_slug = $_GET['category_slug'];
			$cache_key  = $tslug.'-'.$category_slug;
		}

		$location_id = null;
		if(isset($_GET['location_id']) && $_GET['location_id'] != ''){
			Log::info("location exists");
			$location_id = $_GET['location_id'];
			$cache_key  = $cache_key.'-'.$location_id;
		}

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


		$finder_detail = $cache ? Cache::tags($cache_name)->has($cache_key) : false;

		if(!$finder_detail){
			Log::info("Not Cached in app");

			$finderarr = Finder::active()->where('slug','=',$tslug)
				->with(array('category'=>function($query){$query->select('_id','name','slug','detail_rating');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with('categorytags')
				->with('locationtags')
				->with('offerings')
				->with('facilities')
				->with(array('ozonetelno'=>function($query){$query->select('*')->where('status','=','1');}))
				->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
				->with(array('reviews'=>function($query){$query->select('_id','finder_id','customer_id','rating','description','updated_at')->where('status','=','1')->with(array('customer'=>function($query){$query->select('_id','name','picture')->where('status','=','1');}))->orderBy('_id', 'DESC')->limit(1);}))
				->first(array('_id','slug','title','lat','lon','category_id','category','location_id','location','city_id','city','categorytags','locationtags','offerings','facilities','coverimage','finder_coverimage','contact','average_rating','photos','info','manual_trial_enable','manual_trial_auto','trial','commercial_type','multiaddress'));


			$finder = false;

			if($finderarr){
				$finderarr = $finderarr->toArray();

				$finder         =   array_except($finderarr, array('info','finder_coverimage','location_id','category_id','city_id','coverimage','findercollections','categorytags','locationtags','offerings','facilities','blogs'));
				$coverimage     =   ($finderarr['finder_coverimage'] != '') ? $finderarr['finder_coverimage'] : 'default/'.$finderarr['category_id'].'-'.rand(1, 19).'.jpg';
				array_set($finder, 'coverimage', $coverimage);

				$finder['info']              =   array_only($finderarr['info'], ['timing','delivery_timing','service']);

				$finder['today_opening_hour']           =   null;
				$finder['today_closing_hour']           =   null;
				$finder['open_now']                     =   false;
				$finder['open_close_hour_for_week']     =   [];

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

				

				array_set($finder, 'services', pluck( $finderarr['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category','subcategory','batches','vip_trial','meal_type','trial','membership']  ));
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

				$finder['assured']  =   array();
				$not_assured        =   [41,42,45,25,46,10,26,40];


				if(!in_array($finderarr['category_id'], $not_assured) && $finderarr['commercial_type'] != 0 ){

					$finder['assured'] = [
						["icon" => "https://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png", "name" =>"Real-Time Booking"],
						["icon" => "https://b.fitn.in/iconsv1/fitternity-assured/service-fullfillment.png", "name" =>"100% Service Fulfillment"],
						["icon" => "https://b.fitn.in/iconsv1/fitternity-assured/lowest-price.png", "name" =>"Lowest Price"]
					];
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
				if(isset($finderarr['multiaddress']) && count($finderarr['multiaddress']) > 0){
					$finder['multiaddress'] = $finderarr['multiaddress'];
				}else{
					$finder['multiaddress'] = array();
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

					if(time() >= strtotime(date('2016-12-24 00:00:00')) && (int)$finder['commercial_type'] != 0){

						$data['finder']['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/offer_avail_red.png";
					}
					
					
					$category_id = Servicecategory::where('slug', $category_slug)->where('parent_id', 0)->first(['_id']);
					;
					function cmp($a, $b)
		            {
		            	return $a['traction']['sales'] < $b['traction']['sales'];
		            }

		        	usort($data['finder']['services'], "cmp");

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

					/*if(isset($data['finder']['services']['offer_icon_vendor'])){

						$data['finder']['offer_icon'] = $data['finder']['services']['offer_icon_vendor'];

						unset($data['finder']['services']['offer_icon_vendor']);
					}*/

					$category_id                                =   intval($finder['category']['_id']);
					$commercial_type                            =   intval($finder['commercial_type']);
					$bookTrialArr                               =   [5,6,12,42,43,32,36,7,35,13,10,11,47,14,25,9];



					if(in_array($category_id, $bookTrialArr)){
						$data['call_for_action_button']      =      "Book a Trial";

						if(in_array( 27 , $finder['facilities'])){
							$data['call_for_action_button']      =      "Book a Free Trial";
						}

						if($category_id == 42 ){
							$data['call_for_action_button']      =      "Book a Meal";
						}
					}

					if($commercial_type == 0){
						$data['call_for_action_button']       =      "";
					}

					$data['finder']['pay_per_session']        =   true;
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

						$data['finder']['services_trial'] = $this->getTrialWorkoutRatecard($data['finder']['services'],$finder['type'],'trial');
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
					$customer_phone                     =       $decoded->customer->contact_no;
					$customer_id                        =       $decoded->customer->_id;

					$customer                           =       Customer::find((int)$customer_id);

					if($customer){
						$customer   = $customer->toArray();
					}

					if(isset($customer['bookmarks']) && is_array($customer['bookmarks']) && in_array($finder['_id'],$customer['bookmarks'])){
						$finderData['finder']['bookmark'] = true;
					}

					$customer_trials_with_vendors       =       Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->where('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
						->where('finder_id', '=', (int) $finder->_id)
						->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
						->get(array('id'));

					$finderData['trials_detials']              =      $customer_trials_with_vendors;
					$finderData['trials_booked_status']        =      (count($customer_trials_with_vendors) > 0) ? true : false;

				}
			
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
					foreach ($finderData['finder']['services'] as $key => $value) {

						if(isset($value["trial"]) && $value["trial"] == "disable"){
							$disable_button[] = "true";
						}else{
							$disable_button[] = "false";
						}

					}

					if(!in_array("false", $disable_button)){
						$finderData['call_for_action_button'] = "";
						$finderData['finder']['pay_per_session'] = false;
					}

				}

				unset($finderData['finder']['services_workout']);
				unset($finderData['finder']['services_trial']);
			}

			// $finderData['show_reward_banner'] = true;

		}else{

			$finderData['status'] = 404;
		}

		return Response::json($finderData,$finderData['status']);

	}




	public function getTrialWorkoutRatecard($finderservices,$findertype,$type){

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

					if($ratecard_price > 0){
						$ratecard['cashback_on_trial'] = "100% Cashback";
					}

					array_push($ratecardArr, $ratecard);
				}

				foreach ($finderservice['ratecard'] as $ratecard){
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
            Log::info($e);
            return null;
        }
        
        return $decodedToken;
    }


}