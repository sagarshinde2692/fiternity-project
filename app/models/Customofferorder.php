<?php

class Customofferorder extends \Basemodel {

    protected $collection = "customofferorders";

    // Add your validation rules here
    public static $rules = [
    ];

    public function customoffer(){
        return $this->belongsTo('Customoffer');
    }

    // Don't forget to fill this array
//	protected $fillable = [];

}