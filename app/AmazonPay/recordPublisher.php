<?php
require_once 'HttpCurl.php';

postMetrics();

function postMetrics(){
	$config = parse_ini_file( dirname(__DIR__)."/AmazonPay/config.ini" );
	$metricsSize = getFileSize();
	if(($config['lastFetchedTimeForPostingMetrics']<(time()-300))||($metricsSize)>500000){
		$data['latency'] = parseLatencyFile();
		$data['count'] = parseCountFile();
		$post_data['payload'] = $data;
		$post_data['sdkType'] = 'ServerSideSDKPHP';
		$post_data = json_encode(array('data' => $post_data));
		$httpCurlRequest = new HttpCurl();
		$response = $httpCurlRequest->httpPost('http://amazonpay.amazon.in/publishMetrics/data=$post_data', null, null);
		$config[ 'lastFetchedTimeForPostingMetrics' ] = time();
		$fp = fopen(dirname(__DIR__).'/AmazonPay/config.ini', 'w');
		flock($fp, LOCK_EX);
		foreach ( $config as $name => $value )
		{
			fwrite( $fp, "$name = \"$value\"\n" );
		}
		flock($fp, LOCK_UN);
		fclose($fp);
		if(file_exists(dirname(__DIR__).'/AmazonPay/metrics/latencyMetrics.txt')){
			unlink(dirname(__DIR__).'/AmazonPay/metrics/latencyMetrics.txt');
		}
		if(file_exists(dirname(__DIR__).'/AmazonPay/metrics/countMetrics.txt')){
			unlink(dirname(__DIR__).'/AmazonPay/metrics/countMetrics.txt');
		}
	}
}

function getFileSize(){
	$size = 0;
	if(file_exists(dirname(__DIR__).'/AmazonPay/metrics/latencyMetrics.txt')){
		$size = $size + filesize(dirname(__DIR__).'/PayWithAmazon/metrics/latencyMetrics.txt');
	}
	if(file_exists(dirname(__DIR__).'/AmazonPay/metrics/countMetrics.txt')){
		$size = $size + filesize(dirname(__DIR__).'/PayWithAmazon/metrics/countMetrics.txt');
	}
	return $size;
}

function preg_grep_keys( $pattern, $input, $flags = 0 )
{
	$keys = preg_grep( $pattern, array_keys( $input ), $flags );
	$vals = array();
	foreach ( $keys as $key )
	{
		$vals[$key] = $input[$key];
	}
	return $vals;
}

function parseLatencyFile(){
	$pattern='/(?<operation>\w+)\s+(?<key>\w+)\s+(?<time>\d+(\.\d{1,6})?)/i';
	if(file_exists(dirname(__DIR__).'/AmazonPay/metrics/latencyMetrics.txt')){
		$lines = file(dirname(__DIR__).'/AmazonPay/metrics/latencyMetrics.txt');
		$title = 'data';
		$json_data=array();
		foreach ($lines as $line_num => $line) {
			preg_match($pattern,$line,$result);
			$json_data[]=preg_grep_keys('/operation|key|time/',$result);
		}

		return json_encode($json_data);
	}
	else{
		return null;
	}
}

function parseCountFile(){
	$pattern='/(?<key>\w+)\s+(?<count>\d+)/i';
	if(file_exists(dirname(__DIR__).'/AmazonPay/metrics/countMetrics.txt')){
		$lines = file(dirname(__DIR__).'/AmazonPay/metrics/countMetrics.txt');
		$title = 'data';
		$json_data=array();
		foreach ($lines as $line_num => $line) {
			preg_match($pattern,$line,$result);
			$json_data[]=preg_grep_keys('/key|count/',$result);
		}
		return json_encode($json_data);
	}
	else{
		return null;
	}
}