<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\StorageFile;
use Illuminate\Support\Facades\Storage;
use File;
use Illuminate\Support\Facades\Http;
use App\Services\Upload;
use App\Services\GetStreamFileRemote;

class UploadController extends Controller
{
    public function upload(Request $request){
    	$check = $request->hasFile('file');
    	//$mime = $file->extension();
    	if($check){
			$file = $request->File('file');
			$size = $request->file('file')->getSize(); // in bytes
			$ext = $file->extension();

			$token_file = 'congminh-file--'.md5(Hash::make($file->getClientOriginalName()).Carbon::now()->timestamp).'-'.$file->extension().'-'.Carbon::now('Asia/Ho_Chi_Minh')->timestamp;
			$data = null;

			if($ext == 'mp3' || $ext == 'wav' || $ext == 'ogg' || $ext == 'wma' || $size >= 5242880){
				$data = Upload::upload($request->file('file'),md5(Hash::make($file->getClientOriginalName()).Carbon::now()->timestamp).".".$file->extension());
			}

			if(!empty($data['data'])){
				$storage = new StorageFile();
				$storage->token = $token_file;
				$storage->type = $file->extension();
				// $storage->data = base64_encode($blob);
				$storage->path = $data['data'];
				$storage->save();
				return response()->json(['result' => ['status' =>'success','token_file' => $token_file ]]);
			}
    		
    		$imageName = time() . '.' . $file->getClientOriginalExtension();
    		// $path = $file->getRealPath();
			$filenameWithExt = $request->file('file')->getClientOriginalName();
			// $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
			$extension = $request->file('file')->getClientOriginalExtension();
			$fileNameToStore= md5(Hash::make($file->getClientOriginalName()).Carbon::now()->timestamp).".".$file->extension();
			$path = $file->storeAs('public/upload', $fileNameToStore);
    		// $blob = file_get_contents($path);
    		$storage = new StorageFile();
    		$storage->token = $token_file;
    		$storage->type = $file->extension();
    		// $storage->data = base64_encode($blob);
    		$storage->path = $path;
    		$storage->save();
		    return response()->json(['result' => ['status' =>'success','token_file' => $token_file ]]);
		 // good luck
	
	    	
    	}else{
    		return response()->json(['result' => 'File not found !']);
    	}
    	
    }
    public function getFile($token){
    	$getfile = StorageFile::where('token', $token)->firstOrFail();
    	$check_img = $this->checkMimeImg($getfile->type);
    	$sess = random_int(1, 10000);
		if(str_contains($getfile->path, 'https://') || str_contains($getfile->path, 'http://')){
			if($check_img != null){
				$data = GetStreamFileRemote::get($getfile->path);
			}else{
				header('Content-Type: audio/mpeg');
				header('Content-Disposition: attachment; filename="' . $token. '"');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$getfile->path);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 500);
				curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
					$filesize = (int) strlen($data);
					header('Content-Length', $filesize);
	        		header('Accept-Ranges', 'bytes');
	        		header('Content-Range', 'bytes 0-'.$filesize.'/'.$filesize);
					echo $data;
					return strlen($data);
				});
				curl_exec($ch);
				curl_close($ch);
				return 0;
			}
		}else{
			$data = file_get_contents(storage_path("app/".$getfile->path));
		}
    	if($check_img != null){
    		$img = \Image::make($data);
    		return response()->make($img->encode($img->mime()), 200, array('Content-Type' => $img->mime(),'Cache-Control'=>'max-age=86400, public'));

    	}else{
    		$stream = $data;
    		$filesize = (int) strlen($stream);
    		$response = \Response::make($stream, 200);
	        $response->header('Content-Type', 'audio/mpeg');
	        $response->header('Content-Length', $filesize);
	        $response->header('Accept-Ranges', 'bytes');
	        $response->header('Content-Range', 'bytes 0-'.$filesize.'/'.$filesize);
	        return $response;
    	}  	
    }

    protected function checkMimeImg($data){
    	$img = [
    		'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
    	];
    	if(isset($img[$data])){
    		return [$data,$img[$data]];
    	}else{
    		return null;
    	}
    }

}
