<?php


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

    protected $dates = array('start_date','end_date');


    public static $withoutAppends = false;

    public static $setAppends = [];
	

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return self::$setAppends;
		}
		return parent::getArrayableAppends();
	}

//    protected $appends = array('active_weekdays', 'workoutsession_active_weekdays', 'service_coverimage', 'service_coverimage_thumb', 'service_ratecards', 'service_trainer','serviceratecard','servicebatches');
    protected $appends = array('active_weekdays', 'workoutsession_active_weekdays', 'service_coverimage', 'service_coverimage_thumb', 'service_trainer','servicebatches', 'trial_active_weekdays');
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
		if(!empty($this->_id) && isset($this->_id)){
			$ratecardsarr 	= 	Ratecard::where('service_id', intval($this->_id))->orderBy('order', 'asc')->get()->toArray();
		}

		
		if($ratecardsarr){
	//            var_dump($ratecardsarr);

            $offer_exists = false;
            foreach ($ratecardsarr as $key => $value) {
				
            	$ratecardoffers 	= 	[];
				


                if(!empty($value['_id']) && isset($value['_id'])){
                    $ratecardoffersRecards 	= 	Offer::where('ratecard_id', intval($value['_id']))->where('hidden', false)->orderBy('order', 'asc')
                                                    ->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
                                                    ->where('end_date', '>=', new DateTime( date("d-m-Y 00:00:00", time()) ))
                                                    ->get(['start_date','end_date','price','type','allowed_qty','remarks'])
                                                    ->toArray();
                    foreach ($ratecardoffersRecards as $ratecardoffersRecard){
            			$offer_exists = true;
                        $ratecardoffer                  =   $ratecardoffersRecard;
                        $ratecardoffer['offer_text']    =   "";
                        $ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/fitmania/hot_offer_vendor.png";

                        if(isset($value['flags'])){

							if(isset($value['flags']['discother']) && $value['flags']['discother'] == true){
								$ratecardoffer['offer_text']    =   "";
								// $ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/womens-day/women-only.png";
								$ratecardoffer['offer_icon']    =   "";
							}

							if(isset($value['flags']['disc25or50']) && $value['flags']['disc25or50'] == true){
								$ratecardoffer['offer_text']    =   "";
								// $ratecardoffer['offer_icon']    =   "https://b.fitn.in/iconsv1/womens-day/women-only.png";
								$ratecardoffer['offer_icon']    =   "";
							}
						}

                        $today_date     =   new DateTime( date("d-m-Y 00:00:00", time()) );

                        $end_date       =   new DateTime( date("d-m-Y 00:00:00", strtotime("+ 1 days", strtotime("2017-05-15T18:30:00.000Z"))));
                        if(isset($ratecardoffer['end_date'])){
                        	$end_date       =   new DateTime( date("d-m-Y 00:00:00", strtotime("+ 1 days", strtotime($ratecardoffer['end_date']))));
                        }

                        $difference     =   $today_date->diff($end_date);

                        if($difference->days <= 15){
                            $ratecardoffer['offer_text']    =  ($difference->d == 1) ? "Expires Today" : ($difference->d > 3 ? "Expires soon" : "Expires in ".$difference->days." days");

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
					
                }
//                var_dump($ratecardoffers);exit;
				// if(isset($this['offer_available']) && $this->offer_available && !$offer_exists && !in_array($this['finder_id'], Config::get('app.hot_offer_excluded_vendors'))){
				// 	if(isset($value['type']) && ($value['type']=='membership' || $value['type']=='packages')){
				// 		if(isset($value['validity']) && isset($value['validity_type'])){
							
				// 			if($value['validity_type']=='year'){
				// 				$validity = $value['validity'] * 365;
				// 			}else if($value['validity_type']=='months'){
				// 				$validity = $value['validity'] * 30;
				// 			}else if($value['validity_type']=='days'){
				// 				$validity = $value['validity'];
				// 			}
				// 			if($validity){
				// 				if($validity > $max_validity){
				// 					$second_max_validity = $max_validity;
				// 					$second_max_validity_ids = $max_validity_ids;
				// 					$max_validity_ids = [$value['_id']];
				// 					$max_validity = $validity;
				// 				}else if($validity > $second_max_validity && $validity < $max_validity){
				// 					$second_max_validity = $validity;
				// 					$second_max_validity_ids = [$value['_id']];
				// 				}else if($validity == $max_validity){
				// 					array_push($max_validity_ids, $value['_id']);
				// 				}else if($validity == $second_max_validity){
				// 					array_push($second_max_validity_ids, $value['_id']);
				// 				}
				// 			}
							
							
				// 		}
				// 	}
				// }

                $value['offers']  = $ratecardoffers;

                if(count($ratecardoffers) > 0 && isset($ratecardoffers[0]['price'])){
                	
                    $value['special_price'] = $ratecardoffers[0]['price'];

                    ($value['price'] == $ratecardoffers[0]['price']) ? $value['special_price'] = 0 : null;

                    if(isset($ratecardoffers[0]['remarks']) && $ratecardoffers[0]['remarks'] != ""){
                    	$value['remarks'] = $ratecardoffers[0]['remarks'];
                    }
                }

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
				
				if(isset($value['type']) && in_array($value['type'], ['membership', 'packages']) && isset($value['flags']) && isset($value['flags']['campaign_offer']) && $value['flags']['campaign_offer']){
					$value['campaign_offer'] = "(Women - Get additional 30% off)";
					$value['campaign_color'] = "#FA5295";
				}				
				
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

}