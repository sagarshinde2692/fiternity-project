<?PHP

/** 
 * ControllerName : OrderController.
 * Maintains a list of functions used for OrderController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;


class OrderController extends \BaseController {

	protected $customermailer;

	public function __construct(CustomerMailer $customermailer) {

		$this->customermailer	=	$customermailer;

		$this->ordertypes 		= 	array('memberships');

	}

	//create cod order for customer
	public function generateCodOrder(){

		$data				=	Input::json()->all();

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Mmessageissing - customer_name");
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

		if(empty($data['customer_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_location");
		}

		if(empty($data['type'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing Order Type - type");
		}

		if (!in_array($data['type'], $this->ordertypes)) {
			return $resp 	= 	array('status' => 500,'message' => "Invalid Order Type");
		}	

		$orderid 			=	Order::max('_id') + 1;

		$data 				= 	Input::json()->all();

		array_set($data, 'status', '0');

		array_set($data, 'payment_mode', 'cod');

		$order 				= 	new Order($data);

		$order->_id 		= 	$orderid;

		$orderstatus   		= 	$order->save();


		//SEND COD EMAIL TO CUSTOMER

		$sndCodDEmail	= 	$this->customermailer->sendCodOrderMail($order->toArray());

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

		if(empty($data['customer_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_location");
		}

		if(empty($data['type'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing Order Type - type");
		}

		if (!in_array($data['type'], $this->ordertypes)) {
			return $resp 	= 	array('status' => 500,'message' => "Invalid Order Type");
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
			$sndWelcomeMail	= 	$this->customermailer->sendPgOrderMail($order->toArray());

			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");

			return Response::json($resp);
		}

		$orderdata 		=	$order->update($data);

		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");

		return Response::json($resp);

	}



}
