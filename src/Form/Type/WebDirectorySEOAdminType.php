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

class WebDirectorySEOAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('returnLink', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('title', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('link', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('text', TextareaType::class, array('required' => false))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(EntityRepository $er) {
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				}
			))
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_webdirectoryseo_webdirectoryadmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\WebDirectorySEO'
		]);
	}
}