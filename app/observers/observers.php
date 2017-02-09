<?php namespace App\Observers;

use App\Observers\BaseObserver as BaseObserver;

/*$files = \File::allFiles(app_path().'/models');
foreach ($files as $file)
{
 	$file_path = explode('/', $file);

 	//echo"<pre>";print_r($file_path);exit;
 	$file_name = array_pop($file_path);
 	$model = explode('.', $file_name);

 	echo"<pre>";print_r($model[0]);exit;

	if($model[0] == 'Event') continue;
 	//\$model[0]::observe(new BaseObserver);

   //echo (string)$file, "\n";
}*/

\Order::observe(new BaseObserver);
\Booktrial::observe(new BaseObserver);
\Capture::observe(new BaseObserver);



