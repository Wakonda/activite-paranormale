<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityManagerInterface;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use App\Entity\TagWord;
use App\Form\Field\DateTimePartialType;

class TestimonyAdminType extends AbstractType
{
	private $entityManager;

	public function __construct(EntityManagerInterface $entityManager) {
		$this->entityManager = $entityManager;
	}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$locationValue = null;
		$country = null;
		
		if(!empty($location = $builder->getData()->getLocation())) {
			$location = json_decode($location);
			$country = $this->entityManager->getRepository(\App\Entity\Region::class)->findOneBy(["internationalName" => $location->country_code]);
			$locationValue = $location->value;
		}

		$language = $options["locale"];
        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => true, 'constraints' => [new NotBlank()]])
			->add('pseudoUsed', TextType::class)
			->add('publicationDate', DateType::class, ['required' => true, 'widget' => 'single_text'])
            ->add('theme', ThemeEditType::class, ['locale' => $language, 'label' => 'Thème', 'class'=>'App\Entity\Theme', 'constraints' => [new NotBlank()], 'required' => true])
            ->add('language', EntityType::class, ['class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(\App\Repository\LanguageRepository $repository) { return $repository->getLangueOnForm();}
			])
			->add('state', EntityType::class, ['class'=>'App\Entity\State', 
				'choice_label'=>'title', 
				'required' => true,
				'choice_attr' => function($val, $key, $index) {
					return ['data-intl' => $val->getInternationalName()];
				},
				'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			])
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
			->add('country', EntityType::class, ['class'=>'App\Entity\Region', 
					'choice_label'=>'title', 
					'required' => false,
					'mapped' => false,
					'choice_value' => function ($entity) {
						return $entity ? $entity->getInternationalName() : '';
					},
					"data" => $country,
					'query_builder' => function(\App\Repository\RegionRepository $repository) use ($language) { return $repository->getCountryByLanguage($language);}])
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => [new NotBlank()],
					'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			))
			->add('location_selector', ChoiceType::class, ['choices' => [$locationValue => $locationValue], 'data' => $locationValue, 'multiple' => false, 'expanded' => false, "required" => false, "mapped" => false])
			->add('location', HiddenType::class)
			->add('sightingDate', DateTimePartialType::class, ['required' => false]);
    }

    public function getBlockPrefix()
    {
        return 'ap_testimony_testimonyadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Testimony',
			'locale' => 'fr'
		]);
	}
}