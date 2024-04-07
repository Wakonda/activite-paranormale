<?php
namespace App\Service;

class ImageSVG
{
	private $file;

	public function __construct(String $file) {
		$this->file = $file;
	}

	public function isSVG() {
		return self::isSVGByContent(file_get_contents($this->file));
	}

	public function getSize() {
		return self::getSizeByContent(file_get_contents($this->file));
	}

	public static function isSVGByContent($content) {
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($content);
		
		if(!$xml)
			return false;
		
		return ($xml->getName() == "svg") ? true : false;
	}

	public static function getSizeByContent($content) {
		$xml = simplexml_load_string($content);
		$attributes = $xml->attributes();

		$size[0] = (string)$attributes->width;
		$size[1] = (string)$attributes->height;

		return $size;
	}
}