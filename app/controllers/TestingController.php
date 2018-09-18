<?php

class TestingController extends \BaseController {

	public function getOtpByPhone($customer_phone){
		
		$temp = Temp::where('customer_phone', $customer_phone)->where('verified', '!=', 'Y')->orderBy('_id', 'desc')->first();

		if($temp){
			return $temp->otp;
		}
		
		return Response::json(['message'=>'no otp exists'], 404);
	}


}
