<?php
	namespace App\Service;
	
	use App\Service\FunctionsLibrary;
	use Symfony\Contracts\Translation\TranslatorInterface;
	
	class APImgSize
	{
		private $translator;
		private $locale;

		public function __construct(TranslatorInterface $translator = null)
		{
			$this->translator = $translator;
			
			if(!empty($this->translator))
				$locale = $this->translator->getLocale();
			else {
				$attributeBag = new \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag ();
				$locale = $_SESSION[$attributeBag->getStorageKey()]["_locale"];
			}

		}

		public function adaptImageSize($width, $file, $unit = "px")
		{
			$newLarg = 0.0;
			$newLong = 0.0;

			if(!is_file($file) or empty($file) or (!FunctionsLibrary::isUrl($file) and !file_exists($file))) {
				$locale = $this->translator->getLocale();
				$file = "extended/photo/file_no_exist_".$locale.".png";
			}

			$svg = new \App\Service\ImageSVG($file);

			$info = ($svg->isSVG()) ? $svg->getSize() : getimagesize($file);

			$eX = 0.0;
			
			if($info[0] > $width)
			{
				$eX = $width / $info[0];
				$newLarg = $eX * $info[0];
				$newLong = $eX * $info[1];
			}
			else
			{
				$newLarg = $info[0];
				$newLong = $info[1];
			}

			$newLarg = empty($newLarg) ? "fit-content" : round($newLarg).$unit;
			$newLong = empty($newLong) ? "fit-content" : round($newLong).$unit;

			return [$newLarg, $newLong, $file];
		}
	}