<?PHP

/**
 * ControllerName : CouponsController.
 * Maintains a list of functions used for CouponsController.
 *
 * @author Nishank Jain <nishankjain@fitternity.com>
 */

class CouponsController extends \BaseController {
	public function __construct() {
		parent::__construct();
	}

	public function getCouponInfo($couponCode, $ticketID) {
		$couponInfo = Coupon::where('code', strtolower($couponCode))->whereIn('tickets', [intval($ticketID)])->get();
		return $couponInfo;
    }
    
    public function removeCustomerFromCoupon($coupon, $customer_phone){

        $coupon = Coupon::where('code', strtolower($coupon))->where('and_conditions.key', 'logged_in_customer.contact_no')->first();
        $couponArray = $coupon->toArray();
        
        if(!empty($couponArray)){
            foreach($couponArray['and_conditions'] as &$condition){
                if($condition['key'] == 'logged_in_customer.contact_no' && in_array($customer_phone, $condition['values'])){
                    array_splice($condition['values'],$customer_phone, 1);
                }
            }
        }
    
        $coupon->update($couponArray);
    
    }
}