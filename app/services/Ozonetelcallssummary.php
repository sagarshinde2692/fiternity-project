<?PHP namespace App\Services;


use \Log;
use \Ozonetelcapture;

Class Ozonetelcallssummary {

    public function getTotalOzonetelcallsCount($finder_id, $start_date, $end_date)
    {

        $match['$match']['finder_id'] = $finder_id;
        $match['$match']['created_at']['$gte'] = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($start_date))));;
        $match['$match']['created_at']['$lte'] = new \MongoDate(strtotime(date('Y-m-d 00:00:00', strtotime($end_date))));;

        return
            Ozonetelcapture
            ::raw(function($collection) use ($match)
            {
                $aggregate = [];

                if(!empty($match)){
                    $aggregate[] = $match;
                }

                $group = array(
                    '$group' => array(
                        '_id' => array(
                            'finder_id' => '$finder_id',
                            'call_status' => '$call_status',
                        ),
                        'count' => array(
                            '$sum' => 1
                        )
                    )
                );

                $aggregate[] = $group;

                $ozonetel = $collection->aggregate($aggregate);
                $request = [];
                $total=0;

                foreach ($ozonetel['result'] as $key => $value) {

                    if(isset($value['_id']['call_status'])){
                        $request[$value['_id']['call_status']] = $value['count'];
                        $total += $value['count'];
                    }
                }

                $request['answered'] =  isset($request['answered']) ? $request['answered'] : 0;
                $request['not_answered'] =  isset($request['not_answered']) ? $request['not_answered'] : 0;
                $request['called'] =  isset($request['called']) ? $request['called'] : 0;
                $request['total'] =  $total;

                return $request;
            });
    }
    

}