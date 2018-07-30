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

class PaymentGatewayController extends \BaseController {

	public function __construct(

		Mobikwik $mobikwik,
		Paytm $paytm,
		Fitweb $fitweb

	) {

     	parent::__construct();

     	$this->mobikwik = $mobikwik;
     	$this->paytm = $paytm;
     	$this->fitweb = $fitweb;
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

	public function generateOtpPaytm(){

		$data = Input::json()->all();

		$rules = [
			'cell' => 'required'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()){

			$response = [
				'status'=>400,
				'message'=>error_message($validator->errors())
			];

			return Response::json($response);
		}

		$generateOtp = $this->paytm->generateOtp($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($generateOtp['status'] == 200){

			$response = [
				'message'=>$generateOtp['response']['message'],
				'status'=>400
			];

			if($generateOtp['response']['status'] == 'SUCCESS'){

				$response = [
					'message'=>$generateOtp['response']['message'],
					'state'=>$generateOtp['response']['state'],
					'status'=>200
				];
			}

		}

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

			$response = [
				'message'=>$generateToken['response']['ErrorMsg'],
				'status'=>400
			];

			if($generateToken['response']['status'] == 'SUCCESS'){

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

			if($checkBalance['response']['STATUS'] == 'ACTIVE'){

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
            'type'=>$order['type']
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

}
