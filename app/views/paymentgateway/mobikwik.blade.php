<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-I">
	<title>Mobikwik</title>
</head>
<body>
  Redirect from Mobikwik<br>

  <form name="form" method="post">
    <input type="hidden" id="response" name="responseField" value='<?php echo $response?>'>
  </form>
</body>

	<script type="text/javascript">

		response();

		function response(){

			return document.getElementById('response').value;
		}

	</script>

</html>