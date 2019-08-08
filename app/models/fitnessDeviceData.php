<?php

/** 
 * ModelName : FitnessDeviceData.
 * Maintains a list of functions used for FitnessDeviceData.
 *
 */

class FitnessDeviceData extends  \Basemodel {
	
	protected $collection = "fitnessdevicedata";

    public function setIdAttribute($value){	
		$this->attributes['_id'] = $value;
	}

	public function newQuery($excludeDeleted = true){
        
        $query = parent::newQuery($excludeDeleted);

        $query->where('status', '!=', '0');

        return $query;
    
    }

}
