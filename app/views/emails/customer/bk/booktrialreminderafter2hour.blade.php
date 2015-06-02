<html>
<body>

	<div style="text-align:left;">
		<p style="font-size:16px;color:#464646;text-align:justify;"> Hey, {{ ucwords($customer_name) }}</p> 
		<p> Hope you had a good experience at your trial session with {{ ucwords($finder_name) }}. We’ll call you afterwards to hear all about it and share some exclusive discounts in case you wish to subscribe! <br></p>

		<p>We would urge you to review {{ ucwords($finder_name) }} on our website - it will take your 2 minutes to rate and share your experience with other fitness enthusiasts. You also get eligible to our 'Review to Win' Contest and stand a chance to win I-phone 6, organic food hampers and other goodies..<br></p>

		<p>Please access this link to review: {{ link_to('http://www.fitternity.com/'.$finder_slug, ucwords($finder_name), array("style"=>"text-decoration:none; color:#F60") ) }}<br></p>

		<p>Request you to please share feedback about your experience with Fitternity. If there is anything we could do to improve your interaction - we would like to know. You could just reply to this mail or call me us on +91 9222221131.<br></p>

		<p>Regards<br>TEAM FITTERNITY</p>
	</div>

</body>
</html>


