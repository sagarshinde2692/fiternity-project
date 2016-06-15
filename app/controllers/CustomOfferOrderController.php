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

    public function tmpOrder(){

    	$data = Input::json()->all();

    	$rules = [
			'customer_name' => 'required|max:255',
			'customer_email' => 'required|email|max:255',
			'customer_phone' => 'required|max:15',
			'customoffer_id' => 'required',
		];

		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' => error_message($validator->errors())),400);
		}else{

			$offer = Customoffer::find((int)$data['customoffer_id']);

			if($offer){

				$data['customer_id'] = autoRegisterCustomer($data);

				$data['quantity_type'] = $offer->quantity_type;
				$data['allowed_qty'] = $offer->quantity;
				$data['validity'] = $offer->validity;
				$data['price_of_one'] = $offer->price/$offer->quantity;
				$data['used_qty'] = 0;
				$data['status'] = "0";
				$data['expiry_date'] = Carbon::createFromFormat('Y-m-d', date("Y-m-d"))->addDays((int) $data['validity']);

				$order = new Customofferorder($data);
				$order->_id = Customofferorder::max('_id') + 1;
				$order->save();

				return Response::json(array('status' => 200,'message' => 'Tmp order generated sucessfull','order_id'=>$order->_id),200);

			}else{

				return Response::json(array('status' => 400,'message' => 'No offer found'),400);
			}
    	}
	}


	public function captureOrder($order_id){

		$order = Customofferorder::find($order_id);

		if($order){

			$order->status = "1";
			$order->update();

			return Response::json(array('status' => 200,'message' => 'successfull created order'),200);

		}else{

			return Response::json(array('status' => 400,'message' => 'No offer found'),400);
		}
		
	}

	public function getOrders(){

		$jwt_token = Request::header('Authorization');
		$decoded = $this->customerTokenDecode($jwt_token);
		$customer_id = (int)$decoded->customer->_id;
		$order array();

		$customoffer_id = array();

		$order = Customofferorder::where('customer_id',$customer_id)->whereIn('customoffer_id',$customoffer_id)->orderBy('_id', 'desc')->get();

		return Response::json(array('status' => 200,'order'=>$order),200);

	}






}