<?php

return array(
	'web' => array(
		'key' => 'fitternityweb', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+(10), // time when token gets expired (10 sec)
		'alg' => 'HS256',
	),
	'android' => array(
		'key' => 'fitternityandroid', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+(10), // time when token gets expired (10 sec)
		'alg' => 'HS256',
	),
	'ios' => array(
		'key' => 'fitternityios', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+(10), // time when token gets expired (10 sec)
		'alg' => 'HS256',
	),
	'vendorpanel' => array(
		'key' => 'fitternityvendorpanel', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+8640000, // time when token gets expired (1 day)
		'alg' => 'HS256',
	),
	'kiosk' => array(
		'key' => 'fitternitykiosk', //secret key to encode token
		'iat' => time(), // time when token is created
		'nbf' => time(), // time when token can be used from
		'exp' => time()+8640000, // time when token gets expired (1 day)
		'alg' => 'HS256',
	),
);