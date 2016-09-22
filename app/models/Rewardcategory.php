<?php

class Rewardcategory extends \Basemodel {

    protected $collection = "rewardcategories";

    public static $rules = array(
        'title' => 'required|string|unique:title',
        'image' => 'string',
        'description' => 'string',
        'validity_in_days' => 'required|numeric',
        'terms' => 'required|string',
        'status' => 'in:0,1',
        'reward_type' => 'in:fitternity_voucher,fitness_kit,sessions',  //internal_voucher, external_voucher, fit_kit, sessions...
//        'action' => 'required'  //To specify avail reward action....
    );

    public function rewrards(){

        return $this->belongsToMany('Reward', null, 'rewardcategories', 'rewrards');
    }

}