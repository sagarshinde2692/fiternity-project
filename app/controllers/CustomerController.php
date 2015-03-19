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

		$selectfields 	=	array('finder_id','finder_name','finder_slug','service_name','schedule_date','schedule_slot_start_time','schedule_slot_end_time','code');
		$trials 		=	Booktrial::where('customer_id', '=', $customerid)->where('going_status', '=', 1)->orderBy('_id', 'desc')->get($selectfields)->toArray();

		if(count($trials) < 1){
			$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
			return Response::json($resp);
		}

		$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'List of scheduled trials');
		return Response::json($resp);
	}

	
	
}
