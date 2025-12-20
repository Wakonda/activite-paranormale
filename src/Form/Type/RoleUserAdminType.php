<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RoleUserAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, array('required' =>true, 'constraints' => array(new NotBlank())))
            ->add('internationalName', TextType::class, array('required' =>true, 'constraints' => array(new NotBlank())))
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_user_roleusertype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\RoleUser',
		));
	}
}