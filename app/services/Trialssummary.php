<?PHP namespace App\Services;

use \Log;
use \Booktrial;

Class Trialssummary {

    public function getBookedTrialsCount($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->where('schedule_slot', 'exists', true)
            ->where('schedule_slot', '!=', "")
            ->createdBetween($start_date, $end_date)
            ->count();
    }

    public function getAttendedTrialsCount($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->where('post_trial_status', 'attended')
            ->createdBetween($start_date, $end_date)
            ->count();
    }

    public function getNotAttendedTrialsCount($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->whereIn('post_trial_status', array('no show', 'unavailable'))
            ->createdBetween($start_date, $end_date)
            ->count();
    }

    public function getUnknownAttendedStatusTrialsCount($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->where('unavailable_count_post_trial','>=','3')
            ->createdBetween($start_date, $end_date)
            ->count();
    }

    public function getTrialsConvertedCount($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->where('going_status_txt', 'purchased')
            ->orwhere('final_status', 'purchase_confirm')
            ->createdBetween($start_date, $end_date)
            ->count();
    }

    public function getNotInterestedCustomersCount($finder_id, $start_date, $end_date)
    {

        return Booktrial
            ::  where('finder_id', '=', intval($finder_id))
            ->whereIn('going_status_txt', array('dead','cancel'))
            ->orwhere('final_status', 'not_interested')
            ->orwhere('post_trial_initail_status', 'not_interested')
            ->createdBetween($start_date, $end_date)
            ->count();
    }
}