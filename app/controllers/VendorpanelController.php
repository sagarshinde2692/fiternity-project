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
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Mailers\FinderMailer as FinderMailer;


// use \Order;
// use \Capture;
// use \Booktrial;
// use \Finder;



class VendorpanelController extends BaseController
{

    protected $jwtauth;
    protected $salessummary;
    protected $trialssummary;
    protected $ozonetelcallssummary;
    protected $reviewssummary;
    protected $statisticssummary;
    protected $customermailer;
    protected $findermailer;



    public function __construct(

        Jwtauth $jwtauth,
        Salessummary $salessummary,
        Trialssummary $trialssummary,
        Ozonetelcallsssummary $ozonetelcallsssummary,
        Reviewssummary $reviewssummary,
        Statisticssummary $statisticssummary,
        CustomerMailer $customermailer,
        FinderMailer $findermailer
    )
    {

        $this->jwtauth = $jwtauth;
        $this->salessummary = $salessummary;
        $this->trialssummary = $trialssummary;
        $this->ozonetelcallssummary = $ozonetelcallsssummary;
        $this->reviewssummary = $reviewssummary;
        $this->statisticssummary = $statisticssummary;
        $this->customermailer = $customermailer;
        $this->findermailer = $findermailer;
    }


    public function doVendorLogin()
    {

        $credentials = Input::json()->all();
        return $this->jwtauth->vendorLogin($credentials);
    }
    
    public function refreshWebToken()
    {
        return $this->jwtauth->refreshWebToken();
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
        $today_date     = date("d-m-Y", time());
        $start_date     = ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)) : $today_date;
        $end_date       = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

        $req = Input::all();
        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);
        $result = $this->salesListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset);
        return Response::json($result, 200);

    }


    private function salesListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset)
    {

        $result = [];

        switch ($type) {
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id', 'finder_id','created_at', 'renewal','payment_transfer')
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id','created_at')
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id', 'finder_id','created_at')
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id','created_at')
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id','created_at')
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id','created_at')
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id','created_at')
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id','created_at')
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
                        array('customer_email', 'customer_name', 'customer_phone', 'service_id', 'service_name', 'service_duration',
                            'amount_finder', 'payment_mode', 'booktrial_id','created_at')
                    );
                break;
            default:
                break;
        }

        foreach ($result['data'] as $row) {
            $row['membership_origin'] = ($row['customer_took_trial_before'] == 'yes') ? "post trial" : "direct";
            $row['purchase_mode'] = ($row['payment_mode'] == 'atthevendor') ? "direct" : "through fitternity";
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
        $end_date   = ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;


        $finder_id = intval($finder_id);
        $trials_summary = [
            'BookedTrials' => $this->trialssummary->getBookedTrials($finder_id, $start_date, $end_date)->count(),
            'AttendedTrials' => $this->trialssummary->getAttendedTrials($finder_id, $start_date, $end_date)->count(),
            'UpcomingTrials' => $this->trialssummary->getUpcomingTrials($finder_id)->count(),
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


    private function trialsListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset)
    {

        $result = [];

        switch ($type) {
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
                            'service_id', 'service_name', 'share_customer_no','created_at','trial_attended_finder')
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
                            'service_id', 'service_name', 'share_customer_no','created_at')
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
                            'service_id', 'service_name', 'share_customer_no','created_at')
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
                            'service_id', 'service_name', 'share_customer_no','created_at')
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
                            'service_id', 'service_name', 'share_customer_no','created_at')
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
                            'service_id', 'service_name', 'share_customer_no','created_at')
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


    private function OzonetelListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset)
    {

        $result = [];

        switch ($type) {

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
                            'created_at', 'customer_contact_circle', 'customer_contact_operator','created_at','aws_file_name')
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
                            'created_at', 'customer_contact_circle', 'customer_contact_operator','created_at','aws_file_name')
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
                            'created_at', 'customer_contact_circle', 'customer_contact_operator','created_at','aws_file_name')
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
                            'created_at', 'customer_contact_circle', 'customer_contact_operator','created_at','aws_file_name')
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
//            'WeeklyDiffInSales' => $this->statisticssummary->getWeeklyDiffInSales
//            ($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end),
//                'WeeklyDiffInSalesOnlineAndCOD' => $this->statisticssummary->getWeeklyDiffInSalesOnlineAndCOD
//                ($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end),
//                'WeeklyDiffInSalesAtVendor' => $this->statisticssummary->getWeeklyDiffInSalesAtVendor
//                ($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end)
        ];

        return Response::json($data, 200);
    }


    public function getTotalInquires($finder_id, $start_date = NULL, $end_date = NULL)
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
        $total_inquiries =
            Booktrial
                ::  where('finder_id', '=', intval($finder_id))
                ->createdBetween($start_date, $end_date)
                ->count()
            +
            Capture
                ::  where('finder_id', '=', intval($finder_id))
                ->createdBetween($start_date, $end_date)
                ->count()
            +
            Order::where('finder_id', intval($finder_id))
                ->createdBetween($start_date, $end_date)
                ->count();
        return Response::json($total_inquiries, 200);
    }


    public function getVendorsList(){

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        $data =  Finder::whereIn('_id', $finder_ids)->with('location')
            ->get(array('_id','slug','title','logo','location_id','location'));

        return Response::json($data, 200);

    }


    public function getVendorDetails($finder_id){

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $finder_id = intval($finder_id);

        $data =  Finder::where('_id', '=', $finder_id)
            ->with(array('location'=>function($query){$query->select('name','city');}))
            ->get(array('_id','slug','title','logo','location_id','location','total_photos'))
            ->first();

        $data['total_photos'] = $data['total_photos'] != '' ? intval($data['total_photos']) : 0;
        $data['logo'] =         Config::get("app.s3_finder_url") . 'l/' . $data['logo'];
        return Response::json($data, 200);

    }


    public function getContractualInfo($finder_id){

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor data']];
            return Response::json($data, 401);
        }
        $finder_id = intval($finder_id);

        $data =  Findercommercial::where('finder_id', '=', $finder_id)
            ->get(
                array(
                    '_id','aquired_person','aquired_date','contract_start_date',
                    'contract_end_date','contract_duration','commision','listing_fee'
                )
            )
            ->first();

        $data['contract_duration'] = $data['contract_duration'] . ' Months';
        $data['listing_fee'] = intval($data['listing_fee']);

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


    public function getRecentProfileUpdateRequest($finder_id)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor profile']];
            return Response::json($data, 401);
        }
        $finderdata = Vendorupdaterequest::where('finder_id', '=', intval($finder_id))
            ->where('approval_status', '=', 'pending')
            ->first();

        return Response::json($finderdata, 200);

    }


    public function modifyVisualization($finderdata){
        $keys = array_keys($finderdata);
        foreach ($keys as $key){
            if($finderdata[$key] != null){
                if( starts_With($key, 'existing') && $key != "existing"){
                    $finderdata['existing'][str_replace("existing","",$key)] = $finderdata[$key];
                    unset($finderdata[$key]);
                }
                if( starts_With($key, 'new') && $key != "new"){
                    $finderdata['new'][str_replace("new","",$key)] = $finderdata[$key];
                    unset($finderdata[$key]);
                }
            }
            else{
                unset($finderdata[$key]);
            }
        }
        return $finderdata;
    }


    public function updateProfile($finder_id)
    {

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401,'message' => ['error' => 'Unauthorized to update this vendor profile'] ];
            return  Response::json( $data, 401);
        }

        $req = Input::json()->all();

        $req_data = $req['data'];
        $req_visibility = $req['visibility'];


        $validator = Validator::make($req_data, Finder::$update_rules);

        if ($validator->fails()) {
            return Response::json(array('status' => 400,'message' =>error_message_array($validator->errors())),400);
        }

        $directly_editable_fields = array(
            "contact",
            "finder_vcc_email",
            "finder_vcc_mobile",
            "landmark",
            "facilities",
            "photos"
        );

        $fitternity_intervention_editable_fields = array(
            "info",
            "offerings",
            "category_id",
            "categorytags",
            "location_id",
            "locationtags",
            "trial_details"
        );

        $data_existing = $req_data['existing'];
        $visibility_existing = $req_visibility['existing'];

        // Get Directly editable fields by vendor.....
        $data_direct = array_only($req_data['new'], $directly_editable_fields);

        // Get Visibilty of Directly editable fields by vendor.....
        $visibility_direct = array_only($req_visibility['new'], $directly_editable_fields);

        // Get Finder....
        $finder = Finder::where('_id', '=', intval($finder_id))->with('location')->get()->first();

        // Update non-relational fields.....
        $relationalKeys = ['facilities'];
        $finder->update(array_except($data_direct, $relationalKeys));

        // Update relational fields.....
        if(isset($data_direct['facilities']) && is_array($data_direct['facilities'])){
            $finder->facilities()->sync($data_direct['facilities']);
        }

        // Get fields which needs fitternity approval to get updated.....
        $data_requested = array_only($req_data['new'], $fitternity_intervention_editable_fields);

        // Get visibilty of fields which needs fitternity approval to get updated.....
        $visibility_requested = array_only($req_visibility['new'], $fitternity_intervention_editable_fields);

        //Make data object for request....
        $data = array(
            "existing"  =>$data_existing,
            "direct"    =>$data_direct,
            "requested" =>$data_requested
        );

        // Make visibility object for request...
        $visibility = array(
            "existing"  =>$visibility_existing,
            "direct"    =>$visibility_direct,
            "requested" =>$visibility_requested
        );

        // Cancel already pending request.......
        Vendorupdaterequest:: where('finder_id',intval($finder_id))
            ->where('approval_status','pending')
            ->update(['approval_status' => 'cancelled']);


        // Save in a collection......
        $result = Vendorupdaterequest::firstOrCreate([
            'data' => $data,
            'visibility' => $visibility,
            'finder_id' => intval($finder_id),
            'approval_status' => 'pending'
        ]);


        // Modify data to display only modified fields in emails............
        $temp_direct =  array_diff(array_dot($visibility_direct),array_dot($visibility_existing));
        $temp_requested =  array_diff(array_dot($visibility_requested),array_dot($visibility_existing));


        $direct_data_email = array();
        $requested_data_email = array();

        foreach($temp_direct as $key => $value){

            $value = ucwords($value);
            $key_parts = explode('.', $key);

            switch($key_parts[0]){

                case 'facilities':
                    $direct_data_email['Facilities'] = isset($direct_data_email['Facilities']) ? $direct_data_email['Facilities']. ', '.$value : $value;
                    break;
                case 'finder_vcc_email':
                    $direct_data_email['Email'] = isset($direct_data_email['Email']) ? $direct_data_email['Email']. ', '.$value : $value;
                    break;
                case 'finder_vcc_mobile':
                    $direct_data_email['Mobile'] = isset($direct_data_email['Mobile']) ? $direct_data_email['Mobile']. ', '.$value : $value;
                    break;
                case 'photos':
                        $position = $key_parts[1];
                        $nested_key = $key_parts[2];

                        if(!isset($arr_photos)){
                            $arr_photos = array();
                        }
                        $arr_photos[$position][$nested_key] = $value;
                    break;
                default:
                    isset($key_parts[1]) ? $direct_data_email[ucwords($key_parts[1])] = $value : $direct_data_email[ucwords($key_parts[0])] = $value;
                    break;

            }
        }
        isset($arr_photos) ? $direct_data_email['Photos'] = $arr_photos : null;

        foreach($temp_requested as $key => $value){

            $value = ucwords($value);
            $key_parts = explode('.', $key);

            switch($key_parts[0]){

                case 'offerings':
                    $requested_data_email['Offerings'] = isset($requested_data_email['Offerings']) ? $requested_data_email['Offerings']. ', '.$value : $value;
                    break;
                case 'categorytags':
                    $requested_data_email['Categorytags'] = isset($requested_data_email['Categorytags']) ? $requested_data_email['Categorytags']. ', '.$value : $value;
                    break;
                case 'locationtags':
                    $requested_data_email['Locationtags'] = isset($requested_data_email['Locationtags']) ? $requested_data_email['Locationtags']. ', '.$value : $value;
                    break;
                default:
                    isset($key_parts[1]) ? $requested_data_email[ucwords($key_parts[1])] = $value : $requested_data_email[ucwords($key_parts[0])] = $value;
                    break;

            }
        }

        $template_data = array(
            'finder_vcc_email' => $finder['finder_vcc_email'],
            'finder_slug' => $finder['slug'],
            'finder_name' => $finder['title'],
            'location_name' => $finder['location']['name'],
            'direct_data_email' => $direct_data_email,
            'requested_data_email' => $requested_data_email
        );

//        return $template_data;

        // Email to vendor............
        $this->findermailer->VendorEmailOnProfileEditRequest($template_data);
        return $this->findermailer->RMEmailOnProfileEditRequest($template_data);
    }

    public function reviewReplyByVendor($finder_id, $review_id)
    {
        $data = Input::json()->all();

        if(empty($data['reply'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - reply");
            return  Response::json($resp, 400);
        }

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor profile']];
            return Response::json($data, 401);
        }

        $reviewData = Review::with(array('finder'=>function($query){$query->select('_id','title','slug');}))
                ->with(array('customer'=>function($query){$query->select('_id','name','email');}))
                ->where('_id','=',(int) $review_id)
                ->first();

        if($reviewData){

            // UPdate in DB....
            //ToDO correct replied_at timestamp..........
            $reviewData->update(
                array(
                    'reply' => $data['reply'],
                    'replied_at'=> date('Y-m-d h:i:s')
                )
            );

            // Notify Customer....
            $template_data = array(
                'customer_name' => $reviewData['customer']['name'],
                'customer_email' => $reviewData['customer']['email'],
                'finder_name' => $reviewData['finder']['title'],
                'reply' => $reviewData['reply'],
                'created_at' => $reviewData['created_at']
            );
            $this->customermailer->reviewReplyByVendor($template_data);

            // Send response....
            $resp 	= 	'Success';
            return  Response::json($resp, 200);

        }
        else{
            $resp 	= 	array('status' => 400,'message' => "Invalid Id");
            return  Response::json($resp, 400);
        }


    }


    public function updateTrialByVendor($finder_id, $trial_id)
    {
        $data = Input::json()->all();

        if(empty($data['trial_attended_finder'])){
            $resp 	= 	array('status' => 400,'message' => "Data Missing - Attended status");
            return  Response::json($resp, 400);
        }

        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor profile']];
            return Response::json($data, 401);
        }

        $trialData = Booktrial::find((int) $trial_id);

        if($trialData){
            //ToDO correct updated_by_vendor timestamp..........
            $trialData->update(
                array(
                    'trial_attended_finder' => $data['trial_attended_finder'],
                    'updated_by_vendor' => date('Y-m-d h:i:s'),
                ));
            $resp 	= 	'Success';
            return  Response::json($resp, 200);

        }
        else{
            $resp 	= 	array('status' => 400,'message' => "Invalid Trial Id");
            return  Response::json($resp, 400);
        }


    }


    public function gettrialdetail($booktrial_id){  


        if (empty($booktrial_id)) {

            $response = array('status' => 400,'message' =>"Book trial id is required");

        }else{
            
            
            // $finder_id = $data['finder_id'];
            // $service_id = $data['service_id'];
            // $schedule_slot = $data['slot'];
            // $schedule_date = $data['date'];
            // $reason = $data['reason'];

            // $schedule_start_date_time = new DateTime(date("d-m-Y 00:00:00", strtotime($schedule_date)));
            // $schedule_end_date_time = new DateTime(date("d-m-Y 00:00:00", strtotime($schedule_date."+ 1 days")));

            // $booktrial = Booktrial::where("finder_id",(int)$finder_id)->where("service_id",(int)$service_id)->where("schedule_slot",$schedule_slot)->where('schedule_date_time', '>=',$schedule_start_date_time)->where('schedule_date_time', '<=',$schedule_end_date_time)->get();

            // if(count($booktrial) > 0){

            //     foreach ($booktrial as $key => $value) {

            //         $this->cancel($value->_id,'vendor', $reason);
            //     }

            // }

            // $response = array('status' => 200,'message' =>'success');
        }

        return Response::json($response, $response['status']);

    }
}