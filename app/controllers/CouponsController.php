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

	public function getCouponInfo($couponCode) {
		$couponInfo = Coupon::where('code', $couponCode)->get();
		return $couponInfo;
	}
}