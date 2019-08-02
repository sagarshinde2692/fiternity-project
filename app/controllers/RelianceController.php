<?PHP

//use \Log;
use App\Services\RelianceService as RelianceService;
use App\Services\Utilities as Utilities;

class RelianceController extends \BaseController {
    
    public function __construct(RelianceService $relianceService, Utilities $utilities) {
      parent::__construct();
      $this->relianceService = $relianceService;
      $this->utilities = $utilities;
    }

    public function updateAppStepCount () {
        $data = Input::json()->all();
        $token = Request::header('Authorization');
        $device = Request::header('Device-Type');
        $version = Request::header('App-Version');
        Log::info('updateAppStepCount: ', $data);

        if(!empty($token)) {
          $custInfo = $this->utilities->customerTokenDecode($token);
        }

        $resp = $this->relianceService->updateAppStepCount($custInfo, $data, $device, $version);

        return  Response::json($resp, $resp['status']);
    }

    public function updateServiceStepCount () {
        $data = Input::json()->all();
        if(empty($data['admin_auth_key']) || $data['admin_auth_key'] != 'asdasdASDad21!SD32asd@a'){
            return;
        }
        Log::info('updateServiceStepCount: ', $data);
        $resp = $this->relianceService->updateServiceStepCount($data);
        return Response::json($resp, $resp['status']);
    }

    public function updateServiceStepCountJob ($job, $data) {
      if($job){
        $job->delete();
      }
      if(empty($data)) {
        return;
      }
      Log::info('updateServiceStepCountJob: ', $data);
      return $this->relianceService->updateServiceStepCount($data);
    }

    public function getLeaderboard() {
      $data = Input::json()->all();
      $token = Request::header('Authorization');
      $device = Request::header('Device-Type');
      $version = Request::header('App-Version');
      Log::info('updateAppStepCount: ', $data);

      if(!empty($token)) {
        $custInfo = $this->utilities->customerTokenDecode($token);
      }
      else{
        return  Response::json(['msg'=> "Invalid Request."], 400);
      }
      
      if(empty($custInfo->customer->external_reliance)){
        $filters = $this->relianceService->getLeaderboardFiltersList($data, (isset($custInfo->customer->external_reliance))?$custInfo->customer->external_reliance:null);
      }
      else{
        $data = $this->relianceService->getFilterForNonReliance($custInfo->customer->_id);
      }

      $isNewLeaderBoard = !empty($data['isNewLeaderBoard']) ? true: false;
      Log::info('is new leader board:::::', [$isNewLeaderBoard]);
      if(!empty($data['filters'])) {
        $parsedFilters = $this->relianceService->parseLeaderboardFilters($data['filters']);
        $resp = $this->relianceService->getLeaderboard($custInfo->customer->_id, $isNewLeaderBoard, $parsedFilters);
        $resp['data']['selected_filters'] = $data['filters'];
      }
      else {
        $resp = $this->relianceService->getLeaderboard($custInfo->customer->_id, $isNewLeaderBoard);
      }
      if(!empty($resp['data']) && $resp['data']!='Failed' && empty($custInfo->customer->external_reliance)) {
        $resp['data']['filters'] = $filters ;
      }
      return  Response::json($resp, $resp['status']);
    }

    public function storeDob(){

      $data = Input::json()->all();
      $token = Request::header('Authorization');

      if(!empty($token)) {
        $custInfo = $this->utilities->customerTokenDecode($token);
      }

      $rules = [
        'dob' => 'required|date',
      ];
      $validator = Validator::make($data,$rules);
      
      Log::info('custinf', [$custInfo->customer->_id]);
      if ($validator->fails() || empty($custInfo->customer->_id)) {
        return Response::json(array('status' => 400, 'message' => 'Invalid Input'));
      }

      return $this->relianceService->updatedCustomerDOB($custInfo->customer, $data);

    }

}

// 576012 - Unable to proceed this request, please try again later