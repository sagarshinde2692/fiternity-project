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
								This is a notification to inform you that there is a blog comment has been added to the {{$article}}.
							</p>
							<br>
							<table border="1" bordercolor="#2c3e50" align="center">
								<tr>
									<td>User Name:</td>
									<td>{{$name}}</td>
								</tr>
								<tr>
									<td>Email Id:</td>
									<td>{{$email}}</td>
								</tr>
								<tr>
									<td>Article Name:</td>
									<td>{{$article}}</td>
								</tr>
								<tr>
									<td>Date:</td>
									<td>{{$date}}</td>
								</tr>
								<tr>
									<td>Time:</td>
									<td>{{$time}}</td>
								</tr>
								<tr>
									<td>Comment posted:</td>
									<td>{{$comment}}</td>
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