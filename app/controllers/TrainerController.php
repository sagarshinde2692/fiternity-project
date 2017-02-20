<?PHP

/** 
 * ControllerName : TrainerController.
 * Maintains a list of functions used for TrainerController.
 *
 * @author Mahesh jadhav <maheshjadhav@fitternity.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\TrainerMailer as TrainerMailer;
use App\Sms\TrainerSms as TrainerSms;


class TrainerController extends \BaseController {

	protected $customermailer;
    protected $customersms;
	protected $trainermailer;
    protected $trainersms;

	public function __construct(
		CustomerMailer $customermailer,
        CustomerSms $customersms,
		TrainerMailer $trainermailer,
        TrainerSms $trainersms
	) {
		parent::__construct();
        $this->customermailer       =   $customermailer;
        $this->customersms          =   $customersms;
 		$this->trainermailer         =   $trainermailer;
        $this->trainersms            =   $trainersms;
	}

	public function getAvailableSlots(){

		$request = $_REQUEST;

		$rules = array(
            'date'=>'required',
            'customer_id'=>'required',
            'order_id'=>'order_id'
        );

        $validator = Validator::make($request,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }

		$date = $request['date'];
		$customer_id = (int)$request['customer_id'];
		$order_id = (int)$request['order_id'];
		$call_for = "first";

		$weekday =   strtolower(date( "l",strtotime($date)));

		$oldTrainerSlotBooking = TrainerSlotBooking::where('hidden',false)->where('customer_id',$customer_id)->where('order_id',$order_id)->orderBy('_id','desc')->first();

		$schedules_query = Schedule::where('trainer_id','exists',true)->where('day',$weekday);

		if($oldTrainerSlotBooking){

			$schedules_query->where('trainer_id',$oldTrainerSlotBooking->trainer_id);
			$call_for = "followup";

		}

		$schedules = $schedules_query->get();

		$data = [];

		if(!empty($schedules)){

			
			$schedule_data = [];

			foreach ($schedules as $schedule) {

				$slots = [];

				foreach ($schedule->slots as $duration) {

					$slots[] = ['slot' => $duration['duration'],'available' => true];
				}

				$unavailable_slots = 0;
				$total_slots = count($schedule['slots']);

				$trainerSlotBooking = TrainerSlotBooking::where('trainer_id',$schedule->trainer_id)->where('hidden',false)->where('date',$date)->where('day',$weekday)->orderBy('_id','desc')->get();

				if(!empty($trainerSlotBooking)){

					$unavailable_slots = count($trainerSlotBooking);

					foreach ($trainerSlotBooking as $value) {

						foreach ($slots as $slot_key => $slot_value) {

							if($value['slot'] == $slot_value['slot']){
								$slots[$slot_key]['available'] = false;
								break;
							}
						}
					}

				}

				$available_slots = $total_slots - $unavailable_slots;

				$available[] =  $available_slots;

				$data[] = [
					'slots'=>$slots,
					'total_slots'=>$total_slots,
					'available_slots'=>$available_slots,
					'unavailable_slots'=>$unavailable_slots,
				];
			}

		}

		(!empty($data)) ? array_multisort($available, SORT_DESC, $data) : null;

		$response['slots'] = (!empty($data)) ? $data[0]['slots'] : [];
		$response['total_slots'] = (!empty($data)) ? $data[0]['total_slots'] : 0;
		$response['available_slots'] = (!empty($data)) ? $data[0]['available_slots'] : 0;
		$response['unavailable_slots'] = (!empty($data)) ? $data[0]['unavailable_slots'] : 0;
		$response['weekday'] = $weekday;
		$response['date'] = $date;
		$resopnse['order_id'] = $order_id;
		$resopnse['call_for'] = $call_for;	

		return Response::json($response,200);

	}


	public function bookSlot(){

		$data = Input::json()->all();

		$rules = [
			'date' => 'required',
			'slot' => 'required',
			'trainer_id' => 'required',
			'order_id' => 'required'
		];

		$validator = Validator::make($data, $rules);

		if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }

        try {

        	$jwt_token  = Request::header('Authorization');
			$jwt_key = Config::get('app.jwt.key');
			$jwt_alg = Config::get('app.jwt.alg');
			$decoded = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

			$data['customer_email'] = $decoded->customer->email;
			$data['customer_name'] = $decoded->customer->name;
			$data['customer_phone'] = $decoded->customer->contact_no;
			$data['customer_id'] = $decoded->customer->_id;

			
			$data['call_for'] = "first";

			$date = $data['date'] = date('d-m-Y',strtotime($data['date']));
        	$weekday =   strtolower(date( "l",strtotime($date)));
        	$slot = $data['slot'];
        	$slot_explode = explode("-",$data['slot']);
        	$order_id = (int)$data['order_id'];

        	$oldTrainerSlotBooking = TrainerSlotBooking::where('hidden',false)
				->where('order_id',$data['order_id'])
				->first();

			if($oldTrainerSlotBooking){
				$data['call_for'] = "followup";
			}

			$order = Order::find($order_id);

	        if(isset($order->dietplan_end_date) && $order->dietplan_end_date != "" && strtotime($order->dietplan_end_date) < time()){
	        	$data['call_for'] = "review";
	        }
	        
			$trainerSlotBooking = TrainerSlotBooking::where('hidden',false)
				->where('trainer_id',$data['trainer_id'])
				->where('date',$date)
				->where('slot',$slot)
				->where('weekday',$weekday)
				->orderBy('_id','desc')
				->first();

			if($trainerSlotBooking){
				return Response::json(array('status' => 404,'message' => 'Slot is already booked for this Trainer'),404);
			}



			$trainer = Trainer::find($data['trainer_id']);

			$data['trainer_name'] = ucwords($trainer->name);
			$data['trainer_slug'] = $trainer->slug;
			$data['trainer_email'] = $trainer->contact['email'];
			$data['trainer_mobile'] = $trainer->contact['phone']['mobile'];
			$data['trainer_landline'] = $trainer->contact['phone']['landline'];
        	$data['weekday'] = strtolower(date( "l",strtotime($date)));
        	$data['dateunix'] = strtotime($date.$slot_explode[0]);
        	$data['datetime'] = date('Y-m-d H:i:00',$data['dateunix']);
        	$data['hidden'] = false;
        	$data['user_profile_link'] = Config::get('app.website')."/".$data['customer_email']."/profile";
        	$data['trainer_page_link'] = Config::get('app.website')."/".$data['trainer_slug'];
        	$data['healthy_tiffin_link'] = Config::get('app.website')."/mumbai/healthy-tiffins";
        	$data['amount'] = $order->amount;
        	$data['diet_plan_type'] = ucwords($order->service_name);

        	$trainerSlotBooking = new TrainerSlotBooking($data);
	        $trainerSlotBooking->save();

	        if(!isset($order->trainer_id)){
		        $order->trainer_id = (int)$data['trainer_id'];
		        $order->trainer_name = $trainer->name;
				$order->trainer_slug = $trainer->slug;
				$order->trainer_email = $trainer->contact['email'];
				$order->trainer_mobile = $trainer->contact['phone']['mobile'];
				$order->trainer_landline = $trainer->contact['phone']['landline'];
		        $order->update();
		    }

        	$redisid = Queue::connection('redis')->push('TrainerController@sendCommunication', array('trainer_slot_booking_id'=>$trainerSlotBooking->_id),'booktrial');
        	$trainerSlotBooking->update(array('redis_id'=>$redisid));

        	return Response::json(array('status' => 200,'message' => 'Slot Booked Succesfully'),200);


        } catch (Exception $e) {

        	Log::error($e);

        	return Response::json(array('status' => 404,'message' => 'Error! please try after some time'),404);
        	
        }

	}

	public  function sendCommunication($job,$data){

        $job->delete();

        try {

            $trainer_slot_booking_id = (int)$data['trainer_slot_booking_id'];

            $trainerSlotBooking = TrainerSlotBooking::find($trainer_slot_booking_id);

            $delayReminderTimeBefore3Hour = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $trainerSlotBooking->datetime)->subMinutes(60 * 3);

            $trainerSlotBookingArray = $trainerSlotBooking->toArray();


            if($trainerSlotBooking->call_for == "first"){

	            $trainerSlotBooking['sms']['customer']['instantSlotBooking'] = $this->customersms->instantSlotBooking($trainerSlotBookingArray);
	            $trainerSlotBooking['email']['customer']['instantSlotBooking'] = $this->customermailer->instantSlotBooking($trainerSlotBookingArray);

	            $trainerSlotBooking['sms']['trainer']['instantSlotBooking'] = $this->trainersms->instantSlotBooking($trainerSlotBookingArray);
	            $trainerSlotBooking['email']['trainer']['instantSlotBooking'] = $this->trainermailer->instantSlotBooking($trainerSlotBookingArray);
	        }

	        if($trainerSlotBooking->call_for == "review"){

	            $trainerSlotBooking['sms']['customer']['dietPlanAfter15DaysReviewSlotConfirm'] = $this->customersms->dietPlanAfter15DaysReviewSlotConfirm($trainerSlotBookingArray);
	            //$trainerSlotBooking['email']['customer']['dietPlanAfter15DaysReviewSlotConfirm'] = $this->customermailer->dietPlanAfter15DaysReviewSlotConfirm($trainerSlotBookingArray);

	            $trainerSlotBooking['sms']['trainer']['dietPlanAfter15DaysReviewSlotConfirm'] = $this->trainersms->dietPlanAfter15DaysReviewSlotConfirm($trainerSlotBookingArray);
	            //$trainerSlotBooking['email']['trainer']['dietPlanAfter15DaysReviewSlotConfirm'] = $this->trainermailer->dietPlanAfter15DaysReviewSlotConfirm($trainerSlotBookingArray);
	        }

	        if($trainerSlotBooking->call_for == "followup"){

	            $trainerSlotBooking['sms']['customer']['dietPlanAfter15DaysFollowupSlotConfirm'] = $this->customersms->dietPlanAfter15DaysFollowupSlotConfirm($trainerSlotBookingArray);
	            //$trainerSlotBooking['email']['customer']['dietPlanAfter15DaysFollowupSlotConfirm'] = $this->customermailer->dietPlanAfter15DaysFollowupSlotConfirm($trainerSlotBookingArray);

	            $trainerSlotBooking['sms']['trainer']['dietPlanAfter15DaysFollowupSlotConfirm'] = $this->trainersms->dietPlanAfter15DaysFollowupSlotConfirm($trainerSlotBookingArray);
	            //$trainerSlotBooking['email']['trainer']['dietPlanAfter15DaysFollowupSlotConfirm'] = $this->trainermailer->dietPlanAfter15DaysFollowupSlotConfirm($trainerSlotBookingArray);
	        }

            $trainerSlotBooking['sms']['customer']['before3HourSlotBooking'] = $this->customersms->before3HourSlotBooking($trainerSlotBookingArray,$delayReminderTimeBefore3Hour);
            $trainerSlotBooking['sms']['trainer']['before3HourSlotBooking'] = $this->trainersms->before3HourSlotBooking($trainerSlotBookingArray,$delayReminderTimeBefore3Hour);


            if($trainerSlotBooking->call_for == "first"){

            }


            
        } catch (Exception $e) {
            
        }

    }

	
	
}
