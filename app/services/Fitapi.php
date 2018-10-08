<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;
use Log;

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

    public function createToken($data){
		$jwt_claim = array(
			"iat" => \Config::get('jwt.thirdp.iat'),
			"nbf" => \Config::get('jwt.thirdp.nbf'),
			"exp" => \Config::get('jwt.thirdp.exp'),
			"data" => $data
			);
		
		$jwt_key = \Config::get('jwt.thirdp.key');
		$jwt_alg = \Config::get('jwt.thirdp.alg');

		$token = \JWT::encode($jwt_claim,$jwt_key,$jwt_alg);

		return $token;
	}

    public function storeBooktrial ($data, $isThirdP=false){

        $json = $data;

        try {
            Log::info('inside data: ', $data);
            $payload = [
                'json'=>$json
            ];
            if($isThirdP){
                $token = $this->createToken($data);
                Log::info('inside storeBookTrial: ', [$token]);
                $payload = [
                    'headers'=>[
                        'Authorization' => $token,
                        'Client' => 'thirdp'
                    ],
                    'json'=>$json
                ];
            }
            $response = json_decode($this->client->post('sstorebooktrial',$payload)->getBody()->getContents());
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
            \Log::info($return);
            return $return;
        }catch (RequestException $e) {

            $response = $e->getResponse();

            $error = [  'status'=>$response->getStatusCode(),
                        'reason'=>$response->getReasonPhrase()
            ];
            \Log::info($e);
            return $error;
        }catch (Exception $e) {
            \Log::info($e);
            
            $error = [  'status'=>400,
                        'reason'=>'Error'
            ];

            return $error;
        }

    }
        
}                                       