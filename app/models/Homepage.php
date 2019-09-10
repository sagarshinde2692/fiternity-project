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
		
	protected $appends = array();

	
	
	
	public function getRcAttribute()
	{
		$home=$this->home;
		$rc=array_column($home, "ratecard_id");
		$pro=array_column($home, "product_id");
		Product::$withoutAppends=true;
		/* $rates=ProductRatecard::raw(function($collection)
		{
			return $collection->aggregate(
					[
							['$group' => ['_id' => ["p_id"=>'$product_id','color'=>'$color'],'details' => ['$push'=>['ratecards'=>'$_id']]]],
							['$match' => ['details.0' => ['$exists'=>true]]],
							['$project' => ["rcs"=>['$arrayElemAt' => ['$details',0]]]]		
					]);
		});
		(!empty($rates)&&!empty($rates['result']))?
		$rc=array_values(array_intersect(array_column(array_column($rates['result'], 'rcs'), 'ratecards'),$rc)):""; */
		$combined=["rc"=>ProductRatecard::active()->whereIn("_id",$rc)->get(["title","price"]),"pc"=>Product::active()->whereIn("_id",$pro)->with('primarycategory')->get(["title",'productcategory','slug'])];
		
		$rateMain=[];
		$productMain=[];
		foreach ($combined['rc'] as &$value)
			$rateMain[$value->_id]=$value;
		foreach ($combined['pc'] as &$value)
			$productMain[$value->_id]=$value;

		$tpa=[];
		foreach ($home as $key => &$value)
		{
			$rc1=(!empty($value)&&!empty($rateMain)&&!empty($value['ratecard_id'])&&!empty($rateMain[$value['ratecard_id']]))?$rateMain[$value['ratecard_id']]:"";
			$pc1=(!empty($value)&&!empty($productMain)&&!empty($value['product_id'])&&!empty($productMain[$value['product_id']]))?$productMain[$value['product_id']]:"";
			if(!empty($rc1)&&!empty($pc1))
				array_push($tpa,["ratecard"=>$rateMain[$value['ratecard_id']],"product"=>$productMain[$value['product_id']]]);
		}
			
		return array_values($tpa);
	}
}