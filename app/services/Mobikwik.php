<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class Mobikwik {

    protected $base_uri;
    protected $debug = false;
    protected $client;
    protected $mid;
    protected $secret_key;
    protected $si_secret_key;
    protected $merchantname;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false) {

        $debug = ($debug) ? $debug : $this->debug;

        $this->base_uri = 'https://walletapi.mobikwik.com';
        $this->mid = 'MBK9006';
        $this->secret_key = 'ju6tygh7u7tdg554k098ujd5468o';
        $this->si_secret_key = 'lu6tygh7u7tdg554k098ujd5468o';
        $this->merchantname = 'Test Merchant';

        $mobikwik_sandbox = \Config::get('app.mobikwik_sandbox');

        if($mobikwik_sandbox){

            $this->base_uri = 'https://test.mobikwik.com';
            $this->mid = 'MBK9006';
            $this->secret_key = 'ju6tygh7u7tdg554k098ujd5468o';
            $this->si_secret_key = 'lu6tygh7u7tdg554k098ujd5468o';
            $this->merchantname = 'Test Merchant';
        }

        $this->client = new Client( ['debug' => $debug, 'base_uri' => $this->base_uri] );

    }

    public function createChecksum($data){

        $string = "'".implode("''",array_values($data))."'";

        $final_secret_key = $this->secret_key;

        if(!empty($data['token']) && !empty($data['msgcode']) && $data['msgcode'] == 507 ){

            $final_secret_key = $this->si_secret_key;
        }

        return hash_hmac("sha256",$string,$final_secret_key);
    }

    public function verifyChecksum($checksum,$data) {

        $create_checksum = $this->createChecksum($data);

        $flag = false;

        if($checksum == $create_checksum){
            $flag = true;
        }
        
        return $flag;
    }

    public function postForm($data,$url){

        \Log::info('postForm',$data);

        try {

            $url = $url."?".http_build_query($data, "&");

            \Log::info('postFormUrl '.$url);

            // $response = $this->client->post($url,['form_params'=>$data])->getBody()->getContents();

            $response = $this->client->get($url)->getBody()->getContents();

            $xml = simplexml_load_string($response);

            $json = json_encode($xml);
            $response = json_decode($json,TRUE);

            $return  = [
                'status'=>200,
                'response'=>$response
            ];

            \Log::info('postFormResponse',$response);

            return $return;

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

        $url = 'querywallet';

        return $this->postForm($data,$url);

    }

    public function generateOtp($data){

        $data = [
            'amount'=>50000,//(float)$data['amount'],
            'cell'=>substr($data['cell'],-10),
            'merchantname'=>$this->merchantname,
            'mid'=>$this->mid,
            'msgcode'=>504,
            'tokentype'=>1
        ];

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        $url = 'otpgenerate';

        return $this->postForm($data,$url);

    }

    public function generateToken($data){

        $data = [
            'amount'=>50000,//(float)$data['amount'],
            'cell'=>substr($data['cell'],-10),
            'merchantname'=>$this->merchantname,
            'mid'=>$this->mid,
            'msgcode'=>507,
            'otp'=>$data['otp'],
            'tokentype'=>1
        ];

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        $url = 'tokengenerate';

        return $this->postForm($data,$url);

    }

    public function regenerateToken($data){

        $data = [
            'cell'=>substr($data['cell'],-10),
            'merchantname'=>$this->merchantname,
            'mid'=>$this->mid,
            'msgcode'=>507,
            'token'=>$data['token'],
            'tokentype'=>1
        ];

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        $url = 'tokenregenerate';

        return $this->postForm($data,$url);

    }

    public function createUser($data){

        $data = [
            'cell'=>substr($data['cell'],-10),
            // 'email'=>$data['email'],
            'merchantname'=>$this->merchantname,
            'mid'=>$this->mid,
            'msgcode'=>502,
            'otp'=>$data['otp']
        ];

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        $url = 'createwalletuser';

        return $this->postForm($data,$url);

    }

    public function checkBalance($data){

        $data = [
            'cell'=>substr($data['cell'],-10),
            'merchantname'=>$this->merchantname,
            'mid'=>$this->mid,
            'msgcode'=>501,
            'token'=>$data['token']
        ];

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        $url = 'userbalance';

        return $this->postForm($data,$url);

    }

    public function addMoney($data){

        $data = [
            'amount'=>(float)$data['amount'],
            'cell'=>substr($data['cell'],-10),
            'merchantname'=>$this->merchantname,
            'mid'=>$this->mid,
            'orderid'=>$data['txnid'],
            'redirecturl'=>\Config::get('app.url').'/verifyaddmoney/mobikwik',
            'token'=>$data['token']
        ];

        if(stripos($data['orderid'],'fit') == 0){

            $data['redirecturl'] = "http://localhost:3000/verifymobikwik";
        }

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        $url = $this->base_uri.'/addmoneytowallet?'.http_build_query($data, "&");

        $response = [
            'url'=>$url,
            'status'=>200
        ];

        return $response;

    }

    public function debitMoney($data){

        $data = [
            'amount'=>(float)$data['amount'],
            'cell'=>substr($data['cell'],-10),
            'comment'=>'Debit',
            'merchantname'=>$this->merchantname,
            'mid'=>$this->mid,
            'msgcode'=>503,
            'orderid'=>$data['txnid'],
            'token'=>$data['token'],
            'txntype'=>'debit'
        ];

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        $url = 'debitwallet';

        return $this->postForm($data,$url);

    }

    public function checkStatus($data){

        $data = [
            'mid'=>$this->mid,
            'orderid'=>$data['txnid']
        ];

        $checksum = $this->createChecksum($data);

        $data['checksum'] = $checksum;

        $url = 'checkstatus';

        return $this->postForm($data,$url);
    }

    

}                                       