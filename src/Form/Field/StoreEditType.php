<?php

namespace App\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class StoreEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		foreach($options["fields"] as $key => $field) {
			switch($field["type"]) {
				case "choice":
					$builder->add($key, ChoiceType::class, ["choices" => $field["choices"], "label" => $field["label"], "required" => $field["required"], 'translation_domain' => 'validators']);
				break;
			}
		}
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'error_bubbling' => false,
			"fields" => []
		]);
	}
}