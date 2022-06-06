<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class SurThemeAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('label'=>'Titre', 'required' =>true, 'constraints' => array(new NotBlank())))
			->add('internationalName', TextType::class, array('label'=>'Nom international', 'required' =>true, 'constraints' => array(new NotBlank())))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
											'choice_label'=>'title',
											'required' => true,
											'constraints' => array(new NotBlank()),
											'query_builder' => function(EntityRepository $er)
														{
															return $er->createQueryBuilder('u')
																	->orderBy('u.title', 'ASC');
														},
											))
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_index_surthemeadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\SurTheme',
		));
	}
}