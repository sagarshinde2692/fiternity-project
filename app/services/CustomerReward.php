<?PHP namespace App\Services;
use Myreward;
use Reward;
use Offer;
use Coupon;
use App\Services\Utilities;
use Validator;
use Response;
use Log;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Sms\FinderSms as FinderSms;
use Myrewardcapture;
use Rewardcategory;
use Request;
use Customerwallet;
use VendorCommercial;
use Config;
use JWT;
use Finder;
use Input;


Class CustomerReward {

    public function __construct() {

    }

    public function createMyReward($data){

        $utilities = new Utilities;

        $data = !is_array($data) ? $data->toArray() : $data;

        $rules = array(
            'reward_ids'=>'required|array',
            'booktrial_id'=>'required_without:order_id|integer',
            'order_id'=>'required_without:booktrial_id|integer',
            'customer_name'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required'
        );

        $validator1 = Validator::make($data,$rules);
        if ($validator1->fails()) {
            return Response::json(
                array(
                'status' => 404,
                'message' =>$utilities->errorMessage($validator1->errors())),404
            );
        }

        $rewards = Reward::findMany($data['reward_ids']);

        if(count($rewards) == 0){
            return Response::json(array("status" => 422,"message" => "Unprocessible Entity"),422);
        }

        $finderData = array();
        if(isset($data['finder_id']) && $data['finder_id'] != ""){

            $finder_id = (int) $data['finder_id'];

            $finderData = $this->getFinderData($finder_id);
           
        }


        foreach ($rewards as $reward){

            $reward = $reward->toArray();

            $reward  = array_merge($reward,$finderData);

            $reward = array_except($reward, [ 'created_at','updated_at','status','rewrardoffers']);
            $reward['customer_id']      =   (int)$data['customer_id'];
            $reward['customer_name']    =   $data['customer_name'];
            $reward['customer_email']   =   $data['customer_email'];
            $reward['customer_phone']   =   $data['customer_phone'];
            $reward['claimed']          =   0;

            if($reward['reward_type'] == 'personal_trainer_at_studio' && isset($finderData['finder_name']) && isset($finderData['finder_location'])){
                $reward['title'] = "Personal Training At ".$finderData['finder_name']." (".$finderData['finder_location'].")";
            }

            $reward['reward_id']        =   $reward['_id'];
            $reward['rewardcategory_id']        =   $reward['rewardcategory_id'];

            isset($data['booktrial_id']) ? $reward['booktrial_id'] = (int) $data['booktrial_id'] : null;
            isset($data['order_id']) ? $reward['order_id'] = (int) $data['order_id'] : null;

            $this->saveToMyRewards($reward);
        }
    }



    public function saveToMyRewards($reward){

        $reward['status']         = "0";
        $myreward               =   new Myreward($reward);
        $last_insertion_id      =   Myreward::max('_id');
        $last_insertion_id      =   isset($last_insertion_id) ? $last_insertion_id :0;
        $myreward->_id          =   ++ $last_insertion_id;
        $myreward->save();

        if(isset($reward['booktrial_id'])){

            $booktrial = \Booktrial::find($reward['booktrial_id']);
            if($booktrial){
                $booktrial->customer_reward_id = $myreward->_id;
                $booktrial->update();
            }
        }

        if(isset($reward['order_id'])){

            $order = \Order::find($reward['order_id']);
            if($order){
                $order->customer_reward_id = $myreward->_id;
                $order->update();
            }
        }
        
        return;
    }

    public function giveCashbackOrRewardsOnOrderSuccess($order){

        $utilities          =   new Utilities;
        $valid_ticket_ids   =   [99,100, 262];

        try{
            // For Cashback.....
            if(isset($order['cashback']) && $order['cashback'] == true && isset($order['cashback_detail']['wallet_amount'])){

                $customerWallet = Customerwallet::where("order_id",(int)$order['_id'])->where("type","CASHBACK")->get();

                if(count($customerWallet) > 0){
                    return 'true';
                }

                $cashback_amount = $order['cashback_detail']['wallet_amount'];

                /*if($order['payment_mode'] = "at the studio"){
                    $cashback_amount = $order['amount_finder'] * 5 / 100;
                }*/
                $duration_day = isset($order["duration_day"]) ? $order["duration_day"] : 0;
                if(isset($order['ratecard_id']) && $order['ratecard_id'] != "" && $order['ratecard_id'] != null){

                    $ratecard = \Ratecard::find((int)$order['ratecard_id']);

                    if($ratecard){

                        $duration_day = 0;
                        if(isset($ratecard['validity']) && $ratecard['validity'] != ""){

                            switch ($ratecard['validity_type']){
                                case 'days': 
                                    $duration_day = (int)$ratecard['validity'];break;
                                case 'months': 
                                    $duration_day = (int)($ratecard['validity'] * 30) ; break;
                                case 'year': 
                                    $duration_day = (int)($ratecard['validity'] * 30 * 12); break;
                                default : $duration_day =  $ratecard['validity']; break;
                            }
                        }
                    }
                }

                $duration_day += 60; 

                $req = array(
                    "customer_id"=>$order['customer_id'],
                    "order_id"=>$order['_id'],
                    "amount"=>$cashback_amount,
                    "amount_fitcash" => $cashback_amount,
                    "amount_fitcash_plus" => 0,
                    "type"=>'CASHBACK',
                    'entry'=>'credit',
                    'description'=>"5% Cashback for purchase of membership at ".ucwords($order['finder_name'])." (Order ID. ".$order['_id']."), Expires On : ".date('d-m-Y',time()+(86400*$duration_day)),
                    "validity"=>time()+(86400*$duration_day)
                );

                $utilities->walletTransaction($req,$order->toArray());

                $order->update(array('cashback_amount'=>$cashback_amount));

            }elseif(isset($order['reward_id']) && is_array($order['reward_id']) && !empty($order['reward_id'])){

                $myReward = Myreward::where("order_id",(int)$order['_id'])->get();

                if(count($myReward) > 0){
                    return 'true';
                }

                $order['order_id'] = $order['_id'];
                $order['reward_ids'] = $order['reward_id'];

                $order->update(array('reward_ids'=>$order['reward_id']));
                
                $this->createMyReward($order);

            }elseif(isset($order['reward_ids']) && !empty($order['reward_ids'])){

                $myReward = Myreward::where("order_id",(int)$order['_id'])->get();

                if(count($myReward) > 0){
                    return 'true';
                }

                $order['order_id'] = $order['_id'];
                $this->createMyReward($order);

            }elseif(isset($order['type']) && in_array(trim($order['type']),['booktrials','healthytiffintrail']) && isset($order['customer_id']) && isset($order['amount_customer']) ){

                $walletData = array(
                    "order_id"=>$order['_id'],
                    "customer_id"=> intval($order['customer_id']),
                    "amount"=> intval($order['amount_customer'] * 20 / 100),
                    "amount_fitcash" => 0,
                    "amount_fitcash_plus" => intval($order['amount_customer'] * 20 / 100),
                    "type"=>'CASHBACK',
                    'entry'=>'credit',
                    "description"=> "20% Cashback for paid trial purchase at ".ucwords($order['finder_name'])." (Order ID. ".$order['_id']."), Expires On : ".date('d-m-Y',time()+(86400*60)),
                    "validity"=>time()+(86400*60)
                );

                $utilities->walletTransaction($walletData,$order->toArray());

            }elseif(isset($order['type']) && $order['type'] == 'events' && isset($order['customer_id']) && isset($order['amount']) && isset($order['ticket_id']) ){
                
                $customersms = new CustomerSms();

                $fitcash_plus = intval($order['amount']/5);

                if(isset($order['event_type']) && $order['event_type']=='TOI'){
                    $fitcash_plus = intval($order['amount']);
                    
                    if(isset($order['coupon_code'])){
                        $coupon = Coupon::where('code', strtolower($order['coupon_code']))->first();
                        Log::info("coupon");
                        
                        Log::info($coupon);
                        if($coupon){
                            if(isset($coupon->fitternity_only) && $coupon->fitternity_only){
                                $fitcash_plus = $order['amount_finder'];
                            }
                        }

                    }

                    if($fitcash_plus == 0){
                        return;
                    }
                    Log::info("cashback");
                }


                if(isset($order['event_id']) && $order['event_id'] == 108){

                    $fitcash_plus = $order['amount'];

                    if(isset($order['event_customers']) && count($order['event_customers']) > 0){

                        $fitcash_plus = intval($order['amount']/count($order['event_customers']));

                        $event_customers = $order["event_customers"];

                        unset($event_customers[0]);

                        if(count($event_customers) > 0){

                            $customerData = $order->toArray();

                            if(isset($customerData['event_id']) && $customerData['event_id'] != ''){

                                $event = \DbEvent::find(intval($customerData['event_id']));

                                if($event){

                                    $customerData['event'] = $event->toArray();
                                }
                            }

                            if(isset($customerData['ticket_id']) && $customerData['ticket_id'] != ''){

                                $ticket = \Ticket::find(intval($customerData['ticket_id']));

                                if($ticket){

                                    $customerData['ticket'] = $ticket->toArray();
                                }
                            }

                            foreach ($event_customers as $customer_data) {

                                $customer_id = autoRegisterCustomer($customer_data);

                                $walletData = array(
                                    "order_id"=>$order['_id'],
                                    "customer_id"=> intval($customer_id),
                                    "amount"=> $fitcash_plus,
                                    "amount_fitcash" => 0,
                                    "amount_fitcash_plus" => $fitcash_plus,
                                    "type"=>'CASHBACK',
                                    'entry'=>'credit',
                                    "description"=>'Cashback On Event Tickets - Morning Fitness Party' 
                                );

                                $utilities->walletTransaction($walletData,$order->toArray());

                                $customer_data['type'] = "events";
                                $customer_data['amount_20_percent'] = $fitcash_plus;
                                $customer_data['profile_link'] = $utilities->getShortenUrl(Config::get('app.website')."/profile/".$customer_data['customer_email']);

                                
                                $customerData['ticket_quantity'] = 1;
                                $customerData['customer_name'] = $customer_data['customer_name'];
                                $customerData['customer_phone'] = $customer_data['customer_phone'];
                                $customerData['customer_email'] = $customer_data['customer_email'];

                                $customersms->sendPgOrderSms($customerData);

                                if($fitcash_plus > 0){
                                    $customersms->giveCashbackOnTrialOrderSuccessAndInvite($customer_data);
                                }
                            }
                        }
                    }

                }

                $walletData = array(
                    "order_id"=>$order['_id'],
                    "customer_id"=> intval($order['customer_id']),
                    "amount"=> $fitcash_plus,
                    "amount_fitcash" => 0,
                    "amount_fitcash_plus" => $fitcash_plus,
                    "type"=>'CASHBACK',
                    'entry'=>'credit',
                    "description"=>'Cashback On Event Tickets amount - '.$fitcash_plus
                );

                if(isset($order['event_id']) && $order['event_id'] == 108){

                    $walletData["description"] = 'Cashback On Event Tickets - Morning Fitness Party';
                }

                $utilities->walletTransaction($walletData,$order->toArray());
                
                if($fitcash_plus > 0){
                    $customersms = new CustomerSms();
                    $order->amount_20_percent = $fitcash_plus;
                    $customersms->giveCashbackOnTrialOrderSuccessAndInvite($order->toArray());
                }            
            }
            
        }
        catch (Exception $e) {
            Log::info('Error : '.$e->getMessage());
        }
    }


    public function createMyRewardCapture($data){

        $utilities = new Utilities;

        $data = !is_array($data) ? $data->toArray() : $data;

        $rules = array(
            'myreward_id'=>'required',
            'customer_name'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required',
            'customer_id'=>'required'
        );

        $validator = Validator::make($data,$rules);
        if ($validator->fails()) {
            return array('status' => 404,'message' =>$utilities->errorMessage($validator->errors()));
        }

        $data['myreward_id'] = (int)$data['myreward_id'];

        $myreward = Myreward::find((int)$data['myreward_id']);

        if($myreward){

            $created_at = date('Y-m-d H:i:s',strtotime($myreward->created_at));

            $validity_date_unix = strtotime($created_at . ' +'.(int)$myreward->validity_in_days.' days');
            $current_date_unix = time();

            if($validity_date_unix < $current_date_unix){
                return array('status' => 404,'message' => "Validity Is Over");
            }

            $claim_all = array('personal_trainer_at_studio','personal_trainer_at_home','healthy_tiffin');
            
            if(!isset($myreward->claimed) || $myreward->claimed < $myreward->quantity){

                $claimed = (isset($myreward->claimed) && $myreward->claimed != "") ? $myreward->claimed : 0;

                $myreward->claimed = $claimed + 1;
                if(in_array($myreward->reward_type,$claim_all)){
                    $myreward->claimed = $myreward->quantity;
                }
                
                if($myreward->quantity == $myreward->claimed){
                    $myreward->status = "1";
                }

                if(isset($data['customer_address'])){
                    $myreward->customer_address = $data['customer_address'];
                }

                $myreward->success_date = date('Y-m-d H:i:s',time());

                $myreward->update();

                if(isset($myreward->finder_id) && $myreward->finder_id != ""){
                    $data['finder_id'] = (int) $myreward->finder_id;
                }

                if(isset($data['finder_id']) && $data['finder_id'] != ""){
                    $finderData = $this->getFinderData((int)$data['finder_id']);
                    $data  = array_merge($data,$finderData);
                }

                $data['my_reward'] = $myreward->toArray();
                $data['quantity'] = $myreward->quantity;

                $myreward_capture = new Myrewardcapture($data);
                $myreward_capture->_id = Myrewardcapture::max('_id') + 1;
                $myreward_capture->status = "1";
                $myreward_capture->rewardcategory_id = (isset($myreward->rewardcategory_id) && $myreward->rewardcategory_id != "") ? $myreward->rewardcategory_id : "";
                $myreward_capture->save();

                if($myreward_capture->rewardcategory_id != ""){
                    $this->sendCommunication($myreward_capture);
                }

                switch ($myreward->reward_type) {

                    case 'fitness_kit': $message = "Thank you! Your Fitness Kit would be delivered in next 5 to 7 working days.";break;
                    case 'healthy_snacks': $message = "Thank you! Your Healthy Snacks Hamper would be delivered in next 5 to 7 working days.";break;
                    case 'personal_trainer_at_studio': $message = "Thank you! We have notified ".$myreward->title."about your Personal training sessions.";break;
                    case 'personal_trainer_at_home': $message = "Your Personal Training at Home request has being processed. We will reach out to you shortly with trainer details to schedule your first session.";break;
                    default: $message = "Reward Claimed Successfull";break;
                }

                return array('status' => 200,'message' =>$message);

            }else{

                return array('status' => 404,'message' => "Reward Already Claimed");
            }
        }

    }

    public function sendCommunication($myreward_capture){

        $reward_category = Rewardcategory::find((int) $myreward_capture->rewardcategory_id);
        $data = $myreward_capture->toArray();

        $customerMailer     = new CustomerMailer();
        $customerSms        = new CustomerSms();
        $finderMailer       = new FinderMailer();
        $finderSms          = new FinderSms();

        $data['terms_and_condition'] = (isset($reward_category->terms)) ? $reward_category->terms : "";

        Log::info('reward_type : --'.$reward_category->reward_type);

        switch ($reward_category->reward_type) {
            case 'fitness_kit' : 
                $data['label'] = "Reward-FitnessKit-Customer";
                $myreward_capture->customer_email_reward = $customerMailer->rewardClaim($data);
                break;
            case 'healthy_snacks' : 
                $data['label'] = "Reward-HealthySnacksHamper-Customer";
                $myreward_capture->customer_email_reward = $customerMailer->rewardClaim($data);
                break;
            case 'personal_trainer_at_studio' : 
                $data['label'] = "Reward-PersonalTrainer-AtStudio-Customer";
                $myreward_capture->customer_email_reward = $customerMailer->rewardClaim($data);
                break;
            case 'diet_plan' : 
                $data['label'] = "Reward-DietPlan-Customer";
                $myreward_capture->customer_sms_reward = $customerSms->rewardClaim($data);
                break;
            default : break;
        }

        return $myreward_capture->update();

        /*if(isset($reward_category->customer_email_label) && $reward_category->customer_email_label != ""){
            $data['label'] = $reward_category->customer_email_label;
            $myreward_capture->customer_email = $customerMailer->rewardClaim($data);
        }

        if(isset($reward_category->customer_sms_label) && $reward_category->customer_sms_label != ""){
            $data['label'] = $reward_category->customer_sms_label;
            $myreward_capture->customer_sms = $customerSms->rewardClaim($data);
        }

        if(isset($reward_category->finder_email_label) && $reward_category->finder_email_label != ""){
            $data['label'] = $reward_category->finder_email_label;
            $myreward_capture->finder_email = $finderMailer->rewardClaim($data);
        }

        if(isset($reward_category->finder_sms_label) && $reward_category->finder_sms_label != ""){
            $data['label'] = $reward_category->finder_sms_label;
            $myreward_capture->finder_sms = $finderSms->rewardClaim($data);
        }*/
    }

   public function purchaseGameNew($amount,$finder_id,$payment_mode = "paymentgateway",$offer_id = false,$customer_id = false){

        $current_wallet_balance = 0;
        $wallet = 0;
        $wallet_fitcash_plus = 0;
        $cap = 1500;

        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token purchaseGameNew: '.$jwt_token);
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }

        if($customer_id){

            $customer = \Customer::find($customer_id);

            $utilities = new Utilities;

            $request = [
               'customer_id'=>$customer_id,
               'finder_id'=>$finder_id, 
            ];

            $query = $utilities->getWalletQuery($request);
            
            $current_wallet_balance = $query->sum('balance');/*

            if(isset($customer->demonetisation)){

              $current_wallet_balance = \Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');   

            }else{

                $customer_wallet = \Customerwallet::where('customer_id',(int) $customer_id)->orderBy('_id','desc')->first();

                if($customer_wallet){

                    if( isset($customer_wallet->balance_fitcash_plus) && $customer_wallet->balance_fitcash_plus != ''){
                        $wallet_fitcash_plus = (int)$customer_wallet->balance_fitcash_plus;
                    }

                    if(isset($customer_wallet->balance) && $customer_wallet->balance != '' && $wallet_fitcash_plus < $cap){

                        $wallet = $customer_wallet->balance;

                        if(($wallet + $wallet_fitcash_plus) > $cap){

                            $wallet = $cap - $wallet_fitcash_plus;
                        }
                    }

                    $current_wallet_balance = $wallet + $wallet_fitcash_plus;

                    $utilities = new Utilities;

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $current_wallet_balance;
                    $request['entry'] = "credit";
                    $request['type'] = "CREDIT";
                    $request['description'] = "Added FitCash+ Rs ".$current_wallet_balance;

                    $return = $utilities->customerWalletTransaction($request);

                    $customer->update(['demonetisation'=>time()]);

                }

            }*/
        }

        $wallet_percentage = 27 ;

        $vendorCommercial = \VendorCommercial::where('vendor_id',$finder_id)->orderBy('_id','desc')->first();

        $commision = 15;
        if($vendorCommercial){

            if($offer_id){
                if(isset($vendorCommercial->campaign_end_date) && $vendorCommercial->campaign_end_date != "" && isset($vendorCommercial->campaign_cos) && $vendorCommercial->campaign_cos != ""){

                    $campaign_end_date = strtotime(date('Y-m-d 23:59:59',strtotime($vendorCommercial->campaign_end_date)));

                    if($campaign_end_date > time()){
                        $commision = (float) preg_replace("/[^0-9.]/","",$vendorCommercial->campaign_cos);
                    }
                }
            }else{

                if(isset($vendorCommercial->contract_end_date) && $vendorCommercial->contract_end_date != "" && isset($vendorCommercial->commision) && $vendorCommercial->commision != ""){

                    $contract_end_date = strtotime(date('Y-m-d 23:59:59',strtotime($vendorCommercial->contract_end_date)));

                    if($contract_end_date > time()){
                        $commision = (float) preg_replace("/[^0-9.]/","",$vendorCommercial->commision);
                    }
                }
            }
        }

        $algo = array(
            array('min'=>0,'max'=>10,'cashback'=>2.5,'fitcash'=>2.5,'discount'=>0),
            array('min'=>10,'max'=>0,'cashback'=>5,'fitcash'=>5,'discount'=>0),
        );

        $setAlgo = array('cashback'=>5,'fitcash'=>5,'discount'=>0);

        /*if($payment_mode != "paymentgateway"){
            $setAlgo = array('cashback'=>5,'fitcash'=>5,'discount'=>0);
            $wallet = 0;
        }else{

            foreach ($algo as $key => $value) {

                $min_flag = ($commision >= $value['min'] || $value['min'] == 0) ? true : false;
                $max_flag = ($commision < $value['max'] || $value['max'] == 0) ? true : false;

                if($min_flag && $max_flag){
                    $setAlgo = $value;
                    break;
                }

            }
        }*/

        if($amount > 50000){
            $setAlgo = array('cashback'=>0,'fitcash'=>0,'discount'=>0);
        }

        $original_amount = $amount;
        $wallet_amount = round($amount * $setAlgo['fitcash'] / 100);
        $amount_discounted = round($amount * $setAlgo['discount'] / 100); 
        $wallet_algo = round(($amount * $commision / 100) * ($wallet_percentage / 100));
        $amount_deducted_from_wallet = $amount > $current_wallet_balance ? $current_wallet_balance : $amount;
        $final_amount_discount_only = $original_amount - $amount_discounted;
        $final_amount_discount_and_wallet = $original_amount - $amount_discounted - $amount_deducted_from_wallet;

        $data['original_amount'] = $original_amount;
        $data['amount_discounted'] = $amount_discounted;
        $data['amount_deducted_from_wallet'] = $amount_deducted_from_wallet;
        $data['final_amount_discount_only'] = $final_amount_discount_only;
        $data['final_amount_discount_and_wallet'] = $final_amount_discount_and_wallet;
        $data['wallet_amount'] = $wallet_amount;
        $data['algo'] = $setAlgo;
        $data['current_wallet_balance'] = round($current_wallet_balance);


        $data['description'] = "Enjoy instant cashback (FitCash) of Rs. ".$wallet_amount." on this purchase. FitCash can be used for any booking / purchase on Fitternity ranging from workout sessions, memberships and healthy tiffin subscription with a validity of 12 months.";
        
        Log::info('reward_calculation : ',$data);

        return $data;

    }


    public function purchaseGameOld($amount,$finder_id,$payment_mode = "paymentgateway",$offer_id = false,$customer_id = false){

        $wallet = 0;
        $wallet_fitcash_plus = 0;
        $cap = 1500;

        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token purchaseGameOld: '.$jwt_token);
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }
        
        if($customer_id){

            $customer_wallet = Customerwallet::where('customer_id',(int) $customer_id)->orderBy('_id','desc')->first();

            if($customer_wallet && isset($customer_wallet->balance) && $customer_wallet->balance != ''){
                $wallet = $wallet <= $cap ? $customer_wallet->balance : $cap;
            }

            if($customer_wallet && isset($customer_wallet->balance_fitcash_plus) && $customer_wallet->balance_fitcash_plus != ''){
                $wallet_fitcash_plus = $customer_wallet->balance_fitcash_plus;
            }
        }

        $wallet_percentage = 27 ;

        $vendorCommercial = VendorCommercial::where('vendor_id',$finder_id)->orderBy('_id','desc')->first();

        $commision = 15;
        if($vendorCommercial){

            if($offer_id){
                if(isset($vendorCommercial->campaign_end_date) && $vendorCommercial->campaign_end_date != "" && isset($vendorCommercial->campaign_cos) && $vendorCommercial->campaign_cos != ""){

                    $campaign_end_date = strtotime(date('Y-m-d 23:59:59',strtotime($vendorCommercial->campaign_end_date)));

                    if($campaign_end_date > time()){
                        $commision = (float) preg_replace("/[^0-9.]/","",$vendorCommercial->campaign_cos);
                    }
                }
            }else{

                if(isset($vendorCommercial->contract_end_date) && $vendorCommercial->contract_end_date != "" && isset($vendorCommercial->commision) && $vendorCommercial->commision != ""){

                    $contract_end_date = strtotime(date('Y-m-d 23:59:59',strtotime($vendorCommercial->contract_end_date)));

                    if($contract_end_date > time()){
                        $commision = (float) preg_replace("/[^0-9.]/","",$vendorCommercial->commision);
                    }
                }
            }
        }

        $algo = array(
            array('min'=>0,'max'=>10,'cashback'=>2.5,'fitcash'=>2.5,'discount'=>0),
            array('min'=>10,'max'=>0,'cashback'=>5,'fitcash'=>5,'discount'=>0),
        );

        $setAlgo = array('cashback'=>5,'fitcash'=>5,'discount'=>0);

        /*if($payment_mode != "paymentgateway"){
            $setAlgo = array('cashback'=>5,'fitcash'=>5,'discount'=>0);
            $wallet = 0;
        }else{

            foreach ($algo as $key => $value) {

                $min_flag = ($commision >= $value['min'] || $value['min'] == 0) ? true : false;
                $max_flag = ($commision < $value['max'] || $value['max'] == 0) ? true : false;

                if($min_flag && $max_flag){
                    $setAlgo = $value;
                    break;
                }

            }
        }*/

        /*$algo = array(
            array('min'=>0,'max'=>5,'cashback'=>2.5,'fitcash'=>2.5,'discount'=>0),
            array('min'=>5,'max'=>10,'cashback'=>5,'fitcash'=>2.5,'discount'=>2.5),
            array('min'=>10,'max'=>0,'cashback'=>10,'fitcash'=>5,'discount'=>5)
        );

        $setAlgo = array('cashback'=>10,'fitcash'=>5,'discount'=>5);

        if($payment_mode != "paymentgateway"){
            $setAlgo = array('cashback'=>5,'fitcash'=>5,'discount'=>0);
            $wallet = 0;
        }else{

            foreach ($algo as $key => $value) {

                $min_flag = ($commision >= $value['min'] || $value['min'] == 0) ? true : false;
                $max_flag = ($commision < $value['max'] || $value['max'] == 0) ? true : false;

                if($min_flag && $max_flag){
                    $setAlgo = $value;
                    break;
                }

            }
        }*/

        if($amount > 50000){
            $setAlgo = array('cashback'=>0,'fitcash'=>0,'discount'=>0);
        }

        $original_amount = $amount;

        $wallet_amount = round($amount * $setAlgo['fitcash'] / 100);

        $amount_discounted = round($amount * $setAlgo['discount'] / 100); 

        if($amount > 500){
            $wallet_algo = $amount;
        }else{
            $wallet_algo = round(($amount * $commision / 100) * ($wallet_percentage / 100));
        }
        
        if( isset($customer_wallet->balance_fitcash_plus) && $customer_wallet->balance_fitcash_plus != ''){
            $wallet_fitcash_plus = (int)$customer_wallet->balance_fitcash_plus;
        }

        if(isset($customer_wallet->balance) && $customer_wallet->balance != '' && $wallet_fitcash_plus <= $cap){

            $wallet = $customer_wallet->balance;

            if(($wallet + $wallet_fitcash_plus) > $cap){

                $wallet = $cap - $wallet_fitcash_plus;
            }
        }
        if($wallet_fitcash_plus >= $cap){
            $wallet = 0;
        }
        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios']) && isset($_GET['app_version']) && ((float)$_GET['app_version'] <= 3.2) ){


            $amount_deducted_from_wallet = ($wallet_algo < $wallet) ? $wallet_algo : round($wallet);

            $final_amount_discount_only = $original_amount - $amount_discounted;

            $final_amount_discount_and_wallet = $original_amount - $amount_discounted - $amount_deducted_from_wallet;

            $data['original_amount'] = $original_amount;
            $data['amount_discounted'] = $amount_discounted;
            $data['amount_deducted_from_wallet'] = $amount_deducted_from_wallet;
            $data['final_amount_discount_only'] = $final_amount_discount_only;
            $data['final_amount_discount_and_wallet'] = $final_amount_discount_and_wallet;
            $data['wallet_amount'] = $wallet_amount;
            $data['algo'] = $setAlgo;
            $data['current_wallet_balance'] = round($wallet);
            //$data['description'] = "Enjoy instant discount of Rs.".$amount_discounted." on this purchase & FitCash of Rs.".$wallet_amount." for your next purchase (FitCash is fitternity's cool new wallet)";
            // $data['description'] = "Enjoy FitCash of Rs.".$wallet_amount." for your next purchase (FitCash is fitternity's cool new wallet)";
            
            $data['description'] = "Enjoy instant cashback (FitCash) of Rs. ".$wallet_amount." on this purchase. FitCash can be used for any booking / purchase on Fitternity ranging from workout sessions, memberships and healthy tiffin subscription with a validity of 12 months.";
            
            Log::info('reward_calculation : ',$data);

            return $data;

        }else{

            //fitcash+
            $deduct_fitcash_plus = $original_amount;
            $deduct_fitcash = 0;

            if($wallet_fitcash_plus < $original_amount){

                $deduct_fitcash_plus = $wallet_fitcash_plus;

                $deduct_fitcash = ($wallet_algo < $wallet) ? $wallet_algo : round($wallet);

                $balance = $original_amount - $deduct_fitcash_plus;

                if($balance < $deduct_fitcash){
                    $deduct_fitcash = $balance;
                }
            }

            $data['only_wallet'] = [
                "fitcash" => $deduct_fitcash,
                "fitcash_plus" => $deduct_fitcash_plus
            ];

            $data['discount_and_wallet'] = [
                "fitcash" => $deduct_fitcash,
                "fitcash_plus" => $deduct_fitcash_plus
            ];

            $amount_deducted_from_wallet = $deduct_fitcash_plus + $deduct_fitcash;

            if($amount_deducted_from_wallet > ($original_amount - $amount_discounted)){

                $balance = $amount_deducted_from_wallet - ($original_amount - $amount_discounted);

                if($balance < $deduct_fitcash){
                    $data['discount_and_wallet']['fitcash'] = $deduct_fitcash - $balance;
                }elseif($balance < $deduct_fitcash_plus){
                    $data['discount_and_wallet']['fitcash_plus'] = $deduct_fitcash_plus - $balance;
                }
            }

            $final_amount_discount_only = $original_amount - $amount_discounted;

            $final_amount_discount_and_wallet = $original_amount - $amount_discounted - ($data['discount_and_wallet']['fitcash'] + $data['discount_and_wallet']['fitcash_plus']);

            $data['original_amount'] = $original_amount;
            $data['amount_discounted'] = $amount_discounted;
            $data['amount_deducted_from_wallet'] = $amount_deducted_from_wallet;
            $data['final_amount_discount_only'] = $final_amount_discount_only;
            $data['final_amount_discount_and_wallet'] = $final_amount_discount_and_wallet;
            $data['wallet_amount'] = $wallet_amount;
            $data['algo'] = $setAlgo;
            $data['current_wallet_balance'] = round($wallet + $wallet_fitcash_plus);
            $data['current_wallet_balance_only_fitcash'] = round($wallet);
            $data['current_wallet_balance_only_fitcash_plus'] = round($wallet_fitcash_plus);
            //$data['description'] = "Enjoy instant discount of Rs.".$amount_discounted." on this purchase & FitCash of Rs.".$wallet_amount." for your next purchase (FitCash is fitternity's cool new wallet)";
            // $data['description'] = "Enjoy FitCash of Rs.".$wallet_amount." for your next purchase (FitCash is fitternity's cool new wallet)";

            $data['description'] = "Enjoy instant cashback (FitCash) of Rs. ".$wallet_amount." on this purchase. FitCash can be used for any booking / purchase on Fitternity ranging from workout sessions, memberships and healthy tiffin subscription with a validity of 12 months.";
            
            Log::info('reward_calculation : ',$data);

            return $data;
        }

    }


    public function purchaseGame($amount,$finder_id,$payment_mode = "paymentgateway",$offer_id = false,$customer_id = false){

        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token : '.$jwt_token);
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }

        $customer = \Customer::find($customer_id);

        if(isset($customer->demonetisation)){

            return $this->purchaseGameNew($amount,$finder_id,$payment_mode,$offer_id,$customer_id);

        }

        return $this->purchaseGameOld($amount,$finder_id,$payment_mode,$offer_id,$customer_id);

    }

    public function customerTokenDecode($token){

        $jwt_token = $token;
        $jwt_key = Config::get('app.jwt.key');
        $jwt_alg = Config::get('app.jwt.alg');
        $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

        return $decodedToken;
    }

    public function getFinderData($finder_id){

        $finder_id = (int) $finder_id;

        $data = array();

        $finder                             =   Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finder_id))->first()->toArray();

        $finder_city                       =    (isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
        $finder_location                   =    (isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
        $finder_address                    =    (isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
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

        return $data; 

    }

    public function fitternityDietVendor($amount){

        $finder = Finder::where('title','Fitternity Diet Vendor')->first();

        $finder_id = (int) $finder->_id;

        $service = \Service::where('finder_id',$finder_id)->get();

        $data = [];

        if(count($service) > 0){

            foreach ($service as $service_value) {

                $service_data = [];
                $service_id = (int) $service_value->_id;
                $service_data['service_name'] = ucwords($service_value->name);
                $service_data['service_id'] = $service_id;
                $service_data['ratecard'] = [];

                $ratecard = \Ratecard::where('service_id',$service_id)->where('finder_id',$finder_id)->get();

                if(count($ratecard) > 0){

                    foreach ($ratecard as $ratecard_value) {

                        $ratecard_data = [];

                        $ratecard_id = $ratecard_value->_id;

                        $ratecard_data['ratecard_id'] = $ratecard_id;

                        if(isset($ratecard_value['special_price']) && $ratecard_value['special_price'] != 0){
                            $ratecard_data['amount'] = $ratecard_value['special_price'];
                        }else{
                            $ratecard_data['amount'] = $ratecard_value['price'];
                        }

                        if($ratecard_data['amount'] <= 0){
                            continue;
                        }

                        $ratecard_data['service_id'] = $service_id;

                        $service_data['ratecard'][] = $ratecard_data;

                    }

                    $data[] = $service_data;

                }else{
                    continue;
                }

            }
        }

        return $data;

    }


    public function couponCodeDiscountCheck($ratecard,$couponCode,$customer_id = false, $ticket = null, $ticket_quantity = 1, $service_id = null){


        if($ticket){

            $price = $ticket['price'] * $ticket_quantity;
        
        }else{

            $offer = Offer::where('ratecard_id',$ratecard->_id)->where('hidden', false)->where('start_date','<=',new \DateTime(date("d-m-Y 00:00:00")))->where('end_date','>=',new \DateTime(date("d-m-Y 00:00:00")))->first();
            if($offer){
                $price = $offer->price;
            }else{
                $price = $ratecard["special_price"] == 0 ? $ratecard["price"] : $ratecard["special_price"];
            }
        }
            
        $customer_id = isset($customer_id) ? $customer_id : false;
        
        $wallet_balance = 0;

        if(!$ticket){
        
            $calculation = $this->purchaseGameNew($price,$ratecard["finder_id"],"paymentgateway",false,$customer_id);
            $wallet_balance = $calculation["amount_deducted_from_wallet"];

        }

        $today_date = date("d-m-Y 00:00:00");
        $query = Coupon::where('code', strtolower($couponCode))->where('start_date', '<=', new \DateTime($today_date))->where('end_date', '>=', new \DateTime($today_date));

        if($ticket){
            $query->whereIn('tickets', [$ticket->_id]);
        }
        
        $coupon = $query->first();
        
        if(isset($coupon)){
            
            $vendor_coupon = false;
            if(isset($coupon["ticket_id"]) && !$ticket){
                $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Coupon not valid for this transaction");
                return $resp;
            }
            if(isset($coupon['vendor_exclusive']) && $coupon['vendor_exclusive']){
                $vendor_coupon = true;
                $jwt_token = Request::header('Authorization');

                if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
                    $decoded = $this->customerTokenDecode($jwt_token);
                    $customer_id = $decoded->customer->_id;
                }else{
                    Log::info("returning");
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Not logged in");
                    return $resp;
                }


                $customer = \Customer::find((int)$customer_id);

                $finder_id = $customer->finder_id;
                
                if(!$finder_id || !in_array($finder_id, $coupon['finders'])){
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Vendor not eligible");
                    return $resp;
                }

                if(!$service_id || !in_array((int)$service_id, $coupon['services'])){
                   $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Service not eligible");
                    return $resp;
                }
            }
            $excluded_vendors = isset($coupon["finders_exclude"]) ? $coupon["finders_exclude"] : [];
            if(isset($ratecard['finder_id']) && in_array($ratecard['finder_id'], $excluded_vendors)){
                $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Coupon not valid for this transaction");
                return $resp;
            }
            $included_vendors = isset($coupon["finders"]) ? $coupon["finders"] : [];
            // Log::info($included_vendors);
            // Log::info("ratecard['finder_id']".$ratecard['finder_id']);
            if(isset($ratecard['finder_id']) && count($included_vendors) > 0 && !in_array($ratecard['finder_id'], $included_vendors)){
                $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Coupon not valid for this transaction");
                return $resp;
            }



            $fitternity_only_coupon = false;
            
            if(isset($coupon['fitternity_only']) && $coupon['fitternity_only']){
                $fitternity_only_coupon = true;
                $jwt_token = Request::header('Authorization');
                if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
                    $decoded = $this->customerTokenDecode($jwt_token);
                    $customer_id = $decoded->customer->_id;
                }else{
                    Log::info("returning");
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "fitternity_only_coupon"=>$fitternity_only_coupon, "error_message"=>"Not logged in");
                    return $resp;
                }
                Log::info("===========customer".$customer_id);
                $customer = \Customer::find((int)$customer_id);
                $customer_email = $customer->email;
                Log::info("===========customer=========".$customer_email);
                
                if(!in_array($customer_email, ['utkarshmehrotra@fitternity.com', 'maheshjadhav@fitternity.com'])){
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "fitternity_only_coupon"=>$fitternity_only_coupon, "error_message"=>"Customer not eligible");
                    return $resp;
                }
                
            }
            
            $discount_amount = $coupon["discount_amount"];
            $discount_amount = $discount_amount == 0 ? $coupon["discount_percent"]/100 * $price : $discount_amount;
            $discount_amount = intval($discount_amount);
            $discount_amount = $discount_amount > $coupon["discount_max"] ? $coupon["discount_max"] : $discount_amount;
            $discount_price = $price - $discount_amount;
            $final_amount = $discount_price > $wallet_balance ? $discount_price - $wallet_balance : 0;
            $resp = array("data"=>array("discount" => $discount_amount, "final_amount" => $final_amount, "wallet_balance" => $wallet_balance, "only_discount" => $discount_price), "coupon_applied" => true, 'otp'=>$fitternity_only_coupon, "vendor_coupon"=>$vendor_coupon);
        }else{
            $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false);
            // $resp = array("status"=> 400, "message" => "Coupon not found", "error_message" => "Coupon is either not valid or expired");
            // return Response::json($resp,400);    
        }
        return $resp;
    }


}
