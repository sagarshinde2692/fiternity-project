<?php

/** 
 * @author Shahaan
 * 
 */
class Product extends \Basemodel {
	protected $collection = "products";
	protected $connection = "mongodb2";
	
	// Add your validation rules here
	public static $rules = [ 
			'title' => 'required',
			'slug' => 'required' 
		// 'productcategory_id' => 'required',
		// 'servicecategory_id' => 'required'
	];
	public static function getSecondaryCategory() {
		$ops = [ 
				array (
						'$lookup' => [ 
								
								"from" => "productcategories",
								"localField" => "productcategory.secondary",
								"foreignField" => "_id",
								"as" => "secondary" 
						] 
				),
				array (
						'$project' => [ 
								"secondary" => '$secondary',
								"_id" => 0 
						] 
				) 
		];
		$mongoclient = new MongoClient ( config::get ( "database.connections.mongodb2.driver" ) . "://" . config::get ( "database.connections.mongodb2.host" ) . ":" . config::get ( "database.connections.mongodb2.port" ) );
		$c = $mongoclient->selectDB ( config::get ( "database.connections.mongodb2.database" ) )->selectCollection ( "products" );
		$rr = $c->aggregate ( $ops ) ['result'] [0];
		$mongoclient->close ();
		return $rr;
	}
	public function primarycategory() {
		return $this->belongsTo ( 'ProductCategory', "productcategory.primary" );
	}
	public function ratecard() {
		return $this->hasMany('ProductRatecard','product_id');
	}
	public function servicecategory() {
		// $tsd=$this->belongsTo('Servicecategory',"servicecategory.primary");
		return $tsd1 = $this->belongsToMany ( 'Servicecategory', "servicecategory.secondary" );
		// return ["servicecategory_primary"=>$tsd,"servicecategory_secondary"=>$tsd1];
	}
}