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
			return $blogs;
	}

	public function blogdetail($slug){
		$data = array();
		$tslug = (string) $slug;
		$blog = Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
						->with('categorytags')
						->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
						->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
						->where('slug','=',$tslug)
						->remember(Config::get('app.cachetime'))
						->firstOrFail();
						//->get();
		
		$blogid 			= (int) $blog['_id'];	
		$blogcategoryid 	= (int) $blog['category_id'];	
		$findercategoryid 	= (int) $blog['finder_category_id'];

		//return $blog;
					
		if($blog){			
			$relatedblogs 		= 	Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
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

			$recentblogs 		= 	Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
										->with('categorytags')
										->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
										->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
										->where('_id','!=',$blogid)
										->where('status', '=', '1')
										->orderBy('_id', 'desc')
										->remember(Config::get('app.cachetime'))
										->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
										->take(5)->toArray();

			$relatedfinders 	=	Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
											->with(array('location'=>function($query){$query->select('_id','name','slug');}))
											->where('category_id','=',$findercategoryid)
											->where('finder_type', '=', 1)
											->where('status', '=', '1')
											->remember(Config::get('app.cachetime'))
											->get(array('_id','average_rating','category_id','coverimage','slug','title','category','location_id','location','total_rating_count'))
											->take(4)->toArray();				

			$categorytags		= 	Findercategorytag::active()->orderBy('ordering')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
			$locations			= 	Location::active()->whereIn('cities',array(1))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
			$category 			=	Findercategory::where('_id',$findercategoryid)
													->remember(Config::get('app.cachetime'))
													->first(array('name','slug'));

			$data = array('blog' 			=> $blog,
						  'related' 		=> $relatedblogs,
						  'popular' 		=> $recentblogs,
						  'relatedfinders' 	=> $relatedfinders,
						  'categorytags' 	=> $categorytags,
						  'locations' 		=> $locations,
						  'findercategory' 	=> $category,
						  'blog_findercategoryid' => $findercategoryid
						);
			return $data;
		}

	}

	public function getCategoryBLogs($cat){		

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
		return $catblogs;
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
			$locations				= 	Location::active()->whereIn('cities',array($city_id))->orderBy('name')->remember(Config::get('app.cachetime'))->get(array('name','_id','slug'));
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
