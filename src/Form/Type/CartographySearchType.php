<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CartographySearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('theme', EntityType::class, ['label' => 'Thème', 'class'=>'App\Entity\Theme',
						'choice_label'=>'title',
						'required' => false,
						'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language); }])
		;
    }

    public function getBlockPrefix(): string
    {
        return 'form';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'locale' => 'fr',
			'validation_groups' => ['form_validation_only']
		));
	}
}