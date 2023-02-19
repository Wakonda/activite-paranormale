<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Entity\Language;
use App\Service\APImgSize;
use App\Service\APParseHTML;
use App\Service\APDate;
use App\Service\TwitterAPI;
use App\Service\PinterestAPI;
use App\Service\TumblrAPI;
use App\Service\GoogleBlogger;
use App\Service\Shopify;
use App\Service\TheDailyTruth;
use App\Service\FunctionsLibrary;
use App\Service\Facebook;
use App\Service\Mastodon;

class AdminController extends AbstractController
{
    public function indexAction(\Swift_Mailer $mailer)
    {
        return $this->render('admin/Admin/index.html.twig');
    }

	public function selectLanguageAction(Request $request, SessionInterface $session, $language)
    {
		$request->setLocale($language);
		$session->set('_locale', $language);
		
		return $this->redirect($this->generateUrl('Admin_Index'));
    }
	
	public function phpinfoAction()
	{
		phpinfo();
		return new Response();
	}
	
	public function internationalizationSelectGenericAction($entity, String $route, String $showRoute, String $editRoute)
	{
		$em = $this->getDoctrine()->getManager();
		$locales = [];

		if(method_exists($entity, "getInternationalName")) {
			$entities = $em->getRepository(get_class($entity))->findBy(["internationalName" => $entity->getInternationalName()]);

			foreach($entities as $e)
				$locales[] = $e->getLanguage()->getAbbreviation();
		}
		
		$form = $this->createForm(\App\Form\Type\InternationalizationAdminType::class, null, ["locales" => $locales]);

		return $this->render("admin/Admin/internationalization.html.twig", [
			"entity" => $entity,
			"form" => $form->createView(),
			"route" => $this->generateUrl($route, ["id" => $entity->getId()]),
			"showRoute" => $showRoute,
			"editRoute" => $editRoute
		]);
	}
	
	public function loadWikipediaSectionsPageAction(Request $request, TranslatorInterface $translator, \App\Service\Wikipedia $data)
	{
		$url = $request->query->get("url");
		
		$res = [];
		
		$res[] = ["id" => 0, "text" => $translator->trans('admin.wikipedia.Header', [], 'validators', $request->getLocale())];
		
		if(str_contains(parse_url($url, PHP_URL_HOST), "wikimonde")) {
			$data = new \App\Service\Wikimonde();
			$data->setUrl($url);
		} else {
			$data->setUrl($url);
		}

		foreach($data->getSections() as $text => $id)
			$res[] = ["id" => $id, "text" => $text];

		return new JsonResponse($res);
	}
	
	public function importWikipediaAction(Request $request, \App\Service\Wikipedia $data)
	{
		// dd($request->request->get("sections", []), $_POST, $_GET);
		$url = $request->request->get("url");

		if(str_contains(parse_url($url, PHP_URL_HOST), "wikimonde")) {
			$data = new \App\Service\Wikimonde();
			$data->setUrl($url);
		} else {
			$data->setUrl($url);
		}
		$sections = $request->request->get("sections", []);

		$source = ["author" => "", "title" => "", "url" => $request->request->get("url"), "type" => "url"];

		return new JsonResponse(["content" => $data->getContentBySections($sections), "source" => $source]);
	}

	// Blogger
	public function bloggerTagsAction(Request $request, GoogleBlogger $blogger, $id, $path, $routeToRedirect)
	{
		$twig = $this->get("twig");
		
		$type = $request->query->get("type");
		
		$tags = $twig->getExtensions()["App\Twig\APExtension"]->getBloggerTags($type);

		$em = $this->getDoctrine()->getManager();
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
		
		$urlAddUpdate = $this->generateUrl('Admin_Blogger', ['id' => $entity->getId(), 'path' => urlencode($entity->getEntityName()), 'routeToRedirect' => $routeToRedirect, 'type' => $type, 'method' => $method]);
		
		$urlDelete = null;
		
		if(!empty($obj))
			$urlDelete = $this->generateUrl('Admin_Blogger', ['id' => $entity->getId(), 'path' => urlencode($entity->getEntityName()), 'routeToRedirect' => $routeToRedirect, 'type' => $type, 'method' => "DELETE"]);
		
		return new JsonResponse(["obj" => $obj, "method" => $method, "tags" => $tags, "urlAddUpdate" => $urlAddUpdate, "urlDelete" => $urlDelete]);
	}
	
	public function bloggerAction(Request $request, GoogleBlogger $blogger, UrlGeneratorInterface $router, $id, $path, $routeToRedirect, $type, $method)
	{
		$session = $request->getSession();
		$session->set("id_blogger", $id);
		$session->set("method_blogger", $method);
		
		$path = urldecode($path);
		$session->set("path_blogger", $path);
		$session->set("routeToRedirect_blogger", $routeToRedirect);
		
		$tags = $request->request->get('blogger_tags');
		$session->set("tags_blogger", json_encode((empty($tags)) ? [] : $tags));

		$session->set("type_blogger", $type);

		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository($path)->find($id);
		$redirectURL = $router->generate("Admin_BloggerPost", [], UrlGeneratorInterface::ABSOLUTE_URL);

		$blogName = $blogger->getCorrectBlog($type);
		$response = $blogger->getPostInfos($blogName);

		$code = $blogger->getCode($redirectURL);

		return new Response();
	}

	public function bloggerPostAction(Request $request, APImgSize $imgSize, APParseHTML $parser, GoogleBlogger $blogger, TranslatorInterface $translator, UrlGeneratorInterface $router)
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

		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository($path)->find($id);
		
		$title = $entity->getTitle();

		if(in_array($method, ["POST", "PUT"])) {
			$text = "";
			$imgProperty = "";

			switch($entity->getRealClass())
			{
				case "Photo":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $entity->getText();
					$text .= "<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators', $request->getLocale())."</b><br>".$imgCaption."</div>";
					$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "News":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $parser->replacePathImgByFullURL($entity->getAbstractText().$entity->getText()."<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators', $request->getLocale())."</b><br>".$imgCaption."</div>"."<b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation())."</span>", $request->getSchemeAndHttpHost().$request->getBasePath());
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "Video":
					$video = $parser->getVideoResponsive($entity->getEmbeddedCode());
					if(!empty($entity->getMediaVideo()))
						$video = $parser->getVideoResponsive('<video width="550" height="309" controls><source src="'.$request->getSchemeAndHttpHost().'/'.$entity->getAssetVideoPath().'/'.$entity->getMediaVideo().'" type="video/mp4"></video>');
					
					$imgProperty = $entity->getPhoto();
					$img = $entity->getAssetImagePath().$imgProperty;
					$text = $entity->getText()."<br>".$video;
					$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "Grimoire":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;
					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $entity->getText();

					$text = $parser->replacePathImgByFullURL($text."<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators', $request->getLocale())."</b><br>".$imgCaption."</div>"."<b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation())."</span>", $request->getSchemeAndHttpHost().$request->getBasePath());
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "President":
					$imgProperty = $entity->getPhotoIllustrationFilename();
					$img = $entity->getAssetImagePath().$imgProperty;

					$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
					$text = $entity->getText();
					break;
				case "Cartography":
					$imgProperty = $entity->getPhoto();
					$img = $entity->getAssetImagePath().$imgProperty;
					$text = $entity->getText();
					$text .= "<b>".$translator->trans('cartography.admin.LinkGMaps', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b><br><span><a href='".$entity->getLinkGMaps()."'>".(new FunctionsLibrary())->cleanUrl($entity->getLinkGMaps())."</a></span>";
					$text .= "<br><br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
					$text = $parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath());
					break;
				case "Book":
					$imgProperty = $entity->getTheme()->getPhoto();
					$img = $entity->getTheme()->getAssetImagePath().$imgProperty;
					$twig = $this->get("twig");
					$text = $entity->getText()."<br>";
					$text .= $twig->getExtensions()["App\Twig\APStoreExtension"]->getImageEmbeddedCodeByEntity($entity->getBookEditions()->first(), "book", "BookStore")."<br>";
					$text .= "<b>".$translator->trans('biography.index.Author', [], 'validators', $entity->getLanguage()->getAbbreviation())." : </b>".implode(", ", array_map(function($e) { return $e->getTitle(); }, $entity->getAuthors()->getValues()))."<br>";
					$text .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
					break;
				case "Store":
					$imgProperty = strtolower($entity->getCategory()).".jpg";
					$img = $entity->getAssetImagePath()."category/".$imgProperty;
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
					} else {
						$imgProperty = $entity->getAssetImagePath()."category/".strtolower($entity->getCategory()).".jpg";
						$img = $imgProperty;
					}
					$twig = $this->get("twig");
					$text = $entity->getText()."<br>";
					$language = $entity->getBook()->getBook()->getLanguage()->getAbbreviation();
					$title = $translator->trans('book.index.Book', [], 'validators', $language)." - ".$entity->getTitle();
					
					$text .= (!empty($d = $entity->getBook()->getBackCover()) ? "<b>".$translator->trans('bookEdition.admin.BackCover', [], 'validators', $language)."</b><br>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getBook()->getText()) ? "<b>".$translator->trans('book.admin.Text', [], 'validators', $language)."</b><br>".$d."<br>" : "");
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= "<b>".$translator->trans('biography.index.Author', [], 'validators', $entity->getBook()->getBook()->getLanguage()->getAbbreviation())." : </b>".implode(", ", array_map(function($e) { return $e->getTitle(); }, $entity->getBook()->getBook()->getAuthors()->getValues()))."<br>";
					$text .= (!empty($d = $entity->getBook()->getIsbn10()) ? "<b>ISBN 10 : </b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getIsbn13()) ? "<b>ISBN 13 : </b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getNumberPage()) ? "<b>".$translator->trans('bookEdition.admin.NumberPage', [], 'validators', $language)." : </b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getPublisher()->getTitle()) ? "<b>".$translator->trans('bookEdition.admin.Publisher', [], 'validators', $language)." : </b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getBook()->getPublicationDate()) ? "<b>".$translator->trans('bookEdition.admin.PublicationDate', [], 'validators', $language)." : </b>".$twig->getExtensions()["App\Twig\APExtension"]->doPartialDateFilter($d, $entity->getBook()->getBook()->getLanguage()->getAbbreviation())."<br>" : "");
					$text .= "<br>".$translator->trans('store.admin.MoreBooksOn', [], 'validators', $language)." <a href='https://templededelphes.netlify.app/'>Temple de Delphe</a>";
					$text .= !empty($entity->getBook()->getBook()->getSource()) ? "<b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getBook()->getBook()->getLanguage()->getAbbreviation())."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getBook()->getBook()->getSource(), $entity->getBook()->getBook()->getLanguage()->getAbbreviation())."</span>" : "";
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
					$twig = $this->get("twig");
					$text = $entity->getText()."<br>";
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= (!empty($d = $entity->getAlbum()->getArtist()) ? "<b>".$translator->trans('album.admin.Artist', [], 'validators', $language)." : </b>".$d->getTitle()."<br>" : "");
					$text .= (!empty($d = $entity->getAlbum()->getReleaseYear()) ? "<b>".$translator->trans('album.admin.ReleaseYear', [], 'validators', $language)." : </b>".$twig->getExtensions()["App\Twig\APExtension"]->doPartialDateFilter($d, $entity->getAlbum()->getLanguage()->getAbbreviation())."<br>" : "");
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
					$twig = $this->get("twig");
					$text = $entity->getText()."<br>";
					$text .= (!empty($d = $entity->getMovie()->getText()) ? "<b>>".$translator->trans('movie.admin.Text', [], 'validators', $language)."</b>".$d."<br>" : "");
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= (!empty($d = $entity->getMovie()->getDuration()) ? "<b>".$translator->trans('movie.admin.Duration', [], 'validators', $language)." :</b>".$d." minutes<br>" : "");
					$text .= (!empty($d = $entity->getMovie()->getGenre()) ? "<b>".$translator->trans('movie.admin.Genre', [], 'validators', $language)." :</b>".$d."<br>" : "");
					$text .= (!empty($d = $entity->getMovie()->getReleaseYear()) ? "<b>".$translator->trans('movie.admin.ReleaseYear', [], 'validators', $language)." :</b>".$twig->getExtensions()["App\Twig\APExtension"]->doPartialDateFilter($d, $entity->getMovie()->getLanguage()->getAbbreviation())."<br>" : "");
					$text .= (!empty($d = $entity->getMovie()->getTrailer()) ? "<b>".$translator->trans('movie.admin.Trailer', [], 'validators', $language)."</b><br>".$d."<br>" : "");
					
					$actorArray = [];
					
					$biographyDatas = $twig->getExtensions()["App\Twig\APMovieExtension"]->getMovieBiographiesByOccupation($entity->getMovie());
					
					foreach($biographyDatas as $occupation => $biographies) {
						if ($occupation == \App\Entity\Movies\MediaInterface::ACTOR_OCCUPATION) {
							foreach($biographies as $biography) {
								$actorArray[] = $biography["title"].(!empty($r = $biography["role"]) ? " (".$r.")" : "");
							}
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
					$twig = $this->get("twig");
					$text = $entity->getText()."<br>";
					$text .= (!empty($d = $entity->getTelevisionSerie()->getText()) ? "<b>".$translator->trans('televisionSerie.admin.Text', [], 'validators', $language)."</b><br>".$d."<br>" : "");
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= (!empty($d = $entity->getTelevisionSerie()->getGenre()) ? "<b>".$translator->trans('televisionSerie.admin.Genre', [], 'validators', $language)." :</b>".$d."<br>" : "");

					$actorArray = [];
					
					$biographyDatas = $twig->getExtensions()["App\Twig\APMovieExtension"]->getTelevisionSerieBiographiesByOccupation($entity->getTelevisionSerie());
					
					foreach($biographyDatas as $occupation => $biographies) {
						if ($occupation == \App\Entity\Movies\MediaInterface::ACTOR_OCCUPATION) {
							foreach($biographies as $biography) {
								$actorArray[] = $biography["title"].(!empty($r = $biography["role"]) ? " (".$r.")" : "");
							}
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
					$text .= (!empty($d = $entity->getWitchcraftTool()->getText()) ? $d."<br>" : "");
					$text .= $entity->getImageEmbeddedCode()."<br><br>";
					$text .= !empty($entity->getWitchcraftTool()->getSource()) ? "<br><b>".$translator->trans('news.index.Sources', [], 'validators', $language)."</b><span>".(new FunctionsLibrary())->sourceString($entity->getWitchcraftTool()->getSource(), $entity->getWitchcraftTool()->getLanguage()->getAbbreviation())."</span>" : "";
					$text = "<div>".$parser->replacePathLinksByFullURL($text, $request->getSchemeAndHttpHost().$request->getBasePath())."</div>";
					break;
			}

			if(in_array(\App\Entity\Stores\Store::class, [get_class($entity), get_parent_class($entity)])) {
				$language = $entity->getLanguage()->getAbbreviation();
				$text .= "<hr>";
				if(\App\Entity\Stores\Store::ALIEXPRESS_PLATFORM == $entity->getPlatform())
					$text .= '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #E52F20; padding: 0.375rem 0.75rem;background-color: #E52F20;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAliexpress', [], 'validators', $language).'</a></div>';
				else
					$text .= '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #ff9900; padding: 0.375rem 0.75rem;background-color: #ff9900;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAmazon', [], 'validators', $language).'</a></div>';
			}

			$img = $imgSize->adaptImageSize(550, $img);
			
			$baseurl = $request->getSchemeAndHttpHost().$request->getBasePath();
			
			$text = "<div style='font-size: 14pt; text-align: justify; font-family: Times New Roman;'>".$text."</div>";
		}

		switch($method) {
			case "POST";
				$response = $blogger->addPost($blogName, $accessToken, $title, (!empty($imgProperty) ? "<p><img src='".$baseurl."/".$img[2]."' style='width: ".$img[0]."; height:".$img[1]."' alt='' /></p>" : "").$text, $tags);
				break;
			case "PUT";
				$response = $blogger->updatePost($entity->getSocialNetworkIdentifiers()["Blogger"][$blogId]["id"], $blogName, $accessToken, $title, (!empty($imgProperty) ? "<p><img src='".$baseurl."/".$img[2]."' style='width: ".$img[0]."; height:".$img[1]."' alt='' /></p>" : "").$text, $tags);
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
			$session->getFlashBag()->add('error', $translator->trans('admin.blogger.Error', array("%code%" => $response["http_code"]), 'validators'));
		
		return $this->redirect($this->generateUrl($routeToRedirect, array("id" => $entity->getId())));
	}

	// Shopify
	public function shopifyAction(Request $request, Shopify $shopify, UrlGeneratorInterface $router, $id, $path, $routeToRedirect, $type)
	{
		$session = $request->getSession();
		$session->set("id_shopify", $id);
		
		$path = urldecode($path);
		$session->set("path_shopify", $path);
		$session->set("routeToRedirect_shopify", $routeToRedirect);
		
		$tags = $request->request->get('shopify_tags');
		$session->set("tags_shopify", json_encode((empty($tags)) ? [] : $tags));

		$session->set("type_shopify", $type);

		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository($path)->find($id);
		$redirectURL = $router->generate("Admin_ShopifyPost", [], UrlGeneratorInterface::ABSOLUTE_URL);

		$code = $shopify->getCode($redirectURL);

		return new Response();
	}

	public function shopifyPostAction(Request $request, APImgSize $imgSize, APParseHTML $parser, Shopify $shopify, TranslatorInterface $translator, UrlGeneratorInterface $router)
	{
		$code = $request->query->get("code");
		
		$session = $request->getSession();

		$id = $session->get("id_shopify");
		$path = $session->get("path_shopify");
		$tags = $session->get("tags_shopify");
		$type = $session->get("type_shopify");
		$routeToRedirect = $session->get("routeToRedirect_shopify");

		$blogName = $shopify->getCorrectBlog($type);

		$em = $this->getDoctrine()->getManager();
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
			$session->getFlashBag()->add('error', $translator->trans('admin.shopify.Error', array("%code%" => $response["http_code"]), 'validators'));
		
		return $this->redirect($this->generateUrl($routeToRedirect, array("id" => $entity->getId())));
	}
	
	// Pinterest
	public function pinterestAction(Request $request, PinterestAPI $pinterest, TranslatorInterface $translator, SessionInterface $session, UrlGeneratorInterface $router, $id, $path, $routeToRedirect)
	{
		$em = $this->getDoctrine()->getManager();
		$requestParams = $request->request;

		$entity = $em->getRepository(urldecode($path))->find($id);
		
		$currentURL = $router->generate($entity->getShowRoute(), array("id" => $entity->getId(), "title_slug" => $entity->getTitle()), UrlGeneratorInterface::ABSOLUTE_URL);
		$image = $this->getImageName($request, $entity, false);
		
		$image = $request->getUriForPath($entity->getAssetImagePath().$image);

		$res = $pinterest->send($entity, $image, $currentURL);
		
		if($res == "success")
			$session->getFlashBag()->add('success', $translator->trans('admin.pinterest.Success', [], 'validators'));
		else
			$session->getFlashBag()->add('error', $res);

		return $this->redirect($this->generateUrl($routeToRedirect, array("id" => $id)));
	}
	
	// Twitter
	public function twitterAction(Request $request, TwitterAPI $twitterAPI, TranslatorInterface $translator, SessionInterface $session, UrlGeneratorInterface $router, $id, $path, $routeToRedirect)
	{
		$em = $this->getDoctrine()->getManager();
		$requestParams = $request->request;
		
		$path = urldecode($path);
		
		$entity = $em->getRepository($path)->find($id);
		$image = false;
		$url = $requestParams->get("twitter_url", null);

		if($requestParams->get("add_image") == 'on')
			$image = $this->getImageName($request, $entity, false);

		$currentURL = !empty($url) ? $url : $router->generate($entity->getShowRoute(), array("id" => $entity->getId(), "title_slug" => $entity->getTitle()), UrlGeneratorInterface::ABSOLUTE_URL);

		$twitterAPI->setLanguage($entity->getLanguage()->getAbbreviation());
		
		$res = $twitterAPI->sendTweet($requestParams->get("twitter_area")." ".$currentURL, $entity->getLanguage()->getAbbreviation(), $image);

		if(property_exists($res, "errors")) {
			$errorsArray = array_map(function($e) { return $e->code.": ".$e->message; }, $res->errors);
			$session->getFlashBag()->add('error', $translator->trans('admin.twitter.FailedToSendTweet', [], 'validators'). " (".implode(", ", $errorsArray).")");
		}
		else
			$session->getFlashBag()->add('success', $translator->trans('admin.twitter.TweetSent', [], 'validators'));

		return $this->redirect($this->generateUrl($routeToRedirect, array("id" => $id)));
	}
	
	// The Daily Truth
	public function thedailytruthAction(Request $request, int $id, string $path, string $routeToRedirect)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(urldecode($path))->find($id);
		
		$path = realpath($this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$entity->getIllustration()->getRealNameFile());

		$data = [
			"title" => $entity->getTitle(),
			"text" => '<div id="abstract">'.$entity->getAbstractText().'</div>'.$entity->getText(),
			"slug" => $entity->getSlug(),
			"source" => $entity->getSource(),
			"tags" => json_encode($request->request->get("thedailytruth_tags")),
			"media" => json_encode(["content" => base64_encode(file_get_contents($path)), "caption" => $entity->getIllustration()->getCaption(), "name" => $entity->getIllustration()->getRealNameFile()]),
		];
		
		$api = new TheDailyTruth();
		$api->addPost($data, $api->getOauth2Token());
		
		return $this->redirect($this->generateUrl($routeToRedirect, array("id" => $id)));
	}
	
	// Wakonda.GURU
	public function wakondaGuruAction(Request $request, SessionInterface $session, TranslatorInterface $translator, int $id, string $path, string $routeToRedirect)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(urldecode($path))->find($id);

		$illustration = null;
		
		if(!empty($img = $entity->getIllustration())) {
			$path = realpath($this->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR."public".DIRECTORY_SEPARATOR.$entity->getAssetImagePath().$img->getRealNameFile());

			$illustration = [
				"file" => base64_encode(file_get_contents($path)),
				"infos" => json_encode(["license" => $img->getLicense(), "author" => $img->getAuthor(), "urlSource" => $img->getUrlSource()]),
				"filename" => $img->getRealNameFile()
			];
		}

		if($entity->isDevelopment()) {
			$data = [
				"title" => $entity->getTitle(),
				"text" => $entity->getText(),
				"illustration" => $illustration,
				"tags" => !empty($entity->getTags()) ? implode(",", array_map(function($e) { return $e->value; }, json_decode($entity->getTags()))) : null,
				"sources" => $entity->getLinks(),
				"identifier" => $entity->getInternationalName(),
				"category" => $entity->getCategory()
			];
		} elseif($entity->isUsefulLink()) {
			$data = [
				"title" => $entity->getTitle(),
				"text" => $entity->getText(),
				"illustration" => $illustration,
				"url" => json_decode($entity->getLinks())[0]->url,
				"identifier" => $entity->getInternationalName(),
				"category" => $entity->getCategory()
			];
		} elseif($entity->isTool()) {
			$data = [
				"title" => $entity->getTitle(),
				"text" => $entity->getText(),
				"illustration" => $illustration,
				"url" => json_decode($entity->getLinks())[0]->url,
				"identifier" => $entity->getInternationalName(),
				"category" => $entity->getCategory()
			];
		} elseif($entity->isTechnical()) {
			$data = [
				"title" => $entity->getTitle(),
				"text" => $entity->getText(),
				"website" => $entity->getWebsite()->getLink(),
				"identifier" => $entity->getInternationalName(),
				"category" => $entity->getCategory()
			];
		}

		$api = new \App\Service\WakondaGuru();
		$api->addPost($data, $api->getOauth2Token());
		
		$session->getFlashBag()->add('success', $translator->trans('admin.wakondaguru.Success', [], 'validators'));
		
		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $id]));
	}
	
	// Muse
	public function museAction(Request $request, TranslatorInterface $translator, SessionInterface $session, int $id, string $path, string $routeToRedirect)
	{
		$em = $this->getDoctrine()->getManager();
		
		$entity = $em->getRepository(urldecode($path))->find($id);

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

		$data = [
			"text" => $entity->getTextQuotation(),
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
				"country" => ["internationalName" => !empty($n = $biography->getNationality()) ? $n->getInternationalName() : null],
				"language" => ["abbreviation" => $biography->getLanguage()->getAbbreviation()],
				"wikidata" => $biography->getWikidata(),
				"fileManagement" => [
					"imgBase64" => $entity->getAuthorQuotation()->getImgBase64(),
					"photo" => $entity->getAuthorQuotation()->getIllustration()->getRealNameFile(),
					"description" => "<a href='".$biography->getIllustration()->getUrlSource()."'>Source</a>, ".$biography->getIllustration()->getLicense().", ".$biography->getIllustration()->getAuthor()
				]
			],
			// "source" => ["identifier" => $sourceIdentifier],
			// "tags" => $entity->getTags()
		];

		$api = new \App\Service\Muse();
		$result = $api->addPost($data, $api->getOauth2Token());

		
		if($result->{"@type"} == "hydra:Error")
			$session->getFlashBag()->add('error', $result->{"hydra:title"});
		else {
			$entity->setIdentifier($result->identifier);
			$em->persist($entity);
			$em->flush();
			$session->getFlashBag()->add('success', $translator->trans('admin.muse.Success', [], 'validators'));
		}

		
		return $this->redirect($this->generateUrl($routeToRedirect, ["id" => $id]));
	}
	
	// Tumblr
	public function tumblrAction(Request $request, TumblrAPI $tumblr, SessionInterface $session, $id, $path, $routeToRedirect)
	{
		$session->set("id_tumblr", $id);
		$session->set("path_tumblr", urldecode($path));
		$session->set("routeToRedirect_tumblr", $routeToRedirect);
		
		$tags = $request->request->get('tumblr_tags');
		$session->set("tumblr_tags", json_encode((empty($tags)) ? [] : $tags));

		$tumblr->connect();

		exit();
	}
	
	public function tumblrPostAction(Request $request, APImgSize $imgSize, APParseHTML $parser, TumblrAPI $tumblr, TranslatorInterface $translator)
	{
		$session = $request->getSession();

		$id = $session->get("id_tumblr");
		$path = $session->get("path_tumblr");
		$tags = implode(",", json_decode($session->get("tumblr_tags")));
		$routeToRedirect = $session->get("routeToRedirect_tumblr");

		$em = $this->getDoctrine()->getManager();
		
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
			case "Music":
				$body = $entity->getEmbeddedCode();
				$body .= "<br>".$entity->getText();
				$body .= "<b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation());
				break;
			case "News":
				$imgProperty = $entity->getPhotoIllustrationFilename();
				$img = $entity->getAssetImagePath().$imgProperty;
				$img = $imgSize->adaptImageSize(550, $img);

				$imgCaption = !empty($c = $entity->getPhotoIllustrationCaption()) ? implode(", ", $c["source"]) : "";
				$body = $parser->replacePathImgByFullURL($entity->getAbstractText().$entity->getText()."<div><b>".$translator->trans('file.admin.CaptionPhoto', [], 'validators', $request->getLocale())."</b><br>".$imgCaption."</div>"."<br><b>".$translator->trans('news.index.Sources', [], 'validators', $entity->getLanguage()->getAbbreviation())."</b><br><span>".(new FunctionsLibrary())->sourceString($entity->getSource(), $entity->getLanguage()->getAbbreviation())."</span>", $request->getSchemeAndHttpHost().$request->getBasePath());
				$body = "<p><img src='".$baseurl."/".$img[2]."' style='width: ".$img[0]."; height:".$img[1]."' alt='' /></p>".$parser->replacePathLinksByFullURL($body, $request->getSchemeAndHttpHost().$request->getBasePath());

				break;
			case "Cartography":
			case "Photo":
				$body = "<p><img src='".$baseurl."/".$img[2]."' style='width: ".$img[0]."; height:".$img[1]."' alt='' /></p><br>".$entity->getText().$imgCaption;
				$body .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
				$body = $parser->replacePathLinksByFullURL($body, $request->getSchemeAndHttpHost().$request->getBasePath());
				break;
			case "Video":
				$video = $parser->getVideoResponsive($entity->getEmbeddedCode());
				if(!empty($entity->getMediaVideo()))
					$video = $parser->getVideoResponsive('<video width="550" height="309" controls><source src="'.$request->getSchemeAndHttpHost().'/'.$entity->getAssetVideoPath().'/'.$entity->getMediaVideo().'" type="video/mp4"></video>');
				$body = "<p><img src='".$baseurl."/".$img[2]."' width='".$img[0]."' height='".$img[1]."' alt='' /></p><br>".$entity->getText()."<br>".$video;
				$body .= "<br>→ <a href='".$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), "title_slug" => $entity->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$translator->trans('admin.source.MoreInformationOn', [], 'validators', $entity->getLanguage()->getAbbreviation())."</a>";
				$body = $parser->replacePathLinksByFullURL($body, $request->getSchemeAndHttpHost().$request->getBasePath());
				break;
		}
		
		// Fix Tumblr bugs 
		$body = str_replace(array("\r\n", "\n", "\t", "\r"), ' ', $body);
		
		$tumblr->addPost($title, $body, $tags);
		
		$session->getFlashBag()->add('success', $translator->trans('admin.tumblr.Success', [], 'validators'));
		
		return $this->redirect($this->generateUrl($routeToRedirect, array("id" => $entity->getId())));
	}
	
	// Facebook
	public function facebookAction(Request $request, SessionInterface $session, UrlGeneratorInterface $router, Facebook $facebook, TranslatorInterface $translator, $id, $path, $routeToRedirect)
	{
		$em = $this->getDoctrine()->getManager();
		$requestParams = $request->request;
		
		$path = urldecode($path);
		
		$entity = $em->getRepository($path)->find($id);
		$image = false;
		$url = $requestParams->get("facebook_url", null);

		$currentURL = !empty($url) ? $url : $router->generate($entity->getShowRoute(), array("id" => $entity->getId(), "title_slug" => $entity->getTitle()), UrlGeneratorInterface::ABSOLUTE_URL);

		$res = json_decode($facebook->postMessage($currentURL, $request->request->get("facebook_area"), $entity->getLanguage()->getAbbreviation()));

		$message = (property_exists($res, "error")) ? ['state' => 'error', 'message' => $translator->trans('admin.facebook.Failed', [], 'validators'). "(".$res->error->message.")"] : ['state' => 'success', 'message' => $translator->trans('admin.facebook.Success', [], 'validators')];
		
		$session->getFlashBag()->add($message["state"], $message["message"], [], 'validators');

		return $this->redirect($this->generateUrl($routeToRedirect, array("id" => $entity->getId())));
	}
	
	// Mastodon
	public function mastodonAction(Request $request, SessionInterface $session, UrlGeneratorInterface $router, Mastodon $mastodon, TranslatorInterface $translator, $id, $path, $routeToRedirect)
	{
		$em = $this->getDoctrine()->getManager();
		$requestParams = $request->request;
		
		$path = urldecode($path);
		
		$entity = $em->getRepository($path)->find($id);
		$image = false;
		$url = $requestParams->get("mastodon_url", null);

		$currentURL = !empty($url) ? $url : $router->generate($entity->getShowRoute(), array("id" => $entity->getId(), "title_slug" => $entity->getTitle()), UrlGeneratorInterface::ABSOLUTE_URL);

		$res = $mastodon->postMessage($currentURL, $request->request->get("mastodon_area"), $entity->getLanguage()->getAbbreviation());

		$message = (property_exists($res, "error")) ? ['state' => 'error', 'message' => $translator->trans('admin.mastodon.Failed', [], 'validators'). "(".$res->error->message.")"] : ['state' => 'success', 'message' => $translator->trans('admin.mastodon.Success', [], 'validators')];
		
		$session->getFlashBag()->add($message["state"], $message["message"], [], 'validators');

		return $this->redirect($this->generateUrl($routeToRedirect, array("id" => $entity->getId())));
	}
	
	public function wikidataGenericAction(Request $request, \App\Service\Wikidata $wikidata)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->find($request->query->get("locale"));
		$code = $request->query->get("code");
		
		$res = $wikidata->getGenericDatas($code, $language->getAbbreviation());

		return new JsonResponse($res);
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
			default:
				$imageName = null;
		}

		if(!empty($imageName)) {
			$path = $entity->getAssetImagePath().$imageName;
			return ($url) ? $request->getUriForPath('/'.$path) : $path;	
		}

		return null;
	}
}