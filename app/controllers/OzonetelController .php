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
	public function callVendor(){

		Log::info('ozone',$_REQUEST);
		
		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {
		    $this->ozonetel->addPlayText("Please wail while we connecting");
		    $this->ozonetel->addDial("09920864894"); //phone number to dial
		} elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Dial') {
		    if ($_REQUEST['status'] == 'answered') {
		    	$this->ozonetel->addRecord("recordFileName");
		        $this->ozonetel->addPlayText("dialled number is answered");
		    } else {
		        $this->ozonetel->addPlayText("dialled number is not answered");
		    }
		    $this->ozonetel->addHangup();
		} elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Record') {
        	$this->ozonetel->addPlayText("your recorded message is ");
        	$this->ozonetel->addPlayAudio($_REQUEST['data']);
		}else {
		    $this->ozonetel->addHangup();
		}
		$this->ozonetel->send();

		

		/*if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {
    $this->ozonetel->addPlayText("Please record your message");
    $this->ozonetel->addDial("09920864894"); //phone number to dial
    $this->ozonetel->addRecord("recordFileName");
} elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Record') {
        $this->ozonetel->addPlayText("your recorded message is ");
        $this->ozonetel->addPlayAudio($_REQUEST['data']);
}else{
$this->ozonetel->addHangup();
}
$this->ozonetel->send();
	}*/
}
