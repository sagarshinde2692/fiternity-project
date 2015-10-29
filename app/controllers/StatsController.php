<?PHP

/** 
 * ControllerName : StatsController.
 * Maintains a list of functions used for StatsController.
 *
 * @author Mahesh Jadhav
 */


use App\Services\SmsVersionNext as SmsVersionNext;

class StatsController extends \BaseController {

	protected $days;
	protected $sms_version_next;

	public function __construct(SmsVersionNext $sms_version_next) {

		$this->sms_version_next = $sms_version_next;
		$this->days = 13;

	}

	public function booktrial($days){

		$data = array();

		$from_date = new MongoDate(strtotime(date('Y-m-d', strtotime("-".$days." days"))));
        $match['$match']['created_at']['$gte'] = $from_date;

		$to_date = new MongoDate(strtotime(date('Y-m-d')));
        $match['$match']['created_at']['$lte'] = $to_date;

		$trialRequest = DB::collection('booktrials')->raw(function($collection) use ($match){

            $aggregate = [];

            if(!empty($match)){
               $aggregate[] = $match;
            }

            $group = array(
                        '$group' => array(
                            '_id' => array(
                                'day' => array('$dayOfMonth'=> '$created_at'),
                                'month' => array('$month'=> '$created_at'),
                                'year' => array('$year'=> '$created_at'),
                                'booktrial_type' => '$booktrial_type'
                            ),
                            'count' => array(
                                '$sum' => 1
                            )
                        )
                    );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

		$result = [];
		foreach ($trialRequest['result'] as $key => $value) {

			$date = $value['_id']['day'].'-'.$value['_id']['month'].'-'.$value['_id']['year'];
			$unix = strtotime($date);

			$result[$unix][$value['_id']['booktrial_type']] =  $value['count'];
			$result[$unix]['date'] =  date('d M Y',$unix);
		}

		ksort($result);
		$scope = [];
		$scope['series'] = ['Total','Auto','Manual'];

		foreach ($result as $key => $value) {

			$scope['labels'][] = $value['date'];

			if(!isset($value['auto'])){
				$result[$key]['auto'] = 0;
			}
		
			if(!isset($value['manual'])){
				$result[$key]['manual'] = 0;
			}
			
			$scope['data'][0][] = (int)$result[$key]['auto'] + (int)$result[$key]['manual'];
			$scope['data'][1][] = $result[$key]['auto'];
			$scope['data'][2][] = $result[$key]['manual'];
			 
		}

		return json_encode($scope);

	}

	public function signUp($days){

		$data = array();

		$from_date = new MongoDate(strtotime(date('Y-m-d', strtotime("-".$days." days"))));
        $match['$match']['created_at']['$gte'] = $from_date;

		$to_date = new MongoDate(strtotime(date('Y-m-d')));
        $match['$match']['created_at']['$lte'] = $to_date;

		$signup = DB::collection('customers')->raw(function($collection) use ($match){

            $aggregate = [];

            if(!empty($match)){
               $aggregate[] = $match;
            }

            $group = array(
                        '$group' => array(
                            '_id' => array(
                                'day' => array('$dayOfMonth'=> '$created_at'),
                                'month' => array('$month'=> '$created_at'),
                                'year' => array('$year'=> '$created_at'),
                                'identity' => '$identity'
                            ),
                            'count' => array(
                                '$sum' => 1
                            )
                        )
                    );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

		$result = [];
		foreach ($signup['result'] as $key => $value) {

			$date = $value['_id']['day'].'-'.$value['_id']['month'].'-'.$value['_id']['year'];
			$unix = strtotime($date);

			$result[$unix][$value['_id']['identity']] =  $value['count'];
			$result[$unix]['date'] =  date('d M Y',$unix);
		}

		ksort($result);
		$scope = [];
		$scope['series'] = ['Total','Facebook','Email'];

		foreach ($result as $key => $value) {

			$scope['labels'][] = $value['date'];

			if(!isset($value['facebook'])){
				$result[$key]['facebook'] = 0;
			}
		
			if(!isset($value['email'])){
				$result[$key]['email'] = 0;
			}
			
			$scope['data'][0][] = (int)$result[$key]['facebook'] + (int)$result[$key]['email'];
			$scope['data'][1][] = $result[$key]['facebook'];
			$scope['data'][2][] = $result[$key]['email'];
			 
		}

		return json_encode($scope);

	}

	public function orders($days){

		$data = array();

		$from_date = new MongoDate(strtotime(date('Y-m-d', strtotime("-".$days." days"))));
        $match['$match']['created_at']['$gte'] = $from_date;

		$to_date = new MongoDate(strtotime(date('Y-m-d')));
        $match['$match']['created_at']['$lte'] = $to_date;

		$total = DB::collection('orders')->raw(function($collection) use ($match){

            $aggregate = [];

            if(!empty($match)){
               $aggregate[] = $match;
            }

            $group = array(
                        '$group' => array(
                            '_id' => array(
                                'day' => array('$dayOfMonth'=> '$created_at'),
                                'month' => array('$month'=> '$created_at'),
                                'year' => array('$year'=> '$created_at'),
                            ),
                            'count' => array(
                                '$sum' => 1
                            )
                        )
                    );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

        $success = DB::collection('orders')->raw(function($collection) use ($match){

            $aggregate = [];

            $match['$match']['$or'] = array(array('status'=>'1'),array('order_action'=>'bought'));

            $aggregate[] = $match;

            $group = array(
                        '$group' => array(
                            '_id' => array(
                                'day' => array('$dayOfMonth'=> '$created_at'),
                                'month' => array('$month'=> '$created_at'),
                                'year' => array('$year'=> '$created_at'),
                            ),
                            'count' => array(
                                '$sum' => 1
                            )
                        )
                    );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });


		$result = [];
		foreach ($total['result'] as $key => $value) {

			$date = $value['_id']['day'].'-'.$value['_id']['month'].'-'.$value['_id']['year'];
			$unix = strtotime($date);

			$result[$unix]['total'] =  $value['count'];
			$result[$unix]['date'] =  date('d M Y',$unix);
		}

		foreach ($success['result'] as $key => $value) {

			$date = $value['_id']['day'].'-'.$value['_id']['month'].'-'.$value['_id']['year'];
			$unix = strtotime($date);

			$result[$unix]['success'] =  $value['count'];
			$result[$unix]['date'] =  date('d M Y',$unix);
		}

		ksort($result);

		$scope = [];
		$scope['series'] = ['Total','Success','Fail'];

		foreach ($result as $key => $value) {

			$scope['labels'][] = $value['date'];

			if(!isset($value['total'])){
				$result[$key]['total'] = 0;
			}
			
			if(!isset($value['success'])){
				$result[$key]['success'] = 0;
			}

			$scope['data'][0][] = $result[$key]['total'];
			$scope['data'][1][] = $result[$key]['success'];
			$scope['data'][2][] = (int)$result[$key]['total'] - (int)$result[$key]['success'];
			 
		}

		return json_encode($scope);

	}


	public function callBack($days){

		$data = array();

		$from_date = new MongoDate(strtotime(date('Y-m-d', strtotime("-".$days." days"))));
        $match['$match']['created_at']['$gte'] = $from_date;

		$to_date = new MongoDate(strtotime(date('Y-m-d')));
        $match['$match']['created_at']['$lte'] = $to_date;

		$total = DB::collection('captures')->raw(function($collection) use ($match){

            $aggregate = [];

            $match['$match']['capture_type'] = 'request_callback';

            $aggregate[] = $match;

            $group = array(
                        '$group' => array(
                            '_id' => array(
                                'day' => array('$dayOfMonth'=> '$created_at'),
                                'month' => array('$month'=> '$created_at'),
                                'year' => array('$year'=> '$created_at'),
                            ),
                            'count' => array(
                                '$sum' => 1
                            )
                        )
                    );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

        $homepage = DB::collection('captures')->raw(function($collection) use ($match){

            $aggregate = [];

            $match['$match']['capture_type'] = 'request_callback';
            $match['$match']['vendor'] = 'other pages';

            $aggregate[] = $match;

            $group = array(
                        '$group' => array(
                            '_id' => array(
                                'day' => array('$dayOfMonth'=> '$created_at'),
                                'month' => array('$month'=> '$created_at'),
                                'year' => array('$year'=> '$created_at'),
                            ),
                            'count' => array(
                                '$sum' => 1
                            )
                        )
                    );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });


		$result = [];
		foreach ($total['result'] as $key => $value) {

			$date = $value['_id']['day'].'-'.$value['_id']['month'].'-'.$value['_id']['year'];
			$unix = strtotime($date);

			$result[$unix]['total'] =  $value['count'];
			$result[$unix]['date'] =  date('d M Y',$unix);
		}

		foreach ($homepage['result'] as $key => $value) {

			$date = $value['_id']['day'].'-'.$value['_id']['month'].'-'.$value['_id']['year'];
			$unix = strtotime($date);

			$result[$unix]['homepage'] =  $value['count'];
			$result[$unix]['date'] =  date('d M Y',$unix);
		}

		ksort($result);
		
		$scope = [];
		$scope['series'] = ['Total','Homepage','Vendor'];

		foreach ($result as $key => $value) {

			$scope['labels'][] = $value['date'];

			if(!isset($value['total'])){
				$result[$key]['total'] = 0;
			}
			$scope['data'][0][] = $result[$key]['total'];

			if(!isset($value['homepage'])){
				$result[$key]['homepage'] = 0;
			}
			$scope['data'][1][] = $result[$key]['homepage'];

			$scope['data'][2][] = (int)$result[$key]['total'] - (int)$result[$key]['homepage'];
			 
		}

		return json_encode($scope);
	}

	public function ordersPieChart($days){

		$from_day = $this->days-1;
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

	public function signUpPieChart($days){

		$from_day = $this->days-1;
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

	public function review($days){

		$data = array();
		for ($i=0; $i < $days ; $i++) {
			$count = 0;
			$day_month = date('Y-m-d', strtotime("-".$i." days"));
			$to_day = $i-1;
			
			$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$i." days"))));
			$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$to_day." days"))));

			$count = Review::whereBetween('created_at',array($from_date,$to_date))->count();
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

	public function smsBalance($days){

		$transactionBalance  =  $this->sms_version_next->transactionBalance();
		$promotionBalance  =  $this->sms_version_next->promotionBalance();

		$return = array(
					"item" => array(
							array(
								"text"=>($transactionBalance['status'] = 200) ? "Transaction ".ucwords(strtolower($transactionBalance['data'])) : "Transaction ".$transactionBalance['message'],
							   	"type"=>0
							),array(
								"text"=>($promotionBalance['status'] = 200) ? "Promotion ".ucwords(strtolower($promotionBalance['data'])) : "Promotion ".$promotionBalance['message'],
							   	"type"=>1
							)
						)
				);

		return json_encode($return);
	}

}
