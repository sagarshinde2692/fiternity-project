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
		$eventInfo = DbEvent::where('slug', $slug)->get();
		$event_id = $eventInfo[0]['_id'];
		$vendor_ids = $eventInfo[0]['vendors'];
		$tickets = Ticket::where('event_id', $event_id)->get();
		$vendors = Finder::whereIn('_id', $vendor_ids)->get();
		$response = array(
			'event_info' => $eventInfo[0],
			'ticket_info' => $tickets,
			'vendor_info' => $vendors
		);
		return $response;
	}
}