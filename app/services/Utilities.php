<?PHP namespace App\Services;
use Carbon\Carbon;
use Customer;
use Customerwallet;
use Validator;
use Response;
use Config;
use JWT;
use Finder;
use Request;
use Log;
use App\Services\Sidekiq as Sidekiq;
use App\Services\ShortenUrl as ShortenUrl;
use Device;
use Wallet;
use WalletTransaction;
use App\Sms\CustomerSms as CustomerSms;
use App\Mailers\FinderMailer as FinderMailer;

Class Utilities {

//    protected $myrewards;
//    protected $customerReward;


   public function __construct() {
       
    $this->vendor_token = false;
        
    $vendor_token = Request::header('Authorization-Vendor');

    if($vendor_token){

        $this->vendor_token = true;
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

        Log::info('jwt_token : '.$jwt_token);
            
        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){
            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = $decoded->customer->_id;
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
        Log::info($customer_id);

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }

        $request['customer_id'] = $customer_id;

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

        $jwt_token = $token;
        $jwt_key = Config::get('app.jwt.key');
        $jwt_alg = Config::get('app.jwt.alg');
        $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

        return $decodedToken;
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
        $finder_slug                       =    (isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
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
                        ->where('_id','<',(int)$order->_id)
                        ->where('payment_mode','paymentgateway')
                        ->where('paymentLinkEmailCustomerTiggerCount','exists',false)
                        ->where('created_at', '>=', new \DateTime( date("d-m-Y 00:00:00", strtotime("-44 days"))))
                        ->orderBy('_id','desc')
                        ->get();

        if(count($allOrders) > 0){

            foreach ($allOrders as $orderData) {

                $orderData->redundant_order = "1";
                $orderData->update();

                $array = array('auto_followup_date','followup_status_count','followup_date');

                foreach ($array as $value){

                    if(isset($orderData[$value])){
                        $orderData->unset($value);
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

                    foreach ($array as $value){

                        if(isset($orderData[$value])){
                            $orderData->unset($value);
                        }
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
        if((isset($data["order_success_flag"]) && in_array($data["order_success_flag"],['kiosk','admin'])) || $order->pg_type == "PAYTM" || (isset($order['cod_otp_verified']) && $order['cod_otp_verified'])){
            if($order->pg_type == "PAYTM" && !(isset($data["order_success_flag"]))){
                $hashreverse = getpayTMhash($order);
                if($data["verify_hash"] == $hashreverse['reverse_hash']){
                    $hash_verified = true;
                }else{
                    $hash_verified = false;
                }
            }
            if((isset($data["order_success_flag"]) && in_array($data["order_success_flag"],['kiosk','admin'])) || (isset($order['cod_otp_verified']) && $order['cod_otp_verified'])){
                $hash_verified = true;
            }
        }else{
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
        return $hash_verified;
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

            foreach ($array as $value) {

                if((isset($order[$value]))){
                    try {
                        $queue_id[] = $order[$value];
                        $order->unset($value);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }
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

        foreach ($array as $value) {
            if((isset($booktrial[$value]))){
                try {
                    $queue_id[] = $booktrial[$value];
                    $booktrial->unset($value);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }
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

        foreach ($array as $value) {
            if((isset($capture[$value]))){
                try {
                    $queue_id[] = $capture[$value];
                    $capture->unset($value);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }
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

                $allDeviceCount = 0;

                if(isset($data['customer_id']) && $data['customer_id'] != ''){

                    $allDeviceCount = Device::where('customer_id', (int)$data['customer_id'])->where('type','!=','web')->count();
                }

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
                
                if($allDeviceCount == 0 && isset($data['customer_id']) && $data['customer_id'] != ''){

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
                }

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

        $wallet_limit = 2500;

        if($data && isset($data['type']) && $data['type'] == 'wallet'){
            Log::info("increasing wallet limit for pledge");
            $wallet_limit = 100000;
        
        }

        if($request && isset($request['code']) && in_array($request['code'], ["of001","of@2","of03!","o4f","of005","of@6","of07!","o8f","of009","of@10","of011!","o012f","of0013","of@14","of015!","o016f","of0017","of@18","of019!","o020f"])){
            Log::info("increasing wallet limit for coupon");
            $wallet_limit = 100000;
        
        }

        $customer_id = (int)$request['customer_id'];

        $jwt_token = Request::header('Authorization');

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }


        $request['customer_id'] = $customer_id;

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
                ->orderBy('_id','desc')
                ->first();

            if($duplicateRequest != ''){

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

            /*if(!isset($customer->current_wallet_balance) && $current_wallet_balance >= $wallet_limit){
                return ['status' => 400,'message' => 'Wallet is overflowing Rs '.$wallet_limit];
            }*/

            if($current_wallet_balance >= $wallet_limit){
                return ['status' => 400,'message' => 'Wallet is overflowing Rs '.$wallet_limit];

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

            return ['status' => 200,'message' => 'Success Added Wallet'];

        }

        if($entry == 'debit'){

            $amount = $request['amount'];

            $query =  $this->getWalletQuery($request);

            $allWallets  = $query->OrderBy('_id','asc')->get();

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

                        $walletTransactionDebit[] =  [
                            'wallet_id' => $value->_id,
                            'wallet_transaction_id' => $walletTransaction->_id,
                            'amount' => $walletTransactionData['amount']
                        ];

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


    public function getWalletBalance($customer_id){

        $customer_id = (int) $customer_id;

        $wallet_balance = Wallet::active()->where('customer_id',$customer_id)->where('balance','>',0)->sum('balance');

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

                    $customersms->referralFitcash($sms_data);
                }
            }
        }

        if($customer){
            $customer->old_customer = true;
            $customer->update();
        }

        return "success";

    }

    public function hitURLAfterDelay($url, $delay = 0, $label = 'label', $priority = 0){

        Log::info("Scheduling url:$url");
        Log::info("delay: $delay");

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

        if(in_array($method, ["before3HourSlotBooking", "orderRenewalMissedcall", "sendPaymentLinkAfter3Days", "sendPaymentLinkAfter7Days", "sendPaymentLinkAfter45Days", "purchaseAfter10Days", "purchaseAfter30Days"])){
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

        $query = Wallet::active()->where('customer_id',(int)$request['customer_id'])->where('balance','>',0);

        if(isset($request['finder_id']) && $request['finder_id'] != ""){

            $finder = \Finder::find((int)$request['finder_id']);

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
					Log::info("inside1");
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
						Log::info("inside2");
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
					Log::info("inside3");
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
				Log::info("inside4");
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

     public function isConvinienceFeeApplicable($data){
        Log::info(debug_backtrace()[1]['function']);
        Log::info("Data for isConvinienceFeeApplicable");
        Log::info($data);
        if($this->vendor_token || in_array($data['finder_id'],Config::get('app.vendors_without_convenience_fee')) ){
            Log::info("vendor token hai");
            return false;
        }
        (!isset($data['ratecard_flags']) && isset($data['flags'])) ? $data['ratecard_flags'] = $data['flags'] : null;

        if((isset($data["ratecard_flags"]) && isset($data["ratecard_flags"]["convinience_fee_applicable"]) && $data["ratecard_flags"]["convinience_fee_applicable"]  && ( !isset($data['type']) || isset($data['type']) && in_array($data['type'], ["memberships", "membership"]))) || (isset($data['offer_convinience_fee']) && $data['offer_convinience_fee'])){
            Log::info("returning true");
            return true;
        
        }
        Log::info("returning false");
        
        return false;
    }

    public function trialBookedLocateScreen($data = false){

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

        return $response;
    }

    public function membershipBookedLocateScreen($data){

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
            'title1'=>strtoupper('<b>Onlie    diet</b>'),
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
        
        $customer_id = $data['customer_id'];

        $group = $data['group'];

        $customersms = new CustomerSms();
        Log::info("sendGroupCommunication");
        Log::info($data);
        
        foreach($group['members'] as $member){

            if($member['customer_id'] == $customer_id){
             
                $order = \Order::find($member['order_id']);

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

        if(isset($data['vendor_code'])){

            $fit_code = true;

            if(isset($data['post_trial_status']) && $data['post_trial_status'] != ""){
                $fit_code = false;
            }
        }

        $fit_code = true;

        return $fit_code;

    }

}

