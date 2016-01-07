<?php

$monolog = Log::getMonolog();
$syslog = new \Monolog\Handler\SyslogHandler('fitapi');
$formatter = new \Monolog\Formatter\LineFormatter('%channel%.%level_name%: %message% %extra%');
$syslog->setFormatter($formatter);
$monolog->pushHandler($syslog);

App::error(function(Illuminate\Database\Eloquent\ModelNotFoundException $e){
	return Response::json('not found',404);
});


require __DIR__.'/debug_routes.php';


 // $queries = DB::getQueryLog();
 // var_dump($queries);

##############################################################################
/******************** HOME SECTION START HERE ***********************/

Route::get('/home', 'HomeController@getHomePageData');
Route::get('/homev2/{city?}', 'HomeController@getHomePageDatav2');
Route::get('/homev3/{city?}', 'HomeController@getHomePageDatav3');
Route::get('footer/{city?}', 'HomeController@getFooterByCity');
Route::get('/zumbadiscover', 'HomeController@zumbadiscover');
Route::get('/fitcardpage1finders', 'HomeController@fitcardpagefinders');

Route::get('/specialoffers_finder', 'HomeController@specialoffers_finder');
Route::get('/yfc_finders', 'HomeController@yfc_finders');
Route::get('landingzumba', 'HomeController@landingzumba');

Route::get('/fitcardfinders', 'HomeController@fitcardfinders');
Route::post('/fitcardfindersv1', 'HomeController@fitcardfindersV1');

Route::get('getcollecitonnames/{city?}', 'HomeController@getcollecitonnames');
Route::get('getcollecitonfinders/{city}/{slug}', 'HomeController@getcollecitonfinders');
Route::get('getlocations/{city?}', 'HomeController@getCityLocation');
Route::get('getcategories/{city?}', 'HomeController@getCityCategorys');
Route::get('getcities', 'HomeController@getCities');

Route::get('getlandingpagefinders/{cityid}/{landingpageid}/{locationclusterid?}', 'HomeController@getLandingPageFinders');

Route::get('offers/{city?}/{from?}/{size?}', 'HomeController@getOffers');
Route::get('offertabs/{city?}', 'HomeController@getOffersTabs');
Route::get('offertabsoffers/{city}/{captionslug}/{slug}', 'HomeController@getOffersTabsOffers');
Route::get('categorytagofferings/{city?}', 'HomeController@getCategorytagsOfferings');


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
Route::post('customerforgotpasswordemail', array('as' => 'customer.customerforgotpasswordemail','uses' => 'CustomerController@forgotPasswordEmail'));
Route::post('customerforgotpassword', array('as' => 'customer.customerforgotpassword','uses' => 'CustomerController@forgotPassword'));
Route::post('customerforgotpasswordemailapp', array('as' => 'customer.customerforgotpasswordemailapp','uses' => 'CustomerController@forgotPasswordEmailApp'));
Route::post('customervalidateotp', array('as' => 'customer.customervalidateotp','uses' => 'CustomerController@validateOtp'));


Route::get('autobooktrials/{customeremail}',  array('as' => 'customer.autobooktrials','uses' => 'CustomerController@getAutoBookTrials'));
Route::get('reviews/{customerid}/{from?}/{size?}',  array('as' => 'customer.reviews','uses' => 'CustomerController@reviewListing'));
Route::get('orderhistory/{customeremail}/{from?}/{size?}',  array('as' => 'customer.orderhistory','uses' => 'CustomerController@orderHistory'));
Route::get('bookmarks/{customerid}',  array('as' => 'customer.bookmarks','uses' => 'CustomerController@getBookmarks'));
Route::get('updatebookmarks/{customerid}/{finderid}/{remove?}',  array('as' => 'customer.updatebookmarks','uses' => 'CustomerController@updateBookmarks'));
Route::get('customerdetail/{customerid}',  array('as' => 'customer.customerdetail','uses' => 'CustomerController@customerDetail'));

Route::group(array('before' => 'validatetoken'), function() {

	Route::get('validatetoken', array('as' => 'customer.validatetoken','uses' => 'CustomerController@validateToken'));
	Route::get('customerlogout', array('as' => 'customer.validatetokencustomerlogout','uses' => 'CustomerController@customerLogout'));

	Route::post('customer/resetpassword', array('as' => 'customer.customerresetpassword','uses' => 'CustomerController@resetPassword'));
	Route::post('customer/update', array('as' => 'customer.customerupdate','uses' => 'CustomerController@customerUpdate'));
	Route::get('customer/getalltrials',  array('as' => 'customer.getalltrials','uses' => 'CustomerController@getAllTrials'));
	Route::get('customer/getallreviews/{offset?}/{limit?}',  array('as' => 'customer.getallreviews','uses' => 'CustomerController@getAllReviews'));
	Route::get('customer/getallorders/{offset?}/{limit?}',  array('as' => 'customer.getallorders','uses' => 'CustomerController@getAllOrders'));
	Route::get('customer/getallbookmarks',  array('as' => 'customer.getallbookmarks','uses' => 'CustomerController@getAllBookmarks'));
	Route::get('customer/editbookmarks/{finder_id}/{remove?}',  array('as' => 'customer.editbookmarks','uses' => 'CustomerController@editBookmarks'));
	Route::get('getcustomerdetail',  array('as' => 'customer.getcustomerdetail','uses' => 'CustomerController@getCustomerDetail'));

});

/******************** CUSTOMERS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** ORDERS SECTION START HERE ***********************/

Route::get('orderdetail/{orderid}',  array('as' => 'orders.orderdetail','uses' => 'OrderController@getOrderDetail'));

Route::post('generatecodorder',  array('as' => 'orders.generatecodorder','uses' => 'OrderController@generateCodOrder'));
Route::post('generatetmporder',  array('as' => 'orders.generatetmporder','uses' => 'OrderController@generateTmpOrder'));
Route::post('capturepayment',  array('as' => 'order.buymembership','uses' => 'OrderController@captureOrderStatus'));
Route::post('captureorderstatus',  array('as' => 'orders.captureorderstatus','uses' => 'OrderController@captureOrderStatus'));
Route::post('capturefailsorders',  array('as' => 'orders.capturefailsorders','uses' => 'OrderController@captureFailOrders'));


Route::post('buyarsenalmembership',  array('as' => 'orders.buyarsenalmembership','uses' => 'OrderController@buyArsenalMembership'));
Route::post('buylandingpagepurchase',  array('as' => 'orders.buylandingpagepurchase','uses' => 'OrderController@buyLandingpagePurchase'));





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
Route::get('createindex/{index?}', array('as' => 'elasticsearch.createindex','uses' => 'ElasticsearchController@createIndex'));
Route::get('deleteindex/{index?}', array('as' => 'elasticsearch.deleteindex','uses' => 'ElasticsearchController@deleteIndex'));
Route::get('managesetttings/{index?}', array('as' => 'elasticsearch.managesetttings','uses' => 'ElasticsearchController@manageSetttings'));
Route::get('createtype/{type}', array('as' => 'elasticsearch.createtype','uses' => 'ElasticsearchController@createType'));
Route::get('checkmapping/{type}', array('as' => 'elasticsearch.checkmapping','uses' => 'ElasticsearchController@checkMapping'));
Route::get('deletetype/{type}', array('as' => 'elasticsearch.deletetype','uses' => 'ElasticsearchController@deleteType'));		
Route::get('mongo2elastic/{type?}', array('as' => 'elasticsearch.mongo2elastic','uses' => 'ElasticsearchController@mongo2Elastic'));
Route::get('indexautosuggestdata/{type?}', array('as' => 'elasticsearch.indexautosuggestdata','uses' => 'ElasticsearchController@indexautosuggestdata'));
Route::get('indexrankmongo2elastic', array('as' => 'elasticsearch.indexrankmongo2elastic','uses' => 'RankingController@IndexRankMongo2Elastic'));
Route::get('manageautosuggestsetttings', array('as' => 'elasticsearch.manageautosuggestsetttings','uses' => 'ElasticsearchController@manageAutoSuggestSetttings'));
Route::get('embedtrials', array('as' => 'elasticsearch.embedtrials','uses' => 'RankingController@embedTrialsBooked'));
Route::get('indexservicerankmongo2elastic', array('as' => 'elasticsearch.indexservicerankmongo2elastic','uses' => 'ServiceRankingController@IndexServiceRankMongo2Elastic'));

/******************** ELASTICSEARH SECTION END HERE  ********************/
##############################################################################

########################################################################################
/************************KYU SECTION START HERE****************************************/
Route::post('pushkyuevent', 'KYUController@pushkyuevent');
Route::get('getvendorview/{vendor_slug}','KYUController@getvendorviewcount');
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


/******************** SEARCH SECTION END HERE ********************/
##############################################################################




##############################################################################
/******************** SERVICE SECTION START HERE ********************/

Route::get('updateserviceslug/', array('as' => 'service.updateserviceslug','uses' => 'ServiceController@updateSlug'));
Route::get('servicedetail/{id}', array('as' => 'service.servicedetail','uses' => 'ServiceController@serviceDetail'));
Route::get('servicecategorys', array('as' => 'service.servicecategorys','uses' => 'ServiceController@getServiceCategorys'));
Route::get('servicemarketv1/{city?}', array('as' => 'service.servicemarket','uses' => 'ServiceController@getServiceHomePageDataV1'));
Route::get('servicemarketfooterv1/{city?}', array('as' => 'service.servicemarketfooter','uses' => 'ServiceController@getFooterByCityV1'));



/******************** SERVICE SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** SCHEDULE BOOK TRIAL SECTION START HERE ***********************/
Route::get('getschedulebooktrial/{finderid?}/{date?}', array('as' => 'finders.getschedulebooktrial','uses' => 'SchedulebooktrialsController@getScheduleBookTrial'));
Route::get('booktrial/{finderid?}/{date?}', array('as' => 'finders.getbooktrial','uses' => 'SchedulebooktrialsController@getBookTrial'));
Route::post('booktrial', array('as' => 'finders.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialFree'));
Route::post('updatebooktrial', array('as' => 'finders.updatebooktrial','uses' => 'SchedulebooktrialsController@updateBookTrial'));
Route::post('manualbooktrial', array('as' => 'finders.storemanualbooktrial','uses' => 'SchedulebooktrialsController@manualBookTrial'));
Route::post('manual2ndbooktrial', array('as' => 'finders.storemanual2ndbooktrial','uses' => 'SchedulebooktrialsController@manual2ndBookTrial'));
Route::post('storebooktrial', array('as' => 'customer.storebooktrial','uses' => 'SchedulebooktrialsController@bookTrialPaid'));
Route::post('rescheduledbooktrial', array('as' => 'customer.rescheduledbooktrial','uses' => 'SchedulebooktrialsController@rescheduledBookTrial'));

Route::get('gettrialschedule/{finderid}/{date}', array('as' => 'services.gettrialschedule', 'uses' => 'SchedulebooktrialsController@getTrialSchedule'));
Route::get('getworkoutsessionschedule/{finderid}/{date}', array('as' => 'services.getworkoutsessionschedule', 'uses' => 'SchedulebooktrialsController@getWorkoutSessionSchedule'));
Route::get('getserviceschedule/{serviceid}/{date?}/{noofdays?}', array('as' => 'services.getserviceschedule','uses' => 'SchedulebooktrialsController@getServiceSchedule'));
// Route::get('booktrialff', array('as' => 'schedulebooktrials.booktrialff','uses' => 'SchedulebooktrialsController@bookTrialFintnessForce'));
Route::get('updateappointmentstatus', array('as' => 'customer.updateappointmentstatus','uses' => 'SchedulebooktrialsController@updateAppointmentStatus'));

Route::group(array('before' => 'validatetoken'), function() {

	Route::get('booktrials/cancel/{trialid}', array('as' => 'trial.cancel', 'uses' => 'SchedulebooktrialsController@cancel'));
	Route::post('booktrials/reschedule', array('as' => 'customer.rescheduledbooktrial','uses' => 'SchedulebooktrialsController@rescheduledBookTrial'));

});


/******************** SCHEDULE BOOK TRIAL SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** SENDING EMAIL STUFFS SECTION START HERE ********************/
Route::post('notify/{notifytype}','EmailSmsApiController@triggerNotify');
Route::post('email/requestcallback','EmailSmsApiController@RequestCallback');
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
Route::get('flushall', 'CacheApiController@flushAll');

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
Route::get('fitmaniaservicedetail/{serviceid}/{offerid}', 'FitmaniaController@serviceDetail');
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

Route::get('ozonetel/freevendor',  array('as' => 'ozonetel.freevendor','uses' => 'OzonetelsController@freeVendor'));
Route::get('ozonetel/paidvendor',  array('as' => 'ozonetel.paidvendor','uses' => 'OzonetelsController@paidVendor'));
Route::get('ozonetel/outboundcallsend/{phone_number}',  array('as' => 'ozonetel.outboundCallSend','uses' => 'OzonetelsController@outboundCallSend'));
Route::get('ozonetel/outboundcallrecive/{id}',  array('as' => 'ozonetel.outboundCallRecive','uses' => 'OzonetelsController@outboundCallRecive'));
Route::get('ozonetel/outbound/{id}',  array('as' => 'ozonetel.outbound','uses' => 'OzonetelsController@outbound'));
Route::get('ozonetel/missedcall/sms',  array('as' => 'ozonetel.sms','uses' => 'OzonetelsController@sms'));

/******************** OZONETELS SECTION END HERE ********************/
##############################################################################



##############################################################################
/******************** BRAND SECTION START HERE *******************************/

Route::get('branddetail/{slug}', array('as' => 'brands.branddetail','uses' => 'BrandsController@brandDetail'));

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

/******************  GLOBALSEARCH BULK PUSH END HERE************************************************/
#####################################################################################################