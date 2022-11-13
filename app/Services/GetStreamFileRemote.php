<?php

namespace App\Services;

class GetStreamFileRemote{
    public static function get($url, $header = false){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 12800);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($DownloadSize, $Downloaded, $UploadSize, $Uploaded) { return ($Downloaded > 1024 * 4096) ? 1 : 0; } ); # max 4096kb

        $version = curl_version();
        if ($version !==FALSE && ($version['features'] & CURL_VERSION_SSL)) { // Curl do support SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec ($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);



        curl_close ($ch);

        $header_blocks =  array_filter(preg_split('#\n\s*\n#Uis' , substr($response, 0, $header_size)));
        $header_array = explode("\n", array_pop($header_blocks));

        $body = substr($response, $header_size);

        if(!$header){
            return $body;
        }

        $header_result = [];

        $headers = [];
        foreach($header_array as $header_value) {
            $header_pieces = explode(':', $header_value);
            if(count($header_pieces) == 2) {
                $headers[strtolower($header_pieces[0])] = trim($header_pieces[1]);
            }
        }

        if (array_key_exists('content-type', $headers)) {
            $ct = $headers['content-type'];
            if (preg_match('#image/png|image/.*icon|image/jpe?g|image/gif|image/webp|image/svg\+xml#', $ct) !== 1) {
                return false;
            }
            $header_result[] = 'Content-Type: ' . $ct;
        } else {
            $header_result[]='HTTP/1.1 404 Not Found';
        }

        if (array_key_exists('content-length', $headers))
            $header_result[]='Content-Length: ' . $headers['content-length'];
        if (array_key_exists('expires', $headers))
            $header_result[]='Expires: ' . $headers['expires'];
        if (array_key_exists('cache-control', $headers))
            $header_result[]='Cache-Control: ' . $headers['cache-control'];
        if (array_key_exists('last-modified', $headers))
            $header_result[]='Last-Modified: ' . $headers['last-modified'];
        return ['body' => $body, 'header' => $header_result];
    }

    public static function getSize($url){
        $ch = curl_init($url);

    $headers = array(
        'Range: bytes=0-1',
        'Connection: close',
    );

    $in_headers = true;
    $size       = null;

    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2450.0 Iron/46.0.2450.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, 0); // set to 1 to debug
    curl_setopt($ch, CURLOPT_STDERR, fopen('php://output', 'r'));

    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $line) use (&$in_headers, &$size) {
        $length = strlen($line);

        if (trim($line) == '') {
            $in_headers = false;
        }

        list($header, $content) = explode(':', $line, 2);
        $header = strtolower(trim($header));

        if ($header == 'content-range') {
            // found a content-range header
            list($rng, $s) = explode('/', $content, 2);
            $size = (int)$s;
            return 0; // aborts transfer
        } else if ($header == 'content-length' && 206 != curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
            // found content-length header and this is not a 206 Partial Content response (range response)
            $size = (int)$content;
            return 0;
        } else {
            // continue
            return $length;
        }
    });

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use ($in_headers) {
        if (!$in_headers) {
            // shouldn't be here unless we couldn't determine file size
            // abort transfer
            return 0;
        }

        // write function is also called when reading headers
        return strlen($data);
    });

    $result = curl_exec($ch);
    $info   = curl_getinfo($ch);

    return $size;
    }
    
}