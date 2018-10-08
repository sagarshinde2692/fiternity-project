<?PHP

use App\Sms\FinderSms as FinderSms;
use Log;

class ThirdPartyController extends \BaseController {

    protected $findersms;

	public function __construct(FinderSms $findersms) {
		parent::__construct();
        $this->findersms          =   $findersms;
    }
    
    public function sendClockUserDaySMS () {
        $data = Input::json()->all();
        Log::info('$data: ', [$data]);
        $bookTrialData = Booktrial::where('_id', intval($data["booktrial_id"]))->get(['customer_name', 'finder_name', 'vendor_code']);
        Log::info('$bookTrialData: ', [$bookTrialData]);
        
        $smsdata = [
            "customer_name" => $bookTrialData[0]["customer_name"],
            "finder_name" => $bookTrialData[0]["finder_name"],
            "vendor_code" => $bookTrialData[0]["vendor_code"]
        ];

        // $findersms = new FinderSms();
        $res = $this->findersms->common("ClockDayVendor", $data['vendor_nos'], $smsdata);

        Log::info('$res: ', [$res]);

        if(!!$res)
            return ['status'=>1, 'response'=>'S'];
        else
            return ['status'=>0, 'response'=>'F'];
    }
}