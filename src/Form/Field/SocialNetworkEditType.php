<?php

namespace App\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class SocialNetworkEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function getParent()
    {
        return HiddenType::class;
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'error_bubbling' => false
		]);
	}
}