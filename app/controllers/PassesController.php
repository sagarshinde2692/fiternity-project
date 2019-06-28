<?PHP
use App\Services\PassesService as PassesService;
class PassesController extends \BaseController {

    public function __construct(PassesService $passesService) {
        parent::__construct();
        $this->passesService = $passesService;
    }

    public function listPasses(){
        return $this->passesService->listPasses();
    }

}