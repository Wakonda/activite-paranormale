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
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use App\Entity\TagWord;

class PhotoAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
					{
						return $er->createQueryBuilder('u')
								->orderBy('u.title', 'ASC');
					},
			))
            ->add('theme', ThemeEditType::class, ['locale' => $language, 'label' => 'Th�me', 'class'=>'App\Entity\Theme', 'constraints' => [new NotBlank()], 'required' => true])
			->add('pseudoUsed', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
			->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
					'choice_label'=>'title',
					'constraints' => array(new NotBlank()),
					'required' => true,
					'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
					))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title',
					'constraints' => array(new NotBlank()),
					'choice_attr' => function($val, $key, $index) {
						return ['data-intl' => $val->getInternationalName()];
					},
					'required' => true,
					'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			))
			->add('illustration', IllustrationType::class, ['required' => true, 'base_path' => 'Photo_Admin_ShowImageSelectorColorbox', 'file_path' => $builder->getData()->getAssetImagePath()])
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
        return 'ap_photo_photoadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Photo',
			'locale' => 'fr'
		));
	}
}