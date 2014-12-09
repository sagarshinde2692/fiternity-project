<?php


class CaptureController extends \BaseController {

	public function __construct()
	{
		$this->afterFilter(function($response)
		{
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			return $response;
		});
	}

	public function postCapture(){
		
		$data = array(
				'capture_type' => Input::json()->get('capture_type'),
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'),
				'mobile' => Input::json()->get('mobile'),
				'created_at' => date('Y-m-d H:i:s')
			);
		$storecapture = Capture::create($data);
		return Response::json($storecapture);
	}	

}
