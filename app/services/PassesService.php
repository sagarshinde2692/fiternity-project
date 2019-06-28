<?PHP namespace App\Services;

use Log;
use Pass;

Class PassesService {

    public function __construct() {

    }

    Public function listPasses(){

        $passList = Pass:: Active()
        ->select('pass_id', 'amount', 'duaration', 'duration_type', 'type', 'credits')
        ->get();

        return array("status" => 200, "data"=> $passList, "msg" => "success");
    }
}