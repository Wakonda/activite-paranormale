<?php

namespace App\Entity;

trait GenericEntityTrait
{
	public function getEntityName()
	{
		return get_called_class();
	}

	public function getRealClass()
	{
		$classname = get_class($this);

		if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
			$classname = $matches[1];
		}

		return $classname;
	}
	
    public function getPhotoIllustrationFilename(): ?String
    {
		$filename = null;
		if(method_exists($this, "getIllustration") && !empty($this->getIllustration()) && !empty($file = $this->getIllustration()->getRealNameFile())) {
			return $file;
		}

		if(empty($file) && method_exists($this, "getAlternativeIllustration")) {
			$filename = $this->getAlternativeIllustration();
		}
		
		return $filename;
    }
	
    public function getPhotoIllustrationCaption(): ?Array
    {
		if(method_exists($this, "getIllustration") && !empty($this->getIllustration()))
			return [
				"caption" => $this->getIllustration()->getCaption(),
				"source" => ["author" => $this->getIllustration()->getAuthor(), "license" => $this->getIllustration()->getLicense(), "url" => $this->getIllustration()->getUrlSource()]
		    ];
		
		return [];
    }
}