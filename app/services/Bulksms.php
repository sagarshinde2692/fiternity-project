<?PHP namespace App\Services;

use \Log;

Class Bulksms {
    
    function send($data)
    {

        try{

            switch ($data['sms_type']) {

                case 'transactional': $api_key = 'KK33e21df516ab75130faef25c151130c1';$senderid = 'FTRNTY';break;
                case 'promotional': $api_key = 'KK6cb3903e3d2c428bb60c0cfaa212009e';$senderid = 'KOOKOO';break;
                default : $api_key = 'KK6cb3903e3d2c428bb60c0cfaa212009e';$senderid = 'KOOKOO';break;
            }

            $url = 'http://kookoo.in/outbound/outbound_sms_ftrnty_bulk.php';

            $param = array(
                'smstype' => 'BULKSMS',
                'api_key' => $api_key, 
                'phone_no' => implode(',',$data['contact_no']), 
                'message' => $data['message'],
                'senderid'=> $senderid, 
            );
                                      
            $url = $url . "?" . http_build_query($param, '&');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);
            curl_close($ch);

            $return = array('status'=>'success'); 

        }catch(Exception $exception){

            $message = array(
                'type'    => get_class($exception),
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            );

            $return = array('status'=>'fail','error_message'=>$message);

            Log::error($exception);
        } 

        return $return;

    }

}