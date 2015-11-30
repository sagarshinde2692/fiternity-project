<?php
/**
 * Created by PhpStorm.
 * User: ajay
 * Date: 21/9/15
 * Time: 1:09 PM
 */
class KYUController extends \BaseController
{
  protected $indice = "autosuggest_index_alllocations";
  protected $type   = "autosuggestor";

  protected $elasticsearch_port = "";
  protected $elasticsearch_host = "";

  public function __construct()
  {
    parent::__construct();
       // $this->elasticsearch_host = Config::get('app.elasticsearch_host_kibana');
       // $this->elasticsearch_port = Config::get('app.elasticsearch_port_kibana');
  }

  public function pushkyuevent(){

    $event = Input::get();
    $dt = Carbon::now('Asia/Kolkata');
    $newstring = str_replace(" ", "T", $dt);                     
    $event['timestamp'] = $newstring;
    $postfields_data = json_encode($event);             
    $posturl = "http://fitternityelk:admin@52.74.67.151:8060/"."kyulogs/logs/" ;      
    $request = array('url' => $posturl, 'port' => 8060, 'method' => 'POST', 'postfields' => $postfields_data );
    echo "<br>$posturl    ---  ".es_curl_request($request);        
  }

  public function createkyucluster(){

    $body = '{
      "mappings": {
        "_default_": {
          "_source": {
            "compress": "true"
          },
          "_all": {
            "enabled": "true"
          }
        },
        "logs": {
          "dynamic_templates": [
          {
            "template1": {
              "match": "*",
              "match_mapping_type": "string",
              "mapping": {
                "type": "string",
                "index": "not_analyzed"
              }
            }
          },
          {
            "template2": {
              "match": "time*",
              "match_mapping_type": "string",
              "mapping": {
                "enabled": true,
                "type": "date",
                "format": "yyyy-MM-dd HH:mm:ss",
                "store": true,
                "index": "not_analyzed"
              }
            }
          }
          ],
          "properties": {}
        }
      },
      "settings": {
        "index": {
          "number_of_replicas": 0
        },
        "number_of_shards": 5
      }
    }';

    $url        = "http://fitternityelk:admin@52.74.67.151:8060/"."kyulogs";
    $request = array(
      'url' =>  $url,
      'port' => 8060,
      'method' => 'POST',
      );

    echo es_curl_request($request);
    $postfields_data    = json_encode(json_decode($body,true));

        //var_dump($postfields_data);   exit;
    $request = array(
      'url' => $url,
      'port' => 8060,
      'postfields' => $postfields_data,
      'method' => 'POST',
      );       
    echo es_curl_request($request); 
  }

  public function getvendorviewcount($vendor_slug = ''){
    $vendor_slug = ($vendor_slug != '') ? $vendor_slug : '';
    $body = '{"size": 0,
    "query": {
      "filtered": {
        "query": {
          "query_string": {
            "query": "vendor :('.$vendor_slug.')",
            "analyze_wildcard": true
          }
        },
        "filter": {
          "bool": {
            "must": [
            {
              "range": {
                "timestamp": {
                  "gte": 1413904497799                                 
                }
              }
            }
            ],
            "must_not": []
          }
        }
      }
    },
    "aggs": {
      "2": {
        "terms": {
          "field": "vendor",
          "size": 5,
          "order": {
            "_count": "desc"
          }
        }
      }
    }
  }
  ';

  $request = array( 
    'url' => "http://fitternityelk:admin@52.74.67.151:8060/clicklogs/searchlogs/_search",
    'port' => 8060,
    'method' => 'POST',
    'postfields' => $body
    );


  $search_results     =   es_curl_request($request);
  $list = json_decode($search_results, true);  
  $list2 = $list["aggregations"]["2"];
  if(!empty($list2["buckets"]))
  {
    $list3 = $list2["buckets"][0];
    $response       =   ['result' => $list3];
    return Response::json($response);
  }
  else
  {
    $zeroresult = array();
    $zeroresult = ['key' => $vendor_slug, "doc_count" => 0];
    $response = ['result' => $zeroresult];
    return Response::json($response);
  }  
}


public function getcitywiseviews(){

$datefrom = Input::get('datefrom');
$dateto = Input::get('dateto');

$query = '{
              "query": {
                "filtered": {
                  "filter": {
                    "bool": {
                      "must": [
                        {
                          "exists": {
                            "field": "city"
                          }
                        },
                        {
                          "range": {
                            "timestamp": {
                              "gte": "'.$datefrom.'",
                              "lte": "'.$dateto.'"
                            }
                          }
                        }
                      ]
                    }
                  }
                }
              },
              "aggs": {
                "cityname": {
                  "terms": {
                    "field": "city",
                    "size": 100
                  },
                  "aggs": {
                    "eventtype": {
                      "terms": {
                        "field": "event_id",
                        "size": 100
                      }
                    }
                  }
                }
              }
            }';
           
    $request = array( 
    'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
    'port' => 8060,
    'method' => 'POST',
    'postfields' => $query
    );

  $search_results1     =   es_curl_request($request);
  $search_results = json_decode($search_results1, true);  
  $response = array();
  foreach ($search_results['aggregations']['cityname']['buckets'] as $agg) {
     array_push($response, array($agg['key'] => $agg['eventtype']['buckets']));
  }
  return $response;
}

public function getfacebookUTM(){
  $fromdate = Input::json()->get('fromdate');
  $todate = Input::json()->get('todate');

  $query = '{
  "from": 0,
  "size": 500,
  "query": {
    "filtered": {
      "filter": {
        "bool": {
          "must": [
            {
              "term": {
                "event_id": "bookingconfirm"
              }
            },
            {
              "term": {
                "city": "mumbai"
              }
            },
            {
              "range": {
                "timestamp": {
                  "gte": "2015-11-01",
                  "lte": "2015-11-30"
                }
              }
            }
          ]
        }
      }
    }
  }
}';

$request = array( 
    'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
    'port' => 8060,
    'method' => 'POST',
    'postfields' => $query
    );

  $search_results1     =   es_curl_request($request);
  $search_results = json_decode($search_results1, true);
  return $search_results;exit;

}
}