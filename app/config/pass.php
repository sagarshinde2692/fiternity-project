<?php
$apiUrl = \Config::get('app.url');
$success_page_template = Config::get('successPage');
$red_pass = 'https://b.fitn.in/global/classpass/mobile/red%20card-website.png';
$black_pass = 'https://b.fitn.in/global/classpass/mobile/back%20card%20-%20website.png';
return [
    'list' => [
        'passes' => [
            [
                'header' => 'UNLIMITED USAGE',
                'subheader' => 'ALL ACCESS PASS',
                'pass_type' => 'red',
                'text' => '',
                'image' => $red_pass,
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
                'image' => $black_pass,
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
        'app_passes' => [
            [
                'header' => 'RED PASS',
                'card_header' => 'UNLIMITED USAGE',
                'subheader' => 'ALL ACCESS PASS',
                'pass_type' => 'red',
                'text' => '',
                'image' => $red_pass,
                'why_pass' => [
                    'header' => 'WHY GO FOR A PASS',
                    'text' => 'LIMITLESS WORKOUTS, LIMITLESS CHOICES, LIMITLESS VALIDITY, LIMITLESS YOU',
                    'data' => [
                        'ONEPass Fits In Your (Busy) Life',
                        'ONEPass Gives You Membership Privileges'
                    ]
                ],
                'offerings' => [
                    'text' => (json_decode('"'."\u2713".'"')." Limitless workouts across 10,000+ fitness classes, gyms and sports facilities across India.\n".json_decode('"'."\u2713".'"')." Use it like a fitness membership - choose a duration of 15 days to 1 year."),
                    'button_text' => 'Checkout Studios',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => 'Limited access card gives you an access to all fitness centres around you.', // need content
                    'text' => 'view all terms and conditions',
                    "title" =>'Terms and Conditions',
                    'url' => $apiUrl.'/passtermscondition?type=unlimited'
                ]
            ],
            [
                'header' => 'BLACK PASS',
                'card_header' => 'UNLIMITED VALIDITY',
                'subheader' => 'ALL ACCESS PASS',
                'pass_type' => 'black',
                'text' => '',
                'image' => $black_pass,
                'why_pass' => [
                    'header' => 'WHY GO FOR A PASS',
                    'text' => 'LIMITLESS WORKOUTS, LIMITLESS CHOICES, LIMITLESS VALIDITY, LIMITLESS YOU',
                    'data' => [
                        'ONEPass Fits In Your (Busy) Life',
                        'ONEPass Gives You Membership Privileges'
                    ]
                ],
                'offerings' => [
                    'text' => (json_decode('"'."\u2713".'"')." Get Limitless validity - Your membership will never expire!.\n".json_decode('"'."\u2713".'"')." Replace your membership by choosing a pack - ranging from 30 to 180 sessions with lifetime validity."),
                    'button_text' => 'Checkout Studios',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => 'Limitless validity', // need content
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
        "<h2>Terms and Conditions</h2>
        <ul>
            <li>OnePass bookings will start from 1st September 2019</li>
            <li>Incase you find a fitness/sports facility which is not yet part of the OnePass network - We will work to on-board the center within 1-15 working days of your request given alignment of standard terms between Fitternity & the facili</li>
            <li>Incase you're not enjoying OnePass - You can take a refund (No Questions asked). You pro-rata based un-utilized amount will be converted to Fitcash on Fitternity(1 Rupee = 1 Fitcash) and can be used to buy any other service/membership on Fitternity. 5% of your initial payment for OnePass upto a maximum of Rs 500 will be deducted to process this transition from OnePass to Fitcash</li>
            <li>The cashback received via any OnePass transaction can be only be redeemed to upgrade your OnePass</li>
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
        'subline'=>'Hi __customer_name, your __pass_name for __pass_duration is now confirmed. We have also sent you a confirmation Email and SMS.',
        'subline_1'=>'Booking starts from 1st of September 2019.',
        "pass" => [
            // "text" => "(__usage_remark) __end_date",
            "text" => "__end_date",
            'header' => 'pass_name',
            'subheader' => 'duration_text PASS FOR usage_text',
            'image' => $red_pass,
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
    'pass_image_silver' => $red_pass,
    'pass_image_gold' => $black_pass,
    'web_message'=>'Please note - The sessions are bookable only on Fitternity app. Download now.',

    'trial_pass' => [
        "logo" => $red_pass,
        "header" => "Experience the all-new freedom to workout",
        "subheader" => "Book sessions and only pay for days you workout",
        'pass' => [
            'header' => 'pass_name',
            'subheader' => 'duration_text PASS FOR UNLIMITED USAGE',
            'text' => 'duration_text',
            'image' => $red_pass,
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
        "logo" => $black_pass,
        "header" => "Experience the all-new freedom to workout",
        "subheader" => "Book sessions and only pay for days you workout",
        'pass' => [
            'header' => 'pass_name',
            'subheader' => 'ALL ACCESS PASS UNLIMITED USAGE', // do have to mention pass duration time 
            'text' => 'duration_text', //need to confirm what have to show before purchase and after pass purchased
            'image' => $black_pass,
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
    ],

    "home" => [
        "before_purchase" => [
            "logo"  => "https://b.fitn.in/passes/app-home/onepass-icon-new.png",
            "header_img"  => "https://b.fitn.in/passes/app-home/onepass_header.png",
            "text"  => "Enjoy limitless access across Fitternity's 12,000+ fitness classes, gyms and sports facilities across India",
            "passes" => [
                [
                    "image" => "https://b.fitn.in/passes/app-home/op_black_thumb.png",
                    "header1" => "ONEPASS",
                    "header1_color" => "#000000",
                    "header2" => "RED",
                    "header2_color" => "#d50000",
                    "subheader" => "UNLIMITED ACCESS",
                    "desc_header" => "Limitless Workouts",
                    "desc_subheader" => "With Expiry" 
                ],
                [
                    "image" => "https://b.fitn.in/passes/app-home/op_red_thumb.png",
                    "header1" => "ONEPASS",
                    "header1_color" => "#000000",
                    "header2" => "BLACK",
                    "header2_color" => "#000000",
                    "subheader" => "UNLIMITED VALIDITY",
                    "desc_header" => "No Expiry",
                    "desc_subheader" => "Limited Workouts"
                ]
            ],
            "footer" => [
                "index" => 0,
                "text" => "Try the complementary pass @1799 & get 100% instant cashback",
                "button_text" => "Know More"
            ]
        ],
        "after_purchase" => [
            "red" => [
                "pass_image"  => "https://b.fitn.in/passes/app-home/op_red_thumb.png",
                "name"  => "",
                "header" => "UNLIMITED USAGE",
                "subheader" => "", // duration or sessions
                "left_text"  => "UPCOMING: ",
                "left_value"  => 0,
                "right_text"  => "COMPLETED: ",
                "right_value"  => 0
            ],
            "black" => [
                "pass_image"  => "https://b.fitn.in/passes/app-home/op_black_thumb.png",
                "name"  => "",
                "header" => "UNLIMITED VALIDITY",
                "subheader" => "", // duration or sessions
                "left_text"  => "UPCOMING: ",
                "left_value"  => 0,
                "right_text"  => "COMPLETED: ",
                "right_value"  => 0
            ]
        ]
    ]
];