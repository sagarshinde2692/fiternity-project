<?php

use Monolog\Logger;

// return array(
//    #'hosts' => array('localhost:9200'),
//    'hosts' => array('54.179.134.14:9200'),
//    'logPath' => '/var/log/elasticsearch/log',
//    'logLevel' => Logger::INFO,
//    'elasticsearch_host' => '54.179.134.14',
//    'elasticsearch_port' => 9200,
//    'elasticsearch_default_index' => 'fitadmin'
// );


return array(
   'hosts' => array('ESAdmin:fitternity2020@54.169.120.141:8050'),
    'logPath' => 'path/to/your/elasticsearch/log',
    'logLevel' => Logger::INFO,    
);