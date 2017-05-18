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

Class Utilities {

//    protected $myrewards;
//    protected $customerReward;
//
//
//    public function __construct(CustomerReward $customerReward) {
//        
//        $this->customerReward = $customerReward;
//        
//    }
    
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

        if(isset($customer->demonetisation)){

            return $this->walletTransactionNew($request);

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
                        ->whereIn('type',['memberships','healthytiffinmembership'])
                        ->where('service_id',(int)$order->service_id)
                        ->where('finder_id',(int)$order->finder_id)
                        ->where('customer_email',$order->customer_email)
                        ->where('_id','!=',(int)$order->_id)
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
                        ->whereIn('type',['memberships','healthytiffinmembership'])
                        ->where('service_id',(int)$order->service_id)
                        ->where('finder_id',(int)$order->finder_id)
                        ->where('customer_email',$order->customer_email)
                        ->where('_id','!=',(int)$order->_id)
                        ->where('created_at', '>=', new \DateTime( date("d-m-Y 00:00:00", strtotime("-44 days"))))
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
        if((isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin") || $order->pg_type == "PAYTM"){
            if($order->pg_type == "PAYTM"){
                $hashreverse = getpayTMhash($order);
                if($data["verify_hash"] == $hashreverse['reverse_hash']){
                    $hash_verified = true;
                }else{
                    $hash_verified = false;
                }
            }
            if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                $hash_verified = true;
            }
        }else{
            // If amount is zero check for wallet amount
            if($data['amount'] == 0){
                if($order->amount == 0 && isset($order->full_payment_wallet) && $order->full_payment_wallet == true){
                    $hash_verified = true;
                }else{
                    $hash_verified = false;
                    // $resp   =   array('status' => 401, 'statustxt' => 'error', 'order' => $order, "message" => "The amount of purchase is invalid");
                    // return Response::json($resp,401);
                }
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
               /* 'customerNotificationSendPaymentLinkAfter3Days',
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
                'customerNotificationNotInterestedAfter75Days',*/
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
           /* 'customerNotificationPostTrialFollowup1After3Days',
            'customerNotificationPostTrialFollowup1After7Days',
            'customerNotificationPostTrialFollowup1After15Days',
            'customerNotificationPostTrialFollowup1After30Days',
            'customerNotificationPostTrialFollowup2After3Days',
            'customerNotificationPostTrialFollowup2After7Days',
            'customerNotificationPostTrialFollowup2After15Days',
            'customerNotificationPostTrialFollowup2After30Days',
            'customerNotificationNotInterestedAfter15Days',
            'customerNotificationNotInterestedAfter45Days',
            'customerNotificationNotInterestedAfter75Days',*/
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
            /*'customerNotificationPostCaptureFollowup2After3Days',
            'customerNotificationPostCaptureFollowup2After7Days',
            'customerNotificationPostCaptureFollowup2After15Days',
            'customerNotificationPostCaptureFollowup2After30Days',*/
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

                $device->customer_id = (isset($data['customer_id']) && $data['customer_id'] != '') ? (int)$data['customer_id'] : $device->customer_id;
                $device->update();

            } else {

                $allDeviceCount = 0;

                if(isset($data['customer_id']) && $data['customer_id'] != ''){

                    $allDeviceCount = Device::where('customer_id', (int)$data['customer_id'])->count();
                }

                $device_id = Device::max('_id') + 1;
                $device = new Device();
                $device->_id = $device_id;
                $device->reg_id = $data['reg_id'];
                $device->customer_id = (isset($data['customer_id']) && $data['customer_id'] != '') ? (int)$data['customer_id'] : '';
                $device->type = $data['type'];
                $device->status = "1";
                $device->save();
                
                if($allDeviceCount == 0 && isset($data['customer_id']) && $data['customer_id'] != ''){

                    $booktrial = \Booktrial::where("customer_id",(int)$data['customer_id'])->where('type','booktrials')->count();

                    if(count($booktrial) > 0){

                        $addWalletData = [
                            "customer_id" => $data["customer_id"],
                            "amount" => 250,
                            "action" => "add_fitcash_plus",
                            "description" => "Added Fitcash Plus Rs 250 on App Download",
                            "validity"=>strtotime("+ 180 days")
                        ];

                        $this->addWallet($addWalletData);
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
            $req['description'] = "Added Fitcash Rs ".$amount;
        }

        if($data['action'] == "add_fitcash_plus"){
            $req['amount_fitcash_plus'] = $amount;
            $req['type'] = "FITCASHPLUS";
            $req['description'] = "Added Fitcash Plus Rs ".$amount;
        }

        if(isset($data['description']) && $data['description'] != ""){
            $req['description'] = $data['description'];
        }

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

    public function walletTransactionNew($request){

        $wallet_limit = 2500;

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

                if($current_wallet_balance < $wallet_limit && ($current_wallet_balance + (int)$request['amount']) > $wallet_limit){
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

                    $data['wallet_id'] = (int)$value['wallet_id'];                 
                    $data['entry'] = $entry;
                    $data['type'] = $type;
                    $data['customer_id'] = $customer_id;
                    $data['amount'] = intval($value_amount);
                    $data['description'] = "Refund";

                    if(isset($request['order_id']) && $request['order_id'] != ""){

                        $data['order_id'] = (int)$request['order_id'];

                        $data['description'] = "Refund for order ".$request['order_id'];
                    }

                    if(isset($request['trial_id']) && $request['trial_id'] != ""){

                        $data['trial_id'] = (int)$request['trial_id'];

                        $data['description'] = "Refund for trial ".$request['trial_id'];
                    }

                    $data['validity'] = 0;

                    if(isset($value['coupon']) && $value['coupon'] != ""){
                        $data['coupon'] = $value['coupon'];
                    }

                    if(isset($request['description'])){
                        $data['description'] = $request['description'];
                    }
                    
                    $walletTransaction = WalletTransaction::create($data);

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

            if($current_wallet_balance < $wallet_limit && ($current_wallet_balance + (int)$request['amount']) > $wallet_limit){
                $request['amount'] = (int)($wallet_limit - $current_wallet_balance);
            }

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

            $wallet->save();

            $data['wallet_id'] = $wallet->_id;
            $data['entry'] = $wallet->entry;
            $data['type'] = $wallet->type;
            $data['customer_id'] = $customer_id;
            $data['amount'] = (int)$request['amount'];

            if(isset($reqiest['order_id']) && $reqiest['order_id'] != ""){

                $data['order_id'] = (int)$reqiest['order_id'];

                $data['description'] = "Added Amount of Rs ".$request['amount']." for Order ".$request['order_id'];

            }

            if(isset($reqiest['trial_id']) && $reqiest['trial_id'] != ""){

                $data['trial_id'] = (int)$reqiest['trial_id'];

                $data['description'] = "Added Amount of Rs ".$request['amount']." for Trial ".$request['trial_id'];
            }
            
            $data['validity'] = $wallet['validity'];

            if(isset($wallet['coupon']) && $wallet['coupon'] != ""){
                $data['coupon'] = $wallet['coupon'];
            }

            $data['description'] = "Added Amount of Rs ".$request['amount'];

            if(isset($request['description'])){
                $data['description'] = $request['description'];
            }

            $walletTransaction = WalletTransaction::create($data);

            $walletTransaction->update(['group'=>$walletTransaction->_id]);

            return ['status' => 200,'message' => 'Success Added Wallet'];

        }

        if($entry == 'debit'){

            $amount = $request['amount'];

            $allWallets = Wallet::active()->where('customer_id',(int)$customer_id)->where('balance','>',0)->OrderBy('_id','asc')->get();

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

                        $data['amount'] = (int)$value['balance'];

                        if($value['balance'] >= $amount_balance){
                            $data['amount'] = (int)$amount_balance;
                        }

                        $amount_used = intval($amount_used + $data['amount']);
                        $amount_balance = intval($amount_balance - $data['amount']);

                        $data['wallet_id'] = $value->_id;
                        $data['entry'] = $entry;
                        $data['type'] = $request['type'];
                        $data['customer_id'] = $customer_id;

                        if(isset($request['order_id']) && $request['order_id'] != ""){
                            $data['order_id'] = (int)$request['order_id'];

                            $data['description'] = "Paid for Order ID: ".$request['order_id'];
                        }

                        if(isset($request['trial_id']) && $request['trial_id'] != ""){
                            $data['trial_id'] = (int)$request['trial_id'];

                            $data['description'] = "Paid for Trial ID: ".$request['trial_id'];
                        }

                        if(isset($value['validity']) && $value['validity'] != ""){
                            $data['validity'] = $value['validity'];
                        }

                        if(isset($value['coupon']) && $value['coupon'] != ""){
                            $data['coupon'] = $value['coupon'];
                        }

                        $walletTransaction = WalletTransaction::create($data);

                        if($group == ""){
                            $group = $walletTransaction->_id;
                        }

                        $walletTransaction->update(['group'=>$group]);

                        $value->used = intval($value->used + $data['amount']);
                        $value->balance = intval($value->balance - $data['amount']);
                        $value->update();

                        $walletTransactionDebit[] =  [
                            'wallet_id' => $value->_id,
                            'wallet_transaction_id' => $walletTransaction->_id,
                            'amount' => $data['amount']
                        ];

                        if($amount_used == $amount){
                            break;
                        }
                        
                    }

                    if(isset($customer->demonetisation) && isset($customer->current_wallet_balance) && $customer->current_wallet_balance > $wallet_limit){
                        $customer->update(['current_wallet_balance'=> $current_wallet_balance - $amount,'current_wallet_balance_transaction_date'=>time()]);
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

        $order = $order->toArray();

        if(!isset($customer->demonetisation) && isset($order['wallet_amount']) && $order['amount_finder'] >= 500){

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

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $order['cashback_detail']['current_wallet_balance'];
                    $request['entry'] = "credit";
                    $request['type'] = "CREDIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = "Demonetisation Added Fitcash Plus Rs for Order ID: ".$order['_id'];

                    $this->walletTransactionNew($request);

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $order['cashback_detail']['current_wallet_balance'];
                    $request['entry'] = "debit";
                    $request['type'] = "DEBIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = "Paid for Order ID: ".$order['_id'];

                    $this->walletTransactionNew($request);

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $current_wallet_balance;
                    $request['entry'] = "credit";
                    $request['type'] = "CREDIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = "Demonetisation Added Fitcash Plus Rs for Order ID: ".$order['_id'];

                    $this->walletTransactionNew($request);

                }else{

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $order['cashback_detail']['current_wallet_balance'];
                    $request['entry'] = "credit";
                    $request['type'] = "CREDIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = "Demonetisation Added Fitcash Plus Rs for Order ID: ".$order['_id'];

                    $this->walletTransactionNew($request);

                    $request['customer_id'] = $customer_id;
                    $request['amount'] = $order['cashback_detail']['current_wallet_balance'];
                    $request['entry'] = "debit";
                    $request['type'] = "DEBIT";
                    $request['order_id'] = $order['_id'];
                    $request['description'] = "Paid for Order ID: ".$order['_id'];

                    $this->walletTransactionNew($request);


                }

                if($current_wallet_balance > $wallet_limit){
                    $customer->update(['current_wallet_balance'=>$current_wallet_balance]);
                }

                $customer->update(['demonetisation'=>time()]);

            }
        }

        return "success";


    }


}