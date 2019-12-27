<?php
$finder_name = \Config::get('app.finder_name');
$finder_slug = \Config::get('app.finder_slug');
$service_name = 'Plus';
$service_id = 100003;

return [
    'finder_name' => $finder_name,
    'finder_slug' => $finder_slug,
    'service_name' => $service_name,
    'service_id' => $service_id,
    'plus_base_price' => 5000,
    'plus_a' => [
        'lower_limit' => 5001,
        'upper_limit' => 10000
    ],
    'plus_b' => [
        'lower_limit' => 10001
    ]
];