<?php
/**
 * Created by PhpStorm.
 * User: ajay
 * Date: 23/7/15
 * Time: 1:09 PM
 */
class GlobalSearchController extends \BaseController
{
    protected $indice = "autosuggest_index_alllocations";
    protected $type   = "autosuggestor";
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
    }

    public function getautosuggestresults(){

        $from    =         Input::json()->get('from') ? Input::json()->get('from') : 0;
        $string  =         Input::json()->get('key');
        $city    =         Input::json()->get('city') ? Input::json()->get('city') : 'mumbai';
        $lat     =         Input::json()->get('lat') ? Input::json()->get('lat') : '';
        $lon     =         Input::json()->get('lon') ? Input::json()->get('lon') : '';
        //  $keys    =          array_diff($keys1, array(''));
        $geo_location_filter   =   '';//($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';
        $city_filter =  '{ "term": { "city": "'.$city.'", "_cache": true } },';

        $query_filter = trim($geo_location_filter.$city_filter,',');

        $allkeys = explode(" ", $string);
        $stopwords = array(" in "," the "," and "," of "," off "," by "," for ");
        $key1 = str_replace($stopwords, " ", $string);
        $keys   =         explode(" ", $key1); 
        $key2_string_query  = '';
        $key2_fuzzy_query = '';
        $key2_loc_query = '';
        $key2_cat_query = '';
        $key3_loc_query = '';
        $key3_cat_query = ''; 
        $key4_loc_query = ''; 
        $key2_input_query = '';
        $key3_input_query = '';
        
        if(count($keys) > 1)
        {
            $key2_string_query  =    '{
                "query_string": {
                    "fields": [
                    "inputv2",
                    "inputv3",
                    "inputv4"
                    ],
                    "query": "'.$keys[1].'*",
                    "fuzziness": 0,
                    "fuzzy_prefix_length": 0,
                    "boost": 3
                }
            },';

            $key2_fuzzy_query   = ',{
                "fuzzy": {
                    "input": {
                        "value": "'.$keys[1].'",
                        "fuzziness": 1,
                        "prefix_length": 3,
                        "boost": 4
                    }
                }
            }';

            $key2_input_query  =    ',{
                "query_string": {
                    "fields": [
                    "input"
                    ],
                    "query": "'.$keys[1].'*",
                    "fuzziness": 0,
                    "fuzzy_prefix_length": 0                                           
                }
            }';

            $key2_loc_query    =  ',{
                "query_string":{
                    "fields": [                                           
                    "inputloc2"
                    ],
                    "query": "*'.$keys[1].'*",
                    "fuzziness": 0,
                    "fuzzy_prefix_length": 0                                        
                }
            }';

            $key2_cat_query    =  '{
                "query_string":{
                    "fields": [
                    "inputcat"
                    ],
                    "query": "'.$keys[1].'*",
                    "fuzziness": 0,
                    "fuzzy_prefix_length": 0,
                    "boost": 3
                }
            },';
            if(count($keys) > 2)
            {                
                $key3_loc_query    =  ',{
                    "query_string":{
                        "fields": [
                        
                        "inputloc2"
                        ],
                        "query": "*'.$keys[2].'*",
                        "fuzziness": 0,
                        "fuzzy_prefix_length": 0                                      
                    }
                }';

                $key3_cat_query    =  '{
                    "query_string":{
                        "fields": [
                        "inputcat"
                        ],
                        "query": "'.$keys[2].'*",
                        "fuzziness": 0,
                        "fuzzy_prefix_length": 0,
                        "boost": 3
                    }
                },';

                $key3_input_query  =    ',{
                    "query_string": {
                        "fields": [
                        "input"
                        ],
                        "query": "'.$keys[2].'*",
                        "fuzziness": 0,
                        "fuzzy_prefix_length": 0,
                        "boost": 3
                    }
                }';
            }
            if(count($keys) > 3)
            {
                $key4_loc_query    =  ',{
                    "query_string":{
                        "fields": [                                            
                        "inputloc2"
                        ],
                        "query": "*'.$keys[3].'*",
                        "fuzziness": 0,
                        "fuzzy_prefix_length": 0                                      
                    }
                }';
            }
        };
        
        $query          = '{
            "from": '.$from.',
            "size": 10,
            "fields": [
            "input",
            "location",
            "identifier",
            "slug"
            ],
            "query": {
                "filtered": {
                    "query": {
                        "function_score": {
                            "query": {
                                "bool": {
                                    "should": [
                                    {"match": {
                                      "inputloc1": "'.$string.'"
                                      
                                  }},
                                  {
                                    "query_string": {
                                        "fields": [
                                        "inputloc2"                                                                   
                                        ],
                                        "query": "*'.$keys[0].'*",
                                        "fuzziness": 0,
                                        "fuzzy_prefix_length": 0,
                                        "boost": 2
                                    }
                                }
                                '.$key2_loc_query.$key3_loc_query.$key4_loc_query.',{
                                    "query_string": {
                                        "fields": [
                                        "inputcat"
                                        ],
                                        "query": "'.$keys[0].'*",
                                        "fuzziness": 0,
                                        "fuzzy_prefix_length": 0,
                                        "boost": 2
                                    }
                                },
                                '.$key2_cat_query.$key3_cat_query.'{
                                    "query_string": {
                                        "fields": [
                                        "inputv2",
                                        "inputv3",
                                        "inputv4"
                                        ],
                                        "query": "'.$keys[0].'*",
                                        "fuzziness": 0,
                                        "fuzzy_prefix_length": 0,
                                        "boost": 6
                                    }
                                },
                                '.$key2_string_query.'{
                                    "fuzzy": {
                                        "input": {
                                            "value": "'.$keys[0].'",
                                            "fuzziness": 1,
                                            "prefix_length": 2,
                                            "boost": 20
                                        }
                                    }
                                }'.$key2_fuzzy_query.'
                                ]
                            }
                        },
                        "filter": {
                            "bool": {
                                "must": [
                                    '.$query_filter.'
                                ]
                            }
                        },
                        "functions": [
                        {
                            "filter": {
                                "query": {
                                    "bool": {
                                        "should": [
                                        {"match": {
                                          "inputloc1": "'.$string.'"
                                      }} 
                                      
                                      ]
                                  }
                              }
                          },
                          "boost_factor": 11
                      },
                      {
                       "filter": {
                        "query": {
                            "bool": {
                                "should": [     

                                {
                                    "query_string": {
                                        "fields": [
                                        "input"                                                                                    
                                        ],
                                        "query": "'.$keys[0].'*",
                                        "fuzziness": 0,
                                        "fuzzy_prefix_length": 0
                                    }
                                }'.$key2_input_query.$key3_input_query.'
                                ]
                            }
                        }
                    },
                    "boost_factor": 6
                }
                ],
                "boost_mode": "sum"
            }
        }
    }
}
}';

            //$this->elasticsearch_host.$this->elasticsearch_port.  
$request = array(
    'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."autosuggest_index_alllocations/autosuggestor/_search",
    'port' => 8050,
    'method' => 'POST',
    'postfields' => $query
    );    

$search_results     =   es_curl_request($request);
        //return $query;
$response       =   [
'search_results' => json_decode($search_results,true)];

return Response::json($response);

}

public function removeCommonWords($input){

    $commonWords = array('in','a','able','about','all','of','the','yo');
    
    return preg_replace('/\b('.implode('|',$commonWords).')\b/','',$input);
}

public function keywordSearch(){

    try {

        $from    =         Input::json()->get('from') ? Input::json()->get('from') : 0;
        $key     =         Input::json()->get('key');
        $city    =         Input::json()->get('city') ? Input::json()->get('city') : 'mumbai';
        $lat     =         Input::json()->get('lat') ? Input::json()->get('lat') : '';
        $lon     =         Input::json()->get('lon') ? Input::json()->get('lon') : '';
        $sort    =         Input::json()->get('sort') ? Input::json()->get('sort') : '';
        $order   =         Input::json()->get('order') ? Input::json()->get('order') : '';

        $sort_clause = '';

        $geo_location_filter   =   ($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';
        $city_filter = '{"term" : { "city" : "'.$city.'" } },';
        $category_filter =  Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"],"_cache": true}},': '';
        $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('budget'))).'"],"_cache": true}},': '';        
        $regions_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"],"_cache": true}},'  : '';   
        $offerings_filter = ((Input::json()->get('offerings'))) ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"],"_cache": true}},'  : '';
        $facilities_filter = ((Input::json()->get('facilities'))) ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"],"_cache": true}},'  : '';

        $must_filtervalue = trim($city_filter.$regions_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$geo_location_filter,',');

    $mustfilter = '"must": ['.$must_filtervalue.']';        //used for offering and facilities

    $filtervalue = trim($mustfilter,',');

    if($mustfilter != ''){
        $filters = '"filter": {
            "bool" : {'.$filtervalue.'}
        },"_cache" : true';
    }

    $location_facets_filter = trim($geo_location_filter.$category_filter,',');
    $facilities_facets_filter = trim($regions_filter.$geo_location_filter.$category_filter, ',');
    $offerings_facets_filter = trim($regions_filter.$facilities_filter.$geo_location_filter.$category_filter, ',');
    $budgets_facets_filter = trim($regions_filter.$facilities_filter.$offerings_filter.$geo_location_filter.$category_filter, ',');

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



$facetsvalue = trim($regions_facets.$facilities_facets.$offerings_facets.$budgets_facets,',');

$stopwords = array(" in "," the "," and "," of "," off "," by "," for ");
$string = str_replace($stopwords, " ", $key);

if(!empty($sort)){
    $sort_clause = ',"sort": [
      {
        "'.$sort.'": {
          "order": "'.$order.'"
        }
      }
    ]';
}

$query = '{
    "from": '.$from.',
    "size":10,
    "aggs" :{
        '.$facetsvalue.'
    },
    "query": {
        "filtered": {
            "query": {
                "function_score": {
                    "query": {
                        "bool": {
                            "should": [
                            {
                                "match": {
                                    "categorytags_snow": "'.$string.'"
                                }
                            },
                            {
                                "match": {
                                    "locationtags_snow": "'.$string.'"
                                }
                            },
                            {
                                "match": {
                                    "offerings_snow": "'.$string.'"
                                }
                            },
                            {
                                "match": {
                                    "title_snow": "'.$string.'"
                                }
                            },
                            {
                                "match": {
                                    "locationcluster_snow": "'.$string.'"
                                }
                            },
                            {
                                "match": {
                                    "facilities_snow": "'.$string.'"
                                }
                            },
                            {
                                "match": {
                                    "info_service_snow": "'.$string.'"
                                }
                            }
                            ]
                        }
                    },
                    "functions": [
                    {
                        "filter": {
                            "query": {
                                "bool": {"should": [
                                {"match": {
                                    "categorytags_snow": "'.$string.'"
                                }}
                                ]}
                            }
                        },
                        "boost_factor": 8
                    },
                    {
                        "filter": {
                            "query": {
                                "bool": {
                                    "should": [
                                    {"match": {
                                      "locationtags_snow": "'.$string.'"
                                  }}
                                  ]
                              }
                          }
                      },
                      "boost_factor": 10
                  },
                  {
                    "filter": {
                        "query": {
                            "bool": {
                                "should": [
                                {
                                    "match": {
                                        "offerings_snow": "'.$string.'"
                                    }
                                }
                                ]
                            }
                        }
                    },
                    "boost_factor": 4
                },
                {
                    "filter": {
                        "query": {
                            "bool": {
                                "should": [
                                {
                                    "match": {
                                        "title_snow": "'.$string.'"
                                    }
                                }
                                ]
                            }
                        }
                    },
                    "boost_factor": 12
                },
                {
                    "filter": {
                        "query": {
                            "bool": {
                                "should": [
                                {
                                    "match": {
                                        "locationcluster_snow": "'.$string.'"
                                    }
                                }
                                ]
                            }
                        }
                    },
                    "boost_factor": 2
                },
                {
                    "filter": {
                        "query": {
                            "bool": {
                                "should": [
                                {
                                    "match": {
                                        "facilities_snow": "'.$string.'"
                                    }
                                }
                                ]
                            }
                        }
                    },
                    "boost_factor": 2
                },
                {
                    "filter": {
                        "query": {
                            "bool": {
                                "should": [
                                {
                                    "match": {
                                        "info_service_snow": "'.$string.'"
                                    }
                                }
                                ]
                            }
                        }
                    },
                    "boost_factor": 6
                }
                ],
                "boost_mode": "max",
                "score_mode": "sum"
            }
        },
        '.$filters.'
    }
}'.$sort_clause.'
}';


$request = array(
    'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity/finder/_search",
    'port' => 8050,
    'method' => 'POST',
    'postfields' => $query
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
}