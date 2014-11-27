<?php


class CaptureApiController extends BaseAPIController {

	public function __construct()
	{
		$this->afterFilter(function($response)
		{
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			return $response;
		});
	}

	public function postCapture()
	{
		$data = array(
				'capture_type' => 'fitness_guide',
				'name' => Input::json()->get('name'), 
				'email' => Input::json()->get('email'),
				'mobile' => Input::json()->get('mobile'),
				'created_at' => date('Y-m-d H:i:s')
			);
		$storecapture = Capture::create($data);
		return Response::json($storecapture);
	}	

}
