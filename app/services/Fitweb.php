<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class Fitweb {

    protected $base_uri;
    protected $debug = false;
    protected $client;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = \Config::get('app.website');
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

    public function paymentSuccess ($data){

        $json = $data;

        try {

            $response = json_decode($this->client->post('paymentsuccessandroid',['json'=>$json])->getBody()->getContents());

            $return  = [
                'status'=>200,
                'message'=>'Success'
            ];

            return $return;

        }catch (Exception $e) {

            $error = [ 
                'status'=>400,
                'message'=>'Error'
            ];
            
            return $error;
        }

    }
  

}                                       