<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Client;

Class SmsVersionNext {

    protected $base_uri = 'http://103.16.101.52:8080/';
    protected $debug = false;
    protected $client;
    protected $username;
    protected $password;

    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false,$base_uri = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = ($base_uri) ? $base_uri : $this->base_uri;
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );
        $this->username_fitternity = 'vnt-fitternity';
        $this->password_fitternity = 'vishwas1';
        $this->username_fitpromo = 'vnt-fitpromo';
        $this->password_fitpromo = 'india123';
    }

    public function transactionBalance(){

        $username = $this->username_fitternity;
        $password = $this->password_fitternity;

        $url = 'CreditCheck/checkcredits?username='.$username.'&password='.$password;

        try {
            $response = $this->client->get($url)->getBody()->getContents();
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

    public function promotionBalance(){

        $username = $this->username_fitpromo;
        $password = $this->password_fitpromo;

        $url = 'CreditCheck/checkcredits?username='.$username.'&password='.$password;

        try {
            $response = $this->client->get($url)->getBody()->getContents();
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