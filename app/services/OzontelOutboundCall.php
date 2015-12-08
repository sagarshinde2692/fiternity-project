<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;
use App\Services\Sidekiq as Sidekiq;

Class OzontelOutboundCall {

    protected $base_uri = 'http://www.kookoo.in/outbound/outbound.php';
    protected $debug = false;
    protected $client;
    protected $sidekiq;

    public function __construct(Sidekiq $sidekiq) {

        $this->sidekiq = $sidekiq;
        $this->initClient();
    }

    public function initClient($debug = false,$base_uri = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = ($base_uri) ? $base_uri : $this->base_uri;
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

    public function call($phone_no,$trial_id){

        $api_key = 'KK6cb3903e3d2c428bb60c0cfaa212009e';
        $outbound_version = '2';
        $url = 'http://apistg.fitn.in/ozonetel/outboundcallrecive/'.$trial_id;

        $url_pass = '?api_key='.$api_key.'&phone_no='.$phone_no.'&outbound_version='.$outbound_version.'&url='.$url;

        try {
            $response = $this->client->get($url_pass)->getBody()->getContents();
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

    public function sidekiq($trial_id,$label = 'label', $priority = 0, $delay = 0){

        $url = 'http://apistg.fitn.in/ozonetel/outbound/'.$trial_id;

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }
    
        $payload = array('url'=>$url,'delay'=>$delay,'priority'=>$priority,'label' => $label);
        
        $route  = 'outbound';
        $result  = $this->sidekiq->sendToQueue($payload,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
        }

    }

    protected function getSeconds($delay){

        if ($delay instanceof DateTime){
            return max(0, $delay->getTimestamp() - $this->getTime());
        }

        if ($delay instanceof \Carbon\Carbon){
            return max(0, $delay->timestamp - $this->getTime());
        }
        // echo (int) $delay; exit;
        return (int) $delay;
    }



}                                       