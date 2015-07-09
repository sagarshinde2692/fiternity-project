<?php

/** 
 * ModelName : Blog.
 * Maintains a list of functions used for Blog.
 *
 * @author Sanjay Sahu <sanjay.id7@gmail.com>
 */


class Blog extends \Basemodel {

	protected $collection = "blogs";

	public static $rules = array(
		'title' => 'required'
		);


	public function setIdAttribute($value){
		
		$this->attributes['_id'] = intval($value);
	}

	public function category(){
		return $this->belongsTo('Blogcategory','category_id');
	}

	public function categorytags(){
		return $this->belongsToMany('Blogcategorytag', null, 'blogs', 'categorytags');
	}

	public function author(){
		return $this->belongsTo('User','author_id');
	}

	public function expert(){
		return $this->belongsTo('User','expert_id');
	}

	public function comments(){
		return $this->hasMany('Comment','blog_id');
	}

}