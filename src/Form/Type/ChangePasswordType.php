<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
	 /**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('current_password', PasswordType::class, array('label'=>'Current password',
				'mapped' => false,
				'constraints' => new UserPassword(),
				'required' => true
			))
			->add('password', RepeatedType::class, array(
				'first_options'  => ['label' => 'Password'],
				'second_options' => ['label' => 'Repeat Password'],
				'constraints' => new NotBlank(),
				'type' => PasswordType::class,
				'mapped' => false,
				'required' => true
			))
		;
	}
}