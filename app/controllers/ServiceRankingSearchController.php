<?php
/**
 * Controller to generate rankings for finder docs
 * Created by PhpStorm.
 * User: ajay
 * Date: 25/9/15
 * Time: 11:22 AM
 */

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
  }
