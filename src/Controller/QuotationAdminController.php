<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Quotation;
use App\Entity\QuotationImage;
use App\Entity\Language;
use App\Entity\Biography;
use App\Entity\Region;
use App\Form\Type\QuotationAdminType;
use App\Form\Type\QuotationImageGeneratorType;
use App\Service\ConstraintControllerValidator;
use App\Service\APParseHTML;
use App\Service\PHPImage;
use App\Service\APImgSize;

#[Route('/admin/quotation')]
class QuotationAdminController extends AdminGenericController
{
	protected $entityName = 'Quotation';
	protected $className = Quotation::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Quotation_Admin_Index"; 
	protected $showRoute = "Quotation_Admin_Show";
	protected $formName = "ap_quotation_quotationadmintype";

	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		// Check for Doublons
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('authorQuotation')->addError(new FormError($translator->trans('admin.error.Doublon', [], 'validators')));

		if($entityBindded->isProverbFamily())
			$entityBindded->setAuthorQuotation(null);

		if($entityBindded->isQuotationFamily())
			$entityBindded->setCountry(null);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/{page}', name: 'Quotation_Admin_Index', defaults: ['page' => 1], requirements: ['page' => '\d+'])]
    public function index()
    {
		$twig = 'quotation/QuotationAdmin/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/{id}/show', name: 'Quotation_Admin_Show')]
    public function show(EntityManagerInterface $em, $id)
    {
		$form = $this->createForm(QuotationImageGeneratorType::class);
		$twig = 'quotation/QuotationAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig, ["imageGeneratorForm" => $form->createView()]);
    }

	#[Route('/new/{family}', name: 'Quotation_Admin_New', defaults: ['family' => 'quotation'])]
    public function newAction(Request $request, EntityManagerInterface $em, $family)
    {
		$formType = QuotationAdminType::class;
		$entity = new Quotation();
		$entity->setFamily($family);

		$twig = 'quotation/QuotationAdmin/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }

	#[Route('/create', name: 'Quotation_Admin_Create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = QuotationAdminType::class;
		$entity = new Quotation();

		$twig = 'quotation/QuotationAdmin/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/edit', name: 'Quotation_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository($this->className)->find($id);
		$formType = QuotationAdminType::class;

		$twig = 'quotation/QuotationAdmin/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }

	#[Route('/{id}/update', name: 'Quotation_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = QuotationAdminType::class;
		
		$twig = 'quotation/QuotationAdmin/edit.html.twig';
		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $em, $this->formName)]);
    }

	#[Route('/{id}/delete', name: 'Quotation_Admin_Delete')]
    public function deleteAction(EntityManagerInterface $em, $id)
    {
		return $this->deleteGeneric($em, $id);
    }

	#[Route('/datatables', name: 'Quotation_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];
			$row[] = $entity->getId();
			
			if($entity->isQuotationFamily())
				$row[] = $entity->getAuthorQuotation()->getTitle();
			else
				$row[] = $entity->getCountry()->getTitle();

			$row[] = $entity->getTextQuotation();
			$row[] = $translator->trans("quotation.admin.".ucfirst($entity->getFamily()), [], 'validators');
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Quotation_Admin_Show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br />
			 <a href='".$this->generateUrl('Quotation_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br />
			";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/wysiwyg_uploadfile', name: 'Quotation_Admin_WYSIWYG_UploadFile')]
    public function WYSIWYGUploadFile(Request $request, APImgSize $imgSize)
    {
		return $this->WYSIWYGUploadFileGeneric($request, $imgSize, new Quotation());
    }

	#[Route('/create_same_author/{biographyId}', name: 'Quotation_Admin_CreateSameAuthor')]
	public function createSameAuthor(Request $request, EntityManagerInterface $em, $biographyId)
	{
		$formType = QuotationAdminType::class;
		$entity = new Quotation();

		$biography = $em->getRepository(Biography::class)->find($biographyId);

		$entity->setAuthorQuotation($biography);
		$entity->setLanguage($biography->getLanguage());

        $form = $this->createForm($formType, $entity, ["locale" => $entity->getLanguage()->getAbbreviation()]);

        return $this->render('quotation/QuotationAdmin/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
	}

	#[Route('/generate_image/{id}', name: 'Quotation_Admin_GenerateImage')]
	public function generateImage(Request $request, EntityManagerInterface $em, PHPImage $image, $id)
	{
		$entity = $em->getRepository($this->className)->find($id);
        $imageGeneratorForm = $this->createForm(QuotationImageGeneratorType::class);
        $imageGeneratorForm->handleRequest($request);

		if ($imageGeneratorForm->isSubmitted() && $imageGeneratorForm->isValid()) {
			$data = $imageGeneratorForm->getData();

			$font = realpath(__DIR__."/../../public").DIRECTORY_SEPARATOR.'extended'.DIRECTORY_SEPARATOR.'font'.DIRECTORY_SEPARATOR.'Edmundsbury_Serif.ttf';

			$textColor = [0, 0, 0];
			$strokeColor = [255, 255, 255];
			$rectangleColor = [255, 255, 255];
			
			if($data["invert_colors"]) {
				$textColor = [255, 255, 255];
				$strokeColor = [0, 0, 0];
				$rectangleColor = [0, 0, 0];
			}

			$bg = $data['image']->getPathName();
			$image->setDimensionsFromImage($bg);
			$image->draw($bg);
			$image->setAlignHorizontal('center');
			$image->setAlignVertical('center');
			$image->setFont($font);
			$image->setTextColor($textColor);
			$image->setStrokeWidth(1);
			$image->setStrokeColor($strokeColor);
			$gutter = 50;
			$fontSizeAuthor = 20;
			$image->rectangle($gutter, $gutter, $image->getWidth() - $gutter * 2, $image->getHeight() - $gutter * 2 + $fontSizeAuthor, $rectangleColor, 0.5);

			$image->textBox("“".html_entity_decode($entity->getTextQuotation())."”", [
				'width' => $image->getWidth() - $gutter * 2,
				'height' => $image->getHeight() - $gutter * 2,
				'fontSize' => $data["font_size"],
				'x' => $gutter,
				'y' => $gutter
			]);

			if($entity->isProverbFamily())
				$image->textBox($entity->getCountry()->getTitle(), ['width' => $image->getWidth() - $gutter * 2, 'fontSize' => $fontSizeAuthor, 'x' => $gutter, 'y' => ($image->getHeight() - $gutter * 2) + $fontSizeAuthor * 2]);
			else
				$image->textBox($entity->getAuthorQuotation()->getTitle(), ['width' => $image->getWidth() - $gutter * 2, 'fontSize' => $fontSizeAuthor, 'x' => $gutter, 'y' => ($image->getHeight() - $gutter * 2) + $fontSizeAuthor * 2]);

			$fileName = uniqid()."_".$data['image']->getClientOriginalName();

			imagepng($image->getResource(), $entity->getAssetImagePath().$fileName);
			imagedestroy($image->getResource());

			$qi = new QuotationImage();
			$qi->setQuotation($entity);
			$qi->setImage($fileName);

			$entity->addImage($qi);

			$em->persist($qi);
			$em->persist($entity);
			$em->flush();

			return $this->redirect($this->generateUrl("Quotation_Admin_Show", ["id" => $id]));
		}

		$twig = 'quotation/QuotationAdmin/show.html.twig';
		return $this->showGeneric($em, $id, $twig, ["imageGeneratorForm" => $imageGeneratorForm->createView()]);
	}

	#[Route('/generate_image_ajax/{id}', name: 'Quotation_Admin_GenerateImageAjax')]
	public function generateImageAjaxAction(Request $request, EntityManagerInterface $em, PHPImage $image, $id)
	{
		$entity = $em->getRepository($this->className)->find($id);

		$url = $request->request->get("url");
		$size = $request->request->get("size");

		$font = realpath(__DIR__."/../../public").DIRECTORY_SEPARATOR.'extended'.DIRECTORY_SEPARATOR.'font'.DIRECTORY_SEPARATOR.'Edmundsbury_Serif.ttf';

		$textColor = [0, 0, 0];
		$strokeColor = [255, 255, 255];
		$rectangleColor = [255, 255, 255];

		// if($data["invert_colors"]) {
			$textColor = [255, 255, 255];
			$strokeColor = [0, 0, 0];
			$rectangleColor = [0, 0, 0];
		// }

		$bg = $url;
		$image->setDimensionsFromImage($bg);
		$image->draw($bg);
		$image->setAlignHorizontal('center');
		$image->setAlignVertical('center');
		$image->setFont($font);
		$image->setTextColor($textColor);
		$image->setStrokeWidth(1);
		$image->setStrokeColor($strokeColor);
		$gutter = 50;
		$fontSizeAuthor = 20;
		$image->rectangle($gutter, $gutter, $image->getWidth() - $gutter * 2, $image->getHeight() - $gutter * 2 + $fontSizeAuthor, $rectangleColor, 0.5);

		$image->textBox("“".html_entity_decode($entity->getTextQuotation())."”", [
			'width' => $image->getWidth() - $gutter * 2,
			'height' => $image->getHeight() - $gutter * 2,
			'fontSize' => $size,
			'x' => $gutter,
			'y' => $gutter
		]);

		if($entity->isProverbFamily())
			$image->textBox($entity->getCountry()->getTitle(), ['width' => $image->getWidth() - $gutter * 2, 'fontSize' => $fontSizeAuthor, 'x' => $gutter, 'y' => ($image->getHeight() - $gutter * 2) + $fontSizeAuthor * 2]);
		else
			$image->textBox($entity->getAuthorQuotation()->getTitle(), ['width' => $image->getWidth() - $gutter * 2, 'fontSize' => $fontSizeAuthor, 'x' => $gutter, 'y' => ($image->getHeight() - $gutter * 2) + $fontSizeAuthor * 2]);
		
		$fileName = uniqid()."_image.png";

		ob_start (); 

		imagepng ($image->getResource());
		$image_data = ob_get_contents (); 

		ob_end_clean (); 

		$image_data_base64 = base64_encode ($image_data);

		return new JsonResponse(["content" => $image_data_base64]);
	}

	#[Route('/remove_image/{id}/{quotationImageId}', name: 'Quotation_Admin_RemoveImage')]
	public function removeImage(Request $request, EntityManagerInterface $em, $id, $quotationImageId)
	{
		$entity = $em->getRepository(Quotation::class)->find($id);

		$quotationImage = $em->getRepository(QuotationImage::class)->find($quotationImageId);

		$fileName = $quotationImage->getImage();

		$entity->removeImage($quotationImage);

		$em->persist($entity);
		$em->flush();

		$filesystem = new Filesystem();
		$filesystem->remove($entity->getAssetImagePath().$fileName);

		$redirect = $this->generateUrl('Quotation_Admin_Show', array('id' => $entity->getId()));

		return $this->redirect($redirect);
	}

	#[Route('/reload_by_language', name: 'Quotation_Admin_ReloadByLanguage')]
	public function reloadByLanguage(Request $request, EntityManagerInterface $em)
	{
		$language = $em->getRepository(Language::class)->find($request->request->get('id'));
		$translateArray = [];
		
		if(!empty($language)) {
			$currentLanguagesWebsite = explode(",", $_ENV["LANGUAGES"]);
			if(!in_array($language->getAbbreviation(), $currentLanguagesWebsite))
				$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => 'en']);

			$countries = $em->getRepository(Region::class)->findByLanguage($language, ['title' => 'ASC']);
		}
		else {
			$countries = $em->getRepository(Region::class)->findAll();
		}

		$countryArray = [];
		
		foreach($countries as $country)
			$countryArray[] = ["id" => $country->getId(), "title" => $country->getTitle()];

		$translateArray['country'] = $countryArray;
		
		$response = new Response(json_encode($translateArray));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
}