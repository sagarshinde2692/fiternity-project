<?php
$apiUrl = \Config::get('app.url');
$success_page_template = Config::get('successPage');
$silver_logo = 'https://b.fitn.in/passes/monthly_card.png';
$gold_logo = 'https://b.fitn.in/passes/all_access_card.png';
return [
    'list' => [
        'passes' => [
            [
                'header' => 'UNLIMITED USAGE',
                'subheader' => 'ALL ACCESS PASS',
                'pass_type' => 'red',
                'text' => '',
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
                    'header' => 'UNLIMITED USAGE PASS',
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
                'header' => 'UNLIMITED VALIDITY',
                'subheader' => 'ALL ACCESS PASS',
                'pass_type' => 'black',
                'text' => '',
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
                    'header' => 'UNLIMITED VALIDITY PASS',
                    'text' => '(Unlimited Access)',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => 'Limitless validity',
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
        ],
        'subheader' => 'duration_text PASS FOR usage_text',
    ],
    "total_available" => 300,
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
        "header" => "Your ___type pass is active",
        'subline'=>'Hi __customer_name, your __pass_name for __pass_duration is now confirmed. We have also sent you a confirmation Email and SMS.<br/>Booking starts from 1st of September 2019.',
        "pass" => [
            // "text" => "(__usage_remark) __end_date",
            "text" => "__end_date",
            'header' => 'pass_name',
            'subheader' => 'duration_text PASS FOR usage_text',
            'image' => $silver_logo,
            'name' => 'FLEXI PASS',
            'type' => 'pass_type',
            'price' => 'pass_price'
        ],
        'info'=>[
            'header'=>'Things to keep in mind',
            'data'=>[
                'Download the app & get started.',
                'Book classes at any gym/studio near you of your choice.',
                'Not loving it? easy cancellation available.',
            ]
        ],
        "conclusion" => $success_page_template['conclusion'],
        "feedback" => $success_page_template["feedback"]
    ],
    'pass_image_silver' => $silver_logo,
    'pass_image_gold' => $gold_logo,
    'web_message'=>'Please note - The sessions are bookable only on Fitternity app. Download now.',

    'trial_pass' => [
        "logo" => $silver_logo,
        "header" => "Experience the all-new freedom to workout",
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
            "header" => "15 DAY TRIAL PACK",
            "subheader" => "100% CASHBACK",
            "button_text" => "Explore"
        ]
    ],

    'subscription_pass' => [
        "logo" => $gold_logo,
        "header" => "Experience the all-new freedom to workout",
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
            "header" => "15 DAY TRIAL PACK",
            "subheader" => "No CASHBACK",
            "button_text" => "Explore"
        ]
    ],
    
    "flexipass_small" => [
        "header" => "Introducing Fitternity - OnePass",
        "subheader" => "Experience the All New Freedom to workout in any Gym or Studio across India",
        "button_text" => "Explore"
    ],

    "boughtflexipass" => [
        "header" => "OnePass Activated",
        "passtype" => "Upgrade",
        "isUpgrade" => true,
        "button_text" => "Book",
        "sessions" => [
            "text1" => "∞",
            "text2" => "Sessions \n left"
        ],
        "swimming" => [
            "text1" => "__left_swimming_session",
            "text2" => "Swimming Sessions"   
        ],
        "booking" => [
            "text1" => "",
            "text2" => "Bookings \n done"
        ],
        "validity" => [
            "text1" => "__duration",
            "text2" => "Trial ends \n in "
        ],
        "footer"  => "Lorum lorum lorum lorum lorum"
    ]
];