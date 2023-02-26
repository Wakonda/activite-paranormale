<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TelevisionSerieSearchType extends AbstractType
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
			->add('sort', ChoiceType::class, ['required' => false, 'choices' => ["televisionSerie.search.TitleUp" => "title#asc", "televisionSerie.search.TitleDown" => "title#desc", "televisionSerie.search.PublicationDateUp" => "publicationDate#asc", "televisionSerie.search.PublicationDateDown" => "publicationDate#desc"], "data" => "publicationDate#desc", 'translation_domain' => 'validators'])
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