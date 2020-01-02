<?php


class GlobalPushController extends \BaseController
{
  protected $indice = "autosuggest_index_alllocations3";
  protected $type   = "autosuggestor";
  protected $elasticsearch_port = "";
  protected $elasticsearch_default_index = "";
  protected $elasticsearch_url = "";
  protected $elasticsearch_default_url = "";
  protected $elasticsearch_host = "";
  protected $citylist = array(1,2,3,4,5,6,8,9,10,11,12,13);
  protected $citynames = array('1' => 'mumbai','2' => 'pune', '3' => 'bangalore', '4' => 'delhi','5' => 'hyderabad','6' => 'ahmedabad', '8' => 'gurgaon', '9' => 'noida', '11' => 'jaipur', '12' => 'chandigarh', '10' => 'faridabad', '13' => 'kolkata');
  protected $primaryfiltersrank = array('free trial' => '10', 'group classes' => '8', 'parking' => '6', 'sunday open' => '4', 'locker and shower facility' => '2');

  protected $amenitiesrank = array('gyms' => array('24 hour facility' => '6', 'free wifi' => '5', 'juice bar' => '4', 'steam and sauna' => '3', 'stretching area' => '2', 'swimming pool' => '1'), 'yoga' => array('power yoga' => '9', 'iyengar yoga' => '8', 'ashtanga yoga' => '7', 'hatha yoga' => '6', 'aerial yoga' => '5', 'vinyassa yoga' => '4','hot yoga' => '3', 'post natal yoga' => '2', 'prenatal yoga' => '1'), 'zumba' => array('zumba classes' => '2', 'aqua zumba classes' => '1'), 'cross functional training' => array('les mills' => '7', 'calisthenics' => '6', 'cross training' => '5', 'trx training' => '4', 'combine training' => '3', 'group x training' => '2', 'trampoline workout' => '1'), 'crossfit' => array('open box' => '7', 'tires & ropes' => '6', 'olympic lifting' => '5', 'group training' => '4', 'personal training' => '3', 'gymnastic routines' => '2', 'kettle bell training' => '1'), 'pilates' => array('mat pilates' => '2', 'reformer pilates or stott pilates' => '1'), 'mma & kickboxing' => array('mixed martial arts' => '12', 'karate' => '11', 'kick boxing' => '10', 'judo' => '9', 'jujitsu' => '8', 'karv maga' => '7', 'kung fu' => '6', 'muay thai' => '5', 'taekwondo' => '4', 'tai chi' => '3', 'capoeira' => '2', 'kalaripayattu' => '1'), 'dance' => array('bollywood' =>'16', 'hip hop' => '15', 'salsa' => '14', 'free style' => '13', 'contemporary' => '12', 'jazz' => '11', 'jive' => '10', 'belly dancing' => '9', 'cha cha cha' => '8', 'kathak' => '7', 'b boying' => '6', 'bharatanatyam' => '5', 'ballroom' => '4', 'locking and popping' => '3', 'ballet' => '2', 'waltz' => '1'));

    protected $es_data = [];
    protected $i = 0;
    protected $t = 0;
    public function __construct()
  {
    parent::__construct();
    $this->elasticsearch_host = Config::get('app.es.host');
    $this->elasticsearch_port = Config::get('app.es.port');
    $this->elasticsearch_url  = 'http://'.$this->elasticsearch_host.':'.$this->elasticsearch_port.'/autosuggest_index_alllocations3/autosuggestor/';
    $this->build_elasticsearch_url = 'http://'.$this->elasticsearch_host.':'.$this->elasticsearch_port.'/autosuggest_index_alllocations3';
    $this->name = 'autosuggest_index_alllocations';
    $this->elasticsearch_url_build = 'http://'.$this->elasticsearch_host.':'.$this->elasticsearch_port.'/';
  }

  public function rollingbuildautosuggest(){
    
    ini_set('max_execution_time', 300000);

    /*
    appending date to rolling builds for new index
    */
    $timestamp =  date('Y-m-d');
    $index_name = $this->index_name = $this->name.$timestamp.'-'.date('H-i-s');

    /*
   creating new index appended with timestamp
    */

    $url = $this->elasticsearch_url_build."$index_name";
    $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST',
    );

    es_curl_request($request);
    // sleep(5);

    // var_dump($request);exit;

    /*
  closing newly created index
    */

    $url = $this->elasticsearch_url_build."$index_name/_close";
    $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST'
    );

    es_curl_request($request);
    // sleep(5);

    $settings = '{
  "analysis": {
    "analyzer": {
      "synonymanalyzer": {
        "tokenizer": "standard",
        "filter": ["lowercase", "locationsynfilter"]
      },
      "locationanalyzer": {
        "type": "custom",
        "filter": ["standard", "locationsynfilter", "lowercase", "delimiter-filter"],
        "tokenizer": "my_ngram_tokenizer"
      },
      "categoryanalyzer": {
        "type": "custom",
        "filter": ["standard", "categorysynfilter", "lowercase", "delimiter-filter"],
        "tokenizer": "my_ngram_tokenizer"
      },
      "search_analyzer": {
        "type": "custom",
        "filter": [
          "lowercase"
        ],
        "tokenizer": "standard"
      },
      "index_analyzerV1": {
        "type": "custom",
        "filter": [
          "standard",
          "lowercase"
        ],
        "tokenizer": "my_ngram_tokenizer"
      },
      "index_analyzerV2": {
        "type": "custom",
        "filter": [
          "standard",
          "lowercase",
          "ngram-filter"
        ],
        "tokenizer": "standard"
      },
      "input_analyzer": {
        "type": "custom",
        "tokenizer": "standard",
        "filter": [
          "standard",
          "lowercase",
          "ngram-filter",
          "titlesynfilter"
        ]
      }
    },
    "tokenizer": {
      "my_ngram_tokenizer": {
        "type": "edgeNGram",
        "min_gram": "3",
        "max_gram": "20"
      },
      "input_ngram_tokenizer": {
        "type": "edgeNGram",
        "min_gram": "2",
        "max_gram": "25"
      }
    },
    "filter": {
      "ngram-filter": {
        "type": "edgeNGram",
        "min_gram": "1",
        "max_gram": "20"
      },
      "stop-filter": {
        "type": "stop",
        "stopwords": "_english_",
        "ignore_case": "true"
      },
      "snowball-filter": {
        "type": "snowball",
        "language": "english"
      },
      "delimiter-filter": {
        "type": "word_delimiter",
        "preserve_original": true
      },
      "locationsynfilter": {
        "type": "synonym",
        "synonyms": [
          "lokhandwala,andheri west",
          "versova,andheri west",
          "oshiwara,andheri west",
          "chakala,andheri east",
          "jb nagar,andheri east",
          "marol,andheri east",
          "sakinaka,andheri east",
          "chandivali,powai",
          "vidyavihar,ghatkopar",
          "dharavi,sion",
          "chunabatti,sion",
          "deonar,chembur",
          "govandi,chembur",
          "anushakti nagar,chembur",
          "charkop,kandivali",
          "seven bungalows,andheri west",
          "opera house,grant road",
          "nana chowk,grant road",
          "shivaji park,dadar",
          "lalbaug,dadar",
          "walkeshwar,malabar hill",
          "tilak nagar,chembur",
          "vashi,navi mumbai",
          "sanpada,navi mumbai",
          "juinagar,navi mumbai",
          "nerul,navi mumbai",
          "seawoods,navi mumbai",
          "cbd belapur,navi mumbai",
          "kharghar,navi mumbai",
          "airoli,navi mumbai",
          "kamothe,navi mumbai",
          "kopar khairan,navi mumbai",
          "gamdevi,hughes road",
          "mazgaon,byculla",
          "navi mumbai,vashi",
          "navi mumbai,sanpada",
          "navi mumbai,juinagar",
          "navi mumbai,nerul",
          "navi mumbai,seawoods",
          "navi mumbai,cbd belapur",
          "navi mumbai,kharghar",
          "navi mumbai,airoli",
          "navi mumbai,kamothe",
          "navi mumbai,kopar khairan",
          "gamdevi,hughes road",
          "mazgaon,byculla"
        ]
      },
      "categorysynfilter": {
        "type": "synonym",
        "synonyms": [
          "gyms,gymnasium, gym deals, gym workout",
          "zumba,zumba fitness,zumba workout,zumba dance,zumba dance workout,zumba instructor,zumba weight loss,zumba training,aerobics",
          "crossfit,crossfit workouts,crossfit training,crossfit box,crossfit gym,crossfit weight loss,crossfit fitness",
          "pilates,pilates exercises,pilates weiht loss",
          "mma and kick boxing,kickboxing classes,mixed martial arts,mma,kickboxing training",
          "marathon training,marathon coach,marathon fitness,half marathon training,running clubs,marathon training clubs",
          "healthy tiffins,tiffins,tiffining,tiffing service,tiffing",
          "personal trainers,yoga instructor,yoga trainer"
        ]
      },
      "titlesynfilter": {
        "type": "synonym",
        "synonyms": [
          "golds , gold, gold\'s",
          "talwalkars, talwalkar"
        ]

      }
    }
  }
}';

    /*
    add setting to new index
    */
    $url                = $this->elasticsearch_url_build."$index_name/_settings";
    $postfields_data    = json_encode(json_decode($settings,true));

    $request = array(
        'url' => $url,
        'port' => $this->elasticsearch_port,
        'postfields' => $postfields_data,
        'method' => 'PUT'
    );

    es_curl_request($request);
    // sleep(5);

    /*
    open newly created index
    */

    $url = $this->elasticsearch_url_build."$index_name/_open";
    $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST'
    );

    es_curl_request($request);
    // sleep(4);

    $mapping = '{
          "_source": {
            "compress": "true"
          },
          "_all": {
            "enabled": "true"
          },
          "properties": {
            "input": {
              "type": "string",
              "index_analyzer": "input_analyzer"
            },
            "autosuggestvalue": {
              "type": "string",
              "index": "not_analyzed",
              "store": "yes"
            },
            "city": {
              "type": "string",
              "index": "not_analyzed",
              "store": "yes"
            },
            "location": {
              "type": "string",
              "index": "not_analyzed",
              "store": "yes"
            },
            "slug": {
              "type": "string",
              "index": "not_analyzed",
              "store": "yes"
            },
            "type": {
              "type": "string",
              "index": "not_analyzed",
              "store": "yes"
            },
            "inputloc1":{
              "type": "string",
              "index_analyzer": "locationanalyzer"
            },
            "inputv3":{
              "type": "string",
              "index_analyzer": "index_analyzerV2"
            },
            "inputv4":{
              "type": "string",
              "index_analyzer": "index_analyzerV2"
            },
            "inputcat1":{
              "type": "string",
              "index_analyzer": "categoryanalyzer"
            },
            "inputservicecat":{
              "type": "string",
              "index_analyzer": "index_analyzerV2"
            },
            "geolocation" : {
              "type" : "geo_point",
              "geohash": true,
              "geohash_prefix": true,
              "geohash_precision": 10
            }
          }
        }';

    /*
    add mappings to new index
    */

    $postfields_data    =   json_encode(json_decode($mapping,true));
    $url        =   $this->elasticsearch_url_build."$index_name/autosuggestor/_mapping";
    $request = array(
        'url' => $url,
        'port' => $this->elasticsearch_port,
        'method' => 'PUT',
        'postfields' => $postfields_data
    );
    es_curl_request($request);
    // sleep(5);

    /*

    Fill ES cluster will data

    */

    
    $this->pushBrandOutlets($index_name);//required
    $this->pushcategorylocations($index_name);//required
    $this->pushcategorycity($index_name);//required
    $this->pushallfittnesslocation($index_name);//required
    // // $this->pushservicecategorylocations($index_name);
    // // $this->pushservicecategorycity($index_name);
    // $this->t = time();
    foreach ($this->citylist as $key => $city) {
        $this->pushfinders($index_name, $city);//required
    }
    // Log::info(time() - $t);
    // return "Done";


// //        $this->pushcategorywithf$thosacilities($index_name);
// //        $this->pushcategoryoffering($index_name);
        $this->pushcategoryofferinglocation($index_name);//required
// //        $this->pushcategoryfacilitieslocation($index_name);
// //        $this->pushofferingcity($index_name);
    $this->addToEsData(null, true);

    /*
    point the aliases for the cluster to new created index
    */

    $alias_request = '{
        "actions": [ {
        "remove": {
            "index": "*",
            "alias": "fitternity_autosuggestor"
        }
        },
        {
        "add": {
            "index": "'.$index_name.'",
            "alias": "fitternity_autosuggestor"
        }
        }]
    }';

    $url        =   $this->elasticsearch_url_build."_aliases";
    $payload =  json_encode(json_decode($alias_request,true));
    $request = array(
        'url' => $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST',
        'postfields' => $payload
    );
    echo es_curl_request($request);
    Log::info($this->index_name);
    return "Done";

  }


  public function pushfinders($index_name, $city_id){
    
    ini_set('max_execution_time', 30000);
    // ini_set('memory_limit', '512M');
    $city_id = (int) $city_id;
    Finder::$withoutAppends = true;

    $limit = 500;

    $i=0;
    if (!function_exists(('esParse'))) {
        function esParse($source = ''){
            $cluster = '';
            Log::info($source['_id']);
            $info_service_list = array();
            $data = $source;

            if(empty($data['multiaddress'])){
                $data['multiaddress'] = null;
            }
            if(isset($data['services'])&& !empty($data['services'])){
                $info_service_list = array_map('strtolower', array_pluck($data['services'], 'name'));
            }

            $data['lat'] = isset($data['lat']) ? floatval($data['lat']) : 0.0;
            $data['lon'] = isset($data['lon']) ? floatval($data['lon']) : 0.0;
            $data['autosuggestvalue'] = ($data['category']['_id'] == 42 || $data['category']['_id'] == 45 || $data['category']['_id'] == 41 || $data['category']['_id'] == 46 || $data['category']['_id'] == 25 || count($data['multiaddress']) > 1) ? ((count($data['multiaddress']) > 1) ? ucwords($data['title'])." (".count($data['multiaddress'])." locations)" : ucwords($data['title'])) : ucwords($data['title'])." in ".ucwords($data['location']['name']);
            $postfields_data = array(
                'input'                         =>      (isset($data['title']) && $data['title'] != '') ? $data['title'] :"",
                'autosuggestvalue'              =>       $data['autosuggestvalue'],
                'inputv2'                       =>      $info_service_list,//(isset($data['info']['service']) && $data['info']['service'] != '') ? $data['info']['service'] : "",
                'inputv3'                       =>      (isset($data['offerings']) && !empty($data['offerings'])) ? array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))) : "",
                'inputv4'                       =>      (isset($data['facilities']) && !empty($data['facilities'])) ? array_map('strtolower',array_pluck($data['facilities'],'name')) : "",
                'inputloc1'                     =>      strtolower((isset($data['location']) && $data['location'] != '') ? $data['location']['name'] :""),
                'inputloc2'                     =>      ($cluster == '' ? '': strtolower($cluster)),
                'inputcat'                      =>      (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower',array_pluck($data['categorytags'],'name')) : "",
                'inputcat1'                     =>      strtolower($data['category']['name']),
                'city'                          =>      (isset($data['city']) && $data['city'] != '') ? $data['city']['name'] :"",
                'location'                      =>      (isset($data['location']) && $data['location'] != '') ? $data['location']['name'] :"",
                'type'                          =>      'vendor',
                'slug'                          =>      isset($data['slug']) ? $data['slug'] : '',
                'geolocation'                   =>      array('lat' => $data['lat'],'lon' => $data['lon']),
                'inputservicecat'               =>      '',
                'infrastructure_type'           =>      isset($data['business_type']) ? $data['business_type'] : ''
                );
            if($data['city_id'] == 10 || $data['city_id'] == 9){
                Log::info($postfields_data);
            }

            return $postfields_data;
        }
    }
    
    do{
        $indexdocs = Finder::active()
        ->where('status', '=', '1')
        ->whereNotIn('_id', Config::get('app.hide_from_search'))
        ->where('city_id', $city_id)
        ->where('flags.state', '!=', 'closed')
        ->where('flags.mfp', '!=', true)
        ->whereNotIn('categorytags',[37,38,42])
        ->with(array('country'=>function($query){$query->select('name');}))
        ->with(array('city'=>function($query){$query->select('name');}))
        ->with(array('category'=>function($query){$query->select('name','meta');}))
        ->with(array('location'=>function($query){$query->select('name','locationcluster_id' );}))
        ->with('categorytags')
        ->with('locationtags')
        ->with('offerings')
        ->with('facilities')
        // ->with('services')
        ->orderBy('_id')
         ->take($limit)->skip($i++ * $limit)
        // ->take(80000)->skip(0)
        ->timeout(400000000)
        ->get(array("title","country_id","country","city_id","city","category_id","category","location_id","location","categorytags","locationtags","offerings","facilities","slug","business_type","lat","lon"));

        
        
        $indexdocs = array_map('esParse',$indexdocs->toArray());

        $this->addToEsData($indexdocs);

    }while(!empty($indexdocs));
    
  }

  public function pushBrandOutlets($index_name){

    $brands = Finder::raw(function($collection){
      $aggregate = [];
      $match['$match']['brand_id']['$ne'] = '';
      $match['$match']['_id']['$nin'] = Config::get('app.hide_from_search');
      $aggregate[] = $match;
      $group = array(
          '$group' => array(
              '_id' => array(
                  'brand_id' => '$brand_id',
                  'city_id' => '$city_id',
                  'status' => '1'
              ),
              'count' => array(
                  '$sum' => 1
              )
          )
      );
      $aggregate[] = $group;
      return $collection->aggregate($aggregate);
    });

    $brands = $brands['result'];
    // return $cityData = Brand::lists('name','_id');
    $brandsD = Brand::where("status","1")->select(["name","_id","slug"])->get();
    // $brandsData = new stdClass();
    foreach($brandsD as $item){
      $brandsData[$item["id"]] =  array("name" => $item['name'], "slug"=>$item['slug']);
    }
    // return $brandsData;

    $cityData = City::lists('name','_id');

    // Get similar outlets in city.........
    foreach ($brands as $brand){
      if(isset($brand['_id']['brand_id']) && isset($brandsData[$brand['_id']['brand_id']]) && $brand['_id']['brand_id'] != '' && $brand['count'] > 1){
        $data = [
            'brand_id'    =>$brand['_id']['brand_id'],
            'brand_name'  =>$brandsData[$brand['_id']['brand_id']]["name"],
            'city_id'     =>$brand['_id']['city_id'],
            'slug'        =>$brandsData[$brand['_id']['brand_id']]["slug"],
            'city_name'   =>$cityData[$brand['_id']['city_id']],
            'outlets'     =>$brand['count']
        ];


        $postdata =  get_elastic_autosuggest_brandoutlets_doc($data);
        
        $this->addToEsData([$postdata]);

      }
    }
  }

  public function pushcategorylocations($index_name){

    Log::info('in categorylocations.......');

    foreach ($this->citylist as $city) {

      $categorytags = Findercategorytag::active()
          ->with('cities')
          ->where('cities', $city)
          ->whereNotIn('_id', array(22,30))
          ->get();

      $locationtags = Location::where('cities', $city)
          ->with('cities')
          ->get();

    
        foreach ($categorytags as $cat) {
          foreach ($locationtags as $loc) {
            $loc = $loc->toArray();
            $cluster = '';
            $string = '';
            switch ($cat['name']) {
              case 'yoga':
                $string = ucwords($cat['name']).' classes in '.ucwords($loc['name']);
                break;
              case 'zumba':
                $string = ucwords($cat['name']).' classes in '.ucwords($loc['name']);
                break;
              case 'dance':
                $string = ucwords($cat['name']).' classes in '.ucwords($loc['name']);
                break;
              default:
                $string = ucwords($cat['name']).' in '.ucwords($loc['name']);
                break;                              }
            Log::info("location ".$loc['name']);
            // Log::info($loc);
            $postdata =  get_elastic_autosuggest_catloc_doc($cat, $loc, $string, $loc['cities'][0]['name'], $cluster);
            
            $this->addToEsData([$postdata]);
            
          }
        }

    }

    Log::info("done findercategorylocations.......");

  }


  public function pushservicecategorylocations($index_name){

    Log::info("in servicecategorylocations.......");

    ini_set('max_execution_time', 300000);

    $indexed_docs = array();


    foreach ($this->citylist as $city) {


      $finders = Finder::where('city_id', (int) $city)->active()->lists('_id');


    $services = Service::raw(function($collection) use($city,$finders){

            $aggregate = [];
            $match['$match']['servicecategory_id']['$nin'] = array(111);
            $match['$match']['servicesubcategory_id']['$nin'] = array(112,1,2,4,5,19,27,65,82,83,85,111,112,114,115,123,124,138,147,152,153,154,155,170,180,184);
            $match['$match']['city_id'] = (int) $city;
            $match['$match']['status'] = "1";
            $match['$match']['finder_id']['$in'] = $finders;

            $aggregate[] = $match;

            $group = array(
              '$group' => array(
                '_id' => array(
                  'servicesubcategory_id' => '$servicesubcategory_id',
                  'location_id'	=> '$location_id'
                  )
                )
              );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

          });
          $services = array_fetch($services['result'],"_id.servicesubcategory_id");
          // return $services;
       $services = Service::active()
          ->whereIn('_id', $services)
          ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
          ->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
          ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
          ->get(array('city_id','city','servicesubcategory_id','subcategory','location','location_id'))
          ->toArray();


      $servicecategories = array();

      foreach($services as $service){

        isset($service['subcategory']) ? array_push($servicecategories,array(
            'city'=>$service['city']['name'],
            'servicecategory_name'=>$service['subcategory']['name'],
            'location_name'=>$service['location']['name']

        )) : null;
      }


      foreach ($servicecategories as $servicecategory) {

        $location = $servicecategory['location_name'];
        $name = $servicecategory['servicecategory_name'];
        $city = $servicecategory['city'];

        $cluster = '';
        $string = ucwords($name).' in '.ucwords($location);

        if(in_array($string,$indexed_docs)){
          continue;
        }

        array_push($indexed_docs, $string);
        $postdata =  get_elastic_autosuggest_servicecatloc_doc($name, $location, $string, $city, $cluster);

        $postfields_data = json_encode($postdata);
        $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);     //return $request;exit;
        echo "<br>    ---  servicecategorylocation.................".es_curl_request($request);



      }

    }

//    return $indexed_docs;

    Log::info("done servicecategorylocations.......");

  }


  public function pushcategorywithfacilities($index_name){
    $facilitieslist = array('Free Trial', 'Group Classes', 'Locker and Shower Facility', 'Parking', 'Personal Training', 'Sunday Open');

    foreach ($this->citylist as $key => $city) {
      $cityname = $this->citynames[strval($city)];

      $categorytags = Findercategorytag::active()
          ->with('cities')
          ->where('cities', $city)
          ->whereNotIn('_id', array(22,30))
          ->get();

      foreach ($categorytags as $cat) {
        foreach ($facilitieslist as $key1 => $fal) {
          $string = ucwords($cat['name']).' with '.ucwords($fal);
          $postdata =  get_elastic_autosuggest_catfac_doc($cat, $fal, $string, $cityname);
          $postfields_data = json_encode($postdata);
          $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
          echo "<br> ---  ".es_curl_request($request);
        }
      }
    }
  }

  public function pushcategoryoffering($index_name){
    foreach ($this->citylist as $city) {

      $cityname = $this->citynames[strval($city)];

      $categorytag_offerings = Findercategorytag::active()
          ->whereIn('cities',array($city))
          ->with('offerings')
          ->orderBy('ordering')
          ->whereNotIn('_id', array(22,30))
          ->get(array('_id','name','offering_header','slug','status','offerings'));

      foreach ($categorytag_offerings as $cat) {
        $catprioroff = isset($this->amenitiesrank[strtolower($cat['name'])]) ? $this->amenitiesrank[strtolower($cat['name'])] : null;
        $offerings = $cat['offerings'];
        switch ($cat['name']) {
          case 'gyms':
            foreach ($offerings as $off) {
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;

          case 'yoga':
            foreach ($offerings as $off) {
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($off['name']).'- '.ucwords($cat['name']).' classes'.' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;

          case 'zumba':
            foreach ($offerings as $off) {
              if($off['_id'] !== 334) {
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($off['name']).' classes - '.ucwords($cat['name']).' in '.ucwords($cityname);
                $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
              }
            }
            break;

          case 'cross functional training':
            foreach ($offerings as $off) {
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($off['name']).' - '.ucwords($cat['name']).' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;
          case 'dance':
            foreach ($offerings as $off) {
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($off['name']).' '.ucwords($cat['name']).' Classes'.' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;

          case 'fitness studios':
            foreach ($offerings as $off) {
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' - '.ucwords($off['name']).' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;

          case 'crossfit':
            foreach ($offerings as $off) {
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).'- '.ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;

          case 'pilates':
            foreach ($offerings as $off) {
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' '.ucwords($off['name']).' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;

          case 'mma & kickboxing':
            foreach ($offerings as $off) {
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' '.ucwords($off['name']).' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;

          default:
            foreach ($offerings as $off) {
              //$offeringrank = isset($catprioroff) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $offeringrank = 0;//intval($catprioroff[strtolower($off['name'])]);
              $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($cityname);
              $postdata = get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $cityname, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
            }
            break;
        }
      }
    }
  }

  public function pushcategoryofferinglocation($index_name){
    foreach ($this->citylist as $city) {

      $cityname = $this->citynames[strval($city)];

      $locationtags = Location::where('cities', $city)
          ->get();

    $categorytag_offerings = Findercategorytag::active()
          ->whereIn('cities',array($city))
          ->with('offerings')
          ->orderBy('ordering')
          ->whereNotIn('_id', array(22,30))
          //->whereIn('_id',array(32))
          ->get(array('_id','name','offering_header','slug','status','offerings'));
          
          foreach ($categorytag_offerings as $cat) {
              $catprioroff = isset($this->amenitiesrank[strtolower($cat['name'])]) ? $this->amenitiesrank[strtolower($cat['name'])] : null;
              $offerings = $cat['offerings'];
            foreach ($offerings as $off) {
                foreach ($locationtags as $loc) {
                    Log::info($loc);
                switch (strtolower($cat['name'])) {
                case 'gyms':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($loc['name']);
                break;

              case 'yoga':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($off['name']).'- '.ucwords($cat['name']).' classes in '.ucwords($loc['name']);
                
                break;

              case 'zumba':
                if($off['_id'] !== 334) {
                  $cluster = '';
                  $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                  $string = ucwords($off['name']).' classes in '.ucwords($loc['name']);
                  $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                  $postfields_data = json_encode($postdata);
                  $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                  Log::info( "<br> ---  ".es_curl_request($request));
                }
                break;

              case 'cross functional training':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                //$string = ucwords($cat['name']).' in '.ucwords($loc['name']).' with '.ucwords($off['name']);
                $string = ucwords($off['name']).' - '.ucwords($cat['name']).' in '.ucwords($loc['name']);
                
                break;

              case 'dance':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($off['name']).' - '.ucwords($cat['name']).' classes in '.ucwords($loc['name']);
                
                break;

              case 'fitness studios':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($loc['name']);
                
                break;

              case 'crossfit':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($loc['name']);
                
                break;

              case 'pilates':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($off['name']).' - '.ucwords($cat['name']).' in '.ucwords($loc['name']);
                
                break;

              case 'mma & kickboxing':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($cat['name']).' '.ucwords($off['name']).' in '.ucwords($loc['name']);
                
                break;

              default:
                $cluster = '';
                $offeringrank =  0;
                $string = ucwords($cat['name']).' in '.ucwords($loc['name']).' with '.ucwords($off['name']);
                
                break;

            }
            $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
            
            $this->addToEsData([$postdata]);
        }
    
        }
      }
    }
  }

  public function pushcategoryfacilitieslocation($index_name){
    $facilitieslist = array('Free Trial', 'Group Classes', 'Locker and Shower Facility', 'Parking', 'Personal Training', 'Sunday Open');

    foreach ($this->citylist as $key => $city) {
      $cityname = $this->citynames[strval($city)];

      $locationtags = Location::where('cities', $city)
          ->get();

      $categorytags = Findercategorytag::active()
          ->with('cities')
          ->where('cities', $city)
          ->whereNotIn('_id', array(22,30))
          ->get();

      foreach ($categorytags as $cat) {
        foreach ($facilitieslist as $key1 => $fal) {
          foreach ($locationtags as $loc) {
            $cluster ='';
            $string = ucwords($cat['name']).' in '.ucwords($loc['name']).' with '.ucwords($fal);
            $postdata =  get_elastic_autosuggest_catlocfac_doc($cat, $fal, $loc, $string, $cityname, $cluster);
            $postfields_data = json_encode($postdata);
            $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
            echo "<br> ---  ".es_curl_request($request);
          }
        }
      }
    }
  }

  public function pushcategorycity($index_name){

    Log::info("in categorycity.......");

    foreach ($this->citylist as $city) {
      $cityname = $this->citynames[strval($city)];
      $categorytags = Findercategorytag::active()
          ->with('cities')
          ->where('cities', $city)
          ->whereNotIn('_id', array(22,30))
          ->get();

      foreach ($categorytags as $cat) {
        switch ($cat['name']) {
          case 'yoga':
            $string = ucwords($cat['name']).' classes in '.ucwords($cityname);
            $postdata = get_elastic_autosuggest_catcity_doc($cat, $cityname, $string);
            $postfields_data = json_encode($postdata);
            $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
            $this->addToEsData([$postdata]);
            break;

          case 'zumba':
            $string = ucwords($cat['name']).' classes in '.ucwords($cityname);
            $postdata = get_elastic_autosuggest_catcity_doc($cat, $cityname, $string);
            $postfields_data = json_encode($postdata);
            $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
            $this->addToEsData([$postdata]);
            break;

          default:
            $string = ucwords($cat['name']).' in '.ucwords($cityname);
            $postdata = get_elastic_autosuggest_catcity_doc($cat, $cityname, $string);
            $postfields_data = json_encode($postdata);
            $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
            $this->addToEsData([$postdata]);
            break;
        }
      }
    }

    Log::info("done categorycity.......");
  }

  public function pushservicecategorycity($index_name){

    Log::info("in servicecategorycity.......");


    ini_set('max_execution_time', 300000);
//    $indexed_docs = array();

    foreach ($this->citylist as $city) {
      $cityname = $this->citynames[strval($city)];

      $finders = Finder::active()->where('city_id', (int) $city)->lists('_id');

      // $services = Service::active()
      //     ->whereNotIn('servicecategory_id', array(111))
      //     ->whereNotIn('servicesubcategory_id', array(112,1,2,4,5,19,27,65,82,83,85,111,112,114,115,123,124,138,147,152,153,154,155,170,180,184))
      //     ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
      //     ->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
      //     ->where('city_id', (int) $city)
      //     ->whereIn('finder_id', $finders)
      //     ->get(array('city_id','city','servicesubcategory_id','subcategory'))
      //     ->toArray();



      $services = Service::raw(function($collection) use($city,$finders){

            $aggregate = [];
            $match['$match']['servicecategory_id']['$nin'] = array(111);
            $match['$match']['servicesubcategory_id']['$nin'] = array(112,1,2,4,5,19,27,65,82,83,85,111,112,114,115,123,124,138,147,152,153,154,155,170,180,184);
            $match['$match']['city_id'] = (int) $city;
            $match['$match']['status'] = "1";
            $match['$match']['finder_id']['$in'] = $finders;

            $aggregate[] = $match;

            $group = array(
              '$group' => array(
                '_id' => array(
                  'servicesubcategory_id' => '$servicesubcategory_id',
                  'location_id'	=> '$location_id'
                  )
                )
              );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

          });
          $services = array_fetch($services['result'],"_id.servicesubcategory_id");
          // return $services;
       $services = Service::active()
          ->whereIn('_id', $services)
          ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
          ->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
          ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
          ->get(array('city_id','city','servicesubcategory_id','subcategory','location','location_id'))
          ->toArray();




      $servicecategories = array();

      foreach($services as $service){

        isset($service['subcategory']) ? array_push($servicecategories,array(
            'city'=>$service['city'],
            'servicecategory_name'=>$service['subcategory']['name'],
            'servicecategory_id'=>$service['subcategory']['_id']

        )) : null;
      }


      $servicecategories = array_unique(array_pluck($servicecategories,'servicecategory_name'));

      foreach ($servicecategories as $servicecategory) {

        $string = ucwords($servicecategory).' in '.ucwords($cityname);
//          array_push($indexed_docs,$string);
        $postdata = get_elastic_autosuggest_servicecatcity_doc($servicecategory, $cityname, $string);
        $postfields_data = json_encode($postdata);
        $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
        echo "<br> ---  ".es_curl_request($request);
      }
    }
//    return $indexed_docs;
    Log::info("done servicecategorycity.......");


  }

  public function buildglobalindex(){

    $url    = $this->build_elasticsearch_url."/_close";
    $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST',
    );

    echo es_curl_request($request);

    $body = '{
      "analysis": {
        "analyzer": {
          "synonymanalyzer":{
            "tokenizer": "standard",
            "filter": ["lowercase", "locationsynfilter"]
          },          
          "locationanalyzer":{
            "type": "custom",
            "tokenizer": "standard",
            "filter": ["standard", "locationsynfilter", "lowercase","delimiter-filter"],
            "tokenizer": "my_ngram_tokenizer"            
          },
          "search_analyzer": {
            "type": "custom",
            "filter": [
            "lowercase"
            ],
            "tokenizer": "standard"
          },
          "index_analyzerV1": {
            "type": "custom",
            "filter": [
            "standard",
            "lowercase"
            ],
            "tokenizer": "my_ngram_tokenizer"
          },
          "index_analyzerV2": {
            "type": "custom",
            "filter": [
            "standard",
            "lowercase",
            "ngram-filter"
            ],
            "tokenizer": "standard"
          },
          "input_analyzer": {
            "type": "custom",
            "tokenizer": "standard",
            "filter": [
            "standard",
            "lowercase",
            "delimiter-filter",
            "titlesynfilter"            
            ],
            "tokenizer": "input_ngram_tokenizer"
          }
        },
        "tokenizer": {
          "my_ngram_tokenizer": {
            "type": "edgeNGram",
            "min_gram": "3",
            "max_gram": "20"
          },
          "input_ngram_tokenizer": {
            "type": "edgeNGram",
            "min_gram": "2",
            "max_gram": "25"
          }
        },
        "filter": {
          "ngram-filter": {
            "type": "edgeNGram",
            "min_gram": "1",
            "max_gram": "20"
          },
          "stop-filter": {
            "type": "stop",
            "stopwords": "_english_",
            "ignore_case": "true"
          },
          "snowball-filter": {
            "type": "snowball",
            "language": "english"
          },
          "delimiter-filter": {
            "type": "word_delimiter",
            "preserve_original" : true
          },
          "locationsynfilter":{
            "type": "synonym",
            "synonyms" : [
            "lokhandwala,andheri west",
            "versova,andheri west",
            "oshiwara,andheri west",
            "chakala,andheri east",
            "jb nagar,andheri east",
            "marol,andheri east",
            "sakinaka,andheri east",
            "chandivali,powai",
            "vidyavihar,ghatkopar",
            "dharavi,sion",
            "chunabatti,sion",
            "deonar,chembur",
            "govandi,chembur",
            "anushakti nagar,chembur",
            "charkop,kandivali",
            "seven bungalows,andheri west",
            "opera house,grant road",
            "nana chowk,grant road",
            "shivaji park,dadar",
            "lalbaug,dadar",
            "walkeshwar,malabar hill",
            "tilak nagar,chembur",
            "vashi,navi mumbai",
            "sanpada,navi mumbai",
            "juinagar,navi mumbai",
            "nerul,navi mumbai",
            "seawoods,navi mumbai",
            "cbd belapur,navi mumbai",
            "kharghar,navi mumbai",
            "airoli,navi mumbai",
            "kamothe,navi mumbai",
            "kopar khairan,navi mumbai",
            "gamdevi,hughes road",
            "mazgaon,byculla",
            "navi mumbai,vashi",
            "navi mumbai,sanpada",
            "navi mumbai,juinagar",
            "navi mumbai,nerul",
            "navi mumbai,seawoods",
            "navi mumbai,cbd belapur",
            "navi mumbai,kharghar",
            "navi mumbai,airoli",
            "navi mumbai,kamothe",
            "navi mumbai,kopar khairan",
            "gamdevi,hughes road",
            "mazgaon,byculla"
            ]
          },
          "titlesynfilter":{
            "type": "synonym",
            "synonyms": [
            "golds , gold",
            "talwalkars, talwalkar",
			"powerhouse, hanman"
            ]
          }
        }
      }
    }';

    $index        = 'autosuggestor';;
    $url        = $this->build_elasticsearch_url."/_settings";
    $postfields_data  = json_encode(json_decode($body,true));

    $request = array(
        'url' => $url,
        'port' => $this->elasticsearch_port,
        'postfields' => $postfields_data,
        'method' => 'PUT',
    );

    echo es_curl_request($request);

    $url = $this->build_elasticsearch_url."/_open";
    $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST',
    );

    echo es_curl_request($request);

    $autosuggest_mappings = '{
      "_source": {
        "compress": "true"
      },
      "_all": {
        "enabled": "true"
      },
      "properties": {
        "input": {
          "type": "string",
          "index_analyzer": "input_analyzer",
          "search_analyzer": "search_analyzer"
        },
        "autosuggestvalue": {
          "type": "string",
          "index": "not_analyzed",
          "store": "yes"
        },
        "city": {
          "type": "string",
          "index": "not_analyzed",
          "store": "yes"
        },
        "location": {
          "type": "string",
          "index": "not_analyzed",
          "store": "yes"
        },
        "slug": {
          "type": "string",
          "index": "not_analyzed",
          "store": "yes"
        },
        "type": {
          "type": "string",
          "index": "not_analyzed",
          "store": "yes"
        },
        "inputloc1":{
          "type": "string",
          "index_analyzer": "locationanalyzer"
        },
        "inputv3":{
          "type": "string",
          "index_analyzer": "index_analyzerV2"
        },
        "inputv4":{
          "type": "string",
          "index_analyzer": "index_analyzerV2"
        },
        "inputcat1":{
          "type": "string",
          "index_analyzer": "index_analyzerV2"
        },
        "geolocation" : {
          "type" : "geo_point",
          "geohash": true,
          "geohash_prefix": true,
          "geohash_precision": 10
        }
      }
    }';

    $typeurl    = $this->build_elasticsearch_url."/autosuggestor/_mapping";

    $postfields_data  =   json_encode(json_decode($autosuggest_mappings,true));

    $request = array(
        'url' => $typeurl,
        'port' => $this->elasticsearch_port,
        'method' => 'PUT',
        'postfields' => $postfields_data
    );

    return es_curl_request($request);

  }

  public function updatelatlon(){
    $locationtags = Location::whereIn('cities', array(8))
        ->with('cities')
        ->take(20)->skip(0)
        ->get();
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
    foreach ($locationtags as $loc) {

      $locparams = urlencode($loc['name']);
      $locparams = $url.$locparams.'+'.$loc['city'];
      $ci = curl_init();
      curl_setopt($ci, CURLOPT_TIMEOUT, 200000000000000);
      curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
      curl_setopt($ci, CURLOPT_URL, $locparams);
      curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET');

      $response1 = (curl_exec($ci));
      $response = json_decode($response1, true);
      $location = $response['results'][0]['geometry']['location'];
      $locdata = array();
      array_set($locdata, 'lat', $location['lat']);
      array_set($locdata, 'lon', $location['lng']);
      $done = $loc->update($locdata);
      echo "<br> ---  ".$loc['_id'];

    }
  }

  public function pushofferingcity($index_name){

    $citylist = array(1,2,3,4,5,8,9,10);

    foreach ($citylist as $city) {
      $cityname = $this->citynames[strval($city)];
      $categorytag_offerings = Findercategorytag::active()
          ->whereIn('cities',array($city))
          ->whereNotIn('_id', array(22))
          ->with('offerings')
          ->orderBy('ordering')
          //->whereIn('_id',array(32))
          ->get(array('_id','name','offering_header','slug','status','offerings'))->toArray();

      foreach ($categorytag_offerings as $cat) {

        $offerings = $cat['offerings'];
        foreach ($offerings as $off) {
          switch ($cat['name']) {
            case 'gyms':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($cityname);
              $off = $this->citynames[strval($city)];

              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            case 'yoga':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($off['name']).'- '.ucwords($cat['name']).' classes in '.ucwords($cityname);
              $off = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            case 'zumba':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($off['name']).' classes in '.ucwords($cityname);          $off = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            case 'cross functional training':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              //$string = ucwords($cat['name']).' in '.ucwords($loc['name']).' with '.ucwords($off['name']);
              $string = ucwords($off['name']).' - '.ucwords($cat['name']).' in '.ucwords($cityname);
              $off = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            case 'dance':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;

              $string = ucwords($off['name']).'- '.ucwords($cat['name']).' classes in '.ucwords($cityname);
              $off = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            case 'fitness studios':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($cityname);
              $off = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            case 'crossfit':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($cityname);
              $off = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            case 'pilates':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' '.ucwords($off['name']).' in '.ucwords($cityname);
              $off = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            case 'mma & kickboxing':
              $cluster = '';
              $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
              $string = ucwords($cat['name']).' '.ucwords($off['name']).' in '.ucwords($cityname);
              $off = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $off, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;

            default:
              $cluster = '';
              $offeringrank =  0;
              $string = ucwords($cat['name']).' in '.ucwords($cityname).' with '.ucwords($off['name']);
              $cityname = $this->citynames[strval($city)];
              $postdata = get_elastic_autosuggest_catcityoffer_doc($cat, $cityname, $string, $cityname, $cluster, $offeringrank);
              $postfields_data = json_encode($postdata);
              $request = array('url' => $this->elasticsearch_url, 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
              echo "<br> ---  ".es_curl_request($request);
              break;
          }
        }
      }
    }
  }

  public function pushallfittnesslocation($index_name){

    Log::info("in allfittnesslocation.......");

    foreach ($this->citylist as $city) {
      $cityname = $this->citynames[strval($city)];
      $locations = Location::whereIn('cities', array($city))
          ->with('cities')
          ->get();
      foreach ($locations as $loc) {
        $string = 'All Fitness options in '.ucwords($loc['name']);
        $postdata = get_elastic_autosuggest_allfitness_doc($loc, $cityname, $string);
        
        $this->addToEsData([$postdata]);
      }
    }

    Log::info("done allfittnesslocation.......");

  }

    public function addToEsData($data = null, $push_to_es = false) {
        
        if(!empty($data)){
            
            $this->es_data = array_merge($this->es_data, $data);
            
        }

        if((count($this->es_data) && $push_to_es) || count($this->es_data) >= 500){

            for($i = 0; count($this->es_data) > 0; $i++){
                $post_string = "";
                foreach($this->es_data as $x){
                    $post_string = $post_string.json_encode(["index"=>new stdClass()])."\n".json_encode($x)."\n";
                }
                $request = array('url' => $this->elasticsearch_url_build.($this->index_name).'/autosuggestor/_bulk', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $post_string);     //return $request;exit;
                Log::info($this->i++);
                es_curl_request($request);
                Log::info('es_curl_request($request)');
                $this->es_data = [];
            }
        }

    }
}
