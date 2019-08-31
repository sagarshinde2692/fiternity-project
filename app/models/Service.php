<?php
use Illuminate\Support\Facades\Config;
/** 
 * ModelName : Service.
 * Maintains a list of functions used for Service.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Service extends \Basemodel{

	protected $collection = "services";

	public static $rules = array(
		'name' => 'required',
		'servicecategory_id' => 'required',
		'servicesubcategory_id' => 'required'

		);

    protected $dates = array('start_date','end_date','membership_start_date','membership_end_date');


    public static $withoutAppends = false;

    public static $setAppends = [];
	
	public static $isThirdParty = false;

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return self::$setAppends;
		}
		return parent::getArrayableAppends();
	}

//    protected $appends = array('active_weekdays', 'workoutsession_active_weekdays', 'service_coverimage', 'service_coverimage_thumb', 'service_ratecards', 'service_trainer','serviceratecard','servicebatches');
    protected $appends = array('active_weekdays', 'workoutsession_active_weekdays', 'service_coverimage', 'service_coverimage_thumb', 'service_trainer','servicebatches', 'trial_active_weekdays','serviceratecard');
	// protected $appends = array('active_weekdays', 'workoutsession_active_weekdays', 'service_coverimage', 'service_coverimage_thumb', 'service_ratecards');

	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}
	
	public function getActiveWeekdaysAttribute(){

		$activedays 		= 	array();
		if(!empty($this->trialschedules) && isset($this->trialschedules)){
			$trialschedules  	=	$this->trialschedules;
			foreach ($trialschedules as $key => $schedule) {
				if(!empty($schedule['slots'])){
					array_push($activedays, $schedule['weekday']);
				}
			}
			// $activedays 		= pluck( $this->trialschedules , array('weekday') );
		}

		return $activedays;
	}

	public function getWorkoutsessionActiveWeekdaysAttribute(){

		$activedays 	= 	array();
		if(!empty($this->workoutsessionschedules) && isset($this->workoutsessionschedules)){
			$schedules  	=	$this->workoutsessionschedules;

			foreach ($schedules as $key => $schedule) {
				if(!empty($schedule['slots'])){
					array_push($activedays, $schedule['weekday']);
				}
			}
			// $activedays 		= pluck( $this->schedules , array('weekday') );
		}
		return $activedays;
	}

	public function getTrialActiveWeekdaysAttribute(){

		$activedays 	= 	array();
		if(!empty($this->trialschedules) && isset($this->trialschedules)){
			$schedules  	=	$this->trialschedules;

			foreach ($schedules as $key => $schedule) {
				if(!empty($schedule['slots'])){
					array_push($activedays, $schedule['weekday']);
				}
			}
			// $activedays 		= pluck( $this->schedules , array('weekday') );
		}
		return $activedays;
	}


	public function getServiceCoverimageAttribute(){

		$service_coverimage = '';

		if(!empty($this->coverimage) && isset($this->coverimage) && trim($this->coverimage) != ""){
			$service_coverimage = 's/c/'.trim($this->coverimage);
		}else{
			$finder  	=	Finder::find(intval($this->finder_id));
			if($finder){
				$service_coverimage = (trim($finder->coverimage) != '') ? 'f/c/'.trim($finder->coverimage) : 'f/c/default/'.$finder->category_id.'-'.rand(1, 4).'.jpg';
			}
		}
		return $service_coverimage ;
	}

	public function getServiceCoverimageThumbAttribute(){

		$service_coverimage = '';

		if(!empty($this->coverimage) && isset($this->coverimage) && trim($this->coverimage) != ""){
			$service_coverimage = 's/ct/'.trim($this->coverimage);
		}else{
			$finder  	=	Finder::find(intval($this->finder_id));
			if($finder){
				$service_coverimage = (trim($finder->coverimage) != '') ? 'f/c/'.trim($finder->coverimage) : 'f/c/default/'.$finder->category_id.'-'.rand(1, 4).'.jpg';
			}
		}
		return $service_coverimage ;
	}


	public function getServiceRatecardsAttribute(){

		$ratecards 	= 	[];
		// dd($this->ratecards);exit();
		if(!empty($this->ratecards) && isset($this->ratecards)){

			foreach ($this->ratecards as $key => $value) {
				$days = $sessions = '';


				if(isset($value['duration']) && $value['duration'] != ''){
					$durationObj 	=	Duration::active()->where('slug', url_slug(array($value['duration'])))->first();
					$days 			=	(isset($durationObj->days)) ? $durationObj->days : "";
					$sessions 		= 	(isset($durationObj->sessions)) ? $durationObj->sessions : "";
				}

                $offers  = [];


				if(isset($value['type']) && $value['type'] == 'workout session'){
					$value['remarks'] = $$value['remarks']." (100% Cashback)";
				}

				$ratecard = [
				'order'=> (isset($value['order'])) ? $value['order'] : '0',
				'type'=> (isset($value['type'])) ? $value['type'] : '',
				'duration'=> (isset($value['duration'])) ? $value['duration'] : '',
				'days'=> (isset($days)) ? intval($days) : '',
				'sessions'=> (isset($sessions)) ? intval($sessions) : '',
				'price'=> (isset($value['price'])) ? $value['price'] : '',
				'special_price'=> (isset($value['special_price'])) ? $value['special_price'] : '',
				'remarks'=> (isset($value['remarks'])) ? $value['remarks'] : '',
				'show_on_fitmania'=> (isset($value['show_on_fitmania'])) ? $value['show_on_fitmania'] : '',
				'direct_payment_enable'=> (isset($value['direct_payment_enable'])) ? $value['direct_payment_enable'] : '0',
				'featured_offer'=> (isset($value['featured_offer'])) ? $value['featured_offer'] : '0',
                 'cashback' => (isset($value['type']) && $value['type'] == 'trial' && isset($value['price']) && intval($value['price']) > 0)  ? "100%" : ''
				];
				// dd($ratecard);exit();

				array_push($ratecards, $ratecard);
			}
		}

		return $ratecards ;
	}


	public function getServiceratecardAttribute(){

		$ratecards 	= 	[];
		$validity = null;
		$max_validity = 0;
		$second_max_validity = 0;
		$max_validity_ids = [];
		$second_max_validity_ids = [];
		$ratecardsarr = null;
        $finder = $this->finder;

        if(!empty($GLOBALS['finder_commission'])){
            Log::info("Commission");
            Log::info($GLOBALS['finder_commission']);
        }
        if(!empty($finder['flags']['enable_commission_discount']) && empty($GLOBALS['finder_commission'])){

            Log::info("Commission not defined");
            $GLOBALS['finder_commission'] = getVendorCommision(['finder_id'=>$finder['_id']]);
        }
        
		if(!empty($this->_id) && isset($this->_id)){
			if((!empty($finder->brand_id) && $finder->brand_id == 130) || (in_array($finder->_id, Config::get('app.powerworld_finder_ids', [])))){
				// talwalkars & powerworld
				$ratecardsarr 	= 	Ratecard::active()->where('service_id', intval($this->_id))->orderBy('order', 'asc')->get()->toArray();
			}else{
				if(self::$isThirdParty){
					$ratecardsarr 	= 	Ratecard::active()->where('service_id', intval($this->_id))->where('type','=','workout session')->orderBy('order', 'asc')->get()->toArray();
				}
				else {
					$ratecardsarr 	= 	Ratecard::active()->where('service_id', intval($this->_id))->where('type', '!=', 'trial')->orderBy('order', 'asc')->get()->toArray();
				}
			}
		}

		
		if($ratecardsarr){
	        //var_dump($ratecardsarr);

			$offer_exists = false;
			
			// $serviceoffers = Offer::where('vendorservice_id', $this->_id)->where('hidden', false)->orderBy('order', 'asc')
			// 						->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
			// 						->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
			// 						->get(['start_date','end_date','price','type','allowed_qty','remarks','offer_type','ratecard_id','callout','added_by_script'])
			// 						->toArray();

			$serviceoffers = Offer::getActiveV1('vendorservice_id', intval($this->_id), $finder)->toArray();
			$workoutSession = [];
			$extendedRatecards = [];
			foreach ($ratecardsarr as $key => $value) {
				if($value['type']=='workout session'){
					array_push($workoutSession, $value['_id']);
				}
				if($value['type']=='extended validity'){
					array_push($extendedRatecards, $value['_id']);
				}
			}
			foreach ($ratecardsarr as $key => $value) {
                
                $days = getDurationDay($value);
                // if(!empty($finder['flags']['april5']) && in_array($days, Config::get('app.discount_vendors_duration', [180, 360]))){
                //     $value['coupon_text'] = 'Addnl 5% off - Use code MAY5';
                // }

				// if((isset($value['expiry_date']) && $value['expiry_date'] != "" && strtotime("+ 1 days", strtotime($value['expiry_date'])) < time()) || (isset($value['start_date']) && $value['start_date'] != "" && strtotime($value['start_date']) > time())){
				// 	$index--;
				// 	Log::info("ratecard expired");
				// 	Log::info($value['_id']);

				// 	continue;
				// }
				
            	$ratecardoffers 	= 	[];
				// Log::info($serviceoffers);
				//&& !empty($workoutSession) && empty($extendedRatecards)
				//Log::info('workout sessions and extended ratecardsL:::::::::', [$ratecardsarr, $workoutSession, $extendedRatecards]);
                if(!empty($value['_id']) && isset($value['_id'])){
					
					$studioExtValidity = (!in_array($this->servicecategory_id, Config::get('app.non_flexi_service_cat', [111, 65, 5]))) && (!empty($this->batches) && count($this->batches)>0) && in_array($days, [30, 90]) && (!empty($value['duration_type']) && $value['duration_type']=='session' && !empty($value['duration']));

					if(!empty($studioExtValidity) && $studioExtValidity && ($value['type']!='extended validity')){
						$numOfDays = (in_array($value['validity_type'], ['month', 'months']))?$value['validity']*30:$value['validity'];
						
						$numOfDays = (in_array($value['validity_type'], ['year', 'years']))?$value['validity']*360:$numOfDays;

						$numOfDaysExt = ($numOfDays==30)?15:(($numOfDays>=90)?30:0);

						$value['studio_extended_validity'] = [
							'num_days_extended' => $numOfDaysExt
						];
					}

                    $ratecardoffersRecards 	= 	array_where($serviceoffers, function($key, $offer) use ($value){
						if($offer['ratecard_id'] == $value['_id'])
							{
							 return true; 
							}
					});
					if(isset($this->membership) && $this->membership == 'disable' || isset($finder['membership']) && $finder['membership'] == 'disable'){
						$ratecardoffersRecards = [];
					}
                    foreach ($ratecardoffersRecards as $ratecardoffersRecard){
            			$offer_exists = true;
                        $ratecardoffer                  =   $ratecardoffersRecard;
						Log::info("lsdjflsjd ===".$ratecardoffer["price"]." ----- ".$value["price"]);
						if($ratecardoffer["price"] == $value['price']){
							$ratecardoffer["price"] = 0;
						}
                        $ratecardoffer['offer_text']    =   "";
                        $ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/fitmania/hot_offer_vendor.png";
                        if(!empty($ratecardoffersRecard['callout']))$ratecardoffer['callout']=$ratecardoffersRecard['callout'];
                        // if(isset($value['flags'])){

						// 	if(isset($value['flags']['discother']) && $value['flags']['discother'] == true){
						// 		$ratecardoffer['offer_text']    =   "";
						// 		// $ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/womens-day/women-only.png";
						// 		$ratecardoffer['offer_icon']    =   "";
						// 	}

						// 	if(isset($value['flags']['disc25or50']) && $value['flags']['disc25or50'] == true){
						// 		$ratecardoffer['offer_text']    =   "";
						// 		// $ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/womens-day/women-only.png";
						// 		$ratecardoffer['offer_icon']    =   "";
						// 	}
						// }

                        $today_date     =   new DateTime( date("d-m-Y 00:00:00", time()) );

                        $end_date       =   new DateTime( date("d-m-Y 00:00:00", strtotime("+ 1 days", strtotime("2017-05-15T18:30:00.000Z"))));
                        if(isset($ratecardoffer['end_date'])){
                        	$end_date       =   new DateTime( date("d-m-Y 00:00:00", strtotime("+ 1 days", strtotime($ratecardoffer['end_date']))));
                        }

                        $difference     =   $today_date->diff($end_date);

                        if($difference->days <= 15 && $difference->days != 0){
                            $ratecardoffer['offer_text']    =  ($difference->days == 1) ? "Expires Today" : ($difference->days > 7 ? "Expires soon" : "Expires in ".$difference->days." days");
						}
                        
                        Log::info('setting  slots for 13901');
                        
                        if(!in_array($finder->_id, [13901])){
                            
                            $orderVariable = \Ordervariables::where("name","expiring-logic")->orderBy("_id", "desc")->first();
							if(isset($orderVariable["available_slots_end_date"]) && time() >= $orderVariable["available_slots_end_date"]){
								$futureExpiry = (date('d',$orderVariable["end_time"])-intval(date('d', time())));
								$ratecardoffer['offer_text']    =  ($difference->days == 1 || $futureExpiry == 0) ? "Expires Today" : (($difference->days > 7 || $difference->days == 0) ? "Expires in ".((date('d',$orderVariable["end_time"])-intval(date('d', time()))))." days" : "Expires in ".(intval($difference->days))." days");
							}else{
								if($this->available_slots > 0 && time() >= $orderVariable["start_time"] && $key == count($ratecardsarr)-1){
									$ratecardoffer['offer_text']    =  ($this->available_slots > 1 ? $this->available_slots." slots" : $this->available_slots." slot")." left";
								}
							}
                        
                        }

						if($value['type'] == 'membership' && $value['direct_payment_enable'] == '1' && $key == count($ratecardsarr) - 1){

						// 	Log::info($value['_id']);
						// 	Log::info("slots left");

							

							
						}


                        array_push($ratecardoffers,$ratecardoffer);
                    }
					if(isset($value['flags'])){
						// Log::info("in flags");
						if(isset($value['flags']['offerFor'])){
							// Log::info("in offerFor");
							switch($value['flags']['offerFor']){
								case "student": 
												// $ratecardoffers[0]['offer_text']    =   "";
												$ratecardoffers[0]['offer_icon']    =   "https://b.fitn.in/iconsv1/fitmania/hot_offer_vendor.png";	
												break;
								case "women": 
												// $ratecardoffers[0]['offer_text']    =   "";
												$ratecardoffers[0]['offer_icon']    =   "https://b.fitn.in/iconsv1/fitmania/hot_offer_vendor.png";	
												break;
							}
						}
					}
					
					if(count($ratecardoffers) && isset($ratecardoffers[0]['offer_icon'])){
						if(in_array($value['type'], ['membership', 'packages']) && ((isset($finder['membership']) && $finder['membership'] == 'disable') || (isset($this['membership']) && $this['membership'] == 'disable') || (isset($finder['flags']) && isset($finder['flags']['state']) && in_array($finder['flags']['state'], ['temporarily_shut', 'closed'])) || $finder['commercial_type'] == 0)){
							$ratecardoffers[0]['offer_icon'] = "";
						}
					}
					
                }

				$ratecard_price = $value['price'];
				$cost_price = $value['price'];

				if(isset($value['special_price']) && $value['special_price'] != 0){
		            $ratecard_price = $value['special_price'];
		        }


                $value['offers']  = $ratecardoffers;

                // if(count($ratecardoffers) > 0 && isset($ratecardoffers[0]['price'])  ){
                if(count($ratecardoffers) > 0 && isset($ratecardoffers[0]['price'])  && isFinderIntegrated($finder) && isServiceIntegrated($this)){
					
                    $value['special_price'] = $ratecardoffers[0]['price'];

                    ($value['price'] == $ratecardoffers[0]['price']) ? $value['special_price'] = 0 : null;

                    if(isset($ratecardoffers[0]['remarks']) && $ratecardoffers[0]['remarks'] != ""){
                    	$value['remarks'] = $ratecardoffers[0]['remarks'];
                    }

				}

				
				
				// appendUpgradeData($value, $this);
                
                // if($value["type"] == "workout session" && $finder->category_id != 47){
                //     if($value["special_price"] > 0){
                //         $value["peak_price"] = intval($value["special_price"]);
                //         $value["special_price"] = $value["special_price1"] = intval($value["special_price"] * Config::get('app.non_peak_hours.off', 0.6)) ;
                //     }else{
                //         if($value["price"] > 0){
                //             $value["peak_price"] = intval($value["price"]) ;
                //             $value["special_price"] = intval($value["price"] * Config::get('app.non_peak_hours.off', 0.6)) ;
                //         }
                //     }	
                // }
                if($value["type"] == "workout session"){
                   $value[ "button_color"] = Config::get('app.ratecard_button_color');
				   $value[ "pps_know_more"] = true;
				   $value['pps_title'] = "Pay Per Session";
				   $value['title'] = '1 Workout';
				   //unset($value['remarks']);
				}

                if($value['type'] == 'membership' && !empty($GLOBALS['finder_commission'])){
                    if(!empty($value["special_price"] )){
                        $commission_discounted_price = $value["special_price"] = round($value["special_price"] * (100 - $GLOBALS['finder_commission'] + Config::get('app.pg_charge'))/100);
                        
                    }else if($value["price"] ){
                        $commission_discounted_price = $value["price"] = round($value["price"] * (100 - $GLOBALS['finder_commission'] + Config::get('app.pg_charge'))/100);
                        
                    }

                    if(!empty($value['offers'][0]['price']) && !empty($commission_discounted_price)){
                        $value['offers'][0]['price'] = $commission_discounted_price;
					}
				}
				
				(isset($value['special_price']) && $value['price'] == $value['special_price']) ? $value['special_price'] = 0 : null;

				if(intval($value['validity'])%360 == 0){
					$value['validity']  = intval(intval($value['validity'])/360);
					if(intval($value['validity']) > 1){
						$value['validity_type'] = "years";
					}else{
						$value['validity_type'] = "year";
					}
				}
				
				if(intval($value['validity'])%30 == 0){
					$value['validity']  = intval(intval($value['validity'])/30);
					if(intval($value['validity']) > 1){
						$value['validity_type'] = "months";
					}else{
						$value['validity_type'] = "month";
					}
				}

				if(intval($value['validity']) == 1 && $value['validity_type'] == 'months') {
					$value['validity_type'] = "month";
				}

				$offer_price = (!empty($value['special_price'])) ? $value['special_price'] : 0 ;
				$cost_price = (!empty($value['price'])) ? $value['price'] : 0 ;

                if($offer_price !== 0 && $offer_price < $cost_price && !in_array($value['type'], ['workout session', 'trial']) && !(isset($this->membership) && $this->membership == 'disable' || isset($finder['membership']) && $finder['membership'] == 'disable')){

                	$offf_percentage = floor((($cost_price - $offer_price)/$cost_price)*100);

                    // if($offf_percentage < 50){
                    //     $value['price'] = 2*$value['special_price'];
                    //     $offf_percentage = 50;
                    // }

                	$value['campaign_offer'] = $offf_percentage."% off";
					$value['campaign_color'] = "#43a047";
                }

				// if($ratecard_price >= 5000 && !(isset($this->membership) && $this->membership == 'disable' || isset($finder['membership']) && $finder['membership'] == 'disable')){

				// 	$value['campaign_offer'] = !empty($value['campaign_offer']) ?  $value['campaign_offer']." (EMI available)" : "(EMI available)";
				// 	$value['campaign_color'] = "#43a047";
				// }

                if(!empty($value['special_price']) && $value['price'] <= $value['special_price']){
					 $value['price'] = $value['special_price'];
                     $value['special_price'] = 0;
				}
				
				// if(isset($value['type']) && in_array($value['type'], ['membership', 'packages']) && isset($value['flags']) && isset($value['flags']['campaign_offer']) && $value['flags']['campaign_offer']){
				// 	$value['campaign_offer'] = "(Women - Get additional 30% off)";
				// 	$value['campaign_color'] = "#FA5295";
				// }		
                // if(!empty($value['campaign_offer'])){
                //     unset($value['campaign_offer']);
                //     if(!empty($value['campaign_color'])){
                //         unset($value['campaign_color']);
                //     };
                // }

                if(!empty($value['duration']) && $value['duration'] > 1 && !empty($value['duration_type']) && $value['duration_type'] == 'session'){
                    $value['duration_type'] = 'sessions';
                }

                if(isFinderIntegrated($finder) && isServiceIntegrated($this) && !empty($value['type']) && $value['type'] == "workout session" && !empty(Request::header('Device-Type')) && in_array(strtolower(Request::header('Device-Type')), ['android', 'ios'])){
                    if(!empty($value['offers'][0]['remarks'])){
                        $value['offers'][0]['remarks'] = "Use Magic Code: MODAK For Surprise Additional Discounts Upto 75% Off";
                        $value['remarks_imp'] =  true;
                    }else{
                        $value['remarks'] =  "Use Magic Code: MODAK For Surprise Additional Discounts Upto 75% Off";
                        $value['remarks_imp'] =  true;
                    }
                }

                if($this->servicecategory_id == 1 && $value['special_price'] == 99 && $value['type'] == "workout session" && isFinderIntegrated($finder) && isServiceIntegrated($this)){
                    $value['remarks'] =  '';
                    $value['remarks_imp'] =  true;
				}else if(($offer_price == 99 || $value['price'] == 99 || $value['special_price'] == 99) && $value['type'] == "workout session" && !empty($finder['flags']['monsoon_campaign_pps']) && isFinderIntegrated($finder) && isServiceIntegrated($this)){
                    $value['remarks'] =  '';
                    $value['remarks_imp'] =  true;
                }
                
                if(in_array($value['type'], ["membership", "extended validity"])&& isFinderIntegrated($finder) && isServiceIntegrated($this) && !empty(Request::header('Device-Type')) && in_array(strtolower(Request::header('Device-Type')), ['android', 'ios']) ){
                    $value['campaign_offer'] =  "";
                    $value['campaign_color'] = "";
				}else{
					$value['campaign_offer'] =  "";
                    $value['campaign_color'] = "";
				}
				
				unset($value['flags']['convinience_fee_applicable']);
				array_push($ratecards, $value);
			}

			// if(isset($this['offer_available']) && $this->offer_available && !$offer_exists){
			// 	$max_validity_ids = array_merge($max_validity_ids, $second_max_validity_ids);
			// 	foreach($ratecards as &$value){
			// 		if((in_array($value['_id'], $max_validity_ids))){
			// 			// Log::info($value);
			// 			// if($value[])
			// 			$value['offers'][0]['offer_text'] = '';
			// 			$value['offers'][0]['offer_icon'] = 'https://b.fitn.in/iconsv1/fitmania/hot_offer_vendor.png';
			// 		}
			// 	}
			// }
			
		}

		return $ratecards ;
	}


	public function getServiceTrainerAttribute(){

		$trainer 	= 	new stdClass();
		if(!empty($this->trainer_id) && isset($this->trainer_id) && intval($this->trainer_id) != 0){
			$trainerObj 	=	Servicetrainer::find(intval($this->trainer_id));
			$trainer   = $trainerObj;
		}
		return $trainer ;
	}


	public function getServicebatchesAttribute(){

		$service_batches = [];

		if(!empty($this->batches) && isset($this->batches)){
			foreach ($this->batches as $key => $batch) {
				$service_batch = [];

				foreach ($batch as $k => $batch_weekday) {
					$batch_weekday_arr = [];
					$batch_weekday_arr['weekday'] =  $batch_weekday['weekday'];
					$slots = [];
					if(!empty($batch_weekday['slots']) && isset($batch_weekday['slots'])){
						foreach ($batch_weekday['slots'] as $k => $slot) {
							array_push($slots, $slot);
						}
					}
					$batch_weekday_arr['slots'] = array_values($slots);
					array_push($service_batch, $batch_weekday_arr);
				}
				array_push($service_batches, $service_batch);
			}
		}

		return $service_batches;
	}



	public function category(){
		return $this->belongsTo('Servicecategory','servicecategory_id');
	}		

	public function subcategory(){
		return $this->belongsTo('Servicecategory','servicesubcategory_id');
	}

	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

	public function location(){
		return $this->belongsTo('Location','location_id');
	}

	public function city(){
		return $this->belongsTo('City','city_id');
	}

	public function trainer(){
		return $this->belongsTo('Servicetrainer','trainer_id');
	}


	public function ratecards(){
		return $this->hasMany('Ratecard','service_id');
	}



	public function serviceratecards(){
		return $this->hasMany('Ratecard','service_id');
	}

    public function getFreeTrialRatecardsAttribute(){

		return Ratecard::where('service_id', $this->_id)
            ->where('type', 'trial')
            ->where('price', 0)
            ->where(function($query){
                $query
                ->orWhere('expiry_date', 'exists', false)
                ->orWhere('expiry_date', '>', new MongoDate(strtotime('-1 days')));
            })
            ->where(function($query){
                $query
                ->orWhere('start_date', 'exists', false)
                ->orWhere('start_date', '<', new MongoDate(time()));
            })
            ->count();
		
	}
    public function scopeIntegrated ($query){
		return $query->where('status','=','1')->whereNotIn('showOnFront', [[], ['kiosk']])->where(function($query){$query->orWhere('membership', '!=', 'disable')->orWhere('trial', '!=', 'disable');});
	}

	public function scopeIntegratedMembership ($query){
		return $query->where('status','=','1')->whereNotIn('showOnFront', [[], ['kiosk']])->where('membership', '!=', 'disable');
	}

	public function scopeIntegratedTrial ($query){
		return $query->where('status','=','1')->whereNotIn('showOnFront', [[], ['kiosk']])->where('trial', '!=', 'disable');
	}

	public function getServiceInoperationalDatesArrayAttribute(){
		
		$inopertaional_dates = isset($this->inoperational_dates) ? $this->inoperational_dates : [];

		$inopertaional_dates = array_map(function($value){
			return $value->sec; 
		}, $inopertaional_dates);

		return $inopertaional_dates;
		
	}

}