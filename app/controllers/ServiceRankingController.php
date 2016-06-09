ServiceRankkingController.php
<?php
/**
 * Controller to generate rankings for finder docs
 * Created by PhpStorm.
 * User: ajay
 * Date: 25/9/15
 * Time: 11:22 AM
 */

class ServiceRankingController extends \BaseController {

    protected  $gauss_decay     =   0.30;
    protected  $gauss_scale     =   5;
    protected  $gauss_offset    =   15;
    protected  $gauss_variance  =   0.0;

    protected  $linear_decay    =   0.70;
    protected  $linear_scale    =   1;

    //reference data for scoring
    protected  $views_min       =   0;
    protected  $views_max       =   0;

    protected  $trials_min      =   0;
    protected  $trials_max      =   0;

    protected  $reviews_min     =   0;
    protected  $reviews_max     =   0;

    protected $orders_min       =   0;
    protected $orders_max       =   0;

    protected $popularity_min   =   0;
    protected $popularity_max   =   0;

    protected $es_host = '';
    protected $es_port = '';

    public function __construct() {

        parent::__construct();
        $this->elasticsearch_default_url        =   "http://".Config::get('app.elasticsearch_host_new').":".Config::get('app.elasticsearch_port_new').'/'.Config::get('app.elasticsearch_default_index').'/';
        $this->elasticsearch_url                =   "http://".Config::get('app.elasticsearch_host_new').":".Config::get('app.elasticsearch_port_new').'/';
        $this->elasticsearch_host               =   Config::get('app.elasticsearch_host_new');
        $this->elasticsearch_port               =   Config::get('app.elasticsearch_port_new');
        $this->elasticsearch_default_index      =   Config::get('app.elasticsearch_default_index');
        $this->gauss_variance                   =   (-1)*(pow($this->gauss_scale, 2))/(2*log10($this->gauss_decay));
        $this->views_max                        =   Finder::active()->max('views');
        $this->views_min                        =   Finder::active()->min('views');
        $this->trials_max                       =   Finder::active()->max('trialsBooked');
        $this->trials_min                       =   Finder::active()->min('trialsBooked');
        $this->reviews_max                      =   Finder::active()->max('reviews');
        $this->reviews_min                      =   Finder::active()->min('reviews');
        $this->orders_max                       =   Finder::active()->max('orders30days');
        $this->orders_min                       =   Finder::active()->min('orders30days');
        $this->popularity_max                   =   20000;
        $this->popularity_min                   =   0;
        $this->es_host = Config::get('app.es_host');
        $this->es_port = Config::get('app.es_port');

    }

    public function IndexServiceRankMongo2Elastic($city, $index, $timestamp){
    // public function IndexServiceRankMongo2Elastic(){

        // $city =1 ; $index= 'fitternity_vip_trials2016-06-02'; $timestamp = '2016-06-02';
        ini_set('max_execution_time', 30000);

        $es_host = Config::get('app.es_host');
        $es_port = Config::get('app.es_port');

        $vip_trials_index = 'fitternity_vip_trials'.$timestamp;


        $items = Finder::with(array('country'=>function($query){$query->select('name');}))
        ->with(array('city'=>function($query){$query->select('name');}))
        ->with(array('category'=>function($query){$query->select('name','meta');}))
        ->with(array('location'=>function($query){$query->select('name','locationcluster_id' );}))
        ->with('categorytags')
        ->with('locationtags')
        ->with('offerings')
        ->with('facilities')                            
        ->active()
        ->orderBy('_id')                            
        ->where('city_id', intval($city))
        ->where('status', '=', '1')        
        ->take(5000)->skip(0)
        ->timeout(400000000)                        
        ->get(); 

        foreach ($items as $finderdocument) {    

            try{

                $finderdata = $finderdocument->toArray();
                $score = $this->generateRank($finderdocument);                
                $serviceitems = Service::with('category')
                ->with('subcategory')
                ->with(array('location'=>function($query){$query->select('name','locationcluster_id' );}))
                ->where('finder_id',$finderdata['_id'])                                    ->active()
                ->latest()
                ->get();

                if(isset($serviceitems) && (!empty($serviceitems))){ 

                    foreach ($serviceitems as $servicedocument) {                                 
                        $servicedata = $servicedocument->toArray();
                        $clusterid = '';             
                        if(!isset($servicedata['location']['locationcluster_id']))
                        {
                         continue;
                        }
                     else
                        {
                        $clusterid  = $servicedata['location']['locationcluster_id'];
                        }

                    $locationcluster = Locationcluster::active()->where('_id',$clusterid)->get();                
                    $locationcluster->toArray(); 

                    $postdata = get_elastic_service_documentv2($servicedata, $finderdata, $locationcluster[0]['name']);
                    $postdata_workoutsession_schedules = get_elastic_service_workoutsession_schedules($servicedata, $finderdata, $locationcluster[0]['name']);             


                    /******************Index each vip trial session**************/


                return json_encode($postdata_workoutsession_schedules);

                    if(isset($postdata_workoutsession_schedules)){
                       
                        foreach ($postdata_workoutsession_schedules as $workout_session) {
                            if($workout_session['workout_session_schedules_price'] === 0){
                                continue;
                            }
                            $workout_session['rank'] = $score;
                            $catval = evalBaseCategoryScore($finderdata['category_id']);
                            $workout_session['rankv1'] = $catval;
                            $workout_session['rankv2'] = $score + $catval;

                            $postfields_data_vip_trial = json_encode($workout_session);

                            $posturl_vip_trial = 'http://'.$es_host.':'.$es_port.'/'.$vip_trials_index.'/service';

                            $request_vip_trial = array('url' => $posturl_vip_trial, 'port' => $this->es_port, 'method' => 'POST', 'postfields' => $postfields_data_vip_trial);

                            echo "<br>    ---  ".es_curl_request($request_vip_trial);

                        }
                    }

                    /*****************Index each vip trial session***************/

                    $postdata['rank'] = $score;
                    $catval = evalBaseCategoryScore($finderdata['category_id']);
                    $postdata['rankv1'] = $catval;
                    $postdata['rankv2'] = $score + $catval;                      
                    $postfields_data = json_encode($postdata);                                     
                    $posturl = 'http://'.$es_host.':'.$es_port.'/'.$index.'/service/'.$servicedata['_id'];
                    $request = array('url' => $posturl, 'port' => $es_port, 'method' => 'PUT', 'postfields' => $postfields_data );
                    es_curl_request($request);
                }
            }
        }

        catch(Exception $e){
           Log::error($e);
       }
   }
}

public function generateRank($finderDocument = ''){

    $score = (8*($this->evalVendorType($finderDocument)) + 2*($this->evalProfileCompleteness($finderDocument)) + 3*($this->evalPopularity($finderDocument)))/13;
    return $score;

}

public function evalProfileCompleteness($finderDocument = ''){

    $aboutUs        = $finderDocument['info']['about'] != '' ? 1 : 0;
    $rateCard       = (isset($finderDocument['ratecards']) && !empty($finderDocument['ratecards']) ? 1 : 0);
    $schedule       = (isset($finderDocument['Schedule']) && !empty($finderDocument['Schedule']) ? 1 : 0);
    $galleryImage   = $finderDocument['total_photos'] != '0' ? 1 : 0;
    $coverImage     = $finderDocument['coverimage'] != '' ? 1 : 0;
    $updatedFreq    = $this->updatedFrequencyScore(Carbon::now()->diffInDays($finderDocument['updated_at']));
    $timings        = $finderDocument['info']['timing'] != ''? 1 : 0;

    $profileCompleteScore   =       ($aboutUs + $rateCard*3 + $schedule*3 + $galleryImage*3 + $coverImage*1 + $updatedFreq + $timings*2)/14;
    return $profileCompleteScore;
}

public function evalVendorType($finderDocument = ''){

    switch($finderDocument['finder_type'])
    {
        case 0:
        $val=0;
        return $val;

        case 1:
        $val=1;
        return $val;

        case 2:
        $val=0.7;
        return $val;

        case 3:
        $val=0.4;
        return $val;

        default:
        return 0;
    }
}

public function evalPopularity($finderDocument = ''){

    $reviews =  isset($finderDocument['reviews']) ? $finderDocument['reviews'] : 0;
    $trials  =  isset($finderDocument['trialsBooked']) ? $finderDocument['trialsBooked'] : 0;
    $orders  =  isset($finderDocument['orders30days']) ? $finderDocument['orders30days'] : 0;
    $popularity = intval($finderDocument['popularity']);

    $popularityScore  =  (($this->normalizingFunction($this->reviews_min, $this->reviews_max, intval($reviews))) + ($this->normalizingFunction($this->trials_min, $this->trials_max, intval($trials)))
        + 2*($this->normalizingFunction($this->orders_min, $this->orders_max, intval($orders))) + 2*($this->normalizingFunction($this->popularity_min, $this->popularity_max, intval($popularity))))/6;
    return $popularityScore;
}
    //tested
public function updatedFrequencyScore($LastUpdateDate = 0){

    $score  =  exp((-1)*pow(($LastUpdateDate - $this->gauss_offset) > 0 ? ($LastUpdateDate - $this->gauss_offset) : 0 , 2)/(2*$this->gauss_variance));
    return $score;

}

public function normalizingFunction($Emin=0, $Emax=0, $Eval){
    if($Emax == 0 || $Emax==$Emin){
        return 0;
    }

    $score  =  ($Eval-$Emin)/($Emax-$Emin);
    return $score;
}

public function embedTrialsBooked(){

    $items = Finder::active()->orderBy('_id')->take(1000)->skip(4400)->get();        
    foreach($items as $item)
    {
        $Reviews = Review::active()->where('finder_id', $item['_id'])->where('created_at', '>', new DateTime('-30 days'))->get()->count();
        $Trials  = Booktrial::where('finder_id', $item['_id'])->where('created_at', '>', new DateTime('-30 days'))->get()->count();
        $Orders  = Order::where('finder_id', $item['_id'])->where('created_at', '>', new DateTime('-30 days'))->where('status', '1')->get()->count();

        $finderdata = array();
        array_set($finderdata,'reviews', $Reviews);
        array_set($finderdata,'trialsBooked', $Trials);
        array_set($finderdata, 'orders30days', $Orders);
        $resp = $item->update($finderdata);
        echo $resp;
    }
}

public function getFinderCategory(){
        //$finderid = array(1259);
        //$items = Finder::active()->with(array('category'=>function($query){$query->select('name','meta');}))->orderBy('_id')->whereIn('_id', $finderid)->get();

    $next_date= date('Y-m-d', strtotime(Carbon::now(). ' - 30 days'));
    return $next_date;
        //return $items;
}

public function pushdocument($posturl, $postfields_data){

    $request = array('url' => $posturl, 'port' => Config::get('elasticsearch.elasticsearch_port_new'), 'method' => 'PUT', 'postfields' => $postfields_data );

    echo "<br>$posturl    ---  ".es_curl_request($request);
}

public function RollingBuildServiceIndex(){


    $port = Config::get('app.es_port');
    $host = Config::get('app.es_host');

    $url = 'http://'.$host.':'.$port.'/';
   
    $timestamp =  date('Y-m-d');

    $index = 'fitternity_service'.$timestamp;
    $index_build_url = $url.$index;

    $index_vip_trial = 'fitternity_vip_trials'.$timestamp;
    $index_build_url_vip_trial = $url.$index_vip_trial;

    $request = array(
        'url' =>  $index_build_url,
        'port' => $port,
        'method' => 'POST'
        );

    $request_vip_trial = array(
        'url' =>  $index_build_url_vip_trial,
        'port' => $port,
        'method' => 'POST'
        );

    echo es_curl_request($request);
    echo es_curl_request($request_vip_trial);  

    sleep(5);

    $request = array(
        'url' =>   $index_build_url.'/_close',
        'port' => $port,
        'method' => 'POST'
        );

    $request_vip_trial = array(
        'url' =>   $index_build_url_vip_trial.'/_close',
        'port' => $port,
        'method' => 'POST'
        );

    echo es_curl_request($request);  
    echo es_curl_request($request_vip_trial);  
    sleep(5);

    $settings = '{
        "analysis" : {
            "analyzer":{
                "simple_analyzer": {
                    "type": "custom",
                    "tokenizer": "standard",
                    "filter": ["standard","lowercase","asciifolding","filter_stop","filter_worddelimiter"]
                },
                "snowball_analyzer": {
                    "type": "custom",
                    "tokenizer": "standard",
                    "filter": ["standard","lowercase","asciifolding","filter_stop","filter_worddelimiter"]
                },
                "shingle_analyzer": {
                    "type": "custom",
                    "tokenizer": "standard",
                    "filter": ["standard","lowercase","asciifolding","filter_stop","filter_shingle","filter_worddelimiter","filter_snowball"]
                },
                "autocomplete_analyzer": {
                    "type": "custom",
                    "tokenizer": "standard",
                    "filter": ["standard","lowercase","asciifolding","filter_stop","filter_edgengram","filter_worddelimiter","filter_snowball"]
                },
                "title_analyzer" :{
                    "type" : "custom",
                    "tokenizer" : "input_ngram_tokenizer",
                    "filter" : ["standard","lowercase","delimiter-filter", "titlesynfilter"]
                }
            },
            "filter": {
                "filter_stop": {
                    "type":       "stop",
                    "stopwords":  "_english_",
                    "ignore_case" : true
                },
                "filter_shingle": {
                    "type": "shingle",
                    "max_shingle_size": 2,
                    "min_shingle_size": 2,
                    "output_unigrams": true
                },
                "filter_snowball": {
                    "type": "snowball",
                    "language" : "english"
                },
                "filter_stemmer": {
                    "type": "porter_stem",
                    "language": "English"
                },
                "filter_ngram": {
                    "type": "nGram",
                    "min_gram": 3,
                    "max_gram": 15
                },
                "filter_edgengram": {
                    "type": "edgeNGram",
                    "min_gram": 2,
                    "max_gram": 15
                },
                "filter_worddelimiter": {
                    "type": "word_delimiter"
                },
                "delimiter-filter": {
                    "type": "word_delimiter",
                    "preserve_original" : true
                },
                "titlesynfilter":{
                    "type": "synonym",
                    "synonyms" : [
                    "golds, gold",
                    "talwalkars,talwalkar",
                    "yfc,your fitness club"
                    ]
                }
            },
            "tokenizer": {
                "haystack_ngram_tokenizer": {
                    "type": "nGram",
                    "min_gram": 3,
                    "max_gram": 15
                },
                "haystack_edgengram_tokenizer": {
                    "type": "edgeNGram",
                    "min_gram": 2,
                    "max_gram": 15,
                    "side": "front"
                },
                "input_ngram_tokenizer" : {
                    "type": "edgeNGram",
                    "min_gram": "2",
                    "max_gram": "20"
                }
            }
        }
    }';

    $postfields_data    =   json_encode(json_decode($settings,true));

    $request = array(
        'url' => $index_build_url.'/_settings',
        'port' => $port,
        'postfields' => $postfields_data,
        'method' => 'PUT'
        );

    $request_vip_trial = array(
        'url' => $index_build_url_vip_trial.'/_settings',
        'port' => $port,
        'postfields' => $postfields_data,
        'method' => 'PUT'
        );

    echo es_curl_request($request); 
    echo es_curl_request($request_vip_trial); 
    sleep(5);

    $request = array(
        'url' =>  $index_build_url.'/_open',
        'port' => $port,
        'method' => 'POST'
        );

    $request_vip_trial = array(
        'url' =>  $index_build_url_vip_trial.'/_open',
        'port' => $port,
        'method' => 'POST'
        );

    echo es_curl_request($request); 
    echo es_curl_request($request_vip_trial); 
    sleep(5);

    $serivcesmapping = '{
        "service" :{
            "_source" : {"enabled" : true },
            "properties":{
                "name" : {"type" : "string", "index" : "not_analyzed"},
                "name_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "findername" : {"type" : "string", "index" : "not_analyzed"},
                "findername_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "finderslug" : {"type" : "string", "index" : "not_analyzed"},
                "finderslug_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "category" : {"type" : "string","index" : "not_analyzed"},
                "category_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "subcategory" : {"type" : "string","index" : "not_analyzed"},
                "subcategory_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "location" : {"type" : "string", "index" : "not_analyzed"},
                "location_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "session_type" : {"type" : "string","index" : "not_analyzed"},
                "workout_intensity" : {"type" : "string","index" : "not_analyzed"},
                "workout_tags" : {"type" : "string", "index" : "not_analyzed"},
                "city" : {"type" : "string","index" : "not_analyzed"},
                "geolocation" : {"type" : "geo_point","geohash": true,"geohash_prefix": true,"geohash_precision": 10},
                "ratecards": {
                    "properties": {
                        "duration" : {"type" : "string", "index" : "not_analyzed"},
                        "price" : {"type" : "integer", "index" : "not_analyzed"},
                        "special_price" : {"type" : "integer", "index" : "not_analyzed"}
                    },
                    "type": "nested"
                },
                "workoutsessionschedules": {
                    "properties": {
                        "weekday" : {"type" : "string", "index" : "not_analyzed"},
                        "start_time" : {"type" : "string", "index" : "not_analyzed"},
                        "start_time_24_hour_format" : {"type" : "float", "index" : "not_analyzed"},
                        "price" : {"type" : "integer", "index" : "not_analyzed"}
                    },
                    "type": "nested"
                }
            }
        }
    }';

    $serivcesmapping_vip_trial = '{
        "service" :{
            "_source" : {"enabled" : true },
            "properties":{
                "name" : {"type" : "string", "index" : "not_analyzed"},
                "name_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "findername" : {"type" : "string", "index" : "not_analyzed"},
                "findername_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "finderslug" : {"type" : "string", "index" : "not_analyzed"},
                "finderslug_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "category" : {"type" : "string","index" : "not_analyzed"},
                "category_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "subcategory" : {"type" : "string","index" : "not_analyzed"},
                "subcategory_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "location" : {"type" : "string", "index" : "not_analyzed"},
                "location_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                "session_type" : {"type" : "string","index" : "not_analyzed"},
                "workout_intensity" : {"type" : "string","index" : "not_analyzed"},
                "workout_tags" : {"type" : "string", "index" : "not_analyzed"},
                "city" : {"type" : "string","index" : "not_analyzed"},
                "locationcluster" : {"type" : "string", "index": "not_analyzed"},
                "geolocation" : {"type" : "geo_point","geohash": true,"geohash_prefix": true,"geohash_precision": 10},
                "workout_session_schedules_end_time_24_hrs" : {"type" : "float", "index": "not_analyzed"},
                "workout_session_schedules_start_time_24_hrs" : {"type" : "float", "index": "not_analyzed"}       
            }
        }
    }';


    $postfields_data    =   json_encode(json_decode($serivcesmapping,true));

    $postfields_data_vip_trial    =   json_encode(json_decode($serivcesmapping_vip_trial,true));


    $request = array(
        'url' => $index_build_url.'/service/_mapping',
        'port' => $port,
        'method' => 'PUT',
        'postfields' => $postfields_data
        );      

    $request_vip_trial = array(
        'url' => $index_build_url_vip_trial.'/service/_mapping',
        'port' => $port,
        'method' => 'PUT',
        'postfields' => $postfields_data_vip_trial
        );      

    echo es_curl_request($request);
    echo es_curl_request($request_vip_trial);
    sleep(5);

    $city_list = array(1,2,3,4,8);

    foreach ($city_list as $city) {
       
           
        $this->IndexServiceRankMongo2Elastic($city, $index, $timestamp);
    }

    $alias_request = '{
        "actions": [{
            "remove": {
                "index": "*",
                "alias": "fitternity_service"
            }
        },
        {
            "add": {
                "index": "'.$index.'",
                "alias": "fitternity_service"
            }
        }]
    }';

    $alias_request_vip_trial = '{
        "actions": [{
            "remove": {
                "index": "*",
                "alias": "fitternity_vip_trials"
            }
        },
        {
            "add": {
                "index": "'.$index_vip_trial.'",
                "alias": "fitternity_vip_trials"
            }
        }]
    }';

    $url        =   $url."_aliases";

    $payload =  json_encode(json_decode($alias_request,true)); 
    $request = array(
        'url' => $url,
        'port' => $port,
        'method' => 'POST',
        'postfields' => $payload
        );      
    echo es_curl_request($request);

    $payload_vip_trial =  json_encode(json_decode($alias_request_vip_trial,true)); 
    $request_vip_trial = array(
        'url' => $url,
        'port' => $port,
        'method' => 'POST',
        'postfields' => $payload_vip_trial
        );  

    echo es_curl_request($request_vip_trial);

}
}
