<?php
/** 
 * ModelName : CrashLog.
 * Logs crash data from app.
 *
 */
class CrashLog extends  \Basemodel {

	
	protected $collection = "crashlogs";
	
    public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
	
	public static $rules = [
			'app' => 'required',
		];

}