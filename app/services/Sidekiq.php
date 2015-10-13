<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;

Class Sidekiq {

    protected $base_uri = 'http://192.168.1.8:3000/';
    protected $debug = false;
    protected $client;
    protected $route_type;

    public function __construct() {

        $this->$route_type = array('email'=>'sendgenericemail','sms'=>'sendgenericesms');
        $this->initClient();
    }

    public function initClient($debug = false,$base_uri = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = ($base_uri) ? $base_uri : $this->base_uri;
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

    public function sendToQueue($payload,$type){

        $route = $this->$route_type[$type];

        try {
            $response = json_decode($this->client->post($route,['json'=>$payload])->getBody()->getContents());
            $return  = ['status'=>200,
                        'task_id'=>(array) $response->job_id
            ];
            return $return;
        }catch (RequestException $e) {
            $responce = $e->getResponse();
            $error = [  'status'=>$responce->getStatusCode(),
                        'reason'=>$responce->getReasonPhrase()
            ];

            Log::info('Sidekiq Email Error : '.json_encode($error));

            return $error;
        }catch (Exception $e) {
            $error = [  'status'=>400,
                        'reason'=>'Error'
            ];

            Log::info('Sidekiq Email Error : '.json_encode($error));

            return $error;
        }

    }

}