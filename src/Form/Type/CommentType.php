<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$userType = $options['userType'];
		
		$builder
            ->add('messageComment', TextareaType::class, array('label' => null, 'constraints' => array(new NotBlank())))
		;

		if($userType)
		{
			$builder->add('anonymousAuthorComment', TextType::class, array('label' => null, 'constraints' => array(new NotBlank())))
					->add('emailComment', EmailType::class, array('label' => null, 'required' => false, 'constraints' => array(new Email())))
			;
		}
    }

    public function getBlockPrefix()
    {
        return 'ap_comment_commenttype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Comment',
			'userType' => null
		));
	}
}