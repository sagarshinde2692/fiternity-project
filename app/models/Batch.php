<?php
/** 
 * ModelName : Batch.
 * Maintains a list of functions used for Ozonetel.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */
class Batch extends  \Basemodel {
	protected $connection = 'mongodb2';
	
	protected $collection = "batches";
}