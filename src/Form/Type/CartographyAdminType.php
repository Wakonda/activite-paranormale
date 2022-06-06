<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Form\Type\FileSelectorType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use App\Entity\TagWord;

class CartographyAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('coordXMap', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('coordYMap', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('photo', FileType::class, array('data_class' => null, 'required' => true))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label'=>'title',
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
					{
						return $er->createQueryBuilder('u')
								->orderBy('u.title', 'ASC');
					},
				'constraints' => array(new NotBlank())
				))
            ->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
											'choice_label'=>'title',
											'required' => true,
											'constraints' => array(new NotBlank()),
											'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}))
			->add('pseudoUsed', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
											'choice_label'=>'title', 
											'required' => true,
											'constraints' => array(new NotBlank()),
											'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
											))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
											'choice_label'=>'title', 
											'required' => true,
											'constraints' => array(new NotBlank()),
											'choice_attr' => function($val, $key, $index) {
												return ['data-intl' => $val->getInternationalName()];
											},
											'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}))
											
			->add('linkGMaps', UrlType::class, array('required' => true, 'constraints' => [new NotBlank()]))
			->add('photo_selector', FileSelectorType::class, array('required' => false, 'mapped' => false, 'base_path' => 'Cartography_Admin_ShowImageSelectorColorbox', 'data' => $builder->getData()->getPhoto()))
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
        return 'ap_cartography_cartographyadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Cartography',
			'locale' => 'fr'
		));
	}
}