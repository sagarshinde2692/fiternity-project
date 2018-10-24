<?php
/** 
 * ModelName : CrashLog.
 * Logs crash data from app.
 *
 */
class ApiCrashLog extends  \Basemodel {

	
	protected $collection = "apicrashlogs";
	
    public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
	public static $rules = [
			'app' => 'required',
		];

}