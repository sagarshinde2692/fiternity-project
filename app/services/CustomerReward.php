<?PHP namespace App\Services;
use Myreward;
use Reward;
use App\Services\Utilities;
use Validator;
use Response;
use Log;

Class CustomerReward {

    protected $utilities;

    public function __construct(Utilities $utilities) {
        $this->utilities = $utilities;
    }

    public function createMyReward($data){

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
            return Response::json(array(
                'status' => 404,
                'message' =>$this->utilities->errorMessage($validator1->errors())),404
            );
        }


        $customer_id = $this->utilities->autoRegisterCustomer($data);
        $rewards = Reward::findMany($data['reward_ids']);

        if(count($rewards) == 0){
            return Response::json(array("status" => 422,"message" => "Unprocessible Entity"),422);
        }


        foreach ($rewards as $reward){

            $reward = array_except($reward->toArray(), array(
                'created_at','updated_at','status','rewrardoffers','_id'
            ));
            $reward['customer_id'] = $customer_id;
            $reward['customer_name'] = $data['customer_name'];
            $reward['customer_email'] = $data['customer_email'];
            $reward['customer_phone'] = $data['customer_phone'];
            isset($data['booktrial_id']) ? $reward['booktrial_id'] = (int) $data['booktrial_id'] : null;
            isset($data['order_id']) ? $reward['order_id'] = (int) $data['order_id'] : null;

            $this->saveToMyRewards($reward);
        }
    }



    public function saveToMyRewards($reward){
        $reward['status']         = "0";
        $myreward 			    = 	new Myreward($reward);
        $last_insertion_id 	    =	Myreward::max('_id');
        $last_insertion_id      = isset($last_insertion_id) ? $last_insertion_id :0;
        $myreward->_id 		    = 	++ $last_insertion_id;
        $myreward->save();
        return;
    }

    public function giveCashbackOrRewardsOnOrderSuccess($order){

        try{
            // For Cashback.....
            if(isset($order['cashback']) && !empty($order['cashback'])){

                $cashback = $order['cashback'];
                $cashback_amount = 0;

                if(isset($cashback['percentage']) && isset($cashback['cap'])){
                    $discounted_amount = ($cashback['percentage']*$order['amount'])/100;
                    $cashback_amount = (int) ( ($discounted_amount > $cashback['cap']) ? $cashback['cap'] : $discounted_amount);

                }
                else if(isset($cashback['flat'])){
                    $cashback_amount = $cashback['flat'];
                }

                if($cashback_amount > 0){
                    $req = array(
                        "customer_id"=>$order['customer_id'],
                        "order_id"=>$order['_id'],
                        "amount"=>$cashback_amount,
                        "type"=>'CASHBACK',
                        "description"=>'CASHBACK ON PURCHASE - '.$cashback_amount
                    );
                    $this->utilities->walletTransaction($req);
                    $order->update(array('cashback_amount'=>$cashback_amount));
                }
            }

            // For Rewards.....
            if(isset($order['reward_ids']) && !empty($order['reward_ids'])){
                $order['order_id'] = $order['_id'];
                $this->createMyReward($order);
            }
        }
        catch (Exception $e) {
            Log::info('Error : '.$e->getMessage());
        }
    }
}