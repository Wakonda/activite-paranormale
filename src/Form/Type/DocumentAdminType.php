<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use App\Form\Field\DatePartialType;

use App\Entity\TagWord;

class DocumentAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('pdfDoc', FileType::class, array('data_class' => null, 'required' => true))
            ->add('pseudoUsed', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
					'choice_label'=>'title',
					'required' => true,
					'query_builder' => function(EntityRepository $er)
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
					'constraints' => array(new NotBlank()
			)))
            ->add('documentFamily', EntityType::class, array('class'=>'App\Entity\DocumentFamily', 
					'choice_label'=>'title',
					'required' => true,
					'query_builder' => function(\App\Repository\DocumentFamilyRepository $repository) use ($language)
					{
						return $repository->getDocumentFamilyByLanguage($language);
					},
					'constraints' => array(new NotBlank()
			)))
			->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => [new NotBlank()]))
			->add('authorDocumentBiographies', EntityType::class, array('class' => 'App\Entity\Biography', 'choice_label' => 'title', 'multiple' => true, 'expanded' => false, 'query_builder' => function(\App\Repository\BiographyRepository $repository) use ($language) { return $repository->getBiographyByLanguage($language);}, 'constraints' => array(new NotBlank())))
			->add('releaseDateOfDocument', DatePartialType::class, ['required' => false])
			->add('text', TextareaType::class, array('required' => false))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => [new NotBlank()],
					'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			))
            ->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
					'choice_label'=>'title',
					'required' => true,
					'constraints' => [new NotBlank()],
					'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title',
					'required' => true,
					'constraints' => [new NotBlank()],
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
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => null, 'data' => $builder->getData()->getPdfDoc()])
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_document_documentadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Document',
			'locale' => 'fr'
		));
	}
}