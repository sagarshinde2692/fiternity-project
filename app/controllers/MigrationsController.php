<?PHP

/** 
 * ControllerName : MigrationsController.
 * Maintains a list of functions used for MigrationsController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class MigrationsController extends \BaseController {


	public function __construct() {
		parent::__construct();	
	}

	

	/**
	 * Migration for country
	 */
	public function country(){
		// $ids	=	Country::active()->take(1)->lists('_id');
		$ids	=	Country::lists('_id');

		if($ids){ 
			DB::connection('mongodb2')->table('countries')->truncate(); 

			foreach ($ids as $key => $id) {
				$Country = Country::find(intval($id));

				$insertData = [
				'name' =>  trim($Country->name),
				'slug' =>  trim($Country->slug),
				'order' =>  0,
				'hidden' =>  ($Country->status == "1") ? false : true,
				'created_at' =>  (isset($Country->created_at)) ? $Country->created_at : $Country->updated_at,
				'updated_at' =>  $Country->updated_at
				];

				$entity 		=	new Country($insertData);
				$entity->setConnection('mongodb2');
				$entity->_id 	=	intval($Country->_id);
				$entity->save();
			}
		}//ids
		
	}




	/**
	 * Migration for City
	 */
	public function city(){
		// $ids	=	City::active()->take(1)->lists('_id');
		$ids	=	City::take(10)->lists('_id');

		if($ids){ 
			DB::connection('mongodb2')->table('cities')->truncate(); 

			foreach ($ids as $key => $id) {
				// return $City = City::with('findercategorys')->with('categorytags')->with('locations')->with('locationtags')->find(intval($id));
				$City = City::find(intval($id));

				$insertData = [
				'name' =>  trim($City->name),
				'slug' =>  trim($City->slug),
				'country_id' =>  intval($City->country_id),
				'order' =>  0,
				'hidden' =>  (isset($City->status) && $City->status == "1")  ? 	 false : true,
				'created_at' =>  (isset($City->created_at)) ? $City->created_at : $City->updated_at,
				'updated_at' =>  $City->updated_at
				];

				$entity 		=	new City($insertData);
				$entity->setConnection('mongodb2');
				$entity->_id 	=	intval($City->_id);
				$entity->save();
			}
		}//ids
		
	}
	



	/**
	 * Migration for Locationcluster
	 */
	public function locationcluster(){
		$ids	=	Locationcluster::take(100)->lists('_id');

		if($ids){ 
			DB::connection('mongodb2')->table('locationclusters')->truncate(); 

			foreach ($ids as $key => $id) {
				// return $Locationcluster = Locationcluster::with('findercategorys')->with('categorytags')->with('locations')->with('locationtags')->find(intval($id));
				$Locationcluster = Locationcluster::find(intval($id));

				$insertData = [
				'name' =>  trim($Locationcluster->name),
				'slug' =>  trim($Locationcluster->slug),
				'city_id' =>  intval($Locationcluster->city_id),
				'order' =>  0,
				'hidden' =>  (isset($Locationcluster->status) && $Locationcluster->status == "1")  ?  false : true,
				'created_at' =>  (isset($Locationcluster->created_at)) ? $Locationcluster->created_at : $Locationcluster->updated_at,
				'updated_at' =>  $Locationcluster->updated_at
				];

				$entity 		=	new Locationcluster($insertData);
				$entity->setConnection('mongodb2');
				$entity->_id 	=	intval($Locationcluster->_id);
				$entity->save();
			}
		}//ids
		
	}


	/**
	 * Migration for categorys
	 */
	public function category(){
		$ids	=	Findercategory::active()->take(300)->lists('_id');

		if($ids){ 
			DB::connection('mongodb2')->table('vendorcategories')->truncate(); 
			foreach ($ids as $key => $id) {
				$findercategory = Findercategory::find(intval($id));
				$insertData = [
				'name' =>  trim($findercategory->name),
				'slug' =>  trim($findercategory->slug),
				'detail_rating' =>  $findercategory->detail_rating,
				'seo' 	=>  [
				'title' 	=>  ($findercategory->meta['title']) ? strip_tags(trim($findercategory->meta['title'])) : "",
				'description' 	=>  ($findercategory->meta['description']) ? strip_tags(trim($findercategory->meta['description'])) : "",
				'keywords' 	=>  (isset($findercategory->meta['keywords']) && $findercategory->meta['keywords'] != "") ? strip_tags(trim($findercategory->meta['keywords'])) : ""
				],
				'order' =>  intval($findercategory->ordering),
				'hidden' =>  ($findercategory->status == "1") ? false : true,
				'created_at' =>  (isset($findercategory->created_at)) ? $findercategory->created_at : $findercategory->updated_at,
				'updated_at' =>  $findercategory->updated_at
				];

				if(isset($findercategory->cities) && count($findercategory->cities) > 0){

					foreach ($findercategory->cities as $key => $value) {
						// return $newcity 				=	DB::connection('mongodb2')->table('cities')->where('_id', intval($value))->first();
						$newcity 				=	DB::connection('mongodb2')->table('cities')->where('_id', intval($value))->first();
						// return $newcity['vendorcategories'];
						$vendorcategories 		= 	[];
						if(isset($newcity['vendorcategories'])) {
							// echo "<br> exist";	
							$vendorcategories 		= $newcity['vendorcategories'];
							array_push($vendorcategories, $findercategory->_id);
						}else{
							// echo "<br> not exist";	
							array_push($vendorcategories, $findercategory->_id);
						}

						echo  "<br> $value : ";print_r($vendorcategories);
						$updatecity 		=	DB::connection('mongodb2')->table('cities')->where('_id', intval($value))->update(array('vendorcategories' => array_map('intval', array_unique($vendorcategories)) )); 
					}
				}

				
				// merging findercategorytag
				if($findercategory->slug){
					$findercategorytag 	= 	Findercategorytag::where('slug',trim($findercategory->slug))->first();

					if($findercategorytag){
						$insertData['cities'] = (isset($findercategorytag->cities)) ? array_map('intval', $findercategorytag->cities) : [];
						$insertData['vendors'] = (isset($findercategorytag->finders)) ? array_map('intval', $findercategorytag->finders) : [];
						$insertData['offering_header'] = (isset($findercategorytag->offering_header)) ? trim($findercategorytag->offering_header) : "";

					}else{
						echo "<br> Does not exist " .$findercategory->slug;
					}
				}

				$entity 		=	new Vendorcategory($insertData);
				$entity->_id 	=	intval($findercategory->_id);
				$entity->save();
			}
		}//ids
		
	}


	/**
	 * Migration for locations
	 */
	public function location(){
		$ids	=	Location::active()->take(300)->lists('_id');

		if($ids){ 
			DB::connection('mongodb2')->table('vendorlocations')->truncate(); 

			foreach ($ids as $key => $id) {
				$location = Location::find(intval($id));

				$insertData = [
				'name' =>  trim($location->name),
				'slug' =>  trim($location->slug),
				// 'cities' =>  array_map('intval', $location->cities),
				'city_id' =>  intval($location->cities[0]),
				'location_group' =>  trim($location->location_group),
				'order' =>  count($location->ordering),
				'hidden' =>  ($location->status == "1") ? false : true,
				'created_at' =>  (isset($location->created_at)) ? $location->created_at : $location->updated_at,
				'updated_at' =>  $location->updated_at
				];

				if($location->lat != "" && $location->lon != "" ){
					$insertData['geometry'] = [
					'type' 	=> "Point",
					'coordinates' 	=>  [$location->lat, $location->lon],
					];
				}

				// merging locationtag
				if($location->slug){
					$locationtag 	= 	Locationtag::where('slug',trim($location->slug))->first();

					if($locationtag){
						$insertData['vendors'] = (isset($locationtag->finders)) ? array_map('intval', $locationtag->finders) : [];
					}else{
						echo "<br> Does not exist " .$location->slug;
					}
				}


				$entity 		=	new Vendorlocation($insertData);
				$entity->_id 	=	intval($location->_id);
				echo $entity->_id;
				$entity->save();

				// if(isset($location->cities) && count($location->cities) > 0){

				// 	foreach ($location->cities as $key => $value) {
				// 		// return $newcity 				=	DB::connection('mongodb2')->table('cities')->where('_id', intval($value))->first();
				// 		$newcity 				=	DB::connection('mongodb2')->table('cities')->where('_id', intval($value))->first();
				// 		// return $newcity['vendorlocations'];
				// 		$vendorlocations 		= 	[];
				// 		if(isset($newcity['vendorlocations'])) {
				// 			// echo "<br> exist";	
				// 			$vendorlocations 		= $newcity['vendorlocations'];
				// 			array_push($vendorlocations, $location->_id);
				// 		}else{
				// 			// echo "<br> not exist";	
				// 			array_push($vendorlocations, $location->_id);
				// 		}

				// 		echo  "<br> $value : ";print_r($vendorlocations);
				// 		$updatecity 		=	DB::connection('mongodb2')->table('cities')->where('_id', intval($value))->update(array('vendorlocations' => array_map('intval', array_unique($vendorlocations)) )); 
				// 	}
				// }

			}
		}//ids
		
	}



	/**
	 * Migration for offernigs
	 */
	public function offerings(){
		$ids	=	Offering::active()->take(300)->lists('_id');

		if($ids){ 
			DB::connection('mongodb2')->table('offerings')->truncate(); 

			foreach ($ids as $key => $id) {
				$offering = Offering::find(intval($id));

				$findercategorytag 	= 	Findercategorytag::where('_id',intval($offering->categorytag_id))->first();
				$findercategory 	= 	Vendorcategory::where('slug',trim($findercategorytag->slug))->first();
				

				// if offering already exists thn merge to previous once

				if($findercategory){
					$insertData = [
					'name' =>  trim($offering->name),
					'slug' =>  url_slug([$offering->name]),
					'vendorcategories' =>  [ intval($findercategory->_id) ],
					'vendors' => (isset($offering->finders)) ? array_map('intval', $offering->finders) : [],
					'order' =>  intval($offering->ordering),
					'hidden' =>  ($offering->status == "1") ? false : true,
					'created_at' =>  (isset($offering->created_at)) ? $offering->created_at : $offering->updated_at,
					'updated_at' =>  $offering->updated_at
					];

					$entity 		=	new Offering($insertData);
					$entity->setConnection('mongodb2');
					$entity->_id 	=	intval($offering->_id);
					$entity->save();
				}


			}
		}//ids
		
	}


	/**
	 * Migration for facilities
	 */
	public function facilities(){
		$ids	=	Facility::active()->take(1)->lists('_id');

		if($ids){ 
			DB::connection('mongodb2')->table('facilities')->truncate(); 

			foreach ($ids as $key => $id) {
				$facility = Facility::find(intval($id));

				$insertData = [
				'name' =>  trim($facility->name),
				'slug' =>  trim($facility->slug),
				'vendorcategories' =>  [ ],
				'vendors' => (isset($facility->finders)) ? array_map('intval', $facility->finders) : [],
				'order' =>  0,
				'hidden' =>  ($facility->status == "1") ? false : true,
				'created_at' =>  (isset($facility->created_at)) ? $facility->created_at : $facility->updated_at,
				'updated_at' =>  $facility->updated_at
				];

				$entity 		=	new Facility($insertData);
				$entity->setConnection('mongodb2');
				$entity->_id 	=	intval($facility->_id);
				$entity->save();
			}
		}//ids
		
	}

	public function vendorPriceAverage(){

		$finders = Finder::where('city_id',1)->where('commercial_type', 1)->where('status', '1')->take(3000)->skip(0)->get();
		$fp = fopen('averagePriceMumbai.csv', 'w');
		$header =    ["Finder_name", "Finder_id", "Average_monthly", "Total_price", "Total_days", "Number_of_Ratecards"];
		fputcsv($fp, $header);	
		foreach ($finders as $finder) {
			
			$ratecard_days = 0; $ratecard_money = 0;
			$services = Ratecard::where('finder_id', intval($finder['id']))->get();
			$ratecard_count = 0;
			foreach ($services as $service) {
				
				// $ratecard_days = $ratecard_days + $service['validity'];
				// $ratecard_money = $ratecard_money + $service['price'];
				//	echo $service['price'];
				switch($service['validity']){
					case 30:
					$ratecard_count = $ratecard_count + 1;
					$ratecard_money = $ratecard_money + intval($service['price']);
					break;
					case 90:
					$ratecard_count = $ratecard_count + 1;
					$average_one_month = intval($service['price'])/3;
					$ratecard_money = $ratecard_money + $average_one_month;
					break;
					case 120:
					$ratecard_count = $ratecard_count + 1;
					$average_one_month = intval($service['price'])/4;
					$ratecard_money = $ratecard_money + $average_one_month;
					break;
					case 180:
					$ratecard_count = $ratecard_count + 1;
					$average_one_month = intval($service['price'])/6;
					$ratecard_money = $ratecard_money + $average_one_month;
					break;
					case 360:
					$ratecard_count = $ratecard_count + 1;
					$average_one_month = intval($service['price'])/12;
					$ratecard_money = $ratecard_money + $average_one_month;
					break;
				}
			// 	$ratecards = $service['ratecards'];
			// 	if(!is_null($ratecards)){
			// 					foreach ($ratecards as $ratecard) {
			// 	if(array_key_exists('days', $ratecard)&&array_key_exists('price', $ratecard)){
			// 						// if(is_null($ratecard['days']) && is_null(var))
			// 		$ratecard_days = $ratecard_days + isset($ratecard['days']) ? intval($ratecard['days']) :0;
			// 		$ratecard_money = $ratecard_money + isset($ratecard['price']) ? intval($ratecard['price']) : 0;
			// 	}
			// }
			// }
				echo $ratecard_money.'</br>';
			}

			$average_monthly = 0;
			if(($ratecard_count !==0)){

				$average_monthly = ($ratecard_money) / ($ratecard_count);
			}
			$fields = [$finder['title'], $finder['id'], $average_monthly, $ratecard_money , $ratecard_days, $ratecard_count];

			fputcsv($fp, $fields);
		}
		fclose($fp);
  //return 'done';
		return Response::make(rtrim('averagePriceMumbai.csv', "\n"), 200, $header);

	}




	/**
	 * Migration for vendors
	 */
	public function vendors(){

		ini_set('max_execution_time', 300);

		$finder_ids	=	Finder::take(10000)->lists('_id');
		// $finder_ids	=	Finder::where('_id',1)->lists('_id');

		if($finder_ids){ 

			DB::connection('mongodb2')->table('vendors')->truncate(); 

			$commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'freespecial', 3 => 'cos');
			$business_type_arr = array( 0 => 'noninfrastructure', 1 => 'infrastructure');
			$finder_type_arr = array( 0 => 'free', 1 => 'paid');

			foreach ($finder_ids as $key => $finder_id) {

				$finder = Finder::find(intval($finder_id));

				if($finder){
					$insertedid 		= Vendor::max('_id') + 1;
					$commercial_type 	= $commercial_type_arr[intval($finder->commercial_type)];
					$business_type 		= $business_type_arr[intval($finder->business_type)];
					$vendor_type 		= $finder_type_arr[intval($finder->finder_type)];

					$email = $phone = $point_of_contact = $images = $videos = $services_arr = [];
					$line1 = $line2 = $line3 = "";

					//temp_finder_vcc_email_arr temp_finder_vcc_mobile_arr
					$temp_point_of_contact_arr = [];
					if(isset($finder->finder_poc_for_customer_name) || isset($finder->finder_poc_for_customer_mobile)){
						$point_of_contact_arr = [
						'name'  =>  (isset($finder->finder_poc_for_customer_name)) ? trim($finder->finder_poc_for_customer_name) : "",
						'email'  =>   "",
						'mobile'  =>  (isset($finder->finder_poc_for_customer_mobile)) ? trim($finder->finder_poc_for_customer_mobile) : "",
						'landline'  =>  "",
						'used_for'  =>  ["point_of_contact_customer"]
						];
						array_push($temp_point_of_contact_arr, $point_of_contact_arr);
					}

					$temp_finder_vcc_email_arr  	=  (isset($finder->finder_vcc_email)) ? explode(",", str_replace("/", ",", trim($finder->finder_vcc_email)) ) : [];
					$temp_finder_vcc_mobile_arr  	=  (isset($finder->finder_vcc_mobile)) ? explode(",", str_replace("/", ",", trim($finder->finder_vcc_mobile)) ) : [];
					$finder_vcc_email_cnt 			=  count($temp_finder_vcc_email_arr);
					$finder_vcc_mobile_cnt 			=  count($temp_finder_vcc_mobile_arr);

					for ($i=0; $i < max([intval($finder_vcc_email_cnt),intval($finder_vcc_mobile_cnt)]); $i++) { 
						$point_of_contact_arr = [
						'name'  =>  "",
						'mobile'  =>  "",
						'landline'  =>  "",
						'email'  =>   (isset($temp_finder_vcc_email_arr[$i])) ? trim($temp_finder_vcc_email_arr[$i]) : "",
						'used_for'  =>  ["vendor_communication_contact"]
						];

						if(isset($temp_finder_vcc_mobile_arr[$i])  && $temp_finder_vcc_mobile_arr[$i] != "" ){
							$varx = $temp_finder_vcc_mobile_arr[$i];
							if(starts_with($varx, '02') || starts_with($varx, '2') || starts_with($varx, '33') || starts_with($varx, '011') || starts_with($varx, '1') || starts_with($varx, '11')){
								$point_of_contact_arr['landline'] = ltrim($varx,"+");
							}else{
								$find_arr= ["+","+(91)-","(91)","(91)-","91-"];
								$replace_arr= ["","","","",""];
								$clean_mobile_no = trim(str_replace($find_arr, $replace_arr, $varx));
								$point_of_contact_arr['mobile'] = ltrim($clean_mobile_no,"+");
							}
						}
						array_push($temp_point_of_contact_arr, $point_of_contact_arr);

					} // temp_finder_vcc_email temp_finder_vcc_mobile_arr


					if(isset($finder->contact['email']) && $finder->contact['email'] != ""){
						$email = 	array_map('trim', explode(",",str_replace("/", ",", trim($finder->contact['email']) )) );
					}

					if(isset($finder->contact['phone']) && $finder->contact['phone'] != ""){
						$phone_arr = 	array_map('trim', explode(",",str_replace("/", ",", trim($finder->contact['phone']) )) ) ;
						$phone['mobile'] =  $phone['landline'] =  [];
						
						if(count($phone_arr) > 0){
							foreach ($phone_arr as $key => $value) {
								$varx = $value;
								if(starts_with($varx, '02') || starts_with($varx, '2') || starts_with($varx, '33') || starts_with($varx, '011') || starts_with($varx, '1') || starts_with($varx, '11')){
									array_push($phone['landline'], ltrim($varx,"+"));
								}else{
									$find_arr= ["+","+(91)-","(91)","(91)-","91-"];
									$replace_arr= ["","","","",""];
									$clean_mobile_no = trim(str_replace($find_arr, $replace_arr, $varx));
									array_push($phone['mobile'], ltrim($clean_mobile_no,"+"));
								}
							}
						}

					}

					// dd($phone);


					if(isset($finder->contact['address']) && $finder->contact['address'] != ""){

						$temp_address_arr = explode(",", str_replace("&amp;", "&",str_replace("&nbsp;", "", strip_tags( $finder->contact['address'] ))) );
						$temp_address_arr_cnt = count($temp_address_arr);
						$count = $temp_address_arr_cnt / 3;
						$line1 = rtrim(implode(array_slice($temp_address_arr, 0, $count)), ",");
						$line2 = rtrim(implode(array_slice($temp_address_arr, $count * 1, $count * 2)), ",");
						$line3 = rtrim(implode(array_slice($temp_address_arr, $count * 2, $count * 3)), ",");
						// echo "<pre>"; print_r($temp_address_arr); echo $line1; exit();
					}

					if(isset($finder->info['service']) && $finder->info['service'] != ""){
						$services_arr_tmp 	= 	array_map('trim', explode(",", strip_tags(str_replace("</li>", ",", trim($finder->info['service']) ))) ) ;
						$services_arr 		= 	[] ;

						foreach ($services_arr_tmp as $kk => $vv) {
							if(!empty($vv) || $vv != ""){
								array_push($services_arr, $vv);
							}
						}
					}

					$images['cover']  	=	$finder->coverimage;
					$images['logo']  	= 	$finder->logo;
					$images['gallery']  = 	($finder->photos) ? $finder->photos : [];
					$videos       		= 	($finder->videos) ? $finder->videos : [];
					



					//for locationtags
					$new_locationtag_ids_arr	= [];
					if(isset($finder->locationtags) && !empty($finder->locationtags)){
						$old_locationtag_slugs_arr	=	Locationtag::whereIn('_id', array_map('intval', $finder->locationtags))->lists('slug');
						$new_locationtag_ids_arr	=	Vendorlocation::whereIn('slug', $old_locationtag_slugs_arr)->lists('_id');
						// dd($new_locationtag_ids_arr);
					}

					//for categorytags
					$new_categorytag_ids_arr	= [];
					if(isset($finder->categorytags) && !empty($finder->categorytags)){
						$old_categorytag_slugs_arr	=	Findercategorytag::whereIn('_id', array_map('intval', $finder->categorytags))->lists('slug');
						$new_categorytag_ids_arr	=	Vendorcategory::whereIn('slug', $old_categorytag_slugs_arr)->lists('_id');
					}


					//for offerings
					$old_offering_slugs_arr	= [];
					if(isset($finder->offerings) && !empty($finder->offerings)){
						$old_offering_name_arr		=	Offering::whereIn('_id', array_map('intval', $finder->offerings))->lists('name');
						$old_offering_slugs_arr		=	[];
						foreach ($old_offering_name_arr as $key => $value) {
							array_push($old_offering_slugs_arr, url_slug([$value]));
						}
						$new_offering_ids_arr	=	DB::connection('mongodb2')->table('offerings')->whereIn('slug', $old_offering_slugs_arr)->lists('_id');
					}

					// $rating[]			
					$vendorData = [
					'name' =>  trim($finder->title),
					'slug' =>  trim($finder->slug),
					'country_id' =>  intval($finder->country_id),
					'city_id' 	=>  intval($finder->city_id),
					'location' 	=>  [ 'primary' 	=>  intval($finder->location_id), 'secondary' =>  array_map('intval', array_unique($new_locationtag_ids_arr)) ],
					'category' 	=>  [ 'primary' 	=>  intval($finder->category_id), 'secondary' =>  array_map('intval', array_unique($new_categorytag_ids_arr)) ],
					// 'filter' 	=>  [ 'primary' =>  array_map('intval', $finder->facilities), 'secondary' =>  array_map('intval', array_unique($new_offering_ids_arr)) ],
					'facilities' 	=>  array_map('intval', $finder->facilities),
					'offerings' 	=>  array_map('intval', array_unique($new_offering_ids_arr)),
					// 'types' 	=>  [ 'commercials' =>  $commercial_type, 'business' =>  $business_type, 'vendor' =>  $vendor_type ],
					'types' 	=>  [ 'commercials' =>  $commercial_type, 'business' =>  $business_type],
					'contact' 	=>  ['email' =>  $email, 'phone' =>  $phone, 'point_of_contact' =>  $temp_point_of_contact_arr],
					'media' 	=>  [
					'images' =>  $images,
					'videos' =>  $videos
					],
					'info' 	=>  [
					'about' 	=>  ($finder->info['about']) ? trim($finder->info['about']) : "",
					'additional_info' 	=>  ($finder->info['additional_info']) ? trim($finder->info['additional_info']) : "",
					'timing' 	=>  ($finder->info['timing']) ? trim($finder->info['timing']) : "",
					'service' 	=>  $services_arr
					],
					'address' 	=>  [
					'line1' 	=>  trim($line1),
					'line2' 	=>  trim($line2),
					'line3' 	=>  trim($line3),						
					'pincode' 	=>  "",
					'landmark' 	=>  ($finder->landmark) ? strip_tags($finder->landmark) : ""
					],
					'geometry' => [
					'type' 	=> "Point",
					'coordinates' 	=>  [$finder->lon, $finder->lat],
					],
					'rating' 	=>  [
					'avg' =>  $finder->average_rating,
					'count' =>  intval($finder->total_rating_count)
					],
					'detail_rating' =>  [
					'avg' =>    (isset($finder->detail_rating_summary_average)) ? $finder->detail_rating_summary_average : [],
					'count' =>  (isset($finder->detail_rating_summary_count))   ?  array_map('intval', $finder->detail_rating_summary_count) : []
					],
					'seo' 	=>  [
					'title' 	=>  ($finder->meta['title']) ? str_replace("&amp;", "&",str_replace("&nbsp;", "", strip_tags($finder->meta['title']))) : "",
					'description' 	=>  ($finder->meta['description']) ? str_replace("&amp;", "&",str_replace("&nbsp;", "", strip_tags($finder->meta['description']))) : "",
					'keywords' 	=>  (isset($finder->meta['keywords']) && $finder->meta['keywords'] != "") ? str_replace("&amp;", "&",str_replace("&nbsp;", "", strip_tags($finder->meta['keywords']))) : "",
					'og_title' 	=>  "",
					'og_description' 	=>   "",
					'og_image' 	=>   ""
					],
					'hidden' =>  ($finder->status == "1") ? false : true,
					'order' =>  0,
					'created_at' =>  (isset($finder->created_at)) ? $finder->created_at : $finder->updated_at,
					'updated_at' =>  $finder->updated_at
					];

					if($finder->what_i_should_carry){

						
						$vendorData ['what_i_should_carry'] = str_replace("&amp;", "&",str_replace("&nbsp;", "", strip_tags($finder->what_i_should_carry)));
					}

					if($finder->what_i_should_expect){
						$vendorData ['what_i_should_expect'] = str_replace("&amp;", "&",str_replace("&nbsp;", "", strip_tags($finder->what_i_should_expect)));
					}



					$vendor 		=	new Vendor($vendorData);
					$vendor->_id 	=	intval($finder->_id);
					$vendor->save();
				}




			}

		}//finder_ids

	}






	/**
	 * Migration for vendorservicecategory
	 */
	public function vendorservicecategory(){
		$ids	=	Servicecategory::active()->take(1000)->lists('_id');

		if($ids){ 
			DB::connection('mongodb2')->table('vendorservicecategories')->truncate(); 

			foreach ($ids as $key => $id) {
				$servicecategory = Servicecategory::find(intval($id));

				$insertData = [
				'name' =>  trim($servicecategory->name),
				'slug' =>  trim($servicecategory->slug),
				'parent_id' =>  intval($servicecategory->parent_id),
					// 'parent_name' =>  trim($servicecategory->parent_name),
				'description' =>  trim($servicecategory->description),
				'what_i_should_carry' =>  trim($servicecategory->what_i_should_carry),
				'what_i_should_expect' =>  trim($servicecategory->what_i_should_expect),
				'seo' 	=>  [
				'title' 	=>  ($servicecategory->meta['title']) ? strip_tags(trim($servicecategory->meta['title'])) : "",
				'description' 	=>  ($servicecategory->meta['description']) ? strip_tags(trim($servicecategory->meta['description'])) : "",
				'keywords' 	=>  (isset($servicecategory->meta['keywords']) && $servicecategory->meta['keywords'] != "") ? strip_tags(trim($servicecategory->meta['keywords'])) : "",
				'og_title' 	=>  "",
				'og_description' 	=>   "",
				'og_image' 	=>   ""
				],
				'order' =>  0,
				'hidden' =>  ($servicecategory->status == "1") ? false : true,
				'created_at' =>  (isset($servicecategory->created_at)) ? $servicecategory->created_at : $servicecategory->updated_at,
				'updated_at' =>  $servicecategory->updated_at
				];

				$entity 		=	new Vendorservicecategory($insertData);
				$entity->setConnection('mongodb2');
				$entity->_id 	=	intval($servicecategory->_id);
				$entity->save();
			}
		}//ids
		
	}

	public function migratedatatomoenagage(){


		try{

			$dt1 =new DateTime("2015-05-01 11:14:15.638276");



			$all_users = Customer::where('created_at', '>', $dt1)->get(array('email'));

			$user_emails_list = array();

			foreach ($all_users as $user) {

			//fetch payment/conversion for each users and push to moengage
				echo json_encode($user);
				$bool_exist = array_search($user['email'], $user_emails_list);
			
				if($bool_exist !== false){				
					continue;
				}
				array_push($user_emails_list, $user['email']);

			

				$user_trials_booked = Booktrial::where('customer_email', $user['email'])->get();


				$capture = Capture::where('customer_email', $user['email'])->get();


				$user_reviews_written = Review::where('cust_id', intval($user['_id']))->get();

				$attr_phone = isset($user['contact_no']) ? $user['contact_no'] : 0;
				$attr_email = isset($user['email']) ? $user['email'] : '';
				$attr_gender = isset($user['gender']) ? $user['gender'] : '';
				$attr_name = isset($user['name']) ? $user['name'] : '';

				$create_user_payload = '{
					"type":"customer",
					"customer_id" : "'.$user['email'].'",
					"attributes" : {
						"name" :"'.$attr_name.'",
						"phone":'.$attr_phone.',
						"email": "'.$attr_email.'",
						"gender" : "'.$attr_gender.'"
					}

				}';

				//hit moengage to add attributed data for user in moenagage data base
				$curlrequestor = curl_init();
				curl_setopt($curlrequestor, CURLOPT_TIMEOUT, 2000);
				curl_setopt($curlrequestor, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curlrequestor, CURLOPT_FORBID_REUSE, 0);
		
				curl_setopt($curlrequestor, CURLOPT_CUSTOMREQUEST, 'POST');	
				curl_setopt($curlrequestor, CURLOPT_URL, 'https:// W7WD7K4O8B2NE3LAI1DTG0LD: W7WD7K4O8B2NE3LAI1DTG0LD@api.moengage.com/v1/customer?app_id=W7WD7K4O8B2NE3LAI1DTG0LD');

				
				$headers[] = 'Authorization: Basic VzdXRDdLNE84QjJORTNMQUkxRFRHMExEOk01cmtGdDFzM3VGQTgyMWxkYXlWZW9OMQ==';
				
				
				curl_setopt($curlrequestor, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curlrequestor, CURLOPT_POSTFIELDS, $create_user_payload);	
				
				//$res = curl_exec($curlrequestor);
				$response = json_decode($res, true);		
				
			
				$user_orders = Order::where('customer_email', $user['email'])->get();
				$order_actions = '';
			
				foreach ($user_orders as $order) {
					
					$user_time = strtotime($order['created_at']);

					$current_time = time();


					

					$city_id = isset($order['city_id']) ? $order['city_id'] : 0;
					$phone = isset($order['customer_phone']) ? $order['customer_phone'] : '';
					$finder_id = isset($order['finder_id']) ? $order['finder_id'] : 0;
					$finder_name = isset($order['finder_name']) ? $order['findr_name'] : '';
					$service_id = isset($order['service_id']) ? $order['service_id'] : '';
					$service_name = isset($order['service_name']) ? $order['service_name'] : '';
					$type = isset($order['type']) ? $order['type'] : '';

					$customer_orders_payload = '{
						"action": "paymentsuccess",
						"attributes": {
							"email"  : "'.$user['email'].'",
							"phone"  : "'.$phone.'",
							"finder_id"  : '.$finder_id.',
							"finder_name" : "'.$finder_name.'",
							"service_id" : '.$service_id.',
							"service_name" : "'.$service_name.'",
							"type" : "'.$type.'",
							"city_id" : '.$city_id.'
						},
						"platform" : "web",						
						"user_time" : '.$user_time.',
						"current_time" : '.$current_time.'
					},';

					$order_actions = $order_actions.$customer_orders_payload;
					
				}

				

				// $trial_book_actions = '';


				// foreach ($user_trials_booked as $trial) {

				// 	$user_time = strtotime($trial['created_at']);

				// 	$current_time = time();
				// 	$city_id = isset($trial['city_id']) ? $trial['city_id'] ? 0;
				// 	$phone = isset($trial['customer_phone']) ? $trial['customer_phone'] : '';
				// 	$finder_id = isset($trial['finder_id']) ? $trial['finder_id'] : 0;
				// 	$finder_name = isset($trial['finder_name']) ? $trial['finder_name'] : '';
				// 	$service_id = isset($trial['service_id']) ? $trial['service_id'] : '';
				// 	$service_name = isset($trial['service_name']) ? $trial['service_name'] : '';
				// 	$type = isset($trial['type']) ? $trial['type'] : '';
				// 	$type1 = isset($trial['booktrial_type']) ? $trial['booktrial_type'] : '';
				// 	$schedule_date = isset($trial['schedule_date']) ? $trial['schedule_date'] : '';
				// 	$schedule_slot = isset($trial['schedule_slot']) ? $trial['schedule_slot'] : '';


				// 	$customer_orders_payload = '{
				// 		"action": "trialsuccess",
				// 		"attributes": {
				// 			"email"  : "'.$user['email'].'",
				// 			"phone"  : "'.$phone.'",
				// 			"finder_id"  : '.$finder_id.',
				// 			"finder_name" : "'.$finder_name.'",
				// 			"service_id" : '.$service_id.',
				// 			"service_name" : "'.$service_name.'",
				// 			"type" : "'.$type.'",
				// 			"city_id" : '.$city_id.',
				// 			"schedule_slot" : "'.$schedule_slot.'",
				// 			"schedule_date" : "'.$schedule_date.'",
				// 			"type1" : "'.$type1.'"
				// 		},
				// 		"platform" : "web",						
				// 		"user_time" : '.$user_time.',
				// 		"current_time" : '.$current_time.'
				// 	},';

				// 	$trial_book_actions = $trial_book_actions.$customer_trials_payload;

				// }



				// //send moengage request to push the transactional data to there database

				// $total_payload = trim($order_actions.$trial_book_actions,',');

				// $request_payload = '{
				// 	"type": "event",
				// 	"customer_id": "525689553535239acbfe",
				// 	"device_id" : "12345",
				// 	"actions" : [
				// 	'.$total_payload.'
				// 	]
				// }';


				// $request = array(
				// 	'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity_finder/finder/_search",
				// 	'port' => 8050,
				// 	'method' => 'POST',
				// 	'postfields' => $request_payload
				// 	);


				// $search_results     =   es_curl_request($request);








			}



			
		//migrate all transactional data:









		//migrate all attribute data











		//migrate all the 








		//migrate all user searched, favourite categories



	public function migratedatatomoenagage(){


		try{

			$dt1 =new DateTime("2015-05-01 11:14:15.638276");



			$all_users = Customer::where('created_at', '>', $dt1)->get(array('email'));

			$user_emails_list = array();

			foreach ($all_users as $user) {

			//fetch payment/conversion for each users and push to moengage
				echo json_encode($user);
				$bool_exist = array_search($user['email'], $user_emails_list);
			
				if($bool_exist !== false){				
					continue;
				}
				array_push($user_emails_list, $user['email']);

			

				$user_trials_booked = Booktrial::where('customer_email', $user['email'])->get();


				$capture = Capture::where('customer_email', $user['email'])->get();


				$user_reviews_written = Review::where('cust_id', intval($user['_id']))->get();

				$attr_phone = isset($user['contact_no']) ? $user['contact_no'] : 0;
				$attr_email = isset($user['email']) ? $user['email'] : '';
				$attr_gender = isset($user['gender']) ? $user['gender'] : '';
				$attr_name = isset($user['name']) ? $user['name'] : '';

				$create_user_payload = '{
					"type":"customer",
					"customer_id" : "'.$user['email'].'",
					"attributes" : {
						"name" :"'.$attr_name.'",
						"phone":'.$attr_phone.',
						"email": "'.$attr_email.'",
						"gender" : "'.$attr_gender.'"
					}

				}';

				//hit moengage to add attributed data for user in moenagage data base
				$curlrequestor = curl_init();
				curl_setopt($curlrequestor, CURLOPT_TIMEOUT, 2000);
				curl_setopt($curlrequestor, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curlrequestor, CURLOPT_FORBID_REUSE, 0);
		
				curl_setopt($curlrequestor, CURLOPT_CUSTOMREQUEST, 'POST');	
				curl_setopt($curlrequestor, CURLOPT_URL, 'https:// W7WD7K4O8B2NE3LAI1DTG0LD: W7WD7K4O8B2NE3LAI1DTG0LD@api.moengage.com/v1/customer?app_id=W7WD7K4O8B2NE3LAI1DTG0LD');

				
				$headers[] = 'Authorization: Basic VzdXRDdLNE84QjJORTNMQUkxRFRHMExEOk01cmtGdDFzM3VGQTgyMWxkYXlWZW9OMQ==';
				
				
				curl_setopt($curlrequestor, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curlrequestor, CURLOPT_POSTFIELDS, $create_user_payload);	
				
				//$res = curl_exec($curlrequestor);
				$response = json_decode($res, true);		
				
			
				$user_orders = Order::where('customer_email', $user['email'])->get();
				$order_actions = '';
			
				foreach ($user_orders as $order) {
					
					$user_time = strtotime($order['created_at']);

					$current_time = time();


					

					$city_id = isset($order['city_id']) ? $order['city_id'] : 0;
					$phone = isset($order['customer_phone']) ? $order['customer_phone'] : '';
					$finder_id = isset($order['finder_id']) ? $order['finder_id'] : 0;
					$finder_name = isset($order['finder_name']) ? $order['findr_name'] : '';
					$service_id = isset($order['service_id']) ? $order['service_id'] : '';
					$service_name = isset($order['service_name']) ? $order['service_name'] : '';
					$type = isset($order['type']) ? $order['type'] : '';

					$customer_orders_payload = '{
						"action": "paymentsuccess",
						"attributes": {
							"email"  : "'.$user['email'].'",
							"phone"  : "'.$phone.'",
							"finder_id"  : '.$finder_id.',
							"finder_name" : "'.$finder_name.'",
							"service_id" : '.$service_id.',
							"service_name" : "'.$service_name.'",
							"type" : "'.$type.'",
							"city_id" : '.$city_id.'
						},
						"platform" : "web",						
						"user_time" : '.$user_time.',
						"current_time" : '.$current_time.'
					},';

					$order_actions = $order_actions.$customer_orders_payload;
					
				}

				

				// $trial_book_actions = '';


				// foreach ($user_trials_booked as $trial) {

				// 	$user_time = strtotime($trial['created_at']);

				// 	$current_time = time();
				// 	$city_id = isset($trial['city_id']) ? $trial['city_id'] ? 0;
				// 	$phone = isset($trial['customer_phone']) ? $trial['customer_phone'] : '';
				// 	$finder_id = isset($trial['finder_id']) ? $trial['finder_id'] : 0;
				// 	$finder_name = isset($trial['finder_name']) ? $trial['finder_name'] : '';
				// 	$service_id = isset($trial['service_id']) ? $trial['service_id'] : '';
				// 	$service_name = isset($trial['service_name']) ? $trial['service_name'] : '';
				// 	$type = isset($trial['type']) ? $trial['type'] : '';
				// 	$type1 = isset($trial['booktrial_type']) ? $trial['booktrial_type'] : '';
				// 	$schedule_date = isset($trial['schedule_date']) ? $trial['schedule_date'] : '';
				// 	$schedule_slot = isset($trial['schedule_slot']) ? $trial['schedule_slot'] : '';


				// 	$customer_orders_payload = '{
				// 		"action": "trialsuccess",
				// 		"attributes": {
				// 			"email"  : "'.$user['email'].'",
				// 			"phone"  : "'.$phone.'",
				// 			"finder_id"  : '.$finder_id.',
				// 			"finder_name" : "'.$finder_name.'",
				// 			"service_id" : '.$service_id.',
				// 			"service_name" : "'.$service_name.'",
				// 			"type" : "'.$type.'",
				// 			"city_id" : '.$city_id.',
				// 			"schedule_slot" : "'.$schedule_slot.'",
				// 			"schedule_date" : "'.$schedule_date.'",
				// 			"type1" : "'.$type1.'"
				// 		},
				// 		"platform" : "web",						
				// 		"user_time" : '.$user_time.',
				// 		"current_time" : '.$current_time.'
				// 	},';

				// 	$trial_book_actions = $trial_book_actions.$customer_trials_payload;

				// }



				// //send moengage request to push the transactional data to there database

				// $total_payload = trim($order_actions.$trial_book_actions,',');

				// $request_payload = '{
				// 	"type": "event",
				// 	"customer_id": "525689553535239acbfe",
				// 	"device_id" : "12345",
				// 	"actions" : [
				// 	'.$total_payload.'
				// 	]
				// }';


				// $request = array(
				// 	'url' => "http://ESAdmin:fitternity2020@54.169.120.141:8050/"."fitternity_finder/finder/_search",
				// 	'port' => 8050,
				// 	'method' => 'POST',
				// 	'postfields' => $request_payload
				// 	);


				// $search_results     =   es_curl_request($request);












		}
		catch(Exception $e){

			Log::error($e);
		}

	}





		//migrate all the user vendor clicked data attributes












		//migrate all the data attributes regarding services for user









		//migrate all the data attributes for 





		}
		catch(Exception $e){

			Log::error($e);
		}

	}
}
