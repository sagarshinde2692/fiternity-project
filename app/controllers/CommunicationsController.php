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
		
			if($transaction_type == 'order'){

				$transaction_data = Order:: where('_id', intval($id))
											->where("communication_keys.$sender_class-$label", intval($key))
											->first();


			}else if($transaction_type == 'trial'){

				$transaction_data = Booktrial:: where('_id', intval($id))
												->where("communication_keys.$sender_class-$label", intval($key))
												->first();


			}

			if(!$transaction_data){
				return array('status'=>400, 'message'=>'Transaction not found');
			}

			$data = $transaction_data->toArray();
			Log::info("From communicationsController");
			Log::info("$sender_class-$label");
			
			$data = $this->prepareData($data, $label);
			$class = strtolower($sender_class);
			$response = $this->$class->$label($data, 0);

			$communication_keys = $transaction_data->communication_keys;
			$communication_keys["$sender_class-$label"] = "";
			$transaction_data->communication_keys = $communication_keys;
			$transaction_data->update();

			return $response;
			
		}catch(Exceptiion $e){
			Log::info($e);
			return $e;
		}
		
	}

	public function prepareData($data, $label){
		switch($label){
				
				case "alreadyExtendedOrder":
					$data = array();
					$data['customer_name'] = ucwords($order->customer_name);
					$data['customer_phone'] = $ozonetel_missedcall->customer_number;
					$data['finder_name'] = $order->finder_name;
					$data['google_pin'] = $google_pin;
					break;

				
				case "orderAfter3Days":
					$category_slug = "no_category";

					if(isset($data['finder_category_id']) && $data['finder_category_id'] != ""){

						$finder_category_id = $data['finder_category_id'];

						$category = Findercategory::find((int)$finder_category_id);

						if($category){
							$category_slug = $category->slug;
						}
					}

					$data['category_array'] = $this->utilities->getCategoryImage($category_slug);
					break;


				case "bookTrialReminderBefore3Hour":
					$data['poc'] = "vendor";
					$data['poc_no'] = $data['finder_poc_for_customer_no'];
					$hour = (int) date("G");

					if($hour >= 10 && $hour <= 22){
						$data['poc'] = "fitternity";
						$data['poc_no'] = Config::get('app.contact_us_customer_number');
					}
					break;


				case "bookTrialReminderBefore20Min":
					$current_date = date('Y-m-d 00:00:00');
					$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date))));
					$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date." + 1 days"))));
					$batch = 1;
					$booktrialMissedcall  = Booktrial::where('_id','!=',(int) $data['_id'])->where('customer_phone','LIKE','%'.substr($data['customer_phone'], -8).'%')->where('missedcall_batch','exists',true)->where('created_at','>',$from_date)->where('created_at','<',$to_date)->orderBy('_id','desc')->first();
					if(!empty($booktrialMissedcall) && isset($booktrialMissedcall->missedcall_batch) && $booktrialMissedcall->missedcall_batch != ''){
						$batch = $booktrialMissedcall->missedcall_batch + 1;
					}
					$missedcall_no = Ozonetelmissedcallno::where('batch',$batch)->where('type','yes')->where('for','N-3Trial')->first();
					if(empty($missedcall_no)){
						$missedcall_no = Ozonetelmissedcallno::where('batch',1)->where('type','yes')->where('for','N-3Trial')->first();
					}

					$data['yes'] = $missedcall_no->number;
					break;

				case "reminderToConfirmManualTrial":
					$data['id'] = $data['_id'];
					break;

				case "rescheduleTrial":
					$data['customer_profile_url'] = Config::get('app.website')."/profile/".$data['customer_email'];


		}

		return $data;
	}



}
