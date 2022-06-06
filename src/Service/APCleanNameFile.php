<?php
	namespace App\Service;
	
	class APCleanNameFile
	{
		/**
		 * @param string $titre
		 */
		public function filter($titre)
		{
			$search = array ('@[יטךכÊֻ]@i','@[אגהֲִ]@i','@[מן־ֿ]@i','@[ûשüÛÜ]@i','@[פצװײ]@i','@[ח]@i','@[ ]@i','@[^a-zA-Z0-9_]@', '@[ס]@i');
			$replace = array ('e','a','i','u','o','c','_','n');
			$nf = strtolower(preg_replace($search, $replace, $titre));
			$nf = $nf."-".uniqid().".png";
			return $nf;
		}
	}