<?PHP namespace App\Responsemodels;

class FinderresultResponse {

	public  function __construct(){
		$this->results = new \stdClass();
		$this->results->resultlist = array();
		$this->results->aggregationlist = array();
		$this->meta = new \stdClass();
		$this->metadata = new \stdClass();	
	}
	
	public $results;
	public $meta;

}

class FinderResult{

	public function __construct(){
			$this->object = new FinderObject();
	}
	
	public $object;
	public $object_type;
	
}

class FinderObject {

	public function __construct(){	
		$this->contact = new \stdClass();	
		$this->geolocation = new \stdClass();
		$this->ozonetelno = new \stdClass();			
	}

	public $id;		
	public $category;
	public $categorytags;
	public $tag;
	public $location;
	public $locationtags;
	public $average_rating;
	public $membership_discount;
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
	public $price_range;
	public $popularity;
	public $special_offer_title;
	public $slug;
	public $status;
	public $title;
	public $total_rating_count;
	public $views;
	public $instantbooktrial_status;
	public $photos;
	public $locationcluster;
	public $price_rangeval;
	public $servicelist;
	public $servicephotos;
	public $ozonetelno;
	public $capoffer;
}

?>