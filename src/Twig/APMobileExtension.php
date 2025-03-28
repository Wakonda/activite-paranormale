<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Service\FunctionsLibrary;

use Symfony\Contracts\Translation\TranslatorInterface;
use Detection\MobileDetect;

class APMobileExtension extends AbstractExtension
{
	private $translator;
	
	public function __construct(TranslatorInterface $translator)
	{
		$this->translator = $translator;
	}

	public function getFilters()
	{
		return [
			new TwigFilter('date_mobile', [$this, 'dateMobileFilter']),
			new TwigFilter('linkFollowMobile', [$this, 'linkFollowMobileFilter']),
			new TwigFilter('imgsizeMobile', [$this, 'imgsizeMobileFilter']),
		];
	}

	public function getFunctions()
	{
		return [
			new TwigFunction('is_mobile', [$this, 'isMobile']),
			new TwigFunction('is_tablet', [$this, 'isTablet']),
			new TwigFunction('is_application', [$this, 'isApplication'])
		];
	}

	// Filters
	public function dateMobileFilter($dateTime, $locale)
	{
		return (new \App\Service\APDate())->shortDate($dateTime, $locale);
	}

	public function linkFollowMobileFilter($titleMenu, $currentRoute, $onlyActions = [])
	{
		$explode_currentRoute = explode("_", $currentRoute);
		
		$onlyActionsCondition = (!empty($onlyActions)) ? count(array_intersect($explode_currentRoute, $onlyActions)) > 0 : true;

		if (strpos(urldecode($currentRoute), $titleMenu) !== FALSE and $onlyActionsCondition)
			$class = "active";
		else
			$class = "";
		
		return $class;
	}

	public function imgsizeMobileFilter($file, $path, $useAssetPath = true)
	{
		$realPath = ($useAssetPath) ? '/'.$path : $path;

		if($file == "")
			$p = "file_no_exist_".$this->translator->getLocale().".png";
		else
			$p = $path.$file;

		if(!file_exists($p))
		{
			$file = "file_no_exist_".$this->translator->getLocale().".png";
			$p = "extended/photo/".$file;
			$realPath = ($useAssetPath) ? "/extended/photo/" : "extended/photo/";
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
		return (new MobileDetect())->isMobile();
	}
	
	public function isTablet()
	{
		return (new MobileDetect())->isTablet();
	}

	public function isApplication() {
		return (new FunctionsLibrary())->isApplication();
	}
	
	public function getName()
	{
		return 'ap_mobileextension';
	}
}