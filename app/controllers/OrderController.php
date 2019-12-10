<?PHP

/**
 * ControllerName : OrderController.
 * Maintains a list of functions used for OrderController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\FinderSms as FinderSms;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;
use App\Services\CustomerInfo as CustomerInfo;
use App\Services\ShortenUrl as ShortenUrl;
use App\Notification\CustomerNotification as CustomerNotification;


class OrderController extends \BaseController {

    protected $customermailer;
    protected $customersms;
    protected $sidekiq;
    protected $findermailer;
    protected $findersms;
    protected $utilities;
    protected $customerreward;
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
        $this->customermailer		=	$customermailer;
        $this->customersms 			=	$customersms;
        $this->sidekiq 				= 	$sidekiq;
        $this->findermailer		    =	$findermailer;
        $this->findersms 			=	$findersms;
        $this->utilities 			=	$utilities;
        $this->customerreward 		=	$customerreward;
        $this->customernotification     =   $customernotification;
        $this->ordertypes 		= 	array('memberships','booktrials','fitmaniadealsofday','fitmaniaservice','arsenalmembership','zumbathon','booiaka','zumbaclub','fitmania-dod','fitmania-dow','fitmania-membership-giveaways','womens-day','eefashrof','crossfit-week','workout-session','wonderise','lyfe','healthytiffintrail','healthytiffinmembership','3daystrial','vip_booktrials', 'events','fittinabox');

    }


    public function couponCodeUsedForHealthyTiffinByPhoneno($customer_phone){

        $usedCnt = Order::where('couponcode', 'exists', true)
            ->where('couponcode','yummyfit')
            ->where('type','healthytiffintrail')
            ->where('customer_phone',trim($customer_phone))
            ->active()
            ->count();

        $usedCouponStatus 	= ($usedCnt > 0) ? true : false;
        $resp 				= [	'used' => $usedCouponStatus];
        return Response::json($resp,200);
    }

    public function couponCode(){
        $data = Input::json()->all();
        if(!isset($data['coupon'])){
            $resp = array("status"=> 400, "message" => "Coupon code missing", "error_message" => "Please enter a valid coupon");
            return Response::json($resp,400);
        }
        // if(!isset($data['amount'])){
        //     $resp = array("status"=> 400, "message" => "Amount field is missing", "error_message" => "Coupon cannot be applied on this transaction");
        //     return Response::json($resp,400);
        // }
        if(!isset($data['ratecard_id']) && !isset($data['ticket_id'])){
            $resp = array("status"=> 400, "message" => "Ratecard Id or ticket Id must be present", "error_message" => "Coupon cannot be applied on this transaction");
            return Response::json($resp,400);
        }

        $jwt_token = Request::header('Authorization');
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }
        $service_id = isset($data['service_id']) ? $data['service_id']: null;

        $couponCode = $data['coupon'];

        $ticket = null;
        
        $ticket_quantity = isset($data['ticket_quantity']) ? $data['ticket_quantity'] : 1;
        
        
        if(isset($data['ticket_id'])){
            $ticket = Ticket::find($data['ticket_id']);
            if(!$ticket){
                $resp = array("status"=> 400, "message" => "Ticket not found", "error_message" => "Coupon cannot be applied on this transaction");
                return Response::json($resp,400);   
            }
        }

        $ratecard = null;

        if(isset($data['ratecard_id'])){
            $ratecard = Ratecard::find($data['ratecard_id']);
            if(!$ratecard){
                $resp = array("status"=> 400, "message" => "Ratecard not found", "error_message" => "Coupon cannot be applied on this transaction");
                return Response::json($resp,400);   
            }
        }

        if(isset($data['event_id'])){
            $customer_id = false;
        }

        $customer_id = isset($customer_id) ? $customer_id : false;
        $resp = $this->customerreward->couponCodeDiscountCheck($ratecard,$couponCode,$customer_id, $ticket, $ticket_quantity, $service_id);
        if($resp["coupon_applied"]){
            if(isset($data['event_id']) && isset($data['customer_email'])){
                                
                $already_applied_coupon = Customer::where('email',  strtolower($data['customer_email']))->whereIn('applied_promotion_codes',[strtolower($data['coupon'])])->count();
            
                if($already_applied_coupon>0){
                    return Response::json(array('status'=>400,'data'=>array('final_amount'=>($resp['data']['discount']+$resp['data']['final_amount']), "discount" => 0), 'error_message'=>'Coupon already applied', "message" => "Coupon already applied"), 400);
                }
            }
            $resp['status'] = 200;
            return Response::json($resp,200);
        }else{

            $errorMessage =  "Coupon is either not valid or expired";

            if((isset($resp['fitternity_only_coupon']) && $resp['fitternity_only_coupon']) || (isset($resp['vendor_exclusive']) && $resp['vendor_exclusive'])){
                $errorMessage =  $resp['error_message'];
            }
            $resp = array("status"=> 400, "message" => "Coupon not found", "error_message" =>$errorMessage, "data"=>$resp["data"]);
            return Response::json($resp,400);    
        }

        // if($amount > 600 && $data['coupon'] == "fitnow"){
        //     $newamount = ($amount - 500);
        //     $resp = array("status"=> "Coupon applied successfully", "amount" => $newamount,"discount_amount" => 500);
            
        // }else{
        //     $resp = array("status"=> "Coupon is either expired or not valid for this transaction", "amount" => $amount,"discount_amount" => 0);
        //     return Response::json($resp,406);
        // }
        return Response::json($resp,200);
    }


    /*public function couponCode($customer_phone){
        $data = Input::json()->all();
        if(!isset($data['coupon'])){
            $resp = array("status"=> 400, "message" => "Coupon code missing");
            return Response::json($resp,400);
        }
        if(!isset($data['amount'])){
            $resp = array("status"=> 400, "message" => "Amount field is missing");
            return Response::json($resp,400);
        }

        
        $amount = (int) $data['amount'];
        if($data['coupon'] == "fitnow"){
            $newamount = ($amount - 500);
            $resp = array("status"=> "success", "amount" => $newamount);
            
        }else{
            $resp = array("status"=> "failed", "amount" => $amount );
        }
        return Response::json($resp,200);
    }*/


    //capture order status for customer used membership by
    public function captureOrderStatus($data = null){

        ($data == null) ? $data= array_except(Input::json()->all(), array('preferred_starting_date')) : null;

        Log::info('Capture Order Status',$data);

        if(empty($data['order_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - order_id");
            return  Response::json($resp, 400);
        }

        if(empty($data['status'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - status");
            return  Response::json($resp, 400);
        }
        $orderid 	=	(int) $data['order_id'];
        $order 		= 	Order::findOrFail($orderid);



        //If Already Status Successfull Just Send Response
        if(!isset($data["order_success_flag"]) && isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){

            $resp   =   array('status' => 401, 'statustxt' => 'error', 'order' => $order, "message" => "Already Status Successfull");
            return Response::json($resp,401);

        }elseif(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && isset($order->status) && $order->status != '1' && isset($order->order_action) && $order->order_action != 'bought'){

            $resp   =   array('status' => 401, 'statustxt' => 'error', 'order' => $order, "message" => "Status should be Bought");
            return Response::json($resp,401);
        }

        if($order['type']=='events'){
            $hash_verified = true;
        }else{
            $hash_verified = $this->utilities->verifyOrder($data,$order);
        }

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

            $orderdata 	=	$order->update($data);

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

            if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
                $order->update(['email_not_sent'=>'captureOrderStatus']);
            }else{

                if(!in_array($finder->category_id, $abundant_category) && $order->type != "fittinabox" ){
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
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                        if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                            $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                        }

                    }else{
                        $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                    }
                }

                //no email to Healthy Snacks Beverages and Healthy Tiffins
                if(!in_array($finder->category_id, $abundant_category) && $order->type != "fittinabox" && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" ){
                    
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
                
                if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                    if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                        $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                    }

                }else{
                    $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                }
            }

            //no sms to Healthy Snacks Beverages and Healthy Tiffins
            if(!in_array($finder->category_id, $abundant_category) && $order->type != "fittinabox" && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" ){
                
                if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                    if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                        $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                    }
                    
                }else{
                    $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                }
                
            }

            if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && $order->type == "memberships" && !isset($order->cutomerSmsPurchaseAfter10Days) && !isset($order->cutomerSmsPurchaseAfter30Days)){

                $preferred_starting_date = $order->preferred_starting_date;
                
                $after10days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 10);
                $after30days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 30);

                $this->customersms->purchaseInstant($order->toArray());
                $order->cutomerSmsPurchaseAfter10Days = $this->customersms->purchaseAfter10Days($order->toArray(),$after10days);
                $order->cutomerSmsPurchaseAfter30Days = $this->customersms->purchaseAfter30Days($order->toArray(),$after30days);

                /*if(isset($order['gcm_reg_id']) && $order['gcm_reg_id'] != '' && isset($order['device_type']) && $order['device_type'] != ''){
                    $this->customernotification->purchaseInstant($order->toArray());
                    $order->cutomerNotificationPurchaseAfter10Days = $this->customernotification->purchaseAfter10Days($order->toArray(),$after10days);
                    $order->cutomerNotificationPurchaseAfter30Days = $this->customernotification->purchaseAfter30Days($order->toArray(),$after30days);
                }*/

                $order->update();

            }

            $this->utilities->setRedundant($order);

            $this->utilities->addAmountToReferrer($order);
            
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
            try{
                $this->utilities->addAmountToReferrer($order);
            }catch(Excepton $e){
                Log::error($e);
            }
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
    
    //create cod order for customer
    public function generateCodOrder(){


        $data			=	array_except(Input::json()->all(), array('preferred_starting_date'));
        $postdata		=	Input::json()->all();

        Log::info('Gnerate COD Order',$data);


        if(empty($data['customer_name'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_name");
            return  Response::json($resp, 400);
        }

        if(empty($data['customer_email'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
            return  Response::json($resp, 400);
        }

        if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
            $resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
            return  Response::json($resp, 400);
        }

        if(empty($data['customer_identity'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_identity");
            return  Response::json($resp, 400);
        }

        if(empty($data['customer_phone'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
            return  Response::json($resp, 400);
        }

        if(empty($data['customer_source'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_source");
            return  Response::json($resp, 400);
        }

        if(empty($data['customer_location'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_location");
            return  Response::json($resp, 400);
        }

        if(empty($data['city_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - city_id");
            return  Response::json($resp, 400);
        }

        if(empty($data['finder_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
            return  Response::json($resp, 400);
        }

        if(empty($data['finder_name'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_name");
            return  Response::json($resp, 400);
        }

        if(empty($data['finder_address'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_address");
            return  Response::json($resp, 400);
        }

        if(empty($data['service_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - service_id");
            return  Response::json($resp, 400);
        }

        if(empty($data['service_name'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
            return  Response::json($resp, 400);
        }

        if(empty($data['type'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing Order Type - type");
            return  Response::json($resp, 400);
        }

        if (!in_array($data['type'], $this->ordertypes)) {
            $resp 	= 	array('status' => 400,'message' => "Invalid Order Type");
            return  Response::json($resp, 400);
        }

        //Validation base on order type
        if($data['type'] == 'memberships'){

            if( empty($data['service_duration']) ){
                $resp 	= 	array('status' => 400,'message' => "Data Missing - service_duration");
                return  Response::json($resp, 400);
            }
        }else{

            $data['service_duration'] = (isset($data['service_duration']) && $data['service_duration'] != "") ? $data['service_duration'] : "";
        }

        $orderid 			=	Order::maxId() + 1;
        $data			=	array_except(Input::json()->all(), array('preferred_starting_date'));
        if(trim(Input::json()->get('preferred_starting_date')) != '' && trim(Input::json()->get('preferred_starting_date')) != '-'){
            $date_arr = explode('-', Input::json()->get('preferred_starting_date'));
            $preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']));
            array_set($data, 'preferred_starting_date', $preferred_starting_date);
            array_set($data, 'start_date', $preferred_starting_date);
        }
        // return $data;

        $customer_id 		=	(Input::json()->get('customer_id')) ? Input::json()->get('customer_id') : autoRegisterCustomer($data);

        if(trim(Input::json()->get('finder_id')) != '' ){

            $finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval(Input::json()->get('finder_id')))->first()->toArray();

            $finder_city						=	(isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
            $finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
            $finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
            $finder_vcc_email					= 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
            $finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
            $finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
            $finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
            $show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;
            $share_customer_no					= 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;
            $finder_lon							= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $finder_lat							= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";


            array_set($data, 'finder_city', trim($finder_city));
            array_set($data, 'finder_location', trim($finder_location));
            array_set($data, 'finder_address', trim($finder_address));
            array_set($data, 'finder_vcc_email', trim($finder_vcc_email));
            array_set($data, 'finder_vcc_mobile', trim($finder_vcc_mobile));
            array_set($data, 'finder_poc_for_customer_name', trim($finder_poc_for_customer_name));
            array_set($data, 'finder_poc_for_customer_no', trim($finder_poc_for_customer_no));
            array_set($data, 'show_location_flag', $show_location_flag);
            array_set($data, 'share_customer_no', $share_customer_no);
            array_set($data, 'finder_lon', $finder_lon);
            array_set($data, 'finder_lat', $finder_lat);

        }


        $count  = Order::where("status","1")->where('customer_email',$data['customer_email'])->where('customer_phone', substr($data['customer_phone'], 10))->orderBy('_id','asc')->where('_id','<',$orderid)->count();

        if($count > 0){
            array_set($data, 'acquisition_type', 'renewal_direct');
        }else{
            array_set($data,'acquisition_type','direct_payment');
        }

        array_set($data, 'service_name_purchase', $data['service_name']);
        array_set($data, 'service_duration_purchase', $data['service_duration']);

        array_set($data, 'customer_id', intval($customer_id));
        array_set($data, 'status', '0');
        array_set($data, 'payment_mode', 'cod');
        array_set($data, 'membership_bought_at', 'Fitternity COD Mode');

        if(isset($data['schedule_date']) && $data['schedule_date'] != ""){
            $data['membership_duration_type'] = 'workout_session';
        }

        if(isset($data['ratecard_id']) && $data['ratecard_id'] != ""){

            $ratecard = Ratecard::find($data['ratecard_id']);

            if(isset($ratecard->validity) && $ratecard->validity != ""){
                $duration_day = (int)$ratecard->validity;
                $data['duration_day'] = $duration_day;
                if(isset($postdata['preferred_starting_date']) && $postdata['preferred_starting_date']  != '') {
                    $data['end_date'] = date('Y-m-d 00:00:00', strtotime($preferred_starting_date."+ ".$duration_day." days"));
                }

                if($duration_day <= 90){
                    $data['membership_duration_type'] = ($duration_day <= 90) ? 'short_term_membership' : 'long_term_membership' ;
                }
            }
        }

        $order 				= 	new Order($data);
        $order->_id 		= 	$orderid;
        $orderstatus   		= 	$order->save();

        $device_type						= 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id							= 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

        if($device_type != '' && $gcm_reg_id != ''){

            $regData = array();

            $regData['customer_id'] = $customer_id;
            $regData['reg_id'] = $gcm_reg_id;
            $regData['type'] = $device_type;

            $this->utilities->addRegId($regData);
        }

        //SEND COD EMAIL TO CUSTOMER
        //$sndCodEmail	= 	$this->customermailer->sendCodOrderMail($order->toArray());
        //$sndCodEmail	= 	$this->findermailer->sendCodOrderMail($order->toArray());

        //SEND COD SMS TO CUSTOMER
        $sndCodSms	= 	$this->customersms->requestCodOrderSms($order->toArray());

        // print_pretty($sndCodSms); exit;

        $resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Order Successful :)");

        return Response::json($resp);

    }


    /**
     * Generate Temp Order
     *
     *	Service Duration can be (trial, workout session, months, session, etc).
     */

    public function generateTmpOrderPre(){

        $data = Input::json()->all();

        $rules = array(
            'customer_name'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required',
            'customer_source'=>'required',
            'finder_id'=>'required|integer|min:1',
            'service_id'=>'required|integer|min:1',
            'type'=>'required',
            'amount'=>'required'
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' =>$this->errorMessage($validator->errors())),404);
        }

        $workout = array('vip_booktrials','3daystrial','booktrials','workout-session');

        if(in_array($data['type'],$workout)){
            $rules = array(
                'schedule_date'=>'required',
                'schedule_slot'=>'required'
            );

            $validator = Validator::make($data,$rules);

            if ($validator->fails()) {
                return Response::json(array('status' => 404,'message' =>$this->errorMessage($validator->errors())),404);
            }
        }

        $ht_trail = array('healthytiffintrail');

        if(in_array($data['type'],$ht_trail)){
            $rules = array(
                'preferred_starting_date'=>'required',
            );

            $validator = Validator::make($data,$rules);

            if ($validator->fails()) {
                return Response::json(array('status' => 404,'message' =>$this->errorMessage($validator->errors())),404);
            }

        }

        $membership = array('healthytiffinmembership','memberships');

        if(in_array($data['type'],$membership)){
            $rules = array(
                'preferred_starting_date'=>'required',
                'ratecard_id'=>'required|integer|min:1',
            );

            $validator = Validator::make($data,$rules);

            if ($validator->fails()) {

                return Response::json(array('status' => 404,'message' =>$this->errorMessage($validator->errors())),404);
            }

        }

        $data['service_duration'] = "-";

        $data['amount'] = ($data['amount'] != 0) ? $data['amount'] :  "-";

        if(isset($data['ratecard_id']) && $data['ratecard_id'] != "" && $data['ratecard_id'] != 0){
            
            $ratecard = Ratecard::find($data['ratecard_id']);

            if(!$ratecard){
                return Response::json(array('status' => 404,'message' =>'Ratecard does not exists'),404);
            }

            $data['service_duration'] = $this->getServiceDuration($ratecard);

            if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                $data['amount_finder'] = $ratecard->special_price;
            }else{
                $data['amount_finder'] = $ratecard->price;
            }
        }

        $service = Service::find($data['service_id']);

        if(!$service){
            return Response::json(array('status' => 404,'message' =>'Service does not exists'),404);
        }

        $data['finder_address'] = (isset($service['address']) && $service['address'] != "") ? $service['address'] : "-";
        $data['service_name'] = ucwords($service['name']);
        $data['meal_contents'] = ucwords($service['short_description']);

        $finder = Finder::find($data['finder_id']);

        if(!$finder){
            return Response::json(array('status' => 404,'message' =>'Vendor does not exists'),404);
        }

        $data['city_id'] = (int)$finder['city_id'];

        (isset($finder['contact']['address']) && $finder['contact']['address'] != "" && $data['finder_address'] == "-") ? $data['finder_address'] = $finder['contact']['address'] : null;

        $data['finder_name'] = ucwords($finder->title);

        $batch = array();

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

            $data['batch'] = $batch;

        }

        return $this->generateTmpOrder($data);

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

        if($ratecard->duration > 0){
            $service_duration .= $ratecard->duration ." ".ucwords($ratecard->duration_type);
        }
        if($ratecard->duration > 0 && $ratecard['validity'] > 0){
            $service_duration .= " - ";
        }
        if($ratecard['validity'] > 0){
            $service_duration .=  $ratecard['validity'] ." ".ucwords($ratecard['validity_type']);
        }

        ($service_duration == "") ? $service_duration = "-" : null;

        return $service_duration;

    }

    public function generateTmpOrder($data = false){

        if(!$data){

            $postdata = Input::json()->all();
            $data =	array_except(Input::json()->all(), array('preferred_starting_date'));
            
        }else{

            $postdata = $data;
            $data = array_except($data, array('preferred_starting_date'));
        }
        

        Log::info('Gnerate Tmp Order',$postdata);

        /*$data['service_duration'] = (empty($data['service_duration'])) ? '1 Meal' : $data['service_duration'];*/
        // $required_fiels = ['customer_name', ];

        if(empty($data['customer_name'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_name");
            return Response::json($resp,404);
        }

        if(empty($data['customer_email'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_email");
            return Response::json($resp,404);
        }

        if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
            $resp 	= 	array('status' => 404,'message' => "Invalid Email Id");
            return Response::json($resp,404);
        }

        $data['customer_email'] = strtolower($data['customer_email']);

        /*if(empty($data['customer_identity'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_identity");
            return Response::json($resp,404);
        }*/

        if(empty($data['customer_phone'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_phone");
            return Response::json($resp,404);
        }

        if(empty($data['customer_source'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_source");
            return Response::json($resp,404);
        }

        /*if(empty($data['customer_location'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_location");
            return Response::json($resp,404);
        }*/

        if(empty($data['city_id'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - city_id");
            return Response::json($resp,404);
        }else{
            $citydata 		=	City::find(intval($data['city_id']));
            if(!$citydata){
                $resp 	= 	array('status' => 404,'message' => "City does not exist");
                return Response::json($resp,404);
            }
        }

        if(empty($data['finder_id'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - finder_id");
            return Response::json($resp,404);
        }else{
            $finderdata 		=	Finder::find(intval($data['finder_id']));
            if(!$finderdata) {
                $resp = array('status' => 404, 'message' => "Finder does not exist");
                return Response::json($resp, 404);
            }
        }

        if(empty($data['finder_name'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - finder_name");
            return Response::json($resp,404);
        }

        if(empty($data['finder_address'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - finder_address");
            return Response::json($resp,404);
        }

        if (!in_array($data['type'], $this->ordertypes)) {
            $resp 	= 	array('status' => 404,'message' => "Invalid Order Type");
            return Response::json($resp,404);
        }

        if($data['type'] != "events"){
            if(empty($data['service_id'])){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - service_id");
                return Response::json($resp,404);
            }else{
                $servicedata 		=	Service::find(intval($data['service_id']));
                if(!$servicedata) {
                    $resp = array('status' => 404, 'message' => "Service does not exist");
                    return Response::json($resp, 404);
                }
            }
        }

        if(empty($data['service_name'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - service_name");
            return Response::json($resp,404);
        }

        if(isset($data['ratecard_id']) && $data['ratecard_id'] != ""){
            $ratecarddata 		=	Ratecard::find(intval($data['ratecard_id']));
            if(!$ratecarddata) {
                $resp = array('status' => 404, 'message' => "Ratecard does not exist");
                return Response::json($resp, 404);
            }
        }

        if(empty($data['amount'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - amount");
            return Response::json($resp,404);
        }

        if(empty($data['type'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing Order Type - type");
            return Response::json($resp,404);
        }

        //Validation base on order type
        if($data['type'] == 'fitmania-dod' || $data['type'] == 'fitmania-dow'){

            if( empty($data['serviceoffer_id']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - serviceoffer_id");
                return Response::json($resp,404);
            }

            if( empty($postdata['preferred_starting_date']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - preferred_starting_date");
                return Response::json($resp,404);
            }

            /* limit | buyable | sold | acitve | left */
            $serviceoffer 		= 	Serviceoffer::find(intval($data['serviceoffer_id']));
            if(isset($serviceoffer->buyable) && intval($serviceoffer->buyable) == 0){
                $resp 	= 	array('status' => 404,'message' => "Buyable limit reach to zero :)");
                return Response::json($resp,404);
            }

            if(isset($serviceoffer->buyable) && intval($serviceoffer->buyable) > 0){
                $offer_buyable 		=  	$serviceoffer->buyable - 1;
            }else{
                $offer_buyable 		=  	intval($serviceoffer->limit) - 1;
            }
            $service_offerdata  = 	['buyable' => intval($offer_buyable)];
            $serviceoffer->update($service_offerdata);

        }

        if($data['type'] == 'memberships' ||  $data['type'] == 'healthytiffintrail' ||  $data['type'] == 'healthytiffinmembership'){
            if( empty($data['service_duration']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - service_duration");
                return Response::json($resp,404);
            }
        }else{
            $data['service_duration'] = (isset($data['service_duration']) && $data['service_duration'] != "") ? $data['service_duration'] : "";
        }

        if($data['type'] == 'healthytiffintrail' ||  $data['type'] == 'healthytiffinmembership'){

            if( empty($postdata['preferred_starting_date']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - preferred_starting_date");
                return Response::json($resp,404);
            }

            if( empty($data['meal_contents']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - meal_contents");
                return Response::json($resp,404);
            }

            $data['membership_duration_type'] = 'healthy_tiffin_snacks';
        }

        //Validation base on order type for sms body and email body  zumbathon','booiaka
        if($data['type'] == 'zumbathon' || $data['type'] == 'booiaka' || $data['type'] == 'fitmaniadealsofday' || $data['type'] == 'fitmaniaservice' || $data['type'] == 'zumbaclub' || $data['type'] == 'kutchi-minithon' || $data['type'] == 'eefashrof' ){
            if( empty($data['sms_body']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - sms_body");
                return Response::json($resp,404);
            }

            if( empty($data['email_body1']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - email_body1");
                return Response::json($resp,404);
            }

            if( empty($data['email_body2']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - email_body2");
                return Response::json($resp,404);
            }
        }
        // return $data;

        $customer_id 		=	(isset($data['customer_id']) && $data['customer_id'] != "") ? $data['customer_id'] : autoRegisterCustomer($data);

        if($data['type'] == 'booktrials'/* ||  $data['type'] == 'healthytiffintrail'||  $data['type'] == 'vip_booktrials'||  $data['type'] == '3daystrial'*/){

           // Throw an error if user has already booked a trial for that vendor...
           $alreadyBookedTrials = $this->utilities->checkExistingTrialWithFinder($data['customer_email'], $data['customer_phone'], $data['finder_id']);
           if(count($alreadyBookedTrials) > 0){
               $resp 	= 	array('status' => 403,'message' => "You have already booked a trial for this vendor");
               return Response::json($resp,403);
           }

            // Throw an error if user has already booked a trial on same schedule timestamp..
            if(isset($data['schedule_date'])&&isset($data['schedule_slot'])){
                $dates = $this->utilities->getDateTimeFromDateAndTimeRange($data['schedule_date'],$data['schedule_slot']);
                $UpcomingTrialsOnTimestamp = $this->utilities->getUpcomingTrialsOnTimestamp($customer_id, $dates['start_timestamp'],$data['finder_id']);
                if(count($UpcomingTrialsOnTimestamp) > 0){
                    $resp 	= 	array('status' => 403,'message' => "You have already booked a trial on same datetime");
                    return Response::json($resp,403);
                }
            }
        }

        $email_body2 		=	(Input::json()->get('email_body2') != "-") ? Input::json()->get('email_body2') : '';

//		if($data['type'] == 'fitmania-dod' || $data['type'] == 'fitmania-dow'){
//			$reminderTimeAfter12Min 	=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(12);
//			$buyable_after12min 		= 	$this->checkFitmaniaBuyable($orderid ,'checkFitmaniaBuyable', 0, $reminderTimeAfter12Min);
//			array_set($data, 'buyable_after12min_queueid', $buyable_after12min);
//		}

        if($data['type'] == 'fitmania-dod' || $data['type'] == 'fitmania-dow' || $data['type'] == 'fitmania-membership-giveaways'){
            $peppertapobj 	= 	Peppertap::where('status','=', 0)->first();
            if($peppertapobj){
                array_set($data, 'peppertap_code', $peppertapobj->code);
                $peppertapstatus 	=	$peppertapobj->update(['status' => 1]);
            }
        }

        if(!isset($data['amount_finder'])  && isset($data['amount']) && $data['amount'] != "" && $data['amount'] != "-"){
            array_set($data, 'amount_finder', intval($data['amount']));
        }


        array_set($data, 'customer_id', intval($customer_id));

        $device_type						= 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id							= 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

        if($device_type != '' && $gcm_reg_id != ''){

            $regData = array();

            $regData['customer_id'] = $customer_id;
            $regData['reg_id'] = $gcm_reg_id;
            $regData['type'] = $device_type;

            $this->utilities->addRegId($regData);
        }

        if(isset($postdata['preferred_starting_date']) && $postdata['preferred_starting_date']  != '') {

            if(trim($postdata['preferred_starting_date']) != '-'){
                $date_arr = explode('-', $postdata['preferred_starting_date']);
                $preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime($postdata['preferred_starting_date']));
                array_set($data, 'start_date', $preferred_starting_date);
                array_set($data, 'preferred_starting_date', $preferred_starting_date);
            }
        }


        if(trim(Input::json()->get('finder_id')) != '' ){

            $finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval(Input::json()->get('finder_id')))->first()->toArray();


            $disableTrialMembership = $this->disableTrialMembership($data);

            if($disableTrialMembership['status'] != 200){

                return Response::json($disableTrialMembership,$disableTrialMembership['status']);
            }

        
            $finder_city						=	(isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
            $finder_location					=	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
            $finder_address						= 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
            $finder_vcc_email					= 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
            $finder_vcc_mobile					= 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
            $finder_poc_for_customer_name		= 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
            $finder_poc_for_customer_no			= 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
            $show_location_flag 				=   (count($finder['locationtags']) > 1) ? false : true;
            $share_customer_no					= 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;
            $finder_lon							= 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $finder_lat							= 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_category_id					= 	(isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";
            $finder_slug						= 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";

            array_set($data, 'finder_city', trim($finder_city));
            array_set($data, 'finder_location', trim($finder_location));
            array_set($data, 'finder_address', trim($finder_address));
            array_set($data, 'finder_vcc_email', trim($finder_vcc_email));
            array_set($data, 'finder_vcc_mobile', trim($finder_vcc_mobile));
            array_set($data, 'finder_poc_for_customer_name', trim($finder_poc_for_customer_name));
            array_set($data, 'finder_poc_for_customer_no', trim($finder_poc_for_customer_no));
            array_set($data, 'show_location_flag', $show_location_flag);
            array_set($data, 'share_customer_no', $share_customer_no);
            array_set($data, 'finder_lon', $finder_lon);
            array_set($data, 'finder_lat', $finder_lat);
            array_set($data, 'finder_branch', trim($finder_location));
            array_set($data, 'finder_category_id', $finder_category_id);
            array_set($data, 'finder_slug', $finder_slug);

        }

        
        array_set($data, 'batch_time', '');
        array_set($data, 'source_of_membership', 'real time');

        if(isset($data['batches']) && $data['batches'] != ""){
            if(is_array($data['batches'])){
                $data['batches'] = $data['batches'];
            }else{
                $data['batches'] = json_decode($data['batches'],true);
            }

            foreach ($data['batches'] as $key => $value) {

                if(isset($value['slots'][0]['start_time']) && $value['slots'][0]['start_time'] != ""){
                    $data['batch_time'] = strtoupper($value['slots'][0]['start_time']);
                    break;
                }
            }
        }

        if(isset($data['batch']) && $data['batch'] != ""){
            
            if(is_array($data['batch'])){
                $data['batch'] = $data['batch'];
            }else{
                $data['batch'] = json_decode($data['batch'],true);
            }



            foreach ($data['batch'] as $key => $value) {

                if(isset($value['slots']['start_time']) && $value['slots']['start_time'] != ""){
                    $data['batch_time'] = strtoupper($value['slots']['start_time']);
                    break;
                }

                if(isset($value['slots'][0]['start_time']) && $value['slots'][0]['start_time'] != ""){
                    $data['batch_time'] = strtoupper($value['slots'][0]['start_time']);
                    break;
                }
            }
        }

        if(!isset($data['ratecard_id'])){

            if($data['type'] == 'booktrials'){
                $ratecard  = Ratecard::active()->where('type','trial')->first();

                if($ratecard){
                    $data['ratecard_id'] = (int)$ratecard->_id;
                }
            }

            if($data['type'] == 'workout-session'){
                $ratecard  = Ratecard::active()->where('type','workout session')->first();

                if($ratecard){
                    $data['ratecard_id'] = (int)$ratecard->_id;
                }
            }

        }

        $offer_id = false;
        if(isset($data['ratecard_id']) && $data['ratecard_id'] != ""){

            $ratecard = Ratecard::find((int)$data['ratecard_id']);

            if(isset($ratecard->remarks) && $ratecard->remarks != ""){
                
                $data['ratecard_remarks']  = $ratecard->remarks;
            }

            if($ratecard){

                $data['duration'] = (isset($ratecard->duration)) ? $ratecard->duration : "";
                $data['duration_type'] = (isset($ratecard->duration_type)) ? $ratecard->duration_type : "";

                if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                    $data['amount_finder'] = $ratecard->special_price;
                }else{
                    $data['amount_finder'] = $ratecard->price;
                }

                if(isset($ratecard->validity) && $ratecard->validity != ""){

                    switch ($ratecard->validity_type){
                        case 'days': 
                            $data['duration_day'] = $duration_day = (int)$ratecard->validity;break;
                        case 'months': 
                            $data['duration_day'] = $duration_day = (int)($ratecard->validity * 30) ; break;
                        case 'year': 
                            $data['duration_day'] = $duration_day = (int)($ratecard->validity * 30 * 12); break;
                        default : $data['duration_day'] = $duration_day =  $ratecard->validity; break;
                    }

                    if(isset($postdata['preferred_starting_date']) && $postdata['preferred_starting_date']  != '') {
                        $data['end_date'] = date('Y-m-d 00:00:00', strtotime($postdata['preferred_starting_date']."+ ".$duration_day." days"));
                    }

                    if($duration_day <= 90){
                        $data['membership_duration_type'] = ($duration_day <= 90) ? 'short_term_membership' : 'long_term_membership' ;
                    }
                }

                if($data['type'] == 'memberships' || $data['type'] == 'healthytiffinmembership'){

                    $duration = $ratecard->duration;
                    $duration_type = $ratecard->duration_type;
                    $validity = $ratecard->validity;
                    $validity_type = $ratecard->validity_type;

                    $service_duration = $data['service_duration'] = $this->getServiceDuration($ratecard);
                }

                //$offer = Offer::where('ratecard_id',$ratecard->_id)->where('hidden', false)->where('start_date','<=',new DateTime(date("d-m-Y 00:00:00")))->where('end_date','>=',new DateTime(date("d-m-Y 00:00:00")))->first();
                $offer = Offer::getActiveV1('ratecard_id', intval($ratecard->_id), intval($ratecard->finder_id))->first();

                if($offer){
                    $data['amount_finder'] = $offer->price;
                    $offer_id = $offer->_id;
                    $data['offer_id'] = $offer->_id;
                }

                if(isset($offer->remarks) && $offer->remarks != ""){
                
                    $data['ratecard_remarks']  = $offer->remarks;
                }
                
            }else{

                $resp   =   array('status' => 400,'message' => "Ratecard not found");
                return Response::json($resp,400);
            }
        }

        if($data['type'] == 'events'){

            $data["profile_link"] = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);

            if(isset($data['ticket_quantity'])){

                if(isset($data['ticket_id'])){
                    
                    $ticket = Ticket::where('_id', $data['ticket_id'])->first();

                    if($ticket){
                        $data['amount_customer'] = $data['amount'] = $data['amount_finder'] = $data['ticket_quantity'] * $ticket->price;
                    }else{
                        $resp   =   array('status' => 400,'message' => "Ticket not found");
                        return Response::json($resp,400);
                    }
                    
                }else{

                    $resp   =   array('status' => 400,'message' => "Ticket id not found");
                    return Response::json($resp,400);
                    
                }
            }

            $finder = Finder::where('_id', $data['finder_id'])->first(['title']);
            if($finder){
                $data['finder_name'] = $finder->title;
            }

            $event = DbEvent::where('_id', $data['event_id'])->first(['name', 'slug']);

            if($event){
                $data['event_name'] = $event->name;
                if(in_array($event['slug'],Config::get('app.my_fitness_party_slug'))){
                    $data['event_type'] = "TOI";
                }
            }
        }

        array_set($data, 'service_name_purchase', $data['service_name']);
        array_set($data, 'service_duration_purchase', $data['service_duration']);

        array_set($data, 'status', '0');
        array_set($data, 'email_body2', trim($email_body2));
        array_set($data, 'payment_mode', 'paymentgateway');

        // Generate Order......

        $medical_detail                     =   (isset($data['medical_detail']) && $data['medical_detail'] != '') ? $data['medical_detail'] : "";
        $medication_detail                  =   (isset($data['medication_detail']) && $data['medication_detail'] != '') ? $data['medication_detail'] : "";


        if($medical_detail != "" && $medication_detail != ""){

            $customer_info = new CustomerInfo();
            $response = $customer_info->addHealthInfo($data);
        }

        if(isset($data['reward_ids'])&& count($data['reward_ids']) > 0) {
            $rewardoffers   =     array_map('intval', $data['reward_ids']);
            array_set($data, 'reward_ids', $rewardoffers);
        }

        $orderid = Order::maxId() + 1;

        $code = $orderid.str_random(8);

        array_set($data, 'code', $code);

        if(isset($_GET['device_type']) && $_GET['device_type'] != ""){
            $data["device_type"] = strtolower(trim($_GET['device_type']));
        }

        if(isset($_GET['app_version']) && $_GET['app_version'] != ""){
            $data["app_version"] = (float)$_GET['app_version'];
        }

        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios'])){

            if(isset($data['amount_finder'])){

                $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],(int)$data['finder_id'],'paymentgateway',$offer_id,$customer_id);

                if(isset($data['wallet']) && $data['wallet'] == true){
                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                }
            }

            if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                $fitcash_plus = 0;
                $fitcash = $data['wallet_amount'];

                $req = array(
                    'customer_id'=>$customer_id,
                    'order_id'=>$orderid,
                    'amount'=>$data['wallet_amount'],
                    'amount_fitcash' => $fitcash,
                    'amount_fitcash_plus' => $fitcash_plus,
                    'type'=>'DEBIT',
                    'entry'=>'debit',
                    'description'=>'Paid for Order ID: '.$orderid,
                );
                $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                

                if($walletTransactionResponse['status'] != 200){
                    return Response::json($walletTransactionResponse,$walletTransactionResponse['status']);
                }

                // Schedule Check orderfailure and refund wallet amount in that case....
                $url = Config::get('app.url').'/orderfailureaction/'.$orderid;
                $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

            }

        }else{
            
            if(isset($data['amount_finder'])){

                if(isset($data['wallet']) && $data['wallet'] == true){

                    $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],(int)$data['finder_id'],'paymentgateway',$offer_id,$customer_id);

                    $wallet_amount = $data['wallet_amount'] = $cashback_detail['only_wallet']['fitcash'] + $cashback_detail['only_wallet']['fitcash_plus'];

                    $fitcash_plus = $cashback_detail['only_wallet']['fitcash_plus'];
                    $fitcash = $cashback_detail['only_wallet']['fitcash'];

                    if(isset($data['cashback']) && $data['cashback'] == true){
                        $wallet_amount = $data['wallet_amount'] = $cashback_detail['discount_and_wallet']['fitcash'] + $cashback_detail['discount_and_wallet']['fitcash_plus'];
                        $fitcash_plus = $cashback_detail['discount_and_wallet']['fitcash_plus'];
                        $fitcash = $cashback_detail['discount_and_wallet']['fitcash'];
                    }

                    $req = array(
                        'customer_id'=>$customer_id,
                        'order_id'=>$orderid,
                        'amount'=>$wallet_amount,
                        'amount_fitcash' => $fitcash,
                        'amount_fitcash_plus' => $fitcash_plus,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=>'Paid for Order ID: '.$orderid,
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    

                    if($walletTransactionResponse['status'] != 200){
                        return Response::json($walletTransactionResponse,$walletTransactionResponse['status']);
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$orderid;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);
                }
            }
        }

        if(isset($data['address']) && $data['address'] != ''){

        	$data['customer_address']  = $data['address'];
        }

        $customer = Customer::find((int)$customer_id);

	    if(isset($data['customer_address']) && is_array($data['customer_address']) && !empty($data['customer_address'])){
	    	
	        $customerData['address'] = $data['customer_address'];
	        $customer->update($customerData);

	        $data['customer_address'] = $data['address'] = implode(",", array_values($data['customer_address']));
	    }

        if(isset($data['schedule_slot']) && $data['schedule_slot'] != ""){

            $schedule_slot = explode("-", $data['schedule_slot']);

            $data['start_time'] = trim($schedule_slot[0]);
            $data['end_time']= trim($schedule_slot[1]);
        }

        if(isset($data['schedule_date']) && $data['schedule_date'] != ""){

            $date_arr = $data['schedule_date'];
            $schedule_date = date('Y-m-d 00:00:00', strtotime($data['schedule_date']));
            array_set($data, 'start_date', $schedule_date);

            array_set($data, 'end_date', $schedule_date);

            $data['membership_duration_type'] = 'workout_session';

        }

        $set_vertical_type = array(
            'healthytiffintrail'=>'tiffin',
            'healthytiffinmembership'=>'tiffin',
            'memberships'=>'workout',
            'booktrials'=>'workout',
            'workout-session'=>'workout',
            '3daystrial'=>'workout',
            'vip_booktrials'=>'workout',
        );

        $set_membership_duration_type = array(
            'healthytiffintrail'=>'trial',
            'healthytiffinmembership'=>'short_term_membership',
            'memberships'=>'short_term_membership',
            'booktrials'=>'trial',
            'workout-session'=>'workout_session',
            '3daystrial'=>'trial',
            'vip_booktrials'=>'vip_trial',
        );

        (isset($set_vertical_type[$data['type']])) ? $data['vertical_type'] = $set_vertical_type[$data['type']] : null;

        (isset($data['finder_category_id']) &&  $data['finder_category_id'] == 41) ? $data['vertical_type'] = 'trainer' : null;

        (isset($set_membership_duration_type[$data['type']])) ? $data['membership_duration_type'] = $set_membership_duration_type[$data['type']] : null;

        (isset($data['duration_day']) && $data['duration_day'] >=30 && $data['duration_day'] <= 90) ? $data['membership_duration_type'] = 'short_term_membership' : null;

        (isset($data['duration_day']) && $data['duration_day'] >90 ) ? $data['membership_duration_type'] = 'long_term_membership' : null;

        $data['secondary_payment_mode'] = 'payment_gateway_tentative';

        $countOrder = 0;
        $countOrder  = Order::where('customer_email',$data['customer_email'])->where('_id','!=',$orderid)->count();

        $countTrial = 0;
        $countTrial  = Booktrial::where('customer_email',$data['customer_email'])->count();

        $countCapture = 0;
        $countCapture  = Capture::where('customer_email',$data['customer_email'])->count();

        array_set($data, 'repeat_customer', 'no');

        if($countOrder > 0 || $countTrial > 0 || $countCapture > 0){
            array_set($data, 'repeat_customer', 'yes');
        }
        Log::info("Here before create");
        $order 				= 	new Order($data);
        $order->_id 		= 	$orderid;
        Log::info("Here after create".$order->_id);
        $orderstatus   		= 	$order->save();
        Log::info("Here after save");
        $resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Transaction details for tmp order :)");
        return Response::json($resp);

    }

    public function disableTrialMembership($data){

        $finder     =   Finder::find(intval($data['finder_id']));

        $trial_array = array('vip_booktrials','3daystrial','booktrials','workout-session','healthytiffintrail');
        $membership_array = array('healthytiffinmembership','memberships');
        
        if(in_array($data['type'],$trial_array)){

            if(isset($finder['trial']) && $finder['trial'] == "disable"){

                $message = "Sorry, this class is not available. Kindly book a different slot";

                ($data['type'] == "healthytiffintrail") ? $message = "Sorry, this meal subscription can't be fulfilled." : null;

                return array('status' => 400,'message' => $message);
            }

        }

        
        if(in_array($data['type'],$membership_array)){

            if(isset($finder['membership']) && $finder['membership'] == "disable"){

                $message = "Sorry, this membership purchase can't be fulfilled.";

                ($data['type'] == "healthytiffinmembership") ? $message = "Sorry, this meal subscription can't be fulfilled." : null;

                return array('status' => 400,'message' => $message);
            }

        }

        if(isset($data['service_id']) && $data['service_id'] != ""){

            $service = Service::find((int)$data['service_id']);

            if(in_array($data['type'],$trial_array)){

                if(isset($service['trial']) && $service['trial'] == "disable"){

                    $message = "Sorry, this class is not available. Kindly book a different slot";

                    ($data['type'] == "healthytiffintrail") ? $message = "Sorry, this meal subscription can't be fulfilled." : null;

                    return array('status' => 400,'message' => $message);

                }

            }

            if(in_array($data['type'],$membership_array)){

                if(isset($service['membership']) && $service['membership'] == "disable"){

                    $message = "Sorry, this membership purchase can't be fulfilled.";

                    ($data['type'] == "healthytiffinmembership") ? $message = "Sorry, this meal subscription can't be fulfilled." : null;

                    return array('status' => 400,'message' => $message);

                }

            }

        }

        return array('status' => 200,'message' => 'success');
    }


    public function captureFailOrders(){

        $data		=	Input::json()->all();
        if(empty($data['order_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - order_id");
            return  Response::json($resp, 400);
        }
        if(empty($data['status'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - status");
            return  Response::json($resp, 400);
        }
        $orderid 	=	(int) Input::json()->get('order_id');
        $order 		= 	Order::findOrFail($orderid);
        $orderdata 	=	$order->update($data);
        $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
        return Response::json($resp);
    }

    public function buyArsenalMembership(){

        $data			=	Input::json()->all();
        if(empty($data['order_id'])){
            return Response::json(array('status' => 404,'message' => "Data Missing Order Id - order_id"),404);
        }
        // return Input::json()->all();
        $orderid 	=	(int) Input::json()->get('order_id');
        $order 		= 	Order::findOrFail($orderid);
        $orderData 	= 	$order->toArray();

        // array_set($data, 'status', '1');
        $buydealofday 			=	$order->update(['status' => '1']);
        $sndsSmsCustomer		= 	$this->customersms->buyArsenalMembership($orderData);

        if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
            $order->update(['email_not_sent'=>'buyArsenalMembership']);
        }else{
            $sndsEmailCustomer		= 	$this->customermailer->buyArsenalMembership($orderData);
        }

        $resp 	= 	array('status' => 200,'message' => "Successfully buy Arsenal Membership :)");

        return Response::json($resp,200);
    }


    public function buyLandingpagePurchase(){

        $data			=	Input::json()->all();
        if(empty($data['order_id'])){
            return Response::json(array('status' => 404,'message' => "Data Missing Order Id - order_id"),404);
        }

        if($data['status'] != "success"){
            return Response::json(array('status' => 404,'message' => "Order Failed"),404);
        }

        // return Input::json()->all();
        $orderid 	=	(int) Input::json()->get('order_id');
        $order 		= 	Order::findOrFail($orderid);
        $orderData 	= 	$order->toArray();

        // array_set($data, 'status', '1');
        $buydealofday 			=	$order->update(['status' => '1']);
        $sndsSmsCustomer		= 	$this->customersms->buyLandingpagePurchase($orderData);

        if (filter_var(trim($order->customer_email), FILTER_VALIDATE_EMAIL) === false){
            $order->update(['email_not_sent'=>'buyLandingpagePurchase']);
        }else{
            $sndsEmailCustomer		= 	$this->customermailer->buyLandingpagePurchase($orderData);
        }

        if($orderData['type'] == 'eefashrof'){
            $salecount 			= 	Order::where('type', 'eefashrof')->where('status', '1')->count();
            $sndsSmsVendor		= 	$this->findersms->buyLandingpagePurchaseEefashrof($orderData,$salecount);
        }

        $resp 	= 	array('status' => 200,'message' => "Successfully buy Membership :)");

        return Response::json($resp,200);
    }


    public function exportorders() {

        $order_ids 	=	[5754,5783,5786,5789,5791,5800,5806,5823,5826,5827,5881,5801,5807,5809,5822,5831,5835,5837,5839,5857,5890,5891,5892,5896,5897,5903,5925,5947,5984,5985,5996,5998,6000,6006,6007,6008,6011,6014,6019,6021,6023,6035,6044,6045,6056,6066,6068,6071,6073,6074,6077,6097,6102,6103,6105,6107,6110,6111,6122,6124,6126,6127,6129,6131,6132,6135,6137,6138,6139,6142,6146,6152,6164,6170,6171,6172,6175,6178,6199,6203,6206,6214,6216,6218,6223,6224,6226,6227,6237,6239,6267,6277,6278,6279,6281,6285,6291,6295,6306,6312,6316,6317,6318,6320,6332,6344,6346,6348,6351,6354,6361,6364,6366,6367,6370,6390,6375,6372,6371];
        $orders 	= 	Order::whereIn('_id', $order_ids)->get();

        $fp = fopen('orderlatest.csv', 'w');
        $header = ["ID", "NAME", "EMAIL", "NUMBER", "TYPE" , "AMOUNT" , "ADDRESS"   ];
        fputcsv($fp, $header);

        foreach ($orders as $value) {
            $fields = [$value->_id, $value->customer_name, $value->customer_email, $value->customer_phone,  $value->payment_mode, $value->amount, $value->customer_location];
            fputcsv($fp, $fields);
        }


    }


    public function getOrderDetail($orderid){

        $orderdata 		=	Order::customerValidation(customerEmailFromToken())->find(intval($orderid));

        if(isset($orderdata->reward_ids) && !empty($orderdata->reward_ids)){

        	$rewards = Reward::whereIn("_id",$orderdata->reward_ids)->get();
        	$orderdata->rewards = $rewards;
        }

        if(isset($orderdata->start_date) && $orderdata->start_date == ""){
            unset($orderdata->start_date);
        }

        if(isset($orderdata->preferred_starting_date) && $orderdata->preferred_starting_date == ""){
            unset($orderdata->preferred_starting_date);
        }

        if(!empty($orderdata->type) && $orderdata->type=='events' && !empty($orderdata->event_start_date)){
            $event_start_date = $orderdata->event_start_date['date'];
            $event_end_date = $orderdata->event_end_date['date'];
            $data_time = [];
            $data_time['start']['date'] = date('d M, Y', strtotime($event_start_date));
            $data_time['start']['time'] = date('h:i A', strtotime($event_start_date));
        
            $data_time['end']['date'] = date('d M, Y',strtotime($event_end_date));
            $data_time['end']['time'] = date('h:i A', strtotime($event_end_date));
            
            $orderdata->data_time = $data_time;
            $orderdata->subscription_code = $orderdata['_id'];

            $event_success = EventSuccess::where('city_id', (string)$orderdata['city_id'])->first();
            $orderdata->top_text = $event_success['top_text'];
            $orderdata->footer_text = $event_success['footer_text'];
            $orderdata->cover_image = $event_success['cover_image'];
        }
        if(!$orderdata){
            return $this->responseNotFound('Order does not exist');
        }

        $responsedata 	= ['orderdata' => $orderdata,  'message' => 'Order Detial'];
        return Response::json($responsedata, 200);

    }



    public function checkFitmaniaBuyable($order_id, $label = 'label', $priority = 0, $delay = 0){

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }

        $payload = array('order_id'=>$order_id,'delay'=>$delay,'priority'=>$priority,'label' => $label);
        $route  = 'fitmaniabuyable';
        $result  = $this->sidekiq->sendToQueue($payload,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
        }

    }

    public function emailToPersonalTrainers (){

        $match 			=	array(41);
        // $finders 		=	Finder::whereIn('category_id',$match)->where('status','1')->where('_id',7241)->get()->toArray();
        $finders 		=	Finder::whereIn('category_id',$match)->where('status','1')->get()->toArray();

        // return $finders;

        foreach ($finders as $key => $finder) {
            $finder_id				=	(isset($finder['_id'])) ? $finder['_id'] : [];
            $finder_name			=	(isset($finder['title'])) ? $finder['title'] : "";
            $finder_vcc_email		=	(isset($finder['finder_vcc_email'])) ? $finder['finder_vcc_email'] : "";
            // $finder_vcc_email		=   "sanjay.id7@gmail.com";
            // echo  $finder_id .$finder_name ." - ". $finder_vcc_email. " <br>" ;


            if($finder_name !="" && $finder_vcc_email !=""){
                $queid 	=	"";
                $data  	=	$finder;
                $queid 	=	$this->findermailer->emailToPersonalTrainers($finder_vcc_email, $finder_name, $data);
                echo " <br>". $queid ." - ". $finder_id ." - ".$finder_name ." - ". $finder_vcc_email. " <br>" ;
                echo "==================================================================================================================== <br><br>";
            }

        }
        // return $corders;

        return "email send";


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


    public function orderFailureAction($order_id,$customer_id = false){

        $order = Order::where('_id',(int) $order_id)->where('status',"0")->first();

        if($order == ''){
            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'No Action Required'
                ),200

            );
        }

        if(!empty($order['payment_mode']) && $order['payment_mode'] == 'cod'){

            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'No Action Required 2'
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

        $customer_id = ($customer_id) ? (int)$customer_id : (int)$order['customer_id'];

        // Refund wallet amount if deducted........
        if(isset($order['wallet_amount']) && ((int) $order['wallet_amount']) >= 0){
            $req = array(
                'customer_id'=>$customer_id,
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

    public function errorMessage($errors){

        $errors = json_decode(json_encode($errors));
        $message = array();
        foreach ($errors as $key => $value) {
            $message[$key] = $value[0];
        }

        $message = implode(',', array_values($message));

        return $message;
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





    /**
     * Calculate the number of seconds with the given delay.
     *
     * @param  \DateTime|int  $delay
     * @return int
     */
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

    public function getHash($data){
        $env = (isset($data['env']) && $data['env'] == 1) ? "stage" : "production";

        $data['service_name'] = trim($data['service_name']);
        $data['finder_name'] = trim($data['finder_name']);

        $service_name = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', $data['service_name']);
        $finder_name = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', $data['finder_name']);

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

    public function orderUpdate(){

        $rules = array(
            "customer_name"=>"required",
            "customer_email"=>"email|required",
            "customer_phone"=>"required",
            "payment_mode"=>"required",
            "order_id"=>"numeric|required"
        );

        $data = Input::json()->all();

        Log::info('Order Update',$data);

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {

            return Response::json(array('status' => 401,'message' => $this->errorMessage($validator->errors())),401);

        }else{

            $order_id = (int) $data['order_id'];

            $order = array();

            $order = Order::find($order_id);

            if(count($order) < 1){

                $resp   =   array("status" => 401,"message" => "Order Does Not Exists");
                return Response::json($resp,$resp["status"]);
            }

            if(isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){

                $resp   =   array("status" => 401,"message" => "You have purchased this membership");
                return Response::json($resp,$resp["status"]);
            }


            if(isset($order['finder_id']) && $order['finder_id'] != ""){

                $checkFinderState = $this->utilities->checkFinderState($order['finder_id']);

                if($checkFinderState['status'] != 200){
                    return Response::json($checkFinderState,$checkFinderState['status']);
                }
            }

            if(isset($order->cashback) && $order->cashback == true){

                if(isset($order->status) && $order->status == "1"){
                    $resp   =   array("status" => 401,"message" => "We have already received your request");
                    return Response::json($resp,$resp["status"]);
                }
               
                if(!isset($data['payment_mode']) || $data['payment_mode'] != 'cod'){

                    $order->unset('cashback');
                    $order->unset('reward_info');

                }
            }


            if(isset($order->reward_ids) && count($order->reward_ids) > 0 && !in_array($data['payment_mode'], ['cod']) && !(isset($data['part_payment']) && $data['part_payment'])){ 

                if(isset($order->status) && $order->status == "1"){
                    $resp   =   array("status" => 401,"message" => "We have already received your request");
                    return Response::json($resp,$resp["status"]);
                }

                $order->unset('reward_ids');
                $order->unset('reward_info');
            }

            if(isset($data["payment_mode"]) && $data["payment_mode"] == "cod"){
                
                $data['cod_otp'] = $this->utilities->generateRandomString();
                
                $data["secondary_payment_mode"] = "cod_membership";
            
            }

            $data['amount_finder'] = $order->amount_finder;
            $data['amount'] = $order->amount;
            $data['finder_name'] = $order->finder_name;
            $data['service_name'] = $order->service_name;
            $data['service_duration'] = $order->service_duration;

            $customer_id = $data['customer_id'] = autoRegisterCustomer($data);

            $data['logged_in_customer_id'] = $customer_id;

            $jwt_token = Request::header('Authorization');

            if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

                $decoded = customerTokenDecode($jwt_token);
                $data['logged_in_customer_id'] = (int)$decoded->customer->_id;
            }

            $customer = Customer::find((int)$customer_id);

            if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != ''){
                if(trim($data['preferred_starting_date']) != '-'){
                    $date_arr = explode('-', $data['preferred_starting_date']);
                    $preferred_starting_date            =   date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']));
                    array_set($data, 'start_date', $preferred_starting_date);
                    array_set($data, 'preferred_starting_date', $preferred_starting_date);
                }
            }

            if(isset($data['preferred_payment_date']) && $data['preferred_payment_date']  != ''){
                if(trim(Input::json()->get('preferred_payment_date')) != '-'){
                    $date_arr = explode('-', $data['preferred_payment_date']);
                    $preferred_payment_date            =   date('Y-m-d 00:00:00', strtotime($data['preferred_payment_date']));
                    array_set($data, 'preferred_payment_date', $preferred_payment_date);
                }
            }

            $offer_id = false;

            if(isset($order->ratecard_id) && $order->ratecard_id != ""){

                $ratecard = Ratecard::find((int)$order->ratecard_id);

                if($ratecard){

                    if(isset($ratecard->remarks) && $ratecard->remarks != ""){
                
                        $data['ratecard_remarks']  = $ratecard->remarks;
                    }

                    if($data['payment_mode'] == "paymentgateway"){
                        if(isset($ratecard->special_price) && $ratecard->special_price != 0){
                            $data['amount_finder'] = $ratecard->special_price;
                        }else{
                            $data['amount_finder'] = $ratecard->price;
                        }
                    }

                    if(isset($ratecard->validity) && $ratecard->validity != "" && isset($preferred_starting_date)){
                        $duration_day = (int)$ratecard->validity;
                        $data['duration_day'] = $duration_day;
                        if(isset($postdata['preferred_starting_date']) && $postdata['preferred_starting_date']  != '') {
                            $data['end_date'] = date('Y-m-d 00:00:00', strtotime($preferred_starting_date."+ ".$duration_day." days"));
                        }

                        if($duration_day <= 90){
                            $data['membership_duration_type'] = ($duration_day <= 90) ? 'short_term_membership' : 'long_term_membership' ;
                        }
                    }

                    //$offer = Offer::where('ratecard_id',$ratecard->_id)->where('hidden', false)->where('start_date','<=',new DateTime(date("d-m-Y 00:00:00")))->where('end_date','>=',new DateTime(date("d-m-Y 00:00:00")))->first();
                    $offer = Offer::getActiveV1('ratecard_id', intval($ratecard->_id), intval($ratecard->finder_id))->first();
                    
                    if($offer){
                        $data['amount_finder'] = $offer->price;
                        $offer_id = $offer->_id;
                        $data['offer_id'] = $offer->_id;
                    }

                    if(isset($offer->remarks) && $offer->remarks != ""){
                
                        $data['ratecard_remarks']  = $offer->remarks;
                    }
                    
                }else{

                    $resp   =   array('status' => 401,'message' => "Ratecard not found");
                    return Response::json($resp,401);
                }
            }

            if(isset($data['amount_finder']) && !(isset($order['cashback_detail']) && isset($order['cashback_detail']['amount_deducted_from_wallet']) && $order['cashback_detail']['amount_deducted_from_wallet']>0)){

                $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],(int)$order->finder_id,$data['payment_mode'],$offer_id);

                if(isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){

                    if(isset($data['wallet']) && $data['wallet'] == true){


                        if(isset($customer->demonetisation)){

                            $wallet_amount = $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];

                            if(isset($data['cashback']) && $data['cashback'] == true){

                                $data['amount'] = $data['amount'] - $data['cashback_detail']['amount_discounted'];
                            }

                            $data['amount'] = $data['amount'] - $data['wallet_amount'];

                            $req = array(
                                'customer_id'=>$customer_id,
                                'order_id'=>$order_id,
                                'amount'=>$wallet_amount,
                                'type'=>'DEBIT',
                                'entry'=>'debit',
                                'description'=> $this->utilities->getDescription($data),
                                'order_type'=>$order['type'],
                            );
                            $walletTransactionResponse = $this->utilities->walletTransactionNew($req);
                            
                            if($walletTransactionResponse['status'] != 200){
                                return $walletTransactionResponse;
                            }else{
                                // $data['amount_discounted'] = $walletTransactionResponse['wallet_transaction_debit'];
                                $data['wallet_transaction_debit'] = $walletTransactionResponse['wallet_transaction_debit'];
                            }

                            // Schedule Check orderfailure and refund wallet amount in that case....
                            $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$data['logged_in_customer_id'];
                            $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                            $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                        }else{

                            $wallet_amount = $data['wallet_amount'] = $cashback_detail['only_wallet']['fitcash'] + $cashback_detail['only_wallet']['fitcash_plus'];

                            $fitcash = $cashback_detail['only_wallet']['fitcash'];
                            $fitcash_plus = $cashback_detail['only_wallet']['fitcash_plus'];

                            if(isset($data['cashback']) && $data['cashback'] == true){

                                $wallet_amount = $data['wallet_amount'] = $cashback_detail['discount_and_wallet']['fitcash'] + $cashback_detail['discount_and_wallet']['fitcash_plus'];

                                $fitcash = $cashback_detail['discount_and_wallet']['fitcash'];
                                $fitcash_plus = $cashback_detail['discount_and_wallet']['fitcash_plus'];
                            }

                            $data['amount'] = $data['amount'] - $data['wallet_amount'];

                            $req = array(
                                'customer_id'=>$customer_id,
                                'order_id'=>$order_id,
                                'amount'=>$wallet_amount,
                                'amount_fitcash' => $fitcash,
                                'amount_fitcash_plus' => $fitcash_plus,
                                'type'=>'DEBIT',
                                'entry'=>'debit',
                                'order_type'=>$order['type'],
                                'description'=>$this->utilities->getDescription($data),
                            );
                            $walletTransactionResponse = $this->utilities->walletTransactionOld($req,$data);
                            

                            if($walletTransactionResponse['status'] != 200){
                                return Response::json($walletTransactionResponse,$walletTransactionResponse['status']);
                            }

                            // Schedule Check orderfailure and refund wallet amount in that case....
                            $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$data['logged_in_customer_id'];
                            $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                            $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);
                        }

                    }elseif(isset($data['cashback']) && $data['cashback'] == true){
                        $data['amount'] = $data['amount'] - $data['cashback_detail']['amount_discounted'];
                    }
                }
            }

            if(isset($data['reward_ids'])&& count($data['reward_ids']) > 0) {
                $rewardoffers   =     array_map('intval', $data['reward_ids']);
                array_set($data, 'reward_ids', $rewardoffers);
            }

            if(isset($data['address']) && $data['address'] != ''){

                $data['customer_address']  = $data['address'];
            }

            if(isset($data['customer_address']) && is_array($data['customer_address']) && !empty($data['customer_address'])){
                
                $customerData['address'] = $data['customer_address'];
                $customer->update($customerData);

                $data['customer_address'] = $data['address'] = implode(",", array_values($data['customer_address']));
            }

            if(isset($data['payment_mode']) && $data['payment_mode'] != ''){

                $data['payment_mode']  = $data['payment_mode'];

                if($data['payment_mode'] == "cod"){
                  $data["secondary_payment_mode"] = "cod_membership";
                }

            }

            if(isset($data['part_payment']) && $data['part_payment']){

                $data['amount'] = (int)($order["part_payment_calculation"]['amount']);

                $convinience_fee = isset($order['convinience_fee']) ? $order['convinience_fee'] : 0;

                $twenty_percent_amount = $convinience_fee + (int)(($order["amount_customer"] - $convinience_fee)*0.2);

                Log::info('$twenty_percent_amount::'.$twenty_percent_amount);

                $coupon_discount = isset($order["coupon_discount_amount"]) ? $order["coupon_discount_amount"] : 0;

                if(isset($order['wallet_amount'])){

                    if($twenty_percent_amount < $order['wallet_amount']){

                        $data['full_payment_wallet'] = true;
                        
                        $refund_amount = $order['wallet_amount']-$twenty_percent_amount;

                        Log::info("returning wallet::".$refund_amount);

                        $wallet_data = array(
                            'customer_id'=>$order['customer_id'],
                            'amount'=>$refund_amount,
                            'amount_fitcash' => 0,
                            'amount_fitcash_plus' => $refund_amount,
                            'type'=>'CREDIT',
                            'entry'=>'credit',
                            'description'=>"Refund for Order ID: ".$order['_id'],
                        );
                        $walletTransactionResponse = $this->utilities->walletTransaction($wallet_data);

                        $data['wallet_amount'] = $order['wallet_amount'] = $twenty_percent_amount;
                    }

                }
                
                $fitcash_applied = isset($order['wallet_amount']) ? $order['wallet_amount'] : 0;

                $data['remaining_amount'] = $order['amount_customer'] - $data['amount'] - $coupon_discount - $order['wallet_amount'];
                

            }

            if(isset($data['reward_ids']) && !empty($data['reward_ids'])){
                $reward_detail = array();
                $reward_ids = array_map('intval',$data['reward_ids']);
                $rewards = Reward::whereIn('_id',$reward_ids)->get(array('_id','title','quantity','reward_type','quantity_type'));
                if(count($rewards) > 0){
                    foreach ($rewards as $value) {
                        $title = $value->title;
                        if($value->reward_type == 'personal_trainer_at_studio' && isset($order->finder_name) && isset($order->finder_location)){
                            $title = "Personal Training At ".$order->finder_name." (".$order->finder_location.")";
                        }
                        $reward_detail[] = ($value->reward_type == 'nutrition_store') ? $title : $value->quantity." ".$title;
                    }
                    $reward_info = (!empty($reward_detail)) ? implode(" + ",$reward_detail) : "";
                    array_set($data, 'reward_info', $reward_info);
                }
            }

            if(isset($data['cashback']) && $data['cashback'] === true && isset($order['cashback_detail']) ){
                $reward_info = "Cashback";
                
                array_set($data, 'reward_info', $reward_info);
            }
            if(isset($data["coupon_code"]) && $data["coupon_code"] != ""){
                $ratecard = Ratecard::find($data['ratecard_id']);
                Log::info("Customer Info". $customer_id);
                $couponCheck = $this->customerreward->couponCodeDiscountCheck($ratecard,$data["coupon_code"],$customer_id);
                if(isset($couponCheck["coupon_applied"]) && $couponCheck["coupon_applied"] && !isset($order->coupon_discount_amount)){
                    $data["amount"] = $data["amount"] > $couponCheck["data"]["discount"] ? $data["amount"] - $couponCheck["data"]["discount"] : 0;
                    $data["coupon_discount_amount"] = $data["amount"] > $couponCheck["data"]["discount"] ? $couponCheck["data"]["discount"] : $data["amount"];
                }
            }
            if($data['amount'] == 0){
                $data['full_payment_wallet'] = true;
            }else{
                $data['full_payment_wallet'] = false;
            }

            $txnid = "FIT".$order_id;
            $successurl = $order['type'] == "memberships" ? Config::get('app.website')."/paymentsuccess" : Config::get('app.website')."/paymentsuccesstrial";
            $mobilehash = "";
           
            $data['txnid'] = $txnid;
            $hash = getHash($data);

            $data = array_merge($data,$hash);

            if(isset($data['reward_ids']) && !empty($data['reward_ids']) && isset($data['preferred_payment_date']) && $data['preferred_payment_date']  != ''){
                $data['order_confirmation_customer']= date('Y-m-d H:i:s',time());
            }

            $order = Order::find($order_id);

            

            if(isset($data['payment_mode']) && $data['payment_mode'] == 'cod'){
                $group_id = isset($data['group_id']) ? $data['group_id'] : null;
                $order->group_id = $data['group_id']  = $this->utilities->addToGroup(['customer_id'=>$data['customer_id'], 'group_id'=>$group_id, 'order_id'=>$order['_id']]);
                $this->customermailer->orderUpdateCOD($order->toArray());
                $this->customersms->orderUpdateCOD($order->toArray());
            }
            $order->update($data);

            $result['firstname'] = strtolower($data['customer_name']);
            $result['lastname'] = "";
            $result['phone'] = $data['customer_phone'];
            $result['email'] = strtolower($data['customer_email']);
            $result['orderid'] = $order_id;
            $result['txnid'] = $txnid;
            $result['amount'] = $data['amount'];
            $result['productinfo'] = strtolower($data['productinfo']);
            $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', $data['service_name']);
            $result['successurl'] = $successurl;
            $result['hash'] = $data['payment_hash'];
            $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
            $result['full_payment_wallet'] = $data['full_payment_wallet'];
            if(isset($order['wallet_amount'])){
                $result['wallet_amount'] = $order['wallet_amount'];
            }

            if($order->payment_mode == "at the studio" /*&& isset($data['reward_info'])*/){
                $this->findermailer->orderUpdatePaymentAtVendor($order->toArray());
                $this->customermailer->orderUpdatePaymentAtVendor($order->toArray());
            }

            $resp   =   array(
                'status' => 200,
                'data' => $result,
                'message' => "Order Updated Successfull"
            );

            return Response::json($resp);
        }
 
    }

    public function inviteForMembership(){

        $req = Input::json()->all();
        Log::info('inviteForMembership',$req);
        // return $req;
        // exit(0);

        if(isset($req['id_for_invite'])){
            $req['order_id'] = $req['id_for_invite'];
        }
        
        // Request Validations...........
        $rules = [
            'order_id' => 'required|integer|numeric',
            'invitees' => 'required|array',
            'source' => 'in:ANDROID,IOS,WEB',
        ];

        $validator = Validator::make($req, $rules);

        if ($validator->fails()) {
            return Response::json(
                array(
                    'status' => 400,
                    'message' => $this->errorMessage($validator->errors()
                    )),400
            );
        }

        // Invitee info validations...........
        $inviteesData = [];

        foreach ($req['invitees'] as $value){

            $inviteeData = ['name'=>$value['name']];

            $rules = [
                'name' => 'required|string',
                'input' => 'required|string',
            ];
            $messages = [
                'name' => 'invitee name is required',
                'input' => 'invitee email or phone is required'
            ];
            $validator = Validator::make($value, $rules, $messages);

            if ($validator->fails()) {
                return Response::json(
                    array(
                        'status' => 400,
                        'message' => $this->errorMessage($validator->errors()
                        )),400
                );
            }

            if(filter_var($value['input'], FILTER_VALIDATE_EMAIL) != '') {
                // valid address
                $inviteeData = array_add($inviteeData, 'email', $value['input']);
            }
            else if(filter_var($value['input'], FILTER_VALIDATE_REGEXP, array(
                    "options" => array("regexp"=>"/^[2-9]{1}[0-9]{9}$/")
                )) != ''){
                // valid phone
                $inviteeData = array_add($inviteeData, 'phone', $value['input']);

            }
            array_push($inviteesData, $inviteeData);

        }


        foreach ($inviteesData as $value){

            $rules = [
                'name' => 'required|string',
                'email' => 'required_without:phone|email',
                'phone' => 'required_without:email',
            ];
            $messages = [
                'email.required_without' => 'invitee email or phone is required',
                'phone.required_without' => 'invitee email or phone is required'
            ];
            $validator = Validator::make($value, $rules, $messages);

            if ($validator->fails()) {
                return Response::json(
                    array(
                        'status' => 400,
                        'message' => $this->errorMessage($validator->errors()
                        )),400
                );
            }
        }

        // Get Host Data an validate booktrial ID......
        $orderData = Order::where('_id', $req['order_id'])
            ->get(array(
                'customer_id', 'customer_name', 'customer_email','customer_phone','service_name',
                'type', 'finder_name', 'finder_location','finder_address', 'amount', 'service_duration', 'finder_slug', 'service_id', 'finder_city' , 'finder_category_id', 'batch', 'ratecard_remarks', 'finder_id'
            ))
            ->first();
            //  Finder::$withoutAppends=true;
            $locationSlug= Finder::where('_id', $orderData['finder_id'])->with(array('location'=>function($query){$query->select('slug');}))->get(['_id', 'location_id'])->first();
           
            $locationSlug = $locationSlug['location']['slug'];

            // return $locationSlug;
            // exit(0);

        $finder_cat_slug = Findercategory::where('_id', intval($orderData['finder_category_id']))->get(['slug']);

            // return $finder_cat_slug;
            // exit(0);

        $errorMessage = !isset($orderData)
            ? 'Invalid Order ID'
//            : count($BooktrialData['invites']) >= 0
//                ? 'You have already invited your friends for this trial'
            : null;
        if($errorMessage){
            return Response::json(
                array(
                    'status' => 422,
                    'message' => $errorMessage
                ),422
            );
        }

        // Validate customer is not inviting himself/herself......
        $emails = array_fetch($inviteesData, 'email');
        $phones = array_fetch($inviteesData, 'phone');


        if(array_where($emails, function ($key, $value) use($orderData)  {
            if($value == $orderData['customer_email']){
                return true;
            }
        })) {
            return Response::json(
                array(
                    'status' => 422,
                    'message' => 'You cannot invite yourself'
                ),422
            );
        }

        if(array_where($phones, function ($key, $value) use($orderData)  {
            if($value == $orderData['customer_phone']){
                return true;
            }
        })) {
            return Response::json(
                array(
                    'status' => 422,
                    'message' => 'You cannot invite yourself'
                ),422
            );
        }

        // Save Invite info..........
        foreach ($inviteesData as $invitee){
            $invite = new Invite();
            $invite->_id = Invite::max('_id') + 1;
            $invite->status = 'pending';
            $invite->host_id = $orderData['customer_id'];
            $invite->host_email = $orderData['customer_email'];
            $invite->host_name = $orderData['customer_name'];
            $invite->host_phone = $orderData['customer_phone'];
            $invite->root_order_id =
                isset($orderData['root_order_id'])
                    ? $orderData['root_order_id']
                    : $req['order_id'];
            $invite->referrer_order_id = $req['order_id'];
            $invite->source = $req['source'];
            isset($invitee['name']) ? $invite->invitee_name = trim($invitee['name']): null;
            isset($invitee['email']) ? $invite->invitee_email = trim($invitee['email']): null;
            isset($invitee['phone']) ? $invite->invitee_phone = trim($invitee['phone']): null;
            $invite->save();

           // continue;

            // Generate bitly for landing page with invite_id and booktrial_id
            // 'www.fitternity.com/buy/'.$orderData['finder_slug'].'/'.$orderData['service_id']finder-slug/service_id
            // 'www.fitternity.com/'.$orderData['finder_city'].'/'.$finder_cat_slug['slug']
            $url = 'www.fitternity.com/'.$orderData['finder_slug']; //'www.fitternity.com/buy/'.$orderData['finder_slug'].'/'.$orderData['service_id'];
            $url2 = 'www.fitternity.com/'.$orderData['finder_city'].'/'.$finder_cat_slug[0]['slug'].'/'.$locationSlug;
            $shorten_url = new ShortenUrl();
            $url = $shorten_url->getShortenUrl($url);
            $url2 = $shorten_url->getShortenUrl($url2);
            if(!isset($url['status']) ||  $url['status'] != 200){
                return Response::json(
                    array(
                        'status' => 422,
                        'message' => 'Unable to Generate Shortren URL'
                    ),422
                );
            }
            if(!isset($url2['status']) ||  $url2['status'] != 200){
                return Response::json(
                    array(
                        'status' => 422,
                        'message' => 'Unable to Generate Shortren URL'
                    ),422
                );
            }
            $url = $url['url'];
            $url2 = $url2['url'];

            // Send email / SMS to invitees...
            $templateData = array(
                'invitee_name'=>$invite['invitee_name'],
                'invitee_email'=>$invite['invitee_email'],
                'invitee_phone'=>$invite['invitee_phone'],
                'host_name' => $invite['host_name'],
                'type'=> $orderData['type'],
                'finder_name'=> $orderData['finder_name'],
                'finder_location'=> $orderData['finder_location'],
                'finder_address'=> $orderData['finder_address'],
                'service_name'=> $orderData['service_name'],
                'url' => $url,
                'url2' => $url2,
                'amount' => $orderData['amount'],
                'service_duration' => $orderData['service_duration'],
                'ratecard_remarks' => $orderData['ratecard_remarks'],
                'batch' => $orderData['batch']
            );

//            return $this->customermailer->inviteEmail($BooktrialData['type'], $templateData);

            isset($templateData['invitee_email']) ? $this->customermailer->inviteEmail($orderData['type'], $templateData) : null;
            isset($templateData['invitee_phone']) ? $this->customersms->inviteSMS($orderData['type'], $templateData) : null;
        }

        return Response::json(
            array(
                'status' => 200,
                'message' => 'Invitation has been sent successfully'
            ),200
        );
    }



    public function getReversehash($data){

        $data['env'] == 1;
        $env = (isset($data['env']) && $data['env'] == 1) ? "stage" : "production";

        $data['service_name'] = trim($data['service_name']);
        $data['finder_name'] = trim($data['finder_name']);

        $service_name = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', $data['service_name']);
        $finder_name = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', $data['finder_name']);

        $key = 'gtKFFx';
        $salt = 'eCwWELxi';
        if($env == "production"){
            $key = 'l80gyM';
            $salt = 'QBl78dtK';
        }

        $txnid = $data['txnid'];
        $amount = $data['amount'].".00";
        $productinfo = $data['productinfo'] = $service_name." - ".$finder_name;
        $firstname = $data['customer_name'];
        $email = $data['customer_email'];
        $udf1 = "";
        $udf2 = "";
        $udf3 = "";
        $udf4 = "";
        $udf5 = "";

        $payhash_str = $salt.'|success||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
//    $payhash_str = "0|".$salt.'|success||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
        
        // Log::info($payhash_str);
        $data['reverse_hash'] = hash('sha512', $payhash_str);        
        // Log::info($data['reverse_hash']);
        return $data;
    }

    public function debitWalletTransaction(){

		$data =   Input::all();

		Log::info('ASP                ::               ',[$data]);
        $walletData = Input::all();
		$wallet_res = $this->utilities->walletTransactionNew($walletData);
	}


}
