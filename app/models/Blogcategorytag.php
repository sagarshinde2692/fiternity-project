
<?php

class Blogcategorytag extends \Basemodel {

	protected $collection = "blogcategorytags";

	public static $rules = array(
		'name' => 'required'
		);

	public function setIdAttribute($value){
		$this->attributes['_id'] = intval($value);
	}

	public function blogs(){

		return $this->belongsToMany('Blog', null, 'blogs', 'categorytags');
	}


}