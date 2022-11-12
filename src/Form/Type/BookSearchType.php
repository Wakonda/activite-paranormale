<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

class BookSearchType extends AbstractType
{
    public $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('keywords', TextType::class, ['required' => false])
            ->add('theme', EntityType::class, [
					'class'=>'App\Entity\Theme',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) {
						return $repository->getThemeByLanguage($language);
					}])
            ->add('genre', EntityType::class, [
					'class'=>'App\Entity\LiteraryGenre',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\LiteraryGenreRepository $repository) use ($language) {
						return $repository->getGenreByLanguage($language);
					},
					'group_by' => function($choice, $key, $value) {
						if($choice->getFiction())
							return $this->translator->trans("book.search.Fiction", [], "validators");
						
						return $this->translator->trans("book.search.Nonfiction", [], "validators");
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