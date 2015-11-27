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
	protected $appends = array('uber_trial');
	
	// Add your validation rules here
	public static $rules = [
	    'name' => 'required|max:255',
	    'email' => 'required|email|unique:customers|max:255',
	    'password' => 'required|min:6|max:20|confirmed',
	    'password_confirmation' => 'required|min:6|max:20',
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



	public function reviews(){

		return $this->hasMany('Customer', 'customer_id');
	}

	public function comments(){
		
		return $this->hasMany('Customer', 'customer_id');
	}
	

}