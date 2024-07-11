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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use App\Service\Currency;

class ClassifiedAdsType extends AbstractType
{
	public function __construct(private TokenStorageInterface $token){}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

		$fields = [];

        $builder
            ->add('title', TextType::class, ['required' =>true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => true, "constraints" => [new NotBlank()]])
			->add('currencyPrice', ChoiceType::class, ['choices' => Currency::getSymboleValues(), "data" => "EUR", 'expanded' => false, 'multiple' => false, 'required' => false, 'translation_domain' => 'validators'])
			->add('price', NumberType::class, ['required' => true, 'translation_domain' => 'validators', "required" => false])
		    ->add('location', HiddenType::class, ['required' => false])
			->add('displayEmail', CheckboxType::class, ["required" => false])
			->add('illustration', FileType::class, array('data_class' => null, 'required' => false))
            ->add('category', EntityType::class, array('class'=>'App\Entity\ClassifiedAdsCategory', 
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
			))
		;
		
		if(empty($this->token->getToken())) {
			$builder->add('contactName', TextType::class, ['required' => false])
			        ->add('contactEmail', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]]);
		}
    }

    public function getBlockPrefix()
    {
        return 'ap_classifiedads_type';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\ClassifiedAds',
			'locale' => 'fr'
		]);
	}
}