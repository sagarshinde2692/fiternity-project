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
        $content_type = Request::header('Content-type');

        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);


        if($content_type == 'text/csv'){
            $headers = [
                'Content-type'        => 'text/csv'
                ,   'Content-Disposition' => 'attachment; filename=Sales.csv'
            ];

            $csv = "NAME, EMAIL, NUMBER, TYPE, SERVICE MEMBERSHIP, DURATION, AMOUNT, PAYMENT TRANSFERRED \n";
            $result = $this->salesListHelper($finder_id, $type, $start_date, $end_date, 50000, 0);
            foreach ($result['data'] as $key => $value) {
                $csv .= (isset($value['customer_name']) && $value['customer_name'] !="") ? str_replace(',', '|', $value['customer_name']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['customer_email']) && $value['customer_email'] !="") ? str_replace(',', '|', $value['customer_email']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['customer_phone']) && $value['customer_phone'] !="") ? str_replace(',', '|', $value['customer_phone']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['membership_origin']) && $value['membership_origin'] !="") ? str_replace(',', '|', $value['membership_origin']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['service_name']) && $value['service_name'] !="") ? str_replace(',', '|', $value['service_name']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['service_duration']) && $value['service_duration'] !="") ? str_replace(',', '|', $value['service_duration']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['amount_finder']) && $value['amount_finder'] !="") ? str_replace(',', '|', $value['amount_finder']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['payment_transfer']) && $value['payment_transfer'] !="") ? str_replace(',', '|', $value['payment_transfer']) : "-";
                $csv .= " \n";
            }

            return Response::make(rtrim($csv, "\n"), 200, $headers);
        }else{
            $result = $this->salesListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset);
            return Response::json($result, 200);
        }

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
        $content_type = Request::header('Content-type');

        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);

        if($content_type == 'text/csv'){
            $headers = [
                'Content-type'        => 'text/csv'
                ,   'Content-Disposition' => 'attachment; filename=Trials.csv'
            ];

            $csv = "NAME, REQUEST DATE, TRIAL DATE, SLOT, SERVICE, POST TRIAL STATUS, ATTENDED STATUS \n";
            $result = $this->trialsListHelper($finder_id, $type, $start_date, $end_date, 50000, 0);
            foreach ($result['data'] as $key => $value) {
                $csv .= (isset($value['customer_name']) && $value['customer_name'] !="") ? str_replace(',', '|', $value['customer_name']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['created_at']) && $value['created_at'] !="") ? str_replace(',', '|', $value['created_at']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['schedule_date']) && $value['schedule_date'] !="") ? str_replace(',', '|', $value['schedule_date']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['schedule_slot']) && $value['schedule_slot'] !="") ? str_replace(',', '|', $value['schedule_slot']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['service_name']) && $value['service_name'] !="") ? str_replace(',', '|', $value['service_name']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['going_status_txt']) && $value['going_status_txt'] !="") ? str_replace(',', '|', $value['going_status_txt']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['trial_attended_finder']) && $value['trial_attended_finder'] !="") ? str_replace(',', '|', $value['trial_attended_finder']) : "-";
                $csv .= " \n";
            }

            return Response::make(rtrim($csv, "\n"), 200, $headers);
        }else{
            $result = $this->trialsListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset);
            return Response::json($result, 200);
        }


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
                            'service_id', 'service_name', 'share_customer_no','created_at','trial_attended_finder','finder_id')
                    );
                break;
            case 'upcoming':
                $result['count'] = $this
                    ->trialssummary
                    ->getUpcomingTrials($finder_id)
                    ->count();
                $result['data'] = $this
                    ->trialssummary
                    ->getUpcomingTrials($finder_id)
                    ->take($limit)
                    ->skip($offset)
                    ->get(
                        array('booktrial_actions', 'booktrial_type', 'code', 'customer_email',
                            'customer_name', 'customer_phone', 'final_lead_stage', 'final_lead_status',
                            'going_status', 'going_status_txt', 'missedcall_batch', 'origin',
                            'premium_session', 'schedule_date', 'schedule_date_time', 'schedule_slot',
                            'service_id', 'service_name', 'share_customer_no','created_at','trial_attended_finder','finder_id')
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
                            'service_id', 'service_name', 'share_customer_no','created_at','finder_id')
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
                            'service_id', 'service_name', 'share_customer_no','created_at','finder_id')
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
                            'service_id', 'service_name', 'share_customer_no','created_at','finder_id')
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
                            'service_id', 'service_name', 'share_customer_no','created_at','finder_id')
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
                            'service_id', 'service_name', 'share_customer_no','created_at','finder_id')
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
        $content_type = Request::header('Content-type');

        $limit = isset($req['limit']) ? $req['limit'] : 10;
        $offset = isset($req['offset']) ? $req['offset'] : 0;

        $finder_id = intval($finder_id);

        if($content_type == 'text/csv'){
            $headers = [
                'Content-type'        => 'text/csv'
                ,   'Content-Disposition' => 'attachment; filename=OzonetelLogs.csv'
            ];

            $csv = "Number, Operator, Circle, Datetime, Duration, Status \n";
            $result = $this->ozonetelListHelper($finder_id, $type, $start_date, $end_date, 50000, 0);
            foreach ($result['data'] as $key => $value) {
                $csv .= (isset($value['customer_contact_no']) && $value['customer_contact_no'] !="") ? str_replace(',', '|', $value['customer_contact_no']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['customer_contact_operator']) && $value['customer_contact_operator'] !="") ? str_replace(',', '|', $value['customer_contact_operator']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['customer_contact_circle']) && $value['customer_contact_circle'] !="") ? str_replace(',', '|', $value['customer_contact_circle']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['created_at']) && $value['created_at'] !="") ? str_replace(',', '|', $value['created_at']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['call_duration']) && $value['call_duration'] !="") ? str_replace(',', '|', $value['call_duration']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['call_status']) && $value['call_status'] !="") ? str_replace(',', '|', $value['call_status']) : "-";
                $csv .= " \n";
            }

            return Response::make(rtrim($csv, "\n"), 200, $headers);
        }else{
            $result = $this->ozonetelListHelper($finder_id, $type, $start_date, $end_date, $limit, $offset);
            return Response::json($result, 200);
        }

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

        $req = Input::json()->all();
        $content_type = Request::header('Content-type');
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



        if($content_type == 'text/csv'){
            $headers = [
                'Content-type'        => 'text/csv'
                ,   'Content-Disposition' => 'attachment; filename=Reviews.csv'
            ];

            $csv = "NAME, DATETIME, RATING, REVIEW, REPLY \n";
            $reviews_summary = $this
                ->reviewssummary
                ->getReviews($min_rating, $max_rating, $finder_id, $start_date, $end_date, 50000, 0);
//            return $reviews_summary;exit();
            foreach ($reviews_summary['data'] as $key => $value) {
                $csv .= (isset($value['customer']['name']) && $value['customer']['name'] !="") ? str_replace(',', '|', $value['customer']['name']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['created_at']) && $value['created_at'] !="") ? str_replace(',', '|', $value['created_at']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['rating']) && $value['rating'] !="") ? str_replace(',', '|', $value['rating']) : "-";
                $csv .= ", ";
//                if((is_array($value['finder']['category']['detail_rating']) && is_array($value['detail_rating']))){
//                    $detail_rating = array_combine($value['finder']['category']['detail_rating'], $value['detail_rating']);
//                }
//
//                foreach ($detail_rating as $key=>$value){
//                    $csv .= (isset($key) && $key !="") ? str_replace(',', '|', $key) : "";
//                    $csv .= (isset($value) && $value !="") ? str_replace(',', '|', $value) : "";
//
//
//                }
//                $csv .= ", ";
                $csv .= (isset($value['description']) && $value['description'] !="") ? str_replace(',', '|', $value['description']) : "-";
                $csv .= ", ";
                $csv .= (isset($value['reply']) && $value['reply'] !="") ? str_replace(',', '|', $value['reply']) : "-";
                $csv .= " \n";
            }

            return Response::make(rtrim($csv, "\n"), 200, $headers);
        }else{
            $reviews_summary = $this
                ->reviewssummary
                ->getReviews($min_rating, $max_rating, $finder_id, $start_date, $end_date, $limit, $offset);
            return Response::json($reviews_summary, 200);
        }
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
            ->get(array('_id','slug','title','logo','location_id','location','created_at'));

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
            ->with(array('location'=>function($query){$query->select('name','city');}))->with('reviews')
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

        if(!isset($data['trial_attended_finder']) || $data['trial_attended_finder'] == ""){
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





    // App Specific APIs.................
    public function dashboard($finder_id){

        $data = Input::json()->all();
        $result = $ozonetel = $UpcomingTrials = array();
        $finder_ids = $this->jwtauth->vendorIdsFromToken();

        if (!(in_array($finder_id, $finder_ids))) {
            $data = ['status_code' => 401, 'message' => ['error' => 'Unauthorized to access this vendor profile']];
            return Response::json($data, 401);
        }

        $aggregate = $this->getOzonetelaggregate($finder_id);
        $ozonetel = Ozonetelcapture::raw(function($collection) use ($aggregate){
            return $collection->aggregate($aggregate);
        });

        $SalesAggregate = $this->getSalesAggregate($finder_id);
        $sale = Order::raw(function($collection) use ($SalesAggregate){
            return $collection->aggregate($SalesAggregate);
        });

        $Trialaggregate = $this->getTrialaggregate($finder_id);
        $trials = Booktrial::raw(function($collection) use ($Trialaggregate){
            return $collection->aggregate($Trialaggregate);
        });

        $UpcomingTrialaggregate = $this->getUpcomingTrialaggregate($finder_id);
        $upcomingTrials = Booktrial::raw(function($collection) use ($UpcomingTrialaggregate){
            return $collection->aggregate($UpcomingTrialaggregate);
        });


        $result['ozonetel'] = count($ozonetel['result']) > 0 ? $ozonetel['result'] : array("weeks"=>0, "months"=>0, "today"=>0);
        $result['trials'] = count($trials['result']) > 0 ? $trials['result'] : array("weeks"=>0, "months"=>0, "today"=>0);
        $result['upcomingTrials'] = count($upcomingTrials['result']) > 0 ? $upcomingTrials['result'] : array("weeks"=>0, "months"=>0, "today"=>0);
        $result['sale'] = count($sale['result']) > 0 ? $sale['result'] : array("weeks"=>0, "months"=>0, "today"=>0);

        return Response::json($result);


    }


    public function getUpcomingTrialaggregate($finder_id){

        $upcomingWeek = (new DateTime())->modify('this Sunday')->format('Y-m-d h:i:s');

        $from = (new DateTime())->modify('last month')->format('Y-m-d h:i:s');
        $tomorrow = (new DateTime())->modify('tomorrow')->format('Y-m-d h:i:s');
        $from = new MongoDate(strtotime(date('Y-m-d h:i:s', strtotime($from))));
        $tomorrow = new MongoDate(strtotime(date('Y-m-d h:i:s', strtotime($tomorrow))));

        $today = new MongoDate();
        $upcomingWeek = new MongoDate(strtotime(date('Y-m-d h:i:s', strtotime($upcomingWeek))));

        $match['$match']['schedule_date_time']['$gte'] = $today;
        $match['$match']['schedule_date_time']['$lte'] = $upcomingWeek;
        $match['$match']['finder_id'] = (int) $finder_id;

        $project_today['$and'] = array(
            array('$gte'=>array('$schedule_date_time',$today)),
            array('$lte'=>array('$schedule_date_time',$tomorrow))
        );$project_week['$and'] = array(
            array('$gte'=>array('$schedule_date_time',$today)),
            array('$lte'=>array('$schedule_date_time',$upcomingWeek))
        );

        $project['$project']['today']['$cond'] = array($project_today,1,0);
        $project['$project']['upcomingWeek']['$cond'] = array($project_week,1,0);

        $group = array(
            '$group' => array(
                '_id' => null,
                'weeks' => array(
                    '$sum' => '$upcomingWeek'
                ),'today' => array(
                    '$sum' => '$today'
                )
            )
        );
        $aggregate = [];
        $aggregate[] = $match;
        $aggregate[] = $project;
        $aggregate[] = $group;

        return $aggregate;
    }

    public function getOzonetelaggregate($finder_id){

        $today = date("Y-m-d 00:00:00", time());
        $month = (new DateTime())->modify('first day of this month')->format('Y-m-d');
        $week = (new DateTime())->modify('this week')->format('Y-m-d');

        $from = (new DateTime())->modify('last month')->format('Y-m-d');
        $tomorrow = (new DateTime())->modify('tomorrow')->format('Y-m-d');

        $from = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($from))));
        $tomorrow = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($tomorrow))));
        $today = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($today))));
        $month = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($month))));
        $week = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($week))));

        $match['$match']['created_at']['$gte'] = $from;
        $match['$match']['created_at']['$lte'] = $today;
        $match['$match']['finder_id'] = (int) $finder_id;

        $project_today['$and'] = array(
            array('$gte'=>array('$created_at',$today)),
            array('$lte'=>array('$created_at',$tomorrow))
        );$project_week['$and'] = array(
            array('$gte'=>array('$created_at',$week)),
            array('$lte'=>array('$created_at',$tomorrow))
        );$project_month['$and'] = array(
            array('$gte'=>array('$created_at',$month)),
            array('$lte'=>array('$created_at',$tomorrow))
        );

        $project['$project']['today']['$cond'] = array($project_today,1,0);
        $project['$project']['week']['$cond'] = array($project_week,1,0);
        $project['$project']['month']['$cond'] = array($project_month,1,0);

        $group = array(
            '$group' => array(
                '_id' => null,
                'weeks' => array(
                    '$sum' => '$week'
                ),'months' => array(
                    '$sum' => '$month'
                ),'today' => array(
                    '$sum' => '$today'
                )
            )
        );
        $aggregate = [];
        $aggregate[] = $match;
        $aggregate[] = $project;
        $aggregate[] = $group;

        return $aggregate;
    }

    public function getSalesAggregate($finder_id){

        $today = date("Y-m-d 00:00:00", time());
        $month = (new DateTime())->modify('first day of this month')->format('Y-m-d');
        $week = (new DateTime())->modify('this week')->format('Y-m-d');

        $from = (new DateTime())->modify('last month')->format('Y-m-d');
        $tomorrow = (new DateTime())->modify('tomorrow')->format('Y-m-d');

        $from = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($from))));
        $tomorrow = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($tomorrow))));
        $today = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($today))));
        $month = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($month))));
        $week = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($week))));

        $match['$match']['created_at']['$gte'] = $from;
        $match['$match']['created_at']['$lte'] = $today;
        $match['$match']['finder_id'] = (int) $finder_id;

        $project_today['$and'] = array(
            array('$gte'=>array('$created_at',$today)),
            array('$lte'=>array('$created_at',$tomorrow))
        );$project_week['$and'] = array(
            array('$gte'=>array('$created_at',$week)),
            array('$lte'=>array('$created_at',$tomorrow))
        );$project_month['$and'] = array(
            array('$gte'=>array('$created_at',$month)),
            array('$lte'=>array('$created_at',$tomorrow))
        );

        $project['$project']['today']['$cond'] = array($project_today,'$amount',0);
        $project['$project']['week']['$cond'] = array($project_week,'$amount',0);
        $project['$project']['month']['$cond'] = array($project_month,'$amount',0);

        $group = array(
            '$group' => array(
                '_id' => null,
                'weeks' => array(
                    '$sum' => '$week'
                ),'months' => array(
                    '$sum' => '$month'
                ),'today' => array(
                    '$sum' => '$today'
                )
            )
        );
        $aggregate = [];
        $aggregate[] = $match;
        $aggregate[] = $project;
        $aggregate[] = $group;

        return $aggregate;
    }

    public function getTrialaggregate($finder_id){

        $today = date("Y-m-d 00:00:00", time());
        $month = (new DateTime())->modify('first day of this month')->format('Y-m-d');
        $week = (new DateTime())->modify('this week')->format('Y-m-d');

        $from = (new DateTime())->modify('last month')->format('Y-m-d');
        $tomorrow = (new DateTime())->modify('tomorrow')->format('Y-m-d');

        $from = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($from))));
        $tomorrow = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($tomorrow))));
        $today = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($today))));
        $month = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($month))));
        $week = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($week))));

        $match['$match']['schedule_date_time']['$gte'] = $from;
        $match['$match']['schedule_date_time']['$lte'] = $today;
        $match['$match']['finder_id'] = (int) $finder_id;

        $project_today['$and'] = array(
            array('$gte'=>array('$schedule_date_time',$today)),
            array('$lte'=>array('$schedule_date_time',$tomorrow))
        );$project_week['$and'] = array(
            array('$gte'=>array('$schedule_date_time',$week)),
            array('$lte'=>array('$schedule_date_time',$tomorrow))
        );$project_month['$and'] = array(
            array('$gte'=>array('$schedule_date_time',$month)),
            array('$lte'=>array('$schedule_date_time',$tomorrow))
        );

        $project['$project']['today']['$cond'] = array($project_today,1,0);
        $project['$project']['week']['$cond'] = array($project_week,1,0);
        $project['$project']['month']['$cond'] = array($project_month,1,0);

        $group = array(
            '$group' => array(
                '_id' => null,
                'weeks' => array(
                    '$sum' => '$week'
                ),'months' => array(
                    '$sum' => '$month'
                ),'today' => array(
                    '$sum' => '$today'
                )
            )
        );
        $aggregate = [];
        $aggregate[] = $match;
        $aggregate[] = $project;
        $aggregate[] = $group;

        return $aggregate;
    }


}