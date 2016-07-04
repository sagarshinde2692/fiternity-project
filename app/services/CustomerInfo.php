<?PHP namespace App\Services;

Class CustomerInfo {

    public function addHealthInfo ($data = false){

        if(!$data){
            $error = [  'status'=>400,
                    'reason'=>'data not found'
            ];
            return $error;
        }

        try {

            $param = array('customer_id','customer_name','customer_email','customer_phone','medical_detail','medication_detail','physical_activity_detail');

            $info = array();

            foreach ($param as $value) {

                if(array_key_exists($value, $data)){
                    $info[$value] = $data[$value]; 
                }
            }

            if(!empty($info)){

                $healthinfo = new \CustomerHealthInfo($info);
                $healthinfo->_id = \CustomerHealthInfo::max('_id') + 1;
                $healthinfo->status = "1";
                $healthinfo->save();

            }

            $return = array('status'=>200,'messsage'=>'info added');

            return $return;
            
        } catch (Exception $e) {

            Log::error($e);

            $return = array('status'=>400,'messsage'=>'error');

            return $return;
            
        }

    }

   
}