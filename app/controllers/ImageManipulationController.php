<?PHP

use App\Mailers\FinderMailer as FinderMailer;
use App\Mailers\CustomerMailer as CustomerMailer;
use App\Services\Sidekiq as Sidekiq;
use App\Services\Bulksms as Bulksms;
use App\Services\Utilities as Utilities;
use App\Sms\CustomerSms as CustomerSms;
use App\Services\Cacheapi as Cacheapi;
use App\Sms\FinderSms as FinderSms;

use \Pubnub\Pubnub as Pubnub;

class ImageManipulationController extends \BaseController {

	public $fitapi;

	public function __construct(FinderMailer $findermailer) {

		$this->findermailer	= $findermailer;
		$this->fitapi = 'mongodb2';

    }

	public function resize_watermark_image(){
        // $input = Input::file('image');

        $finder_ids = Watermark::where('watermark_updated', 0)->lists('finder_id');
        // print_r($finder_ids);exit;
        $vendors = Vendor::whereIn('_id',$finder_ids)->select('_id','media.images.gallery')->get()->toArray();
        // print_r($vendors);exit;
        $base_url = Config::get('app.s3_finderurl.gallery').'original/';
        // print_r($base_url);exit;
        foreach($vendors as $vendor){
            // print_r($vendor['_id']);
            foreach($vendor['media']['images']['gallery'] as $data){
                // print_r($base_url.$data['url']);exit;
                //determine width & height of image
                $input = $base_url.$data['url'];
                list($width, $height) = getimagesize($input);
                // print_r($width);exit;
                // open an image file
                $img = Image::make($input);

                // now you are able to resize the instance
                if($width > $height){
                    $img->resize(800, 533);
                }else{
                    $img->resize(800, 1200);
                }

                // and insert a watermark after resizing
                $img->insert(public_path('images/watermark_small.png'),'bottom-right', 10, 10);

                // finally we save the image as a new file
                $img->save(public_path('new_image'.time().'.jpg'));
                // exit;
            }
        }

        echo 'Saved successfully';
    }
}