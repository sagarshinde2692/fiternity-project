<?php

class Booktrial extends \Basemodel {


	protected $collection = "booktrials";

	protected $dates = array('schedule_date','schedule_date_time');

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}

	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

	public function city(){
		return $this->belongsTo('City','city_id');
	}



}