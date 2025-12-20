<?php

namespace App\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class IdentifiersEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['enum'] = $options["enum"];
    }

    public function getParent(): ?string
    {
        return HiddenType::class;
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'error_bubbling' => false,
			'enum' => []
		]);
	}
}