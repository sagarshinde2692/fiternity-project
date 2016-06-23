<?php

/** 
 * ModelName : Remindercall.
 * Maintains a list of functions used for Remindercall.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Remindercall extends  \Basemodel {

	protected $collection = "remindercalls";

	protected $dates = array('schedule_date','schedule_date_time');


}