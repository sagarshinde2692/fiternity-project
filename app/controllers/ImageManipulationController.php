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
        set_time_limit(0);
        ini_set('memory_limit','2048M');
        // $input = Input::get('image');

        $finder_ids = Watermark::where('watermark_updated', 0)->limit(100)->lists('finder_id');
        // print_r($finder_ids);exit;

        $vendors = Vendor::whereIn('_id',$finder_ids)->select('_id','media.images.gallery')->get()->toArray();
        // print_r($vendors);exit;

        Finder::$withoutAppends = true;
        $finders = Finder::whereIn('_id',$finder_ids)->select('_id','photos')->get()->toArray();
        // print_r($finders);exit();

        $bulk_images_data_fitadmin = [];
        foreach($finders as $finder){
            // print_r($finder);exit;
            $media_photos = $new_photos = [];
            foreach($finder['photos'] as $photos){
                $media_photos = array_merge($media_photos,$photos);
                $media_photos['url'] = 'new/'.$photos['url'];
                $media_photos['old_url'] = $photos['url'];

                array_push($new_photos,$media_photos);
            }

            //prepare data for multiple update photos fitadmin
            array_push($bulk_images_data_fitadmin, [
                "q"=>['_id'=>$finder['_id']],
                "u"=>[
                    '$set'=>[
                        'photos'=>$new_photos,
                    ]
                ],
                'multi' => false
            ]);
        }
        // print_r($bulk_images_data_fitadmin);exit;

        $base_url = Config::get('app.s3_finderurl.gallery').'original/';
        // print_r($base_url);exit;
        $bulk_images_data = $bulk_status_data = [];
        foreach($vendors as $vendor){
            $media_images = $all_media = $new_media = [];
            $all_media['media']['images']['gallery'] = array();
            // print_r($vendor);exit();
            foreach($vendor['media']['images']['gallery'] as $data){
                // print_r($data);exit;
                //determine width & height of image
                $input = $base_url.$data['url'];

                list($width, $height) = @getimagesize($input);

                if(!empty($width) || !empty($height)){
                    // open an image file
                    $img = Image::make($input);

                    // now you are able to resize the instance
                    if($width > $height){
                        // prevent possible upsizing
                        $img->resize(800, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }else{
                        // prevent possible upsizing
                        $img->resize(null, 1200, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }

                    // and insert a watermark after resizing
                    $img->insert(public_path('images/watermark_small.png'),'bottom-right', 10, 10);

                    // finally we save the image as a new file
                    $filename = 'new_image_'.time().mt_rand(1,200).'.jpg';
                    $filepath = public_path($filename);
                    $img->save($filepath);

                    $s3_path = 'f/g/full/new/'.$data['url'];

                    $s3 = \AWS::get('s3');

                    $s3->putObject(array(
                        'Bucket'     => Config::get('app.aws.bucket'),
                        'Key'        => $s3_path,
                        'SourceFile' => $filepath,
                    ));

                    @unlink($filepath);

                    $media_images = array_merge($media_images,$data);
                    $media_images['url'] = 'new/'.$data['url'];
                    $media_images['old_url'] = $data['url'];

                    // array_push($all_media['media']['images']['gallery'],$media_images);
                    // print_r($media_images); exit;
                }else{
                    $media_images = array_merge($media_images,$data);
                }
                array_push($new_media,$media_images);
            }
            // print_r($new_media);exit;

            //prepare data for multiple update fitapi
            array_push($bulk_images_data, [
                "q"=>['_id'=>$vendor['_id']],
                "u"=>[
                    '$set'=>[
                        'media.images.gallery'=>$new_media
                    ]
                ],
                'multi' => false
            ]);

            array_push($bulk_status_data, [
                "q"=>['finder_id'=>$vendor['_id']],
                "u"=>[
                    '$set'=>[
                        'watermark_updated'=>1,
                        'updated_at'=>new MongoDate(time())
                    ]
                ],
                'multi' => false
            ]);

            // Vendor::where('_id',$vendor['_id'])->update($all_media);
            // Watermark::where('finder_id',$vendor['_id'])->update(array('watermark_updated'=>1,'updated_at'=>new MongoDate(time())));

            Log::info('-----------Watermark bulk data prepared for vendor id-----------------',[$vendor['_id']]);
        }

        // print_r($bulk_images_data);
        // print_r($bulk_status_data);
        // exit();

        //batch update in fitapi
        $update_images = $this->batchUpdate('mongodb2', 'vendors', $bulk_images_data);

        //batch update in fitadmin
        $update_photos = $this->batchUpdate('mongodb', 'finders', $bulk_images_data_fitadmin);
        $update_status = $this->batchUpdate('mongodb', 'new_watermark', $bulk_status_data);

        Log::info('----------- Watermark updated successfully -----------------');

        $msg = 'Watermark updated successfully';
        return Response::json(array('status'=>200,'message'=>$msg));
    }

    public function batchUpdate($db, $collection, $update_data){
        $usr="";$opts=[];
        if(!empty(Config::get ( "database.connections.$db.username")))
        {
            $opts=["authMechanism"=>Config::get ( "database.connections.$db.options.authMechanism"),"db"=>Config::get ( "database.connections.$db.options.db")];
            $usr=Config::get ( "database.connections.$db.username" ).":".Config::get ( "database.connections.$db.password" )."@";
        }
        if(!empty($opts)&&!empty($opts['authMechanism'])&&!empty($opts['db']))
        {
            $mongoclient = new \MongoClient(Config::get ( "database.connections.$db.driver" ) . "://".$usr. Config::get ( "database.connections.$db.host" ) . ":" . Config::get ( "database.connections.$db.port").'/'. Config::get ( "database.connections.$db.database"),$opts);
        }
        else {
            $mongoclient = new \MongoClient(Config::get ( "database.connections.$db.driver" ) . "://".$usr. Config::get ( "database.connections.$db.host" ) . ":" . Config::get ( "database.connections.$db.port"));
        }
            
        $pt_collection = $mongoclient->selectDB ( Config::get ( "database.connections.$db.database" ) )->selectCollection ( $collection );
        // return $update_service_data;
        $batch_update = new MongoUpdateBatch($pt_collection);

        foreach($update_data as $item){
            $batch_update->add($item); 
        }
        $update = $batch_update->execute(array("w" => 1));

        $mongoclient->close();
        Log::info($update);
        return $update;
    }
}