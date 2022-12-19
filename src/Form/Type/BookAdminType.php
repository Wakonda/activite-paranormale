<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use App\Form\Field\SourceEditType;
use App\Entity\TagWord;
use App\Form\EventListener\InternationalNameFieldListener;

class BookAdminType extends AbstractType
{
    public $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('introduction', TextareaType::class, ['required' => false])
			->add('illustration', IllustrationType::class, ['required' => false, 'base_path' => 'Book_Admin_ShowImageSelectorColorbox'])
            ->add('text', TextareaType::class, array('required' => false))
			->add('genre', EntityType::class, ['class'=>'App\Entity\LiteraryGenre',
					'required' => true,
					'constraints' => [new NotBlank()],
					'group_by' => function($choice, $key, $value) {
						if($choice->getFiction())
							return $this->translator->trans("book.admin.Fiction", [], "validators");
						
						return $this->translator->trans("book.admin.Nonfiction", [], "validators");
					},
					'query_builder' => function(\App\Repository\LiteraryGenreRepository $repository) use ($language) { return $repository->getGenreByLanguage($language);}])
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => array(new NotBlank()),
				'query_builder' => function(EntityRepository $er)
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
				))
			->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
            ->add('theme', ThemeEditType::class, ['locale' => $language, 'label' => 'Thème', 'class'=>'App\Entity\Theme', 'constraints' => [new NotBlank()], 'required' => true])
			->add('wikidata', TextType::class, ['required' => false])
		    ->add('authors', Select2EntityType::class, [
				'multiple' => true,
				'remote_route' => 'Biography_Admin_Autocomplete',
				'class' => 'App\Entity\Biography',
				'page_limit' => 10,
				'primary_key' => 'id',
				'text_property' => 'title',
				'allow_clear' => true,
				'delay' => 250,
				'allow_add' => [
					'enabled' => true,
					'new_tag_text' => ' (+)',
					'new_tag_prefix' => '__',
					'tag_separators' => '[","]'
				],
				'cache' => false,
				'req_params' => ['locale' => 'parent.children[language]'],
				'language' => $language,
				"required" => true,
				'constraints' => [new NotBlank()]
			])
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
				'choice_label'=>'title', 
				'required' => true,
				'constraints' => array(new NotBlank()),
				'choice_attr' => function($val, $key, $index) {
					return ['data-intl' => $val->getInternationalName()];
				},
				'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}))
            ->add('source', SourceEditType::class, array('required' => false))
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
		;
		
		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }

    public function getBlockPrefix()
    {
        return 'ap_book_bookadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Book',
			'locale' => 'fr'
		));
	}
}