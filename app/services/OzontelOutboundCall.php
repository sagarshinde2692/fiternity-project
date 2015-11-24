<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class OzontelOutboundCall {

    protected $base_uri = 'http://www.kookoo.in/outbound/outbound.php?';
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

    public function call(){

        $api_key = 'KK6cb3903e3d2c428bb60c0cfaa212009e';
        $phone_no = '9920864894';
        $outbound_version = '2';
        $extra_data = '<response><playtext>Welcome To Fitternity</playtext></response>';
        $url = 'http://apistg.fitn.in/ozonetel/outboundcallrecive';

        $url_pass = 'api_key='.$api_key.'&phone_no='.$phone_no.'&outbound_version='.$outbound_version.'&extra_data='.$extra_data.'&url='.$url;

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

}                                       