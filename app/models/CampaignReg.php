<?php
/** 
 * ModelName : CampaignReg
 *
 */
class CampaignReg extends  \Basemodel {
	
	protected $collection = "campaignregs";
    
    public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
}