
<html>
<body>
  
  <div style="text-align:left;">
    <p style="font-size:16px;color:#464646;text-align:justify;"> Hello,<br> There is a request for purchasing service. </p> 

    <table border="1" bordercolor="#2c3e50" align="center" cellspacing="0">
      <tr><td>Customer Name:</td><td>{{  ucwords($customer_name) }}</td></tr>
      <tr><td>Customer Email:</td><td>{{ $customer_email }}</td></tr>
      <tr><td>Customer Phone:</td><td>{{ $customer_phone }}</td></tr>
      <tr><td>Customer Identify:</td><td>{{ $customer_identity }}</td></tr>
      <tr><td>Service Name:</td><td>{{ ucwords($service_name) }}</td></tr>
      <tr><td>Service Duration:</td><td>{{ ucwords($service_duration) }}</td></tr>
      <tr><td>Finder Name:</td><td>{{ ucwords($finder_name) }}</td></tr>
      <tr><td>Finder Address:</td><td>{{ ucfirst($finder_address) }}</td></tr>
      <tr><td>Order / Subscription ID: :</td><td> {{ $_id }}</td></tr>
      <tr><td>Payment Mode:</td><td> Payment Gateway</td></tr>
    </table> 

    <p>Regards<br>TEAM FITTERNITY</p>
  </div>


</body>
</html>


