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
    $res = es_curl_request($request);        
    echo json_encode($res);

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

  public function getvendorviewcount($vendor_id,$start_date = NULL, $end_date = NULL){

    $slug_data =Finder::active()                      
    ->where('_id', intval($vendor_id))                                 
    ->get(); 

    $start_date = isset($start_date) ? strtotime($start_date) : 1413904497799;
    $end_date = isset($end_date) ? strtotime($end_date) : undefined;
    
    $vendor_slug = $slug_data[0]['slug'];

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
                  "gte": '.$start_date.'
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

  $body1 = '{
    "size": 0,
    "query": {
      "filtered": {
        "filter": {
          "bool": {
            "must": [{
              "term": {
                "event_id": "vendorclick"
              }
            }, {
              "bool": {
                "should": [{
                  "term": {
                    "vendor": "'.$vendor_slug.'"
                  }
                }, {
                  "term": {
                    "vendor": '.$vendor_id.'
                  }
                }]
              }
            }]
          }
        }
      }
    }
  }';

  $body2 = '{
    "size": 0,
    "query": {
      "filtered": {
        "filter": {
          "bool": {
            "must": [{
              "term": {
                "event_id": "homepagefeatured"
              }
            }, {
              "term": {
                "vendor_id": "'.$vendor_id.'"
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
    'postfields' => $body1
    );

  $request2 = array( 
    'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
    'port' => 8060,
    'method' => 'POST',
    'postfields' => $body2
    );

  $request = array( 
    'url' => "http://fitternityelk:admin@52.74.67.151:8060/clicklogs/searchlogs/_search",
    'port' => 8060,
    'method' => 'POST',
    'postfields' => $body
    );


  $search_results     =   es_curl_request($request);
  $search_results_2   =   json_decode(es_curl_request($request2), true);
  $count_2            =   $search_results_2['hits']['total'];

  $search_results_1   =   json_decode(es_curl_request($request1), true);
  $count_1            =   $search_results_1['hits']['total'];

  $list = json_decode($search_results, true);  
  $list2 = $list["aggregations"]["2"];

  if(!empty($list2["buckets"]))
  {
    $list3 = $list2["buckets"][0];
    $list3['doc_count'] = intval($list3['doc_count']) + intval($count_1) + intval($count_2);
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
  $city = 'gurgaon';
  $fromdate = '2016-05-15';
  $todate = '2016-05-30';

  /*
   all conversion events tracked
  */

   $from_size = 0;

  //  this:
  //  if($from_size < 3000)
  // {

  //   $query = '{ 
  //     "from":'.$from_size.',
  //     "size":5000,  
  //     "query": {
  //       "filtered": {
  //         "filter": {
  //           "bool": {
  //             "must": [
  //             {
  //              "terms": {
  //                "event_id": [
  //                "bookingconfirm",
  //                "requestcallback",
  //                "membershipbuy",
  //                "callback"
  //                ]
  //              }
  //            },             
  //           {
  //             "range": {
  //               "timestamp": {
  //                 "gte": "'.$fromdate.'",
  //                 "lte": "'.$todate.'"
  //               }
  //             }
  //           }
  //           ]
  //         }
  //       }
  //     }
  //   }
  // }';

   $query = '{ 
    "from":'.$from_size.',
    "size":5000,  
    "query": {
      "filtered": {
        "filter": {
          "bool": {
            "must": [
            {
             "terms": {
               "event_id": [
               "paymentsuccesstrial",
               "callback",
               "paymentsuccess",
               "trialsuccess"        
               ]
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
  // return $query;
$search_results1     =   es_curl_request($request);
$search_results = json_decode($search_results1, true);
$bookingconfirm = $search_results['hits']['hits'];

$fp = fopen('utm_april.csv', 'w');
$header =    ["Conversion_point","Type","UserEmail", "Vendor", "Category", "Location", "Service", "Slot","City","BookingDate", "BookingTime" ,"TrailDate", "Device",
"trafficSource","TrafficType","UTM_Medium","UTM_Term","UTM_Content","UTM_Campaign","utm_source","gclid", "SessionURL", "SessionReferer"];
fputcsv($fp, $header);

foreach ($bookingconfirm as $bc) {

    // try{

  $bookinginfo = $bc['_source'];
  if(!isset($bookinginfo['useridentifier'])){
    continue;
  }
  $userid = $bookinginfo['useridentifier'];
  $sessionid = isset($bookinginfo['sessionid']) ? $bookinginfo['sessionid'] : '';
  $utmquery = '';
  if($utmquery === ''){
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
  
  $utm_result1     =   es_curl_request($request1);
  $utm_result = json_decode($utm_result1, true);
  
  if(sizeof($utm_result['hits']['hits']) > 0){
    $referer = isset($utm_result['hits']['hits'][0]['_source']['referer']) ? $utm_result['hits']['hits'][0]['_source']['referer'] : '';
    $page = isset($utm_result['hits']['hits'][0]['_source']['page']) ? $utm_result['hits']['hits'][0]['_source']['page'] : '';
    $trafficsource = ''; $tarffictype = '';
    $utm_medium='';$utm_term='';$utm_content='';$utm_campaign='';$utm_source='';$gclid = '';

    if((strpos(strtolower($referer), 'facebook') > -1) || (strpos(strtolower($page), 'facebook') > -1)||(strpos(strtolower($page), 'fb') > -1)){
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
  else if((strpos(strtolower($referer), 'fitmail') > -1)||(strpos(strtolower($page), 'fitmail') > -1)){
    $trafficsource = 'fitmail';
    $tarffictype = 'campaign';
    $utmurl = (strpos(strtolower($referer), 'utm') > -1 ) ? $referer : $page;  
    echo $utmurl;
    $utmarray = explode('?', $utmurl)[1];
    $utmlist = explode('&', $utmarray);
    foreach ($utmlist as $ul) {

     $final = explode('=', $ul);
     switch ($final[0]) {
      case 'utm_source':
      $utm_medium = $final[1];
      break;
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
}

else if(((strpos(strtolower($referer), 'google') > -1 ) || (strpos(strtolower($page), 'google') > -1))||((strpos(strtolower($referer), 'gclid') > -1 ) ||(strpos(strtolower($page), 'gclid') > -1 ))){
  $trafficsource = 'google';

  if((strpos(strtolower($referer), 'utm') > -1) || (strpos(strtolower($page), 'utm') > -1)||(strpos(strtolower($page), 'gclid') > -1 )||(strpos(strtolower($referer), 'gclid') > -1 )) {

   $tarffictype = 'inorganic';
   $utmurl = (strpos(strtolower($referer), 'utm') > -1 ) ? $referer : $page;     
   $utmarray = explode('?', $utmurl)[1];
   $utmlist = explode('&', $utmarray);
   foreach ($utmlist as $ul) {

     $final = explode('=', $ul);
     switch ($final[0]) {
      case 'utm_source':
      $utm_medium = $final[1];
      break;
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
}
else{
 $tarffictype = 'organic';


}

}

else{
  $trafficsource = 'direct';
  $tarffictype = 'organic';
}

$vendor_id = isset($bc1['finder_id']) ? intval($bc1['finder_id']) : 0;
$bc1 = $bc['_source'];
$service = isset($bc1['service_name']) ? $bc1['service_name'] : 'n/a';
$slot = isset($bc1['schedule_slot']) ? $bc1['schedule_slot'] : 'n/a';
$TrailDate = isset($bc1['schedule_date']) ? $bc1['schedule_date'] : 'n/a';

$finder = Finder::where('_id', $vendor_id)->with('category')->with('location')->timeout(40000000000)->first();

$category = isset($finder['category']['name']) ? $finder['category']['name'] : '';
$location = isset($finder['location']['name']) ? $finder['location']['name'] : '';
$timearray = explode('T', $bc1['timestamp']);
$type = isset($bc1['type']) ? $bc1['type'] : 'n/a';
$city = isset($bc1['city_id']) ? $bc1['city_id'] : 'n/a';
$email = isset($bc1['email']) ? $bc1['email'] : 'n/a';
$vendor = isset($bc1['finder_name']) ? $bc1['finder_name'] : 'n/a';
$fields = [$bookinginfo['event_id'],$type, $email, $vendor, $category, $location, $service, $slot, $city, $timearray[0], $timearray[1], $TrailDate, $bc1['device'],$trafficsource, $tarffictype,$utm_medium,$utm_term,$utm_content, $utm_campaign,$utm_source,$gclid, $page, $referer];

fputcsv($fp, $fields);


}
else{
 echo 'exit here</br>';
}

}


fclose($fp);
  //return 'done';
return Response::make(rtrim('utm_april.csv', "\n"), 200, $header);
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
                  "gte": "2015-11-06",
                  "lte": "2015-11-16"
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
      if((strpos($user['page'], 'dir='))||(strpos($user['page'], 'limit=')) || (strpos($user['page'], 'mode='))){
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

public function updatepaymentbooking(){
  $query = '{
    "from" : 0,
    "size" : 200000,
    "query": {
      "filtered": {

        "filter": {
          "bool": {
            "must": [{
              "terms": {
                "event_id": [
                "paymentsuccess",
                "bookingconfirm"
                ]
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
    'postfields' => $query
    );

  $transactions = es_curl_request($request2);
  $transactionlist = json_decode($transactions, true);

  foreach ($transactionlist['hits']['hits'] as $tran) {
    $src = $tran['_source'];
    $sessionid = isset($src['sessionid']) ? $src['sessionid'] : '';
    // return $tran['_source']['sessionid'];
    // $sessionid = $tran['_source']['sessionid'];

    $newquery = '{
      "query": {
        "filtered": {
          "filter": {
            "bool": {
              "must": [{
                "term": {
                  "visitsession": "'.$sessionid.'"
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
      },
      "sort": [{
        "timestamp": {
          "order": "asc"
        }
      }]
    }';

    $request3 = array( 
      'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
      'port' => 8060,
      'method' => 'POST',
      'postfields' => $newquery
      );

    $visits = es_curl_request($request3);
    $visitlist = json_decode($visits, true);
    $utm = '';
    $source = 'organic';

    if($visitlist['hits']['total'] > 0){      
      $utm = isset($visitlist['hits']['hits'][0]['_source']['utm']) ? $visitlist['hits']['hits'][0]['_source']['utm'] : '';
      $source = isset($visitlist['hits']['hits'][0]['_source']['utm']) ? 'inorganic' : 'organic';
    }

    if(!empty($utm)){
     $tran['_source']['utm'] = $utm;
   }   
   $tran['_source']['visitsource'] = $source;
   $transource = $tran['_source'];
   $id = $tran['_id'];
   $postfields_data = json_encode($transource);  

   $posturl = "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/logs/".$id;
   $updaterequest = array('url' => $posturl, 'port' => 8060, 'method' => 'PUT', 'postfields' => $postfields_data );
   es_curl_request($updaterequest);
   echo $id.'</br>';
 }

}
public function getglobalsearchkeywordmatrix(){

 $datefrom = Input::get('datefrom');
 $dateto = Input::get('dateto');
 $query = '{
  "from": 0,
  "size": 0,
  "query": {
    "filtered": {
      "filter": {
        "bool": {
          "must": [
          {
            "term": {
              "event_id": "globalsearch"
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
    "city": {
      "terms": {
        "field": "city",
        "size": 100
      },
      "aggs": {
        "keyword": {
          "terms": {
            "field": "keyword",
            "size": 20
          }
        }
      }
    }
  }
}';

$request3 = array( 
  'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
  'port' => 8060,
  'method' => 'POST',
  'postfields' => $query
  );

$keywords = es_curl_request($request3);
$keywordlist = json_decode($keywords, true);

$result = $keywordlist['aggregations']['city']['buckets'];

$response = array();
foreach ($result as $city) {
  array_push($response, array($city['key'] => $city['keyword']['buckets']));
}

return Response::json($response);
}

public function getglobalsearchclickedmatrix(){

 $datefrom = Input::get('datefrom');
 $dateto = Input::get('dateto');
 $query = '{
  "from": 0,
  "size": 0,
  "query": {
    "filtered": {
      "filter": {
        "bool": {
          "must": [
          {
            "term": {
              "event_id": "globalsearchclick"
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
    "city": {
      "terms": {
        "field": "city",
        "size": 100
      },
      "aggs": {
        "keyword": {
          "terms": {
            "field": "clicked",
            "size": 20
          }
        }
      }
    }
  }
}';

$request3 = array( 
  'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
  'port' => 8060,
  'method' => 'POST',
  'postfields' => $query
  );

$keywords = es_curl_request($request3);
$keywordlist = json_decode($keywords, true);

$result = $keywordlist['aggregations']['city']['buckets'];

$response = array();
foreach ($result as $city) {
  array_push($response, array($city['key'] => $city['keyword']['buckets']));
}

return Response::json($response);
}

public function getdailyvisitors(){
 $date = Input::get('date');
 $query = '{
  "from": 0,
  "size": 0,
  "query": {
    "bool": {
      "must": [
      {
        "term": {
          "event_id": "sessionstart"
        }
      },
      {
        "bool": {
          "must_not": [
          {
            "query_string": {
              "default_field": "page",
              "query": "*dir=*"
            }
          },
          {
            "query_string": {
              "default_field": "page",
              "query": "*limit=*"
            }
          },
          {
            "query_string": {
              "default_field": "page",
              "query": "*mode=*"
            }
          }
          ]
        }
      },
      {
        "range": {
          "timestamp": {
            "gte": "'.$date.'"
          }
        }
      }
      ]
    }
  },
  "aggs": {
    "users": {
      "cardinality": {
        "field": "userid"
      }
    }
  }
}';

$request3 = array( 
  'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
  'port' => 8060,
  'method' => 'POST',
  'postfields' => $query
  );

$visit = es_curl_request($request3);
$users = json_decode($visit, true);

$value = $users['aggregations']['users']['value'];

return $value;

}

public function migratedatatoclevertap(){


  try{

    $dt1 =new DateTime("2015-05-01 11:14:15.638276");
    ini_set('max_execution_time', 30000);
    $all_users = Customer::where('created_at', '>', $dt1)->take(50000)->get(array('email','facebook_id','created_at','gender','name','contact_no'));

    
    // return json_encode($all_users);
    $user_emails_list = array();

    $user_data = '';
    $booktrial_data = '';
    $order_data = '';
    $request_callback_data = '';
    $review_data = '';


    foreach ($all_users as $user) {


      $bool_exist = array_search($user['email'], $user_emails_list);
      
      if($bool_exist !== false){        
        continue;
      }

      if(strpos($user['email'], 'fitternity') !== false){
        continue;
      }

      array_push($user_emails_list, $user['email']);

      $user_trials_booked = Booktrial::where('customer_email', $user['email'])->get();


      $capture = Capture::where('customer_email', $user['email'])->get();


      $user_reviews_written = Review::where('cust_id', intval($user['_id']))->get();

      $user_orders = Order::where('customer_email', $user['email'])->get();

      $attr_phone = isset($user['contact_no']) ? $user['contact_no'] : 0;
      if($attr_phone !== ''){
        if(strpos($attr_phone, '+91') !== false){

        }
        else{
          $attr_phone = '+91'.$attr_phone;
        }
      }
      $attr_email = isset($user['email']) ? $user['email'] : '';
      $attr_gender = isset($user['gender']) ? $user['gender'] : '';
      $attr_name = isset($user['name']) ? $user['name'] : '';
      $customer_type = ((sizeof($user_trials_booked) > 0) || (sizeof($user_orders) > 0)) ? 'converted' : 'lead';
      $gender = ($user['gender'] === 'Female') ? 'F' : 'M';
      $facebook_id = isset($user['facebook_id']) ? $user['facebook_id'] : '';
      $date = new DateTime($user['created_at']);// format: MM/DD/YYYY
      $ts =  $date->format('U'); 

      $create_user_payload_v2 = '       
      {
        "identity":"'.$attr_email.'",
        "ts":'.$ts.',
        "type":"profile",
        "profileData":{
          "Name": "'.$attr_name.'",
          "Email":"'.$attr_email.'",
          "Phone":"'.$attr_phone.'",           
          "Customer Type":"'.$customer_type.'",           
          "Gender":"'.$gender.'"
        },
        "FBID" : "'.$facebook_id.'"
      },';

      $user_data = $user_data.$create_user_payload_v2;
      
      
      // foreach ($user_orders as $order) {

      //   $user_time = strtotime($order['created_at']);

      //   $current_time = time();
      //   $city_id = isset($order['city_id']) ? $order['city_id'] : 0;
      //   $phone = isset($order['customer_phone']) ? $order['customer_phone'] : '';
      //   $finder_id = isset($order['finder_id']) ? $order['finder_id'] : 0;
      //   $finder_name = isset($order['finder_name']) ? $order['findr_name'] : '';
      //   $service_id = isset($order['service_id']) ? $order['service_id'] : '';
      //   $service_name = isset($order['service_name']) ? $order['service_name'] : '';
      //   $type = isset($order['type']) ? $order['type'] : '';

      //   $user_order = '{         
      //       "email"  : "'.$user['email'].'",
      //       "phone"  : "'.$phone.'",
      //       "finder_id"  : '.$finder_id.',
      //       "finder_name" : "'.$finder_name.'",
      //       "service_id" : '.$service_id.',
      //       "service_name" : "'.$service_name.'",
      //       "type" : "'.$type.'",
      //       "city_id" : '.$city_id.'       
      //   },';

      //   $order_actions = $order_actions.$user_order;

      // }




        // $trial_book_actions = '';


      foreach ($user_trials_booked as $trial) {

        
        $date = new DateTime($trial['created_at']);// format: MM/DD/YYYY
        $booktrial_ts =  $date->format('U'); 

        $current_time = time();
        $city_id = isset($trial['city_id']) ? $trial['city_id'] : 0;
        $phone = isset($trial['customer_phone']) ? $trial['customer_phone'] : '';
        $finder_id = isset($trial['finder_id']) ? $trial['finder_id'] : 0;
        $finder_name = isset($trial['finder_name']) ? $trial['finder_name'] : '';
        $service_id = isset($trial['service_id']) ? $trial['service_id'] : '';
        $service_name = isset($trial['service_name']) ? $trial['service_name'] : '';
        $type = isset($trial['type']) ? $trial['type'] : '';
        $type1 = isset($trial['booktrial_type']) ? $trial['booktrial_type'] : '';
        $schedule_date = isset($trial['schedule_date']) ? $trial['schedule_date'] : '';
        $schedule_slot = isset($trial['schedule_slot']) ? $trial['schedule_slot'] : '';
        $amount = isset($trial['amount']) ? $trial['amount'] : 0;
        $event_name = $amount === 0 ? 'trial_success' : 'paymentsuccesstrial';

        $event_data = '{
          "finder_id" : '.$finder_id.',
          "finder_name" :"'.$finder_name.'",
          "service_id": "'.$service_id.'",
          "service_name" : "'.$service_name.'",
          "type" : "'.$type.'",
          "type_other" : "'.$type1.'",
          "schedule_slot" : "'.$schedule_slot.'",
          "schedule_date" : "'.$schedule_date.'",
          "amount" : '.$amount.'
        }';

        $customer_booktrial_payload = '{
          "identity" : "'.$user['email'].'",
          "ts" : '.$booktrial_ts.',
          "type" : "event",
          "eventName" : "'.$event_name.'",
          "evtData" : '.$event_data.'
        },';

        $booktrial_data = $booktrial_data.$customer_booktrial_payload;

      }
    }


    /*******************user data *************/

    $total_user_data = '{
      "d":[
      '.trim($user_data,',').'
      ]
    }';


    $curlrequestor = curl_init();
    curl_setopt($curlrequestor, CURLOPT_TIMEOUT, 2000);
    curl_setopt($curlrequestor, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlrequestor, CURLOPT_FORBID_REUSE, 0);
    curl_setopt($curlrequestor, CURLOPT_CUSTOMREQUEST, 'POST'); 
    curl_setopt($curlrequestor, CURLOPT_URL, 'https://api.clevertap.com/1/upload');

    $headers[] = 'X-CleverTap-Account-Id: RZZ-RRZ-R44Z';
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-CleverTap-Passcode:EVK-ISD-MAAL';

    curl_setopt($curlrequestor, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curlrequestor, CURLOPT_POSTFIELDS, $total_user_data);  
  $res = curl_exec($curlrequestor);
   $response = json_decode($res, true);    

    /*******************user data ******************/



    /*******************booktrial data *************/


    $total_book_trial_data = '{
      "d":[
      '.trim($booktrial_data,',').'
      ]
    }';

    $curlrequestor = curl_init();
    curl_setopt($curlrequestor, CURLOPT_TIMEOUT, 2000);
    curl_setopt($curlrequestor, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlrequestor, CURLOPT_FORBID_REUSE, 0);
    curl_setopt($curlrequestor, CURLOPT_CUSTOMREQUEST, 'POST'); 
    curl_setopt($curlrequestor, CURLOPT_URL, 'https://api.clevertap.com/1/upload');

    $headers[] = 'X-CleverTap-Account-Id: RZZ-RRZ-R44Z';
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-CleverTap-Passcode:EVK-ISD-MAAL';

    curl_setopt($curlrequestor, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curlrequestor, CURLOPT_POSTFIELDS, $total_book_trial_data);  

    $res = curl_exec($curlrequestor);
    $response = json_decode($res, true);

    return $res;

    // return $total_book_trial_data;

    /*******************booktrial data *************/



  }
  catch(Exception $e){
    Log::error($e);
    throw $e;    
  }

}

}