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
use Plusratecards;
use VoucherCategory;

class PlusService {
    protected $utilities;

    public function __construct(Utilities $utilities) {
        $this->utilities	=	$utilities;
        $this->device_type = Request::header('Device-Type');
        $this->app_version = Request::header('App-Version');
        $this->device_id = !empty(Request::header('Device-Id'))? Request::header('Device-Id'): null;
    }

    public function applyPlus($data){
        Log::info("applyPlus");
        $plus_ratecard = $this->plusRatecardData($data);
        return $plus_ratecard;
    }

    public function plusRatecardData($data){
        Log::info("plusRatecardData");
        $plus = Plusratecards::active()->where('min', '<=', $data['base_amount'])->where('max', '>=', $data['base_amount'])->first()->toArray();
        return (!empty($plus)) ? $plus : null;
    }

    public function createPlusRewards($data){
        Log::info("createRewards");

        $customer_id = $data['customer_id'];
        Log::info('customer_id',[$customer_id]);
        $customer = Customer::find($customer_id);
        
        $plus_id = $data['plus']['plus_id'];
        Log::info("plus_id",[$plus_id]);
        $allRewards = VoucherCategory::active()->where('plus_id', $plus_id)->get();
        // return $allRewards;
        $claimed_rewards_arr = array();
        if(!empty($allRewards)){
            foreach($allRewards as $reward){
                $claimed_reward = $this->utilities->assignVoucher($customer, $reward, $data);
                $claimed_rewards_arr[$reward['name']] = !empty($claimed_reward) ? $claimed_reward : array();

                $combo_vouchers =[];
					if(!empty($voucher_category['flags']) && !empty($voucher_category['flags']['combo_vouchers_list'])){
						$combo_voucher_list =$voucher_category['flags']['combo_vouchers_list'];
						foreach($combo_voucher_list as $index=>$value){
							$voucher = VoucherCategory::find($value);
							$combo_vouchers[$value] = $this->utilities->assignVoucher($customer, $voucher);
						}
                    }
                    
					if(count($combo_vouchers) > 0){
						foreach($combo_vouchers as $index=>$value){
							if(!$value){
								$this->utilities->rollbackVouchers($customer, $combo_vouchers);
								return Response::json(array('status' => 400,'message' => 'Cannot claim reward. Please contact customer support (6).'));
							}
						}
					}
            }
        }

        return $claimed_rewards_arr;
        
    }

}