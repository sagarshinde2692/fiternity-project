<?php

require __DIR__ . '/../../bootstrap/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/start.php';
$app->setRequestForConsoleEnvironment();
$app->boot();

$payload = getPayload();

fire($payload);

function fire($payload)
{

	$to = (array)$payload->to;
	$message = strip_tags ($payload->message);

    foreach ($to as $number) {

        $url = 'http://www.kookoo.in/outbound/outbound_sms_ftrnty.php';

        $param = array(
            'api_key' => 'KK33e21df516ab75130faef25c151130c1', 
            'phone_no' => trim($number), 
            'message' => $message,
            'senderid'=> 'FTRNTY' 
        );
                                  
        $url = $url . "?" . http_build_query($param, '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        curl_close($ch);
        
        echo $result;
                
        // echo $number;
        /*$sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($message);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_URL, $sms_url);
        curl_setopt($ci, CURLOPT_HEADER, 0);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ci);
        curl_close($ci);

        echo 'Sms sent to '.$number;*/
    }   

}