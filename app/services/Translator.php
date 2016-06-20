<?PHP namespace App\Services;

Use \App\Responsemodels\AutocompleteResponse;
Use \App\Responsemodels\AutocompleteResult;
Use \App\Responsemodels\FinderresultResponse;
Use \App\Responsemodels\FinderResult;
Use \App\Responsemodels\FinderObject;
Use \App\Responsemodels\ViptrialResponse;
Use \App\Responsemodels\VipResult;
Use \App\Responsemodels\WorkoutSessionObject;

// Translator methods to model API responses for client

class Translator {

	public  function __construct(){
		//empty constructor
	}

	public static function translate_autocomplete($es_autocomplete_response = array(), $city){
		$autcomplete_response = new AutocompleteResponse();
		if(isset($es_autocomplete_response['error'])){
			$autcomplete_response->status = 500;
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
				$resultobject->categorytags = empty($result['categorytags']) ? array() : $result['categorytags'];
				$resultobject->location = $result['location'];	
				$resultobject->locationtags = empty($result['locationtags']) ? array() : $result['locationtags'];
				$resultobject->average_rating = $result['average_rating'];
				$resultobject->membership_discount = $result['membership_discount'];
				$resultobject->country = $result['country'];
				$resultobject->city = $result['city'];
			//$resultobject->city_id = $result['city_id'];
				$resultobject->info_service = $result['info_service'];
			$resultobject->info_service_list = array();//$result['info_service_list'];
			$resultobject->contact->address = $result['contact']['address'];
			$resultobject->contact->email = isset($result['contact']['email']) ? $result['contact']['email'] : "";
			$resultobject->contact->phone = ''; //$result['contact']['phone'];
			$resultobject->contact->website = $result['contact']['website'];
			$resultobject->coverimage = $result['coverimage'];
			$resultobject->commercial_type = $result['commercial_type'];
			$resultobject->finder_type = $result['finder_type'];
			$resultobject->business_type = $result['business_type'];
			$resultobject->fitternityno = '+917506122637';
			$resultobject->facilities = empty($result['facilities']) ? array() : $result['facilities'];
			$resultobject->logo = $result['logo'];
			$resultobject->geolocation->lat = $result['geolocation']['lat'];
			$resultobject->geolocation->long = $result['geolocation']['lon'];
			$resultobject->offerings = empty($result['offerings']) ? array() : $result['offerings'];
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
			$resultobject->ozonetelno->phone_number = (isset($result['ozonetelno']) && isset($result['ozonetelno']['phone_number'])) ? $result['ozonetelno']['phone_number'] : "";
			$resultobject->ozonetelno->extension = (isset($result['ozonetelno']) && isset($result['ozonetelno']['extension'])) ? $result['ozonetelno']['extension'] : "";
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
	
	if(isset($aggs['trialdays'])){


		$finderresult_response->results->aggregationlist->trialdays = array();

		foreach ($aggs['trialdays']['buckets'] as $off){
			$offval = new \stdClass();
			$offval->key = $off['key'];
			$offval->count = $off['doc_count'];
			array_push($finderresult_response->results->aggregationlist->trialdays, $offval);
		}
	}
	return $finderresult_response;
}

public static function translate_searchresultskeywordsearch($es_searchresult_response){
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
			$resultobject->categorytags = empty($result['categorytags']) ? array() : $result['categorytags'];
			$resultobject->location = $result['location'];	
			$resultobject->locationtags = empty($result['locationtags']) ? array() : $result['locationtags'];
			$resultobject->average_rating = $result['average_rating'];
			$resultobject->membership_discount = $result['membership_discount'];
			$resultobject->country = $result['country'];
			$resultobject->city = $result['city'];
			//$resultobject->city_id = $result['city_id'];
			$resultobject->info_service = $result['info_service'];
			$resultobject->info_service_list = array();//$result['info_service_list'];
			$resultobject->contact->address = $result['contact']['address'];
			$resultobject->contact->email = isset($result['contact']['email']) ? $result['contact']['email'] : '';
			$resultobject->contact->phone = ''; //$result['contact']['phone'];
			$resultobject->contact->website = $result['contact']['website'];
			$resultobject->coverimage = $result['coverimage'];
			$resultobject->commercial_type = $result['commercial_type'];
			$resultobject->finder_type = $result['finder_type'];
			$resultobject->business_type = $result['business_type'];
			$resultobject->fitternityno = '+917506122637';
			$resultobject->facilities = empty($result['facilities']) ? array() : $result['facilities'];
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
			$resultobject->ozonetelno->phone_number = (isset($result['ozonetelno']) && isset($result['ozonetelno']['phone_number'])) ? $result['ozonetelno']['phone_number'] : '';
			$resultobject->ozonetelno->extension = (isset($result['ozonetelno']) && isset($result['ozonetelno']['extension'])) ? $result['ozonetelno']['extension'] : '';
			$finder->object = $resultobject;
			array_push($finderresult_response->results->resultlist, $finder);			
		}
	}
	
	$aggs = $es_searchresult_response['aggregations'];

	$finderresult_response->results->aggregationlist->budget = array();
	foreach ($aggs['filtered_budgets']['budgets']['buckets'] as $bud) {
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
	foreach ($aggs['filtered_facilities']['facilities']['buckets'] as $fac) {
		$facval = new \stdClass();
		$facval->key = $fac['key'];
		$facval->count = $fac['doc_count'];
		array_push($finderresult_response->results->aggregationlist->filters, $facval);			
	}

	$finderresult_response->results->aggregationlist->locationcluster = array();
	foreach ($aggs['filtered_locations']['loccluster']['buckets'] as $cluster) {
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

	foreach ($aggs['filtered_offerings']['offerings']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($finderresult_response->results->aggregationlist->offerings, $offval);
	}

	$finderresult_response->results->aggregationlist->locationtags = array();
	foreach ($aggs['filtered_locationtags']['locationstags']['buckets'] as $locs){
		$locval = new \stdClass();
		$locval->key = $locs['key'];
		$locval->count = $locs['doc_count'];
		array_push($finderresult_response->results->aggregationlist->locationtags, $locval);
	}

	$finderresult_response->results->aggregationlist->category = array();	
	foreach ($aggs['category']['buckets'] as $cat){
		$catval = new \stdClass();
		$catval->key = $cat['key'];
		$catval->count = $cat['doc_count'];
		array_push($finderresult_response->results->aggregationlist->category, $catval);
	}

	return $finderresult_response;
}

public static function translate_searchresultsv2($es_searchresult_response){
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
			$resultobject->categorytags = empty($result['categorytags']) ? array() : $result['categorytags'];
			$resultobject->location = $result['location'];	
			$resultobject->locationtags = empty($result['locationtags']) ? array() : $result['locationtags'];
			$resultobject->average_rating = $result['average_rating'];
			$resultobject->membership_discount = $result['membership_discount'];
			$resultobject->country = $result['country'];
			$resultobject->city = $result['city'];
			//$resultobject->city_id = $result['city_id'];
			$resultobject->capoffer = isset($result['capoffer']) ? $result['capoffer'] : false;
			$resultobject->info_service = $result['info_service'];
			$resultobject->info_service_list = array();//$result['info_service_list'];
			$resultobject->contact->address = $result['contact']['address'];
			$resultobject->contact->email = isset($result['contact']['email']) ? $result['contact']['email'] : "";
			$resultobject->contact->phone = ''; //$result['contact']['phone'];
			$resultobject->contact->website = $result['contact']['website'];
			$resultobject->coverimage = $result['coverimage'];
			$resultobject->commercial_type = $result['commercial_type'];
			$resultobject->finder_type = $result['finder_type'];
			$resultobject->business_type = $result['business_type'];
			$resultobject->fitternityno = '+917506122637';
			$resultobject->facilities = empty($result['facilities']) ? array() : $result['facilities'];
			$resultobject->logo = $result['logo'];
			$resultobject->geolocation->lat = $result['geolocation']['lat'];
			$resultobject->geolocation->long = $result['geolocation']['lon'];
			$resultobject->offerings = empty($result['offerings']) ? array() : $result['offerings'];
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
			$resultobject->ozonetelno->phone_number = (isset($result['ozonetelno']) && isset($result['ozonetelno']['phone_number'])) ? $result['ozonetelno']['phone_number'] : "";
			$resultobject->ozonetelno->extension = (isset($result['ozonetelno']) && isset($result['ozonetelno']['extension'])) ? $result['ozonetelno']['extension'] : "";
			$finder->object = $resultobject;
			array_push($finderresult_response->results->resultlist, $finder);			
		}
	}
	
	$aggs = $es_searchresult_response['aggregations'];

	$finderresult_response->results->aggregationlist->budget = array();
	$budval0 = new \stdClass();
	$budval1 = new \stdClass();
	$budval2 = new \stdClass();
	$budval3 = new \stdClass();
	$budval4 = new \stdClass();
	$budval5 = new \stdClass();
	foreach ($aggs['filtered_budgets']['budgets']['buckets'] as $bud) {
		switch ($bud['key']) {
			case 'one':
			$budval0->key = 'less than 1000';
			$budval0->count = $bud['doc_count'];			

			break;
			case 'two':
			$budval1->key = '1000-2500';
			$budval1->count = $bud['doc_count'];
			
			break;
			case 'three':
			$budval2->key = '2500-5000';
			$budval2->count = $bud['doc_count'];
			
			break;
			case 'four':
			$budval3->key = '5000-7500';
			$budval3->count = $bud['doc_count'];
			
			break;
			case 'five':
			$budval4->key = '7500-15000';
			$budval4->count = $bud['doc_count'];
			
			break;
			case 'six':
			$budval5->key = '15000 & Above';
			$budval5->count = $bud['doc_count'];			
			break;				
			default:					
			break;
		}
	}
	array_push($finderresult_response->results->aggregationlist->budget, $budval0);
	array_push($finderresult_response->results->aggregationlist->budget, $budval1);
	array_push($finderresult_response->results->aggregationlist->budget, $budval2);
	array_push($finderresult_response->results->aggregationlist->budget, $budval3);
	array_push($finderresult_response->results->aggregationlist->budget, $budval4);
	array_push($finderresult_response->results->aggregationlist->budget, $budval5);

	$finderresult_response->results->aggregationlist->filters = array();
	foreach ($aggs['filtered_facilities']['facilities']['buckets'] as $fac) {
		$facval = new \stdClass();
		$facval->key = $fac['key'];
		$facval->count = $fac['doc_count'];
		array_push($finderresult_response->results->aggregationlist->filters, $facval);			
	}

	$finderresult_response->results->aggregationlist->locationcluster = array();
	foreach ($aggs['filtered_locations']['loccluster']['buckets'] as $cluster) {
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

	foreach ($aggs['filtered_offerings']['offerings']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($finderresult_response->results->aggregationlist->offerings, $offval);
	}

	$finderresult_response->results->aggregationlist->locationtags = array();

	
	foreach ($aggs['filtered_locationtags']['offerings']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($finderresult_response->results->aggregationlist->locationtags, $offval);
	}
	
	if(isset($aggs['filtered_trials']['trialdays'])){
		$finderresult_response->results->aggregationlist->trialdays = array();

		foreach ($aggs['filtered_trials']['trialdays']['buckets'] as $off){
			$offval = new \stdClass();
			$offval->key = $off['key'];
			$offval->count = $off['doc_count'];
			array_push($finderresult_response->results->aggregationlist->trialdays, $offval);
		}
	}
	return $finderresult_response;
}


public static function translate_searchresultsv3($es_searchresult_response){
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
			$resultobject->categorytags = empty($result['categorytags']) ? array() : $result['categorytags'];
			$resultobject->location = $result['location'];	
			$resultobject->locationtags = empty($result['locationtags']) ? array() : $result['locationtags'];
			$resultobject->average_rating = $result['average_rating'];
			$resultobject->membership_discount = $result['membership_discount'];
			$resultobject->country = $result['country'];
			$resultobject->city = $result['city'];
			//$resultobject->city_id = $result['city_id'];
			$resultobject->info_service = $result['info_service'];
			$resultobject->info_service_list = array();//$result['info_service_list'];
			$resultobject->contact->address = $result['contact']['address'];
			$resultobject->contact->email = isset($result['contact']['email']) ? $result['contact']['email'] : "";
			$resultobject->contact->phone = ''; //$result['contact']['phone'];
			$resultobject->contact->website = $result['contact']['website'];
			$resultobject->coverimage = $result['coverimage'];
			$resultobject->commercial_type = $result['commercial_type'];
			$resultobject->finder_type = $result['finder_type'];
			$resultobject->business_type = $result['business_type'];
			$resultobject->fitternityno = '+917506122637';
			$resultobject->facilities = empty($result['facilities']) ? array() : $result['facilities'];
			$resultobject->logo = $result['logo'];
			$resultobject->geolocation->lat = $result['geolocation']['lat'];
			$resultobject->geolocation->long = $result['geolocation']['lon'];
			$resultobject->offerings = empty($result['offerings']) ? array() : $result['offerings'];
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
			$resultobject->ozonetelno->phone_number = (isset($result['ozonetelno']) && isset($result['ozonetelno']['phone_number'])) ? $result['ozonetelno']['phone_number'] : "";
			$resultobject->manual_trial_bool = (isset($result['manual_trial_bool'])) ? $result['manual_trial_bool'] : "";
			$resultobject->ozonetelno->extension = (isset($result['ozonetelno']) && isset($result['ozonetelno']['extension'])) ? $result['ozonetelno']['extension'] : "";
			$finder->object = $resultobject;
			array_push($finderresult_response->results->resultlist, $finder);			
		}
	}
	
	$aggs = $es_searchresult_response['aggregations'];

	$finderresult_response->results->aggregationlist->budget = array();
	$budval0 = new \stdClass();
	$budval1 = new \stdClass();
	$budval2 = new \stdClass();
	$budval3 = new \stdClass();
	$budval4 = new \stdClass();
	$budval5 = new \stdClass();
	foreach ($aggs['filtered_budgets']['budgets']['buckets'] as $bud) {
		switch ($bud['key']) {
			case 'one':
			$budval0->key = 'less than 1000';
			$budval0->count = $bud['doc_count'];			

			break;
			case 'two':
			$budval1->key = '1000-2500';
			$budval1->count = $bud['doc_count'];
			
			break;
			case 'three':
			$budval2->key = '2500-5000';
			$budval2->count = $bud['doc_count'];
			
			break;
			case 'four':
			$budval3->key = '5000-7500';
			$budval3->count = $bud['doc_count'];
			
			break;
			case 'five':
			$budval4->key = '7500-15000';
			$budval4->count = $bud['doc_count'];
			
			break;
			case 'six':
			$budval5->key = '15000 & Above';
			$budval5->count = $bud['doc_count'];			
			break;				
			default:					
			break;
		}
	}
	array_push($finderresult_response->results->aggregationlist->budget, $budval0);
	array_push($finderresult_response->results->aggregationlist->budget, $budval1);
	array_push($finderresult_response->results->aggregationlist->budget, $budval2);
	array_push($finderresult_response->results->aggregationlist->budget, $budval3);
	array_push($finderresult_response->results->aggregationlist->budget, $budval4);
	array_push($finderresult_response->results->aggregationlist->budget, $budval5);

	$finderresult_response->results->aggregationlist->filters = array();
	foreach ($aggs['filtered_facilities']['facilities']['buckets'] as $fac) {
		$facval = new \stdClass();
		$facval->key = $fac['key'];
		$facval->count = $fac['doc_count'];
		array_push($finderresult_response->results->aggregationlist->filters, $facval);			
	}

	$finderresult_response->results->aggregationlist->locationcluster = array();
	foreach ($aggs['filtered_locations']['loccluster']['buckets'] as $cluster) {
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

	foreach ($aggs['filtered_offerings']['offerings']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($finderresult_response->results->aggregationlist->offerings, $offval);
	}

	$finderresult_response->results->aggregationlist->locationtags = array();

	
	foreach ($aggs['filtered_locationtags']['offerings']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($finderresult_response->results->aggregationlist->locationtags, $offval);
	}
	
	if(isset($aggs['filtered_trials']['level1'])){
		$finderresult_response->results->aggregationlist->trialdays = array();

		foreach ($aggs['filtered_trials']['level1']['level2']['daysaggregator']['buckets'] as $off){
			$offval = new \stdClass();
			$offval->key = $off['key'];
			$offval->count = $off['backtolevel1']['backtorootdoc']['doc_count'];
			array_push($finderresult_response->results->aggregationlist->trialdays, $offval);
		}
	}
	return $finderresult_response;
}

public static function translate_vip_trials($es_searchresult_response){

	$vip_trial_response = new ViptrialResponse();

	$vip_trial_response->results->aggregationlist = new \stdClass();

	if(empty($es_searchresult_response['hits']['hits']))
	{
		$vip_trial_response->results->resultlist = array();
		$vip_trial_response->meta->total_records = 0;
	}
	else{
		$vip_trial_response->meta->total_records = $es_searchresult_response['hits']['total'];

		foreach ($es_searchresult_response['hits']['hits'] as $resultv1) {
			$result = $resultv1['_source'];			
			$finder = new VipResult();
			$finder->object_type = 'vendor';
			$resultobject = new WorkoutSessionObject();		

			$resultobject->id = $result['service_id'];				
			$resultobject->category = $result['category'];
			$resultobject->subcategory = empty($result['subcategory']) ? array() : $result['subcategory'];
			$resultobject->location = $result['location'];	
			$resultobject->findername = $result['findername'];
			$resultobject->finderslug = $result['finderslug'];
			$resultobject->city = $result['city'];
			$resultobject->name = $result['name'];
			$resultobject->slug = $result['slug'];
			$resultobject->workoutintensity = $result['workout_intensity'];
			$resultobject->locationcluster = $result['locationcluster'];
			$resultobject->rating = $result['rating'];
			$resultobject->findercoverimage = $result['finder_coverimage'];
			$resultobject->workout_session_schedules_price = $result['workout_session_schedules_price'];
			$resultobject->workout_session_schedules_weekday = $result['workout_session_schedules_weekday'];
			$resultobject->workout_session_schedules_end_time_24_hrs = $result['workout_session_schedules_end_time_24_hrs'];
			$resultobject->workout_session_schedules_start_time_24_hrs = $result['workout_session_schedules_start_time_24_hrs'];
			$resultobject->workout_session_schedules_end_time = $result['workout_session_schedules_end_time'];
			$resultobject->workout_session_schedules_start_time = $result['workout_session_schedules_start_time'];
			$resultobject->finder_gallery = $result['finder_gallary'];
			$resultobject->finder_address = $result['finder_address'];
			$resultobject->service_address = $result['service_address'];
			$resultobject->finder_slug = $result['finderslug'];

			$finder->object = $resultobject;
			array_push($vip_trial_response->results->resultlist, $finder);			
		}
	}
	
	$aggs = $es_searchresult_response['aggregations'];

	
	$vip_trial_response->results->aggregationlist->time_range = array();

	foreach ($aggs['filtered_time']['time_range']['buckets'] as $fac) {
		$facval = new \stdClass();
		$facval->key = $fac['key'];
		$facval->count = $fac['doc_count'];
		array_push($vip_trial_response->results->aggregationlist->time_range, $facval);			
	}

	$vip_trial_response->results->aggregationlist->category = array();

	foreach ($aggs['filtered_category']['category']['buckets'] as $fac) {
		$facval = new \stdClass();
		$facval->key = $fac['key'];
		$facval->count = $fac['doc_count'];
		array_push($vip_trial_response->results->aggregationlist->category, $facval);			
	}

	$vip_trial_response->results->aggregationlist->locationcluster = array();

	foreach ($aggs['filtered_locations']['loccluster']['buckets'] as $cluster) {
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
		array_push($vip_trial_response->results->aggregationlist->locationcluster, $clusterval);
	}

	$vip_trial_response->results->aggregationlist->locationtags = array();

	foreach ($aggs['filtered_region_tag']['locationtags']['buckets'] as $fac) {
		$facval = new \stdClass();
		$facval->key = $fac['key'];
		$facval->count = $fac['doc_count'];
		array_push($vip_trial_response->results->aggregationlist->locationtags, $facval);			
	}


	$vip_trial_response->results->aggregationlist->subcategory = array();

	foreach ($aggs['filtered_subcategory']['subcategory']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($vip_trial_response->results->aggregationlist->subcategory, $offval);
	}

	$vip_trial_response->results->aggregationlist->workout = array();

	
	foreach ($aggs['filtered_workout']['workout']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($vip_trial_response->results->aggregationlist->workout, $offval);
	}
	
	
	$vip_trial_response->results->aggregationlist->vendor = array();

	foreach ($aggs['filtered_vendor']['vendors']['buckets'] as $off){
		$offval = new \stdClass();
		$offval->key = $off['key'];
		$offval->count = $off['doc_count'];
		array_push($vip_trial_response->results->aggregationlist->vendor, $offval);
	}
	$vip_trial_response->results->aggregationlist->price = new \stdClass();

	$vip_trial_response->results->aggregationlist->price->min = $aggs['filtered_price_min']['price_min']['value'];
	$vip_trial_response->results->aggregationlist->price->max = $aggs['filtered_price_max']['price_max']['value'];
	return $vip_trial_response;

}
}

?>
