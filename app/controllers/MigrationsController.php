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

			foreach ($finder_ids as $key => $finder_id) {

				$finder = Finder::find(intval($finder_id));

				if($finder){
					$insertedid = Vendor::max('_id') + 1;
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
