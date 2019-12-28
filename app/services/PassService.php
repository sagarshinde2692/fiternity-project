<?PHP namespace App\Services;

use Log;
use Pass;
use App\Services\RazorpayService as RazorpayService;
use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Notification\CustomerNotification as CustomerNotification;
use Validator;
use Order;
use Booktrial;

use Config;
use Request;
use Wallet;
use Customer;
use Coupon;
use Finder;
use stdClass;
use Input;
use Service;
use Nocouponcodeoffers;
class PassService {
    protected $utilities;

    public function __construct(Utilities $utilities) {
        $this->utilities	=	$utilities;
        $this->device_type = Request::header('Device-Type');
        $this->app_version = Request::header('App-Version');
        $this->device_id = !empty(Request::header('Device-Id'))? Request::header('Device-Id'): null;
    }

    public function listPasses($customerId, $pass_type=null, $device=null, $version=null, $category=null, $city=null, $source=null, $email = null, $corporateSource=null, $include_onepass_lite_web=null){
        
        $utilities = new Utilities();

        $passList = Pass::where('status', '1')->where('pass_category', '!=', 'local')->where('pass_type', '!=', 'hybrid');

        $response = Config::get('pass.list');

        if(!empty($category) && $category == 'local' && !empty($city)){

            $passList = Pass::where('status', '1')->where('pass_category', 'local')->where('local_cities.city_name', $city);

            
            $local_pass = Config::get('pass.local_pass_fields');
            $city_centers_count = Config::get('pass.city_centers_count');

            $response['passes'][0]['why_pass'] = $local_pass['why_local_pass'];
            $response['passes'][0]['offerings'] = $local_pass['local_pass_offerings'];
            $response['passes'][0]['remarks'] = $local_pass['local_pass_remarks'];

            $response['passes'][0]['offerings']['text'] = strtr($response['passes'][0]['offerings']['text'], ['city_centers_count'=> $city_centers_count[$city], 'city_name'=>ucwords($city) ]);

            unset($response['passes'][0]['local_pass']);
            $response['passes'][0]['header'] = $response['passes'][0]['header']. " LOCAL";
            unset($response['passes'][1]);

            
            $response['app_passes'][0]['why_pass'] = $local_pass['why_local_pass'];
            $response['app_passes'][0]['offerings'] = $local_pass['local_pass_offerings'];
            $response['app_passes'][0]['remarks'] = $local_pass['local_pass_remarks'];

            $response['app_passes'][0]['offerings']['text'] = strtr($response['passes'][0]['offerings']['text'], ['city_centers_count'=> $city_centers_count[$city], 'city_name'=>ucwords($city) ]);

            unset($response['app_passes'][0]['local_pass']);
            $response['app_passes'][0]['header'] = $response['app_passes'][0]['header']. " LOCAL";
            unset($response['app_passes'][1]);
        }
        else if(!empty($city)){
            $local_pass_count =  Pass::where('status', '1')->where('pass_category', 'local')->where('local_cities.city_name', $city)->count();
            Log::info('localpass count::::', [$local_pass_count, $city]);

            if(!empty($local_pass_count)){
                $response['passes'][0]['local_pass']['header'] = strtr($response['passes'][0]['local_pass']['header'], ['city_name'=> ucwords($city)]);

                $response['app_passes'][0]['local_pass']['header'] = strtr($response['app_passes'][0]['local_pass']['header'], ['city_name'=> ucwords($city)]);

            }
            else{
                unset($response['passes'][0]['local_pass']);
                unset($response['app_passes'][0]['local_pass']);
            }
        }
        else{
            unset($response['passes'][0]['local_pass']);
            unset($response['app_passes'][0]['local_pass']);
        }

        if(!Config::get('app.debug')) {
            $trialPurchased =$this->checkTrialPassUsedByCustomer($customerId);
        }
        else {
            $trialPurchased = false;
        }

        if(!empty($trialPurchased['status']) && $trialPurchased['status']) {
            $passList = $passList->where('type','!=', 'trial');
        }

        if(!empty($source)) {
            $passList = Pass::where('status', '1')->where('pass_category', '!=', 'local');
            if(!empty($email) && $source=='sbig') {
                $trialAvailedCustomer = Order::active()->where('customer_email', $email)->where('customer_source', $source)->where('pass.corporate', $source)->where('pass.complementary', true)->count();
                $passList = $passList->where(function($query) use ($source){
                    $query->orWhere(function($query1) use ($source){
                        $query1->where('pass_type', 'hybrid')->where('corporate', $source);
                    })->orWhere(function($query1) {
                        $query1->where('pass_type', '=', 'red')->where('cities', 'mumbai');
                    });
                })->where('type', '!=', 'trial');

                if(!empty($trialAvailedCustomer) && $trialAvailedCustomer>0) {
                    $passList = $passList->where('complementary', '!=', true)->orderBy('duration')->get();
                } else {
                    $passList = $passList->orderBy('duration')->get();
                }

            }
            else {
                // $passList = $passList->where('corporate', $source)->orderBy('duration')->get();
                $passList = null;
                if(!empty($corporateSource)) {
                    $passList = Pass::where('status', '1')->where('pass_category', '!=', 'local')->where('corporate', $corporateSource)->orderBy('duration')->get();
                }
                if(empty($passList) || count($passList)<1) {
                    $passList = Pass::where('status', '1')->where('pass_category', '!=', 'local')->where('corporate', $source)->orderBy('duration')->get();
                }
            }
        }
        else {
            $passList = $passList->whereIn('show_on_front', [null, true])->where('pass_type', '!=', 'hybrid')->where('corporate', 'exists', false);
            if(!empty($pass_type)) {
                $passList = $passList->where('pass_type', $pass_type);
            }
            if(!empty($city)) {
                $city = getmy_city($city);
                $passList = $passList->where('cities', $city);
            }

            // if(!checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=> "5.33"]) && empty($include_onepass_lite_web)){
            //     $passList->where('lite', null);
            // }
            $passList->where('lite', null);
            $passList = $passList->orderBy('duration')->get();
        }
        

        foreach($passList as &$pass) {
            $passSubHeader = strtr($response['subheader'], ['duration_text' => $pass['duration_text'], 'usage_text' => (($pass['pass_type']=='red')?'LIMITLESS WORKOUTS':'LIMITLESS VALIDITY')]);
            $passDetails = [
                'pass_id' => $pass['pass_id'],
                'header' => $pass['duration_text'],
                'subheader' => $passSubHeader,
                // 'text' => (!empty($pass['pass_type']) && $pass['pass_type']=='red')?'All Access':'Limitless Validity',
                'text' => 'All Access',
                'remarks' => (!empty($pass['type']) && $pass['type'] == 'subscription') ? "" : ucwords($pass['type']),
                'type' => $pass['type'],
                'min_start_date' => time(),
                'max_start_date' => strtotime('31-12-2019'),
                'duration' => $pass['duration'],
                'complementary' => (!empty($pass['complementary']))?$pass['complementary']:false,
            ];

            if(!empty($pass['duration']) && $pass['duration'] == 30 && !empty($pass['pass_type']) && $pass['pass_type'] =='red'){
                $passDetails['max_start_date'] = strtotime('+15 days');
            }

            if($pass['type']=='trial') {
                $utilities = new Utilities();
                $passDetails['header'] .= ' Trial';
                $passDetails['cashback'] = '(50% cashback)';
                if(!empty($device) && in_array($device, ['android'])) {
                    $passDetails['extra_info'] = [
                        'title'=>'50% Instant Cashback',
                        'description'=> $utilities->bullet()." The cashback will be added in the form of FitCash in the Fitternity Wallet (1 Fitcash point = INR 1).<br/>".
                                        $utilities->bullet()." FitCash received can only be used to upgrade ONEPASS subscription.<br/>".
                                        $utilities->bullet()." The instant cashback received is valid for 30 days starting from the date of pass activation.<br/>".
                                        $utilities->bullet()." The offer cannot be clubbed with any other offer.<br/>"
                    ];
                }
                else if(checkAppVersionFromHeader(['ios'=>'5.2.3', 'android'=>5])){
                    $passDetails['extra_info'] = [
                        'title'=>'100% Instant Cashback',
                        'description'=> "<ul><li>The cashback will be added in the form of FitCash in the Fitternity Wallet (1 Fitcash point = INR 1).</li>".
                                        "<li>FitCash received can only be used to upgrade ONEPASS subscription.</li>".
                                        "<li>The instant cashback received is valid for 30 days starting from the date of pass activation.</li>".
                                        "<li>The offer cannot be clubbed with any other offer.</li></ul>"
                    ];
                }
            }

            $agrs = array('pass' => $pass, 'city' => $city);
            $brandingData = $utilities->getPassBranding($agrs);
            unset($passDetails['text']);

            if(!empty($source) && in_array($source, ['sodexo', 'thelabellife', 'generic'])) {
                $passDetails['text'] = "(".$pass['total_sessions']." sessions pass)";
            }
            else if(!empty($source) && in_array($source, ['sbig'])) {
                $passDetails['text'] = "Additional 25% off exclusively for SBI customers";
                if($passDetails['complementary']) {
                    $passDetails['price'] = "FREE";
                    $passDetails['text'] = "Complementary pass exclusively for SBI customers";
                }
            }
            unset($passDetails['cashback']);

            unset($passDetails['extra_info']);

            $passDetails['price'] = 'Rs. '.$pass['price'];
            $passDetails['old_price'] = 'Rs. '.$pass['max_retail_price'];

            if(!empty($source) && in_array($source, ['sbig'])) {
                if($passDetails['complementary']) {
                    $passDetails['price'] = "FREE";
                }
            }

            if(!empty($pass['pass_type']) && $pass['pass_type'] == 'black'){
                unset($passDetails['old_price']);
            }

            if(($pass['pass_type']=='red') || ($pass['pass_type']=='hybrid' && $pass['branding']=='red')) {

                $this->formatPassOffering($response, $pass, $passDetails, $device, 0);

            } else{
                
                $this->formatPassOffering($response, $pass, $passDetails, $device, 1);
            }
        }

        $this->formatPassListingWithOnePassLite($response, $include_onepass_lite_web, $city);
        
        if(!empty($device) && in_array($device, ['android', 'ios'])) {
            $response['passes'] = $response['app_passes'];
        }
        unset($response['app_passes']);
        unset($response['passes'][0]['offerings_lite']);
        unset($response['passes'][1]['offerings_lite']);

        $agrs1 = array('city' => $city);
        $brandingData1 = $utilities->getPassBranding($agrs1);
        if(!empty($brandingData1['red_remarks_header'])){
            $response['passes'][0]['remarks']['header'] .= $brandingData1['red_remarks_header'];
            $response['passes'][0]['about_pass']['campaign_text'] = $brandingData1['red_remarks_header'];
        }

        if(!empty($brandingData1['black_remarks_header'])){
            $response['passes'][1]['remarks']['header'] .= $brandingData1['black_remarks_header'];         
            $response['passes'][1]['about_pass']['campaign_text'] = $brandingData1['black_remarks_header'];
        }

        if(!empty($response['passes'][0]['about_pass']) && !empty($response['passes'][0]['offerings'][1]['offering_text'])){
            $response['passes'][0]['offerings'][1]['text'] = $response['passes'][0]['offerings'][1]['offering_text'];
            unset($response['passes'][0]['offerings'][1]['offering_text']);
        }

        $red_pass_coupons = null;
        $black_pass_coupons = null;

        if(empty($pass_type) && (!empty(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=> "5.33"])) || !empty($include_onepass_lite_web))){
            $red_pass_coupons = $this->listValidCouponsOfOnePass('pass', 'red');
            $black_pass_coupons = $this->listValidCouponsOfOnePass('pass', 'black');
        }
        else if(!empty($pass_type) && $pass_type=='red' && (!empty(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=> "5.33"])) || !empty($include_onepass_lite_web))){
            $red_pass_coupons = $this->listValidCouponsOfOnePass('pass', 'red');
        }
        else if(!empty(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=> "5.33"])) || !empty($include_onepass_lite_web)){
            $black_pass_coupons = $this->listValidCouponsOfOnePass('pass', 'black');
        }
    
        if(!empty($red_pass_coupons['options'])){
            $response['passes'][0]['coupons'] = $red_pass_coupons;
        }
        if(!empty($black_pass_coupons['options'])){
            $response['passes'][1]['coupons'] = $black_pass_coupons;
        }

        if((!empty(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=> "5.33"])) || !empty($include_onepass_lite_web)) && !empty($pass_type)){
            if($pass_type == 'black'){
                $response['passes'][0] = $response['passes'][1];
            }

            unset($response['passes'][1]);
        }
        // $passConfig = Config::get('pass');
        // $passCount = Order::active()->where('type', 'pass')->count();
        // if($passCount>=$passConfig['total_available']) {
        //     $response['sold_out'] = true;
        // }
        // $response['passes_left'] = $passConfig['total_available'] - $passCount;
        // $response['total_passes'] = $passConfig['total_available'];
        // unset($response['passes'][1]);
        return $response;
    }

    public function passCapture($data, $existing_order = null){

        if(!empty($data['order_id'])){
            $order_exists = true;
            if(empty($existing_order)){
                $order = Order::find($data['order_id']);
            }else{
                $order = $existing_order;
            }
            $keys = ['customer_email', 'customer_name', 'customer_phone', 'pass_id'];
            foreach($keys as $key){
                if(empty($data[$key]) & !empty($order[$key])){
                    $data[$key] = $order[$key];
                }
            }

        }
        if(!empty($data['customer_source']) && (in_array($data['customer_source'], ['sodexo', 'thelabellife', 'corporate']) || (!empty($data['corporate_source']) && in_array($data['corporate_source'], ['generic'])))){
            // $data['customer_source'] = 'sodexo';
        }
        else {
            $data['customer_source'] = !empty(Request::header('Device-Type')) ? Request::header('Device-Type') : "website" ;
        }
        
        $data['type'] = "pass";
        $data['status'] = "0";
        $utilities = new Utilities();
        $customer_detail = $utilities->getCustomerDetail($data);

        if(empty($customer_detail['status']) || $customer_detail['status'] != 200){
            return $customer_detail;
        }

        $data = array_merge($data, $customer_detail['data']);
        
        $pass = Pass::where('pass_id', $data['pass_id'])->first()->toArray();

        if($pass['type']=='trial' && !Config::get('app.debug')) {
            $trialExists = $this->checkTrialPassUsedByCustomer($customer_detail['data']['customer_id']);
            if(!empty($trialExists['status']) && $trialExists['status']) {
                return [
                    'status' =>400,
                    'data' => null,
                    'msg' => 'Not eligible to book a trial pass.'
                ];
            }
        }

        if(!empty($pass['pass_category']) && $pass['pass_category'] =='local'){

            if(empty($data['customer_city']) && empty($data['city'])){

                return [
                    'status' =>400,
                    'data' => null,
                    'msg' => 'First selecte city.'
                ];
            }

            $data['pass_city_name'] = !empty($data['customer_city']) ? $data['customer_city']: $data['city'];

            if(!in_array(strtolower($data['pass_city_name']), array_column($pass['local_cities'], 'city_name'))){

                return [
                    'status' =>400,
                    'data' => null,
                    'msg' => 'Not availabe a local pass in your selected city.'
                ];
            }

            $data['pass_city_id'] = $this->utilities->getCityId(strtolower($data['pass_city_name']));
        }

        $data['pass'] = $pass;
        if(empty($data['rp_subscription_id'])){
            $data['amount'] = $pass['price'];
        }

        $data['type'] = 'pass';
        $data['payment_mode'] =  'paymentgateway';
        $data['pass_type'] = $pass['type'];
        
        
        if(!empty($data['preferred_starting_date'])){
            $data['start_date'] = new \MongoDate(strtotime('midnight', strtotime($data['preferred_starting_date'])));
        }else{
            $data['start_date'] = new \MongoDate(strtotime('midnight', time()));
        }
        
        $data['end_date'] = new \MongoDate(strtotime('midnight', strtotime('+'.$pass['duration'].' days', (!empty($data['preferred_starting_date']))?strtotime($data['preferred_starting_date']):time())));
        
        if(!empty($pass['credits'])){
            
            $data['total_credits'] = $pass['credits'];
        
        }
        
        if(empty($order_exists)){
            $id = Order::maxId()+1;
            $data['_id'] = $id;
        }else{
            $data['_id'] = $data['order_id'];
        }
        
        $data['order_id'] = $data['_id'];
        $data['orderid'] = $data['_id'];

        if(empty($data['customer_source']) || (!in_array($data['customer_source'], ['sodexo', 'thelabellife', 'corporate']) && (empty($data['corporate_source']) || !in_array($data['corporate_source'], ['generic'])))){
            $rewardinfo = $this->addRewardInfo($data);
		}
        if(!empty($rewardinfo)){
            $data = array_merge($data, $rewardinfo);
        }
        
        if(!empty($data['pass']['payment_gateway']) && $data['pass']['payment_gateway'] == 'payu'){
            
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
            
            
            $data['service_name'] = 'Pass';
            
            $data['service_id'] = 100002;
            
            if(Config::get('app.env') != 'production'){
                $data['env'] = 1;
            }
            
            
            $data['amount_customer'] = $data['amount'];

            $this->applyFitcash($data);

            $utilities = new Utilities();
            $headerSource = Request::header('source');
            if(!empty($headerSource) && $headerSource==Config::get('app.sbig_acronym') && !empty($data['customer_email']) && !empty($data['pass_id']) && !empty($pass)){
                $data['sbig'] = true;
                $data['customer_source'] = "sbig";
                $data['coupon_code'] = $utilities->getSBIGCouponCode($headerSource, $data['customer_email'], $data['pass_id'], $pass);
            }

            if(!empty($data['coupon_code'])) {
                $customerCoupon = Coupon::where('status', '1')->where('code', strtolower($data['coupon_code']))->where('type', 'pass')->where('start_date', '<=', new \MongoDate())->where('end_date', '>=', new \MongoDate())->first();
                if(!empty($customerCoupon)) {
                    $customerreward = new CustomerReward();
                    $customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
                    $customer_email = !empty($data['customer_email']) ? $data['customer_email'] : null;
                    $couponCheck = $customerreward->couponCodeDiscountCheck(null,$data["coupon_code"],$customer_id, null, null, null, null, $customer_email, $pass);

                    Log::info("couponCheck");
                    Log::info($couponCheck);
                    $amount = $data['amount'];
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
                        $data['amount'] = $amount;
                        $data['complementary_pass'] = (isset($amount))?($amount==0):false;
                        // if(strtolower($data["coupon_code"]) == 'fit2018'){
                        //     $data['routed_order'] = "1";
                        // }

                        if(!empty($couponCheck['flags']['extension_percent'])){
                            $data['duration_more'] = round(($pass['duration'] * $couponCheck['flags']['extension_percent']) / 100);

                            $data['duration_total'] = $pass['duration'] + $data['duration_more'];

                            $data['end_date'] = new \MongoDate(strtotime('midnight', strtotime('+'.$data['duration_total'].' days', (!empty($data['preferred_starting_date']))?strtotime($data['preferred_starting_date']):time())));
                        }

                    }
                }
            }
            else if(!empty($order) && empty($data['coupon_code'])){
                $keys = ['coupon_discount_percent', 'coupon_discount_amount', 'coupon_description', 'coupon_code'];
                $order->unset($keys);
            }

            $hash = getHash($data);
            $data = array_merge($data,$hash);
            // $data['amount'] = 0;
            $data['preferred_starting_date'] = (!empty($data['preferred_starting_date']))?date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date'])):null;
            $data['code'] = (string) random_numbers(5);
            $this->addMonthlyBookingCounter($data);
            if(empty($order_exists)){
                $order = new Order($data);
                $order['_id'] = $data['_id'];
                $order->save();
            }else{
                $order->update($data);
            }
            
            if(in_array($order['customer_source'],['android','ios','kiosk'])){
                $mobilehash = $order['payment_related_details_for_mobile_sdk_hash'];
            }
            $result['firstname'] = trim(strtolower($order['customer_name']));
            $result['lastname'] = "";
            $result['phone'] = $order['customer_phone'];
            $result['email'] = strtolower($order['customer_email']);
            $result['orderid'] = $order['_id'];
            $result['txnid'] = $txnid;
            $result['amount'] = $order['amount'];
            $result['productinfo'] = strtolower($order['productinfo']);
            $result['service_name'] = preg_replace("/^'|[^A-Za-z0-9 \'-]|'$/", '', strtolower($order['service_name']));
            $result['successurl'] = $successurl;
            $result['hash'] = $order['payment_hash'];
            $result['payment_related_details_for_mobile_sdk_hash'] = $mobilehash;
            $result['finder_name'] = strtolower($order['finder_name']);
            $result['complementary_pass'] = $order['complementary_pass'];
            $result['type'] = 'pass';
            $result['full_payment_wallet'] = empty($result['amount']);
            $resp = [
                'status' => 200,
                'data' => $result,
                'message' => "Tmp Order Generated Sucessfully"
            ];
            
        }else{
            
            $data['preferred_starting_date'] = (!empty($data['preferred_starting_date']))?date('Y-m-d 00:00:00', strtotime($data['preferred_starting_date'])):null;
            $data['amount_customer'] = $data['amount'];
            $data['rp_subscription_amount'] = $data['amount_customer'];
            
            $this->applyFitcash($data);
            
            $data['rp_name'] = $data['pass']['duration_text'];
            $order = new Order($data);
            $order['_id'] = $data['_id'];

            if(!empty($data['rp_subscription_id'])){
                $order['rp_subscription_id'] = $data['rp_subscription_id'];
                $order['rp_plan_id'] = $data['plan_id'];
                $order['rp_orignal_pass_order_id'] = $data['rp_orignal_pass_order_id'];
            }

            $order->save();

            if(empty($data['rp_subscription_id'])){
                $razorpay_service = new RazorpayService();
                $create_subscription_response = $razorpay_service->createSubscription($id);
                $order['subscription_id'] = $create_subscription_response['subscription_id'];
                $order['rp_subscription_id'] = $create_subscription_response['rp_subscription_id'];
            }


        }

        $resp = [
            'status' => 200,
            'data' => !empty($result) ? $result : $order,
            'message' => "Tmp Order Generated Sucessfully"
        ];

        // if(!empty($order['amount'])){
        if(!empty($order['amount']) && checkAppVersionFromHeader(['ios'=>'5.2.3', 'android'=>5])){
            $resp['data']["coupon_details"] = [
                "title" => "Apply Coupon Code",
                "description" => "",
                "applied" => false,
                "remove_title" => "",
                "remove_msg" => ""
            ];
        }
        // if(!empty($data['coupon_code']) && (!empty($data['coupon_discount_amount']) || !empty($data['coupon_flags']['cashback_100_per']))){
        if(checkAppVersionFromHeader(['ios'=>'5.2.3', 'android'=>5]) && (!empty($data['coupon_code']) && (!empty($data['coupon_discount_amount']) || !empty($data['coupon_flags']['cashback_100_per']) || !empty($data['coupon_flags']['vk_bag_and_box_reward'])))){
            $resp['data']["coupon_details"] = [];
            $resp['data']['coupon_details']['title'] = strtoupper($data['coupon_code']);
            $resp['data']['coupon_details']['remove_title'] =  strtoupper($data['coupon_code'])." applied";
            $resp['data']['coupon_details']['applied'] =  true;
            if(isset($data['coupon_description'])){
                $resp['data']['coupon_details']['description'] = $data['coupon_description'];
            }
        }

        $resp['data']['order_details'] = $this->getBookingDetails($order->toArray());
        
        $payment_mode_type_array = ['paymentgateway'];

        foreach ($payment_mode_type_array as $payment_mode_type) {

            $payment_details[$payment_mode_type] = $this->getPaymentDetails($order->toArray(),$payment_mode_type);

        }
        
        $resp['data']['payment_details'] = $payment_details;
        if(!empty($result['amount'])){
            $resp['data']['payment_modes'] = $this->getPaymentModes($resp, $order->toArray());
        }

        return $resp;

    }

    public function passSuccess($data){
        
        if(empty($data['order_id'])){
            return;
        }

        if(!empty($data['razorpay'])){
            $data['payment_id'] = $data['razorpay']['razorpay_payment_id'];
            $verify_status = $this->verifyOrderSignature(["body"=>$data['razorpay']['rp_body'], "key"=> $data['razorpay']['key'], "signature"=>$data['razorpay']['razorpay_signature']])['status'];
            if(!$verify_status){
                return ['status'=>400, 'message'=>'Invalid Request.'];
            }
        }
        
        $data['order_id'] = intval($data['order_id']);

        $order = Order::where('_id', $data['order_id'])->first();

        if(!empty($order['status'])){
            $block_communication = true;
            $block_success_transactions = true;
        }

        $wallet_update = $this->updateWallet($order);

        if(empty($wallet_update['status']) || $wallet_update['status'] != 200){
            return $wallet_update;
        }

        Log::info('pass success:: ', [$data]);

        if(empty($order['amount']) && empty($order['status'])){
            $order->status ='1';
            $order->onepass_sessions_total = (!empty($order['pass']['classes']))?$order['pass']['classes']:-1;
            $order->update();
        }
        
        $utilities = new Utilities();
        $utilities->updateCoupon($order);
        
        $order = $this->passSuccessRazorpay($order, $data);
        
        if(empty($order['status'])){
            return ['status'=>400, 'message'=>'Something went wrong. Please contact customer support. (2)'];
        }

        if(empty($block_communication)){
            $communication = $this->passPurchaseCommunication($order);
            $order->update(['communication'=> $communication]);
        }

        $success_data = $this->getSuccessData($order);

        if(empty($block_success_transactions)){
            $this->giveCashbackOnOrderSuccess($order);
        }

        \Queue::connection('redis')->push('PassController@afterTransQueued', array('data'=>$order),Config::get('app.queue'));

        return ['status'=>200, 'data'=>$success_data, 'order'=>$order];

    }

    public function getPassBookings($orderId) {
        if(empty($orderId)) {
            return;
        }
        $booktrials = Booktrial::where('pass_order_id', $orderId)->where('going_status',1)->where('schedule_date_time','>=',new \MongoDate(strtotime('midnight')))->get();
        if(empty($booktrials)) {
            return;
        }
        $booktrials = $booktrials->toArray();
        $finalList = [];
        foreach($booktrials as $booking) {
            $temp = [];
            $temp['_id'] = $booking['_id'];
            $temp['order_id'] = $booking['order_id'];
            $temp['pass_order_id'] = $booking['pass_order_id'];
            $temp['customer_id'] = $booking['customer_id'];
            $temp['customer_name'] = $booking['customer_name'];
            $temp['customer_email'] = $booking['customer_email'];
            $temp['customer_phone'] = $booking['customer_phone'];
            $temp['type'] = $booking['type'];
            $temp['finder_id'] = $booking['finder_id'];
            $temp['amount'] = $booking['amount'];
            $temp['finder_slug'] = $booking['finder_slug'];
            $temp['finder_location'] = $booking['finder_location'];
            $temp['schedule_date'] = $booking['schedule_date'];
            $temp['schedule_date_time'] = $booking['schedule_date_time'];
            $temp['schedule_date'] = $booking['schedule_date'];
            $temp['schedule_slot_start_time'] = $booking['schedule_slot_start_time'];
            $temp['schedule_slot_end_time'] = $booking['schedule_slot_end_time'];
            array_push($finalList, $temp);
        }
        return $finalList;
    }

    function getPaymentModes($data, $order = null){
        
        $utilities = new Utilities();

        $payment_modes = [];

        $payment_options['payment_options_order'] = ["cards", "upi", "wallet", "netbanking", "emi"];
        

        $payment_options['upi'] = [
            'title' => 'UPI',
            'notes' => "Open your UPI app on your phone to approve the payment request from Fitternity"
        ];

        $payment_options['wallet'] = [
            'title' => 'Wallet',
            'subtitle' => 'Transact online with Wallets',
            'value'=>'wallet',
            'options'=>(!empty($order['customer_email']) && in_array($order['customer_email'], ['akhilkulkarni@fitternity.com', 'ss@relianceada.com']))?Config::get('app.pass_payment_options_wallets_test'):Config::get('app.pass_payment_options')
        ];
        
        if(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=>5.33])){
            $payment_options['payment_options_order'] = ["cards", "googlepay", "wallet", "netbanking", "emi", "upi"];
            $payment_options['googlepay']  = [
                'title' => 'GooglePay',
                'notes' => "Open your Google Pay app on your phone to approve the payment request from Fitternity"
            ];
        }

        $payment_modes[] = array(
            'title' => 'Pay now',
            'subtitle' => 'Pay online through wallet,credit/debit card',
            'value' => 'paymentgateway',
            'payment_options'=>$payment_options
        );

        return $payment_modes;
    }

    public function getPassOrderDetails($customerId, $credits) {
        $passOrder = Order::raw(function($collection) use ($customerId, $credits) {
            $aggregate = [
                ['$match' => [
                    'status' => '1',
                    'type' => 'pass',
                    'customer_id' => $customerId,
                    'end_date' => [
                        '$gte' => new \MongoDate(strtotime('midnight'))
                    ],
                    '$or' => [
                        [ 'total_credits' => ['$exists'  => true ]],
                        [ 'pass.unlimited_access'  => true ]
                    ]
                ]],
                ['$project' => [
                    'pass_type'=>1, 'total_premium_sessions'=>1, 'premium_sessions_used'=>1, 'total_credits' => 1, 'total_credits_used' => 1,'unlimited_access' => '$pass.unlimited_access',
                    'credits_diff' => ['$subtract' => ['$total_credits', '$total_credits_used']],
                    'credits_available' => ['$gte' => [ [ '$subtract' => [ '$total_credits', '$total_credits_used'] ], $credits]]
                ]],
                ['$match' => [
                    '$or' => [
                        ['$or' => [
                            [ 'credits_available' => true ],
                            [ 'total_credits_used' => ['$exists' => false] ],
                        ]],
                        ['pass.unlimited_access' => true]
                    ]
                ]],
                ['$sort' => ['_id' => -1]],
                ['$limit' => 1]
            ];
            Log::info('getPassOrderDetails query: ', [$aggregate]);
            return $collection->aggregate($aggregate);
        });
        if(!empty($passOrder['result'][0])) {
            return $passOrder = $passOrder['result'][0];
        }
        return;
    }

    public function getPassOrder($customerId, $scheduleDate = null) {
        // $passOrder = Order::active()->where('customer_id', $customerId)->where('type', 'pass')->first();
        $scheduleDate = (!empty($scheduleDate))?(new \MongoDate($scheduleDate)):(new \MongoDate());
        $passOrder = Order::raw(function ($collection) use ($customerId, $scheduleDate) {
            $aggregate = [
                [
                    '$project' => [
                        '_id'=> 1, 'customer_id'=> 1, 'status'=> 1, 'type'=> 1, 'pass'=> 1, 'start_date'=> 1, 'end_date'=> 1, 'onepass_sessions_used'=> 1, 'onepass_sessions_total'=> 1, 'diff_sessions'=> ['$cmp'=> ['$onepass_sessions_total','$onepass_sessions_used']], 'pass_type'=> '$pass.pass_type', 'pass_city_id'
                    ]
                ],
                [
                    '$match' => [
                        'customer_id' => $customerId, 'status' => '1', 'type' => 'pass',
                        'start_date' => ['$lte' => $scheduleDate],
                        '$or' => [
                            ['$and' => [['pass.pass_type' => 'red'], ['end_date' => ['$gt' => $scheduleDate]]]],
                            ['$and' => [['pass.pass_type' => 'black'], ['diff_sessions' => ['$gt' => 0]]]]
                        ]
                    ]
                ],
                ['$sort' => ['_id' => 1]],
                ['$limit' => 1]
            ];
            return $collection->aggregate($aggregate);
        });
        
        if(!empty($passOrder['result'][0]['_id'])) {
            Order::$withoutAppends = true;
            return $passOrder = Order::where('_id', $passOrder['result'][0]['_id'])->first();
        }
        Order::$withoutAppends = true;
        return $passOrder = Order::active()->where('customer_id', $customerId)->where('type', 'pass')->first();
    }

    public function getPremiumExpiryDates($bookingStartDate, $premiumBookingInterval, $duration) {
        $time = time();
        $totalCycles = floor($duration/$premiumBookingInterval);
        $bookingEndDate = null;
        if(!empty($totalCycles)) {
            for($i=0; $i<$totalCycles; $i++) {
                if(empty($bookingEndDate)) {
                    $dateExpTest = ($bookingStartDate)*$i;
                    if($time<$dateExpTest || $i==($totalCycles-1)) {
                        $bookingEndDate = $dateExpTest;
                    }
                    else {
                        $bookingStartDate = $dateExpTest;
                    }
                }
            }
            return ['bookingStartDate'=>$bookingStartDate, 'bookingEndDate'=>$bookingEndDate];
        }
        return null;
    }

    public function isPremiumSessionAvailable($passOrder) {
        $premiumExpiryDate = $this->getPremiumExpiryDates($passOrder['success_date']->sec, $passOrder['pass']['premium_booking_interval'], $passOrder['pass']['duration']);
        Order::$withoutAppends = true;
        $bookingCount = Order::active()->whereIn('type', ['workout-session', 'booktrial', 'trial'])->where('pass_id', $passOrder['_id'])
                        ->where('created_at', '<', new \MongoDate($premiumExpiryDate['bookingEndDate']))
                        ->where('created_at', '>=', new \MongoDate($premiumExpiryDate['bookingStartDate']))
                        ->where('amount','<=',$passOrder['premium_min_booking_price'])
                        ->where('amount','>=',$passOrder['premium_max_booking_price'])
                        ->count();
        if(empty($premiumExpiryDate)) {
            return false;
        }
        return (isset($bookingCount))?$bookingCount<1:false;
    }

    public function allowSession($amount, $customerId, $date = null, $finderId = null, $fromService=null) {
        
        if(!checkAppVersionFromHeader(['ios'=>'5.1.1', 'android'=>5])){
            return;
        }
        
        if(empty($amount) && empty(!$customerId)) {
            return;
        }
        $customer = Customer::find($customerId);
        $upper_amount = Config::get('pass.price_upper_limit');
        if(empty($date)){
            $date = date('d-m-Y', time());
        }
        // $schedule_time = strtotime($date);
        $schedule_time = strtotime('midnight', strtotime($date));
        if(!empty($customerId)) {
            $passOrder = $this->getPassOrder($customerId, $schedule_time);            
        }

        $passType = null;
        $profile_completed = false;
        $onepass_lite = false;
        if(!empty($passOrder)) {
            $passType = $passOrder['pass']['pass_type'];

            if(!empty($passOrder['pass']['lite'])){
                Log::info('insode onepass lite orders::::');
                $onepass_lite = true;
                // $upper_amount = Config::get('pass.onepass_lite_price_upper_limit');
            }
        }

        $finder = null;
        if(!empty($finderId)) {
            $finder_onepass_flag = 'flags.not_available_on_onepass';

            if(!empty($passOrder['pass']['lite'])){
                $finder_onepass_flag = 'flags.not_available_on_onepass_lite';
            }
            
            Finder::$withoutAppends = true;
            $finder = Finder::active()->where('_id', $finderId)->where($finder_onepass_flag, '!=', true)->first();
            
            if(
                empty($finder) 
                || 
                (
                    !empty($passOrder['pass_city_id']) 
                    && 
                    (
                        (int)$finder['city_id'] 
                        != 
                        (int)$passOrder['pass_city_id']
                    )
                )
            ) {
            return [ 'allow_session' => false, 'order_id' => $passOrder['_id'], 'pass_type'=>$passType ];
            }
        }
        
        $canBook = false;
        $pass_branding= null;
        if(!empty($passOrder['pass'])) {
            if($schedule_time>=strtotime($passOrder['start_date'])){
                if($passOrder['pass']['pass_type']=='black'){
                    $sessionsUsed = $passOrder['onepass_sessions_used'];
                    $sessionsTotal = $passOrder['onepass_sessions_total'];
                    if($sessionsTotal > $sessionsUsed) {
                        $canBook = true;
                    }
                }
                else if($passOrder['pass']['pass_type']=='red') {
                    // $duration = $passOrder['pass']['duration'];
                    if($schedule_time<strtotime($passOrder['end_date'])){
                        Booktrial::$withoutAppends = true;
                        $todaysBooking = Booktrial::where('pass_order_id', $passOrder['_id'])->where('schedule_date', new \MongoDate($schedule_time))->where('going_status_txt', '!=', 'cancel')->first();
                        if(empty($todaysBooking)) {
                            $canBook = true;
                        }
                    }
                }
                else if($passOrder['pass']['pass_type']=='hybrid') {
                    Log::info('inside hybrid passs:::::', [strtotime($date)]);
                    if(!empty($passOrder['pass']['corporate']) && $passOrder['pass']['corporate'] == 'sodexo') {
                        $upper_amount = Config::get('pass.sodexo_price_upper_limit');
                    }

                    if($schedule_time<strtotime($passOrder['end_date'])){

                        Booktrial::$withoutAppends = true;
                        $todaysBooking = Booktrial::where('pass_order_id', $passOrder['_id'])->where('schedule_date', new \MongoDate($schedule_time))->where('going_status_txt', '!=', 'cancel')->first();

                        if(empty($todaysBooking)) {
                            
                            $sessionsUsed = $passOrder['onepass_sessions_used'];
                            $sessionsTotal = $passOrder['onepass_sessions_total'];
                            $monthlySessionsTotal = $passOrder['pass']['monthly_total_sessions'];
                            if($sessionsTotal > $sessionsUsed) {

                                Log::info('inside hybrid passs::::: before monthly booking checkins::::', [strtotime($date)]);
                                $pass_start_date = strtotime($passOrder['start_date']);
                                $pass_end_date = strtotime($passOrder['end_date']);
                                $trial_date = strtotime($date);
                                $end_date = strtotime('+30 days', strtotime($passOrder['start_date']));
                                $length = (int)$passOrder['pass']['duration']/30;

                                if(empty($passOrder['monthly_total_sessions_used'])){
                                    return [ 'allow_session' => false, 'order_id' => $passOrder['_id'], 'pass_type'=>$passType ];
                                }
                                
                                $monthly_bookings = $passOrder['monthly_total_sessions_used']; 
                                for($i=0; $i < $length; $i++){

                                    if(
                                        (
                                            $trial_date >= strtotime(date('Y-m-d H:i:s', $monthly_bookings[$i]['start_date']->sec)) && $trial_date < strtotime(date('Y-m-d H:i:s', $monthly_bookings[$i]['end_date']->sec))
                                        )
                                    ){
                                        if($monthlySessionsTotal > $monthly_bookings[$i]['count']){
                                            $canBook= true;
                                        }
                                        break;
                                    }
                                }
                                
                            }
                            $pass_branding = $passOrder['pass']['branding'];

                        }
                        
                    }
                }
            }
            Log::info('allow session uppper amount', [$upper_amount]);
            if (($amount>= $upper_amount && (empty($finder['flags']['forced_on_onepass']) || !($finder['flags']['forced_on_onepass']))) || !$canBook) {
                // over 1000
                return [ 'allow_session' => false, 'order_id' => $passOrder['_id'], 'pass_type'=>$passType, 'pass_branding' => $pass_branding];
            }
            else {
                // below 1001
                
            return [ 'allow_session' => true, 'order_id' => $passOrder['_id'], 'pass_type'=>$passType, 'pass_branding' => $pass_branding, 'max_amount' => $upper_amount, 'onepass_lite' => $onepass_lite];
                //return [ 'allow_session' => true, 'order_id' => $passOrder['_id'], 'pass_type'=>$passType ];
            }
        }
        
        return [ 'allow_session' => false, 'order_id' => $passOrder['_id']];
    }

    public function getCreditsApplicable($amount, $customerId) {

        // credits: 0=>pass not applicable, -1=>unlimited access, >0=>monthly access credit points for the session

        if(empty($amount) && empty(!$customerId)) {
            return;
        }

        $customer = Customer::where('_id', $customerId)->first();

        $credits = null;
        $creditMap = Config::get('app.creditMap');
        foreach($creditMap as $rec) {
            if($amount<=$rec['max_price']){
                $credits = $rec['credits'];
                break;
            }
        }

        if(!empty($customer) && !empty($credits)) {
            $passOrder = $this->getPassOrderDetails($customerId, $credits);
        }

        if(!empty($passOrder)) {
            $passType = $passOrder['pass_type'];
            Log::info('pass orders:::::::::::::::::', [$passOrder]);
        }
        // if(!empty($passType) && $passType=='unlimited') {


        if(!empty($passOrder) && $this->checkUmlimitedPass($passOrder)) {

            if($amount>=750) {
                if(!isset($passOrder['total_premium_sessions']) || !isset($passOrder['premium_sessions_used']) || !($passOrder['premium_sessions_used']<$passOrder['total_premium_sessions'])) {
                    return [ 'credits' => 0, 'order_id' => $passOrder['_id']];        
                }else{
                    $pass_premium_session = true;
                }
            }

            return [ 'credits' => -1, 'order_id' => $passOrder['_id'], 'pass_type' => $passType];
        }
        // else if(!empty($passOrder) && $passOder['pass']['classes']){
        //     return [ 'credits' => -1, 'order_id' => $passOrder['_id'], 'pass_type' => $passType];
        // }
        // else if(empty($passType)) {
        //     return [ 'credits' => 0, 'order_id' => (!empty($passOrder['_id']))?$passOrder['_id']:null ];
        // }
        // if(!empty($passOrder['total_credits']) && empty($passOrder['total_credits_used'])) {
        //     $passOrder['total_credits_used'] = 0;
        // }
        // if(isset($passOrder['total_credits']) && ($credits+$passOrder['total_credits_used'])<=$passOrder['total_credits']) {
        //     return [ 'credits' => $credits, 'order_id' => $passOrder['_id'], 'pass_type' => $passType ];
        // }
        return [ 'credits' => 0, 'order_id' => $passOrder['_id'], 'pass_premium_session' => !empty($pass_premium_session), 'pass_type'=>$passType];
        
    }
    
    public function passSuccessPayU($data){
    
        $rules = [
            'order_id'=>'required | integer',
            'verify_hash'=>'required'
        ];

        if(!empty($data['payment_id_paypal']) && empty($data['verify_hash'])) {
            $data['verify_hash'] = true;
        }

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return ['status' => 404,'message' => error_message($validator->errors())];
        }

        if(!empty($data['order_id'])) {
            $data['order_id'] = intval($data['order_id']);
        }
        $order = Order::where('status', '0')->where('_id', $data['order_id'])->first();
        
        if(empty($order)){
            return ['status'=>400, 'message'=>'Something went wrong. Please try later'];
        }
        
        $utilities = new Utilities();    
        $hash_verified = $utilities->verifyOrder($data, $order);

        if(empty($hash_verified)){
            return ['status'=>400, 'message'=>'Something went wrong. Please try later'];
        }

        if(!empty($order['amount']) && !empty($order['pass']['cashback']) && empty($order['coupon_code'])){
            $validity = time()+(86400*30);
            $amount = ceil($order['amount']/2);
            $walletData = array(
                "order_id"=>$order['_id'],
                "customer_id"=> intval($order['customer_id']),
                "amount"=> $amount,
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $amount,
                "type"=>'CASHBACK',
                'entry'=>'credit',
                'order_type'=>['pass'],
                "description"=> "50% Cashback on buying trial pass, Expires On : ".date('d-m-Y',$validity),
                "validity"=>$validity,
            );
    
            $utilities->walletTransaction($walletData);
        }

        $wallet_update = $this->updateWallet($order);

        if(empty($wallet_update['status']) || $wallet_update['status'] != 200){
            return $wallet_update;
        }

        if(!empty($data['parent_payment_id_paypal'])) {
            $order->parent_payment_id_paypal = $data['parent_payment_id_paypal'];
        }
        if(!empty($data['payment_id_paypal'])) {
            $order->payment_id_paypal = $data['payment_id_paypal'];
        }

        $order->status = '1';
        $order->onepass_sessions_total = (!empty($order->pass['classes']))?$order->pass['classes']:-1;
        $order->update();

        try{
            $communication = $this->passPurchaseCommunication($order);
            $order->communication = $communication;
            $order->update();
        }catch (Exception $e) {
            Log::info('Error : '.$e->getMessage());
        }

        try{
            $this->giveCashbackOnOrderSuccess($order->toArray());
        }catch (Exception $e) {
            Log::info('Error : '.$e->getMessage());
        }

        return ['status'=>200, 'message'=>'Transaction successful'];

    
    }

    public function getCreditsForAmount($amount) {
        $creditMap = Config::get('app.creditMap');
        $credits = 0;
        foreach($creditMap as $rec) {
            if($amount<$rec['max_price']) {
                $credits = $rec['credits'];
                break;
            }
        }
        return $credits;
    }
    
    public function checkTrialPassUsedByCustomer($customerId) {
        if(empty($customerId)) {
            return;
        }
        $response = ["status" => false];
        $trialPass = Order::where('status', "1")
        ->where('customer_id', $customerId)
        ->where("pass_type", 'trial')
        ->where("type", "pass")
        ->select('_id')
        ->first();

        if(isset($trialPass['_id'])) {
            $response["status"]= true;
        }
        return $response;
    }

    public function getSuccessData($order){
        
        $success = Config::get('pass');
        $success_template = $success['success'];
        // $success_template['header'] = strtr($success_template['header'], ['___type' => ucwords($order['pass']['type'])]);
        $success_template['header'] = 'Purchase Successful';
        $success_template['customer_name'] = $order['customer_name'];
        $success_template['customer_email'] = $order['customer_email'];
        $success_template['subline'] = strtr(
            $success_template['subline'], 
            [
                '__customer_name'=>$order['customer_name'], 
                '__pass_name'=>$order['pass']['name'],
                '__pass_duration'=> $order['pass']['duration_text']
            ]
        );

        $unlimited = !empty($order['pass']['unlimited_access']) && $order['pass']['unlimited_access'];
        $success_template['pass']['text'] = strtr(
            $success_template['pass']['text'],
            [
                '__usage_remark' => $unlimited?'Unlimited Workouts': $order['pass']['classes'].' sessions',
                '__end_date'=> ($unlimited)?('Valid up to '. date_format($order['end_date'],'d-M-Y')):''
            ]
        );
        if($unlimited){
            $usage_text = !empty($order['pass']['lite']) ? 'LITE': 'UNLIMITED USAGE';
            $success_template['pass']['subheader'] = strtr(
                $success_template['pass']['subheader'],
                [
                    'duration_text'=> $order['pass']['duration_text'],
                    'usage_text' => $usage_text
                ]
            );
            $success_template['pass']['subheader'] = $order['pass']['duration_text'].' Validity';
            $success_template['pass']['card_header'] = $usage_text;// $order['pass']['name'];
            $success_template['pass']['header'] = $usage_text;// $order['pass']['name'];
            $success_template['pass']['type'] = '';//strtoupper($order['pass']['type']);
            $success_template['pass']['price'] =  $order['pass']['price'];
            $success_template['pass']['pass_type'] =  $order['pass']['pass_type'];
            $success_template['pass']['image'] = $success['pass_image_silver'];
            $success_template['pass_image'] = $success['pass_image_silver'];
            $success_template['pass']['usage_text'] = $usage_text;
        }
        else{
            $success_template['pass']['card_header'] = strtoupper($order['pass']['duration_text']);// $order['pass']['name'];
            $success_template['pass']['header'] = strtoupper($order['pass']['duration_text']);// $order['pass']['name'];
            // strtr(
            //     $success_template['pass']['header'],
            //     [
            //         '__name'=> $order['pass']['name']
            //     ]
            // );
            $success_template['pass']['type'] = '';//strtoupper($order['pass']['type']);
            $success_template['pass']['price'] =  $order['pass']['price'];
            // $success_template['pass']['pass_type'] =  $order['pass']['pass_type'];
            $success_template['pass']['pass_type'] =  ($order['pass']['pass_type']!='hybrid')?$order['pass']['pass_type']:$order['pass']['branding'];
            $success_template['pass']['subheader'] = strtr(
                $success_template['pass']['subheader'],
                [
                    'duration_text'=> $order['pass']['duration_text'],
                    'usage_text' => (!empty($order['corporate_source']) || in_array($order['customer_source'], ['sodexo', 'thelabellife']))?'ALL ACCESS PASS':'UNLIMITED VALIDITY'
                ]
            );
            $success_template['pass']['subheader'] = 'Unlimited Validity';
            $success_template['pass']['image'] = $success['pass_image_gold'];
            $success_template['pass_image'] = $success['pass_image_gold'];
            $success_template['pass']['usage_text'] = 'UNLIMITED VALIDITY';
        }

        $pass_type_ori = $order['pass']['pass_type'];
        $pass_duration = $order['pass']['duration'];

        if(!empty($order['customer_city'])){
            $order['city'] = $order['customer_city'];
        }
        $city = !empty($order['city']) ? $order['city'] : null;
        $coupon_flags = !empty($order['coupon_flags']) ? $order['coupon_flags'] : null;
        $device_type = !empty(Request::header('Device-Type')) ? Request::header('Device-Type') : null;
        
        $agrs = array('city' => $city, 'pass' => $order['pass'], 'coupon_flags' => $coupon_flags, 'device_type' => $device_type, 'order_data' => $order);
        $utilities = new Utilities();    
        $brandingData = $utilities->getPassBranding($agrs);
       
        if(!in_array(Request::header('Device-Type'), ["android", "ios"])){
            $success_template['web_message'] = $success['web_message'];
            $success_template['subline_1'] = "You can start booking from ".date('D, d M Y', strtotime($order['start_date']));

            $success_template['offer_success_msg'] = "";
            
            if(!empty($order['coupon_flags']['cashback_100_per']) && $order['coupon_flags']['cashback_100_per'] && !empty($order['amount']) && $order['amount'] > 0 && empty($order['coupon_flags']['no_cashback'])){
                $success_template['offer_success_msg'] = "Congratulations on receiving your instant cashback. You will receive full 100% cashback as FitCash in your Fitternity account on 1st December. Make the most of the cashback to upgrade your OnePass";
            }

            if(empty($brandingData['offer_success_msg'])){
                unset($success_template['offer_success_msg']);
            }else{
                $success_template['offer_success_msg'] = $brandingData['offer_success_msg'];
            }
            
        }

        if(in_array(Request::header('Device-Type'), ["android", "ios"])){
            $success_template['pass']['image1'] = 'http://b.fitn.in/passes/onepass-app.png';
            $success_template['pass']['image2'] = 'https://b.fitn.in/global/onepass/pass%20line%20design.png';
            // $success_template['info']['data'][0] = 'Book your sessions through the App';
            $success_template['info']['data'] = $success_template['info']['app_data'];
            // $utilities = new Utilities();
            // foreach($success_template['info']['data'] as &$item) {
            //     $item = $utilities->bullet()." ".$item;
            // }
            unset($success_template['info']['app_data']);
            
            $profile_completed = $this->utilities->checkOnepassProfileCompleted(null, $order['customer_id']);

            if(!empty($profile_completed)){
                unset($success_template['personalize']);
            }
            unset($success_template['subline_1']);

            if(!empty($order['coupon_flags']['cashback_100_per']) && $order['coupon_flags']['cashback_100_per'] && !empty($order['amount']) && $order['amount'] > 0 && empty($order['coupon_flags']['no_cashback'])){
                $success_template['subline'] .= 'Congratulations on receiving your instant cashback. Make the most of the cashback to upgrade your OnePass';
            }
            
            if(!empty($brandingData['offer_success_msg'])){
                $success_template['subline'] .= $brandingData['offer_success_msg'];
            }

        }

        return $success_template;

    }

    public function passSuccessRazorpay($order, $data){

        if(!empty($order['pass']['payment_gateway']) && $order['pass']['payment_gateway'] == 'razorpay' && empty($order['status']) && !empty($data['payment_id'])){
            
            $razorpay_service = new RazorpayService();
            $storePaymentDetails = $razorpay_service->storePaymentDetails($order['_id'], $data['payment_id']);
            if(!empty($storePaymentDetails)){
                $order->update(['status'=>'1']);
            }

        }
        return $order;
    }

    public function updateWallet($order){
        
        if(!empty($order['wallet_id']) && empty($order['status'])){
            
            // $wallet_update = Wallet::where('_id', $order['wallet_id'])->update(['status'=>'0']);
            
            // if(empty($wallet_update)){
             
            //     return ['status'=>400, 'message'=>'Something went wrong. Please contact customer support. (112)'];    
            
            // }

            // print_r($order->toArray());exit();

            $req = array(
                'customer_id'=>$order['customer_id'],
                'order_id'=>$order['order_id'],
                'amount'=>$order['cashback_detail']['amount_deducted_from_wallet'],
                'type'=>'DEBIT',
                'entry'=>'debit',
                'description'=> $this->utilities->getDescription($order),
                'order_type'=>'pass',
                'wallet_id'=>$order['wallet_id']
            );

            $trans_response = $this->utilities->walletTransactionNew($req, $order);

            if($trans_response['status'] == 400){
                return $trans_response;
            }

        }

        return ['status'=>200];

    }

    public function verifyOrderSignature($data){
        if(empty($data['key'])){
            $data['key'] = Config::get('app.razorpay.secret_key');
        }
        $expected_signature = hash_hmac('sha256', $data['body'], $data['key']);
        $response= ["status"=>false];
        Log::info("in verify signature:::::::::::::", [$data['signature'], $expected_signature]);
        if($data['signature'] == $expected_signature){
            $response['status'] = true;
        }
        return $response;
    }

    public function getPassOrderList($endDate, $customer_id, $offset, $limit, $type = null) {
        $passOrderList = Order::raw(function($collection) use ($endDate, $customer_id, $offset, $limit, $type){
            $aggregate = [
                ['$match' => [
                    'type' => 'pass', 'customer_id' => $customer_id, 'status' => '1'
                ]],
                ['$sort' => ['_id' => -1]],
                // ['$skip' => $offset],
                // ['$limit' => $limit],
                ['$project' => [
                    'order_id' => '$_id', 'status' => 1, 'end_date' => 1, 'type' => 1, 'customer_id' => 1,
                    'customer_name' => 1, 'customer_phone' => 1, 'total_credits_used' => 1, 'total_credits' => 1,
                    'total_premium_sessions' => 1, 'premium_sessions_used' => 1, 'amount' => 1,
                    'pass_type' => '$pass.type', 'unlimited_access' => '$pass.unlimited_access', 'duration' => '$pass.duration', 'duration_days' => '$pass.duration_days',
                    'duration_text' => '$pass.duration_text', 'pass_name' => '$pass.name', 'classes' => '$pass.classes', 'created_at' => 1
                ]]
            ];
            if ($type=='active') {
                $aggregate[0]['$match']['end_date'] = ['$gte' => $endDate];
            }
            else if($type=='inactive') {
                $aggregate[0]['$match']['end_date'] = ['$lt' => $endDate];
            }
            Log::info('aggregate: ', [$aggregate]);
            return $collection->aggregate($aggregate);
        });
        if(isset($passOrderList['result'])) {
            $passOrderList = $passOrderList['result'];
        }
        return $passOrderList;
    }

    public function orderPassHistory($customer_id, $offset = 0, $limit = 20){
		$customer_id		= 	$customer_id;	
		$offset 			=	intval($offset);
		$limit 				=	intval($limit);

        $endDate = new \MongoDate(strtotime('midnight', time()));

        // $activeOrders = $this->getPassOrderList($endDate, $customer_id, $offset, $limit, 'active');
        $activeOrders = $this->homePostPassPurchaseData($customer_id);
        $inactiveOrders = $this->getPassOrderList($endDate, $customer_id, $offset, $limit, 'inactive');

        $data = [];

        $orderList = [];
        // if(!empty($activeOrders)) {
        //     foreach($activeOrders as $active) {
        //         $_order = [
        //             'image' => 'https://b.fitn.in/passes/monthly_card.png',
        //             'header' => ucwords($active['duration_text']),
        //             'subheader' => (!empty($active['classes']))?strtoupper($active['classes']).' classes':null,
        //             'name' => (!empty($active['pass_name']))?strtoupper($active['pass_name']):null,
        //             'type' => (!empty($active['pass_type']))?strtoupper($active['pass_type']):null,
        //             'text' => 'Valid up to '.date('d M Y', $active['end_date']->sec),
        //             'remarks' => [
        //                 'header' => 'Things to keep in mind',
        //                 'data' => [
        //                     'You get sweatpoint credits to book whatever classes you want.',
        //                     'Download the app & get started',
        //                     'Book classes at any gym/studio near you',
        //                     'Sweatpoints vary by class',
        //                     'Not loving it? easy cancellation available'
        //                 ]
        //             ],
        //             'terms' => [
        //                 '_id' => strval($active['order_id']),
        //                 'header' => 'View all terms & condition',
        //                 'title' => 'Terms & Condition',
        //                 'url' => 'http://apistage.fitn.in/passtermscondition?type=subscribe',
        //                 'button_title' => 'Past Bookings'
        //             ]
        //         ];
        //         if(empty($active['unlimited_access']) || !$active['unlimited_access']) {
        //             $_order['header'] = (!empty($active['total_credits']))?strtoupper($active['total_credits']).' Sweat Points':null;
        //         }
        //         array_push($orderList, $_order);
        //     }
        // }
        // $data['active_pass'] = $orderList;
        $data['active_pass'] = [];
        if(!empty($activeOrders)) {
            $data['active_pass'] = [$activeOrders];
        }

        if(empty($inactiveOrders)) {
            $inactiveOrders = array();
        }

        $orderList = [];
        if(!empty($inactiveOrders)) {
            foreach($inactiveOrders as $inactive) {
                $_order = [
                    '_id' => strval($inactive['order_id']),
                    'header' => ucwords($inactive['duration_text']),
                    // 'subheader' => (!empty($inactive['classes']))?strtoupper($inactive['classes']).' classes':null,
                    'name' => (!empty($inactive['pass_name']))?strtoupper($inactive['pass_name']):null,
                    'pass_type' => (!empty($inactive['pass_type']))?$inactive['pass_type']:null,
                    'type' => (!empty($inactive['pass_type']) && strtolower($inactive['pass_type'])=='red')?'UNLIMITED USAGE':((strtolower($inactive['pass_type'])=='black')?'UNLIMITED VALIDITY':null),
                    'color' => '#f7a81e',
                    'tdate_label' => 'Transaction Date',
                    'tdate_value' => date('d M Y',  $inactive['created_at']->sec),
                    'expired_label' => 'Expired on',
                    'expired_value' => date('d M Y',  $inactive['end_date']->sec),
                    'price' => '₹'.$inactive['amount']
                ];
                // if(empty($active['unlimited_access']) || !$active['unlimited_access']) {
                //     $_order['header'] = (!empty($inactive['total_credits']))?strtoupper($inactive['total_credits']).' Sweat Points':null;
                //     $_order['subheader'] = (!empty($inactive['total_credits_used']))?strtoupper($inactive['total_credits_used']).' Sweat Points used':'0 Sweat Points used';
                // }
                array_push($orderList, $_order);
            }
        }

        $data['expired_pass'] = $orderList;

		$response = [
			'status' => 200,
			'data' => $data,
            'message' => 'Success'
        ];

		return $response;
    }
    
    public function checkUmlimitedPass($data){
        return (!empty($data['pass']['unlimited_access']) && $data['pass']['unlimited_access']) || (!empty($data['unlimited_access'] && $data['unlimited_access']));
    }

    public function passPurchaseCommunication($data){
        if(empty($data['status'])){
            return;
        }

        $sms = new CustomerSms();
        $mail = new CustomerMailer();
        $utilities = new Utilities();

        $pass_data = array(
            "amount" => $data['amount'],
            "amount_customer" => $data['amount_customer'],
            "customer_name" => $data['customer_name'],
            "customer_phone" => $data['customer_phone'],
            "customer_email" => $data['customer_email'],
            "customer_source" => $data['customer_source'],
            "corporate_source" => $data['corporate_source'],
            "type" => $data['type'],
            "customer_email" => $data['customer_email'],
            "customer_id" => $data['customer_id'],
            "pass_name" => $data['pass']['name'],
            "duration" => $data['pass']['duration'],
            "duration_text" => $data['pass']['duration_text'],
            "order_id" => $data['pass']['_id'],
            "payment_mode" => $data['payment_mode'],
            "end_date" => strtotime($data['end_date']),
            "limit" => 3,
            'city' => $data['customer_city'],
            'lat' => $data['customer_lat'],
            'lon' => $data['customer_lon'],
            'selected_region' => $data['customer_region'],
            'pass' => $data['pass'],
            'code' => $data['code'],
            'start_date' => strtotime($data['start_date']),
            'membership_order_id' => !empty($data['membership_order_id']) ? $data['membership_order_id'] : null,
            'coupon_flags' => !empty($data['coupon_flags']) ? $data['coupon_flags'] : null,
        );

        if(!empty($data['onepass_attachment_type'])){
            $pass_data['onepass_attachment_type'] = $data['onepass_attachment_type'];
        }
        if(empty($data['communication']['sms'])){
            $smsSent = $sms->sendPgOrderSms($pass_data);
            Log::info('sent smd',[$smsSent]);
        }
        else{
            $smsSent = $data['communication']['sms'];
        }

        if(empty($data['communication']['email'])){
            $pass_data['workout_search'] = $utilities->getWorkoutSessions($pass_data, 'passEmail');
            Log::info('in sending eamil to customer for passs', [$pass_data['workout_search'] ]);
            $emailSent = $mail->sendPgOrderMail($pass_data);
        }
        else{
            $emailSent = $data['communication']['email'];
        }

        if(!empty($data['diwali_mixed_reward'])){
            Log::info("diwali_mixed_reward");
            $hamper_data = $utilities->getVoucherDetail($pass_data);
            $mail->diwaliMixedReward($hamper_data);
            $sms->diwaliMixedReward($pass_data);
        }

        if(!empty($data['customer_city'])){
            $data['city'] = $data['customer_city'];
        }
        $city = !empty($data['city']) ? $data['city'] : null;
        $coupon_flags = !empty($data['coupon_flags']) ? $data['coupon_flags'] : null;
        $device_type = !empty(Request::header('Device-Type')) ? Request::header('Device-Type') : null;
        
        $agrs = array('city' => $city, 'pass' => $data['pass'], 'coupon_flags' => $coupon_flags, 'device_type' => $device_type, 'order_data' => $data);
        $utilities = new Utilities();    
        $brandingData = $utilities->getPassBranding($agrs);
        if(!empty($brandingData['msg_data'])){
            $customersms = new CustomerSms();
        
            $sms_data = [];
            $sms_data['customer_phone'] = $data['customer_phone'];
            $sms_data['message'] = $brandingData['msg_data'];
            
            $customersms->custom($sms_data);
        }

        return array(
            'sms' => $smsSent,
            'email' => $emailSent
        );
    }

    public function applyFitcash(&$data){
        if(empty($data['logged_in_customer_id'])){
            return;
        }

        if(empty($data['pass']['flags']['fitcash_applicable'])){
            return;
        }
        
        $wallet = Wallet::active()->where('customer_id', $data['logged_in_customer_id'])->where('balance', '>', 0)->where('order_type', 'pass')->first();
        
        if(!empty($wallet)){

            $percentage = !empty($data['pass']['flags']['fitcash_usage_limit']) ? $data['pass']['flags']['fitcash_usage_limit']/100 : 1;

            $fitcash_limit = ceil($data['pass']['price'] * $percentage);

            $fitcash_limit = $fitcash_limit < $data['amount'] ? $fitcash_limit : $data['amount'];

            $data['fitcash'] = $fitcash_limit > $wallet['balance'] ? $wallet['balance'] : $fitcash_limit;

            // $data['fitcash'] = $data['amount'] - $wallet['balance'] > 0 ? $wallet['balance'] : $data['amount']; 
            $data['amount'] = $data['amount'] - $data['fitcash'] > 0 ? ($data['amount'] - $data['fitcash']) : 0;
            $data['wallet_id'] = $wallet['_id'];
            $data['cashback_detail']['amount_deducted_from_wallet'] = $data['fitcash'];
            // $data['rp_description'] = $data['fitcash'].' Rs Fitcash Applied.';
        }
    
    }

    public function getDateDifference($expiryDate) {
        $expiryDate = strtotime($expiryDate);
        $diff = ($expiryDate)-(strtotime('midnight', time()));
        $diffDays = ($diff/(60*60*24));
        return ($diffDays>=1)?intval($diffDays):0;
    }

    public function homePostPassPurchaseData($customerId, $showTnC = true) {
        Order::$withoutAppends = true;
        // $passOrder = Order::active()->where('customer_id', $customerId)->where('type', 'pass')->orderBy('_id', 'desc')->first();
        $passOrder = $this->getPassOrder($customerId);
        if(empty($passOrder)){
            return null;
        }
        Order::$withoutAppends = true;

        $pastBookings = Booktrial::where('pass_order_id', $passOrder->_id)->where('going_status_txt', '!=', 'cancel')->where('schedule_date_time', '<', new \MongoDate())->count();
        $upcomingBookings = Booktrial::where('pass_order_id', $passOrder->_id)->where('going_status_txt', '!=', 'cancel')->where('schedule_date_time', '>=', new \MongoDate())->count();
        $totalBookings = $pastBookings + $upcomingBookings;

        $passExpired = false;

        $pass_type_template_key = $passOrder['pass']['pass_type'];
        if($passOrder['pass']['pass_type']=='hybrid'){
            $pass_type_template_key = $passOrder['pass']['branding'];
        }
        $homePassData = Config::get('pass.home.after_purchase.'.$pass_type_template_key);
        $tnc = Config::get('pass.terms.'.$pass_type_template_key)[0];
        $homePassData['pass_order_id'] = $passOrder['_id'];
        $startDateDiff = $this->getDateDifference($passOrder['start_date']);
        $notStarted = false;
        if(!empty($startDateDiff) && $startDateDiff>0) {
            $notStarted = true;
        }
        if($passOrder['pass']['pass_type']=='black') {
            
            // $homePassData = $homePassData[$passOrder['pass']['pass_type']];

            $totalSessions = $passOrder['pass']['duration'];
            if($totalSessions < $totalBookings) {
                $passExpired = true;
                $usageLeft = 0;
            }
            else {
                $usageLeft =  $totalSessions - $totalBookings;
            }
            
            $this->purchasedPassFormat($homePassData, $passOrder['pass']['pass_type'], $passExpired, $passOrder, $notStarted, $usageLeft, $upcomingBookings, $pastBookings, $totalSessions);
        }
        else if($passOrder['pass']['pass_type']=='red') {
            $totalDuration = $passOrder['pass']['duration'];
            // $expiryDate = date("Y-m-d H:i:s", strtotime('+'.$totalDuration.' days', time()));
            $expiryDate = date("Y-m-d H:i:s", strtotime($passOrder['end_date']));
            $usageLeft = $this->getDateDifference($expiryDate);
            if(empty($usageLeft) || $usageLeft<0) {
                $passExpired = true;
            }
            
            $this->purchasedPassFormat($homePassData, $passOrder['pass']['pass_type'], $passExpired, $passOrder, $notStarted, $usageLeft, $upcomingBookings, $pastBookings, 0);
        }
        else if($passOrder['pass']['pass_type']=='hybrid') {
            $totalDuration = $passOrder['pass']['duration'];
            // $expiryDate = date("Y-m-d H:i:s", strtotime('+'.$totalDuration.' days', time()));
            $expiryDate = date("Y-m-d H:i:s", strtotime($passOrder['end_date']));
            $usageLeft = $this->getDateDifference($expiryDate);
            if(empty($usageLeft) || $usageLeft<0) {
                $passExpired = true;
            }

            $homePassData['header'] = $passOrder['pass']['total_sessions'].' SESSIONS';

            $this->purchasedPassFormat($homePassData, $passOrder['pass']['pass_type'], $passExpired, $passOrder, $notStarted, $usageLeft, $upcomingBookings, $pastBookings, 0);
        }
        $homePassData['pass_expired'] = $passExpired;
        if(!$showTnC && !empty($homePassData['terms'])) {
            unset($homePassData['terms']);
            unset($homePassData['tnc_text']);
        }
        else {
            $homePassData['terms'] = "<h2>Terms and Conditions</h2>".$tnc;
        }
        
        return $homePassData;
    }

    function getBookingDetails($data){

        $pass_type_ori = $data['pass']['pass_type'];
        $pass_type = ucwords($data['pass']['pass_type']);
        $pass_duration = $data['pass']['duration'];
        $duration_field = $data['pass']['pass_type'] == 'red' ? 'Duration' : 'No of Sessions';
        

        $resp = [
            [
               'field' => 'PASS TYPE',
               'value' => ucwords($data['pass']['pass_type']),
            ],
            [
               'field' => $duration_field,
               'value' => $data['pass']['duration_text'],
            ],
            [
               'field' => 'START DATE',
               'value' => date('l, j M Y',strtotime($data['start_date'])),
            ]
        ];

        if(!empty($data['customer_city'])){
            $data['city'] = $data['customer_city'];
        }
        $city = !empty($data['city']) ? $data['city'] : null;
        
        $agrs1 = array('city' => $city, 'pass' => $data['pass']);
		$brandingData = $this->utilities->getPassBranding($agrs1);
		if(!empty($brandingData['purchase_summary_value'])){
			$resp[] = [
                'field' => '',
                'value' => $brandingData['purchase_summary_value'],
            ];
		}

        return $resp;
    }

    function getPaymentDetails($data,$payment_mode_type){

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
            'value' => 'Rs. '.$data['amount']
        );

        $amount_final = $data['amount'];

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

            if((isset($data['coupon_discount_amount']) && $data['coupon_discount_amount'] > 0) || (!empty($data['coupon_flags']['cashback_100_per'])) || (!empty($data['coupon_flags']['extension_percent'])) || (!empty($data['coupon_flags']['vk_bag_and_box_reward']))){

                if($payment_mode_type != 'pay_later'){

                    if(!empty($data['coupon_flags']['extension_percent'])){
                        $amount_summary[] = array(
                            'field' => 'Coupon Discount',
                            'value' => !empty($data['coupon_discount_amount']) ? '-Rs. '.$data['coupon_discount_amount'] : "Extension"
                        );
                    }else if(!empty($data['coupon_flags']['vk_bag_and_box_reward'])){
                            $amount_summary[] = array(
                                'field' => 'Coupon Discount',
                                'value' => !empty($data['coupon_discount_amount']) ? '-Rs. '.$data['coupon_discount_amount'] : "Reward"
                            );
                    }else{
                        $amount_summary[] = array(
                            'field' => 'Coupon Discount',
                            'value' => !empty($data['coupon_discount_amount']) ? '-Rs. '.$data['coupon_discount_amount'] : "100% Cashback"
                        );
                    }
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

        return $payment_details;

    }

    public function passTermsAndCondition(){
        $input = Input::all();
        $passTerms = \Config::get('pass.terms');
        $utilities = new Utilities();
        $agrs = array('city' => 'mumbai');
        $brandingData = $utilities->getPassBranding($agrs);

        if(!empty($input['type']) && $input['type']=='unlimited'){
            $passTerms = $passTerms['red'];

            if(!empty($brandingData['tnc_red'])){
                $passTerms[0] = $brandingData['tnc_red'].$passTerms[0];
            }
        }
        else if(!empty($input['type']) && $input['type']=='subscribe'){
            $passTerms = $passTerms['black'];
            
            if(!empty($brandingData['tnc_black'])){
                $passTerms[0] = $brandingData['tnc_black'].$passTerms[0];
            }
        }
        else{
            $passTerms = $passTerms['default'];

            if(!empty($brandingData['tnc_red'])){
                $passTerms[0] = $brandingData['tnc_red'].$passTerms[0];
            }
        }

        return array("status"=> 200, "data"=> $passTerms[0], "msg"=> "success");
    }

    public function localPassRatecards($type, $city_name){
        return $passList = Pass::active()
        ->where('pass_category', 'local')
        ->where('local_cities.city_name', $city_name)
        ->where('pass_type', $type)
        ->get(
            [
                'pass_id',
                'duration_type',
                'type',
                'duration_text',
                'name',
                'payment_gateway',
                'premium_sessions',
                'cashback',
                'pass_category',
                'price',
                'max_retail_price',
                'pass_type'
            ]
        );
    }

    public function passTabPostPassPurchaseData($customerId, $city, $showTnC = true, $coordinate, $customerData=null) {
        Log::info('passTabPostPassPurchaseData');

        if(!empty($customerData)){
            $customerData = Customer::where('_id', $customerId)->first();
        }
        
        $profile = array();
        if(!empty($customerData)){

            $interest = '';
            if(!empty($customerData['onepass']['interests']) && is_array($customerData['onepass']['interests'])){
                $interests_name = array_column(
                    $this->utilities->personlizedServiceCategoryList($customerData['onepass']['interests']), 
                    'name'
                );
                $interest = implode(', ', $interests_name);
            }

            $profile = [
                'button_text' => "EDIT PREFERENCES"
            ];

            if(!empty($customerData['onepass']['gender']) || !empty($customerData['gender'])){
                !empty($customerData['onepass']['gender']) ? $profile['gender'] = $customerData['onepass']['gender'] : (!empty($customerData['gender']) && $customerData['gender'] != ' ' ? $profile['gender'] = $customerData['gender']: null);
            }

            if(!empty($customerData['onepass']['photo']['url'])){
                $profile['image'] = $customerData['onepass']['photo']['url'];
            }
            else{
                $profile['image'] = Config::get('pass.customer_no_image');
            }

            if(!empty($interest) && $interest !=''){
                $profile['text'] = $interest;
            }

            if(!empty($customerData['name'])){
                $profile['name'] = $customerData['name'];
            }
        }

        $passOrder = $this->getPassOrder($customerId);
        if(empty($passOrder)){
            return null;
        }
        
        $pastBookings = Booktrial::where('pass_order_id', $passOrder->_id)->where('going_status_txt', '!=', 'cancel')->where('schedule_date_time', '<', new \MongoDate())->count();
        $upcomingBookings = Booktrial::where('pass_order_id', $passOrder->_id)->where('going_status_txt', '!=', 'cancel')->where('schedule_date_time', '>=', new \MongoDate())->count();
        $totalBookings = $pastBookings + $upcomingBookings;

        $passExpired = false;

        $tabPassData = Config::get('pass.after_purchase_tab.'.$passOrder['pass']['pass_type']);
        $tnc = Config::get('pass.terms.'.$passOrder['pass']['pass_type'])[0];
        $tabPassData['pass_order_id'] = $passOrder['_id'];
        $startDateDiff = $this->getDateDifference($passOrder['start_date']);
        $notStarted = false;
        if(!empty($startDateDiff) && $startDateDiff>0) {
            $notStarted = true;
        }
        if($passOrder['pass']['pass_type']=='black') {
        
            $totalSessions = $passOrder['pass']['duration'];
            if($totalSessions <= $totalBookings) {
                $passExpired = true;
                $usageLeft = 0;
            }
            else {
                $usageLeft =  $totalSessions - $totalBookings;
            }
            $this->purchasedPassFormat($tabPassData, 'black', $passExpired, $passOrder, $notStarted, $usageLeft, $upcomingBookings, $pastBookings, $totalSessions);
        }
        else if($passOrder['pass']['pass_type']=='red') {
            $totalDuration = $passOrder['pass']['duration'];
            $expiryDate = date("Y-m-d H:i:s", strtotime($passOrder['end_date']));
            $usageLeft = $this->getDateDifference($expiryDate);
            if(empty($usageLeft) || $usageLeft<0) {
                $passExpired = true;
            }

            $this->purchasedPassFormat($tabPassData, 'red', $passExpired, $passOrder, $notStarted, $usageLeft, $upcomingBookings, $pastBookings, 0);
        }
        $tabPassData['pass_expired'] = $passExpired;

        // if(!$showTnC && !empty($tabPassData['terms'])) {
        //     unset($tabPassData['terms']);
        //     unset($tabPassData['tnc_text']);
        // }
        // else {
        //     $tabPassData['terms'] = "<h2>Terms and Conditions</h2>".$tnc;
        // }

        $upcomig = $this->upcomingPassBooking($customerData, null, null, 'pass_tab');

        $res = array();
        $search_results = [];
        if(!empty($customerData['onepass']['home_city']) && !empty($customerData['onepass']['home_lat']) && !empty($customerData['onepass']['home_lon'])){
            $pps_near_home= $this->workoutSessionNearMe(
                $customerData['onepass']['home_city'], 
                [
                    'lat'=>$customerData['onepass']['home_lat'], 
                    'lon'=> $customerData['onepass']['home_lon']
                ]
            );

            $pps_near_home['isViewAllHidden'] = true;
            if(!empty($pps_near_home['data']) && !empty(count($pps_near_home['data']))){
                unset($pps_near_home['header']);
                $pps_near_home['subheader'] = "Top Recommendation Near Your Home";
                array_push($search_results, $pps_near_home);
            }
        }

        if(!empty($customerData['onepass']['work_city'])  && !empty($customerData['onepass']['work_lat']) && !empty($customerData['onepass']['work_lon'])){
            $pps_near_work = $this->workoutSessionNearMe(
                $customerData['onepass']['work_city'], 
                [
                    'lat'=>$customerData['onepass']['work_lat'], 
                    'lon'=> $customerData['onepass']['work_lon']
                ]
            );

            $pps_near_work['isViewAllHidden'] = true;
            if(!empty($pps_near_work['data']) && !empty(count($pps_near_work['data']))){
                $pps_near_work['subheader'] = "Top Recommendations Near Your Office";
                unset($pps_near_work['header']);
                array_push($search_results, $pps_near_work);
            }
        }

        if(empty($search_results)){

            $near_me= $this->workoutSessionNearMe($city, $coordinate);

            if(!empty($near_me['data']) && !empty(count($near_me['data']))){
                $near_me['subheader'] = $near_me['header'];
                unset($near_me['header']);
                array_push($search_results, $near_me);
            }
            
        }
        
        $recommended = [ 
            'header' => 'Recommended for You',
            'data' => $search_results
        ];

        $res['recommended'] = $recommended;
        $headerView = Config::get('pass.before_purchase_tab.headerview');
        unset($headerView['header_sub_text']);
        $res['headerview'] = $headerView;
        $res['profile'] = $profile;
        $res['pass'] = $tabPassData;
        $res['upcoming'] = $upcomig;
        $res['footer'] = Config::get('pass.cancel_onepass');

        if(empty($res['upcoming'])){
            unset($res['upcoming']);
            $res['booknow'] = Config::get('pass.book_now');
        }
        else if(!empty($res['upcoming']['session_data'])){
            $res['session_activated'] = $res['upcoming'];
            unset($res['upcoming']);
        }
        return $res;
    }

    public function upcomingPassBooking($customer, $data=null, $customer_id=null, $from=null){
        
        if(empty($customer)){
            $customer = Customer::where('_id', $customer_id)->first();
        }
        else{
            $customer_id = $customer['_id'];
        }
        
        if(empty($data))
        {
            $data = \Booktrial::where('customer_id', '=', $customer_id)
            ->where('going_status_txt','!=','cancel')
            ->where('post_trial_status', '!=', 'no show')
            ->where('booktrial_type','auto')
            ->where('pass_order_id', 'exists', true)
            ->where(function($query){
                $query->orWhere('schedule_date_time','>=',new \DateTime())
                ->orWhere(function($query){
                    $query	->where('post_trial_status_updated_by_unlocksession_date', '>', new \DateTime(date('Y-m-d H:i:s', strtotime('-2 hour'))))
                            ->where('going_status_txt','!=','cancel')
                            ->where('post_trial_status', 'attended')
                            ->where('post_trial_status_updated_by_unlocksession', 'exists', true);
                })
                ->orWhere(function($query){
                    $query	->where('schedule_date_time', '>', new \DateTime(date('Y-m-d H:i:s', strtotime('-3 hour'))))
                            ->where('going_status_txt','!=','cancel')
                            ->whereIn('post_trial_status', [null, '', 'unavailable']);
                });
            })
            ->orderBy('schedule_date_time', 'asc')
            ->select('finder','finder_name','service_name', 'schedule_date', 'schedule_slot_start_time','finder_address','finder_poc_for_customer_name','finder_poc_for_customer_no','finder_lat','finder_lon','finder_id','schedule_date_time','what_i_should_carry','what_i_should_expect','code', 'payment_done', 'type', 'order_id', 'post_trial_status', 'amount_finder', 'kiosk_block_shown', 'has_reviewed', 'skip_review','amount','studio_extended_validity_order_id','studio_block_shown','pass_order_id','finder_location', 'service_category', 'post_trial_initail_status', 'post_trial_status_updated_by_unlocksession', 'finder_category_id', 'servicecategory_id')
            ->first();
        }
        
        if(empty($data)){
            return;
        }

        Log::info('upcoming:::', []);
        $upcoming = Config::get('pass.upcoming_booking');
        
        $upcoming['_id'] = $data['_id'];

        $icons = $this->utilities->getServiceCategoriesIcon()[0];
        $icon = !empty($icons[$data['service_category']]) ? $icons[$data['service_category']]['icon']: '';

        $upcoming['workout'] = array(
            'icon' => $icon,
            'header' => ucwords($data['service_name']),
            'text' => date('D, d M - h:i A', strtotime($data['schedule_date_time'])),
        ); 
        $upcoming['finder'] = array(
            'header' => $data['finder_name'],
            'text' => $data['finder_location'],
            'address'=> $data['finder_address']
        );

        $upcoming['direction'] = "Get Direction";
        $upcoming['lat'] = $data['finder_lat'];
        $upcoming['lon'] = $data['finder_lon'];

        $vendor_type = $data['finder_category_id'] == 5 ? 'gym' : 'studio';
        $replace_by_value = ['vendor_type' => $vendor_type, 'trial_id'=> $data['_id'], 'finder_name'=> ucwords($data['finder_name'])];

        foreach($upcoming['footer'] as &$value){
            $value = strtr($value, $replace_by_value);
        }
        $upcoming['remarks'] = strtr($upcoming['remarks'] , $replace_by_value);

        $minutes30 = 60*30;
        $hour2 = 60 * 60 *2;
        $scheduleDateTime 				=	\Carbon::parse($data['schedule_date_time']);
        $scheduleDateTime = strtotime($scheduleDateTime);

        $time_diff = $scheduleDateTime - strtotime('now');

        if(!empty($data['post_trial_initail_status']) && strtolower($data['post_trial_initail_status']) == 'interested'  && !empty($data['post_trial_status']) && strtolower($data['post_trial_status']) == 'attended'){

            $upcoming['header'] = "Session Activated";

            if(!empty($this->device_type) && $this->device_type=='ios' && !empty($this->app_version) && $this->app_version=='5.2.4'){
                $upcoming['header'] ="SESSION ACTIVATED\n\n".$upcoming['remarks'];
            }

            unset($upcoming['footer']);
            unset($upcoming['direction']);
            unset($upcoming['lat']);
            unset($upcoming['lon']);

            $upcoming['footer'] =[
                'subscription_description' => 'Session id : '.$data['code']
            ];
            
            if(!empty($customer->onepass['photo']['url'])){
                $upcoming['user_photo'] = $customer->onepass['photo']['url'];
            }
            else{
                $upcoming['user_photo']  = Config::get('pass.customer_no_image');
            }

            $upcoming['time_diff'] = -1;
            if(!empty($from) && $from =='pass_tab'){
                $upcoming_config = Config::get('pass.upcoming_config_booking');
                $upcoming['header_text'] = $upcoming['header'];//"Session Activated";
                unset($upcoming['header']);
                $upcoming_config['title'] = ucwords($data['service_name']);
                $upcoming_config['text'] = ucwords($data['finder_name']);
                $upcoming_config['session_data'] = $upcoming;
                $upcoming = $upcoming_config;
            }
        }
        else{

            $currentDateTime =	\Carbon\Carbon::now();
            $hour1 = 60*60*1;
            $minutes15 = 60*15;
            $time_diff = $scheduleDateTime - strtotime($currentDateTime);
            if(!empty($data['servicecategory_id']) && (($data['servicecategory_id'] == 65 && $time_diff < $minutes15) || ($data['servicecategory_id'] != 65 && $time_diff < $hour1))){
                unset($upcoming['footer']['cancel_text']);
                unset($upcoming['footer']['cancel_url']);
            }
            unset($upcoming['remarks']);
        }

        return $upcoming;
    }

    public function workoutSessionNearMe($city, $coordinate){

        $lat = $coordinate['lat'];
        $lon = $coordinate['lon'];
        $near_by_workout_request = [
            "offset" => 0,
            "limit" => 9,
            "radius" => "2km",
            "category"=>"",
            "lat"=>$lat,
            "lon"=>$lon,
            "city"=>!empty($city) ? strtolower($city): null,
            "onepass_available" => true,
            "pass" => true,
            "keys"=>[
                "average_rating",
                "contact",
                "coverimage",
                "location",
                "multiaddress",
                "slug",
                "name",
                "id",
                "categorytags",
                "category"
            ]
        ];

        return $this->utilities->getWorkoutSessions($near_by_workout_request, 'customerHome');
    }

    public function giveCashbackOnOrderSuccess($order){
        try{

            $utilities = new Utilities();
            if(!empty($order['customer_city'])){
                $order['city'] = $order['customer_city'];
            }
            $city = !empty($order['city']) ? $order['city'] : null;
            $coupon_flags = !empty($order['coupon_flags']) ? $order['coupon_flags'] : null;
            
            $agrs = array('city' => $city, 'pass' => $order['pass'], 'coupon_flags' => $coupon_flags);
            $brandingData = $utilities->getPassBranding($agrs);
            
            if(!empty($order['coupon_flags']['cashback_100_per']) && $order['coupon_flags']['cashback_100_per'] && !empty($order['amount']) && $order['amount'] > 0 && empty($order['coupon_flags']['no_cashback'])){

                $discount_per = $order['coupon_flags']['cashback_100_per'];

                $amount_paid = $order['amount'];

                $cashback_amount = 0;
                if($amount_paid != 0){
                    $cashback_amount = round(($amount_paid * $discount_per) / 100);
                }

                if(!empty($coupon_flags['cashback_max'])){
                    if($coupon_flags['cashback_max'] != -1){
                        $cashback_amount = ($cashback_amount > $coupon_flags['cashback_max']) ? $coupon_flags['cashback_max'] : $cashback_amount;
                    }
                }else{
                    if($cashback_amount > 2000){
                        $cashback_amount = 2000;
                    }
                }

                $cashback_amount_after_gst = $cashback_amount;
                // if($cashback_amount != 0){
                //     $cashback_amount_after_gst = round(($cashback_amount * 82) / 100);
                // }

                $utilities = new Utilities();
                
                if($cashback_amount > 0){

                    $walletData = array(
                        "order_id"=>$order['_id'],
                        "customer_id"=> !empty($order['logged_in_customer_id']) ? intval($order['logged_in_customer_id']) : intval($order['customer_id']),
                        "amount"=> intval($cashback_amount_after_gst),
                        "amount_fitcash" => 0,
                        "amount_fitcash_plus" => intval($cashback_amount_after_gst),
                        "type"=>"CASHBACK",
                        "entry"=>"credit",
                        "order_type"=>["pass"],
                        "description"=> $discount_per."% Cashback on buying OnePass , Expires On : ".date('d-m-Y',time()+(86400*30)),
                        "validity"=>time()+(86400*30),
                        "duplicate_allowed" => true,
                    );

                    $walletTransaction = $utilities->walletTransaction($walletData);
                    
                    if(isset($walletTransaction['status']) && $walletTransaction['status'] == 200){
                        
                        $customersms = new CustomerSms();

                        $sms_data = [];

                        $sms_data['customer_phone'] = $order['customer_phone'];
                        $sms_data['amount'] = $cashback_amount_after_gst;

                        $customersms->onePass100PerCashback($sms_data);
                    }
                }
            }

            if(!empty($order['pass']['flags']['cashback']) && empty($order['coupon_flags']['no_cashback'])){
                $cashback_amount = $order['pass']['flags']['cashback'];

                $cashback_amount_after_gst = $cashback_amount;
                // if($cashback_amount != 0){
                //     $cashback_amount_after_gst = round(($cashback_amount * 82) / 100);
                // }

                $utilities = new Utilities();
                
                if($cashback_amount > 0){

                    $walletData = array(
                        "order_id"=>$order['_id'],
                        "customer_id"=> !empty($order['logged_in_customer_id']) ? intval($order['logged_in_customer_id']) : intval($order['customer_id']),
                        "amount"=> intval($cashback_amount),
                        "amount_fitcash" => 0,
                        "amount_fitcash_plus" => intval($cashback_amount),
                        "type"=>"CASHBACK",
                        "entry"=>"credit",
                        "order_type"=>["pass"],
                        "description"=> "INR ".$cashback_amount." Cashback on buying OnePass , Expires On : ".
                        date('Y-m-d', strtotime("+6 months", strtotime($order['created_at']))),
                        "validity"=> strtotime("+6 months", strtotime($order['created_at'])),
                        "duplicate_allowed" => true,
                    );

                    $walletTransaction = $utilities->walletTransaction($walletData);
                    
                    if(isset($walletTransaction['status']) && $walletTransaction['status'] == 200){
                        Order::where('_id', $order['_id'])->update(['cashback_added' => true]);
                    }

                    if(!empty($brandingData['msg_data'])){
                        $customersms = new CustomerSms();
                    
                        $sms_data = [];
                        $sms_data['customer_phone'] = $order['customer_phone'];
                        $sms_data['message'] = $brandingData['msg_data'];
                        
                        $customersms->custom($sms_data);
                    }
                    
                }
            }
        }catch (Exception $e) {
            Log::info('Error : '.$e->getMessage());
        }
    }

    public function purchasedPassFormat(&$homePassData, $type, $passExpired, $passOrder, $notStarted, $usageLeft, $upcomingBookings, $pastBookings, $totalSessions){

        Log::info('purchase pass format::::::::', [$type, $passExpired]);
        $subheader = strtoupper(trim($passOrder['pass']['duration_text']));

        if($type =='black'){
            $subheader = $totalSessions.' SESSIONS';
        }

        if($type=='red' && !empty($passOrder['pass']['lite'])){
            $homePassData['header'] =  'LITE';
        }

        $homePassData['name'] = strtoupper(trim($passOrder['customer_name']));
        $homePassData['subheader'] = $subheader;
        $homePassData['left_value'] = strval($upcomingBookings);
        $homePassData['right_value'] = strval($pastBookings);
        if(!$passExpired) {
            $lastOrder = Booktrial::where('pass_order_id', $passOrder->_id)->where('going_status', '!=', 'cancel')->orderBy('_id', 'desc')->first();
            if(!empty($lastOrder)) {
                $homePassData['footer']['section1']['button1_subtext'] = ucwords($lastOrder->finder_name);
                $homePassData['footer']['section1']['no_last_order'] = false;
                $homePassData['footer']['section1']['service_slug'] = $lastOrder->service_slug;
                $homePassData['footer']['section1']['finder_slug'] = $lastOrder->finder_slug;
            }
            else {
                unset($homePassData['footer']['section1']['button1_text']);
                unset($homePassData['footer']['section1']['button2_text']);
                if($notStarted) {
                    unset($homePassData['top_right_button_text']);
                    $homePassData['left_text'] = "Booking starts from:";
                    unset($homePassData['left_value']);
                    $homePassData['right_text'] = date('d M Y', strtotime($passOrder['start_date']));
                    unset($homePassData['right_value']);
                }
            }
            if(!empty($usageLeft) && $usageLeft>5) {
                // if(!Config::get('app.debug')) {
                    unset($homePassData['footer']['section2']);
                    unset($homePassData['footer']['section3']);
                // }
            }
            else {
                // $homePassData['footer'] = $homePassData['footer']['ending'];
                $remaining_text =(( $type =='black') ? ' sessions': ' days');
                $homePassData['footer']['section2']['text'] = strtr($homePassData['footer']['section2']['text'], ['remaining_text' => $usageLeft.$remaining_text]);
                unset($homePassData['footer']['section3']);
            }
        }
        else {
            unset($homePassData['footer']['section1']['button1_subtext']);
            unset($homePassData['footer']['section1']['button1_text']);
            unset($homePassData['top_right_button_text']);
            unset($homePassData['footer']['section1']['no_last_order']);
            $homePassData['footer']['section2'] = $homePassData['footer']['section3'];
            unset($homePassData['footer']['section3']);
            if($type =='black'){
                unset($homePassData['footer']['section1']);
            }
        }
    }

    public function addMonthlyBookingCounter(&$data){

        if($data['pass']['pass_type']== 'hybrid'){
            $actual_month_count = ($data['pass']['duration']/30);
            $months_count = ceil($actual_month_count);
            $monthly_total_sessions_used= [];
            $start_date = strtotime(date('Y-m-d H:i:s', $data['start_date']->sec));
            //$end_date = strtotime('+30 days', $start_date);
            if($actual_month_count<1) {
                $end_date = $data['end_date']->sec;
            }
            else {
                $end_date = strtotime('+30 days', $start_date);
            }
            for($i=0; $i< $months_count; $i++){

                
                $monthly_total_sessions_used[] = [
                    'month' => $i+1,
                    'start_date' => new \MongoDate($start_date),
                    'end_date' => new \MongoDate($end_date),
                    'count' => 0
                ];
                $start_date = $end_date;
                $end_date = strtotime('+30 days', $start_date);
            }
            $data['monthly_total_sessions_used'] = $monthly_total_sessions_used;
        }
    }

    public function addRewardInfo($data = null){
        try{
            $rewardinfo = array();
            if(!empty($data['pass'])){
                $pass = $data['pass'];

                // if((!empty($pass['pass_type']) && $pass['pass_type'] == 'black' && !empty($pass['duration']) && in_array($pass['duration'], [60,100]))){

                //     if(empty($data['membership_order_id'])){
                //         $rewardinfo['mufm_kit_reward'] = true;
                //         $rewardinfo['reward_ids'] = [79];
                //     }
                // }

            }
            return $rewardinfo;

        }catch (Exception $e) {
            Log::info('Error : '.$e->getMessage());
        }
    }

    public function preProcessCityData($data){
        if(!empty($data['city']) && !is_array($data['city'])){

            $cityData = get_masterdata_from_cache(getmy_city($data['city']));

        }elseif (!empty($data['customer_city']) && !is_array($data['customer_city'])) {

            $cityData = get_masterdata_from_cache(getmy_city($data['customer_city']));

        }

        if(!empty($cityData)){
            $data['finder_city'] = $cityData['slug'];
            $data['city_id'] = $cityData['_id'];
            $data['city_name'] = $cityData['slug'];
            $data['city_slug'] = $cityData['slug'];
        }

        return $data;
    }

    public function formatPassListingWithOnePassLite(&$response, $include_onepass_lite_web, $city){

        if(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=> "5.33"])){
            $this->formatOfferingOnePassLite('app_passes', 0, $response);
            $this->formatOfferingOnePassLite('app_passes', 1, $response);
            
            $response['faq'] = $response['faq_v_2'];

            unset($response['app_passes'][0]['remarks']['text']);
            unset($response['app_passes'][0]['remarks']['title']);
            unset($response['app_passes'][0]['remarks']['url']);
            unset($response['app_passes'][1]['remarks']['text']);
            unset($response['app_passes'][1]['remarks']['title']);
            unset($response['app_passes'][1]['remarks']['url']);
            if(!empty($response['app_passes'][0]['offerings'][1]['onepass_lite']) && !empty($response['app_passes'][0]['offerings'][1]['border_text'])){
                unset($response['app_passes'][0]['offerings'][1]['border_text']);
            }

            if(!empty($response['app_passes'][0]['offerings'][0]['text_lite'])){
                $response['app_passes'][0]['offerings'][0]['text'] = $response['app_passes'][0]['offerings'][0]['text_lite'];
            }

            if(!empty($response['app_passes'][0]['offerings'][1]['text_lite'])){
                $response['app_passes'][0]['offerings'][1]['text'] = $response['app_passes'][0]['offerings'][1]['text_lite'];
            }

            if(!empty($response['app_passes'][1]['offerings'][0]['text_lite'])){
                $response['app_passes'][1]['offerings'][0]['text'] = $response['app_passes'][1]['offerings'][0]['text_lite'];
            }

            empty($city) ? $city= 'mumbai': null;
            $response['app_passes'][0]['tnc']['url'] = strtr($response['app_passes'][0]['tnc']['url'], ['city_name' => $city]);
            $response['app_passes'][1]['tnc']['url'] = strtr($response['app_passes'][1]['tnc']['url'], ['city_name' => $city]);
        }
        else if(!empty($include_onepass_lite_web)){
            $this->formatOfferingOnePassLite('passes', 0, $response, true);
            $this->formatOfferingOnePassLite('passes', 1, $response, true);
        }
        else {
            unset($response['app_passes'][0]['about_pass']);
            unset($response['app_passes'][1]['about_pass']);
            unset($response['app_passes'][0]['tnc']);
            unset($response['app_passes'][1]['tnc']);
        }
        unset($response['faq_v_2']);

        if(!empty($response['app_passes'][0]['offerings'][0]['text_lite'])){
            unset($response['app_passes'][0]['offerings'][0]['text_lite']);
        }

        if(!empty($response['app_passes'][0]['offerings'][1]['text_lite'])){
            unset($response['app_passes'][0]['offerings'][1]['text_lite']);
        }

        if(!empty($response['app_passes'][1]['offerings'][0]['text_lite'])){
            unset($response['app_passes'][1]['offerings'][0]['text_lite']);
        }
    }

    public function formatOfferingOnePassLite($key, $index, &$response, $web=null){

        if(!empty($response[$key][$index]['offerings_lite']['ratecards'])){
            if(empty($web)){
                $response[$key][$index]['offerings'] = [
                    $response[$key][$index]['offerings'],
                    $response[$key][$index]['offerings_lite']
                ];
            }
            else{
                $response[$key][$index]['offerings'] = [
                    $response[$key][$index]['offerings_lite'],
                    $response[$key][$index]['offerings']
                ];
            }
        }else {
            $response[$key][$index]['offerings'] = [
                $response[$key][$index]['offerings']
            ];
        }

    }

    public function formatPassOffering(&$response, $pass, $passDetails, $device, $index){

        if(!empty($device) && in_array($device, ['android', 'ios'])) {
            if(checkAppVersionFromHeader(['ios'=>'5.2.90', 'android'=> "5.33"])){
                if(!empty($pass['lite'])){
                    $response['app_passes'][$index]['offerings_lite']['ratecards'][] = $passDetails;
                }
                else {
                    $response['app_passes'][$index]['offerings']['ratecards'][] = $passDetails;
                }

                if(!empty($pass['pass_type'])){
                    unset($response['app_passes'][$index]['offerings']['button_text']);
                }
            }
            else {
                $response['app_passes'][$index]['offerings']['ratecards'][] = $passDetails;
            }
        }
        else {
            if(!empty($pass['lite'])){
                $response['onepass_lite_available'] = true;
                $response['passes'][$index]['offerings_lite']['ratecards'][] = $passDetails;
            }
            else {
                $response['passes'][$index]['offerings']['ratecards'][] = $passDetails;
            }
        }

    }

    public function listValidCouponsOfOnePass($ratecard_type, $pass_type='red'){
        
        $resp=[

            "header"=>"Offers For You",
            "text" => "View Offers",
            "options"=>[]
        ];
           
        // return $resp;
        
        $customer_email=null;
        $customer_id=null;

        $jwt_token = Request::header('Authorization');
        $device = Request::header('Device-Type');
        $app_version = Request::header('App-Version');
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = customerTokenDecode($jwt_token);
            !empty($decoded->customer->_id) ? $customer_id = (int)$decoded->customer->_id : null;
            !empty($decoded->customer->email) ? $customer_email=$decoded->customer->email : null;
        }


        $today_date = date("d-m-Y hh:mm:ss");
        // $campaing = $this->utilities->getCampaignData();

        // if(empty($campaing['_id'])){
        //     return $resp;
        // }

        $coupons = Coupon::active()
        ->where('pass_type', $pass_type)
        ->where('start_date', '<=', new \DateTime())
        ->where('end_date', '>', new \DateTime())
        ->where('ratecard_type', $ratecard_type)
        // ->where('campaign.campaign_id', (string)$campaing['_id'])
        ->where('total_available', '>', 0)
        ->get(['code', 'description', 'long_desc'])->toArray();

        $no_code_coupons = Nocouponcodeoffers::active()
        // ->where('campaign_id', (string)$campaing['_id'])
        ->where('pass_type', $pass_type)
        ->where('start_date', '<=', new \DateTime())
        ->where('end_date', '>', new \DateTime())
        ->where('ratecard_type', $ratecard_type)
        ->get(['code', 'description', 'long_desc'])->toArray();

        foreach($no_code_coupons as $key=>&$value){
            $value['complementary'] = true;
            $value['no_code'] = $value['code'];
        }

        $coupons = array_merge($coupons, $no_code_coupons);

        foreach($coupons as &$value){
            $value['terms'] = !empty($value['long_desc']) ? $value['long_desc'] : [];
            unset($value['long_desc']);
        }

        if(empty($coupons)) {
            return $resp;
        }
        
        
        if(!in_array($device, ['ios', 'android'])) {
            $coupons = $this->utilities->removeMobileCodes($coupons);
        }

        if($device=='android' && $app_version == '5.33'){
            foreach($coupons as $key=>&$value){
                $terms = !empty($value['terms']) ?  implode("<br>", $value['terms']) : '';
                $value['terms'] = [$terms];
            }
        }
        $resp['options'] = $coupons;
        return $resp;
            
    }
}