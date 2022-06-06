<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class BannerAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('link', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('image', FileType::class, array('data_class' => null, 'required' => true))
			->add('display', CheckboxType::class, array(
					'required'  => true,
					'data' => true
				))
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
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_index_bannertype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Banner',
		));
	}
}