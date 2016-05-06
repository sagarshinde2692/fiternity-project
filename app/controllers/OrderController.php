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
		$this->ordertypes 		= 	array('memberships','booktrials','fitmaniadealsofday','fitmaniaservice','arsenalmembership','zumbathon','booiaka','zumbaclub','fitmania-dod','fitmania-dow','fitmania-membership-giveaways','womens-day','eefashrof','crossfit-week','workout-session');
	}


	//capture order status for customer used membership by
	public function captureOrderStatus(){

		$data			=	array_except(Input::json()->all(), array('preferred_starting_date'));

		Log::info('Capture Order Status',$data);

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

		if(isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){

			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Already Status Successfull");
			return Response::json($resp);
		}

		if(Input::json()->get('status') == 'success'){

			array_set($data, 'status', '1');
			array_set($data, 'order_action', 'bought');
			array_set($data, 'batch_time', '');

			if(isset($data['batches']) && $data['batches'] != ""){
				if(is_array($data['batches'])){
					$data['batches'] = $data['batches'];
				}else{
					$data['batches'] = json_decode($data['batches'],true);
				}

				foreach ($data['batches'] as $key => $value) {

					if(isset($value['slots'][0]['start_time']) && $value['slots'][0]['start_time'] != ""){
	    				$data['batch_time'] = strtoupper($value['slots'][0]['start_time']);
	    				break;
	    			}
				}
			}

			$orderdata 	=	$order->update($data);

			//send welcome email to payment gateway customer

			$finder = Finder::find((int)$order->finder_id);

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

			$abundant_category = array(42,45);

			if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
				$order->update(['email_not_sent'=>'captureOrderStatus']);
			}else{
				$sndPgMail	= 	$this->customermailer->sendPgOrderMail($order->toArray());

				//no email to Healthy Snacks Beverages and Healthy Tiffins
				if(!in_array($finder->category_id, $abundant_category)){
					$sndPgMail	= 	$this->findermailer->sendPgOrderMail($order->toArray());
				}
			} 
			
			//SEND payment gateway SMS TO CUSTOMER and vendor
			$sndPgSms	= 	$this->customersms->sendPgOrderSms($order->toArray());

			//no sms to Healthy Snacks Beverages and Healthy Tiffins
			if(!in_array($finder->category_id, $abundant_category)){
				$sndPgSms	= 	$this->findersms->sendPgOrderSms($order->toArray());
			}

			if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !in_array($finder->category_id, $abundant_category)){

				$preferred_starting_date = $order->preferred_starting_date;
				$after3days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 3);
				$after10days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 10);

				$category_slug = "no_category";

				if(isset($order->finder_category_id) && $order->finder_category_id != ""){

					$finder_category_id = $order->finder_category_id;

					$category = Findercategory::find((int)$finder_category_id);

					if($category){
						$category_slug = $category->slug;
					}
				}

				if(isset($order->ratecard_id) || isset($order->duration_day)){

					$validity = 0;

					if(isset($order->ratecard_id) && $order->ratecard_id != ""){

						$ratecard = Ratecard::find($order->ratecard_id);

						if(isset($ratecard->validity) && $ratecard->validity != ""){
							$validity = (int)$ratecard->validity;
						}	
					}

					if(isset($order->duration_day) && $order->duration_day != ""){
						
						$validity = (int)$order->duration_day;
					}
					
					if($validity >= 30){

						if($validity >= 30 && $validity < 90){

							$renewal_date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * $validity)->subMinutes(60 * 24 * 7);
						}

						if($validity >= 90 && $validity < 180){

							$renewal_date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * $validity)->subMinutes(60 * 24 * 15);
						}

						if($validity >= 180 && $validity < 360){

							$renewal_date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * $validity)->subMinutes(60 * 24 * 30);
						}

						if($validity >= 360){

							$renewal_date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * $validity)->subMinutes(60 * 24 * 30);
						}

						$order_data = $order->toArray();

						$newOrder  = Order::where('_id','!=',(int) $order_data['_id'])->where('customer_phone','LIKE','%'.substr($order_data['customer_phone'], -8).'%')->where('missedcall_renew_batch','exists',true)->orderBy('_id','desc')->first();
						if(!empty($newOrder)){
							$batch = $newOrder->missedcall_renew_batch + 1;
						}else{
							$batch = 1;
						}

						$order->missedcall_renew_batch = $batch;

						$missedcall_no = Ozonetelmissedcallno::where('batch',$batch)->where('for','OrderRenewal')->get()->toArray();

						if(empty($missedcall_no)){

							$missedcall_no = Ozonetelmissedcallno::where('batch',1)->where('for','OrderRenewal')->get()->toArray();
						}

						foreach ($missedcall_no as $key => $value) {

							switch ($value['type']) {
								case 'renew': $renew = $value['number'];break;
								case 'alreadyextended': $alreadyextended = $value['number'];break;
								case 'explore': $explore = $value['number'];break;
							}

						}

						$order_data['missedcall1'] = $renew;
						$order_data['missedcall2'] = $alreadyextended;
						$order_data['missedcall3'] = $explore;

						$order_data['category_array'] = $this->getCategoryImage($category_slug);

						$order->customer_email_renewal = $this->customermailer->orderRenewalMissedcall($order_data,$renewal_date);
						$order->customer_sms_renewal = $this->customersms->orderRenewalMissedcall($order_data,$renewal_date);

					}

				}

				$order_data = $order->toArray();

				$order_data['category_array'] = $this->getCategoryImage($category_slug);

				$order->customer_sms_after3days = $this->customersms->orderAfter3Days($order_data,$after3days);
				$order->customer_email_after10days = $this->customermailer->orderAfter10Days($order_data,$after10days);

				$order->update();

			}
			
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

		Log::info('Gnerate COD Order',$data);
		

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
		
		if(empty($data['type'])){
			$resp 	= 	array('status' => 400,'message' => "Data Missing Order Type - type");
			return  Response::json($resp, 400);
		}

		if (!in_array($data['type'], $this->ordertypes)) {
			$resp 	= 	array('status' => 400,'message' => "Invalid Order Type");
			return  Response::json($resp, 400);
		}

		//Validation base on order type
		if($data['type'] == 'memberships'){

			if( empty($data['service_duration']) ){
				$resp 	= 	array('status' => 400,'message' => "Data Missing - service_duration");
				return  Response::json($resp, 400);
			}
		}else{

			$data['service_duration'] = (isset($data['service_duration']) && $data['service_duration'] != "") ? $data['service_duration'] : "";
		}

		$orderid 			=	Order::max('_id') + 1;
		$data			=	array_except(Input::json()->all(), array('preferred_starting_date'));
		if(trim(Input::json()->get('preferred_starting_date')) != '' && trim(Input::json()->get('preferred_starting_date')) != '-'){
			$date_arr = explode('-', Input::json()->get('preferred_starting_date'));
			$preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
			array_set($data, 'preferred_starting_date', $preferred_starting_date);
			array_set($data, 'start_date', $preferred_starting_date);
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

		array_set($data, 'service_name_purchase', $data['service_name']);
		array_set($data, 'service_duration_purchase', $data['service_duration']);
		
		array_set($data, 'customer_id', intval($customer_id));
		array_set($data, 'status', '0');
		array_set($data, 'payment_mode', 'cod');
		$order 				= 	new Order($data);
		$order->_id 		= 	$orderid;
		$orderstatus   		= 	$order->save();

		$device_type						= 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
		$gcm_reg_id							= 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

		if($device_type != '' && $gcm_reg_id != ''){

			$reg_data = array();

			$reg_data['customer_id'] = $customer_id;
			$reg_data['reg_id'] = $gcm_reg_id;
			$reg_data['type'] = $device_type;

			$this->addRegId($reg_data);
		}

		//SEND COD EMAIL TO CUSTOMER
		//$sndCodEmail	= 	$this->customermailer->sendCodOrderMail($order->toArray());
		//$sndCodEmail	= 	$this->findermailer->sendCodOrderMail($order->toArray());

		//SEND COD SMS TO CUSTOMER
		$sndCodSms	= 	$this->customersms->requestCodOrderSms($order->toArray());
		
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
		$postdata		=	Input::json()->all();

		Log::info('Gnerate Tmp Order',$postdata);

		/*$data['service_duration'] = (empty($data['service_duration'])) ? '1 Meal' : $data['service_duration'];*/
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

		if($data['type'] == 'memberships'){
			if( empty($data['service_duration']) ){
				$resp 	= 	array('status' => 404,'message' => "Data Missing - service_duration");
				return Response::json($resp,404);				
			}
		}else{
			$data['service_duration'] = (isset($data['service_duration']) && $data['service_duration'] != "") ? $data['service_duration'] : "";
		}


		//Validation base on order type for sms body and email body  zumbathon','booiaka
		if($data['type'] == 'zumbathon' || $data['type'] == 'booiaka' || $data['type'] == 'fitmaniadealsofday' || $data['type'] == 'fitmaniaservice' || $data['type'] == 'zumbaclub' || $data['type'] == 'kutchi-minithon' || $data['type'] == 'eefashrof' ){
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

		$device_type						= 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
		$gcm_reg_id							= 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

		if($device_type != '' && $gcm_reg_id != ''){

			$reg_data = array();

			$reg_data['customer_id'] = $customer_id;
			$reg_data['reg_id'] = $gcm_reg_id;
			$reg_data['type'] = $device_type;

			$this->addRegId($reg_data);
		}

		if(isset($postdata['preferred_starting_date']) && $postdata['preferred_starting_date']  != '') {

			if(trim(Input::json()->get('preferred_starting_date')) != '-'){
				$date_arr = explode('-', Input::json()->get('preferred_starting_date'));
				$preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
				array_set($data, 'start_date', $preferred_starting_date);
				array_set($data, 'preferred_starting_date', $preferred_starting_date);
			}
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
			$finder_category_id					= 	(isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";
			$finder_slug						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";

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
			array_set($data, 'finder_branch', trim($finder_location));
			array_set($data, 'finder_category_id', $finder_category_id);
			array_set($data, 'finder_slug', $finder_slug);

		}

		array_set($data, 'batch_time', '');

		if(isset($data['batches']) && $data['batches'] != ""){
			if(is_array($data['batches'])){
				$data['batches'] = $data['batches'];
			}else{
				$data['batches'] = json_decode($data['batches'],true);
			}

			foreach ($data['batches'] as $key => $value) {

				if(isset($value['slots'][0]['start_time']) && $value['slots'][0]['start_time'] != ""){
    				$data['batch_time'] = strtoupper($value['slots'][0]['start_time']);
    				break;
    			}
			}
		}

		array_set($data, 'service_name_purchase', $data['service_name']);
		array_set($data, 'service_duration_purchase', $data['service_duration']);

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

		$customer 		= 	Customer::active()->where('email', $data['customer_email'])->first();

		if(!$customer) {
			
			$inserted_id = Customer::max('_id') + 1;
			$customer = new Customer();
			$customer->_id = $inserted_id;
			$customer->name = ucwords($data['customer_name']) ;
			$customer->email = $data['customer_email'];
			$customer->picture = "https://www.gravatar.com/avatar/".md5($data['customer_email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
			$customer->password = md5(time());

			if(isset($data['customer_phone'])  && $data['customer_phone'] != ''){
				$customer->contact_no = $data['customer_phone'];
			}

			/*if(isset($data['customer_address'])){

				if(is_array($data['customer_address']) && !empty($data['customer_address'])){

					$customer->address = implode(",", array_values($data['customer_address']));
					$customer->address_array = $data['customer_address'];

				}elseif(!is_array($data['customer_address']) && $data['customer_address'] != ''){

					$customer->address = $data['customer_address'];
				}

			}*/

			$customer->identity = 'email';
			$customer->account_link = array('email'=>1,'google'=>0,'facebook'=>0,'twitter'=>0);
			$customer->status = "1";
			$customer->ishulluser = 1;
			$customer->save();

			return $inserted_id;

		}else{

			$customerData = [];

			try{

				if(isset($data['customer_phone']) && $data['customer_phone'] != ""){
					$customerData['contact_no'] = trim($data['customer_phone']);
				}

				if(isset($data['otp']) &&  $data['otp'] != ""){
					$customerData['contact_no_verify_status'] = "yes";
				}

				/*if(isset($data['customer_address'])){

					if(is_array($data['customer_address']) && !empty($data['customer_address'])){

						$customerData['address'] = implode(",", array_values($data['customer_address']));
						$customerData['address_array'] = $data['customer_address'];

					}elseif(!is_array($data['customer_address']) && $data['customer_address'] != ''){

						$customerData['address'] = $data['customer_address'];
					}

				}*/

				if(count($customerData) > 0){
					$customer->update($customerData);	
				}
				
			} catch(ValidationException $e){
				
				Log::error($e);

			}

			return $customer->_id;
		}

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

		if($orderData['type'] == 'eefashrof'){
			$salecount 			= 	Order::where('type', 'eefashrof')->where('status', '1')->count();
			$sndsSmsVendor		= 	$this->findersms->buyLandingpagePurchaseEefashrof($orderData,$salecount);
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

		if(isset($orderdata->start_date) && $orderdata->start_date == ""){
			unset($orderdata->start_date);
		}

		if(isset($orderdata->preferred_starting_date) && $orderdata->preferred_starting_date == ""){
			unset($orderdata->preferred_starting_date);
		}

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

	public function addRegId($data){

		$response = add_reg_id($data);

		return Response::json($response,$response['status']);
	}





	public function emailToPersonalTrainers (){

		$match 			=	array(41);
   		// $finders 		=	Finder::whereIn('category_id',$match)->where('status','1')->where('_id',7241)->get()->toArray();
		$finders 		=	Finder::whereIn('category_id',$match)->where('status','1')->get()->toArray();

		// return $finders;

		foreach ($finders as $key => $finder) {
			$finder_id				=	(isset($finder['_id'])) ? $finder['_id'] : [];
			$finder_name			=	(isset($finder['title'])) ? $finder['title'] : "";
			$finder_vcc_email		=	(isset($finder['finder_vcc_email'])) ? $finder['finder_vcc_email'] : "";
			// $finder_vcc_email		=   "sanjay.id7@gmail.com";
			// echo  $finder_id .$finder_name ." - ". $finder_vcc_email. " <br>" ;


			if($finder_name !="" && $finder_vcc_email !=""){
				$queid 	=	"";
				$data  	=	$finder;
				$queid 	=	$this->findermailer->emailToPersonalTrainers($finder_vcc_email, $finder_name, $data);
				echo " <br>". $queid ." - ". $finder_id ." - ".$finder_name ." - ". $finder_vcc_email. " <br>" ;
				echo "==================================================================================================================== <br><br>";
			}

		}
		// return $corders;

		return "email send";


	}

	public function getCategoryImage($category = "no_category"){

		$category_array['gyms'] = array('personal-trainers'=>'http://email.fitternity.com/229/personal.jpg','sport-nutrition-supliment-stores'=>'http://email.fitternity.com/229/nutrition.jpg','yoga'=>'http://email.fitternity.com/229/yoga.jpg');
	    $category_array['zumba'] = array('gyms'=>'http://email.fitternity.com/229/gym.jpg','dance'=>'http://email.fitternity.com/229/dance.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg');
	    $category_array['yoga'] = array('pilates'=>'http://email.fitternity.com/229/pilates.jpg','personal-trainers'=>'http://email.fitternity.com/229/personal.jpg','marathon-training'=>'http://email.fitternity.com/229/marathon.jpg');
	    $category_array['pilates'] = array('yoga'=>'http://email.fitternity.com/229/yoga.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg','marathon-training'=>'http://email.fitternity.com/229/marathon.jpg');
	    $category_array['cross-functional-training'] = array('sport-nutrition-supliment-stores'=>'http://email.fitternity.com/229/nutrition.jpg','personal-trainers'=>'http://email.fitternity.com/229/personal.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg');
	    $category_array['crossfit'] = array('yoga'=>'http://email.fitternity.com/229/yoga.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg','sport-nutrition-supliment-stores'=>'http://email.fitternity.com/229/nutrition.jpg');
	    $category_array['dance'] = array('zumba'=>'http://email.fitternity.com/229/zumba.jpg','mma-and-kick-boxing'=>'http://email.fitternity.com/229/mma&kickboxing.jpg','spinning-and-indoor-cycling'=>'http://email.fitternity.com/229/spinning.jpg');
	    $category_array['mma-and-kick-boxing'] = array('personal-trainers'=>'http://email.fitternity.com/229/personal.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg','cross-functional-training'=>'http://email.fitternity.com/229/cross-functional.jpg');
	    $category_array['spinning-and-indoor-cycling'] = array('gyms'=>'http://email.fitternity.com/229/gym.jpg','dietitians-and-nutritionists'=>'http://email.fitternity.com/229/dietitians.jpg','yoga'=>'http://email.fitternity.com/229/yoga.jpg');
	    $category_array['marathon-training'] = array('dietitians-and-nutritionists'=>'http://email.fitternity.com/229/dietitians.jpg','yoga'=>'http://email.fitternity.com/229/yoga.jpg','cross-functional-training'=>'http://email.fitternity.com/229/cross-functional.jpg');

	    if(array_key_exists($category,$category_array)){
	    	return $category_array[$category];
	    }else{
	    	return array('gyms'=>'http://email.fitternity.com/229/gym.jpg','dance'=>'http://email.fitternity.com/229/dance.jpg','yoga'=>'http://email.fitternity.com/229/yoga.jpg');
	    }	

	}


}
