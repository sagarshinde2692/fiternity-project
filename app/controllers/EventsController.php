<?PHP

/**
 * ControllerName : EventsController.
 * Maintains a list of functions used for EventsController.
 *
 * @author Nishank Jain <nishankjain@fitternity.com>
 */

class EventsController extends \BaseController {

    public function __construct() {
        parent::__construct();
    }

    public function getEventInfo($eventName) {
        return "Printed to screen - " . $eventName;
    }
    public function brandDetail($slug, $cache = false){

        $data = array();
        $slug = (string) $slug;

        $brand_detail = $cache ? Cache::tags('brand_detail')->has($slug) : false;

        if(!$brand_detail){

            $brand = Brand::where('slug','=',$slug)->firstOrFail();
                    
            if($brand){

                $finders     =   Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
                                            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                                            ->with(array('city'=>function($query){$query->select('_id','name','slug');}))
                                            ->where('status', '=', '1')
                                            ->whereIn('_id', array_map('intval',$brand->finder_id))
                                            ->get();
                $data = array(
                        'brand'     => $brand,
                        'finders'    => $finders
                    );

                Cache::tags('brand_detail')->put($slug,$data,Config::get('cache.cache_time'));
                
                return Response::json(Cache::tags('brand_detail')->get($slug));
                
            }else{
                return Response::json(array('status' => 400,'message' => 'brand not found'),400);
            }
        }

        return Response::json(array('status' => 200,'brand_detail' => Cache::tags('brand_detail')->get($slug)),200);
    }

}

?>