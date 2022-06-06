<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class EventMessageAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('label'=>'Titre', 'required' =>true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label'=>'Texte', 'required' =>true, 'constraints' => array(new NotBlank())))
			->add('dateFrom', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
			->add('dateTo', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
											'choice_label'=>'title', 
											'required' => true,
										    'query_builder' => function(EntityRepository $er) 
														{
															return $er->createQueryBuilder('u')
																	->orderBy('u.title', 'ASC');
														},
											'constraints' => array(new NotBlank())
											))
			->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
											'choice_label'=>'title', 
											'required' => true,
											'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);},
											'constraints' => array(new NotBlank())
											))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
											'choice_label'=>'title',
											'constraints' => array(new NotBlank()),
											'choice_attr' => function($val, $key, $index) {
												return ['data-intl' => $val->getInternationalName()];
											},
											'required' => true,
											'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			))
			->add('photo', FileType::class, array('data_class' => null, 'required' => true))
			->add('thumbnail', FileType::class, array('data_class' => null, 'required' => false))
            ->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
											'choice_label'=>'title',
											'constraints' => array(new NotBlank()),
											'required' => true,
											'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}))
			->add('longitude', TextType::class, array('required' =>true, 'constraints' => array(new NotBlank())))
			->add('latitude', TextType::class, array('required' =>true, 'constraints' => array(new NotBlank())))
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_page_eventmessageadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\EventMessage',
			'locale' => 'fr'
		));
	}
}