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
}