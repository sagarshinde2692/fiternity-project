<?php


/** 
 * ModelName : Service.
 * Maintains a list of functions used for Service.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */

class Service extends \Basemodel{

	protected $collection = "services";

	public static $rules = array(
		'name' => 'required',
		'servicecategory_id' => 'required',
		'servicesubcategory_id' => 'required'

		);

	protected $appends = array('active_weekdays', 'workoutsession_active_weekdays', 'service_coverimage');

	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}
	
	public function getActiveWeekdaysAttribute(){

		$activedays 		= 	array();
		if(!empty($this->trialschedules) && isset($this->trialschedules)){

			$trialschedules  	=	$this->trialschedules;

			foreach ($trialschedules as $key => $schedule) {
				if(!empty($schedule['slots'])){
					array_push($activedays, $schedule['weekday']);
				}
			}
			// $activedays 		= pluck( $this->trialschedules , array('weekday') );
		}

		return $activedays;
	}

	public function getWorkoutsessionActiveWeekdaysAttribute(){

		$activedays 	= 	array();
		if(!empty($this->workoutsessionschedules) && isset($this->workoutsessionschedules)){
			$schedules  	=	$this->workoutsessionschedules;

			foreach ($schedules as $key => $schedule) {
				if(!empty($schedule['slots'])){
					array_push($activedays, $schedule['weekday']);
				}
			}
			// $activedays 		= pluck( $this->schedules , array('weekday') );
		}
		return $activedays;
	}


	public function getServiceCoverimageAttribute(){

		$service_coverimage = '';

		if(!empty($this->coverimage) && isset($this->coverimage)){
			$service_coverimage = 's/ct/'.$service_coverimage;
		}else{
			$finder  	=	Finder::findOrFail(intval($this->finder_id));
			$service_coverimage = (trim($finder->coverimage) != '') ? 'f/ct/'.trim($finder->coverimage) : 'default/'.$finder->category_id.'-'.rand(1, 4).'.jpg';
		}
		return $service_coverimage ;
	}

	public function category(){
		return $this->belongsTo('Servicecategory','servicecategory_id');
	}		

	public function subcategory(){
		return $this->belongsTo('Servicecategory','servicesubcategory_id');
	}

	public function finder(){
		return $this->belongsTo('Finder');
	}

	public function location(){
		return $this->belongsTo('Location');
	}

	public function city(){
		return $this->belongsTo('City');
	}

	public function trainer(){
		return $this->belongsTo('Servicetrainer','trainer_id');
	}	

}