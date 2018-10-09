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
            'amount'=>600

        ],
        [
            'milestone' => 2,
            'count' => 30,
            'enabled'=>false,
            'next_count'=>75,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>1200

        ],
        [
            'milestone' => 3,
            'count' => 75,
            'enabled'=>false,
            'next_count'=>150,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>2000

        ],
        [
            'milestone' => 4,
            'count' => 150,
            'enabled'=>false,
            'next_count'=>225,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>3000

        ],
        [
            'milestone' => 5,
            'count' => 225,
            'enabled'=>false,
            'progress'=>0,
            'description' => 'Check-ins',
            'amount'=>4000
        ],
    ],
    'register_url'=>Config::get('app.website', 'https://www.fitternity.com').'/loyalty-registration'
);
?>