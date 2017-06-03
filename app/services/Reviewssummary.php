<?PHP namespace App\Services;

use \Log;
use \Review;
use \Finder;

Class Reviewssummary {

    public function getReviews($min_rating, $max_rating, $finder_id, $start_date, $end_date, $limit, $offset)
    {

        $reviewData = [];

        $count = Review
            ::  where('finder_id', '=', intval($finder_id))
            ->whereBetween('rating', [intval($min_rating), intval($max_rating)])
            ->createdBetween($start_date, $end_date)
            ->count();

        $data = Review
            ::where('finder_id', '=', intval($finder_id))
            ->whereBetween('rating', [intval($min_rating), intval($max_rating)])
            ->with(array('customer'=>function($query){$query->select('name');}))
            ->with(array('finder'=>function($query){
                $query->select('category_id')
                ->with(array('category'=>function($query){$query->select('detail_rating');}));

            }))
            ->createdBetween($start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(array('rating','detail_rating','description','reply','customer_id','customer','created_at', 'finder_id'));

        $reviewData['count'] =  $count;
        $reviewData['data'] =  $data;
        return $reviewData;
    }

    public function getAppReviews($min_rating, $max_rating, $finder_id, $start_date, $end_date, $limit, $offset, $reply='')
    {

        $reviewData = [];

        $count = Review
            ::  where('finder_id', '=', intval($finder_id))
            ->whereBetween('rating', [intval($min_rating), intval($max_rating)])
            ->createdBetween($start_date, $end_date)
            ->count();

        $notRepliedCount = Review
            ::  where('finder_id', '=', intval($finder_id))
            ->where('reply', 'exists', false)
            ->whereBetween('rating', [intval($min_rating), intval($max_rating)])
            ->createdBetween($start_date, $end_date)
            ->count();

        $finder = Finder::find(intval($finder_id),['average_rating']);

        $query = Review
            ::where('finder_id', '=', intval($finder_id))
            ->whereBetween('rating', [intval($min_rating), intval($max_rating)]);


        if(isset($reply) && is_bool($reply)){
            $query->where('reply', 'exists', $reply);
        }


        $data = $query->with(array('finder'=>function($query){
                    $query->select('category_id')
                        ->with(array('category'=>function($query){$query->select('detail_rating');}));

                }))
            ->createdBetween($start_date, $end_date)
            ->skip($offset)
            ->take($limit)
            ->get(array('detail_rating','description','reply','customer_id','customer','created_at', 'finder_id', 'replied_at', 'rating'));

        $reviewData['count'] =  $count;
        $reviewData['notRepliedCount'] =  $notRepliedCount;
        $reviewData['rating'] =  $finder['average_rating'];
        $reviewData['data'] =  $data;
        return $reviewData;
    }

}