<?PHP

/** 
 * ControllerName : OrderControllerDebugController.
 * Maintains a list of functions used for DebugController.
 *
 * @author Mahesh Jadhav
 */

class DebugController extends \BaseController {

	public function __construct() {

	}

	public function invalidFinderStats(){

		$finder = Finder::active()->where('finder_vcc_email', 'exists', true)->where("finder_vcc_email","!=","")->orderBy('_id', 'asc')->get(array('_id','slug','finder_vcc_email'));
		$array = [];
		foreach ($finder as $key => $value) {

			$finderarray = [];
			$finderarray['id'] = $value->_id;
			$finderarray['slug'] = $value->slug;
			$finderarray['finder_vcc_email'] = $value->finder_vcc_email;

			$explode = explode(',', $value->finder_vcc_email);
			$valid = [];
			$invalid = [];
			foreach ($explode as $email) {
				if (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false){
					array_push($invalid, $email);
				}else{
					array_push($valid, $email);
				}
			}

			$finderarray['valid'] = $valid;
			$finderarray['invalid'] = $invalid;

			$array[] = $finderarray;
		}

		//echo"<pre>";print_r($array);exit;

		foreach ($array as $key => $value) {

			//echo"<pre>";print_r(count($value['invalid']));
			if(count($value['invalid']) > 0)
			{
				echo"<pre>";print_r($value);
			}
			//echo"<pre>";print_r($value);
			# code...
		}
		
	}

}
