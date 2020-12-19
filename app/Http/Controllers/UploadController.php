<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\StorageFile;
use Illuminate\Support\Facades\Storage;
use File;

class UploadController extends Controller
{
    public function upload(Request $request){
    	$check = $request->hasFile('file');
    	//$mime = $file->extension();
    	if($check){
    		$file = $request->File('file');
    		$token_file = 'VanMin-file--'.md5(Hash::make($file->getClientOriginalName()).Carbon::now()->timestamp).'-'.$file->extension().'-'.Carbon::now('Asia/Ho_Chi_Minh')->timestamp;
    		$imageName = time() . '.' . $file->getClientOriginalExtension();
    		$path = $file->getRealPath();
    		$blob = file_get_contents($path);
    		$storage = new StorageFile();
    		$storage->token = $token_file;
    		$storage->type = $file->extension();
    		$storage->data = base64_encode($blob);
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
    	if($check_img != null){
    		$img = \Image::make($getfile->data);
    		return response()->make($img->encode($img->mime()), 200, array('Content-Type' => $img->mime(),'Cache-Control'=>'max-age=86400, public'));

    	}else{
    		$stream = base64_decode($getfile->data);
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
