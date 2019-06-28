<?PHP namespace App\Services;

use Log;
use Passes;

Class PassesService {

    public function __construct() {

    }

    Public function listPasses(){
        $passList = Passes:: Active()
        ->select('pass_id', 'amount', 'duaration', 'duration_type', 'type', 'credits')
        ->get();

        return $passList;
    }
}