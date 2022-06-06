<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Entity\Quotation;
use App\Entity\QuotationImage;
use App\Entity\Language;
use App\Entity\Biography;
use App\Form\Type\QuotationAdminType;
use App\Form\Type\QuotationImageGeneratorType;
use App\Service\ConstraintControllerValidator;
use App\Service\APParseHTML;
use App\Service\PHPImage;
use App\Service\APImgSize;

/**
 * Quotation controller.
 *
 */
class QuotationAdminController extends AdminGenericController
{
	protected $entityName = 'Quotation';
	protected $className = Quotation::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Quotation_Admin_Index"; 
	protected $showRoute = "Quotation_Admin_Show";
	protected $formName = "ap_quotation_quotationadmintype";

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		// Check for Doublons
		$em = $this->getDoctrine()->getManager();
		$searchForDoublons = $em->getRepository($this->className)->countForDoublons($entityBindded);
		if($searchForDoublons > 0)
			$form->get('authorQuotation')->addError(new FormError($translator->trans('admin.error.Doublon', array(), 'validators')));
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'quotation/QuotationAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$form = $this->createForm(QuotationImageGeneratorType::class);
		$twig = 'quotation/QuotationAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig, ["imageGeneratorForm" => $form->createView()]);
    }

    public function newAction(Request $request)
    {
		$formType = QuotationAdminType::class;
		$entity = new Quotation();

		$twig = 'quotation/QuotationAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale()]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = QuotationAdminType::class;
		$entity = new Quotation();

		$twig = 'quotation/QuotationAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function editAction(Request $request, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository($this->className)->find($id);
		$formType = QuotationAdminType::class;

		$twig = 'quotation/QuotationAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation()]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = QuotationAdminType::class;
		
		$twig = 'quotation/QuotationAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName)]);
    }
	
    public function deleteAction($id)
    {
		return $this->deleteGenericAction($id);
    }

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		foreach($informationArray['entities'] as $entity)
		{
			$row = array();
			$row[] = $entity->getId();
			$row[] = $entity->getAuthorQuotation()->getTitle();
			$row[] = $entity->getTextQuotation();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20px" height="13px">';
			$row[] = "
			 <a href='".$this->generateUrl('Quotation_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Quotation_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />
			";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

    public function WYSIWYGUploadFileAction(Request $request, APImgSize $imgSize)
    {
		return $this->WYSIWYGUploadFileGenericAction($request, $imgSize, new Quotation());
    }

	public function createSameAuthorAction(Request $request, $biographyId)
	{
		$formType = QuotationAdminType::class;
		$entity = new Quotation();
		
		$em = $this->getDoctrine()->getManager();

		$biography = $em->getRepository(Biography::class)->find($biographyId);

		$entity->setAuthorQuotation($biography);
		$entity->setLanguage($biography->getLanguage());

        $form = $this->createForm($formType, $entity, ["locale" => $entity->getLanguage()->getAbbreviation()]);

        return $this->render('quotation/QuotationAdmin/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
	}
	
	public function generateImageAction(Request $request, PHPImage $image, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository($this->className)->find($id);

        $imageGeneratorForm = $this->createForm(QuotationImageGeneratorType::class);
        $imageGeneratorForm->handleRequest($request);
		
		if ($imageGeneratorForm->isSubmitted() && $imageGeneratorForm->isValid()) {
			$data = $imageGeneratorForm->getData();
			
			$font = str_replace("\\", "/", realpath($this->getParameter('kernel.project_dir'). '/../public'))."/extended/font/Edmundsbury_Serif.ttf";
			
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
			$image->rectangle($gutter, $gutter, $image->getWidth() - $gutter * 2, $image->getHeight() - $gutter * 2, $rectangleColor, 0.5);
			$image->textBox("“".$entity->getTextQuotation()."”\n___\n".$entity->getAuthorQuotation()->getTitle(), array(
				'width' => $image->getWidth() - $gutter * 2,
				'height' => $image->getHeight() - $gutter * 2,
				'fontSize' => $data["font_size"],
				'x' => $gutter,
				'y' => $gutter
			));
			
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
		return $this->showGenericAction($id, $twig, ["imageGeneratorForm" => $imageGeneratorForm->createView()]);
	}

	public function removeImageAction(Request $request, $id, $quotationImageId)
	{
		$entityManager = $this->getDoctrine()->getManager();
		$entity = $entityManager->getRepository(Quotation::class)->find($id);
		
		$quotationImage = $entityManager->getRepository(QuotationImage::class)->find($quotationImageId);
		
		$fileName = $quotationImage->getImage();
		
		$entity->removeImage($quotationImage);
		
		$entityManager->persist($entity);
		$entityManager->flush();
		
		$filesystem = new Filesystem();
		$filesystem->remove($entity->getAssetImagePath().$fileName);
		
		$redirect = $this->generateUrl('Quotation_Admin_Show', array('id' => $entity->getId()));

		return $this->redirect($redirect);
	}
}