<html>
<body>
  
  <div style="text-align:left;">
    <p style="font-size:16px;color:#464646;text-align:justify;"> Hey, {{ ucwords($customer_name) }}</p> 
    <p> Thank you for booking a workout session / trial at {{ ucwords($finder_name) }} @if ($show_location_flag) ,  {{ ucwords($finder_location) }} @endif through Fitternity.com. Your session is CONFIRMED. Here are the details. </p>
    <table border="1" bordercolor="#2c3e50" align="center" cellspacing="0" width="550" style="margin:5px 0px 15px 0px;">
      <tr><td>Name of the fitness service provider: </td><td>{{ ucwords($finder_name) }}</td></tr>
      <tr><td>Date: </td><td>{{ date(' jS F\, Y \(l\) ', strtotime($schedule_date_time) )  }}</td></tr>
      <tr><td>Time: </td><td>{{ date(' g\.i A', strtotime($schedule_date_time) ) }}</td></tr>
      <tr><td>Workout form (if any):  </td><td>{{ ucwords($service_name)  }}</td></tr>
      <tr><td>Workout type: </td><td> Trial </td></tr>
      <tr><td>Subscription Code: </td><td> {{ $code }}(please flash this code at the service provider location) </td></tr>
      <tr><td>Session booked for:   </td><td>{{ ucwords($customer_name) }}</td></tr>
    </table> 

    <p><b>How to get there?</b></p>
    <table border="1" bordercolor="#2c3e50" align="center" cellspacing="0" width="550" style="margin:5px 0px 15px 0px;">
      <tr><td>Address: </td><td>{{ ucwords($finder_address) }}</td></tr>
      <tr><td>Contact Person: </td><td>{{ ucwords($finder_poc_for_customer_name) }}</td></tr>
    </table>

    <p>If you need to change the time or day of the session just reply to this mail or call us on +91 92222 21131.</p>
    <p>We shall be sending you a reminder message to ensure you don't miss out on the workout. We hope you have a great session. </p>
    <p>Regards<br>TEAM FITTERNITY</p>
  </div>


</body>
</html>


