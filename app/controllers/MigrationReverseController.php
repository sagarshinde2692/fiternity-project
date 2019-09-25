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
            case 'deleteratecard' : $return = $this->deleteRatecard($id);break;
            case 'schedule' : $return = $this->schedule($id);break;
            case 'batch' : $return = $this->batch($id);break;
            case 'deleteschedulebyvendor' : $return = $this->deletescheduleByVendorId($id);break;
            case 'deletebatchbyservice' : $return = $this->deleteBatchByServiceId($id);break;
            case 'updateschedulebyserviceidv1' : $return = $this->updatescheduleByServiceIdV1($id);break;
            case 'brand' : $return = $this->brand($id);break;
            case 'offer' : $return = $this->offer($id);break;



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
                'defination' =>  (isset($findercategory->defination) && count($findercategory->defination) > 0) ? $findercategory->defination : [],
                'meta'  =>  [
                    'title'     =>  ($findercategory->seo['title']) ? (trim($findercategory->seo['title'])) : "",
                    'description'   =>  ($findercategory->seo['description']) ? (trim($findercategory->seo['description'])) : "",
                    'keywords'  =>  (isset($findercategory->seo['keywords']) && $findercategory->seo['keywords'] != "") ? (trim($findercategory->seo['keywords'])) : ""
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

            if($Findercategorytag && isset($Findercategorytag['_id'])){
                $entity = Findercategorytag::on($this->fitadmin)->find(intval($Findercategorytag['_id']));
                $entity->update($insertData);
            }else{
                $lastcategorytagid  = 	DB::connection($this->fitadmin)->table('findercategorytags')->max('_id');
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
                $insertData['lon'] = $location['geometry']['coordinates'][0];
                $insertData['lat'] = $location['geometry']['coordinates'][1];
                $insertData['lonlat']['lon'] = $location['geometry']['coordinates'][0];
                $insertData['lonlat']['lat'] = $location['geometry']['coordinates'][1];
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

            // $ratecarding = Ratecard::on($this->fitapoffer->ratecard_i)->find(intval($id));

            // if(isset($ratecard->available_slots))
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

            Log::info("migrating vendor:".$id);

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
                        array_push($detail_rating_summary_average, floatval($Finder->detail_rating[strtolower($value)]["value"]));
                        array_push($detail_rating_summary_count, floatval($Finder->detail_rating[strtolower($value)]["count"]));
                    }else{
                        array_push($detail_rating_summary_average, 0);
                        array_push($detail_rating_summary_count, 0);
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

            $trainer_contacts = [];
            $finder_poc = [];
            if(isset($Finder->contact['point_of_contact'])){
                $finder_poc_for_customer_mobile_arr = $finder_poc_for_customer_name_arr = [];
                $finder_vcc_email_arr = $finder_vcc_mobile_arr = [];
                $finder_poc = $Finder->contact['point_of_contact'];

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
                    }else if(in_array('trainer_contact', $value['used_for'])){
                        $trainer = [
                            'name'  => "",
                            'mobile'=> "",
                            'email'=> ""
                        ];
                        if($value['name'] != ""){
                           $trainer['name'] = $value['name'];
                        }
                        if($value['mobile'] != ""){
                           $trainer['mobile'] = $value['mobile'];
                        }
                        if($value['email'] != ""){
                           $trainer['email'] = $value['email'];
                        }
                        array_push($trainer_contacts, $trainer);
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


            $address = '';
            if(isset($Finder->address['line1']) && $Finder->address['line1'] != ""){   $address .= $Finder->address['line1'].","; }
            if(isset($Finder->address['line2']) && $Finder->address['line2'] != ""){   $address .= $Finder->address['line2'].","; }
            if(isset($Finder->address['line3']) && $Finder->address['line3'] != ""){   $address .= $Finder->address['line3'].","; }
            if(isset($Finder->address['landmark']) && $Finder->address['landmark'] != "" ){  $address .= $Finder->address['landmark'].","; }
            if(isset($Finder->address['pincode']) && $Finder->address['pincode'] != ""){   $address .= $Finder->address['pincode']; }
//            var_dump($address);exit;

            $mobilePhoneStr = "";
            if(isset($Finder->contact['phone']['mobile']) && count($Finder->contact['phone']['mobile']) > 0){
                $mobilePhoneStr .= implode(",", $Finder->contact['phone']['mobile']);
                $mobilePhoneStr .= ",";
            }

            if(isset($Finder->contact['phone']['landline']) && count($Finder->contact['phone']['landline']) > 0){
                $mobilePhoneStr .= implode(",", $Finder->contact['phone']['landline']);
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
                'lunchlocationtags'     =>  (isset($Finder->location['secondary_lunch']))? $Finder->location['secondary_lunch']:[],
                'dinnerlocationtags'    =>  (isset($Finder->location['secondary_dinner']))?$Finder->location['secondary_dinner']:[],
                'offerings' 			=>  array_unique($new_offering_ids_arr),
                'facilities' 			=>  (isset($Finder->filter['facilities'])) ? array_unique(array_map('intval', $Finder->filter['facilities'])) : [],
                'lat' 					=>  (isset($Finder->geometry['coordinates'][0])) ? trim($Finder->geometry['coordinates'][0]) : "",
                'lon' 					=>  (isset($Finder->geometry['coordinates'][1])) ? trim($Finder->geometry['coordinates'][1]) : "",
                'info' 	=>  [
                    'about' 	=>  (isset($Finder->info['about'])) ? trim($Finder->info['about']) : "",
                    'additional_info' 	=>  (isset($Finder->info['additional_info'])) ? trim($Finder->info['additional_info']) : "",
                    'timing' 	=>  (isset($Finder->info['delivery_timing'])) ? trim($Finder->info['delivery_timing']) : "",
                    'delivery_address' 	=>  (isset($Finder->info['delivery_address'])) ? trim($Finder->info['delivery_address']) : "",
                    'service' 	=>  (isset($Finder->info['service'])) ? "<ul><li>". implode("</li><li>", $Finder->info['service'])."</li></ul>" : "",
                    'gstin'     => (isset($Finder->info) && isset($Finder->info['gstin'])) ? $Finder->info['gstin'] : "",
                    'terms_and_conditions'     => (isset($Finder->info) && isset($Finder->info['terms_and_conditions'])) ? $Finder->info['terms_and_conditions'] : "",
                	'stripe'     => (!empty($Finder->info)&&!empty($Finder->info['stripe'])) ? $Finder->info['stripe']:null,
                ],
                'meta' 	=>  [
                    'title' 	=>  (isset($Finder->seo['title'])) ? trim($Finder->seo['title']) : "",
                    'description' 	=>  (isset($Finder->seo['description'])) ? trim($Finder->seo['description']) : "",
                ],
                'contact' 	=>  [
                    'address' 	=>  $address,
                    'email' 	=>  (isset($Finder->contact['email']) && count($Finder->contact['email']) > 0)  ? implode(",", $Finder->contact['email']) : "",
                    'phone' 	=>  $mobilePhoneStr,
                    'website' 	=>  "",
                ],

                'landmark' 	                            =>  (isset($Finder->address['landmark'])) ? trim($Finder->address['landmark']) : "",
                'coverimage' 							=>  (isset($Finder['media']['images']['cover'])) ? $Finder['media']['images']['cover'] : "",

                'logo' 									=>  (isset($Finder->logo)) ? $Finder->logo : "",
                'photos' 								=>  (isset($Finder['media']['images']['gallery']) && count($Finder['media']['images']['gallery']) > 0) ? $Finder['media']['images']['gallery'] : [],
                'total_photos' 							=>  count($Finder['media']['images']['gallery']),
                'videos' 								=>  (isset($Finder['media']['videos']) && count($Finder['media']['videos']) > 0) ? $Finder['media']['videos'] : [],
            	'playOverVideo'                         =>  (isset($Finder['media']['playOverVideo']))?$Finder['media']['playOverVideo']:-1,
                'multiaddress' 					        =>  (isset($Finder['multiaddress']) && count($Finder['multiaddress']) > 0) ? $Finder['multiaddress'] : [],
                'peak_hours' 					        =>  (isset($Finder['peak_hours']) && count($Finder['peak_hours']) > 0) ? $Finder['peak_hours'] : [],
                // 'average_rating' 						=>  (isset($Finder->rating['value'])) ? $Finder->rating['value'] : 0,
                // 'total_rating_count' 					=>  (isset($Finder->rating['count'])) ? $Finder->rating['count'] : 0,
//                'detail_rating_summary_average' 		=>  $detail_rating_summary_average,
//                'detail_rating_summary_count' 			=>  $detail_rating_summary_count,
                'what_i_should_carry' 					=>  (isset($Finder->what_i_should_carry)) ? $Finder->what_i_should_carry : "",
                'what_i_should_expect' 					=>  (isset($Finder->what_i_should_expect)) ? $Finder->what_i_should_expect : "",
                'business_type' 						=>  array_search($Finder->business['type'], $business_type_arr),
                'commercial_type' 						=>  array_search($Finder->commercial['type'], $commercial_type_arr),
                'share_customer_no' 					=>  (isset($Finder->commercial['share_customer_no']) && $Finder->commercial['share_customer_no'] === true) ? "1" : "0",
                'finder_poc_for_customer_mobile' 		=>  implode(",", array_unique($finder_poc_for_customer_mobile_arr)),
                'finder_poc_for_customer_name' 			=>  implode(",", array_unique($finder_poc_for_customer_name_arr)),
                'finder_vcc_email' 						=>  implode(",", array_unique($finder_vcc_email_arr)),
                'finder_vcc_mobile' 					=>  implode(",", array_unique($finder_vcc_mobile_arr)),
                'status' 								=>  (isset($Finder->hidden) && $Finder->hidden === false) ? "1" : "0",
                'budget' 								=>  (isset($Finder->cost) && isset($Finder->cost['average_price']) && $Finder->cost['average_price'] != "") ? intval($Finder->cost['average_price']) : 0,
                'price_range' 							=>  (isset($Finder->cost) && isset($Finder->cost['price_range']) && $Finder->cost['price_range'] != "") ? trim($Finder->cost['price_range']) : "one",
                'purchase_gamification_disable' 		=>  (isset($Finder->flags) && isset($Finder->flags['purchase_gamification_disable']) && $Finder->flags['purchase_gamification_disable'] === true) ? "1" : "0",
                'trial' 		                        =>  (isset($Finder->flags) && isset($Finder->flags['trial'])) ? $Finder->flags['trial'] : "auto",
                'membership' 		                    =>  (isset($Finder->flags) && isset($Finder->flags['membership'])) ? $Finder->flags['membership'] : "auto",
                'manual_trial_enable' 				    =>  (isset($Finder->manual_trial_enable) && $Finder->manual_trial_enable === true) ? "1" : "0",
                'manual_trial_auto' 				    =>  (isset($Finder->manual_trial_auto) && $Finder->manual_trial_auto === true) ? "1" : "0",
                'created_at' 							=>  (isset($Finder->created_at)) ? $Finder->created_at : $Finder->updated_at,
                'updated_at' 							=>  $Finder->updated_at,
                'custom_city'                           =>  isset($Finder->custom_city) ? $Finder->custom_city : "",
                'custom_location'                       =>  isset($Finder->custom_location) ? $Finder->custom_location : "",
                'flags'                                 =>  isset($Finder->flags) ? $Finder->flags : array(),
                'renewal_remark'                        =>  isset($Finder->renewal_remark) ? $Finder->renewal_remark : "",
                'backend_flags'                         =>  isset($Finder->backend_flags) ? $Finder->backend_flags : array(),
                'offer_texts'                           =>  isset($Finder->offer_texts) ? $Finder->offer_texts : array(),
                'inoperational_dates'                   =>  isset($Finder->inoperational_dates) ? $Finder->inoperational_dates : array(),
                'servicesfilter' 			            =>  (isset($Finder->filter) && isset($Finder->filter['servicesfilter'])) ? $Finder->filter['servicesfilter'] : [],
                'trainer_contacts'                      =>  $trainer_contacts,
//                 'callout'                               =>  isset($Finder->callout) ? $Finder->callout : "",
//             	'callout_ratecard_id'               =>  isset($Finder->callout_ratecard_id) ? $Finder->callout_ratecard_id: "",
                'poc'                                   => $finder_poc
            ];

    

            $insertData['vip_trial']                    = (isset($Finder->vip_trial) &&  $Finder['vip_trial'] == true ) ? '1' : '0';
            $insertData['finder_type']                    = (isset($insertData['commercial_type']) && !empty(($insertData['commercial_type'])) ) ? (( $insertData['commercial_type'] == 1  || $insertData['commercial_type'] == 3 ) ? 1: 0) :0;

            if(!empty($Finder->website_membership)){
                $insertData['website_membership'] = $Finder->website_membership;
            }

//            dd($Finder->flags['membership']);
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

            Log::info("before flush");

            $this->cacheapi->flushTagKey('finder_detail',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail_android',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail_ios',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail_ios_4_4_3',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail_android_4_4_3',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail_ios_5_1_5',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail_ios_5_1_6',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail_android_5_1_8',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail_android_5_1_9',$entity->slug);
            $this->cacheapi->flushTagKey('finder_detail',($entity->slug).'-thirdp');

            Log::info("after flush");
            


            $finder_id = intval($entity['_id']);

            //manage categorytags
            try{
                $removeFinderIdsCategorytags      =    Findercategorytag::where('finders', $finder_id)->pull('finders',$finder_id);
            }catch(Exception $e){
                Log::error($e);
            }
            if (isset($entity['categorytags']) && !empty($entity['categorytags'])) {
                $findercategorytags = array_map('intval', $entity['categorytags']);
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->categorytags()->sync(array());
                foreach ($findercategorytags as $key => $value) {
                    $finder->categorytags()->attach($value);
                }
            }

            //manage locationtags
            try{
                $removeFinderIdsLocationtag      =    Locationtag::where('finders', $finder_id)->pull('finders',$finder_id);
            }catch(Exception $e){
                Log::error($e);
            }
            if (isset($entity['locationtags']) && !empty($entity['locationtags'])) {
                $finderlocationtags = array_map('intval', $entity['locationtags']);
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->locationtags()->sync(array());
                foreach ($finderlocationtags as $key => $value) {
                    $finder->locationtags()->attach($value);
                }
            }


            //manage facilities
            try{
                $removeFinderIdsFacilities      =    Facility::where('finders', $finder_id)->pull('finders',$finder_id);
            }catch(Exception $e){
                Log::error($e);
            }

            if (isset($entity['facilities']) && !empty($entity['facilities'])) {

                $finderfacilities = array_map('intval', $entity['facilities']);
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->facilities()->attach($finderfacilities[0]);
                $finder->facilities()->sync(array());
                foreach ($finderfacilities as $key => $value) {
                    $finder->facilities()->attach($value);
                }
            }else{
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->facilities()->sync(array());
            }

            //manage offerings
            try{
                $removeFinderIdsOffering      =    Offering::where('finders', $finder_id)->pull('finders',$finder_id);
            }catch(Exception $e){
                Log::error($e);
            }
            if (isset($entity['offerings']) && !empty($entity['offerings'])) {
                $finderofferings = array_map('intval', $entity['offerings']);
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->offerings()->sync(array());
                foreach ($finderofferings as $key => $value) {
                    $finder->offerings()->attach($value);
                }
            }else{
                $finder = Finder::on($this->fitadmin)->find($finder_id);
                $finder->offerings()->sync(array());
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

            $insertData = [
                'calorie_burn'	=>  [
                    'avg' 	=>  (isset($data['calorie_burn']) && isset($data['calorie_burn']["avg"]) ) ? intval($data['calorie_burn']["avg"]) : 0,
                    'type' 	=>  (isset($data['calorie_burn']) && isset($data['calorie_burn']["type"]) ) ? $data['calorie_burn']["type"] : "kcal"
                    ]
            ];

            $insertData['finder_id'] = (int)$data['vendor_id'];
            $insertData['name'] = $data['name'];
            $insertData['servicecategory_id'] = (int)$data['category']['primary'];
            $insertData['servicesubcategory_id'] = (int)$data['category']['secondary'];
            $insertData['lat'] = $data['geometry']['coordinates']['0'];
            $insertData['lon'] = $data['geometry']['coordinates']['1'];
            $insertData['location_id'] = (int)$data['location_id'];
            $insertData['city_id'] = (int)$data['city_id'];
            $insertData['workout_intensity'] = (isset($data['workout_intensity'])) ? $data['workout_intensity'] : "";
            $insertData['enrollment_desc'] = (isset($data['enrollment_desc'])) ? $data['enrollment_desc'] : "";
            $insertData['session_type'] = (isset($data['session_type'])) ? $data['session_type'] : "";
            $insertData['meal_type'] = (isset($data['meal_type'])) ? $data['meal_type'] : "";
            $insertData['workout_tags'] = (isset($data['workout_tags']) && !empty($data['workout_tags'])) ? $data['workout_tags'] : [];
            $insertData['workout_results'] = (isset($data['workout_results']) && !empty($data['workout_results'])) ? $data['workout_results'] : [];
            $insertData['status'] = (isset($data['hidden']) && $data['hidden'] == true) ? '0' : '1';
            $insertData['deduct'] = (isset($data['trial_cashback_status'])  && $data['trial_cashback_status'] == true) ? '1' : '0';
            $insertData['rockbottom'] = (isset($data['rockbottom_price_status'])  && $data['rockbottom_price_status'] == true) ? '1' : '0';
            $insertData['vip_trial'] = (isset($data['vip_trial'])  && $data['vip_trial'] == true) ? '1' : '0';
            $insertData['ordering'] = (int)$data['order'];
            $insertData['short_description'] = $data['info']['short_description'];
            if(!empty($data['info']['pps_description'])){
                $insertData['pps_description'] = $data['info']['pps_description'];
            }
            $insertData['body'] = $data['info']['long_description'];
            $insertData['address'] = ($data['address']['line1'] == '' && $data['address']['line1'] == '' && $data['address']['line1'] == '' && $data['address']['pincode'] == '' && $data['address']['landmark'] == '') ? '' : $data['address']['line1'].', '.$data['address']['line2'].', '.$data['address']['line3'].', '.$data['address']['landmark'].', '.$data['address']['pincode'];
            $insertData['what_i_should_carry'] = $data['what_i_should_carry'];
            $insertData['what_i_should_expect'] = $data['what_i_should_expect'];
            $insertData['photos']	=  (isset($data['gallery']) && count($data['gallery']) > 0) ? array_values($data['gallery']) : [];
            $insertData['timings'] = (isset($data['timings'])) ? $data['timings'] : "";

            if(isset($data['provided_by']) && $data['provided_by'] !== 0){
                $insertData['trainer_id'] = $data['provided_by'];
            }
            $insertData['trial']        = (isset($data['flags']) && isset($data['flags']['trial'])) ? $data['flags']['trial'] : "auto";
            $insertData['membership']   = (isset($data['flags']) && isset($data['flags']['membership'])) ? $data['flags']['membership'] : "auto";
            $insertData['diet_inclusive']        = (isset($data['flags']) && isset($data['flags']['diet_inclusive'])) ? $data['flags']['diet_inclusive'] : false;
            $insertData['show_on']      =   "1";
            $insertData['created_at']   =   $data['created_at'];
            $insertData['updated_at']   =   $data['updated_at'];
            $insertData['showOnFront']   =   isset($data['showOnFront']) ? $data['showOnFront'] : ['web', 'kiosk'];
            // $insertData['showOnFront']   =   isset($data['showOnFront']) ? $data['showOnFront'] : true;
            $insertData['custom_location']   =   isset($data['custom_location']) ? $data['custom_location'] : "";
            $insertData['inoperational_dates']   =   isset($data['inoperational_dates']) ? $data['inoperational_dates'] : [];
            
            if(isset($data['flags'])){
                $insertData['flags'] = $data['flags'];
            }
            if(isset($data['combine_service_ids'])){
                $insertData['combine_service_ids'] = $data['combine_service_ids'];
            }
            if(isset($data['flags'])){
                $insertData['flags'] = $data['flags'];
            }

            if(isset($data['slug'])){
                $insertData['slug'] = $data['slug'];
            }

            $insertData['membership_start_date'] = null;
            if(isset($data['membership_start_date'])){
                $insertData['membership_start_date'] = $data['membership_start_date'];
            }

            $insertData['membership_end_date'] = null;
            if(isset($data['membership_end_date'])){
                $insertData['membership_end_date'] = $data['membership_end_date'];
            }
            
            if(isset($data['pps_non_peak_hours_time_range'])){
                $insertData['pps_non_peak_hours_time_range'] = $data['pps_non_peak_hours_time_range'];
            }
//            return $insertData;

            $service_exists = Service::on($this->fitadmin)->find(intval($id));

            if($service_exists){
                $service_exists->update($insertData);
                $this->updatescheduleByServiceId($id);
//                return var_dump($service_exists->toArray());
            }else{
                $insertData['trialschedules'] = [];
                $insertData['workoutsessionschedules'] = [];
                $insertData['batches'] = [];

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
            $insertData['duration_type'] = $data['quantity_type'];
            $insertData['created_at'] = $data['created_at'];
            $insertData['updated_at'] = $data['updated_at'];
            if(isset($data['combo_pass_id'])){
                $insertData['combo_pass_id'] = (int)$data['combo_pass_id'];
            }
            
            if(isset($data['expiry_date'])){
                $insertData['expiry_date'] = $data['expiry_date'];
            }
            
            if(isset($data['start_date'])){
                $insertData['start_date'] = $data['start_date'];
            }

            if(isset($data['weight']) && $data['weight'] != ""){
                $insertData['weight'] = (int)$data['weight'];
            }

            if(isset($data['weight_type']) && $data['weight_type'] != ""){
                $insertData['weight_type'] = $data['weight_type'];
            }

            if(isset($data['flags'])){
                $insertData['flags'] = $data['flags'];
            }

            if(isset($data['diet_ratecard'])){
                $insertData['diet_ratecard'] = $data['diet_ratecard'];
            }

            if(isset($data['vendor_price'])){
                $insertData['vendor_price'] = $data['vendor_price'];
            }
            
            if(isset($data['remarks_imp'])){
                $insertData['remarks_imp'] = $data['remarks_imp'];
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


    public function deleteRatecard($id){

        try{

            $ratecard   =   Ratecard::on($this->fitadmin)->find(intval($id));

            if($ratecard){

                $delete = Ratecard::destroy(intval($id));

                $hideOffers = Offer::where('ratecard_id', intval($id))->update(['hidden'=>true]);                

                $finder = Finder::on($this->fitadmin)->find(intval($ratecard->finder_id));
                $this->cacheapi->flushTagKey('finder_detail',$finder->slug);

                $response = array('status' => 200, 'message' => 'Success');
            }

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
            $trialRatecard_exists_cnt	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'trial')->where('hidden', false)->count();

            if($trialRatecard_exists_cnt === 0){
                $trialRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'trial')->where('hidden', false)->first();
            }else{
                $trialRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'trial')->where('quantity',1)->where('hidden', false)->first();
            }

            if($trialRatecard && isset($trialRatecard['price'])){
                $trialPrice = (isset($trialRatecard['selling_price']) && intval($trialRatecard['selling_price']) > 0) ? $trialRatecard['selling_price'] : $trialRatecard['price'];
            }

            //Workout session Price From Ratecard
            $workoutSessionPrice = 0;
            $workoutSessionRatecard_exists_cnt	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'workout session')->where('hidden', false)->count();

            if($workoutSessionRatecard_exists_cnt === 0){
                $workoutSessionRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'workout session')->where('hidden', false)->first();
            }else{
                $workoutSessionRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($schedule->vendorservice_id))->where('type', 'workout session')->where('quantity',1)->where('hidden', false)->first();
            }

            if($workoutSessionRatecard && isset($workoutSessionRatecard['price'])){
                $workoutSessionPrice = (isset($workoutSessionRatecard['selling_price']) && intval($workoutSessionRatecard['selling_price']) > 0) ? $workoutSessionRatecard['selling_price'] : $workoutSessionRatecard['price'];
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

            if(count($schedules) > 0){

            //Trial Price From Ratecard
            $trialPrice = 0;
            $trialRatecard_exists_cnt	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->where('hidden', false)->count();

            if($trialRatecard_exists_cnt === 0){
                $trialRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->where('hidden', false)->first();
            }else{
                $trialRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->where('quantity',1)->where('hidden', false)->first();
            }

            if($trialRatecard && isset($trialRatecard['price'])){
                $trialPrice = (isset($trialRatecard['selling_price']) && intval($trialRatecard['selling_price']) > 0) ? $trialRatecard['selling_price'] : $trialRatecard['price'];
            }

            //Workout session Price From Ratecard
            $workoutSessionPrice = 0;
            $workoutSessionRatecard_exists_cnt	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->where('hidden', false)->count();

            if($workoutSessionRatecard_exists_cnt === 0){
                $workoutSessionRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->where('hidden', false)->first();
            }else{
                $workoutSessionRatecard	=	DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->where('quantity',1)->where('hidden', false)->first();
            }

            if($workoutSessionRatecard && isset($workoutSessionRatecard['price'])){
                $workoutSessionPrice = (isset($workoutSessionRatecard['selling_price']) && intval($workoutSessionRatecard['selling_price']) > 0) ? $workoutSessionRatecard['selling_price'] : $workoutSessionRatecard['price'];
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

//                print_pretty($workoutsessionschedules);exit;

            $service_exists = Service::on($this->fitadmin)->find(intval($schedule->vendorservice_id));
            if($service_exists){
                $service_exists->update([
                    'trialschedules' => $trialschedulesdata,
                    'workoutsessionschedules' => $workoutsessionschedules
                ]);

                $finder = Finder::on($this->fitadmin)->find(intval($service_exists->finder_id));

                $this->cacheapi->flushTagKey('finder_detail',$finder->slug);
            }
            }else{
                $serivce_ids    =   Service::where('_id',intval($vendorservice_id))->update(['trialschedules'=>[],'workoutsessionschedules'=>[]]);
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
                        if(isset($slot['day']) && isset($slot['duration'])){
                            $batch_weekdays_data['weekday'] =	$slot['day'];

                            $slot_times 			=	explode('-',$slot['duration']);
                            $start_time 	        =	$slot_times[0];
                            $end_time 	            =	$slot_times[1];

                            $batch_weekdays_data['slots'] =	[
                                [
                                    'weekday' => $slot['day'],
                                    'start_time' => $start_time,
                                    'end_time' => $end_time,
                                    'slot_time' => $slot['duration'],
                                    'limit' => (isset($slot['limit'])) ?  intval($slot['limit']) : 0,
                                    'price' => (isset($slot['price'])) ?  intval($slot['price']) : 0
                                ]
                            ];

                            // return $batch_weekdays_data;
                            array_push($batchdata, $batch_weekdays_data);
                            // return $batchdata;
                        }

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



    public function deletescheduleByVendorId($finder_id){

        try{

            $serivce_ids    =   Service::where('finder_id',intval($finder_id))->update(['trialschedules'=>[],'workoutsessionschedules'=>[]]);

            // foreach ($serivce_ids as $serivce_id){
            //     $service = Service::find($serivce_id);
            // }

            $finder = Finder::on($this->fitadmin)->find(intval($finder_id));

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


 public function deleteBatchByServiceId($service_id){
        


        try{

            $batches        =  Batch::where('vendorservice_id',intval($service_id))->get();
            $batchesdata    = [];


                 
             if(count($batches) > 0){

                    // return $batches;
                    foreach ($batches as $key => $batch) {
                        // return $batch;
                        $batchdata              =   [];
                        // $weekdaydata['slots']    =   [];

                        if(isset($batch['slots'])){
                            $batch_weekdays_data  = [];

                            foreach ($batch['slots'] as $k => $slot) {
                                // return $slot;
                                $batch_weekdays_data['weekday'] =   $slot['day'];

                                if (intval($slot['start_time']['hours']) < 12) {
                                    $start_time = $slot['start_time']['hours'] .":00 am";
                                }else{

                                     $start_time = "12:00 pm";

                                    if(intval($slot['start_time']['hours']) != 12){
                                        $start_time = (intval($slot['start_time']['hours']) - 12) .":00 pm";         
                                    }                            

                                }

                                if (intval($slot['end_time']['hours']) < 12) {
                                    $end_time = $slot['end_time']['hours'] .":00 am";
                                }else{

                                    $end_time = "12:00 pm";
                                    
                                     if(intval($slot['end_time']['hours']) != 12){
                                        $end_time = (intval($slot['end_time']['hours']) - 12) .":00 pm";
                                    }
                                }

                                $batch_weekdays_data['slots'] = [
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

            }

            // return $batchesdata;

            $service_exists = Service::on($this->fitadmin)->find(intval($service_id));
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






    /**
     * Migration for updatescheduleByServiceId
     */
    public function updatescheduleByServiceIdV1($vendorservice_id){

        try{

            $vendorservice_id = $vendorservice_id;

            $schedules = Schedule::where('vendorservice_id',intval($vendorservice_id))->get();

            if(count($schedules) > 0){

                //Trial Price From Ratecard
                $trialPrice = 0;
                $trialRatecard_exists_cnt  =   DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->where('hidden', false)->count();

                if($trialRatecard_exists_cnt < 2){
                    $trialRatecard  =   DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->where('hidden', false)->first();
                }else{
                    $trialRatecard  =   DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'trial')->where('quantity',1)->where('hidden', false)->first();
                }

                if($trialRatecard && isset($trialRatecard['price'])){
                    $trialPrice = (isset($trialRatecard['selling_price']) && intval($trialRatecard['selling_price']) > 0) ? $trialRatecard['selling_price'] : $trialRatecard['price'];
                }

//                var_dump($vendorservice_id); exit;

                //Workout session Price From Ratecard
                $workoutSessionPrice = 0;
                $workoutSessionRatecard_exists_cnt  =   DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->where('hidden', false)->count();

                if($workoutSessionRatecard_exists_cnt < 2){
                    $workoutSessionRatecard =   DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->where('hidden', false)->first();
                }else{
                    $workoutSessionRatecard =   DB::connection($this->fitapi)->table('ratecards')->where('vendorservice_id',intval($vendorservice_id))->where('type', 'workout session')->where('quantity',1)->where('hidden', false)->first();
                }

//                print_pretty($workoutSessionRatecard); exit;

                if($workoutSessionRatecard && isset($workoutSessionRatecard['price'])){
                    $workoutSessionPrice = (isset($workoutSessionRatecard['selling_price']) && intval($workoutSessionRatecard['selling_price']) > 0) ? $workoutSessionRatecard['selling_price'] : $workoutSessionRatecard['price'];
                }else{
                    $workoutSessionPrice = $trialPrice;
                }

//                var_dump($trialPrice); exit;
                if($workoutSessionPrice < 1){
                    $service = Service::find(intval($vendorservice_id));
                    if($service && isset($service['servicecategory_id']) ){
                        $sercviecategory_id = intval($service['servicecategory_id']);
                        $workoutSessionPrice = ($sercviecategory_id == 65) ? 300 : 500;
                    }
                }


//                echo $workoutSessionPrice; exit;

                $trialschedulesdata = [];

                $workoutsessionschedules = [];

                foreach ($schedules as $key => $schedule) {
                    $weekdaydata                =   [];
                    $type                       =   trim($schedule['type']);

                    if(isset($schedule['slots']) && count($schedule['slots']) > 0 && $type == 'trial'){
                        $weekdaydata['weekday']     =   $schedule['day'];
                        $weekdaydata['slots']       =   [];

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
                        array_push($trialschedulesdata, $weekdaydata);

                    }



//                echo $workoutSessionPrice; exit;

                    if($workoutSessionPrice > 0){
                        $weekdaydata                =   [];


                        if(isset($schedule['slots']) && count($schedule['slots']) > 0 && $type == 'trial'){

                            $weekdaydata['weekday']     =   $schedule['day'];
                            $weekdaydata['slots']       =   [];

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
                            }//foreach
                            array_push($workoutsessionschedules, $weekdaydata);

                        }//if

                    }

                }//foreach

//            return $workoutsessionschedules;

//                return $schedule->vendorservice_id;
                $service_exists = Service::on($this->fitadmin)->find(intval($schedule->vendorservice_id));
                if($service_exists){

                    $updateServiceData = [
                        'trialschedules' => $trialschedulesdata,
                        'workoutsessionschedules' => $workoutsessionschedules
                    ];

//                    print_pretty($updateServiceData);exit();

                    $update_service = Service::on($this->fitadmin)->find(intval($schedule->vendorservice_id))->update($updateServiceData);


                    try{
                        $finder = Finder::on($this->fitadmin)->find(intval($service_exists->finder_id));
                        $this->cacheapi->flushTagKey('finder_detail',$finder->slug);
                    }catch(Exception $e){
                        Log::error($e);

                    }
                }
            }else{
                $serivce_ids    =   Service::where('_id',intval($vendorservice_id))->update(['trialschedules'=>[],'workoutsessionschedules'=>[]]);
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


    public function deleteWorkoutSessionRatecard(){

        $vendor_ids = Vendor::on($this->fitapi)->whereIn("vendorcategory.primary",array(45,42))->lists("_id");

        $vendor_ids = array_map('intval',$vendor_ids);

        echo "vendor_ids"; echo "<pre>";print_r($vendor_ids);

        $ratecard_ids = Ratecard::on($this->fitapi)->whereIn("vendor_id",$vendor_ids)->where("type","workout session")->where("hidden",false)->lists("_id");

        echo "ratecard_ids"; echo "<pre>";print_r($ratecard_ids);

        $ratecards = Ratecard::on($this->fitadmin)->whereIn("_id",$ratecard_ids)->delete();

        /*foreach ($ratecard_ids as $ratecard_id) {

            $this->deleteRatecard($ratecard_id);
        }*/

        $ratecards = Ratecard::on($this->fitapi)->whereIn("_id",$ratecard_ids)->update(['hidden' => true]);

        echo "done";

    }

    public function brand($id){
        try{
            $brand       =   Brand::on($this->fitapi)->find(intval($id));

            $insertData = [
                'name' =>  trim($brand->name),
                'status' =>  (isset($brand->hidden) && $brand->hidden === false) ? "1" : "0",
                'created_at' =>  (isset($brand->created_at)) ? $brand->created_at : $brand->updated_at,
                'updated_at' =>  $brand->updated_at,
                'slug' => $brand->slug,
                'description' => isset($brand->description) ? $brand->description : "",
                'coverImage' => isset($brand->media) && isset($brand->media['images']) && isset($brand->media['images']['cover']) ? $brand->media['images']['cover'] : "",
            	'vendor_stripe' => isset($brand->vendor_stripe)? $brand->vendor_stripe:null,
                'logo' => isset($brand->media) && isset($brand->media['images']) && isset($brand->media['images']['logo']) ? $brand->media['images']['logo'] : "",
            ];
            
            if(!empty($brand->brand_website)){
                $insertData['brand_website'] = $brand->brand_website; //storing data of vendor for brand website
            }

            $_exists_cnt =   DB::connection($this->fitadmin)->table('brands')->where('_id', intval($id) )->count();

            if($_exists_cnt === 0){
                $entity         =   new Brand($insertData);
                $entity->setConnection($this->fitadmin);
                $entity->_id    =   intval($brand->_id);
                $entity->save();
            }else{
                // $country = Country::on($this->fitadmin)->where('_id', intval($id) )->update($insertData);
                $brand = Brand::on($this->fitadmin)->find(intval($id));
                $brand->update($insertData);
            }

            $vendor_ids = Vendor::where('brand_id', intval($id))->lists('_id');
            Finder::whereIn('_id', $vendor_ids)->update(['brand_id'=> intval($id)]);
            Finder::whereNotIn('_id', $vendor_ids)->where('brand_id', intval($id))->update(['brand_id'=> null]);
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

    public function workoutRatecardReverseMigrate(){

        ini_set('memory_limit','2048M');
	    ini_set('max_execution_time', 30000);
        $ratecard_ids = Ratecard::where('type', 'workout session')->lists('_id');
        // return $ratecard_ids;
        $t1 = time();
        foreach($ratecard_ids as $key => $id){
            Log::info($key);
            if($key % 100 == 0){
                Log::info("+++++++++++++++++++++++++++++++++++".$key);
                Log::info(time()-$t1);
                $t1 = time();
            }
            Log::info($this->ratecard($id));
        }
		
    }

    public function offer($id){
        
        $offer = Offer::where('_id', $id)->first();
        $ratecard = Ratecard::where('_id', $offer->ratecard_id)->first();

        if(isset($ratecard->available_slots) && (!isset($ratecard->available_slots_end_date) || strtotime($offer->start_date) > time() || strtotime($offer->end_date) < $ratecard->available_slots_end_date)){
            $ratecard->unset(["available_slots","total_slots_created","available_slots_update_at","available_slots_default","available_slots_end_date","expiring_end_date"]);
            $deactivate_offers = Offer::where('added_by_script', true)->where('ratecard_id', $offer->ratecard_id)->where('hidden', false)->update(['hidden'=>true]);
        }





    }

}
