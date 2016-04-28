<?PHP

/** 
 * ControllerName : CustomerController.
 * Maintains a list of functions used for CustomerController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Services\OzonetelResponse as OzonetelResponse;
use App\Services\OzonetelCollectDtmf as OzonetelCollectDtmf;
use App\Services\OzontelOutboundCall as OzontelOutboundCall;
use App\Sms\CustomerSms as CustomerSms;

use Guzzle\Http\Client;

class OzonetelsController extends \BaseController {

	protected $ozonetelResponse;
	protected $ozonetelCollectDtmf;
	protected $ozontelOutboundCall;
	protected $customersms;

	public function __construct(OzonetelResponse $ozonetelResponse,OzonetelCollectDtmf $ozonetelCollectDtmf,OzontelOutboundCall $ozontelOutboundCall,CustomerSms $customersms) {

		$this->ozonetelResponse	=	$ozonetelResponse;
		$this->ozonetelCollectDtmf	=	$ozonetelCollectDtmf;
		$this->ozontelOutboundCall	=	$ozontelOutboundCall;
		$this->customersms 				=	$customersms;

	}

	public function freeVendor(){	

		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {

			$this->addCapture($_REQUEST);
		    $this->ozonetelResponse->addPlayText("This call is recorderd for quality purpose");
		    $this->ozonetelCollectDtmf = new OzonetelCollectDtmf(); //initiate new collect dtmf object
		    $this->ozonetelCollectDtmf->addPlayText("Please dial the extension number");
		    $this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'GotDTMF') {
	    	if (isset($_REQUEST['data']) && $_REQUEST['data'] != '') {

	    		$extension = (int)$_REQUEST['data'];

	    		if($extension < 1 || $extension > 25){

	    			$this->ozonetelCollectDtmf = new OzonetelCollectDtmf(); //initiate new collect dtmf object
		    		$this->ozonetelCollectDtmf->addPlayText("You have dailed wrong extension number please dial correct extension number");
		    		$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);
		 
	    		}else{

	    			$extension = (string) $extension;

	    			$finderDetails = $this->getFinderDetails($_REQUEST['called_number'],$extension);
		   
			    	if($finderDetails){
			    		$phone = $finderDetails->finder->contact['phone'];
			    		$phone = explode(',', $phone);
			    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);//(string)trim($phone[0]);
			    		$this->ozonetelResponse->addDial($contact_no,"true");
			    		$this->updateCapture($_REQUEST,$finderDetails->finder->_id,$extension,$add_count = true);
			    	}else{
			    		$this->ozonetelResponse->addPlayText("You have dailed wrong extension number");
			    		$this->ozonetelResponse->addHangup();
			    	}
	    		}

	    	}
    	}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Dial') {

		    if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'not_answered') {

				$capture = $this->getCapture($_REQUEST['sid']);

				if($capture->count > 1){
					$this->ozonetelResponse->addHangup();
				}else{

					$finder = Finder::findOrFail($capture->finder_id);

					if($finder){

						$commercial_type = (int)$finder->commercial_type;

				    	$premium_vendor = array(1,3);

				    	if(in_array($commercial_type, $premium_vendor)){

	                        $this->ozonetelResponse->addPlayText("Call diverted to another number");
	                        $this->ozonetelResponse->addDial('02261222225',"true");
	                        $this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true);
	                        
	                    }else{

                            $phone = $finder->contact['phone'];
                            $phone = explode(',', $phone);

                            if(isset($phone[1]) && $phone[1] != ''){
                                $contact_no = (string)trim($phone[1]);
                                $this->ozonetelResponse->addPlayText("Call diverted to another number");
                                $this->ozonetelResponse->addDial($contact_no,"true");
                                $this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true);
                            }else{
                                $this->ozonetelResponse->addHangup();
                            }

	                    }

	                }else{
	                    
	                    $this->ozonetelResponse->addHangup();
	                }

				}

			}elseif(isset($_REQUEST['status']) && $_REQUEST['status'] == 'answered') {

				$update_capture = $this->updateCapture($_REQUEST);
				$this->ozonetelResponse->addHangup();
		    	
			}else{

				$this->ozonetelResponse->addHangup();
			}

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Hangup') {

		    $update_capture = $this->updateCapture($_REQUEST);
		    $this->ozonetelResponse->addHangup();

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Disconnect') {

		    $update_capture = $this->updateCapture($_REQUEST);

		}else {

		    $this->ozonetelResponse->addHangup();
		}
		
		$this->ozonetelResponse->send();

	}

	public function paidVendor(){	

		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {

		    $finderDetails = $this->getFinderDetails($_REQUEST['called_number']);
   
	    	if($finderDetails){

	    		$this->ozonetelResponse->addPlayText("This call is recorderd for quality purpose");

	    		$phone = $finderDetails->finder->contact['phone'];
	    		$phone = explode(',', $phone);
	    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);//(string)trim($phone[0]);
	    		$this->ozonetelResponse->addDial($contact_no,"true");
	    		$add_capture = $this->addCapture($_REQUEST,$finderDetails->finder->_id,$add_count = true);
	    	}else{
	    		$this->ozonetelResponse->addHangup();
	    	}

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Dial') {

			if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'not_answered') {

				$capture = $this->getCapture($_REQUEST['sid']);

				if($capture->count > 1){
					$this->ozonetelResponse->addHangup();
				}else{

					$finder = Finder::findOrFail($capture->finder_id);

					if($finder){

						$commercial_type = (int)$finder->commercial_type;

				    	$premium_vendor = array(1,3);

				    	if(in_array($commercial_type, $premium_vendor)){

	                        $this->ozonetelResponse->addPlayText("Call diverted to another number");
	                        $this->ozonetelResponse->addDial('02261222225',"true");
	                        $this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true);
	                        
	                    }else{

                            $phone = $finder->contact['phone'];
                            $phone = explode(',', $phone);

                            if(isset($phone[1]) && $phone[1] != ''){
                                $contact_no = (string)trim($phone[1]);
                                $this->ozonetelResponse->addPlayText("Call diverted to another number");
                                $this->ozonetelResponse->addDial($contact_no,"true");
                                $this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true);
                            }else{
                                $this->ozonetelResponse->addHangup();
                            }

	                    }

	                }else{
	                    
	                    $this->ozonetelResponse->addHangup();
	                }
				}

			}elseif(isset($_REQUEST['status']) && $_REQUEST['status'] == 'answered') {

				$update_capture = $this->updateCapture($_REQUEST);
				$this->ozonetelResponse->addHangup();
		    	
			}else{

				$this->ozonetelResponse->addHangup();
			}

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Hangup') {

		    $update_capture = $this->updateCapture($_REQUEST);
		    $this->ozonetelResponse->addHangup();

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Disconnect') {

		    $update_capture = $this->updateCapture($_REQUEST);

		}else {

		    $this->ozonetelResponse->addHangup();
		}
		
		$this->ozonetelResponse->send();

	}

	public function getFinderDetails($phone_number,$extension = false){

		$ozonetelno = array();

		try {

			if(!$extension){
				$ozonetelno = Ozonetelno::with('finder')->active()->where('phone_number','=',(string) $phone_number)->where('finder_id', 'exists', true)->first();
			}else{
				$ozonetelno = Ozonetelno::with('finder')->active()->where('phone_number','=',(string) $phone_number)->where('finder_id', 'exists', true)->where('extension','=',(string) $extension)->first();
			}

			if(!empty($ozonetelno)){
				return $ozonetelno;
			}else{
				return false;
			}

		} catch (Exception $e) {
			return false;
		}

	}


	public function addCapture($data,$finder_id = false,$add_count = false){
		
		$ozonetel_capture = new Ozonetelcapture();
		$ozonetel_capture->_id = Ozonetelcapture::max('_id') + 1;
		$ozonetel_capture->ozonetel_unique_id = $data['sid'];
		$ozonetel_capture->ozonetel_no = (string) $data['called_number'];
		$ozonetel_capture->customer_contact_no = (string)$data['cid_e164'];
		$ozonetel_capture->customer_contact_circle = (string)$data['circle'];
		$ozonetel_capture->customer_contact_operator = (string)$data['operator'];
		$ozonetel_capture->customer_contact_type = (string)$data['cid_type'];
		$ozonetel_capture->customer_cid = (string)$data['cid'];

		if($finder_id){
			$ozonetel_capture->finder_id = (int) $finder_id;
		}

		if($add_count){
			$ozonetel_capture->count = 1;
		}

		$ozonetel_capture->call_status = "called";
		$ozonetel_capture->status = "1";
		$ozonetel_capture->save();

		return $ozonetel_capture;
	}

	public function updateCapture($data,$finder_id = false,$extension = false,$add_count = false){

		$ozonetel_capture = Ozonetelcapture::where('ozonetel_unique_id','=',$data['sid'])->first();

		if($ozonetel_capture){

			if($finder_id){
				$ozonetel_capture->finder_id = (int) $finder_id;
				$ozonetel_capture->count = 0;
			}

			if($extension){
				$ozonetel_capture->extension = $extension;
			}

			if($add_count){
				$ozonetel_capture->count += 1;
			}


			if(isset($data['status']) && $data['status'] != ''){
				if($data['status'] != 'answered'){

					$ozonetel_capture->call_status = "not_answered";
					$ozonetel_capture->message = $data['message'];
					$ozonetel_capture->telco_code = $data['telco_code'];
					$ozonetel_capture->rec_md5_checksum = $data['rec_md5_checksum'];
					$ozonetel_capture->pickduration = $data['pickduration'];
					
				}else{
					
					$ozonetel_capture->call_status = "answered";
					$ozonetel_capture->call_duration = $data['callduration'];
					$ozonetel_capture->ozone_url_record = $data['data'];
					$ozonetel_capture->message = $data['message'];
					$ozonetel_capture->telco_code = $data['telco_code'];
					$ozonetel_capture->rec_md5_checksum = $data['rec_md5_checksum'];
					$ozonetel_capture->pickduration = $data['pickduration'];
					
					/*$aws_filename = time().$data['sid'].'.wav';
					$ozonetel_capture->aws_file_name = $aws_filename;
					$this->addToAws($data['data'],$aws_filename);*/
				}
			}

			$ozonetel_capture->update();

			return $ozonetel_capture;

		}else{

			return false;
		}
	}

	public function getCapture($sid){

		try {

			$ozonetel_capture = Ozonetelcapture::where('ozonetel_unique_id','=',$sid)->first();

			if(!empty($ozonetel_capture)){
				return $ozonetel_capture;
			}else{
				return false;
			}

		} catch (Exception $e) {
			return false;
		}

	}

	public function addToAws($ozone_fileurl,$aws_filename){

		$folder_path = public_path().'/ozone/';
		$file_path = $this->createFolder($folder_path).$aws_filename;
		
		$createFolder = $this->createFolder($folder_path);
		$copyFromOzone = $this->copyFromOzone($ozone_fileurl,$file_path);


		$s3 = AWS::get('s3');
		$s3->putObject(array(
		    'Bucket'     => Config::get('app.aws.bucket'),
		    'Key'        => Config::get('app.aws.ozonetel.path').$aws_filename,
		    'SourceFile' => $file_path,
		));

		unlink($file_path);
		
		return $s3;
	}

	public function copyFromOzone($fromUrl,$toFile) {

	    try {
	    	fopen($toFile, 'w');
	        $client = new Guzzle\Http\Client();
	        $response = $client->get($fromUrl)->setResponseBody($toFile)->send();
	        chmod($toFile, 0777);
	        return true;

	    } catch (Exception $e) {
	        // Log the error or something
	        return false;
	    }

	}

	public function createFolder($path){

		if(!is_dir($path)){
			mkdir($path, 0777);
			chmod($path, 0777);
		}	

		return $path;
	}

	public function outboundCallSend($phone_no){

		$trial_id = 15961;

	/*	$booktrial = Booktrial::find((int) $trial_id);

		$slot_date 							=	date('d-m-Y', strtotime($booktrial->schedule_date));
		$schedule_date_starttime 			=	strtoupper($slot_date ." ".$booktrial->schedule_slot_start_time);

		echo"<pre>";print_r($schedule_date_starttime);

		$date = '';//\Carbon\Carbon::createFromFormat('j F Y', $schedule_date_starttime);
		$hour = \Carbon\Carbon::createFromFormat('g', $schedule_date_starttime);
		$min = \Carbon\Carbon::createFromFormat('i', $schedule_date_starttime);
		$ante = \Carbon\Carbon::createFromFormat('a', $schedule_date_starttime);

		$ante = ($ante == 'am') ? 'a m' : 'p m';
		$min = ($min == 00) ? ' ' : $min;
		
		$datetime = $date.' ,'.$hour.' '.$min.' '.$ante;

		echo"<pre>";print_r($datetime);exit;*/

		$result = $this->ozontelOutboundCall->call($phone_no,$trial_id);

		return  Response::json($result, $result['status']);

	}

	public function outbound($trial_id){

		$booktrial = Booktrial::find((int) $trial_id);
		$phone_no = $booktrial->customer_phone;
		$result = $this->ozontelOutboundCall->call($phone_no,$trial_id);

		return  Response::json($result, $result['status']);

	}

	public function outboundCallRecive($trial_id){

		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {

			$booktrial = Booktrial::find((int) $trial_id);

			$slot_date 			=	date('d-m-Y', strtotime($booktrial->schedule_date));
			$datetime 			=	strtoupper($slot_date ." ".$booktrial->schedule_slot_start_time);

			$this->ozonetelResponse->addPlayText("Hi ".$booktrial->customer_name.", this is regarding a workout session booked by you through Fitternity at ".$booktrial->finder_name."on ".$datetime." , ",3);

			$this->ozonetelCollectDtmf = new OzonetelCollectDtmf();

			$this->ozonetelCollectDtmf->addPlayText($this->outboundIvr(),3);

		   	$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);

		   	$add_outbound = $this->addOutbound($_REQUEST,$booktrial->finder_id,$trial_id);

		   	$this->ozonetelResponse->addHangup();

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'GotDTMF') {

			if (isset($_REQUEST['data']) && $_REQUEST['data'] != '') {

				$input = (int)$_REQUEST['data'];

				$ivr_status = array(1 =>'confirm',2 =>'cancel',3 =>'reschedule',4 =>'repeat');

				if(array_key_exists($input, $ivr_status))
				{
					switch ($input) {
						case 1:
							$this->ozonetelResponse->addPlayText("Thank you for your confirmation, we hope you have a great workout",3);
							$this->ozonetelResponse->addHangup();
							break;
						case 2:
							$this->ozonetelResponse->addPlayText("Thank you for your request, your session is now cancel",3);
							$this->ozonetelResponse->addHangup();
							break;
						case 3:
							$this->ozonetelResponse->addPlayText("Please hold, your call is being transfer to our fitness concierge",3);
							$this->ozonetelResponse->addDial('09773348762',"true");
							$this->ozonetelResponse->addHangup();
							break;
						case 4:
							$this->ozonetelCollectDtmf = new OzonetelCollectDtmf(); //initiate new collect dtmf object
		    				$this->ozonetelCollectDtmf->addPlayText($this->outboundIvr(),3);
		    				$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);
							$this->ozonetelResponse->addHangup();
							break;
						default:
							$this->ozonetelResponse->addHangup();
							break;
					}

					$booktrial = Booktrial::find((int) $trial_id);
					$booktrial->update(array('ivr_status'=>$input));

					$update_outbound = $this->updateOutbound($_REQUEST);

				}else{

					$this->ozonetelCollectDtmf = new OzonetelCollectDtmf(); //initiate new collect dtmf object
		    		$this->ozonetelCollectDtmf->addPlayText("wrong extension, please dial correct extension number",3);
		    		$this->ozonetelCollectDtmf->addPlayText($this->outboundIvr(),3);
		    		$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);
				}
	    	}else{

	    		$this->ozonetelResponse->addHangup();
	    	}

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Dial') {

			if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'not_answered') {

				$update_outbound = $this->updateOutbound($_REQUEST);
				$this->ozonetelResponse->addHangup();

			}elseif(isset($_REQUEST['status']) && $_REQUEST['status'] == 'answered') {

				$update_outbound = $this->updateOutbound($_REQUEST);
				$this->ozonetelResponse->addHangup();
		    	
			}else{

				$this->ozonetelResponse->addHangup();
			}

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Hangup') {

		    $update_outbound = $this->updateOutbound($_REQUEST);
		    $this->ozonetelResponse->addHangup();

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Disconnect') {

		    $update_outbound = $this->updateOutbound($_REQUEST);
		    $this->ozonetelResponse->addHangup();

		}else {

		    $this->ozonetelResponse->addHangup();
		}
		
		$this->ozonetelResponse->send();

	}


	public function outboundIvr(){

		
		$ivr = 'to confirm if you are going,press 1,

				to cancel the booking,press 2,

				to reschedule or for any query,press 3,

				to repeat,Press 4';

		return $ivr;
	}

	public function addOutbound($data,$finder_id,$trial_id){
		
		$ozonetel_outbound = new Ozoneteloutbound();
		$ozonetel_outbound->_id = Ozoneteloutbound::max('_id') + 1;
		$ozonetel_outbound->ozonetel_unique_id = $data['sid'];
		$ozonetel_outbound->finder_id = (int) $finder_id;
		$ozonetel_outbound->trial_id = (int) $trial_id;
		$ozonetel_outbound->ozonetel_no = (string) $data['called_number'];
		$ozonetel_outbound->customer_contact_no = (string)$data['cid_e164'];
		$ozonetel_outbound->customer_contact_circle = (string)$data['circle'];
		$ozonetel_outbound->customer_contact_operator = (string)$data['operator'];
		$ozonetel_outbound->customer_contact_type = (string)$data['cid_type'];
		$ozonetel_outbound->customer_cid = (string)$data['cid'];
		$ozonetel_outbound->outbound_sid = (string)$data['outbound_sid'];

		$ozonetel_outbound->call_status = "called";
		$ozonetel_outbound->status = "1";
		$ozonetel_outbound->save();

		return $ozonetel_outbound;
	}

	public function updateOutbound($data){

		$ozonetel_outbound = Ozoneteloutbound::where('ozonetel_unique_id','=',$data['sid'])->first();

		if($ozonetel_outbound){

			if(isset($data['status']) && $data['status'] != ''){
				if($data['status'] != 'answered'){

					$ozonetel_outbound->call_status = "not_answered";
					$ozonetel_outbound->message = $data['message'];
					$ozonetel_outbound->telco_code = $data['telco_code'];
					$ozonetel_outbound->rec_md5_checksum = $data['rec_md5_checksum'];
					$ozonetel_outbound->call_duration = $data['callduration'];
					$ozonetel_outbound->pickduration = $data['pickduration'];
					
				}else{
					
					$ozonetel_outbound->call_status = "answered";
					$ozonetel_outbound->call_duration = $data['callduration'];
					$ozonetel_outbound->ozone_url_record = $data['data'];
					$ozonetel_outbound->message = $data['message'];
					$ozonetel_outbound->telco_code = $data['telco_code'];
					$ozonetel_outbound->rec_md5_checksum = $data['rec_md5_checksum'];
					$ozonetel_outbound->pickduration = $data['pickduration'];
				}
			}

			if( isset($data['event']) && $data['event'] == 'GotDTMF' && isset($data['data']) && $data['data'] != ''){
				$ozonetel_outbound->ivr_status = (int) $data['data'];
			}

			if( isset($data['event']) && $data['event'] == 'Hangup' || $data['event'] == 'Disconnect' && isset($data['total_call_duration']) && $data['total_call_duration'] != ''){
				$ozonetel_outbound->total_call_duration = (int) $data['total_call_duration'];
			}

			$ozonetel_outbound->update();

			return $ozonetel_outbound;

		}else{

			return false;
		}
	}

	public function sms(){

		try{

			$response = $this->misscall('sms');

			if($response['status'] == 200){
		
				$ozonetel_missedcall = $response['ozonetel_missedcall'];

				if($ozonetel_missedcall->customer_number != ''){

					$label = 'Additional10Discount';

					$message = "Congratualtions! You've unlocked an Additional 10% discount on Fitternity discounts of upto 50% - Our Fitness Concierge will get in touch with you within 48hrs to get you the membership of your choice. You + Fitternity = A Fitter You!";
				
					$data = array();

					$data['label'] = $label;
					$data['message'] = $message;
					$data['to'] = $ozonetel_missedcall->cid;

					$update['sms_general'] = $this->customersms->generalSms($data);
					$update['sms_message'] = $message;
					$update['label'] = $label;
				
				}else{

					$update['error_message'] = 'contact number is null';
				}

				$ozonetel_missedcall->update($update);

				$response = array('status'=>200,'message'=>'success');

			}

		}catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            
            Log::error($e);
            
        }

        return Response::json($response,$response['status']);

	}

	public function smsb(){

		try{

			$response = $this->misscall('sms');

			if($response['status'] == 200){
		
				$ozonetel_missedcall = $response['ozonetel_missedcall'];

				if($ozonetel_missedcall->customer_number != ''){

					$label = 'CrossfitAtFort';

					$message = 'Thank you for your request! We will call you shortly with the different offers on Crossfit at Fort.';
				
					$data = array();

					$data['label'] = $label;
					$data['message'] = $message;
					$data['to'] = $ozonetel_missedcall->cid;

					$update['sms_general'] = $this->customersms->generalSms($data);
					$update['sms_message'] = $message;
					$update['label'] = $label;
				
				}else{

					$update['error_message'] = 'contact number is null';
				}

				$ozonetel_missedcall->update($update);

				$response = array('status'=>200,'message'=>'success');

			}

		}catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            
            Log::error($e);
            
        }

        return Response::json($response,$response['status']);

	}

	public function misscall($type){

		try{

			$request = $_REQUEST;

			$ozonetel_missedcall = new Ozonetelmissedcall();
			$ozonetel_missedcall->_id = Ozonetelmissedcall::max('_id') + 1;
			$ozonetel_missedcall->type = $type;
			$ozonetel_missedcall->status = "1";
			$ozonetel_missedcall->cid = isset($request['cid']) ? preg_replace("/[^0-9]/", "", $request['cid']) : '';
			$ozonetel_missedcall->customer_number = isset($request['cid']) ? preg_replace("/[^0-9]/", "", $request['cid']) : '';
			$ozonetel_missedcall->sid = isset($request['sid']) ? $request['sid'] : '';
			$ozonetel_missedcall->called_number = isset($request['called_number']) ? $request['called_number'] : '';
			$ozonetel_missedcall->circle = isset($request['circle']) ? $request['circle'] : '';
			$ozonetel_missedcall->operator = isset($request['operator']) ? $request['operator'] : '';
			$ozonetel_missedcall->call_time = isset($request['call_time']) ? $request['call_time'] : ''; 
			$ozonetel_missedcall->called_at = isset($request['call_time']) ? strtotime($request['call_time']) : '';
			$ozonetel_missedcall->save();

			if($type == 'confirm' || $type == 'cancel' || $type == 'reschedule'){

				$missedcall_status = array('confirm'=>1,'cancel'=>2,'reschedule'=>3);

				$ozonetelmissedcallnos = Ozonetelmissedcallno::where('number','LIKE','%'.$ozonetel_missedcall->called_number.'%')->first();

				$booktrial = Booktrial::where('customer_phone','LIKE','%'.substr($ozonetel_missedcall->customer_number, -8).'%')->where('missedcall_batch',$ozonetelmissedcallnos->batch)->orderBy('_id','desc')->first();
				
				if($booktrial){
					$data = array();

					$data['finder_name'] = $booktrial->finder_name;
					$data['customer_phone'] = $ozonetel_missedcall->customer_number;
					$data['schedule_date_time'] = $booktrial->schedule_date_time;

					switch ($type) {
						case 'confirm': $booktrial->missedcall_sms = $this->customersms->confirmTrial($data);break;
						case 'cancel': $booktrial->missedcall_sms = $this->customersms->cancelTrial($data);break;
						case 'reschedule': $booktrial->missedcall_sms = $this->customersms->rescheduleTrial($data);break;
					}

					$booktrial->missedcall_date = date('Y-m-d h:i:s');
					$booktrial->missedcall_status = $missedcall_status[$type];
					$booktrial->source_flag = 'missedcall';
					$booktrial->update();
				}
			}

			$response = array('status'=>200,'message'=>'success','ozonetel_missedcall'=> $ozonetel_missedcall );

		}catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            
            Log::error($e);
            
        }

        return $response;

	}

	public function confirmTrial(){

		try{

			$response = $this->misscall('confirm');

		}catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            Log::error($e);
            
        }

        return Response::json($response,$response['status']);

	}

	public function cancelTrial(){

		try{

			$response = $this->misscall('cancel');

		}catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            Log::error($e);
            
        }

        return Response::json($response,$response['status']);

	}

	public function rescheduleTrial(){

		try{

			$response = $this->misscall('reschedule');

		}catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            
            Log::error($e);
            
        }

        return Response::json($response,$response['status']);

	}

	public function callback(){

		try{

			if(isset($_REQUEST['data']) && $_REQUEST['data'] != ''){
				
				$data = json_decode($_REQUEST['data'],true);

				$insert["agent_phone_number"] = (isset($data["AgentPhoneNumber"]) && $data["AgentPhoneNumber"] != '') ? $data["AgentPhoneNumber"] : '';
				$insert["dial_status"] = (isset($data["DialStatus"]) && $data["DialStatus"] != '') ? $data["DialStatus"] : '';
				$insert["did"] = (isset($data["Did"]) && $data["Did"] != '') ? $data["Did"] : '';
				$insert["monitor_ucid"] = (isset($data["monitorUCID"]) && $data["monitorUCID"] != '') ? $data["monitorUCID"] : '';
				$insert["time_to_answer"] = (isset($data["TimeToAnswer"]) && $data["TimeToAnswer"] != '') ? $data["TimeToAnswer"] : '';
				$insert["agent_id"] = (isset($data["AgentID"]) && $data["AgentID"] != '') ? $data["AgentID"] : '';
				$insert["api_key"] = (isset($data["Apikey"]) && $data["Apikey"] != '') ? $data["Apikey"] : '';
				$insert["hangup_by"] = (isset($data["HangupBy"]) && $data["HangupBy"] != '') ? $data["HangupBy"] : '';
				$insert["location"] = (isset($data["Location"]) && $data["Location"] != '') ? $data["Location"] : '';
				$insert["fall_back_rule"] = (isset($data["FallBackRule"]) && $data["FallBackRule"] != '') ? $data["FallBackRule"] : '';
				$insert["duration"] = (isset($data["Duration"]) && $data["Duration"] != '') ? $data["Duration"] : '';
				$insert["caller_id"] = (isset($data["CallerID"]) && $data["CallerID"] != '') ? $data["CallerID"] : '';
				$insert["user_name"] = (isset($data["UserName"]) && $data["UserName"] != '') ? $data["UserName"] : '';
				$insert["agent_unique_id"] = (isset($data["AgentUniqueID"]) && $data["AgentUniqueID"] != '') ? $data["AgentUniqueID"] : '';
				$insert["transfer_type"] = (isset($data["TransferType"]) && $data["TransferType"] != '') ? $data["TransferType"] : '';
				$insert["audio_file"] = (isset($data["AudioFile"]) && $data["AudioFile"] != '') ? $data["AudioFile"] : '';
				$insert["phone_name"] = (isset($data["PhoneName"]) && $data["PhoneName"] != '') ? $data["PhoneName"] : '';
				$insert["transferred_to"] = (isset($data["TransferredTo"]) && $data["TransferredTo"] != '') ? $data["TransferredTo"] : '';
				$insert["agent_status"] = (isset($data["AgentStatus"]) && $data["AgentStatus"] != '') ? $data["AgentStatus"] : '';
				$insert["comments"] = (isset($data["Comments"]) && $data["Comments"] != '') ? $data["Comments"] : '';
				$insert["agent_name"] = (isset($data["AgentName"]) && $data["AgentName"] != '') ? $data["AgentName"] : '';
				$insert["campaign_name"] = (isset($data["CampaignName"]) && $data["CampaignName"] != '') ? $data["CampaignName"] : '';
				$insert["uui"] = (isset($data["UUI"]) && $data["UUI"] != '') ? $data["UUI"] : '';
				$insert["disposition"] = (isset($data["Disposition"]) && $data["Disposition"] != '') ? $data["Disposition"] : '';
				$insert["status"] = (isset($data["Status"]) && $data["Status"] != '') ? $data["Status"] : '';
				$insert["type"] = (isset($data["Type"]) && $data["Type"] != '') ? $data["Type"] : '';
				$insert["dialed_number"] = (isset($data["DialedNumber"]) && $data["DialedNumber"] != '') ? $data["DialedNumber"] : '';
				$insert["customer_status"] = (isset($data["CustomerStatus"]) && $data["CustomerStatus"] != '') ? $data["CustomerStatus"] : '';
			    $insert["skill"] = (isset($data["Skill"]) && $data["Skill"] != '') ? $data["Skill"] : '';

			    if(isset($data["StartTime"]) && $data["StartTime"] != ''){
			    	$insert["start_time"] = date('Y-m-d h:i:s', strtotime($data['StartTime']));
			    }

			    
			    if(isset($data["EndTime"]) && $data["EndTime"] != ''){
			    	$insert["end_time"] = date('Y-m-d h:i:s', strtotime($data['EndTime']));
			    }

				$ozonetel_callback = new Ozonetelcallback($insert);
				$ozonetel_callback->_id = Ozonetelcallback::max('_id') + 1;
				$ozonetel_callback->save();

				$response = array('status'=>200,'message'=>'success');

			}else{

				$response = array('status'=>400,'message'=>'data is empty');
			}

		}catch (Exception $e) {

            $message = array(
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                );

            $response = array('status'=>400,'message'=>$message['type'].' : '.$message['message'].' in '.$message['file'].' on '.$message['line']);
            
            Log::error($e);
            
        }

        return Response::json($response,$response['status']);

	}


 }