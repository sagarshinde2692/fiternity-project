<?php

return array(



    'pre_register'=>[
        'header' => [
            'image'=>'https://b.fitn.in/loyalty/Header%20Final11.png',
            'ratio'=> 1.23
        ],

        'partners' => [
            'header'=>'Proud Partners',
            'data'=>[
                'https://b.fitn.in/paypersession/level-1.png',
                'https://b.fitn.in/paypersession/level-1.png',
                'https://b.fitn.in/paypersession/level-1.png'
            ]
            
            ],

        'steps' => [
            'header'=>'NO MATTER WHERE YOU WORKOUT | GET REWARDED IN 3 EASY STEPS',
            'data'=>[
                [
                    'title'=>'CHECK-IN FOR YOUR WORKOUT',
                    'description'=>'Check-in at the gym/studio by scanning the QR code through the app',
                    'image'=>'https://b.fitn.in/loyalty/Group%20147%281%29.png'
                ],
                [
                    'title'=>'WORKOUT MORE AND LEVEL UP',
                    'description'=>'Level up on your streak to reach different milestones',
                    'image'=>'https://b.fitn.in/loyalty/Group%20128.png'
                ],
                [
                    'title'=>'EARN REWARDS',
                    'description'=>'Earn exciting rewards on every milestone you achieve ',
                    'image'=>'https://b.fitn.in/loyalty/Group%2049.png'
                ],
            ]
            ],


        'check_ins' => [
            'header'=>'GET CRAZY REWARDS ON COMPLETING EACH MILESTONE',
            'data'=>[
                [	
                    'title'=>'Milestone 1',
                    'milestone'=>'No of sessions - 10',
                    'amount'=> '₹ 300',
                    'images'=>[
                        'https://b.fitn.in/paypersession/level-1.png',
                        'https://b.fitn.in/paypersession/level-1.png',
                        'https://b.fitn.in/paypersession/level-1.png'
                    ]
                ],
                [	
                    'title'=>'Milestone 1',
                    'milestone'=>'No of sessions - 10',
                    'amount'=> '₹ 300',
                    'images'=>[
                        'https://b.fitn.in/paypersession/level-1.png',
                        'https://b.fitn.in/paypersession/level-1.png',
                        'https://b.fitn.in/paypersession/level-1.png'
                    ]
                ],
                [	
                    'title'=>'Milestone 1',
                    'milestone'=>'No of sessions - 10',
                    'amount'=> '₹ 300',
                    'images'=>[
                        'https://b.fitn.in/paypersession/level-1.png',
                        'https://b.fitn.in/paypersession/level-1.png',
                        'https://b.fitn.in/paypersession/level-1.png'
                    ]
                ],
                [	
                    'title'=>'Milestone 1',
                    'milestone'=>'No of sessions - 10',
                    'amount'=> '₹ 300',
                    'images'=>[
                        'https://b.fitn.in/paypersession/level-1.png',
                        'https://b.fitn.in/paypersession/level-1.png',
                        'https://b.fitn.in/paypersession/level-1.png'
                    ]
                ],
                
            ]
        ],

        'footer' => [
            'image'=>'https://b.fitn.in/loyalty/Footer11.png',
            'ratio'=> 1.38
        ],
    ],
    'post_register'=>[
        'header' => [
          'logo' => 'https://b.fitn.in/loyalty/LOGO1.png',
        'text' =>  'Hi <b>$customer_name</b>,<br/><br/>$check_ins/'.Config::get('loyalty_constants.checkin_limit').' check-ins completed<br/><br/>You are on milestone $milestone',
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
          'data' => 
          [
            [
              'title' => 'Milestone 1',
              'description' => 'Select any reward',
              '_id'=>1,
              'data' => 
              [
                [
                  'logo' => 'https://b.fitn.in/paypersession/level-1.png',
                  'price' => '₹300',
                  'price_header' => 'Worth',
                  'claim_enabled' => true,
                  'button_title' => 'Claim',
                  'Terms' => 'HTMl text',
                ],
                
                [
                  'logo' => 'https://b.fitn.in/paypersession/level-1.png',
                  'price' => '₹300',
                  'price_header' => 'Worth',
                  'claim_enabled' => true,
                  'button_title' => 'Claim',
                  'Terms' => 'HTMl text',
                ],
                
                [
                  'logo' => 'https://b.fitn.in/paypersession/level-1.png',
                  'price' => '₹300',
                  'price_header' => 'Worth',
                  'claim_enabled' => true,
                  'button_title' => 'Claim',
                  'Terms' => 'HTMl text',
                ],
              ],
            ],
            [
                'title' => 'Milestone 1',
                'description' => 'Select any reward',
                '_id'=>2,
                'data' => 
                [
                  [
                    'logo' => 'https://b.fitn.in/paypersession/level-1.png',
                    'price' => '$300',
                    'price_header' => 'Worth',
                    'claim_enabled' => true,
                    'button_title' => 'Claim',
                    'Terms' => 'HTMl text',
                  ],
                  
                  [
                    'logo' => 'https://b.fitn.in/paypersession/level-1.png',
                    'price' => '$300',
                    'price_header' => 'Worth',
                    'claim_enabled' => true,
                    'button_title' => 'Claim',
                    'Terms' => 'HTMl text',
                  ],
                  
                  [
                    'logo' => 'https://b.fitn.in/paypersession/level-1.png',
                    'price' => '$300',
                    'price_header' => 'Worth',
                    'claim_enabled' => true,
                    'button_title' => 'Claim',
                    'Terms' => 'HTMl text',
                  ],
                ],
              ],
          ],
        ],
        'past_check_in' => [
            'header'=>'View all past check-ins',
            'subheader'=>'You haven\'t checked in yet.'
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
    'past_check_in_header_text'=>'View Past Check Ins'
);
?>