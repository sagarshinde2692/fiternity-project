<?php

return array(



    'pre_register' => [
        "cover_image" => "https://b.fitn.in/external-vouchers1/new_grid_images/cover%20image.jpg",
        'header' => [
            'image' =>  'https://b.fitn.in/external-vouchers1/new_grid_images/old_fitsquad_app_cover.png', //'https://b.fitn.in/loyalty/FITSQUAD-APP-Cover%20image-DESIGN.jpg',
            'image_new' => "https://b.fitn.in/external-vouchers1/new_grid_images/new%20logo.png",
            'background_image' => 'https://b.fitn.in/external-vouchers1/new_grid_images/cover_image_post.png', // "https://b.fitn.in/external-vouchers1/new_grid_images/app/fitsquad_background.png",
            'ratio' => 1.23,
            'logo_ratio' => 1.23,
            'url'=> Config::get('loyalty_constants.register_url'),
            'title' => 'GET REWARDED FOR EVERY WORKOUT',
            'sub_title' => 'BURN MORE, EARN MORE',
            'button_text' => 'JOIN THE CLUB',
            'text' => 'workout and earn rewards worth ₹ 32,000'
         ],

        // 'partners' => [
        //     'header' => "<b>GET EXCITING REWARDS ON ACHIEVING MILESTONES OF <font color='#f8a81b'> 30, 75, 150 & 250 </font> WORKOUTS</b>",
        //     'data' => [
        //         "https://b.fitn.in/loyalty/vouchers3/AMAZON.png",
        //         "https://b.fitn.in/external-vouchers1/zomato-gold-mobile-logo.jpg",
        //         "https://b.fitn.in/external-vouchers/JCB1.png",
        //         "https://b.fitn.in/external-vouchers/epigamia.png",
        //         "https://b.fitn.in/external-vouchers/small-cleartrip%20logo.jpg",
        //         "https://b.fitn.in/external-vouchers/O21.png",
        //         "https://b.fitn.in/external-vouchers/book%20my%20show.png",
        //         "https://b.fitn.in/external-vouchers1/UberEats-Logo-OnWhite-Color-V.png",
        //         "https://b.fitn.in/loyalty/goqii---logo-mobile.jpg",
        //     ]
        // ],

        'steps' => [
            'header' => 'NO MATTER WHERE YOU WORKOUT | GET REWARDED IN 3 EASY STEPS',
            'data' => [
                [
                    'title' => 'CHECK-IN EVERY TIME YOU WORKOUT',
                    'description' => 'Check-in at the gym/studio by scanning the QR code through the app',
                    'image' => 'https://b.fitn.in/loyalty/Group%20147%281%29.png'
                ],
                [
                    'title' => 'WORKOUT MORE AND LEVEL UP',
                    'description' => 'Reach easily achievable Fitness Milestones',
                    'image' => 'https://b.fitn.in/loyalty/Group%20128.png'
                ],
                [
                    'title' => 'EARN REWARDS WORTH Rs. 32,000',
                    'description' => 'Exciting Rewards from Best Brands in the Country',
                    'image' => 'https://b.fitn.in/loyalty/Group%2049.png'
                ],
            ]
        ],


        'check_ins' => [
            'header' => "<font color='#3B3B3B'> GET EXCITING REWARDS ON ACHIEVING MILESTONES OF <font color='#f8a81b'> 30, 75, 150 & 250 </font> WORKOUTS</font>",//'GET CRAZY REWARDS ON COMPLETING EACH MILESTONE',
            'ios_old' => "GET EXCITING REWARDS ON ACHIEVING MILESTONES OF 30, 75, 150 & 250 WORKOUTS",
            'data' => []
        ],

        'footer' => [
            'image' => "https://b.fitn.in/external-vouchers1/new_grid_images/fitsquad_app_design_footer.png",
            'ratio' => 1.38,
            'url'=> Config::get('loyalty_constants.register_url'),
            'button_text' => 'JOIN THE CLUB'
        ],
        'Terms' => [
            'Title' => 'FitSquad - FAQ and Terms and conditions',
            'text' => 'HTML Text',
            'url' => Config::get('loyalty_constants.fitsquad_faq'),
            'button_text' => 'JOIN THE CLUB'
        ],
        "partners_new" => [
            "header" => '',
            "data" => []
        ]
    ],
    'post_register' => [
        'header' => [
            'logo' => "https://b.fitn.in/external-vouchers1/new_grid_images/new%20logo.png",//'https://b.fitn.in/loyalty/MOBILE%20PROFILE%20LOGO.png',
            'text' => 'Hi <b>$customer_name</b>,<br/><br/>$check_ins/$next_milestone_checkins check-ins completed. $milestone_text',
            'background_image' => "https://b.fitn.in/external-vouchers1/new_grid_images/app/fitsquad_background.png", // 'https://b.fitn.in/loyalty/banner.jpg',
            'ratio' => 0.36,
        ],
        'milestones' => [
            'header' => 'Your Workout Journey',
            'subheader' => 'You are $next_milestone_check_ins check-ins away from milestone $next_milestone.'."\n\nYou can check-in for your workout at the gym/studio through a QR code present on homescreen",
            'description' => "Start working out and level up on your streak.\n Achieve milestones and earn crazy rewards",
            'data' => Config::get('loyalty_constants.milestones'),
            'footer' => 'Your workout counter will reset on $last_date',
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
            'Title' => 'FitSquad - FAQ and Terms and conditions',
            'text' => 'HTML Text',
            'url' => Config::get('loyalty_constants.fitsquad_faq')
        ],
    ],
    'past_check_in_header_text' => 'View Past Check Ins',

    'pre_register_check_ins_data_template' => [
        'title' => 'Milestone milestone',
        'milestone' => 'No of check-ins required',
        'count'=> 'count',
        'amount' => 'amount',
        'images' => []
    ],

    'post_register_rewards_data_outer_template' => [
        'title' => 'Milestone milestone',
        'description' => 'Select 1 reward',
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
        'terms' => 'terms',
        'coupon_description' => 'description',
        'claim_message' => "Are you sure you want to claim this reward? After claiming other rewards will get blocked.",
    ],
    'success_loyalty_header' => [
        'logo' => 'https://b.fitn.in/loyalty/SUCCESS%20PAGE%20LOGO.png',
        'header1' => 'You have successfully registered to FITSQUAD',
        'header2' => "India's Biggest Fitness Rewards Club.\nCheck-in for your workout through QR code present at studio & earn rewards.",
    ],
    
    'milestones' => [
        'header' => 'Your Workout Journey',
        'subheader' => 'You are $next_milestone_check_ins check-ins away from milestone $next_milestone',
        'description' => '$check_ins/$milestone_next_count check-ins done',
        'data' => Config::get('loyalty_constants.milestones')
    ],
    'receipt_message'=>'Please upload the membership receipt to claim your reward',
    'receipt_verification_message'=>'Your membership receipt is under verification. We will notify you post verification.',
    'bookings_block_message'=>'To claim this reward, you need to have transactions on Fitternity app / website worth at least Rs. booking_amount',
    'loyalty_reawrd_images'=>[
        "https://b.fitn.in/loyalty/vouchers3/AMAZON.png",
        "https://b.fitn.in/loyalty/vouchers3/ZOMATO.png",
        "https://b.fitn.in/external-vouchers1/JCB.png",
        "https://b.fitn.in/external-vouchers1/epigamia.png",
        "https://b.fitn.in/external-vouchers1/cleartrip.png",
        "https://b.fitn.in/external-vouchers1/o2.png",
        "https://b.fitn.in/external-vouchers1/book%20my%20show.png",
    ],
    
    "milestones_constant" => [
        "Milestone 1" => [
            "header" => "Cool Marvel starter kit with exciting vouchers",
            "sub_title" => "___count Days",
            "sub_header" => "Exclusive Marvel t-shirt or a gym bag along with exciting vouchers from The Man Company, Healthifyme ,The Label Life & Ease My Trip",
            "backgroud_color" => "#9300FF",
            "background_image" => "https://b.fitn.in/external-vouchers1/new_grid_images/milestone_1_background.png"
        ],
        "Milestone 2" => [
            "header" => "Exclusive Marvel merchandise/ healthy snacking hamper & exciting vouchers",
            "sub_title" => "___count Days",
            "sub_header" => "Exclusive Marvel t-shirt + shaker or Get a curated healthy snacking hamper with protein bars, makhana, peanut butter, healthy cookies and more along with exciting vouchers from O2 Spa, Fast & Up & Puma",
            "backgroud_color" => "#12A960",
            "background_image" => "https://b.fitn.in/external-vouchers1/new_grid_images/milestone_2__background.png"
        ],
        "Milestone 3" => [
            "header" => "Limited edition Marvel merchandise/ healthy snacking hamper",
            "sub_title" => "___count Days",
            "sub_header" => "Limited edition Marvel t-shirt + shaker or Get a curated healthy snacking hamper with protein bars, makhana, peanut butter, healthy cookies, muesli & much more",
            "backgroud_color" => "#187CFF",
            "background_image" => "https://b.fitn.in/external-vouchers1/new_grid_images/milestone_3_background.png"
        ],
        "Milestone 4" => [
            "header" => "Amazon Gift Card, Fitternity Fitcash or ONEPASS",
            "sub_title" => "___count Days",
            "sub_header" => "Exclusive voucher from Amazon or Earn Fitternity Fitcash or Get access to unlimited workout options with Fitternity Onepass",
            "backgroud_color" => "#BA0000",
            "background_image" => "https://b.fitn.in/external-vouchers1/new_grid_images/milestone_4_background.png"
        ]
    ]
);
?>