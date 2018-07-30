<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;
use \Response;

Class Paytm {

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

        $this->base_uri = 'https://pguat.paytm.com/oltp/HANDLER_INTERNAL/';
        $this->mid = 'Fitern22272466067721';
        $this->secret_key = 'j&0CCJb%B26dMs79';
        $this->merchantname = 'Test Merchant';

        $paytm_sandbox = \Config::get('app.paytm_sandbox');

        if($paytm_sandbox){

            $this->base_uri = 'https://pguat.paytm.com/oltp/HANDLER_INTERNAL/';
            $this->mid = 'Fitern22272466067721';
            $this->secret_key = 'j&0CCJb%B26dMs79';
            $this->merchantname = 'Test Merchant';
        }

        $this->client = new Client( ['debug' => $debug, 'base_uri' => $this->base_uri] );

    }

    public function createChecksum($data){

        return $this->getChecksumFromArray($data,$this->secret_key);
    }

    public function verifyChecksum($checksum,$data) {

        return $this->verifychecksum_e($data,$this->secret_key,$checksum);
    }

    public function postForm($data,$url){

        \Log::info('postForm',$data);

        try {

            $JsonData =json_encode($data);

            $body = 'JsonData='.urlencode($JsonData);

            $response = json_decode($this->client->post($url,['body'=>$body])->getBody()->getContents(),true);

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
            'PHONE'=>substr($data['cell'],-10),
            'USER_TYPE'=>'01',
            'RESPONSE_TYPE'=>'token',
            'SCOPE'=>'paytm,txn',
            'MID'=>$this->mid,
            'OTP_DELIVERY_METHOD'=>'SMS'
        ];

        $checksum = $this->createChecksum($data);

        $data['CHECKSUM'] = $checksum;

        $url = 'GENERATE_OTP';

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

    public function encrypt_e($input, $ky) {
        $key = $ky;
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
        $input = $this->pkcs5_pad_e($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $iv = "@@@@&&&&####$$$$";
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    public function decrypt_e($crypt, $ky) {

        $crypt = base64_decode($crypt);
        $key = $ky;
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $iv = "@@@@&&&&####$$$$";
        mcrypt_generic_init($td, $key, $iv);
        $decrypted_data = mdecrypt_generic($td, $crypt);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $decrypted_data = $this->pkcs5_unpad_e($decrypted_data);
        $decrypted_data = rtrim($decrypted_data);
        return $decrypted_data;
    }

    public function pkcs5_pad_e($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public function pkcs5_unpad_e($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        return substr($text, 0, -1 * $pad);
    }

    public function generateSalt_e($length) {
        $random = "";
        srand((double) microtime() * 1000000);

        $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
        $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
        $data .= "0FGH45OP89";

        for ($i = 0; $i < $length; $i++) {
            $random .= substr($data, (rand() % (strlen($data))), 1);
        }

        return $random;
    }

    public function checkString_e($value) {
        $myvalue = ltrim($value);
        $myvalue = rtrim($myvalue);
        if ($myvalue == 'null')
            $myvalue = '';
        return $myvalue;
    }

    public function getChecksumFromArray($arrayList, $key, $sort=1) {

        if ($sort != 0) {
            ksort($arrayList);
        }
        $str = $this->getArray2Str($arrayList);
        $salt = $this->generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = $this->encrypt_e($hashString, $key);
        return $checksum;
    }

    public function verifychecksum_e($arrayList, $key, $checksumvalue) {
        $arrayList = $this->removeCheckSumParam($arrayList);
        ksort($arrayList);
        $str = $this->getArray2Str($arrayList);
        $paytm_hash = $this->decrypt_e($checksumvalue, $key);
        $salt = substr($paytm_hash, -4);

        $finalString = $str . "|" . $salt;

        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;

        $validFlag = FALSE;
        if ($website_hash == $paytm_hash) {
            $validFlag = TRUE;
        } else {
            $validFlag = FALSE;
        }
        return $validFlag;
    }

    public function getArray2Str($arrayList) {
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
        }
        return $paramStr;
    }

    public function redirect2PG($paramList, $key) {
        $hashString = $this->getchecksumFromArray($paramList);
        $checksum = $this->encrypt_e($hashString, $key);
    }

    public function removeCheckSumParam($arrayList) {
        if (isset($arrayList["CHECKSUMHASH"])) {
            unset($arrayList["CHECKSUMHASH"]);
        }
        return $arrayList;
    }

    public function getTxnStatus($requestParamList) {
        return callAPI($this->PAYTM_STATUS_QUERY_URL, $requestParamList);
    }

    public function initiateTxnRefund($requestParamList) {
        $CHECKSUM = $this->getChecksumFromArray($requestParamList,$this->PAYTM_MERCHANT_KEY,0);
        $requestParamList["CHECKSUM"] = $CHECKSUM;
        return callAPI($this->PAYTM_REFUND_URL, $requestParamList);
    }

    

}                                       