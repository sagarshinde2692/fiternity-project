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
use App\Services\Statisticssummary as Statisticssummary;


class VendorpanelController extends BaseController
{

    protected $jwtauth;
    protected $salessummary;
    protected $trialssummary;
    protected $ozonetelcallssummary;
    protected $reviewssummary;
    protected $statisticssummary;


    public function __construct(

        Jwtauth $jwtauth,
        Salessummary $salessummary,
        Trialssummary $trialssummary,
        Ozonetelcallsssummary $ozonetelcallsssummary,
        Reviewssummary $reviewssummary,
        Statisticssummary $statisticssummary)
    {

        $this->jwtauth = $jwtauth;
        $this->salessummary = $salessummary;
        $this->trialssummary = $trialssummary;
        $this->ozonetelcallssummary = $ozonetelcallsssummary;
        $this->reviewssummary = $reviewssummary;
        $this->statisticssummary = $statisticssummary;
    }


    public function doVendorLogin()
    {

        $credentials = Input::json()->all();
        return $this->jwtauth->vendorLogin($credentials);
    }


    public function getSummarySales($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }

        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        $finder_id = intval($finder_id);
        $renewal_nonrenewal_count_amount = $this->salessummary->getRenewalNonRenewalCountAmount($finder_id, $start_date, $end_date);
        $renewal_count_amount = $this->salessummary->getRenewalCountAmount($finder_id, $start_date, $end_date);
        $nonrenewal_count_amount = $this->salessummary->getNonRenewalCountAmount($finder_id, $start_date, $end_date);

        $paymentgateway_cod_atthestudio_count_amount = $this->salessummary->getPaymentGatewayCodAtthestudioSalesCountAmount($finder_id, $start_date, $end_date);
        $paymentgateway_count_amount = $this->salessummary->getPaymentGatewaySalesCountAmount($finder_id, $start_date, $end_date);
        $cod_count_amount = $this->salessummary->getCodSalesCountAmount($finder_id, $start_date, $end_date);
        $atthestudio_count_amount = $this->salessummary->getAtTheStudioSalesCountAmount($finder_id, $start_date, $end_date);

        $linksent_purchase_count_amount = $this->salessummary->getLinkSentPurchaseCountAmount($finder_id, $start_date, $end_date);
        $linksent_notpurchase_count_amount = $this->salessummary->getLinkSentNotPurchaseCountAmount($finder_id, $start_date, $end_date);

        $sales_summary = [
            'renewal_nonrenewal' => $renewal_nonrenewal_count_amount,
            'renewal' => $renewal_count_amount,
            'nonrenewal' => $nonrenewal_count_amount,
            'paymentgateway_cod_atthestudio' => $paymentgateway_cod_atthestudio_count_amount,
            'paymentgateway' => $paymentgateway_count_amount,
            'cod' => $cod_count_amount,
            'atthestudio' => $atthestudio_count_amount,
            'linksent_purchase' => $linksent_purchase_count_amount,
            'linksent_notpurchase' => $linksent_notpurchase_count_amount,
        ];

        return Response::json($sales_summary, 200);
    }


    public function getSummaryTrials($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        $finder_id = intval($finder_id);
        $trials_summary = [
            'BookedTrials' => $this->trialssummary->getBookedTrials($finder_id, $start_date, $end_date)->count(),
            'AttendedTrials' => $this->trialssummary->getAttendedTrials($finder_id, $start_date, $end_date)->count(),
            'NotAttendedTrials' => $this->trialssummary->getNotAttendedTrials($finder_id, $start_date, $end_date)->count(),
            'UnknownAttendedStatusTrials' => $this->trialssummary->getUnknownAttendedStatusTrials($finder_id, $start_date, $end_date)->count(),
            'TrialsConverted' => $this->trialssummary->getTrialsConverted($finder_id, $start_date, $end_date)->count(),
            'NotInterestedCustomers' => $this->trialssummary->getNotInterestedCustomers($finder_id, $start_date, $end_date)->count(),
        ];
        return Response::json($trials_summary, 200);
    }


    public function getSummaryOzonetelcalls($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        $finder_id = intval($finder_id);
        $ozonetel_calls_summary = $this->ozonetelcallssummary->getOzonetelcallsSummary($finder_id, $start_date, $end_date);

        return Response::json($ozonetel_calls_summary, 200);
    }


    public function getSummaryReviews($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $req = Input::all();
        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        $min_rating = isset($req['min_rating']) ? $req['min_rating'] : 0;
        $max_rating = isset($req['max_rating']) ? $req['max_rating'] : 5;
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $reviews_summary = $this
            ->reviewssummary
            ->getReviews($min_rating, $max_rating, $finder_id, $start_date, $end_date, $limit, $offset);


        return Response::json($reviews_summary, 200);
    }


    public function listBookedTrials($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];
        $count = $this
            ->trialssummary
            ->getBookedTrials($finder_id, $start_date, $end_date)
            ->count();
        $data = $this
            ->trialssummary
            ->getBookedTrials($finder_id, $start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('booktrial_actions', 'booktrial_type', 'code', 'customer_email',
                    'customer_name', 'customer_phone', 'final_lead_stage', 'final_lead_status',
                    'going_status', 'going_status_txt', 'missedcall_batch', 'origin',
                    'premium_session', 'schedule_date', 'schedule_date_time', 'schedule_slot',
                    'service_id', 'service_name', 'share_customer_no')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function listAttendedTrials($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];
        $count = $this
            ->trialssummary
            ->getAttendedTrials($finder_id, $start_date, $end_date)
            ->count();
        $data = $this
            ->trialssummary
            ->getAttendedTrials($finder_id, $start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('booktrial_actions', 'booktrial_type', 'code', 'customer_email',
                    'customer_name', 'customer_phone', 'final_lead_stage', 'final_lead_status',
                    'going_status', 'going_status_txt', 'missedcall_batch', 'origin',
                    'premium_session', 'schedule_date', 'schedule_date_time', 'schedule_slot',
                    'service_id', 'service_name', 'share_customer_no')
            );
        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function listNotAttendedTrials($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];
        $count = $this
            ->trialssummary
            ->getNotAttendedTrials($finder_id, $start_date, $end_date)
            ->count();
        $data = $this
            ->trialssummary
            ->getNotAttendedTrials($finder_id, $start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('booktrial_actions', 'booktrial_type', 'code', 'customer_email',
                    'customer_name', 'customer_phone', 'final_lead_stage', 'final_lead_status',
                    'going_status', 'going_status_txt', 'missedcall_batch', 'origin',
                    'premium_session', 'schedule_date', 'schedule_date_time', 'schedule_slot',
                    'service_id', 'service_name', 'share_customer_no')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function listUnknownAttendedStatusTrials($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];
        $count = $this
            ->trialssummary
            ->getUnknownAttendedStatusTrials($finder_id, $start_date, $end_date)
            ->count();
        $data = $this
            ->trialssummary
            ->getUnknownAttendedStatusTrials($finder_id, $start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('booktrial_actions', 'booktrial_type', 'code', 'customer_email',
                    'customer_name', 'customer_phone', 'final_lead_stage', 'final_lead_status',
                    'going_status', 'going_status_txt', 'missedcall_batch', 'origin',
                    'premium_session', 'schedule_date', 'schedule_date_time', 'schedule_slot',
                    'service_id', 'service_name', 'share_customer_no')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function listTrialsConverted($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];
        $count = $this
            ->trialssummary
            ->getTrialsConverted($finder_id, $start_date, $end_date)
            ->count();
        $data = $this
            ->trialssummary
            ->getTrialsConverted($finder_id, $start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('booktrial_actions', 'booktrial_type', 'code', 'customer_email',
                    'customer_name', 'customer_phone', 'final_lead_stage', 'final_lead_status',
                    'going_status', 'going_status_txt', 'missedcall_batch', 'origin',
                    'premium_session', 'schedule_date', 'schedule_date_time', 'schedule_slot',
                    'service_id', 'service_name', 'share_customer_no')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function listNotInterestedCustomers($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];
        $count = $this
            ->trialssummary
            ->getNotInterestedCustomers($finder_id, $start_date, $end_date)
            ->count();
        $data = $this
            ->trialssummary
            ->getNotInterestedCustomers($finder_id, $start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('booktrial_actions', 'booktrial_type', 'code', 'customer_email',
                    'customer_name', 'customer_phone', 'final_lead_stage', 'final_lead_status',
                    'going_status', 'going_status_txt', 'missedcall_batch', 'origin',
                    'premium_session', 'schedule_date', 'schedule_date_time', 'schedule_slot',
                    'service_id', 'service_name', 'share_customer_no')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function listAnsweredCalls($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        $call_status = 'answered';

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];
        $count = $this
            ->ozonetelcallssummary
            ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
            ->count();

        $data = $this
            ->ozonetelcallssummary
            ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('ozonetel_no', 'customer_contact_no', 'call_duration', 'extension', 'call_status',
                    'created_at', 'customer_contact_circle', 'customer_contact_operator')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }

    public function listNotAnsweredCalls($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        $call_status = 'not_answered';

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];
        $count = $this
            ->ozonetelcallssummary
            ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
            ->count();
        $data = $this
            ->ozonetelcallssummary
            ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('ozonetel_no', 'customer_contact_no', 'call_duration', 'extension', 'call_status',
                    'created_at', 'customer_contact_circle', 'customer_contact_operator')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function listCalledStatusCalls($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
        $call_status = 'called';

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];

        $count = $this
            ->ozonetelcallssummary
            ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
            ->count();
        $data = $this
            ->ozonetelcallssummary
            ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('ozonetel_no', 'customer_contact_no', 'call_duration', 'extension', 'call_status',
                    'created_at', 'customer_contact_circle', 'customer_contact_operator')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function listTotalCalls($finder_id, $start_date = NULL, $end_date = NULL)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $today_date = date("d-m-Y", time());
        $start_date = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = [];

        $count = $this
            ->ozonetelcallssummary
            ->getCallRecords($finder_id, $start_date, $end_date)
            ->count();
        $data = $this
            ->ozonetelcallssummary
            ->getCallRecords($finder_id, $start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(
                array('ozonetel_no', 'customer_contact_no', 'call_duration', 'extension', 'call_status',
                    'created_at', 'customer_contact_circle', 'customer_contact_operator')
            );

        $result['count'] = $count;
        $result['data'] = $data;

        return Response::json($result, 200);
    }


    public function getSummaryStatistics($finder_id, $date = NULL)
    {


        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $date = ($date != NULL) ? date("Y-m-d", strtotime($date)) : strtotime("today");


        $previous_week = strtotime("-1 week +1 day", $date);
        $previous_week_start = strtotime("last sunday midnight", $previous_week);
        $previous_week_end = strtotime("next saturday", $previous_week_start);
        $previous_week_start = date("Y-m-d", $previous_week_start);
        $previous_week_end = date("Y-m-d", $previous_week_end);

        $current_week_start = strtotime("last sunday midnight", $date);
        $current_week_end = strtotime("next saturday", $date);
        $current_week_start = date("Y-m-d", $current_week_start);
        $current_week_end = date("Y-m-d", $current_week_end);


        $finder_id = intval($finder_id);
        $data = [
            'WeeklyDiffInTrials' => $this->statisticssummary->getWeeklyDiffInTrials
            ($finder_id, $previous_week_start, $previous_week_end, $current_week_start, $current_week_end),
            'WeeklyDiffInLeads' => $this->statisticssummary->getWeeklyDiffInLeads
            ($finder_id, $previous_week_start, $previous_week_end, $current_week_start, $current_week_end),
//                'WeeklyDiffInSales' => $this->statisticssummary->getWeeklyDiffInSales
//                ($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end),
//                'WeeklyDiffInSalesOnlineAndCOD' => $this->statisticssummary->getWeeklyDiffInSalesOnlineAndCOD
//                ($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end),
//                'WeeklyDiffInSalesAtVendor' => $this->statisticssummary->getWeeklyDiffInSalesAtVendor
//                ($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end)
        ];

        return Response::json($data, 200);
    }


    public function profile($finder_id)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor profile']];
            return Response::json($data, 401);
        }
        $finderdata = Finder::where('_id', '=', intval($finder_id))
            ->with(array('category' => function ($query) {
                $query->select('_id', 'name', 'slug');
            }))
            ->with(array('city' => function ($query) {
                $query->select('_id', 'name', 'slug');
            }))
            ->with(array('location' => function ($query) {
                $query->select('_id', 'name', 'slug');
            }))
            ->with('categorytags')
            ->with('locationtags')
            ->with('offerings')
            ->with('facilities')
            ->with(array('ozonetelno' => function ($query) {
                $query->select('*')->where('status', '=', '1');
            }))
            ->first();

        $finder['_id'] = $finderdata['_id'];
        $finder['category'] = $finderdata['category'];
        $finder['city'] = $finderdata['city'];
        $finder['location'] = $finderdata['location'];
        $finder['ozonetelno'] = $finderdata['ozonetelno'];

        $finderdata = $finderdata->toArray();

        array_set($finder, 'categorytags', pluck($finderdata['categorytags'], array('_id', 'name', 'slug', 'offering_header')));
        array_set($finder, 'locationtags', pluck($finderdata['locationtags'], array('_id', 'name', 'slug')));
        array_set($finder, 'offerings', pluck($finderdata['offerings'], array('_id', 'name', 'slug')));
        array_set($finder, 'facilities', pluck($finderdata['facilities'], array('_id', 'name', 'slug')));

        return Response::json($finder, 200);
    }

    public function updateProfile($finder_id)
    {

//        $finder_ids = $this->jwtauth->vendorIdsFromToken();
//
//        if (!(in_array($finder_id, $finder_ids))) {
//            $data = ['status_code' => 401,'message' => ['error' => 'Unauthorized to update this vendor profile'] ];
//            return  Response::json( $data, 401);
//        }
//
//        $data = Input::json()->all();
//        $validator = Validator::make($data, Finder::$update_rules);
//
//        if ($validator->fails()) {
//            return Response::json(array('status' => 400,'message' =>error_message_array($validator->errors())),400);
//        }
//
//        $finder = Finder::where('_id', '=', intval($finder_id))->get()->first();
//        $finder->update($data);
//        return Response::json($finder, 200);
    }
}