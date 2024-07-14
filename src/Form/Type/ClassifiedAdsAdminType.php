<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use App\Service\Currency;

class ClassifiedAdsAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

		$fields = [];

        $builder
            ->add('title', TextType::class, ['required' =>true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => true, "constraints" => [new NotBlank()]])
			->add('currencyPrice', ChoiceType::class, ['choices' => Currency::getSymboleValues(), 'expanded' => false, 'multiple' => false, 'required' => false, 'translation_domain' => 'validators'])
			->add('price', NumberType::class, ['required' => true, 'translation_domain' => 'validators', "required" => false])
		    ->add('location', HiddenType::class, ['required' => false])
			->add('displayEmail', CheckboxType::class, ["required" => false])
			->add('illustration', IllustrationType::class, array('required' => true))
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
						}
					))
            ->add('category', EntityType::class, ['class'=>'App\Entity\ClassifiedAdsCategory', 
					'choice_label'=>'title', 
					'required' => true,
					'group_by' => 'getParentCategoryTitle',
					'constraints' => array(new NotBlank()),
					'query_builder' => function(EntityRepository $er) use ($language) {
						return $er->createQueryBuilder('u')
								  ->leftJoin('u.language', 'l')
								  ->where('l.abbreviation = :abbreviation')
								  ->setParameter('abbreviation', $language)
								  ->andWhere("u.parentCategory IS NOT NULL")
								  ->orderBy('u.title', 'ASC');
					}
			])
			->add('markAs', ChoiceType::class, [
				'choices'  => [
					"classifiedAds.read.MarkAs" => null,
					"classifiedAds.read.Spam" => "spam",
					"classifiedAds.read.Badcat" => "badcat",
					"classifiedAds.read.Repeated" => "repeated",
					"classifiedAds.read.Expired" => "expired",
					"classifiedAds.read.Offensive" => "offensive"
				],
				'translation_domain' => 'validators', "attr" => ["class" => "form-select"]
			])
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => [new NotBlank()],
					'choice_attr' => function($val, $key, $index) {
						return ['data-intl' => $val->getInternationalName()];
					},
					'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			))
			->add('contactName', TextType::class, ['required' => false])
			->add('contactEmail', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_classifiedads_admintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\ClassifiedAds',
			'locale' => 'fr'
		]);
	}
}