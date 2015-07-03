<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
//use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Client;

Class Fitnessforce {


	protected $base_uri = 'http://27.106.109.11:8088/fitnessForceApi/api/';
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

	public function createAppointment ($data = false){

		if($data){
			
			try {
			    $response = json_decode($this->client->post('Appointment',['json'=>$data])->getBody()->getContents());
			    $return  = ['status'=>200,
			    			'data'=>(array) $response->success[0]
			    ];
			    return $return;
			}catch (RequestException $e) {
				$responce = $e->getResponse();
				$error = [	'status'=>$responce->getStatusCode(),
							'reason'=>$responce->getReasonPhrase()
				];

	    		return $error;
			}catch (Exception $e) {
				$error = [	'status'=>400,
							'reason'=>'Error'
				];

	    		return $error;
			}
		}else{
			$error = [	'status'=>400,
					'reason'=>'data not found'
			];
			return $error;
		}
		
	}

}