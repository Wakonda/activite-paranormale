<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;

use App\Service\Currency;
use App\Entity\User;

class EditProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$locale = $options["locale"];
        $builder
            ->add('email', EmailType::class)
			->add("civility", ChoiceType::class, array('expanded' => false, 'required' => false, 'multiple' => false, 'translation_domain' => 'validators',  'choices'   => array(
				'user.register.Man' => 'man',
				'user.register.Woman' => 'woman',
				'user.register.Other' => 'other',
				)
			))
			->add('country', EntityType::class, array('class'=>'App\Entity\Country', 'required' => false, 'choice_label'=>'title', 'query_builder' => function(EntityRepository $er) use ($locale)
				{
					return $er->createQueryBuilder('u')
					          ->join("u.language", "l")
							  ->where("l.abbreviation = :abbreviation")
							  ->setParameter("abbreviation", $locale)
							  ->orderBy('u.title', 'ASC');
				},
			))
			->add('birthDate', DateType::class, array('required' => false, 'format' => 'dd/MM/yyyy', 'placeholder' => '', 'years' => range(1902, date('Y'))))
            ->add('avatar', FileType::class, array('data_class' => null, 'required' => true))
            ->add('city')
            ->add('siteWeb')
            ->add('blog')
            ->add('presentation')
        ;
		
		$donationArray = [];
		
		if($builder->getData()->getId() != null)
			if($builder->getData()->getDonation() != null)
				$donationArray = json_decode($builder->getData()->getDonation(), true);

		foreach(array_merge(["Paypal"], Currency::getCryptoCurrencies()) as $donation) {
			$key = array_search(ucfirst($donation), array_column($donationArray, "donation"));
			$placeholder = 'user.donation.EmailAddress';

			if($donation != "Paypal")
				$placeholder = 'user.donation.AddressOfYourWallet';
		
			$builder->add(strtolower($donation), TextType::class, array('label' => $donation, 'required' => false, 'translation_domain' => 'validators', 'attr' => ['placeholder' => $placeholder], 'mapped' => false, 'data' => ((isset($donationArray[$key]) and $key !== false) ? $donationArray[$key]["address"] : "")));
		}

		$builder
			->add('donation', HiddenType::class, array('label' => false, 'required' => false, 'attr' => array('class' => 'invisible')))
			->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmitData'));
			
		$avatar = $builder->getData()->getAvatar();

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($avatar)
		{
			$data = $event->getData();
			$form = $event->getForm();
			$notBlank = new NotBlank();

			if(is_object($data->getAvatar()))
			{
				$formatArray = array('image/png', 'image/jpeg', 'image/gif');

				if(!in_array($data->getAvatar()->getMimeType(), $formatArray))
					$form->get('avatar')->addError(new FormError('news.error.FileFormat'));

				if($data->getAvatar()->getSize() > $data->getAvatar()->getMaxFilesize())
					$form->get('avatar')->addError(new FormError('news.error.FileSizeError'));
			}

			if($data->getAvatar() == null and empty($avatar))
				$form->get('avatar')->addError(new FormError($notBlank->message));
		});
    }
	
	public function onPreSubmitData(FormEvent $event)
	{
		$data = $event->getData();
		$json = [];
		
		foreach(array_merge(["Paypal"], Currency::getCryptoCurrencies()) as $donation)
			if(!empty($data[strtolower($donation)]))
				$json[] = ["donation" => ucfirst($donation), "address" => $data[strtolower($donation)]];
		
		$data["donation"] = json_encode($json);

		$event->setData($data);
	}

    public function getBlockPrefix()
    {
        return 'ap_user_editprofiletype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'locale' => 'fr',
		));
	}
}