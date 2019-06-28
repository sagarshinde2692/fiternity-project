<?PHP namespace App\Services;

use Log;
use Config;
use Pass;
use RazorpayPlan;
use RazorpaySubscription;

class RazorpayService {
    
    public function createSubscription ($pass_id) {

        $response = null;
        
        if (empty($pass_id)) {
            return Response::json(array('status' => 400, 'data'=> null, 'msg' => 'Pass id not found.'),$this->error_status);
        }

        $pass = Pass::where('status', '1')->where('pass_id', $pass_id)->first();
        if(empty($pass)){
            Log::info("Pass not found");
            return $response;
        }

        $razorpayPlan = RazorpayPlan::where('status', '1')->where('amount', $pass['amount'])->first();

        if(empty($razorpayPlan)) {
            $razorpayPlan = $this->createPlan($pass);
        }

        $total_count = Config::get('app.razorpay.subscription.total_count');
        $data = [
            'plan_id' => $razorpayPlan['razorpay_plan_id'],
            'total_count' => $total_count,
            'start_at'=> strtotime(Config::get('app.razorpay.subscription.interval')),
            'addons' => [
                [
                    'item' => [
                        'name' => 'Initial Payment',
                        'amount' => $pass['amount'],
                        'currency' => Config::get('app.razorpay.currency')
                    ]
                ]
            ]
        ];
        $subCreationResponse = $this->curlRequest(Config::get('app.razorpay.subscription.url'), $data);
        Log::info('Subscription creation status: ',[ $subCreationResponse ]);
        if(empty($subCreationResponse)) {
            return;
        }
        $subscription = new RazorpaySubscription($subCreationResponse);
        $subscription->save();
        return $subscription;

    }

    public function createPlan($pass) {
        if(empty($pass)){
            return;
        }

        $razorpayPlan = RazorpayPlan::where('status', '1')->where('amount', $pass['amount'])->first();
        if(!empty($razorpayPlan)) {
            Log::info('Plan already exists!');
            return $razorpayPlan->toArray();
        }

        $razorpayPlan = [
            'item' => [
                'name' => ('pass-'.$pass['pass_id'].'-'.$pass['type']),
                'description' => 'Plan for pass: '.$pass['pass_id'].' of the type: '.$pass['type'],
                'amount' => $pass['amount'],
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
            'razorpay_plan_id' => $planCreationResponse['id'],
            'razorpay_item_id' => $planCreationResponse['item']['id'],
            'type' => $planCreationResponse['item']['type'],
            'name' => $razorpayPlan['item']['name'],
            'description' => $razorpayPlan['item']['description'],
            'amount' => $razorpayPlan['item']['amount'],
            'currency' => $razorpayPlan['item']['currency'],
            'interval' => $razorpayPlan['interval'],
            'period' => $razorpayPlan['period']
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

}