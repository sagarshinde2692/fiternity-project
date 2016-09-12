<?PHP namespace App\Services;

use \Log;
use \Capture;
use \Ozonetelcapture;


Class Statisticssummary {


    protected $salessummary;
    protected $trialssummary;

    public function __construct(

        Salessummary $salessummary,
        Trialssummary $trialssummary
    )
    {

        $this->salessummary = $salessummary;
        $this->trialssummary = $trialssummary;
    }

    public function getWeeklyDiffInTrials($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end)
    {
        $lastWeekTrials = $this
            ->trialssummary->
            getBookedTrials($finder_id,$previous_week_start,$previous_week_end)
            ->count();
        $currentWeekTrials = $this
            ->trialssummary
            ->getBookedTrials($finder_id,$current_week_start,$current_week_end)
            ->count();

        $result = $currentWeekTrials - $lastWeekTrials;
        return $result;
    }

    public function getWeeklyDiffInLeads($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end)
    {

        $lastWeekTrials = $this
            ->trialssummary
            ->getBookedTrials($finder_id,$previous_week_start,$previous_week_end)
            ->count();
        $currentWeekTrials = $this
            ->trialssummary
            ->getBookedTrials($finder_id,$current_week_start,$current_week_end)
            ->count();

        $lastWeekCaptures = Capture
            ::  where('finder_id', '=', intval($finder_id))
            ->where('capture_type', 'request_callback')
            ->createdBetween($previous_week_start, $previous_week_end)
            ->count();
        $currentWeekCaptures = Capture
            ::  where('finder_id', '=', intval($finder_id))
            ->where('capture_type', 'request_callback')
            ->createdBetween($current_week_start, $current_week_end)
            ->count();

        $lastWeekOzonetelCaptures = Ozonetelcapture
            ::  where('finder_id', '=', intval($finder_id))
            ->where('capture_type', 'request_callback')
            ->createdBetween($previous_week_start, $previous_week_end)
            ->count();
        $currentWeekOzonetelCaptures = Ozonetelcapture
            ::  where('finder_id', '=', intval($finder_id))
            ->createdBetween($current_week_start, $current_week_end)
            ->count();

        $lastWeekLeads = $lastWeekTrials + $lastWeekCaptures + $lastWeekOzonetelCaptures;
        $currentWeekLeads = $currentWeekTrials + $currentWeekCaptures + $currentWeekOzonetelCaptures;
        $result = $lastWeekLeads - $currentWeekLeads;

        return $result;
    }

//    public function getWeeklyDiffInSales($finder_id,$previous_week_start,$previous_week_end,$current_week_start,$current_week_end)
//    {
//        $lastWeekSales = $this
//            ->salessummary
//            ->getBookedSales($finder_id,$previous_week_start,$previous_week_end)
//            ->count();
//        $currentWeekSales = $this
//            ->salessummary
//            ->getBookedSales($finder_id,$current_week_start,$current_week_end)
//            ->count();
//
//        $result = $currentWeekSales - $lastWeekSales;
//        return $result;
//    }

}