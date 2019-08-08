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
                        "next_count"=> 100000,
                        "enabled"=> false,
                        "progress"=> 0,
                        "description"=> "steps",
                        "amount"=> 200,
                        "claimable_coupon" => 1,
                        "users"=>100
                    ],
                    [
                        "milestone"=> 2,
                        "count"=> 100000,
                        "next_count"=> 200000,
                        "enabled"=> false,
                        "progress"=> 0,
                        "description"=> "steps",
                        "amount"=> 850,
                        "claimable_coupon" => 1,
                        "users"=>300
                    ],
                    [
                        "milestone"=> 3,
                        "count"=> 200000,
                        "next_count"=> 300000,
                        "enabled"=> false,
                        "progress"=> 0,
                        "description"=> "steps",
                        "amount"=> 1500,
                        "bookings"=> 3,
                        "booking_amount"=> 1500,
                        "claimable_coupon" => 1,
                        "users"=>100
                    ],
                    [
                        "milestone"=> 4,
                        "count"=> 300000,
                        "enabled"=> false,
                        "progress"=> 0,
                        "description"=> "steps",
                        "amount"=> 6000,
                        "bookings"=> 3,
                        "booking_amount"=> 1500,
                        "claimable_coupon" => 1,
                        "users"=>50
                    ]
                ]
            ],
            "rewards"=> [
                "header"=> "Claim exciting rewards",
                "open_index"=> 1,
                "claim_message"=> "Are you sure you want to claim this reward as you won't be able to claim other rewards for this milestone?",
                "data"=> [
                    [
                        "title"=> "Milestone 1 (First 100 Users)",
                        "description"=> "Select 1 Reward(s)",
                        "_id"=> 1,
                        "data"=> [
                            [
                                "_id" => "5d416b872632abf3dad0039c",
                                "milestone" => 1,
                                "order" => 1,
                                "price" => "200",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Jio Saavn Pro (2 month subscription)",
                                "logo" => "https://b.fitn.in/loyalty/vouchers3/saavn-logo-mobile.png",
                                "terms" => "<p>Jio Saavn</p><p>How to avail?</p><p>● On claiming the reward you will get a unique promo code from Fitternity through email & SMS.</p><p>● To avail the offer - Visit www.jiosaavn.com/redeem.</p><p>● Log-in if you are an existing user. Sign-up if you are a new user. </p><p>● E JioSaavn Pro code and PIN number and click on Redeemn ter your 16 digit</p><p>&nbsp;Terms and Conditions:</p><p>● The subscription will be active for a period of 1 month, from the date of redemption.</p><p>● Each code is unique and can only be redeemed once.</p><p>● Codes cannot be exchanged for cash, returned or resold.</p><p>● Codes are valid in India only.</p><p>● Existing JioSaavn Pro subscribers on auto-renewal plans cannot redeem the codes.</p><p>● For any difficulty in redeeming the codes, please write to support@jiosaavn.com</p>"
                            ],
                            [
                                "_id" => "5d41688b2632abf3dacfdf4a",
                                "milestone" => 1,
                                "order" => 1,
                                "price" => "200",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "BookMyShow",
                                "logo" => "https://b.fitn.in/external-vouchers1/book%20my%20show.png",
                                "terms" => "<p>&nbsp;</p><p>How to avail -<br/>&nbsp;<br/>● Log on to&nbsp;in.bookmyshow.com&nbsp;or BookMyShow Mobile App.<br/>● Select Location, Event, Venue, Show Date, Time &amp; Seats of your preference as the logic flows.<br/>● On payment page, click on the &ldquo; More Payment Options&rdquo; &amp; click on the \"Gift Voucher\" tab to enter the Gift Voucher e-code in the text box that appears &amp; click on \"Apply\" button to avail the offer.&nbsp;<br/>.</p><p>&nbsp;Terms &amp; Conditions -</p><p>● Gift Voucher can be redeemed only on BookMyShow Website/Mobile App &amp; cannot be redeemed directly at the Cinema/Venue Box Office.<br/>● Gift Voucher can be redeemed on purchase of etickets for Movies/Plays/Concerts/Sports Or Merchandise (if any) available on BookMyShow.<br/>● Multiple Gift Voucher can be used in a single transaction or Gift Voucher can be combined with any other payment type on BookMyShow.<br/>● Gift Voucher can be used multiple times until the last rupee loaded on it gets exhausted within the validity period.<br/>● Gift Voucher to be valid for 6 months.<br/>● Gift Voucher cannot be used to purchase another Gift Card.&nbsp;<br/>● Gift Voucher work on non-extension clause &amp; order once confirmed cannot be Cancelled or Refunded.<br/>● Gift Voucher are valid PAN India across all Cinemas/Partners associated with BookMyShow.<br/>● If lost misused, the Gift Voucher cannot be replaced. Void if resold, cannot be exchanged for points or cash &amp; cannot be re-validated once past expiry date.<br/>● In addition to these Terms &amp; Conditions, BookMyShow Gift Voucher &amp; their use on our Website are also subject to BookMyShow's General Terms of Use &amp; BookMyShow's decision will be final in case of any dispute.<br/>● BookMyShow Terms &amp; Conditions Apply.</p>"
                            ],
                        ]
                    ],
                    [
                        "title"=> "Milestone 2 (First 300 Users)",
                        "description"=> "Select 1 Reward(s)",
                        "_id"=> 2,
                        "data"=> [
                            
                            [
                                "_id" => "5d416c712632abf3dad00de0",
                                "milestone" => 2,
                                "order" => 1,
                                "price" => "850",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Fitternity Merchandise - Gym bag",
                                "logo" => "https://b.fitn.in/external-vouchers/pharmeasy---logo---website.jpg",
                                "terms" => "<p>Pharmeasy</p><p>Get your medicines delivered at your doorstep</p><p>Offer: Flat 30% off</p><p>How to avail?</p><p>● On claiming the reward you will receive a confirmation email and sms with the code</p><p>● To avail the offer - Open the Pharmeasy App</p><p>● Buy your medicines on Pharmeasy App and apply the promocode when your making the payment</p><p>● Get your medicine at your doorstep.</p><p>&nbsp;Terms and Conditions:</p><p>● Applicable only once per user.</p><p>● Applicable only for new users.</p><p>● Is valid on prescription medicines only</p><p>● Cancelled orders will not be eligible for this offer.</p><p>● PharmEasy reserves the right to withdraw and/or alter any terms &amp; conditions of this offer without prior notice.</p><p>● In the event of any dispute, the decision of PharmEasy is final. Customer care no in case of queries: 07666-100-300</p><p>● Discounts &amp; offers are given by PharmEasy partner retailers</p>"
                            ]
                        ]
                    ],
                    [
                        "title"=> "Milestone 3 (First 100 Users)",
                        "description"=> "Select 1 Reward(s)",
                        "_id"=> 3,
                        "data"=> [
                            [ 
                                "_id" => "5bc0bc1745c2aa2dc0ae483a",
                                "milestone" => 3,
                                "order" => 1,
                                "price" => "1000",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Get 1 Session online diet consultation and support worth Rs. 500. and Puma Voucher worth of Rs. 500",
                                "terms" => "<p><strong>Diet Consultation</strong></p><p>&nbsp;How to avail</p><ul><li>Please check your email &amp; sms to confirm your diet consultation</li><li>To book an appointment call&nbsp;88798-84168</li><li>Have a great diet session</li></ul><p>Terms and Conditions:</p><p>&nbsp;</p><ul><li>The session can be booked on Fittenity website or by calling 8879884168</li><li>Once the session has been booked, you will not be allowed to reschedule the session more than once</li><li>Once you have booked the session then you cannot refund or transfer the session to someone else</li></ul><p><br/><br/></p>",
                                "logo" => "https://b.fitn.in/loyalty/vouchers3/diet%20consultation.png"
                            ],
                            [ 
                                "_id" => "5bc0bc1745c2aa2dc0ae483a",
                                "milestone" => 3,
                                "order" => 3,
                                "price" => "1500",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Fitternity FitCash",
                                "terms" => "<p><strong>Fitness Kit</strong></p><p>&nbsp;</p><p><span style=\"font-weight=> 400;\">How to Avail?</span></p><ul><li style=\"font-weight=> 400;\"><span style=\"font-weight=> 400;\">On claiming the reward you will have to provide your address.</span></li><li style=\"font-weight=> 400;\"><span style=\"font-weight=> 400;\">It will take 7 - 10 working days for us to deliver the kit to you.</span></li></ul><p><br/><br/></p><p dir=\"ltr\">Terms and Conditions</p><ul><li dir=\"ltr\"><p dir=\"ltr\">Products will take 7-10 days to get delivered</p></li><li dir=\"ltr\"><p dir=\"ltr\">Once the reward has been claimed it cannot be exchanged/ transferred or returned.</p></li><li dir=\"ltr\"><p dir=\"ltr\">Ensure to put the address where you want the products delivered.</p></li><li dir=\"ltr\"><p dir=\"ltr\">Products once delivered cannot be returned unless they are deliverd damaged.</p></li></ul>",
                                "logo" => "https://b.fitn.in/loyalty/vouchers3/FITNESS%20GEAR.png"
                            ]
                        ]
                    ],
                    [
                        "title"=> "Milestone 4 (First 50 Users)",
                        "description"=> "Select 1 Reward(s)",
                        "_id"=> 4,
                        "data"=> [
                            [ 
                                "_id" => "5d416d8c2632abf3dad01a70",
                                "milestone" => 4,
                                "order" => 1,
                                "price" => "6000",
                                "price_header"=> "Worth",
                                "claim_enabled"=> false,
                                // "button_title"=> "Claim",
                                "coupon_description" => "Actofit Smart band",
                                "terms" => "<p>Actofit</p><p>How to avail?</p><p>● Post claiming you will have to enter the delivery address into your Fitternity profile.</p><p>● The Actofit Smart band along with a subscription code to avail the complimentary Actofit subscription will be provided.</p><p>● Follow the step mentioned on the box to avail the complimentary Actofit Subscription.</p><p>● Enjoy your Actofit Smart band and Subscription.</p><p>&nbsp;Terms and Conditions:</p><p>● Products will take 7-10 days to get delivered.</p><p>● Ensure to put the delivery address into your Fitternity profile.</p><p>● Products once delivered cannot be returned or exchanged unless they are damaged.</p>",
                                "logo" => "https://b.fitn.in/loyalty/goqii---logo-mobile.jpg"
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
    