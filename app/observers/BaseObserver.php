<?php namespace App\Observers;

use Log,Session;
use Transaction;
use Mongodate;

class BaseObserver {

    public function created($model)
    {
        $field="_id";
        Log::info(get_class($model).' created for '.$model->$field);

        $this->updateTransaction($model,'created');

    }


    public function updated($model)
    {
        Log::info(get_class($model).' updated for '.$model->_id);

        $this->updateTransaction($model,'updated');
    }


    public function deleted($model)
    {
        Log::info(get_class($model).' deleted for '.$model->_id);

        $this->updateTransaction($model,'deleted');
    }

    public function updateTransaction($model, $activity){
        if(!(strcmp($activity,'created'))){
            Log::info("Inside");
            $transaction = new \Transaction();
            $this->getTransactionData($transaction,$model);
            $transaction->save();
            Log::info("Transaction saved");

        }elseif(!(strcmp($activity,'updated'))){

            $transaction = Transaction::where('transaction_type', get_class($model))->where('reference_id', $model->_id)->first();
            $this->getTransactionData($transaction,$model);
            $transaction->save();
            Log::info("Transaction updated");

        }else{
            $transaction = Transaction::where('transaction_type', get_class($model))->where('reference_id', $model->_id)->first();
            $transaction->delete();
        }
    }

    public function getTransactionData($transaction, $model){
        // Log::info("modelll".get_class($model));
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

            
    }

}