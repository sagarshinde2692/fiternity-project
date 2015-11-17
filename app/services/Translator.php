<?PHP namespace App\Services;

Use \App\Responsemodels\AutocompleteResponse;
Use \App\Responsemodels\AutocompleteResult;
// Translator methods to model API responses for client

class Translator {

	public  function __construct(){
		//empty constructor
	}

	public static function translate_autocomplete($es_autocomplete_response = array(), $city){
		$autcomplete_response = new AutocompleteResponse();
		if(isset($es_autocomplete_response['error'])){
			$autcomplete_response->status = 400;
			return $autcomplete_response;
		}
		else{		
			$autcomplete_response->meta = new \stdClass();			
			$autcomplete_response->meta->number_of_records = $es_autocomplete_response['hits']['total'];			
			foreach ($es_autocomplete_response['hits']['hits'] as $value) {
				$automodel = new AutocompleteResult();					
				$automodel->keyword = $value['fields']['autosuggestvalue'][0];
				$automodel->object->id = $value['_id'];
				$automodel->object->slug = $value['fields']['slug'][0];
				$automodel->object->location = new \stdClass();
				$automodel->object->location->city = $city;
				$automodel->object->location->area = $value['fields']['location'][0];
				$automodel->object->location->lat = 0.0;
				$automodel->object->location->long = 0.0;//$value['fields']['location'][0];
				$automodel->object->category = $value['fields']['inputcat1'][0];
				$automodel->object->tag = $value['fields']['inputcat'][0];
				$automodel->object_type = $value['fields']['type'][0];				
				array_push($autcomplete_response->results, $automodel);				
			}			
			return $autcomplete_response;
		}		
	}

	public static function translate_searchresults($es_searchresult_response){
		return 'search translotor called here';
	}

}

?>