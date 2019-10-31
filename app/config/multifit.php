<?php
return array(
    "vendors_slug"=> [
        ['name'=>'Mysuru', 'slug'=>'multifitvendor/multifit---mysuru-mysuru', 'city_brand'=>false],
        ['name'=>'Nagpur', 'slug'=>'multifitvendor/multifit-nagpur-dharampeth-nagpur', 'city_brand'=>false],
        ['name'=>'Salem', 'slug'=>'multifitvendor/multifit---salem-salem', 'city_brand'=>false]
    ],
    "without_brand_city"=> [
        'mysure'=>['slug'=>'multifitvendor/multifit---mysuru-mysuru', 'city_brand'=>false],
        'mysuru'=>['slug'=>'multifitvendor/multifit---mysuru-mysuru', 'city_brand'=>false],
        'nagpur'=> ['slug'=>'multifitvendor/multifit-nagpur-dharampeth-nagpur', 'city_brand'=>false],
        'salem'=> ['slug'=>'multifitvendor/multifit---salem-salem', 'city_brand'=>false]
    ],

    "attached_pass" => [
        "complementary" => [
            //"title" =>"membership_duration_text service_name Membership+ Complimentary pass_details_duration_text All Access Onepass Red",
            "header" => "membership_duration_text menbership at vendor_name",
            "subheader" => "Complimentary pass_details_duration_text Trial OnePass RED",
            "image" => "https://b.fitn.in/global/onepass/OnePass.png",
            "data" => [
                [
                    "title" => "What you get in this Membership?",
                    "text" => "Lowest price vendor_name membership + pass_details_duration_text All Access Trial OnePass RED"
                ],
                [
                    "title" => "What is OnePass RED?",
                    "text" => "OnePass RED is Fitternity's exclusive offering which gives you limitless access to 12,000+ fitness classes, gyms and sports facilities across India"
                ]
            ],
            "remarks" => [
                "&bull; Your Complimentary OnePass RED gives you access to a total of pass_details_total_sessions",
            ]
            
        ],
    
        "upgrade" => [
            //"title" => "Your Existing Membership + pass_details_duration_text pass_details_total_sessions All Access OnePass Red",
            "header" => "Upgrade To Membership Plus",
            "subheader" => "vendor_name membership + pass_details_duration_text All Access OnePass",
            "image" => "https://b.fitn.in/global/onepass/OnePass.png",
            "data" => [
                [
                    "title" => "Why upgrade to Membership Plus?",
                    "text" => "By upgrading to <b>Membership Plus</b> you can enjoy access to mulitple fitness activities like swimming at 5-star hotels, MMA, dance, yoga classes and more across India"
                ],
                [
                    "title" => "What is OnePass RED?",
                    "text" => "OnePass RED is Fitternity's exclusive offering which gives you limitless access to 12,000+ fitness classes, gyms and sports facilities across India"
                ]
            ],
            "remarks" => [
                "&bull; Your Membership Plus <b>OnePass RED</b> will be valid for pass_details_duration_text and will give you access to a total of pass_details_total_sessions.",
                "&bull; Membership Plus OnePass RED can be used to book a maximum of pass_details_monthly_total_sessions_text in a month."
            ]
        ],
    
        "membership_plus" => [
            //"title" => "membership_duration_text service_name Membership + pass_details_duration_text pass_details_total_sessions All Access OnePass Red",
            "extra_info" => "OnePass RED gives you access to muliple gyms & fitness centres along with your vendor_name membership",
            "extra_info_text_color" => "#d43b25",
            "background_color" => "#facaa3",
            "header" => "Membership Plus - vendor_name",
            "subheader" => "Lowest price vendor_name membership + pass_details_duration_text All Access OnePass",
            "image" => "https://b.fitn.in/global/onepass/OnePass.png",
            "data" => [
                [
                    "title" => "What you get in Membership Plus?",
                    "text" => "With <b>Membership Plus</b>, you get pass_details_duration_text all access OnePass RED along with your membership_duration_text membership"
                ],
                [
                    "title" => "What is OnePass RED?",
                    "text" => "OnePass RED is Fitternity's exclusive offering which gives you limitless access to 12,000+ fitness classes, gyms and sports facilities across India"
                ]
            ],
            "remarks" => [
                "&bull; Your Membership Plus <b>OnePass RED<b> will be valid for pass_details_duration_text and will give you access to a total of pass_details_total_sessions.",
                "&bull; Membership Plus OnePass RED can be used to book a maximum of pass_details_monthly_total_sessions_text in a month."
            ]
        ]
    ]
    
);