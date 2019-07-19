<?php 

$message = "Hi ".ucwords($customer_name).",Congratulations your All Access $type is now activated. Enjoy the unlimited access to book sessions in all gyms and studios across India. Download Fitternity App and start booking ".Config::get('app.download_app_link').". Valid for $duration. In case of quick assistance call ".Config::get('app.contact_us_customer_number').". Book Now";

echo $message;


?>