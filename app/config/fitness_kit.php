<?php

$url = 'https://b.fitn.in/gamification/reward/goodies/';

$products = [
	'Workout Friendly Armband'=>[
		"title"=>'Workout Friendly Armband',
		'url'=>$url.'products/armband.png'
	],
	'Cool-Water Bottle'=>[
		"title"=>'Cool-Water Bottle',
		'url'=>$url.'products/bottle.png'
	],
	'Earphone Detangler'=>[
		"title"=>'Earphone Detangler',
		'url'=>$url.'products/detangler.jpg'
	],
	'Waterproof Gym Bag'=>[
		"title"=>'Waterproof Gym Bag',
		'url'=>$url.'products/gymbag.jpg'
	],
	'Shaker'=>[
		"title"=>'Shaker',
		'url'=>$url.'products/shaker.png'
	],
	'Drawstring Shoe Bag'=>[
		"title"=>'Drawstring Shoe Bag',
		'url'=>$url.'products/shoebag.jpg'
	],
	'Casual Tote Bag'=>[
		"title"=>'Casual Tote Bag',
		'url'=>$url.'products/totebag.png'
	],
	'Compact Hand Towel'=>[
		"title"=>'Compact Hand Towel',
		'url'=>$url.'products/towel.png'
	],
	'Tshirt Yoga Zumba'=>[
		"title"=>'Breather T-Shirt',
		'url'=>$url.'products/tshirtyzm.png'
	],
	'Tshirt Crossfit'=>[
		"title"=>'Breather T-Shirt',
		'url'=>$url.'products/tshirtcfm.png'
	],
	'Yoga Mat'=>[
		"title"=>'Yoga Mat',
		'url'=>$url.'products/yogamat.png'
	],
	'Yoga Mat Bag'=>[
		"title"=>'Yoga Mat Bag',
		'url'=>$url.'products/yogamatbag.png'
	],
	'Breather T-Shirt'=>[
		"title"=>'Breather T-Shirt',
		'url'=>$url.'products/tshirtcfm.png'
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
					'image' => $url.'zumba/zumba1.1.png',
                    'gallery'=>[
                    	$url.'zumba/zumba1.1.png',
                     	$products["Drawstring Shoe Bag"]["url"],
						$products["Workout Friendly Armband"]["url"]	   
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
					'image' => $url.'crossfit/crossfit1.1.png',
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
					'image' => $url.'yoga/yoga1.1.png',
                    'gallery'=>[
                    	$url.'yoga/yoga1.1.png',
                     	$products["Drawstring Shoe Bag"]["url"],
						$products["Earphone Detangler"]["url"]
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
					'image' => $url.'zumba/zumba1.2.png',
                    'gallery'=>[
                    	$url.'zumba/zumba1.2.png',
                     	$products["Casual Tote Bag"]["url"],
						$products["Workout Friendly Armband"]["url"]
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
					'image' => $url.'crossfit/crossfit1.2.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit1.2.png',
                     	$products["Cool-Water Bottle"]["url"],
						$products["Earphone Detangler"]["url"] 
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
					'image' => $url.'yoga/yoga1.2.png',
                    'gallery'=>[
                    	$url.'yoga/yoga1.2.png',
                     	$products["Casual Tote Bag"]["url"],
						$products["Cool-Water Bottle"]["url"]   
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
					'image' => $url.'zumba/zumba1.3.png',
                    'gallery'=>[
                    	$url.'zumba/zumba1.3.png',
                     	$products["Drawstring Shoe Bag"]["url"],
						$products["Cool-Water Bottle"]["url"]
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
					'image' => $url.'crossfit/crossfit1.3.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit1.3.png',
                     	$products["Waterproof Gym Bag"]["url"],
						$products["Shaker"]["url"] 
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
					'image' => $url.'yoga/yoga1.3.png',
                    'gallery'=>[
                    	$url.'yoga/yoga1.3.png',
                     	$products["Drawstring Shoe Bag"]["url"],
						$products["Compact Hand Towel"]["url"],
						$products["Cool-Water Bottle"]["url"],
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
					'image' => $url.'zumba/zumba1.4.png',
                    'gallery'=>[
                    	$url.'zumba/zumba1.4.png',
                     	$products["Casual Tote Bag"]["url"],
						$products["Cool-Water Bottle"]["url"],
						$products["Compact Hand Towel"]["url"] 
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
					'image' => $url.'crossfit/crossfit1.4.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit1.4.png',
                     	$products["Waterproof Gym Bag"]["url"],
						$products["Shaker"]["url"],
						$products["Compact Hand Towel"]["url"]   
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
					'image' => $url.'yoga/yoga1.4.png',
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
					'image' => $url.'zumba/zumba1.5.png',
                    'gallery'=>[
                    	$url.'zumba/zumba1.5.png',
                     	$products["Casual Tote Bag"]["url"],
						$products["Cool-Water Bottle"]["url"],
						$products["Compact Hand Towel"]["url"],
						$products["Workout Friendly Armband"]["url"] 
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
					'image' => $url.'crossfit/crossfit1.5.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit1.5.png',
                     	$products["Waterproof Gym Bag"]["url"],
						$products["Shaker"]["url"],
						$products["Tshirt Crossfit"]["url"]	   
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
					'image' => $url.'yoga/yoga1.5.png',
                    'gallery'=>[
                    	$url.'yoga/yoga1.5.png',
                     	$products["Casual Tote Bag"]["url"],
						$products["Tshirt Yoga Zumba"]["url"],
						$products["Cool-Water Bottle"]["url"]  
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
					'image' => $url.'zumba/zumba1.6.png',
                    'gallery'=>[
                    	$url.'zumba/zumba1.6.png',
                     	$products["Casual Tote Bag"]["url"],
						$products["Cool-Water Bottle"]["url"],
						$products["Compact Hand Towel"]["url"],
						$products["Workout Friendly Armband"]["url"],
						$products["Earphone Detangler"]["url"]   
                    ]
				],	
				[
					"product" => [
						$products["Waterproof Gym Bag"]["title"],
						$products["Shaker"]["title"],
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
					'image' => $url.'crossfit/crossfit1.6.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit1.6.png',
                     	$products["Waterproof Gym Bag"]["url"],
						$products["Shaker"]["url"],
						$products["Tshirt Crossfit"]["url"],
						$products["Workout Friendly Armband"]["url"] 
                    ]
				],
				[
					"product" => [
						$products["Yoga Mat"]["title"],
						$products["Cool-Water Bottle"]["title"],
						$products["Tshirt Yoga Zumba"]["title"]
					],
					'category_id'=>[
						1,
						4
					],
					'amount'=>1750,
					'image' => $url.'yoga/yoga1.6.png',
                    'gallery'=>[
                    	$url.'yoga/yoga1.6.png',
                     	$products["Yoga Mat"]["url"],
						$products["Cool-Water Bottle"]["url"],
						$products["Tshirt Yoga Zumba"]["url"]
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
					'image' => $url.'zumba/zumba1.7.png',
                    'gallery'=>[
                    	$url.'zumba/zumba1.7.png',
                     	$products["Casual Tote Bag"]["url"],
						$products["Cool-Water Bottle"]["url"],
						$products["Compact Hand Towel"]["url"],
						$products["Workout Friendly Armband"]["url"],
						$products["Tshirt Yoga Zumba"]["url"]   
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
					'image' => $url.'crossfit/crossfit1.7.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit1.7.png',
                     	$products["Tshirt Crossfit"]["url"],
						$products["Compact Hand Towel"]["url"],
						$products["Waterproof Gym Bag"]["url"],
						$products["Earphone Detangler"]["url"],
						$products["Shaker"]["url"],
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
					'image' => $url.'yoga/yoga1.7.png',
                    'gallery'=>[
                    	$url.'yoga/yoga1.7.png',
                     	$products["Yoga Mat"]["url"],
						$products["Yoga Mat Bag"]["url"],
						$products["Cool-Water Bottle"]["url"],
						$products["Tshirt Yoga Zumba"]["url"]
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
					'image' => $url.'zumba/zumba2.1.png',
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
					'image' => $url.'crossfit/crossfit2.1.png',
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
					'image' => $url.'yoga/yoga2.1.png',
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
					'image' => $url.'zumba/zumba2.2.png',
                    'gallery'=>[
                    	$url.'zumba/zumba2.2.png',
                     	$products["Drawstring Shoe Bag"]["url"],
						$products["Compact Hand Towel"]["url"]
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
					'image' => $url.'crossfit/crossfit2.2.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit2.2.png',
                     	$products["Shaker"]["url"],
						$products["Workout Friendly Armband"]["url"]
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
					'image' => $url.'yoga/yoga2.2.png',
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
					'image' => $url.'zumba/zumba2.3.png',
                    'gallery'=>[
                    	$url.'zumba/zumba2.3.png',
                     	$products["Casual Tote Bag"]["url"],
						$products["Workout Friendly Armband"]["url"] 
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
					'image' => $url.'crossfit/crossfit2.3.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit2.3.png',
                     	$products["Cool-Water Bottle"]["url"],
						$products["Tshirt Crossfit"]["url"]
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
					'image' => $url.'yoga/yoga2.3.png',
                    'gallery'=>[
                    	$url.'yoga/yoga2.3.png',
                     	$products["Yoga Mat Bag"]["url"],
						$products["Workout Friendly Armband"]["url"]  
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
					'image' => $url.'zumba/zumba2.4.png',
                    'gallery'=>[
                    	$url.'zumba/zumba2.4.png',
                     	$products["Drawstring Shoe Bag"]["url"],
						$products["Tshirt Yoga Zumba"]["url"]
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
					'image' => $url.'crossfit/crossfit2.4.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit2.4.png',
                     	$products["Waterproof Gym Bag"]["url"],
						$products["Tshirt Crossfit"]["url"]
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
					'image' => $url.'yoga/yoga2.4.png',
                    'gallery'=>[
                    	$url.'yoga/yoga2.4.png',
                    	$products["Casual Tote Bag"]["url"],
						$products["Compact Hand Towel"]["url"],
						$products["Cool-Water Bottle"]["url"]
                     	   
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
					'image' => $url.'zumba/zumba2.5.png',
                    'gallery'=>[
                    	$url.'zumba/zumba2.5.png',
                     	$products["Drawstring Shoe Bag"]["url"],
						$products["Tshirt Yoga Zumba"]["url"],
						$products["Earphone Detangler"]["url"]
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
					'image' => $url.'yoga/yoga2.5.png',
                    'gallery'=>[
                    	$url.'yoga/yoga2.5.png',
                     	$products["Shaker"]["url"],
						$products["Waterproof Gym Bag"]["url"],
						$products["Compact Hand Towel"]["url"],
						$products["Workout Friendly Armband"]["url"] 
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
					'image' => $url.'crossfit/crossfit2.5.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit2.5.png',
                     	$products["Yoga Mat"]["url"],
						$products["Yoga Mat Bag"]["url"]  
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
					'image' => $url.'zumba/zumba2.6.png',
                    'gallery'=>[
                    	$url.'zumba/zumba2.6.png',
                    	$products["Drawstring Shoe Bag"]["url"],
						$products["Tshirt Yoga Zumba"]["url"],
						$products["Compact Hand Towel"]["url"]
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
					'image' => $url.'crossfit/crossfit2.6.png',
                    'gallery'=>[
                    	$url.'crossfit/crossfit2.6.png',
                 	   	$products["Cool-Water Bottle"]["url"],
						$products["Waterproof Gym Bag"]["url"],
						$products["Compact Hand Towel"]["url"],
						$products["Workout Friendly Armband"]["url"]
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
					'image' => $url.'yoga/yoga2.6.png',
                    'gallery'=>[
                    	$url.'yoga/yoga2.6.png',
                     	$products["Yoga Mat"]["url"],
						$products["Yoga Mat Bag"]["url"],
						$products["Compact Hand Towel"]["url"]
                    ]
				]
			]
		]
	]

];