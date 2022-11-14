<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\StorageFile;
use App\Models\DetectAudioUpload;
use Illuminate\Support\Facades\Storage;
use File;
use Config;

class DetectController extends Controller
{
	public function getFile($token){
		$getfile = StorageFile::where('token', $token)->firstOrFail();
		$getDetect = DetectAudioUpload::where('token', $token)->first();
		if($getDetect == null){
			$check_img = $this->checkMimeImg($getfile->type);
	    	if($check_img == null){

				if(str_contains($getfile->path, 'https://') || str_contains($getfile->path, 'http://')){
					$res = $this->fileApiViaUrl($getfile->path, config('app.TOKEN'));
				}else{
					$stream = file_get_contents(storage_path("app/".$getfile->path));
					$tmp = tmpfile();
					fwrite($tmp, $stream);
					fseek($tmp, 0);
					$meta = stream_get_meta_data($tmp);
					$res = $this->fileApi(null, config('app.TOKEN'), $meta['uri'], 'apple_music,spotify', null);
				}

	    		$return = json_decode($res);
				if(empty($return)){
					return \response()->json(['error' => "server error 500"], 500);
				}
				if($return->status == "error"){
					return \response()->json(['error' => "server error 500"], 500);
				}
	    		$saveDetect = new DetectAudioUpload();
	    		$saveDetect->token = $token;
	    		$saveDetect->title = $return->result != null ? $return->result->title : null;
	    		$saveDetect->release_date = $return->result != null ? $return->result->release_date : null;
	    		$saveDetect->album = $return->result != null ? $return->result->album : null;
	    		$saveDetect->label = $return->result != null ? $return->result->label : null;
	    		$saveDetect->timecode = $return->result != null ? $return->result->timecode : null;
	    		$saveDetect->song_link = $return->result != null ? $return->result->song_link : null;
	    		$saveDetect->save();
	    		$lazyload = DetectAudioUpload::where('token', $token)->first();
	    		return \response()->json($lazyload->toArray());
	    	}
		}else{
			return \response()->json($getDetect->toArray());
		}
    	
	}

	protected function fileApiViaUrl($url = null, $token, $return='apple_music,spotify', $param = null){
		$data = [
		    'api_token' => $token,
		    'url' => $url,
		    'return' => $return,
		];
		if($param != null){
			$data = array_merge($data,$param);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, 'https://api.audd.io/');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}


	protected function fileApi($url = null, $token, $tmp, $return, $param = null){
		$data = [
		    'api_token' => $token,
		    'file' => curl_file_create($tmp, 
		        'application/octet-stream', 'file'),
		    'return' => $return,
		];
		if($param != null){
			$data = array_merge($data,$param);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, 'https://api.audd.io/'.$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
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