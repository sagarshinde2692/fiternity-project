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
use App\Sms\FinderSms as FinderSms;
use App\Services\ShortenUrl as ShortenUrl;
use App\Services\Sidekiq as Sidekiq;

use Guzzle\Http\Client;

class OzonetelsController extends \BaseController {

	protected $ozonetelResponse;
	protected $ozonetelCollectDtmf;
	protected $ozontelOutboundCall;
	protected $customersms;

    protected $jump_finder_ids;
    protected $jump_start_time;
    protected $jump_end_time;
    protected $current_date_time;
    protected $jump_fitternity_no;

    protected   $jump_fitternity_no2;


	public function __construct(OzonetelResponse $ozonetelResponse,OzonetelCollectDtmf $ozonetelCollectDtmf,OzontelOutboundCall $ozontelOutboundCall,CustomerSms $customersms,FinderSms $findersms) {

		$this->ozonetelResponse			=	$ozonetelResponse;
		$this->ozonetelCollectDtmf		=	$ozonetelCollectDtmf;
		$this->ozontelOutboundCall		=	$ozontelOutboundCall;
		$this->customersms 				=	$customersms;
		$this->findersms 				=	$findersms;

        $this->jump_finder_ids 		    =	[1484,9111,878,1,941,6466,1490,862,1427,4141,4534,6081,613,8546,1041,596,1873,7036,1664,1068,1739,2806,2821,2824,2828,2833,2844,2848,7896,881,1766,1667,1671,827,1029,1030,1034,1035,1554,1705,1706,1870,4585,5045,7407,9428,608,2890,3175,3178,3179,3183,3192,3201,3204,3233,3330,3331,3332,3333,3335,3336,3341,3342,3343,3345,3346,3347,5566,5735,5736,5737,5738,5739,5964,6254,6594,7081,7106,7111,7114,7116,8872,8878,7336,4307,3239,3229,3972,3619,3620,3499,4763,3491,3774,3775,3777,6945,6947,6948,9422,3193,7035,7037,3350,3351,6985,6988,6991,6993,6995,6999,7006,7017,7020,7360,7441,7870,7872,8646,8647,8648,8666,8729,8731,8741,9390,9418,5066,7136,5303,6796,5347,6440,4834,4901,6566,6460,6333,7143,5740,1908,1863,1865,1884,6227,1846,2861,1895,1971,2723,7297,7003,1935,9304,9423,1892,1853,579,1215,1261,1429,1604,1623,1848,1874,1875,2002,2093,2105,2209,2216,2411,2860,3092,3202,3340,3415,3502,4817,4823,4913,5387,5750,6204,6377,6576,6891,7060,7444,7656,7933,9412];

        $this->jump_start_time 			=	strtotime( date("d-m-Y")." 09:00:00");
        $this->jump_end_time 			=	strtotime( date("d-m-Y")." 21:00:00");
        $this->current_date_time 		=	time();
        $this->jump_fitternity_no 		=	"02261222242";
        $this->jump_fitternity_no2 		=	"02261222209";

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

                        //OZONETEL JUMP LOGIC
                        /*$call_jump = false;

                        if($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time  && in_array($finderDetails->finder->_id, $this->jump_finder_ids)) {
                            $this->ozonetelResponse->addDial($this->jump_fitternity_no, "true");
                            $call_jump = true;
                        }else{
                            $this->ozonetelResponse->addDial($contact_no,"true");
                        }

			    		$this->updateCapture($_REQUEST,$finderDetails->finder->_id,$extension,$add_count = true, $call_jump);*/
			    		
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

					$finder = Finder::findOrFail((int) $capture->finder_id);
					
					if($finder){


                        /*if($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time  && in_array($finder->_id, $this->jump_finder_ids)) {


                            $this->ozonetelResponse->addDial($this->jump_fitternity_no2, "true");
                            $call_jump = true;
                            $this->updateCapture($_REQUEST,$finder->_id,$extension,$add_count = true, $call_jump);


                        }else{*/

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

	                   	//}

	                }
					else{

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
//	    		$this->ozonetelResponse->addDial($contact_no,"true");

                $call_jump = false;
                //OZONETEL JUMP LOGIC
                if($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time  && in_array($finderDetails->finder->_id, $this->jump_finder_ids)) {
                    $this->ozonetelResponse->addDial($this->jump_fitternity_no, "true");
                    $call_jump = true;
                }else{
                    $this->ozonetelResponse->addDial($contact_no,"true");
                }

                $add_capture = $this->addCapture($_REQUEST,$finderDetails->finder->_id,$add_count = true, $call_jump);
	    	}else{
	    		$this->ozonetelResponse->addHangup();
	    	}

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Dial') {

			if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'not_answered') {

				$capture = $this->getCapture($_REQUEST['sid']);

				if($capture->count > 1){
					$this->ozonetelResponse->addHangup();
				}else{

					$finder = Finder::findOrFail((int) $capture->finder_id);
					
					if($finder){

                        if($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time  && in_array($finder->_id, $this->jump_finder_ids)) {

                            $this->ozonetelResponse->addDial($this->jump_fitternity_no2, "true");
                            $call_jump = true;
                            $this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true, $call_jump);

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


	public function addCapture($data,$finder_id = false,$add_count = false , $call_jump = false){
		
		$ozonetel_capture = new Ozonetelcapture();
		$ozonetel_capture->_id = Ozonetelcapture::max('_id') + 1;
		$ozonetel_capture->ozonetel_unique_id = $data['sid'];
		$ozonetel_capture->ozonetel_no = (string) $data['called_number'];
		$ozonetel_capture->customer_contact_no = (string)$data['cid_e164'];
		$ozonetel_capture->customer_contact_circle = (string)$data['circle'];
		$ozonetel_capture->customer_contact_operator = (string)$data['operator'];
		$ozonetel_capture->customer_contact_type = (string)$data['cid_type'];
		$ozonetel_capture->customer_cid = (string)$data['cid'];

        if($call_jump){
            $ozonetel_capture->call_jump = $call_jump;
            $ozonetel_capture->call_jump_number = $this->jump_fitternity_no;
        }
        

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

	public function updateCapture($data,$finder_id = false,$extension = false,$add_count = false, $call_jump = false){

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

            if($call_jump){
                $ozonetel_capture->call_jump = $call_jump;
                $ozonetel_capture->call_jump_number = $this->jump_fitternity_no;
            }

			if($ozonetel_capture->finder_id){

				$finder_id = $ozonetel_capture->finder_id;

				$finder = Finder::with(array('location'=>function($query){$query->select('name','slug');}))->with(array('city'=>function($query){$query->select('name','slug');}))->find((int)$finder_id);

				if($finder){

					$finder = $finder->toArray();

					$data['finder_name'] = $ozonetel_capture->finder_name = ucwords($finder['title']);
					$data['finder_commercial_type'] = $ozonetel_capture->finder_commercial_type = (int)$finder['commercial_type'];
					$data['finder_location'] = $ozonetel_capture->finder_location = ucwords($finder['location']['name']);
					$data['finder_city'] = $ozonetel_capture->finder_city = ucwords($finder['city']['name']);

					$finder_url = ($finder['commercial_type'] != 0) ? "www.fitternity.com/".$finder['slug'] : "www.fitternity.com/".$finder['city']['slug']."/fitness/".$finder['location']['slug'];

					$shorten_url = new ShortenUrl();

		            $url = $shorten_url->getShortenUrl($finder_url);

		            if(isset($url['status']) &&  $url['status'] == 200){
		                $finder_url = $url['url'];
		            }

					$data['finder_url'] = $ozonetel_capture->finder_url = $finder_url;

				}
			}

			$ozonetelmissedcallno= Ozonetelmissedcallno::active()->where('label','CustomerCallToVendor')->get();
			$data['missedcallno'] = array();
			foreach ($ozonetelmissedcallno as $key => $value) {

				$data['missedcallno'][$value->type] = $value->number;
			}

			if(isset($data['event']) && $data['event'] != ''){
				$ozonetel_capture->event = $data['event'];
			}

			$ozonetel_capture->missedcallno = $data['missedcallno'];

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

				}
			}

			$ozonetel_capture->update();

			if($ozonetel_capture->call_status == 'answered' || $ozonetel_capture->call_status == 'not_answered' || $data['event'] == 'Disconnect' && isset($ozonetel_capture->finder_commercial_type) && $ozonetel_capture->finder_commercial_type != 0){
				$this->customersms->ozonetelCapture($ozonetel_capture->toArray());
			}

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

					$label = 'BoughtSms';

					$message = "Thank you for the notification. A member from our team will call you shortly to facilitate the fitness goodies for you.";
				
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

					$label = 'Wonderise';

					$message = "Hey! Bring out the unicorn in you with Wonderise & Fitternity! Here's the link to buy your tickets www.fitternity.com/wonderise. Can't wait to see you there!";
				
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
           $customer_sms_messageids  =  array();


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
                    $shorten_url = new ShortenUrl();

					$finder = Finder::find((int) $booktrial->finder_id);

					$google_pin = "";
					
					if($finder){

						$finder_lat = $finder->lat;
						$finder_lon = $finder->lon;

						$google_pin = "https://maps.google.com/maps?q=".$finder_lat.",".$finder_lon."&ll=".$finder_lat.",".$finder_lon;

			            $url = $shorten_url->getShortenUrl($google_pin);

			            if(isset($url['status']) &&  $url['status'] == 200){
			                $google_pin = $url['url'];
			            }

			        }

                    $customer_profile_url   = "";
                    
                    if(isset($booktrial->customer_email) && $booktrial->customer_email != "" ){

                        $customer_profile_url   =   "https://www.fitternity.com/profile/".$booktrial->customer_email;

                       	$customer_url            =   $shorten_url->getShortenUrl($customer_profile_url);

                       	if(isset($customer_url['status']) &&  $customer_url['status'] == 200){
                           $customer_profile_url = $customer_url['url'];
                       	}

                    }


					$sidekiq = new Sidekiq();

					$data = array();

					$data['finder_name'] = $booktrial->finder_name;
					$data['customer_name'] = ucwords($booktrial->customer_name);
					$data['customer_phone'] = $ozonetel_missedcall->customer_number;
					$data['schedule_date_time'] = $booktrial->schedule_date_time;
					$data['service_name'] = $booktrial->service_name;
                    $data['google_pin'] = $google_pin;
                    $data['customer_profile_url'] = $customer_profile_url;
					$data['finder_vcc_mobile'] = $booktrial->finder_vcc_mobile;
					$data['finder_category_id'] = (int)$booktrial->finder_category_id;
					$data['code'] = $booktrial->code;
					$data['finder_location'] = $booktrial->finder_location;

                    // set before after flag based schecdule time
                    $schedule_date_time     =   strtotime($booktrial['schedule_date_time']);
                    $currentTime            =   time();
                    if($currentTime < $schedule_date_time){
                        $schedule_time_passed_flag = "before";
                    }else{
                        $schedule_time_passed_flag = "after";
                    }
                    $data['schedule_time_passed_flag'] = $schedule_time_passed_flag;


                    Log::info('Missedcall N-3 - '.$type);

                    $delayReminderTimeAfter2Hour		    =	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A',strtotime($booktrial->schedule_date_time)))->addMinutes(60 * 2);

					switch ($type) {
						case 'confirm':
                            $booktrial->missedcall_sms = $this->customersms->confirmTrial($data);
                            $this->findersms->confirmTrial($data);
                            break;
						case 'cancel':
                            $booktrial->missedcall_sms = $this->customersms->cancelTrial($data);
                            $this->findersms->cancelTrial($data);
                            break;
						case 'reschedule':
                            $booktrial->missedcall_sms = $this->customersms->rescheduleTrial($data);
                            $this->findersms->rescheduleTrial($data);
                            break;
					}


                    if((isset($booktrial->finder_smsqueuedids['before1hour']) && $booktrial->finder_smsqueuedids['before1hour'] != '')){
                        try {
                            $sidekiq->delete($booktrial->finder_smsqueuedids['before1hour']);
                        }catch(\Exception $exception){
                            Log::error($exception);
                        }
                    }

                    $in_array = array('cancel','reschedule');

					if(in_array($type,$in_array)){

						if((isset($booktrial->customer_smsqueuedids['after2hour']) && $booktrial->customer_smsqueuedids['after2hour'] != '')){

							try {
								$sidekiq->delete($booktrial->customer_smsqueuedids['after2hour']);
							}catch(\Exception $exception){
								Log::error($exception);
							}
						}

						if((isset($booktrial->customer_emailqueuedids['after2hour']) && $booktrial->customer_emailqueuedids['after2hour'] != '')){

							try {
								$sidekiq->delete($booktrial->customer_emailqueuedids['after2hour']);
							}catch(\Exception $exception){
								Log::error($exception);
							}

						}

						if((isset($booktrial->customer_smsqueuedids['before1hour']) && $booktrial->customer_smsqueuedids['before1hour'] != '')){

							try {
								$sidekiq->delete($booktrial->customer_smsqueuedids['before1hour']);
							}catch(\Exception $exception){
								Log::error($exception);
							}
						}



						if((isset($booktrial->customer_notification_messageids['before1hour']) && $booktrial->customer_notification_messageids['before1hour'] != '')){

							try{
								$sidekiq->delete($booktrial->customer_notification_messageids['before1hour']);
							}catch(\Exception $exception){
								Log::error($exception);
							}

						}

						if((isset($booktrial->customer_notification_messageids['after2hour']) && $booktrial->customer_notification_messageids['after2hour'] != '')){

							try{
								$sidekiq->delete($booktrial->customer_notification_messageids['after2hour']);
							}catch(\Exception $exception){
								Log::error($exception);
							}

						}

                        if((isset($booktrial->rescheduleafter4days) && $booktrial->rescheduleafter4days != '')){

                            try {
                                $sidekiq->delete($booktrial->rescheduleafter4days);
                            }catch(\Exception $exception){
                                Log::error($exception);
                            }
                        }
                        
                    }

//                    $delayReminderRescheduleAfter4Days	=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(5);
                    $delayReminderRescheduleAfter4Days	=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addDays(4);

                    switch ($type) {
                        case 'reschedule':
                            $rescheduleafter4days               =   $this->customersms->reminderRescheduleAfter4Days($data, $delayReminderRescheduleAfter4Days);
                            $booktrial->rescheduleafter4days    =   $rescheduleafter4days;
                            break;
                    }

					$booktrial->missedcall_date = date('Y-m-d h:i:s');
					$booktrial->missedcall_status = $missedcall_status[$type];
                    $booktrial->source_flag = 'missedcall';
                    $booktrial->customer_profile_url = $customer_profile_url;
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

	public function misscallReview($type){

		Log::info('Missedcall N+2 - '.$type);

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
			$ozonetel_missedcall->for = "N+2Trial";
			$ozonetel_missedcall->save();

			$ozonetelmissedcallnos = Ozonetelmissedcallno::where('number','LIKE','%'.$ozonetel_missedcall->called_number.'%')->where('for','N+2Trial')->first();

			$booktrial = Booktrial::where('customer_phone','LIKE','%'.substr($ozonetel_missedcall->customer_number, -8).'%')->where('missedcall_review_batch',$ozonetelmissedcallnos->batch)->orderBy('_id','desc')->first();
	
			if($booktrial){

				$google_pin = "";
				$finder_slug = "";

				$finder = Finder::find((int) $booktrial->finder_id);

				if($finder){

					$finder_lat = $finder->lat;
					$finder_lon = $finder->lon;
					$finder_slug = $finder->slug;

					$google_pin = "https://maps.google.com/maps?q=".$finder_lat.",".$finder_lon."&ll=".$finder_lat.",".$finder_lon;

					$shorten_url = new ShortenUrl();

		            $url = $shorten_url->getShortenUrl($google_pin);

		            if(isset($url['status']) &&  $url['status'] == 200){
		                $google_pin = $url['url'];
		            }
		        }

				$data = array();

				$data['customer_name'] = ucwords($booktrial->customer_name);
				$data['customer_phone'] = $ozonetel_missedcall->customer_number;
				$data['schedule_date_time'] = $booktrial->schedule_date_time;
				$data['finder_name'] = $booktrial->finder_name;
				$data['direct_payment_enable'] = false;
				$data['service_link'] = "";
				$data['google_pin'] = $google_pin;


				if(isset($booktrial->service_id) && $booktrial->service_id != ""){

					$service_id = (int)$booktrial->service_id;

					$direct_payment_enable = Ratecard::where("direct_payment_enable","1")->where("service_id",$service_id)->lists("_id");

					if(!empty($direct_payment_enable)){

						//$link = "www.fitternity.com/membershipbuy/".$finder_slug."/".$booktrial->service_id;
						$link = "www.fitternity.com/".$finder_slug;
						$short_url = "";
						$short_url = "";

		                $shorten_url = new ShortenUrl();

		                $url = $shorten_url->getShortenUrl($link);

		                if(isset($url['status']) &&  $url['status'] == 200){
		                    $short_url = $url['url'];
		                }

		                $data['direct_payment_enable'] = true;
		                $data['service_link'] = $short_url;
					}
	            }

				switch ($type) {
					case 'like': $booktrial->missedcall_review_sms = $this->customersms->likedTrial($data);break;
					case 'explore': $booktrial->missedcall_review_sms = $this->customersms->exploreTrial($data);break;
					case 'notattended': $booktrial->missedcall_review_sms = $this->customersms->notAttendedTrial($data);break;
				}

				$booktrial->missedcall_review_date = date('Y-m-d h:i:s');
				$booktrial->missedcall_review_status = $type;
				$booktrial->source_flag = 'missedcall';
				$booktrial->update();

				$ozonetel_missedcall->update(array('trial_id'=>$booktrial->_id));
			}

			$response = array('status'=>200,'message'=>'success');

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

	public function misscallManualTrial($type){

		Log::info('misscallManualTrial - ');

		try{

			$request = $_REQUEST;

			Log::info('$request - ',$request);


			$ozonetel_missedcall = new Ozonetelmissedcall();
			$ozonetel_missedcall->_id = Ozonetelmissedcall::max('_id') + 1;
			$ozonetel_missedcall->status = "1";
			$ozonetel_missedcall->cid = isset($request['cid']) ? preg_replace("/[^0-9]/", "", $request['cid']) : '';
			$ozonetel_missedcall->customer_number = isset($request['cid']) ? preg_replace("/[^0-9]/", "", $request['cid']) : '';
			$ozonetel_missedcall->sid = isset($request['sid']) ? $request['sid'] : '';
			$ozonetel_missedcall->called_number = isset($request['called_number']) ? $request['called_number'] : '';
			$ozonetel_missedcall->circle = isset($request['circle']) ? $request['circle'] : '';
			$ozonetel_missedcall->operator = isset($request['operator']) ? $request['operator'] : '';
			$ozonetel_missedcall->call_time = isset($request['call_time']) ? $request['call_time'] : '';
			$ozonetel_missedcall->called_at = isset($request['call_time']) ? strtotime($request['call_time']) : '';
			$ozonetel_missedcall->type = $type;
			$ozonetel_missedcall->label = 'manualtrial';
			$ozonetel_missedcall->save();

			$ozonetelmissedcallnos = Ozonetelmissedcallno::where('status','1')->where('number','LIKE','%'.$ozonetel_missedcall->called_number.'%')->where('label','manualtrial')->where('type',$type)->first();

			if($ozonetelmissedcallnos){
				$booktrial = Booktrial::where('customer_phone','LIKE','%'.substr($ozonetel_missedcall->customer_number, -8).'%')->where('manual_trial_auto','1')->orderBy('_id','desc')->first();

				if($booktrial){

					$booktrial->missedcall_manualtrial_type = $type;
					$booktrial->missedcall_manualtrial_date = date('Y-m-d h:i:s');
					$booktrial->update();

					$ozonetel_missedcall->update(array('trial_id'=>$booktrial->_id));
				}
			}


			$response = array('status'=>200,'message'=>'success');

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

	public function misscallOrder($type){

		Log::info('Missedcall Order Renew - '.$type);

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
			$ozonetel_missedcall->for = "OrderRenewal";
			$ozonetel_missedcall->save();

			$ozonetelmissedcallnos = Ozonetelmissedcallno::where('number','LIKE','%'.$ozonetel_missedcall->called_number.'%')->where('for','OrderRenewal')->first();

			$order = Order::active()->where('customer_phone','LIKE','%'.substr($ozonetel_missedcall->customer_number, -8).'%')->where('missedcall_renew_batch',$ozonetelmissedcallnos->batch)->orderBy('_id','desc')->first();

			$finder = Finder::find((int) $order->finder_id);

			$finder_lat = $finder->lat;
			$finder_lon = $finder->lon;

			$google_pin = "https://maps.google.com/maps?q=".$finder_lat.",".$finder_lon."&ll=".$finder_lat.",".$finder_lon;

			$shorten_url = new ShortenUrl();

            $url = $shorten_url->getShortenUrl($google_pin);

            if(isset($url['status']) &&  $url['status'] == 200){
                $google_pin = $url['url'];
            }
			
			if($order){

				$data = array();

				$data['customer_name'] = ucwords($order->customer_name);
				$data['customer_phone'] = $ozonetel_missedcall->customer_number;
				$data['finder_name'] = $order->finder_name;
				$data['google_pin'] = $google_pin;

				switch ($type) {
					case 'renew': $order->missedcall_sms = $this->customersms->renewOrder($data);break;
					case 'alreadyextended': $order->missedcall_sms = $this->customersms->alreadyExtendedOrder($data);break;
					case 'explore': $order->missedcall_sms = $this->customersms->exploreOrder($data);break;
				}

				$order->missedcall_renew_date = date('Y-m-d h:i:s');
				$order->missedcall_renew_status = $type;
				$order->source_flag = 'missedcall';
				$order->update();

				$ozonetel_missedcall->update(array('order_id'=>$order->_id));
			}

			$response = array('status'=>200,'message'=>'success');

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

	public function missedcallSms(){

		try{

			$response = $this->misscall('sms');

			if($response['status'] == 200){
		
				$ozonetel_missedcall = $response['ozonetel_missedcall'];

				if($ozonetel_missedcall->customer_number != ''){

					$missed_call_no = Ozonetelmissedcallno::active()->where('number','LIKE','%'.$ozonetel_missedcall->called_number.'%')->first();

					if($missed_call_no){

						$label = $missed_call_no->label;

						$message = "";

						if(isset($missed_call_no->message) && $missed_call_no->message != ""){

							$message = $missed_call_no->message;

							$data = array();

							$data['label'] = $label;
							$data['message'] = $message;
							$data['to'] = $ozonetel_missedcall->cid;

							$update['sms_general'] = $this->customersms->generalSms($data);
							$update['sms_message'] = $message;
							$update['label'] = $label;
						}
					}
				
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

	public function outboundCallStayOnTrack($id){

		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {

			$stayontrack = Stayontrack::find((int) $id);

			$this->ozonetelResponse->addPlayText("Hi ".$stayontrack->customer_name.", this is regarding a workout session",3);

		   	$this->ozonetelResponse->addHangup();

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

	public function customerCallToVendorMissedcall(){

		Log::info('----------Customer Call To Vendor Missedcall--------');

		try{

			$request = $_REQUEST;

			$ozonetelmissedcallnos = new \stdClass();

			$ozonetelmissedcallnos = Ozonetelmissedcallno::where('number','LIKE','%'.substr($request['called_number'], -9).'%')->where('for','CustomerCallToVendor')->first();

			$ozonetelcapture = new \stdClass();

			if(count($ozonetelmissedcallnos) > 0){

				switch ($ozonetelmissedcallnos->type){

					case 'integrated_vendor_enquiry': $operator = "!="; break;
					case 'integrated_vendor_assistance': $operator = "!="; break;
					case 'non_integrated_vendor_assistance': $operator = "="; break;
					default: $operator = ""; break;
				}

				if($operator != ""){

					Log::info('operator - '.$operator);
					
					$ozonetelcapture = Ozonetelcapture::where('created_at', '>=', new DateTime( date("Y-m-d 00:00:00")))->where('created_at', '<=', new DateTime( date("Y-m-d 23:59:00")))->where('finder_commercial_type',$operator,0)->where('customer_cid','LIKE','%'.substr($request['cid'], -9))->orderBy('_id','desc')->first();

					Log::info('ozonetelcapture - '.count($ozonetelcapture));

				}
			}

			$ozonetel_missedcall = new Ozonetelmissedcall();
			$ozonetel_missedcall->_id = Ozonetelmissedcall::max('_id') + 1;
			$ozonetel_missedcall->type = isset($ozonetelmissedcallnos['type']) ? $ozonetelmissedcallnos['type'] : '';
			$ozonetel_missedcall->label = isset($ozonetelmissedcallnos['label']) ? $ozonetelmissedcallnos['label'] : '';
			$ozonetel_missedcall->status = "1";
			$ozonetel_missedcall->cid = isset($request['cid']) ? preg_replace("/[^0-9]/", "", $request['cid']) : '';
			$ozonetel_missedcall->customer_number = isset($request['cid']) ? preg_replace("/[^0-9]/", "", $request['cid']) : '';
			$ozonetel_missedcall->sid = isset($request['sid']) ? $request['sid'] : '';
			$ozonetel_missedcall->called_number = isset($request['called_number']) ? $request['called_number'] : '';
			$ozonetel_missedcall->circle = isset($request['circle']) ? $request['circle'] : '';
			$ozonetel_missedcall->operator = isset($request['operator']) ? $request['operator'] : '';
			$ozonetel_missedcall->call_time = isset($request['call_time']) ? $request['call_time'] : ''; 
			$ozonetel_missedcall->called_at = isset($request['call_time']) ? strtotime($request['call_time']) : '';
			$ozonetel_missedcall->for = isset($ozonetelmissedcallnos['for']) ? $ozonetelmissedcallnos['for'] : '';
			$ozonetel_missedcall->finder_id = isset($ozonetelcapture['finder_id']) ? $ozonetelcapture['finder_id'] : '';
			$ozonetel_missedcall->finder_name = isset($ozonetelcapture['finder_name']) ? $ozonetelcapture['finder_name'] : '';
			$ozonetel_missedcall->finder_commercial_type = isset($ozonetelcapture['finder_commercial_type']) ? $ozonetelcapture['finder_commercial_type'] : '';
			$ozonetel_missedcall->finder_location = isset($ozonetelcapture['finder_location']) ? $ozonetelcapture['finder_location'] : '';
			$ozonetel_missedcall->finder_city = isset($ozonetelcapture['finder_city']) ? $ozonetelcapture['finder_city'] : '';
			$ozonetel_missedcall->ozonetelcapture_id = isset($ozonetelcapture['_id']) ? $ozonetelcapture['_id'] : '';
			$ozonetel_missedcall->ozonetel_called_at = isset($ozonetelcapture['created_at']) ? $ozonetelcapture['created_at'] : '';
			$ozonetel_missedcall->save();

			$response = array('status'=>200,'message'=>'success');

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


 }