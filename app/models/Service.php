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

	protected $appends = array('active_weekdays', 'workoutsession_active_weekdays');

	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}
	
	public function getActiveWeekdaysAttribute(){

		$activedays 		= 	array();
		$trialschedules  	=	$this->trialschedules;

		foreach ($trialschedules as $key => $schedule) {
			if(!empty($schedule['slots'])){
				array_push($activedays, $schedule['weekday']);
			}
		}
		// $activedays 		= pluck( $this->trialschedules , array('weekday') );
		return $activedays;
	}

	public function getWorkoutsessionActiveWeekdaysAttribute(){

		$activedays 	= 	array();
		$schedules  	=	$this->workoutsessionschedules;

		foreach ($schedules as $key => $schedule) {
			if(!empty($schedule['slots'])){
				array_push($activedays, $schedule['weekday']);
			}
		}
		// $activedays 		= pluck( $this->schedules , array('weekday') );
		return $activedays;
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

	public function trainer(){
		return $this->belongsTo('Servicetrainer','trainer_id');
	}	

}