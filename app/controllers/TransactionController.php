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

    public function __construct(
        CustomerMailer $customermailer,
        CustomerSms $customersms,
        Sidekiq $sidekiq,
        FinderMailer $findermailer,
        FinderSms $findersms,
        Utilities $utilities,
        CustomerReward $customerreward,
        CustomerNotification $customernotification
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
        $this->ordertypes           =   array('memberships','booktrials','workout-session','healthytiffintrail','healthytiffinmembership','3daystrial','vip_booktrials', 'events');
        $this->appOfferDiscount     =   Config::get('app.app.discount');
        $this->appOfferExcludedVendors 				= Config::get('app.app.discount_excluded_vendors');

        $this->membership_array     =   array('memberships','healthytiffinmembership');

    }

    public function capture(){

        $data = Input::json()->all();


        foreach ($data as $key => $value) {

            if(is_string($value)){
                $data[$key] = trim($value);
            }
        }

        Log::info('------------transactionCapture---------------',$data);

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

        // if($data['type'] == 'diet_plan'){
        //     $diet_plan_rules = array(
        //         'offering_type'=>'required'
        //     );

        //     $rules = array_merge($rules,$diet_plan_rules);
        // }

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

        $order = false;
        if(isset($data['order_id'])){

            $old_order_id = $order_id = $data['_id'] = (int)$data['order_id'];

            $order = Order::find((int)$old_order_id);

            $data['repetition'] = 1;
            if($order && isset($order->repetition)){
                $data['repetition'] = $order->repetition + 1;
            }

        }else{
            $order_id = $data['_id'] = $data['order_id'] = Order::max('_id') + 1;
        }

        $data['code'] = $data['order_id'].str_random(8);

        if(isset($data['referal_trial_id'])){

            $data['referal_trial_id'] = (int) $data['referal_trial_id'];
        }

        $cashbackRewardWallet =$this->getCashbackRewardWallet($data,$order);

        if($cashbackRewardWallet['status'] != 200){
            return Response::json($cashbackRewardWallet,$cashbackRewardWallet['status']);
        }

        $data = array_merge($data,$cashbackRewardWallet['data']);

        $txnid = "";
        $successurl = "";
        $mobilehash = "";
        if($data['customer_source'] == "android" || $data['customer_source'] == "ios"){
            $txnid = "MFIT".$data['_id'];
            if(isset($old_order_id)){
                $txnid = "MFIT".$data['_id']."-R".$data['repetition'];
            }
            $successurl = $data['customer_source'] == "android" ? Config::get('app.website')."/paymentsuccessandroid" : Config::get('app.website')."/paymentsuccessios";
        }else{
            $txnid = "FIT".$data['_id'];
            if(isset($old_order_id)){
                $txnid = "FIT".$data['_id']."-R".$data['repetition'];
            }
            $successurl = $data['type'] == "memberships" ? Config::get('app.website')."/paymentsuccess" : Config::get('app.website')."/paymentsuccesstrial";
        }
        $data['txnid'] = $txnid;
        $hash = getHash($data);

        $data = array_merge($data,$hash);

        $data = $this->unsetData($data);

        $data['service_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']);

        $data['payment_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/paymentlink/".$data['order_id']);

        if(in_array($data['type'],$this->membership_array) && isset($data['ratecard_id']) && $data['ratecard_id'] != ""){
            $data['payment_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']."/".$data['ratecard_id']."/".$data['order_id']);
        }

        $data['vendor_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/".$data['finder_slug']);

        $data['profile_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);

        if(in_array($data['type'],$this->membership_array) && isset($data['start_date'])){

            $data["auto_followup_date"] = date('Y-m-d H:i:s', strtotime("+31 days",strtotime($data['start_date'])));
            $data["followup_status"] = "abandon_cart";
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
        $result['full_payment_wallet'] = $data['full_payment_wallet'];

        if(in_array($data['type'],$this->membership_array)){
            $redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),'booktrial');
            $order->update(array('redis_id'=>$redisid));
        }

        $resp   =   array(
            'status' => 200,
            'data' => $result,
            'message' => "Tmp Order Generated Sucessfully"
        );

        return Response::json($resp);

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

            }

            if($order->status == "1" && isset($data['updrage_membership']) && $data['updrage_membership'] == "requested"){

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

            array_set($data, 'auto_followup_date', date('Y-m-d H:i:s', strtotime("+7 days",strtotime($order['start_date']))));
            array_set($data, 'followup_status', 'catch_up');
            array_set($data, 'followup_status_count', 1);

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

            if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !in_array($finder->category_id, $abundant_category) && $order->type == "memberships" && !isset($order->customer_sms_after3days) && !isset($order->customer_email_after10days) && $order->type != 'diet_plan'){

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

            if(isset($order->city_id)){

                $city = City::find((int)$order->city_id,['_id','name','slug']);

                $order->update(['city_name'=>$city->name,'city_slug'=>$city->slug]);
            }

            if(isset($order->finder_category_id)){

                $category = Findercategory::find((int)$order->finder_category_id,['_id','name','slug']);

                $order->update(['category_name'=>$category->name,'category_slug'=>$category->slug]);
            }

            if(isset($order->diet_plan_ratecard_id) && $order->diet_plan_ratecard_id != "" && $order->diet_plan_ratecard_id != 0){
            
                $generaterDietPlanOrder = $this->generaterDietPlanOrder($order->toArray());

                if($generaterDietPlanOrder['status'] != 200){
                    return Response::json($generaterDietPlanOrder,$generaterDietPlanOrder['status']);
                }

                $order->diet_plan_order_id = $generaterDietPlanOrder['order_id'];
                $order->update();

            }

            if(isset($order->type) && $order->type == "diet_plan"){
                return $generaterDietPlanOrder = $this->createDietPlanOrder($order->toArray());
            }
            $this->utilities->setRedundant($order);

            Log::info("Customer for referral");
            $customer = Customer::where('_id', $order['customer_id'])->first(['referred', 'referrer_id', 'first_transaction']);
            Log::info($customer);
            
            if(isset($customer['referred']) && $customer['referred'] && $customer['first_transaction']){
                Log::info("inside first transaction");
                $referrer = Customer::where('_id', $customer->referrer_id)->first();
                $customer->first_transaction = false;
                $customer->update();
                $wallet_data = array(
                                'customer_id' => $customer->referrer_id,
                                'amount' => 250,
                                'amount_fitcash' => 0,
                                'amount_fitcash_plus' => 250,
                                'type' => "REFERRAL",
                                'description' => "Referral fitcashplus to referrer",
                                'order_id' => 0
                                );
                $this->utilities->walletTransaction($wallet_data);
                $url = 'www.fitternity.com/profile/'.$referrer->email;
                $sms_data = array(
                    'customer_phone'=>$referrer->contact_no,
                    // 'friend_name'   =>$customer_name,
                    'wallet_url'    =>$url
                    );
                $referSms = $this->customersms->referralFitcash($sms_data);
            }
            
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

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
            return Response::json($resp);

        }else{
            if($hash_verified == false){
                $Oldorder 		= 	Order::findOrFail($orderid);
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

        if(isset($data['customer_address']) && is_array($data['customer_address']) && !empty($data['customer_address'])){
            
            $customerData['address'] = $data['customer_address'];
            $customer->update($customerData);

            $data['customer_address'] = $data['address'] = implode(",", array_values($data['customer_address']));
        }

        $device_type = (isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id = (isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

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

        if($data['type'] == "memberships" && isset($data['customer_source']) && ($data['customer_source'] == "android" || $data['customer_source'] == "ios")){
            $data['app_discount_amount'] = intval($data['amount'] * ($this->appOfferDiscount/100));
            $amount = $data['amount'] = $data['amount_customer'] = $data['amount'] - $data['app_discount_amount'];
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id']);
        }else{
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id']);
        }

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
                    'type'=>'DEBIT',
                    'entry'=>'debit',
                    'description'=> $this->utilities->getDescription($data),
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

                $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                $data['amount'] = $data['amount'] - $data['wallet_amount'];

                $req = array(
                    'customer_id'=>$data['customer_id'],
                    'order_id'=>$data['order_id'],
                    'amount'=>$data['wallet_amount'],
                    'type'=>'DEBIT',
                    'entry'=>'debit',
                    'description'=> $this->utilities->getDescription($data),
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

            }

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

    public function getCashbackRewardWalletOld($data,$order){

        Log::info('old');

        $jwt_token = Request::header('Authorization');

        $customer_id = $data['customer_id'];
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }

        $amount = $data['amount_customer'] = $data['amount'];

        if($data['type'] == "memberships" && isset($data['customer_source']) && ($data['customer_source'] == "android" || $data['customer_source'] == "ios")){
            $this->appOfferDiscount = in_array($data['finder_id'], $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;
            $data['app_discount_amount'] = intval($data['amount'] * ($this->appOfferDiscount/100));
            $amount = $data['amount'] = $data['amount_customer'] = $data['amount'] - $data['app_discount_amount'];
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id']);
        }else{
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id']);
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
            $data['amount_finder'] = $offer->price;
            $data['offer_id'] = $offer->_id;

            if(isset($offer->remarks) && $offer->remarks != ""){
                $data['ratecard_remarks'] = $offer->remarks;
            }
        }

        $data['amount'] = $data['amount_finder'];

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
        $data['source_of_membership'] =  'real time';

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

            /*if(isset($order['reg_id']) && $order['reg_id'] != "" && isset($order['device_type']) && $order['device_type'] != ""){
                $order->customerNotificationSendPaymentLinkAfter3Days = $this->customernotification->sendPaymentLinkAfter3Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+3 days",$now)));
                $order->customerNotificationSendPaymentLinkAfter7Days = $this->customernotification->sendPaymentLinkAfter7Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+7 days",$now)));
                $order->customerNotificationSendPaymentLinkAfter15Days = $this->customernotification->sendPaymentLinkAfter15Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+15 days",$now)));
                $order->customerNotificationSendPaymentLinkAfter30Days = $this->customernotification->sendPaymentLinkAfter30Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+30 days",$now)));
                $order->customerNotificationSendPaymentLinkAfter45Days = $this->customernotification->sendPaymentLinkAfter45Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+45 days",$now)));
            }*/

            $url = Config::get('app.url')."/addwallet?customer_id=".$order["customer_id"]."&order_id=".$order_id;

            $order->customerWalletSendPaymentLinkAfter15Days = $this->hitURLAfterDelay($url."&time=LPlus15", date('Y-m-d H:i:s', strtotime("+15 days",$now)));
            $order->customerWalletSendPaymentLinkAfter30Days = $this->hitURLAfterDelay($url."&time=LPlus30", date('Y-m-d H:i:s', strtotime("+30 days",$now)));

            $order->notification_status = 'abandon_cart_yes';

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
            'time'=>'required|in:LPlus15,LPlus30,F1Plus15,PurchaseFirst,RLMinus7,RLMinus1',
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
            $trialTime = ['F1Plus15'];

            $amountArray = [
                "LPlus15" => 150,
                "LPlus30" => 150,
                "F1Plus15" => 150,
                "PurchaseFirst" => 150,
                "RLMinus7" => 150,
                "RLMinus1" => 150
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
                $req['description'] = "Added FitCash+, Expires On : ".date('d-m-Y',time()+(86400*60));
                $req["validity"] = time()+(86400*60);
                $req['for'] = $time;

                $walletTransactionResponse = $this->utilities->walletTransaction($req);

                if($walletTransactionResponse['status'] == 200){

                    $customer->update(["added_fitcash_plus" => time()]);

                    $top_up = true;
                }

            }

            if(isset($data['order_id'])){
                $req['order_id'] = (int)$data['order_id'];

                $transaction = Order::find((int)(int)$data['order_id']);
            }

            if(isset($data['booktrial_id'])){
                
                $req['booktrial_id'] = (int)$data['booktrial_id'];

                $transaction = Booktrial::find((int)(int)$data['order_id']);
            }

            if($transaction && $wallet_balance > 0){

                $transaction = $transaction->toArray();
                
                $transaction['wallet_balance'] = $wallet_balance;
                $transaction['top_up'] = $top_up;

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
                        /*if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendRenewalPaymentLinkBefore7Days($transaction,0);
                        }*/
                        break;
                    case 'RLMinus1':
                        $this->customersms->sendRenewalPaymentLinkBefore1Days($transaction,0);
                        /*if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendRenewalPaymentLinkBefore1Days($transaction,0);
                        }*/
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

    public function createDietPlanOrder($data){
        Log::info('inside createDietPlanOrder');
        $data['renewal'] = "no";
        $data['final_assessment'] = "no";

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

        $data = $this->unsetData($data);

        $data['status'] = "1";
        $data['order_action'] = "bought";
        $data['success_date'] = date('Y-m-d H:i:s',time());

        $order = Order::FindOrFail($data['_id']);
        $order->update($data);
        $order_id = $order->_id;
        // $this->customermailer->sendDietPgCustomer($data);
        return array('order_id'=>$order_id,'status'=>200,'message'=>'Diet Plan Order Created Sucessfully');
    }

}