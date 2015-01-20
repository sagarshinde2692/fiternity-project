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
?>