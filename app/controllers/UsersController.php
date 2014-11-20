<?PHP

/** 
 * ControllerName : UsersController.
 * Maintains a list of functions used for UsersController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class UsersController extends \BaseController {


	public function __construct() {
     	parent::__construct();	
    }

	/**
	 * Display a listing of users
	 *
	 * @return Response
	 */
	public function index(){

		$perpage = Config::get('app.perpage');
		$users = User::orderBy('_id')->paginate($perpage);
		return View::make('users.index', compact('users'));
	}

	/**
	 * Show the form for creating a new user
	 *
	 * @return Response
	 */
	public function create(){

		$groups = Group::orderBy('name')->lists('name','_id');
		return View::make('users.create',compact('groups'));
	}

	/**
	 * Store a newly created user in storage.
	 *
	 * @return Response
	 */
	public function store(){

		$insertedid = User::max('_id') + 1;
		$validator = Validator::make($data = Input::all(), User::$rules);
		if ($validator->fails()){
			return Redirect::back()->withErrors($validator)->withInput(Input::except('password'));
		}

		array_set($data, 'usergroups', array_values($data['usergroups']));
		array_set($data, 'ordering', intval(Input::get('ordering', 0)));
		array_set($data, 'avatar', $insertedid.".jpg");
		$user = new User($data);
		$user->_id = $insertedid;
		$user->save();
		//User::create($data);
		
		Session::flash('message', 'Successfully created users!');
		return Redirect::route('users.index');
	}

	/**
	 * Display the specified user.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id){

		$id = (int) $id;
		$user = User::with('blogs')
					->with('validatedblogs')
					->findOrFail($id);
		return $user;
		return View::make('users.show', compact('user'));
	}

	/**
	 * Show the form for editing the specified user.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id){

		$id = (int) $id;
		$user = User::find($id);
		$groups = Group::orderBy('name')->lists('name','_id');
		return View::make('users.edit', compact('user','groups'));
	}

	/**
	 * Update the specified user in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id){

		$id = (int) $id;
		$user = User::findOrFail($id);
		$validator = Validator::make($data = Input::all(), User::$rules);
		if ($validator->fails()){
			return Redirect::back()->withErrors($validator)->withInput();
		}
		array_set($data, 'avatar', $id.".jpg");
		array_set($data, 'usergroups', array_values($data['usergroups']));
		array_set($data, 'ordering', intval($data['ordering']));
		$user->update($data);
		Session::flash('message', 'Successfully updated users!');
		return Redirect::route('users.index');
	}

	/**
	 * Remove the specified user from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id){

		$id = (int) $id;
		User::destroy($id);
		Session::flash('message', 'Successfully deleted users!');
		return Redirect::route('users.index');
	}

}
