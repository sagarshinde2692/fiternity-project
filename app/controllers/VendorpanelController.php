<?PHP


/**
 * ControllerName : VendorpanelController.
 * Maintains a list of functions used for VendorpanelController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


use App\Services\Jwtauth as Jwtauth;
use App\Services\Salessummary as Salessummary;
use App\Services\Trialssummary as Trialssummary;
use App\Services\Ozonetelcallssummary as Ozonetelcallsssummary;
use App\Services\Reviewssummary as Reviewssummary;


class VendorpanelController extends BaseController
{

    protected $jwtauth;
    protected $salessummary;
    protected $trialssummary;
    protected $ozonetelcallssummary;
    protected $reviewssummary;

    public function __construct(Jwtauth $jwtauth, Salessummary $salessummary, Trialssummary $trialssummary, Ozonetelcallsssummary $ozonetelcallsssummary,Reviewssummary $reviewssummary) {
        $this->jwtauth		            =	$jwtauth;
        $this->salessummary		        =	$salessummary;
        $this->trialssummary		    =	$trialssummary;
        $this->ozonetelcallssummary	=	$ozonetelcallsssummary;
        $this->reviewssummary = $reviewssummary;
    
    }


    public function doVendorLogin(){

        $credentials    =   Input::json()->all();
        return $this->jwtauth->vendorLogin($credentials);
    }



//    public function getSummarySales($vendor_ids, $start_date = NULL, $end_date = NULL)
    public function getSummarySales($start_date = NULL, $end_date = NULL)
    {
        $jwt_token                  =   Request::header('Authorization');
        $decoded_token              =   $this->jwtauth->decodeTokenVendorPanel($jwt_token);
        $vendorArr                  =   $decoded_token['vendor'];
        $vendor_ids                 =   array_unique(array_merge([$vendorArr['vendor_id']], $vendorArr['vendors']));
        // print_r($vendor_ids);exit;

        $finderSaleSummaryArr       =   [];
        $finder_ids                 =   $vendor_ids;
        $today_date                 =   date("d-m-Y", time());
        $start_date                 =   ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)): $today_date;
        $end_date                   =   ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        //return "<br> today_date : $today_date  <br>  start_date : $start_date  <br> end_date : $end_date ";

        foreach ($finder_ids as $finder_id){
            $finderData     = [];
            $finder         = Finder::where('_id', '=', intval($finder_id))->get()->first();
            if (!$finder) {
                continue;
            }

            $finder_id                                              = intval($finder_id);
            $renewal_nonrenewal_count_amount                        = $this->salessummary->getRenewalNonRenewalCountAmount($finder_id, $start_date, $end_date);
            $renewal_count_amount                                   = $this->salessummary->getRenewalCountAmount($finder_id, $start_date, $end_date);
            $nonrenewal_count_amount                                = $this->salessummary->getNonRenewalCountAmount($finder_id, $start_date, $end_date);

            $paymentgateway_cod_atthestudio_count_amount            = $this->salessummary->getPaymentGatewayCodAtthestudioSalesCountAmount($finder_id, $start_date, $end_date);
            $paymentgateway_count_amount                            = $this->salessummary->getPaymentGatewaySalesCountAmount($finder_id, $start_date, $end_date);
            $cod_count_amount                                       = $this->salessummary->getCodSalesCountAmount($finder_id, $start_date, $end_date);
            $atthestudio_count_amount                               = $this->salessummary->getAtTheStudioSalesCountAmount($finder_id, $start_date, $end_date);

            $linksent_purchase_count_amount                         = $this->salessummary->getLinkSentPurchaseCountAmount($finder_id, $start_date, $end_date);
            $linksent_notpurchase_count_amount                      = $this->salessummary->getLinkSentNotPurchaseCountAmount($finder_id, $start_date, $end_date);

            $sales_summary = [
                'renewal_nonrenewal'                    =>  $renewal_nonrenewal_count_amount,
                'renewal'                               =>  $renewal_count_amount,
                'nonrenewal'                            =>  $nonrenewal_count_amount,
                'paymentgateway_cod_atthestudio'        =>  $paymentgateway_cod_atthestudio_count_amount,
                'paymentgateway'                        =>  $paymentgateway_count_amount,
                'cod'                                   =>  $cod_count_amount,
                'atthestudio'                           =>  $atthestudio_count_amount,
                'linksent_purchase'                     =>  $linksent_purchase_count_amount,
                'linksent_notpurchase'                  =>  $linksent_notpurchase_count_amount,
            ];

            array_set($finderData, 'finder_id', intval($finder_id));
            array_set($finderData, 'title', trim($finder->title));
            array_set($finderData, 'slug', trim($finder->slug));
            array_set($finderData, 'sales_summary', $sales_summary);

            array_push($finderSaleSummaryArr, $finderData);
        }

        return Response::json($finderSaleSummaryArr, 200);
    }


    public function getSummaryTrials($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $finderTrialSummaryArr = [];
        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        //return "<br> today_date : $today_date  <br>  start_date : $start_date  <br> end_date : $end_date ";


        foreach ($finder_ids as $finder_id) {
            $finderData = [];
            $finder = Finder::where('_id', '=', intval($finder_id))->get()->first();
            if (!$finder) {
                continue;
            }

            $finder_id = intval($finder_id);
            $trials_summary = [
                'BookedTrials' => $this->trialssummary->getBookedTrials($finder_id, $start_date, $end_date)->count(),
                'AttendedTrials' => $this->trialssummary->getAttendedTrials($finder_id, $start_date, $end_date)->count(),
                'NotAttendedTrials' => $this->trialssummary->getNotAttendedTrials($finder_id, $start_date, $end_date)->count(),
                'UnknownAttendedStatusTrials' => $this->trialssummary->getUnknownAttendedStatusTrials($finder_id, $start_date, $end_date)->count(),
                'TrialsConverted' => $this->trialssummary->getTrialsConverted($finder_id, $start_date, $end_date)->count(),
                'NotInterestedCustomers' => $this->trialssummary->getNotInterestedCustomers($finder_id, $start_date, $end_date)->count(),
            ];


            array_set($finderData, 'finder_id', intval($finder_id));
            array_set($finderData, 'title', trim($finder->title));
            array_set($finderData, 'slug', trim($finder->slug));
            array_set($finderData, 'trials_summary', $trials_summary);

            array_push($finderTrialSummaryArr, $finderData);
        }


        return Response::json($finderTrialSummaryArr, 200);
    }


    public function getSummaryOzonetelcalls($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $ResultArr = [];
        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        //return "<br> today_date : $today_date  <br>  start_date : $start_date  <br> end_date : $end_date ";


        foreach ($finder_ids as $finder_id) {
            $finderData = [];
            $finder = Finder::where('_id', '=', intval($finder_id))->get()->first();
            if (!$finder) {
                continue;
            }

            $finder_id = intval($finder_id);
            $ozonetel_calls_summary = $this->ozonetelcallssummary->getOzonetelcallsSummary($finder_id, $start_date, $end_date);


            array_set($finderData, 'finder_id', intval($finder_id));
            array_set($finderData, 'title', trim($finder->title));
            array_set($finderData, 'slug', trim($finder->slug));
            array_set($finderData, 'ozonetel_calls_summary', $ozonetel_calls_summary);

            array_push($ResultArr, $finderData);
        }


        return Response::json($ResultArr, 200);
    }


    public function getSummaryReviews($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $ResultArr = [];

        $req = Input::all();

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        $min_rating = isset($req['min_rating']) ? $req['min_rating'] : 0;
        $max_rating = isset($req['max_rating']) ? $req['max_rating'] : 5;
        //return "<br> today_date : $today_date  <br>  start_date : $start_date  <br> end_date : $end_date ";


        foreach ($finder_ids as $finder_id) {
            $finderData = [];
            $finder = Finder::where('_id', '=', intval($finder_id))->get()->first();
            if (!$finder) {
                continue;
            }

            $finder_id = intval($finder_id);
            $reviews_summary = $this->reviewssummary->getReviews($min_rating, $max_rating, $finder_id, $start_date, $end_date);


            array_set($finderData, 'finder_id', intval($finder_id));
            array_set($finderData, 'title', trim($finder->title));
            array_set($finderData, 'slug', trim($finder->slug));
            array_set($finderData, 'reviews_summary', $reviews_summary);

            array_push($ResultArr, $finderData);
        }


        return Response::json($ResultArr, 200);
    }


    public function listBookedTrials($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $BookedTrials = $this
                ->trialssummary
                ->getBookedTrials($finder_id, $start_date, $end_date)
                ->get(
                    array('booktrial_actions','booktrial_type','code','customer_email',
                    'customer_name','customer_phone','final_lead_stage','final_lead_status',
                    'going_status','going_status_txt','missedcall_batch','origin',
                    'premium_session','schedule_date','schedule_date_time','schedule_slot',
                    'service_id','service_name','share_customer_no')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'booked_trials', $BookedTrials);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }


    public function listAttendedTrials($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $AttendedTrials = $this
                ->trialssummary
                ->getAttendedTrials($finder_id, $start_date, $end_date)
                ->get(
                    array('booktrial_actions','booktrial_type','code','customer_email',
                    'customer_name','customer_phone','final_lead_stage','final_lead_status',
                    'going_status','going_status_txt','missedcall_batch','origin',
                    'premium_session','schedule_date','schedule_date_time','schedule_slot',
                    'service_id','service_name','share_customer_no')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'attended_trials', $AttendedTrials);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }


    public function listNotAttendedTrials($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $NotAttendedTrials = $this
                ->trialssummary
                ->getNotAttendedTrials($finder_id, $start_date, $end_date)
                ->get(
                    array('booktrial_actions','booktrial_type','code','customer_email',
                    'customer_name','customer_phone','final_lead_stage','final_lead_status',
                    'going_status','going_status_txt','missedcall_batch','origin',
                    'premium_session','schedule_date','schedule_date_time','schedule_slot',
                    'service_id','service_name','share_customer_no')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'not_attended_trials', $NotAttendedTrials);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }


    public function listUnknownAttendedStatusTrials($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $UnknownAttendedStatusTrials = $this
                ->trialssummary
                ->getUnknownAttendedStatusTrials($finder_id, $start_date, $end_date)
                ->get(
                    array('booktrial_actions','booktrial_type','code','customer_email',
                        'customer_name','customer_phone','final_lead_stage','final_lead_status',
                        'going_status','going_status_txt','missedcall_batch','origin',
                        'premium_session','schedule_date','schedule_date_time','schedule_slot',
                        'service_id','service_name','share_customer_no')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'unknown_attended_status_trials', $UnknownAttendedStatusTrials);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }


    public function listTrialsConverted($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $TrialsConverted = $this
                ->trialssummary
                ->getTrialsConverted($finder_id, $start_date, $end_date)
                ->get(
                    array('booktrial_actions','booktrial_type','code','customer_email',
                        'customer_name','customer_phone','final_lead_stage','final_lead_status',
                        'going_status','going_status_txt','missedcall_batch','origin',
                        'premium_session','schedule_date','schedule_date_time','schedule_slot',
                        'service_id','service_name','share_customer_no')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'trials_converted', $TrialsConverted);
            array_push($results, $doc);
        }

        return Response::json($results, 200);
    }


    public function listNotInterestedCustomers($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $NotInterestedCustomers = $this
                ->trialssummary
                ->getNotInterestedCustomers($finder_id, $start_date, $end_date)
                ->get(
                    array('booktrial_actions','booktrial_type','code','customer_email',
                        'customer_name','customer_phone','final_lead_stage','final_lead_status',
                        'going_status','going_status_txt','missedcall_batch','origin',
                        'premium_session','schedule_date','schedule_date_time','schedule_slot',
                        'service_id','service_name','share_customer_no')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'not_interested_customers', $NotInterestedCustomers);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }


    public function listAnsweredCalls($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        $call_status = 'answered';


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $finder         = Finder::where('_id', '=', $finder_id)->get()->first();
            if (!$finder) {
                continue;
            }

            $data = $this
                ->ozonetelcallssummary
                ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                ->get(
                    array('ozonetel_no','customer_contact_no','call_duration','extension','call_status',
                        'created_at','customer_contact_circle','customer_contact_operator')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'title', trim($finder->title));
            array_set($doc, 'slug', trim($finder->slug));
            array_set($doc, 'data', $data);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }
    
    public function listNotAnsweredCalls($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        $call_status = 'not_answered';


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $finder         = Finder::where('_id', '=', $finder_id)->get()->first();
            if (!$finder) {
                continue;
            }

            $data = $this
                ->ozonetelcallssummary
                ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                ->get(
                    array('ozonetel_no','customer_contact_no','call_duration','extension','call_status',
                        'created_at','customer_contact_circle','customer_contact_operator')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'title', trim($finder->title));
            array_set($doc, 'slug', trim($finder->slug));
            array_set($doc, 'data', $data);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }
    
    
    public function listCalledStatusCalls($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        $call_status = 'called';


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $finder         = Finder::where('_id', '=', $finder_id)->get()->first();
            if (!$finder) {
                continue;
            }

            $data = $this
                ->ozonetelcallssummary
                ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                ->get(
                    array('ozonetel_no','customer_contact_no','call_duration','extension','call_status',
                        'created_at','customer_contact_circle','customer_contact_operator')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'title', trim($finder->title));
            array_set($doc, 'slug', trim($finder->slug));
            array_set($doc, 'data', $data);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }


    public function listTotalCalls($vendor_ids, $start_date = NULL, $end_date = NULL)
    {

        $results = [];

        $finder_ids = explode(",", $vendor_ids);
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        foreach ($finder_ids as $finder_id) {

            $finder_id = intval($finder_id);
            $finder         = Finder::where('_id', '=', $finder_id)->get()->first();
            if (!$finder) {
                continue;
            }

            $data = $this
                ->ozonetelcallssummary
                ->getCallRecords($finder_id, $start_date, $end_date)
                ->get(
                    array('ozonetel_no','customer_contact_no','call_duration','extension','call_status',
                        'created_at','customer_contact_circle','customer_contact_operator')
                );

            array_set($doc, 'finder_id', intval($finder_id));
            array_set($doc, 'title', trim($finder->title));
            array_set($doc, 'slug', trim($finder->slug));
            array_set($doc, 'data', $data);
            array_push($results, $doc);
        }


        return Response::json($results, 200);
    }
}