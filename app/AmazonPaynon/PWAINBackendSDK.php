<?php namespace App\AmazonPaynon;
require_once 'HttpCurl.php';
use Exception;
use InvalidArgumentException;
class PWAINBackendSDK {
	private $fields = array ();
	private $config = array (
            'merchant_id' => null,
            'secret_key' => null,
            'access_key' => null,
            'base_url'  => null,
            'currency_code' => null,
            'sandbox' => null,
            'platform_id' => null,
            'application_name' => null,
            'application_version' => null,
            'handle_throttle' => true
	);

	public function __construct($config = null) {
		if (! is_null ( $config )) {
			$configArray = $config;
			$this->checkConfigKeys ( $configArray );
			$this->checkWritePermissions();
		} else {
			throw new \Exception ( '$config cannot be null.' );
		}
		// Get the list of fields that we are interested in
		$this->params_SignAndEncrypt = array (
				"orderTotalAmount" => true,
				"orderTotalCurrencyCode" => true,
				"sellerOrderId" => true,
				"customInformation" => false,
				"sellerNote" => false,
				"transactionTimeout" => false,
				"isSandbox" => false,
				"sellerStoreName" => false
		);

		$this->params_verifySignature = array (
				"description" => true,
				"reasonCode" => true,
				"status" => true,
				"signature" => false,
				"sellerOrderId" => false,
				"amazonOrderId" => false,
				"transactionDate" => false,
				"orderTotalAmount" => false,
				"orderTotalCurrencyCode" => false,
				"customInformation" => false
		);

		$this->params_refund = array (
				"amazon_transaction_id" => true,
				"amazon_transaction_type" => true,
				"refund_reference_id" => true,
				"refund_amount" => true,
				"currency_code" => true,
				"merchant_id" => false,
				"seller_refund_note" => false,
				"soft_descriptor" => false,
				"mws_auth_token" => false
		);

		$this->params_refundDetails = array (
				"amazon_refund_id" => true,
				"merchant_id" => false,
				"mws_auth_token" => false
		);

		$this->params_listOrderReference = array (
				"payment_domain" => true,
				"query_id" => true,
				"query_id_type" => true,
				"merchant_id" => false,
				"page_size" => false,
				"sort_order" => false,
				"order_reference_status_list_filter" => false,
				"created_time_range_start" => false,
				"created_time_range_end" => false
		);

		$this->params_listOrderReferenceNextToken = array (
				"next_page_token" => true,
				"merchant_id" => false,
				"created_time_range_start" => false,
				"created_time_range_end" => false
		);

		$this->params_SignAndEncryptGetChargeRequest = array (
				"transactionId" => true,
				"transactionIdType" => true
		);

		$this->params_verifySignatureForProcessChargeResponse = array (
				"transactionId" => true,
				"signature" => false,
				"payUrl" => false
		);

		$this->params_verifySignatureForChargeStatus = array (
				"transactionStatusCode" => true,
				"transactionStatusDescription" => true,
				"transactionId" => false,
				"merchantTransactionId" => false,
				"signature" => false,
				"transactionValue" => false,
				"transactionCurrencyCode" => false,
				"merchantCustomData" => false,
				"transactionDate" => false
		);

		$this->params_fetchTransactionDetails = array (
				"transactionId" => true,
				"transactionIdType" => true 
		);
	}

	/**
	 * generates the signature for the parameters given with the aws secret key
	 * provided and encrypts parameters along with signature
	 *
	 * @param
	 *        	$parameters
	 *
	 * @return encrypted payload
	 */
	public function generateSignatureAndEncrypt($parameters = array()) {
		$startTime = $this->microtime_float();
		try{
			if (! array_key_exists ('operationName', $parameters) ) {
				$this->checkForRequiredParameters ( $parameters, $this->params_SignAndEncrypt );
				$operation = 'SIGN_AND_ENCRYPT';
			} elseif ($parameters['operationName'] == 'SIGN_AND_ENCRYPT_GET_CHARGE_STATUS_REQUEST') {
				$operation = $parameters['operationName'];
				unset($parameters['operationName']);
				$this->checkForRequiredParameters ( $parameters, $this->params_SignAndEncryptGetChargeRequest );
			} else {
				throw new \Exception ( $operation . "is not a valid operation for sign and encrypt." );
			}
			$encryptedResponse = array ();
			$parameters = $this->calculateSignForEncryption ( $parameters );
			$parametersToEncrypt = $this->getParametersToEncrypted ( $parameters );
			$dataToEncrpyt = $this->getParametersAsString ( $parametersToEncrypt );
			$sessionKey = $this->getSecureRandomKey ();
			$pubKey = $this->getPublicKey ();
			$encryptedSessionKey = openssl_public_encrypt ( $sessionKey, $crypted, $pubKey, OPENSSL_PKCS1_OAEP_PADDING );
			$iv = $this->getSecureRandomKey ();
			$encyptedData = $this->encryptAndAppendTag ( $sessionKey, $iv, $dataToEncrpyt, null );
			$encryptedResponse ['payload'] = urlencode ( base64_encode ( $encyptedData ) );
			$encryptedResponse ['key'] = urlencode( base64_encode ( $crypted ) );
			$encryptedResponse ['iv'] = urlencode ( base64_encode ( $iv ) );
			$encryptedResponseAsString = $this->getParametersAsString ( $encryptedResponse );
			$this->executeAfterApi ( $startTime, $operation );
		} catch (Exception $e) {
			$this->updateCountMetrics('SIGN_AND_ENCRYPT_ERROR');
			$this->executeAfterApi ( $startTime, $operation );
			throw new Exception($e, 1);
		}
		return $encryptedResponseAsString;
	}

	/**
	 * calculates the signature for the parameters given with the aws secret key
	 * provided and verfies it against the signature provided.
	 *
	 * @param $paymentResponseMap the
	 *        	paymentResponse Map containing the signature
	 * @return true if the signature provided is valid
	 */
	public function verifySignature($paymentResponseMap) {
		$startTime = $this->microtime_float();
		try{
			$this->validateNotEmpty ( $paymentResponseMap, "paymentResponseMap" );
			// if(isset($paymentResponseMap['verificationOperationName']) && $paymentResponseMap['verificationOperationName'] == 'VERIFY_PROCESS_CHARGE_RESPONSE') {
			// 	unset ( $paymentResponseMap['verificationOperationName'] );
			// 	$this->checkForRequiredParameters ( $paymentResponseMap, $this->params_verifySignatureForProcessChargeResponse );			
			// } elseif (isset($paymentResponseMap['verificationOperationName']) && $paymentResponseMap['verificationOperationName'] == 'VERIFY_CHARGE_STATUS') {
			// 	unset ( $paymentResponseMap['verificationOperationName'] );
			// 	$this->checkForRequiredParameters ( $paymentResponseMap, $this->params_verifySignatureForChargeStatus );			
			// } else {
				$this->checkForRequiredParameters ( $paymentResponseMap, $this->params_verifySignature );
			// }		
			$providedSignature = $paymentResponseMap ['signature'];
			unset ( $paymentResponseMap ['signature'] );
			$this->validateNotNull ( $providedSignature, "ProvidedSignature" );
			$calculatedSignature = $this->calculateSignForVerification ( $paymentResponseMap );
			$this->executeAfterApi ( $startTime, 'VERIFY_SIGNATURE' );
		}catch(Exception $e) {
			$this->updateCountMetrics('VERIFY_SIGNATURE_ERROR');
			$this->executeAfterApi ( $startTime, 'VERIFY_SIGNATURE' );
			throw new Exception($e, 1);
		}
		return ($calculatedSignature ['Signature'] == $providedSignature);
	}

	/**
	 * To get process payment Url with given parameters.
	 * Calculates signed and encrypted payload and generates url
	 *
	 * @param $paramaeters
	 *			to be signed and encrypted
	 * @param $redirectUrl
	 * @return processPaymentUrl
	 *
	 */
	public function getProcessPaymentUrl($parameters, $redirectUrl){
		$startTime = $this->microtime_float();
		try{
			$this->validateNotNull ( $redirectUrl, "Redirect Url" );
			$this->validateNotNull ( parse_url ( $redirectUrl, PHP_URL_SCHEME ), "Invalid redirect URL. Please remember to input http:// or https:// as well. URL scheme" );
			$queryParameters = $this->generateSignatureAndEncrypt ( $parameters );
			$processPaymentUrl = $this->constructPaymentUrl ( $queryParameters, $redirectUrl );
			$this->executeAfterApi ( $startTime, 'GET_PAYMENT_URL' );
		}catch(Exception $e) {
			$this->updateCountMetrics('GET_PAYMENT_URL_ERROR');
			$this->executeAfterApi ( $startTime, 'GET_PAYMENT_URL' );
			throw new Exception($e, 1);
		}
		return $processPaymentUrl;
	}

	/* Refund API call - Refunds a previously captured amount.
	 * @see http://docs.developer.amazonservices.com/en_US/off_amazon_payments/OffAmazonPayments_Refund.html
	 *
	 * @param requestParameters['merchant_id'] - [String]
	 * @param requestParameters['amazon_capture_id'] - [String]
	 * @param requestParameters['refund_reference_id'] - [String]
	 * @param requestParameters['refund_amount'] - [String]
	 * @param requestParameters['currency_code'] - [String]
	 * @optional requestParameters['seller_refund_note'] [String]
	 * @optional requestParameters['soft_descriptor'] - [String]
	 * @optional requestParameters['mws_auth_token'] - [String]
	 */
	public function refund($requestParameters = array())
	{
		$startTime = $this->microtime_float();
		try{
			$this->checkForRequiredParameters ( $requestParameters, $this->params_refund );
			$parameters           = array();
			$parameters['Action'] = 'RefundPayment';
			$requestParameters    = array_change_key_case($requestParameters, CASE_LOWER);
			$fieldMappings = array(
            		'merchant_id' => 'SellerId',
            		'amazon_transaction_id' => 'AmazonTransactionId',
            		'amazon_transaction_type' => 'AmazonTransactionIdType',
            		'refund_reference_id' => 'RefundReferenceId',
            		'refund_amount' => 'RefundAmount.Amount',
            		'currency_code' => 'RefundAmount.CurrencyCode',
            		'seller_refund_note' => 'SellerRefundNote',
            		'soft_descriptor' => 'SoftDescriptor',
            		'content_type' => 'ContentType'
            );
            $responseObject = $this->setParametersAndPost($parameters, $fieldMappings, $requestParameters);
            $response = $this->toArray($responseObject);
            $this->executeAfterApi ( $startTime, 'REFUND' );
		}catch(Exception $e) {
			$this->updateCountMetrics('REFUND_ERROR');
			$this->executeAfterApi ( $startTime, 'REFUND' );
			throw new Exception($e, 1);
		}
		return ($response);
	}

	/* GetRefundDetails API call - Returns the status of a particular refund.
	 * @see http://docs.developer.amazonservices.com/en_US/off_amazon_payments/OffAmazonPayments_GetRefundDetails.html
	 *
	 * @param requestParameters['merchant_id'] - [String]
	 * @param requestParameters['amazon_refund_id'] - [String]
	 * @optional requestParameters['mws_auth_token'] - [String]
	 */
	public function getRefundDetails($requestParameters = array())
	{
		$startTime = $this->microtime_float();
		try{
			$this->checkForRequiredParameters ( $requestParameters, $this->params_refundDetails );
			$parameters           = array();
			$parameters['Action'] = 'GetRefundDetails';
			$requestParameters    = array_change_key_case($requestParameters, CASE_LOWER);
			$fieldMappings = array(
            		'merchant_id'         => 'SellerId',
            		'amazon_refund_id'  => 'AmazonRefundId',
            		'mws_auth_token'     => 'MWSAuthToken'
            );
            $responseObject = $this->setParametersAndPost($parameters, $fieldMappings, $requestParameters);
            $response = $this->toArray($responseObject);
            $this->executeAfterApi ( $startTime, 'GET_REFUND_DETAILS' );
		}catch(Exception $e) {
			$this->updateCountMetrics('GET_REFUND_DETAILS_ERROR');
			$this->executeAfterApi ( $startTime, 'GET_REFUND_DETAILS' );
			throw new Exception($e, 1);
		}
		return ($response);
	}

	/**
	 * To get process payment Url with given queryparameters.
	 *
	 * @param $queryparamaeters
	 * @return processPaymentUrl
	 *
	 */
	public function getProcessPaymentUrlWithQueryParameters($queryParameters, $redirectUrl){
		$startTime = $this->microtime_float();
		try{
			$this->validateNotNull ( $queryParameters, "Query Parameters" );
			$this->validateNotNull ( $redirectUrl, "Redirect Url" );
			$this->validateNotNull ( parse_url ( $redirectUrl, PHP_URL_SCHEME ), "Invalid redirect URL. Please remember to input http:// or https:// as well. URL scheme" );
			$processPaymentUrl = $this->constructPaymentUrl ( $queryParameters, $redirectUrl );
			$this->executeAfterApi ( $startTime, 'GET_PAYMENT_URL_WITH_QUERY_PARAMS' );
		}catch(Exception $e) {
			$this->updateCountMetrics('GET_PAYMENT_URL_WITH_QUERY_PARAMS_ERROR');
			$this->executeAfterApi ( $startTime, 'GET_PAYMENT_URL_WITH_QUERY_PARAMS' );
			throw new Exception($e, 1);
		}
		return $processPaymentUrl;
	}

      /* ListOrderReference API call - provide a list of Orders with some information about OrderDetails corresponding to a sellerOrderID.
       * @param requestParameters['merchant_id'] - [String]
       * @param requestParameters['payment_domain'] - [String]
       * @param requestParameters['query_id'] - [String]
       * @param requestParameters['query_id_type'] - [String]
       * @optional requestParameters['page_size'] [String]
       * @optional requestParameters['sort_order'] - [String]
       * @optional requestParameters['order_reference_status_list_filter'] - [String]
       * @optional requestParameters['created_time_range_start'] - [String]
       * @optional requestParameters['created_time_range_end'] - [String]
       */
      public function listOrderReference($requestParameters = array())
      {
            $startTime = $this->microtime_float();
            try{
                  $this->checkForRequiredParameters ( $requestParameters, $this->params_listOrderReference );
                  $parameters           = array();
                  $parameters['Action'] = 'ListOrderReference';
                  $parameters['Version'] = '2013-01-01';
                  $requestParameters    = array_change_key_case($requestParameters, CASE_LOWER);
                  $fieldMappings = array(
                        'merchant_id' => 'SellerId',
                        'page_size' => 'PageSize',
                        'payment_domain' => 'PaymentDomain',
                        'query_id' => 'QueryId',
                        'query_id_type' => 'QueryIdType',
                        'mws_auth_token'     => 'MWSAuthToken',
                        'sort_order' => 'SortOrder',
                        'order_reference_status_list_filter' => 'OrderReferenceStatusListFilter.OrderReferenceStatus',
                        'created_time_range_start' => 'CreatedTimeRange.StartTime',
                        'created_time_range_end' => 'CreatedTimeRange.EndTime'
                  );
                  $responseObject = $this->setParametersAndPost($parameters, $fieldMappings, $requestParameters);
                  $response = $this->toArray($responseObject);
                  $this->executeAfterApi ( $startTime, 'LIST_ORDER_REFERENCE' );
            }catch(Exception $e) {
                  $this->updateCountMetrics('LIST_ORDER_REFERENCE_ERROR');
                  $this->executeAfterApi ( $startTime, 'LIST_ORDER_REFERENCE' );
                  throw new Exception($e, 1);
            }
            return $response;
      }

      /* ListOrderReferenceByNextToken API call - provide a list of Orders with some information about OrderDetails corresponding to next page token.
       * @param requestParameters['merchant_id'] - [String]
       * @param requestParameters['next_page_token'] - [String]
       * @optional requestParameters['created_time_range_start'] - [String]
       * @optional requestParameters['created_time_range_end'] - [String]
       */
      public function listOrderReferenceByNextToken($requestParameters = array())
      {
            $startTime = $this->microtime_float();
            try{
                  $this->checkForRequiredParameters ( $requestParameters, $this->params_listOrderReferenceNextToken );
                  $parameters           = array();
                  $parameters['Action'] = 'ListOrderReferenceByNextToken';
                  $parameters['Version'] = '2013-01-01';
                  $requestParameters    = array_change_key_case($requestParameters, CASE_LOWER);
                  $fieldMappings = array(
                        'merchant_id' => 'SellerId',
                        'next_page_token' => 'NextPageToken',
                        'mws_auth_token'     => 'MWSAuthToken',
                        'created_time_range_start' => 'CreatedTimeRange.StartTime',
                        'created_time_range_end' => 'CreatedTimeRange.EndTime'
                  );
                  $responseObject = $this->setParametersAndPost($parameters, $fieldMappings, $requestParameters);
                  $response = $this->toArray($responseObject);
                  $this->executeAfterApi ( $startTime, 'LIST_ORDER_REFERENCE_BY_NEXT_TOKEN' );
            }catch(Exception $e) {
                  $this->updateCountMetrics('LIST_ORDER_REFERENCE_BY_NEXT_TOKEN_ERROR');
                  $this->executeAfterApi ( $startTime, 'LIST_ORDER_REFERENCE_BY_NEXT_TOKEN' );
                  throw new Exception($e, 1);
            }
            return $response;
      }

      /** FetchTransactionDetails API call - it fecthes details of transaction by calling get charge status
      * @param requestParameters['transactionId'] - [String]
      * @param requestParameters['transactionIdType'] - [String]
      */
      public function fetchTransactionDetails($requestParameters = array())
      {
      		$startTime = $this->microtime_float();
      		try {
      				$this->checkForRequiredParameters ($requestParameters, $this->params_fetchTransactionDetails);
      				if ($requestParameters['transactionIdType']!='TRANSACTION_ID') {
      					throw new \Exception ( "Transaction Type is not supported." );
      				}
      				$requestParameters['operationName'] = 'SIGN_AND_ENCRYPT_GET_CHARGE_STATUS_REQUEST';
      				$getChargeStatusRequest = $this -> generateSignatureAndEncrypt ($requestParameters);
      				$serviceUrl = 'https://amazonpay.amazon.in//payments/v1/api/chargeStatus';
      				$responseObject = $this->invokeGet ($getChargeStatusRequest, $serviceUrl);
      				$response = json_decode($responseObject['ResponseBody'], true)['response'];
      				switch ($response['transactionStatusCode']) {
      					case '001':
      						$response['transactionStatus'] = "SUCCESS";
      						break;
      					case '01':
      						$response['transactionStatus'] = "PENDING";
      						break;
      					default:
      						$response['transactionStatus'] = "FAILURE";
      						break;
      				}
      				$this->executeAfterApi ( $startTime, 'FETCH_TRANSACTION_DETAILS' );
      		} catch (Exception $e) {
      				$this->updateCountMetrics('FETCH_TRANSACTION_DETAILS_ERROR');
      				$this->executeAfterApi ( $startTime, 'FETCH_TRANSACTION_DETAILS' );
                	throw new Exception($e, 1);
      		}
      		return $response;
      }

      ######### Private Functions ##########

	private function constructPaymentUrl($queryParameters, $redirectUrl ){
		$baseUrl = $this->getBaseUrl();
		$processPaymentUrl = $baseUrl.'/initiatePayment?'.$queryParameters.'&redirectUrl='.urlencode($redirectUrl);
		return $processPaymentUrl;
	}

	/**
	 *
	 * @param string $K
	 *        	Key encryption key
	 * @param string $IV
	 *        	Initialization vector
	 * @param null|string $P
	 *        	Data to encrypt (null for authentication)
	 * @param null|string $A
	 *        	Additional Authentication Data
	 * @param int $tag_length
	 *        	Tag length
	 *
	 * @return array
	 */
	private function encrypt($K, $IV, $P = null, $A = null, $tag_length = 128) {
		assert ( is_string ( $K ), 'The key encryption key must be a binary string.' );
		$key_length = mb_strlen ( $K, '8bit' ) * 8;
		assert ( is_string ( $IV ), 'The Initialization Vector must be a binary string.' );
		assert ( is_string ( $P ) || is_null ( $P ), 'The data to encrypt must be null or a binary string.' );
		assert ( is_string ( $A ) || is_null ( $A ), 'The Additional Authentication Data must be null or a binary string.' );
		assert ( is_integer ( $tag_length ), 'Invalid tag length. Supported values are: 128, 120, 112, 104 and 96.' );
		assert ( in_array ( $tag_length, [
		128,
		120,
		112,
		104,
		96
		] ), 'Invalid tag length. Supported values are: 128, 120, 112, 104 and 96.' );
		if (version_compare ( PHP_VERSION, '7.1.0RC5' ) >= 0 && null !== $P) {
			return $this->encryptWithPHP71 ( $K, $key_length, $IV, $P, $A, $tag_length );
		}
		return $this->encryptWithPHP ( $K, $key_length, $IV, $P, $A, $tag_length );
	}

	/**
	 * This method will append the tag at the end of the ciphertext.
	 *
	 * @param string $K
	 *        	Key encryption key
	 * @param string $IV
	 *        	Initialization vector
	 * @param null|string $P
	 *        	Data to encrypt (null for authentication)
	 * @param null|string $A
	 *        	Additional Authentication Data
	 * @param int $tag_length
	 *        	Tag length
	 *
	 * @return string
	 */
	private function encryptAndAppendTag($K, $IV, $P = null, $A = null, $tag_length = 128) {
		return implode ( $this->encrypt ( $K, $IV, $P, $A, $tag_length ) );
	}
	private function getSecureRandomKey() {
		return openssl_random_pseudo_bytes ( 16 );
	}

	private function getPublicKey() {
		if( !file_exists ( dirname(__DIR__)."/AmazonPaynon/config.ini" ) ){
			throw new Exception( "Config file does not exist", 1 );
		}
		$config = parse_ini_file ( dirname(__DIR__)."/AmazonPaynon/config.ini" );
		$key =  $config[ 'publicKey' ];
		$publicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap ( $key, 64, "\n", true ) . "\n-----END PUBLIC KEY-----";
		return $publicKey;
	}

	/**
	 *
	 * @param string $K
	 *        	Key encryption key
	 * @param string $key_length
	 *        	Key length
	 * @param string $IV
	 *        	Initialization vector
	 * @param null|string $P
	 *        	Data to encrypt (null for authentication)
	 * @param null|string $A
	 *        	Additional Authentication Data
	 * @param int $tag_length
	 *        	Tag length
	 *
	 * @return array
	 */
	private function encryptWithPHP71($K, $key_length, $IV, $P = null, $A = null, $tag_length = 128) {
		$mode = 'aes-' . ($key_length) . '-gcm';
		$T = null;
		$C = openssl_encrypt ( $P, $mode, $K, OPENSSL_ZERO_PADDING, $IV, $T, $A, $tag_length / 8 );
		assert ( false !== $C, 'Unable to encrypt the data.' );
		return [
		base64_decode ( $C ),
		$T
		];
	}

	/**
	 *
	 * @param string $K
	 *        	Key encryption key
	 * @param string $key_length
	 *        	Key length
	 * @param string $IV
	 *        	Initialization vector
	 * @param null|string $P
	 *        	Data to encrypt (null for authentication)
	 * @param null|string $A
	 *        	Additional Authentication Data
	 * @param int $tag_length
	 *        	Tag length
	 *
	 * @return array
	 */
	private function encryptWithPHP($K, $key_length, $IV, $P = null, $A = null, $tag_length = 128) {
		list ( $J0, $v, $a_len_padding, $H ) = $this->common ( $K, $key_length, $IV, $A );
		$C = $this->getGCTR ( $K, $key_length, $this->getInc ( 32, $J0 ), $P );
		$u = $this->calcVector ( $C );
		$c_len_padding = $this->addPadding ( $C );
		$S = $this->getHash ( $H, $A . str_pad ( '', $v / 8, "\0" ) . $C . str_pad ( '', $u / 8, "\0" ) . $a_len_padding . $c_len_padding );
		$T = $this->getMSB ( $tag_length, self::getGCTR ( $K, $key_length, $J0, $S ) );
		return [
		$C,
		$T
		];
	}

	private function addDefaultParameters($parameters) {
		$parameters ['AWSAccessKeyId'] = $this->config ['access_key'];
		$parameters ['SignatureMethod'] = 'HmacSHA256';
		$parameters ['SignatureVersion'] = 2;
		return $parameters;
	}

	private function addParametersForEncryption($parameters) {
		$parameters ['sellerId'] = $this->config ['merchant_id'];
		$parameters ['startTime'] = time();
		return $parameters;
	}

	/**
	 * This method calculates the signature for the parameters given with the
	 * aws secret key provided.
	 *
	 * @param $parameters the
	 *        	parameters to be signed
	 * @return the signature after signing the parameers
	 */
	private function calculateSignForEncryption($parameters = array()) {
		$this->validateNotEmpty ( $parameters, "parameters" );
		$parameters = $this->addParametersForEncryption ( $parameters );
		$this->serviceUrl = 'amazonpay.amazon.in';
		//$this->serviceUrl = 'payments-in-preprod.amazon.com';
		$this->urlScheme = 'POST';
		$this->path = '/';
		return $this->signParameters ( $parameters );
	}

	private function calculateSignForVerification($parameters = array()) {
		$this->validateNotEmpty ( $parameters, "parameters" );
		$this->serviceUrl = 'amazonpay.amazon.in';
		$this->urlScheme = 'POST';
		$this->path = '/';
		return $this->signParameters ( $parameters );
	}

	/**
	 * This method return signature after signing the parameters with the given
	 * secret key.
	 *
	 * @param $parameters the
	 *        	parameters to be signed
	 * @return the calculated signature
	 */
	private function signParameters($parameters = array()) {
		$parameters = $this->urlEncodeParams ( $parameters );
		$parameters = $this->addDefaultParameters ( $parameters );
		uksort ( $parameters, 'strcmp' );
		$stringToSign = $this->calculateStringToSignV2 ( $parameters );
		$sign = $this->sign ( $stringToSign, $this->config ['secret_key'] );
		$parameters ['Signature'] = $sign;
		return $parameters;
	}

	/**
	 *
	 * @param $parameters the
	 *        	request parameters to be signed
	 * @return String to be signed
	 */
	private function calculateStringToSignV2(array $parameters) {
		$data = $this->urlScheme;
		$data .= "\n";
		$data .= $this->serviceUrl;
		$data .= "\n";
		$data .= $this->path;
		$data .= "\n";
		$data .= $this->getParametersAsString ( $parameters );
		return $data;
	}
	private function getParametersToEncrypted($parameters) {
		$parameters = $this->urlEncodeParams ( $parameters );
		unset ( $parameters ['SignatureMethod'] );
		unset ( $parameters ['SignatureVersion'] );
		return $parameters;
	}
	private function urlEncodeParams($parameters) {
		foreach ( $parameters as $key => $value ) {
			$parameters [$key] = $this->urlEncode ( $value, FALSE );
		}
		return $parameters;
	}
	private function urlEncode($value, $path) {
		$encodedString = stripslashes ( rawurlencode ( utf8_encode ( $value ) ) );
		if ($path) {
			$encodedString = str_replace ( '%2F', '/', $encoded );
		}
		return $encodedString;
	}
	private function getParametersAsString(array $parameters) {
		$queryParameters = array ();
		foreach ( $parameters as $key => $value ) {
			$queryParameters [] = $this->urlEncode ( $key, false ) . '=' . $value;
		}
		return implode ( '&', $queryParameters );
	}

	/**
	 * Computes RFC 2104-compliant HMAC signature.
	 * For more details refer this
	 * http://docs.aws.amazon.com/general/latest/gr/signature-version-2.html
	 *
	 * @param $data the
	 *        	final string to be signed
	 * @param $secretKey the
	 *        	secret key to be used for signing the request data
	 * @return the final signed string
	 */
	private static function sign($data, $secretKey) {
		return base64_encode ( hash_hmac ( 'sha256', $data, $secretKey, true ) );
	}
	private function checkConfigKeys($config) {
		foreach ( $config as $key => $value ) {
			if (array_key_exists ( $key, $this->config )) {
				$this->config [$key] = $value;
			} else {
				throw new \Exception ( 'Key ' . $key . ' is either not part of the configuration or has incorrect Key name.
			check the config array key names to match your key names of your config array', 1 );
			}
		}
	}
	private function checkForRequiredParameters($parameters, $fields) {
		foreach ( $fields as $fieldName => $mandatoryField ) {
			if ($mandatoryField) {
				$value = $this->getMandatoryField ( $fieldName, $parameters );
			} else {
				$value = $this->getField ( $fieldName, $parameters );
			}
		}
		foreach ( $parameters as $key => $value ) {
			if (! array_key_exists ( $key, $fields )) {
				throw new \Exception ( "Error with json message - provided field " . $key . " should not be part of input" );
			}
		}
	}

	/**
	 * Extract the mandatory field from the message and return the contents
	 *
	 * @param string $fieldName
	 *        	name of the field to extract
	 *
	 * @throws Exception if not found
	 *
	 * @return string field contents if found
	 */
	private function getMandatoryField($fieldName, $parameters) {
		$value = $this->getField ( $fieldName, $parameters );
		if (is_null ( $value ) || empty($value)) {
			throw new \Exception ( "Error with json message - mandatory field " . $fieldName . " cannot be found or is empty" );
		}
		return $value;
	}

	/**
	 * Extract the field if present, return null if not defined
	 *
	 * @param string $fieldName
	 *        	name of the field to extract
	 *
	 * @return string field contents if found, null otherwise
	 */
	private function getField($fieldName, $parameters) {
		if (array_key_exists ( $fieldName, $parameters )) {
			return $parameters [$fieldName];
		} else {
			return null;
		}
	}

	/**
	 *
	 * @param
	 *        	$K
	 * @param
	 *        	$key_length
	 * @param
	 *        	$IV
	 * @param
	 *        	$A
	 *
	 * @return array
	 */
	private function common($K, $key_length, $IV, $A) {
		$H = openssl_encrypt ( str_repeat ( "\0", 16 ), 'aes-' . ($key_length) . '-ecb', $K, OPENSSL_NO_PADDING | OPENSSL_RAW_DATA ); // ---
		$iv_len = $this->getLength ( $IV );
		if (96 === $iv_len) {
			$J0 = $IV . pack ( 'H*', '00000001' );
		} else {
			$s = $this->calcVector ( $IV );
			$packed_iv_len = pack ( 'N', $iv_len );
			$iv_len_padding = str_pad ( $packed_iv_len, 8, "\0", STR_PAD_LEFT );
			$hash_X = $IV . str_pad ( '', ($s + 64) / 8, "\0" ) . $iv_len_padding;
			$J0 = $this->getHash ( $H, $hash_X );
		}
		$v = $this->calcVector ( $A );
		$a_len_padding = $this->addPadding ( $A );
		return [
		$J0,
		$v,
		$a_len_padding,
		$H
		];
	}

	/**
	 *
	 * @param string $value
	 *
	 * @return int
	 */
	private function calcVector($value) {
		return (128 * ceil ( self::getLength ( $value ) / 128 )) - self::getLength ( $value );
	}

	/**
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function addPadding($value) {
		return str_pad ( pack ( 'N', self::getLength ( $value ) ), 8, "\0", STR_PAD_LEFT );
	}

	/**
	 *
	 * @param string $x
	 *
	 * @return int
	 */
	private function getLength($x) {
		return mb_strlen ( $x, '8bit' ) * 8;
	}

	/**
	 *
	 * @param int $num_bits
	 * @param int $x
	 *
	 * @return string
	 */
	private function getMSB($num_bits, $x) {
		$num_bytes = $num_bits / 8;
		return mb_substr ( $x, 0, $num_bytes, '8bit' );
	}

	/**
	 *
	 * @param int $num_bits
	 * @param int $x
	 *
	 * @return string
	 */
	private function getLSB($num_bits, $x) {
		$num_bytes = ($num_bits / 8);
		return mb_substr ( $x, - $num_bytes, null, '8bit' );
	}

	/**
	 *
	 * @param int $s_bits
	 * @param int $x
	 *
	 * @return string
	 */
	private function getInc($s_bits, $x) {
		$lsb = $this->getLSB ( $s_bits, $x );
		$X = $this->toUInt32Bits ( $lsb ) + 1;
		$res = $this->getMSB ( self::getLength ( $x ) - $s_bits, $x ) . pack ( 'N', $X );
		return $res;
	}

	/**
	 *
	 * @param string $bin
	 *
	 * @return mixed
	 */
	private function toUInt32Bits($bin) {
		list ( , $h, $l ) = unpack ( 'n*', $bin );
		return $l + ($h * 0x010000);
	}

	/**
	 *
	 * @param
	 *        	$X
	 * @param
	 *        	$Y
	 *
	 * @return string
	 */
	private function getProduct($X, $Y) {
		$R = pack ( 'H*', 'E1' ) . str_pad ( '', 15, "\0" );
		$Z = str_pad ( '', 16, "\0" );
		$V = $Y;
		$parts = str_split ( $X, 4 );
		$x = sprintf ( '%032b%032b%032b%032b', $this->toUInt32Bits ( $parts [0] ), $this->toUInt32Bits ( $parts [1] ), $this->toUInt32Bits ( $parts [2] ), $this->toUInt32Bits ( $parts [3] ) );
		$lsb_mask = "\1";
		for($i = 0; $i < 128; $i ++) {
			if ($x [$i]) {
				$Z = $this->getBitXor ( $Z, $V );
			}
			$lsb_8 = mb_substr ( $V, - 1, null, '8bit' );
			if (ord ( $lsb_8 & $lsb_mask )) {
				$V = $this->getBitXor ( $this->shiftStringToRight ( $V ), $R );
			} else {
				$V = $this->shiftStringToRight ( $V );
			}
		}
		return $Z;
	}

	/**
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	private function shiftStringToRight($input) {
		$width = 4;
		$parts = array_map ( 'self::toUInt32Bits', str_split ( $input, $width ) );
		$runs = count ( $parts );
		for($i = $runs - 1; $i >= 0; $i --) {
			if ($i) {
				$lsb1 = $parts [$i - 1] & 0x00000001;
				if ($lsb1) {
					$parts [$i] = ($parts [$i] >> 1) | 0x80000000;
					$parts [$i] = pack ( 'N', $parts [$i] );
					continue;
				}
			}
			$parts [$i] = ($parts [$i] >> 1) & 0x7FFFFFFF;
			$parts [$i] = pack ( 'N', $parts [$i] );
		}
		$res = implode ( '', $parts );
		return $res;
	}

	/**
	 *
	 * @param string $H
	 * @param string $X
	 *
	 * @return mixed
	 */
	private function getHash($H, $X) {
		$Y = [ ];
		$Y [0] = str_pad ( '', 16, "\0" );
		$num_blocks = ( int ) (mb_strlen ( $X, '8bit' ) / 16);
		for($i = 1; $i <= $num_blocks; $i ++) {
			$Y [$i] = $this->getProduct ( $this->getBitXor ( $Y [$i - 1], mb_substr ( $X, ($i - 1) * 16, 16, '8bit' ) ), $H );
		}
		return $Y [$num_blocks];
	}

	/**
	 *
	 * @param string $K
	 * @param int $key_length
	 * @param string $ICB
	 * @param string $X
	 *
	 * @return string
	 */
	private function getGCTR($K, $key_length, $ICB, $X) {
		if (empty ( $X )) {
			return '';
		}
		$n = ( int ) ceil ( $this->getLength ( $X ) / 128 );
		$CB = [ ];
		$Y = [ ];
		$CB [1] = $ICB;
		for($i = 2; $i <= $n; $i ++) {
			$CB [$i] = $this->getInc ( 32, $CB [$i - 1] );
		}
		$mode = 'aes-' . ($key_length) . '-ecb';
		for($i = 1; $i < $n; $i ++) {
			$C = openssl_encrypt ( $CB [$i], $mode, $K, OPENSSL_NO_PADDING | OPENSSL_RAW_DATA );
			$Y [$i] = $this->getBitXor ( mb_substr ( $X, ($i - 1) * 16, 16, '8bit' ), $C );
		}
		$Xn = mb_substr ( $X, ($n - 1) * 16, null, '8bit' );
		$C = openssl_encrypt ( $CB [$n], $mode, $K, OPENSSL_NO_PADDING | OPENSSL_RAW_DATA );
		$Y [$n] = $this->getBitXor ( $Xn, $this->getMSB ( $this->getLength ( $Xn ), $C ) );
		return implode ( '', $Y );
	}

	/**
	 *
	 * @param string $o1
	 * @param string $o2
	 *
	 * @return string
	 */
	private function getBitXor($o1, $o2) {
		$xorWidth = PHP_INT_SIZE;
		$o1 = str_split ( $o1, $xorWidth );
		$o2 = str_split ( $o2, $xorWidth );
		$res = '';
		$runs = count ( $o1 );
		for($i = 0; $i < $runs; $i ++) {
			$res .= $o1 [$i] ^ $o2 [$i];
		}
		return $res;
	}

	private function getBaseUrl(){
		$baseUrl = $this->getBaseUrlDynamically();
		if(is_null($baseUrl)){
			$baseUrl = $this->config['base_url'];
		}
		return $baseUrl;
	}

	//TODO
	private function getBaseUrlDynamically(){
		return null;
	}

	private function setParametersAndPost($parameters, $fieldMappings, $requestParameters)
	{
		/* For loop to take all the non empty parameters in the $requestParameters and add it into the $parameters array,
		 * if the keys are matched from $requestParameters array with the $fieldMappings array
		 */
		foreach ($requestParameters as $param => $value) {
			if(!is_array($value)) {
				$value = trim($value);
			}
			if (array_key_exists($param, $fieldMappings) && $value!='') {
                        if(is_array($value)) {
                              $n = sizeof($value);
                              for($i = 1; $i<=$n; $i++ ) {
                                    $parameters[$fieldMappings[$param].'.'.$i] = $value[$i-1]; 
                              }   
                        }
				// For variables that are boolean values, strtolower them
                        elseif($this->checkIfBool($value))
				{
					$value = strtolower($value);
				}
                        if(!is_array($value)){
                              $parameters[$fieldMappings[$param]] = $value;
                        }
			}
            }
            $parameters = $this->setDefaultValues($parameters, $fieldMappings, $requestParameters);
            $responseObject = $this->calculateSignatureAndPost($parameters);
            return $responseObject;
	}

	/* If merchant_id is not set via the requestParameters array then it's taken from the config array
	 *
	 * Set the platform_id if set in the config['platform_id'] array
	 *
	 * If currency_code is set in the $requestParameters and it exists in the $fieldMappings array, strtoupper it
	 * else take the value from config array if set
	 */
	private function setDefaultValues($parameters, $fieldMappings, $requestParameters){
		if (empty($requestParameters['merchant_id']))
		$parameters['SellerId'] = $this->config['merchant_id'];
		if (array_key_exists('platform_id', $fieldMappings)) {
			if (empty($requestParameters['platform_id']) && !empty($this->config['platform_id']))
			$parameters[$fieldMappings['platform_id']] = $this->config['platform_id'];
		}
		if (array_key_exists('currency_code', $fieldMappings)) {
			if (!empty($requestParameters['currency_code'])) {
				$parameters[$fieldMappings['currency_code']] = strtoupper($requestParameters['currency_code']);
			} else {
				$parameters[$fieldMappings['currency_code']] = strtoupper($this->config['currency_code']);
			}
		}
		return $parameters;
	}

	/* calculateSignatureAndPost - convert the Parameters array to string and curl POST the parameters to MWS */
	private function calculateSignatureAndPost($parameters){
		// Call the signature and Post function to perform the actions. Returns XML in array format
		$parametersString = $this->calculateSignatureAndParametersToString($parameters);
		// POST using curl the String converted Parameters
		$response = $this->invokeGet($parametersString, $this->mwsServiceUrl);
		return $response;
	}

	private function calculateSignatureAndParametersToString($parameters = array()){
		$parameters['Timestamp'] = $this->getFormattedTimestamp();
		$this->createServiceUrl();
		$parameters = $this->signParameters($parameters);
		$parameters['Signature'] = $this->urlEncode($parameters['Signature'], false);
		$parameters = $this->getParametersAsString($parameters);
		return $parameters;
	}

	/* invokeGet takes the parameters and invokes the httpGet function to GET the response
	 * Exponential retries on error 500 and 503
	 * The response from the GET is an XML which is converted to Array
	 */
	private function invokeGet($parameters, $endpoint){
		$response       = array();
		$statusCode     = 200;
		$this->success = false;
		// Submit the request and read response body
		try {
			$shouldRetry = true;
			$retries     = 0;
			do {
				try {
					$this->constructUserAgentHeader();
					$httpCurlRequest = new HttpCurl($this->config);
					$response = $httpCurlRequest->httpGet($endpoint.'?'.$parameters, $this->userAgent);
					$curlResponseInfo = $httpCurlRequest->getCurlResponseInfo();
					$statusCode = $curlResponseInfo["http_code"];

					$response = array(
                        'Status' => $statusCode,
                        'ResponseBody' => $response
					);
					$statusCode = $response['Status'];
					if ($statusCode == 200) {
						$shouldRetry    = false;
						$this->success = true;
					} elseif ($statusCode == 500 || $statusCode == 503) {
						$shouldRetry = true;
						if ($shouldRetry && strtolower($this->config['handle_throttle'])) {
							$this->pauseOnRetry(++$retries, $statusCode);
						}
					} else {
						$shouldRetry = false;
					}
				} catch (\Exception $e) {
					throw $e;
				}
			} while ($shouldRetry);
		} catch (\Exception $se) {
			throw $se;
		}
		return $response;
	}

	/* Exponential sleep on failed request
	 * @param retries current retry
	 * @throws Exception if maximum number of retries has been reached
	 */

	private function pauseOnRetry($retries, $status)
	{
		if ($retries <= self::MAX_ERROR_RETRY) {
			$delay = (int) (pow(4, $retries) * 100000);
			usleep($delay);
		} else {
			throw new \Exception('Error Code: '. $status.PHP_EOL.'Maximum number of retry attempts - '. $retries .' reached');
        }
    }

	/* Create the User Agent Header sent with the POST request */
	private function constructUserAgentHeader(){
		$this->userAgent = $this->quoteApplicationName($this->config['application_name']) . '/' . $this->quoteApplicationVersion($this->config['application_version']);
		$this->userAgent .= ' (';
		$this->userAgent .= 'Language=PHP/' . phpversion();
		$this->userAgent .= '; ';
		$this->userAgent .= 'Platform=' . php_uname('s') . '/' . php_uname('m') . '/' . php_uname('r');
		$this->userAgent .= '; ';
		$this->userAgent .= 'MWSClientVersion=2.1.0';
		$this->userAgent .= ')';
	}

	/* Collapse multiple whitespace characters into a single ' ' and backslash escape '\',
			* and '/' characters from a string.
			* @param $s
			* @return string
			*/
			private function quoteApplicationName($s)
			{
				$quotedString = preg_replace('/ {2,}|\s/', ' ', $s);
				$quotedString = preg_replace('/\\\\/', '\\\\\\\\', $quotedString);
				$quotedString = preg_replace('/\//', '\\/', $quotedString);
				return $quotedString;
			}
			/* Collapse multiple whitespace characters into a single ' ' and backslash escape '\',
			 * and '(' characters from a string.
			 *
			 * @param $s
			 * @return string
			 */
			private function quoteApplicationVersion($s){
				$quotedString = preg_replace('/ {2,}|\s/', ' ', $s);
				$quotedString = preg_replace('/\\\\/', '\\\\\\\\', $quotedString);
				$quotedString = preg_replace('/\\(/', '\\(', $quotedString);
				return $quotedString;
			}

			/* Create MWS service URL and the Endpoint path */
			private function createServiceUrl(){
				$this->modePath = strtolower($this->config['sandbox']) ? 'OffAmazonPayments_Sandbox' : 'OffAmazonPayments';
				$this->serviceUrl  = 'mws.amazonservices.in';
				$this->mwsServiceUrl   = 'https://' . $this->serviceUrl . '/' . $this->modePath . '/2013-01-01';
				$this->path = '/' . $this->modePath . '/2013-01-01';
				$this->urlScheme = 'GET';
			}

			private function getFormattedTimestamp(){
				return gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());
			}

			/* checkIfBool - checks if the input is a boolean */

			private function checkIfBool($string){
				$string = strtolower ( $string );
				return in_array ( $string, array ( 'true', 'false' ) );
			}

			private function validateNotNull($value, $message) {
				if (is_null ( $value )) {
					throw new InvalidArgumentException ( $message . ' cannot be null.' );
				}
			}

			private function validateNotEmpty($value, $message) {
				if (empty ( $value )) {
					throw new InvalidArgumentException ( $message . ' cannot be empty.' );
				}
			}

			/* toArray() - Converts IPN [Message] field to associative array
			 * @return response in array format
			 */

			private function toArray($response)
			{
				$response = $this->simpleXmlObject($response);
				// Converting the SimpleXMLElement Object to array()
				$response = json_encode($response);
				$response = json_decode($response, true);
				return $response;
			}

			private function simpleXmlObject($response)
			{
				// Getting the HttpResponse Status code to the output as a string
				$status = strval($response['Status']);

				// Getting the Simple XML element object of the XML Response Body
				$response = simplexml_load_string((string) $response['ResponseBody']);

				// Adding the HttpResponse Status code to the output as a string
				$response->addChild('ResponseStatus', $status);

				return $response;
			}


			private function execInBackground($cmd) {
				if ( substr ( php_uname(), 0, 7 ) == "Windows" ){
					pclose ( popen ( "start /B ". $cmd, "r" ) );
				}
				else {
					$httpCurlRequest = new \HttpCurl();
					exec ( $cmd." > /dev/null &" );
				}
			}

			private function updateLatencyMetrics($totalTime, $operation){
				$file = dirname(__DIR__).'/AmazonPaynon/metrics/latencyMetrics.txt';
				$sellerId = $this->config ['merchant_id'];
				file_put_contents ( $file, "$sellerId $operation TotalTime $totalTime  \n", FILE_APPEND | LOCK_EX );
			}

			private function updateCountMetrics($metrics){
				$file = dirname(__DIR__).'/AmazonPaynon/metrics/countMetrics.txt';
				$sellerId = $this->config ['merchant_id'];
				file_put_contents ( $file, "$sellerId $metrics 1 \n", FILE_APPEND | LOCK_EX );
			}

			private function microtime_float()
			{
				list ( $usec, $sec ) = explode ( " ", microtime() );
				return ( (float)$usec + (float)$sec );
			}

			private function checkWritePermissions(){
				if(!(is_writable(dirname(__DIR__).'/AmazonPaynon/metrics'))){
					throw new Exception("Metrics Directory is not writable", 1);
				}
				if(!(is_writable(dirname(__DIR__).'/AmazonPaynon/config.ini'))){
					throw new Exception("config.ini is not writable", 1);
					
				}
			}

			private function executeAfterApi($startTime, $operation){
				$totalTime = $this->microtime_float() - $startTime;
				$this -> updateLatencyMetrics ( $totalTime, $operation );
				$this->execInBackground ( "php recordPublisher.php abc" );
				$this->execInBackground ( "php dynamicConfig.php" );
			}
		}