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
                $city_id= City::where("name",'like','%'.$city.'%')->first(['_id']);
                if(!empty($brand['vendor_stripe'])&&!empty($brand['vendor_stripe']['cities'])&&!empty($city_id)&&!empty($city_id->_id)&&in_array((int)$city_id->_id, $brand['vendor_stripe']['cities'])){
                	$data["stripe_data"] = [
                			'text'=> (!empty($brand['vendor_stripe'])&&!empty($brand['vendor_stripe']['text']))?$brand['vendor_stripe']['text']:"",
                			'background-color'=> (!empty($brand['vendor_stripe'])&&!empty($brand['vendor_stripe']['background_color']))?$brand['vendor_stripe']['background_color']:"",
                			'text_color'=> (!empty($brand['vendor_stripe'])&&!empty($brand['vendor_stripe']['text_color']))?$brand['vendor_stripe']['text_color']:"",
                			'background'=> (!empty($brand['vendor_stripe'])&&!empty($brand['vendor_stripe']['background_color']))?$brand['vendor_stripe']['background_color']:""
                	];
                }
                if(!(!empty($brand['vendor_stripe'])&&!empty($brand['vendor_stripe']['text'])))
                	unset($data["stripe_data"]);
                unset($brand['vendor_stripe']);
                
                
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
