<?php

/** 
 * ModelName : Customer.
 * Maintains a list of functions used for Customer.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Ozonetelcallback extends  \Basemodel {

	protected $collection = "ozonetelcallbacks";

	protected $dates = array('start_time','end_time');

}