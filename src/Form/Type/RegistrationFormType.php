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
use App\Entity\Region;

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
									  ->andWhere('u.family = :country')
									  ->setParameter('country', Region::COUNTRY_FAMILY)
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


		$socialNetworkTikTokDefault = "";
		$socialNetworkFacebookDefault = "";
		$socialNetworkTwitterDefault = "";
		$socialNetworkYoutubeDefault = "";
		$socialNetworkInstagramDefault = "";
		$socialNetworkPinterestDefault = "";
		$socialNetworkLinkedinDefault = "";

		if($builder->getData()->getSocialNetwork() != null)
		{
			$socialNetworkArray = json_decode($builder->getData()->getSocialNetwork());

			foreach($socialNetworkArray as $socialNetwork)
			{
				switch($socialNetwork->link)
				{
					case "TikTok":
						$socialNetworkTikTokDefault = $socialNetwork->url;
						break;
					case "Facebook":
						$socialNetworkFacebookDefault = $socialNetwork->url;
						break;
					case "Twitter":
						$socialNetworkTwitterDefault = $socialNetwork->url;
						break;
					case "Youtube":
						$socialNetworkYoutubeDefault = $socialNetwork->url;
						break;
					case "Instagram":
						$socialNetworkInstagramDefault = $socialNetwork->url;
						break;
					case "Pinterest":
						$socialNetworkPinterestDefault = $socialNetwork->url;
						break;
					case "Linkedin":
						$socialNetworkLinkedinDefault = $socialNetwork->url;
						break;
				}
			}
		}

		$builder
			->add('socialNetworkFacebook', TextType::class, array('label' => 'Facebook', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Facebook', 'class' => 'social_network_select'), 'data' => $socialNetworkFacebookDefault, 'constraints' => [new Url()]))
			->add('socialNetworkTwitter', TextType::class, array('label' => 'Twitter', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Twitter', 'class' => 'social_network_select'), 'data' => $socialNetworkTwitterDefault, 'constraints' => [new Url()]))
			->add('socialNetworkTikTok', TextType::class, array('label' => 'TikTok', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'TikTok', 'class' => 'social_network_select'), 'data' => $socialNetworkTikTokDefault, 'constraints' => [new Url()]))
			->add('socialNetworkYoutube', TextType::class, array('label' => 'Youtube', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Youtube', 'class' => 'social_network_select'), 'data' => $socialNetworkYoutubeDefault, 'constraints' => [new Url()]))
			->add('socialNetworkInstagram', TextType::class, array('label' => 'Instagram', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Instagram', 'class' => 'social_network_select'), 'data' => $socialNetworkInstagramDefault, 'constraints' => [new Url()]))
			->add('socialNetworkPinterest', TextType::class, array('label' => 'Pinterest', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Pinterest', 'class' => 'social_network_select'), 'data' => $socialNetworkPinterestDefault, 'constraints' => [new Url()]))
			->add('socialNetworkLinkedin', TextType::class, array('label' => 'Linkedin', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Linkedin', 'class' => 'social_network_select'), 'data' => $socialNetworkLinkedinDefault, 'constraints' => [new Url()]))

			->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmitData'))
		;
		
		$builder->add('socialNetwork', HiddenType::class, array('label' => false, 'required' => false, 'attr' => ['class' => 'invisible']));

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

		$linkJson = [
			["link" => "Facebook", "url" => $data['socialNetworkFacebook'], "label" => "FacebookAccount"],
			["link" => "Twitter", "url" => $data['socialNetworkTwitter'], "label" => "TwitterAccount"],
			["link" => "TikTok", "url" => $data['socialNetworkTikTok'], "label" => "TikTokAccount"],
			["link" => "Youtube", "url" => $data['socialNetworkYoutube'], "label" => "YoutubeAccount"],
			["link" => "Instagram", "url" => $data['socialNetworkInstagram'], "label" => "InstagramAccount"],
			["link" => "Pinterest", "url" => $data['socialNetworkPinterest'], "label" => "PinterestAccount"],
			["link" => "Linkedin", "url" => $data['socialNetworkLinkedin'], "label" => "LinkedInAccount"]
		];

		$data["socialNetwork"] = json_encode($linkJson);

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