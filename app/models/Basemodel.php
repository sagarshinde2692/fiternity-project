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
    
    public function scopeCustomerValidation($query,  $customer_email){
        // return $query;
        return $query->where(function ($query) use($customer_email) { $query->orWhere('customer_email', $customer_email)->orWhere('logged_in_customer_email', $customer_email);});
    }


}