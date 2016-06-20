<?php

class Customoffer extends \Basemodel {

    protected $collection = "customoffers";

    // Add your validation rules here
    public static $rules = [
        'title'=> 'required',
        'description'=> 'required',
        'quantity_type'=> 'required',
        'quantity'=> 'required|integer',
        'price'=> 'required|integer',
        'validity'=> 'required|integer',
        'status'=> 'required'
    ];

    public function campaigns(){
        return $this->belongsToMany('Campaign', null, 'customoffers', 'campaigns');
    }
    // Don't forget to fill this array
//	protected $fillable = [];

}