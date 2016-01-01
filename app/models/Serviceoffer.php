<?php

class Serviceoffer extends \Basemodel {

	public static $rules = [
	'ratecard_id' => 'required|numeric'
	];

	protected $dates = array('start_date','end_date');

	protected $appends = array('service_offer_ratecard');

	public function setOrderAttribute($value){
		$this->attributes['order'] = intval($value);
	}

	public function setPriceAttribute($value){
		$this->attributes['price'] = intval($value);
	}

	public function setLimitAttribute($value){
		$this->attributes['limit'] = intval($value);
	}

	public function setFinderIdAttribute($value){
		$this->attributes['finder_id'] = intval($value);
	}
	
	public function setServiceIdAttribute($value){
		$this->attributes['service_id'] = intval($value);
	}

	public function setRatecardIdAttribute($value){
		$this->attributes['ratecard_id'] = intval($value);
	}

	public function setOrderingAttribute($value){
		$this->attributes['ordering'] = intval($value);
	}


	public function getServiceOfferRatecardAttribute(){

		$ratecard 	= 	[];
		if(!empty($this->ratecard_id) && isset($this->ratecard_id)){
			$ratecardsarr 	= 	Ratecard::find(intval($this->ratecard_id));
		}

		if(!empty($ratecardsarr) && isset($ratecardsarr)){
			if(intval($ratecardsarr['validity'])%360 == 0){
				$ratecardsarr['validity']  = intval(intval($ratecardsarr['validity'])/360);
				if(intval($ratecardsarr['validity']) > 1){
					$ratecardsarr['validity_type'] = "years";
				}else{
					$ratecardsarr['validity_type'] = "year";
				}
			}

			if(intval($ratecardsarr['validity'])%30 == 0){
				$ratecardsarr['validity']  = intval(intval($ratecardsarr['validity'])/30);
				if(intval($ratecardsarr['validity']) > 1){
					$ratecardsarr['validity_type'] = "months";
				}else{
					$ratecardsarr['validity_type'] = "month";
				}
			}
			$ratecard = $ratecardsarr;
		}

		return $ratecard;
	}





	public function finder(){
		return $this->belongsTo('Finder','finder_id');
	}

	public function service(){
		return $this->belongsTo('Service','service_id');
	}

	public function ratecard(){
		return $this->belongsTo('Ratecard','ratecard_id');
	}







}