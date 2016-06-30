<?php

class Reward extends \Basemodel {

    protected $collection = "rewards";

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

    public function rewardoffers(){

        return $this->belongsToMany('Rewardoffer', null, 'rewards', 'rewrardoffers');
    }

}