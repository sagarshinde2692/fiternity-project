<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class Metropolis {

    protected $base_uri;
    protected $debug = false;
    protected $client;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = \Config::get('app.metropolis');
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

    public function vendorserviceDetail ($vendor_id, $service_slug){
        \Log::info($vendor_id);
        \Log::info($service_slug);
        


        try {
            $response = json_decode($this->client->get("/vendorservicedetail/$vendor_id/$service_slug")->getBody()->getContents());
            // \Log::info($response);
            $return  = ['status'=>200,
                        'data'=>$response
            ];
            return $return;
        }catch (RequestException $e) {

            \Log::info($e);
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