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
        $pattern        =       '/[0-9\W]/';        // Replace all non-word chars with comma
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
        $postfields_data = array(
            '_id'                           =>      $data['_id'],
            'alias'                         =>      (isset($data['alias']) && $data['alias'] != '') ? strtolower($data['alias']) : "",
            'average_rating'                =>      (isset($data['average_rating']) && $data['average_rating'] != '') ? round($data['average_rating'],1) : 0,
            'membership_discount'           =>      "",
            'country'                       =>      (isset($data['country']['name']) && $data['country']['name'] != '') ? strtolower($data['country']['name']) : "",
            'city'                          =>      (isset($data['city']['name']) && $data['city']['name'] != '') ? strtolower($data['city']['name']) : "", 
            'category'                      =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "", 
            'category_snow'                 =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "", 
            // 'category_metatitle'            =>      (isset($data['category']['meta']['title']) && $data['category']['meta']['title'] != '') ? strtolower($data['category']['meta']['title']) : "", 
            // 'category_metadescription'      =>      (isset($data['category']['meta']['description']) && $data['category']['meta']['description'] != '') ? strtolower($data['category']['meta']['description']) : "", 
            'categorytags'                  =>      array_map('strtolower',array_pluck($data['categorytags'],'name')),
            'categorytags_snow'             =>      array_map('strtolower',array_pluck($data['categorytags'],'name')),
            'contact'                       =>      (isset($data['contact'])) ? $data['contact'] : '',
            'coverimage'                    =>      (isset($data['coverimage'])) ? $data['coverimage'] : '',
            'finder_type'                   =>      (isset($data['finder_type'])) ? $data['finder_type'] : '',
            'fitternityno'                  =>      (isset($data['fitternityno'])) ? $data['fitternityno'] : '',
            'facilities'                    =>      array_map('strtolower',array_pluck($data['facilities'],'name')),
            'logo'                          =>      (isset($data['logo'])) ? $data['logo'] : '',
            'location'                      =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
            'location_snow'                 =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
            'locationtags'                  =>      array_map('strtolower',array_pluck($data['locationtags'],'name')),
            'locationtags_snow'             =>      array_map('strtolower',array_pluck($data['locationtags'],'name')),
            'geolocation'                   =>      array('lat' => $data['lat'],'lon' => $data['lon']),
            'offerings'                     =>      array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))),
            'price_range'                   =>      (isset($data['price_range']) && $data['price_range'] != '') ? $data['price_range'] : "",
            'popularity'                    =>      (isset($data['popularity']) && $data['popularity'] != '' ) ? $data['popularity'] : 100,
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
            );

        return $postfields_data;

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

                            $newslot = ['start_time' => $val['start_time'], 'start_time_24_hour_format' => floatval(number_format($val['start_time_24_hour_format'],2)), 'price' => intval($val['price']) , 'weekday' => $value['weekday']];

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


?>