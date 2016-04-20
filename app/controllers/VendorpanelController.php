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
        $result = [];

        $result['renewal_nonrenewal']['count'] = $this
            ->salessummary
            ->getRenewalNonRenewal($finder_id, $start_date, $end_date)
            ->count();
        $result['renewal_nonrenewal']['amount'] = $this
            ->salessummary
            ->getRenewalNonRenewal($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        $result['renewal']['count'] = $this
            ->salessummary
            ->getRenewal($finder_id, $start_date, $end_date)
            ->count();
        $result['renewal']['amount'] = $this
            ->salessummary
            ->getRenewal($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        $result['nonrenewal']['count'] = $this
            ->salessummary
            ->getNonRenewal($finder_id, $start_date, $end_date)
            ->count();
        $result['nonrenewal']['amount'] = $this
            ->salessummary
            ->getNonRenewal($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        $result['paymentgateway_cod_atthestudio']['count'] = $this
            ->salessummary
            ->getPaymentGatewayCodAtthestudioSales($finder_id, $start_date, $end_date)
            ->count();
        $result['paymentgateway_cod_atthestudio']['amount'] = $this
            ->salessummary
            ->getPaymentGatewayCodAtthestudioSales($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        $result['paymentgateway']['count'] = $this
            ->salessummary
            ->getPaymentGatewaySales($finder_id, $start_date, $end_date)
            ->count();
        $result['paymentgateway']['amount'] = $this
            ->salessummary
            ->getPaymentGatewaySales($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        $result['cod']['count'] = $this
            ->salessummary
            ->getCODSales($finder_id, $start_date, $end_date)
            ->count();
        $result['cod']['amount'] = $this
            ->salessummary
            ->getCODSales($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        $result['atthestudio']['count'] = $this
            ->salessummary
            ->getAtthestudioSales($finder_id, $start_date, $end_date)
            ->count();
        $result['atthestudio']['amount'] = $this
            ->salessummary
            ->getAtthestudioSales($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        $result['linksent_purchase']['count'] = $this
            ->salessummary
            ->getLinkSentPurchase($finder_id, $start_date, $end_date)
            ->count();
        $result['linksent_purchase']['amount'] = $this
            ->salessummary
            ->getLinkSentPurchase($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        $result['linksent_notpurchase']['count'] = $this
            ->salessummary
            ->getLinkSentNotPurchase($finder_id, $start_date, $end_date)
            ->count();
        $result['linksent_notpurchase']['amount'] = $this
            ->salessummary
            ->getLinkSentNotPurchase($finder_id, $start_date, $end_date)
            ->sum('amount_finder');

        return Response::json($result, 200);
    }


    public function getSalesList($finder_id, $type, $start_date = NULL, $end_date = NULL)
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
        $result = $this->salesListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset);
        return Response::json($result, 200);

    }


    private function salesListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset){

        $result = [];

        switch ($type){
            case 'renewal_nonrenewal':
                $result['count'] = $this
                    ->salessummary
                    ->getRenewalNonRenewal($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getRenewalNonRenewal($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id','finder_id')
                    );
                break;
            case 'renewal':
                $result['count'] = $this
                    ->salessummary
                    ->getRenewal($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getRenewal($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id')
                    );
                break;
            case 'nonrenewal':
                $result['count'] = $this
                    ->salessummary
                    ->getNonRenewal($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getNonRenewal($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id','finder_id')
                    );
                break;
            case 'paymentgateway_cod_atthestudio':
                $result['count'] = $this
                    ->salessummary
                    ->getPaymentGatewayCodAtthestudioSales($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getPaymentGatewayCodAtthestudioSales($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id')
                    );
                break;
            case 'paymentgateway':
                $result['count'] = $this
                    ->salessummary
                    ->getPaymentGatewaySales($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getPaymentGatewaySales($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id')
                    );
                break;
            case 'cod':
                $result['count'] = $this
                    ->salessummary
                    ->getCodSales($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getCodSales($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id')
                    );
                break;
            case 'atthestudio':
                $result['count'] = $this
                    ->salessummary
                    ->getAtthestudioSales($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getAtthestudioSales($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id')
                    );
                break;
            case 'linksentpurchase':
                $result['count'] = $this
                    ->salessummary
                    ->getLinkSentPurchase($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getLinkSentPurchase($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id')
                    );
                break;
            case 'linksentnotpurchase':
                $result['count'] = $this
                    ->salessummary
                    ->getLinkSentNotPurchase($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
                    ->salessummary
                    ->getLinkSentNotPurchase($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('customer_email','customer_name','customer_phone','service_id','service_name','service_duration',
                            'amount_finder','payment_mode','booktrial_id')
                    );
                break;
            default:
                break;
        }

        foreach ($result['data'] as $row){
            $row['membership_origin'] = ($row['customer_took_trial_before'] == 'yes') ?  "post trial" : "direct";
            $row['purchase_mode'] = ($row['payment_mode'] == 'atthevendor') ?  "direct" : "through fitternity";
        }

        return $result;
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


    public function getTrialsList($finder_id, $type, $start_date = NULL, $end_date = NULL)
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
        $result = $this->trialsListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset);
        return Response::json($result, 200);

    }


    private function trialsListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset){

        $result = [];

        switch ($type){
            case 'booked':
                $result['count'] = $this
                    ->trialssummary
                    ->getBookedTrials($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
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
                break;
            case 'attended':
                $result['count'] = $this
                    ->trialssummary
                    ->getAttendedTrials($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
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
                break;
            case 'notattended':
                $result['count'] = $this
                    ->trialssummary
                    ->getNotAttendedTrials($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
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
                break;
            case 'unknownattendedstatus':
                $result['count'] = $this
                    ->trialssummary
                    ->getUnknownAttendedStatusTrials($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
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
                break;
            case 'converted':
                $result['count'] = $this
                    ->trialssummary
                    ->getTrialsConverted($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
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
                break;
            case 'notinterestedcustomers':
                $result['count'] = $this
                    ->trialssummary
                    ->getNotInterestedCustomers($finder_id, $start_date, $end_date)
                    ->count();
                $result['data'] = $this
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
                break;
            default:
                break;
        }

        return $result;
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


    public function getOzonetelList($finder_id, $type, $start_date = NULL, $end_date = NULL)
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
        $result = $this->ozonetelListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset);
        return Response::json($result, 200);

    }


    private function OzonetelListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset){

        $result = [];

        switch ($type){

            case 'total':
                $result['count'] = $this
                    ->ozonetelcallssummary
                    ->getCallRecords($finder_id, $start_date, $end_date)
                    ->count();

                $result['data'] = $this
                    ->ozonetelcallssummary
                    ->getCallRecords($finder_id, $start_date, $end_date)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('ozonetel_no', 'customer_contact_no', 'call_duration', 'extension', 'call_status',
                            'created_at', 'customer_contact_circle', 'customer_contact_operator')
                    );
                break;
            case 'answered':

                $call_status = 'answered';
                $result['count'] = $this
                    ->ozonetelcallssummary
                    ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                    ->count();

                $result['data'] = $this
                    ->ozonetelcallssummary
                    ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('ozonetel_no', 'customer_contact_no', 'call_duration', 'extension', 'call_status',
                            'created_at', 'customer_contact_circle', 'customer_contact_operator')
                    );
                break;
            case 'notanswered':

                $call_status = 'not_answered';

                $result['count'] = $this
                    ->ozonetelcallssummary
                    ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                    ->count();

                $result['data'] = $this
                    ->ozonetelcallssummary
                    ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('ozonetel_no', 'customer_contact_no', 'call_duration', 'extension', 'call_status',
                            'created_at', 'customer_contact_circle', 'customer_contact_operator')
                    );
                break;
            case 'called':

                $call_status = 'called';
                $result['count'] = $this
                    ->ozonetelcallssummary
                    ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                    ->count();

                $result['data'] = $this
                    ->ozonetelcallssummary
                    ->getCallRecords($finder_id, $start_date, $end_date, $call_status)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('ozonetel_no', 'customer_contact_no', 'call_duration', 'extension', 'call_status',
                            'created_at', 'customer_contact_circle', 'customer_contact_operator')
                    );
                break;
            default:
                break;
        }

        return $result;
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