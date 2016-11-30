<?PHP

/**
 * ControllerName : AnalyticsController.
 * Maintains a list of functions used for AnalyticsController.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class AnalyticsController extends \BaseController {

    public function __construct() {
        parent::__construct();
    }

    public function reviews(){

        $data = array();

        $from_date = new MongoDate(strtotime(date('Y-m-d 00:00:00')));
        $to_date = new MongoDate(strtotime(date('Y-m-d 23:59:59')));

        //$review_query = Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date);

        $data[] = ['Source','Count'];
        $data[] = ['Total', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->count()];
        $data[] = ['Admin', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->where(function($query){$query->orWhere('source','exists',false)->orWhere('source','admin');})->count()];
        $data[] = ['Website', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->where('source','customer')->count()];
        $data[] = ['Android', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->where('source','android')->count()];
        $data[] = ['Ios', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->where('source','ios')->count()];

        return Response::json(array('status' => 200,'data'=>$data),200);
    }

}
