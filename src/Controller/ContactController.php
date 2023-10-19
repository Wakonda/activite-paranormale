<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Contact;
use App\Form\Type\ContactType;

class ContactController extends AbstractController
{
    public function indexAction()
    {
        $entity = new Contact();
        $form = $this->createForm(ContactType::class, $entity);

        return $this->render('contact/Contact/index.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    public function sendAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, MailerInterface $mailer)
    {
        $entity  = new Contact();
        $form = $this->createForm(ContactType::class, $entity);
		$entity->setDateContact(new \DateTime("now"));
		$entity->setStateContact(0);
        $form->handleRequest($request);

		$params = $request->request->all($form->getName());
		$session = $request->getSession();

		if($params["captcha"] != $session->get("captcha_word"))
			$form->get('captcha')->addError(new FormError($translator->trans('captcha.error.InvalidCaptcha', [], 'validators')));

		if ($form->isValid()) {
			$email = (new Email())
				->from($entity->getEmailContact())
				->to($_ENV["MAILER_CONTACT"])
				->subject($entity->getSubjectContact())
				->html($this->renderView('contact/Contact/mail.html.twig', ['entity' => $entity]));

			$mailer->send($email);

            $em->persist($entity);
            $em->flush();

			return $this->render('contact/Contact/send.html.twig', [
				'id' => $entity->getId(),
			]);
        }

        return $this->render('contact/Contact/index.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }
}