<?php

namespace App\Service;

class ResmushIt {
	public function compressFromFilename(string $file, string $filename) {
		$mime = mime_content_type($file);

		$output = new \CURLFile($file, $mime, $filename);
		$data = ["files" => $output];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://api.resmush.it/');
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);

		if ($error = curl_errno($ch))
		   $result = curl_error($ch);

		curl_close ($ch);

		if(!empty($res))
			return $data;

		$res = json_decode($result);

		if(property_exists($res, "error"))
			return $data;

		if($res->src_size > $res->dest_size)
			return file_get_contents($res->dest);

		return $data;
	}

	public function compressFromData(string $data, string $filename) {
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$mime = $finfo->buffer($data);

		$output = new \CURLStringFile($data, $filename, $mime);
		$data = ["files" => $output];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://api.resmush.it/');
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);

		if ($error = curl_errno($ch))
		   $result = curl_error($ch);

		curl_close ($ch);

		if(!empty($res))
			return $data;

		$res = json_decode($result);

		if(property_exists($res, "error"))
			return $data;

		if($res->src_size > $res->dest_size)
			return file_get_contents($res->dest);

		return $data;
	}
}