<?PHP

/**
 * ControllerName : CustomofferordersController.
 * Maintains a list of functions used for CustomofferordersController.
 *
 * @author Renuka Aggarwal <renu17a@gmail.com>
 */


class CustomofferorderController extends \BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function BookingFromCustomOfferOrder($customofferorder_id){

//        $customofferorder_id = (int) $customofferorder_id;
//
//        // Check valid orderID, payment status, booking date validity....
//        $customofferorder = Customofferorder::find($customofferorder_id);
//        if($customofferorder['status'] !== '1'){
//            $resp 	= 	array(
//                "message" => "Already Status Successfull"
//            );
//            return Response::json($resp,422);
//        }

        // if type matches with quantity_type then proceed...else throw error of type is not allowed for order...

        // Generate temp order....

        // pass payload and hit success URL based on type....
    }
}