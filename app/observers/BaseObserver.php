<?php namespace App\Observers;

use Log,Session;
use Transaction;
use Mongodate;

class BaseObserver {

    public function created($model)
    {
        $this->getTransactionData($model);
    }

    public function updated($model)
    {
        $this->getTransactionData($model);
    }

    public function deleted($model)
    {
        $this->getTransactionData($model);
    }

    public function getTransactionData($model){

        $transaction = Transaction::where('transaction_type', get_class($model))->where('reference_id', $model->_id)->first();

        if(!$transaction){
            $transaction = new \Transaction();
        }

        $transaction->transaction_type = get_class($model);
        
        $fields = array(
            '_id',   
            'amount_finder',
            'batch_time',
            'booktrial_type',
            'capture_status',
            'capture_type',
            'cashback',
            'customer_email',
            'customer_id',
            'customer_name',
            'customer_phone',
            'customer_source',
            'email',
            'finder_id',
            'finder_location',
            'finder_name',
            'gender',
            'going_status',
            'name',
            'offer_id',
            'order_action',
            'origin',
            'payment_mode',
            'paymentLinkEmailCustomerTiggerCount',
            'post_trial_status',
            'ratecard_id',
            'repeat_customer',
            'schedule_date_time',
            'schedule_date',
            'service_duration',
            'service_id',
            'service_name',
            'source',
            'status',
            'type',
            'updated_at',
            'utm',
            'created_at',
            'mobile',
            'reward_ids'
        );
            
        foreach($fields as $field){
            
            if(isset($model->$field)){
                if(!(strcmp($field, 'schedule_date_time')) ){

                    $transaction->$field =  new Mongodate($model->$field->timestamp);
                    continue;
                }

                if(!(strcmp($field, 'schedule_date')) ){
                    if(isset($model->$field->timestamp)){
                        $transaction->$field =  new Mongodate($model->$field->timestamp);
                    }else{
                        $transaction->$field =  ($model->$field);
                    }
                    
                    continue;
                }                      

                if(!(strcmp($field, '_id'))){
                    $transaction->reference_id = $model->$field;
                    continue;
                }
                if(!(strcmp($field, 'gender'))){
                        $transaction->customer_gender = $model->$field;
                        continue;
                    }
                if(!(strcmp($field, 'going_status'))){
                        $transaction->booktrial_going_status = $model->$field;
                        continue;
                    }
                if(!(strcmp($field, 'origin'))){
                        $transaction->booktrial_origin = $model->$field;
                        continue;
                    }
                if(!(strcmp($field, 'source'))){
                        $transaction->booktrial_source = $model->$field;
                        continue;
                    }
                if(!(strcmp($field, 'booktrial_type'))){
                    if($transaction->$field == "2ndmanual"){
                        $transaction->booktrial_origin = "manual";
                    }
                    continue;
                }
                if(!(strcmp($field, 'name'))){
                        $transaction->customer_name = $model->$field;
                        continue;
                    }
                if(!(strcmp($field, 'mobile'))){
                        $transaction->customer_phone = $model->$field;
                        continue;
                    }   

                if(!(strcmp($field, 'email'))){
                        $transaction->customer_email = $model->$field;
                        continue;
                    }

                $transaction->$field = $model->$field;
            }
        }

        $transaction->save();
            
    }

}