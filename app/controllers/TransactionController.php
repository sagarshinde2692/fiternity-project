<?PHP

/**
 * ControllerName : TransactionController.
 * Maintains a list of functions used for TransactionController
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\FinderSms as FinderSms;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;
use App\Services\CustomerInfo as CustomerInfo;
use App\Notification\CustomerNotification as CustomerNotification;
use App\AmazonPay\PWAINBackendSDK;
use App\Services\Fitapi as Fitapi;
use App\Services\Fitweb as Fitweb;

class TransactionController extends \BaseController {

    protected $customermailer;
    protected $customersms;
    protected $sidekiq;
    protected $findermailer;
    protected $findersms;
    protected $utilities;
    protected $customerreward;
    protected $membership_array;
    protected $customernotification;
    protected $fitapi;
    protected $fitweb;

    public function __construct(
        CustomerMailer $customermailer,
        CustomerSms $customersms,
        Sidekiq $sidekiq,
        FinderMailer $findermailer,
        FinderSms $findersms,
        Utilities $utilities,
        CustomerReward $customerreward,
        CustomerNotification $customernotification,
        Fitapi $fitapi,
        Fitweb $fitweb
    ) {
        parent::__construct();
        $this->customermailer       =   $customermailer;
        $this->customersms          =   $customersms;
        $this->sidekiq              =   $sidekiq;
        $this->findermailer         =   $findermailer;
        $this->findersms            =   $findersms;
        $this->utilities            =   $utilities;
        $this->customerreward       =   $customerreward;
        $this->customernotification =   $customernotification;
        $this->fitapi               =   $fitapi;
        $this->fitweb               =   $fitweb;
        $this->ordertypes           =   array('memberships','booktrials','workout-session','healthytiffintrail','healthytiffinmembership','3daystrial','vip_booktrials', 'events');
        $this->appOfferDiscount     =   Config::get('app.app.discount');
        $this->appOfferExcludedVendors 				= Config::get('app.app.discount_excluded_vendors');

        $this->membership_array     =   array('memberships','healthytiffinmembership');

        $this->vendor_token = false;
        
        $vendor_token = Request::header('Authorization-Vendor');

        if($vendor_token){

            $this->vendor_token = true;
        }

        $this->error_status = ($this->vendor_token) ? 200 : 400;

    }

    public function capture(){

        $data = Input::json()->all();
        
        foreach ($data as $key => $value) {

            if(is_string($value)){
                $data[$key] = trim($value);
            }
        }

        if(isset($data['order_id']) && $data['order_id'] != ""){
            $data['order_id'] = intval($data['order_id']);
        }

        Log::info('------------transactionCapture---------------',$data);

        if(!isset($data['type'])){
            return Response::json(array('status' => 404,'message' =>'type field is required'), $this->error_status);
        }

        if($this->vendor_token){

            $data['customer_source'] = 'kiosk';

            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

            $data['finder_id'] = (int)$vendor['_id'];
        }

        $rules = array(
            'customer_name'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required',
            'customer_source'=>'required',
            // 'ratecard_id'=>'required|integer|min:1',
            'type'=>'required'
        );
        $asshole_numbers = ["7838038094","7982850036","8220720704","8510829603"];
        
        if(in_array(substr($data["customer_phone"], -10), $asshole_numbers)){
            return Response::json("Can't book anything for you.", $this->error_status);
        }

        if(!isset($data['manual_order'])){

            if(!isset($data['ratecard_id']) && !isset($data['ticket_id'])){
                return Response::json(array('status'=>400, 'message'=>'Ratecard Id or ticket Id is required'), $this->error_status);
            }
        }

        if(isset($data['finder_id']) && $data['finder_id'] != ""){

            $checkFinderState = $this->utilities->checkFinderState($data['finder_id']);

            if($checkFinderState['status'] != 200){
                return Response::json($checkFinderState, $this->error_status);
            }
        }

        $workout = array('vip_booktrials','3daystrial','booktrials','workout-session');
        if(in_array($data['type'],$workout)){
            $service_category = Service::find($data['service_id'], ['servicecategory_id']);

            if($service_category['servicecategory_id'] == 65){
                $workout_rules = array(
                    'schedule_date'=>'required',
                    // 'schedule_slot'=>'required'
                );
            }else{
                $workout_rules = array(
                    'schedule_date'=>'required',
                    'schedule_slot'=>'required'
                );
            }


            $rules = array_merge($rules,$workout_rules);
        }

        $membership = array('healthytiffintrail','healthytiffinmembership');

        if(!$this->vendor_token){

            $membership[] = 'memberships';
        }

        if(in_array($data['type'],$membership)){
            $membership_rules = array(
                'preferred_starting_date'=>'required'
            );

            $rules = array_merge($rules,$membership_rules);
        }

        // if($data['type'] == 'diet_plan'){
        //     $diet_plan_rules = array(
        //         'offering_type'=>'required'
        //     );

        //     $rules = array_merge($rules,$diet_plan_rules);
        // }

        if(isset($data['manual_order']) && $data['manual_order']){

            $manual_order_rules = [
                ' = Service::find(_id'=>'required',
                'validity'=>'required',
                'validity_type'=>'required',
                'amount'=>'required',
                'service_name'=>'required'
            ];

            $rules = array_merge($rules,$manual_order_rules);
        }

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),$this->error_status);
        }

        /*if(isset($data['wallet']) && !$data['wallet']){
            $data['paymentmode_selected'] = 'paymentgateway';
        }*/

        if(isset($data['paymentmode_selected']) && $data['paymentmode_selected'] != ""){

            $data['part_payment'] = false;

            switch ($data['paymentmode_selected']) {
                case 'part_payment': $data['part_payment'] = true;break;
                case 'cod': $data['payment_mode'] = 'cod';break;
                case 'emi': $data['payment_mode'] = 'paymentgateway';break;
                case 'paymentgateway': $data['payment_mode'] = 'paymentgateway';break;
                case 'pay_at_vendor': $data['payment_mode'] = 'at the studio';break;
                case 'pay_later': $data['pay_later'] = true;break;
                default:break;
            }

        }

        if($this->vendor_token && !isset($data['order_id'])){
            
            if($this->utilities->checkFitternityCustomer($data['customer_email'], $data['customer_phone'])){
                
                $data['routed_order'] = '0';
            
            }else{
             
                $data['routed_order'] = '1';
            
            }
        
        }

        $updating_part_payment = (isset($data['part_payment']) && $data['part_payment']) ? true : false;

        $updating_cod = (isset($data['payment_mode']) && $data['payment_mode'] == 'cod') ? true : false;

        if($data['type'] == "events" && isset($data['event_customers']) && count($data['event_customers']) > 0 ){

            $event_customers = $data['event_customers'];

            $event_customer_email = [];
            $event_customer_phone = [];

            foreach ($event_customers as $customer_data) {

                if(in_array($customer_data["customer_email"],$event_customer_email)){

                    return Response::json(array('status' => 404,'message' => 'cannot enter same email id'),$this->error_status);

                }else{

                    $event_customer_email[] = strtolower($customer_data["customer_email"]);
                }

                if(in_array($customer_data["customer_phone"],$event_customer_phone)){

                    return Response::json(array('status' => 404,'message' => 'cannot enter same contact number'),$this->error_status);

                }else{

                    $event_customer_phone[] = $customer_data["customer_phone"];
                }
            }
            
        }

        if(isset($data['myreward_id']) && $data['type'] == "workout-session"){

            $validateMyReward = $this->validateMyReward($data['myreward_id']);

            if($validateMyReward['status'] != 200){
                return Response::json($validateMyReward,$this->error_status);
            }
        }

        $customerDetail = $this->getCustomerDetail($data);

        if($customerDetail['status'] != 200){
            return Response::json($customerDetail,$this->error_status);
        }

        $data = array_merge($data,$customerDetail['data']); 

        if(isset($data['customers_list'])){
            foreach($data['customers_list'] as $key => $customer){
                $data['customers_list'][$key]['customer_id'] = autoRegisterCustomer($customer);
            }
        }
        
        if(isset($data['coupon_code']) && $this->utilities->isGroupId($data['coupon_code'])){
            
            if($this->utilities->validateGroupId(['group_id'=>$data['coupon_code'], 'customer_id'=>$data['customer_id']])){

                $data['group_id'] = $data['coupon_code'];

            } 
             
             unset($data['coupon_code']);
 
         }
          
        $payment_mode = isset($data['payment_mode']) ? $data['payment_mode'] : "";

        if(isset($data['ratecard_id'])){
            
            $ratecard_id = (int) $data['ratecard_id'];

            $ratecardDetail = $this->getRatecardDetail($data);

            if($ratecardDetail['status'] != 200){
                return Response::json($ratecardDetail,$this->error_status);
            }

            $data = array_merge($data,$ratecardDetail['data']);

            if(isset($data['customer_quantity'])){
                
                $data['ratecard_amount'] = $data['amount'];
                $data['amount'] = $data['customer_quantity'] * $data['amount'];
                $data['amount_finder'] = $data['customer_quantity'] * $data['amount_finder'];
                
            }

        }

        if(isset($data['manual_order']) && $data['manual_order']){

            $manualOrderDetail = $this->getManualOrderDetail($data);

            if($manualOrderDetail['status'] != 200){
                return Response::json($manualOrderDetail,$this->error_status);
            }

            $data = array_merge($data,$manualOrderDetail['data']);
        }

        if($payment_mode=='cod'){

            $data['payment_mode'] = 'cod';

            $data["secondary_payment_mode"] = "cod_membership";
            
        }        

        

        $finder_id = (int) $data['finder_id'];

        $finderDetail = $this->getFinderDetail($finder_id);

        if($finderDetail['status'] != 200){
            return Response::json($finderDetail,$this->error_status);
        }

        

        // $cash_pickup = (isset($finderDetail['data']['finder_flags']) && isset($finderDetail['data']['finder_flags']['cash_pickup'])) ? $finderDetail['data']['finder_flags']['cash_pickup'] : false;

        $orderfinderdetail = $finderDetail;
        $data = array_merge($data,$orderfinderdetail['data']);
        unset($orderfinderdetail["data"]["finder_flags"]);

        if(isset($data['service_id'])){
            $service_id = (int) $data['service_id'];

            $serviceDetail = $this->getServiceDetail($service_id);

            if($serviceDetail['status'] != 200){
                return Response::json($serviceDetail,$this->error_status);
            }

            $data = array_merge($data,$serviceDetail['data']);
        }


        $order = false;

        if(isset($data['order_id']) && $data['order_id'] != ""){

            $old_order_id = $order_id = $data['_id'] = intval($data['order_id']);

            $order = Order::find((int)$old_order_id);

            $data['repetition'] = 1;

            if($order){

                if(isset($order->repetition)){
                    $data['repetition'] = $order->repetition + 1;
                }

                if(isset($order->cashback)){
                    $order->unset('cashback');
                }

                if(isset($order->reward_ids)){
                    $order->unset('reward_ids');
                }

                $order->update();
            }

        }else{

            $order_id = $data['_id'] = $data['order_id'] = Order::max('_id') + 1;
        }

        $data['code'] = (string)$data['order_id'];

        if(isset($data['referal_trial_id'])){

            $data['referal_trial_id'] = (int) $data['referal_trial_id'];
        }
        
        if($data['type'] == 'events'){

            $data['payment_mode'] = "paymentgateway";
            
            $data['vertical_type'] = 'event';
            $data['membership_duration_type'] = 'event';
            
            $data['ticket_quantity'] = isset($data['ticket_quantity']) ? $data['ticket_quantity'] : 1;
            
            if(isset($data['ticket_id'])){
                
                $ticket = Ticket::where('_id', $data['ticket_id'])->first();

                if($ticket){
                    $data['amount_customer'] = $data['amount'] = $data['amount_finder'] = $data['ticket_quantity'] * $ticket->price;
                }else{
                    $resp   =   array('status' => 400,'message' => "Ticket not found");
                    return Response::json($resp,$this->error_status);
                }
                
            }else{

                $resp   =   array('status' => 400,'message' => "Ticket id not found");
                return Response::json($resp,$this->error_status);
                
            }

            $finder = Finder::where('_id', $data['finder_id'])->first(['title']);
            if($finder){
                $data['finder_name'] = $finder->title;
            }

            $event = DbEvent::where('_id', $data['event_id'])->first(['name', 'slug']);

            if($event){
                $data['event_name'] = $event->name;
                if(in_array($event['slug'],Config::get('app.my_fitness_party_slug'))){
                    $data['event_type'] = "TOI";
                }
            }

            if(isset($data['coupon_code']) && $data['coupon_code'] != ""){
                $data['coupon_code'] = strtolower($data['coupon_code']);
                $already_applied_coupon = Customer::where('_id',$data['customer_id'])->whereIn('applied_promotion_codes',[$data['coupon_code']])->count();
            
                if($already_applied_coupon>0){
                    return Response::json(array('status'=>400, 'message'=>'Coupon already applied'), $this->error_status);
                }
            }

            
        }

        $data['amount_final'] = $data["amount_finder"];

        if(!$updating_part_payment && !isset($data['myreward_id'])) {

            $cashbackRewardWallet =$this->getCashbackRewardWallet($data,$order);
            Log::info("cashbackRewardWallet",$cashbackRewardWallet);

            if($cashbackRewardWallet['status'] != 200){
                return Response::json($cashbackRewardWallet,$this->error_status);
            }

            $data = array_merge($data,$cashbackRewardWallet['data']);
            
        }


        $txnid = "";
        $successurl = "";
        $mobilehash = "";

        if(in_array($data['customer_source'],['android','ios','kiosk'])){

            $txnid = "MFIT".$data['_id'];

            if(isset($old_order_id)){
                $txnid = "MFIT".$data['_id']."-R".$data['repetition'];
            }

            $successurl = $data['customer_source'] == "ios" ? Config::get('app.website')."/paymentsuccessios" : Config::get('app.website')."/paymentsuccessandroid";

        }else{

            $txnid = "FIT".$data['_id'];

            if(isset($old_order_id)){
                $txnid = "FIT".$data['_id']."-R".$data['repetition'];
            }

            $successurl = $data['type'] == "memberships" ? Config::get('app.website')."/paymentsuccess" : Config::get('app.website')."/paymentsuccesstrial";
        }
        

        if($data['type'] == 'events'){
            $successurl = Config::get('app.website')."/eventpaymentsuccess";
        }

        $data['txnid'] = $txnid;

        Log::info($finderDetail["data"]);
        $tmp_finder_flags = (array) $finderDetail['data']['finder_flags'];
        $part_payment = (!empty($tmp_finder_flags) && isset($finderDetail['data']['finder_flags']['part_payment'])) ? $finderDetail['data']['finder_flags']['part_payment'] : false;
        $cash_pickup = (!empty($tmp_finder_flags) && isset($finderDetail['data']['finder_flags']['cash_pickup'])) ? $finderDetail['data']['finder_flags']['cash_pickup'] : false;
        
        if(!$updating_part_payment && $part_payment && $data["amount_finder"] >= 3000){

            $part_payment_data = $data;

            $convinience_fee = $part_payment_data['convinience_fee'] = 0;

            $part_payment_data["amount"] = 0;


            if($finderDetail["data"]["finder_flags"]["part_payment"]){

                if($this->utilities->isConvinienceFeeApplicable($data)){
                    
                    $convinience_fee_percent = Config::get('app.convinience_fee');

                    $convinience_fee = round($part_payment_data['amount_finder']*$convinience_fee_percent/100);

                    $convinience_fee = $convinience_fee <= 150 ? $convinience_fee : 150;
                    
                    $part_payment_data['convinience_fee'] = $convinience_fee;

                }

                $part_payment_amount = ceil(($data["amount_finder"] * (20 / 100)));

                $part_payment_data["amount"] = $convinience_fee + $part_payment_amount;

                if(isset($data['cashback_detail']) && isset($data['cashback_detail']['amount_deducted_from_wallet'])){

                    $part_payment_data["amount"] = 0;

                    if(($convinience_fee + $part_payment_amount) > $data['cashback_detail']['amount_deducted_from_wallet']){
                        $part_payment_data["amount"] = ($convinience_fee + $part_payment_amount) - $data['cashback_detail']['amount_deducted_from_wallet'];
                    }

                }

                $part_payment_hash ="";
                
                if($part_payment_data["amount"] > 0 ){
                    $part_payment_hash = isset($data['part_payment_calculation']['hash']) ? $data['part_payment_calculation']['hash'] :  getHash($part_payment_data)['payment_hash'];
                }else{
                    $part_payment_data["amount"] = 0;
                }
            }

            $data["part_payment_calculation"] = array(
                "amount" => (int)($part_payment_data["amount"]),
                "hash" => $part_payment_hash,
                "convinience_fee"=>$part_payment_data['convinience_fee'],
                "full_wallet_payment" => $part_payment_data["amount"] == 0 ? true : false,
                "part_payment_amount"=>$part_payment_amount,
                "part_payment_and_convinience_fee_amount"=>$part_payment_amount + $convinience_fee
            );

        }

        
        if(isset($data['part_payment']) && $data['part_payment']){

            $convinience_fee = 0;

            if(isset($order["part_payment_calculation"]["convinience_fee"]) && $order["part_payment_calculation"]["convinience_fee"] > 0){

                $convinience_fee = $order["part_payment_calculation"]["convinience_fee"];
            }

            if(isset($order['wallet_amount']) && ((int) $order['wallet_amount']) > 0){

                $req = array(
                    'customer_id'=>$order['customer_id'],
                    'order_id'=>$order['_id'],
                    'amount'=>$order['wallet_amount'],
                    'type'=>'REFUND',
                    'entry'=>'credit',
                    'description'=>'Refund for Order ID: '.$order['_id'],
                    'full_amount'=>true
                );

                $walletTransactionResponse = $this->utilities->walletTransaction($req,$order->toArray());

                if(isset($order['wallet_refund_sidekiq']) && $order['wallet_refund_sidekiq'] != ''){
                    try {
                        $this->sidekiq->delete($order['wallet_refund_sidekiq']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }

                $order->unset('wallet');
                $order->unset('wallet_amount');
            }

            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($order['amount'],$data['finder_id'],'paymentgateway',$data['offer_id'],false,$order["part_payment_calculation"]["part_payment_and_convinience_fee_amount"],$convinience_fee,$data['type']);

            if(isset($data['wallet']) && $data['wallet'] == true){

                $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
            }

            if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                $req = array(
                    'customer_id'=>$data['customer_id'],
                    'order_id'=>$data['order_id'],
                    'amount'=>$data['wallet_amount'],
                    'type'=>'DEBIT',
                    'entry'=>'debit',
                    'description'=> $this->utilities->getDescription($data),
                    'finder_id'=>$data['finder_id'],
                    'order_type'=>$data['type']
                );

                $walletTransactionResponse = $this->utilities->walletTransactionNew($req);
                
                if($walletTransactionResponse['status'] == 200){
                    $data['wallet_transaction_debit'] = $walletTransactionResponse['wallet_transaction_debit'];
                }
            }

            $data['remaining_amount'] = $order['amount_customer'];

            if(isset($order["part_payment_calculation"]["part_payment_amount"]) && $order["part_payment_calculation"]["part_payment_amount"] > 0){

                $data['remaining_amount'] -= $order["part_payment_calculation"]["part_payment_amount"];
            }

            if(isset($order["part_payment_calculation"]["convinience_fee"]) && $order["part_payment_calculation"]["convinience_fee"] > 0){

                $data['remaining_amount'] -= $order["part_payment_calculation"]["convinience_fee"];
            }

            if(isset($order['coupon_discount_amount']) && $order['coupon_discount_amount'] > 0){

                $data['remaining_amount'] -= $order['coupon_discount_amount'];
            }

            if(isset($order['customer_discount_amount']) && $order['customer_discount_amount'] > 0){

                $data['remaining_amount'] -= $order['customer_discount_amount'];
            }

            if(isset($order['app_discount_amount']) && $order['app_discount_amount'] > 0){

                $data['remaining_amount'] -= $order['app_discount_amount'];
            }

            $data['amount'] = $order["part_payment_calculation"]["amount"];

        }

        $data['convinience_fee'] = 0;

        if($this->utilities->isConvinienceFeeApplicable($data)){
            
            $convinience_fee_percent = Config::get('app.convinience_fee');

            $convinience_fee = round($data['amount_finder']*$convinience_fee_percent/100);

            $convinience_fee = $convinience_fee <= 150 ? $convinience_fee : 150;

            $data['convinience_fee'] = $convinience_fee;

        }

        $data['base_amount'] = $order['amount_customer'] - $data['convinience_fee'] ;

        $hash = getHash($data);
        $data = array_merge($data,$hash);

        $data = $this->unsetData($data);

        if(isset($data['service_id'])){

            $data['service_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']);
        
        }

        $data['payment_link'] = Config::get('app.website')."/paymentlink/".$data['order_id']; //$this->utilities->getShortenUrl(Config::get('app.website')."/paymentlink/".$data['order_id']);

        if(in_array($data['type'],$this->membership_array) && isset($data['ratecard_id']) && $data['ratecard_id'] != ""){
            $data['payment_link'] = Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']."/".$data['ratecard_id']."/".$data['order_id']; //$this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']."/".$data['ratecard_id']."/".$data['order_id']);
        }

        $data['vendor_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/".$data['finder_slug']);

        $data['profile_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);

        $data['workout_article_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/article/complete-guide-to-help-you-prepare-for-the-first-week-of-your-workout");
        $data['download_app_link'] = Config::get('app.download_app_link');
        $data['diet_plan_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/diet-plan");

        if(in_array($data['type'],$this->membership_array) && isset($data['start_date'])){

            $data["auto_followup_date"] = date('Y-m-d H:i:s', strtotime("+31 days",strtotime($data['start_date'])));
            $data["followup_status"] = "abandon_cart";
        }


        $addUpdateDevice = $this->utilities->addUpdateDevice($data['customer_id']);

        foreach ($addUpdateDevice as $header_key => $header_value) {

            if($header_key != ""){
               $data[$header_key]  = $header_value;
            }
            
        }

        if($this->utilities->checkCorporateLogin()){
            $data['full_payment_wallet'] = true;
        }

        if(isset($data['myreward_id']) && $data['type'] == "workout-session"){
            $data['amount'] = 0;
            $data['full_payment_wallet'] = true;
        }

        if($data['amount'] == 0){
            $data['full_payment_wallet'] = true;
        }

        $data["status"] = "0";

        if(isset($data['paymentmode_selected']) && $data['paymentmode_selected'] == 'pay_at_vendor'){

            $data['payment_mode'] = 'at the studio';
            $data["secondary_payment_mode"] = "at_vendor_post";
        }

        $is_tab_active = isTabActive($data['finder_id']);

        if($is_tab_active){
            $data['is_tab_active'] = true;
        }

        if(isset($old_order_id)){

            if($order){
               $order->update($data); 
            }else{
                $order = new Order($data);
                $order->_id = $order_id;
                $order->save();
            }

        }else{

            $order = new Order($data);
            $order->_id = $order_id;
            $order->save();
        }

        if(isset($data['payment_mode']) && $data['payment_mode'] == 'cod'){

            $group_id = isset($data['group_id']) ? $data['group_id'] : null;
            $order->group_id = $data['group_id'] = $this->utilities->addToGroup(['customer_id'=>$data['customer_id'], 'group_id'=>$group_id, 'order_id'=>$order['_id']]);
            $this->customermailer->orderUpdateCOD($order->toArray());
            $this->customersms->orderUpdateCOD($order->toArray());

            $order->cod_otp = $this->utilities->generateRandomString();

            $order->update();
        }

        if(isset($data['pay_later']) && $data['pay_later'] && isset($data['wallet']) && $data['wallet']){

            $order->pay_later = true;
            
            $order->update();
            
            $this->utilities->createWorkoutSession($order['_id']);

            $data['full_payment_wallet'] = true;
        }
        
        $this->utilities->financeUpdate($order);
        
        if(in_array($data['customer_source'],['android','ios','kiosk'])){
            $mobilehash = $data['payment_related_details_for_mobile_sdk_hash'];
        }

        $result['firstname'] = strtolower($data['customer_name']);
        $result['lastname'] = "";
        $result['phone'] = $data['customer_phone'];
        $result['email'] = strtolower($data['customer_email']);
        $result['orderid'] = $data['_id'];
        $result['txnid'] = $txnid;
        $result['amount'] = $data['amount'];
        $result['amount_final'] = $data['amount_final'];
        $result['productinfo'] = strtolower($data['productinfo']);
        $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($data['service_name']));
        $result['successurl'] = $successurl;
        $result['hash'] = $data['payment_hash'];
        $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
        $result['finder_name'] = strtolower($data['finder_name']);
        if(isset($data['convinience_fee'])){
            $result['convinience_fee_charged'] = true;
            $result['convinience_fee'] = $data['convinience_fee'];
        }
        if(isset($data['cashback_detail']) && isset($data['cashback_detail']['amount_deducted_from_wallet'])){
            $result['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
        }
        /*if(isset($data["part_payment_calculation"])){
            $result['part_payment_calculation'] = $data["part_payment_calculation"];
        }*/
        

        if(isset($data['full_payment_wallet'])){
            $result['full_payment_wallet'] = $data['full_payment_wallet'];
        }


        if($data['type'] == "events" && isset($data['event_customers']) && count($data['event_customers']) > 0 ){

            Queue::connection('redis')->push('TransactionController@autoRegisterCustomer', array('event_customers'=>$data['event_customers']),Config::get('app.queue'));
        }

        if(in_array($data['type'],$this->membership_array)){
            $redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),Config::get('app.queue'));
            $order->update(array('redis_id'=>$redisid));
        }

        // $cash_pickup_applicable = ($cash_pickup && isset($data['amount_final']) && $data['amount_final'] >= 3000) ? true : false;
        $cash_pickup_applicable = (isset($data['amount_final']) && $data['amount_final'] >= 2500) ? true : false;
        

        // $emi_applicable = $this->utilities->displayEmi(array('amount_final'=>$data['amount_final']));

        $emi_applicable = (isset($data['amount_final']) && $data['amount_final'] >= 5000) ? true : false;

        $part_payment_applicable = false; //(!$updating_part_payment && $part_payment && $data["amount_finder"] >= 3000) ? true : false;

        $pay_at_vendor_applicable = true;

        $pay_later = false;
        
        if($data['type'] == 'workout-session'){
            $pay_later = true;
        }

        $resp   =   array(
            'status' => 200,
            'data' => $result,
            'message' => "Tmp Order Generated Sucessfully",
            'part_payment' => $part_payment_applicable,
            'cash_pickup' => $cash_pickup_applicable,
            'emi'=>$emi_applicable,
            'pay_at_vendor'=>$pay_at_vendor_applicable,
            'pay_later'=>$pay_later
        );

        // if(isset($_GET['device_type']) && in_array($_GET['device_type'], ['android', 'ios'])){
            
            $resp['data']['order_details'] = $this->getBookingDetails($order->toArray());

            $payment_mode_type_array = ['paymentgateway'];

            if($emi_applicable && isset($order->amount_final) && $order->amount_final){

                $payment_mode_type_array[] = 'emi';
            }

            if(!$this->vendor_token){
        
                if($cash_pickup_applicable){

                    $payment_mode_type_array[] = 'cod';
                }

                if($part_payment_applicable){

                    $payment_mode_type_array[] = 'part_payment';
                }
            }

            if($this->vendor_token){
        
                if($pay_at_vendor_applicable){

                    $payment_mode_type_array[] = 'pay_at_vendor';
                }
            }

            if($data['type'] == 'workout-session'){
                $payment_mode_type_array[] = 'pay_later';
            }

            $payment_details = [];

            foreach ($payment_mode_type_array as $payment_mode_type) {

                $payment_details[$payment_mode_type] = $this->getPaymentDetails($order->toArray(),$payment_mode_type);

            }
            
            $resp['data']['payment_details'] = $payment_details;

            $resp['data']['payment_modes'] = [];

            if(isset($order->amount_final) && $order->amount_final ){
                $resp['data']['payment_modes'] = $this->getPaymentModes($resp);
            }
        // }

        if($data['payment_mode'] == 'at the studio' && isset($data['wallet']) && $data['wallet']){

            $data_otp = array_only($data,['finder_id','order_id','service_id','ratecard_id','payment_mode','finder_vcc_mobile','finder_vcc_email','customer_name','service_name','service_duration','finder_name']);

            $data_otp['action'] = "vendor_otp";

            if(isset($data['assisted_by']) && isset($data['assisted_by']['mobile']) && $data['assisted_by']['mobile'] != ""){

                $finder_vcc_mobile = explode(",",$data['finder_vcc_mobile']);

                $finder_vcc_mobile[] = $data['assisted_by']['mobile'];

                $data_otp['finder_vcc_mobile'] = implode(",", $finder_vcc_mobile);
            }

            if(isset($data['assisted_by']) && isset($data['assisted_by']['email']) && $data['assisted_by']['email'] != ""){

                $finder_vcc_email = explode(",",$data['finder_vcc_email']);

                $finder_vcc_email[] = $data['assisted_by']['email'];

                $data_otp['finder_vcc_email'] = implode(",", $finder_vcc_email);
            }

            $addTemp_flag  = true;

            if(isset($order['otp_data'])){

                $old_order = $order->toArray();

                $otp_data = $old_order['otp_data'];

                if(isset($otp_data['created_at']) && ((time() - $otp_data['created_at']) / 60) < 3){

                    $addTemp_flag = false;
                }
                $temp_id = $order['otp_data']['temp_id'];
                $temp = Temp::find($temp_id);

                if(!$temp){

                    return Response::json(['status' => 400, "message" => "Internal Error"],$this->error_status);
                }

                if($temp->verified == "Y"){

                    return Response::json(array('status' => 400,'message' => 'Already Verified'),$this->error_status);
                }

                $otp_data['otp'] = $temp['otp'];

            }

            if($addTemp_flag){

                $addTemp = addTemp($data_otp);

                $otp_data = [
                    'finder_vcc_mobile'=>$data_otp['finder_vcc_mobile'],
                    'finder_vcc_email'=>$data_otp['finder_vcc_email'],
                    'payment_mode'=>$data_otp['payment_mode'],
                    'temp_id'=>$addTemp['_id'],
                    'otp'=>$addTemp['otp'],
                    'created_at'=>time()
                ];

                $order->update(['otp_data'=>$otp_data]);

                $otp_data['otp'] = $addTemp['otp'];
            }

            $otp_data['customer_name'] = $data['customer_name'];
            $otp_data['service_name'] = $data['service_name'];
            $otp_data['service_duration'] = $data['service_duration'];
            $otp_data['finder_name'] = $data['finder_name'];

            $this->findersms->genericOtp($otp_data);
            $this->findermailer->genericOtp($otp_data);

            $resp['vendor_otp'] = $otp_data['otp'];

            $resp['data']['verify_otp_url'] = Config::get('app.url')."/kiosk/vendor/verifyotp";
            $resp['data']['resend_otp_url'] = Config::get('app.url')."/temp/regenerateotp/".$otp_data['temp_id'];

        }

        $resp['data']['assisted_by'] = $this->utilities->getVendorTrainer($data['finder_id']);

        $resp['data']['assisted_by_image'] = "https://b.fitn.in/global/tabapp-homescreen/freetrail-summary/trainer.png";

        $resp['data']['vendor_otp_message'] = "Enter the confirmation code provided by your gym/studio to activate your membership";

        if(in_array($data['type'],['booktrials','workout-session'])){
            $resp['data']['vendor_otp_message'] = " Enter the confirmation code provided by your gym/studio to activate your session";
        }

        if(isset($data['punching_order']) && $data['punching_order']){
            $resp['data']['vendor_otp_message'] = "Enter the verification code to confirm the membership.";
        }

        return Response::json($resp);

    }

    public function generateOtp($length = 4)
    {      
        $characters = '0123456789';
        $result = '';
        $charactersLength = strlen($characters);

        for ($p = 0; $p < $length; $p++)
        {
            $result .= $characters[rand(0, $charactersLength - 1)];
        }

        return $result;
    }

    public function verifyVendorOtpKiosk(){

        $data = Input::json()->all();

        $rules = array(
            'order_id'=>'required',
            'otp'=>'required'
        );

        $validator = Validator::make($data,$rules);

        $app_version  = (float)Request::header('App-Version');

        $status = ($app_version > 1.06) ? 200 : 400;

        if ($validator->fails()) {
            return Response::json(array('status' => 400,'message' => error_message($validator->errors())),$status);
        }

        $order = Order::find((int)$data['order_id']);

        if(!$order){

            return Response::json(['status' => 400, "message" => "Order Not Found"],$status);
        }

        if($order->status == "1"){

            return Response::json(['status' => 400, "message" => "Already Status Successfull"],$status);
        }

        $decodeKioskVendorToken = decodeKioskVendorToken();

        $vendor = $decodeKioskVendorToken->vendor;

        $finder_id = (int)$vendor->_id;

        if($finder_id != $order['finder_id']){

            return Response::json(['status' => 400, "message" => "Incorrect Vendor"],$status);
        }

        if(!isset($order['otp_data'])){

            return Response::json(['status' => 400, "message" => "OTP data not found"],$status);
        }

        if($order['otp_data']['otp'] != $data['otp']){

            return Response::json(['status' => 400, "message" => "Incorrect OTP"],$status);
        }

        $temp_id = $order['otp_data']['temp_id'];

        $temp = Temp::find($temp_id);

        if(!$temp){

            return Response::json(['status' => 400, "message" => "Internal Error"],$status);
        }

        if($temp['otp'] != $data['otp']){

            return Response::json(['status' => 400, "message" => "Incorrect OTP"],$status);
        }

        if($temp->verified == "Y"){

            return Response::json(array('status' => 400,'message' => 'Already Verified'),$status);

        }else{

            $temp->verified = "Y";
            $temp->save();
        }

        if(in_array($order['type'],['booktrials','workout-session'])){

            $data = [];

            $data['status'] = 'success';
            $data['order_success_flag'] = 'admin';
            $data['order_id'] = (int)$order['_id'];
            $data['customer_name'] = $order['customer_name'];
            $data['customer_email'] = $order['customer_email'];
            $data['customer_phone'] = $order['customer_phone'];
            $data['finder_id'] = (int)$order['finder_id'];
            $data['service_name'] = $order['service_name'];
            $data['type'] = $order['type'];
            $data['premium_session'] = true;

            if(isset($order['start_date']) && $order['start_date'] != ""){
                $data['schedule_date'] = date('d-m-Y',strtotime($order['start_date']));
            }

            if(isset($order['start_time']) && $order['start_time'] != "" && isset($order['end_time']) && $order['end_time'] != ""){
                $data['schedule_slot'] = $order['start_time']."-".$order['end_time'];
            }

            if(isset($order['schedule_date']) && $order['schedule_date'] != ""){
                $data['schedule_date'] = $order['schedule_date'];
            }

            if(isset($order['schedule_slot']) && $order['schedule_slot'] != ""){
                $data['schedule_slot'] = $order['schedule_slot'];
            }

            $storeBooktrial = $this->fitapi->storeBooktrial($data);

            if($storeBooktrial['status'] == 200){

                return Response::json($storeBooktrial['data'],$status);

            }else{

                return Response::json(['status' => 400, "message" => "Internal Error Please Report"],$status);
            }

        }else{

            $data['status'] = 'success';
            $data['order_success_flag'] = 'kiosk';
            $data['order_id'] = (int)$data['order_id'];
            $data['customer_email'] = $order['customer_email'];
            $data['send_communication_customer'] = 1;
            $data['send_communication_vendor'] = 1;

            return $this->successCommon($data);

        } 

    }



    public function update(){
        
        $decoded = decode_customer_token();

        $rules = array(
            "order_id"=>"numeric|required"
        );

        $data = Input::json()->all();

        Log::info("transaction update ----------------",$data);

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {

            return Response::json(array('status' => 401,'message' => error_message($validator->errors())),401);

        }else{

            $order_id = (int) $data['order_id'];
            $message = "";

            $order = Order::find($order_id);

            if(!$order){

                $resp   =   array("status" => 401,"message" => "Order Does Not Exists");
                return Response::json($resp,$resp["status"]);
            }

            if($order->customer_email != $decoded->customer->email){
                $resp   =   array("status" => 401,"message" => "Invalid Customer");
                return Response::json($resp,$resp["status"]);
            }

            if(isset($data["payment_mode"]) && $data["payment_mode"] == "cod"){
               $data["secondary_payment_mode"] = "cod_membership";
            }

            if($order->status == "1" && isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '' && $data['preferred_starting_date']  != '-'){

                $preferred_starting_date = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']));

                $data['start_date'] = $preferred_starting_date;
                $data['preferred_starting_date'] = $preferred_starting_date;
                $data['end_date'] = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']."+ ".($order->duration_day-1)." days"));

                $data['preferred_starting_change_date'] = date("Y-m-d H:i:s");

                $order->update($data);

                $emailData = $order->toArray();
                $emailData['preferred_starting_updated'] = true;

                $this->customermailer->sendPgOrderMail($emailData);
                $this->findermailer->sendPgOrderMail($emailData);
                $this->customersms->changeStartDate($emailData);
                $this->findersms->changeStartDate($emailData);

                $message = "Thank you! Your starting date has been changed";

                return Response::json(array('status' => 200,'message' => $message,'start_date'=>$data['start_date'],'end_date'=>$data['end_date']),200);

            }

            if($order->status == "1" && isset($data['upgrade_membership']) && $data['upgrade_membership'] == "requested"){

                $data['upgrade'] = ["requested"=>time()];

                $order->update($data);

                $emailData = $order->toArray();
                $emailData['capture_type'] = "upgrade-membership";
                $emailData['phone'] = $order->customer_phone;

                $this->customersms->landingPageCallback($emailData);

                $message = "Upgrade request has been noted. We will call you shortly.";

                $captureData = [
                    "customer_id" => $order->customer_id,
                    "customer_name" => $order->customer_name,
                    "customer_email" => $order->customer_email,
                    "customer_phone" => $order->customer_phone,
                    "order_id" => $order->order_id,
                    "finder_id" => $order->finder_id,
                    "city_id" => $order->city_id,
                    "capture_type" => "upgrade-membership"
                ];

                $this->utilities->addCapture($captureData);
            }

            return Response::json(array('status' => 200,'message' => $message),200);
        }

    }

    public function success(){

        $data = Input::json()->all();

        return $this->successCommon($data);

    }

    public function successCommon($data){
        
        $rules = array(
            'order_id'=>'required'
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }
        
        $order_id   =   (int) $data['order_id'];
        $order      =   Order::findOrFail($order_id);

        //If Already Status Successfull Just Send Response
        if(!isset($data["order_success_flag"]) && isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){

            $resp   =   array('status' => 401, 'statustxt' => 'error', "message" => "Already Status Successfull");
            return Response::json($resp,401);

        }elseif(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && isset($order->status) && $order->status != '1' && isset($order->order_action) && $order->order_action != 'bought'){

            $resp   =   array('status' => 401, 'statustxt' => 'error',"message" => "Status should be Bought");
            return Response::json($resp,401);
        }


        $hash_verified = $this->utilities->verifyOrder($data,$order);

        if($data['status'] == 'success' && $hash_verified){
            // Give Rewards / Cashback to customer based on selection, on purchase success......

            $this->utilities->demonetisation($order);

            array_set($data, 'status', '1');

            if(isset($order['part_payment']) && $order['part_payment'] && (!isset($data['order_success_flag']) || $data['order_success_flag'] != 'admin')){
                array_set($data, 'status', '3');
            }

            if($data['status'] == '1'){
                if($order->type == "memberships"){
                    $group_id = isset($order->group_id) ? $order->group_id : null;
                    $data['group_id'] = $this->utilities->addToGroup(['customer_id'=>$order->customer_id, 'group_id'=>$group_id, 'order_id'=>$order->_id]);
                }

                $this->customerreward->giveCashbackOrRewardsOnOrderSuccess($order);

                if(isset($order->reward_ids) && !empty($order->reward_ids)){

                    $reward_detail = array();

                    $reward_ids = array_map('intval',$order->reward_ids);

                    $rewards = Reward::whereIn('_id',$reward_ids)->get(array('_id','title','quantity','reward_type','quantity_type'));

                    if(count($rewards) > 0){

                        foreach ($rewards as $value) {

                            $title = $value->title;

                            if($value->reward_type == 'personal_trainer_at_studio' && isset($order->finder_name) && isset($order->finder_location)){
                                $title = "Personal Training At ".$order->finder_name." (".$order->finder_location.")";
                            }

                            $reward_detail[] = ($value->reward_type == 'nutrition_store') ? $title : $value->quantity." ".$title;
                            $profile_link = $value->reward_type == 'diet_plan' ? $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']."#diet-plan") : $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);
                            array_set($data, 'reward_type', $value->reward_type);

                        }

                        $reward_info = (!empty($reward_detail)) ? implode(" + ",$reward_detail) : "";

                        array_set($data, 'reward_info', $reward_info);
                        
                    }

                }

                if(isset($order->cashback) && $order->cashback === true && isset($order->cashback_detail) ){

                    $reward_info = "Cashback";
                    
                    array_set($data, 'reward_info', $reward_info);
                    array_set($data, 'reward_type', 'cashback');
                }
            }

            if(isset($order['previous_booktrial_id']) && $order['previous_booktrial_id'] != ""){

                $booktrial = Booktrial::find((int) $order['previous_booktrial_id']);

                if($booktrial){

                    $booktrial->final_lead_stage = "purchase_stage";
                    $booktrial->update();
                }
            }
            
            array_set($data, 'order_action', 'bought');

            if(((!isset($data['order_success_flag']) || $data['order_success_flag'] != 'admin') && !isset($order['success_date'])) || (isset($order['update_success_date']) && $order['update_success_date'] == "1" && isset($data['order_success_flag']) && $data['order_success_flag'] == 'admin')){
                array_set($data, 'success_date', date('Y-m-d H:i:s',time()));
            }
            
            if(isset($order['start_date'])){
                array_set($data, 'auto_followup_date', date('Y-m-d H:i:s', strtotime("+7 days",strtotime($order['start_date']))));
            }
            
            array_set($data, 'followup_status', 'catch_up');
            array_set($data, 'followup_status_count', 1);

            $data['workout_article_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/article/complete-guide-to-help-you-prepare-for-the-first-week-of-your-workout");
            $data['download_app_link'] = Config::get('app.download_app_link');
            $data['diet_plan_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/diet-plan");

            if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
            
                array_set($data, 'membership_bought_at', 'Fitternity Payu Mode');

                $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone','LIKE','%'.substr($order->customer_phone, -8).'%')->where('_id','!=',(int)$order->_id)->where('finder_id',$order->finder_id)->count();

                if($count > 0){
                    array_set($data, 'acquisition_type', 'renewal_direct');
                    array_set($data, 'membership_type', 'renewal');
                }else{
                    array_set($data,'acquisition_type','direct_payment');
                    array_set($data, 'membership_type', 'new');
                }

                if($order->customer_source != 'admin'){

                    array_set($data, 'secondary_payment_mode', 'payment_gateway_membership');
                }
            }

            if(isset($order->wallet_refund_sidekiq) && $order->wallet_refund_sidekiq != ''){

                try {
                    $this->sidekiq->delete($order->wallet_refund_sidekiq);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }

            if($order['type'] == 'memberships' || $order['type'] == 'healthytiffinmembership'){

                if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){

                    if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                        if(isset($order->instantPurchaseCustomerTiggerCount) && $order->instantPurchaseCustomerTiggerCount != ""){
                            $data['instantPurchaseCustomerTiggerCount']     =  intval($order->instantPurchaseCustomerTiggerCount) + 1;
                        }else{
                            $data['instantPurchaseCustomerTiggerCount']     =   1;
                        }
                    }

                }else{
                    if(isset($order->instantPurchaseCustomerTiggerCount) && $order->instantPurchaseCustomerTiggerCount != ""){
                        $data['instantPurchaseCustomerTiggerCount']     =  intval($order->instantPurchaseCustomerTiggerCount) + 1;
                    }else{
                        $data['instantPurchaseCustomerTiggerCount']     =   1;
                    }
                }


                if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                    if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                        if(isset($order->instantPurchaseFinderTiggerCount) && $order->instantPurchaseFinderTiggerCount != ""){
                            $data['instantPurchaseFinderTiggerCount']       =  intval($order->instantPurchaseFinderTiggerCount) + 1;
                        }else{
                            $data['instantPurchaseFinderTiggerCount']       =   1;
                        }
                    }

                }else{
                    if(isset($order->instantPurchaseFinderTiggerCount) && $order->instantPurchaseFinderTiggerCount != ""){
                        $data['instantPurchaseFinderTiggerCount']       =  intval($order->instantPurchaseFinderTiggerCount) + 1;
                    }else{
                        $data['instantPurchaseFinderTiggerCount']       =   1;
                    }
                }
                
            }
            $data["profile_link"] = isset($profile_link) ? $profile_link : $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);
            $order->update($data);

            //send welcome email to payment gateway customer

            $finder = Finder::find((int)$order->finder_id);
            try {
                if(isset($order->referal_trial_id) && $order->referal_trial_id != ''){
                    $trial = Booktrial::find((int) $order->referal_trial_id);
                    if($trial){
                        $bookdata = array();
                        array_set($bookdata, 'going_status', 4);
                        array_set($bookdata, 'going_status_txt', 'purchased');
                        array_set($bookdata, 'booktrial_actions', '');
                        array_set($bookdata, 'followup_date', '');
                        array_set($bookdata, 'followup_date_time', '');

                        $trial->update($bookdata);
                    }
                }

            } catch (Exception $e) {

                Log::error($e);

            }

            $abundant_category = array(42,45);

            if($order->finder_id == 6318){
                $abundant_category = array(42);
            }

            if(isset($order['part_payment']) && $order['part_payment'] && $data['status'] == '3'){

                $this->customermailer->orderUpdatePartPayment($order->toArray());
                $this->customersms->orderUpdatePartPayment($order->toArray());
                $this->findermailer->orderUpdatePartPayment($order->toArray());
                $this->findersms->orderUpdatePartPayment($order->toArray());
                $this->findermailer->partPaymentFitternity($order->toArray());
                
                $daysToGo = Carbon::now()->diffInDays(\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $order->preferred_starting_date));
            
                if($daysToGo > 2){

                    $before2days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $order->preferred_starting_date)->addMinutes(-60 * 24 * 2);
                    $order->cutomerEmailBefore2Days = $this->customermailer->orderUpdatePartPaymentBefore2Days($order->toArray(), $before2days);
                    $order->cutomerSmsBefore2Days = $this->customersms->orderUpdatePartPaymentBefore2Days($order->toArray(), $before2days);

                    $order->finderEmailBefore2Days = $this->findermailer->orderUpdatePartPaymentBefore2Days($order->toArray(), $before2days);
                    $order->finderSmsBefore2Days = $this->findersms->orderUpdatePartPaymentBefore2Days($order->toArray(), $before2days);
                
                }
            
            }else{

                if (filter_var(trim($order['customer_email']), FILTER_VALIDATE_EMAIL) === false){
                    $order->update(['email_not_sent'=>'captureOrderStatus']);
                }else{

                    if(!in_array($finder->category_id, $abundant_category)){
                        $emailData      =   [];
                        $emailData      =   $order->toArray();
                        if($emailData['type'] == 'events'){
                            if(isset($emailData['event_id']) && $emailData['event_id'] != ''){
                                $emailData['event'] = DbEvent::find(intval($emailData['event_id']))->toArray();
                            }
                            if(isset($emailData['ticket_id']) && $emailData['ticket_id'] != ''){
                                $emailData['ticket'] = Ticket::find(intval($emailData['ticket_id']))->toArray();
                            }
                        }

                        //print_pretty($emailData);exit;
                        if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
                            if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                                $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                            }

                        }else{
                            $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                        }
                    }

                    //no email to Healthy Snacks Beverages and Healthy Tiffins
                    if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan'){
                        
                        if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                            if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                                $sndPgMail  =   $this->findermailer->sendPgOrderMail($order->toArray());
                            }
                            
                        }else{
                            $sndPgMail  =   $this->findermailer->sendPgOrderMail($order->toArray());
                        }

                    }
                }

                //SEND payment gateway SMS TO CUSTOMER and vendor
                if(!in_array($finder->category_id, $abundant_category)){
                    $emailData      =   [];
                    $emailData      =   $order->toArray();
                    if($emailData['type'] == 'events'){
                        if(isset($emailData['event_id']) && $emailData['event_id'] != ''){
                            $emailData['event'] = DbEvent::find(intval($emailData['event_id']))->toArray();
                        }
                        if(isset($emailData['ticket_id']) && $emailData['ticket_id'] != ''){
                            $emailData['ticket'] = Ticket::find(intval($emailData['ticket_id']))->toArray();
                        }
                    }
                    
                    if($this->utilities->checkCorporateLogin()){
                        Log::info("outside checkCorporateLogin ");
                        $emailData['customer_email'] =   $emailData['customer_email'].',vg@fitmein.in';
                    }

                    //print_pretty($emailData);exit;
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
                        if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                            $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                        }

                    }else{
                        $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                    }
                }

                //no sms to Healthy Snacks Beverages and Healthy Tiffins
                if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan'){
                    
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                        if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                            $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                        }
                        
                    }else{
                        $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                    }
                    
                }
            }

            $this->utilities->sendCorporateMail($order->toArray());

            if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !in_array($finder->category_id, $abundant_category) && $order->type == "memberships" && !isset($order->customer_sms_after3days) && !isset($order->customer_email_after10days) && $order->type != 'diet_plan'){

                $preferred_starting_date = $order->preferred_starting_date;

                $category_slug = "no_category";

                if(isset($order->finder_category_id) && $order->finder_category_id != ""){

                    $finder_category_id = $order->finder_category_id;

                    $category = Findercategory::find((int)$finder_category_id);

                    if($category){
                        $category_slug = $category->slug;
                    }
                }

                $order_data = $order->toArray();

                $order_data['category_array'] = $this->getCategoryImage($category_slug);

                $after1days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 1);
                $after7days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 7);
                $after15days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 15);

                $this->customersms->purchaseInstant($order->toArray());
                $order->cutomerSmsPurchaseAfter1Days = $this->customersms->purchaseAfter1Days($order_data,$after1days);
                $order->cutomerSmsPurchaseAfter7Days = $this->customersms->purchaseAfter7Days($order_data,$after7days);
                $order->cutomerSmsPurchaseAfter15Days = $this->customersms->purchaseAfter15Days($order_data,$after15days);

                $order->update();

            }

            if(isset($order->city_id)){

                $city = City::find((int)$order->city_id,['_id','name','slug']);

                $order->update(['city_name'=>$city->name,'city_slug'=>$city->slug]);
            }

            if(isset($order->finder_category_id)){

                $category = Findercategory::find((int)$order->finder_category_id,['_id','name','slug']);

                $order->update(['category_name'=>$category->name,'category_slug'=>$category->slug]);
            }
            

            if(isset($order->diet_plan_ratecard_id) && $order->diet_plan_ratecard_id != "" && $order->diet_plan_ratecard_id != 0 && !isset($order->diet_plan_order_id) || (isset($order->diet_inclusive) && $order->diet_inclusive)){

                if(!isset($order->diet_plan_ratecard_id)){
                    $ratecard = Ratecard::find($order->ratecard_id, array('diet_ratecard'));
                    if($ratecard){
                        $order->diet_plan_ratecard_id = $ratecard->diet_ratecard;
                    }
                }

                $generaterDietPlanOrder = $this->generaterDietPlanOrder($order->toArray());

                if($generaterDietPlanOrder['status'] != 200){
                    return Response::json($generaterDietPlanOrder,$generaterDietPlanOrder['status']);
                }

                $order->diet_plan_order_id = $generaterDietPlanOrder['order_id'];
                $order->update();

            }

            if(isset($order->type) && $order->type == "diet_plan"){
                $order->final_assessment = "no";
                $order->renewal = "no";
                $order->update();
            }
            if(isset($order->coupon_code)){
                $coupon = Coupon::where('code', strtolower($order['coupon_code']))->first();
                Log::info("coupon");
                $fitternity_only_coupon = ($coupon && isset($coupon->fitternity_only) && $coupon->fitternity_only) ? true : false;
                
                if(!$fitternity_only_coupon){
                    $customer_update 	=	Customer::where('_id', $order->customer_id)->push('applied_promotion_codes', $order->coupon_code, true);	
                }
            }

            $this->utilities->setRedundant($order);

            $this->utilities->addAmountToReferrer($order);

            $this->utilities->addAssociateAgent($order);
            
            $finder_id = $order['finder_id'];
            $start_date_last_30_days = date("d-m-Y 00:00:00", strtotime('-31 days',strtotime(date('d-m-Y 00:00:00'))));

            $sales_count_last_30_days = Order::active()->where('finder_id',$finder_id)->where('created_at', '>=', new DateTime($start_date_last_30_days))->count();

            if($sales_count_last_30_days == 0){
            $mailData=array();
            $mailData['finder_name']=$order['finder_name'];
            $mailData['finder_id']=$order['finder_id'];
            $mailData['finder_city']=$order['finder_city'];
            $mailData['finder_location']=$order['finder_location'];
            $mailData['customer_name']=$order['customer_name'];
            $mailData['customer_email']=$order['customer_email'];

                $sndMail  =   $this->findermailer->sendNoPrevSalesMail($mailData);
            }

            $this->utilities->deleteCommunication($order);

            if(isset($order->redundant_order)){
                $order->unset('redundant_order');
            }

            $this->utilities->sendDemonetisationCustomerSms($order);

            if(isset($order->customer_id)){
                setDefaultAccount($order->toArray(), $order->customer_id);
            }

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");

            if($order['payment_mode'] == 'at the studio'){
                $resp   =   array('status' => 200,"message" => "Transaction Successful");
            }

            return Response::json($resp);

        }else{
            if($hash_verified == false){
                $Oldorder 		= 	Order::findOrFail($order_id);
                $Oldorder["hash_verified"] = false;
                $Oldorder->update();
                $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
                return Response::json($resp);
            }
        }

            
        $orderdata 		=	$order->update($data);
        $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
        return Response::json($resp);
    }

    public function unsetData($data){

        $array = array('preferred_starting_date','start_date','start_date_starttime','end_date','preferred_payment_date');

        foreach ($array as $key => $value){

            if(isset($data[$value])){
                if($data[$value] == ""){
                    unset($data[$value]);
                }
            }
        }

        return $data;
    }

    public function getCustomerDetail($data){

        $data['customer_email'] = trim(strtolower($data['customer_email']));
        
        $customer_id = $data['customer_id'] = autoRegisterCustomer($data);

        $data['logged_in_customer_id'] = $customer_id;

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = customerTokenDecode($jwt_token);
            $data['logged_in_customer_id'] = (int)$decoded->customer->_id;
        }

        $customer = Customer::find((int)$customer_id);

        if(isset($data['address']) && $data['address'] != ''){

            $data['customer_address']  = $data['address'];
        }

        if(isset($data['customer_address']) && $data['customer_address'] != ''){

            $data['address']  = $data['customer_address'];
        }

        if(isset($data['customer_address']) && is_array($data['customer_address']) && !empty($data['customer_address'])){
            
            $customerData['address'] = $data['customer_address'];
            $customer->update($customerData);

            $data['customer_address'] = $data['address'] = implode(",", array_values($data['customer_address']));
        }

        if(isset($data['customer_phone']) && $data['customer_phone'] != ''){
            setVerifiedContact($customer_id, $data['customer_phone']);
        }

        $device_type = (isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id = (isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

        if($device_type == '' || $gcm_reg_id == ''){

            $getRegId = getRegId($data['customer_id']);

            if($getRegId['flag']){

                $$device_type = $data["device_type"] = $getRegId["device_type"];;
                $gcm_reg_id = $data["reg_id"] = $getRegId["reg_id"]; 

                $data['gcm_reg_id'] = $getRegId["reg_id"];
            }
        }

        if($device_type != '' && $gcm_reg_id != ''){

            $regData = array();

            $regData['customer_id'] = $data["customer_id"];
            $regData['reg_id'] = $gcm_reg_id;
            $regData['type'] = $device_type;

            $this->utilities->addRegId($regData);
        }

        return array('status' => 200,'data' => $data);

    }


    public function getCashbackRewardWallet($data,$order){

        $customer_id = (int)$data['customer_id'];

        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token : '.$jwt_token);
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }

        $customer = \Customer::find($customer_id);

        if(isset($customer->demonetisation)){

            return $this->getCashbackRewardWalletNew($data,$order);

        }

        return $this->getCashbackRewardWalletOld($data,$order);

    }


    public function getCashbackRewardWalletNew($data,$order){

        Log::info('new');

        $jwt_token = Request::header('Authorization');

        $customer_id = $data['customer_id'];
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }

        $amount = $data['amount_customer'] = $data['amount'];

        $convinience_fee = 0;

        if($this->utilities->isConvinienceFeeApplicable($data)){
            
            $convinience_fee_percent = Config::get('app.convinience_fee');

            $convinience_fee = round($data['amount_finder']*$convinience_fee_percent/100);

            $convinience_fee = $convinience_fee <= 150 ? $convinience_fee : 150;

            $amount += $convinience_fee;

            $data['amount_customer'] += $convinience_fee;

            $data['amount'] += $convinience_fee;

            $data['convinience_fee'] = $convinience_fee;
        }
        $data['instant_payment_discount'] = 100;
        if($data['type'] == 'workout-session' && !(isset($data['pay_later']) && $data['pay_later'])){
            Log::info("inside instant discount");
            // $instant_payment_discount = 100;

            // $data['instant_payment_discount'] = $instant_payment_discount;
            
            $data['amount'] = $data['amount_customer'] = $data['amount_customer'] - $data['instant_payment_discount'];

            $amount  =  $data['amount'];

        }

        if($data['type'] != 'events'){

            if($data['type'] == "memberships" && isset($data['customer_source']) && (in_array($data['customer_source'],['android','ios','kiosk']))){

                $this->appOfferDiscount = in_array($data['finder_id'], $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;
                $data['app_discount_amount'] = intval($data['amount_finder'] * ($this->appOfferDiscount/100));

                $amount -= $data['app_discount_amount'];
            }

            $corporate_discount_percent = $this->utilities->getCustomerDiscount();
            $data['customer_discount_amount'] = intval($data['amount_finder'] * ($corporate_discount_percent/100));

            $amount -= $data['customer_discount_amount'];

            
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($amount,$data['finder_id'],'paymentgateway',$data['offer_id'],false,false,$convinience_fee,$data['type']);

            if(isset($data['cashback']) && $data['cashback'] == true){
                $amount -= $data['cashback_detail']['amount_discounted'];
            }

            if(!isset($data['repetition'])){

                if(isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){
                    $amount -= $data['cashback_detail']['amount_deducted_from_wallet'];
                }

                if(isset($data['wallet']) && $data['wallet'] == true){
                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                }

                if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                        'finder_id'=>$data['finder_id'],
                        'order_type'=>$data['type']
                    );

                    $walletTransactionResponse = $this->utilities->walletTransactionNew($req);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }else{
                        $data['wallet_transaction_debit'] = $walletTransactionResponse['wallet_transaction_debit'];
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                }

            }else{

                $new_data = Input::json()->all();

                if(isset($new_data['wallet']) && $new_data['wallet'] == true){

                    if(isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){
                        $amount -= $data['cashback_detail']['amount_deducted_from_wallet'];
                    }

                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];

                    if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                        $req = array(
                            'customer_id'=>$data['customer_id'],
                            'order_id'=>$data['order_id'],
                            'amount'=>$data['wallet_amount'],
                            'type'=>'DEBIT',
                            'entry'=>'debit',
                            'description'=> $this->utilities->getDescription($data),
                            'finder_id'=>$data['finder_id'],
                            'order_type'=>$data['type']
                        );
                        $walletTransactionResponse = $this->utilities->walletTransactionNew($req);
                        
                        if($walletTransactionResponse['status'] != 200){
                            return $walletTransactionResponse;
                        }else{
                            $data['wallet_transaction_debit'] = $walletTransactionResponse['wallet_transaction_debit'];
                        }

                        // Schedule Check orderfailure and refund wallet amount in that case....
                        $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                        $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                        $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);
                    }

                }else{

                    if(isset($order['wallet_amount']) && $order['wallet_amount'] != "" && $order['wallet_amount'] != 0){

                        $req = array(
                            'customer_id'=>$order['customer_id'],
                            'order_id'=>$order['_id'],
                            'amount'=>$order['wallet_amount'],
                            'type'=>'REFUND',
                            'entry'=>'credit',
                            'description'=>'Refund for Order ID: '.$order['_id'],
                            'full_amount'=>true,
                        );

                        $walletTransactionResponse = $this->utilities->walletTransactionNew($req);
                        
                        if(isset($order['wallet_refund_sidekiq']) && $order['wallet_refund_sidekiq'] != ''){
                            try {
                                $this->sidekiq->delete($order['wallet_refund_sidekiq']);
                            }catch(\Exception $exception){
                                Log::error($exception);
                            }
                        }

                        $order->unset('wallet');
                        $order->unset('wallet_amount');   

                    }

                    $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($amount,$data['finder_id'],'paymentgateway',$data['offer_id'],false,false,$convinience_fee,$data['type']);

                    if(isset($data['cashback']) && $data['cashback'] == true){
                        $amount -= $data['cashback_detail']['amount_discounted'];
                    }

                    if(isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){
                        $amount -= $data['cashback_detail']['amount_deducted_from_wallet'];
                    }

                }

            }

        }


        if(isset($data["coupon_code"]) && $data["coupon_code"] != ""){

            $ticket_quantity = isset($data['ticket_quantity'])?$data['ticket_quantity']:1;
            $ticket = null;

            if(isset($data['ticket_id'])){
                $ticket = Ticket::find($data['ticket_id']);
                if(!$ticket){
                    $resp = array('status'=>400, 'message'=>'Ticket not found');
                    return Response::json($resp, 400);
                }
            }
            
            $ratecard = isset($data['ratecard_id'])?Ratecard::find($data['ratecard_id']):null;

            $service_id = isset($data['service_id']) ? $data['service_id'] : null;
            
            $couponCheck = $this->customerreward->couponCodeDiscountCheck($ratecard,$data["coupon_code"],$customer_id, $ticket, $ticket_quantity, $service_id);

            if(isset($couponCheck["coupon_applied"]) && $couponCheck["coupon_applied"]){

                $data["coupon_discount_amount"] = $amount > $couponCheck["data"]["discount"] ? $couponCheck["data"]["discount"] : $amount;

                $amount -= $data["coupon_discount_amount"];

                if(isset($couponCheck["vendor_coupon"]) && $couponCheck["vendor_coupon"]){
                    $data["payment_mode"] = "at the studio";
                    $data["secondary_payment_mode"] = "cod_membership";
                }

                // if(strtolower($data["coupon_code"]) == 'fit2018'){
                //     $data['routed_order'] = "1";
                // }
            }
            
        }else{

            if($order && isset($order['coupon_code'])){

                $order->unset('coupon_code');
                $order->unset('coupon_discount_amount');
            }

            // if($order && isset($order['routed_order'])){
                
            //     $order->unset('routed_order');
            
            // }

        }

        $data['amount_final'] = $amount;

        if(isset($data['wallet']) && $data['wallet'] == true){
            $data['amount'] = $amount;
        }

        if($data['amount'] == 0){
            $data['full_payment_wallet'] = true;
        }else{
            $data['full_payment_wallet'] = false;
        }
        
        if($this->utilities->checkCorporateLogin()){
            $data["payment_mode"] = "at the studio";
            $data['full_payment_wallet'] = true;
        }
        
        if(isset($data['reward_ids'])&& count($data['reward_ids']) > 0) {
            $data['reward_ids']   =  array_map('intval', $data['reward_ids']);
        }

        return array('status' => 200,'data' => $data); 

    }

    public function getCashbackRewardWalletOld($data,$order){

        Log::info('old');

        $jwt_token = Request::header('Authorization');

        $customer_id = $data['customer_id'];
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }

        $amount = $data['amount_customer'] = $data['amount'];

        if($data['type'] == "memberships" && isset($data['customer_source']) && (in_array($data['customer_source'],['android','ios','kiosk']))){
            $this->appOfferDiscount = in_array($data['finder_id'], $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;
            $data['app_discount_amount'] = intval($data['amount'] * ($this->appOfferDiscount/100));
            $amount = $data['amount'] = $data['amount_customer'] = $data['amount'] - $data['app_discount_amount'];
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id'],false,false,$data['type']);
        }else{
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id'],false,false,$data['type']);
        }

        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios'])){


            if(isset($data['cashback']) && $data['cashback'] == true){
                $data['amount'] = $data['amount'] - $data['cashback_detail']['amount_discounted'];
            }

            if(!isset($data['repetition'])){

                if(isset($data['wallet']) && $data['wallet'] == true){
                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                    $data['amount'] = $data['amount'] - $data['wallet_amount'];
                }

                if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'amount_fitcash' => $data['wallet_amount'],
                        'amount_fitcash_plus' => 0,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                }

            }else{

                $new_data = Input::json()->all();

                if(isset($new_data['wallet']) && $new_data['wallet'] == true){

                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                    $data['amount'] = $data['amount'] - $data['wallet_amount'];

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'amount_fitcash' => $data['wallet_amount'],
                        'amount_fitcash_plus' => 0,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                }else{

                    if(isset($order['wallet_amount']) && $order['wallet_amount'] != "" && $order['wallet_amount'] != 0){

                        $req = array(
                            'customer_id'=>$order['customer_id'],
                            'order_id'=>$order['_id'],
                            'amount'=>$order['wallet_amount'],
                            'type'=>'REFUND',
                            'entry'=>'credit',
                            'description'=>'Refund for Order ID: '.$order['_id'],
                        );

                        $walletTransactionResponse = $this->utilities->walletTransaction($req,$order->toArray());
                        
                        if(isset($order['wallet_refund_sidekiq']) && $order['wallet_refund_sidekiq'] != ''){
                            try {
                                $this->sidekiq->delete($order['wallet_refund_sidekiq']);
                            }catch(\Exception $exception){
                                Log::error($exception);
                            }
                        }

                        $order->unset('wallet');
                        $order->unset('wallet_amount');
                    }

                }

            }

            

        }else{

            if(isset($data['cashback']) && $data['cashback'] == true){
                $amount = $data['amount'] - $cashback_detail['amount_discounted'];
            }

            if(!isset($data['repetition'])){

                if(isset($data['wallet']) && $data['wallet'] == true){

                    $wallet_amount = $data['wallet_amount'] = $cashback_detail['only_wallet']['fitcash'] + $cashback_detail['only_wallet']['fitcash_plus'];

                    $fitcash = $cashback_detail['only_wallet']['fitcash'];
                    $fitcash_plus = $cashback_detail['only_wallet']['fitcash_plus'];

                    if(isset($data['cashback']) && $data['cashback'] == true){

                        $wallet_amount = $data['wallet_amount'] = $cashback_detail['discount_and_wallet']['fitcash'] + $cashback_detail['discount_and_wallet']['fitcash_plus'];

                        $fitcash = $cashback_detail['discount_and_wallet']['fitcash'];
                        $fitcash_plus = $cashback_detail['discount_and_wallet']['fitcash_plus'];
                    }

                    $amount = $data['amount'] - $wallet_amount;

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'amount_fitcash' => $fitcash,
                        'amount_fitcash_plus' => $fitcash_plus,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);
                }

            }else{

                $new_data = Input::json()->all();

                if(isset($new_data['wallet']) && $new_data['wallet'] == true){

                    $wallet_amount = $data['wallet_amount'] = $cashback_detail['only_wallet']['fitcash'] + $cashback_detail['only_wallet']['fitcash_plus'];

                    $fitcash = $cashback_detail['only_wallet']['fitcash'];
                    $fitcash_plus = $cashback_detail['only_wallet']['fitcash_plus'];

                    if(isset($data['cashback']) && $data['cashback'] == true){

                        $wallet_amount = $data['wallet_amount'] = $cashback_detail['discount_and_wallet']['fitcash'] + $cashback_detail['discount_and_wallet']['fitcash_plus'];

                        $fitcash = $cashback_detail['discount_and_wallet']['fitcash'];
                        $fitcash_plus = $cashback_detail['discount_and_wallet']['fitcash_plus'];
                    }

                    $amount = $data['amount'] - $wallet_amount;

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'amount_fitcash' => $fitcash,
                        'amount_fitcash_plus' => $fitcash_plus,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                }else{

                    if(isset($order['wallet_amount']) && $order['wallet_amount'] != "" && $order['wallet_amount'] != 0){

                        $req = array(
                            'customer_id'=>$order['customer_id'],
                            'order_id'=>$order['_id'],
                            'amount'=>$order['wallet_amount'],
                            'type'=>'REFUND',
                            'entry'=>'credit',
                            'description'=>'Refund for Order ID: '.$order['_id'],
                        );

                        $walletTransactionResponse = $this->utilities->walletTransaction($req,$order->toArray());
                        
                        if(isset($order['wallet_refund_sidekiq']) && $order['wallet_refund_sidekiq'] != ''){
                            try {
                                $this->sidekiq->delete($order['wallet_refund_sidekiq']);
                            }catch(\Exception $exception){
                                Log::error($exception);
                            }
                        }

                        $order->unset('wallet');
                        $order->unset('wallet_amount');
                    }

                }
            }

            $data['amount'] = $amount;
        }

        if($data['amount'] == 0){
            $data['full_payment_wallet'] = true;
        }else{
            $data['full_payment_wallet'] = false;
        }
        
        if(isset($data['reward_ids'])&& count($data['reward_ids']) > 0) {
            $data['reward_ids']   =  array_map('intval', $data['reward_ids']);
        }

        return array('status' => 200,'data' => $data); 

    }

    public function orderFailureAction($order_id){

        $order = Order::where('_id',(int) $order_id)->where('status',"0")->first();

        if($order == ''){
            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'No Action Required'
                ),200

            );
        }

        // Update order status to failed........
        $order->update(['status' => '-1']);

        if($order['amount'] >= 15000){
            $order_data = $order->toArray();
            $order_data['finder_vcc_email'] = "vinichellani@fitternity.com";
            $this->findermailer->orderFailureNotificationToLmd($order_data);
        }

        // Refund wallet amount if deducted........
        if(isset($order['wallet_amount']) && ((int) $order['wallet_amount']) >= 0){
            $req = array(
                'customer_id'=>$order['customer_id'],
                'order_id'=>$order_id,
                'amount'=>$order['wallet_amount'],
                'type'=>'REFUND',
                'entry'=>'credit',
                'description'=>'Refund for Order ID: '.$order_id,
            );

            $walletTransactionResponse = $this->utilities->walletTransaction($req,$order->toArray());
            
            if($walletTransactionResponse['status'] != 200){
                return Response::json($walletTransactionResponse,$walletTransactionResponse['status']);
            }

            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'Refund Successful'
                ),200

            );
        }
        else{
            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'No wallet amount has been deducted for the transaction'
                ),200

            );
        }

    }

    public function hitURLAfterDelay($url, $delay = 0, $label = 'label', $priority = 0){

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }

        $payload = array('url'=>$url,'delay'=>$delay,'priority'=>$priority,'label' => $label);

        $route  = 'outbound';
        $result  = $this->sidekiq->sendToQueue($payload,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
        }

    }

    public function getSeconds($delay){

        if ($delay instanceof DateTime){

            return max(0, $delay->getTimestamp() - $this->getTime());

        }elseif ($delay instanceof \Carbon\Carbon){

            return max(0, $delay->timestamp - $this->getTime());

        }elseif(isset($delay['date'])){

            $time = strtotime($delay['date']) - $this->getTime();

            return $time;

        }else{

            $delay = strtotime($delay) - time();
        }

        return (int) $delay;
    }

    public function getManualOrderDetail($data){

        $data['ratecard_remarks']  = (isset($data['remarks'])) ? $data['remarks'] : "";
        $data['duration'] = (isset($data['duration'])) ? $data['duration'] : "";
        $data['duration_type'] = (isset($data['duration_type'])) ? $data['duration_type'] : "";

        $data['service_duration'] = $data['validity']." ".ucwords($data['validity_type']);

        if($data['validity'] > 1){

            $data['service_duration'] = $data['validity']." ".ucwords($data['validity_type'])."s";
        }

        if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '' && $data['preferred_starting_date']  != '-'){

            $preferred_starting_date = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']));
            $data['start_date'] = $preferred_starting_date;
            $data['preferred_starting_date'] = $preferred_starting_date;
        }

        if(isset($data['preferred_payment_date']) && $data['preferred_payment_date']  != '' && $data['preferred_payment_date']  != '-'){

            $preferred_payment_date = date('Y-m-d 00:00:00', strtotime($data['preferred_payment_date']));
            $data['start_date'] = $preferred_payment_date;
            $data['preferred_payment_date'] = $preferred_payment_date;
        }

        $data['amount_finder'] = $data['amount'];
        $data['amount_customer'] = $data['amount'];
        $data['batch_time'] = "";
        $data['offer_id'] = false;

        $set_vertical_type = array(
            'healthytiffintrail'=>'tiffin',
            'healthytiffinmembership'=>'tiffin',
            'memberships'=>'workout',
            'booktrials'=>'workout',
            'workout-session'=>'workout',
            '3daystrial'=>'workout',
            'vip_booktrials'=>'workout',
            'events'=>'event',
            'diet_plan'=>'diet_plan'
        );

        $set_membership_duration_type = array(
            'healthytiffintrail'=>'trial',
            'healthytiffinmembership'=>'short_term_membership',
            'memberships'=>'short_term_membership',
            'booktrials'=>'trial',
            'workout-session'=>'workout_session',
            '3daystrial'=>'trial',
            'vip_booktrials'=>'vip_trial',
            'events'=>'event',
            'diet_plan'=>'short_term_membership'
        );

        (isset($data['type']) && isset($set_vertical_type[$data['type']])) ? $data['vertical_type'] = $set_vertical_type[$data['type']] : null;

        (isset($data['type']) && isset($set_membership_duration_type[$data['type']])) ? $data['membership_duration_type'] = $set_membership_duration_type[$data['type']] : null;

        (isset($data['duration_day']) && $data['duration_day'] >= 30 && $data['duration_day'] <= 90) ? $data['membership_duration_type'] = 'short_term_membership' : null;

        (isset($data['duration_day']) && $data['duration_day'] > 90 ) ? $data['membership_duration_type'] = 'long_term_membership' : null;
        $data['secondary_payment_mode'] = 'payment_gateway_tentative';
        $data['finder_id'] = (int)$data['finder_id'];
        $data['service_id'] = null;
        
        $data['service_name_purchase'] =  $data['service_name'];
        $data['service_duration_purchase'] =  $data['service_duration'];
        $data['status'] =  '0';
        $data['payment_mode'] =  'paymentgateway';
        $data['source_of_membership'] =  'real time';

        return array('status' => 200,'data' =>$data);

    }

    public function getRatecardDetail($data){

        $ratecard = Ratecard::find((int)$data['ratecard_id']);

        if(!$ratecard){
            return array('status' => 404,'message' =>'Ratecard does not exists');
        }

        $ratecard = $ratecard->toArray();

        $data['service_duration'] = $this->getServiceDuration($ratecard);

        $data['ratecard_remarks']  = (isset($ratecard['remarks'])) ? $ratecard['remarks'] : "";
        $data['duration'] = (isset($ratecard['duration'])) ? $ratecard['duration'] : "";
        $data['duration_type'] = (isset($ratecard['duration_type'])) ? $ratecard['duration_type'] : "";

        if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '' && $data['preferred_starting_date']  != '-'){

            $preferred_starting_date = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']));
            $data['start_date'] = $preferred_starting_date;
            $data['preferred_starting_date'] = $preferred_starting_date;
        }

        if(isset($data['preferred_payment_date']) && $data['preferred_payment_date']  != '' && $data['preferred_payment_date']  != '-'){

            $preferred_payment_date = date('Y-m-d 00:00:00', strtotime($data['preferred_payment_date']));
            $data['start_date'] = $preferred_payment_date;
            $data['preferred_payment_date'] = $preferred_payment_date;
        }

        if(isset($ratecard['validity']) && $ratecard['validity'] != ""){

            switch ($ratecard['validity_type']){
                case 'days': 
                    $data['duration_day'] = $duration_day = (int)$ratecard['validity'];break;
                case 'months': 
                    $data['duration_day'] = $duration_day = (int)($ratecard['validity'] * 30) ; break;
                case 'year': 
                    $data['duration_day'] = $duration_day = (int)($ratecard['validity'] * 30 * 12); break;
                default : $data['duration_day'] = $duration_day =  $ratecard['validity']; break;
            }

            if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '' && $data['preferred_starting_date']  != '-'){
                $data['end_date'] = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']."+ ".($duration_day-1)." days"));
            }
        }

        if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
            $data['amount_finder'] = $ratecard['special_price'];
        }else{
            $data['amount_finder'] = $ratecard['price'];
        }

        $data['offer_id'] = false;

        $offer = Offer::where('ratecard_id',$ratecard['_id'])
                ->where('hidden', false)
                ->orderBy('order', 'asc')
                ->where('start_date','<=',new DateTime(date("d-m-Y 00:00:00")))
                ->where('end_date','>=',new DateTime(date("d-m-Y 00:00:00")))
                ->first();

        if($offer){

            $ratecard['offer_convinience_fee'] = $data['offer_convinience_fee'] = true;
            $data['amount_finder'] = $offer->price;
            $data['offer_id'] = $offer->_id;

            if(isset($offer->remarks) && $offer->remarks != ""){
                $data['ratecard_remarks'] = $offer->remarks;
            }
        }

        $data['amount'] = $data['amount_finder'];

        

       /* $corporate_discount_percent = $this->utilities->getCustomerDiscount();
        $data['customer_discount_amount'] = intval($data['amount'] * ($corporate_discount_percent/100));
        $data['amount'] = $data['amount'] - $data['customer_discount_amount'];*/

        $medical_detail                     =   (isset($data['medical_detail']) && $data['medical_detail'] != '') ? $data['medical_detail'] : "";
        $medication_detail                  =   (isset($data['medication_detail']) && $data['medication_detail'] != '') ? $data['medication_detail'] : "";

        if($medical_detail != "" && $medication_detail != ""){

            $customer_info = new CustomerInfo();
            $response = $customer_info->addHealthInfo($data);
        }

        if(isset($data['schedule_date']) && $data['schedule_date'] != ""){

            $schedule_date = date('Y-m-d 00:00:00', strtotime($data['schedule_date']));
            array_set($data, 'start_date', $schedule_date);

            array_set($data, 'end_date', $schedule_date);

            $data['membership_duration_type'] = 'workout_session';
        }

        if(isset($data['schedule_slot']) && $data['schedule_slot'] != ""){
            
            $schedule_slot = explode("-", $data['schedule_slot']);

            $data['start_time'] = trim($schedule_slot[0]);
            if(count($schedule_slot) == 1){
                $data['end_time'] = date('g:i a', strtotime('+1 hour', strtotime($schedule_slot[0])));
                $data['schedule_slot'] = $schedule_slot[0].'-'.$data['end_time'];
            }else{
                $data['end_time']= trim($schedule_slot[1]);
            }
        }

        $batch = array();

        $data['batch_time'] = "";

        if(isset($data['batch']) && $data['batch'] != ""){
            
            if(is_array($data['batch'])){
                $data['batch'] = $data['batch'];
            }else{
                $data['batch'] = json_decode($data['batch'],true);
            }

            foreach ($data['batch'] as $key => $value) {

                if(isset($value['slots']['start_time']) && $value['slots']['start_time'] != ""){

                    $batch[$key]['weekday'] = $value['weekday'];
                    $batch[$key]['slots'][0] = $value['slots'];
                }

                if(isset($value['slots'][0]['start_time']) && $value['slots'][0]['start_time'] != ""){
                    $batch[$key] = $value;
                }
            }

            foreach ($batch as $key => $value) {

                if(isset($value['slots'][0]['start_time']) && $value['slots'][0]['start_time'] != ""){
                    $data['batch_time'] = strtoupper($value['slots'][0]['start_time']);
                    break;
                }
            }

            $data['batch'] = $batch;
        }

        $set_vertical_type = array(
            'healthytiffintrail'=>'tiffin',
            'healthytiffinmembership'=>'tiffin',
            'memberships'=>'workout',
            'booktrials'=>'workout',
            'workout-session'=>'workout',
            '3daystrial'=>'workout',
            'vip_booktrials'=>'workout',
            'events'=>'event',
            'diet_plan'=>'diet_plan'
        );

        $set_membership_duration_type = array(
            'healthytiffintrail'=>'trial',
            'healthytiffinmembership'=>'short_term_membership',
            'memberships'=>'short_term_membership',
            'booktrials'=>'trial',
            'workout-session'=>'workout_session',
            '3daystrial'=>'trial',
            'vip_booktrials'=>'vip_trial',
            'events'=>'event',
            'diet_plan'=>'short_term_membership'
        );

        (isset($data['type']) && isset($set_vertical_type[$data['type']])) ? $data['vertical_type'] = $set_vertical_type[$data['type']] : null;

        if(isset($data['finder_category_id'])){

            switch ($data['finder_category_id']) {
                case 41 : $data['vertical_type'] = 'trainer';break;
                case 45 : $data['vertical_type'] = 'package';break;
                default: break;
            }

        }

       (isset($data['type']) && isset($set_membership_duration_type[$data['type']])) ? $data['membership_duration_type'] = $set_membership_duration_type[$data['type']] : null;

        (isset($data['duration_day']) && $data['duration_day'] >= 30 && $data['duration_day'] <= 90) ? $data['membership_duration_type'] = 'short_term_membership' : null;

        (isset($data['duration_day']) && $data['duration_day'] > 90 ) ? $data['membership_duration_type'] = 'long_term_membership' : null;
        $data['secondary_payment_mode'] = 'payment_gateway_tentative';
        $data['finder_id'] = (int)$ratecard['finder_id'];
        $data['service_id'] = (int)$ratecard['service_id'];
        
        $service = Service::select('name')->find($data['service_id']);
        $data['service_name_purchase'] =  $service['name'];
        $data['service_duration_purchase'] =  $data['service_duration'];
        $data['status'] =  '0';
        $data['payment_mode'] =  'paymentgateway';
        $data['source_of_membership'] =  'real time';

        if($this->convinienceFeeFlag() && $this->utilities->isConvinienceFeeApplicable($ratecard)){

            $data['ratecard_flags'] = isset($ratecard['flags']) ? $ratecard['flags'] : array();
        }

        return array('status' => 200,'data' =>$data);

    }

    public function getServiceDetail($service_id){

        $data = array();

        $service = Service::active()->find((int)$service_id);

        if(!$service){
            return array('status' => 404,'message' =>'Service does not exists');
        }

        $service = $service->toArray();

        $data['finder_address'] = (isset($service['address']) && $service['address'] != "") ? $service['address'] : "-";
        $data['service_name'] = ucwords($service['name']);
        $data['meal_contents'] = $this->stripTags($service['short_description']);
        (isset($service['diet_inclusive'])) ? $data['diet_inclusive'] = $service['diet_inclusive'] : null;
        $data['finder_address'] = (isset($service['address']) && $service['address'] != "") ? $service['address'] : "-";
        
        return array('status' => 200,'data' =>$data);

    }


    public function stripTags($string){
        return ucwords(str_replace("&nbsp;","",strip_tags($string)));
    }

    public function getFinderDetail($finder_id){

        $data = array();

        $finder                            =   Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->find((int)$finder_id);

        if(!$finder){
            return array('status' => 404,'message' =>'Vendor does not exists');
        }

        $finder = $finder->toArray();

        $finder_city                       =    (isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
        $finder_city_slug                  =    (isset($finder['city']['slug']) && $finder['city']['slug'] != '') ? $finder['city']['slug'] : "";
        $finder_location                   =    (isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
        $finder_address                    =    (isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $this->stripTags($finder['contact']['address']) : "";
        $finder_vcc_email                  =    (isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
        $finder_vcc_mobile                 =    (isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
        $finder_poc_for_customer_name       =   (isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
        $finder_poc_for_customer_no        =    (isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
        $show_location_flag                =    (count($finder['locationtags']) > 1) ? false : true;
        $share_customer_no                 =    (isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;
        $finder_lon                        =    (isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
        $finder_lat                        =    (isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
        $finder_category_id                =    (isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";
        $finder_slug                       =    (isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
        $finder_name                       =    (isset($finder['title']) && $finder['title'] != '') ? ucwords($finder['title']) : "";
        $finder_location_id                =    (isset($finder['location']['_id']) && $finder['location']['_id'] != '') ? $finder['location']['_id'] : "";
        $city_id                           =    $finder['city_id'];
        $finder_category                       =    (isset($finder['category']['name']) && $finder['category']['name'] != '') ? $finder['category']['name'] : "";
        $finder_category_slug                  =    (isset($finder['category']['slug']) && $finder['category']['slug'] != '') ? $finder['category']['slug'] : "";
        $finder_flags                       =   isset($finder['flags'])  ? $finder['flags'] : new stdClass();
        $data['finder_city'] =  trim($finder_city);
        $data['finder_location'] =  ucwords(trim($finder_location));
        $data['finder_address'] =  trim($finder_address);
        $data['finder_vcc_email'] =  trim($finder_vcc_email);
        $data['finder_vcc_mobile'] =  trim($finder_vcc_mobile);
        $data['finder_poc_for_customer_name'] =  trim($finder_poc_for_customer_name);
        $data['finder_poc_for_customer_no'] =  trim($finder_poc_for_customer_no);
        $data['show_location_flag'] =  $show_location_flag;
        $data['share_customer_no'] =  $share_customer_no;
        $data['finder_lon'] =  $finder_lon;
        $data['finder_lat'] =  $finder_lat;
        $data['finder_branch'] =  trim($finder_location);
        $data['finder_category_id'] =  $finder_category_id;
        $data['finder_slug'] =  $finder_slug;
        $data['finder_name'] =  ucwords($finder_name);
        $data['finder_location_id'] =  $finder_location_id;
        $data['finder_id'] =  $finder_id;
        $data['city_id'] =  $city_id;
        $data['city_name'] = $finder_city;
        $data['city_slug'] = $finder_city_slug;
        $data['category_name'] = $finder_category;
        $data['category_slug'] = $finder_category_slug;
        $data['finder_flags'] = $finder_flags;
        

        return array('status' => 200,'data' =>$data);
    }

    public function getServiceDuration($ratecard){

        $duration_day = 1;

        if(isset($ratecard['validity']) && $ratecard['validity'] != '' && $ratecard['validity'] != 0){

            $duration_day = $ratecard['validity'];
        }

        if(isset($ratecard['validity']) && $ratecard['validity'] != '' && $ratecard['validity_type'] == "days"){

            $ratecard['validity_type'] = 'Days';

            if(($ratecard['validity'] % 30) == 0){

                $month = ($ratecard['validity']/30);

                if($month == 1){
                    $ratecard['validity_type'] = 'Month';
                    $ratecard['validity'] = $month;
                }

                if($month > 1 && $month < 12){
                    $ratecard['validity_type'] = 'Months';
                    $ratecard['validity'] = $month;
                }

                if($month == 12){
                    $ratecard['validity_type'] = 'Year';
                    $ratecard['validity'] = 1;
                }

            }
              
        }

        if(isset($ratecard['validity']) && $ratecard['validity'] != '' && $ratecard['validity_type'] == "months"){

            $ratecard['validity_type'] = 'Months';

            if($ratecard['validity'] == 1){
                $ratecard['validity_type'] = 'Month';
            }

            if(($ratecard['validity'] % 12) == 0){

                $year = ($ratecard['validity']/12);

                if($year == 1){
                    $ratecard['validity_type'] = 'Year';
                    $ratecard['validity'] = $year;
                }

                if($year > 1){
                    $ratecard['validity_type'] = 'Years';
                    $ratecard['validity'] = $year;
                }
            }
              
        }

        if(isset($ratecard['validity']) && $ratecard['validity'] != '' && $ratecard['validity_type'] == "year"){

            $year = $ratecard['validity'];

            if($year == 1){
                $ratecard['validity_type'] = 'Year';
            }

            if($year > 1){
                $ratecard['validity_type'] = 'Years';
            }
              
        }

        $service_duration = "";

        if($ratecard['duration'] > 0){
            $service_duration .= $ratecard['duration'] ." ".ucwords($ratecard['duration_type']);
        }
        if($ratecard['duration'] > 0 && $ratecard['validity'] > 0){
            $service_duration .= " - ";
        }
        if($ratecard['validity'] > 0){
            $service_duration .=  $ratecard['validity'] ." ".ucwords($ratecard['validity_type']);
        }

        ($service_duration == "") ? $service_duration = "-" : null;

        return $service_duration;
    }


    public function getHash($data){

        $env = (isset($data['env']) && $data['env'] == 1) ? "stage" : "production";

        $data['service_name'] = trim($data['service_name']);
        $data['finder_name'] = trim($data['finder_name']);

        $service_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", '', $data['service_name']);
        $finder_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", '', $data['finder_name']);

        $key = 'gtKFFx';
        $salt = 'eCwWELxi';

        if($env == "production"){
            $key = 'l80gyM';
            $salt = 'QBl78dtK';
        }

        $txnid = $data['txnid'];
        $amount = $data['amount'];
        $productinfo = $data['productinfo'] = $service_name." - ".$finder_name;
        $firstname = $data['customer_name'];
        $email = $data['customer_email'];
        $udf1 = "";
        $udf2 = "";
        $udf3 = "";
        $udf4 = "";
        $udf5 = "";

        $payhash_str = $key.'|'.$txnid.'|'.$amount.'|'.$productinfo.'|'.$firstname.'|'.$email.'|'.$udf1.'|'.$udf2.'|'.$udf3.'|'.$udf4.'|'.$udf5.'||||||'.$salt;
        
        // Log::info($payhash_str);

        $data['payment_hash'] = hash('sha512', $payhash_str);

        $verify_str = $salt.'||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;

        $data['verify_hash'] = hash('sha512', $verify_str);

        $cmnPaymentRelatedDetailsForMobileSdk1              =   'payment_related_details_for_mobile_sdk';
        $detailsForMobileSdk_str1                           =   $key  . '|' . $cmnPaymentRelatedDetailsForMobileSdk1 . '|default|' . $salt ;
        $detailsForMobileSdk1                               =   hash('sha512', $detailsForMobileSdk_str1);
        $data['payment_related_details_for_mobile_sdk_hash'] =   $detailsForMobileSdk1;
        
        return $data;
    }

    public function pg(){

        $data = Input::json()->all();

        $rules = [
            'order_id',
            'pg_type'
        ];

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }

        $order_id = $data['order_id'];

        $order = Order::find((int) $order_id);

        if(!$order){
            return Response::json(array('status' => 404,'message' => 'Order not found'),404);
        }

        if($order->status == "1"){
            return Response::json(array('status' => 404,'message' => 'Order already success'),404);
        }

        $order->pg_type = $data['pg_type'];
        $order->pg_date = date('Y-m-d H:i:s',time());
        $order->update();

        $response   =   array(
            'status' => 200,
            'message' => "PG Captured Sucessfully"
        );

        return Response::json($response,$response['status']);
    }


    public  function sendCommunication($job,$data){

        $job->delete();

        try {

            $order_id = (int)$data['order_id'];

            $order = Order::find($order_id);

            if(isset($order['type']) && $order['type'] == 'wallet'){
                
                $this->customersms->pledge($order->toArray());
                
                return "success";
            
            }

            $this->utilities->removeOrderCommunication($order);

            $nineAM = strtotime(date('Y-m-d 09:00:00'));
            $ninePM = strtotime(date('Y-m-d 21:00:00'));
            $now = time();

            if($now <= $nineAM || $now >= $ninePM){
                $now = strtotime(date('Y-m-d 11:00:00'));
            }

            

            $order->customerSmsSendPaymentLinkAfter3Days = $this->customersms->sendPaymentLinkAfter3Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+3 days",$now)));
            $order->customerSmsSendPaymentLinkAfter7Days = $this->customersms->sendPaymentLinkAfter7Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+7 days",$now)));
            //$order->customerSmsSendPaymentLinkAfter15Days = $this->customersms->sendPaymentLinkAfter15Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+15 days",$now)));
            //$order->customerSmsSendPaymentLinkAfter30Days = $this->customersms->sendPaymentLinkAfter30Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+30 days",$now)));
            $order->customerSmsSendPaymentLinkAfter45Days = $this->customersms->sendPaymentLinkAfter45Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+45 days",$now)));

            //if(isset($order['reg_id']) && $order['reg_id'] != "" && isset($order['device_type']) && $order['device_type'] != ""){
                $order->customerNotificationSendPaymentLinkAfter3Days = $this->customernotification->sendPaymentLinkAfter3Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+3 days",$now)));
                $order->customerNotificationSendPaymentLinkAfter7Days = $this->customernotification->sendPaymentLinkAfter7Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+7 days",$now)));
                /*$order->customerNotificationSendPaymentLinkAfter15Days = $this->customernotification->sendPaymentLinkAfter15Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+15 days",$now)));
                $order->customerNotificationSendPaymentLinkAfter30Days = $this->customernotification->sendPaymentLinkAfter30Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+30 days",$now)));*/
                $order->customerNotificationSendPaymentLinkAfter45Days = $this->customernotification->sendPaymentLinkAfter45Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+45 days",$now)));
           // }

            $url = Config::get('app.url')."/addwallet?customer_id=".$order["customer_id"]."&order_id=".$order_id;

            $order->customerWalletSendPaymentLinkAfter15Days = $this->hitURLAfterDelay($url."&time=LPlus15", date('Y-m-d H:i:s', strtotime("+15 days",$now)));
            $order->customerWalletSendPaymentLinkAfter30Days = $this->hitURLAfterDelay($url."&time=LPlus30", date('Y-m-d H:i:s', strtotime("+30 days",$now)));

            $order->notification_status = 'abandon_cart_yes';

            $booktrial = Booktrial::where('customer_id',$order['customer_id'])->where('finder_id',(int)$order['finder_id'])->orderBy('desc','_id')->first();

            if($booktrial){

                $order->previous_booktrial_id = (int)$booktrial->_id;
            }

            $order->update();

            return "success";

            
        } catch (Exception $e) {

            Log::error($e);

            return "error";
            
        }

    }

    public function getOrderDetails($order){

        //$order = Order::find((int)$order_id);

        $referal_order = [];

        $referal_order['order_id'] =  $order['order_id'];
        $referal_order['city_id'] =  $order['city_id'];
        $referal_order['city_name'] =  (isset($order['city_name']) && $order['city_name']!="") ?  $order['city_name'] : "";
        $referal_order['city_slug'] = (isset($order['city_slug']) && $order['city_slug']!="") ?  $order['city_slug'] : "";
        $referal_order['finder_id'] =  $order['finder_id'];
        $referal_order['finder_name'] =  $order['finder_name'];
        $referal_order['finder_slug'] =  $order['finder_slug'];
        $referal_order['ratecard_id'] =  (isset($order['ratecard_id']) && $order['ratecard_id'] != '') ? $order['ratecard_id'] : "";
        $referal_order['service_id'] =  $order['service_id'];
        $referal_order['service_name'] =  $order['service_name'];
        $referal_order['service_duration'] =  $order['service_duration'];
        $referal_order['city_id'] =  $order['city_id'];
        $referal_order['city_name'] =  $order['city_name'];
        $referal_order['city_slug'] = $order['city_slug'];
        $referal_order['category_id'] =  $order['finder_category_id'];
        $referal_order['category_name'] =  (isset($order['category_name']) && $order['category_name']!="") ?  $order['category_name'] : "";
        $referal_order['category_slug'] = (isset($order['category_slug']) && $order['category_slug']!="") ?  $order['category_slug'] : "";

        return $referal_order;


    }

    public function generaterDietPlanOrderOnline($order_id){

        $order_id = (int)$order_id;

        $order = Order::find($order_id);

        if(!$order){
            return Response::json(array('status' => 404,'message' => 'Order not found'),404);
        }

        if($order->status == "1" && !isset($order->diet_plan_order_id) && isset($order->diet_plan_ratecard_id) && $order->diet_plan_ratecard_id != "" && $order->diet_plan_ratecard_id != 0){


            if(isset($order->city_id)){

                $city = City::find((int)$order->city_id,['_id','name','slug']);

                $order->update(['city_name'=>$city->name,'city_slug'=>$city->slug]);
            }

            if(isset($order->finder_category_id)){

                $category = Findercategory::find((int)$order->finder_category_id,['_id','name','slug']);

                $order->update(['category_name'=>$category->name,'category_slug'=>$category->slug]);
            }

            $generaterDietPlanOrder = $this->generaterDietPlanOrder($order->toArray());

            if($generaterDietPlanOrder['status'] != 200){
                return Response::json($generaterDietPlanOrder,$generaterDietPlanOrder['status']);
            }

            $order->diet_plan_order_id = $generaterDietPlanOrder['order_id'];
            $order->update();

            return Response::json(array('status' => 200,'message' => 'Success','diet_plan_order_id'=>$generaterDietPlanOrder['order_id']),200);
        }

        return Response::json(array('status' => 404,'message' => 'Diet plan not created'),404);
    }

    public function generaterDietPlanOrder($order){

        $data = [];

        $data['type'] = "diet_plan";
        $data['ratecard_id'] = $order['diet_plan_ratecard_id'];
        $data['referal_order_id'] = $order['_id'];
        $data['customer_name'] = $order['customer_name'];
        $data['customer_email'] = $order['customer_email'];
        $data['customer_phone'] = $order['customer_phone'];
        $data['customer_source'] = $order['customer_source'];
        $data['city_id'] =  $order['city_id'];
        $data['city_name'] =  (isset($order['city_name']) && $order['city_name']!="") ?  $order['city_name'] : "";
        $data['city_slug'] = (isset($order['city_slug']) && $order['city_slug']!="") ?  $order['city_slug'] : "";
        $data['offering_type'] = (isset($order['offering_type']) && $order['offering_type']!="") ? $order['offering_type']: "cross_sell";
        $data['renewal'] = "no";
        $data['final_assessment'] = "no";

        $data['referal_order'] = $this->getOrderDetails($order);

        array_set($data, 'status', '1');
        array_set($data, 'order_action', 'bought');
        array_set($data, 'success_date', date('Y-m-d H:i:s',time()));

        $customerDetail = $this->getCustomerDetail($data);

        if($customerDetail['status'] != 200){
            return $customerDetail;
        }

        $data = array_merge($data,$customerDetail['data']); 
          
        $ratecardDetail = $this->getRatecardDetail($data);

        if($ratecardDetail['status'] != 200){
            return $ratecardDetail;
        }

        $data = array_merge($data,$ratecardDetail['data']);

        $diet_inclusive = false;
        if(isset($order['diet_inclusive']) && $order['diet_inclusive']){
            $data['amount'] = $data['amount_finder'] = $data['amount_finder']/2;
            $diet_inclusive = true;
        }

        $ratecard_id = (int) $data['ratecard_id'];
        $finder_id = (int) $data['finder_id'];
        $service_id = (int) $data['service_id'];

        $finderDetail = $this->getFinderDetail($finder_id);

        if($finderDetail['status'] != 200){
            return $finderDetail;
        }

        $data = array_merge($data,$finderDetail['data']);

        $serviceDetail = $this->getServiceDetail($service_id);

        if($serviceDetail['status'] != 200){
            return $serviceDetail;
        }

        $data = array_merge($data,$serviceDetail['data']);

        $order_id = $data['_id'] = $data['order_id'] = Order::max('_id') + 1;

        $data = $this->unsetData($data);

        $data['status'] = "1";
        $data['order_action'] = "bought";
        $data['success_date'] = date('Y-m-d H:i:s',time());

        $order = new Order($data);
        $order->_id = $order_id;
        $order->save();


        if($diet_inclusive){

            $emailData = $order->toArray();
            $dietRatecard = Ratecard::find($data['ratecard_id']);
            $dietService = Service::find($dietRatecard['service_id']);
            $emailData['service_name'] = $dietService['name'];

            $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
            $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
        }


        //$redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),Config::get('app.queue'));
        //$order->update(array('redis_id'=>$redisid));

        return array('order_id'=>$order_id,'status'=>200,'message'=>'Diet Plan Order Created Sucessfully');
    }

    public function getCategoryImage($category = "no_category"){

        $category_array['gyms'] = array('personal-trainers'=>'http://email.fitternity.com/229/personal.jpg','sport-nutrition-supliment-stores'=>'http://email.fitternity.com/229/nutrition.jpg','yoga'=>'http://email.fitternity.com/229/yoga.jpg');
        $category_array['zumba'] = array('gyms'=>'http://email.fitternity.com/229/gym.jpg','dance'=>'http://email.fitternity.com/229/dance.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg');
        $category_array['yoga'] = array('pilates'=>'http://email.fitternity.com/229/pilates.jpg','personal-trainers'=>'http://email.fitternity.com/229/personal.jpg','marathon-training'=>'http://email.fitternity.com/229/marathon.jpg');
        $category_array['pilates'] = array('yoga'=>'http://email.fitternity.com/229/yoga.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg','marathon-training'=>'http://email.fitternity.com/229/marathon.jpg');
        $category_array['cross-functional-training'] = array('sport-nutrition-supliment-stores'=>'http://email.fitternity.com/229/nutrition.jpg','personal-trainers'=>'http://email.fitternity.com/229/personal.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg');
        $category_array['crossfit'] = array('yoga'=>'http://email.fitternity.com/229/yoga.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg','sport-nutrition-supliment-stores'=>'http://email.fitternity.com/229/nutrition.jpg');
        $category_array['dance'] = array('zumba'=>'http://email.fitternity.com/229/zumba.jpg','mma-and-kick-boxing'=>'http://email.fitternity.com/229/mma&kickboxing.jpg','spinning-and-indoor-cycling'=>'http://email.fitternity.com/229/spinning.jpg');
        $category_array['mma-and-kick-boxing'] = array('personal-trainers'=>'http://email.fitternity.com/229/personal.jpg','healthy-tiffins'=>'http://email.fitternity.com/229/healthy-tiffin.jpg','cross-functional-training'=>'http://email.fitternity.com/229/cross-functional.jpg');
        $category_array['spinning-and-indoor-cycling'] = array('gyms'=>'http://email.fitternity.com/229/gym.jpg','dietitians-and-nutritionists'=>'http://email.fitternity.com/229/dietitians.jpg','yoga'=>'http://email.fitternity.com/229/yoga.jpg');
        $category_array['marathon-training'] = array('dietitians-and-nutritionists'=>'http://email.fitternity.com/229/dietitians.jpg','yoga'=>'http://email.fitternity.com/229/yoga.jpg','cross-functional-training'=>'http://email.fitternity.com/229/cross-functional.jpg');

        if(array_key_exists($category,$category_array)){
            return $category_array[$category];
        }else{
            return array('gyms'=>'http://email.fitternity.com/229/gym.jpg','dance'=>'http://email.fitternity.com/229/dance.jpg','yoga'=>'http://email.fitternity.com/229/yoga.jpg');
        }

    }

    public function addWallet(){

        $data = $_GET;

        $rules = array(
            'customer_id'=>'required',
            'time'=>'required|in:LPlus15,LPlus30,F1Plus15,PurchaseFirst,RLMinus7,RLMinus1,Nplus2',
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }

        $time = $data['time'];
        $customer = Customer::find((int)$data['customer_id']);
        $top_up = false;
        $wallet_balance = 0;
        $customer_id = (int)$data['customer_id'];

        if($customer){

            $orderTime = ['LPlus15','LPlus30','PurchaseFirst','RLMinus7','RLMinus1'];
            $trialTime = ['F1Plus15','Nplus2'];

            $amountArray = [
                "LPlus15" => 150,
                "LPlus30" => 150,
                "F1Plus15" => 150,
                "PurchaseFirst" => 150,
                "RLMinus7" => 150,
                "RLMinus1" => 150,
                "Nplus2" => 200,
            ];

            if(isset($customer->demonetisation)){

                $wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

            }else{

                $customerWallet = Customerwallet::where('customer_id',$customer_id)->orderBy('_id', 'desc')->first();

                if(isset($customerWallet) && isset($customerWallet->balance_fitcash_plus)){
                    $wallet_balance = $customerWallet->balance_fitcash_plus;
                }

            }

            $amount = $amountArray[$time];

            Log::info('wallet_balance - '.$wallet_balance);

            if($wallet_balance > 0){

                $amount = 0;

                if($amountArray[$time] > $wallet_balance){
                    $amount = $amountArray[$time] - $wallet_balance;
                }

            }

            if($amount > 0){

                $req = [];

                $req['customer_id'] = $data['customer_id'];
                $req['amount'] = $amount;

                $wallet_balance +=  $amount;

                $req['entry'] = "credit";
                $req['type'] = "FITCASHPLUS";
                $req['amount_fitcash_plus'] = $amount;
                $req['description'] = "Added FitCash+ as Fitternity Bonus, Expires On : ".date('d-m-Y',time()+(86400*60));
                $req["validity"] = time()+(86400*60);
                $req['for'] = $time;

                if($time == "Nplus2"){
                    $req['description'] = "Added FitCash+ as Fitternity Bonus, Expires On : ".date('d-m-Y',time()+(86400*7));
                    $req["validity"] = time()+(86400*7);
                }

                $walletTransactionResponse = $this->utilities->walletTransaction($req);

                if($walletTransactionResponse['status'] == 200){

                    $customer->update(["added_fitcash_plus" => time()]);

                    $top_up = true;
                }

            }

            if(isset($data['order_id'])){
                
                $req['order_id'] = (int)$data['order_id'];

                $transaction = Order::find((int)(int)$data['order_id']);

                $dates = array('followup_date','last_called_date','preferred_starting_date', 'called_at','subscription_start','start_date','start_date_starttime','end_date', 'order_confirmation_customer');

                foreach ($dates as $key => $value){
                    if(isset($transaction[$value]) && $transaction[$value]==''){
                        $transaction->unset($value);
                    }
                }
            }

            if(isset($data['booktrial_id'])){
                
                $req['booktrial_id'] = (int)$data['booktrial_id'];

                $transaction = Booktrial::find((int)(int)$data['booktrial_id']);

                $dates = array('start_date', 'start_date_starttime', 'schedule_date', 'schedule_date_time', 'followup_date', 'followup_date_time','missedcall_date','customofferorder_expiry_date','auto_followup_date');

                foreach ($dates as $key => $value){
                    if(isset($transaction[$value]) && $transaction[$value]==''){
                        $transaction->unset($value);
                    }
                }
            }

            if($transaction && $wallet_balance > 0){

                $transaction = $transaction->toArray();
                
                $transaction['wallet_balance'] = $wallet_balance;
                $transaction['top_up'] = $top_up;
                $transaction['top_up_amount'] = $amount;

                switch ($time) {
                    case 'LPlus15':
                        $this->customersms->sendPaymentLinkAfter15Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendPaymentLinkAfter15Days($transaction,0);
                        }
                        break;
                    case 'LPlus30':
                        $this->customersms->sendPaymentLinkAfter30Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendPaymentLinkAfter30Days($transaction,0);
                        }
                        break;
                    case 'RLMinus7':
                        $this->customersms->sendRenewalPaymentLinkBefore7Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendRenewalPaymentLinkBefore7Days($transaction,0);
                        }
                        break;
                    case 'RLMinus1':
                        $this->customersms->sendRenewalPaymentLinkBefore1Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendRenewalPaymentLinkBefore1Days($transaction,0);
                        }
                        break;
                    case 'PurchaseFirst':
                        $this->customersms->purchaseFirst($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->purchaseFirst($transaction,0);
                        }
                        break;
                    case 'F1Plus15':
                        $this->customersms->postTrialFollowup1After15Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->postTrialFollowup1After15Days($transaction,0);
                        }
                        break;
                    case 'Nplus2':

                        $sms_data = [];

                        $sms_data['customer_phone'] = $transaction['customer_phone'];

                        $sms_data['message'] = "Hi ".ucwords($transaction['customer_name']).". Hope you liked your trial workout at".ucwords($transaction['finder_name']).". You have Rs. ".$transaction['wallet_balance']." in your Fittenrity wallet. Use it now to buy the membership at lowest price with assured complimentary rewards like cool fitness merchandise and Diet Plan. ".$transaction['vendor_link'].".  Valid for 7 days. For quick assistance call Fitternity on ".Config::get('app.contact_us_customer_number');

                        $this->customersms->custom($sms_data);

                        break;
                    default : break;
                }

                return Response::json(array('status' => 200,'message' => 'Success'),200);
            }

            return Response::json(array('status' => 401,'message' => 'Error'),401);

        }

        return Response::json(array('status' => 402,'message' => 'Error'),402);
        
    }

    public function deleteCommunicationOfSuccess(){

        $allOrders = Order::active()
                    ->whereIn('type',['memberships','healthytiffinmembership'])
                    ->where('created_at', '>=', new \DateTime( date("2017-04-15 00:00:00")))
                    ->where('deleteCommunication','exists',false)
                    ->orderBy('_id','desc')
                    ->get();

        if(count($allOrders) > 0){

            foreach ($allOrders as $order) {

                $order->update(['deleteCommunication' => true]);

                $this->utilities->deleteCommunication($order);
                $this->utilities->setRedundant($order);
            }

            return "success";
        }

        return "no orders found";
    
    }

    public function sendMissedOrderCommunication($orderId){
        try{
            Log::info('Processing');
            $order = Order::findOrFail((int)$orderId);
            $finder = Finder::findOrFail($order->finder_id);
            $abundant_category = array(42,45);
            
            if (filter_var(trim($order['customer_email']), FILTER_VALIDATE_EMAIL) === false){
                    $order->update(['email_not_sent'=>'captureOrderStatus']);
                }else{

                    if(!in_array($finder->category_id, $abundant_category)){
                        $emailData      =   [];
                        $emailData      =   $order->toArray();
                        if($emailData['type'] == 'events'){
                            if(isset($emailData['event_id']) && $emailData['event_id'] != ''){
                                $emailData['event'] = DbEvent::find(intval($emailData['event_id']))->toArray();
                            }
                            if(isset($emailData['ticket_id']) && $emailData['ticket_id'] != ''){
                                $emailData['ticket'] = Ticket::find(intval($emailData['ticket_id']))->toArray();
                            }
                        }

                        //print_pretty($emailData);exit;
                        if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
                            if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                                $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                            }

                        }else{
                            $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                        }
                    }

                    //no email to Healthy Snacks Beverages and Healthy Tiffins
                    if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan'){
                        
                        if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                            if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                                $sndPgMail  =   $this->findermailer->sendPgOrderMail($order->toArray());
                            }
                            
                        }else{
                            $sndPgMail  =   $this->findermailer->sendPgOrderMail($order->toArray());
                        }

                    }
                }

                //SEND payment gateway SMS TO CUSTOMER and vendor
                if(!in_array($finder->category_id, $abundant_category)){
                    $emailData      =   [];
                    $emailData      =   $order->toArray();
                    if($emailData['type'] == 'events'){
                        if(isset($emailData['event_id']) && $emailData['event_id'] != ''){
                            $emailData['event'] = DbEvent::find(intval($emailData['event_id']))->toArray();
                        }
                        if(isset($emailData['ticket_id']) && $emailData['ticket_id'] != ''){
                            $emailData['ticket'] = Ticket::find(intval($emailData['ticket_id']))->toArray();
                        }
                    }
                    
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
                        if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                            $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                        }

                    }else{
                        $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                    }
                }

                //no sms to Healthy Snacks Beverages and Healthy Tiffins
                if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan'){
                    
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                        if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                            $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                        }
                        
                    }else{
                        $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                    }
                    
                }

                if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !in_array($finder->category_id, $abundant_category) && $order->type == "memberships" && $order->type != 'diet_plan'){

                    $preferred_starting_date = $order->preferred_starting_date;
                    $after3days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 3);
                    $after10days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 10);

                    $category_slug = "no_category";

                    if(isset($order->finder_category_id) && $order->finder_category_id != ""){

                        $finder_category_id = $order->finder_category_id;

                        $category = Findercategory::find((int)$finder_category_id);

                        if($category){
                            $category_slug = $category->slug;
                        }
                    }

                    $order_data = $order->toArray();

                    $order_data['category_array'] = $this->getCategoryImage($category_slug);

                    $order->customer_sms_after3days = $this->customersms->orderAfter3Days($order_data,$after3days);
                    $order->customer_email_after10days = $this->customermailer->orderAfter10Days($order_data,$after10days);

                    $order->update();
                    Log::info("Processed: $orderId");
                }
        }catch(Exception $e){
            Log::info($e);
        }
    }

    public function sendOrderMissedEmails(){
        ini_set('max_execution_time', 300000);

		$timestamp = strtotime('2017-06-07 11am');
        $order_ids = Order::where('created_at', '>=', new MongoDate(strtotime('2017-06-07 11am')))->where('created_at', '<=', new MongoDate(strtotime('2017-06-07 5pm')))->where('type', 'memberships')->lists('_id');

        Log::info("Starting");
		foreach($order_ids as $id){
            Log::info($id);
            $this->sendMissedOrderCommunication($id);
        }

        return "Done";
	}


    public function validateMyReward($myreward_id){

        $myreward = Myreward::find((int)$myreward_id);

        if($myreward){

            $created_at = date('Y-m-d H:i:s',strtotime($myreward->created_at));

            $validity_date_unix = strtotime($created_at . ' +'.(int)$myreward->validity_in_days.' days');
            $current_date_unix = time();

            if($validity_date_unix < $current_date_unix){
                return array('status' => 404,'message' => "Validity Is Over");
            }

            if(!isset($myreward->claimed) || $myreward->claimed < $myreward->quantity){
                return array('status' => 200,'message' => "Validate Successfully");
            }else{
                return array('status' => 404,'message' => "Reward Already Claimed");
            }

            return array('status' => 200,'message' => "Validate Successfully");
        }

    }

    public  function autoRegisterCustomer($job,$data){

        $job->delete();

        try {

            $event_customers = $data["event_customers"];

            foreach ($event_customers as $customer_data) {

                autoRegisterCustomer($customer_data);
            }

            return "success";

        } catch (Exception $e) {

            Log::error($e);

            return "error";
            
        }
    }

    function getBookingDetails($data){
        
        $booking_details = [];

        $position = 0;

        $booking_details_data["finder_name_location"] = ['field'=>'STUDIO NAME','value'=>$data['finder_name'].", ".$data['finder_location'],'position'=>$position++];

        if(in_array($data['type'],["booktrials","workout-session","manualautotrial"])){
            $booking_details_data["finder_name_location"] = ['field'=>'SESSION BOOKED AT','value'=>$data['finder_name'].", ".$data['finder_location'],'position'=>$position++];
        }

        $booking_details_data["service_name"] = ['field'=>'SERVICE','value'=>$data['service_name'],'position'=>$position++];

        $booking_details_data["service_duration"] = ['field'=>'DURATION','value'=>$data['service_duration'],'position'=>$position++];

        $booking_details_data["start_date"] = ['field'=>'START DATE','value'=>'-','position'=>$position++];

        $booking_details_data["start_time"] = ['field'=>'START TIME','value'=>'-','position'=>$position++];

        $booking_details_data["address"] = ['field'=>'ADDRESS','value'=>'','position'=>$position++];

        if(isset($data['reward_ids']) && !empty($data['reward_ids'])){

            $reward_detail = array();

            $reward_ids = array_map('intval',$data['reward_ids']);

            $rewards = Reward::whereIn('_id',$reward_ids)->get(array('_id','title','quantity','reward_type','quantity_type'));

            if(count($rewards) > 0){

                foreach ($rewards as $value) {

                    $title = $value->title;

                    $reward_detail[] = ($value->reward_type == 'nutrition_store') ? $title : $value->quantity." ".$title;
                    
                    array_set($data, 'reward_type', $value->reward_type);
            
                }
            
                $reward_info = (!empty($reward_detail)) ? implode(" + ",$reward_detail) : "";
            
                array_set($data, 'reward_info', $reward_info);
                
            }
        }

        if(isset($data['cashback']) && $data['cashback']){
            array_set($data,'reward_info','Cashback');
        }

        if(isset($data["reward_info"]) && $data["reward_info"] != ""){

            if($data["reward_info"] == 'Cashback'){
                $booking_details_data["reward"] = ['field'=>'REWARD','value'=>$data["reward_info"],'position'=>$position++];
            }else{
                $booking_details_data["reward"] = ['field'=>'REWARD','value'=>$data["reward_info"]." (Avail it from your Profile)",'position'=>$position++];
            }
        }
         
        if(isset($data["membership"]) && !empty($data["membership"])){

            if(isset($data["membership"]['cashback']) && $data["membership"]['cashback'] === true){

                $booking_details_data["reward"] = ['field'=>'PREBOOK REWARD','value'=>'Cashback','position'=>$position++];
            }

            if(isset($data["membership"]["reward_ids"]) && isset($data["membership"]["reward_ids"]) && !empty($data["membership"]["reward_ids"])){

                $reward_id = $data["membership"]["reward_ids"][0];

                $reward = Reward::find($reward_id,['title']);

                if($reward){

                    $booking_details_data["reward"] = ['field'=>'PREBOOK REWARD','value'=>$reward['title'],'position'=>$position++];
                }
            }

        }

        if(isset($data["assisted_by"]) && isset($data["assisted_by"]["name"]) && $data["assisted_by"] != ""){

            $booking_details_data["assisted_by"] = ['field'=>'ASSISTED BY','value'=>$data["assisted_by"]["name"],'position'=>$position++];
        }

        if(isset($data['start_date']) && $data['start_date'] != ""){
            $booking_details_data['start_date']['value'] = date('d-m-Y (l)',strtotime($data['start_date']));
        }

        if(isset($data['schedule_date']) && $data['schedule_date'] != ""){
            $booking_details_data['start_date']['value'] = date('d-m-Y (l)',strtotime($data['schedule_date']));
        }

        if(isset($data['preferred_starting_date']) && $data['preferred_starting_date'] != ""){
            $booking_details_data['start_date']['value'] = date('d-m-Y (l)',strtotime($data['preferred_starting_date']));
        }

        if(isset($data['start_time']) && $data['start_time'] != ""){
            $booking_details_data['start_time']['value'] = strtoupper($data['start_time']);
        }

        if(isset($data['schedule_slot_start_time']) && $data['schedule_slot_start_time'] != ""){
            $booking_details_data['start_time']['value'] = strtoupper($data['schedule_slot_start_time']);
        }

        if($data['finder_address'] != ""){
            $booking_details_data['address']['value'] = $data['finder_address'];
        }
        
        if(in_array($data['type'],["healthytiffintrial","healthytiffintrail","healthytiffinmembership"])){

            if(isset($data['customer_address']) && $data['customer_address'] != ""){
                $booking_details_data['address']['value'] = $data['customer_address'];
            }

        }else{

            if($data['finder_address'] != ""){
                $booking_details_data['address']['value'] = $data['finder_address'];
            }
            if(isset($data['finder_address']) && $data['finder_address'] != ""){
                $booking_details_data['address']['value'] = $data['finder_address'];
            }
        }

        if(isset($booking_details_data['address']['value'])){

            $booking_details_data['address']['value'] = str_replace("  ", " ",$booking_details_data['address']['value']);
            $booking_details_data['address']['value'] = str_replace(", , ", "",$booking_details_data['address']['value']);
        }

        if(in_array($data['type'], ['manualtrial','manualautotrial','manualmembership'])){
            $booking_details_data["start_date"]["field"] = "PREFERRED DATE";
            $booking_details_data["start_time"]["field"] = "PREFERRED TIME";
        }

        if(in_array($data['type'], ['booktrial','workoutsession'])){
            $booking_details_data["start_date"]["field"] = "DATE";
            $booking_details_data["start_time"]["field"] = "TIME";
        }

        if(isset($data['preferred_day']) && $data['preferred_day'] != ""){
            $booking_details_data['start_date']['field'] = 'PREFERRED DAY';
            $booking_details_data['start_date']['value'] = $data['preferred_day'];
        }

        if(isset($data['preferred_time']) && $data['preferred_time'] != ""){
            $booking_details_data['start_time']['field'] = 'PREFERRED TIME';
            $booking_details_data['start_time']['value'] = $data['preferred_time'];
        }

        if(isset($data['"preferred_service']) && $data['"preferred_service'] != "" && $data['"preferred_service'] != null){
            $booking_details_data['service_name']['field'] = 'PREFERRED SERVICE';
            $booking_details_data['service_name']['value'] = $data['preferred_service'];
        }

        if(in_array($data['type'],["healthytiffintrial","healthytiffintrail"]) && isset($data['ratecard_remarks']) && $data['ratecard_remarks'] != ""){
            $booking_details_data['service_duration']['value'] = ucwords($data['ratecard_remarks']);
        }

        if(in_array($data['type'],["healthytiffintrail","healthytiffintrial","healthytiffinmembership"])){
            $booking_details_data['finder_name_location']['field'] = 'BOUGHT AT';
            $booking_details_data['finder_name_location']['value'] = $data['finder_name'];
        }

        $booking_details_all = [];
        foreach ($booking_details_data as $key => $value) {

            $booking_details_all[$value['position']] = ['field'=>$value['field'],'value'=>$value['value']];
        }

        foreach ($booking_details_all as $key => $value) {

            if($value['value'] != "" && $value['value'] != "-"){
                $booking_details[] = $value;
            }

        }

        return $booking_details;
    }

    function getPaymentDetails($data,$payment_mode_type){

        $amount_summary = [];
        
        $you_save = 0;
        
        $amount_summary[0] = array(
            'field' => 'Total Amount',
            'value' => 'Rs. '.$data['amount_finder']
        );

        $amount_payable = [];

        $amount_payable= array(
            'field' => 'Total Amount Payable',
            'value' => 'Rs. '.$data['amount_final']
        );

        

        if($payment_mode_type == 'part_payment' && isset($data['part_payment_calculation'])){

            $remaining_amount = $data['amount_customer'];

            if(isset($data["part_payment_calculation"]["part_payment_amount"]) && $data["part_payment_calculation"]["part_payment_amount"] > 0){

                $remaining_amount -= $data["part_payment_calculation"]["part_payment_amount"];
            }

            if(isset($data["part_payment_calculation"]["convinience_fee"]) && $data["part_payment_calculation"]["convinience_fee"] > 0){

                $remaining_amount -= $data["part_payment_calculation"]["convinience_fee"];
            }

            if(isset($data['coupon_discount_amount']) && $data['coupon_discount_amount'] > 0){

                $remaining_amount -= $data['coupon_discount_amount'];

                $amount_summary[] = array(
                    'field' => 'Coupon Discount',
                    'value' => '-Rs. '.$data['coupon_discount_amount']
                );

                $you_save += intval($data['coupon_discount_amount']);
                
            }

            if(isset($data['customer_discount_amount']) && $data['customer_discount_amount'] > 0){

                $remaining_amount -= $data['customer_discount_amount'];

                $amount_summary[] = array(
                    'field' => 'Corporate Discount',
                    'value' => '-Rs. '.$data['customer_discount_amount']
                );

                $you_save += intval($data['customer_discount_amount']);
            }

            if(isset($data['app_discount_amount']) && $data['app_discount_amount'] > 0){

                $remaining_amount -= $data['app_discount_amount'];

                $amount_summary[] = array(
                    'field' => 'App Discount',
                    'value' => '-Rs. '.$data['app_discount_amount']
                );

                $you_save += intval($data['app_discount_amount']);
                
            }

            $amount_summary[] = array(
                'field' => 'Remaining Amount Payable',
                'value' => 'Rs. '.$remaining_amount
            );

            $amount_summary[] = array(
                'field' => 'Booking Amount (20%)',
                'value' => 'Rs. '.$data['part_payment_calculation']['part_payment_amount']
            );

            if(isset($data['convinience_fee']) && $data['convinience_fee'] > 0){

                $amount_summary[] = array(
                    'field' => 'Convenience Fee',
                    'value' => '+Rs. '.$data['convinience_fee']
                );

            }

            $cashback_detail = $this->customerreward->purchaseGame($data['amount'],$data['finder_id'],'paymentgateway',$data['offer_id'],false,$data["part_payment_calculation"]["part_payment_and_convinience_fee_amount"],$data['type']);

            if($cashback_detail['amount_deducted_from_wallet'] > 0){

                $amount_summary[] = array(
                    'field' => 'Fitcash Applied',
                    'value' => '-Rs. '.$cashback_detail['amount_deducted_from_wallet']
                );

            }

            $amount_payable = array(
                'field' => 'Total Amount Payable (20%)',
                'value' => 'Rs. '.$data['part_payment_calculation']['amount']
            );

        }else{

            if(isset($data['convinience_fee']) && $data['convinience_fee'] > 0){

                $amount_summary[] = array(
                    'field' => 'Convenience Fee',
                    'value' => '+Rs. '.$data['convinience_fee']
                );
            }

            if(isset($data['cashback_detail']) && isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){

                $amount_summary[] = array(
                    'field' => 'Fitcash Applied',
                    'value' => '-Rs. '.$data['cashback_detail']['amount_deducted_from_wallet']
                );
                $you_save += $data['cashback_detail']['amount_deducted_from_wallet'];
                
            }

            if(isset($data['coupon_discount_amount']) && $data['coupon_discount_amount'] > 0){

                $amount_summary[] = array(
                    'field' => 'Coupon Discount',
                    'value' => '-Rs. '.$data['coupon_discount_amount']
                );
                $you_save += $data['coupon_discount_amount'];
                
            }

            if(isset($data['customer_discount_amount']) && $data['customer_discount_amount'] > 0){

                $amount_summary[] = array(
                    'field' => 'Corporate Discount',
                    'value' => '-Rs. '.$data['customer_discount_amount']
                );
                $you_save += $data['coupon_discount_amount'];
                
                
            }

            if(isset($data['app_discount_amount']) && $data['app_discount_amount'] > 0){

                $amount_summary[] = array(
                    'field' => 'App Discount',
                    'value' => '-Rs. '.$data['app_discount_amount']
                );

                $you_save += $data['app_discount_amount'];
                
            }

            if(isset($data['type']) && $data['type'] == 'workout-session' && $payment_mode_type != 'pay_later'){
                
                $amount_summary[] = array(
                    'field' => 'Instant Pay discount',
                    'value' => '-Rs. '.$data['instant_payment_discount']
                );

                $you_save += $data['instant_payment_discount'];
                
                if(isset($data['pay_later']) && $data['pay_later']){
                    
                    $amount_payable['value'] = "Rs. ".($data['amount_final'] - $data['instant_payment_discount']);

                }

            }

            if(isset($data['type']) && $data['type'] == 'workout-session' && $payment_mode_type == 'pay_later' && !(isset($data['pay_later']) && $data['pay_later'])){
                
                $amount_payable['value'] = "Rs. ".($data['amount_final'] + $data['instant_payment_discount']);

            }
        }

        if(!empty($reward)){
            $amount_summary[] = $reward;
        }

        
        $payment_details  = [];
        
        $payment_details['amount_summary'] = $amount_summary;
        $payment_details['amount_payable'] = $amount_payable;
        
        if($you_save > 0){
            $result['payment_details']['savings'] = [
                'field' => 'Your total savings',
                'value' => "Rs.".$you_save
            ];
        }

        return $payment_details;

    }

    function getPaymentModes($data){

        $payment_modes = [];
        $payment_modes[] = array(
            'title' => 'Online Payment',
            'subtitle' => 'Transact online with netbanking, card and wallet',
            'value' => 'paymentgateway',
        );


        $emi = $this->utilities->displayEmi(array('amount'=>$data['data']['amount']));

        if(!empty($data['emi']) && $data['emi']){
            $payment_modes[] = array(
                'title' => 'EMI',
                'subtitle' => 'Transact online with credit installments',
                'value' => 'emi',
            );
        }

        if(!$this->vendor_token){
            if(!empty($data['cash_pickup']) && $data['cash_pickup']){
                $payment_modes[] = array(
                    'title' => 'Cash Pickup',
                    'subtitle' => 'Schedule cash payment pick up',
                    'value' => 'cod',
                );
            }

            if(!empty($data['part_payment']) && $data['part_payment']){
                $payment_modes[] = array(
                    'title' => 'Reserve Payment',
                    'subtitle' => 'Pay 20% to reserve membership and pay rest on joining',
                    'value' => 'part_payment',
                );
            }
        }

        if($this->vendor_token){

            $payment_modes[] = array(
                'title' => 'Pay at Studio',
                'subtitle' => 'Transact via paying cash at the Center',
                'value' => 'pay_at_vendor',
            );
        
        }

        if(isset($data['pay_later']) && $data['pay_later']){
            
            $payment_modes[] = array(
                'title' => 'Pay Later',
                'subtitle' => 'Pay after the workout session',
                'value' => 'pay_later',
            );
        
        }

        return $payment_modes;
    }

    public function checkCouponCode(){
        
        $data = Input::json()->all();

        if($this->vendor_token){
            $resp = array("status"=> 400, "message" => "Coupon code is not valid", "error_message" => "Coupon code is not valid");
            return Response::json($resp,400);
        }

        if(!isset($data['coupon'])){
            $resp = array("status"=> 400, "message" => "Coupon code missing", "error_message" => "Please enter a valid coupon");
            return Response::json($resp,400);
        }
        if(!isset($data['ratecard_id']) && !isset($data['ticket_id'])){
            $resp = array("status"=> 400, "message" => "Ratecard Id or ticket Id must be present", "error_message" => "Coupon cannot be applied on this transaction");
            return Response::json($resp,400);
        }
        if($this->utilities->isGroupId($data['coupon'])){
            $ratecard = Ratecard::find($data['ratecard_id']);
            if($ratecard['type'] == "membership" || $ratecard['type'] == "memberships"){
                $data['group_id'] = $data['coupon'];

            $resp = $this->utilities->validateGroupId(['group_id'=>$data['coupon']]);
            
            if($resp['status']==200){
                
                return Response::json($resp);
            
            }else{
                
                return Response::json($resp, 400);
                
            }
            }else{
                return Response::json($resp, 400);
            }
            

        }

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }

        $service_id = isset($data['service_id']) ? $data['service_id']: null;

        $couponCode = $data['coupon'];

        $ticket = null;
        
        $ticket_quantity = isset($data['ticket_quantity']) ? $data['ticket_quantity'] : 1;
        
        
        if(isset($data['ticket_id'])){
            $ticket = Ticket::find($data['ticket_id']);
            if(!$ticket){
                $resp = array("status"=> 400, "message" => "Ticket not found", "error_message" => "Coupon cannot be applied on this transaction");
                return Response::json($resp,400);   
            }
        }

        $ratecard = null;
        $amount_finder = 0;
        $amount = 0;
        $offer_id = false;
        $finder_id = false;

        if(isset($data['ratecard_id'])){

            $ratecard = Ratecard::find($data['ratecard_id']);

            if(!$ratecard){
                $resp = array("status"=> 400, "message" => "Ratecard not found", "error_message" => "Coupon cannot be applied on this transaction");
                return Response::json($resp,400);   
            }

            $ratecard_data = $ratecard->toArray();

            $finder_id = (int)$ratecard['finder_id'];

            if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
                $amount_finder = $ratecard['special_price'];
            }else{
                $amount_finder = $ratecard['price'];
            }

            $offer_id = false;

            $offer = Offer::where('ratecard_id',$ratecard['_id'])
                    ->where('hidden', false)
                    ->orderBy('order', 'asc')
                    ->where('start_date','<=',new DateTime(date("d-m-Y 00:00:00")))
                    ->where('end_date','>=',new DateTime(date("d-m-Y 00:00:00")))
                    ->first();

            if($offer){
                $offer_id = $offer->_id;
                $amount_finder = $offer->price;
            }

            $amount = $amount_finder;

            if($ratecard != null && $ratecard['type'] == "membership" && isset($_GET['device_type']) && in_array($_GET['device_type'], ["ios","android"])){

                $this->appOfferDiscount = in_array($finder_id, $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;

                $app_discount_amount = intval($amount_finder * ($this->appOfferDiscount/100));

                $amount -= $app_discount_amount;
            }

            if($this->convinienceFeeFlag() && $this->utilities->isConvinienceFeeApplicable($ratecard_data)){
                
                $convinience_fee_percent = Config::get('app.convinience_fee');

                $convinience_fee = round($amount_finder*$convinience_fee_percent/100);

                $convinience_fee = $convinience_fee <= 150 ? $convinience_fee : 150;

                $amount += $convinience_fee;
            }


            $corporate_discount_percent = $this->utilities->getCustomerDiscount();
            $customer_discount_amount = intval($amount_finder * ($corporate_discount_percent/100));

            $amount -= $customer_discount_amount;

            if($amount > 0){

                $cashback_detail = $this->customerreward->purchaseGame($amount,$finder_id,'paymentgateway',$offer_id,false,false,false,$ratecard['type']);

                if(isset($data['cashback']) && $data['cashback'] == true){
                    $amount -= $cashback_detail['amount_discounted'];
                }


                if(isset($cashback_detail['amount_deducted_from_wallet']) && $cashback_detail['amount_deducted_from_wallet'] > 0){
                    $amount -= $cashback_detail['amount_deducted_from_wallet'];
                }
            }

        }

        if(isset($data['event_id'])){
            $customer_id = false;
        }

        $customer_id = isset($customer_id) ? $customer_id : false;

        $resp = $this->customerreward->couponCodeDiscountCheck($ratecard,$couponCode,$customer_id, $ticket, $ticket_quantity, $service_id); 
        Log::info("REsponse from CustomerReward", $resp);
        if($resp["coupon_applied"]){

            if(isset($data['event_id']) && isset($data['customer_email'])){
                                
                $already_applied_coupon = Customer::where('email', 'like', '%'.$data['customer_email'].'%')->whereIn('applied_promotion_codes',[strtolower($data['coupon'])])->count();
            
                if($already_applied_coupon>0 && !$resp["vendor_routed_coupon"]){
                    return Response::json(array('status'=>400,'data'=>array('final_amount'=>($resp['data']['discount']+$resp['data']['final_amount']), "discount" => 0), 'error_message'=>'Coupon already applied', "message" => "Coupon already applied"), 400);
                }
            }

            if($ratecard != null && $ticket == null){

                $resp["data"]["discount"] = $amount > $resp["data"]["discount"] ? $resp["data"]["discount"] : $amount;
            }

            $resp['status'] = 200;
            $resp['message'] = $resp['success_message'] = "Rs. ".$resp["data"]["discount"]." has been applied Successfully ";

            if(isset($resp['custom_message'])){
                $resp['message'] = $resp['success_message'] = $resp['custom_message'];
            }
            // if(strtolower($data['coupon']) == "fitlove" || $data['coupon'] == "fitlove"){
            //     $resp['success_message'] = $resp['message'] = "Basis slot availability, your surprise discount for this partner outlet is Rs ".$resp["data"]["discount"];
            // }
            // if(!$resp["vendor_routed_coupon"]){
            //     if($resp["data"]["discount"] <= 0){
    
            //         $resp['status'] = 400;
            //         $resp['message'] = $resp['error_message'] = "Cannot apply Coupon";
            //         $resp["coupon_applied"] = false;
    
            //         unset($resp['success_message']);
            //     }
            // }

            return Response::json($resp,$resp['status']);

        }else{

            $errorMessage =  "Coupon is either not valid or expired";

            if((isset($resp['fitternity_only_coupon']) && $resp['fitternity_only_coupon']) || (isset($resp['vendor_exclusive']) && $resp['vendor_exclusive']) || (isset($resp['app_only']) && $resp['app_only'])){
                $errorMessage =  $resp['error_message'];
            }

            $resp = array("status"=> 400, "message" => "Coupon not found", "error_message" =>$errorMessage, "data"=>$resp["data"]);

            return Response::json($resp,400);    
        }

        return Response::json($resp,200);
    }


    public function convinienceFeeFlag(){

        $flag = true;

         $header_array = [
            "Device-Type"=>"",
            "App-Version"=>"",
            "Authorization-Vendor"=>""
        ];

        foreach ($header_array as $header_key => $header_value) {

            $value = Request::header($header_key);

            if($value != "" && $value != null && $value != 'null'){
               $header_array[$header_key] =  $value;
            }
            
        }

        $data['device_type'] = $header_array['Device-Type'];
        $data['app_version'] = $header_array['App-Version'];
        $data['authorization_vendor'] = $header_array['Authorization-Vendor'];

        $version_ios = '4.3';
        $version_android = '4.3';

        if(isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && isset($_GET['app_version']) && (float)$_GET['app_version'] < $version_ios){
            $flag = false;
        }

        if(isset($_GET['device_type']) && $_GET['device_type'] == 'android' && isset($_GET['app_version']) && (float)$_GET['app_version'] < $version_android){
            $flag = false;
        }

        if($data['device_type'] == 'ios' && (float)$data['app_version'] < $version_ios){
            $flag = false;
        }

        if($data['device_type'] == 'android' && (float)$data['app_version'] < $version_android){
            $flag = false;
        }

        if($data['authorization_vendor'] != "" && $data['authorization_vendor'] != null && $data['authorization_vendor'] != 'null'){
           $flag = true;
        }

        return $flag;

    }

    public function walletOrderCapture(){
        
        $data = Input::all();

        Log::info("wallet capture");

        Log::info($data);

        $rules = array(
            'amount'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required',
            'customer_source'=>'required',
            'type'=>'required'
        );

        $validator = Validator::make($data,$rules);

        $customerDetail = $this->getCustomerDetail($data);
        
        if($customerDetail['status'] != 200){
            return Response::json($customerDetail,$customerDetail['status']);
        }
        
        if($data['type'] != 'wallet'){
            return Response::json(array('message'=>'Invalid parameters'), 400);
        }

        $data = array_merge($data,$customerDetail['data']);

        Log::info("before pledge");

        Log::info($data);

        $fitternity_share = $this->getFitternityShareAmount($data);

        Log::info("prev pledge");
        Log::info($fitternity_share);

        $data['fitternity_share_change'] = ((int)$data['fitternity_share']) != $fitternity_share ? true : false;

        $data["fitternity_share"] = $fitternity_share;
        
        $data["fitcash_amount"] = $data['amount'] + $data["fitternity_share"];
        
        $data['amount_finder'] = $data['amount'];

        $data['payment_mode'] = 'paymentgateway';
        
        $data['status'] = "0";
        
        $order_id = $data['_id'] = $data['order_id'] = Order::max('_id') + 1;

        $txnid = "";
        $successurl = "";
        $mobilehash = "";
        if($data['customer_source'] == "android" || $data['customer_source'] == "ios"){
            $txnid = "MFIT".$data['_id'];
            $successurl = $data['customer_source'] == "android" ? Config::get('app.website')."/paymentsuccessandroid" : Config::get('app.website')."/paymentsuccessios";
        }else{
            $txnid = "FIT".$data['_id'];
            $successurl = Config::get('app.website')."/paymentsuccessproduct";
        }
        $data['txnid'] = $txnid;
        $data['finder_name'] = 'Fitternity';
        $data['finder_slug'] = 'fitternity';
        
        $data['service_name'] = 'Fitternity Pledge';
        $data['service_id'] = 100000;
        
        $hash = getHash($data);
        $data = array_merge($data,$hash);
        
        $order = new Order($data);

        $order->_id = $order_id;
        
        $order->save();
        
        $result['firstname'] = strtolower($data['customer_name']);
        $result['lastname'] = "";
        $result['phone'] = $data['customer_phone'];
        $result['email'] = strtolower($data['customer_email']);
        $result['orderid'] = $data['_id'];
        $result['txnid'] = $txnid;
        $result['amount'] = $data['amount'];
        $result['productinfo'] = strtolower($data['productinfo']);
        $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($data['service_name']));
        $result['successurl'] = $successurl;
        $result['hash'] = $data['payment_hash'];
        $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
        $result['finder_name'] = strtolower($data['finder_name']);
        $result['fitternity_share'] = $data['fitternity_share'];
        $result['fitternity_share_change'] = $data['fitternity_share_change'];
        
        
        $resp   =   array(
            'status' => 200,
            'data' => $result,
            'message' => "Tmp Order Generated Sucessfully"
        );
        return Response::json($resp);

    }

    public function walletOrderSuccess(){

        $data = Input::json()->all();

        Log::info("wallet success");
        
        Log::info($data);
        
        $rules = array(
            'order_id'=>'required'
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }
        
        $order_id   =   (int) $data['order_id'];
        $order      =   Order::findOrFail($order_id);

        if(isset($order->status) && $order->status == '1'){

            $resp   =   array('status' => 401, 'statustxt' => 'error', "message" => "Already Status Successfull");
            return Response::json($resp,401);

        }

        $hash_verified = $this->utilities->verifyOrder($data,$order);

        // $hash_verified = true;


        if($data['status'] == 'success' && $hash_verified){

            $order->status = "1";

            $fitternity_share = $this->getFitternityShareAmount($order->toArray());

            $order->fitternity_share_change_success = $order->fitternity_share != $fitternity_share ? true : false;
            
            $order->fitternity_share = $fitternity_share;

            $order->fitcash_amount = $order->amount + $fitternity_share;

            $req = array(
                "customer_id"=>$order['customer_id'],
                "order_id"=>$order['_id'],
                "amount"=>$order['fitcash_amount'],
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $order['fitcash_amount'],
                "type"=>'CREDIT',
                'entry'=>'credit',
                'description'=>"Fitcash credited for PLEDGE",
            );

            Log::info($req);

            $order->wallet_req = $req;

            $wallet = $this->utilities->walletTransaction($req, $order->toArray());

            Log::info("wallet");

            Log::info($wallet);
            
            $redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),Config::get('app.queue'));

            $order->redis_id = $redisid;

            $order->wallet_balance = $this->utilities->getWalletBalance($order['customer_id']);

            $order->website = "www.fitternity.com";

            $order->update();

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
            
        } else {
           
            if($hash_verified == false){
             
                $order->hash_verified = false;
                $order->update();
                
            }
           
            $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'message' => "Transaction Failed :)");
            
        }
        
        return Response::json($resp);
            
    }

    public function getFitternityShareAmount($data){
        
        Log::info( Order::active()->where('customer_id', $data['customer_id'])->where('type', 'wallet')->get());
        
        $prev_pledge_amount = Order::active()->where('customer_id', $data['customer_id'])->where('type', 'wallet')->sum('fitternity_share');

        $remaining_limit = 1000 - $prev_pledge_amount;

        $fitternity_share = $data['amount'] <= $remaining_limit ? $data['amount'] : $remaining_limit;

        return (int)$fitternity_share;
    
    }

    public function checkoutSummary(){

        $data = Input::json()->all();

        $result = [

            'order_details' => [],
            'payment_details' => [
                'amount_summary' => [],
                'amount_payable' => [],
                'note'=>""
            ],
            'full_wallet_payment' => false
        ];

        $ratecard_id = null;

        $data['you_save'] = 0;

        if(!isset($data['ratecard_id']) && !isset($data['order_id'])){
                
                return Response::json(array('status'=>400, 'message'=>'Order id or Ratecard id is required'), $this->error_status);
        }

        if(!isset($data['ratecard_id'])){

            $order = Order::find(intval($data['order_id']));

            if(isset($order->ratecard_id) && $order->ratecard_id != ''){
               
                $ratecard_id = $order->ratecard_id;
            
            }
        
        }else{

                $ratecard_id = intval($data['ratecard_id']);
        }


        if($ratecard_id && $ratecard_id != ''){

            $data['ratecard_id'] = $ratecard_id;
            
            Log::info("idifiifififififi");

            $ratecardDetail = $this->getRatecardDetail($data);

            if($ratecardDetail['status'] != 200){
                return Response::json($ratecardDetail,$this->error_status);
            }

            $data = array_merge($data,$ratecardDetail['data']);
            
            $data['amount_payable'] = $data['amount'];

            $ratecard = Ratecard::find(intval($data['ratecard_id']));

            $data['ratecard_price'] = $ratecard['price'];
            
            
            
            $result['payment_details']['amount_summary'][] = [
                'field' => 'Total Amount',
                'value' => 'Rs. '.(string)number_format($data['amount'])
            ];

            if($this->utilities->isConvinienceFeeApplicable($data)){
                
                $convinience_fee_percent = Config::get('app.convinience_fee');
                
                $convinience_fee = round($data['amount'] * $convinience_fee_percent/100);
                
                $convinience_fee = $convinience_fee <= 150 ? $convinience_fee : 150;

                $data['convinience_fee'] = $convinience_fee;
                
                
                $data['amount_payable'] = $data['amount_payable'] + $data['convinience_fee'];
                
                $result['payment_details']['amount_summary'][] = [
                    'field' => 'Convenience fee',
                    'value' => '+Rs. '.(string)$data['convinience_fee'],
                    /*"info" => "Convenience fees is applicable for exclusive offers on online payments & Cash on delivery."*/
                ];
            }

            $jwt_token = Request::header('Authorization');
            
            Log::info('jwt_token checkout summary: '.$jwt_token);
                
            if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
                
                $decoded = customerTokenDecode($jwt_token);
                
                $customer_id = $decoded->customer->_id;

                $getWalletBalanceData = [
                    'finder_id'=>$ratecard['finder_id'],
                    'order_type'=>$ratecard['type']
                ];

                $data['wallet_balance'] = $this->utilities->getWalletBalance($customer_id,$getWalletBalanceData);

                $data['fitcash_applied'] = $data['amount_payable'] > $data['wallet_balance'] ? $data['wallet_balance'] : $data['amount_payable'];
                
                $data['amount_payable'] -= $data['fitcash_applied'];
                if($data['fitcash_applied'] > 0){

                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Fitcash Applied',
                        'value' => '-Rs. '.(string)number_format($data['fitcash_applied'])
                    ];

                    $data['you_save'] += $data['fitcash_applied'];

                }
            }

            if(isset($data['coupon'])){
                
                $resp = $this->customerreward->couponCodeDiscountCheck($ratecard, $data['coupon']);

                if($resp["coupon_applied"]){
                    
                    $data['coupon_discount'] = $data['amount_payable'] > $resp['data']['discount'] ? $resp['data']['discount'] : $data['amount_payable'];

                    $data['amount_payable'] = $data['amount_payable'] - $data['coupon_discount'];
                    
                    $data['you_save'] += $data['coupon_discount'];
                    
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Coupon Discount',
                        'value' => '-Rs. '.(string) number_format($data['coupon_discount'])
                    ];
                
                }

            }

            $result['payment_details']['amount_payable'] = [
                'field' => 'Total Amount Payable',
                'value' => 'Rs. '.(string)number_format($data['amount_payable'])
            ];

            if($data['amount_payable'] == 0){
                $result['full_wallet_payment'] = true;
            }
            
            $finder_id = (int) $data['finder_id'];
            
            $finderDetail = $this->getFinderDetail($finder_id);
    
            if($finderDetail['status'] != 200){
                return Response::json($finderDetail,$this->error_status);
            }
    
            $data = array_merge($data,$finderDetail['data']);
    
            if(isset($data['service_id'])){
                $service_id = (int) $data['service_id'];
    
                $serviceDetail = $this->getServiceDetail($service_id);

                if($serviceDetail['status'] != 200){
                    return Response::json($serviceDetail,$this->error_status);
                }

                $data = array_merge($data,$serviceDetail['data']);
            }

            $result['order_details'] = [
                "studio_name"=>[
                    "field"=> "",
                    "value"=> $data['finder_name'] . "," . $data['finder_location']
                ],
                "service_name"=>[
                    "field"=> "",
                    "value"=>  $data['service_name']
                ],
                "duration_amount"=>[
                    "field"=> $data['service_duration'],
                    "value"=> "Rs. ".number_format($data['amount'])
                ],
                "remarks"=>[
                    "field"=> "REMARKS",
                    "value"=> $data['ratecard_remarks']
                ]
                // "address"=>[
                //     "field"=> "ADDRESS",
                //     "value"=> $data['finder_address']
                // ]
            ];

            if(isset($data['reward_ids'])){
                
                $reward = Reward::find(intval($data['reward_ids'][0]));

                if($reward){
                    $reward_title = $reward['title'];
                    $reward_amount = $reward['payload']['amount'];
                    
                    $result['order_details']['reward'] = [
                        'field' => "REWARD ($reward_title)",
                        'value' =>  ""
                    ];

                    $data['you_save'] += $reward_amount;
                
                }
            }

            if(isset($data['cashback'])){
                

                $result['order_details']['reward'] = [
                    'field' => 'REWARD(Cashback)',
                    'value' => "Rs. ".$data['cashback']
                ];

                $data['you_save'] += intval($data['cashback']);
                
            }

            if($data['finder_category_id']==42){
                
                // $result['order_details']['studio_name']['field'] = "TIFFIN SERVICE";

                if(isset($result['order_details']['address'])){
                    unset($result['order_details']['address']);
                };
            }

            if($data['finder_slug'] == 'fitternity-diet-vendor-andheri-east'){
                
                unset($result['order_details']['studio_name']);
                
                if(isset($result['order_details']['address'])){
                    unset($result['order_details']['address']);
                }                    
            }
            $result['order_details'] = array_values($result['order_details']);

            if($data['you_save'] > 0){
                $result['payment_details']['savings'] = [
                    'field' => 'Your total savings',
                    'value' => "Rs. ".number_format($data['you_save']),
                    'amount' => $data['you_save']
                ];
            }


        }else{

            Log::info("elelelelelelel");
            

            $order_id = $data['order_id'];
    
            $order = Order::find(intval($order_id));

            $result['order_details'] = [
                "studio_name"=>[
                    "field"=> "",
                    "value"=> $order['finder_name'].",".$order['finder_location']
                ],
                "service_name"=>[
                    "field"=> "",
                    "value"=>  $order['service_name']
                ],
                "duration_amount"=>[
                    "field"=> $order['service_duration'],
                    "value"=> "Rs. ".$order['amount']
                ]
                // "address"=>[
                //     "field"=> "ADDRESS",
                //     "value"=> $data['finder_address']
                // ]
            ];


            $data['you_save'] = 0;

            $result['payment_details']['amount_summary'][] = [
                'field' => 'Total Amount',
                'value' => 'Rs. '.(string)$order['amount']
            ];

            $data['amount_payable'] = $order['amount'];

            $jwt_token = Request::header('Authorization');
            
            Log::info('jwt_token checkout summary: '.$jwt_token);
                
            if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
                
                $decoded = customerTokenDecode($jwt_token);
                
                $customer_id = $decoded->customer->_id;

                $getWalletBalanceData = [
                    'finder_id'=>$order['finder_id'],
                    'order_type'=>$order['type']
                ];

                $data['wallet_balance'] = $this->utilities->getWalletBalance($customer_id,$getWalletBalanceData);

                $data['fitcash_applied'] = $data['amount_payable'] > $data['wallet_balance'] ? $data['wallet_balance'] : $data['amount_payable'];
                
                $data['amount_payable'] -= $data['fitcash_applied'];
                if($data['fitcash_applied'] > 0){

                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Fitcash Applied',
                        'value' => '-Rs. '.(string)$data['fitcash_applied']
                    ];

                    $data['you_save'] += $data['fitcash_applied'];

                }
            }

            if(isset($data['coupon'])){
                
                $resp = $this->customerreward->couponCodeDiscountCheck($ratecard, $data['coupon']);

                if($resp["coupon_applied"]){
                    
                    $data['coupon_discount'] = $data['amount_payable'] > $resp['data']['discount'] ? $resp['data']['discount'] : $data['amount_payable'];

                    $data['amount_payable'] = $data['amount_payable'] - $data['coupon_discount'];
                    
                    $data['you_save'] += $data['coupon_discount'];
                    
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Coupon Discount',
                        'value' => '-Rs. '.(string)$data['coupon_discount']
                    ];
                
                }

            }

            if(isset($data['reward_ids'])){
                
                $reward = Reward::find(intval($data['reward_ids'][0]));

                if($reward){
                    $reward_title = $reward['title'];
                    $reward_amount = $reward['payload']['amount'];
                    
                    $result['order_details']['reward'] = [
                        'field' => "REWARD ($reward_title)",
                        'value' =>  ""
                    ];

                    $data['you_save'] += $reward_amount;
                
                }
            }

            if(isset($data['cashback'])){
                

                $result['order_details']['reward'] = [
                    'field' => 'REWARD (Cashback)',
                    'value' => "Rs. ".$data['cashback']
                ];

                $data['you_save'] += intval($data['cashback']);

            }
                

            $result['payment_details']['amount_payable'] = [
                'field' => 'Total Amount Payable',
                'value' => 'Rs. '.(string)$data['amount_payable']
            ];

            if($data['amount_payable'] == 0){
                $result['full_wallet_payment'] = true;
            }

            if($data['you_save'] > 0){
                $result['payment_details']['savings'] = [
                    'field' => 'Your total savings',
                    'value' => "Rs. ".$data['you_save'],
                ];
            }
            $result['order_details'] = array_values($result['order_details']);
            

        //     $ratecard = Ratecard::find(intval($order->ratecard_id));
                
        //     $order_details= $this->getBookingDetails($order->toArray());
            
        //     $result['order_details'] = [];

        //     $reward_amount = 0;

        //     if(isset($order['reward_ids']) && !empty($order['reward_ids'])){
        //         $reward_ids = array_map('intval',$data['reward_ids']);
        //         $rewards = Reward::whereIn('_id',$reward_ids)->get(array('payload'));
        //         if(count($rewards) > 0){
        //             foreach ($rewards as $value) {
        //                 $reward_amount += $value['payload']['price'];
        //             }
        //         }
        //     }
        //     if(isset($order['cashback']) && $order['cashback']){
        //         $reward_amount += $order['cashback_detail']['wallet_amount'];
        //     }

        //     foreach($order_details as $value){
        //         if(in_array($value['field'], ['ADDRESS', 'START DATE'])){
        //             continue;
        //         }

        //         if(!in_array($value['field'], ['REWARD', 'DURATION'])){
        //             $value['field'] = "";
        //         }

        //         if(in_array($value['field'], ['DURATION'])){
        //             $value['field'] = "";
        //             $value['value'] = "Rs. ".$ratecard['price'];
        //         }

        //         $result['order_details'][] = $value;

        //     }

        //     $result['payment_details'] = $this->getPaymentDetails($order->toArray(),'paymentgateway');
            
        //     if($reward_amount > 0){
        //         if(isset($result['payment_details']['savings'])){
        //             $savings_amount = $reward_amount + $result['payment_details']['savings']['amount'];
        //             $result['payment_details']['savings'] = [
        //                 'field' => 'Your total savings',
        //                 'value' => "Rs. ".$savings_amount
        //             ];
        //         }else{
        //             $result['payment_details']['savings'] = [
        //                 'field' => 'Your total savings',
        //                 'value' => "Rs.".$reward_amount
        //             ];
        //         }
        //     }
            
        }

        return $result;
    }

    public function codOtpSuccess(){

        $jwt_token              =   Request::header('Authorization');
        
        $data = Input::json()->all();

        $rules = [
            'order_id'  => 'required',
            'otp'       => 'required'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }   
        
        if($jwt_token != null){
            
            $decoded = customerTokenDecode($jwt_token);
        
        }        

        $customer_id = (int)($decoded->customer->_id);
            

        $order_id = (int)$data['order_id'];

        $otp = $data['otp'];

        $order = Order::where('customer_id', $customer_id)->where('_id', $order_id)->where('cod_otp', $otp)->first();

        if(!$order){
            return Response::json(array('status' => 404,'message' => 'Please enter the valid code'), $this->error_status);
        }

        $order->cod_otp_verified = true;

        $order->update();

        $success_data = ['order_id'=> $order_id, 'status' => 'success'];
        
        $data_keys = ['customer_name','customer_email','customer_phone','finder_id','service_name','amount','type'];

        foreach($data_keys as $key){
            $success_data[$key] = $order[$key];
        }

        $this->successCommon($success_data);

    }

    
    public function locateMembership($code){

        $order_id = (int) $code;

        $decodeKioskVendorToken = decodeKioskVendorToken();

        $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

        $response = array('status' => 400,'message' =>'Sorry! Cannot locate your membership');
        
        $order = Order::active()->where('type','memberships')->where('finder_id',(int)$vendor['_id'])->find($order_id);

        $locate_data = [
            'code'=>$code,
            'finder_id'=>(int)$vendor['_id'],
            'transaction_type'=>'Order'
        ];
        
        $locateTransaction = LocateTransaction::create($locate_data); 

        if(isset($order)){

            $data = [];

            $preferred_starting_date = date('Y-m-d 00:00:00',time());

            $data['start_date'] = $preferred_starting_date;
            $data['preferred_starting_date'] = $preferred_starting_date;
            $data['end_date'] = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']."+ ".($order->duration_day-1)." days"));
            $data['locate_membership_date'] = time();

            $order->update($data);

            $locateTransaction->transaction_id = (int)$order['_id'];
            $locateTransaction->transaction_type = 'Order';
            $locateTransaction->update();

            $message = "You are good to go your ".ucwords($order['service_duration'])." ".ucwords($order['service_name'])." membership has been activated";

            $createCustomerToken = createCustomerToken((int)$order['customer_id']);

            $response = [
                'status' => 200,
                'message' => $message,
                'token'=>$createCustomerToken,
                'order_id'=> (int)$order['_id']
            ];

            $order_data = $order->toArray();

            $order_data['membership_locate'] = 'locate';

            $response = array_merge($response,$this->utilities->membershipBookedLocateScreen($order_data));

        }

        return Response::json($response,200);

    }

    public function rewardScreen(){

        $data = [];

        $data['title'] = 'Complimentry Rewards';
        $data['banner'] = 'https://b.fitn.in/global/Rewards-page/rewards-web-banner.png';
        $data['rewards'] = [];

        $data['rewards'][] = [ 
            'title' => 'Merchandise Kits',
            'description'=>'We have shaped the perfect fitness kit for you. Strike off these workout essentials from your cheat sheet & get going.',
            'type'=>'fitness_kit',
            'items'=>[
                [
                    'title'=>'Cross Fit & Gym',
                    'description'=>"Lifts and Squats is all you need to think about as we have your workout gear ready - <br/> ● Gym Bag<br/> ● Shaker<br/> ● Arm Band<br/> ● T-Shirt<br/> ● Towel<br/> ● Bottle<br/> ● Earphone Detangler",
                    'products'=>['Gym Bag','Shaker','Arm Band','T-Shirt','Towel','Bottle','Earphone Detangler'],
                    'image'=>'https://b.fitn.in/global/Rewards-page/crossfit%26gym.png'
                ],
                [
                    'title'=>'Zumba & Dance',
                    'description'=>"Groove your way to Fitness while we give you a hip workout wear - <br/> ● Tote Bag<br/> ● Bottle<br/> ● Towel<br/> ● Shoe Bag<br/> ● T-Shirt<br/> ● Arm-Band<br/> ● Earphone Detangler",
                    'products'=>['Tote Bag','Bottle','Towel','Shoe Bag','T-Shirt','Arm-Band','Earphone Detangler'],
                    'image'=>'https://b.fitn.in/global/Rewards-page/zumba.png'
                ],
                [
                    'title'=>'Yoga & Pilates',
                    'description'=>"Lifts and Squats is all you need to think about as we have your workout gear ready - <br/> ● Gym Bag <br/> ● Shaker<br/> ● Arm Band<br/> ● T-Shirt<br/> ● Towel<br/> ● Bottle<br/> ● Earphone Detangler",
                    'products'=>['Gym Bag','Shaker','Arm Band','T-Shirt','Towel','Bottle','Earphone Detangler'],
                    'image'=>'https://b.fitn.in/global/Rewards-page/yoga%26pilates.png'
                ],
            ]
        ];

        $data['rewards'][] = [
            'title'=>'Online Diet Consultation',
            'description'=>'Eating right is 70% & workout is 30% of leading a healthy & fit lifestyle! Fitternity’s got you covered 100% cover.',
            'type'=>'diet_plan',
            'items'=>[
                [
                    'title'=>'',
                    'description'=>"Get a detailed diet plan from out expert dietitian for better workout performance & faster goal achivement.<br/><br/>You will get: <br/> ● Telephonic consultation with your dietician<br/> ● Personalised & customised diet plan<br/> ● Regular follow-ups & progress tracking<br/> ● Healthy recepies & hacks",
                    'products'=>['Telephonic consultation with your dietician','Personalised & customised diet plan','Regular follow-ups & progress tracking','Healthy recepies & hacks'],
                    'image'=>'https://b.fitn.in/gamification/reward/diet_plan.jpg'
                ]
            ]

        ];

        $data['rewards'][] = [
            'title'=>'Instant Cashback',
            'description'=>'Who doesn’t love some money in their wallet? Get 5% back on your purchase!',
            'type'=>'cashback',
            'items'=>[
                [
                    'title'=>'',
                    'description'=>"Get upto Rs 2500 Fitcash+ in your wallet as cashback which is fully redeemable against any Memberships/Session & Diet Plan purchase on Fitternity. Validity of the cashback varies on the amount and duration of the membership. Cashback chosen as reward can be availed for renewal.",
                    'products'=>[],
                    'image'=>'https://b.fitn.in/gamification/reward/cashback1.jpg'
                ]
            ]

        ];

        return Response::json($data);

    }


    public function getCustomMembershipDetails(){

        $decodeKioskVendorToken = decodeKioskVendorToken();

        $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

        $finder_id = (int)$vendor['_id'];

        $data = [];

        $active_service_category_id = Service::active()->where('finder_id',$finder_id)->lists('servicecategory_id');

        $data['service_categories'] = [];

        if(!empty($active_service_category_id)){    

            $active_service_category_id  = array_map('intval',$active_service_category_id);

            $service_categories_array = Servicecategory::active()->whereIn('_id',$active_service_category_id)->where('parent_id',0)->lists('name','_id');

            foreach ($service_categories_array as $key => $value) {

                $array = [
                    'id'=>(int) $key,
                    'name'=>$value
                ];

                $data['service_categories'][] = $array;           
            }
        }

        $data['validity_type'] = [
            [
                'id'=>'day',
                'name'=>'Day'
            ],
            [
                'id'=>'month',
                'name'=>'Month'
            ],
            [
                'id'=>'year',
                'name'=>'Year'
            ]
        ];

        $data['validity'] = [];
        
        for ($i=1; $i <= 30; $i++) {

            $array = [
                'id'=> $i,
                'name'=> (string) $i
            ];

            $data['validity'][] = $array;
        }

        $data['sale_done_by'] = $this->utilities->getVendorTrainer($finder_id);

        $data['vendor_membership_type'] = [
            [
                'id'=>'gym_studio',
                'name'=>'Gym / Fitness Studio'
            ],
            [
                'id'=>'pt',
                'name'=>'Personal Trainer'
            ]
        ];

        return Response::json($data);

    }

    public function generateAmazonUrl(){
        $config = Config::get('amazonpay.config');
        $client = new PWAINBackendSDK($config);
        $post_params = Input::all();
        Log::info(Input::all());
        if(isset($post_params["order_id"])){
            $order = Order::find((int) $post_params["order_id"] );
            Log::info($order);
            $val['orderTotalAmount'] = $order->amount;
            $val['sellerOrderId'] = $order->txnid;
        }else{
            $val['orderTotalAmount'] = $post_params['orderTotalAmount'];
        }
        $val['orderTotalCurrencyCode'] = "INR";
        // $val['transactionTimeout'] = Config::get('amazonpay.timeout');
        // For testing in sandbox mode, remove for production
        // $val['isSandbox'] = Config::get('app.amazonpay_isSandbox');
        $returnUrl = Config::get('app.url')."/verifyamazonchecksum/1";
        // $returnUrl = "http://ar-deepthi.com/amazonpay/thankyou.php";
        $redirectUrl = $client->getProcessPaymentUrl($val, $returnUrl);
        return $redirectUrl;
    }

    public function generateAmazonChecksum(){
        
        $config = Config::get('amazonpay.config');
        
        $client = new PWAINBackendSDK($config);
        // Request can be either GET or POST
        $val = ($_POST);
        // For testing in sandbox mode, remove for production
        // $val['isSandbox'] = "true";
        // $val['isSandbox'] = Config::get('app.amazonpay_isSandbox');
        
        unset($val['sellerId']);
        $response = $client->generateSignatureAndEncrypt($val);
        return $response;
    }

    public function verifyAmazonChecksum($website = false){ 


        $config = Config::get('amazonpay.config');

        $client = new PWAINBackendSDK($config);

        // Request can be either GET or POST
        Log::info(Input::all());

        $val = Input::all();
        Log::info("verifyAmazonChecksum post data ---------------------------------------------------------",$val);
        unset($val['sellerId']);
        $response = $client->verifySignature($val);
        $val['isSignatureValid'] = $response ? 'true' : 'false';

        $val['order_id'] = null;
        
        // $val['isSignatureValid'] = 'true';
        
        if($val['isSignatureValid'] == 'true'){

            $order = Order::where('txnid',$val['sellerOrderId'])->first();

            if($order){

                $order->pg_type = "AMAZON";
                $order->amazon_hash = $val["hash"] = getpayTMhash($order->toArray())['reverse_hash'];
                $order->update();

                $val['order_id'] = $order->_id;

                $success_data = [
                    'txnid'=>$order['txnid'],
                    'amount'=>(int)$val["orderTotalAmount"],
                    'status' => 'success',
                    'hash'=> $val["hash"]
                ];
                if($website == "1"){
                    $url = Config::get('app.website')."/paymentsuccess?". http_build_query($success_data, '', '&');
                    Log::info(http_build_query($success_data, '', '&'));
                    Log::info($url);
                    return Redirect::to($url);
                }else{
                    $paymentSuccess = $this->fitweb->paymentSuccess($success_data);
                }
            }

           /* $order->pg_type = "AMAZON";
            $order->amazon_hash = $val["hash"] = getpayTMhash($order->toArray())['reverse_hash'];
            $order->update();
            
            

            Log::info("success_data--------------------------------------------------------------------",$success_data);
            
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL,Config::get('app.website')."/paymentsuccessandroid");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($success_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'Content-Length: ' . strlen(json_encode($success_data)))                                                                       
            );
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);


            Log::info("httpcode--------------------------------------------------------------------".$httpcode);



            if($httpcode == 200){
                $val['isSignatureValid'] = 'true';
            }else{
                $val['isSignatureValid'] = 'false';  
            }*/
            
            //$resp = curl_exec ($ch);

            //Log::info("Success api response--------------------------------------------------------------------");
            //Log::info($resp);


            if(isset($paymentSuccess['status']) && $paymentSuccess['status'] == 200){
                $val['isSignatureValid'] = "true";
            }else{
                $val['isSignatureValid'] = "false";
                
            }
        }

        return Response::json($val);
    }

    public function getServiceData(){

        $ratecard_count = 1;

        $service_id = 9096;

        $getRatecardCount = $this->fitapi->getServiceData($service_id);

        if($getRatecardCount['status'] != 200){

            $ratecard_count = 0;

        }else{

            if(!isset($getRatecardCount['ratecards'])){
                $ratecard_count = 0;
            }

            if(isset($getRatecardCount['ratecards']) && empty($getRatecardCount['ratecards'])){
                $ratecard_count = 0;
            }
        }

        return $ratecard_count;

    }
    

}