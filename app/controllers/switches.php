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


["id"=>12208,	"name"=>"Multifit	Baner"],
["id"=>10674,	"name"=>"Multifit	Kothrud"],
["id"=>1667,	"name"=>"The Hive Gym	Versova"],
["id"=>14518,	"name"=>"i5 Fitness	Andheri East"],
["id"=>12573,	"name"=>"Ritz Gym	Ghansoli"],
["id"=>12574,	"name"=>"Ritz Gym	Kopar Khairane"],

];

foreach($vendor_ids as $_id){

	define("ENCRYPTION_KEY", "FITITRNTY");
	$string = ['owner'=>'fitternity','vendor_id'=>$_id['id']];
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