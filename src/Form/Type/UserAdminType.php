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
use Symfony\Component\Validator\Constraints\Url;

use App\Service\Currency;
use App\Entity\User;
use App\Entity\Region;

class UserAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$locale = $options["locale"];
        $builder
            ->add('username', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('email', EmailType::class, ['constraints' => [new NotBlank()]])
			->add("civility", ChoiceType::class, array('expanded' => false, 'required' => false, 'multiple' => false, 'translation_domain' => 'validators',  'choices'   => array(
				'user.register.Man' => 'man',
				'user.register.Woman' => 'woman',
				'user.register.Other' => 'other',
				)
			))
			->add('country', EntityType::class, array('class'=>'App\Entity\Region', 'required' => false, 'choice_label'=>'title', 'query_builder' => function(EntityRepository $er) use ($locale)
				{
					return $er->createQueryBuilder('u')
					          ->join("u.language", "l")
							  ->where("l.abbreviation = :abbreviation")
							  ->setParameter("abbreviation", $locale)
							  ->andWhere("u.family = :countryFamily")
							  ->setParameter("countryFamily", Region::COUNTRY_FAMILY)
							  ->orderBy('u.title', 'ASC');
				},
			))
			->add('birthDate', DateType::class, array('required' => false, 'format' => 'dd/MM/yyyy', 'placeholder' => '', 'years' => range(1902, date('Y'))))
            ->add('avatar', FileType::class, array('data_class' => null, 'required' => true))
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'data' => $builder->getData()->getAvatar()])
            ->add('city')
			->add('blog', TextType::class, ['required' => false, 'constraints' => [new Url()]])
			->add('siteWeb', TextType::class, ['required' => false, 'constraints' => [new Url()]])
            ->add('presentation')
			->add('roles', ChoiceType::class, [
				"multiple" => true,
				'choices'  => [
					User::ROLE_ADMIN => User::ROLE_ADMIN,
					User::ROLE_MODERATOR => User::ROLE_MODERATOR,
					User::ROLE_JOURNALIST => User::ROLE_JOURNALIST,
					User::ROLE_CORRECTOR => User::ROLE_CORRECTOR,
					User::ROLE_TRANSLATOR => User::ROLE_TRANSLATOR,
					User::ROLE_ARCHIVIST => User::ROLE_ARCHIVIST,
					User::ROLE_SIMPLE => User::ROLE_SIMPLE,
					User::ROLE_TRADUCTOR => User::ROLE_TRADUCTOR,
					User::ROLE_BANNED => User::ROLE_BANNED,
					User::ROLE_DISABLED => User::ROLE_DISABLED,
					User::ROLE_DEFAULT => User::ROLE_DEFAULT
				]
			])
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
        return 'ap_user_useradmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'locale' => 'fr',
		]);
	}
}