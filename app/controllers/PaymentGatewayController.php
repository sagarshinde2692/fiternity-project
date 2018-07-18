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

}
