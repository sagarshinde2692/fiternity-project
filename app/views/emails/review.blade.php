<html>
<body>
	<table style="width:100%">
		<tr>
			<td style="display:block !important; max-width:600px !important; margin:0 auto !important; clear:both !important;">
				<div style="padding:15px;max-width:100% !important;margin:0 auto; display:block;">
					<table>
						<tr>
							<td>
								<p style="font-size:16px;color:#464646;text-align:justify;">
									Review given on vendor page.
								</p>
								<br>
								<table border="1" bordercolor="#2c3e50" align="center">
									@if ($vendor != '')
									<tr> <td>Vendor Name:</td> <td>{{$vendor}}</td> </tr>
									@endif 

									@if ($review != '')
									<tr> <td>Review :</td> <td>{{$review}}</td> </tr>
									@endif 

									<tr> <td>Date:</td> <td>{{$date}}</td> </tr>
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