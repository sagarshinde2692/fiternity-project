<?php

use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;


class RewardofferController extends BaseController {

    protected $utilities;

    public function __construct(
        Utilities $utilities
    ) {
        $this->utilities = $utilities;
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
                return Response::json(array('status' => 401,'message' => $this->utilities->errorMessage($validator->errors())),401);
            }
            $device             =   isset($data["device_type"]) ? $data["device_type"] : "";
            $finder_id          =   (int)$data['finder_id'];
            $amount             =   (int)$data['amount'];
            $customerReward     =   new CustomerReward();
            $calculation        =   $customerReward->purchaseGame($amount,$finder_id);

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
                'info'          =>  "You can only pay upto 10% of the booking amount through FitCash. \nIt is calculated basis the amount, type and duration of the purchase.  \nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet']
            ];

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


        $rules      =   ['finder_id'=>'required', 'amount'=>'required', 'ratecard_id'=>'required'];
        $device     =   isset($data["device_type"]) ? $data["device_type"] : "";
        $validator  =   Validator::make($data,$rules);
        if ($validator->fails()) {
            return Response::json(array('status' => 401,'message' => $this->utilities->errorMessage($validator->errors())),401);
        }

        $finder_id      =   (int)$data['finder_id'];
        $amount         =   (int)$data['amount'];
        $ratecard_id    =   (int)$data['ratecard_id'];
        $ratecard       =   Ratecard::where('_id',$ratecard_id)->where('finder_id',$finder_id)->first();

        if(isset($data['order_id']) && $data['order_id'] != ""){
            $order_id   = (int) $data['order_id'];
            $order      = Order::find($order_id);
            if(isset($order->payment_mode) && $order->payment_mode == "at the studio"){
                $amount = (int)$data['amount'];
            }
        }
        if(!$ratecard && count($order) == 0){
            $resp   =   array('status' => 401,'message' => "Ratecard Not Present");
            return  Response::json($resp, 401);
        }

        /*if(isset($ratecard->special_price) && $ratecard->special_price > 0 && $ratecard->special_price != ""){
            $amount = $ratecard->special_price;
        }else{
            $amount = $ratecard->price;
        }*/


        $finder                 =   Finder::find($finder_id);
        $findercategory_id      =   intval($finder->category_id);
        $rewards                =   [];

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

            $rewardoffer           =   Rewardoffer::active()->where('findercategory_id', $findercategory_id)
                    ->where('amount_min','<', $amount)
                    ->where('amount_max','>=', $amount)
                    // ->whereNotIn('reward_type',['personal_trainer_at_home'])
                    ->with(array('rewards'=> function($query){$query->select('*')->where('reward_type','!=','personal_trainer_at_home');}  ))
                    // ->with('rewards')
                    ->orderBy('_id','desc')->first();

            if ($rewardoffer){
                $rewardoffer = $rewardoffer->toArray();

                $rewards = isset($rewardoffer['rewards']) ? $rewardoffer['rewards'] : array();

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
                        'sessions',
                        'healthy_snacks',
                        'personal_trainer_at_home',
                        'diet_plan',
                        'healthy_tiffin',
                        'nutrition_store',
                        'fitternity_voucher'
                    );

                    foreach ($reward_type_order as $reward_type_order_value){
                        if($amount < 2000){
                            $rewards = [];        
                        }
                        foreach ($rewards as $rewards_value){
                            if($rewards_value['reward_type'] == "fitness_kit" || $rewards_value['reward_type'] == "healthy_snacks"){
                                switch(true){
                                    case $amount < 2000 :
                                        // $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 750 : 650;
                                        // $rewards_value['contents'] = $rewards_value['reward_type'] == "fitness_kit" ?  ["Shaker", "Badge"] : ["Pop Mak – Roasted Flavoured Makhana 50gm", "2 Honey Chew Pouch (5 flavours) 20gm", "3 Vegan Protein Bar 1 piece", "4 Stroopwaffle (Caramel Wafer Biscuits/ Cookies) 1 piece", "5 Baked Pizza Stick Dippers 75gm", "6 Roasted Mexican Chickpea 100gm" ];
                                        // $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - Badge" : $rewards_value['description'];
                                        // $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-1-20-12-2016.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                    case (2000 <= $amount && $amount < 5000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 600 : 300;
                                        $rewards_value['contents'] =  $rewards_value['reward_type'] == "fitness_kit" ? ["Shaker", "Cloth Bag"] : ["Pop Mak – Roasted Flavoured Makhana (small)", "2 Honey Chew Pouch (5 flavours)","Good Juicery (Sugar Free Sparkling) Juice"];
                                        $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - Cloth Bag" : "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains: <br> - Pop Mak – Roasted Flavoured Makhana (small) <br> - 2 Honey Chew Pouch (5 flavours) <br> - Good Juicery (Sugar Free Sparkling) Juice";
                                        $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-2-24-01-2017.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                    case (5000 <= $amount && $amount < 7500) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 1100: 510;
                                        $rewards_value['contents'] =  $rewards_value['reward_type'] == "fitness_kit" ?  ["Shaker", "T-shirt"] : ["Pop Mak – Roasted Flavoured Makhana (small)", "2 Honey Chew Pouch (5 flavours)","Good Juicery (Sugar Free Sparkling) Juice","Baked Pizza Stick Dippers"];
                                        $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - T-shirt" : "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains: <br> - Pop Mak – Roasted Flavoured Makhana (small) <br> - 2 Honey Chew Pouch (5 flavours) <br> - Good Juicery (Sugar Free Sparkling) Juice <br> - Baked Pizza Stick Dippers";
                                        $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-3-24-01-2017.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                    case (7500 <= $amount && $amount < 10000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 1200 : 700;
                                        $rewards_value['contents'] =  $rewards_value['reward_type'] == "fitness_kit" ?  ["T-shirt", "Shaker", "Cloth Bag"] : ["2 Pop Mak – Roasted Flavoured Makhana (small)", "Honey Chew Pouch (5 flavours)","2 Good Juicery (Sugar Free Sparkling) Juice","Baked Pizza Stick Dippers"];
                                        $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - Cloth Bag <br> - T-shirt" : "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains: <br> - 2 Pop Mak – Roasted Flavoured Makhana (small) <br> - Honey Chew Pouch (5 flavours) <br> - 2 Good Juicery (Sugar Free Sparkling) Juice <br> - Baked Pizza Stick Dippers";
                                        $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-4-24-01-2017.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                    case (10000 <= $amount && $amount < 15000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 1050 : 1600;
                                        $rewards_value['contents'] =  $rewards_value['reward_type'] == "fitness_kit" ? ["T-shirt", "Shaker", "Earphone Detangler", "Notebook"] : ["Pop Mak – Roasted Flavoured Makhana (small)", "Honey Chew Pouch (5 flavours)","Baked Pizza Stick Dippers","Pop Mak – Roasted Flavoured Makhana (big)","Colonel & Co. Nachos with Dip", "Roasted Mexican Chickpeas"];
                                        $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - T-shirt <br> - Earphone Detangler <br> - Notebook" : "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains: <br> - Pop Mak – Roasted Flavoured Makhana (small) <br> - Honey Chew Pouch (5 flavours) <br> - Baked Pizza Stick Dippers <br> - Pop Mak – Roasted Flavoured Makhana (big) <br> - Colonel & Co. Nachos with Dip <br> - Roasted Mexican Chickpeas";
                                        $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-5-24-01-2017.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                    case (15000 <= $amount && $amount < 20000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 2500 : 1600;
                                        $rewards_value['contents'] = $rewards_value['reward_type'] == "fitness_kit" ? ["T-shirt", "Gym bag", "Shaker", "Earphone Detangler", "Notebook"] : ["Pop Mak – Roasted Flavoured Makhana (small)", "2 Honey Chew Pouch (5 flavours)","Good Juicery (Sugar Free Sparkling) Juice","Baked Pizza Stick Dippers","Colonel & Co. Nachos with Dip", "3 Roasted Mexican Chickpeas","Wholewheat Thins"];
                                        $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - Gym Bag <br> - T-shirt <br> - Earphone Detangler" : "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains: <br> - Pop Mak – Roasted Flavoured Makhana (small) <br> - 2 Honey Chew Pouch (5 flavours) <br> - Good Juicery (Sugar Free Sparkling) Juice <br> - Baked Pizza Stick Dippers <br> - Colonel & Co. Nachos with Dip <br> - 3 Roasted Mexican Chickpeas <br> - Wholewheat Thins";
                                        $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-6-24-01-2017.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                    case (20000 <= $amount && $amount < 25000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 3000 : 2020;
                                        $rewards_value['contents'] = $rewards_value['reward_type'] == "fitness_kit" ? ["T-shirt", "Gym bag", "Shaker", "Earphone Detangler", "Notebook","Mug","Cloth Bag"] : ["Pop Mak – Roasted Flavoured Makhana (small)", "2 Honey Chew Pouch (5 flavours)","Good Juicery (Sugar Free Sparkling) Juice","2 Pop Mak – Roasted Flavoured Makhana (big)", "Baked Pizza Stick Dippers","Colonel & Co. Nachos with Dip", "2 Roasted Mexican Chickpeas","Wholewheat Thins"];
                                        $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - Gym Bag <br> - T-shirt <br> - Earphone Detangler <br> - Mug <br> - Notebook <br> - Cloth Bag" : "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains: <br> - Pop Mak – Roasted Flavoured Makhana (small)<br> - 2 Honey Chew Pouch (5 flavours) <br> - Good Juicery (Sugar Free Sparkling) Juice<br> - 2 Pop Mak – Roasted Flavoured Makhana (big)<br> - Baked Pizza Stick Dippers<br> - Colonel & Co. Nachos with Dip<br> - 2 Roasted Mexican Chickpeas<br> - Wholewheat Thins";
                                        $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-7-24-01-2017.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                    case (25000 <= $amount && $amount < 30000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 3500 : 2200;
                                        $rewards_value['contents'] = $rewards_value['reward_type'] == "fitness_kit" ? ["T-shirt", "Gym bag", "Shaker", "Earphone Detangler", "Notebook","Mug","Cloth Bag", "Coaster", "Skipping Rope"] : ["Pop Mak – Roasted Flavoured Makhana ", "2 Honey Chew Pouch (5 flavours)","2 Good Juicery (Sugar Free Sparkling) Juice", "2 Baked Pizza Stick Dippers","Colonel & Co. Nachos with Dip", "Banana & Chia Granola Crunchers","Wholewheat Thins"];
                                        $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - Gym Bag <br> - T-shirt <br> - Earphone Detangler <br> - Mug <br> - Notebook <br> - Cloth Bag <br> - Coaster <br> - Skipping Rope " : "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains: <br> - Pop Mak – Roasted Flavoured Makhana <br> - 2 Honey Chew Pouch (5 flavours)<br> - 2 Good Juicery (Sugar Free Sparkling) Juice <br> - 2 Baked Pizza Stick Dippers <br> - Colonel & Co. Nachos with Dip <br> - Banana & Chia Granola Crunchers <br> - Wholewheat Thins";
                                        $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-8-24-01-2017.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                    case (30000 <= $amount) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 4000 : 2600;
                                        $rewards_value['contents'] = $rewards_value['reward_type'] == "fitness_kit" ? ["T-shirt", "Gym bag", "Shaker", "Earphone Detangler", "Notebook","Mug","Cloth Bag", "Coaster", "Skipping Rope", "Resistance Bands"] : ["Pop Mak – Roasted Flavoured Makhana ", "2 Honey Chew Pouch (5 flavours)","2 Good Juicery (Sugar Free Sparkling) Juice", "2 Baked Pizza Stick Dippers","Colonel & Co. Nachos with Dip", "Banana & Chia Granola Crunchers","Wholewheat Thins","2 Pop Mak – Roasted Flavoured Makhana"];
                                        $rewards_value['description'] = $rewards_value['reward_type'] == "fitness_kit" ? "Start your membership with the right products and gear. Get a super-cool fitness kit which contains the following:<br> - Shaker <br> - Gym Bag <br> - T-shirt <br> - Earphone Detangler <br> - Mug <br> - Notebook <br> - Cloth Bag <br> - Coaster <br> - Skipping Rope <br> - Resistance Bands" : "Ensure you avoid those extra calories by munching on tasty snacks. Get a specially curated hamper which contains: <br> - Pop Mak – Roasted Flavoured Makhana<br> - 2 Honey Chew Pouch (5 flavours)<br> -2 Good Juicery (Sugar Free Sparkling) Juice<br> - 2 Baked Pizza Stick Dippers<br> -Colonel & Co. Nachos with Dip<br> - Banana & Chia Granola Crunchers<br> -Wholewheat Thins<br> -2 Pop Mak – Roasted Flavoured Makhana";
                                        $rewards_value['image'] =  $rewards_value['reward_type'] == "fitness_kit" ? "https://b.fitn.in/gamification/reward/goodies/kit-9-24-01-2017.jpg" : "https://b.fitn.in/gamification/reward/goodies/hamper-2.jpg";
                                        break;
                                }
                                
                            }else{

                                $rewards_value['image'] = "https://b.fitn.in/gamification/reward/".$rewards_value['reward_type'].".jpg";
                            }

                            if($rewards_value['reward_type'] == $reward_type_order_value){

                                $reward_type_array = ["fitness_kit","healthy_snacks"];

                                if(isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && in_array($rewards_value['reward_type'], $reward_type_array)){
                                   
                                    $rewards_value['contents'] = str_replace("<br>", "\n", $rewards_value['contents']);
                                    $rewards_value['description'] = str_replace("<br>", "\n", $rewards_value['description']);
                                }

                                $reward_ordered[] = $rewards_value;

                                break;
                            }
                        }
                    }

                    $rewards = $reward_ordered;

                }
            }
        }

        $customerReward     =   new CustomerReward();
        $calculation        =   $customerReward->purchaseGame($amount,$finder_id);

        if(isset($data['order_id']) && $data['order_id'] != ""){

            $order_id = (int) $data['order_id'];

            $order = Order::find($order_id);

            if(isset($order->payment_mode) && $order->payment_mode == "at the studio"){
                $calculation = $customerReward->purchaseGame($amount,$finder_id,"at the studio");
            }

        }

        $cashback  = array(
            // 'title'=>$calculation['algo']['cashback'].'% Discount on Purchase',
            'title'=>$calculation['algo']['cashback'].'% Instant Cashback on Purchase',
            'percentage'=>$calculation['algo']['cashback'].'%',
            'commision'=>$calculation['algo']['cashback'],
            'calculation'=>$calculation,
            'info'          =>  "You can only pay upto 10% of the booking amount through FitCash. \nIt is calculated basis the amount, type and duration of the purchase.  \nYour total FitCash balance is Rs. ".$calculation['current_wallet_balance']." FitCash applicable for this transaction is Rs. ".$calculation['amount_deducted_from_wallet'],
            'description'=>$calculation['description']
        );

        unset($cashback['calculation']['description']);

        $renewal_cashback  = array('title'=>'Discount on Renewal');
        $selection_limit = 1;
        $data = array(
            'renewal_cashback'          =>   $renewal_cashback,
            'cashback'                  =>   $cashback,
            'rewards'                   =>   $rewards,
            'selection_limit'           =>   $selection_limit,
            'status'                    =>  200,
            'message'                   => "Rewards offers"
        );

        return  Response::json($data, 200);

    }
    
    
}