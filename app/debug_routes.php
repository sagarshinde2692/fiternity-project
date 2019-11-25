<?php



#####################################################################################
/************************ MIGRATIONS SECTION START HERE ***************************/

Route::get('migrations/country', 'MigrationsController@country');
Route::get('migrations/city', 'MigrationsController@city');
Route::get('migrations/locationcluster', 'MigrationsController@locationcluster');
Route::get('migrations/category', 'MigrationsController@category');
Route::get('migrations/location', 'MigrationsController@location');
Route::get('migrations/offerings', 'MigrationsController@offerings');
Route::get('migrations/facilities', 'MigrationsController@facilities');
Route::get('migrations/vendors', 'MigrationsController@vendors');
Route::get('migrations/vendorservicecategory', 'MigrationsController@vendorservicecategory');
Route::get('migrations/vendorPriceAverage', 'MigrationsController@vendorPriceAverage');
Route::get('emailtest', 'DebugController@testEmail');
Route::get('migrations/order', 'MigrationsController@order');
Route::get('migrations/ratecard', 'MigrationsController@ratecard');
Route::get('cleartrip/sendmail', 'DebugController@cleartrip');
Route::get('monsoonsale', 'DebugController@monsoonSale');
Route::get('deactivate/ozoneteldid', 'DebugController@deactivateOzonetelDid');
Route::get('unset/viptrial', 'DebugController@unsetVipTrial');
Route::get('removepersonaltrainerstudio', 'DebugController@removePersonalTrainerStudio');

Route::get('migrations/bdresearch', 'MigrationsController@bdResearch');
Route::get('migrations/outreachrm', 'MigrationsController@outreachRm');
Route::get('migrations/commercial', 'MigrationsController@commercial');
Route::get('migrations/onboard', 'MigrationsController@onboard');
Route::get('migrations/feedback', 'MigrationsController@feedback');
Route::get('newordermigration', 'DebugController@newOrderMigration');
Route::get('ppsRepeat', 'DebugController@ppsRepeat');
Route::get('set25FlatDiscountFlag', 'DebugController@set25FlatDiscountFlag');



############################################################################################
/************************ REVERSE MIGRATIONS SECTION START HERE ***********************/

Route::get('reversemigrations/country', 'ReversemigrationsController@country');
Route::get('reverse/migration/{colllection}/{id}','MigrationReverseController@byId');
Route::get('reverse/migration/deleteworkoutsessionratecard','MigrationReverseController@deleteWorkoutSessionRatecard');

Route::get('latlonswap', 'DebugController@latLonSwap');
Route::get('latlonswapapi', 'DebugController@latLonSwapApi');
Route::get('latlonswapservice', 'DebugController@latLonSwapService');
Route::get('latlonswapserviceapi', 'DebugController@latLonSwapServiceApi');
Route::get('addexpirydate', 'DebugController@addExpiryDate');
Route::get('unsetstartendservice', 'DebugController@unsetStartEndService');


Route::get('xyz','DebugController@xyz');
Route::get('yes/{msg}','DebugController@yes');
Route::get('ozonetelcapturebulksms','DebugController@ozonetelCaptureBulkSms');
Route::get('orderfollowup','DebugController@orderFollowup');
Route::get('trialfollowup','DebugController@trialFollowup');
Route::get('durationdaystring','DebugController@durationDayString');


// please dont merge in live or production environment
Route::get('transaction/delete/{table}/{email}', function ($table,$email){

    $emails = ["ankit13.kumar@gmail.com","sailismart@fitternity.com","amrita.ghosh.cipl@gmail.com","utkarshmehrotra@fitternity.com","amritaghosh@fitternity.com","sanjaysahu@fitternity.com","sanajy.id7@gmail.com","gauravravi@fitternity.com","gauravraviji@gmail.com","maheshjadhav@fitternity.com","ut.mehrotra@gmail.com"];

    if(in_array($email, $emails)){

        if($table == "trial"){
            DB::connection('mongodb')->table('booktrials')->where('customer_email', trim($email))->delete();
        }

        if($table == "order"){
            DB::connection('mongodb')->table('orders')->where('customer_email', trim($email))->delete();
        }

        if($table == "capture"){
            DB::connection('mongodb')->table('captures')->where('email', trim($email))->delete();
        }

        echo "valid email";

    }else{

        echo "invalid email";

    }

});

Route::get('syncsharecustomerno', function (){

    $vendors = Vendor::where('commercial', 'exists', true )->get(['commercial']);
    foreach ($vendors as $vendor){
        $share_customer_no 					=  (isset($vendor['commercial']['share_customer_number']) && $vendor['commercial']['share_customer_number'] === true) ? "1" : "0";
        DB::connection('mongodb2')->table('finders')->where('_id', intval($vendor['_id']))->update(['share_customer_no' => $share_customer_no]);
    }
    echo "done";

});


Route::get('checkfileons3', function (){

    $booktrial          = Booktrial::find(43208);
    $schedule_date_time = strtotime($booktrial['schedule_date_time']);
    $currentTime        = time();

    if($currentTime < $schedule_date_time){
        $schedule_passed_flag = "before";
    }else{
        $schedule_passed_flag = "after";
    }

    echo $schedule_passed_flag;
    exit;
    $s3 = \AWS::get('s3');

    $objects = $s3->getIterator('ListObjects', array(
        'Bucket' => "b.fitn.in",
        "Prefix" => 'f/c/'
    ));


// Use the high-level iterators (returns ALL of your objects).
    try {
        $objects = $s3->getIterator('ListObjects', array(
            'Bucket' => "b.fitn.in",
            "Prefix" => 'f/c/'
        ));

        echo "Keys retrieved!\n";
        foreach ($objects as $object) {
            echo $object['Key'] . "<br>";
        }
    } catch (S3Exception $e) {
        echo $e->getMessage() . "\n";
    }



});


Route::get('inserthexcolor', function (){
    $csvFileData = public_path()."/hex_code.csv";
    $csvData = array_map('str_getcsv', file(public_path()."/hex_code.csv"));
    foreach ($csvData as $data) {
//        print_pretty($data);exit;
        DB::connection('mongodb')->table('hexcodercolors')->insert(['image' => $data[0], 'color_code' => $data[1], 'type' => 'finder_cover']);
    }
});




Route::get('managehexcolor', function (){

    $finders = Finder::where('coverimage','exists', true)->where('coverimage','!=','')->get(['_id','coverimage'])->toArray();

    foreach ($finders as $finder) {
        $hexcolorRerocd  =  DB::connection('mongodb')->table('hexcodercolors')->where('image', $finder['coverimage'])->first();

        if($hexcolorRerocd){
            DB::connection('mongodb')->table('finders')->where('_id', intval($finder['_id']))->update(['finder_coverimage_color' => $hexcolorRerocd['color_code']]);
            $vendor = DB::connection('mongodb2')->table('vendors')->where('_id', intval($finder['_id']))->first();

            // if($vendor){
            //     $media = [
            //         'images' => [
            //             'cover' => ($vendor['media']['images']['cover']) ? $vendor['media']['images']['cover'] : "",
            //             'cover_color' => ($vendor['media']['images']['cover_color']) ? $hexcolorRerocd['color_code'] : "",
            //             'logo' => ($vendor['media']['images']['logo']) ? $vendor['media']['images']['logo'] : "",
            //             'gallery' => ($vendor['media']['images']['gallery']) ? $vendor['media']['images']['gallery'] : []
            //         ],
            //         'videos' => ($vendor['media']['videos']) ? $vendor['media']['videos'] : []
            //     ];
            //     DB::connection('mongodb2')->table('vendors')->where('_id', intval($finder['_id']))->update(['media' => $media]);
            // }//vendor

        } //hexcolor
    }
	return $finders;
});



Route::get('finderallemails', function (){

    ini_set('memory_limit', '500M');
    set_time_limit(3000);

    $finders = Finder::active()->where('finder_vcc_email','exists', true)->where('finder_vcc_email','!=','')
                        ->with(array('category'=>function($query){$query->select('_id','name');}))
                        ->with(array('location'=>function($query){$query->select('_id','name');}))
                        ->with(array('city'=>function($query){$query->select('_id','name');}))
                        ->get(['_id','title','finder_vcc_email','category','category_id','location','location_id','city','city_id']);

    $file_name = "finder_info_email_ids";

    $headers = [
        'Content-type'        => 'application/csv'
        ,   'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
        ,   'Content-type'        => 'text/csv'
        ,   'Content-Disposition' => 'attachment; filename='.$file_name.'.csv'
        ,   'Expires'             => '0'
        ,   'Pragma'              => 'public'
    ];

    $output = "ID, NAME, CATEGORY, LOCATION, CITY, COMMERCIAL, BUSINESS, EMAIL  \n";

    foreach ($finders as $key => $value) {

        $id 					                = 		(isset($value['_id']) && $value['_id'] !="") ? $value['_id'] : "-";
        $title 					                = 		(isset($value['title']) && $value['title'] !="") ? $value['title'] : "-";
        $city 			                        = 		(isset($value['city']) && isset($value['city']['name']) && $value['city']['name'] != "") ? $value['city']['name'] : "-";
        $category 			                    = 		(isset($value['category']) && isset($value['category']['name']) && $value['category']['name'] != "") ? $value['category']['name'] : "-";
        $location 			                    = 		(isset($value['location']) && isset($value['location']['name']) && $value['location']['name'] != "") ? $value['location']['name'] : "-";
        $commercial_type_status 			    = 		(isset($value['commercial_type_status']) &&  $value['commercial_type_status'] != "") ? $value['commercial_type_status'] : "-";
        $business_type_status 			        = 		(isset($value['business_type_status']) &&  $value['business_type_status'] != "") ? $value['business_type_status'] : "-";
        $finder_vcc_email 			            = 		(isset($value['finder_vcc_email']) &&  $value['finder_vcc_email'] != "") ? str_replace(",", " ||  ",$value['finder_vcc_email']) : "-";

        $output .= "$id, $title, $category, $location, $city, $commercial_type_status, $business_type_status, $finder_vcc_email \n";
    }

    return Response::make(rtrim($output, "\n"), 200, $headers);

});


Route::get('inserthexcolor', function (){
    $csvFileData = public_path()."/hex_code.csv";
    $csvData = array_map('str_getcsv', file(public_path()."/hex_code.csv"));
    foreach ($csvData as $data) {
//        print_pretty($data);exit;
        DB::connection('mongodb')->table('hexcodercolors')->insert(['image' => $data[0], 'color_code' => $data[1], 'type' => 'finder_cover']);
    }
});




Route::get('managehexcolor', function (){

    $finders = Finder::where('coverimage','exists', true)->where('coverimage','!=','')->get(['_id','coverimage'])->toArray();

    foreach ($finders as $finder) {
        $hexcolorRerocd  =  DB::connection('mongodb')->table('hexcodercolors')->where('image', $finder['coverimage'])->first();

        if($hexcolorRerocd){
            DB::connection('mongodb')->table('finders')->where('_id', intval($finder['_id']))->update(['finder_coverimage_color' => $hexcolorRerocd['color_code']]);
            $vendor = DB::connection('mongodb2')->table('vendors')->where('_id', intval($finder['_id']))->first();

            if($vendor){
                $media = [
                    'images' => [
                        'cover' => ($vendor['media']['images']['cover']) ? $vendor['media']['images']['cover'] : "",
                        'cover_color' => ($vendor['media']['images']['cover_color']) ? $hexcolorRerocd['color_code'] : "",
                        'logo' => ($vendor['media']['images']['logo']) ? $vendor['media']['images']['logo'] : "",
                        'gallery' => ($vendor['media']['images']['gallery']) ? $vendor['media']['images']['gallery'] : []
                    ],
                    'videos' => ($vendor['media']['videos']) ? $vendor['media']['videos'] : []
                ];
                DB::connection('mongodb2')->table('vendors')->where('_id', intval($finder['_id']))->update(['media' => $media]);
            }//vendor

        } //hexcolor
    }
});











Route::get('findernames', function(){

    $vendor_ids = array_unique([1,1001,1002,1004,1005,1006,1007,1011,1013,1016,1020,1021,1024,1026,1028,1029,1030,1031,1032,1033,1034,1035,1038,1039,1040,1041,1042,1061,1068,1069,1079,108,1080,11,1104,1122,1135,114,1140,1150,1154,119,1208,1209,1214,1222,1230,1233,1242,1257,1258,1259,1260,1261,1262,1263,1265,1269,1277,1293,1295,1296,129,1309,131,1331,1332,1333,1334,1376,138,1380,1388,1389,1392,1393,1395,139,1413,1414,1421,1422,1423,1424,1427,1428,1431,1437,143,1441,1442,1444,1445,1447,1448,1469,147,1471,1472,1473,1484,1488,1489,1490,1493,1494,1495,1496,1497,1498,1499,1500,1501,1504,1507,1510,1513,1516,1518,1522,1523,1554,1560,1563,1579,1580,1581,1582,1583,1584,1587,1589,1600,1602,1603,1604,1605,1606,1607,1608,1611,1613,1614,1621,1622,1623,1624,1630,1639,1642,1646,1648,1649,1650,1651,1652,1653,1654,1655,1656,1658,166,1663,1664,1666,1667,1668,1669,167,1671,1672,1673,1676,1677,168,1682,1688,169,1690,1691,1692,1698,1699,170,1701,1704,1705,1706,1708,171,1711,1712,1720,1732,1737,1739,1749,1750,1751,1752,1756,1764,1765,1766,1767,1768,1770,1771,1773,1783,1786,1799,179,18,1800,1801,1803,1806,1809,1816,1818,1820,1824,1827,1828,1831,1832,1835,1837,1840,1842,1845,1846,1851,1853,1855,1856,1860,1862,1863,1864,1865,1867,1870,1873,1874,1875,1876,188,1883,1884,1885,1889,1891,1892,1895,1908,1913,1927,1928,1934,1935,1936,1937,1938,1939,1946,1955,1960,1961,1962,1965,1968,1971,1984,1985,1986,1989,1997,2001,2002,2004,2013,2021,2024,2029,2030,2035,2044,2050,2079,2083,2090,2101,2105,2107,2109,2117,2119,2126,2127,2134,2137,2140,2145,2147,2148,2155,2156,2157,2165,2169,217,2173,2183,2185,2187,2194,2196,2197,2198,2199,2200,2201,2207,2208,2209,2215,2220,2223,2224,223,2235,2236,2244,224,227,2281,2293,2297,22,2309,232,2378,2386,2408,2421,2424,2436,2443,2451,2459,25,2501,256,2592,26,2628,2630,2632,2640,2650,2651,2663,2667,2669,2673,2677,2678,2680,2697,2701,2707,2723,2736,2739,2757,2774,2776,2777,2778,2782,2785,2806,2807,2810,2813,2818,2821,2823,2824,2828,2833,2839,2844,2848,2860,2861,2864,2867,2873,2890,292,2969,2973,2992,2993,2994,2997,3006,303,3105,3109,3129,3172,3173,3175,3176,3178,3179,3183,3184,3186,3190,3191,3192,3193,3194,3195,3196,3197,3201,3202,3204,3206,3207,3208,3209,3210,3211,3226,3228,3229,3233,3235,3239,3253,3254,3279,328,329,3291,3296,3305,333,3330,3331,3332,3333,3335,3336,3337,3340,3341,3342,3343,3344,3345,3346,3347,3350,3351,3360,3367,3369,3371,3378,3380,3382,3387,3400,3401,3402,3403,3404,3405,3406,3407,3408,3409,341,3410,3412,3415,3416,3417,3421,3424,3443,3449,3450,3451,3456,3457,3473,3485,3491,3495,3496,3498,3499,3502,3504,3512,3514,3516,3518,351,3520,3521,3556,3557,3564,3565,357,3574,3579,3595,3609,3612,3614,3618,3619,3620,3622,3628,3649,3654,3666,3667,3678,3679,3680,369,37,3702,3716,3720,3757,3774,3775,3784,3792,3802,3807,3808,3812,3821,382,3843,3847,3854,3856,3860,3863,3871,3878,3900,3901,3904,3905,3907,3919,3926,3927,3929,3963,3965,3970,3972,3975,3977,3980,3985,3989,40,400,401,4015,4018,402,4021,4022,4027,403,4030,4032,4034,4035,4043,4045,4046,4048,4049,405,4050,4051,4059,4060,4065,4066,4070,4073,4082,4088,4098,4099,41,410,4115,4119,413,4142,4159,4163,4164,417,4173,4175,4179,418,4182,4183,4185,4198,4203,4209,4212,4213,4217,4226,4230,4232,4236,424,4248,4254,4255,4267,4272,4279,4281,4291,4307,4331,4344,4352,4370,4371,438,4388,439,4391,4397,44,4401,441,4417,442,4447,4458,4460,4481,4484,4485,4486,4489,449,4491,4516,4518,4520,4534,4568,4581,4585,4586,4587,459,46,4602,4603,4604,4607,4643,4644,4645,4650,4653,467,4677,4678,4679,4680,4682,4690,4693,4694,47,4700,4705,4706,473,4742,4749,4763,4768,4772,4773,4777,4778,4782,4784,4803,4807,4808,4814,4815,4817,4818,4819,4820,4821,4822,4823,4824,4825,4826,4834,4836,4837,4838,484,4841,4845,4853,4859,4875,4878,4901,4915,4924,4928,4929,4937,4939,4949,4956,4967,4968,4974,4980,4988,4991,4996,5027,5028,5029,5030,5040,5041,5042,5044,5045,5047,5066,5069,5070,5077,5079,5082,5083,5084,5085,5089,50,5144,5145,5146,5147,5148,5149,5150,5152,5162,5191,5200,5204,523,5241,5275,5303,530,5310,5313,5327,5331,5341,5347,5348,5349,5353,5355,5373,5374,5383,5387,5425,5444,5477,547,5502,5505,5508,5529,552,554,555,5560,5566,5570,5585,5586,559,5596,5601,5603,5617,561,5621,563,5641,5655,5657,566,567,5671,568,5684,569,5709,570,571,5711,5713,5717,5721,5725,5726,5727,5728,5729,573,5733,5735,5736,5737,5739,5744,5745,5746,5747,5748,5749,575,5750,576,5769,577,579,581,5817,5833,5842,5848,586,587,5884,5885,5887,5888,5889,5890,5892,5895,5898,590,5900,5902,5909,5928,5950,5956,5957,5958,5959,596,5962,5963,5964,5968,5970,5973,5975,5979,5986,5996,6001,6003,6005,6009,6010,6011,6013,6019,602,6021,6022,6025,6029,603,6034,604,6042,6047,6049,605,6052,6058,6064,607,608,6081,6082,609,61,610,6118,612,6125,6126,6128,6129,613,6133,6134,6139,6140,6141,6144,6151,616,6162,6166,6188,6189,619,6190,6191,6195,6197,6199,620,6202,6208,621,6212,6214,6215,6216,6218,6219,6227,6228,6230,6232,6234,6235,6239,6241,6245,6247,624,6250,6254,6257,625,6266,6280,6289,6291,6316,6317,6319,6320,6324,6333,6342,6377,6394,6397,6408,6411,6412,6414,6415,6417,6422,6427,6432,6440,6446,6450,6451,6452,6457,645,6460,6461,6462,6466,6467,6468,6479,647,6480,6496,6499,6500,6501,6503,6507,6509,6511,6513,6514,6515,6518,6525,6526,6527,6529,6530,6534,6535,6540,6548,6559,6563,6564,6567,657,6574,6587,6589,6593,6594,6598,6602,6603,6613,6614,6624,6632,6642,6644,6650,6656,6668,6680,6686,6694,6697,6698,6706,6707,6730,6747,6753,6756,6768,6774,6796,6808,6874,6876,6881,6882,6885,6888,6890,6891,6893,6894,6905,6907,6922,6933,6941,6942,6943,6950,695,6964,6972,6974,6978,6979,6982,6983,6985,6988,6991,6992,6993,6995,6996,6997,6999,7006,7009,701,7010,7011,7012,7013,7014,7015,7017,7021,7024,7028,7034,7036,7037,7038,7040,7047,7049,7053,7054,7056,7064,712,7123,7125,7127,7131,7133,7134,7135,7136,7142,7143,7145,7146,7148,714,715,7157,7159,7162,7166,7168,7174,7177,718,7187,7190,7194,7205,7211,7212,7220,7224,7267,7273,728,7297,7298,7299,7301,731,7317,7335,7338,7344,7345,7350,7354,7355,7356,7358,7359,7360,7371,7386,7400,7408,7418,741,7421,7428,7429,742,7430,7434,7435,7436,7439,7440,7441,7446,7447,7448,7450,7451,7458,7498,752,7521,7525,7532,7540,7553,7571,7585,76,7603,7616,7635,7643,7661,7663,7668,7716,7717,7724,7728,7771,7773,7786,7792,7805,782,7847,7868,7870,7872,7875,7879,7880,7883,7890,7907,7915,7922,7933,7937,8021,806,807,8094,8125,813,816,82,823,825,826,827,8289,8332,84,841,8447,845,8470,850,853,8534,8598,862,869,871,872,8728,8742,8744,877,878,879,880,881,882,8821,8837,883,8851,8861,889,8924,8937,8945,8968,900,9040,905,906,907,9112,9130,9171,9172,9173,9174,9177,9187,9215,923,9231,9240,9246,925,926,9262,9267,9268,927,9282,9301,9304,940,9400,941,9412,9415,9417,942,9423,9427,943,944,9442,9443,9447,946,9467,9470,9477,9481,949,950,9507,9508,955,957,961,962,963,964,965,966,968,969,970,971,972,973,974,975,976,977,978,979,97,980,981,982,983,984,985,986,987,988,989,99,990,991,992,995,998,999]);
    echo count($vendor_ids)."<br>";
    echo Finder::whereIn('_id',$vendor_ids)->count();
    //exit;

    return $finders   = Finder::whereIn('_id',$vendor_ids)->lists('title','_id');


});

Route::get('updatefindercategories', function(){

    $rows   = Findercategory::where('defination','exists', true)->get();
    foreach ($rows as $row){
//        return $row['defination'];
        DB::connection('mongodb2')->table('vendorcategories')->where('_id', intval($row['_id']))->update(['defination' => $row['defination']]);
    }

});



Route::get('updatefinders', function(){

    DB::connection('mongodb')->table('services')->whereNotIn('city_id',[1])->update(['vip_trial' => '0']);
    DB::connection('mongodb2')->table('vendorservices')->whereNotIn('city_id',[1])->update(['vip_trial' =>  false]);

});


Route::get('migratescheduletype', function(){



	DB::connection('mongodb2')->table('schedules')->update(['type' => "trial"]);


});


Route::get('migratecustomofferorder', function(){


    $rows   = Booktrial::where('customofferorder_id','exists', true)->get();
    foreach ($rows as $row){
        if(is_int($row['customofferorder_id'])){
            DB::table('booktrials')->where('_id', intval($row['_id']))->update(['fitadmin_customofferorder_id' => $row['customofferorder_id'] ]);
            DB::table('booktrials')->where('_id', intval($row['_id']))->unset('customofferorder_id');
        }
    }

    $rows   = Order::where('customofferorder_id','exists', true)->get();
    foreach ($rows as $row){
        if(is_int($row['customofferorder_id'])){
            DB::table('orders')->where('_id', intval($row['_id']))->update(['fitadmin_customofferorder_id' => $row['customofferorder_id'] ]);
            DB::table('orders')->where('_id', intval($row['_id']))->unset('customofferorder_id');
        }
    }

});



Route::get('checkozoneteljump/{finderid}', function($finderid){


    $jump_finder_ids    =   [1,10,30,40];
    $jump_start_time    =   strtotime( date("d-m-Y")." 09:00:00");
    $jump_end_time      =   strtotime( date("d-m-Y")." 21:00:00");
    $finderid           =   intval(trim($finderid));

    $current_date_time  =   time();
    echo "<br>jump_start_time : $jump_start_time <br><br>  jump_end_time : $jump_end_time <br><br>  current_date_time : $current_date_time <br>";
    echo "<br>";

    if($jump_start_time < $current_date_time && $current_date_time < $jump_end_time  && in_array($finderid, $jump_finder_ids)){

        echo "jump dial to fitternity";

    }else{

        echo "not jump dial to vendor";

    }



});

Route::get('/removevip', function() { 
	return Finder::whereNotIn('_id',array(3305))->take(5)->get();
	$services = Service::where("vip_trial","1")->where("city_id","<>",1)->get(array('name','vip_trial'));
	foreach ($services as $service) {
		$service->vip_trial = "0";
		$service->update();
	}
	return $services;
});



//REMOVE OLD RATECARDS
Route::get('/removeunwantedratecardsolddb/{offeset?}/', function($offset = ""){

    ini_set('memory_limit', '500M');
    set_time_limit(3000);

    if($offset == ""){
        $service_ids = DB::connection('mongodb2')->table('vendorservices')->lists('_id');
    }else{
        $service_ids = DB::connection('mongodb2')->table('vendorservices')->take(5000)->skip(intval($offset))->lists('_id');
    }

//    $service_ids = [2714];
    foreach ($service_ids as $service_id) {
        $new_ratecard_ids       =   DB::connection('mongodb2')->table('ratecards')->where('vendorservice_id', intval($service_id))->where('hidden', false)->lists('_id');
        $old_ratecard_ids       =   DB::connection('mongodb')->table('ratecards')->where('service_id', intval($service_id))->whereNotIn('_id', $new_ratecard_ids)->lists('_id');
        $deleteRatecardids      =   DB::connection('mongodb')->table('ratecards')->where('service_id', intval($service_id))->whereIn('_id', $old_ratecard_ids)->delete();

    }

});


Route::get('/updatevendorwebsitecontact', function() {

    ini_set('memory_limit', '500M');
    set_time_limit(3000);

//    $vendor_ids = DB::connection('mongodb2')->table('vendors')->where('contact.phone.mobile', 'exists', true)->where('contact.phone.mobile', [])->count();
    $vendor_ids = DB::connection('mongodb2')->table('vendors')->where('contact.phone.mobile', 'exists', true)->where('contact.phone.mobile', [])->lists("_id");

    foreach ($vendor_ids as $vendor_id){

        $finder = Finder::find(intval($vendor_id));

        if($finder){

            if(isset($finder->contact['phone']) && $finder->contact['phone'] != ""){
                $phone              =   [];
                $phone['mobile']    =   $phone['landline'] =  [];
                $phone_arr          =   array_map('trim', explode(",",str_replace("/", ",", trim($finder->contact['phone']) )) ) ;

                if(count($phone_arr) > 0){
                    foreach ($phone_arr as $key => $value) {
                        $varx = $value;
                        if(starts_with($varx, '02') || starts_with($varx, '2') || starts_with($varx, '33') || starts_with($varx, '011') || starts_with($varx, '1') || starts_with($varx, '11')){
                            array_push($phone['landline'], ltrim($varx,"+"));
                        }else{
                            $find_arr= ["+","+(91)-","(91)","(91)-","91-"];
                            $replace_arr= ["","","","",""];
                            $clean_mobile_no = trim(str_replace($find_arr, $replace_arr, $varx));
                            array_push($phone['mobile'], ltrim($clean_mobile_no,"+"));
                        }
                    }
                }

                $existVendorData                       =        DB::connection('mongodb2')->table('vendors')->where('_id', intval($vendor_id))->first();
                $contactData                           =       (isset($existVendorData['contact'])) ? $existVendorData['contact'] : [];
                $contactData['phone']       =       $phone;
//                return $contactData;
                $updateWebsiteContact       =   DB::connection('mongodb2')->table('vendors')->where('_id', intval($vendor_id))->update(['contact'=>$contactData]);

            }

        }

    }

});



Route::get('/removeworkoutsession', function() {

    ini_set('memory_limit', '500M');
    set_time_limit(3000);

//    $trialRatecard  =   DB::connection('mongodb')->table('ratecards')->where('type', 'workout session')->delete();
//    $trialRatecard  =   DB::connection('mongodb2')->table('ratecards')->where('type', 'workout session')->delete();
});


Route::get('createworkoutsessionifnotexist/{offeset?}/', function($offset = ""){

    ini_set('memory_limit', '500M');
    set_time_limit(3000);

    if($offset == ""){
        $service_ids = DB::connection('mongodb2')->table('vendorservices')->lists('_id');
    }else{
        $service_ids = DB::connection('mongodb2')->table('vendorservices')->take(5000)->skip(intval($offset))->lists('_id');
    }


    //1671,1645,1429,7146,1664,1020,1518

//    $service_ids = DB::connection('mongodb2')->table('vendorservices')->whereIn('vendor_id',[1020])->lists('_id');

//    $service_ids = [1937];
//    return $service_ids;


    foreach ($service_ids as $service_id) {

        $workoutSessionRatecard_exists_cnt = DB::connection('mongodb2')->table('ratecards')->where('vendorservice_id', intval($service_id))->where('type', 'workout session')->where('hidden', false)->count();

        if($workoutSessionRatecard_exists_cnt < 1){

            $trialRatecard_exists_cnt   =   DB::connection('mongodb2')->table('ratecards')->where('vendorservice_id',intval($service_id))->where('type', 'trial')->where('hidden', false)->count();
            if($trialRatecard_exists_cnt > 0){
                if($trialRatecard_exists_cnt < 2){
                    $trialRatecard  =   DB::connection('mongodb2')->table('ratecards')->where('vendorservice_id',intval($service_id))->where('type', 'trial')->where('hidden', false)->first();
                }else{
                    $trialRatecard  =   DB::connection('mongodb2')->table('ratecards')->where('vendorservice_id',intval($service_id))->where('type', 'trial')->where('quantity',1)->where('hidden', false)->first();
                }

                if($trialRatecard){
                    $lastlocationtagid      =   DB::connection('mongodb2')->table('ratecards')->max('_id');
                    $newratecardid          =   intval($lastlocationtagid) + 1;
                    $insertData             =   $trialRatecard;
                    $insertData['type']     =   'workout session';
                    $workoutSessionPrice     =   intval($trialRatecard['price']);

                    if($workoutSessionPrice   == 0){
                        $service = DB::connection('mongodb2')->table('vendorservices')->where('_id',intval($service_id))->first();
                        if($service && isset($service['category']) && isset($service['category']['primary'])){
                            $sercviecategory_id     =   intval($service['category']['primary']);
                            $workoutSessionPrice    =   ($sercviecategory_id == 65) ? 300 : 500;
                        }
                    }
                    $insertData['price']     =   $workoutSessionPrice;
//                    return $insertData;

                    if($workoutSessionPrice > 0){
                        $ratecart_exists        = new Ratecard($insertData);
                        $ratecart_exists->setConnection('mongodb2');
                        $ratecart_exists->_id   =   $newratecardid;
                        $ratecart_exists->save();

                        $curl   =   curl_init();
                        $url    =   "http://a1.fitternity.com/reverse/migration/ratecard/$newratecardid";
//                        $url    =   "http://apistg.fitn.in/reverse/migration/ratecard/$newratecardid";
//                    $url    =   "http://fitapi.com/reverse/migration/ratecard/$newratecardid";
                        curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url ));
                        $resp = curl_exec($curl);
                        curl_close($curl);
                        echo "<br><br> Ratecard Url -- ".$url;
                    }
                }
            }//Trial Ratecard exists

        }//WorkoutSession Ratecard Not Exists

    }

});





Route::get('updateworkoutsessionprices/{offeset?}/', function($offset = ""){

    ini_set('memory_limit', '500M');
    set_time_limit(3000);

    if($offset == ""){
        $service_ids = Service::active()->lists('_id');
    }else{
        $service_ids = Service::active()->take(5000)->skip(intval($offset))->lists('_id');
    }

//    $service_ids = DB::connection('mongodb2')->table('vendorservices')->whereIn('vendor_id',[1671,1645,1429,7146,1664,1020,1518])->lists('_id');

//    $service_ids = [830];
    foreach ($service_ids as $service_id){

        $curl   =   curl_init();
        $id     =   trim($service_id);
//        $url    =   "http://apistg.fitn.in/reverse/migration/updateschedulebyserviceid/$id";

//        $url    =   "http://apistg.fitn.in/reverse/migration/vendorservice/$id";
//        $url    =   "http://fitapi.com/reverse/migration/vendorservice/$id";
        $url    =   "http://a1.fitternity.com/reverse/migration/updateschedulebyserviceidv1/$id";
        curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url));
        $resp = curl_exec($curl);
        curl_close($curl);
        var_dump($resp);


    }


});



Route::get('showrcb', function(){


	return Requestcallbackremindercall::orderBy('_id','desc')->get();
	echo date( "l\, jS F\'y h:i A", strtotime("2016-07-21 18:30:00"));

});


Route::get('typecastcode', function() {

    $trials = Booktrial::where('code','exists',true)->where('code','!=','')->where('code', 'type', 18)->get(['_id','code']);

//    $trials = Booktrial::where('code','exists',true)->where('code','!=','')->where('code', 'type', 18)->where('_id', 340)->get(['_id','code']);

    foreach($trials as $trial){

        $existtrial        =   Booktrial::find(intval($trial['_id']));
        if($existtrial && isset($existtrial->code)){
            $insertData       =   ['code' => (string) $existtrial->code ];
//            var_dump($trial['_id']); var_dump($insertData);exit;
            DB::table('booktrials')->where('_id', intval($trial['_id']))->update($insertData);
        }

    }

});


Route::get('addremindercallmessage', function() {

	$customer_name                      =   "Sanjay";
	$customer_phone                     =   "9773348762";
	$schedule_date                      =   Carbon::today()->toDateTimeString();
	$preferred_time                     =   "2 PM - 6 PM";


	if($preferred_time == "Before 10 AM"){
		$schedule_slot  = "09:00 AM-10:00 PM";
	}elseif($preferred_time == "10 AM - 2 PM"){
		$schedule_slot  = "10:00 AM-02:00 PM";
	}elseif($preferred_time == "2 PM - 6 PM"){
		$schedule_slot  = "02:00 PM-06:00 PM";
	}elseif($preferred_time == "6 PM - 10 PM"){
		$schedule_slot  = "06:00 PM-09:00 PM";
	}


	$slot_times 						=	explode('-', $schedule_slot);
	$schedule_slot_start_time 			=	$slot_times[0];
	$schedule_slot_end_time 			=	$slot_times[1];
	$schedule_slot 						=	$schedule_slot_start_time.'-'.$schedule_slot_end_time;

	$schedule_date_starttime 			=	strtoupper(date('d-m-Y', strtotime($schedule_date)) ." ".$schedule_slot_start_time);
	$schedule_date_time		            =	Carbon::createFromFormat('d-m-Y g:i A', $schedule_date_starttime)->toDateTimeString();



    $data = [
		'customer_name' => trim($customer_name),
		'customer_phone' => trim($customer_phone),
		'schedule_date' => $schedule_date,
		'schedule_date_time' => $schedule_date_time,
		'schedule_slot' => trim($schedule_slot),
		'call_status' => 'no'
	];

//	return $data;

//    return $data;

    $insertedid = Requestcallbackremindercall::max('_id') + 1;
    $obj       =   new Requestcallbackremindercall($data);
    $obj->_id  =   $insertedid;
    $obj->save();


    return Requestcallbackremindercall::get();
});


Route::get('bringbacklandline', function(){

	$Finders = Vendor::on('mongodb2')->where('contact.phone.landline',"!=",[])->get(['contact','name']);
	$phone = [];
	foreach($Finders as $key => $Finder){
		$mobilePhoneStr = "";
		if(isset($Finder->contact['phone']['mobile']) && count($Finder->contact['phone']['mobile']) > 0){
			$mobilePhoneStr = implode(",", $Finder->contact['phone']['mobile']);
			$mobilePhoneStr .= ",";
		}

		if(isset($Finder->contact['phone']['landline']) && count($Finder->contact['phone']['landline']) > 0){
			$mobilePhoneStr .= implode(",", $Finder->contact['phone']['landline']);
		}
		$oldfinder = Finder::find($Finder->_id,['contact']);
		$contact = $oldfinder->contact;
		$contact['phone'] = $mobilePhoneStr;
		$oldfinder->update(['contact'=>$contact]);
		array_push($phone,$Finder->name);
	}
return $phone;


});


Route::get('noidacity', function() {

		$city_id				=		9;
        $locationids        	=    	Location::whereIn('cities',[$city_id])->lists('_id');        
        $locationtagsids        =    	Locationtag::whereIn('cities',[$city_id])->lists('_id');
   		$city          			=    	City::where('_id',$city_id)->push('locations', $locationids, true);
   		$city          			=    	City::where('_id',$city_id)->push('locationtags', $locationtagsids, true);




//    $data               =    array( 'country_id' => 1, "lat" => "28.535517", "lon" => "28.535517", "name" => "noida", "name" => "noida", "status"=> "1");
//    $existcity          =    City::where('slug','noida')->first();
//    if(!$existcity){
//        $insertedid = City::max('_id') + 1;
//        $city       =   new City($data);
//        $city->_id  =   $insertedid;
//        $city->save();
//        $city_id = $insertedid;
//
//        $city       = City::findOrFail(intval($insertedid));
//        $city->update($data);
//
//        $city_id = $city->_id;
//    }else{
//        $existcity->update($citydata);
//        $city_id = $existcity->_id;
//    }






    echo "done";
	exit;
    


});

Route::get('import/defination', function(){

	$definationArr = [

	'12' => [
	"Zumba is an aerobic fitness form featuring movements inspired by various styles of Latin American dance. Zumba dance classes typically feature a cardio interval training with combination of fast and slow rhythms that tone as well as sculpt the body while adding to weight loss. Zumba fitness is ideal for those looking who are looking for maximizing calorie burn, build agility fat burning and body toning.",
	"Zumba fitness is a form of aerobic dance workout that takes it inspiration for movement and music from Latin American dances. Zumba dance workout comprises of fast moving interval workouts and cardio that help in weight loss, build agility body conditioning and calorie burnout",
	"Zumba fitness combines high energy cardio and motivating music with unique moves and combinations. The routines set by a Zumba instructor feature aerobic interval training, cardio along with resistance training with fast and slow rhythms that tone and sculpt the body, aid in weight loss, build agility and have long term health and wellbeing.",
	"Zumba fitness is a mix of Latin American dance moves to high energy beats. A typical Zumba dance class has elements of fitness interval training, cardio, resistance training and body sculpting movements that help in weight loss, build agility, fat burning along with high levels of cardio.",
	"Zumba combines high energy and motivating music with unique moves and combinations, which allow the Zumba participants to dance away their worries. The routines feature aerobic/fitness interval training with a combination of fast and slow rhythms that tone and sculpt the body, and have long term health and wellbeing benefits. Zumba utilizes the principles of fitness interval training and resistance training to maximize caloric output, fat burning and total body toning. It is a mixture of body sculpting movements, exercises for weight loss with easy to follow dance steps."
	],
	'5'	=> [
	"Gymnasium, commonly known as gyms, is an ideal place for workout when you want to achieve balance between strength training, resistance training and cardio workouts. The personal fitness trainers at gym centres help achieve the right training program for fitness goals through physical exercises and activities performed using gym equipments. It helps in body building, weight loss and body toning.",
	"Gym is the short word for gymnasium. It is a place where gym trainers create personalized fitness plans that include physical exercise that balances strength training, resistance training and cardio workouts with gym equipments to help in body building, weight loss and body toning.",
	"A gym is a place of workout characterized by equipments for strength training, body building, resistance training and cardio. Floor gym trainers or personal fitness trainers assist you in creating a fitness plan basis your fitness goal. This may or may not include diet plans. This is ideal workout for those looking to build muscles, weight loss and body toning",
	"A gym is a equipment centric workout place that emphasises on a balance between strength training, resistance training and cardio workouts. It helps in creating a fitness regime with the help of gym trainers or personal fitness trainers that is basis your capacity and fitness goals. Ideal place for those who are looking for body building, weight loss and body toning."
	],
	'6' 	=> [
	"Yoga is an ancient Indian form of physical and mental wellbeing, tying together various yoga asanas or poses and postures with techniques of meditation and Pranayama to create a balance in the body. There are different types of yoga classes, basis the type like Astanga Yoga, Bikram Yoga and Iyengar Yoga, to name a few. It helps in improving stability, strengthening the core and aiding in flexibility.",
	"An ancient Indian form of mental and physical wellbeing, Yoga is characterized by various poses or asanas and postures while incorporating techniques of meditation and Pranayama. There are different forms taught by a yoga teacher at the yoga classes like Astanga yoga, Iyengar Yoga, Bikram yoga, Prenatal yoga, to name a few. Yoga benefits include improvement in flexibility, stability and strengthening the core.",
	"Yoga is a form of mental and physical wellbeing that has its roots in ancient India. A yoga class typically comprises of yoga asanas or postures, Meditation and pranayama. It helps in improving flexibility, stability and strengthening the core. The different types of yoga are Astanga yoga, Iyengar yoga, Bikram Yoga, Prenatal yoga and more.",
	"Yoga has its origins in ancient India and is a form of fitness that helps in improving the mental and physical wellbeing. Popularly, a yoga class will either be Astanga yoga, Iyengar yoga, Bikram yoga or Prenatal yoga and has elements like yoga asanas, pranayama and meditation. Yoga benefits include improvement of stability and flexibility as well as strengthening of core.",
	"Yoga is an ancient Indian form of physical and mental wellbeing, tying together various poses and postures with techniques of meditation and breathing to create a balance in the body. Traditionally there are different types of yoga: Anusura Yoga, Ashtanga Yoga, Hatha Yoga, Iyengar Yoga, and Vinyasa Yoga. Several new versions have been promoted as yoga for weight loss and toning up like Power Yoga and Bikram Yoga. Each of the traditional variations of yoga has its own health benefits, all the while focusing on building your core, aiding weight loss, and connecting the mind with the body."
	],
	'35' =>[
	"Cross functional training workout train your muscles to work together and prepares them for daily tasks by simulating common movements. Functional training exercises work on your speed, power, strength, muscular endurance and aerobic fitness. Functional trainers aim at improving your flexibility, core strengthening and building stamina. Benefits of functional training are building body shape, increased muscular balance, joint stability and coordination.",
	"Cross functional training aims at muscle training by simulating common movements that prepares you for daily tasks. Functional workouts primarily include training for speed, power, muscular endurance, strength and aerobic fitness with an improve flexibility, core strengthening and building stamina. Ideal for those looking to build body shape, increase muscular balance, joint stability and coordination",
	"Cross function training helps in simulating common movements performed in daily tasks to enhance muscle training. A typical functional workout includes exercises that improve your flexibility, strengthen your core and build stamina. Functional trainers create workout routines that focus on speed, power, strength, muscular endurance and aerobic fitness. Some of the benefits of functional training include increase in muscular balance, building body shape, joint stability and coordination",
	"Cross functional training workouts help train muscles through simulated common movements performed in daily tasks. Functional workouts are routines comprising of speed, power, strength muscle endurance and aerobic fitness training that are done within the supervision of a functional trainer, with an aim to improve flexibility, strengthen your core and build stamina. The benefits of functional training are increase in muscular balance, building body shape, joint stability and core strengthening.",
	"A functional fitness workout trains your muscles to work together and prepares them for daily tasks by simulating common movements you might do at home, at work or in sports in a way that builds core strength and muscle flexibility. The benefits of functional training include: Increase and improvement in speed, power, agility, strength, muscular endurance, aerobic fitness, and flexibility Good for promoting good posture Help with injury prevention and rehabilitation Achieves a metabolic training effect helps with building body shape Increased muscular balance, joint stability and coordination"
	],
	'7' => [
	"Dance is an art of moving rhythmically to music, typically following a set sequence of steps. One can learn different types of dance ranging from hip hop, salsa, ballet, ballroom, bollywood, contemporary and more. Dance workouts are generally cardio in nature and help improve stamina, burn calories and weight loss.",
	"Dance fitness is cardio workout that involves moving rhythmically to music by following a set sequence of steps. The different types of dance classes are hip hop, salsa, ballet, ballroom, bollywood and contemporary, to name a few. The benefits of dance are improvement of stamina, calorie burn and weight loss.",
	"Dance is a form of cardio workout that is characterized by rhythmic movement by following a set sequence of steps. One can learn different types of dance like hip hop, salsa, ballet, ballroom, bollywood and contemporary, to name a few. Dance is ideal for those looking to improve their stamina, burn calories and weight loss.",
	"Dance involves following a set sequence of steps leading to rhythmic movement. It is a form of cardio fitness. Dance fitness can be of different types like hip hop, salsa, ballet, ballroom, bollywood and contemporary, among few. The benefits of it are improved stamina, higher calorie burn and weight loss."
	],		
	'43' => [
	"Fitness studios are a workout place where multiple types of fitness activities are conducted under one roof. There are different fitness trainers training for workouts that involve Dance fitness, Functional training, Yoga classes, Zumba dance workout, Pilates workout and MMA workout. It primarily focuses on cardio workouts. Benefits those, who prefer variety and want to achieve fitness goals, like weight loss, calorie burn, endurance building and resistance training.",
	"Fitness studios helps provide variety in fitness activities without the inconvenience of travelling. With a primary focus on cardio and resistance training, these places offer Dance fitness, Functional training, Yoga classes, Zumba dance workout, Pilates workout and MMA workouts. The benefits of it are weight loss, high calorie burn and endurance building.",
	"Fitness studios are a place where under one roof multiple fitness options are provided by different fitness trainers, these options include Dance fitness, Functional training, Yoga classes, Zumba dance workout, Pilates workout and MMA workout. The primary focus is cardio and resistance training with benefits like weight loss, cardio burnout and endurance building.",
	"Fitness studios are a place of workout where multiple cardio and resistance training oriented fitness options like Dance Fitness, Functional training, Yoga classes, Zumba dance workout, Pilates workout and MMA workout, are conducted under one roof. Besides the variety, the benefits of it are weight loss, cardio burnout and endurance building."
	],
	'32' => [
	"CrossFit is a high intensity interval training workout form that involves functional movements reflecting the best aspects of explosive plyometrics, strength training, speed training, Olympic and power-style weight lifting, kettle bells, body weight exercises, gymnastics and endurance training. A typical Crossfit workout routine targets all the major components of physical fitness with benefits like cardio-respiratory fitness, stamina, muscular strength, endurance, flexibility, power, speed, agility, balance, coordination and accuracy.",
	"CrossFit workout is a high intensity interval training workout involving functional movements. Crossfit training includes plyometrics, strength training, speed training, Olympic and power- style weight lifting, kettle bell workout, body weight exercises, gymnastics and endurance training. Targeting all major components of physical fitness, crossfit exercises help in cardio-respiratory fitness, stamina, muscular strength, endurance, flexibility, power, speed, agility, balance coordination and accuracy.",
	"CrossFit workout involves functional movements with high intensity interval training. Crossfit workout program includes plyometrics, strength training, speed training, Olympic and power-style weight lifting, Kettle bell workouts, body weight exercises, gymnastic and endurance training. The benefit of crossfit exercises is cardio-respiratory fitness, stamina, muscular strength, endurance, flexibility, power, speed, agility, balance, coordination and accuracy leading to physical fitness",
	"CrossFit combines strength training, explosive plyometrics, speed training, Olympic- and power-style weight lifting, kettle bells, body weight exercises, gymnastics, and endurance exercises â€“ basically the ultimate whole body workout.By doing this, CrossFit targets what it calls the major components of physical fitness: cardiorespiratory fitness, stamina, muscular strength and endurance, flexibility, power, speed, agility, balance, coordination, and accuracy.",
	"CrossFit workout routine is typically a high intensity interval training that involves functional movements. Crossfit exercises include plyometrics, strength training, speed training, Olympic and power-style weight lifting, Kettle bell workouts, body weight exercises, gymnastic and endurance training that help in cardio-respiratory fitness, stamina, muscular strength, endurance, flexibility, power, speed, agility, balance, coordination and accuracy leading to physical fitness"
	],
	'11' => [
	"Pilates workout is a form of mind-body exercise using a floor mat or a variety of pilates equipments. The two types of pilates are floor pilates and reformer pilates. Pilates exercises primarily focus on good posture and easy, graceful movements. Benefits of Pilates include improved flexibility, weight loss, agility and economy of motion along with core strengthening.",
	"Pilates workout is a form of mind-body exercise and is basically of two types – floor pilates that uses a floor mat or reformer pilates that uses a variety of pilates equipments. Good posture and easy, graceful movements is the primary focus of pilates exercises and is good for those who want weight loss, flexibility, core strength, agility and economy of motion.",
	"Pilates focuses on mind-body exercise with workouts that either done on the floor with a floor mat or using a variety of equipments known as reformer. It focuses on easy, graceful movements along with good posture. The benefits of pilates are weight loss, flexibility, core strength, agility and economy of motion.",
	"Pilates is a mind-body exercise that is of two types- Floor pilates with mat or Reformer pilates using a variety of equipments. Pilates workout includes easy, graceful movements along with good posture. The benefits of pilates exercises are weight loss, flexibility, core strength, agility and economy of motion.",
	"Pilates is an innovative and safe system of mind-body exercise using a floor mat or a variety of equipment. It evolved from the principles of Joseph Pilates and can dramatically transform the way your body looks, feels and performs. It teaches body awareness, good posture and easy, graceful movement. Pilates weight loss exercises also improves flexibility, agility and economy of motion."
	],
	'8' => [
	"Martial arts refers to all of the various systems of training for combat that have been arranged or systematized. Generally, these different systems or styles are all designed for self defence, building and maintaining muscle as well as core conditioning. There are various types are Kickboxing, Krav Maga, Muay Thai, Kung Fu and MMA workout.",
	"Martial arts is a form of training for combat that have been arranged or systemized. There are various systems or types of martial arts like Kickboxing, Krav Maga, Muay Thai, Kung Fu and MMA workout. An ideal workout for those who are looking for self defence, building and maintaining muscle as well as core conditioning.",
	"Martial Arts is an arranged or systemized system of combat training that is of various types. Some of these styles of Martial Arts are Kickboxing, Krav Maga, Muay Thai, Kung Fu and MMA workout. The benefits of martial arts are self defence, building and maintaining muscle as well as core conditioning.",
	"Martial Arts is system of training for combat that is created in a very arranged or systemized manner. There are various divisions of Martial Arts, these include Kickboxing, Krav Maga, Muay Thai, Kung Fu and MMA workout. The benefits of Martial arts are self defence, building and maintaining muscle as well as core conditioning."
	],
	'14' => [
	"Spinning or Indoor Cycling is a form of cardio and aerobic workout set to music led by a certified instructor. Spinning classes generally follow the principles of interval training and last for a duration of 40 to 60 minutes. Ideal for those who want a motivating workout that they can control at their pace. It’s a low impact workout and can be done by people with joint problems.",
	"Spinning or Indoor Cycling is a cardio or aerobic workout led by a certified trainer on a particular set of music, characterized by interval training, lasting for a duration of 40 to 60 minutes. Spinning classes are beneficial for those with joint problems or seeking a motivating low impact workout with an ability to control the pace.",
	"Spinning or Indoor Cycling is an interval training cardio or aerobic workout led by a certified trainer on set music. A typical spinning class lasts for 40 to 60 minutes. Being a low impact workout, it is ideal for those with joint problems or seek a motivating workout with an ability to control the pace.",
	"Spinning or Indoor Cycling is a low impact interval training cardio or aerobic workout that is conducted by a certified trainer on a set of music that typically lasts for 40 to 60 minutes. Spinning classes are ideal for those with joint problems or seeking a motivating low impact workout with an ability to control the pace."
	],
	'42' => [
	"Healthy tiffins are monthly food subscriptions that deliver low calorie meals at your office or home. These healthy dabbas are generally calorie counted, home cooked or specifically designed for a particular diet or medical condition. Ideal for those who are looking for a healthier alternative to the outside food or are in need of particular diets.",
	"Healthy tiffins are food subscription service prescribed for a fixed period of time, delivered at your doorstep. These healthy dabbas are either home cooked, calorie counted or specifically designed for a dietary requirements. Benefits those individuals looking for an alternate healthy meal option to outside food or are in a need of particular diets.",
	"Healthy tiffin meals are food subscription service provided at your given address for a fixed period of time. These healthy meals are either home cooked, calorie counted or specifically designed for a dietary requirements and primarily benefit those who want to cater to their dietary requirements with ease or find an alternative to outside eating.",
	"Healthy tiffins are meal plans delivered by the provider at the particular address given by you for a subscribed period of time. These are mostly calorie counted, home cooked meals or dabbas designed for a specific dietary requirement. Ideal for those looking for a healthier alternative to the outside food or are in need of particular diets."
	],
	'41' => [
	"Personal trainers are certified fitness or gym trainers that customize the workout routine as per your needs and requirements. These fitness programs are generally conducted at home, studio or gym on one-on-one basis. These workouts can be functional training, yoga, pilates, martial arts or gym training. Perfect for those who prefer to workout at their time and space and have fitness goals like weight loss, endurance building, core strengthening and body building.",
	"Personal trainers are certified fitness trainers who help you achieve your fitness goals with personal attention at your home, a studio or a gym. The fitness programs generally include functional training, yoga, pilates, martial arts or gym training. Ideal for those looking for weight loss, endurance building, core strengthening and body building.",
	"Personal trainers provide personalized fitness solution as per your requirements at your home, gym or studio. These fitness trainers can either be specializing in functional training, yoga, pilates, martial arts or gym training. The benefits of personal training is weight loss, endurance building, core strengthening and body building.",
	"Personal trainers or fitness trainers are professionals who provide one on one fitness solution at your home or studio, to help you achieve your goals, ranging from functional training, yoga, pilates, martial arts and gym training. The fitness programs are designed as per your capacity and needs. The benefits include weight loss, endurance building, core strengthening and body building."
	],
	'36' => [
	"Marathon training aims at preparing your body for long-distance running that includes a wide range of full body workouts like functional training, yoga and running along with proper diet and nutrition plan. The two major types of marathon are half marathon, 21kms and full marathon, 42kms. The benefits of this are weight loss, improvement in energy and stamina build up.",
	"Marathon training is an extensive form of workout that includes functional training, running and yoga along with diet and nutrition plan, that aims at preparing your body for long distance running. The most common distances are 21 kms, called half marathon or 42 kms, known as full marathon. Ideal for those who are looking at weight loss, improving energy and building stamina.",
	"Marathon training includes functional training, running, yoga, diet and nutrition plan. It aims at training you for long distance running that is typically 21kms (half marathon) or 42 kms (full marathon). The benefits of this are weight loss, improvement in energy and stamina build up.",
	"Marathon training is a form of running training that prepares your body with workouts like functional training, yoga and running along with diet and nutrition plans for long distance running. There are two common types of Marathon 21kms – half marathon or 42kms – full marathon. . Ideal for those who are looking at weight loss, improving energy and building stamina.",
	"The marathon is a long-distance running event that takes place allover the world with an official distance of 42.195 kilometres (26 miles and 385 yards) ran as a road race. Running a marathon is one of the most challenging and rewarding events that any of us will experience. The marathon distance is exquisitely set to take us beyond our comfort zone, into a realm in which we confront the limitations of our bodies and our minds.There are plenty of reasons for training for and running a marathon. The training will help you to lose weight and increase your fitness. Running will bring you more self-confidence and energy. Achieving such a demanding goal will earn you self-respect, and the esteem of others around you."
	],
	'45' =>[
	"Healthy snacks and beverages are the alternate to mid meal munching and calorie consumption. These range from dips, desserts, beverages and more. These are low on carbohydrates, fats and calories and thus help you stick to your diet.",
	"Healthy snacks and beverages are mid-meal munching and snacks items that help in reducing the calorie consumption as well as match the dietary requirements. The different types of healthy snacks and beverages are dips, desserts, beverages, bars and more. Ideal for those looking on diets that are low on carbohydrates, fats and calories.",
	"Healthy snacks and beverages are the calorie counted alternative to snacks and mid-meal munchies like dips, desserts, bars, beverages and more. They are low on carbohydrate, fats and calories as well as may or may not be high on protein.",
	"Healthy snacks and beverages are mid meal munchies and snack items that are made keeping in mind health and different dietary requirements. These range from bars, desserts, beverages, dips, to name a few. They are low on carbohydrate, fats and calories as well as may or may not be high on protein."
	],
	'10' => [
	"Swimming is a sport or activity that requires you to propel yourself through water primarily using your hands and legs. It is a full body workout that uses water resistance. It helps improve flexibility, balance, coordination, endurance, muscle strength, weight loss and cardio vascular fitness.",
	"Swimming is a form of full body sports fitness that requires you to propel yourself in water against the resistance using the different muscles, hands and legs. The benefits of this are improved flexibility, balance, coordination, endurance, muscle strength, weight loss and cardio vascular fitness.",
	"Swimming is a water based sport fitness that works on your entire body. A swimming workout requires you to propel yourself in water against the resistance using your muscles, hands and legs. Ideal for those who want improved flexibility, balance, coordination, endurance, muscle strength, weight loss and cardio vascular fitness.",
	"Swimming is a water based sport or activity that requires you to propel yourself through water primarily using your muscles, hands and legs, against the resistance caused. The benefits of this are improved flexibility, balance, coordination, endurance, muscle strength, weight loss and cardio vascular fitness.",
	"Swimming is a great fitness workout because you need to move your whole body against the resistance of the water. However, it is an often underrated form of exercise, but the health benefits of swimming are too good to be ignored: It keeps your heart rate up but takes some of the impact stress off your body. It builds endurance, muscle strength and cardiovascular fitness. It helps maintain a healthy weight, healthy heart and lungs. Provides an all-over body workout, as nearly all of your muscles are used during swimming. Improves flexibility, balance, coordination, and posture. It provides good low-impact therapy for some injuries and conditions"	
	],

	'25' => [
	"Dietitian and nutritionist are experts on dietary requirements and regulate it. They alter the nutrition plan basis the fitness and medical requirement of the client. They provide diet plan that can be rich on particular nutrients. Benefits those who are looking for weight loss, sports nutrition, diet maintenance and particular medical requirements.",
	"Dietitian and nutritionist provide advice and regulate the diets of their clients. These diets are rich in particular nutrients and are created basis the fitness and medical requirements. Ideal for those who are looking for weight loss, sports nutrition, diet maintenance and particular medical requirements.",
	"Dietitians and nutritionist and nutrition consultants on the creation, regulation and maintenance of dietary requirements. They create diet plans basis the fitness and medical requirements of the clients and then balance the nutrient content in it. It helps in weight loss, sports nutrition, diet maintenance and particular medical requirements.",
	"Dietitians and nutritionists help in regulation and maintenance of dietary requirements. These nutrition consultants help in creating diet plans with a balance in nutrition basis the fitness and medical requirements. Ideal for those who are looking for weight loss, sports nutrition, diet maintenance and particular medical requirements."
	],
	'46' => [
	"Sports and nutrition store are shops where merchandize required for sports and fitness activities along with vitamins and supplements are kept on sale. You can find yoga mats, sports equipments, attires and accessories, protein shake, whey and other types of protein as well as other food supplements of different brands under one roof. These are required by people looking for weight loss supplements, body building supplements as well as sports and fitness accessories.",
	"Sports and nutrition stores are physical shops that help customers buy fitness merchandize as well as vitamins and supplements like yoga mats, sports equipments, attires, accessories, protein shake, whey and other types of protein as well as other food supplements of different brands. Ideal place for those looking for weight loss supplements, body building supplements as well as sports and fitness accessories.",
	"Sports and nutrition stores are a stop shop to buy sports and fitness merchandize along with vitamin and other supplements. These stores generally have yoga mats, sports equipments, attires, accessories, protein shake, whey and other types of protein as well as other food supplements of different brands, that are necessary for weight loss, body building as well as other sport and fitness activities.",
	"Sports and nutrition stores are a destination for consumers to purchase sports and fitness merchandize along with vitamin and other supplements like yoga mats, sports equipments, attires, accessories, protein shake, whey and other types of protein as well as other food supplements of different brands for weight loss, body building as well as other sport and fitness activities."
	]

	];


	foreach ($definationArr as $key => $value) {

		echo "<pre>"; print_r($key); echo "<pre>"; print_r($value);

		$Findercategory 	=	Findercategory::find(intval($key));
		$response 			= 	$Findercategory->update(['defination' => $value]);

		$Findercategorytag 	=	Findercategorytag::where('slug',$Findercategory->slug)->first();
		$response 			= 	$Findercategorytag->update(['defination' => $value]);


	}	

// Findercategory


});



Route::get('checkgeolocation/', function(){

	$limit 		=  25;

	$finders 	= Finder::where('lat', 'exists', true)
	->where('lon', 'exists', true)
	->orderBy('_id')
	->take($limit)
	->get(['lon','lat','_id','title', 'contact.address']);


	$get_latlon_cnt = $no_latlon_cnt = $no_add = $error_add_cnt = 0;

	$error_finder_ids = [];

	foreach ($finders as $key => $finder) {

		$is_error = 0;
		
		if(isset($finder['contact']['address']) && $finder['contact']['address'] != ''){


			$clean_html 	= strip_tags($finder['contact']['address']);
			$address 		= str_replace(" ", "+", $clean_html); // replace all the white space with "+" sign to match with google search pattern
			$json 			= [];	

			try {
				$url 			= "http://maps.google.com/maps/api/geocode/json?sensor=false&address=$address";
				$response 		= file_get_contents($url);
				$json 			= json_decode($response,TRUE); //generate array object from the response from the web

			} catch (\Exception $e){
				$error_add_cnt += 1; 
				$is_error = 1;
        	}



        	if($is_error == 1){
				array_push($error_finder_ids, $finder['_id']);
        	}


			// return ($json['results'][0]['geometry']['location']['lat'].",".$json['results'][0]['geometry']['location']['lng']);

			if(isset($json['results'][0]['geometry']['location']['lat']) && isset($json['results'][0]['geometry']['location']['lng'])){

				$response_lat 	= 	$json['results'][0]['geometry']['location']['lat'];
				$response_log 	=	$json['results'][0]['geometry']['location']['lng'];


				// echo "<br><br> =============================================================================================";
				// echo "<br>".$finder['_id'] . " -- ".  $finder['title'];
				// echo "<br> Db lat : " .$finder['lat']." Db lon : ".$finder['lon'];
				// echo "<br> Response lat : " .$response_lat. " Response lon : " .$response_log;

				$get_latlon_cnt += 1; 

			}else{

				// echo "<br><br> =============================================================================================";
				// echo "<br>".$finder['_id'] . " -- ".  $finder['title']. " -- ".  $address;
				// echo "<br><strong style='color:red'>No Response using address </strong>";

				$no_latlon_cnt += 1; 

			}


		}else{
			// echo "<br><br> =============================================================================================";
			// echo "<br>".$finder['_id'] . " -- ".  $finder['title']. " Address not exist";

			$no_add += 1; 

		}



	}  //foreach                   

	

	echo "<br><br> =============================================================================================";
	echo "<br><br> =============================================================================================";

	echo "<br><br> $get_latlon_cnt  --  $no_latlon_cnt   ---  $no_add  ===  $error_add_cnt";

	echo "<br><br> =============================================================================================";
	echo "<br><br> =============================================================================================";



});



Route::get('/updatemedia/findercoverimage', function() {

//	return $finders 	= Finder::where('coverimage', 'exists', true)->orWhere('coverimage',"!=", "")->count();
//    $finders 	= Finder::where('coverimage', 'exists', true)->orWhere('coverimage',"!=", "")->whereIn('_id',[1,2])->orderBy('_id')->lists('_id');
	$finders 	= Finder::where('coverimage', 'exists', true)->orWhere('coverimage',"!=", "")->orderBy('_id')->lists('_id');

	foreach ($finders as $key => $item) {
		$finder 	=	Finder::find(intval($item));
		if($finder){
			$finderData = [];
            //Cover Image
			$old_coverimage_name 		=	$finder->coverimage;
			$new_coverimage_name 		=	pathinfo($old_coverimage_name, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($old_coverimage_name, PATHINFO_EXTENSION));
			echo $finder->coverimage." - ".$new_coverimage_name."<br>";
			if($new_coverimage_name == "."){
				$finderData['coverimage']  = '';
			}else{
				$finderData['coverimage']  = trim($new_coverimage_name);
			}
			$response = $finder->update($finderData);
		}
	}

	echo 'done';

});


Route::get('/updatemedia/finderlogo', function() {

	$finders 	= Finder::where('logo', 'exists', true)->orWhere('logo',"!=", "")->orderBy('_id')->lists('_id');
	foreach ($finders as $key => $item) {
		$finder 	=	Finder::find(intval($item));
		if($finder){
			$finderData = [];
            //Logo
			$old_logo_name 		=	$finder->logo;
			$new_logo_name 		=	pathinfo($old_logo_name, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($old_logo_name, PATHINFO_EXTENSION));
			echo $finder->logo." - ".$new_logo_name."<br>";
			if($new_logo_name == "."){
				$finderData['logo']  = '';
			}else{
				$finderData['logo']  = trim($new_logo_name);
			}
			$response = $finder->update($finderData);
		}
	}
	echo 'done';
});




Route::get('/updatemedia/finderlogo', function() {

	$finders 	= Finder::where('logo', 'exists', true)->orWhere('logo',"!=", "")->orderBy('_id')->lists('_id');
	foreach ($finders as $key => $item) {
		$finder 	=	Finder::find(intval($item));
		if($finder){
			$finderData = [];
            //Logo
			$old_logo_name 		=	$finder->logo;
			$new_logo_name 		=	pathinfo($old_logo_name, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($old_logo_name, PATHINFO_EXTENSION));
			echo $finder->logo." - ".$new_logo_name."<br>";
			if($new_logo_name == "."){
				$finderData['logo']  = '';
			}else{
				$finderData['logo']  = trim($new_logo_name);
			}
			$response = $finder->update($finderData);
		}
	}
	echo 'done';
});


Route::get('/updatemedia/findergallery', function() {

//    $finders 	= Finder::where('photos', 'exists', true)->whereIn('_id',[1,2])->orderBy('_id')->lists('_id');
//    $finders 	= Finder::where('photos', 'exists', true)->orderBy('_id')->lists('_id');
	$finders 	= Finder::orderBy('_id')->lists('_id');
	foreach ($finders as $key => $item) {
		$finder 	=	Finder::find(intval($item));
		if($finder){
			$finderData = [];
			$photoArr   = [];

			if(isset($finder->photos) && count($finder->photos) > 0){
				foreach ($finder->photos as $k => $photo){
					$old_url_name 		=	 $photo['url'];
					$new_url_name 		=	$finder->_id."/".pathinfo($old_url_name, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($old_url_name, PATHINFO_EXTENSION));
					echo $finder->_id." - ".$new_url_name."<br>";
					if($new_url_name == "."){
						$url  = '';
					}else{
						$url  = trim($new_url_name);
					}
					$order = (isset($photo['order'])) ? $photo['order'] : "";
					$alt = (isset($photo['alt'])) ? $photo['alt'] : "";
					$caption = (isset($photo['caption'])) ? $photo['caption'] : "";

					$finder_gallery     = array('order' => $order, 'alt' => $alt, 'caption' => $caption, 'url' => $url);
					array_push($photoArr, $finder_gallery);
				}
			}

			if(count($photoArr) > 0){
				$finderData['photos']  = $photoArr;
//                print_r($photoArr);exit;
				$response = $finder->update($finderData);
			}

		}
	}
	echo 'done';
});




Route::get('/attachcustomernumber', function() {

	// $customers = Customer::where('contact_no', 'exists', false)->orWhere('contact_no', "")->count();
	// $customers = Customer::where('contact_no', 'exists', false)->orWhere('contact_no', "")->take(10)->skip(0)->orderBy('_id')->lists('_id');
	// $customers = Customer::where('contact_no', 'exists', false)->orWhere('contact_no', "")->take(5000)->skip(0)->orderBy('_id')->lists('_id');
	$customers = Customer::where('contact_no', 'exists', false)->orWhere('contact_no', "")->orderBy('_id')->lists('_id');

	foreach ($customers as $key => $item) {
		
		$Customer 	=	Customer::find(intval($item));

		if($Customer){

			$customer_phone = "";
			$Booktrial 	=	Booktrial::where('customer_email', $Customer['email'])->first();
			if($Booktrial && isset($Booktrial['customer_phone']) && $Booktrial['customer_phone'] != '' ){
				$customer_phone = trim($Booktrial['customer_phone']);
			}
			
			if($customer_phone != ""){
				$Order 	=	Order::where('customer_email', $Customer['email'])->first();
				if($Order && isset($Order['customer_phone']) && $Order['customer_phone'] != '' ){
					$customer_phone = trim($Order['customer_phone']);
				}
			}

			if($customer_phone != ""){
				$Capture 	=	Capture::where('customer_email', $Customer['email'])->first();
				if($Capture && isset($Capture['mobile']) && $Capture['mobile'] != '' ){
					$customer_phone = trim($Capture['mobile']);
				}
			}
			$customer_phone = str_replace("+", "", $customer_phone);
			$response = $Customer->update(['contact_no' => trim($customer_phone) ]);	
		}
	}
	echo 'done';

});



Route::get('/updatebatches', function() { 

	$items = Service::active()->orderBy('_id')->lists('_id');
	// $items = Service::whereIn('_id',[1])->orderBy('_id')->lists('_id');
	foreach ($items as $key => $item) {
		$Service 	=	Service::find(intval($item),['_id','batches']);

		if($Service && count($Service['batches']) > 0 && isset($Service['batches'])){
			// return $Service;
			$Servicedata = array();
			$data 				=	$Service->toArray();
			$service_batches 	= 	[];

			foreach ($Service['batches'] as $key => $batch) {
				$batchdata 	= [];

				foreach ($batch as $key => $trials) {
					$weekdaydata 			= 	[];
					$weekdaydata["weekday"] = 	$trials["weekday"];
					$weekdaydata["slots"] 	= 	[];
					if(count($trials['slots']) > 0 && isset($trials['slots'])){
						foreach ($trials['slots'] as $k => $val) {
							array_push($weekdaydata["slots"], $val);
						}
						array_values($weekdaydata["slots"]);
					}
					array_push($batchdata, $weekdaydata);
					array_values($batchdata);
				}
				array_push($service_batches, $batchdata);	
				array_values($service_batches);

			}
			// return $service_batches;

			array_set($Servicedata, 'batches', $service_batches);
			$response = $Service->update($Servicedata);
			echo "<br>$response";
		}
	}

});



Route::get('updateratecards', function() {  

	$finder_ids		=	Finder::whereIn('commercial_type',[0,2])->lists('_id');
	$ratecard_ids	=	Ratecard::whereIn('finder_id', array_map('intval', $finder_ids) )->lists('_id');

	// return $ratecard_ids;
	foreach ($ratecard_ids as $key => $id) {
		$ratecard 	=	Ratecard::find(intval($id));
		$data 			= 	[ 'direct_payment_enable' => '0' ];
		$success_order 	=	$ratecard->update($data);
	}
	echo "done";
});

Route::get('/importcode', function() {  

	$serviceoffers	 = 	Serviceoffer::whereIn('finder_id', [7154])->get();	

	foreach ($serviceoffers as $key => $offer) {
		$serviceoffer 	=	Serviceoffer::find(intval($offer->_id));
		$data 			= 	[
		'buyable' => 0,
		'active' => 0,
		'left' => 0,
		'sold' => intval($serviceoffer->limit)
		];

		$success_order 	=	$serviceoffer->update($data);
		echo "<pre>";print_r($data)."</pre>";
	}	
	return "done";
	return Service::active()->whereIn('servicecategory_id', $servicecategory_id)->whereIn('location_id', $locationids_array)->lists('_id');




	return date('Y-m-d 00:00:00', strtotime( "01-13-2016" ));

	$filename = public_path()."/code.csv";


	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;

	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 1000, ',')) !== FALSE)
		{
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	// return $data;

	foreach ($data as $key => $value) {
		$code = ['code' => $value['code'], 'status' => 0 ];
		$peppertap = new Peppertap($code);
		$insertcatid = Peppertap::max('_id') + 1;
		$peppertap->_id = $insertcatid;
		$peppertap->save();
	}
	echo "successfully inserted"; exit();

});

Route::get('moveratecard', function() { 
	$items = Service::active()->orderBy('_id')->lists('_id');
	if($items){ DB::table('ratecards')->truncate(); }

	//export
	$headers = [
	'Content-type'        => 'application/csv'
	,   'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
	,   'Content-type'        => 'text/csv'
	,   'Content-Disposition' => 'attachment; filename=export_newratecard.csv'
	,   'Expires'             => '0'
	,   'Pragma'              => 'public'
	];
	$output = "SERVICE ID, SERVICE NAME, FINDER ID, FINDER NAME, commercial TYPE, business TYPE, FINDER TYPE,  TYPE, PRICE, SPECIAL PRICE, DURATION, PREVIOUS DURATION, DURATION TYPE, VALIDITY, VALIDITY TYPE, DIRECT PAYMENT MODE,  ORDER, REMARKS \n";


	foreach ($items as $key => $item) {
		$service_id = intval($item);
		$Service 	=	Service::find($service_id,['ratecards','finder_id','name']);

		if($Service){
			$data 		=	$Service->toArray();
			$finder_id 	=   intval($data['finder_id']);
			if(count($data['service_ratecards']) > 0 && isset($data['service_ratecards'])){

				foreach ($data['service_ratecards'] as $key => $val) {
					$insertedid = Ratecard::max('_id') + 1;
					$days = $sessions = 0;
					$previous_duration = "-";

					if(isset($val['duration']) && $val['duration'] != ''){
						$previous_duration  = $val['duration'];
						$durationObj = Duration::active()->where('slug', url_slug(array($val['duration'])))->first();
						$days 		= (isset($durationObj->days)) ? intval($durationObj->days) : 0;
						$sessions 	= (isset($durationObj->sessions)) ? intval($durationObj->sessions) : 0;
					}

					$duration_type = 'sessions';
					if(isset($val['duration']) && $val['duration'] == '1-meal'){
						$duration_type 	= 	'meal';
						$days 			= 	0;
					}

					$ratecarddata = [
					'service_id'=> $service_id,
					'finder_id'=> intval($finder_id),
					'type'=> (isset($val['type'])) ? $val['type'] : '',
					'price'=> (isset($val['price'])) ? intval($val['price']) : 0,
					'special_price'=> (isset($val['special_price'])) ? intval($val['special_price']) : 0,
					'duration'=> intval($sessions),
					'duration_type'=> $duration_type,
					'validity'=> intval($days),
					'validity_type'=> 'days',
					'direct_payment_enable'=> (isset($val['direct_payment_enable'])) ? $val['direct_payment_enable'] : '0',
					'remarks'=> (isset($val['remarks'])) ? $val['remarks'] : '',
					'order'=> (isset($val['order'])) ? $val['order'] : '0',
					];
				 	// print_pretty($ratecarddata); exit();

					$ratecard 		=	new Ratecard($ratecarddata);
					$ratecard->_id 	=	$insertedid;
					$ratecard->save();

					//export to csv
					// $Finderobj 					=	Finder::find(intval($finder_id));
					// $findername 				=	(isset($Finderobj->slug) && $Finderobj->slug != "") ? $Finderobj->slug : "-";
					// $commercial_type_status 	=	(isset($Finderobj->commercial_type_status) && $Finderobj->commercial_type_status != "") ? $Finderobj->commercial_type_status : "-";
					// $business_type_status 		=	(isset($Finderobj->business_type_status) && $Finderobj->business_type_status != "") ? $Finderobj->business_type_status : "-";
					// $finder_type 				=	(isset($Finderobj->finder_type) && $Finderobj->finder_type != "") ? $Finderobj->finder_type : "-";
					// $rservice_id 			=	(isset($ratecarddata['service_id']) && $ratecarddata['service_id'] != "") ? $ratecarddata['service_id'] : "-";
					// $rfinder_id 			=	(isset($ratecarddata['finder_id']) && $ratecarddata['finder_id'] != "") ? $ratecarddata['finder_id'] : "-";
					// $rtype 					=	(isset($ratecarddata['type']) && $ratecarddata['type'] != "") ? $ratecarddata['type'] : "-";
					// $rprice 				=	(isset($ratecarddata['price']) && $ratecarddata['price'] != "") ? $ratecarddata['price'] : "-";
					// $rspecial_price 		=	(isset($ratecarddata['special_price']) && $ratecarddata['special_price'] != "") ? $ratecarddata['special_price'] : "-";
					// $rduration 				=	(isset($ratecarddata['duration']) && $ratecarddata['duration'] != "") ? $ratecarddata['duration'] : "-";
					// $rduration_type 		=	(isset($ratecarddata['duration_type']) && $ratecarddata['duration_type'] != "") ? $ratecarddata['duration_type'] : "-";
					// $rvalidity 				=	(isset($ratecarddata['validity']) && $ratecarddata['validity'] != "") ? $ratecarddata['validity'] : "-";
					// $rvalidity_type 		=	(isset($ratecarddata['validity_type']) && $ratecarddata['validity_type'] != "") ? $ratecarddata['validity_type'] : "-";
					// $rdirect_payment_enable =	(isset($ratecarddata['direct_payment_enable']) && $ratecarddata['direct_payment_enable'] != "") ? $ratecarddata['direct_payment_enable'] : "-";
					// $rprevious_duration 	=	(isset($previous_duration) && $previous_duration != "") ? str_replace(',', '|', $previous_duration)  : "-";
					// $rremarks 				=	(isset($ratecarddata['remarks']) && $ratecarddata['remarks'] != "") ? str_replace(',', '|', $ratecarddata['remarks'])  : "-";
					// $rorder 				=	(isset($ratecarddata['order']) && $ratecarddata['order'] != "") ? $ratecarddata['order'] : "-";
					// $rservice_name 				=	(isset($ratecarddata['service_name']) && $ratecarddata['service_name'] != "") ? $ratecarddata['service_name']  : "-";

					// $output .= "$rservice_id, $rservice_name, $rfinder_id, $findername, $commercial_type_status, $business_type_status, $finder_type, $rtype, $rprice, $rspecial_price, $rduration, $rprevious_duration, $rduration_type, $rvalidity, $rvalidity_type, $rdirect_payment_enable, $rorder, $rremarks  \n";
					// echo $output; exit();

					
				}//foreach ratecards
			}
		}
	}

	//for new ratecards
	$newratecards = DB::table('ratecards_dec262015')->where('service_name', 'exists', false)->get(); 
	foreach ($newratecards as $key => $value) {
		$insertedid 	= 	Ratecard::max('_id') + 1;
		$ratecard 		=	new Ratecard($value);
		$ratecard->_id 	=	$insertedid;
		$ratecard->save();
	}


	// return Response::make(rtrim($output, "\n"), 200, $headers);
	return "ratecard migraterated successfully ...";
	
});



Route::get('reverse_moveratecard', function() { 
	// $items = Service::active()->orderBy('_id')->where('_id',24)->lists('_id');
	$items = Service::active()->orderBy('_id')->lists('_id');

	foreach ($items as $key => $item) {
		$service_id = intval($item);
		$Serviceobj 	=	Service::find($service_id);

		if($Serviceobj){
			$servicedata  	= 	[];
			$ratecards 		= 	Ratecard::where('service_id', $service_id )->get()->toArray();

			if(count($ratecards) > 0 && isset($ratecards)){
				$serviceratecards = [];
				foreach ($ratecards as $key => $val) {

					$duration_slug 	= 	"trial";

					if($val['duration'] != '' && $val['validity'] != ''){
						$previous_duration  = $val['duration'];
						$durationObj 		= Duration::active()->where('days', intval($val['validity']) )->where('sessions', intval($val['duration']) )->first();
						$duration_slug 		= (isset($durationObj->slug)) ? intval($durationObj->slug) : "";
					}
					
					$ratecard = [
					'order'=> (isset($val['order'])) ? $val['order'] : '0',
					'type'=> (isset($val['type'])) ? $val['type'] : '',
					'price'=> (isset($val['price'])) ? $val['price'] : '',
					'special_price'=> (isset($val['special_price'])) ? $val['special_price'] : '',
					'remarks'=> (isset($val['remarks'])) ? $val['remarks'] : '',
					
					'duration'=> $duration_slug,
					'days'=> intval($val['validity']),
					'sessions'=> intval($val['duration']),
					
					'show_on_fitmania'=> (isset($val['show_on_fitmania'])) ? $val['show_on_fitmania'] : '',
					'direct_payment_enable'=> (isset($val['direct_payment_enable'])) ? $val['direct_payment_enable'] : '0',
					'featured_offer'=> (isset($val['featured_offer'])) ? $val['featured_offer'] : '0'
					];


					if($ratecard['days'] != '' && $ratecard['days'] != 0){

						if(intval($ratecard['days'])%360 == 0){
							$year_val  = intval(intval($ratecard['days'])/360);
							if(intval($year_val) > 1){
								$year_append = "years";
							}else{
								$year_append = "year";
							}
							$ratecard['duration'] = $year_val." ".$year_append;
						}

						if(intval($ratecard['days'])%30 == 0){
							$month_val  = intval(intval($ratecard['days'])/30);
							if(intval($month_val) > 1){
								$month_append = "months";
							}else{
								$month_append = "month";
							}
							$ratecard['duration'] = $month_val." ".$month_append;
						}
					}
					array_push($serviceratecards, $ratecard);
				}//foreach ratecards

				// return $serviceratecards;
				array_set($servicedata, 'ratecards', array_values($serviceratecards));
			}
			array_set($servicedata, 'updated_at', $Serviceobj->updated_at);
			$Serviceobj->update($servicedata);		

		}
	}

	return "ratecard migraterated successfully ...";
});



Route::get('exportcustomer/{start_date?}/{end_date?}', function() { 

	ini_set('memory_limit','2048M');
	ini_set('max_execution_time', 300);

	$start_date = $end_date = "";
	$file_name = "customer_".$start_date."_".$end_date;

	//CUSTOMERS
	$headers = [
	'Content-type'        => 'application/csv'
	,   'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
	,   'Content-type'        => 'text/csv'
	,   'Content-Disposition' => 'attachment; filename='.$file_name.'.csv'
	,   'Expires'             => '0'
	,   'Pragma'              => 'public'
	];

	$output = "ID, CUSTOMER NAME, CUSTOMER EMAIL, CUSTOMER NUMBER, CUSTOMER GENDER, CUSTOMER LOCATION, CUSTOMER CITY  \n";
	// $customers 	= 	Customer::take(1000)->skip(0)->orderBy('_id', 'asc')->get()->toArray();

	if($start_date == "" || $end_date == ""){
		$customers 	= 	Customer::orderBy('_id', 'asc')->get()->toArray();

	}else{
		$customers = Customer::where('created_at', '>=', new DateTime( date("d-m-Y", strtotime( $start_date )) ))->where('created_at', '<=', new DateTime( date("d-m-Y", strtotime( $end_date)) ))->get();
	}

	$customer_city 			=  "";
	foreach ($customers as $key => $value) {
		// var_dump($value;)exit();
		$id 					= 		(isset($value['_id']) && $value['_id'] !="") ? $value['_id'] : "-";
		$customer_name 			= 		(isset($value['name']) && $value['name'] !="") ? str_replace(',', '|', $value['name']) : "-";
		$customer_email 		= 		(isset($value['email']) && $value['email'] !="") ? str_replace(',', '|', $value['email']) : "-";
		$customer_phone 		= 		(isset($value['contact_no']) && $value['contact_no'] !="") ? str_replace(',', '|', $value['contact_no']) : "-";
		$customer_gender 		= 		(isset($value['gender']) && $value['gender'] !="") ? str_replace(',', '|', $value['gender']) : "-";
		$customer_location 		= 		(isset($value['location']) && $value['location'] !="") ? str_replace(',', '|', $value['location'] ): "-";

		if(isset($value['city_id']) && $value['city_id'] != ""){
			$city 					= 		City::find(intval($value['city_id']));
			$customer_city 			= 		(isset($city) && $city->name != "") ? $city->name : "-";
		}

		$output .= "$id, $customer_name, $customer_email, $customer_phone, $customer_gender, $customer_location, $customer_city \n";
	}

	return Response::make(rtrim($output, "\n"), 200, $headers);

});


Route::get('exportdata/{type}/{start_date}/{end_date}', function($type, $start_date, $end_date) { 
	// return $reminderTimeAfter12Min 			=	\Carbon\Carbon::createFromFormat('d-m-Y g:i A', date('d-m-Y g:i A'))->addMinutes(12);
    ini_set('memory_limit', '-1');
    set_time_limit(3000000000);
    ini_set('max_execution_time', 30000);

	$file_name = $type."_".$start_date."_".$end_date;

	$headers = [
	'Content-type'        => 'application/csv'
	,   'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
	,   'Content-type'        => 'text/csv'
	,   'Content-Disposition' => 'attachment; filename='.$file_name.'.csv'
	,   'Expires'             => '0'
	,   'Pragma'              => 'public'
	];

//    $output = "";
	// ORDERS
	if($type == 'order' || $type == 'orders'){
		$output = "ID, CUSTOMER NAME, CUSTOMER EMAIL, CUSTOMER NUMBER, ORDER TYPE, ORDER ACTION, AMOUNT, ORDER DATE, FINDER CITY, FINDER NAME, FINDER LOCATION, FINDER CATEGORY, SERVICE NAME, SERVICE CATEGORY  \n";
		$items = $items = Order::where('created_at', '>=', new DateTime( date("d-m-Y", strtotime( $start_date )) ))->where('created_at', '<=', new DateTime( date("d-m-Y", strtotime( $end_date)) ))->get();

		foreach ($items as $key => $value) {

			$id 					= 	(isset($value['_id']) && $value['_id'] !="") ? $value['_id'] : "-";
			$customer_name 			= 	(isset($value['customer_name']) && $value['customer_name'] !="") ? $value['customer_name'] : "-";
			$customer_email 		= 	(isset($value['customer_email']) && $value['customer_email'] !="") ? $value['customer_email'] : "-";
			$customer_phone 		= 	(isset($value['customer_phone']) && $value['customer_phone'] !="") ? $value['customer_phone'] : "-";
			$type 					= 	(isset($value['type']) && $value['type'] !="") ? $value['type'] : "-";
			$order_action 			= 	(isset($value['order_action']) && $value['order_action'] !="") ? $value['order_action'] : "-";
			$amount 				= 	(isset($value['amount']) && $value['amount'] !="") ? $value['amount'] : "-";
			$created_at 			= 	(isset($value['created_at']) && $value['created_at'] !="") ? $value['created_at'] : "-";
			$finder_name 			= 	(isset($value['finder_name']) && $value['finder_name'] !="") ? str_replace(',', '|', $value['finder_name']) : "-";
			$finder_location 		= 	(isset($value['finder_location']) && $value['finder_location'] !="") ? $value['finder_location'] : "-";

			$finder_category =  $service_name = $service_category = $finder_city = "-";

			if(isset($value['finder_id']) && $value['finder_id'] != '5000'){
				$finder = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
				->find(intval($value['finder_id']));

				if($finder){
					$finder_name = $finder->title;
					$finder_location = $finder->location->name;
					$finder_city = $finder->city->name;
					$finder_category = ($finder->category->name) ? $finder->category->name : "-";
				}
			}else{
				if(isset($value['city_id']) && $value['city_id'] != ''){
					$city = City::find(intval($value['city_id']));
					$finder_city = $city->name;
				}
			}

			if(isset($value['service_id']) && $value['service_id'] != ''){
				$service = Service::where('_id', (int) $value['service_id'] )->with('category')->first();
				if($service){
					$service_name = str_replace(',', '|', $service->name);
					$service_category = ($service->category && $service->category->name) ? $service->category->name : "-";
				}
			}

			// var_dump($output);exit;
			$output .= "$id, $customer_name, $customer_email, $customer_phone, $type, $order_action, $amount, $created_at, $finder_city, $finder_name, $finder_location, $finder_category, $service_name, $service_category \n";
		}	
	}



	// BOOKTRIALS
	if($type == 'booktrial' || $type == 'booktrials'){

		$output = "ID, SOURCE, BOOKTRIAL TYPE,  CUSTOMER NAME, CUSTOMER EMAIL, CUSTOMER NUMBER, CUSTOMER GENDER, FINDER NAME, FINDER LOCATION, FINDER CITY, FINDER CATEGORY, COMMERCIAL TYPE, SERVICE NAME, SERVICE CATEGORY, AMOUNT, POST TRIAL STATUS, SCHEDULE DATE, SCHEDULE SLOT, REQUESTED DATE, TRIAL TYPE  \n";
//		$items = $items = Booktrial::where('created_at', '>=', new DateTime( date("d-m-Y", strtotime( $start_date )) ))->where('created_at', '<=', new DateTime( date("d-m-Y", strtotime( $end_date)) ))->where('city_id', 1)->take(30000)->get();
		// $items = $items = Booktrial::where('created_at', '>=', new DateTime( date("d-m-Y", strtotime( $start_date )) ))->where('created_at', '<=', new DateTime( date("d-m-Y", strtotime( $end_date)) ))->get();
        $items = $items = Booktrial::where('schedule_date', 'exists', true)->where('created_at', '>=', new DateTime( date("d-m-Y", strtotime( $start_date )) ))->where('created_at', '<=', new DateTime( date("d-m-Y", strtotime( $end_date)) ))->where('city_id', 1)->get();

		foreach ($items as $key => $value) {
//			 var_dump($value->toArray());exit();


			$id 					= 	(isset($value['_id']) && $value['_id'] !="") ? $value['_id'] : "-";
			$source 				= 	(isset($value['source']) && $value['source'] !="") ? $value['source'] : "-";
			$booktrial_type 		= 	(isset($value['booktrial_type']) && $value['booktrial_type'] !="") ? $value['booktrial_type'] : "-";
			$customer_name 			= 	(isset($value['customer_name']) && $value['customer_name'] !="") ? $value['customer_name'] : "-";
			$customer_email 		= 	(isset($value['customer_email']) && $value['customer_email'] !="") ? $value['customer_email'] : "-";
			$customer_phone 		= 	(isset($value['customer_phone']) && $value['customer_phone'] !="") ? $value['customer_phone'] : "-";
            $customer_gender 		= 	(isset($value['gender']) && $value['gender'] !="") ? $value['gender'] : "-";
			$amount 				= 	(isset($value['amount']) && $value['amount'] !="") ? $value['amount'] : "-";
			$post_trial_status 		= 	(isset($value['post_trial_status']) && $value['post_trial_status'] !="") ? $value['post_trial_status'] : "-";
			$schedule_date 			= 	(isset($value['schedule_date']) && $value['schedule_date'] !="") ? $value['schedule_date'] : "-";
			$schedule_slot 			= 	(isset($value['schedule_slot']) && $value['schedule_slot'] !="") ? $value['schedule_slot'] : "-";
			$created_at 			= 	(isset($value['created_at']) && $value['created_at'] !="") ? $value['created_at'] : "-";
			$finder_name 			= 	(isset($value['finder_name']) && $value['finder_name'] !="") ? str_replace(',', '|', $value['finder_name']) : "-";
			$finder_location 		= 	(isset($value['finder_location']) && $value['finder_location'] !="") ? $value['finder_location'] : "-";

            $trial_type = "";

            if(isset($value['premium_session']) && $value['premium_session'] == "1"){
                $trial_type = "PAID";
            }

            if(isset($value['premium_session']) && $value['premium_session'] == "0"){
                $trial_type = "FREE";
            }


			$finder_category =  $service_name = $service_category = $finder_city = "-";

			if(isset($value['finder_id']) && $value['finder_id'] != '5000'){
				$finder = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
				->find(intval($value['finder_id']));

				if($finder){
					$finder_name = ($finder->title) ? $finder->title : "-";  
					$finder_location = ($finder->location->name) ? $finder->location->name : "-"; 
					$finder_city = ($finder->city->name) ? $finder->city->name : "-";  
					$finder_category = ($finder->category->name) ? $finder->category->name : "-";

                    $commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'free special', 3 => 'commission on sales');
                    $commercial_type 	= $commercial_type_arr[intval($finder->commercial_type)];
				}
			}else{
				if(isset($value['city_id']) && $value['city_id'] != ''){
					$city = City::find(intval($value['city_id']));
					$finder_city = $city->name;
				}
			}

			if(isset($value['service_id']) && $value['service_id'] != ''){
				$service = Service::where('_id', (int) $value['service_id'] )->with('category')->first();
				if($service){
					$service_name = str_replace(',', '|', $service->name);
					$service_category = ($service->category && $service->category->name) ? $service->category->name : "-";
				}
			}





			$output .= "$id, $source, $booktrial_type, $customer_name, $customer_email, $customer_phone, $customer_gender, $finder_name, $finder_location, $finder_city, $finder_category, $commercial_type, $service_name, $service_category,  $amount, $post_trial_status, $schedule_date, $schedule_slot, $created_at, $trial_type \n";
		}
	}



	// CAPTURES
	if($type == 'capture' || $type == 'captures'){

		$output = "ID, CAPTURE TYPE, CUSTOMER NAME, CUSTOMER EMAIL, CUSTOMER MOBILE, CUSTOMER PHONE, FINDER NAME, FINDER LOCATION, FINDER CITY, FINDER CATEGORY, SERVICE NAME, SERVICE CATEGORY, CAPTURE STATUS, CAPTURE ACTIONS, REQUESTED DATE , REMARKS , MEMBERSHIP \n";
		$items = $items = Capture::where('created_at', '>=', new DateTime( date("d-m-Y", strtotime( $start_date )) ))->where('created_at', '<=', new DateTime( date("d-m-Y", strtotime( $end_date)) ))->get();

		foreach ($items as $key => $value) {
			$id 					= 	(isset($value['_id']) && $value['_id'] !="") ? $value['_id'] : "-";
			$capture_type 			= 	(isset($value['capture_type']) && $value['capture_type'] !="") ? $value['capture_type'] : "-";
			$customer_name 			= 	(isset($value['name']) && $value['name'] !="") ? $value['name'] : "-";
			$customer_email 		= 	(isset($value['email']) && $value['email'] !="") ? $value['email'] : "-";
			$customer_mobile 		= 	(isset($value['mobile']) && $value['mobile'] !="") ? $value['mobile'] : "-";
			$customer_phone 		= 	(isset($value['phone']) && $value['phone'] !="") ? $value['phone'] : "-";


			$capture_status 		= 	(isset($value['capture_status']) && $value['capture_status'] !="") ? $value['capture_status'] : "-";
			$capture_actions 		= 	(isset($value['capture_actions']) && $value['capture_actions'] !="") ? $value['capture_actions'] : "-";
			$created_at 			= 	(isset($value['created_at']) && $value['created_at'] !="") ? $value['created_at'] : "-";
			$remarks 				= 	(isset($value['remarks']) && $value['remarks'] !="") ? str_replace(',', '|', $value['remarks']) : "-";
			$membership 			= 	(isset($value['membership']) && $value['membership'] !="") ? str_replace(',', '|', $value['membership']) : "-";
			$finder_name 			= 	(isset($value['vendor']) && $value['vendor'] !="") ? str_replace(',', '|', $value['vendor'])  : "-";
			$finder_location 		= 	(isset($value['location']) && $value['location'] !="") ? $value['location'] : "-";

			$finder_category =  $service_name = $service_category = $finder_city = "-";

			if(isset($value['finder_id']) && $value['finder_id'] != '5000'){
				$finder = Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
				->with(array('location'=>function($query){$query->select('_id','name','slug');}))
				->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
				->find(intval($value['finder_id']));

				if($finder){
					$finder_name = $finder->title;
					$finder_location = $finder->location->name;
					$finder_city = $finder->city->name;
					$finder_category = ($finder->category->name) ? $finder->category->name : "-";
				}
			}else{
				if(isset($value['city_id']) && $value['city_id'] != ''){
					$city = City::find(intval($value['city_id']));
					$finder_city = $city->name;
				}
			}

			if(isset($value['service_id']) && $value['service_id'] != ''){
				$service = Service::where('_id', (int) $value['service_id'] )->with('category')->first();
				if($service){
					$service_name = str_replace(',', '|', $service->name);
					$service_category = ($service->category && $service->category->name) ? $service->category->name : "-";
				}
			}

			$output .= "$id, $capture_type, $customer_name, $customer_email, $customer_mobile, $customer_phone, $finder_name, $finder_location, $finder_city,  $finder_category, $service_name, $service_category, $capture_status, $capture_actions, $created_at, $remarks , $membership  \n";
		}

	}


	return Response::make(rtrim($output, "\n"), 200, $headers);
	
});



Route::get('/updateservices', function() { 

	$items = Service::orderBy('_id')->lists('_id');
	// return $items;
	// return 	$Service 	=	Service::find(intval(4));
	ini_set('max_execution_time', 30000);
	$Servicedata = array();

	foreach ($items as $key => $item) {
		// $service_trialschedules = $service_workoutsessionschedules = array();
		echo "<br>id - $item";
		$Service 	=	Service::find(intval($item));
		if($Service){
			// return $Service;
			$data 		=	$Service->toArray();

			$service_trialschedules = [];
			if(isset($data['trialschedules']) && count($data['trialschedules']) > 0){
				foreach ($data['trialschedules'] as $key => $trials) {
					$weekwiseslot = [];
					$weekwiseslot['weekday'] 	=	$trials['weekday'];
					$weekwiseslot['slots']		=	[];
					if(isset($trials['slots'])){
						foreach ($trials['slots'] as $k => $val) {
							$newslot = ['start_time' => $val['start_time'], 
							'start_time_24_hour_format' => (string)$val['start_time_24_hour_format'], 
							'end_time' => $val['end_time'], 
							'end_time_24_hour_format' => (string) $val['end_time_24_hour_format'], 
							'slot_time' => $val['slot_time'], 
							'limit' => (intval($val['limit'])) ? intval($val['limit']) : 0,
							'price' => (intval($val['price']) == 100) ? 0 : intval($val['price']) 
							];
							array_push($weekwiseslot['slots'], $newslot);
						}
					}	
					array_push($service_trialschedules, $weekwiseslot);
				}
			}

			$service_workoutsessionschedules = [];
			if(isset($data['workoutsessionschedules']) && count($data['workoutsessionschedules']) > 0){
				foreach ($data['workoutsessionschedules'] as $key => $trials) {
					$weekwiseslot = [];
					$weekwiseslot['weekday'] 	=	$trials['weekday'];
					$weekwiseslot['slots']		=	[];
					if(isset($trials['slots'])){
						foreach ($trials['slots'] as $k => $val) {
							$newslot = ['start_time' => $val['start_time'], 
							'start_time_24_hour_format' => $val['start_time_24_hour_format'], 
							'end_time' => $val['end_time'], 
							'end_time_24_hour_format' => $val['end_time_24_hour_format'], 
							'slot_time' => $val['slot_time'], 
							'limit' => (intval($val['limit'])) ? intval($val['limit']) : 0,
							'price' => (intval($val['price']) == 100) ? 0 : intval($val['price']) 
							];
							array_push($weekwiseslot['slots'], $newslot);
						}
					}
					array_push($service_workoutsessionschedules, $weekwiseslot);
				}
			}
			$service_batches = [];
			if(isset($data['batches'])){
				if(count($data['batches']) > 0 && isset($data['batches'])){
					foreach ($data['batches'] as $key => $batch) {
						$goodbatch = [];
						$eachbatch = [];
						foreach ($batch as $key => $trials) {
							$eachbatch["weekday"] = $trials["weekday"];
							$eachbatch["slots"] = [];
							foreach ($trials['slots'] as $k => $val) {
							// print_r($val);
								array_push($eachbatch["slots"],$val);
							}
							array_push($goodbatch, $eachbatch);
						}
						array_push($service_batches, $goodbatch);	
					}
					// return $service_batches;
				}
			}

			array_set($Servicedata, 'workoutsessionschedules', $service_workoutsessionschedules);
			array_set($Servicedata, 'trialschedules', $service_trialschedules);
			array_set($Servicedata, 'batches', $service_batches);
			$response = $Service->update($Servicedata);
			echo "<br>$response";
			// if($val == 4){ exit(); }
			// exit();
		}
	}

});


Route::get('/jwt/create', function() { 
	$password_claim = array(
		"iat" => Config::get('jwt.web.iat'),
		"exp" => Config::get('jwt.web.exp'),
		"data" => 'data'
		);
	$password_key = Config::get('jwt.web.key');
	$password_alg = Config::get('jwt.web.alg');
	$token = JWT::encode($password_claim,$password_key,$password_alg);
	return $token;
});

Route::group(array('before' => 'jwt'), function() {
	Route::get('/jwt/check', function() { 
		return "security is working";
	});
	
});


Route::get('/hesh', function() { 
	/* Queue:push(function($job) use ($data){ $data['string']; $job->delete();  }); */
	Queue::connection('redis')->push('LogFile', array( 'string' => 'new testpushqueue instantly -- '.time()));
	//Queue::later(Carbon::now()->addMinutes(1),'WriteFile', array( 'string' => 'new testpushqueue delay by 1 min time -- '.time()));
	//Queue::later(Carbon::now()->addMinutes(2),'WriteFile', array( 'string' => 'new testpushqueue delay by 2 min time -- '.time()));
	return "successfully test push queue with dealy job as well....";
});

class LogFile {

	public function fire($job, $data){
		/*$job_id = $job->getJobId(); 
		File::append(app_path().'/queue.txt', $data['string']." ------ $job_id".PHP_EOL); */
		$job->delete();  
	}

}


Route::get('/', function() {  return date('l')." laravel 4.2 goes here...."; });



Route::get('/testfinder', function() { 

	return $items = Finder::where('status', '1')->take(10000)->skip(0)->groupBy('slug')->get(array('slug'));

	$slugArr = [];
	$duplicateSlugArr = [];
	foreach ($items as $item) {  

		Finder::destroy(intval($item->_id));
		// if (!in_array($item->slug, $slugArr)){
		// 	array_push($slugArr, $item->slug);
		// }else{
		// 	array_push($duplicateSlugArr,  $item->slug);
		// }

	}

	return $duplicateSlugArr;

	exit;

	for ($i=0; $i < 7 ; $i++) { 
		$skip = $i * 1000;
		$items = Finder::active()->take(1000)->skip(0)->get(array('slug'));
		foreach ($items as $item) {  
			$data = $item->toArray();
			$fid = $data['_id'];
			$url =  "http://a1.fitternity.com/finderdetail/".$data['slug'];
			// $fid = 579;
			// $url =  "http://a1.fitternity.com/finderdetail/golds-gym-bandra-west";
			$handlerr = curl_init($url);
			curl_setopt($handlerr,  CURLOPT_RETURNTRANSFER, TRUE);
			$resp = curl_exec($handlerr);
			$ht = curl_getinfo($handlerr, CURLINFO_HTTP_CODE);
			if ($ht == '404'){ echo "\n\n isssue in : fid - $fid url -$url";}
		}
		exit;
	}

});

/*Route::get('/testsms', function() { 

	$number = '9773348762';
	$msg 	= 'test msg';
	$sms_url = "http://103.16.101.52:8080/bulksms/bulksms?username=vnt-fitternity&password=vishwas1&type=0&dlr=1&destination=" . urlencode(trim($number)) . "&source=fitter&message=" . urlencode($msg);
	$ci = curl_init();
	curl_setopt($ci, CURLOPT_URL, $sms_url);
	curl_setopt($ci, CURLOPT_HEADER, 0);
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ci);
	curl_close($ci);
	return $response;

});*/


Route::get('export', function() { 

	$headers = [
	'Content-type'        => 'application/csv'
	,   'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
	,   'Content-type'        => 'text/csv'
	,   'Content-Disposition' => 'attachment; filename=export_finder.csv'
	,   'Expires'             => '0'
	,   'Pragma'              => 'public'
	];

	$output = "ID,  NAME, COMMERCIALTYPE Type, EMAIL, No of TICKETS BOOKED, TICKET RATE, ORDER TOTAL\n";
	$items = Finder::where('status', '1')->where('status', '1')->take(10000)->skip(0)->get();

	foreach ($orders as $key => $value) {

		// $output .= "$value[id], $value[first_name] $value[last_name], $value[contact], $value[email], $value[quantity], $value[price], $value[total]\n";
		$output .= "$value[id], $value[first_name] $value[last_name], $value[contact], $value[email], $value[quantity], $value[price], $value[total]\n";
	}

	return Response::make(rtrim($output, "\n"), 200, $headers);
});



Route::get('updateduration', function() { 

	$items = Order::where('order_action', 'bought')->get();
	foreach ($items as $value) {  
		$order 		=	Order::findOrFail(intval($value->_id));
		$response 	= 	$order->update(['status' => '1']);
	}
	exit();

	$orderids = [3811,3813,3815,3816,3819,3821,3830,3836,3837,3839,3841,3847,3848,3860,3861,3866,3867,3868,3870,3871,3874,3886,3891,3903,3906,3908,3911,3919,3923,3928,3932,3940,3941,3945,3948,3952,3964,3965,3967,3968,3972,3974,3980,3983,3991,3995,3997,4004,4006,4008,4013,4015,4024,4028,4042,4055,4067,4069,4073,4077,4081,4082,4083,4084,4106,4107,4108,4112,4173,4181,4194,4202,4233,4283,4301,4590,4705,4710,4757,4802,4844,4862,4868,4871,4872,4873,4878,4884,4896,4913,4924,4937,4981,4987,4991,4992,4997,5003,5014,5017,5019,5022,5024,5035,5044,5053,5058,5102,5109,5112,5113,5172,5188,5196,5281,5283,5288,5289,5290,5292,5293,5295,5296,5297,5298,5299,5300,5302,5303,5306,5308,5309,5310,5314,5315,5328,5330,5335,5337,5340,5343,5344,5345,5346,5347,5352,5354,5355,5359,5362,5363,5364,5365,5370,5372,5373,5374,5375,5377,5379,5381,5382,5383,5390,5392,5393,5394,5396,5397,5400,5401,5403,5410,5412,5419,5420,5421,5429,5434,5436,5437,5439,5440,5445,5450,5454,5458,5459,5460,5461,5463,5464,5465,5468,5469,5470,5471,5472,5473,5476,5486,5487,5488,5491,5496,5499,5500,5501,5502,5503,5504,5506,5512,5513,5516,5517,5522,5532,5559,5569,5570,5571,5572,5574,5578,5581,5582,5585,5587,5588,5589,5590,5591,5592,5594,5597,5598,5608,5609,5610,5611,5612,5613,5615,5616,5617,5619,5620,5622,5623,5625,5627,5628,5630,5632,5633,5634,5644,5645,5646,5647,5648,5652,5653,5655,5659,5660,5661,5669,5670,5675,5678,5679,5681,5682,5683,5685,5687,5688,5689,5693,5695,5696,5697,5700,5702,5703,5708,5710,5713,5718,5721,5724,5727,5728,5736,5740,5741];
	
	$items = Order::whereIn('_id', $orderids)->get();

	$fp = fopen('orderlatest.csv', 'w');
	$header = ["ID", "NAME", "EMAIL", "NUMBER", "TYPE" , "ADDRESS"  ];
	fputcsv($fp, $header);
	
	foreach ($items as $value) {  
		$fields = [$value->_id, $value->customer_name, $value->customer_email, $value->customer_phone,  $value->payment_mode, $value->customer_location];
		fputcsv($fp, $fields);
	}
	fclose($fp);
	return 'done';
	

	$items = Duration::active()->get();
	$fp = fopen('updateduration.csv', 'w');
	$header = ["ID", "NAME", "SLUG", "DAYS", "SESSIONS"  ];
	
	fputcsv($fp, $header);
	
	foreach ($items as $value) {  
		$fields = [$value->_id, $value->name, $value->slug, $value->days, $value->sessions];
		// return $fields;
		fputcsv($fp, $fields);
	}
	fclose($fp);
	return 'done';
	return Response::make(rtrim($output, "\n"), 200, $headers);

	
	// foreach ($items as $value) {  
	// 	$duration 		=	Duration::findOrFail(intval($value->_id));
	// 	$durationData 	=	[];
	// 	$itemArr 		= 	explode('-', $value->slug);

	// 	if(str_contains($value->slug , 'day')){
	// 		$days 					=  head($itemArr);
	// 		$durationData['days'] 	=  intval($days);
	// 	}

	// 	if(str_contains($value->slug , 'week')){
	// 		$days 					=  head($itemArr) * 7;
	// 		$durationData['days'] 	=  intval($days);
	// 	}

	// 	if(str_contains($value->slug , 'month')){
	// 		$days			 		=  head($itemArr) * 30;
	// 		$durationData['days'] 	=  intval($days);
	// 	}

	// 	if(str_contains($value->slug , 'year')){
	// 		$days 					=  head($itemArr) * 30 * 12;
	// 		$durationData['days'] 	=  intval($days);
	// 	}

	// 	if(str_contains($value->slug , 'session')){
	// 		if(count($itemArr) > 3){
	// 			// echo $value->_id;
	// 			$sessions 					=  $itemArr[3];
	// 			$durationData['sessions'] 	=  intval($sessions);
	// 		}
	// 	}

	// 	if($key = array_search('sessions', $itemArr)){
	// 		if($key == 1){
	// 			echo "<br>"; print_r($key);
	// 			$sessions 					=  $itemArr[0];
	// 			$durationData['sessions'] 	=  intval($sessions);
	// 			$durationData['days'] 		=  0;
	// 		}

	// 		if($key == 3){
	// 			echo "<br>"; print_r($key);
	// 			$sessions 					=  $itemArr[2];
	// 			$durationData['sessions'] 	=  intval($sessions);
	// 			// $durationData['days'] 		=  0;
	// 		}

	// 	}

	// 	// $durationData['days'] 	=  0;
	// 	// $durationData['sessions'] 	=  0;

	// 	$response = $duration->update($durationData);
	// }

});


Route::get('capturedata', function() { 


	// $items = Service::active()->where('trialschedules', 'size', 0)->get();
	// $fp = fopen('serviceslive1.csv', 'w');
	// $header = ["ID", "SERVICENAME", "FINDERID", "FINDERNAME", "COMMERCIALTYPE" ];
	// fputcsv($fp, $header);

	// foreach ($items as $value) {  
	// 	$finder = Finder::findOrFail(intval($value->finder_id));

	// 	$commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'free special', 3 => 'commission on sales');
	// 	$commercial_type 	= $commercial_type_arr[intval($finder->commercial_type)];

	// 	$fields = [$value->_id,
	// 	$value->name,
	// 	$value->finder_id,
	// 	$finder->slug,
	// 	$commercial_type
	// 	];
	// 	// return $fields;
	// 	fputcsv($fp, $fields);
	// 	// exit();
	// }

	// fclose($fp);
	// return "done";
	// return Response::make(rtrim($output, "\n"), 200, $headers);

	// $items = Booktrial::take(5)->skip(0)->get();
	// $items = Finder::active()->get();
	// $items = Finder::active()->orderBy('_id')->whereIn('city_id',array(1,2))->get()->count();
	$items = Finder::active()->with('city')->with('location')->with('category')
	->whereIn('category_id',array(41))
	->orderBy('_id')->take(3000)->skip(0)
	->get(array('_id','finder_type','slug','city_id','commercial_type','city','category','category_id','location_id','contact','locationtags'));

	$data = array();

	$fp = fopen('newfinder.csv', 'w');
	$header = ["ID", "SLUG", "CITY", "CATEGORY",  "LOCAITONTAG", "FINDERTYPE", "COMMERCIALTYPE", "Contact-address", "Contact-email", "Contact-phone", "finder_vcc_email", "finder_vcc_mobile"  ];
	fputcsv($fp, $header);

	foreach ($items as $value) {  
		$commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'free special', 3 => 'commission on sales');
		$FINDERTYPE 		= ($value->finder_type == 1) ? 'paid' : 'free';
		$commercial_type 	= $commercial_type_arr[intval($value->commercial_type)];
		$cityname 			= $value->city->name;
		$category 			= $value->category->name;
		$location 			= $value->location->name;
		// $output .= "$value->_id, $value->slug, $cityname, $category, $FINDERTYPE, $commercial_type"."\n";

		$fields = [
		$value->_id,
		$value->slug,
		$cityname,
		$category,
		$location,
		$FINDERTYPE,
		$commercial_type,
		$value->contact['address'],
		$value->contact['email'],
		$value->contact['phone'],
		$value->finder_vcc_email,
		$value->finder_vcc_mobile
		];
		// return $fields;
		fputcsv($fp, $fields);
		// exit();
	}

	fclose($fp);
	
	return "newfinder";
	return Response::make(rtrim($output, "\n"), 200, $headers);

});



Route::get('/updatefinder', function() { 




	// $items = Finder::active()->take(3000)->skip(0)->get();
	$items = Service::active()->take(3000)->skip(0)->get();

	$finderdata = array();
	foreach ($items as $item) {  
		$data 	= $item->toArray();

		// $august_available_dates = $data['august_available_dates'];
		// $august_available_dates_new = [];

		// foreach ($august_available_dates as $day){
		// 	$date = explode('-', $day);
		// 	// return ucfirst( date("l", strtotime("$date[0]-08-2015") )) ;
		// 	array_push($august_available_dates_new, $date[0].'-'.ucfirst( date("l", strtotime("$date[0]-08-2015") )) );

		// }
		// // return $august_available_dates_new;
		// array_set($finderdata, 'august_available_dates', $august_available_dates_new);

		$finder = Service::findOrFail($data['_id']);
		$finderratecards = [];
		foreach ($data['ratecards'] as $key => $value) {
			if((isset($value['price']) && $value['price'] != '0')){
				$ratecard = [
				'order'=> (isset($value['order']) && $value['order'] != '') ? $value['order'] : '0',
				'type'=> (isset($value['type']) && $value['type'] != '') ? $value['type'] : '',
				'duration'=> (isset($value['duration']) && $value['duration'] != '') ? $value['duration'] : '',
				'price'=> (isset($value['price']) && $value['price'] != '') ? $value['price'] : '',
				'special_price'=> (isset($value['special_price']) && $value['special_price'] != '') ? $value['special_price'] : '',
				'remarks'=> (isset($value['remarks']) && $value['remarks'] != '') ? $value['remarks'] : '',
				'show_on_fitmania'=> (isset($value['show_on_fitmania']) && $value['show_on_fitmania'] != '') ? $value['show_on_fitmania'] : 'no',
				'direct_payment_enable'=> (isset($value['direct_payment_enable']) && $value['direct_payment_enable'] != '') ? $value['direct_payment_enable'] : '0'
				];
				array_push($finderratecards, $ratecard);
			}
		}

		array_set($finderdata, 'ratecards', array_values($finderratecards));
		$response = $finder->update($finderdata);

		print_pretty($response);
	}

	
});


Route::get('/testdate', function() { 

	return Carbon::now();
	$isodate = '2015-03-10T13:00:00.000Z';
	$actualdate =  \Carbon\Carbon::now();
	return \Carbon\Carbon::now();
	return Finder::findOrFail(1)->toArray();
	return  date( "Y-m-d H:i:s", strtotime("2015-03-10T13:00:00.000Z"));
	//convert iso date to php datetime
	return "laravel 4.2 goes here ....";

});

Route::get('/testpushnotification', function() { 

	// PushNotification::app('appNameAndroid')
	// 				->to('APA91bG_gkVGxr6atdmGbMGGHWLP82U2o91HjU-UKu27gtEFy1a-9TVXYg7gVr0Q_DLEPEtpE-0z6K5f2nuL9i_SPeRySLy0Typtt7ZjQRi4yHc49R5EQg44gAGuovNpP76UbC8wuIL8VCjgNVXD2UEXmwnVFvQJDw')
	// 				->send('Hello World, i`m a push message');

	$response = PushNotification::app('appNameAndroid')
	->to('APA91bF5pPDQbftrS4SppKxrgZWsBUhHrtCkjdfwZXXrazVD9c-qvGvo8MejFGnZ3iHrhOoKyMQKeX3yHrtY_N4xC0ZHVYfHFmgHdaxw_WWOKP5YTdUdDv0Enr-1CBO2q411M33YKiHYl6PJB5z12W3WNbu2Pphz8A')
	->send('This is a simple message, takes use to homepage',array( 
		'title' => "Fitternity",
		'type' => "generic"
		));	
	return Response::json($response,200);	


});

Route::get('/testtwilio', function() { 

	return Twilio::message('+919773348762', 'Pink Customer Elephants and Happy Rainbows');
});


Route::get('/testemail', function() { 

	if(filter_var(trim('ut.mehrotra@gmail.com'), FILTER_VALIDATE_EMAIL) === false){
		echo 'not vaild';
	}else{
		echo ' vaild';
	}

	exit();
	// return "email send succuess";
	$m1 = Queue::push('WriteClass', array( 'string' => 'new delete function form local -- '.time()),'pullapp');
	$m2 = Queue::later(Carbon::now()->addMinutes(3),'WriteClass', array( 'string' => 'new delete function 3 min time -- '.time()),'pullapp');
	$m3 = Queue::later(Carbon::now()->addMinutes(5),'WriteClass', array( 'string' => 'new delete function 5 min time -- '.time()),'pullapp');
	echo "$m1 -- $m2 -- $m3";
	// 	$url ='https://mq-aws-us-east-1.iron.io/1/projects/549a5af560c8e60009000030/queues/pullapp/messages/'.$m2.'?oauth=tsFrArQmL8VS8Cx-5PDg3gij19Y';
	//    $ch = curl_init();
	//    curl_setopt($ch, CURLOPT_URL,$url);
	//    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	//    $result = curl_exec($ch);
	//    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	//    curl_close($ch);
	$deleteid = Queue::deleteMessage('pullapp',$m2);
});

class WriteClass {

	public function fire($job, $data){

		$job_id = $job->getJobId(); 

		// File::append(app_path().'/queue.txt', $data['string']." ------ $job_id".PHP_EOL); 
		$email_template = 'emails.test';
		$email_template_data = array();
		$email_message_data = array(
			'string' => 'Hello World from array with time -- '.time(),
			'to' => 'sanjay.id7@gmail.com',
			'reciver_name' => 'sanjay sahu',
			'bcc_emailids' => array('sanjay.fitternity@gmail.com'),
			'email_subject' => $data['string'].' -- Testemail with queue using ngrok from local ' .time()
			);

		Mail::send($email_template, $email_template_data, function($message) use ($email_message_data){
			$message->to($email_message_data['to'], $email_message_data['reciver_name'])
			->bcc($email_message_data['bcc_emailids'])
			->subject($email_message_data['email_subject'].' send email from instant -- '.date( "Y-m-d H:i:s", time()));
		});
		$job->delete();  
		return $job_id;	
	}

}


Route::get('/testpushemail', function() { 

	$email_template = 'emails.testemail1';
	$email_template_data = array();
	$email_message_data = array(
		'string' => 'Hello World from array with time -- '.time(),
		'to' => 'sanjay.id7@gmail.com',
		'reciver_name' => 'sanjay sahu',
		'bcc_emailids' => array('chaithanyapadi@fitternity.com'),
		'bcc_emailids' => array(),
		'email_subject' => 'Testemail using loop ' .time()
		);

	// $messageid1 =  Mail::queue($email_template, $email_template_data, function($message) use ($email_message_data){
	// 		$message->to($email_message_data['to'], $email_message_data['reciver_name'])
	// 		->bcc($email_message_data['bcc_emailids'])
	// 		->subject($email_message_data['email_subject'].' from instant -- '.date( "Y-m-d H:i:s", time()));
	// 	});
	// return var_dump($messageid1);

	// echo $deleteid = Queue::deleteReserved('default',$messageid1);

});


Route::get('/testhipchat', function() { 
	HipChat::setRoom('Teamfitternity');
	HipChat::sendMessage('My Message to room Teamfitternity', 'green');
	// HipChat::sendMessage('My Message', 'red', true);
	return "successfully test hipchat ....";
});

Route::get('/testpushqueue', function() { 
	/* Queue:push(function($job) use ($data){ $data['string']; $job->delete();  }); */
	Queue::push('WriteFile', array( 'string' => 'new testpushqueue instantly -- '.time()));
	Queue::later(Carbon::now()->addMinutes(1),'WriteFile', array( 'string' => 'new testpushqueue delay by 1 min time -- '.time()));
	Queue::later(Carbon::now()->addMinutes(2),'WriteFile', array( 'string' => 'new testpushqueue delay by 2 min time -- '.time()));
	return "successfully test push queue with dealy job as well....";
});

class WriteFile {

	public function fire($job, $data){
		$job_id = $job->getJobId(); 
		File::append(app_path().'/queue.txt', $data['string']." ------ $job_id".PHP_EOL); 
		$job->delete();  
	}

}

Route::get('migrateratecards/', array('as' => 'finders.migrateratecards','uses' => 'FindersController@migrateratecards'));

Route::get('updatepopularity/', array('as' => 'finders.updatepopularity','uses' => 'FindersController@updatepopularity'));




Route::get('/trialcsv', function() { 

	$headers = [
	'Content-type'        => 'application/csv',   
	'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',   
	'Content-type'        => 'text/csv',   
	'Content-Disposition' => 'attachment; filename=trialsdiff.csv',   
	'Expires'             => '0',   
	'Pragma'              => 'public'
	];

	$booktrialslotcnt = Booktrial::where('booktrial_type', 'auto')->where('source', 'website')->skip(0)->take(3000)->get();

	// return $booktrialslotcnt;
	// return $finders;sourceja
	$output = "ID, customer name,customer email, Created At, Updated At, Schedule Date, Diff date \n";
	$emails = ['chaithanya.padi@gmail.com','chaithanyapadi@fitternity.com','sanjay.id7@gmail.com','sanjay.fitternity@gmail.com','utkarsh2arsh@gmail.com','ut.mehrotra@gmail.com','neha@fitternity.com','jayamvora@fitternity.com'];
	foreach ($booktrialslotcnt as $key => $value) {
		$dStart = strtotime($value->created_at);
		$dEnd  = strtotime($value->schedule_date);
		$dDiff = $dEnd - $dStart;
		// $dDiff = $dStart->diff($dEnd);
		if(floor($dDiff/(60*60*24)) > 0 && floor($dDiff/(60*60*24)) < 50){
			if(!in_array($value->customer_email, $emails)){
				$output .= "$value->_id,$value->customer_name,$value->customer_email, $value->created_at, $value->updated_at, ".$value->schedule_date.", ".floor($dDiff/(60*60*24))."\n";
			}
		}
	}
	
	return Response::make(rtrim($output, "\n"), 200, $headers);

});

Route::get('/findercsv', function() { 

	$headers = [
	'Content-type'        => 'application/csv',   
	'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',   
	'Content-type'        => 'text/csv',   
	'Content-Disposition' => 'attachment; filename=freefinders.csv',   
	'Expires'             => '0',   
	'Pragma'              => 'public'
	];

	$finders 		= 	Blog::active()->get();

	// return $finders;
	$output = "ID, URL, \n";

	foreach ($finders as $key => $value) {
		$output .= "$value->_id, http://www.fitternity.com/article/$value->slug, "."\n";
	}
	
	return Response::make(rtrim($output, "\n"), 200, $headers);

	$finders 		= 	Finder::active()
						// ->with(array('category'=>function($query){$query->select('_id','name','slug');}))
						// ->with(array('city'=>function($query){$query->select('_id','name','slug');})) 
						// ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
						// ->skip(0)
						// ->take(3000)
	->where('finder_type', 1)
	->get();

	// return $finders;
	$output = "ID, SLUG, CITY, TYPE, EMAIL, TYPE \n";

	foreach ($finders as $key => $value) {
		$type = ($value->finder_type == '0') ? 'Free' : 'Paid';
		$output .= "$value->_id, $value->slug, ".$value->city->name.", ".$type.", ".$value->finder_vcc_email ."\n";
	}

	
	return Response::make(rtrim($output, "\n"), 200, $headers);

});

Route::get('exportorders/', array('as' => 'orders.exportorders','uses' => 'OrderController@exportorders'));

Route::get('/debug/invalidfinderstats',  array('as' => 'debug.invalidfinderstats','uses' => 'DebugController@invalidFinderStats'));
Route::get('/debug/sendbooktrialdaliysummary',  array('as' => 'debug.sendbooktrialdaliysummary','uses' => 'DebugController@sendbooktrialdaliysummary'));
Route::get('/debug/sendbooktrialdaliysummaryv1',  array('as' => 'debug.sendbooktrialdaliysummaryv1','uses' => 'DebugController@sendbooktrialdaliysummaryv1'));
Route::get('/debug/vendorstats',  array('as' => 'debug.vendorstats','uses' => 'DebugController@vendorStats'));
Route::get('/debug/getvendors',  array('as' => 'debug.getvendors','uses' => 'DebugController@getVendors'));
Route::get('/debug/vendorsbymonth',  array('as' => 'debug.vendorsByMonth','uses' => 'DebugController@vendorsByMonth'));
Route::get('/debug/gurgaonmigration',  array('as' => 'debug.gurgaonmigration','uses' => 'DebugController@gurgaonmigration'));
Route::get('/debug/movekickboxing',  array('as' => 'debug.movekickboxing','uses' => 'DebugController@movekickboxing'));
Route::get('/debug/updateorderamount',  array('as' => 'debug.updateOrderamount','uses' => 'DebugController@updateOrderAmount'));
Route::get('/debug/vendorstatsmeta',  array('as' => 'debug.vendorStatsMeta','uses' => 'DebugController@vendorStatsMeta'));
Route::get('/debug/budgetalgofinders',  array('as' => 'debug.BudgetAlgoFinders','uses' => 'DebugController@BudgetAlgoFinders'));




Route::get('/cleandata', function() { 
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 161)
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Altitude Training is 151 as sub
		//Cross Functional Training as root 5
		$servicedata = array();
		array_set($servicedata,'servicecategory_id', 5);
		array_set($servicedata,'servicesubcategory_id', 151);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/cleandata1', function() { 
	//Danzo Fit clean up
	//delete servicecategory 120
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 120)
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();
		array_set($servicedata,'servicecategory_id', 2);
		array_set($servicedata,'servicesubcategory_id', 122);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/cleandata2', function() { 
	//Aerobics in dance
	//delete servicecategory 152
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 152)
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();
		array_set($servicedata,'servicecategory_id', 2);
		array_set($servicedata,'servicesubcategory_id', 85);       
		$resp = $item->update($servicedata);
	}
	
});

Route::get('/cleandata3', function() {
	//Zumba classes, 
	//delete servicesubcategory 141
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 19)
	->where('servicesubcategory_id',141 )
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();        
		array_set($servicedata,'servicesubcategory_id', 20);       
		$resp = $item->update($servicedata);
	}	
});
//dont his this route ,not sure about the categories
Route::get('/cleandata4', function() {
	//kids gym
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 65)
	->where('servicesubcategory_id',66 )
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();        
		array_set($servicedata,'servicesubcategory_id', 67);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/cleandata5', function() { 
	//functional training (64, 75)
	//delete servicesubcategory _id 75
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 5)
	->where('servicesubcategory_id',75 )
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();        
		array_set($servicedata,'servicesubcategory_id', 64);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/cleandata6', function() {
	//matt pilates (89, 99)
	// delete servicesubcategory 89, 99
	$service = Service::with('category')
	->with('subcategory')
	->with('location')
	->with('city')
	->with('finder')
	->where('servicecategory_id', 4)
	->whereIn('servicesubcategory_id',array(89,99) )
	->get();

	$service_list = $service;
	foreach ($service_list as $item) {
		//Danzo-Fit is 122 as sub
		//Cross Functional Training as root 5
		$servicedata = array();        
		array_set($servicedata,'servicesubcategory_id', 13);       
		$resp = $item->update($servicedata);
	}	
});

Route::get('/customercleanup', function() {
	//matt pilates (89, 99)
	// delete servicesubcategory 89, 99
	$customer = Customer::where('picture','like' ,'%http:%')
	->where('identity','facebook')					
	->get();
	
	foreach ($customer as $item) {

		$picture = $item['picture'];		
		$newpic = str_replace("http:", "https:", $picture);
		$newpic2 = str_replace("http%", "https%", $newpic);	
		array_set($customerdata,'picture', $newpic2);

		echo $resp = $item->update($customerdata);
	}				
	
	
});

Route::get('csv/booktrialall',  array('as' => 'debug.csvbooktrialall','uses' => 'DebugController@csvBooktrialAll'));
Route::get('csv/orderall',  array('as' => 'debug.csvorderall','uses' => 'DebugController@csvOrderAll'));
Route::get('csv/fakebuyall',  array('as' => 'debug.csvfakebuyall','uses' => 'DebugController@csvFakebuyAll'));
Route::get('csv/captureall',  array('as' => 'debug.csvcaptureall','uses' => 'DebugController@csvCaptureAll'));
Route::get('csv/katchi',  array('as' => 'debug.csvkatchi','uses' => 'DebugController@csvKatchi'));
Route::get('csv/ozonetel',  array('as' => 'debug.ozonetel','uses' => 'DebugController@csvOzonetel'));
Route::get('csv/peppertap',  array('as' => 'debug.peppertap','uses' => 'DebugController@csvPeppertap'));
Route::get('lonlat',  array('as' => 'debug.lonlat','uses' => 'DebugController@lonlat'));
Route::get('csv/orderfitmania',  array('as' => 'debug.orderfitmania','uses' => 'DebugController@orderFitmania'));
Route::get('csv/paidtrial',  array('as' => 'debug.csvpaidtrial','uses' => 'DebugController@csvPaidTrial'));
Route::get('csv/freespecial',  array('as' => 'debug.freespecial','uses' => 'DebugController@freeSpecial'));
Route::get('csv/membershipfitmania',  array('as' => 'debug.membershipfitmania','uses' => 'DebugController@membershipFitmania'));
Route::get('csv/reviewaddress',  array('as' => 'debug.reviewaddress','uses' => 'DebugController@reviewAddress'));
Route::get('dumpno',  array('as' => 'debug.dumpno','uses' => 'DebugController@dumpNo'));
Route::get('dumpmissedcallno',  array('as' => 'debug.dumpmissedcallno','uses' => 'DebugController@dumpMissedcallNo'));
Route::get('top10finder',  array('as' => 'debug.top10finder','uses' => 'DebugController@top10Finder'));
Route::get('finderwithnoschedule',  array('as' => 'debug.finderwithnoschedule','uses' => 'DebugController@finderWithNoSchedule'));
Route::get('finderstatus',  array('as' => 'debug.finderstatus','uses' => 'DebugController@finderStatus'));
Route::get('findershaveratecardwithnoservices',  array('as' => 'debug.findershaveratecardwithnoservices','uses' => 'DebugController@findersHaveRatecardWithNoServices'));
Route::get('csv/paymentenabledservices', 'DebugController@paymentEnabledServices');
Route::get('renewalsmsstatus', 'DebugController@renewalSmsStatus');
Route::get('deleteid', 'DebugController@deleteId');
Route::get('updatebrandstofinders', 'DebugController@updateBrandToFindersFromCSV');
Route::get('addmanualtrialautoflagtoFinders', array('as'=> 'DebugController.addManualTrialAutoFlag', 'uses' => 'DebugController@addManualTrialAutoFlag'));



Route::get('repeat/customertrials/{year}/{division}', 'DebugController@customertrials');
Route::get('repeat/customertrialsrepeat/{year}/{division}', 'DebugController@customertrialsrepeat');

Route::get('repeat/customerorders/{year}/{division}', 'DebugController@customerorders');
Route::get('repeat/customerordersrepeat/{year}/{division}', 'DebugController@customerordersrepeat');



Route::get('topBooktrial/{from}/{to}','DebugController@topBooktrial');

Route::get('nehacustomertrials/{year}/{month}','DebugController@nehacustomertrials');


Route::get('updatefinderspecialoffertag', function (){
 
    //  DB::connection('mongodb')->table('finders')->where('status', '1')->update(['special_offer' => false]);
 
	$body["doc"]["offer_available"] = "true";
	$postfields_data = json_encode($body);

    $finder_ids = DB::connection('mongodb2')->table('offers')->where('hidden', false)
                     ->where('start_date', '<=', new DateTime( date("d-m-Y 00:00:00", time()) ))
                     ->where('end_date', '>=', new DateTime( date("d-m-Y 23:59:59", time()) ))
                     ->lists('vendor_id');
					//  exit;
	foreach($finder_ids as $finder){
		$request = array(
            'url' => Config::get('app.es.url')."/fitternity_finder/finder/".$finder."/_update",
            'port' => Config::get('app.es.port'),
            'method' => 'POST',
            'postfields' => $postfields_data
        );
		// return $request;
		$curl_response = es_curl_request($request);
        echo $curl_response;
	}
 
	


    //  DB::connection('mongodb')->table('finders')->whereIn('_id', $finder_ids)->update(['special_offer' => true]);
 
    //  echo "done";
 });

 Route::get('/getsearchlogs', function(){
	 $from = 0;
	 $i = 0;
	 for($i = 0; $i<100;$i++){
		 $from = 1000 *$i;
		 $query = '{
			"from":'.$from.',
			"size":2000,
					"query": {
						"match": {
						"event_id": "globalsearch"
						}
					},
					"sort": {
						"timestamp": {
						"order": "desc"
						}
					}
					}';
		$request = array(
			'url' => "http://fitternityelk:admin@52.74.67.151:8060/kyulogs/_search",
			'port' => 8060,
			'method' => 'POST',
			'postfields' => $query
			);

			// .strtolower(implode('","', $keylist)).
		
		$search_results     =   json_decode(es_curl_request($request),true);
			$search_results['hits']['hits'];
			$contents = file_get_contents("newfile.txt");
		foreach($search_results['hits']['hits'] as $result){
			echo $result['_source']['keyword']."<br>";
			$contents .= $result['_source']['keyword']."\n";
		}
		file_put_contents("newfile.txt", $contents);
		
	 }
 });




 ##################################################################################################
/*******************  Service to vendor category APIs ************************************************/

Route::get('servicetovendormigration', 'DebugController@serviceToVendorMigration');
Route::get('subcattoofferingsmigration', 'DebugController@subCatToOfferings');
Route::get('reversemigratevendors', 'DebugController@vendorReverseMigrate');
Route::get('booktrialfunnel','DebugController@booktrial_funnel');
Route::get('orderfunnel','DebugController@order_funnel');
Route::get('linksentfunnel','DebugController@linksent_funnel');
/******************  Service to vendor category API END HERE************************************************/
#####################################################################################################




// Checking global search
Route::get('pushfinders/{index}/{city_id}', 'GlobalPushController@pushfinders');


Route::post('manualtractionupdate/{type}/{increase_no}','DebugController@manualtractionupdate');
Route::get('addFacilityImages','DebugController@addFacilityImages');

Route::post('createFitcashCoupons','DebugController@createFitcashCoupons');
Route::post('convertOrdersToPPS','DebugController@convertOrdersToPPS');
Route::get('convertorderstoppsdiva','DebugController@convertOrdersToPPSDiva');
Route::post('convertorderstoppsdivarepeat','DebugController@convertOrdersToPPSDivaRepeat');

Route::get('removeloyalty/{id}','DebugController@removeloyalty');
Route::get('assignRenewal','DebugController@assignRenewal');
Route::post('createLoyaltyCoupons','DebugController@createLoyaltyCoupons');

Route::get('registerOngoingLoyalty','DebugController@registerOngoingLoyalty');

Route::get('registerOngoingLoyaltyMail','DebugController@registerOngoingLoyaltyMail');


Route::post('getreversehash','DebugController@getreversehash');
Route::get('verifyCheckinsFromReceipts','DebugController@verifyCheckinsFromReceipts');
Route::get('createtokenbycustomerid/{customer_email}','CustomerController@createtokenbycustomerid');
Route::get('assignGoldLoyalty','DebugController@assignGoldLoyalty');
Route::get('addLoyaltyVouherAll','DebugController@addLoyaltyVouherAll');
Route::get('brandLoyaltySplit','DebugController@brandLoyaltySplit');

Route::get('addVoucherCategory','DebugController@addVoucherCategory');

Route::get('ppsTOMembershipConversion','DebugController@ppsTOMembershipConversion');
Route::get('multifitFitcash','DebugController@multifitFitcash');
Route::get('verifyRatecards','DebugController@verifyRatecards');
Route::get('verifyRatecards','DebugController@verifyRatecards');

Route::get('salesgmvservices','DebugController@salesGMVServices');
Route::get('salesgmv','DebugController@salesGMV');
Route::get('leads','DebugController@leads');
Route::get('otherLeads','DebugController@otherLeads');
Route::get('reviews','DebugController@reviews');
Route::get('abandoncart','DebugController@abandoncart');
Route::get('gmvdata','DebugController@GMVData');
Route::get('commission','DebugController@commission');
Route::get('integratedSplit','DebugController@integratedSplit');
Route::get('salesGMVFinders','DebugController@salesGMVFinders');
Route::get('leadsCaptures','DebugController@leadsCaptures');
Route::get('salesRangeFinders','DebugController@salesRangeFinders');
Route::get('salesRangeFindersGyms','DebugController@salesRangeFindersGyms');
Route::get('salesRangeFindersStudio','DebugController@salesRangeFindersStudio');
Route::get('salesFinderDetails','DebugController@salesFinderDetails');
Route::get('fitcashCouponMigration','DebugController@fitcashCouponMigration');
Route::get('couponsValidMigration','DebugController@couponsValidMigration');
Route::get('testcodesnippet','DebugController@testcodesnippet');
Route::get('corporateCoupons','DebugController@corporateCoupons');
Route::get('rewardDistributionAndClaim', 'DebugController@rewardDistributionAndClaim');
Route::get('addAmountTransferToVendorBreakup', 'DebugController@addAmountTransferToVendorBreakup');
Route::get('manualToSession', 'DebugController@manualToSession');
Route::get('hyperLocal', 'DebugController@hyperLocal');
Route::get('fitnessForce','TransactionController@fitnessForce');
Route::post('combopasscreateforce', 'PassController@passCaptureAutoForce');
Route::get('hyperLocalList', 'DebugController@hyperLocalList');
