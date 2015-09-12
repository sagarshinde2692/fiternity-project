<?PHP

/** 
 * ControllerName : CustomerController.
 * Maintains a list of functions used for CustomerController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

use App\Services\Ozonetel as OzonetelResponce;
use Guzzle\Http\Client;

class OzonetelController extends \BaseController {

	protected $ozonetel;

	public function __construct(OzonetelResponce $ozonetel) {

		$this->ozonetel	=	$ozonetel;

	}

    // Listing Schedule Tirals for Normal Customer
	public function callVendor(){	

		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {

		    $this->ozonetel->addPlayText("This call is recorderd for quality purpose");

		    $finderDetails = $this->getFinderDetails($_REQUEST['called_number']);
		   
	    	if($finderDetails){
	    		$this->ozonetel->addDial($finderDetails->finder_contact_no,"true");
	    		$add_capture = $this->addCapture($_REQUEST,$finderDetails->finder_id);
	    	}else{
	    		$this->ozonetel->addHangup();
	    	}

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Dial') {

		    $update_capture = $this->updateCapture($_REQUEST);
		    $this->ozonetel->addHangup();

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Hangup') {

		    $update_capture = $this->updateCapture($_REQUEST);
		    $this->ozonetel->addHangup();

		}else {

		    if ($_REQUEST['status'] == 'answered') {

				$update_capture = $this->updateCapture($_REQUEST);

		    }

		    $this->ozonetel->addHangup();
		}
		
		$this->ozonetel->send();

	}

	public function getFinderDetails($ozonetel_no){

		$ozonetel = array();

		try {
			$ozonetel = Ozonetel::where('ozonetel_no','=',(string) $ozonetel_no)->first();

			if(!empty($ozonetel)){
				return $ozonetel;
			}else{
				return false;
			}

		} catch (Exception $e) {
			return false;
		}

	}

	public function addCapture($data,$finder_id){
		
		$ozonetel_capture = new Ozonetelcapture();
		$ozonetel_capture->_id = Ozonetelcapture::max('_id') + 1;
		$ozonetel_capture->ozonetel_unique_id = $data['sid'];
		$ozonetel_capture->ozonetel_no = (string) $data['called_number'];
		$ozonetel_capture->customer_contact_no = (string)$data['cid_e164'];
		$ozonetel_capture->customer_contact_circle = (string)$data['circle'];
		$ozonetel_capture->customer_contact_operator = (string)$data['operator'];
		$ozonetel_capture->customer_contact_type = (string)$data['cid_type'];
		$ozonetel_capture->customer_cid = (string)$data['cid'];
		$ozonetel_capture->finder_id = $finder_id;
		$ozonetel_capture->call_status = "called";
		$ozonetel_capture->status = "1";
		$ozonetel_capture->save();

		return $ozonetel_capture;
	}

	public function updateCapture($data){

		$ozonetel_capture = Ozonetelcapture::where('ozonetel_unique_id','=',$data['sid'])->first();
		$ozonetel_capture->call_status = $data['status'];
		$ozonetel_capture->rec_md5_checksum = (string) $data['rec_md5_checksum'];
		$ozonetel_capture->pick_duration = $data['pickduration'];

		if($data['status'] != 'answered'){

			$ozonetel_capture->call_status = "not answered";
			$ozonetel_capture->message = $data['message'];
			$ozonetel_capture->telco_code = $data['telco_code'];
			
		}else{
			
			$ozonetel_capture->call_status = "answered";
			$ozonetel_capture->call_duration = $data['callduration'];
			$ozonetel_capture->ozone_url_record = $data['data'];
			
			/*$aws_filename = time().$data['sid'].'.wav';
			$ozonetel_capture->aws_file_name = $aws_filename;
			$this->addToAws($data['data'],$aws_filename);*/
		}

		$ozonetel_capture->update();

		return $ozonetel_capture;
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


 }