<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BookSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('keywords', TextType::class, ['required' => false])
            ->add('theme', EntityType::class, ['label' => 'Thème', 
					'class'=>'App\Entity\Theme',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) {
						return $repository->getThemeByLanguage($language);
					}])
			->add('sort', ChoiceType::class, ['required' => false, 'choices' => ["book.search.PublicationDateUp" => "publicationDate#asc", "book.search.PublicationDateDown" => "publicationDate#desc"], "data" => "publicationDate#desc", 'translation_domain' => 'validators'])
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