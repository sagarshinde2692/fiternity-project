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
	 * Return author detail.
	 */
	public function vendor(){

		$finder_ids	=	Finder::active()->take(100)->lists('_id');

		if($finder_ids){ 

			DB::connection('mongodb2')->table('finders')->truncate(); 

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

						switch ($temp_address_arr_cnt) {
							case 3:
							$line1 = $temp_address_arr[0];
							$line2 = $temp_address_arr[1];
							$line3 = $temp_address_arr[2];
							break;

							case 4:
							$line1 = $temp_address_arr[0]. ",". $temp_address_arr[1];
							$line2 = $temp_address_arr[2];
							$line3 = $temp_address_arr[3];
							break;

							case 5:
							$line1 = $temp_address_arr[0]. ",". $temp_address_arr[1];
							$line2 = $temp_address_arr[2]. ",". $temp_address_arr[3];
							$line3 = $temp_address_arr[4];
							break;

							case 6:
							$line1 = $temp_address_arr[0]. ",". $temp_address_arr[1];
							$line2 = $temp_address_arr[2]. ",". $temp_address_arr[3];
							$line3 = $temp_address_arr[4]. ",". $temp_address_arr[5];
							break;

							case 7:
							$line1 = $temp_address_arr[0]. ",". $temp_address_arr[1]. ",". $temp_address_arr[2];
							$line2 = $temp_address_arr[3]. ",". $temp_address_arr[4];
							$line3 = $temp_address_arr[5]. ",". $temp_address_arr[6];
							break;
							
							default:
							$line1 = implode($temp_address_arr);
							break;
						}
					}

					if(isset($finder->info['service']) && $finder->info['service'] != ""){
						$services_arr = 	array_map('trim', explode(",", strip_tags(str_replace("</li>", ",", trim($finder->info['service']) ))) ) ;
					}

					$images['cover']  	=	$finder->coverimage;
					$images['logo']  	= 	$finder->logo;
					$images['gallery']  = 	($finder->photos) ? $finder->photos : [];
					$videos       		= 	($finder->videos) ? $finder->videos : [];
					
					// $rating[]			
					$vendorData = [
					'name' =>  trim($finder->title),
					'slug' =>  trim($finder->slug),
					'country_id' =>  intval($finder->country_id),
					'city_id' 	=>  intval($finder->city_id),
					'locations' 	=>  [ 'primary' 	=>  intval($finder->location_id), 'secondary' =>  array_map('intval', $finder->locationtags) ],
					'categorys' 	=>  [ 'primary' 	=>  intval($finder->category_id), 'secondary' =>  array_map('intval', $finder->categorytags) ],
					'filters' 	=>  [ 'primary' =>  array_map('intval', $finder->facilities), 'secondary' =>  array_map('intval', $finder->offerings) ],
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
					'geo' 	=>  [ 'lat' =>  $finder->lat, 'long' =>  $finder->lon ],
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
						'ogtags_title' 	=>  "",
						'ogtags_description' 	=>   "",
						'ogtags_image' 	=>   ""
					],
					'hidden' =>  $finder->status,
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
