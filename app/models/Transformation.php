<?php

/** 
 * ModelName : Ozonetel.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Transformation extends  \Basemodel {

	protected $collection = "transformations";

	protected $dates = array('reminder_schedule_date');

}