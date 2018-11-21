<?php 

// function qwqwas($t){
	
// 	echo count(func_get_args());
// }
// qwqwas(121);


// $a=[["start"=>8,"end"=>9,price=>1000],
// 		["start"=>9,"end"=>10,price=>1000],["start"=>8,"end"=>9,price=>1000],
// 		["start"=>12,"end"=>13,price=>1000],["start"=>14,"end"=>15,price=>1000],
// 		["start"=>17,"end"=>18,price=>1000],["start"=>18,"end"=>19,price=>1000]
// ]
// echo doubleval("09.30 pm");
// $d=date("YY-mm-dd",time());
// $dd=new DateTime();
// $dd->setDate(2018, 04,7 );
// $arr=["sunday","monday","tuesday","wednesday","thursday","friday","saturday"];
// echo $arr[date("w", time())];
// echo date('w', $dd->getTimestamp());
// echo $dd;
/* $arr=[["day"=>'monday',"time"=>[["slot_start"=>9,"slot_end"=>10],["slot_start"=>'10',"slot_end"=>11],["slot_start"=>12,"slot_end"=>13]]],["day"=>'thursday',"time"=>555555],["day"=>'sunday',"time"=>4424]];
$day="monday";

$start=9;
$end=9.3;
$r=array_values(array_filter($arr, function($a)use($day){
	return $a['day'] ==$day;
}));
	if(!empty($r[0])&&!empty($r[0]['time']))
	{
		$r=$r[0]['time'];
		print_r($r);
		$r1=array_values(array_filter($r, function($a) use ($start,$end){
			return $a['slot_start'] >=$start&&$a['slot_end'] <=$end;
		}));
			if(!empty($r[0])&&!empty($r[0]['time']))
			echo $r1[0]['slot_end'];
	} */
// echo $r[0]['day'];
// echo implode("",$r);

			
			
		
// 			$d=strtotime("today");
// 			$rr=new DateTime(date("Y-M-d",$d));
// 			echo $rr->;
// echo strtotime("2018-07-27");

// 	$a=["a"=>12,"sd"=>343];

// 	$temp=[];
// 	foreach ($a as $k => $v)
// 		(!empty($k)&&!empty($v))?array_push($temp,["field"=>$k,"value"=>$v]):"";
// 		echo count($temp)
	


$vendor_ids = [


	["id"=>579,"name"=>	"Gold's Gym Bandra West	Bandra West"],
	["id"=>1029,"name"=>	"Your Fitness Club	Mumbai Central"],
	["id"=>1030,"name"=>	"Your Fitness Club	Sion"],
	["id"=>1034,"name"=>	"Your Fitness Club	Kharghar"],
	["id"=>1233,"name"=>	"Gold's Gym Lower Parel	Lower Parel"],
	["id"=>1257,"name"=>	"Gold's Gym Andheri East	Andheri East"],
	["id"=>1259,"name"=>	"Gold's Gym Lokhandwala	Lokhandwala"],
	["id"=>1260,"name"=>	"Gold's Gym Kandivali East	Kandivali East"],
	["id"=>1261,"name"=>	"Gold's Gym Kandivali West	Kandivali West"],
	["id"=>1262,"name"=>	"Gold's Gym Vashi	Vashi"],
	["id"=>1263,"name"=>	"Gold's Gym Goregaon East	Goregaon East"],
	["id"=>1266,"name"=>	"Gold's Gym Powai	Powai"],
	["id"=>1450,"name"=>	"Talwalkars Wadala	Wadala"],
	["id"=>1455,"name"=>	"Talwalkars Panchpakhadi	Thane West"],
	["id"=>1456,"name"=>	"Talwalkars (Chestnut Plaza)	Thane West"],
	["id"=>1484,"name"=>	"Anytime Fitness Lokhandwala	Lokhandwala"],
	["id"=>1580,"name"=>	"Powerhouse Gym Bandra East	Bandra East"],
	["id"=>1581,"name"=>	"Powerhouse Gym Ghatkopar East	Ghatkopar East"],
	["id"=>1582,"name"=>	"Powerhouse Gym Hughes Road	Hughes Road"],
	["id"=>1583,"name"=>	"Powerhouse Gym Prabhadevi	Prabhadevi"],
	["id"=>1584,"name"=>	"Powerhouse Gym Andheri East	Andheri East"],
	["id"=>1602,"name"=>	"Powerhouse Gym Chembur East	Chembur East"],
	["id"=>1604,"name"=>	"Powerhouse Gym Juhu	Juhu"],
	["id"=>1605,"name"=>	"Powerhouse Gym Mumbai Central	Mumbai Central"],
	["id"=>1606,"name"=>	"Powerhouse Gym Santacruz West	Santacruz West"],
	["id"=>1607,"name"=>	"Powerhouse Gym Vile Parle East	Vile Parle East"],
	["id"=>1705,"name"=>	"Your Fitness Club	Kandivali East"],
	["id"=>1706,"name"=>	"Your Fitness Club	Charni Road"],
	["id"=>2235,"name"=>	"Powerhouse Gym Matunga East	Matunga East"],
	["id"=>2236,"name"=>	"Powerhouse Gym Kandivali West	Kandivali West"],
	["id"=>2545,"name"=>	"Golds Gym	Thane West"],
	["id"=>6893,"name"=>	"Powerhouse Gym Borivali West	Borivali West"],
	["id"=>7064,"name"=>	"Powerhouse FX Juhu	Juhu"],
	["id"=>7407,"name"=>	"Your Fitness Club	Tardeo"],
	["id"=>9111,"name"=>	"Anytime Fitness	Vile Parle West"],
	["id"=>9459,"name"=>	"Anytime Fitness	Khar West"],
	["id"=>9872,"name"=>	"Your Fitness Club IC Colony	Borivali West"],
	["id"=>11230,"name"=>	"Talwalkars FLC, Juhu Versova	Versova"],
	["id"=>11235,"name"=>	"Talwalkars Warden Road	Breach Candy"],
	["id"=>12768,"name"=>	"Your Fitness Club Mazgaon	Byculla"],
	["id"=>1,"name"=>	"20-15 Fitness	Tardeo"],
	["id"=>424,"name"=>	"JG's Fitness Centre	Santacruz West"],
	["id"=>613,"name"=>	"CrossFit OM	Juhu"],
	["id"=>824,"name"=>	"People's Gym	Malad West"],
	["id"=>941,"name"=>	"5 Fitness Club	Cuffe Parade"],
	["id"=>971,"name"=>	"Hyatt Regency - The Club Prana Spa and Fitness Centre	Andheri East"],
	["id"=>978,"name"=>	"Hotel Sofitel	Bandra East"],
	["id"=>980,"name"=>	"The Resort Hotel	Malad West"],
	["id"=>1031,"name"=>	"The Yoga House	Bandra West"],
	["id"=>1429,"name"=>	"Viikings Trance Fitness	Powai"],
	["id"=>1490,"name"=>	"Bodyholics Combine Training Gym	Lokhandwala"],
	["id"=>1493,"name"=>	"Zumba with Master Trainer Sucheta Pal and Team	Bandra West"],
	["id"=>1766,"name"=>	"Tangerine Arts Studio	Bandra West"],
	["id"=>2209,"name"=>	"Tribal Combat	Andheri West"],
	["id"=>2818,"name"=>	"Arts in Motion	Sion"],
	["id"=>3442,"name"=>	"Zion Fitness	Andheri East"],
	["id"=>4534,"name"=>	"CrossFit Blackfire	Andheri West"],
	["id"=>4742,"name"=>	"Arts In Motion	Khar West"],
	["id"=>6049,"name"=>	"Alistair's Dance Academy	Vashi"],
	["id"=>6259,"name"=>	"Perfect Gym	Bhandup"],
	["id"=>6377,"name"=>	"The Space	Juhu"],
	["id"=>7438,"name"=>	"The Square	Powai"],
	["id"=>7656,"name"=>	"El Gymnasio	Malad West"],
	["id"=>7878,"name"=>	"The Freakout Garage	Prabhadevi"],
	["id"=>8546,"name"=>	"CrossFit BPC (TFF)	Goregaon West"],
	["id"=>8892,"name"=>	"Grand Hyatt Mumbai	Santacruz East"],
	["id"=>8910,"name"=>	"Four Points Gym	Vashi"],
	["id"=>9365,"name"=>	"Indus Fitness Edge	Powai"],
	["id"=>9404,"name"=>	"Studio 23	Churchgate"],
	["id"=>9419,"name"=>	"CrossFit 7 Seas	Kandivali East"],
	["id"=>9427,"name"=>	"Shivfit Bandra	Bandra West"],
	["id"=>9436,"name"=>	"United Strength	Vile Parle West"],
	["id"=>9439,"name"=>	"JW Marriott Mumbai Sahar	Andheri East"],
	["id"=>9932,"name"=>	"Multifit - Andheri	Andheri West"],
	["id"=>10119,"name"=>	"Ramada Powai C/o Saryu Properties & Hotels Pvt Ltd	Powai"],
	["id"=>10515,"name"=>	"CrossFit MyDen	Vashi"],
	["id"=>10567,"name"=>	"Rudebox Fitness	Khar West"],
	["id"=>10571,"name"=>	"Change	Powai"],
	["id"=>10965,"name"=>	"Smaaash Shivfit	Lower Parel"],
	["id"=>11159,"name"=>	"Yasmin Karachiwala's Fitocratic	Kemps Corner"],
	["id"=>11183,"name"=>	"Body Bar	Byculla"],
	["id"=>11231,"name"=>	"Concept 360	Ghatkopar East"],
	["id"=>11246,"name"=>	"The Flex Studio	Lokhandwala"],
	["id"=>11475,"name"=>	"Gym Arenaa 	CBD Belapur"],
	["id"=>11810,"name"=>	"Kris Gethin Gym	Chembur East"],
	["id"=>12046,"name"=>	"Reset	Bandra West"],
	["id"=>12157,"name"=>	"CrossFit Real Life	Bandra West"],
	["id"=>12164,"name"=>	"Alpha 7 Seas	Lokhandwala"],
	["id"=>12771,"name"=>	"Footprint Academy Of Holistic Fitness	Kandivali West"],
	["id"=>13660,"name"=>	"F45	Juhu"],
	["id"=>13709,"name"=>	"Movement Sanctuary 	Bandra West"],
	["id"=>14016,"name"=>	"Fit Factory	Andheri west"],
	["id"=>2252,"name"=>	"Fytnation	CBD Belapur"],
	["id"=>14545,"name"=>	"The Cloud 9 Fitness Club - Malad	Malad West"],
	["id"=>14625,"name"=>	"Shivfit Shakti	Thane West"],
	["id"=>9187,"name"=>	"Four Seasons Spa 	Worli "],
	["id"=>14410,"name"=>	"Studio 5 Performance - Crossfit Shakti	Sanpada"],
	["id"=>14453,"name"=>	"X Fitt Forever - Andheri	Andheri East"],
	["id"=>14185,"name"=>	"Diamond Muscles Gym	Goregaon East"],
	["id"=>13231,"name"=>	"Penta Fitness	Kharghar"],
	["id"=>14062,"name"=>	"J And U Fiternal 	Thane West"],
	["id"=>12912,"name"=>	"Heikrujam Mixed Martial Arts Studio	Andheri West"],
	["id"=>13054,"name"=>	"ARC 909 Fitness & Dance Studio	Kandivali West"],
	["id"=>9671,"name"=>	"Impetus	Powai"],
	["id"=>8848,"name"=>	"Sun N Sand	Juhu"],
	["id"=>8865,"name"=>	"The Westin	Goregoan East"],
	["id"=>9165,"name"=>	"Renaissance Mumbai Convention Centre Hotel	Powai"],
	["id"=>647,"name"=>	"Fitness Warehouse	Kandivali West"],
	["id"=>9575,"name"=>	"Transcend Holistic Fitness L.L.P.	Prabhadevi"],
	["id"=>13801,"name"=>	"Fitness Thirst	Vikhroli"],
	["id"=>15210,"name"=>	"Diva Yoga - Women Only	"Bandra West	""],
	["id"=>576,"name"=>	"Talwlakars	Andheri East"],
	["id"=>1451,"name"=>	"Talwlakars	Bandra West"],
	["id"=>1460,"name"=>	"Talwlakars	Bhayandar"],
	["id"=>1647,"name"=>	"Talwlakars	Chembur East"],
	["id"=>9883,"name"=>	"Talwlakars	Churchgate"],
	["id"=>2522,"name"=>	"Talwlakars	Dombivali"],
	["id"=>401,"name"=>	"Talwlakars	Goregaon West"],
	["id"=>1486,"name"=>	"Talwlakars	Kalyan"],
	["id"=>1488,"name"=>	"Talwlakars	Mira Road East"],
	["id"=>1458,"name"=>	"Talwlakars	Mulund West"],
	["id"=>1457,"name"=>	"Talwlakars	Thane West Kalwa"],
	["id"=>1487,"name"=>	"Talwlakars	Ulhasnagar"],
	["id"=>1452,"name"=>	"Talwlakars	Vile Parle East"],
	["id"=>1878,"name"=>	"Talwlakars	FC Road"],
	["id"=>2806,"name"=>	"Mickey Mehta 360Â° Wellness Temple	Charni Road"],
	["id"=>2824,"name"=>	"Mickey Mehta 360Â° Wellness Temple	Dadar"],
	["id"=>2833,"name"=>	"Mickey Mehta 360Â° Wellness Temple	Bandra West"],
	["id"=>13914,"name"=>	"Yogmudra Studio	Bandra West"],
	["id"=>380,"name"=>	"New Age Yoga Studios	Vile Parle East"],
	["id"=>8021,"name"=>	"NrityaFit Dance And Fitness	Charni Road"],
	["id"=>1873,"name"=>	"Endurance Fitness - Lokhandwala	Lokhandwala"],
	["id"=>7388,"name"=>	"Dancamaze	Andheri West"],
	["id"=>1750,"name"=>	"Befit	Juhu"],
	["id"=>11190,"name"=>	"Radiant Fitness Center	Nerul"],
	["id"=>699,"name"=>	"Perfect Fitness Center	Borivali West"],
	["id"=>12112,"name"=>	"Belly Dance With Sanjana	Khar West"],
	["id"=>6144,"name"=>	"Fusion Workouts And Dance Lavina V Khanna	Bandra West"],
	["id"=>6095,"name"=>	"Zumba And Functional Training With Daniella Gomes	Bandra West"],
	["id"=>10153,"name"=>	"Michael Phelps Swimming At Keys Nestor Hotel	Andheri East"],
	["id"=>10121,"name"=>	"Michael Phelps Swimming At Badhwar Park	Cuffe Parade"],
	["id"=>10154,"name"=>	"Michael Phelps Swimming At Evershine Club	Kandivali East"],
	["id"=>1691,"name"=>	"Zumba® With Yogesh Kushalkar	Lokhandwala"],
	["id"=>180,"name"=>	"Carewell Fitness The Gym	Powai"],
	["id"=>14518,"name"=>	"i5 Fitness	Andheri East"],
	["id"=>1667,"name"=>	"The Hive Gym	Versova"],
	["id"=>10967,"name"=>	"The Cloud 9 Fitness Club	Lokhandwala"],
	["id"=>15152,"name"=>	"Michael Phelps Swimming At Nitro Sport And Fitness Center	Thane East"],
	["id"=>1935,"name"=>	"Multifit Wellness	Kalyani Nagar"],
	["id"=>9423,"name"=>	"Multifit	NIBM"],
	["id"=>9481,"name"=>	"Multifit	Kharadi"],
	["id"=>9954,"name"=>	"Multifit - Aundh	Aundh"],
	["id"=>10970,"name"=>	"Multifit Satara Road	Satara Road"],
	["id"=>11021,"name"=>	"Multifit Senapati Bapat Road	Senapati Bapat Road"],
	["id"=>11223,"name"=>	"Multifit Hadapsar	Hadapsar"],
	["id"=>13094,"name"=>	"Multifit Pimple Saudagar	Pimple Saudagar"],
	["id"=>13898,"name"=>	"Multifit - Viman Nagar	Viman Nagar"],
	["id"=>14102,"name"=>	"Multifit - Sinhagad Road 	Sinhagad Road"],
	["id"=>14107,"name"=>	"Multifit - Pradhikaran 	Pradhikaran "],
	["id"=>1860,"name"=>	"Golds Gym	Aundh"],
	["id"=>1874,"name"=>	"Golds Gym	Hadapsar"],
	["id"=>1875,"name"=>	"Gold's Gym	Viman Nagar"],
	["id"=>1876,"name"=>	"Gold's Gym NIBM	NIBM"],
	["id"=>2105,"name"=>	"Gold's Gym	Kalyani Nagar"],
	["id"=>2194,"name"=>	"Gold's Gym	Law College Road"],
	["id"=>5967,"name"=>	"Gold's Gym	Pimple Saudagar"],
	["id"=>6593,"name"=>	"Golds Gym	Satara Road"],
	["id"=>12569,"name"=>	"Gold's Gym	Senapati Bapat Road"],
	["id"=>13965,"name"=>	"Gold's Gym PCMC Akurdi 	Nigdi"],
	["id"=>11811,"name"=>	"Powerhouse Gym Hinjewadi	Hinjewadi"],
	["id"=>1861,"name"=>	"Talwalkars Kothrud	Kothrud"],
	["id"=>1862,"name"=>	"Talwalkars Baner	Baner"],
	["id"=>1879,"name"=>	"Talwalkars Lulla Nagar Wanowrie	Wanowrie"],
	["id"=>1880,"name"=>	"Talwalkars Viman Nagar	Viman Nagar"],
	["id"=>2293,"name"=>	"Talwalkars Koregaon Park	Koregaon Park"],
	["id"=>2425,"name"=>	"Talwalkars Sahakarnagar	Satara Road"],
	["id"=>11239,"name"=>	"Talwalkars HiFi BT Kawade Road	Ghorpadi"],
	["id"=>12073,"name"=>	"Talwalkars Pimple Saudagar	Pimple Saudagar"],
	["id"=>1801,"name"=>	"Core Fitness	Viman Nagar"],
	["id"=>1828,"name"=>	"P40X Fitness Studio	FC Road"],
	["id"=>1846,"name"=>	"Dotfit Fitness	Baner"],
	["id"=>1863,"name"=>	"Optimum Health	Kothrud"],
	["id"=>1865,"name"=>	"Optimum Health	Pimple Saudagar"],
	["id"=>1895,"name"=>	"Khalsa Gyym	Kharadi"],
	["id"=>1908,"name"=>	"Air Life Studio	Aundh"],
	["id"=>1968,"name"=>	"Body Fuel	Kothrud"],
	["id"=>1971,"name"=>	"Lyfe Indoor Cycling	Kothrud"],
	["id"=>2044,"name"=>	"CrossFit Chakra	Koregaon Park"],
	["id"=>2076,"name"=>	"Shree Gym	Viman Nagar"],
	["id"=>2148,"name"=>	"Stretch Fitness and Beyond	Wanowrie"],
	["id"=>2223,"name"=>	"Health Mantra Fitness	Pimpri Chinchwad"],
	["id"=>2677,"name"=>	"Total Yoga	Kalyani Nagar"],
	["id"=>2723,"name"=>	"Lyfe Indoor Cycling	Satara Road"],
	["id"=>4815,"name"=>	"Arkfit Arena	Deccan"],
	["id"=>6022,"name"=>	"Total Yoga	Kondhwa"],
	["id"=>6227,"name"=>	"CrossFit GrayBar	Viman Nagar"],
	["id"=>7003,"name"=>	"Metafit	Wakad"],
	["id"=>8728,"name"=>	"WOW Fitness Club	Katraj"],
	["id"=>10503,"name"=>	"Crossfit Vyom	Koregaon Park"],
	["id"=>10669,"name"=>	"Health Mantra Fitness Pimpri	Pimpri"],

];

foreach($vendor_ids as $_id){

	define("ENCRYPTION_KEY", "FITITRNTY");
	$string = ['owner'=>'fitternity','order_id'=>$order['_id'];
	// echo json_encode($string); 
	echo "<div>".$_id['id']."</div>\n";
	echo "<div>".$_id['name']."</div>\n";
	$encrypted = encrypt(json_encode($string), ENCRYPTION_KEY);
	echo '<div><img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.urlencode($encrypted).'&choe=UTF-8" title="Link to Google.com" /></div>';
	echo "<hr>\n";
}
// echo "<br />";
// echo ["a"=>12];


// // echo $decrypted =json_decode(preg_replace('/[\x00-\x1F\x7F]/', '', decrypt($encrypted, ENCRYPTION_KEY)),true)['vendor_id'];



// /**
//  * Returns an encrypted & utf8-encoded
//  */
function encrypt($pure_string, $encryption_key) {
	$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	$encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, $pure_string, MCRYPT_MODE_ECB, $iv);
	return bin2hex($encrypted_string);
}

// /**
//  * Returns decrypted original string
//  */
function decrypt($encrypted_string, $encryption_key) {
	
	$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	$decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, hex2bin($encrypted_string), MCRYPT_MODE_ECB, $iv);
	return $decrypted_string;
}
	
// $cur=time();
// // echo $cur;
// echo date("H",strtotime("+1 hour",$cur));
// echo date("H",strtotime($cur));

// echo strtotime("2008-12-13 20");
// echo $start_date_time=strtotime(date("Y-m-d H:m:s"));

// $rr=new DateTime(date("Y-m-d 23:59:59",strtotime("-1 days")));
// echo $rr->format("y-m-d H:m:s");
// echo new DateTime(date("Y-m-d H:i:s", mktime(0,0,0)))
// $from_time = strtotime("2008-12-13 10:21:00");
// echo round(abs($to_time - $from_time) / 60,2). " minute";

// $date=date_create("2018-07-25");
// echo date_format($date,"l, jS M Y H:ia");


?>