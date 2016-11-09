<?php

class Booktrial extends \Basemodel {


	protected $collection = "booktrials";

	protected $dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date');

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}

	public function setCodeAttribute($value){

		$this->attributes['code'] = (string) $value;
	}

	
	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

	public function city(){
		return $this->belongsTo('City','city_id');
	}

	public function category(){
		return $this->belongsTo('Findercategory','finder_category_id');
	}
	public function invite(){
		return $this->hasMany('Invite','referrer_booktrial_id');
	}

}
