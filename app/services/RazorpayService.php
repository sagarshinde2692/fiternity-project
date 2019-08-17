<?PHP namespace App\Services;

use Log;
use Config;
use RazorpayPlan;
use RazorpaySubscription;
use Order;
use Customer;

class RazorpayService {

    public function createSubscription ($orderId) {
        
        if (empty($orderId)) {
            return;
        }

        $order = Order::where('status','!=','1')->where('_id', $orderId)->first();
        if(empty($order) || !empty($order['rp_payment_id'])){
            Log::info("Order not found or razorpay payment already done");
            return;
        }
        else if(!empty($order['subscription_id'])) {
            $existingSubscription = RazorpaySubscription::where('subscription_id', $order['subscription_id'])->first();
            if(!empty($existingSubscription)) {
                return $existingSubscription->toArray();
            }
        }
        $ratecardDetails = [
            'type' => $order['type'],
            'amount' => $order['amount']
        ];
        if(!empty($order['pass_id'])) {
            $ratecardDetails['id'] = $order['pass_id'];
            if(!empty($order['pass']['pass_type'])) {
                $ratecardDetails['type'] = $order['pass']['pass_type'];
            }
            if(!empty($order['rp_subscription_amount'])) {
                $ratecardDetails['amount'] = $order['rp_subscription_amount'];
                $ratecardDetails['upfront_amount'] = $order['amount'];
            }
        }
        else if(!empty($order['ratecard_id'])) {
            $ratecardDetails['id'] = $order['ratecard_id'];
        }
        else {
            return;
        }

        
        if(!empty($order['customer_id'])) {
            $rpCustomerId = $this->getRazorpayCustomer($order['customer_id'], $order['customer_name'], $order['customer_email'], $order['customer_phone']);
        }

        if(empty($rpCustomerId)) {
            return;
        }

        $razorpayPlan = RazorpayPlan::where('status', '1')->where('amount', $order['amount'])->first();

        if(empty($razorpayPlan)) {
            $razorpayPlan = $this->getRazorpayPlan($ratecardDetails, true);
        }

        $total_count = Config::get('app.razorpay.subscription.total_count');
        $data = [
            'plan_id' => $razorpayPlan['rp_plan_id'],
            'customer_id' => $rpCustomerId,
            'total_count' => $total_count,
            'start_at'=> strtotime(Config::get('app.razorpay.subscription.interval'), date('d-M-Y', $order['start_date']->sec)),
            'addons' => [
                [
                    'item' => [
                        'name' => 'Initial Payment',
                        'amount' => (!empty($ratecardDetails['upfront_amount']))?($ratecardDetails['upfront_amount']*100):($ratecardDetails['amount']*100),
                        'currency' => Config::get('app.razorpay.currency')
                    ]
                ]
            ]
        ];

        Log::info('subscription details: ', [$data]);

        if(empty($ratecardDetails['upfront_amount'])) {
            unset($data['addons']);
        }
        $subCreationResponse = $this->curlRequest(Config::get('app.razorpay.subscription.url'), $data);
        Log::info('Subscription creation status: ',[ $subCreationResponse ]);
        if(empty($subCreationResponse)) {
            return;
        }

        $modelData = [
            'subscription_id' => RazorpaySubscription::maxId() + 1,
            'plan_id' => $razorpayPlan['plan_id'],
            'order_id' => $orderId,
            'rp_subscription_id' => $subCreationResponse['id'],
            'rp_plan_id' => $subCreationResponse['plan_id'],
            'rp_status' => $subCreationResponse['status'],
            'rp_subscription_amount' => $ratecardDetails['amount'],
            'rp_upfront_amount' => $ratecardDetails['upfront_amount'],
            'rp_start_at' => new \MongoDate($subCreationResponse['start_at']),
            'rp_end_at' => new \MongoDate($subCreationResponse['end_at']),
            'rp_start_at_epoch' => $subCreationResponse['end_at'],
            'rp_end_at_epoch' => $subCreationResponse['start_at'],
            'rp_total_count' => $subCreationResponse['total_count'],
            'rp_customer_notify' => $subCreationResponse['customer_notify'],
            'rp_short_url' => $subCreationResponse['short_url'],
            'rp_expire_by' => $subCreationResponse['expire_by'],
            'status' => '1',
        ];

        if(!empty($order['pass_id'])) {
            $modelData['pass_id'] = $order['pass_id'];
        }
        else if(!empty($order['ratecard_id'])) {
            $modelData['ratecard_id'] = $order['ratecard_id'];
        }

        $subscription = new RazorpaySubscription($modelData);
        $subscription->save();

        $order->subscription_id = $modelData['subscription_id'];
        $order->plan_id = $modelData['plan_id'];
        $order->rp_subscription_id = $modelData['rp_subscription_id'];
        $order->rp_plan_id = $modelData['rp_plan_id'];
        $order->update();

        return $subscription;

    }

    public function getRazorpayPlan($ratecardDetails, $create=false) {
        if(empty($ratecardDetails)){
            return;
        }
        $razorpayPlan = RazorpayPlan::where('status', '1')->where('amount', ($ratecardDetails['amount']))->first();
        if(!empty($razorpayPlan)) {
            Log::info('Plan already exists!');
            return $razorpayPlan->toArray();
        }
        if($create) {
            return $this->createPlan($ratecardDetails);
        }
        return;
    }

    public function getRazorpayCustomer($customerId, $customerName, $customerEmail, $customerContact) {
        if(empty($customerEmail)) {
            return;
        }

        //Checking on email instead of customerid as the customer might have already been created on razorpay with diff acc having same email
        $customer = Customer::where('status', '1')->where('email', $customerEmail)->where('rp_customer_id','exists',true)->first();

        if(!empty($customer['rp_customer_id'])) {
            return $customer['rp_customer_id'];
        }

        $razorpayCustomer = [
            'name' => $customerName,
            'email' => $customerEmail,
            'contact' => $customerContact,
            'notes'=> []
        ];

        $response = $this->curlRequest(Config::get('app.razorpay.customer.url'), $razorpayCustomer);
        if(empty($response['id'])){
            return;
        }
        Customer::where('_id',$customerId)->where('status', '1')->update(['rp_customer_id' => $response['id']]);
        return $response['id'];

    }

    public function createPlan($ratecardDetails) {
        if(empty($ratecardDetails)){
            return;
        }

        $razorpayPlan = [
            'item' => [
                'name' => ('pass-'.$ratecardDetails['id'].'-'.$ratecardDetails['type']),
                'description' => 'Plan for pass: '.$ratecardDetails['id'].' of the type: '.$ratecardDetails['type'],
                'amount' => $ratecardDetails['amount']*100,
                'currency' => Config::get('app.razorpay.currency')
            ],
            'interval' => Config::get('app.razorpay.plan.interval'),
            'period' => Config::get('app.razorpay.plan.period')
        ];

        $planCreationResponse = $this->curlRequest(Config::get('app.razorpay.plan.url'), $razorpayPlan);
        Log::info('Plan creation status: ',[ $planCreationResponse ]);
        if(empty($planCreationResponse)) {
            return;
        }

        $modelData = [
            'plan_id' => RazorpayPlan::maxId() + 1,
            'rp_plan_id' => $planCreationResponse['id'],
            'rp_item_id' => $planCreationResponse['item']['id'],
            'rp_type' => $planCreationResponse['item']['type'],
            'rp_name' => $razorpayPlan['item']['name'],
            'rp_description' => $razorpayPlan['item']['description'],
            'rp_amount' => $ratecardDetails['amount'],
            'rp_currency' => $razorpayPlan['item']['currency'],
            'rp_interval' => $razorpayPlan['interval'],
            'rp_period' => $razorpayPlan['period'],
            'status' => '1',
        ];

        $razorpayPlan = new RazorpayPlan($modelData);
        $razorpayPlan->save();
        return $razorpayPlan->toArray();

    }
    
    public function curlRequest($url, $payload) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_USERPWD, Config::get('app.razorpay.key_id').":".Config::get('app.razorpay.secret_key'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        $output = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $output;
    }

    public function storePaymentDetails($orderId, $paymentId) {
        if(empty($orderId) || empty($paymentId)) {
            return;
        }
        
        
        $repeat_order = Order::where('payment_id', $paymentId)->first();

        if(!empty($repeat_order)){
            return;
        }
        
        $order = Order::where('_id', $orderId)->first();
        $order->update(['rp_payment_id' => $paymentId]);
        RazorpaySubscription::where('subscription_id', $order->rp_subscription_id)->update(['rp_payment_id' => $paymentId]);
        return [
            'order_id' => $orderId,
            'rp_subscription_id' => $order['rp_subscription_id'],
            'rp_payment_id' => $order['rp_payment_id']
        ];
    }

}