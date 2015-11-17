<?PHP namespace App\Responsemodels;

class AutocompleteResponse {

	public  function __construct(){
		$this->results = array();		
	}

	public $total;
	public $results;
	public $status;
	public $size;
	public $from;

}

class AutocompleteResult{

	public function __construct(){
		//empty constructor
	}

	public $autosuggestvalue;
	public $location;
	public $type;
	public $slug;
}

?>