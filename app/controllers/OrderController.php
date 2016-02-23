<?PHP

/** 
 * ControllerName : OrderController.
 * Maintains a list of functions used for OrderController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\FinderSms as FinderSms;
use App\Services\Sidekiq as Sidekiq;

class OrderController extends \BaseController {

	protected $customermailer;
	protected $customersms;
	protected $sidekiq;
	protected $findermailer;
	protected $findersms;

	public function __construct(CustomerMailer $customermailer, CustomerSms $customersms, Sidekiq $sidekiq,FinderMailer $findermailer, FinderSms $findersms) {
		parent::__construct();	
		$this->customermailer		=	$customermailer;
		$this->customersms 			=	$customersms;
		$this->sidekiq 				= 	$sidekiq;
		$this->findermailer		=	$findermailer;
		$this->findersms 			=	$findersms;
		$this->ordertypes 		= 	array('memberships','booktrials','fitmaniadealsofday','fitmaniaservice','arsenalmembership','zumbathon','booiaka','zumbaclub','fitmania-dod','fitmania-dow','fitmania-membership-giveaways');
	}


	//capture order status for customer used membership by
	public function captureOrderStatus(){

		$data			=	array_except(Input::json()->all(), array('preferred_starting_date'));
		if(empty($data['order_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - order_id");
			return  Response::json($resp, 400);
		}

		if(empty($data['status'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - status");
			return  Response::json($resp, 400);
		}
		$orderid 	=	(int) Input::json()->get('order_id');
		$order 		= 	Order::findOrFail($orderid);
		if(Input::json()->get('status') == 'success'){

			array_set($data, 'status', '1');
			array_set($data, 'order_action', 'bought');
			$orderdata 	=	$order->update($data);

			//send welcome email to payment gateway customer

			try {

				if(isset($order->referal_trial_id) && $order->referal_trial_id != ''){

					$trial = Booktrial::find((int) $order->referal_trial_id);

					if($trial){

						$bookdata = array();

						array_set($bookdata, 'going_status', 4);
						array_set($bookdata, 'going_status_txt', 'purchased');
						array_set($bookdata, 'booktrial_actions', '');
						array_set($bookdata, 'followup_date', '');
						array_set($bookdata, 'followup_date_time', '');

						$trial->update($bookdata);
					}
				}
				
			} catch (Exception $e) {

				Log::error($e);
				
			}	

			if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
				$order->update(['email_not_sent'=>'captureOrderStatus']);
			}else{
				$sndPgMail	= 	$this->customermailer->sendPgOrderMail($order->toArray());
				$sndPgMail	= 	$this->findermailer->sendPgOrderMail($order->toArray());
			} 
			
			//SEND payment gateway SMS TO CUSTOMER and vendor
			$sndPgSms	= 	$this->customersms->sendPgOrderSms($order->toArray());
			$sndPgSms	= 	$this->findersms->sendPgOrderSms($order->toArray());

			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
			return Response::json($resp);
		}

		$orderdata 		=	$order->update($data);
		$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
		return Response::json($resp);
	}



	//create cod order for customer
	public function generateCodOrder(){

		$data			=	array_except(Input::json()->all(), array('preferred_starting_date'));
		

		if(empty($data['customer_name'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_name");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_email'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
			return  Response::json($resp, 400);
		}

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			$resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
			return  Response::json($resp, 400);
		} 
		
		if(empty($data['customer_identity'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_identity");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_phone'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
			return  Response::json($resp, 400);
		}

		if(empty($data['customer_source'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_source");
			return  Response::json($resp, 400);
		}
		
		if(empty($data['customer_location'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - customer_location");
			return  Response::json($resp, 400);
		}

		if(empty($data['city_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - city_id");
			return  Response::json($resp, 400);
		}	

		if(empty($data['finder_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
			return  Response::json($resp, 400);
		}

		if(empty($data['finder_name'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_name");
			return  Response::json($resp, 400);
		}	

		if(empty($data['finder_address'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - finder_address");
			return  Response::json($resp, 400);
		}	

		if(empty($data['service_id'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - service_id");
			return  Response::json($resp, 400);
		}

		if(empty($data['service_name'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
			return  Response::json($resp, 400);
		}
		
		if(empty($data['service_duration'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - service_duration");
			return  Response::json($resp, 400);
		}

		if(empty($data['type'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing Order Type - type");
			return  Response::json($resp, 400);
		}

		if (!in_array($data['type'], $this->ordertypes)) {
			$resp 	= 	array('status' => 400,'message' => "Invalid Order Type");
			return  Response::json($resp, 400);
		}

		//Validation base on order type
		if($data['type'] == 'memberships' || $data['type'] == 'booktrials' || $data['type'] == 'fitmaniaservice'|| $data['type'] == 'zumbathon'){
			if( empty($data['service_duration']) ){
				$resp 	= 	array('status' => 400,'message' => "Data Missing - service_duration");
				return  Response::json($resp, 400);
			}
		}

		$orderid 			=	Order::max('_id') + 1;
		$data			=	array_except(Input::json()->all(), array('preferred_starting_date'));
		if(trim(Input::json()->get('preferred_starting_date')) != '' && trim(Input::json()->get('preferred_starting_date')) != '-'){
			$date_arr = explode('-', Input::json()->get('preferred_starting_date'));
			$preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
			array_set($data, 'preferred_starting_date', $preferred_starting_date);
		}
		// return $data;
		
		$customer_id 		=	(Input::json()->get('customer_id')) ? Input::json()->get('customer_id') : $this->autoRegisterCustomer($data);	

		if(trim(Input::json()->get('finder_id')) != '' ){

			$finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval(Input::json()->get('finder_id')))->first()->toArray();

			$finder_city						=	(isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
			$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
			$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
			$finder_vcc_email					= 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
			$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
			$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
			$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
			$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;	
			$share_customer_no					= 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;	

			array_set($data, 'finder_city', trim($finder_city));
			array_set($data, 'finder_location', trim($finder_location));
			array_set($data, 'finder_address', trim($finder_address));
			array_set($data, 'finder_vcc_email', trim($finder_vcc_email));
			array_set($data, 'finder_vcc_mobile', trim($finder_vcc_mobile));
			array_set($data, 'finder_poc_for_customer_name', trim($finder_poc_for_customer_name));
			array_set($data, 'finder_poc_for_customer_no', trim($finder_poc_for_customer_no));
			array_set($data, 'show_location_flag', $show_location_flag);
			array_set($data, 'share_customer_no', $share_customer_no);

		}
		
		array_set($data, 'customer_id', intval($customer_id));
		array_set($data, 'status', '0');
		array_set($data, 'payment_mode', 'cod');
		$order 				= 	new Order($data);
		$order->_id 		= 	$orderid;
		$orderstatus   		= 	$order->save();



		//SEND COD EMAIL TO CUSTOMER
		$sndCodEmail	= 	$this->customermailer->sendCodOrderMail($order->toArray());
		//$sndCodEmail	= 	$this->findermailer->sendCodOrderMail($order->toArray());

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

		// $userdata	=	array_except(Input::all(), array());

		$data			=	array_except(Input::json()->all(), array('preferred_starting_date'));

		$data['service_duration'] = (empty($data['service_duration'])) ? '1 Meal' : $data['service_duration'];
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

		if (!in_array($data['preferred_starting_date'], $this->ordertypes)) {
			$resp 	= 	array('status' => 404,'message' => "Data Missing - preferred_starting_date");
			return Response::json($resp,404);			
		}

		//Validation base on order type
		if($data['type'] == 'fitmania-dod' || $data['type'] == 'fitmania-dow'){

			if( empty($data['serviceoffer_id']) ){
				$resp 	= 	array('status' => 404,'message' => "Data Missing - serviceoffer_id");
				return Response::json($resp,404);				
			}

			if( empty($data['preferred_starting_date']) ){
				$resp 	= 	array('status' => 404,'message' => "Data Missing - preferred_starting_date");
				return Response::json($resp,404);				
			}

			/* limit | buyable | sold | acitve | left */
			$serviceoffer 		= 	Serviceoffer::find(intval($data['serviceoffer_id']));
			if(isset($serviceoffer->buyable) && intval($serviceoffer->buyable) == 0){
				$resp 	= 	array('status' => 404,'message' => "Buyable limit reach to zero :)");
				return Response::json($resp,404);				
			}

			if(isset($serviceoffer->buyable) && intval($serviceoffer->buyable) > 0){
				$offer_buyable 		=  	$serviceoffer->buyable - 1;
			}else{
				$offer_buyable 		=  	intval($serviceoffer->limit) - 1;
			}
			$service_offerdata  = 	['buyable' => intval($offer_buyable)];
			$serviceoffer->update($service_offerdata);

		}

		if($data['type'] == 'memberships' || $data['type'] == 'booktrials' || $data['type'] == 'fitmaniadealsofday' || $data['type'] == 'fitmaniaservice'){
			if( empty($data['service_duration']) ){
				$resp 	= 	array('status' => 404,'message' => "Data Missing - service_duration");
				return Response::json($resp,404);				
			}
		}

		//Validation base on order type for sms body and email body  zumbathon','booiaka
		if($data['type'] == 'zumbathon' || $data['type'] == 'booiaka' || $data['type'] == 'fitmaniadealsofday' || $data['type'] == 'fitmaniaservice' || $data['type'] == 'zumbaclub' || $data['type'] == 'kutchi-minithon'){
			if( empty($data['sms_body']) ){
				$resp 	= 	array('status' => 404,'message' => "Data Missing - sms_body");
				return Response::json($resp,404);				
			}

			if( empty($data['email_body1']) ){
				$resp 	= 	array('status' => 404,'message' => "Data Missing - email_body1");
				return Response::json($resp,404);				
			}

			if( empty($data['email_body2']) ){
				$resp 	= 	array('status' => 404,'message' => "Data Missing - email_body2");
				return Response::json($resp,404);				
			}
		}
		// return $data;

		$orderid 			=	Order::max('_id') + 1;
		// $data 				= 	Input::json()->all();
		$customer_id 		=	(Input::json()->get('customer_id')) ? Input::json()->get('customer_id') : $this->autoRegisterCustomer($data);	
		$email_body2 		=	(Input::json()->get('email_body2') != "-") ? Input::json()->get('email_body2') : '';	
		
		if($data['type'] == 'fitmania-dod' || $data['type'] == 'fitmania-dow'){
			$reminderTimeAfter12Min 	=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(12);
			$buyable_after12min 		= 	$this->checkFitmaniaBuyable($orderid ,'checkFitmaniaBuyable', 0, $reminderTimeAfter12Min);
			array_set($data, 'buyable_after12min_queueid', $buyable_after12min);
		}

		if($data['type'] == 'fitmania-dod' || $data['type'] == 'fitmania-dow' || $data['type'] == 'fitmania-membership-giveaways'){
			$peppertapobj 	= 	Peppertap::where('status','=', 0)->first();
			if($peppertapobj){
				array_set($data, 'peppertap_code', $peppertapobj->code);
				$peppertapstatus 	=	$peppertapobj->update(['status' => 1]);
			}
		}

		array_set($data, 'customer_id', intval($customer_id));
		
		if(trim(Input::json()->get('preferred_starting_date')) != '-'){
			$date_arr = explode('-', Input::json()->get('preferred_starting_date'));
			$preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
			array_set($data, 'preferred_starting_date', $preferred_starting_date);
		}


		if(trim(Input::json()->get('finder_id')) != '' ){

			$finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval(Input::json()->get('finder_id')))->first()->toArray();

			$finder_city						=	(isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
			$finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
			$finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
			$finder_vcc_email					= 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
			$finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
			$finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
			$finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
			$show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;	
			$share_customer_no					= 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;
			$finder_lon							= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
			$finder_lat							= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";	

			array_set($data, 'finder_city', trim($finder_city));
			array_set($data, 'finder_location', trim($finder_location));
			array_set($data, 'finder_address', trim($finder_address));
			array_set($data, 'finder_vcc_email', trim($finder_vcc_email));
			array_set($data, 'finder_vcc_mobile', trim($finder_vcc_mobile));
			array_set($data, 'finder_poc_for_customer_name', trim($finder_poc_for_customer_name));
			array_set($data, 'finder_poc_for_customer_no', trim($finder_poc_for_customer_no));
			array_set($data, 'show_location_flag', $show_location_flag);
			array_set($data, 'share_customer_no', $share_customer_no);
			array_set($data, 'finder_lon', $finder_lon);
			array_set($data, 'finder_lat', $finder_lat);

		}

		array_set($data, 'status', '0');
		array_set($data, 'email_body2', trim($email_body2));
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
			$resp 	= 	array('status' => 400,'message' => "Data Missing - order_id");
			return  Response::json($resp, 400);
		}
		if(empty($data['status'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing - status");
			return  Response::json($resp, 400);
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
			$customer->picture = "https://www.gravatar.com/avatar/".md5($data['customer_email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
			$customer->password = md5(time());
			if(isset($customer['customer_phone'])){
				$customer->contact_no = $data['customer_phone'];
			}
			$customer->identity = 'email';
			$customer->account_link = array('email'=>1,'google'=>0,'facebook'=>0,'twitter'=>0);
			$customer->status = "1";
			$customer->ishulluser = 1;
			$customer->save();

			return $inserted_id;
		}  

		return $customer->_id;
	}


	public function buyArsenalMembership(){

		$data			=	Input::json()->all();		
		if(empty($data['order_id'])){
			return Response::json(array('status' => 404,'message' => "Data Missing Order Id - order_id"),404);			
		}
		// return Input::json()->all();
		$orderid 	=	(int) Input::json()->get('order_id');
		$order 		= 	Order::findOrFail($orderid);
		$orderData 	= 	$order->toArray();

		// array_set($data, 'status', '1');
		$buydealofday 			=	$order->update(['status' => '1']);
		$sndsSmsCustomer		= 	$this->customersms->buyArsenalMembership($orderData);

		if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
			$order->update(['email_not_sent'=>'buyArsenalMembership']);
		}else{
			$sndsEmailCustomer		= 	$this->customermailer->buyArsenalMembership($orderData);
		}

		$resp 	= 	array('status' => 200,'message' => "Successfully buy Arsenal Membership :)");

		return Response::json($resp,200);		
	}


	public function buyLandingpagePurchase(){

		$data			=	Input::json()->all();		
		if(empty($data['order_id'])){
			return Response::json(array('status' => 404,'message' => "Data Missing Order Id - order_id"),404);			
		}

		if($data['status'] != "success"){
			return Response::json(array('status' => 404,'message' => "Order Failed"),404);			
		}

		// return Input::json()->all();
		$orderid 	=	(int) Input::json()->get('order_id');
		$order 		= 	Order::findOrFail($orderid);
		$orderData 	= 	$order->toArray();

		// array_set($data, 'status', '1');
		$buydealofday 			=	$order->update(['status' => '1']);
		$sndsSmsCustomer		= 	$this->customersms->buyLandingpagePurchase($orderData);

		if (filter_var(trim($order->customer_email), FILTER_VALIDATE_EMAIL) === false){
			$order->update(['email_not_sent'=>'buyLandingpagePurchase']);
		}else{
			$sndsEmailCustomer		= 	$this->customermailer->buyLandingpagePurchase($orderData);
		}

		$resp 	= 	array('status' => 200,'message' => "Successfully buy Membership :)");

		return Response::json($resp,200);		
	}


	public function exportorders() {

		$order_ids 	=	[5754,5783,5786,5789,5791,5800,5806,5823,5826,5827,5881,5801,5807,5809,5822,5831,5835,5837,5839,5857,5890,5891,5892,5896,5897,5903,5925,5947,5984,5985,5996,5998,6000,6006,6007,6008,6011,6014,6019,6021,6023,6035,6044,6045,6056,6066,6068,6071,6073,6074,6077,6097,6102,6103,6105,6107,6110,6111,6122,6124,6126,6127,6129,6131,6132,6135,6137,6138,6139,6142,6146,6152,6164,6170,6171,6172,6175,6178,6199,6203,6206,6214,6216,6218,6223,6224,6226,6227,6237,6239,6267,6277,6278,6279,6281,6285,6291,6295,6306,6312,6316,6317,6318,6320,6332,6344,6346,6348,6351,6354,6361,6364,6366,6367,6370,6390,6375,6372,6371];
		$orders 	= 	Order::whereIn('_id', $order_ids)->get();

		$fp = fopen('orderlatest.csv', 'w');
		$header = ["ID", "NAME", "EMAIL", "NUMBER", "TYPE" , "AMOUNT" , "ADDRESS"   ];
		fputcsv($fp, $header);

		foreach ($orders as $value) {  
			$fields = [$value->_id, $value->customer_name, $value->customer_email, $value->customer_phone,  $value->payment_mode, $value->amount, $value->customer_location];
			fputcsv($fp, $fields);
		}


	}


	public function getOrderDetail($orderid){

		$orderdata 		=	Order::find(intval($orderid));

		if(!$orderdata){
			return $this->responseNotFound('Order does not exist');
		}

		$responsedata 	= ['orderdata' => $orderdata,  'message' => 'Order Detial'];
		return Response::json($responsedata, 200);

	}



	public function checkFitmaniaBuyable($order_id, $label = 'label', $priority = 0, $delay = 0){

		if($delay !== 0){
			$delay = $this->getSeconds($delay);
		}

		$payload = array('order_id'=>$order_id,'delay'=>$delay,'priority'=>$priority,'label' => $label);
		$route  = 'fitmaniabuyable';
		$result  = $this->sidekiq->sendToQueue($payload,$route);

		if($result['status'] == 200){
			return $result['task_id'];
		}else{
			return $result['status'].':'.$result['reason'];
		}

	}




}
