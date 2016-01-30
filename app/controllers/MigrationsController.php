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

		$finder_ids	=	Finder::active()->take(1)->lists('_id');

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

					$email = $phone = $point_of_contact = [];

					$vendorData = [
					'name' =>  trim($finder->title),
					'slug' =>  trim($finder->slug),
					'country_id' =>  intval($finder->country_id),
					'city_id' 	=>  intval($finder->city_id),
					'locations' 	=>  [
						'primary' 	=>  intval($finder->location_id),
						'secondary' =>  array_map('intval', $finder->locationtags),
					],
					'categorys' 	=>  [
						'primary' 	=>  intval($finder->category_id),
						'secondary' =>  array_map('intval', $finder->categorytags),
					],
					'filters' 	=>  [
						'primary' =>  array_map('intval', $finder->facilities),
						'secondary' =>  array_map('intval', $finder->offerings)
					],
					'types' 	=>  [
						'commercials' =>  $commercial_type,
						'business' =>  $business_type,
						'vendor' =>  $vendor_type
					],
					'contact' 	=>  [
						'email' =>  $email,
						'phone' =>  $phone,
						'point_of_contact' =>  $point_of_contact
					],
					'info' 	=>  [
						'about' 	=>  ($finder->info['about']) ? trim($finder->info['about']) : "",
						'additional_info' 	=>  ($finder->info['additional_info']) ? trim($finder->info['additional_info']) : "",
						'timing' 	=>  ($finder->info['timing']) ? trim($finder->info['timing']) : "",
						'service' 	=>  ($finder->info['service']) ? trim($finder->info['service']) : ""
					],
					'address' 	=>  [
						'line1' 	=>  ($finder->contact['address']) ? trim($finder->contact['address']) : "",
						'line2' 	=>  "",
						'line3' 	=>  "",
						'state' 	=>  "",
						'pincode' 	=>  "",
						'landmark' 	=>  ($finder->landmark) ? $finder->landmark : ""
						// 'keywords' 	=>  ($finder->meta['keywords']) ? trim($finder->meta['keywords']) : ""
					],
					'seo' 	=>  [
						'title' 	=>  ($finder->meta['title']) ? trim($finder->meta['title']) : "",
						'description' 	=>  ($finder->meta['description']) ? trim($finder->meta['description']) : "",
						// 'keywords' 	=>  ($finder->meta['keywords']) ? trim($finder->meta['keywords']) : ""
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
