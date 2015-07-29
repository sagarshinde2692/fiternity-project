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

        $this->elasticsearch_default_url = "http://" . Config::get('app.elasticsearch_host') . ":" . Config::get('app.elasticsearch_port') . '/' . Config::get('app.elasticsearch_default_index') . '/' . Config::get('app.elasticsearch_default_type') . '/';
        $this->elasticsearch_url = "http://" . Config::get('app.elasticsearch_host') . ":" . Config::get('app.elasticsearch_port') . '/';
        $this->elasticsearch_host = Config::get('app.elasticsearch_host_new');
        $this->elasticsearch_port = Config::get('app.elasticsearch_port');
    }

    public function getautosuggestresults(){

        $from    =         Input::json()->get('from') ? Input::json()->get('from') : 0;
        $key     =         Input::json()->get('key');
        $city    =         Input::json()->get('city') ? Input::json()->get('city') : 'mumbai';


        $city_filter = '{ "term" : { "city" : "'.$city.'" } },';
        $key_filter  = '{ "term" : { "input" : "'.$key.'" } },';

        $query       =  '{
                            "from" : '.$from.',
                            "size" : 10,
                            "query" : {
                            "filtered" : {
                                    "query" :  {"match": {
                                                          "input": "'.$key.'"
                                                        }},
                                    "filter" : {
                                                "bool" : {
                                                            "must" : {
                                                                        "term" : {
                                                                                    "city" : "'.$city.'"
                                                                        }
                                                            }
                                                }
                                    }
                                }
                            }
        }';

        $body = json_decode($query,true);
        $searchParams['index'] = $this->indice;
        $searchParams['type']  = $this->type;
        $searchParams['body']  = $body;
        $rs = Es::search($searchParams);
        if(isset($rs['hits']['hits']) && $rs['hits']['hits'] != '')
        {
            return $rs;
        }

        return '';
    }

}