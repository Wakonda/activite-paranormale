<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class SearchEngineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('query', TextType::class, ["attr" => ["placeholder" => "index.leftMenu.Search"], "translation_domain" => "validators"])
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_search_searchenginetype';
    }
}