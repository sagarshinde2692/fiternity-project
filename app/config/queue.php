<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Default Queue Driver
	|--------------------------------------------------------------------------
	|
	| The Laravel queue API supports a variety of back-ends via an unified
	| API, giving you convenient access to each back-end using the same
	| syntax for each one. Here you may set the default queue driver.
	|
	| Supported: "sync", "beanstalkd", "sqs", "iron", "redis"
	|
	*/

	'default' => 'iron',
	// 'default' => 'redis',

	/*
	|--------------------------------------------------------------------------
	| Queue Connections
	|--------------------------------------------------------------------------
	|
	| Here you may configure the connection information for each server that
	| is used by your application. A default configuration has been added
	| for each back-end shipped with Laravel. You are free to add more.
	|
	*/

	'connections' => array(

		'sync' => array(
			'driver' => 'sync',
		),

		'beanstalkd' => array(
			'driver' => 'beanstalkd',
			'host'   => 'localhost',
			'queue'  => 'default',
			'ttr'    => 60,
		),

		'sqs' => array(
			'driver' => 'sqs',
			'key'    => 'AKIAIPJP4UXP4VLZCP2Q',
			'secret' => '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',
			'queue'  => 'https://sqs.ap-southeast-1.amazonaws.com/246537648714/FitQ',
			'region' => 'ap-southeast-1',
		),

		'iron' => array(
			'driver'  => 'iron',
			'host'    => 'mq-aws-us-east-1.iron.io',
			'token'   => 'tsFrArQmL8VS8Cx-5PDg3gij19Y',
			'project' => '549a5af560c8e60009000030',
			'queue'   => 'app',
			'encrypt' => true,
		),

		'redis' => array(
			'driver' => 'redis',
			'queue'  => 'default',
		),

	),

	/*
	|--------------------------------------------------------------------------
	| Failed Queue Jobs
	|--------------------------------------------------------------------------
	|
	| These options configure the behavior of failed queue job logging so you
	| can control which database and table are used to store the jobs that
	| have failed. You may change them to any database / table you wish.
	|
	*/

	'failed' => array(

		'database' => 'mysql', 'table' => 'failed_jobs',

	),

);
