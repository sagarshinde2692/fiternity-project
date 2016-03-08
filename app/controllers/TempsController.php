<?PHP

/**
 * ControllerName : TempsController.
 * Maintains a list of functions used for TempsController.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class TempsController extends \BaseController {

    public function __construct() {
        parent::__construct();
    }


    public function add(){

        try{

            $data = Input::json()->all();

            $temp = new Temp($data);
            $temp->save();

            $response =  array('status' => 200,'message'=>'Added Successfull');

        }catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            
            Log::error($e);       
        }

        return Response::json($response,$response['status']); 
        
    }

    public function delete($mobile){

        if(isset($mobile) && $mobile != '')
        {

            $temp = Temp::where('mobile',$mobile)->delete();

            $response  =   array('status' => 200,'message' => "Deleted Successfull");

        }else{

            $response  =   array('status' => 400,'message' => "mobile is required or empty");
        }

        
        return Response::json($response,$response['status']); 

    }

}
