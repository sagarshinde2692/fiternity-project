<?php

return array(



    'pre_register' => [
        'header' => [
            'image' => 'https://b.fitn.in/loyalty/Header%20Final11.png',
            'ratio' => 1.23,
            'url'=> Config::get('loyalty_constants.register_url')
        ],

        'partners' => [
            'header' => 'Proud Partners',
            'data' => [
                'https://b.fitn.in/external-vouchers/BMS.png',
                'https://b.fitn.in/external-vouchers/OLA.png',
                'https://b.fitn.in/external-vouchers/ZOMATO.png',
            ]
        ],

        'steps' => [
            'header' => 'NO MATTER WHERE YOU WORKOUT | GET REWARDED IN 3 EASY STEPS',
            'data' => [
                [
                    'title' => 'CHECK-IN FOR YOUR WORKOUT',
                    'description' => 'Check-in at the gym/studio by scanning the QR code through the app',
                    'image' => 'https://b.fitn.in/loyalty/Group%20147%281%29.png'
                ],
                [
                    'title' => 'WORKOUT MORE AND LEVEL UP',
                    'description' => 'Level up on your streak to reach different milestones',
                    'image' => 'https://b.fitn.in/loyalty/Group%20128.png'
                ],
                [
                    'title' => 'EARN REWARDS',
                    'description' => 'Earn exciting rewards on every milestone you achieve ',
                    'image' => 'https://b.fitn.in/loyalty/Group%2049.png'
                ],
            ]
        ],


        'check_ins' => [
            'header' => 'GET CRAZY REWARDS ON COMPLETING EACH MILESTONE',
            'data' => []
        ],

        'footer' => [
            'image' => 'https://b.fitn.in/loyalty/Footer11.png',
            'ratio' => 1.38,
            'url'=> Config::get('app.website', 'https://www.fitternity.com').'/fitsquad?app=true'
        ],
    ],
    'post_register' => [
        'header' => [
            'logo' => 'https://b.fitn.in/loyalty/logo%20mobile%20new.png',
            'text' => 'Hi <b>$customer_name</b>,<br/><br/>$check_ins/' . Config::get('loyalty_constants.checkin_limit') . ' check-ins completed<br/><br/>You are on milestone $milestone',
            'background_image' => 'https://b.fitn.in/loyalty/banner.jpg',
            'ratio' => 0.36,
        ],
        'milestones' => [
            'header' => 'Your Workout Journey',
            'subheader' => 'You are $next_milestone_check_ins check-ins away from milestone $next_milestone',
            'description' => "Start working out and level up on your streak.\n Achieve milestones and earn crazy rewards",
            'data' => Config::get('loyalty_constants.milestones'),
            'footer' => 'Your workout counter will reset on 21 Sept 2018',
        ],
        'rewards' => [
            'header' => 'Claim exciting rewards',
            'open_index' => 0,
            'claim_message' => "Are you sure you want to claim this reward as you won't be able to claim other rewards for this milestone?",

            'data' => [],
        ],
        'past_check_in' => [
            'header' => 'View all past check-ins',
            'subheader' => 'You haven\'t checked in yet.',
            'clickable' => false
        ],
        'Contact' => [
            'title' => 'Want further Assistance? Call us',
            'ph_no' => Config::get('app.contact_us_customer_number'),
        ],
        'Terms' => [
            'Title' => 'FitSquad - Terms and conditions',
            'text' => 'HTML Text',
        ],
    ],
    'past_check_in_header_text' => 'View Past Check Ins',

    'pre_register_check_ins_data_template' => [
        'title' => 'Milestone milestone',
        'milestone' => 'No of sessions - count',
        'count'=> 'count',
        'amount' => '₹ amount',
        'images' => []
    ],

    'post_register_rewards_data_outer_template' => [
        'title' => 'Milestone milestone',
        'description' => 'Select any reward',
        '_id' => 1,
        'data' => []
    ],

    'post_register_rewards_data_inner_template' => [
        'logo' => 'image',
        '_id' => '_id',
        'price' => 'amount',
        'price_header' => 'Worth',
        'claim_enabled' => false,
        'button_title' => 'Claim',
        'terms' => 'HTMl text',
        'coupon_description' => 'description',
        'block_message'=>'',

    ],
    'success_loyalty_header' => [
        'logo' => 'https://b.fitn.in/loyalty/logo%20mobile%20new.png',
        'header1' => 'You have successfully registered to FITSQUAD',
        'header2' => 'India\'s largest fitness rewards club',
    ],
    
    'milestones' => [
        'header' => 'Your Workout Journey',
        'subheader' => 'You are $next_milestone_check_ins check-ins away from milestone $next_milestone',
        'description' => '$check_ins/' . Config::get('loyalty_constants.checkin_limit') . ' check-ins done',
        'data' => Config::get('loyalty_constants.milestones')
    ],
    'receipt_message'=>'Please upload your membership receipt to claim you reward'
);
?>