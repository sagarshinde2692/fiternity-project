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

use App\Services\Jwtauth as Jwtauth;
use App\Services\Metropolis as Metropolis;
use App\Services\RelianceService as RelianceService;


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
    protected $jwtauth;
    protected $relianceService;

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
        CustomerReward $customerreward,
        Jwtauth $jwtauth,
        RelianceService $relianceService
    ) {
        parent::__construct();
        date_default_timezone_set("Asia/Kolkata");
        $this->customermailer           =   $customermailer;
        $this->findermailer             =   $findermailer;
        $this->customersms              =   $customersms;
        $this->findersms                =   $findersms;
        $this->customernotification     =   $customernotification;
        $this->fitnessforce             =   $fitnessforce;
        $this->sidekiq               =   $sidekiq;
        $this->ozontelOutboundCall  =   $ozontelOutboundCall;
        $this->utilities            =   $utilities;
        $this->customerreward            =   $customerreward;
        $this->jwtauth 	=	$jwtauth;
        $this->vendor_token = false;
        $this->relianceService = $relianceService;

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

                /*$scheduleDateTime               =   Carbon::createFromFormat('d-m-Y g:i A', strtoupper($date." ".$slot['start_time']))->subMinutes(1);
                // $oneHourDiffInMin            =   $currentDateTime->diffInMinutes($delayReminderTimeBefore1Hour, false);
                $slot_datetime_pass_status      =   ($currentDateTime->diffInMinutes($scheduleDateTime, false) > 60) ? false : true;*/

                $scheduleDateTimeUnix           =  strtotime(strtoupper($date." ".$slot['start_time']));
                $slot_datetime_pass_status      =  (($scheduleDateTimeUnix - time()) > 60*60) ? false : true;

                array_set($slot, 'passed', $slot_datetime_pass_status);
                array_set($slot, 'service_id', $item['_id']);
                array_set($slot, 'finder_id', $item['finder_id']);

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
        // return "hello";
        // exit(0);

        $currentDateTime        =   \Carbon\Carbon::now();
        $finderid               =   (int) $finderid;
        $date                   =   ($date == null) ? Carbon::now() : $date;
        $timestamp              =   strtotime($date);
        $weekday                =   strtolower(date( "l", $timestamp));

        
        

        $items = Service::active()->where('finder_id', '=', $finderid)->where('trialschedules', 'exists',true)->where('trialschedules', '!=',[])->get(array('_id','name','finder_id', 'trialschedules','servicecategory_id'));
        

        if(!$items){
            return $this->responseNotFound('TrialSchedule does not exist');
        }

        $items->toArray();

        

        $scheduleservices = array();
        foreach ($items as $k => $item) {



            $ratecard_id = Ratecard::where('finder_id',$finderid)->where('service_id',$item['_id'])->where('type', 'trial')->get(['id'])->first();

            $weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
                if($value['weekday'] == $weekday){
                    return $value;
                }
            }));

            $time_in_seconds = time_passed_check($item['servicecategory_id']);

            //slots exists
            if(count($weekdayslots['slots']) > 0){
                // echo "<br> count -- ".count($weekdayslots['slots']);
                $service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday);

                $slots = array();
                
                    foreach ($weekdayslots['slots'] as $slot) {


                    // $totalbookcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
                    // $goingcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
                    // $cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();

                    // $slot_status        =   ($slot['limit'] > $goingcnt) ? "available" : "full";

                    $slot_status        =   "available";

                    array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
                    array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
                    // array_set($slot, 'totalbookcnt', $totalbookcnt);
                    // array_set($slot, 'goingcnt', $goingcnt);
                    // array_set($slot, 'cancelcnt', $cancelcnt);
                    array_set($slot, 'status', $slot_status);

                    try{

                        $scheduleDateTimeUnix           =  strtotime(strtoupper($date." ".$slot['start_time']));
                        $slot_datetime_pass_status      =   (($scheduleDateTimeUnix - time()) > $time_in_seconds) ? false : true;
                        array_set($slot, 'passed', $slot_datetime_pass_status);
                        array_set($slot, 'service_id', $item['_id']);
                        array_set($slot, 'finder_id', $item['finder_id']);
                        array_set($slot, 'ratecard_id', $ratecard_id['_id']);
                        array_push($slots, $slot);

                    }catch(Exception $e){

                        Log::info("getTrialSchedule Error : ".$date." ".$slot['start_time']);
                    }

                }
                
                
                $service['ratecard_id'] = $ratecard_id['id'];
                $service['slots'] = $slots;
                $service['trialschedules']['slots'] = $slots;
                if(isset($ratecard_id['id']) && $ratecard_id['id'] != null){
                    array_push($scheduleservices, $service);
                }
                // array_push($scheduleservices, $service);
            }
        }

        $schedules_sort = array();
        $schedules_slots_empty = array();

        foreach ($scheduleservices as $key => $value) {

            if(count($value['slots']) > 0){
                $schedules_sort[] = $value;
            }else{
                $schedules_slots_empty[] = $value;
            }

        }

        $scheduleservices = array();

        $scheduleservices = array_merge($schedules_sort,$schedules_slots_empty);
        
        return $scheduleservices;
    }


    public function getTrialScheduleIfDontSoltsAlso($finderid,$date = null,$service_id = false){

        // $dobj = new DateTime;print_r($dobj);

        $currentDateTime        =   \Carbon\Carbon::now();
        $finderid              =    (int) $finderid;
        $date                  =    ($date == null) ? Carbon::now() : $date;
        $timestamp             =    strtotime($date);
        $weekday               =    strtolower(date( "l", $timestamp));

        $query                  =   Service::active()->where('finder_id', '=', $finderid);

        if($service_id){
            $query->where('_id',(int)$service_id);
        }

        $items = $query->where('trialschedules', 'exists',true)->where('trialschedules', '!=',[])->where('status','1')->get(array('_id','three_day_trial','vip_trial','name','finder_id', 'trialschedules','servicecategory_id','trial','membership'));

        if(!$items){
            return $this->responseNotFound('TrialSchedule does not exist');
        }

        $items->toArray();

        $scheduleservices = array();
        foreach ($items as $k => $item) {

            $ratecard_id = Ratecard::where('finder_id',$finderid)->where('service_id',$item['_id'])->where('type', 'trial')->get(['id'])->first();

            $weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
                if($value['weekday'] == $weekday){
                    return $value;
                }
            }));

            $time_in_seconds = time_passed_check($item['servicecategory_id']);

            // echo "<br> count -- ".count($weekdayslots['slots']);
            $item['three_day_trial'] = isset($item['three_day_trial']) ? $item['three_day_trial'] : "";
            $item['vip_trial'] = ""; //isset($item['vip_trial']) ? $item['vip_trial'] : "";
            $service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday, 'three_day_trial' => $item['three_day_trial'],'vip_trial' => $item['vip_trial'],'trial' => (isset($item['trial'])) ? $item['trial'] : "",'membership' => (isset($item['membership'])) ? $item['membership'] : "");

            $slots = array();
            $cashback               =   "";
            //slots exists
            if(count($weekdayslots['slots']) > 0){

                $check_cashback         =   true;
                $cashback               =   "";
                
                    foreach ($weekdayslots['slots'] as $slot) {

                    if($check_cashback){
                        if($slot && isset($slot['price']) && intval($slot['price']) > 0){
                            $cashback        =  "100%";
                            $check_cashback  =  false;
                        }
                    }

                    // $totalbookcnt        =   Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
                    // $goingcnt           =    Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
                    // $cancelcnt          =    Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();

                    // $slot_status        =    ($slot['limit'] > $goingcnt) ? "available" : "full";

                    $slot_status        =   "available";

                    array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
                    array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
                    // array_set($slot, 'totalbookcnt', $totalbookcnt);
                    // array_set($slot, 'goingcnt', $goingcnt);
                    // array_set($slot, 'cancelcnt', $cancelcnt);
                    array_set($slot, 'status', $slot_status);

                    $vip_trial_amount = 0;

                    if($item['vip_trial'] == "1"){

                        $price = (int) $slot['price'];

                        if($price >= 500){
                            $vip_trial_amount = $price;
                        }

                        if($price < 500){
                            $vip_trial_amount = $price+150;
                        }

                        if($price == 0){
                            $vip_trial_amount = 199;
                        }

                    }

                    array_set($slot, 'vip_trial_amount', $vip_trial_amount);

                    try{

                        $scheduleDateTimeUnix           =  strtotime(strtoupper($date." ".$slot['start_time']));
                        $slot_datetime_pass_status      =   (($scheduleDateTimeUnix - time()) > $time_in_seconds) ? false : true;
                        array_set($slot, 'passed', $slot_datetime_pass_status);
                        array_set($slot, 'service_id', $item['_id']);
                        array_set($slot, 'finder_id', $item['finder_id']);
                        array_set($slot, 'ratecard_id', $ratecard_id['_id']);
                        array_push($slots, $slot);

                    }catch(Exception $e){

                        Log::info("getTrialScheduleIfDontSoltsAlso Error : ".$date." ".$slot['start_time']);
                    }


                }
                
                
            }

            $service['cashback']                =   $cashback;
            $service['slots']                   =   $slots;
            $service['trialschedules']['slots'] =   $slots;
            $service['ratecard_id'] = $ratecard_id['id'];
            if(isset($ratecard_id['id']) && $ratecard_id['id'] != null){
                array_push($scheduleservices, $service);
            }

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

    public function getWorkoutSessionSchedule($finderid,$date = null,$service_id = false){
        // $dobj = new DateTime;print_r($dobj);

        $currentDateTime        =   \Carbon\Carbon::now();
        $finderid              =    (int) $finderid;
        $date                  =    ($date == null) ? Carbon::now() : $date;
        $timestamp             =    strtotime($date);
        $weekday               =    strtolower(date( "l", $timestamp));

        $query = Service::active()->where('finder_id', '=', $finderid);

        if($service_id){
            $query->where('_id',(int)$service_id);
        }

        $items = $query->get(array('_id','name','finder_id', 'trialschedules', 'workoutsessionschedules','servicecategory_id','trial','membership'));

        if(!$items){
            return $this->responseNotFound('WorkoutSession Schedule does not exist');
        }

        $items->toArray();

        $scheduleservices = array();
        foreach ($items as $k => $item) {

            $ratecard_id = Ratecard::where('finder_id',$finderid)->where('service_id',$item['_id'])->where('type', 'workout session')->get(['id'])->first();

            $weekdayslots = head(array_where($item['workoutsessionschedules'], function($key, $value) use ($weekday){
                if($value['weekday'] == $weekday){
                    return $value;
                }
            }));

            $time_in_seconds = time_passed_check($item['servicecategory_id']);

            //slots exists
            if(count($weekdayslots['slots']) > 0){
                // echo "<br> count -- ".count($weekdayslots['slots']);
                $service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'name' => $item['name'], 'weekday' => $weekday,'trial' => (isset($item['trial'])) ? $item['trial'] : "",'membership' => (isset($item['membership'])) ? $item['membership'] : "");
                $slots = array();
                 

                     foreach ($weekdayslots['slots'] as $slot) {
                    // $totalbookcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->count();
                    // $goingcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
                    // $cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($date) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
                    // $slot_status        =    ($slot['limit'] > $goingcnt) ? "available" : "full";

                    $slot_status        =   "available";

                    // array_set($slot, 'totalbookcnt', $totalbookcnt);
                    // array_set($slot, 'goingcnt', $goingcnt);
                    // array_set($slot, 'cancelcnt', $cancelcnt);
                    array_set($slot, 'status', $slot_status);

                    try{

                        $scheduleDateTimeUnix               =  strtotime(strtoupper($date." ".$slot['start_time']));
                        $slot_datetime_pass_status      =   (($scheduleDateTimeUnix - time()) > $time_in_seconds) ? false : true;
                        array_set($slot, 'passed', $slot_datetime_pass_status);
                        array_set($slot, 'service_id', $item['_id']);
                        array_set($slot, 'finder_id', $item['finder_id']);
                        array_set($slot, 'ratecard_id', $ratecard_id['_id']);
                        array_push($slots, $slot);

                    }catch(Exception $e){

                        Log::info("getWorkoutSessionSchedule Error : ".$date." ".$slot['start_time']);
                    }

                }
                
               

                $service['slots'] = $slots;
                $service['workoutsessionschedules']['slots'] = $slots;
                $service['ratecard_id'] = $ratecard_id['id'];
                if(isset($ratecard_id['id']) && $ratecard_id['id'] != null){
                    array_push($scheduleservices, $service);
                }
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
        $currentDateTime    =   \Carbon\Carbon::now();
        $item              =    Service::active()->where('_id', (int) $serviceid)->first(array('name', 'finder_id', 'trialschedules', 'workoutsessionschedules','servicecategory_id'));
        $ratecard_id ="";
        $date =   ($date == null) ? Carbon::now() : $date;

        if(!$item){
            return $this->responseNotFound('Service Schedule does not exist');
        }

        $item->toArray();

        $time_in_seconds = time_passed_check($item['servicecategory_id']);

        $finderid          =    intval($item['finder_id']);
        $noofdays          =    ($noofdays == null) ? 1 : $noofdays;
        $schedulesof        =   ($schedulesof == null) ? 'trialschedules' : $schedulesof;
        $serviceschedules   =   array();

        for ($j = 0; $j < $noofdays; $j++) {

            $dt            =    Carbon::createFromFormat('Y-m-d', date("Y-m-d", strtotime($date)) )->addDays(intval($j))->format('d-m-Y');
            $timestamp        =     strtotime($dt);
            $weekday        =   strtolower(date( "l", $timestamp));
            // echo "$dt -- $weekday <br>";

            if($schedulesof == 'trialschedules'){

                $ratecard_id = Ratecard::where('finder_id',$finderid)->where('service_id',$item['_id'])->where('type', 'trial')->get(['id'])->first();

                $weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
                    if($value['weekday'] == $weekday){
                        return $value;
                    }
                }));

            }else{

                $ratecard_id = Ratecard::where('finder_id',$finderid)->where('service_id',$item['_id'])->where('type', 'workout session')->get(['id'])->first();

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

                        $totalbookcnt = 0;//Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->count();
                        $goingcnt = 0;//Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 1)->count();
                        // $cancelcnt = Booktrial::where('finder_id', '=', $finderid)->where('service_name', '=', $item['name'])->where('schedule_date', '=', new DateTime($dt) )->where('schedule_slot', '=', $slot['slot_time'])->where('going_status', 2)->count();
                        //$slot_status        =     ($slot['limit'] > $goingcnt) ? "available" : "full";
                        $slot_status        =   "available";

                        array_set($slot, 'totalbookcnt', $totalbookcnt);
                        array_set($slot, 'goingcnt', $goingcnt);
                        // array_set($slot, 'cancelcnt', $cancelcnt);
                        array_set($slot, 'status', $slot_status);


                        try{

                            $scheduleDateTimeUnix               =  strtotime(strtoupper($dt." ".$slot['start_time']));
                            $slot_datetime_pass_status      =   (($scheduleDateTimeUnix - time()) > $time_in_seconds) ? false : true;
                            array_set($slot, 'passed', $slot_datetime_pass_status);
                            array_set($slot, 'service_id', $item['_id']);
                            array_set($slot, 'finder_id', $item['finder_id']);
                            array_set($slot, 'ratecard_id', $ratecard_id['_id']);
                            array_push($slots, $slot);

                        }catch(Exception $e){

                            Log::info("getServiceSchedule Error : ".$date." ".$slot['start_time']);
                        }



                        // $scheduleDateTime               =    Carbon::createFromFormat('d-m-Y g:i A', strtoupper($dt." ".$slot['start_time']));
                        // $scheduleDateTime               =    Carbon::createFromFormat('d-m-Y g:i A', strtoupper($dt." ".$slot['start_time']));

                    }
                }
            
            
            $service['ratecard_id'] = $ratecard_id['id'];
            $service['slots'] = $slots;
            if(isset($ratecard_id['id']) && $ratecard_id['id'] != null){
                array_push($serviceschedules, $service);
            }

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
            return  Response::json($resp, $this->error_status);
        }

        if(empty($data['customer_email'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - customer_email");
            return  Response::json($resp, $this->error_status);
        }

        if (filter_var(trim($data['customer_email']), FILTER_VALIDATE_EMAIL) === false){
            $resp 	= 	array('status' => 400,'message' => "Invalid Email Id");
            return  Response::json($resp, $this->error_status);
        }

        if(!$this->vendor_token){

            if(empty($data['finder_id'])){
                $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_id");
                return  Response::json($resp, $this->error_status);
            }

            if(empty($data['finder_name'])){
                $resp 	= 	array('status' => 400,'message' => "Data Missing - finder_name");
                return  Response::json($resp, $this->error_status);
            }

            if(empty($data['city_id'])){
                $resp 	= 	array('status' => 400,'message' => "Data Missing - city_id");
                return  Response::json($resp, $this->error_status);
            }

            if(empty($data['customer_phone'])){
                $resp   =   array('status' => 400,'message' => "Data Missing - customer_phone");
                return  Response::json($resp, $this->error_status);
            }

        }else{

            $decodeKioskVendorToken = decodeKioskVendorToken();

            $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

            $data['finder_id'] = (int)$vendor['_id'];
            $data['finder_name'] = $vendor['name'];
            $data['city_id'] = $vendor['city']['_id'];
        }

        $is_tab_active = isTabActive($data['finder_id']);

        if($is_tab_active){
            $data['is_tab_active'] = true;
        }


        if(!$this->vendor_token){

            // Throw an error if user has already booked a trial for that vendor...
            $alreadyBookedTrials = $this->utilities->checkExistingTrialWithFinder($data['customer_email'], $data['customer_phone'], $data['finder_id']);

            if (count($alreadyBookedTrials) > 0) {
                $resp = array('status' => 403, 'message' => "You have already booked a trial for this vendor");
                return Response::json($resp, $this->error_status);
            }
        }

        $disableTrial = $this->disableTrial($data);

        if($disableTrial['status'] != 200){

            return Response::json($disableTrial,$this->error_status);
        }

        // return $data	= Input::json()->all();
        $booktrialid 		       =	Booktrial::maxId() + 1;

        $finder_id 			       = 	(int) Input::json()->get('finder_id');
        $city_id 			       =	(int) Input::json()->get('city_id');
        $finder_name 		       =	Input::json()->get('finder_name');
        $finder				       =	Finder::active()->where('_id','=',intval($finder_id))->first();
        $customer_id		       = 	autoRegisterCustomer($data);
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

        $finder = Finder::find($finder_id);

        $finder_name = (isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
        $finder_slug = (isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
        $finder_lat = (isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
        $finder_lon = (isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
        
        // setDefaultAccount($data, $customer_id);
        
        if($device_type != '' && $gcm_reg_id != ''){

            $regData = array();

            $regData['customer_id'] = $customer_id;
            $regData['reg_id'] = $gcm_reg_id;
            $regData['type'] = $device_type;

            $this->utilities->addRegId($regData);
        }


        $origin     =  (isset($finder->manual_trial_auto) && $finder->manual_trial_auto == "1") ? "manualauto" : "manual";

        $booktrialdata = array(
            'premium_session'        =>		$premium_session,

            'finder_id' 	       =>		$finder_id,
            'city_id'		       =>		$city_id,
            'finder_name' 	       =>		isset($finder->title) ? $finder->title : $finder_name,
            'finder_category_id' 	=>		intval($finder->category_id),
            'manual_trial_auto' 	=>	    isset($finder->manual_trial_auto) ? $finder->manual_trial_auto : '0',
            'finder_vcc_email' 	    =>	    isset($finder->finder_vcc_email) ? $finder->finder_vcc_email : '',
            'finder_vcc_mobile' 	=>	    isset($finder->finder_vcc_mobile) ? $finder->finder_vcc_mobile : '',

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
            'origin'		       =>		$origin,
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
            'finder_slug'   =>  $finder_slug,
            'finder_lat'   =>  $finder_lat,
            'finder_lon'   =>  $finder_lon,
            'type' => 'booktrials'
        );

        if($this->vendor_token){

            $booktrialdata['source'] = 'kiosk';
            $booktrialdata['final_lead_stage'] = 'trial_stage';
            $booktrialdata['final_lead_status'] = 'confirmed';
        }


        if(isset($data['customer_address']) && $data['customer_address'] != ''){
            $booktrialdata['customer_address_array'] = $data['customer_address'];
        }

        //         return $booktrialdata;
        $booktrial = new Booktrial($booktrialdata);
        $booktrial->_id = $booktrialid;
        $trialbooked = $booktrial->save();

        //        return $booktrial;

        if($trialbooked && !$this->vendor_token){


            if($booktrialdata['manual_trial_auto'] === '1'){

                $booktrialdata['id'] = $booktrialid;

                $now = Carbon::now();
                $time = date('H.i', time());

                ($time >= 9.0 && $time <= 15.0) ? $finder_reminder_time = $now->addHours(6) : null;
                $tomorrow = ($time >15.0 && $time <= 24.0) ? Carbon::tomorrow()->setTime(9,0,0) : Carbon::today()->setTime(9,0,0);

                $addHours = ($time > 21.0) ? 6.0 : ((($time+6) > 21) ? floatval(($time+6) - 21) : 0.0);
                $hours = explode('.', $addHours)[0];
                $minutes = isset(explode('.', $addHours)[1]) ? explode('.', $addHours)[1] : 0;

                !isset($finder_reminder_time) ? $finder_reminder_time = $tomorrow->addHours($hours)->addMinutes($minutes) : null;
                $customer_reminder_time = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->addHours(8);


                $sndInstantEmailCustomer       = 	$this->customermailer->manualTrialAuto($booktrialdata);
                $sndInstantSmsCustomer	       =	$this->customersms->manualTrialAuto($booktrialdata);
                $sndScheduledSmsCustomer	   =	$this->customersms->reminderToConfirmManualTrial($booktrialdata, $customer_reminder_time);
                $sndInstantEmailFinder         = 	$this->findermailer->manualTrialAuto($booktrialdata);
                $sndInstantSmsFinder	       =	$this->findersms->manualTrialAuto($booktrialdata);
                $sndScheduledSmsFinder	       =	$this->findersms->reminderToConfirmManualTrial($booktrialdata, $finder_reminder_time);

                $booktrial->update([
                    'customer_smsqueuedids'=>['manualtrialauto_8hours' => $sndScheduledSmsCustomer],
                    'finder_smsqueuedids'=>['manualtrialauto_6hours' => $sndScheduledSmsFinder]
                ]);
            }
            else{
                $sndInstantEmailCustomer       = 	$this->customermailer->manualBookTrial($booktrialdata);
                $sndInstantSmsCustomer	       =	$this->customersms->manualBookTrial($booktrialdata);
            }

        }

        $resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'booktrial' => $booktrial, 'message' => "Book a Trial");

        if($this->vendor_token){

            $form_fields = formFields();

            $kiosk_form_url = Config::get('app.website').'/kiosktrialform?booktrial_id='.$booktrial['_id'];

            $resp   =  [
                'status' => 200,
                'message' => "Successfully Booked a Trial",
                'kiosk_form_url'=>$kiosk_form_url
            ];

        }

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

        $customer_id		       = 	autoRegisterCustomer($data);
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

            $booktrialid	=	Booktrial::maxId() + 1;
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

    public function bookTrialHealthyTiffinFree(){

        $data	       =	array_except(Input::json()->all(), array('preferred_starting_date'));
        $postdata       =	Input::json()->all();

        Log::info('bookTrialHealthyTiffinFree',$postdata);
        

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

        $orderid 	       =	Order::maxId() + 1;        
        $customer_id        =	(Input::json()->get('customer_id')) ? Input::json()->get('customer_id') : autoRegisterCustomer($data);
        array_set($data, 'customer_id', intval($customer_id));

        if(isset($data['myreward_id']) && $data['myreward_id'] != ""){
            $createMyRewardCapture = $this->customerreward->createMyRewardCapture($data);

            if($createMyRewardCapture['status'] !== 200){

                return Response::json($createMyRewardCapture,$createMyRewardCapture['status']);
            }
        }

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


        // $hashreverse = getReversehash($order);
        //     Log::info($data["verify_hash"]);
        //     Log::info($hashreverse['reverse_hash']);
        //     if($data["verify_hash"] == $hashreverse['reverse_hash']){
        //         $hash_verified = true;
        //     }else{
        //         $hash_verified = false;
        //         $Oldorder 		= 	Order::findOrFail($orderid);
        //         $Oldorder["hash_verified"] = false;
        //         $Oldorder->update();
        //         $resp 	= 	array('status' => 401, 'order' => $Oldorder, 'message' => "Trial not booked.");
        //         return  Response::json($resp, 400);
        //     }
        $hash_verified = $this->utilities->verifyOrder($data,$order);
                if(!$hash_verified){
                    $resp 	= 	array('status' => 401, 'order' => $order, 'message' => "Trial not booked.");
                    return  Response::json($resp, 400);
                }
        if(Input::json()->get('status') == 'success') {

            $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone', substr($order->customer_phone, -10))->where('customer_source','exists',true)->orderBy('_id','asc')->where('_id','<',$order->_id)->where('finder_id',$order->finder_id)->count();

            if($count > 0){
                array_set($data, 'acquisition_type', 'renewal_direct');
                array_set($data, 'membership_type', 'renewal');
            }else{
                array_set($data,'acquisition_type','direct_payment');
                array_set($data, 'membership_type', 'new');
            }

            $order_data = $order->toArray();

            $order_data['customer_id'] = (int)autoRegisterCustomer($order_data);

            if(isset($order_data['myreward_id']) && $order_data['myreward_id'] != ""){
                $createMyRewardCapture = $this->customerreward->createMyRewardCapture($order_data);

                if($createMyRewardCapture['status'] !== 200){
                    return Response::json($createMyRewardCapture,$createMyRewardCapture['status']);
                }
            }

            $orderData = [];
            array_set($orderData, 'status', '1');
            array_set($orderData, 'order_action', 'bought');
            array_set($orderData, 'success_date', date('Y-m-d H:i:s',time()));

            if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
                array_set($orderData, 'secondary_payment_mode', 'payment_gateway_membership');
            }

            $orderdata 	=	$order->update($orderData);

            // Give Rewards / Cashback to customer based on selection, on purchase success......
            

            //Send Instant (Email) To Customer & Finder
            $sndInstantEmailCustomer                =   $this->customermailer->healthyTiffinTrial($order->toArray());
            $sndInstantSmsCustomer	                =	$this->customersms->healthyTiffinTrial($order->toArray());
            $sndInstantEmailFinder	                = 	$this->findermailer->healthyTiffinTrial($order->toArray());
            $sndInstantSmsFinder	                =	$this->findersms->healthyTiffinTrial($order->toArray());


            if(isset($order['amount_customer']) && $order['amount_customer'] != "" && $order['amount_customer'] > 0 && !isset($order['myreward_id'])){

                $this->utilities->demonetisation($order);
            	$this->customerreward->giveCashbackOrRewardsOnOrderSuccess($order);
            	$this->customersms->giveCashbackOnTrialOrderSuccessAndInvite($order->toArray());
            }

            //Send one before reminder email to vendor at 9:00 AM
            if(isset($order_data['preferred_starting_date'])){
                $slot_date 			            =	date('d-m-Y', strtotime('-1 day', strtotime($order_data['preferred_starting_date']) ));
                $datetime_str 	                =	strtoupper($slot_date ." 09:00AM");
                $reminderDateTime 		        =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $datetime_str);
                $sndReminderEmailFinder	        = 	$this->findermailer->healthyTiffinTrialReminder($order->toArray(),$reminderDateTime);
            }

            $this->utilities->sendDemonetisationCustomerSms($order);

            $this->utilities->addAmountToReferrer($order);

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


        // $hashreverse = getReversehash($order);
        //     Log::info($data["verify_hash"]);
        //     Log::info($hashreverse['reverse_hash']);
        //     if($data["verify_hash"] == $hashreverse['reverse_hash']){
        //         $hash_verified = true;
        //     }else{
        //         $hash_verified = false;
        //         $Oldorder 		= 	Order::findOrFail($orderid);
        //         $Oldorder["hash_verified"] = false;
        //         $Oldorder->update();
        //         $resp 	= 	array('status' => 401, 'order' => $Oldorder, 'message' => "Trial not booked.");
        //         return  Response::json($resp, 400);
        //     }
        $hash_verified = $this->utilities->verifyOrder($data,$order);
                if(!$hash_verified){
                    $resp 	= 	array('status' => 401, 'order' => $order, 'message' => "Trial not booked.");
                    return  Response::json($resp, 401);
                }
        if(Input::json()->get('status') == 'success') {

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

            $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone', substr($order->customer_phone, -10))->where('customer_source','exists',true)->orderBy('_id','asc')->where('_id','<',$order->_id)->where('finder_id',$order->finder_id)->count();


            if($count > 0){
                array_set($data, 'acquisition_type', 'renewal_direct');
                array_set($data, 'membership_type', 'renewal');
            }else{
                array_set($data,'acquisition_type','direct_payment');
                array_set($data, 'membership_type', 'new');
            }

            if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
                array_set($data, 'secondary_payment_mode', 'payment_gateway_membership');
            }

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

            array_set($data, 'auto_followup_date', date('Y-m-d H:i:s', strtotime("+7 days",time())));
            array_set($data, 'followup_status', 'catch_up');
            array_set($data, 'followup_status_count', 1);

            if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
                array_set($data, 'secondary_payment_mode', 'payment_gateway_membership');
            }

            $orderdata 	=	$order->update($data);

            if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                if(isset($data["send_communication_customer"]) && $data["send_communication_customer"] != ""){

                    $sndInstantEmailCustomer        =   $this->customermailer->healthyTiffinMembership($order->toArray());
                    $sndInstantSmsCustomer         =    $this->customersms->healthyTiffinMembership($order->toArray());

                    // $this->customermailer->payPerSessionFree($order->toArray());
                }

            }else{

                $sndInstantEmailCustomer        =   $this->customermailer->healthyTiffinMembership($order->toArray());
                $sndInstantSmsCustomer         =    $this->customersms->healthyTiffinMembership($order->toArray());

                // $this->customermailer->payPerSessionFree($order->toArray());
            }

            if(isset($data["order_success_flag"]) && $data["order_success_flag"] == "admin"){
                if(isset($data["send_communication_vendor"]) && $data["send_communication_vendor"] != ""){

                    $sndInstantEmailFinder         =    $this->findermailer->healthyTiffinMembership($order->toArray());
                    $sndInstantSmsFinder           =    $this->findersms->healthyTiffinMembership($order->toArray());
                }

            }else{
                $sndInstantEmailFinder         =    $this->findermailer->healthyTiffinMembership($order->toArray());
                $sndInstantSmsFinder           =    $this->findersms->healthyTiffinMembership($order->toArray());
            }

            if(isset($order->preferred_starting_date) && $order->preferred_starting_date != "" && !isset($order->cutomerSmsPurchaseAfter10Days) && !isset($order->cutomerSmsPurchaseAfter30Days)){

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

            $this->utilities->deleteCommunication($order);

            if(isset($order->redundant_order)){
                $order->unset('redundant_order');
            }

            $this->utilities->sendDemonetisationCustomerSms($order);

            $this->utilities->addAmountToReferrer($order);

            // $this->utilities->saavn($order);

            $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");
            return Response::json($resp);
        }


        $orderdata        =	$order->update($data);
        $resp 	= 	array('status' => 200, 'statustxt' => 'failed', 'order' => $order, 'message' => "Transaction Failed :)");
        return Response::json($resp);

    }

    public function getCleartripCount($finder_id){

        $count = Booktrial::where('finder_id',(int)$finder_id)->where('source','cleartrip')->count();

        return $count;
    }

    public function getTrialCount($finder_id){

        $count = Booktrial::where('finder_id',(int)$finder_id)->count();

        return $count;
    }

    public function getBeforeThreeMonthTrialCount($finder_id){

        $beforeThreeMonth =  \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s'))->subMonths(3);

        $count = Booktrial::where('finder_id',(int)$finder_id)->where('created_at', '>=', new DateTime($beforeThreeMonth))->count();

        return $count;
    }

    public function bookTrialPaid($data = null){

        // $data = $data ? $data : Input::json()->all();

        if($data){
            $data['internal_success'] = true;
        }else{
            $data = Input::json()->all();
        }

        //        return $data;

       Log::info('------------bookTrialPaid------------',$data);

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

        // if(!isset($data['schedule_slot']) || $data['schedule_slot'] == ''){
        //     $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
        //     return  Response::json($resp, 400);
        // }

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

            if(!empty($order['schedule_slot'])){
                $data['schedule_slot'] = $order['schedule_slot'];
            }

            if(!empty($order['schedule_date'])){
                $data['schedule_date'] = $order['schedule_date'];
            }

            if(isset($order->status) && $order->status == '1' && isset($order->order_action) && $order->order_action == 'bought' && !isset($data['order_success_flag'])){

                $resp 	= 	array('status' => 200, 'order_id' => $order_id, 'message' => "Already Status Successfull");
                return Response::json($resp);
            }

            $order_data = $order->toArray();

            $order_data['customer_id'] = (int)autoRegisterCustomer($order_data);

            if(isset($order_data['myreward_id']) && $order_data['myreward_id'] != "" && !empty($order_data['myreward_id'])){
                $createMyRewardCapture = $this->customerreward->createMyRewardCapture($order_data);

                if($createMyRewardCapture['status'] !== 200){

                    return Response::json($createMyRewardCapture,$createMyRewardCapture['status']);
                }

                $data['myreward_id'] = (int)$order_data['myreward_id'];
            }else{
                // $hashreverse = getReversehash($order);
                // if(isset($data["verify_hash"]) && $data["verify_hash"] == $hashreverse['reverse_hash']){
                //     $hash_verified = true;
                //     Log::info($data["verify_hash"]);
                //     Log::info($hashreverse['reverse_hash']);
                // }else{
                //     $hash_verified = false;
                //     $Oldorder 		= 	Order::findOrFail($order_id);
                //     $Oldorder["hash_verified"] = false;
                //     $Oldorder->update();
                //     $resp 	= 	array('status' => 401, 'order' => $Oldorder, 'message' => "Trial not booked.");
                //     Log::info($data["verify_hash"]);
                //     Log::info($hashreverse['reverse_hash']);
                //     return  Response::json($resp, 400);
                // }
                $hash_verified = $this->utilities->verifyOrder($data,$order);
                
                if(!$hash_verified){
                    $resp 	= 	array('status' => 401, 'order' => $order, 'message' => "Trial not booked.");
                    return  Response::json($resp, 400);
                }
                
                if(isset($order['session_payment']) && $order['session_payment']){
                    Log::info(" info ".print_r("AAAYA 112",true));

                    return $this->payLaterPaymentSuccess($order['_id']);
                    
                }
                
            }

            if(!empty($order['extended_validity_order_id'])){
                $extended_validity_order = $this->utilities->getExtendedValidityOrder($order);
                if(!$extended_validity_order){
                    $resp 	= 	array('status' => 401, 'order' => $order, 'message' => "Trial not booked.");
                    return  Response::json($resp, 400);
                }
                $extended_validity_order->sessions_left = $extended_validity_order->sessions_left - 1;

                if(strtotime($data['schedule_date']) < strtotime($order['start_date'])){
                    $extended_validity_order['prev_start_date'] = new MongoDate(strtotime($extended_validity_order['start_date']));
                    $extended_validity_order['start_date'] =  new MongoDate($data['schedule_date']);
                    $extended_validity_order['prev_end_date'] = new MongoDate(strtotime($data['end_date']));
                    $extended_validity_order['end_date'] = new MongoDate(strtotime('+ '.$extended_validity_order['duration_day'], strtotime($data['schedule_date'])));
                }

                $extended_validity_order->update();
                $extended_validity_no_of_sessions = $extended_validity_order->no_of_sessions;
                $extended_validity_sessions_booked = $extended_validity_order->no_of_sessions - $extended_validity_order->sessions_left;
                $session_pack_comm = !empty($extended_validity_order->ratecard_flags['enable_vendor_ext_validity_comm']);
            }
            $count  = Order::where("status","1")->where('customer_email',$order->customer_email)->where('customer_phone', substr($order->customer_phone, -10))->where('customer_source','exists',true)->orderBy('_id','asc')->where('_id','<',$order->_id)->where('finder_id',$order->finder_id)->count();

            if($count > 0){
                $order->update(array('acquisition_type'=>'renewal_direct'));
            }else{
                array_set($data,'acquisition_type','direct_payment');
            }


            $source                             =   (isset($order->customer_source) && $order->customer_source != '') ? trim($order->customer_source) : "website";

            $service_id	 				       =	(isset($order->service_id) && $order->service_id != '') ? intval($order->service_id) : "";

            $campaign	 				       =	(isset($data['campaign']) && $data['campaign'] != '') ? $data['campaign'] : "";
            $otp	 					       =	(isset($data['otp']) && $data['otp'] != '') ? $data['otp'] : "";

            $slot_times = "";
            $schedule_slot_start_time = "";
            $schedule_slot_end_time = "";
            $schedule_slot = "";
            $slot_date = "";
            $schedule_date_starttime = "";
            $schedule_date_time = "";

            if(isset($data['schedule_slot'])){
                  
                $slot_times 				       =	explode('-',$data['schedule_slot']);
                $schedule_slot_start_time 	       =	trim($slot_times[0]);
                
                if(count($slot_times) == 1){
                
                    $schedule_slot_end_time = date('g:i a', strtotime('+1 hour', strtotime($slot_times[0])));
                    $data['schedule_slot'] = $slot_times[0].'-'.$schedule_slot_end_time;
                
                }else{
                
                    $schedule_slot_end_time= trim($slot_times[1]);
                
                }
            
                $schedule_slot 				       =	$schedule_slot_start_time.'-'.$schedule_slot_end_time;
                $slot_date 					       =	date('d-m-Y', strtotime($data['schedule_date']));
                $schedule_date_starttime 	       =	strtoupper($slot_date ." ".$schedule_slot_start_time);

            }
            
            if(isset($order->booktrial_id)){
                $booktrialid = (int)$order->booktrial_id;
            }else{
                $booktrialid                       =    Booktrial::maxId() + 1;
            }

            $finderid 					       = 	(int) $data['finder_id'];
            $finder 					       = 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');}))->with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->find($finderid);

            $cleartrip_count                   =    $this->getCleartripCount($finderid);
            $trial_count                       =    $this->getTrialCount($finderid);
            $before_three_month_trial_count    =    $this->getBeforeThreeMonthTrialCount($finderid);

            $customer_id 				       =	autoRegisterCustomer($data);
            $customer_name 				       =	$data['customer_name'];
            $customer_email 			       =	$data['customer_email'];
            $customer_phone 			       =	preg_replace("/[^0-9]/", "", $data['customer_phone']) ;$data['customer_phone'];
            $fitcard_user				       = 	!empty($data['fitcard_user']) ? intval($data['fitcard_user']) : 0;
            $type						       = 	!empty($data['type']) ? $data['type'] : '';

            $finder_name				       = 	(isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
            $finder_slug				       = 	(isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
            $finder_lat 				       = 	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon 				       = 	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $city_id 					       =	(int) $finder['city_id'];

            $finder_commercial_type		       = 	(isset($finder['commercial_type']) && $finder['commercial_type'] != '') ? (int)$finder['commercial_type'] : "";
            $finder_category_id				   = 	(isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";

            $final_lead_stage = '';
            $final_lead_status = '';

            $finder_city           =    (isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
            $finder_city_slug      =    (isset($finder['city']['slug']) && $finder['city']['slug'] != '') ? $finder['city']['slug'] : "";
            
            $confirmed = array(1,2,3);

            if(in_array($finder_commercial_type, $confirmed)){

                $final_lead_stage = 'trial_stage';
                $final_lead_status = 'confirmed';

            }else{

                $final_lead_stage = 'booking_stage';
                $final_lead_status = 'call_to_confirm';
            }

            $device_type    =   $data['device_type']    = (isset($order['device_type']) && $order['device_type'] != '') ? $order['device_type'] : "";
            $gcm_reg_id     =   $data['gcm_reg_id']     = (isset($order['gcm_reg_id']) && $order['gcm_reg_id'] != '') ? $order['gcm_reg_id'] : "";

            $social_referrer			       = 	(isset($data['social_referrer']) && $data['social_referrer'] != '') ? $data['social_referrer'] : "";
            $transacted_after			       = 	(isset($data['transacted_after']) && $data['transacted_after'] != '') ? $data['transacted_after'] : "";
            $referrer_object			       = 	(isset($data['referrer_object']) && $data['referrer_object'] != '') ? $data['referrer_object'] : "";

            $age                                =   (isset($data['age']) && $data['age'] != '') ? $data['age'] : "";
            $injury                             =   (isset($data['injury']) && $data['injury'] != '') ? $data['injury'] : "";

            if($device_type != '' && $gcm_reg_id != ''){

                $regData = array();

                $regData['customer_id'] = $customer_id;
                $regData['reg_id'] = $gcm_reg_id;
                $regData['type'] = $device_type;

                $this->utilities->addRegId($regData);
            }


            // $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
            // $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
            // $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;

            $description =  $what_i_should_carry = $what_i_should_expect = $service_category = '';
            $service_slug = null;
            if($service_id != ''){
                $serviceArr 				       = 	Service::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('category')->with('subcategory')->find($service_id);
                if(!empty($serviceArr['slug'])) {
                    $service_slug = $serviceArr['slug'];
                }
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

                if((isset($serviceArr['category']['name']) && $serviceArr['category']['name'] != '')){
                    $service_category = $serviceArr['category']['name'];
                }else{
                    if((isset($serviceArr['subcategory']['name']) && $serviceArr['subcategory']['name'] != '')){
                        $service_category = $serviceArr['subcategory']['name'];
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

            $google_pin		            =	$this->googlePin($finder_lat,$finder_lon);
            $customer_profile_url       =   $this->customerProfileUrl($customer_email);
            $finder_url                 =   $this->vendorUrl($finder['slug']);
            if(isset($serviceArr) && isset($serviceArr['category']) && $serviceArr['category']['_id'] != ''){
                $calorie_burn           =   $this->getCalorieBurnByServiceCategoryId($serviceArr['category']['_id']);
            }else{
                $calorie_burn           =   300;
            }

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
            $finder_flags                       =   isset($finder['flags'])  ? $finder['flags'] : new stdClass();

            $service_name				       =	strtolower($data['service_name']);
            if(isset($data['schedule_slot'])){
                $schedule_date				       =	date('Y-m-d 00:00:00', strtotime($slot_date));
                $schedule_date_time			       =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();
            }else{
                $schedule_date				       =	date('Y-m-d 00:00:00', strtotime($data['schedule_date']));
                $schedule_date_time				       =	date('Y-m-d 00:00:00', strtotime($data['schedule_date']));
            }

            $code						       =	random_numbers(5);
            $vendor_code                       =    random_numbers(5);
            $device_id					       = 	(!empty($data['device_id']) && $data['device_id'] != '') ? $data['device_id'] : "";
            $premium_session 			       =	(!empty($data['premium_session'])) ? (boolean) $data['premium_session'] : false;
            $reminder_need_status 		       =	(!empty($data['reminder_need_status'])) ? $data['reminder_need_status'] : '';
            $additional_info			       = 	(!empty($data['additional_info']) && $data['additional_info'] != '') ? $data['additional_info'] : "";


            $orderid    =   (int) $data['order_id'];
            // $order      =   Order::findOrFail($orderid);
            $type       =   $order->type;

            if($type == "vip_booktrials"){
                $kit_enabled = true;
            }else{
                $kit_enabled = false;
            }

            $medical_detail                     =   (isset($order->medical_detail) && $order->medical_detail != '') ? $order->medical_detail : "";
            $medication_detail                  =   (isset($order->medication_detail) && $order->medication_detail != '') ? $order->medication_detail : "";
            $medical_condition                  =   (isset($order->medical_condition) && $order->medical_condition != '') ? $order->medical_condition : "";
            $physical_activity_detail           =   (isset($order->physical_activity_detail) && $order->physical_activity_detail != '') ? $order->physical_activity_detail : "";
            $note_to_trainer                     =   (isset($order->note_to_trainer) && $order->note_to_trainer != '') ? $order->note_to_trainer : "";
            $amount                             =   (isset($order->amount) && $order->amount != '') ? $order->amount : "";
            $amount_finder                      =   (isset($order->amount_finder) && $order->amount_finder != '') ? $order->amount_finder : "";
            $membership                      =   isset($order->membership) ? (object)$order->membership : new stdClass();

            if(isset($membership->reward_ids) && count($membership->reward_ids)>=0){
                
                $reward = Reward::find($membership->reward_ids[0], array('title'));
                
                if($reward){
                    $membership->reward = $reward;
                }
            }

            $membership =json_decode(json_encode($membership), True);
            

            $finder_location_slug               =   (isset($finder['location']['slug']) && $finder['location']['slug'] != '') ? $finder['location']['slug'] : "";

            $service_link = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$finder_slug."/".$service_id);
            $srp_link =  $this->utilities->getShortenUrl(Config::get('app.website')."/".$finder_city_slug."/".$finder_location_slug."/fitness");
            $vendor_notify_link =  $this->utilities->getShortenUrl(Config::get('app.business')."/trial/cancel/".$booktrialid."/".$finderid);
            $pay_as_you_go_link =  $this->utilities->getShortenUrl(Config::get('app.website')."/workout/".$finder_city_slug."?regions=".$finder_location_slug);
            //$profile_link = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$customer_email);
            $profile_link = Config::get('app.website_deeplink')."/profile?email=".$customer_email;
            $vendor_link = $this->utilities->getShortenUrl(Config::get('app.website')."/".$finder_slug);


            $addUpdateDevice = [];

            $addUpdateDevice['device_type'] = (isset($order['device_type']) && $order['device_type'] != '') ? $order['device_type'] : "";
            $addUpdateDevice['device_model'] = (isset($order['device_model']) && $order['device_model'] != '') ? $order['device_model'] : "";
            $addUpdateDevice['app_version'] = (isset($order['app_version']) && $order['app_version'] != '') ? $order['app_version'] : "";
            $addUpdateDevice['os_version'] = (isset($order['os_version']) && $order['os_version'] != '') ? $order['os_version'] : "";
            $addUpdateDevice['reg_id'] = (isset($order['gcm_reg_id']) && $order['gcm_reg_id'] != '') ? $order['gcm_reg_id'] : "";

            foreach ($addUpdateDevice as $header_key => $header_value) {

                if($header_key != ""){
                   $data[$header_key]  = $header_value;
                }
                
            }

            $pre_trial_vendor_confirmation = (isset($finderid) && in_array($finderid, Config::get('app.trial_auto_confirm_finder_ids'))) ? 'confirmed' : 'yet_to_connect';

            $booktrial_link = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$finder_slug."/".$service_id);
            $workout_article_link = $this->utilities->getShortenUrl(Config::get('app.website')."/article/complete-guide-to-help-you-prepare-for-the-first-week-of-your-workout");
            $download_app_link = Config::get('app.download_app_link');
            $diet_plan_link = $this->utilities->getShortenUrl(Config::get('app.website')."/diet-plan");

            $booktrialdata = array(
                'booktrialid'                   =>      intval($booktrialid),
                'premium_session'               =>      $premium_session,
                'reminder_need_status'          =>      $reminder_need_status,
                'booktrialid'			       =>		intval($booktrialid),
                'campaign'				       =>		$campaign,
                'premium_session' 		       =>		$premium_session,
                'reminder_need_status' 	       =>		$reminder_need_status,
                'logged_in_customer_id'         =>      !empty($order["logged_in_customer_id"]) ? $order["logged_in_customer_id"] : -1,
                'customer_id' 			       =>		$customer_id,
                'customer_name' 		       =>		$customer_name,
                'customer_email' 		       =>		$customer_email,
                'customer_phone' 		       =>		$customer_phone,
                'customer_profile_url' 		   =>		$customer_profile_url,
                'fitcard_user'			       =>		$fitcard_user,
                'type'					       =>		$type,

                'calorie_burn'                  =>      $calorie_burn,
                'finder_url'                    =>      $finder_url,
                'finder_id'                     =>      $finderid,
                'finder_name'                   =>      $finder_name,
                'finder_slug'                   =>      $finder_slug,
                'finder_location'               =>      $finder_location,
                'finder_address'                =>      $finder_address,
                'finder_lat'                    =>      $finder_lat,
                'finder_lon'                    =>      $finder_lon,
                'finder_photos'                 =>      $finder_photos,
                'finder_flags'                  =>      $finder_flags,
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
                'service_slug'                  =>      $service_slug,
                'schedule_slot_start_time'      =>      $schedule_slot_start_time,
                'schedule_slot_end_time'        =>      $schedule_slot_end_time,
                'schedule_date'                 =>      $schedule_date,
                'schedule_date_time'            =>      $schedule_date_time,
                'schedule_slot'                 =>      $schedule_slot,
                'going_status'                  =>      1,
                'going_status_txt'              =>      'going',
                'code'                          =>      $code,
                'vendor_code'                   =>      $vendor_code,
                'device_id'                     =>      $device_id,
                'booktrial_type'                =>      'auto',
                'booktrial_actions'             =>      'call to confirm trial',
                'source'                        =>      $source,
                'origin'                        =>      'auto',
                'additional_info'               =>      $additional_info,
                'amount'                        =>      $amount,
                'amount_finder'                 =>      $amount_finder,
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
                'medication_detail'             =>      $medication_detail,
                'physical_activity_detail'      =>      $physical_activity_detail,
                'cleartrip_count'               =>      $cleartrip_count,
                'trial_count'                   =>      $trial_count,
                'before_three_month_trial_count' =>     $before_three_month_trial_count,
                'token'                         =>      random_number_string(),
                'service_category'              =>      $service_category,
                'service_link'                  =>      $service_link,
                'srp_link'                      =>      $srp_link,
                'vendor_notify_link'            =>      $vendor_notify_link,
                'pay_as_you_go_link'            =>      $pay_as_you_go_link,
                'profile_link'                  =>      $profile_link,
                'vendor_link'                   =>      $vendor_link,
                'finder_location_slug'          =>      $finder_location_slug,
                'order_id'                      =>      $orderid,
                'membership'                    =>      $membership,
                'pre_trial_vendor_confirmation' =>      $pre_trial_vendor_confirmation,
                'vendor_kiosk'                  =>      isKioskVendor($finderid),
                'booktrial_link'                =>      $booktrial_link,
                'workout_article_link'          =>      $workout_article_link,
                'download_app_link'             =>      $download_app_link,
                'diet_plan_link'                =>      $diet_plan_link,
                'pre_trial_status'              =>      'yet_to_connect',
                'ask_review'                    =>      true,
            );


            if(!empty($order['pass_order_id'])) {
                $booktrialdata['pass_order_id'] = $order['pass_order_id'];
            }

            if(!empty($order['pass_booking'])) {
                $booktrialdata['pass_booking'] = $order['pass_booking'];
            }

            if(!empty($order['pass_credits'])) {
                $booktrialdata['pass_credits'] = $order['pass_credits'];
            }

            if(!empty($order['pass_type'])) {
                $booktrialdata['pass_type'] = $order['pass_type'];
            }

            $customer = Customer::where('_id', $customer_id)->first();
            
            if(!empty($customer['corporate_id'])) {
                $booktrialdata['corporate_id'] = $customer['corporate_id'];
            }
            
            if(!empty($customer['external_reliance'])) {
                $booktrialdata['external_reliance'] = $customer['external_reliance'];

            }

            if(!empty($data['studio_extended_validity_order_id'])){
                $booktrialdata['studio_extended_validity_order_id'] = $data['studio_extended_validity_order_id'];
            }

            if(!empty($data['studio_extended_session'])){
                $booktrialdata['studio_extended_session'] = $data['studio_extended_session'];
            }

            if(!empty($data['communications'])){
                $booktrialdata['communications'] = $data['communications'];
            }

            if(isset($extended_validity_no_of_sessions)){
                $booktrialdata['no_of_sessions'] = $extended_validity_no_of_sessions;
            }

            if(isset($extended_validity_sessions_booked)){
                $booktrialdata['sessions_booked'] = $extended_validity_sessions_booked;
            }
            if(isset($session_pack_comm)){
                $booktrialdata['session_pack_comm'] = $session_pack_comm;
            }


            if(!empty($data['third_party'])) {
                $booktrialdata['third_party'] = $data['third_party'];
                $booktrialdata['third_party_details'] = $data['third_party_details'];
                $booktrialdata['third_party_acronym'] = $data['third_party_acronym'];
            }
            if(!empty($extended_validity_order['_id'])){
                $booktrialdata['extended_validity_order_id'] = $extended_validity_order['_id'];
            }

            $session_count = Booktrial::where('customer_id',$customer_id)->count();

            if($session_count == 0){
                $booktrialdata['first_booking'] = true;
            }

            if(!empty($order['assisted_by'])){
                $booktrialdata['assisted_by'] = $order['assisted_by'];
            }

            if(!empty($order['lat'])){
                $booktrialdata['lat'] = $order['lat'];
            }
            
            if(!empty($order['lon'])){
                $booktrialdata['lon'] = $order['lon'];
            }
            
            if(!empty($order['customer_quantity'])){
                $booktrialdata['customer_quantity'] = $order['customer_quantity'];
            }

            if(!empty($order['ratecard_remarks'])){
                $booktrialdata['ratecard_remarks'] = $order['ratecard_remarks'];
            }

            if(!empty($order['multifit'])){
                $booktrialdata['multifit'] = $order['multifit'];
            }

            if(!empty($order['jgs'])){
                $booktrialdata['jgs'] = $order['jgs'];
            }

            if(!empty($order['manual_order'])){
                $booktrialdata['manual_order'] = $order['manual_order'];
            }

            if(!empty($order['punching_order'])){
                $booktrialdata['punching_order'] = $order['punching_order'];
            }
            
            if(!empty($order['first_session_free'])){
                $booktrialdata['first_session_free'] = $order['first_session_free'];
            }
            
            if(!empty($order['checkin_booking'])){
                $booktrialdata['checkin_booking'] = $order['checkin_booking'];
            }

            if(isset($order['corporate_coupon']) && $order['corporate_coupon'] == true){
                $booktrialdata['corporate_coupon'] = $order['corporate_coupon'];
            }
            
            if(!empty($order['coupon_code']) && !empty($order['coupon_discount_amount'])){
                $booktrialdata['coupon_code'] = $order['coupon_code'];
                $booktrialdata['coupon_discount_amount'] = $order['coupon_discount_amount'];
            }

            if(!empty($order['service_flags'])){
                $booktrialdata['service_flags'] = $order['service_flags'];
            }

            if(!empty($order['coupon_flags'])){
                $booktrialdata['coupon_flags'] = $order['coupon_flags'];
            }

            if(!empty($order['ratecard_flags'])){
                $booktrialdata['ratecard_flags'] = $order['ratecard_flags'];
            }

            $is_tab_active = isTabActive($booktrialdata['finder_id']);

            if($is_tab_active){
                $booktrialdata['is_tab_active'] = true;
            }

            if(isset($order['recommended_booktrial_id']) && $order['recommended_booktrial_id'] != ""){
                $booktrialdata['recommended_booktrial_id'] = (int)$order['recommended_booktrial_id'];
            }

            if(isset($order['servicecategory_id']) && $order['servicecategory_id'] != ""){
                $booktrialdata['servicecategory_id'] = (int)$order['servicecategory_id'];
            }

            $workout_session_fields = ['customers_list', 'pay_later'];

            foreach($workout_session_fields as $field){
                if(isset($order[$field])){
                    $booktrialdata[$field] = $order[$field];
                }
            }

            if(isset($order['pay_later']) && $order['pay_later']){
                
                $booktrialdata['payment_done'] = false;
            }
            
            if(isset($order['booking_for_others']) && $order['booking_for_others'] != ""){
              $booktrialdata['booking_for_others'] = $order['booking_for_others'];
            }

            if ($medical_detail != "" && $medication_detail != "") {

                $customer_info = new CustomerInfo();
                $response = $customer_info->addHealthInfo($booktrialdata);
            }

            if(isset($order['promotional_notification_id']) && $order['promotional_notification_id'] != ""){
                $booktrialdata['promotional_notification_id'] = $order['promotional_notification_id'];
            }

            if(isset($order['promotional_notification_label']) && $order['promotional_notification_label'] != ""){
                $booktrialdata['promotional_notification_label'] = $order['promotional_notification_label'];
            }
            
            if(isset($order['amount_customer']) && $order['amount_customer'] != ""){
                $booktrialdata['amount_customer'] = $order['amount_customer'];
            }
            if(isset($order['spin_coupon']) && $order['spin_coupon'] != ""){
                $booktrialdata['spin_coupon'] = $order['spin_coupon'];
            }
            if(isset($order['coupon_discount_percent']) && $order['coupon_discount_percent'] != ""){
                $booktrialdata['coupon_discount_percent'] = $order['coupon_discount_percent'];
            }

            // Add Cashback and rewards to booktrialdata if exist in orders....
            isset($order['cashback']) ? $booktrialdata['cashback'] = $order['cashback']:null;
            isset($order['reward_ids']) ? $booktrialdata['reward_ids'] = $order['reward_ids']:null;

            if(isset($data['customofferorder_id']) && $data['customofferorder_id'] != ""){

                $booktrialdata['customofferorder_id'] = $data['customofferorder_id'];

                $customofferorder   =   Fitapicustomofferorder::find($data['customofferorder_id']);

                if(isset($customofferorder->validity) && $customofferorder->validity != ""){

                    $booktrialdata['customofferorder_expiry_date'] =   date("Y-m-d H:i:s", strtotime("+".$customofferorder->validity." day", strtotime($customofferorder->created_at)));
                    $booktrialdata['customofferorder_validity'] = $customofferorder->validity;
                }

            }

            if(isset($data['myreward_id']) && $data['myreward_id'] != ""){

                $booktrialdata['myreward_id'] = (int)$data['myreward_id'];

                $myreward = Myreward::find((int)$data['myreward_id']);

                if($myreward){
                    $booktrialdata['reward_balance'] = $myreward->quantity - $myreward->claimed;
                }
            }

            //give fitcash+ for first workout session
            $give_fitcash_plus = false;

            // if($type == "workout-session" && !isset($data['myreward_id']) && isset($order['amount']) && $order['amount'] >= 500){
            //     $give_fitcash_plus = true;
            // }

            if($give_fitcash_plus){

                $walletData = array(
                    "customer_id"=> $customer_id,
                    "amount"=> 250,
                    "amount_fitcash" => 0,
                    "amount_fitcash_plus" => 250,
                    "type"=>'FITCASHPLUS',
                    "description"=>'Added FitCash+ on Workout Session amount - 250',
                    "order_id"=>$order->_id,
                    'entry'=>'credit',
                );

                $this->utilities->walletTransaction($walletData);
            }

            if(!isset($booktrialdata['third_party_details']) && empty($booktrialdata['studio_extended_validity_order_id'])){
                $booktrialdata['give_fitcash_plus'] = $give_fitcash_plus;

                $booktrialdata['surprise_fit_cash'] = $this->utilities->getFitcash($booktrialdata);

                $this->utilities->demonetisation($order);

                $this->customerreward->giveCashbackOrRewardsOnOrderSuccess($order);
            }

            if(!empty($booktrialdata['studio_extended_validity_order_id'])){
                $booktrialdata['surprise_fit_cash'] = 0;
            }

            if(isset($order->booktrial_id)){

                if(isset($order->finder_slug) && isset($order->service_id) && isset($order->booktrial_id) ){
                    $finder_slug                        =   trim($order->finder_slug);
                    $service_id                         =   intval($order->service_id);
                    $booktrial_id                       =   intval($order->booktrial_id);
                    $rebook_trial_url                   =   $this->rebookTrialUrl($finder_slug, $service_id, $booktrial_id);
                    $booktrialdata['rebook_trial_url']  =   $rebook_trial_url;
                }

                $booktrial = Booktrial::find((int)$order->booktrial_id);
                $trialbooked = $booktrial->update($booktrialdata);
            }else{


                if(isset($finder_slug) && isset($service_id) && isset($booktrialid) ){
                    $finder_slug                        =   trim($finder_slug);
                    $service_id                         =   intval($service_id);
                    $booktrial_id                       =   intval($booktrialid);
                    $rebook_trial_url                   =   $this->rebookTrialUrl($finder_slug, $service_id, $booktrial_id);
                    $booktrialdata['rebook_trial_url']  =   $rebook_trial_url;
                }

                $booktrial = new Booktrial($booktrialdata);
                $booktrial->_id = (int) $booktrialid;
                $trialbooked = $booktrial->save();

                Log::info('$trialbooked : '.json_encode($trialbooked));
            }

            // if((isset($order['pay_later']) && $order['pay_later'])){
                
            //     $previous_pay_later_session = Paylater::where('customer_id', $booktrial->customer_id)->first();

            //     if($previous_pay_later_session){
                    
            //         $trial_ids = $previous_pay_later_session->trial_ids;
            //         array_push($trial_ids, $booktrial->_id);
            //         $previous_pay_later_session->trial_ids = $trial_ids;
            //         $previous_pay_later_session->save();
                
            //     }else{

            //         $pay_later = new Paylater();
            //         $pay_later->customer_id = $booktrial->customer_id;
            //         $pay_later->trial_ids = [$booktrial->_id];
            //         $pay_later->save();

            //     }


            // }

            if(!empty($order['qrcodepayment']) || !empty($booktrialdata['checkin_booking']) ){
                $booktrial['qrcodepayment'] = true;
                $booktrial['abort_delay_comm'] = true;
                $booktrial['post_trial_status'] = 'attended';
                $booktrial['post_trial_status_updated_by_qrcode'] = time();
                $booktrial['post_trial_status_date'] = time();
                
                if(empty($order['pay_later']) && !empty($order['qrcodepayment'])){

                    $fitcash = $this->utilities->getFitcash($booktrial->toArray());
                    $req = array(
                        "customer_id"=>$booktrial['customer_id'],
                        "trial_id"=>$booktrial['_id'],
                        "amount"=> $fitcash,
                        "amount_fitcash" => 0,
                        "amount_fitcash_plus" => $fitcash,
                        "type"=>'CREDIT',
                        'entry'=>'credit',
                        'validity'=>time()+(86400*21),
                        'description'=>"Added FitCash+ on Session Attendance at ".ucwords($booktrial['finder_name'])." Expires On : ".date('d-m-Y',time()+(86400*21)),
                    );
                    $this->utilities->walletTransaction($req);
                }

            }
            
            if(!(isset($order['pay_later']) && $order['pay_later'])){
                array_set($orderData, 'status', '1');
                array_set($orderData, 'order_action', 'bought');
                array_set($orderData, 'success_date', date('Y-m-d H:i:s',time()));

                if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
                    array_set($orderData, 'secondary_payment_mode', 'payment_gateway_membership');
                }

                if(!empty($order['pass_order_id'])) {
                    Order::$withoutAppends = true;
                    $passOrder = Order::where('_id', $order['pass_order_id'])->first();
                    if(!empty($passOrder)) {
                        if(empty($passOrder->onepass_sessions_used)) {
                            $passOrder->onepass_sessions_used = 0;
                        }
                        $passOrder->onepass_sessions_used += 1;
                        $passOrder->update();
                    }
                }
            }
            
             
            $after_booking_response = [];
            
            try{
                $after_booking_response = $this->utilities->afterTranSuccess($booktrial->toArray(), 'booktrial');
            }catch(Exception $e){
                Log::info("afterTranSuccess error", [$e]);
            }    
                
            Log::info("after_booking_response");
            Log::info($after_booking_response);

            if(!empty($after_booking_response['checkin'])){
                if(!empty($after_booking_response['checkin']['status']) && $after_booking_response['checkin']['status'] == 200 && !empty($after_booking_response['checkin']['checkin']['_id'])){
                    $booktrial->checkin = $after_booking_response['checkin']['checkin']['_id'];
                }
            }

            if(!empty($after_booking_response['checkin']['checkin_response'])){
                unset($after_booking_response['checkin']['checkin_response']['milestones']);
                unset($after_booking_response['checkin']['checkin_response']['image']);
                unset($after_booking_response['checkin']['checkin_response']['checkin']);
                $orderData['checkin_response'] = $after_booking_response['checkin']['checkin_response'];
            }

            if(!empty($after_booking_response['loyalty_registration']['status']) && $after_booking_response['loyalty_registration']['status'] == 200){
                $booktrial->loyalty_registration = true;
                $orderData['loyalty_registration'] = true;
            }

            array_set($orderData, 'booktrial_id', (int)$booktrialid);
            if(!empty($data['parent_payment_id_paypal'])){
                array_set($orderData, 'parent_payment_id_paypal', $data['parent_payment_id_paypal']);
            }

            if(!empty($data['payment_id_paypal'])){
                array_set($orderData, 'payment_id_paypal', $data['payment_id_paypal']);
            }
            
            $order->update($orderData);

            if(!empty($order->vendor_price)){
                $customer_quantity = !empty($order->customer_quantity) ? intval($order->customer_quantity) : 1;
                $order->original_amount_finder = $order->amount_finder;
                $order->amount_finder = $order->vendor_price * $customer_quantity;
                $booktrial->amount_finder = $order->vendor_price * $customer_quantity;

                $order->update();
            
            }else if(!empty($order->ratecard_price_wo_offer)){
                
                $customer_quantity = !empty($order->customer_quantity) ? intval($order->customer_quantity) : 1;
                $order->original_amount_finder = $order->amount_finder;
                $order->amount_finder = $order->ratecard_price_wo_offer * $customer_quantity;
                $booktrial->amount_finder = $order->ratecard_price_wo_offer * $customer_quantity;

                $order->update();
            }
            
            $booktrial->update();

            // Give Rewards / Cashback to customer based on selection, on purchase success......
            

        } catch(ValidationException $e){

            // If booktrial query fail updates error message
            $orderid 	=	(int) $data['order_id'];
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

            $orderid = (int) $data['order_id'];
            $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@sendCommunication', array('booktrial_id'=>$booktrialid),Config::get('app.queue'));
            $booktrial->update(array('redis_id'=>$redisid));

        }

        /*if($trialbooked == true && $campaign != ''){
            $this->attachTrialCampaignToCustomer($customer_id,$campaign,$booktrialid);
        }*/

        $this->utilities->sendDemonetisationCustomerSms($order);

        $this->utilities->addAmountToReferrer($order);
        
        Log::info('Customer Book Trial : '.json_encode(array('book_trial_details' => Booktrial::findOrFail($booktrialid))));

        if(isset($data['temp_id'])){
            $delete = Tempbooktrial::where('_id', $data['temp_id'])->delete();
        }

        $resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'message' => "Session Booked Sucessfully", 'code' => $code);        
        Log::info(" info ".print_r("AAAYA",true));
        return Response::json($resp,200);
    }

     public function sendCommunication($job,$data){
        Log::info("sendCommunication===========================");
        if($job){
            $job->delete();
        }

        try{

            $booktrial_id = (int)$data['booktrial_id'];

            $booktrial = Booktrial::findOrFail($booktrial_id);

            // $booktrial->qrcode = $this->utilities->createQrCode($booktrial['code']);
            $booktrial->qrcode = "";
            $booktrial->pps_blockscreen=Config::get('app.website_deeplink');
            $booktrial->update();


            $dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date','followup_date','auto_followup_date');
            $unset_keys = [];
    
	        foreach ($dates as $key => $value) {
                if(isset($booktrial[$value])){
                    if($booktrial[$value] == "-" || $booktrial[$value] == ""){

                        // $booktrial->unset($value);
                        array_push($unset_keys, $value);
                    }
                }

            }
            
            if(count($unset_keys)>0){
                $booktrial->unset($unset_keys);
            }

            $this->deleteTrialCommunication($booktrial);

            $this->firstTrial($booktrial->toArray()); // first trial communication

            $booktrialdata = $booktrial->toArray();

            $booktrialdata['service_steps'] = 300;
            if(isset($booktrialdata['servicecategory_id']) && $booktrialdata['servicecategory_id'] != ''){
                $service_cat_steps_map = Config::get('health_config.service_cat_steps_map');
                if(in_array($booktrialdata['servicecategory_id'], array_keys($service_cat_steps_map))){
                    $booktrialdata['service_steps'] = $service_cat_steps_map[$booktrialdata['servicecategory_id']];
                }
            }

            $currentDateTime 			       =	\Carbon\Carbon::now();
            $scheduleDateTime 			       =	\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)));


            $delayReminderAfter3Hours      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(60 *3);
            //add check for communication
            $send_communication["fitternity_email_postTrialStatusUpdate"] = $this->findermailer->postTrialStatusUpdate($booktrialdata, $delayReminderAfter3Hours);

            $currentScheduleDateDiffMin = $currentDateTime->diffInMinutes($scheduleDateTime, false);

            $schedule_date_time_hour = intval(date('H',strtotime($booktrial->schedule_date_time)));

            $current_hour = intval(date('H',time()));

            // $delayReminderbefore2Hours      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(-60 * 2);
    
            // $send_communication['customer_sms_offhours_confirmation'] = $this->customersms->offhoursConfirmation($booktrialdata, $delayReminderbefore2Hours);
            if(empty($booktrial->qrcodepayment)){

                if( $this->isWeekend(time()) && in_array(date('l', strtotime($booktrial->schedule_date_time)), Config::get('app.trial_comm.end_weekend')) && $schedule_date_time_hour < Config::get('app.trial_comm.off_hours_end_time')){
                    Log::info("Scheduling sunday 8pm");
                    $delayReminderPrevDaySunday      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 20:00:00',strtotime($booktrial->schedule_date_time)))->addDays(-1);
        
                    $send_communication['customer_sms_offhours_confirmation'] = $this->customersms->offhoursConfirmation($booktrialdata, $delayReminderPrevDaySunday);
                
                // }else if( $this->isOffHour($schedule_date_time_hour) &&  $this->isOffHour($current_hour) && $currentScheduleDateDiffMin <= 15*60){
                }else if( $this->isOffHour($schedule_date_time_hour) &&  $this->isOffHour($current_hour) && (strtotime($booktrial->schedule_date_time) - time() <= 15*60*60)){
                    $booktrial->off_hours = true;
                    if(time() < strtotime(date('Y-m-d '.Config::get('app.trial_comm.offhours_fixed_time_1').':00:00', time())) && strtotime($booktrial->schedule_date_time) > strtotime(date('Y-m-d '.Config::get('app.trial_comm.offhours_fixed_time_1').':00:00', time()))){
                        
                        if($schedule_date_time_hour >= 8 && $schedule_date_time_hour < 11){
                            Log::info("Scheduling offhours 2 hours before");
                            $delayReminderbefore2Hours      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(-60 * Config::get('app.trial_comm.offhours_scheduled_td_hours'));
        
                            $send_communication['customer_sms_offhours_confirmation'] = $this->customersms->offhoursConfirmation($booktrialdata, $delayReminderbefore2Hours);
        
                        }else if($schedule_date_time_hour >= 6 && $schedule_date_time_hour < 8){
                            Log::info("Scheduling offhours at 10 prev day");
                            
                            $delayReminderPrevDay      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d '.Config::get('app.trial_comm.offhours_fixed_time_1').':00:00',strtotime($booktrial->schedule_date_time)))->addDays(-1);
        
                            $send_communication['customer_sms_offhours_confirmation'] = $this->customersms->offhoursConfirmation($booktrialdata, $delayReminderPrevDay);
    
                        }else{
                            Log::info("Scheduling offhours 5 mins after booking");
                        
                            $delayReminderAfter5mins      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',time()))->addMinutes(Config::get('app.trial_comm.offhours_instant_td_mins'));
            
                            $send_communication['customer_sms_offhours_confirmation'] = $this->customersms->offhoursConfirmation($booktrialdata, $delayReminderAfter5mins);
                        }
                    }else{
    
                        Log::info("Scheduling offhours 5 mins after booking");
                        
                        $delayReminderAfter5mins      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',time()))->addMinutes(Config::get('app.trial_comm.offhours_instant_td_mins'));
        
                        $send_communication['customer_sms_offhours_confirmation'] = $this->customersms->offhoursConfirmation($booktrialdata, $delayReminderAfter5mins);
                    
                    }
                    
                }
            }

            $customer_email_messageids 	=  $finder_email_messageids  =	$customer_sms_messageids  =  $finder_sms_messageids  =  $customer_notification_messageids  =  array();

            if($booktrial->going_status_txt == "rescheduled"){
                if (!isset($booktrialdata['third_party_details'])){
                    $send_communication["customer_email_instant"] = $this->customermailer->rescheduledBookTrial($booktrialdata);
                }
                $send_communication["customer_sms_instant"] = $this->customersms->rescheduledBookTrial($booktrialdata);
                $send_communication["finder_email_instant"] = $this->findermailer->rescheduledBookTrial($booktrialdata);
                $send_communication["finder_sms_instant"] = $this->findersms->rescheduledBookTrial($booktrialdata);

            }else{

                if (!isset($booktrialdata['third_party_details'])){
                    $send_communication["customer_email_instant"] = $this->customermailer->bookTrial($booktrialdata);
                
                
                    if(!empty($booktrialdata)&&!empty($booktrialdata['type'])&&$booktrialdata['type']=='workout-session'&&!empty($booktrialdata['customer_id'])&&!empty($booktrialdata['_id']))
                    {   
                        
                        $alreadyWorkoutTaken=Order::where("booktrial_id","!=",(int)$booktrialdata['_id'])->where("type","=",'workout-session')->where("status","=","1")->where("created_at",">=",new DateTime("2018/04/23"))->where("customer_id","=",(int)$booktrialdata['customer_id'])->first();
                        Log::info(" alreadyWorkoutTaken ".print_r($alreadyWorkoutTaken,true));
                        if(empty($alreadyWorkoutTaken))
                            $onepassHoldCustomer = $this->utilities->onepassHoldCustomer();
					        if(!(!empty($onepassHoldCustomer) && $onepassHoldCustomer)){
                                $send_communication["customer_email_instant_workoutlevelstart"] = $this->customermailer->workoutSessionInstantWorkoutLevelStart($booktrialdata);
                            }       
                    }
                    
                    if(isset($booktrialdata['is_tab_active'])&&$booktrialdata['is_tab_active']!=""&&$booktrialdata['is_tab_active']==true&&$booktrialdata['type']=='workout-session')
                    {
                        
                        Log::info(" booktrialdata 1222".print_r($booktrialdata,true));
                        $booktrial->pps_cashback=$this->utilities->getWorkoutSessionLevel((int)$booktrialdata['customer_id'])['current_level']['cashback'];
                        if(isset($booktrial->pps_cashback)&&$booktrial->pps_cashback!="")
                            $booktrial->pps_fitcash=(((int)$booktrial->pps_cashback/100)*$booktrial->amount);
                            $booktrialdata=$booktrial->toArray();
                            Log::info(" booktrialdata 23 ".print_r($booktrialdata,true));
                    }
                }
                // abg check needed
                // Log::info('before abg check', [$booktrialdata]);
                if  (isset($booktrialdata['third_party_details'])){
                    Log::info('$booktrialdata->third_party_details is set', [$booktrialdata['third_party_details']]);
                    $send_communication["customer_sms_instant_abg"] = $this->customersms->bookTrial($booktrialdata);    
                }
                else {
                    Log::info('$booktrialdata->third_party_details is not set');
                    $send_communication["customer_sms_instant"] = $this->customersms->bookTrial($booktrialdata);
                }
                $send_communication["finder_email_instant"] = $this->findermailer->bookTrial($booktrialdata);
                $send_communication["finder_sms_instant"] = $this->findersms->bookTrial($booktrialdata);
            }

            // if($booktrialdata['type'] == 'workout-session'){

            $trailBefore10min = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(10);
            if(!isset($booktrialdata['third_party_details'])){
                $send_communication["customer_notification_before10min"] = $this->customernotification->bookTrialReminderBefore10Min($booktrialdata, $trailBefore10min);
            }
            // $afterTwoDays = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addDays(2);
            
            // $send_communication["customer_notification_after2days"] = $this->customernotification->reviewReminder($booktrialdata, $afterTwoDays);

            // }

            //Send Reminder Notiication (Email, Sms) Before 12 Hour To Customer

             //$before12HourDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime        ($booktrial->created_at)))->addMinutes(30);

            if($currentScheduleDateDiffMin >= (60 * 12)){

                $delayReminderTimeBefore12Hour      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(60 * 12);

                $before12HourDateTime=$delayReminderTimeBefore12Hour ;
                $hour = (int) date("G", strtotime($booktrial->schedule_date_time));

                /* if($hour > 19 &&$hour <= 24  ){
                	
		                // 				do nothing let it go as it is .
                }
                else */ if($hour > 11 &&$hour <= 19  ){
                	
                	//$delayReminderTimeBefore12Hour      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(60 * 4);
                	$before12HourDateTime=\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(60 * 4);
                	// 				do nothing let it go as it is .
                	
                }
                /* else if($hour > 10 &&$hour <= 11 ){
                	
                 	//$delayReminderTimeBefore12Hour      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(60 * 12);
                 	//$before12HourDateTime=    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(60 * 12);
                	// 				do nothing let it go as it is .
                } */
                /* if($hour >=5 && $hour <= 10){

                    $before12HourDateTime = $delayReminderTimeBefore12Hour;

                } */
                
                
                if(!isset($booktrialdata['third_party_details'])){
                    $send_communication["customer_email_before12hour"] = $this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $before12HourDateTime);     
                    
                    $send_communication["customer_notification_before12hour"] = $this->customernotification->bookTrialReminderBefore12Hour($booktrialdata, $before12HourDateTime);
                    
                    $send_communication["customer_sms_before12hour"] = $this->customersms->bookTrialReminderBefore12Hour($booktrialdata, $before12HourDateTime);
                }
            }
            else if(!isset($booktrialdata['third_party_details'])){
                $mins = 30;
                if($currentScheduleDateDiffMin < 60){
                    $mins = 0;
                }
                $reminderTimeAfterHalfHour 	       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes($mins);
                $send_communication["customer_email_before12hour"] = $this->customermailer->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfterHalfHour);     
                $send_communication["customer_notification_before12hour"] = $this->customernotification->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfterHalfHour);
                $send_communication["customer_sms_before12hour"] = $this->customersms->bookTrialReminderBefore12Hour($booktrialdata, $reminderTimeAfterHalfHour);
            }



            if($currentScheduleDateDiffMin >= (60 * 6)){

                $delayReminderTimeBefore6Hour      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(60 * 6);
                if(isset($booktrialdata['type'])&&$booktrialdata['type']!=""&&$booktrialdata['type']!='workout-session')
                $send_communication["finder_sms_before6hour"] = $this->findersms->bookTrialReminderBefore6Hour($booktrialdata, $delayReminderTimeBefore6Hour);

            }

            
            if($currentScheduleDateDiffMin >= (180))
            {
                $booktrialdata['poc'] = "vendor";
                $booktrialdata['poc_no'] = $booktrialdata['finder_poc_for_customer_no'];

                $before3HourDateTime =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(180);

                $hour = (int) date("G", strtotime($booktrial->schedule_date_time));

                if($hour >= 10 && $hour <= 22){
                    $booktrialdata['poc'] = "fitternity";
                    $booktrialdata['poc_no'] = Config::get('app.contact_us_customer_number');
                }

                if($hour <10&&$hour>23)
                {
                	
                	$before3HourDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(60);
                	// as it is
               }
                if(isset($booktrialdata['third_party_details'])){
                    Log::info('$booktrialdata->third_party_details is set customer_email_before3hour_abg: ', [$booktrialdata['third_party_details']]);
                    $send_communication["customer_sms_before3hour_abg"] = $this->customersms->bookTrialReminderBefore3Hour($booktrialdata, $before3HourDateTime);
                }
                else {
                    $send_communication["customer_sms_before3hour"] = $this->customersms->bookTrialReminderBefore3Hour($booktrialdata, $before3HourDateTime);
                    $send_communication["customer_notification_before3hour"] = $this->customernotification->bookTrialReminderBefore3Hour($booktrialdata, $before3HourDateTime);
                }
            }

            if($currentScheduleDateDiffMin >= (10)){

                $current_date = date('Y-m-d 00:00:00');

                $from_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date))));
                $to_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date." + 1 days"))));
                $batch = 1;

                $booktrialMissedcall  = \Booktrial::where('_id','!=',(int) $booktrialdata['_id'])->where('customer_phone', substr($booktrialdata['customer_phone'], -10))->where('missedcall_batch','exists',true)->where('created_at','>',$from_date)->where('created_at','<',$to_date)->orderBy('_id','desc')->first();

                if(!empty($booktrialMissedcall) && isset($booktrialMissedcall->missedcall_batch) && $booktrialMissedcall->missedcall_batch != ''){
                    $batch = $booktrialMissedcall->missedcall_batch + 1;
                }

                $missedcall_no = \Ozonetelmissedcallno::where('batch',$batch)->where('type','yes')->where('for','N-3Trial')->first();

                if(empty($missedcall_no)){

                    $missedcall_no = \Ozonetelmissedcallno::where('batch',1)->where('type','yes')->where('for','N-3Trial')->first();
                }
                if(isset($missedcall_no->number)&&$missedcall_no->number!="")
                $booktrialdata['yes'] = $missedcall_no->number;
                else $booktrialdata['yes'] ="";

                $delayReminderTimeBefore10Min=    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->subMinutes(10);

                if(isset($booktrialdata['third_party_details'])){
                    Log::info('$booktrialdata->third_party_details is set customer_sms_before10Min_abg: ', [$booktrialdata['third_party_details']]);
                    $send_communication["customer_sms_before10Min_abg"] = $this->customersms->bookTrialReminderBefore10Min($booktrialdata, $delayReminderTimeBefore10Min);
                }
                else {
                    $send_communication["customer_sms_before10Min"] = $this->customersms->bookTrialReminderBefore10Min($booktrialdata, $delayReminderTimeBefore10Min);
                    $send_communication["customer_email_before10Min"] = $this->customermailer->bookTrialReminderBefore10Min($booktrialdata, $delayReminderTimeBefore10Min);
                }
                
                // $send_communication["customer_notification_before20Min"] = $this->customernotification->bookTrialReminderBefore20Min($booktrialdata, $delayReminderTimeBefore20Min);

                $booktrial->missedcall_batch = $batch;
            }


            
            if(in_array($booktrial->source, ['ios', 'android'])){

                $delayReminderTimeAfter6Hrs      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(60*6);

            	$delayReminderTimeAfter2Hrs      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(60*2);

                if(isset($booktrialdata['third_party_details'])){
                    $delayReminderTimeAfter30Mins      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(30);
                    Log::info('$booktrialdata->third_party_details is set customer_sms_after2hour_abg: ', [$booktrialdata['third_party_details']]);
                    $send_communication["customer_sms_after2hour_abg"] = $this->customersms->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hrs);
                    $send_communication["customer_sms_after30mins_abg"] = $this->customersms->bookTrialReminderAfter30Mins($booktrialdata, $delayReminderTimeAfter30Mins);
                }
                else {
                    Log::info('customer sm after 2 houers scheduling at schedulebooktrailController',[$booktrialdata]);
                    $send_communication["customer_sms_after2hour"] = $this->customersms->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hrs);
                    $send_communication["customer_notification_after2hour"] = $this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter6Hrs);
                
                    $promoData = [
                        'customer_id'=>$booktrialdata['customer_id'],
                        'delay'=>$delayReminderTimeAfter2Hrs,
                        'text'=>'Punch the Fitcode now & get instant Cashback',
                        'title'=>'Claim Your Fitcash'
                    ];

                    $send_communication["customer_notification_block_screen"] = $this->utilities->sendPromotionalNotification($promoData);

                    if(isset($booktrial->type)&&$booktrial->type!='workout-session'){
                        $send_communication["customer_email_after2hour"] = $this->customermailer->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hrs);
                    }
                }
            }
            else 
            {
                
                if(isset($booktrialdata['third_party_details'])){
                    $delayReminderTimeAfter2Hrs      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(60*2);
                    $delayReminderTimeAfter30Mins      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(30);

                    Log::info('$booktrialdata->third_party_details is set customer_sms_after2hour_abg: ', [$booktrialdata['third_party_details']]);
                    
                    $send_communication["customer_sms_after2hour_abg"] = $this->customersms->bookTrialReminderAfter2Hour($booktrialdata, $delayReminderTimeAfter2Hrs);
                    $send_communication["customer_sms_after30mins_abg"] = $this->customersms->bookTrialReminderAfter30Mins($booktrialdata, $delayReminderTimeAfter30Mins);
                }
                else{
                    if(empty($booktrialdata['multifit']) || !$booktrialdata['multifit']) {
                        $delayReminderTimeAfter24Hour      =    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',strtotime($booktrial->schedule_date_time)))->addMinutes(60*24);
                        $send_communication["customer_sms_after24hour"] = $this->customersms->bookTrialReminderAfter24Hour($booktrialdata, $delayReminderTimeAfter24Hour);
                    }
                }
            }  
            
            

            if((!isset($booktrialdata['third_party_details'])) && $booktrialdata['type'] == "booktrials" && isset($booktrialdata['amount']) && $booktrialdata['amount'] != "" && $booktrialdata['amount'] > 0){
                $this->customersms->giveCashbackOnTrialOrderSuccessAndInvite($booktrialdata);
            }

            $booktrial->send_communication = $send_communication;
            $booktrial->auto_followup_date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s',time()))->addDays(31);
            $booktrial->update();

            if(isset($booktrial->order_id) && $booktrial->order_id != ""){

                $order = Order::find((int) $booktrial->order_id);

                
                if($order){
                    try{
                        $this->utilities->updateCoupon($order);
                    }catch(Exception $e){
                        Log::info("updateCoupon error");
                    }   
                    $this->utilities->setRedundant($order);
                }
            }
            if(empty($booktrial->qrcodepayment) && $currentScheduleDateDiffMin <= 60 && !$this->isWeekend(time()) && !$this->isOffHour($current_hour)){
                $this->publishConfirmationAlert($booktrialdata);
            }

            if( $this->isWeekend(strtotime($booktrial->schedule_date_time)) &&  $this->isWeekend(time()) ){
                $cities 	=	City::active()->orderBy('name')->lists('name', '_id');
                $booktrialdata['city_name'] = $cities[$booktrialdata['city_id']];
                
                $booktrialdata['confirm_link'] = Config::get('app.url').'/updatetrialstatus/'.$booktrialdata['_id'].'/vendor/confirm';
                $booktrialdata['cancel_link'] = Config::get('app.url').'/updatetrialstatus/'.$booktrialdata['_id'].'/vendor/cancel';
                $this->findermailer->trialAlert($booktrialdata);                
                $this->findersms->trialAlert($booktrialdata);                
            }

        }catch(\Exception $exception){

            Log::error($exception);
        }

    }
    
    public function deleteTrialCommunication($booktrial){

        $queue_id = [];

        $array = [
            "customer_email_instant",
            "customer_sms_instant",
            "finder_email_instant",
            "finder_sms_instant",
            "customer_email_before12hour",
            "customer_notification_before12hour",
            "customer_sms_before12hour",
            "finder_sms_before6hour",
            "customer_sms_before3hour",
            "customer_notification_before3hour",
            "customer_sms_before20Min",
            "customer_notification_before20Min",
            "customer_sms_after2hour",
            "customer_email_after2hour",
            "customer_notification_after2hour",
            "trialInstantCallReminder",
            "fitternity_email_postTrialStatusUpdate",
            "customer_notification_before10min",
        	"customer_sms_before10Min",
        	"customer_email_before10Min",
        	"customer_email_instant_workoutlevelstart",
            "customer_notification_block_screen",
            "customer_sms_offhours_confirmation",
            "customer_sms_instant_abg",
            "customer_sms_before12hour_abg",
            "customer_sms_before3hour_abg",
            "customer_sms_before10Min_abg",
            "customer_sms_after2hour_abg",
            "customer_sms_after30mins_abg",
            "customer_sms_after24hour_abg",
        ];

        foreach ($array as $value) {
            if((isset($booktrial['send_communication'][$value]))){
                try {
                    $queue_id[] = $booktrial['send_communication'][$value];
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }
        }

        $booktrial->unset('send_communication');

        if(!empty($queue_id)){

            $this->sidekiq->delete($queue_id);
        }
        
    }


    public function bookTrialFree($data = null)
    {

        // send error message if any thing is missing
        (!isset($data)) ? $data = Input::json()->all() : null;
        (!is_array($data)) ? $data = $data->toArray() : null;


        Log::info('------------bookTrialFree------------',$data);

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
        }else{
            $finderdata 		=	Finder::find(intval($data['finder_id']));
            if(!$finderdata) {
                $resp = array('status' => 404, 'message' => "Finder does not exist");
                return Response::json($resp, 404);
            }
        }

        if(empty($data['service_name'])){
            $data['service_name'] = "-";
        }

        if(empty($data['schedule_date'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_date");
            return  Response::json($resp, 400);
        }

        if(empty($data['schedule_slot'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
            return  Response::json($resp, 400);
        }

        if(isset($data['service_id']) && $data['service_id'] != ""){
            $servicedata 		=	Service::find(intval($data['service_id']));
            if(!$servicedata) {
                $resp = array('status' => 404, 'message' => "Service does not exist");
                return Response::json($resp, 404);
            }
        }
        $asshole_numbers = ["7838038094","7982850036","8220720704","8510829603"];
        
        if(in_array(substr($data["customer_phone"], -10), $asshole_numbers)){
            return Response::json("Can't book anything for you.",400);
        }

        if($this->vendor_token){
            $data['customer_source'] = 'kiosk';
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
            $ratecard_id = (isset($data['ratecard_id']) && $data['ratecard_id'] != '') ? (int) $data['ratecard_id'] : "";
            if($ratecard_id != ""){
                $ratecard = Ratecard::where('_id',$ratecard_id)->first();
                if($ratecard->price > 0 || $ratecard->special_price > 0){
                    return array('status' => 500, 'message' => "Fitcash cannot be used for this booking");
                }
            }
            $booktrialid = Booktrial::maxId() + 1;
            isset($data['finder_id']) ? $finderid = (int)$data['finder_id'] : null;
            $finder = Finder::with(array('location' => function ($query) {
                $query->select('_id', 'name', 'slug');
            }))->with(array('city' => function ($query) {
                $query->select('_id', 'name', 'slug');
            }))->with('locationtags')->where('_id', '=', $finderid)->first()->toArray();
            $data['customer_id'] = $customer_id = autoRegisterCustomer($data);

            $cleartrip_count                   =    $this->getCleartripCount($finderid);
            $trial_count                       =    $this->getTrialCount($finderid);
            $before_three_month_trial_count    =    $this->getBeforeThreeMonthTrialCount($finderid);

            // Throw an error if user has already booked a trial for that vendor...

            if(!isset($data['manual_order'])){
            
            $alreadyBookedTrials = Config::get('app.debug') ? [] : $this->utilities->checkExistingTrialWithFinder($data['customer_email'], $data['customer_phone'], $data['finder_id']);
            
            
            if (count($alreadyBookedTrials) > 0 && empty($data['third_party_acronym'])) {
                $resp = array('status' => 403, 'message' => "You have already booked a trial for this vendor, please choose some other vendor");
                return Response::json($resp, 403);
            }

            // Throw an error if user has already booked a trial on same schedule timestamp..
            $dates = $this->utilities->getDateTimeFromDateAndTimeRange($data['schedule_date'], $data['schedule_slot']);
            $UpcomingTrialsOnTimestamp = Config::get('app.debug') ? [] : $this->utilities->getUpcomingTrialsOnTimestamp($customer_id, $dates['start_timestamp'], $finderid);
            if (count($UpcomingTrialsOnTimestamp) > 0) {
                $resp = array('status' => 403, 'message' => "You have already booked a trial on same datetime");
                return Response::json($resp, 403);
            }

                $disableTrial = $this->disableTrial($data);

                if($disableTrial['status'] != 200){

                    return Response::json($disableTrial,$disableTrial['status']);
                }
                
            }

            $myreward_id = "";

            if (isset($data['reward_id']) && $data['reward_id'] != "") {

                $myreward_id = $data['myreward_id'] = (int)$data['reward_id'];

                $createMyRewardCapture = $this->customerreward->createMyRewardCapture($data);

                if($createMyRewardCapture['status'] !== 200){

                    return Response::json($createMyRewardCapture,$createMyRewardCapture['status']);
                }
            }

            isset($data['customer_name']) ? $customer_name = $data['customer_name'] : null;
            isset($data['customer_email']) ? $customer_email = $data['customer_email'] : null;
            isset($data['customer_phone']) ? $customer_phone = preg_replace("/[^0-9]/", "", $data['customer_phone']) : null;
            $fitcard_user = isset($data['fitcard_user']) ? intval($data['fitcard_user']) : 0;
            $type = isset($data['type']) ? $type = $data['type'] : 'booktrials';

            $finder_name = (isset($finder['title']) && $finder['title'] != '') ? $finder['title'] : "";
            $finder_slug = (isset($finder['slug']) && $finder['slug'] != '') ? $finder['slug'] : "";
            $finder_lat = (isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon = (isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $city_id = (int)$finder['city_id'];
            $finder_commercial_type = (isset($finder['commercial_type']) && $finder['commercial_type'] != '') ? (int)$finder['commercial_type'] : "";
            $finder_category_id = (isset($finder['category_id']) && $finder['category_id'] != '') ? $finder['category_id'] : "";

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

                $regData = array();

                $regData['customer_id'] = $customer_id;
                $regData['reg_id'] = $gcm_reg_id;
                $regData['type'] = $device_type;

                $this->utilities->addRegId($regData);
            }

            // $finder_location                 =   (isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
            // $finder_address                      =   (isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
            // $show_location_flag              =   (count($finder['locationtags']) > 1) ? false : true;

            $description =  $what_i_should_carry = $what_i_should_expect = $service_category = '';
            $service_slug = null;
            if ($service_id != '') {
                $serviceArr = Service::with(array('location' => function ($query) {
                    $query->select('_id', 'name', 'slug');
                }))->with('category')->with('subcategory')->where('_id', '=', intval($service_id))->first()->toArray();

                if(!empty($serviceArr['slug'])) {
                    $service_slug = $serviceArr['slug'];
                }

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

                if((isset($serviceArr['category']['name']) && $serviceArr['category']['name'] != '')){
                    $service_category = $serviceArr['category']['name'];
                }else{
                    if((isset($serviceArr['subcategory']['name']) && $serviceArr['subcategory']['name'] != '')){
                        $service_category = $serviceArr['subcategory']['name'];
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

                $data['service_name'] = $serviceArr['name'];

            }else{
                $finder_location			       =	(isset($finder['location']['name']) && $finder['location']['name'] != '') ? $finder['location']['name'] : "";
                $finder_address				       = 	(isset($finder['contact']['address']) && $finder['contact']['address'] != '') ? $finder['contact']['address'] : "";
                $show_location_flag 		       =   (count($finder['locationtags']) > 1) ? false : true;
            }

            $finder_lat		       =	(isset($finder['lat']) && $finder['lat'] != '') ? $finder['lat'] : "";
            $finder_lon		       =	(isset($finder['lon']) && $finder['lon'] != '') ? $finder['lon'] : "";
            $finder_photos	       = 	[];

            $finder_city           =    (isset($finder['city']['name']) && $finder['city']['name'] != '') ? $finder['city']['name'] : "";
            $finder_city_slug      =    (isset($finder['city']['slug']) && $finder['city']['slug'] != '') ? $finder['city']['slug'] : "";

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
            $vendor_code = random_numbers(5);
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
            $physical_activity_detail = (isset($data['physical_activity_detail']) && $data['physical_activity_detail'] != '') ? $data['physical_activity_detail'] : "";
            $membership = isset($data['membership']) ? (object)$data['membership'] : new stdClass();

            // return $membership->reward_ids;

            if(isset($membership->reward_ids) && count($membership->reward_ids)>=0){
                
                $reward = Reward::find($membership->reward_ids[0], array('title'));
                
                if($reward){
                    $membership->reward = $reward;
                }
            }

            $membership =json_decode(json_encode($membership), True);

            $google_pin = $this->googlePin($finder_lat, $finder_lon);
            $customer_profile_url       =   $this->customerProfileUrl($customer_email);
            $finder_url                 =   $this->vendorUrl($finder['slug']);
            if(isset($serviceArr) && isset($serviceArr['category']) && $serviceArr['category']['_id'] != ''){
                $calorie_burn           =   $this->getCalorieBurnByServiceCategoryId($serviceArr['category']['_id']);
            }else{
                $calorie_burn           =   300;
            }

            $rebook_trial_url         =   $this->rebookTrialUrl($finder_slug, $service_id, $booktrialid);

            $finder_location_slug               =   (isset($finder['location']['slug']) && $finder['location']['slug'] != '') ? $finder['location']['slug'] : "";

            $service_link = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$finder_slug."/".$service_id);
            $srp_link = $this->utilities->getShortenUrl(Config::get('app.website')."/".$finder_city_slug."/".$finder_location_slug."/fitness");
            $vendor_notify_link = $this->utilities->getShortenUrl(Config::get('app.business')."/trial/cancel/".$booktrialid."/".$finderid);
            $pay_as_you_go_link = $this->utilities->getShortenUrl(Config::get('app.website')."/workout/".$finder_city_slug."?regions=".$finder_location_slug);
            $profile_link = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$customer_email);
            $vendor_link = $this->utilities->getShortenUrl(Config::get('app.website')."/".$finder_slug);
            $pre_trial_vendor_confirmation = (isset($data['finder_id']) && in_array($data['finder_id'], Config::get('app.trial_auto_confirm_finder_ids'))) ? 'confirmed' : 'yet_to_connect';

            $booktrial_link = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$finder_slug."/".$service_id);
            $workout_article_link = $this->utilities->getShortenUrl(Config::get('app.website')."/article/complete-guide-to-help-you-prepare-for-the-first-week-of-your-workout");
            $download_app_link = Config::get('app.download_app_link');
            $diet_plan_link = $this->utilities->getShortenUrl(Config::get('app.website')."/diet-plan");

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
                'service_category'      =>      $service_category,
                'city_id'             =>      $city_id,
                'finder_vcc_email'    =>      $finder_vcc_email,
                'finder_vcc_mobile'   =>      $finder_vcc_mobile,
                'finder_poc_for_customer_name'  =>      $finder_poc_for_customer_name,
                'finder_poc_for_customer_no'    =>      $finder_poc_for_customer_no,
                'show_location_flag'  =>      $show_location_flag,
                'share_customer_no'   =>      $share_customer_no,

                'service_id'          =>      $service_id,
                'service_name'        =>      $service_name,
                'service_slug'                  =>      $service_slug,
                'schedule_slot_start_time'      =>      $schedule_slot_start_time,
                'schedule_slot_end_time'        =>      $schedule_slot_end_time,
                'schedule_date'       =>      $schedule_date,
                'schedule_date_time'  =>      $schedule_date_time,
                'schedule_slot'       =>      $schedule_slot,
                'going_status'        =>      1,
                'going_status_txt'    =>      'going',
                'code'                =>      $code,
                'vendor_code'         =>      $vendor_code,
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
                'reward_id' => $myreward_id,
                'referrer_booktrial_id' => $referrer_booktrial_id,
                'root_booktrial_id' => $root_booktrial_id,
                'kit_enabled' => $kit_enabled,

                'medical_condition' => $medical_condition,
                'age' => $age,
                'injury' => $injury,
                'note_to_trainer' => $note_to_trainer,
                'medical_detail' => $medical_detail,
                'medication_detail' => $medication_detail,
                'physical_activity_detail'      =>      $physical_activity_detail,
                'cleartrip_count'               =>      $cleartrip_count,
                'trial_count'               =>      $trial_count,
                'before_three_month_trial_count' =>     $before_three_month_trial_count,
                'myreward_id' => $myreward_id,
                'token'                         =>      random_number_string(),
                'customer_profile_url'         =>       $customer_profile_url,
                'calorie_burn'                  =>      $calorie_burn,
                'finder_url'                    =>      $finder_url,
                'rebook_trial_url'              =>      $rebook_trial_url,
                'service_link'                  =>      $service_link,
                'srp_link'                      =>      $srp_link,
                'vendor_notify_link'            =>      $vendor_notify_link,
                'pay_as_you_go_link'            =>      $pay_as_you_go_link,
                'profile_link'                  =>      $profile_link,
                'vendor_link'                   =>      $vendor_link,
                'finder_location_slug'          =>      $finder_location_slug,
                'membership'                    =>      $membership,
                'pre_trial_vendor_confirmation' =>      $pre_trial_vendor_confirmation,
                'vendor_kiosk'                  =>      isKioskVendor($finderid),
                'booktrial_link'                =>      $booktrial_link,
                'workout_article_link'          =>      $workout_article_link,
                'download_app_link'             =>      $download_app_link,
                'diet_plan_link'                =>      $diet_plan_link,
                'pre_trial_status'              =>      'yet_to_connect',
                'ask_review'                    =>      true
            );

            $customer = Customer::where('_id', $customer_id)->first();
            if(!empty($customer['corporate_id'])) {
                $booktrialdata['corporate_id'] = $customer['corporate_id'];
            }

            if(!empty($data['assisted_by'])){
                $booktrialdata['assisted_by'] = $data['assisted_by'];
            }

            if(!empty($data['manual_order'])){
                $booktrialdata['manual_order'] = $data['manual_order'];
            }

            if(!empty($data['punching_order'])){
                $booktrialdata['punching_order'] = $data['punching_order'];
            }
            if(!empty($this->authorization)){
                $logged_in_customer = customerTokenDecode($this->authorization);
                $logged_in_customer_id = $logged_in_customer->customer->_id;
                $booktrialdata["logged_in_customer_id"] = $logged_in_customer_id;
            }
            $is_tab_active = isTabActive($booktrialdata['finder_id']);

            if($is_tab_active){
                $booktrialdata['is_tab_active'] = true;
            }

            if(isset($data['promotional_notification_id']) && $data['promotional_notification_id'] != ""){
                $booktrialdata['promotional_notification_id'] = $data['promotional_notification_id'];
            }

            if(isset($data['promotional_notification_label']) && $data['promotional_notification_label'] != ""){
                $booktrialdata['promotional_notification_label'] = $data['promotional_notification_label'];
            }

            if(isset($data['booking_for_others']) && $data['booking_for_others'] != ""){
              $booktrialdata['booking_for_others'] = $data['booking_for_others'];
            }

            $addUpdateDevice = $this->utilities->addUpdateDevice($customer_id);

            foreach ($addUpdateDevice as $header_key => $header_value) {

                if($header_key != ""){
                   $booktrialdata[$header_key]  = $header_value;
                }

            }

            if(isset($data['customofferorder_id']) && $data['customofferorder_id'] != ""){

                $booktrialdata['customofferorder_id'] = $data['customofferorder_id'];

                $customofferorder   =   Fitapicustomofferorder::find($data['customofferorder_id']);

                if(isset($customofferorder->validity) && $customofferorder->validity != ""){

                    $booktrialdata['customofferorder_expiry_date'] =   date("Y-m-d H:i:s", strtotime("+".$customofferorder->validity." day", strtotime($customofferorder->created_at)));
                    $booktrialdata['customofferorder_validity'] = $customofferorder->validity;
                }
            }

            if(isset($data['myreward_id']) && $data['myreward_id'] != ""){

                $booktrialdata['myreward_id'] = $data['myreward_id'];

                $myreward = Myreward::find((int)$data['myreward_id']);

                if($myreward){
                    $booktrialdata['reward_balance'] = $myreward->quantity - $myreward->claimed;
                }

                if ($type == 'vip_booktrials_rewarded') {

                    $myreward->update(array('status' => '1','reward_action' => 'claimed','claimed' => '1'));
                }
            }

            // return $this->customersms->bookTrial($booktrialdata);
            // return $booktrialdata;

            if(isset($data['recommended_booktrial_id']) && $data['recommended_booktrial_id'] != ""){
                $booktrialdata['recommended_booktrial_id'] = (int)$data['recommended_booktrial_id'];
            }
            if(empty($data['third_party_acronym'])){
                $booktrialdata['surprise_fit_cash'] = $this->utilities->getFitcash($booktrialdata);
            }

            if(!empty($data['third_party_acronym'])){
                $booktrialdata['third_party'] = true;
                $booktrialdata['third_party_acronym'] = $data['third_party_acronym'];
                $booktrialdata['third_party_details'] = $data['third_party_details'];
                $booktrialdata['third_party_details'][$data['third_party_acronym']]['third_party_used_sessions'] = $data['total_session_used'];
                
                $booktrialdata['total_sessions'] = $data['total_sessions'];
                $booktrialdata['total_sessions_used'] = $data['total_session_used'];
                $booktrialdata['ratecard_id'] = $ratecard_id;

                Customer::$withoutAppends = true;
                $abwCust = Customer::where('third_party_details','exists',true)->where('status','1')->where('email', $customer_email)->first();
                if(!empty($abwCust)) {
                    if(empty($abwCust['total_sessions_used'])) {
                        $abwCust['total_sessions_used'] = 0;
                    }
                    $abwCust['total_sessions_used'] = $abwCust['total_sessions_used'] + 1;
                    $abwCust->update();
                }
            }

            if(isset($data['_id'])){
                $booktrialid = (int) $data['_id'];
                $booktrial = Booktrial::find($booktrialid);
                $trialbooked = $booktrial->update($booktrialdata);
            }
            else{
                $booktrial = new Booktrial($booktrialdata);
                $booktrial->_id = $booktrialid;
                $trialbooked = $booktrial->save();
            }


            if ($medical_detail != "" && $medication_detail != "") {

                $customer_info = new CustomerInfo();
                $response = $customer_info->addHealthInfo($booktrialdata);
            }

        } catch (ValidationException $e) {

            return array('status' => 500, 'message' => $e->getMessage());
        }

        if($trialbooked == true){
            if(empty($data['third_party_acronym'])){
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
            }

            if(empty($data['third_party_acronym'])){
                $after_booking_response =  $this->utilities->afterTranSuccess($booktrial->toArray(), 'booktrial');

                if(!empty($after_booking_response['checkin'])){
                    if(!empty($after_booking_response['checkin']['status']) && $after_booking_response['checkin']['status'] == 200 && !empty($after_booking_response['checkin']['checkin']['_id'])){
                        $booktrial->checkin = $after_booking_response['checkin']['checkin']['_id'];
                    }
                }
                    
                
                if(!empty($after_booking_response['loyalty_registration']['status']) && $after_booking_response['loyalty_registration']['status'] == 200){
                    $booktrial->loyalty_registration = true;
                }
            }

            //if vendor type is free special dont send communication

           /* Log::info('finder commercial_type  -- '. $finder['commercial_type']);
            if($finder['commercial_type'] != '2'){*/
                $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@sendCommunication', array('booktrial_id'=>$booktrialid), Config::get('app.queue'));
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

        // Log::info('Customer Book Trial : '.json_encode(array('book_trial_details' => Booktrial::findOrFail($booktrialid))));

        if(isset($data['temp_id'])){
            $delete = Tempbooktrial::where('_id', $data['temp_id'])->delete();
        }

        $resp 	= 	array('status' => 200, 'booktrialid' => $booktrialid, 'code' => $code, 'message' => "Trial Booked Successfully");

        if($this->vendor_token){

            $item = [];

            $item['booked_locate'] = 'booked';
            $item['finder_id'] = (int)$booktrial['finder_id'];

            $resp['kiosk'] = $this->utilities->trialBookedLocateScreen($item);

        }

        return Response::json($resp,200);
    }

    public function toQueueBookTrialFree($job,$data){

        $job->delete();

        try{

            $booktrialid = (int)$data['booktrialid'];
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
            $delayReminderTimeBefore3Hour        =  \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 3);
            $delayReminderTimeBefore1Hour        =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60);
            $delayReminderTimeBefore5Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 5);
            $delayReminderTimeBefore12Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 12);
            $delayReminderTimeAfter2Hour       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(120);
            $delayReminderTimeAfter30Mins      =    \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(30);
            $delayReminderTimeAfter50Hour       =   \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(60 * 50);
            $reminderTimeAfter1Hour 	       =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(60);
            $oneHourDiff 				       = 	$currentDateTime->diffInHours($scheduleDateTime, false);
            $twelveHourDiff 			       = 	$currentDateTime->diffInHours($scheduleDateTime, false);
            $oneHourDiffInMin 			       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $fiveHourDiffInMin 			       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $twelveHourDiffInMin 		       = 	$currentDateTime->diffInMinutes($scheduleDateTime, false);
            $threeHourDiffInMin                =    $currentDateTime->diffInMinutes($scheduleDateTime, false);
            $finderid 					       = 	(int) $data['finder_id'];

            $booktrialdata = Booktrial::findOrFail($booktrialid)->toArray();
            $finder = Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',$finderid)->first()->toArray();

            $finder_category_id       = (isset($booktrialdata['finder_category_id']) && $booktrialdata['finder_category_id'] != '') ? $booktrialdata['finder_category_id'] : "";

            $customer_email_messageids  =  $finder_email_messageids  =  $customer_sms_messageids  =  $finder_sms_messageids  =  $customer_notification_messageids  =  array();

            $customer_auto_sms = '';

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
            $finder_sms_messageids['instant']        =   $sndInstantSmsFinder;

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

            //Send Reminder Notiication (Sms) Before 3 Hour To Vendor
            if($threeHourDiffInMin >= 180){

                $sndBefore1HourSmsFinder			       =	$this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore3Hour);
                $finder_sms_messageids['before1hour']        = 	$sndBefore1HourSmsFinder;

                /*if(isset($booktrialdata['source']) && $booktrialdata['source'] != 'cleartrip') {

                    if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                        $customer_notification_messageids['before1hour'] = $this->customernotification->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }else{
                        $customer_sms_messageids['before1hour'] = $this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }
                }*/

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
                'finder_smsqueuedids' => $finder_sms_messageids,
                'customer_auto_sms' => $customer_auto_sms
            );

            $booktrial        = 	Booktrial::findOrFail($booktrialid);

            $this->firstTrial($booktrial->toArray());

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

        Log::info('rescheduledBookTrial',$data);

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

        if(empty($data['service_name'])){
            $data['service_name'] = "-";
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

            $disableTrial = $this->disableTrial($data);

            if($disableTrial['status'] != 200){

                return Response::json($disableTrial,$disableTrial['status']);
            }

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
            $delayReminderTimeAfter30Mins      =    Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->addMinutes(30);
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

            $customer_id = $data['customer_id'] = autoRegisterCustomer($data);

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

            $description =  $what_i_should_carry = $what_i_should_expect = $service_category = '';

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

                if((isset($serviceArr['category']['name']) && $serviceArr['category']['name'] != '')){
                    $service_category = $serviceArr['category']['name'];
                }else{
                    if((isset($serviceArr['subcategory']['name']) && $serviceArr['subcategory']['name'] != '')){
                        $service_category = $serviceArr['subcategory']['name'];
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

                $data['service_name'] = $serviceArr['name'];

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
            $premium_session                   =    (isset($data['premium_session'])) ? (boolean) $data['premium_session'] : false;
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
                'note_to_trainer'               =>      $note_to_trainer,
                'service_category'              =>      $service_category
            );

            if(!isset($booktrial['code'])){
                array_set($booktrialdata, 'code',random_numbers(5));
            }

            if(!isset($booktrial['surprise_fit_cash']) && !isset($booktrial['third_party_details'])){
                $booktrialdata['surprise_fit_cash'] = $this->utilities->getFitcash($booktrial->toArray());
            }

            if(!isset($booktrial['vendor_code'])){
                array_set($booktrialdata,'vendor_code',random_numbers(5));
            }
            
            if(isset($schedule_date) && isset($old_schedule_date)){
                if($schedule_date != $old_schedule_date){
                    $booktrialdata['pre_trial_vendor_confirmation'] = (isset($finderid) && in_array($finderid, Config::get('app.trial_auto_confirm_finder_ids'))) ? 'confirmed' : 'yet_to_connect';
                }
            }
            $booktrialdata['reschedule_count'] = isset($booktrial['reschedule_count']) ? $booktrial['reschedule_count'] + 1 : 1;

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
                'delayReminderTimeAfter30Mins' => $delayReminderTimeAfter30Mins,
                'delayReminderTimeAfter50Hour'=>$delayReminderTimeAfter50Hour,
                'reminderTimeAfter1Hour'=> $reminderTimeAfter1Hour,
                'finder'=>$finder,
                'old_going_status'=>$old_going_status,
                'old_schedule_date'=>$old_schedule_date,
                'old_schedule_slot_start_time'=>$old_schedule_slot_start_time,
                'old_schedule_slot_end_time'=>$old_schedule_slot_end_time,
                'schedule_date' => $data['schedule_date'],
                'schedule_slot' => $data['schedule_slot'],
            );

            $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@sendCommunication',['booktrial_id'=>$booktrialid], Config::get('app.queue'));
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

            $slot_times                        =    explode('-',$data['schedule_slot']);
            $schedule_slot_start_time          =    $slot_times[0];
            $schedule_slot_end_time            =    $slot_times[1];
            $schedule_slot                     =    $schedule_slot_start_time.'-'.$schedule_slot_end_time;

            $slot_date                         =    date('d-m-Y', strtotime($data['schedule_date']));
            $schedule_date_starttime           =    strtoupper($slot_date ." ".$schedule_slot_start_time);
            $currentDateTime                   =    \Carbon\Carbon::now();
            $scheduleDateTime                  =    \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime);

            $delayReminderTimeBefore3Hour      =    \Carbon\Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->subMinutes(60 * 3);
            $threeHourDiffInMin                =    $currentDateTime->diffInMinutes($scheduleDateTime, false);


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
            $delayReminderTimeAfter30Mins = $data['delayReminderTimeAfter30Mins'];
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
                        $this->updateBookTrialFintnessForce($booktrialid);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }

                }
            }

            $this->deleteTrialCommunication($booktrial);

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

                if((isset($booktrial->rescheduleafter4days) && $booktrial->rescheduleafter4days != '')){

                    try {
                        $this->sidekiq->delete($booktrial->rescheduleafter4days);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }

            }


            if($send_post_reminder_communication != '' && $update_only_info == ''){
                $sndInstantPostReminderStatusSmsFinder	=	$this->findersms->postReminderStatusSmsFinder($booktrialdata);
            }


            if($send_alert != '' && $update_only_info == ''){

                $customer_email_messageids 	=  $finder_email_messageids  =	$customer_sms_messageids  =  $finder_sms_messageids  = $customer_notification_messageids = array();

                //Send Instant (Email) To Customer & Finder
                $sndInstantEmailCustomer		       = 	$this->customermailer->rescheduledBookTrial($booktrialdata);
                $sndInstantSmsCustomer			       =	$this->customersms->rescheduledBookTrial($booktrialdata);
                $sndInstantEmailFinder			       = 	$this->findermailer->rescheduledBookTrial($booktrialdata);
                $sndInstantSmsFinder			       =	$this->findersms->rescheduledBookTrial($booktrialdata);

                $customer_email_messageids['instant'] 	= 	$sndInstantEmailCustomer;
                $customer_sms_messageids['instant'] 	= 	$sndInstantSmsCustomer;
                $finder_email_messageids['instant'] 	= 	$sndInstantEmailFinder;
                $finder_sms_messageids['instant']        = 	$sndInstantSmsFinder;

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
                if($threeHourDiffInMin >= 180){

                    $sndBefore1HourSmsFinder			       =	$this->findersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore3Hour);
                    $finder_sms_messageids['before1hour']        = 	$sndBefore1HourSmsFinder;

                    /*if($booktrialdata['reg_id'] != '' && $booktrialdata['device_type'] != ''){
                        $customer_notification_messageids['before1hour'] = $this->customernotification->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }else{
                        $customer_sms_messageids['before1hour'] = $this->customersms->bookTrialReminderBefore1Hour($booktrialdata, $delayReminderTimeBefore1Hour);
                    }*/

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
                    'finder_smsqueuedids' => $finder_sms_messageids);

                $booktrial->update($queueddata);

            }

        }catch(\Exception $exception){
            Log::error($exception);
        }

    }
    public function cancelTrialSessionByVendor($finder_id, $trial_id){

        $data = Input::json()->all();
        $reason = isset($data['reason']) ? $data['reason'] : '';
        
        $booktrial = Booktrial::find((int)$trial_id);

        if(!isset($booktrial)){
            $data = ['status_code' => 401, 'message' => ['error' => 'Trial does not exists']];
            return Response::json($data, 401);
        }

        // if(isset($_GET['token']) && $_GET['token'] != ""){

        //     $booktrial = Booktrial::where("_id",(int)$trial_id)->where("token",$_GET['token'])->get();

        //     if(count($booktrial) > 0){
        //         $data = ['status_code' => 401, 'message' => ['error' => 'Trial does not exists']];
        //         return Response::json($data, 401);
        //     }

        // }else{

        //     $data = ['status_code' => 401, 'message' => ['error' => 'Hash 'Required'']];
        //     return Response::json($data, 401);
        // }

        /*$finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor profile']];
            return Response::json($data, 401);
        }*/

        $this->customersms->bookTrialCancelByVendor($booktrial->toArray());

        return $this->cancel($trial_id, 'vendor', $reason);
    }


    /**
     * @param $id
     * @return mixed
     */
    public function cancel($id, $source_flag='customer', $reason='', $isBackendReq = false){
        Log::info('inside cancel',[$id]);

        $id 		       = 	(int) $id;
        $bookdata 	       = 	array();
        $booktrial 	       = 	Booktrial::findOrFail($id);

        if(isset($booktrial->final_lead_stage) && $booktrial->final_lead_stage == 'cancel_stage'){

            $resp 	= 	array('status' => 200, 'message' => "Trial Canceled Repeat");
            return Response::json($resp,200);
        }

       if(!empty($booktrial['finder_category_id']) && $booktrial['finder_category_id'] == 5){
            if(
                // (!empty($booktrial['third_party_details'])) &&
                ((isset($booktrial['post_trial_status_updated_by_lostfitcode'])) || (isset($booktrial['post_trial_status_updated_by_fitcode'])) || (isset($booktrial->schedule_date_time) && time() >= (strtotime($booktrial->schedule_date_time)-900) && !$isBackendReq))){
                    $resp = array('status' => 400, 'message' => "This session cannot be cancelled");
                    return Response::json($resp,200);
            }
        }else{
            if(
                // (!empty($booktrial['third_party_details'])) &&
                ((isset($booktrial['post_trial_status_updated_by_lostfitcode'])) || (isset($booktrial['post_trial_status_updated_by_fitcode'])) || (isset($booktrial->schedule_date_time) && time() >= (strtotime($booktrial->schedule_date_time)-3600) && !$isBackendReq))){
                    $resp = array('status' => 400, 'message' => "This session cannot be cancelled");
                    return Response::json($resp,200);
            }
        }

        array_set($bookdata, 'going_status', 2);
        array_set($bookdata, 'going_status_txt', 'cancel');
        array_set($bookdata, 'booktrial_actions', '');
        array_set($bookdata, 'followup_date', '');
        array_set($bookdata, 'followup_date_time', '');
        array_set($bookdata, 'source_flag', $source_flag);
        array_set($bookdata, 'cancellation_reason_vendor', $reason);
        array_set($bookdata, 'final_lead_stage', 'cancel_stage');
        array_set($bookdata, 'final_lead_status', 'cancelled_by_'.$source_flag);

        if($source_flag == 'vendor'){
            array_set($bookdata, 'pre_trial_vendor_confirmation', 'cancel');
        }

        if($booktrial['type']=='workout-session'){
            array_set($bookdata, 'final_lead_stage', 'cancel_stage');
            array_set($bookdata, 'post_trial_status', 'no show');

        }else{

            if(!empty($booktrial['schedule_date_time']) && time() < strtotime($booktrial['schedule_date_time'])){

                array_set($bookdata, 'final_lead_stage', 'trial_stage');
            }

        }

        if(!empty($booktrial['pass_order_id'])){
            $order = Order::find($booktrial['pass_order_id']);
            if($order['pass']['pass_type'] == 'red'){
                $pass_end_date = array(
                    "old" => new Mongodate(strtotime($order['end_date'])),
                    "new" => new MongoDate(strtotime('+1 days',strtotime($order['end_date'])))
                );

                array_set($bookdata, 'pass_end_date', $pass_end_date);
            }
        }

        array_set($bookdata, 'cancel_by', $source_flag);
        $trialbooked        = 	$booktrial->update($bookdata);
        
        if(!empty($booktrial['pass_order_id'])){
            $order = Order::find($booktrial['pass_order_id']);
            if(
                ($order['pass']['pass_type'] == 'black') &&
                (strtotime($order['start_date']) <= time()) && 
                ($order['onepass_sessions_used'] < $order['onepass_sessions_total']) &&
                ($order['onepass_sessions_used']>0)
            ){
                $order->onepass_sessions_used -= 1;
                $order->update();
            }

            if($order['pass']['pass_type'] == 'red'){
                $order->end_date_original = new Mongodate(strtotime($order['end_date']));
                $order->end_date = new MongoDate(strtotime('+1 days',strtotime($order['end_date'])));
                $order->update();
            }
        }

        if(!empty($booktrial['extended_validity_order_id'])){
            $order = Order::find($booktrial['extended_validity_order_id']);
            if(
                strtotime($order['end_date']) >= time() 
                && 
                $order['sessions_left'] < $order['no_of_sessions']
            ){
                $order->sessions_left = $order->sessions_left+1;
                $order->update();
            }
        }
        if($trialbooked == true ){
            $queueBookTrial = array('id' => $id);
            $this->addExtendedSession($id, $isBackendReq, $booktrial, $queueBookTrial, $resp);
            $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueBookTrialCancel', $queueBookTrial,Config::get('app.queue'));
            $booktrial->update(array('cancel_redis_id'=>$redisid));

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

            $this->deleteTrialCommunication($booktrial);

            if((isset($booktrial->rescheduleafter4days) && $booktrial->rescheduleafter4days != '')){

                try {
                    $this->sidekiq->delete($booktrial->rescheduleafter4days);
                }catch(\Exception $exception){
                    Log::error($exception);
                }
            }

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

            $service_id = (int)$booktrialdata['service_id'];

            $booktrial_link = $this->utilities->getShortenUrl(Config::get('app.website')."/buy/".$finder_slug."/".$service_id);

            $emaildata = array(
                '_id'                           =>      $booktrialdata->_id,
                'customer_name'                 =>      $booktrialdata->customer_name,
                'customer_email'                =>      $booktrialdata->customer_email,
                'customer_phone'                =>      $booktrialdata->customer_phone,
                'customer_id'                   =>      $booktrialdata->customer_id,
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
                'source'                        =>      $booktrialdata->source,
                'booktrial_link'                =>      $booktrial_link
            );
            
            /***********instead of fitcash adding new workout session to customer in stuio_extended_validity************/
            
            // if(!empty($booktrial['studio_extended_validity'])){
                
            //     if(!empty($booktrial['studio_extended_validity_order_id'])){
                
            //         $this->utilities->scheduleStudioBookings($data['order_id'],true);
            //         $emaildata['paid']=0;
                
            //     }
                
            // }

            /***********instead of fitcash adding new workout session to customer in stuio_extended_validity************/


            /***********Creating session pack for studio_extended_validity************/
            
            // if(!empty($booktrial['studio_extended_validity'])){
                
            //     if(!empty($booktrial['studio_extended_validity_order_id'])){
                
			// 		$res_obj = app(TransactionController::class)->createSessionPack(['order_id'=>$booktrial['studio_extended_validity_order_id'], 'booktrial_id'=>$booktrial['_id']]);
                    
            //         $emaildata['paid']=0;
                
            //     }
                
            // }

            /***********instead of fitcash adding new workout session to customer in stuio_extended_validity************/

            
            /*********** Refund  session amount*****************/
            
            if(empty($booktrial['third_party_details']) && empty($booktrial['studio_extended_validity_order_id'])){

                $emaildata['paid']= $this->refundSessionAmount($booktrialdata);

            }
            
            /*********** Refund  session amount*****************/

            if(isset($booktrial['third_party_details']) && empty($booktrial['studio_extended_validity'])){
                
                $emaildata['third_party_details'] = $booktrial['third_party_details'];
            
            }

            if(isset($booktrial['corporate_id']) && $booktrial['corporate_id'] != ''){
                $emaildata['corporate_id'] = $booktrial['corporate_id'];
            }


            if(!empty($booktrial['studio_extended_validity_order_id'])){
                $emaildata['studio_extended_validity_order_id'] = $booktrial['studio_extended_validity_order_id'];
                $emaildata['studio_next_extended_session'] = $booktrial['studio_next_extended_session'];
                $order = Order::where('_id', $booktrial['studio_extended_validity_order_id'])->first(['_id', 'studio_extended_validity', 'studio_sessions', 'studio_membership_duration']);
                $emaildata['studio_extended_validity'] = $order['studio_extended_validity'];
                $emaildata['studio_sessions'] = $order['studio_sessions'];
                $emaildata['studio_membership_duration'] = $order['studio_membership_duration'];
            }

            if(!empty($booktrial['multifit'])){
                $emaildata['multifit'] = $booktrial['multifit'];
            }

            Log::info('after refund');
            if($booktrialdata->source_flag == 'vendor' ){
                if(!isset($booktrial['third_party_details'])){
		            if(empty($booktrial['multifit']) || !$booktrial['multifit']){
                        $this->customermailer->cancelBookTrial($emaildata);
                    }
                }
                $this->findermailer->cancelBookTrial($emaildata);
                $this->findersms->cancelBookTrial($emaildata);
                $this->customersms->cancelBookTrial($emaildata);
            }
            else{
                if(empty($booktrial['studio_extended_validity_order_id']) || !empty($booktrial['studio_extended_session'])){
                    $this->findermailer->cancelBookTrial($emaildata);
                    $this->findersms->cancelBookTrial($emaildata);
                }
                if(isset($booktrialdata->source) && $booktrialdata->source != 'cleartrip'){
                    if(!isset($booktrial['third_party_details'])){
                        if(empty($booktrial['multifit']) || !$booktrial['multifit']){
                            $this->customermailer->cancelBookTrial($emaildata);
                        }
                    }
                    Log::info('sending sms');
                    if(isset($booktrial['third_party_details'])){
                        $emaildata['profile_link'] = $this->utilities->getShortenUrl(Config::get('app.website')."/profile/".$emaildata['customer_email']);
                        if(isset($booktrial['third_party_details']['abg'])){
                            $emaildata['profile_link'] = Config::get('app.multiply_app_download_link');
                        }
                    }
                    $this->customersms->cancelBookTrial($emaildata);
                    if(!isset($booktrial['third_party_details'])){
                        $this->customernotification->cancelBookTrial($emaildata);
                    }
                }
            }

            $updateCorporateCoupons = $this->updateCorporateCoupons($booktrial);

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

        $booktrial  = \Booktrial::where('_id','!=',(int) $data['_id'])->where('customer_phone',substr($data['customer_phone'], -10))->where('missedcall_batch','exists',true)->where('created_at','>',$from_date)->where('created_at','<',$to_date)->orderBy('_id','desc')->first();
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

        if($booktrial['reg_id'] != '' && $booktrial['device_type'] != ''){

            // $booktrial->customerNotificationReminderBefore3Hour = $this->customernotification->bookTrialReminderBefore20Min($data,$ozonetel_date);
        }

        $booktrial->update();

        return $this->customersms->bookTrialReminderBefore20Min($data,$ozonetel_date);
    }

    public function missedCallReview($data,$delayReminderTimeAfter2Hour){

        $current_date = date('Y-m-d 00:00:00');

        $from_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date))));
        $to_date = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($current_date." + 1 days"))));

        $booktrial  = \Booktrial::where('_id','!=',(int) $data['_id'])->where('customer_phone', substr($data['customer_phone'], -10))->where('missedcall_review_batch','exists',true)->where('created_at','>',$from_date)->where('created_at','<',$to_date)->orderBy('_id','desc')->first();
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


    public function booktrialdetail($captureid,$type=false){

        Booktrial::$withoutAppends=true;
        Order::$withoutAppends=true;

        if(isset($_REQUEST['type'])){
            $type = $_REQUEST['type'];
        }

        if(isset($type) && $type == "healthytiffintrail"){
            $booktrial      =   Order::active()->with(array('finder'=>function($query){$query->select('*')->with(array('location'=>function($query){$query->select('name');}))->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}));}))->find(intval($captureid));
        }else{
            $booktrial      =   Booktrial::with('invite')->with(array('finder'=>function($query){$query->select('*')->with(array('location'=>function($query){$query->select('name');}))->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))->with(array('city'=>function($query){$query->select('_id','name','slug');}));}))->find(intval($captureid)); 
        }

        if(!$booktrial){

            return $this->responseNotFound('Request not found');
        }

        if(isset($type) && $type == "healthytiffintrail"){
            $dates = array('preferred_starting_date','start_date','start_date_starttime','end_date','preferred_payment_date','success_date','pg_date','preferred_starting_change_date','dietplan_start_date','followup_date', 'order_confirmation_customer','auto_followup_date','requested_preferred_starting_date');
        }else{         
            $dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date','followup_date','auto_followup_date');
        }

        $unset_keys = [];


        foreach ($dates as $key => $value) {

            if($booktrial[$value] == "" || $booktrial[$value] == "-"){
                // $booktrial->unset($value);
                array_push($unset_keys, $value);
            }
        }

        if(count($unset_keys)>0){
            $booktrial->unset($unset_keys);
        }

        $booktrialModel = $booktrial;

        $booktrial = $booktrial->toArray();

        $unset = array('customer_emailqueuedids','customer_smsqueuedids','customer_notification_messageids','finder_emailqueuedids','finder_smsqueuedids','customer_auto_sms','followup_date_time','send_communication');

        if(isset($booktrial['schedule_date_time']) && strtotime(Carbon::now()) >= strtotime(Carbon::parse($booktrial['schedule_date_time']))){

            $unset[] = 'what_i_should_carry';
            $unset[] = 'what_i_should_expect';
        }else{
            Log::info("ooooooooooo");
            if(isset($booktrial['what_i_should_carry']) && !((isset($booktrial['finder']['what_i_should_carry']) && $booktrial['finder']['what_i_should_carry']=''))){
            Log::info("ooooooooooo");
                
                $booktrial['finder']['what_i_should_carry']  = $booktrial['what_i_should_carry'];
            }
        }

        unset($booktrial['finder']['what_i_should_expect']);

        foreach($unset as $value){

            if(isset($booktrial[$value])){
                unset($booktrial[$value]);
            }
        }

        $currentDateTime =	\Carbon\Carbon::now();
        if(isset($booktrial['schedule_date_time'])){
            $scheduleDateTime 				=	Carbon::parse($booktrial['schedule_date_time']);
            $slot_datetime_pass_status  	= 	($currentDateTime->diffInMinutes($scheduleDateTime, false) > 0) ? false : true;
            $time_diff = strtotime($scheduleDateTime) - strtotime($currentDateTime);
        }else{
            $slot_datetime_pass_status = true;
            $time_diff = 60*60*60*2;
        }

        $hour2 = 60*60*2;
        $hour1 = 60*60;
		$going_status_txt = ['rescheduled','cancel'];

		if(!isset($booktrial['going_status_txt'])){
			$booktrial['going_status_txt'] = "-";
		}

		if(!isset($booktrial['amount'])){
			$booktrial['amount'] = 0;
		}

		if(isset($booktrial['amount']) && $booktrial['amount'] == "-"){
			$booktrial['amount'] = 0;
		}
        $cancel_enable = false;
        
		if($time_diff <= $hour2){
            $reschedule_enable = false;
			$cancel_enable = false;
        }elseif(in_array($booktrial['going_status_txt'], $going_status_txt) || $booktrial['amount'] > 0 || !isset($booktrial['type']) || $booktrial['type'] == 'workout-session' ){
            $reschedule_enable = false;
			$cancel_enable = false;
		}else{
            $reschedule_enable = true;
			$cancel_enable = true;
		}
        
        if($booktrial['type'] == 'workout-session' && $time_diff > $hour1){
			$cancel_enable = true;
        }

		if(!isset($booktrial['going_status_txt'])){
			$reschedule_enable = false;
		}
	
		array_set($booktrial, 'reschedule_enable', $reschedule_enable);
		array_set($booktrial, 'cancel_enable', $cancel_enable);

        if(isset($booktrial['preferred_starting_date'])){

            $booktrial['schedule_date_time'] = $booktrial['preferred_starting_date'];
            $booktrial['schedule_date'] = $booktrial['preferred_starting_date'];

            unset($booktrial['preferred_starting_date']);
        }

        if(isset($booktrial['status'])){

            unset($booktrial['status']);
        }

        if(isset($booktrial['order_action'])){

            unset($booktrial['order_action']);
        }

        if(isset($booktrial['amount_finder']) && $booktrial['amount_finder'] != ""){
            $booktrial['amount'] = $booktrial['amount_finder'];
        }

        if(empty($booktrial['studio_extended_validity_order_id'])){
            $booktrial['fit_code'] = $this->utilities->fitCode($booktrial);
        }
        else {
            Order::$withoutAppends = true;
            $order = Order::where('_id', $booktrial['studio_extended_validity_order_id'])->first(['_id', 'studio_extended_validity', 'studio_sessions', 'studio_membership_duration']);
            if(!empty($order['studio_sessions'])){
                $avail = $order['studio_sessions']['total_cancel_allowed'] - $order['studio_sessions']['cancelled'];
                $avail = ($avail<0)?0:$avail;
                $booktrial['what_i_should_carry'] = !empty($booktrial['what_i_should_carry']) ? $booktrial['what_i_should_carry'] : "";
                $booktrial['what_i_should_carry'] = $booktrial['what_i_should_carry']."<br><br><b>Can't make it? Cancel your session 60 minutes prior from your user profile to avail the extension.</b><br/><b>You have ".$avail.'/'.$order['studio_sessions']['total_cancel_allowed']." cancellations available up to ".date('d-m-Y', $order['studio_membership_duration']['end_date_extended']->sec).".</b><br/><b>Post cancelation, refer your Email for further details.</b>";
                if($avail<=0) {
                    $cancel_enable = false;
                    array_set($booktrial, 'cancel_enable', $cancel_enable);
                }
            }
        }

        if(empty($booktrial['surprise_fit_cash'])){
            $booktrial['surprise_fit_cash'] = $this->utilities->getFitcash($booktrial);

            $booktrialModel->surprise_fit_cash = $booktrial['surprise_fit_cash'];
            $booktrialModel->update();
        }


        $booktrial['lost_code'] = false;
        
        if(isset($booktrial['schedule_date_time']) && time() >= strtotime($booktrial['schedule_date_time'])){
            $booktrial['lost_code'] = true;
        }

        if($booktrial['type'] == 'workout-session'){
            if(!isset($booktrial['extended_validity_order_id'])){
                $customer_level_data = $this->utilities->getWorkoutSessionLevel($booktrial['customer_id']);                

                $booktrial['fitcode_message'] = 'Punch the code & get '.$customer_level_data['current_level']['cashback'].'% cashback';
            }
            else {
                $booktrial['fitcode_message'] = 'Punch the code to mark your attendance.';
            }
        }else{

            $booktrial['fitcode_message'] = 'Punch the code & get Rs '.$booktrial['surprise_fit_cash'].' flat discount';
        }

        $booktrial['fitcode_button_text'] = 'Enter Fitcode';
        $booktrial['vendor_code'] = "0000";
        $responsedata   = [
            'booktrial' => $booktrial,
            'message' => 'Booktrial Detail'
        ];
        
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

    public function vendorUrl($slug){

        $vendor_url    =   Config::get('app.website')."/".$slug;
        $shorten_url    =   new ShortenUrl();
        $url            =   $shorten_url->getShortenUrl($vendor_url);
        if(isset($url['status']) &&  $url['status'] == 200){
            $vendor_url = $url['url'];
        }

        return $vendor_url;
    }

    public function customerProfileUrl($email){

        $profile_url    =   Config::get('app.website')."/profile/".$email;
        $shorten_url    =   new ShortenUrl();
        $url            =   $shorten_url->getShortenUrl($profile_url);
        if(isset($url['status']) &&  $url['status'] == 200){
            $profile_url = $url['url'];
        }

        return $profile_url;
    }

    public function rebookTrialUrl($finder_slug,$service_id,$booktrial_id){

        $input_url    =   Config::get('app.website')."/booktrial/$finder_slug?serid=$service_id&bookid=$booktrial_id&source=fittransactionemail&medium=booktrail&button=rebooktiral";
        $shorten_url    =   new ShortenUrl();
        $url            =   $shorten_url->getShortenUrl($input_url);
        if(isset($url['status']) &&  $url['status'] == 200){
            $input_url = $url['url'];
        }

        return $input_url;
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
        // return date(time()+(60*60*24));
        // exit;

        $rules = [
            'status' => 'required'
        ];

        $validator = Validator::make($data = Input::json()->all(),$rules);

        if($validator->fails()) {
            $resp = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));
            return  Response::json($resp, 400);
        }
        if(!isset($data['booktrial_id'])){
            if(isset($data['notification_id'])){
                $notification = NotificationTracking::where('_id', $data['notification_id'])->first(['booktrial_id']);
                $booktrial_id = $notification['booktrial_id'];
            }
        }else{
            $booktrial_id = intval($data['booktrial_id']);
        }
        $booktrial = Booktrial::find($booktrial_id);

        if($booktrial){

            if($source == 'customer'){

                switch($data['status']){
                    case 'attended_buy_membership':
                    $booktrial->post_trial_status = 'attended';
                    $booktrial->post_trial_initail_status = 'interested';
                    break;
                    case 'attended_still_evaluating':
                    $booktrial->post_trial_status = 'attended';
                    $booktrial->post_trial_initail_status = 'interested';
                    break;
                    case 'attended_membership_not_interested':
                    $booktrial->post_trial_status = 'attended';
                    $booktrial->post_trial_initail_status = 'not_interested';
                    break;
                    case 'attended_membership_explore_more':
                    $booktrial->post_trial_status = 'attended';
                    $booktrial->post_trial_initail_status = 'other_option';
                    break;
                    case 'not_attended':
                    $booktrial->post_trial_status = 'no show';
                    $booktrial->post_trial_initail_status = '';
                    break;
                    case 'not_attended_reschedule':
                    $booktrial->post_trial_status = 'no show';
                    $booktrial->post_trial_initail_status = 'interested';
                    break;
                    case 'not_attended_membership':
                    $booktrial->post_trial_status = 'no show';
                    $booktrial->post_trial_initail_status = 'interested';
                    break;
                    case 'not_attended_other_options':
                    $booktrial->post_trial_status = 'no show';
                    $booktrial->post_trial_initail_status = 'other_option';
                    break;
                    case 'attended_more_info':
                    $booktrial->post_trial_status = 'attended';
                    $booktrial->post_trial_initail_status = 'interested';

                    if(isset($data['followup_date'])){

                        $booktrial->followup_date = date('Y-m-d H:i:s',$data['followup_date']);
                    }else{
                        $booktrial->followup_date = date('Y-m-d H:i:s',strtotime($booktrial->schedule_date.' +3 days'));
                    }

                    break;
                    case 'attended_on_my_way':		
                    $booktrial->post_trial_status = 'attended';		
                    break;		
                    case 'not_attended_not_interested':		
                    $booktrial->post_trial_initail_status = 'not_interested';		
                    $booktrial->post_trial_status = 'no show';		
                    break;
                }
                $booktrial->feedback_about_trial = (isset($data['reason']) && $data['reason'] != "") ? $data['reason'] : "";
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

    public function preTrialAction($source = 'customer'){

        $device_type = Request::header('Device-Type');
        $app_version = Request::header('App-Version');

        $rules = [
            'status' => 'required'
        ];

        $validator = Validator::make($data = Input::json()->all(),$rules);

        if($validator->fails()) {
            $resp = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));
            return  Response::json($resp, 400);
        }

        if(!isset($data['booktrial_id'])){
            if(isset($data['notification_id'])){
                $notification = NotificationTracking::where('_id', $data['notification_id'])->first(['booktrial_id']);
                $booktrial_id = $notification['booktrial_id'];
            }
        }else{
            $booktrial_id = intval($data['booktrial_id']);
        }

        $booktrial = Booktrial::find($booktrial_id);

        if($booktrial){

            $message = "Successfull Posted";

            if($source == 'customer'){

                switch($data['status']){
                    case 'confirm':
                    $booktrial->pre_trial_status = 'confirm';
                    $message = "Thanks for confirming, the trainer will be ready to attend you!";
                    if($device_type && $app_version && $app_version > '4.4.3'){
                        return $this->sessionStatusCapture('confirm', $booktrial_id);
                    }
                    break;
                    case 'cancel':
                    if($device_type && $app_version && $app_version > '4.4.3'){
                        return $this->sessionStatusCapture('didnotattend', $booktrial_id);
                    }
                    break; 
                }

                if((isset($data['reason']) && $data['reason'] != "")){
                    $booktrial->pre_trial_status_reason = $data['reason'];
                }
            }

            if($source == 'vendor'){
                $booktrial->trial_attended_finder = (isset($data['status']) && $data['status'] == true ) ? "attended" : "no show";
                $booktrial->trial_attended_finder_reason = (isset($data['reason']) && $data['reason'] != "") ? $data['reason'] : "";
            }

            $booktrial->update();

            $resp   =   array('status' => 200,'message' => $message);
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

        if(isset($req['id_for_invite'])){
            $req['booktrial_id'] = $req['id_for_invite'];
        }

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


        //Give 50% more cash back to booktrial customer on invites
        $cashback_amount = 0;
        $customer_balance = 0;
        /*if($BooktrialData){

            $booktrial_id   =   intval($req['booktrial_id']);
            $order          =   Order::where('booktrial_id', $booktrial_id)->where('status','1')->first();

            if($order && isset($order['customer_id']) && isset($order['amount']) && $order['amount'] > 0 && $order['amount'] != ""){

                $customer_id            =       intval($order['customer_id']);
                $cashback_amount 	    =	    intval((50/100) * $order['amount']);

                $walletData = array(
                    "customer_id"=> $customer_id,
                    "amount"=> $cashback_amount,
                    "amount_fitcash" => $cashback_amount,
                    "amount_fitcash_plus" => 0,
                    "type"=>'CASHBACK',
                    "description"=>'CASHBACK ON Invite amount - '.$cashback_amount,
                    "order_id"=>$order->_id,
                    'entry'=>'credit',
                );

                $this->utilities->walletTransaction($walletData);
            }
        }*/

        return Response::json(
            array(
                'status' => 200,
                'message' => 'Invitation has been sent successfully',
                'fitcash_added' => $cashback_amount,
                'wallet_balance' => $customer_balance
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

    public function confirmmanualtrialbyvendor(){

        $data = Input::json()->all();

        if(empty($data['service_name'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - service_name");
            return  Response::json($resp, 400);
        }if(empty($data['amount'])){
            $data['amount'] = 0;
        }if(empty($data['schedule_date'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_date");
            return  Response::json($resp, 400);
        }if(empty($data['schedule_slot'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - schedule_slot");
            return  Response::json($resp, 400);
        }if(empty($data['_id'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - _id");
            return  Response::json($resp, 400);
        }

        $data['schedule_date'] = date('Y-m-d 00:00:00', strtotime($data['schedule_date']));
        $booktrial = Booktrial::findOrFail((int) $data['_id']);

        if($booktrial['booktrial_type'] == 'auto'){
            $resp 	= 	array('status' => 422,'message' => "We have already recieved input for this trial");
            return  Response::json($resp, 422);
        }

        $data['confirm_by_vendor'] = "1";

        if($booktrial->update($data)){

            $booktrial = $booktrial->toArray();
            $booktrial['customer_source'] = $booktrial['source'];
            $resp = $this->bookTrialFree($booktrial);
            $data = $resp->getData();
            if($data->status == 200){

                if(isset($booktrial['customer_smsqueuedids']['manualtrialauto_8hours']) && $booktrial['customer_smsqueuedids']['manualtrialauto_8hours'] != ''){
                    try {
                        $this->sidekiq->delete($booktrial['customer_smsqueuedids']['manualtrialauto_8hours']);
                    }catch(\Exception $exception){
                        Log::error($exception);
                    }
                }
            }
            return $resp;
        }

        // Hit booktrialfree API for communication...
        // Handle existing booktrial in booktrial API...
        // Delete customer scheduled msg if confirmation is done

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

    public function firstTrial($data){

        if( isset($data['before_three_month_trial_count']) && isset($data['trial_count'])){

            if($data['before_three_month_trial_count'] == 0 || $data['trial_count'] == 0){

                $this->findermailer->firstTrial($data);
                $this->findersms->firstTrial($data);
            }

        }

        return true;

    }


    public function nutritionStore(){

        $data = Input::json()->all();

        $rules = array(
            'customer_name'=>'required',
            'customer_email'=>'required|email',
            'customer_phone'=>'required',
            'finder_id'=>'required',
        );

        $validator = Validator::make($data,$rules);

        if($validator->fails()) {

            $resp = array('status' => 404,'message' => error_message($validator->errors()));
            return Response::json($resp,$resp['status']);
        }

        $data['customer_id'] = (int)autoRegisterCustomer($data);

        if(isset($data['myreward_id']) && $data['myreward_id'] != ""){
            $createMyRewardCapture = $this->customerreward->createMyRewardCapture($data);

            if($createMyRewardCapture['status'] !== 200){

                return Response::json($createMyRewardCapture,$createMyRewardCapture['status']);
            }
        }

        $finderData = $this->getFinderData($data['finder_id']);
        $data  = array_merge($data,$finderData);
        $data['type'] = 'nutrition';
        $code = $data['code'] = random_numbers(5);
        $off = $data['off'] = "20%";

        $store = new Store($data);
        $store_id = $store->_id = Store::max('_id') + 1;
        $store->save();

        $redisid = Queue::connection('redis')->push('SchedulebooktrialsController@toQueueNutritionStore', array('store_id'=>$store_id),Config::get('app.queue'));

        $store->update(array('redis_id'=>$redisid));

        $resp   =   array('status' => 200,'code' => $code);

        return Response::json($resp,200);

    }

    public function toQueueNutritionStore($job,$data){

        $job->delete();

        try{

            $store = Store::find((int)$data['store_id']);

            $sndInstantEmailCustomer       =    $this->customermailer->nutritionStore($store->toArray());
            $sndInstantSmsCustomer         =    $this->customersms->nutritionStore($store->toArray());
            $sndInstantEmailFinder         =    $this->findermailer->nutritionStore($store->toArray());
            $sndInstantSmsFinder           =    $this->findersms->nutritionStore($store->toArray());

        }catch(Exception $e){

            Log::error($e);
        }
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


    public function cancelByslot(){

        $data = Input::json()->all();

        $rules = [
            'finder_id' => 'required',
            'service_id' => 'required',
            'slot' => 'required',
            'date' => 'required',
            'reason' => 'required',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {

            $response = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));

        }else{

            $finder_id = $data['finder_id'];
            $service_id = $data['service_id'];
            $schedule_slot = $data['slot'];
            $schedule_date = $data['date'];
            $reason = $data['reason'];

            $schedule_start_date_time = new DateTime(date("d-m-Y 00:00:00", strtotime($schedule_date)));
            $schedule_end_date_time = new DateTime(date("d-m-Y 00:00:00", strtotime($schedule_date."+ 1 days")));

            $booktrial = Booktrial::where("finder_id",(int)$finder_id)->where("service_id",(int)$service_id)->where("schedule_slot",$schedule_slot)->where('schedule_date_time', '>=',$schedule_start_date_time)->where('schedule_date_time', '<=',$schedule_end_date_time)->get();

            if(count($booktrial) > 0){

                foreach ($booktrial as $key => $value) {

                    $this->cancel($value->_id,'vendor', $reason);
                }

            }

            $response = array('status' => 200,'message' =>'success');
        }

        return Response::json($response, $response['status']);

    }



    public function getCalorieBurnByServiceCategoryId($category_id){

        $sericecategorysArr = [
            65      => 600,
            1       => 250,
            2       => 450,
            4       => 350,
            5       => 450,
            19      => 700,
            86      => 450,
            111     => 800,
            114     => 400,
            123     => 750,
            152     => 450,
            154     => 300,
            3       => 450,
            161     => 650,
            184     => 400
        ];

        $category_calorie_burn = 300;

        if(isset($sericecategorysArr[$category_id])){
            $category_calorie_burn = $sericecategorysArr[$category_id];
        }

        return $category_calorie_burn;

    }

    public function disableTrial($data){

        $finder     =   Finder::find(intval($data['finder_id']));

        if(isset($finder['trial']) && $finder['trial'] == "disable"){

            $message = "Sorry, this class is not available. Kindly book a different slot";

            return array('status' => 400,'message' => $message);
        }

        if(isset($data['service_id']) && $data['service_id'] != ""){

            $service = Service::find((int)$data['service_id']);

            if(isset($service['trial']) && $service['trial'] == "disable"){

                $message = "Sorry, this class is not available. Kindly book a different slot";

                return array('status' => 400,'message' => $message);
            }
        }

        return array('status' => 200,'message' => 'success');
    }



    public function sendManualCommunication($id){

        // $booktrial = Booktrial::findOrFail(intval($id));

        // $dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date','followup_date','auto_followup_date');

        // foreach ($dates as $key => $value) {
        //     if(isset($booktrial[$value])){
        //         if($booktrial[$value] == "-" || $booktrial[$value] == ""){

        //             $booktrial->unset($value);
        //         }
        //     }

        // }


        // $booktrialdata = $booktrial->toArray();
        // Log::info("before call");
        // return $this->customersms->bookTrial($booktrialdata, "2017-07-07 7pm");

        $this->sendCommunication(null, array('booktrial_id'=>intval($id)));
        return "done";
    }
 


    public function booktrialWithoutReward(){
        try{
            $data = Input::json()->all();
            $tempbooktrial_id = Tempbooktrial::max('_id')+1 ? (Tempbooktrial::max('_id')+1) : 0;
            $tempbooktrial = new Tempbooktrial($data);
            $tempbooktrial->_id = $tempbooktrial_id;
            $tempbooktrial->save();
            Log::info("delete");
            return array('status'=>200, 'temp_id'=>$tempbooktrial->_id);
        }catch(Exception $e){
            Log::info($e);
            return array('message'=>'Try after some time');
        }
        
        
        
    }

    public function transactionSummary(){

        $item = Input::json()->all();

        $rules = [
            'ratecard_id' => 'required',
            'type' => 'required'
        ];

        $validator = Validator::make($item,$rules);

        if($validator->fails()) {

            return Response::json(array('status' => 401,'message' =>$this->errorMessage($validator->errors())),401);
        }

        $ratecard_id = (int)$item['ratecard_id'];

        $ratecard = Ratecard::find($ratecard_id);
        
        if(!(isset($item['manual_order']) && $item['manual_order'] && isset($item['amount']) && $item['amount'] != '')){
            
            
            if(isset($ratecard['special_price']) && $ratecard['special_price'] != 0){
                $item['amount'] = $ratecard['special_price'];
            }else{
                $item['amount'] = $ratecard['price'];
            }
            
            $item['ratecard_remarks']  = (isset($ratecard['remarks'])) ? $ratecard['remarks'] : "";
        }

        $service = Service::find($ratecard['service_id']);

        $finder_name = "";
        $finder_location = "";
        $finder_address = "";
        $finder_id = "";

        $finder = Finder::with(array('city'=>function($query){$query->select('name','slug');}))->with(array('location'=>function($query){$query->select('name','slug');}))->find((int)$ratecard['finder_id'],array('_id','title','location_id','contact','lat','lon','manual_trial_auto','city_id','brand_id'));

        if(isset($finder['_id']) && $finder['_id'] != ""){
            $finder_id = $finder['_id'];
        }

        if(isset($finder['title']) && $finder['title'] != ""){
            $finder_name = ucwords($finder['title']);
        }

        if(isset($finder['location']['name']) && $finder['location']['name'] != ""){
            $finder_location = ucwords($finder['location']['name']);
        }

        if(isset($finder['contact']['address']) && $finder['contact']['address'] != ""){
            $finder_address = $finder['contact']['address'];
        }

        if(isset($service['address']) && $service['address'] != ""){
            $finder_address = $service['address'];
        }

        if(isset($item['schedule_slot']) && $item['schedule_slot'] != ""){
            $slot_times                        =    explode('-',$item['schedule_slot']);
            $item['schedule_slot_start_time']          =    $slot_times[0];
            $item['schedule_slot_end_time']            =    $slot_times[1];
        }

        $poc = $poc_name = $poc_number = "";

        if(isset($finder['finder_poc_for_customer_name']) && $finder['finder_poc_for_customer_name'] != ""){
            $poc_name = $finder['finder_poc_for_customer_name'];
        }

        if(isset($finder['finder_poc_for_customer_no']) && $finder['finder_poc_for_customer_no'] != ""){
            $poc_number = " (".$finder['finder_poc_for_customer_no'].")";
        }

        $poc = $poc_name.$poc_number;


        $booking_details = [];

        $position = 0;

        $service_duration = $this->getServiceDuration($ratecard);

        $booking_details_data["finder_name_location"] = ['field'=>'BOOKING AT','value'=>$finder_name.", ".$finder_location,'position'=>$position++];

        $booking_details_data["service_name"] = ['field'=>'SERVICE','value'=>$service['name'],'position'=>$position++,'image'=>'https://b.fitn.in/global/tabapp-homescreen/freetrail-summary/service.png'];

        $booking_details_data["service_duration"] = ['field'=>'DURATION','value'=>$service_duration,'position'=>$position++];

        $booking_details_data["start_date"] = ['field'=>'DAY & DATE','value'=>'-','position'=>$position++,'image'=>'https://b.fitn.in/global/tabapp-homescreen/freetrail-summary/dayanddate.png'];

        $booking_details_data["start_time"] = ['field'=>'TIME','value'=>'-','position'=>$position++];

        $booking_details_data["address"] = ['field'=>'ADDRESS','value'=>'','position'=>$position++];

        if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && isset($finder['brand_id']) && (($finder['brand_id'] == 66 && $finder['city_id'] == 3) || $finder['brand_id'] == 88)){
            
            $booking_details_data["price"] = ['field'=>'AMOUNT','value'=>'Free','position'=>$position++,'image'=>'https://b.fitn.in/global/tabapp-homescreen/freetrail-summary/amount.png'];

        }else{

            $booking_details_data["price"] = ['field'=>'AMOUNT','value'=>'Free Via Fitternity','position'=>$position++,'image'=>'https://b.fitn.in/global/tabapp-homescreen/freetrail-summary/amount.png'];
        }

        if($poc != ""){ 
            $booking_details_data["poc"] = ['field'=>'POINT OF CONTACT','value'=>$poc,'position'=>$position++];
        }

        if(isset($item['start_date']) && $item['start_date'] != ""){
            $booking_details_data['start_date']['value'] = date('d-m-Y (l)',strtotime($item['start_date']));
        }

        if(isset($item['schedule_date']) && $item['schedule_date'] != ""){
            $booking_details_data['start_date']['value'] = date('l d-m-Y (l)',strtotime($item['schedule_date']));
        }

        if(isset($item['preferred_starting_date']) && $item['preferred_starting_date'] != ""){
            $booking_details_data['start_date']['value'] = date('d-m-Y (l)',strtotime($item['preferred_starting_date']));
        }

        if(isset($item['start_time']) && $item['start_time'] != ""){
            $booking_details_data['start_time']['value'] = strtoupper($item['start_time']);
        }

        if(isset($item['schedule_slot_start_time']) && $item['schedule_slot_start_time'] != ""){
            $booking_details_data['start_time']['value'] = strtoupper($item['schedule_slot_start_time']);
        }

        if(isset($item['amount']) && $item['amount'] != ""){
            $booking_details_data['price']['value'] = "Rs. ".(string)$item['amount'];
        }

        if(isset($item['amount_finder']) && $item['amount_finder'] != ""){
            $booking_details_data['price']['value']= "Rs. ".(string)$item['amount_finder'];
        }

        if(isset($item['payment_mode']) && $item['payment_mode'] == "cod"){
            $booking_details_data['price']['value']= "Rs. ".(string)$item['amount']." (Cash Pickup)";
        }

        if(isset($item['myreward_id']) && $item['myreward_id'] != ""){

            $booking_details_data['price']['value']= "Free Via Fitternity";

            if($this->kiosk_app_version &&  $this->kiosk_app_version >= 1.13 && isset($finder['brand_id']) && (($finder['brand_id'] == 66 && $finder['city_id'] == 3) || $finder['brand_id'] == 88)){

                $booking_details_data['price']['value'] = "Free";
            }
        }

        if(isset($item['part_payment']) && $item['part_payment']){
            $header= "Membership reserved";
        }

        if(isset($item['payment_mode']) && $item['payment_mode'] == 'cod'){
            $subline= "Your membership will be activated once your cash is collected. Fitternity team will reach out to you to coordinate the cash pick-up.";
        }

        if($finder_address != ""){
            $booking_details_data['address']['value'] = $finder_address;
        }

        if(in_array($item['type'],["healthytiffintrail","healthytiffintrial","healthytiffinmembership"])){
            
            if(isset($item['customer_address']) && $item['customer_address'] != ""){
                $booking_details_data['address']['value'] = $item['customer_address'];
            }

            if(isset($item['address']) && $item['address'] != ""){
                $booking_details_data['address']['value'] = $item['address'];
            }

        }else{

            if($finder_address != ""){
                $booking_details_data['address']['value'] = $finder_address;
            }
            if(isset($item['finder_address']) && $item['finder_address'] != ""){
                $booking_details_data['address']['value'] = $item['finder_address'];
            }
        }

        if(isset($booking_details_data['address']['value'])){

            $booking_details_data['address']['value'] = str_replace("  ", " ",$booking_details_data['address']['value']);
            $booking_details_data['address']['value'] = str_replace(", , ", "",$booking_details_data['address']['value']);
        }

        if(in_array($item['type'], ['manualtrial','manualautotrial','manualmembership'])){
            $booking_details_data["start_date"]["field"] = "PREFERRED DATE";
            $booking_details_data["start_time"]["field"] = "PREFERRED TIME";
            $booking_details_data["price"]["value"] = "";
        }

        if(in_array($item['type'], ['booktrialfree','booktrial','workout-session'])){
            $booking_details_data["start_date"]["field"] = "DATE";
            $booking_details_data["start_time"]["field"] = "TIME";
            $booking_details_data["service_duration"]["value"] = "1 Session";
        }

        if(isset($item['preferred_day']) && $item['preferred_day'] != ""){
            $booking_details_data['start_date']['field'] = 'PREFERRED DAY';
            $booking_details_data['start_date']['value'] = $item['preferred_day'];
        }

        if(isset($item['preferred_time']) && $item['preferred_time'] != ""){
            $booking_details_data['start_time']['field'] = 'PREFERRED TIME';
            $booking_details_data['start_time']['value'] = $item['preferred_time'];
        }

        if(isset($item['"preferred_service']) && $item['"preferred_service'] != "" && $item['"preferred_service'] != null){
            $booking_details_data['service_name']['field'] = 'PREFERRED SERVICE';
            $booking_details_data['service_name']['value'] = $item['preferred_service'];
        }

        if(in_array($item['type'],["healthytiffintrial","healthytiffintrail"]) && isset($item['ratecard_remarks']) && $item['ratecard_remarks'] != ""){
            $booking_details_data['service_duration']['value'] = ucwords($item['ratecard_remarks']);
        }

        if(in_array($item['type'],["healthytiffintrail","healthytiffintrial","healthytiffinmembership"])){
            $booking_details_data['finder_name_location']['field'] = 'BOUGHT AT';
            $booking_details_data['finder_name_location']['value'] = $finder_name;
        }

        if($this->vendor_token && in_array($item['type'],["booktrials","workout-session"])){
            $booking_details_data = array_only($booking_details_data, ['service_name','price','start_date']);
        }

       
        $booking_details_all = [];

        foreach ($booking_details_data as $key => $value) {

            switch ($key) {
                case 'service_name':
                case 'price':
                case 'start_date':
                    $booking_details_all[$value['position']] = [
                        'field'=>$value['field'],
                        'value'=>$value['value'],
                        'image'=>$value['image']
                    ];
                    break;
                default: 
                    $booking_details_all[$value['position']] = [
                        'field'=>$value['field'],
                        'value'=>$value['value']
                    ];
                    break;
            }            
        }

        foreach ($booking_details_all as $key => $value) {

            if($value['value'] != "" && $value['value'] != "-"){
                $booking_details[] = $value;
            }

        }

        $response = array('status' => 200,'summary' => $booking_details);

        $response['assisted_by_image'] = "https://b.fitn.in/global/tabapp-homescreen/freetrail-summary/trainer.png";
        $response['assisted_by'] = $this->utilities->getVendorTrainer($finder_id);

        return Response::json($response, $response['status']);

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


    public function locateTrial($code){

        $decodeKioskVendorToken = decodeKioskVendorToken();

        $vendor = json_decode(json_encode($decodeKioskVendorToken->vendor),true);

        $response = array('status' => 400,'message' =>'Sorry! Cannot locate your booking');

        Log::info("Kiosk find at vendor ".$vendor['_id']."and the code used :".$code);
        
        $booktrial = Booktrial::where('code',$code)
           ->where('finder_id',(int)$vendor['_id'])
           ->where('schedule_date_time','>',new MongoDate(strtotime(date('Y-m-d 00:00:00'))))
           ->where('schedule_date_time','<',new MongoDate(strtotime(date('Y-m-d 23:59:59'))))
           ->with('category')
           ->with('city')
           ->orderBy('_id','desc')
           ->first();

        $locate_data = [
            'code'=>$code,
            'finder_id'=>(int)$vendor['_id'],
        ];
        
        $locateTransaction = LocateTransaction::create($locate_data); 

        if(isset($booktrial)){

            $locateTransaction->transaction_id = (int)$booktrial['_id'];
            $locateTransaction->transaction_type = 'Booktrial';
            $locateTransaction->update();

            $customerCapture = CustomerCapture::where('booktrial_id',(int)$booktrial['_id'])->first();

            if($customerCapture){

                $response = array('status' => 400,'message' =>'Already located your booking');

                return Response::json($response,200);

            }

            $url = Config::get('app.url')."/locatetrialcommunication/".$booktrial["_id"];

            if(!isset($booktrial->customerCommunicationAfterOneHour) && empty($booktrial['third_party_details'])){

            	if($booktrial->type != "workout-session")
                $booktrial->customerCommunicationAfterOneHour = $this->utilities->hitURLAfterDelay($url,date('Y-m-d H:i:s',strtotime("+1 hours",time())));
            }

            if(isset($booktrial->vendor_kiosk) && $booktrial->vendor_kiosk && ($booktrial->type == "booktrials"||$booktrial->type=='workout-session')&& !isset($booktrial->post_trial_status_updated_by_kiosk) && empty($booktrial['third_party_details'])){

            	Log::info(" info ".print_r(" AAYA ",true));
            	
            	Log::info(" info ".print_r($booktrial->type,true));

                if($booktrial->type!='workout-session')
                {
	                	$req = array(
	                			"customer_id"=>$booktrial['customer_id'],
	                			"trial_id"=>$booktrial['_id'],
	                			"amount"=> 250,
	                			"amount_fitcash" => 0,
	                			"amount_fitcash_plus" => 250,
	                			"type"=>'CREDIT',
	                			'entry'=>'credit',
                                'for'=>'locate_trial',
	                			'validity'=>time()+(86400*7),
	                			'description'=>"Added FitCash+ on Trial Attendance, Expires On : ".date('d-m-Y',time()+(86400*7))
	                	);
	                	
	                	$this->utilities->walletTransaction($req);
                }
                
                else
                {
                	try {
                        $fitcash = 0;
                        if(!isset($booktrial['extended_validity_order_id']) && !isset($booktrial['pass_order_id'])){
                            $fitcash = round($this->utilities->getWorkoutSessionFitcash($booktrial->toArray()) * $booktrial->amount_finder / 100);
                            
                            $req = array(
                                    "customer_id"=>$booktrial['customer_id'],
                                    "trial_id"=>$booktrial['_id'],
                                    "amount"=> $fitcash,
                                    "amount_fitcash" => 0,
                                    "amount_fitcash_plus" => $fitcash,
                                    "type"=>'CREDIT',
                                    'entry'=>'credit',
                                    'for'=>'locate_trial',
                                    'validity'=>time()+(86400*7),
                                    'description'=>"Added FitCash+ on Trial Attendance, Expires On : ".date('d-m-Y',time()+(86400*7))
                            );
                            
                            $this->utilities->walletTransaction($req);
                        }
                		
                		Log::info(" info fitcash  ".print_r($fitcash,true));
                		$booktrial->pps_pending_amount=$booktrial->amount;
                		$booktrial->pps_fitcash=$fitcash;
                		if(isset($booktrial->type)&&$booktrial->type=='workout-session'&&isset($booktrial->order_id))
                		$booktrial->pps_payment_link=Config::get('app.website').'/paymentlink/'.$booktrial->order_id;
                		else $booktrial->pps_payment_link=Config::get('app.website');
                		
                		$booktrial->pps_srp_link=Config::get('app.website');

                        if(!empty($booktrial->category) && !empty($booktrial->category->name) && !empty($booktrial->city) &&!empty($booktrial->city->name)){
                            $booktrial->pps_srp_link=Config::get('app.website').'/'.$booktrial->city->name.'/'.newcategorymapping($booktrial->category->name);
                        }
                        
                        if(!isset($booktrial['extended_validity_order_id']) && !isset($booktrial['pass_order_id'])){
                            $sendComm = $booktrial->send_communication;
                			if(isset($booktrial->pay_later)&&$booktrial->pay_later!=""&&$booktrial->pay_later==true) {
                				$sendComm['customer_sms_paypersession_FitCodeEnter_PayLater'] = $this->customersms->workoutSmsOnFitCodeEnterPayLater($booktrial->toArray());
                            }
                			else {
                                $sendComm['customer_sms_paypersession_FitCodeEnter'] = $this->customersms->workoutSmsOnFitCodeEnter($booktrial->toArray());
                            }
                            $booktrial->send_communication = $sendComm;
                        }		
                				$this->deleteTrialCommunication($booktrial);
                        
                	} catch (Exception $e) {
                		
                		Log::error(" Error [ locateTrial ] ".$e->getMessage());
                	}
                	
                	
                }

            }

            $booktrial->post_trial_status = 'attended';

            $this->utilities->updateOrderStatus($booktrial);
            
            $booktrial->post_trial_initail_status = 'interested';
            $booktrial->post_trial_status_updated_by_kiosk = time();
            $booktrial->post_trial_status_date = time();
            $booktrial->update();

            if(!empty($booktrial['corporate_id'])){
                // $this->relianceService->updateServiceStepCount(['booktrialId'=>$booktrial['_id'], 'deviceDate'=>time()]);
                Queue::connection('redis')->push('RelianceController@updateServiceStepCountJob', array('booktrialId'=>$booktrial->_id, 'deviceDate'=>time()),Config::get('app.queue'));
            }

            if(isset($booktrial['send_communication']['customer_sms_after24hour']) && $booktrial['send_communication']['customer_sms_after24hour'] != ""){
                         
                $booktrial->unset('customer_sms_after24hour');
             
                $this->sidekiq->delete($booktrial['customer_sms_after24hour']);
            
            }

            $message = "Hi ".ucwords($booktrial['customer_name']).", your booking at ".ucwords($booktrial['finder_name'])." for ".strtoupper($booktrial['schedule_slot_start_time'])." on ".date('D, d M Y',strtotime($booktrial['schedule_date']))." has been successfully located";

            $createCustomerToken = createCustomerToken((int)$booktrial['customer_id']);

            $kiosk_form_url = Config::get('app.website').'/kiosktrialform?booktrial_id='.$booktrial['_id'];


            $response = [
                'status' => 200,
                'message' => $message,
                'token'=>$createCustomerToken,
                'booktrial_id'=> (int)$booktrial['_id'],
                'kiosk_form_url'=>$kiosk_form_url
            ];

            $data = [
                'booked_locate'=>'locate',
                'finder_id'=>(int)$booktrial['finder_id']
            ];

            $response = array_merge($response,$this->utilities->trialBookedLocateScreen($data));
        }

        return Response::json($response,200);

    }

    public function locateTrialCommunication($booktrial_id){

        $booktrial_id = (int)$booktrial_id;

        $booktrial = Booktrial::find((int)$booktrial_id);

        $response = [
            'status' => 400,
            'message' => 'off pubnub',
        ]; 

        if($booktrial){

            $this->pubnub($booktrial->toArray());

            $booktrialdata = $booktrial->toArray();
            
            $booktrialdata['wallet_balance'] = $this->utilities->getWalletBalance((int)$booktrialdata['customer_id']);

            $send_communication["customer_email_locate_trial_plus_1"] = $this->customermailer->locateTrialReminderAfter1Hour($booktrialdata);
            $send_communication["customer_sms_locate_trial_plus_1"] = $this->customersms->locateTrialReminderAfter1Hour($booktrialdata);

            $response = [
                'status' => 200,
                'message' => 'on pubnub',
            ]; 

        }

        return Response::json($response, $response['status']);
    }

    public function pubNub($data){

        $finder_id = (int) $data['finder_id'];
        $booktrial_id = (int) $data['_id'];

        $finder = Finder::with(array('location'=>function($query){$query->select('name','slug');}))->with(array('city'=>function($query){$query->select('name','slug');}))->find((int)$finder_id);

        $array['finder_id'] = $finder_id;
        $array['finder_name'] = ucwords($finder['title']);
        $array['finder_commercial_type'] = (int)$finder['commercial_type'];
        $array['finder_location'] = ucwords($finder['location']['name']);
        $array['finder_city'] = ucwords($finder['city']['name']);
        $array['customer_number'] = $data['customer_phone'];
        $array['customer_name'] = $data['customer_name'];
        $array['booktrial_id'] = $booktrial_id;
        $array['vendor'] = ucwords($finder['title'])." | ".ucwords($finder['location']['name'])." | ".ucwords($finder['city']['name']);
        $array['finder_slug'] = $finder['slug'];

        Log::info("pubNub array : ",$array);

        $pubnub = new \Pubnub\Pubnub(Config::get('app.pubnub_publish'), Config::get('app.pubnub_sub'));
 
        $pubnub->publish('fitternity_ozonetel',$array);

        return 'success';
    }

    public function payLaterPaymentSuccess($order_id){

        $order = Order::find($order_id);

        array_set($orderData, 'status', '1');
        array_set($orderData, 'order_action', 'bought');
        array_set($orderData, 'success_date', date('Y-m-d H:i:s',time()));
        
        if(isset($order->payment_mode) && $order->payment_mode == "paymentgateway"){
            array_set($orderData, 'secondary_payment_mode', 'payment_gateway_membership');
        }

        $order->update($orderData);

        $booktrial_id = $order->booktrial_id;

        $booktrial = Booktrial::find($booktrial_id);

        $booktrial->payment_done = true;
        
        if(time() > strtotime($booktrial->schedule_date_time) || !empty($order['qrcodepayment'])){

            $booktrial->post_trial_status = 'attended';

            $booktrial->post_trial_payment_fitcash = true;

            $this->unsetEmptyDates($booktrial);
            
            $fitcash = round($this->utilities->getWorkoutSessionFitcash($booktrial->toArray()) * $booktrial->amount_finder / 100);
            
            $req = array(
                "customer_id"=>$booktrial['customer_id'],
                "trial_id"=>$booktrial['_id'],
                "amount"=> $fitcash,
                "amount_fitcash" => 0,
                "amount_fitcash_plus" => $fitcash,
                "type"=>'CREDIT',
                'entry'=>'credit',
                'validity'=>time()+(86400*21),
                'description'=>"Added FitCash+ on Session Attendance at ".ucwords($booktrial['finder_name'])." Expires On : ".date('d-m-Y',time()+(86400*21)),
            );
    
            $this->utilities->walletTransaction($req);
        }

        $booktrial->update();

        // $pay_later = Paylater::where('customer_id', $booktrial->customer_id)->first();

        // if($pay_later){
        //     Log::info("Updating pay later entry");
            // $trial_ids = $pay_later->trial_ids;
    
            // if(count($trial_ids) == 1){
                
                // Paylater::destroy($pay_later->_id);
            
            // }else{
    
            //     $key = array_search($booktrial_id, $pay_later);
        
            //     unset($trial_ids[$key]);
        
            //     $pay_later->trial_ids = $trial_ids;
        
            //     $pay_later->update();
            // }
        // }

        // $resp 	= 	array('status' => 200, 'statustxt' => 'success', 'order' => $order, "message" => "Transaction Successful :)");

        $resp 	= 	array('status' => 200, 'booktrialid' => $booktrial_id, 'message' => "Book a Trial", 'code' => $booktrial->code, 'pay_later_payment'=>true);

        return $resp;

    }
    public function verifyFitCode($booktrial_id,$vendor_code){

        $booktrial_id = (int) $booktrial_id;
        $vendor_code = (int) $vendor_code;
        
        $response = array('status' => 400,'message' =>'Sorry! Cannot locate your booking');

        $jwt_token = Request::header('Authorization');
        $decoded = customerTokenDecode($jwt_token);

        $customer_id = (int)$decoded->customer->_id;
        





        $booktrial = Booktrial::where('vendor_code',$vendor_code)
           ->where('customer_id',$customer_id)
           ->where('_id',$booktrial_id)
           ->whereIn('type',['booktrials','3daystrial', 'workout-session'])
           ->where('third_party_details','exists',false)
           ->with('category')
           ->with('city')
           // ->where('schedule_date_time','>',new MongoDate(strtotime(date('Y-m-d 00:00:00'))))
           // ->where('schedule_date_time','<',new MongoDate(strtotime(date('Y-m-d 23:59:59'))))
           // ->orderBy('_id','desc')
           ->first();
        $message = '';
        $fitcash = 0;
        if(isset($booktrial)){

            if(
                $booktrial->type == "booktrials" && 
                !isset($booktrial->post_trial_status_updated_by_fitcode) && 
                !isset($booktrial->post_trial_status_updated_by_lostfitcode) 
            ){

                $post_trial_status_updated_by_fitcode = time();
                $booktrial_update = Booktrial::where('_id', intval($booktrial_id))->where('post_trial_status_updated_by_fitcode', 'exists', false)->update(['post_trial_status_updated_by_fitcode'=>$post_trial_status_updated_by_fitcode]);
                if($booktrial_update && (!isset($booktrial['extended_validity_order_id']))){

                    Log::info("Adding trial fitcash");

                    $fitcash = $this->utilities->getFitcash($booktrial->toArray());

                    $req = array(
                        "customer_id"=>$booktrial['customer_id'],
                        "trial_id"=>$booktrial['_id'],
                        "amount"=> $fitcash,
                        "amount_fitcash" => 0,
                        "amount_fitcash_plus" => $fitcash,
                        "type"=>'CREDIT',
                        'entry'=>'credit',
                        'validity'=>time()+(86400*21),
                        'description'=>"Added FitCash+ on Trial Attendance By Fitcode, Applicable for buying a membership at ".ucwords($booktrial['finder_name'])." Expires On : ".date('d-m-Y',time()+(86400*21)),
                        "valid_finder_id"=>intval($booktrial['finder_id']),
                        "finder_id"=>intval($booktrial['finder_id']),
                    );
                    
                    $this->utilities->walletTransaction($req);
                    
                    $message = "Hi ".ucwords($booktrial['customer_name']).", Rs.".$fitcash." Fitcash is added in your wallet on your attendace . Use it to buy ".ucwords($booktrial['finder_name'])."'s membership at lowest price. Valid for 21 days";
                }
                else{
                    $message = "Thank you, your attendance has been marked.";
                }
            }else if(
                $booktrial->type == "workout-session" && 
                !isset($booktrial->post_trial_status_updated_by_fitcode) && 
                !(isset($booktrial->payment_done) && !$booktrial->payment_done) && 
                !isset($booktrial->post_trial_status_updated_by_lostfitcode) &&
                !isset($booktrial['pass_order_id'])
            ){

                $post_trial_status_updated_by_fitcode = time();
                $booktrial_update = Booktrial::where('_id', intval($booktrial_id))->where('post_trial_status_updated_by_fitcode', 'exists', false)->update(['post_trial_status_updated_by_fitcode'=>$post_trial_status_updated_by_fitcode]);

                if($booktrial_update){

                    Log::info("Adding pps fitcash");
                    
                    if(!isset($booktrial['extended_validity_order_id'])){
                        $fitcash = $this->utilities->getFitcash($booktrial->toArray());
                    }
                    $req = array(
                        "customer_id"=>$booktrial['customer_id'],
                        "trial_id"=>$booktrial['_id'],
                        "amount"=> $fitcash,
                        "amount_fitcash" => 0,
                        "amount_fitcash_plus" => $fitcash,
                        "type"=>'CREDIT',
                        'entry'=>'credit',
                        'validity'=>time()+(86400*21),
                        'description'=>"Added FitCash+ on Session Attendance at ".ucwords($booktrial['finder_name'])." Expires On : ".date('d-m-Y',time()+(86400*21)),
                    );
                    
                    //added check and message
                    $booktrial->pps_fitcash=$fitcash;
                    $booktrial->pps_cashback=$this->utilities->getWorkoutSessionLevel((int)$booktrial->customer_id)['current_level']['cashback'];
                    
                    $booktrial->pps_srp_link=Config::get('app.website');

                    if(!empty($booktrial->category) && !empty($booktrial->category->name) && !empty($booktrial->city) &&!empty($booktrial->city->name)){
                        $booktrial->pps_srp_link=Config::get('app.website').'/'.$booktrial->city->name.'/'.newcategorymapping($booktrial->category->name);
                    }

                    
                    $temp=$booktrial->send_communication;
                    if(!isset($booktrial['extended_validity_order_id'])){
                        if(isset($booktrial->pay_later)&&$booktrial->pay_later!=""&&$booktrial->pay_later==true)
                            $temp['customer_sms_paypersession_FitCodeEnter_PayLater']=$this->customersms->workoutSmsOnFitCodeEnterPayLater($booktrial->toArray());
                        else $temp['customer_sms_paypersession_FitCodeEnter']=$this->customersms->workoutSmsOnFitCodeEnter($booktrial->toArray());
                    }
                    $this->deleteTrialCommunication($booktrial);
                            
                    if(!isset($booktrial['extended_validity_order_id'])){
                        $this->utilities->walletTransaction($req);
                        $message = "Hi ".ucwords($booktrial['customer_name']).", Rs.".$fitcash." Fitcash is added in your wallet on your attendace . Valid for 21 days";
                    }
                    else{
                        $message = "Thank you, your attendance has been marked.";
                    }
                }
            }

            $booktrial->post_trial_status = 'attended';

            $this->utilities->updateOrderStatus($booktrial);
            
            $booktrial->post_trial_initail_status = 'interested';
            $booktrial->post_trial_status_updated_by_fitcode = time();
            $booktrial->post_trial_status_date = time();
            $booktrial->update();

            // $message = "Hi ".ucwords($booktrial['customer_name']).", Rs.".$fitcash." Fitcash is added in your wallet on your attendace . Use it to buy ".ucwords($booktrial['finder_name'])."'s membership at lowest price. Valid for 21 days";

            $response = [
                'status' => 200,
                'message' => $message,
                'booktrial_id'=> (int)$booktrial['_id'],
                'fitcash'=>$fitcash
            ];

            $this->utilities->addCheckin(['customer_id'=>$booktrial['customer_id'], 'finder_id'=>$booktrial['finder_id'], 'type'=>'workout-session', 'sub_type'=>$booktrial['type'], 'fitternity_customer'=>true, 'tansaction_id'=>$booktrial['_id'],"checkout_status"=> true, 'device_token' => $this->device_token]);
            if(!empty($booktrial->corporate_id)) {
                // $this->relianceService->updateServiceStepCount();
                $orderId = null;
                if(!empty($booktrial['order_id'])) {
                    $orderId = $booktrial->order_id;
                }
                \Queue::connection('redis')->push('RelianceController@updateServiceStepCountJob', array('booktrialId'=>$booktrial->_id, 'deviceDate'=>time()),Config::get('app.queue'));
            }
        }

        return Response::json($response,200);

    }

    
    
    public function lostFitCode($booktrial_id){
        
        Log::info($_SERVER['REQUEST_URI']);
        $booktrial_id = (int) $booktrial_id;

        $response = array('status' => 400,'message' =>'Sorry! Cannot locate your booking');

        $jwt_token = Request::header('Authorization');
        $decoded = customerTokenDecode($jwt_token);

        $customer_id = (int)$decoded->customer->_id;
        
        $booktrial = Booktrial::where('_id',$booktrial_id)
           ->where('customer_id',$customer_id)
           ->whereIn('type',['booktrials','3daystrial','workout-session'])
           // ->where('schedule_date_time','>',new MongoDate(strtotime(date('Y-m-d 00:00:00'))))
           // ->where('schedule_date_time','<',new MongoDate(strtotime(date('Y-m-d 23:59:59'))))
           // ->orderBy('_id','desc')
           ->first();

        if(isset($booktrial)){

            $booktrial->post_trial_status = 'attended';

            $lostfitcode = !empty($booktrial->lostfitcode) ? (object)$booktrial->lostfitcode : new stdClass();
            // $reason = 'didnt_get_fitcode';
            $reason_message_array = [
                "Thanks for your feedback"
            ];
            $reason_message = null;
            if(!empty($_GET['reason'])){
                $key = intval($_GET['reason']) - 1;
                $lostcode_reasons_array = ["not_interested_in_fitcash","lost_fitcode","didnt_get_fitcode"];
                if(isset($booktrial['third_party_details'])){
                    $lostcode_reasons_array = array_merge($lostcode_reasons_array, ["i_didn't_get_the_fitcode_from_the_fitness_centre","the_fitcode_given_is_invalid","i_am_getting_an_error_while_submitting_the_fitcode"]);
                }
                $reason = $lostcode_reasons_array[$key];
                $lostfitcode->$reason = time();
                $reason_message = (isset($reason_message_array[$key])) ? $reason_message_array[$key] : null;
            }
            
            if(empty($_GET['reason'])){
                $reason = 'didnt_get_fitcode';
                $lostfitcode->$reason= time();
            }
            
            $booktrial->lostfitcode = $lostfitcode;
            
            $fitcash_amount = 0;
            // if(!isset($booktrial['third_party_details'])){
            //     $fitcash_amount = $this->utilities->getFitcash($booktrial);
            // }

            $device_type = Request::header('Device-Type');
            // if(in_array($device_type, ['ios', 'android']) && empty($booktrial->post_trial_status_updated_by_lostfitcode) && empty($booktrial->post_trial_status_updated_by_fitcode)){
            if( !empty($fitcash_amount) && !(isset($_GET['reason']) && $_GET['reason'] == 1) && empty($booktrial->post_trial_status_updated_by_lostfitcode) && empty($booktrial->post_trial_status_updated_by_fitcode)){

                $update = Booktrial::where('_id',$booktrial['_id'])->where('post_trial_status_updated_by_lostfitcode', 'exists', false)->where('post_trial_status_updated_by_fitcode', 'exists', false)->update(['post_trial_status_updated_by_lostfitcode'=>time()]);

                if($update && !(isset($booktrial['third_party_details'])) && !isset($booktrial['extended_validity_order_id'])){
                    $req = array(
                            "customer_id"=>$booktrial['customer_id'],
                            "trial_id"=>$booktrial['_id'],
                            "amount"=> $fitcash_amount,
                            "amount_fitcash" => 0,
                            "amount_fitcash_plus" => $fitcash_amount,
                            "type"=>'CREDIT',
                            'entry'=>'credit',
                            'validity'=>time()+(86400*21),
                            'description'=>"Added FitCash+ on Lost Fitcode, Applicable for buying a membership at ".ucwords($booktrial['finder_name'])." Expires On : ".date('d-m-Y',time()+(86400*21)),
                            "valid_finder_id"=>intval($booktrial['finder_id']),
                            "finder_id"=>intval($booktrial['finder_id']),
                        );

                    if($booktrial->type == 'workout-session'){
                        unset($req['valid_finder_id']);
                        unset($req['finder_id']);
                        $req['description'] = "Added FitCash+ on Session Attendance at ".ucwords($booktrial['finder_name'])." Expires On : ".date('d-m-Y',time()+(86400*21));
                    }

                    Log::info("adding fitachs");
                    $this->utilities->walletTransaction($req);
                }

            }else{
                Log::info("not adding fitachs");
            }
            
            $this->utilities->updateOrderStatus($booktrial);
            
            $booktrial->post_trial_initail_status = 'interested';
            $booktrial->post_trial_status_updated_by_lostfitcode = time();
            $booktrial->post_trial_status_date = time();
            
            $message = 'Hi, '.ucwords($booktrial['customer_name']).'! Thanks for your update.';
            
            if(!isset($booktrial['extended_validity_order_id']) && !empty($fitcash_amount)){
                $message = 'Hi, '.ucwords($booktrial['customer_name']).'! Thanks for your update. Rs. '.$fitcash_amount.' will be added into your Fitternity wallet within 48 hours';
            }
            
            if($reason_message){
                $message = $reason_message;
            }
            $finderName = ucwords($booktrial['finder_name']);
            if(!empty($_GET['thirdparty'])){
                $message = ucwords($booktrial['customer_name']).", Lost Fitcode to mark your active day for Multiply free session booking at "
                .$finderName."? Don't worry. We'll verify your session attendance with ".$finderName
                ." and process your active day shortly. For quick assistance call Fitternity - 02261094444. "
                ."Team Aditya Birla Wellness.";
            }

            $booktrial->update();

            $response = [
                'status' => 200,
                'message' => $message,
                'booktrial_id'=> (int)$booktrial['_id'],
            ];
        }

        return Response::json($response,200);

    }

    public function sessionStatusCapture($status, $booktrial_id,$qrcode=false){
        
        $booktrial = Booktrial::find(intval($booktrial_id));

        if(!$booktrial){
            return Response::json(array('status'=>400, 'message'=>'Workout Session not found'), 200);
        }

        $payment_done = !(isset($booktrial->payment_done) && !$booktrial->payment_done);

        $pending_payment = [
        		'header'=>"Pending Amount ".$this->utilities->getRupeeForm($booktrial['amount_finder']),
	            'sub_header'=>"Make sure you pay up, to earn Cashback & continue booking more sessions",
                'order_id'=>$booktrial['order_id'],
	            'trial_id'=>$booktrial['_id']
        ];

        if(!empty($booktrial['order_id']))
        	$pending_payment['order_id']=$booktrial['order_id'];
        	 
        // $streak = array_column(Config::get('app.streak_data'), 'number');

        switch($status){

            case 'activate':
                Log::info($_SERVER['REQUEST_URI']);

                if(empty($_GET['vendor_code']) && strpos($_GET['source'], 'let_us_know?vendor_code=')>=0){
                    $vendorCode = explode('vendor_code=', $_GET['source']);
                    if(!empty($vendorCode[1])){
                        $_GET['vendor_code'] = $vendorCode[1];
                        $_GET['source'] = 'let_us_know';
                    }
                }

                if(!isset($_GET['vendor_code'])){
                    return Response::json(array('status'=>400, 'message'=>'Fitcode not attached'), 200);
                }
                $vendor_code = $_GET['vendor_code'];
                $verify_fitcode_result = json_decode(json_encode($this->verifyFitCode($booktrial_id, $vendor_code)->getData()));
                
                if($verify_fitcode_result->status==400){
                	return Response::json(array('status'=>400, 'message'=>$verify_fitcode_result->message), 200);
                }

                $response = [
                    'status'=>200,
                    'header'=>'ENJOY YOUR WORKOUT!',
                    'image'=>'https://b.fitn.in/paypersession/happy_face_icon-2.png',
                    // 'footer'=>$customer_level_data['current_level']['cashback'].'% Cashback has been added in your Fitternity Wallet. Use it to book more workouts and keep on earning!',
                    // 'streak'=>[
                    //     'header'=>'STREAK IT OUT',
                    //     'data'=>$this->utilities->getStreakImages($customer_level_data['current_level']['level'])
                    // ]
                ];

                $voucher_response = $this->utilities->attachExternalVoucher($booktrial);

                if($voucher_response){
                    $response['voucher_data'] = $voucher_response;
                }

                // if(!$customer_level_data['maxed_out']){
                //     $response['streak']['footer'] = 'You have unlocked level '.$customer_level_data['current_level']['level'].' which gets you '.$customer_level_data['current_level']['cashback'].'% cashback upto '.$customer_level_data['current_level']['number'].' sessions!';
                // }

                // if(isset($customer_level_data['next_level']) && isset($customer_level_data['next_level']['cashback'])){
                //     $response['streak']['footer'] = 'You have unlocked level '.$customer_level_data['current_level']['level'].' which gets you '.$customer_level_data['current_level']['cashback'].'% cashback upto '.$customer_level_data['current_level']['number'].' sessions! Make sure to continue as next level gets you '.$customer_level_data['next_level']['cashback'].'%.Higher the Level, Higher the Cashback';
                // }
                $customer_level_data = $this->utilities->getWorkoutSessionLevel($booktrial['customer_id']);                

                Log::info('customer_level_data');
                Log::info($customer_level_data);
                
                if($verify_fitcode_result->fitcash > 0 || empty($booktrial['pass_order_id'])){

                    if($payment_done){
                        $response['sub_header_1'] = $customer_level_data['current_level']['cashback']."% Cashback";
                        $response['sub_header_2'] = " has been added in your Fitternity Wallet. Use it to book more workouts and keep on earning!";
                    }else{
                        $response['payment'] = $pending_payment;
                    }

                    if($booktrial['type'] == 'booktrials'){
                        $response['sub_header_1'] = $verify_fitcode_result->fitcash." Fitcash";
                        $response['sub_header_2'] = " has been added in your Fitternity Wallet. Use it to buy membership with lowest price";
                    }
                }

                if(isset($booktrial['corporate_id']) && $booktrial['corporate_id'] != ''){

                    if($payment_done){
                        unset($response['sub_header_1']);
                        $response['sub_header_2'] = "300 Steps will be added into your steps counter post your workout.";

                        $response['sub_header_2'] = $response['sub_header_2']."\n\n".$customer_level_data['current_level']['cashback']."% cashback is added into your Fitternity wallet. Use it to book more workouts to reach your steps goal faster";

                        if(isset($booktrial['servicecategory_id']) && $booktrial['servicecategory_id'] != ''){
                            $service_cat_steps_map = Config::get('health_config.service_cat_steps_map');
                            if(in_array($booktrial['servicecategory_id'], array_keys($service_cat_steps_map))){
                                $service_steps = $service_cat_steps_map[$booktrial['servicecategory_id']];
                                $response['sub_header_2'] = $service_steps." Steps will be added into your steps counter post your workout.";

                                $response['sub_header_2'] = $response['sub_header_2']."\n\n".$customer_level_data['current_level']['cashback']."% cashback is added into your Fitternity wallet. Use it to book more workouts to reach your steps goal faster";
                            }
                        }
                    }else{
                        $response['payment'] = $pending_payment;
                    }
                    
                }

                if(isset($_GET['source']) && $_GET['source'] == 'let_us_know'){
                    $response['header'] = 'GREAT';
                }
                Log::info("removing n+2 communication");
                $this->utilities->deleteSelectCommunication(['transaction'=>$booktrial, 'labels'=>["customer_sms_after2hour","customer_email_after2hour","customer_notification_after2hour"]]);

            break;
            case 'lost':
                $result = json_decode(json_encode($this->lostFitCode($booktrial_id)->getData()));

                if($result->status==400){
                    return Response::json(array('status'=>500, 'message'=>'Something went wrong'), 200);
                }

                $customer_level_data = $this->utilities->getWorkoutSessionLevel($booktrial['customer_id']);                
                
                $response = [
                    'status'=>200,
                    'header'=>'DON’T WORRY',
                    'image'=>'https://b.fitn.in/paypersession/cashback.png',
                    'sub_header_1'=>$customer_level_data['current_level']['cashback'].'% Cashback',
                    'sub_header_2'=>' will be added to your wallet after we verify your attendace from the gym / studio',
                    // 'streak'=>[
                    //     'header'=>'STREAK IT OUT',
                    //     'data'=>$this->utilities->getStreakImages($customer_level_data['current_level']['level'])
                    // ]
                ];

                $voucher_response = $this->utilities->attachExternalVoucher($booktrial);

                if($voucher_response){
                    $response['voucher_data'] = $voucher_response;
                }

                if(isset($_GET['source']) && $_GET['source'] == 'let_us_know'){
                    $response['header'] = 'GREAT';
                }
                
                if(!$payment_done){
                    $response['payment'] = $pending_payment;
                }

                if($booktrial['type'] == 'booktrials'){
                    unset($response['sub_header_1']);
                    $response['sub_header_2'] = " Fitcash is added in your wallet. Use it to buy ".ucwords($booktrial['finder_name'])."'s membership at lowest price.";;
                }

                if(isset($booktrial['extended_validity_order_id'])){
                    $response['image'] = 'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png';
                }

                if(isset($booktrial['corporate_id']) && $booktrial['corporate_id'] != ''){
                    $response['sub_header_1'] = "300 Steps";
                        
                    if(isset($booktrial['servicecategory_id']) && $booktrial['servicecategory_id'] != ''){
                        $service_cat_steps_map = Config::get('health_config.service_cat_steps_map');
                        if(in_array($booktrial['servicecategory_id'], array_keys($service_cat_steps_map))){
                            $service_steps = $service_cat_steps_map[$booktrial['servicecategory_id']]; 
                            $response['sub_header_1'] = $service_steps." Steps";       
                        }
                    }

                    $response['sub_header_2'] = "will be added into your steps counter post verifying your attendance from gym/studio.";    
                }

                Log::info("removing n+2 communication");
                $this->utilities->deleteSelectCommunication(['transaction'=>$booktrial, 'labels'=>["customer_sms_after2hour","customer_email_after2hour","customer_notification_after2hour"]]);

            break;
            case 'didnotattend':
                $booktrial->post_trial_status = 'no show';
                $booktrial->post_trial_status_date = time();
                $booktrial->update();
                
                $customer_level_data = $this->utilities->getWorkoutSessionLevel($booktrial['customer_id']);     
                
                $response = [
                    'status'=>200,
                    'header'=>'OOPS!',
                    'image'=>'https://b.fitn.in/paypersession/sad-face-icon.png',
                    'sub_header_2'=>'Sorry, cancellation is available only 60 minutes prior to your session time.',
                    'footer'=>'Unlock level '.$customer_level_data['current_level']['level'].' which gets you '.$customer_level_data['current_level']['cashback'].'% cashback upto '.$customer_level_data['current_level']['number'].' sessions! Higher the Level, Higher the Cashback',
                    // 'streak'=>[
                    //     'header'=>'STREAK IT OUT',
                    //     'data'=>$this->utilities->getStreakImages($customer_level_data['current_level']['level'])
                    // ]
                ];

                // if(isset($customer_level_data['next_level']['level'])){
                //     $response['streak']['footer'] = 'Unlock level '.$customer_level_data['next_level']['level'].' which gets you '.$customer_level_data['next_level']['cashback'].'% cashback upto '.$customer_level_data['next_level']['number'].' sessions! Higher the Level, Higher the Cashback';
                // }
                // if($payment_done){
                //     $response['sub_header_2'] = "Make sure you attend next time to earn Cashback and continue working out!";

                //     if(!empty($booktrial->amount)){
                //         $response['sub_header_2'] = $response['sub_header_2']."\n\nWe will transfer your paid amount in form of Fitcash within 24 hours.";
                //     }
                // }
                if($booktrial->type=='booktrials'){
                    
                    $response['reschedule_button'] = true;
                    $response['sub_header_2'] = "We'll cancel you from this batch. Do you want to reschedule instead?";

                }

                if(!empty($booktrial->pass_order_id)){
                    unset($response['footer']);
                }


                if(isset($booktrial['corporate_id']) && $booktrial['corporate_id'] != ''){
                    $response['sub_header_2'] = "Sorry, cancellation is available only 60 minutes prior to your session time.\n\nKeep booking workouts to get closer to your steps milestone.";
                }
                Log::info("removing n+2 communication");
                $this->utilities->deleteSelectCommunication(['transaction'=>$booktrial, 'labels'=>["customer_sms_after2hour","customer_email_after2hour","customer_notification_after2hour"]]);

            break;
            case 'cantmake':

                $customer_level_data = $this->utilities->getWorkoutSessionLevel($booktrial['customer_id']);     
                
                $response = [
                    'status'=>200,
                    'header'=>'OOPS!',
                    'image'=>'https://b.fitn.in/paypersession/sad-face-icon.png',
                    'sub_header_2'=>'Make sure you attend next time to earn Cashback and continue working out!',
                    'footer'=>'Unlock level '.$customer_level_data['current_level']['level'].' which gets you '.$customer_level_data['current_level']['cashback'].'% cashback upto '.$customer_level_data['current_level']['number'].' sessions! Higher the Level, Higher the Cashback',
                    // 'streak'=>[
                    //     'header'=>'STREAK IT OUT',
                    //     'data'=>$this->utilities->getStreakImages($customer_level_data['current_level']['level'])
                    // ]
                ];

                // if(isset($customer_level_data['next_level']['level'])){
                //     $response['streak']['footer'] = 'Unlock level '.$customer_level_data['next_level']['level'].' which gets you '.$customer_level_data['next_level']['cashback'].'% cashback upto '.$customer_level_data['next_level']['number'].' sessions! Higher the Level, Higher the Cashback';
                // }
                if($payment_done){
                    $response['sub_header_2'] = "Make sure you attend next time to earn Cashback and continue working out!";

                    if(!empty($booktrial->amount)){
                        $response['sub_header_2'] = $response['sub_header_2']."\n\nWe will transfer your paid amount in form of Fitcash within 24 hours.";
                    }
                }
                
                if($booktrial->type=='booktrials'){
                    $response['reschedule_button'] = true;
                    $response['sub_header_2'] = "We'll cancel you from this batch. Do you want to reschedule instead?";
                }

                if(!empty($booktrial->pass_order_id)){
                    unset($response['footer']);
                }


                if(isset($booktrial['corporate_id']) && $booktrial['corporate_id'] != ''){
                    if($payment_done){
                        $response['sub_header_2'] = "Make sure you attend next time to earn steps into your steps counter to achieve your goal faster.";
    
                        if(!empty($booktrial->amount)){
                            $response['sub_header_2'] = $response['sub_header_2']."\n\nWe will transfer your paid amount in form of Fitcash within 24 hours.";
                        }
                    }  
                }
                $this->cancel($booktrial->_id);
            break;
            case 'confirm':
                $booktrial->pre_trial_status = 'confirm';
                $booktrial->post_trial_status_date = time();
                $booktrial->update();
                $customer_level_data = $this->utilities->getWorkoutSessionLevel($booktrial['customer_id']);                
                
                $response = [
                    'status'=>200,
                    'header'=>'We’ve got you covered!',
                    'image1'=>'http://b.fitn.in/paypersession/Location-icon-mdpi.png',
                    'image2'=>'http://b.fitn.in/paypersession/bag-icon-mdpi.png',
                    'image'=>'http://b.fitn.in/paypersession/bag-icon-mdpi.png',
                    'image3'=>'http://b.fitn.in/paypersession/money-icon.png',
                    'activate'=>[
                        'sub_header_1'=>'ACTIVATE YOUR SESSION',
                        'sub_header_2'=>'Show your subscription code once you reach and get your FitCode to activate your session',
                    ],
                    'attend'=>[
                        'sub_header_1'=>'ATTEND & EARN',
                        'sub_header_2'=>'Attend this session and earn '.$customer_level_data['next_session']['cashback'].'% Cashback',
                    ],
                    'checklist'=>[
                        'sub_header_1'=>'YOUR WORKOUT CHECKLIST IS READY!',
                        'sub_header_2'=>'What to carry, what to expect, directions, booking details and all that you need to know about your session can be found here.',
                    ],
                ];
                
                if($booktrial->type == 'booktrials'){
                    $response['attend']['sub_header_2'] = 'Attend this session and earn Surprise Cashback';
                }

				if(isTabActive($booktrial['finder_id'])){
                    $response['activate']['sub_header_2'] = 'Punch your subscription code on the kiosk/tab available at the center to activate your session';
                }
                if(isset($booktrial['studio_extended_validity_order_id'])){
                    unset($response['activate']);
                    unset($response['attend']['sub_header_1']);
                    $response['sub_header_2']='Enjoy your session at '.$booktrial['finder_name'].'. Your workout checklist is ready';
                    if($this->device_type=='android')
                        $response['attend']['sub_header_2']='Enjoy your session at '.$booktrial['finder_name'].'. Your workout checklist is ready';
                    
                }


                if(isset($booktrial['pass_order_id'])){
                    unset($response['attend']);
                }

                if(isset($booktrial['corporate_id']) && $booktrial['corporate_id'] != ''){
                    $response['attend']['sub_header_2'] = 'Attend this sessionand earn 300 Steps.';
                    
                    if(isset($booktrial['servicecategory_id']) && $booktrial['servicecategory_id'] != ''){
                        $service_cat_steps_map = Config::get('health_config.service_cat_steps_map');
                        if(in_array($booktrial['servicecategory_id'], array_keys($service_cat_steps_map))){
                            $service_steps = $service_cat_steps_map[$booktrial['servicecategory_id']];
                            $response['attend']['sub_header_2'] = 'Attend this session and earn '.$service_steps.' Steps.';
                        }
                    }
                }
            break;
            case 'cancel':

                $customer_level_data = $this->utilities->getWorkoutSessionLevel($booktrial['customer_id']);     
                
                $response = [
                    'status'=>200,
                    'header'=>'Cancelled',
                    'image'=>'https://b.fitn.in/paypersession/sad-face-icon.png',
                    'sub_header_2'=>'Make sure you attend next time to earn Cashback and continue working out!',
                    'footer'=>'Unlock level '.$customer_level_data['current_level']['level'].' which gets you '.$customer_level_data['current_level']['cashback'].'% cashback upto '.$customer_level_data['current_level']['number'].' sessions! Higher the Level, Higher the Cashback',
                ];
                if($payment_done){
                    $response['sub_header_2'] = "Make sure you attend next time to earn Cashback and continue working out!";

                }
                if(isset($booktrial['studio_extended_validity_order_id'])){
                    // $orderDetails = Order::findOrFail($booktrial['studio_extended_validity_order_id']);
                    Order::$withoutAppends = true;
                    $orderDetails = Order::where('_id', $booktrial['studio_extended_validity_order_id'])->first([]);
                    if(!empty($orderDetails['studio_sessions'])){
                        $avail = $orderDetails['studio_sessions']['total_cancel_allowed'] - $orderDetails['studio_sessions']['cancelled'];
                        $avail = ($avail<=0)?0:$avail-1;
                        $scheduleDates = $this->utilities->getExtendedSessionDate($orderDetails);
                        if(!empty($scheduleDates[0]['schedule_slot'])){
                            $response['sub_header_2'] = "Your session has been cancelled.\nYour next session is booked for ".$scheduleDates[0]['schedule_date'].", ".$scheduleDates[0]['schedule_slot']."\nYou have ".$avail."/".$orderDetails['studio_sessions']['total_cancel_allowed']." cancellations available up to ".date('d-m-Y', $orderDetails['studio_membership_duration']['end_date_extended']->sec);
                        }
                        else {
                            $response['sub_header_2'] = 'Your session has been cancelled. You have '.$avail.'/'.$orderDetails['studio_sessions']['total_cancel_allowed'].' cancellations available up to '.date('d-m-Y', $orderDetails['studio_membership_duration']['end_date_extended']->sec);
                        }
                    }
                    $orderDetails = $orderDetails;
                    // $response['sub_header_2'] ='We have cancelled you out from this batch but we have got you covered. This gets you an exclusive chance to attend this missed session later in other batches. You can extend maximum '.$orderDetails['studio_sessions']['total_cancel_allowed'].' sessions within '.$orderDetails['studio_membership_duration']['num_of_days_extended'].' days of extension.'; 
                    
                    Log::info('order details at cancel studio workout session', [$orderDetails['studio_sessions']['total_cancel_allowed']]);
                    if(isset($orderDetails) && $orderDetails["studio_sessions"]['cancelled'] < $orderDetails["studio_sessions"]['total_cancel_allowed'] ){
                        $this->cancel($booktrial->_id);
                        //$this->cancel($bookTrialId, 'customer', 'Not Available', false);
                    }
                    else{
                        Log::info('not cancelled');
                        $booktrial->update(['partial_cancel'=>true,'partial_cancel_by'=>'customer']);
                        //$booktrial->save();
                        $response['sub_header_2']  = 'Sorry, you have already availed '.$orderDetails['studio_sessions']['total_cancel_allowed'].' sessions extension of your membership.';  
                        $response['header']='Not Cancelled';
                    }
                }

                if(isset($booktrial['corporate_id']) && $booktrial['corporate_id'] != ''){
                    $response['sub_header_2'] = "Make sure you attend next time to earn steps into your steps counter to achieve your goal faster.";
                }
                
            break;
                


        }
        
        // if($booktrial->type == 'booktrials' && isset($response['streak'])){
        //     unset($response['streak']);
        // }

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
        $response['milestones'] = $this->utilities->getMilestoneSection();
        
        // $loyalty_registration = $this->utilities->autoRegisterCustomerLoyalty($booktrial);
        
        // if(!empty($loyalty_registration)){
        //     $booktrial_update = Booktrial::where('_id', $booktrial['_id'])->update(['loyalty_registration'=>true]);
        //     $response['fitsquad'] = $this->utilities->getLoyaltyRegHeader();
        // }

        if(isset($booktrial['extended_validity_order_id']) || !empty($booktrial['pass_order_id'])){
            $response['description'] = '';
            $response['sub_header_1'] = '';
            $response['sub_header_2'] = '';
        }

        return Response::json($response);

    }

    public function scheduleManualCommunication($booktrial_id){

        // $this->sendCommunication(null, ['booktrial_id'=>$booktrial_id]);

        // return "done";

        
        $booktrial = Booktrial::find(intval($booktrial_id));
        $dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date','followup_date','auto_followup_date');
        
        foreach ($dates as $key => $value) {
            if(isset($booktrial[$value])){
                if($booktrial[$value] == "-" || $booktrial[$value] == ""){

                    $booktrial->unset($value);
                }
            }
        }
        
        $booktrialdata = $booktrial->toArray();
        
        $this->customernotification->bookTrialReminderBefore3Hour($booktrialdata, '2018-05-10');

        $this->customernotification->bookTrialReminderBefore10Min($booktrialdata, '2018-05-10');
        
        $this->customernotification->bookTrialReminderAfter2Hour($booktrialdata, '2018-05-10');

        return "done";
        
    }

    public function unsetEmptyDates($booktrial){
        $unset_keys = [];
        $dates = array('schedule_date','schedule_date_time','missedcall_date','customofferorder_expiry_date','followup_date','auto_followup_date');
        foreach ($dates as $key => $value) {
            if(isset($booktrial[$value])){
                if($booktrial[$value] == "-" || $booktrial[$value] == ""){
                    $unset_keys[] = $value;
                }
            }
        }
        if(!empty($unset_keys)){
            $booktrial->unset($unset_keys);
        }
    }

    public function updateOrderStatus($booktrial){
        if(isset($booktrial->pay_later) && $booktrial->pay_later && isset($booktrial->payment_done) && !$booktrial->payment_done){
            Order::where('_id', $booktrial->order_id)->where('status', '0')->update(['status'=>'4']);
        }
    }

    public function manualTrialCommunication(){
        // $booktrial_ids = [116516,116520,116525,116526,116527,116538,116544,116545,116546];
        // $booktrial_ids  = [116491];

        foreach($booktrial_ids as $booktrial_id){
            $this->sendCommunication(null, ['booktrial_id'=>$booktrial_id]);
        }

        return "done";
    }

    public function publishConfirmationAlert($booktrial_data){
        
        return;
        Log::info("publishing trial alert");
        $pubnub = new \Pubnub\Pubnub(Config::get('app.pubnub_publish'), Config::get('app.pubnub_sub'));
        $booktrial_data = array_only($booktrial_data, ['_id', 'finder_name', 'schedule_date_time','finder_location','customer_name', 'city_id']);
        $booktrial_data['schedule_date_time'] = date('d-m-Y g:i A',strtotime( $booktrial_data['schedule_date_time']));
        $booktrial_data['type'] = 1;
        
        $cities 	=	City::active()->orderBy('name')->lists('name', '_id');
        
        $booktrial_data['city_name'] = $cities[$booktrial_data['city_id']];
        $booktrial_data['trial_id'] = $booktrial_data['_id'];
        unset($booktrial_data['_id']);
        
        Trialalert::create($booktrial_data);
        $pubnub->publish('fitternity_trial_alert',$booktrial_data);
        
    }

    function isWeekend($timestamp){
        Log::info("hour");
        Log::info(date('H',$timestamp)); 
        if(in_array(date('l', $timestamp), Config::get('app.trial_comm.full_day_weekend')) ||  (in_array(date('l', $timestamp), Config::get('app.trial_comm.begin_weekend')) && intval(date('H',$timestamp)) >= Config::get('app.trial_comm.off_hours_begin_time')) ||  (in_array(date('l', $timestamp), Config::get('app.trial_comm.end_weekend')) && intval(date('H',$timestamp)) < Config::get('app.trial_comm.off_hours_end_time')) ){
            
            return true;
        
        }
        return false;
    }

    function isOffHour($hour){
        
        if( $hour >= Config::get('app.trial_comm.off_hours_begin_time') || $hour < Config::get('app.trial_comm.off_hours_end_time')){
            
            return true;
        
        }
        return false;
    }

    public function updatetrialstatus($_id, $source, $action, $confirm=false){

        if($confirm){
            if($action == 'confirm'){
                $booktrial = Booktrial::find(intval($_id));
                $this->unsetEmptyDates($booktrial);
                $booktrial->pre_trial_vendor_confirmation = 'confirmed';
                $booktrial->update();
                return "Trial Confirmed Successfully";

            }else if($action == 'cancel'){
                $query = Input::all();
                return $this->cancel($_id, $source, '', $query['isBackendReq']);
            }
        }else{
            $booktrial = Booktrial::find(intval($_id));
            $this->unsetEmptyDates($booktrial);
            $booktrial_data = $booktrial->toArray();
            $query = Input::all();
            $isBackendReq = (isset($query['isBackendReq']))?$query['isBackendReq']:false;
            $action_link = Config::get('app.url').'/updatetrialstatus/'.$_id.'/'.$source.'/'.$action.'/1?isBackendReq='.$isBackendReq;
            $cities 	=	City::orderBy('name')->lists('name', '_id');
            
            return View::make('trialconfirm', compact('booktrial_data', 'action_link', 'action', 'cities'));
        }


    }

    public function refundSessionAmount($booktrialdata){
        $paid = 0;
        if($booktrialdata['type'] == 'workout-session'){
            Log::info('workout-session');
            $order_id = $booktrialdata['order_id'];
            $order = Order::where('_id',$order_id)->first();
            
            if($order){
                if($order->status=='1'){

                    Log::info('order');
                    
                    $order->update(['status' => '-1']);
    
                    if($order['amount'] >= 15000){
                        $order_data = $order->toArray();
                        $order_data['finder_vcc_email'] = "vinichellani@fitternity.com";
                        $this->findermailer->orderFailureNotificationToLmd($order_data);
                    }
    
                    $customer_id =  $order['customer_id'];
    
                    $refund = $paid = $order['amount'] + (isset($order['wallet_amount']) ? $order['wallet_amount'] : 0);
                    Log::info($booktrialdata->source_flag);
                    if($booktrialdata->source_flag == 'vendor'){
                        Log::info("20");
                        $refund = round($refund*1.2);
                    }
                    log::info($refund);
                    $req = array(
                        'customer_id'=>$customer_id,
                        'order_id'=>$order_id,
                        'amount'=>$refund,
                        "type"=>'CREDIT',
                        'entry'=>'credit',
                        'description'=>'Refund for Session ID: '.$booktrialdata['code'],
                    );
    
                    $walletTransactionResponse = $this->utilities->walletTransaction($req,$order->toArray());
                    Log::info($walletTransactionResponse);
    
                    if($walletTransactionResponse['status'] != 200){
                        return $paid = 0;
                    }
                }else{
                    $order->update(['redundant_order' => '1']);
                }


            }
            
        }

        return $paid;

    }

    public function isOfficeHour($timestamp){
        if(!in_array(date('l', $timestamp), ['Sunday', 'Saturday']) && date('H',$timestamp) >= 11 && date('H',$timestamp) < 20 ){
            return true;
        }
        return false;
    }

    public function skipreview($booktrial_id){
        Booktrial::where('_id', intval($booktrial_id))->update(['skip_review'=>true]);
        return ['status'=>200];
    }   
    
    public function addCustomersForTrial(){
        
        $req = Input::json()->all();

        // if($this->device_type){
        //     $req['source'] = $this->device_type;
        // }
        
        $rules = [
            'booktrial_id' => 'required|integer|numeric',
            'invitees' => 'required|array',
            // 'source' => 'in:android,ios,website',
        ];
        $validator = Validator::make($req, $rules);

        if($validator->fails()) {
            $resp = array('status' => 400,'message' =>$this->errorMessage($validator->errors()));
            return  Response::json($resp, 400);
        }

        Log::info('inviteForTrial',$req);

        $inviteesData = [];

        foreach ($req['invitees'] as $value){

            
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
            
            $inviteeData = ['name'=>$value['name']];

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
        // return $inviteesData;

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
        $booktrial = Booktrial::where('_id', $req['booktrial_id'])
            ->with('invite')
            ->get(array(
                'customer_id', 'customer_name', 'customer_email','customer_phone','service_name',
                'type', 'finder_name', 'finder_location','finder_address',
                'schedule_slot_start_time','schedule_date','schedule_date_time','type','root_booktrial_id'
            ))
            ->first();

        if(!$booktrial){

            return Response::json(
                array(
                    'status' => 422,
                    'message' => "Invalid trial id"
                ),422
            );
        }
        return $booktrial = $booktrial->toArray();

        

        $emails = array_fetch($inviteesData, 'email');
        $phones = array_fetch($inviteesData, 'phone');


        if(array_where($emails, function ($key, $value) use($booktrial)  {
            if($value == $booktrial['customer_email']){
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

        if(array_where($phones, function ($key, $value) use($booktrial)  {
            if($value == $booktrial['customer_phone']){
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
            
            isset($templateData['invitee_email']) ? $this->customermailer->inviteEmail($booktrial['type'], $templateData) : null;
            isset($templateData['invitee_phone']) ? $this->customersms->inviteSMS($booktrial['type'], $templateData) : null;
        }

        return Response::json(
            array(
                'status' => 200,
                'message' => 'Invitation has been sent successfully',
                'fitcash_added' => $cashback_amount,
                'wallet_balance' => $customer_balance
            ),200
        );
    }

    public function updateCorporateCoupons($booktrial){

        if(!empty($booktrial->coupon_code) && !empty($booktrial->coupon_discount_amount) && in_array(strtolower($booktrial->coupon_code), Config::get('app.corporate_coupons'))){
            Log::info("Updating corporate coupons");
            $coupon_update = Coupon::where('code', strtolower($booktrial->coupon_code))->decrement('total_used');
        }
    }

    /**
     * @param $id
     * @param $isBackendReq
     * @param $booktrial
     * @param $queueBookTrial
     * @param $resp
     */
    public function addExtendedSession($id, $isBackendReq, &$booktrial, &$queueBookTrial, &$resp)
    {
        Log::info('before redis call');

        $queueBookTrial = array('id' => $id);

        if (!empty($booktrial['studio_extended_validity_order_id'])) {

            $order = Order::find($booktrial['studio_extended_validity_order_id']);

            Log::info('order details', [$order['studio_sessions']['cancelled'] + 1]);

            $m = $order['studio_sessions'];
            $m['cancelled'] = $order['studio_sessions']['cancelled'] + 1;
            $order['studio_sessions'] = $m;
            $order->update();
            $scheduleDates = $this->utilities->getExtendedSessionDate($order);
            $booktrial->update(['studio_next_extended_session' => [
                'schedule_date' => $scheduleDates[0]['schedule_date'],
                'schedule_slot' => $scheduleDates[0]['schedule_slot']
            ]]);
            $queueBookTrial = array('id' => $id, 'order_id' => $order['_id']);
        }

        //for studio membership as workout session
        Log::info('before updating order');
        Log::info('after updating order');
        $message = "Trial Canceled";
        if (isset($booktrial['studio_extended_order_id'])) {
            $message = "We have cancelled you out from this batch but we have got you covered. This gets you an exclusive chance to attend this missed session later in other batches. You can extend maximum <x> sessions within <y> days of extension.";
        }
        $resp = array('status' => 200, 'message' => $message);
        if (!empty($booktrial['third_party_details'])) {
            $cust = Customer::find($booktrial['customer_id']);
            $thirdPartyAcronym = !empty($cust['third_party_acronym']) ? $cust['third_party_acronym'] : 'abg';
            Log::info('cust:::: ', [$cust]);
            if ($cust['total_sessions_used'] > 0) {
                $cust['total_sessions_used'] -= 1;
            }
            $_temp = $cust['third_party_details'];
            if ($cust['third_party_details'][$thirdPartyAcronym]['third_party_used_sessions'] > 0) {
                $_temp[$thirdPartyAcronym]['third_party_used_sessions'] -= 1;
                $cust['third_party_details'] = $_temp;
            }
            $cust->update();
            $resp['booktrial_id'] = $booktrial['_id'];
            if ($isBackendReq) {
                Log::info("it is a backend request");
                $metropolis = new Metropolis();
                $metropolis->cancelThirdPartySession($cust['third_party_details'][$thirdPartyAcronym]['third_party_token_id'], $booktrial['_id'], $resp['message']);
            }
        }
    }


    public function unlockSession($booktrial_id){
        Log::info($_SERVER['REQUEST_URI']);
        $booktrial_id = (int) $booktrial_id;

        $response = array('status' => 400,'message' =>'Sorry! Cannot locate your booking');

        $jwt_token = Request::header('Authorization');
        $decoded = customerTokenDecode($jwt_token);

        $customer_id = (int)$decoded->customer->_id;
        
        $booktrial = Booktrial::where('_id',$booktrial_id)
           ->where('customer_id',$customer_id)
           ->whereIn('type',['booktrials','3daystrial','workout-session'])
           // ->where('schedule_date_time','>',new MongoDate(strtotime(date('Y-m-d 00:00:00'))))
           // ->where('schedule_date_time','<',new MongoDate(strtotime(date('Y-m-d 23:59:59'))))
           // ->orderBy('_id','desc')
           ->first();

        if(isset($booktrial)){

            $booktrial->post_trial_status = 'attended';
            $booktrial->post_trial_initail_status = 'interested';
            $booktrial->post_trial_status_updated_by_unlocksession = time();
            $booktrial->post_trial_status_date = time();

            $booktrial->update();

            $data = $booktrial;
				
			$data_new['header'] = "Session Activated";
							
			$data_new['workout'] = array(
                'image' => '',
				'name' => ucwords($data['customer_name']),
				'icon' => '',
				'header' => ucwords($data['service_name']),
				'datetime' => date('D, d M - h:i A', strtotime($data['schedule_date_time']))
			);
							
			$data_new['finder'] = array(
				'title' => $data['finder_name'],
				'location' => $data['finder_location'],
				'address'=> $data['finder_address'],
				'direction_text' => "Get Direction",
				'lat' => $data['finder_lat'],
				'lon' => $data['finder_lon']
			);

			
            $data_new['footer'] = array(
				'footer1' => 'You can only unlock this session within 200 meters of the gym',
				'footer2' => array(
					'contact_text' => 'Need Help? Contact your Personal Concierge',
					'contact_image' => 'https://b.fitn.in/passes/app-home/contact-us.png',
					'contact_no' => '',
				),
				'footer3' => array(
					'unlock_button_text' => 'Session ID:',
				),
			);
							
			$data_new = array_only($data_new, ['icon','title', 'time_diff', 'time_diff_text', 'schedule_date_time', 'current_time', 'schedule_date_time_text', 'payment_done', 'order_id', 'trial_id', 'header', 'workout', 'finder', 'footer']);
            
            $response = [
                'status' => 200,
                'data' => $data_new,
                'booktrial_id'=> (int)$booktrial['_id'],
            ];
        }

        return Response::json($response,200);
    }

}