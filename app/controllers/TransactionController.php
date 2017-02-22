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


class TransactionController extends \BaseController {

    protected $customermailer;
    protected $customersms;
    protected $sidekiq;
    protected $findermailer;
    protected $findersms;
    protected $utilities;
    protected $customerreward;

    public function __construct(
        CustomerMailer $customermailer,
        CustomerSms $customersms,
        Sidekiq $sidekiq,
        FinderMailer $findermailer,
        FinderSms $findersms,
        Utilities $utilities,
        CustomerReward $customerreward
    ) {
        parent::__construct();
        $this->customermailer       =   $customermailer;
        $this->customersms          =   $customersms;
        $this->sidekiq              =   $sidekiq;
        $this->findermailer         =   $findermailer;
        $this->findersms            =   $findersms;
        $this->utilities            =   $utilities;
        $this->customerreward       =   $customerreward;
        $this->ordertypes       =   array('memberships','booktrials','workout-session','healthytiffintrail','healthytiffinmembership','3daystrial','vip_booktrials', 'events');
        $this->appOfferDiscount 	= Config::get('app.app.discount');;

    }

    public function capture(){

        $data = Input::json()->all();

        if(!isset($data['type'])){
            return Response::json(array('status' => 404,'message' =>'type field is required'),404);
        }

        $rules = array(
            'customer_name'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required',
            'customer_source'=>'required',
            'ratecard_id'=>'required|integer|min:1',
            'type'=>'required'
        );

        $workout = array('vip_booktrials','3daystrial','booktrials','workout-session');
        if(in_array($data['type'],$workout)){

            $workout_rules = array(
                'schedule_date'=>'required',
                'schedule_slot'=>'required'
            );

            $rules = array_merge($rules,$workout_rules);
        }

        $membership = array('healthytiffintrail','healthytiffinmembership','memberships');
        if(in_array($data['type'],$membership)){
            $membership_rules = array(
                'preferred_starting_date'=>'required'
            );

            $rules = array_merge($rules,$membership_rules);
        }

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }

        $customerDetail = $this->getCustomerDetail($data);

        if($customerDetail['status'] != 200){
            return Response::json($customerDetail,$customerDetail['status']);
        }

        $data = array_merge($data,$customerDetail['data']); 
          
        $ratecardDetail = $this->getRatecardDetail($data);

        if($ratecardDetail['status'] != 200){
            return Response::json($ratecardDetail,$ratecardDetail['status']);
        }

        $data = array_merge($data,$ratecardDetail['data']);

        $ratecard_id = (int) $data['ratecard_id'];
        $finder_id = (int) $data['finder_id'];
        $service_id = (int) $data['service_id'];

        $finderDetail = $this->getFinderDetail($finder_id);

        if($finderDetail['status'] != 200){
            return Response::json($finderDetail,$finderDetail['status']);
        }

        $data = array_merge($data,$finderDetail['data']);

        $serviceDetail = $this->getServiceDetail($service_id);

        if($serviceDetail['status'] != 200){
            return Response::json($serviceDetail,$serviceDetail['status']);
        }

        $data = array_merge($data,$serviceDetail['data']);

        $order_id = $data['_id'] = $data['order_id'] = Order::max('_id') + 1;

        $cashbackRewardWallet =$this->getCashbackRewardWallet($data);

        if($cashbackRewardWallet['status'] != 200){
            return Response::json($cashbackRewardWallet,$cashbackRewardWallet['status']);
        }

        $data = array_merge($data,$cashbackRewardWallet['data']);

        $txnid = "";
        $successurl = "";
        $mobilehash = "";
        if($data['customer_source'] == "android" || $data['customer_source'] == "ios"){
            $txnid = "MFIT".$data['_id'];
            $successurl = $data['customer_source'] == "android" ? Config::get('app.website')."/paymentsuccessandroid" : Config::get('app.website')."/paymentsuccessios";
        }else{
            $txnid = "FIT".$data['_id'];
            $successurl = $data['type'] == "memberships" ? Config::get('app.website')."/paymentsuccess" : Config::get('app.website')."/paymentsuccesstrial";
        }
        $data['txnid'] = $txnid;
        $hash = $this->getHash($data);

        $data = array_merge($data,$hash);

        $data = $this->unsetData($data);

        $order = new Order($data);
        $order->_id = $order_id;
        $order->save();

        if($data['customer_source'] == "android" || $data['customer_source'] == "ios"){
            $mobilehash = $data['payment_related_details_for_mobile_sdk_hash'];
        }
        if(isset($data['myreward_id']) && $data['type'] == "workout-session"){
            $data['amount'] = 0;
        }
        $result['firstname'] = $data['customer_name'];
        $result['lastname'] = "";
        $result['phone'] = $data['customer_phone'];
        $result['email'] = $data['customer_email'];
        $result['orderid'] = $data['_id'];
        $result['txnid'] = $txnid;
        $result['amount'] = $data['amount'];
        $result['productinfo'] = $data['productinfo'];
        $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', $data['service_name']);
        $result['successurl'] = $successurl;
        $result['hash'] = $data['payment_hash'];
        $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;


        $resp   =   array(
            'status' => 200,
            'data' => $result,
            'message' => "Tmp Order Generated Sucessfully"
        );

        return Response::json($resp);

    }

    public function update(){

        $rules = array(
            "customer_name"=>"required",
            "customer_email"=>"email|required",
            "customer_phone"=>"required",
            "payment_mode"=>"required",
            "order_id"=>"numeric|required"
        );

        $data = Input::json()->all();

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {

            return Response::json(array('status' => 401,'message' => $this->errorMessage($validator->errors())),401);

        }else{

            $order_id = (int) $data['order_id'];

            $order = array();

            $order = Order::find($order_id);

            if(count($order) < 1){

                $resp   =   array("status" => 401,"message" => "Order Does Not Exists");
                return Response::json($resp,$resp["status"]);
            }

            if(isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){

                $resp   =   array("status" => 401,"message" => "You have purchased this membership");
                return Response::json($resp,$resp["status"]);
            }

            if(isset($order->cashback) && $order->cashback == true && isset($order->status) && $order->status == "1"){

                $resp   =   array("status" => 401,"message" => "We have already received your request");
                return Response::json($resp,$resp["status"]);
            }

            if(isset($order->reward_ids) && count($order->reward_ids) > 0 && isset($order->status) && $order->status == "1"){

                $resp   =   array("status" => 401,"message" => "We have already received your request");
                return Response::json($resp,$resp["status"]);
            }


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
        /*if(!isset($data["order_success_flag"]) && isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){

            $resp   =   array('status' => 401, 'statustxt' => 'error', 'order' => $order, "message" => "Already Status Successfull");
            return Response::json($resp,401);

        }elseif(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && isset($order->status) && $order->status != '1' && isset($order->order_action) && $order->order_action != 'bought'){

            $resp   =   array('status' => 401, 'statustxt' => 'error', 'order' => $order, "message" => "Status should be Bought");
            return Response::json($resp,401);
        }*/

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

        array_set($data, 'status', '1');
        array_set($data, 'order_action', 'bought');
        array_set($data, 'success_date', date('Y-m-d H:i:s',time()));
            
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

        if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
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
                if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                    if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                        $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                    }

                }else{
                    $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                }
            }

            //no email to Healthy Snacks Beverages and Healthy Tiffins
            if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" ){
                
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
            
            if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                    $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                }

            }else{
                $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
            }
        }

        //no sms to Healthy Snacks Beverages and Healthy Tiffins
        if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" ){
            
            if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                    $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                }
                
            }else{
                $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
            }
            
        }

        if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !in_array($finder->category_id, $abundant_category) && $order->type == "memberships" && !isset($order->customer_sms_after3days) && !isset($order->customer_email_after10days)){

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

        }

        if(isset($order->diet_plan_ratecard_id) && $order->diet_plan_ratecard_id != "" && $order->diet_plan_ratecard_id != 0){

            $generaterDietPlanOrder = $this->generaterDietPlanOrder($order->toArray());

            if($generaterDietPlanOrder['status'] != 200){
                return Response::json($generaterDietPlanOrder,$generaterDietPlanOrder['status']);
            }

            $order->diet_plan_order_id = $generaterDietPlanOrder['order_id'];

            $order->update();
        }

        $resp   =   array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");

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

        $customer_id = $data['customer_id'] = autoRegisterCustomer($data);

        $customer = Customer::find((int)$customer_id);

        if(isset($data['address']) && $data['address'] != ''){

            $data['customer_address']  = $data['address'];
        }

        if(isset($data['customer_address']) && is_array($data['customer_address']) && !empty($data['customer_address'])){
            
            $customerData['address'] = $data['customer_address'];
            $customer->update($customerData);

            $data['customer_address'] = $data['address'] = implode(",", array_values($data['customer_address']));
        }

        $device_type = (isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id = (isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

        if($device_type != '' && $gcm_reg_id != ''){

            $reg_data = array();

            $reg_data['customer_id'] = $customer_id;
            $reg_data['reg_id'] = $gcm_reg_id;
            $reg_data['type'] = $device_type;

            $this->addRegId($reg_data);
        }

        return array('status' => 200,'data' => $data);

    }

    public function getCashbackRewardWallet($data){

        $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id']);

        if(isset($data['wallet']) && $data['wallet'] == true){
            $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
            $data['amount'] = $data['amount'] - $data['wallet_amount'];
        }

        if(isset($data['cashback']) && $data['cashback'] == true){
            $data['amount'] = $data['amount'] - $data['cashback_detail']['amount_discounted'];
        }

        if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

            $req = array(
                'customer_id'=>$data['customer_id'],
                'order_id'=>$data['order_id'],
                'amount'=>$data['wallet_amount'],
                'type'=>'DEBIT',
                'description'=>'Paid for Order ID: '.$data['order_id'],
            );
            $walletTransactionResponse = $this->utilities->walletTransaction($req)->getData();
            $walletTransactionResponse = (array) $walletTransactionResponse;

            if($walletTransactionResponse['status'] != 200){
                return $walletTransactionResponse;
            }

            // Schedule Check orderfailure and refund wallet amount in that case....
            $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'];
            $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
            $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

        }

        if(isset($data['reward_ids'])&& count($data['reward_ids']) > 0) {
            $data['reward_ids']   =  array_map('intval', $data['reward_ids']);
        }

        return array('status' => 200,'data' => $data); 

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

    public function addRegId($data){

        $response = add_reg_id($data);

        return Response::json($response,$response['status']);
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

        $offer = Offer::where('ratecard_id',$ratecard['_id'])->where('hidden', false)->where('end_date','>=',new DateTime(date("d-m-Y 00:00:00")))->first();

        if($offer){
            $data['amount_finder'] = $offer->price;
            $data['offer_id'] = $offer->_id;

            if(isset($offer->remarks) && $offer->remarks != ""){
                $data['ratecard_remarks'] = $offer->remarks;
            }
        }

        if(isset($data['diet_plan_ratecard_id']) && $data['diet_plan_ratecard_id'] != "" && $data['diet_plan_ratecard_id'] != 0){

            $getDietPlanAmount = $this->getDietPlanAmount($data['diet_plan_ratecard_id']);

            if($getDietPlanAmount['status'] != 200){
                return $getDietPlanAmount;
            }

            $data['amount_finder'] = $getDietPlanAmount['amount'] + $data['amount_finder'];
        }

        $data['amount'] = $data['amount_finder'];

        if($data['type'] == "memberships" && isset($data['customer_source']) && ($data['customer_source'] == "android" || $data['customer_source'] == "ios")){
            $data['amount'] = intval($data['amount'] - ($data['amount'] * ($this->appOfferDiscount/100)));
            $data['appOffer'] = $this->appOfferDiscount."% Off on purchases from android and iOS";
        }

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
            $data['end_time']= trim($schedule_slot[1]);
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
            'diet_plan'=>'short_term_membership',
        );

        (isset($set_vertical_type[$data['type']])) ? $data['vertical_type'] = $set_vertical_type[$data['type']] : null;

        (isset($data['finder_category_id']) &&  $data['finder_category_id'] == 41) ? $data['vertical_type'] = 'trainer' : null;

        (isset($set_membership_duration_type[$data['type']])) ? $data['membership_duration_type'] = $set_membership_duration_type[$data['type']] : null;

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

        return array('status' => 200,'data' =>$data);

    }


    public function stripTags($string){
        return ucwords(str_replace("&nbsp;","",strip_tags($string)));
    }

    public function getFinderDetail($finder_id){

        $data = array();

        $finder                            =   Finder::active()->with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->find((int)$finder_id);

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

        $service_name = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', $data['service_name']);
        $finder_name = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', $data['finder_name']);

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
        
        Log::info($payhash_str);

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

    public function getDietPlanAmount($ratecard_id){
        
        $ratecard = Ratecard::find($ratecard_id);

        if(!$ratecard){
            return array('status' => 404,'message' => 'Diet Plan Ratecard not found');
        }

        if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
            $amount = $ratecard['special_price'];
        }else{
            $amount = $ratecard['price'];
        }

        return array('status' => 200,'amount' => $amount);
    }

    public function getOrderDetails($order){

        //$order = Order::find((int)$order_id);

        $referal_order = [];

        $referal_order['order_id'] =  $order['order_id'];
        $referal_order['city_id'] =  $order['city_id'];
        $referal_order['city_name'] =  $order['city_name'];
        $referal_order['city_slug'] = $order['city_slug'];
        $referal_order['finder_id'] =  $order['finder_id'];
        $referal_order['finder_name'] =  $order['finder_name'];
        $referal_order['finder_slug'] =  $order['finder_slug'];
        $referal_order['ratecard_id'] =  (isset($order['ratecard_id']) && $order['ratecard_id'] != '') ? $order['ratecard_id'] : "";
        $referal_order['service_id'] =  $order['service_id'];
        $referal_order['service_name'] =  $order['service_name'];
        $referal_order['service_duration'] =  $order['service_duration'];

        return $referal_order;


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
        $data['city_name'] =  $order['city_name'];
        $data['city_slug'] = $order['city_slug'];
        $data['offering_type'] = "cross_sell";

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

        //$redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),'booktrial');
        //$order->update(array('redis_id'=>$redisid));

        return array('order_id'=>$order_id,'status'=>200,'message'=>'Diet Plan Order Created Sucessfully');
    }


    public  function sendCommunication($job,$data){

        $job->delete();

        try {

            $order_id = (int)$data['order_id'];

            $order = Order::find($order_id)->toArray();


            
        } catch (Exception $e) {
            
        }

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

}