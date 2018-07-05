<?php

class Homepage extends \Basemodel {

	protected $collection = "homepages";

	public static $rules = [
		//'city_id' => 'required|numeric|unique:homepages'
		'city_id' => 'required|numeric'
	];

	public function city(){
		return $this->belongsTo('City');
	}

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

	
	
	
	public function getRcAttribute()
	{
		$home=$this->home;
		$rc=array_column($home, "ratecard_id");
		$pro=array_column($home, "product_id");
		Product::$withoutAppends=true;
		$combined=["rc"=>ProductRatecard::whereIn("_id",$rc)->get(["title","price"]),"pc"=>Product::whereIn("_id",$pro)->with('primarycategory')->get(["title",'productcategory'])];
		$rateMain=[];
		$productMain=[];
		foreach ($combined['rc'] as &$value)
			$rateMain[$value->_id]=$value;
		foreach ($combined['pc'] as &$value)
			$productMain[$value->_id]=$value;

		$tpa=[];
		foreach ($home as $key => &$value)
			array_push($tpa,["ratecard"=>$rateMain[$value['ratecard_id']],"product"=>$productMain[$value['product_id']]]);
		return array_values($tpa);
	}
}