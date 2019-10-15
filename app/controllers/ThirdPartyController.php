<?PHP

use App\Sms\FinderSms as FinderSms;
use \Log;
use App\Services\Utilities as Utilities;

class ThirdPartyController extends \BaseController {

    protected $findersms;

	public function __construct(FinderSms $findersms, Utilities $utilities) {
		parent::__construct();
        $this->findersms          =   $findersms;
        $this->utilities          =   $utilities;
    }
    
    public function sendClockUserDaySMS () {
        $data = Input::json()->all();
        Log::info('$data: ', [$data]);
        $smsdata = [];
        if(isset($data['booktrial_id'])){
            $bookTrialData = Booktrial::where('_id', intval($data["booktrial_id"]))->get(['customer_name', 'finder_name', 'vendor_code', 'third_party_details']);
            Log::info('$bookTrialData: ', [$bookTrialData]);
            
            $smsdata = [
                "customer_name" => $bookTrialData[0]["customer_name"],
                "finder_name" => $bookTrialData[0]["finder_name"],
                "vendor_code" => $bookTrialData[0]["vendor_code"],
                "third_party_details" => $bookTrialData[0]["third_party_details"],
            ];
        }
        else {
            $finderDetails = Finder::where('_id', intval($data['finder_id']))->get(['title']);
            if(empty($finderDetails) || empty($finderDetails[0])){
                Log::info('sendClockUserDaySMS - finder details not found: ', [$data['finder_id']]);
                return ['status'=>0, 'response'=>'F'];
            }
            $smsdata = [
                "vendor_code" => random_numbers(5),
                "finder_name" => $finderDetails[0]['title'],
                "customer_name" => $data['customer_name']
            ];
        }
        // $findersms = new FinderSms();
        $res = $this->findersms->common("ClockDayVendor", $data['vendor_nos'], $smsdata);

        Log::info('$res: ', [$res]);

        if(!!$res)
            return ['status'=>1, 'code' => $smsdata['vendor_code'], 'response'=>'S'];
        else
            return ['status'=>0, 'response'=>'F'];
    }
    public function decryptQRCode() {
        $data = Input::json()->all();
        Log::info('$data: ', [$data]);
        return Response::json([
            'status' => 200,
            'data' => json_decode(preg_replace('/[\x00-\x1F\x7F]/', '', $this->utilities->decryptQr($data['code'], Config::get('app.core_key'))),true),
            'message' => 'Success'
        ]);
    }
}