<?php
/* Class HttpCurl
 * Handles Curl POST function for all requests
 */
class HttpCurl
{
    private $config = array();
    private $header = false;
    private $accessToken = null;
    private $curlResponseInfo = null;
    
    /* Takes user configuration array as input
     * Takes configuration for API call or IPN config
     */
    
    public function __construct($config = null)
    {
        $this->config = $config;
    }
    
    /* Setter for boolean header to get the user info */
    
    public function setHttpHeader()
    {
        $this->header = true;
    }
    
    /* Setter for Access token to get the user info */
    
    public function setAccessToken($accesstoken)
    {
        $this->accessToken = $accesstoken;
    }

    private  function commonCurlParams($url,$userAgent)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($userAgent))
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        return $ch;
    }
    
    /* POST using curl for the following situations
     * 1. API calls
     * 2. IPN certificate retrieval
     * 3. Get User Info
     */
    
    public function httpPost($url, $userAgent = null, $parameters = null)
    {
        $ch = $this->commonCurlParams($url,$userAgent);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch,CURLINFO_HEADER_OUT,true);
        $response = $this->execute($ch);
        return $response;
    }
    
    /* GET using curl for the following situations
     * 1. IPN certificate retrieval
     * 2. Get User Info
     */
    
    public function httpGet($url, $userAgent = null)
    {
        $ch = $this->commonCurlParams($url,$userAgent);
        if ($this->header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: bearer ' . $this->accessToken
            ));
        }
        
        $response = $this->execute($ch);
        return $response;
    }
    
    /* Execute Curl request */
    
    private function execute($ch)
    {
        $response = '';
        $response = curl_exec($ch);
        if ($response === false) {
            $error_msg = "Unable to post request, underlying exception of " . curl_error($ch);
            curl_close($ch);
            throw new \Exception($error_msg);
        }
        else{
            $this->curlResponseInfo = curl_getinfo($ch);
        }
        curl_close($ch);
        return $response;
    }
    
    /* Get the output of Curl Getinfo */
    
    public function getCurlResponseInfo()
    {
        return $this->curlResponseInfo;
    }
}