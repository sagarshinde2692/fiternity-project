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


    public function brandDetail($slug, $city, $cache = false){
        
        $brand_detail = $cache ? Cache::tags('brand_detail')->has("$slug-$city") : false;

        if(!$brand_detail){

            $brand = Brand::where('slug',$slug)->firstOrFail();

            $finder_ids = isset($brand->finder_id) ? $brand->finder_id : [];
                    
            if($brand){

                $request = [
                    'brand_id' => $brand->_id,
                    'city'  => $city
                ];
                
                $finders = vendorsByBrand($request);
                
                $data = array(
                        'brand'     => $brand,
                        'finders'    => $finders
                    );

                Cache::tags('brand_detail')->put("$slug-$city" ,$data,Config::get('cache.cache_time'));
                
                return Response::json(Cache::tags('brand_detail')->get("$slug-$city"));
                
            }else{
                return Response::json(array('status' => 400,'message' => 'brand not found'),400);
            }
        }

        return Response::json(array('status' => 200,'brand_detail' => Cache::tags('brand_detail')->get($slug)));
    }

}
