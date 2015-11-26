<?PHP namespace App\Services;

Use \App\Responsemodels\AutocompleteResponse;
Use \App\Responsemodels\AutocompleteResult;
Use \App\Responsemodels\FinderresultResponse;
Use \App\Responsemodels\FinderResult;
Use \App\Responsemodels\FinderObject;
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
			$autcomplete_response->meta->total_records = $es_autocomplete_response['hits']['total'];			
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
		$finderresult_response = new FinderresultResponse();
		
		$finderresult_response->results->aggregationlist = new \stdClass();
		if(empty($es_searchresult_response['hits']['hits']))
		{
			$finderresult_response->results->resultlist = array();
			$finderresult_response->meta->total_records = 0;
		}
		else{
			$finderresult_response->meta->total_records = $es_searchresult_response['hits']['total'];
			foreach ($es_searchresult_response['hits']['hits'] as $resultv1) {
				$result = $resultv1['_source'];			
				$finder = new FinderResult();
				$finder->object_type = 'vendor';
				$resultobject = new FinderObject();			
				$resultobject->id = $result['_id'];				
				$resultobject->category = $result['category'];
				$resultobject->categorytags = $result['categorytags'];
				$resultobject->location = $result['location'];	
				$resultobject->locationtags = $result['locationtags'];
				$resultobject->average_rating = $result['average_rating'];
				$resultobject->membership_discount = $result['membership_discount'];
				$resultobject->country = $result['country'];
				$resultobject->city = $result['city'];
			//$resultobject->city_id = $result['city_id'];
				$resultobject->info_service = $result['info_service'];
			$resultobject->info_service_list = array();//$result['info_service_list'];
			$resultobject->contact->address = $result['contact']['address'];
			$resultobject->contact->email = $result['contact']['email'];
			$resultobject->contact->phone = $result['contact']['phone'];
			$resultobject->contact->website = $result['contact']['website'];
			$resultobject->coverimage = $result['coverimage'];
			$resultobject->commercial_type = $result['commercial_type'];
			$resultobject->finder_type = $result['finder_type'];
			$resultobject->business_type = $result['business_type'];
			$resultobject->fitternityno = $result['fitternityno'];
			$resultobject->facilities = $result['facilities'];
			$resultobject->logo = $result['logo'];
			$resultobject->geolocation->lat = $result['geolocation']['lat'];
			$resultobject->geolocation->long = $result['geolocation']['lon'];
			$resultobject->offerings = $result['offerings'];
			$resultobject->price_range = $result['price_range'];
			$resultobject->popularity = $result['popularity'];
			$resultobject->special_offer_title = $result['special_offer_title'];
			$resultobject->slug = $result['slug'];
			$resultobject->status = $result['status'];
			$resultobject->title = $result['title'];
			$resultobject->total_rating_count = $result['total_rating_count'];
			$resultobject->views = $result['views'];
			$resultobject->instantbooktrial_status = $result['instantbooktrial_status'];
			$resultobject->photos = $result['photos'];
			$resultobject->locationcluster = $result['locationcluster'];
			$resultobject->price_rangeval = $result['price_rangeval'];
			$resultobject->servicelist = isset($result['servicelist']) ? $result['servicelist'] : array();
			$resultobject->servicephotos = isset($result['servicephotos']) ? $result['servicephotos'] : array();
			$finder->object = $resultobject;
			array_push($finderresult_response->results->resultlist, $finder);			
		}
	}
	
	$aggs = $es_searchresult_response['aggregations'];

	$finderresult_response->results->aggregationlist->budget = array();
	foreach ($aggs['budget']['buckets'] as $bud) {
		$budval = new \stdClass();
		switch ($bud['key']) {
			case 'one':
			$budval->key = 'less than 1000';
			$budval->count = $bud['doc_count'];
			break;
			case 'two':
			$budval->key = '1000-2500';
			$budval->count = $bud['doc_count'];
			break;
			case 'three':
			$budval->key = '2500-5000';
			$budval->count = $bud['doc_count'];
			break;
			case 'four':
			$budval->key = '5000-7500';
			$budval->count = $bud['doc_count'];
			break;
			case 'five':
			$budval->key = '7500-15000';
			$budval->count = $bud['doc_count'];
			break;
			case 'six':
			$budval->key = '15000 & Above';
			$budval->count = $bud['doc_count'];
			break;				
			default:					
			break;
		}
		array_push($finderresult_response->results->aggregationlist->budget, $budval);
	}

	$finderresult_response->results->aggregationlist->filters = array();
	foreach ($aggs['facilities']['buckets'] as $fac) {
		$facval = new \stdClass();
		$facval->key = $fac['key'];
		$facval->count = $fac['doc_count'];
		array_push($finderresult_response->results->aggregationlist->filters, $facval);			
	}

	$finderresult_response->results->aggregationlist->locationcluster = array();
	foreach ($aggs['loccluster']['buckets'] as $cluster) {
		$clusterval = new \stdClass();
		$clusterval->key = $cluster['key'];
		$clusterval->count = $cluster['doc_count'];
		$clusterval->regions = array();
		foreach ($cluster['region']['buckets'] as $reg) {
			$regval = new \stdClass();
			$regval->key = $reg['key'];
			$regval->count = $reg['doc_count'];
			array_push($clusterval->regions, $regval);
		}
		array_push($finderresult_response->results->aggregationlist->locationcluster, $clusterval);
	}

	$finderresult_response->results->aggregationlist->offerings = array();

	foreach ($aggs['offerings']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($finderresult_response->results->aggregationlist->offerings, $offval);
	}
	return $finderresult_response;
}

}

?>