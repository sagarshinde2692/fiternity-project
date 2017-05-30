<?php

/** 
 * ModelName : WalletTransaction.
 * Maintains a list of functions used for WalletTransaction.
 *
 * @author Renuka Aggarwal <renu17a@gmail.com>
 */

class WalletTransaction extends  \Basemodel {

	protected $collection = "wallettransactions";
	
	// Add your validation rules here
	public static $rules = [
		'customer_id' => 'required|integer|numeric',
		'amount' => 'required|integer|numeric',
		'type' => 'required|in:debit,credit',
	];

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}



}