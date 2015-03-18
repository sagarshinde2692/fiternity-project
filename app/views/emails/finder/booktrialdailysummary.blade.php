<html>
<body>

    <div style="text-align:left;">
        <p style="font-size:16px;color:#464646;text-align:justify;"> Hi, {{ $finder_poc_for_customer_name }}</p> 
        <p>Greetings from Fitternity.com</p>

        <p> Daily report on customers who have booked sessions for tomorrow. Here are the details. </p>

        <table border="1" bordercolor="#2c3e50" align="center" cellspacing="0" width="550" style="margin:5px 0px 15px 0px;">
            <tr>
                <td>Customer Name </td>
                <td>Schedule Date </td>
                <td>Schedule Slot </td>
                <td>Subscription Code </td>
                <td>Workout Form</td>
                <td>Contact person name (provided to the customer)</td>
            </tr>

            @foreach($scheduletrials as $key => $value)
            <tr>
                <td>{{ ucwords($value['customer_name']) }} </td>
                <td>{{ $value['schedule_date'] }} </td>
                <td>{{ $value['schedule_slot'] }} </td>
                <td>{{ $value['code'] }}</td>
                <td>{{ ucwords($value['service_name']) }}</td>
                <td>{{ ucwords($value['finder_poc_for_customer_name']) }}</td>
            </tr>
            @endforeach
        </table> 
        
        <p>Regards<br>TEAM FITTERNITY</p>
    </div>


</body>
</html>


