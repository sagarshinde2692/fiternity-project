<html>
<body>
	
	<div>
		<p style="font-size:16px;color:#464646;text-align:justify;"> Hey, {{	$customer_name	}}</p> 
		<p> Thank you for booking a workout session / trial at {{ $finder_name }},  Andheri (West) through Fitternity.com. Your session is CONFIRMED. Here are the details. </p>
		<table border="1" bordercolor="#2c3e50" align="center">
			<tr><td>Name of the fitness service provider: </td><td>{{	$finder_name	}}</td></tr>
			<tr><td>Date: </td><td>{{ $schedule_date }}</td></tr>
			<tr><td>Time: </td><td>{{ $sechedule_slot }}</td></tr>
			<tr><td>Workout form (if any):	</td><td>{{	$service_name	}}</td></tr>
			<tr><td>Workout type: </td><td> Trial </td></tr>
			<tr><td>Subscription Code: </td><td> (please flash this code at the service provider location) </td></tr>
			<tr><td>Session booked for:		</td><td>{{	$customer_name	}}</td></tr>

			<tr><td>Customer Email:		</td><td>{{	$customer_email	}}</td></tr>
			<tr><td>Customer Phone:		</td><td>{{	$customer_phone	}}</td></tr>
		</table> 

		<!--table border="1" bordercolor="#2c3e50" align="center">
			<tr><td>How to get there?</td></tr>
			<tr><td>Date: </td><td>{{ $schedule_date }}</td></tr>
			<tr><td>Time: </td><td>{{ $sechedule_slot }}</td></tr>
			<tr><td>Workout form (if any):	</td><td>{{	$service_name	}}</td></tr>
			<tr><td>Workout type: </td><td> Trial </td></tr>
			<tr><td>Subscription Code: </td><td> (please flash this code at the service provider location) </td></tr>
			<tr><td>Session booked for:		</td><td>{{	$customer_name	}}</td></tr>

			<tr><td>Customer Email:		</td><td>{{	$customer_email	}}</td></tr>
			<tr><td>Customer Phone:		</td><td>{{	$customer_phone	}}</td></tr>
		</table>-- >

		<p></p>
	</div>


</body>
</html>


