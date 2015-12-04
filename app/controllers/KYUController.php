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
  // $fromdate = Input::json()->get('fromdate');
  // $todate = Input::json()->get('todate');
  // $city = Input::json()->get('city');
  $city = 'delhi';
  $fromdate = '2015-11-01';
  $todate = '2015-11-30';

  $query = '{ 
  "from":0,
  "size":2000,  
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
                "city": "'.$city.'"
              }
            },
            {
              "range": {
                "timestamp": {
                  "gte": "'.$fromdate.'",
                  "lte": "'.$todate.'"
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
  $bookingconfirm = $search_results['hits']['hits'];
  $fp = fopen('delhitrialsbook.csv', 'w');
  $header =    ["TrialType","UserEmail", "Vendor", "Category", "Location", "Service", "Slot","City","BookingDate", "BookingTime" ,"TrailDate", "Device",
  "trafficSource","TrafficType","UTM_Medium","UTM_Term","UTM_Content","UTM_Campaign"];
  fputcsv($fp, $header);

  foreach ($bookingconfirm as $bc) {
    echo $bc['_id'].'</br>';
    $bookinginfo = $bc['_source'];
    $userid = $bookinginfo['useridentifier'];
    $sessionid = isset($bookinginfo['sessionid']) ? $bookinginfo['sessionid'] : '';
    $utmquery = '';
    if($utmquery !== ''){
      $utmquery = '{
        "query": {
          "filtered": {
            "filter": {
              "bool": {
                "must": [{
                  "term": {
                    "userid": "'.$userid.'"
                  }
                }, {
                  "term": {
                    "visitsession": "'.$sessionid.'"
                  }
                },{
                  "term": {
                    "event_id": "sessionstart"
                  }
                }, {
                  "range": {
                    "timestamp": {
                      "gte": "'.$fromdate.'",
                      "lte": "'.$todate.'"
                    }
                  }
                }]
              }
            }
          }
        }
      }';
    }
    else{
      $utmquery = '{
        "query": {
          "filtered": {
            "filter": {
              "bool": {
                "must": [{
                  "term": {
                    "userid": "'.$userid.'"
                  }
                },{
                  "term": {
                    "event_id": "sessionstart"
                  }
                }, {
                  "range": {
                    "timestamp": {
                      "gte": "2015-11-01",
                      "lte": "2015-11-30"
                    }
                  }
                }]
              }
            }
          }
        }
      }';
    }

    $request1 = array( 
      'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
      'port' => 8060,
      'method' => 'POST',
      'postfields' => $utmquery
      );
        //return $utmquery;exit;
    $utm_result1     =   es_curl_request($request1);
    $utm_result = json_decode($utm_result1, true);

    if(sizeof($utm_result['hits']['hits']) > 0){
      $referer = isset($utm_result['hits']['hits'][0]['_source']['referer']) ? $utm_result['hits']['hits'][0]['_source']['referer'] : '';
      $page = isset($utm_result['hits']['hits'][0]['_source']['page']) ? $utm_result['hits']['hits'][0]['_source']['page'] : '';
      $trafficsource = ''; $tarffictype = '';
      $utm_medium='';$utm_term='';$utm_content='';$utm_campaign='';

      if((strpos(strtolower($referer), 'facebook') > -1) || (strpos(strtolower($page), 'facebook') > -1)){
        //echo $page.'       ---      '.$referer;
        $trafficsource = 'facebook';
        $utmurl = '';
        if((strpos(strtolower($referer), 'utm') > -1) || (strpos(strtolower($page), 'utm') > -1)) {
          $tarffictype = 'inorganic';  

          $utmurl = (strpos(strtolower($referer), 'utm') > -1 ) ? $referer : $page;         
          if(strpos(strtolower($utmurl), 'facebook.com') === false){
           //echo 'here'.$utmurl.'----------------------'.$userid.'</br>';
           $utmarray = explode('?', $utmurl)[1];
           $utmlist = explode('&', $utmarray);
           foreach ($utmlist as $ul) {
             $final = explode('=', $ul);
             switch ($final[0]) {
               case 'utm_medium':
               $utm_medium = $final[1];
               break;
               case 'utm_term':
               $utm_term = $final[1];
               break;
               case 'utm_content':
               $utm_content = $final[1];
               break;
               case 'utm_campaign':
               $utm_campaign = $final[1];
               break;               
               default:                
               break;
             }
           }
         }
       }
       else{
        $tarffictype = 'organic';
      }
    }   

    else if((strpos($referer, 'google') > -1 ) || (strpos($page, 'google') > -1)){
      $trafficsource = 'google';
      $tarffictype = 'organic';
      
    }
    else{
      $trafficsource = 'direct';
      $tarffictype = 'organic';
    }

    $bc1 = $bc['_source'];
    $service = isset($bc1['service']) ? $bc1['service'] : 'n/a';
    $slot = isset($bc1['slot']) ? $bc1['slot'] : 'n/a';
    $TrailDate = isset($bc1['date']) ? $bc1['date'] : 'n/a';
    $vendor = isset($bc1['vendor']) ? $bc1['vendor'] : 'n/a';
    $finder = Finder::where('slug', $vendor)->with('category')->with('location')->timeout(40000000000)->first();

    $category = isset($finder['category']['name']) ? $finder['category']['name'] : '';
    $location = isset($finder['location']['name']) ? $finder['location']['name'] : '';
    $timearray = explode('T', $bc1['timestamp']);

    $fields = [$bc1['type'], $bc1['email'], $bc1['vendor'], $category, $location, $service, $slot, $bc1['city'], $timearray[0], $timearray[1], $TrailDate, $bc1['device'],$trafficsource, $tarffictype,$utm_medium,$utm_term,$utm_content, $utm_campaign];
    
    fputcsv($fp, $fields);
    
  }
  else{
   echo 'exit here</br>';
  }
}
fclose($fp);
  //return 'done';
return Response::make(rtrim('delhitrialsbook.csv', "\n"), 200, $header);
}

public function sessionutm(){

  $query = '{
            "from": 0,
            "size": 30000,
            "query": {
              "bool": {
                "must": [{
                  "term": {
                    "event_id": "sessionstart"
                  }
                }, {
                  "bool": {
                    "should": [{
                      "query_string": {
                        "default_field": "referer",
                        "query": "*utm*"
                      }
                    }, {
                      "query_string": {
                        "default_field": "page",
                        "query": "*utm*"
                      }
                    }]
                  }
                }, {
                  "bool": {
                    "must_not": [{
                      "constant_score": {
                        "filter": {
                          "exists": {
                            "field": "utm"
                          }
                        }
                      }
                    }]
                  }
                }]
              }
            },
            "sort": [{
              "timestamp": {
                "order": "asc"
              }
            }]
          }';


  $request = array( 
      'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
      'port' => 8060,
      'method' => 'POST',
      'postfields' => $query
      );
          
    $utm_result = es_curl_request($request);
    $utm_result = json_decode($utm_result, true);
    $events = $utm_result['hits']['hits'];

    foreach ($events as $event) {
      $utm_medium = ''; $utm_term = ''; $utm_content = ''; $utm_campaign = '';
      $utm_source = ''; $gclid = '';

      $_id = $event['_id'];
      $_source = $event['_source'];     
      $page = isset($_source['page']) ? strtolower($_source['page']) : '';
      $referer = isset($_source['referer']) ? strtolower($_source['referer']) : '';

      if(strpos($page, 'utm') > -1){

        if(strpos($page, 'facebook') > -1){         
           $utm_source = 'facebook';
           $utmarray = explode('?', $page)[1];
           $utmlist = explode('&', $utmarray);
           foreach ($utmlist as $ul) {
             $final = explode('=', $ul);
             switch ($final[0]) {
               case 'utm_medium':
               $utm_medium = $final[1];
               break;
               case 'utm_term':
               $utm_term = $final[1];
               break;
               case 'utm_content':
               $utm_content = $final[1];
               break;
               case 'utm_campaign':
               $utm_campaign = $final[1];
               break;               
               default:                
               break;
             }
           }

          $utm['source'] = $utm_source;
          $utm['medium'] = $utm_medium;
          $utm['term'] = $utm_term;
          $utm['content'] = $utm_content;
          $utm['campaign'] = $utm_campaign;
          $_source['utm'] = $utm;
          $postfields_data = json_encode($_source);
          
          $posturl = "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/logs/".$_id;
          $updaterequest = array('url' => $posturl, 'port' => 8060, 'method' => 'PUT', 'postfields' => $postfields_data );
          es_curl_request($updaterequest);
          echo $_id.'</br>';
        }

       if(strpos($page, 'google') > -1){
        $utm_source = 'google';
           $utmarray = explode('?', $page)[1];
           $utmlist = explode('&', $utmarray);
           foreach ($utmlist as $ul) {
             $final = explode('=', $ul);
             switch ($final[0]) {
               case 'utm_medium':
               $utm_medium = $final[1];
               break;
               case 'utm_term':
               $utm_term = $final[1];
               break;
               case 'utm_content':
               $utm_content = $final[1];
               break;
               case 'utm_campaign':
               $utm_campaign = $final[1];
               break;   
               case 'gclid':
               $gclid = $final[1];
               break;              
               default:                
               break;
             }
           }

          $utm['source'] = $utm_source;
          $utm['medium'] = $utm_medium;
          $utm['term'] = $utm_term;
          $utm['content'] = $utm_content;
          $utm['campaign'] = $utm_campaign;
          $utm['gclid'] = $gclid;
          $_source['utm'] = $utm;

          $postfields_data = json_encode($_source);
          
          $posturl = "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/logs/".$_id;
          $updaterequest = array('url' => $posturl, 'port' => 8060, 'method' => 'PUT', 'postfields' => $postfields_data );
          es_curl_request($updaterequest);
          echo $_id.'</br>';
      }

      elseif (strpos($referer, 'utm') > -1) {
          // dont need for facebook as facebook will always have it in page property, so created a placeholder for google
      }     
    }
}
}
}