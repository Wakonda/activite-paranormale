<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Form\Field\DateTimePartialType;

class TestimonyUserParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options["locale"];
		$user = $options["user"];
		$securityUser = $options["securityUser"];

        $builder
            // ->add('title', TextType::class, array('label' => 'Titre', 'required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label' => 'Témoignage', 'required' => true, 'constraints' => array(new NotBlank())))
            /*->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
				'choice_label'=>'title',
				'placeholder' => "",
				'constraints' => array(new NotBlank()),
				'required' => true,
				'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}
			))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
				'choice_label'=>'title', 
				'required' => true,
				'placeholder' => "",
				'constraints' => array(new NotBlank()),
				'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			))*/
			->add('country', EntityType::class, array('class'=>'App\Entity\Country', 
					'choice_label'=>'title', 
					'required' => false,
					'mapped' => false,
					'choice_value' => function ($entity) {
						return $entity ? $entity->getInternationalName() : '';
					},
					'query_builder' => function(\App\Repository\CountryRepository $repository) use ($language) { return $repository->getCountryByLanguage($language);}))
			->add('location_selector', ChoiceType::class, ['multiple' => false, 'expanded' => false, "required" => false, "mapped" => false])
			->add('location', HiddenType::class)
			->add('sightingDate', DateTimePartialType::class, ['required' => false])
			->add('nextStep', SubmitType::class, ['attr' => ['class' => 'submitcomment btn']])
			->add('save', SubmitType::class, ['attr' => ['class' => 'submitcomment btn']]);

		if(!is_object($user))
		{
			$builder
				->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())))
				->add('emailAuthor', TextType::class, array('required' => false, 'constraints' => array(new Email())));
		}
		else
		{
			$builder->add('isAnonymous', ChoiceType::class, array(
				'choices'   => array(
					'testimony.new.PublishedAnonymously' => 1,
					'testimony.new.PostedWithMyUserAccount' => 0
				),
				'multiple'  => false,
				'expanded'  => false,
				'constraints' => array(new NotBlank()),
				'placeholder' => false,
				'data' => 0,
				'translation_domain' => 'validators'
			));
		}

		if($securityUser->isGranted('IS_AUTHENTICATED_FULLY'))
		{
			$builder
			->add('preview', SubmitType::class, array(
				'attr' => array('class' => 'submitcomment btn'),
			))
			->add('draft', SubmitType::class, array(
				'attr' => array('class' => 'submitcomment btn'),
				'validation_groups' => false
			));
		}

		$builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
			$form = $event->getForm();

			if ($form->has('location_selector')) {
				$form->remove('location_selector');
				$form->add(
					'location_selector', 
					TextType::class, 
					['required' => false, 'mapped' => false]
				);
			}
		});

		$builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
			$form = $event->getForm();

			if ($form->has('location_selector')) {
				$form->remove('location_selector');
				$form->add('location_selector', ChoiceType::class, ['multiple' => false, 'expanded' => false, "required" => false, "mapped" => false]);
			}
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_testimony_testimonyuserparticipationtype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Testimony',
			'locale' => 'fr',
			'user' => null,
			'securityUser' => null
		));
	}
}