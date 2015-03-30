<?PHP namespace App\Sms;

use Queue;

abstract Class CountrySms {

	public function sendTo($to, $message, $delay = null ){

        // return $to;

		if($delay == null){

            $messageid = Queue::push(function($job) use ($to, $message){ 

                $job_id         =   $job->getJobId(); 

                $user           =   "chaitu87"; //your username
                $password       =   "564789123"; //your password
                $senderid       =   "FTRNTY"; //Your senderid
                $messagetype    =   "N"; //Type Of Your Message
                $DReports       =   "Y"; //Delivery Reports
                $url            =   "http://www.smscountry.com/SMSCwebservice_Bulk.aspx";
                $message        =   urlencode(trim(strip_tags($message)));

                foreach ($to as $number) {
                    // // echo $number;
                    // $mobilenumbers  =   "919773348762"; //enter Mobile numbers comma seperated
                    $mobilenumbers  =   urlencode(trim($number));
                    $ch             =   curl_init();
                    $ret            =   curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt ($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                    curl_setopt ($ch, CURLOPT_POSTFIELDS,"User=$user&passwd=$password&mobilenumber=$mobilenumbers&message=$message&sid=$senderid&mtype=$messagetype&DR=$DReports");
                    $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $curlresponse = curl_exec($ch); 
                    curl_close($ch);

                }

                $job->delete();  
            }, array(), 'pullapp');

            return $messageid;

        }else{

            $seconds    =   $this->getSeconds($delay);
            $messageid  =   Queue::later($seconds, function($job) use ($to, $message){ 

                $job_id         =   $job->getJobId();
                
                $user           =   "chaitu87"; //your username
                $password       =   "564789123"; //your password
                $senderid       =   "FTRNTY"; //Your senderid
                $messagetype    =   "N"; //Type Of Your Message
                $DReports       =   "Y"; //Delivery Reports
                $url            =   "http://www.smscountry.com/SMSCwebservice_Bulk.aspx";
                $message        =   urlencode(trim(strip_tags($message)));

                foreach ($to as $number) {
                    // // echo $number;
                    // $mobilenumbers  =   "919773348762"; //enter Mobile numbers comma seperated
                    $mobilenumbers  =   urlencode(trim($number));
                    $ch             =   curl_init();
                    $ret            =   curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt ($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                    curl_setopt ($ch, CURLOPT_POSTFIELDS,"User=$user&passwd=$password&mobilenumber=$mobilenumbers&message=$message&sid=$senderid&mtype=$messagetype&DR=$DReports");
                    $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $curlresponse = curl_exec($ch); 
                    curl_close($ch);
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

        if ($delay instanceof \Carbon\Carbon){
            return max(0, $delay->timestamp - $this->getTime());
        }
        // echo (int) $delay; exit;
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