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
	public function getBlogs($limit,$offset){	
		$offset =  ((int) $offset) ? (int) $offset : 0;	
		$limit = ((int) $limit) ? (int) $limit : 10;	
		$blogs = Blog::with('user')->where('status', '=', '1')->skip($offset)->take($limit)->orderBy('_id', 'desc')->get();		
		$blogs = Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
				->with('categorytags')
				->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
				->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
				->skip($offset)
				->take($limit)
				->where('slug','=',$tslug)
				->firstOrFail();		
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
						->firstOrFail();
						//->get();
		
		$blogid 			= (int) $blog['_id'];	
		$blogcategoryid 	= (int) $blog['category_id'];	

		//return $blog;
					
		if($blog){			
			$relatedblogs = Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
							->with('categorytags')
							->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
							->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))							
							->where('_id','!=',$blogid)
							->where('category_id','=',$blogcategoryid)
							->where('status', '=', '1')
							->orderBy('_id', 'desc')
							->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
							->take(4)->toArray();

			$recentblogs = Blog::with(array('category'=>function($query){$query->select('_id','name','slug');}))
							->with('categorytags')
							->with(array('author'=>function($query){$query->select('_id','name','username','email','avatar');}))
							->with(array('expert'=>function($query){$query->select('_id','name','username','email','avatar');}))
							->where('_id','!=',$blogid)
							->where('status', '=', '1')
							->orderBy('_id', 'desc')
							->get(array('_id','author_id','category_id','categorytags','coverimage','created_at','excerpt','expert_id','slug','title','category','author','expert'))
							->take(5)->toArray();

			$data = array('blog' => $blog,
						   'related' => $relatedblogs,
						   'popular' => $recentblogs
						);
			return $data;
		}

	}

	public function getCategoryBLogs($cat){
		$category = (string) $cat;		
    	$blogs = Blogcategory::with(array('blogs' => 
    										function($query){
    											$query->orderBy('_id', 'DESC')->where('status', '=', '1');
    										}//funciton
    									)//array
    								)//with
    						 	->where('slug','=',$category)
    						   	->get();
		return $blogs;
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
	
}
