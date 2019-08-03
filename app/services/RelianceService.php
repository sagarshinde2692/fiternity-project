<?php namespace App\Services;

use Log;
use FitnessDeviceData;
use Customer;
use MongoDate;
use Order;
use Config;
use Booktrial;
use Request;
use App\Services\Utilities;

Class RelianceService {

    public function prepareDataForIns($custInfo, $rec, $device, $version) {
        Log::info('----- prepareDataForIns -----');
        Log::info('$custInfo: ', [$custInfo]);
        return [
            'corporate_id' => $custInfo['corporate_id'],
            'customer_id' => $custInfo['_id'],
            'customer_phone' => $custInfo['contact_no'],
            'customer_email' => $custInfo['email'],
            'device_date' => new MongoDate(strtotime($rec['date'])),
            'device_date_text' => $rec['date'],
            'start_time_epoch' => (!empty($rec['startTime']))?$rec['startTime']:null,
            'end_time_epoch' => (!empty($rec['endTime']))?$rec['endTime']:null,
            'device' => $device,
            'app_version' => $version,
            'type' => (!empty($rec['type']))?$rec['type']:'steps',
            'value' => (!empty($rec['value']))?$rec['value']:0,
            'status' => '1',
            'created_at' => new MongoDate(),
            'updated_at' => new MongoDate()
        ];
    }

    public function prepareServiceDataForIns($workoutDetails) {
        Log::info('----- prepareServiceDataForIns -----');
        $rel = [
            'corporate_id' => $workoutDetails['corporate_id'],
            'customer_id' => $workoutDetails['customer_id'],
            'customer_phone' => $workoutDetails['customer_phone'],
            'customer_email' => $workoutDetails['customer_email'],
            'device_date' => new MongoDate($workoutDetails['device_date']),
            'device_date_text' => date('d-m-Y', $workoutDetails['device_date']),
            'type' => (!empty($workoutDetails['type']))?$workoutDetails['type']:'steps',
            'value' => (!empty($workoutDetails['service_steps']))?$workoutDetails['service_steps']:0,
            'order_id' => $workoutDetails['order_id'],
            'booktrial_id' => $workoutDetails['booktrial_id'],
            'status' => '1',
            'created_at' => new MongoDate(),
            'updated_at' => new MongoDate()
        ];
        if(!empty($workoutDetails['device'])) {
            $rel['device'] = $workoutDetails['device'];
        }
        if(!empty($workoutDetails['app_version'])) {
            $rel['app_version'] = $workoutDetails['app_version'];
        }
        return $rel;
    }

    public function getMilestoneDetails($steps, $customer) {
        if(empty($customer['external_reliance'])){
            $milestones = Config::get('relianceLoyaltyProfile.post_register.milestones.data');
        }
        else{
            $milestones = Config::get('nonRelianceLoyaltyProfile.post_register.milestones.data');
        }
        $current = array_values(array_filter($milestones, function($mile) use($steps) {
            $mileNextCount = (!empty($mile['next_count']))?$mile['next_count']:999999999;
            return $mile['count']<=$steps && $mileNextCount>$steps;
        }));
        if(!empty($current) && count($current)>0) {
            $current = $current[0];
        }
        return $current;
    }

    public function getCustomerMilestoneCount($milestone=null) {
        if(!empty($milestone)) {
            $customerMilestoneCount = Customer::raw(function($collection) use ($milestone) {
                $aggregate = [
                    ['$unwind' => '$corporate_rewards.milestones'],
                    ['$match' => [
                        'corporate_rewards.milestones.milestone' => $milestone,
                        'corporate_rewards.milestones.claimed' => true
                    ]],
                    ['$group' => [
                        '_id' => null,
                        'count' => ['$sum' => 1]
                    ]]
                ];
                return $collection->aggregate($aggregate);
            });
            if(!empty($customerMilestoneCount['result'])) {
                return $customerMilestoneCount['result'][0];
            }
            return 0;
        }
        else {
            $customerMilestoneCount = Customer::raw(function($collection) {
                $aggregate = [
                    ['$unwind' => '$corporate_rewards.milestones'],
                    ['$match' => [
                        'corporate_rewards.milestones.claimed' => true
                    ]],
                    ['$group' => [
                        '_id' => '$corporate_rewards.milestones.milestone',
                        'count' => ['$sum' => 1]
                    ]]
                ];
                return $collection->aggregate($aggregate);
            });
            if(!empty($customerMilestoneCount['result'])) {

                // $customerMilestoneCount = array_map(function($rec) {
                //     return $rec['count'];
                // }, $customerMilestoneCount['result']);
                $retArr = [0, 0, 0, 0, 0, 0];
                foreach($customerMilestoneCount['result'] as $key => $val) {
                    $retArr[$val['_id']] = $val['count'];
                }
                return $retArr;
            }
            return [0, 0, 0, 0, 0, 0];
        }
        
    }

    public function updateMilestoneDetails($customerId, $corporateId, $syncTime = null) {
        Customer::$withoutAppends = true;
        $currCustMilestone = Customer::where('_id', $customerId)->first();
        $fitnessDeviceData = FitnessDeviceData::where('customer_id', $customerId)->where('corporate_id', $corporateId)->sum('value');
        if(!empty($fitnessDeviceData)) {
            $milestone = $this->getMilestoneDetails($fitnessDeviceData, $currCustMilestone);
            if($milestone['milestone']>=0) {
                $customerMilestoneCount = $this->getCustomerMilestoneCount($milestone['milestone']);
                if(isset($customerMilestoneCount['result'])) {
                    $customerMilestoneCount = $customerMilestoneCount['result'][0]['count'];
                    $userReachedMilestoneCheck = $customerMilestoneCount<$milestone['users'];
                    $milestoneCheck = (empty($currCustMilestone['corporate_rewards']['milestone'])) || ($currCustMilestone['corporate_rewards']['milestone']!=$milestone['milestone']);
                    if($milestoneCheck) {
                        $milestoneDetails = [
                            'milestone' => $milestone['milestone'],
                            'claimed' => false,
                            'user_count' => ($customerMilestoneCount+1),
                            'achieved' => ($userReachedMilestoneCheck)?true:false,
                            'verified' => true
                        ];
                    }
                    else {
                        $milestoneDetails = null;
                    }
                }
                else {
                    $milestoneDetails = [
                        'milestone' => $milestone['milestone'],
                        'claimed' => false,
                        'user_count' => 1,
                        'achieved' => true,
                        'verified' => true
                    ];
                }

                $updateObj['corporate_rewards'] = (!empty($currCustMilestone->corporate_rewards))?$currCustMilestone->corporate_rewards:[];
                if(!empty($milestoneDetails) && !empty($updateObj['corporate_rewards']['milestones'])) {
                    $milestoneAlreadyExists = array_filter($updateObj['corporate_rewards']['milestones'], function($mile) use ($milestoneDetails) {
                        return $mile['milestone']==$milestoneDetails['milestone'];
                    });
                    if(empty($milestoneAlreadyExists)) {
                        $updateObj['corporate_rewards']['milestones'][] = $milestoneDetails;
                    }
                }
                else {
                    $updateObj['corporate_rewards'] = ['milestones' => [$milestoneDetails]];
                }
                $currCustMilestone->corporate_rewards = $updateObj['corporate_rewards'];
                if(!empty($syncTime)) {
                    $currCustMilestone->fitness_data_last_sync = $syncTime;
                }
                $currCustMilestone->update($updateObj);
            }
        }
        return $currCustMilestone;
    }

    public function updateAppStepCount($custInfo, $data, $device, $version) {
        Log::info('----- inside updateAppStepCount -----');
        $custInfo = (array) $custInfo->customer;
        Log::info('$custInfo: ', [$custInfo]);
        Log::info('$device: ', [$device]);
        Log::info('$version: ', [$version]);

        if(empty($custInfo['corporate_id']) && !empty($custInfo['_id'])) {
            Customer::$withoutAppends = true;
            $custInfo = Customer::where('_id', $custInfo['_id'])->first()->toArray();
        }

        if(!empty($data['data']) && count($data['data'])>0 && !empty($custInfo['corporate_id'])) {

            if(!empty($data['city']) && empty($custInfo['reliance_city'])) {
                Customer::where('_id', $custInfo['_id'])->update(['reliance_city' => strtolower($data['city'])]);
            }

            if((!empty($data['city']) && (!empty($data['location'])) && empty($custInfo['reliance_city'])) || ((!empty($data['city']) && !empty($custInfo['reliance_city']) && ($data['city'] == $custInfo['reliance_city'])))) {
                Customer::where('_id', $custInfo['_id'])->update(['reliance_location' => strtolower($data['location'])]);
            }
            
            $fdData = [];
            
            $lastSyncDate = FitnessDeviceData::where('customer_id', $custInfo['_id'])
                                                ->where('type', 'steps')
                                                ->where('corporate_id', $custInfo['corporate_id'])
                                                ->first(['end_time_epoch']);
            if(!empty($lastSyncDate)) {
                $lastSyncDate = $lastSyncDate->toArray();
            }

            $datesList = array_column($data['data'], 'date');

            $existingFitnessDetails = FitnessDeviceData::where('customer_id', $custInfo['_id'])
                                    ->whereIn('device_date', $datesList)
                                    ->orderBy('device_date', 'asc')
                                    ->get(['device_date', 'type', 'value'])->toArray();

            if(!empty($lastSyncDate['end_time_epoch'])) {
                $lastSyncDate = $lastSyncDate['end_time_epoch'];
            }
            else {
                $lastSyncDate = Config::get('health_config.reliance.start_date');
            }

            if((empty($lastSyncDate)) || (date($lastSyncDate) <= date($data['start_time']))) {
                foreach($data['data'] as $rec) {
                    $_rec = $this->prepareDataForIns($custInfo, $rec, $device, $version);
                    array_push($fdData, $_rec);
                }
            }
        }

        $resp = ['status' => 200, 'data' => 'Success', 'msg' => 'Success'];

        if(!empty($fdData)) {
            FitnessDeviceData::insert($fdData);
            $this->updateMilestoneDetails($custInfo['_id'], $custInfo['corporate_id'], $data['sync_time']);
            $resp['data'] = [
                'health' => $this->buildHealthObject($custInfo['_id'], $custInfo['corporate_id'], $device, null, $version)
            ];
            if(!empty($resp['data']['health']['steps'])){
                unset($resp['data']['health']['steps']);
            }
        }
        else {
            $resp = ['status' => 400, 'data' => 'Failed', 'msg' => 'Current sync time cannot be less than last sync time.'];
        }
        return $resp;
    }

    public function updateServiceStepCount($data) {
        Log::info('----- inside updateAppStepCount -----');
        if(empty($data)) {
            Log::info('$data: empty');
            return;
        }
        Log::info('$data: ', $data);
        if(!empty($data['booktrialId'])) {

            $existingFitnessDetails = FitnessDeviceData::where('booktrial_id', $data['booktrialId'])->first();

            if(!empty($existingFitnessDetails)){
                return ['status' => 200, 'data' => 'Success', 'msg' => 'Already added steps for this session.'];
            }

            $workoutDetails = Booktrial::raw(function($collection) use ($data){
                $aggregate = [
                    ['$match' => ['_id' => $data['booktrialId']]],
                    ['$lookup' => [
                        'from' => 'services',
                        'localField' => 'service_id',
                        'foreignField' => '_id',
                        'as' => 'service'
                    ]],
                    ['$project' => [
                        '_id' => 0,
                        'order_id' => '$order_id',
                        'booktrial_id' => '$_id',
                        'corporate_id' => '$corporate_id',
                        'customer_id' => '$customer_id',
                        'customer_name' => '$customer_name',
                        'customer_email' => '$customer_email',
                        'customer_phone' => '$customer_phone',
                        'device' => '$device_type',
                        'app_version' => '$app_version',
                        'type' => 'service_steps',
                        'servicecategory_id' => ['$arrayElemAt' => ['$service.servicecategory_id', 0]]
                    ]]
                ];
                return $collection->aggregate($aggregate);
            });
        }
        else {
            return;
        }
        $resp = ['status' => 200, 'data' => 'Success', 'msg' => 'Success'];
        if(!empty($workoutDetails['result'][0])) {
            $workoutDetails = $workoutDetails['result'][0];
            $workoutDetails['device_date'] = $data['deviceDate'];
            $serviceStepsMap = Config::get('health_config.service_cat_steps_map');
            if(!empty($serviceStepsMap[$workoutDetails['servicecategory_id']])) {
                $workoutDetails['service_steps'] = $serviceStepsMap[$workoutDetails['servicecategory_id']];
            }
            else {
                $workoutDetails['service_steps'] = 0;
            }

            if(empty($workoutDetails['corporate_id'])){
                return $resp = ['status' => 400, 'data' => 'Failed', 'msg' => 'Not a reliance user'];
            }
            $fdData = $this->prepareServiceDataForIns($workoutDetails);
            $fitnessDeviceData = new FitnessDeviceData($fdData);
            $fitnessDeviceData->save();
            $this->updateMilestoneDetails($workoutDetails['customer_id'], $workoutDetails['corporate_id']);
            if(!empty($data['orderId'])) {
                Order::where('_id', $data['orderId'])->update(['service_steps' => $workoutDetails['service_steps'], 'fitness_device_data_id'=>$fitnessDeviceData['_id']]);
            }

            if(!empty($data['booktrialId'])) {
                Booktrial::where('_id', $data['booktrialId'])->update(['service_steps' => $workoutDetails['service_steps'], 'fitness_device_data_id'=>$fitnessDeviceData['_id']]);
            }

        }
        else {
            $resp = ['status' => 400, 'data' => 'Failed', 'msg' => 'Current sync time cannot be less than last sync time.'];
        }
        return $resp;
        
    }

    public function getCustomerFitnessData($customerId, $corporateId) {
        $stepsAgg = FitnessDeviceData::raw(function($collection) use ($customerId, $corporateId) {
            $aggregate = [
                ['$match' => ['status'=>'1', 'corporate_id'=> $corporateId, 'type' => ['$in' => ['steps', 'service_steps']], 'customer_id' => $customerId]],
                ['$group' => [
                    '_id' => ['customer_id' => '$customer_id'],
                    'ind_total_steps_count_overall' => ['$sum' => '$value'],
                    'ind_foot_steps_count_overall' => ['$sum' => [
                        '$cond' => [ 
                            ['$eq' => ['$type', 'steps']],
                            '$value',
                            0
                        ]
                    ]],
                    'ind_workout_steps_count_overall' => ['$sum' => [
                        '$cond' => [ 
                            ['$eq' => ['$type', 'service_steps']],
                            '$value',
                            0
                        ]
                    ]]
                ]],
                ['$project' => [
                    'ind_total_steps_count_overall' => '$ind_total_steps_count_overall',
                    'ind_foot_steps_count_overall' => '$ind_foot_steps_count_overall',
                    'ind_workout_steps_count_overall' => '$ind_workout_steps_count_overall'
                ]]
            ];
            Log::info('$aggregate: ', $aggregate);
            return $collection->aggregate($aggregate);
        });
        if(!empty($stepsAgg['result'])) {
            return $stepsAgg['result'][0];
        }
        return [
            'ind_total_steps_count_overall' => 0,
            'ind_foot_steps_count_overall' => 0,
            'ind_workout_steps_count_overall' => 0
        ];
    }

    public function getFormattedDate() {
        return date('l\, j F Y');
    }

    public function formatStepsText($stepsCount, $decimals=0) {
        if($stepsCount>9999999) {
            $val = number_format(($stepsCount/1000000), $decimals);
            return $val.'Cr';
        }
        else if($stepsCount>9999) {
            $val = number_format(($stepsCount/1000), $decimals);
            return $val.'K';
        }
        else {
            return strval(number_format($stepsCount));
        }
    }

    public function getAchievementPercentage($totalSteps, $goal, $isCompany=false) {
        if(empty($goal)) {
            return 0;
        }
        if($isCompany) {
            return round((($totalSteps/$goal)*100), 3);
        }
        return floor(($totalSteps/$goal)*100);
    }

    public function getDateDifference($corporateId) {
        // if(empty($corporateId)) {
        //     return 0;
        // }
        // $firstRecord = FitnessDeviceData::where('corporate_id', $corporateId)->whereIn('type', ['steps', 'service_steps'])->first();
        // if(empty($firstRecord)) {
        //     return 0;
        // }
        // $firstDate = $firstRecord->device_date->sec;
        $firstDate = Config::get('health_config.reliance.start_date');
        $diff = (time()-($firstDate));
        $diffDays = ($diff/(60*60*24));
        return ($diffDays>=1)?intval($diffDays):0;
    }

    public function buildHealthObject($customerId, $corporateId, $deviceType=null, $city=null, $appVersion=null) {
        Log::info('----- inside buildHealthObject -----');

        Customer::$withoutAppends = true;
        $customer = Customer::where('_id', $customerId)->first()->toArray();

        $stepsAgg = FitnessDeviceData::raw(function($collection) use ($customerId, $corporateId) {
            $aggregate = [
                ['$match' => ['status'=>'1', 'type' => ['$in' => ['steps', 'service_steps']]]],
                ['$facet' => [
                    'individual_total' => [
                        ['$match' => ['customer_id' => $customerId, 'corporate_id'=> $corporateId]],
                        ['$group' => [
                            '_id' => ['customer_id' => '$customer_id'],
                            'ind_total_steps_count_overall' => ['$sum' => '$value'],
                            'ind_foot_steps_count_overall' => ['$sum' => [
                                '$cond' => [ 
                                    ['$eq' => ['$type', 'steps']],
                                    '$value',
                                    0
                                ]
                            ]],
                            'ind_workout_steps_count_overall' => ['$sum' => [
                                '$cond' => [ 
                                    ['$eq' => ['$type', 'service_steps']],
                                    '$value',
                                    0
                                ]
                            ]]
                        ]]
                    ],
                    'individual' => [
                        ['$match' => ['customer_id' => $customerId, 'corporate_id'=> $corporateId, 'device_date_text'=>date('d-m-Y')]],
                        ['$group' => [
                            '_id' => ['customer_id' => '$customer_id', 'device_date_text' => '$device_date_text'],
                            'ind_total_steps_count' => ['$sum' => '$value'],
                            'ind_foot_steps_count' => ['$sum' => [
                                '$cond' => [ 
                                    ['$eq' => ['$type', 'steps']],
                                    '$value',
                                    0
                                ]
                            ]],
                            'ind_workout_steps_count' => ['$sum' => [
                                '$cond' => [ 
                                    ['$eq' => ['$type', 'service_steps']],
                                    '$value',
                                    0
                                ]
                            ]]
                        ]]
                    ],
                    'corporate' => [
                        ['$group' => [
                            '_id' => null,
                            'corporate_steps_count' => ['$sum' => '$value']
                        ]]
                    ]
                ]],
                ['$project' => [
                    'ind_total_steps_count_overall' => ['$arrayElemAt' => ['$individual_total.ind_total_steps_count_overall', 0]],
                    'ind_foot_steps_count_overall' => ['$arrayElemAt' => ['$individual_total.ind_foot_steps_count_overall', 0]],
                    'ind_workout_steps_count_overall' => ['$arrayElemAt' => ['$individual_total.ind_workout_steps_count_overall', 0]],
                    'ind_total_steps_count' => ['$arrayElemAt' => ['$individual.ind_total_steps_count', 0]],
                    'ind_foot_steps_count' => ['$arrayElemAt' => ['$individual.ind_foot_steps_count', 0]],
                    'ind_workout_steps_count' => ['$arrayElemAt' => ['$individual.ind_workout_steps_count', 0]],
                    'corporate_steps_count' => ['$arrayElemAt' => ['$corporate.corporate_steps_count', 0]]
                ]]
            ];
            Log::info('$aggregate: ', $aggregate);
            return $collection->aggregate($aggregate);
        });

        if(!empty($stepsAgg['result'][0])) {
            $_stepsArr = [
                'ind_total_steps_count_overall' => 0,
                'ind_foot_steps_count_overall' => 0,
                'ind_workout_steps_count_overall' => 0,
                'ind_total_steps_count' => 0,
                'ind_foot_steps_count' => 0,
                'ind_workout_steps_count' => 0,
                'corporate_steps_count' => 0
            ];
            $stepsAgg = array_merge($_stepsArr, $stepsAgg['result'][0]);
        }
        else {
            $stepsAgg = [
                'ind_total_steps_count_overall' => 0,
                'ind_foot_steps_count_overall' => 0,
                'ind_workout_steps_count_overall' => 0,
                'ind_total_steps_count' => 0,
                'ind_foot_steps_count' => 0,
                'ind_workout_steps_count' => 0,
                'corporate_steps_count' => 0
            ];
        }

        $companyProgPercent = $this->getAchievementPercentage($stepsAgg['corporate_steps_count'], Config::get('health_config.corporate_steps.goal'), true);

        if(!empty($companyProgPercent) && $companyProgPercent<0.1 && $companyProgPercent>0) {
            $companyProgPercent = 0.1;
        }

        $footGoal = $this->formatStepsText($stepsAgg['ind_foot_steps_count']);
        $workoutGoal = $this->formatStepsText($stepsAgg['ind_workout_steps_count']);
        if(empty($footGoal) && empty($workoutGoal)) {
            $footGoal = '--';
            $workoutGoal = '--';
        }
        else if(empty($footGoal)) {
            $_temp = strval($workoutGoal);
            $footGoal = '--';
            // $footGoal = preg_replace("/[\s\S]/", "-", $_temp);
        }
        else if(empty($workoutGoal)) {
            $_temp = strval($footGoal);
            $workoutGoal = '--';
            // $workoutGoal = preg_replace("/[\s\S]/", "-", $_temp);
        }
        if(empty($customer['external_reliance'])){
            $milestone_data =Config::get('relianceLoyaltyProfile.post_register.milestones.data');
        }
        else{
            $milestone_data =Config::get('nonRelianceLoyaltyProfile.post_register.milestones.data');
        }
        $nextMilestoneData = array_values(array_filter($milestone_data, function($rec) use ($stepsAgg){
            return $rec['count']>$stepsAgg['ind_total_steps_count_overall'];
        }));
        if(!empty($nextMilestoneData)) {
            $nextMilestoneData = $nextMilestoneData[0];
            $remainingSteps = ($nextMilestoneData['count']-$stepsAgg['ind_total_steps_count_overall']);
        }
        $relCity = (!empty($customer['reliance_city']))?$customer['reliance_city']:null;

        if(!empty($relCity)) {
            $filter = ['filters' => [["header"=>"Cities","subheader" => "Select Subtype","values"=>[["name"=>$relCity,"data"=>[]]]]], "isNewLeaderBoard" => true];
        }
        else {
            $filter = ['filters' => [], "isNewLeaderBoard" => true];
        }

        $filters = $this->getLeaderboardFiltersList((!empty($filter))?$filter:null, (!empty($customer['external_reliance']))?$customer['external_reliance']:null);
        if(!empty($filters)) {
            $parsedFilters = $this->parseLeaderboardFilters($filter['filters']);
        }
        else{
            $parsedFilters = null;
        }
        $ranks = $this->getLeaderboard($customerId, true, $parsedFilters, true);
        $selfRank = null;
        if(!empty($ranks['selfRank'])){
            $selfRank = $ranks['selfRank'];
        }
        $res = [
            'intro'=> [
                'image' => Config::get('health_config.reliance.reliance_logo'),
                'header' => "#WalkpeChal",
                'text' => "MissionMoon | 30 Days | 100 Cr steps"
            ],
            'personal_activity' => [
                'name' => $customer['name'],
                'header' => " Your Activity Today",
                'text' => $this->getFormattedDate(),
                'center_header' => "Your Steps Today",
                'center_text' => $this->formatStepsText($stepsAgg['ind_total_steps_count']),
                'goal' => "Goal : ".$this->formatStepsText(Config::get('health_config.individual_steps.goal')),
                'foot_image' => Config::get('health_config.health_images.foot_image'),
                'foot_steps' => $footGoal,
                'workout_steps' => $workoutGoal,
                'workout_image' => Config::get('health_config.health_images.workout_image'),
                // 'achievement' => "Achievement Level ".$this->getAchievementPercentage($stepsAgg['ind_total_steps_count'], Config::get('health_config.individual_steps.goal')).'%',
                'achievement' => (!empty($relCity))?'#'.$selfRank.' in '.ucwords($relCity):null,
                'remarks' => (!empty($relCity) && !empty($ranks['total']))?'Total participants in '.ucwords($relCity) .' : '.$ranks['total']:null,
                'rewards_info' => 'Your steps till now: '.$this->formatStepsText($stepsAgg['ind_total_steps_count_overall']),
                'target' => Config::get('health_config.individual_steps.goal'),
                'progress' => $stepsAgg['ind_total_steps_count'],
                // 'checkout_rewards' => 'Check Rewards',
                // 'rewards_info' => 'You\'ve covered '.$this->formatStepsText($stepsAgg['ind_total_steps_count_overall']).' total steps & are '.$this->formatStepsText($remainingSteps).' steps away from milestone '.$nextMilestoneData['milestone'].' (Hurry! Eligible for first '.$nextMilestoneData['users'].' users)',
                // 'checkout_rewards' => 'Go to Profile',
                // 'rewards_info' => 'Your steps till now: '.$this->formatStepsText($stepsAgg['ind_total_steps_count_overall']).'.',
                'share_info' => 'Hey! I feel fit today – have completed '.(($deviceType=='android')?'%d':$this->formatStepsText($stepsAgg['ind_total_steps_count'])).' steps on walkpechal – Mission Moon with Reliance Nippon Life Insurance powered with Fitternity',
                'total_steps_count' => $this->formatStepsText($stepsAgg['ind_total_steps_count_overall'])
            ],
            'company_stats' => [
                'header' => "COMPANY STATS",
                'text' => $this->formatStepsText($stepsAgg['corporate_steps_count'])." steps | ".$this->getDateDifference($corporateId)." days so far",
                'button_title' => "View Leaderboard",
                'progress' => $companyProgPercent/100,
                'progress_text' => $companyProgPercent."%"
            ],
            'additional_info' => [
                'header' => "Easy way to get closer to your goals by booking workouts at Gym / studio near you. Use code : ",
                'code' => Config::get('app.reliance_coupon_code'),
                'button_title' => "Book"
            ],
            "steps" => $stepsAgg['ind_total_steps_count_overall']
        ];

        // if(!empty($customer['dob']) || (!empty($customer['corporate_id']) && !empty($customer['external_reliance']) && $customer['external_reliance'])) {
        //     unset($res['personal_activity']['checkout_rewards']);
        //     unset($res['personal_activity']['rewards_info']);
        // }

        if(empty($relCity) || empty($ranks['selfRank'])) {
            unset($res['personal_activity']['achievement']);
            unset($res['personal_activity']['remarks']);
        }

        if(!empty($customer['corporate_id']) && !empty($customer['external_reliance']) && $customer['external_reliance']) {
            $res['company_stats']['header'] = "OVERALL STATS";
            unset($res['personal_activity']['remarks']);
        }

        if(!empty($res['additional_info']) && $deviceType=='android' && $appVersion>5.26) {
            $res['additional_info'] = ((!empty($customer['external_reliance']) && $customer['external_reliance']))?Config::get('health_config.health_booking_android_non_reliance'):Config::get('health_config.health_booking_android_reliance');
        }


        if(!empty($res['additional_info']) && !empty($city)){
            $city = getmy_city($city);
            if(isExternalCity($city)){

                if(empty($customer['external_reliance'])){

                    $res['additional_info']['header'] = 'Loose 5 kgs in 1 month on a personalised diet plan for you or your spouse. Check email from "support@fitternity.com"  to subscribe & get started.';
                    if(!empty($res['additional_info']['code'])){
                        unset($res['additional_info']['code']);
                    }
                    if(!empty($res['additional_info']['button_title'])){
                        unset($res['additional_info']['button_title']);
                    }
                }else{
                    unset($res['additional_info']);
                }
            }
        }


        // if(empty($customer['fitness_data_last_sync'])) {
        //     $res['sync_time'] = Config::get('health_config.reliance.start_date');
        // }
        // else {
        //     $res['sync_time'] = $customer['fitness_data_last_sync'];
        // }
        $res['sync_time'] = Config::get('health_config.reliance.start_date');
        return $res;
    }

    public function getLeaderboard($customerId, $isNewLeaderBoard, $filter=null, $rankOnly = false, $deviceType=null, $appVersion=null) {
        $resp = ['status'=>400, 'data'=>'Failed', 'msg'=>'Failed'];
        if(empty($customerId)) {
            return $resp;
        }

        // $token = Request::header('Authorization');
      
        // if(!empty($token)) {
        //     $custInfo = (new Utilities())->customerTokenDecode($token);
        // }

        $users = [];
        $title = "";
        $earnSteps = Config::get('health_config.leader_board.earn_steps');
        $checkout = Config::get('health_config.leader_board.checkout');
        // $earnSteps['description'] = 'The leaderboard is updated till '.date('d-m-Y', strtotime('-1 days')).' 11:59 PM';
        // $earnSteps['description'] = 'The leaderboard is upto date';


        $customer = Customer::where('_id', $customerId)->where('status', '1')->where('corporate_id', 'exists', true)->first();
        if(empty($customer)){
            return $resp;
        }
        
        $customer = $customer->toArray();
        $customerIds = Customer::active()->where('corporate_id', 'exists', true);
        if(!empty($customer['external_reliance']) && $customer['external_reliance']) {
            $customerIds = $customerIds->where('external_reliance', true)->lists('_id');
        }
        else {
            $customerIds = $customerIds->where('external_reliance', '!=', true)->lists('_id');
        }
        $dt = date('d-m-Y', strtotime('-1 days'));
        $endDate = new MongoDate(strtotime($dt));
        $users = FitnessDeviceData::raw(function($collection) use($customer, $endDate, $filter, $isNewLeaderBoard, $customerIds) {
            $aggregate = [
                [
                    '$match' => [
                        'status' => '1',
                        'corporate_id' => $customer['corporate_id'],
                        'customer_id' => ['$in' => $customerIds],
                        // 'device_date' => ['$lte' => $endDate]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$customer_id',
                        'steps' => [
                            '$sum' => '$value'
                        ]
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'customers',
                        'localField' => '_id',
                        'foreignField' => '_id',
                        'as' => 'cust'
                    ]
                ],
                
            ];
            
            if(!empty($filter)) {
                foreach($filter as $key => $value ) {
                    if(!empty($isNewLeaderBoard)){
                        $match_cities = [
                            '$or' =>[]
                        ];
                        if(strtolower($key)=='cities') {
                            $value_ob = array_map(function($rec){
                                $return = ['city'=>strtolower($rec['name'])];
                                if(!empty($rec['data'])){
                                    $location_value = $rec['data'];
                                    foreach($location_value as &$location){
                                        $location= strtolower($location);
                                    }
                                    $return['location_value']= $location_value;
                                }
    
                                return $return;
    
                            },$value);
                        }
                        
                        else if(strtolower($key) == 'departments'){
    
                           $department =array_map(function($rec){
                                return strtoupper($rec['name']);
                            }, $value);
    
                            Log::info('department', [$department]);
                        }
                    }    
                    else{
                        
                        $match = [];
                        foreach($filter as $key => $value ) {
                            if(strtolower($key)=='cities') {
                                $value = array_map(function($rec){
                                    return strtolower($rec);
                                },$value);
                            }

                            $filterMaster = array_values(array_filter(Config::get('health_config.leader_board.filters'), function($rec) use ($key){
                                return strtolower($rec['name'])==strtolower($key);
                            }));
                            if(!empty($filterMaster)) {
                                $match['cust.'.$filterMaster[0]['field']] = ['$in' => $value];
                            }
                        }

                        if(!empty($match) && count($match)>0) {
                            $aggregate[] = [
                                '$match' => $match
                            ];
                        }
                    }
                }

                if(!empty($isNewLeaderBoard)){ //for new filtess
                    if(!empty($value_ob)){
                        foreach($value_ob as $ob_key=> $ob_value){
                
                            if(!empty($ob_value['location_value'])){
                                array_push($match_cities['$or'],[
                                    'cust.reliance_city' => $ob_value['city'],
                                    'cust.reliance_location'=>['$in'=> $ob_value['location_value']]
                                ]);
                            }
                            else{
                                array_push($match_cities['$or'],[
                                    'cust.reliance_city' => $ob_value['city']]);
                            }
                            
                        }
                    }
                    
                    if(!empty($department)){
                        $match_depart =['cust.reliance_department' => ['$in' => $department]];
                    }
    
                    $match = [
                        '$and' => [
                        ]
                    ];
    
                    if(!empty($match_depart) && !empty($match_cities['$or'])){
                        array_push($match['$and'],  $match_depart);
                        array_push($match['$and'],  $match_cities);   
                    }
                    else if(!empty($match_cities['$or'])){
                        $match =$match_cities;
                    }
    
                    else if(!empty($match_depart)){
                        $match =$match_depart;
                    }
                    else{
                        $match =[];
                    }
                }

                if(!empty($match) && count($match)>0) {
                    $aggregate[] = [
                        '$match' => $match
                    ];
                }
                Log::info('match:::',[$match]);
            }

            $aggregate[] = ['$sort' => [ 'steps' => -1 ]];
            $aggregate[] = ['$project' => [
                'customer_id' => '$_id',
                'name' => ['$arrayElemAt' => ['$cust.name', 0]],
                'designation' => ['$arrayElemAt' => ['$cust.reliance_designation', 0]],
                'department' => ['$arrayElemAt' => ['$cust.reliance_department', 0]],
                'location' => ['$arrayElemAt' => ['$cust.reliance_location', 0]],
                'city' => ['$arrayElemAt' => ['$cust.reliance_city', 0]],
                'steps' => '$steps'
            ]];

            return $collection->aggregate($aggregate);
        });

        if(isset($customer['external_reliance']) && $customer['external_reliance']){
            $title = "Leaderboard - All India";
            $my_rank_text = " in India";
        }else{
            $title = "Leaderboard - RNLIC - All India";
            $my_rank_text = " in India";
        }

        if(!empty($filter)){
            $cityArr = array();
                foreach($filter as $fk => $fv){
                    if(strtolower($fk)=='cities'){
                        foreach($fv as $city){
                            array_push($cityArr, $city['name']);
                        }
                    }
                }

                Log::info("cityArr", [$cityArr]);

                if(!empty($cityArr)){
                    if(isset($customer['external_reliance']) && $customer['external_reliance']){
                        Log::info("tttt");
                        $title = (!empty($cityArr) && count($cityArr) > 1) ? "Leaderboard - ".ucwords($cityArr[0])." +".(count($cityArr)-1)." city" : "Leaderboard - ".$cityArr[0] ;
                        $my_rank_text = (!empty($cityArr) && count($cityArr) > 1) ? " in ".ucwords($cityArr[0])." +".(count($cityArr)-1)." city" : " in ".$cityArr[0] ;
                    }else{
                        Log::info("tttt1");
                        $title = (!empty($cityArr) && count($cityArr) > 1) ? "Leaderboard - RNLIC - ".ucwords($cityArr[0])." +".(count($cityArr)-1)." city" : "Leaderboard - RNLIC - ".$cityArr[0] ;
                        $my_rank_text = (!empty($cityArr) && count($cityArr) > 1) ? " in ".ucwords($cityArr[0])." +".(count($cityArr)-1)." city" : " in ".$cityArr[0] ;
                    }
                }else{
                    if(isset($customer['external_reliance']) && $customer['external_reliance']){
                        $title = "Leaderboard - All India";
                        $my_rank_text = " in India";
                    }else{
                        $title = "Leaderboard - RNLIC - All India";
                        $my_rank_text = " in India";
                    }
                }
            }

            Log::info("title" ,[$title]);
        
        if(!empty($users['result'])) {
            $users = $users['result'];
            $totalUsers = count($users);
            $lastUser = $users[($totalUsers)-1];
            $finalList = array_slice($users,0,20);
            $userExists = array_values(array_filter($finalList, function($val) use ($customerId){
                return $val['customer_id']==$customerId;
            }));
            $selfRank = null;
            // if(empty($userExists)) {
                $_arr = array_filter($users, function($val) use ($customerId){
                    return $val['customer_id']==$customerId;
                });

                if(!empty($_arr) && count($_arr)>0) {
                    $keyList = array_keys($_arr);
                    if(empty($userExists)) {
                        if(
                            !empty($deviceType) 
                            && 
                            (
                                ($deviceType=='android' && !empty($appVersion) && $appVersion>5.26) 
                                || 
                                ($deviceType=='ios' && !empty($appVersion) && $appVersion>= "5.2.1")
                            ) 
                        ) {
                            $_arr[$keyList[0]]['show_dots'] = true;
                            $_arr[$keyList[0]]['rank'] = $keyList[0];
                        }
                        array_push($finalList, $_arr[$keyList[0]]);
                    }
                    $selfRank = $keyList[0];
                    // $finalList[$keyList[0]] = $_arr[$keyList[0]];
                }
            // }

            if(
                !empty($deviceType) 
                && 
                (
                    ($deviceType=='android' && !empty($appVersion) && $appVersion>5.26) 
                    || 
                    ($deviceType=='ios' && !empty($appVersion) && $appVersion >= "5.2.1")
                ) 
                && 
                    (empty($customer['external_reliance']) || !$customer['external_reliance']) 
                && 
                    $totalUsers>20
            ) {
                $lastUser['show_dots'] = true;
                $lastUser['rank'] = strval((count($users))-1);
                $lastUser['last_user'] = true;
                array_push($finalList, $lastUser);
            }

            $return = [
                "total" =>$totalUsers
            ];

            if($rankOnly) {
                if(!empty($selfRank)){
                    $return['selfRank'] =  $selfRank+1;
                    return $return;
                }
                $return['selfRank'] =  null;
                return $return;
            }
            else if (!empty($userExists)) {
                $selfRank=  null;
            }
            $rankToShare = $selfRank;
            $selfStepCount = null;
            foreach ( $finalList as $key => &$value ) {
                if($value['customer_id']==$customerId) {
                    $value['self_color'] = Config::get('health_config.leader_board')["self_color"];;
                    $_selfRank = (!empty($selfRank))?($selfRank.""):(strval(($key+1))."");
                    $rankToShare = $_selfRank;
                    $selfStepCount = $this->formatStepsText($value['steps']);
                }
                else {
                    $_selfRank = null;
                }
                if(empty($value['last_user'])) {
                    $value['rank'] = (!empty($_selfRank))?($_selfRank.""):(($key+1)."");
                }
                $value['steps'] = $this->formatStepsText($value['steps']);
                if($key<3) {
                    $value['image'] = Config::get('health_config.leader_board')["leader_rank".($key+1)];
                    $value['color'] = Config::get('health_config.leader_board')["color_rank".($key+1)];
                }
                $value['designation'] = (!empty($value['designation']))?$value['designation']:'';
                $value['department'] = (!empty($value['department']))?$value['department']:'';
                $value['location'] = (!empty($value['location']))?ucwords($value['location']):'';
                $value['city'] = (!empty($value['city']))?ucwords($value['city']):'';
                $value['description'] = "";
                // if(!empty($value['designation'])) {
                    // $value['description'] = $value['designation'];
                // }
                if(!empty($value['department'])) {
                    $value['description'] = $value['department'];
                }
                if(!empty($value['city'])) {
                    $value['description'] = (!empty($value['description']))?($value['description']." | ".ucwords($value['city'])):ucwords($value['city']);
                }
                if(!empty($value['location'])) {
                    $value['description'] = (!empty($value['description']))?($value['description']." | ".$value['location']):($value['location']);
                }
                if(in_array($value['rank'], [1, 2, 3])) {
                    $value['top'] = true;
                }
                unset($value['designation']);
                unset($value['department']);
                unset($value['city']);
                unset($value['location']);
            }
            $stepCountText = "";
            if(!empty($selfStepCount)) {
                $stepCountText = 'Your steps till now: '.$selfStepCount;
            }

            $leaderBoard = [
                'buildingLeaderboard' => false,
                'background' => Config::get('health_config.leader_board.background'),
                'users' => $finalList,
                'my_rank_text' => !empty($rankToShare)?'Your current rank is #'.$rankToShare.''.ucwords($my_rank_text).".\n ".$stepCountText:' ',
                // 'earnsteps' => $earnSteps,
                'checkout' => $checkout,
                'title' => $title
            ];
            if(!empty($rankToShare)) {
                $leaderBoard['share_info'] = 'I am #'.$this->getRankText($rankToShare).' on the leader-board. Excited to be part of this walk initiative';
            }
            // if(!empty($customer) && !empty($customer['corporate_id']) && !empty($customer['external_reliance']) && $customer['external_reliance']){
            //     unset($leaderBoard['checkout']);
            // }
        } else {
            $leaderBoard = [
                'buildingLeaderboard' => true,
                'no_result_title' => 'Coming Soon!',
                'no_result_text' => 'We are still building leaderboard, please check later.',
                'remarks' => 'Earn steps by attending more sessions! You can earn more steps by taking more workout session on fitternity and increase your rank'
            ];
        }

        $resp = ['status'=>200, 'data'=> $leaderBoard, 'msg'=>'Success'];
        return (!$rankOnly)?$resp:null;
    }

    public function getRankText($rank) {
        return $rank;
        $unit = $rank%20;
        $map = ['st','nd','rd'];
        if($unit>=1&&$unit<=3 && ($rank<11 || $rank>13)){
            return $rank.$map[$unit];
        }
        else {
            return $rank.'th';
        }
    }

    public function getRelianceCustomerDetails($customerEmail) {
        $emailMap = Config::get('health_config.reliance.customer_email_list');
        $relCust = array_values(array_filter($emailMap, function($rec) use ($customerEmail){
            return $rec['email']==$customerEmail;
        }));
        if(!empty($relCust)) {
            return $relCust[0];
        }
        else if(preg_match(Config::get('health_config.reliance.email_pattern'), strtolower($customerEmail))) {
            return ['email' => $customerEmail, 'designation'=> '', 'location'=>''];
        }
        return;
    }

    public function getRelianceCustomerEmailList() {
        $emailMap = Config::get('health_config.reliance.customer_email_list');
        return array_column($emailMap, 'email');
    }

    public function parseLeaderboardFilters($_filters) {
        Log::info('filters data:::::::;', [$_filters]);
        $keys = array_column($_filters, 'header');
        $filters = null;
        foreach($keys as $value) {
            //Log::info();
            $_temp = array_values(array_filter($_filters, function($rec) use ($value) {
                return $rec['header'] == $value;
            }));
            if(!empty($_temp) && count($_temp)>0) {
                $filter_status = false;
                foreach($_temp[0]['values'] as &$filtersValue){
                    if(!empty($filtersValue['name']) && !in_array($filtersValue['name'], ["", "null", "Null"])){
                        $filter_status= true;
                        $filtersValue['name']= ucwords($filtersValue['name']);
                    }
                    if(!empty($filtersValue['data'])){
                        foreach($filtersValue['data'] as &$filtersList){
                            if(empty($filtersList) || in_array($filtersList, ["", "null", "Null"])){
                                $index =array_search($filtersList, $filtersValue['data']);
                                unset($filtersValue['data'][$index]);
                            }
                            else{
                                $filtersList = ucwords($filtersList);
                            }
                        }
                    }
                } 
                if($filter_status){
                    $filters[strtolower($_temp[0]['header'])] = $_temp[0]['values'];
                }
            }
        } 
        Log::info('fileters formated::::::::', [$filters]);
        return $filters;
    }

    public function getLeaderboardFiltersList($input, $external_reliance=null) {
        $filtersMap = Config::get('health_config.leader_board.filters');
        if(empty($external_reliance)) {
            $externalRelianceCondition = ['$exists' => false];
        }
        else {
            $externalRelianceCondition = true;
        }
        if(!empty($input['isNewLeaderBoard'])){
            $_values1 = Customer::raw(function($collection) use ($externalRelianceCondition){
                $match = [
                    '$match' =>[
                        'corporate_id' => 1,
                        'external_reliance' => $externalRelianceCondition
                    ]
                ];
    
                $rawList = [
                    '$group' =>[
                        "_id" => null,
                        'Departments' => [
                            '$addToSet' => '$reliance_department'
                        ]
                    ]
                ];
    
                $locationCityWise = [
                    '$group' =>[
                        "_id" => '$reliance_city',
                        "location" => [
                            '$addToSet' => '$reliance_location'
                        ]
                    ]
                ];
    
                $facet = [
                    $match,
                    [
                        '$facet' =>[
                            'one' =>[
                                $rawList
                            ],
                            "two" =>[
                                $locationCityWise
                            ]
                        ]
                    ]
                ];
                return $collection->aggregate($facet);
            });
            
            $rawData = $_values1['result'][0]['one'];
            $cities = $_values1['result'][0]['two'];
            
            $tmp_depart = [];

            if(!empty($rawData)) {
                $tmp_depart = array_map(function($item){
                    return ['name'=> $item];
                }, $rawData[0]['Departments']);
            }

            $finalFiltersList = [];

            if(empty($external_reliance)){
                sort($tmp_depart);
                $finalFiltersList[] = [
                    'header' => "Departments",
                    'values' => $tmp_depart
                ];
            }
    
            $tmp = [
                'header' => 'Cities',
                'subheader' => 'Select Locality',
                'values' => []
            ];
    
            foreach($cities as $key=>$value){
                if(!empty($value['_id']) && !in_array($value['_id'], ["", "null", "Null"])){
                    $value['name'] = ucwords($value['_id']);
                    foreach($value['location'] as &$location){
                        if(empty($location) || in_array($location, ["", "null", "Null"])){
                            $index = array_search($location, $value['location']);
                            unset($value['location'][$index]);
                        }  
                        else{
                            $location = ucwords($location);
                        }
                    
                    }
                    sort($value['location']);
                    $value['data'] = $value['location'];
                    unset($value['_id']);
                    unset($value['location']);
                    array_push($tmp['values'], $value);
                }
            }

            usort($tmp['values'], function($a, $b) { 
                return $a['name'] < $b['name'] ? -1 : 1; 
            });

            array_push($finalFiltersList, $tmp);
            return $finalFiltersList;
        }
        
        foreach($filtersMap as $filter) {
            Customer::$withoutAppends = true;
            $_values = Customer::active()->where('corporate_id',1)->lists($filter['field']);
            if(!empty($_values) && count($_values)>0) {
                $_values = array_unique($_values);
                $_values = array_values(array_map(function($rec){
                    return ucwords($rec);
                }, $_values));
                $finalFiltersList[] = [
                    'header' => ucwords($filter['name']),
                    'values' => $_values
                ];
            }
        }
        return $finalFiltersList;
    }
    
    public function isRelianceSAPEmailId($customerEmail) {
        if(preg_match(Config::get('health_config.reliance.email_pattern'), strtolower($customerEmail))) {
            return true;
        }
        return false;
    }

    public function getCorporateId($decodedToken = null, $customerId = null){
        $corporateId = null;
        $external_reliance = null;
        $emailList = $this->getRelianceCustomerEmailList();
        if(!empty(Request::header('Authorization'))){
            $decodedToken = (new Utilities())->customerTokenDecode(Request::header('Authorization'));
        }

        if(!empty($decodedToken) && !empty($decodedToken->customer->external_reliance)) {
            $external_reliance = $decodedToken->customer->external_reliance;
        }

        if(!empty($decodedToken) && !empty($decodedToken->customer->corporate_id)) {
            $corporateId = $decodedToken->customer->corporate_id;
        }
        else if (!empty($decodedToken) && !empty($decodedToken->customer->email) && (in_array($decodedToken->customer->email, $emailList) || $this->isRelianceSAPEmailId($decodedToken->customer->email))) {
            $customer = Customer::where('_id', $customerId)->where('status', '1')->first();
            $corporateId = $customer['corporate_id'];
            $external_reliance = null;
        }
        else if(empty($decodedToken) && !empty($customerId)) {
            $customer = Customer::where('_id', $customerId)->where('status', '1')->first();
            $corporateId = $customer['corporate_id'];
            $external_reliance = $customer['external_reliance'];
        }
        return ["corporate_id" =>$corporateId, "external_reliance"=> $external_reliance];
    }

    public function getMilestoneSectionOfreliance($customer, $all_data=false){

        $customer_id = $customer->_id;

        $customerStepData = \FitnessDeviceData::raw(function($collection) use($customer_id){

            $query = [
                [
                    '$match' => [
                        'customer_id' => $customer_id,
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$customer_id',
                        'total_steps' =>[
                            '$sum' => '$value'
                        ]
                    ]
                ]
            ];

            return $collection->aggregate($query);
        });
        Log::info('customer steps count result:::', [$customerStepData, $customer_id]);

        if(empty($customerStepData['result'][0])){
            $customerStepData['total_steps'] = 0;
        }
        else{
            $customerStepData = $customerStepData['result'][0];
        }
        if(empty($customer['external_reliance'])){
            $post_register_milestones = Config::get('relianceLoyaltyProfile.post_register');
        }
        else{
            $post_register_milestones = Config::get('nonRelianceLoyaltyProfile.post_register');
        }
        $milestones = $post_register_milestones['milestones'];
        $rewards = $post_register_milestones['rewards'];
        $total_steps = $customerStepData['total_steps'];
        $milestones_step_counter = 0;
        $milestone_no = 0;
        $remaining_steps = 0;

        foreach($milestones['data'] as $key=>$value){
            $next_milestones_step_counter = (!empty($value['next_count']))?$value['next_count']:999999999;
           
            if($total_steps > $next_milestones_step_counter){   
                
                $post_register_milestones['milestones']['data'][$key]['enabled'] = true;
                $post_register_milestones['milestones']['data'][$key]['progress'] = 100;

            }else if($total_steps < $next_milestones_step_counter){   

                $milestone_no = $key;
                $remaining_steps = (!empty($value['next_count']))?($next_milestones_step_counter - $total_steps):0;
                $current_milestone_step = $value['count'];
                $post_register_milestones['milestones']['data'][$key]['enabled'] = true;
                $post_register_milestones['milestones']['data'][$key]['progress'] = round((($total_steps - $current_milestone_step) / ($next_milestones_step_counter- $current_milestone_step)) *100);
                break;
            
            }
        }  

        if($remaining_steps == 0)
        {
            $post_register_milestones['all_milestones_done'] = true;
        }
        
        $format_array = [
            'customer_name'=> $customer->name,
            "total_steps"=> $this->formatStepsText($total_steps),
            "current_milestone_step"=> $this->formatStepsText($current_milestone_step),
            "next_count"=> $this->formatStepsText($next_milestones_step_counter),
            "milestone_no"=> $milestone_no,
            "remaining_steps"=> $this->formatStepsText($remaining_steps),
            "next_milestone"=> $milestone_no+1,
            "milestone_text"=> 'You are on milestone '.$milestone_no,
            "start_date" => date('d M Y', Config::get('health_config.reliance.start_date'))
        ];

        if($milestone_no==0) {
            $format_array['milestone_text'] = 'Rush to your first milestone to claim rewards.';
        }

        $post_register_milestones['header']['text']= strtr($post_register_milestones['header']['text'], $format_array);
        
        $post_register_milestones['milestones']['subheader'] = strtr($post_register_milestones['milestones']['subheader'], $format_array);
        $post_register_milestones['milestones']['footer'] = strtr($post_register_milestones['milestones']['footer'], $format_array);

        foreach($post_register_milestones['milestones']['data'] as &$milestone_ob){
            if(!empty($milestone_ob['count'])){
                $milestone_ob['count'] = $this->formatStepsText($milestone_ob['count']);
            }
            if(!empty($milestone_ob['next_count'])){
                $milestone_ob['next_count'] = $this->formatStepsText($milestone_ob['next_count']);
            }
        }

        if(!empty($all_data)){

            return ["post_register" =>$post_register_milestones];

        }else{
            
            return $post_register_milestones['milestones'];
        }
        
    }

    public function getStepsByServiceCategory($service_category){
        $steps =  Config::get('health_config.service_cat_steps_map', [])[strval($service_category)];
        return !empty($steps) ? $steps : 300; 
    }

    public function updatedCustomerDOB($custInfo, $data){
        try{
            // $data['dob'] = new MongoDate(strtotime($data['dob']));
            Customer::where('_id', $custInfo->_id)->update(['dob' => $data['dob'], 'dob_updated_by_reliance'=>true, 'reliance_reg_date' => new MongoDate()]); 
        }catch(\Exception $e){
            Log::info('error while updating customer dob:::::::', [$e]);
            return [
                'status'=>400, 
                "msg"=> "something went wrong"
            ];
        }

        return [
            "status"=>200,
            "msg" => "Successfully Updated"
        ];

    }

    public function getFilterForNonReliance($customerId){
        Customer::$withoutAppends = true;
        $reliance_city = Customer::active()->where('_id', $customerId)->where('corporate_id',1)->first(['reliance_city']);
        
        $finalFiltersList =null;
        if(!empty($reliance_city['reliance_city'])){
            $finalFiltersList = ['filters' => [["header"=>"Cities","subheader" => "Select Subtype","values"=>[["name"=>$reliance_city['reliance_city'],"data"=>[]]]]], "isNewLeaderBoard" => true];
        }
        return $finalFiltersList;
    }

}   