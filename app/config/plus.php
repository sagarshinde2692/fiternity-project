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
];