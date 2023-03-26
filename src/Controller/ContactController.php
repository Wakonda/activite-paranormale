<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Contact;
use App\Form\Type\ContactType;

use Symfony\Component\DomCrawler\Crawler;

class ContactController extends AbstractController
{
    public function indexAction()
    {	
        $entity = new Contact();
        $form = $this->createForm(ContactType::class, $entity);

        return $this->render('contact/Contact/index.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    public function sendAction(Request $request, TranslatorInterface $translator, \Swift_Mailer $mailer)
    {
        $entity  = new Contact();
        $form = $this->createForm(ContactType::class, $entity);
		$entity->setDateContact(new \DateTime("now"));
		$entity->setStateContact(0);
        $form->handleRequest($request);
		
		$params = $request->request->get($form->getName());
		$session = $request->getSession();
		
		if($params["captcha"] != $session->get("captcha_word"))
			$form->get('captcha')->addError(new FormError($translator->trans('captcha.error.InvalidCaptcha', [], 'validators')));

		if ($form->isValid()) {
			$message = (new \Swift_Message($entity->getSubjectContact()))
				->setTo($_ENV["MAILER_CONTACT"])
				->setFrom(array($entity->getEmailContact()))
				->setBody($this->renderView('contact/Contact/mail.html.twig', array('entity' => $entity)), 'text/html')
			;
			$mailer->send($message);
		
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

			return $this->render('contact/Contact/send.html.twig', array(
				'id' => $entity->getId(),
			));
        }

        return $this->render('contact/Contact/index.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }
	
	public function offerLinkAction(TranslatorInterface $translator)
	{
		$entity = new Contact();
		
		$entity->setSubjectContact($translator->trans('index.leftMenu.OfferLink', [], 'validators'));
		
        $form = $this->createForm(ContactType::class, $entity);

        return $this->render('contact/Contact/index.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
	}
}