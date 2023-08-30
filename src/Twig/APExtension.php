<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;
	use Twig\TwigFunction;

	use Symfony\Component\Finder\Finder;
	use Doctrine\ORM\EntityManagerInterface;
	use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
	use Symfony\Contracts\Translation\TranslatorInterface;
	use Symfony\Component\HttpFoundation\RequestStack;
	use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
	
	use App\Entity\Language;
	use App\Entity\Banner;
	use App\Entity\DocumentFamily;
	use App\Entity\State;
	use App\Entity\Video;
	use App\Entity\Theme;
	use App\Entity\Biography;
	use App\Entity\Tags;
	use App\Entity\SurThemeGrimoire;
	use App\Service\TwitterAPI;
	use App\Service\GoogleBlogger;
	use App\Service\TheDailyTruth;
	use App\Service\PinterestAPI;
	use App\Service\TumblrAPI;
	use App\Service\Shopify;
	use App\Service\APParseHTML;
	use App\Service\Captcha;

	class APExtension extends AbstractExtension
	{
		private $em;
		private $router;
		private $translator;
		private $parameterBag;
		private $requestStack;
		
		public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $router, TranslatorInterface $translator, ParameterBagInterface $parameterBag, RequestStack $requestStack)
		{
			$this->em = $em;
			$this->router = $router;
			$this->translator = $translator;
			$this->parameterBag = $parameterBag;
			$this->requestStack = $requestStack;
		}
		
		public function getFilters()
		{
			return array(
				new TwigFilter('urlclean', array($this, 'urlcleanFilter')),
				new TwigFilter('imgsize', array($this, 'imgsizeFilter')),
				new TwigFilter('displayPrivateFile', array($this, 'displayPrivateFileFilter')),
				new TwigFilter('displayPrivatePDF', array($this, 'displayPrivatePDFFilter'), array('is_safe' => array('html'))),
				new TwigFilter('dodate', array($this, 'dodateFilter')),
				new TwigFilter('doPartialDate', array($this, 'doPartialDateFilter')),
				new TwigFilter('doPartialDateTime', array($this, 'doPartialDateTimeFilter')),
				new TwigFilter('doYearMonthDayDate', array($this, 'doYearMonthDayDateFilter')),
				new TwigFilter('advertisement', array($this, 'advertisementFilter')),
				new TwigFilter('linkfollow', array($this, 'linkFollowFilter')),
				new TwigFilter('is_image', array($this, 'isImageFilter')),
				new TwigFilter('get_real_class', array($this, 'getRealClassFilter')),
				new TwigFilter('strip_tags', array($this, 'stripTagsFilter')),
				new TwigFilter('first', array($this, 'firstFilter')),
				new TwigFilter('urlencode', array($this, 'urlEncodeFilter')),
				new TwigFilter('HTMLPurifier', array($this, 'HTMLPurifierFilter')),
				new TwigFilter('getDocumentFamilyRealName', array($this, 'getDocumentFamilyRealNameFilter')),
				new TwigFilter('utf8_encode', array($this, 'utf8EncodeFilter')),
				new TwigFilter('formatTextForPDFVersion', array($this, 'formatTextForPDFVersionFilter')),
				new TwigFilter('addslashes', array($this, 'addslashesFilter')),
				new TwigFilter('random_banner', array($this, 'getRandomBannerForIndexFilter')),
				new TwigFilter('states_by_language', array($this, 'getAllStatesByLanguageFilter')),
				new TwigFilter('string_duration', array($this, 'stringDurationVideoFilter')),
				new TwigFilter('duration_entities', array($this, 'getDurationByEntities')),
				new TwigFilter('str_replace', array($this, 'strReplaceFilter')),
				new TwigFilter('removeStyleAttributeFromHtmlTags', array($this, 'removeStyleAttributeFromHtmlTagsFilter')),
				new TwigFilter('ucfirst', array($this, 'UcfirstFilter')),
				new TwigFilter('imgCaption', array($this, 'imgCaptionFilter'))
			);
		}

		public function getFunctions()
		{
			return array(
				new TwigFunction('base64_encode', array($this, 'base64Encode')),
				new TwigFunction('json_decode', array($this, 'jsonDecode')),
				new TwigFunction('get_tags', array($this, 'getTagsByEntity'), array('is_safe' => array('html'))),
				new TwigFunction('getTagsByEntityForDisplay', array($this, 'getTagsByEntityForDisplay'), array('is_safe' => array('html'))),
				new TwigFunction('biography_correct_language', array($this, 'getBiographyInCorrectLanguage')),
				new TwigFunction('count_availability', array($this, 'countAvailability')),
				new TwigFunction('count_archived', array($this, 'countArchivedEntries')),
				new TwigFunction('is_current_languages', array($this, 'isCurrentLanguages')),
				new TwigFunction('method_exists', array($this, 'methodExists')),
				new TwigFunction('file_exists', array($this, 'fileExists')),
				new TwigFunction('captcha', array($this, 'generateCaptcha')),
				new TwigFunction('blogger_tags', array($this, 'getBloggerTags')),
				new TwigFunction('blogger_list', array($this, 'getBloggerList')),
				new TwigFunction('blogger_id', array($this, 'getBloggerId')),
				new TwigFunction('thedailytruth_tags', array($this, 'getTheDailyTruthTags')),
				new TwigFunction('slug', array($this, 'slugifyUrl')),
				new TwigFunction('img_size_html2pdf', array($this, 'getImageSizeHTML2PDF')),
				new TwigFunction('entities_other_languages', array($this, 'getEntitiesOtherLanguages')),
				new TwigFunction('isTwitterAvailable', array($this, 'isTwitterAvailable')),
				new TwigFunction('isBloggerAvailable', array($this, 'isBloggerAvailable')),
				new TwigFunction('isFacebookAvailable', array($this, 'isFacebookAvailable')),
				new TwigFunction('isDiasporaAvailable', array($this, 'isDiasporaAvailable')),
				new TwigFunction('isMastodonAvailable', array($this, 'isMastodonAvailable')),
				new TwigFunction('isTheDailyTruthAvailable', array($this, 'isTheDailyTruthAvailable')),
				new TwigFunction('isWakondaGuruAvailable', array($this, 'isWakondaGuruAvailable')),
				new TwigFunction('isMuseAvailable', array($this, 'isMuseAvailable')),
				new TwigFunction('isTumblrAvailable', array($this, 'isTumblrAvailable')),
				new TwigFunction('isPinterestAvailable', array($this, 'isPinterestAvailable')),
				new TwigFunction('isShopifyAvailable', array($this, 'isShopifyAvailable')),
				new TwigFunction('isInstagramAvailable', array($this, 'isInstagramAvailable')),
				new TwigFunction('themes_by_language', array($this, 'getThemesByLanguage')),
				new TwigFunction('grimoire_themes_by_language', array($this, 'getSurThemesGrimoireByLanguage')),
				new TwigFunction('allAvailableLanguages', array($this, 'getAllAvailableLanguages')),
				new TwigFunction('format_history', array($this, 'formatHistory')),
				new TwigFunction('source_document', array($this, 'getSourceDocument'), array('is_safe' => array('html'))),
				new TwigFunction('parse_url', array($this, 'parseUrl')),
				new TwigFunction('getimagesize', array($this, 'getimagesize')),
				new TwigFunction('advertising', array($this, 'advertising')),
				new TwigFunction('get_env', array($this, 'getEnv'))
			);
		}

		// Filters
		public function urlEncodeFilter($str)
		{
			return urlencode($str);
		}
		
		public function firstFilter($str)
		{
			return $str[0];
		}
		
		public function isImageFilter($extension)
		{
			return in_array(strtolower($extension), ["png", "gif", "jpg", "jpeg", "bmp", "webp"]);
		}

		public function stripTagsFilter($text)
		{
			return strip_tags($text);
		}
		
		public function urlcleanFilter($urlclean)
		{
			return (new \App\Service\FunctionsLibrary())->cleanUrl($urlclean);
		}

		public function imgsizeFilter($file, $width, $path, bool $useAssetPath = true, $options = null, $caption = [], bool $displayIfFileNotExist = true, $private = false)
		{
			$realPath = $useAssetPath ? DIRECTORY_SEPARATOR.$path : $path;

			if($private)
				$p = realpath($this->parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."private".$realPath.$file);
			else
				$p = $path.$file;

			if(empty($file) or !file_exists($p))
			{
				if(!$displayIfFileNotExist)
					return null;
				
				$file = "file_no_exist_".$this->translator->getLocale().".png";
				$p = "extended/photo/".$file;
				$realPath = ($useAssetPath) ? "/extended/photo/" : "extended/photo/";
			}

			$newLarg = 0.0;
			$newLong = 0.0;
			
			$svg = new \App\Service\ImageSVG($p);

			$info = ($svg->isSVG()) ? $svg->getSize() : getimagesize($p);
			
			if(empty($info))
				return null;

			$eX = 0.0;
			$info[0] = intval($info[0]);
			$info[1] = intval($info[1]);

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

			$optionString = "";
			
			if(empty($width)) 
			{
				if(empty($options))
					$options = [];

				if(is_array($options) and array_key_exists("class", $options))
					$options["class"] = $options["class"]." img-fluid bg-light";
				else if (!array_key_exists("class", $options))
					$options["class"] = "img-fluid bg-light";
			}
			
			if(!empty($options))
			{
				$optionArray = [];
				foreach($options as $key => $option)
				{
					$optionArray[] = $key.'="'.$option.'"';
				}
				$optionString = implode(" ", $optionArray);
			}
			
			$src = $realPath.$file;
			
			if($private) {
				$data = file_get_contents($p);
				$finfo = finfo_open();
				$mime_type = finfo_buffer($finfo, $data, FILEINFO_MIME_TYPE);
				finfo_close($finfo);
				
				$src = "data:".$mime_type.";base64," . base64_encode($data);
			}

			if($svg->isSVG())
				$res = '<img src="'.$src.'"'.(!empty($newLarg) ? ' width="'.round($newLarg).'"' : ""). (!empty($newLong) ? ' height="'.round($newLong).'"' : "").' class="w-100" />';
			else
				$res = '<img src="'.$src.'"'.(!empty($newLarg) ? ' width="'.round($newLarg).'"' : ""). (!empty($newLong) ? ' height="'.round($newLong).'"' : "").' alt="" '.$optionString.' />';

			return $this->imgCaptionFilter($caption, $src, $res);
		}
		
		public function imgCaptionFilter(?array $caption = [], string $src = null, string $imgContent = null): ?string {
			$res = $imgContent;
			if(!empty($caption) and (!empty($caption["caption"]) or !empty(array_filter(array_values($caption["source"]))))) {
				$caption["source"]["url"] = !empty($url = $caption["source"]["url"]) ? '<a href="'.$url.'">'.parse_url($url, PHP_URL_HOST).'</a>' : null;
				
				$licenseString = !empty($text = $caption["source"]["license"]) ? '<li><span class="fa-li"><i class="fas fa-balance-scale-right"></i></span>'.$text.'</li>' : null;
				$authorString = !empty($text = $caption["source"]["author"]) ? '<li><span class="fa-li"><i class="fas fa-user-tie"></i></span>'.$text.'</li>' : null;
				$urlString = !empty($text = $caption["source"]["url"]) ? '<li><span class="fa-li"><i class="fas fa-link"></i></span>'.$text.'</li>' : null;
				$infosPicture = $licenseString.$authorString.$urlString;
				
				$modal = '<div class="modal black_modal fade" id="fileManagementModal" tabindex="-1" role="dialog" aria-labelledby="fileManagementModalLabel" aria-hidden="true">
							  <div class="modal-dialog" role="document">
								<div class="modal-content">
								  <div class="modal-body">
									<img src="'.$src.'" class="w-100" />
									'.(!empty($infosPicture) ? '<hr class="hr2"><ul class="fa-ul">'.$infosPicture.'</ul>' : "").'
									<hr class="hr2">
									'.$caption["caption"].'
								  </div>
								  <div class="modal-footer">
									<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fas fa-times"></i></button>
								  </div>
								</div>
							  </div>
							</div>';
				$caption["caption"] = !empty($c = $caption["caption"]) ? '<a class="badge bg-info float-end" data-bs-toggle="modal" data-target="#fileManagementModal"><i class="fas fa-info fa-fw"></i></a>' : '';
				
				if(empty($imgContent))
					$res = '<div class="image">'.$res.'<p>'.implode(", ", array_filter($caption["source"])).$caption["caption"].'</p></div>'.(!empty($c = $caption["caption"]) ? $modal : '');
				else
					$res = '<figure class="image">'.$res.'<figcaption>'.implode(", ", array_filter($caption["source"])).$caption["caption"].'</figcaption></figure>'.(!empty($c = $caption["caption"]) ? $modal : '');
			}
			
			return $res;
		}
		
		public function displayPrivatePDFFilter($filePath): ?String {
			$privateDir = "private";
			$file = realPath($this->parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR.$privateDir.$filePath);

			if(file_exists($file)) {
				return '<iframe src="data:application/pdf;base64,'.base64_encode(file_get_contents($file)).'" width="100%" height="500" scrolling="no" marginheight="0" marginwidth="0" frameborder="0"></iframe>';
			}

			return null;
		}
		
		public function displayPrivateFileFilter($html): String {
			$privateDir = "private";

			$html = preg_replace('~[[:cntrl:]]~', '', $html);
			
			$dom = new \DOMDocument();
			$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			$elementsToRemove = [];

			foreach ($dom->getElementsByTagName('img') as $item) {
				if(!empty($item->getAttribute("src"))) {
					$img = realPath($this->parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR.$privateDir.$item->getAttribute("src"));

					if($img === false) {
						$file = "file_no_exist_".$this->translator->getLocale().".png";
						$img = "extended/photo/".$file;
					}

					$content = file_get_contents($img);
					$f = finfo_open();
					$mime_type = finfo_buffer($f, $content, FILEINFO_MIME_TYPE);
					finfo_close($f);

					if(file_exists($img))
						$item->setAttribute('src', "data:".$mime_type.";base64," . base64_encode($content));
					else
						$elementsToRemove[] = $item;
				}
			}
			
			foreach($elementsToRemove as $domElement){
				$domElement->parentNode->removeChild($domElement);
			}
			
			return $dom->saveHTML();
		}

		public function dodateFilter($date, $time = false, $language)
		{
			if((is_object($date) or is_string($date)) and !empty($date))
			{
				if(is_string($date))
					$date = new \DateTime($date);
				
				$dateArray = explode("-", $date->format('Y-m-d'));
			
				if($language == "fr")
				{
					$monthFrench = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
					$dateString = $dateArray[2]." ".$monthFrench[$dateArray[1]-1]." ".ltrim($dateArray[0], "0");
				}
				else if($language == "es")
				{
					$monthSpain = array('Enero', 'Frebero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
					$dateString = $dateArray[2]." de ".$monthSpain[$dateArray[1]-1]." de ".ltrim($dateArray[0], "0");
				}
				else
				{
					$monthEnglish = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
					$dateString = $dateArray[2]."th ".$monthEnglish[$dateArray[1]-1]." ".ltrim($dateArray[0], "0");
				}
				
				if($time)
					$dateString = $dateString.' - '.$date->format('H:i');

				return $dateString;
			}
			else
				return '-';
		}
		
		public function doPartialDateFilter(?string $partialDate, $language)
		{
			return (new \App\Service\APDate())->doPartialDate($partialDate, $language);
		}
		
		public function doPartialDateTimeFilter(?string $partialDateTime, $language)
		{
			return (new \App\Service\APDate())->doPartialDateTime($partialDateTime, $language);
		}
		
		public function doYearMonthDayDateFilter($day, $month, $year, $language)
		{
			return (new \App\Service\APDate())->doYearMonthDayDate($day, $month, $year, $language);
		}

		public function advertisementFilter($pub)
		{
			$display = "<div class='hidden_for_print'>";
			$display .= "<h3>".$this->translator->trans("generality.page.Advertisement", [], "validators")."</h3>";
			
			if($pub == "google")
			{
				$ads = $this->advertising(728, 90);
				$display .= '
				<ins class="adsbygoogle" style="display:block;" data-ad-client="ca-pub-1951906303386196" data-ad-slot="6790583340" data-ad-format="auto" data-full-width-responsive="true">'.
				$ads->getText().
				'</ins>
				<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
				
				if(!empty($ads)) {
					$display .= '<div class="d-none advertising-alternative advertising_image text-center">'.$ads->getText().'</div>';
				}
			}
			elseif($pub == "amazon")
			{
				$display .= '
				<script><!--
				amazon_ad_tag="activiparano-21"; 
				amazon_ad_width="468"; 
				amazon_ad_height="60"; 
				amazon_color_background="EFEFCC"; 
				amazon_color_border="AFBF08"; 
				amazon_color_logo="FFFFFF"; 
				amazon_color_link="A43907"; 
				amazon_ad_logo="hide"; 
				amazon_ad_title="Activité-Paranormale"; //--></script>
				<script src="http://www.assoc-amazon.fr/s/asw.js"></script>';		
			}
			
			$display .= "</div>";
			
			return $display;
		}

		public function linkFollowFilter($titleMenu, $currentRoute)
		{
			$explode_currentRoute = explode("_", strtolower($currentRoute));

			if (isset($explode_currentRoute[0]) and $titleMenu == $explode_currentRoute[0])
			   $class = "active";
			else
				$class = "";

			return $class;
		}

		public function HTMLPurifierFilter($content)
		{
			$content = "<div>".$content."</div>";
			$config = array( 'show-body-only' => true);

			$tidy = new \tidy();
			$tidy->parseString($content, $config, 'utf8');
			$tidy->cleanRepair();

			return $tidy->value;
		}

		public function getDocumentFamilyRealNameFilter($entity, $language)
		{
			$internationalName = (empty($entity)) ? "" : $entity->getInternationalName();
			$documentFamily = $this->em->getRepository(DocumentFamily::class)->getDocumentFamilyRealNameByInternationalNameAndLanguage($internationalName, $language);
			return (empty($documentFamily)) ? "-" : $documentFamily->getTitle();
		}

		public function getRealClassFilter($obj)
		{
			$classname = get_class($obj);

			if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
				$classname = $matches[1];
			}

			return $classname;
		}

		public function utf8EncodeFilter($str)
		{
			return utf8_encode($str);
		}
	
		public function addslashesFilter($str)
		{
			return addslashes($str);
		}

		public function formatTextForPDFVersionFilter($text, $entity)
		{
			// Set correct path for images
			$text = str_replace("/".$entity->getAssetImagePath(), $entity->getAssetImagePath(), $text);

			// We remove all break line
			$text = str_replace(array("\r\n", "\r"), "\n", $text);
			$lines = explode("\n", $text);
			$new_lines = [];

			foreach ($lines as $i => $line) {
				if(!empty($line))
				{
					if(strstr($line, $entity->getAssetImagePath()))
						$new_lines[] = trim($line);
					else
						$new_lines[] = " ".trim($line);
				}
			}
			$text = implode($new_lines);

			// Remove style attributes
			$text = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $text);

			return (new APParseHTML())->eraseVideo($text);
		}

		public function getRandomBannerForIndexFilter()
		{
			$abbreviation = $this->translator->getLocale();
			$language = $this->em->getRepository(Language::class)->findOneBy(array("abbreviation" => $abbreviation));
			
			$entities = $this->em->getRepository(Banner::class)->findBy(array('language' => $language, 'display' => true));

			if(empty($entities))
				return null;
			
			$id = rand(1, count($entities));
			$entity = $this->em->getRepository(Banner::class)->find($id);
			
			if(!is_object($entity))
				return null;

			$webPath = $this->parameterBag->get('kernel.project_dir').'/public/extended/photo/banner/';
			
			if(!$webPath."/".$entity->getImage())
				return null;

			$imageSize = getimagesize($webPath."/".$entity->getImage());
			$subBannerArray = [];
			$subBannerArray['name'] = $entity->getImage();

			$width = $imageSize[0];
			$height = $imageSize[1];

			if($imageSize[0] > 550)
			{
				$width = 550;
				$height = (550 * $imageSize[1]) / $imageSize[0];
			}
			$subBannerArray['width'] = round($width);
			$subBannerArray['height'] = round($height);
			$subBannerArray['link'] = $entity->getLink();

			return $subBannerArray;
		}
		
		public function getAllStatesByLanguageFilter()
		{
			$abbreviation = $this->translator->getLocale();
			
			$language = $this->em->getRepository(Language::class)->findOneBy(array("abbreviation" => $abbreviation));
			$states = $this->em->getRepository(State::class)->findByLanguage($language, array('title' => 'ASC'));
			
			return $states;
		}
		
		public function strReplaceFilter($subject, $search, $replace)
		{
			return str_replace($search, $replace, $subject);
		}
		
		public function stringDurationVideoFilter($duration)
		{
			$duration_array = array_reverse(explode(":", $duration));
			$duration_string = [];
			
			if(isset($duration_array[0]) and intval($duration_array[0]))
				$duration_string[] = $this->translator->trans('video.admin.Seconds', array('%count%' => $duration_array[0]), 'validators');
			if(isset($duration_array[1]) and intval($duration_array[1]))
				$duration_string[] = $this->translator->trans('video.admin.Minutes', array('%count%' => $duration_array[1]), 'validators');
			if(isset($duration_array[2]) and intval($duration_array[2]))
				$duration_string[] = $this->translator->trans('video.admin.Hours', array('%count%' => $duration_array[2]), 'validators');

			return implode(" ", array_reverse($duration_string));
		}
		
		public function getDurationByEntities($entities)
		{
			$durations = [];
			$seconds = 0;

			foreach($entities as $entity)
			{
				if(empty($entity->getLength()))
					continue;

				$duration_explode = explode(":", $entity->getLength());

				if(count($duration_explode) == 1)
					$seconds = $seconds + $duration_explode[0];
				elseif(count($duration_explode) == 2)
					$seconds = $seconds + $duration_explode[1] + $duration_explode[0] * 60;
				else
					$seconds = $seconds + $duration_explode[2] + $duration_explode[1] * 60 + $duration_explode[0] * 60 * 60;
			}

			return $this->stringDurationVideoFilter(gmdate("H:i:s", $seconds));
		}
		
		public function removeStyleAttributeFromHtmlTagsFilter($html)
		{
			return preg_replace('/(<[^>]+) style=".*?"/i', '$1', $html);
		}

		// Functions
		public function methodExists($entity, $method)
		{
			return method_exists($entity, $method);
		}
		
		public function fileExists($filename)
		{
			return file_exists($filename);
		}
		
		public function countAvailability($state)
		{
			return $this->em->getRepository(Video::class)->countVideoByAvailability($state);
		}
		
		public function countArchivedEntries($className)
		{
			return $this->em->getRepository($className)->countArchivedEntries();
		}
		
		public function getTagsByEntity($entity, $show = true, $clean = false, $action = true)
		{
			$className = array_reverse(explode("\\", get_class($entity)));
			$tags = $this->em->getRepository(Tags::class)->findBy(array('idClass' => $entity->getId(), 'nameClass' => $className));
			
			$tagArray = [];

			foreach($tags as $tag) {
				
			$html = "";
			
			if($action)
				$html = '<a href="'.$this->router->generate("TagWord_Admin_Show", ["id" => $tag->getTagWord()->getId()]).'" class="badge bg-info text-white"><i class="fas fa-eye fa-fw"></i></a> <a href="'.$this->router->generate("TagWord_Admin_Edit", ["id" => $tag->getTagWord()->getId()]).'" class="badge bg-success text-white"><i class="fas fa-pencil-alt fa-fw"></i></a> ';

				$tagArray[] = !$clean ? $html.$tag->getTagWord()->getTitle() : $tag->getTagWord()->cleanTags();
			}

			return (empty($tagArray)) ? ($show ? "-" : "") : implode(", ", $tagArray);
		}

		public function getTagsByEntityForDisplay($entity)
		{
			$className = array_reverse(explode("\\", get_class($entity)));
			$tags = $this->em->getRepository(Tags::class)->findBy(array('idClass' => $entity->getId(), 'nameClass' => $className));

			if(!empty($tags))
			{
				$tagsArray = [];
				
				foreach($tags as $tag)
				{
					if(!empty(trim($tag->getTagWord()->getTitle())))
						$tagsArray[] = '<a href="'.$this->router->generate('ap_tags_search', array('id' => $tag->getTagWord()->getId(), 'title' => $tag->getTagWord()->getTitle())).'" class="tags_display">'.$tag->getTagWord()->getTitle().'</a>';
				}
				
				if(empty($tagsArray))
					return null;

				return '<fieldset class="p-2"><legend class="ml-2"> Tags </legend>'.implode('', $tagsArray).'</fieldset><br>';
			}
			return null;
		}
		
		public function getBiographyInCorrectLanguage($entity)
		{
			$locale = $this->translator->getLocale();
			$entity = $this->em->getRepository(Biography::class)->find($entity->getId());
			
			$correctBio = $this->em->getRepository(Biography::class)->getBiographyInCorrectLanguage($entity, $locale);
			
			return $correctBio;
		}

		public function getEntitiesOtherLanguages($entity)
		{
			return $this->em->getRepository(get_class($entity))->findBy(["internationalName" => $entity->getInternationalName()]);
		}
		
		public function isCurrentLanguages($language_article)
		{
			$languages = explode(",", $_ENV["LANGUAGES"]);
			
			if(in_array($language_article, $languages))
				return true;
			
			return false;
		}
		
		public function getAllAvailableLanguages()
		{
			return $this->em->getRepository(Language::class)->getAllAvailableLanguages(explode(",", $_ENV["LANGUAGES"]));
		}
		
		public function base64Encode($str)
		{
			return base64_encode($str);
		}

		public function jsonDecode($str, ?bool $associative = null)
		{
			return json_decode($str, $associative);
		}
		
		public function generateCaptcha()
		{
			$captcha = new Captcha($this->parameterBag, $this->requestStack);

			$wordOrNumberRand = rand(1, 2);
			$length = rand(3, 7);

			if($wordOrNumberRand == 1)
				$word = $captcha->wordRandom($length);
			else
				$word = $captcha->numberRandom($length);
			
			return $captcha->generate($word);
		}
		
		public function getBloggerTags($type)
		{
			$bloggerAPI = new GoogleBlogger();
			$blogName = $bloggerAPI->getCorrectBlog($type);
			
			if(empty($blogName))
				return null;
			
			$blogURL = $bloggerAPI->getBlogURLArray($blogName);
			$url = $blogURL."/feeds/posts/summary?alt=json&max-results=0&callback=cat";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$tags = curl_exec($ch);
			curl_close($ch);

			preg_match("/cat[(]{1}(.*?)[)]{1}[;]{1}$/", $tags, $matches);
			
			if(!isset($matches[1]))
				return [];
			
			$tagsObject = json_decode($matches[1]);
			$tagsArray = [];
			
			if(!property_exists($tagsObject->feed, "category"))
				return $tagsArray;
			
			foreach($tagsObject->feed->category as $tag)
				$tagsArray[] = $tag->term;
			
			sort($tagsArray);

			return $tagsArray;
		}
		
		public function getBloggerList($locale)
		{
			$bloggerAPI = new GoogleBlogger();
			
			$datas = [];
			
			foreach($bloggerAPI->getTypes() as $type) {
				list($f, $l) = explode("_", $type);
				
				if($locale == $l)
					$datas[$type] = $bloggerAPI->getCorrectBlog($type);
			}
			
			array_multisort($datas, SORT_ASC, $datas);

			return $datas;
		}
		
		public function getBloggerId($type)
		{
			$bloggerAPI = new GoogleBlogger();
			
			return $bloggerAPI->blogId_array[$bloggerAPI->getCorrectBlog($type)];
		}
		
		public function getTheDailyTruthTags()
		{
			$api = new TheDailyTruth();
			
			return $api->getTags($api->getOauth2Token());
		}
		
		public function slugifyUrl($title, $replace = [], $delimiter = '-')
		{
			setlocale(LC_ALL, 'en_US.UTF8');

			if( !empty($replace) ) {
				$title = str_replace((array)$replace, ' ', $title);
			}

			$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
			$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
			$clean = strtolower(trim($clean, '-'));
			$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

			return $clean;
		}
		
		public function getImageSizeHTML2PDF($url)
		{
			$img_size = getimagesize($url);
			$max_width = 550;
			$new_width = $img_size[0];
			$new_height = $img_size[1];
			
			if($img_size[0] > $max_width) {
				$new_height = ($max_width * $img_size[1]) / $img_size[0];
				$new_width = $max_width;
			}
			
			return array("width" => $new_width, "height" => $new_height);
		}
		
		public function getimagesize($file, $path)
		{
			$pf = $path.$file;
			
			if(empty($file) or !file_exists($pf))
			{
				$file = "file_no_exist_".$this->translator->getLocale().".png";
				$pf = "extended/photo/".$file;
			}
			
			$svg = new \App\Service\ImageSVG($pf);

			return ($svg->isSVG()) ? $svg->getSize() : getimagesize($pf);

			return array("width" => $new_width, "height" => $new_height);
		}

		public function isTwitterAvailable($entity): bool
		{
			$api = new TwitterAPI();

			return in_array($entity->getLanguage()->getAbbreviation(), $api->getLanguages());
		}

		public function isBloggerAvailable($type): bool
		{
			$api = new GoogleBlogger();
			
			if($_ENV["APP_ENV"] == "dev")
				$type = "test_".explode("_", $type)[1];

			return in_array($type, $api->getTypes());
		}

		public function isFacebookAvailable($entity): bool
		{
			$api = new \App\Service\Facebook();
			return in_array($entity->getLanguage()->getAbbreviation(), $api->getLanguages());
		}

		public function isDiasporaAvailable($entity): bool
		{
			$api = new \App\Service\Diaspora();
			return in_array($entity->getLanguage()->getAbbreviation(), $api->getLanguages());
		}

		public function isInstagramAvailable($entity): bool
		{
			$api = new \App\Service\Instagram();
			return in_array($entity->getLanguage()->getAbbreviation(), $api->getLanguages());
		}

		public function isMastodonAvailable($entity): bool
		{
			$api = new \App\Service\Mastodon();
			return in_array($entity->getLanguage()->getAbbreviation(), $api->getLanguages());
		}

		public function isTheDailyTruthAvailable($locale): bool
		{
			$api = new TheDailyTruth();
			
			return in_array($locale, $api->getLocaleAvailable());
		}

		public function isWakondaGuruAvailable($locale): bool
		{
			$api = new \App\Service\WakondaGuru();
			
			return in_array($locale, $api->getLocaleAvailable());
		}

		public function isMuseAvailable($locale): bool
		{
			$api = new \App\Service\Muse();
			
			return in_array($locale, $api->getLocaleAvailable());
		}

		public function isShopifyAvailable($type)
		{
			$api = new Shopify();

			return in_array($type, $api->getTypes());
		}

		public function isTumblrAvailable($type)
		{
			$api = new TumblrAPI();
			return in_array($type, $api->getTypes());
		}

		public function isPinterestAvailable($entity)
		{
			$api = new PinterestAPI();
			
			return in_array($entity->getLanguage()->getAbbreviation(), $api->getLanguages());
		}
		
		public function getThemesByLanguage($language)
		{
			return $this->em->getRepository(Theme::class)->getTheme($language);
		}
		
		public function getSurThemesGrimoireByLanguage($language)
		{
			return $this->em->getRepository(SurThemeGrimoire::class)->getSurThemeByLanguage($language);
		}

		public function formatHistory($text)
		{
			return "<pre>".implode(PHP_EOL, array_map(function ($string) {
				$string = preg_replace('/(@@ [A-Za-z0-9,\-+\s]* @@)/', '<div class="alert alert-info fw-bold mb-0">$1</div>', $string);
				$string = preg_replace('/((\\+){3} New)/', '<div class="alert alert-success fw-bold mb-0">$1</div>', $string);
				$string = preg_replace('/^(\\+){1}/', '<div class="alert alert-success fw-bold mb-0"><i class="fas fa-plus"></i></div>', $string);
				$string = preg_replace('/^((\\-){3} Original)/', '<div class="alert alert-danger fw-bold mb-0">$1</div>', $string);
				$string = preg_replace('/^(\\-){1}/', '<div class="alert alert-danger fw-bold mb-0"><i class="fas fa-minus"></i></div>', $string);
				$string = str_repeat(' ', 6) . $string;
			 return $string;
			}, explode(PHP_EOL, $text)))."</pre>";
		}
		
		public function getSourceDocument($sourceJSON, $locale = null, Array $classes = [])
		{
			if(!empty($locale))
				$locale = $locale->getAbbreviation();
			else
				$locale = $this->translator->getLocale();
			
			return (new \App\Service\FunctionsLibrary($this->em))->sourceString($sourceJSON, $locale, $classes);
		}
		
		public function parseUrl(string $url, int $component = -1) {
			return parse_url($url, $component);
		}
		
		public function UcfirstFilter(?String $string) {
			return ucfirst($string);
		}
		
		public function advertising($maxWidth, $maxHeight) {
			return $this->em->getRepository("App\Entity\Advertising")->getOneRandomAdsByWidthAndHeight($maxWidth, $maxHeight);
		}

		public function getEnv(string $varname): string
		{
			return $_ENV[$varname];
		}

		public function getName()
		{
			return 'ap_extension';
		}
	}