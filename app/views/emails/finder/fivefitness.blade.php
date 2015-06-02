<html>
<body>
	<table style="width:100%">
		<tr>
			<td style="display:block !important;
			max-width:600px !important; 
			margin:0 auto !important;
			clear:both !important;">
			<div style="padding:15px;max-width:100% !important;margin:0 auto; display:block;">

				<table>
					<tr>
						<td>
							<p style="font-size:16px;color:#464646;text-align:justify;">
								Hello,<br>
								Customer details for 5 fitness.
							</p>
							<br>
							<table border="1" bordercolor="#2c3e50" align="center">
								<tr>
									<td>User Name:</td>
									<td>{{$name}}</td>
								</tr>
								<tr>
									<td>User Email:</td>
									<td>{{$email}}</td>
								</tr>
								<tr>
									<td>User Phone:</td>
									<td>{{$phone}}</td>
								</tr>
								<tr>
									<td>Location:</td>
									<td>{{$location}}</td>
								</tr>
								<tr>
									<td>Alternate vendor list:</td>
									<td>{{$vendor}}</td>
								</tr>
								<tr>
									<td>Date:</td>
									<td>{{$date}}</td>
								</tr>
								
							</table> 

							<p style="font-size:16px;color:#464646;text-align:justify;">
								Thank you.								
							</p>						
						</td>
					</tr>
				</table>
			</div>

		</td>
	</tr>
</table>

</body>
</html>