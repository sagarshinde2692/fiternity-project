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
        $key     =         Input::json()->get('key');
        $city    =         Input::json()->get('city') ? Input::json()->get('city') : 'mumbai';

        $keys    =         explode(" ", $key);

        $key2_string_query  = '';
        $key2_fuzzy_query = '';
    
        if(count($keys) > 1)
        {
            $key2_string_query  =    '{
                                        "query_string": {
                                            "fields": [
                                                "inputv2",
                                                "inputv3",
                                                "inputv4"
                                            ],
                                            "query": "*'.$keys[1].'*",
                                            "fuzziness": 0,
                                            "fuzzy_prefix_length": 0,
                                            "boost": 1
                                        }
                                    },';

            $key2_fuzzy_query   = ',{
                            "fuzzy": {
                                "input": {
                                    "value": "'.$keys[1].'",
                                    "fuzziness": 1,
                                    "prefix_length": 3,
                                    "boost": 1
                                }
                            }
                        }';

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
                                        "bool": {
                                            "should": [
                                                {
                                                    "query_string": {
                                                        "fields": [
                                                            "inputv2",
                                                            "inputv3",
                                                            "inputv4"
                                                        ],
                                                        "query": "*'.$keys[0].'*",
                                                        "fuzziness": 0,
                                                        "fuzzy_prefix_length": 0,
                                                        "boost": 5                                                        
                                                    }
                                                },'.$key2_string_query.'
                                                {
                                                    "fuzzy": {
                                                        "input": {
                                                            "value": "'.$keys[0].'",
                                                            "fuzziness": 1,
                                                            "prefix_length": 2,
                                                            "boost": 5
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
                                                        "city": "'.$city.'"
                                                        ,"_cache": true 
                                                    }                                                    
                                                }
                                            ]
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

        $response       =   [
            'search_results' => json_decode($search_results,true)];

        return Response::json($response);
       
    }

}