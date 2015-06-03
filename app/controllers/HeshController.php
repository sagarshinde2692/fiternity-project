<?php

class HeshController extends \BaseController {

	public function test(){	

		$spams = Cache::tags('adfadf')->has('adfadfspam');
		/*$bans = [
		   [
		       'ip' => 'test ip',
		       'reason' => "spam: test reason",
		   ],
		   [
		       'ip' => 'test ip2 ',
		       'reason' => "spam: test reason 2",
		   ]

		];

		Cache::tags('bans')->put('spam', $bans, 100);*/

		//$spams = Cache::tags('bans')->get('spam');
		//echo"<pre>";print_r($spams);exit;

		if($spams)
			echo "not empty";
		else
			echo "empty";
		/*foreach ($spams as $spam) {
		    echo $spam['ip'].' '.$spam['reason']."<br />";
		}*/
	}
}
