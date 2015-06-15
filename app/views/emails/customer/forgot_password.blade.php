<html>
	<body>
		<div style="text-align:left;">
			<p>Hello {{$name}},</p>
			<p>Someone has requested a link to change your password. You can do this through the link below.</p>
			<p><a href="{{$token}}" target="_blank">Reset your password</a></p>
			<p>If you didn't request this, you can safely ignore this email.</p>
			<p>Your password won't change until you access the link above and create a new one.</p>
			<hr>
			<p>You're receiving this because you registered to <span class="il">Fitternity</span>.</p>
			<p>Regards,<br>Team Fitternity</p>
	</body>
</html>