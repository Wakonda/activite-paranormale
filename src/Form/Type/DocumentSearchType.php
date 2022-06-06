<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class DocumentSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		// dd($builder->getData());
		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('documentFamily', EntityType::class, ['label' => 'Thème', 
					'class'=>'App\Entity\DocumentFamily',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\DocumentFamilyRepository $repository) use ($language) {
						return $repository->getDocumentFamilyByLanguage($language);
					},'choice_value' => function ($entity) {
						return $entity ? $entity->getInternationalName() : '';
					}])
            ->add('theme', EntityType::class, ['label' => 'Thème', 'class'=>'App\Entity\Theme',
						'choice_label'=>'title',
						'required' => false,
						'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language); }])
		;
    }

    public function getBlockPrefix()
    {
        return 'form';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'locale' => 'fr',
			'validation_groups' => ['form_validation_only']
		));
	}
}