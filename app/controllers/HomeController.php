<?php

//use Moa\API\Provider\ProviderInterface;

class HomeController extends BaseController {

	public function __construct() {
     	parent::__construct();	
    }

	public function getHomePageData(){   
		$categorytags	=	$locations	=	$popular_finders	=	$recent_blogs	=	array();		
		$cachetime 			= 	10; 
		$finder_slugs 		=	array('house-of-wow-bandra-west', 
									   'bodyholics-lokhandwala', 
									   'activ8-studio-juhu', 
									   'your-fitness-club-borivali-west', 
									   'fist-andheri-west',
									   'dance-beat-mumbai-chowpatty',
									   'muscle-n-mind-colaba',
									   'korean-combat-martial-arts-academy');

		$categorytags		= 	Findercategorytag::active()->orderBy('ordering')->remember($cachetime)->get(array('name','_id','slug'));
		$locations			= 	Location::active()->orderBy('name')->remember($cachetime)->get(array('name','_id','slug'));
		$popular_finders 	=	Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
										->with(array('location'=>function($query){$query->select('_id','name','slug');}))
										->whereIn('slug', $finder_slugs)
										->remember($cachetime)
										->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location'))
										->take(8)
										->toArray();
		$recent_blogs 		= 	Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
									->with('categorytags')
									->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
									->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
									->where('status', '=', '1')
									->orderBy('_id', 'desc')
									->remember($cachetime)
									->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
									->take(4)->toArray();		
		$homedata 			= 	array(			
									'categorytags' => $categorytags,
									'locations' => $locations,
									'popular_finders' => $popular_finders,       
									'recent_blogs' => $recent_blogs
								);
		return Response::json($homedata);
	}


}