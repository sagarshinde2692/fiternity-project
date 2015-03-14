<html>
<body>
    
    <div style="text-align:left;">
        <p style="font-size:16px;color:#464646;text-align:justify;"> Hi, {{ ucwords($finder_poc_for_customer_name) }}</p> 
        <p>Greetings from Fitternity.com</p>

        <p> We have received a booking for workout session / trial for {{ ucwords($finder_name) }} @if ($value->finder_type == 1) ,  {{ ucwords($finder_location) }} @endif . Here are the details. </p>

        <table border="1" bordercolor="#2c3e50" align="center" cellspacing="0" width="550" style="margin:5px 0px 15px 0px;">
            <tr><td>Name of the customer: </td><td>{{ ucwords($customer_name) }}</td></tr>
            <tr><td>Date: </td><td>{{ date(' jS\F, Y \(l\) ', strtotime($schedule_date_time) )  }}</td></tr>
            <tr><td>Time: </td><td>{{ date(' g\.i A', strtotime($schedule_date_time) ) }}</td></tr>
            <tr><td>Subscription Code: </td><td> {{ $code }}(this code will be shared by the customer to avail the session) </td></tr>
            <tr><td>Workout type: </td><td> Trial </td></tr>
            <tr><td>Workout form :  </td><td>{{ ucwords($service_name)  }}</td></tr>
            <tr><td>Contact person name (provided to the customer): </td><td>{{ ucwords($finder_poc_for_customer_name) }}</td></tr>
        </table> 

        <p>If this session cannot be managed / fulfilled at your end - please let us know at the earliest. You can reply to this mail or call us on +91 92222 21131. <br></p>
        <p>We will be sending you an update if there is a change in this booking. You shall also receive a daily report on customers who have booked sessions for tomorrow.<br></p>
        <p>Regards<br>TEAM FITTERNITY</p>
    </div>


</body>
</html>


