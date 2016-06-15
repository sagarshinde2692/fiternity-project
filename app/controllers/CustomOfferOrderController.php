<?PHP

/**
 * ControllerName : CustomofferordersController.
 * Maintains a list of functions used for CustomofferordersController.
 *
 * @author Renuka Aggarwal <renu17a@gmail.com>
 */

use \GuzzleHttp\Client;

class CustomofferorderController extends \BaseController
{

    protected $base_uri = 'http://fitapi.com';
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

    public function BookingFromCustomOfferOrder(){

        $data = Input::all();
        $customofferorder_id = $data['customofferorder_id'] = (int) $data['customofferorder_id'];
        $customofferorder = Customofferorder::find($customofferorder_id);


        // Check valid orderID, payment status, expiry date validity....
        if(empty($customofferorder)){
            $resp 	= 	array("message" => "Invalid order ID");
            return Response::json($resp,400);
        }
        if($customofferorder['status'] !== '1'){
            $resp 	= 	array("message" => "Booking is allowed only after successful payment");
            return Response::json($resp,422);
        }
        if($customofferorder['used_qty'] >= $customofferorder['allowed_qty']){
            $resp 	= 	array("message" => "You have reached the maximum bookings allowed on this pass");
            return Response::json($resp,422);
        }
        if(Carbon::now() > $customofferorder['expiry_date']){
            $resp 	= 	array("message" => "Your pass validity has been expired");
            return Response::json($resp,422);
        }

        // if type matches with quantity_type then proceed...else throw error of type is not allowed for order...
        if($data['type'] !== $customofferorder['quantity_type']){
            $resp 	= 	array("message" => "This type of session is not allowed in this pass");
            return Response::json($resp,422);
        }

        // Generate temp order....
        try {
            $tmpOrderResponse = json_decode($this->client->post('generatetmporder',['json'=>$data])->getBody()->getContents());

        }catch (GuzzleHttp\Exception\ClientException $e) {
            $tmpOrderResponse = $e->getResponse();
            return $tmpOrderResponse->getBody()->getContents();
        }

        // pass payload and hit success URL based on type....
        $storebooktrial_types = array('workout-session','booktrials','3daystrial','vip_booktrials');
        if(in_array($data['type'],$storebooktrial_types)) {
            $data['order_id'] = $tmpOrderResponse->order->_id;
            $data['status'] = 'success';
            try {
                $orderSuccessResponse = json_decode($this->client->post('storebooktrial',['json'=>$data])->getBody()->getContents());

            }catch (GuzzleHttp\Exception\ClientException $e) {
                $orderSuccessResponse = $e->getResponse();
                return $orderSuccessResponse->getBody()->getContents();
            }
        }

        // Decrease used_qty by 1.....
        $customofferorder['used_qty'] = $customofferorder['used_qty'] + 1;
        $customofferorder->update($customofferorder->toArray());
        return json_encode($orderSuccessResponse);
    }
}