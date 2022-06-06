<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\FormError;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class WitchcraftThemeToolAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('photo', FileType::class, array('data_class' => null, 'required' => true))
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
        return 'ap_witchcraft_witchcraftthemetooladmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\WitchcraftThemeTool'
		));
	}
}