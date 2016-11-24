<?php

/** 
 * ModelName : Ozonetel.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class Bdresearch extends  \Basemodel {

	protected $connection = 'mongodb2';
	
	protected $collection = "vendorbdresearchs";

	protected $dates = array('conversation_date');

}