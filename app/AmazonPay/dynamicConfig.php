<?php
require_once 'HttpCurl.php';

getDynamicConfig();

function getDynamicConfig(){
	try{
		$config = parse_ini_file( dirname(__DIR__)."/AmazonPay/config.ini" );
		if($config['lastFetchedTimeForFetchingConfig']<(time()-3600)){
			$httpCurlRequest = new HttpCurl();
			$dynamicConfig = $httpCurlRequest->httpGet('https://amazonpay.amazon.in/getDynamicConfig?key=serverSideSDKPHP', null);
			$jsonObj = json_decode($dynamicConfig);
			if(isset($jsonObj->{'publicKey'})){
				$config[ 'publicKey' ] = $jsonObj->{'publicKey'};
				$config[ 'lastFetchedTimeForFetchingConfig' ] = time();
				$fp = fopen(dirname(__DIR__).'/AmazonPay/config.ini', 'w');
				flock($fp, LOCK_EX);
				foreach ( $config as $name => $value )
				{
					fwrite( $fp, "$name = \"$value\"\n" );
				}
				flock($fp, LOCK_UN);
				fclose($fp);
			}else{
				throw new Exception("Public key is not present", 1);
			}
		}
	}catch(Exception $e){
		throw new Exception("Error fetching Dynamic Config", 1);
	}
}
