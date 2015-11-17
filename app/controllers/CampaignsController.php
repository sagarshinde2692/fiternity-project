<?php

class CampaignsController extends \BaseController {

	public function __construct() {
		parent::__construct();		
	}

	/**
	 * Display a listing of homepages
	 *
	 * @return Response
	 */
	public function getcampaigncategories($id){
		$id = (int) $id;
		$camp = Campaign::find($id);
		$campaign_categories = array_map('intval', explode(",", $camp['campaign_categories'] ));
		$categories = Findercategorytag::active()->whereIn('_id', $campaign_categories)->remember(Config::get('app.cachetime'))->get(array('_id','slug','name'))->toArray();
		$location = Location::active()->where('cities', $camp['city_id'])->remember(Config::get('app.cachetime'))->get(array('_id','slug','name'))->toArray();
		$camp = Campaign::select('feature_finders','featured_finders')->find($id);
		$campaign = array('category' => $categories, 'location'=>$location,'campaign' =>$camp);
		return $campaign;
		}

	public function campaignsearch(){
		$from    =         Input::json()->get('from') ? Input::json()->get('from') : 0;
        $size    =         Input::json()->get('size') ? Input::json()->get('size') : 10;
        $campaign  =         intval(Input::json()->get('campaign'));
        $category     =         Input::json()->get('category') ? Input::json()->get('category') : '';
        $location     =         Input::json()->get('location') ? Input::json()->get('location') : '';
        $offerings     =         Input::json()->get('offerings') ? Input::json()->get('offerings') : '';
        $camp = Campaign::remember(Config::get('app.cachetime'))->find($campaign);
        $campfinders = array_map('intval', explode(",", $camp['campaign_finders'] ));
        $query = Finder::active()->whereIn('_id',$campfinders);
        if($category != ''){
        	$category = array_map('intval', explode(",", $category ));
        	$query = $query->whereIn('categorytags',$category);
        }
        if($location != ''){
        	$location = array_map('intval', explode(",", $location ));
        	$query = $query->whereIn('locationtags',$location);
        }
        if($offerings != ''){
        	$offerings = array_map('intval', explode(",", $offerings ));
        	$query = $query->whereIn('offerings',$offerings);
        }
        $data = [$from,$size,$campaign,$category,$location];
        $finders = $query->with('location')->get();
		return Response::json($finders,200);
	}
	// public function featuredcampaign($id){
 //        $id = (int) $id;
	// 	$camp = Campaign::select('feature_finders','featured_finders')->find($id);
	// 	return Response::json($camp,200);
	// }
}