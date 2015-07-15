<?PHP

/** 
 * ControllerName : FitmaniaController.
 * Maintains a list of functions used for FitmaniaController.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


use App\Mailers\CustomerMailer as CustomerMailer;
use App\Sms\CustomerSms as CustomerSms;


class FitmaniaController extends \BaseController {

	protected $customermailer;
	protected $customersms;
	
	public function __construct(CustomerMailer $customermailer, CustomerSms $customersms) {

		$this->customermailer		=	$customermailer;
		$this->customersms 			=	$customersms;
	}


	public function getDealOfDay($date = null){

		$date 					=  	($date == null) ? Carbon::now() : $date;
		$timestamp 				= 	strtotime($date);
		$dealsofdays 			=	[];

		// $dealsofdaycolleciton 	=	Fitmaniadod::active()->where('offer_date', '=', new DateTime($date) )->orderBy('ordering','desc')->get()->toArray();
		$dealsofdaycolleciton 	=	Fitmaniadod::active()
		->where('offer_date', '>=', new DateTime( date("d-m-Y", strtotime( $date )) ))
		->where('offer_date', '<=', new DateTime( date("d-m-Y", strtotime( $date )) ))
		->orderBy('ordering','desc')->get()->toArray();

		foreach ($dealsofdaycolleciton as $key => $value) {
			$dealdata = $this->transform($value);
			array_push($dealsofdays, $dealdata);
		}
		$responseData = [ 'dealsofday' => $dealsofdays ];
		return Response::json($responseData, 200);
	}



	private function transform($deal){

		$item  	   	=  	(!is_array($deal)) ? $deal->toArray() : $deal;
		$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');})) 
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->where('_id', (int) $item['finder_id'])->first();
		// return $finderarr;

		$data = [
		'_id' => $item['_id'],
		'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
		'duration' => (isset($item['duration']) && $item['duration'] != '') ? strtolower($item['duration']) : "",
		'price' => (isset($item['price']) && $item['price'] != '') ? strtolower($item['price']) : "",
		'special_price' => (isset($item['special_price']) && $item['special_price'] != '') ? strtolower($item['special_price']) : "",
		'finder_id' => (isset($item['finder_id']) && $item['finder_id'] != '') ? strtolower($item['finder_id']) : "",
		'offer_pic' => (isset($item['offer_pic']) && $item['offer_pic'] != '') ? strtolower($item['offer_pic']) : "",
		'description' => (isset($item['description']) && $item['description'] != '') ? $item['description'] : "",
		'ordering' => (isset($item['ordering']) && $item['ordering'] != '') ? (int)$item['ordering'] : "",
		'offer_link__to' => (isset($item['offer_link__to']) && $item['offer_link__to'] != '') ? (int)$item['offer_link__to'] : "",
		'offer_date' => (isset($item['offer_date']) && $item['offer_date'] != '') ? strtolower($item['offer_date']) : "",
		'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
		'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type')),
		];
		return $data;
	}


	public function fitmaniaServices(){

		// return $data = Input::json()->all();
		$from 				=	(Input::json()->get('from')) ? intval(Input::json()->get('from')) : 0;
		$size 				=	(Input::json()->get('size')) ? intval(Input::json()->get('size')) : 10;
		$category 			=	(Input::json()->get('category')) ? intval(Input::json()->get('category')) : '';		
		$location 			=	(Input::json()->get('location')) ? intval(Input::json()->get('location')) : '';	
		$city_id			= 	1;
		$fitmaniaServices 	=	[];

		$query	 			= 	Service::active()->orderBy('_id')->whereIn('show_on', array('2','3'));	

		if($category != ''){ 
			$query->where('servicecategory_id', $category); 
		}		
		
		if($location != ''){ 
			$query->where('location_id', $location); 
		}		
		
		$serviceColleciton 		= 	$query->take($size)->skip($from)->get();
		foreach ($serviceColleciton as $key => $value) {
			$servicedata = $this->transformFitmaniaService($value);
			array_push($fitmaniaServices, $servicedata);
		}
		// if(!$services){  $services =  'No Service Exist :)'; }

		$responseData = [
		'categories' => Servicecategory::active()->where('parent_id', 0)->orderBy('name')->get(array('name','_id','slug')),
		'locations' => Location::active()->whereIn('cities',array($city_id))->orderBy('name')->get(array('name','_id','slug')),
		'services' => $fitmaniaServices
		];
		// return $responseData;

		return Response::json($responseData, 200);
	}


	private function transformFitmaniaService($serivce){

		$item  	   	=  	(!is_array($serivce)) ? $serivce->toArray() : $serivce;
		$finderarr 	= 	Finder::with(array('city'=>function($query){$query->select('_id','name','slug');}))
		->with(array('location'=>function($query){$query->select('_id','name','slug');}))
		->with(array('category'=>function($query){$query->select('_id','name','slug');}))
		->where('_id', (int) $item['finder_id'])->first();
		// return $item; exit;

		$data = [
		'_id' => $item['_id'],
		'name' => (isset($item['name']) && $item['name'] != '') ? strtolower($item['name']) : "",
		'slug' => (isset($item['slug']) && $item['slug'] != '') ? strtolower($item['slug']) : "",
		'session_type' => (isset($item['session_type']) && $item['session_type'] != '') ? strtolower($item['session_type']) : "",
		'workout_intensity' => (isset($item['workout_intensity']) && $item['workout_intensity'] != '') ? strtolower($item['workout_intensity']) : "",
		'workout_tags' => (isset($item['workout_tags']) && $item['workout_tags'] != '') ? $item['workout_tags'] : [],
		'servicecategory_id' => (isset($item['servicecategory_id']) && $item['servicecategory_id'] != '') ? intval($item['servicecategory_id']) : "",
		'servicesubcategory_id' => (isset($item['servicesubcategory_id']) && $item['servicesubcategory_id'] != '') ? intval($item['servicesubcategory_id']) : "",
		'location_id' => (isset($item['location_id']) && $item['location_id'] != '') ? intval($item['location_id']) : "",
		'ratecards' => (isset($item['ratecards']) && $item['ratecards'] != '') ? $item['ratecards'] : [],
		'finder_id' => (isset($item['finder_id']) && $item['finder_id'] != '') ? strtolower($item['finder_id']) : "",
		'created_at' => (isset($item['created_at']) && $item['created_at'] != '') ? strtolower($item['created_at']) : "",
		'finder' =>  array_only($finderarr->toArray(), array('_id', 'title', 'slug', 'finder_type','commercial_type','coverimage','info','category')),
		];
		
		return $data;
	}


	public function buyService(){

		$data			=	Input::json()->all();		
		if(empty($data['order_id'])){
			$resp 	= 	array('status' => 404,'message' => "Data Missing Order Id - order_id");
			return Response::json($resp,404);			
		}

		$orderid 	=	(int) Input::json()->get('order_id');
		$order 		= 	Order::findOrFail($orderid);
		$orderData 	= 	$order->toArray();
		array_set($data, 'status', '1');
		$buydealofday 	=	$order->update($data);

		if($buydealofday){
			$sndsEmailCustomer		= 	$this->customermailer->buyServiceThroughFitmania($orderData);
			$sndsSmsCustomer		= 	$this->customersms->buyServiceThroughFitmania($orderData);
		}

		Log::info('Customer Purchase', array('purchase_details' => $order));
		
		$resp 	= 	array('status' => 200,'message' => "Successfully buy Serivce through Fitmania :)");
		return Response::json($resp,200);		
	}



}
