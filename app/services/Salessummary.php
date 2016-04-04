<?PHP namespace App\Services;

use \Log;
use \Order;
use \Booktrial;

Class Salessummary {

    
    public function getRenewalNonRenewalCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count = Order::where('finder_id', $finder_id)->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount = Order::where('finder_id', $finder_id)->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $totalsales;
    }


    public function getRenewalCountAmount($finder_id, $start_date, $end_date)
    {

        $sales_count = Order::where('finder_id', $finder_id)->where('renewal', '1')->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount = Order::where('finder_id', $finder_id)->where('renewal', '1')->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $renewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $renewal_totalsales;
    }


    public function getNonRenewalCountAmount($finder_id, $start_date, $end_date)
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



    public function getPaymentGatewayCodAtthestudioSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->whereIn('payment_mode', ['paymentgateway','cod','at the studio'])->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->whereIn('payment_mode', ['paymentgateway','cod','at the studio'])->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $paymentgateway_cod_atthestudio_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $paymentgateway_cod_atthestudio_totalsales;
    }

    public function getPaymentGatewaySalesCountAmount($finder_id, $start_date, $end_date)
    {

        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'paymentgateway')->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'paymentgateway')->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $paymentgateway_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $paymentgateway_totalsales;
    }


    public function getCodSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'cod')->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'cod')->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $cod_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $cod_totalsales;
    }


    public function getAtTheStudioSalesCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->where('payment_mode', 'at the studio')->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('payment_mode', 'at the studio')->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $atthestudio_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $atthestudio_totalsales;
    }



    public function getLinkSentPurchaseCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count    = Order::where('finder_id', $finder_id)->where('paymentLinkEmailCustomer', 'exists', true)->createdBetween($start_date, $end_date)->active()->count();
        $sales_amount   = Order::where('finder_id', $finder_id)->where('paymentLinkEmailCustomer', 'exists', true)->createdBetween($start_date, $end_date)->active()->sum('amount_finder');
        $nowrenewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $nowrenewal_totalsales;
    }

    public function getLinkSentNotPurchaseCountAmount($finder_id, $start_date, $end_date)
    {
        $sales_count = Order::where('finder_id', $finder_id)->where('paymentLinkEmailCustomer', 'exists', true)->createdBetween($start_date, $end_date)->where('status','0')->count();
        $sales_amount = Order::where('finder_id', $finder_id)->where('paymentLinkEmailCustomer', 'exists', true)->createdBetween($start_date, $end_date)->where('status','0')->sum('amount_finder');
        $nowrenewal_totalsales = ['count' => $sales_count, 'amount' => $sales_amount];
        return $nowrenewal_totalsales;
    }


}