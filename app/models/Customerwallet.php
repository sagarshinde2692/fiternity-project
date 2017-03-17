<?php

/** 
 * ModelName : Customerwallet.
 * Maintains a list of functions used for Customerwallet.
 *
 * @author Renuka Aggarwal <renu17a@gmail.com>
 */

class Customerwallet extends  \Basemodel {

	protected $collection = "customerwallets";
	
	// Add your validation rules here
	public static $rules = [
		'customer_id' => 'required|integer|numeric',
		'order_id' => 'required|integer|numeric',
		'amount' => 'required|integer|numeric',
		'type' => 'in:DEBIT,CREDIT,REFUND,CASHBACK,REFERRAL',
	    'description' => 'required'
	];

	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}



//	protected $appends = array('customer');
//
//	public function getCustomerAttribute(){
//		$customer = Customer::where('_id',$this->customer_id)->first(array('name','picture','email','contact_no' ));
//		return $customer;
//	}
//
//	public function order(){
//		return $this->belongsTo('Order');
//	}
//
//	public function customer(){
//		return $this->belongsTo('Customer');
//	}



}