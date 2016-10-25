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

            $resp 	= 	array('status' => 200, 'data' => $data);
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

        $data = Input::json()->all();

        $rules = array(
            'finder_id'=>'required',
            'amount'=>'required',
            'ratecard_id'=>'required'
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 401,'message' => $this->utilities->errorMessage($validator->errors())),401);
        }

        $finder_id = (int)$data['finder_id'];
        $amount = (int)$data['amount'];
        $ratecard_id = (int)$data['ratecard_id'];

        $ratecard = Ratecard::where('_id',$ratecard_id)->where('finder_id',$finder_id)->first();

        if(!$ratecard){
            $resp   =   array('status' => 401,'message' => "Ratecard Not Present");
            return  Response::json($resp, 401);
        }

        if(isset($ratecard->special_price) && $ratecard->special_price > 0 && $ratecard->special_price != ""){
            $amount = $ratecard->special_price;
        }else{
            $amount = $ratecard->price;
        }

        if(isset($data['order_id']) && $data['order_id'] != ""){

            $order_id = (int) $data['order_id'];

            $order = Order::find($order_id);

            if(isset($order->payment_mode) && $order->payment_mode == "at the studio"){
                $amount = (int)$data['amount'];
            }
        }

        $finder = Finder::find($finder_id);

        $findercategory_id      =   intval($finder->category_id);

        $rewards = array();

        if(isset($finder->purchase_gamification_disable) && $finder->purchase_gamification_disable == "1"){
            $rewards = array();
        }else{
            $rewardoffer           =   Rewardoffer::where('findercategory_id', $findercategory_id)
            ->where('amount_min','<', $amount)
            ->where('amount_max','>=', $amount)
            ->with('rewards')
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
                        'healthy_tiffin',
                        'nutrition_store',
                        'fitternity_voucher'
                    );

                    foreach ($reward_type_order as $reward_type_order_value){

                        foreach ($rewards as $rewards_value){
                            if($rewards_value['reward_type'] == "fitness_kit" || $rewards_value['reward_type'] == "healthy_snacks"){
                                switch(true){
                                    case $amount < 3000 :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 1000 : 650;
                                        $rewards_value['contents'] = ["Shaker", "Earphone Detangler"];
                                        break;
                                    case (3000 < $amount && $amount < 5000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 1500 : 1150;
                                        $rewards_value['contents'] = ["Shaker", "Earphone Detangler"];
                                        break;
                                    case (5000 < $amount && $amount < 7500) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 1500 : 1550;
                                        $rewards_value['contents'] = ["Shaker", "Earphone Detangler"];
                                        break;
                                    case (7500 < $amount && $amount < 10000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 3000 : 2150;
                                        $rewards_value['contents'] = ["T-shirt", "Gym bag", "Shaker", "Earphone Detangler", "Mug", "Skipping Details"];
                                        break;
                                    case (10000 < $amount && $amount < 15000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 4000 : 3150;
                                        $rewards_value['contents'] = ["T-shirt", "Gym bag", "Shaker", "Earphone Detangler", "Mug", "Resistance Band", "Coaster", "Notebook"];
                                        break;
                                    case (15000 < $amount && $amount < 20000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 5000 : 4050;
                                        $rewards_value['contents'] = ["T-shirt", "Gym bag", "Shaker", "Earphone Detangler", "Mug", "Resistance Band", "Coaster", "Notebook"];
                                        break;
                                    case (20000 < $amount && $amount < 25000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 6000 : 4550;
                                        $rewards_value['contents'] = ["T-shirt", "Shaker", "Gym bag", "Earphone Detangler", "Resistance Band", "Badge", "Mug", "Notebook", "Coaster", "Skipping Rope", "Alarm Clock"];
                                        break;
                                    case ($amount > 35000) :
                                        $rewards_value['payload']['amount'] = $rewards_value['reward_type'] == "fitness_kit" ? 8000 : 5150;
                                        $rewards_value['contents'] = ["T-shirt", "Shaker", "Gym bag", "Earphone Detangler", "Resistance Band", "Badge", "Mug", "Notebook", "Coaster", "Skipping Rope", "Alarm Clock"];
                                        break;
                                }
                            }

                            if($rewards_value['reward_type'] == $reward_type_order_value){

                                $reward_ordered[] = $rewards_value;

                                break;
                            }
                        }
                    }

                    $rewards = $reward_ordered;

                }
            }
        }

        $customerReward = new CustomerReward();

        $calculation = $customerReward->purchaseGame($amount,$finder_id);

        if(isset($data['order_id']) && $data['order_id'] != ""){

            $order_id = (int) $data['order_id'];

            $order = Order::find($order_id);

            if(isset($order->payment_mode) && $order->payment_mode == "at the studio"){
                $calculation = $customerReward->purchaseGame($amount,$finder_id,"at the studio");
            }

        }

        $cashback  = array(
            'title'=>$calculation['algo']['cashback'].'% Discount on Purchase',
            'percentage'=>$calculation['algo']['cashback'].'%',
            'commision'=>$calculation['algo']['cashback'],
            'calculation'=>$calculation
        );

        $renewal_cashback  = array('title'=>'Discount of 15% on Renewal');
        $selection_limit = 1;

        $data = array(
            'renewal_cashback'          =>   $renewal_cashback,
            'cashback'                  =>   $cashback,
            'rewards'                   =>   $rewards,
            'selection_limit'           =>   $selection_limit,
            'status' => 200,
            'message' => "Rewards offers"
        );

        return  Response::json($data, 200);

    }
	
}																																																																																																																																																																																																																																																																										