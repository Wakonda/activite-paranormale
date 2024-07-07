<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ClassifiedAdsSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];
		$builder->setMethod('GET');

        $builder
            ->add('keywords', TextType::class, ['required' => false])
			->add('country', EntityType::class, array('class'=>'App\Entity\Region', 
					'choice_label'=>'title', 
					'required' => false,
					'choice_value' => function ($entity) {
						return $entity ? $entity->getInternationalName() : '';
					},
					'query_builder' => function(\App\Repository\RegionRepository $repository) use ($language) { return $repository->getCountryByLanguage($language);}))
			->add('location_raw', TextType::class, ["required" => false, "mapped" => false, "data" => isset($builder->getData()["location_raw"]) ? $builder->getData()["location_raw"] : null])
            ->add('category', EntityType::class, array('class'=>'App\Entity\ClassifiedAdsCategory', 
					'choice_label'=>'title', 
					'required' => false,
					'group_by' => 'getParentCategoryTitle',
					'query_builder' => function(EntityRepository $er) use ($language) {
						return $er->createQueryBuilder('u')
								  ->leftJoin('u.language', 'l')
								  ->where('l.abbreviation = :abbreviation')
								  ->setParameter('abbreviation', $language)
								  ->andWhere("u.parentCategory IS NOT NULL")
								  ->orderBy('u.title', 'ASC');
					}
			))
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_classifiedads_searchtype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'locale' => 'fr',
			'validation_groups' => ['form_validation_only']
		]);
	}
}