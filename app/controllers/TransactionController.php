<?PHP

/**
 * ControllerName : TransactionController.
 * Maintains a list of functions used for TransactionController
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\FinderSms as FinderSms;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;
use App\Services\CustomerInfo as CustomerInfo;
use App\Notification\CustomerNotification as CustomerNotification;
use App\AmazonPay\PWAINBackendSDK as PWAINBackendSDK;
use App\AmazonPaynon\PWAINBackendSDK as PWAINBackendSDKNon;
use App\Services\Fitapi as Fitapi;
use App\Services\Fitweb as Fitweb;
use App\Services\Paytm as PaytmService;
use App\Services\PassService as PassService;
use App\Services\PlusService as PlusService;
//use App\Controllers\PaymentGatewayController as GatewayController;
//use App\config\paytm as paytmConfig;
class TransactionController extends \BaseController {

    protected $customermailer;
    protected $customersms;
    protected $sidekiq;
    protected $findermailer;
    protected $findersms;
    protected $utilities;
    protected $customerreward;
    protected $membership_array;
    protected $customernotification;
    protected $fitapi;
    protected $fitweb;
    protected $PaytmService;
    protected $passService;
    protected $plusService;
    
    //protected $GatewayController;
    //protected $paytmConfig;

    public function __construct(
        CustomerMailer $customermailer,
        CustomerSms $customersms,
        Sidekiq $sidekiq,
        FinderMailer $findermailer,
        FinderSms $findersms,
        Utilities $utilities,
        CustomerReward $customerreward,
        CustomerNotification $customernotification,
        Fitapi $fitapi,
        Fitweb $fitweb,
        PaytmService $PaytmService,
        PassService $passService,
        PlusService $plusService
        //GatewayController $GatewayController,
        //paytmConfig $paytmConfig
    ) {
        parent::__construct();
        $this->customermailer       =   $customermailer;
        $this->customersms          =   $customersms;
        $this->sidekiq              =   $sidekiq;
        $this->findermailer         =   $findermailer;
        $this->findersms            =   $findersms;
        $this->utilities            =   $utilities;
        $this->customerreward       =   $customerreward;
        $this->customernotification =   $customernotification;
        $this->fitapi               =   $fitapi;
        $this->fitweb               =   $fitweb;
        $this->passService          =   $passService;
        $this->plusService          =   $plusService;
        $this->ordertypes           =   array('memberships','booktrials','workout-session','healthytiffintrail','healthytiffinmembership','3daystrial','vip_booktrials', 'events');
        $this->appOfferDiscount     =   Config::get('app.app.discount');
        $this->appOfferExcludedVendors 				= Config::get('app.app.discount_excluded_vendors');

        $this->membership_array     =   array('memberships','healthytiffinmembership');

        $this->vendor_token = false;
        $this->PaytmService = $PaytmService;
        $this->passService = $passService;
        //$this->GatewayController = $GatewayController;
        //$this->paytmConfig = $paytmConfig;
        
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

    public function saveTPMemberDetails($orderData) {
        try{
            $tpoDetails = null;
            $tpoId = null;
            if(!empty($orderData['tpo_details']) && !empty($orderData['tpo_details']['tpo_id'])){
                $tpoDetails = $orderData['tpo_details'];
                $tpoId = $tpoDetails['tpo_id'];
                Log::info('tpo_id: ', [$tpoDetails['tpo_id']]);
                Log::info('member_details: ', [$tpoDetails['memberDetails']]);
            } else if (isset($orderData['order_id'])) {
                $tpoId = $orderData['order_id'];
            }
            else {
                Log::info('both order id and tpo details are not available!');
                return ['err' => 'order id and (tpo details or tpo id) are not available'];
            }
            $_env = $orderData['env'];
            Log::info('env: ', [$_env]);
            
            $tpoRec = ThirdPartyOrder::where('_id', $tpoId)->first();
            if(!empty($tpoDetails)){
                $_tempDtls = [];
                foreach ($tpoDetails['memberDetails'] as $rec) {
                    $_tpoRec = [];
                    $_tpoRec['dob'] = $rec['dob'].' 00:00:00'; //new MongoDate(strtotime(date($rec['dob'].' 00:00:00')));
                    $_tpoRec['email_id'] = $rec['emailId'];
                    $_tpoRec['extension'] = $rec['extension'];
                    if(isset($rec['titleCode']))
                        $_tpoRec['title_code'] = $rec['titleCode'];
                    $_tpoRec['first_name'] = $rec['firstName'];
                    $_tpoRec['middle_name'] = $rec['middleName'];
                    $_tpoRec['last_name'] = $rec['lastName'];
                    $_tpoRec['mobile_no'] = $rec['mobileNo'];
                    $_tpoRec['address_line_1'] = $rec['addressLine1'];
                    $_tpoRec['address_line_2'] = $rec['addressLine2'];
                    $_tpoRec['city'] = $rec['addressCity'];
                    $_tpoRec['state'] = $rec['addressState'];
                    $_tpoRec['country'] = "IND";//$rec['addressCountry'];
                    $stateCityDetails = PincodeMaster::where('pincode', $rec['addressPincode'])->where('fit_city_id','exists',true)->first();
                    Log::info('stateCityDetails: ', [$stateCityDetails]);
                    if(empty($stateCityDetails)){
                        return ['err' => "We do not serve in this city."];
                    }
                    $_tpoRec['city'] = $stateCityDetails['city_name'];
                    $_tpoRec['state'] = $stateCityDetails['state_code'];
                    $_tpoRec['pincode'] = $rec['addressPincode'];
                    $_tpoRec['marital_status'] = $rec['maritalStatus'];
                    $_tpoRec['nationality'] = "INDIAN";//$rec['nationality'];
                    if(isset($rec['campaignCode']))
                        $_tpoRec['campaign_code'] = $rec['campaignCode'];
                    $_tpoRec['program_code'] = $rec['programCode'];
                    $_tpoRec['gender'] = $rec['gender'];
                    $_tpoRec['agent_code'] = $rec['agentCode'];
                    $_tpoRec['role'] = $rec['role'];
                    $_tpoRec['relationship'] = $rec['relationship'];
                    $_tpoRec['emp_group_offering_cd'] = $rec['empGroupOfferingCd'];
                    array_push($_tempDtls, $_tpoRec);
                }
                $tpoRec->member_details = $_tempDtls;
            }
            else {
                $tpoRec->pg_payment_mode_selected = $orderData['pg_payment_mode_selected'];
                $tpoRec->repitition = $orderData['repitition'];
                $tpoRec->wallet = $orderData['wallet'];
                $tpoRec->type = $orderData['type'];
                $tpoRec->customer_name = $orderData['customer_name'];
                $tpoRec->customer_phone = $orderData['customer_phone'];
                $tpoRec->customer_source = $orderData['customer_source'];
                $tpoRec->customer_email = $orderData['customer_email'];
                $tpoRec->amount = $orderData['amount'];
            }
            $tpoRec->env = $_env;
            $_txnid = 'TPFIT'.$tpoId;
            if(!isset($tpoRec->txnid)){
                $tpoRec->txnid = $_txnid;
            }
            else{
                $tpoRec->repetition_count = (isset($tpoRec->repetition_count)?$tpoRec->repetition_count+1:1);
                $tpoRec->txnid = $_txnid.'-R'.$tpoRec->repetition_count;
            }
            Log::info('$tpoRec->member_details', $tpoRec->member_details);
            $tpoRec->save();
            $dtls = $tpoRec->member_details;
            Log::info('lets see', [$dtls]);
            return ['txnid' => $tpoRec->txnid];
        }catch(Exception $e) {
            Log::info('Exception in saveTPMemberDetails: ', [$e]);
            return ['err' => 'Something went wrong...'];
        }
    }

    public function capture($data = null){

        $data = $data ? $data : Input::json()->all();

        if(!empty($_SERVER['REQUEST_URI'])){
            Log::info($_SERVER['REQUEST_URI']);
        }
        
        if(!empty(Request::header('Origin'))){
            $data['origin_url'] = Request::header('Origin');
        }
        Log::info('------------transactionCapture---------------',$data);

        if(!empty($data['customer_quantity']) && is_string($data['customer_quantity'])){
            $data['customer_quantity'] = intval($data['customer_quantity']);
        }

        if(!empty($data['coupon_code']) && strtoupper($data['coupon_code']) == "APPLY COUPON"){
            unset($data['coupon_code']);
        }

        if(!empty($data['tpo_details']) || (isset($data['type']) && $data['type']=='thirdparty')){
            $tpMemberDetailsResp = $this->saveTPMemberDetails($data);
            Log::info('$tpMemberDetailsResp: ', [$tpMemberDetailsResp]);
            if(isset($tpMemberDetailsResp['err'])){
                Log::info('error: ', [$tpMemberDetailsResp['err']]);
                return Response::json(['status' => 500, 'message' => $tpMemberDetailsResp['err']], 500);    
            }
            $orderData = $this->getThirdPartyOrderDetails($tpMemberDetailsResp['txnid']);
            if(empty($orderData)){
                return Response::json(['status' => 500, 'message' => 'order details not found'], 500);
            }
            return Response::json(['data' => $orderData], 200);
        }

        foreach ($data as $key => $value) {

            if(is_string($value)){
                $data[$key] = trim($value);
            }
        }

        if(isset($data['order_id']) && $data['order_id'] != ""){
            $data['order_id'] = intval($data['order_id']);
            $existing_order = Order::where('_id', $data['order_id'])->first();

            if(!empty($existing_order['pass_id'])){
                return $this->passService->passCapture($data, $existing_order);
            }
            if(!empty($existing_order['pay_later'])){

                $pay_later_data = $this->getPayLaterData($existing_order);
                $data = array_merge($data, $pay_later_data);
            }
            Log::info('------------transactionCapture---------------',$data);
        }

        

        if(!empty($data['qrcodepayment']) && empty($data['customer_phone']) && !empty($data['customer_email'])){
            
            $customer = Customer::where('email', $data['customer_email'])->first(['contact_no']);

            if(!empty($customer['contact_no'])){
                $data['customer_phone'] = substr($customer['contact_no'], -10);
            }

        }

        if($data['type'] == 'giftcoupon'){
            return $this->giftCouponCapture();
        }



        if(!isset($data['type'])){
            return Response::json(array('status' => 404,'message' =>'type field is required'), $this->error_status);
        }

        if(!empty($data['type']) && $data['type'] == 'membershipwithpg' && $data['type'] == 'membershipwithoutpg'){
            $data['type'] = 'memberships';
        }

        if($this->vendor_token){

            $data['customer_source'] = 'kiosk';

            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

            $data['finder_id'] = (int)$vendor['_id'];
        }

        if(!empty($data['type']) && $data['type'] == 'events' && !empty($data['sub_type']) && $data['sub_type'] == Config::get('app.music_run_event_type')){

            $transform_response = $this->utilities->tranformEventData($data);

            if($transform_response['status']!=200){
                return Response::json($transform_response, 404);
            }

            $data = $transform_response['data'];

        }
        
        $status = "0";
        if(!empty($data['customer_source']) && $data['customer_source'] == 'admin'){
            $status = $data['status'];
            $payment_mode_admin = $data['payment_mode'];
            $secondary_payment_mode_admin = $data['secondary_payment_mode'];

            Log::info("status 1 ::",[$status]);
            Log::info("payment_mode 1 ::",[$payment_mode_admin]);
            Log::info("secondary_payment_mode_admin 1 ::",[$secondary_payment_mode_admin]);
        }
        
        $rules = array(
            'customer_name'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required',
            'customer_source'=>'required',
            // 'ratecard_id'=>'required|integer|min:1',
            'type'=>'required'
        );

        $asshole_numbers = ["7838038094","7982850036","8220720704","8510829603","9990099996","8368952443","7021874658","9726299714"];
        
        if(isset($data["customer_phone"]) && in_array(substr($data["customer_phone"], -10), $asshole_numbers)){
            return Response::json(['status'=>400, 'message'=>"Please try after some time."], $this->error_status);
        }

        $asshole_emails = ["vasuk573@gmail.com","vasukatara01@gmail.com","gauravkhaturiaofficial@gmail.com","gauravactor11@gmail.com","gauravactor515@gmail.com"];
        
        if(isset($data["customer_email"]) && in_array(strtolower($data["customer_email"]),$asshole_emails)){
            return Response::json(['status'=>400, 'message'=>"Please try after some time."], $this->error_status);            
        }
		if(isset($data["gcm_reg_id"])){
            $asshole_gcm = ["fAbd5Ws_am4:APA91bFHI-OBhPCXbBkcmcnp7zrlhwVwpQ4bv9MJzTQXDKAxSv7OKMAscV7OCQLthJvYce6D_EUvj6glKkcZsgVsIn0ZmTn90tqcsoyvFECCU-ToR9tX-9pnCgdKpa5tGkFz9AHSvt34", "ee_4c5_T5hs:APA91bFR32t7MT_TTdIeqA82yFjur4LX5cmZXD-sSMiuTdBAbblYpWbDFQvzYaTKMiRDzygIMP9BxZdgP_Q22u2QWqS1nr9b5AOw4rbHuTP5KyPt6S3D6SHnghwc7bt1_106sQrS-ZR4","fiG_xAuFrzk:APA91bEii0FxJeMcw3BW0zuvZLo9zqLnHhDERfMF40DNzd0IhWexY4n0jlhYU9s_vUIr8-gJs8C_-Nso73vePfpSfs8wavCglzndPVs_kqK6bRG4flSEH79agE8iIeViaL8nTkqobD2w","frxy4N3LHIs:APA91bFEda0Je4wZacmEDmHyoiiZdORyE5gAb-t4HStYvCCP4VAHisYpIHa-9JOk6F1pXf2CypivaUrsIFpZmVrw1ksyqszVmr31GESSQXBUHnxMrnrCfpNJDQxKudtbaNzxJGWFC1Q3",
            "cbNQ-s6LBFQ:APA91bGujfUOMpf6ytwb4uXGZupxCFoTpSIp02L8ta2zNkPgCsgEOuk-Zcr__ibg04MUr3HlZPFjDIc1vhtLaP8N0mU6Cq-T_s8JIHx--p3fxAJ6GCAZJa8iHJ0ogM_O_FMStqJHnz9l",
            "e4SR9PNsZG0:APA91bFUANpsL8dOOEboWDiElWnZD9VdydDlVO0SEsy91HNIM47av51AByOlNtrlM1FxICHCB_x4XLx9zsTrGQaddqJ3vi1PoitRnsq2ewQalM24mFlalWowtBhd4i_MRqoJRtT9ek1G",
            "fswywCMnxvc:APA91bFFTXhn-stB_-YwjKxoEH5LcdplSNjZk6NXxF1eIUFqk0HRyl8jn88_hBkMo_xIkcP29r6VMshv1emkeoW_1M269_EIbiHSJNr1_2a6HBs6IgOpRafegdc_hF8GMlhKUiOHYSvL",
            "dS0QVQ5ONTo:APA91bFDc4C_VG0f67_mT30DTa3q-GdLgZeJpC1Q9qKOVFvH-ed2FzMlDnDbJ7tjIBpqPJMAjewyR1nadwUq4p67g3cHvGm9jCKW84vQHL6tj-5MloN2kLeIih-o1pxARn4pA5CN2VNz",
            "dImOwj5PIRk:APA91bFAE9g9Z85Qm5UpX4WFXs17npGZ7JSihLUFTr7d5oPYCXX_QuhfW4GjEyiVKFi_p6moBq7eDzUJ7Fp6Kb6Eb75hWfU7Xf1CxG3BwWNKaDhkcJ8oIDzIQ3IArdjQYpdHfrZ7kiFT",
            "fLRTgVYHy0E:APA91bHJ-sFZPPl7w-3eRtAkzPi0QP2c2WsLTj6mquargudmtAmuGsO0UxfZ1GqJF_04x4dXACtGFZZQIOHFKQSinuMwqKN4lYdUWTqtksnDr3ql1dJeg-j9Qz9aSahpMcDVVUtA8HNB"
        ];
			if(in_array($data["gcm_reg_id"],$asshole_gcm)){
                return Response::json(['status'=>400, 'message'=>"Please try after some time."], $this->error_status);				
			}
		}
		

        if(!isset($data['manual_order'])){

            if(!isset($data['ratecard_id']) && !isset($data['ticket_id'])){
                return Response::json(array('status'=>400, 'message'=>'Ratecard Id or ticket Id is required'), $this->error_status);
            }
        }

        if(empty($data['service_id']) && !empty($data['ratecard_id']))
        {
            
            Ratecard::$withoutAppends=true;
        	$servId=Ratecard::find(intval($data['ratecard_id']))->first(['service_id']);
        	(!empty($servId))?$data['service_id']=$servId->service_id:"";
        }

        
        $workout = array('vip_booktrials','3daystrial','booktrials','workout-session');
        if(in_array($data['type'],$workout)){
            $service_category = Service::find($data['service_id'], ['servicecategory_id']);

            if($service_category['servicecategory_id'] == 65){
                $workout_rules = array(
                    'schedule_date'=>'required',
                    // 'schedule_slot'=>'required'
                );
            }else{
                $workout_rules = array(
                    'schedule_date'=>'required',
                    'schedule_slot'=>'required'
                );
            }


            $rules = array_merge($rules,$workout_rules);
        }

        $membership = array('healthytiffintrail','healthytiffinmembership');

        if(!$this->vendor_token){

            $membership[] = 'memberships';
        }

        // if(in_array($data['type'],$membership)){
        //     $membership_rules = array(
        //         'preferred_starting_date'=>'required'
        //     );


        //     $rules = array_merge($rules,$membership_rules);
        // }

        if(in_array($data['type'] == 'events',$membership)){

            $event_rules = [
                'event_id'=>'required | integer',
                'ticket_id'=>'required | integer'
            ];

            $rules = array_merge($rules,$event_rules);
        }

        // if($data['type'] == 'diet_plan'){
        //     $diet_plan_rules = array(
        //         'offering_type'=>'required'
        //     );

        //     $rules = array_merge($rules,$diet_plan_rules);
        // }

        if(isset($data['manual_order']) && $data['manual_order']){

            if($data['type'] != 'memberships'){

                $data['validity'] = '1';
                $data['validity_type'] = 'day';
                $data['service_name'] = '-';
                $data['service_category_id'] = null;
            }

            if(!empty($data['ratecard_id'])){

                $ratecard = Ratecard::find((int)$data['ratecard_id']);

                if($ratecard){

                    $data['service_id'] = (int)$ratecard['service_id'];

                    if(isset($data['punching_order']) && $data['punching_order']){

                        $data['validity'] = $ratecard['validity'];
                        $data['validity_type'] = $ratecard['validity_type'];
                    }
                }

            }

            if(!empty($data['service_id'])){

                $service = Service::find((int)$data['service_id']);

                if($service){

                    $data['service_name'] = $service['name'];
                    $data['service_category_id'] = (int)$service['servicecategory_id'];
                }

            }

            $manual_order_rules = [
                'service_category_id'=>'required',
                'validity'=>'required',
                'validity_type'=>'required',
                'amount'=>'required',
                'service_name'=>'required'
            ];

            $rules = array_merge($rules,$manual_order_rules);
        }

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),$this->error_status);
        }

        /*if(isset($data['wallet']) && !$data['wallet']){
            $data['paymentmode_selected'] = 'paymentgateway';
        }*/
        

        if(isset($data['paymentmode_selected']) && $data['paymentmode_selected'] != ""){
            if(!empty($data['customer_quantity']) && $data['customer_quantity'] > 1 ){
                $data['paymentmode_selected'] = 'paymentgateway';
            }

            $data['part_payment'] = false;

            switch ($data['paymentmode_selected']) {
                case 'part_payment': $data['part_payment'] = true;break;
                case 'cod': $data['payment_mode'] = 'cod';break;
                case 'emi': $data['payment_mode'] = 'paymentgateway';break;
                case 'paymentgateway': $data['payment_mode'] = 'paymentgateway';break;
                case 'pay_at_vendor': $data['payment_mode'] = 'at the studio';break;
                case 'pay_later': $data['pay_later'] = true;break;
                default:break;
            }

        }

        // return $data;

        // if(!empty($data['pay_later'])){
        //     $data['customer_quantity'] = 1;
        // }

        if(!empty($_GET['lat'])){
            $data['lat'] = floatval($_GET['lat']);
        }


        if(!empty($_GET['lon'])){
            $data['lon'] = floatval($_GET['lon']);
        }

        $updating_part_payment = (isset($data['part_payment']) && $data['part_payment']) ? true : false;
        
        $updating_cod = (isset($data['payment_mode']) && $data['payment_mode'] == 'cod') ? true : false;

        if(!(isset($data['session_payment']) && $data['session_payment'])){
            if(isset($data['finder_id']) && $data['finder_id'] != ""){
                
                $checkFinderState = $this->utilities->checkFinderState($data['finder_id']);
    
                if($checkFinderState['status'] != 200){
                    return Response::json($checkFinderState, $this->error_status);
                }
            }
    
            if($this->vendor_token && !isset($data['order_id'])){
                
                if($this->utilities->checkFitternityCustomer($data['customer_email'], $data['customer_phone'])){
                    
                    $data['routed_order'] = '0';
                
                }else{
                 
                    $data['routed_order'] = '1';
                
                }
            
            }
            if(!empty($data['event_extra_customer'])){
                if(!empty($data['event_customers'])){
                    $data['event_customers'] = array_merge($data['event_customers'] ,$data['event_extra_customer']);
                }
                else{
                    $data['event_customers']  = $data['event_extra_customer'];
                }
            }
            if($data['type'] == "events" && isset($data['event_customers']) && count($data['event_customers']) > 0 ){
    
                $event_customers = $data['event_customers'];
    
                $event_customer_email = [];
                $event_customer_phone = [];
    
                foreach ($event_customers as $customer_data) {
    
                    if(in_array($customer_data["customer_email"],$event_customer_email)){
    
                        return Response::json(array('status' => 404,'message' => 'cannot enter same email id'),$this->error_status);
    
                    }else if(!empty($customer_data["customer_email"])){
    
                        $event_customer_email[] = strtolower($customer_data["customer_email"]);
                    }
    
                    if(in_array($customer_data["customer_phone"],$event_customer_phone)){
    
                        return Response::json(array('status' => 404,'message' => 'cannot enter same contact number'),$this->error_status);
    
                    }else if(!empty($customer_data["customer_phone"])){
    
                        $event_customer_phone[] = $customer_data["customer_phone"];
                    }
                }
                
            }
    
            if(isset($data['myreward_id']) && $data['type'] == "workout-session"){
    
                $validateMyReward = $this->validateMyReward($data['myreward_id']);
    
                if($validateMyReward['status'] != 200){
                    return Response::json($validateMyReward,$this->error_status);
                }
            }
    
            $customerDetail = $this->getCustomerDetail($data);
            
            if($customerDetail['status'] != 200){
                return Response::json($customerDetail,$this->error_status);
            }
    
            $data = array_merge($data,$customerDetail['data']); 
    
            if(isset($data['customers_list'])){
                foreach($data['customers_list'] as $key => $customer){
                    $data['customers_list'][$key]['customer_id'] = autoRegisterCustomer($customer);
                }
            }
            

            
            $payment_mode = isset($data['payment_mode']) ? $data['payment_mode'] : "";
            
            if(isset($data['ratecard_id'])){
                
                $ratecard_id = (int) $data['ratecard_id'];
                
                $ratecardDetail = $this->getRatecardDetail($data);
                
                if($ratecardDetail['status'] != 200){
                    return Response::json($ratecardDetail,$this->error_status);
                }
                
                $data = array_merge($data,$ratecardDetail['data']);
                
            }
                        
            if(!empty($data['third_party'])) {
                $acronym = $data['third_party_acronym'];
                // unset($data['third_party_acronym']);
                Log::info('$data["total_sessions_used"]: ', [$data['total_sessions_used']]);
            	if(isset($data['total_sessions_used']))
            	{
            		if((intval($data['total_sessions'])==-1) || (intval($data['total_sessions'])>intval($data['total_sessions_used'])))
            		{
	            		$data['total_sessions_used'] = intval($data['total_sessions_used']);
                        $data['total_sessions'] = intval($data['total_sessions']);
                        
                        $data['third_party_details'][$acronym]['third_party_used_sessions'] = $data['total_sessions_used'];
                        
                        $finder_id = (int) $data['finder_id'];
    
                        $finderDetail = $this->getFinderDetail($finder_id);
                
                        if($finderDetail['status'] != 200){
                            return Response::json($finderDetail,$this->error_status);
                        }

                        $orderfinderdetail = $finderDetail;
                        $data = array_merge($data,$orderfinderdetail['data']);
                        unset($orderfinderdetail["data"]["finder_flags"]);
                        
                        if(isset($data['service_id'])){
                            $service_id = (int) $data['service_id'];
                            
                            $serviceDetail = $this->getServiceDetail($service_id);
            
                            if($serviceDetail['status'] != 200){
                                return Response::json($serviceDetail,$this->error_status);
                            }
                            
                            $data = array_merge($data,$serviceDetail['data']);
                            if(isset($data['type']) && $data['type'] == 'workout-session' && $data['servicecategory_id'] == 65){
                                $data['service_name'] = $this->utilities->getGymServiceNamePPS();
                            }
                        }

                        // if(!empty($data['service_id'])){

                        //     $service = Service::find((int)$data['service_id']);
            
                        //     if($service){
            
                        //         $data['service_name'] = $service['name'];
                        //         $data['service_category_id'] = (int)$service['servicecategory_id'];
                        //     }
            
                        // }
                        $data['amount_customer'] = 0; // discussed with Utkarsh
	            		$order_id = Order::maxId() + 1;
	            		$order = new Order($data);
	            		$order->_id = $order_id;
	            		$order->save();
	            		
	            		$cust=Customer::where("_id",intval($data['logged_in_customer_id']))->first();
	            		$cust->total_sessions=intval($data['total_sessions']);
	            		$cust->total_sessions_used=intval($data['total_sessions_used']);

                        $newUser = false;
                        if(!empty($cust->third_party_details) && !empty($cust->third_party_details[$acronym]) && !empty($cust->third_party_details[$acronym]['third_party_new_user'])){
                            Log::info('cust->third_party_details:: ', [$cust->third_party_details[$acronym]['third_party_new_user']]);
                            $newUser = $cust->third_party_details[$acronym]['third_party_new_user'];
                        }
                        $data['third_party_details'][$acronym]['third_party_new_user'] = $newUser;
                        $cust->third_party_details=$data['third_party_details'];
                        $cust->third_party_acronym=$acronym;
                        $cust->save();
                        $createWorkoutRes = null;
                        if(!empty($acronym) && $acronym=='abg') {
                            $createWorkoutRes = $this->utilities->createWorkoutSession($order->_id, true);
                        
                            $booktrialData = json_decode($createWorkoutRes->getContent(), true);
                            if($booktrialData['status']==200){

                                $usedSessionsCount = (intval($data['total_sessions_used'])+1);

                                $updatedOrder = Order::findOrFail($order->_id);
                                $updatedOrder->update(["total_sessions_used" => $usedSessionsCount]);

                                $updatedCust=Customer::where("_id",intval($data['logged_in_customer_id']))->first();
                                $updatedCust->total_sessions_used = $usedSessionsCount;
                                $tpdtls = $updatedCust->third_party_details;
                                $tpdtls[$acronym]['third_party_used_sessions'] = $usedSessionsCount;
                                $updatedCust->third_party_details = $tpdtls;
                                $updatedCust->save();

                                $this->utilities->financeUpdate($order);

                                return Response::json([
                                    'status'=>200,
                                    "message"=>"Successfully Generated and maintained Workout session.",
                                    'booktrial_id' => $booktrialData['booktrialid']
                                ]);
                            }
                            else {
                                return Response::json(['status'=>$booktrialData['status'],"message"=>$booktrialData['message']]);
                            }
                        }
                        else {
                            $this->utilities->financeUpdate($order);

                            $result['firstname'] = trim(strtolower($data['customer_name']));
                            $result['lastname'] = "";
                            $result['phone'] = $data['customer_phone'];
                            $result['email'] = strtolower($data['customer_email']);
                            $result['orderid'] = $order['_id'];
                            $result['productinfo'] = strtolower($order['productinfo']);
                            $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($order['service_name']));
                            $result['finder_name'] = strtolower($order['finder_name']);
                            $result['type'] = $order['type'];
                            if(isset($order['type']) && ($order['type'] == 'workout-session')){
                                $result['session_payment'] = true;
                            }
                            if(isset($order['convinience_fee'])){
                                $result['convinience_fee_charged'] = true;
                                $result['convinience_fee'] = $order['convinience_fee'];
                            }
                            if(isset($order['cashback_detail']) && isset($order['cashback_detail']['amount_deducted_from_wallet'])){
                                $result['wallet_amount'] = $order['cashback_detail']['amount_deducted_from_wallet'];
                            }
                            if(!empty($order['ratecard_id'])){
                                $result['ratecard_id'] = $order['ratecard_id'];
                            }
                            if(isset($order['full_payment_wallet'])){
                                $result['full_payment_wallet'] = $order['full_payment_wallet'];
                            }

                            $resp   =   array(
                                'status' => 200,
                                'data' => $result,
                                'message' => "Tmp Order Generated Sucessfully",
                            );

                            return Response::json($resp);
                        }
            		}
            		else {
                        return Response::json(['status'=>400,"message"=>"Total sessions already crossed. "]);
                    }
            	}
            	else {
                    return Response::json(['status'=>400,"message"=>"Total sessions used not present. "]);
                }
            }
    
            if(isset($data['manual_order']) && $data['manual_order']){
    
                $manualOrderDetail = $this->getManualOrderDetail($data);
    
                if($manualOrderDetail['status'] != 200){
                    return Response::json($manualOrderDetail,$this->error_status);
                }
    
                $data = array_merge($data,$manualOrderDetail['data']);
            }
    
            if($payment_mode=='cod'){
    
                $data['payment_mode'] = 'cod';
    
                $data["secondary_payment_mode"] = "cod_membership";
                
            }        
    
            
    
            $finder_id = (int) $data['finder_id'];
    
            $finderDetail = $this->getFinderDetail($finder_id);

            if($finderDetail['status'] != 200){
                return Response::json($finderDetail,$this->error_status);
            }



            // $cash_pickup = (isset($finderDetail['data']['finder_flags']) && isset($finderDetail['data']['finder_flags']['cash_pickup'])) ? $finderDetail['data']['finder_flags']['cash_pickup'] : false;
            
            $orderfinderdetail = $finderDetail;
            $data = array_merge($data,$orderfinderdetail['data']);
            unset($orderfinderdetail["data"]["finder_flags"]);
    
            if(isset($data['service_id'])){
                $service_id = (int) $data['service_id'];
                
                $serviceDetail = $this->getServiceDetail($service_id);

                if($serviceDetail['status'] != 200){
                    return Response::json($serviceDetail,$this->error_status);
                }
                
                $data = array_merge($data,$serviceDetail['data']);
                if(isset($data['type']) && $data['type'] == 'workout-session' && $data['servicecategory_id'] == 65){
                    $data['service_name'] = $this->utilities->getGymServiceNamePPS();
                }
            }

        }else{
            $finderDetail['data']['finder_flags'] = [];
        }

        if(!empty($data['type']) && $data['type'] == 'workout-session' && !empty($data['trial']) && $data['trial'] == 'disable'){
            return Response::json(['status' => 404,'message' =>'Transaction not allowed'],$this->error_status);
        }
        
        if($data['type'] == 'workout-session'){
			$data['service_name'] = preg_replace('/membership/i', 'Workout', $data['service_name']);
        }
        
        if(isset($data['coupon_code']) && $this->utilities->isGroupId($data['coupon_code'])){
            
            if($this->utilities->validateGroupId(['group_id'=>$data['coupon_code'], 'customer_id'=>$data['customer_id']])){

                $data['group_id'] = $data['coupon_code'];

            } 
             
             unset($data['coupon_code']);
 
        }

        $corporateCustomer = Customer::where('_id', $data['customer_id'])->first();
        if(!empty($corporateCustomer['corporate_id'])) {
            $data['corporate_id'] = $corporateCustomer['corporate_id'];
        }

        $order = false;

        if(isset($data['order_id']) && $data['order_id'] != ""){

            $old_order_id = $order_id = $data['_id'] = intval($data['order_id']);
            $order = Order::find((int)$old_order_id);

            $data['repetition'] = 1;
            
            if($order){

                if(isset($order->repetition)){
                    $data['repetition'] = $order->repetition + 1;
                }

                if(isset($order->cashback)){
                    $order->unset('cashback');
                }

                if(isset($order->reward_ids)){
                    $order->unset('reward_ids');
                }

                $order->update();
            }

        }else{

            $order_id = $data['_id'] = $data['order_id'] = Order::maxId() + 1;
            $order = new Order();
            $order->_id = $order_id;
            $order->save();
        }
        
        $data['code'] = (string) random_numbers(5); //(string)$data['order_id'];

        if(isset($data['referal_trial_id'])){

            $data['referal_trial_id'] = (int) $data['referal_trial_id'];
        }
        
        if($data['type'] == 'events'){

            $data['payment_mode'] = "paymentgateway";
            
            $data['vertical_type'] = 'event';
            $data['membership_duration_type'] = 'event';
            
            $data['ticket_quantity'] = isset($data['ticket_quantity']) ? intval($data['ticket_quantity']) : 1;
            
            if(isset($data['ticket_id'])){
                
                $ticket = Ticket::where('_id', $data['ticket_id'])->first();

                if($ticket){

                    if($ticket['sold'] >= $ticket['quantity']){

                        $resp   =   array('status' => 400,'message' => "All Ticket Sold Out");

                        return Response::json($resp,$this->error_status);
                    }

                    !empty($ticket['minimum_no_of_ticket']) ? $data['ticket_quantity'] = $ticket['minimum_no_of_ticket']: null;

                    $data['amount_customer'] = $data['amount'] = $data['amount_finder'] = $data['ticket_quantity'] * $ticket->price;

                    // if($data['ticket_quantity'] == 4){
                    //     $data['combo_discount'] = 400;
                    //     $data['combo_discount_remark'] = "Buy 4 tickets, get 400 off";
                    //     $data['amount'] = $data['amount'] - $data['combo_discount'];
                    //     $data['amount_customer'] = $data['amount_customer'] - $data['combo_discount'];
                    //     $data['amount_finder'] = $data['amount_finder'] - $data['combo_discount'];
                    // }

                }else{

                    $resp   =   array('status' => 400,'message' => "Ticket not found");

                    return Response::json($resp,$this->error_status);
                }
                
            }else{

                $resp   =   array('status' => 400,'message' => "Ticket id not found");
                return Response::json($resp,$this->error_status);
                
            }

            

            $finder = Finder::where('_id', $data['finder_id'])->first(['title']);
            if($finder){
                $data['finder_name'] = $finder->title;
            }

            $event = DbEvent::where('_id', (int)$data['event_id'])->first(['name', 'slug','mfp','contact','venue', 'start_date', 'end_date']);

            if($event){
                $data['event_name'] = $event->name;
				$data['event_address'] = $event["contact"]["address"];
                $data['event_venue'] = $event["venue"];
                $data['event_start_date'] = $event['start_date'];
                $data['event_end_date'] = $event['end_date'];
                if(in_array($event['slug'],Config::get('app.my_fitness_party_slug')) || !empty($event['mfp'])){
                    $data['event_type'] = "TOI";
                    $data['qr_code'] = $this->utilities->encryptQr(['owner'=>'fitternity','order_id'=>$data['_id']]);
                }
            }

            if(isset($data['coupon_code']) && $data['coupon_code'] != "" && !(!empty($data['event_type']) && $data['event_type'] == 'TOI')){
                $data['coupon_code'] = strtolower($data['coupon_code']);
                $already_applied_coupon = Customer::where('_id',$data['customer_id'])->whereIn('applied_promotion_codes',[$data['coupon_code']])->count();
            
                if($already_applied_coupon>0){
                    return Response::json(array('status'=>400, 'message'=>'Coupon already applied'), $this->error_status);
                }
            }

            
        }

        if ($data['type'] == 'workout-session'){
            
            // $data['instant_payment_discount'] = round($data['amount_finder'] / 5) ;
            $data['instant_payment_discount'] = 0 ;

        }

        

        $data['amount_final'] = $data["amount_finder"];

        //********************************************************************************** DYANMIC PRICING START**************************************************************************************************
        if(
            (
                (
                    isset($finder->flags->disable_dynamic_pricing) 
                    && empty($finder->flags->disable_dynamic_pricing)
                ) 
                || 
                (
                    isset($data['service_flags']['disable_dynamic_pricing']) 
                    && 
                    empty($data['service_flags']['disable_dynamic_pricing'])
                )
            ) 
            && 
            (
                (
                    isset($_GET['device_type']) 
                    && 
                    isset($_GET['app_version'])
                    && 
                    in_array($_GET['device_type'], ['android', 'ios'])
                    && 
                    $_GET['app_version'] >= '5'
                ) 
                || 
                isset($data['qrcodepayment']) || 
                (empty($_GET['device_type'])) || 
                $_GET['device_type'] == 'website')
            &&
            $data["amount_finder"] != 73
        ){
            if($data['type'] == 'workout-session')
            {
             try {
                 Log::info("dynamic price");
             (isset($data['start_time'])&&isset($data['start_date'])&&isset($data['service_id'])&&isset($data['end_time']))?
             $am_calc=$this->utilities->getWsSlotPrice($data['start_time'],$data['end_time'],$data['service_id'],$data['start_date']):"";
             if(isset($am_calc['peak'])){
                $data['amount']  = $am_calc['peak'];
                $data['peak'] = true;
             }else if(isset($am_calc['non_peak'])){
                $data['amount']  = $am_calc['non_peak'];
                $data['non_peak'] = true;
                $data['non_peak_discount']  = $am_calc['non_peak_discount'];
    
             }
            //  (isset($am_calc))?$data['amount']=$am_calc:"";
             
             } catch (Exception $e) {Log::error(" Error :: ".print_r($e,true));}
             } 
        }
            //********************************************************************************** DYANMIC PRICING END****************************************************************************************************
        
        if(!$updating_part_payment && !isset($data['myreward_id']) && (!(isset($data['pay_later']) && $data['pay_later']) || !(isset($data['wallet']) && $data['wallet']))) {
            
            if(!empty($order['duration'])){

                $GLOBALS['order_duration'] = $order['duration'];

            }

            if(!empty($data['customer_source']) && $data['customer_source'] == 'admin'){
            }else{
                $cashbackRewardWallet =$this->getCashbackRewardWallet($data,$order);
            
                // Log::info("cashbackRewardWallet",$cashbackRewardWallet);
                
                if($cashbackRewardWallet['status'] != 200){
                    return Response::json($cashbackRewardWallet,$this->error_status);
                }
                
                $data = array_merge($data,$cashbackRewardWallet['data']);
            }
            
        }

        if(!empty($data['donation_amount']) && is_numeric($data['donation_amount'])){
            
            $data['amount_final'] = $data['amount'] = $data['amount'] + $data['donation_amount'];
            $data['amount_customer'] = $data['amount_customer'] + $data['donation_amount'];
            $data['amount_finder'] = $data['amount_finder'] + $data['donation_amount'];
        
        }
        
        $txnid = "";
        $successurl = "";
        $mobilehash = "";

        if(in_array($data['customer_source'],['android','ios','kiosk'])){

            $txnid = "MFIT".$data['_id'];

            if(isset($old_order_id)){
                $txnid = "MFIT".$data['_id']."-R".$data['repetition'];
            }

            $successurl = $data['customer_source'] == "ios" ? Config::get('app.website')."/paymentsuccessios" : Config::get('app.website')."/paymentsuccessandroid";

        }else{

            $txnid = "FIT".$data['_id'];

            if(isset($old_order_id)){
                $txnid = "FIT".$data['_id']."-R".$data['repetition'];
            }

            $website_url = Config::get('app.website');

            if(!empty($order['multifit']) || !empty($data['multifit']) || (!empty($_SERVER['HTTP_HOST']) && strtolower($_SERVER['HTTP_HOST']) == 'multifitgym.com')){
                $data['multifit'] = true;
                $website_url = Config::get('app.multifit_website');
            
            }

            $successurl = $data['type'] == "memberships" ? $website_url."/paymentsuccess" : $website_url."/paymentsuccesstrial";
        }
        

        if($data['type'] == 'events'){
            $successurl = Config::get('app.website')."/eventpaymentsuccess";
        }

        $data['txnid'] = $txnid;

        Log::info($finderDetail["data"]);
        $tmp_finder_flags = (array) $finderDetail['data']['finder_flags'];
        $part_payment = (!empty($tmp_finder_flags) && isset($finderDetail['data']['finder_flags']['part_payment'])) ? $finderDetail['data']['finder_flags']['part_payment'] : false;
        $cash_pickup = (!empty($tmp_finder_flags) && isset($finderDetail['data']['finder_flags']['cash_pickup'])) ? $finderDetail['data']['finder_flags']['cash_pickup'] : false;
        
        if(!$updating_part_payment && $part_payment && $data["amount_finder"] >= 3000){

            $part_payment_data = $data;

            $convinience_fee = $part_payment_data['convinience_fee'] = 0;

            $part_payment_data["amount"] = 0;


            if($finderDetail["data"]["finder_flags"]["part_payment"]){
                if(!empty($data['customer_source']) && $data['customer_source'] == 'admin'){
                    $convinience_fee = 0;
                    $part_payment_data['convinience_fee'] = $convinience_fee;
                }else{
                    if($this->utilities->isConvinienceFeeApplicable($data)){

                        $convinience_fee_percent = Config::get('app.convinience_fee');
    
                        $convinience_fee = round($part_payment_data['amount_finder']*$convinience_fee_percent/100);
    
                        $convinience_fee = $convinience_fee <= 199 ? $convinience_fee : 199;
                        
                        $part_payment_data['convinience_fee'] = $convinience_fee;
    
                    }
                }
                
                $part_payment_amount = ceil(($data["amount_finder"] * (20 / 100)));

                $part_payment_data["amount"] = $convinience_fee + $part_payment_amount;

                if(isset($data['cashback_detail']) && isset($data['cashback_detail']['amount_deducted_from_wallet'])){

                    $part_payment_data["amount"] = 0;

                    if(($convinience_fee + $part_payment_amount) > $data['cashback_detail']['amount_deducted_from_wallet']){
                        $part_payment_data["amount"] = ($convinience_fee + $part_payment_amount) - $data['cashback_detail']['amount_deducted_from_wallet'];
                    }

                }

                $part_payment_hash ="";
                
                if($part_payment_data["amount"] > 0 ){
                    $part_payment_hash = isset($data['part_payment_calculation']['hash']) ? $data['part_payment_calculation']['hash'] :  getHash($part_payment_data)['payment_hash'];
                }else{
                    $part_payment_data["amount"] = 0;
                }
            }

            $data["part_payment_calculation"] = array(
                "amount" => (int)($part_payment_data["amount"]),
                "hash" => $part_payment_hash,
                "convinience_fee"=>$part_payment_data['convinience_fee'],
                "full_wallet_payment" => $part_payment_data["amount"] == 0 ? true : false,
                "part_payment_amount"=>$part_payment_amount,
                "part_payment_and_convinience_fee_amount"=>$part_payment_amount + $convinience_fee
            );

        }


        if(empty($data['convinience_fee'])){
            $data['convinience_fee'] = 0;
            
            if(!empty($data['customer_source']) && $data['customer_source'] == 'admin'){
                $data['convinience_fee'] = 0;
            }else{
                if($this->utilities->isConvinienceFeeApplicable($data)){
                
                    $convinience_fee_percent = Config::get('app.convinience_fee');
        
                    $convinience_fee = round($data['amount_finder']*$convinience_fee_percent/100);
        
                    $convinience_fee = $convinience_fee <= 199 ? $convinience_fee : 199;
        
                    $data['convinience_fee'] = $convinience_fee;
        
                    if(!empty($data['customer_quantity'])){
                        $data['convinience_fee'] = $data['convinience_fee'] * $data['customer_quantity'];
                    }
        
                }
            }
        }

        if(isset($data['pay_later']) && $data['pay_later'] && isset($data['wallet']) && $data['wallet']){
            $data['amount_final'] = $data['amount'] = $data['amount'] + $data['convinience_fee'];
            $data['amount_customer'] = $data['amount'];
            $data['coupon_discount_amount'] = 0;
            unset($data['instant_payment_discount']);
        
        }

        $data['base_amount'] = $order['amount_customer'] - $data['convinience_fee'] ;
        Log::info("data before hash");
        Log::info($data);
        $hash = getHash($data);
        $data = array_merge($data,$hash);

        $data = $this->unsetData($data);

        if(isset($data['service_id'])){

            $data['service_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']);
        
        }
        

        $data['payment_link'] = Config::get('app.website')."/paymentlink/".$data['order_id']; //$this->utilities->getShortenUrl(Config::get('app.website')."/paymentlink/".$data['order_id']);

        if(in_array($data['type'],$this->membership_array) && isset($data['ratecard_id']) && $data['ratecard_id'] != ""){
            $data['payment_link'] = Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']."/".$data['ratecard_id']."/".$data['order_id']; //$this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$data['finder_slug']."/".$data['service_id']."/".$data['ratecard_id']."/".$data['order_id']);
        }

        $data['vendor_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/".$data['finder_slug']);

        $data['profile_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);

        $data['workout_article_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/article/complete-guide-to-help-you-prepare-for-the-first-week-of-your-workout");
        $data['download_app_link'] = Config::get('app.download_app_link');
        $data['diet_plan_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/diet-plan");

        if(in_array($data['type'],$this->membership_array) && isset($data['start_date'])){

            $data["auto_followup_date"] = date('Y-m-d H:i:s', strtotime("+31 days",strtotime($data['start_date'])));
            $data["followup_status"] = "abandon_cart";
        }


        $addUpdateDevice = $this->utilities->addUpdateDevice($data['customer_id']);

        foreach ($addUpdateDevice as $header_key => $header_value) {

            if($header_key != ""){
               $data[$header_key]  = $header_value;
            }
            
        }


        if($this->utilities->checkCorporateLogin()){
            $data['full_payment_wallet'] = true;
        }

        if(isset($data['myreward_id']) && $data['type'] == "workout-session"){
            $data['amount'] = 0;
            $data['full_payment_wallet'] = true;
        }

        if($data['amount'] == 0){
            $data['full_payment_wallet'] = true;
        }
        
        $data['status'] = $status;
        Log::info("after status !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!", [$data['status']]);

        if(!empty(Request::header('Origin')) && strpos( Request::header('Origin'), "www.jgsfitness.com" ) !== false){
            $data['jgs'] = true;

            if(!empty($data['multifit'])){
                unset($data['multifit']);
            }
        }

        if(isset($data['paymentmode_selected']) && $data['paymentmode_selected'] == 'pay_at_vendor'){

            $data['payment_mode'] = 'at the studio';
            $data["secondary_payment_mode"] = "at_vendor_post";
        }

        if(!empty($data['customer_source']) && $data['customer_source'] == 'admin'){
            $data['payment_mode'] = $payment_mode_admin;
            $data['secondary_payment_mode'] = $secondary_payment_mode_admin;
        }

        $is_tab_active = isTabActive($data['finder_id']);

        if($is_tab_active){
            $data['is_tab_active'] = true;
        }

        if($data['type'] == 'workout-session' && isset($_GET['device_type']) && isset($_GET['app_version']) && in_array($_GET['device_type'], ['android', 'ios']) && $_GET['app_version'] > '4.4.3' ){
            $data['pps_new'] = true;
        }

        if(!empty($ratecardDetail) && !empty($finderDetail) && !empty($data['duration_day'])){
            $studioExtValidity = in_array($data['duration_day'], [30, 90]); 

            $numOfDays = (in_array($ratecardDetail['data']['validity_type'], ['month', 'months']))?$ratecardDetail['data']['validity']*30:$ratecardDetail['data']['validity'];
            
            $numOfDays = (in_array($ratecardDetail['data']['validity_type'], ['year', 'years']))?$ratecardDetail['data']['validity']*360:$numOfDays;

            $numOfDaysExt = ($numOfDays==30)?15:(($numOfDays>=60)?30:0);

            $ext = ' +'.$numOfDaysExt.' days';

            if(!empty($ratecardDetail['data']['duration'])){
                Log::info('$ratecardDetail[data][duration]: ', [$ratecardDetail['data']['duration']]);
            }
            if(!empty($finderDetail['data']['finder_flags'])){
                Log::info('$finderDetail[data][finderFlags]: ', [$finderDetail['data']['finder_flags']]);
            }

            if((!empty($data['servicecategory_id']) && !in_array($data['servicecategory_id'], Config::get('app.non_flexi_service_cat', [111, 65, 5]))) && $data['type']=='memberships' && !empty($data['batch']) && (count($data['batch'])>0) && $studioExtValidity && !empty($ratecardDetail['data']['duration']) && $ratecardDetail['data']['duration']>0 && (empty($finderDetail['data']['finder_flags']['trial']) || $finderDetail['data']['finder_flags']['trial']=='auto')){
                $workoutSessionOrExtendedRatecard = Ratecard::where('direct_payment_enable', '1')->whereIn('type', ['workout session', 'extended validity'])->where('service_id', $data['service_id'])->get()->toArray();
                $workoutSessionRatecard = array_filter($workoutSessionOrExtendedRatecard, function($x){
                    return $x['type'] == 'workout session';
                });
                $extendedValidityRatecards = array_filter($workoutSessionOrExtendedRatecard, function($x){
                    return $x['type'] == 'extended validity';
                });

                if(!empty($workoutSessionRatecard) && empty($extendedValidityRatecards)){
                    $data['studio_extended_validity'] = true;
                    $data['studio_sessions'] = [
                        'total' => $ratecardDetail['data']['duration'],
                        'cancelled' => 0,
                        'total_cancel_allowed' => intval($ratecardDetail['data']['duration']*0.25)
                    ];
                    $data['studio_membership_duration'] = [
                        'num_of_days' => $numOfDays,
                        'num_of_days_extended' => $numOfDaysExt,
                        'start_date' => new MongoDate(strtotime($data['start_date'])),
                        'end_date' => new MongoDate(strtotime($data['end_date'])),
                        'end_date_extended' => new MongoDate(strtotime($data['end_date'].$ext))
                    ];
                }
                else {
                    Log::info('workout session ratecard does not exist for studio extended validity: ', [$data['_id']]);
                }
            }
            else {
                Log::info('Not bookings as studio extended validity: ', [$data['_id']]);
            }
        }
        
        $duration = !empty($data['duration_day']) ? $data['duration_day'] : (!empty($data['order_duration_day']) ? $data['order_duration_day'] : 0);
        $duration = $duration > 180 ? 360 : $duration;
        if(!empty($data['type']) && !empty($data['finder_id']) && in_array($data['type'], ['memberships']) && in_array($duration, [180, 360])){
            Finder::$withoutAppends = true;
            $finder = Finder::find($data['finder_id'], ['brand_id', 'city_id']);
            
            if(!empty($finder['brand_id']) && $finder['brand_id'] == 40 && $duration == 180){
                $duration = 0;
            }

            if(!empty($finder['brand_id']) && !empty($finder['city_id']) && in_array($finder['brand_id'], Config::get('app.brand_loyalty')) && !in_array($finder['_id'], Config::get('app.brand_finder_without_loyalty')) && in_array($duration, [180, 360])){
                $data['finder_flags']['brand_loyalty'] = $finder['brand_id'];
            }
        }
        
        if(isset($old_order_id)){
            
            if($order){
                
            
                $order->update($data); 
            }else{
                $order = new Order($data);
                $order->_id = $order_id;
                $order->save();
                // $redisid = Queue::connection('redis')->push('TransactionController@updateRatecardSlots', array('order_id'=>$order_id, 'delay'=>\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(10)),Config::get('app.queue'));
            }

        }else{

              // $order = new Order($data);
            // $order->_id = $order_id;
            $order->update($data);
            // $redisid = Queue::connection('redis')->push('TransactionController@updateRatecardSlots', array('order_id'=>$order_id, 'delay'=>\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(10)),Config::get('app.queue'));
        }


        if(isset($data['payment_mode']) && $data['payment_mode'] == 'cod'){

            $group_id = isset($data['group_id']) ? $data['group_id'] : null;
            $order->group_id = $data['group_id'] = $this->utilities->addToGroup(['customer_id'=>$data['customer_id'], 'group_id'=>$group_id, 'order_id'=>$order['_id']]);
            $this->customermailer->orderUpdateCOD($order->toArray());
            $this->customersms->orderUpdateCOD($order->toArray());

            $order->cod_otp = $this->utilities->generateRandomString();

            $order->update();
        }


        if(isset($data['pay_later']) && $data['pay_later'] && isset($data['wallet']) && $data['wallet']){

            $order->pay_later = true;
            
            // $order->status = "4";
            
            $order->update();
            
            // $this->utilities->createWorkoutSession($order['_id']);

            $result['payment_done'] = false;
            
            $data['full_payment_wallet'] = true;
        }
        
        $this->utilities->financeUpdate($order);
        
        if(in_array($data['customer_source'],['android','ios','kiosk'])){
            $mobilehash = $data['payment_related_details_for_mobile_sdk_hash'];
        }

        $result['firstname'] = trim(strtolower($data['customer_name']));
        $result['lastname'] = "";
        $result['phone'] = $data['customer_phone'];
        $result['email'] = strtolower($data['customer_email']);
        $result['orderid'] = $data['_id'];
        $result['txnid'] = $txnid;
        $result['amount'] = $data['amount'];
        $result['amount_final'] = $data['amount_final'];
        $result['productinfo'] = strtolower($data['productinfo']);
        $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($data['service_name']));
        $result['successurl'] = $successurl;
        $result['hash'] = $data['payment_hash'];
        $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
        $result['finder_name'] = strtolower($data['finder_name']);
        $result['type'] = $data['type'];
        
        if(isset($data['type']) && ($data['type'] == 'workout-session')){
            $result['session_payment'] = true;
        }
        if(isset($data['convinience_fee'])){
            $result['convinience_fee_charged'] = true;
            $result['convinience_fee'] = $data['convinience_fee'];
        }
        if(isset($data['cashback_detail']) && isset($data['cashback_detail']['amount_deducted_from_wallet'])){
            $result['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
        }
        if(!empty($data['ratecard_id'])){
            $result['ratecard_id'] = $data['ratecard_id'];
        }
        /*if(isset($data["part_payment_calculation"])){
            $result['part_payment_calculation'] = $data["part_payment_calculation"];
        }*/
        
        

        if(isset($data['full_payment_wallet'])){
            $result['full_payment_wallet'] = $data['full_payment_wallet'];
        }


        if($data['type'] == "events" && isset($data['event_customers']) && count($data['event_customers']) > 0 ){
            $auto_register_input = array('event_customers'=>$data['event_customers']);
            if(!empty($data['event_type']) && $data['event_type']=='TOI'){
                $auto_register_input['event_type'] =  $data['event_type'];
            }

            Queue::connection('redis')->push('TransactionController@autoRegisterCustomer', $auto_register_input, Config::get('app.queue'));
        }

        if(in_array($data['type'],$this->membership_array)){
            $redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),Config::get('app.queue'));
            $order->update(array('redis_id'=>$redisid));
        }

        // $cash_pickup_applicable = ($cash_pickup && isset($data['amount_final']) && $data['amount_final'] >= 3000) ? true : false;
        $cash_pickup_applicable = (isset($data['amount_final']) && $data['amount_final'] >= 2500) ? true : false;
        

        // $emi_applicable = $this->utilities->displayEmi(array('amount_final'=>$data['amount_final']));

        $emi_applicable = (isset($data['amount_final']) && $data['amount_final'] >= 5000) ? true : false;

        $part_payment_applicable = false; //(!$updating_part_payment && $part_payment && $data["amount_finder"] >= 3000) ? true : false;

        $pay_at_vendor_applicable = true;

        $pay_later = false;
        
        if($data['type'] == 'workout-session' && isset($_GET['device_type']) && isset($_GET['app_version']) && in_array($_GET['device_type'], ['android', 'ios']) && $_GET['app_version'] > '4.4.3' && !(isset($data['session_payment']) && $data['session_payment'])){
            $pay_later = false;
            $data['pps_new'] = true;
        }

        // if(!empty($data['customer_quantity']) && $data['customer_quantity'] > 1){
        //     $pay_later = false;
        // }
        
        $resp   =   array(
            'status' => 200,
            'data' => $result,
            'message' => "Tmp Order Generated Sucessfully",
            'part_payment' => $part_payment_applicable,
            'cash_pickup' => $cash_pickup_applicable,
            'emi'=>$emi_applicable,
            'pay_at_vendor'=>$pay_at_vendor_applicable,
            'pay_later'=>$pay_later
        );

        if(!empty($data['ratecard_pay_at_vendor'])){
           $resp['ratecard_pay_at_vendor'] = true;
        }
        if(!empty($data['qrcodepayment'])){
           $resp['qrcodepayment'] = true;
        }
        
        
        
            if(!empty($order['amount_final'])){
                $resp['data']["coupon_details"] = [
                    "title" => "Apply Coupon Code",
                    "description" => "",
                    "applied" => false,
                    "remove_title" => "",
                    "remove_msg" => ""
                ];
            }
            
            
            if(!empty($data['coupon_code']) && (!empty($data['coupon_discount_amount']) || !empty($data['coupon_flags']['cashback_100_per']) || !empty($data['coupon_flags']['vk_bag_and_box_reward']))){
                $resp['data']["coupon_details"] = [];
                $resp['data']['coupon_details']['title'] = strtoupper($data['coupon_code']);
                $resp['data']['coupon_details']['remove_title'] =  strtoupper($data['coupon_code'])." applied";
                $resp['data']['coupon_details']['applied'] =  true;
                if(isset($data['coupon_description'])){
                    $resp['data']['coupon_details']['description'] = $data['coupon_description'];
                }
            }
    
            if(empty($data['session_payment']) && empty($data['session_pack_discount']) && empty($order['ratecard_flags']['hide_mfp_quantity'])){
                
                if(in_array($order['type'], ['booktrials', 'workout-session'])){
                    $resp['data']["quantity_details"] = [
                        "field" => "No of People",
                        "description" => "👤 ".(!empty($order['customer_quantity']) ? $order['customer_quantity'] : 1),
                        'max'=>5,
                        'selected_quantity'=>(!empty($order['customer_quantity']) ? $order['customer_quantity'] : 1)
                    ];
                }
        
                // if(!empty($order['customer_quantity'])){
    
                    // $resp['data']["pt_details"] = [
                    //     "title" => "Add on",
                    //     "description" => "Personal Training",
                    //     "cost"=>"Rs.300",
                    //     "applied" => !empty($data['pt_applied']) ? $data['pt_applied'] : false
                    // ];
                // }
            }
        

        // $resp['payment_offers'] = [
        //     'amazon_pay'=>'25% instant cashback'
        // ];

        // if(isset($_GET['device_type']) && in_array($_GET['device_type'], ['android', 'ios'])){
            
            $resp['data']['order_details'] = $this->getBookingDetails($order->toArray());

            $payment_mode_type_array = ['paymentgateway'];

            if($emi_applicable && isset($order->amount_final) && $order->amount_final){

                $payment_mode_type_array[] = 'emi';
            }

            if(!$this->vendor_token){
        
                if($cash_pickup_applicable){

                    $payment_mode_type_array[] = 'cod';
                }

                if($part_payment_applicable){

                    $payment_mode_type_array[] = 'part_payment';
                }
            }

            if($this->vendor_token){
        
                if($pay_at_vendor_applicable){

                    $payment_mode_type_array[] = 'pay_at_vendor';
                }
            }
            if($pay_later){
                
                if($data['type'] == 'workout-session'){
                    $payment_mode_type_array[] = 'pay_later';
                }
            }
            $payment_details = [];

            foreach ($payment_mode_type_array as $payment_mode_type) {

                $payment_details[$payment_mode_type] = $this->getPaymentDetails($order->toArray(),$payment_mode_type);
    
            }
            
            $resp['data']['payment_details'] = $payment_details;

            $resp['data']['payment_modes'] = [];

            if(isset($order->amount_final) && $order->amount_final ){
                $resp['data']['payment_modes'] = $this->getPaymentModes($resp, $order->toArray());
            }
        // }


        $otp_flag = true;

        if($this->vendor_token && !empty($data['manual_order']) && $data['manual_order'] && in_array($data['type'], ['booktrials', 'workout-session'])){

            $otp_flag = false;
            
            $order->manual_order_punched = true;
            $order->update();

            $this->utilities->createWorkoutSession($order->_id);
            
        }

        if($data['payment_mode'] == 'at the studio' && isset($data['wallet']) && $data['wallet'] && $otp_flag){

            $data_otp = array_only($data,['finder_id','order_id','service_id','ratecard_id','payment_mode','finder_vcc_mobile','finder_vcc_email','customer_name','service_name','service_duration','finder_name', 'customer_source','amount_finder','amount','finder_location','customer_email','customer_phone','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon']);

            $data_otp['action'] = "vendor_otp";

            if(!empty($data['multifit'])){
                $data_otp['multifit'] = $data['multifit'];
            }

            if(isset($data['assisted_by']) && isset($data['assisted_by']['mobile']) && $data['assisted_by']['mobile'] != ""){

                $finder_vcc_mobile = explode(",",$data['finder_vcc_mobile']);

                $finder_vcc_mobile[] = $data['assisted_by']['mobile'];

                $data_otp['finder_vcc_mobile'] = implode(",", $finder_vcc_mobile);
            }

            if(isset($data['assisted_by']) && isset($data['assisted_by']['email']) && $data['assisted_by']['email'] != ""){

                $finder_vcc_email = explode(",",$data['finder_vcc_email']);

                $finder_vcc_email[] = $data['assisted_by']['email'];

                $data_otp['finder_vcc_email'] = implode(",", $finder_vcc_email);
            }

            $addTemp_flag  = true;

            if(isset($order['otp_data'])){

                $old_order = $order->toArray();

                Log::info("         =============================== older order:  ", [$old_order]);

                $otp_data = $old_order['otp_data'];

                if(isset($otp_data['created_at']) && ((time() - $otp_data['created_at']) / 60) < 3){

                    $addTemp_flag = false;
                }
                $temp_id = $order['otp_data']['temp_id'];
                $temp = Temp::find($temp_id);

                if(!$temp){

                    return Response::json(['status' => 400, "message" => "Internal Error"],$this->error_status);
                }

                if($temp->verified == "Y"){

                    return Response::json(array('status' => 400,'message' => 'Already Verified'),$this->error_status);
                }

                $otp_data['otp'] = $temp['otp'];

            }

            if($addTemp_flag){

                $addTemp = addTemp($data_otp);

                $otp_data = [
                    'finder_vcc_mobile'=>$data_otp['finder_vcc_mobile'],
                    'finder_vcc_email'=>$data_otp['finder_vcc_email'],
                    'payment_mode'=>$data_otp['payment_mode'],
                    'temp_id'=>$addTemp['_id'],
                    'otp'=>$addTemp['otp'],
                    'created_at'=>time()
                ];

                $order->update(['otp_data'=>$otp_data]);

                $otp_data['otp'] = $addTemp['otp'];
            }


            $otp_data = array_merge($data_otp,$otp_data);

            /*$otp_data['customer_name'] = $data['customer_name'];
            $otp_data['service_name'] = $data['service_name'];
            $otp_data['service_duration'] = $data['service_duration'];
            $otp_data['finder_name'] = $data['finder_name'];
            $otp_data['customer_source'] = $data['customer_source'];
            $otp_data['amount'] = $data['amount'];
            $otp_data['amount_finder'] = $data['amount_finder'];
            $otp_data['finder_location'] = $data['finder_location'];*/

            $this->findersms->genericOtp($otp_data);
            $this->findermailer->genericOtp($otp_data);

            if(!$this->vendor_token){

                $this->customermailer->atVendorOrderCaputure($otp_data);
                $this->customersms->atVendorOrderCaputure($otp_data);
            }

            $resp['vendor_otp'] = $otp_data['otp'];

            $resp['data']['verify_otp_url'] = Config::get('app.url')."/kiosk/vendor/verifyotp";
            $resp['data']['resend_otp_url'] = Config::get('app.url')."/temp/regenerateotp/".$otp_data['temp_id'];
            if($data["customer_source"] == "website"){
                $resp['data']['show_success'] = true;
            }

        }

        $resp['data']['assisted_by'] = $this->utilities->getVendorTrainer($data['finder_id']);

        $resp['data']['assisted_by_image'] = "https://b.fitn.in/global/tabapp-homescreen/freetrail-summary/trainer.png";

        $resp['data']['vendor_otp_message'] = "Enter the confirmation code provided by your gym/studio to activate your membership";

        if(in_array($data['type'],['booktrials','workout-session'])){
            $resp['data']['vendor_otp_message'] = " Enter the confirmation code provided by your gym/studio to activate your session";
        }

        if(isset($data['punching_order']) && $data['punching_order']){
            $resp['data']['vendor_otp_message'] = "Enter the verification code to confirm the membership.";
        }

        if(isset($data['manual_order']) && $data['manual_order'] && $data['type'] != 'memberships'){
            $resp['data']['payment_modes'] = [];
        }
        
        // $scheduleBookingsRedisId = Queue::connection('redis')->push('SchedulebooktrialsController@scheduleStudioBookings', array('order_id'=>$order_id),Config::get('app.queue'));

        // if(!empty($data['studio_extended_validity']) && $data['studio_extended_validity']) {
        //     $this->utilities->scheduleStudioBookings(null, $order_id);
        // }
        
        if(!empty($data['pass_booking']) && $data['pass_booking']){
            unset($resp['data']["quantity_details"]);
            $resp['data']['payment_modes']= [];
            unset($resp['data']["payment_details"]);
            unset($resp['data']["coupon_details"]);

            $passBookingDetails = $this->getPassDetails($data);

            if(!empty($passBookingDetails['onepass_details'])){
                $resp['data']['onepass_details'] =  $passBookingDetails['onepass_details'];
            }

            if(!empty($passBookingDetails['easy_cancellation'])){
                $resp['data']['easy_cancellation'] =  $passBookingDetails['easy_cancellation'];
            }
        }

        //apply fitternity plus
        Log::info("fitternity plus started");
        $amount_customer_int = !empty($data['amount_customer']) ? (int)$data['amount_customer'] : 0;
        $convinience_fee_int = !empty($data['convinience_fee']) ? (int)$data['convinience_fee'] : 0;
        $base_amount_int = $amount_customer_int - $convinience_fee_int;
        Log::info("amount_customer_int ::", [$amount_customer_int]);
        Log::info("convinience_fee_int ::", [$convinience_fee_int]);
        Log::info("base_amount_int ::", [$base_amount_int]);
        if(!empty($base_amount_int) && !empty($data['type']) && ($data['type'] == 'memberships' || $data['type'] == 'membership') ) {
            Log::info("fitternity plus apply");
            $plus_arg_data = array('base_amount' => $base_amount_int, 'customer_id' => $data['customer_id']);
            $plus_details = $this->plusService->applyPlus($plus_arg_data);
            if(!empty($plus_details)){
                $order->update(["plus" => $plus_details]);
            }
        }
        
        Log::info("capture response");
        Log::info($resp);
        return $resp;

    }
    

    public function productCapture($data =null)
    {
    	
    	
    	$data = $data ? $data : Input::json()->all();
    	Log::info('--------------  PRODUCT CAPTURE ---------------',$data);
    	
    	foreach ($data as $key => $value) {
    		(is_string($value))?$data[$key] = trim($value):"";
    	}
    	
    	$rules = array(
    			'customer_source'=>'required',
    			/* 'city_id'=>'required', */
    			'customer_name'=>'required',
    			'customer_email'=>'required|email',
    			'customer_phone'=>'required',
    			'type'=>'required'
    	);
    	
    	$validator = Validator::make($data,$rules);
    	$failedCheck=$this->checkProductCaptureFailureStatus($data);
    	
    	if ($validator->fails())
    		return Response::json(['status' => 0,'message' => error_message($validator->errors())]);
    		if(!$failedCheck['status'])
    			return Response::json($failedCheck);
    			
    			

    			// ALL FINDER DETAIL TO BE ENTERED HERE IN THIS BLOCK ,  SOME VARIABLE MIGHT BE UPDATED LATER IN THE CODE.
    			if($this->vendor_token)
    			{
    				$data['customer']['customer_source'] = 'kiosk';
    				$decodeKioskVendorToken = decodeKioskVendorToken();
    				$vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);
    				
    				if(empty($data['deliver_to_vendor']))
    				{
    					$tmp_cust_addr='customer_address';
    					if(empty($data[$tmp_cust_addr]))
    						return ['status' => 0,'message' => $tmp_cust_addr." required if not delvered to vendor"];
    				}    				
    				$finderDetail = $this->getFinderDetail((int)$vendor['_id']);
    				if($finderDetail['status'] != 200)
    					return Response::json($finderDetail,$this->error_status);
    					$data['finder']=$finderDetail['data'];
    					$finderDetail['data']['finder']['finder_flags'] = [];
    					$is_tab_active = isTabActive($data['finder']['finder_id']);
    					($is_tab_active)?$data['finder']['is_tab_active'] = true:"";
    					$pay_at_vendor_applicable = true;
    					$result['finder_name'] = strtolower($data['finder']['finder_name']);
    			}
    			
    			$customerDetail = $this->getCustomerDetail($data);
    			if($customerDetail['status'] != 200)
    				return Response::json(['status'=>0,"message"=>"Failed to get customer detail."]);
    				else $data['customer']=$customerDetail['data'];
    				
    				unset($data['customer']['cart_data']);
    				// Cart data added based on cart id or token.
    				if($data['customer']['customer_source']=='kiosk')
    				{
    					$addToCartResponse=$this->utilities->addProductsToCart($data['cart_data'],null,false,false);	
    					if($addToCartResponse['status'])
    						$data['cart_data']=$addToCartResponse['response']['data'];
    						else return Response::json($addToCartResponse);
    				}
    				
    				// payment mode
    				if(isset($data['paymentmode_selected']) && $data['paymentmode_selected'] != ""){
    					
    					switch ($data['paymentmode_selected']) {
    						case 'cod': $data['payment_mode'] = 'cod';break;
    						case 'emi': $data['payment_mode'] = 'paymentgateway';break;
    						case 'paymentgateway': $data['payment_mode'] = 'paymentgateway';break;
    						case 'pay_at_vendor': $data['payment_mode'] = 'at the studio';break;
    						default:break;
    					}
    				}
    				
    				// cod
    				$updating_cod = (isset($data['payment_mode']) && $data['payment_mode'] == 'cod') ? true : false;
    				$payment_mode = isset($data['payment_mode']) ? $data['payment_mode'] : "";
    				if($payment_mode=='cod')$data['payment_mode'] = 'cod';
    				
    				// get already generated order start
    				
    				if(!empty($data['order_id']))
    				{
    					$mkOrder=Order::where('_id',intval($data['order_id']))->first();
    					if(!empty($mkOrder))
    					{
    						if(!empty($data['remove_coupon']))
    						{
    							$mkOrder->unset("amount_calculated.coupon_discount_amount");
    							unset($data['remove_coupon']);
    						}
    							
    					}
    				}
    				else unset($data['remove_coupon']);
    				// get already generated order stop
    				
    				
    				
    				// calculated total cart amount.
    				 $amount=$this->utilities->getProductCartAmount($data);
    				if(!$amount['status'])
    					return Response::json($amount);
    					else $data['amount_calculated']=$amount['amount'];
    					
    					
    					//*********************************************************USE THIS CODE IN GET PRODUCT CART AMOUNT.************************************************
    					// 			  															COUPON CODE
    					
    					// use this coupon code in getproductcart amount method by creating another function there.
    					
    					/* if(isset($data['coupon_code']) && $this->utilities->isGroupId($data['coupon_code']))
    					 {
    					 if($this->utilities->validateGroupId(['group_id'=>$data['coupon_code'], 'customer_id'=>$data['customer_id']])){
    					 $data['group_id'] = $data['coupon_code'];
    					 }
    					 unset($data['coupon_code']);
    					 } */
    					
    					//**************************************************************************************************.************************************************
    					
    					
    					$order = false;
    					if(!empty($data['order_id']))
    					{
    						$old_order_id = $order_id = $data['_id'] = intval($data['order_id']);
    						$order = Order::find((int)$old_order_id);
    						$data['repetition'] = 1;
    						if($order)
    							(!empty($order->repetition)) ? $data['repetition'] = $order->repetition+1:"";
    							else
    								return Response::json(['status'=>0,"message"=>"Not a valid order id."]);
    					}
    					else $order_id = $data['_id'] = $data['order_id'] = Order::maxId() + 1;
    					
    					
    					$data['code'] = (string) random_numbers(5);
    					
    					
    					
    					$successurl="";
    					$mobilehash="";
    					
    					if(in_array($data['customer']['customer_source'],['android','ios','kiosk']))
    					{
    						$txnid = "MFIT".$data['_id'];(isset($old_order_id))?$txnid = "MFIT".$data['_id']."-R".$data['repetition']  :"";
    						$successurl = $data['type'] == "product" ? Config::get('app.website')."/paymentsuccessandroid":"";
    					}
    					else return ["status"=>0,"message"=>"customer_source not in ['android','ios','kiosk']"];
    					$data['payment']['txnid'] = $txnid;
    					
    					//*********************************************************USE THIS CODE IN GET PRODUCT CART AMOUNT.************************************************
    					// 			  														CONVINIENCE FEE  AMOUNT
    					
    					/* if($this->utilities->isConvinienceFeeApplicable($data)){
    					 $convinience_fee_percent = Config::get('app.convinience_fee');
    					 $convinience_fee = round($data['amount_finder']*$convinience_fee_percent/100);
    					 $convinience_fee = $convinience_fee <= 199 ? $convinience_fee : 199;
    					 $data['convinience_fee'] = $convinience_fee;
    					 } */
    					
    					//**************************************************************************************************.************************************************
    					
    					
    					//*********************************************************USE THIS CODE IN GET PRODUCT CART AMOUNT .************************************************
    					//																		WALLET AMOUNT
    					
    					
    					/*     	if(isset($data['wallet']) && $data['wallet']){
    					 $data['amount_final'] = $data['amount'] = $data['amount'] + $data['convinience_fee'];
    					 $data['amount_customer'] = $data['amount'];
    					 $data['coupon_discount_amount'] = 0;
    					 unset($data['instant_payment_discount']);
    					 } */
    					
    					//**************************************************************************************************.************************************************
    					
    					
    					$hash = $this->utilities->getProductHash($data);
    					if(!$hash['status'])
    						Response::json($hash);
    					else $data['payment']=array_merge($data['payment'],$hash['data']);
    					
    						
    						
    						$data['link']['payment_link'] = Config::get('app.website')."/paymentlink/".$data['order_id']; //$this->utilities->getShortenUrl(Config::get('app.website')."/paymentlink/".$data['order_id']);
    						$data['link']['profile_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);
    						$data['link']['download_app_link'] = Config::get('app.download_app_link');
    						
    						
    						// 									ADDED LATEST DEVICE INFO IN ORDER FROM REQUEST HEADER IF PROVIDED
    						
    						$addUpdateDevice = $this->utilities->addUpdateDevice($data['customer']['customer_id']);
    						
    						$data['device']=[];
    						foreach ($addUpdateDevice as $header_key => $header_value) {
    							($header_key != "")?
    							$data['device'][$header_key]= $header_value:"";
    						}
    						
    						$data["status"] = "0";
    						
    						
    						
    						
    						
    						unset($data['customer_name']);unset($data['customer_phone']);
    						unset($data['customer_email']);unset($data['customer_identity']);
    						unset($data['customer_source']);unset($data['payment_mode']);
    						
    						
    						//************************************************************** setting default payment mode start 
    						$data['payment']['payment_mode'] = 'paymentgateway';
    						
    						//**************************************************************setting default payment mode end
    						
    						
    						if(isset($data['payment_mode']) && $data['payment_mode'] == 'cod'){
    							
    							$group_id = isset($data['group_id']) ? $data['group_id'] : null;
    							$order->group_id = $data['group_id'] = $this->utilities->addToGroup(['customer_id'=>$data['customer']['customer_id'], 'group_id'=>$group_id, 'order_id'=>$order['_id']]);
    							//     		$this->customermailer->orderUpdateCOD($order->toArray());
    							//     		$this->customersms->orderUpdateCOD($order->toArray());
    							$order->cod_otp = $this->utilities->generateRandomString();
    							$order->update();
    						}
    						
    						// 																											FINANCE CODE
    						//																	***********************************  TO BE WRITTEN LATER ***********************************
    						
    						// 																					    		$this->utilities->financeUpdate($order);
    						
    						//																	*********************************************************************************************************
    						
    						if(in_array($data['customer']['customer_source'],['android','ios','kiosk']))
    						{
    							$mobilehash = $data['payment']['payment_related_details_for_mobile_sdk_hash'];
    						}
    						
    						$result['firstname'] = strtolower($data['customer']['customer_name']);
    						$result['lastname'] = "";
    						$result['phone'] = $data['customer']['customer_phone'];
    						$result['email'] = strtolower($data['customer']['customer_email']);
    						$result['orderid'] = $data['_id'];
    						$result['txnid'] = $txnid;
    						$result['amount'] = $data['amount_calculated']['final'];
    						$result['productinfo'] = strtolower($data['payment']['productinfo']);
    						$result['successurl'] = $successurl;
    						$result['hash'] = $data['payment']['payment_hash'];
    						$result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
    						$result['type'] = $data['type'];
    						
    						
    						/* if(!empty($data['convinience_fee']))
    						 {
    						 $result['convinience_fee_charged'] = true;
    						 $result['convinience_fee'] = $data['convinience_fee'];
    						 } */
    						
                            //  $cash_pickup_applicable = (isset($data['amount_calculated']['final']) && $data['amount_calculated']['final']>= 2500) ? true : false;
    						$emi_applicable = (isset($data['amount_calculated']['final']) && $data['amount_calculated']['final']>= 5000) ? true : false;
    						
    						$resp   =   [
    								'status' => 200,
    								'data' => $result,
    								'message' => "Tmp Order Generated Sucessfully",
                                    //  'cash_pickup' => $cash_pickup_applicable,
    								'emi'=>$emi_applicable
    						];
    						
    						// Commented dont know why its being used right now.
    						
    						//     	$resp['data']['order_details'] = $this->getBookingDetails($order->toArray());
    						
    						
    						$payment_mode_type_array = ['paymentgateway'];
    						$payment_details = [];
    						
    						
    						if(!empty($order))
    							$orderArray=$order->toArray();
    							
    							
    							if(isset($data['amount_calculated']['final']))
    							{
    								(!empty($emi_applicable)&& !empty($data['amount_calculated']['final']))?	array_push($payment_mode_type_array, 'emi'):"";
    								(!empty($cash_pickup_applicable)&& !empty($data['amount_calculated']['final']))?array_push($payment_mode_type_array, 'cod'):"";
    								
    								if($this->vendor_token)
    									if(!empty($pay_at_vendor_applicable))
    										array_push($payment_mode_type_array, 'pay_at_vendor');
    							}
    							
    							
    							foreach ($payment_mode_type_array as $payment_mode_type)
    							{
    								$payment_info=$this->getPaymentDetailsProduct($data);
    								if($payment_info['status'])
    									$payment_details[$payment_mode_type] =$payment_info['details'];
    									else return Response::json($payment_info);
    							}
    							
    							
    							
    							$resp['data']['payment_details'] = $payment_details;
    							$resp['data']['payment_modes'] = [];
    							$prd_details=$this->utilities->getAllProductDetails($data);
    							$resp['data']['order_details']=(!empty($prd_details)&&!empty($prd_details['status'])&&!empty($prd_details['data'])&&!empty($prd_details['data']['cart_details'])?$prd_details['data']['cart_details']:[]);
    							
    							
    							if(!empty($data['amount_calculated']['final']))
    								$resp['data']['payment_modes'] = $this->getPaymentModesProduct($resp);
    								
    								$otp_flag = true;
    								
    								if(!empty($data['payment_mode'])&&$data['payment_mode'] == 'at the studio' && isset($data['wallet']) && $data['wallet'] && $otp_flag){
    									
    									$data_otp = array_only($data,['finder_id','order_id','service_id','ratecard_id','payment_mode','finder_vcc_mobile','finder_vcc_email','customer_name','service_name','service_duration','finder_name', 'customer_source','amount_finder','amount','finder_location','customer_email','customer_phone','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon']);
    									
                                        $data_otp['action'] = "vendor_otp";
                                        
                                        if(!empty($data['multifit'])){
                                            $data_otp['multifit'] = $data['multifit'];
                                        }
    									
    									$addTemp_flag  = true;
    									
    									if(isset($order['otp_data'])){
                                            $old_order = $order->toArray();
                                            
                                            Log::info(" =======  old Oder",[$old_order]);

    										$otp_data = $old_order['otp_data'];
    										
    										if(isset($otp_data['created_at']) && ((time() - $otp_data['created_at']) / 60) < 3){
    											$addTemp_flag = false;
    										}
    										$temp_id = $order['otp_data']['temp_id'];
    										$temp = Temp::find($temp_id);
    										
    										if(!$temp){
    											
    											return Response::json(['status' => 400, "message" => "Internal Error"],$this->error_status);
    										}
    										
    										if($temp->verified == "Y"){
    											
    											return Response::json(array('status' => 400,'message' => 'Already Verified'),$this->error_status);
    										}
                                            $otp_data['otp'] = $temp['otp'];
                                        
    									}
    									
    									if($addTemp_flag){
    										
    										$addTemp = addTemp($data_otp);
    										
    										$otp_data = [
    												'finder_vcc_mobile'=>$data_otp['finder_vcc_mobile'],
    												'finder_vcc_email'=>$data_otp['finder_vcc_email'],
    												'payment_mode'=>$data_otp['payment_mode'],
    												'temp_id'=>$addTemp['_id'],
    												'otp'=>$addTemp['otp'],
    												'created_at'=>time()
    										];
    										$order->update(['otp_data'=>$otp_data]);
    										$otp_data['otp'] = $addTemp['otp'];
    									}
    									$otp_data = array_merge($data_otp,$otp_data);
    									
    									$this->findersms->genericOtp($otp_data);
    									$this->findermailer->genericOtp($otp_data);
    									
    									if(!$this->vendor_token){
    										
    										$this->customermailer->atVendorOrderCaputure($otp_data);
    										$this->customersms->atVendorOrderCaputure($otp_data);
    									}
    									$resp['vendor_otp'] = $otp_data['otp'];
    									$resp['data']['verify_otp_url'] = Config::get('app.url')."/kiosk/vendor/verifyotp";
    									$resp['data']['resend_otp_url'] = Config::get('app.url')."/temp/regenerateotp/".$otp_data['temp_id'];
    									if($data["customer_source"] == "website")
    										$resp['data']['show_success'] = true;
    								}
    								
    								
    								if(isset($old_order_id))
    									$order->update($data);
    									else
    									{
    										$order = new Order($data);
    										$order->_id = $order_id;
    										$order->save();
    									}
    									return Response::json($resp);
    }

    public function generateOtp($length = 4)
    {      
        $characters = '0123456789';
        $result = '';
        $charactersLength = strlen($characters);

        for ($p = 0; $p < $length; $p++)
        {
            $result .= $characters[rand(0, $charactersLength - 1)];
        }

        return $result;
    }

    public function verifyVendorOtpKiosk(){

        $data = Input::json()->all();

        $rules = array(
            'order_id'=>'required',
            'otp'=>'required'
        );

        if(!$this->vendor_token){
            $rules['finder_id'] = 'required'; 
        }

        $validator = Validator::make($data,$rules);

        $app_version  = (float)Request::header('App-Version');

        $status = ($app_version > 1.06) ? 200 : 400;

        if ($validator->fails()) {
            return Response::json(array('status' => 400,'message' => error_message($validator->errors())),$status);
        }

        $order = Order::find((int)$data['order_id']);

        if(!$order){

            return Response::json(['status' => 400, "message" => "Order Not Found"],$status);
        }

        if($order->status == "1"){

            return Response::json(	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)"));

            // return Response::json(['status' => 400, "message" => "Already Status Successfull"],$status);
        }

        if($this->vendor_token){

            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = $decodeKioskVendorToken->vendor;

            $finder_id = (int)$vendor->_id;

        }else{

            $finder_id = (int)$data['finder_id'];
        }

        if($order['type'] != 'product' && $finder_id != $order['finder_id']){

            return Response::json(['status' => 400, "message" => "Incorrect Vendor"],$status);
        }

        if($order['type'] == 'product' && $finder_id != $order['finder']['finder_id']){

            return Response::json(['status' => 400, "message" => "Incorrect Vendor"],$status);
        }

        if(!isset($order['otp_data'])){

            return Response::json(['status' => 400, "message" => "OTP data not found"],$status);
        }

        if($order['otp_data']['otp'] != $data['otp']){

            return Response::json(['status' => 400, "message" => "Incorrect OTP"],$status);
        }

        $temp_id = $order['otp_data']['temp_id'];

        $temp = Temp::find($temp_id);

        if(!$temp){

            return Response::json(['status' => 400, "message" => "Internal Error"],$status);
        }

        if($temp['otp'] != $data['otp']){

            return Response::json(['status' => 400, "message" => "Incorrect OTP"],$status);
        }

        if($temp->verified == "Y"){

            return Response::json(array('status' => 400,'message' => 'Already Verified'),$status);

        }else{

            $temp->verified = "Y";
            $temp->save();
        }

        if(in_array($order['type'],['booktrials','workout-session'])){

            $data = [];

            $data['status'] = 'success';
            $data['order_success_flag'] = 'admin';
            $data['order_id'] = (int)$order['_id'];
            $data['customer_name'] = $order['customer_name'];
            $data['customer_email'] = $order['customer_email'];
            $data['customer_phone'] = $order['customer_phone'];
            $data['finder_id'] = (int)$order['finder_id'];
            $data['service_name'] = $order['service_name'];
            $data['type'] = $order['type'];
            $data['premium_session'] = true;

            if($this->vendor_token){
                $data['order_success_flag'] = 'kiosk';
            }

            if(isset($order['start_date']) && $order['start_date'] != ""){
                $data['schedule_date'] = date('d-m-Y',strtotime($order['start_date']));
            }

            if(isset($order['start_time']) && $order['start_time'] != "" && isset($order['end_time']) && $order['end_time'] != ""){
                $data['schedule_slot'] = $order['start_time']."-".$order['end_time'];
            }

            if(isset($order['schedule_date']) && $order['schedule_date'] != ""){
                $data['schedule_date'] = $order['schedule_date'];
            }

            if(isset($order['schedule_slot']) && $order['schedule_slot'] != ""){
                $data['schedule_slot'] = $order['schedule_slot'];
            }

            $storeBooktrial = $this->fitapi->storeBooktrial($data);

            if($storeBooktrial['status'] == 200){

                return Response::json($storeBooktrial['data'],$status);

            }else{

                return Response::json(['status' => 400, "message" => "Internal Error Please Report"],$status);
            }

        }else{

            $data['status'] = 'success';
            $data['order_success_flag'] = 'admin';
            $data['order_id'] = (int)$data['order_id'];
            $data['customer_email'] = $order['customer_email'];
            $data['send_communication_customer'] = 1;
            $data['send_communication_vendor'] = 1;

            if($this->vendor_token){
                $data['order_success_flag'] = 'kiosk';
            }

            return $this->successCommon($data);

        } 

    }

    public function update(){
        
        $decoded = decode_customer_token();

        $rules = array(
            "order_id"=>"numeric|required"
        );

        $data = Input::json()->all();

        Log::info("transaction update ----------------",$data);

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {

            return Response::json(array('status' => 401,'message' => error_message($validator->errors())),401);

        }else{

            $order_id = (int) $data['order_id'];
            $message = "";

            $order = Order::find($order_id);

            if(!$order){

                $resp   =   array("status" => 401,"message" => "Order Does Not Exists");
                return Response::json($resp,$resp["status"]);
            }

            if($order->customer_email != $decoded->customer->email){
                $resp   =   array("status" => 401,"message" => "Invalid Customer");
                return Response::json($resp,$resp["status"]);
            }

            if(isset($data["payment_mode"]) && $data["payment_mode"] == "cod"){
               $data["secondary_payment_mode"] = "cod_membership";
            }

            if($order->status == "1" && isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '' && $data['preferred_starting_date']  != '-'){

                $preferred_starting_date = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']));

                $data['start_date'] = $preferred_starting_date;
                $data['preferred_starting_date'] = $preferred_starting_date;
                $data['end_date'] = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']."+ ".($order->duration_day-1)." days"));

                $data['preferred_starting_change_date'] = date("Y-m-d H:i:s");

                $order->update($data);

                $emailData = $order->toArray();
                $emailData['preferred_starting_updated'] = true;

                $this->customermailer->sendPgOrderMail($emailData);
                $this->findermailer->sendPgOrderMail($emailData);
                $this->customersms->changeStartDate($emailData);
                $this->findersms->changeStartDate($emailData);

                $message = "Thank you! Your starting date has been changed";

                return Response::json(array('status' => 200,'message' => $message,'start_date'=>$data['start_date'],'end_date'=>$data['end_date']),200);

            }

            if($order->status == "1" && isset($data['upgrade_membership']) && $data['upgrade_membership'] == "requested"){

                $data['upgrade'] = ["requested"=>time()];

                $order->update($data);

                $emailData = $order->toArray();
                $emailData['capture_type'] = "upgrade-membership";
                $emailData['phone'] = $order->customer_phone;

                $this->customersms->landingPageCallback($emailData);

                $message = "Upgrade request has been noted. We will call you shortly.";

                $captureData = [
                    "customer_id" => $order->customer_id,
                    "customer_name" => $order->customer_name,
                    "customer_email" => $order->customer_email,
                    "customer_phone" => $order->customer_phone,
                    "order_id" => $order->order_id,
                    "finder_id" => $order->finder_id,
                    "city_id" => $order->city_id,
                    "capture_type" => "upgrade-membership"
                ];

                $this->utilities->addCapture($captureData);
            }

            return Response::json(array('status' => 200,'message' => $message),200);
        }

    }

    public function success($data = null){
        if($data){
            $data['internal_success'] = true;
        }else{
            $data = Input::json()->all();
        }

        return $this->successCommon($data);

    }

    public function successCommon($data){

        Log::info('successCommon',$data);
        
        $rules = array(
            'order_id'=>'required'
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }
        
        $order_id   =   (int) $data['order_id'];
        $order      =   Order::findOrFail($order_id);
        
        Log::info(" info order_type _____________________".print_r($order['type'],true));
        if(!empty($order)&&!empty($order['type'])&&$order['type']=='product')
        	return $this->productSuccess($data);
        
        if(!empty($order)&&!empty($order['type'])&&$order['type']=='giftcoupon')
        	return $this->giftCouponSuccess();
        
        if(!empty($order)&&!empty($order['type'])&&$order['type']=='pass') {
            $passResp = $this->passService->passSuccessPayU($data);
            if(!empty($data['payment_id_paypal'])) {
                return Response::json($passResp,200);
            }
            return $passResp;
        }

        //If Already Status Successfull Just Send Response
        if(!isset($data["order_success_flag"]) && isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){
            $resp   =   array('status' => 401, 'statustxt' => 'error', "message" => "Already Status Successfull");
            return Response::json($resp,401);

        }elseif(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && isset($order->status) && $order->status != '1' && isset($order->order_action) && $order->order_action != 'bought'){
            $resp   =   array('status' => 401, 'statustxt' => 'error',"message" => "Status should be Bought");
            return Response::json($resp,401);
        }
      
        $hash_verified = $this->utilities->verifyOrder($data,$order);
        
        Log::info("successCommon ",[$hash_verified]);
        if($data['status'] == 'success' && $hash_verified){
            // Give Rewards / Cashback to customer based on selection, on purchase success......

            $this->utilities->demonetisation($order);

            array_set($data, 'status', '1');

            if(isset($order['part_payment']) && $order['part_payment'] && (!isset($data['order_success_flag']) || $data['order_success_flag'] != 'admin')){
                array_set($data, 'status', '3');
            }

            $reward_type = "";

            if(!empty($order['reward_type'])){

                 $reward_type = $order['reward_type'];
            }

            if($data['status'] == '1'){

                if(!empty($data['parent_payment_id_paypal'])){
                    array_set($data, 'parent_payment_id_paypal', $data['parent_payment_id_paypal']);
                }
    
                if(!empty($data['payment_id_paypal'])){
                    array_set($data, 'payment_id_paypal', $data['payment_id_paypal']);
                }

                if($order->type == "memberships"){
                    $group_id = isset($order->group_id) ? $order->group_id : null;
                    $data['group_id'] = $this->utilities->addToGroup(['customer_id'=>$order->customer_id, 'group_id'=>$group_id, 'order_id'=>$order->_id]);
                }

                if((empty($order['studio_extended_validity_order_id']) && !empty($order['studio_extended_session'])) || empty($order['studio_extended_session'])){
                    $this->customerreward->giveCashbackOrRewardsOnOrderSuccess($order);
                }

                if(!empty($order['wallet_transaction_debit']['wallet_transaction'])){

                    $upgraded_wallet = array_filter($order['wallet_transaction_debit']['wallet_transaction'], function($e){return !empty($e['upgraded_order_id']);});
                    $upgraded_order_ids = array_column($upgraded_wallet, 'upgraded_order_id');
                    $this->setUpgradedOrderRedundant($order, $upgraded_order_ids);
                }
                $order = Order::where('_id', $order->_id)->first();

                if(!empty($order['upgrade_fitcash'])){
                    array_set($data, 'upgrade_fitcash', true);
                    // $updated_order->upgrade_fitcash = true;
                }

                // if($updated_order && !empty($updated_order->reward_content)){
                //     $order->reward_content = $updated_order->reward_content;
                // }

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
                            $profile_link = $value->reward_type == 'diet_plan' ? $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']."#diet-plan") : $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);
                            array_set($data, 'reward_type', $value->reward_type);

                            // if($data['reward_type'] == "mixed" && $order['ratecard_amount'] >= 8000 && ($order['type'] == 'memberships' || $order['type'] == 'membership') && empty($order['extended_validity_order_id']) && empty($order['studio_extended_validity_order_id']) ){
                            //     array_set($data, 'diwali_mixed_reward', true);
                            // }

                            if(!empty($order['brand_id']) && $order['brand_id'] == 88){
                                if($data['reward_type'] == "mixed" && $order['ratecard_amount'] >= 8000 && ($order['type'] == 'memberships' || $order['type'] == 'membership') && empty($order['extended_validity']) && empty($order['studio_extended_validity']) ){
                                    array_set($data, 'fitbox_mixed_reward', true);
                                    array_set($data, 'multifit_fitbox_mixed_reward', true);
                                }
                            }else if(!empty($order['finder_id']) && in_array($order['finder_id'], Config::get('app.fitbox_reward_vendor_id'))){
                                if($data['reward_type'] == "mixed" && $order['ratecard_amount'] >= 8000 && ($order['type'] == 'memberships' || $order['type'] == 'membership') && empty($order['extended_validity']) && empty($order['studio_extended_validity']) ){
                                    array_set($data, 'fitbox_mixed_reward', true);
                                    array_set($data, 'other_fitbox_mixed_reward', true);
                                }
                            }
                            // else{
                            //     if($data['reward_type'] == "mixed" && $order['ratecard_amount'] >= 8000 && ($order['type'] == 'memberships' || $order['type'] == 'membership') && empty($order['extended_validity']) && empty($order['studio_extended_validity']) ){
                            //         array_set($data, 'vk_puma_bag_reward', true);
                            //     }
                            // }

                            $reward_type = $value->reward_type;

                        }

                        $reward_info = (!empty($reward_detail)) ? implode(" + ",$reward_detail) : "";

                        array_set($data, 'reward_info', $reward_info);
                        
                    }

                }

                if(isset($order->cashback) && $order->cashback === true && isset($order->cashback_detail) ){

                    $reward_info = "Cashback";
                    
                    array_set($data, 'reward_info', $reward_info);
                    array_set($data, 'reward_type', 'cashback');

                    $reward_type = 'cashback';
                }

                if($order->type == "memberships"){
                    try{
            
                        $after_booking_response = $this->utilities->afterTranSuccess($order->toArray(), 'order');

                    }catch(Exception $e){
            
                        $after_booking_response = [];
                        Log::info(['status'=>400,'message'=>$e->getMessage().' - Line :'.$e->getLine().' - Code :'.$e->getCode().' - File :'.$e->getFile()]);
                        
                    }
                    
                    if(!empty($after_booking_response['loyalty_registration']['status']) && $after_booking_response['loyalty_registration']['status'] == 200){
                        $order->loyalty_registration = true;
                    }
                
                }

    
            }
            
            if(isset($order['previous_booktrial_id']) && $order['previous_booktrial_id'] != ""){

                $booktrial = Booktrial::find((int) $order['previous_booktrial_id']);

                if($booktrial){

                    $booktrial->final_lead_stage = "purchase_stage";
                    $booktrial->update();
                }
            }
            
            array_set($data, 'order_action', 'bought');

            if(((!isset($data['order_success_flag']) || $data['order_success_flag'] != 'admin') && !isset($order['success_date'])) || (isset($order['update_success_date']) && $order['update_success_date'] == "1" && isset($data['order_success_flag']) && $data['order_success_flag'] == 'admin')){
                array_set($data, 'success_date', date('Y-m-d H:i:s',time()));
            }
            
            if(isset($order['start_date'])){
                array_set($data, 'auto_followup_date', date('Y-m-d H:i:s', strtotime("+7 days",strtotime($order['start_date']))));
            }
            
            array_set($data, 'followup_status', 'catch_up');
            array_set($data, 'followup_status_count', 1);

            $data['workout_article_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/article/complete-guide-to-help-you-prepare-for-the-first-week-of-your-workout");
            $data['download_app_link'] = Config::get('app.download_app_link');
            $data['diet_plan_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/diet-plan");

            if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
            
                array_set($data, 'membership_bought_at', 'Fitternity Payu Mode');

                $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone', substr($order->customer_phone, -10))->where('_id','!=',(int)$order->_id)->where('finder_id',$order->finder_id)->count();

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

            if(!empty($order['extended_validity'])){
                // Ratecard::$withoutAppends = true;
                $ratecard = Ratecard::find($order['ratecard_id'], ['flags']);
            }

            $snap_block = in_array($order['finder_id'], Config::get('app.snap_bangalore_finder_ids')) && $order['type'] == 'memberships';
            $extended_validity_block = !empty($order['extended_validity']) && empty($ratecard['flags']['enable_vendor_ext_validity_comm']);

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

                if(empty($snap_block) && empty($extended_validity_block)){

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
				
            }
            $data["profile_link"] = isset($profile_link) ? $profile_link : $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$data['customer_email']);

            $encodeOrderTokenData = [
                'order_id'=>$order['_id'],
                'customer_id'=>$order['customer_id'],
                'customer_email'=>$order['customer_email'],
                'customer_name'=>$order['customer_name'],
                'customer_phone'=>$order['customer_phone'],
            ];

            $data['order_token'] = $order_token = encodeOrderToken($encodeOrderTokenData);

            $data['membership_invoice_request_url'] = Config::get('app.website')."/membership?capture_type=membership_invoice_request&order_token=".$order_token;

            $data['membership_cancel_request_url'] = Config::get('app.website')."/membership?capture_type=membership_cancel_request&order_token=".$order_token;


            if(!empty($data['type']) && $data['type'] == 'memberships'){
                $data['loyalty_email_content'] = $this->utilities->getLoyaltyEmailContent($order);
            }

            // $free_sp_ratecard_id = $this->utilities->getFreeSPRatecard($order);
            
            // if(!empty($free_sp_ratecard_id) && !empty($data["reward_type"]) && $data["reward_type"] ==  "mixed"){
            //     $data['free_sp_ratecard_id'] = $free_sp_ratecard_id['_id'];
            //     $data['free_sp_url'] = Config::get('app.website')."/membership?capture_type=womens_offer_week&order_token=".$order_token;
            // }
            
            if(!empty($order['ratecard_flags']['free_sp'])){

                $parent_order_update = Order::where('_id', $order['parent_order_id'])->where('free_sp_order_id', 'exists', false)->update(['free_sp_order_id'=>$order['_id']]);

                if(empty($parent_order_update)){
                    return ['status' => 400, 'statustxt' => 'failed', 'message' => "Transaction Failed :)"];
                }
            }


            $order->update($data);

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

            if($order->finder_id == 6318){
                $abundant_category = array(42);
            }

            if(isset($order['part_payment']) && $order['part_payment'] && $data['status'] == '3'){

                $this->customermailer->orderUpdatePartPayment($order->toArray());
                $this->customersms->orderUpdatePartPayment($order->toArray());
                $this->findermailer->orderUpdatePartPayment($order->toArray());
                $this->findersms->orderUpdatePartPayment($order->toArray());
                $this->findermailer->partPaymentFitternity($order->toArray());
                
                $daysToGo = Carbon::now()->diffInDays(\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $order->preferred_starting_date));
            
                if($daysToGo > 2){

                    $before2days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $order->preferred_starting_date)->addMinutes(-60 * 24 * 2);
                    $order->cutomerEmailBefore2Days = $this->customermailer->orderUpdatePartPaymentBefore2Days($order->toArray(), $before2days);
                    $order->cutomerSmsBefore2Days = $this->customersms->orderUpdatePartPaymentBefore2Days($order->toArray(), $before2days);

                    $order->finderEmailBefore2Days = $this->findermailer->orderUpdatePartPaymentBefore2Days($order->toArray(), $before2days);
                    $order->finderSmsBefore2Days = $this->findersms->orderUpdatePartPaymentBefore2Days($order->toArray(), $before2days);
                
                }
            
            }else{

                if (filter_var(trim($order['customer_email']), FILTER_VALIDATE_EMAIL) === false){
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
                        if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
                            if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                                $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                                
                                // $this->customermailer->payPerSessionFree($emailData);
                            }

                        }else{

                            if(!empty($emailData['event_type']) && $emailData['event_type'] == 'TOI' && !empty($emailData['event_customers'])){
                                foreach($emailData['event_customers'] as $c){
                                    $emailData['bbcustomer_name'] = $emailData['customer_name'];
                                    $emailData['customer_email'] = $c['customer_email'];
                                    $emailData['customer_name'] = $c['customer_name'];
                                    $emailData['jockey_code'] = !empty($c['jockey_code']) ? $c['jockey_code'] :'';
                                    $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                                }
                            
                            }else{
                                $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                            }
                            

                            // $this->customermailer->payPerSessionFree($emailData);

                            if(isset($order['routed_order']) && $order['routed_order'] == "1" && !in_array($reward_type,['cashback','diet_plan'])){
                                $this->customermailer->routedOrder($emailData);
                            }
                        }
                    }

                    // Log::info('---- checking sendPgOrderMail --- 17 Jan');
                    // Log::info('!in_array($finder->category_id, $abundant_category): ', [!in_array($finder->category_id, $abundant_category)]);
                    // Log::info('$order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != "diet_plan": ', [$order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan']);
                    // Log::info('!(!empty($order->duration_day) && $order->duration_day == 30 && !(!empty($data["order_success_flag"]) && $data["order_success_flag"] == "admin")): ',[!(!empty($order->duration_day) && $order->duration_day == 30 && !(!empty($data["order_success_flag"]) && $data["order_success_flag"] == "admin"))]);
                    // Log::info('empty($snap_block): ', [empty($snap_block)]);
                    // Log::info('empty($extended_validity_block)', [empty($extended_validity_block)]);

                    //no email to Healthy Snacks Beverages and Healthy Tiffins
                    if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan' && empty($snap_block) && empty($extended_validity_block)){

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
                    
                    if($this->utilities->checkCorporateLogin()){
                        Log::info("outside checkCorporateLogin ");
                        $emailData['customer_email'] =   $emailData['customer_email'].',vg@fitmein.in';
                    }

                    //print_pretty($emailData);exit;
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
                        if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                            $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);                            
                        }

                    }else{
                        $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);                        
                    }
                }

                Log::info('---- checking sendPgOrderMail --- 17 Jan');
                Log::info('!in_array($finder->category_id, $abundant_category): ', [!in_array($finder->category_id, $abundant_category)]);
                Log::info('$order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != "diet_plan": ', [$order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan']);
                Log::info('!(!empty($order->duration_day) && $order->duration_day == 30 && !(!empty($data["order_success_flag"]) && $data["order_success_flag"] == "admin")): ',[!(!empty($order->duration_day) && $order->duration_day == 30 && !(!empty($data["order_success_flag"]) && $data["order_success_flag"] == "admin"))]);
                Log::info('empty($snap_block): ', [empty($snap_block)]);
                Log::info('empty($extended_validity_block)', [empty($extended_validity_block)]);

                //no sms to Healthy Snacks Beverages and Healthy Tiffins                
                if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan' && empty($snap_block) && empty($extended_validity_block)){
                
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                        if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                            $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());                           
                        }
                        
                    }else{
                        $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());                        
                    }
                    
                }

                if(!empty($order['upgrade_fitcash'])){
                    $data['upgrade_sms_instant'] = $this->customersms->upgradeMembershipInstant($order->toArray());                    
                }

            }

            $this->utilities->sendCorporateMail($order->toArray());

            if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !in_array($finder->category_id, $abundant_category) && $order->type == "memberships" && !isset($order->customer_sms_after3days) && !isset($order->customer_email_after10days) && $order->type != 'diet_plan'){

                $preferred_starting_date = $order->preferred_starting_date;

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

                // $after1days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 1);
                // $after7days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 7);
                // $after15days = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $preferred_starting_date)->addMinutes(60 * 24 * 15);

                // $this->customersms->purchaseInstant($order->toArray());
                // $order->cutomerSmsPurchaseAfter1Days = $this->customersms->purchaseAfter1Days($order_data,$after1days);
                // $order->cutomerSmsPurchaseAfter7Days = $this->customersms->purchaseAfter7Days($order_data,$after7days);
                // $order->cutomerSmsPurchaseAfter15Days = $this->customersms->purchaseAfter15Days($order_data,$after15days);

                $order->update();

            }

            if(isset($order->city_id)){

                $city = City::find((int)$order->city_id,['_id','name','slug']);

                $order->update(['city_name'=>$city->name,'city_slug'=>$city->slug]);
            }

            if(isset($order->finder_category_id)){

                $category = Findercategory::find((int)$order->finder_category_id,['_id','name','slug']);

                $order->update(['category_name'=>$category->name,'category_slug'=>$category->slug]);
            }
            

            if(isset($order->diet_plan_ratecard_id) && $order->diet_plan_ratecard_id != "" && $order->diet_plan_ratecard_id != 0 && !isset($order->diet_plan_order_id) || (isset($order->diet_inclusive) && $order->diet_inclusive)){

                if(!isset($order->diet_plan_ratecard_id)){
                    $ratecard = Ratecard::find($order->ratecard_id, array('diet_ratecard'));
                    if($ratecard){
                        $order->diet_plan_ratecard_id = $ratecard->diet_ratecard;
                    }
                }

                $generaterDietPlanOrder = $this->generaterDietPlanOrder($order->toArray());

                if($generaterDietPlanOrder['status'] != 200){
                    return Response::json($generaterDietPlanOrder,$generaterDietPlanOrder['status']);
                }

                $order->diet_plan_order_id = $generaterDietPlanOrder['order_id'];
                $order->update();

            }

            if(isset($order->type) && $order->type == "diet_plan"){
                $order->final_assessment = "no";
                $order->renewal = "no";
                $order->update();
            }
            if(isset($order->coupon_code)){
                $coupon = Coupon::where('code', strtolower($order['coupon_code']))->first();
                Log::info("coupon");
                $fitternity_only_coupon = ($coupon && isset($coupon->fitternity_only) && $coupon->fitternity_only) ? true : false;
                
                if(!$fitternity_only_coupon){
                    $customer_update 	=	Customer::where('_id', $order->customer_id)->push('applied_promotion_codes', $order->coupon_code, true);	
                }
            }

            $this->utilities->setRedundant($order);

            $this->utilities->addAmountToReferrer($order);

            $this->utilities->addAssociateAgent($order);

            $this->utilities->updateCoupon($order);

            if(!empty($order['ticket_id']) && !empty($order['ticket_quantity'])){

                $ticket = Ticket::find(intval($order['ticket_id']));

                if($ticket){

                    $ticket->sold = (int)($ticket->sold + (int)$order['ticket_quantity']);
                    $ticket->update();
                }

            }

            // $this->utilities->saavn($order);
            
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

            // if(isset($order->customer_id)){
            //     setDefaultAccount($order->toArray(), $order->customer_id);
            // }

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");

            if($order['payment_mode'] == 'at the studio'){
                $resp   =   array('status' => 200,"message" => "Transaction Successful");
            }
            // $redisid = Queue::connection('redis')->push('TransactionController@updateRatecardSlots', array('order_id'=>$order_id, 'delay'=>0),Config::get('app.queue'));
            if(!empty($order['studio_extended_validity']) && $order['studio_extended_validity']){
                Order::$withoutAppends = true;
                $prevOrderCheck = Order::where(['studio_extended_validity_order_id' => $order_id])->get()->toArray();
                if(empty($prevOrderCheck)){
                    $scheduleBookingsRedisId = Queue::connection('redis')->push('TransactionController@scheduleStudioBookings', array('order_id'=>$order_id, 'isPaid'=>false),Config::get('app.queue'));
                    $order->update(['schedule_bookings_redis_id'=>$scheduleBookingsRedisId]);
                }
            }

            if(!empty($order['combo_pass_id']) && !empty($order['ratecard_flags']['onepass_attachment_type'])){
                $complementry_pass_purchase = Queue::connection('redis')->push(
                    'PassController@passCaptureAuto', 
                    array(
                        'order' => $order,
                        'forced' => false
                    ),
                    Config::get('app.queue')
                );
                Log::info('inside schudling complementary pass purchase redis id:', [$complementry_pass_purchase]);

                $order->update(['schedule_complementry_pass_purchase_redis_id'=>$complementry_pass_purchase]);
            }

            if(!empty($order['diwali_mixed_reward'])){
                $hamper_data = $this->utilities->getVoucherDetail($order->toArray());
                $this->customermailer->diwaliMixedReward($hamper_data);
                $this->customersms->diwaliMixedReward($order->toArray());
            }

            if(!empty($order['fitbox_mixed_reward'])){
                $this->customersms->fitboxMixedReward($order->toArray());
            }

            if(!empty($order['vk_puma_bag_reward'])){

                $order['finder_name'] = !empty($order['finder_name']) ? $order['finder_name'] : null ;

                $sms_data = [];
                $sms_data['customer_phone'] = $order['customer_phone'];
                $sms_data['message'] = "Congratulations on purchasing a fitness membership at ".$order['finder_name'].". Your Special Edition Virat Kohli-Puma Gym Bag Worth INR 2500 will reach your doorstep by 2nd week of December. Kindly feel free to reach out to us on 022-61094444 for queries
                ";
                        
                $this->customersms->custom($sms_data);
            }

            Log::info("successCommon returned");
            Log::info($order['_id']);
            return Response::json($resp);

        }else{
            if($hash_verified == false){
                $Oldorder 		= 	Order::findOrFail($order_id);
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

    public function unsetData($data){

        $array = array('preferred_starting_date','start_date','start_date_starttime','end_date','preferred_payment_date');

        foreach ($array as $key => $value){
            
            if(isset($data[$value])){
                if($data[$value] == ""){
                    unset($data[$value]);
                }
            }
        }

        return $data;
    }

    public function getCustomerDetail($data){

        $data['customer_email'] = trim(strtolower($data['customer_email']));
        
        $customer_id = $data['customer_id'] = autoRegisterCustomer($data);

        $data['logged_in_customer_id'] = $customer_id;

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = customerTokenDecode($jwt_token);
            $data['logged_in_customer_id'] = (int)$decoded->customer->_id;            
            $data['logged_in_customer_email'] = $decoded->customer->email;            
        }

        $customer = Customer::find((int)$customer_id);

        if(!empty($customer['corporate_id'])){
            $data['corporate_id']  = $customer['corporate_id'];
        }
        
        if(!empty($customer['external_reliance'])){
            $data['external_reliance']  = $customer['external_reliance'];
        }

        if(isset($data['customer_address']) && $data['customer_address'] != ''){

            $data['address']  = $data['customer_address'];
        }

        if($data['type'] == 'product'){
            if(!empty($customer['cart_id']))
                $data['cart_id']  = $customer['cart_id'];
            else return ['status' => 400,'message' => "Cart doesn't exists with customer."];
        }
        if(isset($data['address']) && $data['address'] != ''){

            $data['customer_address']  = $data['address'];
        }

        if(isset($data['customer_address']) && $data['customer_address'] != ''){

            $data['address']  = $data['customer_address'];
        }

        if(isset($data['customer_address']) && is_array($data['customer_address']) && !empty($data['customer_address'])){
            
            $customerData['address'] = $data['customer_address'];
            $customer->update($customerData);

            $data['customer_address'] = $data['address'] = implode(",", array_values($data['customer_address']));
        }

        if(isset($data['customer_phone']) && $data['customer_phone'] != ''){
            setVerifiedContact($customer_id, $data['customer_phone']);
        }
        
        if(!empty($data['third_party']) && $data['third_party'] &&!empty($customer->third_party_details->{$data['third_party_acronym']}->third_party_token_id)) {
         	$data['total_sessions']=$customer->total_sessions;
        	$data['total_sessions_used']=$customer->total_sessions_used;
        	$data['third_party_token_id']=$customer->third_party_details->{$data['third_party_acronym']}->third_party_token_id;
        }
        else if(!empty($data['third_party']) && $data['third_party']) {
            // $data['total_sessions'] = $data['third_party_total_sessions'];
        	$data['total_sessions_used'] = $data['third_party_details'][$data['third_party_acronym']]['third_party_used_sessions'];
        	$data['third_party_details'] = $data['third_party_details'];
        }

        $device_type = (isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id = (isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

        if($device_type == '' || $gcm_reg_id == ''){

            $getRegId = getRegId($data['customer_id']);

            if($getRegId['flag']){

                $device_type = $data["device_type"] = $getRegId["device_type"];;
                $gcm_reg_id = $data["reg_id"] = $getRegId["reg_id"]; 

                $data['gcm_reg_id'] = $getRegId["reg_id"];
            }
        }
         
        if($device_type != '' && $gcm_reg_id != ''){

            $regData = array();

            $regData['customer_id'] = $data["customer_id"];
            $regData['reg_id'] = $gcm_reg_id;
            $regData['type'] = $device_type;

            $this->utilities->addRegId($regData);
        }

        return array('status' => 200,'data' => $data);

    }

    public function productSuccess($data)
    {
    	
    	$rules = array(
    			'order_id'=>'required'
    	);
    	Log::info(" info [productSuccess] :: ".print_r($data,true));
    	$validator = Validator::make($data,$rules);
    	
    	if ($validator->fails()) {
    		return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
    	}
    	
    	$order_id   =   (int) $data['order_id'];
    	$order      =   Order::findOrFail($order_id);
    	
    	if(!isset($data["order_success_flag"]) && isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){
    		
    		$resp   =   array('status' => 401, 'statustxt' => 'error', "message" => "Already Status Successfull");
    		return Response::json($resp,401);
    		
    	}elseif(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && isset($order->status) && $order->status != '1' && isset($order->order_action) && $order->order_action != 'bought'){
    		
    		$resp   =   array('status' => 401, 'statustxt' => 'error',"message" => "Status should be Bought");
    		return Response::json($resp,401);
    	}
    	
    	if(!empty($data['internal_success'])){
    		$hash_verified = true;
    	}else{
    		$hash_verified = $this->utilities->verifyOrderProduct($data,$order);
    	}
    	Log::info(" info  hash_verified :: ".print_r($hash_verified,true));
    	if(!empty($data['status'])&&$data['status'] == 'success' && $hash_verified){
    		$orderArr=$order->toArray();
    		// Give Rewards / Cashback to customer based on selection, on purchase success......
    		// $this->utilities->demonetisation($order);
    		
    		$orderArr['status']="1";
    		//     		$orderArr['order_action']="bought";
    		//     		$orderArr['followup_status']="catch_up";
    		//     		$orderArr['followup_status_count']=1;
    		
    		
    		/* if((!isset($data['order_success_flag']) || $data['order_success_flag'] != 'admin')){
    		 $orderArr['status']="3";
    		 } */
    		
    		
    		if(((!isset($data['order_success_flag']) || $data['order_success_flag'] != 'admin') && !isset($order['success_date'])) || (isset($order['update_success_date']) && $order['update_success_date'] == "1" && isset($data['order_success_flag']) && $data['order_success_flag'] == 'admin')){
    			$orderArr['payment']['success_date']=new MongoDate();
    		}
    		
    		
    		$data['link']['workout_article_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/article/complete-guide-to-help-you-prepare-for-the-first-week-of-your-workout");
    		$data['link']['download_app_link'] = Config::get('app.download_app_link');
    		$data['link']['diet_plan_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/diet-plan");
    		$data['link']["profile_link"] = isset($profile_link) ? $profile_link : $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$orderArr['customer']['customer_email']);
    		
    		
    		
    		if(!empty($orderArr['coupon']))
    		{
    			$coupon=\Coupon::active()->whereIn("ratecard_type",['product'])->where("code",$orderArr['coupon'])->first();
    			if(!empty($coupon))
    			{
    				$coupon=$coupon->toArray();
    				if(!empty($coupon['once_per_user']))
    				{
    					$updated_coupon_customer= Customer::where('_id',$orderArr['customer']['customer_id'])->push('product_codes',$orderArr['coupon']);
    					Log::info(" info  updated_coupon_customer :: ".print_r($updated_coupon_customer,true));
    				}
    			}
    		}
    		
    		if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
    			$orderArr['payment']['membership_bought_at']='Fitternity Payu Mode';
    			
    			/* 			$count  = Order::where("status","1")->where('customer.customer_email',$order['customer']['customer_email'])->where('customer.customer_phone','LIKE','%'.substr($order['customer']['customer_phone'], -8).'%')->where('_id','!=',(int)$order->_id)->count();
    			
    			if($count > 0){
    			array_set($data, 'acquisition_type', 'renewal_direct');
    			array_set($data, 'membership_type', 'renewal');
    			}else{
    			array_set($data,'acquisition_type','direct_payment');
    			array_set($data['payment'], 'customer_purchase', 'new');
    			} */
    			
    			if($order['customer']['customer_source'] != 'admin'){
    				$orderArr['payment']['secondary_payment_mode']='payment_gateway_membership';
    			}
    		}
    		
    		$cart_data=(!empty($order['cart_data'])?$order['cart_data']:[]);
    		$cart=$this->utilities->attachCart($cart,true,$orderArr['customer']['logged_in_customer_id']);
    		
    		$cart_new=[];
    		
    		if(!empty($cart))
    		{
    			if(!empty($cart['products'])){
    				foreach ($cart['products'] as $value)
    				{
    					$tmpVal=$value;
    					if(!empty($value)&&!empty($value['ratecard_id']))
    					{
    						
    						$tmp_data=array_values(array_filter($cart_data,function ($e) use ($value) {return (!empty($e['ratecard'])&&!empty($e['ratecard']['_id'])&&$value['ratecard_id']== $e['ratecard']['_id']);}));
    						
    						if(!empty($tmp_data))
    						{
    							$tmp_data=$tmp_data[0];
    							if(!empty($tmp_data))
    							{
    								$tmpcnt=(intval($value['quantity'])>0?(intval($value['quantity'])-intval($tmp_data['quantity'])):0);
    								$tmpVal['quantity']=($tmpcnt<0)?0:$tmpcnt;
    							}
    						}
    					}
    					
    					($tmpVal['quantity']>0)?array_push($cart_new,$tmpVal):"";
    					
    				}
    				Log::info(" info  cart_new :: ".print_r($cart_new,true));
    				Cart::where("_id",intval($cart['_id']))->update(['products'=>$cart_new]);
    			}
    		}
    		$order->update($orderArr);
    		
    		//send welcome email to payment gateway customer
    		
    		$finder = Finder::find((int)$order['finder']['finder_id']);
    		
    		if (filter_var(trim($order['customer']['customer_email']), FILTER_VALIDATE_EMAIL) === false){
    			$order->update(['email_not_sent'=>'captureOrderStatus']);
    		}else{
    			$emailData = $order->toArray();
    			$emailData['near_options'] = $this->getNearBySessions($order);
                $sndPgMail  =   $this->customermailer->sendPgProductOrderMail($emailData);
                
                $products_string = '';

                foreach($order['cart_data'] as $key => $item){
                    if($key){
                        $products_string = $products_string.', ';
                    }
                    $products_string = $item['product']['title'];
                }
                
                $emailData['products_string'] = $products_string;
                $sndPgSms  =   $this->customersms->sendPgProductOrderSms($emailData);
    			
    			
    		}
    		// 			***************************************************************************************  EMAIL  ****************************************************************************************
    		
    		
    		// 			***************************************************************************************  SMS  ****************************************************************************************
    		//SEND payment gateway SMS TO CUSTOMER and vendor
    		/* if(!in_array($finder->category_id, $abundant_category)){
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
    		 
    		 if($this->utilities->checkCorporateLogin()){
    		 Log::info("outside checkCorporateLogin ");
    		 $emailData['customer_email'] =   $emailData['customer_email'].',vg@fitmein.in';
    		 }
    		 
    		 //print_pretty($emailData);exit;
    		 if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
    		 if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){
    		 
    		 $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
    		 }
    		 
    		 }else{
    		 $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
    		 }
    		 } */
    		
    		
    		
    		
    		//no sms to Healthy Snacks Beverages and Healthy Tiffins
    		/* 			if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan'){
    		
    		if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
    		if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){
    		
    		$sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
    		}
    		
    		}else{
    		$sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
    		}
    		
    		} */
    		
    		// 			***************************************************************************************  SMS  ****************************************************************************************
    		
    		// 		$this->utilities->sendCorporateMail($order->toArray());
    		
    		
    		
    		if(isset($order->city_id)){
    			$city = City::find((int)$order->city_id,['_id','name','slug']);
    			$order->update(['city_name'=>$city->name,'city_slug'=>$city->slug]);
    		}
    		
    		if(!empty($order['finder']['finder_category_id'])){
    			
    			$category = Findercategory::find((int)$order['finder']['finder_category_id'],['_id','name','slug']);
    			$order->update(['finder.category_name'=>$category->name,'finder.category_slug'=>$category->slug]);
    		}
    		
    		
    		/* if(isset($order->coupon_code)){
    		 $coupon = Coupon::where('code', strtolower($order['coupon_code']))->first();
    		 Log::info("coupon");
    		 $fitternity_only_coupon = ($coupon && isset($coupon->fitternity_only) && $coupon->fitternity_only) ? true : false;
    		 
    		 if(!$fitternity_only_coupon){
    		 $customer_update 	=	Customer::where('_id', $order->customer_id)->push('applied_promotion_codes', $order->coupon_code, true);
    		 }
    		 } */
    		
    		
    		// 		$this->utilities->setRedundant($order);
    		
    		// 		$this->utilities->addAmountToReferrer($order);
    		
    		// 		$this->utilities->addAssociateAgent($order);
    		
    		if(!empty($order['finder'])&&!empty($order['finder']['finder_id']))
    			
    			/* $start_date_last_30_days = date("d-m-Y 00:00:00", strtotime('-31 days',strtotime(date('d-m-Y 00:00:00'))));
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
    			 } */
    			
    			// 		$this->utilities->deleteCommunication($order);
    			
    			/* if(isset($order->redundant_order)){
    			 $order->unset('redundant_order');
    			 } */
    			
    			// 		$this->utilities->sendDemonetisationCustomerSms($order);
    			
    			// if(isset($order->customer_id)){
    			//     setDefaultAccount($order->toArray(), $order->customer_id);
    			// }
    			
    			$resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
    			
    			if(!empty($order['payment'])&&!empty($order['payment']['payment_mode'])&&$order['payment']['payment_mode'] == 'at the studio')
    			{
    				$resp   =   array('status' => 200,"message" => "Transaction Successful");
    			}
    			
    			return Response::json($resp);
    			
    	}else{
    		if($hash_verified == false){
    			$Oldorder 		= 	Order::findOrFail($order_id);
    			
    			$payment=$Oldorder['payment'];
    			$payment["hash_verified"]=false;
    			$Oldorder['payment']=$payment;
    			$Oldorder->update();
    			$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
    			return Response::json($resp);
    		}
    	}
    	
    	
    	$orderdata 		=	$order->update($data);
    	$resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
    	return Response::json($resp);
    	
    	
    }

    public function getCashbackRewardWallet($data,$order){

        $customer_id = (int)$data['customer_id'];

        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token : '.$jwt_token);
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }

        $customer = \Customer::find($customer_id);

        //************************************************************************************ IF ONLY AMOUNT CUSTOMER*******************************************************************************************
        
        
        if(isset($customer->demonetisation)){
			
            return $this->getCashbackRewardWalletNew($data,$order);

        }

        return $this->getCashbackRewardWalletOld($data,$order);

    }

    public function getCashbackRewardWalletNew($data,$order){

        
        addAToGlobals('ratecard_id_for_wallet', (!empty($order['ratecard_id']) ? $order['ratecard_id'] : 0));

        Log::info('new');

        $jwt_token = Request::header('Authorization');

        $customer_id = $data['customer_id'];
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
            $corporate_discount = !empty($decoded->customer->corporate_discount) ? $decoded->customer->corporate_discount : false;
        }
        $data['ratecard_amount'] = $data['amount'];
        if(!empty($data['customer_quantity'])){
            
            $data['ratecard_amount'] = $data['amount'];

            $data['amount_finder'] = $data['amount_finder'] * $data['customer_quantity'];

            $data['amount'] = $data['amount_customer'] = $data['amount_final'] = $data['amount'] * $data['customer_quantity'];

        }else if(!empty($order['customer_quantity'])){

            $data['ratecard_amount'] = $data['amount'];

            $data['amount_finder'] = $data['amount_finder'] * $order['customer_quantity'];

            $data['amount'] = $data['amount_customer'] = $data['amount_final'] = $data['amount'] * $order['customer_quantity'];
        }

        if(!empty($data['ticket_id']) && $data['ticket_id'] == 494 && !empty($data['ticket_quantity']) && is_integer($data['ticket_quantity']) && $data['ticket_quantity'] % 2 == 0){

            $data['ticket_discount'] = $data['ticket_quantity']/2 * 150;
            
            $data['amount_finder'] = $data['amount'] = $data['amount_customer'] = $data['amount_final'] = $data['amount'] - $data['ticket_discount'];

            if(empty($data['amount'])){
                $data['amount_finder'] = $data['amount'] = $data['amount_customer'] = $data['amount_final'] = 0;
            }
        
        }

        if(!empty($data['ratecard_flags']['free_sp'])){
            $data['amount_customer'] = $data['amount'] = 0;
        }

        if(!empty($data['studio_extended_validity_order_id']) && empty($data['studio_extended_session'])){
            $data['amount_customer'] = $data['amount'] = 0;
        }
        
        if(!empty($data['studio_extended_cancelled']['booktrial_id'])){
            $data['amount_customer'] = $data['amount'] = 0;
        }

        if(!empty($data['amount'] ) && !empty($data['finder_flags']['enable_commission_discount']) && (!empty($data['type']) && $data['type'] == 'memberships') && empty($data['extended_validity'])){
            $commission = getVendorCommision(['finder_id'=>$data['finder_id']]);
            $data['amount'] = $data['amount_customer'] = round($data['amount']  * (100 - $commission + Config::get('app.pg_charge'))/100); 
        }

        $amount = $data['amount_customer'] = $data['amount'];

        $convinience_fee = 0;

        if($this->utilities->isConvinienceFeeApplicable($data)){
            
            $convinience_fee_percent = Config::get('app.convinience_fee');

            $convinience_fee = round($data['amount_customer']*$convinience_fee_percent/100);

            $convinience_fee = $convinience_fee <= 199 ? $convinience_fee : 199;

            $amount += $convinience_fee;

            $data['amount_customer'] += $convinience_fee;

            $data['amount'] += $convinience_fee;

            $data['convinience_fee'] = $convinience_fee;
        }


        if(isset($_GET['device_type']) && isset($_GET['app_version']) && in_array($_GET['device_type'], ['android', 'ios']) && $_GET['app_version'] > '4.4.3'&&!empty($data['amount'])){
            
            if($data['type'] == 'workout-session' && !(isset($data['pay_later']) && $data['pay_later']) && !(isset($data['session_payment']) && $data['session_payment'])){
                Log::info("inside instant discount");
                
                $data['amount'] = $data['amount_customer'] = $data['amount_customer'] - $data['instant_payment_discount'];
    
                $amount  =  $data['amount'];
    
            }
        }    
        
        //  commented on 9th Aug - Akhil
        if($data['type'] == 'workout-session') {
            Order::$withoutAppends = true;
            $passSession = $this->passService->allowSession($data['amount'], $data['customer_id'], $data['schedule_date'], $data['finder_id']);
            if(
                $passSession['allow_session'] 
                && 
                (
                    (
                        !empty($data['service_flags']['classpass_available']) 
                        && 
                        $data['service_flags']['classpass_available']
                        &&
                        empty($passSession['onepass_lite'])
                    )
                    ||
                    (
                        !empty($passSession['onepass_lite'])
                        &&
                        !empty($data['service_flags']['lite_classpass_available'])
                        && 
                        $data['service_flags']['lite_classpass_available']
                    )
                )

            ) {
                $data['pass_type'] = $passSession['pass_type'];
                $data['pass_order_id'] = $passSession['order_id'];
                $data['pass_booking'] = true;

                if(!empty($passSession['pass_premium_session'])) {
                    $data['pass_premium_session'] = true;
                }
                if(!empty($passSession['pass_branding'])){
                    $data['pass_branding'] = $passSession['pass_branding'];
                }

                if(!empty($passSession['onepass_lite'])){
                    $data['pass_booking_lite'] = true;
                }
                $amount = 0;
            }
        }
        
        if(!empty($data['amount'] ) && $data['type'] == 'workout-session' && (empty($data['customer_quantity']) || $data['customer_quantity'] ==1)){
            Order::$withoutAppends = true;
            $extended_validity_order = $this->utilities->getExtendedValidityOrder($data);
            if($extended_validity_order){
                $data['extended_validity_order_id'] = $extended_validity_order['_id'];
                $data['session_pack_discount'] = $data['ratecard_amount'];
                $amount = $data['amount'] - $data['session_pack_discount'];
                // if(!empty($data['ratecard']['enable_vendor_novalidity_comm'])){
                    // $data['amount_finder'] = 0;
                    // $data['vendor_price'] = 0;
                // }
            }
        }        
        
        if(!empty($data['amount'] ) && $data['type'] == 'workout-session' && (empty($data['customer_quantity']) || $data['customer_quantity'] ==1)){
            Order::$withoutAppends = true;
            $studio_extended_validity_order = $this->utilities->getStudioExtendedValidityOrder($data);
            if($studio_extended_validity_order){
                $data['studio_extended_validity_order_id'] = $studio_extended_validity_order['_id'];
                $data['studio_extended_session'] = true;
                $data['session_pack_discount'] = $data['ratecard_amount'];
                $amount = $data['amount'] - $data['session_pack_discount'];
                // if(!empty($data['ratecard']['enable_vendor_novalidity_comm'])){
                    // $data['amount_finder'] = 0;
                    // $data['vendor_price'] = 0;
                    // }
                }
            }
            
            $first_session_free = false;
           if(empty($data['session_pack_discount']) && empty($order['session_pack_discount']) && ((!empty($order['init_source']) && $order['init_source'] == 'vendor') || (!empty($data['init_source']) && $data['init_source'] == 'vendor')) && $data['type'] == 'workout-session' && !empty($this->authorization) && (empty($data['customer_quantity']) || $data['customer_quantity'] == 1)){
            $free_trial_ratecard = Ratecard::where('service_id', $data['service_id'])->where('type', 'trial')->where('price', 0)->first();

            if($free_trial_ratecard){
                if(!$this->utilities->checkTrialAlreadyBooked($data['finder_id'], null, $data['customer_email'], $data['customer_phone'], true)){
                    $data['coupon_code'] = 'firstppsfree';
                    // $data['coupon_description'] = 'First wourkout session free';
                    // $data['coupon_discount_amount'] = $data['ratecard_amount'];
                    // $amount = $data['amount'] - $data['coupon_discount_amount'];
                    // $data['first_session_free'] = true;
                    // $data['amount_finder'] = 0;
                    // $data['vendor_price'] = 0;
                    $first_session_free = true;
                }
            }

        }
        
        if(!empty($corporate_discount) && $corporate_discount){
            Log::info("corporate_discount");
            Log::info("corporate_discount  :::", [$corporate_discount]);
            $coupons = Coupon::where('overall_coupon', true)->orderBy('overall_coupon_order', 'desc')->get(['code']);
            // return $coupon;
            if(!empty($coupons)){
                foreach($coupons as $coupon){
                    // if(!empty($coupon)){
                        Log::info("coupon_code :: ",[$coupon['code']]);
                        $ticket_quantity = isset($data['ticket_quantity'])?$data['ticket_quantity']:1;
                        $ticket = null;
        
                        if(isset($data['ticket_id'])){
                            $ticket = Ticket::find($data['ticket_id']);
                            if(!$ticket){
                                $resp = array('status'=>400, 'message'=>'Ticket not found');
                                return Response::json($resp, 400);
                            }
                        }
                        
                        $ratecard = isset($data['ratecard_id'])?Ratecard::find($data['ratecard_id']):null;
        
                        $service_id = isset($data['service_id']) ? $data['service_id'] : null;
        
                        $total_amount = null;
        
                        if(!empty($data['customer_quantity'])){
                            $total_amount = $data['amount'];
                        }
        
                        !empty($data['customer_email']) ? $customer_email = strtolower($data['customer_email']) : $customer_email = null;
        
                        $couponCheck1 = $this->customerreward->couponCodeDiscountCheck($ratecard,$coupon["code"],$customer_id, $ticket, $ticket_quantity, $service_id, $total_amount, $customer_email, null, null, $corporate_discount_coupon = true);
        
                        Log::info("couponCheck1");
                        Log::info($couponCheck1);
        
                        if(isset($couponCheck1["coupon_applied"]) && $couponCheck1["coupon_applied"]){
        
                            $data['corporate_discount_coupon_code'] = $coupon['code'];
        
                            if(isset($couponCheck1['vendor_commission'])){
                                $data['vendor_commission'] = $couponCheck1['vendor_commission'];
                            }
                            if(isset($couponCheck1['description'])){
                                $data['corporate_discount_coupon_description'] = $couponCheck1['description'];
                            }
                            
                            if(isset($couponCheck1['spin_coupon'])){
                                $data['corporate_discount_spin_coupon'] = $couponCheck1['spin_coupon'];
                            }else{
                                $data['corporate_discount_spin_coupon'] = "";
                            }
                            
                            if(isset($couponCheck1['coupon_discount_percent'])){
                                $data['corporate_discount_coupon_discount_percent'] = $couponCheck1['coupon_discount_percent'];
                            }else{
                                $data['corporate_discount_coupon_discount_percent'] = 0;
                            }
            
                            $data["corporate_discount_coupon_discount_amount"] = $amount > $couponCheck1["data"]["discount"] ? $couponCheck1["data"]["discount"] : $amount;
                            
                            $amount -= $data["corporate_discount_coupon_discount_amount"];
        
                            if(isset($couponCheck1["vendor_coupon"]) && $couponCheck1["vendor_coupon"]){
                                $data["payment_mode"] = "at the studio";
                                $data["secondary_payment_mode"] = "cod_membership";
                            }
            
                            if(!empty($couponCheck1['flags']['disc_by_vendor'])){
                                $data['amount_finder'] -= $data["coupon_discount_amount"];
                            }
            
                            if(!empty($couponCheck1['flags'])){
                                $data['corporate_discount_coupon_flags'] = $couponCheck1['flags'];
                            }
        
                            $total_amount = $data['amount_final'] - $data["corporate_discount_coupon_discount_amount"];
                            
                            break;
                        }
                    // }
                }
            }    
        }

        if(isset($data["coupon_code"]) && $data["coupon_code"] != ""){

            $ticket_quantity = isset($data['ticket_quantity'])?$data['ticket_quantity']:1;
            $ticket = null;

            if(isset($data['ticket_id'])){
                $ticket = Ticket::find($data['ticket_id']);
                if(!$ticket){
                    $resp = array('status'=>400, 'message'=>'Ticket not found');
                    return Response::json($resp, 400);
                }
            }
            
            $ratecard = isset($data['ratecard_id'])?Ratecard::find($data['ratecard_id']):null;

            $service_id = isset($data['service_id']) ? $data['service_id'] : null;

            if(empty($total_amount)){
                $total_amount = null;
            }

            if(!empty($data['customer_quantity'])){
                $total_amount = $amount;
            }

            !empty($data['customer_email']) ? $customer_email = strtolower($data['customer_email']) : $customer_email = null;

            $couponCheck = $this->customerreward->couponCodeDiscountCheck($ratecard,$data["coupon_code"],$customer_id, $ticket, $ticket_quantity, $service_id, $total_amount, $customer_email, null, $first_session_free);

            Log::info("couponCheck");
            Log::info($couponCheck);

            if(isset($couponCheck["coupon_applied"]) && $couponCheck["coupon_applied"]){

                if(isset($couponCheck['vendor_commission'])){
                    $data['vendor_commission'] = $couponCheck['vendor_commission'];
                }
                if(isset($couponCheck['description'])){
                    $data['coupon_description'] = $couponCheck['description'];
                }
                
                if(isset($couponCheck['spin_coupon'])){
                    $data['spin_coupon'] = $couponCheck['spin_coupon'];
                }else{
                    $data['spin_coupon'] = "";
                }
                
                if(isset($couponCheck['coupon_discount_percent'])){
                    $data['coupon_discount_percent'] = $couponCheck['coupon_discount_percent'];
                }else{
                    $data['coupon_discount_percent'] = 0;
                }

                $data["coupon_discount_amount"] = $amount > $couponCheck["data"]["discount"] ? $couponCheck["data"]["discount"] : $amount;

                $amount -= $data["coupon_discount_amount"];
                
                if(isset($couponCheck["vendor_coupon"]) && $couponCheck["vendor_coupon"]){
                    $data["payment_mode"] = "at the studio";
                    $data["secondary_payment_mode"] = "cod_membership";
                }

                if(!empty($couponCheck['flags']['disc_by_vendor'])){
                    $data['amount_finder'] -= $data["coupon_discount_amount"];
                }

                if(!empty($couponCheck['flags'])){
                    $data['coupon_flags'] = $couponCheck['flags'];
                }

                if(!empty($couponCheck['flags']['corporate_coupon']) && $couponCheck['flags']['corporate_coupon'] == true){
                    $data['corporate_coupon'] = true;
                }

                if(!empty($first_session_free) && $first_session_free && !empty($couponCheck['flags']['first_pps_free']) && $couponCheck['flags']['first_pps_free']){
                    $data['first_session_free'] = true;
                    $data['amount_finder'] = 0;
                    $data['vendor_price'] = 0;
                }
                // if(strtolower($data["coupon_code"]) == 'fit2018'){
                //     $data['routed_order'] = "1";
                // }
            }
            
        }else{

            if($order && isset($order['coupon_code'])){

                $order->unset(['coupon_code', 'coupon_discount_amount','coupon_flags']);
                // $order->unset('coupon_discount_amount');
            }

            // if($order && isset($order['routed_order'])){
                
            //     $order->unset('routed_order');
            
            // }

        }

        if($data['type'] != 'events'){

            if($data['type'] == "memberships" && isset($data['customer_source']) && (in_array($data['customer_source'],['android','ios','kiosk']))){

                $this->appOfferDiscount = in_array($data['finder_id'], $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;
                $data['app_discount_amount'] = intval($data['amount_finder'] * ($this->appOfferDiscount/100));

                $amount -= $data['app_discount_amount'];
            }

            $corporate_discount_percent = $this->utilities->getCustomerDiscount();
            $data['customer_discount_amount'] = intval($data['amount_finder'] * ($corporate_discount_percent/100));

            $amount -= $data['customer_discount_amount'];

            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($amount,$data['finder_id'],'paymentgateway',$data['offer_id'],false,false,$convinience_fee,$data['type'],$data);
            Log::info("cashback_detail",[$cashback_detail]);
            if(isset($data['cashback']) && $data['cashback'] == true){
                $amount -= $data['cashback_detail']['amount_discounted'];
            }

            if(!isset($data['repetition'])){

                if(isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){
                    $amount -= $data['cashback_detail']['amount_deducted_from_wallet'];
                }

                if(isset($data['wallet']) && $data['wallet'] == true){
                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                }

                if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                        'finder_id'=>$data['finder_id'],
                        'order_type'=>$data['type'],
                        'extended_validity'=>!empty($data['extended_validity']) || !empty($order['extended_validity'])
                    );

                    $walletTransactionResponse = $this->utilities->walletTransactionNew($req, $data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }else{

                        if(isset($walletTransactionResponse['wallet_transaction_debit']['wallet_transaction'])){
                            foreach($walletTransactionResponse['wallet_transaction_debit']['wallet_transaction'] as $k => $v){
                                if(!empty($v['fitcashcoupon_flags']['corporate_coupon'])){
                                    $data['corporate_coupon'] = true;
                                    break;
                                }
                            }
                        }

                        $data['wallet_transaction_debit'] = $walletTransactionResponse['wallet_transaction_debit'];
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                }

            }else{

                $new_data = Input::json()->all();

                if(isset($new_data['wallet']) && $new_data['wallet'] == true){


                    if(!empty($order['wallet']) && !empty($order['wallet_amount']) && !empty($order['cashback_detail'])){


                        $data['cashback_detail'] = $order['cashback_detail'];

                        if(isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){
                            $amount -= $data['cashback_detail']['amount_deducted_from_wallet'];
                        }


                    //                        $req = array(
                    //                            'customer_id'=>$order['customer_id'],
                    //                            'order_id'=>$order['_id'],
                    //                            'amount'=>$order['wallet_amount'],
                    //                            'type'=>'REFUND',
                    //                            'entry'=>'credit',
                    //                            'description'=>'Refund for Order ID: '.$order['_id'],
                    //                            'full_amount'=>true,
                    //                        );
                    //
                    //                        $walletTransactionResponse = $this->utilities->walletTransactionNew($req);
                    //
                    //                        if(isset($order['wallet_refund_sidekiq']) && $order['wallet_refund_sidekiq'] != ''){
                    //                            try {
                    //                                $this->sidekiq->delete($order['wallet_refund_sidekiq']);
                    //                            }catch(\Exception $exception){
                    //                                Log::error($exception);
                    //                            }
                    //                        }
                    //
                    //                        $order->unset('wallet', 'wallet_amount');
                                            // $order->unset('wallet_amount');

                    }else{

                        // $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($amount,$data['finder_id'],'paymentgateway',$data['offer_id'],false,false,$convinience_fee,$data['type']);


                        if(isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){
                            $amount -= $data['cashback_detail']['amount_deducted_from_wallet'];
                        }

                        $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];

                        if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                            $req = array(
                                'customer_id'=>$data['customer_id'],
                                'order_id'=>$data['order_id'],
                                'amount'=>$data['wallet_amount'],
                                'type'=>'DEBIT',
                                'entry'=>'debit',
                                'description'=> $this->utilities->getDescription($data),
                                'finder_id'=>$data['finder_id'],
                                'order_type'=>$data['type'],
                                'city_id'=>$data['city_id'],
                                'extended_validity'=>!empty($data['extended_validity']) || !empty($order['extended_validity'])
                            );
                            $walletTransactionResponse = $this->utilities->walletTransactionNew($req);

                            if($walletTransactionResponse['status'] != 200){
                                return $walletTransactionResponse;
                            }else{
                                $data['wallet_transaction_debit'] = $walletTransactionResponse['wallet_transaction_debit'];
                            }

                            // Schedule Check orderfailure and refund wallet amount in that case....
                            $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                            $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                            $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);
                        }
                    }


                }else{

                    if(isset($order['wallet_amount']) && $order['wallet_amount'] != "" && $order['wallet_amount'] != 0){

                        $req = array(
                            'customer_id'=>$order['customer_id'],
                            'order_id'=>$order['_id'],
                            'amount'=>$order['wallet_amount'],
                            'type'=>'REFUND',
                            'entry'=>'credit',
                            'description'=>'Refund for Order ID: '.$order['_id'],
                            'full_amount'=>true,
                        );

                        $walletTransactionResponse = $this->utilities->walletTransactionNew($req);
                        
                        if(isset($order['wallet_refund_sidekiq']) && $order['wallet_refund_sidekiq'] != ''){
                            try {
                                $this->sidekiq->delete($order['wallet_refund_sidekiq']);
                            }catch(\Exception $exception){
                                Log::error($exception);
                            }
                        }

                        $order->unset('wallet', 'wallet_amount');
                        // $order->unset('wallet_amount');   

                    }

                    $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($amount,$data['finder_id'],'paymentgateway',$data['offer_id'],false,false,$convinience_fee,$data['type'], $data);

                    if(isset($data['cashback']) && $data['cashback'] == true){
                        $amount -= $data['cashback_detail']['amount_discounted'];
                    }

                    if(isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0){
                        $amount -= $data['cashback_detail']['amount_deducted_from_wallet'];
                    }

                }

            }

        }
        
        $data['amount_final'] = $amount;

        if(isset($data['wallet']) && $data['wallet'] == true){
            $data['amount'] = $amount;
        }

        if($data['amount'] == 0){
            $data['full_payment_wallet'] = true;
        }else{
            $data['full_payment_wallet'] = false;
        }
        
        if($this->utilities->checkCorporateLogin()){
            $data["payment_mode"] = "at the studio";
            $data['full_payment_wallet'] = true;
        }
        
        if(isset($data['reward_ids'])&& count($data['reward_ids']) > 0) {
            $data['reward_ids']   =  array_map('intval', $data['reward_ids']);
        }

        return array('status' => 200,'data' => $data); 

    }

    public function getCashbackRewardWalletOld($data,$order){

        Log::info('old');

        $jwt_token = Request::header('Authorization');

        $customer_id = $data['customer_id'];
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
        }

        $amount = $data['amount_customer'] = $data['amount'];

        if($data['type'] == "memberships" && isset($data['customer_source']) && (in_array($data['customer_source'],['android','ios','kiosk']))){
            $this->appOfferDiscount = in_array($data['finder_id'], $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;
            $data['app_discount_amount'] = intval($data['amount'] * ($this->appOfferDiscount/100));
            $amount = $data['amount'] = $data['amount_customer'] = $data['amount'] - $data['app_discount_amount'];
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id'],false,false,$data['type']);
        }else{
            $cashback_detail = $data['cashback_detail'] = $this->customerreward->purchaseGame($data['amount_finder'],$data['finder_id'],'paymentgateway',$data['offer_id'],$data['customer_id'],false,false,$data['type']);
        }

        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios'])){


            if(isset($data['cashback']) && $data['cashback'] == true){
                $data['amount'] = $data['amount'] - $data['cashback_detail']['amount_discounted'];
            }

            if(!isset($data['repetition'])){

                if(isset($data['wallet']) && $data['wallet'] == true){
                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                    $data['amount'] = $data['amount'] - $data['wallet_amount'];
                }

                if(isset($data['wallet_amount']) && $data['wallet_amount'] > 0){

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'amount_fitcash' => $data['wallet_amount'],
                        'amount_fitcash_plus' => 0,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                }

            }else{

                $new_data = Input::json()->all();

                if(isset($new_data['wallet']) && $new_data['wallet'] == true){

                    $data['wallet_amount'] = $data['cashback_detail']['amount_deducted_from_wallet'];
                    $data['amount'] = $data['amount'] - $data['wallet_amount'];

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'amount_fitcash' => $data['wallet_amount'],
                        'amount_fitcash_plus' => 0,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                }else{

                    if(isset($order['wallet_amount']) && $order['wallet_amount'] != "" && $order['wallet_amount'] != 0){

                        $req = array(
                            'customer_id'=>$order['customer_id'],
                            'order_id'=>$order['_id'],
                            'amount'=>$order['wallet_amount'],
                            'type'=>'REFUND',
                            'entry'=>'credit',
                            'description'=>'Refund for Order ID: '.$order['_id'],
                        );

                        $walletTransactionResponse = $this->utilities->walletTransaction($req,$order->toArray());
                        
                        if(isset($order['wallet_refund_sidekiq']) && $order['wallet_refund_sidekiq'] != ''){
                            try {
                                $this->sidekiq->delete($order['wallet_refund_sidekiq']);
                            }catch(\Exception $exception){
                                Log::error($exception);
                            }
                        }

                        $order->unset('wallet', 'wallet_amount');
                        // $order->unset('wallet_amount');
                    }

                }

            }

            

        }else{

            if(isset($data['cashback']) && $data['cashback'] == true){
                $amount = $data['amount'] - $cashback_detail['amount_discounted'];
            }

            if(!isset($data['repetition'])){

                if(isset($data['wallet']) && $data['wallet'] == true){

                    $wallet_amount = $data['wallet_amount'] = $cashback_detail['only_wallet']['fitcash'] + $cashback_detail['only_wallet']['fitcash_plus'];

                    $fitcash = $cashback_detail['only_wallet']['fitcash'];
                    $fitcash_plus = $cashback_detail['only_wallet']['fitcash_plus'];

                    if(isset($data['cashback']) && $data['cashback'] == true){

                        $wallet_amount = $data['wallet_amount'] = $cashback_detail['discount_and_wallet']['fitcash'] + $cashback_detail['discount_and_wallet']['fitcash_plus'];

                        $fitcash = $cashback_detail['discount_and_wallet']['fitcash'];
                        $fitcash_plus = $cashback_detail['discount_and_wallet']['fitcash_plus'];
                    }

                    $amount = $data['amount'] - $wallet_amount;

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'amount_fitcash' => $fitcash,
                        'amount_fitcash_plus' => $fitcash_plus,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);
                }

            }else{

                $new_data = Input::json()->all();

                if(isset($new_data['wallet']) && $new_data['wallet'] == true){

                    $wallet_amount = $data['wallet_amount'] = $cashback_detail['only_wallet']['fitcash'] + $cashback_detail['only_wallet']['fitcash_plus'];

                    $fitcash = $cashback_detail['only_wallet']['fitcash'];
                    $fitcash_plus = $cashback_detail['only_wallet']['fitcash_plus'];

                    if(isset($data['cashback']) && $data['cashback'] == true){

                        $wallet_amount = $data['wallet_amount'] = $cashback_detail['discount_and_wallet']['fitcash'] + $cashback_detail['discount_and_wallet']['fitcash_plus'];

                        $fitcash = $cashback_detail['discount_and_wallet']['fitcash'];
                        $fitcash_plus = $cashback_detail['discount_and_wallet']['fitcash_plus'];
                    }

                    $amount = $data['amount'] - $wallet_amount;

                    $req = array(
                        'customer_id'=>$data['customer_id'],
                        'order_id'=>$data['order_id'],
                        'amount'=>$data['wallet_amount'],
                        'amount_fitcash' => $fitcash,
                        'amount_fitcash_plus' => $fitcash_plus,
                        'type'=>'DEBIT',
                        'entry'=>'debit',
                        'description'=> $this->utilities->getDescription($data),
                    );
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$data);
                    
                    if($walletTransactionResponse['status'] != 200){
                        return $walletTransactionResponse;
                    }

                    // Schedule Check orderfailure and refund wallet amount in that case....
                    $url = Config::get('app.url').'/orderfailureaction/'.$data['order_id'].'/'.$customer_id;
                    $delay = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addHours(4);
                    $data['wallet_refund_sidekiq'] = $this->hitURLAfterDelay($url, $delay);

                }else{

                    if(isset($order['wallet_amount']) && $order['wallet_amount'] != "" && $order['wallet_amount'] != 0){

                        $req = array(
                            'customer_id'=>$order['customer_id'],
                            'order_id'=>$order['_id'],
                            'amount'=>$order['wallet_amount'],
                            'type'=>'REFUND',
                            'entry'=>'credit',
                            'description'=>'Refund for Order ID: '.$order['_id'],
                        );

                        $walletTransactionResponse = $this->utilities->walletTransaction($req,$order->toArray());
                        
                        if(isset($order['wallet_refund_sidekiq']) && $order['wallet_refund_sidekiq'] != ''){
                            try {
                                $this->sidekiq->delete($order['wallet_refund_sidekiq']);
                            }catch(\Exception $exception){
                                Log::error($exception);
                            }
                        }

                        $order->unset('wallet', 'wallet_amount');
                        // $order->unset('wallet_amount');
                    }

                }
            }

            $data['amount'] = $amount;
        }

        if($data['amount'] == 0){
            $data['full_payment_wallet'] = true;
        }else{
            $data['full_payment_wallet'] = false;
        }
        
        if(isset($data['reward_ids'])&& count($data['reward_ids']) > 0) {
            $data['reward_ids']   =  array_map('intval', $data['reward_ids']);
        }

        return array('status' => 200,'data' => $data); 

    }

    public function orderFailureAction($order_id){

        $order = Order::where('_id',(int) $order_id)->where('status',"0")->first();

        if($order == ''){
            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'No Action Required'
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

        // Refund wallet amount if deducted........
        if(isset($order['wallet_amount']) && ((int) $order['wallet_amount']) >= 0){
            $req = array(
                'customer_id'=>$order['customer_id'],
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

    public function getManualOrderDetail($data){

        $data['ratecard_remarks']  = (isset($data['remarks'])) ? $data['remarks'] : "";
        $data['duration'] = (isset($data['duration'])) ? $data['duration'] : "";
        $data['duration_type'] = (isset($data['duration_type'])) ? $data['duration_type'] : "";
        $data['duration_day'] = $duration_day = 0;

        $data['service_duration'] = $data['validity']." ".ucwords($data['validity_type']);

        if($data['validity'] > 1){

            $data['service_duration'] = $data['validity']." ".ucwords($data['validity_type'])."s";
        }

        if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '' && $data['preferred_starting_date']  != '-'){

            $preferred_starting_date = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']));
            $data['start_date'] = $preferred_starting_date;
            $data['preferred_starting_date'] = $preferred_starting_date;
        }

        if(isset($data['preferred_payment_date']) && $data['preferred_payment_date']  != '' && $data['preferred_payment_date']  != '-'){

            $preferred_payment_date = date('Y-m-d 00:00:00', strtotime($data['preferred_payment_date']));
            $data['start_date'] = $preferred_payment_date;
            $data['preferred_payment_date'] = $preferred_payment_date;
        }

        if(!empty($data['validity']) && !empty($data['validity_type'])){

            switch ($data['validity_type']){
                case 'day': 
                case 'days': 
                    $data['duration_day'] = $duration_day = (int)$data['validity'];break;
                case 'month':
                case 'months': 
                    $data['duration_day'] = $duration_day = (int)($data['validity'] * 30) ; break;
                case 'year':
                case 'years':
                    $data['duration_day'] = $duration_day = (int)($data['validity'] * 30 * 12); break;
                default : $data['duration_day'] = $duration_day =  $data['validity']; break;
            }

            if(isset($data['start_date']) && $data['start_date']  != '' && $data['start_date']  != '-'){
                $data['end_date'] = date('Y-m-d 00:00:00', strtotime($data['start_date']."+ ".($duration_day-1)." days"));
            }
        }

        $data['amount_finder'] = $data['amount'];
        $data['amount_customer'] = $data['amount'];
        $data['batch_time'] = "";
        $data['offer_id'] = false;

        $set_vertical_type = array(
            'healthytiffintrail'=>'tiffin',
            'healthytiffinmembership'=>'tiffin',
            'memberships'=>'workout',
            'booktrials'=>'workout',
            'workout-session'=>'workout',
            '3daystrial'=>'workout',
            'vip_booktrials'=>'workout',
            'events'=>'event',
            'diet_plan'=>'diet_plan'
        );

        $set_membership_duration_type = array(
            'healthytiffintrail'=>'trial',
            'healthytiffinmembership'=>'short_term_membership',
            'memberships'=>'short_term_membership',
            'booktrials'=>'trial',
            'workout-session'=>'workout_session',
            '3daystrial'=>'trial',
            'vip_booktrials'=>'vip_trial',
            'events'=>'event',
            'diet_plan'=>'short_term_membership'
        );

        (isset($data['type']) && isset($set_vertical_type[$data['type']])) ? $data['vertical_type'] = $set_vertical_type[$data['type']] : null;

        (isset($data['type']) && isset($set_membership_duration_type[$data['type']])) ? $data['membership_duration_type'] = $set_membership_duration_type[$data['type']] : null;

        (isset($data['duration_day']) && $data['duration_day'] >= 30 && $data['duration_day'] <= 90) ? $data['membership_duration_type'] = 'short_term_membership' : null;

        (isset($data['duration_day']) && $data['duration_day'] > 90 ) ? $data['membership_duration_type'] = 'long_term_membership' : null;
        $data['secondary_payment_mode'] = 'payment_gateway_tentative';
        $data['finder_id'] = (int)$data['finder_id'];
        $data['service_id'] = (!empty($data['service_id'])) ? $data['service_id'] : null;
        
        $data['service_name_purchase'] =  $data['service_name'];
        $data['service_duration_purchase'] =  $data['service_duration'];
        $data['status'] =  '0';
        $data['payment_mode'] =  'paymentgateway';
        $data['source_of_membership'] =  'real time';

        return array('status' => 200,'data' =>$data);

    }

    public function getProductRatecardDetail($data){
    	
    	
    	$ratecard = ProductRatecard::find((int)$data['ratecard_id']);
    	
    	if(!$ratecard){
    		return array('status' => 404,'message' =>'Ratecard does not exists');
    	}
    	
    	$ratecard = $ratecard->toArray();
    	
    }

    public function getRatecardDetail($data){

        $ratecard = Ratecard::find((int)$data['ratecard_id']);

        if(!empty($ratecard['flags']['free_sp']) && empty($data['parent_order_id'])){
            return array('status' => 404,'message' =>'Ratecard does not exists');
        }

        if(!$ratecard){
            return array('status' => 404,'message' =>'Ratecard does not exists');
        }

        $ratecard = $ratecard->toArray();

        if(isset($ratecard['flags']) && empty($this->device_type)){

            if(isset($ratecard['flags']['pay_at_vendor']) && $ratecard['flags']['pay_at_vendor']){
                $data['ratecard_pay_at_vendor'] = true;
            }
        }

        $data['service_duration'] = $this->getServiceDuration($ratecard);

        $data['ratecard_remarks']  = (isset($ratecard['remarks'])) ? $ratecard['remarks'] : "";
        $data['duration'] = (isset($ratecard['duration'])) ? $ratecard['duration'] : "";
        $data['duration_type'] = (isset($ratecard['duration_type'])) ? $ratecard['duration_type'] : "";
        $data['validity'] = (isset($ratecard['validity'])) ? $ratecard['validity'] : "";
        $data['validity_type'] = (isset($ratecard['validity_type'])) ? $ratecard['validity_type'] : "";
        if(!empty($ratecard['flags']['onepass_attachment_type']) && (isset($ratecard['combo_pass_id']))) {
            $data['combo_pass_id'] = $ratecard['combo_pass_id'];
        }

        if($ratecard['type'] == 'workout session' && !empty($ratecard['vendor_price'])){
            $data['vendor_price'] = $ratecard['vendor_price'];
        }

        if(!isset($data['type'])){
            $data['type'] = $ratecard['type'];
        }

        

        if($ratecard['finder_id'] == 8892 && $ratecard['type'] == 'workout session'){
            $data['vendor_price'] = 990;
        }

        if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '' && $data['preferred_starting_date']  != '-'){

            $preferred_starting_date = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']));
            $data['start_date'] = $preferred_starting_date;
            $data['preferred_starting_date'] = $preferred_starting_date;
        }

        if(isset($data['preferred_payment_date']) && $data['preferred_payment_date']  != '' && $data['preferred_payment_date']  != '-'){

            $preferred_payment_date = date('Y-m-d 00:00:00', strtotime($data['preferred_payment_date']));
            $data['start_date'] = $preferred_payment_date;
            $data['preferred_payment_date'] = $preferred_payment_date;
        }

        if(isset($ratecard['validity']) && $ratecard['validity'] != ""){

            switch ($ratecard['validity_type']){
                case 'days': 
                case 'day': 
                    $data['duration_day'] = $duration_day = (int)$ratecard['validity'];break;
                case 'months': 
                case 'month': 
                    $data['duration_day'] = $duration_day = (int)($ratecard['validity'] * 30) ; break;
                case 'year': 
                case 'years': 
                    $data['duration_day'] = $duration_day = (int)($ratecard['validity'] * 30 * 12); break;
                default : $data['duration_day'] = $duration_day =  $ratecard['validity']; break;
            }

            if(isset($data['preferred_starting_date']) && $data['preferred_starting_date']  != '' && $data['preferred_starting_date']  != '-'){
                $data['end_date'] = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']."+ ".($duration_day-1)." days"));
            }
        }

        if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
            $data['amount_finder'] = $ratecard['special_price'];
        }else{
            $data['amount_finder'] = $ratecard['price'];
        }

        $data['ratecard_price_wo_offer'] = $data['amount_finder'];

        if(!empty($ratecard['price'])){
            $data['ratecard_original_price'] = $ratecard['price'];
        }

        $data['offer_id'] = false;

        // $offer = Offer::where('ratecard_id',$ratecard['_id'])
        //         ->where('hidden', false)
        //         ->orderBy('order', 'asc')
        //         ->where('start_date','<=',new DateTime(date("d-m-Y 00:00:00")))
        //         ->where('end_date','>=',new DateTime(date("d-m-Y 00:00:00")))
        //         ->first();

        $offer = Offer::getActiveV1('ratecard_id', intval($ratecard['_id']), intval($ratecard['finder_id']))->first();
        
        if($offer){
            if(isset($ratecard["flags"]) && isset($ratecard["flags"]["pay_at_vendor"]) && $ratecard["flags"]["pay_at_vendor"]){
                $ratecard['offer_convinience_fee'] = $data['offer_convinience_fee'] = false;    
            }else{
                $ratecard['offer_convinience_fee'] = $data['offer_convinience_fee'] = true;
            }
            $data['amount_finder'] = $offer->price;
            $data['offer_id'] = $offer->_id;

            if(isset($offer->remarks) && $offer->remarks != ""){
                $data['ratecard_remarks'] = $offer->remarks;
            }
            if(!empty($offer->vendor_price) && empty($data['third_party']))
            {
                $data['vendor_price'] = $offer->vendor_price;
            }
        }

        if(isset($data['manual_order']) && $data['manual_order']){
            $data['amount_finder'] = $data['amount'];
        }

        if(isset($data['schedule_date']) && $data['schedule_date'] != ""){
        	
        	$schedule_date = date('Y-m-d 00:00:00', strtotime($data['schedule_date']));
        	array_set($data, 'start_date', $schedule_date);
        	
        	array_set($data, 'end_date', $schedule_date);
        	
        	$data['membership_duration_type'] = 'workout_session';
        }
        
        if(isset($data['schedule_slot']) && $data['schedule_slot'] != ""){
        	
        	$schedule_slot = explode("-", $data['schedule_slot']);
        	
        	$data['start_time'] = trim($schedule_slot[0]);
        	if(count($schedule_slot) == 1){
        		$data['end_time'] = date('g:i a', strtotime('+1 hour', strtotime($schedule_slot[0])));
        		$data['schedule_slot'] = $schedule_slot[0].'-'.$data['end_time'];
        	}else{
        		$data['end_time']= trim($schedule_slot[1]);
        	}
        }
        
        $data['amount'] = $data['amount_finder'];
        

        if($ratecard['type'] == 'extended validity'){
            $data['type'] = 'memberships';
            $data['no_of_sessions'] = $data['sessions_left'] = $ratecard['duration'];
            $data['extended_validity'] = true;
            // $data['amount_finder'] = 0;
        }

       /* $corporate_discount_percent = $this->utilities->getCustomerDiscount();
        $data['customer_discount_amount'] = intval($data['amount'] * ($corporate_discount_percent/100));
        $data['amount'] = $data['amount'] - $data['customer_discount_amount'];*/

        $medical_detail                     =   (isset($data['medical_detail']) && $data['medical_detail'] != '') ? $data['medical_detail'] : "";
        $medication_detail                  =   (isset($data['medication_detail']) && $data['medication_detail'] != '') ? $data['medication_detail'] : "";

        if($medical_detail != "" && $medication_detail != ""){

            $customer_info = new CustomerInfo();
            $response = $customer_info->addHealthInfo($data);
        }

        if(isset($data['schedule_date']) && $data['schedule_date'] != ""){

            $schedule_date = date('Y-m-d 00:00:00', strtotime($data['schedule_date']));
            array_set($data, 'start_date', $schedule_date);

            array_set($data, 'end_date', $schedule_date);
            
            $data['membership_duration_type'] = 'workout_session';
        }

        if(isset($data['schedule_slot']) && $data['schedule_slot'] != ""){
            
            $schedule_slot = explode("-", $data['schedule_slot']);

            //$data['start_time'] = trim($schedule_slot[0]);

            $data['start_time'] = date("h:i a", strtotime(trim($schedule_slot[0])));

            if(count($schedule_slot) == 1){
                $data['end_time'] = date('h:i a', strtotime('+1 hour', strtotime($schedule_slot[0])));
                $data['schedule_slot'] = $schedule_slot[0].'-'.$data['end_time'];
            }else{
                $data['end_time']= trim($schedule_slot[1]);
            }

            $data['schedule_slot'] = $data['start_time'].'-'.$data['end_time'];
        }

        $batch = array();
        
        $data['batch_time'] = "";
        
        
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

            foreach ($batch as $key => $value) {

                if(isset($value['slots'][0]['start_time']) && $value['slots'][0]['start_time'] != ""){
                    $data['batch_time'] = strtoupper($value['slots'][0]['start_time']);
                    break;
                }
            }

            $data['batch'] = $batch;
        }

        $set_vertical_type = array(
            'healthytiffintrail'=>'tiffin',
            'healthytiffinmembership'=>'tiffin',
            'memberships'=>'workout',
            'booktrials'=>'workout',
            'workout-session'=>'workout',
            '3daystrial'=>'workout',
            'vip_booktrials'=>'workout',
            'events'=>'event',
            'diet_plan'=>'diet_plan'
        );

        $set_membership_duration_type = array(
            'healthytiffintrail'=>'trial',
            'healthytiffinmembership'=>'short_term_membership',
            'memberships'=>'short_term_membership',
            'booktrials'=>'trial',
            'workout-session'=>'workout_session',
            '3daystrial'=>'trial',
            'vip_booktrials'=>'vip_trial',
            'events'=>'event',
            'diet_plan'=>'short_term_membership'
        );

        (isset($data['type']) && isset($set_vertical_type[$data['type']])) ? $data['vertical_type'] = $set_vertical_type[$data['type']] : null;

        if(isset($data['finder_category_id'])){

            switch ($data['finder_category_id']) {
                case 41 : $data['vertical_type'] = 'trainer';break;
                case 45 : $data['vertical_type'] = 'package';break;
                default: break;
            }

        }

       (isset($data['type']) && isset($set_membership_duration_type[$data['type']])) ? $data['membership_duration_type'] = $set_membership_duration_type[$data['type']] : null;

        (isset($data['duration_day']) && $data['duration_day'] >= 30 && $data['duration_day'] <= 90) ? $data['membership_duration_type'] = 'short_term_membership' : null;

        (isset($data['duration_day']) && $data['duration_day'] > 90 ) ? $data['membership_duration_type'] = 'long_term_membership' : null;
        $data['secondary_payment_mode'] = 'payment_gateway_tentative';
        $data['finder_id'] = (int)$ratecard['finder_id'];
        $data['service_id'] = (int)$ratecard['service_id'];
        
        $service = Service::select('name')->find($data['service_id']);
        $data['service_name_purchase'] =  $service['name'];
        $data['service_duration_purchase'] =  $data['service_duration'];
        $data['status'] =  '0';
        $data['payment_mode'] =  'paymentgateway';
        $data['source_of_membership'] =  'real time';
        $data['ratecard_flags'] = isset($ratecard['flags']) ? $ratecard['flags'] : array();
        // if($this->convinienceFeeFlag() && $this->utilities->isConvinienceFeeApplicable($ratecard)){

            
        // }

        if(!empty($ratecard['flags']['onepass_attachment_type']) && !empty($ratecard['combo_pass_id'])){
            $data['combo_pass_id'] = $ratecard['combo_pass_id'];
        }

        return array('status' => 200,'data' =>$data);

    }

    public function getServiceDetail($service_id){

        $data = array();
        
        $service = Service::active()->find((int)$service_id);

        if(!$service){
            return array('status' => 404,'message' =>'Service does not exists');
        }

        $service = $service->toArray();

        $data['finder_address'] = (isset($service['address']) && $service['address'] != "") ? $service['address'] : "-";
        $data['service_name'] = ucwords($service['name']);
        $data['meal_contents'] = $this->stripTags($service['short_description']);
        (isset($service['diet_inclusive'])) ? $data['diet_inclusive'] = $service['diet_inclusive'] : null;
        $data['finder_address'] = (isset($service['address']) && $service['address'] != "") ? $service['address'] : "-";
        $data['servicecategory_id'] = (isset($service['servicecategory_id'])) ? $service['servicecategory_id'] : 0;
        if(!empty($service['flags'])){
            $data['service_flags'] = $service['flags'];
        }
        if(!empty($service['combine_service_ids'])){
            $data['all_service_id'] = $service['combine_service_ids'];
        }
        
        return array('status' => 200,'data' =>$data);

    }

    public function stripTags($string){
        return ucwords(str_replace("&nbsp;","",strip_tags($string)));
    }

    public function getFinderDetail($finder_id){

        $data = array();

        $finder                            =   Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->find((int)$finder_id);

        if(!$finder){
            return array('status' => 404,'message' =>'Vendor does not exists');
        }

        $finder = $finder->toArray();

        $finder_city                       =    (isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
        $finder_city_slug                  =    (isset($finder['city']['slug']) && $finder['city']['slug'] != '') ? $finder['city']['slug'] : "";
        $finder_location                   =    (isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
        $finder_location_slug                  =    (isset($finder['location']['slug']) && $finder['location']['slug'] != '') ? $finder['location']['slug'] : "";
        $finder_address                    =    (isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $this->stripTags($finder['contact']['address']) : "";
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
        $city_id                           =    $finder['city_id'];
        $finder_category                       =    (isset($finder['category']['name']) && $finder['category']['name'] != '') ? $finder['category']['name'] : "";
        $finder_category_slug                  =    (isset($finder['category']['slug']) && $finder['category']['slug'] != '') ? $finder['category']['slug'] : "";
        $finder_flags                       =   isset($finder['flags'])  ? $finder['flags'] : new stdClass();
        $finder_notes                        =    (isset($finder['notes']) && $finder['notes'] != '') ? $finder['notes'] : "";
        $brand_id                        =    (!empty($finder['brand_id'])) ? $finder['brand_id'] : 0;
        
        $data['finder_city'] =  trim($finder_city);
        $data['finder_location'] =  ucwords(trim($finder_location));
        $data['finder_location_slug'] =  trim($finder_location_slug);
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
        $data['city_id'] =  $city_id;
        $data['city_name'] = $finder_city;
        $data['city_slug'] = $finder_city_slug;
        $data['category_name'] = $finder_category;
        $data['category_slug'] = $finder_category_slug;
        $data['finder_flags'] = $finder_flags;
        $data['finder_notes'] = $finder_notes;
        $data['trial'] = !empty($finder['trial']) ? $finder['trial'] : 'auto';

        if(!empty($brand_id)){
            $data['brand_id'] = $brand_id;
        }
        
        return array('status' => 200,'data' =>$data);
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

        if($ratecard['duration'] > 0){
            $service_duration .= $ratecard['duration'] ." ".ucwords($ratecard['duration_type']);
        }
        if($ratecard['duration'] > 0 && $ratecard['validity'] > 0){
            $service_duration .= " - ";
        }
        if($ratecard['validity'] > 0){
            $service_duration .=  $ratecard['validity'] ." ".ucwords($ratecard['validity_type']);
        }

        ($service_duration == "") ? $service_duration = "-" : null;

        return $service_duration;
    }

    public function getHash($data){

        $env = (isset($data['env']) && $data['env'] == 1) ? "stage" : "production";

        $data['service_name'] = trim($data['service_name']);
        $data['finder_name'] = trim($data['finder_name']);

        $service_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", '', $data['service_name']);
        $finder_name = preg_replace("/^'|[^A-Za-z0-9 \-]|'$/", '', $data['finder_name']);

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

    public function pg(){

        $data = Input::json()->all();

        $rules = [
            'order_id',
            'pg_type'
        ];

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }

        $order_id = $data['order_id'];

        $order = Order::find((int) $order_id);

        if(!$order){
            return Response::json(array('status' => 404,'message' => 'Order not found'),404);
        }

        if($order->status == "1"){
            return Response::json(array('status' => 404,'message' => 'Order already success'),404);
        }

        $order->pg_type_selected = $data['pg_type'];
        $order->pg_date = date('Y-m-d H:i:s',time());
        $order->update();

        $response   =   array(
            'status' => 200,
            'message' => "PG Captured Sucessfully"
        );

        return Response::json($response,$response['status']);
    }

    public  function sendCommunication($job,$data){

        $job->delete();

        try {
            $order_id = (int)$data['order_id'];

            $order = Order::find($order_id);

            if(isset($order['type']) && $order['type'] == 'wallet' && !empty($order['customer_phone'])){
                
                $this->customersms->walletRecharge($order->toArray());
                
                return "success";
            
            }
            
            Log::info("Order id", [$order['_id']]);
            Log::info("Order status", [$order['status']]);
            Log::info("Order payment mode", [$order['payment_mode']]);

            if(isset($order['status']) && isset($order['payment_mode']) && $order['status'] == '0' && $order['payment_mode'] == 'paymentgateway'){
                //$delayReminderTimeAfter2Hrs = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($order['created_at'])))->addMinutes(60*2);
                Log::info("In send Communication");
                
                $delayReminderTimeAfter2Hrs = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($order['created_at'])))->addMinutes(60*2);
                $this->findermailer->abandonCartCustomerAfter2hoursFinder($order->toArray(),$delayReminderTimeAfter2Hrs);
                //$this->findermailer->abandonCartCustomerAfter2hoursFinder($order->toArray());
            }

            $this->utilities->removeOrderCommunication($order);

            $nineAM = strtotime(date('Y-m-d 09:00:00'));
            $ninePM = strtotime(date('Y-m-d 21:00:00'));
            $now = time();

            if($now <= $nineAM || $now >= $ninePM){
                $now = strtotime(date('Y-m-d 11:00:00'));
            }

            

            // $order->customerSmsSendPaymentLinkAfter3Days = $this->customersms->sendPaymentLinkAfter3Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+3 days",$now)));
            // $order->customerSmsSendPaymentLinkAfter7Days = $this->customersms->sendPaymentLinkAfter7Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+7 days",$now)));
            //$order->customerSmsSendPaymentLinkAfter15Days = $this->customersms->sendPaymentLinkAfter15Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+15 days",$now)));
            //$order->customerSmsSendPaymentLinkAfter30Days = $this->customersms->sendPaymentLinkAfter30Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+30 days",$now)));
            // $order->customerSmsSendPaymentLinkAfter45Days = $this->customersms->sendPaymentLinkAfter45Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+45 days",$now)));

            //if(isset($order['reg_id']) && $order['reg_id'] != "" && isset($order['device_type']) && $order['device_type'] != ""){
                // $order->customerNotificationSendPaymentLinkAfter3Days = $this->customernotification->sendPaymentLinkAfter3Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+3 days",$now)));
                // $order->customerNotificationSendPaymentLinkAfter7Days = $this->customernotification->sendPaymentLinkAfter7Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+7 days",$now)));
                /*$order->customerNotificationSendPaymentLinkAfter15Days = $this->customernotification->sendPaymentLinkAfter15Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+15 days",$now)));
                $order->customerNotificationSendPaymentLinkAfter30Days = $this->customernotification->sendPaymentLinkAfter30Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+30 days",$now)));*/
                // $order->customerNotificationSendPaymentLinkAfter45Days = $this->customernotification->sendPaymentLinkAfter45Days($order->toArray(), date('Y-m-d H:i:s', strtotime("+45 days",$now)));
           // }

            // $url = Config::get('app.url')."/addwallet?customer_id=".$order["customer_id"]."&order_id=".$order_id;

            // $order->customerWalletSendPaymentLinkAfter15Days = $this->hitURLAfterDelay($url."&time=LPlus15", date('Y-m-d H:i:s', strtotime("+15 days",$now)));
            // $order->customerWalletSendPaymentLinkAfter30Days = $this->hitURLAfterDelay($url."&time=LPlus30", date('Y-m-d H:i:s', strtotime("+30 days",$now)));

            $order->notification_status = 'abandon_cart_yes';

            $booktrial = Booktrial::where('type','booktrials')->where('customer_id',$order['customer_id'])->where('finder_id',(int)$order['finder_id'])->orderBy('desc','_id')->first();

            if($booktrial){

                $order->previous_booktrial_id = (int)$booktrial->_id;
            }

            $order->update();

            return "success";

            
        } catch (Exception $e) {

            Log::error($e);

            return "error";
            
        }

    }

    public function getOrderDetails($order){

        //$order = Order::find((int)$order_id);

        $referal_order = [];

        $referal_order['order_id'] =  $order['order_id'];
        $referal_order['city_id'] =  $order['city_id'];
        $referal_order['city_name'] =  (isset($order['city_name']) && $order['city_name']!="") ?  $order['city_name'] : "";
        $referal_order['city_slug'] = (isset($order['city_slug']) && $order['city_slug']!="") ?  $order['city_slug'] : "";
        $referal_order['finder_id'] =  $order['finder_id'];
        $referal_order['finder_name'] =  $order['finder_name'];
        $referal_order['finder_slug'] =  $order['finder_slug'];
        $referal_order['ratecard_id'] =  (isset($order['ratecard_id']) && $order['ratecard_id'] != '') ? $order['ratecard_id'] : "";
        $referal_order['service_id'] =  $order['service_id'];
        $referal_order['service_name'] =  $order['service_name'];
        $referal_order['service_duration'] =  $order['service_duration'];
        $referal_order['city_id'] =  $order['city_id'];
        $referal_order['city_name'] =  $order['city_name'];
        $referal_order['city_slug'] = $order['city_slug'];
        $referal_order['category_id'] =  $order['finder_category_id'];
        $referal_order['category_name'] =  (isset($order['category_name']) && $order['category_name']!="") ?  $order['category_name'] : "";
        $referal_order['category_slug'] = (isset($order['category_slug']) && $order['category_slug']!="") ?  $order['category_slug'] : "";

        return $referal_order;


    }

    public function generaterDietPlanOrderOnline($order_id){

        $order_id = (int)$order_id;

        $order = Order::find($order_id);

        if(!$order){
            return Response::json(array('status' => 404,'message' => 'Order not found'),404);
        }

        if($order->status == "1" && !isset($order->diet_plan_order_id) && isset($order->diet_plan_ratecard_id) && $order->diet_plan_ratecard_id != "" && $order->diet_plan_ratecard_id != 0){


            if(isset($order->city_id)){

                $city = City::find((int)$order->city_id,['_id','name','slug']);

                $order->update(['city_name'=>$city->name,'city_slug'=>$city->slug]);
            }

            if(isset($order->finder_category_id)){

                $category = Findercategory::find((int)$order->finder_category_id,['_id','name','slug']);

                $order->update(['category_name'=>$category->name,'category_slug'=>$category->slug]);
            }

            $generaterDietPlanOrder = $this->generaterDietPlanOrder($order->toArray());

            if($generaterDietPlanOrder['status'] != 200){
                return Response::json($generaterDietPlanOrder,$generaterDietPlanOrder['status']);
            }

            $order->diet_plan_order_id = $generaterDietPlanOrder['order_id'];
            $order->update();

            return Response::json(array('status' => 200,'message' => 'Success','diet_plan_order_id'=>$generaterDietPlanOrder['order_id']),200);
        }

        return Response::json(array('status' => 404,'message' => 'Diet plan not created'),404);
    }

    public function generaterDietPlanOrder($order){

        $data = [];

        $data['type'] = "diet_plan";
        $data['ratecard_id'] = $order['diet_plan_ratecard_id'];
        $data['referal_order_id'] = $order['_id'];
        $data['customer_name'] = $order['customer_name'];
        $data['customer_email'] = $order['customer_email'];
        $data['customer_phone'] = $order['customer_phone'];
        $data['customer_source'] = $order['customer_source'];
        $data['city_id'] =  $order['city_id'];
        $data['city_name'] =  (isset($order['city_name']) && $order['city_name']!="") ?  $order['city_name'] : "";
        $data['city_slug'] = (isset($order['city_slug']) && $order['city_slug']!="") ?  $order['city_slug'] : "";
        $data['offering_type'] = (isset($order['offering_type']) && $order['offering_type']!="") ? $order['offering_type']: "cross_sell";
        $data['renewal'] = "no";
        $data['final_assessment'] = "no";

        $data['referal_order'] = $this->getOrderDetails($order);

        array_set($data, 'status', '1');
        array_set($data, 'order_action', 'bought');
        array_set($data, 'success_date', date('Y-m-d H:i:s',time()));

        $customerDetail = $this->getCustomerDetail($data);

        if($customerDetail['status'] != 200){
            return $customerDetail;
        }

        $data = array_merge($data,$customerDetail['data']); 
          
        $ratecardDetail = $this->getRatecardDetail($data);

        if($ratecardDetail['status'] != 200){
            return $ratecardDetail;
        }

        $data = array_merge($data,$ratecardDetail['data']);

        $diet_inclusive = false;
        if(isset($order['diet_inclusive']) && $order['diet_inclusive']){
            $data['amount'] = $data['amount_finder'] = $data['amount_finder']/2;
            $diet_inclusive = true;
        }

        $ratecard_id = (int) $data['ratecard_id'];
        $finder_id = (int) $data['finder_id'];
        $service_id = (int) $data['service_id'];

        $finderDetail = $this->getFinderDetail($finder_id);

        if($finderDetail['status'] != 200){
            return $finderDetail;
        }

        $data = array_merge($data,$finderDetail['data']);

        $serviceDetail = $this->getServiceDetail($service_id);

        if($serviceDetail['status'] != 200){
            return $serviceDetail;
        }

        $data = array_merge($data,$serviceDetail['data']);

        $order_id = $data['_id'] = $data['order_id'] = Order::maxId() + 1;

        $data = $this->unsetData($data);

        $data['status'] = "1";
        $data['order_action'] = "bought";
        $data['success_date'] = date('Y-m-d H:i:s',time());

        $order = new Order($data);
        $order->_id = $order_id;
        $order->save();


        if($diet_inclusive){

            $emailData = $order->toArray();
            $dietRatecard = Ratecard::find($data['ratecard_id']);
            $dietService = Service::find($dietRatecard['service_id']);
            $emailData['service_name'] = $dietService['name'];

            $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
            $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
        }


        //$redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),Config::get('app.queue'));
        //$order->update(array('redis_id'=>$redisid));

        return array('order_id'=>$order_id,'status'=>200,'message'=>'Diet Plan Order Created Sucessfully');
    }
    
    public function generateFreeDietPlanOrder($order,$type = false){
    	
    	$data = [];
    	$data['type'] = "diet_plan";
    	$data['customer_name'] = $order['customer_name'];
    	$data['customer_email'] = $order['customer_email'];
    	$data['customer_phone'] = $order['customer_phone'];
    	$data['customer_source'] = (!empty($order['customer_source'])?$order['customer_source']:'web');

        if($type){
            $data['pay_for'] = $type;
        }
    	
    	$order['finder_id']=11128;
    	$rt=Ratecard::where("finder_id",$order['finder_id'])->where('validity',1)->where('validity_type','months')->where(function($query)
    	{$query->orWhere('special_price', '!=', 0)->orWhere('price', '!=',0);
    	})->first();
    	Log::info(" free diet plan ratecard ".print_r($rt,true));
    	if(!empty($rt))
    	{
    		$data['ratecard_id'] = $rt->_id;
    			
	    	$customerDetail = $this->getCustomerDetail($data);
	    	if(!empty($customerDetail)&&$customerDetail['status'] == 200)
	    	$data = array_merge($data,$customerDetail['data']);
	    	
	    	$ratecardDetail = $this->getRatecardDetail($data);
	    	if(!empty($ratecardDetail)&&$ratecardDetail['status'] == 200)
	    		$data = array_merge($data,$ratecardDetail['data']);
	    		
	    	$diet_inclusive = false;
    		$ratecard_id = (int) $data['ratecard_id'];
    		$finder_id = (int) $data['finder_id'];
    		$service_id = (int) $data['service_id'];
    		$finderDetail = $this->getFinderDetail($finder_id);
    		
    		if(!empty($finderDetail)&&$finderDetail['status'] == 200)
    			$data = array_merge($data,$finderDetail['data']);
    		
    		$serviceDetail = $this->getServiceDetail($service_id);
    		
    		if(!empty($serviceDetail)&&$serviceDetail['status'] == 200)
    		$data = array_merge($data,$serviceDetail['data']);
    		
    		$data['status'] = "1";
    		$data['order_action'] = "bought";
    		$data['success_date'] = date('Y-m-d H:i:s',time());
    		
    		$order = new Order($data); 
    		$order->_id =Order::maxId()+1;
    		$order->save();
    		Log::info(" free dietplan order ".print_r($order,true));
    		return array('order_id'=>$order->_id,'status'=>200,'message'=>'Diet Plan Order Created Sucessfully');
    	}
    	else return array('status'=>0,'message'=>'Rate Card Not found for giving free diet plan');
    	
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

    public function addWallet(){

        $data = $_GET;

        $rules = array(
            'customer_id'=>'required',
            'time'=>'required|in:Nplus2',
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }

        $time = $data['time'];
        $customer = Customer::find((int)$data['customer_id']);
        $top_up = false;
        $wallet_balance = 0;
        $customer_id = (int)$data['customer_id'];

        if($customer){

            $orderTime = ['LPlus15','LPlus30','PurchaseFirst','RLMinus7','RLMinus1'];
            $trialTime = ['F1Plus15','Nplus2'];

            $amountArray = [
                "LPlus15" => 150,
                "LPlus30" => 150,
                "F1Plus15" => 150,
                "PurchaseFirst" => 150,
                "RLMinus7" => 150,
                "RLMinus1" => 150,
                "Nplus2" => 200,
            ];

            if(isset($customer->demonetisation)){

                $wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

            }else{

                $customerWallet = Customerwallet::where('customer_id',$customer_id)->orderBy('_id', 'desc')->first();

                if(isset($customerWallet) && isset($customerWallet->balance_fitcash_plus)){
                    $wallet_balance = $customerWallet->balance_fitcash_plus;
                }

            }

            $amount = $amountArray[$time];

            Log::info('wallet_balance - '.$wallet_balance);

            if($wallet_balance > 0){

                $amount = 0;

                if($amountArray[$time] > $wallet_balance){
                    $amount = $amountArray[$time] - $wallet_balance;
                }

            }

            if($amount > 0){

                $req = [];

                $req['customer_id'] = $data['customer_id'];
                $req['amount'] = $amount;

                $wallet_balance +=  $amount;

                $req['entry'] = "credit";
                $req['type'] = "FITCASHPLUS";
                $req['amount_fitcash_plus'] = $amount;
                $req['description'] = "Added FitCash+ as Fitternity Bonus, Expires On : ".date('d-m-Y',time()+(86400*60));
                $req["validity"] = time()+(86400*60);
                $req['for'] = $time;

                if($time == "Nplus2"){
                    $req['description'] = "Added FitCash+ as Fitternity Bonus, Expires On : ".date('d-m-Y',time()+(86400*7));
                    $req["validity"] = time()+(86400*7);
                }

                $walletTransactionResponse = $this->utilities->walletTransaction($req);

                if($walletTransactionResponse['status'] == 200){

                    $customer->update(["added_fitcash_plus" => time()]);

                    $top_up = true;
                }

            }

            if(isset($data['order_id'])){
                
                $req['order_id'] = (int)$data['order_id'];

                $transaction = Order::find((int)(int)$data['order_id']);

                $dates = array('followup_date','last_called_date','preferred_starting_date', 'called_at','subscription_start','start_date','start_date_starttime','end_date', 'order_confirmation_customer');
                $unset_keys = [];
                foreach ($dates as $key => $value){
                    if(isset($transaction[$value]) && $transaction[$value]==''){
                        // $transaction->unset($value); 
                        array_push($unset_keys, $value);
                    }
                }
                if(count($unset_keys)>0){
                    $transaction->unset($unset_keys);
                }
            }

            if(isset($data['booktrial_id'])){
                
                $req['booktrial_id'] = (int)$data['booktrial_id'];

                $transaction = Booktrial::find((int)(int)$data['booktrial_id']);

                $dates = array('start_date', 'start_date_starttime', 'schedule_date', 'schedule_date_time', 'followup_date', 'followup_date_time','missedcall_date','customofferorder_expiry_date','auto_followup_date');
                $unset_keys = [];
                
                foreach ($dates as $key => $value){
                    if(isset($transaction[$value]) && $transaction[$value]==''){
                        $transaction->unset($value);
                        array_push($unset_keys, $value);
                    }
                }
                if(count($unset_keys)>0){
                    $transaction->unset($unset_keys);
                }
            }

            if($transaction && $wallet_balance > 0){

                $transaction = $transaction->toArray();
                
                $transaction['wallet_balance'] = $wallet_balance;
                $transaction['top_up'] = $top_up;
                $transaction['top_up_amount'] = $amount;

                switch ($time) {
                    case 'LPlus15':
                        $this->customersms->sendPaymentLinkAfter15Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendPaymentLinkAfter15Days($transaction,0);
                        }
                        break;
                    case 'LPlus30':
                        $this->customersms->sendPaymentLinkAfter30Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendPaymentLinkAfter30Days($transaction,0);
                        }
                        break;
                    case 'RLMinus7':
                        $this->customersms->sendRenewalPaymentLinkBefore7Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendRenewalPaymentLinkBefore7Days($transaction,0);
                        }
                        break;
                    case 'RLMinus1':
                        $this->customersms->sendRenewalPaymentLinkBefore1Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->sendRenewalPaymentLinkBefore1Days($transaction,0);
                        }
                        break;
                    case 'PurchaseFirst':
                        $this->customersms->purchaseFirst($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->purchaseFirst($transaction,0);
                        }
                        break;
                    case 'F1Plus15':
                        $this->customersms->postTrialFollowup1After15Days($transaction,0);
                        if(isset($transaction['reg_id']) && $transaction['reg_id'] != "" && isset($transaction['device_type']) && $transaction['device_type'] != ""){
                            $this->customernotification->postTrialFollowup1After15Days($transaction,0);
                        }
                        break;
                    case 'Nplus2':

                        $sms_data = [];

                        $sms_data['customer_phone'] = $transaction['customer_phone'];

                        $sms_data['message'] = "Hi ".ucwords($transaction['customer_name']).". Hope you liked your trial workout at".ucwords($transaction['finder_name']).". You have Rs. ".$transaction['wallet_balance']." in your Fitternity wallet. Use it now to buy the membership at lowest price with assured complimentary rewards like cool fitness merchandise and Diet Plan. ".$transaction['vendor_link'].".  Valid for 7 days. For quick assistance call Fitternity on ".Config::get('app.contact_us_customer_number');

                        $this->customersms->custom($sms_data);

                        break;
                    default : break;
                }

                return Response::json(array('status' => 200,'message' => 'Success'),200);
            }

            return Response::json(array('status' => 401,'message' => 'Error'),401);

        }

        return Response::json(array('status' => 402,'message' => 'Error'),402);
        
    }

    public function deleteCommunicationOfSuccess(){

        $allOrders = Order::active()
                    ->whereIn('type',['memberships','healthytiffinmembership'])
                    ->where('created_at', '>=', new \DateTime( date("2017-04-15 00:00:00")))
                    ->where('deleteCommunication','exists',false)
                    ->orderBy('_id','desc')
                    ->get();

        if(count($allOrders) > 0){

            foreach ($allOrders as $order) {

                $order->update(['deleteCommunication' => true]);

                $this->utilities->deleteCommunication($order);
                $this->utilities->setRedundant($order);
            }

            return "success";
        }

        return "no orders found";
    
    }

    public function sendMissedOrderCommunication($orderId){
        try{
            Log::info('Processing');
            $order = Order::findOrFail((int)$orderId);
            $finder = Finder::findOrFail($order->finder_id);
            $abundant_category = array(42,45);
            
            if (filter_var(trim($order['customer_email']), FILTER_VALIDATE_EMAIL) === false){
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
                        if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
                            if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                                $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                            }

                        }else{
                            $sndPgMail  =   $this->customermailer->sendPgOrderMail($emailData);
                        }
                    }

                    //no email to Healthy Snacks Beverages and Healthy Tiffins
                    if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan'){
                        
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
                    
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin" && $order->type != 'diet_plan'){
                        if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                            $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                        }

                    }else{
                        $sndPgSms   =   $this->customersms->sendPgOrderSms($emailData);
                    }
                }

                //no sms to Healthy Snacks Beverages and Healthy Tiffins
                if(!in_array($finder->category_id, $abundant_category) && $order->type != "wonderise" && $order->type != "lyfe" && $order->type != "mickeymehtaevent" && $order->type != "events" && $order->type != 'diet_plan'){
                    
                    if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                        if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                            $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                        }
                        
                    }else{
                        $sndPgSms   =   $this->findersms->sendPgOrderSms($order->toArray());
                    }
                    
                }

                if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !in_array($finder->category_id, $abundant_category) && $order->type == "memberships" && $order->type != 'diet_plan'){

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
                    Log::info("Processed: $orderId");
                }
        }catch(Exception $e){
            Log::info($e);
        }
    }

    public function sendOrderMissedEmails(){
        ini_set('max_execution_time', 300000);

		$timestamp = strtotime('2017-06-07 11am');
        $order_ids = Order::where('created_at', '>=', new MongoDate(strtotime('2017-06-07 11am')))->where('created_at', '<=', new MongoDate(strtotime('2017-06-07 5pm')))->where('type', 'memberships')->lists('_id');

        Log::info("Starting");
		foreach($order_ids as $id){
            Log::info($id);
            $this->sendMissedOrderCommunication($id);
        }

        return "Done";
	}


    public function validateMyReward($myreward_id){

        $myreward = Myreward::find((int)$myreward_id);

        if($myreward){

            $created_at = date('Y-m-d H:i:s',strtotime($myreward->created_at));

            $validity_date_unix = strtotime($created_at . ' +'.(int)$myreward->validity_in_days.' days');
            $current_date_unix = time();

            if($validity_date_unix < $current_date_unix){
                return array('status' => 404,'message' => "Validity Is Over");
            }

            if(!isset($myreward->claimed) || $myreward->claimed < $myreward->quantity){
                return array('status' => 200,'message' => "Validate Successfully");
            }else{
                return array('status' => 404,'message' => "Reward Already Claimed");
            }

            return array('status' => 200,'message' => "Validate Successfully");
        }

    }

    public  function autoRegisterCustomer($job,$data){

        $job->delete();

        try {

            $event_customers = $data["event_customers"];

            foreach ($event_customers as $customer_data) {
                if(!empty($data['event_type']) && $data['event_type']== 'TOI'){
                    $customer_data['event_type'] = 'TOI';
                }
                autoRegisterCustomer($customer_data);
            }

            return "success";

        } catch (Exception $e) {

            Log::error($e);

            return "error";
            
        }
    }

    function getBookingDetails($data){
        
        $booking_details = [];

        $position = 0;

        $onepassHoldCustomer = $this->utilities->onepassHoldCustomer();
        if(!empty($onepassHoldCustomer) && $onepassHoldCustomer && ($data['amount_customer'] < Config::get('pass.price_upper_limit') || $this->utilities->forcedOnOnepass(['flags' => $data['finder_flags']])) && !empty($data['type']) && $data['type'] == 'workout-session'){
            $booking_details_data["customer_name"] = ['field'=>'NAME','value'=>$data['customer_name'],'position'=>$position++];
			$booking_details_data["customer_email"] = ['field'=>'EMAIL','value'=>$data['customer_email'],'position'=>$position++];
			$booking_details_data["customer_contact_no"] = ['field'=>'CONTACT NO','value'=>$data['customer_phone'],'position'=>$position++];
        }

        $booking_details_data["finder_name_location"] = ['field'=>'STUDIO NAME','value'=>$data['finder_name'].", ".$data['finder_location'],'position'=>$position++];

        if(in_array($data['type'],["booktrials","workout-session","manualautotrial"])){
            $booking_details_data["finder_name_location"] = ['field'=>'BOOKED AT','value'=>$data['finder_name'].", ".$data['finder_location'],'position'=>$position++];
        }

        $booking_details_data["service_name"] = ['field'=>'SERVICE','value'=>$data['service_name'],'position'=>$position++];

        $booking_details_data["service_duration"] = ['field'=>'DURATION','value'=>$data['service_duration'],'position'=>$position++];

        $booking_details_data["start_date"] = ['field'=>'START DATE','value'=>'-','position'=>$position++];

        $booking_details_data["start_time"] = ['field'=>'START TIME','value'=>'-','position'=>$position++];

        $booking_details_data["address"] = ['field'=>'ADDRESS','value'=>'','position'=>$position++];

        if(isset($data['reward_ids']) && !empty($data['reward_ids'])){

            $reward_detail = array();

            $reward_ids = array_map('intval',$data['reward_ids']);

            $rewards = Reward::whereIn('_id',$reward_ids)->get(array('_id','title','quantity','reward_type','quantity_type'));

            if(count($rewards) > 0){

                foreach ($rewards as $value) {

                    $title = $value->title;

                    $reward_detail[] = ($value->reward_type == 'nutrition_store') ? $title : $value->quantity." ".$title;
                    
                    array_set($data, 'reward_type', $value->reward_type);
            
                }
            
                $reward_info = (!empty($reward_detail)) ? implode(" + ",$reward_detail) : "";
            
                array_set($data, 'reward_info', $reward_info);
                
            }
        }

        if(isset($data['cashback']) && $data['cashback']){
            array_set($data,'reward_info','Cashback');
        }

        if(isset($data["reward_info"]) && $data["reward_info"] != ""){

            if($data["reward_info"] == 'Cashback'){
                $booking_details_data["reward"] = ['field'=>'REWARD','value'=>$data["reward_info"],'position'=>$position++];
            }else{
                $booking_details_data["reward"] = ['field'=>'REWARD','value'=>$data["reward_info"]." (Avail it from your Profile)",'position'=>$position++];
            }
        }
         
        if(isset($data["membership"]) && !empty($data["membership"])){

            if(isset($data["membership"]['cashback']) && $data["membership"]['cashback'] === true){

                $booking_details_data["reward"] = ['field'=>'PREBOOK REWARD','value'=>'Cashback','position'=>$position++];
            }

            if(isset($data["membership"]["reward_ids"]) && isset($data["membership"]["reward_ids"]) && !empty($data["membership"]["reward_ids"])){

                $reward_id = $data["membership"]["reward_ids"][0];

                $reward = Reward::find($reward_id,['title']);

                if($reward){

                    $booking_details_data["reward"] = ['field'=>'PREBOOK REWARD','value'=>$reward['title'],'position'=>$position++];
                }
            }

        }

        $finder_id = "";
        if(!empty($data['finder_id'])){
            $finder_id = $data['finder_id'];
        }

        $multifitFinder = $this->utilities->multifitFinder();
        if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && in_array($finder_id, $multifitFinder)){
            // Log::info('multifit');
            if(!empty($booking_details_data['reward'])){
                Log::info(' *************************multifit    ',[$booking_details_data['reward']['value']]);
                $booking_details_data['reward']['value'] = str_replace("Fitternity ","",$booking_details_data['reward']['value']);
                Log::info('after *************************multifit    ',[$booking_details_data['reward']['value']]);
            }
        }
        // Log::info(' *************************multifit    ',[$booking_details_data['reward']]);

        if(isset($data["assisted_by"]) && isset($data["assisted_by"]["name"]) && $data["assisted_by"] != ""){

            $booking_details_data["assisted_by"] = ['field'=>'ASSISTED BY','value'=>$data["assisted_by"]["name"],'position'=>$position++];
        }

        if(isset($data['start_date']) && $data['start_date'] != ""){
            $booking_details_data['start_date']['value'] = date('l, j M Y',strtotime($data['start_date']));
        }

        if(isset($data['schedule_date']) && $data['schedule_date'] != ""){
            $booking_details_data['start_date']['value'] = date('l, j M Y',strtotime($data['schedule_date']));
        }
        
        if(isset($data['preferred_starting_date']) && $data['preferred_starting_date'] != ""){
            $booking_details_data['start_date']['value'] = date('l, j M Y',strtotime($data['preferred_starting_date']));
        }
        
        // if(!empty($booking_details_data['start_date']['value'])){
        //     $booking_details_data['start_date']['value'] = date('l, j M Y',strtotime($booking_details_data['start_date']['value']));
        // }

        if(isset($data['start_time']) && $data['start_time'] != ""){
            $booking_details_data['start_time']['value'] = strtoupper($data['start_time']);
        }

        if(isset($data['schedule_slot_start_time']) && $data['schedule_slot_start_time'] != ""){
            $booking_details_data['start_time']['value'] = strtoupper($data['schedule_slot_start_time']);
        }

        if($data['finder_address'] != ""){
            $booking_details_data['address']['value'] = $data['finder_address'];
        }
        
        if(in_array($data['type'],["healthytiffintrial","healthytiffintrail","healthytiffinmembership"])){

            if(isset($data['customer_address']) && $data['customer_address'] != ""){
                $booking_details_data['address']['value'] = $data['customer_address'];
            }

        }else{

            if($data['finder_address'] != ""){
                $booking_details_data['address']['value'] = $data['finder_address'];
            }
            if(isset($data['finder_address']) && $data['finder_address'] != ""){
                $booking_details_data['address']['value'] = $data['finder_address'];
            }
        }

        if(isset($booking_details_data['address']['value'])){

            $booking_details_data['address']['value'] = str_replace("  ", " ",$booking_details_data['address']['value']);
            $booking_details_data['address']['value'] = str_replace(", , ", "",$booking_details_data['address']['value']);
        }

        if(in_array($data['type'], ['manualtrial','manualautotrial','manualmembership'])){
            $booking_details_data["start_date"]["field"] = "PREFERRED DATE";
            $booking_details_data["start_time"]["field"] = "PREFERRED TIME";
        }

        if(in_array($data['type'], ['booktrial','workout-session'])){

            $booking_details_data["start_date"]["field"] = "DATE & TIME";
            $booking_details_data["start_time"]["field"] = "SESSION TIME";
            $booking_details_data['service_name']['field'] = 'WORKOUT FORM';

            if($data['type'] == 'workout-session'){
                
                $booking_details_data['service_duration']['value'] = '1 Session';
            }
            
        }

        if(!empty($booking_details_data['service_name']['value']) && !empty($booking_details_data['service_duration']['value'])){
            $booking_details_data['service_name']['value'] = $booking_details_data['service_name']['value'].' ('.$booking_details_data['service_duration']['value'].')';
        }



        if(isset($data['preferred_day']) && $data['preferred_day'] != ""){
            $booking_details_data['start_date']['field'] = 'PREFERRED DAY';
            $booking_details_data['start_date']['value'] = $data['preferred_day'];
        }

        if(isset($data['preferred_time']) && $data['preferred_time'] != ""){
            $booking_details_data['start_time']['field'] = 'PREFERRED TIME';
            $booking_details_data['start_time']['value'] = $data['preferred_time'];
        }
        
        if(!empty($booking_details_data['start_date']['value']) && !empty($booking_details_data['start_time']['value'])){
            $booking_details_data["start_date"]["value"] = $booking_details_data["start_date"]["value"];

            if(!empty($booking_details_data['start_time']['value']) && $booking_details_data['start_time']['value'] != '-'){
                $booking_details_data["start_date"]["value"] = $booking_details_data["start_date"]["value"].' at '.$booking_details_data['start_time']['value'];
            }
        }

        
        if(isset($data['"preferred_service']) && $data['"preferred_service'] != "" && $data['"preferred_service'] != null){
            $booking_details_data['service_name']['field'] = 'PREFERRED SERVICE';
            $booking_details_data['service_name']['value'] = $data['preferred_service'];
        }

        if(in_array($data['type'],["healthytiffintrial","healthytiffintrail"]) && isset($data['ratecard_remarks']) && $data['ratecard_remarks'] != ""){
            $booking_details_data['service_duration']['value'] = ucwords($data['ratecard_remarks']);
        }

        if(in_array($data['type'],["healthytiffintrail","healthytiffintrial","healthytiffinmembership"])){
            $booking_details_data['finder_name_location']['field'] = 'BOUGHT AT';
            $booking_details_data['finder_name_location']['value'] = $data['finder_name'];
        }
        
        if(!empty($booking_details_data['start_time'])) {
            unset($booking_details_data['start_time']);
        }
        
        if(!empty($booking_details_data['service_duration'])) {
            unset($booking_details_data['service_duration']);  
        }

        if(!empty($data['type']) && $data['type'] == 'memberships' && empty($data['extended_validity'])){
            $booking_details_data["add_remark"] = ['field'=>'','value'=>"FLAT 20% Off On Lowest Prices Of Gyms & Studio Memberships | Use Code: DEC20 | 28-30 Dec ",'position'=>$position++];

            if(!empty($data['brand_id']) && $data['brand_id']== 88){
                if($data['ratecard_amount'] >= 8000){
                    $booking_details_data["add_remark"] = ['field'=>'','value'=>"Extra 15% Off On Lowest Prices + Handpicked Healthy Food Hamper Worth INR 2,500 On Memberships | Use Code: FITME15",'position'=>$position++];
                }else{
                    $booking_details_data["add_remark"] = ['field'=>'','value'=>"Extra 15% Off On Lowest Prices | Use Code: FITME15",'position'=>$position++];
                }
            }
            
            if(!empty($data['finder_flags']['monsoon_flash_discount_disabled']) || in_array($data['finder_id'], Config::get('app.camp_excluded_vendor_id')) ){ 
                // if(!empty($data['finder_flags']['monsoon_flash_discount_disabled']) || in_array($data['finder_id'], Config::get('app.camp_excluded_vendor_id')) || (isset($data['finder_flags']['monsoon_flash_discount_per']) && $data['finder_flags']['monsoon_flash_discount_per'] == 0) || !(isset($data['finder_flags']['monsoon_flash_discount']) && isset($data['finder_flags']['monsoon_flash_discount_per']))){ 
                $booking_details_data["add_remark"] = ['field'=>'','value'=>"",'position'=>$position++];
                
			}
        }

        // if(!empty($data['type']) && $data['type'] == 'workout-session' && empty($data['finder_flags']['monsoon_campaign_pps'])){
        if(!empty($data['type']) && $data['type'] == 'workout-session'){
            $booking_details_data["add_remark"] = ['field'=>'','value'=>'You are eligilble for 100% instant cashback with this purchase, use code: CB100','position'=>$position++];

            // $first_session_free = $this->firstSessionFree($data);
            // if(!empty($first_session_free) && $first_session_free){
            //     $booking_details_data["add_remark"] = ['field'=>'','value'=>'Apply code FREE to get this session for free','position'=>$position++];
            // }
            
            if(!empty($onepassHoldCustomer) && $onepassHoldCustomer && ($data['amount_customer'] < Config::get('pass.price_upper_limit') || $this->utilities->forcedOnOnepass(['flags' => $data['finder_flags']]))){
                $booking_details_data["add_remark"] = ['field'=>'','value'=>'','position'=>$position++];
            }

            if((!empty($data['finder_flags']['mfp']) && $data['finder_flags']['mfp']) || (in_array($data['finder_id'], Config::get('app.camp_excluded_vendor_id'))) || !empty($data['finder_flags']['monsoon_flash_discount_disabled']) || (!empty($data['brand_id']) && $data['brand_id'] == 88) ){
                // if((!empty($data['finder_flags']['mfp']) && $data['finder_flags']['mfp']) || (in_array($data['finder_id'], Config::get('app.camp_excluded_vendor_id'))) || !empty($data['finder_flags']['monsoon_flash_discount_disabled']) || (!empty($data['brand_id']) && $data['brand_id'] == 88) || (isset($data['finder_flags']['monsoon_flash_discount_per']) && $data['finder_flags']['monsoon_flash_discount_per'] == 0) || !(isset($data['finder_flags']['monsoon_flash_discount']) && isset($data['finder_flags']['monsoon_flash_discount_per']))){
                $booking_details_data["add_remark"] = ['field'=>'','value'=>'','position'=>$position++];
            }
        }
        
        $booking_details_all = [];
        foreach ($booking_details_data as $key => $value) {

            $booking_details_all[$value['position']] = ['field'=>$value['field'],'value'=>$value['value']];
        }

        foreach ($booking_details_all as $key => $value) {

            if($value['value'] != "" && $value['value'] != "-"){
                $booking_details[] = $value;
            }

        }

        return $booking_details;
    }

    function getPaymentDetails($data,$payment_mode_type){

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }

        $amount_summary = [];
        
        $you_save = 0;
        
        $amount_summary[0] = array(
            'field' => 'Total Amount',
            'value' => 'Rs. '.(isset($data['original_amount_finder']) ? $data['original_amount_finder'] : $data['amount_customer'])
        );
        
        if(isset($data['session_payment']) && $data['session_payment']){
            $amount_summary[0]['value'] = 'Rs. '.$data['amount_customer'];
        }

        if(!empty($data['ratecard_amount'])){
            $amount_summary[0] = array(
                'field' => 'Session Amount',
                'value' => 'Rs. '.$data['ratecard_amount']
            );

            if(!empty($data['type']) && in_array($data['type'], ['memberships', 'membership'])){
                $amount_summary[0] = array(
                    'field' => 'Membership Amount',
                    'value' => 'Rs. '.(!empty($data['amount_customer']) ? $data['amount_customer'] - (!empty($data['convinience_fee']) ? $data['convinience_fee'] : 0) : $data['ratecard_amount'])
                );  
                if(!empty($data['extended_validity'])){
                    $amount_summary[0] = array(
                        'field' => 'Session Pack Amount',
                        'value' => 'Rs. '.$data['ratecard_amount']
                    ); 
                }
            }
            // $amount_summary[] = array(
            //     'field' => 'Quantity',
            //     'value' => !empty($data['customer_quantity']) ? (string)$data['customer_quantity'] : '1'
            // );
            if(!empty($data['customer_quantity']) && $data['customer_quantity'] > 1){

                $amount_summary[] = array(
                    'field' => 'Total Amount',
                    'value' => 'Rs. '.$data['amount_customer']
                );
            }
        }

        if(!empty($data['session_pack_discount'])){
             $amount_summary[] = array(
                    'field' => 'Session pack discount',
                    'value' => '-Rs. '.$data['session_pack_discount']
            );
        }

        $amount_payable = [];

        $amount_payable= array(
            'field' => 'Total Amount Payable',
            'value' => 'Rs. '.$data['amount_final']
        );

        $amount_final = $data['amount_final'];

        // if($payment_mode_type == 'part_payment' && isset($data['part_payment_calculation'])){

        //     $remaining_amount = $data['amount_customer'];

        //     if(isset($data["part_payment_calculation"]["part_payment_amount"]) && $data["part_payment_calculation"]["part_payment_amount"] > 0){

        //         $remaining_amount -= $data["part_payment_calculation"]["part_payment_amount"];
        //     }

        //     if(isset($data["part_payment_calculation"]["convinience_fee"]) && $data["part_payment_calculation"]["convinience_fee"] > 0){

        //         $remaining_amount -= $data["part_payment_calculation"]["convinience_fee"];
        //     }

        //     if(isset($data['coupon_discount_amount']) && $data['coupon_discount_amount'] > 0){

        //         $remaining_amount -= $data['coupon_discount_amount'];

        //         $amount_summary[] = array(
        //             'field' => 'Coupon Discount',
        //             'value' => '-Rs. '.$data['coupon_discount_amount']
        //         );

        //         $you_save += intval($data['coupon_discount_amount']);
                
        //     }

        //     if(isset($data['customer_discount_amount']) && $data['customer_discount_amount'] > 0){

        //         $remaining_amount -= $data['customer_discount_amount'];

        //         $amount_summary[] = array(
        //             'field' => 'Corporate Discount',
        //             'value' => '-Rs. '.$data['customer_discount_amount']
        //         );

        //         $you_save += intval($data['customer_discount_amount']);
        //     }

        //     if(isset($data['app_discount_amount']) && $data['app_discount_amount'] > 0){

        //         $remaining_amount -= $data['app_discount_amount'];

        //         $amount_summary[] = array(
        //             'field' => 'App Discount',
        //             'value' => '-Rs. '.$data['app_discount_amount']
        //         );

        //         $you_save += intval($data['app_discount_amount']);
                
        //     }

        //     $amount_summary[] = array(
        //         'field' => 'Remaining Amount Payable',
        //         'value' => 'Rs. '.$remaining_amount
        //     );

        //     $amount_summary[] = array(
        //         'field' => 'Booking Amount (20%)',
        //         'value' => 'Rs. '.$data['part_payment_calculation']['part_payment_amount']
        //     );

        //     if(isset($data['convinience_fee']) && $data['convinience_fee'] > 0){

        //         $amount_summary[] = array(
        //             'field' => 'Convenience Fee',
        //             'value' => '+Rs. '.$data['convinience_fee']
        //         );

        //     }

        //     $cashback_detail = $this->customerreward->purchaseGame($data['amount'],$data['finder_id'],'paymentgateway',$data['offer_id'],false,$data["part_payment_calculation"]["part_payment_and_convinience_fee_amount"],$data['type']);

        //     // Log::info("asdasdasdasasd============adadasdasdas=");
        //     // Log::info($cashback_detail);

        //     if($cashback_detail['amount_deducted_from_wallet'] > 0){

        //         $amount_summary[] = array(
        //             'field' => 'Fitcash Applied',
        //             'value' => '-Rs. '.$cashback_detail['amount_deducted_from_wallet']
        //         );

        //     }

        //     $amount_payable = array(
        //         'field' => 'Total Amount Payable (20%)',
        //         'value' => 'Rs. '.$data['part_payment_calculation']['amount']
        //     );

        // }else{

            if(isset($data['convinience_fee']) && $data['convinience_fee'] > 0){

                $amount_summary[] = array(
                    'field' => 'Convenience Fee',
                    'value' => '+Rs. '.$data['convinience_fee']
                );
            }

            if(isset($data['cashback_detail']) && isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0 ){
                if($payment_mode_type != 'pay_later'){

                    $amount_summary[] = array(
                        'field' => 'Fitcash Applied',
                        'value' => '-Rs. '.$data['cashback_detail']['amount_deducted_from_wallet']
                    );
                    $you_save += $data['cashback_detail']['amount_deducted_from_wallet'];
                }else{
                    $amount_final = $amount_final + $data['cashback_detail']['amount_deducted_from_wallet'];
                    $amount_payable['value'] = "Rs. ".$amount_final;   
                }
                
            }

            if((isset($data['coupon_discount_amount']) && $data['coupon_discount_amount'] > 0) || (!empty($data['coupon_flags']['cashback_100_per']))){

                if($payment_mode_type != 'pay_later'){

                    $amount_summary[] = array(
                        'field' => 'Coupon Discount',
                        'value' => !empty($data['coupon_discount_amount']) ? '-Rs. '.$data['coupon_discount_amount'] : "100% Cashback"
                    );
                    
                    $you_save += (!empty($data['coupon_discount_amount']) ? $data['coupon_discount_amount'] : 0);
                }else{
                    $amount_final = $amount_final + $data['coupon_discount_amount'];
                    $amount_payable['value'] = "Rs. ".$amount_final;   
                }
                
            }

            if(isset($data['customer_discount_amount']) && $data['customer_discount_amount'] > 0){

                $amount_summary[] = array(
                    'field' => 'Corporate Discount',
                    'value' => '-Rs. '.$data['customer_discount_amount']
                );
                $you_save += $data['coupon_discount_amount'];
                
                
            }

            if(isset($data['app_discount_amount']) && $data['app_discount_amount'] > 0){

                $amount_summary[] = array(
                    'field' => 'App Discount',
                    'value' => '-Rs. '.$data['app_discount_amount']
                );

                $you_save += $data['app_discount_amount'];
                
            }
            
            if(isset($_GET['device_type']) && isset($_GET['app_version']) && in_array($_GET['device_type'], ['android', 'ios']) && $_GET['app_version'] > '4.4.3'){

                if(isset($data['type']) && $data['type'] == 'workout-session' && $payment_mode_type != 'pay_later' && !(isset($data['session_payment']) && $data['session_payment']) && !empty($data['instant_payment_discount'])){
                    
                    $amount_summary[] = array(
                        'field' => 'Instant Pay discount',
                        'value' => '-Rs. '.$data['instant_payment_discount']
                    );
    
                    $you_save += $data['instant_payment_discount'];
                    
                    if(isset($data['pay_later']) && $data['pay_later'] && !(isset($data['session_payment']) && $data['session_payment'])){
                        
                        $amount_payable['value'] = "Rs. ".($data['amount_final'] - $data['instant_payment_discount']);
    
                    }
    
                }
            }

            // if(isset($data['type']) && $data['type'] == 'workout-session' && $payment_mode_type == 'pay_later'){
                
            //     $amount_payable['value'] = "Rs. ".($data['amount_finder']+$data['convinience_fee']);
            // }
        // }

        if(!empty($reward)){
            $amount_summary[] = $reward;
        }

        
        $payment_details  = [];
        
        $payment_details['amount_summary'] = $amount_summary;
        $payment_details['amount_payable'] = $amount_payable;
        
        if($you_save > 0){
            $result['payment_details']['savings'] = [
                'field' => 'Your total savings',
                'value' => "Rs.".$you_save
            ];
        }

        if(!empty($data['type']) && $data['type'] == 'workout-session') {
            // $onepassHoldCustomer = $this->utilities->onepassHoldCustomer();
            $onepassHoldCustomer = $this->utilities->onepassHoldCustomer();
            $allowSession = false;
            if(!empty($onepassHoldCustomer) && $onepassHoldCustomer) {
                $allowSession = $this->passService->allowSession($data['amount_customer'], $customer_id, $data['schedule_date'], $data['finder_id']);
                if(
                    !empty($allowSession['allow_session']) 
                    && 
                    (   
                        (
                            !empty($data['service_flags']['classpass_available']) 
                            && 
                            $data['service_flags']['classpass_available']
                            && 
                            empty($allowSession['onepass_lite'])
                        )
                        ||
                        (
                            !empty($allowSession['onepass_lite'])
                            &&
                            !empty($data['service_flags']['lite_classpass_available'])
                            && 
                            $data['service_flags']['lite_classpass_available']
                        )
                    )
                ) {
                    $allowSession = $allowSession['allow_session'];
                }
                else {
                    $allowSession = false;
                }
            }
            if($allowSession){
            // if(!empty($onepassHoldCustomer) && $onepassHoldCustomer && $data['amount_customer'] < Config::get('pass.price_upper_limit') && !empty($data['type']) && $data['type'] == 'workout-session'){
                $payment_details['amount_summary'] = [];
                $payment_details['amount_payable'] = array(
                    'field' => 'Total Amount Payable',
                    'value' => !empty($allowSession['onepass_lite']) ? Config::get('app.onepass_lite_free_string'): Config::get('app.onepass_free_string')
                );
                unset($payment_details['payment_details']['savings']);
            }
        }

        return $payment_details;

    }

    function getPaymentModes($data, $order=null){

        $payment_modes = [];


        $payment_options['payment_options_order'] = ["wallet", "cards", "netbanking", "emi"];

        if(!empty($order['type']) && $order['type'] == 'memberships'){
            $payment_options['payment_options_order'] = ["cards", "wallet", "netbanking", "emi"];
        }

        
    
        $payment_options['wallet'] = [
            'title' => 'Wallet',
            'subtitle' => 'Transact online with Wallets',
            'value'=>'wallet',
            'options'=>[
                    // [
                    //         'title' => 'Paypal',
                    //         // 'subtitle' => 'Paypal',
                    //         'value' => 'paypal'
                    // ],
                    [
                            'title' => 'Paytm',
                            // 'subtitle' => 'Paytm',
                            'value' => 'paytm'
                    ],
                    [
                            'title' => 'AmazonPay',
                            // 'subtitle' => 'AmazonPay',
                            'value' => 'amazonpay'
                    ],
                    [
                            'title' => 'Mobikwik',
                            // 'subtitle' => 'Mobikwik',
                            'value' => 'mobikwik'
                    ],
                    [
                            'title' => 'PayU',
                            // 'subtitle' => 'PayU',
                            'value' => 'payu'
                    ]
            ]
        ];

        if(($this->get_device_type=='ios' &&$this->get_app_version > '5.1.7') || ($this->get_device_type=='android' &&$this->get_app_version > '5.24')){
            $payment_options['payment_options_order'] = ["wallet", "upi", "cards", "netbanking", "emi"];

            if(!empty($order['type']) && $order['type'] == 'memberships'){
                $payment_options['payment_options_order'] = ["cards", "upi", "wallet", "netbanking", "emi"];
            }

            $payment_options['upi'] = [
                'title' => 'UPI',
                'notes' => "Open your UPI app on your phone to approve the payment request from Fitternity"
            ];

            $payment_options['wallet'] = [
                'title' => 'Wallet',
                'subtitle' => 'Transact online with Wallets',
                'value'=>'wallet',
                'options'=>[
                        [
                                'title' => 'Paypal',
                                'subtitle' => 'Get 50% Instant Cashback Upto INR 300 (New Users Only)',
                                'value' => 'paypal'
                        ],
                        [
                                'title' => 'Paytm',
                                // 'subtitle' => 'Paytm',
                                'value' => 'paytm'
                        ],
                        [
                                'title' => 'AmazonPay',
                                // 'subtitle' => 'AmazonPay',
                                'value' => 'amazonpay'
                        ],
                        [
                                'title' => 'Mobikwik',
                                // 'subtitle' => 'Mobikwik',
                                'value' => 'mobikwik'
                        ],
                        [
                                'title' => 'PayU',
                                // 'subtitle' => 'PayU',
                                'value' => 'payu'
                        ]
                ]
            ];
        }

        if(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=>5.33])){
            $payment_options['payment_options_order'] = ["wallet", "googlepay", "cards", "netbanking", "emi", "upi"];
            $payment_options['googlepay']  = [
                'title' => 'GooglePay',
                'notes' => "Open your Google Pay app on your phone to approve the payment request from Fitternity"
            ];
        }
        
        $os_version = intval(Request::header('Os-Version'));
        
        // if($os_version >= 9 && $this->device_type == 'android'){
        //     $payment_options['wallet']['options'] = [
        //             [
        //                     'title' => 'Paytm',
        //                     // 'subtitle' => 'Paytm',
        //                     'value' => 'paytm'
        //             ],
        //             [
        //                     'title' => 'Mobikwik',
        //                     // 'subtitle' => 'Mobikwik',
        //                     'value' => 'mobikwik'
        //             ],
        //             [
        //                     'title' => 'PayU',
        //                     // 'subtitle' => 'PayU',
        //                     'value' => 'payu'
        //             ]
        //     ];
        // }
        
        if(!empty($data['pay_later'])){
            
            $payment_modes[] = array(
                'title' => 'Pay now',
                'subtitle' => 'Pay online through wallet,credit/debit card',
                'value' => 'paymentgateway',
                'payment_options'=>$payment_options
            );

        }else{
            
            if(isset($order['type']) && $order['type'] == 'workout-session' && isset($order['customer_quantity']) && $order['customer_quantity'] == 1 && isset($order['amount']) && $order['amount'] > 0 && !isset($order['coupon_discount_amount']) && empty($order['finder_flags']['monsoon_campaign_pps'])){
                
                if(!empty($order['finder_flags']['mfp']) && $order['finder_flags']['mfp']){
                    $payment_modes[] = array(
                        'title' => 'Online Payment',
                        'subtitle' => 'Transact online with netbanking, card and wallet',
                        'value' => 'paymentgateway',
                        'payment_options'=>$payment_options
                    );
                }else{
                    $payment_modes[] = array(
                        'title' => 'Online Payment',
                        'subtitle' => 'Transact online with netbanking, card and wallet',
                        'value' => 'paymentgateway',
                        'payment_options'=>$payment_options
                    );
                }
            }else{
                $payment_modes[] = array(
                    'title' => 'Online Payment',
                    'subtitle' => 'Transact online with netbanking, card and wallet',
                    'value' => 'paymentgateway',
                    'payment_options'=>$payment_options
                );
            }
        }



        $emi = $this->utilities->displayEmi(array('amount'=>$data['data']['amount']));

        if(!empty($data['emi']) && $data['emi']){
            $payment_modes[] = array(
                'title' => 'EMI',
                'subtitle' => 'Transact online with credit installments',
                'value' => 'emi',
            );
        }
        
        if(!$this->vendor_token){
            if(!empty($data['cash_pickup']) && $data['cash_pickup'] && empty($data['data']['coupon_details']['applied'])){
                $payment_modes[] = array(
                    'title' => 'Cash Pickup',
                    'subtitle' => 'Schedule cash payment pick up',
                    'value' => 'cod',
                );
            }

            if(!empty($data['part_payment']) && $data['part_payment']){
                $payment_modes[] = array(
                    'title' => 'Reserve Payment',
                    'subtitle' => 'Pay 20% to reserve membership and pay rest on joining',
                    'value' => 'part_payment',
                );
            }
        }

        if(($this->vendor_token || !empty($data['ratecard_pay_at_vendor'])) && empty($data['data']['coupon_details']['applied'])){

            $payment_modes[] = array(
                'title' => 'Pay at Studio',
                'subtitle' => 'Transact via paying cash at the Center',
                'value' => 'pay_at_vendor',
            );

        }

        if(isset($data['pay_later']) && $data['pay_later'] && !(!empty($order['customer_quantity']) && $order['customer_quantity'] > 1 )){

            // if(empty($data['qrcodepayment'])||(!empty($data['qrcodepayment'])&&!empty($data['paymentmode_selected'])))
        	if(empty($data['qrcodepayment']))
        	{
        		$payment_modes[] = array(
        				'title' => 'Pay Later',
        				'subtitle' => 'Pay full amount online, post session date',
        				'value' => 'pay_later',
        		);
        	}
            
        }

        return $payment_modes;
    }

    public function checkCouponCode(){
        
        $data = Input::json()->all();
        $device = Request::header('Device-Type');
        $version = Request::header('App-Version');
        Log::info("checkCouponCode");
        Log::info($data);

        if($this->vendor_token && strtolower($data['coupon']) != 'sburn'){
            $resp = array("status"=> 400, "message" => "Coupon code is not valid", "error_message" => "Coupon code is not valid");
            return Response::json($resp,400);
        }

        if(!isset($data['coupon'])){
            $resp = array("status"=> 400, "message" => "Coupon code missing", "error_message" => "Please enter a valid coupon");
            return Response::json($resp,400);
        }

        // if(!empty($data['coupon']) && strtolower($data['coupon']) == 'fitmein'){

        //     if(empty($data['fitmein']) || $data['fitmein'] !== true){

        //         $resp = array("status"=> 400, "message" => "Please enter a valid coupon", "error_message" => "Please enter a valid coupon");
        //         return Response::json($resp,400);
        //     }
        // }
        
        if(empty($data['ratecard_id']) && empty($data['ticket_id']) && empty($data['pass_id'])){
            if(isset($data['order_id'])){
                $data['order_id'] = intval($data['order_id']);
                $orderDetails = Order::where('_id', $data['order_id'])->first();
                $data['pass_id'] = (!empty($orderDetails['pass_id']))?$orderDetails['pass_id']:null;
                $data['customer_name'] = (!empty($orderDetails['customer_name']))?$orderDetails['customer_name']:null;
                $data['customer_email'] = (!empty($orderDetails['customer_email']))?$orderDetails['customer_email']:null;
                $data['customer_phone'] = (!empty($orderDetails['customer_phone']))?$orderDetails['customer_phone']:null;
                $data['device_type'] = (!empty($device))?$device:null;
                $data['app_version'] = (!empty($version))?$version:null;
                if(!empty($data['pass_id'])) {
                    unset($data['ratecard_id']);
                }
            }
            if(empty($data['pass_id'])) {
                $resp = array("status"=> 400, "message" => "Ratecard Id or ticket Id must be present", "error_message" => "Coupon cannot be applied on this transaction");
                return Response::json($resp,400);
            }
        }
        if($this->utilities->isGroupId($data['coupon'])){
            $ratecard = Ratecard::find($data['ratecard_id']);
            if($ratecard['type'] == "membership" || $ratecard['type'] == "memberships"){
                $data['group_id'] = $data['coupon'];

            $resp = $this->utilities->validateGroupId(['group_id'=>$data['coupon']]);
            
            if($resp['status']==200){
                
                return Response::json($resp);
            
            }else{
                
                return Response::json($resp, 400);
                
            }
            }else{
                return Response::json($resp, 400);
            }
            

        }

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
            
            if(!empty($decoded->customer->contact_no)){
                $data['customer_phone'] = $decoded->customer->contact_no;
            }
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
        $amount_finder = 0;
        $amount = 0;
        $offer_id = false;
        $finder_id = false;
        $amount_without_fitcash = null;
        if(isset($data['ratecard_id'])){

            $ratecard = Ratecard::find($data['ratecard_id']);
            
            $data['type'] = $ratecard['type'];
            if(!empty($ratecard['type']) && $ratecard['type'] == 'workout session'){
                $data['type'] = 'workout-session';
            }

            $data['finder_id'] = $ratecard['finder_id'];
            
            if(!$ratecard){
                $resp = array("status"=> 400, "message" => "Ratecard not found", "error_message" => "Coupon cannot be applied on this transaction");
                return Response::json($resp,400);   
            }

            $ratecard_data = $ratecard->toArray();

            $finder_id = (int)$ratecard['finder_id'];

            if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
                $amount_finder = $ratecard['special_price'];
            }else{
                $amount_finder = $ratecard['price'];
            }

            $offer_id = false;

            // $offer = Offer::where('ratecard_id',$ratecard['_id'])
            //         ->where('hidden', false)
            //         ->orderBy('order', 'asc')
            //         ->where('start_date','<=',new DateTime(date("d-m-Y 00:00:00")))
            //         ->where('end_date','>=',new DateTime(date("d-m-Y 00:00:00")))
            //         ->first();
            
            $offer = Offer::getActiveV1('ratecard_id', intval($ratecard['_id']), intval($ratecard['finder_id']))->first();

            if($offer){
                $offer_id = $offer->_id;
                $amount_finder = $offer->price;
            }

            $amount_without_fitcash = $amount = $amount_finder;

            if($ratecard != null && $ratecard['type'] == "membership" && isset($_GET['device_type']) && in_array($_GET['device_type'], ["ios","android"])){

                $this->appOfferDiscount = in_array($finder_id, $this->appOfferExcludedVendors) ? 0 : $this->appOfferDiscount;

                $app_discount_amount = intval($amount_finder * ($this->appOfferDiscount/100));

                $amount -= $app_discount_amount;
            }
            
            $amount_without_fitcash = $amount;

            if($this->convinienceFeeFlag() && $this->utilities->isConvinienceFeeApplicable($ratecard_data, "ratecard")){
                
                $convinience_fee_percent = Config::get('app.convinience_fee');

                $convinience_fee = round($amount_finder*$convinience_fee_percent/100);

                $convinience_fee = $convinience_fee <= 199 ? $convinience_fee : 199;

                $amount += $convinience_fee;
            }


            $corporate_discount_percent = $this->utilities->getCustomerDiscount();
            $customer_discount_amount = intval($amount_finder * ($corporate_discount_percent/100));

            $amount -= $customer_discount_amount;

            if($amount > 0){

                $cashback_detail = $this->customerreward->purchaseGame($amount,$finder_id,'paymentgateway',$offer_id,false,false,false,$ratecard['type']);

                if(isset($data['cashback']) && $data['cashback'] == true){
                    $amount -= $cashback_detail['amount_discounted'];
                }


                if(isset($cashback_detail['amount_deducted_from_wallet']) && $cashback_detail['amount_deducted_from_wallet'] > 0){
                    $amount -= $cashback_detail['amount_deducted_from_wallet'];
                }
            }

        }

        if(isset($data['event_id'])){
            $customer_id = false;
        }
        !empty($data['customer_email']) ? $customer_email = strtolower($data['customer_email']) : $customer_email = null;
        
        $customer_id = isset($customer_id) ? $customer_id : false;

        if(!empty($ratecard['finder_id'])){
            
            Finder::$withoutAppends = true;
            $finder = Finder::find($ratecard['finder_id']);
            if(!empty($finder['flags']['enable_commission_discount']) && (!empty($ratecard['type']) && $ratecard['type'] == 'membership')){
                $commission = getVendorCommision(['finder_id'=>$ratecard['finder_id']]);
                
                if(!empty($commission)){
                    $amount_without_fitcash = round($amount_without_fitcash * (100 - $commission + Config::get('app.pg_charge'))/100);
                }
            }
        }
        $pass = null;
        if(!empty($data['pass_id'])) {
            $pass = Pass::where('pass_id', intval($data['pass_id']))->first();
        }

        $first_session_free = false;
        if(!empty($data['order_id'])){
            Log::info("Order   ::::::::", [$data['order_id']]);
            $orderData = Order::where('_id', $data['order_id'])->first()->toArray();
            $first_session_free = $this->firstSessionFree($orderData);
        }else{
            $first_session_free = $this->firstSessionFree($data);
        }

        $resp = $this->customerreward->couponCodeDiscountCheck($ratecard,$couponCode,$customer_id, $ticket, $ticket_quantity, $service_id, $amount_without_fitcash, $customer_email, $pass, $first_session_free); 
        Log::info("REsponse from CustomerReward", $resp);
        if($resp["coupon_applied"]){

            if(!empty($resp['data']['discount']))
        		$resp['coupon_description']="Rs. ".$resp['data']['discount']. " off Applied.";

            if(isset($data['event_id']) && isset($data['customer_email']) && !(!empty($data['event_type']) && $data['event_type'] == 'TOI')){
                                
                $already_applied_coupon = Customer::where('email', strtolower($data['customer_email']))->whereIn('applied_promotion_codes',[strtolower($data['coupon'])])->count();
            
                if($already_applied_coupon>0 && !$resp["vendor_routed_coupon"]){
                    return Response::json(array('status'=>400,'data'=>array('final_amount'=>($resp['data']['discount']+$resp['data']['final_amount']), "discount" => 0), 'error_message'=>'Coupon already applied', "message" => "Coupon already applied"), 400);
                }
            }

            if($ratecard != null && $ticket == null){

                $resp["data"]["discount"] = $amount > $resp["data"]["discount"] ? $resp["data"]["discount"] : $amount;
            }

            $resp['status'] = 200;
            $resp['message'] = $resp['success_message'] = "Rs. ".$resp["data"]["discount"]." discount has been applied Successfully ";

            $resp['message'] = $resp['success_message'] = "Coupon has been applied successfully";

            if(isset($resp['custom_message'])){
                $resp['message'] = $resp['success_message'] = $resp['custom_message'];
            }

            if($resp["data"]["discount"] == 0){

                $resp['message'] = $resp['success_message'] = "Promo code applied Successfully.";

                if($this->device_type == 'android'){
                    $resp["data"]["discount"] = null;
                }
            }
            
            // if(strtolower($data['coupon']) == "fitlove" || $data['coupon'] == "fitlove"){
            //     $resp['success_message'] = $resp['message'] = "Basis slot availability, your surprise discount for this partner outlet is Rs ".$resp["data"]["discount"];
            // }
            // if(!$resp["vendor_routed_coupon"]){
            //     if($resp["data"]["discount"] <= 0){
    
            //         $resp['status'] = 400;
            //         $resp['message'] = $resp['error_message'] = "Cannot apply Coupon";
            //         $resp["coupon_applied"] = false;
    
            //         unset($resp['success_message']);
            //     }
            // }
            $resp['message'] = $resp['message']." Promotional fitcash and cashback will not be applicable with discount coupon";
            $resp['success_message'] = $resp['message'];
            return Response::json($resp,$resp['status']);

        }else{

            $errorMessage =  "Coupon is either not valid or expired";

            if(!empty($resp['error_message']) ){
                $errorMessage =  $resp['error_message'];
            }

            if(isset($resp['referral_coupon']) && $resp['referral_coupon']){
                $errorMessage =  $resp['message'];
            }

            $resp = array("status"=> 400, "message" => "Coupon not found", "error_message" =>$errorMessage, "data"=>$resp["data"]);

            if(checkAppVersionFromHeader(['ios'=>'4.9.0', 'android'=>0])){
                return Response::json($resp,200);    
            }else{
                return Response::json($resp,400);    
            }
        }

        return Response::json($resp,200);
    }

    public function convinienceFeeFlag(){

        $flag = true;

         $header_array = [
            "Device-Type"=>"",
            "App-Version"=>"",
            "Authorization-Vendor"=>""
        ];

        foreach ($header_array as $header_key => $header_value) {

            $value = Request::header($header_key);

            if($value != "" && $value != null && $value != 'null'){
               $header_array[$header_key] =  $value;
            }
            
        }

        $data['device_type'] = $header_array['Device-Type'];
        $data['app_version'] = $header_array['App-Version'];
        $data['authorization_vendor'] = $header_array['Authorization-Vendor'];

        $version_ios = '4.3';
        $version_android = '4.3';

        if(isset($_GET['device_type']) && $_GET['device_type'] == 'ios' && isset($_GET['app_version']) && (float)$_GET['app_version'] < $version_ios){
            $flag = false;
        }

        if(isset($_GET['device_type']) && $_GET['device_type'] == 'android' && isset($_GET['app_version']) && (float)$_GET['app_version'] < $version_android){
            $flag = false;
        }

        if($data['device_type'] == 'ios' && (float)$data['app_version'] < $version_ios){
            $flag = false;
        }

        if($data['device_type'] == 'android' && (float)$data['app_version'] < $version_android){
            $flag = false;
        }

        if($data['authorization_vendor'] != "" && $data['authorization_vendor'] != null && $data['authorization_vendor'] != 'null'){
           $flag = true;
        }

        return $flag;

    }

    public function walletOrderCapture(){
        // ini_set('always_populate_raw_post_data', -1);
        $data = Input::all();
        Log::info("wallet capture");
        Log::info($data);
        
        if(in_array($this->device_type, ['ios', 'android'])){
            $data['customer_source'] = $this->device_type;
        }
        
        $rules = array(
            'amount'=>'required',
            'customer_source'=>'required',
        );
        $validator = Validator::make($data,$rules);


        if ($validator->fails()) {
            Log::info($validator->errors());
            if(Config::get('app.debug')){
                return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
            }else{
                return Response::json(array('status' => 404,'message' => 'Invalid request'),404);
            }
        }
        $jwt_token = Request::header('Authorization');
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            $data['customer_email'] = $decoded->customer->email;
            $data['customer_name'] = $decoded->customer->name;
            if(!empty($decoded->customer->contact_no)){
                $data['customer_phone'] = $decoded->customer->contact_no;
            }else{
                $data['customer_phone'] = "";
            }
        }else{
            return Response::json(array("message"=>"Empty token or token should be string","status"=>401));
        }
        
        $data['type'] = 'wallet';
        
        $customerDetail = $this->getCustomerDetail($data);
        
        if($customerDetail['status'] != 200){
            return Response::json($customerDetail,$customerDetail['status']);
        }
        
        $data = array_merge($data,$customerDetail['data']);
        Log::info("before pledge");
        Log::info($data);

        $data["fitcash_amount"] = round($data['amount'] * (1 + Config::get('app.add_wallet_extra')/100));
        $data["additional_fitcash"] = $data['fitcash_amount']-$data['amount'];
        $data['amount_finder'] = 0;
        $data['payment_mode'] = 'paymentgateway';
        
        $data['status'] = "0";
        
        $order_id = $data['_id'] = $data['order_id'] = Order::maxId() + 1;
        $txnid = "";
        $successurl = "";
        $mobilehash = "";
        if($data['customer_source'] == "android" || $data['customer_source'] == "ios"){
            $txnid = "MFIT".$data['_id'];
            $successurl = $data['customer_source'] == "android" ? Config::get('app.website')."/paymentsuccessandroid" : Config::get('app.website')."/paymentsuccessios";
        }else{
            $txnid = "FIT".$data['_id'];
            $successurl = Config::get('app.website')."/paymentsuccessproduct";
        }
        $data['txnid'] = $txnid;
        $data['finder_name'] = 'Fitternity';
        $data['finder_slug'] = 'fitternity';
        
        $data['service_name'] = 'Wallet';
        
        $data['service_id'] = 100001;
        
        $hash = getHash($data);
        
        $data = array_merge($data,$hash);

        if(in_array($data['customer_source'],['android','ios','kiosk'])){
            $mobilehash = $data['payment_related_details_for_mobile_sdk_hash'];
        }
        
        $order = new Order($data);
        $order->_id = $order_id;
        
        $order->save();
        
        $result['firstname'] = strtolower($data['customer_name']);
        $result['lastname'] = "";
        $result['phone'] = $data['customer_phone'];
        $result['email'] = strtolower($data['customer_email']);
        $result['orderid'] = $data['_id'];
        $result['txnid'] = $txnid;
        $result['amount'] = $data['amount'];
        $result['productinfo'] = strtolower($data['productinfo']);
        $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($data['service_name']));
        $result['successurl'] = $successurl;
        $result['hash'] = $data['payment_hash'];
        $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
        $result['finder_name'] = strtolower($data['finder_name']);
        $result['fitcash_amount'] = $data['fitcash_amount'];
        $result['success_msg'] = $data['fitcash_amount']." fitcash has been added into your wallet";
        $result['type'] = 'wallet';
        
        
        $resp   =   array(
            'status' => 200,
            'data' => $result,
            'message' => "Tmp Order Generated Sucessfully"
        );
        $resp['data']['payment_modes'] = $this->getPaymentModes($resp);
        return Response::json($resp);
    }

    public function walletOrderSuccess(){
        $data = Input::json()->all();
        Log::info("wallet success");
        
        Log::info($data);
        
        $rules = array(
            'order_id'=>'required',
            'status'=>'required'
        );
        $validator = Validator::make($data,$rules);
        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }
        
        $order_id   =   (int) $data['order_id'];
        $order      =   Order::findOrFail($order_id);
        if(isset($order->status) && $order->status == '1'){
            $resp   =   array('status' => 401, 'statustxt' => 'error', "message" => "Already Status Successfull");
            return Response::json($resp,401);
        }
        $hash_verified = $this->utilities->verifyOrder($data,$order);
        // $hash_verified = true;
        if($data['status'] == 'success' && $hash_verified){
            $order->status = "1";

            $req = array(
                "customer_id"=>$order['customer_id'],
                "order_id"=>$order['_id'],
                "amount"=>$order['amount'],
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $order['amount'],
                "type"=>'CREDIT',
                'entry'=>'credit',
                'description'=>"Fitcash wallet recharge (Applicable on all transactions)",
                'duplicate_allowed'=>true,
                'for'=>"wallet_recharge"
            );
            Log::info($req);
            // $order->wallet_req = $req;
            $wallet = $this->utilities->walletTransaction($req, $order->toArray());
            Log::info("wallet");
            Log::info($wallet);


             $req = array(
                "customer_id"=>$order['customer_id'],
                "order_id"=>$order['_id'],
                "amount"=>$order['additional_fitcash'],
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $order['additional_fitcash'],
                "type"=>'CREDIT',
                'entry'=>'credit',
                'description'=>"10% additional bonus on wallet recharge (Applicable only on Workout Sessions)",
                'order_type'=>['workout-session', 'workout session'],
                'duplicate_allowed'=>true,
                'for'=>"wallet_recharge"
            );
            Log::info($req);
            // $order->wallet_req = $req;
            $wallet = $this->utilities->walletTransaction($req, $order->toArray());
            Log::info("wallet");
            Log::info($wallet);
            
            $redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),Config::get('app.queue'));
            // $order->redis_id = $redisid;
            $order->wallet_balance = $this->utilities->getWalletBalance($order['customer_id']);
            $order->website = "www.fitternity.com";
            $order->update();
            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful. ".$order['fitcash_amount']." fitcash has been added into your wallet");
            
        } else {
           
            if($hash_verified == false){
             
                $order->hash_verified = false;
                $order->update();
                
            }
           
            $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'message' => "Transaction Failed :)");
            
        }
        
        return Response::json($resp);
            
    }

    public function getFitternityShareAmount($data){
        
        Log::info( Order::active()->where('customer_id', $data['customer_id'])->where('type', 'wallet')->get());
        
        $prev_pledge_amount = Order::active()->where('customer_id', $data['customer_id'])->where('type', 'wallet')->sum('fitternity_share');

        $remaining_limit = 1000 - $prev_pledge_amount;

        $fitternity_share = $data['amount'] <= $remaining_limit ? $data['amount'] : $remaining_limit;

        return (int)$fitternity_share;
    
    }

    public function checkoutSummary(){

        $data = Input::json()->all();
        $headerSource = Request::header('Source');
        if(!empty($headerSource) && $headerSource=='multifit'){
            $data['multifit'] = true;
        }
        else if(!empty($headerSource) && $headerSource==Config::get('app.sbig_acronym') && (!empty($data['pass_id']) || !empty($data['order_id'])) && !empty($data['email'])){
            $data['sbig'] = true;
            $data['customer_source'] = "sbig";
            if(empty($data['pass_id'])) {
                $_passOrder = Order::find(intval($data['order_id']));
                $data['pass_id'] = $_passOrder['pass.pass_id'];
            }
            $data['coupon'] = $this->utilities->getSBIGCouponCode($headerSource, $data['email'], $data['pass_id']);
        }
        Log::info("checkoutSummary");

        Log::info($data);

        $result = [

            'order_details' => [],
            'payment_details' => [
                'amount_summary' => [],
                'amount_payable' => [],
                'note'=>""
            ],
            'full_wallet_payment' => false,
            'register_loyalty'=>false,
            'free_trial_available'=>false,
            'extended_validity'=>false
        ];

        $ratecard_id = null;

        $data['you_save'] = 0;

        if(!isset($data['ratecard_id']) && !isset($data['order_id']) && !isset($data['ticket_id']) && !isset($data['pass_id'])){
                
                return Response::json(array('status'=>400, 'message'=>'Order id or Ratecard id is required'), $this->error_status);
        }
        
        if(!isset($data['ratecard_id']) && isset($data['order_id'])){

            $order = Order::find(intval($data['order_id']));

            if(!empty($order['pass_id'])){
                $data['pass_id'] = $order['pass_id'];
            }

            if(!empty($order['schedule_slot']) && !empty($order['schedule_date'])){
                $data['slot'] = [
                    'slot_time'=>$order['schedule_slot'],
                    'date'=>$order['schedule_date']
                ];
            }

            if(isset($order->ratecard_id) && $order->ratecard_id != ''){
               
                $ratecard_id = $order->ratecard_id;
            
            }
			if(isset($order->ticket_id) && $order->ticket_id != ''){
               
                $ticket_id = $order->ticket_id;
            
            }

            $data['duration_day'] = !empty($order['duration_day']) ? $order['duration_day'] : null;
            $data['service_id'] = !empty($order['service_id']) ? $order['service_id'] : null;
            $data['finder_id'] = !empty($order['finder_id']) ? $order['finder_id'] : null;
            
            if(!empty($order->customer_email)){
                $data['customer_email'] = $order->customer_email;
            }

            if(empty($data['service_flags'])) {
                $data['service_flags'] = !empty($order['service_flags']) ? $order['service_flags'] : null;
            }
        
        }elseif(isset($data['ticket_id'])){
            if(empty($data['customer_quantity'])){
                return Response::json(array('status'=>400, 'message'=>'Customer quantity is required'), $this->error_status);
            }
			$ticket_id = intval($data['ticket_id']);
		}elseif(isset($data['ratecard_id'])){

                $ratecard_id = intval($data['ratecard_id']);
        }


        if($ratecard_id && $ratecard_id != ''){

            addAToGlobals('ratecard_id_for_wallet', $ratecard_id);

            $data['ratecard_id'] = $ratecard_id;
            
            Log::info("idifiifififififi");

            $ratecardDetail = $this->getRatecardDetail($data);
            
            if($ratecardDetail['status'] != 200){
                return Response::json($ratecardDetail,$this->error_status);
            }

            $data = array_merge($data,$ratecardDetail['data']);
            
            $finder_id = (int) $data['finder_id'];
            
            $finderDetail = $this->getFinderDetail($finder_id);
    
            if($finderDetail['status'] != 200){
                return Response::json($finderDetail,$this->error_status);
            }
    
            $data = array_merge($data,$finderDetail['data']);
            
            if(!empty($data['finder_flags']['enable_commission_discount']) && (!empty($data['type']) && $data['type'] == 'membership')){                
                $commission = getVendorCommision(['finder_id'=>$data['finder_id']]);
                
                if(!empty($commission)){
                    $data['amount'] = round($data['amount'] * (100 - $commission + Config::get('app.pg_charge'))/100);
                }
            }
            
            $data['amount_payable'] = $data['amount'];

            $jwt_token = Request::header('Authorization');

            if(!empty($data['customer_email'])){
                $data['order_customer_email'] = $data['customer_email'];
            }
            
            Log::info('jwt_token checkout summary: '.$jwt_token);

            if(!empty($jwt_token) && $jwt_token != 'null'){
                
                $decoded = customerTokenDecode($jwt_token);

                if(empty($data['customer_email'])){
                    $data['customer_email'] = $decoded->customer->email;

                    if(!empty($decoded->customer->contact_no)){
                        $data['customer_phone'] = $decoded->customer->contact_no;
                    }
                }
            }

            if(isset($data['service_id'])){
                $service_id = (int) $data['service_id'];
                
                // Service::$setAppends = array_merge(array_values(Service::getArrayableAppends()), ['freeTrialRatecards']);
                // Service::$withoutAppends = true;

                $serviceDetail = $this->getServiceDetail($service_id);
    
                if($serviceDetail['status'] != 200){
                    return Response::json($serviceDetail,$this->error_status);
                }
                
                $data = array_merge($data,$serviceDetail['data']);
                
                if(isset($data['type']) && in_array($data['type'],  ['workout-session', 'workout session', 'booktrials', 'booktrial']) && $data['servicecategory_id'] == 65){
                    $data['service_name'] = $this->utilities->getGymServiceNamePPS();
                }


            }
            
            if(!empty($data['type']) && in_array($data['type'], ['membership', 'memberships'])){
                $result['offer_text'] = "";
            }

            if(((isset($data['finder_flags']['disable_dynamic_pricing']) && empty($data['finder_flags']['disable_dynamic_pricing'])) || (isset($data['service_flags']['disable_dynamic_pricing']) && empty($data['service_flags']['disable_dynamic_pricing']))) && $data['type'] == 'workout session' && !empty($data['slot']['slot_time']) && $data['slot']['date'])
            {
                $start_time = explode('-', $data['slot']['slot_time'])[0];
                $end_time = explode('-', $data['slot']['slot_time'])[1];
                Log::info("dynamic price");
                $am_calc=$this->utilities->getWsSlotPrice($start_time,$end_time,$data['service_id'],$data['slot']['date']);
                if(isset($am_calc['peak'])){
                    $data['amount']  = $am_calc['peak'];
                    $data['peak'] = true;
                }else if(isset($am_calc['non_peak'])){
                    $data['amount']  = $am_calc['non_peak'];
                    $data['non_peak'] = true;
                    $data['non_peak_discount']  = $am_calc['non_peak_discount'];
                }
            }

            $data['ratecard_amount'] = $data['amount'];

            if(!empty($data['customer_quantity'])){
                $data['amount_payable'] = $data['amount']= $data['amount'] * $data['customer_quantity'];
                $result['customer_quantity'] = $data['customer_quantity'];
            }
            
            if(!empty($order['customer_quantity'])){
                $data['amount_payable'] = $data['amount']= $data['amount'] * $order['customer_quantity'];
                $result['customer_quantity'] = $order['customer_quantity'];
            }

            $ratecard = Ratecard::find(intval($data['ratecard_id']));

            $data['ratecard_price'] = $ratecard['price'];
            

            
            $result['payment_details']['amount_summary'][] = [
                'field' => 'Total Amount',
                'value' => 'Rs. '.(string)number_format($data['amount'])
            ];
            
            if($this->utilities->isConvinienceFeeApplicable($data)){
                
                $convinience_fee_percent = Config::get('app.convinience_fee');
                
                $convinience_fee = round($data['amount'] * $convinience_fee_percent/100);
                
                $convinience_fee = $convinience_fee <= 199 ? $convinience_fee : 199;

                $data['convinience_fee'] = $convinience_fee;
                
                
                $data['amount_payable'] = $data['amount_payable'] + $data['convinience_fee'];
                
                $result['payment_details']['amount_summary'][] = [
                    'field' => 'Convenience fee',
                    'value' => '+Rs. '.(string)$data['convinience_fee'],
                    /*"info" => "Convenience fees is applicable for exclusive offers on online payments & Cash on delivery."*/
                ];
            }

            if(!empty($data['slot']['date']) || $data['type'] == 'workout session' && !empty($data['customer_email']) && !((!empty($data['customer_quantity']) && $data['customer_quantity'] > 1) || (!empty($order['customer_quantity']) && $order['customer_quantity'] > 1)) ){
                $data['schedule_date'] = $data['slot']['date'];
                if(!empty($order['customer_email'])){
                    $data['customer_email'] = $order['customer_email'];
                }
                $extended_validity_order = $this->utilities->getExtendedValidityOrder($data);

                if($extended_validity_order){
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Session Pack Discount',
                        'value' => '-Rs. '.(string)number_format($data['amount'])
                    ];

                    $result['extended_validity'] = true;
                    $data['amount_payable'] = 0;
                }
            }

            //  commented on 9th Aug - Akhil
            if((!empty($data['typeofsession'])) && $data['typeofsession']=='trial-workout' && !(empty($data['customer_quantity'])) && $data['customer_quantity']==1) {
                if(!empty($decoded->customer->_id)) {
                    $scheduleDate = (!empty($data['slot']['date']))?$data['slot']['date']:null;
                    $passSession = $this->passService->allowSession($data['amount'], $decoded->customer->_id, $scheduleDate, $data['finder_id']);
                    Log::info('getCreditApplicable capture checkout response:::::::::', [$passSession]);
                    if(
                        $passSession['allow_session'] 
                        &&
                        (
                            (
                                !empty($data['service_flags']['classpass_available']) 
                                && $data['service_flags']['classpass_available']
                                &&
                                empty($passSession['onepass_lite'])
                            )
                            ||
                            (
                                !empty($passSession['onepass_lite'])
                                &&
                                !empty($data['service_flags']['lite_classpass_available'])
                                && 
                                $data['service_flags']['lite_classpass_available']
                            )
                        )
                    ) {
                        $result['payment_details']['amount_summary'][] = [
                            'field' => ((!empty($passSession['pass_type']) && $passSession['pass_type'] == 'unlimited')?'Unlimited Access':'Monthly Access').' Pass Applied',
                            'value' => "Unlimited Access Applied"//(string)$creditsApplicable['credits'].' Sweat Points Applied'
                        ];
                        $data['amount_payable'] = 0;
                    }
                }
            }

            // if((empty($data['init_source']) || $data['init_source'] != 'pps') && (empty($order['init_source']) || $order['init_source'] != 'pps') && !empty($data['amount_payable']) && (empty($data['coupon_code']) || strtoupper($data['coupon_code']) ==  "FIRSTPPSFREE") && $data['type'] == 'workout session' && (empty($data['customer_quantity']) || $data['customer_quantity'] == 1)){
            $first_session_free = false;
            if((empty($data['coupon']) || strtolower($data['coupon']) == 'firstppsfree') && (empty($data['init_source']) || $data['init_source'] != 'pps') && (empty($order['init_source']) || $order['init_source'] != 'pps') && !empty($data['amount_payable']) && $data['type'] == 'workout session' && (empty($data['customer_quantity']) || $data['customer_quantity'] == 1)){

                $free_trial_ratecard = Ratecard::where('service_id', $data['service_id'])
                ->where('type', 'trial')
                ->where('price', 0)
                ->where(function($query){
                    $query
                    ->orWhere('expiry_date', 'exists', false)
                    ->orWhere('expiry_date', '>', new MongoDate(strtotime('-1 days')));
                })
                ->where(function($query){
                    $query
                    ->orWhere('start_date', 'exists', false)
                    ->orWhere('start_date', '<', new MongoDate(time()));
                })
                ->first();

                if($free_trial_ratecard){
                    $already_booked_trials = $this->utilities->checkTrialAlreadyBooked($data['finder_id'], null, !empty($data['customer_email']) ? $data['customer_email'] : '', !empty($data['customer_phone']) ? $data['customer_phone'] : null , true, 'checkoutSummary');
                    if(empty($already_booked_trials)){

                        $data['coupon_discount'] = $data['ratecard_amount'];

                        $data['amount_payable'] = $data['amount_payable'] - $data['coupon_discount'];
                        
                        $data['you_save'] += $data['coupon_discount'];

                        $result['free_trial_available'] = true;
                        
                        $result['coupon_code'] = "FIRSTPPSFREE";
                        
                        $result['payment_details']['amount_summary'][] = [
                            'field' => 'Coupon Discount',
                            'value' => '-Rs. '.(string) number_format($data['coupon_discount'])
                        ];

                        $first_session_free = true;

                        // $result['payment_details']['free_session_coupon'] = "FREE";
                        
                    }else{
                        Log::info($already_booked_trials['created_at']);
                        $last_booked_at = date('dS M Y', strtotime($already_booked_trials['created_at']));
                    }
                }
            }
            Log::info("amount_payable 1",[$data['amount_payable']]);
            Log::info("you_save 1",[$data['you_save']]);
            $jwt_token = Request::header('Authorization');
                
            if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
                $decoded = customerTokenDecode($jwt_token);
                $corporate_discount = !empty($decoded->customer->corporate_discount) ? $decoded->customer->corporate_discount : false;
            }
            // Log::info("corporate_discount  :::", [$corporate_discount]);
            $data['actual_amount'] = $data['amount'];
            if(!empty($corporate_discount) && $corporate_discount){
                Log::info("corporate_discount");
                $coupons = Coupon::where('overall_coupon', true)->orderBy('overall_coupon_order', 'desc')->get(['code', 'flags']);
                // return $coupon;
                if(!empty($coupons)){
                    foreach($coupons as $coupon){
                        // if(!empty($coupon)){
                            Log::info("coupon :::",[$coupon['code']]);
                            $customer_id_for_coupon = isset($customer_id) ? $customer_id : false;
                            $customer_email = !empty($data['customer_email']) ? $data['customer_email'] : null;
        
                            $resp1 = $this->customerreward->couponCodeDiscountCheck($ratecard, $coupon['code'],$customer_id_for_coupon, null, null, null, $data['amount'], $customer_email, null, null, $corporate_discount_coupon = true);
                            // Log::info("resp1 :::", [$resp1]);
                            if($resp1["coupon_applied"]){
                                Log::info("corporate_discount_coupon_applied");
                                $data['corporate_coupon_discount'] = $data['amount_payable'] > $resp1['data']['discount'] ? $resp1['data']['discount'] : $data['amount_payable'];
        
                                $data['amount_payable'] = $data['amount_payable'] - $data['corporate_coupon_discount'];
                                
                                $data['you_save'] += $data['corporate_coupon_discount'];
                                
                                if((isset($data['corporate_coupon_discount']) && $data['corporate_coupon_discount'] > 0) || (!empty($coupon['flags']['cashback_100_per']))){
                                    $result['payment_details']['amount_summary'][] = [
                                        'field' => 'Corporate Discount (Coupon: '.strtoupper($coupon['code']).')',
                                        'value' => !empty($data['corporate_coupon_discount']) ? '-Rs. '.$data['corporate_coupon_discount'] : "100% Cashback"
                                    ];
                                }else{
                                    $result['payment_details']['amount_summary'][] = [
                                        'field' => 'Corporate Discount (Coupon: '.strtoupper($coupon['code']).')',
                                        'value' => '-Rs. '.(string) number_format($data['corporate_coupon_discount'])
                                    ];
                                }
        
                                $data['amount'] = $data['amount'] - $data['corporate_coupon_discount'];
                                
                                break;
                            }
                        // }
                    }
                }
                
                
            }

            Log::info("amount_payable 2",[$data['amount_payable']]);
            Log::info("you_save 2",[$data['you_save']]);

            if(!empty($order['coupon_code'])){
                $data['coupon'] = $order['coupon_code'];
            }

            if(isset($data['coupon']) && strtolower($data['coupon']) != 'firstppsfree'){
                $customer_id_for_coupon = isset($customer_id) ? $customer_id : false;
                $customer_email = !empty($data['customer_email']) ? $data['customer_email'] : null;
                
                $resp = $this->customerreward->couponCodeDiscountCheck($ratecard, $data['coupon'],$customer_id_for_coupon, null, null, null, $data['amount'], $customer_email, null, $first_session_free);
                if($resp["coupon_applied"]){
                    
                    $data['coupon_discount'] = $data['amount_payable'] > $resp['data']['discount'] ? $resp['data']['discount'] : $data['amount_payable'];

                    $data['amount_payable'] = $data['amount_payable'] - $data['coupon_discount'];
                    
                    $data['you_save'] += $data['coupon_discount'];
                    
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Coupon Discount',
                        'value' => '-Rs. '.(string) number_format($data['coupon_discount'])
                    ];
                
                }

            }

            Log::info("amount_payable 3",[$data['amount_payable']]);
            Log::info("you_save 3",[$data['you_save']]);
                
            if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
                
                $decoded = !empty($decoded) ? $decoded : customerTokenDecode($jwt_token);
                
                $customer_id = $decoded->customer->_id;
                $data['wallet_balance'] = 0;
                if(!empty($order)){

                    if(!empty($order['wallet_amount'])){
                        $data['wallet_balance'] = $order['wallet_amount'];
                    }

                }else{
                    $getWalletBalanceData = [
                        'finder_id'=>$ratecard['finder_id'],
                        'order_type'=>$ratecard['type'],
                        'city_id'=>!empty($data['city_id']) ? $data['city_id'] : null,
                        'service_id'=>!empty($data['service_id']) ? $data['service_id'] : null,
                        'duration_day'=>!empty($data['duration_day']) ? $data['duration_day'] : null
                    ];
                    if(isset($ratecard) && isset($ratecard["flags"]) && isset($ratecard["flags"]["pay_at_vendor"]) && $ratecard["flags"]["pay_at_vendor"] == True){
                        $data['wallet_balance'] = 0;
                    }else{

                        Log::info("customer   email ::: ", [$decoded->customer->email]);
                        if(!empty($data['order_customer_email']) && $data['order_customer_email'] != $decoded->customer->email){
                            Log::info("checkoutsummary email is differ");
                            $getWalletBalanceData['buy_for_other'] = true;
                        }

                        $data['wallet_balance'] = $this->utilities->getWalletBalance($customer_id,$getWalletBalanceData);
                    }
                }



                $data['fitcash_applied'] = $data['amount_payable'] > $data['wallet_balance'] ? $data['wallet_balance'] : $data['amount_payable'];
                
                $data['amount_payable'] -= $data['fitcash_applied'];
                if($data['fitcash_applied'] > 0){

                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Fitcash Applied',
                        'value' => '-Rs. '.(string)number_format($data['fitcash_applied'])
                    ];

                    $data['you_save'] += $data['fitcash_applied'];

                }

                $customer = Customer::find($customer_id);
                $result['register_loyalty'] = empty($customer['loyalty']);
            }

            Log::info("amount_payable 4",[$data['amount_payable']]);
            Log::info("you_save 4",[$data['you_save']]);

            $result['payment_details']['amount_payable'] = [
                'field' => 'Total Amount Payable',
                'value' => 'Rs. '.(string)number_format($data['amount_payable'])
            ];

            // if(!empty($first_session_free) && $data['amount_payable'] == 0){
            //     $result['payment_details']['amount_payable']['value'] = "Free via Fitternity";
            // }

            if(!empty($headerSource) && $headerSource=='multifit' && !empty($first_session_free) && $data['amount_payable'] == 0) {
                $result['payment_details']['amount_payable']['value'] = "Free via Multifit";
            }

            if($data['amount_payable'] == 0){
                $result['full_wallet_payment'] = true;
            }



            $result['order_details'] = [
                "studio_name"=>[
                    "field"=> "",
                    "value"=> $data['finder_name'] . "," . $data['finder_location']
                ],
                "service_name"=>[
                    "field"=> "",
                    "value"=>  $data['service_name']
                ],
                "duration_amount"=>[
                    "field"=> $data['service_duration'],
                    "value"=> "Rs. ".number_format($data['amount'])
                ],
                "remarks"=>[
                    "field"=> "REMARKS",
                    "value"=> $data['ratecard_remarks']
                ]
                // "address"=>[
                //     "field"=> "ADDRESS",
                //     "value"=> $data['finder_address']
                // ]
            ];

            // if(in_array($ratecard['type'], ['trial', 'workout session'])){
                $result['order_details'] = [
                    "session"=>[
                        "field"=> $data['service_name'],
                        "value"=> "₹ ".number_format($data['actual_amount'])
                    ]
                ];

                if(!empty($last_booked_at)){
                    $result['order_details']["last_booked_at"]= [
                        "field"=> "Last booked at",
                        "value"=> $last_booked_at,
                        "type"=> "subtext",
                        "long_text"=> "You have already availed 1 free session at ".$data['finder_name'],
                    ];
                }
                 if(isset($data['slot'])){
                    $result['order_details']['date'] = [
                        "field"=>"Date",
                        "value"=>date('dS M', strtotime($data['slot']['date']))
                    ];
                     $result['order_details']['time'] = [
                        "field"=>"Time",
                        "value"=>trim(explode('-', $data['slot']['slot_time'])[0])
                    ];
                }
                $result['finder_name'] = $data['finder_name'];
                $result['finder_location'] = $data['finder_location'];
            // }

            if(!empty($result['order_details']['date']) && !empty($result['order_details']['time'])){
                $result['order_details']['date_time'] = [
                    'field'=> 'Date & Time',
                    'value'=>$result['order_details']['date']['value'].' at '.$result['order_details']['time']['value']
                ];

                unset($result['order_details']['date']);
                unset($result['order_details']['time']);
            }

            if(isset($data['reward_ids'])){
                
                $reward = Reward::find(intval($data['reward_ids'][0]));

                if($reward){
                    $reward_title = $reward['title'];
                    $reward_amount = $reward['payload']['amount'];
                    
                    $result['order_details']['reward'] = [
                        'field' => "REWARD ($reward_title)",
                        'value' =>  ""
                    ];

                    $data['you_save'] += $reward_amount;
                
                }
            }

            if(isset($data['cashback'])){
                

                $result['order_details']['reward'] = [
                    'field' => 'REWARD(Cashback)',
                    'value' => "Rs. ".$data['cashback']
                ];

                $data['you_save'] += intval($data['cashback']);
                
            }

            if($data['finder_category_id']==42){
                
                // $result['order_details']['studio_name']['field'] = "TIFFIN SERVICE";

                if(isset($result['order_details']['address'])){
                    unset($result['order_details']['address']);
                };
            }

            if($data['finder_slug'] == 'fitternity-diet-vendor-andheri-east'){
                
                unset($result['order_details']['studio_name']);
                
                if(isset($result['order_details']['address'])){
                    unset($result['order_details']['address']);
                }                    
            }
            $result['order_details'] = array_values($result['order_details']);

            if($data['you_save'] > 0 && (empty($data['type']) || $data['type'] != 'workout session')){
                $result['payment_details']['savings'] = [
                    'field' => 'Your total savings',
                    'value' => "Rs. ".number_format($data['you_save']),
                    'amount' => $data['you_save']
                ];
            }

        }elseif(isset($ticket_id)){
			if(isset($order)){
                if(!empty($data['coupon'])){
                    $order["coupon_code"] = $data['coupon'];
                }
				$data = $order;
				$data["customer_quantity"] = $order["ticket_quantity"];
				$data["ticket_id"] = $order["ticket_id"];
				$data["coupon"] = $order["coupon_code"];
			}
			$ticket = Ticket::where("_id", intval($ticket_id))->with("event")->get();
			$ticket = $ticket[0];
			$result["event_name"] = $ticket["name"];
			$result["event_id"] = $ticket["event"]["_id"];
			$result["ticket_id"] = $ticket["_id"];
			$result["finder_id"] = isset($ticket["event"]["finder_id"]) ? $ticket["event"]["finder_id"] : "";
            $result['customer_quantity'] = isset($data['customer_quantity']) ? $data['customer_quantity'] : 1;
			$result['order_details'] = [
                "start_date"=>[
                    "field"=> "Date",
                    "value"=> date('d-m-Y', strtotime($ticket["start_date"]))
                ],
                "date_time"=>[
                    "field"=> "Start Time",
                    "value"=> date('H:i:s', strtotime($ticket["start_date"]))
                ],
                // "discount"=>[
                //     "field"=> "Pay Now Discount(20% Off)",
                //     "value"=> "Rs. ".($ticket['price'] * 20/100)
                // ]
                // "address"=>[
                //     "field"=> "ADDRESS",
                //     "value"=> $data['finder_address']
                // ]
            ];
            !empty($data['customer_quantity']) ? $data['customer_quantity'] = intval($data['customer_quantity']) : null;
            !empty($ticket['minimum_no_of_ticket']) ? $data['customer_quantity']= intval(($ticket['minimum_no_of_ticket'])):null;
			
            $total_amount = $ticket['price'] * intval($data['customer_quantity']);
			$result['payment_details']['amount_summary'][] = [
                'field' => 'Total Amount',
                'value' => 'Rs. '.(string)$total_amount
            ];

            if(!empty($data['ticket_id']) && $data['ticket_id'] == 494 && !empty($data['customer_quantity']) && is_integer($data['customer_quantity']) && $data['customer_quantity'] % 2 == 0){
                
                $ticket_discount = ($data['customer_quantity']/2) * 150;
            
                $total_amount = $total_amount - $ticket_discount;

                if(!empty($ticket_discount)){
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Ticket Discount',
                        'value' => '-Rs. '.(string)$ticket_discount
                    ];
                }
            }
            
            if(!empty($data['coupon'])){
				$resp = $this->customerreward->couponCodeDiscountCheck(array(),$data['coupon'],null, $ticket, $data["customer_quantity"]); 	
                // $resp = $this->customerreward->couponCodeDiscountCheck($ratecard, $data['coupon']);
				
                if($resp["coupon_applied"]){
					
                    $data['coupon_discount'] = $total_amount > $resp['data']['discount'] ? $resp['data']['discount'] : $total_amount;

                    $total_amount = $total_amount - $data['coupon_discount'];
                    Log::info($total_amount);
                    $data['you_save'] += $data['coupon_discount'];
                    
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Coupon Discount',
                        'value' => '-Rs. '.(string)$data['coupon_discount']
                    ];
					
                
                }

            }

            $result['payment_details']['amount_summary'][] = [
                'field' => 'Total Payable Amount',
                'value' => 'Rs. '.(string)$total_amount
            ];

            $data['amount_payable'] = $total_amount;

        }elseif (isset($data['pass_id'])){
            $order = null;
            if(!empty($data['order_id'])) {
                $order_id = $data['order_id'];
    
                $order = Order::find(intval($order_id));
            }
            $coupon = null;
            if(!empty($data['coupon'])) {
                $coupon = $data['coupon'];
            }
            if(!empty($data['pass_id'])){
                $pass = Pass::where('pass_id', intval($data['pass_id']))->first();

                $data['pass'] = $pass;
                
                $jwt_token = Request::header('Authorization');

                if(!empty($jwt_token) && $jwt_token != 'null'){
                    
                    $decoded = customerTokenDecode($jwt_token);

                    if(!empty($decoded)){
                        $data['logged_in_customer_id'] = $data['customer_id'] = $decoded->customer->_id;
                    }
                }                

                $resp = $this->customerreward->couponCodeDiscountCheck(null, $coupon, false,  null, 1, null, null, null, $pass);

                $result['order_details'] = [
                    [
                        "field"=> $pass['name'],
                        "value"=> "₹ ".$pass['price']
                    ]
                ];
                $data['amount_payable'] = $pass['price'];

                $result['payment_details']['amount_summary'][] = [
                    'field' => 'Total Payable Amount',
                    'value' => 'Rs. '.(string)$pass['price']
                ];

                if(!empty($resp["coupon_applied"])){
                    
                    $data['coupon_discount'] = $data['amount_payable'] > $resp['data']['discount'] ? $resp['data']['discount'] : $data['amount_payable'];

                    $data['amount_payable'] = $data['amount_payable'] - $data['coupon_discount'];
                    
                    $data['you_save'] += $data['coupon_discount'];
                    
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Coupon Discount',
                        'value' => '-Rs. '.(string)$data['coupon_discount']
                    ];
                }
                else if(!empty($order["coupon_discount_amount"])){
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Coupon Discount',
                        'value' => '-Rs. '.(string)$order["coupon_discount_amount"]
                    ];
                    $data['amount_payable'] = $data['amount_payable'] - $order["coupon_discount_amount"];
                    $data['you_save'] += $order["coupon_discount_amount"];
                }
                
                $data['amount'] = $data['amount_payable'];
                
                $this->passService->applyFitcash($data);

                
                if(!empty($data['fitcash'])){
                    
                    $data['fitcash_applied'] = $data['amount_payable'] > $data['fitcash'] ? $data['fitcash'] : $data['amount_payable'];
                
                    $data['amount_payable'] -= $data['fitcash_applied'];
                    
                    if($data['fitcash_applied'] > 0){
    
                        $result['payment_details']['amount_summary'][] = [
                            'field' => 'Fitcash Applied',
                            'value' => '-Rs. '.(string)number_format($data['fitcash_applied'])
                        ];
    
                        $data['you_save'] += $data['fitcash_applied'];
    
                    }
                }
                // return $data;
                
                $result['payment_details']['amount_payable'] = [
                    'field' => 'Total Amount Payable',
                    'value' => 'Rs. '.(string)$data['amount_payable']
                ];
                $result['finder_name'] = "ONEPASS";
                if(empty($pass['lite'])){
                    $result['finder_location'] = (!empty($pass['pass_type']) && $pass['pass_type']!='hybrid')?strtoupper($pass['pass_type']):strtoupper($pass['branding']);
                }
                else{
                    $result['finder_location'] = "LITE";
                }
            }

        }else{

            $order_id = $data['order_id'];
    
            $order = Order::find(intval($order_id));
            $result['customer_quantity'] = 1;

            $result['order_details'] = [
                "studio_name"=>[
                    "field"=> "",
                    "value"=> $order['finder_name'].",".$order['finder_location']
                ],
                "service_name"=>[
                    "field"=> "",
                    "value"=>  $order['service_name']
                ],
                "duration_amount"=>[
                    "field"=> $order['service_duration'],
                    "value"=> "Rs. ".$order['amount']
                ]
                // "address"=>[
                //     "field"=> "ADDRESS",
                //     "value"=> $data['finder_address']
                // ]
            ];

            $result['order_details'] = [
                "session"=>[
                    "field"=> $order['service_name'],
                    "value"=> "₹ ".number_format($order['amount'])
                ]
            ];
             if(isset($data['slot'])){
                $result['order_details']['date'] = [
                    "field"=>"Date",
                    "value"=>date('dS M Y', strtotime($data['slot']['date']))
                ];
                 $result['order_details']['time'] = [
                    "field"=>"Time",
                    "value"=>$data['slot']['slot_time']
                ];
            }
            !empty($order['finder_name']) ? $result['finder_name'] = $order['finder_name'] : null;
            !empty($order['finder_location']) ? $result['finder_location'] = $order['finder_location'] : null;

            $data['you_save'] = 0;

            $result['payment_details']['amount_summary'][] = [
                'field' => 'Total Amount',
                'value' => 'Rs. '.(string)$order['amount']
            ];

            $data['amount_payable'] = $order['amount'];

            $jwt_token = Request::header('Authorization');
            
            Log::info('jwt_token checkout summary: '.$jwt_token);
                
            if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null' && (empty($order['type']) || $order['type'] != 'wallet')){
                
                $decoded = customerTokenDecode($jwt_token);
                
                $customer_id = $decoded->customer->_id;

                $getWalletBalanceData = [
                    'finder_id'=>$order['finder_id'],
                    'order_type'=>$order['type'],
                    'city_id' => $order['city_id'],
                ];

                Log::info("customer   email ::: ", [$decoded->customer->email]);
                Log::info("order customer   email ::: ", [$order['customer_email']]);
                if(!empty($order['customer_email']) && $order['customer_email'] != $decoded->customer->email){
                Log::info("checkoutsummary else email is differ");
                    $getWalletBalanceData['buy_for_other'] = true;
                }

                $data['wallet_balance'] = $this->utilities->getWalletBalance($customer_id,$getWalletBalanceData);

                $data['fitcash_applied'] = $data['amount_payable'] > $data['wallet_balance'] ? $data['wallet_balance'] : $data['amount_payable'];
                
                $data['amount_payable'] -= $data['fitcash_applied'];
                if($data['fitcash_applied'] > 0){

                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Fitcash Applied',
                        'value' => '-Rs. '.(string)$data['fitcash_applied']
                    ];

                    $data['you_save'] += $data['fitcash_applied'];

                }
            }
            
            if(!empty($order['ratecard_id'])){
                $ratecard = Ratecard::where('_id',$order['ratecard_id'])->first();
            }

            if(!empty(Request::header('corporate_discount')) && Request::header('corporate_discount')){
                Log::info("corporate_discount");
                $coupon = Coupon::where('overall_coupon', true)->orderBy('overall_coupon_order', 'desc')->first(['code']);
                // return $coupon;
                
                if(!empty($coupon) && !empty($ratecard)){
                    
                    $resp1 = $this->customerreward->couponCodeDiscountCheck($ratecard, $coupon['code']);

                    if($resp1["coupon_applied"]){
                        
                        $data['corporate_coupon_discount'] = $data['amount_payable'] > $resp1['data']['discount'] ? $resp1['data']['discount'] : $data['amount_payable'];

                        $data['amount_payable'] = $data['amount_payable'] - $data['corporate_coupon_discount'];
                        
                        $data['you_save'] += $data['corporate_coupon_discount'];
                        
                        $result['payment_details']['amount_summary'][] = [
                            'field' => 'Corporate Discount (Coupon: '.strtoupper($coupon['code']).')',
                            'value' => '-Rs. '.(string)$data['corporate_coupon_discount']
                        ];
                    
                    }
                }
                
            }
            
            if(!empty($data['coupon']) && !empty($ratecard)){
                
                $resp = $this->customerreward->couponCodeDiscountCheck($ratecard, $data['coupon']);

                if($resp["coupon_applied"]){
                    
                    $data['coupon_discount'] = $data['amount_payable'] > $resp['data']['discount'] ? $resp['data']['discount'] : $data['amount_payable'];

                    $data['amount_payable'] = $data['amount_payable'] - $data['coupon_discount'];
                    
                    $data['you_save'] += $data['coupon_discount'];
                    
                    $result['payment_details']['amount_summary'][] = [
                        'field' => 'Coupon Discount',
                        'value' => '-Rs. '.(string)$data['coupon_discount']
                    ];
                
                }

            }

            // if(isset($data['reward_ids'])){
                
            //     $reward = Reward::find(intval($data['reward_ids'][0]));

            //     if($reward){
            //         $reward_title = $reward['title'];
            //         $reward_amount = $reward['payload']['amount'];
                    
            //         $result['order_details']['reward'] = [
            //             'field' => "REWARD ($reward_title)",
            //             'value' =>  ""
            //         ];

            //         $data['you_save'] += $reward_amount;
                
            //     }
            // }

            if(isset($data['cashback'])){
                

                $result['order_details']['reward'] = [
                    'field' => 'REWARD (Cashback)',
                    'value' => "Rs. ".$data['cashback']
                ];

                $data['you_save'] += intval($data['cashback']);

            }
                

            $result['payment_details']['amount_payable'] = [
                'field' => 'Total Amount Payable',
                'value' => 'Rs. '.(string)$data['amount_payable']
            ];

            if($data['amount_payable'] == 0){
                $result['full_wallet_payment'] = true;
            }

            if($data['you_save'] > 0){
                $result['payment_details']['savings'] = [
                    'field' => 'Your total savings',
                    'value' => "Rs. ".$data['you_save'],
                ];
            }
            $result['order_details'] = array_values($result['order_details']);
            

            //     $ratecard = Ratecard::find(intval($order->ratecard_id));
                    
            //     $order_details= $this->getBookingDetails($order->toArray());
                
            //     $result['order_details'] = [];

            //     $reward_amount = 0;

            //     if(isset($order['reward_ids']) && !empty($order['reward_ids'])){
            //         $reward_ids = array_map('intval',$data['reward_ids']);
            //         $rewards = Reward::whereIn('_id',$reward_ids)->get(array('payload'));
            //         if(count($rewards) > 0){
            //             foreach ($rewards as $value) {
            //                 $reward_amount += $value['payload']['price'];
            //             }
            //         }
            //     }
            //     if(isset($order['cashback']) && $order['cashback']){
            //         $reward_amount += $order['cashback_detail']['wallet_amount'];
            //     }

            //     foreach($order_details as $value){
            //         if(in_array($value['field'], ['ADDRESS', 'START DATE'])){
            //             continue;
            //         }

            //         if(!in_array($value['field'], ['REWARD', 'DURATION'])){
            //             $value['field'] = "";
            //         }

            //         if(in_array($value['field'], ['DURATION'])){
            //             $value['field'] = "";
            //             $value['value'] = "Rs. ".$ratecard['price'];
            //         }

            //         $result['order_details'][] = $value;

            //     }

            //     $result['payment_details'] = $this->getPaymentDetails($order->toArray(),'paymentgateway');
                
            //     if($reward_amount > 0){
            //         if(isset($result['payment_details']['savings'])){
            //             $savings_amount = $reward_amount + $result['payment_details']['savings']['amount'];
            //             $result['payment_details']['savings'] = [
            //                 'field' => 'Your total savings',
            //                 'value' => "Rs. ".$savings_amount
            //             ];
            //         }else{
            //             $result['payment_details']['savings'] = [
            //                 'field' => 'Your total savings',
            //                 'value' => "Rs.".$reward_amount
            //             ];
            //         }
            //     }
            
        }

        return $result;
    }

    public function codOtpSuccess(){

        $jwt_token              =   Request::header('Authorization');
        
        $data = Input::json()->all();

        $rules = [
            'order_id'  => 'required',
            'otp'       => 'required'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }   
        
        if($jwt_token != null){
            
            $decoded = customerTokenDecode($jwt_token);
        
        }        

        $customer_id = (int)($decoded->customer->_id);
            

        $order_id = (int)$data['order_id'];

        $otp = $data['otp'];

        $order = Order::where('customer_id', $customer_id)->where('_id', $order_id)->where(function($query) use ($otp){$query->orWhere('cod_otp', $otp)->orWhere("otp_data.otp", $otp); })->first();

        if(!$order){
            return Response::json(array('status' => 404,'message' => 'Please enter the valid code'), $this->error_status);
        }
        if(isset($order["cod_otp"]) && $order["cod_otp"] == $otp){
            $order->cod_otp_verified = true;
        }
        if(isset($order["otp_data"]) && isset($order["otp_data"]["otp"]) && $order["otp_data"]["otp"] == $otp){
            $order->vendor_otp_verified = true;
        }

        $order->update();

        $success_data = ['order_id'=> $order_id, 'status' => 'success'];
        
        $data_keys = ['customer_name','customer_email','customer_phone','finder_id','service_name','amount','type'];

        foreach($data_keys as $key){
            $success_data[$key] = $order[$key];
        }

        $this->successCommon($success_data);

    }

    public function locateMembership($code){

        $order_id = (int) $code;

        $code = (string) $code;

        $decodeKioskVendorToken = decodeKioskVendorToken();

        $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

        $response = array('status' => 400,'message' =>'Sorry! Cannot locate your membership');

        $order = false;

        $order = Order::active()->where('type','memberships')->where('finder_id',(int)$vendor['_id'])->where('code',$code)->first();

        if(!$order){
  
            $order = Order::active()->where('type','memberships')->where('finder_id',(int)$vendor['_id'])->find($order_id);
        }

        $locate_data = [
            'code'=>$code,
            'finder_id'=>(int)$vendor['_id'],
            'transaction_type'=>'Order'
        ];
        
        $locateTransaction = LocateTransaction::create($locate_data); 

        if($order){

            $order_id = (int) $order['_id'];

            $data = [];

            $preferred_starting_date = date('Y-m-d 00:00:00',time());

            $data['start_date'] = $preferred_starting_date;
            $data['preferred_starting_date'] = $preferred_starting_date;
            $data['end_date'] = date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date']."+ ".($order->duration_day-1)." days"));
            $data['locate_membership_date'] = time();

            $order->update($data);

            $locateTransaction->transaction_id = (int)$order['_id'];
            $locateTransaction->transaction_type = 'Order';
            $locateTransaction->update();

            $message = "You are good to go your ".ucwords($order['service_duration'])." ".ucwords($order['service_name'])." membership has been activated";

            $createCustomerToken = createCustomerToken((int)$order['customer_id']);

            $response = [
                'status' => 200,
                'message' => $message,
                'token'=>$createCustomerToken,
                'order_id'=> (int)$order['_id']
            ];

            $order_data = $order->toArray();

            $order_data['membership_locate'] = 'locate';

            $response = array_merge($response,$this->utilities->membershipBookedLocateScreen($order_data));

        }

        return Response::json($response,200);

    }

    public function rewardScreen(){
    	$data = [];
    	$data['title'] = 'Complimentry Rewards';
    	$data['banner'] = 'https://b.fitn.in/global/Rewards-page/rewards-web-banner.png';
    	$data['rewards'] = [];
    	$data['rewards'][] = [
    			'title' => 'Merchandise Kits',
    			'description'=>'We have shaped the perfect fitness kit for you. Strike off these workout essentials from your cheat sheet & get going.',
    			'type'=>'fitness_kit',
    			'items'=>Config::get('rewardscreenitems.complimentry_rewards.merchandise_kits'),
    	];
    	$data['rewards'][] = [
    			'title'=>'Online Diet Consultation',
    			'description'=>"Eating right is 70% & workout is 30% of leading a healthy & fit lifestyle! Fitternity's got you covered 100% cover.",
    			'type'=>'diet_plan',
    			'items'=>Config::get('rewardscreenitems.complimentry_rewards.online_diet_consultation'),
    	];
    	$data['rewards'][] = [
    			'title'=>'Instant Cashback',
    			'description'=>"Who doesn't love some money in their wallet? Get 5% back on your purchase!",
    			'type'=>'cashback',
    			'items'=>Config::get('rewardscreenitems.complimentry_rewards.instant_cashback'),
    	];
    	return Response::json($data);
    }

    public function getCustomMembershipDetails(){

        $decodeKioskVendorToken = decodeKioskVendorToken();

        $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

        $finder_id = (int)$vendor['_id'];

        $data = [];

        $active_service_category_id = Service::active()->where('finder_id',$finder_id)->lists('servicecategory_id');

        $data['service_categories'] = [];

        if(!empty($active_service_category_id)){    

            $active_service_category_id  = array_map('intval',$active_service_category_id);

            $service_categories_array = Servicecategory::active()->whereIn('_id',$active_service_category_id)->where('parent_id',0)->lists('name','_id');

            foreach ($service_categories_array as $key => $value) {

                $array = [
                    'id'=>(int) $key,
                    'name'=>$value
                ];

                $data['service_categories'][] = $array;           
            }
        }

        $data['validity_type'] = [
            [
                'id'=>'day',
                'name'=>'Day'
            ],
            [
                'id'=>'month',
                'name'=>'Month'
            ],
            [
                'id'=>'year',
                'name'=>'Year'
            ]
        ];

        $data['validity'] = [];
        
        for ($i=1; $i <= 30; $i++) {

            $array = [
                'id'=> $i,
                'name'=> (string) $i
            ];

            $data['validity'][] = $array;
        }

        $data['sale_done_by'] = $this->utilities->getVendorTrainer($finder_id);

        $data['vendor_membership_type'] = [
            [
                'id'=>'gym_studio',
                'name'=>'Gym / Fitness Studio'
            ],
            [
                'id'=>'pt',
                'name'=>'Personal Trainer'
            ]
        ];

        return Response::json($data);

    }

    public function generateAmazonUrl(){
        $config = Config::get('amazonpay.config');
        $client = new PWAINBackendSDKNon($config);
        $post_params = Input::all();
        $rules = array(
            'order_id'=>'required'
        );
        $validator = Validator::make($post_params,$rules);
        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }
        Log::info('input at generate url:::::',Input::all());
        //if(isset($post_params["order_id"])){
            $order = Order::find((int) $post_params["order_id"] );
            //Log::info($order);
            $val['orderTotalAmount'] = $order->amount;
            $val['sellerOrderId'] = $order->txnid;
        //}
        // else{
        //     $val['orderTotalAmount'] = $post_params['orderTotalAmount'];
        // }
        $val['orderTotalCurrencyCode'] = "INR";
        //$val['sellerNote'] = 'some Note';
        //$val['sellerStoreName']= 'Fitternity';
        // $val['transactionTimeout'] = Config::get('amazonpay.timeout');
        // For testing in sandbox mode, remove for production
        $val['isSandbox'] = Config::get('app.amazonpay_isSandbox');
        $returnUrl = Config::get('app.url')."/verifyamazonchecksum/1";
        Log::info('return url:::::>>>>>>>>>>>>>>>>>>>',[$returnUrl]);
        // $returnUrl = "http://ar-deepthi.com/amazonpay/thankyou.php";
        $redirectUrl = $client->getProcessPaymentUrl($val, $returnUrl);
        Log::info('redirct url ::::::::::::::::::::',[$redirectUrl]);
        return $redirectUrl;
    }

    public function generateAmazonChecksum(){
        
        $config = Config::get('amazonpay.config');
        
        $client = new PWAINBackendSDKNon($config);
        // Request can be either GET or POST
        $val = ($_POST);
        // For testing in sandbox mode, remove for production
        // $val['isSandbox'] = "true";
        // $val['isSandbox'] = Config::get('app.amazonpay_isSandbox');
        
        unset($val['sellerId']);
        $response = $client->generateSignatureAndEncrypt($val);
        return $response;
    }

    public function amazonSignAndEncrypt(){
    	
    	$config = Config::get('amazonpay.config_seamless');
    	$client = new PWAINBackendSDK($config);
    	$val = ($_GET);
    	
    	unset($val['sellerId']);
    	$response = $client->generateSignatureAndEncrypt($val);
    	return $response;
    }
    
    public function amazonSignAndEncryptForOperation(){
    	
    	$config = Config::get('amazonpay.config_seamless');
    	$client = new PWAINBackendSDK($config);
    	$val = ($_GET);
    	$val['operationName'] = 'SIGN_AND_ENCRYPT_GET_CHARGE_STATUS_REQUEST';
    	unset($val['sellerId']);
    	unset($val['awsAccessKeyId']);
    	
    	$response = $client->generateSignatureAndEncrypt($val);
    	return $response;
    	
    }
    
    public function verifyAmazonChecksumSignature($website = false){
    	
    	error_reporting(E_ERROR | E_PARSE);
    	$config = Config::get('amazonpay.config_seamless');
    	
    	$client = new PWAINBackendSDK($config);
    	
    	// Request can be either GET or POST
    	Log::info(Input::all());
    	
    	$val = (Input::all());
    	Log::info("verifyAmazonChecksum post data  verifyAmazonChecksumSignature ---------------------------------------------------------",$val);
    	unset($val['sellerId']);
    	$response = $client->verifySignature($val);
    	Log::info(" info response  ".print_r($response,true));
        if($val["status"] != "FAILURE"){
    	    $val['isSignatureValid'] = $response ? 'true' : 'false';
        }else{
            $val['isSignatureValid'] = 'false';
        }
    	
    	$val['order_id'] = null;
    	
    	// $val['isSignatureValid'] = 'true';
    	
    	if($val['isSignatureValid'] == 'true'&&!empty($val["verificationOperationName"])&&$val["verificationOperationName"]=='VERIFY_CHARGE_STATUS'&&isset($val["transactionValue"])){
    		
    		Log::info(" info AMAZON ".print_r("AMAZON SUCCESS.",true));
    		
    		$order = Order::where('txnid',$val['merchantTransactionId'])->first();
    		
    		if($order){
    			
    			$order->pg_type = "AMAZON";
    			$order->amazon_hash = $val["hash"] = getpayTMhash($order->toArray())['reverse_hash'];
    			$order->update();
    			
    			$val['order_id'] = $order->_id;
    			
    			$success_data = [
    					'txnid'=>$order['txnid'],
    					'amount'=>(int)$val["transactionValue"],
    					'status' => 'success',
                        'hash'=> $val["hash"],
                        'orderId'=>$order['_id'],
                        'email'=>$order['customer_email'],
                        
    			];
    			if($website == "1"){
    				$url = $this->getAmazonPaySuccessUrl($order, $success_data);
    				return Redirect::to($url);
    			}else{
    				Log::info(" info success data ".print_r($success_data,true));
    				$paymentSuccess = $this->fitweb->paymentSuccess($success_data);
    			}
    		}
    		
    		if(isset($paymentSuccess)&&isset($paymentSuccess['status']) && $paymentSuccess['status'] == 200){
    			$val['isSignatureValid'] = "true";
    		}else{
    			$val['isSignatureValid'] = "false";
    		}
    		Log::info(" info val".print_r(json_encode($val),true));
    		return Response::json($val);
    	}
    	Log::info(" info ".print_r("nothing passed",true));
    	return $val['isSignatureValid'];
    }

    public function verifyAmazonChecksum($website = false){
    	
    	
    	$config = Config::get('amazonpay.config');
    	
    	$client = new PWAINBackendSDKNon($config);
    	
    	// Request can be either GET or POST
    	Log::info(Input::all());
    	
    	$val = Input::all();
    	Log::info("verifyAmazonChecksum post data ---------------------------------------------------------",$val);
    	unset($val['sellerId']);
        if($val["status"] != "FAILURE"){
            $response = $client->verifySignature($val);
    	    $val['isSignatureValid'] = $response ? 'true' : 'false';
        }else{
            $val['isSignatureValid'] = 'false';
        }
    	
    	
    	$val['order_id'] = null;
    	
    	// $val['isSignatureValid'] = 'true';
    	
    	if($val['isSignatureValid'] == 'true'){
    		
    		
    		$order = Order::where(function($query) use($val)
    		{$query->orWhere('txnid', $val['sellerOrderId'])->orWhere('payment.txnid',$val['sellerOrderId']);
    		})->first();
    		
    		if($order){
    			if($order->type=='product')
    			{
    				$paymentDet=$order->payment;
    				$paymentDet['pg_type']="AMAZON";
    				$order->payment=$paymentDet;
    				$revereseHash=getReverseHashProduct($order->toArray());
    				
    				Log::info("revereseHash data ---------------------------------------------------------",$revereseHash);
    				if($revereseHash['status'])
    				{
    					$paymentDet1=$order->payment;
    					$paymentDet1['amazon_hash']=$val["hash"] = $revereseHash['data']['reverse_hash'];
    					$order->payment=$paymentDet1;
    				}
    				else {
    					$val['isSignatureValid'] = "false";
    					return Response::json($val);
    				}
    				
    			}
    			else
    			{
    				
    				$order->pg_type = "AMAZON";
    				$order->amazon_hash = $val["hash"] = getpayTMhash($order->toArray())['reverse_hash'];
    			}
    			
    			
    			$order->update();
    			
    			$val['order_id'] = $order->_id;
    			
    			$success_data = [
    					'txnid'=>$val['sellerOrderId'],
    					'amount'=>(int)$val["orderTotalAmount"],
    					'status' => 'success',
                        'hash'=> $val["hash"],
                        'orderId'=>$order['_id'],
                        'email'=>$order['customer_email']
    			];
    			if($website == "1"){
    				$url = $this->getAmazonPaySuccessUrl($order, $success_data);
    				return Redirect::to($url);
    			}else{
    				$paymentSuccess = $this->fitweb->paymentSuccess($success_data);
    			}
    		}
    		
    		/* $order->pg_type = "AMAZON";
    		 $order->amazon_hash = $val["hash"] = getpayTMhash($order->toArray())['reverse_hash'];
    		 $order->update();
    		 
    		 
    		 
    		 Log::info("success_data--------------------------------------------------------------------",$success_data);
    		 
    		 $ch = curl_init();
    		 
    		 curl_setopt($ch, CURLOPT_URL,Config::get('app.website')."/paymentsuccessandroid");
    		 curl_setopt($ch, CURLOPT_POST, 1);
    		 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    		 curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($success_data));
    		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		 curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		 'Content-Type: application/json',
    		 'Content-Length: ' . strlen(json_encode($success_data)))
    		 );
    		 $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    		 curl_close($ch);
    		 
    		 
    		 Log::info("httpcode--------------------------------------------------------------------".$httpcode);
    		 
    		 
    		 
    		 if($httpcode == 200){
    		 $val['isSignatureValid'] = 'true';
    		 }else{
    		 $val['isSignatureValid'] = 'false';
    		 }*/
    		
    		//$resp = curl_exec ($ch);
    		
    		//Log::info("Success api response--------------------------------------------------------------------");
    		//Log::info($resp);
    		
    		
    		if(isset($paymentSuccess['status']) && $paymentSuccess['status'] == 200){
    			$val['isSignatureValid'] = "true";
    		}else{
    			$val['isSignatureValid'] = "false";
    			
    		}
    	}else{
            if($website == "1"){
                $order = Order::where(function($query) use($val)
                    {$query->orWhere('txnid', $val['sellerOrderId'])->orWhere('payment.txnid',$val['sellerOrderId']);
                    })->first();
                $success_data = [
    					'txnid'=>$val['sellerOrderId'],
    					'amount'=>(int)$val["orderTotalAmount"],
    					'status' => 'failure',
    					'hash'=> ""
    			];
                $url = Config::get('app.website')."/paymentfailure?". http_build_query($success_data, '', '&');
                if($order['type'] == "booktrials" || $order['type'] == "workout-session"){
                    $url = Config::get('app.website')."/paymentfailuretrial?". http_build_query($success_data, '', '&');
                }
                Log::info(http_build_query($success_data, '', '&'));
                Log::info($url);
                return Redirect::to($url);
            }
        }
        
    	
    	return Response::json($val);
    }

    public function getServiceData(){

        $ratecard_count = 1;

        $service_id = 9096;

        $getRatecardCount = $this->fitapi->getServiceData($service_id);

        if($getRatecardCount['status'] != 200){

            $ratecard_count = 0;

        }else{

            if(!isset($getRatecardCount['ratecards'])){
                $ratecard_count = 0;
            }

            if(isset($getRatecardCount['ratecards']) && empty($getRatecardCount['ratecards'])){
                $ratecard_count = 0;
            }
        }

        return $ratecard_count;

    }

    public function getCaptureData($trial_id){

        $booktrial = Booktrial::find(intval($trial_id));

        
        // $order = Order::find($booktrial->order_id);
        // return $booktrial->order_id;
        return $this->capture(['order_id'=>$booktrial->order_id, 'wallet'=>false]);

        // $data = [];
        
        // $fields = ['customer_name','customer_email','customer_phone','gender','finder_id','finder_name','finder_address','premium_session','service_name','service_id','service_duration','schedule_date','schedule_slot','amount','city_id','type','note_to_trainer','going_together','ratecard_id','customer_identity','customer_source','customer_location','env','customer_id','logged_in_customer_id','device_type','reg_id','gcm_reg_id','ratecard_remarks','duration','duration_type','duration_day','amount_finder','offer_id','start_date','end_date','membership_duration_type','start_time','end_time','batch_time','vertical_type','secondary_payment_mode','service_name_purchase','service_duration_purchase','status','source_of_membership','finder_city','finder_location','finder_vcc_email','finder_vcc_mobile','finder_poc_for_customer_name','finder_poc_for_customer_no','show_location_flag','share_customer_no','finder_lon','finder_lat','finder_branch','finder_category_id','finder_slug','finder_location_id','city_name','city_slug','category_name','category_slug','finder_flags','meal_contents'];

        // foreach($fields as $field){
        //     if(isset($order[$field])){

        //         $data[$field] = $order[$field];
        //     }
        // }

        // $data['session_payment'] = true;
        // $data['order_id'] = $order->_id;
        // // $data['manual_order'] = true;


        // $query_params = [
        //     'app_version'=>$this->app_version,
        //     'device_type'=>$this->device_type
        // ];

        // $headers = [
        //     'Device-Type'=>$this->device_type,
        //     'App-Version'=>$this->app_version,
        //     'Authorization'=>Request::header('Authorization')
        // ];

        // $reponse = $this->fitapi->getCaptureData($data, $headers, $query_params);

        // if($reponse['status']!=200){
        //     return Response::json(['status'=>500, 'message'=>'Please try again later'],200);
        // }
        // return Response::json($reponse['data'],200);

    } 
    
    public function getPayLaterData($order){
        
        $data = [];
        
        $fields = ['customer_name','customer_email','customer_phone','gender','finder_id','finder_name','finder_address','premium_session','service_name','service_id','service_duration','schedule_date','schedule_slot','amount','city_id','type','note_to_trainer','going_together','ratecard_id','customer_identity','customer_source','customer_location','env','customer_id','logged_in_customer_id','device_type','reg_id','gcm_reg_id','ratecard_remarks','duration','duration_type','duration_day','amount_finder','offer_id','start_date','end_date','membership_duration_type','start_time','end_time','batch_time','vertical_type','secondary_payment_mode','service_name_purchase','service_duration_purchase','status','source_of_membership','finder_city','finder_location','finder_vcc_email','finder_vcc_mobile','finder_poc_for_customer_name','finder_poc_for_customer_no','show_location_flag','share_customer_no','finder_lon','finder_lat','finder_branch','finder_category_id','finder_slug','finder_location_id','city_name','city_slug','category_name','category_slug','finder_flags','meal_contents','amount_customer'];

        foreach($fields as $field){
            if(isset($order[$field])){

                $data[$field] = $order[$field];
            }
        }
        $data['amount'] = $data['amount_customer'];
        $data['session_payment'] = true;
        $data['paymentmode_selected'] = 'paymentgateway';
        $data['payment_mode'] =  'paymentgateway';
        $data['customer_source'] =  !empty($_GET['device_type']) ? $_GET['device_type'] : "website";
        // $data['wallet'] =  false;
        

        return $data;

    }
    
    function getPaymentModesProduct($data){
    	
    	$payment_modes = [];
    	$payment_options = [];
    	$payment_options['wallet'] = [
    			'title' => 'Wallet',
    			'subtitle' => 'Transact online with Wallets',
    			'value'=>'wallet',
    			'options'=>[
    					[
    							'title' => 'Paytm',
    							'subtitle' => 'Paytm',
    							'value' => 'paytm'
    					],
    					[
    							'title' => 'AmazonPay',
    							'subtitle' => 'AmazonPay',
    							'value' => 'amazonpay'
    					],
    					// [
    					// 		'title' => 'Mobikwik',
    					// 		'subtitle' => 'Mobikwik',
    					// 		'value' => 'mobikwik'
    					// ],
    					[
    							'title' => 'PayU',
    							'subtitle' => 'PayU',
    							'value' => 'payu'
    					]
    			]
    	];
    	
    	if(!empty($data['emi']) && $data['emi']){
    		$payment_options['emi'] = array(
    				'title' => 'EMI',
    				'subtitle' => 'Transact online with credit installments',
    				'value' => 'emi',
    		);
    	}
    	
    	/* if($data['pay_later']){
    		
    		$payment_modes[] = array(
    				'title' => 'Pay now',
    				'subtitle' => 'Pay 20% less',
    				'value' => 'paymentgateway',
    				'payment_options'=>$payment_options
    		);
    		
    	}else{
    		$payment_modes[] = array(
    				'title' => 'Online Payment',
    				'subtitle' => 'Transact online with netbanking, card and wallet',
    				'value' => 'paymentgateway',
    				'payment_options'=>$payment_options
    		);
    	} */
    	
        
        array_push($payment_modes, ['title' => 'Online Payment','subtitle' => 'Transact online with netbanking, card and wallet','value' => 'paymentgateway','payment_options'=>$payment_options]);
        //array_push($payment_modes, ['title' => 'Cash Pickup','subtitle' => 'Schedule cash payment pick up','value' => 'cod']);

    	$emi = $this->utilities->displayEmi(array('amount'=>$data['data']['amount']));    		
    	if(!empty($data['emi']) && $data['emi'])
    	   array_push($payment_modes, ['title' => 'EMI','subtitle' => 'Transact online with credit installments','value' => 'emi']);
    	
    	   
        if($this->vendor_token)
            array_push($payment_modes, ['title' => 'Pay at Studio','subtitle' => 'Transact via paying cash at the Center','value' => 'pay_at_vendor']);
	
    	return $payment_modes;
    }

    public function checkProductCaptureFailureStatus($data){
    	
    	try {
    		$check=["status"=>1,"message"=>""];
    		if(!empty($data['customer_source']))
    		{
    			if($data['customer_source']=='kiosk'&&empty($data['cart_data']))
    				$check=["status"=>0,"message"=>"card_data key can't be empty."];
    				else if($data['customer_source']!='kiosk')
    					$check=["status"=>0,"message"=>"Products currently launched only for kiosk."];
    			
    		}
    		if(!empty($data['finder_id']))
    		{	
    			$checkFinderState = $this->utilities->checkFinderState($data['finder_id']);
    			if($checkFinderState['status'] != 200)
    				$check=["status"=>0,"message"=>$checkFinderState['message']];
    		}

    		return $check;
    	} catch (Exception $e) 
    	{
    		return ['status'=>0,"message"=>$this->utilities->baseFailureStatusMessage($e)];
    	}
    	
    }
  
    public function getPaymentDetailsProduct($data){
    	
    	try {
    		$response=["status"=>1,"message"=>"success"];
    		$you_save = 0;
    		$amount_summary= [['field' => 'Cart Amount','value' => $this->utilities->getRupeeForm((isset($data['amount_calculated']['cart_amount']) ? $data['amount_calculated']['cart_amount']: $data['amount_calculated']['cart_amount']))]];
    		if(empty($data['deliver_to_vendor']))array_push($amount_summary,['field' => 'Delivery charges','value' =>$this->utilities->getRupeeForm(intval(Config::get('app.product_delivery_charges')))]);

    		$amount_payable = ['field' => 'Total Amount Payable', 'value' => $this->utilities->getRupeeForm($data['amount_calculated']['final'])];
    		
    		
    		// 	******************************************************************************	CONVINIENCE FEE  ******************************************************************************
    		
    		/* 	if(isset($data['convinience_fee']) && $data['convinience_fee'] > 0){
    		
    		$amount_summary[] = array(
    		'field' => 'Convenience Fee',
    		'value' => '+Rs. '.$data['convinience_fee']
    		);
    		} */
    		
    		// 	******************************************************************************	CASHBACK DETAIL /  WALLET  APPLIED ******************************************************************************
    		
    		/* 	if(isset($data['cashback_detail']) && isset($data['cashback_detail']['amount_deducted_from_wallet']) && $data['cashback_detail']['amount_deducted_from_wallet'] > 0 &&  $payment_mode_type != 'pay_later'){
    		
    		$amount_summary[] = array(
    		'field' => 'Fitcash Applied',
    		'value' => '-Rs. '.$data['cashback_detail']['amount_deducted_from_wallet']
    		);
    		$you_save += $data['cashback_detail']['amount_deducted_from_wallet'];
    		
    		} */
    		
    		// 	******************************************************************************	COUPON DISCOUNT  ******************************************************************************
    		if(!empty($data['amount_calculated']['coupon_discount_amount'])){
    		
    		$amount_summary[] = array(
    		'field' => 'Coupon Discount',
    		'value' => '-'.$this->utilities->getRupeeForm($data['amount_calculated']['coupon_discount_amount'])
    		);
    		$you_save += $data['amount_calculated']['coupon_discount_amount'];
    		
    		}
    		
    		// 	******************************************************************************	APP DISCOUNT  ******************************************************************************
    		
    		/* if(isset($data['app_discount_amount']) && $data['app_discount_amount'] > 0){
    		 $amount_summary[] = array(
    		 'field' => 'App Discount',
    		 'value' => '-Rs. '.$data['app_discount_amount']
    		 );
    		 $you_save += $data['app_discount_amount'];
    		 } */
    		
    		$payment_details  = ["amount_summary"=>$amount_summary,"amount_payable"=>$amount_payable];
    		
    		if($you_save > 0)
    			$payment_details['savings'] = ['field' => 'Your total savings',	'value' => $this->utilities->getRupeeForm($you_save)];
    			
    			$response['details']=$payment_details;
    			return $response;
    			
    	} catch (Exception $e) {
    		return ['status'=>0,"message"=>$this->utilities->baseFailureStatusMessage($e)];
    	}
    }

    public function getNearBySessions($order){
        $nearby_same_category_request = [
            "offset" => 0,
            "limit" => 2,
            "radius" => "3km",
            "category"=>'',
            "lat"=>$order['finder']["finder_lat"],
            "lon"=>$order['finder']["finder_lon"],
            "city"=>strtolower($order["finder"]["city_name"]),
            "keys"=>[
              "slug",
              "name",
              "id",
              'address',
              'coverimage',
              'location'
            ],
            "not"=>[
                "vendor"=>[(int)$order['finder']["finder_id"]]
            ],
        ];

        $nearby_same_category = geoLocationFinder($nearby_same_category_request);
        
        foreach($nearby_same_category as &$finder){
            // return $finder;
            if(strlen($finder['title']) > 16){
                $finder['title'] = substr($finder['title'], 0, 16).' ...';
            }
            $finder['address'] = $this->utilities->formatShippingAddress($finder['address']);
            if(strlen($finder['address']) > 30){
                $finder['address'] = substr($finder['address'], 0, 30).' ...';
            }
        }

        return $nearby_same_category;
    }

    public function sendVendorOTPProducts($order_id){

        $order = Order::where('_id',intval($order_id))->first();
        // return $order;
        if(!isset($order['otp_data'])){

            $data_otp = array_merge($order['finder'], $order['customer']);
    
            $data_otp = array_only($data_otp,['finder_id','order_id','service_id','ratecard_id','payment_mode','finder_vcc_mobile','finder_vcc_email','customer_name','service_name','service_duration','finder_name', 'customer_source','amount_finder','amount','finder_location','customer_email','customer_phone','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon']);
                                            
            $data_otp['action'] = "vendor_otp";
            
            $addTemp = addTemp($data_otp);

            $otp_data = [
                'finder_vcc_mobile'=>$data_otp['finder_vcc_mobile'],
                'finder_vcc_email'=>$data_otp['finder_vcc_email'],
                'payment_mode'=>'at the studio',
                'temp_id'=>$addTemp['_id'],
                'otp'=>$addTemp['otp'],
                'created_at'=>time(),
                'customer_name'=>$data_otp['customer_name'],
                'finder_name'=>$data_otp['finder_name'],
                'order_id'=>$order['_id']
            ];
            $payment = $order->payment;
            $payment['payment_mode'] = 'at the studio';
            $order->otp_data = $otp_data;
            $order->payment = $payment;
            $order->save();
            

        }else{
            $otp_data = $order->otp_data;
        }


        $this->findersms->genericOtp($otp_data);
        $this->findermailer->genericOtp($otp_data);
        

        $response = [
            'orderid'=>$order['_id'],
            'resend_otp_url'=>Config::get('app.url')."/temp/regenerateotp/".$otp_data['temp_id'],
            'vendor_otp_message'=>'Enter the confirmation code provided by your gym/studio to place your order'
        ];

        return $response;

    }

    public function updateRatecardSlots($job, $data){
        
        if($job){
            $job->delete();
        }
        if(empty($data['order_id'])){
            return "NoOrderId";
        }
        // if(intval(date('d', time())) >= 25){
        //     return;
        // }
        if(!empty($data['delay'])){
            $order = Order::find(intval($data['order_id']));
            if(!isset($order->ratecard_sidekiq_id)){
                $queue_id = $this->utilities->hitURLAfterDelay(Config::get('app.url').'/updateratecardslotsbyid/'.$data['order_id'], $data['delay']);
                $order->ratecard_sidekiq_id = $queue_id;
                $order->save();
            }
        }else{
            $this->utilities->updateRatecardSlots($data);
        }
        
    }

    public function updateRatecardSlotsByOrderId($order_id){
        Log::info('updateRatecardSlotsByOrderId');
        $this->utilities->updateRatecardSlots(['order_id'=>$order_id]);
    }

    public function giftCouponCapture(){
        
        $data = Input::json()->all();

        Log::info("giftCouponCapture capture");

        Log::info($data);

        $rules = array(
            'customer_source'=>'required',
            'coupon_id'=>'required',
            'customer_email'=>'required|email',
            'customer_name'=>'required',
            'customer_phone'=>'required',
            'receiver_address'=>'required',
            'receiver_email'=>'required',
            'receiver_phone'=>'required',
            'receiver_name'=>'required',
            'type'=>'required',
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }

        if($data['type'] != 'giftcoupon'){
            return Response::json(array('message'=>'Invalid parameters'), 400);
        }
        
        $customerDetail = $this->getCustomerDetail($data);
        
        if($customerDetail['status'] != 200){
            return Response::json($customerDetail,$customerDetail['status']);
        }

        $data = array_merge($data,$customerDetail['data']);

        Log::info("before pledge");

        Log::info($data);

        $coupon = GiftCoupon::active()->find($data['coupon_id']);

        if(!$coupon){
            return Response::json($customerDetail,$customerDetail['status']);
        }

        $data['amount_finder'] = $data['amount'] = $coupon->cost;
        
        $data['fitcash_coupon_amount'] = $coupon->fitcash;
        
        $data['coupon_name'] = $coupon->package_name;

        $data['payment_mode'] = 'paymentgateway';
        
        $data['status'] = "0";
        
        $order_id = $data['_id'] = $data['order_id'] = Order::maxId() + 1;

        $txnid = "";
        $successurl = "";
        $mobilehash = "";
        if($data['customer_source'] == "android" || $data['customer_source'] == "ios"){
            $txnid = "MFIT".$data['_id'];
            $successurl = $data['customer_source'] == "android" ? Config::get('app.website')."/paymentsuccessandroid" : Config::get('app.website')."/paymentsuccessios";
        }else{
            $txnid = "FIT".$data['_id'];
            $successurl = Config::get('app.website')."/paymentsuccess";
        }
        $data['txnid'] = $txnid;
        $data['finder_name'] = 'Fitternity';
        $data['finder_slug'] = 'fitternity';
        
        $data['service_name'] = 'Fitternity Gift Coupons';
        $data['service_id'] = 100000;
        
        $hash = getHash($data);
        $data = array_merge($data,$hash);
        
        $order = new Order($data);

        $order->_id = $order_id;
        
        $order->save();
        
        $result['firstname'] = strtolower($data['customer_name']);
        $result['lastname'] = "";
        $result['phone'] = $data['customer_phone'];
        $result['email'] = strtolower($data['customer_email']);
        $result['orderid'] = $data['_id'];
        $result['txnid'] = $txnid;
        $result['amount'] = $data['amount'];
        $result['productinfo'] = strtolower($data['productinfo']);
        $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($data['service_name']));
        $result['successurl'] = $successurl;
        $result['hash'] = $data['payment_hash'];
        $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
        $result['finder_name'] = strtolower($data['finder_name']);
        
        
        $resp   =   array(
            'status' => 200,
            'data' => $result,
            'message' => "Tmp Order Generated Sucessfully"
        );
        return Response::json($resp);

    }

    public function giftCouponSuccess(){

        $data = Input::json()->all();

        Log::info("giftCouponSuccess success");
        
        Log::info($data);
        
        $rules = array(
            'order_id'=>'required'
        );

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),404);
        }
        
        $order_id   =   (int) $data['order_id'];
        $order      =   Order::findOrFail($order_id);

        if(isset($order->status) && $order->status == '1'){

            $resp   =   array('status' => 401, 'statustxt' => 'error', "message" => "Already Status Successfull");
            return Response::json($resp,401);

        }
        // $hash_verified = true;
        $hash_verified = $this->utilities->verifyOrder($data,$order);

        if($data['status'] == 'success' && $hash_verified){

            $order->status = "1";
            $order->success_date = date('Y-m-d H:i:s',time());

            $fitcash_coupon= $this->utilities->createGiftFitcashCoupon($order->toArray());

            
            
            $order->fitcash_coupon_id = $fitcash_coupon['_id'];
            $order->fitcash_coupon_code = $fitcash_coupon['code'];
            
            // $redisid = Queue::connection('redis')->push('TransactionController@sendCommunication', array('order_id'=>$order_id),Config::get('app.queue'));

            // $order->redis_id = $redisid;

            // $order->website = "www.fitternity.com";

            // return $order;

            $order->update();

            $this->customersms->giftCoupon($order->toArray());

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
            
        } else {
           
            if($hash_verified == false){
             
                $order->hash_verified = false;
                $order->update();
                
            }
           
            $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'message' => "Transaction Failed :)");
            
        }
        
        return Response::json($resp);
            
    }

    public function getThirdPartyOrderDetails($txnid) {
        Log::info('inside getThirdPartyOrderDetails: $txnid: ', [$txnid]);
        $orderData = [];
        $tpoRec = ThirdPartyOrder::where('txnid', $txnid)->first();
        Log::info('$tpoRec: ', [$tpoRec]);
        if(!empty($tpoRec)) {
            Log::info('Third party order record found for txnid: ', [$txnid]);
            $principalMember = array_filter($tpoRec['member_details'], function($member) {
                return $member['role']=='principal';
            });
            if(!empty($principalMember)){
                $orderData['_id'] = $tpoRec['_id'];
                $orderData['third_party_order'] = true;
                $orderData['type'] = 'thirdparty';
                $orderData['logged_in_customer_id'] = $tpoRec['customer_id'];
                $orderData['txnid'] = $tpoRec['txnid'];
                $orderData['amount'] = $tpoRec['fee_details']['total_price'];
                $orderData['firstname'] = $principalMember[0]['first_name'];
                $orderData['email'] = $principalMember[0]['email_id'];
                $orderData['phone'] = $principalMember[0]['mobile_no'];
                $orderData['orderid'] = $tpoRec['_id'];
                $orderData['service_name'] = $tpoRec['plan_code'];
                $orderData['finder_name'] = $tpoRec['thirdparty']['acronym'];
                $orderData['productinfo'] = $orderData['service_name'].' - '.$orderData['finder_name'];
                $orderData['env'] = $tpoRec['env'];

                $orderData['customer_name'] = (isset($tpoRec['customer_name']))?($tpoRec['customer_name']):($principalMember[0]['first_name'].' '.$principalMember[0]['last_name']);
                $orderData['customer_email'] = (isset($tpoRec['customer_email']))?($tpoRec['customer_email']):($principalMember[0]['email_id']);
                $orderData['gender'] = $principalMember[0]['gender']=='M'?'male':'female';
                $orderData['customer_phone'] = (isset($tpoRec['customer_phone']))?($tpoRec['customer_phone']):($principalMember[0]['mobile_no']);
                $orderData['dob'] = $principalMember[0]['dob']; // date('Y-m-d', $principalMember[0]['dob']->sec).' 00:00:00';
                $orderData['customer_address'] = [$principalMember[0]['address_line_1'], $principalMember[0]['address_line_2']];

                if(empty($orderData['logged_in_customer_id'])){
                    Log::info('registering customer');
                    $orderData['logged_in_customer_id'] = autoRegisterCustomer($orderData);
                    $tpoRec['customer_id'] = $orderData['logged_in_customer_id'];
                    Log::info('customer registered: ', [$orderData['logged_in_customer_id']]);
                    $tpoRec->save();
                }

                Log::info('$orderData before getHash(): ', $orderData);
                $orderData["with_hash_params"] = "checkout";
                $orderData = getHash($orderData);
                $orderData['hash'] = $orderData['payment_hash'];
                Log::info('$orderData after getHash(): ', $orderData);
                return ($orderData);
            } else {
                Log::info('principal member not found');
                // principal member not found
                return null;
            }
        } else {
            Log::info('order not found based on txn id');
            // order not found based on txn id
            return null;
        }
    }

    public function webcheckout(){
        $data = Input::json()->all();
        $rules = array(
            'txnId'=>'required',
        );
        $orderWithHash = null;
        $isThirdParty = substr($data["txnId"], 0, 2)==="TP";
        if(!$isThirdParty){
            $validator = Validator::make($data,$rules);
            $jwt_token = Request::header('Authorization');
            if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
                $decoded = customerTokenDecode($jwt_token);
                // $data['logged_in_customer_id'] = (int)$decoded->customer->_id;
            }
        }
        if(!!$data["txnId"] && $isThirdParty){
            $orderWithHash = $this -> getThirdPartyOrderDetails($data["txnId"]);
        }
        else {
            $order = Order::where("txnid",$data["txnId"])->first();
            $order["with_hash_params"] = "checkout";
            $orderWithHash = getHash($order);
        }
        return $orderWithHash;
    }

    public function getUmarkedMfpAttendance(){

        $data = Input::json()->all();
        
        $dcd=$this->utilities->decryptQr($data['code'], Config::get('app.core_key'));
        Log::info($dcd);
        $data=json_decode(preg_replace('/[\x00-\x1F\x7F]/', '', $dcd),true);
        $order_id = $data['order_id'];
        $order = Order::where('_id', $order_id)->first();

        $attendance = !empty($order['attendance']) ? $order['attendance'] : 0;
        $ticket_quantity = !empty($order['ticket_quantity']) ? $order['ticket_quantity'] : 1;
        $attendance_count = $attendance;
        if($attendance_count >= $ticket_quantity){
            return ['status'=>400, 'message'=>'Attendance marked for all customers', 'customers'=>$attendance];
        }

        return ['order_id'=>$order['_id'], 'count'=>$ticket_quantity-$attendance_count];

    }

    public function markMfpAttendance(){
        
        $data = Input::json()->all();

        $order_id = $data['order_id'];
        $attendance = $data['attendance'];
        // $attendance_data = [];
        $order = Order::find($order_id);
        $ticket_quantity = !empty($order['ticket_quantity']) ? $order['ticket_quantity'] : 1;
        $order_attendance = !empty($order['attendance']) ? $order['attendance'] : 0;

        if($order_attendance >= $ticket_quantity){
            return ['status'=>400, 'message'=>'Attendance already marked for all customers'];
        }
        
        $order_attendance = $attendance+$order_attendance;

        $order->update(['attendance'=>$order_attendance]);

        return ['status'=>200, 'message'=>'Attendance Marked'];
            
    }

    public function setUpgradedOrderRedundant($order, $order_ids){
        Order::whereIn('_id', $order_ids)->update(['status'=>'0', 'upgraded_order'=>$order['_id']]);
    }

    public function generateFreeSP(){

        $data = $this->getAllPostData();

        Log::info("generateFreeSP");
        Log::info($data);

        if(!empty($data['order_token'])){
            
            $decodedOrderToken = decodeOrderToken($data['order_token']);
            $data['order_id'] = $decodedOrderToken['data']['order_id'];
            $data['customer_id'] = $decodedOrderToken['data']['customer_id'];
            
        }

        $requestValidation = $this->utilities->validateInput('generateFreeSP', $data);

        if(!(!empty($requestValidation['status']) && $requestValidation['status'] == 200)){
            if(!empty($requestValidation['message'])){
                return ['status'=>400, 'message'=>$requestValidation['message']];
            }else{
                return ['status'=>400, 'message'=>'Please try after sometime(1)'];
            }
        }


        $capture_data = $this->getFreeSPData($data);

        if(empty($capture_data['status']) || $capture_data['status'] != 200){
            if(!empty($capture_data['message'])){
                return ['status'=>400, 'message'=>$capture_data['message']];
            }else{
                return ['status'=>400, 'message'=>'Please try after sometime(2)'];
            }
        }

        $capture_response = $this->capture($capture_data['data']);
        
        if(empty($capture_response['status']) || $capture_response['status'] != 200 || empty($capture_response['data']['orderid']) || empty($capture_response['data']['email'])){
            return ['status'=>500, 'message'=>'Please try again later'];
        }
        
        $success_data = [
            
            'order_id'=>$capture_response['data']['orderid'],
            'status'=>'success',
            'customer_email'=>$capture_response['data']['email'],
            'amount'=>0
        
        ];
        
        return $this->successCommon($success_data);

    }

    /**
     * @return mixed
     */
    public function getAllPostData()
    {
        return Input::json()->all();
    }

    public function getFreeSPData($data){
        
        $customer_id = null;

        if(!empty($data['order_id'])){
            $data['order_id'] = intval($data['order_id']);
        }

        if(!empty($data['order_token'])){
        
            $customer_id = $data['customer_id'];
        
        }else{

            $logged_in_customer = $this->utilities->getCustomerFromToken();
            
            if(empty($logged_in_customer) || empty($logged_in_customer['_id'])){
                return ['status'=>400, 'message'=>'Please log in'];
            }
            
            $customer_id = $logged_in_customer['_id'];
        }

        Log::info("getFreeSPData");
        Log::info($data);
        
        Order::$withoutAppends = true;

        $order = Order::active()
        ->where('customer_id', $customer_id)
        ->whereNotIn('free_sp_ratecard_id', [null])
        ->where('free_sp_order_id', 'exists', false)
        ->find($data['order_id']);

        if(empty($order)){
            return ['status'=>400, 'message'=>'The Complementary Session Pack has already been claimed'];
        }

        if(strtolower($order['customer_email']) == strtolower($data['customer_email'])){
            return ['status'=>400, 'message'=>'Please enter a different email id to claim the Complementary Session Pack'];
        }

        $capture_data = array_only($data, ["customer_email","customer_name","customer_phone","preferred_starting_date"]);
        
        $capture_data = array_merge($capture_data, array_only($order->toArray(), ["customer_source","finder_id","gender","service_id","type","env"]));

        $capture_data['ratecard_id'] = $order['free_sp_ratecard_id'];
        
        $capture_data['customer_identity'] = 'email';
        
        $capture_data['parent_order_id'] = $data['order_id'];

        return ['status'=>200, 'data'=>$capture_data];
    }

    public function scheduleStudioBookings($job, $data) {
        Log::info('Entered scheduleStudioBookings in SchedulebooktrialsController.....');
        if($job){
            $job->delete();
        }
        $this->utilities->scheduleStudioBookings($data['order_id'], $data['isPaid']);
    }

    public function generatePaytmUrl(){
        $input = Input::All();
        Log::info('input data at generatepaytmurl:::::>>>>>>>>>>>>',[$input]);
        $transactionURL ="https://securegw.paytm.in/merchant-status/getTxnStatus?";

        $params = array(
            "ORDER_ID" => '',
            "MID" => 'fitter45826906213917',
            "CUST_ID" => '',
            "MOBILE_NO" => '',
            "EMAIL" => '',
            "CHANNEL_ID" => 'WEB',
            "TXN_AMOUNT" => '',
            "WEBSITE" => 'fitternitywap',
            "INDUSTRY_TYPE_ID" => 'Retail',
            "CALLBACK_URL" => 'https://fitn.in/verifychecksum',
        );

        if(Config::get('app.paytm_sandbox')){
            $params = Config::get('paytm');//$this->paytmconfig;
            $transactionURL ="https://securegw-stage.paytm.in/theia/processTransaction?";
        }
        $rules = array(
            'txn_id'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required',
            'customer_id'=>'required',
            "amount" => 'required'
        );

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),$this->error_status);
        }
        $params['ORDER_ID'] = $input['txn_id'];
        $params['CUST_ID'] = $input['customer_id'];
        $params['MOBILE_NO'] = $input['customer_phone'];
        $params['TXN_AMOUNT'] = $input['amount'];
        $params['EMAIL'] = $input['customer_email'];
        $params['CHECKSUMHASH'] = $this->PaytmService->createChecksum($params);
        Log::info('parameters before generating final url:::>>>>>>>>>.', [$params]);
        foreach($params as $key => $value){
            Log::info([$transactionURL, $key, $value]);
            $transactionURL=$transactionURL.$key."=".rawurlencode($value).'&';
        }
        Log::info('uisuklsdvdf::::::::::::', [$transactionURL, strlen($transactionURL)]);
        $transactionURL = substr($transactionURL,0,(strlen($transactionURL)-1));
        return ($transactionURL);
    }

    function afterTransQueued($job, $data){

        if($job){
            $job->delete();
        }
        
        $data = $data['data'];
        
        $type = $data['type'];
        // Log::info($data);
        $campaign_reg = CampaignReg::where('customer_email', $data['customer_email'])->where('customer_phone', $data['customer_phone'])->where('message.transaction', true)->where('claimed', '!=', true)->orderBy('_id', 'desc')->first();

        Log::info("campaign_regcampaign_regcampaign_regcampaign_regcampaign_regcampaign_regcampaign_regcampaign_regcampaign_regcampaign_regcampaign_regcampaign_regcampaign_reg");
        
        Log::info($campaign_reg);

        if($campaign_reg){
            if($data['amount'] >= 500){
                $claim = true;
            }else{
                $orders_amount = Order::active()->where('customer_id', $data['customer_id'])->where('success_date', '>=', new MongoDate(strtotime($campaign_reg['created_at'])))->sum('amount');
                if($type == 'order'){
                    $orders_amount = $orders_amount->where('_id', '!=', $data['_id']);
                }else{
                    $orders_amount = $orders_amount->where('booktrial_id', '!=', $data['_id']);
                }
                $orders_amount = $orders_amount->sum('amount');
                
                if($orders_amount + $data['amount'] >= 500){
                    $claim = true;
                }
            }
        }

        if(!empty($claim)){

            $campaign_reg->trans_id = $data['_id'];
            $campaign_reg->type = $type;
            $campaign_reg->customer_name = $data['customer_name'];

            $this->customersms->spinWheelAfterTransaction($campaign_reg->toArray());
            $campaign_reg->claimed = true;
            $campaign_reg->save();
        }

        if((isset($data['service_flags']['bulk_purchase_b2c_pps']) && $data['service_flags']['bulk_purchase_b2c_pps']['status'] == true) || (isset($data['service_flags']['bulk_purchase_b2b_pps']) && $data['service_flags']['bulk_purchase_b2b_pps']['status'] == true) || (isset($data['ratecard_flags']['bulk_purchase_membership']) && $data['ratecard_flags']['bulk_purchase_membership']['status'] == true)){
            Log::info("bulk_purchase");
            $this->updateBulkPurchase($data);
        }

        if(!empty($data['type']) && $data['type'] == 'workout-session'){
            Log::info("updatePpsRepeat");
            $this->updatePpsRepeat($data);
        }

        if(!empty($data['plus'])){
            $this->plusService->createPlusRewards($data);
        }
        
        $this->utilities->fitnessForce(['data'=>$data, 'type'=>$type]);

    }

    public function updateBulkPurchase($data){
        Log::info("updateBulkPurchase");

        $type = $data['type'];

        if($type == 'memberships' && isset($data['ratecard_flags']['bulk_purchase_membership']) && $data['ratecard_flags']['bulk_purchase_membership']['status'] == true){

            $order_id = $data['order_id'];
            $ratecard_id = $data['ratecard_id'];
            
            $ratecard = array();
            Ratecard::$withoutAppends = true;
            $ratecard = Ratecard::where('_id', $ratecard_id)->first();
            $ratecard_flag = $ratecard['flags'];

            $used = $ratecard_flag['bulk_purchase_membership']['used'] + 1;
            $price = $ratecard_flag['bulk_purchase_membership']['price'];

            $ratecard_flag['bulk_purchase_membership']['used'] = $used;
            $ratecard['flags'] = $ratecard_flag;
            $ratecard->update();

            $ratecard_api = array();
            $ratecard_api = RatecardAPI::where('_id', $ratecard_id)->first();
            $ratecard_api_flag = $ratecard_api['flags'];
            $ratecard_api_flag['bulk_purchase_membership']['used'] = $used;
            $ratecard_api['flags'] = $ratecard_api_flag;
            $ratecard_api->update();

            $data = array();
            $data['bulk_purchase'] = array(
                'type' => 'bulk_purchase_membership', 
                'used' => $used,
                'price' => $price,
            );

            Order::where('_id',(int)$order_id)->update($data);

        }

        if($type == 'workout-session'){

            $order_id = $data['order_id'];
            $service_id = $data['service_id'];
            $booktrial_id = $data['_id'];

            if((isset($data['third_party_details.abg']) && $data['third_party_details.abg'] != "") || (isset($data['corporate_coupon']) && $data['corporate_coupon'] == true)){

                $service = array();
                Service::$withoutAppends = true;
                $service = Service::where('_id', $service_id)->first();
                $service_flag = $service['flags'];

                $used = $service_flag['bulk_purchase_b2b_pps']['used'] + 1;
                $price = $service_flag['bulk_purchase_b2b_pps']['price'];
                $commission = $service_flag['bulk_purchase_b2b_pps']['commission'];
                $quantity = $service_flag['bulk_purchase_b2b_pps']['quantity'];
                
                if($used <= $quantity){
                    $service_flag['bulk_purchase_b2b_pps']['used'] = $used;
                    $service['flags'] = $service_flag;
                    $service->update();

                    $v_service = array();
                    $v_service = Vendorservice::where('_id', $service_id)->first();
                    $v_service_flag = $v_service['flags'];
                    $v_service_flag['bulk_purchase_b2b_pps']['used'] = $used;
                    $v_service['flags'] = $v_service_flag;
                    $v_service->update();

                    $data = array();
                    $data['bulk_purchase'] = array(
                        'type' => 'bulk_purchase_b2b_pps', 
                        'used' => $used,
                        'price' => $price,
                        'commission' => $commission,
                        'quantity' => $quantity,
                    );
                    Order::where('_id',(int)$order_id)->update($data);
                    Booktrial::where('_id',(int)$booktrial_id)->update($data);
                }
                
            }else if(isset($service_flag['bulk_purchase_b2c_pps']['commission'])){

                $service = array();
                Service::$withoutAppends = true;
                $service = Service::where('_id', $service_id)->first();
                $service_flag = $service['flags'];

                $used = $service_flag['bulk_purchase_b2c_pps']['used'] + 1;
                $price = $service_flag['bulk_purchase_b2c_pps']['price'];
                $commission = $service_flag['bulk_purchase_b2c_pps']['commission'];
                $quantity = $service_flag['bulk_purchase_b2c_pps']['quantity'];
                
                if($used <= $quantity){
                    $service_flag['bulk_purchase_b2c_pps']['used'] = $used;
                    $service['flags'] = $service_flag;
                    $service->update();

                    $v_service = array();
                    $v_service = Vendorservice::where('_id', $service_id)->first();
                    $v_service_flag = $v_service['flags'];
                    $v_service_flag['bulk_purchase_b2c_pps']['used'] = $used;
                    $v_service['flags'] = $v_service_flag;
                    $v_service->update();

                    $data = array();
                    $data['bulk_purchase'] = array(
                        'type' => 'bulk_purchase_b2c_pps', 
                        'used' => $used,
                        'price' => $price,
                        'commission' => $commission,
                        'quantity' => $quantity,
                    );
                    Order::where('_id',(int)$order_id)->update($data);
                    Booktrial::where('_id',(int)$booktrial_id)->update($data);
                }

            }
        }


    }

    public function updatePpsRepeat($data){
        
        Order::$withoutAppends=true;
        $count = Order::where('customer_phone', $data['customer_phone'])
				->where('coupon_code', '!=', 'FIRSTPPSFREE')
				->where('type', 'workout-session')
				->where('created_at', '>', new DateTime( date("d-m-Y 00:00:00", strtotime( '20-04-2018' )) ))
				->where('status', '1')
				->where('created_at', '<', new DateTime( date("d-m-Y H:i:s", strtotime( $data['created_at'] )) ))
				->where('_id', '!=', $data['order_id'])
                ->count();
                        
        if($count > 0){
            Order::where('_id',(int)$data['order_id'])->update(array('pps_repeat'=> true));
        }
    }

    public function createSessionPack($data){
        
        $rules = array(
            'order_id'=>'required|integer',
            'booktrial_id'=>'required|integer'
        );

        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),$this->error_status);
        }
        
        Order::$withoutAppends = true;
        $order = Order::where('_id', $data['order_id']);
        
        $capture_data = array_only($order->toArray(), $this->utilities->getSuccessCommonInputFields());

        $capture_data['studio_extended_cancelled']['booktrial_id'] = $data['booktrial_id'];
        $capture_data['studio_extended_cancelled']['order_id'] = $data['order_id'];

        $capture_response = $this->capture($capture_data);

        if(getArrayValue($capture_response, 'status') != 200){
            return ['status'=>400, 'message'=>'Please contact customer support (110001)'];
        }
        
        $success_data['order_id'] = $capture_response['order_id'];

        $success_response = $this->successCommon($success_data);

        return $success_response;

    }

    public function getSuccessData($order){
        
        $success_data = ['order_id'=> $order['_id'], 'status' => 'success'];
        
        $data_keys = ['customer_name','customer_email','customer_phone','finder_id','service_name','amount','type'];

        foreach($data_keys as $key){
            $success_data[$key] = $order[$key];
        }

        $this->successCommon($success_data);
    }

    public function classPassCapture(){
        $data = Input::All();
        $rules = array(
            'amount'=>'required',
            "pass_type"=> "required"
        );
        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),$this->error_status);
        }
        return $this->getRazorpayPlans($data);
        
    }

    public function getRazorpayPlans($data){
        $amount = (int)$data['amount'];
        $razorPayPlans = RazorpayPlans::Active()
        ->where('amount',$amount)
        ->select('plan_id')
        ->first();
        $plans = $razorPayPlans;
        Log::info("plans:::::::::::", [$plans, $amount, $data['pass_type']]);
        if(empty($plans)){
            $plans = $this->utilities->createRazorpayPlans($amount, $data['pass_type']);
        } 
        Log::info("plans dattata::::::::::", [$plans]);

        return $plans;
    }

    public function getPassDetails($data){
        Log::info("Pass data :::::", [$data]);
        $passBookingDetails = array();
        $totalPassBookings = 0;
        Booktrial::$withoutAppends = true;
        // Order::$withoutAppends = true;
        if(!empty($data['pass_order_id'])){
            $totalPassBookings = Booktrial::where('pass_order_id', $data['pass_order_id'])->where('customer_id', $data['customer_id'])->where('going_status_txt', '!=', 'cancel')->count();
        }
            
        // $count_det = ['1' => '1st', '2' => '2nd', '3' => '3rd'];
        // $totalPassBookings += 1;
        // if($totalPassBookings > 3){
        //     $totalPassBookingsStr = $totalPassBookings."th"; 
        // }else{
        //     $totalPassBookingsStr = $count_det[$totalPassBookings];
        // }
            
        $ordinalBookingCount = $this->utilities->getOrdinalNumber($totalPassBookings + 1);

        if(!empty($data['pass_branding']) && $data['pass_type'] == true){
            $data['pass_type'] = $data['pass_branding'];
        }
        if(!empty($data['pass_booking_lite'])){
            $data['pass_type'] = 'lite';
        }
        $onepass_details = Config::get('pass.transaction_capture.'.$data['pass_type']);
        $onepass_details['desc_subheader'] = "You are booking your ".$ordinalBookingCount." session using Onepass ".ucfirst($data['pass_type']);

        $des = 'You can cancel this session 1 hour prior to your session time.';
		if($data['finder_category_id'] == 5){
			$des = 'You can cancel this session 15 min prior to your session time.';
		}
        $easy_cancellation = array(
            "header" => "Easy Cancelletion: ",
            "description" => $des
        );

        $passBookingDetails['onepass_details'] = $onepass_details;
        $passBookingDetails['easy_cancellation'] = $easy_cancellation;

        return $passBookingDetails;
    }

    public function firstSessionFree($data = null,$order = null){
        Log::info("data   :::::", [$data]);
        $first_session_free = false;
        
        if(empty($data['session_pack_discount']) && empty($order['session_pack_discount']) && ((!empty($order['init_source']) && $order['init_source'] == 'vendor') || (!empty($data['init_source']) && $data['init_source'] == 'vendor')) && $data['type'] == 'workout-session' && !empty($this->authorization) && (empty($data['customer_quantity']) || $data['customer_quantity'] == 1)){
            $free_trial_ratecard = Ratecard::where('service_id', $data['service_id'])->where('type', 'trial')->where('price', 0)->first();

            if($free_trial_ratecard){
                if(!$this->utilities->checkTrialAlreadyBooked($data['finder_id'], null, $data['customer_email'], !empty($data['customer_phone']) ? $data['customer_phone'] : null, true)){
                    $first_session_free = true;
                }
            }
        }

        return $first_session_free;
    }

    public function getAmazonPaySuccessUrl($order, $success_data){
        
        $origin = !empty($order['origin_url']) ? $order['origin_url'] : Config::get('app.website');
        $base_url = $origin."/paymentsuccess";
        
        if($order['type'] == "booktrials" || $order['type'] == "workout-session"){
            $base_url = $origin."/paymentsuccesstrial";
        }else if($order['type'] == "events"){
            $base_url = $origin."/eventpaymentsuccess";
        }
        
        Log::info(http_build_query($success_data, '', '&'));
        return $base_url."?". http_build_query($success_data, '', '&');
    }

    public function getPuchaseRemarkData($arg_data = null){
        Log::info("getheaderConcatData");
		$purchasesummary_remark = "";
        $onepassHoldCustomer = $this->utilities->onepassHoldCustomer();
		$campBranding = $this->utilities->getCampaignBranding($arg_data);

		if(!empty($campBranding)){
            if(!empty($arg_data['tra_data'])){
                $data = $arg_data['tra_data'];

                if(!empty($data['type']) && $data['type'] == 'memberships' && empty($data['extended_validity'])){
                    $purchasesummary_remark = !empty($campBranding['membership_text']) ? $campBranding['membership_text'] : "";
        
                    if(!empty($data['brand_id']) && $data['brand_id']== 88){
                        if($data['ratecard_amount'] >= 8000){
                            $purchasesummary_remark = !empty($campBranding['multifit8000_membership_text']) ? $campBranding['multifit8000_membership_text'] : "";
                        }else{
                            $purchasesummary_remark = !empty($campBranding['multifit_membership_text']) ? $campBranding['multifit_membership_text'] : "";
                        }
                    }
                    
                    if(!empty($data['finder_flags']['monsoon_flash_discount_disabled']) || in_array($data['finder_id'], Config::get('app.camp_excluded_vendor_id')) ){ 
                        $purchasesummary_remark = "";
                    }
                }
        
                if(!empty($data['type']) && $data['type'] == 'workout-session'){
                    $purchasesummary_remark = !empty($campBranding['pps_text']) ? $campBranding['pps_text'] : "";
                    
                    if(!empty($onepassHoldCustomer) && $onepassHoldCustomer && ($data['amount_customer'] < Config::get('pass.price_upper_limit') || $this->utilities->forcedOnOnepass(['flags' => $data['finder_flags']]))){
                        $purchasesummary_remark = "";
                    }
        
                    if((!empty($data['finder_flags']['mfp']) && $data['finder_flags']['mfp']) || (in_array($data['finder_id'], Config::get('app.camp_excluded_vendor_id'))) || !empty($data['finder_flags']['monsoon_flash_discount_disabled']) || (!empty($data['brand_id']) && $data['brand_id'] == 88) ){
                        $purchasesummary_remark = "";
                    }
                }

            }
        }

        return $purchasesummary_remark;
    }

    public function createPlusRewards(){
        $order_id = 422205;
        $data = Order::where('_id', $order_id)->first()->toArray();
        return $this->plusService->createPlusRewards($data);
    }
}

