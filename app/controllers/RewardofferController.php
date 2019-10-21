    <?php

use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;


class RewardofferController extends BaseController {

    protected $utilities;

    public function __construct(
        Utilities $utilities
    ) {

        parent::__construct();
        
        $this->utilities = $utilities;
        
        $this->vendor_token = false;
        
        $vendor_token = Request::header('Authorization-Vendor');

        if($vendor_token){

            $this->vendor_token = true;
        }

        $this->kiosk_app_version = false;

        if($vendor_token){

            $this->vendor_token = true;

            $this->kiosk_app_version = (float)Request::header('App-Version');
        }

        $this->error_status = ($this->vendor_token) ? 200 : 400;
    }


    public function ListRewardsApplicableOnPurchase(){

        try {

            $data = Input::all();
            Log::info('ListRewardsApplicableOnPurchase Request', $data);

            $rules = array(
                'booktrial_type' => 'required',
                'findercategory_id' => 'required',
                'duration' => 'required',
                'duration_type' => 'required'
            );
            $validator = Validator::make($data,$rules);
            if ($validator->fails()) {
                return Response::json(
                    array(
                        'status' => 400,
                        'message' => $this->utilities->errorMessage($validator->errors()
                        )),400
                );
            }

            $query = Rewardoffer::active()
                ->with(array('rewards'=>function($query){$query->select('*')->where('status','=','1')->orderBy('_id', 'DESC');}))
                ->where('findercategory_id',(int) $data['findercategory_id'])
                ->where('booktrial_type',$data['booktrial_type']);

            (isset($data['duration']) && $data['duration'] != '')
                ? $query->where('duration', (int) $data['duration']) : null;
            (isset($data['duration_type']) && $data['duration_type'] != '')
                ? $query->where('duration_type', $data['duration_type']) : null;

            $rewardOfferData = $query->first(array('rewards'));
            $rewardOfferData = !empty($rewardOfferData) ? $rewardOfferData->toArray() : [];
            $existingRewards = isset($rewardOfferData['rewards']) ? $rewardOfferData['rewards'] : [];
            $rewards = array();
            foreach ($existingRewards as $key=>$value){
                array_push($rewards, array_except($value, array('updated_at','created_at','rewrardoffers')));

            }
            $renewal_cashback  = array('title'=>'Cashback of 15% on Renewal');
            $current_purchase_cashback  = array(
                'title'=>'10% Cashback on Membership Purchase',
                'percentage'=>'10%',
                'cap'=>1000,
                'flat'=>300
            );

            $data = array(
                'renewal_cashback'          =>   $renewal_cashback,
                'current_purchase_cashback' =>   $current_purchase_cashback,
                'rewards'                   =>   $rewards,
                'selection_limit'           =>   2
            );

            $resp   =   array('status' => 200, 'data' => $data);
            return Response::json($resp);


        }
        catch (Exception $e) {

            return Response::json(array('status' => 404,'message' => $e->getMessage()),404);
        }
    }

    public function errorMessage($errors){

        $errors = json_decode(json_encode($errors));
        $message = array();
        foreach ($errors as $key => $value) {
            $message[$key] = $value[0];
        }

        $message = implode(',', array_values($message));

        return $message;
    }

    public function getRewardOffers(){

        $data       = Input::json()->all();

        Log::info('----------------------getRewardOffers data-------------------',$data);


        $min_date = null;
        $max_date = null;

        $order              =   array();

        if(isset($data) && isset($data['type']) && $data['type'] == 'workout-session'){
            $rules      =   ['finder_id'=>'required', 'amount'=>'required', 'type'=>'required'];
            $validator  =   Validator::make($data,$rules);
            if ($validator->fails()) {
                return Response::json(array('status' => 401,'message' => $this->utilities->errorMessage($validator->errors())),$this->error_status);
            }
            $device             =   isset($data["device_type"]) ? $data["device_type"] : "";
            $finder_id          =   (int)$data['finder_id'];
            $amount             =   (int)$data['amount'];
            $customerReward     =   new CustomerReward();
            $calculation        =   $customerReward->purchaseGame($amount,$finder_id);

            $calculation['algo']['cashback'] = (int)$calculation['algo']['cashback'];

            if(isset($data['order_id']) && $data['order_id'] != ""){

                $order_id = (int) $data['order_id'];

                $order = Order::find($order_id);

                if(isset($order->payment_mode) && $order->payment_mode == "at the studio"){
                    $calculation = $customerReward->purchaseGame($amount,$finder_id,"at the studio");
                }

            }

            $cashback  = [
                'title'         =>  $calculation['algo']['cashback'].'% Discount on Purchase',
                'percentage'    =>  $calculation['algo']['cashback'].'%',
                'commision'     =>  $calculation['algo']['cashback'],
                'calculation'   =>  $calculation,
                'info'          =>  "",//"You can only pay upto 10% of the booking amount through FitCash. \nIt is calculated basis the amount, type and duration of the purchase.  \nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance_only_fitcash']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet'],
                'payload'=>[
                    'amount'=>!empty($calculation['wallet_amount']) ? $calculation['wallet_amount'] : 0
                ]
            ];
            /*if($calculation["current_wallet_balance_only_fitcash_plus"] > 0){
                $cashback["info"] = "You can only pay upto 10% of the booking amount through FitCash. \n\nIt is calculated basis the amount, type and duration of the purchase.  \n\nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance_only_fitcash_plus']."\n\nYour total FitCash+ balance is Rs. ".$calculation['current_wallet_balance_only_fitcash']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet'];
            }*/

            $renewal_cashback   =   ['title'=>'Discount on Renewal'];
            $rewards            =   [];
            $selection_limit    =   1;

            $data = [
                'renewal_cashback'          =>   $renewal_cashback,
                'cashback'                  =>   $cashback,
                'rewards'                   =>   $rewards,
                'selection_limit'           =>   $selection_limit,
                'status' => 200,
                'message' => "Rewards offers"
            ];

            return  Response::json($data, 200);

        }

        Finder::$withoutAppends = true;

        if($this->vendor_token){

            if(!isset($data['ratecard_id'])){

                $data['ratecard_id'] = true;
            }
            
            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

            $data['finder_id'] = (int) $vendor['_id'];

        }


        $rules      =   ['finder_id'=>'required', 'amount'=>'required', 'ratecard_id'=>'required'];
        $device     =   isset($data["device_type"]) ? $data["device_type"] : "";
        $validator  =   Validator::make($data,$rules);
        if ($validator->fails()) {
            return Response::json(array('status' => 401,'message' => $this->utilities->errorMessage($validator->errors())),$this->error_status);
        }

        $finder_id      =   (int)$data['finder_id'];
        $amount         =   (int)$data['amount'];
        $ratecard_id    =   (int)$data['ratecard_id'];
        $ratecard       =   Ratecard::where('_id',$ratecard_id)->where('finder_id',$finder_id)->first();
        $service_id = 99999999;

        if(isset($data['service_id']) && $data['service_id']){
            $service_id     =  (int) $data['service_id'];
        }

        if($ratecard){

            $service_id     =  (int) $ratecard['service_id'];
        }

        if(isset($data['order_id']) && $data['order_id'] != ""){

            $order = Order::find((int) $data['order_id']);

            if($order && isset($order['service_id']) && $order['service_id'] != ""){

                $service_id     =  (int) $order['service_id'];
            }

        }


        $service = Service::find($service_id);
        // echo"<pre>";print_r($service->servicecategory_id);exit;

        $service_category_id = null;
        $service_category_slug = "";
        $finder_category_id = null;
        $service_category = false;

        if($service){

            $service_category_id = (int)$service->servicecategory_id;

            if(isset($service['membership_start_date'])){
                $min_date = strtotime($service['membership_start_date']);
            }

            if(isset($service['membership_end_date'])){
                $max_date = strtotime($service['membership_end_date']);
            }
        }

        if(isset($data['service_category_id']) && $data['service_category_id'] != ""){

            $service_category_id = (int)$data['service_category_id'];
        }

        if($service_category_id != null){

            $service_category = Servicecategory::find($service_category_id);
        }

        if($service_category){

            $service_category_slug = $service_category['slug'];

            $finder_category = Findercategory::active()->where('slug',$service_category_slug)->first();

            if($service_category_slug == 'martial-arts'){
                 $finder_category = Findercategory::active()->where('slug','mma-and-kick-boxing')->first();  
            }
            
            if($finder_category){
                $finder_category_id = (int)$finder_category['_id'];
            }
        }

        if(isset($data['order_id']) && $data['order_id'] != ""){
            $order_id   = (int) $data['order_id'];
            $order      = Order::find($order_id);
            if(isset($order->payment_mode) && $order->payment_mode == "at the studio"){
                $amount = (int)$data['amount'];
            }
        }


        if($this->vendor_token && isset($data['manual_order']) && $data['manual_order']){

            $amount = (int)$data['amount'];

        }else{

            if(!$ratecard && count($order) == 0 && !isset($data['admin_get_rewards'])){
                $resp   =   array('status' => 401,'message' => "Ratecard Not Present");
                return  Response::json($resp, $this->error_status);
            }
        }

        /*if(isset($ratecard->special_price) && $ratecard->special_price > 0 && $ratecard->special_price != ""){
            $amount = $ratecard->special_price;
        }else{
            $amount = $ratecard->price;
        }*/

        $finder                 =   Finder::find($finder_id);
        $findercategory_id      =   intval($finder->category_id);
        $rewards                =   [];
        $finder_name            =   $finder->title;

        $cutl_vendor = false;
        $cutl_amount = $amount;

        if(isset($finder['brand_id']) && $finder['brand_id'] == 134){

            $min_date = strtotime(' + 2 days');
            $max_date = strtotime(' + 32 days');

            $cutl_vendor = true;
        }

        $city_id = (int)$finder['city_id'];

        // if($amount <= 1025){
        //     switch ($finder_id) {
        //         case 13765 :
        //             if(time() <= strtotime(date('2018-06-14 23:59:59'))){
        //                 $min_date = strtotime(date('2018-06-14 00:00:00'));
        //                 $max_date = strtotime(date('2018-06-14 23:59:59'));
        //             }
        //             break;
        //         case 13761 : 
        //             if(time() <= strtotime(date('2018-06-15 23:59:59'))){
        //                 $min_date = strtotime(date('2018-06-15 00:00:00'));
        //                 $max_date = strtotime(date('2018-06-15 23:59:59'));
        //             }
        //             break;
        //         case 14079 : 
        //             if(time() <= strtotime(date('2018-06-28 23:59:59'))){
        //                 $min_date = strtotime(date('2018-06-28 00:00:00'));
        //                 $max_date = strtotime(date('2018-06-28 23:59:59'));
        //             }
        //             break;
        //         case 14081 : 
        //             if(time() <= strtotime(date('2018-06-25 23:59:59'))){
        //                 $min_date = strtotime(date('2018-06-25 00:00:00'));
        //                 $max_date = strtotime(date('2018-06-25 23:59:59'));
        //             }
        //             break;
        //         case 14082 : 
        //             if(time() <= strtotime(date('2018-06-04 23:59:59'))){
        //                 $min_date = strtotime(date('2018-06-04 00:00:00'));
        //                 $max_date = strtotime(date('2018-06-04 23:59:59'));
        //             }
        //             break;
        //         case 14088 : 
        //             if(time() <= strtotime(date('2018-06-07 23:59:59'))){
        //                 $min_date = strtotime(date('2018-06-07 00:00:00'));
        //                 $max_date = strtotime(date('2018-06-07 23:59:59'));
        //             }
        //             break;
        //         case 14085 : 
        //             if(time() <= strtotime(date('2018-06-30 23:59:59'))){
        //                 $min_date = strtotime(date('2018-06-30 00:00:00'));
        //                 $max_date = strtotime(date('2018-06-30 23:59:59'));
        //             }
        //             break;
        //         case 14078 : 
        //             if(time() <= strtotime(date('2018-06-30 23:59:59'))){
        //                 $min_date = strtotime(date('2018-06-30 00:00:00'));
        //                 $max_date = strtotime(date('2018-06-30 23:59:59'));
        //             }
        //             break;
                
        //         default: break;
        //     }     
        // }

        $service_name           =   "";
        $service_duration       =   "";

        if(isset($service) && isset($service->name)){
            $service_name           =   $service->name;
        }

        if($ratecard){
            $service_duration = $ratecard['validity'].' '.$ratecard['validity_type'];
        }

        if(isset($order) && isset($order->service_duration)){
            $service_duration       =   $order->service_duration;
        }               

        if(isset($finder->purchase_gamification_disable) && $finder->purchase_gamification_disable == "1"){
            $rewards = array();
        }else{

            // if($device == "website"){
            //     // return $device;
            //     $rewardoffer           =   Rewardoffer::active()->where('findercategory_id', $findercategory_id)
            //         ->where('amount_min','<', $amount)
            //         ->where('amount_max','>=', $amount)
            //         // ->whereNotIn('reward_type',['personal_trainer_at_home'])
            //         ->with(array('rewards'=> function($query){$query->select('*')->where('reward_type','!=','personal_trainer_at_home');}  ))
            //         // ->with('rewards')
            //         ->orderBy('_id','desc')->first();
            // }else{
            //     $rewardoffer           =   Rewardoffer::active()->where('findercategory_id', $findercategory_id)
            //         ->where('amount_min','<', $amount)
            //         ->where('amount_max','>=', $amount)
            //         ->with(array('rewards'=> function($query){$query->select('*')->where('reward_type','!=','diet_plan');}  ))
            //         ->orderBy('_id','desc')->first();
            // }

            if($finder_category_id != null){
                $findercategory_id = $finder_category_id;
            }


            Log::info('------------------------------findercategory_id --------------------------'.$findercategory_id);

            if($cutl_vendor){

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

            $rewardoffer           =   Rewardoffer::active()->where('findercategory_id', $findercategory_id)
                    ->where('amount_min','<=', $amount)
                    ->where('amount_max','>', $amount)
                    // ->whereNotIn('reward_type',['personal_trainer_at_home'])
                    ->with(array('rewards'=> function($query){$query->select('*')->whereNotIn('reward_type',['healthy_snacks', 'personal_trainer_at_home']);}  ))
                    // ->with('rewards')
                    ->orderBy('_id','desc')->first();

                                        // return $rewardoffer;
            if ($rewardoffer){

                $power_world_gym = [10861,10863,10868,10870,10872,10875,10876,10877,10880,10883,10886,10887,10888,10890,10891,10892,10894,10895,10897,10900];

                $multifit_qym = [9932,9954,12208,11223,1935,9481,9423,9304,13094,10970,13898,11021,14102,14107,13968,9600];

                $rewardoffer = $rewardoffer->toArray();
                
                $rewards = isset($rewardoffer['rewards']) ? $rewardoffer['rewards'] : array();

                $diet_inclusive_service = Service::where('finder_id', $finder['_id'])->where('diet_inclusive', true)->get();
                    
                if(count($diet_inclusive_service)>0){
                    
                    foreach($rewards as $key => $reward){
                        if($reward['reward_type']=='diet_plan'){
                            array_splice($rewards, $key, 1);
                        }
                    }
                }

                if(count($rewards) > 0){
                    foreach ($rewards as $key => $value){
                        unset($rewards[$key]['rewrardoffers']);
                        unset($rewards[$key]['updated_at']);
                        unset($rewards[$key]['created_at']);
                        if(isset($value['payload']) && isset($value['payload']['amount']) && $value['payload']['amount'] != "" && isset($value['quantity']) && $value['quantity'] != ""){
                            $rewards[$key]['payload']['amount'] = $value['payload']['amount'] * $value['quantity'];
                        }
                    }

                    $reward_ordered = array();

                    $reward_type_order = array(
                        'fitness_kit',
                        //  'diet_plan',
                        'sessions',
                        'healthy_snacks',
                        'personal_trainer_at_home',
                        'healthy_tiffin',
                        'nutrition_store',
                        'fitternity_voucher',
                        'swimming_sessions'
                    );


                    if(in_array($finder_id, Config::get('app.diet_reward_excluded_vendors'))){
                        $reward_type_order = array(
                            'fitness_kit',
                            'sessions',
                            'healthy_snacks',
                            'personal_trainer_at_home',
                            'healthy_tiffin',
                            'nutrition_store',
                            'fitternity_voucher',
                            'swimming_sessions'
                        );
                    }

                    if(isset($ratecard) && ($ratecard["type"] == "trial" || $ratecard["type"] == "workout session")){
                        $rewards = [];
                    }
                    foreach ($reward_type_order as $reward_type_order_value){
                        // if($amount < 2000){
                        //     $rewards = [];        
                        // }
                        foreach ($rewards as &$rewards_value){

                            if(in_array($rewards_value['reward_type'],["fitness_kit","healthy_snacks"]) && $service_category_id != null){

                                $reward_data  = [ 
                                    'contents'=>[],
                                    'payload_amount'=>0,
                                    'image'=>'',
                                    'gallery'=>[]
                                ];

                                $reward_data_flag = false;

                                $reward_type_info = $rewards_value['reward_type'];

                                if($reward_type_info == 'fitness_kit'){

                                    $pos = strpos($rewards_value['title'],'(Kit B)');

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

                                                    $reward_data['contents'] = $content_value['product'];
                                                    $reward_data['payload_amount'] = $content_value['amount'];
                                                    $reward_data['image'] = $content_value['image'];
                                                    $reward_data['gallery'] = $content_value['gallery'];

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

                                                $reward_data['contents'] = $data_value['content'][0]['product'];
                                                $reward_data['payload_amount'] = $data_value['content'][0]['amount'];
                                                $reward_data['image'] = $data_value['content'][0]['image'];
                                                $reward_data['gallery'] = $data_value['content'][0]['gallery'];

                                                break;
                                            }
                                        }
                                    }

                                    $cult_gym = [13761,13762,13763,13764,13765,14078,14079,14081,14082,14085,14088];

                                    if(in_array($finder_id,$cult_gym) && $amount <= 1025){

                                        $pos = strpos($rewards_value['title'],'(Kit B)');

                                        if($pos === false){

                                            $reward_type_info = 'fitness_kit';

                                            $reward_data['contents'] = ['Cool-Water Bottle'];
                                            $reward_data['payload_amount'] = 300;
                                            $reward_data['image'] = 'https://b.fitn.in/gamification/reward_new/new/Bottle_1.png';
                                            $reward_data['gallery'] = [];

                                        }else{

                                            $reward_type_info = 'fitness_kit_2';

                                            $reward_data['contents'] = ['Waterproof Gym Bag'];
                                            $reward_data['payload_amount'] = 850;
                                            $reward_data['image'] = 'https://b.fitn.in/gamification/reward_new/new/GymBag_1.png';
                                            $reward_data['gallery'] = [];
                                        }
                                        
                                    }

                                }

                                $array = [];

                                if($reward_type_info == 'healthy_snacks'){

                                    switch(true){
                                        
                                        case $amount < 2000 :
                                            break;

                                        case (2000 <= $amount && $amount < 5000) :

                                            $array = [
                                                'healthy_snacks' => [
                                                    'payload_amount' => 300,
                                                    'contents' => [
                                                        "Pop Mak – Roasted Flavoured Makhana (small)",
                                                        "2 Honey Chew Pouch (5 flavours)",
                                                        "Good Juicery (Sugar Free Sparkling) Juice"
                                                    ],
                                                    'image' => "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg",
                                                    'gallery'=>[]
                                                ],
                                            ];

                                            break;

                                        case (5000 <= $amount && $amount < 7500) :

                                            $array = [
                                                'healthy_snacks' => [
                                                    'payload_amount' => 510,
                                                    'contents' => [
                                                        "Pop Mak – Roasted Flavoured Makhana (small)",
                                                        "2 Honey Chew Pouch (5 flavours)",
                                                        "Good Juicery (Sugar Free Sparkling) Juice",
                                                        "Baked Pizza Stick Dippers"
                                                    ],
                                                    'image' => "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg",
                                                    'gallery'=>[]
                                                ],
                                            ];

                                            break;

                                        case (7500 <= $amount && $amount < 10000) :

                                            $array = [
                                                'healthy_snacks' => [
                                                    'payload_amount' => 700,
                                                    'contents' => [
                                                        "2 Pop Mak – Roasted Flavoured Makhana (small)",
                                                        "Honey Chew Pouch (5 flavours)",
                                                        "2 Good Juicery (Sugar Free Sparkling) Juice",
                                                        "Baked Pizza Stick Dippers"
                                                    ],
                                                    'image' => "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg",
                                                    'gallery'=>[]
                                                ],
                                            ];

                                            break;

                                        case (10000 <= $amount && $amount < 15000) :

                                            $array = [
                                                'healthy_snacks' => [
                                                    'payload_amount' => 1600,
                                                    'contents' => [
                                                        "Pop Mak – Roasted Flavoured Makhana (small)",
                                                        "Honey Chew Pouch (5 flavours)",
                                                        "Baked Pizza Stick Dippers",
                                                        "Pop Mak – Roasted Flavoured Makhana (big)",
                                                        "Colonel & Co. Nachos with Dip", "Kettle Chips"
                                                    ],
                                                    'image' => "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg",
                                                    'gallery'=>[]
                                                ],
                                            ];

                                            break;

                                        case (15000 <= $amount && $amount < 20000) :

                                            $array = [
                                                'healthy_snacks' => [
                                                    'payload_amount' => 1600,
                                                    'contents' => [
                                                        "Pop Mak – Roasted Flavoured Makhana (small)",
                                                        "2 Honey Chew Pouch (5 flavours)",
                                                        "Good Juicery (Sugar Free Sparkling) Juice",
                                                        "Baked Pizza Stick Dippers",
                                                        "Colonel & Co. Nachos with Dip",
                                                        "3 Kettle Chips",
                                                        "Wholewheat Thins"
                                                     ],
                                                    'image' => "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg",
                                                    'gallery'=>[]
                                                ],
                                            ];

                                            break;

                                        case (20000 <= $amount && $amount < 25000) :

                                            $array = [
                                                'healthy_snacks' => [
                                                    'payload_amount' => 2020,
                                                    'contents' => [
                                                        "Pop Mak – Roasted Flavoured Makhana (small)",
                                                        "2 Honey Chew Pouch (5 flavours)",
                                                        "Good Juicery (Sugar Free Sparkling) Juice",
                                                        "2 Pop Mak – Roasted Flavoured Makhana (big)",
                                                        "Baked Pizza Stick Dippers",
                                                        "Colonel & Co. Nachos with Dip",
                                                        "2 Kettle Chips","Wholewheat Thins"
                                                    ],
                                                    'image' => "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg",
                                                    'gallery'=>[]
                                                ],
                                            ];

                                            break;

                                        case (25000 <= $amount) :

                                            $array = [
                                                'healthy_snacks' => [
                                                    'payload_amount' => 2600,
                                                    'contents' => [
                                                        "Pop Mak – Roasted Flavoured Makhana ",
                                                        "2 Honey Chew Pouch (5 flavours)",
                                                        "2 Good Juicery (Sugar Free Sparkling) Juice",
                                                        "2 Baked Pizza Stick Dippers",
                                                        "Colonel & Co. Nachos with Dip",
                                                        "Banana & Chia Granola Crunchers",
                                                        "Wholewheat Thins",
                                                        "2 Pop Mak – Roasted Flavoured Makhana"
                                                    ],
                                                    'image' => "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg",
                                                    'gallery'=>[]
                                                ],
                                            ];

                                            break;
                                    }
                                }

                                if(!empty($array) && $reward_type_info == 'healthy_snacks'){

                                    $rewards_value['payload']['amount'] = $array[$reward_type_info]['payload_amount'];
                                    $rewards_value['contents'] = $array[$reward_type_info]['contents'];
                                    $rewards_value['image'] = $array[$reward_type_info]['image'];
                                    $rewards_value['gallery'] = $array[$reward_type_info]['gallery'];

                                    $rewards_value['description'] = "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains. <br>- ".implode(" <br>- ",$rewards_value['contents']);

                                    /*$rewards_value['image'] = "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                    $rewards_value['gallery'] = [];*/
                                }

                                if(in_array($rewards_value['reward_type'],["fitness_kit"])){

                                    $rewards_value['description'] = "We have shaped the perfect fitness kit for you. Strike off these workout essentials from your cheat sheet & get going. <br>- ".implode(" <br>- ",$reward_data['contents']);

                                    $rewards_value['contents'] = $reward_data['contents'];
                                    $rewards_value['payload']['amount'] = $reward_data['payload_amount'];
                                    $rewards_value['image'] = $reward_data['image'];
                                    $rewards_value['gallery'] = $reward_data['gallery'];
                                }

                                
                            }else{

                                $rewards_value['image'] = "https://b.fitn.in/gamification/reward/".$rewards_value['reward_type'].".jpg";
                            }

                            if($rewards_value['reward_type'] == "diet_plan"){
                                $rewards_value['service_id'] = Service::where('_id', 19370)->first(['_id'])->_id;
                                switch($rewards_value['_id']){
                                    case 27:
                                    $validity = 14;
                                    $validity_type = 'days';
                                    
                                    break;
                                    case 28:
                                    $validity = 1;
                                    $validity_type = 'months';
                                    break;
                                    case 29:
                                    $validity = 2;
                                    $validity_type = 'months';
                                    break;
                                    case 30:
                                    $validity = 3;
                                    $validity_type = 'months';
                                    break;
                                    case 31:
                                    $validity = 3;
                                    $validity_type = 'months';
                                    break;
                                    case 32:
                                    $validity = 1;
                                    $validity_type = 'days';
                                    break;
                                    
                                }

                                $diet_ratecard = Ratecard::where('service_id', $rewards_value['service_id'])->where('validity', $validity)->where('validity_type', $validity_type)->first(['_id']);
                                if($diet_ratecard){
                                    $rewards_value['ratecard_id'] = $diet_ratecard['_id'];
                                }
                            }

                            if($rewards_value['reward_type'] == $reward_type_order_value){

                                Log::info( $reward_type_order_value);
                                $reward_type_array = ["fitness_kit","healthy_snacks"];

                                if(isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && in_array($rewards_value['reward_type'], $reward_type_array)){
                                   
                                    $rewards_value['contents'] = str_replace("<br>", "\n", $rewards_value['contents']);
                                    $rewards_value['description'] = str_replace("<br>", "\n", $rewards_value['description']);
                                }

                                $reward_ordered[] = $rewards_value;

                                // break;
                            }

                            if($rewards_value['reward_type'] == "sessions"){

                                $reward_type_info = 'sessions';

                                $workout_session_array = Config::get('fitness_kit.workout_session');

                                rsort($workout_session_array);

                                foreach ($workout_session_array as $data_key => $data_value) {

                                    if($amount >= $data_value['min'] ){

                                        $session_content = "Get access to FREE workouts - Anytime, Anywhere <br>- Across 12,000+ fitness centres & 7 cities <br>- 75,000 classes every week: Crossfit, Zumba, Yoga, Kickboxing & 13 more fitness forms <br>- Book real-time & get instant confirmation";

                                        $rewards_value['payload_amount'] = $data_value['amount'];
                                        $rewards_value['new_amount'] = $data_value['amount'];
                                        $rewards_value['title'] = "Workout Session Pack (".$data_value['total']." Sessions)";
                                        $rewards_value['contents'] = ['Workout Session Pack'];
                                        $rewards_value['gallery'] = [];
                                        $rewards_value['description'] = $session_content;
                                        $rewards_value['quantity'] = $data_value['total'];
                                        $rewards_value['payload']['amount'] = $data_value['amount'];

                                        break;
                                    }
                                }
                            }

                            if($rewards_value['reward_type'] == "swimming_sessions"){

                                $reward_type_info = 'swimming_sessions';

                                $swimming_session_array = Config::get('fitness_kit.swimming_session');

                                rsort($swimming_session_array);

                                foreach ($swimming_session_array as $data_key => $data_value) {

                                    if($amount >= $data_value['min'] ){

                                        $session_content = "Get a luxury experience like never before - VIP swimming session in city's best 5-star hotels <br>- Book across 50 hotels in 7 cities <br>- Hotels including JW Marriott, Hyatt, Sofitel, Lalit & many more <br>- Book real-time & get instant confirmation";

                                        $rewards_value['payload_amount'] = $data_value['amount'];
                                        $rewards_value['new_amount'] = $data_value['amount'];
                                        $rewards_value['title'] = "Swimming at 5-star Hotels (".$data_value['total']." Sessions)";
                                        $rewards_value['contents'] = ['Swimming at 5-star Hotels'];
                                        $rewards_value['gallery'] = [];
                                        $rewards_value['description'] = $session_content;
                                        $rewards_value['quantity'] = $data_value['total'];
                                        $rewards_value['payload']['amount'] = $data_value['amount'];
                                        $rewards_value['list'] = [];

                                        break;
                                    }
                                }

                                $swimming_service_ids = Service::where('city_id',$service['city_id'])->where('location_id',$service['location_id'])->where('servicecategory_id',123)->lists('_id');

                                if(!empty($swimming_service_ids)){

                                    $swimming_service_ids = array_map('intval',$swimming_service_ids);

                                    $swimming_finder_ids = Ratecard::whereIn('service_id',$swimming_service_ids)->where('type','workout session')->lists('finder_id');

                                    if(!empty($swimming_finder_ids)){

                                        $swimming_finder_ids = array_map('intval',$swimming_finder_ids);

                                        $swimming_finders = Finder::whereIn('_id',$swimming_finder_ids)->get(['title','slug','_id']);

                                        if($swimming_finders){

                                           $rewards_value['list'] = $swimming_finders->toArray();

                                        }
                                    }
                                }

                            }

                        }
                    }

                    $rewards = $reward_ordered;

                }
            }
        }

        if(!empty($rewards)){

            $fitness_kit_1 = 0;
            $fitness_kit_2 = 0;

            foreach ($rewards as $rewards_key => $rewards_value) {

                if($rewards_value['reward_type'] == 'fitness_kit'){

                    $pos = strpos($rewards_value['title'],'(Kit B)');

                    if($pos === false){

                        $fitness_kit_1 = (int)$rewards_key;

                    }else{

                       $fitness_kit_2 = (int)$rewards_key;  
                    }
                }
            }

            if($fitness_kit_1 > $fitness_kit_2){

                $data_fitness_kit_1 = $rewards[$fitness_kit_1];
                $data_fitness_kit_2 = $rewards[$fitness_kit_2];

                $rewards[$fitness_kit_1] = $data_fitness_kit_2;
                $rewards[$fitness_kit_2] = $data_fitness_kit_1;
            }

            if(in_array($finder_id,$power_world_gym) && $amount == 3500){

                $fitness_kit_count = 0;

                foreach ($rewards as $rewards_key => &$rewards_value) {

                    if($rewards_value['reward_type'] == 'fitness_kit'){

                        if($fitness_kit_count == 0){

                            $rewards_value['title'] = "Fitness Merchandise";
                            $rewards_value['contents'] = ['Waterproof Gym Bag'];
                            $rewards_value['image'] = 'https://b.fitn.in/gamification/reward_new/new/GymBag_1.png';
                            $rewards_value['gallery'] = [];
                            $rewards_value['description'] = "We have curated the perfect partner to kickstart your membership. Strike off this workout essential from your list & get going. <br>- Gym Bag with separate shoe compartment";
                            $rewards_value['payload']['amount'] = 850;
                            $rewards_value['new_amount'] = 850;

                            $fitness_kit_count = 1;

                        }else{

                            unset($rewards[$rewards_key]);
                            break;
                        }
                    }
                }
            }

        }

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

        // if(isset($finder['brand_id']) && $finder['brand_id'] == 66 && $finder['city_id'] == 3 && $duration_day == 360){
        // || (in_array($finder['brand_id'], [135,166,88]) && in_array($duration_day, [180, 360])) 
        
        if((in_array($finder['_id'], Config::get('app.mixed_reward_finders')) && $duration_day == 360) 
        || (in_array($finder['brand_id'], [135,166]) && in_array($duration_day, [180, 360])) 
        || ((in_array($finder['_id'], Config::get('app.upgrade_session_finder_id'))) && $ratecard['type'] == 'extended validity')
        || ((in_array($finder['_id'], Config::get('app.extended_mixed_finder_id'))) && $ratecard['type'] == 'membership')
        ){
            
            $rewardObj = $this->getMixedReward();

			// if(in_array($finder['brand_id'], [135, 166, 88]) && $ratecard['type'] != 'extended validity'){
            if(in_array($finder['brand_id'], [135, 166]) && $ratecard['type'] != 'extended validity'){
                if($finder['brand_id']==135){
                    $mixedreward_content = MixedRewardContent::where('brand_id', $finder['brand_id'])->where('finder_id',$finder['_id'])->where("duration",$duration_day)->first();
                }
                else {
                    $mixedreward_content = MixedRewardContent::where('brand_id', $finder['brand_id'])->where("duration",$duration_day)->first();
                }
			}else{
                $mixedreward_content = MixedRewardContent::where('finder_id', $finder['_id'])->first();
            }
            if(!empty($mixedreward_content)){
                $gold_mixed = true;
				if($rewardObj && $mixedreward_content){
                    
					$rewards = [];

					$rewardObjData = $rewardObj->toArray();

                    $this->unsetRewardObjFields($rewardObjData);

                    $swimming_session_array = Config::get('fitness_kit.swimming_session');

					foreach ($swimming_session_array as $data_key => $data_value) {

						if($amount >= $data_value['min'] ){

							$no_of_sessions = $data_value['total'];
							break;
						}
					}

					$no_of_sessions = (!empty($no_of_sessions) ? ($no_of_sessions == 1 ? '1 person' : $no_of_sessions.' people') : '1 person');

					$rewards_snapfitness_contents = $mixedreward_content->reward_contents;

					foreach($rewards_snapfitness_contents as &$content){
						$content = bladeCompile($content, ['no_of_sessions'=>$no_of_sessions]);
					}

                    list($rewardObjData) = $this->compileRewardObject($mixedreward_content, $rewardObjData, $rewards_snapfitness_contents);

                    list($rewardObjData) = $this->rewardObjDescByDuration($mixedreward_content, $duration_day, $rewardObjData);

                    $rewards[] = $rewardObjData;
				}
			}
            
        }

        if($amount > 8000 && in_array($ratecard['type'],["membership"])){
            $rewardObj = $this->getMixedReward();
            $mixedreward_content = MixedRewardContent::where('flags.type', 'membership')->first();

            if(!empty($mixedreward_content)){
                if($rewardObj && $mixedreward_content){
                    
					$rewards = [];

					$rewardObjData = $rewardObj->toArray();

                    $this->unsetRewardObjFields($rewardObjData);

					
					$rewards_snapfitness_contents = $mixedreward_content->reward_contents;

					foreach($rewards_snapfitness_contents as &$content){
						$content = bladeCompile($content, ['no_of_sessions'=>$no_of_sessions]);
					}

                    list($rewardObjData) = $this->compileRewardObject($mixedreward_content, $rewardObjData, $rewards_snapfitness_contents);

                    list($rewardObjData) = $this->rewardObjDescByDuration($mixedreward_content, $duration_day, $rewardObjData);

                    $rewards[] = $rewardObjData;
				}
			}
        }

        if(empty($mixedreward_content)){
           
            if(!empty($finder['flags']['reward_type']) && in_array($finder['flags']['reward_type'], Config::get('app.no_instant_reward_types')) && !empty($this->vendor_token)){
                
                $rewardObj = $this->getMixedReward();
                $cashback_type = !empty($finder['flags']['cashback_type']) ? $finder['flags']['cashback_type'] : null;
                $mixedreward_content = MixedRewardContent::where('reward_type',$finder['flags']['reward_type'])->first();
                
                if(!empty($mixedreward_content)){
                    
                    if($rewardObj && $mixedreward_content){				
                        $no_instant_rewards = true;
                        $rewards = [];
                        
                        $rewardObjData = $rewardObj->toArray();

                        $this->unsetRewardObjFields($rewardObjData);

                        $rewards_snapfitness_contents = $mixedreward_content->reward_contents;

                        // $cashback = 100;

                        // // switch($cashback_type){
                        // //     case 1:
                        // //     case 2:
                        // //         $cashback = 120;
                           
                        // // }

                        // foreach($rewards_snapfitness_contents as &$content){
                        //     $content = bladeCompile($content, ['cashback'=>$cashback]);
                        // }

                        list($rewardObjData) = $this->compileRewardObject($mixedreward_content, $rewardObjData, $rewards_snapfitness_contents);

                        array_unshift($rewards, $rewardObjData);
                    }
                }
            }

            if(empty($mixedreward_content) && in_array($finder['_id'], Config::get('app.women_mixed_finder_id', []))){
                $rewardObj = $this->getMixedReward();
                
                $mixedreward_content = MixedRewardContent::where('finder_id',$finder['_id'])->first();
    
                if(!empty($mixedreward_content)){

                    if($rewardObj && $mixedreward_content){
    
                        $rewardObjData = $rewardObj->toArray();

                        $this->unsetRewardObjFields($rewardObjData);

                        $rewards_snapfitness_contents = $mixedreward_content->reward_contents;
    
                        $free_sp_ratecard = $this->utilities->getFreeSPRatecard($ratecard, 'ratecard');
                        $free_sp_ratecard_duration = null;
                        if(!empty($free_sp_ratecard)){
                            $free_sp_ratecard_duration = $free_sp_ratecard['duration'];
                            if(!empty($free_sp_ratecard['price'])){
                                $mixedreward_content['total_amount'] += $free_sp_ratecard['price'];
                            }
                            array_push($rewards_snapfitness_contents, $mixedreward_content->session_pack_reward_content);
                            $images = $mixedreward_content['images'];
                            array_push($images, $mixedreward_content->session_pack_image);
                            $mixedreward_content['images'] = $images;
                        }
    
                        foreach($rewards_snapfitness_contents as &$content){
                            $content = bladeCompile($content, ['finder_name'=>$finder['title'], 'no_of_sessions'=>$free_sp_ratecard_duration]);
                        }

                        list($rewardObjData) = $this->compileRewardObject($mixedreward_content, $rewardObjData, $rewards_snapfitness_contents);
                        // $rewards[] = $rewardObjData;
                        array_unshift($rewards, $rewardObjData);
                    }
                }
    
            }
    
            
        }

        if(!empty($rewards)){

            foreach ($rewards as $reward_key => $reward_value) {

                if($reward_value['reward_type'] == 'fitness_kit' && isset($reward_value['payload']['amount']) && $reward_value['payload']['amount'] == 0){
                    unset($rewards[$reward_key]);
                }

                if(!empty($multifit_qym) && in_array($finder_id,$multifit_qym) & $reward_value['reward_type'] == 'diet_plan'){
                    unset($rewards[$reward_key]);
                }

                if($reward_value['reward_type'] == 'swimming_sessions' && in_array($city_id,[5,6])){
                    unset($rewards[$reward_key]);
                }

                if(in_array($reward_value['reward_type'],['swimming_sessions','sessions']) && !empty($this->app_version) && floatval($this->app_version) < 4.9){
                    unset($rewards[$reward_key]);
                }
            }

        }

        $cashback = null;
        
        $customerReward     =   new CustomerReward();

        $amount = $cutl_amount;

        if($amount < 50000 || !isset($_GET['device_type'])){   
            
            $calculation        =   $customerReward->purchaseGame($amount,$finder_id);

            if(isset($data['order_id']) && $data['order_id'] != ""){

                $order_id = (int) $data['order_id'];

                $order = Order::find($order_id);

                if(isset($order->payment_mode) && $order->payment_mode == "at the studio"){
                    $calculation = $customerReward->purchaseGame($amount,$finder_id,"at the studio");
                }

                if(isset($order->part_payment) && $order->part_payment){
                    $part_payment = true;
                }

            }

            $calculation['algo']['cashback'] = (int)$calculation['algo']['cashback'];

            $cashback  = array(
                // 'title'=>$calculation['algo']['cashback'].'% Discount on Purchase',
                'title'=>$calculation['algo']['cashback'].'% Instant Cashback on Purchase',
                'percentage'=>$calculation['algo']['cashback'].'%',
                'commision'=>$calculation['algo']['cashback'],
                'calculation'=>$calculation,
                'info'          =>  "",//"You can only pay upto 10% of the booking amount through FitCash. \n\nIt is calculated basis the amount, type and duration of the purchase.  \n\nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance_only_fitcash']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet'],
                'description'=>$calculation['description'],
                'payload'=>[
                    'amount'=>!empty($calculation['wallet_amount']) ? $calculation['wallet_amount'] : 0
                ]
            );
            /*if($calculation["current_wallet_balance_only_fitcash_plus"] > 0){
                $cashback["info"] = "You can only pay upto 10% of the booking amount through FitCash. \n\nIt is calculated basis the amount, type and duration of the purchase.  \n\nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance_only_fitcash_plus']."\n\nYour total FitCash+ balance is Rs. ".$calculation['current_wallet_balance_only_fitcash']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet'];
            }*/

            unset($cashback['calculation']['description']);

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

            $duration_month = 0;

            if(isset($duration_day)){
                $duration_month = ceil($duration_day/30) + 2;
            }

            $cashback['description'] = "Get Rs ".$calculation['wallet_amount'].". Fitcash+ in your wallet as cashback which is fully redeemable against any Memberships/Session purchase on Fitternity. Earned cashback is valid for ".$duration_month." months. Cashback chosen as reward can be availed for renewal";
        }

        $renewal_cashback  = array('title'=>'Discount on Renewal');
        $selection_limit = 1;

        if($this->vendor_token && isset($cashback['commision']) && !$cashback['commision']){

            $cashback = null;
        }

        if(isset($data['ratecard_id']) && gettype($data['ratecard_id']) == 'integer' && in_array($data['ratecard_id'], [103151,103152,103153,103154,103155,103156,103157,103158])){
            $rewards = [];
            $cashback = null;
        }

        if(!empty($finder['flags']['reward_type']) && in_array($finder['flags']['reward_type'], Config::get('app.no_instant_reward_types')) && empty($this->vendor_token)){
            $rewards = [];
            $cashback = null;
        }
        
        if(isset($finder['brand_id']) && $finder['brand_id'] == 66 && $finder['city_id'] == 3 && $duration_day == 360){
            $cashback = null;
        }
        
        if(!empty($finder['_id']) && $finder['_id'] == 11230 && $duration_day == 360){
            $cashback = null;
        }
        if(!empty($gold_mixed) || !empty($no_instant_rewards)){
            $cashback = null;
        }
        
        $upgradeMembership = $this->addUpgradeMembership($data, $ratecard);
        $multifitFinder = $this->utilities->multifitFinder();
        if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && in_array($finder_id, $multifitFinder)){
            Log::info('multifit');

            if(!empty($rewards)){
                $rew = [];
                foreach($rewards as $k => $v){
                    Log::info(" +++++++++++++++++++++++",[$v['title']]);
                        
                    $v['title'] = str_replace("Fitternity ","",$v['title']);
                    $v['description'] = str_replace("Fitternity ","",$v['description']);
                    
                    Log::info("after +++++++++++++++++++++++",[$v['title']]);

                    $rew[] = $v;
                }

                $rewads = [];
                $rewards = $rew;
                // Log::info("1out +++++++++++++++++++++++",[$rewards]);
            }
        }

        $data = array(
            'renewal_cashback'          =>   $renewal_cashback,
            'cashback'                  =>   $cashback,
            'rewards'                   =>   array_values($rewards),
            'selection_limit'           =>   $selection_limit,
            'status'                    =>  200,
            'message'                   =>  "Rewards offers",
            'amount'                    =>  $amount
        );

        if(!empty($upgradeMembership) && !empty($upgradeMembership['start_date'])){
            $data['upgrade_membership'] = true;
            $data['start_date'] = $upgradeMembership['start_date'];
            $data['title_text'] = "Upgrading your membership to higher a duration ";
        }

        if(empty($calculation['algo']['cashback'])){
            $data['cashback'] = null;
        }
        // $data['cross_sell'] = array(
        //     'diet_plan' => $customerReward->fitternityDietVendor($amount)
        // );
        $data['diet_plan'] = $customerReward->fitternityDietVendor($amount);

        $data['text'] = "Buying $service_name at $finder_name for $service_duration makes you eligible for a COMPLIMENTARY reward from Fitternity! Scroll options below & select";

        if(isset($part_payment)){

            $data['amount'] = ($order->customer_amount)/5;

        }

        if($this->utilities->isConvinienceFeeApplicable($ratecard, "ratecard")){

            $convinience_fee_percent = Config::get('app.convinience_fee');

            $convinience_fee = number_format($amount*$convinience_fee_percent/100, 0);

            $convinience_fee = $convinience_fee <= 199 ? $convinience_fee : 199;

            $data['convinience_fee'] = $convinience_fee;
        }

        if($this->utilities->checkCorporateLogin()){
            $data['corporate_login'] = true;
            unset($data['cashback']);
        }

        $data['min_date'] = $min_date;
        $data['max_date'] = $max_date;

        return  Response::json($data, 200);

    }

    public function addUpgradeMembership($data, $ratecard){
        
        if(empty($ratecard)){
            return;
        }
        
        $duration_day = $this->utilities->getDurationDay($ratecard);
        
        $customer = $this->utilities->getCustomerFromToken();
        
        if(empty($customer)){
            return;
        }
        
        $customer_id = $customer['_id'];
        
        $wallet = Wallet::active()->where('balance', '>', 0)->where('used', 0)->where('customer_id', $customer_id)->whereIn('service_id', [null, $ratecard['service_id']])->where('valid_finder_id', $ratecard['finder_id'])->where('restricted_for', 'upgrade')->whereIn('duration_day', [null, $duration_day])->whereIn('order_type', [null, $ratecard['type']])->first();

        if(!empty($wallet)){
            $order = Order::find($wallet['order_id'], ['start_date']);
        }

        if(!empty($order)){
            return ['start_date'=>date('d-m-Y', strtotime($order['start_date']))];
        }
        
    }

    /**
     * @param $mixedreward_content
     * @param $rewardObjData
     * @param $rewards_snapfitness_contents
     * @return array
     */
    public function compileRewardObject($mixedreward_content, $rewardObjData, $rewards_snapfitness_contents)
    {
        $rewardObjData['title'] = $mixedreward_content['title'];
        $rewardObjData['contents'] = $rewards_snapfitness_contents;
        $rewardObjData['image'] = $mixedreward_content['images'][0];
        $rewardObjData['gallery'] = $mixedreward_content['images'];
        $rewardObjData['new_amount'] = $mixedreward_content['total_amount'];
        $rewardObjData['payload']['amount'] = $mixedreward_content['total_amount'];
        $rewardObjData['description'] = $mixedreward_content['rewards_header'] . ': <br>- ' . implode('<br>- ', $rewards_snapfitness_contents);
        return array($rewardObjData);
    }

    /**
     * @param $mixedreward_content
     * @param $duration_day
     * @param $rewardObjData
     * @return array
     * @throws Exception
     */
    public function rewardObjDescByDuration($mixedreward_content, $duration_day, $rewardObjData)
    {
        if (!empty($mixedreward_content['footer']) && !in_array(Request::header('Device-Type'), ['android', 'ios'])) {
            if ($duration_day == 360) {
                $rewardObjData['description'] = $rewardObjData['description'] . bladeCompile($mixedreward_content['footer'], ['duration' => '1']);
            } else {
                $rewardObjData['description'] = $rewardObjData['description'] . bladeCompile($mixedreward_content['footer'], ['duration' => '6']);
            }
        }
        return array($rewardObjData);
    }

    /**
     * @param $rewardObjData
     */
    public function unsetRewardObjFields(&$rewardObjData)
    {
        unset($rewardObjData['rewrardoffers']);
        unset($rewardObjData['updated_at']);
        unset($rewardObjData['created_at']);
    }

    /**
     * @return mixed
     */
    public function getMixedReward()
    {
        return Reward::where('quantity_type', 'mixed')->first();
    }


}