<?php

class CronController extends BaseController {

	public function __construct() {
     	parent::__construct();	
    }


	public function cronLog(){

		$data = Input::json()->all();

		$rules = [
			'label' => 'required',
			'start_time' => 'required',
			'end_time' => 'required',
			'status' => 'required',
			'message' => 'required'
		];

		$validator = Validator::make($data,$rules);

		if($validator->fails()) {

			return Response::json(array('status' => 400,'message' =>$this->errorMessage($validator->errors())),400);
		}


		$inserted_id = Cronlog::max('_id') + 1;

		$cronlog = new Cronlog();

		$cronlog->_id = $inserted_id;
		$cronlog->label = $data['label'];
		$cronlog->time_required = (int)$data['end_time'] - (int)$data['start_time'];
		$cronlog->status = $data['status'];
		$cronlog->message = $data['message'];
		$cronlog->save();

		return Response::json(array('status' => 200,'message' => 'success'),200);
									
	}

	public function monitor($days){

		$data = array();

		$from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("-".$days." days"))));
        $match['$match']['created_at']['$gte'] = $from_date;

		$to_date = new MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime("+ 1 days"))));
        $match['$match']['created_at']['$lte'] = $to_date;


        $cronlog = Cronlog::raw(function($collection) use ($match){

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
                                'label' => '$label',
                            ),
                            'data' => array(
                                '$push' => '$$ROOT'
                            )
                        )
                    );

            $aggregate[] = $group;
          
            return $collection->aggregate($aggregate);

        });

        $result = [];
		foreach ($cronlog['result'] as $key => $value) {

			$date = $value['_id']['day'].'-'.$value['_id']['month'].'-'.$value['_id']['year'];
			$unix = strtotime($date);
			$label = $value['data']['0']['label'];
	
			$result[$unix][$label]['time_required'] =  $value['data']['0']['time_required'];
			$result[$unix][$label]['status'] =  $value['data']['0']['status'];
			$result[$unix][$label]['message'] =  $value['data']['0']['message'];
			$result[$unix][$label]['date'] =  date('d M Y',$unix);
		}

		ksort($result);
		
        return json_encode($result);

	}

	public function errorMessage($errors){

		$errors = json_decode(json_encode($errors));
		$message = array();
		foreach ($errors as $key => $value) {
			$message[$key] = $value[0];
		}
		return $message;
	}

}																																																																																																																																																																																																																																																																										