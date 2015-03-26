<?PHP namespace App\Sms;

use Queue;

abstract Class VersionNextSms {

	public function sendTo($to, $message, $delay = null ){

        // return $to;

		if($delay == null){

            $messageid = Queue::push(function($job) use ($to, $message){ 

                $job_id = $job->getJobId(); 

                $msg = strip_tags($message);

                foreach ($to as $number) {
                    // echo $number;
                    $sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=india123&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);
                    $ci = curl_init();
                    curl_setopt($ci, CURLOPT_URL, $sms_url);
                    curl_setopt($ci, CURLOPT_HEADER, 0);
                    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
                    $response = curl_exec($ci);
                    curl_close($ci);
                }

                $job->delete();  
            }, array(), 'pullapp');

            return $messageid;

        }else{

            $seconds    =   $this->getSeconds($delay);
            $messageid  =   Queue::later($seconds, function($job) use ($to, $message){ 

                $job_id = $job->getJobId();
                
                $msg = strip_tags($message); 

                foreach ($to as $number) {

                    $sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=india123&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);
                    $ci = curl_init();
                    curl_setopt($ci, CURLOPT_URL, $sms_url);
                    curl_setopt($ci, CURLOPT_HEADER, 0);
                    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
                    $response = curl_exec($ci);
                    curl_close($ci);

                }

                $job->delete();  

            }, array(), 'pullapp');
            return $messageid;
        }

    }


    /**
     * Calculate the number of seconds with the given delay.
     *
     * @param  \DateTime|int  $delay
     * @return int
     */
    protected function getSeconds($delay){
        
        if ($delay instanceof DateTime){
            return max(0, $delay->getTimestamp() - $this->getTime());
        }
        return (int) $delay;
    }

    /**
     * Get the current UNIX timestamp.
     *
     * @return int
     */
    public function getTime(){
        return time();
    }



}