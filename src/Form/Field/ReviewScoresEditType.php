<?php

namespace App\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class ReviewScoresEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['enum'] = $options["enum"];
    }

    public function getParent()
    {
        return HiddenType::class;
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'error_bubbling' => false,
			'enum' => []
		]);
	}
}