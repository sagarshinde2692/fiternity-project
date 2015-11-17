<?PHP namespace App\Services;

Use \App\Responsemodels\AutocompleteResponse;
Use \App\Responsemodels\AutocompleteResult;
// Translator methods to model API responses for client

class Translator {

	public  function __construct(){
		//empty constructor
	}

	public static function translate_autocomplete($es_autocomplete_response = array()){
		$autcomplete_response = new AutocompleteResponse();
		if(isset($es_autocomplete_response['error'])){
			$autcomplete_response->status = 400;
			return $autcomplete_response;
		}
		else{					
			$autcomplete_response->total = $es_autocomplete_response['hits']['total'];
			foreach ($es_autocomplete_response['hits']['hits'] as $value) {
				$automodel = new AutocompleteResult();					
				$automodel->autosuggestvalue = $value['fields']['autosuggestvalue'][0];
				$automodel->location = $value['fields']['location'][0];
				$automodel->type = $value['fields']['type'][0];
				$automodel->slug = $value['fields']['slug'][0];
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