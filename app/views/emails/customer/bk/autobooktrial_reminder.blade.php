<html>
<body>
	
	<div style="text-align:left;">
		<p style="font-size:16px;color:#464646;text-align:justify;"> Hey, {{ ucwords($customer_name) }}</p> 
		<p> This is regarding the workout session / trial booked on Fitternity for {{ ucwords($finder_name) }},  {{ ucwords($finder_location) }} on 
			{{ date(' F jS\, Y \(l\) g\.i A', strtotime($schedule_date_time) )  }} <br></p>

			<p>Incase if you have queries, would like to reschedule or cancel your session - please call us on {{Config::get('app.{{Config::get('app.contact_us_customer_number')}}')}} or reply to this mail.<br></p>

			<p>Here are some quick tips for your session:<br></p>

			<p><b>What to carry?</b></p>
			<p>Gym bag with water bottle and sweat towel</p>
			<p>Additionals (if required): yoga mat, music, face wash, spare clothes/socks, deodorant, post workout snack (protein / granola bar)<br></p>

			<p><b>What to wear?</b></p>
			<p>Comfortable workout wear, Sport shoes and socks (tailor your attire to the specific fitness activity)<br></p>

			<p>We will reach out to you post your session and help you with the membership or provide other options (basis your feedback). Please note we have awesome insider discounts available only for Fitternity members. So stay connected before you make a purchase. <br></p>

			<p><b>Your session details:</b></p>
			<table border="1" bordercolor="#2c3e50" align="center" cellspacing="0" width="550" style="margin:5px 0px 15px 0px;">
				<tr><td>Name of the fitness service provider: </td><td>{{ ucwords($finder_name) }}</td></tr>
				<tr><td>Date: </td><td>{{ date(' jS\, Y \(l\) ', strtotime($schedule_date_time) )  }}</td></tr>
				<tr><td>Time: </td><td>{{ date(' g\.i A', strtotime($schedule_date_time) ) }}</td></tr>
				<tr><td>Workout form (if any):	</td><td>{{	ucwords($service_name)	}}</td></tr>
				<tr><td>Workout type: </td><td> Trial </td></tr>
				<tr><td>Subscription Code: </td><td> {{ $code }}(please flash this code at the service provider location) </td></tr>
				<tr><td>Session booked for:		</td><td>{{	ucwords($customer_name)	}}</td></tr>
			</table> 

			<p><b>How to get there?</b></p>
			<table border="1" bordercolor="#2c3e50" align="center" cellspacing="0" width="550" style="margin:5px 0px 15px 0px;">
				<tr><td>Address: </td><td>{{ ucwords($finder_address) }}</td></tr>
				<tr><td>Contact Person: </td><td>{{ ucwords($finder_poc_for_customer_name) }}</td></tr>
				<tr><td>Contact No: </td><td>{{ $finder_poc_for_customer_no }}</td></tr>
			</table>

			<p>Regards<br>TEAM FITTERNITY</p>
		</div>

	</body>
	</html>


