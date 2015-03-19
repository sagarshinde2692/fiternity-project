<?PHP

/** 
 * ControllerName : CustomerController.
 * Maintains a list of functions used for CustomerController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class CustomerController extends \BaseController {

	public function __construct() {
		parent::__construct();	
	}

    // Listing Schedule Tirals for Customer
	public function getAutoBookTrials($customerid){

		$trials 	=	Booktrial::where('customer_id', '=', $customerid)->where('going_status', '=', 1)->orderBy('_id', 'desc')->get()->toArray();

		if(count($trials) < 1){
			$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
			return Response::json($resp);
		}

		$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'List of scheduled trials');
		return Response::json($resp);
	}

	
	
}
