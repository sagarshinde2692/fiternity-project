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
	 * Return list of experts.
	 */
	public function getExperts(){
		$users = User::where('usergroups',"expert")->orderBy('ordering')->get();
		return $users;
	}


	/**
	 * Return expert detail.
	 */
	public function getExpert($username){
		$username = (string) $username;		
		$user = User::with('blogs')
					->with('validatedblogs')
					->where('username','=',$username)
					->firstOrFail();	
				
		return $user;		
	}

	/**
	 * Return list of author.
	 */
	public function getAuthors(){
		$users = User::where('usergroups',"author")->orderBy('ordering')->get();
		return $users;
	}

	/**
	 * Return author detail.
	 */
	public function getAuthor($username){
		$username = (string) $username;		
		$user = User::with('blogs')
					->where('username','=',$username)
					->firstOrFail();	
		return $user;		
	}


}
