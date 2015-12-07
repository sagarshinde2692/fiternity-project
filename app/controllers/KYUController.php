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
  return $events;exit;
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
public function createkyuusers(){
  $m = new MongoClient("mongodb://54.255.173.1:27017");
  $db = $m->fitadmin;
  $collection = $db->userskyu;

  // $esquery = '{
  //   "from": 0,
  //   "size": 10000000,
  //   "query": {
  //     "bool": {
  //       "must": [{
  //         "term": {
  //           "event_id": "sessionstart"
  //         }
  //       }, {
  //         "bool": {
  //           "must_not": [{
  //             "query_string": {
  //               "default_field": "page",
  //               "query": "*dir=*"
  //             }
  //           },{
  //             "query_string": {
  //               "default_field": "page",
  //               "query": "*limit=*"
  //             }
  //           }]
  //         }
  //       },{"range": {
  //         "timestamp": {
  //           "gte": "2015-11-01",
  //           "lte": "2015-11-10"
  //         }
  //       }}]
  //     }
  //   },
  //   "sort": [{
  //     "timestamp": {
  //       "order": "asc"
  //     }
  //   }],"aggs": {
  //     "users": {
  //       "terms": {
  //         "field": "userid",
  //         "size": 1000000
  //       }
  //     }
  //   }
  // }
  // ';

  $esquery = '{
    "from": 0,
    "size": 20000,
    "query": {
      "filtered": {
        "filter": {
          "bool": {
            "must": [
            {
              "terms": {
                "event_id": [
                "signin",
                "requestcallback",
                "bookingconfirm"
                ]
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
    'postfields' => $esquery
    );

  $user = es_curl_request($request);
  $user_result = json_decode($user, true);
  //return $user_result;exit;
  //$useridlist = $user_result['aggregations']['users']['buckets'];
  // return $useridlist;exit;
  $useridlist = $user_result['hits']['hits'];
  foreach($useridlist as $user1){
    $user = $user1['_source'];
    $key = $user['useridentifier'];

    $firstvisitquery = '{
      "from": 0,
      "size": 100,
      "query": {
        "bool": {
          "must": [{
            "term": {
              "event_id": "sessionstart"
            }
          }, {
            "bool": {
              "must_not": [{
                "query_string": {
                  "default_field": "page",
                  "query": "*dir=*"
                }
              }, {
                "query_string": {
                  "default_field": "page",
                  "query": "*limit=*"
                }
              }]
            }
          }, {
            "range": {
              "timestamp": {
                "gte": "2015-11-01",
                "lte": "2015-11-30"
              }
            }
          }, {
            "term": {
              "userid": {
                "value": "'.$key.'"
              }
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

    $request1 = array( 
      'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
      'port' => 8060,
      'method' => 'POST',
      'postfields' => $firstvisitquery
      );

    $user1 = es_curl_request($request1);
    $user_visits = json_decode($user1, true);

    $kyuuser['_id'] = $key;
    $kyuuser['type'] = 'unidentified';
    $kyuuser['totalsession'] = $user_visits['hits']['total'];   
    //return $user_visits['hits']['hits'][0];exit;
    //return  $user_visits['hits']['hits'][0];exit;
    if($user_visits['hits']['total'] > 0){
      $first_visit = $user_visits['hits']['hits'][0];
      $kyuuser['firstvisittype'] = 'organic';
      $kyuuser['type'] = 'identified';
      if(isset($first_visit['_source']['utm'])){
        $kyuuser['firstvisittype'] = 'inorganic';
        $kyuuser['utm_source'] = $first_visit['_source']['utm'];
      }
      $kyuuser['firstpagelanded'] = $first_visit['_source']['page'];

    // $identification_query = '{
    //   "query": {
    //     "filtered": {
    //       "filter": {
    //         "bool": {
    //           "must": [{
    //             "terms": {
    //               "event_id": [
    //               "signin",
    //               "requestcallback",
    //               "bookingconfirm"
    //               ]
    //             }
    //           }, {
    //             "term": {
    //               "useridentifier": "'.$key.'"
    //             }
    //           }]
    //         }
    //       }
    //     }
    //   }
    // }';

    // $request2 = array( 
    //   'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
    //   'port' => 8060,
    //   'method' => 'POST',
    //   'postfields' => $identification_query
    //   );

    // $identifications = es_curl_request($request2);
    // $identifications = json_decode($identifications, true);
    //return $kyuuser;exit;
      switch ($user['event_id']) {
        case 'requestcallback' || 'bookingconfirm':
        $kyuuser['email'] = $user['email'];
        break;
        case 'signin':
        if(isset($user['email'])){
         $kyuuser['email'] = $user['email'];           
       }
       else if(isset($identity['_source']['userid'])){
         $kyuuser['email'] = $user['userid'];         
       }
       break;
       default:
       break;
       $kyuuser['emailtmsp'] = $user['timestamp'];

   //  if($identifications['hits']['total'] !== 0){
   //    foreach ($identifications['hits']['hits'] as $identity) {
   //      switch ($identity['_source']['event_id']) {
   //        case 'requestcallback' || 'bookingconfirm':
   //        $kyuuser['email'] = $identity['_source']['email'];
   //        break;
   //        case 'signin':
   //        if(isset($identity['_source']['email'])){
   //         $kyuuser['email'] = $identity['_source']['email'];           
   //       }
   //       else if(isset($identity['_source']['userid'])){
   //         $kyuuser['email'] = $identity['_source']['userid'];
   //         $kyuuser['emailtmsp'] = $identity['_source']['timestamp'];
   //       }
   //       break;
   //       default:
   //       break;
   //     }
   //     $kyuuser['emailtmsp'] = $identity['_source']['timestamp'];

   //   }
   // }
   // else{

   // }
     }

     $emailquery = array('email' => $kyuuser['email']);
     $idquery = array('_id' => $kyuuser['_id']);
     $cursor = $collection->find($emailquery);
     $secondcursor = $collection->find($idquery);
     $bool = true;
     foreach ($cursor as $cur) {
      $bool = false;
    }
    foreach ($secondcursor as $scur) {
      $bool = false;
    }
    if($bool){
     $collection->insert($kyuuser, array("w" => 1));

   }
 }
}
}
public function getunidentifiedusers(){
  $m = new MongoClient("mongodb://54.255.173.1:27017");
  $db = $m->fitadmin;
  $collection = $db->userskyu;

  $query = '{
    "from": 0,
    "size": 2000000000,
    "query": {
      "filtered": {       
        "filter": {
          "bool": {
            "must": [{
              "term": {
                "event_id": "sessionstart"
              }
            }, {
              "range": {
                "timestamp": {
                  "gte": "2015-11-01",
                  "lte": "2015-11-10"
                }
              }
            }]
          }
        }
      }
    }
  }';

  $request1 = array( 
    'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
    'port' => 8060,
    'method' => 'POST',
    'postfields' => $query
    );

  $user1 = es_curl_request($request1);
  $user_visits = json_decode($user1, true);
 
  foreach ($user_visits['hits']['hits'] as $user1) {

    $user = $user1['_source'];
    $isbot = false;
    if(isset($user['page'])){
      if((strpos($user['page'], 'dir=') === -1)||(strpos($user['page'], 'limit='))){
        $isbot = true;
      }
    }  
    if(!$isbot){
      $key = $user['userid'];
      $kyuuser['_id'] = $key;
      $kyuuser['type'] = 'unidentified';
      $idquery = array('_id' => $key);
      $cursor = $collection->find($idquery);
      $bool = true;
      foreach ($cursor as $val) {
        $bool = false;
      }
      if($bool){
        $sessionquery = '{
          "from": 0,
          "size": 20000000,
          "query": {
            "filtered": {
              "filter": {
                "bool": {
                  "must": [{
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
                  }, {
                    "term": {
                      "userid": "'.$key.'"
                    }
                  }]
                }
              }
            }
          },
          "sort": [{
            "timestamp": {
              "order": "asc"
            }
          }]
        }';
        $request2 = array( 
          'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
          'port' => 8060,
          'method' => 'POST',
          'postfields' => $sessionquery
          );

        $sessions = es_curl_request($request2);
        $sessionlist = json_decode($sessions, true);      
        $kyuuser['totalsession'] = $sessionlist['hits']['total'];

        $firstsession = $sessionlist['hits']['hits'][0];
        $kyuuser['firstvisittype'] = 'organic';
        $kyuuser['firstpagelanded'] = $firstsession['_source']['page'];
        if(isset($firstsession['_source']['utm'])){
          $kyuuser['utm_source'] = $firstsession['_source']['utm'];
          $kyuuser['firstvisittype'] = 'inorganic';
        }                   
        $resp = $collection->insert($kyuuser, array("w" => 1));
        
      }
    }
  }
}
}