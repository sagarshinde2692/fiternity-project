<?PHP


class VendorpanelController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function getSummarySales($vendor_id, $start_date = NULL, $end_date = NULL)
    {

        $start_date = ($start_date != NULL) ? $start_date : '';
        $end_date = ($end_date != NULL) ? $start_date : '';

        $finder = Finder::where('_id', '=', intval($vendor_id))->get()->first();
        if (!$finder) {
            return $this->responseNotFound('Finder does not exist');
        }

        $finder_id                                          = intval($finder->_id);
        $renewal_nonrenewal_count_amout                     = $this->getRenewalNonRenewalCountAmount($finder_id, $start_date, $end_date);
        $renewal_count_amout                                = $this->getRenewalCountAmount($finder_id, $start_date, $end_date);
        $nonrenewal_count_amout                             = $this->getNonRenewalCountAmount($finder_id, $start_date, $end_date);

        $paymentgateway_cod_atthestudio_count_amout         = $this->getPaymentGatewayCodAtthestudioSalesCountAmount($finder_id, $start_date, $end_date);
        $paymentgateway_count_amout                         = $this->getPaymentGatewaySalesCountAmount($finder_id, $start_date, $end_date);
        $cod_count_amout                                    = $this->getCodSalesCountAmount($finder_id, $start_date, $end_date);
        $atthestudio_count_amout                            = $this->getAtTheStudioSalesCountAmount($finder_id, $start_date, $end_date);

        $sales_summary = [
            'renewal_nonrenewal'                    =>  $renewal_nonrenewal_count_amout,
            'renewal'                               =>  $renewal_count_amout,
            'nonrenewal'                            =>  $nonrenewal_count_amout,
            'paymentgateway_cod_atthestudio'        =>  $paymentgateway_cod_atthestudio_count_amout,
            'paymentgateway'                        =>  $paymentgateway_count_amout,
            'cod'                                   =>  $cod_count_amout,
            'atthestudio'                           =>  $atthestudio_count_amout,
        ];
        return Response::json($sales_summary, 200);
    }


    private function getRenewalNonRenewalCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count = Order::where('finder_id', $finder_id)->active()->count();
        $sales_amount = Order::where('finder_id', $finder_id)->active()->sum('amount_finder');
        $totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $totalsales;
    }


    private function getRenewalCountAmount($finder_id, $start_date, $end_date)
    {

        $sales_count = Order::where('finder_id', $finder_id)->where('renewal', '1')->active()->count();
        $sales_amount = Order::where('finder_id', $finder_id)->where('renewal', '1')->active()->sum('amount_finder');
        $renewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $renewal_totalsales;
    }


    private function getNonRenewalCountAmount($finder_id, $start_date, $end_date)
    {

        $sales_count = Order::where('finder_id', $finder_id)
                            ->where(function ($query) {
                                $query->where('renewal', 'exists', false)->orWhere('renewal', '=', '0');
                            })->active()->count();

        $sales_amount = Order::where('finder_id', $finder_id)
                            ->where(function ($query) {
                                $query->where('renewal', 'exists', false)->orWhere('renewal', '=', '0');
                            })->active()->sum('amount_finder');

        $nowrenewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $nowrenewal_totalsales;
    }



    private function getPaymentGatewayCodAtthestudioSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->whereIn('payment_mode', ['paymentgateway','cod','at the studio'])->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->whereIn('payment_mode', ['paymentgateway','cod','at the studio'])->active()->sum('amount_finder');
        $paymentgateway_cod_atthestudio_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $paymentgateway_cod_atthestudio_totalsales;
    }

    private function getPaymentGatewaySalesCountAmount($finder_id, $start_date, $end_date)
    {

        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'paymentgateway')->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'paymentgateway')->active()->sum('amount_finder');
        $paymentgateway_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $paymentgateway_totalsales;
    }


    private function getCodSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'cod')->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'cod')->active()->sum('amount_finder');
        $cod_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $cod_totalsales;
    }


    private function getAtTheStudioSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'at the studio')->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'at the studio')->active()->sum('amount_finder');
        $atthestudio_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $atthestudio_totalsales;
    }






}