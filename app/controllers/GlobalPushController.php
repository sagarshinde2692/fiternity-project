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
  protected $citylist = array(1,2,3,4,8,9);
  protected $citynames = array('1' => 'mumbai','2' => 'pune', '3' => 'bangalore', '4' => 'delhi', '8' => 'gurgaon', '9' => 'noida');
  protected $primaryfiltersrank = array('free trial' => '10', 'group classes' => '8', 'parking' => '6', 'sunday open' => '4', 'locker and shower facility' => '2');

  protected $amenitiesrank = array('gyms' => array('24 hour facility' => '6', 'free wifi' => '5', 'juice bar' => '4', 'steam and sauna' => '3', 'stretching area' => '2', 'swimming pool' => '1'), 'yoga' => array('power yoga' => '9', 'iyengar yoga' => '8', 'ashtanga yoga' => '7', 'hatha yoga' => '6', 'aerial yoga' => '5', 'vinyassa yoga' => '4','hot yoga' => '3', 'post natal yoga' => '2', 'prenatal yoga' => '1'), 'zumba' => array('zumba classes' => '2', 'aqua zumba classes' => '1'), 'cross functional training' => array('les mills' => '7', 'calisthenics' => '6', 'cross training' => '5', 'trx training' => '4', 'combine training' => '3', 'group x training' => '2', 'trampoline workout' => '1'), 'crossfit' => array('open box' => '7', 'tires & ropes' => '6', 'olympic lifting' => '5', 'group training' => '4', 'personal training' => '3', 'gymnastic routines' => '2', 'kettle bell training' => '1'), 'pilates' => array('mat pilates' => '2', 'reformer pilates or stott pilates' => '1'), 'mma & kickboxing' => array('mixed martial arts' => '12', 'karate' => '11', 'kick boxing' => '10', 'judo' => '9', 'jujitsu' => '8', 'karv maga' => '7', 'kung fu' => '6', 'muay thai' => '5', 'taekwondo' => '4', 'tai chi' => '3', 'capoeira' => '2', 'kalaripayattu' => '1'), 'dance' => array('bollywood' =>'16', 'hip hop' => '15', 'salsa' => '14', 'free style' => '13', 'contemporary' => '12', 'jazz' => '11', 'jive' => '10', 'belly dancing' => '9', 'cha cha cha' => '8', 'kathak' => '7', 'b boying' => '6', 'bharatanatyam' => '5', 'ballroom' => '4', 'locking and popping' => '3', 'ballet' => '2', 'waltz' => '1'));

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
    $index_name = $this->name.$timestamp.'-'.random_numbers(3);

    /*
   creating new index appended with timestamp
    */

    $url = $this->elasticsearch_url_build."$index_name";
    $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST',
    );

    echo es_curl_request($request);
    sleep(5);

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

    echo es_curl_request($request);
    sleep(5);

    $settings = '{
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
            "categoryanalyzer":{
              "type": "custom",
              "tokenizer": "standard",
              "filter": ["standard", "categorysynfilter", "lowercase","delimiter-filter"],
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
            "categorysynfilter":{
              "type": "synonym",
              "synonyms" : [
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
            "titlesynfilter":{
                "type": "synonym",
                "synonyms": [
                "golds , gold , gold\'s",
                "talwalkars, talwalkar"
                ]
              } 
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

    echo es_curl_request($request);
    sleep(5);

    /*
    open newly created index
    */

    $url = $this->elasticsearch_url_build."$index_name/_open";
    $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST'
    );

    echo es_curl_request($request);
    sleep(4);

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
    echo es_curl_request($request);
    sleep(5);

    /*

    Fill ES cluster will data

    */


    $this->pushBrandOutlets($index_name);
    $this->pushcategorylocations($index_name);
    $this->pushcategorycity($index_name);
    $this->pushallfittnesslocation($index_name);
    $this->pushservicecategorylocations($index_name);
    $this->pushservicecategorycity($index_name);
    foreach ($this->citylist as $key => $city) {
      $this->pushfinders($index_name, $city);
    }


//        $this->pushcategorywithfacilities($index_name);
//        $this->pushcategoryoffering($index_name);
//        $this->pushcategoryofferinglocation($index_name);
//        $this->pushcategoryfacilitieslocation($index_name);
//        $this->pushofferingcity($index_name);

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

  }


  public function pushfinders($index_name, $city_id){

    ini_set('max_execution_time', 30000);

    $indexdocs = Finder::active()->with(array('country'=>function($query){$query->select('name');}))
        ->with(array('city'=>function($query){$query->select('name');}))
        ->with(array('category'=>function($query){$query->select('name','meta');}))
        ->with(array('location'=>function($query){$query->select('name','locationcluster_id' );}))
        ->with('categorytags')
        ->with('locationtags')
        ->with('offerings')
        ->with('facilities')
        ->with('services')
        ->orderBy('_id')
        ->where('city_id', $city_id)
//          ->take(1000)->skip(0)
        ->take(50000)->skip(0)
        ->timeout(400000000)
        ->get();

//      var_dump($indexdocs);
//      exit();

//      Log::info('I have $indexdocs.......');


    foreach ($indexdocs as $data) {

//        Log::info('Processing $indexdocs $data.......');


      //Exclude exceptional Finders.........
      $exclude_finders = Config::get('elasticsearch.exclude_finders');
      $finder_id = intval($data['_id']);
      if(in_array($finder_id, $exclude_finders)){
        continue;
      }

      $clusterid = '';

      if(!isset($data['location']['locationcluster_id']))
      {
        continue;
      }

      else
      {
        $clusterid  = $data['location']['locationcluster_id'];
      }

      $locationcluster = Locationcluster::active()->where('_id',$clusterid)->get();
      $locationcluster->toArray();
      $cluster = (isset($locationcluster[0]) && isset($locationcluster[0]['name'])) ? $locationcluster[0]['name'] : '';

//        Log::info('$data for autosuggest.......');


      $postdata = get_elastic_autosuggest_doc($data, $cluster);

      $postfields_data = json_encode($postdata);

//        Log::info('$postfields_data for autosuggest.......');


      // $postfields_data    =   json_encode(json_decode($mapping,true));

      $request = array(
          'url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/'.$data['_id'],
          'port' => $this->elasticsearch_port,
          'method' => 'PUT',
          'postfields' => $postfields_data
      );

      $user = es_curl_request($request);
//        Log::info('finder entry in index........');

//        Log::info('done vendors of city ',array($city_id));

    }
  }

  public function pushBrandOutlets($index_name){

    $brands = Finder::raw(function($collection){
      $aggregate = [];
      $match['$match']['brand_id']['$ne'] = '';
      $aggregate[] = $match;
      $group = array(
          '$group' => array(
              '_id' => array(
                  'brand_id' => '$brand_id',
                  'city_id' => '$city_id'
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
    $brandsData = Brand::lists('name','_id');
    $cityData = City::lists('name','_id');

    // Get similar outlets in city.........
    foreach ($brands as $brand){
      if(isset($brand['_id']['brand_id']) && $brand['_id']['brand_id'] != '' && $brand['count'] > 1){
        $data = [
            'brand_id'    =>$brand['_id']['brand_id'],
            'brand_name'  =>$brandsData[$brand['_id']['brand_id']],
            'city_id'     =>$brand['_id']['city_id'],
            'city_name'   =>$cityData[$brand['_id']['city_id']],
            'outlets'     =>$brand['count']
        ];


        $postdata =  get_elastic_autosuggest_brandoutlets_doc($data);
        $postfields_data = json_encode($postdata);

        $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);     //return $request;exit;
        echo "<br>    ---  ".es_curl_request($request);

      }
    }
  }

  public function pushcategorylocations($index_name){

    Log::info('in categorylocations.......');


    foreach ($this->citylist as $city) {

      $categorytags = Findercategorytag::active()
          ->with('cities')
          ->where('cities', $city)
          ->whereNotIn('_id', array(22))
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

          $postdata =  get_elastic_autosuggest_catloc_doc($cat, $loc, $string, $loc['cities'][0]['name'], $cluster);
          $postfields_data = json_encode($postdata);

          $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);     //return $request;exit;
          echo "<br>    ---  ".es_curl_request($request);
        }
      }

    }

    Log::info("done servicecategorylocations.......");

  }


  public function pushservicecategorylocations($index_name){

    Log::info("in servicecategorylocations.......");

    ini_set('max_execution_time', 300000);

    $indexed_docs = array();


    foreach ($this->citylist as $city) {


      $finders = Finder::where('city_id', (int) $city)->active()->lists('_id');

      $services = Service::active()
          ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
          ->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
          ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
          ->where('city_id', (int) $city)
          ->whereIn('finder_id', $finders)
          ->get(array('city_id','city','servicesubcategory_id','subcategory','location','location_id'))
          ->toArray();

//        var_dump($services);
//        exit();


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
          ->whereNotIn('_id', array(22))
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
          ->whereNotIn('_id', array(22))
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
          ->whereNotIn('_id', array(22))
          //->whereIn('_id',array(32))
          ->get(array('_id','name','offering_header','slug','status','offerings'));

      foreach ($categorytag_offerings as $cat) {
        $catprioroff = isset($this->amenitiesrank[strtolower($cat['name'])]) ? $this->amenitiesrank[strtolower($cat['name'])] : null;
        $offerings = $cat['offerings'];
        foreach ($offerings as $off) {
          foreach ($locationtags as $loc) {
            switch (strtolower($cat['name'])) {
              case 'gyms':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($loc['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;

              case 'yoga':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($off['name']).'- '.ucwords($cat['name']).' classes in '.ucwords($loc['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;

              case 'zumba':
                if($off['_id'] !== 334) {
                  $cluster = '';
                  $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                  $string = ucwords($off['name']).' classes in '.ucwords($loc['name']);
                  $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                  $postfields_data = json_encode($postdata);
                  $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                  echo "<br> ---  ".es_curl_request($request);
                }
                break;

              case 'cross functional training':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                //$string = ucwords($cat['name']).' in '.ucwords($loc['name']).' with '.ucwords($off['name']);
                $string = ucwords($off['name']).' - '.ucwords($cat['name']).' in '.ucwords($loc['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;

              case 'dance':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($off['name']).'- '.ucwords($cat['name']).' classes in '.ucwords($loc['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;

              case 'fitness studios':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($loc['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;

              case 'crossfit':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($cat['name']).' with '.ucwords($off['name']).' in '.ucwords($loc['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;

              case 'pilates':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($off['name']).'- '.ucwords($cat['name']).' in '.ucwords($loc['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;

              case 'mma & kickboxing':
                $cluster = '';
                $offeringrank = (isset($catprioroff)&&(isset($catprioroff[strtolower($off['name'])]))) ? intval($catprioroff[strtolower($off['name'])]) : 0;
                $string = ucwords($cat['name']).' '.ucwords($off['name']).' in '.ucwords($loc['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;

              default:
                $cluster = '';
                $offeringrank =  0;
                $string = ucwords($cat['name']).' in '.ucwords($loc['name']).' with '.ucwords($off['name']);
                $postdata = get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $cityname, $cluster, $offeringrank);
                $postfields_data = json_encode($postdata);
                $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
                echo "<br> ---  ".es_curl_request($request);
                break;
            }
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
          ->whereNotIn('_id', array(22))
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
          ->whereNotIn('_id', array(22))
          ->get();

      foreach ($categorytags as $cat) {
        switch ($cat['name']) {
          case 'yoga':
            $string = ucwords($cat['name']).' classes in '.ucwords($cityname);
            $postdata = get_elastic_autosuggest_catcity_doc($cat, $cityname, $string);
            $postfields_data = json_encode($postdata);
            $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
            echo "<br> ---  ".es_curl_request($request);
            break;

          case 'zumba':
            $string = ucwords($cat['name']).' classes in '.ucwords($cityname);
            $postdata = get_elastic_autosuggest_catcity_doc($cat, $cityname, $string);
            $postfields_data = json_encode($postdata);
            $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
            echo "<br> ---  ".es_curl_request($request);
            break;

          default:
            $string = ucwords($cat['name']).' in '.ucwords($cityname);
            $postdata = get_elastic_autosuggest_catcity_doc($cat, $cityname, $string);
            $postfields_data = json_encode($postdata);
            $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
            echo "<br> ---  ".es_curl_request($request);
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

      $services = Service::active()
          ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
          ->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
          ->where('city_id', (int) $city)
          ->whereIn('finder_id', $finders)
          ->get(array('city_id','city','servicesubcategory_id','subcategory'))
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
            "talwalkars, talwalkar"
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

    $citylist = array(1,2,3,4,8,9);

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
        $postfields_data = json_encode($postdata);
        $request = array('url' => $this->elasticsearch_url_build.$index_name.'/autosuggestor/', 'port' => $this->elasticsearch_port, 'method' => 'POST', 'postfields' => $postfields_data);
        echo "<br> ---  ".es_curl_request($request);
      }
    }

    Log::info("done allfittnesslocation.......");

  }
}
