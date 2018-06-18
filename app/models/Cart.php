<?php

/** 
 * @author Shahaan
 * 
 */

class Cart extends  \Basemodel {

	protected $collection = "carts";
	
	protected $connection = "mongodb2";
	
	// Add your validation rules here
	public static $rules = [
	'title' => 'required',
	'slug' => 'required',
// 	'productcategory_id' => 'required',
// 	'servicecategory_id' => 'required'
	];

	public static $withoutAppends = false;
	
	public static $setAppends = [];
	
	
	protected function getArrayableAppends()
	{
		if(self::$withoutAppends){
			return self::$setAppends;
		}
		return parent::getArrayableAppends();
	}
	
	protected $appends = array('rc');

	public function customer()
	{
		return $this->belongsTo('Customer',"customer_id");
	}
	
	public function getRcAttribute()
	{
		$tp=$this->products;
		$rc=array_column($tp, "ratecard_id");
		$pro=array_column($tp, "product_id");
		$combined=["rc"=>ProductRatecard::whereIn("_id",$rc)->get(["price"]),"pc"=>Product::whereIn("_id",$pro)->get(["description"])];		
		$main=[];
		foreach ($combined['rc'] as &$value)
		{
			$main[(string) $value->_id]=$value;
		}
		foreach ($combined['pc'] as &$value)
		{
			$main[(string) $value->_id]=$value;
		}	
		$tpa=[];
		foreach ($tp as $key => &$value)
		{
			array_push($tpa,["amount"=>$value['amount'],"quantity"=>$value['quantity'],"ratecard_id"=>$main[(string) $value['ratecard_id']],"product_id"=>$main[(string) $value['product_id']]]);
		}
		return array_values($tpa); 
		
	}
}