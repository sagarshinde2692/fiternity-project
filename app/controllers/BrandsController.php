<?PHP

/**
 * ControllerName : brandsController.
 * Maintains a list of functions used for brandsController.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class BrandsController extends \BaseController {

    public function __construct() {
        parent::__construct();
    }

    public function brandDetail($slug, $cache = true){

        $data = array();
        $slug = (string) $slug;

        $brand_detail = $cache ? Cache::tags('brand_detail')->has($slug) : false;

        if(!$brand_detail){

            $brand = Brand::where('slug','=',$slug)->firstOrFail();
                    
            if($brand){

                $finders     =   Finder::with(array('category'=>function($query){$query->select('_id','name','slug','related_finder_title');}))
                                            ->with(array('location'=>function($query){$query->select('_id','name','slug');}))
                                            ->where('status', '=', '1')
                                            ->whereIn('_id', array_map('intval',$brand->finder_id))
                                            ->get();
                $data = array(
                        'brand'     => $brand,
                        'finders'    => $finders
                    );

                Cache::tags('brand_detail')->put($slug,$data,Config::get('cache.cache_time'));
                
            }
        }

        return Response::json(Cache::tags('brand_detail')->get($slug));
    }

}
