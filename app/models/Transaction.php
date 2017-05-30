<?php
/** 
 * ModelName : Transaction.
 * Maintains a collective data for orders, booktrials and captures.
 *
 * @author Dhruv Sarawagi <dhruvsarawagi@fitternity.com>
 */
class Transaction extends  \Basemodel {
	protected $connection = 'mongodb2';
	public $incrementing = false;
	
	protected $collection = "transactions";
}