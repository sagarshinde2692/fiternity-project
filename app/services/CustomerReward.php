<?PHP namespace App\Services;
use Myreward;
use Reward;
use Offer;
use Coupon;
use Vendormou;
use App\Services\Utilities;
use Validator;
use Response;
use Log;
use Cache;
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
use Service;


Class CustomerReward {

    public $device_type;
    public $app_version;

    public function __construct() {

        $this->device_type = Request::header('Device-Type');
        $this->app_version = Request::header('App-Version');

    }

    public function createMyReward($data){

        $utilities = new Utilities;

        $data = !is_array($data) ? $data->toArray() : $data;

        $rules = array(
            'reward_ids'=>'required|array',
            'customer_name'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required'
        );

        if(!isset($data['routed_order_id'])){

            $old_rules = array(
                'booktrial_id'=>'required_without:order_id|integer',
                'order_id'=>'required_without:booktrial_id|integer'
            );

            $rules = array_merge($rules,$old_rules);
        }

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
            isset($data['routed_order_id']) ? $reward['routed_order_id'] = (int) $data['routed_order_id'] : null;


            if(isset($reward['order_id'])){

                $order = \Order::find($reward['order_id']);

                if($order && isset($order['amount_finder'])){

                    $service = \Service::find((int)$order->service_id);
                    $service_category_id = null;

                    $finder_id = (int)$order['finder_id'];

                    if($service){

                        $service_category_id = (int)$service->servicecategory_id;
                    }

                    $amount = (int) $order['amount_finder'];

                    if(isset($finder['brand_id']) && $finder['brand_id'] == 134){

                        $min_date = strtotime(' + 2 days');
                        $max_date = strtotime(' + 32 days');

                        $slab = [            
                            [
                                'min'=>25000,
                                'max'=>0,
                            ],
                            [
                                'min'=>20000,
                                'max'=>25000,
                            ],
                            [
                                'min'=>15000,
                                'max'=>20000,
                            ],
                            [
                                'min'=>10000,
                                'max'=>15000,
                            ],
                            [
                                'min'=>7500,
                                'max'=>10000,
                            ],
                            [
                                'min'=>5000,
                                'max'=>7500,
                            ],
                            [
                                'min'=>2000,
                                'max'=>5000,
                            ],
                            [
                                'min'=>1000,
                                'max'=>2000,
                            ],
                        ];

                        foreach ($slab as $slab_key => $slab_value) {

                            if($amount >= $slab_value['min'] && $slab_value['max'] !== 0 ){

                                $amount = $slab_value['max'];
                                break;
                            }
                        }
                    }

                    $reward['content'] = [];

                    $reward_data_flag = false;

                    $reward_type_info = $reward['reward_type'];

                    if($reward_type_info == 'fitness_kit' && $service_category_id != null){

                        $pos = strpos($reward['title'],'(Kit B)');

                        if($pos === false){

                            $reward_type_info = 'fitness_kit';

                            $fitness_kit_array = Config::get('fitness_kit.fitness_kit');
                        }else{
                            $reward_type_info = 'fitness_kit_2';

                            $fitness_kit_array = Config::get('fitness_kit.fitness_kit_2');    
                        }

                        rsort($fitness_kit_array);

                        foreach ($fitness_kit_array as $data_key => $data_value) {

                            if($amount >= $data_value['min'] ){

                                $content_data = $data_value['content'];

                                foreach ($content_data as $content_key => $content_value) {

                                    if(in_array($service_category_id,$content_value['category_id'])){

                                        $reward['content'] = $content_value['product'];
                                        $reward['image'] = $content_value['image'];

                                        $reward_data_flag = true;

                                        break;
                                    }
                                }

                                break;

                            }
                        }

                        if(!$reward_data_flag && $amount >= 2000){

                            foreach ($fitness_kit_array as $data_key => $data_value) {

                                if($amount >= $data_value['min'] ){

                                    $reward['content'] = $data_value['content'][0]['product'];
                                    $reward['image'] = $data_value['content'][0]['image'];

                                    break;
                                }
                            }
                        }

                        $cult_gym = [13761,13762,13763,13764,13765,14078,14079,14081,14082,14085,14088];

                        if(in_array($finder_id,$cult_gym) && $amount <= 1025){

                            $pos = strpos($reward['title'],'(Kit B)');

                            if($pos === false){

                                $reward_type_info = 'fitness_kit';

                                $reward['content'] = ['Cool-Water Bottle'];
                                $reward['image'] = 'https://b.fitn.in/gamification/reward/goodies/productskit/bottle.png';

                            }else{

                                $reward_type_info = 'fitness_kit_2';

                                $reward['content'] = ['Waterproof Gym Bag'];
                                $reward['image'] = 'https://b.fitn.in/gamification/reward/goodies/productskit/gymbag.png';
                            }
                            
                        }

                        $power_world_gym = [10861,10863,10868,10870,10872,10875,10876,10877,10880,10883,10886,10887,10888,10890,10891,10892,10894,10895,10897,10900];

                        if(in_array($finder_id,$power_world_gym) && $amount == 3500){

                            $reward_type_info = 'fitness_kit';

                            $reward['content'] = ['Waterproof Gym Bag'];
                            $reward['image'] = 'https://b.fitn.in/gamification/reward/goodies/productskit/gymbag.png';
                        }

                    }

                    if($reward['reward_type'] == "sessions"){

                        $reward_type_info = 'sessions';

                        $workout_session_array = Config::get('fitness_kit.workout_session');

                        rsort($workout_session_array);

                        foreach ($workout_session_array as $data_key => $data_value) {

                            if($amount >= $data_value['min'] ){

                                $session_content = $data_value['total']." Workout Sessions";

                                foreach ($data_value['session'] as $session_value){
                                    $session_content .= " <br>- ".$session_value['slabs']." x ".$session_value['quantity'];
                                }

                                $reward['payload_amount'] = $data_value['amount'];
                                $reward['new_amount'] = $data_value['amount'];
                                $reward['title'] = "Workout Session Pack (".$data_value['total']." Sessions)";
                                $reward['contents'] = ['Workout Session'];
                                $reward['gallery'] = [];
                                $reward['description'] = $session_content;
                                $reward['quantity'] = $data_value['total'];
                                $reward['payload']['amount'] = $data_value['amount'];
                                $reward['session'] = $data_value['session'];

                                break;
                            }
                        }
                    }

                    if($reward['reward_type'] == "swimming_sessions"){

                        $reward_type_info = 'swimming_sessions';

                        $swimming_session_array = Config::get('fitness_kit.swimming_session');

                        rsort($swimming_session_array);

                        foreach ($swimming_session_array as $data_key => $data_value) {

                            if($amount >= $data_value['min'] ){

                                $session_content = $data_value['total']." Swimming Sessions";

                                foreach ($data_value['session'] as $session_value){
                                    $session_content .= " <br>- ".$session_value['slabs']." x ".$session_value['quantity'];
                                }

                                $reward['payload_amount'] = $data_value['amount'];
                                $reward['new_amount'] = $data_value['amount'];
                                $reward['title'] = "Swimming at 5-star Hotels (".$data_value['total']." Sessions)";
                                $reward['contents'] = ['Swimming Session'];
                                $reward['gallery'] = [];
                                $reward['description'] = $session_content;
                                $reward['quantity'] = $data_value['total'];
                                $reward['payload']['amount'] = $data_value['amount'];
                                $reward['session'] = $data_value['session'];

                                break;
                            }
                        }
                    }

                    if($reward['reward_type'] == "mixed"){
                        
                        $reward_type_info = 'mixed';

                        $swimming_session_array = Config::get('fitness_kit.swimming_session');

                        rsort($swimming_session_array);

                        foreach ($swimming_session_array as $data_key => $data_value) {

                            if($amount >= $data_value['min'] ){
        
                                $no_of_sessions = $data_value['total'];
                                break;
                            }
                        }
                        
                        $no_of_sessions = (!empty($no_of_sessions) ? ($no_of_sessions == 1 ? '1 person' : $no_of_sessions.' people') : '1 person');

                        $mixedreward_content = \MixedRewardContent::where('finder_id', $data['finder_id'])->first();
                        
                        $rewards_snapfitness_contents = $mixedreward_content->reward_contents;

                        foreach($rewards_snapfitness_contents as &$content){
                            $content = bladeCompile($content, ['no_of_sessions'=>$no_of_sessions]);
                        }

                        $reward['title'] = $mixedreward_content['title'];
                        $reward['content'] = $rewards_snapfitness_contents;
                        $reward['image'] = $mixedreward_content['images'][0];
                        $images = $mixedreward_content['images'];
                        $reward['gallery'] = $mixedreward_content['images'];
                        $reward['new_amount'] = $mixedreward_content['total_amount'];
                        $reward['payload']['amount'] = $mixedreward_content['total_amount'];
                        $reward['payload_amount'] = 6000;
                        $reward['description'] = $mixedreward_content['rewards_header'].': <br>- '.implode('<br>- ',$rewards_snapfitness_contents);
                    }

                }

            }

            $this->saveToMyRewards($reward);
        }

        return "success";
    }

    public function createSessionCoupon($data){

        $bulk_insert = [];

        $coupon_code = [];

        foreach ($data['session'] as $session_value){

            $code = $data['_id'].$session_value['slabs'];

            $coupon_data = [
                'validity'=>time()+(86400*30),
                'code'=>$code,
                'amount'=>$session_value['slabs'],
                'quantity'=>$session_value['quantity'],
                'claimed'=>0,
                'customer_id'=>$data['customer_id'],
                'myreward_id'=>$data['_id'],
                'reward_type'=>$data['reward_type'],
                'created_at'=>new \MongoDate(),
                'updated_at'=>new \MongoDate(),
                'status'=>'1'
            ];

            if($data['reward_type'] == 'swimming_sessions'){
                $coupon_data['service_category_id'] = 123;
            }

            if(!empty($data['order_id'])){
                $coupon_data['order_id'] = (int)$data['order_id'];
            }

            if(!empty($data['booktrial_id'])){
                $coupon_data['booktrial_id'] = (int)$data['booktrial_id'];
            }

            $bulk_insert[] = $coupon_data;

            $coupon_code[] = [
                'code'=>$code,
                'amount'=>$session_value['slabs'],
                'quantity'=>$session_value['quantity'],
                'claimed'=>0
            ];
            
        }

        \CustomerCoupn::insert($bulk_insert);

        return $coupon_code;
        
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

                if(isset($reward['content'])){
                    $order->reward_content = $reward['content'];
                }

                $order->update();
            }
        }
        
        return "success";
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
                    'finder_id'=>(int)$order['finder_id'],
                    "amount"=>$cashback_amount,
                    "amount_fitcash" => $cashback_amount,
                    "amount_fitcash_plus" => 0,
                    "type"=>'CASHBACK',
                    'entry'=>'credit',
                    'description'=>"5% Cashback for purchase of membership at ".ucwords($order['finder_name'])." (Order ID. ".$order['_id']."), Expires On : ".date('d-m-Y',time()+(86400*$duration_day)),
                    "validity"=>time()+(86400*$duration_day)
                );

                $finder_id = (int)$order['finder_id'];

                $power_world_gym_vendor_ids = Config::get('app.power_world_gym_vendor_ids');

                if(in_array($finder_id,$power_world_gym_vendor_ids)){

                    $req['description'] = "15% Cashback for purchase of membership at ".ucwords($order['finder_name'])." (Order ID. ".$order['_id']."), Expires On : ".date('d-m-Y',time()+(86400*$duration_day));
                }

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
                    "amount"=> intval($order['amount_customer']),
                    "amount_fitcash" => 0,
                    "amount_fitcash_plus" => intval($order['amount_customer']),
                    "type"=>'CASHBACK',
                    'entry'=>'credit',
                    "description"=> "100% Cashback on trial booking at ".ucwords($order['finder_name'])." Applicable for buying a membership at ".ucwords($order['finder_name']).", Expires On : ".date('d-m-Y',time()+(86400*7)),
                    "validity"=>time()+(86400*7),
                    "valid_finder_id"=>intval($order['finder_id']),
                    "finder_id"=>intval($order['finder_id']),
                    "valid_service_id"=>intval($order['service_id']),
                    "service_id"=>intval($order['service_id']),
                );

                $walletTransaction =  $utilities->walletTransaction($walletData,$order->toArray());

                if(isset($walletTransaction['status']) && $walletTransaction['status'] == 200){

                    $customersms = new CustomerSms();

                    $sms_data = [];

                    $sms_data['customer_phone'] = $order['customer_phone'];

                    $sms_data['message'] = "Hi ".ucwords($order['customer_name']).", Rs. ".$order['amount_customer']." Fitcash has been added in your Fitternity wallet. Use this Fitcash to buy ".ucwords($order['finder_name'])."'s membership at lowest price and earn complimentary rewards. Valid for 7 days post your trial session. For quick assistance call ".Config::get('app.contact_us_customer_number');

                    $customersms->custom($sms_data);

                    $order->update(['cashback_amount'=>intval($order['amount_customer'])]);
                }

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


                if(isset($order['event_id']) && in_array($order['event_id'],[108,128])){

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

                                $walletTransactionresponse = $utilities->walletTransaction($walletData,$order->toArray());

                                Log::info('------------------------walletTransactionresponse-----------------------',$walletTransactionresponse);

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

                if(isset($order['event_id']) && $order['event_id'] == 124){

                    $fitcash_plus = $order['amount'];
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

                if(isset($order['event_id']) && in_array($order['event_id'],[108,128])){

                    $walletData["description"] = 'Cashback On Event Tickets - Morning Fitness Party';
                }

                $walletTransactionresponse = $utilities->walletTransaction($walletData,$order->toArray());

                Log::info('------------------------walletTransactionresponse 1-----------------------',$walletTransactionresponse);
                
                if($fitcash_plus > 0){
                    $customersms = new CustomerSms();
                    $order->amount_20_percent = $fitcash_plus;
                    $customersms->giveCashbackOnTrialOrderSuccessAndInvite($order->toArray());
                }            
            }

            if(isset($order->coupon_code) && $utilities->isPPSReferralCode($order->coupon_code)){
                $utilities->setPPSReferralData($order->toArray());
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

        $result = [
            "cta" => "Claimed"
        ];

        if($myreward){

            $created_at = date('Y-m-d H:i:s',strtotime($myreward->created_at));

            $validity_date_unix = strtotime($created_at . ' +'.(int)$myreward->validity_in_days.' days');
            $current_date_unix = time();

            if($validity_date_unix < $current_date_unix){
                return array('status' => 404,'message' => "Validity Is Over");
            }

            $claim_all = array('personal_trainer_at_studio','personal_trainer_at_home','healthy_tiffin','sessions','swimming_sessions');
            
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

                if(isset($data['tshirt_size'])){
                    $myreward->tshirt_size = $data['tshirt_size'];
                }

                if(in_array($myreward['reward_type'],['sessions','swimming_sessions'])){

                    $data['coupon'] = [];

                    $result['coupon_detail'] = $data['coupon_detail'] = $myreward->coupon_detail = $this->createSessionCoupon($myreward->toArray());

                    foreach ($data['coupon_detail'] as $value) {

                        $data['coupon'][] = $value['code'];
                    }

                    $myreward->coupon = $data['coupon'];

                    foreach ($result['coupon_detail'] as &$value) {

                        $value['text'] = "Your code is ".$value['code']." (Rs.".$value['amount'].")";
                        $value['usage_text'] = $value['claimed']."/".$value['quantity']." booked";
                    }

                    if(!empty($this->device_type)){

                        $result["cta"] = "Schedule Now";
                        $result["url"] = "ftrnty://ftrnty.com/pps";
                        $result["copy_text"] = "Copied";

                        if($myreward['reward_type'] == 'swimming_sessions'){
                            $result["url"] = "ftrnty://ftrnty.com/pps?cat=swimming-pools";
                        }
                    }
                }

                $myreward->update();

                if(isset($myreward->finder_id) && $myreward->finder_id != ""){
                    $data['finder_id'] = (int) $myreward->finder_id;
                }

                if(isset($data['finder_id']) && $data['finder_id'] != ""){
                    $finderData = $this->getFinderData((int)$data['finder_id']);
                    $data  = array_merge($data,$finderData);
                }

                if(!empty($myreward['payload_amount'])){

                    $data['payload_amount'] = $myreward['payload_amount'];
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

                    case 'fitness_kit': $message = "Thank you! Your Fitness Kit would be delivered in next 7 to 10 working days.";break;
                    case 'healthy_snacks': $message = "Thank you! Your Healthy Snacks Hamper would be delivered in next 7 to 10 working days.";break;
                    case 'personal_trainer_at_studio': $message = "Thank you! We have notified ".$myreward->title."about your Personal training sessions.";break;
                    case 'personal_trainer_at_home': $message = "Your Personal Training at Home request has being processed. We will reach out to you shortly with trainer details to schedule your first session.";break;
                    case 'swimming_sessions' :
                    case 'sessions' : $message = "Congratulations! You have successfully claimed your reward - ".$myreward->title." <br/>Your coupon code vouchers (worth Rs. ".$myreward['payload']['amount'].") are as follows. (also shared via sms/email)";break;
                    default: $message = "Reward Claimed Successfull";break;
                }

                $result['status'] = 200;
                $result['message'] = $message;
                $result['header'] = $myreward->title;

                return $result;

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
            case 'sessions' : 
                $data['label'] = "Reward-WorkoutSession-Customer";
                $myreward_capture->customer_sms_reward = $customerSms->rewardClaim($data);
                $myreward_capture->customer_email_reward = $customerMailer->rewardClaim($data);
                break;
            case 'swimming_sessions' : 
                $data['label'] = "Reward-SwimmingSession-Customer";
                $myreward_capture->customer_sms_reward = $customerSms->rewardClaim($data);
                $myreward_capture->customer_email_reward = $customerMailer->rewardClaim($data);
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

   public function purchaseGameNew($amount,$finder_id,$payment_mode = "paymentgateway",$offer_id = false,$customer_id = false,$part_payment_amount = false,$convinience_fee=false,$order_type = false){

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

            if($order_type){
               $request['order_type'] = $order_type;
            }

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

        if(in_array($finder_id, Config::get('app.mixed_reward_finders'))){
            $setAlgo = array('cashback'=>10,'fitcash'=>10,'discount'=>0);
        }

        $power_world_gym_vendor_ids = Config::get('app.power_world_gym_vendor_ids');

        if($finder_id && $finder_id != "" && $finder_id != null && in_array($finder_id,$power_world_gym_vendor_ids)){
            $commision = 15;
            $setAlgo = array('cashback'=>15,'fitcash'=>15,'discount'=>0);
        }

        $cashback_amount = $amount; 

        if($cashback_amount){
            $cashback_amount = $amount-$convinience_fee;
        }

        if($part_payment_amount){
            $amount = $part_payment_amount;
        }

        $original_amount = $amount;

        $wallet_amount = round($cashback_amount * $setAlgo['fitcash'] / 100);
        $amount_discounted = round($cashback_amount * $setAlgo['discount'] / 100);

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


    public function purchaseGame($amount,$finder_id,$payment_mode = "paymentgateway",$offer_id = false,$customer_id = false,$part_payment_amount = false,$convinience_fee=false,$order_type = false){

        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token : '.$jwt_token);
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }
        return $this->purchaseGameNew($amount,$finder_id,$payment_mode,$offer_id,$customer_id,$part_payment_amount,$convinience_fee,$order_type);
        // $customer = \Customer::find($customer_id);
        
        // if(isset($customer->demonetisation)){

        //     return $this->purchaseGameNew($amount,$finder_id,$payment_mode,$offer_id,$customer_id,$part_payment_amount,$convinience_fee);

        // }

        return $this->purchaseGameOld($amount,$finder_id,$payment_mode,$offer_id,$customer_id,$part_payment_amount,$convinience_fee,$order_type);

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
        // Log::info("dfjkhsdfkhskdjfhksdhfkjshdfkjhsdkjfhks",$ratecard["flags"]);
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

        $code = trim(strtoupper($couponCode));
        Log::info(substr($code, -1 ));
        Log::info($ratecard);
        
        $utilities = new Utilities;
        
        if($utilities->isPPSReferralCode($couponCode)){

            if(!(isset($ratecard) && isset($ratecard['type']) && $ratecard['type'] == 'workout session')){
                
                return array("referral_coupon"=>true, "data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => 0, "only_discount" => $price), "coupon_applied" => false, "message"=>'Coupon is applicable only on workout sessions');
            
            }


            $referral_resp = $utilities->checkPPSReferral($code, $customer_id);

            if($referral_resp['status'] == 200){
                $discount = $referral_resp['discount'];
                $discount = $discount < $price ? $discount : $price;
                $final_amount = $price - $discount;
                $resp = array("referral_coupon"=>true, "data"=>array("discount" => $discount, "final_amount" => $final_amount, "wallet_balance" => 0, "only_discount" => $final_amount), "coupon_applied" => true, "message"=>$referral_resp['message']);
                
            }else{
                
                $resp = array("referral_coupon"=>true, "data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => 0, "only_discount" => $price), "coupon_applied" => false, "message"=>$referral_resp['message']);

            }

            return $resp;

		}
            
        $customer_id = isset($customer_id) ? $customer_id : false;
        
        $wallet_balance = 0;

        if(!$ticket){
        
            $calculation = $this->purchaseGameNew($price,$ratecard["finder_id"],"paymentgateway",false,$customer_id);
            $wallet_balance = $calculation["amount_deducted_from_wallet"];

        }
        Log::info($couponCode);
        $today_date = date("d-m-Y hh:mm:ss");
        Log::info( (new \DateTime())->format("d-m-Y H:i:s"));
        $query = Coupon::where('code', strtolower($couponCode))->where('start_date', '<=', new \DateTime())->where('end_date', '>=', new \DateTime());

        if($ticket){
            $query->whereIn('tickets', [$ticket->_id]);
        }
        
        $coupon = $query->first();
        Finder::$withoutAppends = true;
            
        $finder = Finder::find($ratecard->finder_id);
        $finder_city = $finder->city_id;
        // if(!isset($coupon) && (strtolower($couponCode) == "srfit")){
        //     $vendorMOU = Vendormou::where("vendors",$ratecard["finder_id"])->where('contract_start_date', '<=', new \DateTime())->where('contract_end_date', '>=', new \DateTime())->first();
        //     $coupon = array("code" => strtolower($couponCode),"discount_max" => 1000,"discount_amount" => 0,"discount_min" => 200);
        //     if(isset($vendorMOU)){
        //         if(isset($vendorMOU["cos_percentage_normal"])){
        //             $vendorMOU["cos_percentage_normal"] = 15;
        //         }
        //         if($vendorMOU["cos_percentage_normal"] >= 15){
        //             $coupon["discount_percent"] = 5;
        //         }elseif($vendorMOU["cos_percentage_normal"] >= 10 && $vendorMOU["cos_percentage_normal"] < 15){
        //             $coupon["discount_percent"] = 3;
        //         }elseif($vendorMOU["cos_percentage_normal"] < 10){
        //             $coupon["discount_percent"] = 2;
        //         }
        //     }else{
        //         $coupon["discount_percent"] = 5;
        //     }
        // }
        if(!isset($coupon)){
            $couponRecieved = getDynamicCouponForTheFinder($finder);
            if($couponRecieved["code"] != ""){
                if( $couponRecieved["code"] == strtolower($couponCode)){
                    $coupon = $couponRecieved;
                }else{
                    $finder_detail = Cache::tags('finder_detail')->has($finder["slug"]) ? Cache::tags('finder_detail')->has($finder["slug"]) : false;
                    if($finder_detail && isset($finder_detail["code_applicable"]) && $finder_detail["code_applicable"] == strtolower($couponCode)){
                        $this->cacheapi->flushTagKey('finder_detail',$finder["slug"]);
                        return $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "error_message"=>"Coupon not valid for this transaction. You can use ".$couponRecieved["code"]. " instead");
                    }
                }
            }
        }
        if(!isset($coupon) && (strtolower($couponCode) == "mad18") && $ratecard && $ratecard["finder_id"] == 6168){
            Log::info("New user code");
            
            $discount = 300;

            $prev_order = null;

            if($customer_id){

                if($ratecard->type == 'membership'){
                    Log::info("membership");
                    $prev_order = \Order::active()->where('type', 'memberships')->where("customer_id", $customer_id)->first();
                }else{
                    Log::info("workout-session");
                    $prev_order = \Order::active()->whereIn('type', ['workout-session', 'memberships'])->where("customer_id", $customer_id)->first();
                }

            }
            
            
            
            Log::info($finder_city);
            if($prev_order){
                Log::info('$prev_order');
                Log::info($prev_order);
                if($finder_city == 1 || $finder_city == 2){
                    Log::info('MUMABAI');
                    
                    $discount = 300;

                }else{
                    Log::info('OUT MUMABAI');
                    
                    $discount = 500;
                }

            }else{
                Log::info('NO prev_order');
                
                if($finder_city == 1 || $finder_city == 2){
                    Log::info('MUMABAI');
                    
                    $discount = 500;
                }else{
                    Log::info('OUT MUMABAI');
                    
                    $discount = 750;
                }
            }
            Log::info('$discount');
            Log::info($discount);
            $coupon = array("code" => strtolower($couponCode),"discount_max" => $discount,"discount_amount" => $discount);
        }
        
        if(isset($ratecard["flags"]) && isset($ratecard["flags"]["pay_at_vendor"]) && $ratecard["flags"]["pay_at_vendor"] === True){
            Log::info($ratecard);
            return $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false);
        }
        
        if(isset($coupon)){

            $coupon_data = !is_array($coupon) ? $coupon->toArray() : $coupon;
            
            $vendor_coupon = false;

            if(isset($coupon["tickets"]) && !$ticket){
                $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Coupon not valid for this transaction");
                return $resp;
            }

            if(isset($coupon["app_only"]) && $coupon["app_only"]){
                $device = Request::header('Device-Type');
                if(!$device || !in_array($device, ['ios', 'android'])){
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Coupon valid only on app", "app_only"=>true);
                    return $resp;
                }
            }

            if(isset($coupon["once_per_user"]) && $coupon["once_per_user"]){

                $jwt_token = Request::header('Authorization');

                if(empty($jwt_token)){

                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"User Login Required","user_login_error"=>true);

                    return $resp;
                }

                $decoded = $this->customerTokenDecode($jwt_token);
                $customer_id = $decoded->customer->_id;

                \Order::$withoutAppends = true;

                $order_count = \Order::active()->where("customer_id", $customer_id)->where('coupon_code', 'Like', $coupon['code'])->where('coupon_discount_amount', '>', 0)->count();

                if($order_count >= 1){

                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Coupon already used","user_login_error"=>true);

                    return $resp;

                }
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

            if(!$vendor_coupon){
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
            Log::info("ratecard coupon");
            Log::info($ratecard);
            Log::info($coupon);

            if($ratecard){

                if(!empty($coupon_data['ratecard_type']) && is_array($coupon_data['ratecard_type'])){

                    if(!empty($ratecard['type']) && !in_array($ratecard['type'],$coupon_data['ratecard_type'])){
     
                        $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Coupon not valid for this transaction");

                        return $resp;

                    }
                   
                }
                
                $finder = Finder::where('_id', $ratecard['finder_id'])->first(['flags']);
                $service = Service::where('_id', $ratecard['service_id'])->first(['flags','servicecategory_id']);

                if(!empty($coupon_data['service_category_ids']) && !in_array($service['servicecategory_id'],$coupon_data['service_category_ids'])){
     
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"This coupon is valid only on Yoga Sessions","user_login_error"=>true);

                    return $resp;   
                }

                if(!empty($coupon_data['service_category_ids']) && in_array($service['servicecategory_id'],$coupon_data['service_category_ids'])){

                    $jwt_token = Request::header('Authorization');

                    if(empty($jwt_token)){

                        $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"User Login Required","user_login_error"=>true);

                        return $resp;
                    }

                    $decoded = $this->customerTokenDecode($jwt_token);
                    $customer_id = $decoded->customer->_id;

                    \Order::$withoutAppends = true;

                    $order_count = \Order::active()->where('customer_id',$customer_id)->where('coupon_code','like', $couponCode)->count();

                    if($order_count >= 4){

                        $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"This coupon is applicable only for 4 sessions","user_login_error"=>true);

                        return $resp;

                    }
                   
                }
                
                if($this->hasCamapignOffer($ratecard) || $this->hasCamapignOffer($service) || $this->hasCamapignOffer($finder)){
                    
                    if(isset($coupon['campaign_discount_percent']) && $coupon['campaign_discount_percent'] != ""){
                        
                        $coupon["discount_percent"] = intval($coupon["campaign_discount_percent"]);
                    }
                    
                    if(isset($coupon['campaign_discount_max']) && $coupon['campaign_discount_max'] != ""){
                        
                        $coupon["discount_max"] = intval($coupon["campaign_discount_max"]);
                        
                    }
                    
                    if(isset($coupon['campaign_discount_amount']) && $coupon['campaign_discount_amount'] != ""){
                        
                        $coupon["discount_amount"] = intval($coupon["campaign_discount_amount"]);
                    }
                    
                    if(isset($coupon['campaign_success_message']) && $coupon['campaign_success_message'] != ""){
                        
                        $coupon["success_message"] = $coupon["campaign_success_message"];
                    }
                
                }else{
                    if(isset($coupon['campaign_only']) && $coupon['campaign_only'] == "1"){
                        
                        $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "fitternity_only_coupon"=>$fitternity_only_coupon, "error_message"=>"Code is not applicable on this transaction", "custom_message"=>"Code is not applicable on this transaction");
                        return $resp;
                    
                    }
                }
            }

            if(isset($coupon['type']) && $coupon['type'] == 'syncron'){

                if($coupon['total_used'] >= $coupon['total_available']){
                    
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"This coupon has exhausted");

                    return $resp;
                }
                
                $jwt_token = Request::header('Authorization');

                if(empty($jwt_token)){

                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"User Login Required","user_login_error"=>true);

                    return $resp;
                }

                $decoded = $this->customerTokenDecode($jwt_token);
                
                $customer_email = $decoded->customer->email;

                if(isset($coupon['customer_emails']) && is_array($coupon['customer_emails'])){
                    if(!in_array(strtolower($customer_email), $coupon['customer_emails'])){
                        $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"Invalid Coupon");

                        return $resp;
                    }
                }

                \Booktrial::$withoutAppends = true;

                $booktrial_count = \Booktrial::where('customer_email',$customer_email)->where('created_at','>=',new \MongoDate(strtotime(date('Y-m-d 00:00:00'))))->where('created_at','<=',new \MongoDate(strtotime(date('Y-m-d 23:59:59'))))->count();

                if($booktrial_count > 0){

                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"User can book only one session per day","user_login_error"=>true);

                    return $resp;
                }

                if($price <= $coupon['price_limit']){
                    $coupon["discount_amount"] = 0;
                }
            }

            if(isset($coupon['conditions']) && is_array($coupon['conditions']) && in_array('once_new_pps', $coupon['conditions'])){
                
                $jwt_token = Request::header('Authorization');

                if(empty($jwt_token)){

                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"User Login Required","user_login_error"=>true);

                    return $resp;
                }

                $decoded = $this->customerTokenDecode($jwt_token);
                
                $customer_phone = $decoded->customer->contact_no;

                $customer_email = $decoded->customer->email;

                $prev_workout_session_count = \Booktrial::where('created_at', '>', new \DateTime('2018-04-22'))->where(function($query) use ($customer_email, $customer_phone){ return $query->orWhere('customer_email', $customer_email);})->where('type', 'workout-session')->count();
                
                
                if($prev_workout_session_count){
                    
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>$vendor_coupon, "error_message"=>"Coupon is valid for first time user only","user_login_error"=>true);

                    return $resp;
                }
                
            }

            if(isset($coupon['conditions']) && is_array($coupon['conditions']) && in_array('fitternity_employees', $coupon['conditions'])){
                
                $jwt_token = Request::header('Authorization');

                if(empty($jwt_token)){

                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"User Login Required","user_login_error"=>true);

                    return $resp;
                }

                $decoded = $this->customerTokenDecode($jwt_token);
                
                $customer_email = $decoded->customer->email;

                if(!in_array(strtolower($customer_email), Config::get('fitternityemails'))){
                    
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"Coupon is either not valid or expired","user_login_error"=>true);

                    return $resp;
                
                }
                
            }

            if(isset($coupon['conditions']) && is_array($coupon['conditions']) && in_array('once_per_month', $coupon['conditions'])){
                
                $jwt_token = Request::header('Authorization');

                if(empty($jwt_token)){

                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"User Login Required","user_login_error"=>true);

                    return $resp;
                }

                $decoded = $this->customerTokenDecode($jwt_token);
                
                $customer_phone = $decoded->customer->contact_no;
                $customer_email = $decoded->customer->email;
                
                $prev_workout_session_count = \Order::active()->where('success_date', '>', new \DateTime(date('d-m-Y', strtotime('first day of this month'))))->where(function($query) use ($customer_email, $customer_phone){ return $query->orWhere('customer_phone', 'LIKE', '%'.substr($customer_phone, -10).'%')->orWhere('customer_email', $customer_email);})->where('coupon_code', 'Like', $coupon['code'])->where('coupon_discount_amount', '>', 0)->count();

                if($prev_workout_session_count){
                    
                    $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"Already used","user_login_error"=>true);

                    return $resp;    
                }
                
            }

            if(isset($coupon['total_used']) && isset($coupon['total_available']) && $coupon['total_used'] >= $coupon['total_available']){
                    
                $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"This coupon has exhausted");

                return $resp;
            }

            if(isset($coupon['min_price']) && is_numeric($coupon['min_price']) &&  $price < $coupon['min_price']){
                
                $resp = array("data"=>array("discount" => 0, "final_amount" => $price, "wallet_balance" => $wallet_balance, "only_discount" => $price), "coupon_applied" => false, "vendor_coupon"=>false, "error_message"=>"Applicable on minimum purchase of Rs. ".$coupon['min_price'],"user_login_error"=>true);

                return $resp;
            }

            $discount_amount = $coupon["discount_amount"];
            $discount_amount = $discount_amount == 0 ? $coupon["discount_percent"]/100 * $price : $discount_amount;
            $discount_amount = intval($discount_amount);
            $discount_amount = $discount_amount > $coupon["discount_max"] ? $coupon["discount_max"] : $discount_amount;
            
            
            $discount_price = $price - $discount_amount;
            $final_amount = $discount_price > $wallet_balance ? $discount_price - $wallet_balance : 0;
            $vendor_routed_coupon = isset($coupon["vendor_routed_coupon"]) ? $coupon["vendor_routed_coupon"] : false;
            $resp = array("data"=>array("discount" => $discount_amount, "final_amount" => $final_amount, "wallet_balance" => $wallet_balance, "only_discount" => $discount_price), "coupon_applied" => true, 'otp'=>$fitternity_only_coupon, "vendor_coupon"=>$vendor_coupon, "vendor_routed_coupon" => $vendor_routed_coupon);
            if(isset($coupon['success_message']) && trim($coupon['success_message']) != ""){
                $resp['custom_message'] = str_replace("<amt>",$discount_amount,$coupon['success_message']);
            }

            if(isset($coupon['vendor_commission']) && is_numeric($coupon['vendor_commission'])){
                $resp['vendor_commission'] = $coupon['vendor_commission'];
            }
            if(isset($coupon['description'])){
                $resp['description'] = $coupon['description'];
            }
            
        }else{

            $applyCustomerCoupn = false;

            $resp = [
                "data"=>[
                    "discount" => 0,
                    "final_amount" => $price,
                    "wallet_balance" => $wallet_balance,
                    "only_discount" => $price
                ], 
                "coupon_applied" => $applyCustomerCoupn
            ];

            $customerCoupn = \CustomerCoupn::active()->where('code', strtolower($couponCode))->where('validity','>=',time())->first();

            if($customerCoupn){

                $jwt_token = Request::header('Authorization');

                if(!empty($jwt_token)){

                    $decoded = $this->customerTokenDecode($jwt_token);
                    $customer_id = $decoded->customer->_id;

                    if((int)$customerCoupn['customer_id'] !== $customer_id){

                        $resp['user_login_error'] = true;
                        $resp['error_message'] = 'Wrong Logged in User';

                        return $resp;
                    }

                }else{

                    $resp['user_login_error'] = true;
                    $resp['error_message'] = 'User Login Required';

                    return $resp;
                }

                if(!empty($ratecard['type']) && $ratecard['type'] == 'workout session'){

                    if(!empty($customerCoupn['service_category_id'])){

                        $service = Service::find((int)$ratecard['service_id']);

                        if($service && !empty($service['servicecategory_id']) && $service['servicecategory_id'] == $customerCoupn['service_category_id'] ){

                            $applyCustomerCoupn = true;

                        }else{

                            $resp['user_login_error'] = true;
                            $resp['error_message'] = 'This coupon is applicable only on swimming sessions.';

                            return $resp;
                        }

                    }else{

                        $applyCustomerCoupn = true;
                    }

                }

                if($applyCustomerCoupn){

                    $discount_amount = $customerCoupn["amount"];

                    $discount_price = $price - $discount_amount;

                    $final_amount = $discount_price > $wallet_balance ? $discount_price - $wallet_balance : 0;

                    $resp = [
                        "data"=>[
                            "discount" => $discount_amount,
                            "final_amount" => $final_amount,
                            "wallet_balance" => $wallet_balance,
                            "only_discount" => $discount_price
                        ],
                        "coupon_applied" => $applyCustomerCoupn
                    ];

                }
            }

            if(strtolower($couponCode) == 'zumba'){

                $jwt_token = Request::header('Authorization');

                if(!empty($jwt_token)){

                    $decoded = $this->customerTokenDecode($jwt_token);
                    $customer_id = $decoded->customer->_id;

                }else{

                    $resp['user_login_error'] = true;
                    $resp['error_message'] = 'User Login Required';

                    return $resp;
                }

                \Order::$withoutAppends = true;

                $order_count = \Order::active()->where('customer_id',$customer_id)->where('coupon_code','like', $couponCode)->count();

                if($order_count >= 1){

                    $resp['user_login_error'] = true;
                    $resp['error_message'] = 'This coupon is applicable only for 1 Zumba Session';

                    return $resp;

                }

                if(!empty($ratecard['type']) && $ratecard['type'] == 'workout session'){

                    $service = Service::find((int)$ratecard['service_id']);

                    if($service && !empty($service['servicecategory_id']) && $service['servicecategory_id'] == 19){

                        $applyCustomerCoupn = true;

                    }else{

                        $resp['user_login_error'] = true;
                        $resp['error_message'] = 'This coupon is applicable only on zumba sessions.';

                        return $resp;
                    }

                }

                if($applyCustomerCoupn){

                    $final_amount = 149;

                    if($wallet_balance >= $price){

                        $resp['coupon_applied'] = false;
                        $resp['user_login_error'] = true;
                        $resp['error_message'] = 'Use Fitcash First';

                        return $resp;

                    }else{

                        $discount_amount = $price - $final_amount;
                        $discount_price = $price - $final_amount;

                        if($wallet_balance >= $final_amount){

                            $final_amount = 0;

                        }else{

                            $final_amount = $final_amount - $wallet_balance;
                        }

                    }

                    $resp = [
                        "data"=>[
                            "discount" => $discount_amount,
                            "final_amount" => $final_amount,
                            "wallet_balance" => $wallet_balance,
                            "only_discount" => $discount_price
                        ],
                        "coupon_applied" => $applyCustomerCoupn
                    ];

                }

            }

            return $resp;

        }


        return $resp;
    }

    function hasCamapignOffer($data){
         
        return isset($data['flags']) && isset($data['flags']['campaign_offer']) && $data['flags']['campaign_offer'];
    
    }

}
