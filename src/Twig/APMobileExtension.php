<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;
	use Twig\TwigFunction;

	use Symfony\Component\DependencyInjection\ContainerInterface;
	
	require_once realpath(__DIR__."/../../vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php");

	class APMobileExtension extends AbstractExtension
	{
		private $container;
		
		public function __construct(ContainerInterface $container)
		{
			$this->container = $container;
		}

		public function getFilters()
		{
			return array(
				new TwigFilter('date_mobile', [$this, 'dateMobileFilter']),
				new TwigFilter('linkFollowMobile', [$this, 'linkFollowMobileFilter']),
				new TwigFilter('imgsizeMobile', [$this, 'imgsizeMobileFilter']),
			);
		}

		public function getFunctions()
		{
			return array(
				new TwigFunction('is_mobile', array($this, 'isMobile')),
				new TwigFunction('is_tablet', array($this, 'isTablet'))
			);
		}

		// Filters
		public function dateMobileFilter($date, $locale)
		{
			switch($locale)
			{
				case "es":
				case "fr":
					$dateStr = $date->format("d/m/Y");
					break;
				case "en":
					$dateStr = $date->format("Y-m-d");
					break;
			}

			return $dateStr;
		}

		public function linkFollowMobileFilter($titleMenu, $currentRoute, $onlyActions = array())
		{
			$explode_currentRoute = explode("_", $currentRoute);
			
			$onlyActionsCondition = (!empty($onlyActions)) ? count(array_intersect($explode_currentRoute, $onlyActions)) > 0 : true;

			if (strpos(urldecode($currentRoute), $titleMenu) !== FALSE and $onlyActionsCondition)
			{
				$class = "active";
			}
			else
				$class = "";
			
			return $class;
		}

		public function imgsizeMobileFilter($file, $path, $useAssetPath = true)
		{
			$realPath = ($useAssetPath) ? $this->container->get('request_stack')->getCurrentRequest()->getBasePath().'/'.$path : $path;

			if($file == "")
				$p = "file_no_exist_".$this->container->get('request_stack')->getCurrentRequest()->getLocale().".png";
			else
				$p = $path.$file;

			if(!file_exists($p))
			{
				$basePath = $this->container->get('request_stack')->getCurrentRequest()->getBasePath();
				$file = "file_no_exist_".$this->container->get('request_stack')->getCurrentRequest()->getLocale().".png";
				$p = "extended/photo/".$file;
				$realPath = ($useAssetPath) ? $basePath."/extended/photo/" : "extended/photo/";
			}
			
			$svg = new \App\Service\ImageSVG($p);
			
			if($svg->isSVG())
				$res = '<object data="'.$realPath.$file.'" class="img-fluid"></object>';
			else
				$res = '<img src="'.$realPath.$file.'" class="img-fluid" alt=""/>';
			
			return $res;
		}
		
		public function isMobile()
		{
			return (new \Mobile_Detect)->isMobile();
		}
		
		public function isTablet()
		{
			return (new \Mobile_Detect)->isTablet();
		}
		
		public function getName()
		{
			return 'ap_mobileextension';
		}
	}