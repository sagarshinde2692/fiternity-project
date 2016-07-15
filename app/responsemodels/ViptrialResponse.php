<?PHP 
namespace App\Responsemodels;

class ViptrialResponse {

	public  function __construct(){
		$this->results = new \stdClass();
		$this->results->resultlist = array();
		$this->results->aggregationlist = array();
		$this->meta = new \stdClass();	
	}
	
	public $results;
	public $meta;

}

class VipResult{

	public function __construct(){
			$this->object = new WorkoutSessionObject();
	}
	
	public $object;
	public $object_type;
	
}

class WorkoutSessionObject {

	public function __construct(){	
		$this->contact = new \stdClass();	
		$this->geolocation = new \stdClass();
		$this->ozonetelno = new \stdClass();			
	}

	public $id;		
	public $category;
	public $subcategory;
	public $findername;
	public $tag;
	public $location;
	public $average_rating;
	public $country;
	public $city;
	//public $city_id;
	public $info_service;
	public $info_service_list;
	public $contact;
	public $coverimage;
	public $commercial_type;
	public $business_type;
	public $fitternityno;
	public $facilities; //array
	public $logo;	
	public $geolocation;
	public $offerings;
	public $popularity;
	public $special_offer_title;
	public $slug;
	public $status;
	public $servicename;
	public $photos;
	public $locationcluster;
	public $servicephotos;
	public $ozonetelno;
	public $capoffer;
	public $price;
}

class saleRatecardResponse {

	public  function __construct(){
		$this->results = new \stdClass();
		$this->results->resultlist = array();
		$this->meta = new \stdClass();
	}

	public $results;
	public $meta;
}


class saleRatecardResult{

	public function __construct(){
		$this->object = new saleRatecardObject();
	}

	public $object;
	public $object_type;

}

class saleRatecardObject {

	public function __construct(){
		$this->contact = new \stdClass();
		$this->geolocation = new \stdClass();
		$this->ozonetelno = new \stdClass();
	}

	public $id;
	public $category;
	public $subcategory;
	public $findername;
	public $tag;
	public $location;
	public $average_rating;
	public $country;
	public $city;
	//public $city_id;
	public $info_service;
	public $info_service_list;
	public $contact;
	public $coverimage;
	public $commercial_type;
	public $business_type;
	public $fitternityno;
	public $facilities; //array
	public $logo;
	public $geolocation;
	public $offerings;
	public $popularity;
	public $special_offer_title;
	public $slug;
	public $status;
	public $servicename;
	public $photos;
	public $locationcluster;
	public $servicephotos;
	public $ozonetelno;
	public $capoffer;
	public $price;
	public $sale_ratecards;
}

?>