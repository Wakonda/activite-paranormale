<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Language;
use App\Service\APImgSize;
use App\Service\APParseHTML;
use App\Service\APDate;
use App\Service\TwitterAPI;
use App\Service\PinterestAPI;
use App\Service\TumblrAPI;
use App\Service\GoogleBlogger;
use App\Service\Shopify;
use App\Service\Wordpress;
use App\Service\FunctionsLibrary;
use App\Service\Facebook;
use App\Service\Mastodon;
use App\Service\Instagram;
use App\Service\Diaspora;
use App\Service\VK;
use App\Service\Bluesky;
use App\Service\Amazon;
use App\Service\Flickr;
use App\Service\Telegram;
use App\Entity\Stores\Store;
use App\Twig\APExtension;
use App\Service\PaginatorNativeSQL;

class AdminController extends AbstractController
{
	#[Route('/admin/index', name: 'Admin_Index')]
    public function index()
    {
        return $this->render('admin/Admin/index.html.twig');
    }

	#[Route('/admin/selectLanguage/{language}', name: 'Admin_SelectLanguage', defaults: ['language' => 'fr'])]
	public function selectLanguage(Request $request, $language)
    {
		$session = $request->getSession();
		$request->setLocale($language);
		$session->set('_locale', $language);

		return $this->redirect($this->generateUrl('Admin_Index'));
    }

	#[Route('/admin/phpinfo', name: 'Admin_Phpinfo')]
	public function phpinfoAction()
	{
		phpinfo();
		return new Response();
	}

	public function internationalizationSelectGeneric(EntityManagerInterface $em, $entity, String $route, String $showRoute, String $editRoute)
	{
		$locales = [];

		if(method_exists($entity, "getInternationalName")) {
			$entities = $em->getRepository(get_class($entity))->findBy(["internationalName" => $entity->getInternationalName()]);

			foreach($entities as $e)
				if(!empty($e->getLanguage()))
					$locales[] = $e->getLanguage()->getAbbreviation();
		}

		$form = $this->createForm(\App\Form\Type\InternationalizationAdminType::class, null, ["locales" => $locales]);

		$availableLanguages = [];
		foreach($em->getRepository(Language::class)->getAllAvailableLanguages() as $l) {
			if(!in_array($l->getAbbreviation(), $locales))
				$availableLanguages[] = $l;
		}

		return $this->render("admin/Admin/internationalization.html.twig", [
			"entity" => $entity,
			"form" => $form->createView(),
			"route" => $this->generateUrl($route, ["id" => $entity->getId()]),
			"showRoute" => $showRoute,
			"editRoute" => $editRoute,
			"availableLanguages" => $availableLanguages
		]);
	}

	#[Route('/admin/maintenance/{mode}', name: 'Admin_Maintenance', defaults: ['mode' => ''])]
	public function maintenance(Request $request, ParameterBagInterface $parameterBag, $mode)
	{
		if(!file_exists("sitemaps"))
			mkdir("sitemaps");

		$sitemaps = array_diff(!empty($sm = scandir("sitemaps")) ? $sm : [], ['.', '..']);
		$robotstxt = $parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."robots.txt";
		$htaccessPath = $parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR.".htaccess";
		$htaccessMaintenanceOnPath = $parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."maintenance".DIRECTORY_SEPARATOR."maintenanceon.htaccess";
		$htaccessMaintenanceOffPath = $parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."maintenance".DIRECTORY_SEPARATOR."maintenanceoff.htaccess";

		if($mode == "MaintenanceOn") {
			$content = file_get_contents($htaccessMaintenanceOnPath);
			$content = str_replace("##IP_ADDRESS##", $this->get_ip(), $content);
			file_put_contents($htaccessPath, $content);
		} elseif($mode == "MaintenanceOff") {
			file_put_contents($htaccessPath, file_get_contents($htaccessMaintenanceOffPath));
		} elseif($mode == "robotstxt") {
			file_put_contents($robotstxt, $request->query->get("robotstxt_content"));
		}

		$line = fgets(fopen($parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR.".htaccess", 'r'));
		$mode = ltrim(trim($line), "#");

		if(!file_exists($robotstxt))
			touch($robotstxt);

		return $this->render("admin/Admin/maintenance.html.twig", ["mode" => $mode, "robotstxt" => file_get_contents($robotstxt), "sitemaps" => $sitemaps]);
	}

	private function get_ip(): string {
		$ip = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif(isset($_SERVER['HTTP_X_FORWARDED']))
			$ip = $_SERVER['HTTP_X_FORWARDED'];
		elseif(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_FORWARDED_FOR'];
		elseif(isset($_SERVER['HTTP_FORWARDED']))
			$ip = $_SERVER['HTTP_FORWARDED'];
		elseif(isset($_SERVER['REMOTE_ADDR']))
			$ip = $_SERVER['REMOTE_ADDR'];

		return $ip;
	}

	#[Route('/admin/loadWikipediaSectionsPage', name: 'Admin_WikipediaSections')]
	public function loadWikipediaSectionsPage(Request $request, TranslatorInterface $translator, \App\Service\Wikipedia $data)
	{
		$url = $request->query->get("url");

		$res = [];
		$res[] = ["id" => 0, "text" => $translator->trans('admin.wikipedia.Header', [], 'validators', $request->getLocale())];

		if(str_contains(parse_url($url, PHP_URL_HOST), "wikimonde")) {
			$data = new \App\Service\Wikimonde();
			$data->setUrl($url);
		} else
			$data->setUrl($url);

		foreach($data->getSections() as $text => $id)
			$res[] = ["id" => $id, "text" => $text];

		return new JsonResponse($res);
	}

	#[Route('/admin/import', name: 'Admin_ImportWikipedia')]
	public function importWikipedia(Request $request, \App\Service\Wikipedia $data)
	{
		$url = $request->request->get("url");

		if(str_contains(parse_url($url, PHP_URL_HOST), "wikimonde")) {
			$data = new \App\Service\Wikimonde();
			$data->setUrl($url);
		} else
			$data->setUrl($url);

		$sections = $request->request->all("sections", []);
		$source = ["author" => "", "title" => "", "url" => $request->request->get("url"), "type" => "url"];

		return new JsonResponse(["content" => $data->getContentBySections($sections), "source" => $source]);
	}

	// Blogger
	#[Route('/admin/bloggertags/{id}/{path}/{routeToRedirect}', name: 'Admin_BloggerTags')]
	public function bloggerTags(Request $request, EntityManagerInterface $em, APExtension $apExtension, GoogleBlogger $blogger, $id, $path, $routeToRedirect)
	{
		$type = $request->query->get("type");
		$tags = $apExtension->getBloggerTags($type);
		$entity = $em->getRepository(urldecode($path))->find($id);
		$blogId = $blogger->blogId_array[$blogger->getCorrectBlog($type)];

		$obj = null;
		$method = "POST";

		if(method_exists($entity, "getSocialNetworkIdentifiers")) {
			if(isset($entity->getSocialNetworkIdentifiers()["Blogger"][$blogId])) {
				$obj = $entity->getSocialNetworkIdentifiers()["Blogger"][$blogId];
				$method = "PUT";
			}
		}
		
		$theme = null;
		
		if(method_exists($entity, "getTheme") and !empty($t = $entity->getTheme()))
			$theme = $t->getTitle();

		$urlAddUpdate = $this->generateUrl('Admin_Blogger', ['id' => $entity->getId(), 'path' => urlencode($entity->getEntityName()), 'routeToRedirect' => $routeToRedirect, 'type' => $type, 'method' => $method]);

		$urlDelete = null;

		if(!empty($obj))
			$urlDelete = $this->generateUrl('Admin_Blogger', ['id' => $entity->getId(), 'path' => urlencode($entity->getEntityName()), 'routeToRedirect' => $routeToRedirect, 'type' => $type, 'method' => "DELETE"]);

		return new JsonResponse(["obj" => $obj, "theme" => $theme, "method" => $method, "tags" => $tags, "urlAddUpdate" => $urlAddUpdate, "urlDelete" => $urlDelete]);
	}

	#[Route('/admin/blogger/{id}/{path}/{routeToRedirect}/{type}/{method}', name: 'Admin_Blogger', defaults: ['type' => 'test', 'method' => 'POST'])]
	public function blogger(Request $request, EntityManagerInterface $em, GoogleBlogger $blogger, UrlGeneratorInterface $router, $id, $path, $routeToRedirect, $type, $method)
	{
		$session = $request->getSession();
		$session->set("id_blogger", $id);
		$session->set("method_blogger", $method);

		$path = urldecode($path);
		$session->set("path_blogger", $path);
		$session->set("routeToRedirect_blogger", $routeToRedirect);

		$tags = $request->request->all('blogger_tags');
		$session->set("tags_blogger", json_encode((empty($tags)) ? [] : $tags));
		$session->set("type_blogger", $type);

		$entity = $em->getRepository($path)->find($id);
		$redirectURL = $router->generate("Admin_BloggerPost", [], UrlGeneratorInterface::ABSOLUTE_URL);

		$blogName = $blogger->getCorrectBlog($type);
		$response = $blogger->getPostInfos($blogName);

		$code = $blogger->getCode($redirectURL);

		return new Response();
	}

	#[Route('/admin/bloggerpost', name: 'Admin_BloggerPost')]
	public function bloggerPost(Request $request, EntityManagerInterface $em, APExtension $apExtension, APImgSize $imgSize, APParseHTML $parser, GoogleBlogger $blogger, TranslatorInterface $translator, UrlGeneratorInterface $router)
	{
		$code = $request->query->get("code");
		$session = $request->getSession();

		$id = $session->get("id_blogger");
		$path = $session->get("path_blogger");
		$tags = $session->get("tags_blogger");
		$type = $session->get("type_blogger");
		$method = $session->get("method_blogger");

		$routeToRedirect = $session->get("routeToRedirect_blogger");

		$redirectURL = $router->generate("Admin_BloggerPost", [], UrlGeneratorInterface::ABSOLUTE_URL);
		$accessToken = $blogger->getOauth2Token($code, "online", $redirectURL);
		$blogName = $blogger->getCorrectBlog($type);
		$blogId = $blogger->blogId_array[$blogName];

		$entity = $em->getRepository($path)->find($id);

		$title = $entity->getTitle();
		$img = null;

		if(in_array($method, ["POST", "PUT"])) {
			$text = "";
			$imgProperty = "";
			$isExternalImage = false;
			
			$tagsLinks = [];
			$tagsLinkString = "";
			$tagsEntity = $em->getRepository(\App\Entity\Tags::class)->findBy(['idClass' => $entity->getId(), 'nameClass' => $entity->getRealClass()]);

			$storeButton = "";

			if(in_array(Store::class, [get_class($entity), get_parent_class($entity)])) {
				$language = $entity->getLanguage()->getAbbreviation();

				if(Store::ALIEXPRESS_PLATFORM == $entity->getPlatform())
					$storeButton = '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #E52F20; padding: 0.375rem 0.75rem;background-color: #E52F20;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAliexpress', [], 'validators', $language).'</a></div>';
				elseif(Store::AMAZON_PLATFORM == $entity->getPlatform())
					$storeButton = '<div style="text-align: center"><a href="'.$entity->getExternalAmazonStoreLink().'" style="border: 1px solid #ff9900; padding: 0.375rem 0.75rem;background-color: #ff9900;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAmazon', [], 'validators', $language).'</a></div>';
				elseif(Store::SPREADSHOP_PLATFORM == $entity->getPlatform())
					$storeButton = '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #a73c9e; padding: 0.375rem 0.75rem;background-color: #a73c9e;border-radius: 0.25rem;color: white !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnSpreadshop', [], 'validators', $language).'</a></div>';
				elseif(Store::TEMU_PLATFORM == $entity->getPlatform())
					$storeButton = '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #ff6d00; padding: 0.375rem 0.75rem;background-color: #ff6d00;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAliexpress', [], 'validators', $language).'</a></div>';
			}

			if(!empty($tagsEntity))
				foreach($tagsEntity as $tag)
					$tagsLinks[] = '<a href="'.$this->generateUrl('ap_tags_search', ['id' => $tag->getTagWord()->getId(), 'title_slug' => $tag->getTagWord()->getSlug()]).'">'.$tag->getTagWord()->getTitle().'</a>';

			if(method_exists($entity, "getTheme") and !empty($t = $entity->getTheme()))
				$tagsLinks[] = '<a href="'.$this->generateUrl('Theme_Show', ['id' => $t->getId(), 'theme' => $t->getTitle()]).'">'.$t->getTitle().'</a>';

			if(!empty($tagsLinks))
				$tagsLinkString = "<fieldset style='border: 1px solid black;border-radius: 0.3em;padding: 6px;'><legend style='padding: 3px;'><b>".$translator->trans('tag.admin.Tags', [], 'validators', $request->getLocale())."</b></legend>".implode(", ", $tagsLinks)."</fieldset>";

			switch($entity->getRealClass())
			{
				case "Photo":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $entity->getText();
					$text .= "<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators', $request->getLocale())."</b><br>".$imgCaption."</div>";
					$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
					$text .= $tagsLinkString;
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "Document":
					$text = "";
					$extension = pathinfo($entity->getPdfDoc(), PATHINFO_EXTENSION);
					if($apExtension->isImageFilter($extension)) {
						$imgProperty = $entity->getPdfDoc();
						$img = $entity->getAssetImagePath().$imgProperty;
					} else {
						$text = '<iframe src="'.$request->getSchemeAndHttpHost().$request->getBasePath().$entity->getAssetImagePath()."/".$entity->getPdfDoc().'" height="550" width="300" title="Iframe Example"></iframe>';
					}

					$text .= $entity->getText();
					$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
					$text .= $tagsLinkString;
					$text = $parser->replacePathImgByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "News":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $parser->replacePathImgByFullURL($entity->getAbstractText().$entity->getText()."<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators', $request->getLocale())."</b><br>".$imgCaption."</div>"."<b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation())."</span>".$tagsLinkString, $request->getSchemeAndHttpHost().$request->getBasePath());
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "EventMessage":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";

					$source = empty($entity->getSource()) ? "" : "<b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation())."</span>";

					$text = $parser->replacePathImgByFullURL($entity->getAbstractText().$entity->getText()."<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators', $request->getLocale())."</b><br>".$imgCaption."</div>".$source.$tagsLinkString, $request->getSchemeAndHttpHost().$request->getBasePath());
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());

					if($entity->getType() == \App\Entity\EventMessage::EVENT_TYPE) {
						$dateString = (new \App\Service\APDate())->doYearMonthDayDate($entity->getDayFrom(), $month = $entity->getMonthFrom(), $entity->getYearFrom(), $entity->getLanguage()->getAbbreviation());
						$title = $dateString." - ".$title;
					}
					break;
				case "Video":
					$video = $parser->getVideoResponsive($entity->getEmbeddedCode());
					if(!empty($entity->getMediaVideo()))
						$video = $parser->getVideoResponsive('<video width="550" height="309" controls><source src="'.$request->getSchemeAndHttpHost().'/'.$entity->getAssetVideoPath().'/'.$entity->getMediaVideo().'" type="video/mp4"></video>');

					$imgProperty = $entity->getPhoto();
					$img = $entity->getAssetImagePath().$imgProperty;
				
					if(empty($imgProperty))
						$text = $video."<br>".$entity->getText();
					else
						$text = $entity->getText()."<br>".$video;

					$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>".$tagsLinkString;
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "Grimoire":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $entity->getText();

					$text = $parser->replacePathImgByFullURL($text."<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators', $request->getLocale())."</b><br>".$imgCaption."</div>"."<b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation())."</span>".$tagsLinkString, $request->getSchemeAndHttpHost().$request->getBasePath());
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "WitchcraftTool":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $entity->getText();
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "President":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;

					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $entity->getText();
					break;
				case "Cartography":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$text = $entity->getText();
					$text .= "<b>".$translator->trans('cartography.admin.LinkGMaps', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b><br><span><a href='".$entity->getLinkGMaps()."'>".(new FunctionsLibrary())->cleanUrl($entity->getLinkGMaps())."</a></span>";
					$text .= "<br><br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>".$tagsLinkString;
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "Book":
					$imgProperty = $entity->getTheme()->getPhoto();
					$img = $entity->getTheme()->getAssetImagePath().$imgProperty;
					$text = $entity->getText()."<br>";
					$text .= $apExtension->getImageEmbeddedCodeByEntity($entity->getBookEditions()->first(), "book", "BookStore")."<br>";
					$text .= "<b>".$translator->trans('biography.index.Author', [], 'validators', $entity->getLanguage()->getAbbreviation())." : </b>".implode(", ", array_map(function($e) { return $e->getTitle(); }, $entity->getAuthors()->getValues()))."<br>";
					$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>".$tagsLinkString;
					break;
				case "Store":
					if(!empty($entity->getPhoto())) {
						$imgProperty = $entity->getPhoto();
						$img = $entity->getAssetImagePath().$imgProperty;
					} elseif($entity->isSpreadShopPlatform()) {
						$imgProperty = $img = null;
					} else {
						$imgProperty = strtolower($entity->getCategory()).".jpg";
						$img = $entity->getAssetImagePath()."category/".$imgProperty;
					}

					$text = $entity->getText()."<br>";
					$text .= $entity->getImageEmbeddedCode()."<br>";
					break;
				case "BookStore":
					if(!empty($d = $entity->getBook()->getPhotoIllustrationFilename())) {
						$imgProperty = $d;
						$img = $entity->getBook()->getAssetImagePath().$imgProperty;
					} elseif(!empty($d = $entity->getBook()->getBook()->getPhoto())) {
						$imgProperty = $d;
						$img = $entity->getBook()->getBook()->getAssetImagePath().$imgProperty;
					} elseif(!empty($entity->getImageEmbeddedCode())) {
						if(empty($img)) {
							$dom = new \DOMDocument();
							$dom->loadHTML($entity->getImageEmbeddedCode());

							$images = $dom->getElementsByTagName('img');

							foreach ($images as $image) {
								$img = $imgProperty = $image->getAttribute('src');
								$isExternalImage = true;
							}
						}
					} else {
						$imgProperty = $entity->getAssetImagePath()."category/".strtolower($entity->getCategory()).".jpg";
						$img = $imgProperty;
					}

					$text = $entity->getText()."<br>";
					
					if(!empty($storeButton))
						$text .= $storeButton."<br>";

					$language = $entity->getBook()->getBook()->getLanguage()->getAbbreviation();
					$title = $translator->trans('book.index.Book', [], 'validators', $language)." - ".$entity->getTitle();

					$text .= (!empty($d = $entity->getBook()->getBackCover()) ? "<b>".$translator->trans('bookEdition.admin.BackCover', [], 'validators', $language)."</b><br>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getBook()->getText()) ? "<b>".$translator->trans('book.admin.Text', [], 'validators', $language)."</b><br>".$d."<br>" : "");
					
					if(!$isExternalImage) {
						if(empty($entity->getImageEmbeddedCode()))
							$text .= '<div class="text-center"><img src="'.$this->generateUrl('Store_GenerateEmbeddedCode', ["id" => $entity->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'" style="width: 75%"/></div><br><br>';
						else
							$text .= $entity->getImageEmbeddedCode()."<br><br>";
					}
					$text .= "<b>".$translator->trans('biography.index.Author', [], 'validators', $entity->getBook()->getBook()->getLanguage()->getAbbreviation())." : </b>".implode(", ", array_map(function($e) { return $e->getTitle(); }, $entity->getBook()->getBook()->getAuthors()))."<br>";
					$text .= (!empty($d = $entity->getBook()->getIsbn10()) ? "<b>ISBN 10 : </b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getIsbn13()) ? "<b>ISBN 13 : </b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getNumberPage()) ? "<b>".$translator->trans('bookEdition.admin.NumberPage', [], 'validators', $language)." : </b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getPublisher()->getTitle()) ? "<b>".$translator->trans('bookEdition.admin.Publisher', [], 'validators', $language)." : </b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getPublicationDate()) ? "<b>".$translator->trans('bookEdition.admin.PublicationDate', [], 'validators', $language)." : </b>".$apExtension->doPartialDateFilter($d, $entity->getBook()->getBook()->getLanguage()->getAbbreviation())."<br>" : "");

					$storeURL = null;
					$storeTitle = null;

					switch($entity->getLanguage()->getAbbreviation()) {
						case "fr":
							$storeURL = 'https://templededelphes.netlify.app/';
							$storeTitle = "Temple de Delphe";
							break;
						case "en":
							$storeURL = 'https://paranormalbook.netlify.app/';
							$storeTitle = "Paranormal Book";
							break;
						case "es":
							$storeURL = 'https://libroparanormal.netlify.app/';
							$storeTitle = "Libro Paranormal";
							break;
					}

					if(!empty($storeURL))
						$text .= "<br>".$translator->trans('store.admin.MoreBooksOn', [], 'validators', $language)." <a href='{$storeURL}'>{$storeTitle}</a>";

					$text .= !empty($entity->getBook()->getBook()->getSource()) ? "<br><b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getBook()->getBook()->getLanguage()->getAbbreviation())."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getBook()->getBook()->getSource(), $entity->getBook()->getBook()->getLanguage()->getAbbreviation())."</span>" : "";
					$text = "<div>".$parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath())."</div>";
					break;
				case "AlbumStore":
					if(!empty($d = $entity->getAlbum()->getPhotoIllustrationFilename())) {
						$imgProperty = $d;
						$img = $entity->getAlbum()->getAssetImagePath().$imgProperty;
					} elseif(!empty($d = $entity->getAlbum()->getArtist()->getPhotoIllustrationFilename())) {
						$imgProperty = $d;
						$img = $entity->getAlbum()->getArtist()->getAssetImagePath().$imgProperty;
					} else {
						$imgProperty = $entity->getAssetImagePath()."category/".strtolower($entity->getCategory()).".jpg";
						$img = $imgProperty;
					}
					$language = $entity->getAlbum()->getLanguage()->getAbbreviation();
					$text = $entity->getText()."<br>";
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= (!empty($d = $entity->getAlbum()->getArtist()) ? "<b>".$translator->trans('album.admin.Artist', [], 'validators', $language)." : </b>".$d->getTitle()."<br>" : "");
					$text .= (!empty($d = $entity->getAlbum()->getReleaseYear()) ? "<b>".$translator->trans('album.admin.ReleaseYear', [], 'validators', $language)." : </b>".$apExtension->doPartialDateFilter($d, $entity->getAlbum()->getLanguage()->getAbbreviation())."<br>" : "");
					$text .= (!empty($d = $entity->getAlbum()->getArtist()->getGenre()) ? "<b>".$translator->trans('artist.admin.Sound', [], 'validators', $language)." : </b>".$d->getTitle()."<br>" : "");
					$text = "<div>".$parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath())."</div>";
					break;
				case "MovieStore":
					if(!empty($d = $entity->getMovie()->getPhotoIllustrationFilename())) {
						$imgProperty = $d;
						$img = $entity->getMovie()->getAssetImagePath().$imgProperty;
					} else {
						$imgProperty = $entity->getAssetImagePath()."category/".strtolower($entity->getCategory()).".jpg";
						$img = $imgProperty;
					}
					$language = $entity->getMovie()->getLanguage()->getAbbreviation();
					$text = $entity->getText()."<br>";
					$text .= (!empty($d = $entity->getMovie()->getText()) ? "<b>>".$translator->trans('movie.admin.Text', [], 'validators', $language)."</b>".$d."<br>" : "");
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= (!empty($d = $entity->getMovie()->getDuration()) ? "<b>".$translator->trans('movie.admin.Duration', [], 'validators', $language)." :</b>".$d." minutes<br>" : "");
					$text .= (!empty($d = $entity->getMovie()->getGenre()) ? "<b>".$translator->trans('movie.admin.Genre', [], 'validators', $language)." :</b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getMovie()->getReleaseYear()) ? "<b>".$translator->trans('movie.admin.ReleaseYear', [], 'validators', $language)." :</b>".$apExtension->doPartialDateFilter($d, $entity->getMovie()->getLanguage()->getAbbreviation())."<br>" : "");
					$text .= (!empty($d = $entity->getMovie()->getTrailer()) ? "<b>".$translator->trans('movie.admin.Trailer', [], 'validators', $language)."</b><br>".$d."<br>" : "");

					$actorArray = [];

					$biographyDatas = $apExtension->getMovieBiographiesByOccupation($entity->getMovie());

					foreach($biographyDatas as $occupation => $biographies) {
						if ($occupation == \App\Entity\Movies\MediaInterface::ACTOR_OCCUPATION) {
							foreach($biographies as $biography)
								$actorArray[] = $biography["title"].(!empty($r = $biography["role"]) ? " (".$r.")" : "");
						}
					}
					$text .= (!empty($actorArray) ? "<b>".$translator->trans('biographies.admin.Actor', [], 'validators', $language)." : </b>".implode(", ", $actorArray)."<br>" : "");
					$text .= !empty($entity->getMovie()->getSource()) ? "<br><b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getMovie()->getLanguage()->getAbbreviation())."</b><span>".(new FunctionsLibrary())->sourceString($entity->getMovie()->getSource(), $entity->getMovie()->getLanguage()->getAbbreviation())."</span>" : "";
					$text = "<div>".$parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath())."</div>";
					break;
				case "TelevisionSerieStore":
					if(!empty($d = $entity->getTelevisionSerie()->getPhotoIllustrationFilename())) {
						$imgProperty = $d;
						$img = $entity->getTelevisionSerie()->getAssetImagePath().$imgProperty;
					} else {
						$imgProperty = $entity->getAssetImagePath()."category/".strtolower($entity->getCategory()).".jpg";
						$img = $imgProperty;
					}

					$language = $entity->getTelevisionSerie()->getLanguage()->getAbbreviation();
					$text = $entity->getText()."<br>";
					$text .= (!empty($d = $entity->getTelevisionSerie()->getText()) ? "<b>".$translator->trans('televisionSerie.admin.Text', [], 'validators', $language)."</b><br>".$d."<br>" : "");
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= (!empty($d = $entity->getTelevisionSerie()->getGenre()) ? "<b>".$translator->trans('televisionSerie.admin.Genre', [], 'validators', $language)." :</b>".$d."<br>" : "");

					$actorArray = [];

					$biographyDatas = $apExtension->getTelevisionSerieBiographiesByOccupation($entity->getTelevisionSerie());

					foreach($biographyDatas as $occupation => $biographies) {
						if ($occupation == \App\Entity\Movies\MediaInterface::ACTOR_OCCUPATION) {
							foreach($biographies as $biography)
								$actorArray[] = $biography["title"].(!empty($r = $biography["role"]) ? " (".$r.")" : "");
						}
					}
					$text .= (!empty($actorArray) ? "<b>".$translator->trans('biographies.admin.Actor', [], 'validators', $language)." : </b>".implode(", ", $actorArray)."<br>" : "");
					$text .= !empty($entity->getTelevisionSerie()->getSource()) ? "<br><b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getTelevisionSerie()->getLanguage()->getAbbreviation())."</b><span>".(new FunctionsLibrary())->sourceString($entity->getTelevisionSerie()->getSource(), $entity->getTelevisionSerie()->getLanguage()->getAbbreviation())."</span>" : "";
					$text = "<div>".$parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath())."</div>";
					break;
				case "WitchcraftToolStore":
					$language = $entity->getWitchcraftTool()->getLanguage()->getAbbreviation();
					if(!empty($d = $entity->getWitchcraftTool()->getPhotoIllustrationFilename())) {
						$imgProperty = $d;
						$img = $entity->getWitchcraftTool()->getAssetImagePath().$imgProperty;
					} else {
						$imgProperty = $entity->getAssetImagePath()."category/".strtolower($entity->getCategory()).".jpg";
						$img = $imgProperty;
					}
					$text = $entity->getText()."<br>";
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= !empty($entity->getWitchcraftTool()->getSource()) ? "<br><b>".$translator->trans('news.index.Sources', [], 'validators', $language)."</b><span>".(new FunctionsLibrary())->sourceString($entity->getWitchcraftTool()->getSource(), $entity->getWitchcraftTool()->getLanguage()->getAbbreviation())."</span>" : "";
					$text = "<div>".$parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath())."</div>";
					break;
				case "Music":
					$language = !empty($ar = $entity->getArtist()) ? $ar->getLanguage()->getAbbreviation() : $entity->getAlbum()->getLanguage()->getAbbreviation();
					$text = $entity->getEmbeddedCode();
					$text .= "<br><b>".$translator->trans("music.admin.Morceau", [], "validators", $language)." :</b> ".$entity->getMusicPiece();
					$text .= "<br><b>".$translator->trans("music.admin.Duration", [], "validators", $language)." :</b> ".$apExtension->stringDurationVideoFilter($entity->getLength());
					
					if(!empty($a = $entity->getAlbum()))
						$text .= "<br><b>".$translator->trans("music.admin.Album", [], "validators", $language)." :</b> ".$a->getTitle();
					
					$artist = !empty($ar = $entity->getArtist()) ? $ar : $entity->getAlbum()->getArtist();
					
					$text .= "<br><b>".$translator->trans("album.admin.Artist", [], "validators", $language)." :</b> ".$artist->getTitle();
					$text .= "<hr>".$entity->getText();
					$text .= "<b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation());
					break;
			}

			if(in_array(Store::class, [get_class($entity), get_parent_class($entity)]) and !empty($storeButton)) {
				$language = $entity->getLanguage()->getAbbreviation();
				$text .= "<hr>";

				$text .= $storeButton;
			}

			$baseurl = "";
			
			if(!$isExternalImage) {
				$img = !empty($img) ? $imgSize->adaptImageSize(550, $img) : null;
				$baseurl = $request->getSchemeAndHttpHost().$request->getBasePath()."/";
			} else {
				$img = [
					350,
					'initial',
					$img
				];
			}

			$text = "<div style='font-size: 14pt; text-align: justify; font-family: Times New Roman;'>".$text."</div>";
		}

		switch($method) {
			case "POST";
				$response = $blogger->addPost($blogName, $accessToken, $title, (!empty($imgProperty) ? "<p><img src='".$baseurl.$img[2]."' style='width: ".$img[0]."; height:".$img[1]."' alt='' /></p>" : "").$text, $tags);
				break;
			case "PUT";
				$response = $blogger->updatePost($entity->getSocialNetworkIdentifiers()["Blogger"][$blogId]["id"], $blogName, $accessToken, $title, (!empty($imgProperty) ? "<p><img src='".$baseurl.$img[2]."' style='width: ".$img[0]."; height:".$img[1]."' alt='' /></p>" : "").$text, $tags);
				break;
			case "DELETE";
				$response = $blogger->deletePost($entity->getSocialNetworkIdentifiers()["Blogger"][$blogId]["id"], $blogName, $accessToken);
				break;
		}

		$obj = json_decode($response["response"]);

		if(in_array($response["http_code"], [Response::HTTP_OK, Response::HTTP_NO_CONTENT])) {
			if($response["http_code"] == Response::HTTP_NO_CONTENT) {
				$session->getFlashBag()->add('success', $translator->trans('admin.blogger.DeleteSuccess', [], 'validators'));
			} else {
				$url = "<br><a href='".$obj->url."' target='_blank'>".$obj->url."</a>";
				$session->getFlashBag()->add('success', $translator->trans('admin.blogger.Success', [], 'validators').$url);
			}

			if(method_exists($entity, "setSocialNetworkIdentifiers")) {
				switch($method) {
					case "POST";
					case "PUT";
						$labels = property_exists($obj, "labels") ? $obj->labels : [];
						$sni = $entity->getSocialNetworkIdentifiers();
						
						if(!isset($sni["Blogger"][$blogId]))
							$sni["Blogger"][$blogId] = ["id" => $obj->id, "url" => $obj->url, "labels" => $labels];

						$entity->setSocialNetworkIdentifiers($sni);
						break;
					case "DELETE";
						$sni = $entity->getSocialNetworkIdentifiers();

						unset($sni["Blogger"][$blogId]);

						$entity->setSocialNetworkIdentifiers($sni);
						break;
				}

				$em->persist($entity);
				$em->flush();
			}
		}
		else
			$session->getFlashBag()->add('error', $translator->trans('admin.blogger.Error', ["%code%" => $response["http_code"]], 'validators'));

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}
	
	// AI
	#[Route('/admin/ai', name: 'Admin_AI')]
	public function ai(Request $request, \App\Service\AI $ai) {
		return new JsonResponse(["message" => $ai->gemini($request->query->get("text"), $request->query->get("format"), $request->query->get("locale"))]);
	}

	// Diaspora
	#[Route('/admin/diaspora/{id}/{path}/{routeToRedirect}', name: 'Admin_Diaspora', defaults: ['method' => 'POST'])]
	public function diaspora(Request $request, EntityManagerInterface $em, Diaspora $diaspora, UrlGeneratorInterface $router, $id, $path, $routeToRedirect)
	{
		// dump($diaspora->getClients("https://activite-paranormale.net/admin/diasporapost", "pt"));
		// dd($diaspora->getOpenIDConfiguration("pt"));
		$session = $request->getSession();
		$session->set("id_diaspora", $id);

		$path = urldecode($path);
		$session->set("path_diaspora", $path);
		$session->set("routeToRedirect_diaspora", $routeToRedirect);
		$session->set("diaspora_area", $request->request->get("diaspora_area"));
		$session->set("diaspora_url", $request->request->get("diaspora_url"));

		$entity = $em->getRepository($path)->find($id);
		$redirectURL = $router->generate("Admin_DiasporaPost", [], UrlGeneratorInterface::ABSOLUTE_URL);
		$session->set("diaspora_redirect_uri", $redirectURL);
		$locale = $entity->getLanguage()->getAbbreviation();

		$accessToken = null;

		if(file_exists($diaspora->FILE_PATH)) {
			$tokenInfos = json_decode(file_get_contents($diaspora->FILE_PATH), true);

			if(!empty($tokenInfos) and isset($tokenInfos[$locale]["access_token"]))
			{
				if(!empty($at = $tokenInfos[$locale]["access_token"])) {
					$response = $diaspora->getUserInfo($at);

					if(!property_exists($response, "error")) {
						$accessToken = $tokenInfos[$locale]["access_token"];
					}
					elseif(isset($tokenInfos[$locale]["refresh_token"])) {
						$response = $diaspora->getAuthTokenByRefreshToken($tokenInfos[$locale]["refresh_token"], $locale);

						if(!isset($response["error"]))
							$accessToken = $tokenInfos[$locale]["access_token"];
					}
				}
			}
		}

		if(empty($accessToken)) {
			$session->set("diaspora_access_token_".$locale, null);
			$code = $diaspora->getCode($redirectURL, $locale);
		} else
			$session->set("diaspora_access_token_".$locale, $accessToken);

		return $this->redirect($this->generateUrl('Admin_DiasporaPost'));
	}

    #[Route('/admin/diasporapost', name: 'Admin_DiasporaPost')]
	public function diasporaPost(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APParseHTML $parser, Diaspora $diaspora, TranslatorInterface $translator, UrlGeneratorInterface $router)
	{
		$session = $request->getSession();

		$id = $session->get("id_diaspora");
		$path = $session->get("path_diaspora");
		$routeToRedirect = $session->get("routeToRedirect_diaspora");
		$redirectUri = $session->get("diaspora_redirect_uri");

		$entity = $em->getRepository($path)->find($id);

		if(empty($session->get("diaspora_access_token_".$entity->getLanguage()->getAbbreviation())) or !$session->has("diaspora_access_token_".$entity->getLanguage()->getAbbreviation())) {
			$code = $request->query->get("code");
			$accessToken = $diaspora->getAccessToken($redirectUri, $code, $entity->getLanguage()->getAbbreviation());
		} else
			$accessToken = $session->get("diaspora_access_token_".$entity->getLanguage()->getAbbreviation());

		$text = $session->get("diaspora_area")." ".$session->get("diaspora_url");

		$result = $diaspora->postMessage($text, $accessToken, $entity->getLanguage()->getAbbreviation());

		if(property_exists($result, "error"))
			$session->getFlashBag()->add('error', $translator->trans('admin.diaspora.Error', [], 'validators')." (".$result->error.": ".$result->error_description.")");
		else
			$session->getFlashBag()->add('success', $translator->trans('admin.diaspora.Success', [], 'validators'));
		
		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}

    #[Route('/admin/telegram/{id}/{path}/{routeToRedirect}', name: 'Admin_Telegram', defaults: ['method' => 'POST'])]
	public function telegram(Request $request, EntityManagerInterface $em, Telegram $telegram, TranslatorInterface $translator, UrlGeneratorInterface $router, $id, $path, $routeToRedirect)
	{
		$session = $request->getSession();
		$entity = $em->getRepository($path)->find($id);

		$text = $session->get("telegram_area")." ".$session->get("telegram_url");

		$result = $telegram->postMessage($text, $entity->getLanguage()->getAbbreviation());

		if(isset($result["error"]))
			$session->getFlashBag()->add('error', $translator->trans('admin.telegram.Error', [], 'validators')." (".$result["error"].": ".$result["error_description"].")");
		else
			$session->getFlashBag()->add('success', $translator->trans('admin.telegram.Success', [], 'validators'));
		
		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}

	// Shopify
	#[Route('/admin/shopify/{id}/{path}/{routeToRedirect}/{type}', name: 'Admin_Shopify')]
	public function shopify(Request $request, EntityManagerInterface $em, Shopify $shopify, UrlGeneratorInterface $router, $id, $path, $routeToRedirect, $type)
	{
		$session = $request->getSession();
		$session->set("id_shopify", $id);

		$path = urldecode($path);
		$session->set("path_shopify", $path);
		$session->set("routeToRedirect_shopify", $routeToRedirect);
		
		$tags = $request->request->get('shopify_tags');
		$session->set("tags_shopify", json_encode((empty($tags)) ? [] : $tags));

		$session->set("type_shopify", $type);

		$entity = $em->getRepository($path)->find($id);
		$redirectURL = $router->generate("Admin_ShopifyPost", [], UrlGeneratorInterface::ABSOLUTE_URL);

		$shopify->getCode($redirectURL);

		return new Response();
	}

    #[Route('/admin/shopifypost', name: 'Admin_ShopifyPost')]
	public function shopifyPost(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APParseHTML $parser, Shopify $shopify, TranslatorInterface $translator, UrlGeneratorInterface $router)
	{
		$code = $request->query->get("code");

		$session = $request->getSession();

		$id = $session->get("id_shopify");
		$path = $session->get("path_shopify");
		$tags = $session->get("tags_shopify");
		$type = $session->get("type_shopify");
		$routeToRedirect = $session->get("routeToRedirect_shopify");

		$blogName = $shopify->getCorrectBlog($type);

		$entity = $em->getRepository($path)->find($id);

		$text = "";
		$imgProperty = "";

		switch($entity->getRealClass())
		{
			case "Photo":
				$imgProperty = $entity->getPhotoIllustrationFilename();
				$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
				$text = $entity->getText();
				$text .= "<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators')."</b><br>".$imgCaption."</div>";
				$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
				$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
				break;
			case "News":
				$imgProperty = $entity->getPhotoIllustrationFilename();
				$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
				$text = $parser->replacePathImgByFullURL($entity->getAbstractText().$entity->getText()."<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators')."</b>".$imgCaption."</div><br><b>".$translator->trans('news.index.Sources', [], 'validators')."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation())."</span>", $request->getSchemeAndHttpHost().$request->getBasePath());
				$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
				break;
			case "Video":
				$video = $parser->getVideoResponsive($entity->getEmbeddedCode());
				if(!empty($entity->getMediaVideo()))
					$video = $parser->getVideoResponsive('<video width="550" height="309" controls><source src="'.$request->getSchemeAndHttpHost().'/'.$entity->getAssetVideoPath().'/'.$entity->getMediaVideo().'" type="video/mp4"></video>');

				$imgProperty = $entity->getPhoto();
				$text = $entity->getText()."<br>".$video;
				$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
				$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
				break;
			case "Grimoire":
				$imgProperty = $entity->getPhoto();
				$text = $entity->getText();
				$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
				break;
			case "Cartography":
				$imgProperty = $entity->getPhoto();
				$text = $entity->getText();
				$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
				$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
				break;
		}

		$baseurl = $request->getSchemeAndHttpHost().$request->getBasePath();
		$img = null;

		if(!empty($imgProperty)) {
			$img = $request->getSchemeAndHttpHost().'/'.$entity->getAssetImagePath().$imgProperty;
			$img = $baseurl."/".$entity->getAssetImagePath().$imgProperty;
		}

		$text = "<div style='font-size: 14pt; text-align: justify; font-family: Times New Roman;'>".$text."</div>";

		$response = $shopify->addPost($blogName, $request->query, $entity->getTitle(), $text, $img, $tags, new \DateTime(), $entity->authorToString());

		if($response["http_code"] == Response::HTTP_CREATED) {
			$urlStr = $shopify->getArticleUrl($blogName, $response["handle"]);
			$url = "<br><a href='".$urlStr."' target='_blank'>".$urlStr."</a>";
			$session->getFlashBag()->add('success', $translator->trans('admin.shopify.Success', [], 'validators').$url);

			switch($entity->getRealClass()) {
				case "Grimoire":
					$entity->setSource($urlStr);
					$em->persist($entity);
					$em->flush();
					break;
			}
		}
		else
			$session->getFlashBag()->add('error', $translator->trans('admin.shopify.Error', ["%code%" => $response["http_code"]], 'validators'));

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}

	// Pinterest
	#[Route('/admin/pinterest/{id}/{path}/{routeToRedirect}', name: 'Admin_Pinterest')]
	public function pinterest(Request $request, EntityManagerInterface $em, PinterestAPI $pinterest, TranslatorInterface $translator, UrlGeneratorInterface $router, $id, $path, $routeToRedirect)
	{
		$requestParams = $request->request;

		$entity = $em->getRepository(urldecode($path))->find($id);

		$currentURL = $router->generate($entity->getShowRoute(), ["id" => $entity->getId(), "title_slug" => $entity->getTitle()], UrlGeneratorInterface::ABSOLUTE_URL);
		$image = $this->getImageName($request, $entity, false);

		$image = $request->getUriForPath($entity->getAssetImagePath().$image);

		$res = $pinterest->send($entity, $image, $currentURL);

		if($res == "success")
			$this->addFlash('success', $translator->trans('admin.pinterest.Success', [], 'validators'));
		else
			$this->addFlash('error', $res);

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $id]));
	}

	// Twitter
	private function sendTwitter(Request $request, EntityManagerInterface $em, $id, $path, $router, $twitterAPI, $translator, $socialNetwork = "twitter") {
		$requestParams = $request->request;

		$path = urldecode($path);

		$entity = $em->getRepository($path)->find($id);
		$image = false;
		$url = $requestParams->get($socialNetwork."_url", null);

		if($requestParams->get("add_image") == 'on')
			$image = $this->getImageName($request, $entity, false);

		$currentURL = !empty($url) ? $url : $router->generate($entity->getShowRoute(), ["id" => $entity->getId(), "title_slug" => $entity->getTitle()], UrlGeneratorInterface::ABSOLUTE_URL);

		$locale = $entity->getLanguage()->getAbbreviation();

		if(method_exists($entity, "getRealClass")) {
			switch($entity->getRealClass()) {
				case "WitchcraftTool":
				case "Grimoire":
					$locale = "magic_".$entity->getLanguage()->getAbbreviation();
				break;
			}
		}

		$twitterAPI->setLanguage($entity->getLanguage()->getAbbreviation());
		$res = $twitterAPI->sendTweet($requestParams->get($socialNetwork."_area")." ".$currentURL, $locale, $image);

		if(property_exists($res, "status") or property_exists($res, "reason")) {
			$errorMessage = property_exists($res, "status") ? $res->status : $res->reason;
			$this->addFlash('error', $translator->trans('admin.twitter.FailedToSendTweet', [], 'validators'). " (".$errorMessage."; ".$res->detail.")");
		}
		elseif(property_exists($res, "data"))
			$this->addFlash('success', $translator->trans('admin.twitter.TweetSent', [], 'validators'));
	}

	// Bluesky
	private function sendBluesky(Request $request, EntityManagerInterface $em, $id, $path, $router, $bluesky, $translator, $socialNetwork = "bluesky") {
		$requestParams = $request->request;

		$path = urldecode($path);

		$entity = $em->getRepository($path)->find($id);
		$url = $requestParams->get($socialNetwork."_url", null);

		$currentURL = !empty($url) ? $url : $router->generate($entity->getShowRoute(), ["id" => $entity->getId(), "title_slug" => $entity->getTitle()], UrlGeneratorInterface::ABSOLUTE_URL);

		$locale = $entity->getLanguage()->getAbbreviation();

		$locale = $entity->getLanguage()->getAbbreviation();
		if(method_exists($entity, "getRealClass")) {
			switch($entity->getRealClass()) {
				case "WitchcraftTool":
				case "Grimoire":
					$locale = "magic_".$entity->getLanguage()->getAbbreviation();
				break;
			}
		}

		$res = $bluesky->postMessage($requestParams->get($socialNetwork."_area"), $currentURL, $locale);

		if(property_exists($res, "error"))
			$this->addFlash('error', $translator->trans('admin.bluesky.Failed', [], 'validators'). " (".$res->error."; ".$res->message.")");
		else
			$this->addFlash('success', $translator->trans('admin.bluesky.Success', [], 'validators'));
	}

	//Wordpress
	#[Route('/admin/wordpress/{id}/{path}/{routeToRedirect}', name: 'Admin_Wordpress')]
	public function wordpress(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, int $id, string $path, string $routeToRedirect)
	{
		$entity = $em->getRepository(urldecode($path))->find($id);
		
		$tags = $em->getRepository("\App\Entity\VideoTags")->findBy(["entity" => $id]);
		$tagsArray = [];
		
		foreach($tags as $tag)
			$tagsArray[] = $tag->getTagWord()->getTitle();

		$image = null;
		$text = null;

		switch($entity->getRealClass())
		{
			case "Video":
				if(!empty($entity->getPhoto()))
					$image = $request->getSchemeAndHttpHost().'/'.$entity->getAssetImagePath().$entity->getPhoto();
				
				$text = $entity->getText()."<hr>".$entity->getEmbeddedCode();
				break;
		}

		$data = [
			"_id" => get_class($entity)."_".$entity->getId(),
			"image" => $image,
			"title" => $entity->getTitle(),
			"text" => "<div>".str_replace("\r\n", " ", $text)."</div>",
			'date' => date('Y-m-d H:i:s'),
			'author' => $this->getUser()->getUsername(),
			"tags" => $tagsArray,
			"categories" => [$entity->getTheme()->getTitle()],
		];

		$api = new Wordpress();
		$result = $api->postMessage($data);

		if($result["status"] == "success") {
			$this->addFlash('success', $translator->trans('admin.wordpress.Success', [], 'validators'));
		} else
			$this->addFlash('error', $translator->trans('admin.wordpress.Failed', [], 'validators')." ".$result["message"]);

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $id]));
	}

	// Muse
	#[Route('/admin/muse/{id}/{path}/{routeToRedirect}', name: 'Admin_Muse')]
	public function muse(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, int $id, string $path, string $routeToRedirect)
	{
		$entity = $em->getRepository(urldecode($path))->find($id);

		$api = new \App\Service\Muse();

		$family = null;

		if($request->request->has("image_generated") and !empty($img = $request->request->get("image_generated"))) {
			$images[] = [
				"imgBase64" => $img,
				"identifier" => md5(base64_decode($img)),
				"image" => md5(base64_decode($img)).".png"
			];
		}

		$generator = new \Ausi\SlugGenerator\SlugGenerator;
		$tagArray = !empty($entity->getTags()) ? array_map(function($e) use($generator, $entity) { return ["identifier" => $generator->generate($e->value)."-".$entity->getLanguage()->getAbbreviation(), "title" => $e->value, "slug" => $generator->generate($e->value), "internationalName" => $generator->generate($e->value)]; }, json_decode($entity->getTags())) : [];

		if($entity->isProverbFamily()) {
			$family = "proverbs";

			$data = [
				"text" => $entity->getTextQuotation(),
				"identifier" => !empty($idt = $entity->getIdentifier()) ? $idt : "",
				"language" => ["abbreviation" => $entity->getLanguage()->getAbbreviation()],
				"country" => [
					"internationalName" => $entity->getCountry()->getInternationalName()
				],
				"tags" => $tagArray,
			];
		} else {
			$family = $entity->isPoemFamily() ? "poems" : "quotes";

			$source = !empty($s = $entity->getSource()) ? json_decode($s, true) : null;

			$sourceIdentifier = null;

			if(!empty($source)) {
				if(isset($source["isbn13"]) and !empty($isbn13 = $source["isbn13"]))
					$sourceIdentifier = $isbn13;
				elseif(isset($source["isbn10"]) and !empty($isbn10 = $source["isbn10"]))
					$sourceIdentifier = $isbn10;
			}

			$biography = $entity->getAuthorQuotation();

			$birthDate = explode("-", $biography->getBirthDate());
			$deathDate = explode("-", $biography->getDeathDate());

			$fileManagement = [];

			if(!empty($biography->getImgBase64())) {
				$fileManagement = [
					"imgBase64" => $biography->getImgBase64(),
					"photo" => !empty($f = $biography->getIllustration()) ? $f->getRealNameFile() : null,
					"description" => !empty($f = $biography->getIllustration()) ? "<a href='".$f->getUrlSource()."'>Source</a>, ".$f->getLicense().", ".$f->getAuthor() : null
				];
			}

			$data = [
				"title" => $entity->getTitle(),
				"text" => nl2br($entity->getTextQuotation()),
				"identifier" => !empty($idt = $entity->getIdentifier()) ? $idt : "",
				"language" => ["abbreviation" => $entity->getLanguage()->getAbbreviation()],
				"biography" => [
					"title" => $biography->getTitle(),
					"text" => $biography->getText(),
					"dayBirth" => (isset($birthDate[2]) and !empty($birthDate[2])) ? intval($birthDate[2]) : null,
					"monthBirth" => (isset($birthDate[1]) and !empty($birthDate[1])) ? intval($birthDate[1]) : null,
					"yearBirth" => (isset($birthDate[0]) and !empty($birthDate[0])) ? intval($birthDate[0]) : null,
					"dayDeath" => (isset($deathDate[2]) and !empty($deathDate[2])) ? intval($deathDate[2]) : null,
					"monthDeath" => (isset($deathDate[1]) and !empty($deathDate[1])) ? intval($deathDate[1]) : null,
					"yearDeath" => (isset($deathDate[0]) and !empty($deathDate[0])) ? intval($deathDate[0]) : null,
					"country" => ["internationalName" => !empty($n = $biography->getNationality()) ? $n->getInternationalName() : ""],
					"language" => ["abbreviation" => $biography->getLanguage()->getAbbreviation()],
					"wikidata" => $biography->getWikidata(),
					"fileManagement" => $fileManagement
				],
				// "source" => ["identifier" => $sourceIdentifier],
				"tags" => $tagArray,
			];
		}

		$result = $api->addPost($data, $api->getOauth2Token(), $family);

		if($result->type == "error")
			$this->addFlash('error', $result->{"hydra:title"});
		else {
			$entity->setIdentifier($result->identifier);
			$em->persist($entity);
			$em->flush();

			if(!empty($images))
				$result = $api->addImage($result->identifier, $images[0], $api->getOauth2Token(), $family);

			$this->addFlash('success', $translator->trans('admin.muse.Success', [], 'validators'));
		}

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $id]));
	}

	// Tumblr
	#[Route('/admin/tumblr/{id}/{path}/{routeToRedirect}', name: 'Admin_Tumblr')]
	public function tumblr(Request $request, TumblrAPI $tumblr, $id, $path, $routeToRedirect)
	{
		$session = $request->getSession();
		$session->set("id_tumblr", $id);
		$session->set("path_tumblr", urldecode($path));
		$session->set("routeToRedirect_tumblr", $routeToRedirect);

		$tags = $request->request->get('tumblr_tags');
		$session->set("tumblr_tags", json_encode((empty($tags)) ? [] : $tags));

		$tumblr->connect();

		exit();
	}

	#[Route('/admin/tumblrpost', name: 'Admin_TumblrPost')]
	public function tumblrPost(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APParseHTML $parser, TumblrAPI $tumblr, TranslatorInterface $translator)
	{
		$session = $request->getSession();

		$id = $session->get("id_tumblr");
		$path = $session->get("path_tumblr");
		$tags = implode(",", json_decode($session->get("tumblr_tags")));
		$routeToRedirect = $session->get("routeToRedirect_tumblr");

		$entity = $em->getRepository($path)->find($id);

		$img = null;

		if(method_exists($entity, "getAssetImagePath")) {
			$img = $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename();
			$imgCaption = null;

			if(method_exists($entity, "getPhotoIllustrationCaption"))
				$imgCaption = "<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators')."</b><br>".$imgCaption."</div>";

			$img = $imgSize->adaptImageSize(550, $img);
		}

		$baseurl = $request->getSchemeAndHttpHost().$request->getBasePath();

		$title = $entity->getTitle();

		switch($entity->getRealClass())
		{
			case "Store":
				$body = $entity->getImageEmbeddedCode();
				$body .= $entity->getText();
					
				if(in_array(Store::class, [get_class($entity), get_parent_class($entity)])) {
					$language = $entity->getLanguage()->getAbbreviation();
					$body .= "<hr>";
					if(Store::ALIEXPRESS_PLATFORM == $entity->getPlatform())
						$body .= '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #E52F20; padding: 0.375rem 0.75rem;background-color: #E52F20;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAliexpress', [], 'validators', $language).'</a></div>';
					elseif(Store::AMAZON_PLATFORM == $entity->getPlatform())
						$body .= '<div style="text-align: center"><a href="'.$entity->getExternalAmazonStoreLink().'" style="border: 1px solid #ff9900; padding: 0.375rem 0.75rem;background-color: #ff9900;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAmazon', [], 'validators', $language).'</a></div>';
					elseif(Store::SPREADSHOP_PLATFORM == $entity->getPlatform())
						$body .= '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #a73c9e; padding: 0.375rem 0.75rem;background-color: #a73c9e;border-radius: 0.25rem;color: white !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnSpreadshop', [], 'validators', $language).'</a></div>';
					elseif(Store::TEMU_PLATFORM == $entity->getPlatform())
						$body .= '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #ff6d00; padding: 0.375rem 0.75rem;background-color: #ff6d00;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAliexpress', [], 'validators', $language).'</a></div>';
				}
				
				break;
		}

		// Fix Tumblr bugs 
		$body = str_replace(["\r\n", "\n", "\t", "\r"], ' ', $body);
		$body = str_replace('img src=//', 'img src="https://', $body);

		$blog = "gothic-".$entity->getLanguage()->getAbbreviation();

		$tumblr->addPost($blog, $title, $body, $tags);

		$session->getFlashBag()->add('success', $translator->trans('admin.tumblr.Success', [], 'validators'));

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}

	// Facebook
	#[Route('/admin/facebook/{id}/{path}/{routeToRedirect}', name: 'Admin_Facebook')]
	public function facebook(Request $request, EntityManagerInterface $em, UrlGeneratorInterface $router, Facebook $facebook, TranslatorInterface $translator, $id, $path, $routeToRedirect)
	{
		$path = urldecode($path);
		$entity = $em->getRepository($path)->find($id);

		$this->sendFacebook($request, $em, $id, $path, $router, $facebook, $translator);

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}

	// Flickr
	#[Route('/admin/flickr/{id}/{path}/{routeToRedirect}', name: 'Admin_Flickr')]
	public function flickr(Request $request, EntityManagerInterface $em, UrlGeneratorInterface $router, Flickr $flickr, TranslatorInterface $translator, $id, $path, $routeToRedirect)
	{
		$requestParams = $request->request;
		$flickrLimit = 1000;
	
		if($request->query->has("method") and $request->query->get("method") == "DELETE") {
			$flickr->deletePhoto($request->query->get("photoId"));
			$entity = $em->getRepository(urldecode($path))->find($id);
			$flickerGroupId = $flickr->getParametersByLocale($entity->getLanguage()->getAbbreviation());
			$sni = $entity->getSocialNetworkIdentifiers();
			unset($sni["Flickr"][$flickerGroupId]);
			$entity->setSocialNetworkIdentifiers($sni);

			$em->persist($entity);
			$em->flush();

			$this->addFlash("success", $translator->trans('admin.flickr.DeleteSuccess', [], 'validators'));

			return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
		}

		if($flickr->getCounts() >= $flickrLimit) {
			$photoId = $flickr->getOldestPhoto();
			$entityNamespaces = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
			$data = null;

			foreach ($entityNamespaces as $entityClass) {
				if (class_exists($entityClass)) {
					if(!empty($data))
						continue;

					$reflectionClass = new \ReflectionClass($entityClass);
					
					$mappedSuperclassAnnotation = (new \Doctrine\Common\Annotations\AnnotationReader())->getClassAnnotation($reflectionClass, \Doctrine\ORM\Mapping\MappedSuperclass::class);
					if ($mappedSuperclassAnnotation !== null) {
						continue;
					}

					if ($reflectionClass->hasMethod("setSocialNetworkIdentifiers")) {
						$data = $em->getRepository($entityClass)->createQueryBuilder('u')->where("u.socialNetworkIdentifiers LIKE '%Flickr%'")->andWhere("u.socialNetworkIdentifiers LIKE :photoId")->setParameter("photoId", "%".$photoId."%")->getQuery()->getOneOrNullResult();

						if(!empty($data)) {
							$flickerGroupId = $flickr->getParametersByLocale($data->getLanguage()->getAbbreviation());
							$sni = $data->getSocialNetworkIdentifiers();
							unset($sni["Flickr"][$flickerGroupId]);
							$data->setSocialNetworkIdentifiers($sni);

							$em->persist($data);
							$em->flush();
						}
					}
				}
			}
			
			$flickr->deletePhoto($photoId);
		}

		$entity = $em->getRepository(urldecode($path))->find($id);
		$flickerGroupId = $flickr->getParametersByLocale($entity->getLanguage()->getAbbreviation());

		$photo = realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."public".DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$entity->getIllustration()->getRealNameFile());

		$sni = $entity->getSocialNetworkIdentifiers();

		if(isset($sni["Flickr"]) and isset($sni["Flickr"][$flickerGroupId])) {
			$resUpload = $flickr->setMeta($sni["Flickr"][$flickerGroupId]["id"], $entity->getTitle(), $entity->getText());
		} else {
			$resUpload = $flickr->uploadPhoto($entity->getTitle(), $photo, $entity->getLanguage()->getAbbreviation(), $entity->getText());

			if(isset($resUpload["success"])) {
				$photoId = $resUpload["success"];
				$res = $flickr->postImageGroup($entity->getLanguage()->getAbbreviation(), $photoId);

				$sni["Flickr"][$flickerGroupId] = ["id" => $photoId, "url" => "https://www.flickr.com/photos/188024915@N02/{$photoId}/in/pool-$flickerGroupId/", "labels" => null];
				$entity->setSocialNetworkIdentifiers($sni);
			}
		}

		$em->persist($entity);
		$em->flush();

		$message = isset($resUpload["success"]) ? ['state' => 'success', 'message' => $translator->trans('admin.flickr.Success', [], 'validators'). (!empty($resUpload["success"]) ? " (".$resUpload["success"].")" : "")] : ['state' => 'error', 'message' => $translator->trans('admin.flickr.Failed', [], 'validators')];

		$this->addFlash($message["state"], $message["message"]);

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}

	// vk
	#[Route('/admin/vk/{id}/{path}/{routeToRedirect}', name: 'Admin_VK')]
	public function vk(Request $request, EntityManagerInterface $em, UrlGeneratorInterface $router, VK $vk, TranslatorInterface $translator, $id, $path, $routeToRedirect)
	{
		$session = $request->getSession();

		$accessToken = null;
		$redirectUri = $this->generateUrl("Admin_VK", ["id" => $id, "path" => $path, "routeToRedirect" => $routeToRedirect], UrlGeneratorInterface::ABSOLUTE_URL);

		/*if($request->query->has("code")) {
			$accessToken = $vk->getAccessToken($redirectUri, $request->query->get("code"))->access_token;
		} else {*/
			$session->set("vk_area", $request->request->get("vk_area"));
			$session->set("vk_url", $request->request->get("vk_url"));
			/*$vk->getCode($redirectUri);
		}
		*/
		// $vk->getCode($redirectUri);
		//if(!empty($accessToken)) {
			$requestParams = $request->request;
			$path = urldecode($path);

			$entity = $em->getRepository($path)->find($id);
			$url = $session->get("vk_url");

			$currentURL = !empty($url) ? $url : $router->generate($entity->getShowRoute(), ["id" => $entity->getId(), "title_slug" => $entity->getTitle()], UrlGeneratorInterface::ABSOLUTE_URL);
// dd($session->get("vk_area"), $currentURL);
			$res = $vk->postMessage($session->get("vk_area"), $currentURL);

			$message = (property_exists($res, "error")) ? ['state' => 'error', 'message' => $translator->trans('admin.vk.Failed', [], 'validators'). "(".$res->error->error_msg.")"] : ['state' => 'success', 'message' => $translator->trans('admin.vk.Success', [], 'validators')];

			$this->addFlash($message["state"], $message["message"], [], 'validators');
		//} else
		//	$this->addFlash('error', $translator->trans('admin.vk.Failed', [], 'validators'), [], 'validators');

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}

	// Instagram
	#[Route('/admin/instagram/{id}/{path}/{routeToRedirect}', name: 'Admin_Instagram')]
	public function instagram(Request $request, EntityManagerInterface $em, UrlGeneratorInterface $router, Instagram $instagram, TranslatorInterface $translator, $id, $path, $routeToRedirect)
	{
		$this->sendInstagram($request, $em, $id, $path, $router, $instagram, $translator, "instagram");

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $entity->getId()]));
	}

	// Mastodon
    #[Route('/admin/twittermastodonbluesky/{id}/{path}/{routeToRedirect}/{socialNetwork}/{family}', name: 'Admin_TwitterMastodonBluesky')]
	public function twitterMastodonBluesky(Request $request, EntityManagerInterface $em, UrlGeneratorInterface $router, TwitterAPI $twitter, Mastodon $mastodon, Bluesky $bluesky, Facebook $facebook, Instagram $instagram, TranslatorInterface $translator, $id, $path, $routeToRedirect, $socialNetwork, $family) {
		$socialNetworks = explode("|", $family);
		if(in_array("twitter", $socialNetworks))
			$this->sendTwitter($request, $em, $id, $path, $router, $twitter, $translator, $socialNetwork);
		if(in_array("mastodon", $socialNetworks))
			$this->sendMastodon($request, $em, $id, $path, $router, $mastodon, $translator, $socialNetwork);
		if(in_array("bluesky", $socialNetworks))
			$this->sendBluesky($request, $em, $id, $path, $router, $bluesky, $translator, $socialNetwork);
		if(in_array("facebook", $socialNetworks))
			$this->sendFacebook($request, $em, $id, $path, $router, $facebook, $translator, $socialNetwork);
		if(in_array("instagram", $socialNetworks))
			$this->sendInstagram($request, $em, $id, $path, $router, $instagram, $translator, $socialNetwork);

		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $id]));
	}

	private function sendFacebook($request, $em, $id, $path, $router, $facebook,  $translator, $fieldName = "facebook") {
		$requestParams = $request->request;

		$path = urldecode($path);

		$entity = $em->getRepository($path)->find($id);
		$image = false;
		$url = $requestParams->get($fieldName."_url", null);

		$currentURL = !empty($url) ? $url : $router->generate($entity->getShowRoute(), ["id" => $entity->getId(), "title_slug" => $entity->getTitle()], UrlGeneratorInterface::ABSOLUTE_URL);

		$res = json_decode($facebook->postMessage($currentURL, $request->request->get("{$fieldName}_area"), $entity->getLanguage()->getAbbreviation()));

		$message = (property_exists($res, "error")) ? ['state' => 'error', 'message' => $translator->trans('admin.facebook.Failed', [], 'validators'). "(".$res->error->message.")"] : ['state' => 'success', 'message' => $translator->trans('admin.facebook.Success', [], 'validators')];

		$this->addFlash($message["state"], $message["message"], [], 'validators');
	}

	private function sendInstagram($request, $em, $id, $path, $router, $instagram,  $translator, $fieldName = "Instagram") {
		$requestParams = $request->request;

		$path = urldecode($path);

		$entity = $em->getRepository($path)->find($id);

		$baseurl = $request->getSchemeAndHttpHost().$request->getBasePath();

		$url = $requestParams->get($fieldName."_url");

		$image_url = null;

		switch($entity->getRealClass())
		{
			case "Store":
			case "BookStore":
				$image_url = $baseurl."/".$entity->getAssetImagePath().$entity->getPhoto();
				break;
			default:
				$image_url = $baseurl."/".$entity->getAssetImagePath().$entity->getIllustration()->getRealNameFile();
				break;
		}
		
		if(empty($image_url))
			return;

		$res = json_decode($instagram->addMediaMessage($image_url, $request->request->get($fieldName."_area")." ".$url, $entity->getLanguage()->getAbbreviation()));

		$message = (property_exists($res, "error")) ? ['state' => 'error', 'message' => $translator->trans('admin.instagram.Failed', [], 'validators'). "(".$res->error->message.")"] : ['state' => 'success', 'message' => $translator->trans('admin.instagram.Success', [], 'validators')];

		$this->addFlash($message["state"], $message["message"], [], 'validators');
	}

	private function sendMastodon($request, $em, $id, $path, $router, $mastodon, $translator, $fieldName = "mastodon") {
		$requestParams = $request->request;

		$path = urldecode($path);

		$entity = $em->getRepository($path)->find($id);
		$image = false;
		$url = $requestParams->get($fieldName."_url", null);

		$currentURL = !empty($url) ? $url : $router->generate($entity->getShowRoute(), ["id" => $entity->getId(), "title_slug" => $entity->getTitle()], UrlGeneratorInterface::ABSOLUTE_URL);

		$locale = $entity->getLanguage()->getAbbreviation();
		if(method_exists($entity, "getRealClass")) {
			switch($entity->getRealClass()) {
				case "WitchcraftTool":
				case "Grimoire":
					$locale = "magic_".$entity->getLanguage()->getAbbreviation();
				break;
			}
		}

		$res = $mastodon->postMessage($currentURL, $request->request->get($fieldName."_area"), $locale);

		$message = (property_exists($res, "error")) ? ['state' => 'error', 'message' => $translator->trans('admin.mastodon.Failed', [], 'validators'). " (".$res->error->message.")"] : ['state' => 'success', 'message' => $translator->trans('admin.mastodon.Success', [], 'validators')];

		$this->addFlash($message["state"], $message["message"], [], 'validators');
	}

	#[Route('/admin/wikidata_generic', name: 'Admin_WikidataGeneric')]
	public function wikidataGeneric(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");

		$res = $wikidata->getGenericDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
	}

	#[Route('/admin/wikidata_generic_load_image', name: 'Admin_WikidataGenericLoadImage')]
	public function wikidataGenericLoadImage(Request $request, EntityManagerInterface $em, \App\Service\Wikidata $wikidata)
	{
		$url = $request->query->get("url");

		$urlHost = parse_url($url, PHP_URL_HOST);

		$res = [];

		if(str_contains($urlHost, "wikipedia") or str_contains($urlHost, "wikidata") or str_contains($urlHost, "wikimedia")) {
			$urlArray = explode(":", $url);
			$filename = $urlArray[count($urlArray) - 1];
			$res = $wikidata->getImageInfos($filename);
		} elseif(str_contains($urlHost, "pixabay")) {
			$pixabay = new \App\Service\Pixabay();
			$res = $pixabay->getImageInfos($url);
		} elseif(str_contains($urlHost, "flickr")) {
			$flickr = new \App\Service\Flickr();
			$res = $flickr->getImageInfos($url);
		} elseif(str_contains($urlHost, "starryai")) {
			$res = [
			"url" => $url,
			"user" => "StarryAI",
			"license" => "CC0",
			"source" => "https://starryai.com/",
			"description" => ""];
		} elseif(str_contains($urlHost, "pixai")) {
			$res = [
				"url" => $url,
				"user" => "Pixai.art",
				"license" => "CC0",
				"source" => "https://pixai.art/",
				"description" => ""];
		} elseif(str_contains($urlHost, "x.com")) {
			$res = [
				"url" => $url,
				"user" => "Grok",
				"license" => "CC0",
				"source" => "https://x.com/",
				"description" => ""];
		} elseif(str_contains($urlHost, "gemini")) {
			$res = [
				"url" => "",
				"user" => "Gemini",
				"license" => "CC0",
				"source" => "https://google.gemini.com/",
				"description" => ""];
		}

		return new JsonResponse($res);
	}

	#[Route('/admin/amazon_image', name: 'Admin_AmazonImage')]
	public function amazonImage(Request $request, Amazon $amazon) {
		$itemId = $request->query->get("paste");

		$item = $amazon->getItem($itemId);
		
		if(!empty($item)) {
			$url = $item->Images->Primary->Large->URL;
			$width = $item->Images->Primary->Large->Width;
			$height = $item->Images->Primary->Large->Height;

			return new JsonResponse(["url" => $url, "width" => $width, "height" => $height]);
		}

		return new JsonResponse(["url" => null, "width" => null, "height" => null]);
	}

	private function getImageName(Request $request, $entity, $url = true)
	{
		$imageName = null;

		switch($entity->getRealClass())
		{
			case "Cartography":
			case "Video":
			case "Book":
				$imageName = $entity->getPhoto();
				break;
			case "Photo":
			case "News":
				$imageName = $entity->getPhotoIllustrationFilename();
				break;
		}

		if(!empty($imageName)) {
			$path = $entity->getAssetImagePath().$imageName;
			return ($url) ? $request->getUriForPath('/'.$path) : $path;	
		}

		return null;
	}

	#[Route('/admin/sitemap', name: 'Admin_Sitemap')]
	public function generateSitemap(Request $request, EntityManagerInterface $em)
	{
		$urls = $em->getRepository(\App\Entity\News::class)->getDatasForSitemap((new \App\Entity\News())->getShowRoute());

		if(!file_exists("sitemaps"))
			mkdir("sitemaps", 0777, true);
		$urls = array_map(function($e) { $e["url"] = $this->generateUrl((new \App\Entity\News())->getShowRoute(), ["id" => $e["id"], "title_slug" => $e["slug"]], UrlGeneratorInterface::ABSOLUTE_URL); return $e; }, $urls);

		$this->generateSitemapFile($urls, "news");
		
		return $this->redirectToRoute("Admin_Maintenance");
	}

	function generateSitemapFile($urls, $filename) {
		$xml = new \DOMDocument('1.0', 'UTF-8');

		$xml->formatOutput = true;
		$xml->preserveWhiteSpace = false;

		$urlset = $xml->createElement('urlset');
		$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$xml->appendChild($urlset);

		foreach ($urls as $url) {
			$urlElement = $xml->createElement('url');
			$urlset->appendChild($urlElement);

			$loc = $xml->createElement('loc', htmlspecialchars($url["url"]));
			$urlElement->appendChild($loc);

			$lastmod = $xml->createElement("lastmod", $url["writingDate"]->format("Y-m-d"));
			$urlElement->appendChild($lastmod);
		}

		$xml->save('sitemaps/sitemap_'.$filename.'.xml');
	}

	#[Route('/admin/sqlExplorer', name: 'Admin_SQLExplorer')]
    public function sqlExplorer(Request $request, EntityManagerInterface $em, PaginatorNativeSQL $paginator)
    {
		$conn = $em->getConnection();

		$mode = $request->query->get("mode", null);
		$action = $request->query->get("action", "show");
		$session = $request->getSession();
		$tables = array_map(function($e) { return array_values($e)[0];}, $conn->fetchAllAssociative("SHOW TABLES"));

		if($mode == "table") {
			$table = $request->query->get("table");
			$num_results_on_page = 15;
			$columns = [];
			$pagination = [];
			
			$columnDatas = $conn->fetchAllAssociative("select COLUMN_NAME , DATA_TYPE, COLUMN_KEY,
				(select group_concat(isk.REFERENCED_TABLE_NAME, '#')
					from
						information_schema.key_column_usage isk
					where
						isk.referenced_table_name is not null
						and isk.table_schema = DATABASE()
						and isk.TABLE_NAME = '$table' AND isk.COLUMN_NAME = isc.COLUMN_NAME) AS foreign_key
					from information_schema.columns isc
					where isc.table_schema = DATABASE()
					and isc.table_name = '$table'");

			foreach($columnDatas as $columnData)
				$columns[$columnData["COLUMN_NAME"]] = ["foreign_key" => trim($columnData["foreign_key"], "#"), "data_type" => $columnData["DATA_TYPE"], "primary_key" => $columnData["COLUMN_KEY"] == "PRI"];

			if($action == "edit" and $request->isMethod('post')) {
				$updateData = $request->request->all();
				unset($updateData["save_form"]);
				$primaryKeys = json_decode($request->query->get("primary_keys"), true);

				if(isset($updateData["delete_form"])) {
					unset($updateData["delete_form"]);

					try {
						$result = $conn->delete($table, $primaryKeys);

						$error = null;
						$success = "Item has been deleted";

						if($result == 0) {
							$session->getFlashBag()->add('error', "An error occurs");
							$success = null;
						} else
							$session->getFlashBag()->add('success', $success);
						return $this->redirect($this->generateUrl('Admin_SQLExplorer', ["mode" => "table", "table" => $table]));
					} catch(\Exception $e) {
						return $this->render('admin/Admin/sql.html.twig', ["tables" => $tables, "columns" => $columns, "datas" => $updateData, "error" => $e->getMessage()]);
					}
				}

				try {
					$nullDatas = [];
					
					if(isset($updateData["null_data"])) {
					$nullDatas = $updateData["null_data"];
					unset($updateData["null_data"]);
					}

					foreach($updateData as $key => $ud) {
						if((isset($nullDatas[$key]) and $nullDatas[$key][0] == "on") or $updateData[$key] == null)
							$updateData[$key] = null;
					}

					$result = $conn->update($table, $updateData, $primaryKeys);
					$session->getFlashBag()->add('success', "Item has been updated.");
					return $this->redirect($this->generateUrl('Admin_SQLExplorer', ["mode" => "table", "table" => $table]));
				} catch(\Exception $e) {
					$session->getFlashBag()->add('error', $e->getMessage());
					return $this->render('admin/Admin/sql.html.twig', ["tables" => $tables, "columns" => $columns, "datas" => $updateData, "error" => $e->getMessage()]);
				}
			}

			if($action == "edit") {
				$params = [];
				foreach(json_decode($request->query->get("primary_keys"), true) as $key => $value)
					$params[] = $key." = ".$value;

				$updateData = $conn->fetchAssociative("SELECT * FROM ".$request->query->get("table")." WHERE ".implode(" AND ", $params));
				$error = null;
				if(empty($updateData))
					$error = "No rows.";
					$session->getFlashBag()->add('error', $error);
					return $this->render('admin/Admin/sql.html.twig', ["tables" => $tables, "columns" => $columns, "datas" => $updateData, "error" => $error]);
			}

			if(!empty($table)) {
				$page = $request->query->get("page");
				$where = $request->query->has("where") ? implode(" AND ", $request->query->all("where", [])) : null;

				$sortBy = $request->query->get("sortBy", null);
				$sortDir = $request->query->get("sortDir", "ASC");
				$orderBy = !empty($sortBy) ? " ORDER BY ".$sortBy." ".$sortDir : "";

				$pagination = $paginator->paginate(
					"SELECT * FROM ".$request->query->get("table").(!empty($where) ? " WHERE ".$where : "").$orderBy,
					($request->query->has("page")) ? $page : 1,
					$num_results_on_page,
					$conn
				);

				$pagination->setCustomParameters(['align' => 'center']);
			}

			return $this->render('admin/Admin/sql.html.twig', ["tables" => $tables, "columns" => $columns, "pagination" => $pagination]);
		} elseif($mode == "query") {
			$res = [];
			if($request->request->has("sql_area")) {
				$sqls = array_filter(explode(";", $request->request->get("sql_area")));
				$datas = null;

				foreach($sqls as $sql) {
					$sql = trim($sql);
					if(empty($sql))
						continue;

					$success = null;

					try {
						if($this->isSelectQuery($sql))
							$datas = $conn->fetchAllAssociative($sql);
						else 
							$success = "Query executed OK, ".$conn->executeQuery($sql)->rowCount()." rows affected.";

						$res[] = ["datas" => $datas, "columns" => !empty($datas) ? array_keys($datas[0]) : null, "query" => $sql, "success" => $success];
					} catch(\Exception $e) {
						$res[] = ["datas" => null, "columns" => null, "query" => $sql, "error" => $e->getMessage()];
					}
				}
			}

			return $this->render('admin/Admin/sql.html.twig', ["res" => $res, "tables" => $tables]);
		}

        return $this->render('admin/Admin/sql.html.twig', ["tables" => $tables, "tableInfos" => $conn->fetchAllAssociative("SHOW TABLE STATUS")]);
    }

	private function isSelectQuery($sql) {
		return str_starts_with(strtolower($sql), "select ");
	}

	#[Route('/admin/publish/social_network', name: 'Admin_PublishSocialNetwork')]
	public function publishSocialNetwork(Request $request, TranslatorInterface $translator, TwitterAPI $twitterAPI, Bluesky $bluesky, Mastodon $mastodon, Facebook $facebook, Diaspora $diaspora) {
		$form = $this->createForm(\App\Form\Type\SocialNetworkAdminType::class);
		$rePostForm = $this->createForm(\App\Form\Type\RePostAdminType::class);

		if($request->isMethod('post')) {
			$form->handleRequest($request);
			$data = $form->getData();
			$socialNetworks = $data["socialNetwork"];

			if($form->isValid()) {
				foreach($socialNetworks as $socialNetwork) {
					list($socialNetwork, $locale) = explode("_", $socialNetwork, 2);

					switch($socialNetwork) {
						case "twitter":
							$res = $twitterAPI->postLink($data["text"]." ".$data["url"], $locale);

							if(property_exists($res, "status") or property_exists($res, "reason")) {
								$errorMessage = property_exists($res, "status") ? $res->status : $res->reason;
								$this->addFlash('error', $translator->trans('admin.twitter.FailedToSendTweet', [], 'validators'). " (".$errorMessage."; ".$res->detail.")");
							}
							elseif(property_exists($res, "data"))
								$this->addFlash('success', $translator->trans('admin.twitter.TweetSent', [], 'validators'));
						break;
						case "bluesky":
							$res = $bluesky->postMessage($data["text"], $data["url"], $locale);

							if(property_exists($res, "error"))
								$this->addFlash('error', $translator->trans('admin.bluesky.Failed', [], 'validators'). " (".$res->error."; ".$res->message.")");
							else
								$this->addFlash('success', $translator->trans('admin.bluesky.Success', [], 'validators'));
						break;
						case "mastodon":
							$res = $mastodon->postMessage($data["url"], $data["text"], $locale);
							$message = (property_exists($res, "error")) ? ['state' => 'error', 'message' => $translator->trans('admin.mastodon.Failed', [], 'validators'). "(".$res->error->message.")"] : ['state' => 'success', 'message' => $translator->trans('admin.mastodon.Success', [], 'validators')];
							$this->addFlash($message["state"], $message["message"], [], 'validators');
						break;
						case "facebook":
							$res = json_decode($facebook->postMessage($data["url"], $data["text"], $locale));
							$message = (property_exists($res, "error")) ? ['state' => 'error', 'message' => $translator->trans('admin.facebook.Failed', [], 'validators'). "(".$res->error->message.")"] : ['state' => 'success', 'message' => $translator->trans('admin.facebook.Success', [], 'validators')];
							$this->addFlash($message["state"], $message["message"], [], 'validators');
						break;
					}
				}

				$form = $this->createForm(\App\Form\Type\SocialNetworkAdminType::class);
			}
		}

		return $this->render("admin/Admin/socialNetwork.html.twig", ["form" => $form->createView(), "rePostForm" => $rePostForm->createView()]);
	}

	#[Route('/admin/publish/repost', name: 'Admin_RePost')]
	public function rePost(Request $request, TranslatorInterface $translator, TwitterAPI $twitterAPI) {
		$form = $this->createForm(\App\Form\Type\SocialNetworkAdminType::class);
		$rePostForm = $this->createForm(\App\Form\Type\RePostAdminType::class);

		if($request->isMethod('post')) {
			$rePostForm->handleRequest($request);
			$data = $rePostForm->getData();
			$socialNetworks = $data["socialNetwork"];

			if($rePostForm->isValid()) {
				foreach($socialNetworks as $socialNetwork) {
					list($socialNetwork, $locale) = explode("_", $socialNetwork, 2);

					switch($socialNetwork) {
						case "twitter":
							$res = $twitterAPI->retweet($data["postId"], $locale);

							if(property_exists($res, "title")) {
								$errorMessage = $res->title;
								$this->addFlash('error', $translator->trans('admin.twitter.FailedToSendTweet', [], 'validators'). " (".$errorMessage."; ".$res->detail.")");
							}
							elseif(property_exists($res, "data") and property_exists($res->data, "retweeted") and $res->data->retweeted)
								$this->addFlash('success', $translator->trans('admin.twitter.TweetSent', [], 'validators'));
						break;
					}
				}

				$rePostForm = $this->createForm(\App\Form\Type\RePostAdminType::class);
			}
		}

		return $this->render("admin/Admin/socialNetwork.html.twig", ["form" => $form->createView(), "rePostForm" => $rePostForm->createView()]);
	}

	#[Route('/admin/account', name: 'Admin_Account')]
	public function account() {
		return $this->render("admin/Admin/account.html.twig");
	}
}