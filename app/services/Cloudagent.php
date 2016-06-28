<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Log;

Class Cloudagent {


    protected $base_uri             =   'http://cloudagent.in/';
    protected $debug                =   false;
    protected $api_key              =   'KKd55347ed3b7d96997d959860ce3abb54';
    protected $username             =   'fitternity_solns';
    protected $campaign_name        =   'ClickToCallWebsite';
    protected $client;
    protected $route_type;

    public function __construct() {
        $this->initClient();
    }

    public function initClient($debug = false, $base_uri = false) {

        $debug          =   ($debug) ? $debug : $this->debug;
        $base_uri       =   ($base_uri) ? $base_uri : $this->base_uri;
        $this->client   =   new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }


    public function requestToCallBack($data)
    {

        $this->campaign_name = "ClickToCallWebsite";
        $param = [
            'api_key' => $this->api_key,
            'campaign_name' => $this->campaign_name,
            'PhoneNumber' => $data['phone'],
            'Name' => $data['name'],
            'action' => "START",
            'format' => "json"
        ];
        $url = "cloudAgentRestAPI/index.php/CloudAgent/CloudAgentAPI/addCamapaignData?" . http_build_query($param, "&");


        Log::info('Cloudagent Url : '.$url);

        try {
            $responseData = json_decode($this->client->get($url)->getBody()->getContents(), TRUE);
            $response = array('status'=>200, 'data'=> $responseData);
//            var_dump($xdata); var_dump($url); var_dump($response);  exit;

        }catch (RequestException $e) {

            $error      = $e->getResponse();
            $response   = [  'status'=> $error->getStatusCode(), 'reason'=> $error->getReasonPhrase() ];
            Log::info('Cloudagent Error : '.json_encode($response));

        }catch (Exception $e) {

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status'=>400,'reason'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            Log::info('Cloudagent Error : '.json_encode($response));
        }

        return $response;

    }






}