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
    protected $merchant_id;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false) {

        $debug = ($debug) ? $debug : $this->debug;

        $this->base_uri = 'https://api.paypal.com/';
        $this->client_id = 'AZi5DfXM1DJhUmLWSFEH0Lx0bRQv2mwvmJ8I1lupg5epT6irnBkqv-iuRgdKN5o6l4JqzifZ52Lte5zg';
        $this->secret_key = 'EO1RH_hGADEAl0yXToQmU9FNhpnSsUBUC9sgmm8_aKUR3t-atUrVrGihJlHXcgocPqtigDZVEpGjcRGY';
        $this->merchant_id = "3527Q7ANXWFEW";

        $paypal_sandbox = \Config::get('app.paypal_sandbox');

        if($paypal_sandbox){

            $this->base_uri = 'https://api.sandbox.paypal.com/';
            $this->client_id = 'AfDQs-Y-KAWp7J0ynzgYum_Yetyq67nnhqesWcszrIgoDicfMRAJl2BQi_a6_ud3Pq05MhijsgZnLS49';
            $this->secret_key = 'EBg_om1ii2YNsCXzQQ3ELuUtMT1mt8nNQL-5vmGMr2Kg32Z1uGBTutrE_jbAX-IB7WpDrdKMuv0e_lWm';
            $this->merchant_id = "TEJG56ER4YM26";
        }

        // $this->client = new Client( ['debug' => $debug, 'base_uri' => $this->base_uri] );
        $this->client = new Client();
       
        //$client = new \GuzzleHttp\Client();
        $response = $this->client->request('POST', $this->base_uri.'v1/oauth2/token', [
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
        \Log::info("access token  :: ",[$this->access_token]);
    }

    public function getAccessToken(){
        return $this->access_token;
    }

    public function setTransactionContext($data = null){
        try {
            $dataArr = json_decode($data,true);
            \Log::info("access token  :: ",[$this->access_token]);
            \Log::info("marchant _id  :: ",[$this->merchant_id]);
            \Log::info("tracking _id  :: ",[$dataArr['tracking_id']]);
            \Log::info('https://api.paypal.com/v1/risk/transaction-contexts/'.$this->merchant_id.'/'.$dataArr['tracking_id']);

            $response = $this->client->request('PUT', 'https://api.paypal.com/v1/risk/transaction-contexts/'.$this->merchant_id.'/'.$dataArr['tracking_id'], [
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

    public function createPayment($data = null, $uniqueId = ""){
        try {
            $response = $this->client->request('POST', $this->base_uri.'v1/payments/payment', [
                'headers' =>
                    [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$this->access_token.'',
                        'PayPal-Client-Metadata-Id' => $uniqueId
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

    public function executePayment($paymentId, $payer_id, $uniqueId){
        try {
            \Log::info('executePayment: ');
            \Log::info('URI: ', [$this->base_uri.'v1/payments/payment/'.$paymentId.'/execute']);
            \Log::info('payload: ', [[
                'headers' =>
                    [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$this->access_token.'',
                        'PayPal-Client-Metadata-Id' => $uniqueId,
                        'PayPal-Request-Id' => $uniqueId
                    ],
                'body' => $payer_id
            ]]);
            $response = $this->client->request('POST', $this->base_uri.'v1/payments/payment/'.$paymentId.'/execute', [
                'headers' =>
                    [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$this->access_token.'',
                        'PayPal-Client-Metadata-Id' => $uniqueId,
                        'PayPal-Request-Id' => $uniqueId
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