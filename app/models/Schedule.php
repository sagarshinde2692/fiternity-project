<?php
/** 
 * ModelName : Schedule.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */
class Schedule extends  \Basemodel {
	
	protected $connection = 'mongodb2';
	
	protected $collection = "schedules";
}