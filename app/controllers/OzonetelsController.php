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
use App\Services\Utilities as Utilities;
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
    protected $sunday;
    protected $free_special_finder;
    protected $utilities;

    protected   $jump_fitternity_no2;
    protected 	$direct_route_to_fitternity_vendor;


	public function __construct(
		OzonetelResponse $ozonetelResponse,
		OzonetelCollectDtmf $ozonetelCollectDtmf,
		OzontelOutboundCall $ozontelOutboundCall,
		CustomerSms $customersms,
		FinderSms $findersms,
		Utilities $utilities
	) {

		$this->ozonetelResponse			=	$ozonetelResponse;
		$this->ozonetelCollectDtmf		=	$ozonetelCollectDtmf;
		$this->ozontelOutboundCall		=	$ozontelOutboundCall;
		$this->customersms 				=	$customersms;
		$this->findersms 				=	$findersms;
		$this->utilities            	=   $utilities;

		$mumbai = [14,138,143,147,303,329,380,590,602,647,667,718,900,927,966,975,980,984,988,998,1219,1242,1332,1522,1613,1630,1642,1664,1764,1766,1771,1783,2242,2421,2501,2818,2864,2865,3451,4586,4749,5529,5684,5979,6036,6140,6143,6377,6461,6466,6543,6784,7438,7442,7866,7878,8021,8842,9124,9340,9404,9414,9476,9671,10508,10571,10927];

		$bangalore = [6668,3586,4825,7321,3720,3210,6997,3186,4088,3340,6668,4763,3975,4013,3421,4650,3202,9112,7157,3209,4291,3985,4164,3351,3929,3429,3654,7031,3279,3195,6141,7029,4484,4046,4049,4048,7437,7325,4240,11315,9374,3678,7166,3642,6237,3807,4041,3821,10090,11063,10098,10482,10552,10642,11168,6397,4698,3989,6002,4486,6034,3413,3291,3196,9959,3953,3565,7010,7059,3556,11771,11698];

		$pune = [1801,1803,1806,1816,1824,1828,1846,1848,1853,1860,1863,1865,1866,1874,1875,1876,1878,1883,1884,1892,1895,1935,1940,1946,1968,1971,1980,2002,2013,2044,2076,2093,2105,2107,2119,2126,2137,2145,2159,2161,2183,2187,2194,2196,2197,2200,2216,2217,2222,2665,2677,2723,2785,2813,2845,2860,2861,2867,2886,2890,2992,3100,3101,3255,3256,3854,4585,4677,4772,4815,5149,5967,5970,6022,6054,6131,6188,6227,6245,6593,6681,6963,7004,7005,7299,7301,7317,7397,7446,7940,7941,7947,8058,8332,8519,8744,8937,9304,9386,9423,9469,9479,9481,9500,9729,9731,9732,9735,9745,9933,9954,9973,10076,10674,10696,10970,11021];

		$default_cities = [10190,10521,10522,10524,10526,10558,10728,10745,10753,10770,10771,10786,10787];

		$other_cities = [4836,4841,4853,4928,5038,5041,5348,5629,5688,5769,5833,6212,6216,6333,6460,6885,6988,7192,7340,7359,7541,7649,8763,9154,9440,9450,9482,9912,11133,11242,11405,5152];

		$this->jump_finder_ids = array_merge($mumbai,$bangalore,$pune,$other_cities,$default_cities);

		$mumbai_dr = [40,142,179,223,328,401,417,424,442,449,530,561,569,575,576,579,596,613,625,823,827,841,862,878,881,941,978,987,1013,1026,1029,1030,1031,1034,1035,1038,1040,1041,1068,1215,1233,1257,1259,1260,1261,1262,1263,1266,1309,1330,1388,1393,1395,1421,1427,1431,1451,1452,1457,1458,1460,1484,1486,1487,1488,1490,1493,1496,1501,1505,1510,1554,1579,1580,1581,1582,1583,1584,1602,1604,1605,1606,1607,1623,1647,1656,1671,1676,1677,1690,1691,1705,1706,1739,1837,1873,1928,1938,2209,2235,2236,2244,2257,2281,2309,2522,2545,2806,2821,2824,2828,2833,2844,2848,3382,3579,3856,4141,4142,4534,4678,4679,4680,4742,4773,5387,6009,6049,6058,6081,6082,6095,6126,6129,6133,6134,6151,6162,6233,6259,6289,6468,6511,6532,6893,6907,6910,6914,6916,6946,7036,7054,7064,7215,7224,7319,7341,7388,7407,7456,7480,7525,7532,7656,7661,7696,7697,7724,7792,7896,8546,8852,8859,8892,9040,9111,9187,9246,9336,9365,9370,9378,9419,9420,9427,9432,9436,9459,9485,9518,9575,9751,9752,9870,9872,9877,9883,9909,9922,9932,9935,9942,9943,9948,9994,10119,10136,10465,10485,10486,10515,10565,10567,10752,10768,10946,10965,10966,10967,10968,11230,12046];
		$bangalore_dr = [3332,7429,3184,4823,4817,7114,3343,4822,4819,3333,3491,7111,3229,5958,5964,8872,3197,3239,4602,3233,7081,4175,3183,4179,4059,3178,3191,3972,3201,4824,3350,3330,3346,4821,10892,9988,6686,4818,7106,9615,3235,3253,3175,4826,5950,3342,7116,3192,9410,3331,3204,3336,7786,5655,9400,7220,9957,3417,3335,3341,3322,6948,5988,3345,6884,3775,9951,11251,10894,4388,5957,3970,3179,5959,3443,5956,8877,3667,6947,10868,10659,4491,6945,6942,3347,3504,9422,10046,10883,4032,3774,10870,10877,3365,9918,10888,10890,10900,10897,10880,10875,10863,9339,6029,10394,10324,9947,10761,10872,10876,10891,10886,10887,10861,10895,10315,10614,5958,7433,3622,7053,6005,3499,9579,6949,11988];
		$pune_dr = [1874,1878,1935,2105,9304,9423,9481,9954,10674,10970,11021,11223];
		$other_cities_dr = [4929,4968,5027,5066,5145,5355,5603,5609,5617,5709,6047,6411,6412,6499,6534,6876,6895,6979,7136,7448,7657,7907,7909,8289,8837,8878,9125,9171,9178,9201,9337,9397,9415,9417,9600,9624,9726,9728,9876,9878,9888,9913,10245,10568,10570,10624,10847,10957,10962,10993,11034,11040,11134,11176,11274,11374,6993,10987,8470,8823,6446,9855,11028,11030,11031,9854];

		$this->direct_route_to_fitternity_vendor = array_merge($mumbai_dr,$bangalore_dr,$pune_dr,$other_cities_dr);

        $this->free_special_finder 		= 	[7389,5740,6083,9589,9881,9882,1609,2187,5741,4818,1876,9216,4822,4821,4825,5041];

        $this->jump_start_time 			=	strtotime( date("d-m-Y")." 09:00:00");
        $this->jump_end_time 			=	strtotime( date("d-m-Y")." 20:00:00");
        $this->current_date_time 		=	time();
        $this->jump_fitternity_no 		=	"09867593025";
        $this->jump_fitternity_no2 		=	"09867593025";
        $this->sunday 					=   date('l');

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

	    			$ozonetelNoDetails = $this->getOzonetelNoDetails($_REQUEST['called_number'],$extension);
		   
			    	if($ozonetelNoDetails){
			    		$phone = $ozonetelNoDetails->finder->contact['phone'];
			    		$phone = explode(',', $phone);
			    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);//(string)trim($phone[0]);

			    		$this->ozonetelResponse->addDial($contact_no,"true");
			    		$this->updateCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$extension,$add_count = true);

                        //OZONETEL JUMP LOGIC
                        /*$call_jump = false;

                        if($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time  && in_array($ozonetelNoDetails->finder->_id, $this->jump_finder_ids)) {
                            $this->ozonetelResponse->addDial($this->jump_fitternity_no, "true");
                            $call_jump = true;
                        }else{
                            $this->ozonetelResponse->addDial($contact_no,"true");
                        }

			    		$this->updateCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$extension,$add_count = true, $call_jump);*/
			    		
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

	public function freeVendorV1(){	

		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {

			$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=select_extension");
			$this->addCapture($_REQUEST);
		    $this->ozonetelResponse->addPlayText("This call is recorderd for internal training purpose, Please dial the extension number");
		    $this->ozonetelCollectDtmf = new OzonetelCollectDtmf(); //initiate new collect dtmf object
		    $this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'GotDTMF') {
	    	if (isset($_REQUEST['data']) && $_REQUEST['data'] != '') {

	    		$extension = (int)$_REQUEST['data'];

	    		if(isset($_REQUEST['fit_action']) && $_REQUEST['fit_action'] == 'select_extension'){

		    		if($extension < 1 || $extension > 99){

		    			$this->ozonetelCollectDtmf = new OzonetelCollectDtmf(); //initiate new collect dtmf object
			    		$this->ozonetelCollectDtmf->addPlayText("You have dailed wrong extension number please dial correct extension number");
			    		$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);

		    		}else{

		    			$extension = (string) $extension;

		    			$ozonetelNoDetails = $this->getOzonetelNoDetails($_REQUEST['called_number'],$extension);
			   
				    	if($ozonetelNoDetails){

				    		if($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time && !in_array($this->sunday,["Sunday"]) && in_array($ozonetelNoDetails->finder->_id, $this->direct_route_to_fitternity_vendor)){

				    			$capture = $this->getCapture($_REQUEST['sid']);
								$call_jump = true;
						    	$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=route_to_fitternity_1");
						    	$this->ozonetelResponse->addDial($this->jump_fitternity_no, "true");

						    	$this->updateCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$extension,$add_count = true, $call_jump);

						    	$this->pubNub($_REQUEST,$ozonetelNoDetails->finder->_id,$capture->_id);

							}elseif($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time && !in_array($this->sunday,["Sunday"]) && ( in_array($ozonetelNoDetails->finder->_id, $this->jump_finder_ids) || in_array($ozonetelNoDetails->finder->city_id, [4,8,9]))){

				    			if(in_array($ozonetelNoDetails->finder->commercial_type,[1,3]) || ($ozonetelNoDetails->finder->commercial_type == 2 && in_array($ozonetelNoDetails->finder->_id, $this->free_special_finder))){

				    				$this->ozonetelCollectDtmf = new OzonetelCollectDtmf();
						    		$this->ozonetelCollectDtmf->addPlayText('Thank you for calling.');
									$this->ozonetelCollectDtmf->addPlayText($this->ozonetelIvr());
									$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=select_options");
								   	$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);
								   	$this->updateCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$extension);

				    			}else{

				    				$phone = $ozonetelNoDetails->finder->contact['phone'];
						    		$phone = explode(',', $phone);
						    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);//(string)trim($phone[0]);
						    		$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=route_to_vendor_1");
						    		$this->ozonetelResponse->addDial($contact_no,"true");
					                $this->updateCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$extension,$add_count = true);
				    			}
				    			
							}else{

								$phone = $ozonetelNoDetails->finder->contact['phone'];
					    		$phone = explode(',', $phone);
					    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);//(string)trim($phone[0]);
					    		$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=route_to_vendor_1");
					    		$this->ozonetelResponse->addDial($contact_no,"true");

				                $this->updateCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$extension,$add_count = true);
							}
				    		
				    	}else{

				    		$this->ozonetelResponse->addPlayText("You have dailed wrong extension number");
				    		$this->ozonetelResponse->addHangup();
				    	}
		    		}

		    	}elseif(isset($_REQUEST['fit_action']) && $_REQUEST['fit_action'] == 'select_options'){

		    		$extension_array = [1,2,3];

		    		if(in_array($extension,$extension_array)){

		    			if($extension == 3){

		    				$this->ozonetelCollectDtmf = new OzonetelCollectDtmf();
							$this->ozonetelCollectDtmf->addPlayText($this->ozonetelIvr());
							$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=select_options");
						   	$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);

		    			}else{

		    				$capture = $this->getCapture($_REQUEST['sid']);

		    				$finder = Finder::findOrFail((int) $capture->finder_id);

					    	if($finder){

					    		$this->ozonetelResponse->addPlayText("Please hold while we transfer your call to the concerned person");

					    		//$this->ozonetelResponse->addPlayText("This call is recorderd for quality purpose");

					    		if($extension == 2){

							    	$call_jump = true;
							    	$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=route_to_fitternity_1");
							    	$this->ozonetelResponse->addDial($this->jump_fitternity_no, "true");

							    	$this->updateCapture($_REQUEST,$finder->_id,$extension = false,$add_count = true, $call_jump);

							    	$this->pubNub($_REQUEST,$finder->_id,$capture->_id);

					            }else{

						    		$phone = $finder->contact['phone'];
						    		$phone = explode(',', $phone);
						    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);//(string)trim($phone[0]);
						    		$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=route_to_vendor_1");
						    		$this->ozonetelResponse->addDial($contact_no,"true");

					                $this->updateCapture($_REQUEST,$finder->_id,$extension = false,$add_count = true);

					            }

					    	}else{

					    		$this->ozonetelResponse->addHangup();
					    	}
					    }
		    			
		    		}else{

		    			$this->ozonetelCollectDtmf = new OzonetelCollectDtmf(); //initiate new collect dtmf object
			    		$this->ozonetelCollectDtmf->addPlayText("You have dailed wrong extension number please dial correct extension number");
			    		$this->ozonetelCollectDtmf->addPlayText($this->ozonetelIvr());
			    		$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);
		    		}

		    	}else{

		    		$this->ozonetelResponse->addPlayText("Sorry wrong input");
		    		$this->ozonetelResponse->addHangup();
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

						if(isset($capture->call_jump)){

								$this->ozonetelResponse->addPlayText("Call diverted to another number");
								$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=route_to_fitternity_2");
						    	$this->ozonetelResponse->addDial($this->jump_fitternity_no2, "true");
						    	$this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true);
						    	$this->pubNub($_REQUEST,$capture->finder_id,$capture->_id);

		    			}else{

		    				$phone = $finder->contact['phone'];
                            $phone = explode(',', $phone);

                            if(isset($phone[1]) && $phone[1] != ''){
                                $contact_no = (string)trim($phone[1]);
                                $this->ozonetelResponse->addPlayText("Call diverted to another number");
                                $this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/freevendor?fit_action=route_to_vendor_2");
                                $this->ozonetelResponse->addDial($contact_no,"true");
                                $this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true);
                            }else{
                                $this->ozonetelResponse->addHangup();
                            }
		    				
		    			}
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

		    $ozonetelNoDetails = $this->getOzonetelNoDetails($_REQUEST['called_number']);
   
	    	if($ozonetelNoDetails){

	    		$this->ozonetelResponse->addPlayText("This call is recorderd for quality purpose");

	    		$phone = $ozonetelNoDetails->finder->contact['phone'];
	    		$phone = explode(',', $phone);
	    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);//(string)trim($phone[0]);
//	    		$this->ozonetelResponse->addDial($contact_no,"true");

                $call_jump = false;
                //OZONETEL JUMP LOGIC
                if($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time  && in_array($ozonetelNoDetails->finder->_id, $this->jump_finder_ids)) {
                    $this->ozonetelResponse->addDial($this->jump_fitternity_no, "true");
                    $call_jump = true;
                }else{
                    $this->ozonetelResponse->addDial($contact_no,"true");
                }

                $add_capture = $this->addCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$add_count = true, $call_jump);
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

        
	public function paidVendorV1(){

		if (isset($_REQUEST['event']) && $_REQUEST['event'] == 'NewCall') {

			$ozonetelNoDetails = $this->getOzonetelNoDetails($_REQUEST['called_number']);

			if($ozonetelNoDetails){

	    		$phone = $ozonetelNoDetails->finder->contact['phone'];
	    		$phone = explode(',', $phone);
	    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);
                $call_jump = false;
                $city = [1,2];

                
                //OZONETEL JUMP LOGIC
                //if(in_array($ozonetelNoDetails->finder->city_id, $city) || in_array($ozonetelNoDetails->finder->_id, $this->jump_finder_ids)) {

                	if($this->jump_start_time < $this->current_date_time && $this->current_date_time < $this->jump_end_time && !in_array($this->sunday,["Sunday"]) && in_array($ozonetelNoDetails->finder->_id, $this->jump_finder_ids)){

			    		$this->ozonetelCollectDtmf = new OzonetelCollectDtmf();
			    		$this->ozonetelCollectDtmf->addPlayText('Thank you for calling.');
			    		$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/paidvendor?fit_action=select_options");
						$this->ozonetelCollectDtmf->addPlayText($this->ozonetelIvr());
					   	$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);
					   	$this->addCapture($_REQUEST,$ozonetelNoDetails->finder->_id);

					}else{

						$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/paidvendor?fit_action=route_to_vendor_1");
	                    $this->ozonetelResponse->addDial($contact_no,"true");
	                    $this->addCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$add_count = true);
	                }

                /*}else{

                    $this->ozonetelResponse->addDial($contact_no,"true");
                    $this->addCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$add_count = true);
                }*/
                
	    	}else{

	    		$this->ozonetelResponse->addHangup();
	    	}

		}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'GotDTMF') {

	    	if (isset($_REQUEST['data']) && $_REQUEST['data'] != '') {

	    		$extension = (int)$_REQUEST['data'];

	    		$extension_array = [1,2,3];

	    		if(in_array($extension,$extension_array)){

	    			if($extension == 3){

	    				$this->ozonetelCollectDtmf = new OzonetelCollectDtmf();
						$this->ozonetelCollectDtmf->addPlayText($this->ozonetelIvr());
						$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/paidvendor?fit_action=select_options");
					   	$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);

	    			}else{

	    				$ozonetelNoDetails = $this->getOzonetelNoDetails($_REQUEST['called_number']);

	    				$capture = $this->getCapture($_REQUEST['sid']);

				    	if($ozonetelNoDetails){

				    		$this->ozonetelResponse->addPlayText("please hold while we transfer your call to the concerned person");
				    		$this->ozonetelResponse->addPlayText("This call is recorderd for quality purpose");

				    		if($extension == 2){

						    	$call_jump = true;
						    	$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/paidvendor?fit_action=route_to_fitternity_1");
						    	$this->ozonetelResponse->addDial($this->jump_fitternity_no, "true");

						    	$this->updateCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$extension = false,$add_count = true, $call_jump);

						    	$this->pubNub($_REQUEST,$ozonetelNoDetails->finder->_id,$capture->_id);

				            }else{

					    		$phone = $ozonetelNoDetails->finder->contact['phone'];
					    		$phone = explode(',', $phone);
					    		$contact_no = preg_replace("/[^0-9]/", "", $phone[0]);//(string)trim($phone[0]);
					    		$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/paidvendor?fit_action=route_to_vendor_1");
					    		$this->ozonetelResponse->addDial($contact_no,"true");

				                $this->updateCapture($_REQUEST,$ozonetelNoDetails->finder->_id,$extension = false,$add_count = true);

				            }

				    	}else{

				    		$this->ozonetelResponse->addHangup();
				    	}
				    }
	    			
	    		}else{

	    			$this->ozonetelCollectDtmf = new OzonetelCollectDtmf(); //initiate new collect dtmf object
		    		$this->ozonetelCollectDtmf->addPlayText("You have dailed wrong extension number please dial correct extension number");
		    		$this->ozonetelCollectDtmf->addPlayText($this->ozonetelIvr());
		    		$this->ozonetelResponse->addCollectDtmf($this->ozonetelCollectDtmf);
	    		}

	    	}else{

	    		$this->ozonetelResponse->addHangup();
	    	}

    	}elseif (isset($_REQUEST['event']) && $_REQUEST['event'] == 'Dial') {

			if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'not_answered') {

				$capture = $this->getCapture($_REQUEST['sid']);

				if($capture->count > 1){

					$this->ozonetelResponse->addHangup();

				}else{

					$finder = Finder::find((int) $capture->finder_id);

					$city = [1,2];

					if($finder){

						//if(in_array($finder->city_id, $city) || in_array($finder->_id, $this->jump_finder_ids)) {

							if(isset($capture->call_jump)){

								$this->ozonetelResponse->addPlayText("Call diverted to another number");
								$this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/paidvendor?fit_action=route_to_fitternity_2");
						    	$this->ozonetelResponse->addDial($this->jump_fitternity_no2, "true");
						    	$this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true);
						    	$this->pubNub($_REQUEST,$capture->finder_id,$capture->_id);

			    			}else{

			    				$phone = $finder->contact['phone'];
	                            $phone = explode(',', $phone);

	                            if(isset($phone[1]) && $phone[1] != ''){
	                                $contact_no = (string)trim($phone[1]);
	                                $this->ozonetelResponse->addPlayText("Call diverted to another number");
	                                $this->ozonetelResponse->addGoto(Config::get('app.url')."/ozonetel/paidvendor?fit_action=route_to_vendor_2");
	                                $this->ozonetelResponse->addDial($contact_no,"true");
	                                $this->updateCapture($_REQUEST,$finder_id = false,$extension = false,$add_count = true);
	                            }else{
	                                $this->ozonetelResponse->addHangup();
	                            }
			    				
			    			}

	                    /*}else{

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

	                    }*/

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

	public function getOzonetelNoDetails($phone_number,$extension = false){

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
					$data['finder_vcc_mobile'] = $ozonetel_capture->finder_vcc_mobile = (isset($finder['finder_vcc_mobile']) && $finder['finder_vcc_mobile'] != '') ? $finder['finder_vcc_mobile'] : "";

					$finder_url = ($finder['commercial_type'] != 0) ? "www.fitternity.com/".$finder['slug'] : "www.fitternity.com/".$finder['city']['slug']."/".$finder['location']['slug']."/fitness";

					$data['finder_url'] = $ozonetel_capture->finder_url = $this->utilities->getShortenUrl($finder_url);

					$data['srp_link'] = $ozonetel_capture->srp_link =  $this->utilities->getShortenUrl(Config::get('app.website')."/".$finder['city']['slug']."/".$finder['location']['slug']."/fitness");
					$data['vendor_link'] = $ozonetel_capture->vendor_link = $this->utilities->getShortenUrl(Config::get('app.website')."/".$finder['slug']);

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

			if(isset($data['fit_action']) && $data['fit_action'] != ''){

				$fit_action = [];

				if(isset($ozonetel_capture->fit_action)){
					$fit_action = $ozonetel_capture->fit_action;
				}

				array_push($fit_action, $data['fit_action']);

				$ozonetel_capture->fit_action = $fit_action;
			}

			$ozonetel_capture->update();

			//send sms on call to customer
			if(($ozonetel_capture->call_status == 'answered' || $ozonetel_capture->call_status == 'not_answered' || $data['event'] == 'Disconnect') && isset($ozonetel_capture->finder_commercial_type)){

				if(!isset($ozonetel_capture->sms_send)){

					$ozonetel_capture->sms_send = true;

					$ozonetel_capture->update();

					$ozonetel_capture->customer_ozonetel_capture_sms = $this->customersms->ozonetelCapture($ozonetel_capture->toArray());

					if(!isset($ozonetel_capture->call_jump) && isset($data['finder_vcc_mobile']) && $data['finder_vcc_mobile'] != ""){
						$ozonetel_capture->finder_ozonetel_capture_sms = $this->findersms->ozonetelCapture($ozonetel_capture->toArray());
					}

					
					$ozonetel_capture->update();
				}
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

	public function ozonetelIvr(){
		
		$ivr = 'Please press 1 if you are an existing member, Press 2 if you have an enquiry about membership packages, Press 3 to here these options again';

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

	public function pubNub($data,$finder_id,$capture_id){

		$finder_id = (int) $finder_id;
		$capture_id = (int) $capture_id;

		$finder = Finder::with(array('location'=>function($query){$query->select('name','slug');}))->with(array('city'=>function($query){$query->select('name','slug');}))->find((int)$finder_id);

		$array['finder_id'] = $finder_id;
		$array['finder_name'] = ucwords($finder['title']);
		$array['finder_commercial_type'] = (int)$finder['commercial_type'];
		$array['finder_location'] = ucwords($finder['location']['name']);
		$array['finder_city'] = ucwords($finder['city']['name']);
		$array['customer_number'] = $data['cid'];
		$array['capture_id'] = $capture_id;
		$array['vendor'] = ucwords($finder['title'])." | ".ucwords($finder['location']['name'])." | ".ucwords($finder['city']['name']);
		$array['finder_slug'] = $finder['slug'];

		Log::info("pubNub array : ",$array);

		$pubnub = new \Pubnub\Pubnub('pub-c-df66f0bb-9e6f-488d-a205-38862765609d', 'sub-c-d9cf3842-cf1f-11e6-90ff-0619f8945a4f');
 
        $pubnub->publish('fitternity_ozonetel',$array);

		return 'success';
	}


 }