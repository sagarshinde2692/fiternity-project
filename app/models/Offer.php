<?php

class Offer extends \Basemodel {

    protected $connection = 'mongodb2';

    protected $collection = "offers";

    protected $dates = array('start_date','end_date');


	public function setOrderAttribute($value){
		$this->attributes['order'] = intval($value);
	}
	
	public function setCityIdAttribute($value){
		$this->attributes['city_id'] = intval($value);
	}

	public function setOrderingAttribute($value){
		$this->attributes['ordering'] = intval($value);
	}

	public function city(){
		return $this->belongsTo('City');
	}

    public function ratecard(){
        return $this->belongsTo('Ratecard');
    }

    public function finder(){
        return $this->belongsTo('Finder', 'vendor_id');
    }

    public function service(){
        return $this->belongsTo('Service', 'vendorservice_id');
    }


}
