<?php

/** 
 * ModelName : Wallet.
 * Maintains a list of functions used for Wallet.
 *
 * @author Renuka Aggarwal <renu17a@gmail.com>
 */

class Wallet extends  \Basemodel {

	protected $collection = "wallets";
	
	// Add your validation rules here
	public static $rules = [
		'customer_id' => 'required|integer|numeric',
		'amount' => 'required|integer|numeric',
		'entry'=>'required|in:debit,credit',
		'type'=>'required'
		//'validity'=>'required'
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