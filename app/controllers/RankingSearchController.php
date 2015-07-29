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
        //input filters
        $category = Input::json()->get('category');


        $location_filter =  '{"term" : { "city" : "'.$location.'"} },';
        $category_filter =  Input::json()->get('category') ? '{"terms" : {  "categorytags": ["'.str_ireplace(',', '","', strtolower(Input::json()->get('category'))).'"] }},': '';
        $regions_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "region": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';
        $region_tags_filter = ((Input::json()->get('regions'))) ? '{"terms" : {  "region_tags": ["'.str_ireplace(',', '","',Input::json()->get('regions')).'"] }},'  : '';
        $offerings_filter = ((Input::json()->get('offerings'))) ? '{"terms" : {  "offerings": ["'.str_ireplace(',', '","',Input::json()->get('offerings')).'"] }},'  : '';
        $facilities_filter = ((Input::json()->get('facilities'))) ? '{"terms" : {  "facilities": ["'.str_ireplace(',', '","',Input::json()->get('facilities')).'"] }},'  : '';

        $should_filtervalue = trim($regions_filter.$region_tags_filter,',');
        $must_filtervalue = trim($location_filter.$offerings_filter.$facilities_filter.$category_filter,',');
        $shouldfilter = '"should": ['.$should_filtervalue.'],';	//used for location
        $mustfilter = '"must": ['.$must_filtervalue.']';		//used for offering and facilities
        $filtervalue = trim($shouldfilter.$mustfilter,',');

        $sort = '"sort":[{"rankv2":{"order":"desc"}}]';

        if($category_filter != '')
        {
            $factor = evalBaseCategoryScore($category);
            $sort = '"sort":
        {"_script" : {
            "script" : "(doc[\'category\'].value == \''.$category.'\' ? doc[\'rankv2\'].value + factor : doc[\'category\'].value == \'fitness studios\' ? doc[\'rank\'].value + factor + '.$factor.' : doc[\'rankv2\'].value + 0)",
            "type" : "number",
            "params" : {
                "factor" : 13
            },
            "order" : "desc"
        }}';
        }

        if($shouldfilter != '' || $mustfilter != ''){
            $filters = '"filter": {
				"bool" : {'.$filtervalue.'}
			},"_cache" : true';
        }


        $category_facets = '"category": {"terms": {"field": "category","all_terms": true,"size": '.$facetssize.',"order": "term"}},';
        $regions_facets = '"regions": {"terms": {"field": "regions","all_terms": true,"size": '.$facetssize.',"order": "term"}},';
        $offerings_facets = '"offerings": {"terms": {"field": "offerings","all_terms": true,"size": '.$facetssize.',"order": "term"}},';
        $facilities_facets = '"facilities": {"terms": {"field": "facilities","all_terms": true,"size": '.$facetssize.',"order": "term"}},';
        $facetsvalue = trim($category_facets.$regions_facets.$offerings_facets.$facilities_facets,',');

        $aggs = '{}';

        $body =	'{
			"from": '.$from.',
			"size": '.$size.',
			"facets": {'.$facetsvalue.'},
			"query": {
                    "filtered": {
                            '.$filters.'
						}
					},
           '.$sort.'
		}';

        $eSQuery = json_decode($body,true);
        $searchParams['index'] = $this->indice;
        $searchParams['type']  = $type;
        $searchParams['body'] = $eSQuery;
        $results =  Es::search($searchParams);
        return $results;
    }
}

