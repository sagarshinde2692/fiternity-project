<?PHP

/** 
 * ControllerName : PaymentGatewayController.
 * Maintains a list of functions used for PaymentGatewayController.
 *
 * @author Mahesh Jadhv <maheshjadhav@fitternity.com>
 */

use App\Services\Mobikwik as Mobikwik;
use App\Services\Paytm as Paytm;
use App\Services\Fitweb as Fitweb;
use App\Services\Paypal as Paypal;

class PaymentGatewayController extends \BaseController {

	public function __construct(

		Mobikwik $mobikwik,
		Paytm $paytm,
		Fitweb $fitweb,
		Paypal $paypal

	) {

     	parent::__construct();

     	$this->mobikwik = $mobikwik;
     	$this->paytm = $paytm;
     	$this->fitweb = $fitweb;
     	$this->paypal = $paypal;
    }

	
	public function checkExistingUserMobikwik($cell){

		$checkExistingUser = $this->mobikwik->checkExistingUser($cell);

		$response = [
			'user_exists'=>false,
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($checkExistingUser['status'] == 200){

			$response = [
				'user_exists'=>false,
				'status'=>200,
				'message'=>$checkExistingUser['response']['statusdescription'],
				'statuscode'=>$checkExistingUser['response']['statuscode'],
			];

			if($checkExistingUser['response']['status'] == 'SUCCESS' && $checkExistingUser['response']['statuscode'] === '0'){

				$response = [
					'user_exists'=>true,
					'status'=>200,
					'message'=>$checkExistingUser['response']['statusdescription'],
					'statuscode'=>$checkExistingUser['response']['statuscode'],
				];
			}

		}

		return Response::json($response);
	}

	public function generateOtp($type){

		switch ($type) {
			case 'mobikwik': 
				$return = $this->generateOtpMobikwik();
				break;
			case 'paytm': 
				$return = $this->generateOtpPaytm();
				break;
			default:
				$return = ['status'=>401,'message'=>'not found!'];
				break;
		}

		return $return;
	}

	public function generateOtpPaytm($data = false){
		Log::info('----entering generateOtpPaytm----');
		Log::info('data:: ', [$data]);
		$data = $data ? $data : Input::json()->all();  

		$rules = [
			'cell' => 'required'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()){
			Log::info('paytm validation failed');
			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}
		Log::info('paytm generating otp');
		$generateOtp = $this->paytm->generateOtp($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($generateOtp['status'] == 200){

			if(!empty($generateToken['response']['ErrorMsg'])){
				Log::info('paytm otp error');
				$response = [
					'message'=>$generateToken['response']['ErrorMsg'],
					'status'=>400
				];
			}

			if(!empty($generateOtp['response']['status']) && $generateOtp['response']['status'] == 'SUCCESS'){

				$response = [
					'message'=>$generateOtp['response']['message'],
					'state'=>$generateOtp['response']['state'],
					'status'=>200
				];

			}
		}
		Log::info('paytm response:: ', [$response]);
		return Response::json($response);
	}

	public function generateOtpMobikwik(){

		$data = Input::json()->all();

		$rules = [
			'cell' => 'required',
			'amount' => 'required|'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$generateOtp = $this->mobikwik->generateOtp($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($generateOtp['status'] == 200){

			$response = [
				'message'=>$generateOtp['response']['statusdescription'],
				'status'=>400
			];

			if($generateOtp['response']['status'] == 'SUCCESS' && $generateOtp['response']['statuscode'] === '0'){

				$response = [
					'message'=>$generateOtp['response']['statusdescription'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function generateToken($type){

		switch ($type) {
			case 'mobikwik': 
				$return = $this->generateTokenMobikwik();
				break;
			case 'paytm': 
				$return = $this->generateTokenPaytm();
				break;
			default:
				$return = ['status'=>401,'message'=>'not found!'];
				break;
		}

		return $return;
	}

	public function generateTokenPaytm(){

		$data = Input::json()->all();

		$rules = [
			'state' => 'required',
			'otp' => 'required'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		$generateToken = $this->paytm->generateToken($data);

		if($generateToken['status'] == 200){

			if(!empty($generateToken['response']['ErrorMsg'])){

				$response = [
					'message'=>$generateToken['response']['ErrorMsg'],
					'status'=>400
				];
			}

			if(!empty($generateToken['response']['STATUS']) && $generateToken['response']['STATUS'] == 'SUCCESS'){

				$response = [
					'txn_token'=>$generateToken['response']['TOKEN_DETAILS']['TXN_TOKEN'],
					'paytm_token'=>$generateToken['response']['TOKEN_DETAILS']['PAYTM_TOKEN'],
					'message'=>"Token Created",
					'wallet_balance'=>0,
					'status'=>200
				];

				$checkBalanceData = [
					'paytm_token'=>$response['paytm_token']
				];

				$checkBalance = $this->paytm->checkBalance($checkBalanceData);

				if($checkBalance['status'] == 200 && !empty($checkBalance['response']['STATUS']) && $checkBalance['response']['STATUS'] == 'ACTIVE'){

					$response['wallet_balance'] = $checkBalance['response']['WALLETBALANCE'];
				}

			}
		}

		return Response::json($response);
	}

	public function generateTokenMobikwik(){

		$data = Input::json()->all();

		$rules = [
			'cell' => 'required',
			'amount' => 'required',
			'otp' => 'required'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		$checkExistingUser = $this->mobikwik->checkExistingUser($data['cell']);

		if($checkExistingUser['status'] == 200 && !empty($checkExistingUser['response']['statuscode'])){

			if($checkExistingUser['response']['statuscode'] === "159" || $checkExistingUser['response']['statuscode'] === '0'){

				if($checkExistingUser['response']['statuscode'] === "159"){

					$createUser = $this->mobikwik->createUser($data);

					if($createUser['status'] == 200){

						$response = [
							'message'=>$createUser['response']['statusdescription'],
							'status'=>400
						];

						if($createUser['response']['status'] == 'SUCCESS' && $createUser['response']['statuscode'] === '0'){

							$response = [
								'message'=>$createUser['response']['statusdescription'],
								'status'=>200
							];
						}

					}
					
				}

			}else{

				$response = [
					'status'=>400,
					'message'=>$checkExistingUser['response']['statusdescription'],
					'statuscode'=>$checkExistingUser['response']['statuscode'],
				];

				return Response::json($response);
			}

		}

		$generateToken = $this->mobikwik->generateToken($data);

		if($generateToken['status'] == 200){

			$response = [
				'message'=>$generateToken['response']['statusdescription'],
				'status'=>400
			];

			if($generateToken['response']['status'] == 'SUCCESS' && $generateToken['response']['statuscode'] === '0'){

				$response = [
					'token'=>$generateToken['response']['token'],
					'message'=>$generateToken['response']['statusdescription'],
					'wallet_balance'=>0,
					'status'=>200
				];

				$checkBalanceData = [
					'cell'=>$data['cell'],
					'token'=>$generateToken['response']['token']
				];

				$checkBalance = $this->mobikwik->checkBalance($checkBalanceData);

				if($checkBalance['status'] == 200 && $checkBalance['response']['status'] == 'SUCCESS' && $checkBalance['response']['statuscode'] === '0'){

					$response['wallet_balance'] = $checkBalance['response']['balanceamount'];
				}

			}

		}

		return Response::json($response);
	}

	public function regenerateTokenMobikwik(){

		$data = Input::json()->all();

		$rules = [
			'cell' => 'required',
			'token' => 'required'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$regenerateToken = $this->mobikwik->regenerateToken($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($regenerateToken['status'] == 200){

			$response = [
				'message'=>$regenerateToken['response']['statusdescription'],
				'status'=>400
			];

			if($regenerateToken['response']['status'] == 'SUCCESS' && $regenerateToken['response']['statuscode'] === '0'){

				$response = [
					'token'=>$regenerateToken['response']['token'],
					'message'=>$regenerateToken['response']['statusdescription'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function createUserMobikwik(){

		$data = Input::json()->all();

		$rules = [
			'cell' => 'required',
			// 'email' => 'required|email',
			'otp' => 'required'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$createUser = $this->mobikwik->createUser($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($createUser['status'] == 200){

			$response = [
				'message'=>$createUser['response']['statusdescription'],
				'status'=>400
			];

			if($createUser['response']['status'] == 'SUCCESS' && $createUser['response']['statuscode'] === '0'){

				$response = [
					'message'=>$createUser['response']['statusdescription'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function checkBalance($type){
		Log::info('----entering checkBalance----');
		Log::info('data:: ', [$type]);
		switch ($type) {
			case 'mobikwik': 
				$return = $this->checkBalanceMobikwik();
				break;
			case 'paytm': 
				$return = $this->checkBalancePaytm();
				break;
			default:
				$return = ['status'=>401,'message'=>'not found!'];
				break;
		}

		return $return;
	}

	public function checkBalancePaytm(){

		$data = Input::json()->all();

		$rules = [
			'paytm_token' => 'required'
		];

		$validator = Validator::make($data,$rules);
		
		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$checkBalance = $this->paytm->checkBalance($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($checkBalance['status'] == 200){

			$response = [
				'message'=>"User Inactive",
				'status'=>400
			];

			if(!empty($checkBalance['response']['STATUS']) && $checkBalance['response']['STATUS'] == 'ACTIVE'){

				$response = [
					'wallet_balance'=>$checkBalance['response']['WALLETBALANCE'],
					'message'=>"your wallet balance is ".$checkBalance['response']['WALLETBALANCE'],
					'status'=>200
				];
			}
		}

		return Response::json($response);
	}

	public function checkBalanceMobikwik(){

		$data = Input::json()->all();

		$rules = [
			'cell' => 'required',
			'token' => 'required'
		];

		$validator = Validator::make($data,$rules);
		
		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$checkBalance = $this->mobikwik->checkBalance($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($checkBalance['status'] == 200){

			$response = [
				'message'=>$checkBalance['response']['statusdescription'],
				'status'=>400 //199 Either Invalid Token (Expiry or Token mismatch) or Token mismatched due to transaction amount exceeding authorized amount
			];

			if($checkBalance['response']['status'] == 'SUCCESS' && $checkBalance['response']['statuscode'] === '0'){

				$response = [
					'wallet_balance'=>$checkBalance['response']['balanceamount'],
					'message'=>$checkBalance['response']['statusdescription'],
					'status'=>200
				];
			}
		}

		return Response::json($response);
	}

	public function addMoneyMobikwik(){

		$data = Input::json()->all();

		$rules = [
			'cell' => 'required',
			'amount' => 'required',
			'token' => 'required',
			'txnid' => 'required'
		];

		$validator = Validator::make($data,$rules);
		
		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$data['txnid'] = $data['txnid']."-MBKC";

		$response = $this->mobikwik->addMoney($data);

		return Response::json($response);
	}

	public function debitMoneyMobikwik(){

		$data = Input::json()->all();

		Log::info('debitMoneyMobikwik',$data);

		$rules = [
			'cell' => 'required',
			'amount' => 'required',
			'token' => 'required',
			'txnid' => 'required'
		];

		$validator = Validator::make($data,$rules);
		
		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$order = Order::where('txnid',$data['txnid'])->first();

		if(!$order){

			$response = [
				'status'=>400,
				'message'=>'Order not found'
			];

			return Response::json($response);
		}

		if($order['amount'] !== $data['amount']){

			$response = [
				'status'=>400,
				'message'=>'Order amount diff'
			];

			return Response::json($response);
		}

		$success_data = [
        	'txnid'=>$data['txnid'],
            'amount'=>(int)$data["amount"],
            'status' => 'success',
            'type'=>$order['type'],
            'email'=>$order['customer_email']
        ];

        $data['txnid'] = $data['txnid']."-MBKD";

		$debitMoney = $this->mobikwik->debitMoney($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($debitMoney['status'] == 200){

			$response = [
				'message'=>$debitMoney['response']['statusdescription'],
				'status'=>400
			];

			if($debitMoney['response']['status'] == 'SUCCESS' && $debitMoney['response']['statuscode'] === '0'){

				$checkStatusData = [
					'txnid'=> $data['txnid']
				];

				$checkStatus = $this->mobikwik->checkStatus($checkStatusData);

				if($checkStatus['status'] == 200){

					if($checkStatus['response']['statuscode'] !== '0'){

						$response = [
							'message'=>'Check Status Error',
							'status'=>400
						];

						return Response::json($response);
					}

				}else{

					$response = [
						'message'=>'Check Status Error',
						'status'=>400
					];

					return Response::json($response);
				}

				$response = [
					'debit_amount'=>$debitMoney['response']['debitedamount'],
					'balance_amount'=>$debitMoney['response']['balanceamount'],
					'txnid'=>$debitMoney['response']['orderid'],
					'reference_id'=>$debitMoney['response']['refId'],
					'message'=>$debitMoney['response']['statusdescription'],
					'token'=>$data['token'],
					'status'=>200
				];

				$regenerateTokenData = [
					'cell'=> $data['cell'],
					'token'=>$data['token']
				];

				$regenerateToken = $this->mobikwik->regenerateToken($regenerateTokenData);

				if($regenerateToken['status'] == 200){

					if($regenerateToken['response']['status'] == 'SUCCESS' && $regenerateToken['response']['statuscode'] === '0'){

						$response['token'] = $regenerateToken['response']['token'];
					}
				}

				$order->pg_type = "MOBIKWIK";
        		$order->mobikwik_hash = $success_data['hash'] = getpayTMhash($order->toArray())['reverse_hash'];
        		$order->mobikwik_orderid = $response['txnid'];
        		$order->mobikwik_debit_amount = $response['debit_amount'];
        		$order->update();

        		if(stripos($success_data['txnid'],'fit') == 0){

        			$response['success_data'] = $success_data;

		        }else{

		        	$paymentSuccess = $this->fitweb->paymentSuccess($success_data);

	                if($paymentSuccess['status'] !== 200){
	                	
		                $response['status'] = 400;
		                $response['message'] = 'Payment success error';
		            }
		        }

			}

		}

		return Response::json($response);
	}

	public function refundMoneyMobikwik(){

		$data = Input::json()->all();

		$rules = [
			'amount' => 'required',
			'txnid' => 'required'
		];

		$validator = Validator::make($data,$rules);
		
		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$refundMoney = $this->mobikwik->refundMoney($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($refundMoney['status'] == 200){

			$response = [
				'message'=>'amount not refunded',
				'status'=>400
			];

			if($refundMoney['response']['statuscode'] === '0'){

				$response = [
					'txnid'=>$debitMoney['response']['txid'],
					'reference_id'=>$debitMoney['response']['refId'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function verifyPayment($status = 'success'){
		Log::info('----entering verifyPayment----');
		$response = [
			'status'=>200,
			'message'=>'200 Added to wallet',
		];

		if($status == "failure"){

			$response = [
				'status'=>400,
				'message'=>'failure status'
			];
		}

		$response = htmlentities(json_encode($response));

		return View::make('paymentgateway.mobikwik', compact('response'));
	}

	public function verifyAddMoneyMobikwik(){

		$data = $_REQUEST;

		Log::info('verifyAddMoneyMobikwik',$data);

		$rules = [
			'statusmessage' => 'required',
			'statuscode' => 'required',
			'orderid' => 'required',
			'amount' => 'required',
			'mid' => 'required',
			'checksum' => 'required'
		];

		$validator = Validator::make($data,$rules);
		
		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($data['statuscode'] === '0'){

			$response = [
				'status'=>200,
				'message'=>$data['amount'].' Added to wallet',
				'orderid'=>$data['orderid']
			];

		}

		$response = htmlentities(json_encode($response));

		return View::make('paymentgateway.mobikwik', compact('response'));
    }

    public function checkStatusMobikwik(){

		$data = Input::json()->all();

		$rules = [
			'txnid' => 'required'
		];

		$validator = Validator::make($data,$rules);
		
		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$checkStatus = $this->mobikwik->checkStatus($data);

		$response = [
			'status'=>400,
			'message'=>'Status not Success'
		];

		if($checkStatus['status'] == 200){

			$response = [
				'message'=>'Status not Success',
				'status'=>400
			];

			if($checkStatus['response']['statuscode'] === '0'){

				$response = [
					'message'=>'Success',
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function firstCallPaypal(){
		$access_token = $this->paypal->getAccessToken();
		return Response::json($access_token);
	}

	public function createPaymentPaypal(){
		
		$postData = Input::all();
		
		if(empty($postData)){
			$postData = Input::json()->all();
		}
        
		Log::info($postData);
		
		$firstname = (empty($postData['firstname'])) ? (empty($postData['customer_name'])) ? "" : $postData['customer_name'] : $postData['firstname'];
		$email = (empty($postData['email'])) ? (empty($postData['customer_email'])) ? "" : $postData['customer_email'] : $postData['email'];
		$phone = (empty($postData['phone'])) ? (empty($postData['customer_phone'])) ? "" : $postData['customer_phone'] : $postData['phone'];

		$header = $this->getHeaderInfo();
		$customer_id = "";
		if(!empty($header)){
			$customer_id = $header['customer_id'];
		}
		// print_r($header);
		// exit();

		$paypal_sandbox = \Config::get('app.paypal_sandbox');
		if(!$paypal_sandbox){
			$stcData = array(
				"tracking_id" => $postData['txnid'],
				"additional_data" => array(
					array(
						"key" => "sender_account_id",
						"value" => $customer_id
					),
					array(
						"key" => "sender_first_name", 
						"value" => $firstname
					),
					array(
						"key" => "sender_email", 
						"value" => $email
					),
					array(
						"key" => "sender_phone", 
						"value" => $phone
					),
					array(
						"key" => "loyalty_flag_exists", 
						"value" => 0
					)
				)
			);
			
			$jsonStcData = json_encode($stcData);
			// print_r( $jsonStcData);
			// exit();

			$res = $this->paypal->setTransactionContext($jsonStcData);
			Log::info("STC CALL result ::: ",[$res]);
		}
		
		$data = array("intent" => "sale",
					"payer" => array(
						"payment_method" => "paypal",
						"payer_info" => array(
							"email" => $email,
						   	"first_name" => $firstname
						)
					),
					"application_context" => array(
						"shipping_preference" => "NO_SHIPPING",
						"locale" => "en_IN",
						"user_action" => "commit"
					),
					"transactions" => array(array(
						"amount" => array(
							"total" => $postData['amount'], 
							"currency" => "INR" 
						),
						"invoice_number" => $postData['txnid'],
						"item_list" => array(
							"items" => array(array(
								"name" => ucwords($postData['productinfo']),
								"description" => ucwords($postData['service_name']),
					  			"quantity" => "1",
					  			"price" => $postData['amount'],
					  			"sku" => ucwords($postData['type']),
					  			"currency" => "INR"
							)),
							"shipping_phone_number" => "+91".$phone,
						)
					)),
					"note_to_payer" => "Contact us for any questions on your order.",
					"redirect_urls" => array(
			  			"return_url" => Config::get('app.url')."/successRoutePaypal?txnid=".$postData['txnid'],
			  			"cancel_url" => Config::get('app.url')."/cancleRoutePaypal"
					)
				);
	
		$jsonData = json_encode($data);
		//echo $jsonData;
		//exit();
		
		$response = $this->paypal->createPayment($jsonData, $postData['txnid']);
		Log::info("create payment res ::: ", [$response]);
		$value = array("rel" => "approval_url");
		if($response['status'] == 200){
			$link = array_where($response['message']['links'], function($key, $val) use ($value){
				if($val['rel'] == $value['rel'])
					{
					 return true; 
					}
			});
			
			if(!empty($link)){
				foreach($link as $k => $v){
					$l = $v['href'];
				}
			}

			$returnResponse = [
				'status' => '200',
				'url' => $l.'&locale.x=en_IN&country.x=IN'
			];
			
		}else{
			$returnResponse = $response;
		}
		
		return Response::json($returnResponse);
	}

	public function successExecutePaymentPaypal(){
		Log::info("successExecutePaymentPaypal");
		$header = $this->getHeaderInfo();
		$app_device = strtolower($header['app_device']);
		$PayerID = Input::get('PayerID');
		$token = Input::get('token');
		$paymentId = Input::get('paymentId');
		$txnid = Input::get('txnid');
		Log::info("txnid", [$txnid]);
		$payer_id = json_encode(array("payer_id" => $PayerID));
		$response = $this->paypal->executePayment($paymentId, $payer_id, $txnid);
		// return Response::json($response);
		// exit();
		Log::info("execute paymet res :::   ", [Response::json($response)]);
		
		if(!empty($txnid)) {
			$order = Order::where('txnid', $txnid)->first(['_id','customer_name','customer_email','customer_phone','finder_id','service_name','amount_customer','schedule_date','type', 'device_type'])->toArray();
		}

		if($response['status'] == 200){
			Log::info("200");

			if(!empty($response['message']['transactions'][0]['related_resources'])){
				Log::info("not empty transaction");
				$state = $response['message']['transactions'][0]['related_resources'][0]['sale']['state'];
				if($state == 'completed'){

					Log::info("completed");
					$parent_payment_id = $response['message']['transactions'][0]['related_resources'][0]['sale']['parent_payment'];
					$payment_id = $response['message']['transactions'][0]['related_resources'][0]['sale']['id'];
					$txnid = $response['message']['transactions'][0]['invoice_number'];
					Log::info("parent payment id :: ",[$parent_payment_id]);
					Log::info("payment id :: ",[$payment_id]);
					
					// Order::where('txnid', $txnid)->update(['parent_payment_id_paypal' => $parent_payment_id, "payment_id_paypal" => $payment_id]);
					if(empty($order)) {
						$order = Order::where('txnid', $txnid)->first(['_id','customer_name','customer_email','customer_phone','finder_id','service_name', 'amount', 'amount_customer','schedule_date','type', 'device_type'])->toArray();
					}
					$fin_arr = array(
						"order_id" => $order['_id'],
						"status" => "success",
						"customer_name" => $order['customer_name'],
						"customer_email" => $order['customer_email'],
						"customer_phone" => $order['customer_phone'],
						"error_Message" => "",
						"service_name" => $order['service_name'],
						"amount" => $order['amount'],
						"finder_id" => (!empty($order['finder_id']))?$order['finder_id']:0,
						"schedule_date" => (empty($order['schedule_date']))? "" : $order['schedule_date'],
						"type" => $order['type'],
						"parent_payment_id_paypal" => $parent_payment_id,
						"payment_id_paypal" => $payment_id
					);
					
					
					if($order['type'] == "booktrials" || $order['type'] == "workout-session"){
						$res_obj = app(SchedulebooktrialsController::class)->bookTrialPaid($fin_arr);
					}else{
						$res_obj = app(TransactionController::class)->success($fin_arr);
					}
					// print_r($res_obj->getData());
					// echo "<hr>";
					$res = json_decode(json_encode($res_obj->getData()),true);
					if($res['status'] == 200){
						Log::info("db updated");
						if(!empty($order['device_type']) && ($order['device_type'] == 'android' || $order['device_type'] == 'ios')){
							return Redirect::to('ftrnty://ftrnty.com/paypalresponse?status=200');
						}

						if($order['type'] == "booktrials" || $order['type'] == "workout-session"){
							return Redirect::to(Config::get('app.website')."/paymentsuccesstrial?orderId=".$order['_id']."&type=paypal");
						}

						return Redirect::to(Config::get('app.website')."/paymentsuccess?orderId=".$order['_id']."&type=paypal");
					}
					// else{
					// 	Log::info("db update fail");
					// 	if($app_device == 'android' || $app_device == 'ios'){
					// 		return Redirect::to('ftrnty://ftrnty.com/paypalresponse?status=400&message=fail');
					// 	}
					// 	return Redirect::to(Config::get('app.website')."/paymentfailure");	
					// }
				}
				// else{
				// 	Log::info("nnot completed");
				// 	if($app_device == 'android' || $app_device == 'ios'){
				// 		return Redirect::to('ftrnty://ftrnty.com/paypalresponse?status=400&message=fail');
				// 	}
				// 	return Redirect::to(Config::get('app.website')."/paymentfailure");	
				// }
			}
			// else{
			// 	Log::info("empty tran");
			// 	if($app_device == 'android' || $app_device == 'ios'){
			// 		return Redirect::to('ftrnty://ftrnty.com/paypalresponse?status=400&message=fail');
			// 	}
			// 	return Redirect::to(Config::get('app.website')."/paymentfailure");
			// }
		}
		// else{
		// 	Log::info("execute fail");
		// 	// return header('Location: ftrnty://ftrnty.com/paypalresponse?status=400&message=fail');
		// 	if($app_device == 'android' || $app_device == 'ios'){
		// 		return Redirect::to('ftrnty://ftrnty.com/paypalresponse?status=400&message=fail');
		// 	}
		// 	return Redirect::to(Config::get('app.website')."/paymentfailure");
		// }

		if(!empty($order['device_type']) && ($order['device_type'] == 'android' || $order['device_type'] == 'ios')){
			return Redirect::to('ftrnty://ftrnty.com/paypalresponse?status=400&message=fail');
		}
		return Redirect::to(Config::get('app.website')."/paymentfailure");
	}

	public function canclePaymentPaypal(){
		$header = $this->getHeaderInfo();

		$app_device = strtolower($header['app_device']);

		if($app_device == 'android' || $app_device == 'ios'){
			return Redirect::to('ftrnty://ftrnty.com/paypalresponse?status=400&message=fail');
		}
		return Redirect::to(Config::get('app.website')."/paymentfailure");
	}

	public function getHeaderInfo(){

		Log::info("header");

		$customer_id = "";
		$app_device = "";
		$app_version = "";

		$jwt_token = Request::header('Authorization');
		if($jwt_token){
			$decoded = customerTokenDecode($jwt_token);

			$customer_id = (int)$decoded->customer->_id;
		}
	
		$app_device = Request::header('Device-Type');
   		$app_version = Request::header('App-Version');
		
		Log::info("app device paypal ::: ", [$app_device]);
		Log::info("app version paypal ::: ", [$app_version]);
		Log::info("customer paypal ::: ", [$customer_id]);

		$data = array("customer_id" => $customer_id,
				"app_version" => $app_version,
				"app_device" => $app_device
		);

		return $data;
	}
}
