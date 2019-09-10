<?php

class CampaignsController extends \BaseController {

	public function __construct() {
		parent::__construct();		
	}

	/**
	 * Display a listing of homepages
	 *
	 * @return Response
	 */
	public function getcampaigncategories($id){
		$id = (int) $id;
		$camp = Campaign::find($id);
		$campaign_categories = array_map('intval', explode(",", $camp['campaign_categories'] ));
		$categories = Findercategorytag::active()->whereIn('_id', $campaign_categories)->remember(Config::get('app.cachetime'))->get(array('_id','slug','name'))->toArray();
		$location = Location::active()->where('cities', $camp['city_id'])->remember(Config::get('app.cachetime'))->get(array('_id','slug','name'))->toArray();
		$camp = Campaign::select('feature_finders','featured_finders')->find($id);
		$campaign = array('category' => $categories, 'location'=>$location,'campaign' =>$camp);
		return $campaign;
	}
	public function campaignsearch(){
		$from    =         Input::json()->get('from') ? Input::json()->get('from') : 0;
		$size    =         Input::json()->get('size') ? Input::json()->get('size') : 10;
		$campaign  =         intval(Input::json()->get('campaign'));
		$category     =         Input::json()->get('category') ? Input::json()->get('category') : '';
		$location     =         Input::json()->get('location') ? Input::json()->get('location') : '';
		$offerings     =         Input::json()->get('offerings') ? Input::json()->get('offerings') : '';
		$days     =         Input::json()->get('days') ? Input::json()->get('days') : '';
		$camp = Campaign::remember(Config::get('app.cachetime'))->find($campaign);
		$campfinders = array_map('intval', explode(",", $camp['campaign_finders'] ));
		$query = Finder::active()->whereIn('_id',$campfinders);
		if($category != ''){
			$category = array_map('intval', explode(",", $category ));
			$query = $query->orWhereIn('categorytags',$category);
		}
		if($location != ''){
			$location = array_map('intval', explode(",", $location ));
        	$query = $query->orWhereIn('locationtags',$location);
		}
		if($offerings != ''){
			$offerings = array_map('intval', explode(",", $offerings ));
        	$query = $query->orWhereIn('offerings',$offerings);
		}
		if($days != ''){
			$days = explode(",", $days );
			$query = $query->with(['services'=>function($query) use ($days){
				return $query->whereIn('trialschedules.weekday',$days);
			}]);
		}
		$data = [$from,$size,$campaign,$category,$location];
		$finders = $query->with('location')->get();
		$payload = array("cat" => $category, "loc" => $location, "offers" => $offerings, "days"=>$days);
		$finders = $this->sortmyfinders($finders,$payload);
  //       usort($finders, function($a, $b) { //Sort the array using a user defined function
		//     return $a->flag > $b->flag ? -1 : 1; //Compare the scores
		// });
		return Response::json($finders,200);
	}
	public function registercustomer()
	{
		$data = Input::json()->all();
		$rules = [
		'name' => 'required|max:255',
		'email' => 'required|email|max:255',
		'contact_no' => 'max:15',
		'code' => 'required'
		];
		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);
		}else{
			$customer = Customer::where('email','=',$data['email'])->first();
			if(empty($customer)){
				$code = Ubercode::where('code',$data['code']."\n")->where('status',1)->first();
				if(empty($code)){
					return Response::json(array('status' => 400,'message' => "The code is either invalid or already used"),400);
				}
				$inserted_id = Customer::maxId() + 1;
				$account_link = array('email'=>1,'google'=>0,'facebook'=>0,'twitter'=>0);
				$customer = new Customer();
				$customer->_id = $inserted_id;
				$customer->name = ucwords($data['name']) ;
				$customer->email = $data['email'];
				$customer->picture = "https://www.gravatar.com/avatar/".md5($data['email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
				$customer->hull_id = "Selfregistered";
				$customer->ishulluser = 1;
				$customer->password = md5("Fitternity");
				if(isset($data['contact_no'])){
					$customer->contact_no = $data['contact_no'];
				}
				$customer->identity = "email";
				$customer->account_link = $account_link;
				$customer->status = "1";
				$customer->uber_code = $data['code'];
				$customer->save();
			}
			else{
				$code = Ubercode::where('code',$data['code']."\n")->first();
				if(empty($code)){
					return Response::json(array('status' => 400,'message' => "The code is either invalid"),400);
				}
				else{
					if(isset($code->customer_email) && $code->customer_email == $customer->email){
						$customer_data = array('name'=> $customer->name, 'email'=> $customer->email, 'uber_code'=>$customer->uber_code, 'contact_no'=> $customer->contact_no);
						return $customer_data;
					}
					elseif($code->status == 0){
						return Response::json(array('status' => 400,'message' => "The code is already used"),400);
					}
					else{
						$customer->uber_code = $data['code'];
						$customer->save();	
					}
				}
			}
			$code->status = 0;
			$code->customer_email = $customer->email;
			$code->save();
			$customer_data = array('name' => $customer->name,'email' => $customer->email, 'uber_code'=>$customer->uber_code,'contact_no'=> $customer->contact_no);
			return $customer_data;
		}
	}
	public function campaignregistercustomer()
	{
		$data = Input::json()->all();
		$rules = [
		'name' => 'required|max:255',
		'email' => 'required|email|max:255',
		'contact_no' => 'max:15',
		];
		$validator = Validator::make($data,$rules);

		if ($validator->fails()) {
			return Response::json(array('status' => 400,'message' => $this->errorMessage($validator->errors())),400);
		}else{
			$customer = Customer::where('email','=',$data['email'])->first();
			if(empty($customer)){
				$inserted_id = Customer::maxId() + 1;
				$account_link = array('email'=>1,'google'=>0,'facebook'=>0,'twitter'=>0);
				$customer = new Customer();
				$customer->_id = $inserted_id;
				$customer->name = ucwords($data['name']) ;
				$customer->email = $data['email'];
				$customer->picture = "https://www.gravatar.com/avatar/".md5($data['email'])."?s=200&d=https%3A%2F%2Fb.fitn.in%2Favatar.png";
				$customer->hull_id = "Selfregistered";
				$customer->ishulluser = 1;
				$customer->password = md5("Fitternity");
				if(isset($data['contact_no'])){
					$customer->contact_no = $data['contact_no'];
				}
				$customer->identity = "email";
				$customer->account_link = $account_link;
				$customer->status = "1";
				$customer->save();
			}
			$customer_data = array('name' => $customer->name,'email' => $customer->email, 'contact_no'=> $customer->contact_no);
			return $customer_data;
		}
	}
	public function getcampaigntrials($campaignid='',$email= '')
	{
		$customer = Customer::where('email','=',$email)->first();
		switch($campaignid){
			case "1": if(isset($customer->uber_code)){
						$customer_data = array('name' => $customer->name,'email' => $customer->email, 'uber_code'=>$customer->uber_code,'contact_no'=> $customer->contact_no, 'uber_trial'=>$customer->uber_trial);
						return $customer_data;	
					}
					else{
						return Response::json(array('status' => 400,'message' => "Not a valid Uber Customer"),400); 
					}
					break;
			case "3":
			case "4":
			case "5":
			case "6":
			 $customer_data = array('name' => $customer->name,'email' => $customer->email,'contact_no'=> $customer->contact_no, 'uber_trial'=>$customer->ttt_trial);
			 break;
		}
		return $customer_data;	
	}
	function sortmyfinders($finders, $payload){
		$arr = [];
		// print_r($payload);
		// exit;
		foreach ($finders as $key => $value) {
			$flag = 0;
			if($payload['cat'] != '' && !empty(array_intersect($payload['cat'], $value['categorytags']))){
				$flag++;
			}
			if($payload['loc'] != '' && !empty(array_intersect($payload['loc'], $value['locationtags']))){
				$flag++;
			}
			if($payload['offers'] != '' && !empty(array_intersect($payload['offers'], $value['offerings']))){
				$flag++;
			}
			if($payload['days'] != ''){
				if( count($value['services']) > 0){
					array_push($arr, $value);
				}
				else{
				}
			}
			else{
				$value['flag'] = $flag;
				array_push($arr, $value);
			}
		}
		return $arr;
	}
	public function errorMessage($errors){

		$errors = json_decode(json_encode($errors));
		$message = array();
		foreach ($errors as $key => $value) {
			$message[$key] = $value[0];
		}
		return $message;
	}

	// If a campaign is related to any service Created by Utkarsh on 26th April 2016
	public function campaignServices($city_id,$campaign_name){
		$city_id = isset($city_id) && $city_id != "" ? intval($city_id) : 1;
		$campaign = isset($campaign) && $campaign != "" ? $campaign : "crossfit-week";

		$services = Service::where('campaign_type',$campaign)->with('location')->with('city')->with('finder')->get(array('name','location_id','location','category_id','city_id','category','city','finder_id', 'finder'))->groupBy('city_id');
		$blogs = Blog::whereIn("_id",array(307,308,309,310))->with('author')->get();
		return Response::json(array("services"=>$services,"blogs"=>$blogs));
	}

	public function yogaDay($city_id){

		$yoga_form = array();

    	$yoga_form[] = array('title'=>'Hatha Yoga','description'=>'Hatha Yoga is the practice of asanas (postures) and is commonly referred to as the yoga of balance that aligns your body with mind and spirit. In literal translation it is a willful activity and can be broken down into a combination of sun “ha” and moon “tha”. It is a way of disciplining, purifying and preparing the body for higher levels of energy. It encompasses all physical yoga activities under its branch.','image'=>'');
    	$yoga_form[] = array('title'=>'Iyengar Yoga','description'=>'The most commonly practiced form of Yoga, Iyengar Yoga lays emphasis on detail, precision, alignment and breath control while performing body posture. This in turn helps use the body energy to improve mobility as well as stability. The Asanas and Pranayamas are performed in a certain sequence and may involve use of props like yoga blocks, belts and ropes.','image'=>'');
    	$yoga_form[] = array('title'=>'Vinyasa Yoga','description'=>'Vinyasa Yoga is a branch of Yoga, that is characterized by aligning movements to breath, with an emphasis on inhaling and exhaling in unison with body postures or asanas being practiced. A typical Vinyassa Flow class showcases a dynamic transition across postures.','image'=>'');
    	$yoga_form[] = array('title'=>'Ashtanga Yoga','description'=>'Ashtanga Yoga is denoted as the “eight-limbed yoga” as defined in the Yoga Sutra by the sage Patanjali. It follows the eight spiritual practices of Moral Codes (Yama), Self-purification (Niyama), Posture (Asana), Breath control (Pranayama), Sense control (Pratyahara), Concentration (Dharana), Meditation (Dhyana) and absorption (Samadhi), to create a ritual of synchronized movement, breathing and concentration.','image'=>'');
    	$yoga_form[] = array('title'=>'Aerial Yoga','description'=>'Aerial Yoga is characterized by a series of exercises and postures inspired from Hatha Yoga, Pilates and Calisthenics, performed while being suspended on wires or hammocks. Also known as Anti-gravity Yoga this is an engaging and contemporary branch of Yoga, popularized by the West.','image'=>'');
    	$yoga_form[] = array('title'=>'Power Yoga','description'=>'With a stronger focus on fitness, Power Yoga is synchronized movement from one body posture to another with strong emphasis on breathing. It differentiates from others because of its raised levels of intensity and vigor, leading to a higher calorie burn.','image'=>'');
    	$yoga_form[] = array('title'=>'Traditional Yoga','description'=>'Traditional Yoga functions on the principle of the body being a whole and thus the focus is on a state of being rather than the individual body parts. Thus, the body postures or Asanas are only a small part. The focus is laid on the development of relationships, senses, body, breath and mind.','image'=>'');

    	$personal_trainer = array();

    	$personal_trainer[] = array('title'=>'Iyengar Yoga','average_rating'=>5,'image'=>'');
    	$personal_trainer[] = array('title'=>'Iyengar Yoga','average_rating'=>5,'image'=>'');
    	$personal_trainer[] = array('title'=>'Iyengar Yoga','average_rating'=>5,'image'=>'');
    	$personal_trainer[] = array('title'=>'Iyengar Yoga','average_rating'=>5,'image'=>'');
    	$personal_trainer[] = array('title'=>'Iyengar Yoga','average_rating'=>5,'image'=>'');

		$response = array("status"=>400,"message"=>"no campaign found");

		$campaign = Campaign::where('slug','yoga-focus')->where('city_id',(int)$city_id)->select('_id','featured_finders_locationcluster','featured_services_locationcluster')->first();

		if($campaign){

			$response = array("status"=>400,"message"=>"campaign by wrong");

			$campaign = $campaign->toArray();

			$response = array("status"=>400,"message"=>"featured_finders_locationcluster not found");

			if(isset($campaign['featured_services_locationcluster'])){

				$services = $campaign['featured_services_locationcluster'];
				$clusters = array();
				$service_count = 0;

				foreach ($services as $cluster_slug => $service_id) {

					$location_cluster = Locationcluster::active()->where('slug',$cluster_slug)->first();

					$cluster['name'] = ucwords($location_cluster->name);
					$cluster['slug'] = $cluster_slug;

					$service_id = array_map('intval', $service_id);
					$service = array();

					foreach ($service_id as $_id) {

						$hesh = Service::with(array('location'=>function($query){$query->select('_id','name','slug');}))
						->with(array('city'=>function($query){$query->select('_id','name','slug');}))
						->with(array('category'=>function($query){$query->select('_id','name','slug');}))
						->with(array('subcategory'=>function($query){$query->select('_id','name','slug');}))
						->with(array('finder'=>function($query){$query->select('title','average_rating','slug','city_id','city','coverimage');}))
						->find($_id,array('finder_id','name','slug','city_id','servicecategory_id','servicesubcategory_id','location_id'));

						if($hesh){

							$hesh = $hesh->toArray();

							$service[] = $hesh;
						}
					}

					if(count($service) > 0){

						$service_count += count($service);

						foreach ($service as $key => $value) {

							unset($service[$key]['active_weekdays']);
							unset($service[$key]['workoutsession_active_weekdays']);
							unset($service[$key]['service_ratecards']);
							unset($service[$key]['service_trainer']);
							unset($service[$key]['serviceratecard']);
							unset($service[$key]['servicebatches']);
						}

					}

					$cluster['service'] = $service;
					$clusters[$cluster_slug] = $cluster;
					
				}

				$data['yoga_form'] = $yoga_form;
				$data['cluster'] = $clusters;
				$data['personal_trainer'] = $personal_trainer;
				$data['service_count'] = $service_count;

				$response = array("status"=>200,"data"=>$data);
			}
		}

		return Response::json($response,$response['status']);
    }
}