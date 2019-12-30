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
        $plus = Plusratecards::active()->where('min', '<=', $data['base_amount'])->where('max', '>=', $data['base_amount'])->first();
        return (!empty($plus)) ? $plus->toArray() : null;
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

        $voucher_not_claimed = array();
        if(!empty($allRewards)){
            foreach($allRewards as $reward){
                

                if(!empty($reward['flags']) && !empty($reward['flags']['combo_vouchers_list'])){

                    $rollback = false;
                    $combo_vouchers =[];

					$combo_voucher_list =$reward['flags']['combo_vouchers_list'];
					foreach($combo_voucher_list as $index=>$value){
                        $voucher = VoucherCategory::find($value);
                        $voucher['plus_id'] = [$plus_id];
                        $voucher['milestone'] = $reward['milestone'];
						$combo_vouchers[$value] = $this->utilities->assignVoucher($customer, $voucher, $data);
                    }
                    
                    if(count($combo_vouchers) > 0){
                        foreach($combo_vouchers as $index => $value){
                            if(!$value){
                                $rollback = true;
                                $this->utilities->rollbackVouchers($customer, $combo_vouchers);
                                array_push($voucher_not_claimed, $reward['_id']);
                            }
                        }
                    }

                    if(empty($rollback)){
                        Log::info("empty rollback");
                        
                        $flags= $reward['flags'];
						$flags['manual_redemption'] = true;
                        $reward['flags'] = $flags;
                        $claimed_reward = $this->utilities->assignVoucher($customer, $reward, $data);
                        $claimed_rewards_arr[$reward['name']] = !empty($claimed_reward) ? $claimed_reward : array();
                        $claimed_rewards_arr[$reward['name']]['combo_vouchers'] = $combo_vouchers;

                        if(empty($claimed_reward)){
                            array_push($voucher_not_claimed, $reward['_id']);
                        }
                    }

                    continue;
                }
                
                $claimed_reward = $this->utilities->assignVoucher($customer, $reward, $data);
                $claimed_rewards_arr[$reward['name']] = !empty($claimed_reward) ? $claimed_reward : array();
            }

            if(!empty($voucher_not_claimed)){
                \Order::where('_id',$data['_id'])->update('plus.voucher_not_sent', $voucher_not_claimed);
            }
        }

        return $claimed_rewards_arr;
        
    }

    public function getMembershipSuccessData($order, $booking_details_data){
        $str = "";

        // return $booking_details_data;
        // return $order['finder_name'];

        if(!empty($order['plus'])){
            $str = "<br><br>With your <b>".$booking_details_data["finder_name_location"]['value']."</b> membership you get <b>".$order['plus']['duration_text']." of Fitternity Plus</b>. You will also receive an email regarding all your Fitternity Plus privileges.";
        }
        return $str;
    }

}