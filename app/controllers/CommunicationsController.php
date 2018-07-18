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

			$label_array = ["sendPaymentLinkAfter3Days","sendPaymentLinkAfter7Days","sendPaymentLinkAfter45Days","purchaseAfter10Days","purchaseAfter30Days"];

			if(in_array($label,$label_array)){
				return array('status'=>400, 'message'=>'Communication not sent');
			}
		
			if($transaction_type == 'order'){

				$transaction_data = Order:: where('_id', intval($id))
											->where("communication_keys.$sender_class-$label", intval($key))
											->first();


			}else if($transaction_type == 'trial'){

				$transaction_data = Booktrial:: where('_id', intval($id))
												->where("communication_keys.$sender_class-$label", intval($key))
												->first();

				if(empty($transaction_data['surprise_fit_cash'])){
					
					$transaction_data->surprise_fit_cash = $this->utilities->getFitcash($transaction_data->toArray());
				}

			}

			if(!$transaction_data){
				return array('status'=>400, 'message'=>'Transaction not found');
			}

			$dates = array('followup_date','last_called_date','preferred_starting_date', 'called_at','subscription_start','start_date','start_date_starttime','end_date', 'order_confirmation_customer', 'start_date', 'start_date_starttime', 'schedule_date', 'schedule_date_time', 'followup_date', 'followup_date_time','missedcall_date','customofferorder_expiry_date','auto_followup_date');
			$unset_keys = [];
	

			foreach ($dates as $key => $value){
				if(isset($transaction_data[$value]) && $transaction_data[$value]==''){
					// $transaction_data->unset($value);
					array_push($unset_keys, $value);
			
				}
			}

			if(count($unset_keys)>0){
				$transaction_data->unset($unset_keys);
			}

			$data = $transaction_data->toArray();
			Log::info("From communicationsController");
			Log::info("$sender_class-$label");
			
			$data = $this->prepareData($data, $label);
			$class = strtolower($sender_class);

			$communication_keys = $transaction_data->communication_keys;
			$communication_keys["$sender_class-$label"] = "";
			$transaction_data->communication_keys = $communication_keys;
			$transaction_data->update();

			/*if(isset($transaction_data['customer_id'])){

				$getWalletBalance = $this->utilities->getWalletBalance($transaction_data['customer_id']);

				if($getWalletBalance < 200 && $class == "customersms" && $label == "bookTrialReminderAfter2Hour" && $transaction_type == 'trial' && !isset($transaction_data['order_id'])){

					$url = Config::get('app.url')."/addwallet?customer_id=".$transaction_data["customer_id"]."&booktrial_id=".$transaction_data['_id'];

					$this->utilities->hitUrlAfterDelay($url."&time=Nplus2");

					return "no sms sent";
				}
			}*/

			$response = $this->$class->$label($data, 0);

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
					if(isSet($missedcall_no->number)&&$missedcall_no->number!="")
						$data['yes'] = $missedcall_no->number;
						else $data['yes'] = "";
						
						$data['pps_cashback'] =$this->utilities->getWorkoutSessionLevel($data['customer_id'])['current_level']['cashback'];
						$booktrial = Booktrial::find($data['_id']);
						if(iseSet($booktrial&&$booktrial!="")&&isset($data['pps_cashback'])&&$data['pps_cashback']!="")
						{
							$booktrial->pps_cashback=$data['pps_cashback'];
							$booktrial->update();
						}
						
						break;

				case "reminderToConfirmManualTrial":
					$data['id'] = $data['_id'];
					break;

				case "rescheduleTrial":
					$data['customer_profile_url'] = Config::get('app.website')."/profile/".$data['customer_email'];
					break;
					
				case "bookTrialReminderBefore6Hour":
					if(!isset($data['vendor_code'])){
						$booktrial = Booktrial::find($data['_id']);
						$booktrial->vendor_code = random_numbers(5);
						$booktrial->update();
						$data['vendor_code'] = $booktrial->vendor_code;
					}
					
				case "bookTrialReminderBefore10Min":
					{
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
						if(isSet($missedcall_no->number)&&$missedcall_no->number!="")
							$data['yes'] = $missedcall_no->number;
							else $data['yes'] = "";
						
						$data['pps_cashback'] =$this->utilities->getWorkoutSessionLevel($data['customer_id'])['current_level']['cashback'];
						$booktrial = Booktrial::find($data['_id']);
						if(isset($booktrial)&&$booktrial!=""&&isset($data['pps_cashback'])&&$data['pps_cashback']!="")
						{
							$booktrial->pps_cashback=$data['pps_cashback'];
							$booktrial->update();
						}
						break;
					}
				case "bookTrialReminderAfter2Hour":
					{ 
						$data['pps_cashback'] =$this->utilities->getWorkoutSessionLevel($data['customer_id'])['current_level']['cashback'];
						$booktrial = Booktrial::find($data['_id']);
						if(isset($booktrial)&&$booktrial!=""&&isset($data['pps_cashback'])&&$data['pps_cashback']!="")
						{
							$booktrial->pps_cashback=$data['pps_cashback'];
							$booktrial->update();
						}
						break;
					}
		}

		if(isset($data['customer_id']) && $data['customer_id'] != ""){

			$data['wallet_balance'] = $this->utilities->getWalletBalance((int)$data['customer_id']);
		}

		$data['booktrial_link'] = "";
		
		if(isset($data['finder_slug']) && $data['service_id']){
			$data['booktrial_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']);
		}

        $data['workout_article_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/article/complete-guide-to-help-you-prepare-for-the-first-week-of-your-workout");
        $data['download_app_link'] = Config::get('app.download_app_link');
        $data['diet_plan_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/diet-plan");

		return $data;
	}



}
