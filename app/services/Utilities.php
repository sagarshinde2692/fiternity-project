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
                    $query->where('customer_email', $customer_email)
                        ->orWhere('customer_phone', $customer_phone);
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

        if($jwt_token != "" && $jwt_token != null && $jwt_token != 'null'){

            $decoded = $this->customerTokenDecode($jwt_token);
            $customer_id = (int)$decoded->customer->_id;
        }
        
        // Validate transaction request........
        $validator = Validator::make($request, Customerwallet::$rules);

        if ($validator->fails()) {
            return Response::json(
                array(
                    'status' => 400,
                    'message' => $this->errorMessage($validator->errors()
                    )),400
            );
        }

        // Check Duplicacy of transaction request........
        $duplicateRequest = Customerwallet::where('order_id', (int) $request['order_id'])
            ->where('type', $request['type'])
            ->first();

        if($duplicateRequest != ''){
            return Response::json(
                array(
                    'status' => 400,
                    'message' => 'Request has been already processed'
                    ),400
            );
        }

        if(isset($_GET['device_type']) && in_array($_GET['device_type'],['ios','android'])){

            $wallet = Customer::where('_id',$customer_id)
                ->first(array('balance'));

            !($wallet && isset($wallet['balance']))
                ? $wallet['balance'] = 0
                : null;

            // Process Action on basis of request........
            ($request['type'] == 'CREDIT' || $request['type'] == 'REFUND'|| $request['type'] == 'CASHBACK')
                ? $request['balance'] = ((int) $wallet['balance'] + abs($request['amount']))
                : null;
            if($request['type'] == 'DEBIT'){
                if($wallet['balance'] < $request['amount']){
                    return Response::json(array('status' => 422,'message' => 'Your wallet balance is low for transaction'),422);
                }
                else{
                    $request['balance'] = ((int) $wallet['balance'] - abs($request['amount']));
                }
            }
            $customerwallet = new Customerwallet();
            $id = Customerwallet::max('_id');
            //echo $id;
            $max_id = (isset($id) && !empty($id)) ? $id : 0;
            $customerwallet->_id = $max_id + 1;
            $customerwallet->customer_id = $customer_id;
            $customerwallet->order_id = (int) $request['order_id'];
            $customerwallet->type = $request['type'];
            $customerwallet->amount = (int) $request['amount'];
            $customerwallet->balance = (int) $request['balance'];
            isset($request['description']) ? $customerwallet->description = $request['description'] : null;
            $customerwallet->save();

            // Update customer wallet balance........
            Customer::where('_id', $customer_id)->update(array('balance' => (int) $request['balance']));

            // Response........
            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'Transaction successful',
                    'balance' => $request['balance']
                ),200

            );

        }elseif($data && isset($data['customer_source']) && in_array($data['customer_source'],['ios','android'])){

            $wallet = Customer::where('_id',$customer_id)
                ->first(array('balance'));

            !($wallet && isset($wallet['balance']))
                ? $wallet['balance'] = 0
                : null;

            // Process Action on basis of request........
            ($request['type'] == 'CREDIT' || $request['type'] == 'REFUND'|| $request['type'] == 'CASHBACK')
                ? $request['balance'] = ((int) $wallet['balance'] + abs($request['amount']))
                : null;
            if($request['type'] == 'DEBIT'){
                if($wallet['balance'] < $request['amount']){
                    return Response::json(array('status' => 422,'message' => 'Your wallet balance is low for transaction'),422);
                }
                else{
                    $request['balance'] = ((int) $wallet['balance'] - abs($request['amount']));
                }
            }
            $customerwallet = new Customerwallet();
            $id = Customerwallet::max('_id');
            //echo $id;
            $max_id = (isset($id) && !empty($id)) ? $id : 0;
            $customerwallet->_id = $max_id + 1;
            $customerwallet->customer_id = $customer_id;
            $customerwallet->order_id = (int) $request['order_id'];
            $customerwallet->type = $request['type'];
            $customerwallet->amount = (int) $request['amount'];
            $customerwallet->balance = (int) $request['balance'];
            isset($request['description']) ? $customerwallet->description = $request['description'] : null;
            $customerwallet->save();

            // Update customer wallet balance........
            Customer::where('_id', $customer_id)->update(array('balance' => (int) $request['balance']));

            // Response........
            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'Transaction successful',
                    'balance' => $request['balance']
                ),200

            );

        }else{

            // Get Customer wallet balance........
            $customer = Customer::find($customer_id);

            !($customer && isset($customer['balance'])) ? $customer['balance'] = 0 : null;

            !($customer && isset($customer['balance_fitcash_plus'])) ? $customer['balance_fitcash_plus'] = 0: null;

            $request['balance'] = (int)$customer['balance'];
            $request['balance_fitcash_plus'] = (int)$customer['balance_fitcash_plus'];

            // Process Action on basis of request........
            if($request['type'] == 'CREDIT' || $request['type'] == 'CASHBACK'){
                $request['balance'] = ((int) $customer['balance'] + abs($request['amount']));
            }

            if($request['type'] == 'REFUND'){

                $request['balance'] = ((int) $customer['balance'] + abs($request['amount']));

                if($data && isset($data['cashback_detail']) && isset($data['cashback_detail']['only_wallet']) && isset($data['cashback_detail']['discount_and_wallet'])){

                    $cashback_detail = $data['cashback_detail'];

                    $request['balance'] = $customer['balance'] + $cashback_detail['only_wallet']['fitcash'];
                    $request['balance_fitcash_plus'] = $customer['balance_fitcash_plus'] + $cashback_detail['only_wallet']['fitcash_plus'];

                    if(isset($data['cashback']) && $data['cashback'] == true){

                        $request['balance'] = $customer['balance'] + $cashback_detail['discount_and_wallet']['fitcash'];
                        $request['balance_fitcash_plus'] = $customer['balance_fitcash_plus'] + $cashback_detail['discount_and_wallet']['fitcash_plus'];
                    }

                }

            }
     
            if($request['type'] == 'DEBIT'){

                if(($customer['balance']+$customer['balance_fitcash_plus']) < $request['amount']){
                    return Response::json(array('status' => 422,'message' => 'Your wallet balance is low for transaction'),422);
                }

                $cashback_detail = $data['cashback_detail'];

                $request['balance'] = $customer['balance'] - $cashback_detail['only_wallet']['fitcash'];
                $request['balance_fitcash_plus'] = $customer['balance_fitcash_plus'] - $cashback_detail['only_wallet']['fitcash_plus'];

                if(isset($data['cashback']) && $data['cashback'] == true){

                    $request['balance'] = $customer['balance'] - $cashback_detail['discount_and_wallet']['fitcash'];
                    $request['balance_fitcash_plus'] = $customer['balance_fitcash_plus'] - $cashback_detail['discount_and_wallet']['fitcash_plus'];
                }
            }

            $customerwallet = new Customerwallet();
            $id = Customerwallet::max('_id');
            //echo $id;
            $max_id = (isset($id) && !empty($id)) ? $id : 0;
            $customerwallet->_id = $max_id + 1;
            $customerwallet->customer_id = $customer_id;
            $customerwallet->order_id = (int) $request['order_id'];
            $customerwallet->type = $request['type'];
            $customerwallet->amount = (int) $request['amount'];
            $customerwallet->balance = (int) $request['balance'];
            $customerwallet->balance_fitcash_plus = (int) $request['balance_fitcash_plus'];
            isset($request['description']) ? $customerwallet->description = $request['description'] : null;
            $customerwallet->save();


            //update customer balance and balance_fitcash_plus
            $customer->balance = (int)$request['balance'];
            $customer->balance_fitcash_plus = (int)$request['balance_fitcash_plus'];
            $customer->update();

            // Response........
            return Response::json(
                array(
                    'status' => 200,
                    'message' => 'Transaction successful',
                    'balance' => $request['balance']
                ),200

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

    public function setRedundant($order){

        $allOrders = \Order::where('status','!=','1')
                        ->whereIn('type',['memberships','healthytiffinmembership'])
                        ->where('service_id',(int)$order->service_id)
                        ->where('finder_id',(int)$order->finder_id)
                        ->where('customer_email',(int)$order->customer_email)
                        ->where('created_at', '>=', new \DateTime( date("d-m-Y 00:00:00", strtotime("-44 days"))))
                        ->where('created_at', '<=', new \DateTime( date("d-m-Y 23:59:59", strtotime("-44 days"))))
                        ->where('paymentLinkEmailCustomerTiggerCount','exists',true)
                        ->where('paymentLinkEmailCustomerTiggerCount','>',0)
                        ->where('redundant_order','exists',false)
                        ->orderBy('_id','desc')
                        ->get();

        if(count($allOrders) > 0){

            foreach ($allOrders as $orderData) {

                $orderData->update(array('redundant_order'=>'1'));
            }
        }

        return true;

    }

}