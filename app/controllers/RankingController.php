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
    }

    public function IndexRankMongo2Elastic(){

        //$finderids1  =   array(1020,1041,1042,1259,1413,1484,1671,1873,45,624,1695,1720,1738,1696);

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
                            //->whereIn('_id', $finderids1)
                            ->take(4000)->skip(2000)
                            // ->take(3000)->skip(0)
                            //->take(3000)->skip(3000)
                            ->get();
                                   
        foreach ($items as $finderdocument) {
           
                $data = $finderdocument->toArray();
                $score = $this->generateRank($finderdocument);
                $clusterid  = $data['location']['locationcluster_id'];                
                $locationcluster = Locationcluster::active()->where('_id',$clusterid)->get();
                $locationcluster->toArray();                            
                $postdata = get_elastic_finder_documentv2($data, $locationcluster[0]['name']);
                $postdata['rank'] = $score;
                $catval = evalBaseCategoryScore($finderdocument['category_id']);
                $postdata['rankv1'] = $catval;
                $postdata['rankv2'] = $score + $catval;
                $postfields_data = json_encode($postdata); 
                //return $postfields_data;               
                //$posturl = $this->elasticsearch_url . "fitternity/finder/" . $finderdocument['_id'];
                //$posturl = "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity/finder/" . $finderdocument['_id'];
                $posturl = "http://localhost:9200/"."fitternity/finder/" . $finderdocument['_id'];
                //$request = array('url' => $posturl, 'port' => Config::get('elasticsearch.elasticsearch_port_new'), 'method' => 'PUT', 'postfields' => $postfields_data );
                $request = array('url' => $posturl, 'port' => 9200, 'method' => 'PUT', 'postfields' => $postfields_data );
                echo "<br>$posturl    ---  ".es_curl_request($request);
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

        $reviews =  $finderDocument['reviews'];
        $trials  =  $finderDocument['trialsBooked'];
        $orders  =  $finderDocument['orders30days'];
        $popularity = intval($finderDocument['popularity']);

        $popularityScore  =  (($this->normalizingFunction($this->reviews_min, $this->reviews_max, $reviews)) + ($this->normalizingFunction($this->trials_min, $this->trials_max, $trials))
                                + 2*($this->normalizingFunction($this->orders_min, $this->orders_max, $orders)) + 2*($this->normalizingFunction($this->popularity_min, $this->popularity_max, $popularity)))/6;
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
        $items = Finder::active()->orderBy('_id')->take(3500)->skip(3500)->get();        
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