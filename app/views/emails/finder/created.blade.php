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
								Hello Administrator,<br>
								You have received a new Finder!
							</p>
							<br>
							<table border="1" bordercolor="#2c3e50" align="center">
								<tr>
									<td>Finder ID:</td>
									<td>{{$finder_id}}</td>
								</tr>
								<tr>
									<td>Finder Owner:</td>
									<td>{{$finder_owner}}</td>
								</tr>
								<tr>
									<td>Approval Status:</td>
									<td>{{$approval_status	}}</td>		
								</tr>
								<tr>
									<td>Finder Link:</td>
									<td>{{$finder_link	}}</td>
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