<?php
$apiUrl = \Config::get('app.url');
$success_page_template = Config::get('successPage');
// $red_pass = 'https://b.fitn.in/global/classpass/mobile/red%20card-website.png';
// $black_pass = 'https://b.fitn.in/global/classpass/mobile/back%20card%20-%20website.png';
$red_pass = 'https://b.fitn.in/passes/cards/onepass-red.png';
$black_pass = 'https://b.fitn.in/passes/cards/onepass-black.png';

return [
    'price_upper_limit' => 1001,
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
                    'header' => "In addition to owning the coolest fitness membership, OnePass users get exclusive rewards, vouchers and more! \nLimited Period Offer, Only Few Passes For Grabs.",
                    'text' => 'Terms & Conditions',
                    "title" =>'Terms and Conditions',
                    'url' => $apiUrl.'/passtermscondition?type=unlimited'
                ],
                'local_pass' => [
                    'header' => 'Onepass Red city_name',
                    'description' => 'description of red pass',
                    'button_text' => 'buy'
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
                        'OnePass Red give you Unlimited Access',
                        'OnePass Red gives you Membership Privileges',
                        'Onepass Red gives you the option to workout Anytime, Anywhere'
                    ]
                ],
                'offerings' => [
                    'text' => (json_decode('"'."\u2713".'"')." Limitless workouts across 12,000+ fitness classes, gyms and sports facilities across India.\n".json_decode('"'."\u2713".'"')." Use it like a fitness membership - choose a duration of 15 days to 1 year."),
                    'button_text' => 'Checkout Gyms/Studios',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => "In addition to owning the coolest fitness membership, OnePass users get exclusive rewards, vouchers and more! \n\nGet 30% Off + Additional FLAT INR 500 Off", // need content
                    'text' => 'Terms and Conditions',
                    "title" =>'Terms and Conditions',
                    'url' => $apiUrl.'/passtermscondition?type=unlimited'
                ],
                'local_pass' => [
                    'header' => 'Onepass Red city_name',
                    'description' => 'description of red pass',
                    'button_text' => 'buy'
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
                        'OnePass Black give you Unlimited Validity',
                        'OnePass Black gives you Membership Privileges',
                        'Onepass Black gives you the option to workout Anytime, Anywhere'
                    ]
                ],
                'offerings' => [
                    'text' => (json_decode('"'."\u2713".'"')." Get Limitless validity - Your membership will never expire!.\n".json_decode('"'."\u2713".'"')." Replace your membership by choosing a pack - ranging from 15 to 45 sessions with lifetime validity."),
                    'button_text' => 'Checkout Gyms/Studios',
                    'ratecards' => []
                ],
                'remarks' => [
                    'header' => "In addition to owning the coolest fitness membership, OnePass users get exclusive rewards, vouchers and more! \n\nGet 30% Off + Additional FLAT INR 500 Off", // need content
                    'text' => 'Terms and Conditions',
                    "title" =>'Terms and Conditions',
                    'url' => $apiUrl.'/passtermscondition?type=subscribe'

                ]
            ]
        ],
        'faq' => [
            'header' => 'FAQs',
            'text' => 'Click here to view all FAQ`s ',
            'title' => 'FAQ Title',
            'url' => $apiUrl.'/passfaq'
        ],
        'subheader' => 'duration_text PASS FOR usage_text'
    ],
    "total_available" => 300,
    "terms"=>[
        "red"=>[
            "<ul>
                <li>Discount of upto 30% off is pre-applied on MRP</li>
                <li>Code: FIVE00 will give the user additional INR 500 off</li>
                <li>Discount varies across different types of pass</li>
                <li>The offer cannot be clubbed with any other existing offer</li>
                <li>The offer expires on 6th October after which code: FIVE00 will be invalid</li>
                <li>Onepass Red is your personal health and fitness pass, it cannot be used by your friends or family members.</li>
                <li>All OnePass bookings can only be done through the Fitternity App.</li>
                <li>OnePass Red user will have access to book 1 session a day.</li>
                <li>In case if a customer does not attend the session, the session cannot be reversed or re-booked for the same day.</li>
                <li>Cashback receivable on the trial subscription of OnePass Red will be added in the Fitternity wallet in the form of FitCash after deducting GST fees.</li>
                <li>OnePass is a proprietary offering of Fitternity and bookings can only be done on the Fitternity app. Any booking or exchange of interest cannot be done at the gym/fitness center. If done so, Fitternity holds the rights to cancel the particular booking or take appropriate action.</li>
                <li>At select fitness centers available on the OnePass program, users have limited access to work out at the following locations (Applicable to both OnePass Black & OnePass Red Subscription) - </li>
                <br/> - F45 Training in Juhu  - 8 sessions per month.
                <br/> - F45 Training in Bandra One BKC - 8 sessions per month.</li>
                <li>Fitternity reserves the right to revise, update and amend the list of Gyms and Fitness Classes available for Onepass booking.</li>
                <li>No cashback streaks would be applicable for OnePass users on the attendance of workout sessions.</li>
                <li>In the case of session cancellation or unfulfillment from the service provider’s end, OnePass Red user will get an additional day.</li>
                <li>Slot availability is subject to change as per the availability at the fitness center.</li>
                <li>On occasions of Festivals, National holidays, Maintenance Days at the fitness center, session booking are subject to availability.</li>
                <li>OnePass is an arrangement /agreement /membership of Fitternity. All issues, doubts or clarification concerning OnePass should be communicated with Fitternity.</li>
                <li>Fitternity holds the right to cancel the OnePass subscription at any point of time without any prior notice. Upon cancellation, the refund amount would be calculated on a pro-rata basis and the same would be reverted back to the user's bank account.</li>
                <li>In case of cancellation of OnePass subscription, the refund would be transferred in the Fitternity wallet in the form of FitCash. The FitCash can be redeemed on all services available on Fitternity.</li>
                <li>In case of misbehavior by the user at the Vendor location, Fitness center or the staff, Fitternity holds the right to cancel the user's subscription without any prior notice. No refund amount would be processed in this scenario.</li>
                <li>Refund Policy is only applicable on subscriptions above 3 months.</li>
                <li>The user will be charged a 3% convenience fee on cancellation of his/her OnePass subscription (over and above usage of subscription on pro rata basis)</li>
                <li>If the user opts for  “Cash Pick Up” option for payment of dues,on any amount of subscription of OnePass, a flat pickup fee of INR 200 will be charged additionally, over and above the subscription amount.</li>	
                <li>In case you find a fitness/sports facility which is not yet part of the OnePass network - We will work to onboard the center within 1-15 working days of your request, given the alignment of standard terms between Fitternity & the facility</li>
                <li>In case the user is found misusing any of the OnePass services, he/she will be penalized at the discretion of Fitternity.</li>
                <li>In case of any injury or accident at the Fitness Center, any claims on Fitternity would not be applicable whatsoever.</li>
                <li>In case of any dispute, the final decision made by Fitternity would be final.</li>
                <li>In case of any assistance concerning OnePass write to us at onepass@fitternity.com or call your personal concierge at +917400062849</li>
            </ul><br/><br/>",
        ],
        "black" =>[
            "<ul>
                <li>Discount of upto 30% off is pre-applied on MRP</li>
                <li>Code: FIVE00 will give the user additional INR 500 off</li>
                <li>Discount varies across different types of pass</li>
                <li>The offer cannot be clubbed with any other existing offer</li>
                <li>The offer expires on 6th October after which code: FIVE00 will be invalid</li>
                <li>OnePass Black is your personal health and fitness pass, it cannot be used by your friends or family members.</li>
                <li>All OnePass bookings can only be done through the Fitternity App.</li>
                <li>OnePass is a proprietary offering of Fitternity and bookings can only be done on the Fitternity app. Any booking or exchange of interest cannot be done at the gym/fitness center. If done so, Fitternity holds the rights to cancel the particular booking or take appropriate action.</li>
                <li>At select fitness centers available on the OnePass program, users have limited access to work out at the following locations (Applicable to both OnePass Black & OnePass Red Subscription) - </li>
                <br/> - F45 Training in Juhu  - 8 sessions per month.
                <br/> - F45 Training in Bandra One BKC - 8 sessions per month.</li>
                <li>Fitternity reserves the right to revise, update and amend the list of Gyms and Fitness Classes available for Onepass booking.</li>
                <li>No cashback streaks would be applicable for OnePass users on attendance of workout sessions.</li>
                <li>In case if a customer does not attend the session, the session cannot be reversed or re-booked for the same day.</li>
                <li>In case of session cancellation or unfulfillment from the service provider’s end, the OnePass Black user will get the session back as well as an additional session.</li>
                <li>The session booked will be credited back to the user in his Fitternity profile, if he/she cancels the session 2 hours prior to the session time.</li>
                <li>Slot availability is subject to change as per availability slot availability at the fitness center.</li>
                <li>On occasions of Festivals, National holidays, Maintenance Days at the fitness center, session booking are subject to availability.</li>
                <li>OnePass is an arrangement /agreement /membership of Fitternity. All issues, doubts or clarification concerning OnePass should be communicated with Fitternity.</li>
                <li>Fitternity holds the right to cancel the OnePass subscription at any point of time without any prior notice. Upon cancellation, the refund amount would be calculated on a pro-rata basis and the same would be reverted back to the user's bank account.</li>
                <li>In case of cancellation of OnePass subscription, the refund would be transferred in the Fitternity wallet in the form of FitCash. The FitCash can be redeemed on all services available on Fitternity.</li>
                <li>In case of misbehavior by the user at the Vendor location, Fitness center or the staff, Fitternity holds the right to cancel the user's subscription without any prior notice. No refund amount would be processed in this scenario.</li>
                <li>Refund Policy is only applicable on subscriptions on 45 sessions & above.</li>
                <li>The user will be charged a 3% convenience fee on cancellation of his/her OnePass subscription (over and above usage of subscription on pro-rata basis)</li>
                <li>If the user opts for  “Cash Pick Up” option for payment of dues,on any amount of subscription of OnePass, a flat pickup fee of INR 200 will be charged additionally, over and above the subscription amount.</li>	
                <li>In case you find a fitness/sports facility which is not yet part of the OnePass network - We will work to onboard the center within 1-15 working days of your request, given alignment of standard terms between Fitternity & the facility</li>
                <li>In case the user is found misusing any of the OnePass services, he/she will be penalized at the discretion of Fitternity.</li>
                <li>In case of any injury or accident at the Fitness Center, any claims on Fitternity would not be applicable whatsoever.</li>
                <li>In case of any dispute the final decision made by Fitternity would be final.</li>
                <li>In case of any assistance concerning OnePass write to us at onepass@fitternity.com or call your personal concierge at +917400062849.</li>
            </ul><br/><br/>"
        ],
        "default"=>[
            "<ul>
                <li>Discount of upto 30% off is pre-applied on MRP</li>
                <li>Code: FIVE00 will give the user additional INR 500 off</li>
                <li>Discount varies across different types of pass</li>
                <li>The offer cannot be clubbed with any other existing offer</li>
                <li>The offer expires on 6th October after which code: FIVE00 will be invalid</li>
                <li>Onepass Red is your personal health and fitness pass, it cannot be used by your friends or family members.</li>
                <li>All OnePass bookings can only be done through the Fitternity App.</li>
                <li>OnePass Red user will have access to book 1 session a day.</li>
                <li>In case if a customer does not attend the session, the session cannot be reversed or re-booked for the same day.</li>
                <li>Cashback receivable on the trial subscription of OnePass Red will be added in the Fitternity wallet in the form of FitCash after deducting GST fees.</li>
                <li>Onepass Black is your personal health and fitness pass, it cannot be used by your friends or family members.</li>		
                <li>In case of session cancellation or unfulfillment from the service provider’s end, the OnePass Black user will get the session back as well as an additional session.</li>	
                <li>OnePass is a proprietary offering of Fitternity and bookings can only be done on the Fitternity app. Any booking or exchange of interest cannot be done at the gym/fitness center. If done so, Fitternity holds the rights to cancel the particular booking or take appropriate action.</li>
                <li>At select fitness centers available on the OnePass program, users have limited access to work out at the following locations (Applicable to both OnePass Black & OnePass Red Subscription) - </li>
                <br/> - F45 Training in Juhu  - 8 sessions per month.
                <br/> - F45 Training in Bandra One BKC - 8 sessions per month.</li>
                <li>Fitternity reserves the right to revise, update and amend the list of Gyms and Fitness Classes available for Onepass booking.</li>
                <li>No cashback streaks would be applicable for OnePass users on the attendance of workout sessions.</li>
                <li>In the case of session cancellation or unfulfillment from the service provider’s end, OnePass Red user will get an additional day.</li>
                <li>Slot availability is subject to change as per the availability at the fitness center.</li>
                <li>On occasions of Festivals, National holidays, Maintenance Days at the fitness center, session booking are subject to availability.</li>
                <li>OnePass is an arrangement /agreement /membership of Fitternity. All issues, doubts or clarification concerning OnePass should be communicated with Fitternity.</li>
                <li>Fitternity holds the right to cancel the OnePass subscription at any point of time without any prior notice. Upon cancellation, the refund amount would be calculated on a pro-rata basis and the same would be reverted back to the user's bank account.</li>
                <li>In case of cancellation of OnePass subscription, the refund would be transferred in the Fitternity wallet in the form of FitCash. The FitCash can be redeemed on all services available on Fitternity.</li>
                <li>In case of misbehavior by the user at the Vendor location, Fitness center or the staff, Fitternity holds the right to cancel the user's subscription without any prior notice. No refund amount would be processed in this scenario.</li>
                <li>Refund Policy is only applicable on subscriptions above 3 months(OnePass Red), subscriptions on 45 sessions & above(OnePass Black).</li>
                <li>The user will be charged a 3% convenience fee on cancellation of his/her OnePass subscription (over and above usage of subscription on pro rata basis)</li>
                <li>If the user opts for  “Cash Pick Up” option for payment of dues,on any amount of subscription of OnePass, a flat pickup fee of INR 200 will be charged additionally, over and above the subscription amount.</li>	
                <li>In case you find a fitness/sports facility which is not yet part of the OnePass network - We will work to onboard the center within 1-15 working days of your request, given the alignment of standard terms between Fitternity & the facility</li>
                <li>In case the user is found misusing any of the OnePass services, he/she will be penalized at the discretion of Fitternity.</li>
                <li>In case of any injury or accident at the Fitness Center, any claims on Fitternity would not be applicable whatsoever.</li>
                <li>In case of any dispute, the final decision made by Fitternity would be final.</li>
                <li>In case of any assistance concerning OnePass write to us at onepass@fitternity.com or call your personal concierge at +917400062849</li>
            </ul><br/><br/>",
        ]
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
        'subline'=>"Congratulations __customer_name, <br/> Your __pass_name for __pass_duration is now confirmed. We have also sent you a confirmation Email and SMS.",
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
            ],
            'app_data'=>[
                'Book your sessions through the App.',
                'Book classes at any gym/studio near you of your choice.',
                'Not loving it? easy cancellation available.',
            ]
        ],
        "conclusion" => $success_page_template['conclusion'],
        "feedback" => $success_page_template["feedback"],
        "personalize" => "PERSONALIZE YOUR ONEPASS"
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
                    "subheader" => "UNLIMITED ACCESS",
                    "desc_header" => "Limitless Workouts",
                    //"desc_subheader" => "With Expiry" 
                ],
                [
                    "image" => "https://b.fitn.in/passes/app-home/op_black_thumb.png",
                    "header1" => "ONEPASS",
                    "header1_color" => "#000000",
                    "header2" => "BLACK",
                    "header2_color" => "#000000",
                    "subheader" => "UNLIMITED VALIDITY",
                    "desc_header" => "No Expiry",
                    //"desc_subheader" => "Limited Workouts"
                ]
            ],
            "footer" => [
                "index" => 0,
                "text" => "Get 30% Off + Additional FLAT INR 500 Off",
                "button_text" => "Know More"
            ],
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
                        'contact_no' => \Config::get('app.contact_us_customer_number_onepass')
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
                        'contact_no' => \Config::get('app.contact_us_customer_number_onepass')
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
            
    ],

    "passtab" => [
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
                    "subheader" => "UNLIMITED ACCESS",
                    "desc_header" => "Limitless Workouts",
                    //"desc_subheader" => "With Expiry" 
                ],
                [
                    "image" => "https://b.fitn.in/passes/app-home/op_black_thumb.png",
                    "header1" => "ONEPASS",
                    "header1_color" => "#000000",
                    "header2" => "BLACK",
                    "header2_color" => "#000000",
                    "subheader" => "UNLIMITED VALIDITY",
                    "desc_header" => "No Expiry",
                    //"desc_subheader" => "Limited Workouts"
                ]
            ],
            "footer" => [
                "index" => 0,
                "text" => "Get 30% off on OnePass. Buy your OnePass before prices go up!",
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
                        'contact_no' => \Config::get('app.contact_us_customer_number_onepass')
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
                        'contact_no' => \Config::get('app.contact_us_customer_number_onepass')
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

    "pass_profile" =>[
        "address_details" => [
            "header" => "Let us know where you are",
            "home_subtext" => "We will use this address to send you vouchers and gifts.",
        ],
        "interests" => [
            "header" => "Tell us about your interests",
            "subheader" => "Select at least 3"
        ],
        "personlized_profile" => [
            "header" => "Explore our fitness options",
            "url" => "",
            "success_image" => "https://b.fitn.in/passes/check_circle.png",
            "text" => "personalization Completed",
            "skip_text" => "Skip for now",
            "interests" => [
                "header" => "Explore our fitness options",
                "data" => [
                    [
                        '_id'=>0,
                        'slug'=>'', 
                        'name'=> 'Explore All', 
                        'image'=> 'https://b.fitn.in/iconsv1/all_category_icon.png'
                    ]
    
                ]
            ]
        ]
    ],
    
    "before_purchase_tab" => [
        "headerview" =>[
            "header_img" =>  "https://b.fitn.in/passes/onepass-app.png", //"https://b.fitn.in/onepass/onepss_background_logo.png",
            "background_image" => "http://b.fitn.in.s3.amazonaws.com/onepass/Confetti_3x.png",
            "header_text" => "FOR EVERYTHING HEALTH AND FITNESS",
            "header_sub_text"  => "Enjoy limitless access across Fitternity's 12,000+ fitness classes, gyms and sports facilities across India",
        ],
        "passes" => [
            [
                "image" => "https://b.fitn.in/passes/app-home/op_red_thumb.png",//"https://b.fitn.in/onepass/OnePass_Red_3x.png",
                "header1" => "ONEPASS",
                "header1_color" => "#000000",
                "header2" => "RED",
                "header2_color" => "#d50000",
                "subheader" => "UNLIMITED ACCESS",
                "desc_header" => "Limitless Workouts",
                //"desc_subheader" => "With Expiry" 
            ],
            [
                "image" => "https://b.fitn.in/passes/app-home/op_black_thumb.png",//"https://b.fitn.in/onepass/OnePass_Black_3x.png",
                "header1" => "ONEPASS",
                "header1_color" => "#000000",
                "header2" => "BLACK",
                "header2_color" => "#000000",
                "subheader" => "UNLIMITED VALIDITY",
                "desc_header" => "No Expiry",
                //"desc_subheader" => "Limited Workouts"
            ]
        ],

        'why_pass' => [
            'header' => 'Why Go For OnePass?',
            'data' => [
                [
                    'header' => 'Select a fitness Centre & slot',
                    'text' => 'select a fitness centre and slot',
                    'image' => 'https://b.fitn.in/global/onepass/icon-1.png'
                ],
                [
                    'header' => 'Book & Go Workout',
                    'text' => 'Reach the fitness centre & flash your booking to unlock your session',
                    'image' => 'https://b.fitn.in/global/onepass/icon-2.png'
                ],
                [
                    'header' => 'Make The best workout regime',
                    'text' => 'Go to your favourite place multiple times or mix it up',
                    'image' => 'https://b.fitn.in/global/onepass/icon-3.png'
                ]
            ]
        ],

        "near_by" =>[
            "icon" => "https://b.fitn.in/onepass/Dumbell_3x.png",
            "header" => "Over 12,000+ fitness classes,\n gyms & sports venues",
        ],

        "offers" => [
            "icon" => "https://b.fitn.in/onepass/discount_3x.png",
            "header" => "Exciting OnePass Offers",
            "logo" => "https://b.fitn.in/onepass/OnePass_offer_Logo_3x.png",
            "text" => "Get 30% off on OnePass. Buy your OnePass before prices go up!",
        ],

        'faq' => [
            "icon" => "https://b.fitn.in/onepass/FAQ_3x.png",
            'header' => 'Frequently Asked Questions',
            'title' => 'FAQ Title',
            'url' => $apiUrl.'/passfaq'
        ],

        'tnc' => [
            'icon' => "https://b.fitn.in/onepass/Terms_And_Conditions_3x.png",
            'header' => 'Terms and Conditions',
            'title' => '',
            'url' => $apiUrl.'/passtermscondition'
        ],

        'footer' => [
            'contact_text' => 'Need Help? Contact your Personal Concierge',
            'contact_image' => 'https://b.fitn.in/passes/app-home/contact-us.png',
            'contact_no' => \Config::get('app.contact_us_customer_number_onepass')
        ]
    ],

    "after_purchase_tab" => [
        "red" => [
            "pass_image"  => "https://b.fitn.in/passes/cards/onepass-red.png",//"https://b.fitn.in/onepass/OnePass_Red_3x.png",
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
                    'contact_no' => \Config::get('app.contact_us_customer_number_onepass')
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
        ],
        "black" => [
            "pass_image"  => "https://b.fitn.in/passes/cards/onepass-black.png",//"https://b.fitn.in/onepass/OnePass_Black_3x.png",
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
                    'contact_no' => \Config::get('app.contact_us_customer_number_onepass')
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
            'pass_expired' => false
        ]
    ],

    "book_now" => [
        "header" => "Upcoming Session",
        "image" => "https://b.fitn.in/onepass/Clock_Icon.png",
        "text" => "Uh Oh! You don't have any upcoming bookings",
        "button_text" => "BOOK NOW"
    ],

    "cancel_onepass" => [
        'text' => 'Not Satisfied with Onepass',
        'button_text' => 'Cancel OnePass'
    ],

    'local_pass_fields' => [
        'why_local_pass' => [
            'header' => 'WHY GO FOR A PASS',
            'text' => 'LIMITLESS WORKOUTS, LIMITLESS CHOICES, LIMITLESS VALIDITY, LIMITLESS YOU',
            'data' => [
                'OnePass Red give you Unlimited Access',
                'OnePass Red gives you Membership Privileges',
                'Onepass Red gives you the option to workout Anytime, Anywhere'
            ]
        ],
        'local_pass_offerings' => [
            'text' => (json_decode('"'."\u2713".'"')." Limitless workouts across city_centers_count fitness classes, gyms and sports facilities across city_name. \n".json_decode('"'."\u2713".'"')." Use it like a fitness membership - choose a duration of 15 days to 1 year."),
            'button_text' => 'Checkout Gyms/Studios',
            'ratecards' => []
        ],
        'local_pass_remarks' => [
            'header' => "In addition to owning the coolest fitness membership, OnePass users get exclusive rewards, vouchers and more! \n\nGet 30% Off + Additional FLAT INR 500 Off", // need content
            'text' => 'Terms and Conditions',
            "title" =>'Terms and Conditions',
            'url' => $apiUrl.'/passtermscondition?type=unlimited'
        ]
    ],

    'city_centers_count' => [
        'pune' => 1000,
        'mumbai' => 1500,
        'delhi' => 2000,
        'faridabad' => 800,
        'bangalore' =>1200,
        'hydrabad' => 1000
    ],

];