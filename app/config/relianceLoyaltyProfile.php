<?php
   return array(
    "post_register"=> [
            "header"=> [
                "logo"=> "https://b.fitn.in/reliance/reward_icon_mobile.png",
                "text"=> 'Hi <b> customer_name </b>,<br/><br/>total_steps/next_count steps completed.<br/><br/>milestone_text',
                "background_image"=> "https://b.fitn.in/loyalty/banner.jpg",
                "ratio"=> 0.36
            ],
            "milestones"=> [
                "header"=> "Your Steps Journey",
                "subheader"=> 'You are remaining_steps steps away from milestone next_milestone.',
                'footer' => 'Start date: start_date',
                // "description"=> "Start working out and level up on your streak.\n Achieve milestones and earn crazy rewards",
                "data"=> [
                    [
                        "milestone"=> 0,
                        "count"=> 0,
                        "next_count"=> 50000,
                        "enabled"=> true,
                        "progress"=> 100,
                        "description"=> "steps",
                        "amount"=> 0,
                        "users"=>-1
                    ],
                    [
                        "milestone"=> 1,
                        "count"=> 50000,
                        "next_count"=> 200000,
                        "enabled"=> false,
                        "progress"=> 0,
                        "description"=> "steps",
                        "amount"=> 300,
                        "claimable_coupon" => 1,
                        "users"=>400
                    ],
                    [
                        "milestone"=> 2,
                        "count"=> 200000,
                        "next_count"=> 400000,
                        "enabled"=> false,
                        "progress"=> 0,
                        "description"=> "steps",
                        "amount"=> 500,
                        "claimable_coupon" => 1,
                        "users"=>100
                    ],
                    [
                        "milestone"=> 3,
                        "count"=> 400000,
                        "next_count"=> 500000,
                        "enabled"=> false,
                        "progress"=> 0,
                        "description"=> "steps",
                        "amount"=> 1500,
                        "bookings"=> 3,
                        "booking_amount"=> 1500,
                        "claimable_coupon" => 1,
                        "users"=>50
                    ],
                    [
                        "milestone"=> 4,
                        "count"=> 500000,
                        "enabled"=> false,
                        "progress"=> 0,
                        "description"=> "steps",
                        "amount"=> 1500,
                        "bookings"=> 3,
                        "booking_amount"=> 1500,
                        "claimable_coupon" => 1,
                        "users"=>1
                    ]
                ]
            ],
            "rewards"=> [
                "header"=> "Claim exciting rewards",
                "open_index"=> 1,
                "claim_message"=> "Are you sure you want to claim this reward as you won't be able to claim other rewards for this milestone?",
                "data"=> [
                    [
                        "title"=> "Milestone 1 (First 400 Users)",
                        "description"=> "Select 1 Reward(s)",
                        "_id"=> 1,
                        "data"=> [
                            [
                                "_id" => "5c38b674b5498644aacc72c3",
                                "milestone" => 1,
                                "order" => 1,
                                "price" => "300",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Jio Saavn Pro (1 month subscription)",
                                "logo" => "https://b.fitn.in/loyalty/vouchers3/saavn-logo-mobile.png",
                                "terms" => "<p>Jio Saavn</p><p>How to avail?</p><p>● On claiming the reward you will get a unique promo code from Fitternity through email & SMS.</p><p>● To avail the offer - Visit www.jiosaavn.com/redeem.</p><p>● Log-in if you are an existing user. Sign-up if you are a new user. </p><p>● E JioSaavn Pro code and PIN number and click on Redeemn ter your 16 digit</p><p>&nbsp;Terms and Conditions:</p><p>● The subscription will be active for a period of 1 month, from the date of redemption.</p><p>● Each code is unique and can only be redeemed once.</p><p>● Codes cannot be exchanged for cash, returned or resold.</p><p>● Codes are valid in India only.</p><p>● Existing JioSaavn Pro subscribers on auto-renewal plans cannot redeem the codes.</p><p>● For any difficulty in redeeming the codes, please write to support@jiosaavn.com</p>"
                            ],
                            [
                                "_id" => "5c62709728e0355b66c7266a",
                                "milestone" => 1,
                                "order" => 1,
                                "price" => "300",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Uber Eats",
                                "logo" => "https://b.fitn.in/external-vouchers1/UberEats-Logo-OnWhite-Color-V.png",
                                "terms" => "<div><p><strong class=\"m_8458346447262812218gmail-m_4032559922928812213gmail-m_-7600817027860335562gmail-m_-7184242573835891437gmail-black\">HOW TO AVAIL=></strong>&nbsp;</p><ul><li>On claiming the reward you will get a unique promocode from Fitternity through email &amp; SMS</li><li>Download the Uber Eats app here=>&nbsp;<a class=\"m_8458346447262812218gmail-m_4032559922928812213gmail-m_-7600817027860335562gmail-m_-7184242573835891437gmail-Xx\" dir=\"ltr\" href=\"https=>//www.google.com/url?q=http=>//t.uber.com/fitternity&amp;sa=D&amp;source=hangouts&amp;ust=1549441575825000&amp;usg=AFQjCNHIfDTMayzg68F68Q5F_SWpGzUyRQ\" target=\"_blank\" rel=\"nofollow noreferrer noopener\" data-saferedirecturl=\"https=>//www.google.com/url?q=https=>//www.google.com/url?q%3Dhttp=>//t.uber.com/fitternity%26sa%3DD%26source%3Dhangouts%26ust%3D1549441575825000%26usg%3DAFQjCNHIfDTMayzg68F68Q5F_SWpGzUyRQ&amp;source=gmail&amp;ust=1550039979443000&amp;usg=AFQjCNF9z92b7JbJ_aWCfevqcx0UVdBZUQ\">http=>//t.uber.com/<wbr/>fitternity</a></li><li>Order food your favorite restaurant and apply the promocode when you make a payment</li><li>Get Food Delivered at your doorstep</li></ul>Terms &amp; Conditions</div><div><div><ul><li>Valid on 3 orders</li><li>Max Discount=> Rs 100/- per order&nbsp;</li><li>Min. order value Rs 200/-&nbsp;</li><li><span id=\"m_8458346447262812218gmail-docs-internal-guid-7bff15d4-7fff-97af-e36c-afca385110f2\">Uber Eats offer cannot be clubbed with any other offer or deal.</span>&nbsp;&nbsp;</li><li>Valid for 30 days after applying promo code.</li><li>Valid till 5 May 2019.</li></ul></div></div>" 
                            ],
                        ]
                    ],
                    [
                        "title"=> "Milestone 2 (First 100 Users)",
                        "description"=> "Select 1 Reward(s)",
                        "_id"=> 2,
                        "data"=> [
                            
                            [
                                "_id" => "5c38b674b5498644aacc72c9",
                                "milestone" => 2,
                                "order" => 1,
                                "price" => "800",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "PharmEasy",
                                "logo" => "https://b.fitn.in/external-vouchers/pharmeasy---logo---website.jpg",
                                "terms" => "<p>Pharmeasy</p><p>Get your medicines delivered at your doorstep</p><p>Offer: Flat 30% off</p><p>How to avail?</p><p>● On claiming the reward you will receive a confirmation email and sms with the code</p><p>● To avail the offer - Open the Pharmeasy App</p><p>● Buy your medicines on Pharmeasy App and apply the promocode when your making the payment</p><p>● Get your medicine at your doorstep.</p><p>&nbsp;Terms and Conditions:</p><p>● Applicable only once per user.</p><p>● Applicable only for new users.</p><p>● Is valid on prescription medicines only</p><p>● Cancelled orders will not be eligible for this offer.</p><p>● PharmEasy reserves the right to withdraw and/or alter any terms &amp; conditions of this offer without prior notice.</p><p>● In the event of any dispute, the decision of PharmEasy is final. Customer care no in case of queries: 07666-100-300</p><p>● Discounts &amp; offers are given by PharmEasy partner retailers</p>"
                            ],
                            [  
                                "_id" => "5bc0bc1745c2aa2dc0ae483c",
                                "milestone" => 2,
                                "order" => 3,
                                "price" => "800",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Healthifyme Smart Diet Plan",
                                "terms" => "<p><strong>Diet Consultation</strong></p><p>&nbsp;How to avail</p><ul><li>Please check your email &amp; sms to confirm your diet consultation</li><li>To book an appointment call&nbsp;88798-84168</li><li>Have a great diet session</li></ul><p>Terms and Conditions=></p><p>&nbsp;</p><ul><li>The session can be booked on Fitternity website or by calling 8879884168</li><li>Once the session has been booked, you will not be allowed to reschedule the session more than once</li><li>Once you have booked the session then you cannot refund or transfer the session to someone else</li></ul><p><br/><br/></p>",
                                "logo" => "https://b.fitn.in/loyalty/vouchers3/diet%20consultation.png"                          
                            ],
                            [
                                "_id" => "5c62709728e0355b66c7266a",
                                "milestone" => 2,
                                "order" => 1,
                                "price" => "800",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Fitternity FitCash",
                                "logo" => "https://b.fitn.in/loyalty/vouchers3/fitcash.png",
                                "terms" => "<p>FitCash</p><p>FitCash as the name suggests is Fitternity&rsquo;s Currency. You can use FitCash across 10,000 +<br />gyms and fitness studios to buy memberships, pay-per-session, diet consultation, etc.<br />How to Avail?<br />● On claiming the reward your FitCash will be directly be deposited into your Fitternity<br />Wallet<br />Terms and Conditions<br />● Once the Fitcash has been used/redeemed for any transaction on Fitternity, it cannot<br />be refunded<br />● FitCash cannot be transferred to someone else.<br />● Fitcash can be redeemed for any transactions on Fitternity<br />● Fitcash can be used/redeemed within 12 months.</p>" 
                            ],
                        ]
                    ],
                    [
                        "title"=> "Milestone 3 (First 50 Users)",
                        "description"=> "Select 1 Reward(s)",
                        "_id"=> 3,
                        "data"=> [
                            [ 
                                "_id" => "5bc0bc1745c2aa2dc0ae483a",
                                "milestone" => 3,
                                "order" => 1,
                                "price" => "5000",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Goqii / Actofit Smart band",
                                "terms" => "<p>Goqii</p><p>How to avail?</p><p>● Post claiming you will have to enter the delivery address into your Fitternity profile.</p><p>● The Goqii Vital Band along with a subscription code to avail the complimentary Goqii subscription will be provided.</p><p>● Follow the step mentioned on the box to avail the complimentary Goqii Subscription.</p><p>● Enjoy your Goqii Vital Band and Subscription.</p><p>&nbsp;Terms and Conditions:</p><p>● Products will take 7-10 days to get delivered.</p><p>● Ensure to put the delivery address into your Fitternity profile.</p><p>● Products once delivered cannot be returned or exchanged unless they are damaged.</p>",
                                "logo" => "https://b.fitn.in/loyalty/goqii---logo-mobile.jpg"
                            ],
                            [ 
                                "_id" => "5bc0bc1745c2aa2dc0ae483a",
                                "milestone" => 3,
                                "order" => 1,
                                "price" => "5000",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Fitternity Merchandise (T-shirt & gym bag)",
                                "terms" => "<p><strong>Fitness Kit</strong></p><p>&nbsp;</p><p><span style=\"font-weight=> 400;\">How to Avail?</span></p><ul><li style=\"font-weight=> 400;\"><span style=\"font-weight=> 400;\">On claiming the reward you will have to provide your address.</span></li><li style=\"font-weight=> 400;\"><span style=\"font-weight=> 400;\">It will take 7 - 10 working days for us to deliver the kit to you.</span></li></ul><p><br/><br/></p><p dir=\"ltr\">Terms and Conditions</p><ul><li dir=\"ltr\"><p dir=\"ltr\">Products will take 7-10 days to get delivered</p></li><li dir=\"ltr\"><p dir=\"ltr\">Once the reward has been claimed it cannot be exchanged/ transferred or returned.</p></li><li dir=\"ltr\"><p dir=\"ltr\">Ensure to put the address where you want the products delivered.</p></li><li dir=\"ltr\"><p dir=\"ltr\">Products once delivered cannot be returned unless they are deliverd damaged.</p></li></ul>",
                                "logo" => "https://b.fitn.in/loyalty/vouchers3/FITNESS%20GEAR.png"
                            ]
                        ]
                    ],
                    [
                        "title"=> "Milestone 4 (First User)",
                        "description"=> "Select 1 Reward(s)",
                        "_id"=> 3,
                        "data"=> [
                            [ 
                                "_id" => "5bc0bc1745c2aa2dc0ae483a",
                                "milestone" => 3,
                                "order" => 1,
                                // "price" => "1500",
                                // "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "An opportunity to catch up with a top celebrity ",
                                // "terms" => "<p><strong>Fitness Kit</strong></p><p>&nbsp;</p><p><span style=\"font-weight=> 400;\">How to Avail?</span></p><ul><li style=\"font-weight=> 400;\"><span style=\"font-weight=> 400;\">On claiming the reward you will have to provide your address.</span></li><li style=\"font-weight=> 400;\"><span style=\"font-weight=> 400;\">It will take 7 - 10 working days for us to deliver the kit to you.</span></li></ul><p><br/><br/></p><p dir=\"ltr\">Terms and Conditions</p><ul><li dir=\"ltr\"><p dir=\"ltr\">Products will take 7-10 days to get delivered</p></li><li dir=\"ltr\"><p dir=\"ltr\">Once the reward has been claimed it cannot be exchanged/ transferred or returned.</p></li><li dir=\"ltr\"><p dir=\"ltr\">Ensure to put the address where you want the products delivered.</p></li><li dir=\"ltr\"><p dir=\"ltr\">Products once delivered cannot be returned unless they are deliverd damaged.</p></li></ul>",
                                "logo" => "https://b.fitn.in/external-vouchers/istockphoto-476085198-612x612.jpg"
                            ]
                        ]
                    ]
                ]
            ],
            "Contact"=> [
                "title"=> "Want further Assistance? Call us",
                "ph_no"=> "+912261094444"
            ]
        ]
    )

?>
    