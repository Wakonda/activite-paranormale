<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class PresidentAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(EntityRepository $er) 
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
				))
			->add('illustration', IllustrationType::class, array('required' => true, 'base_path' => 'President_Admin_ShowImageSelectorColorbox', 'file_path' => $builder->getData()->getAssetImagePath()))
            ->add('logo', FileType::class, ["required" => false, 'data_class' => null])
			->add('logo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => null, 'data' => $builder->getData()->getLogo()])
			->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => [new NotBlank()]))
			->add('pseudoUsed', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
			->add('numberOfDays', IntegerType::class, array('required' => true, 'constraints' => [new NotBlank()]))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
				'choice_label'=>'title', 
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
				))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
				'choice_label'=>'title',
				'choice_attr' => function($val, $key, $index) {
					return ['data-intl' => $val->getInternationalName()];
				},
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}))
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_page_presidentadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\President',
			'locale' => 'fr'
		));
	}
}