<?PHP namespace App\Services;

use \Log;
use \Order;

Class Salessummary {

    
    public function getRenewalNonRenewal($finder_id, $start_date, $end_date)
    {

        return Order::where('finder_id', $finder_id)
            ->createdBetween($start_date, $end_date)
            ->active();
    }


    public function getRenewal($finder_id, $start_date, $end_date)
    {

        return Order::where('finder_id', $finder_id)
            ->where('renewal', 'exists', true)
            ->where('renewal', '1')
            ->createdBetween($start_date, $end_date)
            ->active();
    }


    public function getNonRenewal($finder_id, $start_date, $end_date)
    {

        return Order::where('finder_id', $finder_id)
            ->where(function ($query) {
                $query
                    ->where('renewal', 'exists', false)
                    ->orWhere('renewal', '=', '0');
            })
            ->createdBetween($start_date, $end_date)
            ->active();
    }



    public function getPaymentGatewayCodAtthestudioSales($finder_id, $start_date, $end_date)
    {
        return Order::where('finder_id', $finder_id)
            ->whereIn('payment_mode', ['paymentgateway','cod','at the studio'])
            ->createdBetween($start_date, $end_date)
            ->active();
    }

    public function getPaymentGatewayCodSales($finder_id, $start_date, $end_date)
    {
        return Order::where('finder_id', $finder_id)
            ->whereIn('payment_mode', ['paymentgateway','cod'])
            ->createdBetween($start_date, $end_date)
            ->active();
    }

    public function getPaymentGatewaySales($finder_id, $start_date, $end_date)
    {

        return Order::where('finder_id', $finder_id)
            ->where('payment_mode', 'paymentgateway')
            ->createdBetween($start_date, $end_date)
            ->active();
    }


    public function getCODSales($finder_id, $start_date, $end_date)
    {
        return Order::where('finder_id', $finder_id)
            ->where('payment_mode', 'cod')
            ->createdBetween($start_date, $end_date)
            ->active();
    }


    public function getAtTheStudioSales($finder_id, $start_date, $end_date)
    {
        return Order::where('finder_id', $finder_id)
            ->where('payment_mode', 'at the studio')
            ->createdBetween($start_date, $end_date)
            ->active();
    }



    public function getLinkSentPurchase($finder_id, $start_date, $end_date)
    {
        return Order::where('finder_id', $finder_id)
            ->where('paymentLinkEmailCustomer', 'exists', true)
            ->createdBetween($start_date, $end_date)
            ->active();
    }

    public function getLinkSentNotPurchase($finder_id, $start_date, $end_date)
    {
        return Order::where('finder_id', $finder_id)
            ->where('paymentLinkEmailCustomer', 'exists', true)
            ->createdBetween($start_date, $end_date)
            ->where('status','0');
    }


}