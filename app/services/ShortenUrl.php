<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class ShortenUrl {

    protected $base_uri = 'https://www.googleapis.com/';
    protected $debug = false;
    protected $client;
    protected $key;


    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false,$base_uri = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = ($base_uri) ? $base_uri : $this->base_uri;
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );
        $this->key = 'AIzaSyA5kQPOF6wZ42GKbGMqENmuHzm0lJMXTss';

    }

    public function getShortenUrl ($longUrl = false){

        if(!$longUrl){
            $error = array('status'=>400,'reason'=>'url is empty');
            return $error;
        }

        $longUrl = rtrim($longUrl,'/').'/';
        
        $json = array('longUrl'=>$longUrl);

        $url = 'urlshortener/v1/url?key='.$this->key;

        try {

            $response = json_decode($this->client->post($url,['json'=>$json])->getBody()->getContents());
            $return = array('url'=>$response->id,'status'=>200);

            return $return;

        }catch (RequestException $e) {

            $response = $e->getResponse();
            $error = array('status'=>$response->getStatusCode(),'reason'=>$response->getReasonPhrase());

            return $error;

        }catch (Exception $e) {

            $error = array('status'=>400,'reason'=>'Error');

            return $error;
        }

    }
    
    public function getHistory(){

        $url = 'urlshortener/v1/url/history?key='.$this->key;

        try {

            $response = json_decode($this->client->get($url)->getBody()->getContents());
            $return = array('url'=>$response->id,'status'=>200);

            return $return;

        }catch (RequestException $e) {

            $response = $e->getResponse();
            $error = array('status'=>$response->getStatusCode(),'reason'=>$response->getReasonPhrase());

            return $error;

        }catch (Exception $e) {

            $error = array('status'=>400,'reason'=>'Error');

            return $error;
        }

    }

}                                       