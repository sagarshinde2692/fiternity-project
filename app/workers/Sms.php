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
        // echo $number;
        $sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($message);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_URL, $sms_url);
        curl_setopt($ci, CURLOPT_HEADER, 0);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ci);
        curl_close($ci);

        echo 'Sms sent to '.$number;
    }   

}