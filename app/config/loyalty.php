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
          'text' =>  "Hi <b>Laxansh</b>,<br/><br/>20/225 check-ins completed<br/><br/>You are on milestone 1",
        ],
        'milestones' => [
          'header' => 'Your Workout Journey',
          'subheader' => 'You are 10 check-ins away from milestone 2',
          'decription' => 'Start working out and level up on your streak.<br/> Achieve milestones and earn crazy rewards',
          'data' => 
          [
            [
                'title' => '',
                'count' => 0,
                'description' => 'Check-ins',
                'enabled'=>true
            ],
            [
                'title' => 'Milestone 1',
                'count' => 10,
                'description' => 'Check-ins',
                'enabled'=>false
            ],
            [
                'title' => 'Milestone 2',
                'count' => 30,
                'description' => 'Check-ins',
                'enabled'=>false
            ],
            [
                'title' => 'Milestone 3',
                'count' => 75,
                'description' => 'Check-ins',
                'enabled'=>false
            ],
          ],
          'footer' => 'Your workout counter will reset on 21 Sept 2018',
        ],
        'rewards' => [
          'header' => 'Claim exciting rewards',
          'data' => 
          [
            [
              'title' => 'Milestone 1',
              'description' => 'Select any reward',
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
        'past_check_in' => 'View all past check-ins',
        'Contact' => [
          'title' => 'Want further Assistance? Call us',
          'ph_no' => '999999999',
        ],
        'Terms' => [
          'Title' => 'FitSquad - Terms and conditions',
          'text' => 'HTML Text',
        ],
    ]
);
?>