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

	public function errorMessage($errors){

		$errors = json_decode(json_encode($errors));
		$message = array();
		foreach ($errors as $key => $value) {
			$message[$key] = $value[0];
		}
		return $message;
	}

}																																																																																																																																																																																																																																																																										