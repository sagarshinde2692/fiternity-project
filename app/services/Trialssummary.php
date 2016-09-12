<?PHP namespace App\Services;

use \Log;
use \Booktrial;

Class Trialssummary {

    public function getBookedTrials($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->where('schedule_slot', 'exists', true)
            ->where('schedule_slot', '!=', "")
            ->where('schedule_date_time',  '>=', new \DateTime( date("d-m-Y", strtotime( $start_date )) ))
            ->where('schedule_date_time',  '<=', new \DateTime( date("d-m-Y", strtotime( $end_date )) ));
    }

    public function getAttendedTrials($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->where('post_trial_status', 'attended')
            ->createdBetween($start_date, $end_date);
    }

    public function getNotAttendedTrials($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->whereIn('post_trial_status', array('no show', 'unavailable'))
            ->createdBetween($start_date, $end_date);
    }

    public function getUnknownAttendedStatusTrials($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->where('unavailable_count_post_trial','>=','3')
            ->createdBetween($start_date, $end_date);
    }

    public function getTrialsConverted($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->Where(function($query)
            {
                $query->orwhere('going_status_txt', 'purchased')
                    ->orWhere('final_status', 'purchase_confirm');
            })
            ->createdBetween($start_date, $end_date);
    }

    public function getNotInterestedCustomers($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->Where(function($query)
            {
                $query->whereIn('going_status_txt', array('dead','cancel'))
                    ->orWhere('final_status', 'not_interested')
                    ->orWhere('post_trial_initail_status', 'not_interested');
            })
            ->createdBetween($start_date, $end_date);
    }

    public function getUpcomingTrials($finder_id)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->where('schedule_slot', 'exists', true)
            ->where('schedule_slot', '!=', "")
            ->where('schedule_date',  '>=', new \DateTime( date("d-m-Y", time()) ));
    }
}