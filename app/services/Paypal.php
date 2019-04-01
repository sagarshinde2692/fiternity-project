<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class Paypal {

    protected $base_uri;
    protected $debug = false;
    protected $client;
    protected $client_id;
    protected $secret_key;
    protected $access_token;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false) {

        $debug = ($debug) ? $debug : $this->debug;

        // $this->base_uri = 'https://secure.paytm.in/oltp/HANDLER_INTERNAL/';
        // $this->secret_key = 'j&0CCJb%B26dMs79';
        // $this->transaction_api = "https://secure.paytm.in/oltp-web/processTransaction?orderid=";

        $paypal_sandbox = \Config::get('app.paypal_sandbox');

        if($paypal_sandbox){

            $this->base_uri = 'https://api.sandbox.paypal.com/';
            $this->client_id = 'AfDQs-Y-KAWp7J0ynzgYum_Yetyq67nnhqesWcszrIgoDicfMRAJl2BQi_a6_ud3Pq05MhijsgZnLS49';
            $this->secret_key = 'EBg_om1ii2YNsCXzQQ3ELuUtMT1mt8nNQL-5vmGMr2Kg32Z1uGBTutrE_jbAX-IB7WpDrdKMuv0e_lWm';
            $this->auth_uri = "https://api.sandbox.paypal.com/v1/oauth2/token";
        }

        // $this->client = new Client( ['debug' => $debug, 'base_uri' => $this->base_uri] );
        $this->client = new Client();
       
        //$client = new \GuzzleHttp\Client();
        $response = $this->client->request('POST', $this->auth_uri, [
                'headers' =>
                    [
                        'Accept' => 'application/json',
                        'Accept-Language' => 'en_US',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                'body' => 'grant_type=client_credentials',

                'auth' => [$this->client_id, $this->secret_key, 'basic']
            ]
        );

        $data = json_decode($response->getBody(), true);

        $this->access_token = $data['access_token'];

    }

    public function getAccessToken(){
        return $this->access_token;
    }

    public function listWebProfile(){
        try {
            $response = $this->client->request('GET', $this->base_uri.'v1/payment-experience/web-profiles', [
                'headers' =>
                    [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$this->access_token.'',
                    ]
                ]);

            $res_data = json_decode($response->getBody(), true);
            
            $res_da = [  
                'status'=>'200',
                'message'=>$res_data
            ];
            return $res_da;
        }catch (RequestException $e) {

            $response = $e->getResponse();

            $error = [  
                'status'=>$response->getStatusCode(),
                'message'=>$response->getReasonPhrase()
            ];

            return $error;

        }catch (Exception $e) {

            $error = [  
                'status'=>400,
                'message'=>'Error'
            ];

            return $error;
        }
    }

    public function createWebProfile(){
        try {
            $response = $this->client->request('POST', $this->base_uri.'v1/payment-experience/web-profiles', [
                'headers' =>
                    [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$this->access_token.'',
                    ],
                'body' => '{
                    "name": "Fitternity Profile",
                    "input_fields": {
                      "no_shipping": 1
                    },
                    "flow_config": {
                      "landing_page_type": "billing"
                    }
                  }'
                ]);

            $res_data = json_decode($response->getBody(), true);
            
            $res_da = [  
                'status'=>'200',
                'message'=>$res_data
            ];
            return $res_da;
        }catch (RequestException $e) {

            $response = $e->getResponse();

            $error = [  
                'status'=>$response->getStatusCode(),
                'message'=>$response->getReasonPhrase()
            ];

            return $error;

        }catch (Exception $e) {

            $error = [  
                'status'=>400,
                'message'=>'Error'
            ];

            return $error;
        }
    }

    public function createPayment($data = null){
        try {
            $response = $this->client->request('POST', $this->base_uri.'v1/payments/payment', [
                'headers' =>
                    [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$this->access_token.'',
                    ],
                'body' => $data
                ]);

            $res_data = json_decode($response->getBody(), true);
            
            $res_da = [  
                'status'=>'200',
                'message'=>$res_data
            ];
            return $res_da;
        }catch (RequestException $e) {

            $response = $e->getResponse();

            $error = [  
                'status'=>$response->getStatusCode(),
                'message'=>$response->getReasonPhrase()
            ];

            return $error;

        }catch (Exception $e) {

            $error = [  
                'status'=>400,
                'message'=>'Error'
            ];

            return $error;
        }
    }

    public function executePayment($paymentId, $payer_id){
        try {
            $response = $this->client->request('POST', $this->base_uri.'v1/payments/payment/'.$paymentId.'/execute', [
                'headers' =>
                    [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$this->access_token.'',
                    ],
                'body' => $payer_id
                ]);

            $res_data = json_decode($response->getBody(), true);
            
            $res_da = [  
                'status'=>'200',
                'message'=>$res_data
            ];
            return $res_da;
        }catch (RequestException $e) {

            $response = $e->getResponse();

            $error = [  
                'status'=>$response->getStatusCode(),
                'message'=>$response->getReasonPhrase()
            ];

            return $error;

        }catch (Exception $e) {

            $error = [  
                'status'=>400,
                'message'=>'Error'
            ];

            return $error;
        }
    }

}                                       