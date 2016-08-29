<?php

/** 
 * ModelName : Tickets.
 * Maintains a list of functions used for Tickets.
 *
 * @author Nishank Jain <nishankjain@fitternity.com>
 */

class Ticket extends \Basemodel {
	protected $collection = "tickets";

	protected $dates = array('start_date','end_date');
}