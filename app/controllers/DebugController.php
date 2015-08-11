<?PHP

/** 
 * ControllerName : DebugController.
 * Maintains a list of functions used for DebugController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class DebugController extends \BaseController {

	protected $worker;

	public function __construct() {

        $this->worker = new IronWorker(array(
		    'token' => Config::get('queue.connections.iron.token'),
    		'project_id' => Config::get('queue.connections.iron.project')
		));
    }

    

	//capture order status for customer
	public function ironWorker(){

		$taskid = Queue::pushRaw("This is Hello World payload :)", 'SampleWorkerClass');

		echo"<pre>";print_r($taskid);exit;

		$this->worker->postMessages('ExampleLaraWorker', array(
		        return $crypt->encrypt("This is Hello World payload_1"),
		        return $crypt->encrypt("This is Hello World payload_2")
		    )
		);

		//$taskID = $this->worker->postTask('HelloWorld');
		//echo"<pre>";print_r($taskID);exit;

		/*Queue::push(function fire($job){

			File::append(app.path().'/ironmq.txt',time().PHP_EOL);

			$job->delete();

		});*/
		
	}

	

}
