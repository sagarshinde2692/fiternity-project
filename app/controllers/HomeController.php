<?php

//use Moa\API\Provider\ProviderInterface;

class HomeController extends BaseController {

	public function __construct() {
     	parent::__construct();	
    }

	public function getHomePageData(){   

		$categorytags = $locations = $popular_finders =	$recent_blogs =	array();						
		
		$finder_gym_slugs 		=		array('golds-gym-bandra-west', 
											   'your-fitness-club-charni-road', 
											   'powerhouse-gym-juhu', 
											   'anytime-fitness-lokhandwala');

		$finder_yoga_slugs 		=		array('cosmic-fusion-santacruz-west', 
											   'mandeep-hot-yoga-lokhandwala', 
											   'yogacara-healing-arts-bandra-west', 
											   'bharat-thakur-s-artistic-yoga-bandra-west');

		$finder_zumba_slugs 	=		array('dance-beat-mumbai-chowpatty', 
											   'aamad-performing-arts-andheri-west', 
											   'y-s-dance-academy', 
											   'house-of-wow-bandra-west');

		$finder_slugs 			= 		array_merge($finder_gym_slugs,$finder_yoga_slugs,$finder_zumba_slugs);

		$categorytags			= 		Findercategorytag::active()->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
		$locations				= 		Location::active()->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));

		$popular_finders 		=		Finder::with(array('category'=>function($query){$query->select('_id','name','slug');}))
											->with(array('location'=>function($query){$query->select('_id','name','slug');}))
											->whereIn('slug', $finder_slugs)
											->remember(Config::get('app.cachetime'))
											->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location'))
											->groupBy('category.name')
											->toArray();

		$recent_blogs	 		= 		Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
											->with('categorytags')
											->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
											->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
											->where('status', '=', '1')
											->orderBy('_id', 'desc')
											->remember(Config::get('app.cachetime'))
											->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
											->take(4)->toArray();		
		$homedata 				= 	array(			
										'categorytags' => $categorytags,
										'locations' => $locations,
										'popular_finders' => $popular_finders,       
										'recent_blogs' => $recent_blogs
									);
		return Response::json($homedata);
	}


}