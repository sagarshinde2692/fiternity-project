<?PHP

/** 
 * ControllerName : CustomerController.
 * Maintains a list of functions used for CustomerController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Services\Ozonetel as Ozonetel;


class OzonetelController extends \BaseController {

	protected $ozonetel;

	public function __construct(Ozonetel $ozonetel) {

		$this->ozonetel	=	$ozonetel;

	}

    // Listing Schedule Tirals for Normal Customer
	public function callVender(){
		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {
		    $this->ozonetel->addPlayText("Please wail while we connecting");
		    $this->ozonetel->addDial("09920864894"); //phone number to dial
		} elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Dial') {
		    if ($_REQUEST['status'] == 'answered') {
		        $this->ozonetel->addPlayText("dialled number is answered");
		    } else {
		        $this->ozonetel->addPlayText("dialled number is not answered");
		    }
		    $this->ozonetel->addHangup();
		} else {
		    $this->ozonetel->addHangup();
		}
		$this->ozonetel->send();
	}
}
