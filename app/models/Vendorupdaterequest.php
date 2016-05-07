<?php

class Vendorupdaterequest extends \Basemodel {


	protected $collection = "vendorupdaterequests";


	public function setIdAttribute($value){
		
		$this->attributes['_id'] = $value;
	}
}