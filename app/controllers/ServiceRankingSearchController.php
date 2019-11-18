<?php
/**
 * Controller to generate rankings for finder docs
 * Created by PhpStorm.
 * User: ajay
 * Date: 25/9/15
 * Time: 11:22 AM
 */

use App\Services\Translator;
use App\Responsemodels\VipTrialResponse;

class ServiceRankingSearchController extends \BaseController {

  public function __construct() {
    parent::__construct();
    $this->elasticsearch_default_url        =   "http://".Config::get('app.es.host').":".Config::get('app.es.port').'/'.Config::get('app.es.default_index').'/';
    $this->elasticsearch_url                =   "http://".Config::get('app.es.host').":".Config::get('app.es.port').'/';
    $this->elasticsearch_host               =   Config::get('app.es.host');
    $this->elasticsearch_port               =   Config::get('app.es.port');
    $this->elasticsearch_default_index      =   Config::get('app.es.default_index');
  }

  public function searchrankedservices(){

    try {
      $from = 0;
      $size = 60;
      $sort = '';        

      $from =  (Input::json()->get('from')) ? Input::json()->get('from') : 0;
      $city = (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';
      $orderfield  = (Input::json()->get('sort')) ? Input::json()->get('sort') : '';
      $order = (Input::json()->get('order')) ? Input::json()->get('order') : 'desc';
      $category = (Input::json()->get('category')) ? Input::json()->get('category') : '';       
      $budget = (Input::json()->get('budget')) ? Input::json()->get('budget') : '';
      $limit = explode('-', $budget);    
      $budget_filter = '';
      if(sizeof($limit) > 1){
        $gte = intval($limit[0]);
        $lte = intval($limit[1]);
        $budget_filter = Input::json()->get('budget') ? '{"range" : {  "price": {"gte":'.$gte.', "lte":'.$lte.'},"_cache": true}},': '';
      }   
      $city_filter =  '{"term" : { "city" : "'.$city.'", "_cache": true }},';
      $category_filter =  Input::json()->get('category') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"],"_cache": true}},': '';
      $subcategory_filter =  Input::json()->get('subcategory') ? '{"terms" : {  "subcategory": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('subcategory'))).'"],"_cache": true}},': '';
      $location_filter = ((Input::json()->get('location'))) ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('location')).'"],"_cache": true}},'  : '';        
      $workouttags_filter = Input::json()->get('workouttags') ? '{"terms" : {  "workout_tags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('workouttags'))).'"],"_cache": true}},': '';   
      $vendor_filter = Input::json()->get('vendor') ? '{"terms" : {  "findername": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('vendor'))).'"],"_cache": true}},': '';        
      $duration_filter = Input::json()->get('duration') ? '{"terms" : {  "day_slab": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('duration'))).'"],"_cache": true}},': '';    
      $servicemarket_flag = '{"terms" : {  "sm_flagv1": ["y"],"_cache": true}},';     
      $ratecard_filtervalue = trim($duration_filter.$budget_filter,',');


      if($orderfield == 'popularity')
      {
        if($category_filter != '') {
          $factor = evalBaseCategoryScoreService($category);
          $sort = '"sort":
          {"_script" : {
            "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
            "type" : "number",
            "params" : {

              "factor" : 8

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
      $ratecard_filter ='';
      if($duration_filter !== '' || $budget_filter !==''){

        $ratecard_filter = '{
          "nested": {
            "filter": {
              "bool": {
                "must": ['.$ratecard_filtervalue.']
              }
            },
            "path": "ratecards",
            "inner_hits": {}
          }
        },';    
      }
      $service_filter = trim($city_filter.$location_filter.$category_filter.$subcategory_filter.$location_filter.$workouttags_filter.$vendor_filter.$ratecard_filter.$servicemarket_flag,',');      
      $filters = '"filter": {
        "bool" : { "must":['.$service_filter.']}}
      }';

      $budgets_facets =  '"budget" : {
        "nested": {
          "path": "ratecards"
        },
        "aggs": {
          "budget_range": {
            "terms": {
              "field": "ratecards.price_slab",
              "min_doc_count":0                                        
            },
            "aggs": {
              "ratecards_to_service": {
                "reverse_nested": {},
                "aggs": {
                  "servicesdoc": {
                    "terms": {
                      "field": "_id"
                    }
                  }
                }
              }
            }
          }
        }
      },';

      $duration_facets =  '"days" : {
        "nested": {
          "path": "ratecards"
        },
        "aggs": {
          "days_range": {
            "terms": {
              "field": "ratecards.day_slab",
              "min_doc_count":0                                        
            },
            "aggs": {
              "ratecards_to_service": {
                "reverse_nested": {},
                "aggs": {
                  "servicesdoc": {
                    "terms": {
                      "field": "_id"
                    }
                  }
                }
              }
            }
          }
        }
      },';

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
      $subcategory_facets = '"subcategory":{"terms": {"field":"subcategory","min_doc_count":1,"size":"500","order":{"_term":"asc"}}},';
      $finder_facets = '"finder":{"terms": {"field":"findername","min_doc_count":1,"size":"500","order":{"_count":"desc"}}},';
      $location_facets = '"locations": {"terms": {"field": "location","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';         
      $facetsvalue = trim($regions_facets.$location_facets.$budgets_facets.$subcategory_facets.$duration_facets.$finder_facets,',');

      $body = '{
        "from": '.$from.',
        "size": '.$size.',
        "aggs": {'.$facetsvalue.'},
        "query": {

          "filtered": {
            '.$filters.'                        
          },
          '.$sort.'
        }';              

        $request = array(
          'url' => $this->elasticsearch_host."/servicemarketplace1/service/_search",
          'port' => $this->elasticsearch_port,
          'method' => 'POST',
          'postfields' => $body
          );


        $search_results     =   es_curl_request($request);

        $response       =   [

        'search_results' => json_decode($search_results,true)];

        return Response::json($response);
      }
      catch(Exception $e){
        throw $e;        
      }
    }  

    public function getservicecategories(){
      try {

        $data = Servicecategory::where('parent_name','root')->active()->get();

        $response  = ['categories' => json_decode($data,true)];

        return Response::json($response);
      }
      catch(Exception $e){
        throw $e;      
      }
    }   

    public function getmaxminservice(){     

      try {

        $city = (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';
        $days = (Input::json()->get('days')) ? Input::json()->get('days') : 30;
        $city_filter =  '{"term" : { "city" : "'.$city.'", "_cache": true }},';
        $category_filter =  Input::json()->get('category') ? '{"terms" : {  "category": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"],"_cache": true}},': '';    
        $location_filter = ((Input::json()->get('location'))) ? '{"terms" : {  "location": ["'.str_ireplace(',', '","',Input::json()->get('location')).'"],"_cache": true}},'  : '';
        $servicemarket_flag = '{"terms" : {  "sm_flagv1": ["y"],"_cache": true}},';
        $service_filter = trim($city_filter.$location_filter.$category_filter.$servicemarket_flag,',');
        $filters = '"filter": {
          "bool" : { "must":['.$service_filter.']}}
        }';

        $min_facets = '"filteredmin" : {
          "nested" : {
            "path" : "ratecards"
          },
          "aggs" : {
            "mindays" : {
              "filter" : {
                "range" : {
                  "days" : { "lte" : '.$days.' }
                }
              },
              "aggs" : {
                "minvalue" : {
                  "min" : {
                    "field" : "price"
                  }
                }
              }
            }
          }
        },';
        $max_facets = '"filteredmax" : {
          "nested" : {
            "path" : "ratecards"
          },
          "aggs" : {
            "maxdays" : {
              "filter" : {
                "range" : {
                  "days" : { "lte": '.$days.' }
                }
              },
              "aggs" : {
                "maxvalue" : {
                  "max" : {
                    "field" : "price"
                  }
                }
              }
            }
          }
        },';

        $facetsvalue = trim($min_facets.$max_facets,',');
        $body = '{"from": 0,
        "size":0,
        "aggs": {'.$facetsvalue.'},
        "query": {

          "filtered": {
            '.$filters.'                        
          }      
        }';      

        $request = array(
          'url' => $this->elasticsearch_host."/servicemarketplace1/service/_search",
          'port' => $this->elasticsearch_port,
          'method' => 'POST',
          'postfields' => $body
          );

        $search_results     =   es_curl_request($request);

        $response       =   [

        'search_results' => json_decode($search_results,true)];

        return Response::json($response);
      }
      catch (Exception $e){
        throw $e;    
      }
    }

    public function searchviptrials(){
        return;
      try{

        $data = Input::json()->all();
        Log::info('quickbook_search',$data);
        /*****************************offset********************************************************************************************/

        $from          =         (null !== Input::json()->get('offset')['from']) ? Input::json()->get('offset')['from'] : 0;

        $size          =         (null !== Input::json()->get('offset')['number_of_records']) ? Input::json()->get('offset')['number_of_records'] : 50;


        /*****************************offset********************************************************************************************/

        /*****************************sort********************************************************************************************/

        $orderfield    =           (Input::json()->get('sort')) ? strtolower(Input::json()->get('sort')['sortfield']) : '';

        $order         =           (Input::json()->get('sort')) ? strtolower(Input::json()->get('sort')['order']) : '';

        $keys   =         (Input::json()->get('keys')) ? Input::json()->get('keys') : array();



        /*****************************sort********************************************************************************************/

        /*****************************filters*****************************************************************************************/

        $locat        =         (Input::json()->get('location'));

        $city         =         $locat['city'] ? strtolower($locat['city']): 'mumbai';
        $city         =         getmy_city($city);
        $lat          =         (isset($locat['lat'])) ? $locat['lat']  : '';
        $lon          =         (isset($locat['long'])) ? $locat['long']  : '';

        $day = (null !== Input::json()->get('day')) ? strtolower(Input::json()->get('day')) : '';

        $today = strtolower(date("l")); 

        $vip_trial_filter = '';
        
        $region_filter = (isset($locat['regions']) && !empty($locat['regions'])) ? '{"terms" : {  "location": ["'.strtolower(implode('","', $locat['regions'])).'"],"_cache": true}},' : '';

        $category_filter = ( (null !== Input::json()->get('category')) &&(!empty(Input::json()->get('category')))) ? '{"terms" : {  "category": ["'.strtolower(implode('","', Input::json()->get('category'))).'"],"_cache": true}},' : '';

        $subcategory_filter =( (null !== Input::json()->get('subcategory')) &&(!empty(Input::json()->get('subcategory')))) ?  '{"terms" : {  "subcategory": ["'.strtolower(implode('","', Input::json()->get('subcategory'))).'"],"_cache": true}},' : '';

        $workout_intensity_filter = ( (null !== Input::json()->get('workout_type')) &&(!empty(Input::json()->get('workout_type')))) ? '{"terms" : {  "session_type": ["'.strtolower(implode('","', Input::json()->get('workout_type'))).'"],"_cache": true}},' : '';

        $day_filter = ( (null !== Input::json()->get('day')) &&(!empty(Input::json()->get('day')))) ? '{"terms" : {  "workout_session_schedules_weekday": ["'.Input::json()->get('day').'"],"_cache": true}},' : '';

        $city_filter = '{"terms" : {  "city": ["'.$city.'"],"_cache": true}},';

        $vendor_filter = ( (null !== Input::json()->get('vendor')) &&(!empty(Input::json()->get('vendor')))) ? '{"terms" : {  "findername": ["'.strtolower(implode('","', Input::json()->get('vendor'))).'"],"_cache": true}},' : '';

        $vendorId_filter = ( (null !== Input::json()->get('vendor_id')) &&(!empty(Input::json()->get('vendor_id')))) ? '{"terms" : {  "finder_id": ['.Input::json()->get('vendor_id').'],"_cache": true}},' : '';

        $service_filter = '';
        if((null !== Input::json()->get('campaign_id')) &&(!empty(Input::json()->get('campaign_id')))){
          $campaign_id = Input::json()->get('campaign_id');
          $campaignServices = Campaign::where('_id',(int) $campaign_id)->pluck('campaign_services');
         // return json_encode($campaignServices);
          $service_filter = isset($campaignServices) ? '{"terms" : {  "service_id": '.json_encode($campaignServices).',"_cache": true}},' : '';
        }

        $service_type = (Input::json()->get('service_type')) ? strtolower(Input::json()->get('service_type')) : 'workout_session';

        $service_type_filter = "";
        
        
        $service_type_filter = '{"terms" : {  "service_type": ["'.$service_type.'"],"_cache": true}},';

        /***********************************Geo Range Filter*********************************/

        $vendor_id = ((null !== Input::json()->get('vendor_id')) && (!empty(Input::json()->get('vendor_id')))) ? Input::json()->get('vendor_id') : "";

        if($vendor_id != ""){ // if vendor dont search by lat lon
          $lat = "";
          $lon = "";
        }

        $geo_distance_filter = '';

        if(($lat !== '')&&($lon !== '')){

         $geo_distance_filter = ' {
            "geo_distance_range": {
                "from": "0km",
                "to": "3km",
                "geolocation": {
                    "lat": '.$lat.',
                    "lon": '.$lon.'
                }
            }
        },';

      }

      /*********************************Geo Range Filter***********************************/

      /***********************************Range filters ***********************************/

      $price = ( (null !== Input::json()->get('price')) &&(!empty(Input::json()->get('price')))) ? Input::json()->get('price') : '';

      $time = ( (null !== Input::json()->get('time')) &&(!empty(Input::json()->get('time')))) ? Input::json()->get('time') : '';

      $price_range_filter = $price_range_above_100_filter = '{
        "range": {
          "workout_session_schedules_price": {
            "gte": 100
          }
        }
      },';

      $time_range_filter = ''; 

      if($service_type != "workout_session"){
        $price_range_filter = $price_range_above_100_filter = "";
      }

      if($price !== ''){

        if($service_type == "workout_session"){

          $price_from = (isset($price['from']) && $price['from'] >= 100 ) ? $price['from'] : 100;
          $price_to = (isset($price['to']) && $price['to'] >= 100) ? $price['to'] : 1000000;

        }else{

          $price_from = (isset($price['from'])) ? $price['from'] : 0;
          $price_to = (isset($price['to'])) ? $price['to'] : 1000000;

        }

        $price_range_filter = '{
          "range": {
            "workout_session_schedules_price": {
              "gte": '.$price_from.',
              "lte": '.$price_to.'
            }
          }
        },';

      }

      if(($time !== '')||($day !== $today)){


        $time_from = isset($time['from']) ? $time['from'] : 0;
        $time_to = isset($time['to']) ? $time['to'] : 1000000;

        $time_range_filter = '{
          "range": {
            "workout_session_schedules_start_time_24_hrs": {
              "gte": '.$time_from.'              
            }
          }
        },{
          "range": {
            "workout_session_schedules_end_time_24_hrs": {
              "lte": '.$time_to.'              
            }
          }
        },';

      }
      else{

        /*****************************************handle time logic here to get workout session schedules after 2 hours from now ***************************/

        $min_time         =   intval(date("H")) > 20 ? 4 : intval(date("H")) + 2;

         $time_range_filter = '{
          "range": {
            "workout_session_schedules_start_time_24_hrs": {
              "gte": '.$min_time.'              
            }
          }
        },';

    

      }

      $mustnot_filter = "";
      $exclude_category = '{"terms" : {  "category": ["dietitians and nutritionists","healthy tiffins","marathon training","healthy snacks and beverages","sport nutrition supliment stores"]}},';
      $exclude_categorytags   = '{"terms" : {  "categorytags": ["dietitians and nutritionists","healthy tiffins","marathon training","healthy snacks and beverages","sport nutrition supliment stores"]}},';
      $exclude_commercial_type   = '{"terms" : {  "commercial_type": ["0"]}},';
      $mustnot_filter = trim($exclude_commercial_type.$exclude_category.$exclude_categorytags,',');
      

      /**********************************************************************************************/
      
      $bool_filter = trim($city_filter.$category_filter.$subcategory_filter.$workout_intensity_filter.$day_filter.$price_range_filter.$region_filter.$vip_trial_filter.$time_range_filter.$geo_distance_filter.$service_filter.$service_type_filter.$vendorId_filter, ',');

      $post_filter_query = 
      '{
        "bool": {
          "must": ['.$bool_filter.'],
          "must_not": ['.$mustnot_filter.']
        }
      }';

      /*******************************************Drilled Aggregations here********************************************/

      $time_facets_filter = trim($city_filter.$workout_intensity_filter.$subcategory_filter.$region_filter.$day_filter.$category_filter.$vip_trial_filter.$price_range_filter.$geo_distance_filter.$service_filter,',');

      $category_facets_filter = trim($city_filter.$vip_trial_filter,',');//trim($city_filter.$workout_intensity_filter.$region_filter.$day_filter.$time_range_filter.$vip_trial_filter.$price_range_filter.$geo_distance_filter.$service_filter,',');

      $location_facets_filter = trim($city_filter.$vip_trial_filter,',');//trim($city_filter.$workout_intensity_filter.$subcategory_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$price_range_filter.$geo_distance_filter.$service_filter,',');

      $location_tag_facets_filter = trim($city_filter.$workout_intensity_filter.$subcategory_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$price_range_filter.$geo_distance_filter.$service_filter,',');

      $subcategory_facets_filter = trim($city_filter.$workout_intensity_filter.$region_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$price_range_filter.$geo_distance_filter.$service_filter, ',');

      $workout_facets_filter = trim($city_filter.$subcategory_filter.$region_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$price_range_filter.$geo_distance_filter.$service_filter, ',');

      $price_facets_filter = trim($city_filter.$vip_trial_filter.$price_range_above_100_filter,',');//trim($city_filter.$workout_intensity_filter.$subcategory_filter.$region_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$geo_distance_filter.$service_filter,',');

      $vendor_facets_filter = trim($city_filter.$workout_intensity_filter.$subcategory_filter.$region_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$price_range_filter.$geo_distance_filter.$service_filter, ',');

      $vendorId_facets_filter = trim($city_filter.$workout_intensity_filter.$subcategory_filter.$region_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$price_range_filter.$geo_distance_filter.$service_filter.$vendorId_filter, ',');

      $time_bool = '"filter": {
        "bool" : { "must":['.$time_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $category_bool = '"filter": {
        "bool" : {"must":['.$category_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $location_bool = '"filter": {
        "bool" : {"must":['.$location_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $subcategory_bool = '"filter": {
        "bool" : {"must":['.$subcategory_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $workout_bool = '"filter": {
        "bool" : {"must":['.$workout_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $price_bool = '"filter": {
        "bool" : {"must":['.$price_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $vendor_bool = '"filter": {
        "bool" : {"must":['.$vendor_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $vendorId_bool = '"filter": {
        "bool" : {"must":['.$vendorId_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $region_tag_bool = '"filter": {
        "bool" : {"must":['.$location_tag_facets_filter.'],"must_not": ['.$mustnot_filter.']}
      }';

      $time_facets = '"filtered_time": {
        '.$time_bool.',
        "aggs": {
          "time_range": {
            "range": {
              "field": "workout_session_schedules_start_time_24_hrs",
              "ranges": [{
                "from": 0,
                "to": 12
              },
              {
                "from": 12,
                "to": 18
              },
              {
                "from": 18,
                "to": 24
              }]
            }
          }
        }
      },';

            $category_subcategory_facets = '
      "filtered_category_subcategory": { '.$category_bool.', 
      "aggs":
      { "category": {
        "terms": {
          "field": "category",
          "min_doc_count":1

        },"aggs": {
          "subcategory": {
            "terms": {
              "field": "subcategory",
              "min_doc_count":1,
              "size":"500",
              "order": {
                "_count": "desc"
              }

            }
          }
        }}}
      },';

            $category_facets = ' "filtered_category": {
        '.$category_bool.',
        "aggs": {
          "category": {
            "terms": {
              "field": "category",
              "min_doc_count": 1,
              "size": 500,
              "order":{"_count": "desc"}
            }
          }
        }
      },';

            $region_tag_facets = ' "filtered_region_tag": {
        '.$location_bool.',
        "aggs": {
          "locationtags": {
            "terms": {
              "field": "location",
              "min_doc_count": 1,
              "size": 500,
              "order":{"_count": "desc"}
            }
          }
        }
      },';

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
                "_count": "desc"
              }

            }
          }
        }}}
      },';

            $subcategory_facets = ' "filtered_subcategory": {
        '.$subcategory_bool.',
        "aggs": {
          "subcategory": {
            "terms": {
              "field": "subcategory",
              "min_doc_count": 1,
              "size": 500,
              "order":{"_count": "desc"}
            }
          }
        }
      },';

      $workout_facets = ' "filtered_workout": {
        '.$workout_bool.',
        "aggs": {
          "workout": {
            "terms": {
              "field": "session_type",
              "min_doc_count": 0,
              "size": 500,
              "order":{"_count": "desc"}
            }
          }
        }
      },';

      $price_min_facets = ' "filtered_price_min": {
        '.$price_bool.',
        "aggs": {
          "price_min": {
            "min": {
              "field": "workout_session_schedules_price"
            }
          }
        }
      },';

      $price_max_facets = ' "filtered_price_max": {
        '.$price_bool.',
        "aggs": {
          "price_max": {
            "max": {
              "field": "workout_session_schedules_price"
            }
          }
        }
      },';

      $vendor_facets = ' "filtered_vendor": {
        '.$vendor_bool.',
        "aggs": {
          "vendors": {
            "terms": {
              "field": "findername",
              "min_doc_count": 1,
              "size": 500,
              "order":{"_count": "desc"}
            }
          }
        }
      },';


      $vendorId_facets = ' "filtered_vendorId": {
      '.$vendorId_bool.',
      "aggs": {
          "vendors": {
            "terms": {
              "field": "vendor_id",
              "min_doc_count": 1,
              "size": 500,
              "order":{"_count": "desc"}
            }
          }
        }
      },';

      $facetsvalue = trim($time_facets.$category_subcategory_facets.$category_facets.$regions_facets.$region_tag_facets.$subcategory_facets.$workout_facets.$vendor_facets.$price_max_facets.$price_min_facets.$vendorId_facets,',');
      
      /*******************************************Drilled Aggregations here ******************************************/

      $geo_sort = "";

      if(($lat !== '')&&($lon !== '')){

        $geo_sort = ',{"_geo_distance": {
          "geolocation": { 
            "lat":  '.$lat.',
            "lon": '.$lon.'
          },
          "order":"asc",
          "unit":"km", 
          "distance_type": "plane" 
        }}';

      }

    //  $sort = 

      $sort = '"sort":[{"workout_session_schedules_start_time_24_hrs" : {"order" : "asc"}},{"rankv2":{"order":"desc"}}'.$geo_sort.']';

          //echo "<pre>";print_r($sort);exit;

      $current_hour = intval(date("G")); 

     

        if((isset($time_from)) && ($current_hour - intval($time_from) > 3) && ($day === $today)){
          $size = 0;
        }

       
       
      $query = '{
        "from" : '.$from.',
        "size" : '.$size.',
        "aggs" : {'.$facetsvalue.'},
        "post_filter" : '.$post_filter_query.' ,
        '.$sort.'
      }';



      // var_dump($query);exit();
      // return $query;

      $request = array(
        'url' => $this->elasticsearch_host."/fitternity_vip_trials/service/_search",
        'port' => $this->elasticsearch_port,
        'method' => 'POST',
        'postfields' => $query
        );

        // .strtolower(implode('","', $keylist)).
      
      $search_results     =   es_curl_request($request);

      $search_results1    =   json_decode($search_results, true);
      $searchresulteresponse = Translator::translate_vip_trials($search_results1);
      $searchresulteresponse->meta->number_of_records = intval($size);
      $searchresulteresponse->meta->from = intval($from);
      $searchresulteresponse->meta->sortfield = $orderfield;
      $searchresulteresponse->meta->sortorder = $order;
      $searchresulteresponse = $this->responseHandler($searchresulteresponse, $keys);


        $searchresulteresponse1 = json_encode($searchresulteresponse, true);

      $response       =   json_decode($searchresulteresponse1,true);

      return Response::json($response);


    }

    catch(Exception $e){

      throw $e;

    }
  }

  public function responseHandler($response, $keys) {

    if(isset($keys) && count($keys) <= 0){
      return $response;
    }

    $resultlist = $response->results->resultlist;
    $responseaggregationlist = $response->results->aggregationlist;
    $responsemeta = $response->meta;

    $Response = array();
    $ResultList = array();
    $Record = array();

    foreach ($resultlist as $res){
      $res = $res->object;
      $newObj = array();
      foreach ($keys as $key){
        isset($res->$key) ? $newObj[$key]=$res->$key : null;
      }
      $Record['object'] = $newObj;
      array_push($ResultList,$Record);
    }

    $Response['results'] = array();
    $Response['results']['resultlist'] = $ResultList;
    $Response['results']['aggregationlist'] = $responseaggregationlist;
    $Response['meta'] = $responsemeta;

    return $Response;
  }

    public function searchSaleRatecards(){

        try{

          /*****************************offset********************************************************************************************/

          $from          =         (null !== Input::json()->get('offset')['from']) ? Input::json()->get('offset')['from'] : 0;

          $size          =         (null !== Input::json()->get('offset')['number_of_records']) ? Input::json()->get('offset')['number_of_records'] : 50;


          /*****************************offset********************************************************************************************/

          /*****************************sort********************************************************************************************/

          $orderfield    =           (Input::json()->get('sort')) ? strtolower(Input::json()->get('sort')['sortfield']) : '';

          $order         =           (Input::json()->get('sort')) ? strtolower(Input::json()->get('sort')['order']) : '';


          /*****************************sort********************************************************************************************/

          /*****************************filters*****************************************************************************************/

          $locat        =         (Input::json()->get('location'));
          $city         =         $locat['city'] ? strtolower($locat['city']): 'mumbai';
          $lat          =         (isset($locat['lat'])) ? $locat['lat']  : '';
          $lon          =         (isset($locat['long'])) ? $locat['long']  : '';

          $meal_type    =         (Input::json()->get('meal_type')) ? strtolower(Input::json()->get('meal_type')) : '';
          $subcategory    =       (Input::json()->get('subcategory')) ? strtolower(Input::json()->get('subcategory')) : '';
          $validity    =       (Input::json()->get('validity')) ? Input::json()->get('validity') : '';
          $validity_type    =       (Input::json()->get('validity_type')) ? strtolower(Input::json()->get('validity_type')) : '';

          $category_filter = ( (null !== Input::json()->get('category')) &&(!empty(Input::json()->get('category')))) ? '{"terms" : {  "category": ["'.strtolower(implode('","', Input::json()->get('category'))).'"],"_cache": true}},' : '';
          $region_filter = (isset($locat['regions']) && !empty($locat['regions'])) ? '{"terms" : {  "location": ["'.strtolower(implode('","', $locat['regions'])).'"],"_cache": true}},' : '';
          $city_filter = '{"terms" : {  "city": ["'.$city.'"],"_cache": true}},';

          $meal_type_filter = ($meal_type != "") ? '{"terms" : {  "meal_type": ["'.$meal_type.'"],"_cache": true}},' : '';
          $subcategory_filter = ($subcategory != "") ? '{"terms" : {  "subcategory": ["'.$subcategory.'"],"_cache": true}},' : '';


          $validity_filter = ($validity != "") ? '{"term" : {  "validity": '.$validity.'}},' : '';
          $validity_type_filter = ($validity_type != "") ? '{"term" : {  "validity_type": "'.$validity_type.'"}},' : '';

          $bool_rate_card_filter = trim($validity_filter.$validity_type_filter, ',');

          $rate_card_filter = '{
            "nested": {
              "path": "sale_ratecards",
              "query": {
                "filtered": {
                  "filter": {
                    "bool": {
                      "must": ['.$bool_rate_card_filter.']
                    }
                  }
                }
              }
            }
          }';

          /***********************************Geo Range Filter*********************************/

          $geo_distance_filter = '';

          if(($lat !== '')&&($lon !== '')){

           $geo_distance_filter = ' {
              "geo_distance_range": {
                  "from": "0km",
                  "to": "3km",
                  "geolocation": {
                      "lat": '.$lat.',
                      "lon": '.$lon.'
                  }
              }
          },';

        }

        /*********************************Geo Range Filter***********************************/

        $bool_filter = trim($city_filter.$category_filter.$region_filter.$geo_distance_filter.$meal_type_filter.$subcategory_filter.$rate_card_filter, ',');

        $post_filter_query =
        '{
          "bool": {
            "must": ['.$bool_filter.']
          }
        }';

        /*********************************Sort Logic******************************************/

//          if($orderfield == 'popularity')
//          {
//            if($category_filter != '') {
//              $category = strtolower(implode('","', Input::json()->get('category')));
//              $factor = evalBaseCategoryScore($category);
//              $sort = '"sort":
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
//            else{
//              $sort = '"sort":[{"rankv2":{"order":"'.$order.'"}}]';
//            }
//
//          }
//          else
//          {
//            $sort = '"sort":[{"'.$orderfield.'":{"order":"'.$order.'"}}]';
//          }

          $sort = '"sort":[{"rankv2":{"order":"desc"}}]';


          /*********************************Sort Logic******************************************/

        $query = '{
          "from" : '.$from.',
          "size" : '.$size.',
          "post_filter" : '.$post_filter_query.' ,
          '.$sort.'
        }';



        $request = array(
          'url' => $this->elasticsearch_host."/fitternity_sale_ratecards/service/_search",
          'port' => $this->elasticsearch_port,
          'method' => 'POST',
          'postfields' => $query
          );


        $search_results     =   es_curl_request($request);
        $search_results1    =   json_decode($search_results, true);
        $searchresulteresponse = Translator::translate_sale_ratecards($search_results1);

        $city_array = array('mumbai'=>1,'pune'=>2,'delhi'=>4,'bangalore'=>3,'gurgaon'=>8,'noida'=>9);
        $agg_location = Location::active()->whereIn('cities',array($city_array[$city]))->orderBy('name')->get(array('name','_id','slug'));
        $agg_category = array(
                array(
                 "_id"=> 1,
                "name"=> "Yoga",
                "slug"=> "yoga"
                        ),
                array(
                 "_id"=> 2,
                "name"=> "Dance",
                "slug"=> "dance"
                        ),
                array(
                 "_id"=> 3,
                "name"=> "Martial Arts",
                "slug"=> "martial-arts"
                        ),
                array(
                 "_id"=> 4,
                "name"=> "Pilates",
                "slug"=> "pilates"
                        ),
                array(
                 "_id"=> 5,
                "name"=> "Cross Functional Training",
                "slug"=> "cross-functional-training"
                        ),
                array(
                 "_id"=> 19,
                "name"=> "Zumba",
                "slug"=> "zumba"
                        ),
                array(
                 "_id"=> 65,
                "name"=> "Gym",
                "slug"=> "gym"
                        ),
                array(
                 "_id"=> 111,
                "name"=> "Crossfit",
                "slug"=> "crossfit"
                        )
        );
        $searchresulteresponse->results->aggregationlist = array('category'=> $agg_category, 'location'=> $agg_location);

        $searchresulteresponse->meta->number_of_records = intval($size);
        $searchresulteresponse->meta->from = intval($from);
        $searchresulteresponse->meta->sortfield = $orderfield;
        $searchresulteresponse->meta->sortorder = $order;
        $searchresulteresponse1 = json_encode($searchresulteresponse, true);
        $response       =   json_decode($searchresulteresponse1,true);
        return Response::json($response);
      }

      catch(Exception $e){

        throw $e;

      }
    }
}
