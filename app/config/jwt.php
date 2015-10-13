<?php

return array(
	'webnode' => array(
		'key' => 'fitternitynodeweb', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+(10), // time when token gets expired (10 sec)
		'alg' => 'HS256',
	),
	'androidnode' => array(
		'key' => 'fitternitynodeandroid', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+(10), // time when token gets expired (10 sec)
		'alg' => 'HS256',
	),
	'iosnode' => array(
		'key' => 'fitternitynodeios', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+(10), // time when token gets expired (10 sec)
		'alg' => 'HS256',
	),
);