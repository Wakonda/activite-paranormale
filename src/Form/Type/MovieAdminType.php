<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use App\Entity\Movies\Movie;
use App\Entity\Movies\MovieBiography;
use App\Form\Field\SourceEditType;
use App\Form\Field\IdentifiersEditType;
use App\Form\Field\ReviewScoresEditType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use App\Service\Currency;
use App\Entity\TagWord;
use App\Form\Field\DatePartialType;
use App\Form\EventListener\InternationalNameFieldListener;

class MovieAdminType extends AbstractType
{
	private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('introduction', TextareaType::class, array('required' => false))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('trailer', TextareaType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('fullStreaming', TextareaType::class, array('required' => false))
            ->add('duration', IntegerType::class, array('required' => false))
            ->add('releaseYear', DatePartialType::class, array('required' => false))
			->add('genre', EntityType::class, array('class'=>'App\Entity\Movies\GenreAudiovisual',
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(\App\Repository\GenreAudiovisualRepository $repository) use ($language) { return $repository->getGenreByLanguage($language);}))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
					'choice_label'=>'title',
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(EntityRepository $er)
						{
							return $er->createQueryBuilder('u')
									  ->orderBy('u.title', 'ASC');
						},
				))
            ->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
					'choice_label'=>'title',
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => array(new NotBlank()),
					'choice_attr' => function($val, $key, $index) {
						return ['data-intl' => $val->getInternationalName()];
					}))
			->add('country', EntityType::class, array('class'=>'App\Entity\Country', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(\App\Repository\CountryRepository $repository) use ($language) { return $repository->getCountryByLanguage($language);}))
		    ->add('previous', Select2EntityType::class, [
				'multiple' => false,
				'remote_route' => 'Movie_Admin_Autocomplete',
				'class' => Movie::class,
				'page_limit' => 10,
				'primary_key' => 'id',
				'text_property' => 'title',
				'allow_clear' => true,
				'delay' => 250,
				'cache' => false,
				'req_params' => ['locale' => 'parent.children[language]'],
				'language' => $language
			])
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'Movie_Admin_ShowImageSelectorColorbox'))
			->add('movieBiographies', CollectionType::class, array("label" => false, "required" => false, 'entry_type' => BiographiesAdminType::class, "allow_add" => true, "allow_delete" => true, "entry_options" => ["label" => false, "data_class" => MovieBiography::class, "language" => $language, "req_params" => ["locale" => $this->getBlockPrefix()."[language]"]]))
		    ->add('tags', Select2EntityType::class, [
				'multiple' => true,
				'allow_add' => [
					'enabled' => true,
					'new_tag_text' => '',
					'new_tag_prefix' => '__',
					'tag_separators' => '[","]'
				],
				'remote_route' => 'TagWord_Admin_Autocomplete',
				'class' => TagWord::class,
				'req_params' => ['locale' => 'parent.children[language]'],
				'page_limit' => 10,
				'primary_key' => 'id',
				'text_property' => 'title',
				'allow_clear' => true,
				'delay' => 250,
				'cache' => false,
				'language' => $language,
				'mapped' => false,
				'data' => $builder->getData(),
				"transformer" => \App\Form\DataTransformer\TagWordTransformer::class
			])
            ->add('source', SourceEditType::class, array('required' => false))
            ->add('identifiers', IdentifiersEditType::class, array('required' => false))
			->add('wikidata', TextType::class, ['required' => false])
			->add('boxOffice', NumberType::class, array('required' => true, 'translation_domain' => 'validators', "required" => false))
			->add('boxOfficeUnit', ChoiceType::class, array('choices' => Currency::getSymboleValues(), 'expanded' => false, 'multiple' => false, 'required' => false, 'translation_domain' => 'validators'))
			->add('cost', NumberType::class, array('required' => true, 'translation_domain' => 'validators', "required" => false))
			->add('costUnit', ChoiceType::class, array('choices' => Currency::getSymboleValues(), 'expanded' => false, 'multiple' => false, 'required' => false, 'translation_domain' => 'validators'))
            ->add('reviewScores', ReviewScoresEditType::class, array('required' => false))
		;

		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }

    public function getBlockPrefix()
    {
        return 'ap_movie_movieadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => Movie::class,
			'locale' => 'fr'
		));
	}
}