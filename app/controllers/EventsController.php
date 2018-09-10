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

			$tickets = Ticket::where('event_id',(int)$eventInfo['_id'])->where('start_date', '<=', new DateTime(date('Y-m-d H:i:s', time())))->where('end_date', '>', new DateTime(date('Y-m-d H:i:s', time())))->get();

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