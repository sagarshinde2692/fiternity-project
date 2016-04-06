<?php
/**
 * Controller to generate rankings for finder docs
 * Created by PhpStorm.
 * User: ajay
 * Date: 14/7/15
 * Time: 11:22 AM
 */

class RankingController extends \BaseController {

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

    protected $name = 'fitternity';

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
        $this->city_list                        =   array(1,2,3,4,8);
    }

    /*

    Rolling build functions to maintain new replicated index

    */

    public function RollingBuildFinderSearchIndex(){

        /*
        appending date to rolling builds for new index        
        */
        $timestamp =  date('Y-m-d');
        $index_name = $this->name.$timestamp;

        /*
       creating new index appended with timestamp
        */

       $url = $this->elasticsearch_url."$index_name";
       $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST',
        );

       echo es_curl_request($request); 
       sleep(5);

        /*
      closing newly created index      
        */
      $url = $this->elasticsearch_url."$index_name/_close";
      $request = array(
        'url' =>  $url,
        'port' => $this->elasticsearch_port,
        'method' => 'POST',
        );

      echo es_curl_request($request);   
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

        /*
        add setting to new index       
        */
        $url                = $this->elasticsearch_url."$index_name/_settings";       
        $postfields_data    = json_encode(json_decode($settings,true));       
        $request = array(
            'url' => $url,
            'port' => $this->elasticsearch_port,
            'postfields' => $postfields_data,
            'method' => 'PUT',
            );

        echo es_curl_request($request); 
        sleep(5);

        /*
        open newly created index  
        */
        $url = $this->elasticsearch_url."$index_name/_open";
        $request = array(
            'url' =>  $url,
            'port' => $this->elasticsearch_port,
            'method' => 'POST',
            );

        echo es_curl_request($request);   
        sleep(5);

        $mapping = '{
            "finder" :{
                "_source" : {"enabled" : true },
                "properties":{
                    "title" : {"type" : "string", "index" : "not_analyzed"},
                    "title_snow":   { "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                    "category" : {"type" : "string","index" : "not_analyzed"},
                    "category_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                    "location" : {"type" : "string", "index" : "not_analyzed"},
                    "location_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                    "categorytags" : {"type" : "string","index" : "not_analyzed"},
                    "categorytags_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                    "locationtags" : {"type" : "string","index" : "not_analyzed"},
                    "locationtags_snow" : {"type" : "string", "type": "string", "search_analyzer": "simple_analyzer", "index_analyzer": "snowball_analyzer" },
                    "offerings" : {"type" : "string", "index" : "not_analyzed"},
                    "facilities" : {"type" : "string", "index" : "not_analyzed"},
                    "geolocation" : {"type" : "geo_point","geohash": true,"geohash_prefix": true,"geohash_precision": 10},
                    "average_rating" : {"type" : "float", "index" : "not_analyzed"},
                    "locationcluster" : {"type" : "string", "index" : "not_analyzed"},
                    "trials": {
                        "properties": {
                            "day" : {"type" : "string", "index" : "not_analyzed"},
                            "start" : {"type" : "integer", "index" : "not_analyzed"},
                            "end" : {"type" : "integer", "index" : "not_analyzed"}
                        },
                        "type": "nested"
                    }
                }
            }
        }';

        /*
        add mappings to new index       
        */

        $postfields_data    =   json_encode(json_decode($mapping,true));       
        $url        =   $this->elasticsearch_url."$index_name/finder/_mapping"; 
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
        foreach ($this->city_list as $key => $city) {
            $this->IndexRankMongo2Elastic($index_name, $city);
        }
       
        /*    
        point the aliases for the cluster to new created index    
        */

        $alias_request = '{
            "actions": [ {
                "remove": {
                    "index": "*",
                    "alias": "fitternity_finder"
                }
            },
            {
                "add": {
                    "index": "'.$index_name.'",
                    "alias": "fitternity_finder"
                }
            }]
        }';

        $url        =   $this->elasticsearch_url."_aliases"; 
        $payload =  json_encode(json_decode($alias_request,true)); 
        $request = array(
            'url' => $url,
            'port' => $this->elasticsearch_port,
            'method' => 'POST',
            'postfields' => $payload
            );      
        echo es_curl_request($request);
    }

   
    public function IndexRankMongo2Elastic($index_name, $city_id){

        //$finderids1  =   array(1020,1041,1042,1259,1413,1484,1671,1873,45,624,1695,1720,1738,1696);
         ini_set('max_execution_time', 30000);
        $citykist      =    array(1,2,3,4,8);
        $items = Finder::with(array('country'=>function($query){$query->select('name');}))
        ->with(array('city'=>function($query){$query->select('name');}))
        ->with(array('category'=>function($query){$query->select('name','meta');}))
        ->with(array('location'=>function($query){$query->select('name','locationcluster_id' );}))
        ->with('categorytags')
        ->with('locationtags')
        ->with('offerings')
        ->with('facilities')
        ->with('services')
        ->with(array('ozonetelno'=>function($query){$query->select('phone_number','extension')->where('status','=','1');}))
        ->active()
        ->orderBy('_id')
                            //->whereIn('category_id', array(42,45))
                            //->whereIn('_id', array(579))
        ->where('city_id', $city_id)
        ->where('status', '=', '1')
        ->take(100000)->skip(0)
        ->timeout(400000000)
                            // ->take(3000)->skip(0)
                            //->take(3000)->skip(3000)
        ->get(); 

        foreach ($items as $finderdocument) {           
            $data = $finderdocument->toArray();
            $score = $this->generateRank($finderdocument);
                //$trialdata = get_elastic_finder_trialschedules($data);               
            $clusterid = '';
            if(!isset($data['location']['locationcluster_id']))
            {
               continue;
           }
           else
           {
            $clusterid  = $data['location']['locationcluster_id'];
        }

        if(isset($data['ozonetelno']) && $data['ozonetelno'] != ''){
            $data['ozonetelno']['phone_number'] = '+'.$data['ozonetelno']['phone_number'];
        }

        $locationcluster = Locationcluster::active()->where('_id',$clusterid)->get();
        $locationcluster->toArray();
        $range = (isset($data['price_range']) && $data['price_range'] != '') ? $data['price_range'] : "";
        $rangeval = 0;
        switch ($range) {
         case 'one':
         $rangeval = 1;
         break;
         case 'two':
         $rangeval = 2;
         break;
         case 'three':
         $rangeval = 3;
         break;
         case 'four':
         $rangeval = 4;
         break;
         case 'five':
         $rangeval = 5;
         break;
         case 'six':
         $rangeval = 6;
         break;
         default:
         $rangeval = 0;
         break;
     }                                                   
     $postdata = get_elastic_finder_documentv2($data, $locationcluster[0]['name'], $rangeval);      
     $postdata['rank'] = $score;
     $catval = evalBaseCategoryScore($finderdocument['category_id']);
     $postdata['rankv1'] = $catval;
     $postdata['rankv2'] = $score + $catval;
     $postfields_data = json_encode($postdata);             
     //$posturl = "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."$index_name/finder/" . $finderdocument['_id'];
        $posturl = "http://localhost:9200/"."$index_name/finder/" . $finderdocument['_id'];
     $request = array('url' => $posturl, 'port' => 9200, 'method' => 'PUT', 'postfields' => $postfields_data );
     $curl_response = es_curl_request($request);
     echo json_encode($curl_response);
     //echo "<br>$posturl    ---  ".es_curl_request($request);
 }


}
public function generateRank($finderDocument = ''){

        //$finderCategory = $finderDocument['category'];

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

        //$finderids1  =   array(1020,1041,1042,1259,1413,1484,1671,1873,45,624,1695,1720,1738,1696);
    $items = Finder::active()->orderBy('_id')->take(100000)->skip(0)->get();        
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
}