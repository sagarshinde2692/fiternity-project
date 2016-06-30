<?php

class Rewardoffer extends \Basemodel {

    protected $collection = "rewrardoffers";

    public static $rules = array(
        'duration' => 'required',
        'duration_type' => 'required',
        'booktrial_type' => 'required',  //On buying memberships,booktrials,vip_booktrials etc
        'findercategory_id' => 'required',
        'status' => 'in:0,1',
        'rewards' => 'array'
    );

    public function rewards(){
        return $this->belongsToMany('Reward', null, 'rewrardoffers', 'rewards');
    }

    public function findercategory(){

        return $this->belongsTo('Findercategory','findercategory_id');
    }

}