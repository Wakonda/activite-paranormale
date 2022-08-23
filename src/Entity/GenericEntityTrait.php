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
		if(method_exists($this, "getIllustration") and !empty($this->getIllustration()) and !empty($file = $this->getIllustration()->getRealNameFile()))
			return $file;
		
		return null;
    }
	
    public function getPhotoIllustrationCaption(): ?Array
    {
		if(method_exists($this, "getIllustration") and !empty($this->getIllustration()))
			return [
				"caption" => $this->getIllustration()->getCaption(),
				"source" => ["author" => $this->getIllustration()->getAuthor(), "license" => $this->getIllustration()->getLicense(), "url" => $this->getIllustration()->getUrlSource()]
		    ];
		
		return [];
    }
}