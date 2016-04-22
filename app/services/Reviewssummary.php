<?PHP namespace App\Services;

use \Log;
use \Review;

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
            ->createdBetween($start_date, $end_date)
            ->take($limit)
            ->skip($offset)
            ->get(array('rating','detail_rating','description','customer_id','customer','created_at'));

        $reviewData['count'] =  $count;
        $reviewData['data'] =  $data;
        return $reviewData;
    }

}