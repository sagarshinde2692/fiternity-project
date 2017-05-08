<?php
/** 
 * ModelName : TrainerSchedule.
 * Maintains a list of functions used for Trainer Slots.
 *
 * @author Mahesh Jadhav <utkarshmehrotra@fitternity.com>
 */
class TrainerSchedule extends  \Basemodel {
	
	protected $connection = 'mongodb2';
	
	protected $collection = "trainerschedules";
}