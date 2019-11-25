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

    public function cancelThirdPartySession($thirdPartyAcronym, $tokenId, $booktrialId, $msg) {
        try {
            $response = null;
            if(!empty($thirdPartyAcronym) && $thirdPartyAcronym=='abg') {
                $response = json_decode(
                    $this->client->post("/thirdp/cancelBookingFromBackend", [
                        'json' => [
                            'token_id' => $tokenId,
                            'booktrial_id' => $booktrialId,
                            'msg' => $msg,
                        ],
                        'headers' => [
                            'sector' => 'multiply'
                        ]
                    ])->getBody()->getContents()
                );
            }
            else if (!empty($thirdPartyAcronym) && $thirdPartyAcronym=='ekn') {
                $apikey = array_values(array_filter(\Config::get('app.corporate_mapping'), function($corpMap) {
                    return ($corpMap['acronym']=='ekn');
                }))[0]['key'];
                $response = json_decode(
                    $this->client->post("/api/cancelsessionwebhookcall", [
                        'json' => [
                            'token_id' => (!empty($tokenId))?$tokenId:null,
                            'booktrial_id' => $booktrialId,
                            'msg' => $msg,
                        ],
                        'headers' => [
                            'key' => $apikey
                        ]
                    ])->getBody()->getContents()
                );
            }

            $return  = [
                'status' => 200,
                'data' => $response
            ];
            return $return;
        }catch (RequestException $e) {
            \Log::info($e);
            $response = $e->getResponse();
            $error = [
                'status'=>$response->getStatusCode(),
                'reason'=>$response->getReasonPhrase()
            ];
            return $error;
        }catch (Exception $e) {
            $error = [
                'status'=>400,
                'reason'=>'Error'
            ];
            return $error;
        }
    }
  

}                                       