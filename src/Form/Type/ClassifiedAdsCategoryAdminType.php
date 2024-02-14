<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ClassifiedAdsCategoryAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, ['required' =>true, 'constraints' => [new NotBlank()]])
            ->add('language', EntityType::class, ['class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er)
				{
					return $er->createQueryBuilder('u')
							  ->orderBy('u.title', 'ASC');
				},
				'constraints' => [new NotBlank()],
			])
			->add('parentCategory', EntityType::class, ['class'=>'App\Entity\ClassifiedAdsCategory', 
				'choice_label'=>'title',
				'required' => false,
				'query_builder' => function(\App\Repository\ClassifiedAdsCategoryRepository $repository) use($language) { return $repository->getParentClassifiedAdsCategoryByLanguage($language);}
			])
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_classifiedadscategory_admintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\ClassifiedAdsCategory',
			'locale' => 'fr'
		]);
	}
}