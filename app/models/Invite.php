<?php

/** 
 * ModelName : Invite.
 * Maintains a list of functions used for Invite.
 *
 * @author Renuka Aggarwal <renu17a@gmail.com>
 */

class Invite extends  \Basemodel {

	protected $collection = "invites";

	public function setHostIdAttribute($value){
		$this->attributes['host_id'] = intval($value);
	}

	public function booktrial(){
		return $this->belongsTo('Booktrial','booktrial_id');
	}

}