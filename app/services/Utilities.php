<?PHP namespace App\Services;
use Carbon\Carbon;
use Customer;
use Cart;
use Customerwallet;
use ProductRatecard;
use Validator;
use Response;
use Config;
use JWT;
use Finder;
use Service;
use Request;
use Log;
use App\Services\Sidekiq as Sidekiq;
use App\Services\ShortenUrl as ShortenUrl;
use Device;
use Wallet;
use WalletTransaction;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\FinderSms as FinderSms;
use App\Services\Fitapi as Fitapi;
use App\Mailers\CustomerMailer as CustomerMailer;
use Exception;
use App\Notification\CustomerNotification;
use Ratecard;
use Offer;
use DateTime;
use Order;
use Checkin;
use FinderMilestone;
use MongoDate;
use Coupon;
use \GuzzleHttp\Client;

use App\Services\Fitnessforce as Fitnessforce;

use App\Services\OzontelOutboundCall as OzontelOutboundCall;
use App\Services\CustomerReward as CustomerReward;
use App\Services\CustomerInfo as CustomerInfo;

use App\Services\Jwtauth as Jwtauth;

Class Utilities {

//    protected $myreward;
//    protected $customerReward;

   
   public function __construct() {
    
    $this->days=["sunday","monday","tuesday","wednesday","thursday","friday","saturday"];
    $this->vendor_token = false;
        
    $vendor_token = Request::header('Authorization-Vendor');
    $this->days=["sunday","monday","tuesday","wednesday","thursday","friday","saturday"];


    if($vendor_token){

        $this->vendor_token = true;
    }

    $this->kiosk_app_version = false;

    if($vendor_token){

        $this->vendor_token = true;

        $this->kiosk_app_version = (float)Request::header('App-Version');
    }

       
   }
    
    public function checkExistingTrialWithFinder($customer_email = null,$customer_phone = null,$finder_id = null){
        // For test vendor 
        if($finder_id == 7146 || $finder_id == 1584){
            return [];
        }
        // End for test vendor
        if(($customer_email == null && $customer_phone == null) && $finder_id == null){
            $error = array('status'=>400,'reason'=>'Required fields are missing');
            return $error;
        }

        try {
            return \Booktrial::
                where(function ($query) use($customer_email, $customer_phone) {
                    $query->orWhere('customer_email', $customer_email)
                        ->orWhere('customer_phone','LIKE','%'.substr($customer_phone, -9).'%');
                })
                ->where('finder_id', '=', (int) $finder_id)
                ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                ->get(array('id'));

        }catch (Exception $e) {
            $error = array('status'=>400,'reason'=>'Error');
            return $error;
        }

    }

    public function checkExistingTrialWithService($customer_email = null,$customer_phone = null,$finder_id = null, $service_id = null){
        // For test vendor 
        if($finder_id == 7146 || $finder_id == 1584){
            return [];
        }
        // End for test vendor
        if(($customer_email == null && $customer_phone == null) || $service_id == null){
            $error = array('status'=>400,'reason'=>'Required fields are missing');
            return $error;
        }

        try {
            return \Booktrial::
                where(function ($query) use($customer_email, $customer_phone) {
                    $query->orWhere('customer_email', $customer_email)
                        ->orWhere('customer_phone','LIKE','%'.substr($customer_phone, -9).'%');
                })
                ->where('finder_id', '=', (int) $finder_id)
                ->where('service_id', '=', (int) $service_id)
                ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                ->get(array('id'));

        }catch (Exception $e) {
            $error = array('status'=>400,'reason'=>'Error');
            return $error;
        }

    }

    public function getUpcomingTrialsOnTimestamp($customer_id = null, $schedule_datetime = null, $finderid = null){

        // For test vendor 
        if($finderid == 7146){
            return [];
        }
        // End for test vendor

        if($customer_id == null || $schedule_datetime == null){
            $error = array('status'=>400,'reason'=>'Required fields are missing');
            return $error;
        }

        try {
            return \Booktrial::
            where('customer_id', '=', (int) $customer_id)
                ->where('schedule_date_time', '=', new \DateTime(date($schedule_datetime)))
                ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                ->get(array('id'));

        }catch (Exception $e) {
            $error = array('status'=>400,'reason'=>'Error');
            return $error;
        }

    }
    
    public function getDateTimeFromDateAndTimeRange($date, $slot){

        $date    					=	date('d-m-Y', strtotime($date));
        $slots 						=	explode('-',$slot);
        $start_time 			    =	$slots[0];
        $start_timestamp			=	Carbon::createFromFormat('d-m-Y g:i A',
            strtoupper($date ." ".$start_time))->toDateTimeString();
        $end_time 			        =	$slots[1];
        $end_timestamp			    =	Carbon::createFromFormat('d-m-Y g:i A',
            strtoupper($date ." ".$end_time))->toDateTimeString();

        return array("start_timestamp"=>$start_timestamp, "end_timestamp"=>$end_timestamp);

    }

    public function walletTransaction($request,$data = false){

        $customer_id = (int)$request['customer_id'];

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = $this->customerTokenDecode($jwt_token);

            if(empty($request['for']) || (!empty($request['for']) && !in_array( $request['for'],['starter_pack_reference','locate_trial']))){
                $customer_id = (int)$decoded->customer->_id;
            }

            $request['customer_id'] = $customer_id;
        }

        $customer = \Customer::find($customer_id);

        $total_balance = 0;

        if(isset($customer->demonetisation)){

            $total_balance = \Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

        }else{

            $customerWallet = \Customerwallet::where('customer_id',$customer_id)->orderBy('_id','desc')->first();

            if($customerWallet){

                $fitcash = 0;
                $fitcash_plus = 0;

                $fitcash = $customerWallet->balance;

                if(isset($customerWallet->balance_fitcash_plus)){
                    $fitcash_plus = $customerWallet->balance_fitcash_plus;
                }

                $total_balance = (int)($fitcash + $fitcash_plus);
                
            }else{

                $customer->demonetisation = time();
                $customer->update();
            }
        }

        if($total_balance <= 0){

            if($request['entry'] == 'debit'){
                return ['status'=>200,'message'=>'cannot debit balance zero'];
            }
        }

        if(isset($customer->demonetisation) ){

            return $this->walletTransactionNew($request, $data);
        }

        return $this->walletTransactionOld($request,$data);

    }

    public function walletTransactionOld($request,$data = false){

        $customer_id = (int)$request['customer_id'];

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = $this->customerTokenDecode($jwt_token);

            if(empty($request['for']) || (!empty($request['for']) && !in_array( $request['for'],['starter_pack_reference','locate_trial']))){
                $customer_id = (int)$decoded->customer->_id;
            }

            $request['customer_id'] = $customer_id;
        }

        if(!isset($request['order_id'])){
            $request['order_id'] = 0;
        }
        
        // Validate transaction request........
        $validator = Validator::make($request, Customerwallet::$rules);

        if ($validator->fails()) {
            return array(
                        'status' => 400,
                        'message' => $this->errorMessage($validator->errors())
                    );
        }

        if($request['order_id'] != 0){

            // Check Duplicacy of transaction request........
            $duplicateRequest = Customerwallet::where('order_id', (int) $request['order_id'])
                ->where('type', $request['type'])
                ->orderBy('_id','desc')
                ->first();

            if($duplicateRequest != ''){

                if($request['type'] == "DEBIT"){

                    $debitAmount = Customerwallet::where('order_id', (int) $request['order_id'])
                    ->where('type', 'DEBIT')
                    ->sum('amount');

                    $refundAmount = Customerwallet::where('order_id', (int) $request['order_id'])
                    ->where('type', 'REFUND')
                    ->sum('amount');

                    if($debitAmount - $refundAmount != 0){
                        return array(
                                    'status' => 400,
                                    'message' => 'Request has been already processed'
                                );
                    }

                }elseif($request['type'] == "REFUND"){

                    $debitAmount = Customerwallet::where('order_id', (int) $request['order_id'])
                    ->where('type', 'DEBIT')
                    ->sum('amount');

                    $refundAmount = Customerwallet::where('order_id', (int) $request['order_id'])
                    ->where('type', 'REFUND')
                    ->sum('amount');

                    if($debitAmount - $refundAmount <= 0){
                        return array(
                                    'status' => 400,
                                    'message' => 'Request has been already processed'
                                );
                    }
                    
                }else{

                    return array(
                                    'status' => 400,
                                    'message' => 'Request has been already processed'
                                );
                }
                
            }

        }

        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios']) && isset($_GET['app_version']) && ((float)$_GET['app_version'] <= 3.2) ){

            $customer_balance = 0;
            $customer_balance_fitcash_plus = 0;

            $currentCustomerwallet = Customerwallet::where('customer_id',$customer_id)->OrderBy('_id','desc')->first();

            if($currentCustomerwallet){

                if(isset($currentCustomerwallet->balance)){
                    $customer_balance = $currentCustomerwallet->balance;
                }

                if(isset($currentCustomerwallet->balance_fitcash_plus)){
                    $customer_balance_fitcash_plus = $currentCustomerwallet->balance_fitcash_plus;
                }
            }

            $wallet = Customer::where('_id',$customer_id)
                ->first(array('balance'));

            !($wallet && isset($customer_balance))
                ? $customer_balance = 0
                : null;

            !($wallet && isset($wallet['balance_fitcash_plus']))
            ? $wallet['balance_fitcash_plus'] = 0
            : null;

            // Process Action on basis of request........
            ($request['type'] == 'CREDIT' || $request['type'] == 'REFUND'|| $request['type'] == 'CASHBACK')
                ? $request['balance'] = ((int) $customer_balance + abs($request['amount']))
                : null;
            if($request['type'] == 'DEBIT'){
                if($customer_balance < $request['amount']){
                    return array('status' => 421,'message' => 'Your wallet balance is low for transaction');
                }
                else{
                    $request['balance'] = ((int) $customer_balance - abs($request['amount']));
                }
            }

            $request['balance_fitcash_plus'] = (int)$customer_balance_fitcash_plus;

            $customerwallet = new Customerwallet();
            $id = Customerwallet::max('_id');
            //echo $id;
            $max_id = (isset($id) && !empty($id)) ? $id : 0;
            $customerwallet->_id = $max_id + 1;
            $customerwallet->customer_id = $customer_id;
            $customerwallet->order_id = (int) $request['order_id'];
            $customerwallet->type = $request['type'];
            $customerwallet->amount = (int) $request['amount'];
            $customerwallet->amount_fitcash = (int)$request['amount'];
            $customerwallet->amount_fitcash_plus = 0;
            $customerwallet->balance = (int) $request['balance'];
            $customerwallet->balance_fitcash_plus = (int) $request['balance_fitcash_plus'];
            isset($request['description']) ? $customerwallet->description = $request['description'] : null;
            isset($request['validity']) ? $customerwallet->validity = $request['validity'] : null;
            $customerwallet->save();

            // Update customer wallet balance........
            Customer::where('_id', $customer_id)->update(array('balance' => (int) $request['balance']));

            // Response........
            return array(
                    'status' => 200,
                    'message' => 'Transaction successful',
                    'balance' => $request['balance']
                );

        }elseif($data && isset($data['customer_source']) && in_array($data['customer_source'],['ios']) && isset($data['app_version']) && ((float)$data['app_version'] <= 3.2) ){

            $customer_balance = 0;
            $customer_balance_fitcash_plus = 0;

            $currentCustomerwallet = Customerwallet::where('customer_id',$customer_id)->OrderBy('_id','desc')->first();

            if($currentCustomerwallet){

                if(isset($currentCustomerwallet->balance)){
                    $customer_balance = $currentCustomerwallet->balance;
                }

                if(isset($currentCustomerwallet->balance_fitcash_plus)){
                    $customer_balance_fitcash_plus = $currentCustomerwallet->balance_fitcash_plus;
                }
            }

            $wallet = Customer::where('_id',$customer_id)
                ->first(array('balance'));

            !($wallet && isset($customer_balance))
                ? $customer_balance = 0
                : null;

            !($wallet && isset($wallet['balance_fitcash_plus']))
            ? $wallet['balance_fitcash_plus'] = 0
            : null;

            // Process Action on basis of request........
            ($request['type'] == 'CREDIT' || $request['type'] == 'REFUND'|| $request['type'] == 'CASHBACK')
                ? $request['balance'] = ((int) $customer_balance + abs($request['amount']))
                : null;
            if($request['type'] == 'DEBIT'){
                if($customer_balance < $request['amount']){
                    return array('status' => 422,'message' => 'Your wallet balance is low for transaction');
                }
                else{
                    $request['balance'] = ((int) $customer_balance - abs($request['amount']));
                }
            }

            $request['balance_fitcash_plus'] = (int)$customer_balance_fitcash_plus;

            $customerwallet = new Customerwallet();
            $id = Customerwallet::max('_id');
            //echo $id;
            $max_id = (isset($id) && !empty($id)) ? $id : 0;
            $customerwallet->_id = $max_id + 1;
            $customerwallet->customer_id = $customer_id;
            $customerwallet->order_id = (int) $request['order_id'];
            $customerwallet->type = $request['type'];
            $customerwallet->amount = (int) $request['amount'];
            $customerwallet->amount_fitcash = (int)$request['amount'];
            $customerwallet->amount_fitcash_plus = 0;
            $customerwallet->balance = (int) $request['balance'];
            $customerwallet->balance_fitcash_plus = (int) $request['balance_fitcash_plus'];
            isset($request['description']) ? $customerwallet->description = $request['description'] : null;
            isset($request['validity']) ? $customerwallet->validity = $request['validity'] : null;
            $customerwallet->save();

            // Update customer wallet balance........
            Customer::where('_id', $customer_id)->update(array('balance' => (int) $request['balance']));

            // Response........
            return array(
                    'status' => 200,
                    'message' => 'Transaction successful',
                    'balance' => $request['balance']
                );

        }else{

            Log::info("--request--",$request);

            // Get Customer wallet balance........
            $customer = Customer::find((int)$customer_id);

            $customer_balance = 0;
            $customer_balance_fitcash_plus = 0;

            $currentCustomerwallet = Customerwallet::where('customer_id',$customer_id)->OrderBy('_id','desc')->first();

            if($currentCustomerwallet){

                if(isset($currentCustomerwallet->balance)){
                    $customer_balance = $currentCustomerwallet->balance;
                }

                if(isset($currentCustomerwallet->balance_fitcash_plus)){
                    $customer_balance_fitcash_plus = $currentCustomerwallet->balance_fitcash_plus;
                }
            }

            (!isset($customer_balance)) ? $customer_balance = 0 : null;

            (!isset($customer_balance_fitcash_plus)) ? $customer_balance_fitcash_plus = 0: null;

            (!isset($request['amount_fitcash'])) ? $request['amount_fitcash'] = 0 : null;

            (!isset($request['amount_fitcash_plus'])) ? $request['amount_fitcash_plus'] = 0: null;

            $request['balance'] = (int)$customer_balance;
            $request['balance_fitcash_plus'] = (int)$customer_balance_fitcash_plus;

            if(in_array($request['type'], ['CASHBACK','FITCASHPLUS','REFERRAL','CREDIT','FITCASH'])){

                $request['balance'] = ((int) $customer_balance + (int) $request['amount_fitcash']);
                $request['balance_fitcash_plus'] = ((int) $customer_balance_fitcash_plus + (int) $request['amount_fitcash_plus']);
            }

            if($request['type'] == 'REFUND'){

                $request['balance'] = ((int) $customer_balance + abs($request['amount']));

                $request['amount_fitcash'] = $request['amount'];
                $request['amount_fitcash_plus'] = 0;

                if($data && isset($data['cashback_detail']) && isset($data['cashback_detail']['only_wallet']) && isset($data['cashback_detail']['discount_and_wallet'])){

                    $cashback_detail = $data['cashback_detail'];

                    $request['balance'] = $customer_balance + $cashback_detail['only_wallet']['fitcash'];
                    $request['balance_fitcash_plus'] = $customer_balance_fitcash_plus + $cashback_detail['only_wallet']['fitcash_plus'];

                    $request['amount_fitcash'] = $cashback_detail['only_wallet']['fitcash'];
                    $request['amount_fitcash_plus'] = $cashback_detail['only_wallet']['fitcash_plus'];

                    if(isset($data['cashback']) && $data['cashback'] == true){

                        $request['balance'] = $customer_balance + $cashback_detail['discount_and_wallet']['fitcash'];
                        $request['balance_fitcash_plus'] = $customer_balance_fitcash_plus + $cashback_detail['discount_and_wallet']['fitcash_plus'];

                        $request['amount_fitcash'] = $cashback_detail['discount_and_wallet']['fitcash'];
                        $request['amount_fitcash_plus'] = $cashback_detail['discount_and_wallet']['fitcash_plus'];
                    }

                }

            }
     
            if($request['type'] == 'DEBIT'){

                if(($customer_balance+$customer_balance_fitcash_plus) < $request['amount']){
                    return array('status' => 423,'message' => 'Your wallet balance is low for transaction');
                }

                $cashback_detail = $data['cashback_detail'];

                $request['balance'] = $customer_balance - $cashback_detail['only_wallet']['fitcash'];
                $request['balance_fitcash_plus'] = $customer_balance_fitcash_plus - $cashback_detail['only_wallet']['fitcash_plus'];

                $request['amount_fitcash'] = $cashback_detail['only_wallet']['fitcash'];
                $request['amount_fitcash_plus'] = $cashback_detail['only_wallet']['fitcash_plus'];

                if(isset($data['cashback']) && $data['cashback'] == true){

                    $request['balance'] = $customer_balance - $cashback_detail['discount_and_wallet']['fitcash'];
                    $request['balance_fitcash_plus'] = $customer_balance_fitcash_plus - $cashback_detail['discount_and_wallet']['fitcash_plus'];

                    $request['amount_fitcash'] = $cashback_detail['discount_and_wallet']['fitcash'];
                    $request['amount_fitcash_plus'] = $cashback_detail['discount_and_wallet']['fitcash_plus'];
                }
            }

            $customerwallet = new Customerwallet();
            $id = Customerwallet::max('_id');
            //echo $id;
            $max_id = (isset($id) && !empty($id)) ? $id : 0;
            $customerwallet->_id = $max_id + 1;
            $customerwallet->customer_id = $customer_id;
            ($request['order_id'] != 0) ? $customerwallet->order_id = (int) $request['order_id'] : null;
            $customerwallet->type = $request['type'];
            $customerwallet->amount = (int) $request['amount'];
            $customerwallet->balance = (int) $request['balance'];
            $customerwallet->amount_fitcash = (int)$request['amount_fitcash'];
            $customerwallet->amount_fitcash_plus = (int)$request['amount_fitcash_plus'];
            $customerwallet->balance_fitcash_plus = (int) $request['balance_fitcash_plus'];
            isset($request['description']) ? $customerwallet->description = $request['description'] : null;
            isset($request['validity']) ? $customerwallet->validity = $request['validity'] : null;
            $customerwallet->save();
            
            //update customer balance and balance_fitcash_plus
            $customer->balance = (int)$request['balance'];
            $customer->balance_fitcash_plus = (int)$request['balance_fitcash_plus'];
            $customer->update();

            // Response........
            return array(
                    'status' => 200,
                    'message' => 'Transaction successful',
                    'balance' => $request['balance']
                );
        }
    }

    
    public function getCustomerFreeTrials($customer_id){
        return \Booktrial::raw(function($collection) use ($customer_id){

            $aggregate = [];

            $match['$match']['type']['$exists'] = true;
            $match['$match']['amount']['$exists'] = true;
            $match['$match']['type']['$nin'] = array("",0);
            $match['$match']['$and'] = array(array('customer_id'=>(int) $customer_id),array('amount'=>0));

            $aggregate[] = $match;

            $group = array(
                '$group' => array(
                    '_id' => array(
                        'type' => '$type'
                    ),
                    'count' => array(
                        '$sum' => 1
                    )
                )
            );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

    }

    public function getCustomerFreeOrders($customer_id){
        return \Order::raw(function($collection) use ($customer_id){

            $aggregate = [];

            $match['$match']['type']['$exists'] = true;
            $match['$match']['type']['$nin'] = array("",0);
            $match['$match']['amount']['$exists'] = true;
            $match['$match']['amount']['$in'] = array("",0);
            $match['$match']['customer_id'] = (int) $customer_id;

            $aggregate[] = $match;

            $group = array(
                '$group' => array(
                    '_id' => array(
                        'type' => '$type'
                    ),
                    'count' => array(
                        '$sum' => 1
                    )
                )
            );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

    }

    public function getCustomerPaidOrders($customer_id){
        return  \Order::raw(function($collection) use ($customer_id){

            $aggregate = [];

            $match['$match']['type']['$exists'] = true;
            $match['$match']['type']['$nin'] = array("",0);
            $match['$match']['amount']['$exists'] = true;
            $match['$match']['amount']['$nin'] = array("",0);
            $match['$match']['customer_id'] = (int) $customer_id;

            $aggregate[] = $match;

            $group = array(
                '$group' => array(
                    '_id' => array(
                        'type' => '$type'
                    ),
                    'count' => array(
                        '$sum' => 1
                    )
                )
            );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });
    }

    public function customerTokenDecode($token){

        // $jwt_token = $token;
        // $jwt_key = Config::get('app.jwt.key');
        // $jwt_alg = Config::get('app.jwt.alg');
        // $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

        // return $decodedToken;

        return customerTokenDecode($token);
    }


    public function autoRegisterCustomer($data){

        // Input customer_email, customer_phone, customer_name, customer_id...
        // Output customer_id ...

        if(isset($data['customer_id']) && $data['customer_id'] != ""){
            return (int) $data['customer_id'];
        }


        $customer 		= 	Customer::active()->where('email', $data['customer_email'])->first();

        if(!$customer) {

            $inserted_id = Customer::max('_id') + 1;
            $customer = new Customer();
            $customer->_id = $inserted_id;
            $customer->name = ucwords($data['customer_name']) ;
            $customer->email = $data['customer_email'];
            $customer->picture = "https://www.gravatar.com/avatar/".md5($data['customer_email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
            $customer->password = md5(time());

            if(isset($data['customer_phone'])  && $data['customer_phone'] != ''){
                $customer->contact_no = $data['customer_phone'];
            }

            if(isset($data['customer_address'])){

                if(is_array($data['customer_address']) && !empty($data['customer_address'])){

                    $customer->address = implode(",", array_values($data['customer_address']));
                    $customer->address_array = $data['customer_address'];

                }elseif(!is_array($data['customer_address']) && $data['customer_address'] != ''){

                    $customer->address = $data['customer_address'];
                }

            }

            $customer->identity = 'email';
            $customer->account_link = array('email'=>1,'google'=>0,'facebook'=>0,'twitter'=>0);
            $customer->status = "1";
            $customer->ishulluser = 1;
            $customer->save();

            return $inserted_id;

        }else{

            $customerData = [];

            try{

                if(isset($data['customer_phone']) && $data['customer_phone'] != ""){
                    $customerData['contact_no'] = trim($data['customer_phone']);
                }

                if(isset($data['otp']) &&  $data['otp'] != ""){
                    $customerData['contact_no_verify_status'] = "yes";
                }

                if(isset($data['customer_address'])){

                    if(is_array($data['customer_address']) && !empty($data['customer_address'])){

                        $customerData['address'] = implode(",", array_values($data['customer_address']));
                        $customerData['address_array'] = $data['customer_address'];

                    }elseif(!is_array($data['customer_address']) && $data['customer_address'] != ''){

                        $customerData['address'] = $data['customer_address'];
                    }

                }

                if(count($customerData) > 0){
                    $customer->update($customerData);
                }

            } catch(ValidationException $e){

                Log::error($e);

            }

            return $customer->_id;
        }

    }

    public function errorMessage($errors){

        $errors = json_decode(json_encode($errors));
        $message = array();
        foreach ($errors as $key => $value) {
            $message[$key] = $value[0];
        }
        return $message;
    }


    public function removeEmptyKeys($req){

        $result = array();
        foreach ($req as $key=>$value){
            if(!empty($value)){
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function getCustomerTrials($customer_email){

        return \Booktrial::raw(function($collection) use ($customer_email){

            $aggregate = [];
            
            $match['$match']['customer_email'] = $customer_email;
            $match['$match']['booktrial_type']['$in'] = array("auto");

            $aggregate[] = $match;

            $group = array(
                '$group' => array(
                    '_id' => '$type',
                    'count' => array(
                        '$sum' => 1
                    )
                )
            );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

    }

    public function getFinderData($finder_id){

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
        $data['finder_slug']                       =    (isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
        $finder_name                       =    (isset($finder['title']) && $finder['title'] != '') ? ucwords($finder['title']) : "";

        $data['finder_city'] =  trim($finder_city);
        $data['finder_location'] =  trim($finder_location);
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
        $data['finder_name'] =  $finder_name;

        return $data; 

    }


    public function removeOrderCommunication($order){

        $allOrders = \Order::where('status','!=','1')
                        ->whereIn('type',['memberships','healthytiffinmembership','diet_plan','workout-session','booktrials'])
                        // ->where('service_id',(int)$order->service_id)
                        ->where('finder_id',(int)$order->finder_id)
                        ->where('customer_email',$order->customer_email)
                        ->where('_id','<=',(int)$order->_id)
                        ->where('payment_mode','paymentgateway')
                        ->where('paymentLinkEmailCustomerTiggerCount','exists',false)
                        ->where('created_at', '>=', new \DateTime( date("d-m-Y 00:00:00", strtotime("-44 days"))))
                        ->orderBy('_id','desc')
                        ->get();

        if(count($allOrders) > 0){

            foreach ($allOrders as $orderData) {

                if($orderData['_id'] != $order['_id']){

                    $orderData->redundant_order = "1";
                    $orderData->update();

                    $array = array('auto_followup_date','followup_status_count','followup_date');
                    $unset_keys = [];
                    foreach ($array as $value){

                        if(isset($orderData[$value])){
                            // $orderData->unset($value);
                            array_push($unset_keys, $value);
                        }
                    }
                    if(count($unset_keys)>0){
                        $orderData->unset($unset_keys);
                    }
                }

                $this->deleteCommunication($orderData);
            }
        }

    }

    public function setRedundant($order){

        try {

            $allOrdersLinkSent = \Order::where('status','!=','1')
                        ->whereIn('type',['memberships','healthytiffinmembership','diet_plan','workout-session','booktrials'])
                        // ->where('service_id',(int)$order->service_id)
                        ->where('finder_id',(int)$order->finder_id)
                        ->where('customer_email',$order->customer_email)
                        ->where('_id','<',(int)$order->_id)
                        ->where('created_at', '>=', new \DateTime( date("d-m-Y 00:00:00", strtotime("-44 days"))))
                        ->whereIn('payment_mode',['paymentgateway','cod'])
                        ->where('paymentLinkEmailCustomerTiggerCount','exists',true)
                        ->where('paymentLinkEmailCustomerTiggerCount','>',0)
                        ->where('redundant_order','exists',false)
                        ->orderBy('_id','desc')
                        ->get();

            if(count($allOrdersLinkSent) > 0){

                foreach ($allOrdersLinkSent as $orderData) {

                    $orderData->redundant_order = "1";
                    $orderData->update();
                    
                    $array = array('auto_followup_date','followup_status_count','followup_date');
                    $unset_keys = [];
                    foreach ($array as $value){
                        
                        if(isset($orderData[$value])){
                            // $orderData->unset($value);
                            array_push($unset_keys, $value);
                        }
                    }
                    
                    if(count($unset_keys)>0){
                        $orderData->unset($unset_keys);
                    }
                    $this->deleteCommunication($orderData);
                }
            }

            $this->removeOrderCommunication($order);
            
            $allBooktrials = \Booktrial::where('customer_email',$order->customer_email)
                        ->where('notification_status','exists',true)
                        ->where('notification_status','yes')
                        ->orderBy('_id','desc')
                        ->get();

            if(count($allBooktrials) > 0){

                foreach ($allBooktrials as $booktrial) {

                    $booktrial->notification_status = "no";
                    $booktrial->update();

                    $this->deleteTrialCommunication($booktrial);
                }
            }

            $allCaptures = \Capture::where('customer_email',$order->customer_email)
                        ->where('notification_status','exists',true)
                        ->where('notification_status','yes')
                        ->orderBy('_id','desc')
                        ->get();

            if(count($allCaptures) > 0){

                foreach ($allCaptures as $capture) {

                    $capture->notification_status = "no";
                    $capture->update();

                    $this->deleteCaptureCommunication($capture);
                }
            }


            return "success";

        } catch (Exception $e) {

            Log::error($e);

            return "error";
            
        }

    }



    public function verifyOrder($data,$order){
    	// Log::info(" info data".print_r($data,true));
    	// Log::info(" info order ".print_r($order,true));
    	if((!empty($data['third_party'])&&!empty($order['type'])&&$order['type']=='workout-session') || (!empty($order['studio_extended_validity_order_id']))){
    		$hash_verified = true;
        }
    	else if((isset($data["order_success_flag"]) && in_array($data["order_success_flag"],['kiosk','admin'])) || $order->pg_type == "PAYTM" || $order->pg_type == "AMAZON" || $order->pg_type == "MOBIKWIK" ||(isset($order['cod_otp_verified']) && $order['cod_otp_verified']) || (isset($order['vendor_otp_verified']) && $order['vendor_otp_verified']) || (isset($order['pay_later']) && $order['pay_later'] && !(isset($order['session_payment']) && $order['session_payment'])) || (isset($order->manual_order_punched) && $order->manual_order_punched) || !empty($data['internal_success'])){
            if(($order->pg_type == "PAYTM"|| $order->pg_type == "AMAZON" || $order->pg_type == "MOBIKWIK") && !(isset($data["order_success_flag"]))){
                $hashreverse = getpayTMhash($order);
                if($data["verify_hash"] == $hashreverse['reverse_hash']){
                    $hash_verified = true;
                }else{
                    $hash_verified = false;
                }
            }

            if((isset($data["order_success_flag"]) && in_array($data["order_success_flag"],['kiosk','admin'])) || (isset($order['cod_otp_verified']) && $order['cod_otp_verified']) || (isset($order['vendor_otp_verified']) && $order['vendor_otp_verified']) || (isset($order['pay_later']) && $order['pay_later'] && !(isset($order['session_payment']) && $order['session_payment'])) || (isset($order->manual_order_punched) && $order->manual_order_punched)){
                $hash_verified = true;
            }
            
	    	}
        else {
            // If amount is zero check for wallet amount
            if($data['amount'] == 0 || isset($order->full_payment_wallet) && $order->full_payment_wallet == true){
                $hash_verified = true;
            }else{
                $hashreverse = getReversehash($order);
                // Log::info($data["verify_hash"]);
                // Log::info($hashreverse['reverse_hash']);
                if($data["verify_hash"] == $hashreverse['reverse_hash']){
                    $hash_verified = true;
                }else{
                    $hash_verified = false;
                }
            }
        }
        if(!$hash_verified){
            $order["hash_verified"] = false;
            $order->update();
        }

        if($hash_verified && !empty($order['coupon_code'])){

            $customerCoupn = \CustomerCoupn::where('code', strtolower($order['coupon_code']))->first();

            if($customerCoupn){

                if($customerCoupn['status'] == "0"){

                    $hash_verified = false;

                    $order->update(['customer_coupn_error'=>true]);

                }else{

                    $order->update(['myreward_id'=>$customerCoupn['myreward_id']]);

                    $customerCoupn->claimed = $customerCoupn->claimed + 1;

                    if($customerCoupn->quantity == $customerCoupn->claimed){
                        $customerCoupn->status = "0";
                    }

                    $orders = [];

                    if(!empty($customerCoupn->orders)){
                        $orders = $customerCoupn->orders;
                    }

                    $orders[] = $order['_id'];

                    $customerCoupn->update();

                    $myreward = \Myreward::find($customerCoupn['myreward_id']);

                    $myrewardData = $myreward->toArray();

                    $coupon_detail = $myrewardData['coupon_detail'];

                    foreach ($coupon_detail as $key => &$value) {

                        if($value['code'] == strtolower($order['coupon_code'])){

                            if(!isset($value['claimed'])){
                                $value['claimed'] = 0;
                            }

                            $value['claimed'] += 1;
                            
                            $myreward->coupon_detail = $coupon_detail;
                            $myreward->update();
                            break;
                        }

                    }

                }      
            }
        }

        return $hash_verified;
    }

    
    public function verifyOrderProduct($data,$order)
    {
    	try {
    		Log::info(" info [verifyOrderProduct] data".print_r($data,true));
    		$orderArr=$order->toArray();
    		$hash_verified = false;
    		if((!empty($orderArr['payment'])&&!empty($orderArr['payment']['pg_type'])&&in_array($orderArr['payment']['pg_type'],['PAYTM','AMAZON'])))
    		{
    			$hashreverse = getReverseHashProduct($orderArr);
    			if($hashreverse['status']&&$hashreverse['data']['reverse_hash']==$data["verify_hash"] )
    				$hash_verified = true;
    		}
    		else
    		{
    			// If amount is zero check for wallet amount
    			if($orderArr['amount_calculated']['final']== 0)
    				$hash_verified = true;
    				else
    				{
    					$hashreverse = getReverseHashProduct($orderArr);
    					Log::info(" info hashreverse :: ".print_r($hashreverse ,true));
    					if($hashreverse['status']&&$data["verify_hash"] == $hashreverse['data']['reverse_hash'])
    						$hash_verified = true;
    				}
    		}
    		if(!$hash_verified){
    			
    			$paymentq=$orderArr['payment'];
    			$paymentq["hash_verified"]=false;
    			$orderArr['payment']=$paymentq;
    			$order->update($orderArr);
    		}
    		return $hash_verified;
    		
    	} catch (Exception $e) {
    		Log::error(" Error [verifyOrderProduct] ".print_r($this->baseFailureStatusMessage($e),true));
    		return false;
    	}
    }
    

    public function deleteCommunication($order){

        $queue_id = [];
        $notification_status = ['renewal_link_sent_no','link_sent_no','abandon_cart_no'];

        if($order->status == "1" || (isset($order->redundant_order) && $order->redundant_order == "1") || (isset($order->notification_status) && in_array($order->notification_status,$notification_status))){

            $array = [
                'customerSmsSendPaymentLinkAfter3Days',
                'customerSmsSendPaymentLinkAfter7Days',
                'customerSmsSendPaymentLinkAfter15Days',
                'customerSmsSendPaymentLinkAfter30Days',
                'customerSmsSendPaymentLinkAfter45Days',
                'customerSmsRenewalLinkSentBefore30Days',
                'customerSmsRenewalLinkSentBefore15Days',
                'customerSmsRenewalLinkSentBefore7Days',
                'customerSmsRenewalLinkSentBefore1Days',
                'customerSmsRenewalLinkSentAfter7Days',
                'customerSmsRenewalLinkSentAfter15Days',
                'customerSmsRenewalLinkSentAfter30Days',
                'customerSmsNotInterestedAfter15Days',
                'customerSmsNotInterestedAfter45Days',
                'customerSmsNotInterestedAfter75Days',
                'customerNotificationSendPaymentLinkAfter3Days',
                'customerNotificationSendPaymentLinkAfter7Days',
                'customerNotificationSendPaymentLinkAfter15Days',
                'customerNotificationSendPaymentLinkAfter30Days',
                'customerNotificationSendPaymentLinkAfter45Days',
                'customerNotificationRenewalLinkSentBefore30Days',
                'customerNotificationRenewalLinkSentBefore15Days',
                'customerNotificationRenewalLinkSentBefore7Days',
                'customerNotificationRenewalLinkSentBefore1Days',
                'customerNotificationRenewalLinkSentAfter7Days',
                'customerNotificationRenewalLinkSentAfter15Days',
                'customerNotificationRenewalLinkSentAfter30Days',
                'customerNotificationNotInterestedAfter15Days',
                'customerNotificationNotInterestedAfter45Days',
                'customerNotificationNotInterestedAfter75Days',
                'customerWalletRenewalLinkSentBefore7Days',
                'customerWalletRenewalLinkSentBefore1Days',
                'customerWalletSendPaymentLinkAfter15Days',
                'customerWalletSendPaymentLinkAfter30Days'
            ];
            $unset_keys = [];
    

            foreach ($array as $value) {

                if((isset($order[$value]))){
                    try {
                        $queue_id[] = $order[$value];
                        // $order->unset($value);
                        array_push($unset_keys, $value);
            
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }
            }

            if(count($unset_keys)>0){
                $order->unset($unset_keys);
            }

            if($order->status == "1"){
                $order->update(['notification_status' => 'purchase_yes']);
            }else{

                if(isset($order->paymentLinkEmailCustomerTiggerCount)){
                    $order->update(['notification_status' => 'link_sent_no']);
                }else{
                    $order->update(['notification_status' => 'abandon_cart_no']);
                }

                if(isset($order->renewalPaymentLinkCustomerTiggerCount)){
                    $order->update(['notification_status' => 'renewal_link_sent_no']);
                }
            }


        }

        if(!empty($queue_id)){

            $sidekiq = new Sidekiq();
            $sidekiq->delete($queue_id);
        }

    }
    public function customSort($field, &$array, $direction = 'asc')
    {
    	
    	usort($array, create_function('$a, $b', '
        		$a = $a["' . $field . '"];
        		$b = $b["' . $field . '"];
        		if ($a == $b) return 0;
        		return ($a ' . ($direction == 'desc' ? '>' : '<') .' $b) ? -1 : 1;
    			'));
    	return true;
    }
    public function sortDeep($field, &$array, $direction = 'asc')
    {
    	
    	usort($array, create_function('$a, $b', '
        		$a = $a["' . $field . '"];
        		$b = $b["' . $field . '"];
        		if ($a == $b) return 0;
        		return ($a ' . ($direction == 'desc' ? '>' : '<') .' $b) ? -1 : 1;
    			'));
    	return true;
    }
    public function deleteTrialCommunication($booktrial){

        $queue_id = [];

        $array = [
            'customerSmsPostTrialFollowup1After3Days',
            'customerSmsPostTrialFollowup1After7Days',
            'customerSmsPostTrialFollowup1After15Days',
            'customerSmsPostTrialFollowup1After30Days',
            'customerSmsPostTrialFollowup2After3Days',
            'customerSmsPostTrialFollowup2After7Days',
            'customerSmsPostTrialFollowup2After15Days',
            'customerSmsPostTrialFollowup2After30Days',
            'customerSmsNotInterestedAfter15Days',
            'customerSmsNotInterestedAfter45Days',
            'customerSmsNotInterestedAfter75Days',
            'customerNotificationPostTrialFollowup1After3Days',
            'customerNotificationPostTrialFollowup1After7Days',
            'customerNotificationPostTrialFollowup1After15Days',
            'customerNotificationPostTrialFollowup1After30Days',
            'customerNotificationPostTrialFollowup2After3Days',
            'customerNotificationPostTrialFollowup2After7Days',
            'customerNotificationPostTrialFollowup2After15Days',
            'customerNotificationPostTrialFollowup2After30Days',
            'customerNotificationNotInterestedAfter15Days',
            'customerNotificationNotInterestedAfter45Days',
            'customerNotificationNotInterestedAfter75Days',
            'customerWalletPostTrialFollowup1After15Days'
        ];
        $unset_keys = [];

        foreach ($array as $value) {
            if((isset($booktrial[$value]))){
                try {
                    $queue_id[] = $booktrial[$value];
                    // $booktrial->unset($value);
                    array_push($unset_keys, $value);
        
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }
        }

        if(count($unset_keys)>0){
            $booktrial->unset($unset_keys);
        }

        if(!empty($queue_id)){

            $sidekiq = new Sidekiq();
            $sidekiq->delete($queue_id);
        }

    }

    public function deleteCaptureCommunication($capture){

        $queue_id = [];

        $array = [
            'customerSmsPostCaptureFollowup2After3Days',
            'customerSmsPostCaptureFollowup2After7Days',
            'customerSmsPostCaptureFollowup2After15Days',
            'customerSmsPostCaptureFollowup2After30Days',
            'customerNotificationPostCaptureFollowup2After3Days',
            'customerNotificationPostCaptureFollowup2After7Days',
            'customerNotificationPostCaptureFollowup2After15Days',
            'customerNotificationPostCaptureFollowup2After30Days',
        ];
        $unset_keys = [];

        foreach ($array as $value) {
            if((isset($capture[$value]))){
                try {
                    $queue_id[] = $capture[$value];
                    // $capture->unset($value);
                    array_push($unset_keys, $value);
        
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }
        }

        if(count($unset_keys)>0){
            $capture->unset($unset_keys);
        }

        if(!empty($queue_id)){

            $sidekiq = new Sidekiq();
            $sidekiq->delete($queue_id);
        }
        
    }

    public function addCapture($data){

        if(isset($data['order_id']) && $data['order_id'] != ""){

            $order = \Order::find((int) $data['order_id']);

            if(isset($order->finder_id)){
                $data['vendor_id'] = $data['finder_id'] = $order->finder_id;
            }

            if(isset($order->finder_name)){
                $data['vendor_name'] = $data['finder_name'] = $order->finder_name;
            }

            if(isset($order->city_id)){
                $data['city_id'] = $order->city_id;
            }
        }

        if(isset($data['customer_phone']) && $data['customer_phone'] != ""){
            $data['phone'] = $data['mobile'] = $data['customer_phone'];
        }

        if(isset($data['mobile']) && $data['mobile'] != ""){
            $data['customer_phone'] = $data['phone'] = $data['mobile'];
        }

        if(isset($data['customer_name']) && $data['customer_name'] != ""){
            $data['name'] = $data['customer_name'];
        }

        if(isset($data['customer_email']) && $data['customer_email'] != ""){
            $data['email'] = $data['customer_email'];
        }

        if(isset($data['phone']) && $data['phone'] != ""){
            $data['customer_phone'] = $data['mobile'] = $data['phone'];
        }

        if(isset($data['name']) && $data['name'] != ""){
            $data['customer_name'] = $data['name'];
        }

        if(isset($data['email']) && $data['email'] != ""){
            $data['customer_email'] = $data['email'];
        }

        array_set($data, 'capture_status', 'yet to connect');
        array_set($data, 'date',date("h:i:sa"));
        array_set($data, 'ticket_number',random_numbers(5));

        if(isset($data['finder_id']) && $data['finder_id'] != ""){
            $data['finder_id'] = (int)$data['finder_id'];
        }

       \Capture::create($data);

       return "Success";

    }

    public function addNotificationTracking($data){

        $notificationTracking = new \NotificationTracking($data);
        $notificationTracking->count = 1;
        $notificationTracking->save();

        return $notificationTracking;
    }

    public function addRegId($data){

        try {

            $rules = [
                'reg_id' => 'required',
                'type' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {

                return array('status' => 400, 'message' => error_message($validator->errors()));
            }

            $device = Device::where('reg_id', $data['reg_id'])->orderBy("_id","DESC")->first();

            if ($device) {

                if(isset($data['customer_id']) && $data['customer_id'] != '' && $data['customer_id'] != null && $data['customer_id'] != 'null'){
                    $device->customer_id = (int)$data['customer_id'];
                }

                if(isset($data['device_model']) && $data['device_model'] != '' && $data['device_model'] != null && $data['device_model'] != 'null'){
                    $device->device_model = $data['device_model'];
                }

                if(isset($data['app_version']) && $data['app_version'] != '' && $data['app_version'] != null && $data['app_version'] != 'null'){
                    $device->app_version = (float)$data['app_version'];
                }

                if(isset($data['os_version']) && $data['os_version'] != '' && $data['os_version'] != null && $data['os_version'] != 'null'){
                    $device->os_version = (float)$data['os_version'];
                }

                $device->last_visited_date = time();

                $device->update();

            } else {

                /*$allDeviceCount = 0;

                if(isset($data['customer_id']) && $data['customer_id'] != ''){

                    $allDeviceCount = Device::where('customer_id', (int)$data['customer_id'])->where('type','!=','web')->count();
                }*/

                $device_id = Device::max('_id') + 1;
                $device = new Device();
                $device->_id = $device_id;
                $device->reg_id = $data['reg_id'];

                if(isset($data['customer_id']) && $data['customer_id'] != '' && $data['customer_id'] != null && $data['customer_id'] != 'null'){
                    $device->customer_id = (int)$data['customer_id'];
                }

                if(isset($data['device_model']) && $data['device_model'] != '' && $data['device_model'] != null && $data['device_model'] != 'null'){
                    $device->device_model = $data['device_model'];
                }

                if(isset($data['app_version']) && $data['app_version'] != '' && $data['app_version'] != null && $data['app_version'] != 'null'){
                    $device->app_version = (float)$data['app_version'];
                }

                if(isset($data['os_version']) && $data['os_version'] != '' && $data['os_version'] != null && $data['os_version'] != 'null'){
                    $device->os_version = (float)$data['os_version'];
                }

                $device->last_visited_date = time();

                $device->type = $data['type'];
                $device->status = "1";
                $device->save();
                
                /*if($allDeviceCount == 0 && isset($data['customer_id']) && $data['customer_id'] != ''){

                    $booktrial = \Booktrial::where('created_at','>',new \DateTime(date("d-m-Y 00:00:00",strtotime("20-4-2017 00:00:00"))))->where("customer_id",(int)$data['customer_id'])->where('type','booktrials')->count();

                    $description = "app download";

                    $fitcashGivenWallet = Wallet::where('description','LIKE','%'.$description.'%')->where('customer_id',(int)$data['customer_id'])->count();

                    $fitcashGivenCustomerwallet = Customerwallet::where('description','LIKE','%'.$description.'%')->where('customer_id',(int)$data['customer_id'])->count();

                    if($booktrial > 0 && $fitcashGivenWallet == 0 && $fitcashGivenCustomerwallet == 0){

                        $addWalletData = [
                            "customer_id" => $data["customer_id"],
                            "amount" => 250,
                            "amount_fitcash_plus"=>250,
                            "description" => "Added FitCash+ Rs 250 on App Download, Expires On : ".date('d-m-Y',time()+(86400*180)),
                            "validity"=>time()+(86400*180),
                            "entry"=>"credit",
                            "type"=>"FITCASHPLUS"
                        ];

                        $this->walletTransaction($addWalletData);
                    }
                }*/

            }

            $response = array('status' => 200, 'message' => 'success');

        } catch (Exception $e) {

            $message = array(
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            );

            $response = array('status' => 400, 'message' => $message['type'] . ' : ' . $message['message'] . ' in ' . $message['file'] . ' on ' . $message['line']);

            Log::error($e);

        }

        return $response;
    }

    public function addWallet($data){

        Log::info("---data---",$data);

        $customer_id = (int) $data["customer_id"];
        $amount = $data["amount"];

        $req['customer_id'] = $customer_id;
        $req['amount'] = $amount;

        if($data['action'] == "add_fitcash"){
            $req['amount_fitcash'] = $amount;
            $req['type'] = "FITCASH";
            $req['description'] = "Added FitCash Rs ".$amount;
        }

        if($data['action'] == "add_fitcash_plus"){
            $req['amount_fitcash_plus'] = $amount;
            $req['type'] = "FITCASHPLUS";
            $req['description'] = "Added FitCash+ Rs ".$amount;
        }

        if(isset($data['description']) && $data['description'] != ""){
            $req['description'] = $data['description'];
        }

        $req['entry'] = 'credit';

        $walletTransactionResponse = $this->walletTransaction($req);


        Log::info("----walletTransactionResponse-----",$walletTransactionResponse);

        if($walletTransactionResponse['status'] != 200){
            return "success";
        }

        return "error";

    }

    public function getShortenUrl($url){

        $shortenUrl = new ShortenUrl();
        $shorten_url = $shortenUrl->getShortenUrl($url);

        if(isset($shorten_url['status']) &&  $shorten_url['status'] == 200){
            $url =  $shorten_url['url'];
        }

        return $url;

    }

    public function walletTransactionNew($request, $data=false){

        $wallet_limit = 100000;

        // if($data && isset($data['type']) && in_array($data['type'], ['wallet'])){
        //     Log::info("increasing wallet limit for pledge");
        //     $wallet_limit = 100000;
        
        // }
        
        // if(!empty($request['remove_wallet_limit'])){
        //     Log::info("increasing wallet limit");
        //     $wallet_limit = 100000;
        
        // }

        // if($request && isset($request['code']) && in_array($request['code'], ["of001","of@2","of03!","o4f","of005","of@6","of07!","o8f","of009","of@10","of011!","o012f","of0013","of@14","of015!","o016f","of0017","of@18","of019!","o020f","opf001","ofp@2","ofp03!","o4fp","ofp005","ofp@6","ofp07!","o8fp","ofp009","ofp@10","ofp011!","o012fp","ofp0013","ofp@14","ofp015!","o016fp","ofp0017","ofp@18","ofp019!","o020fp"])){
        //     Log::info("increasing wallet limit for coupon");
        //     $wallet_limit = 100000;
        
        // }

        $customer_id = (int)$request['customer_id'];

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = $this->customerTokenDecode($jwt_token);

            if(empty($request['for']) || (!empty($request['for']) && !in_array( $request['for'],['starter_pack_reference','locate_trial']))){
                $customer_id = (int)$decoded->customer->_id;
            }

            $request['customer_id'] = $customer_id;
        }

        $customer = Customer::find($customer_id);

        $validator = Validator::make($request, Wallet::$rules);

        if ($validator->fails()) {
            return ['status' => 400,'message' => $this->errorMessage($validator->errors())];
        }

        $entry = $request['entry'];
        $type = $request['type'];

        if(isset($request['order_id']) &&  $request['order_id'] != 0){

            // Check Duplicacy of transaction request........
            $duplicateRequest = WalletTransaction::where('order_id', (int) $request['order_id'])
                ->where('type', $request['type'])
                ->where('customer_id',$customer_id)
                ->orderBy('_id','desc')
                ->first();

            if($duplicateRequest != '' && empty($request['duplicate_allowed'])){

                if($request['type'] == "DEBIT"){

                    $debitAmount = WalletTransaction::where('order_id', (int) $request['order_id'])
                    ->where('type', 'DEBIT')
                    ->sum('amount');

                    $refundAmount = WalletTransaction::where('order_id', (int) $request['order_id'])
                    ->where('type', 'REFUND')
                    ->sum('amount');

                    if($debitAmount - $refundAmount != 0){

                        return ['status' => 400,'message' => 'Request has been already processed'];
                    }

                }elseif($request['type'] == "REFUND"){

                    $debitAmount = WalletTransaction::where('order_id', (int) $request['order_id'])
                    ->where('type', 'DEBIT')
                    ->sum('amount');

                    $refundAmount = WalletTransaction::where('order_id', (int) $request['order_id'])
                    ->where('type', 'REFUND')
                    ->sum('amount');

                    if($debitAmount - $refundAmount <= 0){

                        return ['status' => 400,'message' => 'Request has been already processed'];
                    }
                    
                }else{

                    return ['status' => 400,'message' => 'Request has been already processed'];
                }
                
            }

        }

        if($entry == 'credit'){

            $current_wallet_balance = \Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

            if($type == 'REFUND'){

                if(isset($customer->current_wallet_balance) && $customer->current_wallet_balance > $wallet_limit){

                    $wallet_limit = $customer->current_wallet_balance;
                }

                if($current_wallet_balance >= $wallet_limit){
                    return ['status' => 400,'message' => 'Wallet is overflowing Rs '.$wallet_limit];
                }

                $request_amount_balance = $request_amount = $request['amount'];

                if(!isset($request['full_amount']) && $current_wallet_balance < $wallet_limit && ($current_wallet_balance + (int)$request['amount']) > $wallet_limit){
                    $request_amount_balance = $request_amount = $request['amount'] = (int)($wallet_limit - $current_wallet_balance);
                }

                $order = \Order::where('status','!=','1')->find((int)$request['order_id'])->toArray();

                $wallet_transaction = $order['wallet_transaction_debit']['wallet_transaction'];
                $wallet_amount = $order['wallet_transaction_debit']['amount'];

                $group = "";

                foreach ($wallet_transaction as $key => $value) {

                    $value_amount = $value['amount'];

                    if($request_amount_balance <= 0){
                        break;
                    }

                    if($request_amount_balance < $value_amount){
                        $value_amount = $request_amount_balance;
                    }

                    $wallet = Wallet::find((int)$value['wallet_id']);
                    $wallet->used = intval($wallet->used - $value_amount);
                    $wallet->balance = intval($wallet->balance + $value_amount);

                    $wallet->update();

                    $walletTransactionData['wallet_id'] = (int)$value['wallet_id'];                 
                    $walletTransactionData['entry'] = $entry;
                    $walletTransactionData['type'] = $type;
                    $walletTransactionData['customer_id'] = $customer_id;
                    $walletTransactionData['amount'] = intval($value_amount);
                    $walletTransactionData['description'] = "Refund";

                    if(isset($request['order_id']) && $request['order_id'] != ""){

                        $walletTransactionData['order_id'] = (int)$request['order_id'];

                        $walletTransactionData['description'] = "Refund for order ".$request['order_id'];
                    }

                    if(isset($request['trial_id']) && $request['trial_id'] != ""){

                        $walletTransactionData['trial_id'] = (int)$request['trial_id'];

                        $walletTransactionData['description'] = "Refund for trial ".$request['trial_id'];
                    }

                    $walletTransactionData['validity'] = 0;

                    if(isset($value['coupon']) && $value['coupon'] != ""){
                        $walletTransactionData['coupon'] = $value['coupon'];
                    }

                    if(isset($request['description'])){
                        $walletTransactionData['description'] = $request['description'];
                    }
                    
                    $walletTransaction = WalletTransaction::create($walletTransactionData);

                    if($group == ""){
                        $group = $walletTransaction->_id;
                    }

                    $walletTransaction->update(['group'=>$group]);

                    $request_amount_balance = (int)($request_amount_balance - $value_amount);
   
                }

                return ['status' => 200,'message' => 'Refunded in wallet'];

            }

            // if(isset($request['finder_id']) && $request['finder_id'] != ""){

            //     $finder_id = (int)$request['finder_id'];

            //     $power_world_gym_vendor_ids = Config::get('app.power_world_gym_vendor_ids');

            //     if(in_array($finder_id,$power_world_gym_vendor_ids)){

            //         $wallet_limit = 100000;
            //     }
                
            // }

            /*if(!isset($customer->current_wallet_balance) && $current_wallet_balance >= $wallet_limit){
                return ['status' => 400,'message' => 'Wallet is overflowing Rs '.$wallet_limit];
            }*/

            if($current_wallet_balance >= $wallet_limit){
            	if(!empty($request['qrcodescan']))
            		return ['status' => 200,'message' => 'Your Fitcash limit of '.$wallet_limit." has been reached.","sub_header"=>""];
                else return ['status' => 400,'message' => 'Wallet is overflowing Rs '.$wallet_limit];

            }

            /*if($current_wallet_balance < $wallet_limit && ($current_wallet_balance + (int)$request['amount']) > $wallet_limit){

                $request['amount'] = (int)($wallet_limit - $current_wallet_balance);

                if(isset($customer->current_wallet_balance)){

                    $request['amount'] = (int)($wallet_limit - $customer->current_wallet_balance) > 0 ? (int)($wallet_limit - $customer->current_wallet_balance) : 0;

                    if($request['amount'] <= 0){

                        return ['status' => 400,'message' => 'Wallet is overflowing Rs '.$wallet_limit];
                    }

                }
                
            }*/

            if(!isset($customer->current_wallet_balance) && $current_wallet_balance < $wallet_limit && ($current_wallet_balance + (int)$request['amount']) > $wallet_limit){

              $request['amount'] = (int)($wallet_limit - $current_wallet_balance);

            }


            if(isset($customer->current_wallet_balance)){

                $entry_present = \Wallet::active()->where('customer_id',$customer_id)->count();

                if($entry_present){

                    if($current_wallet_balance < $wallet_limit && ($current_wallet_balance + (int)$request['amount']) > $wallet_limit){

                        $request['amount'] = (int)($wallet_limit - $current_wallet_balance);

                        $customer->unset('current_wallet_balance');

                    }
                }

            }

            if($request['amount'] <= 0){
                return ['status' => 400,'message' => 'Requested amount is zero'];
            }

            Log::info('credit',$request);

            $wallet = new Wallet();
            $wallet->_id = (Wallet::max('_id')) ? (int) Wallet::max('_id') + 1 : 1;
            $wallet->amount = (int)$request['amount'];
            $wallet->used = 0;
            $wallet->balance = (int)$request['amount'];
            $wallet->status = "1";
            $wallet->entry = $entry;
            $wallet->customer_id = $customer_id;
            $wallet->validity = 0;
            $wallet->type = $type;

            if(isset($request['order_id']) && $request['order_id'] != ""){
                $wallet->order_id = (int)$request['order_id'];
            }
            
            if(isset($request['membership_order_id']) && $request['membership_order_id'] != ""){
                $wallet->membership_order_id = (int)$request['membership_order_id'];
            }

            if(isset($request['trial_id']) && $request['trial_id'] != ""){
                $wallet->trial_id = (int)$request['trial_id'];
            }

            if(isset($request['coupon']) && $request['coupon'] != ""){
                $wallet->coupon = $request['coupon'];
            }

            if(isset($request['validity']) && $request['validity'] != ""){
                $wallet->validity = $request['validity'];
            }

            if(isset($request['description']) && $request['description'] != ""){
                $wallet->description = $request['description'];
            }

            if(isset($request['code']) && $request['code'] != ""){
                $wallet->coupon = $request['code'];
            }

            if(isset($request['for']) && $request['for'] != ""){
                $wallet->for = $request['for'];
            }

            if(isset($request['review_id']) && $request['review_id'] != ""){
                $wallet->review_id = (int)$request['review_id'];
            }

            if(isset($request['finder_id']) && $request['finder_id'] != ""){
                $wallet->finder_id = (int)$request['finder_id'];
            }

            if(isset($request['valid_finder_id']) && $request['valid_finder_id'] != ""){

                $wallet->valid_finder_id = $request['valid_finder_id'];

                if(empty($request['order_type'])){
                    $wallet->order_type = ['membership','memberships','healthytiffinmembership'];
                }
            }

            if(isset($request['service_id']) && $request['service_id'] != ""){

                $wallet->service_id = $request['service_id'];
            }
            
            if(isset($request['duration_day']) && $request['duration_day'] != ""){

                $wallet->duration_day = $request['duration_day'];
            }

            if(isset($request['valid_service_id']) && $request['valid_service_id'] != ""){

                $wallet->valid_service_id = $request['valid_service_id'];
            }
            
            if(!empty($request['order_type'])){

                $wallet->order_type = $request['order_type'];
            }
            if(!empty($request['restricted'])){

                $wallet->restricted = $request['restricted'];
            }
            if(!empty($request['restricted_for'])){

                $wallet->restricted_for = $request['restricted_for'];
            }
            if(!empty($request['details'])){

                $wallet->for_details = $request['details'];
            }
            
            $wallet->save();

            $walletTransactionData['wallet_id'] = $wallet->_id;
            $walletTransactionData['entry'] = $wallet->entry;
            $walletTransactionData['type'] = $wallet->type;
            $walletTransactionData['customer_id'] = $customer_id;
            $walletTransactionData['amount'] = (int)$request['amount'];

            if(isset($request['order_id']) && $request['order_id'] != ""){

                $walletTransactionData['order_id'] = (int)$request['order_id'];

                $walletTransactionData['description'] = "Added Amount of Rs ".$request['amount']." for Order ".$request['order_id'];

            }

            if(isset($request['trial_id']) && $request['trial_id'] != ""){

                $walletTransactionData['trial_id'] = (int)$request['trial_id'];

                $walletTransactionData['description'] = "Added Amount of Rs ".$request['amount']." for Trial ".$request['trial_id'];
            }
            
            $walletTransactionData['validity'] = $wallet['validity'];

            if(isset($wallet['coupon']) && $wallet['coupon'] != ""){
                $walletTransactionData['coupon'] = $wallet['coupon'];
            }

            $walletTransactionData['description'] = "Added Amount of Rs ".$request['amount'];

            if(isset($request['description'])){
                $walletTransactionData['description'] = $request['description'];
            }

            $walletTransaction = WalletTransaction::create($walletTransactionData);

            $walletTransaction->update(['group'=>$walletTransaction->_id]);

            if(!empty($request['qrcodescan']))
            	if($current_wallet_balance==$wallet_limit)
            		return ['status' => 200,'message' => 'Rs. '.$request['amount'].' has been added to your wallet and your fitcash limit of '.$wallet_limit.' has been reached.','amount'=>$request['amount'],"sub_header"=>$request['amount']];
            		else return ['status' => 200,'message' =>'Rs. '.$request['amount'].' has been added to your wallet'];
            	
            return ['status' => 200,'message' => 'Success Added Wallet'];

        }

        if($entry == 'debit'){
                
            $amount = $request['amount'];

            $query =  $this->getWalletQuery($request);

            $allWallets  = $query->OrderBy('restricted','desc')->OrderBy('_id','asc')->get();

            if(count($allWallets) > 0){

                $allWalletsValidityZero = [];
                $allWalletsValidityOther = [];
                $allWalletsValidityDate = [];

                foreach ($allWallets as $key => $value){

                    if($value['validity'] == 0){
                        $allWalletsValidityZero[] = $value;
                    }else{
                        $allWalletsValidityOther[] = $value;
                        $allWalletsValidityDate[] = $value['validity'];
                    }
                }

                $walletData = $allWalletsValidityZero;

                Log::info('----allWalletsValidityZero----',$allWalletsValidityZero);
                Log::info('----allWalletsValidityOther----',$allWalletsValidityOther);
                Log::info('----allWalletsValidityDate----',$allWalletsValidityDate);

                if(count($allWalletsValidityOther) > 0){
                    array_multisort($allWalletsValidityDate, SORT_ASC, $allWalletsValidityOther);
                    $walletData = array_merge($allWalletsValidityOther,$allWalletsValidityZero);
                }

                if(count($walletData) > 0){

                    $amount_used = 0;
                    $amount_balance = (int)$amount;

                    $walletTransactionDebit = [];
                    $group = "";

                    foreach ($walletData as $key => $value) {

                        $walletTransactionData['amount'] = (int)$value['balance'];

                        if($value['balance'] >= $amount_balance){
                            $walletTransactionData['amount'] = (int)$amount_balance;
                        }

                        $amount_used = intval($amount_used + $walletTransactionData['amount']);
                        $amount_balance = intval($amount_balance - $walletTransactionData['amount']);

                        $walletTransactionData['wallet_id'] = $value->_id;
                        $walletTransactionData['entry'] = $entry;
                        $walletTransactionData['type'] = $request['type'];
                        $walletTransactionData['customer_id'] = $customer_id;
                        
                        if(isset($request['order_id']) && $request['order_id'] != ""){
                            $walletTransactionData['order_id'] = (int)$request['order_id'];

                            $walletTransactionData['description'] = "Paid for Order ID: ".$request['order_id'];
                        }

                        if(isset($request['trial_id']) && $request['trial_id'] != ""){
                            $walletTransactionData['trial_id'] = (int)$request['trial_id'];

                            $walletTransactionData['description'] = "Paid for Trial ID: ".$request['trial_id'];
                        }

                        if(isset($value['validity']) && $value['validity'] != ""){
                            $walletTransactionData['validity'] = $value['validity'];
                        }

                        if(isset($value['coupon']) && $value['coupon'] != ""){
                            $walletTransactionData['coupon'] = $value['coupon'];
                        }

                        $walletTransaction = WalletTransaction::create($walletTransactionData);

                        if($group == ""){
                            $group = $walletTransaction->_id;
                        }

                        $walletTransaction->update(['group'=>$group]);

                        $value->used = intval($value->used + $walletTransactionData['amount']);
                        $value->balance = intval($value->balance - $walletTransactionData['amount']);
                        $value->update();

                        $walletTransactionDebitEntry = [
                            'wallet_id' => $value->_id,
                            'wallet_transaction_id' => $walletTransaction->_id,
                            'amount' => $walletTransactionData['amount']
                        ];
                        
                        if(!empty($value['restricted_for']) && $value['restricted_for'] == 'upgrade'){
                            $walletTransactionDebitEntry['upgraded_order_id'] = $value['order_id'];
                        }
                        
                        $walletTransactionDebit[] =  $walletTransactionDebitEntry;


                        if($amount_used == $amount){
                            break;
                        }
                        
                    }

                    if(isset($customer->demonetisation) && isset($customer->current_wallet_balance) && $customer->current_wallet_balance > $wallet_limit){
                        $customer->update(['current_wallet_balance'=> (int)($customer->current_wallet_balance - $amount),'current_wallet_balance_transaction_date'=>time()]);
                    }

                    if(isset($request['order_id']) && $request['order_id'] != ""){

                        return ['status' => 200,'message' => 'Success Updated Wallet','wallet_transaction_debit'=>['amount'=>$amount,'wallet_transaction'=>$walletTransactionDebit]];
                    }

                }else{

                    return ['status' => 400,'message' => 'Wallet data not found'];
                }

            }else{

                return ['status' => 400,'message' => 'Wallet not found'];
            }

            return ['status' => 200,'message' => 'Success Updated Wallet'];
        }

        return ['status' => 200,'message' => 'Success'];
    }

    public function demonetisation($order){

        $wallet_limit = 2500;
        $cap = 2000;
        $wallet = 0;
        $wallet_fitcash_plus = 0;

        $customer_id = $order['logged_in_customer_id'];

        $customer = \Customer::find($customer_id);

        $currentOrder = $order;

        $order = $order->toArray();


        if(!isset($customer->demonetisation) && isset($order['wallet_amount']) && $order['amount_finder'] > 500){

            $customer_wallet = \Customerwallet::where('customer_id',(int) $customer_id)->orderBy('_id','desc')->first();

            if($customer_wallet){

                if( isset($customer_wallet->balance_fitcash_plus) && $customer_wallet->balance_fitcash_plus != ''){
                    $wallet_fitcash_plus = (int)$customer_wallet->balance_fitcash_plus;
                }

                if($cap >= $order['wallet_amount']){
                    $cap = $cap - $order['wallet_amount'];
                }

                if(isset($customer_wallet->balance) && $customer_wallet->balance != '' && $wallet_fitcash_plus < $cap){

                    $wallet = $customer_wallet->balance;

                    if(($wallet + $wallet_fitcash_plus) > $cap){

                        $wallet = $cap - $wallet_fitcash_plus;
                    }
                }

                $current_wallet_balance = (int)($wallet + $wallet_fitcash_plus);

                if($current_wallet_balance > 0){

                    $credit_amount = 2000;

                    $current_wallet_balance_only_fitcash = $order['cashback_detail']['current_wallet_balance_only_fitcash'];
                    $current_wallet_balance_only_fitcash_plus = $order['cashback_detail']['current_wallet_balance_only_fitcash_plus'];

                    if($current_wallet_balance_only_fitcash+$current_wallet_balance_only_fitcash_plus < 2000){

                        $credit_amount = $current_wallet_balance_only_fitcash+$current_wallet_balance_only_fitcash_plus;

                    }

                    if($current_wallet_balance_only_fitcash_plus >= 2000){

                        $credit_amount = $current_wallet_balance_only_fitcash_plus;

                    }

                    if($credit_amount > $wallet_limit){
                        $customer->update(['current_wallet_balance'=>$current_wallet_balance]);
                    }

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $credit_amount;
                    $request['entry'] = "credit";
                    $request['type'] = "CREDIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = "Conversion of FitCash+ on Demonetization (Order ID. ".$order['_id'].")";
                    Log::info("1",$request);

                    $this->walletTransactionNew($request);

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $order['wallet_amount'];
                    $request['entry'] = "debit";
                    $request['type'] = "DEBIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = $this->getDescription($order);

                    Log::info("2",$request);

                    $this->walletTransactionNew($request);

                }else{
                    
                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $order['cashback_detail']['current_wallet_balance'];
                    $request['entry'] = "credit";
                    $request['type'] = "CREDIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = "Conversion of FitCash+ on Demonetization (Order ID. ".$order['_id'].")";

                    $this->walletTransactionNew($request);

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $order['wallet_amount'];//$order['cashback_detail']['current_wallet_balance'];
                    $request['entry'] = "debit";
                    $request['type'] = "DEBIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = $this->getDescription($order);

                    $this->walletTransactionNew($request);


                }

                $customer->update(['demonetisation'=>time()]);

                $currentOrder->update(['demonetisation'=>time()]);

            }
        }

        return "success";


    }


    public function getDescription($data,$validity = false){

        $type = $data['type'];
        $expires_on = "";

        if($validity){
            $expires_on = ", Expires On : ".date('d-m-Y H:i:s',$validity);
        }

        if(isset($data['_id']) && !isset($data['order_id'])){
            $data['order_id'] = $data['_id'];
        }

        $description = "Payment for Order ID. ".$data['order_id'].$expires_on;

        try{

            switch ($type) {
                case 'memberships': $description = "Payment for purchase of membership at ".$data['finder_name']." (Order ID. ".$data['order_id'].")".$expires_on; break;
                case 'booktrials': $description = "Payment for purchase of paid trial at ".$data['finder_name']." (Order ID. ".$data['order_id'].")".$expires_on; break;
                case 'workout-session': $description = "Payment for purchase of workout session at ".$data['finder_name']." (Order ID. ".$data['order_id'].")".$expires_on; break;
                case 'diet_plan': $description = "Payment for purchase of diet plan (Order ID. ".$data['order_id'].")".$expires_on; break;
                case 'healthytiffintrail': $description = "Payment for purchase of healthy tiffin trial at (Order ID. ".$data['order_id'].")".$expires_on; break;
                case 'healthytiffinmembership': $description = "Payment for purchase of healthy tiffin subscription at (Order ID. ".$data['order_id'].")".$expires_on; break;
                default:break;
            }

            return $description;

        }catch(Exception $e){

            Log::info("getType Error : ".$type);

            return $description;
        }

        return $description;

    }


   public function getWalletBalance($customer_id,$data = false){

        $customer_id = (int) $customer_id;

        $finder_id = ($data && isset($data['finder_id']) && $data['finder_id'] != "") ? (int)$data['finder_id'] : "";
        $order_type = ($data && isset($data['order_type']) && $data['order_type'] != "") ? $data['order_type'] : "";

        $query = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0);

        if($finder_id && $finder_id != ""){

            $query->where(function($query) use($finder_id) {$query->orWhere('valid_finder_id','exists',false)->orWhere('valid_finder_id',$finder_id);});


        }else{

            $query->where('valid_finder_id','exists',false);
        }

        if(!empty($data['order_type'])){
            $query->where(function($query) use ($data){$query->orwhere('order_type', 'exists', false)->orWhere('order_type', $data['order_type']);});
        }
        if(!empty($data['service_id'])){
            $query->where(function($query) use ($data){$query->orwhere('service_id', 'exists', false)->orWhere('service_id', $data['service_id']);});
        }
        if(!empty($data['duration_day'])){
            $query->where(function($query) use ($data){$query->orwhere('duration_day', 'exists', false)->orWhere('duration_day', $data['duration_day']);});
        }

        $wallet_balance = $query->sum('balance');

        return $wallet_balance;
    }


    public function sendDemonetisationCustomerSms($order){

        if(isset($order->demonetisation)){

            $customer_id = (int)$order->customer_id;

            if($order->logged_in_customer_id){
                $customer_id = (int)$order->logged_in_customer_id;
            }

            $customer_wallet_balance = (int)$this->getWalletBalance($customer_id);

            $order->update(['customer_wallet_balance'=>$customer_wallet_balance]);

            $customersms = new CustomerSms();

            $customersms->demonetisation($order->toArray());
        }

        return "success";

    }


    public function addAmountToReferrer($order){

        Log::info("inside addAmountToReferrer function");

        $customer = \Customer::find((int)$order['customer_id']);

        $customer_referral_count = 0;

        // $customer_ids = \Customer::where('contact_no','LIKE','%'.substr($order['customer_phone'], -10).'%')->lists('_id');

        // if(!empty($customer_ids)){

        //     $customer_ids = array_map('intval',$customer_ids);

        //     $customer_referral_count = Wallet::whereIn('customer_id',$customer_ids)->where('type','REFERRAL')->where('description', 'Referral fitcashplus to referrer')->count();
        // }

        if($customer_referral_count == 0 && $customer && isset($customer['old_customer']) && !$customer['old_customer'] && isset($customer['referrer_id']) && $customer['referrer_id'] != 0 && isset($order['amount_customer']) && $order['amount_customer'] > 450){

            Log::info("inside first transaction");

            $referrer = \Customer::find((int)$customer->referrer_id);

            $previous_referral_order_ids = Wallet::where('customer_id', $customer['referrer_id'])->where('description', 'Referral fitcashplus to referrer')->lists('order_id');

            $customer_phones = \Order::whereIn('_id', $previous_referral_order_ids)->lists('customer_phone');

            $match = preg_grep('%'.substr($order['customer_phone'], -10).'%', $customer_phones);            

            if(count($match) == 0 && $referrer && ((!isset($customer->contact_no) || !isset($referrer->contact_no)) || (substr($customer->contact_no, -10) != substr($referrer->contact_no, -10)))){

                $wallet_data = [
                    'customer_id' => $customer->referrer_id,
                    'amount' => 250,
                    'amount_fitcash' => 0,
                    'amount_fitcash_plus' => 250,
                    'type' => "REFERRAL",
                    "entry"=>'credit',
                    'description' => "Referral fitcashplus to referrer",
                    'order_id' => $order['_id']
                ];

                $walletTransaction = $this->walletTransaction($wallet_data);

                if($walletTransaction['status'] == 200){

                    $referrer_email =  $referrer->email;

                    $url = $this->getShortenUrl(Config::get('app.website')."/profile/$referrer_email#wallet");

                    $sms_data = [
                        'customer_phone'=>$referrer->contact_no,
                        'friend_name'   =>ucwords($customer->name),
                        'wallet_url'    =>$url
                    ];

                    $customersms = new CustomerSms();
                    if(!(isset($_GET['source']) && $_GET['source'] == 'admin')){
                        $customersms->referralFitcash($sms_data);
                    }
                }
            }
        }

        if($customer){
            $customer->old_customer = true;
            $customer->update();
        }

        return "success";

    }

    public function sendPromotionalNotification($data){

        if(!empty($data['delay']) && $data['delay'] !== 0){
            $data['delay'] = $this->getSeconds($data['delay']);
        }else{
            $data['delay'] == 0;
        }

        $device = \Device::where('customer_id', $data['customer_id'])
            ->where('reg_id','exists',true)
            ->whereIn('type', ["android", "ios"])
            ->orderBy('updated_at', 'desc')
            ->first();

        if($device){

            $to = array($device['reg_id']);
            $device_type = $device['type'];

        }else{
            
            \Log::info("no device id");
            return "no device id";
        }

        $data['promo_id'] = !empty($data['promo_id']) ? $data['promo_id'] : 9999;
        $data['couponcode'] = !empty($data['couponcode']) ? $data['couponcode'] : "";
        $data['deeplink'] = !empty($data['deeplink']) ? $data['deeplink'] : "";
        $data['title'] = !empty($data['title']) ? $data['title'] : "";
        $data['text'] = !empty($data['text']) ? $data['text'] : "";
        $data['text'] = !empty($data['body']) ? $data['body'] : "";
        $data['unique_id'] = !empty($data['unique_id']) ? $data['unique_id'] : '593a9380820095bf3e8b4568';
        $data['label'] = !empty($data['label']) ? $data['label'] : "label";

        if($device_type == "android"){
            $notification_object = array("notif_id" => $data['promo_id'],"notif_type" => "promotion", "notif_object" => array("promo_id"=>$data['promo_id'],"promo_code"=>$data['couponcode'],"deep_link_url"=>"ftrnty://ftrnty.com".$data['deeplink'], "unique_id"=> $data['unique_id'],"title"=> $data["title"],"text"=> $data["text"]));
        }else{
            $notification_object = array("aps"=>array("alert"=> array("body" => $data["title"]), "sound" => "default", "badge" => 1), "notif_object" => array("promo_id"=>$data['promo_id'],"notif_type" => "promotion","promo_code"=>$data['couponcode'],"deep_link_url"=>"ftrnty://ftrnty.com".$data['deeplink'], "unique_id"=> $data['unique_id'],"title"=> $data["title"],"text"=> $data["text"]));
        }

        $notificationData = array("to" =>$to,"delay" =>$data['delay'],"label"=>$data['label'],"app_payload"=>$notification_object);

        $route  = $device_type;

        $sidekiq = new Sidekiq();

        $result  = $sidekiq->sendToQueue($notificationData,$route);

        if($result['status'] == 200){
            return $result['task_id'];
        }else{
            return $result['status'].':'.$result['reason'];
        }

    }

    public function hitURLAfterDelay($url, $delay = 0, $label = 'label', $priority = 0){

        Log::info("Scheduling url:$url");
        // Log::info("delay: $delay");

        if($delay !== 0){
            $delay = $this->getSeconds($delay);
        }



        $payload = array('url'=>$url,'delay'=>$delay,'priority'=>$priority,'label' => $label);

        $route  = 'outbound';

        $sidekiq = new Sidekiq();
        $result  = $sidekiq->sendToQueue($payload,$route);

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
    
    public function getTime(){
        return time();
    }

    public function scheduleCommunication($data, $method, $class_name){
        $transaction_data = $data[0];
        $transaction_id = isset($transaction_data['_id']) ? $transaction_data['_id'] : $transaction_data['id'];
        $delay = $data[1];
        // $transaction_type = (isset($transaction_data['order_id'])) ? "order" : "trial";
        $key = rand(100000000, 999999999);

        if(in_array($method, ["before3HourSlotBooking", "orderRenewalMissedcall", "sendPaymentLinkAfter3Days", "sendPaymentLinkAfter7Days", "sendPaymentLinkAfter45Days", "purchaseAfter10Days", "purchaseAfter30Days", "abandonCartCustomerAfter2hoursFinder"])){
            $transaction_type = "order";
        }else{
            $transaction_type = "trial";
        }

        if($transaction_type == "order"){
            $transaction = \Order::find($transaction_id);
            
        }else{
            $transaction = \Booktrial::find($transaction_id);
        }

        $communication_keys = isset($transaction->communication_keys)?$transaction->communication_keys:array();
        $communication_variable = "$class_name-$method";
        $communication_keys[$communication_variable] = $key;
        

        $transaction['communication_keys'] = $communication_keys;

        $transaction->update();

        Log::info("Scheduling url from scheduleCommunication function");
        return $this->hitURLAfterDelay(Config::get('app.url')."/communication/$class_name/$transaction_type/$method/$transaction_id/$key", $delay);

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

    public function getWalletQuery($request){

        Log::info('---------------request-------------------',$request);

        $query = Wallet::active()->where('customer_id',(int)$request['customer_id'])->where('balance','>',0);

        if(!empty($request['extended_validity'])){
            $query->where('restricted_for', '!=', 'upgrade');
        }

        if(isset($request['finder_id']) && $request['finder_id'] != ""){

            $finder_id = (int)$request['finder_id'];

            $finder = \Finder::find($finder_id);

            $conditionData = [];

            $conditionData['finder_category_id'] = $finder->category_id;

            $fitcashCoupons = \Fitcashcoupon::select('_id','code','condition')->where('condition','exists',true)->get();

            if(count($fitcashCoupons) > 0){

                $fitcashCoupons = $fitcashCoupons->toArray();

                foreach ($fitcashCoupons as $coupon) {

                    $code = $coupon['code'];

                    $condition_array = [];

                    foreach ($coupon['condition'] as $condition) {

                        $operator = $condition['operator'];
                        $field = $condition['field'];
                        $value = $condition['value'];

                        switch ($operator) {
                            case 'in':

                                if(isset($conditionData[$field]) && in_array($conditionData[$field],$value)){
                                    $condition_array[] = 'true';
                                }else{
                                    $condition_array[] = 'false';
                                }

                                break;

                            case 'not_in':

                                if(isset($conditionData[$field]) && !in_array($conditionData[$field],$value)){
                                    $condition_array[] = 'true';
                                }else{
                                    $condition_array[] = 'false';
                                }

                                break;
                        }

                    }

                    if(in_array('false', $condition_array)){
                        $query->where('coupon','!=',$code);
                    }

                }
            }


            $query->where(function($query) use($finder_id) {$query->orWhere('valid_finder_id','exists',false)->orWhere('valid_finder_id',$finder_id);});


        }else{

            $query->where('valid_finder_id','exists',false);
        }

        if(!empty($request['service_id'])){
            $query->where(function($query) use($request) {$query->orWhere('service_id','exists',false)->orWhere('service_id', $request['service_id']);});
        }
        
        if(!empty($request['duration_day'])){
            $query->where(function($query) use($request) {$query->orWhere('duration_day','exists',false)->orWhere('duration_day', $request['duration_day']);});
        }

        Log::info("wallet debit query");
        Log::info($request);

        if(!empty($request['order_type'])){
            $query->where(function($query) use ($request){$query->orwhere('order_type', 'exists', false)->orWhere('order_type', $request['order_type']);});
        }

        return $query;

    }


    public function addUpdateDevice($customer_id = false){

        $header_array = [
            "Device-Type"=>"",
            "Device-Model"=>"",
            "App-Version"=>"",
            "Os-Version"=>"",
            "Device-Token"=>"",
            "Device-Id"=>""
        ];

        $flag = false;

        foreach ($header_array as $header_key => $header_value) {

            $value = Request::header($header_key);

            if($value != "" && $value != null && $value != 'null'){
               $header_array[$header_key] =  $value;
               $flag = true;
            }
            
        }

        if($customer_id && $customer_id != ""){
          $header_array['customer_id'] = (int)$customer_id;
        }

        $data = [];

        if($customer_id && $customer_id != ""){
          $data['customer_id'] = (int)$customer_id;
        }

        $data['device_id'] = $header_array['Device-Id'];
        $data['os_version'] = $header_array['Os-Version'];
        $data['app_version'] = $header_array['App-Version'];
        $data['device_model'] = $header_array['Device-Model'];
        $data['type'] = $header_array['Device-Type'];
        $data['device_type'] = $header_array['Device-Type'];
        $data['reg_id'] = $header_array['Device-Token'];
        $data['gcm_reg_id'] = $header_array['Device-Token'];

        $this->addRegId($data);

        unset($data['type']);
        unset($data['device_id']);

        if($customer_id && $customer_id != ""){
          unset($data['customer_id']);
        }

        return $data;

    }

    function getCustomerDiscount(){
        
        $jwt_token = Request::header('Authorization');
        $customer_email = "";
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = customerTokenDecode($jwt_token);
            $customer_email = $decoded->customer->email;
        }
        
        if(in_array($customer_email, Config::get('app.corporate_login.emails'))){
            return Config::get('app.corporate_login.discount');
        }else{
            return 0;
        }
    }

    function checkCorporateLogin($customer_email = ""){

        if(!isset($customer_email) ||  $customer_email != ""){
            $jwt_token = Request::header('Authorization');
            if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
                $decoded = customerTokenDecode($jwt_token);
                // Log::info("Customer Decoded Token",$decoded->toArray());
                $customer_email = $decoded->customer->email;
            }
        }
        if(in_array($customer_email, Config::get('app.corporate_login.emails'))){
            return true;
        }
        return false;
        
    }

    function checkCorporateEmail($customer_email){

        if(in_array($customer_email, Config::get('app.corporate_login.emails'))){
            return true;
        }

        return false;
        
    }

    function sendCorporateMail($data){
        if(isset($data['logged_in_customer_id'])){

            $logged_in_customer_id = $data['logged_in_customer_id'];
            
            $customer = Customer::find($logged_in_customer_id);

            if($customer){
                if(in_array($customer->email, Config::get('app.corporate_login.emails'))){
                    $data['corporate_email'] = $customer->email;
                    $data['corporate_name'] = $customer->name;
                    
                    $findermailer = new FinderMailer();

                    $findermailer->sendOrderCorporateMail($data);
                
                }
            }
        }

    }

    public function displayEmi($data){
		$bankNames=array();
		$bankList= array();
	 	$emiStruct = Config::get('app.emi_struct');
		$response = array(
			"bankList"=>array(),
			"emiData"=>array(),
			"higerMinVal" => array()
			);
		foreach ($emiStruct as $emi) {
			if(isset($data['bankName']) && !isset($data['amount'])){
				if($emi['bankName'] == $data['bankName']){
					if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
					// Log::info("inside1");
					$emiData = array();
						$emiData['total_amount'] =  "";
						$emiData['emi'] ="";
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
					array_push($response['emiData'], $emiData);
				}
			
			}elseif(isset($data['bankName'])&&isset($data['amount'])){
					if($emi['bankName'] == $data['bankName'] && $data['amount']>=$emi['minval']){
						// Log::info("inside2");
						$emiData = array();
						if(!in_array($emi['bankName'], $bankList)){
							array_push($bankList, $emi['bankName']);
						}
						$emiData['total_amount'] =  (string)round($data['amount']*(100+$emi['rate'])/100, 2);
						$emiData['emi'] =(string)round($emiData['total_amount']/$emi['bankTitle'], 2);
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
						array_push($response['emiData'], $emiData);
					}elseif($emi['bankName'] == $data['bankName']){
						$emiData = array();
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['minval'] = (string)$emi['minval'];
						array_push($response['higerMinVal'], $emiData);
						break;
					}
			}elseif(isset($data['amount']) && !(isset($data['bankName']))){
				if($data['amount']>=$emi['minval']){
					if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
					// Log::info("inside3");
					$emiData = array();
					$emiData['total_amount'] =  (string)round($data['amount']*(100+$emi['rate'])/100, 2);
					$emiData['emi'] =(string)round($emiData['total_amount']/$emi['bankTitle'], 2);
					$emiData['months'] = (string)$emi['bankTitle'];
					$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
					$emiData['rate'] = (string)$emi['rate'];
					$emiData['minval'] = (string)$emi['minval'];
					array_push($response['emiData'], $emiData);
				}else{
					$key = array_search($emi['bankName'], $bankNames);
					if(!is_int($key)){
						array_push($bankNames, $emi['bankName']);
						$emiData = array();
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['minval'] = (string)$emi['minval'];
						array_push($response['higerMinVal'], $emiData);
					}
				}
			}else{
				if(!in_array($emi['bankName'], $bankList)){
						array_push($bankList, $emi['bankName']);
					}
				// Log::info("inside4");
				$emiData = array();
						$emiData['total_amount'] =  "";
						$emiData['emi'] ="";
						$emiData['months'] = (string)$emi['bankTitle'];
						$emiData['bankName'] = $emi['bankName'];
						$emiData['bankCode'] = $emi['bankCode'];
						$emiData['rate'] = (string)(string)$emi['rate'];
						$emiData['minval'] = (string)$emi['minval'];
				array_push($response['emiData'], $emiData);
			}
		}
		$response['bankList'] = $bankList;
	    return $response;
	}
    function checkFinderState($finder_id){
        $response = array('status'=>200, 'message'=>'Can book Session or Membership');
        if(in_array($finder_id,Config::get('app.fitternity_vendors'))){
            return $response;
        }
        Finder::$withoutAppends = true;

        $finder = Finder::find((int)$finder_id);

        $state_array = ["closed","temporarily_shut"];

        if(isset($finder['flags']['state']) && in_array($finder['flags']['state'],$state_array)){

            $response = array('status'=>400, 'message'=>'Connot book Session or Membership');
        }

        return $response;

    }

    public function createFolder($path){

        if(!is_dir($path)){
            mkdir($path, 0777);
            chmod($path, 0777);
        }   

        return $path;
    }


    public function createQrCode($text){

        $folder_path = public_path().'/qrcodes/';

        $this->createFolder($folder_path);

        $filename = time().'.png';

        $file_path = $folder_path.$filename;

        \QrCode::format('png')->size(200)->margin(0)->generate($text, $file_path);

        chmod($file_path, 0777);

        $aws_filename = $filename;

        $s3 = \AWS::get('s3');
        $s3->putObject(array(
            'Bucket'     => Config::get('app.aws.bucket'),
            'Key'        => Config::get('app.aws.qrcode.path').$aws_filename,
            'SourceFile' => $file_path,
        ));

        unlink($file_path);

        return $aws_filename;

    }

    function generateRandomString($length = 5) {
        
         $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
         $charactersLength = strlen($characters);
        
         $randomString = '';
        
         for ($i = 0; $i < $length; $i++) {
        
             $randomString .= $characters[rand(0, $charactersLength - 1)];
        
         }
        
         return $randomString;
     }

     public function isConvinienceFeeApplicable($data, $type="order"){
        Log::info(debug_backtrace()[1]['function']);
        Log::info("Data for isConvinienceFeeApplicable");
        Log::info($data);
        
        if($type == "order"){
            $flags = isset($data['ratecard_flags']) ? $data['ratecard_flags'] : array();
            if(isset($data['customer_source']) && $data["customer_source"] == "kiosk"){
                return false;
            }
        }else{
            if(empty($data['flags'])){
                return false;
            }
            $flags = $data['flags'];
        }
        $finder = Finder::find((int) $data["finder_id"]);
        
        if((isset($data['session_payment']) && $data['session_payment'])||
           ($this->vendor_token)||
           (in_array($data['finder_id'],Config::get('app.vendors_without_convenience_fee')))||
           (isset($flags) && isset($flags["pay_at_vendor"]) && $flags["pay_at_vendor"] === True)||
           (!empty($data['type']) && in_array($data['type'], ["workout session", "workout-session", "trial", "booktrials","events"]))||
           (!empty($data['paymentmode_selected']) && $data['paymentmode_selected']== 'pay_at_vendor'))
        {
            return false;
        }
        if(!empty($data['type']) && $data['type'] == 'events'){
            return false;
        }
        if(!empty($finder['brand_id']) && $finder['brand_id'] == 88 && $finder['city_id'] == 2){
            return false;
        }
        
        // if((!empty($data['type']) && in_array($data['type'], ["memberships", "membership", "package", "packages", "healthytiffinmembership"]))||(isset($finder) && $finder["commercial_type"] != 0)) {
        //     Log::info("returning true");
        //     return true;
        // } 
        
        Log::info("returning true");
        return true;
    }

    public function trialBookedLocateScreen($data = false){

        if(isset($data['finder_id'])){

            Finder::$withoutAppends=true;

            $finder = Finder::find((int)$data['finder_id']);
        }

        $fitcash_amount = 150;

        $response['message_title'] = "DONE!";

        $response['message'] = 'Great! Your session has been booked. Enjoy your workout!';

        if(isset($data['booked_locate']) && $data['booked_locate'] == 'locate'){
            $response['message'] = 'Great! Your session has been activated. Enjoy your workout!';
        }

        $response['title'] = 'MAKE MOST OF FITTERNITY!';

        $response['review'] = [
            'image'=>'https://b.fitn.in/gamification/reward/cashback.jpg',
            'amount'=>(string)$fitcash_amount,
            'title1'=>strtoupper('<b>review</b>'),
            'title2'=>strtoupper('<b>₹'.$fitcash_amount.'</b> FITCASH+'),
            'description'=>'<b>Post    your    trial</b>    make    sure    you    review    your    experience    on    this    tab    &    get    <b>₹'.$fitcash_amount.'    Fitcash+</b>    in    your    Fitternity    Wallet    that    can    be    used    to    purchase    your    membership',
        ];

        $response['rewards'] = [
            'title'=>strtoupper('use    fitcash+    to    buy    membership    &    win    below    rewards'),
            'description'=>'Buy    Membership    at    <b>lowest    price</b>    &    choose    a    complimentary    rewad    from    the    options    below',
            'items'=>[
                [
                    'title'=>'Instant Cashback',
                    'image'=>'https://b.fitn.in/gamification/reward/cashback.jpg',
                    'worth'=>'worth ₹ 2500'
                ],
                [
                    'title'=>'Merchandise Kit',
                    'image'=>'https://b.fitn.in/gamification/reward/fitness_kit.jpg',
                    'worth'=>'worth ₹ 2250'
                ],
                [
                    'title'=>'Diet Consultation',
                    'image'=>'https://b.fitn.in/gamification/reward/diet_plan.jpg',
                    'worth'=>'worth ₹ 1499'
                ]
            ]
        ];

        if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && isset($finder['brand_id']) && $finder['brand_id'] == 66 && isset($finder['city_id']) && $finder['city_id'] == 3){

            $response['title'] = "";
            $response['message'] = "";
        }

        return $response;
    }

    public function membershipBookedLocateScreen($data){

        if(isset($data['finder_id'])){

            Finder::$withoutAppends=true;

            $finder = Finder::find((int)$data['finder_id']);
        }

        $response['message_title'] = "DONE!";

        $response['message'] = "You are good to go! your <b>".ucwords($data['service_duration'])." ".ucwords($data['service_name'])."</b> membership has been confirmed";

        if(isset($data['membership_locate']) && $data['membership_locate'] == 'locate'){
            $response['message'] = "You are good to go! your <b>".ucwords($data['service_duration'])." ".ucwords($data['service_name'])."</b> membership has been activated";
        }

        $response['message'] .= "<br/><br/><b>To claim your reward, access your user profile by downloading Fitternity app/Login on fitternity.com</b>";

        $response['features'] = [];

        $response['features'][] = [
            'image'=>'https://b.fitn.in/global/Tab-app-success-page/tab-membership-success-1.png',
            'title1'=>strtoupper('fitternity    profile'),
            'title2'=>strtoupper('on    app    &    website'),   
            'description'=>"&#9679; <b>Track</b>    your    FitCash    wallet    balance<br/>&#9679;    <b>Renew</b>    membership    with    best    discount    &    offers<br/>&#9679;    <b>Upgrade</b>    membership    by    extending    the    duration    at    initial    price",
            'type'=>'profile'
        ];

        $response['features'][] = [
            'image'=>'https://b.fitn.in/global/Tab-app-success-page/membership-success-2.png',
            'title1'=>strtoupper('<b>Online    diet</b>'),
            'title2'=>strtoupper('<b>consultation</b>'),
            'description'=>'Make    the    most    of    your    membership,    with    <b>Fitternity’s    Online    Diet    Consultation</b>    to    improve    your    workout    performance',
            'type'=>'diet_plan'
        ];

        $response['features'][] = [
            'image'=>'https://b.fitn.in/global/Tab-app-success-page/membership-success-3.png',
            'title1'=>strtoupper('beat    monotony'),
            'title2'=>strtoupper('<b>pay-per-session</b>'),
            'description'=>'<b>Don’t    let    your    workout    be    monotonous.</b>    Try    different    workouts    around    you    by    only    paying    per    session!',
            'type'=>'pay_per_session'
        ];

        if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && isset($finder['brand_id']) && $finder['brand_id'] == 66 && isset($finder['city_id']) && $finder['city_id'] == 3){

            $response['features'] = [];
            $response['message'] = "";
        }

        return $response;
    }

    public function getVendorTrainer($finder_id){

        $finder = Finder::find($finder_id);

        $assisted_by = [];

        if($finder && isset($finder['trainer_contacts']) && !empty($finder['trainer_contacts'])){

            foreach ($finder['trainer_contacts'] as $key => $value) {

                $array = $value;

                $array['id'] = $value['email'];
                $array['name'] = ucwords($value['name']);

                $assisted_by[] = $array;
            }

        }

        $assisted_by[] = [   
            'id'=>'others',
            'name'=>'Others',
            'email'=>'',
            'mobile'=>''
        ];

        return $assisted_by;
    }

    public function addAssociateAgent($order){

        if(!isset($order->paymentLinkEmailCustomerTiggerCount) && isset($order->type) && $order->type == 'memberships'){

            $date = new \DateTime(date('d-m-Y H:i:s',strtotime("-1 month",time())));

            $agentPresentOrder = \Order::where('status','!=','1')
                ->whereIn('type',['memberships'])
                ->where('customer_id',(int) $order['customer_id'])
                ->where('created_at','>=',$date)
                ->where('person_followingup','exists',true)
                ->where('person_followingup','!=','')
                ->where('paymentLinkEmailCustomerTiggerCount','exists',true)
                ->orderBy('_id','ASC')->first();

            if($agentPresentOrder){

                $order->person_followingup = $agentPresentOrder->person_followingup;

                if(isset($agentPresentOrder->source_of_membership) && $agentPresentOrder->source_of_membership != ""){
                    $order->source_of_membership = $agentPresentOrder->source_of_membership;
                }
                
                $order->auto_associate_agent_date = time();
                $order->update();
            }
        }

        return 'success';

    }

    public function createWorkoutSession($order_id, $isThirdP=false){
        
        $order = \Order::find($order_id);

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

        if($isThirdP) {
            $data['third_party'] = $order['third_party'];
            $data['third_party_details'] = $order['third_party_details'];
            $data['third_party_acronym'] = $order['third_party_acronym'];
        }

        /*if(isset($order->pay_later) && $order->pay_later){
            $data['premium_session'] = true;
            $data['payment_done'] = false;
        }*/

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

        if(!empty($order['service_id'])){
            $data['service_id'] = (int)$order['service_id'];
        }

        if(!empty($order['ratecard_id'])){
            $data['ratecard_id'] = (int)$order['ratecard_id'];
        }

        $workout_session_fields = ['customers_list', 'pay_later'];
        
        foreach($workout_session_fields as $field){
            if(isset($order[$field])){
                $data[$field] = $order[$field];
            }
        }

        $fitapi = new Fitapi();
        Log::info('before storeBookTrial: ', [$isThirdP]);
        $storeBooktrial = $fitapi->storeBooktrial($data, $isThirdP);

        if($storeBooktrial['status'] == 200){

            return Response::json($storeBooktrial['data'],200);

        }else{

            return Response::json(['status' => 400, "message" => "Internal Error Please Report"],400);
        }
    }

    public function hasPendingPayments(){

        if(Request::header('Authorization')){
			$decoded                            =       decode_customer_token();
            $customer_email                     =       $decoded->customer->email;
            $customer_id                        =       $decoded->customer->_id;
            // $customer_phone                     =       isset($decoded->customer->contact_no) ? $decoded->customer->contact_no : "";
            $pending_payment = \Booktrial::where('type', 'workout-session')->where('post_trial_verified_status', '!=', 'no')->where(function ($query) use($customer_email, $customer_id) { $query->orWhere('customer_email', $customer_email)->orWhere("logged_in_customer_id", $customer_id);})->where('going_status_txt','!=','cancel')->where('payment_done', false)->first(['_id', 'amount', 'order_id', 'finder_name']);

			if(count($pending_payment) > 0){
                $order = \Order::find($pending_payment['order_id'], ['txnid']);
				return [
                    'header'=>'Pending Payment',
                    'text'=>'Please complete your pending payment',
                    'trial_id'=>$pending_payment['_id'],
                    'amount'=>$pending_payment['amount'],
                    'txnid'=>$order['txnid'],
                    'finder_name'=>$pending_payment['finder_name']
                ];
			}else{
				return false;
			}
		}else{
			return false;
		}

    }

    public function addToGroup($data){

        Log::info("inside addToGroup");
        Log::info($data);
        

        $validate = $validate = $this->validateGroupId($data);

        Log::info("validate group data in addToGroup");

        Log::info($validate);

        if($validate['status']==400 && isset($validate['group_id'])){
            
            Log::info("invalid group id");
            
            return "";
        
        }

        // if($validate['status']==400 && isset($validate['group_id'])){

        //     $group = \Customergroup::where('group_id', strtoupper($data['group_id']))->first();

        //     Log::info("group");
            
        //     Log::info($group);
            
        //     $this->sendGroupCommunication(['group'=>$group,'customer_id'=>$data['customer_id']]);
            
        //     return $validate['group_id'];
        
        // }

        $group = \Customergroup::where('members.customer_id', $data['customer_id'])->first();
        
        if($group){
            Log::info("returning old group id");
            $this->sendGroupCommunication(['group'=>$group,'customer_id'=>$data['customer_id']]);
            return $group['group_id'];
        }
        
        if(isset($data['group_id']) && $data['group_id']){
            
            $group = \Customergroup::where('group_id', strtoupper($data['group_id']))->where('members.customer_id', '!=', $data['customer_id'])->first();

            if($group){

                $members = $group->members;
                
                array_push($members, ['customer_id'=>$data['customer_id'], 'order_id'=>$data['order_id']]);
    
                $group->members = $members;
    
                $group->status = "1";
    
                $group->save();
    
                Log::info("Added to group");
    
                Log::info($group);
                
                $this->sendGroupCommunication(['group'=>$group,'customer_id'=>$data['customer_id']]);

            }
        
            return $data['group_id'];

        }

        
        $group = new \Customergroup();
            
        $group->group_id = $this->getUniqueGroupId();

        $group->members = [['customer_id'=>$data['customer_id'], 'order_id'=>$data['order_id']]];

        $group->status = "0";
        
        $group->save();

        Log::info("created a new group");

        Log::info($group);

        $this->sendGroupCommunication(['group'=>$group->toArray(),'customer_id'=>$data['customer_id']]);
        
        return $group->group_id;

    }

    public function getUniqueGroupId(){

        $id = "GRP".$this->generateRandomString(4);

        $group = \Customergroup::where('group_id', $id)->count();

        if($group){
        
            return $this->getUniqueGroupId();
       
        }

        return $id;
    
    }

    public function isGroupId($code){
        
        // return is_numeric($code);

        return strtoupper(substr($code, 0, 3)) == 'GRP';

    }

    public function validateGroupId($data){

        // if(!isset($data['customer_id'])){
        //     $jwt_token = Request::header('Authorization');
            
    
        //     if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
    
        //         $decoded = $this->customerTokenDecode($jwt_token);

        //         Log::info($data);
                
        //         $data['customer_id'] = (int)$decoded->customer->_id;
            
        //     }else{
                
        //         return array('status'=>400, 'message'=>'You need to log in');
            
        //     }
        // }

        // $group = \Customergroup::where('members.customer_id', $data['customer_id'])->first();

        // Log::info('Invalid group');

        // Log::info($group);

        // if($group){

        //     return array('status'=>400, 'message'=>'You are already a member of a group', 'error_message'=>'You are already a member of a group', 'group_id'=>$group['group_id']);

        // }

        if(!isset($data['group_id']) || $data['group_id'] == null){
            return array('status'=>400, 'message'=>'Empty group code', 'error_message'=>'Code empty');
        }

        $group = \Customergroup::where('group_id', strtoupper($data['group_id']))->first();

        Log::info("Valid group");

        Log::info($group);

        if($group){

            return array('status'=>200, 'message'=>'Group code applied successfully');
            
        }else{
            
            return array('status'=>400, 'message'=>'Invalid group code', 'error_message'=>'Invalid group code', 'group_id'=>$data['group_id']);
        
        }

    }

    public function sendGroupCommunication($data){
        return;
        if(!(isset($_GET['source']) && $_GET['source'] == 'admin')){

            $customer_id = $data['customer_id'];
    
            $group = $data['group'];
    
            $customersms = new CustomerSms();
            Log::info("sendGroupCommunication");
            Log::info($data);
            
            foreach($group['members'] as $member){
    
                if($member['customer_id'] == $customer_id){
                 
                    $order = \Order::find(intval($member['order_id']));
					Log::info($member['order_id']);
                    $new_member_name = $order->customer_name;
    
                    $customersms->addGroupNewMember(['customer_phone'=>$order->customer_phone,'customer_name'=>$order->customer_name,'vendor_name'=>$order->finder_name, 'group_id'=>$group['group_id']]);
    
                }
    
            }
    
            foreach($group['members'] as $member){
                
                if($member['customer_id'] != $customer_id){
                    
                    $order = \Order::find($member['order_id'], ['customer_phone', 'customer_name']);
                    
                    $customersms->addGroupOldMembers(['customer_phone'=>$order->customer_phone,'customer_name'=>$order->customer_name, 'new_member_name'=>$new_member_name]);
    
                }
    
            }
        }

    }
    public function checkFitternityCustomer($customer_email, $customer_phone){
        
        $beforeTime 	=	date('d-m-Y H:i:s', strtotime(Carbon::now()->addHours(-4)));
        
        $transaction = \Transaction::where('created_at', '<', new \DateTime($beforeTime))->where(function($query) use ($customer_email, $customer_phone){ return $query->orWhere('customer_phone', 'LIKE', '%'.substr($customer_phone, -10).'%')->orWhere('customer_email', $customer_email);})->first();

        if($transaction){
            
            Log::info("returning true");
        
            return true;
        
        }
        
        Log::info("returning false");
        return false;

    }


    public function checkFitternityCustomer1($customer_email, $customer_phone, $date){
        
        $beforeTime 	=	date('d-m-Y H:i:s', strtotime(Carbon::createFromFormat('Y-m-d H:i:s', $date)->addHours(-4)));

        Log::info($beforeTime);
        
        $transaction = \Transaction::where('created_at', '<', new \DateTime($beforeTime))->where(function($query) use ($customer_email, $customer_phone){ return $query->orWhere('customer_phone', 'LIKE', '%'.substr($customer_phone, -10).'%')->orWhere('customer_email', $customer_email);})->first();

        if($transaction){
            
            Log::info("returning true");
        
            return true;
        
        }
        
        Log::info("returning false");
        return false;

    }

    public function fitCode($data){

        $fit_code = false;

        // if(isset($data['vendor_code']) && $data['type'] != 'workout-session'){
        if(isset($data['vendor_code']) ){

            $fit_code = true;

            if(!empty($data['post_trial_status_updated_by_fitcode'])){
                $fit_code = false;
            }

            if(!empty($data['post_trial_status_updated_by_qrcode'])){
                $fit_code = false;
            }

            // if(!isset($data['post_trial_status_updated_by_fitcode']) && !isset($data['post_trial_status_updated_by_lostfitcode'])){

            //     if(isset($data['schedule_date_time']) && $data['schedule_date_time'] != "" && time() <= strtotime('+48 hours', strtotime($data['schedule_date_time']))){

            //         $fit_code = true;
            //     }
            // }

            if(isset($data['manual_order']) && $data['manual_order']){
                $fit_code = false;
            }

            if(isset($data['is_tab_active']) && $data['is_tab_active']){
                $fit_code = false;
            }
        }
        
        return $fit_code;

    }

    public function customerHome(){

        $decoded = decode_customer_token();
        // Log::info($decoded);
        $customer_id = $decoded->customer->_id;

        $response = null;
        $stage = '';
        $booktrial = false;
        $state = '';
        $time_left = 0;
        $card_message = "Congratulations on completing your trial";
        
        $booktrial = \Booktrial::where('customer_id',$customer_id)
            ->whereIn('type',['booktrials','3daystrial'])
            ->where('going_status_txt','!=','cancel')
            ->where('booktrial_type','auto')
            ->where('schedule_date_time','>=',new \MongoDate(strtotime("-21 days")))
            ->where(function($query){$query->orWhere('vendor_code','exists',true)->orWhere('is_tab_active','exists',true);})
            // ->orderBy('schedule_date_time', 'desc')
            ->orderBy('_id', 'desc')
            ->first();
        if(!$booktrial){
            return $response;
        }
        if(strtotime($booktrial["schedule_date_time"]) > time()){

            $stage = 'before_trial';
            $state = 'booked_trial';
            
            $time_left = strtotime($booktrial->schedule_date_time) - time();
        }


        if($stage == ''){

            // $booktrial = false;

            // $booktrial = \Booktrial::where('customer_id',$customer_id)
            //     ->whereIn('type',['booktrials','3daystrial'])
            //     ->where('going_status_txt','!=','cancel')
            //     ->where('booktrial_type','auto')
            //     ->where('schedule_date_time','<=',new \MongoDate(time()))
            //     ->where(function($query){$query->orWhere('vendor_code','exists',true)->orWhere('is_tab_active','exists',true);})
            //     ->orderBy('schedule_date_time', 'desc')
            //     ->first();

            if(strtotime($booktrial["schedule_date_time"]) < time()){

                $order_count = \Order::active()
                    ->where('customer_id',$customer_id)
                    ->where('type','memberships')
                    ->where('success_date','>=',new \MongoDate(strtotime($booktrial['schedule_date_time'])))
                    ->count();

                if($order_count > 0){

                    return $response;
                }

                $stage = 'after_trial';

                $state = 'trial_done';

            }
        }

        /*if($stage == ''){

            $booktrial = false;

            $booktrial = \Booktrial::where('customer_id',$customer_id)
                ->whereIn('type',['booktrials','3daystrial'])
                ->where('going_status_txt','!=','cancel')
                ->where('booktrial_type','auto')
                ->where('schedule_date_time','>=',new \MongoDate(strtotime("+21 days")))
                ->orderBy('schedule_date_time', 'desc')
                ->first();

            if($booktrial){

                $stage = 'buy_membership';

                $state = 'trial_attended';

                $order_count = \Order::active()
                    ->where('customer_id',$customer_id)
                    ->where('type','memberships')
                    ->count();

                if($order_count > 0){
                    $state = 'membership_purchased';
                }
            }
        }*/

        if($booktrial && $stage != ""){

            $unset_dates = \Booktrial::$unset_dates;

            $unset_keys = [];

            foreach ($unset_dates as $date){
                if(isset($booktrial[$date]) && $booktrial[$date]==''){
                    $unset_keys[] = $date;
                }
            }

            if(count($unset_keys) > 0){
                $booktrial->unset($unset_keys);
            }

            $fit_code_status = $this->fitCode($booktrial->toArray());

            $fitcash = $this->getFitcash($booktrial->toArray());

            $category_calorie_burn = 300;
            \Service::$withoutAppends=true;
            $service = \Service::find((int)$booktrial['service_id']);

            if($service){

                $sericecategorysCalorieArr = Config::get('app.calorie_burn_categorywise');

                $service_category_id = (isset($service['servicecategory_id']) && $service['servicecategory_id'] != "") ? $service['servicecategory_id'] : 0;

                if(isset($service['calorie_burn']) && $service['calorie_burn']['avg'] != 0){
                    $category_calorie_burn = $service['calorie_burn']['avg'];
                }else{
                    if(isset($sericecategorysCalorieArr[$service_category_id])){
                        $category_calorie_burn = $sericecategorysCalorieArr[$service_category_id];
                    }
                }

            }

            $is_tab_active = (isset($booktrial['is_tab_active']) && $booktrial['is_tab_active']) ? true : false;

            /*if($is_tab_active){
                $fitcash = 250;
            }*/

            if($stage == 'before_trial'){

                $card_message = "Provide this & get your <b>FITCODE</b> from Gym/Studio to unlock Rs ".$fitcash." flat discount.<br>Use this discount to buy your membership at lowest price";

                if($is_tab_active){

                    $card_message = "Punch this code on the tab available at Gym/Studio to unlock Rs ".$fitcash." flat discount .<br>Use this discount to buy your membership at lowest price";
                }
            }

            if($stage == 'after_trial'){

                $card_message = "Yes? Enter your <b>FITCODE</b> to get <b>Rs ".$fitcash." flat discount</b><br/>No? You can always reschedule";

                if($is_tab_active){

                    $card_message = "Let us know now & we'll give Rs ".$fitcash." flat discount to buy your membership at lowest price";
                }

            }

            if(isset($booktrial['post_trial_status']) && $booktrial['post_trial_status'] == 'attended'){

                if(!$fit_code_status){

                    $card_message = "Congratulations on completing your trial";
                    
                    if(isset($booktrial['post_trial_status_updated_by_fitcode']) || isset($booktrial['post_trial_status_updated_by_kiosk'])){

                        $card_message = "Congratulations <b>₹".$fitcash." FitCash</b> has been added in your wallet.Use it to get a discount on your Membership";
                    }

                    $state = 'trial_attended';
                }
            }

            $response = [];
            $response['stage'] = $stage;
            $response['state'] = $state;
            $response['fit_code_status'] = $fit_code_status;
            $response['booktrial_id'] = (int)$booktrial['_id'];
            $response['finder_id'] = (int)$booktrial['finder_id'];
            $response['finder_slug'] = $booktrial['finder_slug'];
            $response['service_id'] = (int)$booktrial['service_id'];
            $response['finder_name'] = ucwords($booktrial['finder_name']);
            $response['service_name'] = ucwords($booktrial['service_name']);
            $response['ratecard_url'] = Config::get('app.url').'/getmembershipratecardbyserviceid/'.$booktrial['service_id'];
            $response['redirect_url'] = Config::get('app.website').'/servicebuy/'.$booktrial['service_id'];
            $response['verify_fit_code_url'] = Config::get('app.url').'/verifyfitcode/'.$booktrial['_id'].'/';
            $response['lost_fit_code_url'] = Config::get('app.url').'/lostfitcode/'.$booktrial['_id'];
            $response['subscription_code'] = $booktrial['code'];
            $response['fitcash'] = $fitcash;
            $response['card_message'] = $card_message;
            $response['what_to_carry'] = $booktrial['what_i_should_carry'];
            $response['time_left'] = $time_left;
            $response['lat'] = $booktrial['finder_lat'];
            $response['lon'] = $booktrial['finder_lon'];
            $response['calorie_burn'] = $category_calorie_burn;
            $response['calorie_burn_text'] = "Get ready to burn ".$category_calorie_burn." Calories at ".ucwords($booktrial['finder_name']);
            $response['is_tab_active'] = $is_tab_active;


            $ratecard_count = 1;

            $fitapi = New Fitapi();

            // $getRatecardCount = $fitapi->getServiceData($booktrial['service_id']);

            // if($getRatecardCount['status'] != 200){

            //     $ratecard_count = 0;

            // }else{

            //     if(!isset($getRatecardCount['data']) && !isset($getRatecardCount['data']['service']) && !isset($getRatecardCount['data']['service']['ratecard'])){
            //         $ratecard_count = 0;
            //     }

            //     if(isset($getRatecardCount['data']['service']) && isset($getRatecardCount['data']['service']['ratecard']) && empty($getRatecardCount['data']['service']['ratecard'])){
            //         $ratecard_count = 0;
            //     }
            // }

           $ratecard_count = \Ratecard::where('service_id',(int)$booktrial['service_id'])
                ->where('finder_id',(int)$booktrial['finder_id'])
                ->whereIn('type',['membership','packages'])
                ->where('direct_payment_enable','!=','0')
                ->orWhere(function($query){$query->where('expiry_date','exists',true)->where('expiry_date','<=',new \MongoDate(time()));})
                ->orWhere(function($query){$query->where('start_date','exists',true)->where('start_date','>=',new \MongoDate(time()));})
                ->count();

            if($ratecard_count == 0){
                $response['redirect_url'] = Config::get('app.website').'/'.$booktrial['finder_slug'];
            }
            
        }

        return $response;

    }

    public function productsTabCartHomeCustomer($customer_id=null){
    	
    	if(empty($customer_id))
    	{
    		try {
    			$decoded = decode_customer_token();
    			if(!empty($decoded)&&!empty($decoded->customer))
    				$customer_id = $decoded->customer->_id;
    			else return null;
    		} catch (Exception $e) {
    			return null;
    		}
    	}
    	if(!empty($customer_id))
    	{
    		Cart::$withoutAppends=true;
    		return Cart::where("customer_id",intval($customer_id))->with("customer")->first();
    	}
    	else return null;	
    }
    public function getCustomerAddress($customer_id=null){
    	
    	if(empty($customer_id))
    	{
    		try {
    			$decoded = decode_customer_token();
    			if(!empty($decoded)&&!empty($decoded->customer)){
                    $customer_id = $decoded->customer->_id;
                    Log::info($customer_id);
                }
                else return null;
    		} catch (Exception $e) {
    			return null;
    		}
    	}
    	if(!empty($customer_id))
    	{
    		Customer::$withoutAppends=true;
    		return Customer::where("_id",intval($customer_id))->first(['customer_addresses_product']);
    	}
    	else return null;
    }
    public function addCustomerAddress($customer_id=null,$customer_address){
    	
    	if(empty($customer_id))
    	{
    		try {
    			$decoded = decode_customer_token();
    			if(!empty($decoded)&&!empty($decoded->customer))
    				$customer_id = $decoded->customer->_id;
    				else return null;
    		} catch (Exception $e) {
    			return null;
    		}
    	}
    	if(!empty($customer_id))
    	{
    		$added=Customer::where("_id",intval($customer_id))->push('customer_addresses_product',$customer_address);
    		return (!empty($added))?true:false;
    	}
    	else return null;
    }
    
    public function getFitcash($data){

        $finder_id = (int)$data['finder_id'];

        $fitcash = 0;

        if(!empty($data['type']) && in_array($data['type'],['booktrials','3daystrial'])){

            if(!empty($data['is_tab_active']) && $data['is_tab_active'] === true){

                return 250;
            }
        
            \Ratecard::$withoutAppends = true;

            $ratecards = \Ratecard::where('finder_id',$finder_id)->whereIn('type',['membership','packages'])->get();

            $amount = 0;
            $days = 0;
            $fitcash = 300;

            if(!empty($ratecards)){

                foreach ($ratecards as $ratecard) {

                    $new_days = $this->getDurationDay($ratecard);

                    if($new_days >= $days){

                        $days = $new_days;

                        $new_amount = $this->getRatecardAmount($ratecard);

                        if($new_amount >= $amount){
                            $amount = $new_amount;
                        }
                    }

                }

                if($amount >= 10000){
                    $fitcash = 500;
                }
            }

            return $fitcash;
        }

        if(!empty($data['amount_customer']) && !empty($data['type']) && in_array($data['type'],['workout-session'])){

            $getWorkoutSessionFitcash = $this->getWorkoutSessionFitcash($data);

            $fitcash = round($getWorkoutSessionFitcash * $data['amount_customer'] / 100);
        }
        return $fitcash;

    }

    public function getRatecardAmount($ratecard){

        if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
            $price = $ratecard['special_price'];
        }else{
            $price = $ratecard['price'];
        }

        return $price;
    }

    public function getDurationDay($ratecard){

        if(!empty($ratecard['validity_type_copy'])){
            $ratecard['validity_type'] = $ratecard['validity_type_copy'];
        }
        if(!empty($ratecard['validity_copy'])){
            $ratecard['validity'] = $ratecard['validity_copy'];
        }
        
        empty($ratecard['validity_type']) ? $ratecard['validity_type'] = "days" : null;
        
        switch ($ratecard['validity_type']){
            case 'days': 
            case 'day': 
                $duration_day = (int)$ratecard['validity'];break;
            case 'months': 
            case 'month': 
                $duration_day = (int)($ratecard['validity'] * 30) ; break;
            case 'year': 
            case 'years': 
                $duration_day = (int)($ratecard['validity'] * 30 * 12); break;
            default : $duration_day =  $ratecard['validity']; break;
        }

        return $duration_day;

    }

    public function getVendorCommision($data){

        $finder_id = (int)$data['finder_id'];
        $offer_id = (isset($data['offer_id']) && $data['offer_id'] != "") ? (int)$data['offer_id'] : false;

        $vendorCommercial = \VendorCommercial::where('vendor_id',$finder_id)->orderBy('_id','desc')->first();

        $commision = 15;

        if($vendorCommercial){

            if(isset($vendorCommercial['contract_end_date']) && $vendorCommercial['contract_end_date'] != "" && isset($vendorCommercial['commision']) && $vendorCommercial['commision'] != ""){

                $contract_end_date = strtotime(date('Y-m-d 23:59:59',strtotime($vendorCommercial['contract_end_date'])));

                if($contract_end_date > time()){
                    $commision = (float) preg_replace("/[^0-9.]/","",$vendorCommercial['commision']);
                }
            }

            if($offer_id && isset($vendorCommercial['campaign_end_date']) && $vendorCommercial['campaign_end_date'] != "" && isset($vendorCommercial['campaign_cos']) && $vendorCommercial['campaign_cos'] != ""){

                $campaign_end_date = strtotime(date('Y-m-d 23:59:59',strtotime($vendorCommercial['campaign_end_date'])));

                if($campaign_end_date > time()){
                    $commision = (float) preg_replace("/[^0-9.]/","",$vendorCommercial['campaign_cos']);
                }
            }

            if(isset($data['routed_order']) && $data['routed_order'] == "1" && isset($vendorCommercial['routing_cos']) && !empty($vendorCommercial['routing_cos'])){

                if($data['payment_mode'] != "at the studio" && isset($vendorCommercial['routing_cos']['online']) && $vendorCommercial['routing_cos']['online'] != ""){
                    $commision = $vendorCommercial['routing_cos']['online'];
                }

                if($data['payment_mode'] == "at the studio" && isset($vendorCommercial['routing_cos']['offline']) && $vendorCommercial['routing_cos']['offline'] != ""){
                    $commision = $vendorCommercial['routing_cos']['offline'];
                }
            }
            
        }
                
        if(isset($data['routed_order']) && $data['routed_order'] == "1"){
            
            $finder = \Finder::find($finder_id);
            $routed_commission_reward_type_map = Config::get('app.routed_commission_reward_type_map');
            if(!empty($finder['flags']['reward_type']) && !empty($routed_commission_reward_type_map[$finder['flags']['reward_type']])){
                $commision = $routed_commission_reward_type_map[$finder['flags']['reward_type']];
            }
        }

        Log::info('commision : '.$commision);
        return $commision;
        
    }

    public function financeUpdate($order){

        $order->cos_applicable = 'yes';

        $order->gst_applicable = 'yes';

        if(!isset($order->vendor_commission)){

            $order->cos_percentage = $this->getVendorCommision($order->toArray());
        
        }else{
        
            $order->cos_percentage = $order->vendor_commission;
        
        }

        $amount_used = (!empty($order->vendor_price)) ? $order->vendor_price : $order->amount_finder;

        $order->cos_finder_amount = ceil(($amount_used * $order->cos_percentage) / 100);

        $order->gst_percentage = Config::get('app.gst_on_cos_percentage');

        $order->amount_transferred_to_vendor = $amount_used;

        if($order->payment_mode == "at the studio"){
            $order->amount_transferred_to_vendor = 0;
        }

        $order->amount_transferred_to_vendor -= $order->cos_finder_amount;

        $order->gst_finder_amount =  floor(($order->gst_percentage * $order->cos_finder_amount) / 100);

        $order->amount_transferred_to_vendor -= $order->gst_finder_amount;

        $order->update();
    }

    public function getWorkoutSessionLevel($customer_id){

        $trials_attended = \Booktrial::where('customer_id', $customer_id)->where('post_trial_status', 'attended')->get([]);

        $trials_attended = count($trials_attended);
        
        $streak_data = Config::get('app.streak_data');
        $maxed_out = false;
        $current_level = $this->getLevelByTrials($trials_attended);
        
        if($current_level['level']  == count($streak_data)){
        
            $next_level = [];
            $maxed_out = true;
        
        }else{
        
            $next_level =  $streak_data[$current_level['level']];
        
        }
        
        return [
            'current_level'=>$current_level,
            'next_level'=>$next_level,
            'trials_attended'=>$trials_attended,
            'maxed_out'=>$maxed_out,
            'next_session'=>$this->getLevelByTrials($trials_attended+1)
        ];

    }

    public function getLevelByTrials($trials_attended){

        $streak_data = Config::get('app.streak_data');
        $current_level = $streak_data[count($streak_data) - 1];
        
        foreach($streak_data as $key => $value){
            if($trials_attended < $value['number']){
                $current_level = $value;
                break;
            }
        }

        return $current_level;

    }

    public function getWorkoutSessionFitcash($booktrialData){
        // Log::info($this->getWorkoutSessionLevel($booktrialData['customer_id']));
        Log::info(__FUNCTION__." called from ".debug_backtrace()[1]['function']);

        Log::info("booktrialData");
        Log::info($booktrialData);
        $fitcash =  $this->getWorkoutSessionLevel($booktrialData['customer_id'])['current_level']['cashback'];
        return $fitcash;
        
    }

    public function getStreakImages($current_level){
        $streak = [];
        $streak_data = Config::get('app.streak_data');
        $unlock_url = Config::get('app.paypersession_level_icon_base_url');
        $lock_url = Config::get('app.paypersession_lock_icon');
        
        foreach($streak_data as $level){
            if($current_level >= $level['level']){
                array_push($streak, ['header'=> 'Level '.$level['level'], 'url'=>$unlock_url.$level['level'].'.png', 'text'=>$level['cashback'].'%', 'unlocked'=>true, 'unlock_color'=>$level['unlock_color']]);
            }else{
                array_push($streak, ['header'=> 'Level '.$level['level'], 'url'=>$lock_url, 'text'=>$level['cashback'].'%', 'unlocked'=>false, 'unlock_color'=>'#b6b6b6']);
            }
        }
        return $streak;
    }

    public function deleteSelectCommunication($data){

        $transaction = $data['transaction'];
        $labels = $data['labels'];
        
        $unset_keys = [];
        $queue_id = [];
        if(isset($transaction['send_communication'])){

            foreach ($labels as $value) {
                
                if((isset($transaction['send_communication'][$value]))){
                    try {
                        $queue_id[] = $transaction['send_communication'][$value];
                        $unset_keys[] = 'send_communication.'.$value;
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }
            }
            Log::info("unsetting communication");
            Log::info($queue_id);
            if(!empty($queue_id)){
    
                $transaction->unset($unset_keys);
                $sidekiq = new Sidekiq();
                $sidekiq->delete($queue_id);
    
            }
        }
    }
    public function saavn($order){

        $saavn = \Saavn::active()->where('used_date','exists',false)->orderBy('_id','asc')->first();

        if($saavn){

            $customermailer = new CustomerMailer();

            $saavn->order_id = (int)$order['_id'];
            $saavn->used_date = time();
            $saavn->update();

            $order->saavn_code = $saavn['code'];
            $order->update();

            $customermailer->saavn($order->toArray());

            return "success";
        }

        return "error, no code";
    }

    public function checkIfpopPup($customer, $customdata=array()){
        Log::info("checkIfpopPup");
        Log::info($customer);
		$resp = array();

		$resp["show_popup"] = false;
        $resp["popup"] = array();
        if(!isset($customer)){
            return;
        }

		if(count($customdata) == 0){

				$current_wallet_balance = \Wallet::active()->where('customer_id',$customer->_id)->where('balance','>',0)->sum('balance');
                Log::info($current_wallet_balance);
				if($current_wallet_balance > 0){

					$resp["show_popup"] = true;
					$resp["popup"]["header_image"] = "https://b.fitn.in/iconsv1/global/fitcash.jpg";
					$resp["popup"]["header_text"] = "Congratulations";
					$resp["popup"]["text"] = "Login successful. You have Rs ".$current_wallet_balance." in your Fitcash wallet - you can use this to do membership purchase or pay-per-session bookings.";
					$resp["popup"]["button"] = "Ok";

				}
		}else{
			if(isset($customdata['signupIncentive']) && $customdata['signupIncentive'] == true){

				$addWalletData = [
					"customer_id" => $customer["_id"],
					"amount" => 250,
					"amount_fitcash_plus"=>250,
					"description" => "Added FitCash+ Rs 250 on Sign-Up, Expires On : ".date('d-m-Y',time()+(86400*15)),
					"validity"=>time()+(86400*15),
					"entry"=>"credit",
					"type"=>"FITCASHPLUS"
				];
				$this->utilities->walletTransaction($addWalletData);
				$resp["show_popup"] = true;
				$resp["popup"]["header_image"] = "https://b.fitn.in/iconsv1/global/fitcash.jpg";
				$resp["popup"]["header_text"] = "Congratulations";
				$resp["popup"]["text"] = "You have recieved Rs.250 FitCash plus. Validity: 15 days";
				$resp["popup"]["button"] = "Ok";
			}
		}

		return $resp;
	}
    function getAddWalletArray($data=array())
    {
    	
    	$req = [];
    	$req['customer_id'] = isset($data['customer_id'])&&$data['customer_id']!=""?$data['customer_id']:"";;
    	$req['amount'] = isset($data['amount'])&&$data['amount']!=""?$data['amount']:"";
    	$req['entry'] = "credit";
    	$req['type'] = "FITCASHPLUS";
    	$req['amount_fitcash_plus'] = isset($data['amount'])&&$data['amount']!=""?$data['amount']:"";
    	$req['description'] = !empty($data['description'])?$data['description']:"";
    	$req["validity"] = time()+(86400*60);
    	$req['for'] = isset($data['for'])&&$data['for']!=""?$data['for']:"";
    	$req['starter_pack']=true;
    	return $this->walletTransaction($req);
    }

    public function checkPPSReferral($code, $customer_id){

        $customer = Customer::active()->where('pps_referral_code', strtoupper($code))->first();

        if($customer){

            if($customer['_id'] == $customer_id){

                if(isset($customer['pps_referral_credits']) && $customer['pps_referral_credits'] > 0){
                    $credits_used = isset($customer['pps_referral_credits_used']) ? $customer['pps_referral_credits_used'] : 0;

                    if($credits_used < 5){

                            if($customer['pps_referral_credits'] - $credits_used > 0){

                                return ['status'=>200, 'message'=>'Successfully applied referral discount', 'discount'=> 299, 'type'=>'self', 'customer'=>$customer];
                            
                            }else{
                                    
                                return ['status'=>400, 'message'=>'Your friends have not booked yet'];
                                
                            }


                    }else{
                        
                        return ['status'=>400, 'message'=>'You have exhausted the limit of this coupon'];

                    }
                }else{
                    return ['status'=>400, 'message'=>'Your friends have not booked yet'];
                }


            }else{

                $prev_workout_sessions = \Order::active()->where('customer_id', $customer_id)->where('type', 'workout-session')->count();

                if($prev_workout_sessions){
                    
                    return ['status'=>400, 'message'=>'Pay per session referral is only applicable for new users'];
                
                }else{

                    return ['status'=>200, 'message'=>'Pay per session referral is successfully applied', 'discount'=>299, 'type'=>'referral', 'customer'=>$customer];

                }

            }

        }else{
            return ['status'=>400, 'message'=>'Incorrect Referral Code'];
        }
        



    }

    public function isPPSReferralCode($code){
        $code = strtoupper($code);
		$assholeCodes = ["NIRA7325R","GAUR7726R","GAUR2025R","GAUR8976R","GAUR7374R","GAUR1952R","GAUR4066R","GAUR8183R","GAUR9928R","GAUR8907R","GAUR7850R","GAUR3786R","GAUR8213R","GAUR2389R","GAUR2098R","GAUR3549R","GAUR1798R","GAUR3347R","GAUR4958R","GAUR6830R","GAUR7014R","GAUR7675R","GAUR9502R","GAUR3739R","RAJ5078R","RAHU2157R","RAJ1993R","GAUR2466R","GAUR8731R","RAHU4004R","RAJU6022R","GAUR3393R","GORU5013R","RAJ2108R","GAUR3839R","GAUR8786R","RAJ7506R","GAUR1239R","GORU8493R","RAJA9388R","RAHU2224R","RAM8335R","RAGH2992R","RAJ2752R","RAMA9818R","GAUR7926R","GAUR4087R","GANE6913R","GAUR1360R","RAVI1022R","RAIN7225R","RAJE4631R","RAVI7890R","RAGI5524R","DIVY4144R","RAVI7741R","RAVI5252R","RAMA3692R","PRIY2800R","RAM3154R","POOJ5073R","KRIS4965R","SHIV1177R","GAUT9460R","ROHI5588R","RAJE4868R","SNEH7426R","DHAR8793R","ANUJ5700R","AJAY8632R","KANH9604R","PURO8073R","HITE8333R","RAJR7176R","GAUR2482R","RAJE3868R","RAKE5575R","GAUR1404R","RAMG3131R","NIRA8347R","ROBI6419R","GAUT6627R","AMAN9183R","GANP9123R","RAMK8355R","RAJE2832R","RAMR6998R","HEMA1578R","KAMA7471R","GANP4203R","RAVI7593R","JAYA5318R","AMIT6423R","RAJU9276R","NAKU7625R","HARM5287R","NIKI1409R","RAJM9073R","YOGE5696R","BHAV2629R","SHRU6579R","RAJA1533R","SUDH2075R"];
		if(in_array($code, $assholeCodes)){
			return false;
		}

        $couponCodes = ["32REDTFMR","RED33TFMR","35REDTFMR","RED36TFMR","38REDTFMR","RED39TFMR","41REDTFMR","RED42TFMR","44REDTFMR","RED45TFMR"];

        if(in_array($code, $couponCodes)){
			return false;
		}
        return (strlen($code)==9 && substr($code, -1 ) == 'R') || (strlen($code)==10 && substr($code, 0, 2) == 'R-');
    }

    public function setPPSReferralData($order){
        $customer_id = $order['customer_id'];
        $referral_resp = $this->checkPPSReferral($order['coupon_code'], $customer_id);

        if($referral_resp['status'] == 200){
            
            $customer = $referral_resp['customer'];
            
            if($referral_resp['type'] == 'self'){
            
                $customer->pps_referral_credits_used = $customer->pps_referral_credits_used + 1;
                $customer->update();

                $update_order = \Order::where('_id', $order['_id'])->update(['pps_referral'=> 'self']);
            
            }else{
            
                $customer->pps_referral_credits = $customer->pps_referral_credits + 1;
                $pps_referral_customer_ids = isset($customer->pps_referral_customer_ids) ? $customer->pps_referral_customer_ids : [];
                array_push($pps_referral_customer_ids, $customer_id);
                $customer->pps_referral_customer_ids = $pps_referral_customer_ids;
                $customer->update();

                $update_referred = Customer::where('_id', $customer_id)->update(['pps_referred_from'=> $customer->_id]);

                $update_order = \Order::where('_id', $order['_id'])->update(['pps_referral'=> 'referred']);

                $customersms = new CustomerSms();

                $customersms->ppsReferral(['customer_name'=>$order['customer_name'], 'pps_referral_code'=>$customer->pps_referral_code, 'customer_phone'=>$customer->contact_no]);
                
            }
        }
    }

    public function getRemainigPPSSessions($customer){

        return 5 - (isset($customer['pps_referral_credits_used']) ? $customer['pps_referral_credits_used'] : 0);
        // if(isset($customer['pps_referral_credits']) && $customer['pps_referral_credits'] > 0){
        //     $credits_used = isset($customer['pps_referral_credits_used']) ? $customer['pps_referral_credits_used'] : 0;
        //     if($credits_used < 5){
        //         return $customer['pps_referral_credits'] - $credits_used;
        //     }else{
        //         return 0;
        //     }
        // }else{
        //     return 5;
        // }
    }
    
    public function isIntegratedVendor($finderdata){
        if($finderdata['commercial_type'] == 0 || (isset($finderdata['membership']) && $finderdata['membership'] == 'disable' && isset($finderdata['trial']) && $finderdata['trial'] == 'disable') || (!empty($finderdata['flags']['state']) && in_array($finderdata['flags']['state'], ['temporarily_shut', 'closed']))){
            return false;
        }
        return true;
    }

    public function getContactOptions($finderarr){
		$knowlarity_no = [];
				
		if(isset($finderarr['knowlarityno']) && count($finderarr['knowlarityno'])){
			// return $finderarr['knowlarityno'];
			if(count($finderarr['knowlarityno']) == 1){
				$finderarr['knowlarityno'] = $finderarr['knowlarityno'][0];
				$finderarr['knowlarityno']['extension'] = strlen($finderarr['knowlarityno']['extension']) < 2 && $finderarr['knowlarityno']['extension'] >= 1  ?  str_pad($finderarr['knowlarityno']['extension'], 2, '0', STR_PAD_LEFT) : $finderarr['knowlarityno']['extension'];
				if($finderarr['knowlarityno']['extension']){

					$knowlarity_no[] = ['decription'=>'Already a member & have a query', 'phone_number'=>'+91'.$finderarr['knowlarityno']['phone_number'], 'extension'=>'1'.$finderarr['knowlarityno']['extension'], 'popup'=>true];
					$knowlarity_no[] = ['decription'=>'Want to join & need assistance', 'phone_number'=>'+91'.$finderarr['knowlarityno']['phone_number'], 'extension'=>'2'.$finderarr['knowlarityno']['extension'], 'popup'=>false];
					$knowlarity_no[] = ['decription'=>'For collaborations & other matters', 'phone_number'=>'+91'.$finderarr['knowlarityno']['phone_number'], 'extension'=>'3'.$finderarr['knowlarityno']['extension'], 'popup'=>true];
				
				}
			
			}else{
				foreach($finderarr['knowlarityno'] as $number){
					// return $finderarr['knowlarityno'];
					if(!(isset($number['extension']) && $number['extension'])){
						
						$knowlarity_no[] = ['decription'=>'Want to join & need assistance', 'phone_number'=>'+91'.$number['phone_number'], 'extension'=>null, 'popup'=>false];
						
					}else{
						$extension = str_pad($number['extension'], 2, '0', STR_PAD_LEFT);
					
						array_unshift($knowlarity_no, ['decription'=>'Already a member & have a query', 'phone_number'=>'+91'.$number['phone_number'], 'extension'=>'1'.$extension, 'popup'=>true]);
						array_push($knowlarity_no, ['decription'=>'For collaborations & other matters', 'phone_number'=>'+91'.$number['phone_number'], 'extension'=>'3'.$extension, 'popup'=>true]);
					}
				}
			}
		}
		
		return $knowlarity_no;

	}
    

	public function baseFailureStatusMessage($e)
	{
		$message = ['type'    => get_class($e),'message' => $e->getMessage(),'file'=> $e->getFile(),'line'=> $e->getLine()];
		return  $message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line'];
	}
	
	
	public function addProductsToCart($cartDataInput=[],$cart_id=null,$cart_summary=false,$update=true)
	{
		try {
			if(empty($cartDataInput))
			{
				$data = Input::json()->all();
				$cartDataInput=(!empty($data['cart_data'])?$data['cart_data']:[]);
			}
			$response=["status"=>1,"response"=>["message"=>"Success"]];
			$jwt_token = Request::header("Authorization");
			if(!empty($jwt_token)||!empty($cart_id))
			{
				if(!empty($cart_id))
					$cart_id=intval($cart_id);
					else
					{
						$token_decoded=decode_customer_token();
						$cart_id=((!empty($token_decoded->customer)&&!empty($token_decoded->customer->cart_id))?$token_decoded->customer->cart_id:null);
                    }
                    if(empty($cart_id) && !empty($token_decoded->customer->_id)){
                        $user_cart = Cart::where('customer_id', intval($token_decoded->customer->_id))->first();
                        if($user_cart){
                            $cart_id = $user_cart->_id;
                        }
                    }
					if(!empty($cart_id)&&!empty($cartDataInput))
					{
						$cartData=[];
						$cartDataExtended=[];
						$cartDataRatecards=array_column($cartDataInput, 'ratecard_id');
						$cartDataUnique=count(array_unique($cartDataRatecards));
						$cartQuantityCount=count(array_column($cartDataInput, 'quantity'));
						$cartRatecardsCount=count($cartDataRatecards);
						// 					return $cartDataInput;
						if($cartRatecardsCount>0&&$cartQuantityCount>0&&$cartQuantityCount==$cartRatecardsCount)
						{
							$ratecards=ProductRatecard::active()->whereIn("_id",array_map('intval',$cartDataRatecards))->with(array('product'=>function($query){$query->active()->select('_id','slug','title','slug','info','specification','image');}))->get(['price','properties','product_id','title','color','size','image']);
							if(!empty($ratecards))
							{
								$ratecards=$ratecards->toArray();
								if($cartDataUnique!=count($ratecards))return ['status'=>0,"message"=>"Invalid Ratecard Found."];
								foreach ($ratecards as &$ratecard)
								{
									if(!empty($ratecard['product']))
									{
										$neededObject = array_values(array_filter($cartDataInput,function ($e) use ($ratecard) {return $e['ratecard_id']== $ratecard['_id'];}));
										if(!empty($neededObject)&&count($neededObject)>0)
											$neededObject=$neededObject[0];
											if(!empty($ratecard)&&!empty($neededObject)&&!empty($neededObject['quantity'])&&!empty($ratecard['product_id'])&&!empty($ratecard['_id'])&&isset($ratecard['price']))
											{
												array_push($cartData, ["product_id"=>$ratecard['product_id'],"ratecard_id"=>$ratecard['_id'],"price"=>$ratecard['price'],"quantity"=>intval($neededObject['quantity'])]);
												$tmpRatecardinfo=['_id'=>!empty($ratecard['_id'])?$ratecard['_id']:"",'title'=>!empty($ratecard['title'])?$ratecard['title']:"",'color'=>(!empty($ratecard['properties'])&&!empty($ratecard['properties']['color']))?$ratecard['properties']['color']:"",
														'size'=>(!empty($ratecard['properties'])&&!empty($ratecard['properties']['size']))?$ratecard['properties']['size']:"",'slug'=>!empty($ratecard['slug'])?$ratecard['slug']:"",'properties'=>!empty($ratecard['properties'])?$ratecard['properties']:"",'image'=>!empty($ratecard['image'])?$ratecard['image']:""];
												array_push($cartDataExtended, ["product"=>$ratecard['product'],"ratecard"=>$tmpRatecardinfo,"price"=>$ratecard['price'],"quantity"=>intval($neededObject['quantity'])]);
											}
                                            else return ['status'=>0,"message"=>"Not a valid ratecard or ratecard doesn't exist."];
									}
									else return ['status'=>0,"message"=>"Not a valid product id."];
								}
								if($cart_summary)return ['status'=>1,"data"=>$cartDataExtended];
								if($update) {
									$addedToCart=Cart::where('_id', intval($cart_id))->first();
									$addedToCart=$addedToCart->update(['products'=>$cartData]);
                                }
                                $response['response']['data']=$cartDataExtended;
								return $response;
							}
							else return ['status'=>0,"message"=>"No product Ratecards Found."];
						}
						else return ['status'=>0,"message"=>"Invalid Cart Input data."];
					}
					else return ['status'=>0,"message"=>"No Data To insert or cart Id is invalid/absent."];
			}
			else return ['status'=>0,"message"=>"Token Not Present"];
			
			return $response;
        } catch (Exception $e)
		{
			return  ['status'=>0,"message"=>$this->baseFailureStatusMessage($e)];
		}
		
	}
	
	public function getProductCartAmount($data)
	{
		try {
			$resp=["status"=>1,"message"=>"success",'amount'=>[]];
			$cart_data =$data['cart_data'];
			$amount=0;
			foreach ($cart_data as $cart_item)
				$amount=$amount+(intval($cart_item['quantity'])*intval($cart_item['price']));
				
				$resp['amount']['cart_amount']=$amount;
				// KINDLY ADD WALLET AMOUNT HERE TO BE SUBTRACTED
				// GET WALLET CALCULATED AMOUNT FROM DIFF FUNCTION
				// MAKE DIFFERENT VARIABLE FOR WALLET AMOUNT ADD IT IN RESPONSE IN DATA WITH KEY wallet amount
				
				
				// KINDLY ADD COUPON OFF AMOUNT HERE TO BE SUBTRACTED
				// GET COUPON CALCULATED AMOUNT FROM DIFF FUNCTION
				// MAKE DIFFERENT VARIABLE FOR WALLET AMOUNT ADD IT IN RESPONSE IN DATA WITH KEY coupon_amount
				
				if(!empty($data['coupon']))
				{
					 $couponValid=$this->getCouponCodeAttach($data,$amount);
					if(!empty($couponValid)&&!empty($couponValid['status']))
					{
						$couponValid=$couponValid['coupon'];
						$couponDiscAmnt=0;
						if(!empty($couponValid['discount_amount']))
						{
							if($amount>=$couponValid['discount_amount'])
							{
								$amount=$amount-$couponValid['discount_amount'];
								$couponDiscAmnt=$couponValid['discount_amount'];
							}
							else {
								$couponDiscAmnt=$amount;
								$amount=0;							
							}
							
						}
						else if(!empty($couponValid['discount_percent'])&&!empty($couponValid['discount_max']))
						{
// 							if($amount>=$couponValid['discount_max'])
// 							{
								$disc_perc_amnt=(($couponValid['discount_percent']/100)*$amount);
								if($disc_perc_amnt>=$couponValid['discount_max'])
								{
									$amount=$amount-$couponValid['discount_max'];
									$couponDiscAmnt=$couponValid['discount_max'];
								}
									else {
										$amount=intval($amount-$disc_perc_amnt);
										$couponDiscAmnt=intval($disc_perc_amnt);
									}
									
// 							}
// 							else {
// 								$couponDiscAmnt=$amount;
// 								$amount=0;
// 							}
						}
						else return ['status'=>0,"message"=>"Coupon discount not set.Can't be used yet."];
						if($couponDiscAmnt!=0)
							$resp['amount']['coupon_discount_amount']=$couponDiscAmnt;	
					}
					else return $couponValid;
				}
					
			
				// AFTER CALCULATION SHOW ONLY DEDUCTION HERE
				// $amount = $amount - $walletamuount - $couponAmount + $convinience_fee;
				
				
				// DELIVERY CHARGES
				
				if(empty($data['deliver_to_vendor']))
				{
					$resp['amount']['delivery']=intval(Config::get('app.product_delivery_charges'));
					$amount=$amount+$resp['amount']['delivery'];
				}

					// FINALLY RETURN
					$resp['amount']['final']=$amount;
					
					return $resp;
		} catch (Exception $e)

		{
			return  ['status'=>0,"message"=>$this->baseFailureStatusMessage($e)];
		}
		
	}
	
	public function getCartSummary($order)
	{
		try {
			if(empty($order))return ["status"=>0,"message"=>"No order present."];
			$resp=["status"=>1,"message"=>"success","data"=>[]];
			
			$cart_data =$order['cart_data'];
			$cart_desc=[];
			$amount=0;
			foreach ($cart_data as $cart_item)
			{
				$img_url="";
				if(!empty($cart_item['ratecard'])&&!empty($cart_item['ratecard']['image'])&&!empty($cart_item['ratecard']['image']['primary']))
					$img_url=$cart_item['ratecard']['image']['primary'];
					else if (!empty($cart_item['product']&&!empty($cart_item['product']['image'])&&!empty($cart_item['product']['image']['primary'])))
						$img_url=$cart_item['product']['image']['primary'];
					
						$temp=[];
						$temp['quantity']=$cart_item['quantity'];
						$temp['price']=(intval($cart_item['quantity'])*intval($cart_item['price']));
						$temp['size']=$cart_item['ratecard']['size'];
						$temp['title']=$cart_item['product']['title'];
						$temp['sub_title']=$cart_item['ratecard']['color'];
						
						if(!empty($cart_item['ratecard']['properties']))
						{
							$props_arr=$this->mapProperties($cart_item['ratecard']['properties']);
							(!empty($props_arr))?$temp['properties']=$props_arr:"";
						}
						
						$temp['image']=$img_url;
						array_push($cart_desc,$temp);
						$amount=$amount+(intval($cart_item['quantity'])*intval($cart_item['price']));

			}
			
			$resp['data']['cart_details']=$cart_desc;
			$resp['data']['total_cart_amount']=$amount;
			if(!emptY($order['amount_calculated']['coupon_discount_amount']))
			{
				$resp['data']['coupon_discount']=$order['amount_calculated']['coupon_discount_amount'];
				$amount=$amount-$order['amount_calculated']['coupon_discount_amount'];
			}
			if(empty($order['deliver_to_vendor']))$amount=$amount+intval(Config::get('app.product_delivery_charges'));
			$resp['data']['total_amount']=$amount;
			// 			$this->getProductCartAmount($order);
			return $resp;
		} catch (Exception $e)
		{
			return  ['status'=>0,"message"=>$this->baseFailureStatusMessage($e)];
		}
	}
	
// 		public function  getProductImages($cart_data)
// 		{
	
// 				$data=array_map(function($e){return [ratecard_id=>intval($e['ratecard']['_id']),product_id=>intval($e['product']['_id'])];},$cart_data);
		
// 				$products=array_column(array_column($cart_data,'product'),'_id');
// 				$ratecards=array_column(array_column($cart_data,'ratecard'),'_id');
// // 				\Product::$withoutAppends=true;
// // 				$productView=Product::whereIn("_id",$products)->with(array('ratecard'=>function($query) use ($ratecards) {$query->whereIn("_id",$ratecards)->select('_id','product_id','image');}))->get(['image']);
// // 				$map=[];
// // 				if(!empty($productView))
// // 					{
// // 					$productView=$productView->toArray();
// // 					foreach ($productView as $product) {
// // 						foreach ($ratecards as $value) {
// // 							$selectedRatecard=array_values(array_filter($productView['ratecard'],function ($e) use ($value) {return $value==$e['_id'];}));
// // 							if(!empty($selectedRatecard))
// // 								{
// // 								$selectedRatecard=$selectedRatecard[0];
// // 								if(!empty($selectedRatecard['image'])&&!empty($selectedRatecard['primary'])/* &&count($selectedRatecard['image']['secondary'])>0 */)
// // 										$img=$selectedRatecard['image']['primary'];
// // 								}
// // 								else if(!empty($value['image'])&&!empty($value['image']['primary'])/* &&count($productView['image']['secondary'])>0 */)
// // 										$img=$value['image']['primary'];
// // 									$map[intval($value['ratecard']['_id'])]=$img;
// // 							}
// // 						}
// // 					}
					
// 					$rc=array_column($home, "ratecard_id");
// 					$pro=array_column($home, "product_id");
					
// 					$products=array_column(array_column($cart_data,'product'),'_id');
// 					$ratecards=array_column(array_column($cart_data,'ratecard'),'_id');
					
// 					Product::$withoutAppends=true;
// 					/* $rates=ProductRatecard::raw(function($collection)
// 					 {
// 					 return $collection->aggregate(
// 					 [
// 					 ['$group' => ['_id' => ["p_id"=>'$product_id','color'=>'$color'],'details' => ['$push'=>['ratecards'=>'$_id']]]],
// 					 ['$match' => ['details.0' => ['$exists'=>true]]],
// 					 ['$project' => ["rcs"=>['$arrayElemAt' => ['$details',0]]]]
// 					 ]);
// 					 });
// 					 (!empty($rates)&&!empty($rates['result']))?
// 					 $rc=array_values(array_intersect(array_column(array_column($rates['result'], 'rcs'), 'ratecards'),$rc)):""; */
// 					$combined=["rc"=>ProductRatecard::active()->whereIn("_id",$rc)->get(["title","price"]),"pc"=>Product::active()->whereIn("_id",$pro)->with('primarycategory')->get(["title",'productcategory','slug'])];
					
					
// 				$rateMain=[];
// 				$productMain=[];
// 				foreach ($combined['rc'] as &$value)
// 					$rateMain[$value->_id]=$value;
// 					foreach ($combined['pc'] as &$value)
// 						$productMain[$value->_id]=$value;
					
// 				$tpa=[];
// 				foreach ($home as $key => &$value)
// 				{
// 					$rc1=(!empty($value)&&!empty($rateMain)&&!empty($value['ratecard_id'])&&!empty($rateMain[$value['ratecard_id']]))?$rateMain[$value['ratecard_id']]:"";
// 					$pc1=(!empty($value)&&!empty($productMain)&&!empty($value['product_id'])&&!empty($productMain[$value['product_id']]))?$productMain[$value['product_id']]:"";
// 					if(!empty($rc1)&&!empty($pc1))
// 						array_push($tpa,["ratecard"=>$rateMain[$value['ratecard_id']],"product"=>$productMain[$value['product_id']]]);
// 				}
					
			
// 					return $map;
// 		}
			
	public function getCartFinalSummary($cart_data,$cart_id)
	{
		try {
			if(empty($cart_data))
				return ["status"=>5,"message"=>"No Cart Data present Or Cart is Empty."];
			   $resp=["status"=>1,"message"=>"success","data"=>[]];
				
				$cart_desc=[];
				$cart_details=[];
				/* $cart_data=$this->addProductsToCart($cart_data,$cart_id,true);
				if(!empty($cart_data)&&!empty($cart_data['status']))$cart_data=$cart_data['data'];
				else return ["status"=>0,"message"=>(!empty($cart_data['message'])?$cart_data['message']:"couldn't get cart data.")]; */
				$amount=0;
				$count=0;
				$hc=new \HomeController(new CustomerNotification(), new Sidekiq(),$this);
				foreach ($cart_data as $cart_item)
				{
					$temp=[];
					$dataProd=$hc->getProductDetail($cart_item['ratecard_id'], $cart_item['product_id'],true);
					if(!empty($dataProd['status']))
						$temp['product']=$dataProd['data'];
					else return $dataProd;
					$temp['quantity']=$cart_item['quantity'];
					$count=$count+$temp['quantity'];
					$temp['price']=$this->getRupeeForm((intval($cart_item['quantity'])*intval($cart_item['price'])));
					array_push($cart_desc,$temp);
					$amount=$amount+(intval($cart_item['quantity'])*intval($cart_item['price']));
				}
				
				$resp['data']['cart_details']=$cart_desc;
// 				$resp['data']['total_cart_amount']=$amount;
				$resp['data']['total_amount']=$this->getRupeeForm($amount);
				$resp['data']['delivery_charges']='+ '.$this->getRupeeForm(intval(Config::get('app.product_delivery_charges'))).' delivery charges';
				$resp['data']['delivery_charges_at_studio']="Free Delivery";
				
				
				
				$resp['data']['total_count']=$count;
				return $resp;
		} catch (Exception $e)
		{
			return  ['status'=>0,"message"=>$this->baseFailureStatusMessage($e)];
		}
		
	}
	public function groupBy($array,$key) {
		$return = array();
		foreach($array as $val) {
			$return[$val[$key]][] = $val;
		}
		return $return;
	}
	
	public function getProductHash($data)
	{
		
		try {
			
			$resp=["status"=>1,"message"=>"success"];
			$createdData=[];
			
			// defaulters
			(!empty($data['payment_mode']))?
				$createdData['payment_mode']=$data['payment_mode']:"";
			$env = (!empty($data['env']) && $data['env'] == 1) ? "stage" : "production";
			
			$key = 'gtKFFx';
			$salt = 'eCwWELxi';
			
			if($env == "production"){
				$key = 'l80gyM';$salt = 'QBl78dtK';
			}
			
			$txnid = $data['payment']['txnid'];
			$amount = $data['amount_calculated']['final'];
			$tmp=[];
			foreach ($data['cart_data'] as $value) {
				array_push($tmp,$value['ratecard']['_id']);
			}
			$productinfo=$createdData['productinfo'] =implode("_",array_map('strtolower', $tmp));
			// $productinfo=$createdData['productinfo'] ="asdasda-asdasdas";
			
			$firstname = strtolower($data['customer']['customer_name']);
			$email = strtolower($data['customer']['customer_email']);
			$udf1 = "";
			$udf2 = "";
			$udf3 = "";
			$udf4 = "";
			$udf5 = "";
			
			$payhash_str = $key.'|'.$txnid.'|'.$amount.'|'.$productinfo.'|'.$firstname.'|'.$email.'|'.$udf1.'|'.$udf2.'|'.$udf3.'|'.$udf4.'|'.$udf5.'||||||'.$salt;
			
			$createdData['payment_hash'] = hash('sha512', $payhash_str);
			
			$verify_str = $salt.'|success||||||'.$udf5.'|'.$udf4.'|'.$udf3.'|'.$udf2.'|'.$udf1.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
			
			 $createdData['verify_hash'] = hash('sha512', $verify_str);
			
			$cmnPaymentRelatedDetailsForMobileSdk1              =   'payment_related_details_for_mobile_sdk';
			$detailsForMobileSdk_str1                           =   $key  . '|' . $cmnPaymentRelatedDetailsForMobileSdk1 . '|default|' . $salt ;
			$detailsForMobileSdk1                               =   hash('sha512', $detailsForMobileSdk_str1);
			$createdData['payment_related_details_for_mobile_sdk_hash'] =   $detailsForMobileSdk1;
			$resp['data']=$createdData;
			
			return $resp;
		} catch (Exception $e)
		{
			return  ['status'=>0,"message"=>$this->baseFailureStatusMessage($e)];
		}
		
		
	}

    
    public function compressImage($file, $src, $dir='tmp-images'){
        $mime_type = $file->getClientMimeType();
        $file_extension = $file->getClientOriginalExtension();
        $local_directory = public_path().'/'.$dir;
        $dst = $src."-c";
        
        $local_path_original =  join('/', [$local_directory, $src.".".$file_extension]);
        $local_path_compressed =  join('/', [$local_directory, $dst.".".$file_extension]);
        $resp = $file->move($local_directory,$src.".".$file_extension);
        
        if ($mime_type == 'image/jpeg'){
            $image = imagecreatefromjpeg($local_path_original);
        }elseif ($mime_type == 'image/png'){
            $image = imagecreatefrompng($local_path_original);
        }

        imagejpeg($image, $local_path_compressed, 30);

        unlink($local_path_original);
        
        return $local_path_compressed;
    }

    public function uploadFileToS3($local_path, $s3_path){
        try{
            $s3 = \AWS::get('s3');
					
            $result = $s3->putObject(array(
                'Bucket'     => Config::get('app.aws.bucket'),
                'Key'        => $s3_path,
                'SourceFile' => $local_path
            ));
            return true;
        }catch(Exception $e){
            Log::info($e);
            return false;
        }
    }
    

    public function getDayWs($date=null)
    {
    	return $this->days[date("w",strtotime($date))];
    	
    }
    public function getSlotReqdField($start=null,$end=null,$service_id=null,$start_date=null,$required='limited_seat',$buy_type='workoutsessionschedules') {
    	
    	try {
    		if(!isset($service_id))throw new Exception("Service id Not Defined.");
    		else if(!isset($start_date))throw new Exception("Start Date not present.");
    		else if(!isset($start)||!isset($end))throw new Exception("Start/End Not Defined.");
    		else {
    			$week_days=["sunday","monday","tuesday","wednesday","thursday","friday","saturday"];
    			$day=$week_days[date("w",strtotime($start_date))];
    			if(empty($day))throw new Exception("Day Not present in the schedules.");
    			$start=(str_contains($start, "pm"))?doubleval($start)+12:doubleval($start);$end=(str_contains($end, "pm"))?doubleval($end)+12:doubleval($end);
    			\Service::$withoutAppends=true;
    			$service=\Service::where("_id",intval($service_id))->first([$buy_type]);
    			if(isset($service)&&!empty($service[$buy_type]))
    			{
    				$r=array_values(array_filter($service[$buy_type], function($a) use ($day){return !empty($a['weekday'])&&$a['weekday']==$day;}));
    				if(!empty($r[0])&&!empty($r[0]['slots']))
    				{
    					$r=$r[0]['slots'];
    					$r=array_values(array_filter($r, function($a) use ($start,$end){return isset($a['start_time_24_hour_format'])&&isset($a['end_time_24_hour_format'])&&$a['start_time_24_hour_format'] >=$start&&$a['end_time_24_hour_format'] <=$end;}));
    					return (!empty($r[0])&&isset($r[0][$required]))?$r[0][$required]:null;
    				}else return null;
    			}else return null;
    		}
    	} catch (Exception $e) {
    		Log::error(" Error Message ::  ".print_r($e->getMessage(),true));
    		return null;
    	}
    	return null;
    }
    
    public function getSlotBookedCount($slot=null,$service_id=null,$date=null,$allowed_qty=10000,$serv_type=['workout-session','booktrials']) {
    	
    	$data=["count"=>0,"allowed"=>false];
    	try {
    		
    		if(!isset($service_id))throw new Exception("Service id Not Defined.");
    		else if(!isset($slot))throw new Exception("Slot not present.");
    		else if(!isset($date))throw new Exception("Date not present.");
    		else {
    			$slot_times=explode('-',$slot);
    			$slot=trim($slot_times[0]).'-'.trim($slot_times[1]);
    			$orders=\Order::active()->where("service_id",intval($service_id))
    			->whereIn("type",$serv_type)
    			->where("status","1")
    			->where("schedule_slot",$slot)
    			->where("schedule_date",$date)->lists("_id");
    			if(empty($orders))
    			{
    				$data['allowed']=true;
    				return $data;
    			}
    			else {
//     				$orders=$orders->toArray();
    				if(count($orders)<$allowed_qty)
    				{
    					$data['count']=count($orders);
    					$data['allowed']=true;
    					return $data;
    				}
    				else {
    					$data['count']=count($orders);
    					$data['allowed']=false;
    					return $data;
    				};
    			}
    			return $data;
    		}
    	} catch (Exception $e) {
    		Log::error(" Error Message ::  ".print_r($e->getMessage(),true));
    		return $data;
    	}
    	return $data;
    }



	public function getRupeeForm($number) {
		return json_decode('"'."\u20b9".'"')." ".(isset($number)?$number:"");
	}
	
	public function getProductDetailsCustom($t,$type="primary",$base=[])
	{
		try {
			$temp=array_values(array_filter($t,function($e) {return isset($e['status'])&&$e['status']== 1;}));
			$this->customSort('order', $temp);
			return ($type=='primary')?array_map(function($e){return ["name"=>$e['name'],"value"=>$e['value']];},$temp):implode("",$this->decorateKeyValueDesc($temp,$base));
		} catch (Exception $e) {
			Log::error(" [ getProductDetailsCustom ]".print_r($this->baseFailureStatusMessage($e),true));
			return "";
		}
	}
	
	public function getQueryMultiplier($arr=[],$product_id)
	{
		$t=[];
		foreach ($arr as $current)
			if(!empty($current['value']))
				$t['properties.'.$current['type']]=$current['value'];
				$t['product_id'] =$product_id;
				$t['status']="1";

	  return $t;
	}
	public function getSelectionView($data,$product_id=null,$productView="",$ratecard_id=null,&$trav_idx=[],$arr=[])
	{
		$intrinsic_data=array_shift($data);
		if(!empty($intrinsic_data)&&!empty($product_id))
		{
			array_push($arr,['type'=>$intrinsic_data['name']]);
			$rates=ProductRatecard::active()->raw(function($collection) use($product_id,$intrinsic_data,$arr)
			{
				return $collection->aggregate([
						['$match'=>$this->getQueryMultiplier($arr,$product_id)],
						['$group'=>['_id' =>'$properties.'.$intrinsic_data['name'],'details' => ['$push'=>['_id'=>'$_id','order'=>'$order','properties'=>'$properties','flags'=>'$flags','price'=>'$price','slash_price'=>'$slash_price','info'=>'$info']]]],
						['$sort'=>['order'=>1]],
						['$match'=>['details.0' => ['$exists'=>true]]],
				]);
			});
			
			usort($rates['result'],function ($a,$b)
			{	
				if (!emptY($b)&&!emptY($a)&&!empty($a['details'])&&!empty($a['details'][0])&&!empty($a['details'][0]['order'])&&!empty($b['details'])&&!empty($b['details'][0])&&!empty($b['details'][0]['order'])&&$a['details'][0]['order']==$b['details'][0]['order']) return 0;
				return (!emptY($b)&&!emptY($a)&&!empty($a['details'])&&!empty($a['details'][0])&&!empty($a['details'][0]['order'])&&!empty($b['details'])&&!empty($b['details'][0])&&!empty($b['details'][0]['order'])&&$a['details'][0]['order']<$b['details'][0]['order'])?-1:1;
			});
// 			return $rates;
// 			if(count($arr)==1)
// 				return $rates;
			if(!empty($rates)&&!empty($rates['result']))
			{
				$temp['variants']=["title"=>"Select ".$intrinsic_data['name'],"sub_title"=>$intrinsic_data['name'],'options'=>[]];
				foreach ($rates['result'] as $key1=>$value) {
					
// 					return $rates;
					foreach ($value['details'] as $key=>$current) {
						$tt=[
								"value"=>(!empty($current['properties'])&&!empty($current['properties'][$intrinsic_data['name']]))?$current['properties'][$intrinsic_data['name']]:"",
								"enabled"=>(!empty($current['flags'])&&!empty($current['flags']['available'])?true:false),
								"ratecard_id"=>$current['_id'],"product_id"=>$product_id,"price"=>$current['price'],
								"cost"=>(isset($current['slash_price'])&&$current['slash_price']!=="")?$this->slashPriceFormat($current)." ".$this->getRupeeForm($current['price']):$this->getRupeeForm($current['price'])
						];
						
						if(!empty($current['info'])&&!empty($current['info']['long_description']))$tt['long_description']=$current['info']['long_description'];
						else if(!empty($productView['info'])&&!empty($productView['info']['long_description']))$tt['long_description']=$productView['info']['long_description'];
						
						if(!empty($current['info'])&&!empty($current['info']['short_description'])&&count($current['info']['short_description'])>0)$tt['short_description']=$this->getProductDetailsCustom($current['info']['short_description'],'secondary');
						else if(!empty($productView['info'])&&!empty($productView['info']['short_description'])&&count($productView['info']['short_description'])>0)$tt['short_description']=$this->getProductDetailsCustom($productView['info']['short_description'],'secondary');
						
						
						if(!empty($tt['value']))
						{
							$arr[count($arr)-1]['value']=$tt['value'];$this->attachProductQuantity($tt);
							/* if(($tt['ratecard_id']==$ratecard_id)&&count($value['details'])==1){
								array_unshift($trav_idx,['ind'=>$key1,'inserted'=>true,'ratecard_id'=>$current['_id']]);
							}
							if(($tt['ratecard_id']==$ratecard_id)&&count($value['details'])>1){
								$wqw=array_values(array_filter($trav_idx,function ($e) use ($current) {return $current['_id']== $e['ratecard_id']&&$e['inserted']==true;}));
								if(!empty($wqw))
								{
									$wqw=$wqw[0];
									array_unshift($trav_idx,['ind'=>$key,'inserted'=>false,'ratecard_id'=>$current['_id']]);
								}
							} */
// 							return $el=$this->getSelectionView($data,$product_id,$productView,$ratecard_id,$trav_idx,$arr);
							
							$el=$this->getSelectionView($data,$product_id,$productView,$ratecard_id,$trav_idx,$arr);
						
							(!empty($el)&&!empty($el['variants']))?$tt['more']=$el['variants']:"";
							if(count($arr)<=1)($key==0)?array_push($temp['variants']['options'], $tt):"";
							else array_push($temp['variants']['options'], $tt);
						}
					}	
				}
				return (count($temp['variants']['options'])>0)?$temp:[];
			}
			return [];
		}
		return [];
	}
	public function getFilteredAndOrdered($data=[],$sort_key="order",$filter_key="status",$filter_value=1,$sort="asc",$tmp_data=[])
	{
		try {
			$tmp_data=array_values(array_filter($data,function ($e) use ($filter_value,$filter_key) {return $filter_value== $e[$filter_key];}));
			if(!empty($tmp_data))
				$this->customSort($sort_key, $tmp_data);
		} catch (Exception $e) {
			Log::error(" [ getFilteredAndOrdered ]".print_r($this->baseFailureStatusMessage($e),true));
		}
		return $tmp_data;
	}
	
	private function decorateKeyValueDesc(&$temp,&$base)
	{
		foreach ($temp as $value)
		{
			array_push($base,"<b>".$value['name']." : </b>");
			array_push($base,$value['value']."<br />");
		}
		return $base;
	}
	
	public function attachCart(&$data,$onlyCart=false,$customer_id=null)
	{
		$jwt=Request::header("Authorization");
		if(isset($jwt))
		{
			$cart=$this->productsTabCartHomeCustomer($customer_id);
			if(!empty($cart))
			{
				$cart=$cart->toArray();
				Log::info(" info attachCart cart ::".print_r($cart,true));
				if($onlyCart)return $cart;
				$data['cart']=["count"=>$this->getCartTotalCount($cart)];
			}
			else return null;
		}
		

	}

	public function attachProductQuantity(&$data,$onlyQuantity=false)
	{
		$jwt=Request::header("Authorization");
		if(isset($jwt))
		{
			 $cart=$this->productsTabCartHomeCustomer();
			if(!empty($cart))
			{
				
				$cart=$cart->toArray();
				if(!empty($cart['products']))
					$tmp_data=array_values(array_filter($cart['products'],function ($e) use ($data) {return (!empty($data['ratecard_id'])&&!empty($e['ratecard_id'])&&$data['ratecard_id']== $e['ratecard_id']);}));
					if(!empty($tmp_data))
					{
						$tmp_data=$tmp_data[0];
						if(!empty($tmp_data['quantity']))
						{
							if($onlyQuantity)return $tmp_data['quantity'];
							else $data['quantity']=$tmp_data['quantity'];
						}
						else {
							if($onlyQuantity) return null;
							else $data['quantity']=1;
						}
					}
			}
		}
	}
	
	public function fetchCustomerAddresses(&$data)
	{
		$jwt=Request::header("Authorization");
		if(isset($jwt))
		{
			$customer=$this->getCustomerAddress();
            Log::info($customer);
			if(!empty($customer))
			{
				$customer=$customer->toArray();
				$data['customer_address'] =(!empty($customer['customer_addresses_product'])?$customer['customer_addresses_product']:[]);
			}
		}
		

	}
	public function getCartTotalCount($cart=null)
	{	if(empty($cart)||empty($cart['products']))return 0;
		else return (!empty($cart))?array_reduce((!empty($cart['products'])?array_map(function($e){return (!empty($e['quantity'])?intval($e['quantity']):0);},$cart['products']):[]),function($carry,$item){$carry+=$item;return $carry;}):0;
	}
	public function fetchProductCities(&$data)
	{
		$jwt=Request::header("Authorization");
		if(isset($jwt))
		{
			$customer=$this->getCustomerAddress();
			if(!empty($customer))
			{
				$customer=$customer->toArray();
				$data['customer_address'] =(!empty($customer['customer_addresses_product'])?$customer['customer_addresses_product']:[]);
			}
		}
		
	}
	
	public function getAllProductDetails($order)
	{
		
		try {
			if(empty($order))
				return ["status"=>0,"message"=>"No data present."];
			if(empty($order['cart_data']))
					return ["status"=>5,"message"=>"No Cart Data present."];
				$resp=["status"=>1,"message"=>"success","data"=>[]];
				$cart_data =$order['cart_data'];
				$cart_desc=[];
				foreach ($cart_data as $cart_item)
				{
					$temp=[];
					$detail=$this->productProperties($cart_item['ratecard']);
					$temp['field']=(!empty($cart_item['product'])&&!empty($cart_item['product']['title']))?$cart_item['product']['title']:(!empty($cart_item['ratecard'])&&!empty($cart_item['ratecard']['title'])?$cart_item['ratecard']['title']:"");
					$temp['value']=("qty : ".intval($cart_item['quantity']));
					if(!empty($detail)&&!empty($detail['status'])&&!empty($detail['data']))$temp['value']=$temp['value']."<br />".$detail['data'];
					array_push($cart_desc,$temp);
				}
				$resp['data']['cart_details']=$cart_desc;
				return $resp;
		} catch (Exception $e)
		{
			return  ['status'=>0,"message"=>$this->baseFailureStatusMessage($e)];
		}	
	}
	public function getProductCities()
	{
		$cities = \City::active()->orderBy('product_order')->whereNotIn('_id',[10000])->remember(Config::get('app.cachetime'))->lists("name");
		if(!empty($cities))
			return $cities;
		else return [];
	}
	
	public function productProperties($ratecard)
	{
		
		try {
			if(empty($ratecard))
				return ["status"=>0,"message"=>"No Ratecards present."];
				$resp=["status"=>1,"message"=>"success","data"=>""];$tmp=[];
				$rc =(!empty($ratecard['properties'])?$ratecard['properties']:[]);
				foreach ($rc as $key => $value)array_push($tmp, strtolower($key)." : ".$value);
				(!empty($tmp)&&count($tmp)>0)?$resp['data']=implode("<br />",$tmp):"";
				return $resp;
		} catch (Exception $e)
		{
			return  ['status'=>0,"message"=>$this->baseFailureStatusMessage($e)];
		}
		
		
		
	}
	public function formatShippingAddress($data=[],$cust_name="",$finder=false)
	{
		
		$temp="";
		$cur_seperator=", ";
		if(!$finder)
		{
			if(!empty($data["name"]))$temp=$temp.$data["name"]." <br />";
			if(!empty($data["line1"]))$temp=$temp.$data["line1"].$cur_seperator;
			if(!empty($data["line2"]))$temp=$temp.$data["line2"].$cur_seperator;
			if(!empty($data["landmark"]))$temp=$temp.$data["landmark"].$cur_seperator;
			if(!empty($data["city"]))$temp=$temp.$data["city"].$cur_seperator;
			if(!empty($data["pincode"]))$temp=$temp.$data["pincode"];
			
		}
		else {
            Log::info("shipping");
			if(!empty($cust_name))$temp=$temp.$cust_name." <br />";
			if(!empty($data['finder_name']))$temp=$temp.$data['finder_name'];
			if(!empty($data['finder_location']))$temp=$temp.'-'.$data['finder_location'];
		}
		return $temp;
	}
	public function getRateCardBaseImage($ratecards=[])
	{
		foreach ($ratecards as $value) 
			if(!empty($value)&&!empty($value['image'])&&!empty($value['image']['primary']))
				return $value['image']['primary'];
		return "";
	}

     public function updateRatecardSlots($data){
        Log::info("inside updateRatecardSlots");
        $orderVariable = \Ordervariables::where("name","expiring-logic")->orderBy("_id", "desc")->first();
        // if(intval(date('d', time())) >= 25){
        if(isset($orderVariable["available_slots_end_date"]) && time() >= $orderVariable["available_slots_end_date"]){
            return;
        }

        $order = \Order::find(intval($data['order_id']));

        Finder::$withoutAppends = true;
        $finder = Finder::find($order['finder_id'], ['brand_id']);
        if($finder['brand_id'] == 135){
            return;
        }


        
        if($order && !empty($order['service_id'])){

            if(!empty($order->ratecard_sidekiq_id_deleted)){
                return;
            }
            
            if(!empty($order->ratecard_sidekiq_id)){
                
                $sidekiq = new Sidekiq();
                $sidekiq->delete($order->ratecard_sidekiq_id);
                $order->ratecard_sidekiq_id_deleted = true;
                $order->save();
            }

            $service_id = $order['service_id'];
            $service = \Service::find($order['service_id']);
            
            if($service){
                
                $service->available_slots = isset($service->available_slots) ? $service->available_slots : 10;
                
                $available_slots = $service->available_slots = $service->available_slots - 1;
                Log::info("reducing slots");
                Log::info("new slots".$available_slots);
                
                
                $ratecard = \Ratecard::active()
                ->where('service_id', $service_id)
                ->where('type','membership')
                ->orderBy('order', 'desc')
                ->first();
                if(empty($ratecard)){
                    return;
                }
                $ratecard_id = $ratecard->_id;
                
                $offer = \Offer::active()
                ->where('ratecard_id', $ratecard_id)
                ->where('added_by_script', '!=', true)
                ->orderBy('order', 'asc')
                ->first();
                
                if($available_slots <= 0){

                    $ordervariable = \Ordervariables::where('name', 'expiring-logic')->orderBy('_id', 'desc')->first();
                    
                    $days_passed = intval(date('d', time())) - intval(date('d', $ordervariable->start_time));

                    $days_passed = $days_passed == 0 ? 1 : $days_passed;
                    
                    $days_left = abs(intval(date('d', $ordervariable->available_slots_end_date)) - intval(date('d', time())));

                    $days_left = $days_left == 0 ? 1 : $days_left;

                    $service->total_slots_created = isset($service->total_slots_created) ? $service->total_slots_created : $service->available_slots;
                    
                    $new_slots = ceil($service->total_slots_created / $days_passed * $days_left / 2);

                    Log::info($days_passed);
                    Log::info($days_left);
                    Log::info($new_slots);
                    
                    $service->available_slots = $new_slots;
                    
                    $service->total_slots_created = $service->total_slots_created + $new_slots;
                
                    if(!empty($offer)){
                        
                        
                        $price = $offer['price'];
                        
                        $ratecard->price_increased_times = isset($ratecard->price_increased_times) ? $ratecard->price_increased_times + 1 : 1;
                        
                        $new_price = round($price * (1 + $ratecard->price_increased_times/100));

                        
                        
                        $new_price = $price + ($new_price > 50 ? ($new_price < 75 ? $new_price : 75) : 50);
                        
                        $offer_data = $offer->toArray();
                        
                        $offer_data['price'] = $new_price;
                        $offer_data['order'] = $offer_data['order'] - $ratecard->price_increased_times ;
                        // $offer_data['end_date'] = \Carbon\Carbon::createFromFormat('d-m-Y H:i:s', date('d-m-Y 00:00:00', strtotime('+24 days', strtotime('first day of this month'))));
                        $offer_data['start_date'] = \Carbon\Carbon::createFromFormat('d-m-Y H:i:s', date('d-m-Y 00:00:00', time()));
                        unset($offer_data['created_at']);
                        unset($offer_data['updated_at']);
                        
                        
                        Log::info("increasing price");
                        Log::info("old price:". $price);
                        Log::info("new price:".$new_price);
                        
                        $create_offer  = $this->createOffer($offer_data);
                        
                        $ratecard->save();
                    }
                    
                    
                    
                }
                $service->save();
                
                $this->busrtFinderCache($order['finder_slug']);
                
            }
        }
        
    
    }

     public function createOffer($offer_data){
        
        $offer_data['added_by_script'] = true;
        $offer_data['created_from_offer'] = $offer_data['_id'];
        $offer_id = \Offer::max('_id') + 1;
        $update_counter = \Identitycounter::where('model', 'Offer')->update(['count'=>$offer_id]);
        Log::info("update_counter");
        Log::info($update_counter);
        
        $offer = new \Offer($offer_data);
        $offer->_id = $offer_id;
        $offer->save();

        Log::info("offer created");
        Log::info($offer);


    }

    public function busrtFinderCache($slug){

        \Cache::tags('finder_detail')->forget($slug);
        \Cache::tags('finder_detail_android')->forget($slug);
        \Cache::tags('finder_detail_ios')->forget($slug);
        \Cache::tags('finder_detail_ios_4_4_3')->forget($slug);
        \Cache::tags('finder_detail_android_4_4_3')->forget($slug);
        
    }

    
    function decryptQr($encrypted_string, $encryption_key) {	
    	$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
    	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    	$decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, hex2bin($encrypted_string), MCRYPT_MODE_ECB, $iv);
    	return $decrypted_string;
    }
    public function updateOrderStatus($booktrial){
    	if(isset($booktrial->pay_later) && $booktrial->pay_later && isset($booktrial->payment_done) && !$booktrial->payment_done){
    		\Order::where('_id', $booktrial->order_id)->where('status', '0')->update(['status'=>'4']);
    	}
    }

    public function getSessionSlotsService($cityId=null,$cat_ids=[],$cache=true,$cache_key=null)
    {
    	$count=0;
    	$alreadyExists=$cache&&$cache_key? \Cache::tags('sessionslotscount')->has($cache_key):false;
    	if($alreadyExists)
    		return intval(\Cache::tags('sessionslotscount')->get($cache_key));
    		else
    		{
    			$query=\Service::where("city_id",$cityId);
    			if(count($cat_ids)==0)
    				$services=$query->lists('workoutsessionschedules');
    			else $services=$query->whereIn("servicecategory_id",$cat_ids)->lists('workoutsessionschedules');
    			if(count($services)==0)
    				return $count;
    			foreach ($services as $value)
    				foreach ($value as $value1)
    					if(!empty($value1)&&!empty($value1['slots']))
    						$count=$count+count($value1['slots']);
    				if($cache&&$cache_key)
    						{
    							\Cache::tags('sessionslotscount')->put($cache_key,$count,Config::get('cache.cache_time'));
    							return $count;
    						}
    						else return $count;
    		}
    }

    public function attachExternalVoucher($data){
        return;
        if($data['type'] != 'workout-session'){
            return;
        }
    
        $voucherAttached = \Externalvoucher::where('booktrial_id', $data['_id'])->first();

        Log::info($voucherAttached);

        if(!$voucherAttached){
           
            $sessions_attended = $this->getSessionAttended($data['customer_id']);
            Log::info('$sessions_attended');
            Log::info($sessions_attended);
    
            
            $voucherType = $this->getVoucherType($sessions_attended);
    
            Log::info('$voucherType');
            Log::info($voucherType);
            
            $voucherAttached = $this->attachVoucher($voucherType, $data);
    
            if(!$voucherAttached){
                return;
            }
        }

        
        $resp =  [
            'header'=>"VOUCHER UNLOCKED",
            'sub_header'=>"You have unlocked ".strtoupper($voucherAttached['type'])." voucher on attending your session at ".$data['finder_name'],
            'coupon_title'=>$voucherAttached['description'],
            'coupon_text'=>"USE CODE : ".strtoupper($voucherAttached['code']),
            'coupon_image'=>$voucherAttached['image'],
            'coupon_code'=>strtoupper($voucherAttached['code']),
            'coupon_subtext'=>'(also sent via email/sms)',
            'unlock'=>'UNLOCK VOUCHER',
            'terms_text'=>'T & C applied.'
        ];
        
        if(!empty($voucherAttached['tnc'])){
            $tnc = "<p>Terms and Conditions:</p>";
            foreach($voucherAttached['tnc'] as $key => $t){
                $tnc = $tnc."<p>".($key+1).". ".$t."</p>";
            }
            
            $resp['terms_detailed_text'] = $tnc;
        }
        $customermailer = new CustomerMailer();

        $email_data['finder_name'] = $data['finder_name'];
        $email_data['customer_name'] = $data['customer_name'];
        $email_data['customer_email'] = $data['customer_email'];
        $email_data['resp'] = $resp;
        $customermailer->externalVoucher($email_data);

        return $resp;
        

    }

    public function getSessionAttended($customer_id){
        return \Booktrial::where('customer_id', $customer_id)->where('post_trial_status', 'attended')->count();
    }

    public function getVoucherType($sessions_attended){
        $voucher_grid = Config::get('app.voucher_grid');
        foreach($voucher_grid as $value){
            if(empty($value['max']) || ($sessions_attended >= $value['min'] && $sessions_attended < $value['max'])){
                return $value['type'];
            }
        }
    }

    public function getGymServiceNamePPS(){
        return "Gym Workout";
    }

    public function reviewScreenData($data){
        $response['title'] = "Rate your Experience";
        $response['section_1'] = [
            'header'=>"How was your experience?",
            'rating_text'=>Config::get('app.rating_text')
        ];
        if(!empty($data['service_name']) && !empty($data['schedule_date_time'])){
            $response['header'] = "Share your experience for <b>".ucwords($data['service_name'])."</b> at<br/>".$data['finder_name'].", ".$data['finder_location']."<br>".date('jS M', strtotime($data['schedule_date_time']))." | ".date('D', strtotime($data['schedule_date_time']))." | ".date('h:i a', strtotime($data['schedule_date_time']));
            $response['image'] = "https://b.fitn.in/paypersession/Vendor%20Icon@3x.png";
        }else if(!empty($data['service_name'])){
            $response['header'] = "Share your experience for <b>".ucwords($data['service_name'])."</b> at<br/>".$data['title'].", ".$data['service_location'];
            $response['image'] = "https://b.fitn.in/paypersession/Vendor%20Icon@3x.png";
        }else{
            $response['section_1']['header']  = "Rate your overall experience at ".$data['title'];
        }
        $response['section_2'] = [
            'header'=>"Rate your experience basis following parameters (optional)",
            'detail_ratings' =>[]
        ];
        
        $detail_ratings_array = $data['category']['detail_rating'];

        foreach($detail_ratings_array as $key => $text){
            if(!empty($data['category']['detail_ratings_images'][$key])){
                array_push($response['section_2']['detail_ratings'], ['image'=>Config::get('app.aws.detail_ratings_images.url').$data['category']['detail_ratings_images'][$key], 'text'=>$text]);
            }else{
                array_push($response['section_2']['detail_ratings'], ['text'=>$text]);
            }
        }

        $response['block'] = false;
        

        return $response;
    }

    public function attachVoucher($type, $data){
        Log::info($type);
        Log::info($type);
        $voucher = \Externalvoucher::active()->where('type', $type)->where('customer_id', 'exists', false)->orderBy('_id')->first();

        Log::info('$voucher');
        Log::info($voucher);

        if($voucher){
            $voucher->customer_id = $data['customer_id'];
            $voucher->booktrial_id = $data['_id'];
            $voucher->save();
            return $voucher;
        }else{
            return null;
        }
    
    }
	public function getPrimaryCategory($finder_id=null,$service_id=null) {
		
		try {
			if(!empty($finder_id))
			{
				Finder::$withoutAppends=true;$finder=Finder::find(intval($finder_id))->with('category')->first(['category_id']);
				return (!empty($finder)&&!empty($finder->category))?$finder->toArray()['category']['name']:null;
			}
			else if(!empty($service_id))
			{
				Service::$withoutAppends=true;$service=Service::find(intval($service_id))->with('category')->first(['servicecategory_id']);
				return (!empty($service)&&!empty($service->category))?$service->toArray()['category']['name']:null;
			}
			else return null;
		} catch (Exception $e) {
			Log::error(" Error Message ::  ".print_r($e->getMessage(),true));
			return null;
		}
		return null;
	}
	public function getWSNonPeakPrice($start=null,$end=null,$workoutSessionPrice=null,$service_cat=null,$just_tag=false) {
		Log::info(func_get_args());
		try {
			$peak=true;
			if(!empty($start)&&!empty($end)&&!empty($service_cat))
			{   
				if($service_cat=='gym')
				{
					if(intval($start)>=Config::get('app.non_peak_hours.gym.start')&&intval($end)<=Config::get('app.non_peak_hours.gym.end'))
					{
						(!$just_tag)?$workoutSessionPrice=Config::get('app.non_peak_hours.gym.off')*intval($workoutSessionPrice):"";$peak=false;
					}
				}
				else if(intval($start)>=Config::get('app.non_peak_hours.studios.start')&&intval($end)<=Config::get('app.non_peak_hours.studios.end'))
				{
					(!$just_tag)?$workoutSessionPrice=Config::get('app.non_peak_hours.studios.off')*intval($workoutSessionPrice):"";$peak=false;
				}			
			}
			return ['wsprice'=>$workoutSessionPrice,'peak'=>$peak];
		} catch (Exception $e) {
			Log::error(" Error Message ::  ".print_r($e->getMessage(),true));
			return null;
		}
		return null;
	}
	public function getPeakAndNonPeakPrice($slots=[],$service_cat=null) {
	
		try {
			if(empty($service_cat))throw new Exception("No Service Category Exists.");
            $resp=[];
			foreach ($slots as $value) 
			{
				if(!isset($resp['peak'])||!isset($resp['non_peak']))
				{
                    $temp=$this->getWSNonPeakPrice($value['start_time_24_hour_format'],$value['end_time_24_hour_format'],$value['price'],$service_cat,false);
					if(!empty($temp)&&isset($temp['wsprice']))
					{
                        if(!empty($temp['peak'])){
                            $resp['peak']=intval($temp['wsprice']);
                        }
						else{
                            $resp['non_peak']=intval($temp['wsprice']);
                            $resp['non_peak_discount'] = $value['price'] - $resp['non_peak'];
                        } 
					}
				}
				else return $resp;
            }
            return $resp;
		} catch (Exception $e) {
			Log::error(" Error Message ::  ".print_r($e->getMessage(),true));
			throw $e;
		}
		return null;
	}
	
	public function getWsSlotPrice($start=null,$end=null,$service_id=null,$start_date=null ) {
		Log::info(func_get_args());
		try {
				if(!isset($service_id))throw new Exception("Service id Not Defined.");
				else if(!isset($start_date))throw new Exception("Start Date not present.");
				else if(!isset($start)||!isset($end))throw new Exception("Start/End Not Defined.");
				else {
						$day=$this->days[date("w",strtotime($start_date))];
						if(empty($day))throw new Exception("Day Not present in the schedules.");	
                        
                        $start=(int)date('G', strtotime($start));
                        $end=(int)date('G', strtotime($end));
                        
						Service::$withoutAppends=true;
						$service=Service::where("_id",intval($service_id))->first(['workoutsessionschedules', 'finder_id']);
                        Finder::$withoutAppends = true;
                        $finder = Finder::find($service['finder_id'], ['category_id']);
                        if($finder['category_id'] == 47){
                            return null;
                        }
						if(isset($service)&&!empty($service->workoutsessionschedules))
						{
							$r=array_values(array_filter($service->workoutsessionschedules, function($a) use ($day){return !empty($a['weekday'])&&$a['weekday']==$day;}));
                            Log::info($r);
							if(!empty($r[0])&&!empty($r[0]['slots']))
									{
                                        Log::info('$rzdcsc');

										$r=$r[0]['slots'];
                                        $r=array_values(array_filter($r, function($a) use ($start,$end){return isset($a['start_time_24_hour_format'])&&isset($a['end_time_24_hour_format'])&&$a['start_time_24_hour_format'] >=$start&&$a['end_time_24_hour_format'] <=$end;}));
                                        Log::info($r);
                                        return $price = $this->getPeakAndNonPeakPrice($r, $this->getPrimaryCategory(null,$service['_id']));
										// return (!empty($r[0])&&isset($r[0]['price']))?$r[0]['price']:null;
									}else return null;
						}else return null;
					}
		} catch (Exception $e) {
			Log::error(" Error Message ::  ".print_r($e->getMessage(),true));
			throw $e;
		}
		return null;
	}
	
	
	public function getAnySlotAvailablePNp($requested_date,$service_details) {
		$closeDate=date('Y-m-d', strtotime($requested_date.' + 7 days'));
		$iterDate=date('Y-m-d', strtotime($requested_date));
		$p_np=null;
		while($closeDate!==$iterDate)
		{
			$day=$this->getDayWs($requested_date);
			if(!empty($day))
			{
				$tmp=array_values(array_filter($service_details['workoutsessionschedules'],function ($e) use($day){if($e['weekday']==$day)return $e;}));
				if(!empty($tmp))
				{
					$p_np=$this->getPeakAndNonPeakPrice($tmp[0]['slots'],$this->getPrimaryCategory(null,$service_details['_id']));
					return $p_np;
				}
			}
			else $iterDate=date('Y-m-d', strtotime($requested_date. ' + 1 days'));
		}
		return $p_np;
	}
	
    public function removeMobileCodes($coups=[])
    {
    	return array_filter($coups,function ($e) { return !empty($e)&&empty($e['app_only']);});
    }
    
    public function removeAlreadyUsedCodes($coups=[],$customer_id,$single=false)
    {
    	if(!$single)
    	{
	    	\Order::$withoutAppends = true;
	    	$order_codes=\Order::active()->where("customer_id", $customer_id)->whereIn('coupon_code', $coups)->where('coupon_discount_amount', '>', 0)->get();
	    	
	    	if(!empty($order_codes))
	    	{
	    		$order_codes=$order_codes->toArray();
	    		$already_applied_codes=array_pluck($order_codes, 'coupon_code');
	    		return array_filter($coups,function ($e) use ($already_applied_codes){ return !empty($e)&&!empty($e['code'])&&!in_array($e['code'],$already_applied_codes);});
	    	}
	    	else return $coups;    		
    	}
    	else {
    		\Order::$withoutAppends = true;
    		$order_codes=\Order::active()->where("customer_id", $customer_id)->where('coupon_code',$coups['code'])->where('coupon_discount_amount', '>', 0)->count();
    		if($order_count >= 1)
    			return false;
    		else return true;
    	}
    }
    public function allowSpecificvendors($coups=[],$finder_id=null,$service_id=null,$single=false)
    {
    	if(!$single)
    	{
    		return array_filter($coups,function ($e) use($finder_id,$service_id) {
    			
    			$output=!empty($e)&&!empty($finder_id)&&!empty($service_id)&&!empty($e['finders'])&&!empty($e['services'])&&in_array($finder_id, $e['finders'])&&in_array($finder_id, $e['services']);
    			
    			if($output&&(empty($e['finders_exclude'])||!empty($e['finders_exclude'])&&!in_array($finder_id, $e['finders_exclude'])))
    				return true;
    				else return false;
    		});
    	}
    	else {
    		
    		$output=!empty($finder_id)&&!empty($service_id)&&!empty($coups['finders'])&&!empty($coups['services'])&&in_array($finder_id, $coups['finders'])&&in_array($service_id, $coups['services']);
    		if($output&&(empty($coups['finders_exclude'])||!empty($coups['finders_exclude'])&&!in_array($finder_id, $e['finders_exclude'])))
    			return true;
    		else return false;
    	}
    	
    }
    
    public function allowFitternityUsers($coups=[],$customer_id=null,$customer_email=null)
    {
    	$customer = \Customer::find((int)$customer_id);
    	if(!$single)
    	{
    		return array_filter($coups,function ($e) use($customer_id,$customer_email) {
    			return in_array($customer_email, ['utkarshmehrotra@fitternity.com','shahaansyed@fitternity.com','maheshjadhav@fitternity.com']);
    		});
    	}
    	else in_array($customer_email, ['utkarshmehrotra@fitternity.com','shahaansyed@fitternity.com','maheshjadhav@fitternity.com']);
    }

	public function getRateCardBaseID($ratecards=[])
	{
		foreach ($ratecards as $value)
			if(!empty($value)&&!empty($value['_id']))
				return $value['_id'];
			return "";
	}
	
	public function mapProperties($properties=null)
	{
		$props_arr=[];
		if(!empty($properties))
		{
			foreach ($properties as $k=>$v)
				(!empty($k)&&!empty($v))?array_push($props_arr,["field"=>$k,"value"=>$v]):"";	
				return  $props_arr;
		}
		else return null;
	}
	public function slashPriceFormat($selectedRatecard)
	{
        return "";
		return (empty($selectedRatecard)||empty($selectedRatecard['slash_price']))?"":'<strike>'.$this->getRupeeForm($selectedRatecard['slash_price']).'</strike>';
	}
	
	

//     public function getRupeeForm($amount){
//     	return (isset($amount)?'\u20B9'.' '.$amount:"");
//     }
    
    public function getAttendedResponse($status='attended',$booktrial,$customer_level_data,$pending_payment,$payment_done,$fitcash,$add_chck)
    {
    	if($status=='attended')
    	{
    		$response = [
    				'status'=>200,
    				'header'=>'CHCEK-IN SUCCESSFUL',
    				'image'=>'https://b.fitn.in/paypersession/cashback.png',
    				// 'footer'=>$customer_level_data['current_level']['cashback'].'% Cashback has been added in your Fitternity Wallet. Use it to book more workouts and keep on earning!',
    				// 'streak'=>[
    				// 		'header'=>'STREAK IT OUT',
    				// 		'data'=>$this->getStreakImages($customer_level_data['current_level']['level'])
    				// ]
    		];
    		
    		if($payment_done){
    			$response['sub_header_1'] = $customer_level_data['current_level']['cashback']."% Cashback";
    			$response['sub_header_2'] = " has been added in your Fitternity Wallet. Use it to book more workouts and keep on earning!";
    		}else $response['payment'] = $pending_payment;
    		
    		if($booktrial['type'] == 'booktrials'){
    			if (isset($add_chck['sub_header'])){
    				$response['sub_header_1'] = $add_chck['sub_header'];
    				$response['sub_header_2'] = $add_chck['message'];
    			}
    			else{
    				$response['sub_header_1'] = $fitcash." Fitcash";
    				$response['sub_header_2'] = " has been added in your Fitternity Wallet. Use it to buy membership with lowest price";
    			}
            }
            
            
            
            $this->deleteSelectCommunication(['transaction'=>$booktrial, 'labels'=>["customer_sms_after2hour","customer_email_after2hour","customer_notification_after2hour"]]);
    		
    	}
    	else
    	{
    		$response = [
    				'status'=>200,
    				'header'=>'OOPS!',
    				'image'=>'https://b.fitn.in/paypersession/sad-face-icon.png',
    				'sub_header_2'=>'Make sure you attend next time to earn Cashback and continue working out!',
    				'footer'=>'Unlock level '.$customer_level_data['current_level']['level'].' which gets you '.$customer_level_data['current_level']['cashback'].'% cashback upto '.$customer_level_data['current_level']['number'].' sessions! Higher the Level, Higher the Cashback',
    				'streak'=>[
    						'header'=>'STREAK IT OUT',
    						'data'=>$this->getStreakImages($customer_level_data['current_level']['level'])
    				]
    		];
    		
    		if(isset($customer_level_data['next_level']['level'])){
    			$response['streak']['footer'] = 'Unlock level '.$customer_level_data['next_level']['level'].' which gets you '.$customer_level_data['next_level']['cashback'].'% cashback upto '.$customer_level_data['next_level']['number'].' sessions! Higher the Level, Higher the Cashback';
    		}
    		if($payment_done){
    			$response['sub_header_2'] = "Make sure you attend next time to earn Cashback and continue working out!\n\nWe will transfer your paid amount in form of Fitcash within 24 hours.";
    		}
    		if($booktrial->type=='booktrials'){
    			
    			$response['reschedule_button'] = true;
    			$response['sub_header_2'] = "We'll cancel you from this batch. Do you want to reschedule instead?";
    			
    		}
    		$this->deleteSelectCommunication(['transaction'=>$booktrial, 'labels'=>["customer_sms_after2hour","customer_email_after2hour","customer_notification_after2hour"]]);
    		
    	}
    	
    	if($booktrial->type == 'booktrials' && isset($response['streak'])){
    		unset($response['streak']);
    	}
    	if($booktrial->type == 'workout-session'){
            $response['milestones'] = $this->getMilestoneSection();
        }
    	$description = "";
    	
    	if(isset($response['sub_header_1'])){
    		$description = "<font color='#f7a81e'>".$response['sub_header_1']."</font>";
    	}
    	
    	if(isset($response['sub_header_2'])){
    		$description = $description.$response['sub_header_2'];
    	}
    	$response['description'] = $description;
    	$response['trial_id'] = (string)$booktrial->_id;
    	$response['finder_id'] = $booktrial->finder_id;
    	$response['service_id'] = $booktrial->service_id;
    	return $response;
    }
    
    public function alreadyPurchasedAnyThing()
    {
    	
    	
    }
    
    
    
    public function getScheduleTimeBasedView()
    {
    	$currentDateTime=time();
    	$date         			=   date('Y-m-d',$currentDateTime);
    	$timestamp    			=   strtotime($date);
    	$weekday     			=   strtolower(date( "l", $timestamp));
    	
    	
    	
    	
    }
    
    
    
    public function displayFormatSlot($data=[],$service=null,$ratecard_id=null)
    {
    	$view=[];
    	if(!empty($data)&&count($data)>0)
    	{
    		foreach ($data as $value)
    			if(!empty($service)&&!empty($service['name'])&&!empty($value['slot_time']))
    				array_push($view, ['slot'=>$value['slot_time'],'service_id'=>$service['_id'],'service_name'=>(!empty($service['name'])?$service['name']:""),"ratecard_id"=>(!empty($ratecard_id)?$ratecard_id:"")]);
    	}
    	return $view;
    }
    public function getCoreSlotsView($slots,$service,$ratecard_id,$cost,$type,$cat_id,$cust=null,$device_type=null,$paymentmode_selected=[],$schedule_date=null,$wallet_pass=[],$sp=null,$rp=null,$city_id=null)
    {
    		try {
    			$baseData=['service_id'=>$service['_id'],'service_name'=>$service['name'],'schedule_date'=>$schedule_date,'finder_id'=>$service['finder_id'],'ratecard_id'=>$ratecard_id,'price'=>intval($cost),'type'=>$type,'qrcodepayment'=>true,'customer_source'=>$device_type,
    					'btnview'=>($type== 'booktrials')?'Book':"Book"];
    			if(!empty($city_id))$baseData['city_id']=$city_id;
    			if($type!= 'booktrials')
    				$baseData=array_merge($baseData,$wallet_pass,$paymentmode_selected,['premium_session'=>true]);
    			else $baseData=array_merge($baseData,['premium_session'=>false]);
                if(!empty($sp))$baseData['special_price']=$sp;
    			if(!empty($rp))$baseData['price']=$rp;
                $cur=time();
                if(!empty($slots))
                {
                    $mainSlots=[];
    					if(in_array(intval($cat_id),[65]))
    					{
    						if(!empty($slots[0]))
    						{
    							if(round(($cur-$slots[0]['epoch_start_time'])/60,2)>=0&&round(($slots[0]['epoch_end_time']-$cur)/60,2)>=0)
    							{
    								array_push($mainSlots, $this->mergeCustomerToSlot(['schedule_slot'=>date("h:i a",$cur)],$baseData,$cust));
    							}
    							else if(round(($slots[0]['epoch_start_time']-$cur)/60,2)<=60)
    							{
    								array_push($mainSlots, $this->mergeCustomerToSlot(['schedule_slot'=>date("h:i a",$cur)],$baseData,$cust));
    							}
    						}
    					}
    					else {
    						foreach ($slots as $key=>$value)
    						{
    							if(!empty($value['start_time_24_hour_format'])&&!empty($value['end_time_24_hour_format'])&&!empty($value['epoch_start_time']) &&!empty($value['epoch_end_time']))
    							{
    								if(round(($cur-$value['epoch_start_time'])/60,2)>=0&&round(($value['epoch_end_time']-$cur)/60,2)>=0)
    								{
    									array_push($mainSlots, $this->mergeCustomerToSlot(['schedule_slot'=>$value['slot_time']],$baseData,$cust));
    									if(!empty($slots[($key+1)]))
    									{
    										//     									if(round(($slots[($key+1)]['epoch_start_time']-$cur)/60,2)<=60)
    											//     									{
    										array_push($mainSlots, $this->mergeCustomerToSlot(['schedule_slot'=>$slots[($key+1)]['slot_time']],$baseData,$cust));
    										//     									}
    										break;
    									}
    									break;			
    								}
    							}
    						}
    						if(count($mainSlots)==0&&count($slots)>0)
    							array_push($mainSlots, $this->mergeCustomerToSlot(['schedule_slot'=>$slots[0]['slot_time']],$baseData,$cust));
    						
    					}
    					if(!empty($mainSlots)){
                            foreach($mainSlots as &$slot1){
                                $time_array = explode('-', $slot1['schedule_slot']);
                                $start = $time_array[0];
                                if(empty($time_array[1])){
                                    $time_array[1] = date('g:i a',strtotime('+1 hour', strtotime($start)));
                                }
                                $end = $time_array[1];
                                $dynamic_price = $this->getWsSlotPrice($start, $end, $slot1['service_id'], date('d-m-Y', time()));
                                isset($dynamic_price['non_peak']) ? ($slot1['special_price'] = $dynamic_price['non_peak']) : (isset($dynamic_price['peak']) ? ($slot1['special_price'] = $dynamic_price['peak']) : null);
                            }
                        }
    					return $mainSlots;
    				}else return [];
    		} catch (Exception $e) {
    			Log::error(" Error Message ::  ".print_r($e->getMessage(),true));
    			throw $e;
    		}
    		return [];
    }
    	
    private function mergeCustomerToSlot($data=[],$baseData=[],$cust=null)
    {
        if(!empty($cust))
            return array_merge($data,$baseData,["customer_name"=>(!empty($cust['name'])?$cust['name']:""),"customer_email"=>(!empty($cust['email'])?$cust['email']:""),"customer_phone"=>(!empty($cust['contact_no'])?$cust['contact_no']:""),"customer_gender"=>(!empty($cust['gender'])?$cust['gender']:"")]);
        else return $data;
    }
    
	public function getCouponCodeAttach($data,$amount)
	{	
		try {
				$resp=['status'=>1,"message"=>"Coupon Successfully applied."];
				$cur=new \DateTime();
				$data['coupon'] = strtolower($data['coupon']);
				$coupon=\Coupon::active()->where("start_date","<=",$cur)->where("end_date",">=",$cur)->whereIn("ratecard_type",['product'])->where("code",$data['coupon'])->first();
				if(!empty($coupon))
				{
					
					$coupon=$coupon->toArray();
					if(!empty($coupon['once_per_user']))
					{
						$already_applied_coupon = Customer::where('_id',$data['customer']['customer_id'])->whereIn('product_codes',[$data['coupon']])->count();
						if($already_applied_coupon>0)return ['status'=>0,"message"=>"Coupon can't be used twice by the same user."];
					}
					if(!empty($coupon['total_available']))
					{
						if(!empty($coupon['total_used']))
						{
							if(intval($coupon['total_used'])>=intval($coupon['total_available']))
								return ['status'=>0,"message"=>"Coupons of this type already exhausted.Better luck next time."];
						}
					}
					if(!empty($coupon['finders']))
					{
						if((empty($data['finder'])||empty($data['finder']['finder_id'])))
							return ['status'=>0,"message"=>"Coupon can only be applied on specific vendors"];
							else if(!in_array($data['finder']['finder_id'], $coupon['finders']))
							return ['status'=>0,"message"=>"Coupon can't be applied for ".$data['finder']['finder_name']];
					}
					return $resp=['status'=>1,"message"=>"Coupon can be applied Found.","coupon"=>$coupon];
				}
				else return ['status'=>0,"message"=>"Invalid Coupon"];
			
		} catch (Exception $e) {
			return  ['status'=>0,"message"=>$this->baseFailureStatusMessage($e)];
		}

	} 
	
    public function createGiftFitcashCoupon($order){
        
        $constant_code = "rakhi-".strtolower(substr($order['receiver_name'], 0, 4));
        $coupon_code = $constant_code;
        while($coupon = \Fitcashcoupon::where('code', $coupon_code)->first()){
            $coupon_code = $constant_code.'-'.$this->generateRandomString(3);
        }
        
        $fitcash_coupon_data = [
            'valid_till' => strtotime('+1 month', time()),
            'expiry' => strtotime('+1 month', time()),
            'amount' => $order['fitcash_coupon_amount'],
            'expiry' => strtotime('+1 month', time()),
            'order_type'=>['workout-session'],
            'quantity'=>1,
            'code'=>strtolower($coupon_code),
            'remove_wallet_limit'=>true
        ];
        $fitcash_coupon = $this->createFitcashCoupn($fitcash_coupon_data);

        return $fitcash_coupon;

    }

    public function createFitcashCoupn($data){
        
        $rules = array(
            'valid_till'=>'required | numeric',
            'expiry'=>'required | numeric',
            'amount'=>'required | numeric',
            'code'=>'required',
        );
        
        $validator = Validator::make($data,$rules);
        
        if ($validator->fails()) {
            return Response::json(array('status' => 404,'message' => error_message($validator->errors())),400);
        }
        
        $coupon_data = array_only($data, ['valid_till', 'expiry', 'amount', 'conditions', 'quantity', 'code', 'order_type', 'remove_wallet_limit']);

        $coupon_data['type'] = 'fitcashplus';

        $fitcash_coupon = new \FitcashCoupon($coupon_data);

        $fitcash_coupon->save();

        return $fitcash_coupon;

    } 
    
    public function assignVoucher($customer, $voucher_category){

        $already_assigned_voucher = \LoyaltyVoucher::
                where('milestone', $voucher_category->milestone)
                ->where('voucher_category', $voucher_category->_id)
                ->where('customer_id', $customer['_id'])
                ->orderBy('_id', 'asc')
                ->first();

        if($already_assigned_voucher){
            return $already_assigned_voucher;
        }

        if(!empty($voucher_category['flags']['manual_redemption'])){

            $new_voucher =  $this->assignManualVoucher($customer, $voucher_category);
        
        }else{

            $new_voucher = \LoyaltyVoucher::active()
                ->where('voucher_category', $voucher_category->_id)
                ->where('customer_id', null)
                // ->where('expiry_date', '>', new \DateTime())
                ->orderBy('_id', 'asc')
                ->first();
            
            if(!$new_voucher){
                return;
            }
        
        }

        $new_voucher->customer_id = $customer['_id'];
        $new_voucher->name = $voucher_category['name'];
        $new_voucher->image = $voucher_category['image'];
        $new_voucher->terms = $voucher_category['terms'];
        $new_voucher->amount = $voucher_category['amount'];
        $new_voucher->claim_date = new \MongoDate();

        if(isset($voucher_category['flags'])){
            $new_voucher->flags = $voucher_category['flags'];
        }

        $new_voucher->update();

        return $new_voucher;

    }


    public function getMilestoneSection($customer=null, $brand_milestones=null){

        if(empty($customer)){
            $jwt_token = Request::header('Authorization');
		    $decoded = decode_customer_token($jwt_token);
		    $customer_id = $decoded->customer->_id;
		    $customer = Customer::find($customer_id);
        }
        if(empty($customer['loyalty'])){
            return ['data'=>[]];
        }
        
        
        $milestone_no = 1;
        $check_ins = !empty($customer->loyalty['checkins']) ? $customer->loyalty['checkins'] : 0;
        $customer_milestones = !empty($customer->loyalty['milestones']) ? $customer->loyalty['milestones'] : [];
        $milestone_no = count($customer_milestones);
        $brand_loyalty = !empty($customer->loyalty['brand_loyalty']) ? $customer->loyalty['brand_loyalty'] : null;
        $brand_loyalty_duration = !empty($customer->loyalty['brand_loyalty_duration']) ? $customer->loyalty['brand_loyalty_duration'] : null;
        $brand_version = !empty($customer->loyalty['brand_version']) ? $customer->loyalty['brand_version'] : null;
        
        $post_register_milestones = Config::get('loyalty_screens.milestones');
        $checkin_limit = Config::get('loyalty_constants.checkin_limit');
        
        $finder_milestones = $this->getFinderMilestones($customer, $brand_milestones);

        if(!empty($finder_milestones)){
            $post_register_milestones['data'] = $finder_milestones['milestones'];
            $checkin_limit = $finder_milestones['checkin_limit'];
        }

        foreach($post_register_milestones['data'] as &$milestone){
            
            if(!empty($milestone['next_count'])){
                
                if($milestone['milestone'] < $milestone_no){
                    $milestone['enabled'] = true;
                    $milestone['progress'] = 100;
                }else{
                    $milestone['enabled'] = true;
                    $milestone_next_count = $milestone['next_count'];
                        $milestone['progress'] = round(($check_ins-$milestone['count'])/($milestone['next_count']-$milestone['count']) * 100);
                    break;
                }
            }
        }
        if(empty($milestone_next_count)){
            $milestone_next_count = $checkin_limit;
            $post_register_milestones['all_milestones_done'] = true;
        }
        $post_register_milestones['milestone_next_count'] = $milestone_next_count;
        unset($milestone);
        $post_register_milestones['subheader'] = strtr($post_register_milestones['subheader'], ['$next_milestone_check_ins'=>$milestone_next_count-$check_ins, '$next_milestone'=>$milestone_no+1]);
        $post_register_milestones['description'] = strtr($post_register_milestones['description'], ['$check_ins'=>$check_ins, '$milestone_next_count'=>$milestone_next_count]);

        return $post_register_milestones;
    }

    public function getLoyaltyRegHeader(){
       return Config::get('loyalty_screens.success_loyalty_header');
    }

    public function addCheckin($data){

		try{

            if(!empty($data['sub_type']) && $data['sub_type'] == 'booktrials'){
				return ['status'=>400, 'message'=>'Checkin not registered for booktrials'];
            }

    		if(Config::get('app.vendor_communication')){
		        $already_checkedin =  Checkin::where('customer_id', $data['customer_id'])->where('date', new DateTime(date('d-m-Y', time())))->first();
            }

			if(!empty($already_checkedin)){
				return ['status'=>200, 'message'=>'Already checked-in for today', 'already_checked_in'=>true];
			}
            $customer_id = $data['customer_id'];
            $customer = Customer::where('_id', $customer_id)->where('loyalty.start_date', 'exists', true)->first(['loyalty']);
            $brand_loyalty = !empty($customer->loyalty['brand_loyalty']) ? $customer->loyalty['brand_loyalty'] : null;
            $brand_loyalty_duration = !empty($customer->loyalty['brand_loyalty_duration']) ? $customer->loyalty['brand_loyalty_duration'] : null;
            $brand_version = !empty($customer->loyalty['brand_version']) ? $customer->loyalty['brand_version'] : null;



            if(empty($customer)){
				return ['status'=>400, 'message'=>'Customer not registered'];
            }
            
            
			$checkin = new \Checkin();
			$checkin->finder_id = $data['finder_id'];
			$checkin->customer_id = $customer_id;
            $checkin->date = new \DateTime(date('d-m-Y', time()));
            
            if(!empty($_GET['lat']) && !empty($_GET['lon'])){
                $data['lat'] = floatval($_GET['lat']);
                $data['lon'] = floatval($_GET['lon']);
            }

            if(!empty(\Input::get('lat')) && !empty(\Input::get('lon'))){
                $data['lat'] = floatval(\Input::get('lat'));
                $data['lon'] = floatval(\Input::get('lon'));
            }

            if(Request::header('Device-Type')){
                $checkin->device_type = Request::header('Device-Type');
            }

            if(Request::header('App-Version')){
                $checkin->app_version = Request::header('App-Version');
            }
            
            $fields = ['sub_type', 'tansaction_id', 'type', 'fitternity_customer', 'unverified','lat','lon','receipt'];

            foreach($fields as $field){
                if(isset($data[$field])){
                    $checkin->$field = $data[$field];
                }
            }

            $milestones = Config::get('loyalty_constants.milestones', []);

            if(is_numeric($brand_loyalty) && is_numeric($brand_loyalty_duration)){
                if(!empty($brand_version)){
                    $finder_milestones = FinderMilestone::where('brand_id', $brand_loyalty)->where('duration', $brand_loyalty_duration)->where('brand_version', $brand_version)->first();
                }
                else {
                    $finder_milestones = FinderMilestone::where('brand_id', $brand_loyalty)->where('duration', $brand_loyalty_duration)->where('brand_version', 1)->first();
                }
                // $finder_milestones = FinderMilestone::where('brand_id', $brand_loyalty)->where('duration', $brand_loyalty_duration)->first();
                if($finder_milestones){
                    $milestones = $finder_milestones['milestones'];
                    $checkin->unverified = false;
                    $checkin->brand_loyalty = $brand_loyalty;
                }

            }

			$checkin->save();
            
            if(!empty($data['finder_id']) && !empty($data['type']) && $data['type'] == 'membership'){
                if(empty($customer)){
                    $customer = Customer::find($customer_id, ['loyalty']);
                }
                $loyalty = $customer->loyalty;
                $memberships = !empty($loyalty['memberships']) ? $loyalty['memberships'] : [];
                
                if(!in_array($data['finder_id'], $memberships)){
                    array_push($memberships, $data['finder_id']);
                }
                $loyalty['memberships'] = $memberships;
                $customer->loyalty = $loyalty;
                
            }

            $all_checkins = \Checkin::where('customer_id', $customer_id)->get();

            // Log::info('$all_checkins');
            // Log::info("$all_checkins");

            $checkin_count = count($all_checkins);

            
            
                
            $milestone_checkins = array_column($milestones, 'count');
            
            $milestone_reached = array_search($checkin_count, $milestone_checkins);
            
            if(is_integer($milestone_reached)){
                
                $unverified_membership_checkins_count = count(array_where($all_checkins, function($key, $checkin){
                   return !empty($checkin['type']) && $checkin['type'] == 'membership' && !empty($checkin['unverified']);
                }));

                $receipt_checkins_count = count(array_where($all_checkins, function($key, $checkin){
                    return !empty($checkin['receipt']);
                }));
                
                Log::info("unverified_membership_checkins_count");
                Log::info($unverified_membership_checkins_count);
                
                $milestone = [
                    'milestone'=>$milestone_reached,
                    'date'=>new \MongoDate(),
                    'verified'=>empty($unverified_membership_checkins_count),
                ];

                if(!empty($receipt_checkins_count)){
                    $milestone['receipt_index'] = '1';
                }
                
                if(empty($customer)){
                    $customer = Customer::find($customer_id, ['loyalty']);
                }

                $loyalty = $customer->loyalty;

                $customer_milestones = !empty($loyalty['milestones']) ? $loyalty['milestones'] : [];

                array_push($customer_milestones, $milestone);

                $loyalty['milestones'] = $customer_milestones;

                $customer->loyalty = $loyalty;

                
            }

            if(!empty($customer)){
                $customer->update();
            }

            $customer_update = \Customer::where('_id', $data['customer_id'])->increment('loyalty.checkins');

            Log::info($customer_update);

            if(!empty($data['tansaction_id']) && !empty($data['type']) && $data['type'] == 'workout-session'){
                $booktrial_update = \Booktrial::where('_id', $data['tansaction_id'])->update(['checkin'=>$checkin->_id, 'from_add'=>true]);
            }

            Log::info('checkins updated in customer');

            Log::info($customer_update);


			return ['status'=>200, 'checkin'=>$checkin];
		}catch(Exception $e){
			Log::info($e);
			return ['status'=>500, 'message'=>'Please try after some time'];
		}
	}

    public function afterTranSuccess($data, $type){
        $checkin = null;
        $loyalty_registration = null;
        if($type == 'order'){
            $data['order_id']=$data['_id'];
            $loyalty_registration = $this->autoRegisterCustomerLoyalty($data);
        }else if($type == 'booktrial' && !isset($data['third_party_details'])){
            $data['booktrial_id']=$data['_id'];
            $loyalty_registration = $this->autoRegisterCustomerLoyalty($data);
            if(!empty($data['qrcodepayment']) && empty($data['checkin'])){
                $checkin = $this->addCheckin(['customer_id'=>$data['customer_id'], 'finder_id'=>$data['finder_id'], 'type'=>'workout-session', 'sub_type'=>$data['type'], 'fitternity_customer'=>true, 'tansaction_id'=>$data['_id'], 'lat'=>!empty($data['lat']) ? $data['lat'] : null, 'lon'=>!empty($data['lon']) ? $data['lon'] : null ]);
            }
        }
        
        return ['loyalty_registration'=>$loyalty_registration, 'checkin'=> $checkin];
    }

     public function uploadFileToS3Kraken($params){
        try{
            Log::info($params);

            $headersparms                       =   array("Cache-Control" => "max-age=2592000000");
            $input_object                       =   $params['input'];
            $local_directory                    =   $params['local_directory'];
            // $resize                               =   $params['resize'];
            $input_realname                     =   $input_object->getClientOriginalName();
            $upload_path                        =   $params['upload_path'].$params['file_name'];

		    $resp = $input_object->move($local_directory,$input_realname);
            $local_path = $local_directory.'/'.$input_realname;

            $original_upload_params = array(
                "file"      => $local_path,
                "wait"      => true,
                "lossy"     => false,
                // 'resize'    => $resize,
                "s3_store"  => array(
                    "key"   => Config::get('app.aws.key'),
                    "secret" => Config::get('app.aws.secret'),
                    "bucket" => Config::get('app.aws.bucket'),
                    "region" => Config::get('app.aws.region'),
                    "headers" => $headersparms,
                    'path'  =>$upload_path
                    )
                );

                Log::info($original_upload_params);
                // return;
                $kraken = new Kraken();
                $original_response = $kraken->upload($original_upload_params);
                Log::info($original_response);
                // return;
                unlink($local_path);
                return $original_response;
        }catch(Exception $e){
            Log::info($e);
            unlink($local_path);
            return false;
        }
    }


    public function tranformEventData($data){

        $rules = [
            'customer_data'=>'required',
        ];

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return array('status' => 404,'message' => error_message($validator->errors()));
        }

        $customer = $data['customer_data']['0'];

        $rules = [
            'firstname'=>'required | string',
            // 'lastname'=>'required | string',
            'customer_email'=>'required | email',
            'customer_phone'=>'required|regex:/[0-9]{10}/',
        ];
        
        $validator = Validator::make($customer,$rules);

        if ($validator->fails()) {
            return array('status' => 404,'message' => error_message($validator->errors()));
        }

        $data['customer_name'] = $customer['firstname'];

        if(!empty($customer['lastname'])){
            $data['customer_name'] = $data['customer_name'].' '.$customer['lastname'];
        }

        $data = array_merge($data, array_only($customer, ['customer_email', 'customer_phone']));

        return ['status'=>200, 'data'=>$data];

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
    		$order->_id =Order::max('_id')+1;
    		$order->save();
    		Log::info(" free dietplan order ".print_r($order,true));
    		return array('order_id'=>$order->_id,'status'=>200,'message'=>'Diet Plan Order Created Sucessfully');
    	}
    	else return array('status'=>0,'message'=>'Rate Card Not found for giving free diet plan');
    	
    }

    public function getRatecardDetail($data){

        $ratecard = Ratecard::find((int)$data['ratecard_id']);

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
                    $data['duration_day'] = $duration_day = (int)$ratecard['validity'];break;
                case 'months': 
                    $data['duration_day'] = $duration_day = (int)($ratecard['validity'] * 30) ; break;
                case 'year': 
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

        $data['offer_id'] = false;

        $offer = Offer::where('ratecard_id',$ratecard['_id'])
                ->where('hidden', false)
                ->orderBy('order', 'asc')
                ->where('start_date','<=',new DateTime(date("d-m-Y 00:00:00")))
                ->where('end_date','>=',new DateTime(date("d-m-Y 00:00:00")))
                ->first();

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
        
        
        //********************************************************************************** DYANMIC PRICING START**************************************************************************************************
        /* if($data['type'] == 'workout-session')
        {
        	try {
        		(isset($data['start_time'])&&isset($data['start_date'])&&isset($data['service_id'])&&isset($data['end_time']))?
        			$am_calc=$this->utilities->getWsSlotPrice($data['start_time'],$data['end_time'],$data['service_id'],$data['start_date']):"";
        		(isset($am_calc))?$data['amount_finder']=$am_calc:"";
        	} catch (Exception $e) {Log::error(" Error :: ".print_r($e,true));}
        } */
        //********************************************************************************** DYANMIC PRICING END****************************************************************************************************
        
        $data['amount'] = $data['amount_finder'];

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
        
        
        return array('status' => 200,'data' =>$data);

    }


    public function stripTags($string){
        return ucwords(str_replace("&nbsp;","",strip_tags($string)));
    }
    
    public function autoRegisterCustomerLoyalty($data){
        try{
            $customer = Customer::where('_id', $data['customer_id'])->where('loyalty', 'exists', false)->first();
            if(!$customer){
                return ['status'=>400, 'Customer already registered'];
            }
            
            if(empty($data['finder_flags']) && !empty($data['finder_id']) && !empty($data['order_success_flag']) && $data['order_success_flag'] == 'admin'){
                
                Finder::$withoutAppends = true;
                $finder = Finder::find($data['finder_id']);
                $data['finder_flags'] = !empty($finder['flags']) ? $finder['flags'] : [];
            
            }

            if(!empty($data['finder_flags']['reward_type']) && in_array($data['finder_flags']['reward_type'], Config::get('app.no_fitsquad_reg', [1]))){
                return ['status'=>400, 'message'=>'No fitsquad for vendor'];
            }
            
            
            $loyalty = [
                'start_date'=>new \MongoDate(strtotime('midnight')),
                'start_date_time'=>new \MongoDate()
            ];

            if(!empty($data['start_date'])){
                $loyalty['start_date'] = new \MongoDate(strtotime('midnight', strtotime($data['start_date'])));
                $loyalty['start_date_time'] = new \MongoDate(strtotime($data['start_date']));
            }
            $fields_to_add = array_only($data, ['order_id', 'booktrial_id', 'end_date', 'finder_id', 'type','custom_finder_name','customer_membership']);
            $loyalty = array_merge($loyalty, $fields_to_add);
            $duration = !empty($data['duration_day']) ? $data['duration_day'] : (!empty($data['order_duration_day']) ? $data['order_duration_day'] : 0);
            $duration = $duration > 180 ? 360 : $duration;
            
            if(!empty($data['order_id']) && !empty($data['type']) && !empty($data['finder_id']) && in_array($data['type'], ['memberships']) && in_array($duration, [180, 360])){
                if(empty($finder)){
                    Finder::$withoutAppends = true;
                    $finder = Finder::find($data['finder_id'], ['brand_id', 'city_id']);
                }
                if(!empty($finder['brand_id']) && !empty($finder['city_id']) && in_array($finder['brand_id'], Config::get('app.brand_loyalty')) && !in_array($finder['_id'], Config::get('app.brand_finder_without_loyalty'))){
                    $brand_loyalty = true;
                    $loyalty['brand_loyalty'] = $finder['brand_id'];
                    $loyalty['brand_loyalty_duration'] = $duration;
                    $loyalty['brand_loyalty_city'] = $data['city_id'];

                    if($loyalty['brand_loyalty'] == 135){
                        if($loyalty['brand_loyalty_duration'] == 180){
                            $loyalty['brand_version'] = 1;
                        }else{
                            $loyalty['brand_version'] = 2;
                        }
                    }else{
                        $loyalty['brand_version'] = 1;
                    }
                }
            }
            
            
            if(empty($brand_loyalty) && !empty($data['finder_flags']['reward_type']) && !empty($data['type']) && $data['type'] == 'memberships'){
                
                $loyalty['reward_type'] = $data['finder_flags']['reward_type'];
                if(!empty($data['finder_flags']['cashback_type'])){
                    $loyalty['cashback_type'] = $data['finder_flags']['cashback_type'];
                }
            }

            $update_data = [
                'loyalty'=>$loyalty 
            ];
            $customer_update = Customer::where('_id', $data['customer_id'])->where('loyalty', 'exists', false)->update($update_data);

            if($customer_update){
                return ['status'=>200];
            }else{
                return ['status'=>400, 'message'=>'Customer already registered'];
            }
            // if($customer_update && $this->sendLoyaltyCommunication($data)){

            //     $customermailer = new CustomerMailer();

            //     $customermailer->loyaltyRegister($customer->toArray());

            //     return ['status'=>200];
            // }else{
            //     return ['status'=>400, 'message'=>'Customer already registered'];
            // }
        
        }catch(Exception $e){
        
            Log::info(['status'=>400,'message'=>$e->getMessage().' - Line :'.$e->getLine().' - Code :'.$e->getCode().' - File :'.$e->getFile()]);
            return ['status'=>500, 'Please try after some time'];
        }
    
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

        return array('status' => 200,'data' =>$data);
    }

    public function getCustomerDetail($data){

        $data['customer_email'] = trim(strtolower($data['customer_email']));

        $customer_id = $data['customer_id'] = autoRegisterCustomer($data);

        $data['logged_in_customer_id'] = $customer_id;

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = customerTokenDecode($jwt_token);
            $data['logged_in_customer_id'] = (int)$decoded->customer->_id;
        }

        $customer = Customer::find((int)$customer_id);

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

        $device_type = (isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id = (isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

        if($device_type == '' || $gcm_reg_id == ''){

            $getRegId = getRegId($data['customer_id']);

            if($getRegId['flag']){

                $$device_type = $data["device_type"] = $getRegId["device_type"];;
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

    public function getLoyaltyRegisterUrl($finder_id=null){
        
        Log::info("getLoyaltyRegisterUrl");
        Log::info(Request::header('Mobile-Verified'));
        
        
        $url = Config::get('loyalty_constants.register_url').'?app=true&token='.Request::header('Authorization').'&otp_verified='.(!empty(Request::header('Mobile-Verified')) ? Request::header('Mobile-Verified'):'false');
        if(!empty($finder_id)){
            $url = Config::get('loyalty_constants.register_url').'?app=true&token='.Request::header('Authorization').'&otp_verified='.(!empty(Request::header('Mobile-Verified')) ? Request::header('Mobile-Verified'):'false').'&finder_id='.$finder_id;
        }
        Log::info($url);
        return $url;
    }

    public function updateCoupon($order){
        if(!empty($order['coupon_code']) && !empty($order['coupon_discount_amount'])){
            $coupon_update = \Coupon::where('code', strtolower($order['coupon_code']))->increment('total_used');
        }
    }

    public function assignManualVoucher($customer, $voucher_category){

        $voucher_data = [
            'voucher_category'=>$voucher_category['_id'],
            'status'=>"1",
            'description'=>$voucher_category['description'],
            'milestone'=>$voucher_category['milestone'],
            'expiry_date'=>date('Y-m-d H:i:s',strtotime('+1 month')),
            'code'=>$voucher_category['name'],
        ];

        if(!empty($voucher_category['flags']['diet_plan'])){
        
            $diet_plan = $this->generateFreeDietPlanOrder(['customer_name'=>$customer->name, 'customer_email'=>$customer->email,'customer_phone'=>$customer->contact_no]);

            if($diet_plan['status']!=200){
                return ['status'=>400, 'message'=> 'Cannot claim reward. Please contact customer support (4).'];
            }

            $voucher_data['diet_plan_order_id'] = $diet_plan['order_id'];
        }

        if(!empty($voucher_category['flags']['swimming_session'])){
            
            $voucher_data['code']  = $this->generateSwimmingCouponCode(['customer'=>$customer, 'amount'=>$voucher_category['amount'], 'description'=>$voucher_category['description'],'end_date'=>new MongoDate(strtotime('+2 months'))]);
            Log::info("asdsad");
        }
        
        return $voucher = \LoyaltyVoucher::create($voucher_data);

    }

    public function generateSwimmingCouponCode($data){

        $coupon = [
            "name" =>$data['description'],
            "discount_percent" =>0,
            "discount_max" =>$data['amount'],
            "discount_amount" =>$data['amount'],
            "start_date" =>new MongoDate(),
            "end_date" =>$data['end_date'],
        ];

        $coupon['and_conditions'] = [
            [
                "key" =>"service.servicecategory_id",
                "operator" =>"in",
                "values" =>[ 
                    123
                ]
            ],
            [
                "key" =>"logged_in_customer._id",
                "operator" =>"in",
                "values" =>[ 
                    $data['customer']['_id']
                ]
            ]   
        ];
        

        $coupon['once_per_user'] = true;
        $coupon['used'] = 0;
        $coupon["ratecard_type"] = [ "workout session"];
        $coupon["loyalty_reward"] = true;

        $coupon['code'] = $this->getSwimmingSessionCode();
        // print_r($coupon);
        // exit();
        
        $coupon = new Coupon($coupon);
        $coupon->_id = Coupon::max('_id')+1;
        $coupon->save();
        return $coupon['code'];

    }

    public function getSwimmingSessionCode(){
        $code = 'sw'.strtolower($this->generateRandomString());
        // print_r($code);
        // exit();
        $alreadyExists = Coupon::where('code', $code)->first();
        if($alreadyExists){
            return $this->getSwimmingSessionCode();
        }
        return $code;
    }

    public function encryptQr($data){
        define("ENCRYPTION_KEY", Config::get('app.core_key'));
        $string = $data;
        return $encrypted = $this->encrypt(json_encode($string), ENCRYPTION_KEY);
    }

    public function encrypt($pure_string, $encryption_key) {
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, $pure_string, MCRYPT_MODE_ECB, $iv);
        return bin2hex($encrypted_string);
    }
    
    public function checkTrialAlreadyBooked($finder_id,$service_id = false,$customer_email=null,$customer_phone="",$check_workout=false, $from=null){

    	$return = false;

    	if($finder_id == ""){
        	return false;
        }

    	$customer_id = "";
        $jwt_token = Request::header('Authorization');

        Log::info('jwt_token : '.$jwt_token);

        if(empty($customer_email) && $jwt_token == true && $jwt_token != 'null' && $jwt_token != null){
            $decoded = decode_customer_token();
            $customer_id = intval($decoded->customer->_id);
            $customer_email = $decoded->customer->email;
            $customer_phone = "";

            if(isset($decoded->customer->contact_no)){
				$customer_phone = $decoded->customer->contact_no;
			}
        }
        $booktrial_count = 0;
        
        if(!empty($customer_email)){

        	if($customer_phone != ""){

        		$query = \Booktrial::where(function ($query) use($customer_email, $customer_phone) {
								$query->orWhere('customer_email', $customer_email)
									->orWhere('customer_phone','LIKE','%'.substr($customer_phone, -10).'%');
							})
                        ->where('finder_id',(int)$finder_id)
                        ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"]);
            }else{

            	$query = \Booktrial::where('customer_email', $customer_email)
                        ->where('finder_id',(int)$finder_id)
                        
                        ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"]);

            }

            if(!$check_workout){
                $query->where('type','booktrials');
            }

            // if($service_id){
            // 	$query->where('service_id',(int)$service_id);
            // }
       
            $booktrial_count = $query->orderBy('created_at', 'desc')->get(['created_at'])->toArray();
        }

        if(!empty($from) && $from == 'checkoutSummary'){

            if(!empty($booktrial_count)){
               return $booktrial_count[0];
            }else{
                return false;
            }
        }
       
        if(!empty($booktrial_count)){
            Log::info("returning true=========================================");
        	$return = true;
        }

        return $return;

    }

    public function assignJockeyCoupon($order){

        if(empty($order['event_customers'])){
            return;
        }
        
        $event_customers = $order['event_customers'];
        
        foreach($event_customers as &$customer){
            if(!empty($customer['jockey_code'])){
                continue;
            }
            
            $code = \JockeyCode::where('order_id', 'exists', false)->first();

            if($code){
                $customer['jockey_code'] = $code['code'];
                $code->order_id = $order->_id;
                $code->update();
            }
        }

        Order::where('_id', $order['_id'])->update(['event_customers'=>$event_customers]);

    }

    public function getExtendedValidityOrder($data){
        
        if(!empty($data['order_customer_email'])){
            $data['customer_email'] = $data['order_customer_email'];
        }

        if(empty($data['customer_id']) && empty($data['customer_email'])){
            return;
        }

        $query = Order::active();
        
        if(!empty($data['customer_email'])){
            $query->where('customer_email', $data['customer_email']);
        }

        if(!empty($data['customer_id'])){
            $query->where('customer_id', $data['customer_id']);
        }

        if(!empty($data['schedule_date'])){
            $query
                // ->where('start_date', '<=', new MongoDate(strtotime($data['schedule_date'])))
                ->where(function($query) use ($data){
                    $query->where('ratecard_flags.unlimited_validity', true)->orWhere('end_date', '>=', new MongoDate(strtotime($data['schedule_date'])));
                });
        }

        return $query
            ->where(function($query) use ($data){ $query->orWhere('service_id', $data['service_id'])->orWhere('all_service_id', $data['service_id']);})
            ->where('sessions_left', '>', 0)
            ->first();;
    }


    public function getAllExtendedValidityOrders($data){

        
        if(!empty($data['order_customer_email'])){
            $data['customer_email'] = $data['order_customer_email'];
        }
        
        if(empty($data['customer_email']) && empty($data['customer_id'])){
            return null;
        }
        
        $order =  Order::active() 
                    // ->where('start_date', '<=', new DateTime())
                    ->where(function($query){
                        $query->where('ratecard_flags.unlimited_validity', true)->orWhere('end_date', '>=', new DateTime());
                    })
                    ->where('sessions_left', '>', 0);

        if(!empty($data['customer_email'])){
             $order->where('customer_email', $data['customer_email']);
        }

        if(!empty($data['customer_id'])){
            $order->where('customer_id', $data['customer_id']);
        }

        return  $order->first();

    }

    public function getExtendedValidityOrderFinder($data){


        if(!empty($data['order_customer_email'])){
            $data['customer_email'] = $data['order_customer_email'];
        }

        $orders = Order::active()->where('finder_id', $data['finder_id'])
            // ->where('start_date', '<=', new MongoDate(strtotime($data['schedule_date'])))
            ->where(function($query) use ($data){
                $query->where('ratecard_flags.unlimited_validity', true)->orWhere('end_date', '>=', new MongoDate(strtotime($data['schedule_date'])));
            })
            ->where('sessions_left', '>', 0);

        if(!empty($data['customer_email'])){
            return $orders->where('customer_email', $data['customer_email'])->get(['service_id','all_service_id']);
        }

        if(!empty($data['customer_id'])){
            return $orders->where('customer_id', $data['customer_id'])->get(['service_id','all_service_id']);
        }

        return null;
    }

    public function giveFitcashforUpgrade($order){

        if(!empty($order['upgrade_fitcash'])){
            return;
        }

        if(!empty($order['duration_day']) && !empty($order['servicecategory_id']) && in_array($order['servicecategory_id'], Config::get('upgrade_membership.service_cat', [65, 111])) && in_array($order['duration_day'], Config::get('upgrade_membership.duration', [30])) && empty($order['extended_validity'])){

            $fitcash_amount = $order['amount_customer'] - (!empty($order['convinience_fee']) ? $order['convinience_fee'] : 0);

            $no_of_days = Config::get('upgrade_membership.fitcash_days');

            $request = $walletData = array(
                "customer_id"=> $order->customer_id,
                "amount"=> $fitcash_amount,
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $fitcash_amount,
                "type"=>'FITCASHPLUS',
                'description'=>"Added FitCash+ for upgrading 1 month ".ucwords($order['service_name'])." to 1 Year only at ".$order['finder_name'].", Expires On : ".date('d-m-Y',time()+(86400*$no_of_days)),
                'entry'=>'credit',
                'valid_finder_id'=>$order['finder_id'],
                'service_id'=>$order['service_id'],
                'remove_wallet_limit'=>true,
                'validity'=>strtotime($order['start_date'])+(86400*$no_of_days),
                'duration_day'=>360,
                'restricted_for'=>'upgrade',
                'restricted'=>1,
                'order_id'=>$order['_id'],
            );
            
            $this->walletTransactionNew($request);
            
            $order->upgrade_fitcash = true;

        }elseif(!empty($order['extended_validity']) && in_array($order['finder_id'], Config::get('app.upgrade_session_finder_id', []))){
            $fitcash_amount = $order['amount_customer'] - (!empty($order['convinience_fee']) ? $order['convinience_fee'] : 0);

            $no_of_days = Config::get('upgrade_membership.fitcash_days');

            $request = $walletData = array(
                "customer_id"=> $order->customer_id,
                "amount"=> $fitcash_amount,
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $fitcash_amount,
                "type"=>'FITCASHPLUS',
                'description'=>"Added FitCash+ for upgrading ".ucwords($order['service_name'])." session pack to a membership only at ".$order['finder_name'].", Expires On : ".date('d-m-Y',time()+(86400*$no_of_days)),
                'entry'=>'credit',
                'valid_finder_id'=>$order['finder_id'],
                'remove_wallet_limit'=>true,
                'validity'=>strtotime($order['start_date'])+(86400*$no_of_days),
                'restricted_for'=>'upgrade',
                'restricted'=>1,
                'order_id'=>$order['_id'],
                'order_type'=>['membership', 'memberships'],
                'duration_day'=>Config::get('upgrade_membership.upgrade_session_duration', [90, 180, 360]),
            );
            
            $this->walletTransactionNew($request);
            
            $order->upgrade_fitcash = true;
        }
    }

    public function getCustomerFromToken(){
        
        $token = Request::header('Authorization');
        
        if(empty($token)){
            return;
        }
        
        $token_decoded = customerTokenDecode($token);

        $customer = $token_decoded->customer;

        return json_decode(json_encode($customer), true);

    }

    public function getCustomerFromTokenAsObject(){
        
        $token = Request::header('Authorization');
        
        if(empty($token)){
            return;
        }
        
        $token_decoded = customerTokenDecode($token);

        $customer = $token_decoded->customer;

        return $customer;

    }

    public function sessionPackMultiServiceDiscount($ratecard, $customer_email, $amount){
        
         if(empty($customer_email)){
            
            $customer = $this->getCustomerFromToken();
            
            if(empty($customer)){
                return;
            }

            $customer_email = $customer['email'];
        }

        return $extended_validity_order = Order::active()->where('customer_email', $data['customer_email'])->where('all_service_id', $data['ratercard']['service_id'])->where('start_date', '<=', new MongoDate(strtotime($data['schedule_date'])))->where('end_date', '>=', new MongoDate(strtotime($data['schedule_date'])))->where('sessions_left', '>', 0)->first();
        
    }

    public function getFinderMilestones($customer, $brand_milestones = null){
        
        if(!empty($brand_milestones)){
            return $brand_milestones;
        }
        
        $filter = $this->getMilestoneFilterData($customer);

        
        
        if(is_numeric($filter['brand_loyalty']) && is_numeric($filter['brand_loyalty_duration'])){
            
            if(!$brand_milestones){
                if(!empty($filter['brand_loyalty'])) {
                    if(!empty($filter['brand_version'])){
                        $brand_milestones = FinderMilestone::where('brand_id', $filter['brand_loyalty'])->where('duration', $filter['brand_loyalty_duration'])->where('brand_version', $filter['brand_version'])->first();
                    }
                    else {
                        $brand_milestones = FinderMilestone::where('brand_id', $filter['brand_loyalty'])->where('duration', $filter['brand_loyalty_duration'])->where('brand_version', 1)->first();
                    }
                }
                else {
                    $brand_milestones = FinderMilestone::where('brand_id', $filter['brand_loyalty'])->where('duration', $filter['brand_loyalty_duration'])->first();
                }
            }

        }else if(!empty($filter['reward_type'])){

			$brand_milestones = FinderMilestone::where('reward_type', $filter['reward_type']);

			if(in_array($filter['reward_type'], [3, 4, 5]) && !empty($filter['cashback_type'])){
				$brand_milestones = $brand_milestones->where('cashback_type', $filter['cashback_type']);
			}
			
            $brand_milestones = $brand_milestones->first();
            
        }
        
        if(empty($brand_milestones)){

            $brand_milestones = $this->getDefaultMilestones();
        
        }
        
        return $brand_milestones;
    
    }

    public function getMilestoneFilterData($customer){
        $filter = [];
        $filter['brand_loyalty'] = !empty($customer->loyalty['brand_loyalty']) ? $customer->loyalty['brand_loyalty'] : null;
        $filter['brand_loyalty_city'] = !empty($customer->loyalty['brand_loyalty_city']) ? $customer->loyalty['brand_loyalty_city'] : null;
        $filter['brand_loyalty_duration'] = !empty($customer->loyalty['brand_loyalty_duration']) ? $customer->loyalty['brand_loyalty_duration'] : null;
        $filter['brand_version'] = !empty($customer->loyalty['brand_version']) ? $customer->loyalty['brand_version'] : null;
        $filter['reward_type'] = !empty($customer->loyalty['reward_type']) ? $customer->loyalty['reward_type'] : null;
        $filter['cashback_type'] = !empty($customer->loyalty['cashback_type']) ? $customer->loyalty['cashback_type'] : null;
        return $filter;
    }

    public function getVoucherCategoriesAggregate($filter){
        
        return $voucher_categories = \VoucherCategory::raw(function($collection) use($filter){
				
            $match = [
                '$match'=>[
                    'status'=>'1',
                ]
                
            ];
            
            if(!empty($filter['brand_loyalty']) && !empty($filter['brand_loyalty_duration']) && !empty($filter['brand_loyalty_city'])){
                $match['$match']['brand_id'] = $filter['brand_loyalty'];
                $match['$match']['duration'] = $filter['brand_loyalty_duration'];
				$match['$match']['city'] = $filter['brand_loyalty_city'];
            }else{
                $match['$match']['brand_id'] =['$exists'=>false];
                $match['$match']['duration'] =['$exists'=>false];
                $match['$match']['city'] =['$exists'=>false];

                if(!empty($filter['reward_type']) ){
                    $match['$match']['reward_type'] = $filter['reward_type'];
                }else{
                    $match['$match']['reward_type'] = 2;
                }
    
                if(!empty($filter['cashback_type']) ){
                    $match['$match']['cashback_type'] = $filter['cashback_type'];
                }else{
                    $match['$match']['cashback_type'] =['$exists'=>false];
                }
			}
			if(!empty($filter['brand_loyalty'])) {
				if(!empty($filter['brand_version'])){
					$match['$match']['brand_version'] = $filter['brand_version'];
				}
				else {
					$match['$match']['brand_version'] = 1;
				}
			}


            // print_r($match);
            // exit();

            $sort =[
                '$sort'=>[
                    'order'=>1
                ]
            ];

            $group = [
                '$group'=>[
                    '_id'=>'$milestone',
                    'vouchers'=>['$push'=>'$$ROOT'],
                    'amount'=>['$max'=>'$amount']
                ]
            ];

            $sort1 = [
                '$sort'=>[
                    '_id'=>1
                ]
            ];
            $aggregate = [$match, $sort, $group, $sort1];
            Log::info($aggregate);
            // exit();
            return $collection->aggregate($aggregate);
        });
    
    }

    /**
     * @return mixed
     */
    public function getDefaultMilestones()
    {
        return Config::get('loyalty_constants');
    }

    public function sendLoyaltyCommunication($item){
        
        if(!empty($item['finder_flags']['reward_type']) && in_array($item['finder_flags']['reward_type'], Config::get('app.no_fitsquad_reg_msg'))){
            return false;
        }

        return true;
    }

    public function getLoyaltyEmailContent($order){
        
        // if(empty($order['loyalty_registration'])){
        //     return "";
        // }
        $cashback = 100;

        
        $reward_type =!empty($order['finder_flags']['reward_type']) ? $order['finder_flags']['reward_type'] :  1;
        $cashback_type = !empty($order['finder_flags']['cashback_type']);
        

        switch($cashback_type){
            case 1:
            case 2:
                $cashback = 120;
        }
        $msg = "";
        switch($reward_type){
            case 1:
            break;
            case 2:
            break;
            case 3:
                $msg = "Congratulations! You have got an exclusive access to earn ".$cashback."% cashback on your membership amount. Please download the Fitternity app , look for Fitsquad option and start check-in for your workout at ".$order['finder_name'];
            break;
            case 4:
                $msg = "Congratulations! You have got an exclusive access to earn exciting rewards & ".$cashback."% cashback on your membership amount. Please download the Fitternity app , look for Fitsquad option and start check-in for your workout at ".$order['finder_name'];
            break;
            case 5:
                $msg  = "Congratulations! You have got an exclusive access to earn ".$cashback."% cashback on your membership amount. Please download the Fitternity app , look for Fitsquad option and start check-in for your workout at ".$order['finder_name'];
            break;
            case 6:
                $msg  = "Congratulations! You have got an exclusive access to earn exciting rewards & ".$cashback."% cashback on your membership amount. Please download the Fitternity app , look for Fitsquad option and start check-in for your workout at ".$order['finder_name'];
            break;
        }

        return $msg;

    }


    public function getPPSSearchResult($data){
        $payload = [
            'category' =>!empty($data['localName']) && !empty($data['name']) ?
                [
                    [
                        'localName' => !empty($data['localName']) ? $data['localName'] : '',
                        'name' => !empty($data['name']) ? $data['name'] : '',
                        'subcategory' =>
                            [],
                    ],
            ] : [],
            'time_tag' => !empty($data['time_tag']) ? $data['time_tag'] : '',
            'keys' =>!empty($data['keys']) ? $data['keys'] :
                [
                'id',
                'address',
                'average_rating',
                'category',
                'commercial_type',
                'geolocation',
                'location',
                'name',
                'slug',
                'total_rating_count',
                'slots',
                'vendor_name',
                'price',
                'coverimage',
                'total_slots',
                'next_slot',
                'vendor_slug',
                'overlayimage',
                'trial_header',
                'membership_header',
            ],
            'location' =>
                [
                'city' => !empty($data['city']) ? $data['city'] : 'mumbai',
                'geo' =>
                    [
                    'lat' => !empty($data['lat']) ? $data['lat'] : null,
                    'lon' => !empty($data['lon']) ? $data['lon'] : null,
                    'radius' => !empty($data['radius']) ? $data['radius'] : null,
                ],
                'regions'=>!empty($data['regions']) ? $data['regions'] : [],
            ],
            'offset' =>
                [
                'from' => 0,
                'number_of_records' => !empty($data['number_of_records']) ? $data['number_of_records'] : "4",
            ],
            'price_range' => '',
            'skipTimings' => false,
            'sort' =>
                [
                'order' => 'desc',
                'sortField' => 'popularity',
            ],
        ];

        $url = "search/paypersession";

        $finder = [];

        try {
            $client = new Client( ['debug' => false, 'base_uri' =>Config::get('app.new_search_url')."/"] );
            $response  =   json_decode($client->post($url,['json'=>$payload])->getBody()->getContents(),true);
            return $response;
        }catch(Exception $e){
            Log::info($e);
            return null;
        }
    }
           
    public function validateInput($functionName, $data){

        switch($functionName){
            case 'generateFreeSP':
            $rules = [
                'customer_name'=>'required',
                'customer_email'=>'required|email',
                'customer_phone'=>'required',
                'order_id'=>'required|integer'
            ];
        }

        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return ['status' => 404,'message' => error_message($validator->errors())];
        }else{
            return ['status'=>200];
        }

    }

    public function getFreeSPRatecard($data, $source='order', $free_sp_rc_all=[]){

        try{
            
            if($source == 'ratecard'){
                $data['duration_day'] = $this->getDurationDay(($data));
            }
            
            $sessions = $duration = null;
            if(in_array($data['finder_id'], Config::get('app.women_mixed_finder_id')) && in_array($data['type'], ['memberships', 'membership']) && empty($data['extended_validity']) && !empty($data['duration_day']) && in_array($data['duration_day'], [360, 180, 30, 90])){
                switch($data['duration_day']){
                    case 30:
                    case 90:    
                        $duration = 4;
                        break;
                    case 180:
                        $duration= 12;
                        break;
                    case 360:
                        $duration= 20;
                }

                if(empty($free_sp_rc_all)){

                    $free_sp_rc_all = Ratecard::where('flags.free_sp', true);
                    if(!empty($data['finder_id'])){
                        $free_sp_rc_all->where('finder_id', $data['finder_id']);
                    }
                    if(!empty($data['service_id'])){
                        $free_sp_rc_all->where('service_id', $data['service_id']);
                    }
                    
                    $free_sp_rc_all = $free_sp_rc_all->where('duration', $duration)->get();
                }

                if(!empty($free_sp_rc_all)){
                    $free_sp_rc_all_map = [];

                    foreach($free_sp_rc_all as $f){
                        $free_sp_rc_all_map[$f['service_id'].'-'.$f['duration']] = $f;
                    }

                    if(!empty($free_sp_rc_all_map[$data['service_id'].'-'.$duration])){
                        $free_sp_rc = $free_sp_rc_all_map[$data['service_id'].'-'.$duration];
                    }
                }
                
                if(!empty($free_sp_rc)){
                    
                    return $free_sp_rc;
                
                }
            }
            

            return null;

        }catch(Exception $e){

            Log::info(['status'=>400,'message'=>$e->getMessage().' - Line :'.$e->getLine().' - Code :'.$e->getCode().' - File :'.$e->getFile()]);
            return null;

        }
    }

     public function getFreeSPRatecardsByFinder($data){
        return Ratecard::where('flags.free_sp', true)->where('finder_id', $data['finder_id'])->get();
    }

    public function addFitcashforVoucherCatageory($data){
        $validity = strtotime(date('d-m-Y')+(86400* 90));
        $request = array(
            "customer_id"=> $data['id'],
            "amount"=> $data['voucher_catageory']['fitcash'],
            "amount_fitcash" => 0,
            "amount_fitcash_plus" => $data['voucher_catageory']['fitcash'],
            "type"=>'FITCASHPLUS',
            "validity"=>$validity,
            'description'=>"Added FitCash for Fitsquad milestone ".$data['voucher_catageory']['milestone']." Expires On : ".date('d-m-Y', $validity),
            'entry'=>'credit',
            'for'=>'Fitsquad',
            'details'=> array(
                'for'=>'Fitsquad',
                'voucher_name'=>$data['voucher_catageory']['name'],
                'voucher_catageory_id'=> $data['voucher_catageory']['_id']
            )
        );
        $this->walletTransactionNew($request);
    }

    public function getRatecardPrice($ratecard){
        return !empty($ratecard['offers'][0]['price']) ? $ratecard['offers'][0]['price'] : (!empty($ratecard['special_price']) ? $ratecard['special_price'] : $ratecard['price']);        
    }

    public function getBatchDaysOfWeek($batch) {
        $daysList = [];
        foreach($batch as $slot) {
            array_push($daysList, $slot['weekday']);
        }
        return $daysList;
    }

    public function getBatchSlotTimeDaywise($batch){
        $slotTime = [];
        foreach($batch as $slot) {
            $slotTime[$slot['weekday']] = $slot['slots'][0]['slot_time'];
        }
        return $slotTime;
    }

    public function getDatesToSchedule($order) {
        $batchDaysOfWeek = $this->getBatchDaysOfWeek($order['batch']);
        $scheduleDates = [];
        $prevDate = date('Y-m-d', strtotime($order['start_date']));
        $dayOfWeekMap = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $slotTimes = $this->getBatchSlotTimeDaywise($order['batch']);
        $limit = $order['studio_sessions']['total'];
        for($i=0; $i<$limit; $i++) {
            while(empty($scheduleDates[$i])){
                $dayofweek = $dayOfWeekMap[date('w', strtotime($prevDate))];
                if(in_array($dayofweek, $batchDaysOfWeek)){
                    array_push($scheduleDates, [
                        'schedule_date' => date('d-m-Y', strtotime($prevDate)),
                        'day' => $dayofweek,
                        'schedule_slot' => $slotTimes[$dayofweek]
                    ]);
                }
                $prevDate = date('Y-m-d', strtotime('+1 day', strtotime($prevDate)));
            }
        }
        return $scheduleDates;
    }

    public function getExtendedSessionDate($order) {
        $studioSessions = $order['studio_sessions'];
        $cancelled = $studioSessions['cancelled'];
        $total_cancel_allowed = $studioSessions['total_cancel_allowed'];

        if($cancelled <= $total_cancel_allowed) {

            Booktrial::$withoutAppends = true;
            $lastBooktrial = Booktrial::where('going_status','!=',2)->where('studio_extended_validity_order_id', $order['_id'])->orderBy('schedule_date_time', 'desc')->first();

            Log::info('$lastBooktrial - schedule_date:: ', [$lastBooktrial['schedule_date']]);
            $scheduleDateStart = strtotime('+1 day', strtotime($lastBooktrial['schedule_date']));
            $scheduleDateEnd = strtotime('+1 day', $order['studio_membership_duration']['end_date_extended']->sec);
            $scheduleDates = [];
            $currTime = time();
            if($currTime>$scheduleDateStart){
                $scheduleDateStart = strtotime('+1 day', $currTime);
            }
            if($scheduleDateEnd>$currTime){
                $batchDaysOfWeek = $this->getBatchDaysOfWeek($order['batch']);
                $prevDate = date('Y-m-d', $scheduleDateStart);
                $dayOfWeekMap = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                $slotTimes = $this->getBatchSlotTimeDaywise($order['batch']);
                while(empty($scheduleDates[0])){
                    $dayofweek = $dayOfWeekMap[date('w', strtotime($prevDate))];
                    if(in_array($dayofweek, $batchDaysOfWeek)){
                        array_push($scheduleDates, [
                            'schedule_date' => date('d-m-Y', strtotime($prevDate)),
                            'day' => $dayofweek,
                            'schedule_slot' => $slotTimes[$dayofweek]
                        ]);
                    }
                    $prevDate = date('Y-m-d', strtotime('+1 day', strtotime($prevDate)));
                }
            }
            return $scheduleDates;
        }
        else {
            return null;
        }

    }

    public function scheduleStudioBookings($order_id, $isPaid=false) {

		Log::info('Utilities scheduleStudioBookings:: ', [$order_id]);

		Order::$withoutAppends = true;
		$order = Order::where('_id', $order_id)->first();

		if(!empty($order['studio_extended_validity']) && $order['studio_extended_validity']){
			Log::info('making studio bookings...');

			$scheduleDates = [];

            if($isPaid) {
                $scheduleDates = $this->getExtendedSessionDate($order);
                Log::info('scheduleDates:: ', [$scheduleDates]);
            }
            else {
                $scheduleDates = $this->getDatesToSchedule($order);
            }

            if(!empty($scheduleDates) && count($scheduleDates)>0){

                Log::info('scheduleDates: ', [count($scheduleDates)]);

                $tc=new \TransactionController( new CustomerMailer(), new CustomerSms(), new Sidekiq(), new FinderMailer(), new FinderSms(), $this,new CustomerReward(), new CustomerNotification(), new Fitapi(), new Fitweb());

                $sc=new \SchedulebooktrialsController( new CustomerMailer(), new FinderMailer(), new CustomerSms(), new FinderSms(), new CustomerNotification(), new Fitnessforce(), new Sidekiq(), new OzontelOutboundCall(new Sidekiq()), $this,new CustomerReward(), new Jwtauth());

                $ratecard = Ratecard::active()->where('service_id', $order['service_id'])->where('type', 'workout session')->first();

                foreach($scheduleDates as $booking_date) {
                    Log::info('booking now....');
                    $captureReq = [
                        "booking_for_others" => false,
                        "cashback" => false,
                        "customer_email" => (!empty($order['customer_email']))?$order['customer_email']:null,
                        "customer_name" => (!empty($order['customer_name']))?$order['customer_name']:null,
                        "customer_phone" => (!empty($order['customer_phone']))?$order['customer_phone']:null,
                        "customer_source" => (!empty($order['customer_source']))?$order['customer_source']:null,
                        "wallet" => false,
                        "device_type" => (!empty($order['device_type']))?$order['device_type']:null,
                        "finder_id" => $order['finder_id'],
                        "gender" => $order['gender'],
                        "gcm_reg_id" => (!empty($order['gcm_reg_id']))?$order['gcm_reg_id']:null,
                        "schedule_date" => $booking_date['schedule_date'],
                        "schedule_slot" => $booking_date['schedule_slot'],
                        "pt_applied" => (!empty($order['pt_applied']))?$order['pt_applied']:null,
                        "customer_quantity" => 1,
                        "ratecard_id" => $ratecard['_id'],
                        "reward_ids" => [],
                        "service_id" => $order['service_id'],
                        "type" => "workout-session",
                        "studio_extended_validity_order_id" => $order['_id']
                    ];
                    if($isPaid){
                        $captureReq['studio_extended_session'] = true;
                    }
                    $captureRes = $tc->capture($captureReq);

                    if(!(empty($captureRes['status']) || $captureRes['status'] != 200 || empty($captureRes['data']['orderid']) || empty($captureRes['data']['email']))){
                        $booktrialReq = [
                            "order_id" => $captureRes['data']['orderid'],
                            "status" => "success",
                            "customer_name" => (!empty($order['customer_name']))?$order['customer_name']:null,
                            "customer_email" => (!empty($order['customer_email']))?$order['customer_email']:null,
                            "customer_phone" => (!empty($order['customer_phone']))?$order['customer_phone']:null,
                            "schedule_date" => $booking_date['schedule_date'],
                            "schedule_slot" => $booking_date['schedule_slot'],
                            "finder_id" => $order['finder_id'],
                            "service_name" => $captureRes['data']['service_name'],
                            "service_id" => $order['service_id'],
                            "ratecard_id" => $ratecard['_id'],
                            "type" => "workout-session",
                            "studio_extended_validity_order_id" => $order['_id'],
                            "communications" => [
                                "customer" => [
                                    "mails" => [
                                        'bookTrialReminderBefore3Hour',
                                        'bookTrialReminderBefore12Hour',
                                        'cancelBookTrial',
                                        'cancelBookTrialByVendor'
                                    ],
                                    "sms" => [
                                        'bookTrialReminderBefore3Hour',
                                        'bookTrialReminderBefore12Hour',
                                        'cancelBookTrial',
                                        'cancelBookTrialByVendor'
                                    ],
                                    "notifications" => [
                                        'bookTrialReminderBefore10Min',
                                        'bookTrialReminderBefore3Hour',
                                        'bookTrialReminderBefore12Hour',
                                        'cancelBookTrial',
                                        'cancelBookTrialByVendor'
                                    ]
                                ],
                                "finder" => [
                                    "mails" => [],
                                    "sms" => [],
                                    "notifications" => []
                                ]
                            ]
                        ];

                        if($isPaid){
                            $booktrialReq['studio_extended_session'] = true;
                            if(!empty($booktrialReq['communications']['customer'])) {
                                unset($booktrialReq['communications']['customer']);
                            }
                            $booktrialReq["communications"]["finder"] = [
                                "mails" => [
                                    'bookTrial',
                                    'cancelBookTrial'
                                ],
                                "sms" => [
                                    'bookTrial',
                                    'cancelBookTrial'
                                ]
                            ];
                        }
                        $booktrialRes = $sc->bookTrialPaid($booktrialReq);
                    }
                    Log::info('booking done....');
                    sleep(20);
                }
                Log::info('....All bookings done....');

            }
            else {
                Log::info('Number of cancellable sessions exceeded...');
            }
        }

		

	}
}

