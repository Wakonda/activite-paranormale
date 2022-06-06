<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RoleUserAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('required' =>true, 'constraints' => array(new NotBlank())))
            ->add('internationalName', TextType::class, array('required' =>true, 'constraints' => array(new NotBlank())))
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_user_roleusertype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\RoleUser',
		));
	}
}