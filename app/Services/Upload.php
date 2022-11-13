<?php
namespace App\Services;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class Upload{
    private $file = null;
    private $key_api = null;
    private $url = 'https://catbox.moe/user/api.php';

    private static $instance = null;

    private function __construct()
    {
        $this->key_api = Config::get('app.TOKEN_UPLOAD');

    }

    public static function upload(UploadedFile $file, string $name, $quality = null){
        if(empty(self::$instance)){
            self::$instance = new self();
        }
        $stream = file_get_contents($file->getRealPath());
        if(!empty($quality)){
            $stream = self::compressImage($file->getRealPath(), $quality);
        }
        // $base64 = 'data:' . $file->getClientMimeType. ';base64,' . base64_encode($data);
        $response = Http::attach('fileToUpload', $stream, $name)->post(self::$instance->url, [
            'userhash' => self::$instance->key_api,
            'reqtype' => 'fileupload',
        ]);
        
        if($response->successful()) return ['status' => true, 'data' => $response->body()];
        if($response->failed()) return ['status' => false, 'data' => 'status code: '.$response->status()];
        if($response->clientError()) return ['status' => false, 'data' => 'response has a status 400'];
        if($response->serverError()) return ['status' => false, 'data' => 'server error'];
    }

    public static function uploadViaStream($stream){
        if(empty(self::$instance)){
            self::$instance = new self();
        }
        $name = md5(uniqid().Carbon::now()->timestamp);
        $response = Http::attach('fileToUpload', $stream, $name)->post(self::$instance->url, [
            'userhash' => self::$instance->key_api,
            'reqtype' => 'fileupload',
        ]);
        if($response->successful()) return ['status' => true, 'data' => json_decode($response)];
        if($response->failed()) return ['status' => false, 'data' => 'status code: '.$response->status()];
        if($response->clientError()) return ['status' => false, 'data' => 'response has a status 400'];
        if($response->serverError()) return ['status' => false, 'data' => 'server error'];
    }




    public static function compressImage($source_url, $quality) {
        if (!extension_loaded('imagick')){
            throw new \Exception("imagick not found");
        }
        $backgroundImagick = new \Imagick(realpath($source_url));
        $imagick = new \Imagick();
        $imagick->setCompressionQuality($quality);
        $imagick->newPseudoImage(
            $backgroundImagick->getImageWidth(),
            $backgroundImagick->getImageHeight(),
            'canvas:white'
        );
    
        $imagick->compositeImage(
            $backgroundImagick,
            \Imagick::COMPOSITE_ATOP,
            0,
            0
        );
        
        $imagick->setFormat("jpg");    
        // header("Content-Type: image/jpg");
        return $imagick->getImageBlob();
    }
}