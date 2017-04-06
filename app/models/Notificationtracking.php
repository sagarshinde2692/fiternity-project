<?php

/** 
 * ModelName : NotificationTracking.
 * Maintains a list of functions used for NotificationTracking.
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

class NotificationTracking extends  \Basemodel {
	
	protected $collection = "notificationtrackings";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

}
