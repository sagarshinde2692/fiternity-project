<?PHP

/**
 * ControllerName : CustomofferordersController.
 * Maintains a list of functions used for CustomofferordersController.
 *
 * @author Renuka Aggarwal <renu17a@gmail.com>
 */

use \GuzzleHttp\Client;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;

class CustomofferorderController extends \BaseController
{

    protected $base_uri = false;
    protected $debug = false;
    protected $client;
    protected $customermailer;
	protected $customersms;

    public function __construct(
    	CustomerMailer $customermailer,
		CustomerSms $customersms
    ){
    	$this->customermailer		=	$customermailer;
		$this->customersms 			=	$customersms;
        $this->initClient();
    }

    public function initClient($debug = false,$base_uri = false) {

        $debug = ($debug) ? $debug : $this->debug;
        $base_uri = ($base_uri) ? $base_uri : Config::get('app.url');
        $this->client = new Client( ['debug' => $debug, 'base_uri' => $base_uri] );

    }

    public function BookingFromCustomOfferOrder(){

        $data		=	Input::json()->all();

        // Check valid orderID, payment status, expiry date validity....
        if(empty($data['customofferorder_id'])){
            $resp 	= 	array("status"=>400,"message" => "Order ID is required");
            return Response::json($resp,400);
        }
        $customofferorder_id = $data['customofferorder_id'] = (int) $data['customofferorder_id'];
        $customofferorder = Customofferorder::find($customofferorder_id);
        if(empty($customofferorder)){
            $resp 	= 	array("status"=>400,"message" => "Invalid order ID");
            return Response::json($resp,400);
        }
        if(!isset($data['campaign']) || $data['campaign'] == ''){
            $resp 	= 	array("status"=>400,"message" => "Campaign Name is required");
            return Response::json($resp,400);
        }
        if($customofferorder['status'] !== '1'){
            $resp 	= 	array("status"=>422,"message" => "Booking is allowed only after successful payment");
            return Response::json($resp,422);
        }
        if(Carbon::now() > $customofferorder['expiry_date']){
            $resp 	= 	array("status"=>422,"message" => "Your pass validity has been expired");
            return Response::json($resp,422);
        }

        if($data['type'] !== $customofferorder['quantity_type']){
            $resp 	= 	array("status"=>422,"message" => "This type of session is not allowed in this pass");
            return Response::json($resp,422);
        }

        if($data['customer_email'] !== $customofferorder['customer_email']){
            $resp 	= 	array("status"=>422,"message" => "email not matching");
            return Response::json($resp,422);
        }

        // if type matches with quantity_type then proceed...else throw error of type is not allowed for order...
        if($customofferorder['used_qty'] >= $customofferorder['allowed_qty']){
            $resp 	= 	array("status"=>422,"message" => "reached max of allowed qty");
            return Response::json($resp,422);
        }
//        $data['campaign'] = 'yogaday';

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

    public function tmpOrder(){

    	$data = Input::json()->all();

    	$rules = [
			'customer_name' => 'required|max:255',
			'customer_email' => 'required|email|max:255',
			'customer_phone' => 'required|max:15',
			'customoffer_id' => 'required',
		];

		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' => error_message($validator->errors())),400);
		}else{

			$offer = Customoffer::find((int)$data['customoffer_id']);

			if($offer){

				$data['customer_id'] = autoRegisterCustomer($data);

				$data['quantity_type'] = $offer->quantity_type;
				$data['allowed_qty'] = $offer->quantity;
				$data['validity'] = $offer->validity;
				$data['price_of_one'] = 300;
				if($offer->quantity != 1000){
					$data['price_of_one'] = round(($offer->price/$offer->quantity), 2);
				}
				$data['used_qty'] = 0;
				$data['status'] = "0";
				$data['expiry_date'] = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addDays((int) $data['validity']);
				$data['price'] = $offer->price;
				$data['pass_type'] = $offer->title;
				$data['code'] = $this->generateRandomString();

				$order = new Customofferorder($data);
				$order->_id = Customofferorder::max('_id') + 1;
				$order->save();

                if($offer->price == 0){
                    return $this->captureOrder($order->_id);
                }

				return Response::json(array('status' => 200,'message' => 'Tmp order generated sucessfull','order_id'=>$order->_id),200);

			}else{

				return Response::json(array('status' => 400,'message' => 'No offer found'),400);
			}
    	}
	}

	function generateRandomString($length = 10) {

	    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}


	public function captureOrder($order_id){

		$order = Customofferorder::find((int)$order_id);

		if($order){

			if($order->status == "1"){
				return Response::json(array('status' => 200,'message' => 'Already Purchased','order'=>$order),200);
			}

			$order->status = "1";

			$order->email_yogaday = $this->customermailer->yogaDayPass($order->toArray());
			$order->sms_yogaday = $this->customersms->yogaDayPass($order->toArray());

			$order->update();

			return Response::json(array('status' => 200,'message' => 'Successfull Created Order','order'=>$order),200);

		}else{

			return Response::json(array('status' => 400,'message' => 'No Order Offer found'),400);
		}
		
	}

	public function customerTokenDecode($token){

		$jwt_token = $token;
		$jwt_key = Config::get('app.jwt.key');
		$jwt_alg = Config::get('app.jwt.alg');
		$decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

		return $decodedToken;
	}

	public function getOrders(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = (int)$decoded->customer->_id;
		$order  = array();

		$order = Customofferorder::where('customer_id',$customer_id)->orderBy('_id', 'desc')->get();

		return Response::json(array('status' => 200,'order'=>$order),200);

	}

    public function getdetails($id){
        $id = intval($id);
        $order = Customoffer::where('_id',$id)->first();

        return Response::json(array('status' => 200,'customoffer'=>$order),200);

    }

}