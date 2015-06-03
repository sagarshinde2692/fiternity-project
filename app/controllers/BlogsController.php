<?PHP

/** 
 * ControllerName : BlogsController.
 * Maintains a list of functions used for BlogsController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class BlogsController extends \BaseController {

	public function __construct() {
     	parent::__construct();	
    }

    // Limiting to something
	public function getBlogs($offset = 0,$limit = 10){	
		$blog_list = Cache::tags('blog_list')->has($offset.'_'.$limit);

		if(!$blog_list){
			$offset =  	(int) $offset;	
			$limit 	= 	(int) $limit;	
			$blogs 	=	Blog::with(array('category'=>function($query){$query->select('_id','name','slug','meta');}))
							->with('categorytags')
							->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
							->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))							
							->where('status', '=', '1')
							->orderBy('_id', 'desc')
							->skip($offset)
							->take($limit)
							->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
							->toArray();

			Cache::tags('blog_list')->put($offset.'_'.$limit,$blogs,Config::get('cache.cache_time'));
		}

		return Cache::tags('blog_list')->get($offset.'_'.$limit);
	}

	public function blogdetail($slug){
		$data = array();
		$tslug = (string) $slug;

		$blog_detail = Cache::tags('blog_detail')->has($tslug);
		$blog_detail_related = Cache::tags('blog_detail_related')->has($tslug);
		$blog_detail_recent = Cache::tags('blog_detail_recent')->has($tslug);
		$blog_detail_related_finders = Cache::tags('blog_detail_related_finders')->has($tslug);
		$blog_detail_category_tags = Cache::tags('blog_detail_category_tags')->has($tslug);
		$blog_detail_locations = Cache::tags('blog_detail_locations')->has($tslug);
		$blog_detail_category = Cache::tags('blog_detail_category')->has($tslug);

		if(!$blog_detail){
			$blog = Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
							->with('categorytags')
							->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
							->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
							->where('slug','=',$tslug)
							->remember(Config::get('app.cachetime'))
							->firstOrFail();
							//->get();

			Cache::tags('blog_detail')->forever($tslug,$blog);
		}else{
			$blog = Cache::tags('blog_detail')->get($tslug);
		}

		//return $blog;
					
		if($blog){

			$blogid 			= (int) $blog['_id'];	
			$blogcategoryid 	= (int) $blog['category_id'];	
			$findercategoryid 	= (int) $blog['finder_category_id'];

			if(!$blog_detail_related){
				$relatedblogs 	= 	Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
										->with('categorytags')
										->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
										->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))							
										->where('_id','!=',$blogid)
										->where('category_id','=',$blogcategoryid)
										->where('status', '=', '1')
										->orderBy('_id', 'desc')
										->remember(Config::get('app.cachetime'))
										->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
										->take(4)->toArray();

				Cache::tags('blog_detail_related')->forever($tslug,$relatedblogs);
			}

			if(!$blog_detail_recent){
				$recentblogs 	= 	Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
										->with('categorytags')
										->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
										->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
										->where('_id','!=',$blogid)
										->where('status', '=', '1')
										->orderBy('_id', 'desc')
										->remember(Config::get('app.cachetime'))
										->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
										->take(5)->toArray();

				Cache::tags('blog_detail_recent')->forever($tslug,$recentblogs);
			}

			if(!$blog_detail_related_finders){
				$relatedfinders 	=	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
											->with(array('location'=>function($query){$query->select('_id','name','slug');}))
											->where('category_id','=',$findercategoryid)
											->where('finder_type', '=', 1)
											->where('status', '=', '1')
											->remember(Config::get('app.cachetime'))
											->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count'))
											->take(4)->toArray();

				Cache::tags('blog_detail_related_finders')->forever($tslug,$relatedfinders);
			}			

			if(!$blog_detail_category_tags){
				$categorytags	= 	Findercategorytag::active()->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));

				Cache::tags('blog_detail_category_tags')->forever($tslug,$categorytags);
			}

			if(!$blog_detail_locations){
				$locations			= 	Location::active()->whereIn('cities',array(1))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));

				Cache::tags('blog_detail_locations')->forever($tslug,$locations);
			}

			if(!$blog_detail_category){
				$category 	=	Findercategory::where('_id',$findercategoryid)
													->remember(Config::get('app.cachetime'))
													->first(array('name','slug'));

				Cache::tags('blog_detail_category')->forever($tslug,$category);
			}

			$data = array(
						'blog' 			=> Cache::tags('blog_detail')->get($tslug),
						'related' 		=> Cache::tags('blog_detail_related')->get($tslug),
						'popular' 		=> Cache::tags('blog_detail_recent')->get($tslug),
						'relatedfinders' 	=> Cache::tags('blog_detail_related_finders')->get($tslug),
						'categorytags' 	=> Cache::tags('blog_detail_category_tags')->get($tslug),
						'locations' 		=> Cache::tags('blog_detail_locations')->get($tslug),
						'findercategory' 	=> Cache::tags('blog_detail_category')->get($tslug),
						'blog_findercategoryid' => $findercategoryid
					);

			return Response::json($data);
		}

	}

	public function getCategoryBlogs($cat){		

		$blog_by_category_list = Cache::tags('blog_by_category_list')->has($cat);

		if(!$blog_by_category_list){
			$blogcategory		=  Blogcategory::where('slug','=',$cat)->firstOrFail();
			$blogcategoryid 	= (int) $blogcategory['_id'];	

			$catblogs = Blog::with(array('category'=>function($query){$query->select('_id','name','slug','meta');}))
							->with('categorytags')
							->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
							->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
							->where('status', '=', '1')
							->where('category_id','=',$blogcategoryid)
							->orderBy('_id', 'desc')
							->remember(Config::get('app.cachetime'))
							->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'));

			Cache::tags('blog_by_category_list')->put($cat,$catblogs,Config::get('app.cachetime'));

		}	
		return Cache::tags('blog_by_category_list')->get($cat);
	}



	public function updateblogdate(){
		$items = Blog::orderBy('_id')->get();
		$blogdata = array();
		foreach ($items as $item) {  
			$data = $item->toArray();
			array_set($blogdata, 'created_at', date(strtotime($data['created_at'])));
			array_set($blogdata, 'updated_at', date(strtotime($data['updated_at'])));		
			$blog = Blog::findOrFail($data['_id']);
			$blog->update($blogdata);
		}

		$items = User::orderBy('_id')->get();
		$userdata = array();
		foreach ($items as $item) {  
			$data = $item->toArray();
			array_set($userdata, 'created_at', date(strtotime($data['created_at'])));	
			array_set($userdata, 'updated_at', date(strtotime($data['updated_at'])));			
			$blog = User::findOrFail($data['_id']);
			$blog->update($userdata);
		}

	}


	public function getblogRelatedFinder(){

			$city_id				= 	(Input::json()->get('city_id')) ? intval(Input::json()->get('city_id')) : 1;	
			$blog_findercategoryid 	= 	(int) Input::json()->get('blog_findercategoryid');	

			$categorytags			= 	Findercategorytag::active()->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
			$locations				= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug','location_group'));
			$relatedfinders 		=	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
											->with(array('location'=>function($query){$query->select('_id','name','slug');}))
											->where('category_id','=',$blog_findercategoryid)
											->where('finder_type', '=', 1)
											->where('city_id', '=', $city_id)
											->where('status', '=', '1')
											->remember(Config::get('app.cachetime'))
											->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count'))
											->take(4)->toArray();	

			$data = array('relatedfinders' 	=> $relatedfinders,
						  'categorytags' 	=> $categorytags,
						  'locations' 		=> $locations
						);
			return $data;

	}
	
}
