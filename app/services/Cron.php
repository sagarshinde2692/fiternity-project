<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class Cron {

    protected $base_uri = 'https://a1.fitternity.com/cron/';
    protected $debug = false;
    protected $client;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false,$base_uri = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = ($base_uri) ? $base_uri : $this->base_uri;
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

   public function cronLog ($data = false){

        if(!$data){
            $error = [  'status'=>400,
                    'reason'=>'data not found'
            ];
            return $error;
        }
        
        $json = $data;

        try {
            $response = json_decode($this->client->post('cronlog',['json'=>$json])->getBody()->getContents());
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