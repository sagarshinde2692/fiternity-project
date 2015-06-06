<?PHP

/** 
 * ControllerName : CustomerController.
 * Maintains a list of functions used for CustomerController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;


class CustomerController extends \BaseController {

	protected $customermailer;

	public function __construct(CustomerMailer $customermailer) {

		$this->customermailer	=	$customermailer;

	}

    // Listing Schedule Tirals for Normal Customer
	public function getAutoBookTrials($customeremail){

		$selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt');

		$trials 		=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
										->where('customer_email', '=', $customeremail)
										->whereIn('booktrial_type', array('auto'))
										->orderBy('_id', 'desc')
										->get($selectfields)->toArray();

		if(count($trials) < 1){

			$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
			
			return Response::json($resp);
		}

		$customertrials  = 	$trial = array();

		$currentDateTime =	\Carbon\Carbon::now();

		foreach ($trials as $trial){

			$scheduleDateTime 				=	Carbon::parse($trial['schedule_date_time']);
			
			$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 0) ? false : true;
			
			array_set($trial, 'passed', $slot_datetime_pass_status);
			
			array_push($customertrials, $trial);
		}

		$resp 	= 	array('status' => 200,'trials' => $customertrials,'message' => 'List of scheduled trials');
		return Response::json($resp);
	}


	// Listing Schedule Tirals for Fitcard Customer
	public function getFitcardAutoBookTrials($customeremail){

		$selectfields 	=	array('finder', 'finder_id', 'finder_name', 'finder_slug', 'service_name', 'schedule_date', 'schedule_slot_start_time', 'schedule_date_time', 'schedule_slot_end_time', 'code', 'going_status', 'going_status_txt');
		
		$trials 		=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
							->where('customer_email', '=', $customeremail)
							->where('fitcard_user', 1)
							->whereIn('booktrial_type', array('auto'))
							->orderBy('_id', 'desc')
							->get($selectfields)->toArray();

		if(count($trials) < 1){

			$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
			
			return Response::json($resp);
		}

		$customertrials  = 	$trial = array();
		$currentDateTime =	\Carbon\Carbon::now();

		foreach ($trials as $trial){

			$scheduleDateTime 				=	Carbon::parse($trial['schedule_date_time']);
			
			$slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 0) ? false : true;
			
			array_set($trial, 'passed', $slot_datetime_pass_status);
			
			// return $trial; 
			array_push($customertrials, $trial);
		}

		$resp 	= 	array('status' => 200,'trials' => $customertrials,'message' => 'List of scheduled trials');

		return Response::json($resp);
	}


	// Get Particular Tiral of Customer
	public function getAutoBookTrial($trialid){

		$selectfields 	=	array('finder', 'finder_id','finder_name','finder_slug','service_name','schedule_date','schedule_slot_start_time','schedule_slot_end_time','code');
		$trial 			=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))->where('_id', '=', intval($trialid) )->where('going_status', '=', 1)->first($selectfields);

		if(!$trial){

			$resp 	= 	array('status' => 200, 'trial' => $trial, 'message' => 'No trial Exist :)');
			
			return Response::json($resp);
		}

		$resp 	= 	array('status' => 200, 'trial' => $trial, 'message' => 'Particular Tiral of Customer');
		
		return Response::json($resp);
	}


	//capturePayment for book schedule 
	public function capturePayment(){

		$data					=	Input::all();
		$orderid 				=	Booktrialorder::max('_id') + 1;
		$booktrialorder 		= 	new Booktrialorder($data);
		$booktrialorder->_id 	= 	$orderid;
		$order   				= 	$booktrialorder->save();

		if(Input::get('status') == 'success'){
			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
			return Response::json($resp);
		}

		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		return Response::json($resp);

	}


	//create cod order for fitcard
	public function generateFitCardCodOrder(){

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

		if(empty($data['customer_location'])){
			return $resp 	= 	array('status' => 500,'message' => "Data Missing - customer_location");
		}

		$orderid 			=	Order::max('_id') + 1;

		$data = array(
			'customer_name'		=>		Input::json()->get('customer_name'),
			'customer_phone'	=>		Input::json()->get('customer_phone'),
			'customer_email'	=>		Input::json()->get('customer_email'),
			'customer_location'	=>		Input::json()->get('customer_location'),
			'customer_identity'	=>		Input::json()->get('customer_identity'),
			'fitcardno'			=>		intval((10000 + intval($orderid)) - 10000),
			'type'				=>		'fitcardbuy',
			'payment_mode'		=>		'cod',
			'status'			=>		'0'	
			);

		$order 				= 	new Order($data);
		$order->_id 		= 	$orderid;
		$orderstatus   		= 	$order->save();

		//send welcome email to cod customer
		$sndWelcomeMail	= 	$this->customermailer->fitcardCodWelcomeMail($order->toArray());

		$resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Order Successful :)");

		return Response::json($resp);

	}

	//generate fitcard temp order
	public function generateFitCardTmpOrder(){

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

		$orderid 			=	Order::max('_id') + 1;

		$data = array(
			'customer_name'		=>		Input::json()->get('customer_name'),
			'customer_phone'	=>		Input::json()->get('customer_phone'),
			'customer_email'	=>		Input::json()->get('customer_email'),
			'customer_location'	=>		Input::json()->get('customer_location'),
			'customer_identity'	=>		Input::json()->get('customer_identity'),
			'fitcardno'			=>		intval((10000 + intval($orderid)) - 10000),
			'type'				=>		'fitcardbuy',
			'payment_mode'		=>		'paymentgateway',
			'status'			=>		'0'	
			);

		$order 			= 	new Order($data);

		$order->_id 		= 	$orderid;

		$orderstatus   		= 	$order->save();

		$resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Transaction details for tmp fitcard buy :)");
		return Response::json($resp);

	}


	//capture order status for customer
	public function captureOrderPayment(){

		$data		=	Input::json()->all();

		$orderid 	=	(int) Input::json()->get('orderid');

		$order 		= 	Order::findOrFail($orderid);

		if(Input::json()->get('status') == 'success'){

			array_set($data, 'status', '1');

			$orderdata 	=	$order->update($data);
			
			//send welcome email to payment gateway customer
			$sndWelcomeMail	= 	$this->customermailer->fitcardPaymentGateWelcomeMail($order->toArray());

			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");

			return Response::json($resp);
		}

		$orderdata 		=	$order->update($data);

		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		
		return Response::json($resp);

	}
	

	
}
