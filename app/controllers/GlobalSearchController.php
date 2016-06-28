<?php
/**
 * Created by PhpStorm.
 * User: ajay
 * Date: 23/7/15
 * Time: 1:09 PM
 */

use App\Services\Translator;
use App\Responsemodels\AutocompleteResponse;
use App\Responsemodels\FinderresultResponse;
use \RedisL4;
use App\Services\Cacheapi as Cacheapi;


class GlobalSearchController extends \BaseController
{
    protected $indice = "autosuggest_index_alllocations1";
    protected $type   = "autosuggestor";
    protected $facetssize = 10000;
    protected $limit = 10;
    protected $elasticsearch_port = "";
    protected $elasticsearch_default_index = "";
    protected $elasticsearch_url = "";
    protected $elasticsearch_default_url = "";
    protected $redis;
    protected $redis_db;
    protected $cacheapi;
    protected $cache;
    //protected $servers1 = [Config::get('database.redis')];
    protected $servers;

    public function __construct()
    {
        parent::__construct();

        $this->elasticsearch_default_url = "http://" . Config::get('app.es.host') . ":" . Config::get('app.es.port') . '/' . Config::get('app.es.default_index') . '/' . Config::get('app.es.default_type') . '/';
        $this->elasticsearch_url = "http://" . Config::get('app.es.host') . ":" . Config::get('app.es.port') . '/';
        $this->elasticsearch_host = Config::get('app.es.host');
        $this->elasticsearch_port = Config::get('app.es.port');
        $redis_config = Config::get('database.redis');

        $this->servers = [
        'cluster' => $redis_config['cluster'],
        'default' => [
        'host'     => $redis_config['newredis']['host'],
        'port'     => $redis_config['newredis']['port'],
        'database' => $redis_config['newredis']['database'],
        ]
        ];
        $this->redis = RedisL4::connection('newredis');
        $this->redis_db = new Illuminate\Redis\Database($this->servers);
        $this->cache = new Illuminate\Cache\RedisStore($this->redis_db);
        //$this->$cacheapi = $Cacheapi;
    }

    public function getautosuggestresults(){

        $from    =         Input::json()->get('offset')['from'];
        $size    =         Input::json()->get('offset')['number_of_records'] ? Input::json()->get('offset')['number_of_records'] : 10;
        $string  =         Input::json()->get('keyword');
        $city    =         Input::json()->get('location')['city'] ? strtolower(Input::json()->get('location')['city']): 'mumbai';
        $lat     =         Input::json()->get('location')['lat'] ? Input::json()->get('location')['lat'] : '';
        $lon     =         Input::json()->get('location')['long'] ? Input::json()->get('location')['long'] : '';

        //  $keys    =          array_diff($keys1, array(''));
        $geo_location_filter   =   '';//($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';
        $city_filter =  '{ "term": { "city": "'.$city.'", "_cache": true } },';      
        $query_filter = trim($geo_location_filter.$city_filter,',');

        $allkeys = explode(" ", $string);
        $stopwords = array(" in "," the "," and "," of "," off "," by "," for ", " with ");
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
            "size": '.$size.',
            "fields": [
            "autosuggestvalue",
            "location",
            "identifier",
            "type",
            "slug",
            "inputcat1",
            "inputcat"        
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

$request = array(
    'url' => Config::get('app.es.url')."/autosuggest_index_alllocations/autosuggestor/_search",
    'port' => Config::get('app.es.port'),
    'method' => 'POST',
    'postfields' => $query
    );    

$search_results     =   es_curl_request($request);
$search_results1    =   json_decode($search_results, true);

$autocompleteresponse = Translator::translate_autocomplete($search_results1, $city);
$autocompleteresponse->meta->number_of_records = $size;
$autocompleteresponse->meta->from = $from;
$autocompleteresponse1 = json_encode($autocompleteresponse, true);

$response       =   json_decode($autocompleteresponse1,true);

return Response::json($response);

}

public function removeCommonWords($input){

    $commonWords = array('in','a','able','about','all','of','the','yo');
    
    return preg_replace('/\b('.implode('|',$commonWords).')\b/','',$input);
}

public function keywordSearch(){

    try {

        $from    =         Input::json()->get('from') ? Input::json()->get('from') : 0;
        $size    =         Input::json()->get('size') ? Input::json()->get('size') : 10;
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

        
        $must_filtervalue_post = trim($regions_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$geo_location_filter,',');
        

        $keylist = explode(' ', $key);
        $locations_new_filter =' {"terms" : { "locationtags_snow" : ["'.strtolower(implode('","', $keylist)).'"] } },';
        $category_new_filter =' {"terms" : { "categorytags_snow" : ["'.strtolower(implode('","', $keylist)).'"] } },';
        $title_new_filter =' {"terms" : { "title_snow" : ["'.strtolower(implode('","', $keylist)).'"] } },';
        $offering_new_filter =' {"terms" : { "offerings_snow" : ["'.strtolower(implode('","', $keylist)).'"] } },';
        $city_new_filter =' {"terms" : { "city" : ["'.strtolower(implode('","', $keylist)).'"] } },';
        $loccatshould_filter = trim($locations_new_filter.$category_new_filter.$title_new_filter.$offering_new_filter.$city_new_filter,',');

        $loccatshould = '{"bool":{"should":['.$loccatshould_filter.']}},';
        $mustfilter_post = '"must": ['.$must_filtervalue_post.']';
        
        $filtervalue_post = trim($mustfilter_post,',');
        $must_filtervalue = trim($city_filter.$loccatshould,',');
        $mustfilter = '"must": ['.$must_filtervalue.']'; 
        $filtervalue = trim($mustfilter,',');

     //$locationarray = array('andheri, juhu, bandra, ')
        
        if($mustfilter != ''){
            $filters = '"filter": {
                "bool" : {'.$filtervalue.'}
            },"_cache" : true';
        }

        if($mustfilter_post != ''){
            $filters_post = ',"post_filter": {
                "bool" : {'.$filtervalue_post.'}
            }';
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

        $location_facets = ' "filtered_locationtags": {
            '.$location_bool.',
            "aggs": {
                "locationstags": {
                    "terms": {
                        "field": "locationtags",                    
                        "min_doc_count": 1,
                        "size": 500,
                        "order":{"_term": "asc"}
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

$category_facets = '"category": {"terms": {"field": "category","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';

$facetsvalue = trim($regions_facets.$facilities_facets.$offerings_facets.$budgets_facets.$location_facets.$category_facets,',');

$stopwords = array(" in "," the "," and "," of "," off "," by "," for ");
$string = str_replace($stopwords, " ", $key);

$titleboost = 50;
if(($key === '5 fitness')||($key === '5 fitness club')){
  $titleboost = 75;
}
if(!empty($sort)){
    $sort_clause = ',"sort": [
    {
        "'.$sort.'": {
          "order": "'.$order.'"
      }
  }
  ]';
}

$keywordfunction = '';

foreach ($keylist as $keyval) {
   $newval = '{
    "filter": {
        "query": {
            "bool": {"should": [
            {"match": {
                "categorytags_snow": "'.$keyval.'"
            }}
            ]}
        }
    },
    "boost_factor": 20
},
{
    "filter": {
        "query": {
            "bool": {"should": [
            {"match": {
                "category_snow": "'.$keyval.'"
            }}
            ]}
        }
    },
    "boost_factor": 30
},
{
    "filter": {
        "query": {
            "bool": {
                "should": [
                {"match": {
                  "locationtags_snow": "'.$keyval.'"
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
                {"match": {
                  "location_snow": "'.$keyval.'"
              }}
              ]
          }
      }
  },
  "boost_factor": 20
},
{
    "filter": {
        "query": {
            "bool": {
                "should": [
                {
                    "match": {
                        "offerings_snow": "'.$keyval.'"
                    }
                }
                ]
            }
        }
    },
    "boost_factor": 15
},
{
    "filter": {
        "query": {
            "bool": {
                "should": [
                {
                    "match": {
                        "title_snow": "'.$keyval.'"
                    }
                }
                ]
            }
        }
    },
    "boost_factor": '.$titleboost.'
},
{
    "filter": {
        "query": {
            "bool": {
                "should": [
                {
                    "match": {
                        "locationcluster_snow": "'.$keyval.'"
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
                        "facilities_snow": "'.$keyval.'"
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
                        "info_service_snow": "'.$keyval.'"
                    }
                }
                ]
            }
        }
    },
    "boost_factor": 6
},';

$keywordfunction = $keywordfunction.$newval;
}

$keywordfunction = trim($keywordfunction,',');

$query = '{
    "from": '.$from.',
    "size": '.$size.',
    "aggs" :{
        '.$facetsvalue.'
    },   
    "min_score": 20,
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
                            },
                            {
                                "match": {
                                    "location_snow": "'.$string.'"
                                }
                            },
                            {
                                "match": {
                                    "category_snow": "'.$string.'"
                                }
                            }
                            ],
                            "minimum_number_should_match": 2
                        }
                    },
                    "functions": [
                    '.$keywordfunction.'
                    ],
                    "boost_mode": "max",
                    "score_mode": "sum"
                }
            },
            '.$filters.'
        }
    }'.$filters_post.$sort_clause.'
}';



$request = array(
    'url' => Config::get('app.es.url')."/fitternityv2/finder/_search",
    'port' => Config::get('app.es.port'),
    'method' => 'POST',
    'postfields' => $query
    );    

$search_results     =   es_curl_request($request);       
$search_results     =   es_curl_request($request);
$search_results1    =   json_decode($search_results, true);
$searchresulteresponse = Translator::translate_searchresultskeywordsearch($search_results1);
$searchresulteresponse->meta->number_of_records = $size;
$searchresulteresponse->meta->from = $from;
$searchresulteresponse->meta->sortfield = $sort;
$searchresulteresponse->meta->sortorder = $order;

$searchresulteresponse1 = json_encode($searchresulteresponse, true);

$response       =   json_decode($searchresulteresponse1,true);

return Response::json($response);
}
catch(Exception $e){
   throw $e;
}
}

public function newglobalsearch(){

   $from     =         Input::json()->get('offset')['from'];
   $size     =         Input::json()->get('offset')['number_of_records'] ? Input::json()->get('offset')['number_of_records'] : 10;
   $string   =         strtolower(Input::json()->get('keyword'));
   $city     =         Input::json()->get('location')['city'] ? strtolower(Input::json()->get('location')['city']): 'mumbai';
   $location =         Input::json()->get('location');
   $lat      =         isset($location['lat']) ? $location['lat'] : '';
   $lon      =         isset($location['long']) ? $location['long'] : '';
        //  $keys    =          array_diff($keys1, array(''));
        $geo_location_filter   =   '';//($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';
        $city_filter =  '{ "term": { "city": "'.$city.'", "_cache": true } },';
        $string1 = '';
        $stopwords = array(" and "," in");
        if(($city==='bangalore') && (strpos($string, 'in'))){
            if(sizeof(explode(" ", $string)) > 2){
                $stopwords = array(" and "," in ");
                $string1 = str_replace($stopwords, " ", $string);
            }                        
        }
        else{
         $string1 = str_replace($stopwords, "", $string);
     }


     $keylist   = array_filter(explode(" ", $string1));

     if(sizeof($keylist) > 1){
        if(($keylist[1] === 'i')){
            array_splice($keylist, 1);
        }
    }
    $firstwordscript = '';

    if($keylist[0] === 'i'){
        $firstwordscript = '{
            "script_score": {            
                "params": {
                    "boost": 50,
                    "param2": 20
                },
                "script": "((doc[\'type\'].value == \'vendor\') ? 100 : 0)"
            }                                           
        },';
    }
    $goldflag = false;
    $crossfitomflag = false;
    if(strpos($string1, 'gold') !== false){
        $goldflag = true;
    }
    if(strpos($string1, 'crossfito') !== false){
        $crossfitomflag = true;
    }

    $geofunction = 50;
    $geo_boost = 20;
    $inputboost = 150;
    $inputv2boost = 5;
    $inputv3boost = 50;
    $inputv4boost = 40;
    $inputloc1boost = 50;
    $inputcat1boost = 5;
    $indelimeterboost = 50;
    $withdelimeterboost = 50;
    $withofferingpriorboost = 30;
    $categorylocationscriptboost = 10;
    $geofunction = '';$indelimterscript = '';$withdelimeterscript = '';$withofferingpriorityscript = '';
    $inputfunction = '';$inputv2function='';$inputv3function=''; $inputv4function = ''; $inputcat1function = ''; $inputloc1function = '';


    $inputfilter = '{
        "query": {
            "match": {
                "input": {
                    "query": "'.$string.'",
                    "fuzziness": "2",
                    "operator": "or",
                    "prefix_length": 6,
                    "max_expansions": 100
                }
            }
        }
    },';         

    $inputv1filter = '{
        "query": {
            "match": {
                "inputv1": {
                    "query": "'.$string.'",
                    "fuzziness": "2",
                    "operator": "or",
                    "prefix_length": 6,
                    "max_expansions": 100
                }
            }
        }
    },';        

    $inputv2filter = '{
        "query": {
            "match": {
                "inputv2": {
                    "query": "'.$string.'",
                    "fuzziness": "2",
                    "operator": "or",
                    "prefix_length": 6,
                    "max_expansions": 100
                }
            }
        }
    },';        

    $inputv3filter = '{
        "query": {
            "match": {
                "inputv3": {
                    "query": "'.$string.'",
                    "fuzziness": "2",
                    "operator": "or",
                    "prefix_length": 6,
                    "max_expansions": 100
                }
            }
        }
    },';        

    $inputv4filter = '{
        "query": {
            "match": {
                "inputv2": {
                    "query": "'.$string.'",
                    "fuzziness": "2",
                    "operator": "or",
                    "prefix_length": 6,
                    "max_expansions": 100
                }
            }
        }
    },';        

    $inputloc1filter = '{
        "query": {
            "match": {
                "inputloc1": {
                    "query": "'.$string.'",
                    "fuzziness": "2",
                    "operator": "or",
                    "prefix_length": 6,
                    "max_expansions": 100
                }
            }
        }
    },';        

    $inputcat1filter = '{
        "query": {
            "match": {
                "inputcat1": {
                    "query": "'.$string.'",
                    "fuzziness": "2",
                    "operator": "or",
                    "prefix_length": 6,
                    "max_expansions": 100
                }
            }
        }
    },';


    if(!empty($lat)&&!empty($lon)){
        $geofunction = '{
          "weight": '.$geo_boost.',            
          "exp": {
            "geolocation": {
              "origin": {
                "lat":'.$lat.',
                "lon": '.$lon.'
            },
            "scale": "0.5km",
            "offset": "0km",
            "decay" : 0.5
        }
    }                          
},';
}

if (strpos($string,'in') !== false){
    $indelimterscript = '{
        "script_score": {            
            "params": {
                "boost": 50,
                "param2": 20
            },
            "script": "(doc[\'type\'].value == \'categorycity\') ? 40 :(doc[\'type\'].value == \'allfitnesslocation\') ? 31 :(doc[\'type\'].value == \'categorylocation\') ? 30 : (doc[\'type\'].value == \'categorylocationoffering\') ? 8 : (doc[\'type\'].value == \'categorylocationfacilities\') ? 6 : 0"
        }                                           
    },';
}

if ((strpos($string,'in') === false) && (strpos($string,'with') === false) && (strpos($string, '-') === false))
{    
    $indelimterscript = '{
        "script_score": {            
            "params": {
                "boost": 1,
                "param2": 20
            },
            "script": "(doc[\'type\'].value == \'categorycity\') ? 80 : (doc[\'type\'].value == \'categorylocation\') ? 60 : (doc[\'type\'].value == \'categorylocationoffering\') ? 40 : (doc[\'type\'].value == \'categorylocationfacilities\') ? 6 : 0"
        }                                           
    },';
}

if (((strpos($string, 'with') !== false)||(strpos($string, '-') !== false))&&(strpos($string, 'in') === false)){
    $withdelimeterscript = '{
        "script_score": {       
            "params": {
                "boost": 50,
                "param2": 20
            },
            "script": "((doc[\'type\'].value != \'categorylocation\') || (doc[\'type\'].value != \'vendor\') ? '.$withdelimeterboost.' : 0)"
        }                                
    },{
        "script_score": {       
            "params": {
                "boost": 50,
                "param2": 20
            },
            "script": "((doc[\'type\'].value == \'categoryoffering\') ? 200 : 0)"
        }                                
    },';

    $withofferingpriorityscript = '{
        "script_score": {       
            "params": {
                "boost": 50,
                "param2": 20
            },
            "script": "((doc[\'type\'].value != \'categorylocationfacilities\') ? '.$withofferingpriorboost.' : 0)"
        }                                 
    },';
}
                                //categoryfacility, categoryoffering, categorylocation, categorylocationoffering, categorylocationfacilities
foreach ($keylist as $key) {

    $inputfunction1 = '{
        "filter": {
            "query": {
                "match": {
                    "input": {
                        "query": "'.$key.'",
                        "fuzziness": "2",
                        "operator": "or",
                        "prefix_length": 6,
                        "max_expansions": 100
                    }
                }
            }
        },
        "boost_factor": '.$inputboost.'
    },';

    $inputfunction = $inputfunction.$inputfunction1;

    $inputv2function1 = '{
        "filter": {
            "query": {
                "match": {
                    "inputv2": {
                        "query": "'.$key.'",
                        "fuzziness": "2",
                        "operator": "or",
                        "prefix_length": 6,
                        "max_expansions": 100
                    }
                }
            }
        },
        "boost_factor": '.$inputv2boost.'
    },';

    $inputv2function = $inputv2function.$inputv2function1;

    $inputv3function1 = '{
        "filter": {
            "query": {
                "match": {
                    "inputv3": {
                        "query": "'.$key.'",
                        "fuzziness": "2",
                        "operator": "or",
                        "prefix_length": 6,
                        "max_expansions": 100
                    }
                }
            }
        },
        "boost_factor": '.$inputv3boost.'
    },';

    $inputv3function = $inputv3function.$inputv3function1;

    $inputv4function1 = '{
        "filter": {
            "query": {
                "match": {
                    "inputv4": {
                        "query": "'.$key.'",
                        "fuzziness": "2",
                        "operator": "or",
                        "prefix_length": 6,
                        "max_expansions": 100
                    }
                }
            }
        },
        "boost_factor": '.$inputv4boost.'
    },';

    $inputv4function = $inputv4function.$inputv4function1;

    $inputloc1function1 = '{
        "filter": {
            "query": {
                "match": {
                    "inputloc1": {
                        "query": "'.$key.'",
                        "fuzziness": "2",
                        "operator": "or",
                        "prefix_length": 6,
                        "max_expansions": 100
                    }
                }
            }
        },
        "boost_factor": '.$inputloc1boost.'
    },';

    $inputloc1function = $inputloc1function.$inputloc1function1;

    $inputcat1function1 = '{
        "filter": {
            "query": {
                "match": {
                    "inputcat1": {
                        "query": "'.$key.'",
                        "fuzziness": "2",
                        "operator": "or",
                        "prefix_length": 6,
                        "max_expansions": 100
                    }
                }
            }
        },
        "boost_factor": '.$inputcat1boost.'
    },';

    $inputcat1function = $inputcat1function.$inputcat1function1;

}

$vendortypescript = '{
    "script_score": {            
        "params": {
            "boost": 50,
            "param2": 20
        },
        "script": "((doc[\'type\'].value != \'vendor\') ? 0 : 0)"
    }                                           
},';

$offeringpriorityscript = '{
    "script_score": {            
        "params": {
            "boost": 50
        },
        "script": "doc[\'type\'].value == \'categorylocationoffering\' ? doc[\'offeringrank\'].value : 0"
    }                                           
},';

$allfitnessscript = '{
    "script_score": {            
        "params": {
            "terms": 50
        },
        "script": "doc[\'type\'].value == \'allfitnesslocation\' ? -70 : 0"
    }                                           
},';

$goldfunction = '';
if($goldflag){

    $goldfunction = '{
        "filter": {
            "query": {
                "match": {
                    "input": {
                        "query": "gold",
                        "fuzziness": "2",
                        "operator": "or",
                        "prefix_length": 6,
                        "max_expansions": 100
                    }
                }
            }
        },
        "boost_factor": 100
    },';
}
$crossfunction = '';
if($crossfitomflag){   
 $crossfunction = '{
    "filter": {
        "query": {
            "match": {
                "input": {
                    "query": "crossfitom",
                    "fuzziness": "0",
                    "operator": "or",
                    "prefix_length": 15,
                    "max_expansions": 100
                }
            }
        }
    },
    "boost_factor": 200
},';
}
$functionlist = trim($inputfunction.$inputv2function.$inputv3function.$inputv4function.$inputloc1function.$inputcat1function.$geofunction.$indelimterscript.$withdelimeterscript.$withofferingpriorityscript.$vendortypescript.$offeringpriorityscript.$allfitnessscript.$firstwordscript.$goldfunction.$crossfunction,',');

$filterlist = trim($inputfilter.$inputv2filter.$inputv3filter.$inputv4filter.$inputloc1filter.$inputcat1filter,',');

$functionquery =  '"query": {
    "function_score": {
        "functions": [
        '.$functionlist.'
        ],
        "boost_mode": "max",
        "score_mode": "sum"
    }
},'; 

$functionfilters =  ' "filter": {
    "bool": {
        "must": [{"bool":{"should" :['.$filterlist.']}},
        {"term":{"city":"'.$city.'"}}
        ]
    }
}'; 

$query = '{
 "fields": [
 "autosuggestvalue",
 "location",
 "identifier",
 "type",
 "slug",
 "inputcat1",
 "inputcat"
 ],
 "from": '.$from.',
 "size": '.$size.',
 "query": {
    "filtered": {
     '.$functionquery.$functionfilters.'                                
 }
}
}';

//    return $query;

$request = array(
    'url' => Config::get('app.es.url')."/fitternity_autosuggestor/autosuggestor/_search",
    'port' => Config::get('app.es.port'),
    'method' => 'POST',
    'postfields' => $query
    );    

$search_results     =   es_curl_request($request);
$search_results1    =   json_decode($search_results, true);

$autocompleteresponse = Translator::translate_autocomplete($search_results1, $city);
$autocompleteresponse->meta->number_of_records = $size;
$autocompleteresponse->meta->from = $from;
$autocompleteresponse1 = json_encode($autocompleteresponse, true);

$response       =   json_decode($autocompleteresponse1,true);

//    return $query;
return Response::json($response);

}

public function improvedkeywordSearch(){

    try {
        $city_name_list = array('mumbai','pune', 'bangalore', 'delhi', 'gurgaon');
        $from    =         Input::json()->get('from') ? Input::json()->get('from') : 0;
        $size    =         Input::json()->get('size') ? Input::json()->get('size') : 10;
        $key     =         strtolower(Input::json()->get('key'));
        $city    =         Input::json()->get('city') ? Input::json()->get('city') : 'mumbai';
        $lat     =         Input::json()->get('lat') ? Input::json()->get('lat') : '';
        $lon     =         Input::json()->get('lon') ? Input::json()->get('lon') : '';
        $sort    =         Input::json()->get('sort') ? Input::json()->get('sort') : '';
        $order   =         Input::json()->get('order') ? Input::json()->get('order') : '';
        $location =          Input::json()->get('regions') ? Input::json()->get('regions') : array();
        $category =         Input::json()->get('category') ? Input::json()->get('category') : array();

        $sort_clause = '';

        /*

        All static filters and static clause, 

        */

        $geo_location_filter   =   ($lat != '' && $lon != '') ? '{"geo_distance" : {  "distance": "10km","distance_type":"plane", "geolocation":{ "lat":'.$lat. ',"lon":' .$lon. '}}},':'';
        $city_filter = '{"term" : { "city" : "'.$city.'" } },';
        $regions_filter = '';
        $category_filter = '';
        $keylist = array();

        /*

        All filters from request

        */  


         $string = $this->_removeStopwords($key);
         
          $keylist = explode(' ', $string);
        $cityBool = false;
        if(sizeof($category) > 1){
           $keylist = array_unique(array_merge($keylist, $category));
        }
        if((sizeof($location) === 1)&&(array_search($location[0], $city_name_list) !== false)){
            $cityBool = true;
        }

        $redis_query_flag = ((sizeof($location) > 0)||(sizeof($category) > 0));    

        if(!$cityBool){
           if(sizeof($category) > 0){
            $category_filter = '{"terms" : {  "categorytags": ["'.strtolower(implode('","', $category)).'"],"_cache": true}},';
        }

        if(sizeof($location) > 0){
         $regions_filter = '{"terms" : {  "locationtags": ["'.strtolower(implode('","', $location)).'"],"_cache": true}},';
     }
    
    

     if(!$redis_query_flag){
        $cat_list = array();
        $loc_list = array();
        /*
        
        Query Redis Database here 

        */

        $redis_response = ($this->_queryRedis($keylist));
         // return  ($keylist);exit;

        /*
        Build constant filters if response from the redis return results
        */

        foreach ($redis_response as $key => $tag_found) {

            switch($tag_found[1]){
                case 'category':
                array_push($cat_list, $tag_found[0]);
                array_push($category, $tag_found[0]);
                break;
                case 'location':
                array_push($loc_list, $tag_found[0]);
                array_push($location, $tag_found[0]);
                break;
            }
        }   

        if(sizeof($cat_list) > 0){
            $category_filter = '{"terms" : {  "categorytags": ["'.strtolower(implode('","', $cat_list)).'"],"_cache": true}},';
        }
        if(sizeof($loc_list) > 0){
            $regions_filter = '{"terms" : {  "locationtags": ["'.strtolower(implode('","', $loc_list)).'"],"_cache": true}},';
        }
    }

}




$regez = $this->_getCategoryRegex($city);

$budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.strtolower(implode('","', Input::json()->get('budget'))).'"],"_cache": true}},': '';         
$offerings_filter = ((Input::json()->get('offerings'))) ? '{"terms" : {  "offerings": ["'.strtolower(implode('","', Input::json()->get('offerings'))).'"],"_cache": true}},'  : '';
$facilities_filter = ((Input::json()->get('facilities'))) ? '{"terms" : {  "facilities": ["'.strtolower(implode('","', Input::json()->get('facilities'))).'"],"_cache": true}},'  : '';

$must_filtervalue_post ='';
if($cityBool){
    $regions_filter = '';
}


$must_filtervalue_post = trim($regions_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter.$geo_location_filter.$city_filter,',');    


$locations_new_filter =' {"terms" : { "locationtags_snow" : ["'.strtolower(implode('","', $keylist)).'"] } },';
$category_new_filter =' {"terms" : { "categorytags_snow" : ["'.strtolower(implode('","', $keylist)).'"] } },';
$title_new_filter =' {"terms" : { "title_snow" : ["'.strtolower(implode('","', $keylist)).'"] } },';
$offering_new_filter =' {"terms" : { "offerings_snow" : ["'.strtolower(implode('","', $keylist)).'"] } },';
$city_new_filter =' {"terms" : { "city" : ["'.strtolower(implode('","', $keylist)).'"] } },';
if($cityBool){
    $locations_new_filter = '';
}
$loccatshould_filter = trim($locations_new_filter.$category_new_filter.$title_new_filter.$offering_new_filter.$city_new_filter,',');

$loccatshould = '{"bool":{"should":['.$loccatshould_filter.']}},';
$mustfilter_post = '"must": ['.$must_filtervalue_post.']';
$filtervalue_post = trim($mustfilter_post,',');
$must_filtervalue = trim($city_filter.$loccatshould,',');
$mustfilter = '"must": ['.$must_filtervalue.']'; 
$filtervalue = trim($mustfilter,',');

if($mustfilter != ''){
    $filters = '"filter": {
        "bool" : {'.$filtervalue.'}
    },"_cache" : true';
}

if($mustfilter_post != ''){
    $filters_post = ',"post_filter": {
        "bool" : {'.$filtervalue_post.'}
    }';
}

$location_facets_filter = trim($city_filter.$geo_location_filter.$category_filter,',');
$facilities_facets_filter = trim($city_filter.$regions_filter.$geo_location_filter.$category_filter, ',');
$offerings_facets_filter = trim($city_filter.$regions_filter.$facilities_filter.$geo_location_filter.$category_filter, ',');
$budgets_facets_filter = trim($city_filter.$regions_filter.$facilities_filter.$offerings_filter.$geo_location_filter.$category_filter, ',');

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

$location_facets = ' "filtered_locationtags": {
    '.$location_bool.',
    "aggs": {
        "locationstags": {
            "terms": {
                "field": "locationtags",                    
                "min_doc_count": 1,
                "size": 500,
                "order":{"_term": "asc"}
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

$category_facets = '"category": {"terms": {"field": "categorytags","include":"'.$regez.'","min_doc_count":1,"size":"500","order": {"_term": "asc"}}},';

$facetsvalue = trim($regions_facets.$facilities_facets.$offerings_facets.$budgets_facets.$location_facets.$category_facets,',');

$titleboost = 50;
if(($key === '5 fitness')||($key === '5 fitness club')){
  $titleboost = 75;
}
if(!empty($sort)){
    $sort_clause = ',"sort": [
    {
        "'.$sort.'": {
          "order": "'.$order.'"
      }
  }
  ]';
}

$keywordfunction = '';

foreach ($keylist as $keyval) {
   $newval = '{
    "filter": {
        "query": {
            "bool": {"should": [
            {"match": {
                "categorytags_snow": "'.$keyval.'"
            }}
            ]}
        }
    },
    "boost_factor": 20
},
{
    "filter": {
        "query": {
            "bool": {"should": [
            {"match": {
                "category_snow": "'.$keyval.'"
            }}
            ]}
        }
    },
    "boost_factor": 30
},
{
    "filter": {
        "query": {
            "bool": {
                "should": [
                {"match": {
                  "locationtags_snow": "'.$keyval.'"
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
                {"match": {
                  "location_snow": "'.$keyval.'"
              }}
              ]
          }
      }
  },
  "boost_factor": 20
},
{
    "filter": {
        "query": {
            "bool": {
                "should": [
                {
                    "match": {
                        "offerings_snow": "'.$keyval.'"
                    }
                }
                ]
            }
        }
    },
    "boost_factor": 15
},
{
    "filter": {
        "query": {
            "bool": {
                "should": [
                {
                    "match": {
                        "title_snow": "'.$keyval.'"
                    }
                }
                ]
            }
        }
    },
    "boost_factor": '.$titleboost.'
},
{
    "filter": {
        "query": {
            "bool": {
                "should": [
                {
                    "match": {
                        "locationcluster_snow": "'.$keyval.'"
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
                        "facilities_snow": "'.$keyval.'"
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
                        "info_service_snow": "'.$keyval.'"
                    }
                }
                ]
            }
        }
    },
    "boost_factor": 6
},';

$keywordfunction = $keywordfunction.$newval;
}

$keywordfunction = trim($keywordfunction,',');

$query = '{
    "from": '.$from.',
    "size": '.$size.',
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
                            },
                            {
                                "match": {
                                    "location_snow": "'.$string.'"
                                }
                            },
                            {
                                "match": {
                                    "category_snow": "'.$string.'"
                                }
                            }
                            ],
                            "minimum_number_should_match": 2
                        }
                    },
                    "functions": [
                    '.$keywordfunction.'
                    ],
                    "boost_mode": "max",
                    "score_mode": "sum"
                }
            },
            '.$filters.'
        }
    }'.$filters_post.$sort_clause.'
}';

// return $query;
$request = array(
    'url' => Config::get('app.es.url')."/fitternityv2/finder/_search",
    'port' => Config::get('app.es.port'),
    'method' => 'POST',
    'postfields' => $query
    );

$search_results     =   es_curl_request($request);       
$search_results     =   es_curl_request($request);
$search_results1    =   json_decode($search_results, true);
$searchresulteresponse = Translator::translate_searchresultskeywordsearch($search_results1);
$searchresulteresponse->meta->number_of_records = $size;
$searchresulteresponse->meta->from = $from;
$searchresulteresponse->meta->sortfield = $sort;
$searchresulteresponse->meta->sortorder = $order;
$searchresulteresponse->meta->categoryfilters = $category;
$searchresulteresponse->meta->locationfilters = $location;

$searchresulteresponse1 = json_encode($searchresulteresponse, true);

$response       =   json_decode($searchresulteresponse1,true);

return Response::json($response);
}
catch(Exception $e){
   throw $e;
}
}

public function preparekeywordsearchcache(){

    $category_list = Findercategorytag::active()
    ->with('cities')    
    ->where('status','1')
    ->whereNotIn('_id', array(22))
    ->get();


    $location_list = Location::where('status', '1')->get();

    /*
    Fill redis cache with static data
    */

    foreach ($category_list as $key => $category) {
        $this->redis_db->set(strtolower($category['name']), serialize(array(strtolower($category['name']), 'category')));       
    }

    foreach ($location_list as $key => $location) {
        $this->redis_db->set(strtolower($location['name']), serialize(array(strtolower($location['name']), 'location')));
    }
}

/*

Private function to query redis database

*/

private function _queryRedis($key_list = array()){
   $return_value = array();   
   foreach ($key_list as $k => $v) {
    $res = $this->redis_db->command('keys', array($v.'*'));
    if(sizeof($res) > 0){
        foreach ($res as $key => $r) {
            $value = $this->redis_db->get($r);
            $val = unserialize($value);
            array_push($return_value, $val);
        }
    }
}

return $return_value;
}

private function _removeStopwords($string){
 $stopwords = array(" in "," the "," and "," of "," off "," by "," for ");
 $resp = str_replace($stopwords, " ", $string);
 return $resp;
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
        
        default:
            # code...
        break;
    }

    return $regex;

}

}