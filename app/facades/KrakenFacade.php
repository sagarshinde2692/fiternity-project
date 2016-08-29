<?php 
namespace App\Facades;
 
use Illuminate\Support\Facades\Facade;
 
class KrakenFacade extends Facade {
 
    protected static function getFacadeAccessor(){
        return new \App\Services\Kraken;
    }
 
}