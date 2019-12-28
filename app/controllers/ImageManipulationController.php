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
    private $utilities;
	public function __construct(FinderMailer $findermailer, Utilities $utilities) {

		$this->findermailer	= $findermailer;
        $this->fitapi = 'mongodb2';
        $this->utilities = $utilities;

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
                // $filename = $input->getClientOriginalName();
                // print_r($filename);exit;
                list($width, $height) = getimagesize($input);
                // print_r($width);exit;
                // open an image file
                $img = Image::make($input);

                // now you are able to resize the instance
                if($width > $height){
                    // $img->resize(800, 533);

                    // prevent possible upsizing
                    $img->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }else{
                    // $img->resize(800, 1200);

                    // prevent possible upsizing
                    $img->resize(null, 1200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                // and insert a watermark after resizing
                $img->insert(public_path('images/watermark_small.png'),'bottom-right', 10, 10);

                // finally we save the image as a new file
                $filename = 'new_image_'.time().'.jpg';
                $filepath = public_path($filename);
                $img->save($filepath);

                // $img->stream();

                $s3_path = 'f/g/new/'.$data['url'];

                $s3 = \AWS::get('s3');

                $s3->putObject(array(
                    'Bucket'     => Config::get('app.aws.bucket'),
                    'Key'        => $s3_path,
                    'SourceFile' => $filepath,
                ));

                echo $s3_path;
                unlink($filepath);
                exit;
            }
        }

        echo 'Saved successfully';
    }
}