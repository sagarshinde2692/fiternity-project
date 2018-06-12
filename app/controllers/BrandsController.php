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

                $city_id = "";

                $city = City::where("name",'like','%'.$city.'%')->first(['_id']);

                if($city){
                    $city_id = (int)$city['_id'];
                }

                $data["stripe_data"] = [
                    'text'=>'',
                    'background-color'=> '#000000',
                    'text_color'=> '#ffffff',
                    'background'=> '#000000'
                ];

                if(!empty($brand['vendor_stripe']) && !empty($brand['vendor_stripe']['cities']) && in_array($city_id,$brand['vendor_stripe']['cities'])){

                    $data['stripe_data']['text'] = $brand['vendor_stripe']['text'];
                    $data['stripe_data']['text_color'] = $brand['vendor_stripe']['text_color'];
                    $data['stripe_data']['background-color'] = $brand['vendor_stripe']['background_color'];
                    $data['stripe_data']['background'] = $brand['vendor_stripe']['background_color'];
                }

                if(!empty($brand['vendor_stripe']) && empty($brand['vendor_stripe']['cities']) && !empty($city_id)){

                    foreach ($brand['vendor_stripe'] as $value) {

                        if(in_array($city_id,$value['cities'])){

                            $data['stripe_data']['text'] = $value['text'];
                            $data['stripe_data']['text_color'] = $value['text_color'];
                            $data['stripe_data']['background-color'] = $value['background_color'];
                            $data['stripe_data']['background'] = $value['background_color'];

                            break;
                        }
                    }
                }

                if(empty($data['stripe_data']['text'])){
                    unset($data["stripe_data"]);
                }

                unset($brand['vendor_stripe']);

                Cache::tags('brand_detail')->put("$slug-$city" ,$data,Config::get('cache.cache_time'));
                
            }else{

                return Response::json(array('status' => 400,'message' => 'brand not found'),400);
            }
        }

        $brand_detail = Cache::tags('brand_detail')->get("$slug-$city");

        if(!empty($this->device_type) && $this->device_type == "android"){

            unset($brand_detail['finders']['result']);
            unset($brand_detail['finders']['aggregation']);
        }

        return Response::json($brand_detail);
    }

    public function brandlist(){
        
        $brands = Brand::active()->lists('slug');
        $cities = City::active()->lists('slug');

        return array('brands'=>$brands, 'cities'=>$cities);

    }

}
