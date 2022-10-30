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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use App\Form\Field\SourceEditType;
use App\Entity\TagWord;

class BookAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('photo', FileType::class, array('data_class' => null, 'required' => false))
            ->add('text', TextareaType::class, array('required' => false))
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
			->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
            ->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
						'choice_label'=>'title',
						'required' => true,
						'constraints' => array(new NotBlank()),
						'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}))
			->add('wikidata', TextType::class, ['required' => false])
			->add('photo_selector', FileSelectorType::class, array('required' => false, 'mapped' => false, 'base_path' => null, 'data' => $builder->getData()->getPhoto()))
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