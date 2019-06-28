<?PHP
use App\Services\PassesService as PassesService;
class PassesController extends \BaseController {

    public function __construct(PassesService $passesService) {
        parent::__construct();
        $this->passesService = $passesService;
    }

    public function listPassesController(){
        return $this->passesService->listPasses();
    }

}