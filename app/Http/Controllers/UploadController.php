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
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Methods: GET, HEAD');
				header("Access-Control-Allow-Headers: X-Requested-With");
				header("Content-Transfer-Encoding: binary");
				header("Pragma: no-cache");
				header('Accept-Ranges: bytes');
				header('Content-Type: audio/mpeg');
				header('Accept-Ranges', 'bytes');
				$headers =[];
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$getfile->path);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FILETIME, true);
        		curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_setopt($ch, CURLOPT_HEADERFUNCTION,
				function($curl, $header) use (&$headers)
				{
					$len = strlen($header);
					$header = explode(':', $header, 2);
					if (count($header) < 2) // ignore invalid headers
					return $len;

					$headers[strtolower(trim($header[0]))][] = trim($header[1]);
					
					return $len;
				}
				);
				$response = curl_exec($ch);
				curl_close($ch);
				$headers['content-length'][0] = (int) $headers['content-length'][0];

				// // safari change timeline
				// $media_total = $headers['content-length'][0];
				// $content_length = strlen($media_total);
				// $total_bytes = $content_length;
				// $content_length_1 = $content_length - 1;

				// if (isset($_SERVER['HTTP_RANGE'])) {

				// 	$byte_range = explode('-',trim(str_ireplace('bytes=','',$_SERVER['HTTP_RANGE'])));

				// 	$byte_from = $byte_range[0];
				// 	$byte_to = intval($byte_range[1]);
				// 	$byte_to = $byte_to == 0 ? $content_length_1 : $byte_to;

				// 	$media_total = substr($media_total,$byte_from,$byte_to);

				// 	$content_length = strlen($media_total);

				// 	header('HTTP/1.1 206 Partial Content');
				// }
				// else {
				// 	$byte_from = 0;
				// 	$byte_to = $content_length_1;
				// }

				// $content_range = 'bytes '.$byte_from.'-' . $byte_to . '/' . $total_bytes;

				// // header('Accept-Ranges: bytes');
				// header("Content-Range: ".$content_range);
				// header("Content-length: ".$content_length);
				// header('Content-Transfer-Encoding: binary');
				header('Content-Length: ' . $headers['content-length'][0]);
				header("Content-Range: 0-".($headers['content-length'][0]-1)."/".$headers['content-length'][0]);
				// header('Content-Range', 'bytes 0-'.($headers['content-length'][0] - 1).'/'.$headers['content-length'][0]);
				
				// header('Content-Length: ' . $contentLength);
				// $ch = curl_init();
				// curl_setopt($ch, CURLOPT_URL,$getfile->path);
				// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 500);
				// curl_setopt($ch,CURLOPT_HEADERFUNCTION,function($ch,string $header){
				// 	$ret=strlen($header);
				// 	if(0===stripos($header,"Content-Encoding")){
				// 		return $ret;
				// 	}
				// 	header(substr($header,0,-2));
				// 	return $ret;
				// });
				// curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
				// 	echo $data;
				// 	return strlen($data);
				// });
				// curl_exec($ch);
				// curl_close($ch);
				
				// // header('Content-Disposition: attachment; filename="' . $token. '"');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$getfile->path);

				// curl_setopt($ch, CURLOPT_HEADER, 1);
				// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50000);
				// curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $str) use (&$fp_tmp) {
				// 	$length = fwrite($fp_tmp, $str);
				// 	return $length;
				// });
				// curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
				// 	echo $data;
				// 	return strlen($data);
				// });
				$data=curl_exec($ch);
				// $filesize = (int) strlen($data);
				// if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
				// 	$contentLength = (int)$matches[1];
				//   }
				// header('Content-Length: ' . $contentLength);
				// header('Cache-Control: must-revalidate');
				// header('Content-Range', 'bytes 0-'.$filesize.'/'.$filesize);
				
				curl_close($ch);
				// $data = file_get_contents($fp_tmp);
				echo $data;
				// $context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
				// $data = file_get_contents($getfile->path,false,$context);
				// return 0;
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
