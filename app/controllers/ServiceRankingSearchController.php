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
    $this->elasticsearch_default_url        =   "http://".Config::get('app.elasticsearch_host_new').":".Config::get('app.elasticsearch_port_new').'/'.Config::get('app.elasticsearch_default_index').'/';
    $this->elasticsearch_url                =   "http://".Config::get('app.elasticsearch_host_new').":".Config::get('app.elasticsearch_port_new').'/';
    $this->elasticsearch_host               =   Config::get('app.elasticsearch_host_new');
    $this->elasticsearch_port               =   Config::get('app.elasticsearch_port_new');
    $this->elasticsearch_default_index      =   Config::get('app.elasticsearch_default_index');        
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

      try{

        /*****************************offset********************************************************************************************/

        $from          =         (null !== Input::json()->get('offset')['from']) ? Input::json()->get('offset')['from'] : 0;

        $size          =         (null !== Input::json()->get('offset')['number_of_records']) ? Input::json()->get('offset')['number_of_records'] : 10;


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

        $vip_trial_filter = '{"term" : {  "vip_trial_flag": 1,"_cache": true}},';
        
        $region_filter = (isset($locat['regions']) && !empty($locat['regions'])) ? '{"terms" : {  "location": ["'.strtolower(implode('","', $locat['regions'])).'"],"_cache": true}},' : '';

        $category_filter = ( (null !== Input::json()->get('category')) &&(!empty(Input::json()->get('category')))) ? '{"terms" : {  "category": ["'.strtolower(implode('","', Input::json()->get('category'))).'"],"_cache": true}},' : '';

        $subcategory_filter =( (null !== Input::json()->get('subcategory')) &&(!empty(Input::json()->get('subcategory')))) ?  '{"terms" : {  "subcategory": ["'.strtolower(implode('","', Input::json()->get('subcategory'))).'"],"_cache": true}},' : '';

        $workout_intensity_filter = ( (null !== Input::json()->get('workout_type')) &&(!empty(Input::json()->get('workout_type')))) ? '{"terms" : {  "session_type": ["'.strtolower(implode('","', Input::json()->get('workout_type'))).'"],"_cache": true}},' : '';

        $day_filter = ( (null !== Input::json()->get('day')) &&(!empty(Input::json()->get('day')))) ? '{"terms" : {  "workout_session_schedules_weekday": ["'.Input::json()->get('day').'"],"_cache": true}},' : '';

        $city_filter = '{"terms" : {  "city": ["'.$city.'"],"_cache": true}},';

        $vendor_filter = ( (null !== Input::json()->get('vendor')) &&(!empty(Input::json()->get('vendor')))) ? '{"terms" : {  "findername": ["'.strtolower(implode('","', Input::json()->get('vendor'))).'"],"_cache": true}},' : '';


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

      /***********************************Range filters ***********************************/

      $price = ( (null !== Input::json()->get('price')) &&(!empty(Input::json()->get('price')))) ? Input::json()->get('price') : '';

      $time = ( (null !== Input::json()->get('time')) &&(!empty(Input::json()->get('time')))) ? Input::json()->get('time') : '';

      $price_range_filter = '';
      $time_range_filter = '';        

      if($price !== ''){

        $price_from = isset($price['from']) ? $price['from'] : 0;
        $price_to = isset($price['to']) ? $price['to'] : 1000000;

        $price_range_filter = '{
          "range": {
            "workout_session_schedules_price": {
              "gte": '.$price_from.',
              "lte": '.$price_to.'
            }
          }
        },';

      }

      if($time !== ''){

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

      /**********************************************************************************************/
      
      $bool_filter = trim($city_filter.$category_filter.$subcategory_filter.$workout_intensity_filter.$day_filter.$price_range_filter.$region_filter.$vip_trial_filter.$time_range_filter.$geo_distance_filter, ',');

      $post_filter_query = 
      '{
        "bool": {
          "must": ['.$bool_filter.']
        }
      }';

      /*******************************************Drilled Aggregations here********************************************/

      $time_facets_filter = trim($city_filter.$day_filter.$vip_trial_filter.$geo_distance_filter,',');

      $category_facets_filter = trim($city_filter.$time_range_filter.$day_filter.$vip_trial_filter.$geo_distance_filter,',');

      $location_facets_filter = trim($city_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$geo_distance_filter,',');

      $location_tag_facets_filter = trim($city_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$geo_distance_filter,',');

      $subcategory_facets_filter = trim($city_filter.$region_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$geo_distance_filter, ',');

      $workout_facets_filter = trim($city_filter.$subcategory_filter.$region_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$geo_distance_filter, ',');

      $price_facets_filter = trim($city_filter.$category_filter.$region_filter.$subcategory_filter.$day_filter.$time_range_filter.$workout_intensity_filter.$vip_trial_filter.$geo_distance_filter,',');

      $vendor_facets_filter = trim($city_filter.$workout_intensity_filter.$subcategory_filter.$region_filter.$day_filter.$time_range_filter.$category_filter.$vip_trial_filter.$geo_distance_filter, ',');



      $time_bool = '"filter": {
        "bool" : { "must":['.$time_facets_filter.']}
      }';

      $category_bool = '"filter": {
        "bool" : {"must":['.$category_facets_filter.']}
      }';

      $location_bool = '"filter": {
        "bool" : {"must":['.$location_facets_filter.']}
      }';

      $subcategory_bool = '"filter": {
        "bool" : {"must":['.$subcategory_facets_filter.']}
      }';

      $workout_bool = '"filter": {
        "bool" : {"must":['.$workout_facets_filter.']}
      }';

      $price_bool = '"filter": {
        "bool" : {"must":['.$price_facets_filter.']}
      }';

      $vendor_bool = '"filter": {
        "bool" : {"must":['.$vendor_facets_filter.']}
      }';

      $region_tag_bool = '"filter": {
        "bool" : {"must":['.$location_tag_facets_filter.']}
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

      $facetsvalue = trim($time_facets.$category_facets.$regions_facets.$region_tag_facets.$subcategory_facets.$workout_facets.$vendor_facets.$price_max_facets.$price_min_facets,',');

      
      /*******************************************Drilled Aggregations here ******************************************/

      $sort = '"sort":[{"rankv2":{"order":"desc"}}]';

      $query = '{
        "from" : '.$from.',
        "size" : '.$size.',
        "aggs" : {'.$facetsvalue.'},
        "post_filter" : '.$post_filter_query.' ,
        '.$sort.'
      }';

    

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

      $searchresulteresponse1 = json_encode($searchresulteresponse, true);

      $response       =   json_decode($searchresulteresponse1,true);

      return Response::json($response);


    }

    catch(Exception $e){

      throw $e;

    }
  }
}
