<?php

class City extends \Basemodel {

	protected $collection = "cities";

	public static $rules = array(
		'name' => 'required'
		);

	public function country(){
		return $this->belongsTo('Country','country_id');
	}


	public function findercategorys(){
		return $this->belongsToMany('Findercategory', null, 'cities', 'findercategorys');
	}

	public function categorytags(){
		return $this->belongsToMany('Findercategorytag', null, 'cities', 'categorytags');
	}


	public function locations(){
		return $this->belongsToMany('Location', null, 'cities', 'locations');
	}


	public function locationtags(){
		return $this->belongsToMany('Locationtag', null, 'cities', 'locationtags');
	}


	public function locationclusters(){
		return $this->hasMany('Locationcluster','city_id');
	}


	public function finders(){
		return $this->hasMany('Finder','city_id');
	}

	public function booktrials(){
		return $this->hasMany('Booktrial','booktrial_id');
	}
	public function campaigns(){
		return $this->hasMany('Campaigns','city_id');
	}

	public function homepage(){
		return $this->hasOnce('Homepage');
	}

	public function fitmaniadods(){
		return $this->hasMany('Fitmaniadod','city_id');
	}


}