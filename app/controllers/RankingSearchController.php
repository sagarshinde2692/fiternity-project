<?php
/**
 * Created by PhpStorm.
 * User: ajay
 * Date: 14/7/15
 * Time: 7:41 PM
 */

use App\Services\Translator;
use App\Responsemodels\FinderresultResponse;

class RankingSearchController extends \BaseController
{

    protected $indice = "fitternity";
    protected $facetssize = 10000;
    protected $limit = 10;
    protected $elasticsearch_port = "";
    protected $elasticsearch_default_index = "";
    protected $elasticsearch_url = "";
    protected $elasticsearch_default_url = "";

    public function __construct()
    {
        parent::__construct();

        $this->elasticsearch_default_url = "http://" . Config::get('app.elasticsearch_host_new') . ":" . Config::get('app.elasticsearch_port_new') . '/' . Config::get('app.elasticsearch_default_index') . '/' . Config::get('app.elasticsearch_default_type') . '/';
        $this->elasticsearch_url = "http://" . Config::get('app.elasticsearch_host_new') . ":" . Config::get('app.elasticsearch_port_new') . '/';
        $this->elasticsearch_host = Config::get('app.elasticsearch_host_new');
        $this->elasticsearch_port = Config::get('app.elasticsearch_port_new');
        $this->elasticsearch_default_index = Config::get('app.elasticsearch_default_index');
    }

    public function getRankedFinderResults()
    {

        $searchParams = array();
        $facetssize =  $this->facetssize;
        $rankField = 'rankv2';
        $type = "finder";
        $filters = "";
        $from =  (Input::json()->get('from')) ? Input::json()->get('from') : 0;
        $size =  (Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;
        $location = (Input::json()->get('location')) ? Input::json()->get('location') : 'mumbai';
        $orderfield  = (Input::json()->get('sort')) ? Input::json()->get('sort') : '';
        $order = (Input::json()->get('order')) ? Input::json()->get('order') : '';
        //input filters
        $category = Input::json()->get('category');

        $trial_time_from = (Input::json()->get('trialfrom')) ? Input::json()->get('trialfrom') : '';
        $trial_time_to = (Input::json()->get('trialto')) ? Input::json()->get('trialto') : '';

        $location_filter =  '{"term" : { "city" : "'.$location.'", "_cache": true }},';
        $category_filter =  Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"],"_cache": true}},': '';
        $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('budget'))).'"],"_cache": true}},': '';        
        $regions_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"],"_cache": true}},'  : '';
        $region_tags_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "region_tags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"],"_cache": true}},'  : '';
        $offerings_filter = ((Input::json()->get('offerings'))) ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"],"_cache": true}},'  : '';
        $facilities_filter = ((Input::json()->get('facilities'))) ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"],"_cache": true}},'  : '';
        $trials_day_filter = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "service_weekdays": ["'.str_ireplace(',', '","',Input::json()->get('trialdays')).'"],"_cache": true}},'  : '';
        $trial_range_filter = '';
        if(($trial_time_from !== '')&&($trial_time_to !== '')){
            $trial_range_filter = '  {
                "nested": {
                  "path": "trials",
                  "query": {
                    "filtered": {
                        "filter": {"bool": {"must": [
                        {"range": {
                            "start": {
                              "gte": '.$trial_time_from.'
                          }
                      }},
                      {
                        "range": {
                          "end": {
                            "lte": '.$trial_time_to.'
                        }
                    }
                }
                ]}}
            }}
        }
    },';
}


$should_filtervalue = trim($regions_filter.$region_tags_filter,',');
$must_filtervalue = trim($location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$trials_day_filter.$trial_range_filter,',');
        $shouldfilter = '"should": ['.$should_filtervalue.'],'; //used for location
        $mustfilter = '"must": ['.$must_filtervalue.']';        //used for offering and facilities

        $filtervalue = trim($shouldfilter.$mustfilter,',');



        if($orderfield == 'popularity')
        {
            if($category_filter != '') {
                $factor = evalBaseCategoryScore($category);
                $sort = '"sort":
                {"_script" : {
                    "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
                    "type" : "number",
                    "params" : {

                        "factor" : 11

                    },
                    "order" : "' . $order . '"
                }}';
            }
            else{
                $sort = '"sort":[{"rankv2":{"order":"'.$order.'"}}]';
            }

        }
        else
        {
            $sort = '"sort":[{"'.$orderfield.'":{"order":"'.$order.'"}}]';
        }
        if($shouldfilter != '' || $mustfilter != ''){
            $filters = '"filter": {
                "bool" : {'.$filtervalue.'}
            },"_cache" : true';
        }

        $budgets_facets = '"budget": {"terms": {"field": "price_range","min_doc_count":0,"size":"500","order":{"_term": "asc"}}},';
        $regions_facets = '"loccluster": {
            "terms": {
                "field": "locationcluster",
                "min_doc_count":1
                
            },"aggs": {
              "region": {
                "terms": {
                    "field": "location",
                    "min_doc_count":1,
                    "size":"500",
                    "order": {
                      "_term": "asc"
                  }
                  
              }
          }
      }
  },';

  $location_facets = '"locations": {"terms": {"field": "locationtags","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';
  $offerings_facets = '"offerings": {"terms": {"field": "offerings","min_doc_count":0,"size":"500","order": {"_term": "asc"}}},';
  $facilities_facets = '"facilities": {"terms": {"field": "facilities","min_doc_count":0,"size":"500","order": {"_term": "asc"}}},';
  $facetsvalue = trim($regions_facets.$location_facets.$offerings_facets.$facilities_facets.$budgets_facets,',');

  $body = '{
    "from": '.$from.',
    "size": '.$size.',
    "aggs": {'.$facetsvalue.'},
    "query": {

        "filtered": {
            '.$filters.'
        }
    },
    '.$sort.'
}';

$request = array(
    'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity_finder/finder/_search",
    'port' => 8050,
    'method' => 'POST',
    'postfields' => $body
    );

$search_results     =   es_curl_request($request);

$response       =   [

'search_results' => json_decode($search_results,true)];

return Response::json($response);

}

public function CategoryAmenities()
{
    $category =  (Input::json()->get('category')) ? Input::json()->get('category') : '';
    $city     =  (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';
    $city_id = 0;
    switch ($city) {
        case 'mumbai':
        $city_id = 1;
        break;
        case 'pune':
        $city_id = 2;
        break;
        case 'bangalore':
        $city_id = 3;
        break;
        case 'delhi':
        $city_id = 4;
        break;
        case 'hyderabad':
        $city_id = 5;
        break;
        case 'ahmedabad':
        $city_id = 6;
        break; 
        case 'gurgaon':
        $city_id = 8;
        break;           
        default:                
        break;
    }
    
    $categorytag_offerings = '';
    if($category != '')
    {
        $categorytag_offerings = Findercategorytag::active()
        ->where('slug', '=', url_slug(array($category)))
        ->whereIn('cities',array($city_id))
        ->with('offerings')
        ->orderBy('ordering')
        ->get(array('_id','name','offering_header','slug','status','offerings'));
    } 
    

    $meta_title = $meta_description = $meta_keywords = '';
    if($category != ''){
        $findercategory     =   Findercategory::active()->where('slug', '=', url_slug(array($category)))->first(array('meta'));
        $meta_title         = $findercategory['meta']['title'];
        $meta_description   = $findercategory['meta']['description'];
        $meta_keywords      = $findercategory['meta']['keywords'];
    }                                 
    $resp  =    array(
        'meta_title' => $meta_title,
        'meta_description' => $meta_description,
        'meta_keywords' => $meta_keywords,                                        
        'catoff' => $categorytag_offerings
        );
        //return Response::json($search_results); exit;
    return Response::json($resp);
    

}

public function getcategories(){
    $city_id     =  (Input::json()->get('city_id')) ? Input::json()->get('city_id') : 'mumbai';
    $categorytags           =       Findercategorytag::active()->whereIn('cities',array($city_id))->orderBy('ordering')->get(array('name','_id','slug'));

    return Response::json($categorytags);        
}

public function getsearchmetadata(){
    $category = (Input::json()->get('category')) ? Input::json()->get('category') : 'All-Fitness';
    $meta_title = $meta_description = $meta_keywords = '';       
    if($category != ''){
        $findercategory     =   Findercategory::where('slug', '=', url_slug(array($category)))->first(array('meta'));
        $meta_title         = $findercategory['meta']['title'];
        $meta_description   = $findercategory['meta']['description'];
        $meta_keywords      = $findercategory['meta']['keywords'];
    } 

    $resp  =    array(
        'meta_title' => $meta_title,
        'meta_description' => $meta_description,
        'meta_keywords' => $meta_keywords           
        );
    
    return Response::json($resp);
}

public function CategoryAmenitiesv2()
{
    $category =  (Input::json()->get('category')) ? Input::json()->get('category') : '';
    $city_id     =  (Input::json()->get('city')) ? Input::json()->get('city') : 1;

    $categorytag_offerings = '';
    if($category != '')
    {
        $categorytag_offerings = Findercategorytag::active()
        ->where('slug', '=', url_slug(array($category)))
        ->whereIn('cities',array($city_id))
        ->with('offerings')
        ->orderBy('ordering')
        ->get(array('_id','name','offering_header','slug','status','offerings'));
    } 
    
    
    $meta_title = $meta_description = $meta_keywords = '';
    if($category != ''){
        $findercategory     =   Findercategory::active()->where('slug', '=', url_slug(array($category)))->first(array('meta'));
        $meta_title         = $findercategory['meta']['title'];
        $meta_description   = $findercategory['meta']['description'];
        $meta_keywords      = $findercategory['meta']['keywords'];
    }                                 
    $resp  =    array(
        'meta_title' => $meta_title,
        'meta_description' => $meta_description,
        'meta_keywords' => $meta_keywords,                                        
        'catoff' => $categorytag_offerings
        );
        //return Response::json($search_results); exit;
    return Response::json($resp);
    
}

public function getRankedFinderResultsMobile()
{
    $searchParams = array();
    $facetssize =  $this->facetssize;
    $rankField = 'rankv2';
    $type = "finder";
    $filters = "";
    $from =  (Input::json()->get('from')) ? Input::json()->get('from') : 0;
    $size =  (Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;
    $location = (Input::json()->get('location')) ? Input::json()->get('location') : 'mumbai';
    $orderfield  = (Input::json()->get('sort')) ? Input::json()->get('sort') : '';
    $order = (Input::json()->get('order')) ? Input::json()->get('order') : '';
    $lat = (Input::json()->get('lat')) ? Input::json()->get('lat') : 0 ;
    $lon = (Input::json()->get('lon')) ? Input::json()->get('lon') : 0 ; 
        //input filters
    $category = Input::json()->get('category');

    $trial_time_from = (Input::json()->get('trialfrom')) ? Input::json()->get('trialfrom') : '';
    $trial_time_to = (Input::json()->get('trialto')) ? Input::json()->get('trialto') : '';



    $location_filter =  '{"term" : { "city" : "'.$location.'", "_cache": true }},';
    $category_filter =  Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"],"_cache": true}},': '';
    $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('budget'))).'"],"_cache": true}},': '';        
    $regions_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"],"_cache": true}},'  : '';
    $region_tags_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "region_tags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"],"_cache": true}},'  : '';
    $offerings_filter = ((Input::json()->get('offerings'))) ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"],"_cache": true}},'  : '';
    $facilities_filter = ((Input::json()->get('facilities'))) ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"],"_cache": true}},'  : '';
    $trials_day_filter = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "service_weekdays": ["'.str_ireplace(',', '","',Input::json()->get('trialdays')).'"],"_cache": true}},'  : '';

    $trial_range_filter = '';
    if(($trial_time_from !== '')&&($trial_time_to !== '')){
        $trial_range_filter = '  {
            "nested": {
              "path": "trials",
              "query": {
                "filtered": {
                    "filter": {"bool": {"must": [
                    {"range": {
                        "start": {
                          "gte": '.$trial_time_from.'
                      }
                  }},
                  {
                    "range": {
                      "end": {
                        "lte": '.$trial_time_to.'
                    }
                }
            }
            ]}}
        }}
    }
},';
}

$should_filtervalue = trim($regions_filter.$region_tags_filter,',');
$must_filtervalue = trim($location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$trials_day_filter.$trial_range_filter,',');
        $shouldfilter = '"should": ['.$should_filtervalue.'],'; //used for location
        $mustfilter = '"must": ['.$must_filtervalue.']';        //used for offering and facilities

        $filtervalue = trim($shouldfilter.$mustfilter,',');



        if($orderfield == 'popularity')
        {
            if($category_filter != '') {
                $factor = evalBaseCategoryScore($category);
                $sort = '"sort":
                {"_script" : {
                    "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
                    "type" : "number",
                    "params" : {

                        "factor" : 11

                    },
                    "order" : "' . $order . '"
                }}';
            }
            else{
                $sort = '"sort":[{"rankv2":{"order":"'.$order.'"}}]';
            }

        }
        else
        {
            $sort = '"sort":[{"'.$orderfield.'":{"order":"'.$order.'"}}]';
        }
        if($shouldfilter != '' || $mustfilter != ''){
            $filters = '"filter": {
                "bool" : {'.$filtervalue.'}
            },"_cache" : true';
        }

        $budgets_facets = '"budget": {"terms": {"field": "price_range","min_doc_count":0,"size":"500","order":{"_term": "asc"}}},';
        $regions_facets = '"loccluster": {
            "terms": {
                "field": "locationcluster",
                "min_doc_count":1
                
            },"aggs": {
              "region": {
                "terms": {
                    "field": "location",
                    "min_doc_count":1,
                    "size":"500",
                    "order": {
                      "_term": "asc"
                  }
                  
              }
          }
      }
  },';

  $location_facets = '"locations": {"terms": {"field": "locationtags","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';
  $offerings_facets = '"offerings": {"terms": {"field": "offerings","min_doc_count":0,"size":"500","order": {"_term": "asc"}}},';
  $facilities_facets = '"facilities": {"terms": {"field": "facilities","min_doc_count":0,"size":"500","order": {"_term": "asc"}}},';
  $facetsvalue = trim($regions_facets.$location_facets.$offerings_facets.$facilities_facets.$budgets_facets,',');

  $body = '{
    "fields": ["_source"],
    "script_fields":{
        "distance": {
          "params": {
            "lat": '.$lat.',
            "lon": '.$lon.'
        },
        "script": "doc[\'geolocation\'].distanceInKm(lat,lon)"
    }
},
"from": '.$from.',
"size": '.$size.',
"aggs": {'.$facetsvalue.'},
"query": {

    "filtered": {
        '.$filters.'
    }
},
'.$sort.'
}';

$request = array(
    'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity_finder/finder/_search",
    'port' => 8050,
    'method' => 'POST',
    'postfields' => $body
    );


$search_results     =   es_curl_request($request);

$response       =   [

'search_results' => json_decode($search_results,true)];

return Response::json($response);

}

public function getRankedFinderResultsApp()
{
    // echo "yo";
   // return Input::json()->all();
    $searchParams = array();
    $facetssize =  $this->facetssize;
    $rankField = 'rankv2';
    $type = "finder";
    $filters = "";
    $from    =         Input::json()->get('offset')['from'];
    $size    =         Input::json()->get('offset')['number_of_records'] ? Input::json()->get('offset')['number_of_records'] : 10;
    //$location =        (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';
    $orderfield  =     (Input::json()->get('sort')) ? Input::json()->get('sort')['sortfield'] : '';
    $order   =         (Input::json()->get('sort')) ? Input::json()->get('sort')['order'] : '';
    $location    =         Input::json()->get('location')['city'] ? strtolower(Input::json()->get('location')['city']): 'mumbai';
    $locat = Input::json()->get('location');
    $lat     =         (isset($locat['lat'])) ? $locat['lat']  : '';
    $lon    =         (isset($locat['long'])) ? $locat['long']  : '';
        //input filters

    $category = Input::json()->get('category');

    $trial_time_from = (Input::json()->get('trialfrom')) ? Input::json()->get('trialfrom') : '';
    $trial_time_to = (Input::json()->get('trialto')) ? Input::json()->get('trialto') : '';


    //return Input::json()->get('offset')['from'];

    $location_filter =  '{"term" : { "city" : "'.$location.'", "_cache": true }},';
    $category_filter = Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.strtolower(Input::json()->get('category')).'"],"_cache": true}},': '';
    $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.strtolower(implode('","', Input::json()->get('budget'))).'"],"_cache": true}},': '';
    $regions_filter = Input::json()->get('regions') ? '{"terms" : {  "locationtags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
    $region_tags_filter = Input::json()->get('regions') ? '{"terms" : {  "region_tags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
    $offerings_filter = Input::json()->get('offerings') ? '{"terms" : {  "offerings": ["'.strtolower(implode('","', Input::json()->get('offerings'))).'"],"_cache": true}},': '';
    $facilities_filter = Input::json()->get('facilities') ? '{"terms" : {  "facilities": ["'.strtolower(implode('","', Input::json()->get('facilities'))).'"],"_cache": true}},': '';
    $trials_day_filter = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "service_weekdays": ["'.strtolower(implode('","', Input::json()->get('trialdays'))).'"],"_cache": true}},'  : '';
    
    $trial_range_filter = '';
    if(($trial_time_from !== '')&&($trial_time_to !== '')){
        $trial_range_filter = '  {
            "nested": {
              "path": "trials",
              "query": {
                "filtered": {
                    "filter": {"bool": {"must": [
                    {"range": {
                        "start": {
                          "gte": '.$trial_time_from.'
                      }
                  }},
                  {
                    "range": {
                      "end": {
                        "lte": '.$trial_time_to.'
                    }
                }
            }
            ]}}
        }}
    }
},';
}

$should_filtervalue = trim($regions_filter.$region_tags_filter,',');
$must_filtervalue = trim($location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$trials_day_filter.$trial_range_filter,',');
        $shouldfilter = '"should": ['.$should_filtervalue.'],'; //used for location
        $mustfilter = '"must": ['.$must_filtervalue.']';        //used for offering and facilities

        $filtervalue = trim($shouldfilter.$mustfilter,',');

        if($orderfield == 'popularity')
        {
            if($category_filter != '') {
                $factor = evalBaseCategoryScore($category);
                $sort = '"sort":
                {"_script" : {
                    "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
                    "type" : "number",
                    "params" : {

                        "factor" : 11

                    },
                    "order" : "' . $order . '"
                }}';
            }
            else{
                $sort = '"sort":[{"rankv2":{"order":"'.$order.'"}}]';
            }

        }
        else
        {
            $sort = '"sort":[{"'.$orderfield.'":{"order":"'.$order.'"}}]';
        }
        if($shouldfilter != '' || $mustfilter != ''){
            $filters = '"filter": {
                "bool" : {'.$filtervalue.'}
            },"_cache" : true';
        }

        $budgets_facets = '"budget": {"terms": {"field": "price_range","min_doc_count":0,"size":"500","order":{"_term": "asc"}}},';
        $regions_facets = '"loccluster": {
            "terms": {
                "field": "locationcluster",
                "min_doc_count":1
                
            },"aggs": {
              "region": {
                "terms": {
                    "field": "location",
                    "min_doc_count":1,
                    "size":"500",
                    "order": {
                      "_term": "asc"
                  }
                  
              }
          }
      }
  },';

  $location_facets = '"locations": {"terms": {"field": "locationtags","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';
  $offerings_facets = '"offerings": {"terms": {"field": "offerings","min_doc_count":0,"size":"500","order": {"_term": "asc"}}},';
  $facilities_facets = '"facilities": {"terms": {"field": "facilities","include" : "personal training|free trial|group classes|locker and shower facility|parking|sunday open","min_doc_count":0,"size":"500","order": {"_term": "asc"}}},';
  $facetsvalue = trim($regions_facets.$location_facets.$offerings_facets.$facilities_facets.$budgets_facets,',');

  $body = '{
    "from": '.$from.',
    "size": '.$size.',
    "aggs": {'.$facetsvalue.'},
    "query": {

        "filtered": {
            '.$filters.'
        }
    },
    '.$sort.'
}';

$request = array(
    'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity_finder/finder/_search",
    'port' => 8050,
    'method' => 'POST',
    'postfields' => $body
    );

$search_results     =   es_curl_request($request);
$search_results1    =   json_decode($search_results, true);

$searchresulteresponse = Translator::translate_searchresults($search_results1);
$searchresulteresponse->meta->number_of_records = $size;
$searchresulteresponse->meta->from = $from;
$searchresulteresponse->meta->sortfield = $orderfield;
$searchresulteresponse->meta->sortorder = $order;

$searchresulteresponse1 = json_encode($searchresulteresponse, true);

$response       =   json_decode($searchresulteresponse1,true);

return Response::json($response);

}

public function getRankedFinderResultsAppv2()
{
    // echo "yo";
   // return Input::json()->all();
    $searchParams = array();
    $facetssize =  $this->facetssize;
    $rankField = 'rankv2';
    $type = "finder";
    $filters = "";
    $from    =         Input::json()->get('offset')['from'];
    $size    =         Input::json()->get('offset')['number_of_records'] ? Input::json()->get('offset')['number_of_records'] : 10;
    //$location =        (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';
    $orderfield  =     (Input::json()->get('sort')) ? Input::json()->get('sort')['sortfield'] : '';
    $order   =         (Input::json()->get('sort')) ? Input::json()->get('sort')['order'] : '';
    $location    =         Input::json()->get('location')['city'] ? strtolower(Input::json()->get('location')['city']): 'mumbai';
    $locat = Input::json()->get('location');
    $lat     =         (isset($locat['lat'])) ? $locat['lat']  : '';
    $lon    =         (isset($locat['long'])) ? $locat['long']  : '';
        //input filters

    $category = Input::json()->get('category');
   
    $trial_time_from = Input::json()->get('trialfrom') !== null ? Input::json()->get('trialfrom') : '';
    $trial_time_to = Input::json()->get('trialto') !== null ? Input::json()->get('trialto') : '';
     
    // return $category;
    $offering_regex = $this->_getOfferingRegex($category);

    //return Input::json()->get('offset')['from'];
    $must_not_filter = '';
    
    if($category === ''){
        $must_not_filter = ',
                    "must_not": [{
                        "terms": {
                            "categorytags": [
                                "healthy tiffins",
                                "healthy snacks and beverages",
                                "sport nutrition supliment stores",
                                "dietitians and nutritionists"
                            ]
                        }
                    }]
                ';
    }

    $location_filter =  '{"term" : { "city" : "'.$location.'", "_cache": true }},';
    $category_filter = Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.strtolower(Input::json()->get('category')).'"],"_cache": true}},': '';
    $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.strtolower(implode('","', Input::json()->get('budget'))).'"],"_cache": true}},': '';
    $regions_filter = Input::json()->get('regions') ? '{"terms" : {  "locationtags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
    $region_tags_filter = Input::json()->get('regions') ? '{"terms" : {  "region_tags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
    $offerings_filter = Input::json()->get('offerings') ? '{"terms" : {  "offerings": ["'.strtolower(implode('","', Input::json()->get('offerings'))).'"],"_cache": true}},': '';
    $facilities_filter = Input::json()->get('facilities') ? '{"terms" : {  "facilities": ["'.strtolower(implode('","', Input::json()->get('facilities'))).'"],"_cache": true}},': '';
    $trials_day_filter = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "service_weekdays": ["'.strtolower(implode('","', Input::json()->get('trialdays'))).'"],"_cache": true}},'  : '';
    
    $trial_range_filter = '';
    if(($trial_time_from !== '')&&($trial_time_to !== '')){

        $trial_range_filter = '  {
            "nested": {
              "path": "trials",
              "query": {
                "filtered": {
                    "filter": {"bool": {"must": [
                    {"range": {
                        "start": {
                          "gte": '.$trial_time_from.'
                      }
                  }},
                  {
                    "range": {
                      "end": {
                        "lte": '.$trial_time_to.'
                    }
                }
            }
            ]}}
        }}
    }
},';
}

$should_filtervalue = trim($regions_filter.$region_tags_filter,',');
$must_filtervalue = trim($location_filter.$regions_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$trials_day_filter.$trial_range_filter,',');
        $shouldfilter = '"should": ['.$should_filtervalue.'],'; //used for location
        $mustfilter = '"must": ['.$must_filtervalue.']';        //used for offering and facilities
        $mustfilter_post = '"must": ['.$must_filtervalue.']';
          $filtervalue_post = trim($mustfilter_post,',');
        $filtervalue = trim($shouldfilter.$mustfilter,',');

        if($orderfield == 'popularity')
        {
            if($category_filter != '') {
                $factor = evalBaseCategoryScore($category);
                $sort = '"sort":
                {"_script" : {
                    "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
                    "type" : "number",
                    "params" : {

                        "factor" : 11

                    },
                    "order" : "' . $order . '"
                }}';
            }
            else{
                $sort = '"sort":[{"rankv2":{"order":"'.$order.'"}}]';
            }

        }
        else
        {
            $sort = '"sort":[{"'.$orderfield.'":{"order":"'.$order.'"}}]';
        }
        if($shouldfilter != '' || $mustfilter != ''){
            $filters = '"filter": {
                "bool" : {'.$filtervalue.'}
            },"_cache" : true';
        }

        if($mustfilter != ''){
             $filters_post = '"post_filter": {
                "bool" : {'.$filtervalue_post.$must_not_filter.'
            }},';            
        }

        /*

        Aggregations filters here for drilling down

        */

        $location_facets_filter = trim($location_filter.$category_filter,',');
        $facilities_facets_filter = trim($location_filter.$regions_filter.$category_filter, ',');
        $offerings_facets_filter = trim($location_filter.$regions_filter.$facilities_filter.$category_filter, ',');
        $budgets_facets_filter = trim($location_filter.$regions_filter.$facilities_filter.$offerings_filter.$category_filter, ',');
        $trialday_facets_filter = trim($location_filter.$regions_filter.$facilities_filter.$offerings_filter.$category_filter.$budget_filter, ',');

        $facilities_bool = '"filter": {
            "bool" : { "must":['.$facilities_facets_filter.']}
        }';

        $offering_bool = '"filter": {
            "bool" : {"must":['.$offerings_facets_filter.']}
        }';

        $budgets_bool = '"filter": {
            "bool" : {"must":['.$budgets_facets_filter.']}
        }';

        $location_bool = '"filter": {
            "bool" : {"must":['.$location_facets_filter.']}
        }';

        $trialdays_bool = '"filter": {
            "bool" : {"must":['.$trialday_facets_filter.']}
        }';

        $regions_facets = '
        "filtered_locations": { '.$location_bool.', 
        "aggs":
        { "loccluster": {
            "terms": {
                "field": "locationcluster",
                "min_doc_count":1

            },"aggs": {
              "region": {
                "terms": {
                    "field": "location",
                    "min_doc_count":1,
                    "size":"500",
                    "order": {
                      "_term": "asc"
                  }

              }
          }
      }}}
  },';


  $locationtags_facets = ' "filtered_locationtags": {
    '.$location_bool.',
    "aggs": {
        "offerings": {
            "terms": {
                "field": "locationtags",             
                "min_doc_count": 1,
                "size": 500,
                "order":{"_term": "asc"}
            }
        }
    }
},';

  $facilities_facets = ' "filtered_facilities": {
    '.$facilities_bool.',
    "aggs": {
        "facilities": {
            "terms": {
                "field": "facilities",
                "include" : "personal training|free trial|group classes|locker and shower facility|parking|sunday open",
                "min_doc_count": 0,
                "size": 500,
                "order":{"_term": "asc"}
            }
        }
    }
},';

$offerings_facets = ' "filtered_offerings": {
    '.$offering_bool.',
    "aggs": {
        "offerings": {
            "terms": {
                "field": "offerings",
                "include" : "'.$offering_regex.'",
                "min_doc_count": 1,
                "size": 500,
                "order":{"_term": "asc"}
            }
        }
    }
},';

$budgets_facets = ' "filtered_budgets": {
    '.$budgets_bool.',
    "aggs": {
        "budgets": {
            "terms": {
                "field": "price_range",
                "min_doc_count": 0,
                "size": 500,
                "order":{"_term": "asc"}
            }
        }
    }
},';

$trialdays_facets = ' "filtered_trials": {
    '.$trialdays_bool.',
    "aggs": {
        "trialdays": {
            "terms": {
                "field": "service_weekdays",
                "min_doc_count": 0,
                "size": 500,
                "order":{"_term": "asc"}
            }
        }
    }
},';


$category_facets = '"category": {"terms": {"field": "category","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';

$facetsvalue = trim($regions_facets.$locationtags_facets.$facilities_facets.$offerings_facets.$budgets_facets.$trialdays_facets.$category_facets,',');
 
  $body = '{
    "from": '.$from.',
    "size": '.$size.',
    "aggs": {'.$facetsvalue.'},
    '.$filters_post.$sort.'
}';
    
 
$request = array(
    'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity_finder/finder/_search",
    'port' => 8050,
    'method' => 'POST',
    'postfields' => $body
    );

// $request = array(
//     'url' => "http://localhost:9200/"."fitternity2016-04-16/finder/_search",
//     'port' => 9200,
//     'method' => 'POST',
//     'postfields' => $body
//     );
    // return $body;exit;
$search_results     =   es_curl_request($request);
$search_results1    =   json_decode($search_results, true);
$searchresulteresponse = Translator::translate_searchresultsv2($search_results1);
$searchresulteresponse->meta->number_of_records = intval($size);
$searchresulteresponse->meta->from = intval($from);
$searchresulteresponse->meta->sortfield = $orderfield;
$searchresulteresponse->meta->sortorder = $order;

$searchresulteresponse1 = json_encode($searchresulteresponse, true);

$response       =   json_decode($searchresulteresponse1,true);

return Response::json($response);

}

private function _getOfferingRegex($category){
    $regex = '';
   
    switch($category)
    {
        case 'spinning and indoor cycling':
        $regex = 'ac|gym|health bar|indoor|live dj|nutritional support|outdoor activities';
        break;
        case 'personal trainers' :
        $regex = 'gender - female|mma & kickboxing|yoga|dietitian & nutritionist|pilates|zumba|aerobics|strength training|equipment-provided|certified trainer|gender - male|crossfit|free trial|functional training';
        break;
        case 'gyms':
        $regex = 'steam and sauna|juice bar|cycling studio|swimming pool|nutritional support|free wifi|strength training equipment|spinning|strectching area|personal training|24 hour facility|get your own trainer|music and video entertainment|massages|physiotherapy|cardio equipment';
        break;

         case 'yoga':
        $regex = 'ashtanga yoga|aerial yoga|hatha yoga|vinyassa yoga|power yoga|prenatal yoga|hot yoga|hot yoga|post natal yoga|iyengar yoga';
        break;

         case 'zumba':
        $regex = 'aqua zumba|zumba classes';
        break;

         case 'cross functional training':
        $regex = 'trampoline workout|les mills|calisthenics|cross training|group x training|TRX training|combine training';
        break;

         case 'dance':
        $regex = 'belly dancing|krumping|locking and poppin|b boying|contemporary|hip hop|free style|rumba|zouk|kathak|bharatanatyam|odissi|ballroom|tango|ballet|jazz|salsa|bollywood|cha cha ch|waltz|bachata|masala bhangra|samba|paso doble|rock n roll|jive|';
        break;

         case 'fitness studios':
        $regex = 'dance|aerobics|pilates|zumba|yoga|functional training|mma and kickboxing';
        break;

         case 'crossfit':
        $regex = 'workshops|kettlebell training|olympic lifting|indoor|outdoor|open box|cardio equipment|personal training|tyres & ropes|gymnastic routines|group training';
        break;

         case 'kick boxing':
        $regex = '';
        break;

         case 'pilates':
        $regex = 'reformer or stott pilates|mat pilates';
        break;

         case 'mma and kick boxing':
        $regex = 'muay thai|kick boxing|capoeira|judo|kung fu|kalaripayattu|krav maga|tai chi|mixed martial arts|taekwondo|karate|jujitsu';
        break;

         case 'healthy tiffins':
        $regex = 'only non veg|salad|calorie counted|cuisine - international|cuisine - jain|meal type - dinner only|vegan meals|meal type - lunch only|veg and non veg|meal type - lunch and dinner|trial available|only veg|cuisine - indian';
        break;

         case 'marathon training':
        $regex = 'beach training|individual training|group training|road training';
        break;

         case 'swimming':
        $regex = 'steam and sauna|jaccuzi|olympic pool|indoor pool|outdoor pool';
        break;

         case 'dietitians and nutritionists':
        $regex = 'meals provided|body fat analysis|child nutrition|pregnancy nutrition|medical or disorder related|telephonic  or  online consultation|weight management|sports nutrition';
        break;

        case 'sport nutrition supliment stores':
        $regex = 'Post Workout Supplements|pre workout Supplements|Accessories|Nutrition Bar|Protein Supplements|Energy & Endurance Supplements|Lean Muscle Gainer|Lean Mass Gainer|Weight Gainer|Fat Burners|Health & Wellness Supplements|Muscle Gainer|Soy Protein|Whey Protein|Fish Oil|Shakers|Mass Gainers|Food Supplements|';
        break;
        case '':
        $regex  = ' ';
        break;

        default :
        $regex = "nothing";
        break;
    }

    return strtolower($regex);
}
}
