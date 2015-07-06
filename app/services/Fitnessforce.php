<?PHP namespace App\Services;

use \GuzzleHttp\Exception\RequestException;
//use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Client;

Class Fitnessforce {


    protected $base_uri = 'http://27.106.109.11:8088/FitnessForceApi/api/';
    protected $debug = false;
    protected $client;
    protected $key = 'F862975730294C0F82E24DD224A26890';


    public function __construct() {

        $this->initClient();
    }

    public function initClient($debug = false,$base_uri = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = ($base_uri) ? $base_uri : $this->base_uri;
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }


    /*
        Appointment Status
        Booked​     :   [Appointment schedule by a member/enquiry.] 
        Attended​   :   [Appointment attended by a member/enquiry.] 
        No Show​        :   [member/enquiry did not turned up on the appointment date .] 
        Cancelled​  :­      [Appointment cancelled by either member/enquiry or user.] 
    */
    public function createAppointment ($data = false){

        if(!$data){
            $error = [  'status'=>400,
                    'reason'=>'data not found'
            ];
            return $error;
        }

        $booktrial = $data['booktrial'];
        $finder = $data['finder'];

        if(!$finder){
            $error = [  'status'=>400,
                    'reason'=>'finder not found'
            ];
            return $error;
        }

        $json = [];
        $json['authenticationkey'] = $this->key;
        $json['trialowner'] = "AUTO";
        $json['name'] = $booktrial->customer_name;
        $json['mobileno'] = $booktrial->customer_phone; 
        $json['emailaddress'] = $booktrial->customer_email;
        $json['startdate'] = date('d-M-Y',strtotime($booktrial->schedule_date_time));
        $json['enddate'] = date('d-M-Y',strtotime($booktrial->schedule_date_time));
        $json['starttime'] = $booktrial->schedule_slot_start_time;
        $json['endtime'] = $booktrial->schedule_slot_end_time;

        try {
            $response = json_decode($this->client->post('Appointment',['json'=>$json])->getBody()->getContents());
            $return  = ['status'=>200,
                        'data'=>(array) $response->success[0]
            ];
            return $return;
        }catch (RequestException $e) {
            $responce = $e->getResponse();
            $error = [  'status'=>$responce->getStatusCode(),
                        'reason'=>$responce->getReasonPhrase()
            ];

            return $error;
        }catch (Exception $e) {
            $error = [  'status'=>400,
                        'reason'=>'Error'
            ];

            return $error;
        }

    }

    public function getAppointmentStatus($appointmentid){

        $url = 'Appointment?authenticationkey='.$this->key.'&appointmentid='.$appointmentid;

        try {
            $response = json_decode($this->client->get($url)->getBody()->getContents());
            $return  = ['status'=>200,
                        'data'=>(array) $response->success[0]
            ];
            return $return;
        }catch (RequestException $e) {
            $responce = $e->getResponse();
            $error = [  'status'=>$responce->getStatusCode(),
                        'message'=>$responce->getReasonPhrase()
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