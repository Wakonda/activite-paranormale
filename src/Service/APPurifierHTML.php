<?php
	namespace App\Service;

	use Symfony\Component\DependencyInjection\ContainerInterface;
	
	class APPurifierHTML
	{
		public function purifier($string)
		{
			return $this->HTMLPurifier($string);
		}

		public function HTMLPurifier($string)
		{
			$config = array(
			   'show-body-only' => true,
			   'new-inline-tags' => 'video,source'
		    );

			$tidy = new \tidy();
			$tidy->parseString($string, $config, 'utf8');
			$tidy->cleanRepair();

			return $tidy->value;
		}
	}