<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class MovieSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('keywords', TextType::class, ['required' => false])
            ->add('genre', EntityType::class, ['label' => 'Thème', 
					'class'=>'App\Entity\Movies\GenreAudiovisual',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\GenreAudiovisualRepository $repository) use ($language) {
						return $repository->getGenreByLanguage($language);
					}])
            ->add('theme', EntityType::class, [
					'class'=>'App\Entity\Theme',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) {
						return $repository->getThemeByLanguage($language);
					}])
			->add('releaseYear', IntegerType::class, ['required' =>false, 'translation_domain' => 'validators'])
			->add('sort', ChoiceType::class, ['required' => false, 'choices' => ["movie.search.TitleUp" => "title#asc", "movie.search.TitleDown" => "title#desc", "movie.search.ReleaseYearUp" => "releaseYear#asc", "movie.search.ReleaseYearDown" => "releaseYear#desc", "movie.search.PublicationDateUp" => "publicationDate#asc", "movie.search.PublicationDateDown" => "publicationDate#desc"], "data" => "publicationDate#desc", 'translation_domain' => 'validators'])
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