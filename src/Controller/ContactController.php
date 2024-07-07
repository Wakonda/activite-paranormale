<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Contact;
use App\Entity\User;
use App\Service\APDate;
use App\Form\Type\ContactType;
use App\Form\Type\ContactPrivateMessageType;

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

	public function sendPrivateMessage(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, MailerInterface $mailer, $userId, $initialMessageId = null, $idClassName = null, $className = null) {
        $entity = new Contact();
		$recipient = $em->getRepository(User::class)->find($userId);

		$initialMessageEntity = null;
		
		if(!empty($initialMessageId)) {
			$initialMessageEntity = $em->getRepository(Contact::class)->find($initialMessageId);
			$entity->setSubjectContact($initialMessageEntity->getSubjectContact());
		}
		
		$recipientName = null;
		$recipientId = null;

		switch($className) {
			case 'ClassifiedAds':
				$entityLink = $em->getRepository(\App\Entity\ClassifiedAds::class)->find($idClassName);
				$link = $translator->trans('index.className.ClassifiedAds', [], 'validators')." - <a href='".$this->generateUrl("ClassifiedAds_Read", ["id" => $idClassName, "title_slug" => $entityLink->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$entityLink->getTitle()."</a>";
				$recipientName = (!empty($recipient) ? $recipient->getUsername() : (!empty($d = $entityLink->getContactName()) ? $d : ($entityLink->displayEmail() ? $entityLink->getContactEmail() : "")));
				$recipientId = (!empty($recipient) ? $recipient->getId() : 0);
				break;
		}

        $form = $this->createForm(ContactPrivateMessageType::class, $entity, ["initialMessage" => $initialMessageEntity]);

		if ($request->isMethod('POST')) {
			$form->handleRequest($request);
			$session = $request->getSession();

			$params = $request->request->all($form->getName());
			if($params["captcha"] != $session->get("captcha_word"))
				$form->get('captcha')->addError(new FormError($translator->trans('captcha.error.InvalidCaptcha', [], 'validators')));

			if ($form->isSubmitted() && $form->isValid()) {
				if(!empty($recipient)) {
					if(!empty($user = $this->getUser()))
						$entity->setSender($user);

					$entity->setRecipient($recipient);
					$entity->setInitialMessage(empty($initialMessageEntity) ? $entity : $initialMessageEntity);
					$em->persist($entity);
					$em->flush();
				}

				$entityLinked = null;
				$link = null;
				$recipientEmail = null;
				$redirect = $this->redirect($this->generateUrl("APUserBunble_otherprofile", ["id" => $userId]));

				switch($className) {
					case 'ClassifiedAds':
						$entityLink = $em->getRepository(\App\Entity\ClassifiedAds::class)->find($idClassName);
						$link = $translator->trans('index.className.ClassifiedAds', [], 'validators')." - <a href='".$this->generateUrl("ClassifiedAds_Read", ["id" => $idClassName, "title_slug" => $entityLink->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$entityLink->getTitle()."</a>";
						$recipientEmail = !empty($recipient) ? $recipient->getEmail() : $entityLink->getContactEmail();
						$redirect = $this->redirect($this->generateUrl($entityLink->getShowRoute(), ["id" => $entityLink->getId()]));
						break;
				}

				if(!empty($link))
					$entity->setMessageContact($entity->getMessageContact()."<br><br>".$link);

				if(!empty($entity->getEmailContact()) and !empty($recipientEmail)) {
					$email = (new Email())
						->from($entity->getEmailContact())
						->to($recipientEmail)
						->subject("Activité-Paranormale - ".$entity->getSubjectContact())
						->html($this->renderView('contact/Contact/mail.html.twig', ['entity' => $entity]));

					$mailer->send($email);
				}

				$session->getFlashBag()->add('success', $translator->trans('privateMessage.send.Success', [], 'validators'));

				if(!empty($this->getUser()))
					return $this->redirect($this->generateUrl("Contact_IndexPrivateMessage"));
				
				return $redirect;
			}
		}

		return $this->render("contact/Contact/privateMessage.html.twig", [
            'entity' => $entity,
            'recipientId' => $recipientId,
            'recipientName' => $recipientName,
            'form' => $form->createView(),
			'initialMessageId' => !empty($initialMessageEntity) ? $initialMessageEntity->getId() : null,
			'className' => $className,
			'idClassName' => $idClassName
        ]);
	}

	public function indexPrivateMessage(EntityManagerInterface $em) {
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
		$unreadMessage = $em->getRepository(Contact::class)->countUnreadMessage($this->getUser());

		return $this->render("contact/Contact/indexPrivateMessage.html.twig", ["unreadMessage" => $unreadMessage]);
	}

	public function readPrivateMessage(EntityManagerInterface $em, $messageId, $initialMessageId) {
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
		$initialMessage = $em->getRepository(Contact::class)->find($initialMessageId);
		$entities = $em->getRepository(Contact::class)->findBy(["initialMessage" => $initialMessageId, "recipient" => $this->getUser()], ["dateContact" => "desc"]);

		foreach($entities as $entity) {
			$entity->setStateContact(1);
			$em->persist($entity);
		}

		$em->flush();

		return $this->render("contact/Contact/readPrivateMessage.html.twig", ["entities" => $entities, "initialMessage" => $initialMessage]);
	}

	public function privateMessageDatatables(Request $request, EntityManagerInterface $em, APDate $date, $type)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Contact::class)->getDatatablesPrivateMessage($type, $this->getUser(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Contact::class)->getDatatablesPrivateMessage($type, $this->getUser(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $data)
		{
			$entity = $data[0];
			$state = $data["reading"];
			$dateContact = new \DateTime($data["dateContact"]);

			$row = [];
			$row[] = $entity->getSubjectContact();
			
			if($type == "inbox")
				$row[] = !empty($pseudo = $entity->getPseudoContact()) ? $pseudo : $entity->getSender()->getUsername();
			else
				$row[] = $entity->getRecipient()->getUsername();
			
			$reading = '<i class="fa-solid fa-eye"></i>';
			
			if(empty($state) and $type == "inbox")
				$reading = '<i class="fa-solid fa-eye-slash"></i>';

			$row[] = $date->doDate($request->getLocale(), $dateContact);
			$row[] = '<a href="'.$this->generateUrl("Contact_ReadPrivateMessage", ['messageId' => $entity->getId(), 'initialMessageId' => $entity->getInitialMessage()->getId()]).'" class="btn '.((empty($state) and $type == "inbox") ? 'btn-danger' : 'btn-success').' btn-sm">'.$reading.'</a>';

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}
}