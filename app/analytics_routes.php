<?php



Route::get('analytics/reviews', array('as' => 'analytics.reviews','uses' => 'AnalyticsController@reviews'));
Route::get('analytics/reviewsdiffday', array('as' => 'analytics.reviewsdiffday','uses' => 'AnalyticsController@reviewsDiffDay'));
Route::get('analytics/reviewsdiffmonth', array('as' => 'analytics.reviewsdiffmonth','uses' => 'AnalyticsController@reviewsDiffMonth'));
Route::get('analytics/trialsdiffday', array('as' => 'analytics.trialsdiffday','uses' => 'AnalyticsController@trialsDiffDay'));
Route::get('analytics/trialsdiffmonth', array('as' => 'analytics.trialsdiffmonth','uses' => 'AnalyticsController@trialsDiffMonth'));
Route::get('analytics/ozonetelcallsdiffday', array('as' => 'analytics.ozonetelcallsdiffday','uses' => 'AnalyticsController@ozonetelCallsDiffDay'));
Route::get('analytics/ozonetelcallsdiffmonth', array('as' => 'analytics.ozonetelcallsdiffmonth','uses' => 'AnalyticsController@ozonetelCallsDiffMonth'));
Route::get('analytics/vendor', array('as' => 'analytics.vendor','uses' => 'AnalyticsController@vendor'));