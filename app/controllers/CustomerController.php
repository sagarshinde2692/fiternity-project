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
	public function getAutoBookTrials($customeremail){

		$selectfields 	=	array('finder', 'finder_id','finder_name','finder_slug','service_name','schedule_date','schedule_slot_start_time','schedule_slot_end_time','code');
		$trials 		=	Booktrial::with(array('finder'=>function($query){$query->select('_id','lon', 'lat', 'contact.address','finder_poc_for_customer_mobile', 'finder_poc_for_customer_name');}))
									  ->where('customer_email', '=', $customeremail)->where('going_status', '=', 1)->orderBy('_id', 'desc')->get($selectfields)->toArray();

		if(count($trials) < 1){
			$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'No trials scheduled yet :)');
			return Response::json($resp);
		}

		$resp 	= 	array('status' => 200,'trials' => $trials,'message' => 'List of scheduled trials');
		return Response::json($resp);
	}

	
	
}
