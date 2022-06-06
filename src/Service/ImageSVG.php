<?php
namespace App\Service;

class ImageSVG
{
	private $file;
	
	public function __construct(String $file)
	{
		$this->file = $file;
	}
	
	public function isSVG()
	{
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string(file_get_contents($this->file));
		
		if(!$xml)
			return false;
		
		return ($xml->getName() == "svg") ? true : false;
	}
	
	public function getSize()
	{
		$xml = simplexml_load_string(file_get_contents($this->file));
		$attributes = $xml->attributes();

		$size[0] = (string)$attributes->width;
		$size[1] = (string)$attributes->height;
		
		return $size;
	}
}