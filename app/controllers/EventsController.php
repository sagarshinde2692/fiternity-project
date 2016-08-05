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
		$eventInfo = Events::where('slug', $slug)->get();
		$event_id = $eventInfo[0]['_id'];
		$tickets = Tickets::where('event_id', (string) $event_id)->get();
		$response = array(
			'event_info' => $eventInfo[0],
			'ticket_info' => $tickets
		);
		return $response;
	}
}