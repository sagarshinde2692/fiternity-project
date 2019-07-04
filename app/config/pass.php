<?php
$apiUrl = \Config::get('app.url');
return [
    'list' => [
        'passes' => [
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
                    't_and_c_url' => $apiUrl.'/passtermscondition?type=subscripe'

                ]
            ],
            [
                'header' => 'ALL ACCESS PASS',
                'subheader' => 'ALL ACCESS PASS UNLIMITED USAGE',
                'text' => '1 MONTH | 3 MONTHS | 6 MONTHS',
                'image' => 'https://b.fitn.in/passes/all_access_card.png',
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
                    't_and_c_url' => $apiUrl.'/passtermscondition?type=unlimited'
                ]
            ]
        ],
        'faq' => [
            'header' => 'FAQs',
            'text' => 'sdfdfdsf sdfsd fsdf sdfs sf sf sdfs d',
            'title' => 'FAQ Title',
            'faq_url' => $apiUrl.'/passfaq'
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
        'section1'=>'Hi __customer_name, your __pass_name for __pass_duration is now active. We have also sent you a confirmation Email and SMS.',
        'section2'=>[
            'header'=>'Things to keep in mind',
            'data'=>[
                'You get sweatpoint credits to book whatever classes you want',
                'Download the app & get started',
                'Book classes at any gym/studio near you, sweatpoints vary by class',
                'Not loving it? easy cancellation available',
            ]
        ],
        'session3'=>'Please note - The sessions are bookable only of Fitternity app. Download now'
    ]

];