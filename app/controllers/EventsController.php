<?PHP

/**
 * ControllerName : EventsController.
 * Maintains a list of functions used for EventsController.
 *
 * @author Nishank Jain <nishankjain@fitternity.com>
 */

class EventsController extends \BaseController {
	public function __construct() {
		parent::__construct();
	}

	public function getEventInfo($slug) {

		$eventInfo = DbEvent::where('slug', $slug)->first();

		if($eventInfo){

			$eventInfo = $eventInfo->toArray();

			$tickets = Ticket::where('event_id',(int)$eventInfo['_id'])->get();

			if(!empty($tickets)){

				$tickets = $tickets->toArray();

				foreach ($tickets as $key => &$value) {

					$value['sold_out'] = false;

					if($value['sold'] >= $value['quantity']){
						$value['sold_out'] = true;
					}
				}

			}else{

				$tickets = [];
			}

			Finder::$withoutAppends = true;

			$vendors = Finder::whereIn('_id',$eventInfo['vendors'])->get();

			if(!empty($vendors)){

				$vendors = $vendors->toArray();

			}else{

				$vendors = [];
			}

			$response = array(
				'event_info' => $eventInfo,
				'ticket_info' => $tickets,
				'vendor_info' => $vendors
			);

			return Response::json($response,200);

		}

		return Response::json(["message"=>"Data Not Found"],404);
	}

	public function getOrderDetails($orderid) {
		$orderdata = Order::where('sub_type','music-run')->with(['ticket'=>function($query){$query->select('name', 'price');}])->find(intval($orderid));
       	unset($orderdata['ticket']['_id']);
        if(empty($orderdata)){
			$responsedata = ['message' => 'Order does not exists.', 'status' => 400];
			return Response::json($responsedata, 400);
		}
		$first_customer = $orderdata['customer_data'][0];
		$eventdatadetails = array(
			'date_time' => $orderdata['success_date']->toDateTimeString(),
			'timezone' => $orderdata['success_date']->format('T'),
			'event_id' => $orderdata['event_id'],
			'customer_id'=> $orderdata['customer_id'],
			'customer_email'=> $orderdata['customer_email'],
			'customer_phone1' => $orderdata['customer_phone'],
			'customer_first_name' => $first_customer['firstname'],
			'customer_last_name' => $first_customer['lastname'],
			'customer_dob'=> $first_customer['dob'],
			'customer_gender'=> $first_customer['gender'],
			'billing_address'=> $first_customer['address'],
			'billing_city'=> $first_customer['city'],
			'billing_state'=> $first_customer['state'],
			'billing_postal_code' => $first_customer['postalcode'],
			'billing_country' => $first_customer['country'],
			'shipping_address'=> $first_customer['address'],
			'shipping_city' => $first_customer['city'],
			'shipping_state'=> $first_customer['state'],
			'shipping_postal_code' => $first_customer['postalcode'],
			'shipping_country' => $first_customer['country'],
			'language' => 'English',
			'currency' => 'INR.',
			'order_total' => $orderdata['amount'],
			'discount' => $orderdata['combo_discount'],
			'discount_description' => $orderdata['combo_discount_remark'],
			'date_updated' => $orderdata['updated_at']->toDateTimeString(),
			'customer_booking_data' => $orderdata['customer_data'],
			'ticket_quantity' => $orderdata['ticket_quantity'],
			'ticket_info' => $orderdata['ticket'],
			'event_name' => $orderdata['event_name']
		);
        return Response::json($eventdatadetails, 200);
	}
}


