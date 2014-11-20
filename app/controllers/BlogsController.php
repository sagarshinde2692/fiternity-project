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

	/**
	 * Display a listing of blogs
	 *
	 * @return Response
	 */
	public function index(){

		$perpage = Config::get('app.perpage');
		$blogs = Blog::with('category')->with('categorytags')->orderBy('_id')->paginate($perpage);
		return View::make('blogs.index', compact('blogs'));
	}

	/**
	 * Show the form for creating a new blog
	 *
	 * @return Response
	 */
	public function create(){

		$authors = User::active()->where('usergroups',"author")->orderBy('ordering')->lists('name','_id');
		$experts = array_add(User::active()->where('usergroups',"expert")->orderBy('ordering')->lists('name','_id'),'','select expert');
		$categories = Blogcategory::active()->orderBy('name')->lists('name','_id');
		$categorytags = Blogcategorytag::active()->orderBy('name')->lists('name','_id');
		return View::make('blogs.create', compact('categories','categorytags','authors','experts'));
	}

	/**
	 * Store a newly created blog in storage.
	 *
	 * @return Response
	 */
	public function store(){

		$insertedid = Blog::max('_id') + 1;
		$validator = Validator::make($data = Input::all(), Blog::$rules);
		if ($validator->fails()){
			return Redirect::back()->withErrors($validator)->withInput();
		}

		$blogdata = $data;
		array_set($blogdata, 'slug', url_slug(array($blogdata['title'])));
		array_set($blogdata, 'views', 0);

		//used keep the relastionship cloumn atleast if not selected
		array_set($blogdata, 'categorytags', array());
		array_set($blogdata, 'category_id', intval($blogdata['category_id']));
		array_set($blogdata, 'author_id', intval($blogdata['author_id']));
		if(!Input::has('expert_id')){
			array_set($blogdata, 'expert_id', '');
		}else{
			array_set($blogdata, 'expert_id', intval($blogdata['expert_id']));
		}
		array_set($blogdata, 'coverimage', $insertedid.".jpg");

		//Blog::create($data);
		$blog = new Blog($blogdata);
		$blog->_id = $insertedid;
		$blog->save();

		//manage categorytags
		if(!empty(Input::get('categorytags'))){
			$blogcategorytags = array_map('intval', Input::get('categorytags'));
			$blog = Blog::find($insertedid);
			$blog->categorytags()->sync(array());
			foreach ($blogcategorytags as $key => $value) {
				$blog->categorytags()->attach($value);
			}
		}

		Session::flash('message', 'Post created Successfully!');
		return Redirect::route('blogs.index');
	}

	/**
	 * Display the specified blog.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id){

		$id = (int) $id;
		$blog = Blog::with('category')
					->with('categorytags')
					->with('author')
					->with('expert')
					->findOrFail($id);

		return $blog;
		return View::make('blogs.show', compact('blog'));
	}

	/**
	 * Show the form for editing the specified blog.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id){

		$id = (int) $id;
		$blog = Blog::find($id);
		$authors = User::active()->where('usergroups',"author")->orderBy('ordering')->lists('name','_id');
		$experts = array_add(User::active()->where('usergroups',"expert")->orderBy('ordering')->lists('name','_id'),'','select expert');
		$categories = Blogcategory::active()->orderBy('name')->lists('name','_id');
		$categorytags = Blogcategorytag::active()->orderBy('name')->lists('name','_id');
		return View::make('blogs.edit', compact('blog','categories','categorytags','authors','experts'));
	}

	/**
	 * Update the specified blog in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id){

		$id = (int) $id;
		$blog = Blog::findOrFail($id);
		$validator = Validator::make($data = Input::all(), Blog::$rules);
		if ($validator->fails()){
			return Redirect::back()->withErrors($validator)->withInput();
		}
		$blogdata = array_except($data, array('categorytags'));	
		array_set($blogdata, 'slug', url_slug(array($blogdata['title'])));
		array_set($blogdata, 'views', 0);
		array_set($blogdata, 'category_id', intval($blogdata['category_id']));
		array_set($blogdata, 'author_id', intval($blogdata['author_id']));
		if(!Input::has('expert_id')){
			array_set($blogdata, 'expert_id', '');
		}else{
			array_set($blogdata, 'expert_id', intval($blogdata['expert_id']));
		}
		array_set($blogdata, 'coverimage', $id.".jpg");
		$blog->update($blogdata);

		//manages categorytags
		if(!empty(Input::get('categorytags'))){
			$blogcategorytags = array_map('intval', Input::get('categorytags'));
			$blog->categorytags()->attach($blogcategorytags[0]);
			$blog->categorytags()->sync(array());
			foreach ($blogcategorytags as $key => $value) {
				$blog->categorytags()->attach($value);
			}
		}else{
			$blog->categorytags()->sync(array());
		}

		Session::flash('message', 'Successfully updated blogs!');
		return Redirect::route('blogs.index');
	}

	/**
	 * Remove the specified blog from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id){

		$id = (int) $id;
		Blog::destroy($id);
		Session::flash('message', 'Successfully deleted blogs!');
		return Redirect::route('blogs.index');
	}

}
