<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class Mobikwik {

    protected $base_uri;
    protected $debug = false;
    protected $client;
    protected $mid;
    protected $key;
    protected $merchantname;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false) {

        $debug = ($debug) ? $debug : $this->debug;

        $base_uri = 'https://walletapi.mobikwik.com';
        $this->mid = 'MBK9005';
        $this->key = 'ju6tygh7u7tdg554k098ujd5468o';
        $this->merchantname = 'TestMerchant';

        $mobikwik_sandbox = \Config::get('app.mobikwik_sandbox');

        if($mobikwik_sandbox){
            $base_uri = 'https://test.mobikwik.com';
            $this->mid = 'MBK9005';
            $this->key = 'ju6tygh7u7tdg554k098ujd5468o';
            $this->merchantname = 'TestMerchant';
        }

        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

    public function createChecksum($data){

        $string = "'".implode("''",array_values($data))."'";

        return hash_hmac("sha256",$string,$this->key);
    }

    function verifyChecksum($checksum,$data) {

        $create_checksum = $this->createChecksum($data);

        $flag = false;

        if($checksum == $create_checksum){
            $flag = true;
        }
        
        return $flag;
    }

    public function checkExistingUser($cell){

        $cell = substr($cell,-10);

        $data = [
            'action'=>'existingusercheck',
            'cell'=>$cell,
            'merchantname'=>$this->merchantname,
            'mid'=>$this->mid,
            'msgcode'=>500
        ];

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        try {

            $response = $this->client->post($url,['body'=>$data])->getBody()->getContents();

            $return  = [
                'status'=>200,
                'data'=>$response
            ];

            return $return;

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