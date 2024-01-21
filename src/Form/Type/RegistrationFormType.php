<?php

namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use App\Service\Currency;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
		$locale = $options["locale"];

        $builder
            ->add('email', EmailType::class, ['label' => 'form.email', 'translation_domain' => 'FOSUserBundle', 'constraints' => [new NotBlank()]])
            ->add('username', TextType::class, ['label' => 'form.username', 'translation_domain' => 'FOSUserBundle', 'constraints' => [new NotBlank()]])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => ['label' => 'form.password'],
                'second_options' => ['label' => 'form.password_confirmation'],
                'invalid_message' => 'fos_user.password.mismatch',
				'constraints' => [new NotBlank()]
            ])
			->add('country', EntityType::class, ['class'=>'App\Entity\Region', 
					'choice_label' => 'title', 
					'required' => true,
					'placeholder' => "",
					'required' => false,
					'query_builder' => function(EntityRepository $er) use ($locale)
						{
							return $er->createQueryBuilder('u')
									  ->join("u.language", "l")
									  ->where("l.abbreviation = :abbreviation")
									  ->setParameter("abbreviation", $locale)
									  ->orderBy('u.title', 'ASC');
						},
			])
			->add('avatar', FileType::class)
			->add('birthDate', DateType::class, ['required' => false, 'format' => 'dd/MM/yyyy', 'placeholder' => '', 'years' => range(1902, date('Y'))])
			->add('city', TextType::class, ['required' => false])
			->add('blog', TextType::class, ['required' => false, 'constraints' => [new Url()]])
			->add('siteWeb', TextType::class, ['required' => false, 'constraints' => [new Url()]])
			->add('presentation', TextareaType::class, ['required' => false])
			->add("civility", ChoiceType::class, ['placeholder' => null,'expanded' => true, 'multiple' => false, 'translation_domain' => 'validators', 'required' => false, 'choices' => [
				'user.register.Man' => 'man',
				'user.register.Woman' => 'woman',
				'user.register.Other' => 'other'
				]
			]);

		$donationArray = [];

		if($builder->getData()->getId() != null)
			if($builder->getData()->getDonation() != null)
				$donationArray = json_decode($builder->getData()->getDonation(), true);

		foreach(array_merge(["Paypal"], Currency::getCryptoCurrencies()) as $donation) {
			$key = array_search(ucfirst($donation), array_column($donationArray, "donation"));
			$placeholder = 'user.donation.EmailAddress';

			if(strtolower($donation) != "paypal")
				$placeholder = 'user.donation.AddressOfYourWallet';

			$builder->add(strtolower($donation), TextType::class, ['label' => $donation, 'required' => false, 'translation_domain' => 'validators', 'attr' => ['placeholder' => $placeholder], 'mapped' => false, 'data' => ((isset($donationArray[$key]) and $key !== false) ? $donationArray[$key]["address"] : "")]);
		}

		$builder
			->add('donation', HiddenType::class, ['label' => false, 'required' => false, 'attr' => ['class' => 'invisible']])
			->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmitData']);

		$avatar = $builder->getData()->getAvatar();

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($avatar)
		{
			$data = $event->getData();
			$form = $event->getForm();
			$notBlank = new NotBlank();

			if(is_object($data->getAvatar()))
			{
				$formatArray = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

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
			if(!empty($data[$donation]))
				$json[] = ["donation" => ucfirst($donation), "address" => $data[$donation]];

		$data["donation"] = json_encode($json);

		$event->setData($data);
	}

    public function getBlockPrefix()
    {
        return 'ap_user_registration';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'locale' => 'fr',
		]);
	}
}