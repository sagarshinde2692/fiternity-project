<?php

/** 
 * ModelName : Finder.
 * Maintains a list of functions used for Finder.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Finder extends  \Basemodel {

	protected $collection = "finders";
	
	// Add your validation rules here
	public static $rules = [
	'title' => 'required',
	'lat' => 'required',
	'lon' => 'required',
	'country_id' => 'required',
	'city_id' => 'required',
	'location_id' => 'required',
	'category_id' => 'required',
	'finder_logo' => 'mimes:jpeg,png|image|max:2000',
	'finder_coverimage' => 'mimes:jpeg,png|image|max:2000',
	];

	public static $withoutAppends = false;

	public static $setAppends = [];
	

	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return self::$setAppends;
		}
		return parent::getArrayableAppends();
	}

	public static $update_rules = [
		'title' => 'sometimes|required|string'
	];

	protected $appends = array('finder_coverimage','commercial_type_status', 'business_type_status');

	// protected $dates = array('inoperational_dates');



	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}


	public function getFinderCoverimageAttribute(){

		$finder_coverimage = (trim($this->coverimage) != '') ? trim($this->coverimage) : 'default/'.$this->category_id.'-'.rand(1, 4).'.jpg';
		return $finder_coverimage;
	}


	public function getCommercialTypeStatusAttribute(){
		
		$val = '';
		if($this->commercial_type == 0){
			$val = 'Free';
		}elseif($this->commercial_type == 1){
			$val = 'Paid';
		}elseif($this->commercial_type == 2){
			$val = 'Free Special';
		}elseif($this->commercial_type == 3){
			$val = 'Commission On Sales';
		}
		return $val;
	}

	public function getBusinessTypeStatusAttribute(){

		$val = '';
		if($this->business_type == 0){
			$val = 'Non Infrastructure';
		}elseif($this->business_type == 1){
			$val = 'Infrastructure';
		}
		return $val;
	}

	public function getInoperationalDatesArrayAttribute(){
		
		$inopertaional_dates = isset($this->inoperational_dates) ? $this->inoperational_dates : [];

		$inopertaional_dates = array_map(function($value){
			return $value->sec; 
		}, $inopertaional_dates);

		return $inopertaional_dates;
		
	}



	public function user(){
		return $this->belongsTo('User');
	}

	public function blogs(){
		return $this->belongsToMany('Blog', null, 'finders', 'blogs');
	}

	public function findercollections(){
		return $this->belongsToMany('Findercollection', null, 'finders', 'findercollections');
	}


	public function category(){
		return $this->belongsTo('Findercategory');
	}	

	public function country(){
		return $this->belongsTo('Country');
	}	

	public function city(){
		return $this->belongsTo('City');
	}	

	
	public function locationcluster(){
		return $this->belongsTo('Locationcluster');
	}	

	public function location(){
		return $this->belongsTo('Location');
	}	
	
	public function categorytags(){
		return $this->belongsToMany('Findercategorytag', null, 'finders', 'categorytags');
	}

	public function locationtags(){
		return $this->belongsToMany('Locationtag', null, 'finders', 'locationtags');
	}

	public function offerings(){
		return $this->belongsToMany('Offering', null, 'finders', 'offerings');
	}
	
	public function facilities(){
		return $this->belongsToMany('Facility', null, 'finders', 'facilities');
	}

	public function scheduleservices(){
		return $this->hasMany('Schedulebooktrial');
	}

	public function booktrials(){
		return $this->hasMany('Booktrial','booktrial_id');
	}

	public function servicerates(){
		return $this->hasMany('Ratecard','finder_id');
	}

	public function services(){
		return $this->hasMany('Service','finder_id');
	}

	public function reviews(){
		return $this->hasMany('Review','finder_id');
	}

	public function ozonetelno(){
		return $this->hasOne('Ozonetelno','finder_id');
	}

	public function brand(){
		return $this->belongsTo('Brand');
	}

	public function knowlarityno(){
		return $this->hasMany('KnowlarityNo','vendor_id');
	}

	public function finders(){
		
		return $this->hasMany('Checkin');
	}

    public function scopeIntegrated ($query){
		return $query->where('status','=','1')->where('commercial_type', '!=', 0)->where('flags.state', '!=', 'closed')->where('flags.state', '!=', 'temporarily_shut')->where(function($query){$query->orWhere('membership', '!=', 'disable')->orWhere('trial', '!=', 'disable');});
	}

	public function scopeIntegratedMembership ($query){
		return $query->where('status','=','1')->where('commercial_type', '!=', 0)->where('flags.state', '!=', 'closed')->where('flags.state', '!=', 'temporarily_shut')->where('membership', '!=', 'disable');
	}

	public function scopeIntegratedTrial ($query){
		return $query->where('status','=','1')->where('commercial_type', '!=', 0)->where('flags.state', '!=', 'closed')->where('flags.state', '!=', 'temporarily_shut')->where('trial', '!=', 'disable');
	}

}