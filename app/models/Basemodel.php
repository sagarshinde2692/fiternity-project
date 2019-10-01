<?php

class Basemodel extends \Moloquent {


	protected $guarded = array();
	
	public function scopeActive ($query){

		return $query->where('status','=','1');
	}

    public function scopeCreatedBetween($query,  $start_date, $end_date){
        return $query->where('created_at', '>=', new \DateTime( date("d-m-Y", strtotime( $start_date )) ))->where('created_at', '<=', new \DateTime( date("d-m-Y", strtotime( $end_date )) ));
    }
    
	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
    }
    
    public function scopeCustomerValidation($query,  $logged_in_customer_id){
        return $query;
        return $query->where(function ($query) use($logged_in_customer_id) { $query->orWhere('customer_id', $logged_in_customer_id)->orWhere("logged_in_customer_id", $logged_in_customer_id);});
    }


}