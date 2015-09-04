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
        $string     =         Input::json()->get('key');
        $city    =         Input::json()->get('city') ? Input::json()->get('city') : 'mumbai';
        
        //  $keys    =          array_diff($keys1, array(''));
        
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
                                            "inputloc1",
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
                                            "inputloc1",
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
                                            "inputloc1",
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
                                    "virgininput",
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
                                                            {
                                                                "query_string": {
                                                                    "fields": [
                                                                        "inputloc1",
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
                                                            {
                                                                "term": {
                                                                    "city": "'.$city.'",
                                                                    "_cache": true
                                                                }
                                                            }
                                                        ]
                                                    }
                                                },
                                                "functions": [
                                                    {
                                                        "filter": {
                                                            "query": {
                                                                "bool": {
                                                                    "should": [     

                                                                        {
                                                                            "query_string": {
                                                                                "fields": [
                                                                                    "inputloc1",
                                                                                    "inputloc2"
                                                                                ],
                                                                                "query": "*'.$keys[0].'*",
                                                                                "fuzziness": 0,
                                                                                "fuzzy_prefix_length": 0
                                                                            }
                                                                        }'.$key2_loc_query.$key3_loc_query.$key4_loc_query.'
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
                                                        "boost_factor": 8
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

}