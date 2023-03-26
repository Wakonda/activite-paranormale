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

use App\Form\Field\SourceEditType;
use App\Entity\TagWord;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class CreepyStoryAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('language', EntityType::class, ['class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er) {
					return $er->createQueryBuilder('u')
							  ->orderBy('u.title', 'ASC');
				}
			])
            ->add('theme', ThemeEditType::class, ['locale' => $language, 'label' => 'Thème', 'class'=>'App\Entity\Theme', 'constraints' => [new NotBlank()], 'required' => true])
			->add('licence', EntityType::class, ['class'=>'App\Entity\Licence', 
				'choice_label'=>'title',
				'constraints' => [new NotBlank()],
				'required' => true,
				'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			])
			->add('state', EntityType::class, ['class'=>'App\Entity\State', 
				'choice_label'=>'title',
				'constraints' => [new NotBlank()],
				'choice_attr' => function($val, $key, $index) {
					return ['data-intl' => $val->getInternationalName()];
				},
				'required' => true,
				'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			])
            ->add('source', SourceEditType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('pseudoUsed', TextType::class, ['required' =>true, 'constraints' => [new NotBlank()]])
			->add('illustration', IllustrationType::class, ['required' => true, 'base_path' => 'CreepyStory_Admin_ShowImageSelectorColorbox'])
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
			]);
    }

    public function getBlockPrefix()
    {
        return 'ap_creepystory_creepystoryadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\CreepyStory',
			'locale' => 'fr'
		]);
	}
}