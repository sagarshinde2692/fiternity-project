<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class Fitapi {

    protected $base_uri;
    protected $debug = false;
    protected $client;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = \Config::get('app.url');
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

    public function storeBooktrial ($data){

        $json = $data;

        try {
            $response = json_decode($this->client->post('storebooktrial',['json'=>$json])->getBody()->getContents());
            $return  = ['status'=>200,
                        'data'=>$response
            ];
            return $return;
        }catch (RequestException $e) {

            $response = $e->getResponse();

            $error = [  'status'=>$response->getStatusCode(),
                        'reason'=>$response->getReasonPhrase()
            ];

            return $error;
        }catch (Exception $e) {
            $error = [  'status'=>400,
                        'reason'=>'Error'
            ];

            return $error;
        }

    }

    public function getServiceData ($service_id){

        $url = 'getmembershipratecardbyserviceid/'.$service_id;

        try {
            $response = $this->client->get($url)->getBody()->getContents();
            $return  = ['status'=>200,
                        'data'=>$response
            ];
            return $return;
        }catch (RequestException $e) {
            $response = $e->getResponse();
            $error = [  'status'=>$response->getStatusCode(),
                        'message'=>$response->getReasonPhrase()
            ];

            return $error;
        }catch (Exception $e) {
            $error = [  'status'=>400,
                        'message'=>'Error'
            ];

            return $error;
        }

    }
  
    public function getCaptureData ($json, $headers=[], $query_params=[]){

        try {
            $response = json_decode($this->client->post('transaction/capture?'.http_build_query($query_params),['json'=>$json, 'headers'=>$headers])->getBody()->getContents());
            $return  = ['status'=>200,
                        'data'=>$response
            ];
            return $response;
        }catch (RequestException $e) {

            $response = $e->getResponse();

            $error = [  'status'=>$response->getStatusCode(),
                        'reason'=>$response->getReasonPhrase()
            ];

            return $error;
        }catch (Exception $e) {
            $error = [  'status'=>400,
                        'reason'=>'Error'
            ];

            return $error;
        }

    }
        
}                                       