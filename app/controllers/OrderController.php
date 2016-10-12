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


class OrderController extends \BaseController {

    protected $customermailer;
    protected $customersms;
    protected $sidekiq;
    protected $findermailer;
    protected $findersms;
    protected $utilities;
    protected $customerreward;

    public function __construct(
        CustomerMailer $customermailer,
        CustomerSms $customersms,
        Sidekiq $sidekiq,
        FinderMailer $findermailer,
        FinderSms $findersms,
        Utilities $utilities,
        CustomerReward $customerreward
    ) {
        parent::__construct();
        $this->customermailer		=	$customermailer;
        $this->customersms 			=	$customersms;
        $this->sidekiq 				= 	$sidekiq;
        $this->findermailer		    =	$findermailer;
        $this->findersms 			=	$findersms;
        $this->utilities 			=	$utilities;
        $this->customerreward 		=	$customerreward;
        $this->ordertypes 		= 	array('memberships','booktrials','fitmaniadealsofday','fitmaniaservice','arsenalmembership','zumbathon','booiaka','zumbaclub','fitmania-dod','fitmania-dow','fitmania-membership-giveaways','womens-day','eefashrof','crossfit-week','workout-session','wonderise','lyfe','healthytiffintrail','healthytiffinmembership','3daystrial','vip_booktrials', 'events');

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

        if($data['status'] == 'success'){
            // Give Rewards / Cashback to customer based on selection, on purchase success......

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

                    }

                    $reward_info = (!empty($reward_detail)) ? implode(" + ",$reward_detail) : "";

                    array_set($data, 'reward_info', $reward_info);
                }

            }

            if(isset($order->cashback) && $order->cashback === true && isset($order->cashback_detail) ){

                $reward_info = "Cashback";
                
                array_set($data, 'reward_info', $reward_info);
            }

            

            array_set($data, 'status', '1');
            array_set($data, 'order_action', 'bought');

            if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
                
                array_set($data, 'membership_bought_at', 'Fitternity Payu Mode');

                $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone','LIKE','%'.substr($order->customer_phone, -8).'%')->orderBy('_id','asc')->where('_id','<',$order->_id)->count();

                if($count > 0){
                    array_set($data, 'acquisition_type', 'renewal_direct');
                }else{
                    array_set($data,'acquisition_type','direct_payment');
                }
            }

            if(isset($order->wallet_refund_sidekiq) && $order->wallet_refund_sidekiq != ''){

            	try {
                    $this->sidekiq->delete($order->wallet_refund_sidekiq);
                }catch(\Exception $exception){
                    Log::error($exception);
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
                if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" ){
                    
                   if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                        if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

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
            if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" ){
                
                if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                    if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                        $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                    }
                    
                }else{
                    $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                }
                
            }


            if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !in_array($finder->category_id, $abundant_category) && $order->type == "memberships" && !isset($order->customer_sms_after3days) && !isset($order->customer_email_after10days)){

                $preferred_starting_date = $order->preferred_starting_date;
                $after3days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 3);
                $after10days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 10);

                $category_slug = "no_category";

                if(isset($order->finder_category_id) && $order->finder_category_id != ""){

                    $finder_category_id = $order->finder_category_id;

                    $category = Findercategory::find((int)$finder_category_id);

                    if($category){
                        $category_slug = $category->slug;
                    }
                }

                $order_data = $order->toArray();

                $order_data['category_array'] = $this->getCategoryImage($category_slug);

                $order->customer_sms_after3days = $this->customersms->orderAfter3Days($order_data,$after3days);
                $order->customer_email_after10days = $this->customermailer->orderAfter10Days($order_data,$after10days);

                $order->update();

            }

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
            return Response::json($resp);
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

        $orderid 			=	Order::max('_id') + 1;
        $data			=	array_except(Input::json()->all(), array('preferred_starting_date'));
        if(trim(Input::json()->get('preferred_starting_date')) != '' && trim(Input::json()->get('preferred_starting_date')) != '-'){
            $date_arr = explode('-', Input::json()->get('preferred_starting_date'));
            $preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
            array_set($data, 'preferred_starting_date', $preferred_starting_date);
            array_set($data, 'start_date', $preferred_starting_date);
        }
        // return $data;

        $customer_id 		=	(Input::json()->get('customer_id')) ? Input::json()->get('customer_id') : $this->autoRegisterCustomer($data);

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


        $count  = Order::where("status","1")->where('customer_email',$data['customer_email'])->where('customer_phone','LIKE','%'.substr($data['customer_phone'], -8).'%')->orderBy('_id','asc')->where('_id','<',$orderid)->count();

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

            $reg_data = array();

            $reg_data['customer_id'] = $customer_id;
            $reg_data['reg_id'] = $gcm_reg_id;
            $reg_data['type'] = $device_type;

            $this->addRegId($reg_data);
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

    public function generateTmpOrder(){

        // $userdata	=	array_except(Input::all(), array());

        $data			=	array_except(Input::json()->all(), array('preferred_starting_date'));
        $postdata		=	Input::json()->all();

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

        if(empty($data['customer_identity'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_identity");
            return Response::json($resp,404);
        }

        if(empty($data['customer_phone'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_phone");
            return Response::json($resp,404);
        }

        if(empty($data['customer_source'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_source");
            return Response::json($resp,404);
        }

        if(empty($data['customer_location'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - customer_location");
            return Response::json($resp,404);
        }

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

        if (!in_array($data['type'], $this->ordertypes)) {
            $resp 	= 	array('status' => 404,'message' => "Invalid Order Type");
            return Response::json($resp,404);
        }

        //Validation base on order type
        if($data['type'] == 'fitmania-dod' || $data['type'] == 'fitmania-dow'){

            if( empty($data['serviceoffer_id']) ){
                $resp 	= 	array('status' => 404,'message' => "Data Missing - serviceoffer_id");
                return Response::json($resp,404);
            }

            if( empty($data['preferred_starting_date']) ){
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

        $customer_id 		=	(Input::json()->get('customer_id')) ? Input::json()->get('customer_id') : $this->autoRegisterCustomer($data);

        if($data['type'] == 'booktrials' ||  $data['type'] == 'healthytiffintrail'||  $data['type'] == 'vip_booktrials'||  $data['type'] == '3daystrial'){

            // Throw an error if user has already booked a trial for that vendor...
//            $alreadyBookedTrials = $this->utilities->checkExistingTrialWithFinder($data['customer_email'], $data['customer_phone'], $data['finder_id']);
//            if(count($alreadyBookedTrials) > 0){
//                $resp 	= 	array('status' => 403,'message' => "You have already booked a trial for this vendor");
//                return Response::json($resp,403);
//            }

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

            $reg_data = array();

            $reg_data['customer_id'] = $customer_id;
            $reg_data['reg_id'] = $gcm_reg_id;
            $reg_data['type'] = $device_type;

            $this->addRegId($reg_data);
        }

        if(isset($postdata['preferred_starting_date']) && $postdata['preferred_starting_date']  != '') {

            if(trim(Input::json()->get('preferred_starting_date')) != '-'){
                $date_arr = explode('-', Input::json()->get('preferred_starting_date'));
                $preferred_starting_date			=	date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
                array_set($data, 'start_date', $preferred_starting_date);
                array_set($data, 'preferred_starting_date', $preferred_starting_date);
            }
        }

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

        $code		=	random_numbers(5);

        array_set($data, 'code', $code);
        array_set($data, 'batch_time', '');

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

        if(isset($data['schedule_date']) && $data['schedule_date'] != ""){
            $data['membership_duration_type'] = 'workout_session';
        }

        if(isset($data['ratecard_id']) && $data['ratecard_id'] != ""){

            $ratecard = Ratecard::find((int)$data['ratecard_id']);

            if(isset($ratecard->remarks) && $ratecard->remarks){
                
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
                    $duration_day = (int)$ratecard->validity;
                    $data['duration_day'] = $duration_day;
                    if(isset($postdata['preferred_starting_date']) && $postdata['preferred_starting_date']  != '') {
                        $data['end_date'] = date('Y-m-d 00:00:00', strtotime($preferred_starting_date."+ ".$duration_day." days"));
                    }

                    if($duration_day <= 90){
                        $data['membership_duration_type'] = ($duration_day <= 90) ? 'short_term_membership' : 'long_term_membership' ;
                    }
                }
                
            }else{

                $resp   =   array('status' => 400,'message' => "Ratecard not found");
                return Response::json($resp,400);
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

        if(isset($data['amount_finder'])){

        	$data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],(int)$data['finder_id']);

            if(isset($data['wallet']) && $data['wallet'] == true){
                $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
            }
        }

        // Deduct wallet balance if applicable and feasible....
        $orderid 			=	Order::max('_id') + 1;


        if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

            $req = array(
                'customer_id'=>$customer_id,
                'order_id'=>$orderid,
                'amount'=>$data['wallet_amount'],
                'type'=>'DEBIT',
                'description'=>'Paid for Order ID: '.$orderid,
            );
            $walletTransactionResponse = $this->utilities->walletTransaction($req)->getData();
            $walletTransactionResponse = (array) $walletTransactionResponse;

            if($walletTransactionResponse['status'] != 200){
                return $walletTransactionResponse;
            }

            // Schedule Check orderfailure and refund wallet amount in that case....
	        $url = Config::get('app.url').'/orderfailureaction/'.$orderid;
	        $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
	        $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

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

        $order 				= 	new Order($data);
        $order->_id 		= 	$orderid;
        $orderstatus   		= 	$order->save();
        $resp 	= 	array('status' => 200, 'order' => $order, 'message' => "Transaction details for tmp order :)");
        return Response::json($resp);

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


    public function autoRegisterCustomer($data){

        $customer 		= 	Customer::active()->where('email', $data['customer_email'])->first();

        if(!$customer) {

            $inserted_id = Customer::max('_id') + 1;
            $customer = new Customer();
            $customer->_id = $inserted_id;
            $customer->name = ucwords($data['customer_name']) ;
            $customer->email = $data['customer_email'];
            $customer->dob =  isset($data['dob']) ? $data['dob'] : "";
            $customer->gender =  isset($data['gender']) ? $data['gender'] : "";
            $customer->fitness_goal = isset($data['fitness_goal']) ? $data['fitness_goal'] : "";
            $customer->picture = "https://www.gravatar.com/avatar/".md5($data['customer_email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
            $customer->password = md5(time());

            if(isset($data['customer_phone'])  && $data['customer_phone'] != ''){
                $customer->contact_no = $data['customer_phone'];
            }

            /*if(isset($data['customer_address'])){

                if(is_array($data['customer_address']) && !empty($data['customer_address'])){

                    $customer->address = implode(",", array_values($data['customer_address']));
                    $customer->address_array = $data['customer_address'];

                }elseif(!is_array($data['customer_address']) && $data['customer_address'] != ''){

                    $customer->address = $data['customer_address'];
                }

            }*/

            $customer->identity = 'email';
            $customer->account_link = array('email'=>1,'google'=>0,'facebook'=>0,'twitter'=>0);
            $customer->status = "1";
            $customer->ishulluser = 1;
            $customer->save();

            return $inserted_id;

        }else{

            $customerData = [];

            try{

                if(isset($data['dob']) && $data['dob'] != ""){
                    $customerData['dob'] = trim($data['dob']);
                }

                if(isset($data['fitness_goal']) && $data['fitness_goal'] != ""){
                    $customerData['fitness_goal'] = trim($data['fitness_goal']);
                }

                if(isset($data['customer_phone']) && $data['customer_phone'] != ""){
                    $customerData['contact_no'] = trim($data['customer_phone']);
                }

                if(isset($data['otp']) &&  $data['otp'] != ""){
                    $customerData['contact_no_verify_status'] = "yes";
                }

                if(isset($data['gender']) && $data['gender'] != ""){
                    $customerData['gender'] = $data['gender'];
                }

                /*if(isset($data['customer_address'])){

                    if(is_array($data['customer_address']) && !empty($data['customer_address'])){

                        $customerData['address'] = implode(",", array_values($data['customer_address']));
                        $customerData['address_array'] = $data['customer_address'];

                    }elseif(!is_array($data['customer_address']) && $data['customer_address'] != ''){

                        $customerData['address'] = $data['customer_address'];
                    }

                }*/

                if(count($customerData) > 0){
                    $customer->update($customerData);
                }

            } catch(ValidationException $e){

                Log::error($e);

            }

            return $customer->_id;
        }

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

        $orderdata 		=	Order::find(intval($orderid));

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

    public function addRegId($data){

        $response = add_reg_id($data);

        return Response::json($response,$response['status']);
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


    public function orderFailureAction($order_id){

        $data = Order::where('_id',(int) $order_id)
            ->where('status',"0")
            ->first();

        if($data == ''){
            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'No Action Required'
                ),200

            );
        }

        // Update order status to failed........
        $data->update(['status' => '-1']);


        // Refund wallet amount if deducted........
        if(isset($data['wallet_amount']) && ((int) $data['wallet_amount']) >= 0){
            $req = array(
                'customer_id'=>$data['customer_id'],
                'order_id'=>$order_id,
                'amount'=>$data['wallet_amount'],
                'type'=>'REFUND',
                'description'=>'Refund for Order ID: '.$order_id,
            );

            $walletTransactionResponse = $this->utilities->walletTransaction($req)->getData();
            $walletTransactionResponse = (array) $walletTransactionResponse;


            if($walletTransactionResponse['status'] != 200){
                return $walletTransactionResponse;
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

            if(isset($order->cashback) && $order->cashback == true){

                $resp   =   array("status" => 401,"message" => "We have already received your request");
                return Response::json($resp,$resp["status"]);
            }

            if(isset($order->reward_ids) && count($order->reward_ids) > 0){

                $resp   =   array("status" => 401,"message" => "We have already received your request");
                return Response::json($resp,$resp["status"]);
            }

            $data['amount_finder'] = $order->amount_finder;
            $data['amount'] = $order->amount;

            $customer_id = $this->autoRegisterCustomer($data);

            if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != ''){
                if(trim(Input::json()->get('preferred_starting_date')) != '-'){
                    $date_arr = explode('-', Input::json()->get('preferred_starting_date'));
                    $preferred_starting_date            =   date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
                    array_set($data, 'start_date', $preferred_starting_date);
                    array_set($data, 'preferred_starting_date', $preferred_starting_date);
                }
            }

            if(isset($data['preferred_payment_date']) && $data['preferred_payment_date']  != ''){
                if(trim(Input::json()->get('preferred_payment_date')) != '-'){
                    $date_arr = explode('-', Input::json()->get('preferred_payment_date'));
                    $preferred_payment_date            =   date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
                    array_set($data, 'preferred_payment_date', $preferred_payment_date);
                }
            }

            if(isset($order->ratecard_id) && $order->ratecard_id != ""){

                $ratecard = Ratecard::find((int)$order->ratecard_id);

                if($ratecard){

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
                    
                }else{

                    $resp   =   array('status' => 401,'message' => "Ratecard not found");
                    return Response::json($resp,401);
                }
            }

            if(isset($data['amount_finder'])){

                $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],(int)$order->finder_id,$data['payment_mode']);

                if(isset($data['wallet']) && $data['wallet'] == true){
                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                }
            }

            if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                $req = array(
                    'customer_id'=>$customer_id,
                    'order_id'=>$order_id,
                    'amount'=>$data['wallet_amount'],
                    'type'=>'DEBIT',
                    'description'=>'Paid for Order ID: '.$order_id,
                );
                $walletTransactionResponse = $this->utilities->walletTransaction($req)->getData();
                $walletTransactionResponse = (array) $walletTransactionResponse;

                if($walletTransactionResponse['status'] != 200){
                    return $walletTransactionResponse;
                }

                // Schedule Check orderfailure and refund wallet amount in that case....
                $url = Config::get('app.url').'/orderfailureaction/'.$order_id;
                $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

            }

            if(isset($data['reward_ids'])&& count($data['reward_ids']) > 0) {
                $rewardoffers   =     array_map('intval', $data['reward_ids']);
                array_set($data, 'reward_ids', $rewardoffers);
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

            $order->update($data);

            if($order->payment_mode == "at the studio" /*&& isset($data['reward_info'])*/){
                $this->findermailer->orderUpdatePaymentAtVendor($order->toArray());
                $this->customermailer->orderUpdatePaymentAtVendor($order->toArray());
            }

            $resp   =   array("status" => 200, 'message' => "Order Updated Successfull",'order' => $order);

            return Response::json($resp);
        }

    }


}
