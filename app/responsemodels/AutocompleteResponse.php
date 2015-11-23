<?PHP namespace App\Responsemodels;

class AutocompleteResponse {

	public  function __construct(){
		$this->results = array();
		$this->meta = new \stdClass();		
	}
	
	public $results;
	public $meta;

}

class AutocompleteResult{

	public function __construct(){
		$this->object = new Autoobject();		
	}

	public $keyword;
	public $object;
	public $object_type;
	
}

class Autoobject {

	public function __construct(){		
		//empty constructor
	}

	public $id;
	public $slug;
	public $location;
	public $category;
	public $tag;
}

?>