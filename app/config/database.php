<?php

//local
// $host = "localhost";
// $username = "";
// $password = "";

// stage
// $host = "apistage.fitn.in";
// $username = "admin";
// $password = "fit123";
// $options = ['db' => 'admin','authMechanism' => 'MONGODB-CR']; // sets the authentication database required by mongo 3]

//production

$host = "54.179.134.14"; 
$username = ""; 
$password = ""; 

return array(

	/*
	|--------------------------------------------------------------------------
	| PDO Fetch Style
	|--------------------------------------------------------------------------
	|
	| By default, database results will be returned as instances of the PHP
	| stdClass object; however, you may desire to retrieve records in an
	| array format for simplicity. Here you can tweak the fetch style.
	|
	*/

	'fetch' => PDO::FETCH_CLASS, 

	/*
	|--------------------------------------------------------------------------
	| Default Database Connection Name
	|--------------------------------------------------------------------------
	|
	| Here you may specify which of the database connections below you wish
	| to use as your default connection for all database work. Of course
	| you may use many connections at once using the Database library.
	|
	*/


	'default' => 'mongodb', 

	/*
	|--------------------------------------------------------------------------
	| Database Connections
	|--------------------------------------------------------------------------
	|
	| Here are each of the database connections setup for your application.
	| Of course, examples of configuring each database platform that is
	| supported by Laravel is shown below to make development simple.
	|
	|
	| All database work in Laravel is done through the PHP PDO facilities
	| so make sure you have the driver for your particular database of
	| choice installed on your machine before you begin development.
	|
	*/

	'connections' => array(

		'mongodb' => array(
			'driver' => 'mongodb', 
			'host' => $host, 
			'port' => 27017, 
			'database' => 'fitadmin', 
		    'username' => $username, 
		    'password' => $password, 
			// 'options' => $options
			// 'options' => [
			// 		'db' => 'admin' // sets the authentication database required by mongo 3
			// 	]
		), 

		'mongodb2' => array(
			'driver' => 'mongodb', 
			'host' => $host, 
			'port' => 27017, 
			'database' => 'fitapi', 
			'username' => $username, 
			'password' => $password, 
			// 'options' => $options
			// 'options' => [
			// 	'db' => 'admin' // sets the authentication database required by mongo 3
			// ]
		), 

		'fitcheckins' => array(
			'driver' => 'mongodb', 
			'host' => $host, 
			'port' => 27017, 
			'database' => 'fitcheckins', 
			'username' => $username, 
			'password' => $password, 
			// 'options' => $options
			// 'options' => [
			// 	'db' => 'admin' // sets the authentication database required by mongo 3
			// ]
		), 
		// 'stage' => array(
		// 	'driver' => 'mongodb', 
		// 	'host' => "apistage.fitn.in", 
		// 	'port' => 27017, 
		// 	'database' => 'fitapi', 
		// 	'username' => 'admin, 
		// 	'password' => 'fit123, 
		// 	'options' => ['db' => 'admin','authMechanism' => 'MONGODB-CR']
		// 	// 'options' => [
		// 	// 	'db' => 'admin' // sets the authentication database required by mongo 3
		// 	// ]
		// ), 


		// 'mongodb3' => array(
		// 	'driver'   => 'mongodb',
        //     'host'     => $host,
		// 	'port'     => 27017,
		// 	'database' => 'fitadminnew',
        //     'username' => $username,
        //     'password' => $password
		// ),


		/*'sqlite' => array(
			'driver'   => 'sqlite',
			'database' => __DIR__.'/../database/production.sqlite', 
			'prefix' => '', 
			), 

		'mysql' => array(
			'driver' => 'mysql', 
			'host' => 'apistage.fitn.in', 
			'database' => 'database', 
			'username' => 'root', 
			'password' => '', 
			'charset' => 'utf8', 
			'collation' => 'utf8_unicode_ci', 
			'prefix' => '', 
			), 

		'pgsql' => array(
			'driver' => 'pgsql', 
			'host' => 'localhost', 
			'database' => 'database', 
			'username' => 'root', 
			'password' => '', 
			'charset' => 'utf8', 
			'prefix' => '', 
			'schema' => 'public', 
			), 

		'sqlsrv' => array(
			'driver' => 'sqlsrv', 
			'host' => 'localhost', 
			'database' => 'database', 
			'username' => 'root', 
			'password' => '', 
			'prefix' => '', 
			),  */

			), 

	/*
	|--------------------------------------------------------------------------
	| Migration Repository Table
	|--------------------------------------------------------------------------
	|
	| This table keeps track of all the migrations that have already run for
	| your application. Using this information, we can determine which of
	| the migrations on disk have not actually be run in the databases.
	|
	*/

	'migrations' => 'migrations', 

	/*
	|--------------------------------------------------------------------------
	| Redis Databases
	|--------------------------------------------------------------------------
	|
	| Redis is an open source, fast, and advanced key-value store that also
	| provides a richer set of commands than a typical key-value systems
	| such as APC or Memcached. Laravel makes it easy to dig right in.
	|
	*/

	'redis' => array(

		'cluster' => false, 

		'default' => array(
			'host' => '127.0.0.1', 
			'port' => 6379, 
			'database' => 0, 
			), 
		'newredis' => array(
			'host' => '127.0.0.1', 
			'port' => 6379, 
			'database' => 1, 
			), 
		), 
	); 
