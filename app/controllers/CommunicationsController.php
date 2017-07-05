<?php
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\FinderSms as FinderSms;
use App\Notification\CustomerNotification as CustomerNotification;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Utilities as Utilities;

class CommunicationsController extends \BaseController {

	protected $customermailer;
    protected $customersms;
    protected $findermailer;
    protected $findersms;
    protected $customernotification;
    protected $sidekiq;
    protected $utilities;

    public function __construct(
        CustomerMailer $customermailer,
        CustomerSms $customersms,
        Sidekiq $sidekiq,
        FinderMailer $findermailer,
        FinderSms $findersms,
        Utilities $utilities,
        CustomerNotification $customernotification
    ) {
        parent::__construct();
        $this->customermailer		=	$customermailer;
        $this->customersms 			=	$customersms;
        $this->sidekiq 				= 	$sidekiq;
        $this->findermailer		    =	$findermailer;
        $this->findersms 			=	$findersms;
        $this->utilities 			=	$utilities;
        $this->customernotification     =   $customernotification;

    }

	public function sendCommunication($type, $to, $transaction_type, $id, $label){
		
		if($transaction_type == 'order'){
			$transaction_data = Order:: find(intval($id));
		}else if($transaction_type == 'trial'){
			$transaction_data = Booktrial:: find(intval($id));
		}

		if(!$transaction_data){
			return array('status'=>400, 'message'=>'Transaction not found');
		}

		$transaction_data = $transaction_data->toArray();
		
		switch($type){
			case 'sms':
				case 'customer':
					$response = $this->customersms->$label($transaction_data);
					break;
				case 'finder':
					$response = $this->findersms->$label($transaction_data);
					break;
			break;					
			case 'mail':
				case 'customer':
					$response = $this->customermailer->$label($transaction_data);
					break;
				case 'finder':
					$response = $this->findermailer->$label($transaction_data);
					break;
			break;
			case 'notification':
				case 'customer':
					$response = $this->customernotification->$label($transaction_data);
					break;
		}
		return $response;
	}
}
