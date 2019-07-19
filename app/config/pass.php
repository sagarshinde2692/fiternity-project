<?php
$apiUrl = \Config::get('app.url');
$success_page_template = Config::get('successPage');
$silver_logo = 'https://b.fitn.in/passes/monthly_card.png';
$gold_logo = 'https://b.fitn.in/passes/all_access_card.png';
return [
    'list' => [
        'passes' => [
            [
                'header' => 'ALL ACCESS PASS',
                'subheader' => 'ALL ACCESS PASS UNLIMITED USAGE',
                'text' => '1 MONTH | 3 MONTHS | 6 MONTHS',
                'image' => $gold_logo,
                'why_pass' => [
                    'header' => 'WHY GO FOR A PASS',
                    'text' => 'adfaf fs fsadf dadfdsf sf safasdf asf asdfsdf sfs dsdf',
                    'data' => [
                        'afsdf',
                        'adfsdfsf sd',
                        'sdfs fsdfsd f'
                    ]
                ],
                'offerings' => [
                    'header' => 'ALL ACCESS PASS',
                    'text' => '(Limited Access)',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => 'Limited access card gives you an access to all fitness centres around you.',
                    'text' => 'view all terms and conditions',
                    "title" =>'Terms and Conditions',
                    'url' => $apiUrl.'/passtermscondition?type=unlimited'
                ]
            ],
            [
                'header' => 'MONTHLY PASS',
                'subheader' => 'MONTHLY PASS FOR LIMITED USAGE',
                'text' => '1 MONTH',
                'image' => 'https://b.fitn.in/passes/monthly_card.png',
                'why_pass' => [
                    'header' => 'WHY GO FOR A PASS',
                    'text' => 'adfaf fs fsadf dadfdsf sf safasdf asf asdfsdf sfs dsdf',
                    'data' => [
                        'afsdf',
                        'adfsdfsf sd',
                        'sdfs fsdfsd f'
                    ]
                ],
                'offerings' => [
                    'header' => 'Select a monthly pass',
                    'text' => '(Limited Access)',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => 'Limited access card gives you an access to all fitness centres around you.',
                    'text' => 'view all terms and conditions',
                    "title" =>'Terms and Conditions',
                    'url' => $apiUrl.'/passtermscondition?type=subscripe'

                ]
            ]
        ],
        'faq' => [
            'header' => 'FAQs',
            'text' => 'sdfdfdsf sdfsd fsdf sdfs sf sf sdfs d',
            'title' => 'FAQ Title',
            'url' => $apiUrl.'/passfaq'
        ]
    ],
    "terms"=>[
        "<h2>terms and condition header</h2>
        <ul>
            <li> terms1 vkvdfk</li>
            <li> terms t2</li>
            <li> tersm 4</li>
            <li>terms 4</li>
            <li> terms 5</li>
        </ul>"
    ],
    'question_list' => [
        [
            'question' => 'asdhjkdfhb adfjkvsfb?',
            'answer' => 'asfbdgf  sfduhsflkv sdfhbsfbh pdfshipubshf pbhfsdbhsb sfbgfb'
        ],
        [
            'question' => 'asdhjkdfhb adfjkvsfb?',
            'answer' => 'asfbdgf  sfduhsflkv sdfhbsfbh pdfshipubshf pbhfsdbhsb sfbgfb'
        ],
        [
            'question' => 'asdhjkdfhb adfjkvsfb?',
            'answer' => 'asfbdgf  sfduhsflkv sdfhbsfbh pdfshipubshf pbhfsdbhsb sfbgfb'
        ]
    ],
    'success'=>[
        'image' => 'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png',
        "header" => "Your subscription is active",
        'subline'=>'Hi __customer_name, your __pass_name for __pass_duration is now active. We have also sent you a confirmation Email and SMS.',
        "pass" => [
            "header" => "__credit_point sweat point credits",
            "subheader" => "__pass_count Classes",
            "type" => "Monthly",
            "text" => "Valid up to __end_date"
        ],
        'info'=>[
            'header'=>'Things to keep in mind',
            'data'=>[
                'You get sweatpoint credits to book whatever classes you want',
                'Download the app & get started',
                'Book classes at any gym/studio near you, sweatpoints vary by class',
                'Not loving it? easy cancellation available',
            ]
        ],
        "concultion" => $success_page_template['conclusion'],
        "feedback" => $success_page_template["feedback"]
    ],
    'pass_image_silver' => $silver_logo,
    'pass_image_gold' => $gold_logo,
    'web_message'=>'Please note - The sessions are bookable only of Fitternity app. Download now',

    'trial_pass' => [
        "logo" => $silver_logo,
        "header" => "EXPERIENCE FITNESS LIKE NEVER BEFORE!",
        "subheader" => "Book sessions and only pay for days you workout",
        'pass' => [
            'header' => 'pass_name',
            'subheader' => 'duration_text PASS FOR UNLIMITED USAGE',
            'text' => 'duration_text',
            'image' => $silver_logo,
            'name' => 'FLEXI PASS',
            'type' => 'pass_type',
            'price' => 'pass_price'
        ],
        
        'footer' => [
            "text" => "lorum lorum lorum lorum lorum lorum lorum lorum lorum lorum",
            "button_text" => "Explore"
        ]
    ],

    'subscription_pass' => [
        "logo" => $gold_logo,
        "header" => "EXPERIENCE FITNESS LIKE NEVER BEFORE!",
        "subheader" => "Book sessions and only pay for days you workout",
        'pass' => [
            'header' => 'pass_name',
            'subheader' => 'ALL ACCESS PASS UNLIMITED USAGE', // do have to mention pass duration time 
            'text' => 'duration_text', //need to confirm what have to show before purchase and after pass purchased
            'image' => $gold_logo,
            'name' => 'FLEXI PASS',
            'type' => 'pass_type',
            'price' => 'pass_price'
        ],
        
        'footer' => [
            "text" => "lorum lorum lorum lorum lorum lorum lorum lorum lorum lorum",
            "button_text" => "Explore"
        ]
    ],
    
    "flexipass_small" => [
        "text" => "lorum lorum lorum lorum lorum lorum lorum lorum lorum lorum",
        "button_text" => "Explore"
    ]
];