<?PHP
/**
 * ControllerName : SchedulebooktrialsController.
 * Maintains a list of functions used for SchedulebooktrialsController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\CustomerMailer as CustomerMailer;
use App\Mailers\FinderMailer as FinderMailer;
use App\Sms\CustomerSms as CustomerSms;
use App\Sms\FinderSms as FinderSms;
use App\Notification\CustomerNotification as CustomerNotification;
use App\Services\Fitnessforce as Fitnessforce;
use Carbon\Carbon;
use App\Services\Sidekiq as Sidekiq;
use App\Services\OzontelOutboundCall as OzontelOutboundCall;
use App\Services\ShortenUrl as ShortenUrl;
use App\Services\Utilities as Utilities;
use App\Services\CustomerReward as CustomerReward;
use App\Services\CustomerInfo as CustomerInfo;



class SchedulebooktrialsController extends \BaseController {

    protected $customermailer;
    protected $findermailer;
    protected $customersms;
    protected $findersms;
    protected $customernotification;
    protected $fitnessforce;
    protected $worker;
    protected $sidekiq;
    protected $ozontelOutboundCall;
    protected $utilities;
    protected $customerreward;


    public function __construct(
        CustomerMailer $customermailer,
        FinderMailer $findermailer,
        CustomerSms $customersms,
        FinderSms $findersms,
        CustomerNotification $customernotification,
        Fitnessforce $fitnessforce,
        Sidekiq $sidekiq,
        OzontelOutboundCall $ozontelOutboundCall,
        Utilities $utilities,
        CustomerReward $customerreward
    ) {
        //parent::__construct();
        date_default_timezone_set("Asia/Kolkata");
        $this->customermailer           =   $customermailer;
        $this->findermailer             =   $findermailer;
        $this->customersms              =   $customersms;
        $this->findersms                =   $findersms;
        $this->customernotification     =   $customernotification;
        $this->fitnessforce             =   $fitnessforce;
        $this->sidekiq              =   $sidekiq;
        $this->ozontelOutboundCall  =   $ozontelOutboundCall;
        $this->utilities            =   $utilities;
        $this->customerreward            =   $customerreward;

    }

    /**
     * Display the ScheduleBookTrial.
     *
     * @param  int  $finderid
     * @param  date  $date(dd-mm-yyyy)
     * @return Response
     */

    public function getScheduleBookTrial($finderid,$date = null){

        //$dobj = new DateTime;print_r($dobj);
        $currentDateTime        =   \Carbon\Carbon::now();
        $finderid               =   (int) $finderid;
        $date                   =   ($date == null) ? Carbon::now() : $date;
        $timestamp              =   strtotime($date);
        $weekday                =   strtolower(date( "l", $timestamp));

        // echo "$date  --- $timestamp -- $weekday";exit;
        //finder schedule trials
        $items = Schedulebooktrial::where('finder_id', '=', $finderid)->where('weekday', '=', $weekday)->get()->toArray();
        if(!$items){
            return $this->responseNotFound('Schedule does not exist');
        }

        $scheduletrials = array();
        foreach ($items as $item) {
            $price = (isset($item['price'])) ? $item['price'] : 0;
            $trial = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'price' => $price, 'weekday' =>  $item['weekday']);
            $slots = array();
            foreach ($item['slots'] as $slot) {
                $booktrialslotcnt = Booktrial::where('finder_id', '=', $finderid)
                    ->where('service_name', '=', $item['name'])
                    ->where('schedule_date', '=', new DateTime($date) )
                    ->where('schedule_slot', '=', $slot['slot_time'])
                    ->count();
                // var_dump($booktrialslotcnt);

                $slot_status        =   ($slot['limit'] > $booktrialslotcnt) ? "available" : "full";
                array_set($slot, 'booked', $booktrialslotcnt);
                array_set($slot, 'status', $slot_status);

                $scheduleDateTime               =   Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']))->subMinutes(1);
                // $oneHourDiffInMin            =   $currentDateTime->diffInMinutes($delayReminderTimeBefore1Hour, false);
                $slot_datetime_pass_status      =   ($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
                array_set($slot, 'passed', $slot_datetime_pass_status);

                //echo "<br>finderid : $finderid  --- schedule_date : $date servicename : $item[name] -- slot_time : $slot[slot_time] --  booktrialslotcnt : $booktrialslotcnt";
                array_push($slots, $slot);
            }
            $trial['slots'] = $slots;
            array_push($scheduletrials, $trial);
        }
        return $scheduletrials;
    }


    /**
     * Display the TrialSchedule.
     *
     * @param  int  $finderid
     * @param  date  $date(dd-mm-yyyy)
     * @return Response
     */

    public function getTrialSchedule($finderid,$date = null){

        // $dobj = new DateTime;print_r($dobj);

        $currentDateTime        =   \Carbon\Carbon::now();
        $finderid               =   (int) $finderid;
        $date                   =   ($date == null) ? Carbon::now() : $date;
        $timestamp              =   strtotime($date);
        $weekday                =   strtolower(date( "l", $timestamp));

        $items = Service::active()->where('finder_id', '=', $finderid)->get(array('_id','name','finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();
        if(!$items){
            return $this->responseNotFound('TrialSchedule does not exist');
        }

        $scheduleservices = array();
        foreach ($items as $k => $item) {
            $weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
                if($value['weekday'] == $weekday){
                    return $value;
                }
            }));

            //slots exists
            if(count($weekdayslots['slots']) > 0){
                // echo "<br> count -- ".count($weekdayslots['slots']);
                $service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday);

                $slots = array();
                foreach ($weekdayslots['slots'] as $slot) {
                    $totalbookcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
                    $goingcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
                    $cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
                    $slot_status        =   ($slot['limit'] > $goingcnt) ? "available" : "full";
                    array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
                    array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
                    array_set($slot, 'totalbookcnt', $totalbookcnt);
                    array_set($slot, 'goingcnt', $goingcnt);
                    array_set($slot, 'cancelcnt', $cancelcnt);
                    array_set($slot, 'status', $slot_status);
                    $scheduleDateTime               =   Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']));
                    $slot_datetime_pass_status      =   ($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
                    array_set($slot, 'passed', $slot_datetime_pass_status);
                    array_push($slots, $slot);
                }

                $service['slots'] = $slots;
                $service['trialschedules']['slots'] = $slots;
                array_push($scheduleservices, $service);
            }
        }
        return $scheduleservices;
    }


    public function getTrialScheduleIfDontSoltsAlso($finderid,$date = null){

        // $dobj = new DateTime;print_r($dobj);

        $currentDateTime        =	\Carbon\Carbon::now();
        $finderid 		       = 	(int) $finderid;
        $date 			       =  	($date == null) ? Carbon::now() : $date;
        $timestamp 		       = 	strtotime($date);
        $weekday 		       = 	strtolower(date( "l", $timestamp));

        $items                  =   Service::where('finder_id', '=', $finderid)->where('status','1')->get(array('_id','three_day_trial','vip_trial','name','finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();

        if(!$items){
            return $this->responseNotFound('TrialSchedule does not exist');
        }

        $scheduleservices = array();
        foreach ($items as $k => $item) {
            $weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
                if($value['weekday'] == $weekday){
                    return $value;
                }
            }));


            // echo "<br> count -- ".count($weekdayslots['slots']);
            $item['three_day_trial'] = isset($item['three_day_trial']) ? $item['three_day_trial'] : "";
            $item['vip_trial'] = isset($item['vip_trial']) ? $item['vip_trial'] : "";
            $service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday, 'three_day_trial' => $item['three_day_trial'],'vip_trial' => $item['vip_trial']);

            $slots = array();
            //slots exists
            if(count($weekdayslots['slots']) > 0){
                foreach ($weekdayslots['slots'] as $slot) {
                    $totalbookcnt        = 	Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
                    $goingcnt 	       = 	Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
                    $cancelcnt 	       = 	Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
                    $slot_status        = 	($slot['limit'] > $goingcnt) ? "available" : "full";
                    array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
                    array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
                    array_set($slot, 'totalbookcnt', $totalbookcnt);
                    array_set($slot, 'goingcnt', $goingcnt);
                    array_set($slot, 'cancelcnt', $cancelcnt);
                    array_set($slot, 'status', $slot_status);

                    $vip_trial_amount = 0;

                    if($item['vip_trial'] == "1"){

                        $price = (int) $slot['price'];

                        if($price >= 500){
                            $vip_trial_amount = 500;
                        }

                        if($price < 500){
                            $vip_trial_amount = $price+150;
                        }

                        if($price == 0){
                            $vip_trial_amount = 199;
                        }

                    }

                    array_set($slot, 'vip_trial_amount', $vip_trial_amount);

                    $scheduleDateTime 		       =	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']));
                    $slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
                    array_set($slot, 'passed', $slot_datetime_pass_status);
                    array_push($slots, $slot);
                }
            }

            $service['slots'] 			       =	$slots;
            $service['trialschedules']['slots'] =	$slots;
            array_push($scheduleservices, $service);

        }

        foreach ($scheduleservices as $key => $value) {

            if(empty($value['slots'])){
                unset($scheduleservices[$key]);
                array_push($scheduleservices,$value);
            }
        }

        return array_values($scheduleservices);
    }


    /**
     * Display the WorkoutSession Schedule.
     *
     * @param  int  $finderid
     * @param  date  $date(dd-mm-yyyy)
     * @return Response
     */

    public function getWorkoutSessionSchedule($finderid,$date = null){
        // $dobj = new DateTime;print_r($dobj);

        $currentDateTime        =	\Carbon\Carbon::now();
        $finderid 		       = 	(int) $finderid;
        $date 			       =  	($date == null) ? Carbon::now() : $date;
        $timestamp 		       = 	strtotime($date);
        $weekday 		       = 	strtolower(date( "l", $timestamp));

        $items = Service::where('finder_id', '=', $finderid)->get(array('_id','name','finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();
        if(!$items){
            return $this->responseNotFound('WorkoutSession Schedule does not exist');
        }

        $scheduleservices = array();
        foreach ($items as $k => $item) {
            $weekdayslots = head(array_where($item['workoutsessionschedules'], function($key, $value) use ($weekday){
                if($value['weekday'] == $weekday){
                    return $value;
                }
            }));

            //slots exists
            if(count($weekdayslots['slots']) > 0){
                // echo "<br> count -- ".count($weekdayslots['slots']);
                $service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday);
                $slots = array();

                foreach ($weekdayslots['slots'] as $slot) {
                    $totalbookcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
                    $goingcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
                    $cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
                    $slot_status        = 	($slot['limit'] > $goingcnt) ? "available" : "full";

                    array_set($slot, 'totalbookcnt', $totalbookcnt);
                    array_set($slot, 'goingcnt', $goingcnt);
                    array_set($slot, 'cancelcnt', $cancelcnt);
                    array_set($slot, 'status', $slot_status);

                    $scheduleDateTime 		       =	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']));
                    $slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
                    array_set($slot, 'passed', $slot_datetime_pass_status);
                    array_push($slots, $slot);
                }

                $service['slots'] = $slots;
                $service['workoutsessionschedules']['slots'] = $slots;
                array_push($scheduleservices, $service);
            }
        }
        return $scheduleservices;
    }



    /**
     * Display the ServiceSchedule.
     *
     * @param  int  $serviceid
     * @param  date $date(dd-mm-yyyy)
     * @param  int  $noofdays
     * @return Response
     */

    public function getServiceSchedule($serviceid, $date = null, $noofdays = null, $schedulesof = null){


        // $dobj = new DateTime;print_r($dobj);exit;
        $currentDateTime 	=	\Carbon\Carbon::now();
        $item 		       =	Service::where('_id', (int) $serviceid)->first(array('name', 'finder_id', 'trialschedules', 'workoutsessionschedules'))->toArray();
        if(!$item){
            return $this->responseNotFound('Service Schedule does not exist');
        }

        $finderid 	       = 	intval($item['finder_id']);
        $noofdays 	       =  	($noofdays == null) ? 1 : $noofdays;
        $schedulesof        =  	($schedulesof == null) ? 'trialschedules' : $schedulesof;
        $serviceschedules 	= 	array();

        for ($j = 0; $j < $noofdays; $j++) {

            $dt 	       =	Carbon::createFromFormat('Y-m-d', date("Y-m-d", strtotime($date)) )->addDays(intval($j))->format('d-m-Y');
            $timestamp        = 	strtotime($dt);
            $weekday        = 	strtolower(date( "l", $timestamp));
            // echo "$dt -- $weekday <br>";

            if($schedulesof == 'trialschedules'){

                $weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
                    if($value['weekday'] == $weekday){
                        return $value;
                    }
                }));

            }else{

                $weekdayslots = head(array_where($item['workoutsessionschedules'], function($key, $value) use ($weekday){
                    if($value['weekday'] == $weekday){
                        return $value;
                    }
                }));
            }

            // print_pretty($weekdayslots);

            // sslots exists
            $service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'date' => $dt, 'weekday' => $weekday, 'month' => date( "M", $timestamp), 'day' => date( "d", $timestamp));
            $slots = array();
            if(count($weekdayslots['slots']) > 0){
                foreach ($weekdayslots['slots'] as $slot) {
                    $totalbookcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->count();
                    $goingcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
                    // $cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
                    $slot_status        = 	($slot['limit'] > $goingcnt) ? "available" : "full";

                    array_set($slot, 'totalbookcnt', $totalbookcnt);
                    array_set($slot, 'goingcnt', $goingcnt);
                    // array_set($slot, 'cancelcnt', $cancelcnt);
                    array_set($slot, 'status', $slot_status);

                    $scheduleDateTime 		       =	Carbon::createFromFormat('d-m-Y g:i A', date("d-m-Y g:i A", strtotime(strtoupper($dt." ".$slot['start_time']))) );
                    // $scheduleDateTime 		       =	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($dt." ".$slot['start_time']));
                    // $scheduleDateTime 		       =	Carbon::createFromFormat('d-m-Y g:i A', strtoupper($dt." ".$slot['start_time']));
                    $slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;
                    array_set($slot, 'passed', $slot_datetime_pass_status);
                    array_push($slots, $slot);
                }
            }

            $service['slots'] = $slots;
            array_push($serviceschedules, $service);

        }

        return $serviceschedules;
    }


    public function getBookTrial($finderid,$date = null){
        $finderid 	= 	(int) $finderid;
        $items        = 	Booktrial::where('finder_id', '=', $finderid)
            ->where('service_name', '=', 'gyms' )
            ->where('schedule_date', '=', new DateTime($date) )
            ->get(array('customer_name','service_name','finder_id','schedule_date','schedule_slot'));
        return $items;
    }


    /**
     * Update Book A Trial.
     *
     */

    public function updateBookTrial() {

        $data = Input::json()->all();

        if(empty($data['booktrial_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - booktrial_id");
            return  Response::json($resp, 400);
        }

        $booktrial_id = intval(Input::json()->get('booktrial_id'));
        $customer_reminder_need_status = Input::json()->get('customer_reminder_need_status');
        $booktrialdata = array(
            'customer_reminder_need_status'        =>		$customer_reminder_need_status
        );
        $booktiral 		       = 	Booktrial::findOrFail($booktrial_id);
        $booktiral_response 	=	$booktiral->update($booktrialdata);

        $resp 	= 	array('status' => 200,'message' => "Book Trial Update Sucessfully");
        return Response::json($resp,200);

    }

    /**
     * Booked Manual Book A Trial.
     *
     */

    public function manualBookTrial() {


        $data = Input::json()->all();

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

        if(empty($data['customer_phone'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
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

        if(empty($data['city_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - city_id");
            return  Response::json($resp, 400);
        }

        // return $data	= Input::json()->all();
        $booktrialid 		       =	Booktrial::max('_id') + 1;
        $finder_id 			       = 	(int) Input::json()->get('finder_id');
        $city_id 			       =	(int) Input::json()->get('city_id');
        $finder_name 		       =	Input::json()->get('finder_name');
        $finder				       =	Finder::active()->where('_id','=',intval($finder_id))->first();
        $customer_id		       = 	$this->autoRegisterCustomer($data);
        $customer_name		       = 	$data['customer_name'];
        $customer_email		       = 	$data['customer_email'];
        $customer_phone		       = 	$data['customer_phone'];

        $preferred_location	       = 	(isset($data['preferred_location']) && $data['preferred_location'] != '') ? $data['preferred_location'] : "";
        $preferred_service	       = 	(isset($data['preferred_service']) && $data['preferred_service'] != '') ? $data['preferred_service'] : "";
        $preferred_day		       = 	(isset($data['preferred_day']) && $data['preferred_day'] != '') ? $data['preferred_day'] : "";
        $preferred_time		       = 	(isset($data['preferred_time']) && $data['preferred_time'] != '') ? $data['preferred_time'] : "";
        $device_id			       = 	(isset($data['device_id']) && $data['device_id'] != '') ? $data['device_id'] : "";
        $premium_session 	       =	(isset($data['premium_session'])) ? (boolean) $data['premium_session'] : false;
        $additional_info	       = 	(isset($data['additional_info']) && $data['additional_info'] != '') ? $data['additional_info'] : "";
        $otp	 			       =	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";
        $customer_address	        =	(isset($data['customer_address']) && $data['customer_address'] != '') ? implode(',', array_values($data['customer_address'])) : "";
        $customer_note	 	       =	(isset($data['customer_note']) && $data['customer_note'] != '') ? $data['customer_note'] : "";
        $note_to_trainer              =   (isset($data['note_to_trainer']) && $data['note_to_trainer'] != '') ? $data['note_to_trainer'] : "";

        $device_type				       = 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
        $gcm_reg_id					       = 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

        $social_referrer			       = 	(isset($data['social_referrer']) && $data['social_referrer'] != '') ? $data['social_referrer'] : "";
        $referrer_object			       = 	(isset($data['referrer_object']) && $data['referrer_object'] != '') ? $data['referrer_object'] : "";
        $transacted_after			       = 	(isset($data['transacted_after']) && $data['transacted_after'] != '') ? $data['transacted_after'] : "";

        if($device_type != '' && $gcm_reg_id != ''){

            $reg_data = array();

            $reg_data['customer_id'] = $customer_id;
            $reg_data['reg_id'] = $gcm_reg_id;
            $reg_data['type'] = $device_type;

            $this->addRegId($reg_data);
        }



        $booktrialdata = array(
            'premium_session'        =>		$premium_session,

            'finder_id' 	       =>		$finder_id,
            'city_id'		       =>		$city_id,
            'finder_name' 	       =>		$finder_name,
            'finder_category_id' 	=>		intval($finder->category_id),

            'customer_id' 	       =>		$customer_id,
            'customer_name'        =>		$customer_name,
            'customer_email'        =>		$customer_email,
            'customer_phone'        =>		$customer_phone,

            'preferred_location'	=>		Input::json()->get('preferred_location'),
            'preferred_service'       =>		Input::json()->get('preferred_service'),
            'preferred_day'	       =>		Input::json()->get('preferred_day'),
            'preferred_time'       =>		Input::json()->get('preferred_time'),
            'device_id'		       =>		$device_id,
            'going_status'	       =>		0,
            'going_status_txt'       =>		'not fixed',
            'booktrial_type'       =>		'manual',
            'booktrial_actions'       =>		'call to set up trial',
            'source'		       =>		'website',
            'origin'		       =>		'manual',
            'additional_info'       =>		$additional_info,
            'otp'			       =>		$otp,
            'source_flag'	       => 		'customer',
            'final_lead_stage'	       =>		'booking_stage',
            'final_lead_status'	       =>		'slot_not_fixed',
            'customer_address'       => 		$customer_address,
            'customer_note'       =>		$customer_note,

            'social_referrer'		       =>		$social_referrer,
            'transacted_after'		       =>		$transacted_after,
            'referrer_object'		       =>		$referrer_object,

            'device_type'		       =>		$device_type,
            'gcm_reg_id'		       =>		$gcm_reg_id,
            'note_to_trainer'                =>      $note_to_trainer,
        );


        if(isset($data['customer_address']) && $data['customer_address'] != ''){
            $booktrialdata['customer_address_array'] = $data['customer_address'];
        }


        // return $booktrialdata;
        $booktrial = new Booktrial($booktrialdata);
        $booktrial->_id = $booktrialid;
        $trialbooked = $booktrial->save();

        if($trialbooked){
            $sndInstantEmailCustomer       = 	$this->customermailer->manualBookTrial($booktrialdata);
            $sndInstantSmsCustomer	       =	$this->customersms->manualBookTrial($booktrialdata);
        }

        $resp 	= 	array('status' => 200,'booktrial'=> $booktrial, 'message' => "Book a Trial");
        return Response::json($resp,200);
    }


    public function manual2ndBookTrial() {

        $data = Input::json()->all();

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

        if(empty($data['customer_phone'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
            return  Response::json($resp, 400);
        }

        if(empty($data['finder_ids'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_ids");
            return  Response::json($resp, 400);
        }

        if(empty($data['finder_names'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_names");
            return  Response::json($resp, 400);
        }

        if(empty($data['city_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - city_id");
            return  Response::json($resp, 400);
        }

        // if(empty($data['preferred_location'])){
        // $resp 	= 	array('status' => 400,'message' => "Data Missing - preferred_location");
        // return  Response::json($resp, 400);
        // }

        // if(empty($data['preferred_day'])){
        //    $resp 	= 	array('status' => 400,'message' => "Data Missing - preferred_day");
        // return  Response::json($resp, 400);
        // }

        // if(empty($data['preferred_time'])){
        // $resp 	= 	array('status' => 400,'message' => "Data Missing - preferred_time");
        // return  Response::json($resp, 400);
        // }


        // return $data	= Input::json()->all();
        $finder_ids 		       = 	Input::json()->get('finder_ids');
        $finder_names 		       =	Input::json()->get('finder_names');
        $city_id 			       =	(int) Input::json()->get('city_id');

        $customer_id		       = 	$this->autoRegisterCustomer($data);
        $customer_name		       = 	(Input::has('customer_name') && Input::json()->get('customer_name') != '') ? Input::json()->get('customer_name') : "";
        $customer_email		       = 	(Input::has('customer_email') && Input::json()->get('customer_email') != '') ? Input::json()->get('customer_email') : "";
        $customer_phone		       = 	(Input::has('customer_phone') && Input::json()->get('customer_phone') != '') ? Input::json()->get('customer_phone') : "";

        $preferred_location	       = 	(Input::has('preferred_location') && Input::json()->get('preferred_location') != '') ? Input::json()->get('preferred_location') : "";
        $preferred_service	       = 	(Input::has('preferred_service') && Input::json()->get('preferred_service') != '') ? Input::json()->get('preferred_service') : "";
        $preferred_day		       = 	(Input::has('preferred_day') && Input::json()->get('preferred_day') != '') ? Input::json()->get('preferred_day') : "";
        $preferred_time		       = 	(Input::has('preferred_time') && Input::json()->get('preferred_time') != '') ? Input::json()->get('preferred_time') : "";
        $device_id			       = 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
        $premium_session 	       =	(Input::json()->get('premium_session')) ? (boolean) Input::json()->get('premium_session') : false;
        $additional_info	       = 	(Input::has('additional_info') && Input::json()->get('additional_info') != '') ? Input::json()->get('additional_info') : "";


        $booktrialdata = array(
            'premium_session'        =>		$premium_session,
            'finder_ids' 	       =>		implode(", ",$finder_ids),
            'city_id'		       =>		$city_id,
            'finder_names' 	       =>		implode(", ",$finder_names),

            'customer_id' 	       =>		$customer_id,
            'customer_name'        =>		$customer_name,
            'customer_email'        =>		$customer_email,
            'customer_phone'        =>		$customer_phone,

            'preferred_location'	=>		Input::json()->get('preferred_location'),
            'preferred_service'       =>		Input::json()->get('preferred_service'),
            'preferred_day'	       =>		Input::json()->get('preferred_day'),
            'preferred_time'       =>		Input::json()->get('preferred_time'),
            'device_id'		       =>		$device_id,
            'going_status'	       =>		0,
            'going_status_txt'       =>		'not fixed',
            'booktrial_type'       =>		'2ndmanual',
            'booktrial_actions'       =>		'call to set up trial',
            'source'		       =>		'website',
            'additional_info'       =>		$additional_info

        );

        foreach ($finder_ids as $key => $finder_id) {

            $insertdata       = 	array_except($booktrialdata, array('finder_ids','finder_names')); ;
            array_set($insertdata, 'finder_id', intval($finder_id));
            array_set($insertdata, 'finder_name', $finder_names[$key]);
            // return $insertdata;

            $booktrialid	=	Booktrial::max('_id') + 1;
            $booktrial        = new Booktrial($insertdata);
            $booktrial->_id = $booktrialid;
            $trialbooked = $booktrial->save();
        }

        $sndInstantEmailCustomer	= 	$this->customermailer->manual2ndBookTrial($booktrialdata);
        $sndInstantSmsCustomer       =	$this->customersms->manual2ndBookTrial($booktrialdata);
        $resp 				       = 	array('status' => 200,'message' => "Second Book a Trial");
        return Response::json($resp,200);

    }

    // public function bookTrialFintnessForce($booktrial,$finder){


    // 	if($finder){
    // 		$data = [];
    // 		$data['authenticationkey'] = "F862975730294C0F82E24DD224A26890";
    // 		$data['trialowner'] = "AUTO";
    // 		$data['name'] = $booktrial->customer_name;
    // 		$data['mobileno'] = $booktrial->customer_phone;
    // 		$data['emailaddress'] = $booktrial->customer_email;
    // 		$data['startdate'] = date('d-M-Y',strtotime($booktrial->schedule_date_time));
    // 		$data['enddate'] = date('d-M-Y',strtotime($booktrial->schedule_date_time));
    // 		$data['starttime'] = $booktrial->schedule_slot_start_time;
    // 		$data['endtime'] = $booktrial->schedule_slot_end_time;

    // 		return $this->fitnessforce->createAppointment($data);
    // 	}
    // 	return false;
    // }

    public function updateAppointmentStatus(){

        $date = date("d-m-Y");
        $booktrail = Booktrial::with('finder')->where('schedule_date', '=', new DateTime($date))->get();
        $response = [];

        foreach ($booktrail as $key => $value) {
            $fitness_force = $this->fitnessforce->getAppointmentStatus($value);

            if($fitness_force['status'] == 200){

                $queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
                $queueddata['fitness_force_appointment']['status'] = 200;
                $queueddata['fitness_force_appointment'] = $fitness_force['data'];

                try{
                    $value->update($queueddata);
                    $response[$key] = [  	'status'=>200,
                        'message'=>'Sucessfull',
                        'id'=>$value->_id
                    ];
                }catch(Exception $e){
                    $response[$key] = [  	'status'=>400,
                        'message'=>'Update error',
                        'id'=>$value->_id
                    ];
                }
            }else{
                $response[$key] = $fitness_force ;
                $response[$key]['id'] = $value->_id;
            }

        }

        return Response::json($response,200);
    }


    public function autoRegisterCustomer($data){

        $customer       =   Customer::active()->where('email', $data['customer_email'])->first();

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

            return (int)$inserted_id;

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

            return (int)$customer->_id;
        }

    }


    public function bookTrialHealthyTiffinFree(){

        $data	       =	array_except(Input::json()->all(), array('preferred_starting_date'));
        $postdata       =	Input::json()->all();

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
        }

        if(empty($data['finder_id'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - finder_id");
            return Response::json($resp,404);
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
        }

        if(empty($data['service_name'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - service_name");
            return Response::json($resp,404);
        }

        if(empty($data['type'])){
            $resp 	= 	array('status' => 404,'message' => "Data Missing Order Type - type");
            return Response::json($resp,404);
        }

        if (!in_array($data['type'], ['healthytiffintrail'])) {
            $resp 	= 	array('status' => 404,'message' => "Invalid Order Type");
            return Response::json($resp,404);
        }

        if( empty($data['service_duration']) ){
            $resp 	= 	array('status' => 404,'message' => "Data Missing - service_duration");
            return Response::json($resp,404);
        }

        $orderid 	       =	Order::max('_id') + 1;
        $customer_id        =	(Input::json()->get('customer_id')) ? Input::json()->get('customer_id') : $this->autoRegisterCustomer($data);
        array_set($data, 'customer_id', intval($customer_id));
        
        if(trim(Input::json()->get('finder_id')) != '' ){

            $finder 	                        = 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval(Input::json()->get('finder_id')))->first()->toArray();

            $finder_city				       =	(isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
            $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
            $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
            $finder_vcc_email			       = 	(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != '') ? $finder['finder_vcc_email'] : "";
            $finder_vcc_mobile			       = 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
            $finder_poc_for_customer_name       = 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
            $finder_poc_for_customer_no	       = 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
            $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;
            $share_customer_no			       = 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;
            $finder_lon					       = 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $finder_lat					       = 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_category_id			       = 	(isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";
            $finder_slug				       = 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";

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

        $code       =	random_numbers(5);
        array_set($data, 'code', $code);
        if(isset($postdata['preferred_starting_date']) && $postdata['preferred_starting_date']  != '') {

            if(trim(Input::json()->get('preferred_starting_date')) != '-'){
                $date_arr = explode('-', Input::json()->get('preferred_starting_date'));
                $preferred_starting_date	       =	date('Y-m-d 00:00:00', strtotime( $date_arr[2]."-".$date_arr[1]."-".$date_arr[0]));
                array_set($data, 'start_date', $preferred_starting_date);
                array_set($data, 'preferred_starting_date', $preferred_starting_date);
            }
        }

//        return $data;

        $order 		       = 	new Order($data);
        $order->_id        = 	$orderid;
        $orderstatus          = 	$order->save();
        
        if($orderstatus){
            //Send Instant (Email) To Customer & Finder
            $sndInstantEmailCustomer        =   $this->customermailer->healthyTiffinTrial($order->toArray());
            $sndInstantSmsCustomer	       =	$this->customersms->healthyTiffinTrial($order->toArray());
            $sndInstantEmailFinder	       = 	$this->findermailer->healthyTiffinTrial($order->toArray());
            $sndInstantSmsFinder	       =	$this->findersms->healthyTiffinTrial($order->toArray());

        }

        $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
        return Response::json($resp);
        
    }
    

    public function bookTrialHealthyTiffinPaid(){

        $data = Input::json()->all();
        Log::info('bookTrialHealthyTiffinPaid',$data);

        if(empty($data['order_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - order_id");
            return  Response::json($resp, 400);
        }

        if(empty($data['status'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - status");
            return  Response::json($resp, 400);
        }

        $orderid 	=	(int) Input::json()->get('order_id');
        $order        = 	Order::findOrFail($orderid);

        $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone','LIKE','%'.substr($order->customer_phone, -8).'%')->where('customer_source','exists',true)->orderBy('_id','asc')->where('_id','<',$order->_id)->count();

        if($count > 0){
            array_set($data, 'acquisition_type', 'renewal_direct');
        }else{
            array_set($data,'acquisition_type','direct_payment');
        }

        if(Input::json()->get('status') == 'success') {

//            echo "ih";exit();
            $orderData = [];
            array_set($orderData, 'status', '1');
            array_set($orderData, 'order_action', 'bought');
            $orderdata 	=	$order->update($orderData);

            // Give Rewards / Cashback to customer based on selection, on purchase success......
            $this->customerreward->giveCashbackOrRewardsOnOrderSuccess($order);

            //Send Instant (Email) To Customer & Finder
            $sndInstantEmailCustomer        =   $this->customermailer->healthyTiffinTrial($order->toArray());
            $sndInstantSmsCustomer	       =	$this->customersms->healthyTiffinTrial($order->toArray());
            $sndInstantEmailFinder	       = 	$this->findermailer->healthyTiffinTrial($order->toArray());
            $sndInstantSmsFinder	       =	$this->findersms->healthyTiffinTrial($order->toArray());

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
            return Response::json($resp);
        }

        $orderdata        =	$order->update($data);
        $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
        return Response::json($resp);
    }


    public function bookMembershipHealthyTiffinPaid(){

        $data = Input::json()->all();
        Log::info('bookTrialHealthyTiffinPaid',$data);

        if(empty($data['order_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - order_id");
            return  Response::json($resp, 400);
        }

        if(empty($data['status'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - status");
            return  Response::json($resp, 400);
        }

        $orderid 	=	(int) Input::json()->get('order_id');
        $order        = 	Order::findOrFail($orderid);

        $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone','LIKE','%'.substr($order->customer_phone, -8).'%')->where('customer_source','exists',true)->orderBy('_id','asc')->where('_id','<',$order->_id)->count();

        if($count > 0){
            array_set($data, 'acquisition_type', 'renewal_direct');
        }else{
            array_set($data,'acquisition_type','direct_payment');
        }

        if(Input::json()->get('status') == 'success') {

//            echo "ih";exit();
            $orderData = [];
            array_set($orderData, 'status', '1');
            array_set($orderData, 'order_action', 'bought');
            $orderdata 	=	$order->update($orderData);
            // Give Rewards / Cashback to customer based on selection, on purchase success......
            $this->customerreward->giveCashbackOrRewardsOnOrderSuccess($order);

            //Send Instant (Email) To Customer & Finder
            $sndInstantEmailCustomer        =   $this->customermailer->healthyTiffinMembership($order->toArray());
            $sndInstantSmsCustomer	       =	$this->customersms->healthyTiffinMembership($order->toArray());
            $sndInstantEmailFinder	       = 	$this->findermailer->healthyTiffinMembership($order->toArray());
            $sndInstantSmsFinder	       =	$this->findersms->healthyTiffinMembership($order->toArray());

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
            return Response::json($resp);
        }


        $orderdata        =	$order->update($data);
        $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
        return Response::json($resp);

    }



    public function bookTrialPaid(){

        $data = Input::json()->all();
//        return $data;

        if(!isset($data['customer_name']) || $data['customer_name'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_name");
            return  Response::json($resp, 400);
        }

        if(!isset($data['customer_email']) || $data['customer_email'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
            return  Response::json($resp, 400);
        }

        if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
            $resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
            return  Response::json($resp, 400);
        }

        if(!isset($data['customer_phone']) || $data['customer_phone'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
            return  Response::json($resp, 400);
        }

        if(!isset($data['finder_id']) || $data['finder_id'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
            return  Response::json($resp, 400);
        }

        if(!isset($data['service_name']) || $data['service_name'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
            return  Response::json($resp, 400);
        }

        if(!isset($data['schedule_date']) || $data['schedule_date'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_date");
            return  Response::json($resp, 400);
        }

        if(!isset($data['schedule_slot']) || $data['schedule_slot'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
            return  Response::json($resp, 400);
        }

        if(!isset($data['order_id']) || $data['order_id'] == ''){
            return Response::json($data, 200);
            $resp 	= 	array('status' => 400,'message' => "Data Missing - order_id");
            return  Response::json($resp, 400);
        }

        if(!isset($data['status']) || $data['status'] != 'success'){
            $resp 	= 	array('status' => 400,'message' => "data missing or not success - status");
            return  Response::json($resp, 400);
        }

        if(isset($data['preferred_starting_date']) && $data['preferred_starting_date'] == ""){
            unset($data['preferred_starting_date']);
        }

        try {

            $order_id = $data['order_id'];

            $order        = 	Order::findOrFail((int)$order_id);

            if(isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought'){

                $resp 	= 	array('status' => 200, 'order_id' => $order_id, 'message' => "Already Status Successfull");
                return Response::json($resp);
            }

            $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone','LIKE','%'.substr($order->customer_phone, -8).'%')->where('customer_source','exists',true)->orderBy('_id','asc')->where('_id','<',$order->_id)->count();

            if($count > 0){
                $order->update(array('acquisition_type'=>'renewal_direct'));
            }else{
                array_set($data,'acquisition_type','direct_payment');
            }
            
            $source                             =   (isset($order->customer_source) && $order->customer_source != '') ? trim($order->customer_source) : "website";

            $service_id	 				       =	(isset($order->service_id) && $order->service_id != '') ? intval($order->service_id) : "";

            $campaign	 				       =	(isset($data['campaign']) && $data['campaign'] != '') ? $data['campaign'] : "";
            $otp	 					       =	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";
            $slot_times 				       =	explode('-',$data['schedule_slot']);
            $schedule_slot_start_time 	       =	$slot_times[0];
            $schedule_slot_end_time 	       =	$slot_times[1];
            $schedule_slot 				       =	$schedule_slot_start_time.'-'.$schedule_slot_end_time;
            $slot_date 					       =	date('d-m-Y', strtotime(Input::json()->get('schedule_date')));
            $schedule_date_starttime 	       =	strtoupper($slot_date ." ".$schedule_slot_start_time);

            $booktrialid 				       =	Booktrial::max('_id') + 1;
            $finderid 					       = 	(int) Input::json()->get('finder_id');
            $finder 					       = 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->find($finderid);

            $customer_id 				       =	$this->autoRegisterCustomer($data);
            $customer_name 				       =	Input::json()->get('customer_name');
            $customer_email 			       =	Input::json()->get('customer_email');
            $customer_phone 			       =	preg_replace("/[^0-9]/", "", Input::json()->get('customer_phone')) ;Input::json()->get('customer_phone');
            $fitcard_user				       = 	(Input::json()->get('fitcard_user')) ? intval(Input::json()->get('fitcard_user')) : 0;
            $type						       = 	(Input::json()->get('type')) ? Input::json()->get('type') : '';

            $finder_name				       = 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
            $finder_slug				       = 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
            $finder_lat 				       = 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon 				       = 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $city_id 					       =	(int) $finder['city_id'];

            $finder_commercial_type		       = 	(isset($finder['commercial_type']) && $finder['commercial_type'] != '') ? (int)$finder['commercial_type'] : "";
            $finder_category_id				       = 	(isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";
            $note_to_trainer                    =   (isset($data['note_to_trainer']) && $data['note_to_trainer'] != '') ? $data['note_to_trainer'] : "";

            $final_lead_stage = '';
            $final_lead_status = '';

            $confirmed = array(1,2,3);

            if(in_array($finder_commercial_type, $confirmed)){

                $final_lead_stage = 'trial_stage';
                $final_lead_status = 'confirmed';

            }else{

                $final_lead_stage = 'booking_stage';
                $final_lead_status = 'call_to_confirm';
            }

            $gcm_reg_id					       = 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";
            $device_type				       = 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
            $social_referrer			       = 	(isset($data['social_referrer']) && $data['social_referrer'] != '') ? $data['social_referrer'] : "";
            $transacted_after			       = 	(isset($data['transacted_after']) && $data['transacted_after'] != '') ? $data['transacted_after'] : "";
            $referrer_object			       = 	(isset($data['referrer_object']) && $data['referrer_object'] != '') ? $data['referrer_object'] : "";

            $age                                =   (isset($data['age']) && $data['age'] != '') ? $data['age'] : "";
            $injury                             =   (isset($data['injury']) && $data['injury'] != '') ? $data['injury'] : "";
            $note_to_trainer                    =   (isset($data['note_to_trainer']) && $data['note_to_trainer'] != '') ? $data['note_to_trainer'] : "";

            if($device_type != '' && $gcm_reg_id != ''){

                $reg_data = array();

                $reg_data['customer_id'] = $customer_id;
                $reg_data['reg_id'] = $gcm_reg_id;
                $reg_data['type'] = $device_type;

                $this->addRegId($reg_data);
            }


            // $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
            // $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
            // $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;

            $description =  $what_i_should_carry = $what_i_should_expect = '';
            if($service_id != ''){
                $serviceArr 				       = 	Service::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('category')->with('subcategory')->find($service_id);

                if((isset($serviceArr['category']['description']) && $serviceArr['category']['description'] != '')){
                    $description = $serviceArr['category']['description'];
                }else{
                    if((isset($serviceArr['subcategory']['description']) && $serviceArr['subcategory']['description'] != '')){
                        $description = $serviceArr['subcategory']['description'];
                    }
                }

                if((isset($serviceArr['category']['what_i_should_carry']) && $serviceArr['category']['what_i_should_carry'] != '')){
                    $what_i_should_carry = $serviceArr['category']['what_i_should_carry'];
                }else{
                    if((isset($serviceArr['subcategory']['what_i_should_carry']) && $serviceArr['subcategory']['what_i_should_carry'] != '')){
                        $what_i_should_carry = $serviceArr['subcategory']['what_i_should_carry'];
                    }
                }



                if((isset($serviceArr['location']['name']) && $serviceArr['location']['name'] != '')){
                    $finder_location			       =	$serviceArr['location']['name'];
                    $show_location_flag 		       =   true;
                }else{
                    $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
                    $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;
                }
                if((isset($serviceArr['address']) && $serviceArr['address'] != '')){
                    $finder_address				       = 	$serviceArr['address'];
                }else{
                    $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
                }
            }else{
                $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
                $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
                $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;
            }

            $finder_lat		       =	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon		       =	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";

            $google_pin		       =	$this->googlePin($finder_lat,$finder_lon);

            $finder_photos	       = 	[];
            if(isset($finder['photos']) && count($finder['photos']) > 0){
                foreach ($finder['photos'] as $key => $value) {
                    if($key > 2){ continue; }
                    array_push($finder_photos, Config::get('app.s3_finder_url').'g/full/'.$value['url']);
                }
            }

            $finder_vcc_email = "";
            if(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != ''){
                $explode = explode(',', $finder['finder_vcc_email']);
                $valid_finder_email = [];
                foreach ($explode as $email) {
                    if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
                        $valid_finder_email[] = $email;
                    }
                }
                if(!empty($valid_finder_email)){
                    $finder_vcc_email = implode(",", $valid_finder_email);
                }
            }

            $finder_vcc_mobile			       = 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
            $finder_poc_for_customer_name       = 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
            $finder_poc_for_customer_no	       = 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
            $share_customer_no			       = 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;


            $service_name				       =	strtolower(Input::json()->get('service_name'));
            $schedule_date				       =	date('Y-m-d 00:00:00', strtotime($slot_date));
            $schedule_date_time			       =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

            $code						       =	random_numbers(5);
            $device_id					       = 	(Input::has('device_id') && Input::json()->get('device_id') != '') ? Input::json()->get('device_id') : "";
            $premium_session 			       =	(Input::json()->get('premium_session')) ? (boolean) Input::json()->get('premium_session') : false;
            $reminder_need_status 		       =	(Input::json()->get('reminder_need_status')) ? Input::json()->get('reminder_need_status') : '';
            $additional_info			       = 	(Input::has('additional_info') && Input::json()->get('additional_info') != '') ? Input::json()->get('additional_info') : "";


            $orderid    =   (int) $data['order_id'];
            $order      =   Order::findOrFail($orderid);
            $type       =   $order->type;

            if($type == "vip_booktrials"){
                $kit_enabled = true;
            }else{
                $kit_enabled = false;
            }

            $medical_detail                     =   (isset($order->medical_detail) && $order->medical_detail != '') ? $order->medical_detail : "";
            $medication_detail                  =   (isset($order->medication_detail) && $order->medication_detail != '') ? $order->medication_detail : "";
            $medical_condition                     =   (isset($order->medical_condition) && $order->medical_condition != '') ? $order->medical_condition : "";

            $booktrialdata = array(
                'booktrialid'                   =>      intval($booktrialid),
                'premium_session'               =>      $premium_session,
                'reminder_need_status'          =>      $reminder_need_status,
                'booktrialid'			       =>		intval($booktrialid),
                'campaign'				       =>		$campaign,
                'premium_session' 		       =>		$premium_session,
                'reminder_need_status' 	       =>		$reminder_need_status,

                'customer_id' 			       =>		$customer_id,
                'customer_name' 		       =>		$customer_name,
                'customer_email' 		       =>		$customer_email,
                'customer_phone' 		       =>		$customer_phone,
                'fitcard_user'			       =>		$fitcard_user,
                'type'					       =>		$type,

                'finder_id'                     =>      $finderid,
                'finder_name'                   =>      $finder_name,
                'finder_slug'                   =>      $finder_slug,
                'finder_location'               =>      $finder_location,
                'finder_address'                =>      $finder_address,
                'finder_lat'                    =>      $finder_lat,
                'finder_lon'                    =>      $finder_lon,
                'finder_photos'                 =>      $finder_photos,
                'description'                   =>      $description,
                'what_i_should_carry'           =>      $what_i_should_carry,
                'what_i_should_expect'          =>      $what_i_should_expect,

                'city_id'                       =>      $city_id,
                'finder_vcc_email'              =>      $finder_vcc_email,
                'finder_vcc_mobile'             =>      $finder_vcc_mobile,
                'finder_poc_for_customer_name'  =>      $finder_poc_for_customer_name,
                'finder_poc_for_customer_no'    =>      $finder_poc_for_customer_no,
                'show_location_flag'            =>      $show_location_flag,
                'share_customer_no'             =>      $share_customer_no,

                'service_id'                    =>      $service_id,
                'service_name'                  =>      $service_name,
                'schedule_slot_start_time'      =>      $schedule_slot_start_time,
                'schedule_slot_end_time'        =>      $schedule_slot_end_time,
                'schedule_date'                 =>      $schedule_date,
                'schedule_date_time'            =>      $schedule_date_time,
                'schedule_slot'                 =>      $schedule_slot,
                'going_status'                  =>      1,
                'going_status_txt'              =>      'going',
                'code'                          =>      $code,
                'device_id'                     =>      $device_id,
                'booktrial_type'                =>      'auto',
                'booktrial_actions'             =>      'call to confirm trial',
                'source'                        =>      $source,
                'origin'                        =>      'auto',
                'additional_info'               =>      $additional_info,
                'amount'                        =>      $order->amount,
                'otp'                           =>      $otp,
                'source_flag'                   =>      'customer',

                'final_lead_stage'              =>      $final_lead_stage,
                'final_lead_status'             =>      $final_lead_status,

                'reg_id'                        =>      $gcm_reg_id,
                'device_type'                   =>      $device_type,

                'finder_category_id'            =>      $finder_category_id,
                'social_referrer'               =>      $social_referrer,
                'transacted_after'              =>      $transacted_after,
                'referrer_object'               =>      $referrer_object,
                'google_pin'                    =>      $google_pin,

                'kit_enabled'                   =>      $kit_enabled,

                'medical_condition'             =>      $medical_condition,
                'age'                           =>      $age,
                'injury'                        =>      $injury,
                'note_to_trainer'               =>      $note_to_trainer,
                'membership_duration_type'      =>      'workout_session',
                'medical_detail'                =>      $medical_detail,
                'medication_detail'             =>      $medication_detail
            );

            if ($medical_detail != "" && $medication_detail != "") {

                $customer_info = new CustomerInfo();
                $response = $customer_info->addHealthInfo($booktrialdata);
            }

            // Add Cashback and rewards to booktrialdata if exist in orders....
            isset($order['cashback']) ? $booktrialdata['cashback'] = $order['cashback']:null;
            isset($order['reward_ids']) ? $booktrialdata['reward_ids'] = $order['reward_ids']:null;
            
            if(isset($data['customofferorder_id']) && $data['customofferorder_id'] != ""){
                $booktrialdata['customofferorder_id'] = $data['customofferorder_id'];
            }
            // return $this->customersms->bookTrial($booktrialdata);
//             return $booktrialdata;
            $booktrial = new Booktrial($booktrialdata);
            $booktrial->_id = (int) $booktrialid;
            $trialbooked = $booktrial->save();

            Log::info('$trialbooked : '.json_encode($trialbooked));


            // Give Rewards / Cashback to customer based on selection, on purchase success......
            $this->customerreward->giveCashbackOrRewardsOnOrderSuccess($order);

        } catch(ValidationException $e){

            // If booktrial query fail updates error message
            $orderid 	=	(int) Input::json()->get('order_id');
            $order        = 	Order::findOrFail($orderid);
            array_set($data, 'message', $e->getMessage());
            $orderdata 	=	$order->update($data);
            return array('status' => 500,'message' => $e->getMessage());
        }

        if($trialbooked == true){

            try {
                $this->addReminderMessage($booktrialid);

            }catch (Exception $e) {

                $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

                $response = array('status'=>400,'reason'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
                Log::info('addReminderMessage Error : '.json_encode($response));
            }

            $orderid = (int) Input::json()->get('order_id');
            $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueBookTrialPaid', array('data'=>$data,'orderid'=>$orderid,'booktrialid'=>$booktrialid),'booktrialv2');
            $booktrial->update(array('redis_id'=>$redisid));

        }

        /*if($trialbooked == true && $campaign != ''){
            $this->attachTrialCampaignToCustomer($customer_id,$campaign,$booktrialid);
        }*/
        
        Log::info('Customer Book Trial : '.json_encode(array('book_trial_details' => Booktrial::findOrFail($booktrialid))));

        $resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'message' => "Book a Trial", 'code' => $code);
        return Response::json($resp,200);
    }

    public function toQueueBookTrialPaid($job,$data){

        $job->delete();

        try{
            $orderid = $data['orderid'];
            $booktrialid = $data['booktrialid'];
            $data = $data['data'];

            $slot_times 				       =	explode('-',$data['schedule_slot']);
            $schedule_slot_start_time 	       =	$slot_times[0];
            $schedule_slot_end_time 	       =	$slot_times[1];
            $schedule_slot 				       =	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

            $slot_date 					       =	date('d-m-Y', strtotime($data['schedule_date']));
            $schedule_date_starttime 	       =	strtoupper($slot_date ." ".$schedule_slot_start_time);
            $currentDateTime 			       =	\Carbon\Carbon::now();
            $scheduleDateTime 			       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime);
            $delayReminderTimeBefore1Min        =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
            $delayReminderTimeBefore1Hour        =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
            $delayReminderTimeBefore3Hour        =  \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 3);
            $delayReminderTimeBefore5Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 5);
            $delayReminderTimeBefore12Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
            $delayReminderTimeAfter2Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(120);
            $delayReminderTimeAfter50Hour        =   \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 50);
            $reminderTimeAfter1Hour 	       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(60);
            $oneHourDiff 				       = 	$currentDateTime->diffInHours($scheduleDateTime, false);
            $twelveHourDiff 			       = 	$currentDateTime->diffInHours($scheduleDateTime, false);
            $oneHourDiffInMin 			       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $fiveHourDiffInMin 			       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $twelveHourDiffInMin 		       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $threeHourDiffInMin                =    $currentDateTime->diffInHours($scheduleDateTime, false);
            $finderid 					       = 	(int) $data['finder_id'];

            $booktrialdata = Booktrial::findOrFail($booktrialid)->toArray();
            $order = Order::findOrFail($orderid);
            $finder = Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();
            if(isset($booktrialdata['customofferorder_id'])){
                $booktrialdata['customofferorder'] = Customofferorder::where('_id',$booktrialdata['customofferorder_id'])
                    ->with('customoffer')
                    ->first();
            }

            $finder_category_id 		       = (isset($booktrialdata['finder_category_id']) && $booktrialdata['finder_category_id'] != '') ? $booktrialdata['finder_category_id'] : "";

            array_set($data, 'status', '1');
            array_set($data, 'order_action', 'bought');
            array_set($data, 'booktrial_id', (int)$booktrialid);
            $orderdata 	=	$order->update($data);

            $customer_email_messageids 	=  $finder_email_messageids  =	$customer_sms_messageids  =  $finer_sms_messageids  =  $customer_notification_messageids  =  array();

            //Send Instant (Email) To Customer & Finder
            if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip'){

                if(isset($booktrialdata['campaign'])){

                    switch($booktrialdata['campaign']){
                        case 'yogaday':
                            // Yogaday campaign EMAIL/SMS.........
                            $sndInstantEmailCustomer		       = 	$this->customermailer->bookYogaDayTrial($booktrialdata);
                            $sndInstantSmsCustomer			       =	$this->customersms->bookYogaDayTrial($booktrialdata);
                            break;
                        default:
                            break;
                    }
                }

                // Normal flow.........
                !isset($sndInstantEmailCustomer) ?  $sndInstantEmailCustomer = $this->customermailer->bookTrial($booktrialdata) : array();
                !isset($sndInstantSmsCustomer) ? $sndInstantSmsCustomer	=	$this->customersms->bookTrial($booktrialdata) : array();
                // Send Email to Customer........
                $customer_email_messageids['instant'] 	= 	$sndInstantEmailCustomer;
                $customer_sms_messageids['instant'] 	= 	$sndInstantSmsCustomer;
            }

            if(isset($booktrialdata['campaign'])) {
                switch ($booktrialdata['campaign']) {
                    case 'yogaday':
                        // Yogaday campaign EMAIL/SMS.........
                        $sndInstantEmailFinder = $this->findermailer->bookYogaDayTrial($booktrialdata);
                        $sndInstantSmsFinder = $this->findersms->bookYogaDayTrial($booktrialdata);
                        break;
                }
            }

            if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip'){

                //no auto sms (N-3)for paid trial
                $customer_auto_sms = 'no n-3 for paid trials';//$this->autoSms($booktrialdata,$schedule_date_starttime);

                if($booktrialdata['type'] == 'vip_booktrials'){
                    $myreward = $this->addVIPTrialAsRewardOnVIPPaidTrial($booktrialdata, $orderid);

                    $customer_email_messageids['vipreward'] = $myreward['email'];
                    $customer_sms_messageids['vipreward'] = $myreward['sms'];
                }

            }

            // Normal flow.........
            !isset($sndInstantEmailFinder) ?  $sndInstantEmailFinder = $this->findermailer->bookTrial($booktrialdata) : array();
            !isset($sndInstantSmsFinder) ? $sndInstantSmsFinder =   $this->findersms->bookTrial($booktrialdata) : array();
            $finder_email_messageids['instant']     =   $sndInstantEmailFinder;
            $finer_sms_messageids['instant']        =   $sndInstantSmsFinder;

            //Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
            if($twelveHourDiffInMin >= (12 * 60)){

                // if($finder_category_id != 41){
                    if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                        $sndBefore12HourEmailCustomer = $this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
                        $customer_email_messageids['before12hour'] = $sndBefore12HourEmailCustomer;
                    }
                // }

                if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                    $customer_notification_messageids['before12hour'] = $this->customernotification->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
                    }
                }

            }else{
                // if($finder_category_id != 41){
                    if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                        $sndBefore12HourEmailCustomer = $this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
                        $customer_email_messageids['before12hour'] = $sndBefore12HourEmailCustomer;
                    }
                // }

                if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != '') {
                    if (isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                        $customer_notification_messageids['before12hour'] = $this->customernotification->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
                    }
                }

            }

            //Send Reminder Notiication (Sms) Before 1 Hour To Customer
            /*if($oneHourDiffInMin >= 60){

                $sndBefore1HourSmsFinder			       =	$this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                $finer_sms_messageids['before1hour']        = 	$sndBefore1HourSmsFinder;

                if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                    if ($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != '') {
                        $customer_notification_messageids['before1hour'] = $this->customernotification->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    } else {
                        $customer_sms_messageids['before1hour'] = $this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }
                }

            }*/
            //Send Reminder Notiication (Sms) Before 3 Hour To Customer
            if($threeHourDiffInMin >= 180){

                $sndBefore3HourSmsFinder                   =    $this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore3Hour);
                $finer_sms_messageids['before1hour']        =   $sndBefore3HourSmsFinder;

                if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                    if ($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != '') {
                        $customer_notification_messageids['before1hour'] = $this->customernotification->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore3Hour);
                    } else {
                        $customer_sms_messageids['before1hour'] = $this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore3Hour);
                    }
                }

            }

            //Send Post Trial Notificaiton After 2 Hours Need to Write
            if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                if($booktrialdata['type'] == '3daystrial'){

                        $customer_sms_messageids['after2hour'] = $this->customersms->reminderAfter2Hour3DaysTrial($booktrialdata, $delayReminderTimeAfter2Hour);

                        $customer_email_messageids['after50hour'] = $this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter50Hour);
                        
                        if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){  
                            $customer_notification_messageids['after50hour'] = $this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter50Hour);
                        }else{
                            $customer_sms_messageids['after50hour'] = $this->missedCallReview($booktrialdata, $delayReminderTimeAfter50Hour);
                        }

                }else{

                        if($booktrialdata['type'] != "workout-session"){
                            $sndAfter2HourEmailCustomer                         =   $this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
                            $customer_email_messageids['after2hour']            =   $sndAfter2HourEmailCustomer;
                            
                            if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){  
                                $customer_notification_messageids['after2hour'] = $this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
                            }else{
                                $customer_sms_messageids['after2hour'] = $this->missedCallReview($booktrialdata, $delayReminderTimeAfter2Hour);
                            }
                        }

                }
            }



            //update queue ids for booktiral
            $booktrial        = 	Booktrial::findOrFail($booktrialid);

            $queueddata 	= 	array('customer_emailqueuedids' => $customer_email_messageids,
                'customer_smsqueuedids' => $customer_sms_messageids,
                'customer_notification_messageids' => $customer_notification_messageids,
                'finder_emailqueuedids' => $finder_email_messageids,
                'finder_smsqueuedids' => $finer_sms_messageids,
                'customer_auto_sms' => $customer_auto_sms
            );

            $fitness_force  = 	$this->fitnessforce->createAppointment(['booktrial'=>$booktrial,'finder'=>$finder]);

            if($fitness_force){
                if($fitness_force['status'] == 200){
                    $queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
                    $queueddata['fitness_force_appointment']['status'] = 200;
                    $queueddata['fitness_force_appointment'] = $fitness_force['data'];
                }else{
                    $queueddata['fitness_force_appointment'] = $fitness_force;
                }
            }

            $trialbooked 	= 	$booktrial->update($queueddata);

        }catch(\Exception $exception){
            Log::error($exception);
        }

    }


    public function bookTrialFree($data = null)
    {

        // send error message if any thing is missing
        !isset($data) ? $data = Input::json()->all() : null;

        Log::info('input_data',$data);

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

        if(empty($data['customer_phone'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
            return  Response::json($resp, 400);
        }

        if(empty($data['finder_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
            return  Response::json($resp, 400);
        }

        if(empty($data['service_name'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
            return  Response::json($resp, 400);
        }

        if(empty($data['schedule_date'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_date");
            return  Response::json($resp, 400);
        }

        if(empty($data['schedule_slot'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
            return  Response::json($resp, 400);
        }

        $myreward_id = "";

        if (isset($data['type']) && $data['type'] == 'vip_booktrials_rewarded') {
            if (empty($data['reward_id'])) {
                $resp = array('status' => 400, 'message' => "Data Missing - reward_id");
                return Response::json($resp, 400);
            } else {

                $myreward_id = (int)$data['reward_id'];

                $myreward = Myreward::find($myreward_id);

                if ($myreward->status == "1") {
                    $resp = array('status' => 400, 'message' => "Reward Already Claimed");
                    return Response::json($resp, 400);
                }
            }
        }

        try {

            $service_id	 				       =	(isset($data['service_id']) && $data['service_id'] != '') ? intval($data['service_id']) : "";
            $campaign	 				       =	(isset($data['campaign']) && $data['campaign'] != '') ? $data['campaign'] : "";
            $otp	 					       =	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";
            $slot_times 				       =	explode('-',$data['schedule_slot']);
            $schedule_slot_start_time 	       =	$slot_times[0];
            $schedule_slot_end_time 	       =	$slot_times[1];
            $schedule_slot 				       =	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

            $slot_date = date('d-m-Y', strtotime($data['schedule_date']));
            $schedule_date_starttime = strtoupper($slot_date . " " . $schedule_slot_start_time);
            $currentDateTime = \Carbon\Carbon::now();

            $booktrialid = Booktrial::max('_id') + 1;
            isset($data['finder_id']) ? $finderid = (int)$data['finder_id'] : null;
            $finder = Finder::with(array('location' => function ($query) {
                $query->select('_id', 'name', 'slug');
            }))->with('locationtags')->where('_id', '=', $finderid)->first()->toArray();
            $customer_id = $this->autoRegisterCustomer($data);

            // Throw an error if user has already booked a trial for that vendor...
            $alreadyBookedTrials = $this->utilities->checkExistingTrialWithFinder($data['customer_email'], $data['customer_phone'], $data['finder_id']);
            if (count($alreadyBookedTrials) > 0) {
                $resp = array('status' => 403, 'message' => "You have already booked a trial for this vendor");
                return Response::json($resp, 403);
            }

            // Throw an error if user has already booked a trial on same schedule timestamp..
            $dates = $this->utilities->getDateTimeFromDateAndTimeRange($data['schedule_date'], $data['schedule_slot']);
            $UpcomingTrialsOnTimestamp = $this->utilities->getUpcomingTrialsOnTimestamp($customer_id, $dates['start_timestamp'], $finderid);
            if (count($UpcomingTrialsOnTimestamp) > 0) {
                $resp = array('status' => 403, 'message' => "You have already booked a trial on same datetime");
                return Response::json($resp, 403);
            }

            isset($data['customer_name']) ? $customer_name = $data['customer_name'] : null;
            isset($data['customer_email']) ? $customer_email = $data['customer_email'] : null;
            isset($data['customer_phone']) ? $customer_phone = preg_replace("/[^0-9]/", "", $data['customer_phone']) : null;
            $fitcard_user = isset($data['fitcard_user']) ? intval($data['fitcard_user']) : 0;
            $type = isset($data['type']) ? $type = $data['type'] : '';

            $finder_name = (isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
            $finder_slug = (isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
            $finder_lat = (isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon = (isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $city_id = (int)$finder['city_id'];
            $finder_commercial_type = (isset($finder['commercial_type']) && $finder['commercial_type'] != '') ? (int)$finder['commercial_type'] : "";
            $finder_category_id = (isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";

            $google_pin = $this->googlePin($finder_lat, $finder_lon);

            $referrer_booktrial_id = (isset($data['referrer_booktrial_id']) && $data['referrer_booktrial_id'] != '') ? intval($data['referrer_booktrial_id']) : "";
            $root_booktrial_id = "";
            $kit_enabled = false;

            if ($referrer_booktrial_id != "") {

                $trial_detail = Booktrial::find($referrer_booktrial_id);

                if ($trial_detail) {

                    if (isset($trial_detail->root_booktrial_id) && $trial_detail->root_booktrial_id != "") {
                        $root_booktrial_id = $trial_detail->root_booktrial_id;
                    } else {
                        $root_booktrial_id = $referrer_booktrial_id;
                    }

                    $kit_enabled = ($type == 'vip_booktrials_invited' && isset($trial_detail->kit_enabled) && $trial_detail->kit_enabled == true) ? true : false;
                }
            }

            $google_pin                         =   $this->googlePin($finder_lat,$finder_lon);
            $source                             =   (isset($data['customer_source']) && $data['customer_source'] != '') ? trim($data['customer_source']) : "website";

            $final_lead_stage = '';
            $final_lead_status = '';

            $confirmed = array(1,2,3);

            if (in_array($finder_commercial_type, $confirmed)) {

                $final_lead_stage = 'trial_stage';
                $final_lead_status = 'confirmed';

            }else{

                $final_lead_stage = 'booking_stage';
                $final_lead_status = 'call_to_confirm';
            }

            $device_type = (isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";
            $gcm_reg_id = (isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";

            $social_referrer = (isset($data['social_referrer']) && $data['social_referrer'] != '') ? $data['social_referrer'] : "";
            $referrer_object = (isset($data['referrer_object']) && $data['referrer_object'] != '') ? $data['referrer_object'] : "";
            $transacted_after = (isset($data['transacted_after']) && $data['transacted_after'] != '') ? $data['transacted_after'] : "";
            $note_to_trainer = (isset($data['note_to_trainer']) && $data['note_to_trainer'] != '') ? $data['note_to_trainer'] : "";

            if($device_type != '' && $gcm_reg_id != ''){

                $reg_data = array();

                $reg_data['customer_id'] = $customer_id;
                $reg_data['reg_id'] = $gcm_reg_id;
                $reg_data['type'] = $device_type;

                $this->addRegId($reg_data);
            }

            // $finder_location                 =   (isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
            // $finder_address                      =   (isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
            // $show_location_flag              =   (count($finder['locationtags']) > 1) ? false : true;

            $description = $what_i_should_carry = $what_i_should_expect = '';
            if ($service_id != '') {
                $serviceArr = Service::with(array('location' => function ($query) {
                    $query->select('_id', 'name', 'slug');
                }))->with('category')->with('subcategory')->where('_id', '=', intval($service_id))->first()->toArray();

                if((isset($serviceArr['category']['description']) && $serviceArr['category']['description'] != '')){
                    $description = $serviceArr['category']['description'];
                }else{
                    if((isset($serviceArr['subcategory']['description']) && $serviceArr['subcategory']['description'] != '')){
                        $description = $serviceArr['subcategory']['description'];
                    }
                }

                if((isset($serviceArr['category']['what_i_should_carry']) && $serviceArr['category']['what_i_should_carry'] != '')){
                    $what_i_should_carry = $serviceArr['category']['what_i_should_carry'];
                }else{
                    if((isset($serviceArr['subcategory']['what_i_should_carry']) && $serviceArr['subcategory']['what_i_should_carry'] != '')){
                        $what_i_should_carry = $serviceArr['subcategory']['what_i_should_carry'];
                    }
                }

                if((isset($serviceArr['category']['what_i_should_expect']) && $serviceArr['category']['what_i_should_expect'] != '')){
                    $what_i_should_expect = $serviceArr['category']['what_i_should_expect'];
                }else{
                    if((isset($serviceArr['subcategory']['what_i_should_expect']) && $serviceArr['subcategory']['what_i_should_expect'] != '')){
                        $what_i_should_expect = $serviceArr['subcategory']['what_i_should_expect'];
                    }
                }


                if((isset($serviceArr['location']['name']) && $serviceArr['location']['name'] != '')){
                    $finder_location			       =	$serviceArr['location']['name'];
                    $show_location_flag 		       =   true;
                }else{
                    $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
                    $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;
                }
                if((isset($serviceArr['address']) && $serviceArr['address'] != '')){
                    $finder_address				       = 	$serviceArr['address'];
                }else{
                    $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
                }
            }else{
                $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
                $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
                $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;
            }

            $finder_lat		       =	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon		       =	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $finder_photos	       = 	[];
            if(isset($finder['photos']) && count($finder['photos']) > 0){
                foreach ($finder['photos'] as $key => $value) {
                    if($key > 2){ continue; }
                    array_push($finder_photos, Config::get('app.s3_finder_url').'g/full/'.$value['url']);
                }
            }

            $finder_vcc_email = "";
            if(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != ''){
                $explode = explode(',', $finder['finder_vcc_email']);
                $valid_finder_email = [];
                foreach ($explode as $email) {
                    if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
                        $valid_finder_email[] = $email;
                    }
                }
                if(!empty($valid_finder_email)){
                    $finder_vcc_email = implode(",", $valid_finder_email);
                }
            }

                        $finder_vcc_mobile = (isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
            $finder_poc_for_customer_name = (isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
            $finder_poc_for_customer_no = (isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
            $share_customer_no = (isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;

            isset($data['service_name']) ? $service_name = strtolower($data['service_name']) : null;
            $schedule_date = date('Y-m-d 00:00:00', strtotime($slot_date));
            $schedule_date_time = Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

            $code = random_numbers(5);
            $device_id = (isset($data['device_id']) && $data['device_id'] != '') ? $data['device_id'] : "";
            $premium_session = (isset($data['premium_session']) && $data['premium_session'] != '') ? (bool)$data['premium_session'] : false;
            $reminder_need_status = isset($data['reminder_need_status']) ? $data['reminder_need_status'] : '';
            $additional_info = (isset($data['additional_info']) && $data['additional_info'] != '') ? $data['additional_info'] : "";

            $medical_condition = (isset($data['medical_condition']) && $data['medical_condition'] != '') ? $data['medical_condition'] : "";
            $age = (isset($data['age']) && $data['age'] != '') ? $data['age'] : "";
            $injury = (isset($data['injury']) && $data['injury'] != '') ? $data['injury'] : "";
            $note_to_trainer = (isset($data['note_to_trainer']) && $data['note_to_trainer'] != '') ? $data['note_to_trainer'] : "";
            $medical_detail = (isset($data['medical_detail']) && $data['medical_detail'] != '') ? $data['medical_detail'] : "";
            $medication_detail = (isset($data['medication_detail']) && $data['medication_detail'] != '') ? $data['medication_detail'] : "";
            $source                   =   (isset($data['customer_source']) && $data['customer_source'] != '') ? trim($data['customer_source']) : "website";


            $booktrialdata = array(

                'booktrialid'         =>      $booktrialid,
                'campaign'            =>      $campaign,

                'premium_session'     =>      $premium_session,
                'reminder_need_status'          =>      $reminder_need_status,

                'customer_id'         =>      $customer_id,
                'customer_name'       =>      $customer_name,
                'customer_email'      =>      $customer_email,
                'customer_phone'      =>      $customer_phone,
                'fitcard_user'        =>      $fitcard_user,
                'type'                =>      $type,

                'finder_id'           =>      $finderid,
                'finder_name'         =>      $finder_name,
                'finder_slug'         =>      $finder_slug,
                'finder_location'     =>      $finder_location,
                'finder_address'      =>      $finder_address,
                'finder_lat'          =>      $finder_lat,
                'finder_lon'          =>      $finder_lon,
                'finder_photos'       =>      $finder_photos,
                'description'         =>      $description,
                'what_i_should_carry' =>      $what_i_should_carry,
                'what_i_should_expect'          =>      $what_i_should_expect,

                'city_id'             =>      $city_id,
                'finder_vcc_email'    =>      $finder_vcc_email,
                'finder_vcc_mobile'   =>      $finder_vcc_mobile,
                'finder_poc_for_customer_name'  =>      $finder_poc_for_customer_name,
                'finder_poc_for_customer_no'    =>      $finder_poc_for_customer_no,
                'show_location_flag'  =>      $show_location_flag,
                'share_customer_no'   =>      $share_customer_no,

                'service_id'          =>      $service_id,
                'service_name'        =>      $service_name,
                'schedule_slot_start_time'      =>      $schedule_slot_start_time,
                'schedule_slot_end_time'        =>      $schedule_slot_end_time,
                'schedule_date'       =>      $schedule_date,
                'schedule_date_time'  =>      $schedule_date_time,
                'schedule_slot'       =>      $schedule_slot,
                'going_status'        =>      1,
                'going_status_txt'    =>      'going',
                'code'                =>      $code,
                'device_id'           =>      $device_id,
                'booktrial_type'      =>      'auto',
                'booktrial_actions'   =>      'call to confirm trial',
                'source'              =>      $source,
                'origin'              =>      'auto',
                'additional_info'     =>      $additional_info,
                'otp'                 =>      $otp,
                'source_flag'         =>      'customer',
                'final_lead_stage'    =>      $final_lead_stage,
                'final_lead_status'   =>      $final_lead_status,

                'reg_id'              =>      $gcm_reg_id,
                'device_type'         =>      $device_type,

                'social_referrer'     =>      $social_referrer,
                'transacted_after'    =>      $transacted_after,
                'finder_category_id'  =>      $finder_category_id,
                'referrer_object'     =>      $referrer_object,

                'google_pin'          =>      $google_pin,
                'note_to_trainer'     =>      $note_to_trainer,
                'referrer_booktrial_id' => $referrer_booktrial_id,
                'root_booktrial_id' => $root_booktrial_id,
                'kit_enabled' => $kit_enabled,

                'medical_condition' => $medical_condition,
                'age' => $age,
                'injury' => $injury,
                'note_to_trainer' => $note_to_trainer,
                'reward_id' => $myreward_id,
                'medical_detail' => $medical_detail,
                'medication_detail' => $medication_detail

            );

            // return $this->customersms->bookTrial($booktrialdata);
            // return $booktrialdata;
            $booktrial = new Booktrial($booktrialdata);
            $booktrial->_id = $booktrialid;
            $trialbooked = $booktrial->save();

            if ($medical_detail != "" && $medication_detail != "") {

                $customer_info = new CustomerInfo();
                $response = $customer_info->addHealthInfo($booktrialdata);
            }

            if ($type == 'vip_booktrials_rewarded') {

                $myreward->update(array('status' => '1', 'reward_action' => 'claimed'));
            }

        } catch (ValidationException $e) {

            return array('status' => 500, 'message' => $e->getMessage());
        }

        if($trialbooked == true){

            try {
                $this->addReminderMessage($booktrialid);

            }catch (Exception $e) {

                $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

                $response = array('status'=>400,'reason'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
                Log::info('addReminderMessage Error : '.json_encode($response));
            }

            //if vendor type is free special dont send communication
           /* Log::info('finder commercial_type  -- '. $finder['commercial_type']);
            if($finder['commercial_type'] != '2'){*/
                $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueBookTrialFree', array('data'=>$data,'booktrialid'=>$booktrialid), 'booktrialv2');
                $booktrial->update(array('redis_id'=>$redisid));
            /*}else{

                $customer_sms_free_special = $this->customersms->bookTrialFreeSpecial($booktrialdata);
                $booktrial->customer_sms_free_special = $customer_sms_free_special;
                $booktrial->update();
            }*/
        }

        /*if($trialbooked == true && $campaign != ''){
            $this->attachTrialCampaignToCustomer($customer_id,$campaign,$booktrialid);
        }*/

        Log::info('Customer Book Trial : '.json_encode(array('book_trial_details' => Booktrial::findOrFail($booktrialid))));

        $resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'code' => $code, 'message' => "Book a Trial");
        return Response::json($resp,200);
    }

    public function toQueueBookTrialFree($job,$data){

    	$job->delete();

        try{

            $booktrialid = $data['booktrialid'];
            $data = $data['data'];

            $slot_times 				       =	explode('-',$data['schedule_slot']);
            $schedule_slot_start_time 	       =	$slot_times[0];
            $schedule_slot_end_time 	       =	$slot_times[1];
            $schedule_slot 				       =	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

            $slot_date 					       =	date('d-m-Y', strtotime($data['schedule_date']));
            $schedule_date_starttime 	       =	strtoupper($slot_date ." ".$schedule_slot_start_time);
            $currentDateTime 			       =	\Carbon\Carbon::now();
            $scheduleDateTime 			       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime);
            $delayReminderTimeBefore1Min        =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
            $delayReminderTimeBefore1Hour        =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
            $delayReminderTimeBefore5Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 5);
            $delayReminderTimeBefore12Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
            $delayReminderTimeAfter2Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(120);
            $delayReminderTimeAfter50Hour       =   \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 50);
            $reminderTimeAfter1Hour 	       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(60);
            $oneHourDiff 				       = 	$currentDateTime->diffInHours($scheduleDateTime, false);
            $twelveHourDiff 			       = 	$currentDateTime->diffInHours($scheduleDateTime, false);
            $oneHourDiffInMin 			       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $fiveHourDiffInMin 			       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $twelveHourDiffInMin 		       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $finderid 					       = 	(int) $data['finder_id'];

            $booktrialdata = Booktrial::findOrFail($booktrialid)->toArray();
            $finder = Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();

                        $finder_category_id       = (isset($booktrialdata['finder_category_id']) && $booktrialdata['finder_category_id'] != '') ? $booktrialdata['finder_category_id'] : "";

            $customer_email_messageids  =  $finder_email_messageids  =  $customer_sms_messageids  =  $finer_sms_messageids  =  $customer_notification_messageids  =  array();

            //Send Instant (Email) To Customer & Finder
            if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip'){

                if(isset($booktrialdata['campaign'])){

                    switch($booktrialdata['campaign']){
                        case 'yogaday':
                            // Yogaday campaign EMAIL/SMS.........
                            $sndInstantEmailCustomer      =   $this->customermailer->bookYogaDayTrial($booktrialdata);
                            $sndInstantSmsCustomer        =   $this->customersms->bookYogaDayTrial($booktrialdata);
                            break;
                        default:
                            break;
                    }
                }

                // Normal flow.........
                !isset($sndInstantEmailCustomer) ?  $sndInstantEmailCustomer = $this->customermailer->bookTrial($booktrialdata) : array();
                !isset($sndInstantSmsCustomer) ? $sndInstantSmsCustomer =   $this->customersms->bookTrial($booktrialdata) : array();
                // Send Email to Customer........
                $customer_email_messageids['instant']   =   $sndInstantEmailCustomer;
                $customer_sms_messageids['instant']     =   $sndInstantSmsCustomer;
            }

            if(isset($booktrialdata['campaign'])) {
                switch ($booktrialdata['campaign']) {
                    case 'yogaday':
                        // Yogaday campaign EMAIL/SMS.........
                        $sndInstantEmailFinder = $this->findermailer->bookYogaDayTrial($booktrialdata);
                        $sndInstantSmsFinder = $this->findersms->bookYogaDayTrial($booktrialdata);
                        break;
                    default:
                        break;
                }
            }

            !isset($sndInstantEmailFinder) ?  $sndInstantEmailFinder = $this->findermailer->bookTrial($booktrialdata) : array();
            !isset($sndInstantSmsFinder) ? $sndInstantSmsFinder =   $this->findersms->bookTrial($booktrialdata) : array();
            $finder_email_messageids['instant']     =   $sndInstantEmailFinder;
            $finer_sms_messageids['instant']        =   $sndInstantSmsFinder;

            //ozonetel outbound calls
            if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                $customer_auto_sms = $this->autoSms($booktrialdata,$schedule_date_starttime);
            }

            //Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
            if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {
                if($twelveHourDiffInMin >= (12 * 60)){
                    // if($finder_category_id != 41){
                        $sndBefore12HourEmailCustomer		       = 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
                        $customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
                    // }

                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                        $customer_notification_messageids['before12hour'] = $this->customernotification->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
                    }

                }else{
                    // if($finder_category_id != 41){
                        $sndBefore12HourEmailCustomer		       = 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
                        $customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
                    // }

                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                        $customer_notification_messageids['before12hour'] = $this->customernotification->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
                    }

                }
            }

            //Send Reminder Notiication (Sms) Before 1 Hour To Customer
            if($oneHourDiffInMin >= 60){

                $sndBefore1HourSmsFinder			       =	$this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                $finer_sms_messageids['before1hour']        = 	$sndBefore1HourSmsFinder;

                if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {

                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                        $customer_notification_messageids['before1hour'] = $this->customernotification->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }else{
                        $customer_sms_messageids['before1hour'] = $this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }
                }

            }

            //Send Post Trial Notificaiton After 2 Hours Need to Write
             if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {

                if($booktrialdata['type'] == '3daystrial'){

                    $customer_sms_messageids['after2hour'] = $this->customersms->reminderAfter2Hour3DaysTrial($booktrialdata, $delayReminderTimeAfter2Hour);

                    $customer_email_messageids['after50hour'] = $this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter50Hour);
                    
                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){  
                        $customer_notification_messageids['after50hour'] = $this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter50Hour);
                    }else{
                        $customer_sms_messageids['after50hour'] = $this->missedCallReview($booktrialdata, $delayReminderTimeAfter50Hour);
                    }

                }else{

                    if($booktrialdata['type'] != "workout-session"){
                        $sndAfter2HourEmailCustomer                         =   $this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
                        $customer_email_messageids['after2hour']            =   $sndAfter2HourEmailCustomer;
                        
                        if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){  
                            $customer_notification_messageids['after2hour'] = $this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
                        }else{
                            $customer_sms_messageids['after2hour'] = $this->missedCallReview($booktrialdata, $delayReminderTimeAfter2Hour);
                        }
                    }

                }

            }//cleartrip


            //update queue ids for booktiral
            $queueddata 	= 	array('customer_emailqueuedids' => $customer_email_messageids,
                'customer_smsqueuedids' => $customer_sms_messageids,
                'customer_notification_messageids' => $customer_notification_messageids,
                'finder_emailqueuedids' => $finder_email_messageids,
                'finder_smsqueuedids' => $finer_sms_messageids,
                'customer_auto_sms' => $customer_auto_sms
            );

            $booktrial        = 	Booktrial::findOrFail($booktrialid);

            $fitness_force  = 	$this->fitnessforce->createAppointment(['booktrial'=>$booktrial,'finder'=>$finder]);

            if($fitness_force){
                if($fitness_force['status'] == 200){
                    $queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
                    $queueddata['fitness_force_appointment']['status'] = 200;
                    $queueddata['fitness_force_appointment'] = $fitness_force['data'];
                }else{
                    $queueddata['fitness_force_appointment'] = $fitness_force;
                }
            }

            $trialbooked = $booktrial->update($queueddata);

        }catch(\Exception $exception){
            Log::error($exception);
        }

    }


    public function rescheduledBookTrial(){

        $data = Input::json()->all();

        if(!isset($data['booktrial_id']) || $data['booktrial_id'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - booktrial_id");
            return  Response::json($resp, 400);
        }

        if(!isset($data['customer_name']) || $data['customer_name'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_name");
            return  Response::json($resp, 400);
        }

        if(!isset($data['customer_email']) || $data['customer_email'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
            return  Response::json($resp, 400);
        }

        if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
            $resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
            return  Response::json($resp, 400);
        }

        if(!isset($data['customer_phone']) || $data['customer_phone'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_phone");
            return  Response::json($resp, 400);
        }

        if(!isset($data['finder_id']) || $data['finder_id'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
            return  Response::json($resp, 400);
        }

        if(!isset($data['service_name']) || $data['service_name'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
            return  Response::json($resp, 400);
        }

        if(!isset($data['schedule_date']) || $data['schedule_date'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_date");
            return  Response::json($resp, 400);
        }

        if(!isset($data['schedule_slot']) || $data['schedule_slot'] == ''){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
            return  Response::json($resp, 400);
        }


        try {

            $id        = 	(int) $data['booktrial_id'];
            $booktrial 	= 	Booktrial::findOrFail($id);
            $old_going_status	 				       =	(isset($booktrial->going_status) && $booktrial->going_status != '') ? $booktrial->going_status : "";
            $old_schedule_date	 				       =	(isset($booktrial->schedule_date) && $booktrial->schedule_date != '') ? $booktrial->schedule_date : "";
            $old_schedule_slot_start_time	 	       =	(isset($booktrial->schedule_slot_start_time) && $booktrial->schedule_slot_start_time != '') ? $booktrial->schedule_slot_start_time : "";
            $old_schedule_slot_end_time	 		       =	(isset($booktrial->schedule_slot_end_time) && $booktrial->schedule_slot_end_time != '') ? $booktrial->schedule_slot_end_time : "";


            $service_id	 				       =	(isset($data['service_id']) && $data['service_id'] != '') ? intval($data['service_id']) : "";
            $campaign	 				       =	(isset($data['campaign']) && $data['campaign'] != '') ? $data['campaign'] : "";
            $send_alert	 				       =	true;

            $update_only_info	 		       =	'';
            $send_post_reminder_communication	=	(isset($data['send_post_reminder_communication']) && $data['send_post_reminder_communication'] != '') ? $data['send_post_reminder_communication'] : "";
            $send_purchase_communication       =	(isset($data['send_purchase_communication']) && $data['send_purchase_communication'] != '') ? $data['send_purchase_communication'] : "";
            $deadbooktrial				       =	(isset($data['deadbooktrial']) && $data['deadbooktrial'] != '') ? $data['deadbooktrial'] : "";
            $note_to_trainer                    =   (isset($data['note_to_trainer']) && $data['note_to_trainer'] != '') ? $data['note_to_trainer'] : "";
            $reason                             =   (isset($data['reason']) && $data['reason'] != '') ? $data['reason'] : "";

            //its helpful to send any kind for dateformat date time as srting or iso formate timezond
            $slot_times 				       =	explode('-',$data['schedule_slot']);
            $schedule_slot_start_time 	       =	$slot_times[0];
            $schedule_slot_end_time 	       =	$slot_times[1];
            $schedule_slot 				       =	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

            $slot_date 					       =	date('d-m-Y', strtotime($data['schedule_date']));
            $schedule_date_starttime 	       =	strtoupper($slot_date ." ".$schedule_slot_start_time);
            $currentDateTime 			       =	Carbon::now();
            $scheduleDateTime 			       =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
            $delayReminderTimeBefore1Min        =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(1);
            $delayReminderTimeBefore1Hour        =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
            $delayReminderTimeBefore12Hour       =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
            $delayReminderTimeAfter2Hour       =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(120);
            $delayReminderTimeAfter50Hour       =   Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 50);
            $reminderTimeAfter1Hour 	       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(60);
            $oneHourDiff 				       = 	$currentDateTime->diffInHours($scheduleDateTime, false);
            $twelveHourDiff 			       = 	$currentDateTime->diffInHours($scheduleDateTime, false);
            $oneHourDiffInMin 			       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $twelveHourDiffInMin 		       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);

            $booktrialid 				       =	(int) $data['booktrial_id'];
            $finderid 					       = 	(int) $data['finder_id'];
            $finder 					       = 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();

            $customer_id 				       =	$data['customer_id'];
            $customer_name 				       =	$data['customer_name'];
            $customer_email 			       =	$data['customer_email'];
            $customer_phone 			       =	preg_replace("/[^0-9]/", "",$data['customer_phone']);

            $finder_name				       = 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
            $finder_slug				       = 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
            $finder_lat 				       = 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon 				       = 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";

            $google_pin					       =	$this->googlePin($finder_lat,$finder_lon);

            $city_id 					       =	(int) $finder['city_id'];

            $otp	 					       =	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";

            $gcm_reg_id					       = 	(isset($data['gcm_reg_id']) && $data['gcm_reg_id'] != '') ? $data['gcm_reg_id'] : "";
            $device_type				       = 	(isset($data['device_type']) && $data['device_type'] != '') ? $data['device_type'] : "";

            $finder_category_id			       = 	(isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";

            $description =  $what_i_should_carry = $what_i_should_expect = '';
            if($service_id != ''){
                $serviceArr 				       = 	Service::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('category')->with('subcategory')->where('_id','=', intval($service_id))->first()->toArray();

                if((isset($serviceArr['category']['description']) && $serviceArr['category']['description'] != '')){
                    $description = $serviceArr['category']['description'];
                }else{
                    if((isset($serviceArr['subcategory']['description']) && $serviceArr['subcategory']['description'] != '')){
                        $description = $serviceArr['subcategory']['description'];
                    }
                }

                if((isset($serviceArr['category']['what_i_should_carry']) && $serviceArr['category']['what_i_should_carry'] != '')){
                    $what_i_should_carry = $serviceArr['category']['what_i_should_carry'];
                }else{
                    if((isset($serviceArr['subcategory']['what_i_should_carry']) && $serviceArr['subcategory']['what_i_should_carry'] != '')){
                        $what_i_should_carry = $serviceArr['subcategory']['what_i_should_carry'];
                    }
                }

                if((isset($serviceArr['category']['what_i_should_expect']) && $serviceArr['category']['what_i_should_expect'] != '')){
                    $what_i_should_expect = $serviceArr['category']['what_i_should_expect'];
                }else{
                    if((isset($serviceArr['subcategory']['what_i_should_expect']) && $serviceArr['subcategory']['what_i_should_expect'] != '')){
                        $what_i_should_expect = $serviceArr['subcategory']['what_i_should_expect'];
                    }
                }


                if((isset($serviceArr['location']['name']) && $serviceArr['location']['name'] != '')){
                    $finder_location			       =	$serviceArr['location']['name'];
                    $show_location_flag 		       =   true;
                }else{
                    $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
                    $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;
                }
                if((isset($serviceArr['address']) && $serviceArr['address'] != '')){
                    $finder_address				       = 	$serviceArr['address'];
                }else{
                    $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
                }
            }else{
                $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
                $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
                $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;
            }

            $finder_lat		       =	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon		       =	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $finder_photos	       = 	[];
            if(isset($finder['photos']) && count($finder['photos']) > 0){
                foreach ($finder['photos'] as $key => $value) {
                    if($key > 2){ continue; }
                    array_push($finder_photos, Config::get('app.s3_finder_url').'g/full/'.$value['url']);
                }
            }

            $finder_vcc_email = "";
            if(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != ''){
                $explode = explode(',', $finder['finder_vcc_email']);
                $valid_finder_email = [];
                foreach ($explode as $email) {
                    if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
                        $valid_finder_email[] = $email;
                    }
                }
                if(!empty($valid_finder_email)){
                    $finder_vcc_email = implode(",", $valid_finder_email);
                }
            }

            $finder_vcc_mobile			       = 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
            $finder_poc_for_customer_name       = 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
            $finder_poc_for_customer_no	       = 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
            $share_customer_no			       = 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;

            $service_name				       =	(isset($data['service_name']) && $data['service_name'] != '') ? strtolower($data['service_name']) : "";
            $service_name_purchase		       =	(isset($data['service_name_purchase']) && $data['service_name_purchase'] != '') ? strtolower($data['service_name_purchase']) : "";
            $service_duration_purchase	       =	(isset($data['service_duration_purchase']) && $data['service_duration_purchase'] != '') ? strtolower($data['service_duration_purchase']) : "";
            $finder_branch				       =	(isset($data['finder_branch']) && $data['finder_branch'] != '') ? strtolower($data['finder_branch']) : "";

            if($update_only_info == ''){
                $schedule_date				       =	date('Y-m-d 00:00:00', strtotime($slot_date));
                $schedule_date_time			       =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();
                $code						       =	random_numbers(5);
            }
            $device_id					       = 	(Input::has('device_id') && $data['device_id'] != '') ? $data['device_id'] : "";
            $followup_date 				       =	(isset($data['followup_date']) && $data['followup_date'] != '') ? date('Y-m-d 00:00:00', strtotime($data['followup_date'])) : '';
            $followup_time 				       =	(isset($data['followup_time']) && $data['followup_time'] != '') ? $data['followup_time'] : '';
            $followup_date_time			       =	'';

            $menmbership_bought			       =	(isset($data['menmbership_bought']) && $data['menmbership_bought'] != '') ? strtolower($data['menmbership_bought']) : "";
            $amount						       =	(isset($data['amount']) && $data['amount'] != '') ? intval($data['amount']) : "";
            $amount_finder				       =	(isset($data['amount_finder']) && $data['amount_finder'] != '') ? intval($data['amount_finder']) : "";
            $paid_trial_amount			       =	(isset($data['paid_trial_amount']) && $data['paid_trial_amount'] != '') ? intval($data['paid_trial_amount']) : "";
            $premium_session 			       =	(boolean) $data['premium_session'];
            $booktrial_actions 			       =	(isset($data['booktrial_actions']) && $data['booktrial_actions'] != '') ? $data['booktrial_actions'] : "";
            $person_followingup 		       =	(isset($data['person_followingup']) && $data['person_followingup'] != '') ? $data['person_followingup'] : "";
            $remarks 					       =	(isset($data['remarks']) && $data['remarks'] != '') ? $data['remarks'] : "";
            $feedback_about_trial 		       =	(isset($data['feedback_about_trial']) && $data['feedback_about_trial'] != '') ? $data['feedback_about_trial'] : "";

            $post_trial_status 			       =	(isset($data['post_trial_status']) && $data['post_trial_status'] != '') ? $data['post_trial_status'] : "";
            $post_reminder_status 		       =	(isset($data['post_reminder_status']) && $data['post_reminder_status'] != '') ? $data['post_reminder_status'] : "";
            $reminder_need_status 		       =	(isset($data['reminder_need_status']) && $data['reminder_need_status'] != '') ? $data['reminder_need_status'] : "";
            $membership_bought_at 		       =	(isset($data['membership_bought_at']) && $data['membership_bought_at'] != '') ? $data['membership_bought_at'] : "";

            $final_lead_stage = 'trial_stage';
            $final_lead_status = 'rescheduled';

            $booktrialdata = array(
                'booktrialid' 			       =>		$booktrialid,
                'menmbership_bought' 	       =>		$menmbership_bought,

                'campaign'				       =>		$campaign,
                'service_id'			       =>		$service_id,
                'service_name' 			       =>		$service_name,
                'service_name_purchase'        =>		$service_name_purchase,
                'service_duration_purchase' 	=>		$service_duration_purchase,
                'finder_branch' 		       =>		$finder_branch,

                'amount' 				       =>		$amount,
                'amount_finder' 		       =>		$amount_finder,
                'paid_trial_amount' 	       =>		$paid_trial_amount,
                'premium_session' 		       =>		$premium_session,
                'booktrial_actions' 	       =>		$booktrial_actions,
                'person_followingup' 	       =>		$person_followingup,
                'remarks' 				       =>		$remarks,
                'feedback_about_trial' 	       =>		$feedback_about_trial,
                'post_trial_status' 	       =>		$post_trial_status,
                'post_reminder_status' 	       =>		$post_reminder_status,
                'reminder_need_status' 	       =>		$reminder_need_status,
                'membership_bought_at' 	       =>		$membership_bought_at,

                'followup_date' 		       =>		$followup_date,
                'followup_time' 		       =>		$followup_time,
                'followup_date_time' 	       =>		$followup_date_time,

                'customer_id' 			       =>		$customer_id,
                'customer_name' 		       =>		$customer_name,
                'customer_email' 		       =>		$customer_email,
                'customer_phone' 		       =>		$customer_phone,

                'finder_id' 			       =>		$finderid,
                'finder_name' 			       =>		$finder_name,
                'finder_slug' 			       =>		$finder_slug,
                'finder_location' 		       =>		$finder_location,
                'finder_address' 		       =>		$finder_address,
                'finder_lat'		 	       =>		$finder_lat,
                'finder_lon'		 	       =>		$finder_lon,
                'finder_photos'		 	       =>		$finder_photos,
                'what_i_should_carry'		 	=>		$what_i_should_carry,
                'what_i_should_expect'		 	=>		$what_i_should_expect,

                'city_id'				       =>		$city_id,
                'finder_vcc_email' 		       =>		$finder_vcc_email,
                'finder_vcc_mobile' 	       =>		$finder_vcc_mobile,
                'finder_poc_for_customer_name'	=>		$finder_poc_for_customer_name,
                'finder_poc_for_customer_no'	=>		$finder_poc_for_customer_no,
                'show_location_flag'	       => 		$show_location_flag,
                'share_customer_no'		       => 		$share_customer_no,
                'device_id'				       =>		$device_id,
                'otp'					       => 		$otp,
                'source_flag'			       => 		'customer',
                'finder_category_id'	       =>		$finder_category_id,

                'final_lead_stage'		       =>		$final_lead_stage,
                'final_lead_status'		       =>		$final_lead_status,
                'reg_id'				       => 		$gcm_reg_id,
                'device_type'			       => 		$device_type,
                'google_pin'			       =>		$google_pin,
                'note_to_trainer'               =>      $note_to_trainer
            );


            if($update_only_info == ''){
                array_set($booktrialdata, 'schedule_slot_start_time', $schedule_slot_start_time);
                array_set($booktrialdata, 'schedule_slot_end_time', $schedule_slot_end_time);
                array_set($booktrialdata, 'schedule_date', $schedule_date);
                array_set($booktrialdata, 'schedule_date_time', $schedule_date_time);
                array_set($booktrialdata, 'schedule_slot', $schedule_slot);
                array_set($booktrialdata, 'code', $code);
                array_set($booktrialdata, 'going_status', 1);
                array_set($booktrialdata, 'going_status_txt', 'rescheduled');
                array_set($booktrialdata, 'booktrial_type', 'auto');
            }

            $trialbooked = $booktrial->update($booktrialdata);

            $payload = array(
                'booktrialid'=>$booktrialid,
                'send_alert'=>$send_alert,
                'update_only_info'=>$update_only_info,
                'send_post_reminder_communication'=>$send_post_reminder_communication,
                'booktrialdata'=>$booktrialdata,
                'delayReminderTimeBefore12Hour'=>$delayReminderTimeBefore12Hour,
                'twelveHourDiffInMin'=>$twelveHourDiffInMin,
                'oneHourDiffInMin'=>$oneHourDiffInMin,
                'delayReminderTimeBefore1Hour'=>$delayReminderTimeBefore1Hour,
                'delayReminderTimeAfter2Hour'=>$delayReminderTimeAfter2Hour,
                'delayReminderTimeAfter50Hour'=>$delayReminderTimeAfter50Hour,
                'reminderTimeAfter1Hour'=> $reminderTimeAfter1Hour,
                'finder'=>$finder,
                'old_going_status'=>$old_going_status,
                'old_schedule_date'=>$old_schedule_date,
                'old_schedule_slot_start_time'=>$old_schedule_slot_start_time,
                'old_schedule_slot_end_time'=>$old_schedule_slot_end_time
            );

            $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueRescheduledBookTrial',$payload, 'booktrialv2');
            $booktrial->update(array('reschedule_redis_id'=>$redisid));

            $resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'message' => "Rescheduled Trial");
            return Response::json($resp,200);

        } catch(ValidationException $e){

            return array('status' => 500,'message' => $e->getMessage());
        }

    }

    public function toQueueRescheduledBookTrial($job,$data){

        $job->delete();

        try{

            $booktrialid = (int) $data['booktrialid'];
            $send_alert = $data['send_alert'];
            $update_only_info = $data['update_only_info'];
            $send_post_reminder_communication = $data['send_post_reminder_communication'];
            $booktrialdata = Booktrial::find($booktrialid)->toArray();//$data['booktrialdata'];
            $delayReminderTimeBefore12Hour = $data['delayReminderTimeBefore12Hour'];
            $twelveHourDiffInMin = $data['twelveHourDiffInMin'];
            $oneHourDiffInMin = $data['oneHourDiffInMin'];
            $delayReminderTimeBefore1Hour = $data['delayReminderTimeBefore1Hour'];
            $delayReminderTimeAfter2Hour = $data['delayReminderTimeAfter2Hour'];
            $delayReminderTimeAfter50Hour = $data['delayReminderTimeAfter50Hour'];
            $reminderTimeAfter1Hour = $data['reminderTimeAfter1Hour'];
            $finder = $data['finder'];
            $old_going_status = $data['old_going_status'];
            $old_schedule_date = $data['old_schedule_date'];
            $old_schedule_slot_start_time = $data['old_schedule_slot_start_time'];
            $old_schedule_slot_end_time = $data['old_schedule_slot_end_time'];

            $booktrial = Booktrial::find($booktrialid);

            $finder_category_id 		       = (isset($booktrialdata['finder_category_id']) && $booktrialdata['finder_category_id'] != '') ? $booktrialdata['finder_category_id'] : "";

            //hit fitness force api start here
            if(isset($finder['fitnessforce_key']) && $finder['fitnessforce_key'] != ''){
                if($old_going_status == 6){
                    $this->bookTrialFintnessForce ($booktrial,$finder);
                }elseif($old_schedule_date != $booktrial->schedule_date || $old_schedule_slot_start_time != $booktrial->schedule_slot_start_time || $old_schedule_slot_start_time != $booktrial->schedule_slot_end_time && isset($booktrial->fitness_force_appointment['appointmentbooktrialid']) && $booktrial->fitness_force_appointment['appointmentid'] != ''){

                	try {
                        $this->updateBookTrialFintnessForce($id);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
  
                }
            }

            if($send_alert != '' && $update_only_info == ''){
                if((isset($booktrial->customer_emailqueuedids['before12hour']) && $booktrial->customer_emailqueuedids['before12hour'] != '')){

                    try {
                        $this->sidekiq->delete($booktrial->customer_emailqueuedids['before12hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }

                if((isset($booktrial->customer_emailqueuedids['after2hour']) && $booktrial->customer_emailqueuedids['after2hour'] != '')){

                    try {
                        $this->sidekiq->delete($booktrial->customer_emailqueuedids['after2hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }

                }

                if((isset($booktrial->customer_emailqueuedids['after50hour']) && $booktrial->customer_emailqueuedids['after50hour'] != '')){

                    try {
                        $this->sidekiq->delete($booktrial->customer_emailqueuedids['after50hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }

                }

                if((isset($booktrial->customer_smsqueuedids['before1hour']) && $booktrial->customer_smsqueuedids['before1hour'] != '')){

                    try {
                        $this->sidekiq->delete($booktrial->customer_smsqueuedids['before1hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }

                if((isset($booktrial->customer_smsqueuedids['after2hour']) && $booktrial->customer_smsqueuedids['after2hour'] != '')){

                    try {
                        $this->sidekiq->delete($booktrial->customer_smsqueuedids['after2hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }

                if((isset($booktrial->customer_smsqueuedids['after50hour']) && $booktrial->customer_smsqueuedids['after50hour'] != '')){

                    try {
                        $this->sidekiq->delete($booktrial->customer_smsqueuedids['after50hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }

                if((isset($booktrial->finder_smsqueuedids['before1hour']) && $booktrial->finder_smsqueuedids['before1hour'] != '')){

                    try {
                        $this->sidekiq->delete($booktrial->finder_smsqueuedids['before1hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }

                if(isset($booktrial->customer_auto_sms) && $booktrial->customer_auto_sms != '' && $booktrial->customer_auto_sms != 'no_auto_sms'){

                    try {
                        $this->sidekiq->delete($booktrial->customer_auto_sms);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }

                if((isset($booktrial->customer_notification_messageids['before12hour']) && $booktrial->customer_notification_messageids['before12hour'] != '')){

                    try{
                        $this->sidekiq->delete($booktrial->customer_notification_messageids['before12hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }

                }

                if((isset($booktrial->customer_notification_messageids['before1hour']) && $booktrial->customer_notification_messageids['before1hour'] != '')){

                    try{
                        $this->sidekiq->delete($booktrial->customer_notification_messageids['before1hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }

                }

                if((isset($booktrial->customer_notification_messageids['after2hour']) && $booktrial->customer_notification_messageids['after2hour'] != '')){

                    try{
                        $this->sidekiq->delete($booktrial->customer_notification_messageids['after2hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }

                }

                if((isset($booktrial->customer_notification_messageids['after50hour']) && $booktrial->customer_notification_messageids['after50hour'] != '')){

                    try{
                        $this->sidekiq->delete($booktrial->customer_notification_messageids['after50hour']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }

                }

            }


            if($send_post_reminder_communication != '' && $update_only_info == ''){
                $sndInstantPostReminderStatusSmsFinder	=	$this->findersms->postReminderStatusSmsFinder($booktrialdata);
            }


            if($send_alert != '' && $update_only_info == ''){

                $customer_email_messageids 	=  $finder_email_messageids  =	$customer_sms_messageids  =  $finer_sms_messageids  = $customer_notification_messageids = array();

                //Send Instant (Email) To Customer & Finder
                $sndInstantEmailCustomer		       = 	$this->customermailer->rescheduledBookTrial($booktrialdata);
                $sndInstantSmsCustomer			       =	$this->customersms->rescheduledBookTrial($booktrialdata);
                $sndInstantEmailFinder			       = 	$this->findermailer->rescheduledBookTrial($booktrialdata);
                $sndInstantSmsFinder			       =	$this->findersms->rescheduledBookTrial($booktrialdata);

                $customer_email_messageids['instant'] 	= 	$sndInstantEmailCustomer;
                $customer_sms_messageids['instant'] 	= 	$sndInstantSmsCustomer;
                $finder_email_messageids['instant'] 	= 	$sndInstantEmailFinder;
                $finer_sms_messageids['instant']        = 	$sndInstantSmsFinder;

                //Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer
                if($twelveHourDiffInMin >= (12 * 60)){
                    // if($finder_category_id != 41){
                        $sndBefore12HourEmailCustomer		       = 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
                        $customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
                    // }

                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                        $customer_notification_messageids['before12hour'] = $this->customernotification->bookTrialReminderBefore12Hour($booktrialdata, $delayReminderTimeBefore12Hour);
                    }

                }else{
                    // if($finder_category_id != 41){
                        $sndBefore12HourEmailCustomer		       = 	$this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
                        $customer_email_messageids['before12hour'] 	= 	$sndBefore12HourEmailCustomer;
                    // }

                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                        $customer_notification_messageids['before12hour'] = $this->customernotification->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfter1Hour);
                    }

                }

                //Send Reminder Notiication (Sms) Before 1 Hour To Customer
                if($oneHourDiffInMin >= 60){

                    $sndBefore1HourSmsFinder			       =	$this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    $finer_sms_messageids['before1hour']        = 	$sndBefore1HourSmsFinder;

                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                        $customer_notification_messageids['before1hour'] = $this->customernotification->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }else{
                        $customer_sms_messageids['before1hour'] = $this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }

                }

                //Send Post Trial Notificaiton After 2 Hours Need to Write
                if($booktrialdata['type'] == '3daystrial'){

                    $customer_sms_messageids['after2hour'] = $this->customersms->reminderAfter2Hour3DaysTrial($booktrialdata, $delayReminderTimeAfter2Hour);

                    $customer_email_messageids['after50hour'] = $this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter50Hour);
                    
                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){  
                        $customer_notification_messageids['after50hour'] = $this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter50Hour);
                    }else{
                        $customer_sms_messageids['after50hour'] = $this->missedCallReview($booktrialdata, $delayReminderTimeAfter50Hour);
                    }

                }else{

                    if($booktrialdata['type'] != "workout-session"){
                        $sndAfter2HourEmailCustomer                         =   $this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
                        $customer_email_messageids['after2hour']            =   $sndAfter2HourEmailCustomer;
                        
                        if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){  
                            $customer_notification_messageids['after2hour'] = $this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hour);
                        }else{
                            $customer_sms_messageids['after2hour'] = $this->missedCallReview($booktrialdata, $delayReminderTimeAfter2Hour);
                        }
                    }

                }


                //update queue ids for booktiral
                $booktrial        = 	Booktrial::findOrFail($booktrialid);
                $queueddata 	= 	array('customer_emailqueuedids' => $customer_email_messageids,
                    'customer_notification_messageids' => $customer_notification_messageids,
                    'customer_smsqueuedids' => $customer_sms_messageids,
                    'finder_emailqueuedids' => $finder_email_messageids,
                    'finder_smsqueuedids' => $finer_sms_messageids);

                $booktrial->update($queueddata);

            }

        }catch(\Exception $exception){
            Log::error($exception);
        }

    }


    public function cancel($id){

        $id 		       = 	(int) $id;
        $bookdata 	       = 	array();
        $booktrial 	       = 	Booktrial::findOrFail($id);

        if(isset($booktrial->final_lead_stage) && $booktrial->final_lead_stage == 'cancel_stage'){

            $resp 	= 	array('status' => 200, 'message' => "Trial Canceled Repeat");
            return Response::json($resp,200);
        }

        array_set($bookdata, 'going_status', 2);
        array_set($bookdata, 'going_status_txt', 'cancel');
        array_set($bookdata, 'booktrial_actions', '');
        array_set($bookdata, 'followup_date', '');
        array_set($bookdata, 'followup_date_time', '');
        array_set($bookdata, 'source_flag', 'customer');
        array_set($bookdata, 'final_lead_stage', 'cancel_stage');
        array_set($bookdata, 'cancel_by', 'customer');
        $trialbooked        = 	$booktrial->update($bookdata);

        if($trialbooked == true ){

            $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueBookTrialCancel', array('id'=>$id), 'booktrialv2');
            $booktrial->update(array('cancel_redis_id'=>$redisid));

            $resp 	= 	array('status' => 200, 'message' => "Trial Canceled");
            return Response::json($resp,200);

        }else{

            $resp 	= 	array('status' => 400, 'message' => "Error");
            return Response::json($resp,400);

        }

    }

    public function toQueueBookTrialCancel($job,$data){

        $job->delete();

        try{

            $id = $data['id'];
            $booktrial = Booktrial::find($id);

            //hit fitness force api to cancel trial
            if(isset($booktrial->fitness_force_appointment['appointmentid']) && $booktrial->fitness_force_appointment['appointmentid'] != ''){

            	try {
                   	$this->cancelBookTrialFintnessForce($id);
                }catch(\Exception $exception){
                    Log::error($exception);
                }      
            }

            if((isset($booktrial->customer_emailqueuedids['before12hour']) && $booktrial->customer_emailqueuedids['before12hour'] != '')){

                try {
                    $this->sidekiq->delete($booktrial->customer_emailqueuedids['before12hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }

            if((isset($booktrial->customer_emailqueuedids['after2hour']) && $booktrial->customer_emailqueuedids['after2hour'] != '')){

                try {
                    $this->sidekiq->delete($booktrial->customer_emailqueuedids['after2hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }

            }

            if((isset($booktrial->customer_emailqueuedids['after50hour']) && $booktrial->customer_emailqueuedids['after50hour'] != '')){

                try {
                    $this->sidekiq->delete($booktrial->customer_emailqueuedids['after50hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }

            }

            if((isset($booktrial->customer_smsqueuedids['before1hour']) && $booktrial->customer_smsqueuedids['before1hour'] != '')){

                try {
                    $this->sidekiq->delete($booktrial->customer_smsqueuedids['before1hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }

            if((isset($booktrial->customer_smsqueuedids['after2hour']) && $booktrial->customer_smsqueuedids['after2hour'] != '')){

                try {
                    $this->sidekiq->delete($booktrial->customer_smsqueuedids['after2hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }

            if((isset($booktrial->customer_smsqueuedids['after50hour']) && $booktrial->customer_smsqueuedids['after50hour'] != '')){

                try {
                    $this->sidekiq->delete($booktrial->customer_smsqueuedids['after50hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }

            if((isset($booktrial->finder_smsqueuedids['before1hour']) && $booktrial->finder_smsqueuedids['before1hour'] != '')){

                try {
                    $this->sidekiq->delete($booktrial->finder_smsqueuedids['before1hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }

            if(isset($booktrial->customer_auto_sms) && $booktrial->customer_auto_sms != '' && $booktrial->customer_auto_sms != 'no_auto_sms'){

                try {
                    $this->sidekiq->delete($booktrial->customer_auto_sms);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }

            if((isset($booktrial->customer_notification_messageids['before12hour']) && $booktrial->customer_notification_messageids['before12hour'] != '')){

                try{
                    $this->sidekiq->delete($booktrial->customer_notification_messageids['before12hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }

            }

            if((isset($booktrial->customer_notification_messageids['before1hour']) && $booktrial->customer_notification_messageids['before1hour'] != '')){

                try{
                    $this->sidekiq->delete($booktrial->customer_notification_messageids['before1hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }

            }

            if((isset($booktrial->customer_notification_messageids['after2hour']) && $booktrial->customer_notification_messageids['after2hour'] != '')){

                try{
                    $this->sidekiq->delete($booktrial->customer_notification_messageids['after2hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }

            }

            if((isset($booktrial->customer_notification_messageids['after50hour']) && $booktrial->customer_notification_messageids['after50hour'] != '')){

                try{
                    $this->sidekiq->delete($booktrial->customer_notification_messageids['after50hour']);
                }catch(\Exception $exception){
                    Log::error($exception);
                }

            }

            $booktrialdata      =	$booktrial;

            $finderid 					       = 	(int) $booktrialdata->finder_id;

            $finder                             =   Finder::with(array('city'=>function($query){$query->select('_id','name','slug');}))->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();


            $finder_name                        =   (isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
            $finder_slug                        =   (isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
            $finder_location                    =   (isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
            $finder_location_slug               =   (isset($finder['location']['slug']) && $finder['location']['slug'] != '') ? $finder['location']['slug'] : "";
            $finder_category                    =   (isset($finder['category']['name']) && $finder['category']['name'] != '') ? $finder['category']['name'] : "";
            $finder_category_slug               =   (isset($finder['category']['slug']) && $finder['category']['slug'] != '') ? $finder['category']['slug'] : "";
            $finder_address                     =   (isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
            $finder_lat                         =   (isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon                         =   (isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $city_id                            =   (int) $finder['city_id'];
            $city                               =   $finder['city']['name'];
            $city_slug                          =   $finder['city']['slug'];
            $google_pin                         =   $this->googlePin($finder_lat,$finder_lon);

            $finder_vcc_email = "";
            if(isset($finder['finder_vcc_email']) && $finder['finder_vcc_email'] != ''){
                $explode = explode(',', $finder['finder_vcc_email']);
                $valid_finder_email = [];
                foreach ($explode as $email) {
                    if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
                        $valid_finder_email[] = $email;
                    }
                }
                if(!empty($valid_finder_email)){
                    $finder_vcc_email = implode(",", $valid_finder_email);
                }
            }

            $finder_vcc_mobile			       = 	(isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";
            $finder_poc_for_customer_name       = 	(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != '') ? $finder['finder_poc_for_customer_name'] : "";
            $finder_poc_for_customer_no	       = 	(isset($finder['finder_poc_for_customer_mobile']) && $finder['finder_poc_for_customer_mobile'] != '') ? $finder['finder_poc_for_customer_mobile'] : "";
            $share_customer_no			       = 	(isset($finder['share_customer_no']) && $finder['share_customer_no'] == '1') ? true : false;
            $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;

            $gcm_reg_id						       = 	(isset($booktrialdata->reg_id) && $booktrialdata->reg_id != '') ? $booktrialdata->reg_id : '';
            $device_type				       = 	(isset($booktrialdata->device_type) && $booktrialdata->device_type != '') ? $booktrialdata->device_type : '';

            if(isset($booktrialdata->customer_id) && $booktrialdata->customer_id != '' && $gcm_reg_id == '' && $device_type == ''){

                $device = Device::where('customer_id',(int)$booktrialdata->customer_id)->orderBy('_id', 'desc')->first();

                if($device){

                    $gcm_reg_id	= $device->reg_id;
                    $device_type = $device->reg_id;
                }
            }

            $image[1] = "http://email.fitternity.com/231/".$finder_category_slug."_1.jpg";
            $image[2] = "http://email.fitternity.com/231/".$finder_category_slug."_2.jpg";

            foreach ($image as $key => $url) {

                $file_headers = @get_headers($image[$key]);

                if ($file_headers[0] != "HTTP/1.1 200 OK") {
                    $image[$key] = "http://email.fitternity.com/231/default_".$key."jpg";
                }
            }

            $emaildata = array(
                'customer_name'                 =>      $booktrialdata->customer_name,
                'customer_email'                =>      $booktrialdata->customer_email,
                'customer_phone'                =>      $booktrialdata->customer_phone,

                'finder_id'                     =>      $finderid,
                'finder_name'                   =>      $finder_name,
                'finder_slug'                   =>      $finder_slug,
                'finder_location'               =>      $finder_location,
                'finder_location_slug'          =>      $finder_location_slug,
                'finder_category'               =>      $finder_category,
                'finder_category_slug'          =>      $finder_category_slug,
                'finder_address'                =>      $finder_address,
                'finder_lat'                    =>      $finder_lat,
                'finder_lon'                    =>      $finder_lon,
                'city_id'                       =>      $city_id,
                'city'                          =>      $city,
                'city_slug'                     =>      $city_slug,
                'finder_vcc_email'              =>      $finder_vcc_email,
                'finder_vcc_mobile'             =>      $finder_vcc_mobile,
                'finder_poc_for_customer_name'  =>      $finder_poc_for_customer_name,
                'finder_poc_for_customer_no'    =>      $finder_poc_for_customer_no,
                'show_location_flag'            =>      $show_location_flag,
                'share_customer_no'             =>      $share_customer_no,

                'service_name'                  =>      $booktrialdata->service_name,
                'schedule_slot_start_time'      =>      $booktrialdata->schedule_slot_start_time,
                'schedule_slot_end_time'        =>      $booktrialdata->schedule_slot_end_time,
                'schedule_date'                 =>      $booktrialdata->schedule_date,
                'schedule_date_time'            =>      $booktrialdata->schedule_date_time,
                'schedule_slot'                 =>      $booktrialdata->schedule_slot,

                'code'                          =>      $booktrialdata->code,
                'booktrial_actions'             =>      "",
                'followup_date'                 =>      "",
                'followup_date_time'            =>      "",
                'reg_id'                        =>      $gcm_reg_id,
                'device_type'                   =>      $device_type,
                'type'                          =>      $booktrialdata->type,
                'google_pin'                    =>      $google_pin,
                'cancel_by'                     =>      (isset($booktrialdata->cancel_by) && $booktrialdata->cancel_by != '') ? $booktrialdata->cancel_by : '',
                'image'                         =>      $image,
                'source'                        =>      $booktrialdata->source
            );

            $this->findermailer->cancelBookTrial($emaildata);
            $this->findersms->cancelBookTrial($emaildata);

            if(isset($booktrialdata->source) && $booktrialdata->source != 'cleartrip'){
                $this->customermailer->cancelBookTrial($emaildata);
                if($emaildata['reg_id'] != '' && $emaildata['device_type'] != ''){
                    $this->customernotification->cancelBookTrial($emaildata);
                }else{
                    $this->customersms->cancelBookTrial($emaildata);
                }
            }


        }catch(\Exception $exception){

            Log::error($exception);
        }

    }

    public function bookTrialFintnessForce($booktrial,$finder){

        $fitness_force  = 	$this->fitnessforce->createAppointment(['booktrial'=>$booktrial,'finder'=>$finder]);

        if($fitness_force){
            if($fitness_force['status'] == 200){
                $queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
                $queueddata['fitness_force_appointment'] = $fitness_force['data'];
            }else{
                $queueddata['fitness_force_appointment'] = $fitness_force;
            }
        }

        return $booktrial->update($queueddata);
    }

    public function cancelBookTrialFintnessForce($id){

        $booktrial = Booktrial::with('finder')->where('_id','=',$id)->first();
        $fitness_force  = 	$this->fitnessforce->cancelAppointment($booktrial);

        if($fitness_force){
            if($fitness_force['status'] == 200){
                $queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
                $queueddata['fitness_force_appointment'] = $booktrial->fitness_force_appointment;
                $queueddata['fitness_force_appointment']['appointmentstatus'] = $fitness_force['data']['appointmentstatus'];
            }else{
                $queueddata['fitness_force_appointment_cancel'] = $fitness_force;
            }
        }

        return $booktrial->update($queueddata);
    }



    public function updateBookTrialFintnessForce($id){

        $booktrial = Booktrial::with('finder')->where('_id','=',$id)->first();
        $fitness_force  = 	$this->fitnessforce->updateAppointment($booktrial);

        if($fitness_force){
            if($fitness_force['status'] == 200){
                $queueddata['fitness_force_appointment_status'] = strtolower($fitness_force['data']['appointmentstatus']);
                $queueddata['fitness_force_appointment'] = $booktrial->fitness_force_appointment;
                $queueddata['fitness_force_appointment']['appointmentid'] = $fitness_force['data']['appointmentid'];
                $queueddata['fitness_force_appointment']['status'] = $fitness_force['data']['status'];
                $queueddata['fitness_force_appointment']['appointmentstatus'] = $fitness_force['data']['appointmentstatus'];
                $queueddata['fitness_force_appointment']['appointmentwith'] = $fitness_force['data']['appointmentwith'];
                $queueddata['fitness_force_appointment']['startdate'] = $fitness_force['data']['startdate'];
                $queueddata['fitness_force_appointment']['enddate'] = $fitness_force['data']['enddate'];
                $queueddata['fitness_force_appointment']['starttime'] = $fitness_force['data']['starttime'];
                $queueddata['fitness_force_appointment']['endtime'] = $fitness_force['data']['endtime'];
            }else{
                $queueddata['fitness_force_appointment_update'] = $fitness_force;
            }
        }

        return $booktrial->update($queueddata);
    }



    public function attachTrialCampaignToCustomer($cid, $campaign, $trialid = ''){

        $data        = [];
        $customer 	= Customer::find(intval($cid));
        if($campaign == 'uber' && $trialid != ''){
            $data['uber_trials'] = $customer->uber_trials.','.$trialid;
        }

        return $customer->update($data);
    }

    public function deleteTask($id){

        $cancel = Schedulerjob::where('_id',(int)$id)->update(array('status'=>'cancel'));

        return $cancel;

    }

    public function autoSms($booktrialdata,$schedule_date_starttime){

        $created_date =  strtotime($booktrialdata['created_at']);
        $schedule_date = \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

        $created_sec = $created_date;
        $scheduled_sec = strtotime($schedule_date);
        $diff_sec = (int) ($scheduled_sec - $created_sec) ;
        $hour = (int) date("G", strtotime($schedule_date));
        $min = (int) date("i", strtotime($schedule_date));
        $hour2 = 60*60*2;
        $hour3 = 60*60*3;

        if($hour >= 11 && $hour <= 22){

            if($diff_sec >= $hour3){

                $booktrial = Booktrial::find((int) $booktrialdata['_id']);
                $booktrial->update(array('outbound_sms_status'=>'1'));

                $ozonetel_date = date("Y-m-d H:i:s", strtotime($schedule_date . "-3 hours"));

                Log::info('ozonetel_date  -- '. $ozonetel_date);

                return $this->missedCall($booktrialdata,$ozonetel_date);

            }

        }

        if($hour >= 6 && $hour <= 10){

            $booktrial = Booktrial::find((int) $booktrialdata['_id']);
            $booktrial->update(array('outbound_sms_status'=>'1'));

            $ozonetel_date = date("Y-m-d 21:00:00", strtotime($schedule_date . "-1 days"));
            $ozonetel_date_sec = strtotime($ozonetel_date);

            if($ozonetel_date_sec > $created_sec){

                Log::info('ozonetel_date  -- '. $ozonetel_date);

                return $this->missedCall($booktrialdata,$ozonetel_date);
            }

        }

        return 'no_auto_sms';
    }

    public function missedCall($data,$ozonetel_date){

        $current_date = date('Y-m-d 00:00:00');

        $from_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date))));
        $to_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date." + 1 days"))));

        $booktrial  = \Booktrial::where('_id','!=',(int) $data['_id'])->where('customer_phone','LIKE','%'.substr($data['customer_phone'], -8).'%')->where('missedcall_batch','exists',true)->where('created_at','>',$from_date)->where('created_at','<',$to_date)->orderBy('_id','desc')->first();
        if(!empty($booktrial) && isset($booktrial->missedcall_batch) && $booktrial->missedcall_batch != ''){
            $batch = $booktrial->missedcall_batch + 1;
        }else{
            $batch = 1;
        }

        $missedcall_no = \Ozonetelmissedcallno::where('batch',$batch)->where('for','N-3Trial')->get()->toArray();

        if(empty($missedcall_no)){

            $missedcall_no = \Ozonetelmissedcallno::where('batch',1)->where('for','N-3Trial')->get()->toArray();
        }

        foreach ($missedcall_no as $key => $value) {

            switch ($value['type']) {
                case 'yes': $data['yes'] = $value['number'];break;
                case 'no': $data['no'] = $value['number'];break;
                case 'reschedule': $data['reschedule'] = $value['number'];break;
            }

        }

        $slot_date 	       =	date('d-m-Y', strtotime($data['schedule_date']));
        $data['datetime'] 	       =	strtoupper($slot_date ." ".$data['schedule_slot_start_time']);

        $booktrial = \Booktrial::find((int) $data['_id']);
        $booktrial->missedcall_batch = $batch;
        $booktrial->update();

        return $this->customersms->missedCallDelay($data,$ozonetel_date);
    }

    public function missedCallReview($data,$delayReminderTimeAfter2Hour){

        $current_date = date('Y-m-d 00:00:00');

        $from_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date))));
        $to_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date." + 1 days"))));

        $booktrial  = \Booktrial::where('_id','!=',(int) $data['_id'])->where('customer_phone','LIKE','%'.substr($data['customer_phone'], -8).'%')->where('missedcall_review_batch','exists',true)->where('created_at','>',$from_date)->where('created_at','<',$to_date)->orderBy('_id','desc')->first();
        if(!empty($booktrial) && isset($booktrial->missedcall_review_batch) && $booktrial->missedcall_review_batch != ''){
            $batch = $booktrial->missedcall_review_batch + 1;
        }else{
            $batch = 1;
        }

        $missedcall_no = \Ozonetelmissedcallno::where('batch',$batch)->where('for','N+2Trial')->get()->toArray();

        if(empty($missedcall_no)){

            $missedcall_no = \Ozonetelmissedcallno::where('batch',1)->where('for','N+2Trial')->get()->toArray();
        }

        foreach ($missedcall_no as $key => $value) {

            switch ($value['type']) {
                case 'like': $data['missedcall1'] = $value['number'];break;
                case 'explore': $data['missedcall2'] = $value['number'];break;
                case 'notattended': $data['missedcall3'] = $value['number'];break;
            }

        }

        $slot_date 	       =	date('d-m-Y', strtotime($data['schedule_date']));
        $data['datetime'] 	       =	strtoupper($slot_date ." ".$data['schedule_slot_start_time']);

        $booktrial = \Booktrial::find((int) $data['_id']);
        $booktrial->missedcall_review_batch = $batch;
        $booktrial->update();

        return $this->customersms->bookTrialReminderAfter2Hour($data, $delayReminderTimeAfter2Hour);
    }

    public function addRegId($data){

        $response = add_reg_id($data);

        return Response::json($response,$response['status']);
    }
    
    public function booktrialdetail($captureid){

        $booktrial      =   Booktrial::with('invite')->with(array('finder'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}));}))->find(intval($captureid));

        if(!$booktrial){

            return $this->responseNotFound('Request not found');
        }

        $booktrial = $booktrial->toArray();

        $unset = array('customer_emailqueuedids','customer_smsqueuedids','customer_notification_messageids','finder_emailqueuedids','finder_smsqueuedids','customer_auto_sms');

        if(isset($booktrial['schedule_date_time']) && strtotime(Carbon::now()) >= strtotime(Carbon::parse($booktrial['schedule_date_time']))){

            $unset[] = 'what_i_should_carry';
            $unset[] = 'what_i_should_expect';
        }

        foreach($unset as $value){

            if(isset($booktrial[$value])){
                unset($booktrial[$value]);
            }
        }

        $responsedata   = ['booktrial' => $booktrial,  'message' => 'Booktrial Detail'];
        return Response::json($responsedata, 200);

    }

    public function googlePin($lat,$lon){

        $google_pin = "https://maps.google.com/maps?q=".$lat.",".$lon."&ll=".$lat.",".$lon;

        $shorten_url = new ShortenUrl();

        $url = $shorten_url->getShortenUrl($google_pin);

        if(isset($url['status']) &&  $url['status'] == 200){
            $google_pin = $url['url'];
        }

        return $google_pin;
    }

    public function booktrialAction($action,$trial_id){

        $booktrial = Booktrial::find(intval($trial_id));

        if($booktrial){

            $booktrial->customer_action = $action;
            $booktrial->update();

            $resp   =   array('status' => 200,'message' => ucwords($action)." Successfull");
            return  Response::json($resp, 200);

        }else{

            $resp   =   array('status' => 400,'message' => "No Trials Found");
            return  Response::json($resp, 400);
        }

    }

    public function postTrialAction($source = 'customer'){

        $rules = [
            'booktrial_id' => 'required',
            'status' => 'required'
        ];

        $validator = Validator::make($data = Input::json()->all(),$rules);

        if($validator->fails()) {
            $resp = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));
            return  Response::json($resp, 400);
        }

        $booktrial = Booktrial::find(intval($data['booktrial_id']));

        if($booktrial){

            if($source == 'customer'){
                $booktrial->post_trial_status = (isset($data['status']) && $data['status'] == true ) ? "attended" : "no show";
                $booktrial->post_trial_status_reason = (isset($data['reason']) && $data['reason'] != "") ? $data['reason'] : "";
            }

            if($source == 'vendor'){
                $booktrial->trial_attended_finder = (isset($data['status']) && $data['status'] == true ) ? "attended" : "no show";
                $booktrial->trial_attended_finder_reason = (isset($data['reason']) && $data['reason'] != "") ? $data['reason'] : "";
            }

            $booktrial->update();

            $resp   =   array('status' => 200,'message' => "Successfull");
            return  Response::json($resp, 200);

        }else{

            $resp   =   array('status' => 400,'message' => "No Trials Found");
            return  Response::json($resp, 400);
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

    public function feedbackFromCustomer(){

        $data = Input::json()->all();
        if(empty($data['customer_id'])){
            $resp   =   array('status' => 400,'message' => "Data Missing - customer_id");
            return  Response::json($resp, 400);
        }
        if(empty($data['finder_id'])){
            $resp   =   array('status' => 400,'message' => "Data Missing - finder_id");
            return  Response::json($resp, 400);
        }
        if(empty($data['booktrial_id'])){
            $resp   =   array('status' => 400,'message' => "Data Missing - booktrial_id");
            return  Response::json($resp, 400);
        }
        if(empty($data['service_id'])){
            $resp   =   array('status' => 400,'message' => "Data Missing - service_id");
            return  Response::json($resp, 400);
        }
        if(empty($data['feedback'])){
            $resp   =   array('status' => 400,'message' => "Data Missing - feedback");
            return  Response::json($resp, 400);
        }
        $feedback = array(
            'customer_id'                  =>      Input::json()->get('customer_id'), 
            'finder_id'                 =>      Input::json()->get('finder_id'), 
            'booktrial_id'                 =>      Input::json()->get('booktrial_id'),
            'service_id'                =>      Input::json()->get('service_id'),
            'feedback'              =>      Input::json()->get('feedback'),
            );
        $feed = Feedback::create($feedback);
        $resp   =   array('status' => 200,'message' => "Feedback recieved", 'feedback' => $feed);
        return Response::json($resp);
    }

    // Confirm a trial from the app
    public function confirm($id){

        $id                 =   (int) $id;
        $bookdata           =   array();
        $booktrial          =   Booktrial::findOrFail($id);

        /*if(isset($booktrial->pre_trial_status) && $booktrial->pre_trial_status == 'confirm'){

            $resp   =   array('status' => 200, 'message' => "Trial Already confirmed");
            return Response::json($resp,200);
        }*/

        array_set($bookdata, 'going_status', 1);
        array_set($bookdata, 'going_status_txt', 'confirmed');
        array_set($bookdata, 'booktrial_actions', '');
        array_set($bookdata, 'followup_date', '');
        array_set($bookdata, 'followup_date_time', '');
        array_set($bookdata, 'source_flag', 'customer');
        array_set($bookdata, 'pre_trial_status', 'confirm');
        array_set($bookdata, 'final_lead_stage', 'trial_stage');
        array_set($bookdata, 'final_lead_status', 'confirmed');
        $trialbooked        =   $booktrial->update($bookdata);

        if($trialbooked == true ){
            $resp   =   array('status' => 200, 'message' => "Trial confirmed");
            return Response::json($resp,200);

        }else{

            $resp   =   array('status' => 400, 'message' => "Error");
            return Response::json($resp,400);

        }

    }

    public function inviteForTrial(){

        $req = Input::json()->all();
        Log::info('inviteForTrial',$req);

        // Request Validations...........
        $rules = [
            'booktrial_id' => 'required|integer|numeric',
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
        $BooktrialData = Booktrial::where('_id', $req['booktrial_id'])
            ->with('invite')
            ->get(array(
                'customer_id', 'customer_name', 'customer_email','customer_phone','service_name',
                'type', 'finder_name', 'finder_location','finder_address',
                'schedule_slot_start_time','schedule_date','schedule_date_time','type','root_booktrial_id'
            ))
            ->first();

        $errorMessage = !isset($BooktrialData)
            ? 'Invalid Booktrial ID'
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


        if(array_where($emails, function ($key, $value) use($BooktrialData)  {
            if($value == $BooktrialData['customer_email']){
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

        if(array_where($phones, function ($key, $value) use($BooktrialData)  {
            if($value == $BooktrialData['customer_phone']){
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
            $invite->host_id = $BooktrialData['customer_id'];
            $invite->host_email = $BooktrialData['customer_email'];
            $invite->host_name = $BooktrialData['customer_name'];
            $invite->host_phone = $BooktrialData['customer_phone'];
            $invite->root_booktrial_id =
                isset($BooktrialData['root_booktrial_id'])
                    ? $BooktrialData['root_booktrial_id']
                    : $req['booktrial_id'];
            $invite->referrer_booktrial_id = $req['booktrial_id'];
            $invite->source = $req['source'];
            isset($invitee['name']) ? $invite->invitee_name = trim($invitee['name']): null;
            isset($invitee['email']) ? $invite->invitee_email = trim($invitee['email']): null;
            isset($invitee['phone']) ? $invite->invitee_phone = trim($invitee['phone']): null;
            $invite->save();

            // Generate bitly for landing page with invite_id and booktrial_id
            $url = 'www.fitternity.com/invitedtrial?booktrial_id='.$invite['referrer_booktrial_id'].'&invite_id='.$invite['_id'];
            $url2 = 'www.fitternity.com/invitedtrial?booktrial_id='.$invite['referrer_booktrial_id'].'&invite_id='.$invite['_id'].'&accompany=false';
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
                'type'=> $BooktrialData['type'],
                'finder_name'=> $BooktrialData['finder_name'],
                'finder_location'=> $BooktrialData['finder_location'],
                'finder_address'=> $BooktrialData['finder_address'],
                'schedule_date'=> $BooktrialData['schedule_date'],
                'schedule_date_time'=> $BooktrialData['schedule_date_time'],
                'service_name'=> $BooktrialData['service_name'],
                'schedule_slot_start_time'=> $BooktrialData['schedule_slot_start_time'],
                'url' => $url,
                'url2' => $url2
            );

//            return $this->customermailer->inviteEmail($BooktrialData['type'], $templateData);

            isset($templateData['invitee_email']) ? $this->customermailer->inviteEmail($BooktrialData['type'], $templateData) : null;
            isset($templateData['invitee_phone']) ? $this->customersms->inviteSMS($BooktrialData['type'], $templateData) : null;
        }

        return Response::json(
            array(
                'status' => 200,
                'message' => 'Invitation has been sent successfully'
            ),200
        );
    }

    public function acceptInvite(){

        $req = Input::json()->all();
        Log::info('acceptInvite',$req);

        // Request Validations...........
        $rules = [
            'invite_id' => 'required|integer|numeric'
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

        // Check invite status.........
        $invite = Invite::where('_id',$req['invite_id'])->first();

        if($invite['status'] != 'pending'){
            return Response::json(
                array(
                    'status' => 422,
                    'message' => 'We already got your input for this invitation'
                ),422
            );
        }

        // Booktrial for invitee......
        $bookTrialResponse = $this->bookTrialFree($req)->getData();
        $bookTrialResponse = (array) $bookTrialResponse;
        if($bookTrialResponse['status'] != 200){
            return Response::json($bookTrialResponse);
        }
        $bookTrialData = Booktrial::where('_id', $bookTrialResponse['booktrialid'])->first();

        // Update acceptance_status in invites collection.....
        $invite['status'] = 'accepted';
        $invite->save();

        // Send Email to host to notify acceptance of trial invitation by invitee......
        $templateData = array(
            'host_name' => $invite['host_name'],
            'host_email' => $invite['host_email'],
            'host_phone' => $invite['host_phone'],
            'invitee_name' => $invite['invitee_name'],
            'finder_name' => $bookTrialData['finder_name'],
            'finder_location' => $bookTrialData['finder_location'],
            'schedule_date' => $bookTrialData['schedule_date'],
            'schedule_date_time' => $bookTrialData['schedule_date_time'],
            'schedule_slot_start_time' => $bookTrialData['schedule_slot_start_time'],
            'service_name' => $bookTrialData['service_name']
        );
//        isset($templateData['host_email']) ? $this->customermailer->respondToInviteEmail($templateData) : null;
        isset($templateData['host_phone']) ? $this->customersms->respondToInviteSMS($templateData) : null;

        // Send Success Response........
        return Response::json(
            array(
                'status' => 200,
                'message' => 'Invite has been accepted successfully'
            ),200
        );

    }

    public function addVIPTrialAsRewardOnVIPPaidTrial($booktrialdata, $order_id=null){

        try {
            $req['customer_id'] = $booktrialdata['customer_id'];
            $req['customer_name'] = $booktrialdata['customer_name'];
            $req['customer_phone'] = $booktrialdata['customer_phone'];
            $req['customer_email'] = $booktrialdata['customer_email'];
            $req['title'] = '1 VIP Trial';
            $req['description'] = '1 VIP Trial';
            $req['reward_type'] = 'sessions';
            $req['validity_in_days'] = 30;
            $req['terms'] = 'Terms & Conditions';
            $req['booktrial_id'] = $booktrialdata['_id'];
            $req['order_id'] = (int) $order_id;
            $req['payload']['booktrial_type'] = $booktrialdata['type'];
            $req['payload']['amount'] = 199;
            $req['quantity'] = 1;

            $this->customerreward->saveToMyRewards($req);
            $email   =  $this->customermailer->vipReward($booktrialdata);
            $sms    =  $this->customersms->vipReward($booktrialdata);

            return array('email'=>$email,'sms'=>$sms);


        } catch (Exception $e) {

            Log::error($e);

            return false;
        }
    }



    public function addReminderMessage($bootrial_id){

        $trial  = Booktrial::find(intval($bootrial_id));

        if($trial && isset($trial['reminder_need_status']) && $trial['reminder_need_status'] =='yes'){

            $customer_id                        =   intval($trial['customer_id']);
            $customer_name                      =   $trial['customer_name'];
            $customer_phone                     =   $trial['customer_phone'];
            $finder_id                          =   intval($trial['finder_id']);
            $schedule_date                      =   $trial['schedule_date'];
            $schedule_slot                      =   $trial['schedule_slot'];

//        $customer_phone                     =   "9773348762";
//        $finder_id                          =   3305;
//        $schedule_date                      =   "30-05-2015";
//        $schedule_slot                      =   "05:00 PM-06:30 PM";

            $slot_times 				       =	explode('-', $schedule_slot);
            $schedule_slot_start_time 	       =	$slot_times[0];
            $schedule_slot_end_time 	       =	$slot_times[1];
            $schedule_slot 				       =	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

            $slot_date 					       =	date('d-m-Y', strtotime($schedule_date));
            $schedule_date_starttime 	       =	strtoupper($slot_date ." ".$schedule_slot_start_time);
            $schedule_date_time			       =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();

            $finder                             =   Finder::with('location')->find($finder_id);
            $findername                         =   "";

            if($finder){
                if($finder['title'] && $finder['location']['name']){
                    $findername     = ucwords($finder['title'])." ".ucwords($finder['location']['name']);
                }else{
                    $findername     = ($finder['title']) ? ucwords($finder['title']) : "";
                }
            }

            $data = [
                'customer_id' => $customer_id,
                'customer_name' => trim($customer_name),
                'customer_phone' => trim($customer_phone),
                'message' => 'Hope you are ready for your session at fitness with '.$findername.' at '.strtoupper($schedule_slot_start_time),
                'schedule_date' => trim($schedule_date),
                'schedule_date_time' => trim($schedule_date_time),
                'schedule_slot' => trim($schedule_slot),
                'call_status' => 'no',
                'booktrial_id' => 37688
            ];

//            return $data;

            $insertedid = Remindercall::max('_id') + 1;
            $obj       =   new Remindercall($data);
            $obj->_id  =   $insertedid;
            $obj->save();

        }

    }

    public function getReminderMessage(){

        $customer_mobile  = Input::get('phone');

        if(!$customer_mobile){
            $resp       =    array('status' => 400, 'message' => "Customer phone no required");
            return Response::json($resp,400);
        }

        $current_date       =       date("Y-m-d");
        $reminderMessage    =       Remindercall::where('schedule_date_time', '>=', new DateTime( date("d-m-Y", strtotime( $current_date )) ))
                                                    ->where('customer_phone', '=', trim($customer_mobile))
                                                    ->where('call_status', '=','yes')
                                                    ->first();

//       return $reminderMessage;

        if(!$reminderMessage){
            $resp       =    array('status' => 400, 'message' => "Customer Number Does Not Exist");
            return Response::json($resp,400);
        }
        $message       =       trim($reminderMessage['message']);
        $resp          =       array('status' => 200, 'message' => $message);
        return Response::json($resp,200);

    }



}
