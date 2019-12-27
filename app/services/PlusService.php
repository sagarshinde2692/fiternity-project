<?PHP namespace App\Services;

use Log;
use Pass;
use App\Services\RazorpayService as RazorpayService;
use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Notification\CustomerNotification as CustomerNotification;
use Validator;
use Config;
use Request;
use stdClass;
use Input;
use Customer;

class PlusService {
    protected $utilities;

    public function __construct(Utilities $utilities) {
        $this->utilities	=	$utilities;
        $this->device_type = Request::header('Device-Type');
        $this->app_version = Request::header('App-Version');
        $this->device_id = !empty(Request::header('Device-Id'))? Request::header('Device-Id'): null;
    }

    public function applyPlus($data){
        $base_amount = $data['base_amount'];

        $plus_a_limit = Config::get('plus.plus_a');
        $plus_b_limit = Config::get('plus.plus_b');

        if($base_amount >= $plus_a_limit['lower_limit'] && $base_amount <= $plus_a_limit['upper_limit']){
            $data['plus_id'] = 1;
        }else if($base_amount >= $plus_b_limit['lower_limit']){
            $data['plus_id'] = 2;
        }

        $plus_ratecard = $this->plusRatecardData($data);
        if(!empty($plus_ratecard)){
            $this->applyRewards($plus_ratecard);
        }
    }

    public function plusRatecardData($data){
        if(!empty($data['plus_id'])){
            $plus = Plusratecards::active()->where('plus_id', $data['plus_id'])->first()->toArray();
        }
        
        return (!empty($plus)) ? $plus : null;
    }

    public function applyRewards($ratecard){
        
    }

}