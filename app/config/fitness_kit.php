<?php

$url = 'https://b.fitn.in/gamification/reward_new/';

$products = [
	'Workout Friendly Armband'=>[
		"title"=>'Workout Friendly Armband',
		'url'=>$url.'productskit/armband.png'
	],
	'Cool-Water Bottle'=>[
		"title"=>'Cool-Water Bottle',
		'url'=>$url.'productskit/bottle.png'
	],
	'Earphone Detangler'=>[
		"title"=>'Earphone Detangler',
		'url'=>$url.'productskit/detangler.png'
	],
	'Waterproof Gym Bag'=>[
		"title"=>'Waterproof Gym Bag',
		'url'=>$url.'productskit/gymbag.png'
	],
	'Shaker'=>[
		"title"=>'Shaker',
		'url'=>$url.'productskit/shaker.png'
	],
	'Drawstring Shoe Bag'=>[
		"title"=>'Drawstring Shoe Bag',
		'url'=>$url.'productskit/shoebag.png'
	],
	'Casual Tote Bag'=>[
		"title"=>'Casual Tote Bag',
		'url'=>$url.'productskit/totebag.png'
	],
	'Compact Hand Towel'=>[
		"title"=>'Compact Hand Towel',
		'url'=>$url.'productskit/towel.png'
	],
	'Tshirt Yoga Zumba'=>[
		"title"=>'Breather T-Shirt',
		'url'=>$url.'productskit/tshirtyzm.png'
	],
	'Tshirt Crossfit'=>[
		"title"=>'Breather T-Shirt',
		'url'=>$url.'productskit/tshirtcfm.png'
	],
	'Yoga Mat'=>[
		"title"=>'Yoga Mat',
		'url'=>$url.'productskit/yogamat.png'
	],
	'Yoga Mat Bag'=>[
		"title"=>'Yoga Mat Bag',
		'url'=>$url.'productskit/yogamatbag.png'
	],
	'Breather T-Shirt'=>[
		"title"=>'Breather T-Shirt',
		'url'=>$url.'productskit/tshirtcfm.png'
	]	
];

return [

	'fitness_kit' => [
		[
			'min'=>2000,
			'max'=>5000,
			'content'=>[
				[
					"product" => [
						$products["Drawstring Shoe Bag"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>450,
					'image' => $url.'zumbakit/zumbaa.1.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbaa.1.jpg',
                     	$url.'zumbakit/s1_a_shoebag_1.png',
						$url.'zumbakit/s1_a_armband_2.png',
                    ]
				],
				[
					"product" => [
						$products["Cool-Water Bottle"]["title"],
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>400,
					'image' => $url.'gymkit/gyma.1.png',
                    'gallery'=>[]
				],
				[
					"product" => [
						$products["Drawstring Shoe Bag"]["title"],
						$products["Earphone Detangler"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>400,
					'image' => $url.'yogakit/yogaa.1.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogaa.1.jpg',
                     	$url.'yogakit/s1_a_shoebag_1.png',
						$url.'yogakit/s1_a_detangler_2.png',
                    ]
				]
			]
		],
		[
			'min'=>5000,
			'max'=>7500,
			'content'=>[
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>550,
					'image' => $url.'zumbakit/zumbaa.2.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbaa.2.jpg',
                     	$url.'zumbakit/s2_a_totebagg_1.png',
						$url.'zumbakit/s2_a_armband_2.png',
                    ]
				],
				[
					"product" => [
						$products["Cool-Water Bottle"]["title"],
						$products["Earphone Detangler"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>650,
					'image' => $url.'gymkit/gyma.2.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gyma.2.jpg',
                     	$url.'gymkit/s2_a_bottle_1.png',
						$url.'gymkit/s2_a_detangler_2.png',
                    ]
				],
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Cool-Water Bottle"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>650,
					'image' => $url.'yogakit/yogaa.2.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogaa.2.jpg',
                     	$url.'yogakit/s2_a_totebag_1.png',
						$url.'yogakit/s2_a_bottle_2.png',
                    ]
				]
			]
		],
		[
			'min'=>7500,
			'max'=>10000,
			'content'=>[
				[
					"product" => [
						$products["Drawstring Shoe Bag"]["title"],
						$products["Cool-Water Bottle"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>550,
					'image' => $url.'zumbakit/zumbaa.3.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbaa.3.jpg',
                     	$url.'zumbakit/s1_a_shoebag_1.png',
						$url.'zumbakit/s3_a_bottle_2.png'
                    ]
				],
				[
					"product" => [
						$products["Waterproof Gym Bag"]["title"],
						$products["Shaker"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>950,
					'image' => $url.'gymkit/gyma.3.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gyma.3.jpg',
						$url.'gymkit/s3_a_shaker_1.png',
                     	$url.'gymkit/s3_a_gymbag_2.png',
                    ]
				],
				[
					"product" => [
						$products["Drawstring Shoe Bag"]["title"],
						$products["Compact Hand Towel"]["title"],
						$products["Cool-Water Bottle"]["title"],
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>900,
					'image' => $url.'yogakit/yogaa.3.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogaa.3.jpg',
                     	$url.'yogakit/s3_a_shoebag_1.png',
						$url.'yogakit/s3_a_towel_2.png',
						$url.'yogakit/s3_a_bottle_3.png',

                    ]
				]
			]
		],
		[
			'min'=>10000,
			'max'=>15000,
			'content'=>[
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Cool-Water Bottle"]["title"],
						$products["Compact Hand Towel"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>1000,
					'image' => $url.'zumbakit/zumbaa.4.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbaa.4.jpg',
                     	$url.'zumbakit/s4_a_totebagr_1.png',
						$url.'zumbakit/s4_a_bottle_2.png',
						$url.'zumbakit/s5_a_towel_3.png',
                    ]
				],
				[
					"product" => [
						$products["Waterproof Gym Bag"]["title"],
						$products["Shaker"]["title"],
						$products["Compact Hand Towel"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>1300,
					'image' => $url.'gymkit/gyma.4.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gyma.4.jpg',
                     	$url.'gymkit/s4_a_shaker_1.png',
						$url.'gymkit/s4_a_gymbag_2.png',
						$url.'gymkit/s6_b_towel_3.png',
                    ]
				],
				[
					"product" => [
						$products["Yoga Mat"]["title"],
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>800,
					'image' => $url.'yogakit/yogaa.4.png',
                    'gallery'=>[]
				]
			]
		],
		[
			'min'=>15000,
			'max'=>20000,
			'content'=>[
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Cool-Water Bottle"]["title"],
						$products["Compact Hand Towel"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>1300,
					'image' => $url.'zumbakit/zumbaa.5.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbaa.5.jpg',
                     	$url.'zumbakit/s5_a_totebagr_1.png',
						$url.'zumbakit/s5_a_bottle_2.png',
						$url.'zumbakit/s5_a_towel_3.png',
						$url.'zumbakit/s5_a_armband_4.png',
                    ]
				],
				[
					"product" => [
						$products["Waterproof Gym Bag"]["title"],
						$products["Shaker"]["title"],
						$products["Tshirt Crossfit"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>1500,
					'image' => $url.'gymkit/gyma.5.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gyma.5.jpg',
						$url.'gymkit/s5_a_shaker_1.png',
                     	$url.'gymkit/s5_a_gymbag_2.png',
						$url.'gymkit/s5_a_tshirt_lll_3.png',
                    ]
				],
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Tshirt Yoga Zumba"]["title"],
						$products["Cool-Water Bottle"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>1200,
					'image' => $url.'yogakit/yogaa.5.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogaa.5.jpg',
						$url.'yogakit/s5_a_totebag_g_1.png',
						$url.'yogakit/s5_a_tshirtf_2.jpg',
						$url.'yogakit/s5_a_bottle_3.png',
                    ]
				]
			]
		],
		[
			'min'=>20000,
			'max'=>25000,
			'content'=>[
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Cool-Water Bottle"]["title"],
						$products["Compact Hand Towel"]["title"],
						$products["Workout Friendly Armband"]["title"],
						$products["Earphone Detangler"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>1550,
					'image' => $url.'zumbakit/zumbaa.6.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbaa.6.jpg',
                     	$url.'zumbakit/s6_a_totebag_1.png',
						$url.'zumbakit/s6_a_bottle_2.png',
						$url.'zumbakit/s5_a_towel_3.png',
						$url.'zumbakit/s6_a_armband_4.png',
						$url.'zumbakit/s6_a_detangler_5.png',
                    ]
				],	
				[
					"product" => [
						$products["Shaker"]["title"],
						$products["Waterproof Gym Bag"]["title"],
						$products["Tshirt Crossfit"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>1800,
					'image' => $url.'gymkit/gyma.6.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gyma.6.jpg',
                     	$url.'gymkit/s6_a_shaker_1.png',
						$url.'gymkit/s6_a_gymbag_2.png',
						$url.'gymkit/s6_a_tshirtlll_3.png',
                     	$url.'gymkit/s6_a_armband_4.png',
                    ]
				],
				[
					"product" => [
						$products["Yoga Mat"]["title"],
						$products["Tshirt Yoga Zumba"]["title"],
						$products["Cool-Water Bottle"]["title"],
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>1750,
					'image' => $url.'yogakit/yogaa.6.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogaa.6.jpg',
						$url.'yogakit/s6_a_yogamat_1.png',
						$url.'yogakit/s6_a_tshirtm_2.jpg',
						$url.'yogakit/s6_a_bottle_3.png',
                    ]
				]
			]
		],
		[
			'min'=>25000,
			'max'=>0,
			'content'=>[
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Cool-Water Bottle"]["title"],
						$products["Compact Hand Towel"]["title"],
						$products["Workout Friendly Armband"]["title"],
						$products["Tshirt Yoga Zumba"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>1850,
					'image' => $url.'zumbakit/zumba.7.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumba.7.jpg',
                     	$url.'zumbakit/s7_totebagr_1.png',
						$url.'zumbakit/s7_bottle_2.png',
						$url.'zumbakit/s7_towel_3.png',
						$url.'zumbakit/s7_armband_4.png',
						$url.'zumbakit/s7_tshirtf_5.jpg',
                    ]
				],
				[
					"product" => [
						$products["Tshirt Crossfit"]["title"],
						$products["Compact Hand Towel"]["title"],
						$products["Waterproof Gym Bag"]["title"],
						$products["Earphone Detangler"]["title"],
						$products["Shaker"]["title"],
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>2100,
					'image' => $url.'gymkit/gym7.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gym7.jpg',
						$url.'gymkit/s7_tshirtlll_1.png',
						$url.'gymkit/s7_towel_2.png',
						$url.'gymkit/s7_shaker_3.png',
						$url.'gymkit/s7_gymbag_4.png',
                     	$url.'gymkit/s7_detangler_5.png',
                    ]
				],
				[
					"product" => [
						$products["Yoga Mat"]["title"],
						$products["Yoga Mat Bag"]["title"],
						$products["Cool-Water Bottle"]["title"],
						$products["Tshirt Yoga Zumba"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>2250,
					'image' => $url.'yogakit/yoga7.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yoga7.jpg',
                     	$url.'yogakit/s7_tshirtm_1.jpg',
						$url.'yogakit/s7_yogamat_2.png',
						$url.'yogakit/s7_yogamatbag_3.png',
						$url.'yogakit/s7_bottle_4.png',
                    ]
				]
			]
		],
	],


	'fitness_kit_2' => [
		[
			'min'=>2000,
			'max'=>5000,
			'content'=>[
				[
					"product" => [
						$products["Cool-Water Bottle"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>400,
					'image' => $url.'zumbakit/zumbab.1.png',
                    'gallery'=>[]
				],
				[
					"product" => [
						$products["Shaker"]["title"],
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>300,
					'image' => $url.'gymkit/gymb.1.png',
                    'gallery'=>[]
				],
				[
					"product" => [
						$products["Cool-Water Bottle"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>400,
					'image' => $url.'yogakit/yogab.1.png',
                    'gallery'=>[]
				]
			]
		],
		[
			'min'=>5000,
			'max'=>7500,
			'content'=>[
				[
					"product" => [
						$products["Drawstring Shoe Bag"]["title"],
						$products["Compact Hand Towel"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>500,
					'image' => $url.'zumbakit/zumbab.2.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbab.2.jpg',
                     	$url.'zumbakit/s2_b_shoebag_1.png',
						$url.'zumbakit/s2_b_towel_2.png',
                    ]
				],
				[
					"product" => [
						$products["Shaker"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>600,
					'image' => $url.'gymkit/gymb.2.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gymb.2.jpg',
                     	$url.'gymkit/s2_b_shaker_1.png',
						$url.'gymkit/s2_b_armband_2.png',
                    ]
				],
				[
					"product" => [
						$products["Yoga Mat Bag"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>500,
					'image' => $url.'yogakit/yogab.2.png',
                    'gallery'=>[]
				]
			]
		],
		[
			'min'=>7500,
			'max'=>10000,
			'content'=>[
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>550,
					'image' => $url.'zumbakit/zumbab.3.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbab.3.jpg',
                     	$url.'zumbakit/s3_b_totebagg_1.png',
						$url.'zumbakit/s3_b_armband_2.png',
                    ]
				],
				[
					"product" => [
						$products["Cool-Water Bottle"]["title"],
						$products["Tshirt Crossfit"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>950,
					'image' => $url.'gymkit/gymb.3.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gymb.3.jpg',
                     	$url.'gymkit/s3_b_bottle_1.png',
						$url.'gymkit/s3_b_tshirt_u_2.png',
                    ]
				],
				[
					"product" => [
						$products["Yoga Mat Bag"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>800,
					'image' => $url.'yogakit/yogab.3.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogab.3.jpg',
                     	$url.'yogakit/s3_b_arm%20Band_1.png',
						$url.'yogakit/s3_b_yogamatbag_2.png',
                    ]
				]
			]
		],
		[
			'min'=>10000,
			'max'=>15000,
			'content'=>[
				[
					"product" => [
						$products["Drawstring Shoe Bag"]["title"],
						$products["Tshirt Yoga Zumba"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>700,
					'image' => $url.'zumbakit/zumbab.4.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbab.4.jpg',
                     	$url.'zumbakit/s4_b_shoebag_1.png',
						$url.'zumbakit/s4_b_tshirtf_2.jpg',
                    ]
				],
				[
					"product" => [
						$products["Waterproof Gym Bag"]["title"],
						$products["Tshirt Crossfit"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>1200,
					'image' => $url.'gymkit/gymb.4.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gymb.4.jpg',
                     	$url.'gymkit/s4_b_gymbag_1.png',
						$url.'gymkit/s4_b_tshirtlll_2.png',
                    ]
				],
				[
					"product" => [
						$products["Casual Tote Bag"]["title"],
						$products["Compact Hand Towel"]["title"],
						$products["Cool-Water Bottle"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>1000,
					'image' => $url.'yogakit/yogab.4.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogab.4.jpg',
                    	$url.'yogakit/s4_b_totebag_r_1.png',
						$url.'yogakit/s4_b_bottle_2.png',
						$url.'yogakit/s4_b_towel_3.png',
                     	   
                    ]
				]
			]
		],
		[
			'min'=>15000,
			'max'=>20000,
			'content'=>[
				[
					"product" => [
						$products["Drawstring Shoe Bag"]["title"],
						$products["Tshirt Yoga Zumba"]["title"],
						$products["Earphone Detangler"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>950,
					'image' => $url.'zumbakit/zumbab.5.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbab.5.jpg',
                     	$url.'zumbakit/s5_b_shoebag_1.png',
						$url.'zumbakit/s5_b_tshirtf_2.jpg',
						$url.'zumbakit/s5_b_detangler_3.png',
                    ]
				],
				[
					"product" => [
						$products["Shaker"]["title"],
						$products["Waterproof Gym Bag"]["title"],
						$products["Compact Hand Towel"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>1450,
					'image' => $url.'gymkit/gymb.5.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gymb.5.jpg',
                     	$url.'gymkit/s4_a_shaker_1.png',
						$url.'gymkit/s6_b_gymbag_2.png',
						$url.'gymkit/s6_b_towel_3.png',
						$url.'gymkit/s6_b_armband_4.png',
                    ]
				],
				[
					"product" => [
						$products["Yoga Mat"]["title"],
						$products["Yoga Mat Bag"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>1300,
					'image' => $url.'yogakit/yogab.5.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogab.5.jpg',
                     	$url.'yogakit/s5_b_yogamat_1.png',
						$url.'yogakit/s5_b_yogamatbag_2.png',
                    ]
				]
			]
		],
		[
			'min'=>20000,
			'max'=>25000,
			'content'=>[
				[
					"product" => [
						$products["Drawstring Shoe Bag"]["title"],
						$products["Tshirt Yoga Zumba"]["title"],
						$products["Compact Hand Towel"]["title"]
					],
					'category_id'=>[
						19,
						2,
						114,
						123
					],
					'amount'=>1050,
					'image' => $url.'zumbakit/zumbab.6.jpg',
                    'gallery'=>[
                    	$url.'zumbakit/zumbab.6.jpg',
                    	$url.'zumbakit/s6_b_shoebag_1.png',
						$url.'zumbakit/s6_b_tshirtm_2.jpg',
						$url.'zumbakit/s6_a_towel_3.png',
                    ]
				],
				[
					"product" => [
						$products["Cool-Water Bottle"]["title"],
						$products["Waterproof Gym Bag"]["title"],
						$products["Compact Hand Towel"]["title"],
						$products["Workout Friendly Armband"]["title"]
					],
					'category_id'=>[
						111,
						65,
						5,
						3
					],
					'amount'=>1700,
					'image' => $url.'gymkit/gymb.6.jpg',
                    'gallery'=>[
                    	$url.'gymkit/gymb.6.jpg',
                 	   	$url.'gymkit/s6_b_bottle_1.png',
						$url.'gymkit/s6_b_gymbag_2.png',
						$url.'gymkit/s6_b_towel_3.png',
						$url.'gymkit/s6_b_armband_4.png',
                    ]
				],
				[
					"product" => [
						$products["Yoga Mat"]["title"],
						$products["Yoga Mat Bag"]["title"],
						$products["Compact Hand Towel"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>1650,
					'image' => $url.'yogakit/yogab.6.jpg',
                    'gallery'=>[
                    	$url.'yogakit/yogab.6.jpg',
                     	$url.'yogakit/s6_b_yogamat_1.png',
						$url.'yogakit/s6_b_yogamatbag_2.png',
						$url.'yogakit/s6_b_towel_3.png',
                    ]
				]
			]
		]
	],

	'workout_session' => [
		[
			'min'=>0,
			'max'=>2000,
			'session' => [
				[
					'slabs'=>299,
					'quantity'=>1
				]
			],
			'total'=>1,
			'amount'=>299
		],
		[
			'min'=>2000,
			'max'=>5000,
			'session' => [
				[
					'slabs'=>299,
					'quantity'=>1
				]
			],
			'total'=>1,
			'amount'=>299
		],
		[
			'min'=>5000,
			'max'=>7500,
			'session' => [
				[
					'slabs'=>299,
					'quantity'=>2
				]
			],
			'total'=>2,
			'amount'=>598
		],
		[
			'min'=>7500,
			'max'=>10000,
			'session' => [
				[
					'slabs'=>299,
					'quantity'=>1
				],
				[
					'slabs'=>499,
					'quantity'=>1
				]
			],
			'total'=>2,
			'amount'=>789
		],
		[
			'min'=>10000,
			'max'=>15000,
			'session' => [
				[
					'slabs'=>499,
					'quantity'=>2
				]
			],
			'total'=>2,
			'amount'=>998
		],
		[
			'min'=>15000,
			'max'=>20000,
			'session' => [
				[
					'slabs'=>299,
					'quantity'=>1
				],
				[
					'slabs'=>499,
					'quantity'=>2
				]
			],
			'total'=>3,
			'amount'=>1297
		],
		[
			'min'=>20000,
			'max'=>25000,
			'session' => [
				[
					'slabs'=>499,
					'quantity'=>3
				]
			],
			'total'=>3,
			'amount'=>1497
		],
		[
			'min'=>25000,
			'max'=>35000,
			'session' => [
				[
					'slabs'=>299,
					'quantity'=>2
				],
				[
					'slabs'=>499,
					'quantity'=>3
				]
			],
			'total'=>5,
			'amount'=>2095
		],
		[
			'min'=>35000,
			'max'=>0,
			'session' => [
				[
					'slabs'=>299,
					'quantity'=>4
				],
				[
					'slabs'=>499,
					'quantity'=>3
				]
			],
			'total'=>7,
			'amount'=>2693
		],
	],

	'swimming_session' => [
		[
			'min'=>7500,
			'max'=>10000,
			'session' => [
				[
					'slabs'=>1500,
					'quantity'=>1
				]
			],
			'total'=>1,
			'amount'=>1500
		],
		[
			'min'=>10000,
			'max'=>15000,
			'session' => [
				[
					'slabs'=>1500,
					'quantity'=>1
				]
			],
			'total'=>1,
			'amount'=>1500
		],
		[
			'min'=>15000,
			'max'=>20000,
			'session' => [
				[
					'slabs'=>1500,
					'quantity'=>1
				]
			],
			'total'=>1,
			'amount'=>1500
		],
		[
			'min'=>20000,
			'max'=>25000,
			'session' => [
				[
					'slabs'=>1500,
					'quantity'=>2
				]
			],
			'total'=>2,
			'amount'=>3000
		],
		[
			'min'=>25000,
			'max'=>35000,
			'session' => [
				[
					'slabs'=>1500,
					'quantity'=>2
				]
			],
			'total'=>2,
			'amount'=>3000
		],
		[
			'min'=>35000,
			'max'=>0,
			'session' => [
				[
					'slabs'=>1500,
					'quantity'=>2
				]
			],
			'total'=>2,
			'amount'=>3000
		],
	],



];