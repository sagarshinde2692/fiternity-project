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
use App\Services\PassService as PassService;

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

	public function __construct(FinderMailer $findermailer, Cacheapi $cacheapi, Utilities $utilities, PassService $passService) {

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
		$this->passService 						= $passService;

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

		// Log::info($_SERVER['REQUEST_URI']);        

		$thirdPartySector = Request::header('sector');
		$isThirdParty = (isset($thirdPartySector) && in_array($thirdPartySector, ['multiply', 'health']));


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

		if($isThirdParty){
			$cache_key = $cache_key.'-thirdp';
		}

		$customer_email = null;
		
		$jwt_token = Request::header('Authorization');
		Log::info('finderdetail token:: ', [$jwt_token]);
		if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null' && $jwt_token != 'undefined'){
			
			$decoded = decode_customer_token();
			
			if($decoded){
				$customer_email = $decoded->customer->email;
			}

		}

		// $cache_key = $this->updateCacheKey($cache_key);

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
			Service::$isThirdParty = $isThirdParty;
			Service::$setAppends=['active_weekdays','serviceratecard'];
			$brand_id = Finder::active()->where('slug', $tslug)->get(['brand_id'])->first();
			if(!empty($brand_id) && !empty($brand_id['brand_id'])){
				$brand_id = $brand_id['brand_id'];
			}
			else {
				$brand_id = null;
			}
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
				// ->with(array('ozonetelno'=>function($query){$query->select('*')->where('status','=','1');}))
				->with(array('knowlarityno'=>function($query){$query->select('*')->where('status',true)->orderBy('extension', 'asc');}))

				->with(array('services'=>function($query) use ($isThirdParty, $brand_id){
					if($isThirdParty){
						if(!empty($brand_id) && $brand_id==130){
							$query->where(function($q1){
								$q1->where('workoutsessionschedules.0','exists',true)
								->orWhere('trialschedules.0','exists',true);
							})->where('trial','auto');
							// ->whereIn('showOnFront',['web','kiosk'])
						}
						else {
							$query->where('workoutsessionschedules.0','exists',true)->where('trial','auto');
							// ->whereIn('showOnFront',['web','kiosk'])
						}
					}
					$query->where('status','=','1')->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug', 'description');}))->with(array('location'=>function($query){$query->select('_id','name');}))->orderBy('ordering', 'ASC');
				}))

				->with(array('reviews'=>function($query){$query->select('*')->where('status','=','1')->where('description', '!=', "")->orderBy('updated_at', 'DESC')->limit(5);}))
				// ->with(array('reviews'=>function($query){$query->select('*')->where('status','=','1')->orderBy('_id', 'DESC');}))
				->first();

                unset($finderarr['ratecards']);
			// return $finderarr;
			
			$finder = null;	
			
			if($finderarr){
				
				// $ratecards           =   Ratecard::with('serviceoffers')->where('finder_id', intval($finder_id))->orderBy('_id', 'desc')->get();
				$finderarr = $finderarr->toArray();
				
				// if(count($finderarr['reviews']) < 5){
				// 	$initial_review_count = count($finderarr['reviews']);
				// 	$reviews = Review::where('finder_id', $finderarr['_id'])->where('description', "")->orderBy('updated_at', 'DESC')->limit(5-$initial_review_count)->get();
				// 	if(count($reviews)){
				// 		$initial_reviews = $finderarr['reviews'];
				// 		$initial_reviews = array_merge($initial_reviews, $reviews->toArray());
				// 		$finderarr['reviews'] = $initial_reviews;
				// 	}
				// }			
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

				$finderarr['reviews_booktrial_index'] = $this->getReviewBooktrialIndex($finderarr['_id']);

				if(!empty($finderarr['reviews'])){

					foreach ($finderarr['reviews'] as $rev_key => $rev_value) {

						if($rev_value['customer'] == null){

							$finderarr['reviews'][$rev_key]['customer'] = array("id"=>0,"name"=>"A Fitternity User","picture"=>"https://www.gravatar.com/avatar/0573c7399ef3cf8e1c215cdd730f02ec?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png");
						}

						if((!empty($rev_value['description'])) && $rev_value['rating']==0) {
							$finderarr['reviews'][$rev_key]['rating'] = 5;
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
                                      //return $slot;
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

                                  //return $slots_start_time_24_hour_format_Arr;

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

                                      // return "$opening_hour  -- $closing_hour";
										//   $finder['opening_hour'] = min($slots_start_time_24_hour_format_Arr);
										//   $finder['closing_hour'] = max($slots_end_time_24_hour_format_Arr)
										//Log::info('opening and closing hours:', [$opening_hour, $closing_hour]);
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

                //return  $finder;

				// if(isset($finderarr['ozonetelno']) && $finderarr['ozonetelno'] != ''){
				// 	$finderarr['ozonetelno']['phone_number'] = '+'.$finderarr['ozonetelno']['phone_number'];
				// 	$finder['ozonetelno'] = $finderarr['ozonetelno'];
				// }
				if(isset($finderarr['knowlarityno']) && count($finderarr['knowlarityno'])){
					$finderarr['knowlarityno'] = $this->utilities->getContactOptions($finderarr);
					// $finderarr['knowlarityno']['phone_number'] = '+91'.$finderarr['knowlarityno']['phone_number'];
					// $finderarr['knowlarityno']['extension'] = strlen($finderarr['knowlarityno']['extension']) < 2 && $finderarr['knowlarityno']['extension'] >= 1  ?  "0".$finderarr['knowlarityno']['extension'] : $finderarr['knowlarityno']['extension'];
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

				// $finder['review_count']     =   isset($finderarr["total_rating_count"]) ? $finderarr["total_rating_count"] : 0;
				$finder['review_count']     =   Review::where('status','=','1')->where('description', '!=', "")->where('finder_id', $finder['_id'])->count();

				if(empty($finder['review_count'])) {
					$finder['review_count'] = 0;
				}

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
				

				
				
				array_set($finder, 'services', pluck( $finderarr['services'] , ['_id', 'name', 'lat', 'lon', 'serviceratecard', 'session_type', 'workout_tags', 'calorie_burn', 'workout_results', 'short_description','service_trainer','timing','category','subcategory','batches','vip_trial','meal_type','trial','membership', 'offer_available', 'showOnFront', 'traction', 'timings', 'flags','location_id','slug','location', 'inoperational_dates']  ));
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

				$pay_per_session_abandunt_catyegory             =   [41,42,45,25,46,10,26,40];

				if((isset($finder['trial']) && in_array($finder['trial'], ['disable', 'manual'])) || (!empty($finder['manual_trial_enable']) && $finder['manual_trial_enable'] == "1") || count($finder['services']) == 0 || $finder['commercial_type'] == 0 || in_array($finder['category_id'],$pay_per_session_abandunt_catyegory)){
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
				unset($finder['flags']['convinience_fee_applicable']);
				
				// start top selling and newly launched logic 
				
				
				if(!empty($finderarr['flags']) && !empty($finderarr['flags']['top_selling']))
					if($finderarr['flags']['top_selling'])		
						 $finder['flags']['top_selling']=true;
				    else unset($finder['flags']['top_selling']);
				    
				if(!empty($finderarr['flags']) && !empty($finderarr['flags']['newly_launched']) && !empty($finderarr['flags']['newly_launched_date'])){

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

				}else if(!empty($finderarr['flags'])){
					
					unset($finder['flags']['newly_launched_date']);
					unset($finder['flags']['newly_launched']);
				}
				
				// end top selling and newly launched logic 
				
				
				// return $info_timing;
				if(count($finder['services']) > 0 ){

					$serviceArr                             =   [];
					$sericecategorysCalorieArr              =   Config::get('app.calorie_burn_categorywise');
					$sericecategorysWorkoutResultArr        =   Config::get('app.workout_results_categorywise');

					foreach ($finder['services'] as $key => $service){

						// if(!isset($service['showOnFront']) || ((isset($service['showOnFront']) && $service['showOnFront']))){
						if((!isset($service['showOnFront']) || (isset($service['showOnFront']) && (in_array('web', $service['showOnFront']) || $isThirdParty))) && count($service['serviceratecard'])){ 



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
								$dupDurationDays = [];
								foreach ($service['serviceratecard'] as $ratekey => $rateval){

									$durationDays = $this->utilities->getDurationDay($rateval);
									if($rateval['type']!='extended validity') {
										if(empty($dupDurationDays[$durationDays])){
											$dupDurationDays[$durationDays] = [];	
										}
										array_push($dupDurationDays[$durationDays], $ratekey);
									}

									if((!empty($service['batches']) && count($service['batches'])>0 ) && !empty($rateval['studio_extended_validity']) && $rateval['studio_extended_validity']) {
										$service['studio_extended_validity'] = [
											'1_month' => ['count' => '15', 'unit' => 'days'],
											'greater_than_1_month' => ['count' => '30', 'unit' => 'days']
										];
									}

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
									

									$customerDiscount = 0;
									// $customerDiscount = $this->utilities->getCustomerDiscount();

									$final_price = 0;
							
									$discount = $customerDiscount;
									if($rateval['special_price'] > 0){
										$discount_amount = intval($rateval['special_price'] * ($discount/100));
										$final_price = $service['serviceratecard'][$ratekey]['special_price'] = $rateval['special_price'] - $discount_amount;
									}else{
										$discount_amount = intval($rateval['price'] * ($discount/100));
										$final_price = $service['serviceratecard'][$ratekey]['price'] = $rateval['price'] - $discount_amount;
									}
									if(in_array($rateval['type'], ['workout session']) && isset($rateval['peak_price'])){
										if(
                                            (!empty($service["trial"]) && in_array($service["trial"],["manual","diable", "manualauto"]) )
                                            || 
                                            (!empty($finder["trial"]) && in_array($finder["trial"],["manual","diable", "manualauto"])) 
                                            || 
                                            in_array($finder["category_id"],[47])){
											$service['serviceratecard'][$ratekey]['special_price'] = $rateval['peak_price'];
											// return $service['serviceratecard'][$ratekey];
										}
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

								$remarkImportantIndex = [];
								foreach ($dupDurationDays as $record) {
									if(count($record)>1) {
										$remarkImportantIndex = array_merge($remarkImportantIndex, $record);
									}
								}
								foreach ($remarkImportantIndex as $idx) {
									if(!empty($service['serviceratecard'][$idx])) {
										$service['serviceratecard'][$idx]['remarks_imp'] = true;
										$service['serviceratecard'][$idx]['remarks_imp_api'] = true;
									}
								}

							}

							if((isset($finderarr['membership']) && $finderarr['membership'] == 'disable') || isset($service['membership']) && $service['membership'] == 'disable'){
								$service['offer_available'] = false;
							}
							$service['pay_per_session'] = false;

							if(isset($finder['pay_per_session']) && $finder['pay_per_session'] && isset($service['trial']) && $service['trial'] != 'disable'){
								foreach($service['serviceratecard'] as &$ratecard){
									if($ratecard['type']=='workout session'){
										$this->addRemarkToraecardweb($ratecard, $service, $finder);
										$service['pay_per_session'] = true;
										$pay_per_session = true;
									
									}
								}
							}

							if($pay_per_session){
                                $finder['pay_per_session'] = true;
                                // $finder['trial'] = 'disable';                                
							}

							if(isset($service['category']) && $service['category'] && $service['category']['_id'] == 184){
								$service['remarks'] = "Personal Training is not inclusive of the Gym membership. To avail Personal Training, ensure to buy the Gym membership also.";
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

                $no_validity_ratecard_exists = false;
                $no_validity_service = false;

                

				if(isset($finder['pay_per_session']) && $finder['pay_per_session']){

					$cheapest_price = $this->getCheapestWorkoutSession($finder['services']);
					
					if($cheapest_price>0){

						$finder['pps_content'] = [
							'header1'=>	'PAY - PER - SESSION',
							'header2'=>	'Available here',
							'header3'=>	"Why pay for 30 days when you use for 6 days?\nPay Per Session at ".$finder['title']." by just paying Rs. ".$cheapest_price,
							'image'=>''
						];

					}else{

						$finder['pay_per_session'] = false;
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
				$finder['cult_enquiry'] = null;

				if(isset($finderarr['brand_id']) && $finderarr['brand_id'] == 134){
					$finder['cult_enquiry'] = true;
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

// 				if(!isset($finder['callout']) || trim($finder['callout']) == ''){
					
				
				$this->removeConvinienceFee($finder);

				unset($finder['callout']);
				unset($finder['callout_ratecard_id']);

				
				
// 				}
				// 	$callout_offer = Offer::where('vendor_id', $finder['_id'])->where('hidden', false)->orderBy('order', 'asc')
				// 					->where('offer_type', 'newyears')
				// 					->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
				// 					->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
				// 					->first();
				// 	Log::info($callout_offer);
				// 	if($callout_offer){

				// 		$device = $this->vendor_token ? 'kiosk' : 'web';
				// 		$callout_service = Service::active()->where('_id', $callout_offer['vendorservice_id'])->where(function($query) use ($device){return $query->orWhere('showOnFront', 'exists', false)->orWhere('showOnFront', $device);})->first();
				// 		$callout_ratecard = Ratecard::find($callout_offer['ratecard_id']);
				// 		Log::info($callout_ratecard);
				// 		if($callout_service && $callout_ratecard){
				// 			$finder['callout'] = $callout_service['name']." - ".$this->getServiceDuration($callout_ratecard)." @ Rs. ".$callout_offer['price'];
				// 		}
				// 	}
				// }

				$finderdata         =   $finder;
				$finderid           = (int) $finderdata['_id'];
				$finder_cityid      = (int) $finderdata['city_id'];
				$findercategoryid   = (int) $finderdata['category_id'];
				$finderlocationid   = (int) $finderdata['location_id'];

				$skip_categoryid_finders    = [41,42,45,25,46,10,26,40];

				$nearby_same_category_request = [
                    "offset" => 0,
                    "limit" => 2,
                    "radius" => "3km",
                    "category"=>newcategorymapping($finderdata["category"]["name"]),
                    "lat"=>$finderdata["lat"],
                    "lon"=>$finderdata["lon"],
                    "city"=>strtolower($finderdata["city"]["name"]),
                    "keys"=>[
                      "average_rating",
                      "total_rating_count",
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
                      "category",
					  "overlayimage",
					  "featured"
                    ],
                    "not"=>[
                    	"vendor"=>[(int)$finderdata["_id"]],
                    ],
                    "only_featured"=>true
                ];

				$nearby_other_category_request = [
                    "offset" => 0,
                    "limit" => 2,
                    "radius" => "3km",
                    "category"=>"",
                    "lat"=>$finderdata["lat"],
                    "lon"=>$finderdata["lon"],
                    "city"=>strtolower($finderdata["city"]["name"]),
                    "keys"=>[
                      "average_rating",
                      "total_rating_count",
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
                      "category",
					  "overlayimage",
					  "featured"
                    ],
                    "not"=>[
                    	"vendor"=>[(int)$finderdata["_id"]],
                    	// "category"=>[newcategorymapping($finderdata["category"]["name"])]
                    ],
                    "only_featured"=>true
				];
				
				if(!$this->utilities->isIntegratedVendor($finderdata)){
					$nearby_same_category['limit'] = $nearby_other_category['limit'] = 4;
					unset($nearby_same_category['only_featured']);
					unset($nearby_other_category['only_featured']);
				}else{
					Log::info("Integrated vendor");
				}
				

				$nearby_other_options_meta = [
                    "offset" => 0,
                    "limit" => 0,
                    "radius" => "2km",
                    "category"=>"",
                    "lat"=>$finderdata["lat"],
                    "lon"=>$finderdata["lon"],
                    "city"=>strtolower($finderdata["city"]["name"]),
                    "keys"=>[
                    ]
				];
				
				$nearby_same_category = geoLocationFinder($nearby_same_category_request);

				$nearby_other_category = geoLocationFinder($nearby_other_category_request);
				
				$nearby_other_options_meta = geoLocationFinderMeta($nearby_other_options_meta);

				$finder['nearby_options'] = "";
				if(in_array($finder['_id'], [576,1451,1460,1647,9883,2522,401,1486,1488,1458,1487,1452,1878,4830,4827,4831,4829,13138,13135,13137,13136,11836,11828,11829,11838,13680,11830,11451])){
					$finder['nearby_options'] = isset($nearby_other_options_meta['total_records']) ? $nearby_other_options_meta['total_records'] : "";
				}
				
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

				
					
				
				if(isset($finder['videos']) && is_array($finder['videos'])){

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
				}
			
				if(isset($finder['playOverVideo'])&&$finder['playOverVideo']!=-1&&isset($finder['videos']) && is_array($finder['videos']))
				{
					try {
						$povInd=$finder['videos'][(int)$finder['playOverVideo']];
						Log::info(" povInd  :: ".print_r($povInd,true));
						array_splice($finder['videos'],(int)$finder['playOverVideo'], 1);
						$finder['playOverVideo']=$povInd;
					} catch (Exception $e) {
						$message = array(
								'type'    => get_class($e),
								'message' => $e->getMessage(),
								'file'    => $e->getFile(),
								'line'    => $e->getLine(),
						);
						Log::info(" playOverVideoError ".print_r($message,true));
					}
				}
				else unset($finder['playOverVideo']);
				

				// if(count($finder['video_service_tags'])>1 && $video_service_tags_others_count>0){
				// 	$finder['video_service_tags']['Others'] = $video_service_tags_others_count;
					
				// }
				
				$finder['title'] = str_replace('crossfit', 'CrossFit', $finder['title']);
				$response['statusfinder']                   =       200;
				$response['finder']                         =       $finder;
				$response['defination']                     =       ['categorytags' => $categoryTagDefinationArr];
				$response['nearby_same_category']           =       $nearby_same_category;
				$response['nearby_other_category']          =       $nearby_other_category;
				$response['show_reward_banner'] 			= 		true;
				$response['finder_footer']					= 		$finder_footer;
				$response['finder']['payment_options']		=		$this->getPaymentModes($payment_options_data);
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

				if(in_array($finder["_id"], Config::get('app.remove_patti_from_brands')) ){
					$response['vendor_stripe_data'] = "no-patti";
				}
				// return $response['finder']["brand_id"];
				// if(isset($response['finder']["brand_id"]) && in_array($response['finder']["brand_id"],[134,33])){
				
					if(isset($response['finder']['stripe_text'])){
						$response['vendor_stripe_data']	=	[
							'text'=> $response['finder']['stripe_text'],
							'text_color'=> '#ffffff',
							'background'=> '-webkit-linear-gradient(left, #1392b3 0%, #20b690 100%)',
							'background-color'=> ''
						];
					} else if(!empty($response['finder'])&&!empty($response['finder']['info'])&&!empty($response['finder']['info']['stripe'])&&!empty($response['finder']['info']['stripe']['text'])){
						$response['vendor_stripe_data']	=	[
							'text'=> (!empty($response['finder']['info']['stripe']['text']))?$response['finder']['info']['stripe']['text']:"",
							'background-color'=> (!empty($response['finder']['info']['stripe']['background_color']))?$response['finder']['info']['stripe']['background_color']:"",
							'text_color'=> (!empty($response['finder']['info']['stripe']['text_color']))?$response['finder']['info']['stripe']['text_color']:"",
							'background'=> (!empty($response['finder']['info']['stripe']['background_color']))?$response['finder']['info']['stripe']['background_color']:""
					];
					} else{
						$coupon = getDynamicCouponForTheFinder($finder);
						if($coupon["text"] != ""){
							$response['vendor_stripe_data']	=	[
								'text'=> $coupon["text"],
								'background-color'=> "",
								'text_color'=> ""
							];
							$response["code_applicable"] = $coupon["code"];
						}
					}
				// }
				unset($response['finder']['info']['stripe']);

				if(isset($finder['commercial_type']) && $finder['commercial_type'] == 0){

					unset($response['finder']['payment_options']);
				}

				if(isset($finder['flags']) && isset($finder['flags']['state']) && in_array($finder['flags']['state'],['closed', 'temporarily_shut'])){

					$response['finder']['membership'] = "disable";
					$response['finder']['trial'] = "disable";

					unset($response['finder']['payment_options']);
				}

				// $response['finder']  = $this->applyNonValidity($response, 'web');

                $this->applyFreeSP($response);

                // $this->insertWSNonValidtiy($response, 'web');

                // $response['finder'] = $this->applyTopService($response);

                // if(!empty($cheapest_price)){
                //     $this->insertWSRatecardTopService($response, $cheapest_price);
				// }

				// $this->addNonValidityLink($response);
                $this->applyNonValidityDuration($response);
				// if(!in_array($finder['_id'], Config::get('app.upgrade_session_finder_id'))){
				// 	$this->removeNonValidity($response, 'web');
				// }

                $this->removeEmptyServices($response, 'web');
                
                $this->removeUpgradeWhereNoHigherAvailable($response);
                
                $this->serviceRemoveFlexiIfExtendedPresent($response);
                try{
                    $this->orderRatecards($response);
                }catch(Exception $e){
                    Log::info("Error while sorting ratecard");
                }
                
                if(empty($response['vendor_stripe_data']['text']) ){
                    if(empty($finder['flags']['state']) || !in_array($finder['flags']['state'], ['closed', 'temporarily_shut'] )){
                    
                        if(!empty($response['finder']['flags']['monsoon_campaign_pps']) && empty($response['finder']['flags']['monsoon_flash_discount_disabled'])){
                            $response['vendor_stripe_data']	= [
								
								'text1'=> "Surprise Additional 30% off On Lowest Price Memberships. Limited Period Offer. TCA",
                                'text3'=>"",
                                'background-color'=> "",
                                'text_color'=> '$fff',
                                'background'=> '#49bfb3'
                            ];

                            $response['show_timer'] = true;
                        
                        }else if(!empty($response['finder']['flags']['monsoon_campaign_pps'])){
                            $response['vendor_stripe_data']	= [
                            
								'text1'=> "Surprise Additional 30% off On Lowest Price Memberships. Limited Period Offer. TCA",
                                'text3'=>"",
                                'background-color'=> "",
                                'text_color'=> '$fff',
                                'background'=> '#49bfb3'
                            ];

                            $response['show_timer'] = true;
                        
                        }else if(empty($response['finder']['flags']['monsoon_flash_discount_disabled'])){
                            $response['vendor_stripe_data']	= [
                            
								'text1'=> "Surprise Additional 30% off On Lowest Price Memberships. Limited Period Offer. TCA",
                                'text3'=>"",
                                'background-color'=> "",
                                'text_color'=> '$fff',
                                'background'=> '#49bfb3'
                            ];

                            $response['show_timer'] = true;
                        
                        }
                    }
					
                }else if(!empty($response['vendor_stripe_data']['text'])){
                    $response['vendor_stripe_data']['text1'] = $response['vendor_stripe_data']['text'];
                }

                if(empty($response['vendor_stripe_data']['text1'])){
                    $response['vendor_stripe_data'] = "no-patti";
                }

                $cashback_type_map = Config::get('app.cashback_type_map');

                $response['finder']['type'] = !empty($finder['flags']['reward_type']) ?  $finder['flags']['reward_type'] : 2;
                $response['finder']['sub_type'] = !empty($finder['flags']['cashback_type']) ?  $cashback_type_map[strval($finder['flags']['cashback_type'])] : null;

				// if($this->utilities->isIntegratedVendor($response['finder'])){
				// 	$response['finder']['finder_one_line'] = $this->getFinderOneLiner($data);
				// }

				
                /********** Flash Offer Section Start**********/


                // $callOutObj= $this->getCalloutOffer($response['finder']['services']);

				// if(!empty($callOutObj)){

				// 	if(!empty($callOutObj['callout'])){
				// 		$response['finder']['callout'] = $callOutObj['callout'];
				// 	}

				// 	if(!empty($callOutObj['ratecard_id'])){
				// 		$response['finder']['callout_ratecard_id'] = $callOutObj['ratecard_id'];
				// 	}

                //     if(!empty($callOutObj['non_validity_ratecard'])){
				// 		$response['finder']['non_validity_ratecard'] = $callOutObj['non_validity_ratecard'];
				// 	}	
                    
                //     if(!empty($callOutObj['callout_extended'])){
				// 		$response['finder']['callout_extended'] = $callOutObj['callout_extended'];
				// 	}	
                    
                //     $response['finder']['callout_header'] = "New Year Offer";


				// }

                /********** Flash Offer Section End**********/


				// $response['finder']['services'] = $this->addPPSStripe($response['finder'], 'finderdetail');

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
		
		if(Request::header('Authorization') && Request::header('Authorization') != 'undefined'){
			// $decoded                            =       decode_customer_token();
			$customer_email                     =       $decoded->customer->email;
			$customer_phone                     =       $decoded->customer->contact_no;
			$customer_id                     	=       (int)$decoded->customer->_id;
			$customer_trials_with_vendors       =       Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->where('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
			->where('finder_id', '=', (int) $response['finder']['_id'])
			->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
			->get(array('id'));
            $customer = Customer::where('email', $customer_email)->first();
            $response['register_loyalty'] = !empty($customer['loyalty']);
			$response['trials_detials']              =      $customer_trials_with_vendors;
			$response['trials_booked_status']        =      (count($customer_trials_with_vendors) > 0) ? true : false;
            $response['session_pack']                =      !empty($this->utilities->getAllExtendedValidityOrders(['customer_email'=>$customer_email]));
		}else{
            $response['register_loyalty'] = false;
			$response['trials_detials']              =      [];
			$response['trials_booked_status']        =      false;
		}


        $response['trial_button'] = false;

        if(empty(count($response['trials_detials'])) && ((!empty($response['finder']['brand_id']) && $response['finder']['brand_id'] == 130) || (in_array($response['finder']['_id'], Config::get('app.powerworld_finder_ids', []))))){
            $response['trial_button'] = true;
        }
        $response['pending_payment'] = $this->utilities->hasPendingPayments();
        $response['spin_wheel_array'] = array_column(getSpinArray(), 'label1');
		if(!$response['pending_payment']){
			unset($response['pending_payment']);	
		}
		// if($response['finder']['offer_icon'] == ""){
		// 	$response['finder']['offer_icon']        =        "https://b.fitn.in/iconsv1/womens-day/womens-day-mobile-banner.svg";
		// }
		// if($response['finder']['offer_icon_mob'] == "" && (int)$response['finder']['commercial_type'] != 0){
		// 	$response['finder']['offer_icon_mob']        =        "https://a.fitn.in/fitimages/fitmania/offer_available_sale.svg";
		// }
		$response['finder']['offer_icon']        =        "https://a.fitn.in/fitimages/vendor-app-download-badge1.svg";
		try{
			if(!empty($_GET['source'])){
				$this->updateFinderHit($response['finder']);
			}else{
				Log::info("Not increasing hits");
			}
		}catch(Exception $e){
			Log::info($e);
		}

		// if(time() >= strtotime(date('2016-12-24 00:00:00')) && (int)$response['finder']['commercial_type'] != 0){

		// 	$response['finder']['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/offer_available_search.png";
		// }

		// commented on 9th Augus - Akhil
		if(!empty($customer_id)){
			$this->addCreditPoints($response['finder']['services'], $customer_id);
		}

		$this->multifitGymWebsiteVendorUpdate($response);

		return Response::json($response);

	}


	public function getPaymentModes($data){

        $payment_modes = [];

        $payment_modes[] = array(
            'title' => 'Online Payment',
            'subtitle' => 'Buy with Debit Card or Credit card, NetBanking, UPI and mobile wallets',
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
                'subtitle' => 'Get Cash Picked up from your Preferred Location',
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

	public function getReviewBooktrialIndex($finder_id){

        $reviews_booktrial_index = null;

        $reviews = Review::active()->where('finder_id',(int)$finder_id)->orderBy('updated_at','desc')->get();

        if(!empty($reviews)){

            $reviews = $reviews->toArray();

            $reviews_booktrial_index_flag = false;
            $reviews_booktrial_index_count = 0;

            foreach ($reviews as $rev_key => $rev_value) {

                if($rev_value['rating'] >= 4){

                    $reviews_booktrial_index_flag = true;
                    $reviews_booktrial_index_count += 1;

                }else{

                    $reviews_booktrial_index_flag = false;
                    $reviews_booktrial_index_count = 0;
                }

                if($reviews_booktrial_index_count == 3 && $reviews_booktrial_index == null){
                    $reviews_booktrial_index = $rev_key+1;
                }
            }
        }

        return $reviews_booktrial_index;

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
			'finder_id' => 'required_without:service_id|integer|numeric',
			'service_id'=> 'required_without:finder_id|integer|numeric',
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

		if(!isset($data['detail_rating']) || array_sum($data['detail_rating']) == 0){
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
			$data = Input::all();
		}

		if(!$data){
			$data = Input::json()->all();
		}


		// return $images = Input::file('images') ;

		Log::info("addReview");
		Log::info($data);
		
		
		$jwt_token = Request::header('Authorization');

	    if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

	        $decoded = customerTokenDecode($jwt_token);
	        $data['customer_id'] = (int)$decoded->customer->_id;
	    }

		// return Input::json()->all();
		if(!empty($data['service_id'])){
			$validator = Validator::make($data, Review::$rulesService);
		}else{
			$validator = Validator::make($data, Review::$rules);
		}
		
		$rating = $data['rating'];
		
		if(!isset($data['detail_rating']) || array_sum($data['detail_rating']) == 0){
			$data['detail_rating'] = [$rating,$rating,$rating,$rating,$rating];
		}
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
		// return $data;
		if(isset($data['detail_rating']) && is_string($data['detail_rating'])){
			$data['detail_rating'] = json_decode($data['detail_rating']);
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
			'assisted_by' => (isset($data['assisted_by'])) ? $data['assisted_by'] : null,
			'tag' => (isset($data['tag'])) ? $data['tag'] : []
		];

		(isset($_GET['device_type']) && $_GET['device_type'] != "") ? $reviewdata['source'] = strtolower($_GET['device_type']) : null ;

		if(isset($data['booktrialid']) && $data['booktrialid'] != '' && (!isset($data['source']) || $data['source'] != 'admin')){
			$booktrial = Booktrial::find(intval($data['booktrialid']));
			$booktrial->post_trial_status = 'attended';
			$booktrial->update();
		}
		

		(isset($data['booktrial_id']) && $data['booktrial_id'] != "") ? $reviewdata['booktrial_id'] =  intval($data['booktrial_id']) : null;

		if(isset($data['agent_name'])){
			$reviewdata['agent_name'] = $data['agent_name'];
		}
		
		if(!empty($data['service_id'])){
			$reviewdata['service_id'] = $data['service_id'];
		}

		if(isset($data['agent_email'])){
			$reviewdata['agent_email'] = $data['agent_email'];
		}

		if(isset($data['booktrial_id']) && $data['booktrial_id'] != ""){
			$reviewdata['booktrial_id'] = (int)$data['booktrial_id'];
		}

		$finder = Finder::find(intval($data['finder_id']));
		$category = Findercategory::find(intval($finder['category_id']));

		$review = Review::where('finder_id', intval($data['finder_id']))->where('customer_id', intval($data['customer_id']))->first();

		if(isset($data['order_id']) && $data['order_id'] != ""){
			$order = Order::find((int) $data['order_id']);

			if($order){
				$order->update(["review_added"=>true]);
			}
		}

		$images = Input::file('images') ;
		$images_urls = [];
		
		if($images){

			foreach($images as $key => $value){
				Log::info("Asdsad");
				// return get_class($value);
				if($value->getError()){
					
					return Response::json(['status'=>400, 'message'=>'Please upload jpg/jpeg/png image formats with max. size of 4 MB']);
				
				}

				$file_name = "review-".$data['customer_id']."-".time()."-$key";
				
				$path_compresed = $this->utilities->compressImage( $value, $file_name);

				$watermark = imagecreatefrompng('images/watermark.png');
				
				$im = imagecreatefromjpeg($path_compresed);
				
				imagecopy($im, $watermark,(imagesx($im)-imagesx($watermark))/2, (imagesy($im)-imagesy($watermark))/2,0, 0, imagesx($watermark), imagesy($watermark));

				$compressed_path_parts = pathinfo($path_compresed);

				$path_watermarked = $compressed_path_parts['dirname'].'/'.$compressed_path_parts['filename'].'-w.'.$compressed_path_parts['extension'];
				
				imagejpeg($im, $path_watermarked);
				
				imagedestroy($im);	
				
				$compressed_s3_path  = $finder['_id']."/".$compressed_path_parts['filename'].".".$compressed_path_parts['extension'];
				
				$watermarked_s3_path  = $finder['_id']."/".$compressed_path_parts['filename']."-w.".$compressed_path_parts['extension'];
				
				$this->utilities->uploadFileToS3( $path_compresed, Config::get('app.aws.review_images.path').$compressed_s3_path);
				$this->utilities->uploadFileToS3( $path_watermarked,Config::get('app.aws.review_images.path').$watermarked_s3_path);
				unlink($path_compresed);
				unlink($path_watermarked);

				array_push($images_urls, Config::get('app.aws.review_images.url').$watermarked_s3_path);
			}
			
			$reviewdata['images'] = $images_urls;
		
		}

		if(!empty($data['tag'])){
			$txn = null;
			switch($data['tag']){
				case 'trial':
				case 'Trial':
					$data['tag'] = 'trial_verified';
					$txn = Booktrial::where('customer_id', $reviewdata['customer_id'])->where('finder_id', $reviewdata['finder_id'])->where('type', 'booktrials')->first();
				break;
				case 'workout-session':
				case 'Workout-session':
					$data['tag'] = 'workout-session';
					$txn = Booktrial::where('customer_id', $reviewdata['customer_id'])->where('finder_id', $reviewdata['finder_id'])->where('type', 'workout-session')->first();
				break;
				case 'membership':
				case 'Membership':
					$data['tag'] = 'membership';
					$txn = Booktrial::where('customer_id', $reviewdata['customer_id'])->where('finder_id', $reviewdata['finder_id'])->whereNotIn('type', ['workout-session', 'booktrial'])->first();
				break;
			}

			if($txn){
				$reviewdata['tag'] = [$data['tag'].'_verified'];
			}
		}else{

			$prev_order = Transaction::where('customer_id', $reviewdata['customer_id'])->where('finder_id', $reviewdata['finder_id'])->where(function($query){$query->orWhere('transaction_type', '!=', 'Order')->orWhere('status', '1');})->orderBy('_id', 'desc')->first();

			if($prev_order && !empty($prev_order['type'])){
				$type = $prev_order ;
				switch($type){
					case 'booktrial':
					$reviewdata['tag'] = 'trial_verified';
					break;
					case 'worktout-session':
					$reviewdata['tag'] = 'workout_sesison_verified';

					break;
					default:
					$reviewdata['tag'] = 'membership_verified';
				}
			}
		}
		
		
		$fresh_review = true;

		if($review){

			$fresh_review = false;

			$review->update($reviewdata);
			$message = 'Review has been updated successfully';
			$review_id = $review->_id;

		}else{

			$inserted_id = Review::max('_id') + 1;
			$review = new Review($reviewdata);
			$review_id = $review->_id = $inserted_id;
			$review->save();

			$message = 'Thank You. Your review has been posted successfully';
		}

		// if(!empty($reviewdata['booktrial_id'])){
		// 	Booktrial::where('_id', intval($reviewdata['booktrial_id']))->update(['post_tril_review'=>true]);
		// }

		Queue::connection('redis')->push('FindersController@asyncUpdateFinderRating', array('finder'=>$finder, 'reviewdata'=>$reviewdata),Config::get('app.queue'));
		// $this->updateFinderRatingV2($finder);

		// $review_detail = $this->updateFinderRatingV1($reviewdata);
		$review_detail = null;
		$deviceType = Request::header('Device-Type');
		if(empty($deviceType) || !in_array($deviceType, ['android','ios'])) {
			$review_detail    =  ['rating' => [
				'average_rating' => $finder->average_rating, 'total_rating_count' => $finder->total_rating_count, 'detail_rating_summary_average' => $finder->detail_rating_summary_average, 'detail_rating_summary_count' => $finder->detail_rating_summary_count
			]];
			
			$review_detail['reviews'] = Review::active()->where('description', '!=', "")->where('finder_id',intval($data['finder_id']))->orderBy('_id', 'DESC')->limit(5)->get();
		}
		$response = array('status' => 200, 'message' => $message,'id'=>$review_id,'review_detail'=>$review_detail);

		if(isset($data['booktrialid']) &&  $data['booktrialid'] != '' && isset($review_id) &&  $review_id != ''){
			$booktrial_id   =   (int) $data['booktrialid'];
			$trial          =   Booktrial::find($booktrial_id);
			$trial->update(['review_id'=> intval($review_id), 'has_reviewed' => '1']);
		}

		$this->cacheapi->flushTagKey('finder_detail',$finder->slug);
		$this->cacheapi->flushTagKey('review_by_finder_list',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_android',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_android',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_android',$finder->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android_3_2',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_3_2',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_3_2',$finder->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android_3_2',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_ios',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_ios_3_2',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_3_2',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_3_2',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_ios_4_4_3',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_4_4_3',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_4_4_3',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android_4_4_3',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_4_4_3',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_4_4_3',$finder->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android_4_4_3',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android_5_1_8',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_5_1_8',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_5_1_8',$finder->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android_5_1_8',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android_5_1_9',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_5_1_9',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_android_5_1_9',$finder->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_android_5_1_9',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_ios_5_1_5',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_5_1_5',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_5_1_5',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		$this->cacheapi->flushTagKey('finder_detail_ios_5_1_6',$finder->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_5_1_6',$finder->slug.'-'.$category->slug);
		$this->cacheapi->flushTagKey('finder_detail_ios_5_1_6',$finder->slug.'-'.$category->slug.'-'.$finder->location_id);
		
		if(!empty($reviewdata['service_id'])){
			$service = Service::find($reviewdata['service_id'], ['slug']);
			$this->cacheapi->flushTagKey('service_detail',$finder->slug.'-'.$service->slug);
			$this->cacheapi->flushTagKey('service_detail',$finder->slug.'-'.$service->slug.'-5');
		}

		
		if($this->vendor_token){
			
			$order_count = Order::active()->where('type','memberships')->where('finder_id',(int)$data["finder_id"])->where('customer_id',(int)$data["customer_id"])->count();
	
			$booktrial_count = Booktrial::where('type','booktrials')->where('finder_id',(int)$data["finder_id"])->where('customer_id',(int)$data["customer_id"])->count();

			if($fresh_review && $booktrial_count > 0 && $order_count == 0){

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

		}

		return Response::json($response, 200);
	}

	public function updateFinderRatingV2($finder){
		$finder = Finder::find($finder['_id']);
		
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

	public function finderTopReview($slug, $limit = '', $cache=true){

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
		$reviews            =   Review::with(array('finder'=>function($query){$query->select('_id','title','slug','coverimage');}))->active()->where('finder_id','=',$finder_id)->where('description', '!=', '')->take($size)->skip($from)->orderBy('updated_at', 'desc')->get();

		// return $reviews;
		$customer_ids = array_column($reviews->toArray(), 'customer_id');
 		// return $customer_ids;

		$ongoing_membership_customer_ids = Order::active()->whereIn('customer_id', $customer_ids)->where('finder_id', $finder_id)->where('start_date', '<=', new DateTime())->where('end_date', '>=', new DateTime())->lists('customer_id');
		
		foreach($reviews as &$review){
			if(in_array($review['customer_id'], $ongoing_membership_customer_ids)){
				$review['tags'] = ['ongoing membership'];
			}else{
				$review['tags'] = [];
			}
		}
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
		Log::info("tabtabtabtabt");
		$device_id = Request::header('Device-Id');
		Log::info($device_id);
		Log::info(Request::header('Device-Serial'));
		Log::info($finder_id);
	    $getTrialSchedule = $this->getTrialSchedule($finder_id);

		$multifitFinder = $this->utilities->multifitFinder();

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

					if(empty($ratecard_value['flags'])){
						$ratecards[$ratecard_key]['flags'] = null;
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

		if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13){

			Finder::$withoutAppends=true;

			$finder = Finder::find((int)$finder_id);

			if(isset($finder['brand_id']) && $finder['brand_id'] == 66 && $finder['city_id'] == 3){

				unset($response['perks']);
			}

			if(in_array($finder_id, $multifitFinder)){
				unset($response['perks']);
			}
		}

		$this->kioskTabLastLoggedIn();

		return Response::json($response,200);
	}


	public function getTrialSchedule($finder_id,$category = false, $finder = false){

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
		if(!$finder){
			Service::$withoutAppends=true;
			Service::$setAppends=['active_weekdays','serviceratecard'];
			if(isset($_GET['device_type']) && $_GET['device_type'] == 'android' && empty(Request::header('Authorization-Vendor'))){

				$items = Service::active()->where('finder_id', $finder_id)->get(array('_id','name','finder_id', 'serviceratecard','trialschedules','servicecategory_id','batches','short_description','photos','trial','membership', 'traction', 'location_id', 'offer_available', 'ad', 'showOnFront','calorie_burn','workout_results'))->toArray();


			}else{

				if(!empty(Request::header('Authorization-Vendor'))){
					$membership_services = Ratecard::active()->where('finder_id', $finder_id)->whereIn('type',['membership', 'packages', 'extended validity'])->lists('service_id');
				}else{
					$membership_services = Ratecard::active()->where('finder_id', $finder_id)->orWhere('type','membership')->orWhere('type','packages')->lists('service_id');
				}
				$membership_services = array_map('intval',$membership_services);

				$items = Service::active()->whereIn('_id',$membership_services)->where('finder_id', $finder_id)->get(array('_id','name','finder_id', 'serviceratecard','trialschedules','servicecategory_id','batches','short_description','photos','trial','membership', 'traction', 'location_id','offer_available', 'showOnFront','calorie_burn','workout_results'))->toArray();


			}
		}else{
			$items = $finder["services"];

			$items = pluck($items, array('_id','name','finder_id', 'serviceratecard','trialschedules','servicecategory_id','batches','short_description','photos','trial','membership', 'traction', 'location_id','offer_available', 'showOnFront','calorie_burn', 'slug', 'location','non_validity','workout_results'));
			
		}

		if(!$items){
			return array();
		}
		
		$scheduleservices = array();
		$sericecategorysWorkoutResultArr        =   Config::get('app.workout_results_categorywise');

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


			if(isset($item['calorie_burn']) && $item['calorie_burn']['avg'] != 0){
				$category_calorie_burn = $item['calorie_burn']['avg'];
			}else{
				if(isset($sericecategorysCalorieArr[$service_category_id])){
					$category_calorie_burn = $sericecategorysCalorieArr[$service_category_id];
				}
			}
            if(isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && $_GET['app_version'] < '5.1.6'){

                $extra_info[0] = array(
                    'title'=>'Description',
                    'icon'=>'https://b.fitn.in/iconsv1/vendor-page/form.png',
                    'description'=> (isset($item['short_description']) && count($item['short_description']) > 0) ? strip_tags($item['short_description']) : ""
                );
                if(!empty($item['short_description'])){
                    unset($item['short_description']);
                }

                // $extra_info[0] = array(
                // 	'title'=>'Avg. Calorie Burn',
                // 	'icon'=>'https://b.fitn.in/iconsv1/vendor-page/calorie.png',
                // 	'description'=>$category_calorie_burn.' Kcal'
                // );

                // $extra_info[1] = array(
                // 	'title'=>'Results',
                // 	'icon'=>'http://b.fitn.in/iconsv1/vendor-page/description.png',
                // 	'description'=>'Burn Fat | Super Cardio'
                // );
			}
			$workoutResult = null;
			if(!empty($sericecategorysWorkoutResultArr[$service_category_id])){
				$workoutResult = $sericecategorysWorkoutResultArr[$service_category_id];
			}
			if(((isset($_GET['device_type']) && $_GET['device_type'] == 'android') || (isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && $_GET['app_version'] >= '5.1.6')) && !empty($item['short_description'])){

				$extra_info[] = array(
					'title'=>'Description',
					'icon'=>'https://b.fitn.in/iconsv1/vendor-page/form.png',
					'description'=> $item['short_description']
				);

				if(((isset($_GET['device_type']) && $_GET['device_type'] == 'android') || (isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && $_GET['app_version'] >= '5.1.6')) && (!empty($category_calorie_burn) && $category_calorie_burn>0)) {
					$extra_info[] = array(
						'title'=>'Avg. Calorie Burn',
						'icon'=>'https://b.fitn.in/iconsv1/vendor-page/calorie.png',
						'description'=>$category_calorie_burn.' Kcal'
					);
				}
				if(((isset($_GET['device_type']) && $_GET['device_type'] == 'android') || (isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && $_GET['app_version'] >= '5.1.6')) && (!empty($workoutResult))) {
					$extra_info[] = array(
						'title'=>'Results',
						'icon'=>'http://b.fitn.in/iconsv1/vendor-page/description.png',
						'description'=> implode(' | ', $workoutResult)
					);
				}
			}	
				

			if($category && ($category["_id"] == 42 || $category["_id"] == 45)){

				$extra_info = [];

				if(isset($item['short_description']) && $item['short_description'] != ""){
					$extra_info[] = array(
						'title'=>'Meal Contents',
						'icon'=>'https://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
						'description'=> str_replace("&nbsp;", "", strip_tags($item['short_description'])) 
					);
				}
			}

			if(isset($item['servicecategory_id']) && $item['servicecategory_id'] == 184){
				$extra_info[] = array(
					'title'=>'Note',
					'icon'=>'https://b.fitn.in/iconsv1/vendor-page/form.png',
					'description'=> "Personal Training is not inclusive of the Gym membership. To avail Personal Training, ensure to buy the Gym membership also.",
					'text_color'=>'#f8a81b'
				);
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
				'ratecard'=>isset($item['serviceratecard']) ? $item['serviceratecard'] : [],
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
				'slug'=>isset($item['slug']) ? $item['slug'] : "",
				'location'=>isset($item['location']) ? $item['location'] : null
				// 'showOnFront'=>(isset($item['showOnFront'])) ? $item['showOnFront'] : []
			);

			if(empty($service['extra_info']) || count($service['extra_info'])<1) {
				unset($service['extra_info']);
			}
			
			foreach($service['ratecard'] as $rateval){
				if((!empty($service['batches']) && count($service['batches'])>0 ) && !empty($rateval['studio_extended_validity']) && $rateval['studio_extended_validity']) {
					$service['studio_extended_validity'] = [
						'1_month' => ['count' => '15', 'unit' => 'days'],
						'greater_than_1_month' => ['count' => '30', 'unit' => 'days']
					];
				}
			}

			// if(isset($service['servicecategory_id']) && $service['servicecategory_id'] == 184){
			// 	$service['remarks'] = "Personal Training is not inclusive of the Gym membership. To avail Personal Training, ensure to buy the Gym membership also.";
			// }

			// if(isset($item['offer_available']) && $item['offer_available'] == true && !in_array($finder_id, Config::get('app.hot_offer_excluded_vendors'))){

			// 	$service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/women_offer_ratecard.png";
			// }
			
			if(!$finder){
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
							// $ratecardoffersRecards  =   Offer::where('ratecard_id', intval($rateval['_id']))->where('hidden', false)
							// 	->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
							// 	->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
							// 	->orderBy('order', 'asc')
							// 	->get(['start_date','end_date','price','type','allowed_qty','remarks'])
							// 	->toArray();

							$ratecardoffersRecards = Offer::getActiveV1('ratecard_id', intval($rateval['_id']), intval($rateval['finder_id']))->toArray();


							if(count($ratecardoffersRecards) > 0){ 

								// $service['offer_icon'] = "https://b.fitn.in/iconsv1/fitmania/mob_offer_ratecard.png";
								//$offer_icon_vendor = "https://b.fitn.in/iconsv1/fitmania/offer_available_search.png";
								
								foreach ($ratecardoffersRecards as $ratecardoffersRecard){
									$ratecardoffer                  =   $ratecardoffersRecard;
									$ratecardoffer['offer_text']    =   "";
									$ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/fitmania/hot_offer_vendor.png";
									$ratecardoffer['offer_color'] 	= 	"#5EBBBA";

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

					$ratecard_price = $rateval['price'];
					$cost_price = $rateval['price'];

					if(isset($rateval['special_price']) && $rateval['special_price'] != 0){
			            $ratecard_price = $rateval['special_price'];
			        }
					

					count($ratecardoffers)>0 ? $rateval['offers']  = $ratecardoffers: null;

					if(count($ratecardoffers) > 0 && isset($ratecardoffers[0]['price'])){

						/*if($ratecardoffers[0]['price'] == $rateval['price']){
							$rateval['price'] = $ratecardoffers[0]['price'];
						}else{
							$rateval['special_price'] = $ratecardoffers[0]['price'];
						}*/

						$offer_price = $ratecardoffers[0]['price'];

						$rateval['special_price'] = $ratecardoffers[0]['price'];

                    	($rateval['price'] == $ratecardoffers[0]['price']) ? $rateval['special_price'] = 0 : null;

						if(isset($ratecardoffers[0]['remarks']) && $ratecardoffers[0]['remarks'] != ""){
							$rateval['remarks'] = $ratecardoffers[0]['remarks'];
						}

						if($offer_price !== 0 && $offer_price < $cost_price && !in_array($rateval['type'], ['workout session', 'trial'])){

	                    	$offf_percentage = ceil((($cost_price - $offer_price) /$cost_price) *100);

	                    	$rateval['campaign_offer'] = "Get ".$offf_percentage."% off - Limited Slots";
							$rateval['campaign_color'] = "#43a047";
	                    }


					}
					
					// if($rateval['type'] == 'workout session'){
					// 	Log::info("workour session");
					// 	$rateval['remarks'] = (isset($rateval['remarks'])) ? $rateval['remarks']. "(100% Cashback)" : "(100% Cashback)";
					// }
					/*if($category->_id == 42){
						array_push($ratecardArr, $rateval);
					}else{*/
						if($rateval['type'] == 'membership' || $rateval['type'] == 'packages' || (!empty(Request::header('Authorization-Vendor')) && $rateval['type'] == 'extended validity' && in_array($rateval['finder_id'], Config::get('app.tab_session_pack_vendor_ids', [])))){
							
							$appOfferDiscount = in_array($finder_id, $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;

							$customerDiscount = 0;
							// $customerDiscount = $this->utilities->getCustomerDiscount();
							
							// Log::info("getCustomerDiscount");
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
							if($rateval_price>= 5000){

								$rateval['campaign_offer'] = !empty($rateval['campaign_offer']) ?  $rateval['campaign_offer']."(EMI available)" : "(EMI available)";
								$rateval['campaign_color'] = "#43a047";
							}
					        /*if($rateval_price >= 20000){

					        	$rateval['campaign_offer'] = "(EMI options available)";
					        	$rateval['campaign_color'] = "#43a047";
					        }*/
					        
							array_push($ratecardArr, $rateval);
						}
						// else{
						// 	array_push($ratecardArr, $rateval);
						// }
					//}
				}

				$service['ratecard'] = $ratecardArr;
				
			}
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

	public function getFinderOneLiner($data) {

        $line = null;
        if(empty($data['finder']['flags']['monsoon_flash_discount_disabled']) && !empty($data['finder']['flags']['monsoon_campaign_pps'])){

            
			if($this->device_type == 'android'){
				$line = "<u>Festive Fitness Fiesta</u><br><br>- Get Surprise Additional Discounts Upto 30% Off on Lowest Price Memberships & Session Packs. Use Magic Code : MODAK<br><br>- Get 100% Instant Cashback on Workout Sessions. Use Code : CB100 ";
            }else{	
				$line = "\nFestive Fitness Fiesta\n\n- Get Surprise Additional Discounts Upto 30% Off on Lowest Price Memberships & Session Packs. Use Magic Code : MODAK\n\n- Get 100% Instant Cashback on Workout Sessions. Use Code : CB100 ";
            }
            
        }else if(empty($data['finder']['flags']['monsoon_flash_discount_disabled'])){

            if($this->device_type == 'android'){
				$line = "<u>Festive Fitness Fiesta</u><br><br>- Get Surprise Additional Discounts Upto 30% Off on Lowest Price Memberships & Session Packs. Use Magic Code : MODAK<br><br>- Get 100% Instant Cashback on Workout Sessions. Use Code : CB100 ";
            }else{	
				$line = "\nFestive Fitness Fiesta\n\n- Get Surprise Additional Discounts Upto 30% Off on Lowest Price Memberships & Session Packs. Use Magic Code : MODAK\n\n- Get 100% Instant Cashback on Workout Sessions. Use Code : CB100 ";
            }
        
        }else if(!empty($data['finder']['flags']['monsoon_campaign_pps'])){

			if($this->device_type == 'android'){
				$line = "<u>Festive Fitness Fiesta</u><br><br>- Get Surprise Additional Discounts Upto 30% Off on Lowest Price Memberships & Session Packs. Use Magic Code : MODAK<br><br>- Get 100% Instant Cashback on Workout Sessions. Use Code : CB100 ";
            }else{	
				$line = "\nFestive Fitness Fiesta\n\n- Get Surprise Additional Discounts Upto 30% Off on Lowest Price Memberships & Session Packs. Use Magic Code : MODAK\n\n- Get 100% Instant Cashback on Workout Sessions. Use Code : CB100 ";
            }
			
		}
		
		foreach($data['finder']['services'] as &$service){
			foreach($service['ratecard'] as &$ratecard){
				if($ratecard['type'] == 'workout session' || $ratecard['type'] == 'trial'){
					$price = !empty($ratecard['special_price']) ? $ratecard['special_price'] : $ratecard['price'];
					$onepassHoldCustomer = $this->utilities->onepassHoldCustomer();
					if(!empty($onepassHoldCustomer) && $onepassHoldCustomer && $price < 1001){
						if($this->device_type == 'android'){
							$line = "<u>Festive Fitness Fiesta</u><br><br>- Get Surprise Additional Discounts Upto 30% Off on Lowest Price Memberships & Session Packs. Use Magic Code : MODAK";
						}else{	
							$line = "\nFestive Fitness Fiesta\n\n- Get Surprise Additional Discounts Upto 30% Off on Lowest Price Memberships & Session Packs. Use Magic Code : MODAK";
						}
						
						break;
					}
				}
			}
		}

        return $line;
		
		$brandMap = [
			135 => 'Buy a membership & get exclusive access to Fitsquad to Earn ₹20,000 worth of rewards',
			88 => 'Buy a membership & get exclusive access to Fitsquad to Earn ₹35,000 worth of rewards',
			166 => 'Buy a membership & get exclusive access to Fitsquad to Earn ₹35,000 worth of rewards'
		];

		// $brandsList = [135, 88, 166];
		$brandsList = [135, 166];

		if (!empty($data['finder']['brand_id']) && in_array($data['finder']['brand_id'], $brandsList) && !in_array($data['finder']['_id'], Config::get('app.brand_finder_without_loyalty'))) {
			if(!empty($brandMap[$data['finder']['brand_id']])){
				return $brandMap[$data['finder']['brand_id']];
			}
			return 'Buy a membership & get exclusive access to Fitsquad to Earn ₹25,000 worth of rewards';
		}
		else {
			if(empty($data['finder']['flags']['reward_type'])) {
				$data['finder']['flags']['reward_type'] = 2;
			}

			$rewardMap = [
				1 => [
					1 => null, 2 => null, 3 => null
				],
				2 => [
					1 => 'Buy a membership & get exclusive access to Fitsquad to Earn ₹25,000 worth of rewards',
					2 => 'Buy a membership & get exclusive access to Fitsquad to Earn ₹25,000 worth of rewards',
					3 => 'Buy a membership & get exclusive access to Fitsquad to Earn ₹25,000 worth of rewards'
				],
				3 => [
					1 => 'Buy a membership through Fitternity & get exclusive access to  instant rewards + 120% cashback',
					2 => 'Buy a membership through Fitternity & get exclusive access to  instant rewards + 120% cashback',
					3 => 'Buy a membership through Fitternity & get exclusive access to  instant rewards + 100% cashback'
				],
				4 => [
					1 => 'Buy a membership through Fitternity & get exclusive access to 120% cashback + exciting rewards worth ₹20,000',
					2 => 'Buy a membership through Fitternity & get exclusive access to 120% cashback + exciting rewards worth ₹20,000',
					3 => 'Buy a membership through Fitternity & get exclusive access to 100% cashback + exciting rewards worth ₹20,000'
				],
				5 => [
					1 => 'Buy a membership through Fitternity & get exclusive access to 120% cashback',
					2 => 'Buy a membership through Fitternity & get exclusive access to 120% cashback',
					3 => 'Buy a membership through Fitternity & get exclusive access to 100% cashback'
				],
				6 => [
					1 => 'Buy a membership through Fitternity & get exclusive access to 120% cashback + exciting rewards worth ₹20,000',
					2 => 'Buy a membership through Fitternity & get exclusive access to 120% cashback + exciting rewards worth ₹20,000',
					3 => 'Buy a membership through Fitternity & get exclusive access to 100% cashback + exciting rewards worth ₹20,000'
				]
			];
			$cbVal = (!empty($data['finder']['flags']['cashback_type']) && $data['finder']['flags']['cashback_type']<3)?($data['finder']['flags']['cashback_type']):3;
			return $rewardMap[$data['finder']['flags']['reward_type']][$cbVal];
		}



	}

	public function finderDetailApp($slug, $cache = true){

		Log::info($_SERVER['REQUEST_URI']);

		$data   =  array();	
		$tslug  = (string) strtolower($slug);


		if($tslug == "default" && isset($_GET['vendor_id']) && $_GET['vendor_id'] != ""){
			Finder::$withoutAppends=true;
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

		if(isset($_GET['device_type']) && $_GET['device_type'] == 'ios'){
			$cache_name = "finder_detail_ios";
		}

		if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios']) && isset($_GET['app_version']) && $_GET['app_version'] > '4.4.2'){
			$cache_name = "finder_detail_ios_4_4_3";
		}

		if(isset($_GET['device_type']) && in_array($_GET['device_type'],['android']) && isset($_GET['app_version']) && $_GET['app_version'] > '4.42'){
			$cache_name = "finder_detail_android_4_4_3";
		}

        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios']) && isset($_GET['app_version']) && $_GET['app_version'] > '5.1.4'){
			$cache_name = "finder_detail_ios_5_1_5";
		}

        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios']) && isset($_GET['app_version']) && $_GET['app_version'] > '5.1.5'){
			$cache_name = "finder_detail_ios_5_1_6";
		}
        
        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['android']) && isset($_GET['app_version']) && $_GET['app_version'] > '5.17'){
			$cache_name = "finder_detail_android_5_1_8";
		}
        
        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['android']) && isset($_GET['app_version']) && $_GET['app_version'] > '5.18'){
			$cache_name = "finder_detail_android_5_1_9";
		}
		Log::info($cache_name);
		$finder_detail = $cache ? Cache::tags($cache_name)->has($cache_key) : false;

		if(!$finder_detail){
			Log::info("Not Cached in app");
			Finder::$withoutAppends=true;
			Service::$withoutAppends=true;
			Service::$setAppends=['active_weekdays','serviceratecard'];
			Finder::$setAppends=['finder_coverimage'];
			$finderarr = Finder::active()->where('slug','=',$tslug)
				->with(array('category'=>function($query){$query->select('_id','name','slug','detail_rating','detail_ratings_images');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with('categorytags')
				->with('locationtags')
				->with('offerings')
				->with('facilities')
				// ->with(array('ozonetelno'=>function($query){$query->select('*')->where('status','=','1');}))
				->with(array('knowlarityno'=>function($query){$query->select('*')->where('status',true)->orderBy('extension', 'asc');}))

				->with(array('services'=>function($query){$query->select('*')->where('status','=','1')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->with(array('location'=>function($query){$query->select('_id','name');}))->orderBy('ordering', 'ASC');}))

				->with(array('reviews'=>function($query){$query->where('status','=','1')->where('description','!=', "")->select('_id','finder_id','customer_id','rating','description','updated_at','tag')->with(array('customer'=>function($query){$query->select('_id','name','picture')->where('status','=','1');}))->orderBy('updated_at', 'DESC')->limit(1);}))
                ->first(array('_id','slug','title','lat','lon','category_id','category','location_id','location','city_id','city','categorytags','locationtags','offerings','facilities','coverimage','finder_coverimage','contact','average_rating','photos','info','manual_trial_enable','manual_trial_auto','trial','commercial_type','multiaddress','membership','flags','custom_link','videos','total_rating_count','playOverVideo','pageviews','brand_id','custom_city','custom_location'));
                
            
			$finder = false;
			
			if($finderarr){
				$finderarr = $finderarr->toArray();

				// if(count($finderarr['reviews']) < 1){
				// 	$initial_review_count = count($finderarr['reviews']);
				// 	$reviews = Review::where('finder_id', $finderarr['_id'])->where('description', "")->orderBy('updated_at', 'DESC')->limit(1-$initial_review_count)->get();
				// 	if(count($reviews)){
				// 		$initial_reviews = $finderarr['reviews'];
				// 		$initial_reviews = array_merge($initial_reviews, $reviews->toArray());
				// 		$finderarr['reviews'] = $initial_reviews;
				// 	}
				// }

				if(isset($finderarr['trial']) && $finderarr['trial']=='manual'){
					$finderarr['manual_trial_enable'] = '1';
				}

				if(!empty($finderarr['reviews'])){

					foreach ($finderarr['reviews'] as $rev_key => $rev_value) {

						if($rev_value['customer'] == null){
							
							$finderarr['reviews'][$rev_key]['customer'] = array("id"=>0,"name"=>"A Fitternity User","picture"=>"https://www.gravatar.com/avatar/0573c7399ef3cf8e1c215cdd730f02ec?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png");
						}
						if(!empty($rev_value['description']) && $rev_value['rating']==0) {
							$finderarr['reviews'][$rev_key]['rating'] = 5;
						}
					}
				}

				
				

				$finder         =   array_except($finderarr, array('info','finder_coverimage','location_id','category_id','city_id','coverimage','findercollections','categorytags','locationtags','offerings','facilities','blogs'));
				
				if(isset($finder['playOverVideo'])&&$finder['playOverVideo']!=-1&&isset($finder['videos']) && is_array($finder['videos']))
				{
					try {
						$povInd=$finder['videos'][(int)$finder['playOverVideo']];
						if(!isset($povInd['url']) || trim($povInd['url']) == ""){
							$povInd=null;
						}
						//Log::info(" povInd  :: ".print_r($povInd,true));
						if(!empty($povInd))
						{
							array_splice($finder['videos'],(int)$finder['playOverVideo'], 1);
							$finder['playOverVideo']=$povInd;
						}
					} catch (Exception $e) {
						$message = array(
								'type'    => get_class($e),
								'message' => $e->getMessage(),
								'file'    => $e->getFile(),
								'line'    => $e->getLine(),
						);
						Log::info(" playOverVideoError ".print_r($message,true));
					}
				}
				else unset($finder['playOverVideo']);
				
				if(isset($finder['videos'])){
					foreach($finder['videos'] as $key => $video){
						if(!isset($video['url']) || trim($video['url']) == ""){
							unset($finder['videos'][$key]);
						}
					}
				}
				
				
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
									//Log::info('at before of formating:::',[$slots_start_time_24_hour_format_Arr]);
									if(!empty($slots_start_time_24_hour_format_Arr) && !empty($slots_end_time_24_hour_format_Arr)){
										$opening_hour_arr       = explode(".",min($slots_start_time_24_hour_format_Arr));
										$opening_hour_surfix    = "";
										if(isset($opening_hour_arr[1])){
											$opening_hour_surfix = (strlen($opening_hour_arr[1]) == 1) ? $opening_hour_arr[1]."0" : $opening_hour_arr[1];
										}
										else{
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
										//   $finder['opening_hour'] = min($slots_start_time_24_hour_format_Arr);
										//   $finder['closing_hour'] = max($slots_end_time_24_hour_format_Arr)
										if($today_weekday == $weekday){
											//Log::info('opening and closing toime::', [$opening_hour, $closing_hour]);
											$finder['today_opening_hour'] =  date("g:i A", strtotime(str_replace(".",":",$opening_hour)));
											$finder['today_closing_hour'] = date("g:i A", strtotime(str_replace(".",":",$closing_hour)));
										}
										//Log::info('opening and closing toime::', [$opening_hour,  date("g:i A", strtotime(str_replace(".",":","6:00"))),$closing_hour]);
										$whole_week_open_close_hour[$weekday]['opening_hour'] = date("g:i A", strtotime(str_replace(".",":",$opening_hour)));
										$whole_week_open_close_hour[$weekday]['closing_hour'] = date("g:i A", strtotime(str_replace(".",":",$closing_hour)));
										//Log::info('opening and closing toime::', [$whole_week_open_close_hour, $whole_week_open_close_hour]);
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
				$cult_Ids = array();
				// || in_array($finderarr['_id'], $cult_Ids)
				if((isset($finderarr['category_id']) && $finderarr['category_id'] == 41) ){
					$finder['trial'] = 'disable';
					$finder['membership'] = 'disable';
				}

				if(isset($finderarr['custom_link'])){
					$finder['trial'] = 'disable';
					$finder['membership'] = 'disable';
					$finder["custom_link"] = "";
				}

				if($finder['today_opening_hour'] != NULL && $finder['today_closing_hour'] != NULL){
					//Log::info('opening and closing time :', [$finder['today_opening_hour'], $finder['today_closing_hour']]);
					$status = false;
					$startTime = DateTime::createFromFormat('h:i A', $finder['today_opening_hour'])->format('Y-m-d H:i:s');
					$endTime   = DateTime::createFromFormat('h:i A', $finder['today_closing_hour'])->format('Y-m-d H:i:s');


					$chSplitColon = explode(':', $finder['today_closing_hour']);
					$chSplitSpace = explode(' ', $finder['today_closing_hour']);
					$isTwelve = $chSplitColon[0] == 12;
					$isAm = in_array($chSplitSpace[1], ['AM', 'am']);
					if($isTwelve && $isAm) {
						$endTime = date('Y-m-d H:i:s',strtotime('+1 days',strtotime($endTime)));
					}

					// if($finder['today_closing_hour'] == "12:00 AM"){
					// 	$endTime = date('Y-m-d H:i:s',strtotime('+1 days',strtotime($endTime)));
					// }

					if (time() >= strtotime($startTime) && time() <= strtotime($endTime)) {
						$status = true;
					}

					array_set($finder, 'open_now', $status);
				}

				// return $finderarr['services'];

				array_set($finder, 'services', pluck( $finderarr['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category', 'subcategory','batches','vip_trial','meal_type','trial','membership', 'timings','finder_id','servicecategory_id','traction','location_id', 'offer_available','calorie_burn', 'slug', 'location','showOnFront']  ));

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

					if(isset($finder['open_close_hour_for_week'])){
						$info_timing = $this->createTiming($finder['open_close_hour_for_week']).$info_timing;
					}


					if(isset($finder['info']) && $info_timing != ""){
						$finder['info']['timing'] = $info_timing;
					}
					// unset($finder['services']);
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
				$not_assured_brands = [130];

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
					if(isset($finder["brand_id"]) && in_array($finder["brand_id"], $not_assured_brands)){
						$finder['assured'] = null;
						$assured_flag = false;
					}
				}

				// $finder['review_count']     =   isset($finder["total_rating_count"]) ? $finder["total_rating_count"] : 0;

				$finder['review_count']     =   Review::where('status','=','1')->where('description', '!=', "")->where('finder_id', $finder['_id'])->count();

				if(empty($finder['review_count'])) {
					$finder['review_count'] = 0;
				}

				$finder['average_rating']   =   (isset($finder['average_rating']) && $finder['average_rating'] != "") ? round($finder['average_rating'],1) : 0;
				
				// if(isset($finderarr['ozonetelno']) && $finderarr['ozonetelno'] != '' && isset($finder['contact']['phone']) && $finder['contact']['phone'] != ""){

				// 	$extension = (isset($finder['ozonetelno']['extension']) && $finder['ozonetelno']['extension'] != "") ? ",".$finder['ozonetelno']['extension'] : "";
				// 	$finder['ozonetelno']['phone_number'] = '+'.$finder['ozonetelno']['phone_number'].$extension;
				// 	$finder['contact']['phone'] = $finder['ozonetelno']['phone_number'];
				// 	unset($finder['ozonetelno']);
				// 	unset($finder['contact']['website']);
				// }
				if(isset($finderarr['knowlarityno']) && count($finderarr['knowlarityno'])){
					$finder['knowlarityno'] = $finder['knowlarityno'][0];
					$extension = (isset($finder['knowlarityno']['extension']) && $finder['knowlarityno']['extension'] != "") ? ",,".(400+$finder['knowlarityno']['extension']) : "";
					$finder['knowlarityno']['phone_number'] = '+91'.$finder['knowlarityno']['phone_number'].$extension;
					$finder['contact']['phone'] = $finder['knowlarityno']['phone_number'];
					// unset($finder['knowlarityno']);
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

				// $finder = Finder::active()->where('slug','=',$tslug)->first();
				if($finder){
					$data['finder']['services']          =        $this->getTrialSchedule($finder["_id"],$finder["category"], $finder);
					$data['finder']['bookmark']          =        false;
					$data['trials_detials']              =        [];
					$data['trials_booked_status']        =        false;
					$data['call_for_action_button']      =        "";
					$data['call_for_action_text']      =        "";
					
					$data['finder']['offer_icon']        =        "";
					$data['finder']['multiaddress']	     =		  $finder["multiaddress"];

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


					// return $finder['facilities'];
					if(in_array($category_id, $bookTrialArr)){
						$data['call_for_action_button']      =      "Book a Trial";
						$data['call_for_action_text'] = 'Get me started with a personalised trial experience';

						if(in_array( 27 , $finder['facilities']) || in_array( "Free Trial" , $finder['facilities'])){
							$data['call_for_action_button']      =      "Book a Free Trial";
						}

						if($category_id == 42 ){
							$data['call_for_action_button']      =      "Book a Meal";
							$data['call_for_action_text'] = 'Get a select set of meals and experience the choice of cuisine available with this trial';
						}
					}

					if($commercial_type == 0 || in_array($finder['_id'], $cult_Ids)){
						$data['call_for_action_button']       =      "";
						$data['call_for_action_text']   =      "";
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
                    if((isset($_GET['device_type']) && in_array($_GET['device_type'], ['android']) && $_GET['app_version'] >= '5.18') || (isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && $_GET['app_version'] >= '5.1.5')){
						$data['finder']  = $this->applyNonValidity($data, 'app');
                        $this->insertWSNonValidtiy($data, 'app');
                    }
                    
                    // if((isset($_GET['device_type']) && in_array($_GET['device_type'], ['android'])) || (isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && $_GET['app_version'] >= '5.1.5')){
                    //     $data['finder'] = $this->applyTopService($data, 'app');

                    //     $cheapest_price = $this->getCheapestWorkoutSession($data['finder']['services'], 'app');
                    //     if(!empty($cheapest_price)){
                    //         $this->insertWSRatecardTopService($data, $cheapest_price, 'app');
                    //     }
                    // }

					$device_type = ['ios','android'];

					if(isset($_GET['device_type']) && in_array($_GET['device_type'], $device_type) && isset($_GET['app_version']) && (float)$_GET['app_version'] >= 3.2 && isset($data['finder']['services']) && count($data['finder']['services']) > 0){
						
						$data['finder']['services_trial'] = $this->getTrialWorkoutRatecard($data['finder']['services'],$finder['type'],'trial', $data['finder']['trial'],$data['finder']);
						$data['finder']['services_workout'] = $this->getTrialWorkoutRatecard($data['finder']['services'],$finder['type'],'workout session',null,$data['finder']);

                        
						
					}
					// return $data['finder']['flags'];
					if(!empty($data['finder']['flags']) && !empty($data['finder']['flags']['top_selling']))
					if($data['finder']['flags']['top_selling'])		
						 $data['finder']['overlayimage']='https://b.fitn.in/global/finder/best-seller.png';
				    
				    
					if(!empty($data['finder']['flags']) && !empty($data['finder']['flags']['newly_launched']) && !empty($data['finder']['flags']['newly_launched_date'])){

						if($data['finder']['flags']['newly_launched']&&$data['finder']['flags']['newly_launched_date']){

							$launchedTime=strtotime($data['finder']['flags']['newly_launched_date']);	
							$date1=date_create(date("Y/m/d"));
							$date2=date_create(date('Y/m/d',$data['finder']['flags']['newly_launched_date']->sec));
							$diff=date_diff($date1,$date2);
							//Log::info(" info diff ".print_r($diff,true));
							if($diff->invert>0)
							{
								if($diff->days<=30)
									$data['finder']['overlayimage']='https://b.fitn.in/global/finder/newly-launched.png';
							}
							else $data['finder']['overlayimage']='https://b.fitn.in/global/finder/opening-soon.png';
						}

					}

					if(!empty($data['finder']['flags']) && !empty($data['finder']['flags']['state']) && $data['finder']['flags']['state'] == 'temporarily_shut'){
						// $data['finder']['overlayimage'] = 'https://b.fitn.in/global/finder/temp-shut.png';
						$data['finder']['overlayimage'] = 'https://b.fitn.in/global/temporarily%20closed.png';
					}

                }

				$data['finder']['other_offers'] = null;


                /********** Flash Offer Section Start**********/

				// $getCalloutOffer = $this->getCalloutOffer($data['finder']['services'],'app');

				// if(!empty($getCalloutOffer['callout'])){

				// 	$data['finder']['other_offers'] = $getCalloutOffer;

				// 	$data['finder']['other_offers']['icon'] = "https://b.fitn.in/global/fitness-flash-sale-logo.png";
				// 	$data['finder']['other_offers']['description'] = $getCalloutOffer['callout'];
				// 	$data['finder']['other_offers']['header'] = "Flash Offer";
				// 	$data['finder']['other_offers']['features'] = [
				// 		'Lowest Price Guarantee',
				// 		'Limited slots',
				// 		'EMI option available'
				// 	];

				// 	unset($data['finder']['other_offers']['callout']);
				// }

                /********** Flash Offer Section Start**********/

                $nearby_same_category_request = [
                    "offset" => 0,
                    "limit" => 2,
                    "radius" => "3km",
                    "category"=>newcategorymapping($finderarr["category"]["name"]),
                    "lat"=>$finderarr["lat"],
                    "lon"=>$finderarr["lon"],
                    "city"=>strtolower($finderarr["city"]["name"]),
                    "keys"=>[
                      "average_rating",
                      "total_rating_count",
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
                      "category",
					  "overlayimage",
					  "featured"
                    ],
                    "not"=>[
                    	"vendor"=>[(int)$finderarr["_id"]],
                    ],
                    "only_featured"=>true
                ];

				$nearby_other_category_request = [
                    "offset" => 0,
                    "limit" => 2,
                    "radius" => "3km",
                    "category"=>"",
                    "lat"=>$finderarr["lat"],
                    "lon"=>$finderarr["lon"],
                    "city"=>strtolower($finderarr["city"]["name"]),
                    "keys"=>[
                      "average_rating",
                      "total_rating_count",
		              "contact",
		              "coverimage",
		              "location",
		              "multiaddress",
		              "slug",
		              "name",
		              "id",
		              "categorytags",
		              "category",
		              "overlayimage"
                    ],
                    "not"=>[
                    	"vendor"=>[(int)$finderarr["_id"]],
                    ],
                    "only_featured"=>true
                ];

				if(!$this->utilities->isIntegratedVendor($finderarr)){
					$nearby_same_category['limit'] = $nearby_other_category['limit'] = 4;
					unset($nearby_same_category['only_featured']);
					unset($nearby_other_category['only_featured']);
				}else{
					Log::info("Integrated vendor");
				}

				$nearby_same_category = geoLocationFinder($nearby_same_category_request);
                $nearby_other_category = geoLocationFinder($nearby_other_category_request);

				if($finderarr['city_id'] == 10000){
					$finderarr['city']['name'] = $finderarr['custom_city'];
					$finderarr['location']['name'] = $finderarr['custom_location'];
					$nearby_same_category = [];
					$nearby_other_category = [];
				}

				if(!$this->utilities->isIntegratedVendor($finderarr)){
					$data['nearby_same_category']['title'] = ucwords($finderarr["category"]["name"]).' Near '.$finderarr['location']['name'];
					$data['nearby_same_category']['description'] = 'Check out Fitternity recommended handpicked '.strtolower($finderarr["category"]["name"]).' in '.$finderarr['location']['name'].' near you...';
					$data['nearby_same_category']['near_by_vendor'] = $nearby_same_category;
				}

                $data['recommended_vendor']['title'] = "Other popular options in ".$finderarr["location"]["name"];
                $data['recommended_vendor']['description'] = "Checkout fitness services near you";
				$data['recommended_vendor']['near_by_vendor'] = $nearby_other_category;

				// $data['finder']['review_data'] = $this->utilities->reviewScreenData($finder);

				// $data['finder']['review_data']['finder_id'] = $data['finder']['_id'];
				// $data['finder']['review_data']['tag'] = ['Membership', 'Trial', 'Workout-session'];

				$data['finder']['review_url'] = Config::get('app.url').'/finderreviewdata/'.$data['finder']['_id'];
                $data['show_membership_bargain'] = false;
				$data['finder']['city_name'] = strtolower($finderarr["city"]["name"]);
				if($this->utilities->isIntegratedVendor($data['finder'])){
					$this->applyFitsquadSection($data);
					$data['finder']['finder_one_line'] = $this->getFinderOneLiner($data);
				}
				
				$data = Cache::tags($cache_name)->put($cache_key, $data, Config::get('cache.cache_time'));

			}

		}

		$finderData = Cache::tags($cache_name)->get($cache_key);
	
		if(count($finderData) > 0 && isset($finderData['status']) && $finderData['status'] == 200){

			$finder = Finder::active()->where('slug','=',$tslug)->first();

			if($finder){
				try{
						$this->updateFinderHit($finder);
				}catch(Exception $e){
					Log::info($e);
				}
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

						$customer_trials_with_vendors       =       Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->orWhere('customer_email', $customer_email)->orWhere('customer_phone',substr($customer_phone, -10));})
                        ->where('finder_id', '=', (int) $finder->_id)
						// ->where('tag', ['Membership', 'Trial', 'Workout-session'])
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

                    if(!empty($finderData['finder']['category']['_id']) && $finderData['finder']['category']['_id'] != 42 ){

                        if(empty($customer_trials_with_vendors->toArray())){

                            if(!empty($finderData['finder']['facilities']) && in_array( "Free Trial" , $finderData['finder']['facilities'])){
                                $finderData['call_for_action_button']      =      "Book Free Session";
                                $finderData['call_for_action_text'] = 'Experience a workout at '.$finderData['finder']['title'].' by booking your first trial session';    
                            }else{
                                $finderData['call_for_action_button']      =      "Book A Session";
								$finderData['call_for_action_text'] = 'Experience a workout at '.$finderData['finder']['title'].' by booking your first trial session'; 
								// if(!empty($finderData['finder']['flags']['monsoon_campaign_pps'])){
                                //     $finderData['call_for_action_button']      =      "Book a Session @ 73";
                                // }   
                            }
                        }else{
                            $finderData['call_for_action_button']      =      "Book A Session";
                            $finderData['call_for_action_text'] = 'Experience a workout at '.$finderData['finder']['title'].' by booking sessions';    

                            // if(!empty($finderData['finder']['flags']['monsoon_campaign_pps'])){
                            //     $finderData['call_for_action_button']      =      "Book a Session @ 73";
                            // }
                        }

                    }

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

					// if($pay_per_session){
					// 	$finderData['finder']['pay_per_session'] = true;
					// }

					// if(isset($finderData['finder']['pay_per_session']) && $finderData['finder']['pay_per_session']){

					// 	$cheapest_price = $this->getCheapestWorkoutSession($finderData['finder']['services_workout'], 'app');
						
					// 	if($cheapest_price>0){

					// 		$finderData['finder']['pps_content'] = [
					// 			'header1'=>	'PAY - PER - SESSION',
					// 			'header2'=>	'Available here',
					// 			'header3'=>	"Why pay for 30 days when you use for 6 days?\nPay Per Session at ".$finderData['finder']['title']." by just paying Rs. ".$cheapest_price,
					// 			'image'=>''
					// 		];

					// 	}else{

							$finderData['finder']['pay_per_session'] = false;
					// 	}
					// }

					if(!in_array("false", $disable_button)){
						$finderData['call_for_action_button'] = "";
						$finderData['finder']['pay_per_session'] = false;
					}

                }

                $extended_validity_ratecards = 0;
                foreach($finderData['finder']['services'] as $service){
                    foreach($service['ratecard'] as $ratecard){
                        if($ratecard['type'] == 'extended validity'){
                            $extended_validity_ratecards++;
                        }
                    }
                }
                
                if($pps_stripe = $this->addPPSStripe($finderData['finder'])){
                    $finderData['finder']['services'] = $pps_stripe;
                }
				
				$isIntegratedVendor = $this->utilities->isIntegratedVendor($finderData['finder']);

                if($extended_validity_ratecards >= 2 && $isIntegratedVendor){
                    
                    $finderData['fit_ex'] =[
                        'title'=>"Most effective way to workout at ".$finderData['finder']['title']." is here!",
                        'subtitle'=>"Use Fitternity’s Extended Validity Membership to workout here with a longer validity period",
                        'image'=>'https://b.fitn.in/global/fitex-logo.png',
                        'data'=>[
                            [
                                'title'=>"Unlimited Validity Membership",
                                'subtitle'=>"Buy a sessions pack and use it over a longer duration",
                                'image'=>'https://b.fitn.in/global/web%20NVM%403x.png'
                            ],
                            [
                                'title'=>"Money Saver",
                                'subtitle'=>"Pay only for the days you workout",
                                'image'=>'https://b.fitn.in/global/pps%20-%20web/Path%2027%403x.png'
                            ],
                            [
                                'title'=>"Easy to Book",
                                'subtitle'=>"Book your workout through the app or scan QR code at gym/studio",
                                'image'=>'https://b.fitn.in/non-validity/success-page/mob%20icon%201.png'
                            ],
                            [
                                'title'=>"Track Your Usage",
                                'subtitle'=>"Check the workout counter in your Fitternity profile",
                                'image'=>'https://b.fitn.in/non-validity/success-page/WEB%20icon%202.png'
                            ],
                        ]
                    ];
                }else if($isIntegratedVendor){
                    
                    if($pps_stripe){
                        
                        $finderData['fit_ex'] =[
                            'title'=>"Now working out at ".$finderData['finder']['title']." is possible without buying a membership",
                            'subtitle'=>"Use Fitternity's Pay-Per-Session to workout here and pay session by session",
                            'image'=>'https://b.fitn.in/global/pps%20-%20web/Group%20188%403x.png',
                            'data'=>[
                                [
                                    'title'=>"Money Saver",
                                    'subtitle'=>"Pay only for the days you workout",
                                    'image'=>'https://b.fitn.in/global/pps%20-%20web/Path%2027%403x.png'
                                ],
                                [
                                    'title'=>"Unlimited Access",
                                    'subtitle'=>"Book multiple sessions.",
                                    'image'=>'https://b.fitn.in/global/pps%20-%20web/Group%20323%403x.png'
                                ],
                                [
                                    'title'=>"Super Easy",
                                    'subtitle'=>"Book, Reschedule, Cancel on the go",
                                    'image'=>'https://b.fitn.in/global/pps%20-%20web/Group%20325%403x.png'
                                ],
                                [
                                    'title'=>"Get Addicted",
                                    'subtitle'=>"Book, Burn & get rewarded on every workout",
                                    'image'=>'https://b.fitn.in/global/pps%20-%20web/Group%20322%403x.png'
                                ],
                            ]
                        ];
                    }
                        
                }
                
                $this->serviceRemoveFlexiIfExtendedPresent($finderData, "app");
                
                if($finderData['finder']['commercial_type'] == 0){
					$finderData['finder']['trial'] = "disable";
					$finderData['finder']['membership'] = "disable";
				}

				if(isset($finderData['finder']['trial']) && $finderData['finder']['trial'] == "disable" ){
					$finderData['call_for_action_button'] = "";
					$finderData['finder']['pay_per_session'] = false;
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
					'chat_enable'=>false,
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
			$finderData['pending_payment'] = $this->utilities->hasPendingPayments();
			if(!$finderData['pending_payment']){
				unset($finderData['pending_payment']);	
			}

            if(((!empty($finderData['finder']['brand_id']) && $finderData['finder']['brand_id'] == 130) || (in_array($finderData['finder']['_id'], Config::get('app.powerworld_finder_ids', [])))) && empty($finderData['trials_booked_status'])){

            }else{
               $finderData['trials_booked_status'] = true;
			}

			if($this->utilities->isIntegratedVendor($finderData['finder'])){
				$finderData['renewal_data'] = [
					"header" => "Are you looking to renew your membership?",
					"title" => "All above rates are applicable to new members only. If you are looking to renew your membership at ".$finderData['finder']['title']." share your details & we'll help you with the best offer.",
					"button_title" => "RENEW NOW",
					"callback_header" => "Renewal request for ".$finderData['finder']['title']
				];
			}
			$finderData['total_photos_count'] = count($finder['photos']);

		}else{

			$finderData['status'] = 404;
		}

		try{
			$this->orderRatecards($finderData, 'app');
		}catch(Exception $e){
			Log::info("Error while sorting ratecard", [$e]);
		}

		// $workout_ratecard_arr = array();
		// foreach($finderData['finder']['services'] as $service){
		// 	foreach($service['ratecard'] as $ratecard){
		// 		if($ratecard['type'] == 'workout session' || $ratecard['type'] == 'trial'){
		// 		Log::info("ratecard_id :::", [$ratecard['_id']]);
		// 			array_push($workout_ratecard_arr, 1);
		// 		}
		// 	}
		// }

		// Log::info("workout_ratecard_arr ::", [$workout_ratecard_arr]);
		// Log::info("count workout_ratecard_arr ::", [count($workout_ratecard_arr)]);
		
		// if(count($workout_ratecard_arr) == 0){
		// 	Log::info("no workout ratecard");
		// 	$finderData['call_for_action_button'] = "";
		// 	$finderData['finder']['pay_per_session'] = false;
		// }

		// commented on 9th August - Akhil
		if(!empty($customer_id)){
			$this->addCreditPoints($finderData['finder']['services'], $customer_id);
		}
		//adding static data for hanman fitness
		// if(isset($finderData['finder']) && isset($finderData['finder']['brand_id']) && $finderData['finder']['brand_id']==56){
		// 	$finderData['finder']['finder_one_line']='All above rates are applicable to new members only. If you are looking to renew your membership at hanMan';
		// }
		//Log::info('finder',[$finderData['finder']]);

		foreach($finderData['finder']['services'] as &$service){
			foreach($service['ratecard'] as &$ratecard){
				if($ratecard['type'] == 'workout session' || $ratecard['type'] == 'trial'){
					$price = !empty($ratecard['special_price']) ? $ratecard['special_price'] : $ratecard['price'];
					Log::info("Price onepass ::",[$price]);
					$onepassHoldCustomer = $this->utilities->onepassHoldCustomer();
					$allowSession = false;
					if(!empty($onepassHoldCustomer) && $onepassHoldCustomer) {
						$allowSession = $this->passService->allowSession($price, $customer_id);
						if(!empty($allowSession['allow_session'])) {
							$allowSession = $allowSession['allow_session'];
						}
						else {
							$allowSession = false;
						}
					}
					if($allowSession){
						unset($ratecard['button_color']);
						unset($ratecard['pps_know_more']);
						unset($ratecard['pps_title']);
						unset($ratecard['remarks']);
						unset($ratecard['remarks_imp']);
						unset($ratecard['price_text']);

						unset($finderData['fit_ex']);

						$ratecard['price'] = $ratecard['special_price'] = "0";
						$ratecard['start_price_text'] = Config::get('app.onepass_free_string');
						$ratecard['skip_share_detail'] = true;
					}
				}
			}
		}

		return Response::json($finderData,$finderData['status']);

	}




	public function getTrialWorkoutRatecard($finderservices,$findertype,$type, $finder_trial = null, $finder=null){

		$finderservicesArr  =   [];

		foreach ($finderservices as $finderservice){

			$finderserviceObj   =   array_except($finderservice,['ratecard']);
			$ratecardArr        =   [];

			if(isset($finderservice['ratecard']) && count($finderservice['ratecard']) > 0){

				// $ratecard = Ratecard::where('type',$type)->where('service_id', intval($finderserviceObj['_id']))->first();

				// if($ratecard){
				// 	$ratecard = $ratecard->toArray();
				// 	$ratecard['offers'] = [];

				// 	if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
	            //         $ratecard_price = $ratecard['special_price'];
	            //     }else{
	            //         $ratecard_price = $ratecard['price'];
	            //     }
				// 	(isset($ratecard['special_price']) && $ratecard['price'] == $ratecard['special_price']) ? $ratecard['special_price'] = 0 : null;
					
				// 	$ratecard['cashback_on_trial'] = "";

				// 	if($ratecard_price > 0 && $type == 'trial'){
				// 		$ratecard['cashback_on_trial'] = "100% Cashback";
				// 	}

				// 	if((isset($finderservice['trial']) && $finderservice['trial']=='manual' || $finder_trial=='manual') && $ratecard['type'] == 'trial'){
				// 		if(isset($_GET['app_version']) && isset($_GET['device_type']) && (($_GET['device_type'] == 'android' && $_GET['app_version'] > 4.42) || ($_GET['device_type'] == 'ios' && version_compare($_GET['app_version'], '4.4.2') > 0))){
				// 			Log::info($ratecard['_id']);
				// 			$ratecard['manual_trial_enable'] = "1";
				// 			unset($ratecard['direct_payment_enable']);
				// 			Log::info("manual_trial_enable");
							
				// 		}else{
				// 			$ratecard['direct_payment_enable'] = "0";
				// 			Log::info("direct_payment_enable");
							
				// 		}
				// 	}
				// 	array_push($ratecardArr, $ratecard);
				// }
				// return $finderservice['ratecard'];
				// exit;
				foreach ($finderservice['ratecard'] as $ratecard){
                    //Log::info($ratecard);
					if(in_array($ratecard["type"],["workout session", "trial"])){
                        unset($ratecard['remarks']);
                        
						if($type == "workout session" && in_array($ratecard["type"],["trial"])){
							continue;
						}
                        if($ratecard['type'] == 'workout session' && isFinderIntegrated($finder) && isServiceIntegrated($finderservice)){
                            $ratecard['remarks'] = "Get 100% Instant Cashback, Use Code: CB100";
                            // if(!empty($finder['flags']['monsoon_campaign_pps']) && ($ratecard['price'] == 73 || $ratecard['special_price'] == 73)){
                            //     $ratecard['remarks'] = "Get 100% Instant Cashback, Use Code: CB100";
                            // }
                        }
						if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
							$ratecard_price = $ratecard['special_price'];
						}else{
							$ratecard_price = $ratecard['price'];
						}
						(isset($ratecard['special_price']) && $ratecard['price'] == $ratecard['special_price']) ? $ratecard['special_price'] = 0 : null;
						$ratecard['cashback_on_trial'] = "";

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
						}else{
							if($ratecard_price > 0 && $ratecard['type'] == 'trial'){
								$ratecard['cashback_on_trial'] = "100% Cashback";
							}
						}

						if($ratecard['price'] == 0 && $ratecard['special_price'] == 0){
							$ratecard['start_price_text'] = "Free Via Fitternity";
						}
					}
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

						if(isset($ratecard['flags']['pay_at_vendor']) && $ratecard['flags']['pay_at_vendor']){
							$ratecard['direct_payment_enable'] = "0";
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

        // $jwt_token = $token;

        // $jwt_key = Config::get('app.jwt.key');
        // $jwt_alg = Config::get('app.jwt.alg');
        // try {
        //     $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

        // }catch (Exception $e) {
        //     // Log::info($e);
        //     return null;
        // }
        
        // return $decodedToken;

        return customerTokenDecode($token);
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

		/*$data[] = [
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
		];*/

		$ladies_gym = [
			'Mumbai'=>[
				[
					'name'=>'Gyms for ladies  Mumbai',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug
				],
				[
					'name'=>'Gyms for ladies Thane West',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Thane West')) 
				],
				[
					'name'=>'Gyms for ladies Dadar',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Dadar'))
				],
				[
					'name'=>'Gyms for ladies Colaba',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Colaba'))
				],
				[
					'name'=>'Gyms for ladies Borivali West',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Borivali West'))
				],
			],
			'Pune'=>[
				[
					'name'=>'Gyms for ladies Pune',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug
				],
				[
					'name'=>'Gyms for ladies Dhankawadi',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Dhankawadi'))
				],
				[
					'name'=>'Gyms for ladies Kothrud',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Kothrud'))
				],
				[
					'name'=>'Gyms for ladies Camp',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Camp'))
				]
			],
			'Bangalore'=>[
				[
					'name'=>'Gyms for ladies Bangalore',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug
				],
				[
					'name'=>'Gyms for ladies J P Nagar',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('J P Nagar'))
				],
				[
					'name'=>'Gyms for ladies Jaya Nagar',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Jaya Nagar'))
				],
				[
					'name'=>'Gyms for ladies Bannerghatta Road',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Bannerghatta Road'))
				],
				[
					'name'=>'Gyms for ladies HSR Layout',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('HSR Layout'))
				]
			],
			'Delhi'=>[
				[
					'name'=>'Gyms for ladies Delhi',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug
				],
				[
					'name'=>'Gyms for ladies Paschim Vihar',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Paschim Vihar'))
				],
				[
					'name'=>'Gyms for ladies Dwarka',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Dwarka'))
				],
				[
					'name'=>'Gyms for ladies Punjabi Bagh',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Punjabi Bagh'))
				],
				[
					'name'=>'Gyms for ladies Karol Bagh',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Karol Bagh'))
				]
			],
			'Gurgaon'=>[
				[
					'name'=>'Gyms for ladies Gurgaon',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug
				],
				[
					'name'=>'Gyms for ladies DLF Phase 1',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('DLF Phase 1'))
				],
				[
					'name'=>'Gyms for ladies DLF Phase 4',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('DLF Phase 4'))
				],
				[
					'name'=>'Gyms for ladies Sector 45',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Sector 45'))
				],
				[
					'name'=>'Gyms for ladies Palam Vihar',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Palam Vihar'))
				]
			],
			'Hyderabad'=>[
				[
					'name'=>'Gyms for ladies Hyderabad',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug
				],
				[
					'name'=>'Gyms for ladies Himayat Nagar',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Himayat Nagar'))
				],
				[
					'name'=>'Gyms for ladies Miyapur',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Miyapur'))
				],
				[
					'name'=>'Gyms for ladies Ameerpet',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Ameerpet'))
				],
				[
					'name'=>'Gyms for ladies Ashok Nagar',
					'link'=> Config::get('app.website').'/ladies-gym-'.$city_slug.'-'.str_replace(" ","-",strtolower('Ashok Nagar'))
				]
			],
		];

		if(!empty($ladies_gym[$city_name])){

			$data[] = [
				'title'=>'Gyms Near Me for Ladies',
				'row'=>$ladies_gym[$city_name]
			];

		}

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

		$multifitFinder = $this->utilities->multifitFinder();

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
							"title"=>"Fitstore",
							"image"=>"https://b.fitn.in/products/home_banner_1.jpg"
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
				
			];
		}

		if($this->kiosk_app_version &&  $this->kiosk_app_version > 1.13){
			$response["response"]["options"][] = [
				"title"=>"Fitstore",
				"description"=>"Buy products",
				"image"=>"https://b.fitn.in/products/fitsotr_banner_small.png",
				"banner_image"=>"https://b.fitn.in/products/fitstore_banner_large.png",
				"id"=>8,
				'type'=>'fitstore'
			];
		}

		$response["response"]["options"][] =[
			"title"=>"Fitternity Advantage",
			"description"=>"Buy through Fitterntiy & get access to these amazing rewards",
			"image"=>"https://b.fitn.in/global/tabapp-homescreen/reward-small.jpg",
			"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/rewards-big-picture-1.jpg",
			"id"=>7,
			'type'=>'rewards'
		];

		if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && isset($finder['brand_id']) && $finder['brand_id'] == 66 && $finder['city_id'] == 3){

			$response["response"]["powered"] = "Powered by ";//.ucwords($finder['title']);
			$response["response"]["logo"] = "https://b.fitn.in/global/snap_logo_1.png";

			unset($response["response"]["about"]);

			foreach ($response["response"]["options"] as &$value){

				$value['title'] = str_replace("Fitternity ","",$value['title']); 
			}

			array_pop($response["response"]["options"]);
			array_pop($response["response"]["options"]);

		}
		
		Log::info("kiosk version: ",[$this->kiosk_app_version]);
		Log::info("multifit finder: ",[$multifitFinder]);
		if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && in_array($finder_id, $multifitFinder)){
			Log::info("multifit");
			unset($response["response"]["about"]);
            $response["response"]["options"]= [
				[
					"title"=>"Access Fitternity Booking",
					"description"=>"Quick step to activate your trial/session",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/access_booking.png",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/access_booking_banner.png",
					"id"=>1,
					'type'=>'access_booktrial'
				],
				[
					"title"=>"Buy Membership",
					"description"=>"Quick buy with free rewards & flexible payment options.",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/buy_membership.png",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/buy_membership_banner.png",
					"id"=>2,
					"type"=>'memberships'
				],
				[
					"title"=>"Schedule New Bookings",
					"description"=>"Pick a day & slot that works for you and get started",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/schedule_new_booking.png",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/schedule_new_booking_banner.png",
					"id"=>6,
					'type'=>'booktrials'
				],
				[
					"title"=>"Activate Fitternity Membership",
					"description"=>"Quick step to activate your membership",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/active_membership.png",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/activate_membership_banner.png",
					"id"=>5,
					"type"=>'activate_membership'
				],
				[
					"title"=>"Post a Review",
					"description"=>"Rate your experience & help fellow fitness enthusiasts.",
					"image"=>"https://b.fitn.in/global/tabapp-homescreen/post_a_review.png",
					"banner_image"=>"https://b.fitn.in/global/tabapp-homescreen/post_a_review_banner.png",
					"id"=>3,
					'type'=>'post_review'
				],				
				
            ];
			foreach ($response["response"]["options"] as &$value){
				$value['title'] = str_replace("Fitternity ","Multifit ",$value['title']);
			}
			
			array_pop($response["response"]["options"]);
			
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
	
	function getCheapestWorkoutSession($services, $source=""){

		$ratecards_key = 'serviceratecard';

		if($source == 'app'){
			$ratecards_key = 'ratecard';
		}

		$price = 0;

		foreach($services as $service){
			if(isset($service[$ratecards_key])){
				foreach($service[$ratecards_key] as $ratecard){
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

				if(isset($ratecard) && isset($ratecard['type']) && in_array($ratecard['type'],['membership','packages','extended validity'])){

					$tnc['description'] .= "<b> - </b>  Discount varies across different outlets depending on slot availability.";
					$tnc['description'] .= "<br/><br/><b> - </b>  For memberships reserved by part payment and not fully paid for on date of joining, 5% of total membership value will be deducted as convenience fees & the remaining will be transferred in the wallet as Fitcash+ . The membership will also be terminated.";
					$tnc['description'] .= "<br/><br/><b> - </b>  Memberships once purchased are not transferrable or resalable.";
					$tnc['description'] .= "<br/><br/><b> - </b>  For detailed terms and conditions refer to <a href='https://www.fitternity.com/terms-conditions/all'> https://www.fitternity.com/terms-conditions/all</a>";
					
				}else{

					$tnc['description'] .= "<b> - </b>  I recognise that staff at ".ucwords($finder['title']).$location_name.", their associates and staff at Fitternity Health E Solutions are not able to provide medical advice or assess whether it is suitable for me to participate in programs.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I participate at my own risk. I acknowledge that as with any exercise program, there are risks, including increased heart stress and the chance of musculoskeletal injuries.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I warrant that I am physically and mentally well enough to proceed with the classes and have furnished true details in the form above.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I hereby waive, release and forever discharge ".ucwords($finder['title']).", Fitternity Health E Solutions and all their associates from all liabilities for injures or damages resulting from my participation in fitness activities and classes.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I understand no refunds or transfer / extension of services will be issued for unused classes, sessions and services.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I have read and understand the advice given above.";
					$tnc['description'] .= "<br/><br/><b> - </b>  I assume the risk of and responsibility of personal property loss or damage.";

					if(isset($ratecard) && isset($ratecard['type']) && in_array($ratecard['type'],['workout session'])){
						$tnc['description'] .= "<br/><br/><b> - </b>  Maximum discount for First free session is Rs 299.";
					}

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

        if(empty($finder_id)){
            $response['tnc']['description'] = $this->passService->passTermsAndCondition()['data'];
        }
		return $response;

	}

	public function getCalloutOffer($services,$source = 'web'){

		$key = 'serviceratecard';
        $service_name = 'name';

		if($source == 'app'){
            $key = 'ratecard';
            $service_name = 'service_name';
		}

		$return = [
			"callout"=>"",
			"ratecard_id"=>null,
			"service_id"=>null,
			"type"=>"",
			"button_text"=>"Book",
			"amount"=>0,
		];
		
		foreach($services as $service){

			foreach($service[$key] as $ratecard){

				if(!empty($ratecard['offers_copy'])){
					$ratecard['offers'] = $ratecard['offers_copy'];
				}

				if(!empty($ratecard['offers']) && !empty($ratecard['offers'][0]['offer_type']) && $ratecard['offers'][0]['offer_type'] == 'newyears'){

					$return['callout'] = $service[$service_name]." - ".$this->getServiceDuration($ratecard)." @ Rs. ".$ratecard['offers'][0]['price'].". ";

					if($source == 'app'){

						$return['callout'] = $service[$service_name]." - <b>".$this->getServiceDuration($ratecard)."</b> @ Rs. <b>".$ratecard['offers'][0]['price']."</b>. ";
					}

					if(!empty($ratecard['offers'][0]['callout'])){
						$return['callout'] = $ratecard['offers'][0]['callout'];
					}

					$return['ratecard_id'] = (int)$ratecard['_id'];

					$return['service_id'] = (int)$ratecard['service_id'];

					$return['type'] = $ratecard['type'];

					$return['amount'] = $ratecard['offers'][0]['price'];

                    if(!empty($ratecard['non_validity_ratecard'])){
                        $return['non_validity_ratecard'] = $ratecard['non_validity_ratecard'];
						// $return['non_validity_ratecard']['description'] = $return['non_validity_ratecard']['description'].Config::get('nonvalidity.how_works');
						$return['non_validity_ratecard']['description'] = strtr($return['non_validity_ratecard']['description'], ['no_of_sessions'=>$ratecard['duration']]);
                    }

					if(in_array($ratecard['type'],["membership","packages"])){
						$return['button_text'] = "Buy";
					}

                    if(!empty($ratecard['flags']['unlimited_validity'])){
                    
                        $return['callout_extended'] = "Unlimited Validity Membership";
                    
                    }else{
                    
                        $return['callout_extended'] = "Extended Validity Membership";
                    
                    }

					break;
				}
			}	
		}

		return $return;
	}

	public function getPageViewsForVendors(){
				$data = Input::all();
				$orderDatetime = date("Y-m-d",strtotime("2016-01-01"));
				$orderEndDatetime = date("Y-m-d");
		if(isset($data["start_date"])){
			$orderDatetime = date("Y-m-d",strtotime($data["start_date"]));	
		}
		if(isset($data["end_date"])){
			$orderEndDatetime = date("Y-m-d",strtotime($data["end_date"]));	
		}
		$vendors = [];
		$vendor_ids = [];
		if(isset($data["vendors"]) && count($data["vendors"])){

			$vendors = Finder::whereIn("_id", $data["vendors"])->get(["slug", "_id"]);
			$vendor_ids = array_pluck($vendors, 'slug');
		}
		// return $orderDatetime;
		$query = '{
			"aggs": {
			  "vendor": {
				"aggs": {
				  "vendor_slug": {
					"terms": {
					  "field": "slug",
					  "size": 0
					}
				  }
				},
				"filter": {
					"bool": {
						"must": [
							{
								"range": {
								  "timestamp": {
									"gt": "'.$orderDatetime.'",
									"lt": "'.$orderEndDatetime.'"
								  }
								}
							},
							{
								"terms": {
									"slug":  ["'.strtolower(implode('","', $vendor_ids)).'"]
								}
							}
						]
					  }
				}
			  }
			},
			"query": {
			  "filtered": {
				"filter": {
				  "bool": {
					"must": [
						{
							"range": {
							  "timestamp": {
								"gt": "'.$orderDatetime.'",
								"lt": "'.$orderEndDatetime.'"
							  }
							}
						},
						{
							"term": {
							"event_id": "vendorpageloaded"
							}
						}
					]
				  }
				}
			  }
			}
		  }';
		//   return $query;
			$request = array(
				'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
				'port' => 8060,
				'method' => 'POST',
				'postfields' => $query
				);
				// .strtolower(implode('","', $keylist)).
			
			$search_results     =   json_decode(es_curl_request($request),true);
			$result = [];
			foreach($search_results["aggregations"]["vendor"]["vendor_slug"]["buckets"] as &$vendor){
				foreach($vendors as $struct) {
					if ($vendor["key"] == $struct->slug) {
						$vendor["_id"] = $struct->_id;
						break;
					}
				}
				array_push($result, $vendor);
			}
			// return $vendors;
			return $result;
			
	}

	public function removeConvinienceFee(&$finder){
		foreach ($finder['services'] as &$service){
			unset($service['flags']['convinience_fee_applicable']);
		}
		
	}

	public function getBrandVendors($brand_id,$city_id)
	{
		try {
			
			$data = ['brand_id'=>$brand_id,'city_id'=>$city_id];
			$rules = ['brand_id' => 'required|numeric','city_id' => 'required|numeric'];
			$validator = Validator::make($data,$rules);
			if ($validator->fails()) {
				$response = array('status' => 400, 'message' => 'Id absent.', 'errors' => $validator->errors());
				return Response::json($response, 400);
			}
			else
			{
				$main=[];
				$finders=Finder::active()->where("city_id",intval($city_id))->where("commercial_type","!=",0)->where("membership","!=","disable")->where("membership","!=","disable")->where("brand_id",intval($brand_id))->with(array('location'=>function($query){$query->select('_id','name','slug');}))->get(['location_id','slug','title','contact']);
// 				return $finders;
				if(!empty($finders))
				{
					$finderIds=[];
					foreach ($finders as &$finder)
					{
						$t=((!empty($finder->contact)&&!empty($finder->contact)&&!empty($finder->contact))?$finder->contact:[]);
						$oo=[
								"_id"=>(!empty($finder->_id)?$finder->_id:""),
								"title"=>(!empty($finder->title)?$finder->title:""),
								"slug"=>(!empty($finder->slug)?$finder->slug:""),
								"address"=>((!empty($t['address']))?$t['address']:""),
								"name"=>(!empty($finder->location)&&!empty($finder->location->name))?$finder->location->name:""];
						array_push($finderIds,$oo);
					}
					$services=Service::active()->whereIn("finder_id",array_map('intval',array_pluck($finderIds, "_id")))->whereIn("showOnFront",[null,'web'])->where("membership","!=","disable")->lists('_id');
					$rateCards=Ratecard::whereIn("finder_id", array_map('intval',array_pluck($finderIds, "_id")))->whereIn("service_id",array_map('intval',$services))
					->where(function ($query){ $query->where('price',9990 )->orWhere('special_price', 9990);})
					->where("validity",3)->where("validity_type","months")->get(['_id','finder_id','service_id']);
					
					if(!empty($rateCards))
					{
						$alreadyHas=[];
						foreach ($rateCards as &$rateCard)
						{
							$rf=intval($rateCard->finder_id);
							if(!array_key_exists($rf, $alreadyHas))
							{
								$obj=[];
								$find=array_values(array_filter($finderIds,function ($e) use ($rf) {return $e['_id'] == $rf;}))[0];
								$rcd=(!empty($rateCard->_id)?$rateCard->_id:"");
								$sid=(!empty($rateCard->service_id)?$rateCard->service_id:"");
								if(!empty($rcd)&&!empty($sid))
								{
									$obj['url']=Config::get('app.website')."/buy/".((!empty($find)&&!empty($find['slug']))?$find['slug']:"")."/".$sid."/".$rcd;
									$obj['name']=(!empty($find['title'])?$find['title']:"")." - ".(!empty($find['name'])?$find['name']:"");
									$obj['address']=(!empty($find['address'])?$find['address']:"");
									array_push($alreadyHas,intval($rf));
									array_push($main,$obj);
								}
							}
						}
					}
				}
				return Response::json($main, 200);
			}
			
		} catch (Exception $e) {
			
			$message = array(
					'type'    => get_class($e),
					'message' => $e->getMessage(),
					'file'    => $e->getFile(),
					'line'    => $e->getLine(),
					'stack'    => $e->getTraceAsString()
			);
			Log::info("  Error ".print_r($message,true));
			return Response::json($message, 400);
			
		}
	}
		
	public function createTiming($open_close_hour_for_week){

		$result = "";
		
		if(count($open_close_hour_for_week)){
			
			$result = "<p><strong>Gym</strong></p>";
	
			foreach($open_close_hour_for_week as $day){
				$result = $result."<p><i>".ucwords($day['day'])." : </i>".str_pad($day['opening_hour'], 8, '0', STR_PAD_LEFT)."-".$day['closing_hour']."</p>";
			}
		}
		return $result;
	}

	public function updateFinderHit($finder){
		if(!empty($_GET['source'])){
			$source = $_GET['source'];
			Log::info("web Increased ".$source);
			$finder = Finder::find($finder['_id']);
			$total_hits = !empty($finder['hits'][$source]) ? $finder['hits'][$source] + 1 : 1 ;
			Log::info($total_hits);
			Finder::where('_id', $finder['_id'])->update(['hits.'.$source =>$total_hits]);
		}else{
			if(!empty($finder['flags']['hyper_local'])){
				Log::info("app Increased featured");
				$finder = Finder::find($finder['_id']);
				$total_hits = !empty($finder['hits']['featured_search']) ? $finder['hits']['featured_search'] + 1 : 1 ;
				Log::info($total_hits);
				Finder::where('_id', $finder['_id'])->update(['hits.featured_search'=>$total_hits]);
			}else{
				Log::info("Not Increased featured");
			}
		}
		
	}
	public function addPPSStripe($finder, $source='app'){
		
		$ratecard_key = 'ratecard';

		if($source != 'app'){
			$ratecard_key = 'serviceratecard';
		}
		
		foreach($finder['services'] as &$service){
			$pps_ratecard = null;
			if(isFinderIntegrated($finder) && isServiceIntegrated($service)){

				foreach($service[$ratecard_key] as $key => &$ratecard){
					
					if(isset($ratecard['type']) && $ratecard['type'] == 'workout session'){
						$pps_exists = true;
                        // $ratecard['start_price_text'] = 'Starting at';
						// $pps_ratecard = $ratecard;
					}
	
					// if(isset($ratecard['type']) && $ratecard['type'] == 'membership'){
					// 	if(isset($pps_exists) && $pps_exists){
					// 		array_splice( $service[$ratecard_key], $key+1, 0, [$this->addPPSStripeData($pps_ratecard, $service, $finder)]); 
					// 		break;
					// 	}else{
					// 		break;
					// 	}
						
					// }
				}
			}
		}
		
		return !empty($pps_exists) ? $finder['services'] : false;
	}

	public function addPPSStripeData($ratecard, $service, $finder){
		
		$count = !empty($finder['pageviews']) ? $finder['pageviews'] : 0;
		
		if(!$count){
			$count = 10233;
		}

		$count = round($count/10) + (!empty($service['traction']['sales']) ? $service['traction']['sales'] : 0 ) + (!empty($service['traction']['trials']) ? $service['traction']['trials'] : 0 ) + (!empty($service['traction']['requests']) ? $service['traction']['requests'] : 0 );
		
		$return = ['type'=>'pps_stripe', 'service_id'=>$ratecard['service_id'], 'finder_id'=>$ratecard['finder_id'], 'line1'=>'Book multiple sessions without buying a membership & pay only when you workout', 'line2'=>'USE PAY - PER - SESSION', 'line3'=>'(Not a Membership, Not a Pass - Even Better)', '_id'=>0];

		$return['pps_details'] =[
			'pps'=>[
				'header'=>'PAY - PER - SESSION',
				'data'=>"Convenience • Variety • No Commitment",
			],
			'description'=>[
				'header'=>'',
				'data'=>[
					['image'=>'https://b.fitn.in/paypersession/pps_stripe1.png', 'text'=>'Pay only for the days you workout'],
					['image'=>'https://b.fitn.in/paypersession/pps_stripe2.png', 'text'=>'Book, Reschedule, Cancel on-the-go'],
					['image'=>'https://b.fitn.in/paypersession/pps_stripe3.png', 'text'=>'Book sessions multiple times at '.$finder['title']]
				],
			],
		];

		// $return['pps_details']['more_info'] = [
		// 	'header'=>'See how pay per session works',
		// 	'description'=>"Step 1: Choose your workout form out of 17 different options<br><br>Step 2: Book session of your choice near you with instant booking<br><br>Step3: Enjoy your workout and repeat",
		// ];
		
		$return['pps_details']['ps'] = $count.' people in '.(!empty($service['location']['name']) ? $service['location']['name'] : 'this location').' are using it.';

		$return['pps_details']['action'] = [
			'action_text'=>'Book Session here @Rs.'.($ratecard['special_price'] != 0 ? $ratecard['special_price'] : $ratecard['price']),
			'assistance_text'=>'Need help? Let us assist you',
			'phone_number'=>Config::get('app.contact_us_customer_number'),
			'finder_slug'=>$finder['slug'],
			'service_slug'=>$service['slug'],
		];

		return $return;



	}

	public function finderReviewData($finder_id){

		Log::info($_SERVER['REQUEST_URI']);
		
		Finder::$withoutAppends = true;		
		
		$finder = Finder::active()->where('_id', intval($finder_id))
				->with(array('category'=>function($query){$query->select('_id', 'detail_rating','detail_ratings_images');}))
				->with(array('location'=>function($query){$query->select('_id','name');}))
				->first(array('title','category_id', 'category','location_id'));

		if(!empty($_GET['service_name'])){
			$finder['service_name'] = ucwords(trim(urldecode($_GET['service_name'])));
		}

		if(!empty($_GET['service_location'])){
			$finder['service_location'] = ucwords(trim(urldecode($_GET['service_location'])));
		}

		$review_data = $this->utilities->reviewScreenData($finder);

		if(!empty($_GET['service_id'])){
			$review_data['service_id'] = intval($_GET['service_id']);
		}

		$review_data['finder_id'] = $finder['_id'];
		$review_data['optional'] = false;
		$review_data['show_rtc'] = false;
		if(empty($_GET['service_name'])){
			$review_data['section_3'] = [
				'tag' => ['Membership', 'Trial', 'Workout-session'],
				'header' => 'What is your review based on',
			];
		}

		return $review_data;
	}

	public function asyncUpdateFinderRating($job, $data){
		
		if($job){
			$job->delete();
		}

		$this->updateFinderRatingV2($data['finder']);
		$this->updateFinderRatingV1($data['reviewdata']);

	}
    public function integratedVendorList($city_id){
        $city_id = intval($city_id);
        Finder::$withoutAppends = true;
        $finders = Finder::active()
            ->where('city_id', $city_id)
            ->where('commercial_type', '!=', 0)
            ->where('flags.state','!=', 'closed')
            ->where(function($query){$query->orWhere('membership','!=',"disable")->orWhere('trial','!=',"disable");})
            ->with(array('location'=>function($query){$query->select('name');}))
            ->remember(Config::get('app.cachetime'), 'integrated_vendor_list_'.$city_id)
            ->get(['title', 'location_id'])->toArray();
        foreach($finders as &$finder){
            if(!empty($finder['location']['name'])){
                $finder['title'] = $finder['title'].', '.$finder['location']['name'];
            }
            $finder = array_only($finder, ['_id', 'title']);
        }
        return $finders;
	}

    public function applyNonValidity($data, $source = 'web'){
        
        $extended_services = [];
        $ratecard_key = 'ratecard';
		$service_name_key = 'service_name';

		if($source != 'app'){
			$ratecard_key = 'serviceratecard';
			$service_name_key = 'name';
		}

        $memberships = [];
        // // $session_pack_duration_map =  Config::get('nonvalidity.session_pack_duration_map');
        $duration_session_map =  Config::get('nonvalidity.duration_session_map');

        // function cmpSessions($a, $b)
        // {
        //     return $a['validity_copy'] <= $b['validity_copy'];
        // }
        // foreach($data['finder']['services'] as $key => $service){
        //     $no_validity_exists = false;
		// 	$no_validity_ratecards = [];
        //     $no_validity_ratecards_all = [];

        //     $this->extractNonValidityRatecards($service, $ratecard_key, $no_validity_ratecards, $ratecard, $duration_day, $no_validity_exists, $no_validity_ratecards_all);

        //     usort($no_validity_ratecards_all, "cmpSessions");

        //     if(!empty($no_validity_ratecards)){

        //         $data['finder']['extended_validity'] = true;

		// 		foreach($service[$ratecard_key] as $key1 => $ratecard){
		// 			//Log::info('ratecard type::::::: ', [$ratecard['type']]);
        //             if($ratecard['type'] == 'membership'){

        //                 $duration_day = $this->utilities->getDurationDay($ratecard);

		// 				$price = $this->utilities->getRatecardPrice($ratecard);
						
		// 				$ratecard['final_price'] = $price;
                        
        //                 if(empty($memberships[$service['_id']][$duration_day])){
        //                      $memberships[$service['_id']][$duration_day] = [];
        //                 }
		// 				array_push($memberships[$service['_id']][$duration_day], $ratecard) ;

        //                 if(!empty($duration_session_map[strval($duration_day)])){
                            
        //                     $sessions_range = $duration_session_map[strval($duration_day)];
        //                     foreach($no_validity_ratecards_all as $cs_ratecard){
        //                         $cs_ratecard_price = $this->utilities->getRatecardPrice($cs_ratecard);
        //                         if($cs_ratecard['duration'] > $sessions_range['low'] && $cs_ratecard['duration'] <= $sessions_range['high'] && $cs_ratecard_price <= $price * (1 + Config::get('nonvalidity.cross_sell_diff'))){

        //                             //$this->formatCrossSellRatecard($cs_ratecard, $cs_ratecard_price);

        //                             $this->formatRatecard($ratecard, $price);

        //                             //$this->getCorssSellSection($data, $ratecard, $cs_ratecard, $key, $ratecard_key, $key1);

        //                             break;
        //                         }
        //                     }
        //                 }
        //             }
        //             if($ratecard['type'] == 'extended validity'){
        //                 $duration_day = $this->utilities->getDurationDay($ratecard);
        //                 $price = $this->utilities->getRatecardPrice($ratecard);
                        
        //                 if(!empty($ratecard['flags']['unlimited_validity'])){
        //                     $ext_validity = "Unlimited Validity";
        //                 }else{
        //                     $ext_validity = "Valid for ".$service[$ratecard_key][$key1]['validity'].' '.$service[$ratecard_key][$key1]['validity_type'];
        //                 }

        //                 $this->formatServiceRatecard($data, $ext_validity, $key, $ratecard_key, $key1, $ratecard, $service);

        //             }
		// 		}
                
        //         $service['recommended'] = Config::get('nonvalidity.recommnded_block');
        //         $service['service_name_display'] = $service[$service_name_key];
		// 		$post_name = (!empty($no_validity_exists) ? "Unlimited" : "Extended")." Validity Membership";
						
        //         if(!empty($_GET['device_type']) && (($_GET['device_type'] == 'android' && $_GET['app_version'] < '5.18') || ($_GET['device_type'] == 'ios' && $_GET['app_version'] < '5.1.6'))){
		// 		    $service[$service_name_key] = $service[$service_name_key]." - ".$post_name;
        //         }else{	
		// 			if(!empty($_GET['device_type']) && in_array($_GET['device_type'], ['ios', 'android'])){
		// 				$service['post_name'] = " - ".$post_name;
		// 			}else{
		// 				$service['post_name'] = $post_name;
		// 			}

        //             $service['post_name_color'] = Config::get('app.ratecard_button_color');
				
		// 		}
		// 		$service['unlimited_validity'] = $no_validity_exists;
		// 		$no_validity_ratecards_service = [];

		// 		foreach(array_values($no_validity_ratecards) as $nvc){
		// 			$no_validity_ratecards_service = array_merge($no_validity_ratecards_service, $nvc);
		// 		}

        //         $service[$ratecard_key] = $no_validity_ratecards_service;
        //         $service['type'] = 'extended validity';
				

        //         array_push($extended_services, $service);
        //     }
		// }
		$data = $this->getExtendedValidityTypeToRateCards($data, $ratecard_key);
        // $extended_services_map = [];

        // foreach($extended_services as $ext_ser){
        //     $extended_services_map[$ext_ser['_id']] = $ext_ser;
        // }

        // $merged_services = [];
        
        // foreach($data['finder']['services'] as $ser_key => $ser_value){
        //     array_push($merged_services, $ser_value);
        //     if(!empty($extended_services_map[$ser_value['_id']])){
        //         array_push($merged_services, $extended_services_map[$ser_value['_id']]);
        //     }
        // }

        // $data['finder']['services'] = $merged_services;
        // try{
		// 	Log::info('ratecard keys::::::::::::::', [$ratecard_key]);
        //     foreach($data['finder']['services'] as &$s){
		// 		Log::info('services:::::::::::::::::: ');
        //             foreach($s[$ratecard_key] as &$r){
		// 				Log::info('ratecards:::::::::::::::::: ');
        //                 if($r['type'] == 'extended validity'){
        //                     // $price = $this->utilities->getRatecardPrice($r);
        //                     // $sessions = $r['duration'];
        //                     // $mem_ratecard = null;
        //                     // foreach($duration_session_map as $key => $value){
        //                     // 	if( $sessions > $value['low'] &&  $sessions <= $value['high']){
        //                     // 		if(!empty($memberships[strval($s['_id'])][$key])){
        //                     // 			foreach($memberships[strval($s['_id'])][$key] as $m){
        //                     // 				if(!empty($m['final_price']) && $m['final_price'] > $price){
        //                     // 					$mem_ratecard = $m;
        //                     // 					break;
        //                     // 				}
        //                     // 			}
        //                     // 		}
        //                     // 	}
        //                     // }
    
        //                     // if(empty($mem_ratecard)){
        //                     // 	continue;
        //                     // }
    
		// 					Log::info('extended ratecard:::::::::::::::::: ');
        //                     $getNonValidityBanner = $this->getNonValidityBanner();
        //                     // $mem_ratecard_duration_day = $this->utilities->getDurationDay($mem_ratecard);
        //                     // $mem_ratecard_price = $this->utilities->getRatecardPrice($mem_ratecard);
        //                     // $price = $this->utilities->getRatecardPrice($r);
		// 					$extended_validity_type = $this->getExtendedValidityType($r);
		// 					if($this->app_version > '5.1.7'){
		// 						$getNonValidityBanner['header'] = strtr($getNonValidityBanner['header'], ['vendor_name'=>($data['finder']['title'])]);	
		// 						$getNonValidityBanner['description'] = strtr($getNonValidityBanner['description'], ['vendor_name'=>($data['finder']['title'])]);
		// 						$r['data']  = $getNonValidityBanner;
		// 						$r['non_validity_ratecard_copy'] = $getNonValidityBanner;
		// 					}
		// 					else{
		// 						$getNonValidityBanner['description'] = strtr( $getNonValidityBanner['description'], [
		// 							// "__membership_price"=>$mem_ratecard_price,
		// 							// "__membership_months"=>$mem_ratecard_duration_day/30,
		// 							// "__extended_sessions_count"=>$r['duration'],
		// 							// "__extended_sessions_price"=>$price,
		// 							// "__sessions_validity_months"=>$r['ext_validity'],
		// 							"__vendor_name"=>$data['finder']['title'],
		// 							"__ext_validity_type"=> $extended_validity_type
		// 						]);
		
		// 						if(!empty($getNonValidityBanner['how_it_works'])){
		// 							$getNonValidityBanner['how_it_works']['description'] = strtr($getNonValidityBanner['how_it_works']['description'], ['__vendor_name'=>$data['finder']['title']]);
		// 						}
		
		// 						$getNonValidityBanner['title'] = strtr($getNonValidityBanner['title'], ['__ext_validity_type'=>($extended_validity_type)]);
		// 						if(!empty($getNonValidityBanner['title1'])){
		// 							$getNonValidityBanner['title1'] = strtr($getNonValidityBanner['title1'], ['__ext_validity_type'=>($extended_validity_type)]);
		// 						}
		// 						$r['non_validity_ratecard_copy'] = $getNonValidityBanner;
		// 						$getNonValidityBanner['description'] = $getNonValidityBanner['description'].Config::get('nonvalidity.how_works');
		// 						$getNonValidityBanner['description'] = strtr($getNonValidityBanner['description'], ['no_of_sessions'=>$r['duration']]);
		// 						$r['non_validity_ratecard']  = $getNonValidityBanner;
		// 					}
        //                     Log::info('non validity ratecard:::::::::::', [$r['non_validity_ratecard']]);
    
        //                 }
        //             }
        //     }
        // }catch(Exception $e){
            
        //     Log::info("Non validity description breaking", [$e]);
        
        // }

        // foreach($data['finder']['services'] as &$ser){
        //     foreach($ser[$ratecard_key] as &$rate_c){
		// 		if(!empty($ser['type']) && $ser['type'] == 'extended validity'){

		// 			if(!empty($ser['unlimited_validity'])){
	
		// 				if(!empty($rate_c['flags']['unlimited_validity']) && !empty($rate_c['non_validity_ratecard'])){
		// 					$ser['non_validity'] = $rate_c['non_validity_ratecard_copy'];
		// 					$ser['non_validity']['description'] = $ser['non_validity']['description'].Config::get('nonvalidity.service_footer');
		// 					break;
		// 				}
		// 			}else{
		// 				if(!empty($rate_c['non_validity_ratecard']) && !empty($ser['type'])){
		// 					$ser['non_validity'] = $rate_c['non_validity_ratecard_copy'];
		// 					$ser['non_validity']['description'] = $ser['non_validity']['description'].Config::get('nonvalidity.service_footer');
		// 					break;
		// 				}
		// 			}
		// 		}
				
                
        //     }
        // }

        // foreach($data['finder']['services'] as &$ser1){
        //     foreach($ser1[$ratecard_key] as &$rate_c1){
        //         if(!empty($ser1['type']) && $ser1['type'] == 'extended validity'){
    	// 			unset($rate_c1['non_validity_ratecard']);
		// 		}
		// 		unset($rate_c1['non_validity_ratecard_copy']);
        //     }
		// }
		$getExtendedValidityBanner = $this->getExtendedValidityBanner();
		foreach($data['finder']['services'] as &$extended){
			foreach($extended['ratecard'] as &$ratecards){
				if(!empty($ratecards['studio_extended_validity'])){
					$ratecards['type'] = 'studio_extended_validity';
					$ratecards['pps_title'] = "Flexi Membership";
					$ratecards['popup_data'] = $getExtendedValidityBanner;		
				}
			}
		}

		foreach($data['finder']['services'] as &$service){
			$service = $this->addingRemarkToDuplicate($service, 'app');
		}

		$data['finder']['services'] = $this->orderSummary($data['finder']['services'], $data['finder']['title'],$data['finder']);
		//updating duration name for extended validity ratecards
		foreach($data['finder']['services'] as &$service){
			foreach($service[$ratecard_key] as $key1=>&$ratecard){
				if($ratecard['type'] == 'extended validity'){
					//Log::info('inside setting ext_validity');
					if(!empty($ratecard['flags']['unlimited_validity'])){
						$ratecard['ext_validity'] = "Unlimited Validity";
					}else{
						$ratecard['ext_validity']  = "Valid for ".$service[$ratecard_key][$key1]['validity'].' '.$service[$ratecard_key][$key1]['validity_type'];
					}
					$ratecard['duration_type'] = $ratecard['duration_type']."\n (". $ratecard['ext_validity']. ")";
					$ratecard['validity_copy'] = $ratecard['validity'];
					$ratecard['validity_type_copy'] = $ratecard['validity_type'];
					unset($ratecard['validity_type'] );
					$ratecard['validity']= 0;
				}
			}
		}

        return $data['finder'];
    }

    public function getNonValidityBanner(){
		Log::info('values:::::::', [$this->device_type, $this->app_version]);
        if(in_array($this->device_type, ['android', 'ios'])){
			if(($this->device_type=='ios' &&$this->app_version > '5.1.7') || ($this->device_type=='android' &&$this->app_version > '5.23')){
				Log::info('values:::::::', [$this->device_type, $this->app_version]);
				return Config::get('nonvalidity.finder_banner_app_data');
			}
			else{
				return Config::get('nonvalidity.finder_banner_app');
			}
        }else{
            return Config::get('nonvalidity.finder_banner');
        }
    }

    public function applyTopService($data, $source = 'web'){
        
        $ratecard_key = 'ratecard';
		$service_name_key = 'service_name';

		if($source != 'app'){
			$ratecard_key = 'serviceratecard';
			$service_name_key = 'name';
		}


        $pushed_rc = [];

        $service = null;

        foreach($data['finder']['services'] as $ser){
            foreach($ser[$ratecard_key] as $rc){
                if((empty($ser['type']) || $ser['type'] != "extended validity") && !empty($rc['flags']['top_service']) && !in_array($rc['_id'], $pushed_rc)){
                    if(empty($service)){
                        $service = $ser;
                        $service[$service_name_key] = "New year offer - best price of the year";
                        $service['_id'] = 100000;
                        $service['top_service'] = true;

                        if(!empty($_GET['device_type']) && in_array($_GET['device_type'], ['android', 'ios'])){
                            
							$service['extra_info'][0] = [
                                'title'=>'Description',
                                'icon'=>'https://b.fitn.in/iconsv1/vendor-page/form.png',
                                'description'=> "<p>We have curated the best offers for you to kickstart a fit 2019 at ".$data['finder']['title'].". These are exclusively available on Fitternity for a limited period.</p>"
                            ];
                        
						}else{
                            
							$service['short_description'] = "<p>We have curated the best offers for you to kickstart a fit 2019 at ".$data['finder']['title'].". These are exclusively available on Fitternity for a limited period.</p>";

                        }


                        $service[$ratecard_key] = [];
                        if(!empty($service['batches'])){
                            unset($service['batches']);
                        }
                        $service['recommended'] = Config::get('nonvalidity.recommnded_block');
                    }

                    array_push($service[$ratecard_key], $rc);
                }
            }
		}
		
		if(!empty($service)){
			array_unshift($data['finder']['services'], $service);
		}

        return $data['finder'];
    
    }

    public function insertWSNonValidtiy(&$data, $source = null){
        
        $ratecard_key = 'ratecard';
		$service_name_key = 'service_name';

		if($source != 'app'){
			$ratecard_key = 'serviceratecard';
		}

        $services = $data['finder']['services'];
        $sessions = [];
        foreach($services as $service){
            if(!(!empty($service['type']) && $service['type'] == 'extended validity')){
                foreach($service[$ratecard_key] as $ratecard){
                    if($ratecard['type'] == 'workout session'){
                        if(empty($sessions[$service['_id']])){
                            $sessions[$service['_id']] = [];
                        }
                        array_push( $sessions[$service['_id']], $ratecard);
                    }
                }
            }
        }

        foreach($services as &$service){
            if(!empty($service['type']) && $service['type'] == 'extended validity' && !empty($sessions[$service['_id']])){
                $service[$ratecard_key] = array_merge($sessions[$service['_id']], $service[$ratecard_key]);
            }
        }

        $data['finder']['services'] = $services;
    }

    public function insertWSRatecardTopService(&$data, $cheapest_price, $source = null){
        $ratecard_key = 'ratecard';
		$service_name_key = 'service_name';

		if($source != 'app'){
			$ratecard_key = 'serviceratecard';
		}

        $services = $data['finder']['services'];


        foreach($services as &$service){
            if(!empty($service['top_service'])){

                $ws_rc = [
                    "type"=>'workout session',
                    "top_service"=>true,
                    "price"=>$cheapest_price,
                    "special_price"=>0,
                    "remarks"=>"Book multiple sessions at this price",
                    "_id"=> 1,
                    "finder_id"=> 1,
                    "direct_payment_enable"=> "1",
                    "order"=> 1,
                    "duration"=> 1,
                    "duration_type"=> "session",
                    "offers"=> [],
					"validity"=> 0,
                    "duration_type"=> "",
                    "button_color"=> Config::get('app.ratecard_button_color'),
                    'pps_know_more'=>true
                ];


                array_unshift($service[$ratecard_key], $ws_rc);
                break;
            }
        }
        $data['finder']['services'] = $services;
	} 

	public function removeNonValidity(&$data, $source = null){
		$ratecard_key = 'ratecard';
		$service_name_key = 'service_name';

		if($source != 'app'){
			$ratecard_key = 'serviceratecard';
		}

		$services = $data['finder']['services'];
		
		foreach($services as $key => $value){
			$ratecards = [];
			foreach($value[$ratecard_key] as $rate_key => $ratecard){
				if($ratecard['type'] == 'extended validity' && empty($value['type']) && empty($value['top_service'])){
					$ratecard['hidden'] = true;
				}
				array_push($ratecards, $ratecard);
			}
			$services[$key][$ratecard_key] = $ratecards;
		}

		$data['finder']['services'] = $services;
	}
	
	public function addNonValidityLink(&$data){
		foreach($data['finder']['services'] as &$service){
			if(empty($service['type']) && empty($service['top_service'])){

				foreach($service['serviceratecard'] as $ratecard){
					if($ratecard['type'] == 'extended validity'){
						$service['non_validity_link'] = [
							"text" => "Check out Unlimited Validity Memberships available on this service",
							"service_id"=>$service['_id']
						];
						break;
					}
				}
			}
		}
	}
    
    public function getVendorStripeCashbackText($finder){
        
		if(empty($finder['flags']['reward_type'])){
			$finder['flags']['reward_type'] = 1;
        }
        if(empty($finder['flags']['cashback_type'])){
            $finder['flags']['cashback_type'] = 0;
        }
        $cashback = 100;
        switch($finder['flags']['cashback_type']){
            case 1:
            case 2:
                $cashback = 120;
        }
        $msg = "";
        switch($finder['flags']['reward_type']){
			case 1:
            break;
            case 2:
            break;
            case 3:
			$msg = "BEST OFFER : GET ".$cashback."% CASHBACK & INSTANT REWARDS";
            break;
            case 4:
            case 6:
			$msg = "BEST OFFER : GET ".$cashback."% CASHBACK & REWARDS WORTH RS 20,000";
            break;
            case 5:
                $msg  = "BEST OFFER : GET ".$cashback."% CASHBACK ON MEMBERSHIP AMOUNT";
            break;
        }

        return $msg;

    }

    public function applyFreeSP(&$data){
        
		$free_sp_rc_all = $this->utilities->getFreeSPRatecardsByFinder(['finder_id'=>$data['finder']['_id']]);

        if(!empty($free_sp_rc_all)){
            foreach($data['finder']['services'] as &$service){
                foreach($service['serviceratecard'] as &$ratecard){
					$free_sp_rc = $this->utilities->getFreeSPRatecard($ratecard,'ratecard',$free_sp_rc_all);
                    if(!empty($free_sp_rc)){
                        // return $free_sp_rc;
                        $ratecard['special_offer'] = true;
                        $finder_special_offer = true;

                    }
                }
            }
        }

        $data['finder']['special_offer'] = !empty($finder_special_offer);

	}
	
	public function testOffer(){
		Log::info("in Test");

		$field_name = 'ratecard_id';
		$field_value = $rate_card = 72855;
		$finder_id = 40;

		// return Service::where('finder_id',$finder_id)->active()
		// 	->with('serviceratecard')
		// 	->latest()
		// 	->get();
		// 	exit();

		// return DB::connection('mongodb2')->table('offers')
		// ->where($field_name, intval($field_value))->where('hidden', false)->orderBy('order', 'asc')
		// ->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
		// //->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
        // ->join('finders', function($join)
        // {
        //     $join->on('offers.vendor_id', '=', 'finders._id');
        // })
		// ->get();

		// Log::info("qe  ::  ",[ DB::connection('mongodb2')->getQueryLog()]);
		// exit();

		
		$ratecardoffersRecards = Offer::getActiveV1('ratecard_id', intval($field_value), intval($finder_id))->toArray();
		Log::info("qe  ::  ",[ DB::connection('mongodb2')->getQueryLog()]);
		return $ratecardoffersRecards;
		// Log::info("query :     ",[DB::getQueryLog()]);
		// print_r($ratecardoffersRecards);
		// Log::info("count   ::   ", [sizeof($ratecardoffersRecards)] );
		// foreach($ratecardoffersRecards as $v){
		// 	print_r($v);
		// }
		//print_r($re);

		

		// $ratecardoffersRecards  =   Offer::where('ratecard_id', intval($field_value))->where('hidden', false)
		// 						->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
		// 						->where(function($q) {
		// 							$query->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
		// 								->orWhere(function ($query1){
		// 									$query1->with(['finder' => function($query2){$query2->where('flags.gmv1',true);}]);
		// 								});
		// 						})
		// 						->orderBy('order', 'asc')
		// 						->get(['start_date','end_date','price','type','allowed_qty','remarks'])
		// 						->toArray();
								//print_r($ratecardoffersRecards);

								
	}
    
    public function applyFitsquadSection(&$data){

        $data['fitsquad'] = [
			'image' => null,
			'header' => null,
			'imageText' => null,
			'title' => null,
			'subtitle' => null,
			'description' => null,
			'reward_title' => null,
			'reward_images' => null,
			'buy_button' => null,
			'know_more_button' => null,
			'checkout_button' => null
		];
		
		// $brandsList = [135,88,166,56,40];
		$brandsList = [135,166,56,40];
		// $nonHanmanBrandsList = [135,88,166,40];
		$nonHanmanBrandsList = [135,166,40];
		// $brandsMap = ['golds' => 135, 'multifit' => 88, 'shivfit' => 166, 'hanman' => 56, 'hype' => 40];
		$brandsMap = ['golds' => 135, 'shivfit' => 166, 'hanman' => 56, 'hype' => 40];
		$finderDetails = $data['finder'];
		$finderRewardType = (!empty($data['finder']['flags']['reward_type']))?$data['finder']['flags']['reward_type']:2;
		$finderCashbackType =(!empty($data['finder']['flags']['cashback_type']))?$data['finder']['flags']['cashback_type']:null;
		$fitsquadHeader = 'Become a member of Fitsquad';
		$fitsquadLogo = 'http://b.fitn.in/global/pps/fitsquadlogo.png';
		$powByFit = 'POWERED BY FITTERNITY';
		$fitsquadTitle = "TO GET EXCLUSIVE ACCESS TO INDIA'S BIGGEST REWARDS CLUB FITSQUAD, BUY / RENEW YOUR MEMBERSHIP NOW";

		$thumbsUpImage = 'https://b.fitn.in/global/web%20payment%20page%20thumbs%20up%20icon%403x.png';
		$thumbsUpBackImage = 'https://b.fitn.in/global/web%20payment%20page%20background_app%403x.png';
		if(in_array($this->device_type, ['android', 'ios'])) {
			$thumbsUpBackImage = "https://b.fitn.in/mobile_checkout_background.png"; 
		}
		// $brandIdCheck = in_array($finderDetails['brand_id'], $nonHanmanBrandsList) && !in_array($finderDetails['brand_id'], Config::get('app.brand_finder_without_loyalty')) && in_array($finderRewardType, [2]);
		// $brandIdHanmanCheck = in_array($finderDetails['brand_id'], [56]) && !in_array($finderDetails['brand_id'], Config::get('app.brand_finder_without_loyalty')) && in_array($finderRewardType, [6]);
		if((!empty($finderDetails['brand_id'])) && in_array($finderDetails['brand_id'], $brandsList) && !in_array($finderDetails['_id'], Config::get('app.brand_finder_without_loyalty'))){
			// fitsquad
			$data['fitsquad']['image'] = $fitsquadLogo;
			$data['fitsquad']['header'] = $fitsquadHeader;
			$data['fitsquad']['imageText'] = null;
			$data['fitsquad']['title'] = $fitsquadTitle;
			//$data['fitsquad']['reward_title'] = "Proud Reward Partners";
			$data['fitsquad']['reward_images'] = [];
			$data['fitsquad']['checkout_button'] = [
				'text'=>'KNOW MORE',
				'image'=>null
			];

			$data['fitsquad']['checkout_summary'] = [
				'image' => $thumbsUpImage,
				'back_image' => $thumbsUpBackImage,
				'line1' => 'On buying this, you get exclusive access into FitSquad',
				'line2' => 'India\'s Biggest Fitness Rewards club',
				'checkout_button' =>[
					'text' => 'Checkout Rewards',
					'image' => ''
				],
				'know_more' => true
			];

			if($brandsMap['golds']==$finderDetails['brand_id']){
				$data['fitsquad']['image'] = "https://b.fitn.in/global/fitsquad%20-%20gold%20-%20vendor%20page%20%281%29.png";
				$data['fitsquad']['imageText'] = $powByFit;
				$data['fitsquad']['title'] = "GET 100% CASHBACK + REWARDS";
				$data['fitsquad']['subtitle'] = 'Buy/Renew a membership at '.$finderDetails['title'].' & earn ₹20,000 worth of rewards.';
				// $data['fitsquad']['description'] = 'GET EXCITING REWARDS ON ACHIEVING MILESTONES OF 10, 45, 75, 150, 225 WORKOUTS';
				//$data['fitsquad']['description'] = "<span>GET EXCITING REWARDS ON ACHIEVING MILESTONES OF <span style='color: #f7a81e'>10, 45, 75, 150, 225</span> WORKOUTS";
				
				// $data['fitsquad']['reward_images'] = [
				// 	"https://b.fitn.in/global/uber.jpg"
				// ];
				// $data['fitsquad']['checkout_button']['image'] = 'https://b.fitn.in/global/POP-UP-DESIGN-.jpg';
				$data['fitsquad']['checkout_button']['image'] = $this->utilities->openrewardlist('1', $finderDetails['brand_id'], $finderDetails['city_name']);
				$data['fitsquad']['checkout_summary']['line1'] = 'On buying this, you get exclusive access into FitSquad Gold';
			}
			// else if($brandsMap['multifit']==$finderDetails['brand_id']) {
			// 	$data['fitsquad']['image'] = "https://b.fitn.in/global/MULTIFIT-LOGO-VENDOR-PAGE.png";
			// 	$data['fitsquad']['title'] = "GET 120% CASHBACK + REWARDS";
			// 	$data['fitsquad']['subtitle'] = 'Buy/Renew a membership at '.$finderDetails['title'].' & earn ₹35,000 worth of rewards.';
			// 	//$data['fitsquad']['description'] = "<span>Just Workout for <span style='color: #f7a81e'>10, 45, 75, 150, 225</span> Days & Earn Rewards Worth of ₹35,000";
			// 	// $data['fitsquad']['checkout_button']['image'] = 'https://b.fitn.in/global/multifit---grid---final%20%282%29.jpg';
			// 	$data['fitsquad']['checkout_button']['image'] = $this->utilities->openrewardlist('1', $finderDetails['brand_id'], $finderDetails['city_name']);
			// }
			else if($brandsMap['shivfit']==$finderDetails['brand_id']) {
				$data['fitsquad']['image'] = "https://b.fitn.in/global/SHIVFIT-LOGO---VENDOR-PAGE.png";
				$data['fitsquad']['title'] = "TO GET EXCLUSIVE ACCESS TO FITSQUAD SHIVFIT BUY / RENEW YOUR MEMBERSHIP AT ".strtoupper($finderDetails['title']);
				//$data['fitsquad']['description'] = 'Just Workout for 10, 45, 75, 150, 225 Days & Earn Rewards Worth of ₹35,000';
				//$data['fitsquad']['description'] = "<span>Just Workout for <span style='color: #f7a81e'>10, 45, 75, 150, 225</span> Days & Earn Rewards Worth of ₹35,000";
				// $data['fitsquad']['checkout_button']['image'] = 'https://b.fitn.in/global/shivfit---grids-new.jpg';
				$data['fitsquad']['checkout_button']['image'] = $this->utilities->openrewardlist('1', $finderDetails['brand_id'], $finderDetails['city_name']);
			}
			else if($brandsMap['hanman']==$finderDetails['brand_id']) {
				$data['fitsquad']['image'] = "http://b.fitn.in/global/pps/fitsquadlogo.png";
				$data['fitsquad']['title'] = "GET 100% CASHBACK + REWARDS";
				$data['fitsquad']['subtitle'] = 'Buy/Renew a membership at '.$finderDetails['title'].' & earn ₹20,000 worth of rewards.';
				// $data['fitsquad']['description'] = 'GET EXCITING REWARDS ON ACHIEVING MILESTONES OF 10, 45, 75, 150, 250 WORKOUTS';
				//$data['fitsquad']['description'] = "<span>GET EXCITING REWARDS ON ACHIEVING MILESTONES OF <span style='color: #f7a81e'>10, 45, 75, 150, 250</span> WORKOUTS";
				// $data['fitsquad']['reward_images'] = [
				// 	"https://b.fitn.in/global/cashback/rewards/UberEats-Logo-OnWhite-Color-H.png"
				// ];
				// $data['fitsquad']['checkout_button']['image'] = 'https://b.fitn.in/hanman/download2.jpeg';
				$data['fitsquad']['checkout_button']['image'] = $this->utilities->openrewardlist('1', $finderDetails['brand_id'], $finderDetails['city_name']);
			}
			else if($brandsMap['hype']==$finderDetails['brand_id']) {
				$data['fitsquad']['image'] = "https://b.fitn.in/global/fitsquad-hype-logo.png";
				$data['fitsquad']['title'] = "GET 100% CASHBACK + REWARDS";
				$data['fitsquad']['subtitle'] = 'Buy/Renew a membership at '.$finderDetails['title'].' & earn ₹35,000 worth of rewards.';
				$data['fitsquad']['checkout_button']['image'] = $this->utilities->openrewardlist('1', $finderDetails['brand_id'], $finderDetails['city_name']);
			}

			$data['fitsquad']['checkout_summary']['checkout_button']['image'] = $data['fitsquad']['checkout_button']['image'];

			// array_push($data['fitsquad']['reward_images'], "https://b.fitn.in/loyalty/vouchers3/ZOMATO.png");
			// array_push($data['fitsquad']['reward_images'], "https://b.fitn.in/external-vouchers1/gnc.png");
			// array_push($data['fitsquad']['reward_images'], "https://b.fitn.in/external-vouchers1/cleartrip.png");

		}
		else {
			if($finderRewardType==2) {
				// fitsquad
				$data['fitsquad']['image'] = $fitsquadLogo;
				$data['fitsquad']['header'] = $fitsquadHeader;
				$data['fitsquad']['imageText'] = $powByFit;
				$data['fitsquad']['title'] = $fitsquadTitle;

				// $data['fitsquad']['description'] = "Just Workout for 10, 30, 75, 150, 225 Days & Earn Rs. 25,000 Worth of Rewards";

				//$data['fitsquad']['reward_title'] = "Proud Reward Partners";
				// $data['fitsquad']['reward_images'] = [
				// 	"https://b.fitn.in/loyalty/vouchers3/ZOMATO.png",
				// 	"https://b.fitn.in/global/uber.jpg",
				// 	"https://b.fitn.in/external-vouchers1/book%20my%20show.png",
				// 	"https://b.fitn.in/external-vouchers1/cleartrip.png",
				// 	"https://b.fitn.in/loyalty/vouchers3/AMAZON.png",
				// 	"https://b.fitn.in/external-vouchers1/JCB.png"
				// ];

				$data['fitsquad']['know_more_button'] = [
					'text'=>'KNOW MORE',
					'popup'=>[
						'image'=>'https://b.fitn.in/global/FitSquad%20logo%20transparent%403x.png',
						'background'=>'https://b.fitn.in/global/banner%20image.png',
						'header'=>[
							'line1'=>"India's Biggest Rewards Club",
							'line2'=>"Get Rewards Upto Rs 25,000 For Working Out!",
							'line3'=>"Burn More, Earn More"
						],
						'title'=>"Workout at ".$finderDetails['title']." by buying a membership or booking a session & get rewarded in 3 easy steps",
						'steps'=>[
							"1. Check-in every time you workout",
							"2. Workout more and level up",
							"3. Earn rewards worth Rs.25,000",
						],
						// 'steps_image' => 'https://b.fitn.in/global/Group%20770%403x.png',
						// 'steps_desc'=>[
						// 	"Check-in at ".$finderDetails['title']." by scanning the QR code through the app",
						// 	"Reach easily achievable Fitness Milestones",
						// 	"Exciting Rewards from Best Brands in country",
						// ],
						//'reward_title'=>'Proud Reward Partners',
						'reward_images'=>[
							"https://b.fitn.in/loyalty/vouchers3/AMAZON.png",
							"https://b.fitn.in/loyalty/vouchers3/ZOMATO.png",
							"https://b.fitn.in/external-vouchers1/JCB.png",
							"https://b.fitn.in/external-vouchers1/epigamia.png",
							"https://b.fitn.in/external-vouchers1/cleartrip.png",
							"https://b.fitn.in/external-vouchers1/o2.png",
							"https://b.fitn.in/external-vouchers1/book%20my%20show.png",
						],
						'post_image_text'=>'And Many More'
					]
				];

				$data['fitsquad']['checkout_button'] = [
					'text'=>"Checkout Rewards",
					// "image"=>'https://b.fitn.in/global/cashback/rewards/fitternity-new-rewards-all-cities.jpg'
					'image' => 'https://b.fitn.in/global/Homepage-branding-2018/srp/Edited%20Fitsquad%20Grid%20%281%29.jpg'
				];

				$data['checkout_summary'] = [
					'image' => $thumbsUpImage,
					'back_image' => $thumbsUpBackImage,
					'line1' => 'On buying this, you get exclusive access into FitSquad',
					'line2' => 'India\'s Biggest Fitness Rewards club',
					'checkout_button' =>[
						'text' => 'Checkout Rewards',
						// 'image' => 'https://b.fitn.in/global/cashback/rewards/fitternity-new-rewards-all-cities.jpg'
						'image' => 'https://b.fitn.in/global/Homepage-branding-2018/srp/Edited%20Fitsquad%20Grid%20%281%29.jpg'
					],
					'know_more' => true
				];

			} else if($finderRewardType==3){
				$data['fitsquad']['image'] = $fitsquadLogo;
				$data['fitsquad']['header'] = $fitsquadHeader;
				$data['fitsquad']['imageText'] = $powByFit;
				$cashbackImageMap = [
					0 => ["image" => "https://b.fitn.in/global/cashback/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%201.png", "cashback_rate" => "100%", "cashback_days" => "250, 275, 300"],
					1 => ["image" => "https://b.fitn.in/global/cashback/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%201.png", "cashback_rate" => "120%", "cashback_days" => "250, 275, 300"],
					2 => ["image" => "https://b.fitn.in/global/cashback/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%202.png", "cashback_rate" => "120%", "cashback_days" => "250, 275, 300"],				
					3 => ["image" => "https://b.fitn.in/global/cashback/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%203.png", "cashback_rate" => "100%", "cashback_days" => "250, 275, 300"],				
					4 => ["image" => "", "cashback_rate" => "100%", "cashback_days" => "250"],				
					5 => ["image" => "", "cashback_rate" => "100%", "cashback_days" => "275"],				
					6 => ["image" => "", "cashback_rate" => "100%", "cashback_days" => "300"]
				];

				$cashbackRate = (isset($finderDetails['flags']['cashback_type']))?$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_rate']:"";
				$cashbackDays = (isset($finderDetails['flags']['cashback_type']))?$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_days']:"";

				$data['fitsquad']['title'] = "Get instant complimentary rewards  ".$cashbackRate. " cashback";
				// $data['fitsquad']['title_2'] = "+ ".$cashbackRate." cashback";
				//$data['fitsquad']['title'] = "<span style='text-align: center;font-weight: 900;'><span style='color: #f7a81e;'>Get instant complimentary rewards</span><br/><span>+ ".$cashbackRate." cashback</span></span>";
				$data['fitsquad']['subtitle'] = "Workout & earn ".$cashbackRate." cashback on your membership amount";
				//$data['fitsquad']['description'] = "BUY A MEMBERSHIP THROUGH FITTERNITY & GET EXCLUSIVE ACCESS TO INSTANT REWARDS + ".$cashbackRate." CASHBACK ON ACHIEVING MILESTONES OF ".$cashbackDays." WORKOUTS";
				//$data['fitsquad']['description'] = "<span>BUY A MEMBERSHIP THROUGH FITTERNITY & GET EXCLUSIVE ACCESS TO INSTANT REWARDS + ".$cashbackRate." CASHBACK ON ACHIEVING MILESTONES OF <span style='color: #f7a81e;font-weight: 900;'>".$cashbackDays."</span> WORKOUTS</span>";

				$jumpToService = null;
				$jumpToRatecard = null;
				$services = $finderDetails['services'];
				for($i=0;$i<count($services);$i++){
					$ratecards = $services[$i]['ratecard'];
					for($j=0;$j<count($ratecards);$j++){
						if($ratecards[$j]['validity'] == 1 && $ratecards[$j]['validity_type'] == 'year' && (empty($services[$i]['type']) || ($services[$i]['type'] != 'extended validity'))) {
							$jumpToService = $services[$i]['_id'];
							$jumpToRatecard = $ratecards[$j]['_id'];
							break;
						}
					}
					if(!empty($jumpToService)){
						break;
					}
				}

				$data['fitsquad']['checkout_summary'] = [
					'image' => $thumbsUpImage,
					'back_image' => $thumbsUpBackImage,
					'line1' => 'On buying this you get exclusive access to earn '.$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_rate'].' cashback on your membership amount.',
					'line2' => null,
					'checkout_button' => null,
					'know_more' => false
				];

				if(!in_array($finderDetails['flags']['cashback_type'], [4,5,6])){
					if(!empty($jumpToService) && !empty($jumpToRatecard)){
						$data['fitsquad']['buy_button'] = [
							'text' => 'BUY NOW',
							'service_id' => $jumpToService,
							'ratecard_id' => $jumpToRatecard
						];
					}
					$data['fitsquad']['checkout_button'] = [
						'text'=>'KNOW MORE',
						'image'=>$cashbackImageMap[$finderDetails['flags']['cashback_type']]['image']
					];

					$data['fitsquad']['checkout_summary']['checkout_button'] = [
						'text' => 'KNOW MORE',
						'image' => $cashbackImageMap[$finderDetails['flags']['cashback_type']]['image']
					];

				}

				

			} else if(in_array($finderRewardType, [4, 6])){
				// fitsquad
				$data['fitsquad']['image'] = $fitsquadLogo;
				$data['fitsquad']['header'] = $fitsquadHeader;
				$data['fitsquad']['imageText'] = $powByFit;
				$cashbackImageMap = [
					0 => ["image" => "https://b.fitn.in/global/cashback/rewards/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%203A1.png", "cashback_rate" => "100%", "cashback_days" => "250, 275, 300"],
					1 => ["image" => "https://b.fitn.in/global/cashback/rewards/120%25%20cash%20back%20%2B%20rewards%20type%20A2.png", "cashback_rate" => "120%", "cashback_days" => "10, 30, 75, 150, 250, 275, 300"],
					2 => ["image" => "https://b.fitn.in/global/cashback/rewards/120%25%20cash%20back%20%2B%20rewards%20type%20B2.png", "cashback_rate" => "120%", "cashback_days" => "10, 30, 75, 150, 250, 275, 300"],				
					3 => ["image" => "https://b.fitn.in/global/cashback/rewards/100%25%20cash%20back%20%2B%20rewards%20type%20C2.png", "cashback_rate" => "100%", "cashback_days" => "10, 30, 75, 150, 250, 275, 300"],				
					4 => ["image" => "https://b.fitn.in/global/cashback/rewards/100%25%20cash%20back%20%2B%20rewards%20type%20D2.png", "cashback_rate" => "100%", "cashback_days" => "10, 30, 75, 150, 250"],				
					5 => ["image" => "https://b.fitn.in/global/cashback/rewards/100%25%20cash%20back%20%2B%20rewards%20type%20E2.png", "cashback_rate" => "100%", "cashback_days" => "10, 30, 75, 150, 275"],				
					6 => ["image" => "https://b.fitn.in/global/cashback/rewards/100%25%20cash%20back%20%2B%20rewards%20type%20F2.png", "cashback_rate" => "100%", "cashback_days" => "10, 30, 75, 150, 300"]
				];

				$cashbackRate = (isset($finderDetails['flags']['cashback_type']))?$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_rate']:"";
				$cashbackDays = (isset($finderDetails['flags']['cashback_type']))?$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_days']:"";
				$data['fitsquad']['title'] = "Get ".$cashbackRate." Cashback + Exciting Rewards";
				//$data['fitsquad']['title'] = "<span style='text-align: center;font-weight: 900;'>Get ".$cashbackRate." cashback + Exciting Rewards</span>";
				$data['fitsquad']['subtitle'] = 'Buy/Renew a membership at '.$finderDetails['title'].' & earn ₹20,000 worth of rewards';
				// $data['fitsquad']['description_title'] = "Buy a membership through Fitternity &";
				//$data['fitsquad']['description'] = 'GET EXCLUSIVE ACCESS TO '.$cashbackRate." CASHBACK + EXCITING REWARDS WORTH ₹20,000 ON ACHIEVING MILESTONES OF ".$cashbackDays. "WORKOUTS";
				//$data['fitsquad']['description'] = '<span><span style="font-weight: 500">Buy a membership through Fitternity &</span><br/><span>GET EXCLUSIVE ACCESS TO '.$cashbackRate." CASHBACK + EXCITING REWARDS WORTH ₹20,000 ON ACHIEVING MILESTONES OF <span style='text-align: center;'>".$cashbackDays."</span></span> WORKOUTS</span>";

				$jumpToService = null;
				$jumpToRatecard = null;
				$services = $finderDetails['services'];
				for($i=0;$i<count($services);$i++){
					$ratecards = $services[$i]['ratecard'];
					for($j=0;$j<count($ratecards);$j++){
						if($ratecards[$j]['validity'] == 1 && $ratecards[$j]['validity_type'] == 'year' && (empty($services[$i]['type']) || ($services[$i]['type'] != 'extended validity'))) {
							$jumpToService = $services[$i]['_id'];
							$jumpToRatecard = $ratecards[$j]['_id'];
							break;
						}
					}
					if(!empty($jumpToService)){
						break;
					}
				}

				//$data['fitsquad']['reward_title'] = "Proud Reward Partners";
				// $data['fitsquad']['reward_images'] = [
				// 	"https://b.fitn.in/global/cashback/rewards/UberEats-Logo-OnWhite-Color-H.png",
				// 	"https://b.fitn.in/global/amazon-logo-vendor-page.png",
				// 	"https://b.fitn.in/external-vouchers1/gnc.png",
				// 	"https://b.fitn.in/external-vouchers1/cleartrip.png"
				// ];


				if(!empty($jumpToService) && !empty($jumpToRatecard)){
					$data['fitsquad']['buy_button'] = [
						'text' => 'BUY NOW',
						'service_id' => $jumpToService,
						'ratecard_id' => $jumpToRatecard
					];
				}
				$data['fitsquad']['checkout_button'] = [
					'text'=>'KNOW MORE',
					'image'=>$cashbackImageMap[$finderDetails['flags']['cashback_type']]['image']
				];

				$data['fitsquad']['checkout_summary'] = [
					'image' => $thumbsUpImage,
					'back_image' => $thumbsUpBackImage,
					'line1' => 'On buying this you get exclusive access to earn '.$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_rate'].' cashback on your membership amount.',
					'line2' => null,
					'checkout_button' => [
						'text' => 'KNOW MORE',
						'image' => $cashbackImageMap[$finderDetails['flags']['cashback_type']]['image']
					],
					'know_more' => false
				];

			} else if(in_array($finderRewardType, [5])){
				$data['fitsquad']['image'] = $fitsquadLogo;
				$data['fitsquad']['header'] = $fitsquadHeader;
				$data['fitsquad']['imageText'] = $powByFit;
				$cashbackImageMap = [
					0 => ["image" => "https://b.fitn.in/global/cashback/rewards/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%203A1.png", "cashback_rate" => "100%", "cashback_days" => "250, 275, 300"],
					1 => ["image" => "https://b.fitn.in/global/cashback/rewards/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%201A.png", "cashback_rate" => "120%", "cashback_days" => "250, 275, 300"],
					2 => ["image" => "https://b.fitn.in/global/cashback/rewards/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%202A.png", "cashback_rate" => "120%", "cashback_days" => "250, 275, 300"],				
					3 => ["image" => "https://b.fitn.in/global/cashback/rewards/120%25%20cash%20back%20%2B%20instant%20assured%20rewards%20grid%203A.png", "cashback_rate" => "100%", "cashback_days" => "250, 275, 300"],				
					4 => ["image" => "", "cashback_rate" => "100%", "cashback_days" => "250"],				
					5 => ["image" => "", "cashback_rate" => "100%", "cashback_days" => "275"],				
					6 => ["image" => "", "cashback_rate" => "100%", "cashback_days" => "300"]
				];

				$cashbackRate = (isset($finderDetails['flags']['cashback_type']))?$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_rate']:"100%";
				$cashbackDays = (isset($finderDetails['flags']['cashback_type']))?$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_days']:"250, 275, 300";

				$data['fitsquad']['title'] = "Get ".$cashbackRate." cashback";
				//$data['fitsquad']['title'] = "<span style='color: #f7a81e;font-weight: 900;'>GET ".$cashbackRate." CASHBACK</span>";
				
				//$data['fitsquad']['description'] = "Buy a membership through Fitternity & GET EXCLUSIVE ACCESS TO ".$cashbackRate." CASHBACK ON ACHIEVING MILESTONES OF ".$cashbackDays." WORKOUTS";
				//$data['fitsquad']['description'] = "<span>BUY A MEMBERSHIP THROUGH FITTERNITY & GET EXCLUSIVE ACCESS TO ".$cashbackRate." CASHBACK ON ACHIEVING MILESTONES OF <span style='color: #f7a81e;'>".$cashbackDays."</span> WORKOUTS</span>";

				$jumpToService = null;
				$jumpToRatecard = null;
				$services = $finderDetails['services'];
				for($i=0;$i<count($services);$i++){
					$ratecards = $services[$i]['ratecard'];
					for($j=0;$j<count($ratecards);$j++){
						if($ratecards[$j]['validity'] == 1 && $ratecards[$j]['validity_type'] == 'year' && (empty($services[$i]['type']) || ($services[$i]['type'] != 'extended validity'))) {
							$jumpToService = $services[$i]['_id'];
							$jumpToRatecard = $ratecards[$j]['_id'];
							break;
						}
					}
					if(!empty($jumpToService)){
						break;
					}
				}
				
				$data['fitsquad']['checkout_summary'] = [
					'image' => $thumbsUpImage,
					'back_image' => $thumbsUpBackImage,
					'line1' => 'On buying this you get exclusive access to earn '.$cashbackImageMap[$finderDetails['flags']['cashback_type']]['cashback_rate'].' cashback on your membership amount.',
					'line2' => null,
					'checkout_button' => null,
					'know_more' => false
				];

				if(!in_array($finderDetails['flags']['cashback_type'], [4,5,6])){
					if(!empty($jumpToService) && !empty($jumpToRatecard)){
						$data['fitsquad']['buy_button'] = [
							'text' => 'BUY NOW',
							'service_id' => $jumpToService,
							'ratecard_id' => $jumpToRatecard
						];
					}
					$data['fitsquad']['checkout_button'] = [
						'text'=>'KNOW MORE',
						'image'=>$cashbackImageMap[$finderDetails['flags']['cashback_type']]['image']
					];

					$data['fitsquad']['checkout_summary']['checkout_button'] = [
						'text' => 'KNOW MORE',
						'image' => $cashbackImageMap[$finderDetails['flags']['cashback_type']]['image']
					];
				}
			} else if(!empty($data['fitsquad'])) {
				unset($data['fitsquad']);
			}
        }
    }
    /**
     * @param $service
     * @param $ratecard_key
     * @param $no_validity_ratecards
     * @param $ratecard
     * @param $duration_day
     * @param $no_validity_exists
     * @param $no_validity_ratecards_all
     */
    public function extractNonValidityRatecards(&$service, $ratecard_key, &$no_validity_ratecards, &$ratecard, &$duration_day, &$no_validity_exists, &$no_validity_ratecards_all)
    {
        if (!empty($service[$ratecard_key])) {
            foreach ($service[$ratecard_key] as $rate_key => $ratecard) {
                $duration_day = $this->utilities->getDurationDay($ratecard);
                if ($ratecard['type'] == 'extended validity') {

                    $service[$ratecard_key][$rate_key]['recommended'] = Config::get('nonvalidity.recommnded_block');

                    $ratecard['duration_type_copy'] = $ratecard['duration_type'];

                    if (!empty($ratecard['flags']['unlimited_validity'])) {
                        $no_validity_exists = true;
                        $ratecard['ext_validity'] = "Unlimited Validity";

                    } else {

                        $ratecard['ext_validity'] = "Valid for " . $ratecard['validity'] . ' ' . $ratecard['validity_type'];
                    }
                    $ratecard['duration_type'] = $ratecard['duration_type'] . "\n(" . $ratecard['ext_validity'] . ')';

                    $ratecard['validity_type_copy'] = $ratecard['validity_type'];
                    $ratecard['validity_copy'] = $ratecard['validity'];
                    $ratecard['validity'] = 0;

                    if (empty($no_validity_ratecards[$duration_day])) {
                        $no_validity_ratecards[$duration_day] = [];
                    }
                    array_push($no_validity_ratecards[$duration_day], $ratecard);
                    array_push($no_validity_ratecards_all, $ratecard);
                }
            }
        }
    }

    /**
     * @param $cs_ratecard
     * @param $cs_ratecard_price
     */
    public function formatCrossSellRatecard(&$cs_ratecard, $cs_ratecard_price)
    {
        $cs_ratecard['knowmore'] = false;
        $cs_ratecard['sub_title'] = !empty($cs_ratecard['flags']['unlimited_validity']) ? "Unlimited Validity" : "Valid for " . $cs_ratecard['validity_copy'] . ' ' . ucwords($cs_ratecard['validity_type']);
        $cs_ratecard['title'] = $cs_ratecard['duration'] . ' ' . $cs_ratecard['duration_type_copy'];
        $cs_ratecard['button_text'] = 'BUY';
        $cs_ratecard['validity'] = 0;
        $cs_ratecard['price'] = $cs_ratecard_price;
        $cs_ratecard['special_price'] = 0;
    }

    /**
     * @param $ratecard
     * @param $price
     */
    public function formatRatecard(&$ratecard, $price)
    {
        $ratecard['button_text'] = 'Continue';
        $ratecard['title'] = $ratecard['validity'] . ' ' . $ratecard['validity_type'];
        $ratecard['validity'] = 0;

        if (!empty($ratecard['campaign_offer'])) {
            unset($ratecard['campaign_offer']);
        }
        if (!empty($ratecard["remarks"])) {
            unset($ratecard['remarks']);
        }
        if (!empty($ratecard['offers'])) {
            $ratecard['offers'] = [];
        }

        $ratecard['price'] = $price;
        $ratecard['special_price'] = 0;
        $ratecard['validity'] = 0;
    }

    /**
     * @param $data
     * @param $ratecard
     * @param $cs_ratecard
     * @param $key
     * @param $ratecard_key
     * @param $key1
     */
    public function getCorssSellSection(&$data, $ratecard, $cs_ratecard, $key, $ratecard_key, $key1)
    {
        $section3 = Config::get('nonvalidity.success_page');
        $section3['data'][0]['text'] = strtr($section3['data'][0]['text'], ['__vendor_name' => $data['finder']['title']]);

        if (empty($_GET['device_type']) || !in_array($_GET['device_type'], ['android', 'ios'])) {
            $section3['text'] = "You will get workout sessions in your wallet on Fitternity & can book this whenever you wish to workout. It gives you the ability to manage your usage & pay only for the workouts you end up doing.";
        }

        $data['finder']['services'][$key][$ratecard_key][$key1]['block'] = [
            'header' => 'Want to SAVE MORE?',
            'section1' => [
                'header' => 'You are currently buying',
                'ratecards' => [$ratecard],
            ],
            'section2' => [
                'header' => 'Save more by buying Session Packs',
                'ratecards' => [$cs_ratecard],
            ],
            'section3' => $section3
        ];
    }

    /**
     * @param $data
     * @param $ext_validity
     * @param $key
     * @param $ratecard_key
     * @param $key1
     * @param $ratecard
     * @param $service
     */
    public function formatServiceRatecard(&$data, $ext_validity, $key, $ratecard_key, $key1, &$ratecard, $service)
    {
        $data['finder']['services'][$key][$ratecard_key][$key1]['ext_validity'] = $ext_validity;
        $data['finder']['services'][$key]['unlimited_validity'] = !empty($data['finder']['services'][$key]['unlimited_validity']) || !empty($ratecard['flags']['unlimited_validity']);
        $data['finder']['services'][$key][$ratecard_key][$key1]['duration_type'] = $service[$ratecard_key][$key1]['duration_type'] . "\n(" . $ext_validity . ')';
        $data['finder']['services'][$key][$ratecard_key][$key1]['validity_copy'] = $data['finder']['services'][$key][$ratecard_key][$key1]['validity'];
        $data['finder']['services'][$key][$ratecard_key][$key1]['validity'] = 0;
        $data['finder']['services'][$key][$ratecard_key][$key1]['validity_type_copy'] = $data['finder']['services'][$key][$ratecard_key][$key1]['validity_type'];
        unset($data['finder']['services'][$key][$ratecard_key][$key1]['validity_type']);
    }
    
    public function removeEmptyServices(&$data, $source = null){
		
		$ratecard_key = 'ratecard';
	
		if($source != 'app'){
			$ratecard_key = 'serviceratecard';
		}
	
        $services = $data['finder']['services'];
        
        $extended_validity_service_ids = array_column(array_values(array_filter($services,function ($e) {return !empty($e['type']) && $e['type'] == "extended validity";})), '_id');
		$removed_services = [];
		foreach($services as $key => $value){
            
            $membership_ratecards = array_values(array_filter($value[$ratecard_key],function ($e) {return empty($e['hidden']) && !in_array($e['type'], ['trial', 'workout session']);}));

			if((empty($value['type']) || $value['type'] != 'extended validity') && in_array($value['_id'], $extended_validity_service_ids) && (empty($membership_ratecards))){
                unset($services[$key]);
                array_push($removed_services, $value['_id']);
			}


        }
        
		$data['finder']['services'] = array_values($services);
	}

    /**
     * @param $r
     * @return string
     */
    public function getExtendedValidityType(&$r)
    {
        return !empty($r['flags']['unlimited_validity']) ? "Unlimited Validity" : "Extended Validity";
    }

	public function testMultifit(){
		return $this->utilities->multifitFinder();
    }
    
    public function applyNonValidityDuration(&$data){
		foreach($data['finder']['services'] as &$service){
            $membership_ratecards = false;
            $unlimited_validity = false;
            foreach($service['serviceratecard'] as &$ratecard){
                if($ratecard['type'] == 'extended validity'){
                        
                    if(!empty($ratecard['flags']['unlimited_validity'])){
                        $ext_validity = "Unlimited Validity";
                        $unlimited_validity = true;
                    }else{
                        $ext_validity = "Valid for ".$ratecard['validity'].' '.$ratecard['validity_type'];
                    }

                    $ratecard['duration_type'] = $ratecard['duration_type'] . "(" . $ext_validity . ')';
                }else{
                    if($ratecard['type'] != 'workout session'){
                        $membership_ratecards = true;
                    }
                }
            }

            if(empty($membership_ratecards)){
                
                $getNonValidityBanner = $this->getNonValidityBanner();
                $getNonValidityBanner['description'] = $getNonValidityBanner['description'].Config::get('nonvalidity.service_footer');
                foreach($getNonValidityBanner as &$value){
                    $value = strtr($value, ['__ext_validity_type'=>!empty($unlimited_validity) ? "Unlimited Validity":"Extended Validity", '__vendor_name'=>$data['finder']['title']]);
                }
                $service['non_validity'] = $getNonValidityBanner;


            }
		
		}
	}
    
    public function removeUpgradeWhereNoHigherAvailable(&$data){

		foreach($data['finder']['services'] as $key => &$service){
            if(empty($key)){
                continue;
            }
            
            $upgradable_ratecards_membership = array_filter($service['serviceratecard'], function($x){
                return in_array(getDurationDay($x), [180,360]) && $x['type'] == 'membership';
            });

            $extended_validity_ratecards = array_filter($service['serviceratecard'], function($x){
                return $x['type'] == 'extended validity';
            });
            if(empty($extended_validity_ratecards)){
                $max_duration_session_pack = 0;    
            }else{

                $max_duration_session_pack = max(array_column($extended_validity_ratecards, 'duration'));
            }

            if(empty($upgradable_ratecards_membership)){
                foreach($service['serviceratecard'] as &$rc){
                    if($rc['type'] == 'membership'){
                        unset($rc['upgrade_popup']);
                    }
                }
            }
            
            foreach($service['serviceratecard'] as &$rc){
                if($rc['type'] == 'extended validity'){
                    if($rc['duration'] <= 20){
                        
                        if(empty($upgradable_ratecards_membership) && $max_duration_session_pack <= $rc['duration']){
                            unset($rc['upgrade_popup']);
                        }
                        
                    
                    }else{

                        if($max_duration_session_pack <= $rc['duration']){
                            unset($rc['upgrade_popup']);
                        }
                    
                    }
                }
            }
		}
	}
    
    public function serviceRemoveFlexiIfExtendedPresent(&$data, $source="web"){

        $ratecard_key = 'ratecard';
	
		if($source != 'app'){
			$ratecard_key = 'serviceratecard';
		}
        
        foreach($data['finder']['services'] as &$service){
            $extended_validity = false;
            
            foreach($service[$ratecard_key] as &$ratecard){
                if($ratecard['type'] == 'extended validity'){
                    $extended_validity = true;
                    break;
                }
            }

            if(!empty($extended_validity)){
                foreach($service[$ratecard_key] as &$ratecard1){
                    if(!empty($ratecard1['studio_extended_validity'])){
                        unset($ratecard1['studio_extended_validity']);
                    }
                    if(!empty($ratecard1['type']) && $ratecard1['type']=='studio_extended_validity'){
                        $ratecard1['type'] = 'membership';
                    }
                }

            }
		
		}
		
    }
    
    public function orderRatecards(&$data, $source='web'){
        $serviceRatecards = $source=='web' ? 'serviceratecard' : 'ratecard';
        $duration_session_pack = [1=>1, 30=>7, 90=>20, 180=>75, 360=>120, 720=>500];
        
        function compareDuration($a, $b){
            return getDurationDay($a) >= getDurationDay($b);
        }
        
        function compareSessions($a, $b){
            return $a['duration'] >= $b['duration'];
        }
        function duration_days($a){
            $a['duration_day'] = getDurationDay($a);
            return $a;
        }

        foreach($data['finder']['services'] as &$service){
            $trial_ratecards = [];
            $trial_ratecards = array_filter($service[$serviceRatecards], function($rc){
                return $rc['type'] == 'trial';
            });
            
            $ws_ratecards = array_filter($service[$serviceRatecards], function($rc){
                return $rc['type'] == 'workout session';
            });
            
            $membership_ratecards = array_filter($service[$serviceRatecards], function($rc){
                return $rc['type'] == 'membership';
            });

            $session_ratecards = array_filter($service[$serviceRatecards], function($rc){
                return $rc['type'] == 'extended validity' ;
            });
			
			$studio_extended_validity = array_filter($service[$serviceRatecards], function($rc){
                return $rc['type'] == 'studio_extended_validity' ;
			});
			
            usort($membership_ratecards, "compareDuration");
            usort($session_ratecards, "compareSessions");
            usort($studio_extended_validity, "compareSessions");
            // return $session_ratecards;
            $all_ratecards = array_merge($trial_ratecards, $ws_ratecards);

            $membership_ratecards = array_map('duration_days', $membership_ratecards);

            $session_buckets = createBucket($session_ratecards, 'duration', array_values($duration_session_pack));
            
            $membership_buckets = createBucket($membership_ratecards, 'duration_day', array_keys($duration_session_pack));

			$studio_extended_buckets = createBucket($studio_extended_validity, 'duration', array_keys($duration_session_pack));
			//Log::info('studio extended at order ratecard::::::', [$studio_extended_buckets]);
            foreach($duration_session_pack as $key => $value){
                $all_ratecards = array_merge($all_ratecards, $studio_extended_buckets[$key], $session_buckets[$value], $membership_buckets[$key]);
            }

            $service[$serviceRatecards] =  $all_ratecards;
		}
    }

	public function getExtendedValidityBanner(){
        if(in_array($this->device_type, ['android', 'ios'])){
            return Config::get('extendedValidity.finder_banner_app');
		}
		else{
            return Config::get('extendedValidity.finder_banner');
        }
    }
	
	public function orderSummary($services, $finder_name, $finder=null){
        $orderSummary2 = Config::get('orderSummary.order_summary');
		$orderSummary2['header'] = strtr($orderSummary2['header'], ['vendor_name'=>$finder_name]);
		$title =  strtolower($orderSummary2['title']);
		
		foreach($services as &$service){
			$orderSummary2['header'] = strtr($orderSummary2['header'], ['service_name'=>$service['service_name']]);
			foreach($service['ratecard'] as &$rc){
				$orderSummary = $orderSummary2;
				//Log::info('ratecard details:::::::::',[$rc['validity'], $rc['validity_type'], $rc['duration'], $rc['duration_type']]);
				if(in_array($rc['type'], ['membership', 'extended validity', 'studio_extended_validity'])){
					$orderSummary['header'] = ucwords(strtr($orderSummary['header'], ['ratecard_name'=>$rc['validity'].' '.$rc['validity_type'].' Membership' ]));
					
					if(empty($finder['flags']['monsoon_flash_discount_disabled'])){
						$orderSummary['header'] = ucwords(strtr($orderSummary['header'], ['ratecard_name'=>$rc['validity'].' '.$rc['validity_type'].' Membership' ])."\n\n Festive Fitness Fiesta \n\n Use Magic Code: MODAK For Surprise Additional Discount Upto 30% Off On Lowest Price Memberships & Session Packs");
					}
                }else{
                    $orderSummary['header'] = ucwords(strtr($orderSummary['header'], ['ratecard_name'=>$rc['validity'].' '.$rc['validity_type'].' '.$rc['duration'].' '.$rc['duration_type']])."\n\n Festive Fitness Fiesta \n\n Book Workout Sessions And Get 100% Instant Cashback. Use Code: CB100");
                    // if(!empty($finder['flags']['monsoon_campaign_pps'])){
					// 	$orderSummary['header'] = $orderSummary['header']." ".ucwords("\n\n Festive Fitness Fiesta \n\n Use Magic Code: MODAK For Surprise Additional Discounts Upto 75%");
                    // }

                }
				$orderSummary['title'] = ucwords($title);
				$rc['order_summary'] = $orderSummary;
				$remark_data=[];
				if(isset($rc['remarks']) && $rc['remarks'] != ""){
					array_push($remark_data,  ucwords(strtr($orderSummary['remark_data'], ['ratecard_remark'=>$rc['remarks']])));
					$rc['order_summary']['remark_data'] = $remark_data;
				}
				else{
					unset($rc['order_summary']['title']);
					unset($rc['order_summary']['remark_data']);
					
				}
				// deleting remark from ratecard if it is not important
				if(!(isset($rc['remarks_imp']) && $rc['remarks_imp'])){
					unset($rc['remarks']);
				}
				// adding extended validity branding for vendor they have more then two ratecards of extended validity
			}
		}
		return $services;
	}
	
	public function addingRemarkToDuplicate($service, $source="web"){
		$serviceRatecards = $source=='web' ? 'serviceratecard' : 'ratecard';
		$dupDurationDays = [];
		foreach ($service[$serviceRatecards] as $ratekey => $rateval){
			$durationDays = $this->utilities->getDurationDay($rateval);
			if($rateval['type']!='extended validity') {
				if(empty($dupDurationDays[$durationDays])){
					$dupDurationDays[$durationDays] = [];	
				}
				array_push($dupDurationDays[$durationDays], $ratekey);
			}
		}

		$remarkImportantIndex = [];
		foreach ($dupDurationDays as $record) {
			if(count($record)>1) {
				$remarkImportantIndex = array_merge($remarkImportantIndex, $record);
			}
		}

		foreach ($remarkImportantIndex as $idx) {
			if(!empty($service[$serviceRatecards][$idx])) {
				$service[$serviceRatecards][$idx]['remarks_imp'] = true;
				$service[$serviceRatecards][$idx]['remarks_imp_api'] = true;
			}
		}
		return $service;
	}

	public function getExtendedValidityTypeToRateCards($data, $ratecard_key){
		try{
			Log::info('extended ratecard:::::::::::::::::: ', [$this->app_version]);
			$getNonValidityBanner = $this->getNonValidityBanner();
			
            foreach($data['finder']['services'] as &$s){

                    foreach($s[$ratecard_key] as &$r){

                        if($r['type'] == 'extended validity'){
							$r[ "button_color"] = Config::get('app.ratecard_button_color');
							$r['pps_image'] = Config::get('app.pps_image');
							$r['recommended'] = Config::get('nonvalidity.recommnded_block');
							if(empty($r['offers']) && ($this->device_type=='ios')) {
								$r['offers'] = [[
									'offer_text' => $r['recommended']['text']
								]];
							}
							$extended_validity_type = $this->getExtendedValidityType($r);

							if(($this->device_type=='ios' &&$this->app_version > '5.1.7') || ($this->device_type=='android' &&$this->app_version > '5.23')){
								$getNonValidityBanner['header'] = strtr($getNonValidityBanner['header'], ['vendor_name'=>($data['finder']['title'])]);	
								$getNonValidityBanner['description'] = strtr($getNonValidityBanner['description'], ['vendor_name'=>($data['finder']['title'])]);
								$r['popup_data']  = $getNonValidityBanner;
							}
							else{

								$getNonValidityBanner['description'] = strtr( $getNonValidityBanner['description'], [
									"__vendor_name"=>$data['finder']['title'],
									"__ext_validity_type"=> $extended_validity_type
								]);
		
								if(!empty($getNonValidityBanner['how_it_works'])){
									$getNonValidityBanner['how_it_works']['description'] = strtr($getNonValidityBanner['how_it_works']['description'], ['__vendor_name'=>$data['finder']['title']]);
								}
		
								$getNonValidityBanner['title'] = strtr($getNonValidityBanner['title'], ['__ext_validity_type'=>($extended_validity_type)]);
								if(!empty($getNonValidityBanner['title1'])){
									$getNonValidityBanner['title1'] = strtr($getNonValidityBanner['title1'], ['__ext_validity_type'=>($extended_validity_type)]);
								}
							
								$getNonValidityBanner['description'] = $getNonValidityBanner['description'].Config::get('nonvalidity.how_works');
								$getNonValidityBanner['description'] = strtr($getNonValidityBanner['description'], ['no_of_sessions'=>$r['duration']]);
								$r['non_validity_ratecard']  = $getNonValidityBanner;
							}
    
                        }
                    }
			}
			return $data;
        }catch(Exception $e){
            
            Log::info("Non validity description breaking", [$e]);
        
        }
	}


	public function addCreditPoints(&$value, $customer_id){
		
		if(!empty($customer_id)){
			foreach($value as &$service){
				if(!empty($service['serviceratecard'])){
					foreach($service['serviceratecard'] as &$ratecards){
						if($ratecards['type']=='workout session'){
							// $creditApplicable = $this->passService->getCreditsApplicable($ratecards['price'], $customer_id);
							$creditApplicable = $this->passService->allowSession($ratecards['price'], $customer_id);
							Log::info('credit appplicable"::::::', [$creditApplicable]);
							if($creditApplicable['allow_session']){
								$ratecards['price_text'] = 'Free for you';	
							}
						}
					}
				}
				else if(!empty($service['ratecard'])){
					foreach($service['ratecard'] as &$ratecards){
						if($ratecards['type']=='workout session'){
							// $creditApplicable = $this->passService->getCreditsApplicable($ratecards['price'], $customer_id);
							$creditApplicable = $this->passService->allowSession($ratecards['price'], $customer_id);
							Log::info('credit appplicable"::::::', [$creditApplicable]);
							if($creditApplicable['allow_session']){
								$ratecards['price_text'] = 'Free for you';	
							}
						}
					}
				}
			}
		}
	}

	public function multifitGymWebsiteVendorUpdate(&$data){
		
		if(!empty(Request::header('Source')) && Request::header('Source') == "multifit"){
			Log::info('inside vendor update for multifit gym');
			if(!empty($data['finder']['website_membership'])){
				$data['finder']['website_membership'] = $this->pathAddingToVendorWebsite($data['finder']['website_membership']);
	
				if(!empty($data['finder']['website_membership']['cover']['image'])){
	
					$data['finder']['coverimage'] = $data['finder']['website_membership']['cover']['image'];
					if(!empty($data['finder']['website_membership']['cover']['mobile_image'])){
						$data['finder']['mobile_image'] = $data['finder']['website_membership']['cover']['mobile_image'];
					}
				}
	
				if(!empty($data['finder']['website_membership']['class_time_table']['image'])){
	
					$data['finder']['class_time_table'] = $data['finder']['website_membership']['class_time_table']['image'];
				}
	
				if(!empty($data['finder']['website_membership']['address'])){

					if(empty($data['finder']['contact'])){
						$data['finder']['contact']= null;
					}

					$data['finder']['contact']['address'] = !empty($data['finder']['website_membership']['address']['location'])? $data['finder']['website_membership']['address']['location']: (!empty($data['finder']['contact']['address'])? $data['finder']['contact']['address']: null);

					$data['finder']['contact']['phone'] = !empty($data['finder']['website_membership']['address']['contact_number']) ? $data['finder']['website_membership']['address']['contact_number']:  (!empty($data['finder']['contact']['phone'])? $data['finder']['contact']['phone']: null);

					$data['finder']['contact']['email'] = !empty($data['finder']['website_membership']['address']['email']) ?$data['finder']['website_membership']['address']['email']: (!empty($data['finder']['contact']['email'])? $data['finder']['contact']['email']: null);

					$data['finder']['contact']['pincode'] = !empty($data['finder']['website_membership']['address']['pincode'])?$data['finder']['website_membership']['address']['pincode']: (!empty($data['finder']['contact']['pincode'])? $data['finder']['contact']['pincode']: null);

					foreach($data['finder']['contact'] as $key=>$value){
						if(empty($value)){
							unset($data['finder']['contact'][$key]);
						}
					}
				}
	
				if(!empty($data['finder']['website_membership']['services_list'])){
					$data['finder']['servicesfilter_fitt'] = $data['finder']['servicesfilter'];
					$data['finder']['servicesfilter'] = $data['finder']['website_membership']['services_list'];
					// foreach($data['finder']['website_membership']['services_list'] as $key=> $value){
	
					// 	foreach($data['finder']['servicesfilter'] as $serviceFilterKey=> $serviceFilterValue){
	
					// 		if(strtolower($value['name'])==  strtolower($serviceFilterValue['tag'])){
					// 			Log::info('inside vendor update for multifit gym------------->>>>>>>>>>>>.matched service name image');
					// 			$data['finder']['servicesfilter'][$serviceFilterKey]['image'] = $value['image'];
					// 			break;
					// 		}
					// 	}
					// }
				}
	
				if(!empty($data['finder']['website_membership']['facilities_list'])){
					$data['finder']['facilities_fitt'] = $data['finder']['facilities'];
					$data['finder']['facilities'] = $data['finder']['website_membership']['facilities_list'];
					// foreach($data['finder']['website_membership']['facilities_list'] as $key=> $value){
	
					// 	foreach($data['finder']['facilities'] as $facilitiesKey=> $facilitiesValue){
	
					// 		if(strtolower($value['name'])==  strtolower($facilitiesValue['name'])){
					// 			Log::info('inside vendor update for multifit gym------------->>>>>>>>>>>>.matched filtes name image',[$value['image']]);
					// 			$data['finder']['facilities'][$facilitiesKey]['image'] = $value['image'];
					// 			break;
					// 		}
					// 	}
					// }
				}

				if(!empty($data['finder']['website_membership']['walk_through']['video'])){
					$data['finder']['playOverVideo']['url'] = $data['finder']['website_membership']['walk_through']['video'];
				}
				unset($data['finder']['website_membership']);
				unset($data['finder']['brand']['brand_detail']['brand_website']);

			}
		}
	}

	public function pathAddingToVendorWebsite($first_block){
		$base_url =Config::get('app.s3_bane_url');
		foreach($first_block as $key=>$value){
			if(in_array($key,['cover', 'thumbnail', 'class_time_table']) && isset($first_block[$key]['image'])){	
				$first_block[$key]['image'] =  $base_url.$first_block[$key]['path'].$first_block[$key]['image'] ;
				if(!empty($first_block[$key]['mobile_image'])){
					$first_block[$key]['mobile_image'] =  $base_url.$first_block[$key]['path'].$first_block[$key]['mobile_image'] ;
				}
			}	

			if(in_array($key,['services_list', 'memberships_list', 'facilities_list'])){
				foreach($first_block[$key] as $keyImage=>$valueImage){
					if(!empty($first_block[$key][$keyImage]['image'])){
						$first_block[$key][$keyImage]['image'] =  $base_url.$first_block[$key][$keyImage]['path'].$first_block[$key][$keyImage]['image'];
					}
				}
			}	
		}
		return $first_block;
	}

	public function addRemarkToraecardweb(&$rateCard, $finderservice, $finder){
		if(isFinderIntegrated($finder) && isServiceIntegrated($finderservice)){
			$rateCard['remarks'] = "Get 100% Instant Cashback, Use Code: CB100";
			// if(!empty($finder['flags']['monsoon_campaign_pps']) && ($rateCard['price'] == 73 || $rateCard['special_price'] == 73)){
			// 	$rateCard['remarks'] = "Get 100% Instant Cashback, Use Code: CB100";
			// }
			$rateCard['remarks_imp'] = true;
		
		}
	}

}