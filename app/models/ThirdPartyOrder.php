<?php
 class ThirdPartyOrder extends \Basemodel {
	protected $collection = "thirdpartyorders";
    protected $connection = "mongodb2";
    protected $appends = ["thirdparty"];

    public function getThirdPartyAttribute(){
        return ThirdParty::where('key', $this->client_key)->first(['key', 'acronym', 'name', 'slug', 'status']);
    }

}