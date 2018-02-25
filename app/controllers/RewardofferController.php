    <?php

use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;


class RewardofferController extends BaseController {

    protected $utilities;

    public function __construct(
        Utilities $utilities
    ) {
        $this->utilities = $utilities;
        
        $this->vendor_token = false;
        
        $vendor_token = Request::header('Authorization-Vendor');

        if($vendor_token){

            $this->vendor_token = true;
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
                'info'          =>  ""//"You can only pay upto 10% of the booking amount through FitCash. \nIt is calculated basis the amount, type and duration of the purchase.  \nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance_only_fitcash']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet']
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

            $rewardoffer           =   Rewardoffer::active()->where('findercategory_id', $findercategory_id)
                    ->where('amount_min','<=', $amount)
                    ->where('amount_max','>', $amount)
                    // ->whereNotIn('reward_type',['personal_trainer_at_home'])
                    ->with(array('rewards'=> function($query){$query->select('*')->whereNotIn('reward_type',['healthy_snacks', 'personal_trainer_at_home']);}  ))
                    // ->with('rewards')
                    ->orderBy('_id','desc')->first();

                                        // return $rewardoffer;
            if ($rewardoffer){
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
                        'diet_plan',
                        'sessions',
                        'healthy_snacks',
                        'personal_trainer_at_home',
                        'healthy_tiffin',
                        'nutrition_store',
                        'fitternity_voucher'
                    );


                    if(in_array($finder_id, Config::get('app.diet_reward_excluded_vendors'))){
                        $reward_type_order = array(
                            'fitness_kit',
                            'sessions',
                            'healthy_snacks',
                            'personal_trainer_at_home',
                            'healthy_tiffin',
                            'nutrition_store',
                            'fitternity_voucher'
                        );
                    }

                    if(isset($ratecard) && ($ratecard["type"] == "trial" || $ratecard["type"] == "workout session")){
                        $rewards = [];
                    }
                    foreach ($reward_type_order as $reward_type_order_value){
                        // if($amount < 2000){
                        //     $rewards = [];        
                        // }
                        foreach ($rewards as $rewards_value){

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

                                    if(!$reward_data_flag){

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

                                    if(in_array($finder_id,[13219,13221]) && $amount <= 1000){

                                        $pos = strpos($rewards_value['title'],'(Kit B)');

                                        if($pos === false){

                                            $reward_type_info = 'fitness_kit';

                                            $reward_data['contents'] = ['Cool-Water Bottle'];
                                            $reward_data['payload_amount'] = 300;
                                            $reward_data['image'] = 'https://b.fitn.in/gamification/reward/goodies/productskit/bottle.png';
                                            $reward_data['gallery'] = [];

                                        }else{

                                            $reward_type_info = 'fitness_kit_2';

                                            $reward_data['contents'] = ['Waterproof Gym Bag'];
                                            $reward_data['payload_amount'] = 850;
                                            $reward_data['image'] = 'https://b.fitn.in/gamification/reward/goodies/productskit/gymbag.png';
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

        }

        $cashback = null;
        
        $customerReward     =   new CustomerReward();

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
                'description'=>$calculation['description']
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

            $cashback['description'] = "Get Rs ".$calculation['wallet_amount'].". Fitcash+ in your wallet as cashback which is fully redeemable against any Memberships/Session & Diet Plan purchase on Fitternity. Earned cashback is valid for ".$duration_month." months. Cashback chosen as reward can be availed for renewal";
        }

        $renewal_cashback  = array('title'=>'Discount on Renewal');
        $selection_limit = 1;

        if($this->vendor_token && isset($cashback['commision']) && !$cashback['commision']){

            $cashback = null;
        }

        $data = array(
            'renewal_cashback'          =>   $renewal_cashback,
            'cashback'                  =>   $cashback,
            'rewards'                   =>   $rewards,
            'selection_limit'           =>   $selection_limit,
            'status'                    =>  200,
            'message'                   =>  "Rewards offers",
            'amount'                    =>  $amount
        );
        // $data['cross_sell'] = array(
        //     'diet_plan' => $customerReward->fitternityDietVendor($amount)
        // );
        $data['diet_plan'] = $customerReward->fitternityDietVendor($amount);

        $data['text'] = "Buying $service_name at $finder_name for $service_duration makes you eligible for a COMPLIMENTARY reward from Fitternity! Scroll options below & select";

        if(isset($part_payment)){

            $data['amount'] = ($order->customer_amount)/5;

        }

        if($this->utilities->isConvinienceFeeApplicable($ratecard)){

            $convinience_fee_percent = Config::get('app.convinience_fee');

            $convinience_fee = number_format($amount*$convinience_fee_percent/100, 0);

            $convinience_fee = $convinience_fee <= 150 ? $convinience_fee : 150;

            $data['convinience_fee'] = $convinience_fee;
        }

        if($this->utilities->checkCorporateLogin()){
            $data['corporate_login'] = true;
            unset($data['cashback']);
        }

        return  Response::json($data, 200);

    }
    
    
}