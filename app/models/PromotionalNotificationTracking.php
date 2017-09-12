<?php

/** 
 * ModelName : PromotionalNotificationTracking.
 * Maintains a list of functions used for PromotionalNotificationTracking.
 *
 * @author Mahesh Jadhav <maheshjadhav@fitternity.com>
 */

class PromotionalNotificationTracking extends  \Basemodel {
	
	protected $collection = "promotionalnotificationtrackings";

	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}

}
