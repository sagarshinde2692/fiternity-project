<?PHP

/** 
 * ControllerName : OrderController.
 * Maintains a list of functions used for OrderController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;



class OrderController extends \BaseController {

	protected $customermailer;

	protected $customersms;


	public function __construct(CustomerMailer $customermailer, CustomerSms $customersms) {

		$this->customermailer		=	$customermailer;

		$this->customersms 			=	$customersms;

		$this->ordertypes 		= 	array('memberships');

	}

	//create cod order for customer
	public function generateCodOrder(){

		$data				=	Input::json()->all();

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_name");
		}

		if(empty($data['customer_email'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_email");
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			return $resp 	= 	array('status' => 500,'message' => "Invalid Email Id");
		} 
		
		if(empty($data['customer_identity'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_identity");
		}

		if(empty($data['customer_phone'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_phone");
		}

		if(empty($data['customer_source'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_source");
		}
		
		if(empty($data['customer_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_location");
		}

		if(empty($data['city_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - city_id");
		}	

		if(empty($data['finder_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_id");
		}

		if(empty($data['finder_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_name");
		}	

		if(empty($data['finder_address'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_address");
		}	

		if(empty($data['service_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - service_id");
		}

		if(empty($data['service_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - service_name");
		}

		if(empty($data['type'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing Order Type - type");
		}

		if (!in_array($data['type'], $this->ordertypes)) {
			return $resp 	= 	array('status' => 500,'message' => "Invalid Order Type");
		}


		//Validation base on order type

		if($data['type'] == 'memberships')){

			if(empty($data['service_duration'])){
				return $resp 	= 	array('status' => 500,'message' => "Data Missing - service_duration");
			}

		}




		$orderid 			=	Order::max('_id') + 1;

		$data 				= 	Input::json()->all();

		array_set($data, 'status', '0');

		array_set($data, 'payment_mode', 'cod');

		$order 				= 	new Order($data);

		$order->_id 		= 	$orderid;

		$orderstatus   		= 	$order->save();

		//SEND COD EMAIL TO CUSTOMER
		$sndCodEmail	= 	$this->customermailer->sendCodOrderMail($order->toArray());

		//SEND COD SMS TO CUSTOMER
		$sndCodSms	= 	$this->customersms->sendCodOrderSms($order->toArray());

		$resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Order Successful :)");

		return Response::json($resp);

	}

	//generate fitcard temp order
	public function generateTmpOrder(){

		$data			=	Input::json()->all();

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_name");
		}

		if(empty($data['customer_email'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_email");
		}

		if(empty($data['customer_phone'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_phone");
		}

		if(empty($data['customer_identity'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_identity");
		}

		if(empty($data['customer_source'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_source");
		}

		if(empty($data['customer_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_location");
		}

		if(empty($data['type'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing Order Type - type");
		}

		if (!in_array($data['type'], $this->ordertypes)) {
			return $resp 	= 	array('status' => 500,'message' => "Invalid Order Type");
		}	

		if(empty($data['city_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - city_id");
		}	

		if(empty($data['finder_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - finder_id");
		}	

		if(empty($data['service_id'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - service_id");
		}

		if(empty($data['service_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - service_name");
		}

		$orderid 			=	Order::max('_id') + 1;

		$data 				= 	Input::json()->all();

		array_set($data, 'status', '0');

		array_set($data, 'payment_mode', 'paymentgateway');

		$order 				= 	new Order($data);

		$order->_id 		= 	$orderid;

		$orderstatus   		= 	$order->save();

		$resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Transaction details for tmp order :)");

		return Response::json($resp);

	}


	//capture order status for customer
	public function captureOrderStatus(){

		$data		=	Input::json()->all();

		$orderid 	=	(int) Input::json()->get('orderid');

		$order 		= 	Order::findOrFail($orderid);

		if(Input::json()->get('status') == 'success'){

			array_set($data, 'status', '1');

			$orderdata 	=	$order->update($data);

			//send welcome email to payment gateway customer
			$sndPgMail	= 	$this->customermailer->sendPgOrderMail($order->toArray());

			//SEND COD SMS TO CUSTOMER
			$sndPgSms	= 	$this->customersms->sendPgOrderSms($order->toArray());

			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");

			return Response::json($resp);
		}

		$orderdata 		=	$order->update($data);

		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");

		return Response::json($resp);

	}



}
