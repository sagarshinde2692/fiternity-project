<?PHP namespace App\Services;

Use \App\Responsemodels\AutocompleteResponse;
Use \App\Responsemodels\AutocompleteResult;
Use \App\Responsemodels\FinderresultResponse;
Use \App\Responsemodels\FinderResult;
Use \App\Responsemodels\FinderObject;
Use \App\Responsemodels\FinderResultNew;
Use \App\Responsemodels\FinderObjectNew;
Use \App\Responsemodels\ViptrialResponse;
Use \App\Responsemodels\VipResult;
Use \App\Responsemodels\WorkoutSessionObject;
Use \App\Responsemodels\saleRatecardResponse;
Use \App\Responsemodels\saleRatecardResult;
Use \App\Responsemodels\saleRatecardObject;
use \Input;
use Log, Config, Request;

// Translator methods to model API responses for client

class Translator {

	public  function __construct(){
		//empty constructor
	}

	public static function translate_autocomplete($es_autocomplete_response = array(), $city, $customer_email = "",$device_type = ""){
		$autcomplete_response = new AutocompleteResponse();
		if(isset($es_autocomplete_response['error'])){
			$autcomplete_response->status = 500;
			return $autcomplete_response;
		}
		else{	

			$autcomplete_response->meta = new \stdClass();			
			$autcomplete_response->meta->total_records = $es_autocomplete_response['hits']['total'];
			foreach ($es_autocomplete_response['hits']['hits'] as $value) {
				if(in_array($value['fields']['slug'][0], Config::get('app.test_vendors'))){
					if(!in_array($customer_email, Config::get('app.test_page_users'))){
						$autcomplete_response->meta->total_records--;
						continue;
					}
				}
				$area = '';
				if($value['fields']['location'][0] === ""){
					$area = $city;
				}
				else{
					$area = $value['fields']['location'][0];
				}
				//Log::info($value['fields']['slug'][0]);
                $automodel = new AutocompleteResult();
                $automodel->keyword = $value['fields']['autosuggestvalue'][0];
                $automodel->object->id = $value['_id'];
                $automodel->object->slug = $value['fields']['slug'][0];
                $automodel->object->location = new \stdClass();
                $automodel->object->location->city = $city;
                $automodel->object->location->area = $area;
                $automodel->object->location->lat = 0.0;
                $automodel->object->location->long = 0.0;//$value['fields']['location'][0];
                $automodel->object->category = !empty($value['fields']['category_subcat']) ? $value['fields']['category_subcat'][0] : $value['fields']['inputcat1'][0];
				$automodel->object->servicecategory = isset($value['fields']['inputservicecat']) ? $value['fields']['inputservicecat'][0] : '';
				$automodel->object->tag = $value['fields']['inputcat'][0];
                $automodel->object->brand = isset($value['fields']['brand']) ? $value['fields']['brand'][0] : '';
                $automodel->object->brand_id = isset($value['fields']['brand_id']) ? $value['fields']['brand_id'][0] : '';
                $automodel->object->outlets = isset($value['fields']['outlets']) ? $value['fields']['outlets'][0] : '';
                $automodel->object->infra_type = isset($value['fields']['infrastructure_type']) ? $value['fields']['infrastructure_type'][0] : '';
                $automodel->object_type = $value['fields']['type'][0];
				if(($device_type == "android" || $device_type == "") && ($value['fields']['inputcat1'][0] == "healthy tiffins" || $automodel->object->servicecategory == "healthy tiffins")){
					$autcomplete_response->meta->total_records --;
					continue;
				}
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
				$resultobject->info_service = isset($result['info_service']) ? $result['info_service'] : "";;
			$resultobject->info_service_list = array();//$result['info_service_list'];
			$resultobject->contact->address = isset($result['contact']['address']) ? $result['contact']['address'] : "";;
			$resultobject->contact->email = isset($result['contact']['email']) ? $result['contact']['email'] : "";
			$resultobject->contact->phone = ''; //$result['contact']['phone'];
			$resultobject->contact->website = isset($result['contact']['website']) ? $result['contact']['website'] : "";
			$resultobject->coverimage = isset($result['coverimage']) ? "https://b.fitn.in/f/ct/".$result['coverimage'] : "";;
			$resultobject->commercial_type = isset($result['commercial_type']) ? $result['commercial_type'] : "";;
			$resultobject->finder_type = (isset($result['finder_type']) && !empty($result['finder_type'])) ? $result['finder_type'] : 0;
			$resultobject->business_type = isset($result['business_type']) ? $result['business_type'] : "";;
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
			$resultobject->title = str_replace('crossfit', 'CrossFit', $result['title']);;
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
			$resultobject->title = str_replace('crossfit', 'CrossFit', $result['title']);;
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

			$resultobject->vendor_type = "";
				if($result['category'] != "personal trainer"){
					if($result['category'] != "dietitians and nutritionists" && $result['category'] != "healthy snacks and beverages" && $result['category'] != "healthy tiffins"){

						if($result['business_type'] == 0){
							$resultobject->vendor_type = "Trainer";
						}else{
							$resultobject->vendor_type = "Outlet";
						}
					}else{
						if($result['category'] == "dietitians and nutritionists" ){
							$resultobject->vendor_type = "";
						}elseif($result['category'] == "healthy tiffins"){
							$resultobject->vendor_type = "healthy tiffins";
						}else{
							$resultobject->vendor_type = "healthy snacks";
						}
					}
				}else{
					$resultobject->vendor_type = "Trainer";
				}

				// Booktrial caption button
				$resultobject->booktrial_button_caption = "";

                $nobooktrialCategories = ['healthy snacks and beverages','swimming pools','sports','dietitians and nutritionists','sport nutrition supliment stores'];
				if($resultobject->commercial_type != 0){
					if(!in_array($result['category'],$nobooktrialCategories)){
						if($result['category'] != "healthy tiffins"){
							if( in_array('free trial',$result['facilities']) ){
								$resultobject->booktrial_button_caption = "Book a free trial";
							}else{
								$resultobject->booktrial_button_caption = "Book a trial";
							}
						}else{
							$resultobject->booktrial_button_caption = "Book a trial Meal";
						}
					}
				}

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
			$resultobject->title = str_replace('crossfit', 'CrossFit', $result['title']);;
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


	public static function translate_searchresultsv3($es_searchresult_response,$search_request = array(), $customer_email = ""){
		$finderresult_response = new FinderresultResponse();

		$finderresult_response->results->aggregationlist = new \stdClass();


		$resultCategory = [];

		if(empty($es_searchresult_response['hits']['hits']))
		{
			$finderresult_response->results->resultlist = array();
			$finderresult_response->meta->total_records = 0;
		}
		else{
			$finderresult_response->meta->total_records = $es_searchresult_response['hits']['total'];
			foreach ($es_searchresult_response['hits']['hits'] as $resultv1) {
				if(in_array($resultv1['_source']['slug'], Config::get('app.test_vendors'))){
					if(!in_array($customer_email, Config::get('app.test_page_users'))){
						$finderresult_response->meta->total_records--;
						continue;
					}
				}
				$result = $resultv1['_source'];
				$finder = new FinderResult();
				$finder->object_type = 'vendor';
				$resultobject = new FinderObject();
				$resultobject->distance = isset($resultv1['fields']) ? $resultv1['fields']['distance'][0] : 0;
				$resultobject->id = $result['_id'];
				$resultobject->category = $result['category'];
				$resultCategory = $result['category'];
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
				$resultobject->contact->address = isset($result['contact']['address']) ? $result['contact']['address'] : "";
				$resultobject->contact->email = isset($result['contact']['email']) ? $result['contact']['email'] : "";
				$resultobject->contact->phone = ''; //$result['contact']['phone'];
				$resultobject->contact->website = isset($result['contact']['website']) ? $result['contact']['website'] : "";
				$resultobject->coverimage = $result['coverimage'];
				$resultobject->finder_coverimage_webp = ""; //isset($result['finder_coverimage_webp']) ? (strpos($result['finder_coverimage_webp'],"default/") > -1 ? "" : $result['finder_coverimage_webp']) : "";
				$resultobject->finder_coverimage_color = isset($result['finder_coverimage_color']) && $result['finder_coverimage_color'] != "" ? $result['finder_coverimage_color'] : "#FFC107";

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
				$resultobject->title = str_replace('crossfit', 'CrossFit', $result['title']);;
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
				$resultobject->flags = isset($result['flags']) ? $result['flags'] : array();
				$result['facilities'] = (is_array($result['facilities']) && $result['facilities'] != "") ? $result['facilities'] : [];

				$resultobject->offer_available = "";
				if(in_array($result['commercial_type'],["1","2","3"])){
					$resultobject->offer_available = "https://b.fitn.in/iconsv1/fitmania/offer_avail_red.png";
				}else{
					$resultobject->offer_available = "";
				}

				// Deciding which address to show
				if(count($search_request) > 0 && isset($search_request['regions']) && count($search_request['regions']) > 0 && !empty($result['multiaddress'])){
					$multiaddress_locations = array();
					$intersect = array();
					$found = false;
					foreach($search_request['regions'] as $loc){
						$loc = str_replace("-"," ",$loc);
						foreach($result['multiaddress'] as $key => $regions){
							if(in_array(strtolower($loc),$regions['location'])){
								array_push($intersect,$regions);
								$found = true;
								unset($result['multiaddress'][$key]);	
							}
							if(in_array("Base location",$regions['location'])){
								$regions['location'] = str_replace("Base location",$result['location'],$regions['location']);
								// Log::info($regions['location']);
								$result['multiaddress'][$key]['location'] = $regions['location'];
							}
						}
						foreach($result['multiaddress'] as $key => $regions){
							array_push($intersect,$regions);
						}
					}
					$resultobject->multiaddress = $intersect;
				}else{
					$resultobject->multiaddress = isset($result['multiaddress']) && count($result['multiaddress']) > 0 ? $result['multiaddress'] : array();
				}
				// if(count($search_request) > 0 && isset($search_request['regions']) && count($search_request['regions']) > 0 && !empty($result['multiaddress'])){
				// 	$multiaddress_locations = array();
				// 	$intersect = array();
				// 	$found = false;
				// 	foreach($search_request['regions'] as $loc){
				// 		foreach($result['multiaddress'] as $regions){
				// 			if(in_array(strtolower($loc),$regions['location'])){
				// 				array_push($intersect,$regions);
				// 				$found = true;
				// 				break;	
				// 			}
				// 		}
				// 	}
				// 	$resultobject->multiaddress = $found ? $intersect : $result['multiaddress'];
				// }else{
				// 	$resultobject->multiaddress = isset($result['multiaddress']) && count($result['multiaddress']) > 0 ? $result['multiaddress'] : array();
				// }
				// if(count($search_request) > 0 && ((isset($search_request['womens_day']) && $search_request['womens_day'] == true) ||(isset($search_request['offer_available']) && $search_request['offer_available'] == true) )){
				// 	// echo "disc25or50".$result['flags']['disc25or50'];
				// 	// echo "discother".$result['flags']['discother'];
				// 	if($result['flags']['disc25or50'] == 1){
				// 		$resultobject->offer_available = "https://b.fitn.in/iconsv1/womens-day/additional-50.png";
				// 	}
				// 	if($result['flags']['discother'] == 1){
				// 		$resultobject->offer_available = "https://b.fitn.in/iconsv1/womens-day/exclusive.png";
				// 	}
				// }


				// Decide vendor type
				$resultobject->vendor_type = "";
				if($result['category'] != "personal trainer"){
					if($result['category'] != "dietitians and nutritionists" && $result['category'] != "healthy snacks and beverages" && $result['category'] != "healthy tiffins"){

						if($result['business_type'] == 0){
							$resultobject->vendor_type = "Trainer";
						}else{
							$resultobject->vendor_type = "Outlet";
						}
					}else{
						if($result['category'] == "dietitians and nutritionists" ){
							$resultobject->vendor_type = "";
						}elseif($result['category'] == "healthy tiffins"){
							$resultobject->vendor_type = "Healthy tiffins";
						}else{
							$resultobject->vendor_type = "Healthy snacks";
						}
					}
				}else{
					$resultobject->vendor_type = "Trainer";
				}

				// Booktrial caption button
				$resultobject->booktrial_button_caption = "";

                $nobooktrialCategories = ['healthy snacks and beverages','swimming pools','sports','dietitians and nutritionists','sport nutrition supliment stores'];
				if($resultobject->commercial_type != 0){
					if(!in_array($result['category'],$nobooktrialCategories)){
						if($result['category'] != "healthy tiffins"){
							if( in_array('free trial',$result['facilities']) ){
								$resultobject->booktrial_button_caption = "Book a free trial";
							}else{
								$resultobject->booktrial_button_caption = "Book a trial";
							}
						}else{
							$resultobject->booktrial_button_caption = "Book a trial Meal";
						}
					}
				}
				$finder->object = $resultobject;
				$resultobject->vendor_type = "";
				if($result['category'] != "personal trainer"){
					if($result['category'] != "dietitians and nutritionists" && $result['category'] != "healthy snacks and beverages"){
						if($result['business_type'] == 0){
							$resultobject->vendor_type = "Trainer";
						}else{
							$resultobject->vendor_type = "Outlet";
						}
					}else{
						$resultobject->vendor_type = "";
					}
				}else{
					$resultobject->vendor_type = "Trainer";
				}

				array_push($finderresult_response->results->resultlist, $finder);
			}
		}


		$aggs = new \stdClass();
		if(isset($es_searchresult_response['aggregations'])){

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
			$noBasicFilterCategories = ['healthy snacks and beverages','healthy tiffins','dietitians and nutritionists','sport nutrition supliment stores'];
			if(!in_array($resultCategory,$noBasicFilterCategories)){
				foreach ($aggs['filtered_facilities']['facilities']['buckets'] as $fac) {
					$facval = new \stdClass();
					$facval->key = $fac['key'];
					$facval->count = $fac['doc_count'];
					array_push($finderresult_response->results->aggregationlist->filters, $facval);
				}
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

			$finderresult_response->results->aggregationlist->vip_trial = array();

			foreach ($aggs['filtered_vip_trial']['vip_trial']['buckets'] as $off){
				$offval = new \stdClass();
				$offval->key = $off['key'];
				$offval->count = $off['doc_count'];
				array_push($finderresult_response->results->aggregationlist->vip_trial, $offval);
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
		}
		return $finderresult_response;
	}

	public static function translate_vip_trials($es_searchresult_response){

		$city_array = array('mumbai'=>1,'pune'=>2,'delhi'=>4,'banglore'=>3,'gurgaon'=>8,'noida'=>9);

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

				$sort = $resultv1['sort'];

				// var_dump($result['commercial_type'] );exit;

					// var_dump($result['commercial_type'] );exit();


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
					$resultobject->ratecard_id = $result['ratecard_id']; 
					$resultobject->finder_id = isset($result['finder_id']) ? $result['finder_id'] : 0;
					//$resultobject->city_id = isset($result['city_id']) ? $result['city_id'] : 0;

					/*if(isset($_GET['device_type']) && (strtolower($_GET['device_type']) == "android") && isset($_GET['app_version']) && ((float)$_GET['app_version'] >= 2.4)){

						$resultobject->geolocation = new \stdClass();

						$resultobject->geolocation->lat = (float)$result['geolocation']['lat'];
						$resultobject->geolocation->long = (float)$result['geolocation']['lon'];

						if(isset($sort[2])){
							$resultobject->geolocation->distance = round((float)$sort[2],2);
						}
					}*/

					$resultobject->city_id = isset($result['city_id']) ? $result['city_id'] : $city_array[$result['city']];

					$finder->object = $resultobject;
					if($resultobject->ratecard_id != ""){
						array_push($vip_trial_response->results->resultlist, $finder);
					}

			}
		}

        if(empty($es_searchresult_response['aggregations'])){
        
            return Response::json(['status'=>400], 400);
        
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

		$vip_trial_response->results->aggregationlist->categorysubcategory = array();

		foreach ($aggs['filtered_category_subcategory']['category']['buckets'] as $cluster) {
			$clusterval = new \stdClass();
			$clusterval->key = $cluster['key'];
			$clusterval->count = $cluster['doc_count'];
			$clusterval->subcategory = array();
			foreach ($cluster['subcategory']['buckets'] as $reg) {
				$regval = new \stdClass();
				$regval->key = $reg['key'];
				$regval->count = $reg['doc_count'];
				array_push($clusterval->subcategory, $regval);
			}
			array_push($vip_trial_response->results->aggregationlist->categorysubcategory, $clusterval);
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

public static function translate_sale_ratecards($es_searchresult_response){

	$city_array = array('mumbai'=>1,'pune'=>2,'delhi'=>4,'banglore'=>3,'gurgaon'=>8,'noida'=>9);
	$sale_ratecard_response = new saleRatecardResponse();
	$sale_ratecard_response->results->aggregationlist = array();
	$validity = (Input::json()->get('validity')) ? Input::json()->get('validity') : '';
	$validity_type = (Input::json()->get('validity_type')) ? Input::json()->get('validity_type') : '';

	if(empty($es_searchresult_response['hits']['hits']))
	{
		$sale_ratecard_response->results->resultlist = array();
		$sale_ratecard_response->meta->total_records = 0;
	}
	else{
		$sale_ratecard_response->meta->total_records = $es_searchresult_response['hits']['total'];

		foreach ($es_searchresult_response['hits']['hits'] as $resultv1) {
			$result = $resultv1['_source'];
			$service = new saleRatecardResult();
			$service->object_type = 'service';

			$resultobject = new saleRatecardObject();
			$resultobject->id = $result['service_id'];

			if($validity != ""){
				foreach ($result['sale_ratecards'] as $sale_ratecards_key => $sale_ratecards_value){
					if($sale_ratecards_value['validity'] !== $validity){
						unset($result['sale_ratecards'][$sale_ratecards_key]);
					} 
				}
			}

			if($validity_type != ""){
				foreach ($result['sale_ratecards'] as $sale_ratecards_key => $sale_ratecards_value){
					if($sale_ratecards_value['validity_type'] !== $validity_type){
						unset($result['sale_ratecards'][$sale_ratecards_key]);
					} 
				}
			}
			$resultobject->sale_ratecards = $result['sale_ratecards'];
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
			$resultobject->finder_gallery = $result['finder_gallary'];
			$resultobject->finder_address = $result['finder_address'];
			$resultobject->service_address = $result['service_address'];
			$resultobject->finder_slug = $result['finderslug'];
			$resultobject->finder_id = isset($result['finder_id']) ? $result['finder_id'] : 0;
			$resultobject->city_id = isset($result['city_id']) ? $result['city_id'] : $city_array[$result['city']];
			$resultobject->meal_type = isset($result['meal_type']) ? $result['meal_type'] : "";
			$resultobject->short_description = isset($result['short_description']) ? strip_tags($result['short_description']) : "";
			$service->object = $resultobject;
			array_push($sale_ratecard_response->results->resultlist, $service);
			
		}
	}
	return $sale_ratecard_response;

}







public static function translate_searchresultsv4($es_searchresult_response,$search_request = array(),$keys = array(), $customer_email = ""){
		$finderresult_response 							 = new FinderresultResponse();
		$finderresult_response->results->aggregationlist = new \stdClass();
		$resultCategory 								 = [];
		$currentcity 									 = "mumbai";
		if(empty($es_searchresult_response['hits']['hits']))
		{
			$finderresult_response->results->resultlist = array();
			$finderresult_response->metadata->total_records = 0;
		}
		else{
			$finderresult_response->metadata->total_records = $es_searchresult_response['hits']['total'];
			foreach ($es_searchresult_response['hits']['hits'] as $resultv1) {
				if(in_array($resultv1['_source']['slug'], Config::get('app.test_vendors'))){
					if(!in_array($customer_email, Config::get('app.test_page_users'))){
						$finderresult_response->metadata->total_records--;
						continue;
					}
				}
				$result 						= $resultv1['_source'];
				$finder 						= new FinderResult();
				$finder->object_type 			= 'vendor';
				$resultobject 					= new FinderObject();
				$resultobject->distance 		= isset($resultv1['fields']) ? $resultv1['fields']['distance'][0] : 0;
				$resultobject->id 				= $result['_id'];
				$resultobject->category 		= $result['category'];
				$resultCategory 				= $result['category'];
				$resultobject->categorytags 	= empty($result['categorytags']) ? array() : $result['categorytags'];
				$resultobject->location 		= $result['location'];
				$resultobject->locationtags 	= empty($result['locationtags']) ? array() : $result['locationtags'];
				$resultobject->average_rating 	= $result['average_rating'];
				$resultobject->membership_discount = $result['membership_discount'];
				$resultobject->country 			= $result['country'];
				$resultobject->city 			= $result['city'];
				$currentcity 					= $result['city'];
				//$resultobject->city_id = $result['city_id'];
				$resultobject->info_service 	= $result['info_service'];
				$resultobject->info_service_list= array();//$result['info_service_list'];
				$resultobject->contact->address = isset($result['contact']['address']) ? $result['contact']['address'] : "";
				$resultobject->contact->email 	= isset($result['contact']['email']) ? $result['contact']['email'] : "";
				$resultobject->contact->phone 	= ''; //$result['contact']['phone'];
				$resultobject->contact->website = isset($result['contact']['website']) ? $result['contact']['website'] : "";
				$resultobject->coverimage 		= $result['coverimage'];
				$resultobject->finder_coverimage_webp = ""; //isset($result['finder_coverimage_webp']) ? (strpos($result['finder_coverimage_webp'],"default/") > -1 ? "" : $result['finder_coverimage_webp']) : "";
				$resultobject->finder_coverimage_color = isset($result['finder_coverimage_color']) && $result['finder_coverimage_color'] != "" ? $result['finder_coverimage_color'] : "#FFC107";
				$resultobject->commercial_type 	= $result['commercial_type'];
				$resultobject->finder_type 		= $result['finder_type'];
				$resultobject->business_type 	= $result['business_type'];
				$resultobject->fitternityno 	= '+917506122637';
				$resultobject->facilities 		= empty($result['facilities']) ? array() : $result['facilities'];
				$resultobject->logo 			= $result['logo'];
				$resultobject->geolocation->lat = $result['geolocation']['lat'];
				$resultobject->geolocation->long= $result['geolocation']['lon'];
				$resultobject->offerings 		= empty($result['offerings']) ? array() : $result['offerings'];
				$resultobject->price_range 		= $result['price_range'];
				$resultobject->popularity 		= $result['popularity'];
				$resultobject->special_offer_title = $result['special_offer_title'];
				$resultobject->slug 			= $result['slug'];
				$resultobject->status 			= $result['status'];
				$resultobject->title 			= isset($result['title_show']) ? $result['title_show'] : $result['title'];
				$resultobject->total_rating_count = $result['total_rating_count'];
				$resultobject->views 			= $result['views'];
				$resultobject->state 			= isset($result['state']) ? $result['state'] : "";
				$resultobject->instantbooktrial_status = $result['instantbooktrial_status'];
				$resultobject->photos 			= $result['photos'];
				$resultobject->locationcluster 	= $result['locationcluster'];
				$resultobject->price_rangeval 	= $result['price_rangeval'];
				$resultobject->servicelist 		= isset($result['servicelist']) ? $result['servicelist'] : array();
				$resultobject->servicephotos 	= isset($result['servicephotos']) ? $result['servicephotos'] : array();
				$resultobject->ozonetelno->phone_number = (isset($result['ozonetelno']) && isset($result['ozonetelno']['phone_number'])) ? $result['ozonetelno']['phone_number'] : "";
				$resultobject->manual_trial_bool = (isset($result['manual_trial_bool'])) ? $result['manual_trial_bool'] : "";
				$resultobject->ozonetelno->extension = (isset($result['ozonetelno']) && isset($result['ozonetelno']['extension'])) ? $result['ozonetelno']['extension'] : "";
				$resultobject->distance 		= (isset($resultv1['fields']) && isset($resultv1['fields']['distance'])) ? number_format((float)$resultv1['fields']['distance'][0], 2, '.', '')."km" : "no";
				$result['facilities'] 			= (is_array($result['facilities']) && $result['facilities'] != "") ? $result['facilities'] : [];

				$resultobject->offer_available 	= "";
				if(in_array($result['commercial_type'],["1","2","3"])){
					$resultobject->offer_available = "https://b.fitn.in/iconsv1/fitmania/offer_avail_red.png";
				}else{
					$resultobject->offer_available = "";
				}

				// Deciding which address to show
				if(count($search_request) > 0 && isset($search_request['regions']) && count($search_request['regions']) > 0 && !empty($result['multiaddress'])){
					$multiaddress_locations = array();
					$intersect = array();
					$found = false;
					foreach($search_request['regions'] as $loc){
						$loc = str_replace("-"," ",$loc);
						foreach($result['multiaddress'] as $key => $regions){
							if(in_array(strtolower($loc),$regions['location'])){
								array_push($intersect,$regions);
								$found = true;
								unset($result['multiaddress'][$key]);	
							}
							if(in_array("Base location",$regions['location'])){
								$regions['location'] = str_replace("Base location",$result['location'],$regions['location']);
								// Log::info($regions['location']);
								$result['multiaddress'][$key]['location'] = $regions['location'];
							}
						}
						foreach($result['multiaddress'] as $key => $regions){
							array_push($intersect,$regions);
						}
					}
					$resultobject->multiaddress = $intersect;
				}else{
					$resultobject->multiaddress = isset($result['multiaddress']) && count($result['multiaddress']) > 0 ? $result['multiaddress'] : array();
				}

				// Decide vendor type
				$resultobject->vendor_type = "";
				if($result['category'] != "personal trainer"){
					if($result['category'] != "dietitians and nutritionists" && $result['category'] != "healthy snacks and beverages" && $result['category'] != "healthy tiffins"){

						if($result['business_type'] == 0){
							$resultobject->vendor_type = "Trainer";
						}else{
							$resultobject->vendor_type = "Outlet";
						}
					}else{
						if($result['category'] == "dietitians and nutritionists" ){
							$resultobject->vendor_type = "";
						}elseif($result['category'] == "healthy tiffins"){
							$resultobject->vendor_type = "Healthy tiffins";
						}else{
							$resultobject->vendor_type = "Healthy snacks";
						}
					}
				}else{
					$resultobject->vendor_type = "Trainer";
				}

				// Booktrial caption button
				$resultobject->booktrial_button_caption = "";

                $nobooktrialCategories = ['healthy snacks and beverages','swimming pools','sports','dietitians and nutritionists','sport nutrition supliment stores'];
				if($resultobject->commercial_type != 0){
					if(!in_array($result['category'],$nobooktrialCategories)){
						if($result['category'] != "healthy tiffins"){
							if( in_array('free trial',$result['facilities']) ){
								$resultobject->booktrial_button_caption = "Book a free trial";
							}else{
								$resultobject->booktrial_button_caption = "Book a trial";
							}
						}else{
							$resultobject->booktrial_button_caption = "Book a trial Meal";
						}
					}
				}
				if(count($keys) > 0){
					$newObj = array();
					foreach ($keys as $key){
						isset($resultobject->$key) ? $newObj[$key]=$resultobject->$key : null;
					}
					$finder->object = $newObj;
				}else{
					$finder->object = $resultobject;
				}
				$resultobject->vendor_type = "";
				if($result['category'] != "personal trainer"){
					if($result['category'] != "dietitians and nutritionists" && $result['category'] != "healthy snacks and beverages"){
						if($result['business_type'] == 0){
							$resultobject->vendor_type = "Trainer";
						}else{
							$resultobject->vendor_type = "Outlet";
						}
					}else{
						$resultobject->vendor_type = "";
					}
				}else{
					$resultobject->vendor_type = "Trainer";
				}

				array_push($finderresult_response->results->resultlist, $finder);
			}
		}
		unset($finderresult_response->meta);



		$aggs = $es_searchresult_response['aggregations'];

		$finderresult_response->results->aggregationlist->budget = array();
		$budval0 = new \stdClass();
		$budval1 = new \stdClass();
		$budval2 = new \stdClass();
		$budval3 = new \stdClass();
		$budval4 = new \stdClass();
		$budval5 = new \stdClass();
		// print_r($aggs['filtered_budgets']['budgets']['buckets']);
		// exit;
		if(count($aggs['filtered_budgets']['budgets']['buckets']) > 0){
			
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
		}

		$finderresult_response->results->aggregationlist->filters = array();
		$noBasicFilterCategories = ['healthy snacks and beverages','healthy tiffins','dietitians and nutritionists','sport nutrition supliment stores'];
        if(!in_array($resultCategory,$noBasicFilterCategories)){
			foreach ($aggs['filtered_facilities']['facilities']['buckets'] as $fac) {
				$facval = new \stdClass();
				$facval->key = $fac['key'];
				$facval->slug = str_replace(' ', '-', $fac['key']);
				$facval->count = $fac['doc_count'];
				array_push($finderresult_response->results->aggregationlist->filters, $facval);
			}
		}

		$finderresult_response->results->aggregationlist->locationcluster = array();
		$cityfound = true;
		if(isset($search_request["city"])){
			$cityResponse = ifCityPresent($search_request["city"]);
			$cityfound = $cityResponse["found"];
		}
		if($cityfound){
			foreach ($aggs['filtered_locations']['loccluster']['buckets'] as $cluster) {
				$clusterval = new \stdClass();
				$clusterval->key = $cluster['key'];
				$clusterval->slug = strtolower(str_replace(' ', '-', $cluster['key']));
				$clusterval->count = $cluster['doc_count'];
				$clusterval->regions = array();
				if(isset($cluster['region']['attrs'])){
					foreach ($cluster['region']['attrs']['buckets'] as $reg) {
						$regval = new \stdClass();
						$regval->key = $reg['key'];
						$regval->slug = $reg['attrsValues']['buckets'][0]['key'];
						$regval->count = $reg['doc_count'];
						array_push($clusterval->regions, $regval);
					}
				}
				array_push($finderresult_response->results->aggregationlist->locationcluster, $clusterval);
			}
		}

		$finderresult_response->results->aggregationlist->subcategories = array();

		foreach ($aggs['filtered_offerings']['offerings']['buckets'] as $off){
			$offval = new \stdClass();
			$offval->key = $off['key'];
			$offval->slug = str_replace(' ', '-', $off['key']);
			$offval->count = $off['doc_count'];
			array_push($finderresult_response->results->aggregationlist->subcategories, $offval);
		}

		// $finderresult_response->results->aggregationlist->vip_trial = array();

		// foreach ($aggs['filtered_vip_trial']['vip_trial']['buckets'] as $off){
		// 	$offval = new \stdClass();
		// 	$offval->key = $off['key'];
		// 	$offval->count = $off['doc_count'];
		// 	array_push($finderresult_response->results->aggregationlist->vip_trial, $offval);
		// }
		if(isset($search_request['with_locationtags']) && $search_request['with_locationtags'] == 1){
			$finderresult_response->results->aggregationlist->locationtags = array();
			foreach ($aggs['filtered_locationtags']['offerings']['attrs']['buckets'] as $off){
				$offval = new \stdClass();
				$offval->key = $off['key'];
				$offval->slug = $off['attrsValues']['buckets'][0]['key'];
				//$offval->cluster = $off['locationcluster']['buckets'][0]['key'];
				$offval->count = $off['doc_count'];
				array_push($finderresult_response->results->aggregationlist->locationtags, $offval);
			}
		}

	
	if(isset($aggs['filtered_trials']['level1'])){
		$finderresult_response->results->aggregationlist->trialdays = array();

			foreach ($aggs['filtered_trials']['level1']['level2']['daysaggregator']['buckets'] as $off){
				$offval = new \stdClass();
				$offval->key = $off['key'];
				$offval->slug = str_replace(' ', '-', $off['key']);
				$offval->count = $off['backtolevel1']['backtorootdoc']['doc_count'];
				array_push($finderresult_response->results->aggregationlist->trialdays, $offval);
			}
			$weekdays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
			$trialdays = json_decode(json_encode($finderresult_response->results->aggregationlist->trialdays), true);
			$trialdays = sorting_array($trialdays, "key", $weekdays, false);
			$finaltrialdays = array();
			foreach($trialdays as $day){
				if($day["key"] != ""){
					$day["slug"] = $day["key"]."-open";
					$day["key"] = $day["key"]." open";
					array_push($finaltrialdays, $day);
				}
				//  print_r($days["key"]);
				// exit;
			}
			$finderresult_response->results->aggregationlist->trialdays = $finaltrialdays;
		}
		$finderresult_response->results->aggregationlist->categories = array();
		$finderresult_response->results->aggregationlist->categories = citywise_categories($currentcity);
		// print_r($finderresult_response);
		return $finderresult_response;
	}


public static function translate_searchresultsv5($es_searchresult_response,$search_request = array(),$keys = array(), $customer_email = ""){

		$device_type = (Request::header('Device-Type') && Request::header('Device-Type') != "" && Request::header('Device-Type') != null) ? strtolower(Request::header('Device-Type')) : "";
		$app_version = (Request::header('App-Version') && Request::header('App-Version') != "" && Request::header('App-Version') != null) ? (float)Request::header('App-Version') : "";

		$finderresult_response 							 = new FinderresultResponse();
		$finderresult_response->results->aggregationlist = new \stdClass();
		$finderresult_response->results->aggregations = new \stdClass();
		$resultCategory 								 = [];
		$currentcity 									 = "mumbai";
		if(empty($es_searchresult_response['hits']['hits']))
		{
			$finderresult_response->results->results = array();
			$finderresult_response->metadata->total_records = 0;
		}
		else{
			$finderresult_response->metadata->total_records = $es_searchresult_response['hits']['total'];
			foreach ($es_searchresult_response['hits']['hits'] as $resultv1) {
				if(in_array($resultv1['_source']['slug'], Config::get('app.test_vendors'))){
					if(!in_array($customer_email, Config::get('app.test_page_users'))){
						$finderresult_response->metadata->total_records--;
						continue;
					}
				}
				$result 						= $resultv1['_source'];
				$finder 						= new FinderResultNew();
				$finder->object_type 			= 'vendor';
				$resultobject 					= new FinderObjectNew();
				$resultobject->distance 		= isset($resultv1['fields']) ? $resultv1['fields']['distance'][0] : 0;
				$resultobject->id 				= $result['_id'];
				$resultobject->category 		= $result['category'];
				$resultCategory 				= $result['category'];
				$resultobject->categorytags 	= empty($result['categorytags']) ? array() : $result['categorytags'];
				$resultobject->location 		= $result['location'];
				$resultobject->locationtags 	= empty($result['locationtags']) ? array() : $result['locationtags'];
				$resultobject->average_rating 	= $result['average_rating'];
				$resultobject->membership_discount = $result['membership_discount'];
				$resultobject->country 			= $result['country'];
				$resultobject->city 			= $result['city'];
				$currentcity 					= $result['city'];
				//$resultobject->city_id = $result['city_id'];
				$resultobject->info_service 	= $result['info_service'];
				$resultobject->info_service_list= array();//$result['info_service_list'];
				$resultobject->contact->address = isset($result['contact']['address']) ? $result['contact']['address'] : "";
				$resultobject->contact->email 	= isset($result['contact']['email']) ? $result['contact']['email'] : "";
				$resultobject->contact->phone 	= ''; //$result['contact']['phone'];
				$resultobject->contact->website = isset($result['contact']['website']) ? $result['contact']['website'] : "";
				$resultobject->coverimage 		= "https://b.fitn.in/f/c/".$result['coverimage'];
				$resultobject->finder_coverimage_webp = ""; //isset($result['finder_coverimage_webp']) ? (strpos($result['finder_coverimage_webp'],"default/") > -1 ? "" : $result['finder_coverimage_webp']) : "";
				$resultobject->finder_coverimage_color = isset($result['finder_coverimage_color']) && $result['finder_coverimage_color'] != "" ? $result['finder_coverimage_color'] : "#FFC107";
				$resultobject->commercial_type 	= $result['commercial_type'];
				$resultobject->finder_type 		= $result['finder_type'];
				$resultobject->business_type 	= $result['business_type'];
				$resultobject->fitternityno 	= '+917506122637';
				$resultobject->facilities 		= empty($result['facilities']) ? array() : $result['facilities'];
				$resultobject->logo 			= $result['logo'];
				$geolocation  					= new \stdClass();
				$geolocation->lat = (float)$result['geolocation']['lat'];
				$geolocation->lon = (float)$result['geolocation']['lon'];
				array_push($resultobject->geolocation,$geolocation);
				$resultobject->offerings 		= empty($result['offerings']) ? array() : $result['offerings'];
				$resultobject->price_range 		= $result['price_range'];
				$resultobject->popularity 		= $result['popularity'];
				$resultobject->special_offer_title = $result['special_offer_title'];
				$resultobject->slug 			= $result['slug'];
				$resultobject->status 			= $result['status'];
				$resultobject->name 			= isset($result['title_show']) ? $result['title_show'] : $result['title'];
				$resultobject->total_rating_count = $result['total_rating_count'];
				$resultobject->views 			= $result['views'];
				$resultobject->state 			= isset($result['state']) ? $result['state'] : "";
				$resultobject->instantbooktrial_status = $result['instantbooktrial_status'];
				$resultobject->photos 			= $result['photos'];
				$resultobject->locationcluster 	= $result['locationcluster'];
				$resultobject->price_rangeval 	= $result['price_rangeval'];
				$resultobject->servicelist 		= isset($result['servicelist']) ? $result['servicelist'] : array();
				$resultobject->servicephotos 	= isset($result['servicephotos']) ? $result['servicephotos'] : array();
				$resultobject->ozonetelno->phone_number = (isset($result['ozonetelno']) && isset($result['ozonetelno']['phone_number'])) ? $result['ozonetelno']['phone_number'] : "";
				$resultobject->manual_trial_bool = (isset($result['manual_trial_bool'])) ? $result['manual_trial_bool'] : "";
				$resultobject->ozonetelno->extension = (isset($result['ozonetelno']) && isset($result['ozonetelno']['extension'])) ? $result['ozonetelno']['extension'] : "";
				$resultobject->distance 		= (isset($resultv1['fields']) && isset($resultv1['fields']['distance'])) ? number_format((float)$resultv1['fields']['distance'][0], 2, '.', '')."km" : "no";
				$result['facilities'] 			= (is_array($result['facilities']) && $result['facilities'] != "") ? $result['facilities'] : [];

				$resultobject->offer_available 	= "";
				if(in_array($result['commercial_type'],["1","2","3"])){
					$resultobject->offer_available = "https://b.fitn.in/iconsv1/fitmania/offer_avail_red.png";
				}else{
					$resultobject->offer_available = "";
				}

				// Deciding which address to show
				if(count($search_request) > 0 && isset($search_request['regions']) && count($search_request['regions']) > 0 && !empty($result['multiaddress'])){
					$multiaddress_locations = array();
					$intersect = array();
					$found = false;
					foreach($search_request['regions'] as $loc){
						$loc = str_replace("-"," ",$loc);
						foreach($result['multiaddress'] as $key => $regions){
							if(in_array(strtolower($loc),$regions['location'])){
								array_push($intersect,$regions);
								$found = true;
								unset($result['multiaddress'][$key]);	
							}
							if(in_array("Base location",$regions['location'])){
								$regions['location'] = str_replace("Base location",$result['location'],$regions['location']);
								// Log::info($regions['location']);
								$result['multiaddress'][$key]['location'] = $regions['location'];
							}
						}
						foreach($result['multiaddress'] as $key => $regions){
							array_push($intersect,$regions);
						}
					}
					$resultobject->multiaddress = $intersect;
				}else{
					if(isset($result['multiaddress']) && count($result['multiaddress']) > 0){
						$resultobject->multiaddress = $result['multiaddress'];
					}else{
						$address = array("line1"=> $resultobject->contact->address,"line2"=>"", "line3"=>"", "location"=>array($resultobject->location),"landmark"=>"");
						$resultobject->multiaddress = array($address);
					}
				}

				// Decide vendor type
				$resultobject->vendor_type = "";
				if($result['category'] != "personal trainer"){
					if($result['category'] != "dietitians and nutritionists" && $result['category'] != "healthy snacks and beverages" && $result['category'] != "healthy tiffins"){

						if($result['business_type'] == 0){
							$resultobject->vendor_type = "Trainer";
						}else{
							$resultobject->vendor_type = "Outlet";
						}
					}else{
						if($result['category'] == "dietitians and nutritionists" ){
							$resultobject->vendor_type = "";
						}elseif($result['category'] == "healthy tiffins"){
							$resultobject->vendor_type = "Healthy tiffins";
						}else{
							$resultobject->vendor_type = "Healthy snacks";
						}
					}
				}else{
					$resultobject->vendor_type = "Trainer";
				}

				// Booktrial caption button
				$resultobject->booktrial_button_caption = "";

                $nobooktrialCategories = ['healthy snacks and beverages','swimming pools','sports','dietitians and nutritionists','sport nutrition supliment stores'];
				if($resultobject->commercial_type != 0){
					if(!in_array($result['category'],$nobooktrialCategories)){
						if($result['category'] != "healthy tiffins"){
							if( in_array('free trial',$result['facilities']) ){
								$resultobject->booktrial_button_caption = "Book a free trial";
							}else{
								$resultobject->booktrial_button_caption = "Book a trial";
							}
						}else{
							$resultobject->booktrial_button_caption = "Book a trial Meal";
						}
					}
				}
				if(count($keys) > 0){
					$newObj = array();
					foreach ($keys as $key){
						isset($resultobject->$key) ? $newObj[$key]=$resultobject->$key : null;
					}
					$finder = $newObj;
				}else{
					$finder = $resultobject;
				}
				$resultobject->vendor_type = "";
				if($result['category'] != "personal trainer"){
					if($result['category'] != "dietitians and nutritionists" && $result['category'] != "healthy snacks and beverages"){
						if($result['business_type'] == 0){
							$resultobject->vendor_type = "Trainer";
						}else{
							$resultobject->vendor_type = "Outlet";
						}
					}else{
						$resultobject->vendor_type = "";
					}
				}else{
					$resultobject->vendor_type = "Trainer";
				}

				array_push($finderresult_response->results->results, $finder);
			}
		}
		unset($finderresult_response->meta);
		unset($finderresult_response->results->resultlist);
		unset($finderresult_response->results->aggregationlist);



		$aggs = $es_searchresult_response['aggregations'];

		$finderresult_response->results->aggregations->budget = array();
		$budval0 = new \stdClass();
		$budval1 = new \stdClass();
		$budval2 = new \stdClass();
		$budval3 = new \stdClass();
		$budval4 = new \stdClass();
		$budval5 = new \stdClass();
		// print_r($aggs['filtered_budgets']['budgets']['buckets']);
		// exit;
		if(count($aggs['filtered_budgets']['budgets']['buckets']) > 0){
			
			foreach ($aggs['filtered_budgets']['budgets']['buckets'] as $bud) {
				switch ($bud['key']) {
					case 'one':
						$budval0->name = 'less than 1000';
						$budval0->count = $bud['doc_count'];

						break;
					case 'two':
						$budval1->name = '1000-2500';
						$budval1->count = $bud['doc_count'];

						break;
					case 'three':
						$budval2->name = '2500-5000';
						$budval2->count = $bud['doc_count'];

						break;
					case 'four':
						$budval3->name = '5000-7500';
						$budval3->count = $bud['doc_count'];

						break;
					case 'five':
						$budval4->name = '7500-15000';
						$budval4->count = $bud['doc_count'];

						break;
					case 'six':
						$budval5->name = '15000 & Above';
						$budval5->count = $bud['doc_count'];
						break;
					default:
						break;
				}
			}
			array_push($finderresult_response->results->aggregations->budget, $budval0);
			array_push($finderresult_response->results->aggregations->budget, $budval1);
			array_push($finderresult_response->results->aggregations->budget, $budval2);
			array_push($finderresult_response->results->aggregations->budget, $budval3);
			array_push($finderresult_response->results->aggregations->budget, $budval4);
			array_push($finderresult_response->results->aggregations->budget, $budval5);
		}

		$finderresult_response->results->aggregations->filters = array();
		$noBasicFilterCategories = ['healthy snacks and beverages','healthy tiffins','dietitians and nutritionists','sport nutrition supliment stores'];
        if(!in_array($resultCategory,$noBasicFilterCategories)){
			foreach ($aggs['filtered_facilities']['facilities']['buckets'] as $fac) {
				$facval = new \stdClass();
				$facval->name = $fac['key'];
				$facval->slug = str_replace(' ', '-', $fac['key']);
				$facval->count = $fac['doc_count'];
				
				if($device_type == 'android'){
					array_push($finderresult_response->results->aggregations->filters, $fac['key']);
				}else{
					array_push($finderresult_response->results->aggregations->filters, $facval);
				}
			}
		}

		$finderresult_response->results->aggregations->locationcluster = array();
		$cityfound = true;
		if(isset($search_request["city"])){
			$cityResponse = ifCityPresent($search_request["city"]);
			$cityfound = $cityResponse["found"];
		}
		if($cityfound){
			foreach ($aggs['filtered_locations']['loccluster']['buckets'] as $cluster) {
				$clusterval = new \stdClass();
				$clusterval->name = $cluster['key'];
				$clusterval->slug = strtolower(str_replace(' ', '-', $cluster['key']));
				$clusterval->count = $cluster['doc_count'];
				$clusterval->regions = array();
				if(isset($cluster['region']['attrs'])){
					foreach ($cluster['region']['attrs']['buckets'] as $reg) {
						$regval = new \stdClass();
						$regval->name = $reg['key'];
						$regval->slug = $reg['attrsValues']['buckets'][0]['key'];
						$regval->count = $reg['doc_count'];
						array_push($clusterval->regions, $regval);
					}
				}
				array_push($finderresult_response->results->aggregations->locationcluster, $clusterval);
			}
		}

		$finderresult_response->results->aggregations->subcategories = array();

		foreach ($aggs['filtered_offerings']['offerings']['buckets'] as $off){
			$offval = new \stdClass();
			$offval->name = $off['key'];
			$offval->slug = str_replace(' ', '-', $off['key']);
			$offval->count = $off['doc_count'];

			if($device_type == 'android'){
				array_push($finderresult_response->results->aggregations->subcategories, $off['key']);
			}else{
				array_push($finderresult_response->results->aggregations->subcategories, $offval);
			}
		}

		// $finderresult_response->results->aggregations->vip_trial = array();

		// foreach ($aggs['filtered_vip_trial']['vip_trial']['buckets'] as $off){
		// 	$offval = new \stdClass();
		// 	$offval->key = $off['key'];
		// 	$offval->count = $off['doc_count'];
		// 	array_push($finderresult_response->results->aggregations->vip_trial, $offval);
		// }
		if(isset($search_request['with_locationtags']) && $search_request['with_locationtags'] == 1){
			$finderresult_response->results->aggregations->locationtags = array();
			foreach ($aggs['filtered_locationtags']['offerings']['attrs']['buckets'] as $off){
				$offval = new \stdClass();
				$offval->name = $off['key'];
				$offval->slug = $off['attrsValues']['buckets'][0]['key'];
				//$offval->cluster = $off['locationcluster']['buckets'][0]['key'];
				$offval->count = $off['doc_count'];
				array_push($finderresult_response->results->aggregations->locationtags, $offval);
			}
		}

	
	if(isset($aggs['filtered_trials']['level1'])){
		$finderresult_response->results->aggregations->trialdays = array();

			foreach ($aggs['filtered_trials']['level1']['level2']['daysaggregator']['buckets'] as $off){
				$offval = new \stdClass();
				$offval->name = $off['key'];
				$offval->slug = str_replace(' ', '-', $off['key']);
				$offval->count = $off['backtolevel1']['backtorootdoc']['doc_count'];
				array_push($finderresult_response->results->aggregations->trialdays, $offval);
			}
			$weekdays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
			$trialdays = json_decode(json_encode($finderresult_response->results->aggregations->trialdays), true);
			$trialdays = sorting_array($trialdays, "key", $weekdays, false);
			$finaltrialdays = array();
			foreach($trialdays as $day){
				if($day["key"] != ""){
					$day["slug"] = $day["key"]."-open";
					$day["key"] = $day["key"]." open";
					array_push($finaltrialdays, $day);
				}
				//  print_r($days["key"]);
				// exit;
			}
			$finderresult_response->results->aggregations->trialdays = $finaltrialdays;
		}

		$finderresult_response->results->aggregations->categories = array();

		if($device_type == 'android'){

			foreach (citywise_categories($currentcity) as $key => $value) {

				array_push($finderresult_response->results->aggregations->categories, $value['name']);
			}

		}else{

			$finderresult_response->results->aggregations->categories = citywise_categories($currentcity);
		}

		
		// print_r($finderresult_response);
		return $finderresult_response;
	}




}

?>
