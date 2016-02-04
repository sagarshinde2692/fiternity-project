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
					'created_at' =>  $findercategory->created_at,
					'updated_at' =>  $findercategory->updated_at
				];
				
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
					'cities' =>  array_map('intval', $location->cities),
					'location_group' =>  trim($location->location_group),
					'order' =>  count($location->ordering),
					'hidden' =>  ($location->status == "1") ? false : true,
					'created_at' =>  $location->created_at,
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
				$entity->save();

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
						'created_at' =>  $offering->created_at,
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
					'created_at' =>  $facility->created_at,
					'updated_at' =>  $facility->updated_at
				];

				$entity 		=	new Facility($insertData);
				$entity->setConnection('mongodb2');
				$entity->_id 	=	intval($facility->_id);
				$entity->save();
			}
		}//ids
		
	}




	/**
	 * Migration for vendors
	 */
	public function vendors(){

		$finder_ids	=	Finder::active()->take(10)->lists('_id');

		if($finder_ids){ 

			DB::connection('mongodb2')->table('vendors')->truncate(); 

			$commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'freespecial', 3 => 'cos');
			$business_type_arr = array( 0 => 'infrastructure', 1 => 'noninfrastructure');
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

						$temp_address_arr = explode(",",strip_tags($finder->contact['address']));
						$temp_address_arr_cnt = count($temp_address_arr);
						$count = $temp_address_arr_cnt / 3;
						$line1 = rtrim(implode(array_slice($temp_address_arr, 0, $count)), ",");
						$line2 = rtrim(implode(array_slice($temp_address_arr, $count * 1, $count * 2)), ",");
						$line3 = rtrim(implode(array_slice($temp_address_arr, $count * 2, $count * 3)), ",");
						// echo "<pre>"; print_r($temp_address_arr); echo $line1; exit();
					}

					if(isset($finder->info['service']) && $finder->info['service'] != ""){
						$services_arr = 	array_map('trim', explode(",", strip_tags(str_replace("</li>", ",", trim($finder->info['service']) ))) ) ;
					}

					$images['cover']  	=	$finder->coverimage;
					$images['logo']  	= 	$finder->logo;
					$images['gallery']  = 	($finder->photos) ? $finder->photos : [];
					$videos       		= 	($finder->videos) ? $finder->videos : [];
					

					//for locationtags
					$old_locationtag_slugs_arr	=	Locationtag::active()->whereIn('_id', array_map('intval', $finder->locationtags))->lists('slug');
					$new_locationtag_ids_arr	=	Vendorlocation::whereIn('slug', $old_locationtag_slugs_arr)->lists('_id');

					//for categorytags
					$old_categorytag_slugs_arr	=	Findercategorytag::active()->whereIn('_id', array_map('intval', $finder->categorytags))->lists('slug');
					$new_categorytag_ids_arr	=	Vendorcategory::active()->whereIn('slug', $old_categorytag_slugs_arr)->lists('_id');

					//for offerings
					$old_offering_name_arr		=	Offering::active()->whereIn('_id', array_map('intval', $finder->offerings))->lists('name');
					$old_offering_slugs_arr		=	[];
					foreach ($old_offering_name_arr as $key => $value) {
						array_push($old_offering_slugs_arr, url_slug([$value]));
					}
					$new_offering_ids_arr	=	DB::connection('mongodb2')->table('offerings')->whereIn('slug', $old_offering_slugs_arr)->lists('_id');


					// $rating[]			
					$vendorData = [
					'name' =>  trim($finder->title),
					'slug' =>  trim($finder->slug),
					'country_id' =>  intval($finder->country_id),
					'city_id' 	=>  intval($finder->city_id),
					'location' 	=>  [ 'primary' 	=>  intval($finder->location_id), 'secondary' =>  array_map('intval', array_unique($new_locationtag_ids_arr)) ],
					'category' 	=>  [ 'primary' 	=>  intval($finder->category_id), 'secondary' =>  array_map('intval', array_unique($new_categorytag_ids_arr)) ],
					'filter' 	=>  [ 'primary' =>  array_map('intval', $finder->facilities), 'secondary' =>  array_map('intval', array_unique($new_offering_ids_arr)) ],
					'types' 	=>  [ 'commercials' =>  $commercial_type, 'business' =>  $business_type, 'vendor' =>  $vendor_type ],
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
						'coordinates' 	=>  [$finder->lat, $finder->lon],
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
						'title' 	=>  ($finder->meta['title']) ? strip_tags(trim($finder->meta['title'])) : "",
						'description' 	=>  ($finder->meta['description']) ? strip_tags(trim($finder->meta['description'])) : "",
						'keywords' 	=>  (isset($finder->meta['keywords']) && $finder->meta['keywords'] != "") ? strip_tags(trim($finder->meta['keywords'])) : "",
						'og_title' 	=>  "",
						'og_description' 	=>   "",
						'og_image' 	=>   ""
					],
					'hidden' =>  $finder->status,
					'order' =>  0,
					'created_at' =>  $finder->created_at,
					'updated_at' =>  $finder->updated_at
					];

					if($finder->what_i_should_carry){
						$vendorData ['what_i_should_carry'] = $finder->what_i_should_carry;
					}

					if($finder->what_i_should_expect){
						$vendorData ['what_i_should_expect'] = $finder->what_i_should_expect;
					}



					$vendor 		=	new Vendor($vendorData);
					$vendor->_id 	=	intval($finder->_id);
					$vendor->save();
				}




			}

		}//finder_ids

	}













}
