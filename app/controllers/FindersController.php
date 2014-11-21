<?PHP

/** 
 * ControllerName : FindersController.
 * Maintains a list of functions used for FindersController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class FindersController extends \BaseController {

	public function __construct() {
		parent::__construct();			
	}

	public function finderdetail($slug){
		$data = array();
		$tslug = (string) $slug;
		$finder = Finder::with('category')
						->with('location')
						->with('categorytags')
						->with('locationtags')
						->with('offerings')
						->with('facilities')
						->where('slug','=',$tslug)
						->first();

		if($finder){
			$data['finder'] 		= $finder;
			$data['statusfinder'] 	= 200;
			return $data;
		}else{
			$updatefindersulg 		= Urlredirect::whereIn('oldslug',array($tslug))->firstOrFail();
			$data['finder'] 		= $updatefindersulg->newslug;
			$data['statusfinder'] 	= 404;			
			return $data;
		}		
	}


}
