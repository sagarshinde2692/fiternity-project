<?PHP namespace App\Sms;

use Queue;

abstract Class VersionNextSms {

	public function sendTo($to, $message, $delay = null ){

        // return $to;

		if($delay == null){

            Queue::push(function($job) use ($to, $message){ 

                $job_id = $job->getJobId(); 

                foreach ($to as $number) {
                    echo $number;
                    $sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=india123&type=0&dlr=1&destination=" . urlencode($number) . "&source=fitter&message=" . urlencode($message);
                    $ci = curl_init();
                    curl_setopt($ci, CURLOPT_URL, $sms_url);
                    curl_setopt($ci, CURLOPT_HEADER, 0);
                    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
                    $response = curl_exec($ci);
                    curl_close($ci);
                }

                $job->delete();  
            });

        }else{

            Queue::later($delay, function($job) use ($to, $message){ 

                $job_id = $job->getJobId(); 

                foreach ($to as $number) {

                    $sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=india123&type=0&dlr=1&destination=" . urlencode($number) . "&source=fitter&message=" . urlencode($message);
                    $ci = curl_init();
                    curl_setopt($ci, CURLOPT_URL, $sms_url);
                    curl_setopt($ci, CURLOPT_HEADER, 0);
                    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
                    $response = curl_exec($ci);
                    curl_close($ci);

                }

                $job->delete();  

            });
        }

    }



}