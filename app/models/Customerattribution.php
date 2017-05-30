<?php
/** 
 * ModelName : Customerattribution.
 * Maintains customer attribution data.
 *
 * @author Dhruv
 */
class Customerattribution extends  \Basemodel {
	
    public static $rules = [
			'attribution' => 'required|array',
			'customer_email' => 'required|email',
			'transaction_id' => 'required|integer',
			'transaction_type' => 'required|in:order,booktrial',
    ];

    protected $dates = array('visit_date');

	protected $collection = "customerattribution";
}