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

        $resp = ['status'=>400, 'msg'=>'failed'];
        // if(!empty($device) && (($device == 'ios' && $version <= '5.1.9') || ($device == 'android' && $version <= '5.27'))) {
          $resp = $this->relianceService->uploadStepsFirebase($custInfo, $data, $device, $version, $token);
          if(!empty($resp)) {
            $resp = ['status'=>200, 'data' =>$resp, 'msg'=> 'success'];
          }
          else {
            $resp = ['status'=>400, 'data' =>$resp, 'msg'=> 'failed'];
          }
        // }
        return  Response::json($resp, $resp['status']);  
    }

    public function updateServiceStepCount () {
        $data = Input::json()->all();
        if(empty($data['admin_auth_key']) || $data['admin_auth_key'] != 'asdasdASDad21!SD32asd@a'){
            return;
        }
        Log::info('updateServiceStepCount: ', $data);
        // $resp = $this->relianceService->updateServiceStepCount($data);
        $resp = $this->relianceService->uploadServiceStepsToFirebase($data);
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
      // if(!empty($device) && (($device == 'ios' && $version <= '5.1.9') || ($device == 'android' && $version <= '5.27'))) {
        $firebaseResponse = ($this->relianceService->getFirebaseLeaderboard($token, $device, $version));
        if(!empty($firebaseResponse)) {
          return Response::json($firebaseResponse);
        }
        else {
          return Response::json(['msg'=> "Invalid Request."], 400);;
        }
      // }
      // return $abc;
      // if(empty($custInfo->customer->external_reliance)){
      //   $filters = $this->relianceService->getLeaderboardFiltersList($data, (isset($custInfo->customer->external_reliance))?$custInfo->customer->external_reliance:null);
      // }
      // else{
      //   $data = $this->relianceService->getFilterForNonReliance($custInfo->customer->_id);
      // }

      // $isNewLeaderBoard = !empty($data['isNewLeaderBoard']) ? true: false;
      // Log::info('is new leader board:::::', [$isNewLeaderBoard]);
      // if(!empty($data['filters'])) {
      //   $parsedFilters = $this->relianceService->parseLeaderboardFilters($data['filters']);
      //   $resp = $this->relianceService->getLeaderboard($custInfo->customer->_id, $isNewLeaderBoard, $parsedFilters, null, $device, $version);
      //   $resp['data']['selected_filters'] = $data['filters'];
      // }
      // else {
      //   $resp = $this->relianceService->getLeaderboard($custInfo->customer->_id, $isNewLeaderBoard, null, null, $device, $version);
      // }
      // if(!empty($resp['data']) && $resp['data']!='Failed' && empty($custInfo->customer->external_reliance)) {
      //   $resp['data']['filters'] = $filters ;
      // }
      // return  Response::json($resp, $resp['status']);
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

    public function buildHealthObjectStructure() {
      $data = Input::all();
      $token = Request::header('Authorization');
      $deviceType = Request::header('Device-Type');
      $appVersion = Request::header('App-Version');

      if(!empty($token)) {
        $custInfo = $this->utilities->customerTokenDecode($token);
      }

      $rules = [
        'dob' => 'required|date',
      ];
      Validator::make($data,$rules);

      Log::info('custinf', [$custInfo->customer->_id]);

      $customerDetails = $this->relianceService->getCustomerDetails($custInfo->customer->_id);
      $result = $this->relianceService->getHealthObject($customerDetails, $custInfo, $data, $deviceType, $appVersion);
      
      if(!!$customerDetails && empty($customerDetails['corporate_id'])) {
        return Response::json(array('status' => 400, 'message' => 'Not applicable for new customers.'));  
      }
      
      if(!empty($result)) {
        return Response::json(array('status' => 200, 'data' => $result, 'message' => 'success'));
      }
      
      return Response::json(array('status' => 400, 'message' => 'Invalid Input'));

    }

}

// 576012 - Unable to proceed this request, please try again later