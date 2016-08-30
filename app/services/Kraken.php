<?php 
namespace App\Services;
use Config, File, Log;

class Kraken
{

    protected $auth = array();

    public function __construct($key = '', $secret = ''){
        $this->auth = array(
            "auth" => array(
                "api_key" => Config::get('app.kraken_key', '73dbf866dbe673867134dc90204ddf96'),
                "api_secret" => Config::get('app.kraken_secret', 'd206555b6c07d8e3eba3807402a183578471251e')
            )
        );
    }

    public function url($opts = array()){
        // return "calling from kraken";
        $data = json_encode(array_merge($this->auth, $opts));
        $response = self::request($data, "https://api.kraken.io/v1/url");

        return $response;
    }

    public function upload($opts = array()){
        if (!isset($opts['file'])){
            return array(
                "success" => false,
                "error" => "File parameter was not provided"
            );
        }

        if (preg_match("/\/\//i", $opts['file'])){
            $opts['url'] = $opts['file'];
            unset($opts['file']);
            return $this->url($opts);
        }

        if (!file_exists($opts['file'])){
            return array(
                "success" => false,
                "error" => "File `" . $opts['file'] . "` does not exist"
            );
        }

        if( class_exists( 'CURLFile') ){
			$file = new \CURLFile( $opts['file'] );
		}else{
			$file = '@' . $opts['file'];
		}

        unset($opts['file']);

        $data = array_merge(array(
            "file" => $file,
            "data" => json_encode(array_merge(
                            $this->auth, $opts
            ))
        ));

        $response = self::request($data, "https://api.kraken.io/v1/upload");

        return $response;
    }

    public function status(){
        $data = array('auth' => array(
            'api_key' => $this->auth['auth']['api_key'],
            'api_secret' => $this->auth['auth']['api_secret']
        ));
        $response = self::request(json_encode($data), "https://api.kraken.io/user_status");
        return $response;
    }

    private function request($data, $url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_FAILONERROR, 0);
        $response = json_decode(curl_exec($curl), true);
        $error = curl_errno($curl);
        curl_close($curl);
        if ($error > 0) {
            throw new \RuntimeException(sprintf('cURL returned with the following error code: "%s"', $error));
        }
        return $response;
    }

}

