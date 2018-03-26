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


    public function brandDetail($slug, $city, $cache = true){
        
        $brand_detail = $cache ? Cache::tags('brand_detail')->has("$slug-$city") : false;

        if(!$brand_detail){

            $brand = Brand::where('slug',$slug)->where("status","1")->firstOrFail();

            $finder_ids = isset($brand->finder_id) ? $brand->finder_id : [];
                    
            if($brand){

                if($brand->coverImage == ""){
                    unset($brand->coverImage);
                }

                if($brand->logo == ""){
                    unset($brand->logo);
                }

                if(isset($brand->coverImage)){
                    $brand->coverImage = "https://b.fitn.in/brand/cover/".$brand->coverImage;
                }

                if(isset($brand->logo)){
                    $brand->logo = "https://b.fitn.in/brand/logo/".$brand->logo;
                }

                $request = [
                    'brand_id' => $brand->_id,
                    'city'  => $city
                ];
                
                $finders = vendorsByBrand($request);
                $data = array(
                        'brand'     => $brand,
                        'finders'    => $finders
                );
                if($slug == "power-world-gym"){
                    $data["stripe_data"] = [
                        'text'=> "Get additional 25% cashback as Fitcash on 1 year membership",
                        'text_color'=> '#ffffff',
                        'background'=> '-webkit-linear-gradient(left, #71b2c7 0%, #71b2c7 100%)',
                        'background-color'=> ''
                    ];
                }elseif($slug == "fitness-first"){
                    $data["stripe_data"] = [
                        'text'=> "1 month membership free (only for women) valid till 31st March",
                        'text_color'=> '#ffffff',
                        'background'=> '-webkit-linear-gradient(left, #FE7E87 0%, #FA5295 100%)',
                        'background-color'=> ''
                    ];
                }
                Cache::tags('brand_detail')->put("$slug-$city" ,$data,Config::get('cache.cache_time'));
                
                return Response::json(Cache::tags('brand_detail')->get("$slug-$city"));
                
            }else{
                return Response::json(array('status' => 400,'message' => 'brand not found'),400);
            }
        }

        return Response::json(Cache::tags('brand_detail')->get("$slug-$city"));
    }

    public function brandlist(){
        
        $brands = Brand::active()->lists('slug');
        $cities = City::active()->lists('slug');

        return array('brands'=>$brands, 'cities'=>$cities);

    }

}
