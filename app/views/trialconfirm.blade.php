<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Laravel PHP Framework</title>
	
</head>
<body>
    
    <h3>Are you sure you want to {{$action}} the {{$booktrial_data['type']}}?</h3>
    <ul>
    <li>Vendor Name:{{$booktrial_data['finder_name']}}</li>
    <li>Vendor Location:{{$booktrial_data['finder_location']}}</li>
    <li>City:{{$cities[$booktrial_data['city_id']]}}</li>
    <li>POC Contact Number: {{$booktrial_data['finder_vcc_mobile']}}</li>
    <li>Customer Name: {{$booktrial_data['customer_name']}}</li>
    <li>Customer Number: {{$booktrial_data['customer_phone']}}</li>
    <li>Schedule Date & Time:{{date("d-m-Y g:i A", strtotime($booktrial_data['schedule_date_time']))}}</li>
    <li>Service: {{ucwords($booktrial_data['service_name'])}}</li>
    <li>Amount: {{isset($booktrial_data['amount']) ? $booktrial_data['amount'] : "Free"}}</li>

    </ul>
    <div><a href="{{$action_link}}">Yes</a></div>
   
    </body>
    </html>