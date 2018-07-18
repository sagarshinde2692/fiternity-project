<?PHP

/** 
 * ControllerName : PaymentGatewayController.
 * Maintains a list of functions used for PaymentGatewayController.
 *
 * @author Mahesh Jadhv <maheshjadhav@fitternity.com>
 */

use App\Services\Mobikwik as Mobikwik;


class PaymentGatewayController extends \BaseController {

	protected $mobikwik;

	public function __construct(

		Mobikwik $mobikwik

	) {

     	parent::__construct();

     	$this->mobikwik = $mobikwik;
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
				'status'=>400,
				'message'=>$generateOtp['response']['statusdescription'],
			];

			if($checkExistingUser['response']['status'] == 'SUCCESS' && $checkExistingUser['response']['statuscode'] === '0'){

				$response = [
					'user_exists'=>true,
					'status'=>200,
					'message'=>$generateOtp['response']['statusdescription']
				];
			}

		}

		return Response::json($response);
	}

	public function generateOtpMobikwik(){

		$data = Input::json()->all();

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

	public function generateTokenMobikwik(){

		$data = Input::json()->all();

		$generateToken = $this->mobikwik->generateToken($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($generateToken['status'] == 200){

			$response = [
				'message'=>$generateToken['response']['statusdescription'],
				'status'=>400
			];

			if($generateToken['response']['status'] == 'SUCCESS' && $generateToken['response']['statuscode'] === '0'){

				$response = [
					'token'=>$generateToken['response']['token'],
					'message'=>$generateToken['response']['statusdescription'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function regenerateTokenMobikwik(){

		$data = Input::json()->all();

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
					'token'=>$createUser['response']['token'],
					'message'=>$createUser['response']['statusdescription'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function checkBalanceMobikwik(){

		$data = Input::json()->all();

		$checkBalance = $this->mobikwik->checkBalance($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($checkBalance['status'] == 200){

			$response = [
				'message'=>$checkBalance['response']['statusdescription'],
				'status'=>400
			];

			if($checkBalance['response']['status'] == 'SUCCESS' && $checkBalance['response']['statuscode'] === '0'){

				$response = [
					'amount'=>$checkBalance['response']['balanceamount'],
					'message'=>$checkBalance['response']['statusdescription'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function addMoneyMobikwik(){

		$data = Input::json()->all();

		$addMoney = $this->mobikwik->addMoney($data);

		$response = [
			'status'=>400,
			'message'=>'something went wrong'
		];

		if($addMoney['status'] == 200){

			$response = [
				'message'=>$addMoney['response']['statusdescription'],
				'status'=>400
			];

			if($addMoney['response']['status'] == 'SUCCESS' && $addMoney['response']['statuscode'] === '0'){

				$response = [
					'amount'=>$addMoney['response']['balanceamount'],
					'message'=>$addMoney['response']['statusdescription'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

	public function debitMoneyMobikwik(){

		$data = Input::json()->all();

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

				$response = [
					'debit_amount'=>$debitMoney['response']['debitedamount'],
					'balance_amount'=>$debitMoney['response']['balanceamount'],
					'order_id'=>$debitMoney['response']['orderid'],
					'reference_id'=>$debitMoney['response']['refId'],
					'message'=>$debitMoney['response']['statusdescription'],
					'status'=>200
				];
			}

		}

		return Response::json($response);
	}

}
