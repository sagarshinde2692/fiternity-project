<?php
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\FinderSms as FinderSms;
use App\Notification\CustomerNotification as CustomerNotification;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Utilities as Utilities;
use App\Mailers\TrainerMailer as TrainerMailer;
use App\Sms\TrainerSms as TrainerSms;

class CommunicationsController extends \BaseController {

	protected $customermailer;
    protected $customersms;
    protected $findermailer;
    protected $findersms;
    protected $customernotification;
    protected $sidekiq;
    protected $utilities;
	protected $trainermailer;
    protected $trainersms;


    public function __construct(
        CustomerMailer $customermailer,
        CustomerSms $customersms,
        Sidekiq $sidekiq,
        FinderMailer $findermailer,
        FinderSms $findersms,
        Utilities $utilities,
        CustomerNotification $customernotification,
		TrainerMailer $trainermailer,
        TrainerSms $trainersms
    ) {
        parent::__construct();
		$this->customermailer		=	$customermailer;
        $this->customersms 			=	$customersms;
        $this->sidekiq 				= 	$sidekiq;
        $this->findermailer		    =	$findermailer;
        $this->findersms 			=	$findersms;
        $this->utilities 			=	$utilities;
        $this->customernotification     =   $customernotification;
		$this->trainermailer         =   $trainermailer;
        $this->trainersms            =   $trainersms;

    }

	public function sendCommunication($sender_class, $transaction_type, $label, $id, $key){

		try{
			Log::info("inside");
		
			if($transaction_type == 'order'){
				$transaction_data = Order:: where('_id', intval($id))->where("communication_keys.$sender_class-$label", intval($key))->first();
				$dates = array('preferred_starting_date','start_date','start_date_starttime','end_date','preferred_payment_date','success_date','pg_date','preferred_starting_change_date','dietplan_start_date','followup_date', 'order_confirmation_customer','auto_followup_date','requested_preferred_starting_date');


			}else if($transaction_type == 'trial'){
				$transaction_data = Booktrial::where('_id', intval($id))->where("communication_keys.$sender_class-$label", intval($key))->first();
				$dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date','followup_date','auto_followup_date');

			}

			if(!$transaction_data){
				return array('status'=>400, 'message'=>'Transaction not found');
			}

			$data = $transaction_data->toArray();

			$sender_class = strtolower($sender_class);
			Log::info($sender_class);
			Log::info($label);
			
			$response = $this->prepareData($data, $sender_class, $label);
			$response = $this->$sender_class->$label($data, 0);
			
			



			// $communication_keys = $transaction_data->communication_keys;
			// $communication_keys["$sender_class-$label"] = "";

			// $transaction_data->communication_keys = $communication_keys;
			// $transaction_data->update();

			// foreach ($dates as $key => $value){
			// 	if(isset($transaction_data[$value]) && $transaction_data[$value]==''){
			// 		$transaction_data->unset($value);
			// 	}
			// }

			return $response;
			
		}catch(Exceptiion $e){
			Log::info($e);
			return $e;
		}
		
	}

	public function prepareData($data, $sender_class, $label){
		switch($label){
				case "alreadyExtendedOrder":
				$data = array();
				$data['customer_name'] = ucwords($order->customer_name);
				$data['customer_phone'] = $ozonetel_missedcall->customer_number;
				$data['finder_name'] = $order->finder_name;
				$data['google_pin'] = $google_pin;
				break;
				case "orderAfter3Days":
				$order_data['category_array'] = $this->getCategoryImage($category_slug);
				break;
				

		}

		return $this->$sender_class->$label($data, 0);
	}



}
