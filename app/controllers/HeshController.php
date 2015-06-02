<?php

class HeshController extends \BaseController {

	public function test(){	

		$redis = Redis::connection();
		$redis->set('name', json_encode(array('ssadf'=>'asddfasdf','asdf'=>'asdfasdf')));

		$name = $redis->get('name');

		$values = $redis->lrange('names', 5, 10);

		echo"<pre>";print_r($name);exit;
	}
}
