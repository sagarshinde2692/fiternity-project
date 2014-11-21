<?php


App::error(function(Illuminate\Database\Eloquent\ModelNotFoundException $e){
	return Response::json('not found',404);
});



##############################################################################
/******************** DEBUG SECTION START HERE /********************/
Route::get('/', function() { return "laravel 4.2 goes here....";});
Route::get('/test', function() { return "laravel 4.2 goes here....";});
/******************** DEBUG SECTION END HERE ********************/
##############################################################################

Route::get('/home', 'HomeApiController@getHomePageData');

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
Route::get('finderdetail/{slug}', array('as' => 'finders.finderdetail','uses' => 'FindersController@finderdetail'));

/******************** FINDERS SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** BLOGS SECTION START HERE ********************/
Route::get('/blogs/{limit}/{offset}', 'BlogsController@getBlogs');
Route::get('blogdetail/{slug}', array('as' => 'blogs.blogdetail','uses' => 'BlogsController@blogdetail'));
Route::get('/blogs/{cat}', 'BlogsController@getCategoryBLogs');
Route::get('/updateblogdate', 'BlogsController@updateblogdate');


/******************** BLOGS SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** SEARCH SECTION START HERE ********************/
Route::post('/search', 'SearchApiController@getGlobal');
Route::post('/search/finders', 'SearchApiController@getFinders');
Route::post('/findersearch', 'SearchApiController@getFindersv2');

/******************** SEARCH SECTION END HERE ********************/
##############################################################################


##############################################################################
/******************** SENDING EMAIL STUFFS SECTION START HERE ********************/
Route::post('/email/requestcallback','EmailSmsApiController@RequestCallback');
Route::post('/email/booktrial','EmailSmsApiController@BookTrail');
Route::post('/email/finderlead','EmailSmsApiController@FinderLead');
Route::post('/email/emailfinder','EmailSmsApiController@EmailSmsFinder');
Route::post('/email/newfinder','EmailSmsApiController@findercreated');
Route::post('/email/finderreview','EmailSmsApiController@ReviewOnfinder');
Route::post('/email/createcommunity','EmailSmsApiController@CreateCommunity');
Route::post('/email/joincommuntiy','EmailSmsApiController@JoinCommunity');
Route::post('/email/interestcommunity','EmailSmsApiController@InterestCommunity');
Route::post('/email/commentonblog','EmailSmsApiController@CommentOnBlog');
Route::post('/subscribenewsletter','EmailSmsApiController@SubscribeNewsletter');
Route::post('/email/joinevent','EmailSmsApiController@JoinEvent');
Route::post('/email/createevent','EmailSmsApiController@CreateEvent');
Route::post('/landing', 'CaptureApiController@postCapture');


##############################################################################
/******************** SENDING EMAIL STUFFS SECTION START HERE ********************/