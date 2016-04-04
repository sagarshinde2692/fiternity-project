<?PHP


class VendorpanelController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function getSummarySales($vendor_id, $start_date = NULL, $end_date = NULL)
    {

        $today_date     =   date("d-m-Y", time());
        $start_date     =   ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)): $today_date;
        $end_date       =   ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;

//        return "<br> today_date : $today_date  <br>  start_date : $start_date  <br> end_date : $end_date ";


        $finder = Finder::where('_id', '=', intval($vendor_id))->get()->first();
        if (!$finder) {
            return $this->responseNotFound('Finder does not exist');
        }

        $finder_id                                              = intval($finder->_id);
        $renewal_nonrenewal_count_amount                        = $this->getRenewalNonRenewalCountAmount($finder_id, $start_date, $end_date);
        $renewal_count_amount                                   = $this->getRenewalCountAmount($finder_id, $start_date, $end_date);
        $nonrenewal_count_amount                                = $this->getNonRenewalCountAmount($finder_id, $start_date, $end_date);

        $paymentgateway_cod_atthestudio_count_amount            = $this->getPaymentGatewayCodAtthestudioSalesCountAmount($finder_id, $start_date, $end_date);
        $paymentgateway_count_amount                            = $this->getPaymentGatewaySalesCountAmount($finder_id, $start_date, $end_date);
        $cod_count_amount                                       = $this->getCodSalesCountAmount($finder_id, $start_date, $end_date);
        $atthestudio_count_amount                               = $this->getAtTheStudioSalesCountAmount($finder_id, $start_date, $end_date);

        $linksent_purchase_count_amount                         = $this->getLinkSentPurchaseCountAmount($finder_id, $start_date, $end_date);
        $linksent_notpurchase_count_amount                      = $this->getLinkSentNotPurchaseCountAmount($finder_id, $start_date, $end_date);


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
        return Response::json($sales_summary, 200);
    }


    private function getRenewalNonRenewalCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count = Order::where('finder_id', $finder_id)->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount = Order::where('finder_id', $finder_id)->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $totalsales;
    }


    private function getRenewalCountAmount($finder_id, $start_date, $end_date)
    {

        $sales_count = Order::where('finder_id', $finder_id)->where('renewal', '1')->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount = Order::where('finder_id', $finder_id)->where('renewal', '1')->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $renewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $renewal_totalsales;
    }


    private function getNonRenewalCountAmount($finder_id, $start_date, $end_date)
    {

        $sales_count = Order::where('finder_id', $finder_id)
                            ->where(function ($query) {
                                $query->where('renewal', 'exists', false)->orWhere('renewal', '=', '0');
                            })->createdBetween($start_date, $end_date)->active()->count();

        $sales_amount = Order::where('finder_id', $finder_id)
                            ->where(function ($query) {
                                $query->where('renewal', 'exists', false)->orWhere('renewal', '=', '0');
                            })->createdBetween($start_date, $end_date)->active()->sum('amount_finder');

        $nowrenewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $nowrenewal_totalsales;
    }



    private function getPaymentGatewayCodAtthestudioSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->whereIn('payment_mode', ['paymentgateway','cod','at the studio'])->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->whereIn('payment_mode', ['paymentgateway','cod','at the studio'])->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $paymentgateway_cod_atthestudio_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $paymentgateway_cod_atthestudio_totalsales;
    }

    private function getPaymentGatewaySalesCountAmount($finder_id, $start_date, $end_date)
    {

        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'paymentgateway')->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'paymentgateway')->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $paymentgateway_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $paymentgateway_totalsales;
    }


    private function getCodSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'cod')->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'cod')->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $cod_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $cod_totalsales;
    }


    private function getAtTheStudioSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'at the studio')->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'at the studio')->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $atthestudio_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $atthestudio_totalsales;
    }



    private function getLinkSentPurchaseCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->where('paymentLinkEmailCustomer', 'exists', true)->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('paymentLinkEmailCustomer', 'exists', true)->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $nowrenewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $nowrenewal_totalsales;
    }

    private function getLinkSentNotPurchaseCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count = Order::where('finder_id', $finder_id)->where('paymentLinkEmailCustomer', 'exists', true)->createdBetween($start_date, $end_date)->where('status','0')->count();
        $sales_amount = Order::where('finder_id', $finder_id)->where('paymentLinkEmailCustomer', 'exists', true)->createdBetween($start_date, $end_date)->where('status','0')->sum('amount_finder');
        $nowrenewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $nowrenewal_totalsales;
    }


}