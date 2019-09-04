<?php
$apiUrl = \Config::get('app.url');
$success_page_template = Config::get('successPage');
// $red_pass = 'https://b.fitn.in/global/classpass/mobile/red%20card-website.png';
// $black_pass = 'https://b.fitn.in/global/classpass/mobile/back%20card%20-%20website.png';
$red_pass = 'https://b.fitn.in/passes/cards/onepass-red.png';
$black_pass = 'https://b.fitn.in/passes/cards/onepass-black.png';
return [
    'list' => [
        'title'=>'ONEPASS',
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
                    'header' => "In addition to owning the coolest fitness membership, OnePass users get exclusive rewards, vouchers and more ! \nLimited Period Offer, Only Few Passes Up For Grabs.",
                    'text' => 'Terms & Conditions',
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
                    'text' => 'Terms & Conditions',
                    "title" =>'Terms and Conditions',
                    'url' => $apiUrl.'/passtermscondition?type=subscripe'

                ]
            ]
        ],
        'app_passes' => [
            [
                'header' => 'ONEPASS RED',
                'header1' => 'ONEPASS ',
                'header1_color' => '#000000',
                'header2' => 'RED',
                'header2_color' => '#d50000',
                'card_header' => 'UNLIMITED USAGE',
                'subheader' => 'ALL ACCESS PASS',
                'pass_type' => 'red',
                'text' => '',
                'image' => $red_pass,
                'image1' => 'http://b.fitn.in/passes/onepass-app.png',
                'image2' => 'https://b.fitn.in/global/onepass/pass%20line%20design.png',
                'why_pass' => [
                    'header' => 'WHY GO FOR A PASS',
                    'text' => 'LIMITLESS WORKOUTS, LIMITLESS CHOICES, LIMITLESS VALIDITY, LIMITLESS YOU',
                    'data' => [
                        'ONEPass Fits In Your (Busy) Life',
                        'ONEPass Gives You Membership Privileges'
                    ]
                ],
                'offerings' => [
                    'text' => (json_decode('"'."\u2713".'"')." Limitless workouts across 12,000+ fitness classes, gyms and sports facilities across India.\n".json_decode('"'."\u2713".'"')." Use it like a fitness membership - choose a duration of 15 days to 1 year."),
                    'button_text' => 'Checkout Gyms/Studios',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => "In addition to owning the coolest fitness membership, OnePass users get exclusive rewards, vouchers and more ! \nLimited Period Offer, Only few passes for Grabs.", // need content
                    'text' => 'Terms and Conditions',
                    "title" =>'Terms and Conditions',
                    'url' => $apiUrl.'/passtermscondition?type=unlimited'
                ]
            ],
            [
                'header' => 'ONEPASS BLACK',
                'header1' => 'ONEPASS ',
                'header1_color' => '#000000',
                'header2' => 'BLACK',
                'header2_color' => '#000000',
                'card_header' => 'UNLIMITED VALIDITY',
                'subheader' => 'ALL ACCESS PASS',
                'pass_type' => 'black',
                'text' => '',
                'image' => $black_pass,
                'image1' => 'http://b.fitn.in/passes/onepass-app.png',
                'image2' => 'https://b.fitn.in/global/onepass/pass%20line%20design.png',
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
                    'button_text' => 'Checkout Gyms/Studios',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => "In addition to owning the coolest fitness membership, OnePass users get exclusive rewards, vouchers and more ! \nLimited Period Offer, Only few passes for Grabs.", // need content
                    'text' => 'Terms and Conditions',
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
            <li>OnePass bookings will start from 5th September 2019</li>
            <li>Incase you find a fitness/sports facility which is not yet part of the OnePass network - We will work to on-board the center within 1-15 working days of your request given alignment of standard terms between Fitternity & the facility</li>
            <li>Incase you're not enjoying OnePass - You can take a refund (No Questions asked). You pro-rata based un-utilized amount will be converted to Fitcash on Fitternity(1 Rupee = 1 Fitcash) and can be used to buy any other service/membership on Fitternity. 5% of your initial payment for OnePass upto a maximum of Rs 500 will be deducted to process this transition from OnePass to Fitcash</li>
            <li>The cashback received via any OnePass transaction can be only be redeemed to upgrade your OnePass</li>
        </ul>"
    ],
    'question_list' => [
        [
            'question' => 'How many sessions can I book in a day?',
            'answer' => 'Onepass Red users can book a single session a day. Onepass Black users can book multiple sessions in a day.'
        ],
        [
            'question' => 'Can I book multiple sessions at the same fitness center?',
            'answer' => 'Yes, Onepass gives you the flexibility to explore sessions at any fitness center. You can book sessions at any one particular fitness center or can explore fitness options/services at different centers.'
        ],
        [
            'question' => 'Do I have access to Swimming Sessions / Luxury Hotel?',
            'answer' => 'Onepass enables you to workout across 17 categories with swimming being 1 of them. Onepass includes Swimming & Gym sessions at Luxury Hotels. Select hotels are not on Onepass and we will be on-boarding them/new luxury hotels soon. The list of partners can be viewed here'
        ],

        [
            'question' => 'Can I change the date of my activation?',
            'answer' => 'Yes the date of activation can be changed/extended upto 31st December 2019.'
        ],
        [
            'question' => 'When is the program starting?',
            'answer' => 'Booking process will be available starting 5th september 2019. The booking facility will be available only on the Fitternity App.'
        ],
        [
            'question' => 'What is the Validity of my trial cash-back on OnePass Red?',
            'answer' => 'OnePass Red trial cashback has a validity of 30 days from the activation date of OnePass subscription. This cashback can only be redeemed to renew/purchase your OnePass subscription.'
        ],
        [
            'question' => 'Do I get a physical copy of the pass to show at the gym?',
            'answer' => 'OnePass is an online entity which can only be used on the Fitternity App to book your workout sessions. Once the booking is successful on the Fitternity App, all you have to do is share your booking confirmation details at the fitness center to activate your session.'
        ],
        [
            'question' => 'Which services are available to book on the OnePass?',
            'answer' => 'All fitness forms of activities are available on the OnePass program across 12000+ fitness centers on the Fitternity network. The outlets on Onepass can be checked here'
        ],
        [
            'question' => 'Can the customer request to onboard a fitness center on the OnePass Program?',
            'answer' => 'Yes, Fitternity will work to on-board the fitness center within 1-15 working days of your request given alignment of standard terms between Fitternity & the facility.'
        ],
        [
            'question' => 'Is FitSquad available on OnePass?',
            'answer' => 'The Fitsquad Loyalty Program is currently not available on Onepass Program.'
        ],
        [
            'question' => 'What is the timeline to book sessions prior to session time?',
            'answer' => 'You can book sessions for all facilities on Onepass basis availability. For certain facilities the sessions can be booked real time , while for certain facilities the sessions have to be booked 2 hours in advance.'
        ],
        [
            'question' => 'Will the prices change after the OnePass Trial subscription expires?',
            'answer' => 'All prices are subject to change without any prior notice.'
        ],
        [
            'question' => 'What is the refund policy?',
            'answer' => 'The customer can take a refund (No Questions asked). The calculation will be based on pro-rata based on un-utilized amount which will be converted to Fitcash in Fitternity wallet (1 Rupee = 1 Fitcash) and can be used to buy any other service/membership on Fitternity. 3% convenience fee will be charged over & above the usage of subscription to process this transition from OnePass to Fitcash.'
        ],
        [
            'question' => 'Can give my OnePass to my friends and family Will the prices change after the OnePass Trial subscription expires?',
            'answer' => 'All prices are subject to change without any prior notice.'
        ]
    ],
    'success'=>[
        'image' => 'https://b.fitn.in/iconsv1/success-pages/BookingSuccessfulpps.png',
        "header" => "Your ___type pass is active",
        'subline'=>'Hi __customer_name, your __pass_name for __pass_duration is now confirmed. We have also sent you a confirmation Email and SMS.',
        'subline_1'=>'Booking starts from 5th of September 2019.',
        "pass" => [
            // "text" => "(__usage_remark) __end_date",
            "text" => "__end_date",
            'header' => 'pass_name',
            'subheader' => 'duration_text PASS FOR usage_text',
            'image' => $red_pass,
            'name' => 'ONE PASS',
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
            'name' => 'ONE PASS',
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
            'name' => 'ONE PASS',
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
            "header_new_img" => "http://b.fitn.in/passes/onepass-app.png",
            "header_sub_text" => "FOR EVERYTHING HEALTH AND FITNESS",
            "text"  => "Enjoy limitless access across Fitternity's 12,000+ fitness classes, gyms and sports facilities across India",
            "passes" => [
                [
                    "image" => "https://b.fitn.in/passes/app-home/op_red_thumb.png",
                    "header1" => "ONEPASS",
                    "header1_color" => "#000000",
                    "header2" => "RED",
                    "header2_color" => "#d50000",
                    //"subheader" => "UNLIMITED ACCESS",
                    "desc_header" => "Limitless Access",//"Limitless Workouts",
                    //"desc_subheader" => "With Expiry" 
                ],
                [
                    "image" => "https://b.fitn.in/passes/app-home/op_black_thumb.png",
                    "header1" => "ONEPASS",
                    "header1_color" => "#000000",
                    "header2" => "BLACK",
                    "header2_color" => "#000000",
                    //"subheader" => "UNLIMITED VALIDITY",
                    "desc_header" => "Limitless Validity",//"No Expiry",
                    //"desc_subheader" => "Limited Workouts"
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
                "pass_image"  => "https://b.fitn.in/passes/cards/onepass-red.png",
                "name"  => "",
                "header" => "UNLIMITED USAGE",
                "subheader" => "", // duration or sessions
                "top_right_button_text" => "BOOK >",
                "left_text"  => "UPCOMING: ",
                "left_value"  => 0,
                "right_text"  => "COMPLETED: ",
                "right_value"  => 0,

                'footer' => [
                    'section1' => [
                        'button1_text' => 'REPEAT LAST BOOKING',
                        // 'button1_subtext' => '',
                        'button2_text' => 'VIEW ALL BOOKINGS',
                        'no_last_order' => true,
                        'contact_text' => 'Need Help? Contact your Personal Concierge',
                        'contact_image' => 'https://b.fitn.in/passes/app-home/contact-us.png',
                        'contact_no' => '+919876543210'
                    ],
                    'section2' => [
                        'text' => 'Your Onepass Red will expire after remaining_text',
                        'subtext' => 'Upto 50% Off + Additional 20% Off On Onepass',
                        'button_text' => 'RENEW NOW',
                        'index' => 0
                    ],
                    'section3' => [
                        'text' => 'Your Onepass Red has expired',
                        'subtext' => 'Upto 50% Off + Additional 20% Off On Onepass',
                        'button_text' => 'RENEW',
                        'index' => 0
                    ]
                ],
                'pass_expired' => false,
                'tnc_text' => 'View T&C',
                "terms" => "<h2>Terms and Conditions</h2>
                            <ul>
                                <li>OnePass bookings will start from 5th September 2019</li>
                                <li>Incase you find a fitness/sports facility which is not yet part of the OnePass network - We will work to on-board the center within 1-15 working days of your request given alignment of standard terms between Fitternity & the facility</li>
                                <li></li>
                                <li>The cashback received via any OnePass transaction can be only be redeemed to upgrade your OnePass</li>
                            </ul>"
            ],
            "black" => [
                "pass_image"  => "https://b.fitn.in/passes/cards/onepass-black.png",
                "name"  => "",
                "header" => "UNLIMITED VALIDITY",
                "subheader" => "", // duration or sessions
                "top_right_button_text" => "BOOK >",
                "left_text"  => "UPCOMING: ",
                "left_value"  => "0",
                "right_text"  => "COMPLETED: ",
                "right_value"  => "0",
                'footer' => [
                    'section1' => [
                        'button1_text' => 'REPEAT LAST BOOKING',
                        // 'button1_subtext' => '',
                        'button2_text' => 'VIEW ALL BOOKINGS',
                        'no_last_order' => true,
                        'contact_text' => 'Need Help? Contact your Personal Concierge',
                        'contact_image' => 'https://b.fitn.in/passes/app-home/contact-us.png',
                        'contact_no' => '+919876543210'
                    ],
                    'section2' => [
                        'text' => 'Your Onepass Black will expire after remaining_text',
                        'subtext' => 'Upto 50% Off + Additional 20% Off On Onepass',
                        'button_text' => 'RENEW NOW',
                        'index' => 1
                    ],
                    'section3' => [
                        'text' => 'Your Onepass Black has expired',
                        'subtext' => 'Upto 50% Off + Additional 20% Off On Onepass',
                        'button_text' => 'RENEW',
                        'index' => 1
                    ]
                ],
                'pass_expired' => false,
                'tnc_text' => 'View T&C',
                "terms" => "<h2>Terms and Conditions</h2>
                            <ul>
                                <li>OnePass bookings will start from 5th September 2019</li>
                                <li>Incase you find a fitness/sports facility which is not yet part of the OnePass network - We will work to on-board the center within 1-15 working days of your request given alignment of standard terms between Fitternity & the facility</li>
                                <li>Incase you're not enjoying OnePass - You can take a refund (No Questions asked). You pro-rata based un-utilized amount will be converted to Fitcash on Fitternity(1 Rupee = 1 Fitcash) and can be used to buy any other service/membership on Fitternity. 5% of your initial payment for OnePass upto a maximum of Rs 500 will be deducted to process this transition from OnePass to Fitcash</li>
                                <li>The cashback received via any OnePass transaction can be only be redeemed to upgrade your OnePass</li>
                            </ul>"
            ]
        ]
    ],

    "transaction_capture" => [
        "red" => [
            "image" => "https://b.fitn.in/passes/app-home/onepass-icon-new.png",
            "header1" => "ONEPASS",
            "header1_color" => "#000000",
            "header2" => "RED",
            "header2_color" => "#d50000",
            "subheader" => "UNLIMITED USAGE",
            "desc_subheader" => "With Expiry" 
        ],
        "black" =>[
            "image" => "https://b.fitn.in/passes/app-home/onepass-icon-new.png",
            "header1" => "ONEPASS",
            "header1_color" => "#000000",
            "header2" => "BLACK",
            "header2_color" => "#000000",
            "subheader" => "UNLIMITED VALIDITY",
            "desc_subheader" => "Limited Workouts"
        ]
            
    ]
];