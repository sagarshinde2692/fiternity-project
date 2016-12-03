<?PHP

/**
 * ControllerName : AnalyticsController.
 * Maintains a list of functions used for AnalyticsController.
 *
 * @author Mahesh Jadhav <mjmjadhav@gmail.com>
 */

class AnalyticsController extends \BaseController {

    public function __construct() {
        parent::__construct();
    }

    public function reviews(){

        $data = array();

        $from_date = new DateTime(date('Y-m-d 00:00:00'));
        $to_date = new DateTime(date('Y-m-d 23:59:59'));

        $header = ['Source','Count'];

        $data = $this->getReviewsByDate($from_date,$to_date);

        array_unshift($data,$header);

        return Response::json(array('status' => 200,'data'=>$data),200);
    }

    public function reviewsDiffDay(){

        $data = array();

        $from_date_today = new DateTime(date('Y-m-d 00:00:00'));
        $to_date_today = new DateTime(date('Y-m-d 23:59:59'));

        $data_today = $this->getReviewsByDate($from_date_today,$to_date_today);


        $from_date_yestarday = new DateTime(date('Y-m-d 00:00:00',strtotime("-1 days")));
        $to_date_yestarday = new DateTime(date('Y-m-d 23:59:59',strtotime("-1 days")));

        $data_yestarday = $this->getReviewsByDate($from_date_yestarday,$to_date_yestarday);

        $header = ['Source','Yestarday','Today'];

        $count = count($data_today) ;

        for ($i=0; $i < $count; $i++) { 

            $data[$i] = [$data_today[$i][0],$data_yestarday[$i][1],$data_today[$i][1]];
        }

        array_unshift($data,$header);

        return Response::json(array('status' => 200,'data'=>$data),200);
    }

    public function reviewsDiffMonth(){

        $data = array();

        $from_date_current_month = new DateTime(date('Y-m-01 00:00:00'));
        $to_date_current_month = new DateTime(date('Y-m-d 23:59:59'));
        $current_month = date('M Y');

        $data_current_month = $this->getReviewsByDate($from_date_current_month,$to_date_current_month);


        $from_last_month = new DateTime(date('Y-m-01 00:00:00',strtotime("-1 months")));
        $to_last_month = new DateTime(date('Y-m-d 23:59:59',strtotime("-1 months")));
        $last_month = date('M Y',strtotime("-1 months"));

        $data_last_month = $this->getReviewsByDate($from_last_month,$to_last_month);

        $header = ['Source',$last_month,$current_month];

        $count = count($data_current_month) ;

        for ($i=0; $i < $count; $i++) { 

            $data[$i] = [$data_current_month[$i][0],$data_last_month[$i][1],$data_current_month[$i][1]];
        }

        array_unshift($data,$header);


        return Response::json(array('status' => 200,'data'=>$data),200);
    }

    public function getReviewsByDate($from_date,$to_date){

        $data[] = ['Total', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->count()];
        $data[] = ['Admin', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->where(function($query){$query->orWhere('source','exists',false)->orWhere('source','admin');})->count()];
        $data[] = ['Website', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->where('source','customer')->count()];
        $data[] = ['Android', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->where('source','android')->count()];
        $data[] = ['Ios', Review::active()->where('updated_at', '>=',$from_date)->where('updated_at', '<=',$to_date)->where('source','ios')->count()];

        return $data;

    }

    public function trialsDiffDay(){

        $data = array();

        $from_date_today = new DateTime(date('Y-m-d 00:00:00'));
        $to_date_today = new DateTime(date('Y-m-d 23:59:59'));

        $data_today = $this->getTrialsByDate($from_date_today,$to_date_today);


        $from_date_yestarday = new DateTime(date('Y-m-d 00:00:00',strtotime("-1 days")));
        $to_date_yestarday = new DateTime(date('Y-m-d 23:59:59',strtotime("-1 days")));

        $data_yestarday = $this->getTrialsByDate($from_date_yestarday,$to_date_yestarday);

        $header = ['Type','Yestarday','Today'];

        $count = count($data_today) ;

        for ($i=0; $i < $count; $i++) { 

            $data[$i] = [$data_today[$i][0],$data_yestarday[$i][1],$data_today[$i][1]];
        }

        array_unshift($data,$header);

        return Response::json(array('status' => 200,'data'=>$data),200);
    }

    public function trialsDiffMonth(){

        $data = array();

        $from_date_current_month = new DateTime(date('Y-m-01 00:00:00'));
        $to_date_current_month = new DateTime(date('Y-m-d 23:59:59'));
        $current_month = date('M Y');

        $data_current_month = $this->getTrialsByDate($from_date_current_month,$to_date_current_month);


        $from_last_month = new DateTime(date('Y-m-01 00:00:00',strtotime("-1 months")));
        $to_last_month = new DateTime(date('Y-m-d 23:59:59',strtotime("-1 months")));
        $last_month = date('M Y',strtotime("-1 months"));

        $data_last_month = $this->getTrialsByDate($from_last_month,$to_last_month);

        $header = ['Type',$last_month,$current_month];

        $count = count($data_current_month) ;

        for ($i=0; $i < $count; $i++) { 

            $data[$i] = [$data_current_month[$i][0],$data_last_month[$i][1],$data_current_month[$i][1]];
        }

        array_unshift($data,$header);


        return Response::json(array('status' => 200,'data'=>$data),200);
    }

    public function getTrialsByDate($from_date,$to_date){

        $data[] = ['Requested', Booktrial::where('created_at', '>=',$from_date)->where('created_at', '<=',$to_date)->count()];
        $data[] = ['Booked', Booktrial::where('created_at', '>=',$from_date)->where('created_at', '<=',$to_date)->where('schedule_date_time', 'exists',true)->count()];
        $data[] = ['Schedule', Booktrial::where('schedule_date_time', '>=',$from_date)->where('schedule_date_time', '<=',$to_date)->count()];
        $data[] = ['Fake Buy', Capture::where('created_at', '>=',$from_date)->where('created_at', '<=',$to_date)->where('capture_type','FakeBuy')->count()];

        return $data;

    }

    public function ozonetelCallsDiffDay(){

        $data = array();

        $from_date_today = new DateTime(date('Y-m-d 00:00:00'));
        $to_date_today = new DateTime(date('Y-m-d 23:59:59'));

        $data_today = $this->getOzonetelCallsByDate($from_date_today,$to_date_today);


        $from_date_yestarday = new DateTime(date('Y-m-d 00:00:00',strtotime("-1 days")));
        $to_date_yestarday = new DateTime(date('Y-m-d 23:59:59',strtotime("-1 days")));

        $data_yestarday = $this->getOzonetelCallsByDate($from_date_yestarday,$to_date_yestarday);

        $header = ['Type','Yestarday','Today'];

        $count = count($data_today) ;

        for ($i=0; $i < $count; $i++) { 

            $data[$i] = [$data_today[$i][0],$data_yestarday[$i][1],$data_today[$i][1]];
        }

        array_unshift($data,$header);

        return Response::json(array('status' => 200,'data'=>$data),200);
    }

    public function ozonetelCallsDiffMonth(){

        $data = array();

        $from_date_current_month = new DateTime(date('Y-m-01 00:00:00'));
        $to_date_current_month = new DateTime(date('Y-m-d 23:59:59'));
        $current_month = date('M Y');

        $data_current_month = $this->getOzonetelCallsByDate($from_date_current_month,$to_date_current_month);


        $from_last_month = new DateTime(date('Y-m-01 00:00:00',strtotime("-1 months")));
        $to_last_month = new DateTime(date('Y-m-d 23:59:59',strtotime("-1 months")));
        $last_month = date('M Y',strtotime("-1 months"));

        $data_last_month = $this->getOzonetelCallsByDate($from_last_month,$to_last_month);

        $header = ['Type',$last_month,$current_month];

        $count = count($data_current_month) ;

        for ($i=0; $i < $count; $i++) { 

            $data[$i] = [$data_current_month[$i][0],$data_last_month[$i][1],$data_current_month[$i][1]];
        }

        array_unshift($data,$header);


        return Response::json(array('status' => 200,'data'=>$data),200);
    }

    public function getOzonetelCallsByDate($from_date,$to_date){

        $data[] = ['Requested',Ozonetelcapture::where('created_at', '>=',$from_date)->where('created_at', '<=',$to_date)->count()];
        $data[] = ['Not Connected',Ozonetelcapture::where('created_at', '>=',$from_date)->where('created_at', '<=',$to_date)->where('finder_id', 'exists',false)->count()];
        $data[] = ['Answered',Ozonetelcapture::where('created_at', '>=',$from_date)->where('created_at', '<=',$to_date)->where("call_status","answered")->where('finder_id', 'exists',true)->count()];
        $data[] = ['Not Answered',Ozonetelcapture::where('created_at', '>=',$from_date)->where('created_at', '<=',$to_date)->where("call_status","called")->where('finder_id', 'exists',true)->count()];
        $data[] = ['Disconnected',Ozonetelcapture::where('created_at', '>=',$from_date)->where('created_at', '<=',$to_date)->where("call_status","not_answered")->where('finder_id', 'exists',true)->count()];

        return $data;

    }

    public function vendor(){

        $from_date =  new MongoDate(strtotime(date('2000-01-01 00:00:00')));
        $to_date = new MongoDate(strtotime(date('Y-m-d 23:59:59')));

        $data = $this->getVendorCityCategoryWise($from_date,$to_date);

        return Response::json(array('status' => 200,'data'=>$data),200);

    }

    public function getVendorCityCategoryWise($from_date,$to_date){

        $match['$match']['created_at']['$gte'] = $from_date;
        $match['$match']['created_at']['$lte'] = $to_date;

        $finder = Finder::raw(function($collection) use ($match){

            $aggregate = [];

            $match['$match']['status'] = "1";

            $aggregate[] = $match;

            $group = array(
                        '$group' => array(
                            '_id' => array(
                                'category_id' => '$category_id',
                                'city_id' => '$city_id'
                            ),
                            'count' => array(
                                '$sum' => 1
                            )
                        )
                    );

            $aggregate[] = $group;

            return $collection->aggregate($aggregate);

        });

        $data = array();

        foreach ($finder['result'] as $key => $value) {

            if(isset($value['_id']['city_id']) && isset($value['_id']['category_id'])){
                $data[$value['_id']['city_id']][$value['_id']['category_id']] = $value['count'];
            }

        }

        $result = array();

        foreach ($data as $city_id => $categories) {

            $city = City::find((int)$city_id,array('name'));

            $city_name = ucwords($city['name']);

            $category = array();

            $city_array = array();

            $city_array[] = ['Category','Count'];

            foreach ($categories as $category_id => $count) {

                $category = Findercategory::find((int)$category_id,array('name'));

                $city_array[] = [ucwords($category['name']),$count];
            }

            $result[$city_name] = $city_array;
        }

        return $result;

    }

}
