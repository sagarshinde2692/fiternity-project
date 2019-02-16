<?php

class Checkin extends  \Basemodel {

    protected $connection = 'fitcheckins';
    
    protected $collection = "checkins";
    protected $appends = ['finder'];
    public $incrementing = false;
    protected $dates = ['date'];
    
    public function getFinderAttribute(){
      Finder::$withoutAppends = true;
		  return Finder::where('_id', intval($this->finder_id))->first(['title']);
    }
    
    public function newQuery($excludeDeleted = true){
        
        $query = parent::newQuery($excludeDeleted);

        $query->where('status', '!=', '0');

        return $query;
    
    }
}