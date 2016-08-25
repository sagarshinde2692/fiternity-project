<?PHP

/**
 * ControllerName : MigrationReverseController.
 * Maintains a list of functions used for MigrationReverseController.
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

use App\Services\Cacheapi as Cacheapi;

class MigrationReverseController extends \BaseController {

    public $fitapi;
    public $fitadmin;
    protected $cacheapi;

    public function __construct(Cacheapi $cacheapi) {

        $this->fitapi = 'mongodb2';
        $this->fitadmin = 'mongodb';
        $this->cacheapi 	=	$cacheapi;

    }

    public function byId($collection,$id){

        switch ($collection) {

            case 'country' : $return = $this->country($id);break;
            case 'city' : $return = $this->city($id);break;
            case 'locationcluster' : $return = $this->locationcluster($id);break;
            case 'category' : $return = $this->category($id);break;
            case 'location' : $return = $this->location($id);break;
            case 'offering' : $return = $this->offering($id);break;
            case 'facility' : $return = $this->facility($id);break;
            case 'vendor' : $return = $this->vendor($id);break;
            case 'vendorservicecategory' : $return = $this->vendorservicecategory($id);break;
            case 'vendorservice' : $return = $this->vendorservice($id);break;
            case 'ratecard' : $return = $this->ratecard($id);break;
            case 'schedule' : $return = $this->schedule($id);break;
            case 'batch' : $return = $this->batch($id);break;

            default : $return = "no function found";break;
        }

        return $return;

    }

    /**
     * ReverseMigration for country
     */
    public function country($id){

        try{
            $country = Country::on($this->fitapi)->find(intval($id));

            $insertData = [
                'name' =>  trim($country->name),
                'slug' =>  trim($country->slug),
                'order' =>  0,
                'status' =>  (isset($country->hidden) && $country->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($country->created_at)) ? $country->created_at : $country->updated_at,
                'updated_at' =>  $country->updated_at
            ];

            $country_exists_cnt	=	DB::connection($this->fitadmin)->table('countries')->where('_id', intval($id) )->count();

            if($country_exists_cnt === 0){
                $entity 		=	new Country($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 	=	intval($country->_id);
                $entity->save();
            }else{
                // $country = Country::on($this->fitadmin)->where('_id', intval($id) )->update($insertData);
                $country = Country::on($this->fitadmin)->find(intval($id));
                $country->update($insertData);
            }

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);
    }

    /**
     * Migration for City
     */
    public function city($id){

        try{
            $city = City::on($this->fitapi)->find(intval($id));

            $insertData = [
                'name' =>  trim($city->name),
                'slug' =>  trim($city->slug),
                'country_id' =>  intval($city->country_id),
                'order' =>  0,
                'status' =>  (isset($city->hidden) && $city->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($city->created_at)) ? $city->created_at : $city->updated_at,
                'updated_at' =>  $city->updated_at
            ];

            $city_exists_cnt	=	DB::connection($this->fitadmin)->table('cities')->where('_id', intval($id) )->count();

            if($city_exists_cnt === 0){
                $entity 		=	new City($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 	=	intval($city->_id);
                $entity->save();
            }else{

                $city = City::on($this->fitadmin)->find(intval($id));
                $city->update($insertData);
            }

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);

    }

    /**
     * Migration for Locationcluster
     */
    public function locationcluster($id){

        try{

            $locationcluster = Locationcluster::on($this->fitapi)->find(intval($id));

            $insertData = [
                'name' =>  trim($locationcluster->name),
                'slug' =>  trim($locationcluster->slug),
                'city_id' =>  intval($locationcluster->city_id),
                'order' =>  0,
                'status' =>  (isset($locationcluster->hidden) && $locationcluster->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($locationcluster->created_at)) ? $locationcluster->created_at : $locationcluster->updated_at,
                'updated_at' =>  $locationcluster->updated_at
            ];

            $locationcluster_exists_cnt	=	DB::connection($this->fitadmin)->table('locationclusters')->where('_id', intval($id) )->count();

            if($locationcluster_exists_cnt === 0){
                $entity 		=	new Locationcluster($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 	=	intval($locationcluster->_id);
                $entity->save();
            }else{
                // $locationcluster = Locationcluster::on($this->fitadmin)->where('_id', intval($id) )->update($insertData);
                $locationcluster = Locationcluster::on($this->fitadmin)->find(intval($id));
                $locationcluster->update($insertData);
            }

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);
    }


    /**
     * Migration for categorys
     */
    public function category($id){

        try{

            $findercategory = Vendorcategory::on($this->fitapi)->find(intval($id));

            $insertData = [
                'name' =>  trim($findercategory->name),
                'slug' =>  trim($findercategory->slug),
                'detail_rating' =>  $findercategory->detail_rating,
                'cities' =>  (isset($findercategory->cities) && count($findercategory->cities) > 0) ? $findercategory->cities : [],
                'seo' 	=>  [
                    'title' 	=>  ($findercategory->meta['title']) ? strip_tags(trim($findercategory->meta['title'])) : "",
                    'description' 	=>  ($findercategory->meta['description']) ? strip_tags(trim($findercategory->meta['description'])) : "",
                    'keywords' 	=>  (isset($findercategory->meta['keywords']) && $findercategory->meta['keywords'] != "") ? strip_tags(trim($findercategory->meta['keywords'])) : ""
                ],
                'ordering' =>  intval($findercategory->order),
                'status' =>  (isset($findercategory->hidden) && $findercategory->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($findercategory->created_at)) ? $findercategory->created_at : $findercategory->updated_at,
                'updated_at' =>  $findercategory->updated_at
            ];


            $Findercategory_exists_cnt	=	DB::connection($this->fitadmin)->table('findercategories')->where('_id', intval($id) )->count();


            if($Findercategory_exists_cnt === 0){
                $entity 		=	new Findercategory($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 	=	intval($findercategory->_id);
                $entity->save();

            }else{
                $entity = Findercategory::on($this->fitadmin)->find(intval($id));
                $entity->update($insertData);
            }


            $Findercategorytag	=	DB::connection($this->fitadmin)->table('findercategorytags')->where('slug', trim($findercategory->slug))->first();

            $insertData = [
                'name' =>  trim($findercategory->name),
                'slug' =>  trim($findercategory->slug),
                'cities' =>  (isset($findercategory->cities) && count($findercategory->cities) > 0) ? $findercategory->cities : [],
                'finders' =>  (isset($findercategory->vendors) && count($findercategory->vendors) > 0) ? $findercategory->vendors : [],
                'offering_header' => (isset($findercategory->offering_header)) ? trim($findercategory->offering_header) : "",
                'ordering' =>  intval($findercategory->order),
                'status' =>  (isset($findercategory->hidden) && $findercategory->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($findercategory->created_at)) ? $findercategory->created_at : $findercategory->updated_at,
                'updated_at' =>  $findercategory->updated_at
            ];

            if($Findercategorytag && isset($Findercategorytag->_id)){
                $entity = Findercategorytag::on($this->fitadmin)->find(intval($Findercategorytag->_id));
                $entity->update($insertData);
            }else{
                $lastcategorytagid  = 	DB::connection($this->fitadmin)->table('findercategorytags')->count();
                $categorytagid  	= 	intval($lastcategorytagid) + 1;
                $entity 			=	new Findercategorytag($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 		=	$categorytagid;
                $entity->save();
            }


            // Manage Cities
            if(isset($findercategory->cities) && count($findercategory->cities) > 0){

                foreach ($findercategory->cities as $key => $value) {
                    $newcity 				=	DB::connection($this->fitadmin)->table('cities')->where('_id', intval($value))->first();
                    $findercategorys 		= 	[];

                    if(isset($newcity['findercategorys'])) {
                        $findercategorys 		= $newcity['findercategorys'];
                        array_push($findercategorys, $findercategory->_id);
                    }else{
                        array_push($findercategorys, $findercategory->_id);
                    }

                    $findercategorys =  array_map('intval', array_unique($findercategorys));
                    $updatecity = City::on($this->fitadmin)->find(intval($value));
                    if($updatecity){
                        $updatecity->update( [ 'findercategorys' => $findercategorys, 'categorytags' => $findercategorys ]);
                    }
                }
            }

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);

    }


    /**
     * Migration for locations
     */
    public function location($id){

        try{

            $location = Location::on($this->fitapi)->find(intval($id));

            $insertData = [
                'cities' =>  [intval($location->city_id)],
                'name' =>  trim($location->name),
                'slug' =>  trim($location->slug),
                'locationcluster_id' =>  intval($location->locationcluster_id),
                'location_group' =>  trim($location->location_group),
                'ordering' =>  intval($location->order),
                'status' =>  (isset($location->hidden) && $location->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($location->created_at)) ? $location->created_at : $location->updated_at,
                'updated_at' =>  $location->updated_at
            ];

            if($location['geometry']['coordinates'][0] != "" && $location['geometry']['coordinates'][1] != "" ){
                $insertData['lat'] = $location['geometry']['coordinates'][0];
                $insertData['lon'] = $location['geometry']['coordinates'][1];
                $insertData['lonlat']['lat'] = $location['geometry']['coordinates'][0];
                $insertData['lonlat']['lon'] = $location['geometry']['coordinates'][1];
            }


            $Location_exists_cnt	=	DB::connection($this->fitadmin)->table('locations')->where('_id', intval($id) )->count();


            if($Location_exists_cnt === 0){
                $entity 		=	new Location($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 	=	intval($location->_id);
                $entity->save();

            }else{
                $entity = Location::on($this->fitadmin)->find(intval($id));
                $entity->update($insertData);
            }



            $Locationtag	=	DB::connection($this->fitadmin)->table('locationtags')->where('slug', trim($location->slug))->first();

            $insertData = [
                'cities' =>  [intval($location->city_id)],
                'finders' =>  (isset($location->vendors) && count($location->vendors) > 0) ? $location->vendors : [],
                'name' =>  trim($location->name),
                'slug' =>  trim($location->slug),
                'ordering' =>  intval($location->order),
                'status' =>  (isset($location->hidden) && $location->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($location->created_at)) ? $location->created_at : $location->updated_at,
                'updated_at' =>  $location->updated_at
            ];


            if($Locationtag && isset($Locationtag->_id)){
                $entity = Locationtag::on($this->fitadmin)->find(intval($Locationtag->_id));
                $entity->update($insertData);
            }else{
                $lastlocationtagid  = 	DB::connection($this->fitadmin)->table('locationtags')->max('_id');
                $locationtagid  	= 	intval($lastlocationtagid) + 1;
                $entity 			=	new Locationtag($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 		=	$locationtagid;
                $entity->save();
            }



            // Manage Cities
            if(isset($location->city_id) && $location->city_id != "" ){
                $newcity 		=	DB::connection($this->fitadmin)->table('cities')->where('_id', intval($location->city_id))->first();
                $locations 		= 	[];

                if(isset($newcity['locations'])) {
                    $locations 		= $newcity['locations'];
                    array_push($locations, $location->_id);
                }else{
                    array_push($locations, $location->_id);
                }

                $locations 	=	array_map('intval', array_unique($locations));
                $updatecity =	City::on($this->fitadmin)->find(intval($location->city_id));
                if($updatecity){
                    $updatecity->update( [ 'locations' => $locations, 'locationtags' => $locations ]);
                }
            }

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);

    }



    /**
     * Migration for offernigs
     */
    public function offering($id){

        try{

            $offering = Offering::on($this->fitapi)->find(intval($id));

//                                var_dump($offering->vendorcategories);exit;

            if(isset($offering->vendorcategories) && count($offering->vendorcategories) > 0){

                foreach ($offering->vendorcategories as $key => $value) {
                    $vendorcategoryobj	=	DB::connection($this->fitapi)->table('vendorcategories')->where('_id', intval($value))->first();

                    if($vendorcategoryobj){
                        $Findercategorytag	=	DB::connection('mongodb')->table('findercategorytags')->where('slug', trim($vendorcategoryobj['slug']) )->first();
                        $offering_slug      =   url_slug([$Findercategorytag['slug'], $offering->slug ]);

                        $insertData = [
                            'name' =>  trim($offering->name),
                            'slug' =>  trim($offering_slug),
                            'categorytag_id' =>  intval($Findercategorytag['_id']),
                            'finders' =>  (isset($offering->vendors) && count($offering->vendors) > 0) ? $offering->vendors : [],
                            'status' =>  (isset($offering->hidden) && $offering->hidden === false) ? "1" : "0",
                            'created_at' =>  (isset($offering->created_at)) ? $offering->created_at : $offering->updated_at,
                            'updated_at' =>  $offering->updated_at
                        ];

                        $offering_exists_cnt	=	DB::connection($this->fitadmin)->table('offerings')->where('slug', trim($offering_slug) )->count();

                        if($offering_exists_cnt === 0){

                            $offering_id_exists_cnt	=	DB::connection($this->fitadmin)->table('offerings')->where('_id', intval($offering->_id))->count();
							if($offering_id_exists_cnt === 0){
                                $offering_id    = 	intval($offering->_id);
							}else{
								$lastofferingid  = 	DB::connection($this->fitadmin)->table('offerings')->max('_id');
                                $offering_id  	= 	intval($lastofferingid) + 1;
							}

                            $entity 		=	new Offering($insertData);
                            $entity->setConnection($this->fitadmin);
                            $entity->_id 	=	$offering_id;
                            $entity->save();

                        }else{

                            // $entity = Offering::on($this->fitadmin)->find(intval($id));
                            $entity = Offering::on($this->fitadmin)->where('slug', trim($offering_slug) );
                            $entity->update($insertData);
                        }
//                    exit();

                    }


                }// foreach

            }// if

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);

    }



    /**
     * Migration for facilities
     */
    public function facility($id){

        try{

            $facility 		=	Facility::on($this->fitapi)->find(intval($id));
            $facility_slug 	=	$facility->slug;

            $insertData = [
                'name' =>  trim($facility->name),
                'slug' =>  trim($facility_slug),
                'finders' =>  (isset($facility->vendors) && count($facility->vendors) > 0) ? $facility->vendors : [],
                'status' =>  (isset($facility->hidden) && $facility->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($facility->created_at)) ? $facility->created_at : $facility->updated_at,
                'updated_at' =>  $facility->updated_at
            ];

            $facility_exists_cnt	=	DB::connection($this->fitadmin)->table('facilities')->where('_id', intval($id) )->count();


            if($facility_exists_cnt === 0){
                $facility_id    = 	intval($facility->_id);
                $entity 		=	new Facility($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 	=	$facility_id;
                $entity->save();

            }else{
                $entity = Facility::on($this->fitadmin)->find(intval($id));
                $entity->update($insertData);
            }

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);

    }




    /**
     * Migration for vendors
     */
    public function vendor($id){

        try{

            $detail_rating_obj = "";
            $commercial_type_arr = array( 0 => 'free', 1 => 'paid', 2 => 'freespecial', 3 => 'cos');
            $business_type_arr = array( 0 => 'noninfrastructure', 1 => 'infrastructure');
            $finder_type_arr = array( 0 => 'free', 1 => 'paid');

            $Finder 			=	Vendor::on($this->fitapi)->find(intval($id));
            $Findercategory 	=	Findercategory::find(intval($Finder->vendorcategory['primary']));

            $detail_rating_summary_average = $detail_rating_summary_count = [];

            if(isset($Findercategory['detail_rating']) && !empty($Findercategory['detail_rating'])){
                foreach ($Findercategory['detail_rating'] as $key => $value) {
                    if(isset($Finder->detail_rating[strtolower($value)])){
                        array_push($detail_rating_summary_average, $Finder->detail_rating[strtolower($value)]["value"]);
                        array_push($detail_rating_summary_count, $Finder->detail_rating[strtolower($value)]["count"]);
                    }

                }
            }


            //for categorytags
            $new_categorytag_ids_arr	= [];
            if(isset($Finder->vendorcategory['secondary']) && !empty($Finder->vendorcategory['secondary'])){
                $old_categorytag_slugs_arr	=	Findercategory::whereIn('_id', array_map('intval', $Finder->vendorcategory['secondary']))->lists('slug');
                $new_categorytag_ids_arr	=	Findercategorytag::whereIn('slug', $old_categorytag_slugs_arr)->lists('_id');
            }

            //for locationtags
            $new_locationtag_ids_arr	= [];
            if(isset($Finder->location['secondary']) && !empty($Finder->location['secondary'])){
                $old_locationtag_slugs_arr	=	Location::whereIn('_id', array_map('intval', $Finder->location['secondary']))->lists('slug');
                $new_locationtag_ids_arr	=	Locationtag::whereIn('slug', $old_locationtag_slugs_arr)->lists('_id');
                // dd($new_locationtag_ids_arr);
            }

            //for offerings
            $new_offering_ids_arr	= [];
            if(isset($Finder->filter['offerings']) && !empty($Finder->filter['offerings'])){
//                 var_dump($Finder->filter['offerings']);exit();
                foreach ($Finder->filter['offerings'] as $key => $offeringid) {
                    $oldoffering = Offering::on($this->fitapi)->find(intval($offeringid));
                    if(isset($oldoffering->vendorcategories) && count($oldoffering->vendorcategories) > 0){
//                         return $oldoffering->vendorcategories;
//                        var_dump($oldoffering->vendorcategories);exit();

                        foreach ($oldoffering->vendorcategories as $key => $value) {
                            // return $value;
                            $findercategorytagsobj	=	DB::connection($this->fitapi)->table('vendorcategories')->where('_id', intval($value))->first();
                            $offering_slug 			= url_slug([$findercategorytagsobj['slug'], $oldoffering->slug]);
                            $newoffering 			= Offering::on($this->fitadmin)->where('slug', trim($offering_slug) )->first();

                            if(!$newoffering){
                                $this->offering($offeringid);
                                $newoffering = Offering::on($this->fitadmin)->where('slug', trim($offering_slug) )->first();
                            }

                            array_push($new_offering_ids_arr, $newoffering->_id);

                        }
                    }//foreach vendorcategories
                }//foreach offering
            }//offering
//                 var_dump($new_offering_ids_arr);exit();


                if(isset($Finder->contact['point_of_contact'])){
                $finder_poc_for_customer_mobile_arr = $finder_poc_for_customer_name_arr = [];
                $finder_vcc_email_arr = $finder_vcc_mobile_arr = [];

                foreach ($Finder->contact['point_of_contact'] as $key => $value) {
                    if(in_array('customer_display', $value['used_for'])){
                        if($value['name'] != ""){
                            array_push($finder_poc_for_customer_name_arr, $value['name']);
                        }
                        if($value['mobile'] != ""){
                            array_push($finder_poc_for_customer_mobile_arr, $value['mobile']);
                        }
                        if($value['landline'] != ""){
                            array_push($finder_poc_for_customer_mobile_arr, $value['landline']);
                        }
                    }else{
                        if($value['email'] != ""){
                            array_push($finder_vcc_email_arr, $value['email']);
                        }
                        if($value['mobile'] != ""){
                            array_push($finder_vcc_mobile_arr, $value['mobile']);
                        }
                        if($value['landline'] != ""){
                            array_push($finder_vcc_mobile_arr, $value['landline']);
                        }
                    }
                }
            }

             $insertData = [
                'title' 				=>  trim($Finder->name),
                'slug' 					=>  trim($Finder->slug),
                'country_id' 			=>  intval($Finder->country_id),
                'city_id' 				=>  intval($Finder->city_id),
                'category_id' 			=>  intval($Finder->vendorcategory['primary']),
                'categorytags' 			=>  array_unique($new_categorytag_ids_arr),
                'location_id' 			=>  intval($Finder->location['primary']),
                'locationtags' 			=>  array_unique($new_locationtag_ids_arr),
                'offerings' 			=>  array_unique($new_offering_ids_arr),
                'facilities' 			=>  (isset($Finder->filter['facilities'])) ? array_unique(array_map('intval', $Finder->filter['facilities'])) : [],
                'lon' 					=>  ($Finder->geometry['coordinates'][0]) ? trim($Finder->geometry['coordinates'][0]) : "",
                'lat' 					=>  ($Finder->geometry['coordinates'][1]) ? trim($Finder->geometry['coordinates'][1]) : "",
                'info' 	=>  [
                    'about' 	=>  ($Finder->info['about']) ? trim($Finder->info['about']) : "",
                    'additional_info' 	=>  ($Finder->info['additional_info']) ? trim($Finder->info['additional_info']) : "",
                    'timing' 	=>  ($Finder->info['timing']) ? trim($Finder->info['timing']) : "",
                    'service' 	=>  ($Finder->info['service']) ? "<ul><li>". implode("</li><li>", $Finder->info['service'])."</li></ul>" : "",
                ],
                'meta' 	=>  [
                    'title' 	=>  ($Finder->seo['title']) ? trim($Finder->seo['title']) : "",
                    'description' 	=>  ($Finder->seo['description']) ? trim($Finder->seo['description']) : "",
                ],
                'contact' 	=>  [
                    'address' 	=>  ($Finder->address['line1']) ? trim($Finder->address['line1']).",".trim($Finder->address['line2']).",".trim($Finder->address['line3']) : "",
                    'email' 	=>  (isset($Finder->contact['email']) && count($Finder->contact['email']) > 0)  ? implode(",", $Finder->contact['email']) : "",
                    'phone' 	=>  (isset($Finder->contact['phone']['mobile']) && count($Finder->contact['phone']['mobile']) > 0)  ? implode(",", $Finder->contact['phone']['mobile']) : "",
                    'website' 	=>  "",
                ],
                'landmark' 	=>  ($Finder->address['landmark']) ? trim($Finder->address['landmark']) : "",
                 'coverimage' 							=>  (isset($Finder->coverimage)) ? $Finder->coverimage : "",
                'logo' 									=>  (isset($Finder->logo)) ? $Finder->logo : "",
                'photos' 								=>  (isset($Finder['media']['images']['gallery']) && count($Finder['media']['images']['gallery']) > 0) ? $Finder['media']['images']['gallery'] : [],
                'total_photos' 							=>  count($Finder['media']['images']['gallery']),
                 'videos' 								=>  (isset($Finder['media']['videos']) && count($Finder['media']['videos']) > 0) ? $Finder['media']['videos'] : [],
                 'multiaddress' 					    =>  (isset($Finder['multiaddress']) && count($Finder['multiaddress']) > 0) ? $Finder['multiaddress'] : [],
                'average_rating' 						=>  (isset($Finder->rating['value'])) ? $Finder->rating['value'] : 0,
                'total_rating_count' 					=>  (isset($Finder->rating['count'])) ? $Finder->rating['count'] : 0,
                'detail_rating_summary_average' 		=>  $detail_rating_summary_average,
                'detail_rating_summary_count' 			=>  $detail_rating_summary_count,
                'what_i_should_carry' 					=>  (isset($Finder->what_i_should_carry)) ? $Finder->what_i_should_carry : "",
                'what_i_should_expect' 					=>  (isset($Finder->what_i_should_expect)) ? $Finder->what_i_should_expect : "",
                'business_type' 						=>  array_search($Finder->business['type'], $business_type_arr),
                'commercial_type' 						=>  array_search($Finder->commercial['type'], $commercial_type_arr),
                'share_customer_no' 					=>  (isset($Finder->commercial['type']['share_customer_number']) && $Finder->commercial['type']['share_customer_number'] === true) ? "1" : "0",
                'finder_poc_for_customer_mobile' 		=>  implode(",", array_unique($finder_poc_for_customer_mobile_arr)),
                'finder_poc_for_customer_name' 			=>  implode(",", array_unique($finder_poc_for_customer_name_arr)),
                'finder_vcc_email' 						=>  implode(",", array_unique($finder_vcc_email_arr)),
                'finder_vcc_mobile' 					=>  implode(",", array_unique($finder_vcc_mobile_arr)),
                'status' 								=>  (isset($Finder->hidden) && $Finder->hidden === false) ? "1" : "0",
                'created_at' 							=>  (isset($Finder->created_at)) ? $Finder->created_at : $Finder->updated_at,
                'updated_at' 							=>  $Finder->updated_at
            ];

            $insertData['vip_trial']                    = ($Finder['vip_trial'] == true ) ? '1' : '0';

//            var_dump($insertData);exit();
            $Finder_exists_cnt	=	DB::connection($this->fitadmin)->table('finders')->where('_id', intval($id) )->count();


            if($Finder_exists_cnt === 0){
                $entity 		=	new Finder($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 	=	intval($Finder->_id);
                $entity->save();
            }else{
                // $Finder = Finder::on($this->fitadmin)->where('_id', intval($id) )->update($insertData);
                $entity = Finder::on($this->fitadmin)->find(intval($id));
                $entity->update($insertData);
            }

            $this->cacheapi->flushTagKey('finder_detail',$entity->slug);


            $finder_id = intval($entity['_id']);

            //manage categorytags
            if (isset($entity['categorytags']) && !empty($entity['categorytags'])) {
                $findercategorytags = array_map('intval', $entity['categorytags']);
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->categorytags()->sync(array());
                foreach ($findercategorytags as $key => $value) {
                    $finder->categorytags()->attach($value);
                }
            }

            //manage locationtags
            if (isset($entity['locationtags']) && !empty($entity['locationtags'])) {
                $finderlocationtags = array_map('intval', $entity['locationtags']);
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->locationtags()->sync(array());
                foreach ($finderlocationtags as $key => $value) {
                    $finder->locationtags()->attach($value);
                }
            }


            //manage facilities
            if (isset($entity['facilities']) && !empty($entity['facilities'])) {
                $finderfacilities = array_map('intval', $entity['facilities']);
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->facilities()->sync(array());
                foreach ($finderfacilities as $key => $value) {
                    $finder->facilities()->attach($value);
                }
            }

            //manage offerings
            if (isset($entity['offerings']) && !empty($entity['offerings'])) {
                $finderofferings = array_map('intval', $entity['offerings']);
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->offerings()->sync(array());
                foreach ($finderofferings as $key => $value) {
                    $finder->offerings()->attach($value);
                }
            }


            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);

    }



    /**
     * Migration for vendorservicecategory
     */
    public function vendorservicecategory($id){

        try{

            $servicecategory =	DB::connection($this->fitapi)->table('vendorservicecategories')->where('_id', intval($id))->first();

            $insertData = [
                'name' =>  trim($servicecategory['name']),
                'slug' =>  trim($servicecategory['slug']),
                'parent_id' =>  intval($servicecategory['parent_id']),
                // 'parent_name' =>  trim($servicecategory['parent_name']),
                'description' =>  strip_tags(trim($servicecategory['description'])),
                'what_i_should_carry' =>  strip_tags(trim($servicecategory['what_i_should_carry'])),
                'what_i_should_expect' =>  strip_tags(trim($servicecategory['what_i_should_expect'])),
                'meta' 	=>  [
                    'title' 	=>  ($servicecategory['seo']['title']) ? strip_tags(trim($servicecategory['seo']['title'])) : "",
                    'description' 	=>  ($servicecategory['seo']['description']) ? strip_tags(trim($servicecategory['seo']['description'])) : "",
                    'keywords' 	=>  (isset($servicecategory['seo']['keywords']) && $servicecategory['seo']['keywords'] != "") ? strip_tags(trim($servicecategory['seo']['keywords'])) : ""
                ],
                'ordering' =>  0,
                'status' =>  (isset($servicecategory['hidden']) && $servicecategory['hidden'] === false) ? "1" : "0",
                'created_at' =>  (isset($servicecategory['created_at'])) ? $servicecategory['created_at'] : $servicecategory['updated_at'],
                'updated_at' =>  $servicecategory['updated_at']
            ];

            // return $insertData;

            $vendorservicecategories_exists_cnt	=	DB::connection($this->fitadmin)->table('servicecategories')->where('_id', intval($id) )->count();

            // exit();

            if($vendorservicecategories_exists_cnt === 0){
                $vendorservicecategories_id    = 	intval($servicecategory['_id']);
                $entity 		=	new Servicecategory($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id 	=	$vendorservicecategories_id;
                $entity->save();

            }else{
                $entity = Servicecategory::on($this->fitadmin)->find(intval($id));
                $entity->update($insertData);
            }

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);

    }


    public function vendorservice($id){

        try{

            $data	=	DB::connection($this->fitapi)->table('vendorservices')->where('_id', intval($id))->first();

            $insertData['finder_id'] = (int)$data['vendor_id'];
            $insertData['name'] = $data['name'];
            $insertData['servicecategory_id'] = (int)$data['category']['primary'];
            $insertData['servicesubcategory_id'] = (int)$data['category']['secondary'];
            $insertData['lon'] = $data['geometry']['coordinates']['0'];
            $insertData['lat'] = $data['geometry']['coordinates']['1'];
            $insertData['location_id'] = (int)$data['location_id'];
            $insertData['city_id'] = (int)$data['city_id'];
            $insertData['workout_intensity'] = $data['workout_intensity'];
            $insertData['session_type'] = $data['session_type'];
            $insertData['workout_tags'] = $data['workout_tags'];
            $insertData['status'] = (isset($data['hidden']) && $data['hidden'] == true) ? '0' : '1';
            $insertData['deduct'] = (isset($data['trial_cashback_status'])  && $data['trial_cashback_status'] == true) ? '1' : '0';
            $insertData['rockbottom'] = (isset($data['rockbottom_price_status'])  && $data['rockbottom_price_status'] == true) ? '1' : '0';
            $insertData['ordering'] = (int)$data['order'];
            $insertData['short_description'] = $data['info']['short_description'];
            $insertData['body'] = $data['info']['long_description'];
            $insertData['address'] = ($data['address']['line1'] == '' && $data['address']['line1'] == '' && $data['address']['line1'] == '' && $data['address']['pincode'] == '' && $data['address']['landmark'] == '') ? '' : $data['address']['line1'].', '.$data['address']['line2'].', '.$data['address']['line3'].', '.$data['address']['landmark'].', '.$data['address']['pincode'];
            $insertData['what_i_should_carry'] = $data['what_i_should_carry'];
            $insertData['what_i_should_expect'] = $data['what_i_should_expect'];

            if(isset($data['provided_by']) && $data['provided_by'] !== 0){
                $insertData['trainer_id'] = $data['provided_by'];
            }
            $insertData['show_on']      =   "1";
            $insertData['created_at']   =   $data['created_at'];
            $insertData['updated_at']   =   $data['updated_at'];

//            return $insertData;

            $service_exists = Service::on($this->fitadmin)->find(intval($id));

            if($service_exists){
                $service_exists->update($insertData);
                $this->updatescheduleByServiceId($id);
//                return var_dump($service_exists->toArray());
            }else{
                $service_exists = new Service($insertData);
                $service_exists->setConnection($this->fitadmin);
                $service_exists->_id 	=	intval($id);
                $service_exists->save();
            }

            $finder = Finder::on($this->fitadmin)->find(intval($service_exists->finder_id));

	        $this->cacheapi->flushTagKey('finder_detail',$finder->slug);

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);


    }

    public function ratecard($id){

        try{

            $data	=	DB::connection($this->fitapi)->table('ratecards')->where('_id', intval($id))->first();

            $insertData['finder_id'] = (int)$data['vendor_id'];
            $insertData['service_id'] = (int)$data['vendorservice_id'];
            $insertData['type'] = $data['type'];
            $insertData['price'] = (int)$data['price'];
            $insertData['special_price'] = (int)$data['selling_price'];
            $insertData['direct_payment_enable'] = ($data['direct_payment_enable'] == true ) ? '1' : '0';
            $insertData['remarks'] = $data['remarks'];
            $insertData['discount_amount'] = (isset($data['maximum_discount_amount'])) ? intval($data['maximum_discount_amount']) : 0;
            $insertData['order'] = (int)$data['order'];
            $insertData['validity'] = (int)$data['duration'];
            $insertData['validity_type'] = $data['duration_type'];
            $insertData['duration'] = (int)$data['quantity'];
            $insertData['duration_type'] = 'sessions';
            $insertData['created_at'] = $data['created_at'];
            $insertData['updated_at'] = $data['updated_at'];

            if(isset($data['weight']) && $data['weight'] != ""){
                $insertData['weight'] = (int)$data['weight'];
            }

            if(isset($data['weight_type']) && $data['weight_type'] != ""){
                $insertData['weight_type'] = $data['weight_type'];
            }


             $ratecart_exists = Ratecard::on($this->fitadmin)->find(intval($id));

            if($ratecart_exists){
                $ratecart_exists->update($insertData);
            }else{
                $ratecart_exists = new Ratecard($insertData);
                $ratecart_exists->setConnection($this->fitadmin);
                $ratecart_exists->_id 	=	intval($id);
                $ratecart_exists->save();
            }

            if($data['type'] == "trial" || $data['type'] == "workout session") {
                if(isset($data['vendorservice_id']) && $data['vendorservice_id'] !=""){
                    $updatescheduleByServiceId  = $this->updatescheduleByServiceId($data['vendorservice_id']);

//                    return $updatescheduleByServiceId;
                }
            }


            $finder = Finder::on($this->fitadmin)->find(intval($ratecart_exists->finder_id));

	        $this->cacheapi->flushTagKey('finder_detail',$finder->slug);

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);

    }


    /**
     * Migration for schedules
     */
    public function schedule($id){

        try{

            $schedule = Schedule::find($id);

            $schedules = Schedule::where('vendorservice_id',intval($schedule->vendorservice_id))->get();

            //Trial Price From Ratecard
            $trialPrice = 0;
            $trialRatecard_exists_cnt	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'trial')->count();

            if($trialRatecard_exists_cnt === 0){
                $trialRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'trial')->first();
            }else{
                $trialRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'trial')->where('quantity',1)->first();
            }

            if($trialRatecard && isset($trialRatecard['price'])){
                $trialPrice = $trialRatecard['price'];
            }

            //Workout session Price From Ratecard
            $workoutSessionPrice = 0;
            $workoutSessionRatecard_exists_cnt	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'workout session')->count();

            if($workoutSessionRatecard_exists_cnt === 0){
                $workoutSessionRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'workout session')->first();
            }else{
                $workoutSessionRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'workout session')->where('quantity',1)->first();
            }

            if($workoutSessionRatecard && isset($workoutSessionRatecard['price'])){
                $workoutSessionPrice = $workoutSessionRatecard['price'];
            }



            $trialschedulesdata = [];

            $workoutsessionschedules = [];

            foreach ($schedules as $key => $schedule) {
                $weekdaydata 				=	[];
                $weekdaydata['weekday'] 	=	$schedule['day'];
                $weekdaydata['slots'] 		=	[];

                if(isset($schedule['slots'])){
                    foreach ($schedule['slots'] as $k => $slot) {
                        if(isset($slot['duration'])){
                            $duration_arr = explode('-', $slot['duration']);
                            $newslot = [
                                'start_time' => $duration_arr[0],
                                'end_time' => $duration_arr[1],
                                'start_time_24_hour_format' => $slot['start_time']['hours'],
                                'end_time_24_hour_format' => $slot['end_time']['hours'],
                                'slot_time' => $slot['duration'],
                                'limit' => (isset($slot['limit'])) ?  intval($slot['limit']) : 0,
                                'price' => intval($trialPrice)
                            ];
                            array_push($weekdaydata['slots'], $newslot);
                        }
                    }
                }
                array_push($trialschedulesdata, $weekdaydata);



                if($workoutSessionPrice > 0){
                    $weekdaydata 				=	[];
                    $weekdaydata['weekday'] 	=	$schedule['day'];
                    $weekdaydata['slots'] 		=	[];

                    if(isset($schedule['slots'])){
                        foreach ($schedule['slots'] as $k => $slot) {
                            if(isset($slot['duration'])){
                                $duration_arr = explode('-', $slot['duration']);
                                $newslot = [
                                    'start_time' => $duration_arr[0],
                                    'end_time' => $duration_arr[1],
                                    'start_time_24_hour_format' => $slot['start_time']['hours'],
                                    'end_time_24_hour_format' => $slot['end_time']['hours'],
                                    'slot_time' => $slot['duration'],
                                    'limit' => (isset($slot['limit'])) ?  intval($slot['limit']) : 0,
                                    'price' => intval($workoutSessionPrice)
                                ];
                                array_push($weekdaydata['slots'], $newslot);
                            }
                        }
                    }
                    array_push($workoutsessionschedules, $weekdaydata);
                }

            }

//            return $workoutsessionschedules;

            $service_exists = Service::on($this->fitadmin)->find(intval($schedule->vendorservice_id));
            if($service_exists){
                $service_exists->update([
                    'trialschedules' => $trialschedulesdata,
                    'workoutsessionschedules' => $workoutsessionschedules
                ]);
            }

            $finder = Finder::on($this->fitadmin)->find(intval($service_exists->finder_id));

	        $this->cacheapi->flushTagKey('finder_detail',$finder->slug);

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);
    }



    /**
     * Migration for schedules
     */
    public function updatescheduleByServiceId($vendorservice_id){

        try{

             $vendorservice_id = $vendorservice_id;

            $schedules = Schedule::where('vendorservice_id',intval($vendorservice_id))->get();

            //Trial Price From Ratecard
            $trialPrice = 0;
            $trialRatecard_exists_cnt	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->count();

            if($trialRatecard_exists_cnt === 0){
                $trialRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->first();
            }else{
                $trialRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->where('quantity',1)->first();
            }

            if($trialRatecard && isset($trialRatecard['price'])){
                $trialPrice = $trialRatecard['price'];
            }

            //Workout session Price From Ratecard
            $workoutSessionPrice = 0;
            $workoutSessionRatecard_exists_cnt	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->count();

            if($workoutSessionRatecard_exists_cnt === 0){
                $workoutSessionRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->first();
            }else{
                $workoutSessionRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->where('quantity',1)->first();
            }

            if($workoutSessionRatecard && isset($workoutSessionRatecard['price'])){
                $workoutSessionPrice = $workoutSessionRatecard['price'];
            }



            $trialschedulesdata = [];

            $workoutsessionschedules = [];

            foreach ($schedules as $key => $schedule) {
                $weekdaydata 				=	[];
                $weekdaydata['weekday'] 	=	$schedule['day'];
                $weekdaydata['slots'] 		=	[];

                if(isset($schedule['slots'])){
                    foreach ($schedule['slots'] as $k => $slot) {
                        if(isset($slot['duration'])){
                            $duration_arr = explode('-', $slot['duration']);
                            $newslot = [
                                'start_time' => $duration_arr[0],
                                'end_time' => $duration_arr[1],
                                'start_time_24_hour_format' => $slot['start_time']['hours'],
                                'end_time_24_hour_format' => $slot['end_time']['hours'],
                                'slot_time' => $slot['duration'],
                                'limit' => (isset($slot['limit'])) ?  intval($slot['limit']) : 0,
                                'price' => intval($trialPrice)
                            ];
                            array_push($weekdaydata['slots'], $newslot);
                        }
                    }
                }
                array_push($trialschedulesdata, $weekdaydata);



                if($workoutSessionPrice > 0){
                    $weekdaydata 				=	[];
                    $weekdaydata['weekday'] 	=	$schedule['day'];
                    $weekdaydata['slots'] 		=	[];

                    if(isset($schedule['slots'])){
                        foreach ($schedule['slots'] as $k => $slot) {
                            if(isset($slot['duration'])){
                                $duration_arr = explode('-', $slot['duration']);
                                $newslot = [
                                    'start_time' => $duration_arr[0],
                                    'end_time' => $duration_arr[1],
                                    'start_time_24_hour_format' => $slot['start_time']['hours'],
                                    'end_time_24_hour_format' => $slot['end_time']['hours'],
                                    'slot_time' => $slot['duration'],
                                    'limit' => (isset($slot['limit'])) ?  intval($slot['limit']) : 0,
                                    'price' => intval($workoutSessionPrice)
                                ];
                                array_push($weekdaydata['slots'], $newslot);
                            }
                        }
                    }
                    array_push($workoutsessionschedules, $weekdaydata);
                }

            }

//            return $trialschedulesdata;

            $service_exists = Service::on($this->fitadmin)->find(intval($schedule->vendorservice_id));
            if($service_exists){
                $service_exists->update([
                    'trialschedules' => $trialschedulesdata,
                    'workoutsessionschedules' => $workoutsessionschedules
                ]);
            }

            $finder = Finder::on($this->fitadmin)->find(intval($service_exists->finder_id));

            $this->cacheapi->flushTagKey('finder_detail',$finder->slug);

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);
    }



    /**
     * Migration for batches
     */
    public function batch($id){

        try{
	        $batch = Batch::find($id);

	        $batches 		=  Batch::where('vendorservice_id',intval($batch->vendorservice_id))->get();
	        $batchesdata 	= [];

	        // return $batches;
	        foreach ($batches as $key => $batch) {
	            // return $batch;
	            $batchdata 				=	[];
	            // $weekdaydata['slots'] 	=	[];

	            if(isset($batch['slots'])){
	                $batch_weekdays_data  = [];

	                foreach ($batch['slots'] as $k => $slot) {
	                    // return $slot;
	                    $batch_weekdays_data['weekday'] =	$slot['day'];

	                    if (intval($slot['start_time']['hours']) < 12) {
	                        $start_time = $slot['start_time']['hours'] .":00 am";
	                    }else{
	                        $start_time = (intval($slot['start_time']['hours']) + 12) .":00 pm";
	                    }

	                    if (intval($slot['end_time']['hours']) < 12) {
	                        $end_time = $slot['end_time']['hours'] .":00 am";
	                    }else{
	                        $end_time = (intval($slot['end_time']['hours']) + 12) .":00 pm";
	                    }

	                    $batch_weekdays_data['slots'] =	[
	                        [
	                            'weekday' => $slot['day'],
	                            'start_time' => $start_time,
	                            'end_time' => $end_time,
	                            'slot_time' => $start_time."-".$end_time,
	                            'limit' => (isset($slot['limit'])) ?  intval($slot['limit']) : 0,
	                            'price' => (isset($slot['price'])) ?  intval($slot['price']) : 0
	                        ]
	                    ];

	                    // return $batch_weekdays_data;
	                    array_push($batchdata, $batch_weekdays_data);
	                    // return $batchdata;

	                }
	            }
	            array_push($batchesdata, $batchdata);
	        }

	        // return $batchesdata;

	        $service_exists = Service::on($this->fitadmin)->find(intval($batch->vendorservice_id));
	        if($service_exists){
	            $service_exists->update(['batches' => $batchesdata]);
	        }

	        $finder = Finder::on($this->fitadmin)->find(intval($service_exists->finder_id));

	        $this->cacheapi->flushTagKey('finder_detail',$finder->slug);

            $response = array('status' => 200, 'message' => 'Success');

        }catch(Exception $e){

            Log::error($e);

            $message = array(
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            );

            $response = array('status' => 404, 'message' => $message);

        }

        return Response::json($response,$response['status']);
    }




}
