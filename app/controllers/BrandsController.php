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

            $brand = Brand::where('slug',$slug)->where("status","1")->first();

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

                
                $finder_locations = [];

                if($this->device_type == 'android'){

                    $finder_locations[] = 'All Locations';
                }
                
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
                $locations = implode(', ', array_slice($finder_locations, 0, 5));
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
                    $data['stripe_data']['sub_title']  = "&bull; Limited Slots &bull;";
                    $data['stripe_data']['title'] = $brand['vendor_stripe']['text'];
                    $data['stripe_data']['text'] = $brand['vendor_stripe']['text'];
                    $data['stripe_data']['text_color'] = $brand['vendor_stripe']['text_color'];
                    $data['stripe_data']['background-color'] = isset($brand['vendor_stripe']['background_color']) ? $brand['vendor_stripe']['background_color'] : "#0066b9";
                    $data['stripe_data']['background'] = isset($brand['vendor_stripe']['background_color']) ? $brand['vendor_stripe']['background_color'] : "#0066b9";
                }

                if(!empty($brand['vendor_stripe']) && empty($brand['vendor_stripe']['cities']) && !empty($city_id)){

                    foreach ($brand['vendor_stripe'] as $value) {

                        if(!empty($value['cities']) && in_array($city_id,$value['cities'])){
                            $data['stripe_data']['header']  = "NEVER SEEN BEFORE DISCOUNTS";
                            $data['stripe_data']['sub_title']  = "&bull; Limited Slots &bull;";
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

                if(!empty($data['stripe_data']['text'])){
                    $data['stripe_data']['text1'] = $data['stripe_data']['text'];
                }
                
                if(isset($data["stripe_data"])){
                    $data['brand']['stripe_data'] = $data["stripe_data"];
                }

                if(in_array("$slug-$city", Config::get('app.no_patti_brands_slugs'))){
                    $data['stripe_data'] = "no-patti";
                }

                if(!empty($this->device_type) && $this->device_type == "android"){

                    unset($data['finders']['request']);
                    unset($data['finders']['aggregations']);
                }
                
                if(empty($finders) || empty($finders['metadata']['total_records'])){
                    Log::info("Not caching brand");

                    if(Config::get('app.debug')){
                        $findersms = new \App\Sms\FinderSms();
                        $findersms->brandVendorEmpty(['url'=>Config::get('app.url').$_SERVER['REQUEST_URI']]);
                    }

                    return Response::json($data);
				}
				
				if($data['brand']['_id']==88){
					$data['cities_list'] = $this->multifitCities();
				}

                $this->multifitGymWebsiteBrandUpdate($data);
                Cache::tags('brand_detail')->put("$slug-$city" ,$data,Config::get('cache.cache_time'));
                
            }else{

                return Response::json(array('status' => 400,'message' => 'Brand not active'),400);
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

    public function brandlistcity($city){

        $city = City::where('slug', strtolower($city))->first();

        if(!$city){
            return;
        }

        $brand_ids = Finder::active()->where('city_id', $city['_id'])->where('brand_id', 'exists', true)->lists('brand_id');
        
        $brands = Brand::active()->whereIn('_id', $brand_ids)->get(['name', 'slug']);

        return $brands;

    }

    public function multifitGymWebsiteBrandUpdate(&$data){

        if(!empty(Request::header('Source')) && Request::header('Source') == "multifit"){

            if(!empty($data['finders']['results'])){

                foreach($data['finders']['results'] as $key=>$value){

                    if(!empty($value['thumbnail_website_membership'])){
                        $data['finders']['results'][$key]['coverimage'] = $value['thumbnail_website_membership'];
                    }
                }
            }
        }
    }

    public function getBrandWebsiteHome($brand_id=null){
		$data = Input::All();
		//$brand_id = $data['brand_id'];
		if(empty($brand_id)){
			return array("status"=>false, "message"=>"Brand is Missing");
		}

		$base_url =Config::get('app.s3_bane_url');

		$home = Brand::where('_id',(int)$brand_id)
		->select('brand_website.banner','brand_website.offer', 'brand_website.centers_block','brand_website.speakers_block','brand_website.advisory_block', 'brand_website.video')
		->get();

		$home1 = $home[0]['brand_website'];
		foreach($home1 as $key=>$value){
			if(in_array($key,['banner'])){
				$home1[$key]['image'] =  $base_url.$home1[$key]['path'].$home1[$key]['image'];
				if(!empty($home1[$key]['mobile_image'])){
					$home1[$key]['mobile_image'] =  $base_url.$home1[$key]['path'].$home1[$key]['mobile_image'];
				}
			}	

			if(in_array($key,['centers_block', 'speakers_block', 'advisory_block'])){
				foreach($home1[$key] as $keyImage=>$valueImage){
					if(!in_array($keyImage, ['webm', 'mp4', 'ogg'],true)){
						$home1[$key][$keyImage]['image'] =  $base_url.$home1[$key][$keyImage]['path'].$home1[$key][$keyImage]['image'];
					}
				}
			}	
		}
		$this->updateCitiesData($home1);
		$home[0]['brand_website'] =  $home1;

		if(!empty($home)){
			return array('status'=>true, "data"=>$home);
		}
		else{
			return array('status'=>true, "data"=>$home);
		}
	}

	public function getBrandWebsiteAboutUs($brand_id=null){
		$data = Input::All();
		//$brand_id = $data['brand_id'];
		if(empty($brand_id)){
			return array("status"=>false, "message"=>"Brand is Missing");
		}
		$base_url =Config::get('app.s3_bane_url');

		$home = Brand::where('_id',(int)$brand_id)
		->select('brand_website.overview_block','brand_website.founders_block', 'brand_website.fitness_studio','brand_website.gym_equipement','brand_website.training_software', 'brand_website.awards_list', 'brand_website.media_coverages')
		->get();

		$first_block = $home[0]['brand_website'];

		foreach($first_block as $key=>$value){
			if(in_array($key,['founders_block', 'awards_list', 'media_coverages'])){
				foreach($first_block[$key] as $keyImage=>$valueImage){
					if(!empty($first_block[$key][$keyImage]['image'])){
						$first_block[$key][$keyImage]['image'] =  $base_url.$first_block[$key][$keyImage]['path'].$first_block[$key][$keyImage]['image'] ;
					}
					if(!empty($first_block[$key][$keyImage]['popup_image'])){
						$first_block[$key][$keyImage]['popup_image'] =  $base_url.$first_block[$key][$keyImage]['path'].$first_block[$key][$keyImage]['popup_image'] ;
					}
				}
			}	

			if(in_array($key,['training_software', 'gym_equipement', 'fitness_studio'])){
				foreach($first_block[$key]['image'] as $keyImage=>$valueImage){
					$first_block[$key]['image'][$keyImage] =  $base_url.$first_block[$key]['path'].$first_block[$key]['image'][$keyImage];
				}
			}	
		}

		$home1[0]['brand_website'] = $home[0]['brand_website'];
		$home1[0]['brand_website'] = $first_block;

		if(!empty($home)){
			return array('status'=>true, "data"=>$home1);
		}
		else{
			return array('status'=>true, "data"=>$home1);
		}
	}

	public function getBrandWebsitePrograms($brand_id=null){
		$data = Input::All();
		//$brand_id = $data['brand_id'];
		if(empty($brand_id)){
			return array("status"=>false, "message"=>"Brand is Missing");
		}

		$base_url =Config::get('app.s3_bane_url');

		$home = Brand::where('_id',(int)$brand_id)
		->select('brand_website.programs')
		->get();

		$programs = $home[0]['brand_website']['programs'];
		foreach($programs as $key=>$value){
			if(!in_array($key, ['name','path'], true)){
				foreach($value['image'] as $imageIndex=>$imageName){
					$programs[$key]['image'][$imageIndex] =  $base_url.$programs['path'].$imageName;
				}
			}
		}

		$home1[0]['brand_website']['programs'] = $programs;
		if(!empty($home)){
			return array('status'=>true, "data"=>$home1);
		}
		else{
			return array('status'=>true, "data"=>$home1);
		}
	}

	public function getBrandWebsiteHiit($brand_id=null){
		$data = Input::All();
		//$brand_id = $data['brand_id'];
		if(empty($brand_id)){
			return array("status"=>false, "message"=>"Brand is Missing");
		}
		
		$base_url =Config::get('app.s3_bane_url');

		$home = Brand::where('_id',(int)$brand_id)
		->select('brand_website.hiit')
		->get();

		if(!empty($base_url)){
			foreach($home as $key=>$value){
				$home[$key]['base_url'] = $base_url;
			}
		}

		$hiit = $home[0]['brand_website']['hiit'];
		foreach($hiit as $key=>$value){
			if(!in_array($key, ['name','path'], true) && !empty($value['image'])){
				foreach($value['image'] as $imageIndex=>$imageName){
					$hiit[$key]['image'][$imageIndex] =  $base_url.$hiit['path'].$imageName;
				}
			}
		}
		$home1[0]['brand_website']['hiit'] = $hiit;

		if(!empty($home1)){
			return array('status'=>true, "data"=>$home1);
		}
		else{
			return array('status'=>true, "data"=>$home);
		}
	}

	public function getBrandWebsiteContactUs($brand_id=null){
		$data = Input::All();
		//$brand_id = $data['brand_id'];
		if(empty($brand_id)){
			return array("status"=>false, "message"=>"Brand is Missing");
		}
		
		$base_url =Config::get('app.s3_bane_url');

		$home = Brand::where('_id',(int)$brand_id)
		->select('brand_website.contact_us')
		->get();

		$home1= $home[0]['brand_website'];
		$home1['contact_us']['banner_image'] = $base_url.$home1['contact_us']['path'].$home1['contact_us']['banner_image'];
		$home[0]['brand_website']=$home1; 

		if(!empty($home)){
			return array('status'=>true, "data"=>$home);
		}
		else{
			return array('status'=>true, "data"=>$home);
		}
	}

	public function getBrandWebsiteOwnFranchise($brand_id=null){
		$data = Input::All();
		//$brand_id = $data['brand_id'];
		if(empty($brand_id)){
			return array("status"=>false, "message"=>"Brand is Missing");
		}

		$base_url =Config::get('app.s3_bane_url');

		$home = Brand::where('_id',(int)$brand_id)
		->select('brand_website.own_franchise')
		->get();


		$home1 = $home[0]['brand_website'];
		foreach($home1['own_franchise']['what_we_deliver']['details'] as $key=>$value){
			$home1['own_franchise']['what_we_deliver']['details'][$key]['image'] =  $base_url.$home1['own_franchise']['what_we_deliver']['details'][$key]['path'].$home1['own_franchise']['what_we_deliver']['details'][$key]['image'];
		}

		foreach($home1['own_franchise']['partners_list'] as $key=>$value){
			$home1['own_franchise']['partners_list'][$key]['logo'] = $base_url.$home1['own_franchise']['partners_list'][$key]['path'].$home1['own_franchise']['partners_list'][$key]['logo'];

			$home1['own_franchise']['partners_list'][$key]['colored_logo'] = $base_url.$home1['own_franchise']['partners_list'][$key]['path'].$home1['own_franchise']['partners_list'][$key]['colored_logo'];
		}

		$home1['own_franchise']['banner_image']['image'] = $base_url.$home1['own_franchise']['banner_image']['path'].$home1['own_franchise']['banner_image']['image'];
		$home[0]['brand_website'] =  $home1;


		if(!empty($home)){
			return array('status'=>true, "data"=>$home);
		}
		else{
			return array('status'=>true, "data"=>$home);
		}
	}

	public function multifitCities(){
		$cities = City::lists('name');
		Log::info('cities name::::::::::::', [$cities]);
		$city_list = [];
		$listed_cities_multifit = ['jaipur','pune', 'mumbai', 'hyderabad', 'bangalore', 'gurgaon'];
		foreach($cities as $key=>$value){
			if(in_array(strtolower($value), $listed_cities_multifit)){
				array_push($city_list,[
					'name' => ucwords($value) ,
					'slug' => 'listing-multifit-'.strtolower($value),
					'city_brand' => true 
				]);
			}
		} 
		$city = Config::get('multifit.vendors_slug');
		return array_merge($city_list, $city);
	}

	public function updateCitiesData(&$data){
		$cities = City::lists('name');
		$without_brand_city = Config::get('multifit.without_brand_city');

		foreach($cities as &$city){
			$city = strtolower($city);
		}
		foreach($data['centers_block'] as &$value){
			if(in_array(strtolower($value['name']), $cities)){
				$value['city_brand'] = true;
				$value['slug'] = 'listing-multifit-'.strtolower($value['name']);
			}
			else{
				$value['city_brand'] = false;
				$value['slug'] = !empty($without_brand_city[strtolower($value['name'])])? $without_brand_city[strtolower($value['name'])]['slug']:'';
			}
		}
	}

}
