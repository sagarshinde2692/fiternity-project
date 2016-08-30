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

        $ratecard = Ratecard::where('_id',$ratecard_id)/*->where('price',$amount)*/->where('finder_id',$finder_id)->first();

        if(!$ratecard){
            $resp   =   array('status' => 401,'message' => "Ratecard Price and Amount does not Match");
            return  Response::json($resp, 401);
        }

        if(isset($ratecard->special_price) && $ratecard->special_price > 0 && $ratecard->special_price != ""){
            $amount = $ratecard->special_price;
        }else{
            $amount = $ratecard->price;
        }

        $finder = Finder::find($finder_id);

        $findercategory_id      =   intval($finder->category_id);

        $rewards = array();

        $rewardoffer           =   Rewardoffer::where('findercategory_id', $findercategory_id)
            ->where('amount_min','<', $amount)
            ->where('amount_max','>=', $amount)
            ->with('rewards')
            ->orderBy('_id','desc')->first();

        if ($rewardoffer){
            $rewardoffer = $rewardoffer->toArray();
            $rewards = isset($rewardoffer['rewards']) ? $rewardoffer['rewards'] : array();
        }

        $customerReward = new CustomerReward();

        $calculation = $customerReward->purchaseGame($amount,$finder_id);
        
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