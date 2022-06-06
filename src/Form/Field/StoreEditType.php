<?php

namespace App\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class StoreEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		foreach($options["fields"] as $key => $field) {
			switch($field["type"]) {
				case "choice":
					$builder->add($key, ChoiceType::class, ["choices" => $field["choices"], "label" => $field["label"], "required" => $field["required"], 'translation_domain' => 'validators']);
				break;
			}
		}
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'error_bubbling' => false,
			"fields" => []
		));
	}
}