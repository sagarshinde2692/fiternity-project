
<html>
<body>
	
	<div style="text-align:left;">
		<p style="font-size:16px;color:#464646;text-align:justify;"> Hello,<br> There is a request for manual second book a trial. </p> 

		<table border="1" bordercolor="#2c3e50" align="center" cellspacing="0">
			<tr><td>Customer Name:</td><td>{{  ucwords($customer_name) }}</td></tr>
			<tr><td>Customer Email:</td><td>{{ $customer_email }}</td></tr>
			<tr><td>Customer Phone:</td><td>{{$customer_phone}}</td></tr>
			<tr><td>Finder Names:</td><td>{{$finder_names}}</td></tr>
			<tr><td>Preferred Location:</td><td>{{$preferred_location}}</td></tr>
			@if ($preferred_service != '')
			<tr><td>Preferred Service:</td><td>{{$preferred_service}}</td></tr>
			@endif 
			<tr><td>Preferred Time:</td><td>{{$preferred_time}}</td></tr>
			<tr><td>Preferred Day:</td><td>{{$preferred_day}}</td></tr>
		</table> 

		<p>Regards<br>TEAM FITTERNITY</p>
	</div>


</body>
</html>


