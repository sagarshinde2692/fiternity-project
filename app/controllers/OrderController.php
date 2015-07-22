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
		$this->ordertypes 		= 	array('memberships','booktrials','fitmaniadealsofday','fitmaniaservice');
	}


	//capture order status for customer
	public function captureOrderStatus(){

		$data		=	Input::json()->all();
		if(empty($data['order_id'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - order_id");
		}

		if(empty($data['status'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - status");
		}
		$orderid 	=	(int) Input::json()->get('order_id');
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

	//create cod order for customer
	public function generateCodOrder(){

		$data				=	Input::json()->all();

		if(empty($data['customer_name'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_name");
		}

		if(empty($data['customer_email'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_email");
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			return $resp 	= 	array('status' => 404,'message' => "Invalid Email Id");
		} 
		
		if(empty($data['customer_identity'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_identity");
		}

		if(empty($data['customer_phone'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_phone");
		}

		if(empty($data['customer_source'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_source");
		}
		
		if(empty($data['customer_location'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_location");
		}

		if(empty($data['city_id'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - city_id");
		}	

		if(empty($data['finder_id'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - finder_id");
		}

		if(empty($data['finder_name'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - finder_name");
		}	

		if(empty($data['finder_address'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - finder_address");
		}	

		if(empty($data['service_id'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - service_id");
		}

		if(empty($data['service_name'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - service_name");
		}
		
		if(empty($data['service_duration'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - service_duration");
		}

		if(empty($data['type'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing Order Type - type");
		}

		if (!in_array($data['type'], $this->ordertypes)) {
			return $resp 	= 	array('status' => 404,'message' => "Invalid Order Type");
		}

		//Validation base on order type
		if($data['type'] == 'memberships' || $data['type'] == 'booktrials'){
			if( empty($data['service_duration']) ){
				return $resp 	= 	array('status' => 404,'message' => "Data Missing - service_duration");
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
		// print_pretty($sndCodSms); exit;

		$resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Order Successful :)");

		return Response::json($resp);

	}


	/**
	 * Generate Temp Order
	 * 
	 *	Service Duration can be (trial, workout session, months, session, etc).
	 */

	public function generateTmpOrder(){

		$data			=	Input::json()->all();

		// $required_fiels = ['customer_name', ];

		if(empty($data['customer_name'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - customer_name");
        	return Response::json($resp,404);			
		}

		if(empty($data['customer_email'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - customer_email");
        	return Response::json($resp,404);			
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			$resp 	= 	array('status' => 404,'message' => "Invalid Email Id");
        	return Response::json($resp,404);			
		} 
		
		if(empty($data['customer_identity'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - customer_identity");
        	return Response::json($resp,404);			
		}

		if(empty($data['customer_phone'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - customer_phone");
        	return Response::json($resp,404);			
		}

		if(empty($data['customer_source'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - customer_source");
        	return Response::json($resp,404);			
		}
		
		if(empty($data['customer_location'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - customer_location");
        	return Response::json($resp,404);			
		}

		if(empty($data['city_id'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - city_id");
        	return Response::json($resp,404);			
		}	

		if(empty($data['finder_id'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - finder_id");
        	return Response::json($resp,404);			
		}

		if(empty($data['finder_name'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - finder_name");
        	return Response::json($resp,404);			
		}	

		if(empty($data['finder_address'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - finder_address");
        	return Response::json($resp,404);			
		}	

		if(empty($data['service_id'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - service_id");
        	return Response::json($resp,404);			
		}

		if(empty($data['service_name'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - service_name");
        	return Response::json($resp,404);			
		}

		if(empty($data['amount'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing - amount");
        	return Response::json($resp,404);			
		}

		if(empty($data['type'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing Order Type - type");
        	return Response::json($resp,404);			
		}

		if (!in_array($data['type'], $this->ordertypes)) {
			$resp 	= 	array('status' => 404,'message' => "Invalid Order Type");
        	return Response::json($resp,404);			
		}

		//Validation base on order type
		if($data['type'] == 'memberships' || $data['type'] == 'booktrials' || $data['type'] == 'fitmaniadealsofday' || $data['type'] == 'fitmaniaservice'){
			if( empty($data['service_duration']) ){
				$resp 	= 	array('status' => 404,'message' => "Data Missing - service_duration");
        		return Response::json($resp,404);				
			}
		}

		$orderid 			=	Order::max('_id') + 1;
		$data 				= 	Input::json()->all();
		$customer_id 		=	(Input::json()->get('customer_id')) ? Input::json()->get('customer_id') : $this->autoRegisterCustomer($data);	
		
		array_set($data, 'customer_id', intval($customer_id));
		array_set($data, 'status', '0');
		array_set($data, 'payment_mode', 'paymentgateway');
		$order 				= 	new Order($data);
		$order->_id 		= 	$orderid;
		$orderstatus   		= 	$order->save();
		$resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Transaction details for tmp order :)");
		return Response::json($resp);

	}

	public function captureFailOrders(){

		$data		=	Input::json()->all();
		if(empty($data['order_id'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - order_id");
		}
		if(empty($data['status'])){
			return $resp 	= 	array('status' => 404,'message' => "Data Missing - status");
		}
		$orderid 	=	(int) Input::json()->get('order_id');
		$order 		= 	Order::findOrFail($orderid);
		$orderdata 	=	$order->update($data);
		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		return Response::json($resp);
	}


	public function autoRegisterCustomer($data){

		$customerdata 	= 	$data;
		$customer 		= 	Customer::active()->where('email', $data['customer_email'])->first();

		if(!$customer) {
			$inserted_id = Customer::max('_id') + 1;
        	$customer = new Customer();
        	$customer->_id = $inserted_id;
	        $customer->name = ucwords($data['customer_name']) ;
	        $customer->email = $data['customer_email'];
	        $customer->picture = "http://www.gravatar.com/avatar/".md5($data['customer_email'])."?s=200&d=http%3A%2F%2Fb.fitn.in%2Favatar.png";
	        $customer->password = md5(time());
	        if(isset($customer['customer_phone'])){
	        	$customer->contact_no = $data['customer_phone'];
	        }
	        $customer->identity = 'email';
	        $customer->account_link = array('email'=>1,'google'=>0,'facebook'=>0,'twitter'=>0);
	        $customer->status = "1";
	        $customer->save();

	        return $inserted_id;
		}  

		return $customer->_id;
	}



}
