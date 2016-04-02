<?PHP



class VendorpanelController extends BaseController {

	public function __construct() {
		parent::__construct();	
	}




	public function getSummarySales($vendor_id, $start_date = NULL, $end_date = NULL){

        $finder = 	Finder::where('_id', '=', intval($vendor_id))->get()->first();
        if(!$finder){
            return $this->responseNotFound('Finder does not exist');
        }

        $tolal_sales_count_amout                =   $this->getTotalSalesCountAmount($finder);
        $renewal_tolal_sales_count_amout        =   $this->getRenewalTotalSalesCountAmount($finder);
        $nonrenewal_tolal_sales_count_amout     =   $this->getNonRenewalTotalSalesCountAmount($finder);

        $sales_summary 	= 	[
            'totalsales'                =>      $tolal_sales_count_amout,
            'renewal_totalsales'        =>      $renewal_tolal_sales_count_amout,
            'nonrenewal_totalsales'     =>      $nonrenewal_tolal_sales_count_amout,
        ];
        return Response::json($sales_summary,200);
    }



    private function getTotalSalesCountAmount($finder){

        $finder_id      =   intval($finder->_id);
        $sales_count    =   Order::where('finder_id', $finder_id)->count();
        $sales_amount   =   Order::where('finder_id', $finder_id)->sum('amount_finder');

        $totalsales     =   ['count' => $sales_count, 'amount' => $sales_amount];
        return $totalsales;
    }


    private function getRenewalTotalSalesCountAmount($finder){

        $finder_id      =   intval($finder->_id);
        $sales_count    =   Order::where('finder_id', $finder_id)->where('renewal', '1')->count();
        $sales_amount   =   Order::where('finder_id', $finder_id)->where('renewal', '1')->sum('amount_finder');

        $renewal_totalsales     =   ['count' => $sales_count, 'amount' => $sales_amount];
        return $renewal_totalsales;
    }

    
    private function getNonRenewalTotalSalesCountAmount($finder){

        $finder_id      =   intval($finder->_id);
        $sales_count    =   Order::where('finder_id', $finder_id)->where('renewal', '1')->count();
        $sales_amount   =   Order::where('finder_id', $finder_id)->where('renewal', '1')->sum('amount_finder');

        $nowrenewal_totalsales     =   ['count' => $sales_count, 'amount' => $sales_amount];
        return $nowrenewal_totalsales;
    }










}																																																																																																																																																																																																																																																																										