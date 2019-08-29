<?php

return [
    'service_cat_steps_map' => [
        "65"=>	2000,//Gym
        "1"=>	1000,//Yoga
        "19"=>	2000,//Zumba
        "163"=>	1000,//Fitness Studios
        "17"=>	1000,//MMA & Kickboxing
        "5"=>	1000,//Cross Functional Training
        "2"=>	2000,//Dance
        "4"=>	1000,//Pilates
        "86"=>	2000,//Spinning & Indoor Cycling
        "118"=>	1000,//Aerial Fitness
        "97"=>	1000,//Pre-Natal Fitness
        "114"=>	3000,//Marathon Training
        "123"=>	2500,//Swimming
        "3"=>	1000,//Martial Arts
    ],
    'individual_steps' => [
        'goal' => 5000
    ],
    'corporate_steps' => [
        'goal' => 1000000000
    ],
    'health_images' => [
        'foot_image' => 'https://b.fitn.in/reliance/home_shoe.png',
        'workout_image' => 'https://b.fitn.in/reliance/home_dumbbell.png',
    ],
    'health_popup' => [
        'title' => '#WalkpeChal',
        // 'image' => 'https://b.fitn.in/reliance/reliance_logo.png',
        'image' => 'https://b.fitn.in/reliance/reliance_new_logo.jpg',
        'message' => "By agreeing to take part in the walkpechal program I hereby grant consent:\n\n1. to override my NDNC registration and receive any communication from Reliance Nippon Life Insurance Company Ltd. (“RNLIC”) on SMS or voice call or e-mail or WhatsApp.\n\n2. To share my data stored in the fitternity app with RNLIC for the purpose of walkpechal and other similar health campaigns.\n\nReliance Nippon Life Insurance Company Limited, IRDAI Reg. 121, CIN – U66010MH2001PLC167089.\nTrade logo belongs to Anil Dhirubhai Ambani Ventures Private Limited & Nippon Life Insurance Company and used by Reliance Nippon Life Insurance Company Limited under license",
        // 'message' => "I hereby grant consent to override my NDNC registration and receive any communication from Reliance Nippon Life Insurance Company Ltd. (“RNLIC”) on SMS or voice call or e-mail or WhatsApp.\n\nI agree and understand that the information contained in the said communication is classified and confidential & that I am bound to maintain utmost confidentiality of the same.\n\nReliance Nippon Life Insurance Company Limited, IRDAI Reg. 121, CIN – U66010MH2001PLC167089.\n\nTrade logo belongs to Anil Dhirubhai Ambani Ventures Private Limited & Nippon Life Insurance Company and used by Reliance Nippon Life Insurance Company Limited under license",
        // 'message' => "I hereby grant consent to override my NDNC registration and receive any communication from Reliance Nippon Life Insurance Company Ltd. (\"RNLIC\") on sms or Whatsapp.\n\nI agree and understand that the information contained in the said communication is classified and confidential & that I am bound maintain utmost confidentiality of the same.\n\nI agree that all such information received shall be subject to the privacy policy available on the website of RNLIC.",
        'button_title' => 'I Agree'
    ],
    'health_booking_ios_non_reliance' => [
        'header' => "Book workout at any Gym or Studio near you and get closer to your goals.",
        'button_title' => 'Know More'
    ],
    'health_booking_ios_reliance' => [
        'header' => "Get 2 Free Workouts: Use code RELIANCE to book a session at any Gym or Studio near you.",
        'button_title' => 'Know More'
    ],
    'health_booking_android_reliance' => [
        'header' => "Get 2 Free Workouts: Use code RELIANCE to book a session at any Gym or Studio near you.",
        'button_title' => 'Know More',
        'slider' => [
            'header' => 'Each workout gets you more steps and gets you closer to your goals.',
            'categories' => [
                [
                    'name' => 'Gym',
                    'steps' => '2000',
                    'steps_text' => 'STEPS'
                ],
                [
                    'name' => 'Yoga',
                    'steps' => '1000',
                    'steps_text' => 'STEPS'
                ],
                [
                    'name' => 'Zumba',
                    'steps' => '2000',
                    'steps_text' => 'STEPS'
                ],
                [
                    'name' => 'Swimming',
                    'steps' => '2500',
                    'steps_text' => 'STEPS'
                ]
            ],
            'more_text' => '& many more',
            'button_title' => 'BOOK'
        ]
    ],
    'health_booking_android_non_reliance' => [
        'header' => "Book workout at any Gym or Studio near you and get closer to your goals.",
        'button_title' => 'Know More',
        'slider' => [
            'header' => 'Each workout gets you more steps and gets you closer to your goals.',
            'categories' => [
                [
                    'name' => 'Gym',
                    'steps' => '2000',
                    'steps_text' => 'STEPS'
                ],
                [
                    'name' => 'Yoga',
                    'steps' => '1000',
                    'steps_text' => 'STEPS'
                ],
                [
                    'name' => 'Zumba',
                    'steps' => '2000',
                    'steps_text' => 'STEPS'
                ],
                [
                    'name' => 'Swimming',
                    'steps' => '2500',
                    'steps_text' => 'STEPS'
                ]
            ],
            'more_text' => '& many more',
            'button_title' => 'BOOK'
        ]
    ],
    'dob_popup' => [
        'header' => '#WalkpeChal',
        'title' => 'Date of Birth',
        'text' => 'Please select your Date of Birth to proceed & to get onto #walkchallenge',
        'button_title' => 'Proceed'
    ],
    'leader_board' => [
        'background' => 'https://b.fitn.in/reliance/leader_bg.png',
        'leader_rank1' => 'https://b.fitn.in/reliance/leader_rank1.png',
        'leader_rank2' => 'https://b.fitn.in/reliance/leader_rank2.png',
        'leader_rank3' => 'https://b.fitn.in/reliance/leader_rank3.png',
        'color_rank1' => '#be1f0d',
        'color_rank2' => '#199920',
        'color_rank3' => '#fc9c29',
        'self_color' => '#4fa4a3',
        "checkout"=> [
            "text"=> "Achieve your steps milestone to earn exciting rewards",
            "button_title"=> "Rewards"
        ],
        'earn_steps' => [
            // 'header' => 'Earn More Steps',
            // 'title' => 'Earn steps by attending more sessions',
            // 'description' => 'You can earn more steps by taking more workout session on fitternity and increase your rank'
        ],
        'filters' => [
            ['name' => 'Cities', 'field' => 'reliance_city'],
            ['name' => 'Departments', 'field' => 'reliance_department'],
            ['name' => 'Locations', 'field' => 'reliance_location']
        ]
    ],
    'reliance' => [
        'reliance_logo' => 'https://b.fitn.in/reliance/reliance_new_logo.jpg',
        // 'start_date' => 1560500400,
        'start_date' => 1564597800,
        'corporate_id' => 1,
        'email_pattern' => '/\@(relianceada)\.com$/',
        'customer_email_list' => [
            // ['email' => 'akhilkulkarni@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'dhruvsarawagi@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'laxanshadesara@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'gauravravi@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'sailismart@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'neha@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'jayamvora@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'kailashbajya@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'ankitamamni@fitternity.com', 'department' => 'IT', 'designation' => 'Software Engineer', 'location' => ''],
            // ['email' => 'jyotijuneja925@live.com', 'department' => '', 'designation' => '', 'location' => ''],
            // ['email' => 'jyotijuneja295@icloud.com', 'department' => '', 'designation' => '', 'location' => '']
        ],
        'customer_list' => [
            // '9619240452',
            // '9767000029',
            // '7506262489',
            // '7506026203',
            // '9824313243',
            // '8169961014'
        ]
    ],


    "non_reliance"=> [
        "image" => "https://b.fitn.in/reliance/reliance_new_logo.jpg",
        "header"=> "#WalkpeChal",
        "text"=> "MissionMoon | 30 Days | 100 Cr steps",
        // "Footer"=> "Join Reliance Nippon Life #walkchallenge & get onto a step challenge with 40,000 other users.",
        "Footer"=> "Join India's largest fitness challenge along with other lakhs of users.",
        "button_title"=> "Know More",
        'enable_button_text' => 'I Agree',
        // "section1"=> "Walking doesn't seem to be rewarding you yet. Taking the stairs at office or home is not motivating?...\n\nWhat if we tell you that every step you take, lets you earn exciting rewards.\n\nTo join, allow access to calculate daily steps & fitness activities.",
        // "section1"=> "I hereby grant consent to override my NDNC registration and receive any communication from Reliance Nippon Life Insurance Company Ltd. (\"RNLIC\") on sms or Whatsapp.\n\nI agree and understand that the information contained in the said communication is classified and confidential & that I am bound maintain utmost confidentiality of the same.\n\nI agree that all such information received shall be subject to the privacy policy available on the website of RNLIC."
        // "section1"=> "I hereby grant consent to override my NDNC registration and receive any communication from Reliance Nippon Life Insurance Company Ltd. (“RNLIC”) on SMS or voice call or e-mail or WhatsApp.\n\nI agree and understand that the information contained in the said communication is classified and confidential & that I am bound to maintain utmost confidentiality of the same.\n\nReliance Nippon Life Insurance Company Limited, IRDAI Reg. 121, CIN – U66010MH2001PLC167089.\n\nTrade logo belongs to Anil Dhirubhai Ambani Ventures Private Limited & Nippon Life Insurance Company and used by Reliance Nippon Life Insurance Company Limited under license",
        "section1"=> "By agreeing to take part in the walkpechal program I hereby grant consent:\n\n1. to override my NDNC registration and receive any communication from Reliance Nippon Life Insurance Company Ltd. (“RNLIC”) on SMS or voice call or e-mail or WhatsApp.\n\n2. To share my data stored in the fitternity app with RNLIC for the purpose of walkpechal and other similar health campaigns.\n\nReliance Nippon Life Insurance Company Limited, IRDAI Reg. 121, CIN – U66010MH2001PLC167089.\nTrade logo belongs to Anil Dhirubhai Ambani Ventures Private Limited & Nippon Life Insurance Company and used by Reliance Nippon Life Insurance Company Limited under license",
    ],
    "non_reliance_android"=> [
        "image" => "https://b.fitn.in/reliance/reliance_new_logo.jpg",
        "header"=> "#WalkpeChal",
        "text"=> "MissionMoon | 30 Days | 100 Cr steps",
        "Footer"=> "Join India's largest fitness challenge along with other lakhs of users.",
        "button_title"=> "Know More",
        'enable_button_text' => 'ENABLE',
        "section1"=> "1. Join India's Biggest Fitness Challenge and join lakhs of users.\n\n2. 30 days | 100 Cr. Steps | 600 Cities Challenge to Walk to the Moon\n\n3. Get your Friends & Family and track your progress on the Leaderboard",
        "tnc"=> [
            "agree_text" => "",
            "terms_condition_text" => "Please read the terms & conditions before you agree",
            "terms_condition_link" => "https://www.fitternity.com/reliancetnc",
            "title" => "Terms and Conditions",
            "selected" => false
        ]
    ]
];

?>


