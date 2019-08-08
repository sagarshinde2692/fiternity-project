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
            if($slug == "the-music-run"){
                $tickets = Ticket::active()->where('event_id',(int)$eventInfo['_id'])->where('start_date', '<=', new DateTime(date('Y-m-d H:i:s', time())))->where('end_date', '>', new DateTime(date('Y-m-d H:i:s', time())))->get();
            }else{
                $tickets = Ticket::active()->where('event_id',(int)$eventInfo['_id'])->get();
            }
                
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

			if(!empty($eventInfo['start_date'])){
				$eventInfo['start_day'] = date('j<\s\u\p>S</\s\u\p> F', strtotime($eventInfo['start_date']));
			}
			
			if(!empty($eventInfo['end_date'])){
				$eventInfo['end_day'] = date('j<\s\u\p>S</\s\u\p> F', strtotime($eventInfo['end_date']));
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
		try {
			$authorization_token = Request::header('Authorization');
			if($authorization_token !== 'FCgvnAPEpncLGTRxgfNE') {
				$responsedata = ['message' => 'Unauthorized access.', 'status' => 401];
				return Response::json($responsedata, 401);
			}
			$orderdata = Order::where('sub_type','music-run')->with(['ticket'=>function($query){$query->select('name', 'price');}])->find(intval($orderid));
            if(isset($orderdata['ticket'])) {
                unset($orderdata['ticket']['_id']);
            } else {
                
                $orderdata['ticket'] = array(
                    'name' =>  $orderdata['ticket_name'],
                    'price' => ($orderdata['amount_finder'] / $orderdata['ticket_quantity']) 
                );
            }
			if(empty($orderdata)){
				$responsedata = ['message' => 'Order does not exists.', 'status' => 400];
				return Response::json($responsedata, 400);
			}
			$first_customer = $orderdata['customer_data'][0];
			$eventdatadetails = array(
				'date_time' => $orderdata['created_at']->toDateTimeString(),
				'timezone' => $orderdata['created_at']->format('T'),
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
		} catch (Exception $e) {
			$message = array(
				'type'    => get_class($e),
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
			);
			$response = array('status'=>400,'reason'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
			Log::info('Event getOrderDetails Error : '.json_encode($response));
			return Response::json(array('status' => 400,'message' => 'Something went wrong.'),400);
		}	
	}

	public function getOrderList($event_slug) {
		try {
			$authorization_token = Request::header('Authorization');
			if($authorization_token !== 'FCgvnAPEpncLGTRxgfNE') {
				$responsedata = ['message' => 'Unauthorized access.', 'status' => 401];
				return Response::json($responsedata, 401);
			}
			if($event_slug != 'music-run') {
				return Response::json(array('status' => 404,'message' => 'Event name is not valid.'),404);
			}
			$orderList = Order::where('sub_type',$event_slug);
			$status = '1';
			if(isset($_GET['status']) && $_GET['status'] != "") {
				if(strtolower($_GET['status']) === 'booked') {
					$status = '1';
				} else if(strtolower($_GET['status']) === 'pending') {
					$status = '0';
				} 
			}
			$orderList->where('status',$status);
			if(isset($_GET['timestamp']) && $_GET['timestamp'] != "") {
				$timestamp = $_GET['timestamp'];
				$from_date = new MongoDate(strtotime(date('Y-m-d H:i:s',$timestamp)));
				$orderList->where('updated_at','>=',$from_date);
			}
			// $orderList = $orderList->get(['order_id','amount','cutomer_name','ticket_quantity','event_address','ticket_name','event_name','event_venue','created_at','updated_at']);
			$orderList = $orderList->get();
			$returnOrder = array();
			foreach($orderList as $key => $orderdata) {
				if(isset($orderdata['customer_data'])) {
					$first_customer = $orderdata['customer_data'][0];
				} 
				$eventdatadetails = array(
					'order_id' => $orderdata['order_id'],
					'created_at' => $orderdata['created_at']->toDateTimeString(),
					'updated_at' => $orderdata['updated_at']->toDateTimeString(),
					'timezone' => $orderdata['created_at']->format('T'),
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
                    // 'ticket_info' => $orderdata['ticket'],
                    'ticket_name' => $orderdata['ticket_name'],
					'event_name' => $orderdata['event_name'],
				);
				array_push($returnOrder, $eventdatadetails);
			}
			return Response::json(array('status' => 200,'orderlist'=>$returnOrder),200);
		} catch (Exception $e) {
			$message = array(
				'type'    => get_class($e),
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
			);
			$response = array('status'=>400,'reason'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
			Log::info('Event getOrderDetails Error : '.json_encode($response));
			return Response::json(array('status' => 400,'message' => 'Something went wrong.'),400);
		}	
	}

	public function inviteForEvent(){
        
        $req = Input::json()->all();
        
        $rules = [
            'order_id' => 'required|integer|numeric',
            'invitees' => 'required|array',
        ];
        $validator = Validator::make($req, $rules);
        if($validator->fails()) {
            $resp = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));
            return  Response::json($resp, 400);
        }
        Log::info('inviteForEvent',$req);
        $inviteesData = [];
        foreach ($req['invitees'] as $value){
            
            $rules = [
                'name' => 'required|string',
                'input' => 'required|string',
            ];
            $messages = [
                'name' => 'invitee name is required',
                'input' => 'invitee email or phone is required'
            ];
            $validator = Validator::make($value, $rules, $messages);
            
            if ($validator->fails()) {
                return Response::json(
                    array(
                        'status' => 400,
                        'message' => $this->errorMessage($validator->errors()
                    )),400
                );
            }
            
            $inviteeData = ['name'=>$value['name']];
            if(filter_var($value['input'], FILTER_VALIDATE_EMAIL) != '') {
                // valid address
                $inviteeData = array_add($inviteeData, 'email', $value['input']);
            }
            else if(filter_var($value['input'], FILTER_VALIDATE_REGEXP, array(
                    "options" => array("regexp"=>"/^[2-9]{1}[0-9]{9}$/")
                )) != ''){
                // valid phone
                $inviteeData = array_add($inviteeData, 'phone', $value['input']);
            }
            array_push($inviteesData, $inviteeData);
        }
        // return $inviteesData;
        foreach ($inviteesData as $value){
            $rules = [
                'name' => 'required|string',
                'email' => 'required_without:phone|email',
                'phone' => 'required_without:email',
            ];
            $messages = [
                'email.required_without' => 'invitee email or phone is required',
                'phone.required_without' => 'invitee email or phone is required'
            ];
            $validator = Validator::make($value, $rules, $messages);
            if ($validator->fails()) {
                return Response::json(
                    array(
                        'status' => 400,
                        'message' => $this->errorMessage($validator->errors()
                        )),400
                );
            }
        }
        // Get Host Data an validate booktrial ID......
        $order = Order::where('_id', $req['order_id'])
            ->get()
            ->first();

        if($order['type'] == 'events'){
            if(isset($order['event_id']) && $order['event_id'] != ''){
                $order['event'] = DbEvent::find(intval($order['event_id']))->toArray();
            }
            if(isset($order['ticket_id']) && $order['ticket_id'] != ''){
                $order['ticket'] = Ticket::find(intval($order['ticket_id']))->toArray();
            }
        }
        if(!$order){
            return Response::json(
                array(
                    'status' => 422,
                    'message' => "Invalid trial id"
                ),422
            );
        }
        
        $order = $order->toArray();
        
        $emails = array_fetch($inviteesData, 'email');
        $phones = array_fetch($inviteesData, 'phone');
        if(array_where($emails, function ($key, $value) use($order)  {
            if($value == $order['customer_email']){
                return true;
            }
        })) {
            return Response::json(
                array(
                    'status' => 422,
                    'message' => 'You cannot invite yourself'
                ),422
            );
        }
        if(array_where($phones, function ($key, $value) use($order)  {
            if($value == $order['customer_phone']){
                return true;
            }
        })) {
            return Response::json(
                array(
                    'status' => 422,
                    'message' => 'You cannot invite yourself'
                ),422
            );
        }
        // Save Invite info..........
        // return $inviteesData; 
        $customersms = new \App\Sms\CustomerSms;
        foreach ($inviteesData as $invitee){
            $order['invitee'] = $invitee;
            
            // isset($templateData['invitee_email']) ? $this->customermailer->inviteEmail($order['type'], $templateData) : null;
            // return $order;
            isset($invitee['phone']) ? $customersms->inviteEvent($order) : null;
        }
        return Response::json(
            array(
                'status' => 200,
                'message' => 'Invitation has been sent successfully'
            ),200
        );
    }
}
