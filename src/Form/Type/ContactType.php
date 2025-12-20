<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudoContact', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('emailContact', TextType::class, array('required' => true, 'constraints' => array(new NotBlank(), new Email())))
            ->add('subjectContact', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('messageContact', TextareaType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('captcha', TextType::class, array('required' => true, 'constraints' => array(new NotBlank()), 'mapped' => false))
		;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_contact_contacttype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Contact'
		));
	}
}