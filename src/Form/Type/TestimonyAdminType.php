<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use App\Entity\TagWord;

class TestimonyAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options["locale"];
        $builder
            ->add('title', TextType::class, array('label' => 'Titre', 'required' => true))
            ->add('text', TextareaType::class, array('label' => 'T?moignage', 'required' => true))
			->add('pseudoUsed', TextType::class)
			->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text'))
            ->add('theme', EntityType::class, array('label' => 'Th?me', 'class'=>'App\Entity\Theme',
											'choice_label'=>'title',
											'required' => true,
											'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}
											))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
											'choice_label'=>'title', 
											'required' => true,
											'query_builder' => function(\App\Repository\LanguageRepository $repository) { return $repository->getLangueOnForm();}
			))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
											'choice_label'=>'title', 
											'required' => true,
											'choice_attr' => function($val, $key, $index) {
												return ['data-intl' => $val->getInternationalName()];
											},
											'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			))
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
        return 'ap_testimony_testimonyadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Testimony',
			'validation_groups' => array('testimony_validation', 'mappedsuperclass_validation'),
			'locale' => 'fr'
		));
	}
}