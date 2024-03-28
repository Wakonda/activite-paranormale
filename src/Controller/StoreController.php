<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Entity\Stores\Store;
use App\Form\Type\StoreSearchType;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\PHPImage;

class StoreController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page)
    {
		$nbMessageByPage = 12;
		$datas = [];

		if($request->query->has("category") and !empty($c = $request->query->get("category")))
			$datas["category"] = $c;

		$form = $this->createForm(StoreSearchType::class, $datas);
		$form->handleRequest($request);
		$datas = null;

		if(!empty($d = $request->query->all()))
			$datas = $d;

		if ($form->isSubmitted() && $form->isValid() && isset($datas[$form->getName()]))
			$datas = $datas[$form->getName()];

		$query = $em->getRepository(Store::class)->getStores($datas, $nbMessageByPage, $page, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('store/Store/index.html.twig', ['pagination' => $pagination, 'form' => $form->createView()]);
    }

	public function showAction(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Store::class)->find($id);

		return $this->render('store/Store/show.html.twig', ['entity' => $entity]);
	}
	
	public function sliderAction(Request $request, EntityManagerInterface $em)
	{
		$entities = $em->getRepository(Store::class)->getSlider($request->getLocale());

		return $this->render("store/Widget/slider.html.twig", array(
			"entities" => $entities
		));
	}

    public function randomAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
    {
		$category = explode("|", $request->query->get("category", Store::BOOK_CATEGORY));
		$locale = $request->query->get("locale", $request->getLocale());

		$entity = $em->getRepository(Store::class)->getRandom($locale, $category);
		$entity = $em->getRepository(Store::class)->find(90);

		if(empty($entity))
			return new Response();

		$media = $entity->getimageEmbeddedCode();

		if(empty($entity->getimageEmbeddedCode()))
			$media = "<img src='".$request->getUriForPath('/extended/photo/store/'.$entity->getPhoto())."'>";
		
		if(empty($entity->getimageEmbeddedCode()) and empty($entity->getPhoto())) {
			$media = "<img src='".$this->generateUrl("Store_GenerateEmbeddedCode", ['id' => $entity->getId()], UrlGeneratorInterface::ABSOLUTE_URL)."'>";
		}

		$text = "<div style='text-align: center'>".$media."</div>";
		$text .= "<br>";
		if(Store::ALIEXPRESS_PLATFORM == $entity->getPlatform())
			$text .= '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #E52F20; padding: 0.375rem 0.75rem;background-color: #E52F20;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAliexpress', [], 'validators', $locale).'</a></div>';
		elseif(Store::AMAZON_PLATFORM == $entity->getPlatform())
			$text .= '<style>img { width: 170px }</style><div style="text-align: center"><a href="'.$entity->getExternalAmazonStoreLink().'" style="border: 1px solid #ff9900; padding: 0.375rem 0.75rem;background-color: #ff9900;border-radius: 0.25rem;color: black !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnAmazon', [], 'validators', $locale).'</a></div>';
		elseif(Store::SPREADSHOP_PLATFORM == $entity->getPlatform())
			$text .= '<div style="text-align: center"><a href="'.$entity->getUrl().'" style="border: 1px solid #a73c9e; padding: 0.375rem 0.75rem;background-color: #a73c9e;border-radius: 0.25rem;color: white !important;text-decoration: none;">'.$translator->trans('store.index.BuyOnSpreadshop', [], 'validators', $locale).'</a></div>';
	
        return new Response($text);
    }

	public function generateEmbeddedCode(Request $request, EntityManagerInterface $em, PHPImage $image, $id) {
		$entity = $em->getRepository(Store::class)->find($id);

		$textColor = [255, 255, 255];
		$strokeColor = [255, 255, 255];

		$font = realpath(__DIR__."/../../public").DIRECTORY_SEPARATOR.'extended'.DIRECTORY_SEPARATOR.'font'.DIRECTORY_SEPARATOR.'Edmundsbury_Serif.ttf';

		$bg = $request->getUriForPath('/extended/photo/store/bookcover.png');
		$image->setDimensionsFromImage($bg);
		$image->draw($bg);
		$image->setAlignHorizontal('center');
		$image->setAlignVertical('center');
		$image->setFont($font);
		$image->setTextColor($textColor);
		$image->setStrokeWidth(1);
		$image->setStrokeColor($strokeColor);
		$gutterY = 60;
		$gutterX = 100;
		$fontSizeAuthor = 30;

		$image->textBox($entity->getTitle(), [
			'width' => $image->getWidth() - $gutterX * 2,
			'height' => $image->getHeight() - $gutterY * 2,
			'fontSize' => 45,
			'x' => 70,
			'y' => $gutterY
		]);

		return new \Symfony\Component\HttpFoundation\StreamedResponse(fn () => imagepng($image->getResource()), 200, ['Content-Type' => 'image/png']);
	}
}