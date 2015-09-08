<?PHP

/** 
 * ControllerName : StatsController.
 * Maintains a list of functions used for StatsController.
 *
 * @author Mahesh Jadhav
 */

class StatsController extends \BaseController {

	protected $days;

	public function __construct() {

		$this->days = 21;

	}

	public function booktrial(){

		$day = $this->days;
		$data = array();
		for ($i=1; $i <= $day ; $i++) {
			$count = 0;
			$day_month = date('Y-m-d', strtotime("-".$i." days"));
			$to_day = $i-1;
			
			$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$i." days"))));
			$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$to_day." days"))));

			$count = Booktrial::whereBetween('created_at',array($from_date,$to_date))->count();
			$data[$day_month] = $count;
		}

		$data = array_reverse($data);

		$return = array(
					"x_axis" => array(
						"type"=>"datetime",
						   "labels"=>array_keys($data)
						),
						"series" => array(
								array(
									"data"=>array_values($data)
								)
						)
				);

		return json_encode($return);

	}

	public function signUp(){

		$day = $this->days;
		$data = array();
		for ($i=1; $i <= $day ; $i++) {
			$count = 0;
			$day_month = date('Y-m-d', strtotime("-".$i." days"));
			$to_day = $i-1;
			
			$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$i." days"))));
			$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$to_day." days"))));

			$count = Customer::whereBetween('created_at',array($from_date,$to_date))->count();
			$data[$day_month] = $count;
		}

		$data = array_reverse($data);

		$return = array(
					"x_axis" => array(
						"type"=>"datetime",
						   "labels"=>array_keys($data)
						),
						"series" => array(
								array(
									"data"=>array_values($data)
								)
						)
				);

		return json_encode($return);

	}

	public function orders(){

		$day = $this->days;
		$data = array();
		for ($i=1; $i <= $day ; $i++) {
			$count = 0;
			$day_month = date('Y-m-d', strtotime("-".$i." days"));
			$to_day = $i-1;
			
			$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$i." days"))));
			$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$to_day." days"))));

			$count = Order::whereBetween('created_at',array($from_date,$to_date))->count();
			$data[$day_month] = $count;
		}

		$data = array_reverse($data);

		$return = array(
					"x_axis" => array(
						"type"=>"datetime",
						   "labels"=>array_keys($data)
						),
						"series" => array(
								array(
									"data"=>array_values($data)
								)
						)
				);

		return json_encode($return);

	}


	public function callBack(){

		$day = $this->days;
		$data = array();
		for ($i=1; $i <= $day ; $i++) {
			$count = 0;
			$day_month = date('Y-m-d', strtotime("-".$i." days"));
			$to_day = $i-1;
			
			$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$i." days"))));
			$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$to_day." days"))));

			$count = Capture::whereBetween('created_at',array($from_date,$to_date))->where('capture_type', '=', 'request_callback')->count();
			$data[$day_month] = $count;
		}

		$data = array_reverse($data);

		$return = array(
					"x_axis" => array(
						"type"=>"datetime",
						   "labels"=>array_keys($data)
						),
						"series" => array(
								array(
									"data"=>array_values($data)
								)
						)
				);

		return json_encode($return);

	}

	public function ordersPieChart(){

		$from_day = $this->days;
		$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$from_day." days"))));
		$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("+1 days"))));

		$success = Order::whereBetween('created_at',array($from_date,$to_date))->where('status', '=', '1')->count();

		$failure = Order::whereBetween('created_at',array($from_date,$to_date))->where('status', '=', '0')->count();

		$return = array(
					"item" => array(
							array(
								"value" => $success,
	     						"label" => "success",
	     						"color" => "13699c"
	     					),
	     					array(
								"value" => $failure,
	     						"label" => "failure",
	     						"color" => "60b8ec"
	     					)
						)	
				);

		return json_encode($return);

	}

	public function signUpPieChart(){

		$from_day = $this->days;
		$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$from_day." days"))));
		$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("+1 days"))));

		$facebook = Customer::whereBetween('created_at',array($from_date,$to_date))->where('identity', '=', 'facebook')->count();

		$email = Customer::whereBetween('created_at',array($from_date,$to_date))->where('identity', '=', 'email')->count();

		$return = array(
					"item" => array(
							array(
								"value" => $facebook,
	     						"label" => "facebook",
	     						"color" => "13699c"
	     					),
	     					array(
								"value" => $email,
	     						"label" => "email",
	     						"color" => "60b8ec"
	     					)
						)	
				);

		return json_encode($return);

	}

}
