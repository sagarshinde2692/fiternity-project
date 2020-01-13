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

        $vendors = Vendor::whereIn('_id',$finder_ids)->select('_id','media.images.gallery')->get()->toArray();

        Finder::$withoutAppends = true;
        $finders = Finder::whereIn('_id',$finder_ids)->select('_id','photos')->get()->toArray();

        $bulk_images_data_fitadmin = [];
        foreach($finders as $finder){

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

        $base_url = Config::get('app.s3_finderurl.gallery').'original/';

        $bulk_images_data = $bulk_status_data = [];
        foreach($vendors as $vendor){
            $media_images = $all_media = $new_media = [];
            $all_media['media']['images']['gallery'] = array();

            foreach($vendor['media']['images']['gallery'] as $data){

                $input = $base_url.$data['url'];

                $thumb_input = Config::get('app.s3_finderurl.gallery').'thumbs/'.$data['url'];
                
                //determine width & height of image
                list($width, $height) = @getimagesize($input);

                if(!empty($width) || !empty($height)){

                    //returns files path if processed properly else false
                    $filepath = $this->resize_add_watermark($input,$width,$height);

                    $s3_path = 'f/g/full/new/'.$data['url'];
                    $s3_thumb_path = 'f/g/thumbs/new/'.$data['url'];
                    
                    //upload thumbnail
                    $this->uploadS3FromLink($thumb_input,$s3_thumb_path);
                    
                    //upload processed image
                    $this->uploadS3($filepath,$s3_path);

                    @unlink($filepath);

                    $media_images = array_merge($media_images,$data);
                    $media_images['url'] = 'new/'.$data['url'];
                    $media_images['old_url'] = $data['url'];

                }else{
                    $media_images = array_merge($media_images,$data);
                }
                array_push($new_media,$media_images);
            }

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

            Log::info('-----------Watermark bulk data prepared for vendor id-----------------',[$vendor['_id']]);
        }

        //batch update in fitapi
        $update_images = $this->batchUpdate('mongodb2', 'vendors', $bulk_images_data);

        //batch update in fitadmin
        $update_photos = $this->batchUpdate('mongodb', 'finders', $bulk_images_data_fitadmin);
        $update_status = $this->batchUpdate('mongodb', 'new_watermark', $bulk_status_data);

        Log::info('----------- Watermark updated successfully -----------------');

        $msg = 'Watermark updated successfully';
        return Response::json(array('status'=>200,'message'=>$msg));
    }

    public function uploadS3($filepath,$s3_path){
        try{
            $s3 = \AWS::get('s3');

            $s3->putObject(array(
                'Bucket'     => Config::get('app.aws.bucket'),
                'Key'        => $s3_path,
                'SourceFile' => $filepath,
            ));
        }catch(Exception $e){
            Log::info($e);
            return false;
        }
    }

    public function uploadS3FromLink($thumb_input,$s3_thumb_path){
        try{
            $s3 = \AWS::get('s3');

            $s3->putObject(array(
                'Bucket'     => Config::get('app.aws.bucket'),
                'Key'        => $s3_thumb_path,
                'ContentType' =>'image/jpeg',
                'Body' => file_get_contents($thumb_input)
            ));
        }catch(Exception $e){
            Log::info($e);
            return false;
        }
    }

    public function resize_add_watermark($input,$width,$height){
        try{
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
            return $filepath;
        }catch(Exception $e){
            Log::info($e);
            return false;
        }
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