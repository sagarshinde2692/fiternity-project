<?php

class Campaign extends \Basemodel {

	protected $collection = "campaigns";
	public static $rules = [
		//'city_id' => 'required|numeric|unique:homepages'
		'city_id' => 'required|numeric'
	];

	public function city(){
		return $this->belongsTo('City')->select('name','slug','country_id','status');
	}
	public function categories(){
		return $this->hasMany('Findercategory');
	}

	protected $appends = array('feature_finders');

	public function getFeatureFindersAttribute(){

		$finders 	= 	[];
		// dd($this->campaign_finders);exit();
		if(!empty($this->featured_finders) && isset($this->featured_finders)){

			$findersObj 	=	Finder::active()->with('location')->whereIn('_id', array_map('intval', explode(",",$this->featured_finders)))->get();
			foreach ($findersObj as $key => $value) {
				// dd($value);exit();
				array_push($finders, $value);
			}		
		}

		return $finders;
	}

}