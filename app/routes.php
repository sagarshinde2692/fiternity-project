<?php

$monolog = Log::getMonolog();
$syslog = new \Monolog\Handler\SyslogHandler('fitapi');
$formatter = new \Monolog\Formatter\LineFormatter('%channel%.%level_name%: %message% %extra%');
$syslog->setFormatter($formatter);
$monolog->pushHandler($syslog);

App::error(function(Illuminate\Database\Eloquent\ModelNotFoundException $e){
	return Response::json('not found',404);
});

// Event::listen('illuminate.query', function($query)
// {
//    Log::info($query);
// });



// require __DIR__.'/debug_routes.php';
require __DIR__.'/analytics_routes.php';
require __DIR__.'/testing_routes.php';



 // $queries = DB::getQueryLog();
 // var_dump($queries);


Route::get('/', function() {  return date('l')." laravel beta 4.2 goes here...."; });
Route::get('acceptvendormou/{vendormouid}', 'FindersController@acceptVendorMou');
Route::get('cancelvendormou/{vendormouid}', 'FindersController@cancelVendorMou');




##############################################################################
/******************** VENDOR PANEL SECTION START HERE ***********************/



Route::post('/vendorlogin',  array('as' => 'vendor.login','uses' => 'VendorpanelController@doVendorLogin'));

Route::post('/vendorsummary/{finder_id?}/trials/{trial_id?}/cancel',
		array('as' => 'vendor.cancelTrialSessionByVendor','uses' => 'SchedulebooktrialsController@cancelTrialSessionByVendor'));

Route::group(array('before' => 'validatevendor'), function() {

	Route::post('/vendorapp/dashboard/{finder_id?}',
		array('as' => 'vendor.dashboard', 'uses' => 'VendorpanelController@dashboard'));

	Route::post('/vendorapp/reviews/{finder_id?}',
		array('as' => 'vendor.reviews','uses' => 'VendorpanelController@getReviewsApp'));

	Route::post('/vendorapp/sales/{finder_id?}',
			array('as' => 'vendor.sales','uses' => 'VendorpanelController@getSalesApp'));

	Route::post('/vendorapp/ozonetel/{finder_id?}',
				array('as' => 'vendor.ozonetel','uses' => 'VendorpanelController@getOzonetelApp'));

	Route::post('/vendorapp/trials/{finder_id?}',
					array('as' => 'vendor.trials','uses' => 'VendorpanelController@getTrialsApp'));

	Route::post('/vendorapp/upcomingtrials/{finder_id?}',
						array('as' => 'vendor.upcomingtrials','uses' => 'VendorpanelController@getUpcomingTrialsApp'));

	Route::post('/refreshWebToken',
		array('as' => 'vendor.refreshWebToken', 'uses' => 'VendorpanelController@refreshWebToken'));

	Route::get('/vendorsummary/listVendors',
		array('as' => 'vendor.listvendor', 'uses' => 'VendorpanelController@getVendorsList'));

	Route::get('/vendorsummary/{finder_id?}',
		array('as' => 'vendor.summaryvendor', 'uses' => 'VendorpanelController@getVendorDetails'));

	Route::get('/vendorsummary/{finder_id?}/contract',
		array('as' => 'vendor.summarycontract', 'uses' => 'VendorpanelController@getContractualInfo'));

	Route::post('/vendorsummary/sales/{finder_id?}/{start_date?}/{end_date?}',
		array('as' => 'vendor.summarysales', 'uses' => 'VendorpanelController@getSummarySales'));

	Route::post('/vendorsummary/sales/{finder_id?}/{type?}/{start_date?}/{end_date?}',
		array('as' => 'vendor.saleslist', 'uses' => 'VendorpanelController@getSalesList'));

	Route::post('/vendorsummary/trials/{finder_id?}/{start_date?}/{end_date?}',
		array('as' => 'vendor.summarytrials','uses' => 'VendorpanelController@getSummaryTrials'));

	Route::post('/vendorsummary/trials/{finder_id?}/{type?}/{start_date?}/{end_date?}',
		array('as' => 'vendor.trialslist', 'uses' => 'VendorpanelController@getTrialsList'));

	Route::post('/vendorsummary/ozonetel/{finder_id?}/{start_date?}/{end_date?}',
		array('as' => 'vendor.summaryozonetelcalls','uses' => 'VendorpanelController@getSummaryOzonetelcalls'));

	Route::post('/vendorsummary/ozonetel/{finder_id?}/{type?}/{start_date?}/{end_date?}',
		array('as' => 'vendor.ozonetellist', 'uses' => 'VendorpanelController@getOzonetelList'));

	Route::post('/vendorsummary/statistics/{date?}',
		array('as' => 'vendor.summarystatistics','uses' => 'VendorpanelController@getSummaryStatistics'));
	
	Route::post('/vendorsummary/reviews/{finder_id?}/{start_date?}/{end_date?}',
		array('as' => 'vendor.summaryreviews','uses' => 'VendorpanelController@getSummaryReviews'));

	Route::post('/vendorsummary/inquiries/{finder_id?}/{start_date?}/{end_date?}',
		array('as' => 'vendor.totalinquiries','uses' => 'VendorpanelController@getTotalInquires'));

	Route::post('/vendorsummary/profile/{finder_id?}',
		array('as' => 'vendor.profile','uses' => 'VendorpanelController@profile'));

	Route::post('/vendorsummary/recentprofileupdaterequest/{finder_id?}',
		array('as' => 'vendor.getrecentprofileupdaterequest','uses' => 'VendorpanelController@getRecentProfileUpdateRequest'));

	Route::post('/vendorsummary/{finder_id?}/reviews/{review_id?}/reply',
		array('as' => 'vendor.reviewReplyByVendor','uses' => 'VendorpanelController@reviewReplyByVendor'));

	Route::post('/vendorsummary/{finder_id?}/trial/{trial_id?}/edit',
		array('as' => 'vendor.updateTrialByVendor','uses' => 'VendorpanelController@updateTrialByVendor'));

	Route::put('/vendorsummary/profile/{finder_id?}/edit',
		array('as' => 'vendor.updateprofile','uses' => 'VendorpanelController@updateProfile'));

	Route::get('/gettrialdetail/{booktrial_id}',array('as' => 'vendor.gettrialdetail','uses' => 'VendorpanelController@gettrialdetail'));

	Route::post('/cancelbyslot',array('as' => 'vendor.cancelbyslot','uses' => 'SchedulebooktrialsController@cancelByslot'));
	
});


/******************** VENDOR PANEL SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** HOME SECTION START HERE ***********************/
Route::post('saveutmdata', array('as' => 'home.saveutmdata','uses' => 'HomeController@saveUtmData'));
Route::post('gethashes', array('as' => 'home.gethashes','uses' => 'HomeController@getHashes'));

Route::get('monsoonsalehome/{city?}', 'HomeController@getMonsoonSaleHomepage');
Route::get('getfindercountlocationwise/{city?}', 'HomeController@getFinderCountLocationwise');

Route::get('/home', 'HomeController@getHomePageData');
Route::get('/homev2/{city?}', 'HomeController@getHomePageDatav2');
Route::get('/homev3/{city?}', 'HomeController@getHomePageDatav3');
Route::get('/homev4/{city?}', 'HomeController@getHomePageDatav4');
Route::get('footer/{city?}', 'HomeController@getFooterByCity');
Route::get('footerv1/{city?}/{category}/{location?}', 'HomeController@getFooterByCityV1');
Route::get('/zumbadiscover', 'HomeController@zumbadiscover');
Route::get('/fitcardpage1finders', 'HomeController@fitcardpagefinders');

Route::get('/specialoffers_finder', 'HomeController@specialoffers_finder');
Route::get('/yfc_finders', 'HomeController@yfc_finders');
Route::get('landingzumba', 'HomeController@landingzumba');
Route::get('landingcrushfinders/', 'HomeController@landingcrushFinders');
Route::get('landingcrushlocationclusterwise/{location_cluster}', 'HomeController@landingcrushLocationClusterWise');
Route::get('landinganytimefitnessfinders/', 'HomeController@landingAnytimeFitnessFinders');
Route::get('landinganytimefitnessfinders/{cityid}', 'HomeController@landingAnytimeFitnessFindersCityWise');



// Power house gym
Route::get('landingpowerhousefinders/', 'HomeController@landingPowerhouseFinders');

Route::get('landingfinders/{typeoflandingpage}', 'HomeController@landingFinders');
Route::get('landingfinderstitle/{typeoflandingpage}/{cityid?}', 'HomeController@landingFindersTitle');

Route::get('/fitcardfinders', 'HomeController@fitcardfinders');
Route::post('/fitcardfindersv1', 'HomeController@fitcardfindersV1');

Route::get('getcollecitonnames/{city?}', 'HomeController@getcollecitonnames');
Route::get('getcollecitonfinders/{city}/{slug}', 'HomeController@getcollecitonfinders');
Route::get('getlocations/{city?}', 'HomeController@getCityLocation');
Route::get('getcategories/{city?}', 'HomeController@getCityCategorys');
Route::get('getcities', 'HomeController@getCities');
Route::get('ifcity/{city?}', 'HomeController@ifCity');

Route::get('getlandingpagefinders/{cityid}/{landingpageid}/{locationclusterid?}', 'HomeController@getLandingPageFinders');

Route::get('offers/{city?}/{from?}/{size?}', 'HomeController@getOffers');
Route::get('offertabs/{city?}', 'HomeController@getOffersTabs');
Route::get('offertabsoffers/{city}/{captionslug}/{slug}', 'HomeController@getOffersTabsOffersV1');
Route::get('categorytagofferings/{city?}', 'HomeController@getCategorytagsOfferings');


Route::get('getcapturedetail/{captureid}', 'CaptureController@getCaptureDetail');

Route::post('feedbackfromcustomer', 'SchedulebooktrialsController@feedbackFromCustomer');

/*Events API*/
Route::get('events/{eventSlug}', 'EventsController@getEventInfo');

/*Coupons API*/
Route::get('getcouponinfo/{couponCode}/{ticketID}', 'CouponsController@getCouponInfo');

Route::get('send/communication', 'SchedulebooktrialsController@sendCommunication');

##############################################################################
/******************** CUSTOMERS SECTION START HERE ***********************/
Route::get('/fitcardautobooktrials/{customeremail}',  array('as' => 'customer.fitcardautobooktrials','uses' => 'CustomerController@getFitcardAutoBookTrials'));
Route::get('/autobooktrial/{trialid}',  array('as' => 'customer.autobooktrial','uses' => 'CustomerController@getAutoBookTrial'));
// Route::post('capturepayment',  array('as' => 'customer.capturepayment','uses' => 'CustomerController@capturePayment'));

Route::post('generatefitcardcodorder',  array('as' => 'customer.generatefitcardcodorder','uses' => 'CustomerController@generateFitCardCodOrder'));
Route::post('generatefitcardtmporder',  array('as' => 'customer.generatefitcardtmporder','uses' => 'CustomerController@generateFitCardTmpOrder'));
// Route::post('captureorderpayment',  array('as' => 'customer.captureorderpayment','uses' => 'CustomerController@captureOrderPayment'));
Route::post('captureorderpayment', array('as' => 'customer.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialPaid'));

Route::post('customerregister', array('as' => 'customer.customerregister','uses' => 'CustomerController@register'));
Route::post('customerlogin', array('as' => 'customer.customerlogin','uses' => 'CustomerController@customerLogin'));
Route::post('customerloginotp', array('as' => 'customer.customerloginotp','uses' => 'CustomerController@customerLoginOtp'));
Route::post('customerforgotpasswordemail', array('as' => 'customer.customerforgotpasswordemail','uses' => 'CustomerController@forgotPasswordEmail'));
Route::post('customerforgotpassword', array('as' => 'customer.customerforgotpassword','uses' => 'CustomerController@forgotPassword'));
Route::post('customerforgotpasswordemailapp', array('as' => 'customer.customerforgotpasswordemailapp','uses' => 'CustomerController@forgotPasswordEmailApp'));
Route::post('customervalidateotp', array('as' => 'customer.customervalidateotp','uses' => 'CustomerController@validateOtp'));

Route::post('customerstatus', array('as' => 'customer.customerstatus','uses' => 'CustomerController@customerstatus'));



Route::get('autobooktrials/{customeremail}/{from?}/{size?}',  array('as' => 'customer.autobooktrials','uses' => 'CustomerController@getAutoBookTrials'));
Route::get('reviews/{customerid}/{from?}/{size?}',  array('as' => 'customer.reviews','uses' => 'CustomerController@reviewListing'));
Route::get('orderhistory/{customeremail}/{from?}/{size?}',  array('as' => 'customer.orderhistory','uses' => 'CustomerController@orderHistory'));
Route::get('bookmarks/{customerid}',  array('as' => 'customer.bookmarks','uses' => 'CustomerController@getBookmarks'));
Route::get('updatebookmarks/{customerid}/{finderid}/{remove?}',  array('as' => 'customer.updatebookmarks','uses' => 'CustomerController@updateBookmarks'));
Route::get('updateservicebookmarks/{customerid}/{serviceid}/{remove?}',  array('as' => 'customer.updateservicebookmarks','uses' => 'CustomerController@updateServiceBookmarks'));
Route::get('customerdetail/{customerid}',  array('as' => 'customer.customerdetail','uses' => 'CustomerController@customerDetail'));
Route::get('foryou/{customeremail}/{city_id}/{lat?}/{lon?}',  array('as' => 'customer.foryou','uses' => 'CustomerController@foryou'));

Route::get('reviews/email/{customeremail}/{from?}/{size?}',  array('as' => 'customer.reviewsbyemail','uses' => 'CustomerController@reviewListingByEmail'));
Route::get('bookmarks/email/{customeremail}',  array('as' => 'customer.bookmarksbyemail','uses' => 'CustomerController@getBookmarksByEmail'));
Route::get('updatebookmarks/email/{customeremail}/{finderid}/{remove?}',  array('as' => 'customer.updatebookmarksbyemail','uses' => 'CustomerController@updateBookmarksByEmail'));
Route::get('customerdetail/email/{customeremail}',  array('as' => 'customer.customerdetailbyemail','uses' => 'CustomerController@customerDetailByEmail'));
Route::get('isregistered/{email}/{id?}/{collection?}',  array('as' => 'customer.isregistered','uses' => 'CustomerController@isRegistered'));
Route::post('customer/addregid', array('as' => 'customer.addregid','uses' => 'CustomerController@addRegId'));
Route::post('customer/add/webnotification', array('as' => 'customer.addwebnotification','uses' => 'CustomerController@addWebNotification'));
Route::post('customer/update/webnotification', array('as' => 'customer.updatewebnotification','uses' => 'CustomerController@updateWebNotification'));
Route::post('customer/addhealthinfo', array('as' => 'customer.addhealthinfo','uses' => 'CustomerController@addHealthInfo'));
Route::post('customer/myrewards/create', array('as' => 'customer.createMyReward','uses' => 'MyrewardController@createMyReward'));
Route::group(array('before' => 'device'), function() {
    Route::get('customer/home/{city?}', array('as' => 'customer.home','uses' => 'CustomerController@home'));
});

Route::post('customer/transformation', array('as' => 'customer.transformation','uses' => 'CustomerController@transformation'));
Route::post('sms/downloadapp', array('as' => 'customer.downloadapp','uses' => 'CustomerController@downloadApp'));
Route::get('app/forceupdate', array('as' => 'customer.forceupdate','uses' => 'CustomerController@forceUpdate'));
Route::get('app/config', array('as' => 'customer.appconfig','uses' => 'CustomerController@appConfig'));
Route::post('storecustomerattribution',  array('as' => 'customer.storecustomerattribution','uses' => 'CustomerController@storeCustomerAttribution'));


Route::post('inviteforsignup', array('as' => 'customer.inviteForSignup','uses' => 'CustomerController@inviteForSignup'));


Route::post('admin/customer/capturemyreward', array('as' => 'customer.capturemyreward','uses' => 'CustomerController@captureMyReward'));

Route::group(array('before' => 'validatetoken'), function() {

	Route::get('validatetoken', array('as' => 'customer.validatetoken','uses' => 'CustomerController@validateToken'));
	Route::get('customerlogout', array('as' => 'customer.validatetokencustomerlogout','uses' => 'CustomerController@customerLogout'));

	Route::post('customer/resetpassword', array('as' => 'customer.customerresetpassword','uses' => 'CustomerController@resetPassword'));
	Route::post('customer/update', array('as' => 'customer.customerupdate','uses' => 'CustomerController@customerUpdate'));
	Route::get('customer/getalltrials/{from?}/{size?}',  array('as' => 'customer.getalltrials','uses' => 'CustomerController@getAllTrials'));
	Route::get('customer/getallreviews/{offset?}/{limit?}',  array('as' => 'customer.getallreviews','uses' => 'CustomerController@getAllReviews'));
	Route::get('customer/getallorders/{offset?}/{limit?}',  array('as' => 'customer.getallorders','uses' => 'CustomerController@getAllOrders'));
	Route::get('customer/getallbookmarks',  array('as' => 'customer.getallbookmarks','uses' => 'CustomerController@getAllBookmarks'));
	Route::get('customer/editbookmarks/{finder_id}/{remove?}',  array('as' => 'customer.editbookmarks','uses' => 'CustomerController@editBookmarks'));
	Route::get('getcustomerdetail',  array('as' => 'customer.getcustomerdetail','uses' => 'CustomerController@getCustomerDetail'));
	Route::get('getcustomertransactions',  array('as' => 'customer.getcustomertransactions','uses' => 'CustomerController@getCustomerTransactions'));
	Route::get('upcomingtrials',  array('as' => 'customer.upcomingtrials','uses' => 'CustomerController@getUpcomingTrials'));
	Route::get('customer/myrewards/list/{offset?}/{limit?}',  array('as' => 'customer.listMyRewards','uses' => 'MyrewardController@listMyRewards'));
	Route::get('customer/myrewardsv1/list/{offset?}/{limit?}',  array('as' => 'customer.listMyRewardsv1','uses' => 'MyrewardController@listMyRewardsV1'));
	Route::get('customer/mydietplan/list',  array('as' => 'customer.listMyDietPlan','uses' => 'CustomerController@listMyDietPlan'));
	
	// Route::post('apply/promotioncode', array('as' => 'customer.applypromotioncode','uses' => 'CustomerController@applyPromotionCode'));

	// Wallet APIs...
	Route::post('apply/promotioncode', array('as' => 'customer.applypromotioncode','uses' => 'CustomerController@applyPromotionCode'));

	Route::get('getwalletbalance',  array('as' => 'customer.getWalletBalance','uses' => 'CustomerController@getWalletBalance'));
	
//	Route::post('wallettransaction',  array('as' => 'customer.walletTransaction','uses' => 'CustomerController@walletTransaction'));
	Route::get('listwalletsummary/{limit?}/{offset?}',  array('as' => 'customer.listWalletSummary','uses' => 'CustomerController@listWalletSummary'));
	Route::get('getexistingtrialwithfinder/{finder_id?}', array('as' => 'customer.getExistingTrialWithFinder','uses' => 'CustomerController@getExistingTrialWithFinder'));
	Route::get('customer/getinteractedfinder',  array('as' => 'customer.getinteractedfinder','uses' => 'CustomerController@getInteractedFinder'));
	Route::post('customer/capturemyreward', array('as' => 'customer.capturemyreward','uses' => 'CustomerController@captureMyReward'));

	Route::post('customer/transformation', array('as' => 'customer.transformation','uses' => 'CustomerController@transformation'));
	Route::post('customer/stayontrack', array('as' => 'customer.stayontrack','uses' => 'CustomerController@stayOnTrack'));

	Route::get('customer/gettransformation', array('as' => 'customer.gettransformation','uses' => 'CustomerController@getTransformation'));
	Route::get('customer/getstayontrack', array('as' => 'customer.getstayontrack','uses' => 'CustomerController@getStayOnTrack'));
	Route::get('getreferralcode', array('as' => 'customer.referralcode','uses' => 'CustomerController@getReferralCode'));
	Route::post('referfriend', array('as' => 'customer.referfriend','uses' => 'CustomerController@referFriend'));

	Route::get('getwalletdetails/{limit?}/{offset?}',  array('as' => 'customer.getWalletDetails','uses' => 'CustomerController@getWalletDetails'));

	Route::post('reportareview', array('as' => 'finderdetails.reportareview','uses' => 'FindersController@reportReview'));
    
    Route::post('passcapture', 'PassController@passCapture');
	
	Route::get('customer/getstepprofile/{city?}',  array('as' => 'customer.getstepprofile','uses' => 'CustomerController@getStepProfile'));
    
    /******************** AUTHENTICATION ENFORCED FOR SECURITY START HERE ***********************/
    Route::get('booktrialdetail/{captureid}/{type?}', 'SchedulebooktrialsController@booktrialdetail');
    
    Route::get('orderdetail/{orderid}',  array('as' => 'orders.orderdetail','uses' => 'OrderController@getOrderDetail'));
    
    Route::get('/successmsg/{type}/{id}', 'HomeController@getSuccessMsg');
    /******************** AUTHENTICATION ENFORCED FOR SECURITY END HERE ***********************/

	Route::post('customer/campaignseenstatus', array('as' => 'customer.campaignseenstatus','uses' => 'CustomerController@campaignSeenStatus'));
});

Route::post('walletTransactionnew', array('uses' => 'OrderController@debitWalletTransaction'));
/******************** CUSTOMERS SECTION END HERE ********************/
##############################################################################




##############################################################################
/******************** REWARDS SECTION START HERE ***********************/

Route::get('listrewardsapplicableonpurchase', array(
	'as' => 'rewards.ListRewardsApplicableOnPurchase','uses' => 'RewardofferController@ListRewardsApplicableOnPurchase'
));
Route::post('getrewardoffers', array('as' => 'rewards.getRewardOffers','uses' => 'RewardofferController@getRewardOffers'));

/******************** REWARDS SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** RELIANCE SECTION START HERE ********************/

Route::post('reliancecustomer',[	'uses' => 'HomeController@updateRelianceCustomer']);
Route::post('reliancecustomerupdate',['uses' => 'HomeController@reliancecustomerupdate']);

/******************** RELIANCE SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** ORDERS SECTION START HERE ***********************/
Route::get('couponcodeusedforhealthytiffinbyphoneno/{phoneno}',  array('as' => 'customer.couponcodeusedforhealthytiffinbyphoneno','uses' => 'OrderController@couponCodeUsedForHealthyTiffinByPhoneno'));

// Route::post('checkcouponcode',  array('as' => 'orders.couponcode','uses' => 'OrderController@couponCode'));

Route::post('checkcouponcode',  array('as' => 'transaction.couponcode','uses' => 'TransactionController@checkCouponCode'));

Route::post('generatecodorder',  array('as' => 'orders.generatecodorder','uses' => 'OrderController@generateCodOrder'));
Route::post('generatetmporder',  array('as' => 'orders.generatetmporder','uses' => 'OrderController@generateTmpOrder'));
Route::post('capturepayment',  array('as' => 'order.buymembership','uses' => 'OrderController@captureOrderStatus'));
Route::post('captureorderstatus',  array('as' => 'orders.captureorderstatus','uses' => 'OrderController@captureOrderStatus'));
Route::post('capturefailsorders',  array('as' => 'orders.capturefailsorders','uses' => 'OrderController@captureFailOrders'));

Route::post('generatetmporderpre',  array('as' => 'orders.generatetmporderpre','uses' => 'OrderController@generateTmpOrderPre'));

Route::post('buyarsenalmembership',  array('as' => 'orders.buyarsenalmembership','uses' => 'OrderController@buyArsenalMembership'));
Route::post('buylandingpagepurchase',  array('as' => 'orders.buylandingpagepurchase','uses' => 'OrderController@buyLandingpagePurchase'));
Route::get('orderfailureaction/{order_id}/{customer_id?}', array('as' => 'orders.orderFailureAction','uses' => 'OrderController@orderFailureAction'));

Route::get('linkopenfororder/{order_id}',  array('as' => 'orders.linkOpenForOrder','uses' => 'OrderController@linkOpenForOrder'));

Route::post('orderupdate', array('as' => 'orders.orderupdate','uses' => 'OrderController@orderUpdate'));

Route::post('inviteformembership', array('as' => 'customer.inviteForMembership','uses' => 'OrderController@inviteForMembership'));







/******************** ORDERS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** USERS SECTION START HERE ***********************/
Route::get('/experts', 'UsersController@getExperts');
Route::get('/expert/{username}', 'UsersController@getExpert');
Route::get('/authors', 'UsersController@getAuthors');
Route::get('/author/{username}', 'UsersController@getAuthor');

/******************** USERS SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** FINDERS SECTION START HERE ***********************/
//Route::get('getallfinder/', array('as' => 'finders.getallfinder','uses' => 'FindersController@getallfinder'));
Route::get('finderdetail/{slug}', array('as' => 'finders.finderdetail','uses' => 'FindersController@finderdetail'));
Route::get('finderservices/{finderid}', array('as' => 'finders.finderservices','uses' => 'FindersController@finderServices'));
// Route::get('ratecards/{finderid}', array('as' => 'finders.ratecards','uses' => 'FindersController@ratecards'));
Route::get('ratecarddetail/{id}', array('as' => 'finders.ratecarddetail','uses' => 'FindersController@ratecarddetail'));
Route::post('updatefinderrating/', array('as' => 'finders.updatefinderrating','uses' => 'FindersController@updatefinderrating'));
Route::get('getfinderleftside/', array('as' => 'finders.getfinderleftside','uses' => 'FindersController@getfinderleftside'));
//Route::get('getallfinders/', array('as' => 'finders.getallfinders','uses' => 'FindersController@getallfinders'));
Route::get('updatefinderlocaiton/', array('as' => 'finders.updatefinderlocaiton','uses' => 'FindersController@updatefinderlocaiton'));

Route::get('finder/sendbooktrialdaliysummary/', array('as' => 'finders.sendbooktrialdaliysummary','uses' => 'FindersController@sendbooktrialdaliysummary'));
Route::get('checkbooktrialdaliysummary/{date}', array('as' => 'finders.checkbooktrialdaliysummary','uses' => 'FindersController@checkbooktrialdaliysummary'));

Route::get('reviewlisting/{finderid}/{from?}/{size?}', array('as' => 'finders.reviewlisting','uses' => 'FindersController@reviewListing'));
Route::post('addreview', array('as' => 'finders.addreview','uses' => 'FindersController@addReview'));
Route::get('reviewdetail/{id}', array('as' => 'review.reviewdetail','uses' => 'FindersController@detailReview'));
Route::get('getfinderreview/{slug}', array('as' => 'finders.getfinderreview','uses' => 'FindersController@getFinderReview'));
Route::get('findertopreview/{slug}/{limit?}', array('as' => 'finders.findertopreview','uses' => 'FindersController@finderTopReview'));

Route::get('finderdetail/app/{slug}', array('as' => 'finders.finderdetailapp','uses' => 'FindersController@finderDetailApp'));
// Route::get("/pushBrandOutlets/{index_name}", "GlobalPushController@pushBrandOutlets");
Route::get('finderdetailphoto/app/{slug}', array('as' => 'finders.finderdetailAppPhoto','uses' => 'FindersController@finderdetailAppPhoto'));

/******************** FINDERS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** BLOGS SECTION START HERE ********************/
Route::get('/blogs/{offset}/{limit}', 'BlogsController@getBlogs');
Route::get('blogdetail/{slug}', array('as' => 'blogs.blogdetail','uses' => 'BlogsController@blogdetail'));
Route::get('/blogs/{cat}', 'BlogsController@getCategoryBLogs');
Route::get('/updateblogdate', 'BlogsController@updateblogdate');
Route::post('/getblogrelatedfinder', 'BlogsController@getblogRelatedFinder');
Route::post('addcomment', array('as' => 'blogs.addcomment','uses' => 'BlogsController@addComment'));
Route::get('getblogcomment/{slug}', array('as' => 'blogs.getblogcomment','uses' => 'BlogsController@getBlogComment'));

/******************** BLOGS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** ELASTICSEARH SECTION START HERE  *******************/
// Route::get('createindex/{index?}', array('as' => 'elasticsearch.createindex','uses' => 'ElasticsearchController@createIndex'));
// Route::get('deleteindex/{index?}', array('as' => 'elasticsearch.deleteindex','uses' => 'ElasticsearchController@deleteIndex'));
// Route::get('managesetttings/{index?}', array('as' => 'elasticsearch.managesetttings','uses' => 'ElasticsearchController@manageSetttings'));
// Route::get('createtype/{type}', array('as' => 'elasticsearch.createtype','uses' => 'ElasticsearchController@createType'));
Route::get('checkmapping/{type}', array('as' => 'elasticsearch.checkmapping','uses' => 'ElasticsearchController@checkMapping'));
// Route::get('deletetype/{type}', array('as' => 'elasticsearch.deletetype','uses' => 'ElasticsearchController@deleteType'));		
// Route::get('mongo2elastic/{type?}', array('as' => 'elasticsearch.mongo2elastic','uses' => 'ElasticsearchController@mongo2Elastic'));
// Route::get('indexautosuggestdata/{type?}', array('as' => 'elasticsearch.indexautosuggestdata','uses' => 'ElasticsearchController@indexautosuggestdata'));
// Route::get('indexrankmongo2elastic', array('as' => 'elasticsearch.indexrankmongo2elastic','uses' => 'RankingController@IndexRankMongo2Elastic'));
// Route::get('manageautosuggestsetttings', array('as' => 'elasticsearch.manageautosuggestsetttings','uses' => 'ElasticsearchController@manageAutoSuggestSetttings'));
// Route::get('embedtrials', array('as' => 'elasticsearch.embedtrials','uses' => 'RankingController@embedTrialsBooked'));
// Route::get('indexservicerankmongo2elastic', array('as' => 'elasticsearch.indexservicerankmongo2elastic','uses' => 'ServiceRankingController@IndexServiceRankMongo2Elastic'));
Route::get('v1/rollingfinderindex', array('as' => 'elasticsearch.rollingbuildfindersearch','uses' => 'RankingController@RollingBuildFinderSearchIndex'));
Route::get('v1/rollingserviceindex', array('as' => 'elasticsearch.rollingbuildserviceindex','uses' => 'ServiceRankingController@RollingBuildServiceIndex'));
//Route::get('rollingbuildserviceindexv2','ServiceRankingController@RollingBuildServiceIndex');
Route::get('indexfinderdocument/{id}','RankingController@IndexFinderDocument');
Route::get('locationcity/{value?}','SearchController@locationCity');

Route::get('updatescheduleinsearch/{finderid}','ServiceRankingController@UpdateScheduleOfThisFinderInSessionSearch');

/******************** ELASTICSEARH SECTION END HERE  ********************/
##############################################################################

########################################################################################
/************************KYU SECTION START HERE****************************************/
Route::post('pushkyuevent', 'KYUController@pushkyuevent');
// Route::get('migratedatatoclevertap', 'KYUController@migratedatatoclevertap');
Route::get('getvendorview/{vendor_id}/{start_date?}/{end_date?}','KYUController@getvendorviewcount');
Route::post('getcitywiseviews','KYUController@getcitywiseviews');
//Route::get('getfacebookadsconversion','KYUController@getfacebookadsconversion');

/************************KYU SECTION END HERE******************************************/
########################################################################################


##############################################################################
/******************** SEARCH SECTION START HERE ********************/
Route::post('search', 'SearchController@getGlobal');
Route::post('search/finders', 'SearchController@getFinders');
Route::post('findersearchv3', 'SearchController@getFindersv3');
Route::post('globalsearch', 'SearchController@getGlobalv2');
Route::post('findersearch', 'SearchController@getFindersv4');
Route::post('geolocationfindersearch', 'SearchController@geoLocationFinder');

Route::get('categoryfinders', 'SearchController@categoryfinders');
Route::post('fitmaniafinders', 'SearchController@getFitmaniaFinders');
Route::post('fitcardfinders', 'SearchController@getFitcardFinders');

Route::post('workoutsessionsearch', 'SearchServicesController@getWorkoutsessions');
Route::post('ratcardsearch', 'SearchServicesController@getRatecards');
Route::post('getnearbytrials', 'SearchServicesController@geoLocationService');
Route::post('getrankedfinder', 'RankingSearchController@getRankedFinderResults');
Route::post('getfindercategory', 'RankingController@getFinderCategory');
Route::post('search/getautosuggestresults', 'GlobalSearchController@getautosuggestresults');
Route::post('getcategoryofferings', 'RankingSearchController@CategoryAmenities');
Route::post('getcategoryofferingsv2', 'RankingSearchController@CategoryAmenitiesv2');
Route::post('getcategories', 'RankingSearchController@getcategories');
Route::post('getsearchmetadata', 'RankingSearchController@getsearchmetadata');
Route::post('getrankedservices', 'ServiceRankingSearchController@searchrankedservices');
Route::get('getservicecategories','ServiceRankingSearchController@getservicecategories');
Route::post('getmaxminservice', 'ServiceRankingSearchController@getmaxminservice');
Route::post('getrankedfinderapp', 'RankingSearchController@getRankedFinderResultsMobile');
Route::post('keywordsearchweb', 'GlobalSearchController@keywordSearch');
Route::post('search/getfinderresults', 'RankingSearchController@getRankedFinderResultsApp');
Route::post('search/getfinderresultsv2', 'RankingSearchController@getRankedFinderResultsAppv2');
Route::post('search/getfinderresultsv3', 'RankingSearchController@getRankedFinderResultsAppv3');

Route::post('search/getfinderresultsv4', 'RankingSearchController@getRankedFinderResultsAppv4');

Route::get('buildkeywordcache', 'GlobalSearchController@preparekeywordsearchcache');
Route::post('keywordsearchwebv1', 'GlobalSearchController@improvedkeywordSearch');
Route::post('search/searchdirectpefinders', 'RankingSearchController@searchDirectPaymentEnabled');
Route::post('search/searchviptrials', 'ServiceRankingSearchController@searchviptrials');
Route::post('search/searchsaleratecards/v1', 'ServiceRankingSearchController@searchSaleRatecards');

/******************** SEARCH SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** SERVICE SECTION START HERE ********************/

Route::get('updateserviceslug/', array('as' => 'service.updateserviceslug','uses' => 'ServiceController@updateSlug'));
Route::get('servicedetail/{id}', array('as' => 'service.servicedetail','uses' => 'ServiceController@serviceDetail'));
Route::get('servicecategorys', array('as' => 'service.servicecategorys','uses' => 'ServiceController@getServiceCategorys'));
Route::get('servicemarketv1/{city?}', array('as' => 'service.servicemarket','uses' => 'ServiceController@getServiceHomePageDataV1'));
Route::get('servicemarketfooterv1/{city?}', array('as' => 'service.servicemarketfooter','uses' => 'ServiceController@getFooterByCityV1'));
Route::get('service/getservicewithworkoutsession/{finder_id}', array('as' => 'service.getservicewithworkoutsession','uses' => 'ServiceController@getServiceWithWorkoutSession'));
Route::get('service/getworkoutsessionschedulebyservice/{service_id}/{date?}', array('as' => 'service.getworkoutsessionschedulebyservice','uses' => 'ServiceController@getWorkoutSessionScheduleByService'));
Route::get('getservicesbytype/{finder_id}/{type}', array('as' => 'service.getservicesbytype','uses' => 'ServiceController@getServicesByType'));
Route::get('getschedulebyfinderservice', array('as' => 'service.getschedulebyfinderservice','uses' => 'ServiceController@getScheduleByFinderService'));
Route::post('timepreference', array('as' => 'service.timepreference','uses' => 'ServiceController@timepreference'));



/******************** SERVICE SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** SCHEDULE BOOK TRIAL SECTION START HERE ***********************/
Route::get('getschedulebooktrial/{finderid?}/{date?}', array('as' => 'finders.getschedulebooktrial','uses' => 'SchedulebooktrialsController@getScheduleBookTrial'));
Route::get('booktrial/{finderid?}/{date?}', array('as' => 'finders.getbooktrial','uses' => 'SchedulebooktrialsController@getBookTrial'));
Route::post('booktrial', array('as' => 'finders.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialFree'));
Route::post('updatebooktrial', array('as' => 'finders.updatebooktrial','uses' => 'SchedulebooktrialsController@updateBookTrial'));
Route::post('manualbooktrial', array('as' => 'finders.storemanualbooktrial','uses' => 'SchedulebooktrialsController@manualBookTrial'));
Route::post('confirmmanualtrialbyvendor', array('as' => 'finders.confirmmanualtrialbyvendor','uses' => 'SchedulebooktrialsController@confirmmanualtrialbyvendor'));
//Route::post('manual2ndbooktrial', array('as' => 'finders.storemanual2ndbooktrial','uses' => 'SchedulebooktrialsController@manual2ndBookTrial'));
Route::post('storebooktrial', array('as' => 'customer.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialPaid'));
Route::post('rescheduledbooktrial', array('as' => 'customer.rescheduledbooktrial','uses' => 'SchedulebooktrialsController@rescheduledBookTrial'));
Route::post('storebooktrialhealthytiffinfree', array('as' => 'customer.storebooktrialhealthytiffinfree','uses' => 'SchedulebooktrialsController@bookTrialHealthyTiffinFree'));
Route::post('storebooktrialhealthytiffin', array('as' => 'customer.storebooktrialhealthytiffin','uses' => 'SchedulebooktrialsController@bookTrialHealthyTiffinPaid'));
Route::post('storebookmembershiphealthytiffin', array('as' => 'customer.storebookmembershiphealthytiffin','uses' => 'SchedulebooktrialsController@bookMembershipHealthyTiffinPaid'));


Route::get('finder/senddaliysummaryhealthytiffin/', array('as' => 'finders.senddaliysummaryhealthytiffin','uses' => 'FindersController@sendDaliySummaryHealthyTiffin'));


Route::get('gettrialschedule/{finderid}/{date}', array('as' => 'services.gettrialschedule', 'uses' => 'SchedulebooktrialsController@getTrialSchedule'));
Route::get('gettrialschedulev1/{finderid}/{date}/{service_id?}', array('as' => 'services.gettrialschedule', 'uses' => 'SchedulebooktrialsController@getTrialScheduleIfDontSoltsAlso'));
Route::get('getworkoutsessionschedule/{finderid}/{date}/{service_id?}', array('as' => 'services.getworkoutsessionschedule', 'uses' => 'SchedulebooktrialsController@getWorkoutSessionSchedule'));
Route::get('getserviceschedule/{serviceid}/{date?}/{noofdays?}/{schedulesof?}', array('as' => 'services.getserviceschedule','uses' => 'SchedulebooktrialsController@getServiceSchedule'));
// Route::get('booktrialff', array('as' => 'schedulebooktrials.booktrialff','uses' => 'SchedulebooktrialsController@bookTrialFintnessForce'));
Route::get('updateappointmentstatus', array('as' => 'customer.updateappointmentstatus','uses' => 'SchedulebooktrialsController@updateAppointmentStatus'));
Route::get('canceltrial/{trialid}', array('as' => 'trial.cancel', 'uses' => 'SchedulebooktrialsController@cancel'));

Route::post('invitefortrial', array('as' => 'customer.inviteForTrial','uses' => 'SchedulebooktrialsController@inviteForTrial'));
Route::post('acceptinvite', array('as' => 'customer.acceptInvite','uses' => 'SchedulebooktrialsController@acceptInvite'));

Route::get('getremindermessage/',  array('as' => 'trial.getremindermessage','uses' => 'SchedulebooktrialsController@getReminderMessage'));
Route::post('nutritionstore/',  array('as' => 'trial.nutritionstore','uses' => 'SchedulebooktrialsController@nutritionStore'));

Route::group(array('before' => 'validatetoken'), function() {

	Route::post('posttrialaction/{source}', array('as' => 'trial.posttrialaction', 'uses' => 'SchedulebooktrialsController@postTrialAction'));
	Route::get('booktrials/cancel/{trialid}', array('as' => 'trial.cancel', 'uses' => 'SchedulebooktrialsController@cancel'));
	Route::get('booktrials/confirm/{trialid}', array('as' => 'trial.confirm', 'uses' => 'SchedulebooktrialsController@confirm'));

	Route::post('booktrials/reschedule', array('as' => 'customer.rescheduledbooktrial','uses' => 'SchedulebooktrialsController@rescheduledBookTrial'));
	Route::get('booktrials/{action}/{trialid}', array('as' => 'trial.booktrialaction', 'uses' => 'SchedulebooktrialsController@booktrialAction'));
	Route::post('pretrialaction/{source}', array('as' => 'trial.pretrialaction', 'uses' => 'SchedulebooktrialsController@preTrialAction'));

});


/******************** SCHEDULE BOOK TRIAL SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** SENDING EMAIL STUFFS SECTION START HERE ********************/
Route::post('notify/{notifytype}','EmailSmsApiController@triggerNotify');
Route::post('email/requestcallback','EmailSmsApiController@RequestCallback');
Route::get('requestcallbackcloudagent/{requestcallbackremindercall_id}','EmailSmsApiController@requestCallbackCloudAgent');
// Route::post('email/booktrial','EmailSmsApiController@BookTrial');
// Route::post('email/extrabooktrial','EmailSmsApiController@extraBookTrial');
Route::post('email/finderlead','EmailSmsApiController@FinderLead');
Route::post('email/emailfinder','EmailSmsApiController@EmailSmsFinder');
// Route::post('email/newfinder','EmailSmsApiController@findercreated');
// Route::post('email/finderreview','EmailSmsApiController@ReviewOnfinder');
// Route::post('email/createcommunity','EmailSmsApiController@CreateCommunity');
// Route::post('email/joincommuntiy','EmailSmsApiController@JoinCommunity');
// Route::post('email/interestcommunity','EmailSmsApiController@InterestCommunity');
Route::post('email/commentonblog','EmailSmsApiController@CommentOnBlog');
Route::post('subscribenewsletter','EmailSmsApiController@SubscribeNewsletter');
// Route::post('email/joinevent','EmailSmsApiController@JoinEvent');
// Route::post('email/createevent','EmailSmsApiController@CreateEvent');
Route::post('landing', 'CaptureController@postCapture');
Route::post('fivefitness/customer', 'EmailSmsApiController@fivefitnesscustomer');
Route::post('fivefitness/refundcustomer', 'EmailSmsApiController@refundfivefitnesscustomer');
Route::post('registerme', 'EmailSmsApiController@registerme');
Route::post('landingpage/conversion', 'EmailSmsApiController@landingconversion');
Route::post('landingpage/callback', 'EmailSmsApiController@landingpagecallback');
Route::put('landingpage/callback/{capture_id}', 'EmailSmsApiController@landingpagecallbacksave');
Route::post('landingpage/register', 'EmailSmsApiController@landingpageregister');
Route::post('offeravailed', 'EmailSmsApiController@offeravailed');
Route::post('fitcardbuy', 'EmailSmsApiController@fitcardbuy');
Route::post('not_able_to_find', 'EmailSmsApiController@not_able_to_find');
Route::get('email/testemail','EmailSmsApiController@testemail');


Route::post('/queue/push', function(){
	return Queue::marshal();
});
##############################################################################
/******************** SENDING EMAIL STUFFS SECTION START HERE ********************/




##############################################################################
/******************** CACHE SECTION START HERE *******************************/

Route::get('flushtag/{tag}', 'CacheApiController@flushTag');
Route::get('flushtagkey/{tag}/{key}', 'CacheApiController@flushTagKey');
//Route::get('flushall', 'CacheApiController@flushAll');
Route::get('cachedrop', 'CacheApiController@flushAll');
// Route::get('dropall', 'CacheApiController@flushAll');

##############################################################################
/******************** CACHE SECTION END HERE *******************************/



##############################################################################
/******************** FITMANIA SECTION START HERE *******************************/




Route::get('fitmania/{city?}/{from?}/{size?}', 'FitmaniaController@homeData');
Route::get('fitmaniadod/{city?}/{from?}/{size?}', 'FitmaniaController@getDealOfDay');
Route::get('fitmaniamembership/{city?}/{from?}/{size?}', 'FitmaniaController@getMembership');
Route::get('fitmaniahomepagebanners/{city?}/{type?}/{from?}/{size?}', 'FitmaniaController@getFitmaniaHomepageBanners');
Route::post('searchfitmaniamembership', 'FitmaniaController@serachMembership');
Route::post('searchfitmaniadoddow', 'FitmaniaController@serachDodAndDow');
Route::post('buyfitmaniaoffer', 'FitmaniaController@buyOffer');
Route::post('searchfitmaniafinders', 'FitmaniaController@serachFinders');
// Route::get('fitmaniaservicedetail/{serviceid}/{offerid}', 'FitmaniaController@serviceDetail');
Route::get('maintainactiveflag/{serviceid?}', 'FitmaniaController@maintainActiveFlag');
Route::get('checkfitmaniaorder/{orderid}', 'FitmaniaController@checkFitmaniaOrder');
Route::get('updatecityid/', 'FitmaniaController@updateCityIdFromFinderCityId');
Route::get('checkcouponcode/{code}', 'FitmaniaController@checkCouponcode');
Route::get('checkbuyablevalue/{offerid}', 'FitmaniaController@checkBuyableValue');
Route::get('updateexplorecategoryoffers/{cityid?}', 'FitmaniaController@exploreCategoryOffers');
Route::get('updateexplorelocationclusteroffers/{cityid?}', 'FitmaniaController@exploreLocationClusterOffers');
Route::get('categorycitywisesuccesspage/{city?}/{from?}/{size?}', 'FitmaniaController@categoryCitywiseSuccessPage');
Route::get('fitmaniaresendemails', 'FitmaniaController@resendEmails');
Route::get('fitmaniaresendemailsworngcustomer', 'FitmaniaController@resendEmailsForWorngCustomer');
Route::get('fitmaniaresendemailsworngfinder', 'FitmaniaController@resendEmailsForWorngFinder');
Route::get('email2fitmaniavendors', 'FitmaniaController@emailToFitmaniaVendors');
Route::get('email2personaltrainers', 'OrderController@emailToPersonalTrainers');



######################################################################################################################
/******************************** Special Offer Section *****************************************************/
Route::get('specialoffermembership/{city?}/{offertype?}/{from?}/{size?}', 'SpecialofferController@getMembership');
Route::post('searchspecialoffermembership', 'SpecialofferController@serachMembership');
Route::post('buyfitmaniaoffer', 'SpecialofferController@buyOffer');
Route::post('searchofferfinders', 'SpecialofferController@serachFinders');
Route::get('fitmaniaservicedetail/{serviceid}/{offerid}', 'SpecialofferController@serviceDetail');
Route::get('checkfitmaniaorder/{orderid}', 'SpecialofferController@checkFitmaniaOrder');
Route::get('updatecityid/', 'SpecialofferController@updateCityIdFromFinderCityId');
Route::get('checkcouponcode/{code}', 'SpecialofferController@checkCouponcode');
Route::get('checkbuyablevalue/{offerid}', 'SpecialofferController@checkBuyableValue');
Route::get('updateexplorecategoryoffers/{cityid?}', 'SpecialofferController@exploreCategoryOffers');
Route::get('updateexplorelocationclusteroffers/{cityid?}', 'SpecialofferController@exploreLocationClusterOffers');
Route::get('categorycitywisesuccesspage/{city?}/{from?}/{size?}', 'SpecialofferController@categoryCitywiseSuccessPage');
Route::get('standardofferstext/{type?}', 'SpecialofferController@standardOffersText');

######################################################################################################################
/******************************** End Special Offer Section *****************************************************/

// Route::get('fitmaniahealthytiffin/{city?}/{from?}/{size?}/{category_cluster?}', 'FitmaniaController@getDealOfDayHealthyTiffin');

// Route::get('fitmaniazumba/{city?}/{location_cluster?}', 'FitmaniaController@getDealOfDayZumba');
// Route::get('fitmaniadeals/{startdate?}/{enddate?}/{city?}/{location_cluster?}', 'FitmaniaController@getDealOfDayBetweenDate');

// Route::post('fitmania', 'FitmaniaController@fitmaniaServices');

// Route::post('buyfitmaniaservice', 'FitmaniaController@buyService');
// Route::post('buyfitmaniaservicemembership', 'FitmaniaController@buyServiceMembership');
// Route::post('buyfitmaniahealthytiffin', 'FitmaniaController@buyServiceHealthyTiffin');


// Route::get('resendemails', 'FitmaniaController@resendEmails');
// Route::get('resendfinderemail', 'FitmaniaController@resendFinderEmail');
// Route::get('resendcustomeremail', 'FitmaniaController@resendCustomerEmail');


##############################################################################
/******************** FITMANIA SECTION END HERE *******************************/



##############################################################################
/******************** STATS SECTION START HERE *******************************/

Route::get('stats/booktrial/{day}', 'StatsController@booktrial');
Route::get('stats/signup/{day}', 'StatsController@signUp');
Route::get('stats/orders/{day}', 'StatsController@orders');
Route::get('stats/callback/{day}', 'StatsController@callBack');
Route::get('stats/orderspiechart/{day}', 'StatsController@ordersPieChart');
Route::get('stats/signuppiechart/{day}', 'StatsController@signUpPieChart');
Route::get('stats/review/{day}', 'StatsController@review');
Route::get('stats/smsbalance/{day}', 'StatsController@smsBalance');

##############################################################################
/******************** STATS SECTION END HERE *******************************/

##############################################################################
/******************** OZONETELS SECTION START HERE ***********************/

Route::get('ozonetel/freevendor',  array('as' => 'ozonetel.freevendor','uses' => 'OzonetelsController@freeVendorV1'));
Route::get('ozonetel/paidvendor',  array('as' => 'ozonetel.paidvendor','uses' => 'OzonetelsController@paidVendorV1'));
Route::get('ozonetel/outboundcallsend/{phone_number}',  array('as' => 'ozonetel.outboundCallSend','uses' => 'OzonetelsController@outboundCallSend'));
Route::get('ozonetel/outboundcallrecive/{id}',  array('as' => 'ozonetel.outboundCallRecive','uses' => 'OzonetelsController@outboundCallRecive'));
Route::get('ozonetel/outbound/{id}',  array('as' => 'ozonetel.outbound','uses' => 'OzonetelsController@outbound'));
Route::get('ozonetel/missedcall/sms',  array('as' => 'ozonetel.missedcallsms','uses' => 'OzonetelsController@missedcallSms'));
Route::get('ozonetel/missedcall/smsb',  array('as' => 'ozonetel.missedcallsms','uses' => 'OzonetelsController@missedcallSms'));
Route::get('ozonetel/missedcallsms',  array('as' => 'ozonetel.missedcallsms','uses' => 'OzonetelsController@missedcallSms'));

Route::get('ozonetel/confirmtrial',  array('as' => 'ozonetel.confirmtrial','uses' => 'OzonetelsController@confirmTrial'));
Route::get('ozonetel/canceltrial',  array('as' => 'ozonetel.canceltrial','uses' => 'OzonetelsController@cancelTrial'));
Route::get('ozonetel/rescheduletrial',  array('as' => 'ozonetel.rescheduletrial','uses' => 'OzonetelsController@rescheduleTrial'));
Route::post('callcenter/callback',  array('as' => 'ozonetel.callback','uses' => 'OzonetelsController@callback'));

Route::get('ozonetel/misscallreview/{type}',  array('as' => 'ozonetel.misscallreview','uses' => 'OzonetelsController@misscallReview'));
Route::get('ozonetel/misscallorder/{type}',  array('as' => 'ozonetel.misscallorder','uses' => 'OzonetelsController@misscallOrder'));
Route::get('ozonetel/misscallmanualtrial/{type}',  array('as' => 'ozonetel.misscallmanualtrial','uses' => 'OzonetelsController@misscallManualTrial'));
Route::get('ozonetel/outboundcall/stayontrack/{id}',  array('as' => 'ozonetel.outboundcallstayontrack','uses' => 'OzonetelsController@outboundCallStayOnTrack'));
Route::get('ozonetel/customercalltovendor/missedcall',  array('as' => 'ozonetel.customercalltovendormissedcall','uses' => 'OzonetelsController@customerCallToVendorMissedcall'));


/******************** OZONETELS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** BRAND SECTION START HERE *******************************/

Route::post('branddetail/{slug}/{city}', array('as' => 'brands.branddetail','uses' => 'BrandsController@brandDetail'));

##############################################################################
/******************** BRAND SECTION END HERE *******************************/

##############################################################################
/******************** SECURITY SECTION START HERE *******************************/

Route::group(array('before' => 'jwt'), function() {
	
	//finder info
	Route::get('sfinderdetail/{slug}', array('as' => 'finders.finderdetail','uses' => 'FindersController@finderdetail')); 

	//booktrial
	Route::post('sbooktrial', array('as' => 'finders.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialFree'));
	Route::post('smanualbooktrial', array('as' => 'finders.storemanualbooktrial','uses' => 'SchedulebooktrialsController@manualBookTrial'));
	Route::post('sstorebooktrial', array('as' => 'customer.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialPaid'));
	Route::post('scaptureorderpayment', array('as' => 'customer.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialPaid'));

	//home
	Route::get('shome', 'HomeController@getHomePageData');
	Route::get('shomev2/{city?}', 'HomeController@getHomePageDatav2');
	Route::get('shomev3/{city?}', 'HomeController@getHomePageDatav3');
	Route::get('sgetcollecitonnames/{city?}', 'HomeController@getcollecitonnames');
	Route::get('sgetcollecitonfinders/{city}/{slug}', 'HomeController@getcollecitonfinders');

	//captures
	Route::post('slanding', 'CaptureController@postCapture');
	Route::post('semail/requestcallback','EmailSmsApiController@RequestCallback');
	Route::post('slandingpage/callback', 'EmailSmsApiController@landingpagecallback');

	//order
	Route::post('sgeneratecodorder',  array('as' => 'orders.generatecodorder','uses' => 'OrderController@generateCodOrder'));
	Route::post('sgeneratetmporder',  array('as' => 'orders.generatetmporder','uses' => 'OrderController@generateTmpOrder'));

	//search
	Route::post('sgetrankedfinder', 'RankingSearchController@getRankedFinderResults');
	Route::post('sgetfindercategory', 'RankingController@getFinderCategory');
	Route::post('sgetautosuggestresults', 'GlobalSearchController@getautosuggestresults');
	Route::post('sgetcategoryofferings', 'RankingSearchController@CategoryAmenities');
	Route::post('sgetcategories', 'RankingSearchController@getcategories');
	Route::post('sgetsearchmetadata', 'RankingSearchController@getsearchmetadata');
	Route::post('sgetrankedfinderapp', 'RankingSearchController@getRankedFinderResultsMobile');
	Route::post('skeywordsearchweb', 'GlobalSearchController@keywordSearch');
});

##############################################################################
/******************** CRONS SECTION START HERE ***********************/

Route::post('cron/cronlog',  array('as' => 'cron.cronlog','uses' => 'CronController@cronLog'));
Route::get('cron/monitor/{days}',  array('as' => 'cron.monitor','uses' => 'CronController@monitor'));


/******************** CRONS SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** Campaign SECTION START HERE ***********************/
Route::get('/getcampaigncategories/{campaignid}', 'CampaignsController@getcampaigncategories');
Route::get('/getcampaigntrials/{campaignid}/{email}', 'CampaignsController@getcampaigntrials');
// Route::get('/featuredcampaign/{campaignid}', 'CampaignsController@featuredcampaign');
Route::post('campaignsearch', 'CampaignsController@campaignsearch');
Route::post('campaign/registercustomer', 'CampaignsController@registercustomer');
Route::post('campaign/campaignregistercustomer', 'CampaignsController@campaignregistercustomer');
Route::get('campaign/{city_id}/{campaign_name}', 'CampaignsController@campaignServices');
Route::get('campaign/listbycluster/{campaign_slug}/{city_id}/{cluster_slug}/{campaignby?}',  array('as' => 'campaigns.listbycluster','uses' => 'CampaignsController@listByCluster'));

/******************** Campaign SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** SECURITY SECTION END HERE *******************************/

##################################################################################################
/*******************  GLOBALSEARCH BULK PUSH HERE ************************************************/

Route::get('buildglobalindex', 'GlobalPushController@buildglobalindex');
Route::get('pushcategorylocations', 'GlobalPushController@pushcategorylocations');
Route::get('pushfinders', 'GlobalPushController@pushFinders');
Route::get('pushcategorywithfacilities', 'GlobalPushController@pushcategorywithfacilities');
Route::get('pushcategoryoffering', 'GlobalPushController@pushcategoryoffering');
Route::get('pushcategoryofferinglocation', 'GlobalPushController@pushcategoryofferinglocation');
Route::get('pushcategoryfacilitieslocation', 'GlobalPushController@pushcategoryfacilitieslocation');
Route::get('pushcategorycity', 'GlobalPushController@pushcategorycity');
Route::get('updatelatlon', 'GlobalPushController@updatelatlon');
Route::get('pushallfittnesslocation', 'GlobalPushController@pushallfittnesslocation');
Route::get('getfacebookUTM', 'KYUController@getfacebookUTM');
Route::get('sessionutm', 'KYUController@sessionutm');
Route::get('createkyuusers', 'KYUController@createkyuusers');
Route::get('getunidentifiedusers', 'KYUController@getunidentifiedusers');
Route::get('updatepaymentbooking', 'KYUController@updatepaymentbooking');
Route::post('getglobalsearchkeywordmatrix', 'KYUController@getglobalsearchkeywordmatrix');
Route::post('getglobalsearchclickedmatrix', 'KYUController@getglobalsearchclickedmatrix');
Route::post('getdailyvisitors', 'KYUController@getdailyvisitors');
Route::get('v1/rollingautosuggestindex', 'GlobalPushController@rollingbuildautosuggest');

####################################################################################################
/**********************************Moengage Migration COntroller***********************************/

// Route::get('migratedatatomoenagage', 'MigrationsController@migratedatatomoenagage');


/********************************Moengage Migration Controller*************************************/
####################################################################################################


/******************  GLOBALSEARCH BULK PUSH END HERE************************************************/
#####################################################################################################

##################################################################################################
/*******************  New Search for APP ************************************************/

Route::post('search/getautosuggestresults1', 'GlobalSearchController@newglobalsearch');

/******************  GLOBALSEARCH BULK PUSH END HERE************************************************/
#####################################################################################################

##################################################################################################
/*******************  CleanUP API ************************************************/

Route::get('movepostnatal', 'DebugController@movepostnatal');
Route::get('manualtrialdisable', 'DebugController@manualtrialdisable');

/******************  GLOBALSEARCH BULK PUSH END HERE************************************************/
#####################################################################################################

##################################################################################################
/*******************  Temp API ************************************************/

Route::post('temp/addweb', array('as' => 'temps.addweb','uses' => 'TempsController@addWeb'));
Route::post('temp/add', array('as' => 'temps.add','uses' => 'TempsController@add'));
Route::get('temp/delete/{customer_phone}', array('as' => 'temps.delete','uses' => 'TempsController@delete'));

Route::get('temp/verifynumber/{number}', array('as' => 'temps.verifynumber','uses' => 'TempsController@verifyNumber'));
Route::get('temp/verifyotp/{temp_id}/{otp}/{email?}/{name?}', array('as' => 'temps.verifyotp','uses' => 'TempsController@verifyOtp'));
Route::get('temp/proceedwithoutotp/{temp_id}', array('as' => 'temps.proceedwithoutotp','uses' => 'TempsController@proceedWithoutOtp'));
Route::get('temp/regenerateotp/{temp_id}', array('as' => 'temps.regenerateotp','uses' => 'TempsController@regenerateOtp'));

/**** Version 1 ****/
Route::post('temp/addv1', array('as' => 'temps.add','uses' => 'TempsController@addV1'));
Route::get('temp/verifyotpv1/{temp_id}/{otp}/{email?}/{name?}', array('as' => 'temps.verifyotp','uses' => 'TempsController@verifyOtpV1'));
Route::get('temp/proceedwithoutotpv1/{temp_id}', array('as' => 'temps.proceedwithoutotp','uses' => 'TempsController@proceedWithoutOtpV1'));


/******************  Temp API END HERE************************************************/
#####################################################################################################



##################################################################################################
/*******************  Temp API ************************************************/

Route::get('budgetalgocron', 'FindersController@updateBudgetFromRatecardsToFinder');
/******************  Temp API END HERE************************************************/
#####################################################################################################



##################################################################################################
/*******************  Yoga Day Campaign APIs ************************************************/

Route::post('bookingfromcustomofferorder', 'CustomOfferOrderController@BookingFromCustomOfferOrder');

Route::get('yogaform', 'DebugController@yogaForm');

Route::get('customoffer/getdetails/{id}', array('as' => 'finders.getdetails','uses' => 'CustomOfferOrderController@getdetails'));

Route::post('customoffer/tmporder', array('as' => 'finders.tmporder','uses' => 'CustomOfferOrderController@tmpOrder'));
Route::get('customoffer/captureorder/{order_id}', array('as' => 'finders.captureorder','uses' => 'CustomOfferOrderController@captureOrder'));
Route::get('yogaday/{city_id}',  array('as' => 'campaigns.yogaday','uses' => 'CampaignsController@yogaDay'));

Route::group(array('before' => 'validatetoken'), function() {
	Route::get('customoffer/getorders', array('as' => 'finders.getorders','uses' => 'CustomOfferOrderController@getOrders'));
});
/******************  Yoga Day API END HERE************************************************/
#####################################################################################################

Route::post('seourl', 'GlobalSearchController@seourl');

Route::get('email/opened', 'CustomerController@emailOpened');

Route::post('transaction/capture',array('as' => 'transaction.capture','uses' => 'TransactionController@capture'));
Route::get('transaction/dcos',array('as' => 'transaction.dcos','uses' => 'TransactionController@deleteCommunicationOfSuccess'));

Route::get('getfindercategories/{city?}', 'DebugController@cacheFinderCategoryTags');
Route::post('getfindercategories/{city?}', 'DebugController@cacheFinderCategoryTags');
Route::get('getofferingscategories/{categorySlug?}', 'DebugController@getOfferingsCategoriesSlug');

Route::get('getfinderlocations', 'DebugController@cacheLocations');

Route::get('getsubcategories', 'DebugController@cacheOfferings');

Route::get('getfinders', 'DebugController@cacheFinders');
Route::get('getfinders/{city_slug?}', 'DebugController@cacheFindersFromCity');

Route::post('transaction/pg',array('as' => 'transaction.pg','uses' => 'TransactionController@pg'));
Route::post('transaction/success',array('as' => 'transaction.success','uses' => 'TransactionController@success'));

Route::get('referral', 'CustomerController@generateReferralCode');

Route::post('displayemi','CustomerController@displayEmi');
Route::post('displayemiv1','CustomerController@displayEmiV1');


Route::post('trainer/getavailableslots',array('as' => 'trainer/getavailableslots','uses' => 'TrainerController@getAvailableSlots'));

//Route::group(array('before' => 'validatetoken'), function() {
	Route::post('trainer/bookslot', array('as' => 'trainer.bookslot','uses' => 'TrainerController@bookSlot'));
	Route::post('transaction/update',array('as' => 'transaction.update','uses' => 'TransactionController@update'));
	Route::get('customer/orderdetail/{order_id}',array('as' => 'customer.orderdetail','uses' => 'CustomerController@orderDetail'));
	Route::post('customer/addreview', array('as' => 'finders.addreviewcustomer','uses' => 'FindersController@addReviewCustomer'));
//});

Route::get('getdetailrating',array('as' => 'getdetailrating','uses' => 'FindersController@getDetailRating'));

Route::get('customer/notification/{id}',array('as' => 'customer.notification','uses' => 'CustomerController@notificationTracking'));

Route::get('addwallet',array('as' => 'transaction.addwallet','uses' => 'TransactionController@addWallet'));


Route::get('customerorderdata','DebugController@customer_data');
Route::get('zumbadata','DebugController@zumba_data');
Route::get('syncsharecustomerno','DebugController@syncsharecustomerno');

//reverse migration roures
Route::get('reverse/migration/{colllection}/{id}','MigrationReverseController@byId');
Route::get('fitternitydietvendordetail','FindersController@fitternityDietVedorDetail');
Route::get('orderdemonetisation/{order_id}',array('as' => 'orderdemonetisation','uses' => 'CustomerController@orderDemonetisation'));


//Route::get('trainer/generaterdietplanorderonline/{order_id}',array('as' => 'transaction.generaterdietplanorderonline','uses' => 'TransactionController@generaterDietPlanOrderOnline'));
Route::post('notifylocation',array('as' => 'customer/notifylocation','uses' => 'CustomerController@notifyLocation'));

//Route::get('demonetisation', 'DebugController@demonetisation');

Route::post('customer/getlink', 'CustomerController@getLink');


Route::post('app/installs', 'HomeController@appinstalls');
Route::post('promotional/notification','HomeController@promotionalNotification');
Route::post('customer/feedback', 'CustomerController@feedback');

// Route::get('sendtransactionemails/{withInstant?}','SchedulebooktrialsController@sendTransactionEmails');
// Route::get('sendordermissedemails','TransactionController@sendOrderMissedEmails');

Route::get('termsandconditions/{type}', 'CustomerController@termsAndConditions');

Route::get('addfitcash', 'DebugController@addFitcash');

Route::get('communication/{sender_class}/{transaction_type}/{label}/{id}/{key}', 'CommunicationsController@sendCommunication');

// Route::get('sendManualCommunication/{id}', 'SchedulebooktrialsController@sendManualCommunication');
Route::get('getbrands/{city}/{brand_id}', 'FindersController@getbrands');
Route::get('orderfollowup','DebugController@orderFollowup');
Route::post('belp/signin','HomeController@belpSignin');
Route::post('belp/fitnessquiz','HomeController@belpFitnessQuiz');
Route::post('belp/userdata','HomeController@belpUserData');
Route::post('belp/showcapture','HomeController@showBelpCapture');
Route::post('belp/tracking','HomeController@belpTracking');


Route::get('conditiontest','DebugController@conditionTest');

Route::get('getmealdetailsbyid', 'ServiceController@getMealDetailsById');
Route::get('getlocationbyratecard/{ratecard_id}', 'ServiceController@getLocationByRatecard');


Route::get('fitternitypersonaltrainersdetail','FindersController@fitternityPersonalTrainersDetail');



Route::get('linksentnotsuccess','DebugController@linkSentNotSuccess');

Route::post('crashlog','HomeController@crashLog');

Route::get('payusuccessdate','DebugController@payuSuccessDate');

Route::get('servicefilterreversemigration', 'DebugController@servicefilterreversemigration');

//Route::get('orderstarttommorow', 'DebugController@orderStartTommorow');


// Route::get('masssms','DebugController@massSms');

Route::get('alertmsg/{date}','DebugController@alertmsg');
Route::get('eventupdate','DebugController@eventUpdate');
Route::get('autofollowupunset','DebugController@autoFollowupUnset');
Route::post('customer/promotionalnotification',array('as' => 'customer.promotionalnotification','uses' => 'CustomerController@promotionalNotificationTracking'));
// Route::get('sendManualCommunication/{id}', 'SchedulebooktrialsController@sendManualCommunication');


Route::post('booktrialwithoutreward','SchedulebooktrialsController@booktrialWithoutReward');

Route::get('rewardamountzero', 'DebugController@rewardAmountZero');
Route::get('noratecardorder', 'DebugController@noRatecardOrder');

Route::get('mfp/{city?}','HomeController@mfp');

Route::get('customsms', 'DebugController@customerSmsLinkSentSept');

Route::get('linksentdatasept', 'DebugController@linkSentDataSept');

Route::get('reviewFromProfileSept', 'DebugController@reviewFromProfileSept');

Route::get('chagneStartDateSept', 'DebugController@chagneStartDateSept');

Route::get('renewalSept', 'DebugController@renewalSept');

Route::get('createQrCode', 'DebugController@createQrCode');

Route::get('locatetrial/{code}','SchedulebooktrialsController@locateTrial');

Route::get('locatemembership/{code}','TransactionController@locateMembership');

Route::get('servicemembership/{finder_id}','FindersController@serviceMembership');

Route::get('getnetbankingoptions','HomeController@getNetBankingOptions');

Route::get('kiosk/dashboard/{finder_id}','FindersController@kisokDashboard');

Route::post('career/capture','HomeController@careerCapture');

Route::get('vendorLocation','DebugController@vendorLocation');

Route::get('copyVendorAddress','DebugController@copyVendorAddress');

Route::get('linkSentOct','DebugController@linkSentOct');

Route::get('testDelay','DebugController@testDelay');



Route::get('assitancequestions','HomeController@getAssitanceQuestions');

Route::post('postanswers','HomeController@postAnswers');

Route::post('campaignconversion', 'DebugController@campaignConversion');
Route::post('transaction/summary','SchedulebooktrialsController@transactionSummary');

Route::post('kiosk/vendor/verifyotp','TransactionController@verifyVendorOtpKiosk');

Route::post('customer/capture','CustomerController@customerCapture');

Route::get('customer/getformfields','CustomerController@getFormFields');

Route::get('customerDemonetiseIssue', 'DebugController@customerDemonetiseIssue');

Route::get('locatetrialcommunication/{booktrial_id}','SchedulebooktrialsController@locateTrialCommunication');

Route::get('ratecardmembership/{service_id}','FindersController@ratecardMembership');

Route::post('invitepreregister','CustomerController@invitePreRegister');

// Route::get('sendSms1', 'DebugController@sendSms1');
Route::group(array('before' => 'validatetoken'), function() {
	
	Route::post('walletordercapture', array('as' => 'transaction.walletOrderCapture','uses' => 'TransactionController@walletOrderCapture'));
	
	
	Route::post('codotpsuccess', array('as' => 'transaction.codotpsuccess','uses' => 'TransactionController@codOtpSuccess'));
	
	Route::get('getcodorders','CustomerController@getCodOrders');
	
});
Route::post('walletordersuccess', array('as' => 'transaction.walletOrderSuccess','uses' => 'TransactionController@walletOrderSuccess'));

Route::post('customer/sendvendornumber','CustomerController@sendVendorNumberToCustomer');
Route::get('customerexists/{email}','CustomerController@customerExists');

Route::get('getcrashlogs/{count}','HomeController@getCrashLog');

Route::post('transaction/checkoutsummary','TransactionController@checkoutSummary');

Route::get('transaction/rewardscreen','TransactionController@rewardScreen');

Route::get('getcustommembershipdetails','TransactionController@getCustomMembershipDetails');

Route::get('verifyvendorkioskpin/{pin}','CustomerController@verifyVendorKioskPin');

Route::get('getvendortrainer/{finder_id?}','FindersController@getVendorTrainer');

Route::get('finalMfpData', 'DebugController@finalMfpData');

Route::get('rewardReminderJan','DebugController@rewardReminderJan');

Route::get('servicedetailv1/{finder_slug}/{service_slug}', 'ServiceController@serviceDetailv1');

Route::get('workoutservicecategorys/{city?}/{slotsCountCache?}', array('as' => 'service.workoutservicecategorys','uses' => 'ServiceController@workoutServiceCategorys'));
Route::post('sharegroupid', 'CustomerController@shareGroupId');

Route::get('markRoutedOrders', 'DebugController@markRoutedOrders');

Route::get('cityfitnessoptions', 'HomeController@cityFitnessOptions');

// AMAZON PAY
Route::post('generateamazonchecksum', 'TransactionController@generateAmazonChecksum');
Route::match(array('GET', 'POST'),'verifyamazonchecksum/{id?}', 'TransactionController@verifyAmazonChecksum');
Route::post('generateamazonurl', 'TransactionController@generateAmazonUrl');
Route::post('generatepaytmurl', 'TransactionController@generatePaytmUrl');
Route::match(array('GET', 'POST'),'verifypaytmchecksum', 'TransactionController@verifyPaytmChecksum');
Route::post('verifyamazonchecksumsignature', 'TransactionController@verifyAmazonChecksumSignature');
Route::post('amazonsignandencrypt', 'TransactionController@amazonSignAndEncrypt');
Route::post('amazonsignandencryptop', 'TransactionController@amazonSignAndEncryptForOperation');


Route::get('verifyfitcode/{booktrial_id}/{code}','SchedulebooktrialsController@verifyFitCode');
Route::get('lostfitcode/{booktrial_id}','SchedulebooktrialsController@lostFitCode');
Route::get('getmembershipratecardbyserviceid/{service_id}','FindersController@getMembershipRatecardByServiceId');

Route::post('customer/uploadreceipt','CustomerController@uploadReceipt');

Route::get('ldJson/{booktrial_id}','DebugController@ldJson');

Route::get('customerhome','HomeController@customerHome');
Route::get('groupsData','DebugController@groupsData');
Route::get('rewardClaimData','DebugController@rewardClaimData');
Route::get('sendVendorEmail','DebugController@sendVendorEmail');
Route::get('paypersession','DebugController@paypersession');
Route::get('rewardClaimAvgTime','DebugController@rewardClaimAvgTime');

Route::get('gettermsandcondition','FindersController@getTermsAndCondition');
Route::get('workoutSession','DebugController@workoutSession');
Route::get('cityWise','DebugController@cityWise');
Route::get('brandlist','BrandsController@brandlist');

Route::get('getServiceData', 'TransactionController@getServiceData');

Route::get('sessionstatuscapture/{status}/{booktrial_id}', 'SchedulebooktrialsController@sessionStatusCapture');

Route::get('notificationdatabytrialid/{booktrial_id}/{label}', 'CustomerController@notificationDataByTrialId');
Route::get('getcapturedata/{trial_id}', 'TransactionController@getCaptureData');
Route::get('scheduleManualCommunication/{booktrial_id}', 'SchedulebooktrialsController@scheduleManualCommunication');
Route::get('streakscreendata', 'CustomerController@streakScreenData');
Route::get('bulkInsertSaavn','DebugController@bulkInsertSaavn');

Route::get('deleteCommunicationSidekiq','DebugController@deleteCommunicationSidekiq');

Route::get('updateSubscriptionCode','DebugController@updateSubscriptionCode');
Route::get('addvendorstripedata','DebugController@addvendorstripedata');
Route::get('workoutRatecardReverseMigrate','MigrationReverseController@workoutRatecardReverseMigrate');
Route::get('updatetrialstatus/{_id}/{source}/{action}/{confirm?}','SchedulebooktrialsController@updatetrialstatus');

Route::get('loginoptions','CustomerController@loginOptions');
Route::get('trialWorkout','DebugController@trialWorkout');
Route::get('eventfitex','DebugController@eventfitex');

Route::get('checkemail/{email}/{phone}','HomeController@checkemail');

Route::post('setdefaultaccount','CustomerController@setDefaultAccount');


Route::get('promoNotification','DebugController@promoNotification');
Route::post('getPageViewsForVendors','FindersController@getPageViewsForVendors');

Route::get('PayPerSessionWallet','DebugController@PayPerSessionWallet');
Route::post('autoregister','CustomerController@autoRegister');


Route::get('manualTrialCommunication','SchedulebooktrialsController@manualTrialCommunication');

Route::get('getreferralscreendata','CustomerController@getReferralScreenData');
Route::get('addWallet','DebugController@addWallet');
Route::get('tagReviews','DebugController@tagReviews');

Route::get('getbrandvendors/{brand_id}/{city_id}', array('as' => 'finders.getbrandvendors','uses' => 'FindersController@getBrandVendors'));
Route::get('addProducts','DebugController@addProducts');
Route::get('productshome','HomeController@getProductsHome');
Route::get('productdetail/{ratecard_id}/{product_id}','HomeController@getProductDetail');
Route::get('catproducts/{productcategory_id}','HomeController@getCategoryBasedProducts');


Route::get('addproducttocart/{ratecard_id}/{quantity}','HomeController@addProductToCart');
Route::post('addproductstocart','HomeController@addProductsToCart');

Route::post('transaction/capture/product','TransactionController@productCapture');
Route::post('productscats','HomeController@productsCats');



Route::get('productSpecifications','DebugController@productSpecifications');
Route::get('addPriceToProduct','DebugController@addPriceToProduct');
Route::get('addRatecards','DebugController@addRatecards');
Route::get('updateProductHomePage','DebugController@updateProductHomePage');

Route::get('cartsummary','HomeController@getFinalCartSummary');

Route::get('customeraddress','HomeController@getCustomerAddress');
Route::post('customeraddress','HomeController@setCustomerAddress');
Route::get('sendvendorotpproducts/{order_id}','TransactionController@sendVendorOTPProducts');

Route::get('updateCouponUsed','DebugController@updateCouponUsed');

Route::get('lostFitcode','DebugController@lostFitcode');

Route::post('getcustomercarddetails','CustomerController@getCustomerCardDetails');

Route::get('updateRatecardSlots','DebugController@updateRatecardSlots');

Route::get('updateratecardslotsbyid/{order_id}','TransactionController@updateRatecardSlotsByOrderId');

Route::get('customer/skipreview/{booktrial_id}','SchedulebooktrialsController@skipreview');

Route::get('addpicturestoratingparams','DebugController@addPicturesToRatingParams');

Route::get('finderreviewdata/{finder_id}','FindersController@finderReviewData');

Route::get('toto/{vendorservice_id}', 'MigrationReverseController@tot');
Route::get('listvalidcoupons','HomeController@listValidCoupons');
Route::post('getunmarkedattendance','CustomerController@getCustomerUnmarkedAttendance');
Route::post('markcustomerattendance','CustomerController@markCustomerAttendance');



Route::get('brandlistcity/{city}','BrandsController@brandlistcity');

Route::post('inviteforevent','EventsController@inviteForEvent');

// Music Run
// Route::get('eventorderdetails/{orderid}','EventsController@getOrderDetails');
// Route::get('geteventorders/{event_slug}','EventsController@getOrderList');


Route::get('checkexistinguser/mobikwik/{cell}','PaymentGatewayController@checkExistingUserMobikwik');
Route::post('generateotp/{type}','PaymentGatewayController@generateOtp');\
Route::post('generatetoken/{type}','PaymentGatewayController@generateToken');
Route::post('regeneratetoken/mobikwik','PaymentGatewayController@regenerateTokenMobikwik');
Route::post('createuser/mobikwik','PaymentGatewayController@createUserMobikwik');
Route::post('checkbalance/{type}','PaymentGatewayController@checkBalance');
Route::post('addmoney/mobikwik','PaymentGatewayController@addMoneyMobikwik');
Route::post('debitmoney/mobikwik','PaymentGatewayController@debitMoneyMobikwik');
Route::match(array('GET', 'POST'),'verifyaddmoney/mobikwik', 'PaymentGatewayController@verifyAddMoneyMobikwik');
Route::post('checkstatus/mobikwik','PaymentGatewayController@checkStatusMobikwik');
Route::get('verifypayment/{status}','PaymentGatewayController@verifyPayment');
Route::get('gettoken','PaymentGatewayController@firstCallPaypal');
Route::post('createpaymentpaypal','PaymentGatewayController@createPaymentPaypal');
Route::get('successRoutePaypal','PaymentGatewayController@successExecutePaymentPaypal');
Route::get('cancleRoutePaypal','PaymentGatewayController@canclePaymentPaypal');
##################################################################################################
/*******************  Loyalty API ************************************************/

Route::get('loyaltyprofile', 'CustomerController@loyaltyProfile');
Route::get("objectToArrayVoucher", "DebugController@objectToArrayVoucher");

Route::post('registerloyalty', 'CustomerController@registerLoyalty');

Route::group(array('before' => 'validatetoken'), function() {

	Route::get('listcheckins', 'CustomerController@listCheckins');

	Route::get('claimexternalcoupon/{_id}/{customer_id?}/{key?}', 'CustomerController@claimExternalCoupon');
	Route::get('claimexternalcouponrewards/{_id}', 'CustomerController@claimExternalCouponRewards');

	Route::get('markcheckin/{finder_id}', 'CustomerController@markCheckin');

	Route::post('uploadreceiptloyalty', 'CustomerController@uploadReceiptLoyalty');
	Route::post('onepasscustomerupdate', 'CustomerController@onePassCustomerUpdate');

});

/******************  Loyalty API END HERE************************************************/
#####################################################################################################
Route::post('addafriendforbooking','CustomerController@addafriendforbooking');
Route::post('editfriendforbooking','CustomerController@editfriendforbooking');
Route::get('getbookingfriends','CustomerController@getallBookingfriends');
Route::post('webcheckout','TransactionController@webcheckout');
Route::post('addcustomersfortrial','SchedulebooktrialsController@addCustomersForTrial');
Route::get('getcouponpackages','HomeController@getCouponPackages');
// Route::get('reviewParamsPicturesStageToLive','DebugController@reviewParamsPicturesStageToLive');


Route::get('integratedvendorlist/{city_id}','FindersController@integratedVendorList');
Route::get('assignRenewal','DebugController@assignRenewal');
Route::group(array('before' => 'validatetoken'), function() {

    Route::post('updatefreshchatid','CustomerController@updateFreshchatId');

});

Route::post('apicrashlogs','HomeController@apicrashlogs');
Route::get('addvoucherimages','DebugController@addvoucherimages');

Route::post('getumarkedmfpattendance','TransactionController@getUmarkedMfpAttendance');
Route::post('markmfpattendance','TransactionController@markMfpAttendance');


Route::post('sendcommvendorthirdparty','ThirdPartyController@sendClockUserDaySMS');
Route::get('customer/getsessionpacks/{offset?}/{limit?}','CustomerController@getSessionPacks');
Route::post('addServiceMultipleSessionPack','DebugController@addServiceMultipleSessionPack');

Route::get('orderOldSuccessDateToNew', 'DebugController@orderOldSuccessDateToNew');

Route::get('test', 'CustomerController@test');
Route::get('addTypeOfPpsVendor', 'DebugController@addTypeOfPpsVendor');

Route::post('generatefreesp', 'TransactionController@generateFreeSP');
Route::get('paidAndFreeFItcash', 'DebugController@paidAndFreeFItcash');

// Route::get('assignGold', 'DebugController@assignGoldLoyalty');
// Route::get('assignloyaltygen', 'DebugController@assignLoyalty');
Route::post('registershedeat', 'EmailSmsApiController@registerShedEat');
Route::post('notifyshedeat', 'EmailSmsApiController@notifyShedEat');
Route::get('testoffers', 'FindersController@testOffer');

// Route::get('testmultifit', 'FindersController@testMultifit');
// Route::get('testmm', 'DebugController@testmailMsg');
// Route::post('loyaltyAppropriation', 'CustomerController@loyaltyAppropriation');
Route::match(array('PUT', 'POST'), 'customer/loyaltyAppropriation', 'CustomerController@loyaltyAppropriation');
Route::get('home/getLoyaltyAppropriationConsentMsg/{customer_id}/{order_id}','HomeController@getLoyaltyAppropriationConsentMsg');
Route::get('testSpinResult/{number}','EmailSmsApiController@testSpinResult');
Route::post('spinthewheelreg','EmailSmsApiController@spinTheWheelReg');
Route::match(array('PUT', 'POST'), 'customer/remaincurrentloyalty', 'CustomerController@fitSquadUpgradeRemainLoyalty');
Route::get('fitpassComparison', 'DebugController@fitpassComparison');
Route::get('fixCustomerQuantity', 'DebugController@fixCustomerQuantity');
Route::get('fixFinanceCustomerQuantity', 'DebugController@fixFinanceCustomerQuantity');
Route::get('fixAmountCustomer', 'DebugController@fixAmountCustomer');
Route::get('goldsFitcashMessage', 'DebugController@goldsFitcashMessage');
Route::get('getBrandFinderList', 'DebugController@getBrandFinderList');
Route::post('fitnessforce/orderdetails', 'DebugController@getFFOrderDetails');

// Route::post('passcapture', 'TransactionController@classPassCapture');
Route::get('listpass/{pass_type?}', 'PassController@listPasses');
Route::post('razorpay/subscribe', 'RazorpayController@createSubscription');
Route::post('razorpay/storepaymentdetails', 'RazorpayController@storePaymentDetails');
Route::post('passsuccess', 'PassController@passSuccess');
Route::get('orderpasshistory',  array('as' => 'customer.orderpasshistory','uses' => 'PassController@orderPassHistory'));
Route::get('passtermscondition', 'PassController@passTermsAndCondition');
Route::get('passfaq', 'PassController@passFrequentAskedQuestion');
Route::post('razorpaywebhooks', 'RazorpayController@razorpayWebhooks');
Route::any('subventiondataupdate/', array('as' => 'transaction.subventionDataUpdate','uses' => 'TransactionController@subventionDataUpdate'));

Route::get('brandwebsite/home/{brand_id}', 'BrandsController@getBrandWebsiteHome');
Route::get('brandwebsite/aboutus/{brand_id}', 'BrandsController@getBrandWebsiteAboutUs');
Route::get('brandwebsite/programs/{brand_id}', 'BrandsController@getBrandWebsitePrograms');
Route::get('brandwebsite/hiit/{brand_id}', 'BrandsController@getBrandWebsiteHiit');
Route::get('brandwebsite/contactus/{brand_id}', 'BrandsController@getBrandWebsiteContactUs');
Route::get('brandwebsite/ownfranchise/{brand_id}', 'BrandsController@getBrandWebsiteOwnFranchise');
Route::get('multifitDataMigration', 'DebugController@multifitDataMigration');

Route::post('reliance/updateAppStepCount', 'RelianceController@updateAppStepCount');
Route::post('reliance/updateservicestepcount', 'RelianceController@updateServiceStepCount');
Route::get('reliance/getLeaderboard', 'RelianceController@getLeaderboard');
Route::post('reliance/leaderboard', 'RelianceController@getLeaderboard');
Route::post('customer/storedob', 'RelianceController@storeDob');
Route::post('customer/enablereliancecampaign', 'CustomerController@enableRelianceCampaign');

Route::get('nearbyvendors', 'CustomerController@getNearbyVendors');
Route::get('migrateStepsToFirestore', 'DebugController@migrateStepsToFirestore');
Route::get('testOnePassUser', 'DebugController@testOnePassUser');
Route::get('homepostpasspurchase', 'PassController@homePostPassPurchaseData');
Route::get('dynamicOnepassEMailSms', 'DebugController@dynamicOnepassEMailSms');
Route::get('addFlagClasspassAvalible', 'DebugController@addFlagClasspassAvalible');
Route::get('localpassratecard', 'PassController@localPassRatecards');
Route::post('unlocksession/{trial_id}','SchedulebooktrialsController@unlockSession');
Route::post('passtab', 'PassController@passTab');
Route::get('passCashback', 'DebugController@passCashback');

Route::get('healthobject', 'RelianceController@buildHealthObjectStructure');
Route::post('tpcancelsession', 'SchedulebooktrialsController@tpcancelsession');
Route::post('decryptqrcode', 'ThirdPartyController@decryptQRCode');
Route::get('renewalOnepass', 'DebugController@renewalOnepass');
Route::get('removePassOrders/{email}', 'DebugController@removePassOrders');

// Route::get('getcampaigndata', 'HomeController@getCampaignData');