<?PHP namespace App\Services;
use Myreward;
use Reward;
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
use Findercommercial;
use Config;
use JWT;
use Finder;


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
        return;
    }

    public function giveCashbackOrRewardsOnOrderSuccess($order){

        $utilities          =   new Utilities;
        $valid_ticket_ids   =   [99,100];

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

                $req = array(
                    "customer_id"=>$order['customer_id'],
                    "order_id"=>$order['_id'],
                    "amount"=>$cashback_amount,
                    "type"=>'CASHBACK',
                    "description"=>'CASHBACK ON PURCHASE - '.$cashback_amount
                );

                $utilities->walletTransaction($req);
                $order->update(array('cashback_amount'=>$cashback_amount));

            }elseif(isset($order['reward_ids']) && !empty($order['reward_ids'])){

                $myReward = Myreward::where("order_id",(int)$order['_id'])->get();

                if(count($myReward) > 0){
                    return 'true';
                }

                $order['order_id'] = $order['_id'];
                $this->createMyReward($order);

            }elseif(isset($order['type']) && $order['type'] == 'events' && isset($order['customer_id']) && isset($order['amount']) && isset($order['ticket_id']) && in_array(intval($order['ticket_id']), $valid_ticket_ids )){

                $amounttobeadded        =       intval($order['amount']);
                $customer_id            =       intval($order['customer_id']);

                $customerwallet 		= 		\Customerwallet::where('customer_id',$customer_id)->orderBy('_id', 'desc')->first();

                if($customerwallet){
                    echo "asd".$customerwallet['balance'];
                    $customer_balance 	=	$customerwallet['balance'] + $amounttobeadded;
                }else{
                    $customer_balance 	=	 $amounttobeadded;
                }

//                var_dump($customer_balance);exit();

                $cashback_amount 	=	$amounttobeadded;

                $walletData = array(
                    "customer_id"=> $customer_id,
                    "amount"=> $cashback_amount,
                    "type"=>'CASHBACK',
                    "balance"=>	$customer_balance,
                    "description"=>'CASHBACK ON Events Tickets amount - '.$cashback_amount
                );

                // return $walletData;

                $wallet               	=   new \CustomerWallet($walletData);
                $last_insertion_id      =   \CustomerWallet::max('_id');
                $last_insertion_id      =   isset($last_insertion_id) ? $last_insertion_id :0;
                $wallet->_id          	=   ++ $last_insertion_id;
                $wallet->save();

                $customer_update 	=	\Customer::where('_id', $customer_id)->update(['balance' => intval($customer_balance)]);



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

            $created_at = date('Y-m-d h:i:s',strtotime($myreward->created_at));

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

                return array('status' => 200,'message' => "My Reward Captured Successfull");

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

    public function purchaseGame($amount,$finder_id,$payment_mode = "paymentgateway"){

        $wallet = 0;

        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token : '.$jwt_token);
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;

            $customer_wallet = Customerwallet::where('customer_id',(int) $customer_id)->orderBy('_id','desc')->first();

            if($customer_wallet && isset($customer_wallet->balance) && $customer_wallet->balance != ''){
                $wallet = $customer_wallet->balance;
            }

        }

        $wallet_percentage = 27 ;

        $findercommercial = Findercommercial::where('finder_id',$finder_id)->orderBy('_id','desc')->first();

        $commision = 10;
        if($findercommercial){

            $commision = (float) preg_replace("/[^0-9.]/","",$findercommercial->commision);
        }

        $algo = array(
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
        }

        $original_amount = $amount;

        $wallet_amount = round($amount * $setAlgo['fitcash'] / 100);

        $amount_discounted = round($amount * $setAlgo['discount'] / 100);

        $wallet_algo = round(($amount * $commision / 100) * ($wallet_percentage / 100));

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

        return $data;

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


}
