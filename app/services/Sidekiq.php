<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Log;

Class Sidekiq {
    
    //protected $base_uri = 'http://localhost:3000/';
    //protected $base_uri = 'http://kick.fitn.in/';
    protected $base_uri = 'http://nw.fitn.in/';
    protected $debug = false;
    protected $client;
    protected $route_type;

    public function __construct() {

        $this->route_type = array('email'=>'trig/email','sms'=>'trig/sms','delete'=>'trig/delmsg','outbound'=>'trig/outbound','fitmaniabuyable'=>'trig/fitmaniabuyable','android'=>'trig/android','ios'=>'trig/ios');
        $this->initClient();
    }

    public function initClient($debug = false, $base_uri = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = ($base_uri) ? $base_uri : $this->base_uri;
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

    public function sendToQueue($payload,$type){

        $route = $this->route_type[$type];

        try {
            $response = json_decode($this->client->post($route,['json'=>$payload])->getBody()->getContents());
            $return  = ['status'=>200,
                        'task_id'=>$response->jid
            ];
            return $return;
        }catch (RequestException $e) {
            
            $response = $e->getResponse();
            $error = [  'status'=>$response->getStatusCode(),
                        'reason'=>$response->getReasonPhrase()
            ];

            Log::info('Sidekiq Email Error : '.json_encode($error));

            return $error;

        }catch (Exception $e) {
            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $error = array('status'=>400,'reason'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            Log::info('Sidekiq Email Error : '.json_encode($message));

            return $error;
        }

    }

    public function delete($id){

        if($id){

            $id = array($id);
            $type = 'delete';
            $route = $this->route_type[$type];
            $payload = array('jid'=>$id);

            try {
                    $response = json_decode($this->client->post($route,['json'=>$payload])->getBody()->getContents());
                    $return  = ['status'=>200,
                                'message'=>$response->message
                    ];
                    return $return;
            }catch (RequestException $e) {
                
                $response = $e->getResponse();
                $error = [  'status'=>$response->getStatusCode(),
                            'reason'=>$response->getReasonPhrase()
                ];

                Log::info('Sidekiq Email Error : '.json_encode($error));

                return $error;

            }catch (Exception $e) {
                $message = array(
                        'type'    => get_class($e),
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    );

                $error = array('status'=>400,'reason'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
                Log::info('Sidekiq Email Error : '.json_encode($message));

                return $error;
            }

        }else{

            $response = array('status'=>400,'reason'=>'atach id');
            Log::info('Sidekiq Email Error : attach id');
        }

        return Response::json($response,$response['status']); 

    }

}