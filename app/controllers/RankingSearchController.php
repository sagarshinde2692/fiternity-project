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

        $this->elasticsearch_default_url = "http://" . Config::get('app.es.host') . ":" . Config::get('app.es.port') . '/' . Config::get('app.es.default_index') . '/' . Config::get('app.es.default_type') . '/';
        $this->elasticsearch_url = "http://" . Config::get('app.es.host') . ":" . Config::get('app.es.port') . '/';
        $this->elasticsearch_host = Config::get('app.es.host');
        $this->elasticsearch_port = Config::get('app.es.port');
        $this->elasticsearch_default_index = Config::get('app.es.default_index');
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
            'url' => Config::get('app.es.url')."/fitternity_finder/finder/_search",
            'port' => Config::get('app.es.port'),
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
            case 'noida':
                $city_id = 9;
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

        /*$city_id     =  (Input::json()->get('city_id')) ? Input::json()->get('city_id') : 'mumbai';
        $categorytags           =       Findercategorytag::active()->whereIn('cities',array($city_id))->orderBy('ordering')->get(array('name','_id','slug'));

        return Response::json($categorytags);  */

        $city_id     =  (Input::json()->get('city_id')) ? (int)Input::json()->get('city_id') : 1;

        $category_slug = array(
            "gyms",
            "yoga",
            "zumba",
            "cross-functional-training",
            "pilates",
            "crossfit",
            "mma-and-kick-boxing",
            "dance",
            "fitness-studios",
            "marathon-training",
            "healthy-tiffins",
            "personal-trainers",
            "swimming",
            "spinning-and-indoor-cycling",
            "aerobics",
            //"luxury-hotels",
            "healthy-snacks-and-beverages",
            "sport-nutrition-supliment-stores",
            "kids-fitness",
            "dietitians-and-nutritionists"
        );

        $category           =       Findercategory::active()->where('cities',$city_id)->whereIn('slug',$category_slug)->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'))->toArray();

        $ordered_category = array();

        foreach ($category_slug as $category_slug_key => $category_slug_value){

            foreach ($category as $category_key => $category_value){

                if($category_value['slug'] == $category_slug_value){

                    $ordered_category[] = $category_value;
                    break;
                }
            }
        }

        return Response::json($ordered_category);
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
            'url' => Config::get('app.es.url')."/fitternity_finder/finder/_search",
            'port' => Config::get('app.es.port'),
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


        $region = Input::json()->get('regions');
        $locationCount = 0;
        if(count($region) == 1){

            $region_slug = str_replace(' ', '-',strtolower(trim($region[0])));

            $locationCount = Location::where('slug',$region_slug)->count();

            if($locationCount > 0){
                $lat = "";
                $lon = "";
            }

        }else{
            $lat = "";
            $lon = "";
            $locationCount = count($region);
        }

        //return Input::json()->get('offset')['from'];

        $location_filter =  '{"term" : { "city" : "'.$location.'", "_cache": true }},';
        $category_filter = Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.strtolower(Input::json()->get('category')).'"],"_cache": true}},': '';
        $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.strtolower(implode('","', Input::json()->get('budget'))).'"],"_cache": true}},': '';
        $regions_filter = Input::json()->get('regions') && $locationCount > 0 ? '{"terms" : {  "locationtags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $region_tags_filter = Input::json()->get('regions') && $locationCount > 0 ? '{"terms" : {  "region_tags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $offerings_filter = Input::json()->get('offerings') ? '{"terms" : {  "offerings": ["'.strtolower(implode('","', Input::json()->get('offerings'))).'"],"_cache": true}},': '';
        $facilities_filter = Input::json()->get('facilities') ? '{"terms" : {  "facilities": ["'.strtolower(implode('","', Input::json()->get('facilities'))).'"],"_cache": true}},': '';
        $trials_day_filter = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "service_weekdays": ["'.strtolower(implode('","', Input::json()->get('trialdays'))).'"],"_cache": true}},'  : '';
        $geo_location_filter   =   ($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';

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
        $must_filtervalue = trim($location_filter.$offerings_filter.$facilities_filter.$category_filter.$regions_filter.$geo_location_filter.$budget_filter.$trials_day_filter.$trial_range_filter,',');
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
            'url' => Config::get('app.es.url')."/fitternity_finder/finder/_search",
            'port' => Config::get('app.es.port'),
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
        $vip_trial    =         Input::json()->get('vip_trial') ? array(intval(Input::json()->get('vip_trial'))) : [1,0];
        $vip_trial = implode($vip_trial,',');
        $locat = Input::json()->get('location');
        $lat     =         (isset($locat['lat'])) ? $locat['lat']  : '';
        $lon    =         (isset($locat['long'])) ? $locat['long']  : '';
        $keys   =         (Input::json()->get('keys')) ? Input::json()->get('keys') : array();

        $object_keys = array();
        //input filters
        
        $category = Input::json()->get('category');

        $trial_time_from = Input::json()->get('trialfrom') !== null ? Input::json()->get('trialfrom') : '';
        $trial_time_to = Input::json()->get('trialto') !== null ? Input::json()->get('trialto') : '';


        $region = Input::json()->get('regions');
        $locationCount = 0;
        if(count($region) == 1){

            $region_slug = str_replace(' ', '-',strtolower(trim($region[0])));

            $locationCount = Location::where('slug',$region_slug)->count();

            if($locationCount > 0){
                $lat = "";
                $lon = "";
            }

        }else{
            $lat = "";
            $lon = "";
            $locationCount = count($region);
        }

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
                "dietitians and nutritionists",
                "personal trainers"
                ]
            }
        }]
        ';
        }

        $geo_location_filter   =   ($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';

        $free_trial_enable = Input::json()->get('free_trial_enable');
        $trial_filter = '';
        if(intval($free_trial_enable) == 1){
            $trial_filter =  Input::json()->get('free_trial_enable') ? '{"term" : { "free_trial_enable" : '.intval($free_trial_enable).',"_cache": true }},' : '';

        }
        $vip_trial_filter =  Input::json()->get('vip_trial') ? '{"terms" : { "vip_trial" : ['.$vip_trial.'],"_cache": true }},' : '';
//    $vip_trial_filter =  '{"terms" : { "vip_trial" : ['.$vip_trial.'],"_cache": true }},';
        $location_filter =  '{"term" : { "city" : "'.$location.'", "_cache": true }},';
        $commercial_type_filter = Input::json()->get('commercial_type') ? '{"terms" : {  "commercial_type": ['.implode(',', Input::json()->get('commercial_type')).'],"_cache": true}},': '';
        $category_filter = Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.strtolower(Input::json()->get('category')).'"],"_cache": true}},': '';
        $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.strtolower(implode('","', Input::json()->get('budget'))).'"],"_cache": true}},': '';
        $regions_filter = Input::json()->get('regions') && $locationCount > 0 ? '{"terms" : {  "locationtags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $region_tags_filter = Input::json()->get('regions') && $locationCount > 0 ? '{"terms" : {  "region_tags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $offerings_filter = Input::json()->get('offerings') ? '{"terms" : {  "offerings": ["'.strtolower(implode('","', Input::json()->get('offerings'))).'"],"_cache": true}},': '';
        $facilities_filter = Input::json()->get('facilities') ? '{"terms" : {  "facilities": ["'.strtolower(implode('","', Input::json()->get('facilities'))).'"],"_cache": true}},': '';
        $trials_day_filter = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "service_weekdays": ["'.strtolower(implode('","', Input::json()->get('trialdays'))).'"],"_cache": true}},'  : '';
        $trials_day_filterv2 = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "day": ["'.strtolower(implode('","', Input::json()->get('trialdays'))).'"],"_cache": true}},'  : '';
        $trial_range_filter = '';
        if(($trial_time_from !== '')&&($trial_time_to !== '')){

            $trial_range_filter = '  {
            "nested": {
              "path": "trials",
              "query": {
                "filtered": {
                    "filter": {"bool": {"must": [
                    {"range": {
                        "trials.start": {
                          "gte": '.$trial_time_from.'
                      }
                  }},
                  {
                    "range": {
                      "trials.end": {
                        "lte": '.$trial_time_to.'
                    }
                }
            }
            ]}}
        }}
    }
},';
        }

        $service_slots_filters = '';

        if(($trials_day_filter !== '')||($trial_time_from !== '')||($trial_time_to !== ''))
        {
            $service_slots_filters = ' {"nested": {
      "path": "service_level_data.slots_nested",
      "query": {"filtered": {
        "filter": {"bool": {"must": [
        '.trim($trials_day_filterv2.$trial_time_from.$trial_time_to, ',').'
    
    ]}
}
}}
}},';
        }

        $service_category_synonyms_filters = '';

        if(($category !== '')&&($category !== 'fitness studios'))
        {
            $service_category_synonyms_filters = '{
      "term": {
        "service_category_synonyms": "'.$category.'"
    }
},';
        }

        $all_nested_filters = trim($service_slots_filters.$service_category_synonyms_filters,',');

        $service_level_nested_filter = '';

        if($all_nested_filters !== '')
        {
            $service_level_nested_filter = '{
  "nested": {
    "path": "service_level_data",
    "query": {"filtered": {
      "filter": {"bool": {"must": [
      '.$all_nested_filters.'
      ]}}
  }}
}
},';
        }

        $should_filtervalue = trim($regions_filter.$region_tags_filter,',');

        $must_filtervalue = trim($trial_filter.$commercial_type_filter.$vip_trial_filter.$location_filter.$regions_filter.$geo_location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter,',');
        if($trials_day_filter !== ''){
            $must_filtervalue = trim($trial_filter.$commercial_type_filter.$vip_trial_filter.$location_filter.$regions_filter.$geo_location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$service_level_nested_filter,',');
        }

        $shouldfilter = '"should": ['.$should_filtervalue.'],'; //used for location
        $mustfilter = '"must": ['.$must_filtervalue.']';        //used for offering and facilities
        $mustfilter_post = '"must": ['.$must_filtervalue.']';
        $filtervalue_post = trim($mustfilter_post,',');
        $filtervalue = trim($shouldfilter.$mustfilter,',');

        if($orderfield == 'popularity')
        {
//            if($category_filter != '') {
//                $factor = evalBaseCategoryScore($category);
//                $sort = '"sort":
//                {"_script" : {
//                    "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
//                    "type" : "number",
//                    "params" : {
//
//                        "factor" : 11
//
//                    },
//                    "order" : "' . $order . '"
//                }}';
//            }
//            if($category_filter != '') {
            $sort = '"sort":[{"rank":{"order":"'.$order.'"}}]';
//            }
//            else{
//                $sort = '"sort":[{"rankv2":{"order":"'.$order.'"}}]';
//            }

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

        $nested_level1_filter = ($category_filter === '') ? '': '  {"nested": {
          "path": "service_level_data",
          "query": {"filtered": {
            "filter": {"bool": {"must": [
              {"term": {
                "service_category_synonyms": "'.$category.'"
              }}
            ]}}
          }}
        }}';

        $nested_level2_filter = '';

        $vip_trial_facets_filter = trim($commercial_type_filter.$vip_trial_filter.$location_filter.$category_filter,',');
        $location_facets_filter = trim($commercial_type_filter.$vip_trial_filter.$location_filter.$category_filter,',');
        $facilities_facets_filter = trim($commercial_type_filter.$vip_trial_filter.$location_filter.$regions_filter.$category_filter, ',');
        $offerings_facets_filter = trim($commercial_type_filter.$vip_trial_filter.$location_filter.$regions_filter.$facilities_filter.$category_filter, ',');
        $budgets_facets_filter = trim($commercial_type_filter.$vip_trial_filter.$location_filter.$regions_filter.$facilities_filter.$offerings_filter.$category_filter, ',');
        $trialday_facets_filter = trim($commercial_type_filter.$vip_trial_filter.$location_filter.$regions_filter.$facilities_filter.$offerings_filter.$category_filter.$budget_filter.$nested_level1_filter, ',');

        $facilities_bool = '"filter": {
            "bool" : { "must":['.$facilities_facets_filter.']}
        }';

        $offering_bool = '"filter": {
            "bool" : {"must":['.$offerings_facets_filter.']}
        }';

        $budgets_bool = '"filter": {
            "bool" : {"must":['.$budgets_facets_filter.']}
        }';

        $vip_trial_bool = '"filter": {
            "bool" : {"must":['.$vip_trial_facets_filter.']}
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

        $vip_trial_facets = ' "filtered_vip_trial": {
    '.$vip_trial_bool.',
    "aggs": {
        "vip_trial": {
            "terms": {
                "field": "vip_trial",
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
   "level1": {
     "nested": {
       "path": "service_level_data"
     },
     "aggs": {
       "level2": {
         "nested": {
           "path": "service_level_data.slots_nested"
         },
         "aggs": {
           "daysaggregator": {
             "terms": {
               "field": "day",
               "size": 10000,
               "min_doc_count" : 0
             },
             "aggs": {
               "backtolevel1": {
                 "reverse_nested": {
                   "path": "service_level_data"
                 },
                 "aggs": {
                   "backtorootdoc": {
                     "reverse_nested": {
                     }
                   }
                 }
               }
             }
           }
         }
       }
     }
   }
 }
},';


        $category_facets = '"category": {"terms": {"field": "category","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';

        $facetsvalue = trim($regions_facets.$locationtags_facets.$facilities_facets.$offerings_facets.$budgets_facets.$trialdays_facets.$category_facets.$vip_trial_facets,',');

        $body = '{
    "from": '.$from.',
    "size": '.$size.',
    "aggs": {'.$facetsvalue.'},
    '.$filters_post.$sort.'
}';


//    return $body;


        $request = array(
            'url' => Config::get('app.es.url')."/fitternity_finder/finder/_search",
            'port' => Config::get('app.es.port'),
            'method' => 'POST',
            'postfields' => $body
        );
        // return json_decode($body,true);
// $request = array(
//     'url' => "http://localhost:9200/"."fitternity_finder/finder/_search",
//     'port' => 9200,
//     'method' => 'POST',
//     'postfields' => $body
//     );

        $search_results     =   es_curl_request($request);

        $search_results1    =   json_decode($search_results, true);
        $search_request     =   Input::json()->all();
        $searchresulteresponse = Translator::translate_searchresultsv3($search_results1,$search_request);
        $searchresulteresponse->meta->number_of_records = intval($size);
        $searchresulteresponse->meta->from = intval($from);
        $searchresulteresponse->meta->sortfield = $orderfield;
        $searchresulteresponse->meta->sortorder = $order;
        $searchresulteresponse->meta->request = Input::all();
        $searchresulteresponse = $this->CustomResponse($searchresulteresponse, $keys);
        $searchresulteresponse1 = json_encode($searchresulteresponse, true);

        $response       =   json_decode($searchresulteresponse1,true);
        if($from == 0 && count(Input::json()->get('offerings')) == 0 && count(Input::json()->get('facilities')) == 0 && count(Input::json()->get('budget')) == 0 && $locationCount == 0){
            $response['campaign'] = array(
                'image'=>'http://b.fitn.in/iconsv1/fitmania/sale_banner.png',
                // 'link'=>'fitternity://www.fitternity.com/search/offer_available/true',
                'link'=>'',
                'title'=>'FitStart 2017',
                'height'=>1,
                'width'=>6,
                'ratio'=>1/6
            );
        }

        return Response::json($response);

    }

    public function CustomResponse($response, $keys) {

        if(count($keys) <= 0){
            return $response;
        }

        $resultlist = $response->results->resultlist;
        $responseaggregationlist = $response->results->aggregationlist;
        $responsemeta = $response->meta;

        $newResponse = array();
        $newResultList = array();
        $newRecord = array();

        foreach ($resultlist as $res){
            $res = $res->object;
            $newObj = array();
            foreach ($keys as $key){
                isset($res->$key) ? $newObj[$key]=$res->$key : null;
            }
            $newObj['offer_available'] = (isset($res->offer_available) && $res->offer_available != "") ? $res->offer_available : "";
            $newRecord['object'] = $newObj;
            array_push($newResultList,$newRecord);
        }

        $newResponse['results'] = array();
        $newResponse['results']['resultlist'] = $newResultList;
        $newResponse['results']['aggregationlist'] = $responseaggregationlist;
        $newResponse['meta'] = $responsemeta;

        return $newResponse;
    }


    /********************************************google places search api *************************************/

    public function getRankedFinderResultsAppv3()
    {
        // echo "yo";
        // return Input::json()->all();
        $searchParams = array();
        $facetssize =  $this->facetssize;
        $rankField = 'rankv2';
        $type = "finder";
        $filters = "";
        $from    =        (null !== Input::json()->get('offset')['from']) ? Input::json()->get('offset')['from'] : 0;
        $size    =         Input::json()->get('offset')['number_of_records'] ? Input::json()->get('offset')['number_of_records'] : 10;
        //$location =        (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';
        $orderfield  =     (Input::json()->get('sort')) ? Input::json()->get('sort')['sortfield'] : 'popularity';
        $order   =         (Input::json()->get('sort')) ? Input::json()->get('sort')['order'] : 'desc';
        $loc             = Input::json()->get('location');
        $location    =         isset($loc['city']) ? strtolower(Input::json()->get('location')['city']): '';
        $vip_trial    =         Input::json()->get('vip_trial') ? array(intval(Input::json()->get('vip_trial'))) : [1,0];
        $vip_trial = implode($vip_trial,',');
        $locat = Input::json()->get('location');
        $lat     =         (isset($locat['lat'])) ? floatval($locat['lat'])  : '';
        $lon    =         (isset($locat['long'])) ? floatval($locat['long'])  : '';



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

        $free_trial_enable = Input::json()->get('free_trial_enable');
        $trial_filter = '';
        if(intval($free_trial_enable) == 1){
            $trial_filter =  Input::json()->get('free_trial_enable') ? '{"term" : { "free_trial_enable" : '.intval($free_trial_enable).',"_cache": true }},' : '';

        }
        $vip_trial_filter =  Input::json()->get('vip_trial') ? '{"terms" : { "vip_trial" : ['.$vip_trial.'],"_cache": true }},' : '';
        $location_filter =  $location != '' ? '{"term" : { "city" : "'.$location.'", "_cache": true }},' : '';
        $category_filter = Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.strtolower(Input::json()->get('category')).'"],"_cache": true}},': '';
        $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.strtolower(implode('","', Input::json()->get('budget'))).'"],"_cache": true}},': '';
        $regions_filter = Input::json()->get('regions') ? '{"terms" : {  "locationtags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $region_tags_filter = Input::json()->get('regions') ? '{"terms" : {  "region_tags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $offerings_filter = Input::json()->get('offerings') ? '{"terms" : {  "offerings": ["'.strtolower(implode('","', Input::json()->get('offerings'))).'"],"_cache": true}},': '';
        $facilities_filter = Input::json()->get('facilities') ? '{"terms" : {  "facilities": ["'.strtolower(implode('","', Input::json()->get('facilities'))).'"],"_cache": true}},': '';
        $trials_day_filter = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "service_weekdays": ["'.strtolower(implode('","', Input::json()->get('trialdays'))).'"],"_cache": true}},'  : '';
        $trials_day_filterv2 = ((Input::json()->get('trialdays'))) ? '{"terms" : {  "day": ["'.strtolower(implode('","', Input::json()->get('trialdays'))).'"],"_cache": true}},'  : '';
        $trial_range_filter = '';
        $geo_range_filter = '';
        $distance_decay_function='';
        $distance_slabs_function='';



        if(($trial_time_from !== '')&&($trial_time_to !== '')){

            $trial_range_filter = '  {
            "nested": {
              "path": "trials",
              "query": {
                "filtered": {
                    "filter": {"bool": {"must": [
                    {"range": {
                        "trials.start": {
                          "gte": '.$trial_time_from.'
                      }
                  }},
                  {
                    "range": {
                      "trials.end": {
                        "lte": '.$trial_time_to.'
                    }
                }
            }
            ]}}
        }}
    }
},';
        }


        $function_score_complete_query = '';

        $source_fields = '';

        if(($lat !== '')&&($lon !== '')){
            $source_fields = ' "fields": ["_source"],
                                "script_fields":{
                                    "distance": {
                                      "params": {
                                        "lat": '.$lat.',
                                        "lon": '.$lon.'
                                    },
                                    "script": "doc[\'geolocation\'].distanceInKm(lat,lon)"
                                }
                            },';
        }

        $function_score_query_googleplaces = '';
        $sort = '';

        switch ($orderfield){

            case 'distance':
                if(($lat !== '')&&($lon !== '')){

                    $geo_range_filter = '{
            "geo_distance_range": {
                "from": "0km",
                "to": "1000km",
                "geolocation": {
                    "lat": '.$lat.',
                    "lon": '.$lon.'
                }
            }
        },';
                }

                $distance_score_function = '  {
                            "script_score": {
                                "params": {
                                    "lat": '.$lat.',
                                    "lon": '.$lon.'
                                },
                                "script": "doc[\'geolocation\'].distanceInKm(lat,lon) ? doc[\'geolocation\'].distanceInKm(lat,lon) : 0"

                            }
                        }';

                $function_score_query_googleplaces = $distance_score_function;
                $sort = '"sort":[{"_score":{"order":"'.$order.'"}}]';
                break;
            case 'average_price':
                //Either..........
//                $commercial_type_function = '{
//                            "script_score": {
//                                "script": "(doc[\'commercial_type\'].value == 1)||(doc[\'commercial_type\'].value == 3) ? 15000 : (doc[\'commercial_type\'].value == 2) ? 7000 : 0"
//                            }
//                        }';
//
//                $function_score_query_googleplaces = $commercial_type_function;
//
//                $sort = '"sort":[{"_score":{"order":"'.$order.'"},"'.$orderfield.'":{"order":"'.$order.'"}}]';
                // OR..........
                $sort = '"sort":[{"'.$orderfield.'":{"order":"'.$order.'"}}]';
                break;
            default:    // Popularity
                if(($lat !== '')&&($lon !== '')){

                    $geo_range_filter = '{
            "geo_distance_range": {
                "from": "0km",
                "to": "5km",
                "geolocation": {
                    "lat": '.$lat.',
                    "lon": '.$lon.'
                }
            }
        },';
                }

                $commercial_type_function = '{
                            "script_score": {
                                "script": "(doc[\'commercial_type\'].value == 1)||(doc[\'commercial_type\'].value == 3) ? 15000 : (doc[\'commercial_type\'].value == 2) ? 7000 : 0"
                            }
                        }';

                if(($lat !== '')&&($lon !== '')){
                    $distance_decay_function = ',{
                            "linear": {
                                "geolocation": {
                                    "origin": {
                                        "lat": '.$lat.',
                                        "lon": '.$lon.'
                                    },
                                    "scale": "100m",
                                    "decay": 0.95,
                                    "offset": "50m"
                                }
                            },
                            "weight": "0"
                        },';


                    $distance_slabs_function = '  {
                            "script_score": {
                                "params": {
                                    "lat": '.$lat.',
                                    "lon": '.$lon.'
                                },
                                "script": "(doc[\'geolocation\'].distanceInKm(lat,lon) <= 3)&&(doc[\'commercial_type\'].value != 0) ? 5000: (doc[\'geolocation\'].distanceInKm(lat,lon) <= 5)&&(doc[\'geolocation\'].distanceInKm(lat,lon) > 3)&&(doc[\'commercial_type\'].value != 0) ? 3000 : 0"
                            }
                        }';
                }

                $function_score_query_googleplaces = $commercial_type_function.$distance_decay_function.$distance_slabs_function;

                $sort = '"sort":[{"_score":{"order":"'.$order.'"},"'.$orderfield.'":{"order":"'.$order.'"}}]';
                break;

        }

        $function_score_query = '                                        
                                        "function_score": {
                                                "functions": ['.$function_score_query_googleplaces.'],
                                                "score_mode": "sum"
                                            }';

        $function_score_complete_query = ' "query": {"filtered": {
                                        "query": {'.$function_score_query.'}
                                      }},';


        $service_slots_filters = '';

        if(($trials_day_filter !== '')||($trial_time_from !== '')||($trial_time_to !== ''))
        {
            $service_slots_filters = ' {"nested": {
      "path": "service_level_data.slots_nested",
      "query": {"filtered": {
        "filter": {"bool": {"must": [
        '.trim($trials_day_filterv2.$trial_time_from.$trial_time_to, ',').'

        ]}
    }
}}
}},';
        }

        $service_category_synonyms_filters = '';

        if(($category !== '')&&($category !== 'fitness studios'))
        {
            $service_category_synonyms_filters = '{
      "term": {
        "service_category_synonyms": "'.$category.'"
    }
},';
        }

        $all_nested_filters = trim($service_slots_filters.$service_category_synonyms_filters,',');

        $service_level_nested_filter = '';

        if($all_nested_filters !== '')
        {
            $service_level_nested_filter = '{
      "nested": {
        "path": "service_level_data",
        "query": {"filtered": {
          "filter": {"bool": {"must": [
          '.$all_nested_filters.'
          ]}}
      }}
  }
},';
        }

        $should_filtervalue = trim($regions_filter.$region_tags_filter,',');

        $must_filtervalue = trim($vip_trial_filter.$trial_filter.$location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$geo_range_filter,',');
        if($trials_day_filter !== ''){
            $must_filtervalue = trim($vip_trial_filter.$trial_filter.$location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$service_level_nested_filter.$geo_range_filter,',');
        }

        $shouldfilter = '"should": ['.$should_filtervalue.'],'; //used for location
        $mustfilter = '"must": ['.$must_filtervalue.']';        //used for offering and facilities
        $mustfilter_post = '"must": ['.$must_filtervalue.']';
        $filtervalue_post = trim($mustfilter_post,',');
        $filtervalue = trim($shouldfilter.$mustfilter,',');






        //     if($category_filter != '') {
        //         $factor = evalBaseCategoryScore($category);
        //         $sort = '"sort":
        //         {"_script" : {
        //             "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
        //             "type" : "number",
        //             "params" : {

        //                 "factor" : 11

        //             },
        //             "order" : "' . $order . '"
        //         }}';
        //     }
        //     else{
        //         $sort = '"sort":[{"rankv2":{"order":"'.$order.'"}}]';
        //     }

        // }
        // else
        // {
        //     $sort = '"sort":[{"'.$orderfield.'":{"order":"'.$order.'"}}]';
        // }


        if($shouldfilter != '' || $mustfilter != ''){
            $filters = '"filter": {
                "bool" : {'.$filtervalue.'}
            },"_cache" : true';
        }

        if($mustfilter != ''){
            $filters_post = '"post_filter": {
            "bool" : {'.$filtervalue_post.$must_not_filter.'
        }}';
        }

        /*

        Aggregations filters here for drilling down

        */

        $nested_level1_filter = ($category_filter === '') ? '': '  {"nested": {
          "path": "service_level_data",
          "query": {"filtered": {
            "filter": {"bool": {"must": [
            {"term": {
                "service_category_synonyms": "'.$category.'"
            }}
            ]}}
        }}
    }}';

        $nested_level2_filter = '';

        $vip_trial_facets_filter = trim($location_filter.$category_filter,',');
        $location_facets_filter = trim($location_filter.$category_filter.$geo_range_filter,',');
        $facilities_facets_filter = trim($location_filter.$regions_filter.$category_filter.$geo_range_filter, ',');
        $offerings_facets_filter = trim($location_filter.$regions_filter.$facilities_filter.$category_filter.$geo_range_filter, ',');
        $budgets_facets_filter = trim($location_filter.$regions_filter.$facilities_filter.$offerings_filter.$category_filter.$geo_range_filter, ',');
        $trialday_facets_filter = trim($geo_range_filter.$location_filter.$regions_filter.$facilities_filter.$offerings_filter.$category_filter.$budget_filter.$nested_level1_filter, ',');

        $facilities_bool = '"filter": {
        "bool" : { "must":['.$facilities_facets_filter.']}
    }';

        $offering_bool = '"filter": {
        "bool" : {"must":['.$offerings_facets_filter.']}
    }';

        $budgets_bool = '"filter": {
        "bool" : {"must":['.$budgets_facets_filter.']}
    }';

        $vip_trial_bool = '"filter": {
            "bool" : {"must":['.$vip_trial_facets_filter.']}
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

        $vip_trial_facets = ' "filtered_vip_trial": {
    '.$vip_trial_bool.',
    "aggs": {
        "vip_trial": {
            "terms": {
                "field": "vip_trial",
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
     "level1": {
       "nested": {
         "path": "service_level_data"
     },
     "aggs": {
         "level2": {
           "nested": {
             "path": "service_level_data.slots_nested"
         },
         "aggs": {
             "daysaggregator": {
               "terms": {
                 "field": "day",
                 "size": 10000,
                 "min_doc_count" : 0
             },
             "aggs": {
                 "backtolevel1": {
                   "reverse_nested": {
                     "path": "service_level_data"
                 },
                 "aggs": {
                     "backtorootdoc": {
                       "reverse_nested": {
                       }
                   }
               }
           }
       }
   }
}
}
}
}
}
},';


        $category_facets = '"category": {"terms": {"field": "category","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';

        $facetsvalue = trim($regions_facets.$locationtags_facets.$facilities_facets.$offerings_facets.$budgets_facets.$trialdays_facets.$category_facets.$vip_trial_facets,',');


// $body = '{    
//     '.$source_fields.'
//     "from": '.$from.',
//     "size": '.$size.',
//     "query" : '.$function_score_query.',
//     "aggs": {'.$facetsvalue.'},
//     '.$filters_post.$sort.'
// }';

        $body = '{    
    '.$source_fields.'
    "from": '.$from.',
    "size": '.$size.',
    '.$function_score_complete_query.'
    "aggs": {'.$facetsvalue.'},
    '.$filters_post.','.$sort.'
}';

//        return $body;


        $request = array(
            'url' => Config::get('app.es.url')."/fitternity_finder/finder/_search",
            'port' => Config::get('app.es.port'),
            'method' => 'POST',
            'postfields' => $body
        );

// $request = array(
//     'url' => "http://localhost:9200/"."fitternity_finder/finder/_search",
//     'port' => 9200,
//     'method' => 'POST',
//     'postfields' => $body
//     );

        $search_results     =   es_curl_request($request);
//    return $search_results;

        $search_results1    =   json_decode($search_results, true);
        $searchresulteresponse = Translator::translate_searchresultsv3($search_results1);
        $searchresulteresponse->meta->number_of_records = intval($size);
        $searchresulteresponse->meta->from = intval($from);
        $searchresulteresponse->meta->sortfield = $orderfield;
        $searchresulteresponse->meta->sortorder = $order;

        $searchresulteresponse1 = json_encode($searchresulteresponse, true);

        $response       =   json_decode($searchresulteresponse1,true);

        return Response::json($response);

    }


    public function searchDirectPaymentEnabled(){

        $from = (Input::json()->get('from')) ? Input::json()->get('from') : 0;
        $size = (Input::json()->get('size')) ? Input::json()->get('size') :10;
        $city = (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';
        $category = (Input::json()->get('category')) ? Input::json()->get('category') : '';
        $group_by_flag = (Input::json()->get('group_by_flag')) ? Input::json()->get('group_by_flag') : false;

        $city_filter =  '{"term" : { "city" : "'.$city.'", "_cache": true }},';
        $category_filter = Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.strtolower(Input::json()->get('category')).'"],"_cache": true}},': '';
        $region_filter = Input::json()->get('regions') ? '{"terms" : {  "locationtags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $direct_payment_filter = '{"term" : {  "direct_payment_enable": true,"_cache": true}},';

        $post_filter = trim($direct_payment_filter.$category_filter.$city_filter.$region_filter,',');



        $category_regex = $this->_getCategoryRegex($city);
        //return json_decode($group_by_flag);
        if($group_by_flag){


            $body = '{
            "from": '.$from.',
            "size": '.$size.',
            "query": {
                "filtered": {
                    "filter": {
                        "bool": {
                            "must": [{
                                "term": {
                                    "city": "'.$city.'"
                                }
                            }, {
                                "term": {
                                    "direct_payment_enable": true
                                }
                            }]
                        }
                    }
                }
            },
            "aggs": {
                "grouped_by_category": {
                    "terms": {
                        "field": "categorytags",
                        "size": 10000
                    },
                    "aggs": {
                        "grouped_by_category_hits": {
                            "top_hits": {
                                "sort": [{
                                    "rankv2": {
                                        "order": "desc"
                                    }
                                }],
                                "size": 100000000
                            }
                        }
                    }
                }
            }
        }';

        }else{


            $Post_filter_query = '
        "bool": {"must": [
        '.$post_filter.'
        ]}
        ';

            $query = '"query": {"filtered": {
            "filter": {"bool": {"must": [
            '.trim($city_filter.$direct_payment_filter,',').'
            ]}}
        }},';


            $location_tags_facets = '"filtered_cluster_locations_tags": {
            "filter": {
                "bool": {
                    "must": [
                    '.trim($city_filter.$category_filter.$direct_payment_filter,',').'
                    ]
                }
            },
            "aggs": {
                "loccluster": {
                    "terms": {
                        "field": "locationcluster",
                        "min_doc_count": 1
                    },
                    "aggs": {
                        "region": {
                            "terms": {
                                "field": "location",
                                "min_doc_count": 1,
                                "size": "500",
                                "order": {
                                    "_term": "asc"
                                }
                            }
                        }
                    }
                }
            }
        }';

            $location_facets = '"filtered_location_tags": {
            "filter": {
                "bool": {
                    "must": [
                    '.trim($city_filter.$category_filter.$direct_payment_filter,',').'
                    ]
                }
            },
            "aggs": {
                "locationtags": {
                    "terms": {
                        "field": "locationtags",
                        "min_doc_count": 1,
                        "size": 500,
                        "order": {
                            "_term": "asc"
                        }
                    }
                }
            }
        },
        ';

            $category_aggregations = '"aggs": {
            "category_grouping": {
              "terms": {
                "field": "categorytags",
                "size": 1000,
                "include" : "'.$category_regex.'"
            }
        },'.$location_facets.$location_tags_facets.'
    },';





            $sort = '';

            if($category != '') {

                $factor = evalBaseCategoryScore($category);
                $sort = '"sort":
        {"_script" : {
            "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
            "type" : "number",
            "params" : {

                "factor" : 11

            },
            "order" : "desc"
        }}';
            }

            else{
                $sort = '"sort":[{"rankv2":{"order":"desc"}}]';
            }


            $body = '{
        "from": '.$from.',
        "size": '.$size.',
        '.$category_aggregations.'
        '.$query.'
        '.$sort.',
        "post_filter" : {'.$Post_filter_query.'}
    }';

        }

        $request = array(
            'url' => Config::get('app.es.url')."/fitternity_finder/finder/_search",
            'port' => Config::get('app.es.port'),
            'method' => 'POST',
            'postfields' => $body
        );



// $request = array(
//     'url' => "http://localhost:9200/"."fitternity_finder/finder/_search",
//     'port' => 9200,
//     'method' => 'POST',
//     'postfields' => $body
//     );

        $search_results     =   es_curl_request($request);

        $response       =   [

            'search_results' => json_decode($search_results,true)];

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

            case 'luxury hotels':
                $regex = 'cardio equipment|weights section|stretching workout area|other fitness activities|group activities|steam and sauna|indoor swimming pool|outdoor swimming pool|jacuzzi|spa|personal training';
                break;

            case 'dietitians and nutritionists':
                $regex = 'meals provided|body fat analysis|child nutrition|pregnancy nutrition|medical or disorder related|telephonic  or  online consultation|weight management|sports nutrition';
                break;

            case 'sport nutrition supliment stores':
                $regex = 'Post Workout Supplements|pre workout Supplements|Accessories|Nutrition Bar|Protein Supplements|Energy & Endurance Supplements|Lean Muscle Gainer|Lean Mass Gainer|Weight Gainer|Fat Burners|Health & Wellness Supplements|Muscle Gainer|Soy Protein|Whey Protein|Fish Oil|Shakers|Mass Gainers|Food Supplements|';
                break;

            case 'pre-natal classes':
                $regex = 'Lamaze Class|Prenatal Yoga|';
                break;

            case 'aerial fitness':
                $regex = 'Aerial Ring|Aerial Silk|Aerial Yoga|Nine Month Module';
                break;

            case 'bootcamp':
                $regex = 'Indoor|Outdoor|';
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

    private function _getCategoryRegex($city){

        $regex = '';
        switch ($city) {
            case 'mumbai':
                $regex = 'gyms|yoga|zumba|fitness studios|crossfit|pilates|healthy tiffins|cross functional training|mma And kick boxing|dance|marathon training|spinning and indoor cycling|personal trainers|healthy snacks and beverages|dietitians and nutritionists|swimming|sport nutrition supliment stores';
                break;
            case 'delhi':
                $regex = 'gyms|yoga|zumba|fitness studios|crossfit|pilates|cross functional training|mma And kick boxing|dance|spinning and indoor cycling';
                break;
            case 'bangalore':
                $regex = 'gyms|yoga|zumba|fitness studios|crossfit|pilates|healthy tiffins|cross functional Training|mma And kick boxing|dance|spinning and indoor cycling';
                break;
            case 'pune':
                $regex = 'gyms|yoga|zumba|fitness studios|crossfit|pilates|cross functional training|mma And kick boxing|dance|spinning and indoor cycling';
                break;
            case 'gurgaon':
                $regex = 'gyms|yoga|zumba|fitness studios|crossfit|pilates|cross functional training|mma And kick boxing|dance|spinning and indoor cycling';
                break;
            case 'noida':
                $regex = 'gyms|yoga|zumba|fitness studios|crossfit|pilates|cross functional training|mma And kick boxing|dance|spinning and indoor cycling';
                break;

            default:
                # code...
                break;
        }

        return $regex;

    }

// public function 

public function getRankedFinderResultsAppv4()
    {
        $searchParams       = array();
        $facetssize         =  $this->facetssize;
        $rankField          = 'rankv2';
        $type               = "finder";
        $filters            = "";
        $from               =         Input::json()->get('offset')['from'];
        $size               =         Input::json()->get('offset')['number_of_records'] ? Input::json()->get('offset')['number_of_records'] : 10;
        $orderfield         =     (Input::json()->get('sort')) ? Input::json()->get('sort')['sortfield'] : '';
        $order              =         (Input::json()->get('sort')) ? Input::json()->get('sort')['order'] : '';
        $location           =         Input::json()->get('location')['city'] ? strtolower(Input::json()->get('location')['city']): 'mumbai';
        // $vip_trial       =         Input::json()->get('vip_trial') ? array(intval(Input::json()->get('vip_trial'))) : [1,0];
        // $vip_trial       = implode($vip_trial,',');
        $locat              = Input::json()->get('location');
        $lat                =         (isset($locat['lat'])) ? $locat['lat']  : '';
        $lon                =         (isset($locat['long'])) ? $locat['long']  : '';
        $keys               =         (Input::json()->get('keys')) ? Input::json()->get('keys') : array();
        $category           = Input::json()->get('category');
        $trial_time_from    = Input::json()->get('session-start-time') !== null ? Input::json()->get('session-start-time') : '';
        $trial_time_to      = Input::json()->get('session-end-time') !== null ? Input::json()->get('session-end-time') : '';
        $region             = Input::json()->get('regions');
        $offerings          = Input::json()->get('subcategories');
        $facilities         = Input::json()->get('facilities');
        $budget             = Input::json()->get('budget');
        $trialdays          = Input::json()->get('trialdays');
        $other_filters          = Input::json()->get('other_filters');
        foreach ($other_filters as $filter){
            // $budget_filters = ["one","two","three","four","five","six"];
            $trialdays_filters = ["sunday open","monday open","tuesday open","wednesday open","friday open","saturday open"];
            if(in_array($filter, $trialdays_filters)){
                array_push(str_replace(" open","",$filter),$trialdays);
            }else{
                array_push($filter,$facilities);
            }
        }
        $object_keys        = array();

        $locationCount = 0;
        if(count($region) == 1){
            $region_slug = str_replace(' ', '-',strtolower(trim($region[0])));
            $locationCount = Location::where('slug',$region_slug)->count();
            if($locationCount > 0){
                $lat = "";
                $lon = "";
            }
        }else{
            $lat = "";
            $lon = "";
            $locationCount = count($region);
        }
        $offering_regex = $this->_getOfferingRegex($category);
        $must_not_filter = '';

        if($category === ''){
            $must_not_filter = ',
                "must_not": [{
                    "terms": {
                        "categorytags": [
                        "healthy tiffins",
                        "healthy snacks and beverages",
                        "sport nutrition supliment stores",
                        "dietitians and nutritionists",
                        "personal trainers"
                        ]
                    }
                }]';
        }
        $geo_location_filter   =   ($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';
        $free_trial_enable     = Input::json()->get('free_trial_enable');
        $trial_filter          = '';

        if(intval($free_trial_enable) == 1){
            $trial_filter      =  Input::json()->get('free_trial_enable') ? '{"term" : { "free_trial_enable" : '.intval($free_trial_enable).',"_cache": true }},' : '';
        }
        // $vip_trial_filter =  Input::json()->get('vip_trial') ? '{"terms" : { "vip_trial" : ['.$vip_trial.'],"_cache": true }},' : '';
//    $vip_trial_filter =  '{"terms" : { "vip_trial" : ['.$vip_trial.'],"_cache": true }},';
        $location_filter        =  '{"term" : { "city" : "'.$location.'", "_cache": true }},';
        $commercial_type_filter = Input::json()->get('commercial_type') ? '{"terms" : {  "commercial_type": ['.implode(',', Input::json()->get('commercial_type')).'],"_cache": true}},': '';
        $category_filter        = Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.strtolower(Input::json()->get('category')).'"],"_cache": true}},': '';
        $budget_filter          = $budget ? '{"terms" : {  "price_range": ["'.strtolower(implode('","', $budget)).'"],"_cache": true}},': '';
        $regions_filter         = Input::json()->get('regions') && $locationCount > 0 ? '{"terms" : {  "locationtags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $region_tags_filter     = Input::json()->get('regions') && $locationCount > 0 ? '{"terms" : {  "region_tags": ["'.strtolower(implode('","', Input::json()->get('regions'))).'"],"_cache": true}},': '';
        $offerings_filter       = $offerings ? '{"terms" : {  "offerings": ["'.strtolower(implode('","', $offerings)).'"],"_cache": true}},': '';
        $facilities_filter      = $facilities ? '{"terms" : {  "facilities": ["'.strtolower(implode('","', $facilities)).'"],"_cache": true}},': '';
        $trials_day_filter      = (($trialdays)) ? '{"terms" : {  "service_weekdays": ["'.strtolower(implode('","', $trialdays)).'"],"_cache": true}},'  : '';
        $trials_day_filterv2    = (($trialdays)) ? '{"terms" : {  "day": ["'.strtolower(implode('","', $trialdays)).'"],"_cache": true}},'  : '';
        $trial_range_filter     = '';

        if(($trial_time_from !== '')&&($trial_time_to !== '')){
            $trial_range_filter = '{
                "nested": {
                    "path": "trials",
                    "query": {
                        "filtered": {
                            "filter": {
                                "bool": {
                                    "must": [{
                                        "range": {
                                            "trials.start": {
                                                "gte": '.$trial_time_from.'
                                            }
                                        }
                                    }, {
                                        "range": {
                                            "trials.end": {
                                                "lte": '.$trial_time_to.'
                                            }
                                        }
                                    }]
                                }
                            }
                        }
                    }
                }
            },';
        }
        $service_slots_filters      = '';
        if(($trials_day_filter !== '')||($trial_time_from !== '')||($trial_time_to !== ''))
        {
            $service_slots_filters = '
                {
                    "nested": {
                        "path": "service_level_data.slots_nested",
                        "query": {
                            "filtered": {
                                "filter": {
                                    "bool": {
                                        "must": [
                                            '.trim($trials_day_filterv2.$trial_time_from.$trial_time_to, ', ').'
                                        ]
                                    }
                                }
                            }
                        }
                    }
                },';
        }
        $service_category_synonyms_filters = '';
        if(($category !== '')&&($category !== 'fitness studios'))
        {
            $service_category_synonyms_filters = '
                {
                    "term": {
                        "service_category_synonyms": "'.$category.'"
                    }
                },';
        }

        $all_nested_filters = trim($service_slots_filters.$service_category_synonyms_filters,',');

        $service_level_nested_filter = '';

        if($all_nested_filters !== '')
        {
            $service_level_nested_filter = '
                {
                    "nested": {
                        "path": "service_level_data",
                        "query": {
                            "filtered": {
                                "filter": {
                                    "bool": {
                                        "must": [
                                            '.$all_nested_filters.'
                                        ]
                                    }
                                }
                            }
                        }
                    }
                },';
        }
        $should_filtervalue     = trim($regions_filter.$region_tags_filter,',');
        $must_filtervalue       = trim($trial_filter.$commercial_type_filter.$location_filter.$regions_filter.$geo_location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter,',');
        if($trials_day_filter !== ''){
            $must_filtervalue   = trim($trial_filter.$commercial_type_filter.$location_filter.$regions_filter.$geo_location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$service_level_nested_filter,',');
        }

        $shouldfilter       = '"should": ['.$should_filtervalue.'],'; //used for location
        $mustfilter         = '"must": ['.$must_filtervalue.']';        //used for offering and facilities
        $mustfilter_post    = '"must": ['.$must_filtervalue.']';
        $filtervalue_post   = trim($mustfilter_post,',');
        $filtervalue        = trim($shouldfilter.$mustfilter,',');

        if($orderfield == 'popularity'){
                $sort = '"sort":[{"rank":{"order":"'.$order.'"}}]';
        }
        else{
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

        // $nested_level1_filter = ($category_filter === '') ? '': '  {"nested": {
        //   "path": "service_level_data",
        //   "query": {"filtered": {
        //     "filter": {"bool": {"must": [
        //       {"term": {
        //         "service_category_synonyms": "'.$category.'"
        //       }}
        //     ]}}
        //   }}
        // }}';
        $nested_level1_filter = "";
        if($category != ""){
            $nested_level1_filter = $category_filter === '' ? '{"terms" : {  "service_category_synonyms": "'.strtolower($category).'","_cache": true}},': '';
        }
        

        $nested_level2_filter = '';

        // $vip_trial_facets_filter = trim($commercial_type_filter.$vip_trial_filter.$location_filter.$category_filter,',');
        $location_facets_filter = trim($commercial_type_filter.$location_filter.$category_filter,',');
        $facilities_facets_filter = trim($commercial_type_filter.$location_filter.$regions_filter.$category_filter, ',');
        $offerings_facets_filter = trim($commercial_type_filter.$location_filter.$regions_filter.$facilities_filter.$category_filter, ',');
        $budgets_facets_filter = trim($commercial_type_filter.$location_filter.$regions_filter.$facilities_filter.$offerings_filter.$category_filter, ',');
        $trialday_facets_filter = trim($commercial_type_filter.$location_filter.$regions_filter.$facilities_filter.$offerings_filter.$category_filter.$budget_filter.$nested_level1_filter, ',');

        $facilities_bool = '"filter": {
            "bool" : { "must":['.$facilities_facets_filter.']}
        }';

        $offering_bool = '"filter": {
            "bool" : {"must":['.$offerings_facets_filter.']}
        }';

        $budgets_bool = '"filter": {
            "bool" : {"must":['.$budgets_facets_filter.']}
        }';

        // $vip_trial_bool = '"filter": {
        //     "bool" : {"must":['.$vip_trial_facets_filter.']}
        // }';

        $location_bool = '"filter": {
            "bool" : {"must":['.$location_facets_filter.']}
        }';

        $trialdays_bool = '"filter": {
            "bool" : {"must":['.$trialday_facets_filter.']}
        }';

        $regions_facets = '
        "filtered_locations": { '.$location_bool.', 
        "aggs":{ 
            "loccluster": {
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


        $locationtags_facets = ' 
        "filtered_locationtags": {
            '.$location_bool.',
            "aggs": {
                "offerings": {
                    "terms": {
                        "field": "locationtags",
                        "min_doc_count": 1,
                        "size": 500,
                        "order": {
                            "_term": "asc"
                        }
                    }
                }
            }
        },';

        $facilities_facets = '
        "filtered_facilities": {
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

        $offerings_facets = '
        "filtered_offerings": {
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

        $budgets_facets = '
        "filtered_budgets": {
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

//         $vip_trial_facets = ' "filtered_vip_trial": {
//     '.$vip_trial_bool.',
//     "aggs": {
//         "vip_trial": {
//             "terms": {
//                 "field": "vip_trial",
//                 "min_doc_count": 0,
//                 "size": 500,
//                 "order":{"_term": "asc"}
//             }
//         }
//     }
// },';

        $trialdays_facets = ' 
        "filtered_trials": {
            '.$trialdays_bool.',
            "aggs": {
                "level1": {
                    "nested": {
                        "path": "service_level_data"
                    },
                    "aggs": {
                        "level2": {
                            "nested": {
                                "path": "service_level_data.slots_nested"
                            },
                            "aggs": {
                                "daysaggregator": {
                                    "terms": {
                                        "field": "day",
                                        "size": 10000,
                                        "min_doc_count": 0
                                    },
                                    "aggs": {
                                        "backtolevel1": {
                                            "reverse_nested": {
                                                "path": "service_level_data"
                                            },
                                            "aggs": {
                                                "backtorootdoc": {
                                                    "reverse_nested": {}
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
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


//    return json_decode($body,true);


        $request = array(
            'url' => Config::get('app.es.url')."/fitternity_finder/finder/_search",
            'port' => Config::get('app.es.port'),
            'method' => 'POST',
            'postfields' => $body
        );

// $request = array(
//     'url' => "http://localhost:9200/"."fitternity_finder/finder/_search",
//     'port' => 9200,
//     'method' => 'POST',
//     'postfields' => $body
//     );

        $search_results     =   es_curl_request($request);

        $search_results1    =   json_decode($search_results, true);
        $search_request     =   Input::json()->all();
        $searchresulteresponse = Translator::translate_searchresultsv4($search_results1,$search_request,$keys);
        $searchresulteresponse->metadata = $this->getOfferingHeader($category,$location);
        $searchresulteresponse->metadata['total_records'] = intval($search_results1['hits']['total']);
        $searchresulteresponse->metadata['number_of_records'] = intval($size);
        $searchresulteresponse->metadata['from'] = intval($from);
        $searchresulteresponse->metadata['sortfield'] = $orderfield;
        $searchresulteresponse->metadata['sortorder'] = $order;
        $searchresulteresponse->metadata['request'] = Input::all();
        // $searchresulteresponse = $this->CustomResponse($searchresulteresponse, $keys);
        $searchresulteresponse1 = json_encode($searchresulteresponse, true);

        $response       =   json_decode($searchresulteresponse1,true);
        if($from == 0 && count($offerings) == 0 && count(Input::json()->get('facilities')) == 0 && count(Input::json()->get('budget')) == 0 && $locationCount == 0){
            $response['campaign'] = array(
                'image'=>'http://b.fitn.in/iconsv1/fitmania/sale_banner.png',
                // 'link'=>'fitternity://www.fitternity.com/search/offer_available/true',
                'link'=>'',
                'title'=>'FitStart 2017',
                'height'=>1,
                'width'=>6,
                'ratio'=>1/6
            );
        }

        return Response::json($response);

    }


 public function getOfferingHeader($category,$city){

        $categorytag_offerings = '';


        $meta_title = "Find fitness options near you in <city_name>";
        $meta_description = "Find,try and buy fitness options near you in <city_name>";
        $meta_keywords = '';
        if($category != ''){
            $findercategory     =   Findercategory::active()->where('slug', '=', url_slug(array($category)))->first(array('meta'));
            $findercategorytag     =   Findercategorytag::active()->where('slug', '=', url_slug(array($category)))->first(array('offering_header'));
            $meta_title         = $findercategory['meta']['title'];
            $meta_description   = $findercategory['meta']['description'];
            $meta_keywords      = $findercategory['meta']['keywords'];
            $categorytag_offerings    = $findercategorytag['offering_header'];
        }
        $resp  =    array(
            'meta' => array(
                'title' => str_replace("<city_name>", $city, $meta_title),
                'description' => str_replace("<city_name>", $city, $meta_description),
                'keywords' => $meta_keywords,
            ),
            'offering_header' =>$categorytag_offerings
        );
        
        //return Response::json($search_results); exit;
        return $resp;

    
}

}
