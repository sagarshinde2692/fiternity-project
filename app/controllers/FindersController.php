<?PHP

/**
 * ControllerName : FindersController.
 * Maintains a list of functions used for FindersController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Mailers\FinderMailer as FinderMailer;
use App\Services\Cacheapi as Cacheapi;
use App\Services\Cron as Cron;


class FindersController extends \BaseController {


    protected $facetssize 					=	10000;
    protected $limit 						= 	10000;
    protected $elasticsearch_host           =   "";
    protected $elasticsearch_port           =   "";
    protected $elasticsearch_default_index  =   "";
    protected $elasticsearch_url            =   "";
    protected $elasticsearch_default_url    =   "";

    protected $findermailer;
    protected $cacheapi;

    public function __construct(FinderMailer $findermailer, Cacheapi $cacheapi) {

        parent::__construct();
        $this->elasticsearch_default_url 		=	"http://".Config::get('app.es.host').":".Config::get('app.es.port').'/'.Config::get('app.es.default_index').'/';
        $this->elasticsearch_url 				=	"http://".Config::get('app.es.host').":".Config::get('app.es.port').'/';
        $this->elasticsearch_host 				=	Config::get('app.es.host');
        $this->elasticsearch_port 				=	Config::get('app.es.port');
        $this->elasticsearch_default_index 		=	Config::get('app.es.default_index');
        $this->findermailer						=	$findermailer;
        $this->cacheapi						=	$cacheapi;
    }



    public function acceptVendorMou($mouid){


        $vendormou = Vendormou::with(array('finder'=>function($query){$query->select('_id','title','slug');}))->find(intval($mouid));

        if($vendormou){

            $vendormouData =    $vendormou->toArray();

            return $this->findermailer->acceptVendorMou($vendormouData);

        }


    }


     public function cancelVendorMou($mouid){


        $vendormou = Vendormou::with(array('finder'=>function($query){$query->select('_id','title','slug');}))->find(intval($mouid));

        if($vendormou){

            $vendormouData =    $vendormou->toArray();

            return $this->findermailer->cancelVendorMou($vendormouData);

        }


    }



    public function finderdetail($slug, $cache = true){

        $data 	=  array();
        $tslug 	= (string) strtolower($slug);

        $finder_detail = $cache ? Cache::tags('finder_detail')->has($tslug) : false;

        if(!$finder_detail){

            $finderarr = Finder::active()->where('slug','=',$tslug)
            ->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
            ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->with('findercollections')
            ->with('blogs')
            ->with('categorytags')
            ->with('locationtags')
            ->with('offerings')
            ->with('facilities')
            ->with(array('ozonetelno'=>function($query){$query->select('*')->where('status','=','1');}))
            ->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
            ->with(array('reviews'=>function($query){$query->select('*')->where('status','=','1')->orderBy('_id', 'DESC');}))
            ->first();


            if($finderarr){
                // $ratecards 			= 	Ratecard::with('serviceoffers')->where('finder_id', intval($finder_id))->orderBy('_id', 'desc')->get();
                $finderarr = $finderarr->toArray();

                // return  pluck( $finderarr['categorytags'] , array('name', '_id') );
                $finder 		= 	array_except($finderarr, array('coverimage','findercollections','categorytags','locationtags','offerings','facilities','services','blogs'));
                $coverimage  	=	($finderarr['coverimage'] != '') ? $finderarr['coverimage'] : 'default/'.$finderarr['category_id'].'-'.rand(1, 4).'.jpg';
                array_set($finder, 'coverimage', $coverimage);

                $finder['today_opening_hour'] =  null;
                $finder['today_closing_hour'] = null;

                if(isset($finderarr['category_id']) && $finderarr['category_id'] == 5){

                    if(isset($finderarr['services']) && count($finderarr['services']) > 0){
                        //for servcie category gym
                        $finder_gym_service  = [];
                        $finder_gym_service = head(array_where($finderarr['services'], function($key, $value){
                            if($value['category']['_id'] == 65){ return $value; }
                        }));

                        if(isset($finder_gym_service['trialschedules']) && count($finder_gym_service['trialschedules']) > 0){
                            $all_weekdays                       =   $finder_gym_service['active_weekdays'];
                            $today_weekday 		                = 	strtolower(date( "l", time()));

                            foreach ($all_weekdays as $weekday){
                                $whole_week_open_close_hour_Arr             =   [];
                                $slots_start_time_24_hour_format_Arr 		=   [];
                                $slots_end_time_24_hour_format_Arr 			=   [];

                                $weekdayslots       =   head(array_where($finder_gym_service['trialschedules'], function($key, $value) use ($weekday){
                                    if($value['weekday'] == $weekday){
                                        return $value;
                                    }
                                }));// weekdayslots

                                if(isset($weekdayslots['slots']) && count($weekdayslots['slots']) > 0){
                                    foreach ($weekdayslots['slots'] as $key => $slot) {
                                        array_push($slots_start_time_24_hour_format_Arr, intval($slot['start_time_24_hour_format']));
                                        array_push($slots_end_time_24_hour_format_Arr, intval($slot['end_time_24_hour_format']));
                                    }
                                    if(!empty($slots_start_time_24_hour_format_Arr) && !empty($slots_end_time_24_hour_format_Arr)){
                                        $opening_hour = min($slots_start_time_24_hour_format_Arr);
                                        $closing_hour = max($slots_end_time_24_hour_format_Arr);
                                        //   $finder['opening_hour'] = min($slots_start_time_24_hour_format_Arr);
                                        //   $finder['closing_hour'] = max($slots_end_time_24_hour_format_Arr)
                                        if($today_weekday == $weekday){
                                         $finder['today_opening_hour'] =  date("g:i A", strtotime("$opening_hour:00"));
                                         $finder['today_closing_hour'] = date("g:i A", strtotime("$closing_hour:00"));
                                     }
                                     $whole_week_open_close_hour[$weekday]['opening_hour'] = date("g:i A", strtotime("$opening_hour:00"));
                                     $whole_week_open_close_hour[$weekday]['closing_hour'] = date("g:i A", strtotime("$closing_hour:00"));
                                     array_push($whole_week_open_close_hour_Arr, $whole_week_open_close_hour);
                                 }
                             }
                         }

                         $finder['open_close_hour_for_week'] = (!empty($whole_week_open_close_hour_Arr) && count($whole_week_open_close_hour_Arr) > 0) ? head($whole_week_open_close_hour_Arr) : [];

                        }// trialschedules

                    }
                }


                if(isset($finderarr['ozonetelno']) && $finderarr['ozonetelno'] != ''){
                    $finderarr['ozonetelno']['phone_number'] = '+'.$finderarr['ozonetelno']['phone_number'];
                    $finder['ozonetelno'] = $finderarr['ozonetelno'];
                }


                $finder['associate_finder'] = null;
                if(isset($finderarr['associate_finder']) && $finderarr['associate_finder'] != ''){

                    $associate_finder = array_map('intval',$finderarr['associate_finder']);
                    $associate_finder = Finder::active()->whereIn('_id',$associate_finder)->get(array('_id','title','slug'))->toArray();
                    $finder['associate_finder'] = $associate_finder;
                }

                array_set($finder, 'finder_coverimage_webp', substr($finderarr['coverimage'], 0, -3)."webp");
                // array_set($finder, 'finder_coverimage_color', "#4286f4");
                array_set($finder, 'services', pluck( $finderarr['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category','subcategory','batches','vip_trial','meal_type']  ));
                array_set($finder, 'categorytags', pluck( $finderarr['categorytags'] , array('_id', 'name', 'slug', 'offering_header') ));
                array_set($finder, 'findercollections', pluck( $finderarr['findercollections'] , array('_id', 'name', 'slug') ));
                array_set($finder, 'blogs', pluck( $finderarr['blogs'] , array('_id', 'title', 'slug', 'coverimage') ));
                array_set($finder, 'locationtags', pluck( $finderarr['locationtags'] , array('_id', 'name', 'slug') ));
                array_set($finder, 'offerings', pluck( $finderarr['offerings'] , array('_id', 'name', 'slug') ));
                array_set($finder, 'facilities', pluck( $finderarr['facilities'] , array('_id', 'name', 'slug') ));

                if(count($finder['services']) > 0 ){

                    $info_timing = $this->getInfoTiming($finder['services']);

                    if(isset($finder['info']) && $info_timing != ""){
                        $finder['info']['timing'] = $info_timing;
                    }
                    
                }
                if(count($finder['offerings']) > 0 ){
                    $tempoffering = [];
                    $tempofferingname = [];
                    foreach ($finder['offerings'] as $offering) {
                        if(in_array(strtolower($offering["name"]),$tempofferingname)){

                        }else{
                            array_push($tempoffering,$offering);
                            array_push($tempofferingname,strtolower($offering["name"]));
                        }
                    }
                    $finder['offerings'] = $tempoffering;
                    
                }

                $fitmania_offer_cnt 	=	Serviceoffer::where('finder_id', '=', intval($finderarr['_id']))->where("active" , "=" , 1)->whereIn("type" ,["fitmania-dod", "fitmania-dow","fitmania-membership-giveaways"])->count();
                if($fitmania_offer_cnt > 0){
                    array_set($finder, 'fitmania_offer_exist', true);
                }else{
                    array_set($finder, 'fitmania_offer_exist', false);
                }

                if(isset($finderarr['brand_id'])){

                    $brand = Brand::find((int)$finderarr['brand_id']);

                    $brandFinder = Finder::where("brand_id",(int)$finderarr['brand_id'])
                                    ->active()
                                    ->where("_id","!=",(int)$finderarr['_id'])
                                    ->where('city_id',(int)$finderarr['city_id'])
                                    ->with('offerings')
                                    ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                                    ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
                                    ->get(['_id','title','slug','brand_id','location_id','city_id','offerings','average_rating','finder_coverimage','coverimage'])->toArray();
                    
                    foreach($brandFinder as $key => $finder1){
                        array_set($brandFinder[$key], 'offerings', pluck( $finder1['offerings'] , array('_id', 'name', 'slug') ));
                    }

                    $finderarr['brand']['brand_detail'] = $brand;
                    $finderarr['brand']['finder_detail'] = $brandFinder;
                    $finder['brand'] = $finderarr['brand'];

                }

            }else{

                $finder = null;
            }

            if($finder){

                $categoryTagDefinationArr     =   [];
                
                // $Findercategory         =   Findercategory::find(intval($finderarr['category_id']));                

                $findercategorytag_ids      =   array_pluck(pluck( $finderarr['categorytags'] , array('_id')), '_id');
                $Findercategorytags         =   Findercategorytag::whereIn('_id', $findercategorytag_ids)->get()->toArray();

                foreach ($Findercategorytags as $key => $Findercategorytag) {
                    if(isset($Findercategorytag['defination']) && isset($Findercategorytag['defination']) && count($Findercategorytag['defination']) > 0){
                        $maxCnt                                 =   count($Findercategorytag['defination']) - 1;
                        $categoryTagDefination['slug']          =   $Findercategorytag['slug'];
                        $categoryTagDefination['defination']    =   $Findercategorytag['defination'][rand(0,$maxCnt)];
                        array_push($categoryTagDefinationArr, $categoryTagDefination);
                    }
                }

                // return $categoryTagDefinationArr;



                $finderdata 		=	$finder;
                $finderid 			= (int) $finderdata['_id'];
                $finder_cityid 		= (int) $finderdata['city_id'];
                $findercategoryid 	= (int) $finderdata['category_id'];
                $finderlocationid 	= (int) $finderdata['location_id'];

                $skip_categoryid_finders    = [41,42,45,25,46,10,26,40];

                $nearby_same_category 		= 	Finder::where('category_id','=',$findercategoryid)
                    ->where('location_id','=',$finderlocationid)
                    ->where('_id','!=',$finderid)
                    ->where('status', '=', '1')
                    ->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
                    ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                    ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
                    ->with('offerings')
                    ->orderBy('finder_type', 'DESC')
                    ->remember(Config::get('app.cachetime'))
                    ->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','finder_coverimage','offerings'))
                    ->take(5)->toArray();
                foreach($nearby_same_category as $key => $finder1){
                    array_set($nearby_same_category[$key], 'offerings', pluck( $finder1['offerings'] , array('_id', 'name', 'slug') ));
                }

                $nearby_other_category 		= 	Finder::where('category_id','!=',$findercategoryid)
                    ->whereNotIn('category_id', $skip_categoryid_finders)
                    ->where('location_id','=',$finderlocationid)
                    ->where('_id','!=',$finderid)
                    ->where('status', '=', '1')
                    ->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
                    ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                    ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
                    ->with('offerings')
                    ->orderBy('finder_type', 'DESC')
                    ->remember(Config::get('app.cachetime'))
                    ->get(array('_id','average_rating','category_id','coverimage','finder_coverimage', 'slug','title','category','location_id','location','city_id','city','total_rating_count','logo','finder_coverimage','offerings'))
                    ->take(5)->toArray();
                foreach($nearby_other_category as $key => $finder1){
                    array_set($nearby_other_category[$key], 'offerings', pluck( $finder1['offerings'] , array('_id', 'name', 'slug') ));
                }
                $data['statusfinder'] 					= 		200;
                $data['finder']                         =       $finder;
                $data['defination'] 					= 		['categorytags' => $categoryTagDefinationArr];
                $data['nearby_same_category'] 			= 		$nearby_same_category;
                $data['nearby_other_category'] 			= 		$nearby_other_category;

                $data = Cache::tags('finder_detail')->put($tslug,$data,Config::get('cache.cache_time'));
                $data = Cache::tags('finder_detail')->get($tslug);
                if(Request::header('Authorization')){
                    $decoded                            =       decode_customer_token();
                    $customer_email                     =       $decoded->customer->email;
                    $customer_phone                     =       $decoded->customer->contact_no;
                    $customer_id                        =       $decoded->customer->customer_id;
                    $customer_trials_with_vendors       =       Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->where('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
                                                                    ->where('finder_id', '=', (int) $finderid)
                                                                    ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                                                                    ->get(array('id'));
                    $data['trials_detials']              =      $customer_trials_with_vendors;
                    $data['trials_booked_status']        =      (count($customer_trials_with_vendors) > 0) ? true : false;
                }else{
                    $data['trials_detials']              =      [];
                    $data['trials_booked_status']        =      false;
                }



                

                return Response::json($data);

            }else{

                $updatefindersulg 		= Urlredirect::whereIn('oldslug',array($tslug))->firstOrFail();
                $data['finder'] 		= $updatefindersulg->newslug;
                $data['statusfinder'] 	= 404;

                return Response::json($data);
            }
        }else{


            $finderData = Cache::tags('finder_detail')->get($tslug);

            if(Request::header('Authorization')){
                $decoded                            =       decode_customer_token();
                $customer_email                     =       $decoded->customer->email;
                $customer_phone                     =       $decoded->customer->contact_no;
                $customer_trials_with_vendors       =       Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->where('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
                    ->where('finder_id', '=', (int) $finderData['finder']['_id'])
                    ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                    ->get(array('id'));
                $finderData['trials_detials']              =      $customer_trials_with_vendors;
                $finderData['trials_booked_status']        =      (count($customer_trials_with_vendors) > 0) ? true : false;
            }else{
                $finderData['trials_detials']              =      [];
                $finderData['trials_booked_status']        =      false;
            }

            return Response::json($finderData);
        }
    }



    public function finderServices($finderid){
        $finderid 	=  	(int) $finderid;
        $finder = Finder::active()->with(array('services'=>function($query){$query->select('*')->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))->where('_id','=',$finderid)->first();
        if($finder){
            $finderarr = $finder->toArray();
            $data['message'] 		= "Finder Detail With services";
            $data['status'] 		= 200;
            $data['finder'] 		= array_only($finderarr, array('services'));
        }else{
            $data['message'] 		= "Finder Does Not Exist";
            $data['status'] 		= 404;
        }

        return Response::json($data,200);
    }




    // public function ratecards($finderid){

    // 	$finderid 	=  	(int) $finderid;
    // 	$ratecard 	= 	Ratecard::where('finder_id', '=', $finderid)->where('going_status', '=', 1)->orderBy('_id', 'desc')->get($selectfields)->toArray();
    // 	$resp 		= 	array('status' => 200,'ratecard' => $ratecard);
    // 	return Response::json($resp);
    // }

    public function ratecarddetail($id){
        $id 		=  	(int) $id;
        $ratecard 	= 	Ratecard::find($id);

        if($ratecard){
            $resp 	= 	array('status' => 200,'ratecard' => $ratecard);
        }else{
            $resp 	= 	array('status' => 200,'message' => 'No Ratecard exist :)');
        }
        return Response::json($resp,200);
    }


    public function getfinderleftside(){
        $data = array('categorytag_offerings' => Findercategorytag::active()->with('offerings')->orderBy('ordering')->get(array('_id','name','offering_header','slug','status','offerings')),
            'locations' => Location::active()->whereIn('cities',array(1))->orderBy('name')->get(array('name','_id','slug','location_group')),
            'price_range' => array(
                array("slug" =>"one","name" => "less than 1000"),
                array("slug"=>"two","name" => "1000-2500"),
                array("slug" =>"three","name" => "2500-5000"),
                array("slug"=>"four","name" => "5000-7500"),
                array("slug"=>"five" ,"name"=> "7500-15000"),
                array("slug"=>"six","name"=> "15000 & above")
                ),
            'facilities' => Facility::active()->orderBy('name')->get(array('name','_id','slug'))
            );
        return Response::json($data,200);
    }



    public function pushfinder2elastic ($slug){

        $tslug 		= 	(string) $slug;
        $finderarr 	= 	Finder::active()->with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title','detail_rating');}))
        ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
        ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
        ->with('categorytags')
        ->with('locationtags')
        ->with('offerings')
        ->with('facilities')
        ->with('servicerates')
        ->with('services')
        ->where('slug','=',$tslug)
        ->first();
        $data 		= 	$finderarr->toArray();
        // print_pretty($data);exit;
        $postdata 	= 	get_elastic_finder_document($data);

        $request = array(
            'url' => $this->elasticsearch_url."fitternity/finder/".$data['_id'],
            'port' => Config::get('app.es.port'),
            'method' => 'PUT',
            'postfields' => json_encode($postdata)
            );
        //echo es_curl_request($request);exit;
        es_curl_request($request);
    }

    public function updatefinderrating (){

        // $data = Input::all()->json();
        // return $data;
        $finderid = (int) Input::json()->get('finderid');
        $total_rating_count = round(floatval(Input::json()->get('total_rating_count')),1);
        $average_rating =  round(floatval(Input::json()->get('average_rating')),1);

        $finder = Finder::findOrFail($finderid);
        $finderslug = $finder->slug;
        $finderdata = array();


        //cache set

        array_set($finderdata, 'average_rating', round($average_rating,1));
        array_set($finderdata, 'total_rating_count', round($total_rating_count,1));

        // return $finderdata;
        if($finder->update($finderdata)){
            //updating elastic search
            $this->pushfinder2elastic($finderslug);
            //sending email
            $email_template = 'emails.review';
            $email_template_data = array( 'vendor' 	=>	ucwords($finderslug) ,  'date' 	=>	date("h:i:sa") );

            // print_pretty($email_template_data);  exit;


            $email_message_data = array(
                'to' => Config::get('mail.to_neha'),
                'reciver_name' => 'Fitternity',
                'bcc_emailids' => Config::get('mail.bcc_emailds_review'),
                'email_subject' => 'Review given for - ' .ucwords($finderslug)
                );
            $email = Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
                $message->to($email_message_data['to'], $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
                // $message->to('sanjay.id7@gmail.com', $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
            });

            //sending response
            $rating  = 	array('average_rating' => $finder->average_rating, 'total_rating_count' => $finder->total_rating_count);
            $resp 	 = 	array('status' => 200, 'rating' => $rating, "message" => "Rating Updated Successful :)");

            return Response::json($resp);
        }
    }

    public function updatefinderlocaiton (){

        $items = Finder::active()->orderBy('_id')->whereIn('location_id',array(14))->get(array('_id','location_id'));
        //exit;
        $finderdata = array();
        foreach ($items as $item) {
            $data 	= $item->toArray();
            //print_pretty($data);
            array_set($finderdata, 'location_id', 69);
            $finder = Finder::findOrFail($data['_id']);
            $response = $finder->update($finderdata);
            print_pretty($response);
        }

    }


    public function getallfinder(){
        //->take(2)
        $items = Finder::active()->orderBy('_id')->get(array('_id','slug','title'));
        return Response::json($items);
    }


    public function sendbooktrialdaliysummary(){

        $start_time = time();
        $cron = new Cron;
        $flag = true;
        $message = '';

        try{

            // $tommorowDateTime 	=	date('d-m-Y', strtotime('02-09-2015'));
            $tommorowDateTime 	=	date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
            //$finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();

            $final_lead_status = array('rescheduled','confirmed');

            $finders            =   Booktrial::where('final_lead_stage', 'trial_stage')->whereIn('final_lead_status',$final_lead_status)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();
            // $finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->where('finder_id', '=',3305)->get()->groupBy('finder_id')->toArray();

            // echo $todayDateTime 		=	date('d-m-Y', strtotime(Carbon::now()) );
            // return $todaytrialarr 		=	Booktrial::where('going_status', 1)
            // ->where('schedule_date', '>=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
            // ->where('schedule_date', '<=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
            // ->where('finder_id', 3305 )->get();

            foreach ($finders as $finderid => $trials) {
                $finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
                $finderarr 	= 	$finder->toArray();

                if($finder->finder_vcc_email != ""){
                    $finder_vcc_email = "";
                    $explode = explode(',', $finder->finder_vcc_email);
                    $valid_finder_email = [];
                    foreach ($explode as $email) {
                        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
                            $valid_finder_email[] = $email;
                        }
                    }
                    if(!empty($valid_finder_email)){
                        $finder_vcc_email = implode(",", $valid_finder_email);
                    }

                    // echo "<br>finderid  ---- $finder->_id <br>finder_vcc_email  ---- $finder->finder_vcc_email";
                    // echo "<pre>";print_r($trials);

                    $finder_name_new					= 	(isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
                    $finder_location_new				=	(isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
                    $finder_name_base_locationtags 		= 	(count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

                    $trialdata = array();
                    foreach ($trials as $key => $value) {
                        $trial = array('customer_name' => $value->customer_name,
                            'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
                            'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ),
                            'schedule_slot' => $value->schedule_slot,
                            'code' => $value->code,
                            'service_name' => $value->service_name,
                            'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name,
                            'type' => $value->type,
                            );
                        array_push($trialdata, $trial);
                    }

                    $todayDateTime 		=	date('d-m-Y', strtotime(Carbon::now()) );

                    //$todaytrialarr 		=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($todayDateTime))->where('finder_id', intval($finder->_id) )->get();

                    $todaytrialarr         =   Booktrial::where('final_lead_stage', 'trial_stage')->whereIn('final_lead_status',$final_lead_status)->where('schedule_date', '=', new DateTime($todayDateTime))->where('finder_id', intval($finder->_id) )->get();

                    $todaytrialdata = array();
                    if($todaytrialarr){
                        foreach ($todaytrialarr as $key => $value) {
                            $trial = array('customer_name' => $value->customer_name,
                                'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
                                'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ),
                                'schedule_slot' => $value->schedule_slot,
                                'code' => $value->code,
                                'service_name' => $value->service_name,
                                'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name,
                                'type' => $value->type,
                                );
                            array_push($todaytrialdata, $trial);
                        }
                    }


                    $scheduledata = array('user_name'	=> 'sanjay sahu',
                        'user_email'					=> 'sanjay.id7@gmail',
                        'finder_name'					=> $finder->title,
                        'finder_name_base_locationtags'	=> $finder_name_base_locationtags,
                        'finder_poc_for_customer_name'	=> $finder->finder_poc_for_customer_name,
                        'finder_vcc_email'				=> $finder_vcc_email,
                        'scheduletrials' 				=> $trialdata,
                        'todaytrials' 					=> $todaytrialdata
                        );
//                     echo "<pre>";print_r($scheduledata);exit();

                    $this->findermailer->sendBookTrialDaliySummary($scheduledata);
                }
            }

            Log::info('Trial Daily Summary Cron : success');
            $message = 'Email Send';
            $resp 	= 	array('status' => 200,'message' => "Email Send");


        }catch(Exception $e){


            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                );

            $resp 	= 	array('status' => 400,'message' => $message);
            Log::info('Trial Daily Summary Cron : fial',$message);
            $flag = false;
        }

        $end_time = time();
        $data = [];
        $data['label'] = 'TrialDailySummary';
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $data['status'] = ($flag) ? '1' : '0';
        $data['message'] = $message;

        $cron = $cron->cronLog($data);

        return Response::json($resp);


    }

    public function checkbooktrialdaliysummary($date){
        //give one date before
        $tommorowDateTime 	=	date('d-m-Y', strtotime($date));
        $finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->get()->groupBy('finder_id')->toArray();
        // $finders 			=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($tommorowDateTime))->where('finder_id', '=',3305)->get()->groupBy('finder_id')->toArray();

        // echo $todayDateTime 		=	date('d-m-Y', strtotime(Carbon::now()) );
        // return $todaytrialarr 		=	Booktrial::where('going_status', 1)
        // ->where('schedule_date', '>=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
        // ->where('schedule_date', '<=', new DateTime( date("d-m-Y", strtotime( $todayDateTime )) ))
        // ->where('finder_id', 3305 )->get();

        foreach ($finders as $finderid => $trials) {
            $finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
            $finderarr 	= 	$finder->toArray();

            if($finder->finder_vcc_email != ""){
                $finder_vcc_email = "";
                $explode = explode(',', $finder->finder_vcc_email);
                $valid_finder_email = [];
                foreach ($explode as $email) {
                    if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
                        $valid_finder_email[] = $email;
                    }
                }
                if(!empty($valid_finder_email)){
                    $finder_vcc_email = implode(",", $valid_finder_email);
                }

                // echo "<br>finderid  ---- $finder->_id <br>finder_vcc_email  ---- $finder->finder_vcc_email";
                // echo "<pre>";print_r($trials);

                $finder_name_new					= 	(isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
                $finder_location_new				=	(isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
                $finder_name_base_locationtags 		= 	(count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

                $trialdata = array();
                foreach ($trials as $key => $value) {
                    $trial = array('customer_name' => $value->customer_name,
                        'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
                        'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ),
                        'schedule_slot' => $value->schedule_slot,
                        'code' => $value->code,
                        'service_name' => $value->service_name,
                        'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name
                        );
                    array_push($trialdata, $trial);
                }

                $todayDateTime 		=	date('d-m-Y', strtotime(Carbon::now()) );
                $todaytrialarr 		=	Booktrial::where('going_status', 1)->where('schedule_date', '=', new DateTime($todayDateTime))->where('finder_id', intval($finder->_id) )->get();
                $todaytrialdata = array();
                if($todaytrialarr){
                    foreach ($todaytrialarr as $key => $value) {
                        $trial = array('customer_name' => $value->customer_name,
                            'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
                            'schedule_date' => date('d-m-Y', strtotime($value->schedule_date) ),
                            'schedule_slot' => $value->schedule_slot,
                            'code' => $value->code,
                            'service_name' => $value->service_name,
                            'finder_poc_for_customer_name' => $value->finder_poc_for_customer_name
                            );
                        array_push($todaytrialdata, $trial);
                    }
                }


                $scheduledata = array('user_name'	=> 'sanjay sahu',
                    'user_email'					=> 'sanjay.id7@gmail',
                    'finder_name'					=> $finder->title,
                    'finder_name_base_locationtags'	=> $finder_name_base_locationtags,
                    'finder_poc_for_customer_name'	=> $finder->finder_poc_for_customer_name,
                    'finder_vcc_email'				=> $finder_vcc_email,
                    'scheduletrials' 				=> $trialdata,
                    'todaytrials' 					=> $todaytrialdata
                    );
//                echo "<pre>";print_r($scheduledata);

                 $this->findermailer->sendBookTrialDaliySummary($scheduledata);
            }
        }

        $resp 	= 	array('status' => 200,'message' => "Email Send");
        return Response::json($resp);

    }


    public function sendDaliySummaryHealthyTiffin()
    {

//        $todayDate 	=	date('d-m-Y', strtotime('06-05-2015'));
        $todayDate 	        =	date('d-m-Y', time());
        $tommorowDateTime 	=	date('d-m-Y', strtotime(Carbon::now()->addDays(1)));
        $startDateTime 	    =	$todayDate." 00:00:00";
        $endDateTime        =	$tommorowDateTime." 00:00:00";

//        return "$startDateTime   $endDateTime";

        try{
        $finders   =   Order::whereIn('type',['healthytiffinmembership','healthytiffintrail'])->where('status', '=', '1')
                                    ->where('created_at', '>=', new DateTime($startDateTime))
                                    ->where('created_at', '<=', new DateTime($endDateTime))
                                    ->get()
                                    ->groupBy('finder_id')->toArray();


        foreach ($finders as $finderid => $trials) {
            $finder 	= 	Finder::with(array('location'=>function($query){$query->select('_id','name','slug');}))->with('locationtags')->where('_id','=',intval($finderid))->first();
            $finderarr 	= 	$finder->toArray();

            if($finder->finder_vcc_email != ""){
                $finder_vcc_email = "";
                $explode = explode(',', $finder->finder_vcc_email);
                $valid_finder_email = [];
                foreach ($explode as $email) {
                    if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
                        $valid_finder_email[] = $email;
                    }
                }
                if(!empty($valid_finder_email)){
                    $finder_vcc_email = implode(",", $valid_finder_email);
                }

                // echo "<br>finderid  ---- $finder->_id <br>finder_vcc_email  ---- $finder->finder_vcc_email";
                // echo "<pre>";print_r($trials);

                $finder_name_new					= 	(isset($finderarr['title']) && $finderarr['title'] != '') ? $finderarr['title'] : "";
                $finder_location_new				=	(isset($finderarr['location']['name']) && $finderarr['location']['name'] != '') ? $finderarr['location']['name'] : "";
                $finder_name_base_locationtags 		= 	(count($finderarr['locationtags']) > 1) ? $finder_name_new : $finder_name_new." ".$finder_location_new;

                $trialsData = $purchasesData = array();
                foreach ($trials as $key => $value) {
                    $trial = ['customer_name' => $value->customer_name,
                        'customer_phone' => (isset($finderarr['share_customer_no']) && $finderarr['share_customer_no'] == '1') ? $value->customer_phone : '',
                        'customer_email' => $value->customer_email,
                        'preferred_starting_date' => date('d-m-Y', strtotime($value->preferred_starting_date) ),
                        'code' => $value->code,
                        'code' => $value->code,
                        'service_name' => $value->service_name,
                        'service_duration' => $value->service_duration,
                        'meal_contents' => $value->meal_contents,
                        'amount' => $value->amount
                    ];

                    if($value->type == "healthytiffintrail"){
                        array_push($trialsData, $trial);
                    }

                    if($value->type == "healthytiffinmembership"){
                        array_push($purchasesData, $trial);
                    }
                }

                $scheduledata = array('user_name'	=> 'sanjay sahu',
                    'user_email'					=> 'sanjay.id7@gmail',
                    'finder_name'					=> $finder->title,
                    'finder_name_base_locationtags'	=> $finder_name_base_locationtags,
                    'finder_poc_for_customer_name'	=> $finder->finder_poc_for_customer_name,
                    'finder_vcc_email'				=> $finder_vcc_email,
                    'trials' 				        => $trialsData,
                    'purchases' 		            => $purchasesData
                );
//                echo "<pre>";print_r($scheduledata);

                 $this->findermailer->sendDaliySummaryHealthyTiffin($scheduledata);
            }
        }

            $message = 'Email Send';
            $resp 	= 	array('status' => 200,'message' => "Email Send");
            Log::info('Trial Daily Summary Cron For Healthy Tiffin : success');

        }catch(Exception $e){
            $message = 'Email Send Fail';
            $resp 	= 	array('status' => 400,'message' => $message);
            Log::info('Trial Daily Summary Cron  For Healthy Tiffin : fail');
        }

    }

    public function migrateratecards(){

        //Ratecard::truncate();
        Ratecard::truncate();
        //exit;
        // $items = Finder::with('category')->with('location')->active()->orderBy('_id')->take(2)->get();
        $items = Finder::with('category')->with('location')->active()->orderBy('_id')->get();
        $finderdata = array();

        foreach ($items as $item) {
            $finderdata = $item->toArray();

            $finderratecards  	=	$finderdata['ratecards'];


            if(count($finderratecards) > 1){

                $finderid  			=	(int) $finderdata['_id'];
                $findercategory_id  =	$finderdata['category']['_id'];
                $location_id  		=	$finderdata['location']['_id'];
                $interest  			=	$finderdata['category']['name'];
                $area  				=	$finderdata['location']['name'];

                foreach ($finderratecards as $key => $value) {

                    $ratedata 		= array();
                    array_set($ratedata, 'finder_id', $finderid );
                    array_set($ratedata, 'name', $value['service_name']);
                    array_set($ratedata, 'slug', url_slug(array($value['service_name'])));
                    array_set($ratedata, 'duration', $value['duration']);
                    array_set($ratedata, 'price', intval($value['price']));
                    array_set($ratedata, 'special_price', intval($value['special_price']));
                    array_set($ratedata, 'product_url', $value['product_url']);
                    array_set($ratedata, 'order',  (isset($value['order']) && $value['order'] != '') ? intval($value['order']) : 0);

                    array_set($ratedata, 'findercategory_id', $findercategory_id );
                    array_set($ratedata, 'location_id', $location_id );
                    array_set($ratedata, 'interest', $interest );
                    array_set($ratedata, 'area', $area );

                    array_set($ratedata, 'short_description', '' );
                    array_set($ratedata, 'body', '' );

                    echo "<br><br>finderid  --- $finderid";
                    //echo "<br>finderratecards <pre> "; print_r($ratedata);
                    $insertedid	= Ratecard::max('_id') + 1;
                    $ratecard 	= new Ratecard($ratedata);
                    $ratecard->_id = $insertedid;
                    $ratecard->save();
                }
            }

        }

    }

    public function updatepopularity (){

        // set popularity 10000 for following category
        $items = Finder::active()->where('finder_type', 0)->whereIn('city_id', array(2,3,4))->whereIn('category_id', array(5,11,14,32,35,6,12,8,7))->get();

        $finderdata = array();
        foreach ($items as $item) {
            $data 	= $item->toArray();
            array_set($finderdata, 'popularity', 10000);
            $finder = Finder::findOrFail($data['_id']);
            $response = $finder->update($finderdata);
            print_pretty($response);
        }

        // set popularity 4000 for following category
        $items = Finder::active()->where('finder_type', 0)->whereIn('city_id', array(2,3,4))->whereIn('category_id', array(36,41,25,42,26,40))->get();
        $finderdata = array();
        foreach ($items as $item) {
            $data 	= $item->toArray();
            array_set($finderdata, 'popularity', 4000);
            $finder = Finder::findOrFail($data['_id']);
            $response = $finder->update($finderdata);
            print_pretty($response);
        }

    }


    public function addReview(){
        // return Input::json()->all();
        $validator = Validator::make($data = Input::json()->all(), Review::$rules);
        if ($validator->fails()) {
            $response = array('status' => 400, 'message' => 'Could not create a review.', 'errors' => $validator->errors());
            return Response::json($response, 400);
        }

        $reviewdata = [
        'finder_id' => intval($data['finder_id']),
        'customer_id' => intval($data['customer_id']),
        'rating' => floatval($data['rating']),
        'detail_rating' => array_map('floatval',$data['detail_rating']),
        'description' => $data['description'],
        'uploads' => (isset($data['uploads'])) ? $data['uploads'] : [],
        'booktrial_id' => (isset($data['booktrialid'])) ? intval($data['booktrialid']) : '',
        'source' => (isset($data['source'])) ? $data['source'] : 'customer',
        'status' => '1'
        ];

        $reviewdata['booktrial_id'] = ($reviewdata['booktrial_id'] == "" && isset($data['booktrial_id']) && $data['booktrial_id'] != "") ? intval($data['booktrial_id']) : '';

        $finderobj = Finder::where('_id', intval($data['finder_id']))->first();
        //$cacheurl = 'flushtagkey/finder_detail/'.$finderobj->slug;
        //clear_cache($cacheurl);

        //if exist then update
        $oldreview = Review::where('finder_id', intval($data['finder_id']))->where('customer_id', intval($data['customer_id']))->first();

        if($oldreview){
            $updatefinder = $this->updateFinderRatingV1($reviewdata,$oldreview);
            $oldreviewobj = Review::findOrFail(intval($oldreview->_id));
            $oldreviewobj->update($reviewdata);
            $review_id = $oldreview->_id;
            $response = array('status' => 200, 'message' => 'Review Updated Successfully.','id'=>$oldreview->_id);
        }else{
            $inserted_id = Review::max('_id') + 1;
            $review = new Review($reviewdata);
            $review->_id = $inserted_id;
            $reviewobject = $review->save();
            $updatefinder = $this->updateFinderRatingV1($reviewdata);
            $review_id = $inserted_id;

            Log::info('Customer Review : '.json_encode(array('review_details' => Review::findOrFail($inserted_id))));
            $response = array('status' => 200, 'message' => 'Review Created Successfully.','id'=>$inserted_id);
        }

        if(isset($data['booktrialid']) &&  $data['booktrialid'] != '' && isset($review_id) &&  $review_id != ''){
            $booktrial_id 	=	(int) $data['booktrialid'];
            $trial 			= 	Booktrial::find($booktrial_id);
            $trialdata 	=	$trial->update(['review_id'=> intval($review_id), 'has_reviewed' => '1']);
        }

        $this->cacheapi->flushTagKey('finder_detail',$finderobj->slug);
        $this->cacheapi->flushTagKey('review_by_finder_list',$finderobj->slug);
        return Response::json($response, 200);
    }

    public function updateFinderRatingV1 ($review, $oldreview = NULL ){

        $data 					=	$review;
        $total_rating_count 	=	round(floatval(Input::json()->get('total_rating_count')),1);
        $average_rating 		=	round(floatval(Input::json()->get('average_rating')),1);
        $finderdata 			=	array();
        $finderid 				=	(int) $data['finder_id'];
        $finder 				=	Finder::findOrFail($finderid);
        $finderslug 			=	$finder->slug;
        $total_rating_count 	=	Review::where('finder_id', $finderid)->count();
        $sum_rating 		=	Review::where('finder_id', $finderid)->sum('rating');

        array_set($finderdata, 'total_rating_count', round($total_rating_count,1));
        array_set($finderdata, 'average_rating', ($sum_rating/$total_rating_count));

        //Detail rating summary count && Detail rating summary avg
        if(isset($finder->detail_rating_summary_average) && !empty($finder->detail_rating_summary_average)){
            if(isset($finder->detail_rating_summary_count) && !empty($finder->detail_rating_summary_count)){
                $detail_rating_summary_average = $finder->detail_rating_summary_average;
                $detail_rating_summary_count = $finder->detail_rating_summary_count;
                if($oldreview == NULL){
                    for($i = 0; $i < 5; $i++) {
                        if($data['detail_rating'][$i] > 0){
                            $sum_detail_rating = floatval(floatval($finder->detail_rating_summary_average[$i]) * floatval($finder->detail_rating_summary_count[$i]));
                            $detail_rating_summary_average[$i] = ($sum_detail_rating + $data['detail_rating'][$i])/($detail_rating_summary_count[$i]+1);
                            $detail_rating_summary_count[$i] = (int) $detail_rating_summary_count[$i]+1;
                        }
                    }

                }else{
                    for($i = 0; $i < 5; $i++) {
                        if($oldreview['detail_rating'][$i] == 0 && $data['detail_rating'][$i] > 0){
                            $sum_detail_rating = floatval(floatval($finder->detail_rating_summary_average[$i]) * floatval($finder->detail_rating_summary_count[$i])) - $oldreview['detail_rating'][$i];
                            $detail_rating_summary_average[$i] = ($sum_detail_rating + $data['detail_rating'][$i])/($detail_rating_summary_count[$i]+1);
                            $detail_rating_summary_count[$i] = (int) $detail_rating_summary_count[$i]+1;
                        }
                        else if($data['detail_rating'][$i] == 0 && $oldreview['detail_rating'][$i] > 0){
                            $sum_detail_rating = floatval(floatval($finder->detail_rating_summary_average[$i]) * floatval($finder->detail_rating_summary_count[$i])) - $oldreview['detail_rating'][$i];
                            if($detail_rating_summary_count[$i] > 1){
                                $detail_rating_summary_average[$i] = ($sum_detail_rating)/($detail_rating_summary_count[$i]-1);
                            }
                            else{
                                $detail_rating_summary_average[$i] = 0;
                            }
                            $detail_rating_summary_count[$i] = (int) $detail_rating_summary_count[$i]-1;
                        }
                        else if($data['detail_rating'][$i] == 0 && $oldreview['detail_rating'][$i] == 0){

                        }
                        else{
                            $sum_detail_rating = floatval(floatval($finder->detail_rating_summary_average[$i]) * floatval($finder->detail_rating_summary_count[$i])) - $oldreview['detail_rating'][$i];
                            $detail_rating_summary_average[$i] = ($sum_detail_rating + $data['detail_rating'][$i])/($detail_rating_summary_count[$i]);
                        }
                    }
                }
            }
        }else{
            $detail_rating_summary_average = [0,0,0,0,0];
            $detail_rating_summary_count = [0,0,0,0,0];
            for($i = 0; $i < 5; $i++) {
                $detail_rating_summary_average[$i] =  ($data['detail_rating'][$i] > 0) ? $data['detail_rating'][$i] : 0;
                $detail_rating_summary_count[$i] = ($data['detail_rating'][$i] > 0) ? 1 : 0;
            }
        }
        array_set($finderdata, 'detail_rating_summary_average', $detail_rating_summary_average);
        array_set($finderdata, 'detail_rating_summary_count', $detail_rating_summary_count);

        // return $finderdata;
        $success = $finder->update($finderdata);
        // return $finder;

        if($finder->update($finderdata)){
            //updating elastic search
            // $this->pushfinder2elastic($finderslug);
            //sending email
            $email_template = 'emails.review';
            $email_template_data = array( 'vendor' 	=>	ucwords($finderslug) , 'review' => $data['description'] ,  'date' 	=>	date("h:i:sa") );
            $email_message_data = array(
                'to' => Config::get('mail.to_neha'),
                'reciver_name' => 'Fitternity',
                'bcc_emailids' => Config::get('mail.bcc_emailds_review'),
                'email_subject' => 'Review given for - ' .ucwords($finderslug)
                );
            $email = Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
                // $message->to($email_message_data['to'], $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
                $message->to('sanjay.id7@gmail.com', $email_message_data['reciver_name'])->bcc($email_message_data['bcc_emailids'])->subject($email_message_data['email_subject']);
            });

            //sending response
            $rating  = 	array('average_rating' => $finder->average_rating, 'total_rating_count' => $finder->total_rating_count, 'detail_rating_summary_average' => $finder->detail_rating_summary_average, 'detail_rating_summary_count' => $finder->detail_rating_summary_count);
            $resp 	 = 	array('status' => 200, 'rating' => $rating, "message" => "Rating Updated Successful :)");
            return Response::json($resp);
        }
    }

    public function getFinderReview($slug,$cache = true){
        $data = array();
        $tslug = (string) $slug;

        $review_by_finder_list = $cache ? Cache::tags('review_by_finder_list')->has($tslug) : false;

        if(!$review_by_finder_list){

            $finder_by_slug= Finder::where('slug','=',$tslug)->firstOrFail();

            if(!empty($finder_by_slug)){

                $finder_id 	= (int) $finder_by_slug['_id'];
                $reviews = Review::where('status', '!=', '1')
                ->where('finder_id','=',$finder_id)
                ->orderBy('_id', 'desc')
                ->get(array('_id','finder_id','customer_id','customer','rating','detail_rating','description','updated_at','created_at'));

                $data = array('status' => 200,'data'=>$reviews);

                Cache::tags('review_by_finder_list')->put($slug,$data,Config::get('app.cachetime'));
                $response = $data;

            }else{
                $response = array('status' => 200,'message'=>'no reviews');
            }
        }else{

            $response = Cache::tags('review_by_finder_list')->get($tslug);
        }

        return Response::json($response);
    }


    /**
     * Return the specified reivew.
     *
     * @param  int  	$reivewid
     * @param  string  	$slug
     * @return Response
     */

    public function detailReview($reivewid){

        $review = Review::with('finder')->where('_id', (int) $reivewid)->first();

        if(!$review){
            $resp 	= 	array('status' => 400, 'review' => [], 'message' => 'No review Exist :)');
            return Response::json($resp, 400);
        }

        $reviewdata = $this->transform($review);
        $resp 	= 	array('status' => 200, 'review' => $reviewdata, 'message' => 'Particular Review Info');
        return Response::json($resp, 200);
    }


    private function transform($review){

        $item  =  (!is_array($review)) ? $review->toArray() : $review;
        $data = [
        'finder_id' => $item['finder_id'],
        'customer_id' => $item['customer_id'],
        'rating' => $item['rating'],
        'detail_rating' => $item['detail_rating'],
        'description' => $item['description'],
        'created_at' => $item['created_at'],
        'updated_at' => $item['updated_at'],
        'customer' => $item['customer'],
        'finder' =>  array_only($item['finder'], array('_id', 'title', 'slug'))
        ];

        return $data;
    }

    public function finderTopReview($slug, $limit = '', $cache=false){

        $limit 	=	($limit != '') ? intval($limit) : 10;
        $finder_detail_with_top_review = $cache ? Cache::tags('finder_detail_with_top_review')->has($slug) : false;

        if(!$finder_detail_with_top_review){
            $finder = array();
            $review = array();

            try {
                $finder = Finder::where('slug','=',(string)$slug)
                ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
                ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                ->first(array('title','photos','city_id','location_id','info','contact','total_rating_count','detail_rating_summary_average','detail_rating_summary_count'));
            } catch (Exception $error) {
                return $errorMessage = $this->errorMessage($error);
            }


            if(!is_null($finder) || !empty($finder) && isset($finder->_id)){
                try {
                    $review = Review::where('finder_id','=',$finder->_id)->orderBy('created_at', 'desc')->orderBy('rating', 'desc')->take($limit)->get();
                } catch (Exception $error) {
                    return $errorMessage = $this->errorMessage($error);
                }

                if(is_null($review)){
                    $review = array();
                }

            }else{
                $finder = array();
            }

            $data = [ 'finder' => $finder, 'review' => $review ];
            $response = array('status' => 200,'data'=>$data);

            if(!empty($finder) && !empty($review)){
                Cache::tags('finder_detail_with_top_review')->put($slug,$response,Config::get('app.cachetime'));
            }

        }else{
            $response = Cache::tags('finder_detail_with_top_review')->get($slug);
        }

        return Response::json($response,200);
    }


    public function errorMessage($error){

        $message = $error->getMessage().' in '.$error->getFile().' : '.$error->getLine();
        $status = 400;

        $response = array('status'=>$status,'message'=>$message);

        return Response::json($response,$status);
    }

    public function reviewListing($finder_id, $from = '', $size = ''){

        $finder_id			= 	(int) $finder_id;
        $from 				=	($from != '') ? intval($from) : 0;
        $size 				=	($size != '') ? intval($size) : 10;

        $reviews 			= 	Review::with(array('finder'=>function($query){$query->select('_id','title','slug','coverimage');}))->active()->where('finder_id','=',$finder_id)->take($size)->skip($from)->orderBy('_id', 'desc')->get();
        $responseData 		= 	['reviews' => $reviews,  'message' => 'List for reviews'];

        return Response::json($responseData, 200);
    }

    public function updateBudgetFromRatecardsToFinder(){

        $city_list = array(1,2,3,4,8);

        foreach ($city_list as $city) {


            $finder_documents = Finder::with(array('country'=>function($query){$query->select('name');}))
            ->with(array('city'=>function($query){$query->select('name');}))               
            ->active()
            ->orderBy('_id')
            ->where('city_id', intval($city))
            //->where('status', '=', '1')
            ->take(50000)->skip(0)
            ->timeout(400000000)
            ->get(); 


            foreach ($finder_documents as $finder) {

                $ratecards = Ratecard::where('finder_id', intval($finder['id']))->get();
                $ratecard_money = 0;
                $ratecard_count = 0;  $average_monthly = 0;

                foreach ($ratecards as $ratecard) {

                    switch($ratecard['validity']){
                        case 30:
                        $ratecard_count = $ratecard_count + 1;
                        $ratecard_money = $ratecard_money + intval($ratecard['price']);
                        break;
                        case 90:
                        $ratecard_count = $ratecard_count + 1;
                        $average_one_month = intval($ratecard['price'])/3;
                        $ratecard_money = $ratecard_money + $average_one_month;
                        break;
                        case 120:
                        $ratecard_count = $ratecard_count + 1;
                        $average_one_month = intval($ratecard['price'])/4;
                        $ratecard_money = $ratecard_money + $average_one_month;
                        break;
                        case 180:
                        $ratecard_count = $ratecard_count + 1;
                        $average_one_month = intval($ratecard['price'])/6;
                        $ratecard_money = $ratecard_money + $average_one_month;
                        break;
                        case 360:
                        $ratecard_count = $ratecard_count + 1;
                        $average_one_month = intval($ratecard['price'])/12;
                        $ratecard_money = $ratecard_money + $average_one_month;
                        break;
                    }  

                }

                if(($ratecard_count !==0)){

                    $average_monthly = ($ratecard_money) / ($ratecard_count);
                }

                $average_monthly_tag = '';

                switch($average_monthly){
                    case ($average_monthly < 1001):
                    $average_monthly_tag = 'one';
                    $rangeval = 1;
                    break;

                    case ($average_monthly > 1000 && $average_monthly < 2501):
                    $average_monthly_tag = 'two';
                    $rangeval = 2;
                    break;

                    case ($average_monthly > 2500 && $average_monthly < 5001):
                    $average_monthly_tag = 'three';
                    $rangeval = 3;
                    break;

                    case ($average_monthly > 5000 && $average_monthly < 7501):
                    $average_monthly_tag = 'four';
                    $rangeval = 4;
                    break;

                    case ($average_monthly > 7500 && $average_monthly < 15001):
                    $average_monthly_tag = 'five';
                    $rangeval = 5;
                    break;

                    case ($average_monthly > 15000):
                    $average_monthly_tag = 'six';
                    $rangeval = 6;
                    break;
                }
                
                $finderData = [];
            //Logo                
                $finderData['price_range']  = $average_monthly_tag;
                 $finderData['budget']  = round($average_monthly);

                $response = $finder->update($finderData);
            }
        }
    }


    public function getInfoTiming($services){

        $service_batch = array();

        foreach ($services as $service_key => $service_value){

            if(isset($service_value['batches']) && !empty($service_value['batches'])){

                $service_batch[$service_value['name']] = $this->getAllBatches($service_value['batches']);
            }
        }

        $info_timing = "";

        if(count($service_batch) > 0){

            foreach ($service_batch as $ser => $btch){

                $info_timing .= "<p><strong>".$ser."</strong></p>";
                foreach ($btch as $btch_value){

                    foreach ($btch_value as $key => $value) {
                        $info_timing .= "<p><i>".$this->matchAndReturn($value)." : </i>". $key ."</p>";
                    }

                }
            }
        }

        return $info_timing;

    }

    public function getAllBatches($batches){

        $result = array();

        foreach ($batches as $key => $batch) {

            $result_weekday = array();

            foreach ($batch as $data) {

                $count = 0;

                if(isset($data['slots'])){
                    foreach ($data['slots'] as $slot) {
                        if($count == 0){

                            if(isset($slot['weekday']) && isset($slot['slot_time'])){
                                $result_weekday[ucwords($slot['weekday'])] = strtoupper($slot['slot_time']);
                            }
                            
                        }else{
                            break;
                        }

                        $count++;
                    }
                }
            }

            $result[] = $this->getDupKeys($result_weekday);

        }

        return $result;
            
    }

    public function getDupKeys($array) {

        $dups = array();

        foreach ($array as $k => $v) {
                $dups[$v][] = $k;
        }

        foreach($dups as $k => $v){

            $dups[$k] = implode(", ", $v);

        }

        return $dups;
    }

    public function matchAndReturn($key){

        $match = array(
            "Monday, Tuesday, Wednesday"=>"Monday - Wednesday",
            "Monday, Tuesday, Wednesday, Thursday"=>"Monday - Thursday",
            "Monday, Tuesday, Wednesday, Thursday, Friday"=>"Monday - Friday",
            "Monday, Tuesday, Wednesday, Thursday, Friday, Saturday"=>"Monday - Saturday",
            "Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday"=>"Monday - Sunday",
        );

        if(array_key_exists($key,$match)){
            return $match[$key];
        }else{
            return $key;
        }
    }



    public function customerTokenDecode($token){

        $jwt_token = $token;
        $jwt_key = Config::get('app.jwt.key');
        $jwt_alg = Config::get('app.jwt.alg');
        $decodedToken = JWT::decode($jwt_token, $jwt_key,array($jwt_alg));

        return $decodedToken;
    }

    public function getTrialSchedule($finder_id,$category){

        $currentDateTime        =   date('Y-m-d');
        $finder_id               =   (int) $finder_id;
        $date                   =   date('Y-m-d');
        $timestamp              =   strtotime($date);
        $weekday                =   strtolower(date( "l", $timestamp));
        if($category->_id == 42){
            $membership_services = Ratecard::where('finder_id', $finder_id)->lists('service_id');
        }else{
            $membership_services = Ratecard::where('finder_id', $finder_id)->orWhere('type','membership')->orWhere('type','packages')->lists('service_id');
        }

        $membership_services = array_map('intval',$membership_services);

        $items = Service::active()->where('finder_id', $finder_id)->whereIn('_id', $membership_services)->get(array('_id','name','finder_id', 'serviceratecard','trialschedules','servicecategory_id','batches','short_description','photos'))->toArray();
        
        if(!$items){
            return array();
        }

        $scheduleservices = array();

        foreach ($items as $k => $item) {

            $extra_info = array();

            $extra_info[0] = array(
                'title'=>'Description',
                'icon'=>'http://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
                'description'=> (isset($item['short_description']) && count($item['short_description']) > 0) ? strip_tags($item['short_description']) : ""
            );

            unset($items[$k]['short_description']);

            $extra_info[1] = array(
                'title'=>'Avg. Calorie Burn',
                'icon'=>'http://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
                'description'=>'750 Kcal'
            );

            $extra_info[2] = array(
                'title'=>'Results',
                'icon'=>'http://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png',
                'description'=>'Burn Fat | Super Cardio'
            );

            $batches = array();

            if(isset($item['batches']) && count($item['batches']) > 0){

                $batches = $item['batches'];

                foreach ($batches as $batches_key => $batches_value) {

                    foreach ($batches_value as $batches_value_key => $value) {

                        $batches[$batches_key][$batches_value_key]['slots'] = $value['slots'][0];
                    }
                }
            }
            $photo = null;
            if(isset($item['photos']) && count($item['photos']) > 0){

                $photo = $item['photos'][0];
            }
            $service = array('_id' => $item['_id'], 'finder_id' => $item['finder_id'], 'service_name' => $item['name'], 'weekday' => $weekday,'ratecard'=>[],'slots'=>null,'extra_info'=>$extra_info,'batches'=>$batches,'image'=>$photo);

            if(count($item['serviceratecard']) > 0){
                $ratecardArr = [];
                foreach ($item['serviceratecard'] as $rateval){
                    if($category->_id == 42){
                        array_push($ratecardArr, $rateval);
                    }else{
                        if($rateval['type'] == 'membership' || $rateval['type'] == 'packages'){ array_push($ratecardArr, $rateval); }
                    }
                }
                $service['ratecard'] = $ratecardArr;
            }

            $time_in_seconds = time_passed_check($item['servicecategory_id']);

            if(isset($item['trialschedules']) && count($item['trialschedules']) > 0){

                $weekdayslots = head(array_where($item['trialschedules'], function($key, $value) use ($weekday){
                    if($value['weekday'] == $weekday){
                        return $value;
                    }
                }));

                $slots = array();

                if(count($weekdayslots['slots']) > 0){
                    foreach ($weekdayslots['slots'] as $slot) {
                        array_set($slot, 'start_time_24_hour_format', (string) $slot['start_time_24_hour_format']);
                        array_set($slot, 'end_time_24_hour_format', (string) $slot['end_time_24_hour_format']);
                        try{
                           $scheduleDateTimeUnix               =  strtotime(strtoupper($date." ".$slot['start_time']));
                            if(($scheduleDateTimeUnix - time()) > $time_in_seconds){
                                array_push($slots, $slot);
                            }
                        }catch(Exception $e){
                            Log::info("getTrialSchedule Error : ".$date." ".$slot['start_time']);
                        }
                    }

                    if(count($slots) > 0){
                        $service['slots'] = $slots[0];
                    }
                }
            }
            array_push($scheduleservices, $service);
        }

        return $scheduleservices;
    }

    public function finderDetailApp($slug, $cache = true){

        $data   =  array();
        $tslug  = (string) strtolower($slug);

        $finder_detail = $cache ? Cache::tags('finder_detail_app')->has($tslug) : false;

        if(!$finder_detail){

            $finderarr = Finder::active()->where('slug','=',$tslug)
            ->with(array('category'=>function($query){$query->select('_id','name','slug','detail_rating');}))
            ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
            ->with('categorytags')
            ->with('locationtags')
            ->with('offerings')
            ->with('facilities')
            ->with(array('ozonetelno'=>function($query){$query->select('*')->where('status','=','1');}))
            ->with(array('services'=>function($query){$query->select('*')->with(array('category'=>function($query){$query->select('_id','name','slug');}))->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))->whereIn('show_on', array('1','3'))->where('status','=','1')->orderBy('ordering', 'ASC');}))
            ->with(array('reviews'=>function($query){$query->select('_id','finder_id','customer_id','rating','description','updated_at')->where('status','=','1')->with(array('customer'=>function($query){$query->select('_id','name','picture')->where('status','=','1');}))->orderBy('_id', 'DESC')->limit(1);}))
            ->first(array('_id','slug','title','lat','lon','category_id','category','location_id','location','city_id','city','categorytags','locationtags','offerings','facilities','coverimage','finder_coverimage','contact','average_rating','photos','info','manual_trial_enable'));

            //echo "<pre>";print_r($finderarr);exit;

            $finder = false;

            if($finderarr){
                $finderarr = $finderarr->toArray();

                $finder         =   array_except($finderarr, array('info','finder_coverimage','location_id','category_id','city_id','coverimage','findercollections','categorytags','locationtags','offerings','facilities','blogs'));
                $coverimage     =   ($finderarr['finder_coverimage'] != '') ? $finderarr['finder_coverimage'] : 'default/'.$finderarr['category_id'].'-'.rand(1, 19).'.jpg';
                array_set($finder, 'coverimage', $coverimage);

                $finder['info']              =   array_only($finderarr['info'], ['timing','delivery_timing']);

                $finder['today_opening_hour']   =   null;
                $finder['today_closing_hour']   =   null;
                $finder['open_close_hour_for_week'] = [];

                if(isset($finderarr['category_id']) && $finderarr['category_id'] == 5){

//                    return $finderarr['services'] ;
//                    return pluck( $finderarr['services'] , ['_id', 'name', 'trialschedules']);

                    if(isset($finderarr['services']) && count($finderarr['services']) > 0){
                        //for servcie category gym
                        $finder_gym_service  = [];
                        $finder_gym_service = head(array_where($finderarr['services'], function($key, $value){
                            if($value['category']['_id'] == 65){ return $value; }
                        }));

                        if(isset($finder_gym_service['trialschedules']) && count($finder_gym_service['trialschedules']) > 0){

//                            var_dump($finder_gym_service['trialschedules']); exit;

                            $all_weekdays                       =   $finder_gym_service['active_weekdays'];
                            $today_weekday                      =   strtolower(date( "l", time()));

                            foreach ($all_weekdays as $weekday){
                                $whole_week_open_close_hour_Arr             =   [];
                                $slots_start_time_24_hour_format_Arr        =   [];
                                $slots_end_time_24_hour_format_Arr          =   [];

                                $weekdayslots       =   head(array_where($finder_gym_service['trialschedules'], function($key, $value) use ($weekday){
                                    if($value['weekday'] == $weekday){
                                        return $value;
                                    }
                                }));// weekdayslots

                                if(isset($weekdayslots['slots']) && count($weekdayslots['slots']) > 0){
                                    foreach ($weekdayslots['slots'] as $key => $slot) {
                                        array_push($slots_start_time_24_hour_format_Arr, intval($slot['start_time_24_hour_format']));
                                        array_push($slots_end_time_24_hour_format_Arr, intval($slot['end_time_24_hour_format']));
                                    }
                                    if(!empty($slots_start_time_24_hour_format_Arr) && !empty($slots_end_time_24_hour_format_Arr)){
                                        $opening_hour = min($slots_start_time_24_hour_format_Arr);
                                        $closing_hour = max($slots_end_time_24_hour_format_Arr);
                                        if($today_weekday == $weekday){
                                         $finder['today_opening_hour'] =  date("g:i A", strtotime("$opening_hour:00"));
                                         $finder['today_closing_hour'] = date("g:i A", strtotime("$closing_hour:00"));
                                     }
                                     $whole_week_open_close_hour[$weekday]['opening_hour'] = date("g:i A", strtotime("$opening_hour:00"));
                                     $whole_week_open_close_hour[$weekday]['closing_hour'] = date("g:i A", strtotime("$closing_hour:00"));
                                     array_push($whole_week_open_close_hour_Arr, $whole_week_open_close_hour);
                                 }
                             }
                         }

                            //  $finder['open_close_hour_for_week'] = (!empty($whole_week_open_close_hour_Arr) && count($whole_week_open_close_hour_Arr) > 0) ? head($whole_week_open_close_hour_Arr) : null;

                            if(!empty($whole_week_open_close_hour_Arr) && count($whole_week_open_close_hour_Arr) > 0){
//                                var_dump($whole_week_open_close_hour_Arr);  exit;
                                $weekWiseArr                    =   [];
                                $whole_week_open_close_hour_Arr =   head($whole_week_open_close_hour_Arr);
                                $weekdayDays                    =   ["monday","tuesday","wednesday","thursday","friday","saturday","sunday"];
                                foreach ($weekdayDays as $day){
                                    if (array_key_exists($day, $whole_week_open_close_hour_Arr)) {
                                        $obj = ["day" => $day, "opening_hour" => $whole_week_open_close_hour_Arr[$day]["opening_hour"],  "closing_hour" => $whole_week_open_close_hour_Arr[$day]["closing_hour"]];
                                        array_push($weekWiseArr, $obj);
                                    }
                                }
                                $finder['open_close_hour_for_week'] = $weekWiseArr;
                            }else{
                                $finder['open_close_hour_for_week'] = [];
                            }

                        }// trialschedules

                    }
                }

                array_set($finder, 'services', pluck( $finderarr['services'] , ['_id', 'name', 'lat', 'lon', 'ratecards', 'serviceratecard', 'session_type', 'trialschedules', 'workoutsessionschedules', 'workoutsession_active_weekdays', 'active_weekdays', 'workout_tags', 'short_description', 'photos','service_trainer','timing','category','subcategory','batches','vip_trial','meal_type']  ));
                array_set($finder, 'categorytags', array_map('ucwords',array_values(array_unique(array_flatten(pluck( $finderarr['categorytags'] , array('name') ))))));
                array_set($finder, 'locationtags', array_map('ucwords',array_values(array_unique(array_flatten(pluck( $finderarr['locationtags'] , array('name') ))))));
                array_set($finder, 'offerings', array_map('ucwords',array_values(array_unique(array_flatten(pluck( $finderarr['offerings'] , array('name') ))))));
                array_set($finder, 'facilities', array_map('ucwords',array_values(array_unique(array_flatten(pluck( $finderarr['facilities'] , array('name') ))))));
                
                if(count($finder['services']) > 0 ){
                    $info_timing = $this->getInfoTiming($finder['services']);
                    if(isset($finder['info']) && $info_timing != ""){
                        $finder['info']['timing'] = $info_timing;
                    }
                    unset($finder['services']);
                }
                if($finderarr['category_id'] == 5){
                    $finder['type'] = "gyms";
                }elseif($finderarr['category_id'] == 42 || $finderarr['category_id'] == 45){
                    $finder['type'] = "healthytiffins";
                }elseif($finderarr['category_id'] == 41){
                    $finder['type'] = "personaltrainers";
                }else{
                    $finder['type'] = "fitnessstudios";
                }

                $finder['assured'] = [
                    ["icon" => "http://b.fitn.in/iconsv1/fitternity-assured/realtime-booking.png", "name" =>"Real-Time Booking"],
                    ["icon" => "http://b.fitn.in/iconsv1/fitternity-assured/service-fullfillment.png", "name" =>"100% Service Fulfillment"],
                    ["icon" => "http://b.fitn.in/iconsv1/fitternity-assured/lowest-price.png", "name" =>"Lowest Price"]
                ];

                $finder['review_count']     =   Review::active()->where('finder_id',$finderarr['_id'])->count();
                $finder['average_rating']   =   (isset($finder['average_rating']) && $finder['average_rating'] != "") ? round($finder['average_rating'],1) : 0;

                if(isset($finderarr['ozonetelno']) && $finderarr['ozonetelno'] != ''){
                    $finder['ozonetelno']['phone_number'] = '+'.$finder['ozonetelno']['phone_number'];
                    $finder['contact']['phone'] = $finder['ozonetelno']['phone_number'];
                    unset($finder['ozonetelno']);
                    unset($finder['contact']['website']);
                }
                $data['status']                         =       200;
                $data['finder']                         =       $finder;

                $data = Cache::tags('finder_detail_app')->put($tslug, $data, Config::get('cache.cache_time'));
               
            }

        }

        $finderData = Cache::tags('finder_detail_app')->get($tslug);

        if(count($finderData) > 0 && isset($finderData['status']) && $finderData['status'] == 200){

             $finder = Finder::active()->where('slug','=',$tslug)->first();

            if($finder){

                $finderData['finder']['services'] = $this->getTrialSchedule($finder->_id,$finder->category);
                $finderData['finder']['bookmark'] = false;
                $finderData['trials_detials']              =      [];
                $finderData['trials_booked_status']        =      false;

                if(Request::header('Authorization')){
                    $decoded                            =       decode_customer_token();
                    $customer_email                     =       $decoded->customer->email;
                    $customer_phone                     =       $decoded->customer->contact_no;
                    $customer_id                        =       $decoded->customer->_id;

                    $customer                           =       Customer::find((int)$customer_id);

                    if(isset($customer->bookmarks) && is_array($customer->bookmarks) && in_array($finder->_id,$customer->bookmarks)){
                        $finderData['finder']['bookmark'] = true;
                    }

                    $customer_trials_with_vendors       =       Booktrial::where(function ($query) use($customer_email, $customer_phone) { $query->where('customer_email', $customer_email)->orWhere('customer_phone', $customer_phone);})
                                                                ->where('finder_id', '=', (int) $finder->_id)
                                                                ->whereNotIn('going_status_txt', ["cancel","not fixed","dead"])
                                                                ->get(array('id'));

                    $finderData['trials_detials']              =      $customer_trials_with_vendors;
                    $finderData['trials_booked_status']        =      (count($customer_trials_with_vendors) > 0) ? true : false;
                }

            }
        }else{
            $finderData['status'] = 404;
        }

        return Response::json($finderData,$finderData['status']);
        
    }

}
