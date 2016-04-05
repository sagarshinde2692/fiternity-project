<?PHP


/**
 * ControllerName : VendorpanelController.
 * Maintains a list of functions used for VendorpanelController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


use App\Services\Salessummary as Salessummary;


class VendorpanelController extends BaseController
{

    protected $salessummary;

    public function __construct(Salessummary $salessummary) {
        $this->salessummary		=	$salessummary;
    }


    public function getSummarySales($vendor_ids, $start_date = NULL, $end_date = NULL)
    {
        $finderSaleSummaryArr   = [];
        $finder_ids             =   explode(",",$vendor_ids);
        $today_date             =   date("d-m-Y", time());
        $start_date             =   ($start_date != NULL) ? date("d-m-Y", strtotime($start_date)): $today_date;
        $end_date               =   ($end_date != NULL) ? date("d-m-Y", strtotime($end_date)) : $today_date;
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




}