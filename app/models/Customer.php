<?php

/** 
 * ModelName : Customer.
 * Maintains a list of functions used for Customer.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Customer extends  \Basemodel {

	protected $collection = "customers";
	protected $dates = array('last_visited','birthday');
	protected $appends = array('uber_trial','ttt_trial',"loyaltyvoucher_category","is_health_shown", "category_interested");

	public static $withoutAppends = false;

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return ['is_health_shown'];
		}
		return parent::getArrayableAppends();
	}
	
	// Add your validation rules here
	public static $rules = [
	    'name' => 'required|max:255',
	    'email' => 'required|email|unique:customers|max:255',
	    // 'password' => 'required|min:6|max:20|confirmed',
	    // 'password_confirmation' => 'required|min:6|max:20',
	    'contact_no' => 'max:15',
	    'identity' => 'required'
	];

	public static $update_rules = [
	    'name' => 'max:255',
	    'email' => 'email|max:255',
	    'contact_no' => 'max:15',
	    'location' => 'max:255'
	];

	public function getUberTrialAttribute(){

		$finders 	= 	[];
		$passed = [];
		$upcoming = [];
		// dd($this->campaign_finders);exit();
		if(!empty($this->uber_trials) && isset($this->uber_trials)){

			$trialObj 	=	Booktrial::whereIn('_id', array_map('intval', explode(",",$this->uber_trials)))->get();
			foreach ($trialObj as $key => $value) {
				// dd($value);exit();
				if(strtotime(date($value->schedule_date_time)) < time()){
					array_push($passed,$value);
				}
				else{
					array_push($upcoming,$value);	
				}
				$finders = array('passed_trial' => $passed, 'upcoming_trial' => $upcoming);
			}		
		}

		return $finders;
	}
	public function getTttTrialAttribute(){

		$finders 	= 	[];
		$passed = [];
		$upcoming = [];
		// dd($this->campaign_finders);exit();
		if(!empty($this->ttt_trials) && isset($this->ttt_trials)){

			$trialObj 	=	Booktrial::whereIn('_id', array_map('intval', explode(",",$this->ttt_trials)))->get();
			foreach ($trialObj as $key => $value) {
				// dd($value);exit();
				if(strtotime(date($value->schedule_date_time)) < time()){
					array_push($passed,$value);
				}
				else{
					array_push($upcoming,$value);	
				}
				$finders = array('passed_trial' => $passed, 'upcoming_trial' => $upcoming);
			}		
		}

		return $finders;
	}



	public function reviews(){

		return $this->hasMany('Customer', 'customer_id');
	}

	public function comments(){
		
		return $this->hasMany('Customer', 'customer_id');
	}
	
    public function loyaltyFinder(){
        return $this->belongsTo('Finder', 'loyalty.finder_id');
    }

	public function getloyaltyvoucherCategoryAttribute(){
		// return $this["loyalty"];
		if(!empty($this["loyalty"]["milestones"][0]["voucher"]["voucher_category"])){
			
			return $voucherCategory = VoucherCategory::where("_id", $this["loyalty"]["milestones"][0]["voucher"]["voucher_category"])->get(array("name", "image", "terms", "amount"));
		}
    }
    
    public static function maxId(){
        
        $model = "Customer";
        
        $identitycounter =  Identitycounter::where('model', $model)->where('field', '_id')->first();

        if(empty($identitycounter)){
            return $model::max('_id');
        }

        $identitycounter_count =  $identitycounter->count;
        
        $update = Identitycounter::where('model', $model)->where('field', '_id')->where('count', $identitycounter_count)->increment('count');

        if($update){
            Log::info("returning::".strval($identitycounter_count));
            return $identitycounter_count;
        }
        Log::info("reiterating::".strval($identitycounter_count));
        return  $model::maxId();
	}
	
    public function getIsHealthShownAttribute(){
        if(!empty($this->corporate_id)){
            return true;
        }
	}
	
	public function getCategoryInterestedAttribute(){
		$category_interested = [];
		if(is_array($this->finder_category_interested)){
			$reviews = Findercategory::wherein('_id', array_map('intval', $this->finder_category_interested))->get();
			foreach ($reviews as $key => $item) {
				array_push($category_interested, $item['name']);
			}
		}
		return $category_interested;
	}

}