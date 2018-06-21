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
		Log::info($_SERVER['REQUEST_URI']);
        
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
                $finder_locations = ['All Locations'];
                if(isset($finders['results'])){
                    foreach($finders['results'] as $finder){
                        if(isset($finder['location']) && $finder['location'] != "" && !in_array(ucwords($finder['location']), $finder_locations)){
                            array_push($finder_locations, ucwords($finder['location']));
                        }
                    }
                }

                sort($finder_locations);
                
                $brand['finder_locations'] = $finder_locations;
                array_shift($finder_locations);
                $locations = implode(',', $finder_locations);
                $brand["meta_data"] = array(
                    "title" =>$brand["name"] . " in ". ucwords($city),
                    "description" => "List of branches in ". ucwords($city)." in areas ".$locations.". See membership offers, reviews, location, fees"
                );
                $brand['stats_data'] = (!empty($finders['metadata']['total_records']) ? $finders['metadata']['total_records'] : 0).' Outlets';
                $data = array(
                        'brand'     => $brand,
                        'finders'    => $finders,
                );

                $city_id = "";

                $cityData = City::where("name",'like','%'.$city.'%')->first(['_id']);

                if($cityData){
                    $city_id = (int)$cityData['_id'];
                }

                $data["stripe_data"] = [
                    'text'=>'',
                    'background-color'=> '#000000',
                    'text_color'=> '#ffffff',
                    'background'=> '#000000'
                ];

                if(!empty($brand['vendor_stripe']) && !empty($brand['vendor_stripe']['cities']) && in_array($city_id,$brand['vendor_stripe']['cities'])){
                    $data['stripe_data']['header']  = "NEVER SEEN BEFORE DISCOUNTS";
                    $data['stripe_data']['sub_title']  = "• Limited Slots •";
                    $data['stripe_data']['title'] = $brand['vendor_stripe']['text'];
                    $data['stripe_data']['text'] = $brand['vendor_stripe']['text'];
                    $data['stripe_data']['text_color'] = $brand['vendor_stripe']['text_color'];
                    $data['stripe_data']['background-color'] = $brand['vendor_stripe']['background_color'];
                    $data['stripe_data']['background'] = $brand['vendor_stripe']['background_color'];
                }

                if(!empty($brand['vendor_stripe']) && empty($brand['vendor_stripe']['cities']) && !empty($city_id)){

                    foreach ($brand['vendor_stripe'] as $value) {

                        if(!empty($value['cities']) && in_array($city_id,$value['cities'])){
                            $data['stripe_data']['header']  = "NEVER SEEN BEFORE DISCOUNTS";
                            $data['stripe_data']['sub_title']  = "• Limited Slots •";
                            $data['stripe_data']['title'] = $value['text'];
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

                unset($data['brand']['vendor_stripe']);

                if(isset($data["stripe_data"])){
                    $data['brand']['stripe_data'] = $data["stripe_data"];
                }

                Cache::tags('brand_detail')->put("$slug-$city" ,$data,Config::get('cache.cache_time'));
                
            }else{

                return Response::json(array('status' => 400,'message' => 'brand not found'),400);
            }
        }

        $brand_detail = Cache::tags('brand_detail')->get("$slug-$city");

        if(!empty($this->device_type) && $this->device_type == "android"){

            unset($brand_detail['finders']['request']);
            unset($brand_detail['finders']['aggregations']);
        }

        if(!empty($_GET['source'])){
			$source = $_GET['source'];
            Log::info("web Increased ".$source);
            
			$brand = Brand::find($brand_detail['brand']['_id'], ['hits']);
			$total_hits = !empty($brand['hits'][$city][$source]) ? $brand['hits'][$city][$source] + 1 : 1 ;
			Log::info($total_hits);
			Brand::where('_id', $brand['_id'])->update(['hits.'.$city.'.'.$source =>$total_hits]);
		}

        return Response::json($brand_detail);
        
    }

    public function brandlist(){
        
        $brands = Brand::active()->lists('slug');
        $cities = City::active()->lists('slug');

        return array('brands'=>$brands, 'cities'=>$cities);

    }

}
