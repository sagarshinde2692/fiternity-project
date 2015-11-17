<?php

/**
 * Maintains a list of common useful functions.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


if (!function_exists('print_pretty')) {
    function print_pretty($a) {
        echo "<pre>";
        print_r($a);
        echo "</pre>";
    }
}



/**
 * Clear Cache
 * @param str $str
 * @return str
 */
if (!function_exists('clear_cache')) {
    function clear_cache($url) {


        $finalurl = Config::get('app.apiurl').strtolower($url);

        $request = array( 'url' => $finalurl, 'method' => 'GET' );

        return es_curl_request($request);
    }
}

if (!function_exists('random_numbers')) {
    function random_numbers($digits) {
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;
        return mt_rand($min, $max);
    }
}


/**
 * URL Slug
 * @param str $str
 * @return str
 */
if (!function_exists('url_slug')) {
    function url_slug($inputarray) {
        $str = implode('-', $inputarray);
        #convert case to lower
        $str = strtolower($str);
        #remove special characters
        $str = preg_replace('/[^a-zA-Z0-9]/i',' ', $str);
        #remove white space characters from both side
        $str = trim($str);
        #remove double or more space repeats between words chunk
        $str = preg_replace('/\s+/', ' ', $str);
        #fill spaces with hyphens
        $str = preg_replace('/\s+/', '-', $str);
        return $str;
    }
}

/**
 * Remove Stopwords
 * @param str $str
 * @return str
 */
if (!function_exists('refine_keyword')) {
    function refine_keyword($keyword) {

        //$stopWords = array('i','a','about','an','and','are','as','at','be','by','com','de','en','for','from','how','in','is','it','la','of','on','or','that','the','this','to','was','what','when','where','who','will','with','und','the','www');
        $stopwords      =       array('i','a','about','an','and','are','as','at','be','by','com','de','en','for','from','how','in','is','it','la','of','on','or','that','the','this','to','was','what','when','where','who','will','with','und','the','www');        
        // $pattern        =       '/[0-9\W]/';        // Replace all non-word chars with comma
        $pattern        =       '/[\W]/';        // Replace all non-word chars with comma
        $string         =       preg_replace($pattern, ',', trim(strtolower($keyword)));

        foreach (explode(",",$string) as $term) {
            if (!in_array($term, $stopwords)) {
              $keywords[] = $term;
          }
      };

      return implode(" ", $keywords);
  }
}


if (!function_exists('get_elastic_finder_document')) {
    function get_elastic_finder_document($finderdata = array()) {

        $data = $finderdata;
        
        try {
            $postfields_data = array(
                '_id'                           =>      $data['_id'],
                'alias'                         =>      (isset($data['alias']) && $data['alias'] != '') ? strtolower($data['alias']) : "",
                'average_rating'                =>      (isset($data['average_rating']) && $data['average_rating'] != '') ? round($data['average_rating'],1) : 0,
                'membership_discount'           =>      "",
                'country'                       =>      (isset($data['country']['name']) && $data['country']['name'] != '') ? strtolower($data['country']['name']) : "",
                'city'                          =>      (isset($data['city']['name']) && $data['city']['name'] != '') ? strtolower($data['city']['name']) : "", 
                'info_service'                  =>      (isset($data['info']['service']) && $data['info']['service'] != '') ? $data['info']['service'] : "", 
                'category'                      =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "", 
                'category_snow'                 =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "", 
                // 'category_metatitle'            =>      (isset($data['category']['meta']['title']) && $data['category']['meta']['title'] != '') ? strtolower($data['category']['meta']['title']) : "", 
                // 'category_metadescription'      =>      (isset($data['category']['meta']['description']) && $data['category']['meta']['description'] != '') ? strtolower($data['category']['meta']['description']) : "", 
                'categorytags'                  =>      (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower',array_pluck($data['categorytags'],'name')) : "",
                'categorytags_snow'             =>      (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower',array_pluck($data['categorytags'],'name')) : "",
                'contact'                       =>      (isset($data['contact'])) ? $data['contact'] : '',
                'coverimage'                    =>      (isset($data['coverimage'])) ? $data['coverimage'] : '',
                'finder_type'                   =>      (isset($data['finder_type'])) ? $data['finder_type'] : '',
                'commercial_type'               =>      (isset($data['commercial_type'])) ? $data['commercial_type'] : '',
                'business_type'                 =>      (isset($data['business_type'])) ? $data['business_type'] : '',
                'fitternityno'                  =>      (isset($data['fitternityno'])) ? $data['fitternityno'] : '',
                'facilities'                    =>      (isset($data['facilities']) && !empty($data['facilities'])) ? array_map('strtolower',array_pluck($data['facilities'],'name')) : "",
                'logo'                          =>      (isset($data['logo'])) ? $data['logo'] : '',
                'location'                      =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
                'location_snow'                 =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
                'locationtags'                  =>      (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower',array_pluck($data['locationtags'],'name')) : "",
                'locationtags_snow'             =>      (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower',array_pluck($data['locationtags'],'name')) : "",
                'geolocation'                   =>      array('lat' => $data['lat'],'lon' => $data['lon']),
                'offerings'                     =>      (isset($data['offerings']) && !empty($data['offerings'])) ? array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))) : "",
                'price_range'                   =>      (isset($data['price_range']) && $data['price_range'] != '') ? $data['price_range'] : "",
                'popularity'                    =>      (isset($data['popularity']) && $data['popularity'] != '' ) ? $data['popularity'] : 0,
                'special_offer_title'           =>      (isset($data['special_offer_title']) && $data['special_offer_title'] != '') ? $data['special_offer_title'] : "",
                'slug'                          =>      (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",
                'status'                        =>      (isset($data['status']) && $data['status'] != '') ? $data['status'] : "",
                'title'                         =>      (isset($data['title']) && $data['title'] != '') ? strtolower($data['title']) : "",
                'title_snow'                    =>      (isset($data['title']) && $data['title'] != '') ? strtolower($data['title']) : "",
                'total_rating_count'            =>      (isset($data['total_rating_count']) && $data['total_rating_count'] != '') ? $data['total_rating_count'] : 0,
                'views'                         =>      (isset($data['views']) && $data['views'] != '') ? $data['views'] : 0,
                'created_at'                    =>      (isset($data['created_at']) && $data['created_at'] != '') ? $data['created_at'] : "",
                'updated_at'                    =>      (isset($data['updated_at']) && $data['updated_at'] != '') ? $data['updated_at'] : "",
                'instantbooktrial_status'       =>      (isset($data['instantbooktrial_status']) && $data['instantbooktrial_status'] != '') ? intval($data['instantbooktrial_status']) : 0,
                'photos'                        =>      (isset($data['photos']) && $data['photos'] != '') ? array_map('strtolower', array_pluck($data['photos'],'url')) : "",
                );

return $postfields_data;
}catch(Swift_RfcComplianceException $exception){
    Log::error($exception);
    return [];
        }//catch

    }
}



if (!function_exists('get_elastic_service_document')) {

    function get_elastic_service_document($servicedata = array()) {

        $data  = $servicedata;

        $ratecards = $slots =  array();

        if(!empty($servicedata['workoutsessionschedules'])){

            $items = $servicedata['workoutsessionschedules'];

            foreach ($items as $key => $value) {

                if(!empty($items[$key]['slots'])){

                    foreach ($items[$key]['slots'] as $k => $val) {

                        if($value['weekday'] != '' && $val['start_time'] != '' && $val['start_time_24_hour_format'] != '' && $val['price'] != ''){

                            $newslot = ['start_time' => $val['start_time'], 
                            'start_time_24_hour_format' => floatval(number_format($val['start_time_24_hour_format'],2)), 
                            'end_time' => $val['end_time'], 
                            'end_time_24_hour_format' => floatval(number_format($val['end_time_24_hour_format'],2)), 
                            'price' => intval($val['price']) , 
                            'weekday' => $value['weekday']
                            ];
                            
                            array_push($slots, $newslot);
                        }
                    }
                }
            }
        }


        if(!empty($servicedata['ratecards'])){

            foreach ($servicedata['ratecards'] as $key => $value) {

                if(isset($value['type']) && $value['type'] == 'membership' && isset($value['duration']) && isset($value['price']) ){

                    array_push($ratecards, array('type' => $value['type'], 'special_price' => intval($value['special_price']), 'price' => intval($value['price']), 'duration' => $value['duration']));

                }
            }
        }

        if(isset($data['lat']) && $data['lat'] != '' && isset($data['lon']) && $data['lon'] != ''){
            $geolocation = array('lat' => $data['lat'],'lon' => $data['lon']);

        }elseif(isset($data['finder']['lat']) && $data['finder']['lat'] != '' && isset($data['finder']['lon']) && $data['finder']['lon'] != ''){
            $geolocation = array('lat' => $data['finder']['lat'], 'lon' => $data['finder']['lon']);

        }else{

            $geolocation = '';
        }


        $postfields_data = array(
            '_id'                           =>      $data['_id'],
            
            'category'                      =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "", 
            'category_snow'                 =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "", 
            'subcategory'                   =>      (isset($data['subcategory']['name']) && $data['subcategory']['name'] != '') ? strtolower($data['subcategory']['name']) : "", 
            'subcategory_snow'              =>      (isset($data['subcategory']['name']) && $data['subcategory']['name'] != '') ? strtolower($data['subcategory']['name']) : "", 

            'geolocation'                   =>      $geolocation,
            'finder_id'                     =>      $data['finder_id'],
            'findername'                     =>      (isset($data['finder']['title']) && $data['finder']['title'] != '') ? strtolower($data['finder']['title']) : "", 
            'findername_snow'                =>      (isset($data['finder']['title']) && $data['finder']['title'] != '') ? strtolower($data['finder']['title']) : "", 
            'commercial_type'               =>       (isset($data['finder']['commercial_type']) && $data['finder']['commercial_type'] != '') ? strtolower($data['finder']['commercial_type']) : "", 
            'commercial_type_snow'          =>      (isset($data['finder']['commercial_type']) && $data['finder']['commercial_type'] != '') ? strtolower($data['finder']['commercial_type']) : "", 
            'finderslug'                     =>      (isset($data['finder']['slug']) && $data['finder']['slug'] != '') ? strtolower($data['finder']['slug']) : "", 
            'finderslug_snow'                =>      (isset($data['finder']['slug']) && $data['finder']['slug'] != '') ? strtolower($data['finder']['slug']) : "", 
            'location'                      =>      (isset($data['finder']['location']['name']) && $data['finder']['location']['name'] != '') ? strtolower($data['finder']['location']['name']) : "", 
            'location_snow'                 =>      (isset($data['finder']['location']['name']) && $data['finder']['location']['name'] != '') ? strtolower($data['finder']['location']['name']) : "", 
            'city'                          =>      (isset($data['finder']['city']['name']) && $data['finder']['city']['name'] != '') ? strtolower($data['finder']['city']['name']) : "", 
            'country'                       =>      (isset($data['finder']['country']['name']) && $data['finder']['country']['name'] != '') ? strtolower($data['finder']['country']['name']) : "", 
            
            'name'                          =>      (isset($data['name']) && $data['name'] != '') ? strtolower($data['name']) : "",
            'name_snow'                     =>      (isset($data['name']) && $data['name'] != '') ? strtolower($data['name']) : "",
            'slug'                          =>      (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",
            
            'workout_intensity'             =>      (isset($data['workout_intensity']) && $data['workout_intensity'] != '') ? strtolower($data['workout_intensity']) : "",
            'workout_tags'                  =>      (isset($data['workout_tags']) && !empty($data['workout_tags'])) ? array_map('strtolower',$data['workout_tags']) : "",
            'workout_tags_snow'             =>      (isset($data['workout_tags']) && !empty($data['workout_tags'])) ? array_map('strtolower',$data['workout_tags']) : "",

            'workoutsessionschedules'       =>      $slots,
            'ratecards'                     =>      $ratecards

            );

return $postfields_data;

}
}





if (!function_exists('es_curl_request')) {
    function es_curl_request($params) {

        $ci = curl_init();
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_URL, $params['url']);

        if(isset($params['port'])){
            curl_setopt($ci, CURLOPT_PORT, $params['port']);
        }        
        if(isset($params['method'])){
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $params['method']);
        }
        if(isset($params['postfields'])){
            curl_setopt($ci, CURLOPT_POSTFIELDS, $params['postfields']);
        }
        
        return $response = curl_exec($ci);        
    }
}


//return date in mysql format
if (!function_exists('get_mysql_date')) {
    function get_mysql_date($time = NULL) {
        if (!$time) {
            $time = time();
        }
        return date('Y-m-d H:i:s', $time);
    }
}

//return status text only
if (!function_exists('status_text')) {
    function status_text($status) {
        if ($status == -1) {
            $status = "Deleted";
        } elseif ($status == 1) {
            $status = "Enable";
        } else {
            $status = "Disable";
        }
        return $status;
    }
}

//return status with text and html
if (!function_exists('status_html')) {
    function status_html($status) {
        if ($status == -1) {
            $status = '<span class="label label-important">Deleted</span>';
        } elseif ($status == 1) {
            $status = '<span class="label label-success">Enable</span>';
        } else {
            $status = '<span class="label label-warning">Disable</span>';
        }
        return $status;
    }
}


//generate status select box in edit form
if (!function_exists('get_ip ')) {
    function get_ip() {
        return $_SERVER['REMOTE_ADDR'];
    }
}

if (!function_exists('hash_password')) {
    function hash_password($pass) {
        $CI = & get_instance();
        //echo $CI->config->item("salt_key");
        $str = $CI->config->item("salt_key") . $pass;
        return sha1($str);
    }
}


if (!function_exists('make_dbvalue')) {
    function make_dbvalue($value) {
        $value = strtolower($value);
        return $value;
    }
}



if (!function_exists('sort_by_order')) {
    function sort_by_order ($a, $b){
        //print_pretty($a);print_pretty($b);exit;
        return $a['order'] - $b['order'];
    }
}


if (!function_exists('pluck')) {

    function pluck ($array, $property = array()){

        $returnArr = array();

        foreach ($array as $key => $value) {

            foreach ($property as $k => $val) {

                $returnArr[$key][$val] = array_get( $array[$key] , $val );

            }

        }            

        return $returnArr;

    }
}

if (!function_exists(('get_elastic_autosuggest_doc'))){

    function get_elastic_autosuggest_doc($source='', $cluster){


        $data = $source;
        $postfields_data = array(
            'input'                         =>      (isset($data['title']) && $data['title'] != '') ? $data['title'] :"",
            'autosuggestvalue'              =>       ucwords($data['title'])." in ".ucwords($data['location']['name']),
            'inputv2'                       =>      (isset($data['info']['service']) && $data['info']['service'] != '') ? $data['info']['service'] : "",                                                                       
            'inputv3'                       =>      (isset($data['offerings']) && !empty($data['offerings'])) ? array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))) : "",
            'inputv4'                       =>      (isset($data['facilities']) && !empty($data['facilities'])) ? array_map('strtolower',array_pluck($data['facilities'],'name')) : "",
            'inputloc1'                     =>      strtolower((isset($data['location']) && $data['location'] != '') ? $data['location']['name'] :""),
            'inputloc2'                     =>      ($cluster == '' ? '': strtolower($cluster)),
            'inputcat'                      =>      (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower',array_pluck($data['categorytags'],'name')) : "",
            'inputcat1'                     =>      strtolower($data['category']['name']),
            'city'                          =>      (isset($data['city']) && $data['city'] != '') ? $data['city']['name'] :"",
            'location'                      =>      (isset($data['location']) && $data['location'] != '') ? $data['location']['name'] :"",
            'identifier'                    =>      $data['category']['name'],
            'type'                          =>      'vendor',
            'slug'                          =>      $data['slug'],
            'geolocation'                   =>      array('lat' => $data['lat'],'lon' => $data['lon'])
            );

return $postfields_data;
}
}

if (!function_exists(('get_elastic_category_doc'))){

    function get_elastic_category_doc($source=''){

        $data = $source;
        $postfields_data = array(
            'input'                         =>      $data['name'],
            'inputv2'                       =>      '',
            'inputv3'                       =>      '',
            'inputv4'                       =>      '',
            'city'                          =>      array('mumbai','pune','bangalore','chennai','hyderabad','delhi','ahmedabad','gurgaon'),
            'location'                      =>      '',
            'identifier'                    =>      'categories',
            'slug'                          =>      $data['slug'],
            );
        return $postfields_data;
    }
}

if (!function_exists(('get_elastic_location_doc'))){

    function get_elastic_location_doc($source=''){

        $data = $source;
        $postfields_data = array(
            'input'                         =>      $data['name'],
            'inputv2'                       =>      '',
            'inputv3'                       =>      '',
            'inputv4'                       =>      '',
            'city'                          =>      (isset($data['cities']) && $data['cities'] != '') ? $data['cities'][0]['name']:"",
            'location'                      =>      (isset($data['cities']) && $data['cities'] != '') ? $data['cities'][0]['name']:"",
            'identifier'                    =>      'locations',
            'slug'                          =>      $data['slug'],
            );
        return $postfields_data;
    }
}

if (!function_exists(('evalBaseCategoryScore'))){
    function evalBaseCategoryScore($categoryId){
        $val = 0;
        switch($categoryId)
        {
            case 6:
            case 'yoga':
            case 12:
            case 'zumba':
            case 5:
            case 'gyms':
            case 43:
            case 'fitness studios':
                $val = 11; //gyms
                break;           

                case 35:
                case 32:
                case 'cross functional training':
                case 'crossfit':
                $val =  10;
                break;

                case 13:
                case 'kick boxing':
                $val =  9;
                break;

                case 8:
                case 7:
                case 29:
                case 'martial arts':
                case 'dance':
                case 'dance teachers':
                $val =  8;
                break;

                case 14:
                case 'spinning and indoor cycling':
                $val =  7;
                break;

                case 11:
                case 'pilates':
                $val =  6;
                break;

                case 36:
                case 'marathon training':
                $val =  5;
                break;

                case 10:
                case 'swimming':
                $val =  4;
                break;

                case 41:
                case 'personal trainers':
                $val =  3;
                break;

                case 40:
                case 'sports':
                $val =  2;
                break;

                case 42:
                case 'Healthy Tiffins':
                $val =  1;
                break;
            }
            return $val;
        }
    }

    if (!function_exists('get_elastic_finder_documentv2')) {
        function get_elastic_finder_documentv2($finderdata = array(), $locationcluster='', $rangeval =0) {

            $data = $finderdata;
            $flag = false;
            $picslist = array();            
            if(($data['category_id'] == 42) || ($data['category_id'] == 45))
            { 
                $flag = true;                   
                $service = Service::with('category')->with('subcategory')->with('finder')->where('finder_id', (int)$data['_id'])->get();
                foreach ($service as $doc1) { 
                 $doc = $doc1->toArray();
                 if( isset($doc['photos']) && !empty($doc['photos'])){                                            
                    $photos = $doc['photos'];                       
                    foreach ($photos as $key => $value) {                           
                        if(!empty($photos[$key])){                               
                            array_push($picslist, strtolower($value['url']));
                        }
                    }
                }
            }
        }
        $servicenamelist = array();
        if(isset($data['services']) && !empty($data['services'])){
            foreach ($data['services'] as $serv) {
                array_push($servicenamelist, strtolower($serv['name']));
            }
        }
        $info_service_list = array();
        if(isset($data['info']['service'])&& !empty($data['info']['service'])){
            $key1 = str_replace(array("<ul><li>","</li></ul>"), " ", $data['info']['service']);
            $key3 = trim($key1," ");
            $info_service_list = explode("</li><li>", $key3);
        }
        try {
            $postfields_data = array(
                '_id'                           =>      $data['_id'],
                'alias'                         =>      (isset($data['alias']) && $data['alias'] != '') ? strtolower($data['alias']) : "",
                'average_rating'                =>      (isset($data['average_rating']) && $data['average_rating'] != '') ? round($data['average_rating'],1) : 0,
                'membership_discount'           =>      "",
                'country'                       =>      (isset($data['country']['name']) && $data['country']['name'] != '') ? strtolower($data['country']['name']) : "",
                'city'                          =>      (isset($data['city']['name']) && $data['city']['name'] != '') ? strtolower($data['city']['name']) : "", 
                'city_id'                       =>      (isset($data['city_id']) && $data['city_id'] != '') ? strtolower($data['city_id']) : 1, 
                'info_service'                  =>      (isset($data['info']['service']) && $data['info']['service'] != '') ? $data['info']['service'] : "", 
                'info_service_snow'             =>      (isset($data['info']['service']) && $data['info']['service'] != '') ? $data['info']['service'] : "", 
                'info_service_list'             =>      $info_service_list,
                'category'                      =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "", 
                'category_snow'                 =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "", 
                // 'category_metatitle'            =>      (isset($data['category']['meta']['title']) && $data['category']['meta']['title'] != '') ? strtolower($data['category']['meta']['title']) : "", 
                // 'category_metadescription'      =>      (isset($data['category']['meta']['description']) && $data['category']['meta']['description'] != '') ? strtolower($data['category']['meta']['description']) : "", 
                'categorytags'                  =>      (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower',array_pluck($data['categorytags'],'name')) : "",
                'categorytags_snow'             =>      (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower',array_pluck($data['categorytags'],'name')) : "",
                'contact'                       =>      (isset($data['contact'])) ? $data['contact'] : '',
                'coverimage'                    =>      (isset($data['coverimage'])) ? $data['coverimage'] : '',
                'finder_type'                   =>      (isset($data['finder_type'])) ? $data['finder_type'] : '',
                'commercial_type'               =>      (isset($data['commercial_type'])) ? $data['commercial_type'] : '',
                'business_type'                 =>      (isset($data['business_type'])) ? $data['business_type'] : '',
                'fitternityno'                  =>      (isset($data['fitternityno'])) ? $data['fitternityno'] : '',
                'facilities'                    =>      (isset($data['facilities']) && !empty($data['facilities'])) ? array_map('strtolower',array_pluck($data['facilities'],'name')) : "",
                'facilities_snow'               =>      (isset($data['facilities']) && !empty($data['facilities'])) ? array_map('strtolower',array_pluck($data['facilities'],'name')) : "",
                'logo'                          =>      (isset($data['logo'])) ? $data['logo'] : '',
                'location'                      =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
                'location_snow'                 =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
                'locationtags'                  =>      (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower',array_pluck($data['locationtags'],'name')) : "",
                'locationtags_snow'             =>      (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower',array_pluck($data['locationtags'],'name')) : "",
                'geolocation'                   =>      array('lat' => $data['lat'],'lon' => $data['lon']),
                'offerings'                     =>      (isset($data['offerings']) && !empty($data['offerings'])) ? array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))) : "",
                'offerings_snow'                =>      (isset($data['offerings']) && !empty($data['offerings'])) ? array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))) : "",
                'price_range'                   =>      (isset($data['price_range']) && $data['price_range'] != '') ? $data['price_range'] : "",
                'popularity'                    =>      (isset($data['popularity']) && $data['popularity'] != '' ) ? $data['popularity'] : 0,
                'special_offer_title'           =>      (isset($data['special_offer_title']) && $data['special_offer_title'] != '') ? $data['special_offer_title'] : "",
                'slug'                          =>      (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",
                'status'                        =>      (isset($data['status']) && $data['status'] != '') ? $data['status'] : "",
                'title'                         =>      (isset($data['title']) && $data['title'] != '') ? strtolower($data['title']) : "",
                'title_snow'                    =>      (isset($data['title']) && $data['title'] != '') ? strtolower($data['title']) : "",
                'total_rating_count'            =>      (isset($data['total_rating_count']) && $data['total_rating_count'] != '') ? $data['total_rating_count'] : 0,
                'views'                         =>      (isset($data['views']) && $data['views'] != '') ? $data['views'] : 0,
                'created_at'                    =>      (isset($data['created_at']) && $data['created_at'] != '') ? $data['created_at'] : "",
                'updated_at'                    =>      (isset($data['updated_at']) && $data['updated_at'] != '') ? $data['updated_at'] : "",
                'instantbooktrial_status'       =>      (isset($data['instantbooktrial_status']) && $data['instantbooktrial_status'] != '') ? intval($data['instantbooktrial_status']) : 0,
                'photos'                        =>      (isset($data['photos']) && $data['photos'] != '') ? array_map('strtolower', array_pluck($data['photos'],'url')) : "",
                'locationcluster'               =>      $locationcluster,
                'locationcluster_snow'          =>      $locationcluster,
                'price_rangeval'                =>      $rangeval,
                'servicelist'                   =>      $servicenamelist
                //'trialschedules'                =>      $trialdata,
                );                
$postfields_data['servicephotos'] = $picslist;

return $postfields_data;
}catch(Swift_RfcComplianceException $exception){
    Log::error($exception);
    return [];
        }//catch

    }
}

if (!function_exists('get_elastic_finder_trialschedules')) {
    function get_elastic_finder_trialschedules($finderdata = array()) {

        $data = $finderdata;
        $trialdata =[];        
        try {

            if(!isset($data['services']))
            {
                return [];
            }
            else
            {
                foreach ($data['services'] as $service) {  
                    $trialservice = [];                  
                    $id = $service['_id'];
                    $city_id = $service['city_id'];
                    $trial = (isset($service['trialschedules']) ? $service['trialschedules'] : []); 
                    
                    //$traslservice;                   
                    
                    array_push($trialdata, array("service_id" => $id, "city_id" => $city_id, "trials" => $trial));                    
                }
            }
            return $trialdata;
        }catch(Swift_RfcComplianceException $exception){
            Log::error($exception);
            return [];
        }

    }
}

if (!function_exists('get_elastic_service_documentv2')) {

    function get_elastic_service_documentv2($data = array(), $finderdata = array(), $locationcluster ='') {

        $servicedata= $data;

        $ratecards = $slots =  array();      
        if(!empty($servicedata['workoutsessionschedules'])){

            $items = $servicedata['workoutsessionschedules'];

            foreach ($items as $key => $value) {

                if(!empty($items[$key]['slots'])){

                    foreach ($items[$key]['slots'] as $k => $val) {

                        if($value['weekday'] != '' && $val['start_time'] != '' && $val['start_time_24_hour_format'] != '' && $val['price'] != ''){

                            $newslot = ['start_time' => $val['start_time'], 
                            'start_time_24_hour_format' => floatval(number_format($val['start_time_24_hour_format'],2)), 
                            'end_time' => $val['end_time'], 
                            'end_time_24_hour_format' => floatval(number_format($val['end_time_24_hour_format'],2)), 
                            'price' => intval($val['price']) , 
                            'weekday' => $value['weekday']
                            ];
                            
                            array_push($slots, $newslot);
                        }
                    }
                }
            }
        }

        $durationheader ='';$budgetheader = ''; $headerarray = array(); $flag1 = false;
        $servicemarketflag = 'n';
        if(!empty($servicedata['ratecards'])){

            foreach ($servicedata['ratecards'] as $key => $value) {
                if(isset($value['type']) && $value['type'] == 'membership' && isset($value['duration']) && isset($value['price']) ){
                    $servicemarketflag = 'y';
                    $days = Duration::where('slug',$value['duration'])->get();
                    $day = $days->toArray();                
                    if(intval($day[0]['days'])=== 30){
                        $durationheader = $value['duration'];
                        $budgetheader = $value['price'];
                        $flag1 = true;
                    }
                    $price_slab = '';
                    switch($t = $value['price'])
                    {
                        case ($t < 501) :
                        $price_slab = '0 to 500';
                        break;
                        case ($t > 500 && $t < 2001) :
                        $price_slab = '500 to 2000';
                        break;
                        case ($t > 2000 && $t < 10001):
                        $price_slab = '2000 to 10000';
                        break;
                        case ($t > 10000):
                        $price_slab = '10000 to 200000';
                        break;
                    }
                    $day_slab = '';
                    switch($d = intval($day[0]['days']))
                    {                        
                        case ($d < 16) :
                        $day_slab = 'less than 2 weeks';
                        break;
                        case ($d > 15 && $d < 91) :
                        $day_slab = '1 to 3 months';
                        break;
                        case ($d > 90 && $d < 181):
                        $day_slab = '4 to 6 months';
                        break;
                        case ($d > 180):
                        $day_slab = 'more than 6 months';
                        break;                    
                    }         
                    array_push($ratecards, array('type' => $value['type'], 'special_price' => intval($value['special_price']), 'price' => intval($value['price']), 'duration' => $value['duration'], 'days' => intval($day[0]['days']), 'price_slab' => $price_slab, 'day_slab' => $day_slab, 'direct_payment_enable' => isset($value['direct_payment_enable']) ? $value['direct_payment_enable'] : 0));
                    array_push($headerarray, array('duration' => $value['duration'], 'days' => intval($day[0]['days']), 'budget' => intval($value['price'])));
                }
            }
        }

        if(isset($data['lat']) && $data['lat'] != '' && isset($data['lon']) && $data['lon'] != ''){
            $geolocation = array('lat' => $data['lat'],'lon' => $data['lon']);

        }elseif(isset($finderdata['lat']) && $finderdata['lat'] != '' && isset($finderdata['lon']) && $finderdata['lon'] != ''){
            $geolocation = array('lat' => $finderdata['lat'], 'lon' => $finderdata['lon']);

        }else{

            $geolocation = '';
        }
        $comparer = 10000000;
        if(!$flag1){
            foreach ($headerarray as $key => $val) {
                if(intval($val['budget']) < $comparer){
                    $comparer = intval($val['budget']);
                }
            }
            foreach ($headerarray as $key => $value) {
                if(intval($value['budget']) === $comparer){
                    $durationheader = $value['duration'];
                    $budgetheader = $value['budget'];
                }
            }
        }
        $cluster = array('suburb' => $locationcluster, 'locationtag' => array('loc' => (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : ""));        
        $postfields_data = array(
            '_id'                           =>      $data['_id'],            
            'category'                      =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",             
            'subcategory'                   =>      (isset($data['subcategory']['name']) && $data['subcategory']['name'] != '') ? strtolower($data['subcategory']['name']) : "",             
            'geolocation'                   =>      $geolocation,
            'finder_id'                     =>      $data['finder_id'],
            'findername'                    =>      (isset($finderdata['title']) && $finderdata['title'] != '') ? strtolower($finderdata['title']) : "", 
            'commercial_type'               =>      (isset($finderdata['commercial_type']) && $finderdata['commercial_type'] != '') ? strtolower($finderdata['commercial_type']) : "",             
            'finderslug'                    =>      (isset($finderdata['slug']) && $finderdata['slug'] != '') ? strtolower($finderdata['slug']) : "",             
            'location'                      =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",             
            'city'                          =>      (isset($finderdata['city']['name']) && $finderdata['city']['name'] != '') ? strtolower($finderdata['city']['name']) : "", 
            'country'                       =>      (isset($finderdata['country']['name']) && $finderdata['country']['name'] != '') ? strtolower($finderdata['country']['name']) : "",             
            'name'                          =>      (isset($data['name']) && $data['name'] != '') ? strtolower($data['name']) : "",            
            'slug'                          =>      (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",            
            'workout_intensity'             =>      (isset($data['workout_intensity']) && $data['workout_intensity'] != '') ? strtolower($data['workout_intensity']) : "",
            'workout_tags'                  =>      (isset($data['workout_tags']) && !empty($data['workout_tags'])) ? array_map('strtolower',$data['workout_tags']) : "",            
            'locationcluster'               =>      $locationcluster,
            'workoutsessionschedules'       =>      $slots,
            'ratecards'                     =>      $ratecards,
            'short_description'             =>      (isset($data['short_description']) && $data['short_description'] != '') ? strtolower($data['short_description']) : "", 
            'rating'                        =>      0,
            'coverimage'                    =>      (isset($finderdata['coverimage']) && $finderdata['coverimage'] != '') ? strtolower($finderdata['coverimage']) : strtolower($finderdata['finder_coverimage']), 
            'cluster'                       =>      $cluster,
            'durationheader'                =>      $durationheader,
            'budgetheader'                  =>      intval($budgetheader),
            'sm_flagv1'                     =>      $servicemarketflag,
            'budgetfinder'                  =>      isset($finderdata['budget']) ? intval($finderdata['budget']) : 0
            );

return $postfields_data;

}
}

if (!function_exists(('get_global_catloc_doc'))){

    function get_global_catloc_doc($source='', $string, $city, $loc){

        $data = $source;
        $postfields_data = array(
            'input'                         =>      $string,
            'inputv2'                       =>      '',
            'inputv3'                       =>      '',
            'inputv4'                       =>      '',
            'city'                          =>      $city,
            'location'                      =>      '',
            'identifier'                    =>      'catloc',
            //'slug'                          =>      $city.'/'.,
            );
        return $postfields_data;
    }
}

if (!function_exists(('get_elastic_autosuggest_catloc_doc'))){

    function get_elastic_autosuggest_catloc_doc($cat, $loc, $string, $city, $cluster){
        
        $postfields_data = array(
            'input'                         =>      $string,
            'autosuggestvalue'              =>      $string,
            'inputv2'                       =>      "",                                                                 
            'inputv3'                       =>      "",
            'inputv4'                       =>      "",
            'inputloc1'                     =>      strtolower($loc['name']),
            'inputloc2'                     =>      $cluster,
            'inputcat'                      =>      $cat['name'],
            'inputcat1'                     =>      $cat['name'],
            'city'                          =>      $city,
            'location'                      =>      (isset($loc['name']) && $loc['name'] != '') ? $loc['name'] :"",
            'identifier'                    =>      $cat['name'],            
            'type'                          =>      'categorylocation',
            'slug'                          =>      "",
            'geolocation'                   =>      array('lat' => 0.0,'lon' => 0.0)
            );
        return $postfields_data;
    }
}

if (!function_exists(('get_elastic_autosuggest_catfac_doc'))){

    function get_elastic_autosuggest_catfac_doc($cat, $fac, $string, $city){
      
        $postfields_data = array(
            'input'                         =>      $cat['name'],
            'autosuggestvalue'              =>      $string,
            'inputv2'                       =>      "",                                                                 
            'inputv3'                       =>      "",
            'inputv4'                       =>      strtolower($fac),
            'inputloc1'                     =>      "",
            'inputloc2'                     =>      "",
            'inputcat'                      =>      $cat['name'],
            'inputcat1'                     =>      $cat['name'],
            'city'                          =>      $city,
            'location'                      =>      "",
            'identifier'                    =>      $cat['name'],
            'type'                          =>      'categoryfacility',
            'slug'                          =>      "",
            'geolocation'                   =>      array('lat' => 0,'lon' => 0)
            );
        return $postfields_data;
    }
}

if (!function_exists(('get_elastic_autosuggest_catoffer_doc'))){

    function get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $city){
       
        $postfields_data = array(
            'input'                         =>      $cat['name'],
            'autosuggestvalue'              =>      $string,
            'inputv2'                       =>      "",                                                                 
            'inputv3'                       =>      $off['name'],
            'inputv4'                       =>      "",
            'inputloc1'                     =>      "",
            'inputloc2'                     =>      "",
            'inputcat'                      =>      $cat['name'],
            'inputcat1'                     =>      $cat['name'],
            'city'                          =>      $city,
            'location'                      =>      "",
            'identifier'                    =>      $cat['name'],
            'type'                          =>      'categoryoffering',
            'slug'                          =>      "",
            'geolocation'                   =>      array('lat' => 0,'lon' => 0)
            );
        return $postfields_data;
    }
}

if (!function_exists(('get_elastic_autosuggest_catlocoffer_doc'))){

    function get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $city, $cluster){
       
        $postfields_data = array(
            'input'                         =>      $cat['name'],
            'autosuggestvalue'              =>      $string,
            'inputv2'                       =>      "",                                                                 
            'inputv3'                       =>      strtolower($off['name']),
            'inputv4'                       =>      "",
            'inputloc1'                     =>      strtolower($loc['name']),
            'inputloc2'                     =>      $cluster,
            'inputcat'                      =>      $cat['name'],
            'inputcat1'                     =>      $cat['name'],
            'city'                          =>      $city,
            'location'                      =>      strtolower($loc['name']),
            'identifier'                    =>      $cat['name'],
            'type'                          =>      'categorylocationoffering',
            'slug'                          =>      "",
            'geolocation'                   =>      array('lat' => 0,'lon' => 0)
            );
        return $postfields_data;
    }
}

if (!function_exists(('get_elastic_autosuggest_catlocfac_doc'))){

    function get_elastic_autosuggest_catlocfac_doc($cat, $fac, $loc, $string, $city, $cluster){
       
        $postfields_data = array(
            'input'                         =>      $cat['name'],
            'autosuggestvalue'              =>      $string,
            'inputv2'                       =>      "",                                                                 
            'inputv3'                       =>      "",
            'inputv4'                       =>      strtolower($fac),
            'inputloc1'                     =>      strtolower($loc['name']),
            'inputloc2'                     =>      $cluster,
            'inputcat'                      =>      $cat['name'],
            'inputcat1'                     =>      $cat['name'],
            'city'                          =>      $city,
            'location'                      =>      strtolower($loc['name']),
            'identifier'                    =>      $cat['name'],
            'type'                          =>      'categorylocationfacilities',
            'slug'                          =>      "",
            'geolocation'                   =>      array('lat' => 0,'lon' => 0)
            );
        return $postfields_data;
    }
}

?>