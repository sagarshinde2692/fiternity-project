<?php
/**
 * Created by PhpStorm.
 * User: ajay
 * Date: 14/7/15
 * Time: 7:41 PM
 */

class RankingSearchController extends \BaseController
{

    protected $indice = "fitternity";
    protected $facetssize = 10000;
    protected $limit = 10;
    protected $elasticsearch_port = "";
    protected $elasticsearch_default_index = "";
    protected $elasticsearch_url = "";
    protected $elasticsearch_default_url = "";

    public function __construct()
    {
        parent::__construct();

        $this->elasticsearch_default_url = "http://" . Config::get('app.elasticsearch_host_new') . ":" . Config::get('app.elasticsearch_port_new') . '/' . Config::get('app.elasticsearch_default_index') . '/' . Config::get('app.elasticsearch_default_type') . '/';
        $this->elasticsearch_url = "http://" . Config::get('app.elasticsearch_host_new') . ":" . Config::get('app.elasticsearch_port_new') . '/';
        $this->elasticsearch_host = Config::get('app.elasticsearch_host_new');
        $this->elasticsearch_port = Config::get('app.elasticsearch_port_new');
        $this->elasticsearch_default_index = Config::get('app.elasticsearch_default_index');
    }

    public function getRankedFinderResults()
    {
        $searchParams = array();
        $facetssize =  $this->facetssize;
        $rankField = 'rankv2';
        $type = "finder";
        $filters = "";
        $from =  (Input::json()->get('from')) ? Input::json()->get('from') : 0;
        $size =  (Input::json()->get('size')) ? Input::json()->get('size') : $this->limit;
        $location = (Input::json()->get('location')) ? Input::json()->get('location') : 'mumbai';
        $orderfield  = (Input::json()->get('sort')) ? Input::json()->get('sort') : '';
        $order = (Input::json()->get('order')) ? Input::json()->get('order') : '';
        //input filters
        $category = Input::json()->get('category');



        $location_filter =  '{"term" : { "city" : "'.$location.'", "_cache": true }},';
        $category_filter =  Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"],"_cache": true}},': '';
        $budget_filter = Input::json()->get('budget') ? '{"terms" : {  "price_range": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('budget'))).'"],"_cache": true}},': '';        
        $regions_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "locationtags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"],"_cache": true}},'  : '';
        $region_tags_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "region_tags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"],"_cache": true}},'  : '';
        $offerings_filter = ((Input::json()->get('offerings'))) ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"],"_cache": true}},'  : '';
        $facilities_filter = ((Input::json()->get('facilities'))) ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"],"_cache": true}},'  : '';

        $should_filtervalue = trim($regions_filter.$region_tags_filter,',');
        $must_filtervalue = trim($location_filter.$offerings_filter.$facilities_filter.$category_filter.$budget_filter,',');
        $shouldfilter = '"should": ['.$should_filtervalue.'],'; //used for location
        $mustfilter = '"must": ['.$must_filtervalue.']';        //used for offering and facilities

        $filtervalue = trim($shouldfilter.$mustfilter,',');



        if($orderfield == 'popularity')
        {
            if($category_filter != '') {
                $factor = evalBaseCategoryScore($category);
                $sort = '"sort":
                        {"_script" : {
                            "script" : "(doc[\'category\'].value == \'' . $category . '\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + ' . $factor . ' : doc[\'rankv2\'].value + 0)",
                            "type" : "number",
                            "params" : {

                                "factor" : 11

                            },
                            "order" : "' . $order . '"
                        }}';
            }
            else{
                $sort = '"sort":[{"rankv2":{"order":"'.$order.'"}}]';
            }

        }
        else
        {
            $sort = '"sort":[{"'.$orderfield.'":{"order":"'.$order.'"}}]';
        }
        if($shouldfilter != '' || $mustfilter != ''){
            $filters = '"filter": {
                "bool" : {'.$filtervalue.'}
            },"_cache" : true';
        }

        $budgets_facets = '"budget": {"terms": {"field": "price_range","size":"500","order":{"_term": "asc"}}},';
        $regions_facets = '"loccluster": {
            "terms": {
                "field": "locationcluster"
               
            },"aggs": {
              "region": {
                "terms": {
                "field": "location",
                "size":"500",
                "order": {
                  "_term": "asc"
                }
               
            }
              }
            }
        },';

        $location_facets = '"locations": {"terms": {"field": "locationtags","size":"500","order": {"_term": "asc"}}},';
        $offerings_facets = '"offerings": {"terms": {"field": "offerings","size":"500","order": {"_term": "asc"}}},';
        $facilities_facets = '"facilities": {"terms": {"field": "facilities","size":"500","order": {"_term": "asc"}}},';
        $facetsvalue = trim($regions_facets.$location_facets.$offerings_facets.$facilities_facets.$budgets_facets,',');

        $body = '{
            "from": '.$from.',
            "size": '.$size.',
            "aggs": {'.$facetsvalue.'},
            "query": {

                    "filtered": {
                            '.$filters.'
                        }
                    },
           '.$sort.'
        }';
       // return $body;

        $request = array(
            'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity/finder/_search",
            //'url' => "http://localhost:9200/"."fitternity/finder/_search",
            'port' => 8050,
            'method' => 'POST',
            'postfields' => $body
        );


        $search_results     =   es_curl_request($request);

        $response       =   [

            'search_results' => json_decode($search_results,true)];

        return Response::json($response);

        /*$eSQuery = json_decode($body,true);
        $searchParams['index'] = $this->indice;
        $searchParams['type']  = $type;
        $searchParams['body'] = $eSQuery;
        $results =  Es::search($searchParams);
        return $results;*/
    }

    public function CategoryAmenities()
    {
        $category =  (Input::json()->get('category')) ? Input::json()->get('category') : '';
        $city     =  (Input::json()->get('city')) ? Input::json()->get('city') : 'mumbai';
        $city_id = 0;
        switch ($city) {
            case 'mumbai':
                $city_id = 1;
                break;
            case 'pune':
                $city_id = 2;
                break;
            case 'bangalore':
                $city_id = 3;
                break;
            case 'delhi':
                $city_id = 4;
                break;
            case 'hyderabad':
                $city_id = 5;
                break;
            case 'ahmedabad':
                $city_id = 6;
                break; 
            case 'gurgaon':
                $city_id = 8;
                break;           
            default:                
                break;
        }
        
        $categorytag_offerings = '';
        if($category != '')
        {
            $categorytag_offerings = Findercategorytag::active()
                                    ->where('name', $category)
                                    ->whereIn('cities',array($city_id))
                                    ->with('offerings')
                                    ->orderBy('ordering')
                                    ->get(array('_id','name','offering_header','slug','status','offerings'));
        } 
        
        $meta_title = $meta_description = $meta_keywords = '';
        if($category != ''){
            $findercategory     =   Findercategory::active()->where('slug', '=', url_slug(array($category)))->first(array('meta'));
            $meta_title         = $findercategory['meta']['title'];
            $meta_description   = $findercategory['meta']['description'];
            $meta_keywords      = $findercategory['meta']['keywords'];
        }                                 
        $resp  =    array(
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,                                        
            'catoff' => $categorytag_offerings
            );
        //return Response::json($search_results); exit;
        return Response::json($resp);
        

    }
}
