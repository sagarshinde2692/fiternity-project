<?php

return array(
    'checkin_limit'=>225,
    'milestones'=>[
        [
            'milestone' => 0,
            'count' => 0,
            'enabled'=>true,
            'next_count'=>10,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>0
            
        ],
        [
            'milestone' => 1,
            'count' => 10,
            'enabled'=>false,
            'next_count'=>30,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>500,

        ],
        [
            'milestone' => 2,
            'count' => 30,
            'enabled'=>false,
            'next_count'=>75,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>3000

        ],
        [
            'milestone' => 3,
            'count' => 75,
            'enabled'=>false,
            'next_count'=>150,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>4000

        ],
        [
            'milestone' => 4,
            'count' => 150,
            'enabled'=>false,
            'next_count'=>225,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>7000

        ],
        [
            'milestone' => 5,
            'count' => 225,
            'enabled'=>false,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>12000
        ],
    ],
    'register_url'=>Config::get('app.website', 'https://www.fitternity.com').'/fitsquad',
    'fitsquad_logo'=>'https://b.fitn.in/loyalty/web%20FitSquad%20logo%20orange.png',
    'fitsquad_faq'=>Config::get('app.website', 'https://www.fitternity.com').'/fitsquad-faq',
    
    
    
);
?>