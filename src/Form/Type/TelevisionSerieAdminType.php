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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use App\Form\Field\SourceEditType;
use App\Form\Field\IdentifiersEditType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Form\EventListener\InternationalNameFieldListener;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use App\Entity\TagWord;
use App\Entity\Movies\TelevisionSerieBiography;

class TelevisionSerieAdminType extends AbstractType
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
            ->add('title', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('introduction', TextareaType::class, array('required' => false))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => [new NotBlank()]))
			->add('genre', EntityType::class, array('class'=>'App\Entity\Movies\GenreAudiovisual',
					'required' => true,
					'constraints' => [new NotBlank()],
					'query_builder' => function(\App\Repository\GenreAudiovisualRepository $repository) use ($language) { return $repository->getGenreByLanguage($language);}))
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
            ->add('theme', ThemeEditType::class, ['locale' => $language, 'label' => 'Thème', 'class'=>'App\Entity\Theme', 'constraints' => [new NotBlank()], 'required' => true])
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => [new NotBlank()],
					'choice_attr' => function($val, $key, $index) {
						return ['data-intl' => $val->getInternationalName()];
					}))
			->add('country', EntityType::class, array('class'=>'App\Entity\Country', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => [new NotBlank()],
					'query_builder' => function(\App\Repository\CountryRepository $repository) use ($language) { return $repository->getCountryByLanguage($language);}))
			
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'Movie_Admin_ShowImageSelectorColorbox'))
			->add('televisionSerieBiographies', CollectionType::class, array("label" => false, "required" => false, 'entry_type' => BiographiesAdminType::class, "allow_add" => true, "allow_delete" => true, "entry_options" => ["label" => false, "data_class" => TelevisionSerieBiography::class, "language" => $language, "req_params" => ["locale" => $this->getBlockPrefix()."[language]"]]))
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
			->add('wikidata', TextType::class, ['required' => false])
            ->add('source', SourceEditType::class, array('required' => false))
			->add('episode', HiddenType::class, ['mapped' => false, 'required' => false])
            ->add('identifiers', IdentifiersEditType::class, ['required' => false, 'enum' => ["IMDb ID", "Rotten Tomatoes ID"]])
		;
		
		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }

    public function getBlockPrefix()
    {
        return 'ap_movie_televisionserieadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Movies\TelevisionSerie',
			'locale' => 'fr'
		));
	}
}