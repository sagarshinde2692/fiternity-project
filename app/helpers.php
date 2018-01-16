<?php

/**
 * Maintains a list of common useful functions.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;

use App\Mailers\CustomerMailer as CustomerMailer;

use App\Services\Utilities as Utilities;

if (!function_exists('checkNull')) {

    function checkNull($value){
        if ($value == null) {
            return '';
        } else {
            return $value;
        }
    }

}



if (!function_exists('decode_customer_token')) {

    function decode_customer_token(){

        $jwt_token              =   Request::header('Authorization');
        $jwt_key                =   Config::get('app.jwt.key');
        $jwt_alg                =   Config::get('app.jwt.alg');

        try{

            if(Cache::tags('blacklist_customer_token')->has($jwt_token)){
                return Response::json(array('status' => 400,'message' => 'User logged out'),400);
            }

            $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
            return $decodedToken;

        }catch(DomainException $e){
            return Response::json(array('status' => 400,'message' => 'Token incorrect, Please login again'),400);
        }catch(ExpiredException $e){

            JWT::$leeway = (86400*365);

            $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
            return $decodedToken;
            
        }catch(SignatureInvalidException $e){
            return Response::json(array('status' => 400,'message' => 'Signature verification failed, Please login again'),400);
        }catch(Exception $e){
            return Response::json(array('status' => 400,'message' => 'Token incorrect, Please login again'),400);
        }
    }

}

if (!function_exists('sorting_array')) {
    function sorting_array($unOrderArr, $column, $orderIds, $columnIsInt = false){
        $orderArr        =      [];
        $columnname      =      trim($column);

        foreach ($orderIds as $orderid){
            if($columnIsInt){
                $arrItem  = head(array_where($unOrderArr, function($key, $value) use ($orderid, $columnname){
                    if(intval($value[$columnname]) == intval($orderid)){
                        return $value;
                    }
                }));
            }else{
                $arrItem  = head(array_where($unOrderArr, function($key, $value) use ($orderid, $columnname){
                    if($value[$columnname] == $orderid){
                        return $value;
                    }
                }));
            }
            array_push($orderArr,$arrItem);
        }
        // print_r($orderArr);
        return $orderArr;
    }
}


if(!function_exists('citywise_category')){
    function citywise_categories($city){
            $city = getmy_city($city);
            $category_slug = [
                "gyms",
                "yoga",
                "zumba",
                "fitness-studios",
                "marathon-training",
                "dance",
                "cross-functional-training",
                "mma-and-kick-boxing",
                "swimming",
                "pilates",
                "luxury-hotels",
                "healthy-snacks-and-beverages",
                "spinning-and-indoor-cycling",
                "healthy-tiffins",
                "sport-nutrition-supplement-stores",
                "aqua-fitness"
            ];

            $cat = [];

            $cat['mumbai'] = [
                ["name" => "All Fitness Options","slug" => "fitness"],
                ["name" => "Gyms","slug" => "gyms"],
                ["name" => "Zumba","slug" => "zumba-classes"],
                ["name" => "Cross Functional Training","slug" => "functional-training"],
                ["name" => "Fitness Studios","slug" => "fitness-studios"],
                ["name" => "MMA And Kick Boxing","slug" => "mma-and-kick-boxing-classes"],
                ["name" => "Yoga","slug" => "yoga-classes"],
                ["name" => "Swimming","slug" => "swimming-pools"],
                ["name" => "Marathon Training","slug" => "marathon-training"],
                ["name" => "Pilates","slug" => "pilates-classes"],
                ["name" => "Dance","slug" => "dance-classes"],
                ["name" => "Spinning And Indoor Cycling","slug" => "spinning-classes"],
                ["name" => "Healthy Tiffins","slug" => "healthy-tiffins"],
                // ["name" => "Personal Trainers","slug" => "personal-trainers"],
                // ["name" => "Healthy Snacks And Beverages","slug" => "healthy-snacks-and-beverages"],
                // ["name" => "Sport Nutrition Supplement Stores","slug" => "sport-nutrition-supplement-stores"],
                ["name" => "Luxury Hotels","slug" => "luxury-hotels"],
                ["name" => "Aerial Fitness","slug" => "aerial-fitness"],
                ["name" => "Pre-natal Classes","slug" => "pre-natal-classes"],
                ["name" => "Kids Fitness","slug" => "kids-fitness-classes"],
                ["name" => "Aqua Fitness","slug" => "aqua-fitness"]
            ];

            $cat['pune'] = [
                ["name" => "All Fitness Options","slug" => "fitness"],
                ["name" => "Gyms","slug" => "gyms"],
                ["name" => "Zumba","slug" => "zumba-classes"],
                ["name" => "Cross Functional Training","slug" => "functional-training"],
                ["name" => "Fitness Studios","slug" => "fitness-studios"],
                ["name" => "Dance","slug" => "dance-classes"],
                ["name" => "MMA And Kick Boxing","slug" => "mma-and-kick-boxing-classes"],
                ["name" => "Spinning And Indoor Cycling","slug" => "spinning-classes"],
                ["name" => "Yoga","slug" => "yoga-classes"],
                ["name" => "Aerobics","slug" => "aerobics"],
                ["name" => "Pilates","slug" => "pilates-classes"],
                ["name" => "Healthy Tiffins","slug" => "healthy-tiffins"],
                // ["name" => "Personal Trainers","slug" => "personal-trainers"],
                // ["name" => "Sport Nutrition Supplement Stores","slug" => "sport-nutrition-supplement-stores"],
                ["name" => "Aerial Fitness","slug" => "aerial-fitness"],
                ["name" => "Pre-natal Classes","slug" => "pre-natal-classes"],
                ["name" => "Kids Fitness","slug" => "kids-fitness-classes"]
            ];

            $cat['bangalore'] = [
                ["name" => "All Fitness Options","slug" => "fitness"],
                ["name" => "Gyms","slug" => "gyms"],
                ["name" => "Zumba","slug" => "zumba-classes"],
                ["name" => "Cross Functional Training","slug" => "functional-training"],
                ["name" => "Yoga","slug" => "yoga-classes"],
                ["name" => "Fitness Studios","slug" => "fitness-studios"],
                ["name" => "MMA And Kick Boxing","slug" => "mma-and-kick-boxing-classes"],
                ["name" => "Dance","slug" => "dance-classes"],
                ["name" => "Pilates","slug" => "pilates-classes"],
                ["name" => "Spinning And Indoor Cycling","slug" => "spinning-classes"],
                ["name" => "Healthy Tiffins","slug" => "healthy-tiffins"],
                // ["name" => "Personal Trainers","slug" => "personal-trainers"],
                // ["name" => "Sport Nutrition Supplement Stores","slug" => "sport-nutrition-supplement-stores"],
                ["name" => "Aerial Fitness","slug" => "aerial-fitness"],
                ["name" => "Pre-natal Classes","slug" => "pre-natal-classes"],
                ["name" => "Kids Fitness","slug" => "kids-fitness-classes"]
            ];

            $cat['delhi'] = [
                ["name" => "All Fitness Options","slug" => "fitness"],
                ["name" => "Gyms","slug" => "gyms"],
                ["name" => "Zumba","slug" => "zumba-classes"],
                ["name" => "Cross Functional Training","slug" => "functional-training"],
                ["name" => "Fitness Studios","slug" => "fitness-studios"],
                ["name" => "Dance","slug" => "dance-classes"],
                ["name" => "Yoga","slug" => "yoga-classes"],
                ["name" => "MMA And Kick Boxing","slug" => "mma-and-kick-boxing-classes"],
                ["name" => "Pilates","slug" => "pilates-classes"],
                ["name" => "Spinning And Indoor Cycling","slug" => "spinning-classes"],
                ["name" => "Healthy Tiffins","slug" => "healthy-tiffins"],
                // ["name" => "Personal Trainers","slug" => "personal-trainers"],
                // ["name" => "Sport Nutrition Supplement Stores","slug" => "sport-nutrition-supplement-stores"],
                // ["name" => "Marathon Training","slug" => "marathon-training"],
                ["name" => "Aerial Fitness","slug" => "aerial-fitness"],
                ["name" => "Pre-natal Classes","slug" => "pre-natal-classes"],
                ["name" => "Kids Fitness","slug" => "kids-fitness-classes"]
            ];

            $cat['gurgaon'] = [
                ["name" => "All Fitness Options","slug" => "fitness"],
                ["name" => "Gyms","slug" => "gyms"],
                ["name" => "Cross Functional Training","slug" => "functional-training"],
                ["name" => "Fitness Studios","slug" => "fitness-studios"],
                ["name" => "Zumba","slug" => "zumba-classes"],
                ["name" => "Yoga","slug" => "yoga-classes"],
                ["name" => "Dance","slug" => "dance-classes"],
                ["name" => "Pilates","slug" => "pilates-classes"],
                ["name" => "MMA And Kick Boxing","slug" => "mma-and-kick-boxing-classes"],
                ["name" => "Spinning And Indoor Cycling","slug" => "spinning-classes"],
                ["name" => "Healthy Tiffins","slug" => "healthy-tiffins"],
                // ["name" => "Personal Trainers","slug" => "personal-trainers"],
                // ["name" => "Sport Nutrition Supplement Stores","slug" => "sport-nutrition-supplement-stores"],
                // ["name" => "Aerial Fitness","slug" => "aerial-fitness"],
                ["name" => "Pre-natal Classes","slug" => "pre-natal-classes"],
                ["name" => "Kids Fitness","slug" => "kids-fitness-classes"]
            ];
            
            $cat['noida'] = [
                ["name" => "All Fitness Options","slug" => "fitness"],
                ["name" => "Gyms","slug" => "gyms"],
                ["name" => "Fitness Studios","slug" => "fitness-studios"],
                ["name" => "Zumba","slug" => "zumba-classes"],
                ["name" => "Dance","slug" => "dance-classes"],
                ["name" => "Yoga","slug" => "yoga-classes"],
                ["name" => "MMA And Kick Boxing","slug" => "mma-and-kick-boxing-classes"],
                // ["name" => "Pre-natal Classes","slug" => "pre-natal-classes"],
                // ["name" => "Kids Fitness","slug" => "kids-fitness-classes"]
            ];

            $cat['hyderabad'] = [];

            $cat['all'] = [
                ["name" => "All Fitness Options","slug" => "fitness"],
                ["name" => "Gyms","slug" => "gyms"],
                ["name" => "Yoga","slug" => "yoga-classes"],
                ["name" => "Zumba","slug" => "zumba-classes"],
                ["name" => "Fitness Studios","slug" => "fitness-studios"],
                ["name" => "Pilates","slug" => "pilates-classes"],
                ["name" => "Healthy Tiffins","slug" => "healthy-tiffins"],
                ["name" => "Cross Functional Training","slug" => "functional-training"],
                ["name" => "Aerobics","slug" => "aerobics"],
                ["name" => "MMA And Kick Boxing","slug" => "mma-and-kick-boxing-classes"],
                ["name" => "Dance","slug" => "dance-classes"],
                ["name" => "Spinning And Indoor Cycling","slug" => "spinning-classes"],
                // ["name" => "Personal Trainers","slug" => "personal-trainers"],
                ["name" => "Healthy Snacks And Beverages","slug" => "healthy-snacks-and-beverages"],
                ["name" => "Marathon Training","slug" => "marathon-training"],
                ["name" => "Swimming","slug" => "swimming-pools"],
                // ["name" => "Sport Nutrition Supplement Stores","slug" => "sport-nutrition-supplement-stores"],
                ["name" => "Luxury Hotels","slug" => "luxury-hotels"],
                ["name" => "Aerial Fitness","slug" => "aerial-fitness"],
                ["name" => "Pre-natal Classes","slug" => "pre-natal-classes"],
                ["name" => "Kids Fitness","slug" => "kids-fitness-classes"],
                ["name" => "Aqua Fitness","slug" => "aqua-fitness"]
            ];

            if(isset($cat[$city])){
                $category_slug = $cat[$city];
            }

            return $category_slug;
    }
}

if(!function_exists('getmy_city')){
    function getmy_city($city){
        $city = strtolower($city);
        switch($city){
            case "mumbai":
            case "bombay":
            case "thane":
            case "navi mumbai":
            case "bhayandar":
            case "navi":
                return "mumbai";
                break;
            case "delhi":
            case "new delhi":
                return "delhi";
                break;
            case "bangalore":
            case "bengaluru":
                return "bangalore";
                break;
            case "gurgaon":
            case "gurugram":
                return "gurgaon";
                break;
            case "pimpri":
            case "chinchwad":
            case "poona":
            case "pimpri-chichwad":
                return "pune";
                break;
            default: return $city;
        };
    }
}


if(!function_exists('ifCityPresent')){
    function ifCityPresent($city){
        $city = strtolower($city);
        $send_city = $city;
        $ifcity = false;
        switch($city){
            case "mumbai":
            case "bombay":
            case "thane":
            case "vashi":
            case "bhiwandi":
            case "navi mumbai":
                $send_city = "mumbai";
                $ifcity = true;
                break;
            case "pune":
            case "pimpri":
            case "pimpri chinchwad":
            case "pimpri-chinchwad":
                $send_city = "pune";
                $ifcity = true;
                break;
            case "bangalore":
            case "bengaluru":
                $send_city = "bangalore";
                $ifcity = true;
                break;
            case "delhi":
            case "new delhi":
                $send_city = "delhi";
                $ifcity = true;
                break;
            case "gurugram":
            case "gurgaon":
                $send_city = "gurgaon";
                $ifcity = true;
                break;
            case "noida":
            case "greater noida":
                $send_city = "gurgaon";
                $ifcity = true;
                break;
        };
        $response = array("city"=>$send_city,"found"=>$ifcity);
        return $response;
    }
}


if (!function_exists('bitly_url')) {

    function bitly_url($url = 'https://www.fitternity.com')
    {
        $url = $url;
        $domain = 'bit.ly';
        $access_token = '6813bbea5cf6ddf122a31fdf35f7c9b4afb244f5';
        $format = 'json';
        $bitly = 'https://api-ssl.bitly.com/v3/shorten?access_token=' . $access_token . '&longUrl=' . urlencode($url) . '&domain=' . $domain . '&format=' . $format;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $bitly);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $json = @json_decode($data, true);

        if ($json['status_code'] == 200) {
            return $bitly = $json['data']['url'];
        } else {
            return $url;
        }
    }
}


if (!function_exists('random_numbers')) {
    function random_numbers($digits)
    {
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;
        return mt_rand($min, $max);
    }
}


if (!function_exists('print_pretty')) {
    function print_pretty($a)
    {
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
    function clear_cache($url)
    {


        $finalurl = Config::get('app.apiurl') . strtolower($url);

        $request = array('url' => $finalurl, 'method' => 'GET');

        return es_curl_request($request);
    }
}

if (!function_exists('random_numbers')) {
    function random_numbers($digits)
    {
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
    function url_slug($inputarray)
    {
        $str = implode('-', $inputarray);
        #convert case to lower
        $str = strtolower($str);
        #remove special characters
        $str = preg_replace('/[^a-zA-Z0-9]/i', ' ', $str);
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
    function refine_keyword($keyword)
    {

        //$stopWords = array('i','a','about','an','and','are','as','at','be','by','com','de','en','for','from','how','in','is','it','la','of','on','or','that','the','this','to','was','what','when','where','who','will','with','und','the','www');
        $stopwords = array('i', 'a', 'about', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'in', 'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und', 'the', 'www');
        // $pattern        =       '/[0-9\W]/';        // Replace all non-word chars with comma
        $pattern = '/[\W]/';        // Replace all non-word chars with comma
        $string = preg_replace($pattern, ',', trim(strtolower($keyword)));

        foreach (explode(",", $string) as $term) {
            if (!in_array($term, $stopwords)) {
                $keywords[] = $term;
            }
        };

        return implode(" ", $keywords);
    }
}


if (!function_exists('get_elastic_finder_document')) {
    function get_elastic_finder_document($finderdata = array())
    {

        $data = $finderdata;

        try {
            $postfields_data = array(
                '_id' => $data['_id'],
                'alias' => (isset($data['alias']) && $data['alias'] != '') ? strtolower($data['alias']) : "",
                'average_rating' => (isset($data['average_rating']) && $data['average_rating'] != '') ? round($data['average_rating'], 1) : 0,
                'membership_discount' => "",
                'country' => (isset($data['country']['name']) && $data['country']['name'] != '') ? strtolower($data['country']['name']) : "",
                'city' => (isset($data['city']['name']) && $data['city']['name'] != '') ? strtolower($data['city']['name']) : "",
                'info_service' => (isset($data['info']['service']) && $data['info']['service'] != '') ? $data['info']['service'] : "",
                'category' => (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",
                'category_snow' => (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",
                // 'category_metatitle'            =>      (isset($data['category']['meta']['title']) && $data['category']['meta']['title'] != '') ? strtolower($data['category']['meta']['title']) : "", 
                // 'category_metadescription'      =>      (isset($data['category']['meta']['description']) && $data['category']['meta']['description'] != '') ? strtolower($data['category']['meta']['description']) : "", 
                'categorytags' => (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower', array_pluck($data['categorytags'], 'name')) : "",
                'categorytags_snow' => (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower', array_pluck($data['categorytags'], 'name')) : "",
                'contact' => (isset($data['contact'])) ? $data['contact'] : '',
                'coverimage' => (isset($data['coverimage'])) ? $data['coverimage'] : '',
                'finder_type' => (isset($data['finder_type'])) ? $data['finder_type'] : '',
                'commercial_type' => (isset($data['commercial_type'])) ? $data['commercial_type'] : '',
                'business_type' => (isset($data['business_type'])) ? $data['business_type'] : '',
                'fitternityno' => (isset($data['fitternityno'])) ? $data['fitternityno'] : '',
                'facilities' => (isset($data['facilities']) && !empty($data['facilities'])) ? array_map('strtolower', array_pluck($data['facilities'], 'name')) : "",
                'logo' => (isset($data['logo'])) ? $data['logo'] : '',
                'location' => (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
                'location_snow' => (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
                'locationtags' => (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower', array_pluck($data['locationtags'], 'name')) : "",
                'locationtags_snow' => (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower', array_pluck($data['locationtags'], 'name')) : "",
                'geolocation' => array('lat' => $data['lat'], 'lon' => $data['lon']),
                'offerings' => (isset($data['offerings']) && !empty($data['offerings'])) ? array_values(array_unique(array_map('strtolower', array_pluck($data['offerings'], 'name')))) : "",
                'price_range' => (isset($data['price_range']) && $data['price_range'] != '') ? $data['price_range'] : "",
                'popularity' => (isset($data['popularity']) && $data['popularity'] != '') ? $data['popularity'] : 0,
                'special_offer_title' => (isset($data['special_offer_title']) && $data['special_offer_title'] != '') ? $data['special_offer_title'] : "",
                'slug' => (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",
                'status' => (isset($data['status']) && $data['status'] != '') ? $data['status'] : "",
                'title' => (isset($data['title']) && $data['title'] != '') ? strtolower($data['title']) : "",
                'title_snow' => (isset($data['title']) && $data['title'] != '') ? strtolower($data['title']) : "",
                'total_rating_count' => (isset($data['total_rating_count']) && $data['total_rating_count'] != '') ? $data['total_rating_count'] : 0,
                'views' => (isset($data['views']) && $data['views'] != '') ? $data['views'] : 0,
                'created_at' => (isset($data['created_at']) && $data['created_at'] != '') ? $data['created_at'] : "",
                'updated_at' => (isset($data['updated_at']) && $data['updated_at'] != '') ? $data['updated_at'] : "",
                'instantbooktrial_status' => (isset($data['instantbooktrial_status']) && $data['instantbooktrial_status'] != '') ? intval($data['instantbooktrial_status']) : 0,
                'photos' => (isset($data['photos']) && $data['photos'] != '') ? array_map('strtolower', array_pluck($data['photos'], 'url')) : "",
            );

            return $postfields_data;
        } catch (Swift_RfcComplianceException $exception) {
            Log::error($exception);
            return [];
        }//catch

    }
}


if (!function_exists('get_elastic_service_document')) {

    function get_elastic_service_document($servicedata = array())
    {

        $data = $servicedata;

        $ratecards = $slots = array();

        if (!empty($servicedata['workoutsessionschedules'])) {

            $items = $servicedata['workoutsessionschedules'];

            foreach ($items as $key => $value) {

                if (!empty($items[$key]['slots'])) {

                    foreach ($items[$key]['slots'] as $k => $val) {

                        if ($value['weekday'] != '' && $val['start_time'] != '' && $val['start_time_24_hour_format'] != '' && $val['price'] != '') {

                            $newslot = ['start_time' => $val['start_time'],
                                'start_time_24_hour_format' => floatval(number_format($val['start_time_24_hour_format'], 2)),
                                'end_time' => $val['end_time'],
                                'end_time_24_hour_format' => floatval(number_format($val['end_time_24_hour_format'], 2)),
                                'price' => intval($val['price']),
                                'weekday' => $value['weekday']
                            ];

                            array_push($slots, $newslot);
                        }
                    }
                }
            }
        }


        if (!empty($servicedata['ratecards'])) {

            foreach ($servicedata['ratecards'] as $key => $value) {

                if (isset($value['type']) && $value['type'] == 'membership' && isset($value['duration']) && isset($value['price'])) {

                    array_push($ratecards, array('type' => $value['type'], 'special_price' => intval($value['special_price']), 'price' => intval($value['price']), 'duration' => $value['duration']));

                }
            }
        }

        if (isset($data['lat']) && $data['lat'] != '' && isset($data['lon']) && $data['lon'] != '') {
            $geolocation = array('lat' => $data['lat'], 'lon' => $data['lon']);

        } elseif (isset($data['finder']['lat']) && $data['finder']['lat'] != '' && isset($data['finder']['lon']) && $data['finder']['lon'] != '') {
            $geolocation = array('lat' => $data['finder']['lat'], 'lon' => $data['finder']['lon']);

        } else {

            $geolocation = '';
        }


        $postfields_data = array(
            '_id' => $data['_id'],

            'category' => (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",
            'category_snow' => (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",
            'subcategory' => (isset($data['subcategory']['name']) && $data['subcategory']['name'] != '') ? strtolower($data['subcategory']['name']) : "",
            'subcategory_snow' => (isset($data['subcategory']['name']) && $data['subcategory']['name'] != '') ? strtolower($data['subcategory']['name']) : "",

            'geolocation' => $geolocation,
            'finder_id' => $data['finder_id'],
            'findername' => (isset($data['finder']['title']) && $data['finder']['title'] != '') ? strtolower($data['finder']['title']) : "",
            'findername_snow' => (isset($data['finder']['title']) && $data['finder']['title'] != '') ? strtolower($data['finder']['title']) : "",
            'commercial_type' => (isset($data['finder']['commercial_type']) && $data['finder']['commercial_type'] != '') ? strtolower($data['finder']['commercial_type']) : "",
            'commercial_type_snow' => (isset($data['finder']['commercial_type']) && $data['finder']['commercial_type'] != '') ? strtolower($data['finder']['commercial_type']) : "",
            'finderslug' => (isset($data['finder']['slug']) && $data['finder']['slug'] != '') ? strtolower($data['finder']['slug']) : "",
            'finderslug_snow' => (isset($data['finder']['slug']) && $data['finder']['slug'] != '') ? strtolower($data['finder']['slug']) : "",
            'location' => (isset($data['finder']['location']['name']) && $data['finder']['location']['name'] != '') ? strtolower($data['finder']['location']['name']) : "",
            'location_snow' => (isset($data['finder']['location']['name']) && $data['finder']['location']['name'] != '') ? strtolower($data['finder']['location']['name']) : "",
            'city' => (isset($data['finder']['city']['name']) && $data['finder']['city']['name'] != '') ? strtolower($data['finder']['city']['name']) : "",
            'country' => (isset($data['finder']['country']['name']) && $data['finder']['country']['name'] != '') ? strtolower($data['finder']['country']['name']) : "",

            'name' => (isset($data['name']) && $data['name'] != '') ? strtolower($data['name']) : "",
            'name_snow' => (isset($data['name']) && $data['name'] != '') ? strtolower($data['name']) : "",
            'slug' => (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",

            'workout_intensity' => (isset($data['workout_intensity']) && $data['workout_intensity'] != '') ? strtolower($data['workout_intensity']) : "",
            'workout_tags' => (isset($data['workout_tags']) && !empty($data['workout_tags'])) ? array_map('strtolower', $data['workout_tags']) : "",
            'workout_tags_snow' => (isset($data['workout_tags']) && !empty($data['workout_tags'])) ? array_map('strtolower', $data['workout_tags']) : "",

            'workoutsessionschedules' => $slots,
            'ratecards' => $ratecards

        );

        return $postfields_data;

    }
}


if (!function_exists('es_curl_request')) {
    function es_curl_request($params)
    {

        $ci = curl_init();
        curl_setopt($ci, CURLOPT_TIMEOUT, 200);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
        curl_setopt($ci, CURLOPT_URL, $params['url']);

        if (isset($params['port'])) {
            curl_setopt($ci, CURLOPT_PORT, $params['port']);
        }
        if (isset($params['method'])) {
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $params['method']);
        }
        if (isset($params['postfields'])) {
            curl_setopt($ci, CURLOPT_POSTFIELDS, $params['postfields']);
        }

        return $response = curl_exec($ci);
    }
}


//return date in mysql format
if (!function_exists('get_mysql_date')) {
    function get_mysql_date($time = NULL)
    {
        if (!$time) {
            $time = time();
        }
        return date('Y-m-d H:i:s', $time);
    }
}

//return status text only
if (!function_exists('status_text')) {
    function status_text($status)
    {
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
    function status_html($status)
    {
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
    function get_ip()
    {
        return $_SERVER['REMOTE_ADDR'];
    }
}

if (!function_exists('hash_password')) {
    function hash_password($pass)
    {
        $CI = &get_instance();
        //echo $CI->config->item("salt_key");
        $str = $CI->config->item("salt_key") . $pass;
        return sha1($str);
    }
}


if (!function_exists('make_dbvalue')) {
    function make_dbvalue($value)
    {
        $value = strtolower($value);
        return $value;
    }
}


if (!function_exists('sort_by_order')) {
    function sort_by_order($a, $b)
    {
        //print_pretty($a);print_pretty($b);exit;
        return $a['order'] - $b['order'];
    }
}


if (!function_exists('pluck')) {

    function pluck($array, $property = array())
    {

        $returnArr = array();

        foreach ($array as $key => $value) {

            foreach ($property as $k => $val) {

                $returnArr[$key][$val] = array_get($array[$key], $val);

            }

        }

        return $returnArr;

    }
}

if (!function_exists(('get_elastic_autosuggest_doc'))) {

    function get_elastic_autosuggest_doc($source = '', $cluster)
    {

        $info_service_list = array();
        $data = $source;
        if(isset($data['services'])&& !empty($data['services'])){
            // $key1 = str_replace(array("<ul><li>","</li></ul>"), " ", $data['info']['service']);
            // $key3 = trim($key1," ");
            // $info_service_list = explode("</li><li>", $key3);
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

return $postfields_data;
}
}

if (!function_exists(('get_elastic_category_doc'))) {

    function get_elastic_category_doc($source = '')
    {

        $data = $source;
        $postfields_data = array(
            'input' => $data['name'],
            'inputv2' => '',
            'inputv3' => '',
            'inputv4' => '',
            'city' => array('mumbai', 'pune', 'bangalore', 'chennai', 'hyderabad', 'delhi', 'ahmedabad', 'gurgaon'),
            'location' => '',
            'identifier' => 'categories',
            'slug' => $data['slug'],
        );
        return $postfields_data;
    }
}

if (!function_exists(('get_elastic_location_doc'))) {

    function get_elastic_location_doc($source = '')
    {

        $data = $source;
        $postfields_data = array(
            'input' => $data['name'],
            'inputv2' => '',
            'inputv3' => '',
            'inputv4' => '',
            'city' => (isset($data['cities']) && $data['cities'] != '') ? $data['cities'][0]['name'] : "",
            'location' => (isset($data['cities']) && $data['cities'] != '') ? $data['cities'][0]['name'] : "",
            'identifier' => 'locations',
            'slug' => $data['slug'],
        );
        return $postfields_data;
    }
}

if (!function_exists(('evalBaseCategoryScore'))) {
    function evalBaseCategoryScore($categoryId)
    {
        $val = 0;
        switch ($categoryId) {
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
                $val = 10;
                break;

            case 13:
            case 'kick boxing':
                $val = 9;
                break;

            case 8:
            case 7:
            case 29:
            case 'martial arts':
            case 'dance':
            case 'dance teachers':
                $val = 8;
                break;

            case 14:
            case 'spinning and indoor cycling':
                $val = 7;
                break;

            case 11:
            case 'pilates':
                $val = 6;
                break;

            case 36:
            case 'marathon training':
                $val = 5;
                break;

            case 10:
            case 'swimming':
                $val = 4;
                break;

            case 41:
            case 'personal trainers':
                $val = 3;
                break;

            case 40:
            case 'sports':
                $val = 2;
                break;

            case 42:
            case 'Healthy Tiffins':
                $val = 1;
                break;

            default:

                $val = 0;
        }
        return $val;
    }
}

if (!function_exists('get_elastic_finder_documentv2')) {
    function get_elastic_finder_documentv2($finderdata = array(), $locationcluster = '', $rangeval = 0)
    {

        try {
            $data = $finderdata;
            $data['vip_trial'] = 0;
            $flag = false;
            $picslist = array();
            if (($data['category_id'] == 42) || ($data['category_id'] == 45)) {
                $flag = true;
                $service = Service::with('category')->with('subcategory')->with('finder')->where('finder_id', (int)$data['_id'])->get();
                foreach ($service as $doc1) {
                    $doc = $doc1->toArray();
                    if (isset($doc['photos']) && !empty($doc['photos'])) {
                        $photos = $doc['photos'];
                        foreach ($photos as $key => $value) {
                            if (!empty($photos[$key])) {
                                array_push($picslist, strtolower($value['url']));
                            }
                        }
                    }
                }
            }
            $offer_counter = 0;
            $servicenamelist = array();
            if (isset($data['services']) && !empty($data['services'])) {
                foreach ($data['services'] as $serv) {
                    array_push($servicenamelist, strtolower($serv['name']));
                    if (isset($serv['show_in_offers'])) {
                        ++$offer_counter;
                    }
                }
            }

            $info_service_list = array();
            if (isset($data['info']['service']) && !empty($data['info']['service'])) {
                $key1 = str_replace(array("<ul><li>", "</li></ul>"), " ", $data['info']['service']);
                $key3 = trim($key1, " ");
                $info_service_list = explode("</li><li>", $key3);
            }

            /*

            Build schedules array here for the finder for schedules/weekdays search
            */

            $weekdays = array();
            $trial_slots = array();
            $service_level_data_all = array();
                $arr_service_category_exact = array();
                $arr_service_cat_synonyms = array();
//                var_dump($data['services']);exit();
        foreach ($data['services'] as $serv) {

            $service_level_data = array();
            $service_level_data['service_category_exact'] = array();
            $service_level_data['service_category_synonyms'] = array();
            $service_level_data['day'] = array();
            $service_level_data['start'] = array();
            $service_level_data['end'] = array();
            $service_cat = '';
            $service_cat_sub ='';
//            if($data['category']['name'] !== 'healthy tiffins' )
//            {
            $service_cat = strtolower($serv['category']['name']).','.get_service_category_synonyms(strtolower($serv['category']['name']));
            $service_cat = trim($service_cat, ',');
            $service_cat = explode(',',$service_cat);

            $service_cat_sub = strtolower($serv['subcategory']['name']).','.get_service_category_synonyms(strtolower($serv['subcategory']['name']));
            $service_cat_sub = trim($service_cat_sub, ',');
            $service_cat_sub = explode(',',$service_cat_sub);

            $service_cat = array_values(array_unique(array_merge($service_cat, $service_cat_sub)));
            $service_category_exact = array();
            isset($serv['category']['name']) ? array_push($service_category_exact, $serv['category']['name']) : null;
            isset($serv['subcategory']['name']) ? array_push($service_category_exact, $serv['subcategory']['name']) : null;
            $service_category_exact = array_values(array_unique($service_category_exact));

//            }

            $service_level_data['service_category_exact'] = array_merge($service_level_data['service_category_exact'], $service_category_exact);
            $service_level_data['service_category_synonyms'] = array_merge($service_level_data['service_category_synonyms'], $service_cat);
            $arr_service_category_exact = array_merge($arr_service_category_exact,$service_category_exact);
            $arr_service_cat_synonyms = array_merge($arr_service_cat_synonyms,$service_cat);


            if(isset($serv['trialschedules'])){
                $trialschedules = $serv['trialschedules'];

                foreach ($trialschedules as $trial) {
                   //echo json_encode($trial);
                 array_push($weekdays, $trial['weekday']);
                 array_push($service_level_data['day'], $trial['weekday']);

                        foreach ($trial['slots'] as $slot) {
                            array_push($trial_slots, array('day' => $trial['weekday'], 'start' => intval($slot['start_time_24_hour_format']), 'end' => intval($slot['end_time_24_hour_format'])));
                            // array_push($service_level_data['day'], $trial['weekday']);
                            array_push($service_level_data['start'], intval($slot['start_time_24_hour_format']));
                            array_push($service_level_data['end'], intval($slot['end_time_24_hour_format']));
                        }
                    }
                }

                (isset($serv['vip_trial']) && $serv['vip_trial'] == "1") ? $data['vip_trial'] = (int) $serv['vip_trial']  : null;
            }
            if (sizeof($trial_slots) > 0) {
                $service_level_data['slots_nested'] = $trial_slots;
                array_push($service_level_data_all, $service_level_data);
            }
                $data['service_category_exact']= $arr_service_category_exact;
                $data['service_category_synonyms']= $arr_service_cat_synonyms;
                $data['service_category_snow']= $arr_service_cat_synonyms;
        $locationtag_object = array();
        foreach($data['locationtags'] as $loc){
             array_push($locationtag_object,array("name" => $loc['name'],"slug" => $loc['slug']));
        }
        $postfields_data = array(
            '_id'                           =>      $data['_id'],
            'alias'                         =>      (isset($data['alias']) && $data['alias'] != '') ? strtolower($data['alias']) : "",
            'average_rating'                =>      (isset($data['average_rating']) && $data['average_rating'] != '') ? round($data['average_rating'],1) : 0,
            'membership_discount'           =>      "",
            'country'                       =>      (isset($data['country']['name']) && $data['country']['name'] != '') ? strtolower($data['country']['name']) : "",
            'city'                          =>      (isset($data['city']['name']) && $data['city']['name'] != '') ? $data['city_id'] == 10000 ? strtolower($data['custom_city']) : strtolower($data['city']['name']) : "",
            'city_id'                       =>      (isset($data['city_id']) && $data['city_id'] != '') ? strtolower($data['city_id']) : 1,
            'info_service'                  =>      (isset($data['info']['service']) && $data['info']['service'] != '') ? $data['info']['service'] : "",
            'info_service_snow'             =>      (isset($data['info']['service']) && $data['info']['service'] != '') ? $data['info']['service'] : "",
            'info_service_list'             =>      $info_service_list,
            'category'                      =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",
            'category_snow'                 =>      (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",
                // 'category_metatitle'            =>      (isset($data['category']['meta']['title']) && $data['category']['meta']['title'] != '') ? strtolower($data['category']['meta']['title']) : "",
                // 'category_metadescription'      =>      (isset($data['category']['meta']['description']) && $data['category']['meta']['description'] != '') ? strtolower($data['category']['meta']['description']) : "",
            'categorytags'                  =>      (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower',array_pluck($data['categorytags'],'name')) : array(),
            'categorytags_snow'             =>      (isset($data['categorytags']) && !empty($data['categorytags'])) ? array_map('strtolower',array_pluck($data['categorytags'],'name')) : array(),
            'contact'                       =>      (isset($data['contact'])) ? $data['contact'] : '',
            'finder_type'                   =>      (isset($data['finder_type'])) ? $data['finder_type'] : '',
            'commercial_type'               =>      (isset($data['commercial_type'])) ? $data['commercial_type'] : '',
            'business_type'                 =>      (isset($data['business_type'])) ? $data['business_type'] : '',
            'fitternityno'                  =>      (isset($data['fitternityno'])) ? $data['fitternityno'] : '',
            'facilities'                    =>      (isset($data['facilities']) && !empty($data['facilities'])) ? array_map('strtolower',array_pluck($data['facilities'],'name')) : array(),
            'facilities_snow'               =>      (isset($data['facilities']) && !empty($data['facilities'])) ? array_map('strtolower',array_pluck($data['facilities'],'name')) : array(),
            'logo'                          =>      (isset($data['logo'])) ? $data['logo'] : '',
            'location'                      =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? $data['city_id'] == 10000 ? strtolower($data['custom_location']) : strtolower($data['location']['name']) : array(),
            'location_snow'                 =>      (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : array(),
            'locationtags'                  =>      (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower',array_pluck($data['locationtags'],'name')) : array(),
            'locationtags_slug'             =>      (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower',array_pluck($data['locationtags'],'slug')) : array(),
            'locationtags_snow'             =>      (isset($data['locationtags']) && !empty($data['locationtags'])) ? array_map('strtolower',array_pluck($data['locationtags'],'name')) : array(),
            'geolocation'                   =>      array('lat' => $data['lat'],'lon' => $data['lon']),
            'offerings'                     =>      (isset($data['offerings']) && !empty($data['offerings'])) ? array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))) : array(),
            'offerings_snow'                =>      (isset($data['offerings']) && !empty($data['offerings'])) ? array_values(array_unique(array_map('strtolower',array_pluck($data['offerings'],'name')))) : array(),
            'price_range'                   =>      (isset($data['price_range']) && $data['price_range'] != '') ? $data['price_range'] : "",
            'popularity'                    =>      (isset($data['popularity']) && $data['popularity'] != '' ) ? $data['popularity'] : 0,
            'special_offer_title'           =>      (isset($data['special_offer_title']) && $data['special_offer_title'] != '') ? $data['special_offer_title'] : "",
            'slug'                          =>      (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",
            'status'                        =>      (isset($data['status']) && $data['status'] != '') ? $data['status'] : "",
            'title'                         =>      (isset($data['title']) && $data['title'] != '') ? strtolower($data['title']) : "",
            'title_snow'                    =>      (isset($data['title']) && $data['title'] != '') ? strtolower($data['title']) : "",
            'title_show'                    =>      (isset($data['title']) && $data['title'] != '') ? $data['title'] : "",
            'total_rating_count'            =>      (isset($data['total_rating_count']) && $data['total_rating_count'] != '') ? $data['total_rating_count'] : 0,
            'views'                         =>      (isset($data['views']) && $data['views'] != '') ? $data['views'] : 0,
            'created_at'                    =>      (isset($data['created_at']) && $data['created_at'] != '') ? $data['created_at'] : "",
            'updated_at'                    =>      (isset($data['updated_at']) && $data['updated_at'] != '') ? $data['updated_at'] : "",
            'instantbooktrial_status'       =>      (isset($data['instantbooktrial_status']) && $data['instantbooktrial_status'] != '') ? intval($data['instantbooktrial_status']) : 0,
            'photos'                        =>      (isset($data['photos']) && $data['photos'] != '') ? array_map('strtolower', array_pluck($data['photos'],'url')) : array(),
            'locationcluster'               =>      $locationcluster,
            'locationcluster_snow'          =>      $locationcluster,
            'price_rangeval'                =>      $rangeval,
            'manual_trial_bool'             =>      (isset($data['manual_trial_enable'])&&($data['manual_trial_enable'] !== '')) ? $data['manual_trial_enable'] : '0',
            'servicelist'                   =>      $servicenamelist,
            'show_offers'                   =>      $offer_counter,
            'budget'                        =>      (isset($data['budget']) ? $data['budget'] : 0),
            'ozonetelno'                    =>      (isset($data['ozonetelno']) && $data['ozonetelno'] != '') ? $data['ozonetelno'] : new stdClass(),
            'service_weekdays'              =>      $weekdays,
            'trials'                        =>      $trial_slots,
            'service_level_data'            =>      $service_level_data_all,
            'vip_trial'                     =>      isset($data['vip_trial']) ? $data['vip_trial'] : 0,
            'service_category_synonyms'     =>      (isset($data['service_category_synonyms'])&&($data['service_category_synonyms'] !== '')) ? $data['service_category_synonyms'] : array(),
            'service_category_exact'        =>      (isset($data['service_category_exact'])&&($data['service_category_exact'] !=='')) ? $data['service_category_exact'] : array(),
            'service_category_snow'         =>      (isset($data['service_category_snow']) && !empty($data['service_category_snow'])) ? $data['service_category_snow'] : array(),
            'brand_id' => isset($data['brand_id']) ? $data['brand_id'] : '',
            'brand' => (isset($data['brand']) && isset($data['brand']['name'])) ? $data['brand']['name'] : '',
            'finder_coverimage_webp' => (isset($data['coverimage']) && $data['coverimage'] != '') ? strtolower( substr($data['coverimage'], 0, -3)."webp"  ) : strtolower($data['finder_coverimage']),
            'finder_coverimage_color' => (isset($data['finder_coverimage_color']) && $data['finder_coverimage_color'] != "") ? $data['finder_coverimage_color'] : "",
            'multiaddress'            => (isset($data['multiaddress'])) ? $data['multiaddress'] : [],
            'location_obj'            => $locationtag_object,
            'main_location_obj'       => (isset($data['location'])) ? array("name"=>$data['location']['name'],"slug"=>$data['location']['slug'],"locationcluster"=>$locationcluster) : array(),
            'state'                   => (isset($data['flags']) && isset($data['flags']['state']) != '') ? $data['flags']['state'] : ""
                //'trialschedules'                =>      $trialdata,
            );

$postfields_data['coverimage']  =   ($data['coverimage'] != '') ? $data['coverimage'] : 'default/'.$data['category_id'].'-'.rand(1, 4).'.jpg';
$postfields_data['servicephotos'] = $picslist;

//            var_dump($postfields_data);exit();
            return $postfields_data;
        } catch
        (Exception $exception) {
            Log::error($exception);

            return array();
        }


    }
}


if (!function_exists('get_service_category_synonyms')) {
    function get_service_category_synonyms($service_category) {

        $synonyms_list = array('yoga' => 'yoga',
            'dance' => 'dance',
            'martial arts' => 'mma and kick boxing',
            'pilates' => 'pilates',
            'zumba' => 'zumba',
            'mat pilates' => 'pilates',
            'yoga' => 'yoga',
            'dance' => 'dance',
            'aqua fitness' => 'zumba',
            'cross functional training' => 'cross functional training',
            'group x training' => 'cross functional training',
            'combine training' => 'cross functional training',
            'trx training' => 'cross functional training',
            'less mills' => 'cross functional training',
            'cross training' => 'cross functional training',
            'calisthenics' => 'cross functional training',
            'reformer or stott pilates' => 'pilates',
            'kalaripayattu' => 'mma and kick boxing',
            'taekwondo' => 'mma and kick boxing',
            'karate' => 'mma and kick boxing',
            'mixed martial arts' => 'mma and kick boxing',
            'judo' => 'mma and kick boxing',
            'zumba classes' => 'zumba',
            'gym' => 'gyms',
            'kids' => 'kids fitness',
            'aqua zumba' => 'zumba',
            'kung fu' => 'mma and kick boxing',
            'muay thai' => 'mma and kick boxing',
            'tai chi' => 'mma and kick boxing',
            'krav maga' => 'mma and kick boxing',
            'jujitsu' => 'mma and kick boxing',
            'kick boxing' => 'mma and kick boxing',
            'capoeira' => 'mma and kick boxing',
            'masala bhangra' => 'dance',
            'bollywood' => 'dance',
            'tango' => 'dance',
            'jazz' => 'dance',
            'waltz' => 'dance',
            'samba' => 'dance',
            'ballroom' => 'dance',
            'tango' => 'dance',
            'jazz' => 'dance',
            'waltz' => 'dance',
            'samba' => 'dance',
            'ballroom' => 'dance',
            'cha cha cha' => 'dance',
            'locking & popping' => 'dance',
            'salsa' => 'dance',
            'bharatanatyam' => 'dance',
            'hip hop' => 'dance',
            'ballet' => 'dance',
            'b boying' => 'dance',
            'rock n roll' => 'dance',
            'krumping' => 'dance',
            'paso doble' => 'dance',
            'zouk ' => 'dance',
            'odissi' => 'dance',
            'bachata' => 'dance',
            'jive' => 'dance',
            'rumba' => 'dance',
            'belly dancing' => 'dance',
            'bollydancing' => 'dance',
            'freestyle' => 'dance',
            'contemporary' => 'dance',
            'kathak' => 'dance',
            'bokwa' => 'dance',
            'folka' => 'dance',
            'EDM' => 'dance',
            'dancersize' => 'dance',
            'robusfit' => 'cross functional training',
            'dancethon' => 'dance',
            'power moves' => 'cross functional training',
            'doonya' => 'dance',
            'functional training' => 'cross functional training',
            'Bodycombat,cross functional training',
            'Boddyattack,cross functional training',
            'Bodypump,cross functional training',
            'Bodyjam,cross functional training',
            'Bodybalance,cross functional training',
            'Cardio Circuit,cross functional training',
            'mambo' => 'dance',
            'iyengar yoga' => 'yoga',
            'restorative yoga' => 'yoga',
            'hatha yoga' => 'yoga',
            'classical hatha yoga', 'yoga',
            'hatha flow yoga' => 'yoga',
            'gym' => 'gym',
            'circuit interval / boot camp' => 'cross functional training',
            'aerobics' => 'aerobics',
            'spinning' => 'spinning and indoor cycling',
            'power yoga' => 'yoga',
            'house' => 'dance',
            'kizomba' => 'dance',
            'kids fitness' => 'kids fitness',
            'healthy tiffins' => 'healthy tiffins',
            'pachanga' => 'yoga',
            'ashtanga yoga' => 'yoga',
            'vinyasa yoga' => 'yoga',
            'hatha vinyasa' => 'yoga',
            'meditation' => 'yoga',
            'prenatal' => 'yoga',
            'sivananda' => 'yoga',
            'dance fitness' => 'dance',
            'latin' => 'dance',
            'choreography level' => 'dance',
            'technical level' => 'dance',
            'kids classes (3yrs to 6yrs)' => 'kids fitness',
            'kids classes (7yrs to 11yrs)' => 'kids fitness',
            'salsa & bachata' => 'dance',
            'Folk dances' => 'dance',
            'pre ballet' => 'dance',
            'pranayama' => 'yoga',
            'hot yoga' => 'yoga',
            'crossfit' => 'crossfit',
            'kids yoga' => 'kids fitness',
            'marathon training' => 'marathon training',
            'traditional yoga' => 'yoga',
            'kettlebell workout,cross functional training',
            'aerial silk,cross functional training',
            'strength & flexibility,cross functional training',
            'danzo-fit' => 'dance',
            'swimming' => 'swimming',
            'tabata classes' => 'cross functional training',
            'spartacus training' => 'cross functional training',
            'military training' => 'cross functional training',
            'dynamic yoga' => 'yoga',
            'yoga stretching' => 'yoga',
            'glute camp' => 'cross functional training',
            'functional fitness and core conditioning' => 'cross functional training',
            'zumba step' => 'zumba',
            'zumba toning' => 'zumba',
            'piloxing' => 'cross functional training',
            'pilates swiss ball' => 'cross functional training',
            'xxx - training' => 'cross functional training',
            'tranceletics' => 'cross functional training',
            'hard core and booty program' => 'cross functional training',
            'boxing' => 'mma and kick boxing',
            'wrestling / grappling' => 'mma and kick boxing',
            'conditioning' => 'cross functional training',
            'sparring session' => 'mma and kick boxing',
            'flamenco' => 'dance',
            'aero attack' => 'cross functional training',
            'anti gravity yoga' => 'yoga',
            'kids dance classes' => 'kids fitness',
            'altitude training' => 'cross functional training',
            'kids fitness' => 'kids fitness',
            'kuchipudi' => 'dance',
            'wrudo' => 'mma and kick boxing',
            'fight yoga' => 'yoga',
            'brazilian jiu-jitsu' => 'mma and kick boxing',
            'sparring' => 'mma and kick boxing',
            'altitude training' => 'cross functional training',
            'altitude training' => 'cross functional training',
            'fitness studio' => 'fitness studios',
            'combine training' => 'cross functional training',
            'hiit (high intensity training)' => 'cross functional training',
            'parkour' => 'cross functional training',
            'yogalates' => 'yoga',
            'healthy snacks & beverages' => 'healthy snacks and beverages',
            'baked treats' => 'healthy snacks and beverages',
            'dietitians and nutritionists' => 'dietitians and nutritionists',
            'nutrition counselling' => 'dietitians and nutritionists',
            'breakfast cereals' => 'healthy snacks and beverages',
            'cold pressed juices' => 'healthy snacks and beverages',
            'energy or granola bars' => 'healthy snacks and beverages',
            'snack box' => 'healthy snacks and beverages',
            'dry fruits & seed mixes' => 'healthy snacks and beverages',
            'savouries' => 'healthy snacks and beverages',
            'dips & sauces' => 'healthy snacks and beverages',
            'salad' => 'healthy snacks and beverages',
            'vegetarian' => 'healthy tiffins',
            'non-vegetarian' => 'healthy tiffins',
            'milk' => 'healthy snacks and beverages',
            'personal trainers' => 'personal trainers',
            'combined training' => 'cross functional training',
            'fitness trainer' => 'personal trainers',
            'yoga trainer' => 'personal trainers',
            'anu chariya' => 'personal trainers'
        );

        if (array_key_exists($service_category, $synonyms_list)) {
            return $synonyms_list[$service_category];
        } else {
            return '';
        }

    }
}


if (!function_exists('get_elastic_finder_trialschedules')) {
    function get_elastic_finder_trialschedules($finderdata = array())
    {

        $data = $finderdata;
        $trialdata = [];
        try {

            if (!isset($data['services'])) {
                return [];
            } else {
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
        } catch (Swift_RfcComplianceException $exception) {
            Log::error($exception);
            return [];
        }

    }
}

if (!function_exists('get_elastic_service_documentv2')) {

    function get_elastic_service_documentv2($data = array(), $finderdata = array(), $locationcluster = '')
    {

        $servicedata = $data;

        $ratecards = $slots = array();
        if (!empty($servicedata['workoutsessionschedules'])) {

            $items = $servicedata['workoutsessionschedules'];

            foreach ($items as $key => $value) {

                if (!empty($items[$key]['slots'])) {

                    foreach ($items[$key]['slots'] as $k => $val) {

                        if ($value['weekday'] != '' && $val['start_time'] != '' && $val['start_time_24_hour_format'] != '' && $val['price'] != '') {
                            $newslot = ['start_time' => $val['start_time'],
                                'start_time_24_hour_format' => floatval(number_format($val['start_time_24_hour_format'], 2)),
                                'end_time' => $val['end_time'],
                                'end_time_24_hour_format' => floatval(number_format($val['end_time_24_hour_format'], 2)),
                                'price' => intval($val['price']),
                                'weekday' => $value['weekday']
                            ];

                            array_push($slots, $newslot);
                        }
                    }
                }
            }
        }

        $durationheader = '';
        $budgetheader = '';
        $headerarray = array();
        $flag1 = false;
        $servicemarketflag = 'n';

        if (isset($data['lat']) && $data['lat'] != '' && isset($data['lon']) && $data['lon'] != '') {
            $geolocation = array('lat' => $data['lat'], 'lon' => $data['lon']);

        } elseif (isset($finderdata['lat']) && $finderdata['lat'] != '' && isset($finderdata['lon']) && $finderdata['lon'] != '') {
            $geolocation = array('lat' => $finderdata['lat'], 'lon' => $finderdata['lon']);

        } else {

            $geolocation = '';
        }
        $comparer = 10000000;
        if (!$flag1) {
            foreach ($headerarray as $key => $val) {
                if (intval($val['budget']) < $comparer) {
                    $comparer = intval($val['budget']);
                }
            }
            foreach ($headerarray as $key => $value) {
                if (intval($value['budget']) === $comparer) {
                    $durationheader = $value['duration'];
                    $budgetheader = $value['budget'];
                }
            }
        }
        $cluster = array('suburb' => $locationcluster, 'locationtag' => array('loc' => (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : ""));
        $postfields_data = array(
            '_id' => $data['_id'],
            'category' => (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",
            'subcategory' => (isset($data['subcategory']['name']) && $data['subcategory']['name'] != '') ? strtolower($data['subcategory']['name']) : "",
            'geolocation' => $geolocation,
            'finder_id' => $data['finder_id'],
            'findername' => (isset($finderdata['title']) && $finderdata['title'] != '') ? strtolower($finderdata['title']) : "",
            'commercial_type' => (isset($finderdata['commercial_type']) && $finderdata['commercial_type'] != '') ? strtolower($finderdata['commercial_type']) : "",
            'finderslug' => (isset($finderdata['slug']) && $finderdata['slug'] != '') ? strtolower($finderdata['slug']) : "",
            'location' => (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
            'city' => (isset($finderdata['city']['name']) && $finderdata['city']['name'] != '') ? strtolower($finderdata['city']['name']) : "",
            'country' => (isset($finderdata['country']['name']) && $finderdata['country']['name'] != '') ? strtolower($finderdata['country']['name']) : "",
            'name' => (isset($data['name']) && $data['name'] != '') ? strtolower($data['name']) : "",
            'slug' => (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",
            'workout_intensity' => (isset($data['workout_intensity']) && $data['workout_intensity'] != '') ? strtolower($data['workout_intensity']) : "",
            'workout_tags' => (isset($data['workout_tags']) && !empty($data['workout_tags'])) ? array_map('strtolower', $data['workout_tags']) : "",
            'locationcluster' => $locationcluster,
            'workoutsessionschedules' => $slots,
            'ratecards' => $ratecards,
            'short_description' => (isset($data['short_description']) && $data['short_description'] != '') ? strtolower($data['short_description']) : "",
            'rating' => 0,
            'finder_coverimage' => (isset($finderdata['coverimage']) && $finderdata['coverimage'] != '') ? strtolower($finderdata['coverimage']) : strtolower($finderdata['finder_coverimage']),
            'finder_coverimage_webp' => (isset($finderdata['coverimage']) && $finderdata['coverimage'] != '') ? strtolower( substr($finderdata['coverimage'], 0, -3)."webp"  ) : strtolower($finderdata['finder_coverimage']),
            'finder_coverimage_color' => (isset($finderdata['finder_coverimage_color']) && $finderdata['finder_coverimage_color'] != "") ? $finderdata['finder_coverimage_color'] : "",
            'cluster' => $cluster,
            'durationheader' => $durationheader,
            'budgetheader' => intval($budgetheader),
            'sm_flagv1' => $servicemarketflag,
            'budgetfinder' => isset($finderdata['budget']) ? intval($finderdata['budget']) : 0,
            'finder_facilities' => (isset($finderdata['facilities']) && !empty($finderdata['facilities'])) ? array_map('strtolower', array_pluck($finderdata['facilities'], 'name')) : "",
            'finder_offerings' => (isset($finderdata['offerings']) && !empty($finderdata['offerings'])) ? array_values(array_unique(array_map('strtolower', array_pluck($finderdata['offerings'], 'name')))) : "",
            'finder_price_slab' => $finderdata['price_range'],
            'finder_slug' => $finderdata['slug'],
            'finder_gallary' => (isset($finderdata['photos'])) ? $finderdata['photos'] : array(),
            'finder_location' => (isset($finderdata['location']['name']) && $finderdata['location']['name'] != '') ? strtolower($finderdata['location']['name']) : "",
            'finder_locationtags' => (isset($finderdata['locationtags']) && !empty($finderdata['locationtags'])) ? array_map('strtolower', array_pluck($finderdata['locationtags'], 'name')) : "",
            'finder_category' => (isset($finderdata['category']['name']) && $finderdata['category']['name'] != '') ? strtolower($finderdata['category']['name']) : "",
            'finder_categorytags' => (isset($finderdata['categorytags']) && !empty($finderdata['categorytags'])) ? array_map('strtolower', array_pluck($finderdata['categorytags'], 'name')) : "",

        );

        return $postfields_data;

    }
}

if (!function_exists('get_elastic_service_workoutsession_schedules')) {

    function get_elastic_service_workoutsession_schedules($data = array(), $finderdata = array(), $locationcluster = '')
    {

        $data_array = array();

        $servicedata = $data;

        $ratecards = $slots = array();
        $ratecards = $servicedata['serviceratecard'];
        $durationheader = '';
        $budgetheader = '';
        $headerarray = array();
        $flag1 = false;
        $ratecard_id_workout = "";
        $ratecard_id_trial = "";
        $servicemarketflag = 'n';
        if(isset($ratecards) && count($ratecards) > 0){
            foreach($ratecards as $ratecard){
                if($ratecard['type'] == "trial"){
                    $ratecard_id_trial = $ratecard['_id'];
                }
                if($ratecard['type'] == "workout session"){
                    $ratecard_id_workout = $ratecard['_id'];
                }
            }
        }
        if (isset($data['lat']) && $data['lat'] != '' && isset($data['lon']) && $data['lon'] != '') {
            $geolocation = array('lat' => $data['lat'], 'lon' => $data['lon']);

        } elseif (isset($finderdata['lat']) && $finderdata['lat'] != '' && isset($finderdata['lon']) && $finderdata['lon'] != '') {
            $geolocation = array('lat' => $finderdata['lat'], 'lon' => $finderdata['lon']);

        } else {

            $geolocation = '';
        }
        $comparer = 10000000;

        if (!$flag1) {
            foreach ($headerarray as $key => $val) {
                if (intval($val['budget']) < $comparer) {
                    $comparer = intval($val['budget']);
                }
            }
            foreach ($headerarray as $key => $value) {
                if (intval($value['budget']) === $comparer) {
                    $durationheader = $value['duration'];
                    $budgetheader = $value['budget'];
                }
            }
        }

        if (!empty($servicedata['workoutsessionschedules'])) {

            $workout_session_schedules = $servicedata['workoutsessionschedules'];

            foreach ($workout_session_schedules as $key => $value) {

                $day = isset($value['weekday']) ? $value['weekday'] : '';

                if (!empty($workout_session_schedules[$key]['slots'])) {

                    foreach ($workout_session_schedules[$key]['slots'] as $k => $val) {

                        $cluster = array('suburb' => $locationcluster, 'locationtag' => array('loc' => (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : ""));

                        $postfields_data = array(
                            'service_id' => $data['_id'],
                            'category' => (isset($data['category']['name']) && $data['category']['name'] != '') ? strtolower($data['category']['name']) : "",
                            'subcategory' => (isset($data['subcategory']['name']) && $data['subcategory']['name'] != '') ? strtolower($data['subcategory']['name']) : "",
                            'geolocation' => $geolocation,
                            'finder_id' => $data['finder_id'],
                            'findername' => (isset($finderdata['title']) && $finderdata['title'] != '') ? strtolower($finderdata['title']) : "",
                            'commercial_type' => (isset($finderdata['commercial_type']) && $finderdata['commercial_type'] != '') ? strtolower($finderdata['commercial_type']) : "",
                            'finderslug' => (isset($finderdata['slug']) && $finderdata['slug'] != '') ? strtolower($finderdata['slug']) : "",
                            'location' => (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : "",
                            'city' => (isset($finderdata['city']['name']) && $finderdata['city']['name'] != '') ? strtolower($finderdata['city']['name']) : "",
                            'country' => (isset($finderdata['country']['name']) && $finderdata['country']['name'] != '') ? strtolower($finderdata['country']['name']) : "",
                            'name' => (isset($data['name']) && $data['name'] != '') ? strtolower($data['name']) : "",
                            'slug' => (isset($data['slug']) && $data['slug'] != '') ? $data['slug'] : "",
                            'workout_intensity' => (isset($data['workout_intensity']) && $data['workout_intensity'] != '') ? strtolower($data['workout_intensity']) : "",
                            'workout_tags' => (isset($data['workout_tags']) && !empty($data['workout_tags'])) ? array_map('strtolower', $data['workout_tags']) : "",
                            'locationcluster' => $locationcluster,
                            'workoutsessionschedules' => $slots,
                            'ratecards' => $ratecards,
                            'short_description' => (isset($data['short_description']) && $data['short_description'] != '') ? strtolower($data['short_description']) : "",
                            'rating' => 0,
                            'finder_coverimage' => (isset($finderdata['coverimage']) && $finderdata['coverimage'] != '') ? strtolower($finderdata['coverimage']) : strtolower($finderdata['finder_coverimage']),
                            'cluster' => $cluster,
                            'durationheader' => $durationheader,
                            'budgetheader' => intval($budgetheader),
                            'vip_trial_flag' => isset($data['vip_trial']) ? intval($data['vip_trial']) : 0,
                            'sm_flagv1' => $servicemarketflag,
                            'budgetfinder' => isset($finderdata['budget']) ? intval($finderdata['budget']) : 0,
                            'finder_facilities' => (isset($finderdata['facilities']) && !empty($finderdata['facilities'])) ? array_map('strtolower', array_pluck($finderdata['facilities'], 'name')) : "",
                            'finder_offerings' => (isset($finderdata['offerings']) && !empty($finderdata['offerings'])) ? array_values(array_unique(array_map('strtolower', array_pluck($finderdata['offerings'], 'name')))) : "",
                            'finder_price_slab' => $finderdata['price_range'],
                            'finder_slug' => $finderdata['slug'],
                            'finder_gallary' => (isset($finderdata['photos'])) ? $finderdata['photos'] : array(),
                            'finder_location' => (isset($finderdata['location']['name']) && $finderdata['location']['name'] != '') ? strtolower($finderdata['location']['name']) : "",
                            'finder_locationtags' => (isset($finderdata['locationtags']) && !empty($finderdata['locationtags'])) ? array_map('strtolower', array_pluck($finderdata['locationtags'], 'name')) : "",
                            'finder_category' => (isset($finderdata['category']['name']) && $finderdata['category']['name'] != '') ? strtolower($finderdata['category']['name']) : "",
                            'finder_categorytags' => (isset($finderdata['categorytags']) && !empty($finderdata['categorytags'])) ? array_map('strtolower', array_pluck($finderdata['categorytags'], 'name')) : "",
                            'workout_session_schedules_price' => (isset($val['price'])) ? intval($val['price']) : 0,
                            'workout_session_schedules_weekday' => $day,
                            'workout_session_schedules_end_time_24_hrs' => (isset($val['end_time_24_hour_format'])) ? floatval($val['end_time_24_hour_format']) : 0,
                            'workout_session_schedules_start_time_24_hrs' => (isset($val['start_time_24_hour_format'])) ? floatval($val['start_time_24_hour_format']) : 0,
                            'workout_session_schedules_end_time' => (isset($val['end_time'])) ? $val['end_time'] : '',
                            'workout_session_schedules_start_time' => (isset($val['start_time'])) ? $val['start_time'] : '',
                            'session_type' => (isset($data['session_type'])) ? $data['session_type'] : '',
                            'finder_address' => (isset($finderdata['contact']) && isset($finderdata['contact']['address'])) ? $finderdata['contact']['address'] : '',
                            'service_address' => (isset($data['address'])) ? $data['address'] : '',
                            'city_id' => isset($finderdata['city_id']) ? intval($finderdata['city_id']) : 0,
                            'service_type' => 'workout_session',
                            'ratecard_id' => $ratecard_id_workout
                        );

                        array_push($data_array, $postfields_data);
                    }

                }

            }
        }

        if(!empty($servicedata['trialschedules'])){

            $workout_session_schedules = $servicedata['trialschedules'];

            foreach ($workout_session_schedules as $key => $value) {

                $day = isset($value['weekday']) ? $value['weekday'] : '';

                if(!empty($workout_session_schedules[$key]['slots'])){

                    foreach ($workout_session_schedules[$key]['slots'] as $k => $val) {

                        $cluster = array('suburb' => $locationcluster, 'locationtag' => array('loc' => (isset($data['location']['name']) && $data['location']['name'] != '') ? strtolower($data['location']['name']) : ""));        
                        
                        $postfields_data = array(
                            'service_id'                    =>      $data['_id'],            
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
                            'finder_coverimage'             =>      (isset($finderdata['coverimage']) && $finderdata['coverimage'] != '') ? strtolower($finderdata['coverimage']) : strtolower($finderdata['finder_coverimage']), 
                            'cluster'                       =>      $cluster,
                            'durationheader'                =>      $durationheader,
                            'budgetheader'                  =>      intval($budgetheader),
                            'vip_trial_flag'                =>      isset($data['vip_trial']) ? intval($data['vip_trial']) : 0,
                            'sm_flagv1'                     =>      $servicemarketflag,
                            'budgetfinder'                  =>      isset($finderdata['budget']) ? intval($finderdata['budget']) : 0,
                            'finder_facilities'             =>      (isset($finderdata['facilities']) && !empty($finderdata['facilities'])) ? array_map('strtolower',array_pluck($finderdata['facilities'],'name')) : "",
                            'finder_offerings'              =>      (isset($finderdata['offerings']) && !empty($finderdata['offerings'])) ? array_values(array_unique(array_map('strtolower',array_pluck($finderdata['offerings'],'name')))) : "",
                            'finder_price_slab'             =>      $finderdata['price_range'],           
                            'finder_slug'                   =>      $finderdata['slug'],
                            'finder_gallary'                =>      (isset($finderdata['photos'])) ? $finderdata['photos'] : array(),                          
                            'finder_location'               =>      (isset($finderdata['location']['name']) && $finderdata['location']['name'] != '') ? strtolower($finderdata['location']['name']) : "",
                            'finder_locationtags'           =>      (isset($finderdata['locationtags']) && !empty($finderdata['locationtags'])) ? array_map('strtolower',array_pluck($finderdata['locationtags'],'name')) : "",
                            'finder_category'               =>      (isset($finderdata['category']['name']) && $finderdata['category']['name'] != '') ? strtolower($finderdata['category']['name']) : "", 
                            'finder_categorytags'           =>      (isset($finderdata['categorytags']) && !empty($finderdata['categorytags'])) ? array_map('strtolower',array_pluck($finderdata['categorytags'],'name')) : "",
                            'workout_session_schedules_price'     =>  (isset($val['price'])) ? intval($val['price']) : 0,
                            'workout_session_schedules_weekday'     =>  $day,
                            'workout_session_schedules_end_time_24_hrs'     =>  (isset($val['end_time_24_hour_format'])) ? floatval($val['end_time_24_hour_format']) : 0,
                            'workout_session_schedules_start_time_24_hrs'     =>  (isset($val['start_time_24_hour_format'])) ? floatval($val['start_time_24_hour_format']) : 0,
                            'workout_session_schedules_end_time' => (isset($val['end_time'])) ? $val['end_time'] : '',
                            'workout_session_schedules_start_time' => (isset($val['start_time'])) ? $val['start_time'] : '',
                            'session_type' => (isset($data['session_type'])) ? $data['session_type'] : '',
                            'finder_address' => (isset($finderdata['contact'])&& isset($finderdata['contact']['address'])) ? $finderdata['contact']['address'] : '',
                            'service_address' => (isset($data['address'])) ? $data['address'] : '',
                            'city_id' => isset($finderdata['city_id']) ? intval($finderdata['city_id']) : 0,
                            'service_type' => 'trial',
                            'ratecard_id' => $ratecard_id_trial
                            );

                        array_push($data_array, $postfields_data);
                    }

                }

            }
        }

        return $data_array;
    }
}


if (!function_exists('get_elastic_service_sale_ratecards')) {

    function get_elastic_service_sale_ratecards($servicedata = array(), $finderdata = array(), $locationcluster = '')
    {
        $postfields_data = array();
        $geolocation = '';

        if (isset($servicedata['lat']) && $servicedata['lat'] != '' && isset($servicedata['lon']) && $servicedata['lon'] != '') {
            $geolocation = array('lat' => $servicedata['lat'], 'lon' => $servicedata['lon']);

        } elseif (isset($finderdata['lat']) && $finderdata['lat'] != '' && isset($finderdata['lon']) && $finderdata['lon'] != '') {
            $geolocation = array('lat' => $finderdata['lat'], 'lon' => $finderdata['lon']);
        }

        $sale_ratecards = array();

        if (!empty($servicedata['serviceratecard'])) {

            $ratecards = $servicedata['serviceratecard'];
            $sale_ratecards = array_values(
                array_where($ratecards, function ($key, $ratecard) {

                    if(isset($ratecard['type']) && ($ratecard['type'] == 'membership' || $ratecard['type'] == 'packages') && (isset($ratecard['direct_payment_enable']) && $ratecard['direct_payment_enable'] == '1')){
                        return $ratecard;
                    }

                    if(isset($ratecard['type']) && $ratecard['type'] == 'trial' && isset($finderdata['commercial_type']) && $finderdata['commercial_type'] != 0){
                        return $ratecard;
                    }

                    /*if (((isset($ratecard['monsoon_sale_enable']) && $ratecard['monsoon_sale_enable'] == '1') || (isset($ratecard['direct_payment_enable']) && $ratecard['direct_payment_enable'] == '1')) && (isset($ratecard['type']) && ($ratecard['type'] == 'membership' || $ratecard['type'] == 'packages' || $ratecard['type'] == 'trial'))) {
                        return $ratecard;
                    }*/
                })
            );
        }

        $monsoon_sale_enable = in_array('1', array_fetch($sale_ratecards, 'monsoon_sale_enable')) ? '1' : '0';

        if (count($sale_ratecards) > 0) {
            $cluster = array('suburb' => $locationcluster, 'locationtag' => array('loc' => (isset($servicedata['location']['name']) && $servicedata['location']['name'] != '') ? strtolower($servicedata['location']['name']) : ""));
            $postfields_data = array(
                'sale_ratecards' => $sale_ratecards,
                'monsoon_sale_enable' => $monsoon_sale_enable,
                'service_id' => $servicedata['_id'],
                'category' => (isset($servicedata['category']['name']) && $servicedata['category']['name'] != '') ? strtolower($servicedata['category']['name']) : "",
                'subcategory' => (isset($servicedata['subcategory']['name']) && $servicedata['subcategory']['name'] != '') ?  trim(strtolower($servicedata['subcategory']['name'])) : "",
                'geolocation' => $geolocation,
                'finder_id' => $servicedata['finder_id'],
                'findername' => (isset($finderdata['title']) && $finderdata['title'] != '') ? strtolower($finderdata['title']) : "",
                'commercial_type' => (isset($finderdata['commercial_type']) && $finderdata['commercial_type'] != '') ? strtolower($finderdata['commercial_type']) : "",
                'finderslug' => (isset($finderdata['slug']) && $finderdata['slug'] != '') ? strtolower($finderdata['slug']) : "",
                'location' => (isset($servicedata['location']['name']) && $servicedata['location']['name'] != '') ? strtolower($servicedata['location']['name']) : "",
                'city' => (isset($finderdata['city']['name']) && $finderdata['city']['name'] != '') ? strtolower($finderdata['city']['name']) : "",
                'country' => (isset($finderdata['country']['name']) && $finderdata['country']['name'] != '') ? strtolower($finderdata['country']['name']) : "",
                'name' => (isset($servicedata['name']) && $servicedata['name'] != '') ? strtolower($servicedata['name']) : "",
                'slug' => (isset($servicedata['slug']) && $servicedata['slug'] != '') ? $servicedata['slug'] : "",
                'workout_intensity' => (isset($servicedata['workout_intensity']) && $servicedata['workout_intensity'] != '') ? strtolower($servicedata['workout_intensity']) : "",
                'workout_tags' => (isset($servicedata['workout_tags']) && !empty($servicedata['workout_tags'])) ? array_map('strtolower', $servicedata['workout_tags']) : "",
                'locationcluster' => $locationcluster,
                'short_description' => (isset($servicedata['short_description']) && $servicedata['short_description'] != '') ? strtolower($servicedata['short_description']) : "",
                'rating' => 0,
                'finder_coverimage' => (isset($finderdata['coverimage']) && $finderdata['coverimage'] != '') ? strtolower($finderdata['coverimage']) : strtolower($finderdata['finder_coverimage']),
                'cluster' => $cluster,
                'budgetfinder' => isset($finderdata['budget']) ? intval($finderdata['budget']) : 0,
                'finder_facilities' => (isset($finderdata['facilities']) && !empty($finderdata['facilities'])) ? array_map('strtolower', array_pluck($finderdata['facilities'], 'name')) : "",
                'finder_offerings' => (isset($finderdata['offerings']) && !empty($finderdata['offerings'])) ? array_values(array_unique(array_map('strtolower', array_pluck($finderdata['offerings'], 'name')))) : "",
                'finder_price_slab' => $finderdata['price_range'],
                'finder_slug' => $finderdata['slug'],
                'finder_gallary' => (isset($finderdata['photos'])) ? $finderdata['photos'] : array(),
                'finder_location' => (isset($finderdata['location']['name']) && $finderdata['location']['name'] != '') ? strtolower($finderdata['location']['name']) : "",
                'finder_locationtags' => (isset($finderdata['locationtags']) && !empty($finderdata['locationtags'])) ? array_map('strtolower', array_pluck($finderdata['locationtags'], 'name')) : "",
                'finder_category' => (isset($finderdata['category']['name']) && $finderdata['category']['name'] != '') ? strtolower($finderdata['category']['name']) : "",
                'finder_categorytags' => (isset($finderdata['categorytags']) && !empty($finderdata['categorytags'])) ? array_map('strtolower', array_pluck($finderdata['categorytags'], 'name')) : "",
                'session_type' => (isset($servicedata['session_type'])) ? $servicedata['session_type'] : '',
                'finder_address' => (isset($finderdata['contact']) && isset($finderdata['contact']['address'])) ? $finderdata['contact']['address'] : '',
                'service_address' => (isset($servicedata['address'])) ? $servicedata['address'] : '',
                'city_id' => isset($finderdata['city_id']) ? intval($finderdata['city_id']) : 0,
                'meal_type' => (isset($servicedata['meal_type']) && $servicedata['meal_type'] != '') ? $servicedata['meal_type'] : "",
                'short_description' => (isset($servicedata['short_description']) && $servicedata['short_description'] != '') ? $servicedata['short_description'] : ""
            );
        }

        return $postfields_data;
    }
}


            if (!function_exists(('get_elastic_autosuggest_brandoutlets_doc'))) {

                function get_elastic_autosuggest_brandoutlets_doc($data)
                {

                    $autosuggestvalue = 'All ' . $data['brand_name'] . ' in ' . $data['city_name'];

                    $postfields_data = array(
                        'input' => $data['brand_name'],
                        'autosuggestvalue' => ucwords($autosuggestvalue),
                        'inputv2' => "",
                        'inputv3' => "",
                        'inputv4' => "",
                        'inputloc1' => "",
                        'inputloc2' => "",
                        'inputcat' => "",
                        'inputcat1' => "",
                        'city' => $data['city_name'],
                        'location' => "",
                        'type' => 'brand',
                        'slug' => "",
                        'geolocation' => array('lat' => 0.0, 'lon' => 0.0),
                        'brand_id' => $data['brand_id'],
                        'brand' => $data['brand_name'],
                        'outlets' => $data['outlets']
                    );


                    return $postfields_data;
                }
            }


            if (!function_exists(('get_elastic_autosuggest_catloc_doc'))) {

                function get_elastic_autosuggest_catloc_doc($cat, $loc, $string, $city, $cluster)
                {

                    $lat = isset($loc['lat']) ? floatval($loc['lat']) : 0.0;
                    $lon = isset($loc['lon']) ? floatval($loc['lon']) : 0.0;

                    if (($cat['name'] === 'yoga') || ($cat['name'] === 'dance') || ($cat['name'] === 'zumba')) {
                        $catname = $cat['name'] . ' classes sessions';
                    } else {
                        $catname = $cat['name'];
                    }

                    $postfields_data = array(
                        'input' => $catname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => $catname,
                        'inputv4' => "",
                        'inputloc1' => strtolower($loc['name']),
                        'inputloc2' => $cluster,
                        'inputcat' => $catname,
                        'inputcat1' => strtolower($cat['name']),
                        'city' => $city,
                        'location' => (isset($loc['name']) && $loc['name'] != '') ? $loc['name'] : "",
                        'type' => 'categorylocation',
                        'slug' => "",
                        'geolocation' => array('lat' => $lat, 'lon' => $lon),
                        'inputservicecat' => ''

                    );
                    return $postfields_data;
                }

            }


            if (!function_exists(('get_elastic_autosuggest_servicecatloc_doc'))) {

                function get_elastic_autosuggest_servicecatloc_doc($servicecategory, $location, $string, $city, $cluster)
                {


                    $lat = 0.0;
                    $lon = 0.0;

                    if (($servicecategory === 'yoga') || ($servicecategory === 'dance') || ($servicecategory === 'zumba')) {
                        $servicecatname = $servicecategory . ' classes sessions';
                    } else {
                        $servicecatname = $servicecategory;
                    }

                    $postfields_data = array(
                        'input' => $servicecatname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => $servicecatname,
                        'inputv4' => "",
                        'inputloc1' => strtolower($location),
                        'inputloc2' => $cluster,
                        'inputcat' => "",
                        'inputcat1' => "",
                        'inputservicecat' => $servicecatname,
                        'city' => $city,
                        'location' => (isset($location) && $location != '') ? $location : "",
                        'type' => 'servicecategorylocation',
                        'slug' => "",
                        'geolocation' => array('lat' => $lat, 'lon' => $lon)
                    );
                    return $postfields_data;
                }

            }

            if (!function_exists(('get_elastic_autosuggest_catfac_doc'))) {

                function get_elastic_autosuggest_catfac_doc($cat, $fac, $string, $city)
                {

                    if (($cat['name'] === 'yoga') || ($cat['name'] === 'dance') || ($cat['name'] === 'zumba')) {
                        $catname = $cat['name'] . ' classes sessions';
                    } else {
                        $catname = $cat['name'];
                    }
                    $postfields_data = array(
                        'input' => $catname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => "",
                        'inputv4' => strtolower($fac),
                        'inputloc1' => "",
                        'inputloc2' => "",
                        'inputcat' => $catname,
                        'inputcat1' => strtolower($catname),
                        'city' => $city,
                        'location' => "",
                        'type' => 'categoryfacility',
                        'slug' => "",
                        'geolocation' => array('lat' => 0, 'lon' => 0),
                        'inputservicecat' => ''
                    );
                    return $postfields_data;
                }
            }

            if (!function_exists(('get_elastic_autosuggest_catoffer_doc'))) {

                function get_elastic_autosuggest_catoffer_doc($cat, $off, $string, $city, $offrank)
                {

                    if (($cat['name'] === 'yoga') || ($cat['name'] === 'dance') || ($cat['name'] === 'zumba')) {
                        $catname = $cat['name'] . ' classes sessions';
                    } else {
                        $catname = $cat['name'];
                    }

                    $postfields_data = array(
                        'input' => $catname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => strtolower($off['name'] . ' ' . $catname),
                        'inputv4' => "",
                        'inputloc1' => "",
                        'inputloc2' => "",
                        'inputcat' => $catname,
                        'inputcat1' => strtolower($catname),
                        'city' => $city,
                        'location' => "",
                        'type' => 'categoryoffering',
                        'slug' => "",
                        'geolocation' => array('lat' => 0, 'lon' => 0),
                        'offeringrank' => $offrank
                    );
                    return $postfields_data;
                }
            }

            if (!function_exists(('get_elastic_autosuggest_catlocoffer_doc'))) {

                function get_elastic_autosuggest_catlocoffer_doc($cat, $off, $loc, $string, $city, $cluster, $offrank)
                {

                    if (($cat['name'] === 'yoga') || ($cat['name'] === 'dance') || ($cat['name'] === 'zumba')) {
                        $catname = $cat['name'] . ' classes sessions';
                    } else {
                        $catname = $cat['name'];
                    }
                    $lat = isset($loc['lat']) ? floatval($loc['lat']) : 0.0;
                    $lon = isset($loc['lon']) ? floatval($loc['lon']) : 0.0;
                    $postfields_data = array(
                        'input' => $catname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => strtolower($off['name'] . ' ' . $catname),
                        'inputv4' => "",
                        'inputloc1' => strtolower($loc['name']),
                        'inputloc2' => $cluster,
                        'inputcat' => $catname,
                        'inputcat1' => strtolower($catname),
                        'city' => $city,
                        'location' => strtolower($loc['name']),
                        'type' => 'categorylocationoffering',
                        'slug' => "",
                        'geolocation' => array('lat' => $lat, 'lon' => $lon),
                        'offeringrank' => $offrank,
                        'inputservicecat' => ''
                    );
                    return $postfields_data;
                }
            }

            if (!function_exists(('get_elastic_autosuggest_catlocfac_doc'))) {

                function get_elastic_autosuggest_catlocfac_doc($cat, $fac, $loc, $string, $city, $cluster)
                {

                    if (($cat['name'] === 'yoga') || ($cat['name'] === 'dance') || ($cat['name'] === 'zumba')) {
                        $catname = $cat['name'] . ' classes sessions';
                    } else {
                        $catname = $cat['name'];
                    }
                    $lat = isset($loc['lat']) ? floatval($loc['lat']) : 0.0;
                    $lon = isset($loc['lon']) ? floatval($loc['lon']) : 0.0;
                    $postfields_data = array(
                        'input' => $catname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => "",
                        'inputv4' => strtolower($fac),
                        'inputloc1' => strtolower($loc['name']),
                        'inputloc2' => $cluster,
                        'inputcat' => $catname,
                        'inputcat1' => strtolower($catname),
                        'city' => $city,
                        'location' => strtolower($loc['name']),
                        'type' => 'categorylocationfacilities',
                        'slug' => "",
                        'geolocation' => array('lat' => $lat, 'lon' => $lon),
                        'inputservicecat' => ''
                    );
                    return $postfields_data;
                }
            }

            if (!function_exists(('get_elastic_autosuggest_catcity_doc'))) {

                function get_elastic_autosuggest_catcity_doc($cat, $city, $string)
                {

                    if (($cat['name'] === 'yoga') || ($cat['name'] === 'dance') || ($cat['name'] === 'zumba')) {
                        $catname = $cat['name'] . ' classes sessions';
                    } else {
                        $catname = $cat['name'];
                    }
                    $postfields_data = array(
                        'input' => $catname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => $catname,
                        'inputv4' => "",
                        'inputloc1' => $city,
                        'inputloc2' => "",
                        'inputcat' => $catname,
                        'inputcat1' => strtolower($cat['name']),
                        'city' => $city,
                        'location' => "",
                        'type' => 'categorycity',
                        'slug' => "",
                        'geolocation' => array('lat' => 0.0, 'lon' => 0.0)
                    );
                    return $postfields_data;
                }
            }


            if (!function_exists(('get_elastic_autosuggest_servicecatcity_doc'))) {

                function get_elastic_autosuggest_servicecatcity_doc($subcat, $city, $string)
                {

                    if (($subcat === 'yoga') || ($subcat === 'dance') || ($subcat === 'zumba')) {
                        $subcatname = $subcat . ' classes sessions';
                    } else {
                        $subcatname = $subcat;
                    }
                    $postfields_data = array(
                        'input' => $subcatname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => $subcatname,
                        'inputv4' => "",
                        'inputloc1' => $city,
                        'inputloc2' => "",
                        'inputcat' => "",
                        'inputcat1' => "",
                        'city' => $city,
                        'location' => "",
                        'type' => 'servicecategorycity',
                        'slug' => "",
                        'geolocation' => array('lat' => 0.0, 'lon' => 0.0),
                        'inputservicecat' => $subcatname
                    );
                    return $postfields_data;
                }
            }

            if (!function_exists(('get_elastic_autosuggest_catcityoffer_doc'))) {

                function get_elastic_autosuggest_catcityoffer_doc($cat, $city, $string)
                {

                    if (($cat['name'] === 'yoga') || ($cat['name'] === 'dance') || ($cat['name'] === 'zumba')) {
                        $catname = $cat['name'] . ' classes sessions';
                    } else {
                        $catname = $cat['name'];
                    }
                    $postfields_data = array(
                        'input' => $catname,
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => $catname,
                        'inputv4' => "",
                        'inputloc1' => $city,
                        'inputloc2' => "",
                        'inputcat' => $catname,
                        'inputcat1' => strtolower($catname),
                        'city' => $city,
                        'location' => strtolower($city),
                        'type' => 'categorycity',
                        'slug' => "",
                        'geolocation' => array('lat' => 0.0, 'lon' => 0.0),
                        'inputservicecat' => ''
                    );
                    return $postfields_data;
                }
            }

            if (!function_exists(('get_elastic_autosuggest_allfitness_doc'))) {

                function get_elastic_autosuggest_allfitness_doc($loc, $city, $string)
                {

                    $lat = isset($loc['lat']) ? floatval($loc['lat']) : 0.0;
                    $lon = isset($loc['lon']) ? floatval($loc['lon']) : 0.0;

                    $postfields_data = array(
                        'input' => 'all fitness options ' . $loc['name'],
                        'autosuggestvalue' => $string,
                        'inputv2' => "",
                        'inputv3' => '',
                        'inputv4' => "",
                        'inputloc1' => $loc['name'],
                        'inputloc2' => "",
                        'inputcat' => '',
                        'inputcat1' => '',
                        'city' => $city,
                        'location' => strtolower($loc['name']),
                        'type' => 'allfitnesslocation',
                        'slug' => "",
                        'geolocation' => array('lat' => $lat, 'lon' => $lon),
                        'inputservicecat' => ''
                    );
                    return $postfields_data;
                }
            }

            if (!function_exists(('add_reg_id'))) {

                function add_reg_id($data)
                {

                    try {

                        $rules = [
                            'reg_id' => 'required',
                            'type' => 'required',
                        ];

                        $validator = Validator::make($data, $rules);

                        if ($validator->fails()) {

                            return array('status' => 400, 'message' => error_message($validator->errors()));
                        }

                        $device = Device::where('reg_id', $data['reg_id'])->orderBy("_id","DESC")->first();

                        if ($device) {

                            $device->customer_id = (isset($data['customer_id']) && $data['customer_id'] != '') ? (int)$data['customer_id'] : $device->customer_id;
                            $device->update();

                        } else {

                            $device_id = Device::max('_id') + 1;
                            $device = new Device();
                            $device->_id = $device_id;
                            $device->reg_id = $data['reg_id'];
                            $device->customer_id = (isset($data['customer_id']) && $data['customer_id'] != '') ? (int)$data['customer_id'] : '';
                            $device->type = $data['type'];
                            $device->status = "1";
                            $device->save();

                        }

                        $response = array('status' => 200, 'message' => 'success');

                    } catch (Exception $e) {

                        $message = array(
                            'type' => get_class($e),
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        );

                        $response = array('status' => 400, 'message' => $message['type'] . ' : ' . $message['message'] . ' in ' . $message['file'] . ' on ' . $message['line']);

                        Log::error($e);

                    }

                    return $response;
                }
            }

            if (!function_exists(('error_message'))) {

                function error_message($errors)
                {

                    $errors = json_decode(json_encode($errors));
                    $message = array();
                    foreach ($errors as $key => $value) {
                        $message[$key] = $value[0];
                    }

                    $message = implode(',', array_values($message));

                    return $message;
                }
            }


            if (!function_exists(('autoRegisterCustomer'))) {

                function autoRegisterCustomer($data)
                {

                    $customer = Customer::active()->where('email', $data['customer_email'])->first();

                    if (!$customer) {

                        $inserted_id = Customer::max('_id') + 1;
                        $customer = new Customer();
                        $customer->_id = $inserted_id;
                        $customer->name = ucwords($data['customer_name']);
                        $customer->email = $data['customer_email'];
                        $customer->dob = isset($data['dob']) ? $data['dob'] : "";
                        $customer->gender = isset($data['gender']) ? $data['gender'] : "";
                        $customer->fitness_goal = isset($data['fitness_goal']) ? $data['fitness_goal'] : "";
                        $customer->picture = "https://www.gravatar.com/avatar/" . md5($data['customer_email']) . "?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
                        $customer->password = md5(time());

                        if (isset($data['customer_phone']) && $data['customer_phone'] != '') {
                            $customer->contact_no = $data['customer_phone'];
                        }

                        if (isset($data['customer_address'])) {

                            if (is_array($data['customer_address']) && !empty($data['customer_address'])) {

                                $customer->address = implode(",", array_values($data['customer_address']));
                                $customer->address_array = $data['customer_address'];

                            } elseif (!is_array($data['customer_address']) && $data['customer_address'] != '') {

                                $customer->address = $data['customer_address'];
                            }

                        }

                        $customer->identity = 'email';
                        $customer->account_link = array('email' => 1, 'google' => 0, 'facebook' => 0, 'twitter' => 0);
                        $customer->status = "1";
                        $customer->ishulluser = 1;
                        $customer->old_customer = false;
                        $customer->demonetisation = time();
                        $customer->save();
                        registerMail($customer->_id);
                       

                        

                        // invalidateDuplicatePhones($data, $customer->toArray());

                        return $inserted_id;

                    } else {

                        $customerData = [];

                        try {

                            if (isset($data['customer_phone']) && $data['customer_phone'] != "" ) {

                                if(!isset($customer->contact_no) || $customer->contact_no == ''){
                                    
                                    $customerData['contact_no'] = trim($data['customer_phone']);

                                }

                            }

                            if (isset($data['otp']) && $data['otp'] != "") {
                                $customerData['contact_no_verify_status'] = "yes";
                            }

                            if (isset($data['gender']) && $data['gender'] != "") {
                                $customerData['gender'] = $data['gender'];
                            }

                            if (isset($data['customer_address'])) {

                                if (is_array($data['customer_address']) && !empty($data['customer_address'])) {

                                    $customerData['address'] = implode(",", array_values($data['customer_address']));
                                    $customerData['address_array'] = $data['customer_address'];

                                } elseif (!is_array($data['customer_address']) && $data['customer_address'] != '') {

                                    $customerData['address'] = $data['customer_address'];
                                }

                            }

                            if (count($customerData) > 0) {
                                $customer->update($customerData);
                            }

                        } catch (ValidationException $e) {

                            Log::error($e);

                        }

                        // invalidateDuplicatePhones($data, $customer->toArray());
                        
                        return $customer->_id;
                    }

                }

            }

            if(!function_exists('createCustomerToken')){
                function createCustomerToken($customer_id){
                    
                    $customer = Customer::find($customer_id);
                    $customer = array_except($customer->toArray(), array('password'));

                    $customer['name'] = (isset($customer['name'])) ? $customer['name'] : "";
                    $customer['email'] = (isset($customer['email'])) ? $customer['email'] : "";
                    $customer['picture'] = (isset($customer['picture'])) ? $customer['picture'] : "";
                    $customer['facebook_id'] = (isset($customer['facebook_id'])) ? $customer['facebook_id'] : "";
                    $customer['address'] = (isset($customer['address'])) ? $customer['address'] : "";
                    $customer['contact_no'] = (isset($customer['contact_no'])) ? $customer['contact_no'] : "";
                    $customer['location'] = (isset($customer['location'])) ? $customer['location'] : "";
                    $customer['extra']['mob'] = (isset($customer['contact_no'])) ? $customer['contact_no'] : "";
                    $customer['extra']['location'] = (isset($customer['location'])) ? $customer['location'] : "";
                    $customer['gender'] = (isset($customer['gender'])) ? $customer['gender'] : "";

                    $data = array(
                                '_id'=>$customer['_id'],
                                'name'=>$customer['name'],
                                "email"=>$customer['email'],
                                "picture"=>$customer['picture'],
                                'facebook_id'=>$customer['facebook_id'],
                                "identity"=>$customer['identity'],
                                "address"=>$customer['address'],
                                "contact_no"=>$customer['contact_no'],
                                "location"=>$customer['location'],
                                'gender'=>$customer['gender'],
                                'extra'=>array(
                                    'mob'=>$customer['extra']['mob'],
                                    'location'=>$customer['extra']['location']
                                )
                            ); 

                    $jwt_claim = array(
                        "iat" => Config::get('app.jwt.iat'),
                        "nbf" => Config::get('app.jwt.nbf'),
                        "exp" => Config::get('app.jwt.exp'),
                        "customer" => $data
                        );
                    
                    $jwt_key = Config::get('app.jwt.key');
                    $jwt_alg = Config::get('app.jwt.alg');

                    $token = JWT::encode($jwt_claim,$jwt_key,$jwt_alg);

                    return $token;
                }
            }

            if(!function_exists("genericGenerateOtp")){
                function genericGenerateOtp($length = 4){
                        $characters = '0123456789';
                        $result = '';
                        $charactersLength = strlen($characters);

                        for ($p = 0; $p < $length; $p++)
                        {
                            $result .= $characters[rand(0, $charactersLength - 1)];
                        }

                        return $result;
                }
            }

            if (!function_exists('csv_to_array')) {
                function csv_to_array($filename = '', $delimiter = ',')
                {
                    if (!file_exists($filename) || !is_readable($filename))
                        return FALSE;

                    $header = NULL;
                    $data = array();
                    if (($handle = fopen($filename, 'r')) !== FALSE) {
                        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                            if (!$header)
                                $header = $row;
                            else
                                $data[] = array_combine($header, $row);
                        }
                        fclose($handle);
                    }
                    return $data;
                }
            }

            if (!function_exists('img_name_kraken_url')) {
                function img_name_from_kraken_url($response)
                {
                    $imagename = last(explode('/', $response['kraked_url']));
                    return $imagename;
                }
            }

            if (!function_exists('upload_magic')) {
                function upload_magic($params = array())
                {

                    $headersparms = array("Cache-Control" => "max-age=2592000000");
                    $id = $params['id'];
                    $detail = $params['detail'];
                    $input_object = $params['input'];
                    $input_path = $input_object->getRealPath();
                    $input_realname = $input_object->getClientOriginalName();
                    $input_name = $id . "_" . time();
                    $input_ext = strtolower($input_object->getClientOriginalExtension());
                    $input_size = $input_object->getSize();
                    $input_mimetype = $input_object->getMimeType();
                    list($imagewidth, $imageheight) = getimagesize($input_path);

                    $upload_params = array(
                        "file" => $input_path,
                        "wait" => true,
                        "lossy" => true,
                        "s3_store" => array(
                            "key" => Config::get('app.aws.key'),
                            "secret" => Config::get('app.aws.secret'),
                            "bucket" => Config::get('app.aws.bucket'),
                            "region" => Config::get('app.aws.region'),
                            "headers" => $headersparms
                        )
                    );

                    $image_name = $input_name . "." . $input_ext;

                    $return['image_name'] = $image_name;
                    $response = array();

                    foreach ($detail as $value) {

                        $aws_bucketpath = $value['path'] . $image_name;
                        array_set($upload_params, 's3_store.path', $aws_bucketpath);
                        array_set($upload_params, 'resize', array("width" => $value['width'], "strategy" => "landscape"));
                        $kraken_data = \Kraken::upload($upload_params);
                        $kraken_data['type'] = $value['type'];
                        $kraken_data['folder_path'] = $value['path'];

                        $response[] = $kraken_data;
                    }

                    $return['response'] = $response;

                    return $return;

                }
            }


if (!function_exists(('error_message_array'))){

    function error_message_array($errors){

        $errors = json_decode(json_encode($errors));
        $message = array();
        foreach ($errors as $key => $value) {
            $message[$key] = $value[0];
        }

        return $message;
    }
}

if (!function_exists(('random_number_string'))){

    function random_number_string($length = 10)
    {      
        $characters = '0123456789ABCDEFGHIJKLMOPQRSTUVWXYZ';
        $result = '';
        $charactersLength = strlen($characters);

        for ($p = 0; $p < $length; $p++)
        {
            $result .= $characters[rand(0, $charactersLength - 1)];
        }

        return $result;
    }
}

if (!function_exists(('time_passed_check'))){

    function time_passed_check($servicecategory_id)
    {      
        $service_category_id = array(2,19,65);

        return (in_array((int)$servicecategory_id,$service_category_id)) ? 15*60 : 90*60 ;
    }
}


if (!function_exists(('newcategorymapping'))){

    function newcategorymapping($category)
    {      
        $category = strtolower($category);
        switch(true){
            case $category == "kids fitness" || $category == "kids fitness classes" :
                return $category == "kids fitness" ? "kids fitness classes" : "kids fitness";
                break;
            case $category == "zumba" || $category == "zumba classes" :
                return $category == "zumba" ? "zumba classes" : "zumba";
                break;
            case $category == "yoga" || $category == "yoga classes" :
                return $category == "yoga" ? "yoga classes" : "yoga";
                break;
            case $category == "cross functional training" || $category == "functional training" :
                return $category == "cross functional training" ? "functional training" : "cross functional training";
                break;
            case $category == "dance" || $category == "dance classes" :
                return $category == "dance" ? "dance classes" : "dance";
                break;
            case $category == "crossfit" || $category == "crossfit gym" :
                return $category == "crossfit" ? "crossfit gym" : "crossfit";
                break;
            case $category == "pilates" || $category == "pilates classes" :
                return $category == "pilates" ? "pilates classes" : "pilates";
                break;
            case $category == "mma and kick boxing" || $category == "mma and kick boxing classes" :
                return $category == "mma and kick boxing" ? "mma and kick boxing classes" : "mma and kick boxing";
                break;
            case $category == "spinning and indoor cycling" || $category == "spinning classes" :
                return $category == "spinning and indoor cycling" ? "spinning classes" : "spinning and indoor cycling";
                break;
            case $category == "swimming" || $category == "swimming pools" :
                return $category == "swimming" ? "swimming pools" : "swimming";
                break;
            case $category == "dietitians and nutritionists" || $category == "dietitians" :
                return $category == "dietitians and nutritionists" ? "dietitians" : "dietitians and nutritionists";
                break;
            case $category == "sport nutrition and supliment stores" || $category == "sport supliment stores" :
                return $category == "sport nutrition and supliment stores" ? "sport supliment stores" : "sport nutrition and supliment stores";
                break;
            case $category == "pre natal classes":
                return $category == "pre-natal classes";
                break;
            default: 
                return $category;

        }
    }
}

if (!function_exists(('getReversehash'))){
     function getReversehash($data){

        Log::info($data);

        if(Config::get('app.env') == 'stage'){
            $data['env'] = 1;
        }

        $env = (isset($data['env']) && $data['env'] == 1) ? "stage" : "production";

        $data['service_name'] = trim($data['service_name']);
        $data['finder_name'] = trim($data['finder_name']);

        $service_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", ' ', $data['service_name']);
        $finder_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", ' ', $data['finder_name']);

        $key = 'gtKFFx';
        $salt = 'eCwWELxi';

        if($env == "production"){
            $key = 'l80gyM';
            $salt = 'QBl78dtK';
        }
        if($data['txnid'] == ""){
            if($data["customer_source"] == "android" || $data["customer_source"] == "ios"){
                $data['txnid'] = "MFIT".$data["_id"];
            }else{
                $data['txnid'] = "FIT".$data["_id"];
            }
        }
        
        $txnid = $data['txnid'];
        $amount = $data['amount'].".00";
        $productinfo = $service_name." - ".$finder_name;
        $productinfo = $data['productinfo'] = trim(strtolower(substr($productinfo,0,100)));
        $firstname = strtolower($data['customer_name']);
        $email = strtolower($data['customer_email']);
        $udf1 = "";
        $udf2 = "";
        $udf3 = "";
        $udf4 = "";
        $udf5 = "";

        // if(($data['type'] == "booktrials" || $data['type'] == "workout-session" || $data['type'] == "healthytiffintrail") && $data['customer_source'] == "website"){
        //     $udf1 = $data['service_name'];
        //     // $udf2 = $data['type'] == "healthytiffintrail" ? $data['schedule_date'] : "";
        //     // $udf3 = $data['schedule_slot'];
        //     $udf4 = $data['finder_id'];
        // }

        $payhash_str = $salt.'|success||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
//    $payhash_str = "0|".$salt.'|success||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
        
        Log::info($payhash_str);
        $data['reverse_hash'] = hash('sha512', $payhash_str);        
        Log::info("Reverse Hash -- ".$data['reverse_hash']);
        return $data;
    }
}

if (!function_exists(('getHash'))){
    function getHash($data){

        $env = (isset($data['env']) && $data['env'] == 1) ? "stage" : "production";
        $data['service_name'] = trim($data['service_name']);
        $data['finder_name'] = trim($data['finder_name']);

        $service_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", ' ', strtolower($data['service_name']));
        $finder_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", ' ', strtolower($data['finder_name']));

        $key = 'gtKFFx';
        $salt = 'eCwWELxi';

        if($env == "production"){
            $key = 'l80gyM';
            $salt = 'QBl78dtK';
        }

        $txnid = $data['txnid'];
        $amount = $data['amount'];
        $productinfo = $service_name." - ".$finder_name;
        $productinfo = $data['productinfo'] = trim(strtolower(substr($productinfo,0,100)));
        $firstname = strtolower($data['customer_name']);
        $email = strtolower($data['customer_email']);
        $udf1 = "";
        $udf2 = "";
        $udf3 = "";
        $udf4 = "";
        $udf5 = "";

        $payhash_str = $key.'|'.$txnid.'|'.$amount.'|'.$productinfo.'|'.$firstname.'|'.$email.'|'.$udf1.'|'.$udf2.'|'.$udf3.'|'.$udf4.'|'.$udf5.'||||||'.$salt;
        
        Log::info($payhash_str);

        $data['payment_hash'] = hash('sha512', $payhash_str);

        $verify_str = $salt.'||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;

        $data['verify_hash'] = hash('sha512', $verify_str);

        $cmnPaymentRelatedDetailsForMobileSdk1              =   'payment_related_details_for_mobile_sdk';
        $detailsForMobileSdk_str1                           =   $key  . '|' . $cmnPaymentRelatedDetailsForMobileSdk1 . '|default|' . $salt ;
        $detailsForMobileSdk1                               =   hash('sha512', $detailsForMobileSdk_str1);
        $data['payment_related_details_for_mobile_sdk_hash'] =   $detailsForMobileSdk1;
        
        return $data;
    }
}

if (!function_exists(('getpayTMhash'))){
     function getpayTMhash($data){
// Log::info($data);

        $data['service_name'] = trim($data['service_name']);
        $data['finder_name'] = trim($data['finder_name']);

        $service_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", '', strtolower($data['service_name']));
        $finder_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", '', strtolower($data['finder_name']));

        $productinfo = $service_name." - ".$finder_name;
        $productinfo = $data['productinfo'] = strtolower(substr($productinfo,0,100));

        $key = 'fitterKEY';
        $salt = '1086fit';
        
        $txnid = $data['txnid'];
        $amount = $data['amount'].".00";
        $firstname = strtolower($data['customer_name']);
        $email = strtolower($data['customer_email']);
        $udf1 = "";
        $udf2 = "";
        $udf3 = "";
        $udf4 = "";
        $udf5 = "";

        $payhash_str = $salt.'|success||||||'.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
//    $payhash_str = "0|".$salt.'|success||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
        
        // Log::info($payhash_str);
        $data['reverse_hash'] = hash('sha512', $payhash_str);        
        // Log::info($data['paytm_hash']);
        return $data;
    }
}

if (!function_exists(('customerTokenDecode'))){
    function customerTokenDecode($token){

        $jwt_token = $token;
        $jwt_key = Config::get('app.jwt.key');
        $jwt_alg = Config::get('app.jwt.alg');


        try{

            if(Cache::tags('blacklist_customer_token')->has($jwt_token)){
                Log::info("Yes1");
                return Response::json(array('status' => 400,'message' => 'User logged out'),400);
            }

            $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
            // Log::info($decodedToken);
            return $decodedToken;

        }catch(DomainException $e){
            Log::info("Yes3");
            return Response::json(array('status' => 400,'message' => 'Token incorrect, Please login again'),400);
        }catch(ExpiredException $e){

            JWT::$leeway = (86400*365);

            $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));
            Log::info("Yes4");
            return $decodedToken;
            
        }catch(SignatureInvalidException $e){
            Log::info("Yes5");
            return Response::json(array('status' => 400,'message' => 'Signature verification failed, Please login again'),400);
        }catch(Exception $e){
            Log::info("Yes6");
            return Response::json(array('status' => 400,'message' => 'Token incorrect, Please login again'),400);
        }
    }
}

if (!function_exists(('getFinderType'))){
    function getFinderType($id){
        if($id == 5){
            $type = "gyms";
        }elseif($id == 42 || $id == 45){
            $type = "healthytiffins";
        }elseif($id == 41){
            $type = "personaltrainers";
        }elseif($id == 25){
            $type = "dietitians and nutritionists";
        }elseif($id == 46){
            $type = "sport nutrition supliment stores";
        }else{
            $type = "fitnessstudios";
        }
        

        return $type;
    }
}

if(!function_exists(('sort_weekdays'))){
    function sort_weekdays($arr){
        $arr4 = array();
        // return $arr;
    $arr2=array('monday','tuesday','wednesday','thursday','friday','saturday', 'sunday');
        //A simple loop that traverses all elements of the template...
        foreach($arr2 as $v)
        {
            //If the value in the template exists as a key in the actual array.. (condition)
            // return "sdfs".in_array($v,$arr);
            if(in_array($v,$arr))
            {
                array_push($arr4,$v);
            }
        }

        //prints the new array
        return $arr4;
            }
}

if (!function_exists(('getRegId'))){
    function getRegId($customer_id){

        $response = ["reg_id"=>"","device_type"=>"","flag"=>false];

        $device = Device::where("customer_id",(int)$customer_id)->where('type','!=','web')->orderBy('updated_at','desc')->first();

        if($device){
            $response = ["reg_id"=>$device->reg_id,"device_type"=>$device->type,"flag"=>true];
        }

        return $response;

    }
}

if (!function_exists(('isNotInoperationalDate'))){
    function isNotInoperationalDate($date, $city_id=null, $slot=null, $findercategory_id=null){

        $inoperational_dates = ['2017-12-25'];
        if(in_array($date, $inoperational_dates)){
            return false;
        }

        $inoperational_dates = ['2018-01-01'];

        if($findercategory_id && !in_array($findercategory_id, [5]) && in_array($date, $inoperational_dates)){
            return false;
        }
        
        
        return true;

    }
}

if (!function_exists(('geoLocationFinder'))){

    function geoLocationFinder($request){

        $client = new Client( ['debug' => false, 'base_uri' => Config::get("app.url")."/"] );
        $offset  = $request['offset'];
        $limit   = $request['limit'];
        $radius  = $request['radius'];
        $lat    =  $request['lat'];
        $lon    =  $request['lon'];
        $category = $request['category'];
        $keys = $request['keys'];
        $city = $request['city'];
        $not = isset($request['not']) ? $request['not'] : new \stdClass();
        $region = isset($request['region']) ? $request['region'] : [];

        $payload = [
            "category"=>$category,
            "sort"=>[
              "order"=>"desc",
              "sortfield"=>"popularity"
          ],
          "offset"=>[
              "from"=>$offset,
              "number_of_records"=>$limit
          ],
          "location"=>[
              "geo"=>[
                  "lat"=>$lat,
                  "lon"=>$lon,
                  "radius"=>$radius
              ],
              "regions"=>$region,
              "city"=>$city
          ],
          "keys"=>$keys,
          "not"=>$not
      ];

        $url = Config::get('app.new_search_url')."/search/vendor";

        $finder = [];

        try {

            $response  =   json_decode($client->post($url,['json'=>$payload])->getBody()->getContents(),true);

            if(isset($response['results'])){

                $vendor = $response['results'];

                foreach ($vendor as $key => $value) {

                    $address = false;

                    $finder_data = $value;

                    if(in_array('coverimage',$request['keys'])){
                        $finder_data['coverimage'] = $finder_data['coverimage'];
                    }

                    if(in_array('offerings',$request['keys']) && isset($finder_data['offerings'])){
                        $finder_data['remarks'] = implode(",",$finder_data['offerings']);
                        unset($finder_data['offerings']);
                    }

                    if(in_array('multiaddress',$request['keys'])){

                        $finder_data['address'] = "";

                        if(!empty($finder_data['multiaddress']) && isset( $finder_data['multiaddress'][0])){

                            $multi_address = $finder_data['multiaddress'][0];

                            $finder_data['address'] = $multi_address['line1'].$multi_address['line2'].$multi_address['line3'].$multi_address['landmark'].$multi_address['pincode'];

                            $address = true;                            
                        }
                     
                        unset($finder_data['multiaddress']);
                    }

                    if(in_array('contact',$request['keys'])){

                        if(!$address && !empty($finder_data['contact']) && isset($finder_data['contact']['address']) && $finder_data['contact']['address'] != ""){

                            $finder_data['address'] = $finder_data['contact']['address'];
                        }

                        unset($finder_data['contact']);
                    }

                    if(in_array('name',$request['keys'])){
                        $finder_data['title'] = $finder_data['name'];
                        unset($finder_data['name']);
                    }

                    if(in_array('average_rating',$request['keys'])){
                        $finder_data['average_rating'] = round($finder_data['average_rating'],1);
                    }

                    if(in_array('categorytags',$request['keys'])){
                        $finder_data['subcategories'] = array_map('ucwords',$finder_data['categorytags']);
                    }

                    if(in_array('category',$request['keys'])){
                        $finder_data['category'] = ucwords($finder_data['category']);
                    }

                    $finder[] = $finder_data;
                }
            }

            return $finder;

        }catch (RequestException $e) {

            return $finder;

        }catch (Exception $e) {

            return $finder;
        }

    }
}

if (!function_exists('decodeKioskVendorToken')) {

    function decodeKioskVendorToken(){

        $jwt_token              =   Request::header('Authorization-Vendor');
        $jwt_key                =   Config::get('jwt.kiosk.key');
        $jwt_alg                =   Config::get('jwt.kiosk.alg');
        $decodedToken           =   JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

        Log::info("Vendor Token : ".$jwt_token);

        Log::info("decodeKioskVendorToken : ",json_decode(json_encode($decodedToken),true));

        return $decodedToken;
    }

}

if (!function_exists('generateOtp')) {

    function generateOtp($length = 4){

        $characters = '0123456789';
        $result = '';
        $charactersLength = strlen($characters);

        for ($p = 0; $p < $length; $p++)
        {
            $result .= $characters[rand(0, $charactersLength - 1)];
        }

        return $result;
    }

}

if (!function_exists('addTemp')) {

    function addTemp($data){

        $temp = new Temp($data);
        $temp->otp = generateOtp();
        $temp->attempt = 1;
        $temp->verified = "N";
        $temp->proceed_without_otp = "N";
        $temp->source = "website";

        
        if(isset($data['order_id']) && $data['order_id'] != ""){
            $temp->order_id = (int) $data['order_id'];
        }
        
        if(isset($data['finder_id']) && $data['finder_id'] != ""){
            $temp->finder_id = (int) $data['finder_id'];
        }
        
        if(isset($data['service_id']) && $data['service_id'] != ""){
            $temp->service_id = (int) $data['service_id'];
        }
        
        if(isset($data['ratecard_id']) && $data['ratecard_id'] != ""){
            $temp->ratecard_id = (int) $data['ratecard_id'];
            
            $ratecard = Ratecard::find((int) $data['ratecard_id']);
            
            if($ratecard){
                $temp->finder_id = (int) $ratecard->finder_id;
                $temp->service_id = (int) $ratecard->service_id;
            }
            
        }
        
        if(isset($_GET['device_type']) && $_GET['device_type'] != ""){
            $temp->source = $_GET['device_type'];
        }
        
        if(isset($_GET['app_version']) && $_GET['app_version'] != ""){
            $temp->version = $_GET['app_version'];
        }
        
        if($data['action'] == "vendor_otp"){

            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = $decodeKioskVendorToken->vendor;

            $temp->finder_id = (int)$vendor->_id;

            $temp->source = "kiosk";
        }

        $temp->save();

        return $temp->toArray();

    }
}

if (!function_exists('formFields')) {

    function formFields(){

        $data = [
            [
                'field'=>'age',
                'title'=>'Age',
                'data_type'=>'number',
                'input_type'=>'number',
                'required'=>true
            ],
            [
                'field'=>'blood_group',
                'title'=>'Blood group',
                'data_type'=>'text',
                'input_type'=>'select',
                'options'=>[
                    'A+',
                    'A-',
                    'B+',
                    'B-',
                    'AB+',
                    'AB-',
                    'O+',
                    'O-'
                ],
                'required'=>true
            ],
           /* [
                'field'=>'emergency_contact_number',
                'title'=>'Emergency Contact Number',
                'data_type'=>'text',
                'input_type'=>'text',
                'required'=>true
            ],*/
           /* [
                'field'=>'recommended_to_workout',
                'title'=>'Were you recommended to workout by a doctor',
                'data_type'=>'text',
                'input_type'=>'text',
                'required'=>true
            ],*/
            /*[
                'field'=>'medical_condition',
                'title'=>'Medical Condition',
                'data_type'=>'text',
                'input_type'=>'text',
                'required'=>true
            ],*/
            [
                'field'=>'prescriptive_medication',
                'title'=>'Do you take any prescriptive medication',
                'data_type'=>'text',
                'input_type'=>'select',
                'options'=>[
                    'Yes',
                    'No'
                ],
                'required'=>true
            ],
            [
                'field'=>'smoke',
                'title'=>'Do you smoke',
                'data_type'=>'text',
                'input_type'=>'select',
                'options'=>[
                    'Yes',
                    'No'
                ],
                'required'=>true
            ],
            [
                'field'=>'consume_alcohol',
                'title'=>'Do you consume alcohol',
                'data_type'=>'text',
                'input_type'=>'select',
                'options'=>[
                    'Yes',
                    'No'
                ],
                'required'=>true
            ],          
        ];

        return $data;
    }

}

if (!function_exists('isKioskVendor')) {

    function isKioskVendor($finder_id){

        $isKioskVendor = false;

        $count = KioskUser::where('hidden',false)->where('finder_id',(int) $finder_id)->where('type','kiosk')->count();

        if($count){
            $isKioskVendor = true;
        }

        return $isKioskVendor;
    }
}

if (!function_exists('setDefaultAccount')) {
    
    function setDefaultAccount($data, $customer_id){
        
        Log::info("Inside setDefaultAccount");
        Log::info($data);
        if( ((isset($data['source']) && $data['source'] == 'kiosk') || (isset($data['customer_source']) && $data['customer_source'] == 'kiosk')) && isset($data['customer_phone']) && $data['customer_phone'] != ''){
            
            Log::info("Creating default account");
            Customer::$withoutAppends = true;
            $defaultCustomer = Customer::find(intval($customer_id));
            $defaultCustomer->default_account = true;
            $duplicateCustomers = Customer::where('contact_no','LIKE','%'.substr($data['customer_phone'], -10).'%')->whereNot('_id', $customer_id)->lists('_id');
            if(count($duplicateCustomers) > 0){
                $defaultCustomer->attached_accounts = $duplicateCustomers;
            
            }
            Log::info("====Duplicate Customers=======");
            Log::info($duplicateCustomers);
            $defaultCustomer->update();
            
            // foreach($duplicateCustomers as $customer){
                
            //     $secondary_contact_no = array();
            //     if(isset($customer->secondary_contact_no)){
            //         $secondary_contact_no = $customer->secondary_contact_no;
            //     }
            //     array_push($secondary_contact_no, substr($data['customer_phone'], -10));
            //     $customer->secondary_contact_no = $secondary_contact_no;
            //     $customer->contact_no = '';
            //     $customer->update();
            // }
            
        }
        return;
    }
}

if (!function_exists('setVerifiedContact')) {
    
    function setVerifiedContact($customer_id, $contact_no){
        Log::info("customer_id");
        Log::info("$customer_id");
        Log::info("contact_no");
        Log::info("$contact_no");
        
        $customer = Customer::find(intval($customer_id));
        if(!isset($customer->contact_no) || $customer->contact_no == ''){
            
            $customer->contact_no = trim($contact_no);
            
        }
        if(substr($customer->contact_no, -10) == substr( trim($contact_no), -10)){
            
            $customer->contact_no_verified = true;
        
        }else{
            $secondary_verified_no = isset($customer->secondary_verified_no) ? $customer->secondary_verified_no : array();
            if(in_array(trim($contact_no), $secondary_verified_no)){
                
                array_push($secondary_verified_no, trim($contact_no));
            }
            $customer->secondary_verified_no = $secondary_verified_no;
        }
        $customer->update();
       
    }
}

if (!function_exists('registerMail')) {
    
    function registerMail($customer_id){
        try{
            Log::info("inside register====================");
            $customerData = Customer::find($customer_id);
            
            if(!isset($customerData->welcome_mail_sent) || !$customerData->welcome_mail_sent){
                $customermailer = new CustomerMailer();
                $utilities = new Utilities();
                
                $wallet_balance = $utilities->getWalletBalance($customer_id);
                $customerData->wallet_balance = $wallet_balance;
                if($wallet_balance > 0){
        
                    $customermailer->registerFitcash($customerData->toArray());
        
                }else{
        
                    $customermailer->registerNoFitcash($customerData->toArray());
        
                }
                $customerData->welcome_mail_sent = true;
                $customerData->update();
            }
        
        }catch(Exception $e){
            Log::info($e);
        }
    }
}
       
if (!function_exists('vendorsByBrand')) {
    
    function vendorsByBrand($request){
            
        $client = new Client( ['debug' => false, 'base_uri' => Config::get("app.url")."/"] );
        $offset  = 0;
        $limit   = 50;
        $brand_id = $request['brand_id'];
        $city = $request['city'];
        $keys = [ "id","address","average_rating","business_type","categorytags","commercial_type","contact","coverimage","distance","facilities","geolocation","location","locationtags","multiaddress","name","offer_available","offerings","photos","servicelist","slug","total_rating_count","vendor_type","subcategories","tractionscore","trial_offer","membership_offer" ];
        
        // $brand_id = $request['brand_id'];
        $payload = [ 
            "category"=> "",
            "keys"=> $keys,
            "location"=> [ "city"=> $city ],
            "brand"=> $brand_id,
            "offset"=> [ "from"=> $offset, "number_of_records"=> $limit ],
            "other_filters"=> [] 
        ];

        $url = Config::get('app.new_search_url')."/search/vendor";

        try {
            
            $response  =   json_decode($client->post($url,['json'=>$payload])->getBody()->getContents(),true);

            return $response;            

            // if(isset($response['results'])){

            //     return $finders = $response['results'];

            // }

        }catch (RequestException $e) {
            \Log::info($e);
            return [];

        }catch (Exception $e) {
            \Log::info($e);
            return [];
        }
            
    }
}



?>