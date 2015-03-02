<?PHP

/** 
 * ControllerName : SchedulebooktrialsController.
 * Maintains a list of functions used for SchedulebooktrialsController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class SchedulebooktrialsController extends \BaseController {



	public function __construct() {
		parent::__construct();	
	}

	public function getScheduleBookTrial($finderid,$date = null){
		
		$date = Carbon\Carbon::now();
		return $date;


		

		/*
			-	based on date get weekday
			-	get services base on finderid and weekday
			date
			weekday
			schedule_class[
				{
					service name, slots
				}
			]
		*/
		return "retrun ScheduleBookTrial for finder";
	}



}
